<?php

namespace App\Http\Controllers;

use App\Models\SepayWebhookLog;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

class AdminSepayWebhookController extends Controller
{
    public function index(): View
    {
        $tableReady = Schema::hasTable('sepay_webhook_logs');

        return view('admin.sepay-webhooks.index', [
            'tableReady' => $tableReady,
            'logs' => $tableReady
                ? SepayWebhookLog::query()->latest()->paginate(15)
                : new LengthAwarePaginator([], 0, 15),
        ]);
    }
}
