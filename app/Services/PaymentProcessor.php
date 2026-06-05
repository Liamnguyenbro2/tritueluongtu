<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\LessonUnlock;
use App\Models\PaymentOrder;
use App\Models\Plan;
use App\Models\Referral;
use App\Models\Subscription;
use App\Models\TransactionLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentProcessor
{
    public function __construct(
        private readonly WalletLedgerService $ledger,
        private readonly ReferralCommissionService $referrals,
        private readonly TransactionLogService $transactionLogs,
    ) {
    }

    public function createOrder(int $userId, int $planId, int $amountVnd, string $paymentMethod = 'bank_qr', array $metadata = []): PaymentOrder
    {
        $user = User::query()->findOrFail($userId);
        $plan = Plan::query()->findOrFail($planId);
        $transactionType = $this->transactionLogs->determinePlanTransactionType($user);

        $order = PaymentOrder::query()->create([
            'user_id' => $userId,
            'plan_id' => $planId,
            'code' => 'QI'.Str::upper(Str::random(8)),
            'amount_vnd' => $amountVnd,
            'status' => 'pending',
            'metadata' => array_merge([
                'payment_method' => $paymentMethod,
                'qr' => $paymentMethod === 'bank_qr' ? config('quantum.bank_qr') : null,
                'transaction_type' => $transactionType,
            ], $metadata),
        ]);

        $this->transactionLogs->upsertPaymentOrderLog(
            $order,
            $transactionType,
            TransactionLog::STATUS_PENDING,
            $this->transactionLogs->planTransactionDescription($plan, $transactionType),
            $this->transactionLogs->paymentMethodNote($order)
        );

        return $order;
    }

    public function payWithWallet(User $user, Plan $plan, int $amountVnd, array $metadata = []): PaymentOrder
    {
        return DB::transaction(function () use ($user, $plan, $amountVnd, $metadata) {
            $order = $this->createOrder($user->id, $plan->id, $amountVnd, 'wallet', $metadata);
            $wallet = $this->ledger->walletForUser($user);

            if ($wallet->is_locked) {
                throw new \RuntimeException(html_entity_decode('V&#237; c&#7911;a b&#7841;n &#273;ang b&#7883; kh&#243;a t&#7841;m th&#7901;i.'));
            }

            if ($wallet->balance_vnd < $amountVnd) {
                throw new \RuntimeException(html_entity_decode('S&#7889; d&#432; v&#237; kh&#244;ng &#273;&#7911; &#273;&#7875; thanh to&#225;n g&#243;i n&#224;y.'));
            }

            $this->ledger->debit(
                $wallet,
                $amountVnd,
                'wallet_payment',
                $order,
                html_entity_decode('Thanh to&#225;n g&#243;i ').$plan->name.html_entity_decode(' b&#7857;ng v&#237; s&#7889; d&#432;')
            );

            return $this->complete($order, 'WALLET-'.$order->code);
        });
    }

    public function complete(PaymentOrder $order, string $providerTransactionId, ?string $paidAt = null): PaymentOrder
    {
        return DB::transaction(function () use ($order, $providerTransactionId, $paidAt) {
            $lockedOrder = PaymentOrder::query()->lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->status === 'paid') {
                return $lockedOrder;
            }

            $lockedOrder->update([
                'status' => 'paid',
                'provider_transaction_id' => $providerTransactionId,
                'paid_at' => $paidAt ? Carbon::parse($paidAt) : now(),
            ]);

            $user = $lockedOrder->user()->firstOrFail();
            $plan = $lockedOrder->plan()->firstOrFail();
            $now = now();
            $currentEndsAt = Subscription::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->where('ends_at', '>', $now)
                ->max('ends_at');
            $extensionBase = $currentEndsAt ? Carbon::parse($currentEndsAt) : $now;
            $subscriptionEndsAt = $extensionBase->copy()->addDays((int) $plan->duration_days);

            $subscription = Subscription::query()->create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'starts_at' => $now,
                'ends_at' => $subscriptionEndsAt,
                'status' => 'active',
                'grants_full_library' => $plan->code !== config('quantum.plans.monthly_code'),
            ]);

            $this->grantSelectedLessonIfNeeded($user, $lockedOrder, $plan, $subscription, $now);

            $transactionType = $lockedOrder->metadata['transaction_type']
                ?? $this->transactionLogs->determinePlanTransactionType($user);

            $this->transactionLogs->upsertPaymentOrderLog(
                $lockedOrder,
                $transactionType,
                TransactionLog::STATUS_SUCCESS,
                $this->transactionLogs->planTransactionDescription($plan, $transactionType),
                trim($this->transactionLogs->paymentMethodNote($lockedOrder).' | '.html_entity_decode('M&#227; giao d&#7883;ch').': '.$providerTransactionId)
            );

            Referral::query()->where('referred_id', $user->id)->whereNull('activated_at')->update(['activated_at' => $now]);

            $amount = (int) $lockedOrder->amount_vnd;
            $allocation = config('quantum.allocation');
            $referral = $user->referredBy()->first();
            $referrer = $referral?->referrer()->first();
            $companyAdmin = User::query()->where('is_admin', true)->orderBy('id')->firstOrFail();
            $activatedAt = ($lockedOrder->paid_at ?? $now)->copy();
            $triggerMemo = "#{$user->id} - {$user->email} ".html_entity_decode('k&#237;ch ho&#7841;t').' - '.$activatedAt->format('d/m/Y | H:i');
            $companyWallet = $this->ledger->walletForUser($companyAdmin);

            if ($referrer) {
                $referralAmount = intdiv($amount * (int) $allocation['affiliate'], 100);
                $this->ledger->credit(
                    $this->ledger->walletForUser($referrer),
                    $referralAmount,
                    'referral_commission',
                    $lockedOrder,
                    html_entity_decode('Hoa h&#7891;ng affiliate ').$triggerMemo,
                );
            }

            $this->ledger->credit($companyWallet, intdiv($amount * (int) $allocation['vat'], 100), 'company_vat', $lockedOrder, html_entity_decode('Ghi nh&#7853;n ph&#237; VAT ').$triggerMemo);
            $this->ledger->credit($companyWallet, intdiv($amount * (int) $allocation['company_revenue'], 100), 'company_revenue', $lockedOrder, html_entity_decode('Doanh thu do t&#224;i kho&#7843;n ').$triggerMemo);
            $this->ledger->credit($this->ledger->systemWallet('shared_pool'), intdiv($amount * (int) $allocation['shared_pool'], 100), 'payment_shared_pool', $lockedOrder, html_entity_decode('Ghi nh&#7853;n Pool Share ').$triggerMemo);

            return $lockedOrder->refresh();
        });
    }

    private function grantSelectedLessonIfNeeded(User $user, PaymentOrder $order, Plan $plan, Subscription $subscription, Carbon $now): void
    {
        if ($plan->code !== config('quantum.plans.monthly_code')) {
            return;
        }

        $lessonId = data_get($order->metadata, 'selected_lesson_id');

        if (! $lessonId) {
            return;
        }

        $lesson = Lesson::query()
            ->where('is_trial', false)
            ->find($lessonId);

        if (! $lesson) {
            return;
        }

        LessonUnlock::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'subscription_id' => $subscription->id,
                'amount_vnd' => (int) $order->amount_vnd,
                'unlocked_at' => $now,
                'expires_at' => $subscription->ends_at,
            ]
        );
    }
}
