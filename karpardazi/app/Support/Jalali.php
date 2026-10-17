<?php

namespace App\Support;

use Carbon\Carbon;
use Hekmatinasser\Verta\Verta;

/**
 * تبدیل یکپارچه تاریخ شمسی/میلادی برای کل برنامه.
 * تاریخ‌ها در دیتابیس به صورت میلادی (Y-m-d) ذخیره می‌شوند و فقط
 * در ورودی فرم و خروجی نمایش/چاپ از این کلاس تبدیل می‌شوند.
 */
class Jalali
{
    /** ارقام لاتین به فارسی */
    public static function faDigits(string|int|float|null $value): string
    {
        if ($value === null) {
            return '';
        }

        return str_replace(
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'],
            (string) $value
        );
    }

    /** ارقام فارسی/عربی به لاتین */
    public static function latinDigits(string $value): string
    {
        return str_replace(
            ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            $value
        );
    }

    /** نرمال‌سازی ورودی کاربر به قالب Y/m/d با ارقام لاتین */
    public static function normalize(?string $jalali): ?string
    {
        if (! $jalali) {
            return null;
        }
        $v = trim(self::latinDigits($jalali));
        $v = str_replace(['-', '.'], '/', $v);

        return $v !== '' ? $v : null;
    }

    /**
     * تبدیل تاریخ شمسی (YYYY/MM/DD) به میلادی (Y-m-d) برای ذخیره در دیتابیس.
     * در صورت نامعتبر بودن null برمی‌گرداند.
     */
    public static function toGregorian(?string $jalali): ?string
    {
        $v = self::normalize($jalali);
        if (! $v) {
            return null;
        }

        try {
            $dt = Verta::parseFormat('Y/m/d', $v);

            // اعتبارسنجی رفت‌وبرگشتی: ورودی مثل 1405/13/40 نباید پذیرفته شود
            $parts = array_map('intval', explode('/', $v));
            if (count($parts) !== 3) {
                return null;
            }
            if ($dt->format('Y/n/j') !== implode('/', $parts)) {
                return null;
            }

            return $dt->datetime()->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    public static function isValid(?string $jalali): bool
    {
        return self::toGregorian($jalali) !== null;
    }

    /** تبدیل تاریخ میلادی (ذخیره‌شده) به رشته شمسی برای نمایش */
    public static function of(Carbon|string|null $date, string $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '';
        }
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        return Verta::instance($carbon)->format($format);
    }

    /** سال شمسی یک تاریخ میلادی (مثل 1405) — مبنای شماره‌گذاری اسناد */
    public static function yearOf(Carbon|string $date): int
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        return (int) Verta::instance($carbon)->format('Y');
    }

    /** قالب‌بندی مبلغ ریالی با جداکننده هزارگان و ارقام فارسی */
    public static function money(int|string|float|null $amount): string
    {
        return self::faDigits(number_format((int) ($amount ?? 0)));
    }
}
