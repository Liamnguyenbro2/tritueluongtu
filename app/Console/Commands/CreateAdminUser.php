<?php

namespace App\Console\Commands;

use App\Models\ReferralLink;
use App\Models\User;
use App\Services\WalletLedgerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create
        {email : Email admin}
        {username : ID tài khoản admin}
        {name : Họ và tên admin}
        {--phone= : Số điện thoại 10 số, để trống sẽ tự sinh}
        {--password= : Mật khẩu, để trống sẽ tự sinh}
        {--force : Cập nhật user hiện có theo email thành admin}';

    protected $description = 'Create or update an admin account from the terminal.';

    /**
     * @var array<int, string>
     */
    private const RESERVED_USERNAMES = [
        'admin',
        'administrator',
        'support',
        'root',
        'system',
        'mod',
        'moderator',
        'staff',
        'api',
        'login',
        'register',
        'dashboard',
    ];

    public function handle(WalletLedgerService $wallets): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        $username = $this->normalizeUsername((string) $this->argument('username'));
        $name = trim((string) $this->argument('name'));
        $phone = $this->normalizePhone($this->option('phone'));
        $password = (string) ($this->option('password') ?: $this->generatePassword());

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Email không đúng định dạng.');

            return self::INVALID;
        }

        if (! preg_match('/^[a-z0-9._]{4,30}$/', $username)) {
            $this->error('Username chỉ được gồm chữ thường, số, dấu chấm hoặc dấu gạch dưới, dài 4-30 ký tự.');

            return self::INVALID;
        }

        if (in_array($username, self::RESERVED_USERNAMES, true)) {
            $this->error('Username này là từ khóa hệ thống, vui lòng chọn username khác.');

            return self::INVALID;
        }

        if ($name === '') {
            $this->error('Tên hiển thị không được để trống.');

            return self::INVALID;
        }

        if ($phone !== null && ! preg_match('/^\d{10}$/', $phone)) {
            $this->error('Số điện thoại phải đúng 10 chữ số.');

            return self::INVALID;
        }

        $existingByEmail = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($existingByEmail && ! $this->option('force')) {
            $this->error('Email này đã tồn tại. Dùng --force nếu bạn muốn cập nhật user hiện có thành admin.');

            return self::INVALID;
        }

        if (User::query()->where('username', $username)->when($existingByEmail, fn ($query) => $query->whereKeyNot($existingByEmail->id))->exists()) {
            $this->error('Username này đã được sử dụng.');

            return self::INVALID;
        }

        $phone ??= $this->generateUniquePhone($existingByEmail?->id);

        if (User::query()->where('phone', $phone)->when($existingByEmail, fn ($query) => $query->whereKeyNot($existingByEmail->id))->exists()) {
            $this->error('Số điện thoại này đã được sử dụng.');

            return self::INVALID;
        }

        /** @var User $user */
        $user = DB::transaction(function () use ($existingByEmail, $email, $username, $name, $phone, $password, $wallets): User {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'username' => $username,
                    'name' => $name,
                    'phone' => $phone,
                    'password' => Hash::make($password),
                    'role' => 'admin',
                    'is_admin' => true,
                    'trial_started_at' => $existingByEmail?->trial_started_at ?? now(),
                ]
            );

            $user->profile()->updateOrCreate([], [
                'accepted_terms' => true,
                'accepted_terms_at' => now(),
            ]);

            ReferralLink::query()->updateOrCreate(
                ['user_id' => $user->id],
                ['code' => $this->buildReferralCode($user)]
            );

            $wallets->walletForUser($user);

            return $user;
        });

        $this->info('Tạo tài khoản admin thành công.');
        $this->line('Email: '.$user->email);
        $this->line('Username: '.$user->username);
        $this->line('Name: '.$user->name);
        $this->line('Phone: '.$user->phone);
        $this->line('Password: '.$password);
        $this->line('Login URL: '.url('/login'));
        $this->newLine();
        $this->comment('Ví dụ chạy trên hosting:');
        $this->line(sprintf(
            'php artisan admin:create %s %s "%s"%s%s',
            $user->email,
            $user->username,
            $user->name,
            $this->option('phone') ? ' --phone='.$user->phone : '',
            $this->option('password') ? ' --password=YOUR_PASSWORD' : ''
        ));

        return self::SUCCESS;
    }

    private function normalizeUsername(string $username): string
    {
        return Str::lower(trim($username));
    }

    private function normalizePhone(mixed $phone): ?string
    {
        $phone = trim((string) $phone);

        return $phone === '' ? null : preg_replace('/\D+/', '', $phone);
    }

    private function generateUniquePhone(?int $ignoreUserId = null): string
    {
        do {
            $phone = '09'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (
            User::query()
                ->where('phone', $phone)
                ->when($ignoreUserId, fn ($query) => $query->whereKeyNot($ignoreUserId))
                ->exists()
        );

        return $phone;
    }

    private function generatePassword(): string
    {
        return 'Adm@'.Str::upper(Str::random(3)).random_int(100000, 999999);
    }

    private function buildReferralCode(User $user): string
    {
        $base = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($user->username ?: 'ADMIN')), 0, 12));

        return $base.$user->id;
    }
}
