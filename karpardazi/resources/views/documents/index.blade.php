@extends('layouts.app')

@section('title', 'اسناد هزینه')

@section('content')
    <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
        <h5 class="fw-bold mb-0">اسناد هزینه</h5>
        <a href="{{ route('documents.create') }}" class="btn btn-primary btn-sm">+ سند جدید</a>
    </div>

    <div class="card kpi-card mb-3 no-print">
        <div class="card-body py-3">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">جست‌وجو (شماره، شرح، کارپرداز)</label>
                    <input type="text" name="q" class="form-control form-control-sm" value="{{ request('q') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">از تاریخ</label>
                    <input type="text" name="from" class="form-control form-control-sm jdate" value="{{ request('from') }}" placeholder="1405/01/01" autocomplete="off">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">تا تاریخ</label>
                    <input type="text" name="to" class="form-control form-control-sm jdate" value="{{ request('to') }}" autocomplete="off">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">شعبه</label>
                    <select name="branch_id" class="form-select form-select-sm">
                        <option value="">همه</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">وضعیت</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">همه</option>
                        <option value="final" @selected(request('status') === 'final')>قطعی</option>
                        <option value="draft" @selected(request('status') === 'draft')>پیش‌نویس</option>
                    </select>
                </div>
                <div class="col-md-1 d-grid">
                    <button class="btn btn-sm btn-outline-primary">اعمال</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card kpi-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>شماره سند</th><th>تاریخ</th><th>شعبه</th><th>کارپرداز</th>
                    <th>مبلغ (ریال)</th><th>مالیات</th><th>بیمه</th><th>جمع کل</th>
                    <th>وضعیت</th><th class="no-print">عملیات</th>
                </tr>
                </thead>
                <tbody>
                @forelse($documents as $doc)
                    <tr>
                        <td class="fw-bold">{{ $doc->document_number ? fa($doc->document_number) : '—' }}</td>
                        <td>{{ jdate_fa($doc->document_date) }}</td>
                        <td>{{ $doc->branch?->name }}</td>
                        <td>{{ $doc->karpardaz_name }}</td>
                        <td>{{ fa_money($doc->total_amount) }}</td>
                        <td>{{ fa_money($doc->total_tax) }}</td>
                        <td>{{ fa_money($doc->total_insurance) }}</td>
                        <td class="fw-bold">{{ fa_money($doc->grandTotal()) }}</td>
                        <td><span class="badge {{ $doc->isFinal() ? 'badge-final' : 'badge-draft' }}">{{ $doc->isFinal() ? 'قطعی' : 'پیش‌نویس' }}</span></td>
                        <td class="text-nowrap no-print">
                            <a href="{{ route('documents.show', $doc) }}" class="btn btn-sm btn-outline-secondary">مشاهده</a>
                            <a href="{{ route('documents.print', $doc) }}" target="_blank" class="btn btn-sm btn-outline-primary">چاپ</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">سندی یافت نشد.</td></tr>
                @endforelse
                </tbody>
                @if($documents->count())
                    <tfoot>
                    <tr class="fw-bold table-light">
                        <td colspan="4">جمع ({{ fa($sums->c) }} سند)</td>
                        <td>{{ fa_money($sums->a) }}</td>
                        <td>{{ fa_money($sums->t) }}</td>
                        <td>{{ fa_money($sums->i) }}</td>
                        <td>{{ fa_money($sums->a + $sums->t + $sums->i) }}</td>
                        <td colspan="2"></td>
                    </tr>
                    </tfoot>
                @endif
            </table>
        </div>
        @if($documents->hasPages())
            <div class="card-footer no-print">{{ $documents->links() }}</div>
        @endif
    </div>
@endsection
