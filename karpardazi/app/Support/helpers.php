<?php

use App\Models\Setting;
use App\Support\Jalali;

if (! function_exists('fa')) {
    /** تبدیل ارقام به فارسی */
    function fa(string|int|float|null $value): string
    {
        return Jalali::faDigits($value);
    }
}

if (! function_exists('fa_money')) {
    /** مبلغ با جداکننده هزارگان و ارقام فارسی */
    function fa_money(int|string|float|null $amount): string
    {
        return Jalali::money($amount);
    }
}

if (! function_exists('jdate')) {
    /** تاریخ میلادی به شمسی (ارقام لاتین) */
    function jdate(Carbon|string|null $date, string $format = 'Y/m/d'): string
    {
        return Jalali::of($date, $format);
    }
}

if (! function_exists('jdate_fa')) {
    /** تاریخ میلادی به شمسی با ارقام فارسی */
    function jdate_fa(Carbon|string|null $date, string $format = 'Y/m/d'): string
    {
        return Jalali::faDigits(Jalali::of($date, $format));
    }
}

if (! function_exists('setting')) {
    /** خواندن تنظیمات با کش */
    function setting(string $key, ?string $default = null): ?string
    {
        return Setting::value($key, $default);
    }
}
