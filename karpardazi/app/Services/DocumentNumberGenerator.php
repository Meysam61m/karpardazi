<?php

namespace App\Services;

use App\Models\Counter;
use App\Models\ExpenseDocument;
use App\Support\Jalali;
use Illuminate\Support\Facades\DB;

/**
 * تخصیص شماره یکتای سند — داخل تراکنش با قفل ردیف شمارنده.
 * این روش حتی با ثبت هم‌زمان چند سند، شماره تکراری تولید نمی‌کند.
 */
class DocumentNumberGenerator
{
    public function assign(ExpenseDocument $document): void
    {
        DB::transaction(function () use ($document) {
            $year = Jalali::yearOf($document->document_date);
            $key = "doc_serial_{$year}";

            $counter = Counter::where('key', $key)->lockForUpdate()->first();
            if (! $counter) {
                // اولین سند این سال: اگر داده قبلی هست از بیشترین سریال ادامه بده
                $counter = new Counter([
                    'key' => $key,
                    'value' => (int) ExpenseDocument::where('fiscal_year', $year)->max('serial'),
                ]);
            }

            $serial = (int) $counter->value;
            do {
                $serial++;
                $number = $this->render($year, $serial);
            } while (ExpenseDocument::where('document_number', $number)->exists());

            $counter->value = $serial;
            $counter->save();

            $document->forceFill([
                'fiscal_year' => $year,
                'serial' => $serial,
                'document_number' => $number,
                'status' => ExpenseDocument::STATUS_FINAL,
                'finalized_at' => now(),
            ])->save();
        });
    }

    /**
     * قالب‌سازی شماره از الگوی تنظیمات.
     * نشانه‌ها: {yyyy}=1405  {yy}=405  {serial}=1  {serial:3}=001  {serial:4}=0001
     */
    public function render(int $year, int $serial): string
    {
        $pattern = setting('number_pattern', '{yy}-{serial:3}') ?: '{yy}-{serial:3}';

        return strtr($pattern, [
            '{yyyy}' => (string) $year,
            '{yy}' => substr((string) $year, -3),
            '{serial:3}' => str_pad((string) $serial, 3, '0', STR_PAD_LEFT),
            '{serial:4}' => str_pad((string) $serial, 4, '0', STR_PAD_LEFT),
            '{serial}' => (string) $serial,
        ]);
    }
}
