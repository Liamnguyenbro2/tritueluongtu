<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePaidUserCompletedKyc
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->requiresKycForPaidAccess()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Ban can hoan thanh KYC truoc khi tiep tuc su dung dich vu tra phi.',
                ], 423);
            }

            return redirect()
                ->route('dashboard')
                ->withErrors([
                    'kyc' => 'Ban can hoan thanh KYC truoc khi tiep tuc su dung dich vu tra phi.',
                ]);
        }

        return $next($request);
    }
}
