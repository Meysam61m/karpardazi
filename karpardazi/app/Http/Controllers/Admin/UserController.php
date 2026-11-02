<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use App\Support\Jalali;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return view('admin.users.index', ['users' => User::with('branches')->orderBy('id')->get()]);
    }

    public function create()
    {
        return view('admin.users.form', ['user' => new User, 'branches' => Branch::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateUser($request);

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'password' => $data['password'],
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active'),
            'tankhah_amount' => $data['tankhah_amount'] ?? null,
            'tankhah_charged_on' => Jalali::toGregorian($data['tankhah_charged_on'] ?? null),
        ]);

        $user->branches()->sync($data['role'] === User::ROLE_KARPARDAZ ? ($data['branch_ids'] ?? []) : []);

        AuditLog::record('user_create', $user, null, [
            'username' => $user->username,
            'role' => $user->role,
            'branch_ids' => $user->branches()->pluck('branches.id')->all(),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'حساب کاربر ساخته شد.');
    }

    public function edit(User $user)
    {
        return view('admin.users.form', ['user' => $user->load('branches'), 'branches' => Branch::orderBy('name')->get()]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validateUser($request, $user->id);

        // محافظت از آخرین مدیر فعال
        $becomesInactive = $user->isAdmin() && ($data['role'] !== 'admin' || ! $request->boolean('is_active'));
        if ($becomesInactive && User::where('role', 'admin')->where('is_active', true)->count() === 1) {
            return back()->withInput()->with('error', 'این تنها حساب مدیر فعال است؛ نمی‌توانید نقش یا وضعیت آن را تغییر دهید.');
        }

        $old = [
            'name' => $user->name,
            'username' => $user->username,
            'role' => $user->role,
            'is_active' => $user->is_active,
            'tankhah_amount' => $user->tankhah_amount,
            'tankhah_charged_on' => jdate($user->tankhah_charged_on) ?: null,
            'branch_ids' => $user->branches()->pluck('branches.id')->all(),
        ];

        $user->fill([
            'name' => $data['name'],
            'username' => $data['username'],
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active'),
            'tankhah_amount' => $data['tankhah_amount'] ?? null,
            'tankhah_charged_on' => Jalali::toGregorian($data['tankhah_charged_on'] ?? null),
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();
        $user->branches()->sync($data['role'] === User::ROLE_KARPARDAZ ? ($data['branch_ids'] ?? []) : []);

        AuditLog::record('user_update', $user, $old, [
            'name' => $user->name,
            'username' => $user->username,
            'role' => $user->role,
            'is_active' => $user->is_active,
            'tankhah_amount' => $user->tankhah_amount,
            'tankhah_charged_on' => jdate($user->tankhah_charged_on) ?: null,
            'branch_ids' => $user->branches()->pluck('branches.id')->all(),
        ]);

        if (! empty($data['password'])) {
            AuditLog::record('user_password_reset', $user);
        }

        return redirect()->route('admin.users.index')->with('success', 'اطلاعات کاربر به‌روزرسانی شد.');
    }

    public function toggle(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'نمی‌توانید حساب خودتان را غیرفعال کنید.');
        }

        if ($user->isAdmin() && $user->is_active
            && User::where('role', User::ROLE_ADMIN)->where('is_active', true)->count() <= 1) {
            return back()->with('error', 'این تنها حساب مدیر فعال است و نمی‌توان آن را غیرفعال کرد.');
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        AuditLog::record($user->is_active ? 'user_activate' : 'user_deactivate', $user);

        return back()->with('success', $user->is_active ? 'حساب فعال شد.' : 'حساب غیرفعال شد.');
    }

    private function validateUser(Request $request, ?int $ignoreId = null): array
    {
        // جداکننده هزارگان و ارقام فارسی ورودی را قبل از اعتبارسنجی حذف می‌کنیم
        $amount = $request->input('tankhah_amount');
        if ($amount !== null && trim((string) $amount) !== '') {
            $request->merge([
                'tankhah_amount' => preg_replace('/[^0-9]/', '', Jalali::latinDigits((string) $amount)),
            ]);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => [
                'required', 'string', 'min:3', 'max:50',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($ignoreId),
            ],
            'password' => [$ignoreId ? 'nullable' : 'required', 'string', 'min:8', 'max:100', 'confirmed'],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_KARPARDAZ])],
            'branch_ids' => ['nullable', 'array', 'max:100'],
            'branch_ids.*' => ['integer', 'distinct', Rule::exists('branches', 'id')],
            'tankhah_amount' => ['nullable', 'integer', 'min:0', 'max:999999999999999'],
            'tankhah_charged_on' => [
                'nullable', 'string', 'max:20',
                function (string $attribute, $value, \Closure $fail) {
                    if ($value && ! Jalali::isValid($value)) {
                        $fail('تاریخ شارژ تنخواه معتبر نیست. قالب درست: 1405/07/18');
                    }
                },
            ],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'required' => 'وارد کردن :attribute الزامی است.',
            'unique' => 'این :attribute قبلاً استفاده شده است.',
            'min' => ':attribute کوتاه‌تر از حد مجاز است.',
            'confirmed' => 'تکرار رمز عبور با رمز یکسان نیست.',
            'regex' => ':attribute فقط می‌تواند شامل حروف لاتین، اعداد، نقطه و زیرخط باشد.',
            'integer' => ':attribute باید عدد صحیح باشد.',
        ], [
            'name' => 'نام و نام خانوادگی',
            'username' => 'نام کاربری',
            'password' => 'رمز عبور',
            'role' => 'نقش',
            'tankhah_amount' => 'مبلغ تنخواه',
            'tankhah_charged_on' => 'تاریخ شارژ تنخواه',
            'branch_ids' => 'شعبه‌های مجاز',
        ]);
    }
}
