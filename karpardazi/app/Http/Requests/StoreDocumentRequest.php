<?php

namespace App\Http\Requests;

use App\Support\Jalali;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $items = collect((array) $this->input('items', []))
            ->map(function ($row) {
                $row = (array) $row;
                foreach (['amount', 'tax', 'insurance'] as $field) {
                    $raw = Jalali::latinDigits((string) ($row[$field] ?? ''));
                    $raw = preg_replace('/[^0-9]/', '', $raw);
                    $row[$field] = $raw === '' ? 0 : $raw;
                }
                $row['description'] = trim((string) ($row['description'] ?? ''));
                $row['expense_date'] = trim((string) ($row['expense_date'] ?? ''));

                return $row;
            })
            ->values()
            ->all();

        $this->merge(['items' => $items]);
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'document_date' => ['required', 'string', 'max:20'],
            'karpardaz_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'attachments_pages' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.expense_date' => ['nullable', 'string', 'max:20'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
            'items.*.amount' => ['nullable', 'integer', 'min:0', 'max:999999999999999'],
            'items.*.tax' => ['nullable', 'integer', 'min:0', 'max:999999999999999'],
            'items.*.insurance' => ['nullable', 'integer', 'min:0', 'max:999999999999999'],
            'action' => ['required', 'in:save_draft,save_final'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (! Jalali::isValid($this->input('document_date'))) {
                $v->errors()->add('document_date', 'تاریخ سند معتبر نیست. قالب درست: 1405/07/18');
            }

            $usedRows = 0;
            foreach ((array) $this->input('items', []) as $i => $row) {
                $row = (array) $row;
                $isUsed = ($row['description'] ?? '') !== ''
                    || (int) ($row['amount'] ?? 0) > 0
                    || (int) ($row['tax'] ?? 0) > 0
                    || (int) ($row['insurance'] ?? 0) > 0
                    || trim((string) ($row['expense_date'] ?? '')) !== '';

                if (! $isUsed) {
                    continue;
                }
                $usedRows++;

                if (! Jalali::isValid($row['expense_date'] ?? null)) {
                    $v->errors()->add("items.$i.expense_date", 'تاریخ هزینه ردیف '.($i + 1).' معتبر نیست.');
                }
                if (($row['description'] ?? '') === '') {
                    $v->errors()->add("items.$i.description", 'شرح هزینه ردیف '.($i + 1).' الزامی است.');
                }
            }

            if ($usedRows === 0) {
                $v->errors()->add('items', 'حداقل یک ردیف هزینه با شرح و مبلغ وارد کنید.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'branch_id' => 'شعبه',
            'document_date' => 'تاریخ سند',
            'karpardaz_name' => 'نام کارپرداز',
            'description' => 'توضیحات',
            'attachments_pages' => 'تعداد برگ مدارک',
            'items.*.amount' => 'مبلغ ردیف :position',
            'items.*.tax' => 'مالیات ردیف :position',
            'items.*.insurance' => 'بیمه ردیف :position',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'وارد کردن :attribute الزامی است.',
            'exists' => ':attribute انتخاب‌شده معتبر نیست.',
            'integer' => ':attribute باید عدد صحیح باشد.',
            'min' => ':attribute نمی‌تواند منفی باشد.',
            'max' => ':attribute از حد مجاز بزرگ‌تر است.',
            'string' => ':attribute معتبر نیست.',
            'in' => 'مقدار :attribute معتبر نیست.',
        ];
    }
}
