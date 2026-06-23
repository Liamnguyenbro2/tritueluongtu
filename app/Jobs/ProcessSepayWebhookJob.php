<?php

namespace App\Jobs;

use App\Models\SepayWebhookLog;
use App\Services\SepayWebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessSepayWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(public int $webhookLogId)
    {
    }

    public function handle(SepayWebhookService $service): void
    {
        $log = SepayWebhookLog::query()->find($this->webhookLogId);

        if (! $log) {
            return;
        }

        try {
            $service->process($log);
        } catch (\Throwable $exception) {
            $log->update(['status' => 'failed']);

            Log::error('sepay webhook processing failed', [
                'webhook_uuid' => $log->webhook_uuid,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
