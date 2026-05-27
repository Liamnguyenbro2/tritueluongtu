<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WalletLedgerService
{
    public function ensureSystemWallets(): void
    {
        foreach (['admin', 'tax', 'shared_pool'] as $type) {
            Wallet::query()->firstOrCreate([
                'owner_type' => null,
                'owner_id' => null,
                'type' => $type,
            ], ['balance_vnd' => 0]);
        }
    }

    public function systemWallet(string $type): Wallet
    {
        return Wallet::query()->firstOrCreate([
            'owner_type' => null,
            'owner_id' => null,
            'type' => $type,
        ], ['balance_vnd' => 0]);
    }

    public function walletForUser(User $user): Wallet
    {
        return Wallet::query()->firstOrCreate([
            'owner_type' => $user::class,
            'owner_id' => $user->id,
            'type' => 'user',
        ], ['balance_vnd' => 0]);
    }

    public function credit(Wallet $wallet, int $amountVnd, string $type, ?Model $reference = null, ?string $memo = null): LedgerEntry
    {
        return $this->entry($wallet, abs($amountVnd), 'credit', $type, $reference, $memo);
    }

    public function debit(Wallet $wallet, int $amountVnd, string $type, ?Model $reference = null, ?string $memo = null): LedgerEntry
    {
        return $this->entry($wallet, -abs($amountVnd), 'debit', $type, $reference, $memo);
    }

    private function entry(Wallet $wallet, int $signedAmount, string $direction, string $type, ?Model $reference, ?string $memo): LedgerEntry
    {
        return DB::transaction(function () use ($wallet, $signedAmount, $direction, $type, $reference, $memo) {
            $lockedWallet = Wallet::query()->lockForUpdate()->findOrFail($wallet->id);
            $newBalance = $lockedWallet->balance_vnd + $signedAmount;

            if ($newBalance < 0) {
                throw new RuntimeException('Insufficient wallet balance.');
            }

            $lockedWallet->update(['balance_vnd' => $newBalance]);

            return LedgerEntry::query()->create([
                'wallet_id' => $lockedWallet->id,
                'amount_vnd' => $signedAmount,
                'direction' => $direction,
                'type' => $type,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'memo' => $memo,
            ]);
        });
    }
}
