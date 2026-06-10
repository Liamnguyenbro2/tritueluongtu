<?php

namespace App\Http\Middleware;

use App\Models\UserLoginSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleDeviceSessionIsValid
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isAdmin() || $user->isAccountant()) {
            return $next($request);
        }

        $sessionId = $request->session()->getId();

        if ($sessionId === '') {
            return $next($request);
        }

        $this->persistSession($request, $user->id, $sessionId);

        return $next($request);
    }

    private function persistSession(Request $request, int $userId, string $sessionId): void
    {
        UserLoginSession::query()->updateOrCreate(
            ['user_id' => $userId],
            [
                'session_id' => $sessionId,
                'device_name' => $this->resolveDeviceName($request),
                'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
                'ip_address' => $request->ip(),
                'last_seen_at' => now(),
                'expires_at' => now()->addMinutes((int) config('session.lifetime', 120)),
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
