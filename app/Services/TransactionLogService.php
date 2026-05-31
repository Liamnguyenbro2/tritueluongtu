<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Models\PaymentOrder;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\TransactionLog;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;

class TransactionLogService
{
    public function determinePlanTransactionType(User $user): string
    {
        $hasActiveSubscription = Subscription::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->exists();

        return $hasActiveSubscription
            ? TransactionLog::TYPE_PLAN_RENEWAL
            : TransactionLog::TYPE_PLAN_UPGRADE;
    }

    public function upsertPaymentOrderLog(PaymentOrder $order, string $transactionType, string $status, string $description, ?string $notes = null): TransactionLog
    {
        return TransactionLog::query()->updateOrCreate(
            [
                'user_id' => $order->user_id,
                'reference_id' => $order->code,
            ],
            [
                'transaction_type' => $transactionType,
                'amount' => -abs((int) $order->amount_vnd),
                'description' => $description,
                'notes' => $notes,
                'status' => $status,
            ]
        );
    }

    public function recordLedgerEntry(LedgerEntry $entry): ?TransactionLog
    {
        $wallet = $entry->wallet()->with('owner')->first();

        if (! $wallet instanceof Wallet || $wallet->type !== 'user') {
            return null;
        }

        $owner = $wallet->owner;

        if (! $owner instanceof User) {
            return null;
        }

        $mapped = $this->mapLedgerEntry($entry);

        if ($mapped === null) {
            return null;
        }

        return TransactionLog::query()->create([
            'user_id' => $owner->id,
            'transaction_type' => $mapped['transaction_type'],
            'amount' => (int) $entry->amount_vnd,
            'description' => $mapped['description'],
            'notes' => $mapped['notes'],
            'status' => $mapped['status'],
            'reference_id' => $mapped['reference_id'],
        ]);
    }

    public function markWithdrawalSuccessful(WithdrawalRequest $withdrawal): void
    {
        TransactionLog::query()
            ->where('user_id', $withdrawal->user_id)
            ->where('reference_id', $this->withdrawalReferenceId($withdrawal))
            ->where('transaction_type', TransactionLog::TYPE_MONEY_OUT)
            ->where('status', TransactionLog::STATUS_PENDING)
            ->latest('id')
            ->first()
            ?->update([
                'status' => TransactionLog::STATUS_SUCCESS,
                'description' => 'Rút tiền về tài khoản ngân hàng.',
                'notes' => 'Yêu cầu rút tiền đã được duyệt.',
            ]);
    }

    public function markWithdrawalFailed(WithdrawalRequest $withdrawal, ?string $reason = null): void
    {
        TransactionLog::query()
            ->where('user_id', $withdrawal->user_id)
            ->where('reference_id', $this->withdrawalReferenceId($withdrawal))
            ->where('transaction_type', TransactionLog::TYPE_MONEY_OUT)
            ->where('status', TransactionLog::STATUS_PENDING)
            ->latest('id')
            ->first()
            ?->update([
                'status' => TransactionLog::STATUS_FAILED,
                'description' => 'Yêu cầu rút tiền đã bị từ chối.',
                'notes' => $reason ? 'Lý do: '.$reason : 'Yêu cầu rút tiền không được duyệt.',
            ]);
    }

    public function planTransactionDescription(Plan $plan, string $transactionType): string
    {
        return match ($transactionType) {
            TransactionLog::TYPE_PLAN_RENEWAL => "Gia hạn gói {$plan->name}.",
            default => "Nâng cấp gói {$plan->name}.",
        };
    }

    public function paymentMethodNote(PaymentOrder $order): string
    {
        $paymentMethod = $order->metadata['payment_method'] ?? 'bank_qr';

        return match ($paymentMethod) {
            'wallet' => 'Phương thức: Ví số dư',
            default => 'Phương thức: Mã QR',
        };
    }

    private function mapLedgerEntry(LedgerEntry $entry): ?array
    {
        return match ($entry->type) {
            'wallet_payment' => null,
            'referral_commission' => $this->buildMappedEntry($entry, TransactionLog::TYPE_AFFILIATE, TransactionLog::STATUS_SUCCESS, $entry->memo ?: 'Hoa hồng affiliate được cộng vào ví.'),
            'pool_share_payout' => $this->buildMappedEntry($entry, TransactionLog::TYPE_POOL_SHARE, TransactionLog::STATUS_SUCCESS, $entry->memo ?: 'Nhận Pool Share.'),
            'withdrawal_hold' => $this->buildMappedEntry($entry, TransactionLog::TYPE_MONEY_OUT, TransactionLog::STATUS_PENDING, 'Yêu cầu rút tiền đang chờ xử lý.', 'Tiền đã được tạm giữ trong ví.'),
            'withdrawal_completed' => $this->buildMappedEntry($entry, TransactionLog::TYPE_MONEY_OUT, TransactionLog::STATUS_SUCCESS, 'Rút tiền về tài khoản ngân hàng.', 'Yêu cầu rút tiền đã được duyệt.'),
            'withdrawal_refund' => $this->buildMappedEntry($entry, TransactionLog::TYPE_REFUND, TransactionLog::STATUS_SUCCESS, $entry->memo ?: 'Hoàn tiền về ví.'),
            'admin_transfer_in' => $this->buildMappedEntry($entry, TransactionLog::TYPE_MONEY_IN, TransactionLog::STATUS_SUCCESS, $entry->memo ?: 'Nhận tiền từ admin.'),
            'admin_transfer_out' => $this->buildMappedEntry($entry, TransactionLog::TYPE_MONEY_OUT, TransactionLog::STATUS_SUCCESS, $entry->memo ?: 'Chuyển tiền cho người dùng khác.'),
            default => $this->buildMappedEntry(
                $entry,
                $entry->amount_vnd >= 0 ? TransactionLog::TYPE_MONEY_IN : TransactionLog::TYPE_MONEY_OUT,
                TransactionLog::STATUS_SUCCESS,
                $entry->memo ?: 'Giao dịch ví phát sinh.'
            ),
        };
    }

    private function buildMappedEntry(LedgerEntry $entry, string $transactionType, string $status, string $description, ?string $notes = null): array
    {
        return [
            'transaction_type' => $transactionType,
            'status' => $status,
            'description' => $description,
            'notes' => $notes,
            'reference_id' => $this->referenceIdForLedgerEntry($entry),
        ];
    }

    private function referenceIdForLedgerEntry(LedgerEntry $entry): string
    {
        $reference = $entry->reference;

        if ($reference instanceof WithdrawalRequest) {
            return $this->withdrawalReferenceId($reference);
        }

        if ($reference instanceof PaymentOrder) {
            return $reference->code;
        }

        return 'LE-'.$entry->id;
    }

    private function withdrawalReferenceId(WithdrawalRequest $withdrawal): string
    {
        return 'WD-'.$withdrawal->id;
    }
}
