@extends('layouts.app')

@section('title', $user->exists ? 'ویرایش کاربر' : 'کاربر جدید')

@section('content')
    <h5 class="fw-bold mt-3 mb-3">{{ $user->exists ? 'ویرایش کاربر «'.$user->name.'»' : 'ایجاد کاربر جدید' }}</h5>

    <div class="card kpi-card">
        <div class="card-body">
            <form method="post" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
                @csrf
                @if($user->exists) @method('PUT') @endif
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">نام و نام خانوادگی <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required maxlength="150">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">نام کاربری (لاتین) <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" dir="ltr" value="{{ old('username', $user->username) }}" required maxlength="50" autocomplete="off">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">نقش <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="karpardaz" @selected(old('role', $user->role) === 'karpardaz')>کارپرداز</option>
                            <option value="admin" @selected(old('role', $user->role) === 'admin')>مدیر</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">رمز عبور {{ $user->exists ? '(برای تغییر وارد کنید)' : '' }} <span class="text-danger">{{ $user->exists ? '' : '*' }}</span></label>
                        <input type="password" name="password" class="form-control" dir="ltr" {{ $user->exists ? '' : 'required' }} minlength="8" autocomplete="new-password">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تکرار رمز عبور</label>
                        <input type="password" name="password_confirmation" class="form-control" dir="ltr" autocomplete="new-password">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $user->exists ? $user->is_active : true))>
                            <label class="form-check-label" for="is_active">حساب فعال است</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">شعبه‌های مجاز کارپرداز</label>
                        @php($selectedBranches = old('branch_ids', $user->exists ? $user->branches->pluck('id')->all() : []))
                        <select name="branch_ids[]" class="form-select" multiple size="4">
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(in_array($branch->id, $selectedBranches))>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">با Ctrl یا Command می‌توانید چند شعبه انتخاب کنید. برای کارپرداز بدون شعبه، دسترسی به ثبت سند ندارد.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">مبلغ تنخواه (ریال) — برای کارپرداز</label>
                        <input type="text" name="tankhah_amount" class="form-control money-input" value="{{ old('tankhah_amount', $user->tankhah_amount) }}" inputmode="numeric">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاریخ آخرین شارژ تنخواه</label>
                        <input type="text" name="tankhah_charged_on" class="form-control jdate" value="{{ old('tankhah_charged_on', jdate($user->tankhah_charged_on)) }}" autocomplete="off">
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary">ذخیره</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
@endsection
