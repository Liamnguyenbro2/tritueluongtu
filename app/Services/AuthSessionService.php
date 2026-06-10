<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserLoginSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthSessionService
{
    private const SESSION_STARTED_AT = 'auth_session_started_at';
    private const SESSION_LAST_ACTIVITY_AT = 'auth_session_last_activity_at';

    public function start(Request $request, User $user): void
    {
        $startedAt = now();
        $request->session()->put(self::SESSION_STARTED_AT, $startedAt->toIso8601String());
        $request->session()->put(self::SESSION_LAST_ACTIVITY_AT, $startedAt->toIso8601String());

        $this->persistLoginSession($request, $user, $startedAt, $startedAt);
    }

    public function touch(Request $request, User $user): array
    {
        [$startedAt, $lastActivityAt] = $this->resolveTimestamps($request, $user);

        $now = now();
        $request->session()->put(self::SESSION_STARTED_AT, $startedAt->toIso8601String());
        $request->session()->put(self::SESSION_LAST_ACTIVITY_AT, $now->toIso8601String());

        $this->persistLoginSession($request, $user, $startedAt, $now);

        return $this->clientPayload($request, $user, $startedAt, $now);
    }

    public function clientPayload(Request $request, User $user, ?Carbon $startedAt = null, ?Carbon $lastActivityAt = null): array
    {
        [$startedAt, $lastActivityAt] = $startedAt && $lastActivityAt
            ? [$startedAt, $lastActivityAt]
            : $this->resolveTimestamps($request, $user);

        $timeouts = $this->timeoutsFor($user);
        $idleExpiresAt = $lastActivityAt->copy()->addMinutes($timeouts['idle_minutes']);
        $absoluteExpiresAt = $startedAt->copy()->addMinutes($timeouts['absolute_minutes']);
        $expiresAt = $idleExpiresAt->lessThan($absoluteExpiresAt) ? $idleExpiresAt : $absoluteExpiresAt;

        return [
            'warning_seconds' => (int) config('quantum.auth_sessions.warning_seconds', 300),
            'idle_expires_at' => $idleExpiresAt->toIso8601String(),
            'absolute_expires_at' => $absoluteExpiresAt->toIso8601String(),
            'expires_at' => $expiresAt->toIso8601String(),
            'idle_minutes' => $timeouts['idle_minutes'],
            'absolute_minutes' => $timeouts['absolute_minutes'],
            'idle_label' => $timeouts['idle_label'],
            'absolute_label' => $timeouts['absolute_label'],
        ];
    }

    public function isExpired(Request $request, User $user): bool
    {
        [$startedAt, $lastActivityAt] = $this->resolveTimestamps($request, $user);
        $timeouts = $this->timeoutsFor($user);
        $now = now();

        return $lastActivityAt->copy()->addMinutes($timeouts['idle_minutes'])->lte($now)
            || $startedAt->copy()->addMinutes($timeouts['absolute_minutes'])->lte($now);
    }

    public function hasActiveSessionOnAnotherDevice(Request $request, User $user): bool
    {
        if ($user->isAdmin() || $user->isAccountant()) {
            return false;
        }

        UserLoginSession::query()
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->whereNotNull('idle_expires_at')->where('idle_expires_at', '<=', now())
                    ->orWhere(function ($absoluteQuery) {
                        $absoluteQuery->whereNotNull('absolute_expires_at')
                            ->where('absolute_expires_at', '<=', now());
                    })
                    ->orWhere(function ($legacyQuery) {
                        $legacyQuery->whereNull('idle_expires_at')
                            ->whereNull('absolute_expires_at')
                            ->whereNotNull('expires_at')
                            ->where('expires_at', '<=', now());
                    });
            })
            ->delete();

        $activeSession = UserLoginSession::query()->where('user_id', $user->id)->first();

        if (! $activeSession) {
            return false;
        }

        return $activeSession->session_id !== $request->session()->getId();
    }

    public function conflictPayload(User $user): array
    {
        $session = UserLoginSession::query()->where('user_id', $user->id)->first();

        return [
            'message' => 'Bạn đang sử dụng tài khoản này trên một thiết bị khác, vui lòng đăng xuất khỏi thiết bị đó.',
            'device_name' => $session?->device_name,
            'last_seen_at' => $session?->last_seen_at?->format('d/m/Y H:i'),
        ];
    }

    public function expiredPayload(): array
    {
        return [
            'message' => 'Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại.',
        ];
    }

    public function clear(Request $request, ?User $user): void
    {
        $request->session()->forget([
            self::SESSION_STARTED_AT,
            self::SESSION_LAST_ACTIVITY_AT,
        ]);

        if ($user) {
            UserLoginSession::query()->where('user_id', $user->id)->delete();
        }
    }

    public function timeoutsFor(User $user): array
    {
        $config = $user->isAdmin() || $user->isAccountant()
            ? config('quantum.auth_sessions.privileged')
            : config('quantum.auth_sessions.user');

        return [
            'idle_minutes' => (int) ($config['idle_minutes'] ?? 60),
            'absolute_minutes' => (int) ($config['absolute_minutes'] ?? 1440),
            'idle_label' => (string) ($config['idle_label'] ?? 'Không hoạt động'),
            'absolute_label' => (string) ($config['absolute_label'] ?? 'Phiên đăng nhập'),
        ];
    }

    private function resolveTimestamps(Request $request, User $user): array
    {
        $startedAt = $request->session()->get(self::SESSION_STARTED_AT);
        $lastActivityAt = $request->session()->get(self::SESSION_LAST_ACTIVITY_AT);

        if ($startedAt && $lastActivityAt) {
            return [Carbon::parse($startedAt), Carbon::parse($lastActivityAt)];
        }

        $storedSession = $user->loginSession;

        if ($storedSession?->login_at && $storedSession?->last_seen_at) {
            return [$storedSession->login_at->copy(), $storedSession->last_seen_at->copy()];
        }

        $now = now();

        return [$now->copy(), $now];
    }

    private function persistLoginSession(Request $request, User $user, Carbon $startedAt, Carbon $lastActivityAt): void
    {
        if ($user->isAdmin() || $user->isAccountant()) {
            return;
        }

        $timeouts = $this->timeoutsFor($user);

        UserLoginSession::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'session_id' => $request->session()->getId(),
                'device_name' => $this->resolveDeviceName($request),
                'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
                'ip_address' => $request->ip(),
                'login_at' => $startedAt,
                'last_seen_at' => $lastActivityAt,
                'idle_expires_at' => $lastActivityAt->copy()->addMinutes($timeouts['idle_minutes']),
                'absolute_expires_at' => $startedAt->copy()->addMinutes($timeouts['absolute_minutes']),
                'expires_at' => $lastActivityAt->copy()->addMinutes($timeouts['idle_minutes']),
            ]
        );
    }

    private function resolveDeviceName(Request $request): ?string
    {
        $userAgent = (string) $request->userAgent();

        if ($userAgent === '') {
            return null;
        }

        $platform = str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')
            ? 'iPhone/iPad'
            : (str_contains($userAgent, 'Android')
                ? 'Android'
                : (str_contains($userAgent, 'Windows')
                    ? 'Windows'
                    : (str_contains($userAgent, 'Macintosh') ? 'Mac' : 'Thiết bị khác')));

        $browser = str_contains($userAgent, 'Edg/')
            ? 'Edge'
            : (str_contains($userAgent, 'Chrome/')
                ? 'Chrome'
                : (str_contains($userAgent, 'Safari/') && ! str_contains($userAgent, 'Chrome/')
                    ? 'Safari'
                    : (str_contains($userAgent, 'Firefox/')
                        ? 'Firefox'
                        : 'Trình duyệt')));

        return $platform.' - '.$browser;
    }
}
