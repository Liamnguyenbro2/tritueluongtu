<?php

namespace App\Services;

use App\Jobs\ProcessSepayWebhookJob;
use App\Models\PaymentOrder;
use App\Models\PaymentTransaction;
use App\Models\SepayWebhookLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SepayWebhookService
{
    public function __construct(
        private readonly PaymentProcessor $payments,
    ) {}

    public function receive(Request $request): SepayWebhookLog
    {
        $payload = $this->extractPayload($request);
        $headers = $this->extractHeaders($request);

        $log = SepayWebhookLog::query()->create([
            'webhook_uuid' => (string) Str::uuid(),
            'headers' => $headers,
            'payload' => $payload,
            'ip_address' => $request->ip(),
            'status' => 'received',
        ]);

        Log::info('sepay webhook received', [
            'webhook_uuid' => $log->webhook_uuid,
            'ip_address' => $log->ip_address,
            'headers' => $headers,
            'payload' => $payload,
        ]);

        $log->update(['status' => 'queued']);

        // Shared hosting may not run a persistent queue worker. Processing after
        // the response keeps the webhook fast without leaving payments queued.
        ProcessSepayWebhookJob::dispatchAfterResponse($log->id);

        return $log->fresh();
    }

    public function isAuthorized(Request $request): bool
    {
        if (! config('sepay.enabled', true)) {
            return false;
        }

        $expectedToken = trim((string) (config('sepay.webhook_verify_token') ?: config('sepay.webhook_secret')));

        if ($expectedToken === '') {
            return true;
        }

        $authorization = trim((string) $request->header('Authorization'));
        $authorizationToken = preg_replace('/^(?:Bearer|Apikey)\s+/i', '', $authorization);
        $providedTokens = array_filter([
            trim((string) $request->header('X-Sepay-Token')),
            trim((string) $request->header('X-Webhook-Token')),
            trim((string) $authorizationToken),
        ]);

        foreach ($providedTokens as $providedToken) {
            if (hash_equals($expectedToken, $providedToken)) {
                return true;
            }
        }

        return false;
    }

    public function process(SepayWebhookLog $log): void
    {
        if (in_array($log->status, ['processed', 'duplicate'], true)) {
            return;
        }

        $payload = is_array($log->payload) ? $log->payload : [];
        $gatewayTransactionId = $this->extractString($payload, [
            'gateway_transaction_id',
            'transaction_id',
            'transactionId',
            'id',
            'transfer_id',
            'transferId',
            'reference',
            'reference_code',
            'referenceCode',
        ]) ?? ('sepay-webhook-'.$log->webhook_uuid);
        $rawContent = $this->extractString($payload, ['order_code', 'orderCode', 'content', 'description']);
        $orderCode = $this->extractOrderCode($rawContent);
        $amount = $this->extractAmount($payload);
        $transactionType = $this->extractString($payload, ['transaction_type', 'transactionType', 'type', 'transferType']) ?? 'bank_transfer';
        $direction = strtolower((string) $this->extractString($payload, ['transfer_type', 'transferType', 'direction']));
        $paidAt = $this->extractString($payload, [
            'transaction_date',
            'transactionDate',
            'paid_at',
            'paidAt',
        ]);
        $paymentOccurredAt = $paidAt ? Carbon::parse($paidAt) : now();

        DB::transaction(function () use ($log, $payload, $gatewayTransactionId, $orderCode, $amount, $transactionType, $direction, $paidAt, $paymentOccurredAt) {
            $transaction = PaymentTransaction::query()->firstOrCreate(
                ['gateway_transaction_id' => $gatewayTransactionId],
                [
                    'gateway' => 'sepay',
                    'order_code' => $orderCode,
                    'amount' => $amount,
                    'transaction_type' => $transactionType,
                    'status' => 'received',
                    'raw_payload' => $payload,
                ]
            );

            if (! $transaction->wasRecentlyCreated && $transaction->processed_at !== null) {
                $log->update(['status' => 'duplicate']);

                return;
            }

            if (! $orderCode) {
                $this->finishTransaction($transaction, $log, 'invalid_order_code');

                return;
            }

            if ($amount <= 0) {
                $this->finishTransaction($transaction, $log, 'invalid_amount');

                return;
            }

            if (in_array($direction, ['out', 'debit', 'outgoing'], true)) {
                $this->finishTransaction($transaction, $log, 'ignored_direction');

                return;
            }

            $order = PaymentOrder::query()
                ->where('code', $orderCode)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                $this->finishTransaction($transaction, $log, 'order_not_found');

                return;
            }

            if ((int) $order->amount_vnd !== $amount) {
                $this->finishTransaction($transaction, $log, 'amount_mismatch');

                return;
            }

            if ($order->status === 'paid') {
                $this->finishTransaction($transaction, $log, 'duplicate');

                return;
            }

            $canReopenCancelledOrder = in_array($order->status, ['cancelled', 'expired'], true)
                && $order->expires_at
                && ! $paymentOccurredAt->isAfter($order->expires_at);
            $paymentIsAfterExpiry = $order->expires_at
                && $paymentOccurredAt->isAfter($order->expires_at);

            if (
                ($order->status !== 'pending' && ! $canReopenCancelledOrder)
                || $paymentIsAfterExpiry
            ) {
                if ($order->status === 'pending') {
                    $order->update(['status' => 'cancelled']);
                }

                $this->finishTransaction($transaction, $log, 'order_expired');

                return;
            }

            if ($canReopenCancelledOrder) {
                $order->update(['status' => 'pending']);
            }

            $this->payments->complete($order, $gatewayTransactionId, $paidAt);
            $this->finishTransaction($transaction, $log, 'processed');
        });
    }

    private function extractPayload(Request $request): array
    {
        $payload = $request->all();

        if ($payload !== []) {
            return $payload;
        }

        $rawBody = trim((string) $request->getContent());

        if ($rawBody === '') {
            return [];
        }

        $decoded = json_decode($rawBody, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return ['_raw' => $rawBody];
    }

    private function extractHeaders(Request $request): array
    {
        return collect($request->headers->all())
            ->map(function (mixed $value) {
                if (! is_array($value)) {
                    return $value;
                }

                return count($value) === 1 ? $value[0] : array_values($value);
            })
            ->all();
    }

    private function extractString(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = data_get($payload, $key);

            if ($value === null) {
                continue;
            }

            $text = trim((string) $value);

            if ($text !== '') {
                return $text;
            }
        }

        return null;
    }

    private function extractAmount(array $payload): int
    {
        foreach (['amount', 'transfer_amount', 'transferAmount', 'value'] as $key) {
            $value = data_get($payload, $key);

            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return 0;
    }

    private function extractOrderCode(?string $content): ?string
    {
        if (! $content) {
            return null;
        }

        if (preg_match('/\bTTLT[\s._-]*([YCPW])[\s._-]*(\d+)\b/i', $content, $matches) === 1) {
            return 'TTLT-'.strtoupper($matches[1]).'-'.$matches[2];
        }

        $trimmed = strtoupper(trim($content));

        return $trimmed !== '' ? $trimmed : null;
    }

    private function finishTransaction(PaymentTransaction $transaction, SepayWebhookLog $log, string $status): void
    {
        $transaction->update([
            'status' => $status,
            'processed_at' => now(),
        ]);
        $log->update(['status' => $status]);
    }
}
