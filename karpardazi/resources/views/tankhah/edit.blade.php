@extends('layouts.app')

@section('title', 'اطلاعات تنخواه')

@section('content')
    <h5 class="fw-bold mt-3 mb-3">اطلاعات واریز تنخواه</h5>
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card kpi-card"><div class="card-body">
                <p class="text-muted">مبلغ و تاریخ واریز ثبت‌شده برای حساب شما. این اطلاعات در محاسبه مانده تنخواه استفاده می‌شود.</p>
                <form method="post" action="{{ route('tankhah.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="tankhah_amount">مبلغ واریز (ریال)</label>
                            <input id="tankhah_amount" type="text" inputmode="numeric" name="tankhah_amount" class="form-control money-input" value="{{ old('tankhah_amount', $user->tankhah_amount) }}" maxlength="18">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="tankhah_charged_on">تاریخ واریز</label>
                            <input id="tankhah_charged_on" type="text" name="tankhah_charged_on" class="form-control jdate" value="{{ old('tankhah_charged_on', jdate($user->tankhah_charged_on)) }}" placeholder="1405/07/18" autocomplete="off">
                        </div>
                    </div>
                    <div class="mt-4"><button class="btn btn-primary">ذخیره اطلاعات</button></div>
                </form>
            </div></div>
        </div>
        <div class="col-lg-5">
            <div class="card kpi-card"><div class="card-body">
                <div class="fw-bold mb-3">خلاصه تنخواه شما</div>
                <div class="d-flex justify-content-between mb-2"><span>مبلغ واریزی</span><strong>{{ $user->tankhah_amount === null ? 'ثبت نشده' : fa(number_format($user->tankhah_amount)) . ' ریال' }}</strong></div>
                <div class="d-flex justify-content-between mb-2"><span>هزینه قطعی از تاریخ واریز</span><strong>{{ fa(number_format($spent)) }} ریال</strong></div>
                <hr>
                <div class="d-flex justify-content-between"><span>مانده</span><strong>{{ $remaining === null ? 'نامشخص' : fa(number_format($remaining)) . ' ریال' }}</strong></div>
            </div></div>
        </div>
    </div>
@endsection
