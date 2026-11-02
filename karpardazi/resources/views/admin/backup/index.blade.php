@extends('layouts.app')

@section('title', 'پشتیبان‌گیری و بازیابی')

@section('content')
    <h5 class="fw-bold mt-3 mb-3">پشتیبان‌گیری و بازیابی پایگاه داده</h5>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card kpi-card h-100">
                <div class="card-body">
                    <div class="fw-bold mb-2">دریافت نسخه پشتیبان</div>
                    <p class="small text-muted">
                        از کل داده‌های سامانه (پایگاه داده «{{ $database }}») یک فایل SQL تهیه و دانلود می‌شود.
                        این کار را به‌طور منظم (مثلاً هفتگی) انجام دهید.
                    </p>
                    @if($driver === 'mysql')
                        <form method="post" action="{{ route('admin.backup.download') }}">
                            @csrf
                            <button class="btn btn-primary">دانلود نسخه پشتیبان</button>
                        </form>
                    @else
                        <div class="alert alert-warning small mb-0">
                            پشتیبان‌گیری خودکار فقط با MySQL/MariaDB فعال است (پایگاه فعلی: {{ $driver }}).
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card kpi-card h-100"><div class="card-body">
                <div class="fw-bold mb-2">بازیابی نسخه پشتیبان</div>
                <div class="alert alert-warning small mb-0">
                    بازیابی از طریق پنل وب به دلایل امنیتی غیرفعال است. برای بازیابی، از اپراتور پایگاه داده بخواهید
                    فایل نسخه پشتیبان را در محیط کنترل‌شده و پس از تهیه نسخه پشتیبان فعلی بازیابی کند.
                </div>
            </div></div>
        </div>
    </div>
@endsection
