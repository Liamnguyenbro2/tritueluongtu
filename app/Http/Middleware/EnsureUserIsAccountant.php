<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAccountant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user && ($user->isAccountant() || $user->isAdmin()), 403);

        return $next($request);
    }
}
