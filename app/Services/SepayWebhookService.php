<?php

namespace App\Services;

use App\Jobs\ProcessSepayWebhookJob;
use App\Models\PaymentTransaction;
use App\Models\SepayWebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SepayWebhookService
{
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

        ProcessSepayWebhookJob::dispatch($log->id);

        return $log->fresh();
    }

    public function process(SepayWebhookLog $log): void
    {
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

        PaymentTransaction::query()->updateOrCreate(
            ['gateway_transaction_id' => $gatewayTransactionId],
            [
                'gateway' => 'sepay',
                'order_code' => $this->extractString($payload, ['order_code', 'orderCode', 'content', 'description']),
                'amount' => $this->extractAmount($payload),
                'transaction_type' => $this->extractString($payload, ['transaction_type', 'transactionType', 'type']) ?? 'bank_transfer',
                'status' => $this->extractString($payload, ['status']) ?? 'received',
                'raw_payload' => $payload,
                'processed_at' => now(),
            ]
        );

        $log->update(['status' => 'processed']);
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
}
