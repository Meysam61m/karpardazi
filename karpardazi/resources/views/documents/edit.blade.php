@extends('layouts.app')

@section('title', 'ویرایش سند')

@section('content')
    <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
        <h5 class="fw-bold mb-0">
            ویرایش سند
            @if($document->document_number)
                <span class="text-primary">{{ fa($document->document_number) }}</span>
            @else
                <span class="text-muted">(پیش‌نویس)</span>
            @endif
        </h5>
        <a href="{{ route('documents.show', $document) }}" class="btn btn-outline-secondary btn-sm">بازگشت</a>
    </div>

    @if($document->isFinal())
        <div class="alert alert-warning">
            این سند <strong>قطعی</strong> است و شماره آن قابل تغییر نیست؛ اما می‌توانید اطلاعات آن را اصلاح کنید.
            همه تغییرات با نام کاربر و زمان در <strong>سوابق</strong> ثبت می‌شود.
        </div>
    @endif

    <form method="post" action="{{ route('documents.update', $document) }}">
        @csrf
        @method('PUT')
        <div class="card kpi-card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">شعبه / محل هزینه <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-select" required>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(old('branch_id', $document->branch_id) == $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">تاریخ سند <span class="text-danger">*</span></label>
                        <input type="text" name="document_date" class="form-control jdate" value="{{ old('document_date', jdate($document->document_date)) }}" required autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">نام کارپرداز <span class="text-danger">*</span></label>
                        <input type="text" name="karpardaz_name" class="form-control" value="{{ old('karpardaz_name', $document->karpardaz_name) }}" required maxlength="150">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">تعداد برگ مدارک (اختیاری)</label>
                        <input type="number" name="attachments_pages" class="form-control" value="{{ old('attachments_pages', $document->attachments_pages) }}" min="0" max="65535">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">مبلغ واریز تنخواه (ریال) <span class="text-danger">*</span></label>
                        <input type="text" name="tankhah_deposit_amount" class="form-control money-input" value="{{ old('tankhah_deposit_amount', $document->tankhah_deposit_amount) }}" inputmode="numeric" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">تاریخ واریز تنخواه <span class="text-danger">*</span></label>
                        <input type="text" name="tankhah_deposit_date" class="form-control jdate" value="{{ old('tankhah_deposit_date', jdate($document->tankhah_deposit_date)) }}" placeholder="1405/07/18" autocomplete="off" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">توضیحات</label>
                        <textarea name="description" class="form-control" rows="2" maxlength="2000">{{ old('description', $document->description) }}</textarea>
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
                        @php
                            $oldItems = old('items');
                            $rows = is_array($oldItems) ? $oldItems : $document->items->map(fn($i) => [
                                'expense_date' => jdate($i->expense_date),
                                'description' => $i->description,
                                'insurance' => $i->insurance ?: '',
                                'tax' => $i->tax ?: '',
                                'amount' => $i->amount ?: '',
                            ])->all();
                            if (count($rows) === 0) { $rows = [['expense_date' => '', 'description' => '', 'insurance' => '', 'tax' => '', 'amount' => '']]; }
                        @endphp
                        @foreach($rows as $i => $item)
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
            @if($document->isDraft())
                <button type="submit" name="action" value="save_draft" class="btn btn-outline-secondary">ذخیره پیش‌نویس</button>
                <button type="submit" name="action" value="save_final" class="btn btn-primary" onclick="return confirm('با ثبت قطعی، شماره سند صادر می‌شود و دیگر قابل تغییر نیست. ادامه می‌دهید؟')">ثبت قطعی و صدور شماره</button>
            @else
                <button type="submit" name="action" value="save_final" class="btn btn-primary">ذخیره تغییرات (با ثبت در سوابق)</button>
            @endif
        </div>
    </form>
@endsection
