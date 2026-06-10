<?php

namespace App\Http\Controllers;

use App\Models\AccountSuspension;
use App\Models\Referral;
use App\Models\ReferralLink;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AuthSessionService;
use App\Services\WalletLedgerService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
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
    public function __construct(
        private readonly AuthSessionService $authSessions,
    ) {
    }

    private const LOGIN_LOCK_AFTER = 5;
    private const LOGIN_SUSPEND_AFTER = 10;
    private const LOGIN_LOCK_MINUTES = 15;
    private const LOGIN_ATTEMPT_DECAY_HOURS = 24;
    private const RESERVED_USERNAMES = [
        'admin',
        'administrator',
        'support',
        'root',
        'system',
        'mod',
        'moderator',
        'staff',
        'api',
        'login',
        'register',
        'dashboard',
    ];

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
        $reference = trim((string) $request->query('ref', $request->query('code', '')));

        if ($reference === '') {
            return response()->json(['found' => false]);
        }

        $referrer = $this->resolveReferrerByReference($reference);

        return response()->json([
            'found' => $referrer !== null,
            'name' => $referrer?->name,
        ]);
    }

    public function lookupEmail(Request $request): JsonResponse
    {
        $email = Str::lower(trim((string) $request->query('email', '')));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['exists' => false]);
        }

        return response()->json([
            'exists' => User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->exists(),
        ]);
    }

    public function lookupUsername(Request $request): JsonResponse
    {
        $username = $this->normalizeUsername((string) $request->query('username', ''));

        if ($username === '' || ! preg_match('/^[a-z0-9._]{4,30}$/', $username)) {
            return response()->json(['exists' => false]);
        }

        return response()->json([
            'exists' => $this->usernameExists($username) || in_array($username, self::RESERVED_USERNAMES, true),
        ]);
    }

    public function register(Request $request, WalletLedgerService $wallets): RedirectResponse
    {
        $referralCode = trim((string) $request->input('referral_code'));
        $normalizedUsername = $this->normalizeUsername((string) $request->input('username'));
        $normalizedEmail = Str::lower(trim((string) $request->input('email')));

        $request->merge([
            'username' => $normalizedUsername,
            'name' => trim((string) $request->input('name')),
            'email' => $normalizedEmail,
            'phone' => trim((string) $request->input('phone')),
            'referral_code' => $referralCode === '' ? null : $referralCode,
        ]);

        $data = $request->validate([
            'username' => [
                'required',
                'string',
                'min:4',
                'max:30',
                'regex:/^[a-z0-9._]+$/',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $normalized = $this->normalizeUsername((string) $value);

                    if (in_array($normalized, self::RESERVED_USERNAMES, true)) {
                        $fail('ID này đã tồn tại.');
                        return;
                    }

                    if ($this->usernameExists($normalized)) {
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
            'referral_code' => [
                'nullable',
                'string',
                'max:50',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value && ! $this->resolveReferrerByReference((string) $value)) {
                        $fail('Mã giới thiệu không tồn tại.');
                    }
                },
            ],
            'accepted_terms' => ['accepted'],
        ], [
            'username.required' => 'Vui lòng nhập ID tài khoản.',
            'username.min' => 'ID tài khoản phải có ít nhất 4 ký tự.',
            'username.regex' => 'ID tài khoản chỉ được dùng chữ, số, dấu chấm hoặc dấu gạch dưới.',
            'username.max' => 'ID tài khoản tối đa 30 ký tự.',
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

        try {
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
                'code' => $this->generateUniqueReferralCode($user->username),
            ]);
        } catch (QueryException) {
            $errors = [];

            if ($this->usernameExists($normalizedUsername) || in_array($normalizedUsername, self::RESERVED_USERNAMES, true)) {
                $errors['username'] = 'ID này đã tồn tại.';
            }

            if (User::query()->whereRaw('LOWER(email) = ?', [$normalizedEmail])->exists()) {
                $errors['email'] = 'Email này đã được sử dụng.';
            }

            return back()
                ->withErrors($errors ?: ['username' => 'Không thể tạo tài khoản với dữ liệu hiện tại.'])
                ->withInput($request->except(['password', 'password_confirmation']));
        }

        $refCode = $data['referral_code'] ?: config('quantum.default_referral_code');
        $referrer = $this->resolveReferrerByReference((string) $refCode);

        if ($referrer) {
            Referral::query()->create([
                'referrer_id' => $referrer->id,
                'referred_id' => $user->id,
            ]);
        }

        $wallets->walletForUser($user);

        Auth::login($user);
        $request->session()->regenerate();
        $this->authSessions->start($request, $user);

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

        if ($user && Auth::attempt(['id' => $user->id, 'password' => $credentials['password']])) {
            $this->clearLoginGuards($login, $user);

            $request->session()->regenerate();

            if ($this->authSessions->hasActiveSessionOnAnotherDevice($request, $user)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('device_login_conflict', $this->authSessions->conflictPayload($user))
                    ->withInput(['login' => $login]);
            }

            $this->authSessions->start($request, $user);

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
        $user = $request->user();

        $this->authSessions->clear($request, $user);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user, 401);

        return response()->json([
            'ok' => true,
            'session' => $this->authSessions->touch($request, $user),
        ]);
    }

    public function expireSession(Request $request): RedirectResponse
    {
        $user = $request->user();

        $this->authSessions->clear($request, $user);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('session_expired_notice', $this->authSessions->expiredPayload());
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

    private function normalizeUsername(string $username): string
    {
        return Str::lower(trim($username));
    }

    private function usernameExists(string $username): bool
    {
        return User::query()
            ->whereRaw('LOWER(username) = ?', [Str::lower($username)])
            ->exists();
    }

    private function resolveReferrerByReference(string $reference): ?User
    {
        $normalized = trim($reference);

        if ($normalized === '') {
            return null;
        }

        $byUsername = User::query()
            ->whereRaw('LOWER(username) = ?', [Str::lower($normalized)])
            ->first();

        if ($byUsername) {
            return $byUsername;
        }

        $link = ReferralLink::query()
            ->with('user')
            ->where('code', Str::upper($normalized))
            ->first();

        return $link?->user;
    }

    private function generateUniqueReferralCode(string $username): string
    {
        $base = strtoupper(preg_replace('/[^A-Z0-9]/', '', $username)) ?: 'USER';
        $base = substr($base, 0, 16);
        $candidate = $base;
        $suffix = 1;

        while (ReferralLink::query()->where('code', $candidate)->exists()) {
            $candidate = substr($base, 0, max(1, 16 - strlen((string) $suffix))).$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
