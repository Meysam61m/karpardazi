@extends('layouts.app')

@section('title', 'مدیریت شعب')

@section('content')
    <h5 class="fw-bold mt-3 mb-3">مدیریت شعب و کدهای آن‌ها</h5>

    <div class="card kpi-card mb-3">
        <div class="card-body py-3">
            <form method="post" action="{{ route('admin.branches.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label small mb-1">نام شعبه جدید</label>
                    <input type="text" name="name" class="form-control form-control-sm" required maxlength="150">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">کد شعبه</label>
                    <input type="text" name="code" class="form-control form-control-sm" maxlength="20">
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-sm btn-primary">افزودن</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card kpi-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                <tr>
                    <th style="width: 30%;">نام شعبه</th><th style="width: 12%;">کد</th>
                    <th style="width: 10%;">تعداد اسناد</th><th style="width: 12%;">پیش‌فرض</th><th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($branches as $branch)
                    <tr>
                        <form method="post" action="{{ route('admin.branches.update', $branch) }}">
                            @csrf @method('PUT')
                            <td><input type="text" name="name" class="form-control form-control-sm" value="{{ $branch->name }}" required maxlength="150"></td>
                            <td><input type="text" name="code" class="form-control form-control-sm" value="{{ $branch->code }}" maxlength="20"></td>
                            <td>{{ fa($branch->documents_count) }}</td>
                            <td>
                                @if($branch->is_default)
                                    <span class="badge badge-final">پیش‌فرض</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <button class="btn btn-sm btn-outline-primary">ذخیره</button>
                        </form>
                                @if(!$branch->is_default)
                                    <form method="post" action="{{ route('admin.branches.default', $branch) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success" title="شعبه پیش‌فرض فرم سند">پیش‌فرض</button>
                                    </form>
                                @endif
                                @if($branch->documents_count === 0)
                                    <form method="post" action="{{ route('admin.branches.destroy', $branch) }}" class="d-inline" onsubmit="return confirm('شعبه حذف شود؟')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">حذف</button>
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
