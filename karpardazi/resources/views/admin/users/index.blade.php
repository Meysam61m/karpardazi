@extends('layouts.app')

@section('title', 'مدیریت کاربران')

@section('content')
    <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
        <h5 class="fw-bold mb-0">مدیریت کاربران</h5>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">+ کاربر جدید</a>
    </div>

    <div class="card kpi-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>نام</th><th>نام کاربری</th><th>نقش</th><th>وضعیت</th>
                    <th>شعبه‌های مجاز</th><th>تنخواه (ریال)</th><th>تاریخ شارژ</th><th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td dir="ltr" class="text-start">{{ $user->username }}</td>
                        <td>{{ $user->isAdmin() ? 'مدیر' : 'کارپرداز' }}</td>
                        <td><span class="badge {{ $user->is_active ? 'badge-final' : 'bg-secondary' }}">{{ $user->is_active ? 'فعال' : 'غیرفعال' }}</span></td>
                        <td>{{ $user->isAdmin() ? 'همه شعب' : ($user->branches->pluck('name')->join('، ') ?: 'بدون دسترسی') }}</td>
                        <td>{{ $user->tankhah_amount !== null ? fa_money($user->tankhah_amount) : '—' }}</td>
                        <td>{{ $user->tankhah_charged_on ? jdate_fa($user->tankhah_charged_on) : '—' }}</td>
                        <td class="text-nowrap">
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">ویرایش</a>
                            @if($user->id !== auth()->id())
                                <form method="post" action="{{ route('admin.users.toggle', $user) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                        {{ $user->is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
