@extends('layouts.app')

@section('title', 'تنظیمات عمومی')

@section('content')
    <h5 class="fw-bold mt-3 mb-3">تنظیمات عمومی</h5>

    <form method="post" action="{{ route('admin.settings.update') }}">
        @csrf @method('PUT')
        <div class="card kpi-card mb-3">
            <div class="card-header bg-white fw-bold">اطلاعات سازمان (در سربرگ سند چاپ می‌شود)</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">نام سازمان</label>
                        <input type="text" name="org_name" class="form-control" value="{{ old('org_name', $values['org_name']) }}" required maxlength="200">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">عنوان مجتمع</label>
                        <input type="text" name="complex_name" class="form-control" value="{{ old('complex_name', $values['complex_name']) }}" required maxlength="200">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">شناسه ملی</label>
                        <input type="text" name="national_id" class="form-control" dir="ltr" value="{{ old('national_id', $values['national_id']) }}" maxlength="30">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">کد اقتصادی</label>
                        <input type="text" name="economic_code" class="form-control" dir="ltr" value="{{ old('economic_code', $values['economic_code']) }}" maxlength="30">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">کد فرم</label>
                        <input type="text" name="form_code" class="form-control" dir="ltr" value="{{ old('form_code', $values['form_code']) }}" maxlength="40">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">تاریخ بازنگری فرم</label>
                        <input type="text" name="form_revision_date" class="form-control" dir="ltr" value="{{ old('form_revision_date', $values['form_revision_date']) }}" maxlength="20" placeholder="1398/04/26">
                    </div>
                </div>
            </div>
        </div>

        <div class="card kpi-card mb-3">
            <div class="card-header bg-white fw-bold">شماره‌گذاری و پیش‌فرض‌ها</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">الگوی شماره سند</label>
                        <input type="text" name="number_pattern" class="form-control" dir="ltr" value="{{ old('number_pattern', $values['number_pattern']) }}" required maxlength="40">
                        <div class="form-text">
                            نشانه‌های مجاز:
                            <code>{serial}</code> شماره سریال،
                            <code>{serial:3}</code> سریال سه‌رقمی،
                            <code>{serial:4}</code> سریال چهاررقمی،
                            <code>{yy}</code> سال دورقمی،
                            <code>{yyyy}</code> سال چهاررقمی.
                            نمونه: <code>{yy}-{serial:3}</code> ← 05-014
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">شعبه پیش‌فرض در فرم سند جدید</label>
                        <select name="default_branch_id" class="form-select">
                            <option value="">—</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(old('default_branch_id', $values['default_branch_id']) == $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <button class="btn btn-primary">ذخیره تنظیمات</button>
    </form>
@endsection
