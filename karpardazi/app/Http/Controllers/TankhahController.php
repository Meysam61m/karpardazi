<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Jalali;
use Illuminate\Http\Request;

class TankhahController extends Controller
{
    public function edit(Request $request)
    {
        abort_unless($request->user()->isKarpardaz(), 403);

        $user = $request->user();

        return view('tankhah.edit', [
            'user' => $user,
            'spent' => $user->tankhahSpent(),
            'remaining' => $user->tankhahRemaining(),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless($request->user()->isKarpardaz(), 403);

        $amount = $request->input('tankhah_amount');
        if ($amount !== null && trim((string) $amount) !== '') {
            $digits = Jalali::latinDigits((string) $amount);
            $digits = str_replace([',', '٬', ' '], '', $digits);
            $request->merge(['tankhah_amount' => $digits]);
        }

        $data = $request->validate([
            'tankhah_amount' => ['nullable', 'integer', 'min:0', 'max:999999999999999'],
            'tankhah_charged_on' => [
                'nullable', 'string', 'max:20',
                function (string $attribute, $value, \Closure $fail): void {
                    if ($value && ! Jalali::isValid($value)) {
                        $fail('تاریخ واریز تنخواه معتبر نیست. قالب درست: 1405/07/18');
                    }
                },
            ],
        ], [
            'integer' => 'مبلغ باید عدد صحیح باشد.',
            'min' => 'مبلغ نمی‌تواند منفی باشد.',
            'max' => 'مبلغ از حد مجاز بیشتر است.',
        ]);

        /** @var User $user */
        $user = $request->user();
        $old = [
            'tankhah_amount' => $user->tankhah_amount,
            'tankhah_charged_on' => jdate($user->tankhah_charged_on) ?: null,
        ];
        $user->tankhah_amount = ($data['tankhah_amount'] ?? '') === '' ? null : $data['tankhah_amount'];
        $user->tankhah_charged_on = Jalali::toGregorian($data['tankhah_charged_on'] ?? null);
        $user->save();

        AuditLog::record('tankhah_update', $user, $old, [
            'tankhah_amount' => $user->tankhah_amount,
            'tankhah_charged_on' => jdate($user->tankhah_charged_on) ?: null,
        ]);

        return redirect()->route('tankhah.edit')->with('success', 'اطلاعات واریز تنخواه ذخیره شد.');
    }
}
