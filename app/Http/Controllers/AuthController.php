<?php

namespace App\Http\Controllers;

use App\Models\AccountSuspension;
use App\Models\Referral;
use App\Models\ReferralLink;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\WalletLedgerService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const LOGIN_LOCK_AFTER = 5;
    private const LOGIN_SUSPEND_AFTER = 10;
    private const LOGIN_LOCK_MINUTES = 15;
    private const LOGIN_ATTEMPT_DECAY_HOURS = 24;

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
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(6)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
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
            'password.confirmed' => 'Nhập lại mật khẩu không khớp.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'password.mixed' => 'Mật khẩu phải bao gồm chữ hoa và chữ thường.',
            'password.numbers' => 'Mật khẩu phải có ít nhất 1 chữ số.',
            'password.symbols' => 'Mật khẩu phải có ít nhất 1 ký tự đặc biệt.',
            'referral_code.exists' => 'Mã giới thiệu không tồn tại.',
            'accepted_terms.accepted' => 'Bạn cần đồng ý điều khoản sử dụng.',
        ]);

        $user = User::query()->create([
            'username' => $data['username'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => 'user',
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

        return $this->redirectAfterLogin($user);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = trim((string) $credentials['login']);
        $user = $this->resolveUserFromLogin($login);
        [$attemptKey, $lockKey] = $this->loginGuardKeys($login, $user);

        if ($user && $user->activeSuspension()->exists()) {
            return redirect()->route('login')
                ->with('suspension_notice', $this->suspensionNoticePayload($user, $user->activeSuspension()->firstOrFail()))
                ->withInput(['login' => $login]);
        }

        if ($lockPayload = $this->activeLoginLockPayload($login, $lockKey)) {
            return back()
                ->withErrors(['login' => 'Bạn đã nhập sai mật khẩu quá nhiều lần. Vui lòng chờ hết thời gian đếm ngược để thử lại.'])
                ->with('login_lock', $lockPayload)
                ->onlyInput('login');
        }

        if ($user && Auth::attempt(['id' => $user->id, 'password' => $credentials['password']], true)) {
            $this->clearLoginGuards($login, $user);

            $request->session()->regenerate();

            return $this->redirectAfterLogin($user);
        }

        $attempts = $this->incrementFailedLoginAttempts($attemptKey);

        if ($user && $attempts >= self::LOGIN_SUSPEND_AFTER) {
            $this->clearLoginGuards($login, $user);

            $suspension = AccountSuspension::query()->create([
                'user_id' => $user->id,
                'type' => 'temporary',
                'reason' => 'Tài khoản bạn bị tạm khóa do nhập sai mật khẩu quá nhiều lần',
                'starts_at' => now(),
                'ends_at' => null,
            ]);

            return redirect()->route('login')
                ->with('suspension_notice', $this->suspensionNoticePayload($user, $suspension))
                ->withInput(['login' => $login]);
        }

        if ($attempts % self::LOGIN_LOCK_AFTER === 0) {
            $lockUntil = now()->addMinutes(self::LOGIN_LOCK_MINUTES);
            Cache::put($lockKey, $lockUntil->toIso8601String(), $lockUntil);

            return back()
                ->withErrors(['login' => 'Bạn đã nhập sai mật khẩu quá 5 lần. Vui lòng đợi 15 phút trước khi đăng nhập lại.'])
                ->with('login_lock', $this->loginLockPayload($login, $lockUntil))
                ->onlyInput('login');
        }

        return back()
            ->withErrors(['login' => 'Thông tin đăng nhập không đúng.'])
            ->onlyInput('login');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }

    private function redirectAfterLogin(User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return redirect()->route('admin.index');
        }

        if ($user->isAccountant()) {
            return redirect()->route('accountant.dashboard');
        }

        return redirect()->route('dashboard');
    }

    private function resolveUserFromLogin(string $login): ?User
    {
        $normalized = Str::lower(trim($login));

        if ($normalized === '') {
            return null;
        }

        return User::query()
            ->whereRaw('LOWER(email) = ?', [$normalized])
            ->orWhereRaw('LOWER(username) = ?', [$normalized])
            ->first();
    }

    private function loginGuardKeys(string $login, ?User $user): array
    {
        $suffix = $user
            ? 'user:'.$user->id
            : 'login:'.sha1(Str::lower(trim($login)));

        return [
            'auth_login_attempts:'.$suffix,
            'auth_login_lock:'.$suffix,
        ];
    }

    private function incrementFailedLoginAttempts(string $attemptKey): int
    {
        Cache::add($attemptKey, 0, now()->addHours(self::LOGIN_ATTEMPT_DECAY_HOURS));
        $attempts = (int) Cache::increment($attemptKey);
        Cache::put($attemptKey, $attempts, now()->addHours(self::LOGIN_ATTEMPT_DECAY_HOURS));

        return $attempts;
    }

    private function activeLoginLockPayload(string $login, string $lockKey): ?array
    {
        $raw = Cache::get($lockKey);

        if (! $raw) {
            return null;
        }

        $lockUntil = Carbon::parse($raw);

        if (! $lockUntil->isFuture()) {
            Cache::forget($lockKey);

            return null;
        }

        return $this->loginLockPayload($login, $lockUntil);
    }

    private function loginLockPayload(string $login, Carbon $lockUntil): array
    {
        return [
            'login' => $login,
            'until' => $lockUntil->toIso8601String(),
            'seconds_remaining' => now()->diffInSeconds($lockUntil, false),
        ];
    }

    private function clearLoginGuards(string $login, ?User $user): void
    {
        [$attemptKey, $lockKey] = $this->loginGuardKeys($login, $user);

        Cache::forget($attemptKey);
        Cache::forget($lockKey);
    }

    private function suspensionNoticePayload(User $user, AccountSuspension $suspension): array
    {
        return [
            'user_id' => $user->id,
            'email' => $user->email,
            'type' => $suspension->type,
            'type_label' => $suspension->type === 'permanent' ? 'vĩnh viễn' : 'tạm thời',
            'reason' => $suspension->reason,
            'ends_at' => $suspension->ends_at?->format('d/m/Y H:i'),
        ];
    }
}
