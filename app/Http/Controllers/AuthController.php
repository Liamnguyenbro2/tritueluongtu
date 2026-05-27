<?php

namespace App\Http\Controllers;

use App\Models\Referral;
use App\Models\ReferralLink;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\WalletLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function lookupReferral(Request $request): JsonResponse
    {
        $code = Str::upper(trim((string) $request->query('code', '')));

        if ($code === '') {
            return response()->json(['found' => false]);
        }

        $link = ReferralLink::query()
            ->with('user:id,name')
            ->where('code', $code)
            ->first();

        return response()->json([
            'found' => $link !== null,
            'name' => $link?->user?->name,
        ]);
    }

    public function register(Request $request, WalletLedgerService $wallets): RedirectResponse
    {
        $referralCode = Str::upper(trim((string) $request->input('referral_code')));

        $request->merge([
            'username' => trim((string) $request->input('username')),
            'name' => trim((string) $request->input('name')),
            'email' => trim((string) $request->input('email')),
            'phone' => trim((string) $request->input('phone')),
            'referral_code' => $referralCode === '' ? null : $referralCode,
        ]);

        $data = $request->validate([
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9]+$/',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (User::query()->whereRaw('LOWER(username) = ?', [Str::lower((string) $value)])->exists()) {
                        $fail('ID này đã tồn tại.');
                    }
                },
            ],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', 'regex:/^\d{10}$/', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'referral_code' => ['nullable', 'string', 'max:50', 'exists:referral_links,code'],
            'accepted_terms' => ['accepted'],
        ], [
            'username.required' => 'Vui lòng nhập ID tài khoản.',
            'username.regex' => 'ID tài khoản chỉ được dùng chữ hoặc số.',
            'username.max' => 'ID tài khoản tối đa 50 ký tự.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email phải đúng định dạng có @.',
            'email.unique' => 'Email này đã được sử dụng.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại không đúng vui lòng kiểm tra lại.',
            'phone.unique' => 'Số điện thoại này đã được sử dụng.',
            'referral_code.exists' => 'Mã giới thiệu không tồn tại.',
            'accepted_terms.accepted' => 'Bạn cần đồng ý điều khoản sử dụng.',
        ]);

        $user = User::query()->create([
            'username' => $data['username'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'trial_started_at' => now(),
        ]);

        UserProfile::query()->create([
            'user_id' => $user->id,
            'accepted_terms' => true,
            'accepted_terms_at' => now(),
        ]);

        ReferralLink::query()->create([
            'user_id' => $user->id,
            'code' => Str::upper($user->username).Str::upper(Str::random(4)),
        ]);

        $refCode = $data['referral_code'] ?: config('quantum.default_referral_code');
        $referrerLink = ReferralLink::query()->where('code', $refCode)->first();

        if ($referrerLink) {
            Referral::query()->create([
                'referrer_id' => $referrerLink->user_id,
                'referred_id' => $user->id,
            ]);
        }

        $wallets->walletForUser($user);

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (Auth::attempt([$field => $credentials['login'], 'password' => $credentials['password']], true)) {
            $request->session()->regenerate();

            $user = $request->user();
            $activeSuspension = $user?->activeSuspension()->first();

            if ($user && $activeSuspension) {
                Auth::logout();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('suspension_notice', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'type' => $activeSuspension->type,
                    'type_label' => $activeSuspension->type === 'permanent' ? 'vĩnh viễn' : 'tạm thời',
                    'reason' => $activeSuspension->reason,
                    'ends_at' => $activeSuspension->ends_at?->format('d/m/Y H:i'),
                ]);
            }

            return redirect()->route('dashboard');
        }

        return back()->withErrors(['login' => 'Thông tin đăng nhập không đúng.'])->onlyInput('login');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }
}
