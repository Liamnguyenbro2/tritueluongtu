<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonUnlock;
use App\Models\PaymentOrder;
use App\Models\Plan;
use App\Models\SiteSetting;
use App\Services\PaymentProcessor;
use App\Services\WalletLedgerService;
use Illuminate\Http\JsonResponse;
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
            'lessons' => Lesson::query()
                ->where('is_trial', false)
                ->orderBy('position')
                ->get(['id', 'position', 'title', 'unlock_price_vnd']),
            'unlockedLessonIds' => LessonUnlock::query()
                ->where('user_id', $request->user()->id)
                ->where('expires_at', '>', now())
                ->pluck('lesson_id')
                ->all(),
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

        $download = $request->boolean('download');
        $filename = sprintf(
            'plan-qr-%s.%s',
            $plan->code ?: $plan->id,
            pathinfo($plan->bank_qr_image_path, PATHINFO_EXTENSION) ?: 'png'
        );

        return $disk->response($plan->bank_qr_image_path, $download ? $filename : null, [
            'Content-Disposition' => $download
                ? 'attachment; filename="'.$filename.'"'
                : 'inline',
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
            'lesson_id' => ['nullable', 'exists:lessons,id'],
        ]);

        $plan = Plan::query()->findOrFail($data['plan_id']);
        $selectedLesson = $this->resolveMonthlyLesson($request, $plan, $data['lesson_id'] ?? null);

        if (! $plan->allowsPaymentMethod($data['payment_method'])) {
            throw ValidationException::withMessages([
                'payment_method' => 'Phuong thuc thanh toan nay dang tam tat cho goi da chon.',
            ]);
        }

        $orderMetadata = [];

        if ($selectedLesson) {
            $orderMetadata['selected_lesson_id'] = $selectedLesson->id;
            $orderMetadata['selected_lesson_title'] = $selectedLesson->title;
            $orderMetadata['selected_lesson_price_vnd'] = (int) $selectedLesson->unlock_price_vnd;
        }

        $checkoutAmount = $selectedLesson
            ? (int) $selectedLesson->unlock_price_vnd
            : (int) $plan->price_vnd;

        if ($data['payment_method'] === 'wallet') {
            try {
                $order = $payments->payWithWallet($request->user(), $plan, $checkoutAmount, $orderMetadata);
            } catch (RuntimeException $exception) {
                throw ValidationException::withMessages([
                    'payment_method' => $exception->getMessage() ?: 'So du vi khong du de thanh toan goi nay.',
                ]);
            }

            return redirect()->route('billing')->with(
                'status',
                $selectedLesson
                    ? "Da thanh toan va mo khoa bai {$selectedLesson->title}: {$order->code}"
                    : "Da thanh toan bang vi so du: {$order->code}"
            );
        }

        $order = $payments->createOrder(
            $request->user()->id,
            $plan->id,
            $checkoutAmount,
            'bank_qr',
            $orderMetadata
        );

        return redirect()->route('billing.orders.show', $order)->with(
            'status',
            $selectedLesson
                ? "Tao don QR thanh cong cho bai {$selectedLesson->title}: {$order->code}"
                : "Tao don QR thanh cong: {$order->code}"
        );
    }

    public function walletTopup(Request $request, PaymentProcessor $payments): RedirectResponse
    {
        $data = $request->validate([
            'amount_vnd' => [
                'required',
                'integer',
                'min:'.config('sepay.wallet_topup_min_vnd', 10000),
                'max:'.config('sepay.wallet_topup_max_vnd', 500000000),
            ],
        ], [
            'amount_vnd.required' => 'Vui lòng nhập số tiền muốn nạp.',
            'amount_vnd.integer' => 'Số tiền nạp phải là số nguyên.',
            'amount_vnd.min' => 'Số tiền nạp tối thiểu là '.number_format((int) config('sepay.wallet_topup_min_vnd', 10000), 0, ',', '.').' đ.',
            'amount_vnd.max' => 'Số tiền nạp vượt quá giới hạn cho phép.',
        ]);

        $order = $payments->createWalletTopupOrder($request->user(), (int) $data['amount_vnd']);

        return redirect()->route('billing.orders.show', $order)
            ->with('status', "Đã tạo mã QR nạp ví: {$order->code}");
    }

    public function show(Request $request, PaymentOrder $order): View
    {
        abort_unless(
            $request->user()->isAdmin() || (int) $order->user_id === (int) $request->user()->id,
            403
        );

        return view('billing.show', [
            'order' => $order->load('plan'),
            'paymentSettings' => SiteSetting::paymentAccount(),
        ]);
    }

    public function orderStatus(Request $request, PaymentOrder $order): JsonResponse
    {
        abort_unless(
            $request->user()->isAdmin() || (int) $order->user_id === (int) $request->user()->id,
            403
        );

        return response()->json([
            'status' => $order->status,
            'paid_at' => $order->paid_at?->toIso8601String(),
        ]);
    }

    private function resolveMonthlyLesson(Request $request, Plan $plan, ?string $lessonId): ?Lesson
    {
        if ($plan->code !== config('quantum.plans.monthly_code')) {
            return null;
        }

        if (! $lessonId) {
            throw ValidationException::withMessages([
                'lesson_id' => 'Vui long chon bai hoc muon mo khoa khi mua goi thang.',
            ]);
        }

        $lesson = Lesson::query()
            ->where('is_trial', false)
            ->findOrFail($lessonId);

        $alreadyUnlocked = LessonUnlock::query()
            ->where('user_id', $request->user()->id)
            ->where('lesson_id', $lesson->id)
            ->where('expires_at', '>', now())
            ->exists();

        if ($alreadyUnlocked) {
            throw ValidationException::withMessages([
                'lesson_id' => 'Bai hoc nay da duoc mo khoa truoc do.',
            ]);
        }

        return $lesson;
    }
}
