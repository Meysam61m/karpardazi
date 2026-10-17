@extends('layouts.app')

@section('title', 'داشبورد')

@section('content')
    <h5 class="fw-bold mt-3 mb-3">داشبورد</h5>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="card kpi-card"><div class="card-body">
                <div class="kpi-label">تعداد اسناد ثبت‌شده</div>
                <div class="kpi-value">{{ fa($stats['docs_total']) }}</div>
                <div class="small text-muted">قطعی: {{ fa($stats['docs_final']) }} — پیش‌نویس: {{ fa($stats['docs_draft']) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card kpi-card"><div class="card-body">
                <div class="kpi-label">مجموع مبالغ اسناد قطعی (ریال)</div>
                <div class="kpi-value">{{ fa_money($stats['grand']) }}</div>
                <div class="small text-muted">مبلغ: {{ fa_money($stats['sum_amount']) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card kpi-card"><div class="card-body">
                <div class="kpi-label">مالیات و بیمه اسناد قطعی (ریال)</div>
                <div class="kpi-value">{{ fa_money($stats['sum_tax'] + $stats['sum_insurance']) }}</div>
                <div class="small text-muted">مالیات: {{ fa_money($stats['sum_tax']) }} — بیمه: {{ fa_money($stats['sum_insurance']) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card kpi-card"><div class="card-body">
                <div class="kpi-label">اسناد ماه جاری</div>
                <div class="kpi-value">{{ fa($stats['month_count']) }} سند</div>
                <div class="small text-muted">جمع: {{ fa_money($stats['month_sum']) }} ریال</div>
            </div></div>
        </div>
    </div>

    @if($tankhahCards->isNotEmpty())
        <div class="row g-3 mb-3">
            @foreach($tankhahCards as $tu)
                <div class="col-md-6 col-lg-4">
                    <div class="card kpi-card border-start border-4 border-info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold">تنخواه {{ $tu->name }}</span>
                                @if($tu->tankhah_charged_on)
                                    <span class="small text-muted">شارژ: {{ jdate_fa($tu->tankhah_charged_on) }}</span>
                                @endif
                            </div>
                            <div class="small">مبلغ تنخواه: <strong>{{ fa_money($tu->tankhah_amount) }}</strong> ریال</div>
                            <div class="small">هزینه‌شده: {{ fa_money($tu->tankhahSpent()) }} ریال</div>
                            @php $remaining = $tu->tankhahRemaining(); @endphp
                            <div class="mt-1">مانده:
                                <span class="fw-bold {{ $remaining !== null && $remaining < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ fa_money($remaining) }} ریال
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card kpi-card h-100">
                <div class="card-body">
                    <div class="fw-bold mb-3">هزینه‌ها به تفکیک ماه (اسناد قطعی)</div>
                    <canvas id="monthlyChart" height="260"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card kpi-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold">آخرین اسناد</span>
                        <form method="get" action="{{ route('documents.index') }}" class="d-flex gap-2 no-print">
                            <input type="text" name="q" class="form-control form-control-sm" placeholder="جست‌وجوی شماره سند یا شرح…" style="width: 220px;">
                            <button class="btn btn-sm btn-outline-primary">جست‌وجو</button>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                            <tr>
                                <th>شماره</th><th>تاریخ</th><th>شعبه</th><th>جمع کل (ریال)</th><th>وضعیت</th><th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($latest as $doc)
                                <tr>
                                    <td>{{ $doc->document_number ? fa($doc->document_number) : '—' }}</td>
                                    <td>{{ jdate_fa($doc->document_date) }}</td>
                                    <td>{{ $doc->branch?->name }}</td>
                                    <td>{{ fa_money($doc->grandTotal()) }}</td>
                                    <td><span class="badge {{ $doc->isFinal() ? 'badge-final' : 'badge-draft' }}">{{ $doc->isFinal() ? 'قطعی' : 'پیش‌نویس' }}</span></td>
                                    <td><a class="btn btn-sm btn-outline-secondary" href="{{ route('documents.show', $doc) }}">مشاهده</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">هنوز سندی ثبت نشده است.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: {
            labels: @json($chart['labels']),
            datasets: [{
                data: @json($chart['data']),
                backgroundColor: '#3d7fd6',
                borderRadius: 6
            }]
        },
        options: {
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (c) { return c.parsed.y.toLocaleString('en-US') + ' ریال'; }
                    },
                    rtl: true
                }
            },
            scales: {
                y: { ticks: { callback: function (v) { return v.toLocaleString('en-US'); } } }
            }
        }
    });
</script>
@endpush
