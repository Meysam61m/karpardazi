@extends('layouts.app')

@section('title', 'ثبت سند هزینه جدید')

@section('content')
    <h5 class="fw-bold mt-3 mb-3">ثبت سند هزینه جدید</h5>

    <form method="post" action="{{ route('documents.store') }}">
        @csrf
        <div class="card kpi-card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">شعبه / محل هزینه <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-select" required>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(old('branch_id', $defaultBranch?->id) == $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">تاریخ سند <span class="text-danger">*</span></label>
                        <input type="text" name="document_date" class="form-control jdate" value="{{ old('document_date', $todayJalali) }}" required autocomplete="off" placeholder="1405/07/18">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">نام کارپرداز <span class="text-danger">*</span></label>
                        <input type="text" name="karpardaz_name" class="form-control" value="{{ old('karpardaz_name', auth()->user()->name) }}" required maxlength="150">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">تعداد برگ مدارک (اختیاری)</label>
                        <input type="number" name="attachments_pages" class="form-control" value="{{ old('attachments_pages') }}" min="0" max="65535">
                    </div>
                    <div class="col-12">
                        <label class="form-label">توضیحات</label>
                        <textarea name="description" class="form-control" rows="2" maxlength="2000">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card kpi-card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold">اقلام هزینه</span>
                    <button type="button" id="add-row" class="btn btn-sm btn-outline-primary">+ افزودن ردیف</button>
                </div>
                <div class="table-responsive">
                    <table class="table items-table mb-0">
                        <thead>
                        <tr>
                            <th class="row-num">ردیف</th>
                            <th style="min-width: 120px;">تاریخ</th>
                            <th style="min-width: 260px;">شرح هزینه</th>
                            <th style="min-width: 130px;">بیمه (ریال)</th>
                            <th style="min-width: 130px;">مالیات (ریال)</th>
                            <th style="min-width: 150px;">مبلغ (ریال)</th>
                            <th style="width: 40px;"></th>
                        </tr>
                        </thead>
                        <tbody id="items-body">
                        @php $oldItems = old('items', []); @endphp
                        @if(count($oldItems))
                            @foreach($oldItems as $i => $item)
                                <tr>
                                    <td class="row-num"></td>
                                    <td><input type="text" name="items[{{ $i }}][expense_date]" class="form-control form-control-sm jdate" value="{{ $item['expense_date'] ?? '' }}" autocomplete="off"></td>
                                    <td><input type="text" name="items[{{ $i }}][description]" class="form-control form-control-sm" value="{{ $item['description'] ?? '' }}" maxlength="500"></td>
                                    <td><input type="text" name="items[{{ $i }}][insurance]" class="form-control form-control-sm money-input item-insurance" value="{{ $item['insurance'] ?? '' }}" inputmode="numeric"></td>
                                    <td><input type="text" name="items[{{ $i }}][tax]" class="form-control form-control-sm money-input item-tax" value="{{ $item['tax'] ?? '' }}" inputmode="numeric"></td>
                                    <td><input type="text" name="items[{{ $i }}][amount]" class="form-control form-control-sm money-input item-amount" value="{{ $item['amount'] ?? '' }}" inputmode="numeric"></td>
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger del-row" title="حذف ردیف">×</button></td>
                                </tr>
                            @endforeach
                        @else
                            @for($i = 0; $i < 3; $i++)
                                <tr>
                                    <td class="row-num"></td>
                                    <td><input type="text" name="items[{{ $i }}][expense_date]" class="form-control form-control-sm jdate" value="{{ $todayJalali }}" autocomplete="off"></td>
                                    <td><input type="text" name="items[{{ $i }}][description]" class="form-control form-control-sm" maxlength="500"></td>
                                    <td><input type="text" name="items[{{ $i }}][insurance]" class="form-control form-control-sm money-input item-insurance" inputmode="numeric"></td>
                                    <td><input type="text" name="items[{{ $i }}][tax]" class="form-control form-control-sm money-input item-tax" inputmode="numeric"></td>
                                    <td><input type="text" name="items[{{ $i }}][amount]" class="form-control form-control-sm money-input item-amount" inputmode="numeric"></td>
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger del-row" title="حذف ردیف">×</button></td>
                                </tr>
                            @endfor
                        @endif
                        </tbody>
                        <tfoot>
                        <tr class="fw-bold table-light">
                            <td colspan="3" class="text-start">جمع:</td>
                            <td id="sum-insurance">۰</td>
                            <td id="sum-tax">۰</td>
                            <td id="sum-amount">۰</td>
                            <td></td>
                        </tr>
                        <tr class="fw-bold table-primary">
                            <td colspan="5" class="text-start">جمع کل (مبلغ + مالیات + بیمه):</td>
                            <td id="sum-grand">۰</td>
                            <td></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
                @error('items')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" name="action" value="save_draft" class="btn btn-outline-secondary">ثبت پیش‌نویس</button>
            <button type="submit" name="action" value="save_final" class="btn btn-primary" onclick="return confirm('با ثبت قطعی، شماره سند صادر می‌شود و دیگر قابل تغییر نیست. ادامه می‌دهید؟')">ثبت قطعی و صدور شماره</button>
        </div>
    </form>
@endsection
