<?php

namespace App\Http\Controllers;

use App\Models\TransactionLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserTransactionController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_if($user->is_admin, 403);
        $type = (string) $request->string('type', 'all');
        $search = trim((string) $request->string('q', ''));

        $transactions = TransactionLog::query()
            ->where('user_id', $user->id)
            ->when($type !== '' && $type !== 'all', fn ($query) => $query->where('transaction_type', $type))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('description', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhere('reference_id', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('transactions.index', [
            'transactions' => $transactions,
            'typeOptions' => TransactionLog::typeOptions(),
            'selectedType' => $type,
            'search' => $search,
            'affiliateTotalVnd' => (int) TransactionLog::query()
                ->where('user_id', $user->id)
                ->where('transaction_type', TransactionLog::TYPE_AFFILIATE)
                ->where('status', TransactionLog::STATUS_SUCCESS)
                ->sum('amount'),
            'poolShareTotalVnd' => (int) TransactionLog::query()
                ->where('user_id', $user->id)
                ->where('transaction_type', TransactionLog::TYPE_POOL_SHARE)
                ->where('status', TransactionLog::STATUS_SUCCESS)
                ->sum('amount'),
        ]);
    }
}
