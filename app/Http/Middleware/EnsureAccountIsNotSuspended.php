<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsNotSuspended
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $activeSuspension = $user?->activeSuspension()->first();

        if ($user && $activeSuspension) {
            auth()->logout();

            return redirect()->route('login')->with('suspension_notice', [
                'user_id' => $user->id,
                'email' => $user->email,
                'type' => $activeSuspension->type,
                'type_label' => $activeSuspension->type === 'permanent' ? 'vĩnh viễn' : 'tạm thời',
                'reason' => $activeSuspension->reason,
                'ends_at' => $activeSuspension->ends_at?->format('d/m/Y H:i'),
            ]);
        }

        return $next($request);
    }
}
