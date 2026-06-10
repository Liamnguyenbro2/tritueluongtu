<?php

namespace App\Http\Middleware;

use App\Services\AuthSessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleDeviceSessionIsValid
{
    public function __construct(
        private readonly AuthSessionService $authSessions,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($this->authSessions->isExpired($request, $user)) {
            $this->authSessions->clear($request, $user);
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại.',
                    'session_expired' => true,
                ], 401);
            }

            return redirect()->route('login')->with('session_expired_notice', $this->authSessions->expiredPayload());
        }

        $request->attributes->set(
            'auth_session_client_payload',
            $this->authSessions->touch($request, $user)
        );

        return $next($request);
    }
}
