<?php

namespace App\Http\Controllers;

use App\Models\BankWebhookEvent;
use App\Models\PaymentOrder;
use App\Services\BankWebhookVerifier;
use App\Services\PaymentProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankWebhookController extends Controller
{
    public function __invoke(Request $request, BankWebhookVerifier $verifier, PaymentProcessor $payments): JsonResponse
    {
        if (! $verifier->valid($request)) {
            return response()->json(['message' => 'invalid signature'], 403);
        }

        $data = $request->validate([
            'provider_transaction_id' => ['required', 'string'],
            'amount' => ['required', 'integer'],
            'description' => ['required', 'string'],
            'paid_at' => ['nullable', 'date'],
        ]);

        $event = BankWebhookEvent::query()->firstOrCreate(
            ['provider_transaction_id' => $data['provider_transaction_id']],
            ['status' => 'received', 'payload' => $request->all()]
        );

        if ($event->wasRecentlyCreated === false && $event->status === 'processed') {
            return response()->json(['message' => 'duplicate ignored']);
        }

        $order = PaymentOrder::query()
            ->where('code', $data['description'])
            ->where('amount_vnd', $data['amount'])
            ->where('status', 'pending')
            ->first();

        if (! $order) {
            $event->update(['status' => 'ignored', 'message' => 'order not found']);

            return response()->json(['message' => 'order not found'], 404);
        }

        $payments->complete($order, $data['provider_transaction_id'], $data['paid_at'] ?? null);
        $event->update(['status' => 'processed']);

        return response()->json(['message' => 'ok']);
    }
}
