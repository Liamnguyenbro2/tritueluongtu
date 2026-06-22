<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Support\SupportedBanks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'bankAccount' => $request->user()->bankAccount,
            'bankOptions' => SupportedBanks::options(),
            'selectedBankName' => old('bank_name', SupportedBanks::normalize($request->user()->bankAccount?->bank_name) ?? $request->user()->bankAccount?->bank_name),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
        ]);

        $user->update($data);

        return back()->with('status', 'Đã cập nhật thông tin profile.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($data['password']),
        ]);

        return back()->with('status', 'Đã đổi mật khẩu.');
    }

    public function updateBank(Request $request): RedirectResponse
    {
        $user = $request->user();
        $existing = BankAccount::query()->where('user_id', $user->id)->first();

        $data = $request->validate([
            'bank_name' => SupportedBanks::validationRules(),
            'account_number' => ['required', 'string', 'max:50'],
            'account_holder' => ['required', 'string', 'max:100'],
        ], [
            'bank_name.required' => 'Vui lòng chọn ngân hàng.',
            'bank_name.in' => 'Tên ngân hàng không hợp lệ.',
        ]);

        $data['bank_name'] = SupportedBanks::normalize($data['bank_name']) ?? $data['bank_name'];

        if ($existing && ! $existing->can_edit) {
            abort(403, 'Thông tin ngân hàng chỉ được chỉnh sửa khi admin mở khóa.');
        }

        BankAccount::query()->updateOrCreate(
            ['user_id' => $user->id],
            $data + ['can_edit' => false]
        );

        return back()->with('status', 'Đã cập nhật thông tin ngân hàng.');
    }
}
