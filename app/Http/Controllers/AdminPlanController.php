<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminPlanController extends Controller
{
    public function index(): View
    {
        return $this->plansView();
    }

    public function show(Plan $plan): View
    {
        return $this->plansView($plan);
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $request->merge([
            'price_vnd' => preg_replace('/\D+/', '', (string) $request->input('price_vnd')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:5000'],
            'price_vnd' => ['required', 'integer', 'min:0'],
            'features_text' => ['nullable', 'string', 'max:3000'],
            'bank_qr_enabled' => ['nullable', 'boolean'],
            'bank_qr_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'wallet_enabled' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Vui lòng nhập tên gói.',
            'duration_days.required' => 'Vui lòng nhập số ngày sử dụng.',
            'duration_days.min' => 'Số ngày sử dụng phải lớn hơn 0.',
            'price_vnd.required' => 'Vui lòng nhập giá gói.',
            'bank_qr_image.image' => 'Ảnh mã QR phải là file hình ảnh hợp lệ.',
            'bank_qr_image.mimes' => 'Ảnh mã QR chỉ hỗ trợ JPG, PNG hoặc WEBP.',
            'bank_qr_image.max' => 'Ảnh mã QR không được lớn hơn 5MB.',
        ]);

        $features = collect(preg_split('/\r\n|\r|\n/', (string) ($data['features_text'] ?? '')))
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values()
            ->all();

        $bankQrImagePath = $plan->bank_qr_image_path;

        if ($request->hasFile('bank_qr_image')) {
            $this->deleteFile($plan->bank_qr_image_path);
            $bankQrImagePath = $request->file('bank_qr_image')->store('plan-qr', 'public');
        }

        $plan->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'duration_days' => (int) $data['duration_days'],
            'price_vnd' => (int) $data['price_vnd'],
            'features' => $features,
            'bank_qr_enabled' => $request->boolean('bank_qr_enabled'),
            'bank_qr_image_path' => $bankQrImagePath,
            'wallet_enabled' => $request->boolean('wallet_enabled'),
        ]);

        return redirect()
            ->route('admin.plans.show', $plan)
            ->with('status', "Đã cập nhật gói {$plan->name}.");
    }

    private function plansView(?Plan $selectedPlan = null): View
    {
        $plans = Plan::query()->orderBy('price_vnd')->get();

        if ($selectedPlan) {
            $plans = $plans
                ->sortBy(fn (Plan $plan) => $plan->id === $selectedPlan->id ? 0 : 1)
                ->values();
        }

        return view('admin.plans.index', [
            'plans' => $plans,
            'selectedPlan' => $selectedPlan,
        ]);
    }

    private function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
