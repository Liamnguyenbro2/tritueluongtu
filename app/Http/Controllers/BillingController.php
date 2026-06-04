<?php

namespace App\Http\Controllers;

use App\Models\PaymentOrder;
use App\Models\Plan;
use App\Services\PaymentProcessor;
use App\Services\WalletLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BillingController extends Controller
{
    public function index(Request $request, WalletLedgerService $wallets): View
    {
        return view('billing.index', [
            'plans' => Plan::query()->orderBy('price_vnd')->get(),
            'orders' => PaymentOrder::query()
                ->with('plan')
                ->where('user_id', $request->user()->id)
                ->latest()
                ->limit(10)
                ->get(),
            'wallet' => $wallets->walletForUser($request->user()),
        ]);
    }

    public function qrImage(Request $request, Plan $plan): StreamedResponse
    {
        abort_unless($request->user(), 403);
        abort_unless($plan->bank_qr_enabled && $plan->bank_qr_image_path, 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($plan->bank_qr_image_path), 404);

        return $disk->response($plan->bank_qr_image_path, null, [
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, noarchive, nosnippet',
        ]);
    }

    public function store(Request $request, PaymentProcessor $payments): RedirectResponse
    {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'payment_method' => ['required', 'in:bank_qr,wallet'],
        ]);

        $plan = Plan::query()->findOrFail($data['plan_id']);

        if (! $plan->allowsPaymentMethod($data['payment_method'])) {
            throw ValidationException::withMessages([
                'payment_method' => 'PhÆ°Æ¡ng thá»©c thanh toÃ¡n nÃ y Ä‘ang táº¡m táº¯t cho gÃ³i Ä‘Ã£ chá»n.',
            ]);
        }

        if ($data['payment_method'] === 'wallet') {
            try {
                $order = $payments->payWithWallet($request->user(), $plan);
            } catch (RuntimeException) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Số dư ví không đủ để thanh toán gói này.',
                ]);
            }

            return redirect()->route('billing')->with('status', "Đã thanh toán bằng ví số dư: {$order->code}");
        }

        $order = $payments->createOrder($request->user()->id, $plan->id, (int) $plan->price_vnd);

        return redirect()->route('billing')->with('status', "Tạo đơn QR thành công: {$order->code}");
    }

    public function show(Request $request, PaymentOrder $order): View
    {
        abort_unless($request->user()->is_admin || $order->user_id === $request->user()->id, 403);

        return view('billing.show', compact('order'));
    }
}
