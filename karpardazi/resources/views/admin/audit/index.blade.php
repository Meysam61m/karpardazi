@extends('layouts.app')

@section('title', 'سوابق و تغییرات')

@section('content')
    <h5 class="fw-bold mt-3 mb-3">سوابق و تغییرات</h5>

    <div class="card kpi-card mb-3 no-print">
        <div class="card-body py-3">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">کاربر</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">همه</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">رویداد</label>
                    <select name="event" class="form-select form-select-sm">
                        <option value="">همه</option>
                        @foreach($events as $event)
                            <option value="{{ $event }}" @selected(request('event') === $event)>{{ \App\Models\AuditLog::eventLabel($event) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">از تاریخ</label>
                    <input type="text" name="from" class="form-control form-control-sm jdate" value="{{ request('from') }}" autocomplete="off">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">تا تاریخ</label>
                    <input type="text" name="to" class="form-control form-control-sm jdate" value="{{ request('to') }}" autocomplete="off">
                </div>
                <div class="col-md-1 d-grid">
                    <button class="btn btn-sm btn-outline-primary">اعمال</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card kpi-card">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                <tr><th>زمان</th><th>کاربر</th><th>رویداد</th><th>IP</th><th>جزئیات</th></tr>
                </thead>
                <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="text-nowrap">{{ jdate_fa($log->created_at, 'Y/m/d H:i:s') }}</td>
                        <td>{{ $log->user?->name ?? 'سیستم' }}</td>
                        <td>{{ \App\Models\AuditLog::eventLabel($log->event) }}</td>
                        <td dir="ltr" class="text-start">{{ $log->ip ?? '—' }}</td>
                        <td>
                            @if($log->old_values || $log->new_values)
                                <details>
                                    <summary class="text-primary" style="cursor:pointer;">نمایش تغییرات</summary>
                                    <div class="small mt-1">
                                        @if($log->old_values)
                                            <div><span class="text-muted">قبل:</span> <code dir="ltr" class="d-block text-break">{{ json_encode($log->old_values, JSON_UNESCAPED_UNICODE) }}</code></div>
                                        @endif
                                        @if($log->new_values)
                                            <div><span class="text-muted">بعد:</span> <code dir="ltr" class="d-block text-break">{{ json_encode($log->new_values, JSON_UNESCAPED_UNICODE) }}</code></div>
                                        @endif
                                    </div>
                                </details>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">موردی یافت نشد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="card-footer no-print">{{ $logs->links() }}</div>
        @endif
    </div>
@endsection
