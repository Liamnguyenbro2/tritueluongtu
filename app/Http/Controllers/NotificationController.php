<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function readAll(Request $request): RedirectResponse
    {
        $request->session()->put($this->sessionKey($request), now()->toIso8601String());

        return back();
    }

    private function sessionKey(Request $request): string
    {
        return 'notifications_read_at_'.$request->user()->id;
    }
}
