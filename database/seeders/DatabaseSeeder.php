<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Plan;
use App\Models\ReferralLink;
use App\Models\User;
use App\Services\WalletLedgerService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'username' => 'admin',
                'name' => 'Admin',
                'phone' => '0900000000',
                'password' => Hash::make('password'),
                'is_admin' => true,
                'trial_started_at' => now(),
            ],
        );

        $user = User::query()->firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'username' => 'nguyenvana',
                'name' => 'Nguyễn Văn A',
                'phone' => '0911111111',
                'password' => Hash::make('password'),
                'trial_started_at' => now(),
            ],
        );

        foreach ([$admin, $user] as $account) {
            $account->profile()->updateOrCreate([], [
                'accepted_terms' => true,
                'accepted_terms_at' => now(),
            ]);

            ReferralLink::query()->firstOrCreate(
                ['user_id' => $account->id],
                ['code' => strtoupper($account->username)]
            );
        }

        Plan::query()->updateOrCreate(['code' => 'monthly'], [
            'name' => 'Gói Tháng',
            'duration_days' => 30,
            'price_vnd' => 199000,
        ]);

        Plan::query()->updateOrCreate(['code' => 'yearly'], [
            'name' => 'Gói Năm',
            'duration_days' => 365,
            'price_vnd' => 1500000,
        ]);

        $course = Course::query()->firstOrCreate([
            'title' => 'Thư viện năng lượng tích cực',
        ], [
            'description' => '16 nội dung thiền định và phát triển bản thân.',
        ]);

        $titles = [
            'Năng Lượng tổng thể',
            'Tình Yêu & Hạnh Phúc',
            'Tài Lộc Thịnh Vượng',
            'Bình An Nội Tâm',
            'Sức Khỏe Dồi Dào',
            'Thiền Định Sâu',
            'Trí Tuệ & Minh Mẫn',
            'Mối Quan Hệ Tốt Đẹp',
            'Bảo Vệ & Hòa Giải',
            'Kết Nối Vũ Trụ',
            'Khai Mở Tâm Linh',
            'Thư Giãn & Giảm Stress',
            'Giấc Ngủ Sâu',
            'Cân Bằng Luân Xa',
            'Thành Công & May Mắn',
            'Năng Lượng Vũ Trụ',
        ];

        foreach ($titles as $index => $title) {
            Lesson::query()->updateOrCreate([
                'course_id' => $course->id,
                'position' => $index + 1,
            ], [
                'title' => $title,
                'description' => 'Nội dung mẫu cho MVP, có thể thay bằng audio/video thật sau.',
                'is_trial' => $index < 3,
                'duration_minutes' => 12 + $index,
            ]);
        }

        app(WalletLedgerService::class)->ensureSystemWallets();
        app(WalletLedgerService::class)->walletForUser($user);
    }
}
