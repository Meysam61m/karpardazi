<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\DefaultDataSeeder;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'karpardazi:install';

    protected $description = 'راه‌اندازی اولیه سامانه: اجرای مایگریشن‌ها، داده‌های پایه و ساخت حساب مدیر به صورت امن';

    public function handle(): int
    {
        $this->info('راه‌اندازی اولیه «سامانه کارپردازی مجتمع خراسان»');
        $this->newLine();

        $this->components->task('اجرای مایگریشن‌های پایگاه داده', function () {
            $this->callSilently('migrate', ['--force' => true]);
        });

        $this->components->task('ثبت داده‌های پایه (تنظیمات و شعب)', function () {
            (new DefaultDataSeeder)->run();
        });

        if (User::where('role', User::ROLE_ADMIN)->exists()) {
            $this->warn('حساب مدیر قبلاً ساخته شده است. برای ساخت کارپرداز وارد پنل مدیر شوید.');

            return self::SUCCESS;
        }

        $this->info('ساخت حساب مدیر (اطلاعات در هیچ فایلی ذخیره نمی‌شود و رمز هش می‌شود)');
        $name = $this->askValid('نام و نام خانوادگی مدیر', fn ($v) => mb_strlen(trim((string) $v)) >= 3, 'حداقل ۳ حرف وارد کنید.');
        $username = $this->askValid('نام کاربری مدیر (حروف لاتین، اعداد، نقطه یا زیرخط)', fn ($v) => preg_match('/^[A-Za-z0-9._-]{3,50}$/', (string) $v), 'نام کاربری معتبر نیست.');

        $password = $this->secret('رمز عبور (حداقل ۸ کاراکتر)');
        while (mb_strlen((string) $password) < 8) {
            $this->error('رمز عبور باید حداقل ۸ کاراکتر باشد.');
            $password = $this->secret('رمز عبور (حداقل ۸ کاراکتر)');
        }
        $confirm = $this->secret('تکرار رمز عبور');
        if ($password !== $confirm) {
            $this->error('تکرار رمز با رمز یکسان نیست. دوباره دستور را اجرا کنید.');

            return self::FAILURE;
        }

        $user = User::create([
            'name' => trim($name),
            'username' => trim($username),
            'password' => $password,
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        AuditLog::record('install', $user, null, ['username' => $user->username]);

        $this->newLine();
        $this->info('✔ نصب کامل شد.');
        $this->line('حالا وارد سامانه شوید و از بخش «کاربران» حساب کارپرداز را بسازید.');

        return self::SUCCESS;
    }

    private function askValid(string $question, callable $validator, string $error): string
    {
        $value = $this->ask($question);
        while (! $validator($value)) {
            $this->error($error);
            $value = $this->ask($question);
        }

        return (string) $value;
    }
}
