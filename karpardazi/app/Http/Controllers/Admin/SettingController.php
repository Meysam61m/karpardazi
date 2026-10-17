<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    private const KEYS = [
        'org_name' => 'نام سازمان',
        'complex_name' => 'عنوان مجتمع',
        'national_id' => 'شناسه ملی',
        'economic_code' => 'کد اقتصادی',
        'form_code' => 'کد فرم',
        'form_revision_date' => 'تاریخ بازنگری فرم',
        'number_pattern' => 'الگوی شماره‌گذاری سند',
        'default_branch_id' => 'شعبه پیش‌فرض',
    ];

    public function edit()
    {
        $values = [];
        foreach (self::KEYS as $key => $label) {
            $values[$key] = Setting::value($key, '');
        }

        return view('admin.settings.edit', [
            'keys' => self::KEYS,
            'values' => $values,
            'branches' => Branch::orderBy('id')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'org_name' => ['required', 'string', 'max:200'],
            'complex_name' => ['required', 'string', 'max:200'],
            'national_id' => ['nullable', 'string', 'max:30'],
            'economic_code' => ['nullable', 'string', 'max:30'],
            'form_code' => ['nullable', 'string', 'max:40'],
            'form_revision_date' => ['nullable', 'string', 'max:20'],
            'number_pattern' => ['required', 'string', 'max:40', 'regex:/\{serial(:[34])?\}/'],
            'default_branch_id' => ['nullable', 'exists:branches,id'],
        ], [
            'required' => 'وارد کردن :attribute الزامی است.',
            'regex' => 'الگوی شماره‌گذاری باید شامل {serial} یا {serial:3} باشد. مثال: {yy}-{serial:3}',
            'exists' => ':attribute معتبر نیست.',
        ], self::KEYS);

        $old = [];
        foreach (array_keys(self::KEYS) as $key) {
            $old[$key] = Setting::value($key, '');
        }

        foreach ($data as $key => $value) {
            Setting::put($key, is_array($value) ? (string) ($value[0] ?? '') : (string) $value);
        }
        Setting::flushCache();

        AuditLog::record('settings_update', null, $old, $data);

        return back()->with('success', 'تنظیمات ذخیره شد.');
    }
}
