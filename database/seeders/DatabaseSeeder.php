<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Plan;
use App\Models\ReferralLink;
use App\Models\SiteSetting;
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
                'role' => 'admin',
                'trial_started_at' => now(),
            ],
        );

        $user = User::query()->firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'username' => 'nguyenvana',
                'name' => 'Nguyen Van A',
                'phone' => '0911111111',
                'password' => Hash::make('password'),
                'role' => 'user',
                'trial_started_at' => now(),
            ],
        );

        $accountant = User::query()->firstOrCreate(
            ['email' => 'accountant@example.com'],
            [
                'username' => 'accountant',
                'name' => 'Kế toán',
                'phone' => '0933333333',
                'password' => Hash::make('password'),
                'role' => 'accountant',
            ],
        );

        $admin->update(['role' => 'admin', 'is_admin' => true]);
        $user->update(['role' => 'user']);
        $accountant->update(['role' => 'accountant', 'is_admin' => false]);

        foreach ([$admin, $user, $accountant] as $account) {
            $account->profile()->updateOrCreate([], [
                'accepted_terms' => true,
                'accepted_terms_at' => now(),
            ]);

            ReferralLink::query()->updateOrCreate(
                ['user_id' => $account->id],
                ['code' => strtoupper($account->username)]
            );
        }

        Plan::query()->updateOrCreate(['code' => 'monthly'], [
            'name' => 'Goi Thang',
            'description' => 'Phu hop trai nghiem noi dung tra phi theo thang.',
            'duration_days' => 30,
            'price_vnd' => 199000,
            'features' => [
                'Mo quyen kich hoat cac khoa tra phi',
                'Active tung khoa trong 7 ngay khi can hoc',
                'Ghi nhan day du trong lich su hoa don',
            ],
        ]);

        Plan::query()->updateOrCreate(['code' => 'yearly'], [
            'name' => 'Goi Nam',
            'description' => 'Toi uu chi phi khi su dung lau dai.',
            'duration_days' => 365,
            'price_vnd' => 1500000,
            'features' => [
                'Mo quyen kich hoat cac khoa tra phi',
                'Active tung khoa trong 7 ngay khi can hoc',
                'Ghi nhan day du trong lich su hoa don',
            ],
        ]);

        $course = Course::query()->firstOrCreate([
            'title' => 'Thu vien nang luong tich cuc',
        ], [
            'description' => '16 noi dung thien dinh va phat trien ban than.',
        ]);

        $titles = [
            'Nang Luong tong the',
            'Tinh Yeu va Hanh Phuc',
            'Tai Loc Thinh Vuong',
            'Binh An Noi Tam',
            'Suc Khoe Doi Dao',
            'Thien Dinh Sau',
            'Tri Tue va Minh Man',
            'Moi Quan He Tot Dep',
            'Bao Ve va Hoa Giai',
            'Ket Noi Vu Tru',
            'Khai Mo Tam Linh',
            'Thu Gian va Giam Stress',
            'Giac Ngu Sau',
            'Can Bang Luan Xa',
            'Thanh Cong va May Man',
            'Nang Luong Vu Tru',
        ];

        foreach ($titles as $index => $title) {
            Lesson::query()->updateOrCreate([
                'course_id' => $course->id,
                'position' => $index + 1,
            ], [
                'title' => $title,
                'description' => 'Noi dung mau cho MVP, co the thay bang audio/video that sau.',
                'is_trial' => $index < 3,
                'duration_minutes' => 12 + $index,
            ]);
        }

        app(WalletLedgerService::class)->ensureSystemWallets();
        app(WalletLedgerService::class)->walletForUser($admin);
        app(WalletLedgerService::class)->walletForUser($user);
        app(WalletLedgerService::class)->walletForUser($accountant);

        SiteSetting::query()->updateOrCreate(['key' => 'brand_eyebrow'], ['value' => 'Noi dung demo']);
        SiteSetting::query()->updateOrCreate(['key' => 'brand_name'], ['value' => 'Demo']);
    }
}
