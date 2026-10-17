@extends('layouts.app')

@section('title', 'گزارش‌ها')

@section('content')
    <h5 class="fw-bold mt-3 mb-3">گزارش‌ها</h5>

    <div class="card kpi-card mb-3 no-print">
        <div class="card-body py-3">
            <form method="get" action="{{ route('reports.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">نوع گزارش</label>
                    <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}" @selected($report['type'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @if($report['type'] !== 'incomplete')
                    <div class="col-md-2">
                        <label class="form-label small mb-1">از تاریخ</label>
                        <input type="text" name="from" class="form-control form-control-sm jdate" value="{{ request('from') }}" autocomplete="off">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">تا تاریخ</label>
                        <input type="text" name="to" class="form-control form-control-sm jdate" value="{{ request('to') }}" autocomplete="off">
                    </div>
                @endif
                @if(!in_array($report['type'], ['incomplete'], true))
                    <div class="col-md-2">
                        <label class="form-label small mb-1">شعبه</label>
                        <select name="branch_id" class="form-select form-select-sm">
                            <option value="">همه</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @if($report['type'] === 'documents')
                    <div class="col-md-2">
                        <label class="form-label small mb-1">وضعیت</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">همه</option>
                            <option value="final" @selected(request('status') === 'final')>قطعی</option>
                            <option value="draft" @selected(request('status') === 'draft')>پیش‌نویس</option>
                        </select>
                    </div>
                @endif
                <div class="col-md-1 d-grid">
                    <button class="btn btn-sm btn-primary">نمایش</button>
                </div>
                <div class="col-md-auto">
                    <a href="{{ route('reports.export', request()->query()) }}" class="btn btn-sm btn-success">خروجی Excel</a>
                    <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary">چاپ</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card kpi-card">
        <div class="card-header bg-white fw-bold">
            {{ $report['title'] }}
            @if(request('from') || request('to'))
                <span class="text-muted small fw-normal">
                    (از {{ fa(request('from', '—')) }} تا {{ fa(request('to', '—')) }})
                </span>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-sm mb-0">
                <thead>
                <tr>
                    @foreach($report['headings'] as $heading)
                        <th>{{ $heading }}</th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @forelse($report['rows'] as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($report['headings']) }}" class="text-center text-muted py-4">داده‌ای یافت نشد.</td></tr>
                @endforelse
                </tbody>
                @if($report['totalsRow'] && count($report['rows']))
                    <tfoot>
                    <tr class="fw-bold table-light">
                        @foreach($report['totalsRow'] as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
