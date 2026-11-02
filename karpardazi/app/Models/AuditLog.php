<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function eventLabel(string $event): string
    {
        return [
            'login' => 'ورود به سامانه',
            'login_failed' => 'تلاش ورود ناموفق',
            'logout' => 'خروج از سامانه',
            'document_create' => 'ثبت سند',
            'document_update' => 'ویرایش سند',
            'document_finalize' => 'قطعی شدن سند و صدور شماره',
            'document_delete' => 'حذف پیش‌نویس سند',
            'user_create' => 'ایجاد کاربر',
            'user_update' => 'ویرایش کاربر',
            'user_password_reset' => 'تغییر رمز عبور کاربر',
            'user_activate' => 'فعال‌سازی حساب',
            'user_deactivate' => 'غیرفعال‌سازی حساب',
            'tankhah_update' => 'ثبت یا ویرایش اطلاعات واریز تنخواه',
            'branch_create' => 'ایجاد شعبه',
            'branch_update' => 'ویرایش شعبه',
            'branch_delete' => 'حذف شعبه',
            'branch_set_default' => 'تنظیم شعبه پیش‌فرض',
            'settings_update' => 'تغییر تنظیمات',
            'report_export' => 'خروجی Excel گزارش',
            'backup_download' => 'دانلود نسخه پشتیبان',
            'backup_restore' => 'بازیابی نسخه پشتیبان',
        ][$event] ?? $event;
    }

    public static function record(string $event, ?Model $auditable = null, ?array $old = null, ?array $new = null): self
    {
        return static::create([
            'user_id' => Auth::id(),
            'event' => $event,
            'auditable_type' => $auditable ? $auditable->getMorphClass() : null,
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'ip' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}
