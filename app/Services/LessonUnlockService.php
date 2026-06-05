<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\LessonUnlock;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LessonUnlockService
{
    public function __construct(
        private readonly WalletLedgerService $wallets,
    ) {
    }

    public function unlock(User $user, Lesson $lesson): LessonUnlock
    {
        if ($lesson->is_trial) {
            throw new RuntimeException('Bài học này đã mở miễn phí.');
        }

        $subscription = $user->activeMonthlySubscription();

        if (! $subscription || $subscription->grants_full_library) {
            throw new RuntimeException('Gói tháng hiện tại không hỗ trợ mở khóa bài học theo cơ chế mới.');
        }

        $existingUnlock = LessonUnlock::query()
            ->where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        if ($existingUnlock?->isActive()) {
            return $existingUnlock;
        }

        return DB::transaction(function () use ($user, $lesson, $subscription) {
            $wallet = $this->wallets->walletForUser($user);

            if ($wallet->is_locked) {
                throw new RuntimeException('Ví của bạn đang bị khóa tạm thời.');
            }

            $amount = max(0, (int) $lesson->unlock_price_vnd);

            if ($amount > 0 && $wallet->balance_vnd < $amount) {
                throw new RuntimeException('Số dư ví không đủ để mở khóa bài học này.');
            }

            $unlock = LessonUnlock::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'lesson_id' => $lesson->id,
                ],
                [
                    'subscription_id' => $subscription->id,
                    'amount_vnd' => $amount,
                    'unlocked_at' => now(),
                    'expires_at' => $subscription->ends_at,
                ]
            );

            if ($amount > 0) {
                $this->wallets->debit(
                    $wallet,
                    $amount,
                    'lesson_unlock_payment',
                    $unlock,
                    "Mở khóa bài học {$lesson->title}"
                );
            }

            return $unlock;
        });
    }
}
