@extends('layouts.app')

@section('title', 'سند ' . ($document->document_number ? fa($document->document_number) : 'پیش‌نویس'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
        <h5 class="fw-bold mb-0">
            سند هزینه شعبه {{ $document->branch?->name }}
            @if($document->document_number)
                — <span class="text-primary">{{ fa($document->document_number) }}</span>
            @endif
            <span class="badge {{ $document->isFinal() ? 'badge-final' : 'badge-draft' }}">{{ $document->isFinal() ? 'قطعی' : 'پیش‌نویس' }}</span>
        </h5>
        <div class="d-flex gap-2 no-print">
            <a href="{{ route('documents.print', $document) }}" target="_blank" class="btn btn-primary btn-sm">چاپ سند</a>
            <a href="{{ route('documents.edit', $document) }}" class="btn btn-outline-secondary btn-sm">ویرایش</a>
            @if($document->canDelete())
                <form method="post" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('این پیش‌نویس حذف شود؟')">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm">حذف</button>
                </form>
            @endif
        </div>
    </div>

    <div class="card kpi-card mb-3">
        <div class="card-body">
            <div class="row g-2 small">
                <div class="col-md-3"><span class="text-muted">تاریخ سند:</span> {{ jdate_fa($document->document_date) }}</div>
                <div class="col-md-3"><span class="text-muted">کارپرداز:</span> {{ $document->karpardaz_name }}</div>
                <div class="col-md-3"><span class="text-muted">ثبت‌کننده:</span> {{ $document->user?->name }}</div>
                <div class="col-md-3"><span class="text-muted">تعداد برگ مدارک:</span> {{ $document->attachments_pages !== null ? fa($document->attachments_pages) : '—' }}</div>
                @if($document->isFinal())
                    <div class="col-md-3"><span class="text-muted">زمان قطعی شدن:</span> {{ jdate_fa($document->finalized_at, 'Y/m/d H:i') }}</div>
                @endif
                @if($document->description)
                    <div class="col-12"><span class="text-muted">توضیحات:</span> {{ $document->description }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="card kpi-card mb-3">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                <tr>
                    <th>ردیف</th><th>تاریخ</th><th>شرح هزینه</th>
                    <th>بیمه (ریال)</th><th>مالیات (ریال)</th><th>مبلغ (ریال)</th>
                </tr>
                </thead>
                <tbody>
                @foreach($document->items as $item)
                    <tr>
                        <td>{{ fa($item->row_number) }}</td>
                        <td>{{ jdate_fa($item->expense_date) }}</td>
                        <td>{{ $item->description }}</td>
                        <td>{{ fa_money($item->insurance) }}</td>
                        <td>{{ fa_money($item->tax) }}</td>
                        <td>{{ fa_money($item->amount) }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                <tr class="fw-bold table-light">
                    <td colspan="3" class="text-start">جمع:</td>
                    <td>{{ fa_money($document->total_insurance) }}</td>
                    <td>{{ fa_money($document->total_tax) }}</td>
                    <td>{{ fa_money($document->total_amount) }}</td>
                </tr>
                <tr class="fw-bold table-primary">
                    <td colspan="5" class="text-start">جمع کل:</td>
                    <td>{{ fa_money($document->grandTotal()) }}</td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="card kpi-card no-print">
        <div class="card-body">
            <div class="fw-bold mb-2">سوابق این سند</div>
            <div class="table-responsive">
                <table class="table table-sm small mb-0">
                    <thead><tr><th>زمان</th><th>کاربر</th><th>رویداد</th></tr></thead>
                    <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ jdate_fa($log->created_at, 'Y/m/d H:i:s') }}</td>
                            <td>{{ $log->user?->name ?? 'سیستم' }}</td>
                            <td>{{ \App\Models\AuditLog::eventLabel($log->event) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">موردی ثبت نشده است.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
