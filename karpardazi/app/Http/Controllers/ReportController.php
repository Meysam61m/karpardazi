<?php

namespace App\Http\Controllers;

use App\Exports\SimpleExport;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\ExpenseDocument;
use App\Models\ExpenseItem;
use App\Support\Jalali;
use Hekmatinasser\Verta\Verta;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public const TYPES = [
        'documents' => 'فهرست اسناد هزینه',
        'daily' => 'گزارش روزانه',
        'monthly' => 'گزارش ماهانه',
        'yearly' => 'گزارش سالانه',
        'by_description' => 'گزارش بر اساس شرح هزینه',
        'tax_insurance' => 'گزارش مالیات و بیمه',
        'incomplete' => 'اسناد ناقص',
    ];

    public function index(Request $request)
    {
        $report = $this->buildReport($request);

        return view('reports.index', [
            'report' => $report,
            'types' => self::TYPES,
            'branches' => $request->user()->isAdmin() ? Branch::orderBy('id')->get() : $request->user()->branches()->get(),
        ]);
    }

    public function export(Request $request)
    {
        $report = $this->buildReport($request);

        AuditLog::record('report_export', null, null, ['type' => $report['type']]);

        $filename = 'gozaresh-'.$report['type'].'-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new SimpleExport($report['headings'], $report['exportRows']), $filename);
    }

    private function buildReport(Request $request): array
    {
        $type = $request->input('type', 'documents');
        if (! array_key_exists($type, self::TYPES)) {
            $type = 'documents';
        }

        [$from, $to] = $this->range($request);
        $branchId = $request->integer('branch_id') ?: null;

        return match ($type) {
            'daily' => $this->periodic($from, $to, $branchId, 'daily'),
            'monthly' => $this->periodic($from, $to, $branchId, 'monthly'),
            'yearly' => $this->periodic($from, $to, $branchId, 'yearly'),
            'by_description' => $this->byDescription($from, $to, $branchId),
            'tax_insurance' => $this->taxInsurance($from, $to, $branchId),
            'incomplete' => $this->incomplete(),
            default => $this->documents($request, $from, $to, $branchId),
        };
    }

    private function range(Request $request): array
    {
        return [
            Jalali::toGregorian($request->input('from')),
            Jalali::toGregorian($request->input('to')),
        ];
    }

    private function finalQuery(?string $from, ?string $to, ?int $branchId)
    {
        return ExpenseDocument::query()
            ->when(! auth()->user()->isAdmin(), fn ($q) => $q->whereIn('branch_id', auth()->user()->branches()->select('branches.id')))
            ->where('status', ExpenseDocument::STATUS_FINAL)
            ->when($from, fn ($q) => $q->whereDate('document_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('document_date', '<=', $to))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }

    private function documents(Request $request, ?string $from, ?string $to, ?int $branchId): array
    {
        $docs = ExpenseDocument::with('branch')
            ->when(! auth()->user()->isAdmin(), fn ($q) => $q->whereIn('branch_id', auth()->user()->branches()->select('branches.id')))
            ->when($from, fn ($q) => $q->whereDate('document_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('document_date', '<=', $to))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when(in_array($request->input('status'), ['draft', 'final'], true), fn ($q) => $q->where('status', $request->input('status')))
            ->orderBy('document_date')
            ->orderBy('serial')
            ->get();

        $rows = [];
        $exportRows = [];
        foreach ($docs as $doc) {
            $rows[] = [
                $doc->document_number ? fa($doc->document_number) : '—',
                jdate_fa($doc->document_date),
                $doc->branch?->name ?? '—',
                $doc->karpardaz_name,
                fa_money($doc->total_amount),
                fa_money($doc->total_tax),
                fa_money($doc->total_insurance),
                fa_money($doc->grandTotal()),
                $doc->isFinal() ? 'قطعی' : 'پیش‌نویس',
            ];
            $exportRows[] = [
                $doc->document_number ?? '—',
                jdate($doc->document_date),
                $doc->branch?->name ?? '—',
                $doc->karpardaz_name,
                $doc->total_amount,
                $doc->total_tax,
                $doc->total_insurance,
                $doc->grandTotal(),
                $doc->isFinal() ? 'قطعی' : 'پیش‌نویس',
            ];
        }

        $sumA = $docs->sum('total_amount');
        $sumT = $docs->sum('total_tax');
        $sumI = $docs->sum('total_insurance');

        return [
            'type' => 'documents',
            'title' => self::TYPES['documents'],
            'headings' => ['شماره سند', 'تاریخ', 'شعبه', 'کارپرداز', 'مبلغ (ریال)', 'مالیات (ریال)', 'بیمه (ریال)', 'جمع کل (ریال)', 'وضعیت'],
            'rows' => $rows,
            'exportRows' => $exportRows,
            'totalsRow' => ['جمع ('.fa($docs->count()).' سند)', '', '', '', fa_money($sumA), fa_money($sumT), fa_money($sumI), fa_money($sumA + $sumT + $sumI), ''],
        ];
    }

    /** گزارش روزانه/ماهانه/سالانه بر اساس اسناد قطعی */
    private function periodic(?string $from, ?string $to, ?int $branchId, string $mode): array
    {
        $daily = $this->finalQuery($from, $to, $branchId)
            ->selectRaw('document_date, COUNT(*) AS c, COALESCE(SUM(total_amount),0) AS a, COALESCE(SUM(total_tax),0) AS t, COALESCE(SUM(total_insurance),0) AS i')
            ->groupBy('document_date')
            ->orderBy('document_date')
            ->get();

        $buckets = [];
        foreach ($daily as $day) {
            [$key, $label] = match ($mode) {
                'daily' => [$day->document_date->format('Y-m-d'), jdate_fa($day->document_date)],
                'monthly' => [jdate($day->document_date, 'Y/m'), fa(Verta::instance($day->document_date)->format('F Y'))],
                default => [jdate($day->document_date, 'Y'), fa(jdate($day->document_date, 'Y'))],
            };

            $buckets[$key]['label'] = $label;
            $buckets[$key]['c'] = ($buckets[$key]['c'] ?? 0) + $day->c;
            $buckets[$key]['a'] = ($buckets[$key]['a'] ?? 0) + $day->a;
            $buckets[$key]['t'] = ($buckets[$key]['t'] ?? 0) + $day->t;
            $buckets[$key]['i'] = ($buckets[$key]['i'] ?? 0) + $day->i;
        }

        $rows = [];
        $exportRows = [];
        foreach ($buckets as $bucket) {
            $grand = $bucket['a'] + $bucket['t'] + $bucket['i'];
            $rows[] = [$bucket['label'], fa($bucket['c']), fa_money($bucket['a']), fa_money($bucket['t']), fa_money($bucket['i']), fa_money($grand)];
            $exportRows[] = [$bucket['label'], $bucket['c'], $bucket['a'], $bucket['t'], $bucket['i'], $grand];
        }

        $sumA = collect($buckets)->sum('a');
        $sumT = collect($buckets)->sum('t');
        $sumI = collect($buckets)->sum('i');
        $sumC = collect($buckets)->sum('c');

        return [
            'type' => $mode,
            'title' => self::TYPES[$mode].' (اسناد قطعی)',
            'headings' => ['دوره', 'تعداد اسناد', 'مبلغ (ریال)', 'مالیات (ریال)', 'بیمه (ریال)', 'جمع کل (ریال)'],
            'rows' => $rows,
            'exportRows' => $exportRows,
            'totalsRow' => ['جمع', fa($sumC), fa_money($sumA), fa_money($sumT), fa_money($sumI), fa_money($sumA + $sumT + $sumI)],
        ];
    }

    private function byDescription(?string $from, ?string $to, ?int $branchId): array
    {
        $rows = ExpenseItem::query()
            ->join('expense_documents', 'expense_items.expense_document_id', '=', 'expense_documents.id')
            ->where('expense_documents.status', ExpenseDocument::STATUS_FINAL)
            ->when(! auth()->user()->isAdmin(), fn ($q) => $q->whereIn('expense_documents.branch_id', auth()->user()->branches()->select('branches.id')))
            ->when($from, fn ($q) => $q->whereDate('expense_documents.document_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('expense_documents.document_date', '<=', $to))
            ->when($branchId, fn ($q) => $q->where('expense_documents.branch_id', $branchId))
            ->groupBy('expense_items.description')
            ->selectRaw('expense_items.description, COUNT(*) AS c, COALESCE(SUM(expense_items.amount),0) AS a, COALESCE(SUM(expense_items.tax),0) AS t, COALESCE(SUM(expense_items.insurance),0) AS i')
            ->orderByDesc('a')
            ->get();

        $out = [];
        $export = [];
        foreach ($rows as $r) {
            $grand = $r->a + $r->t + $r->i;
            $out[] = [$r->description, fa($r->c), fa_money($r->a), fa_money($r->t), fa_money($r->i), fa_money($grand)];
            $export[] = [$r->description, $r->c, $r->a, $r->t, $r->i, $grand];
        }

        $sumA = $rows->sum('a');
        $sumT = $rows->sum('t');
        $sumI = $rows->sum('i');

        return [
            'type' => 'by_description',
            'title' => self::TYPES['by_description'].' (اسناد قطعی)',
            'headings' => ['شرح هزینه', 'تعداد ردیف', 'مبلغ (ریال)', 'مالیات (ریال)', 'بیمه (ریال)', 'جمع کل (ریال)'],
            'rows' => $out,
            'exportRows' => $export,
            'totalsRow' => ['جمع', fa($rows->sum('c')), fa_money($sumA), fa_money($sumT), fa_money($sumI), fa_money($sumA + $sumT + $sumI)],
        ];
    }

    private function taxInsurance(?string $from, ?string $to, ?int $branchId): array
    {
        $docs = $this->finalQuery($from, $to, $branchId)
            ->with('branch')
            ->orderBy('document_date')
            ->get();

        $rows = [];
        $export = [];
        foreach ($docs as $doc) {
            $rows[] = [
                fa($doc->document_number),
                jdate_fa($doc->document_date),
                $doc->branch?->name ?? '—',
                fa_money($doc->total_tax),
                fa_money($doc->total_insurance),
                fa_money($doc->total_tax + $doc->total_insurance),
            ];
            $export[] = [
                $doc->document_number,
                jdate($doc->document_date),
                $doc->branch?->name ?? '—',
                $doc->total_tax,
                $doc->total_insurance,
                $doc->total_tax + $doc->total_insurance,
            ];
        }

        $sumT = $docs->sum('total_tax');
        $sumI = $docs->sum('total_insurance');

        return [
            'type' => 'tax_insurance',
            'title' => self::TYPES['tax_insurance'].' (اسناد قطعی)',
            'headings' => ['شماره سند', 'تاریخ', 'شعبه', 'مالیات (ریال)', 'بیمه (ریال)', 'جمع (ریال)'],
            'rows' => $rows,
            'exportRows' => $export,
            'totalsRow' => ['جمع ('.fa($docs->count()).' سند)', '', '', fa_money($sumT), fa_money($sumI), fa_money($sumT + $sumI)],
        ];
    }

    /** اسنادی که پیش‌نویس‌اند یا اطلاعات تکمیلی ندارند */
    private function incomplete(): array
    {
        $docs = ExpenseDocument::with('branch')
            ->when(! auth()->user()->isAdmin(), fn ($q) => $q->whereIn('branch_id', auth()->user()->branches()->select('branches.id')))
            ->where(function ($q) {
                $q->where('status', ExpenseDocument::STATUS_DRAFT)
                    ->orWhereNull('description')
                    ->orWhere('description', '')
                    ->orWhereNull('attachments_pages');
            })
            ->orderByDesc('id')
            ->get();

        $rows = [];
        $export = [];
        foreach ($docs as $doc) {
            $missing = [];
            if ($doc->isDraft()) {
                $missing[] = 'قطعی نشده';
            }
            if (! $doc->description) {
                $missing[] = 'بدون توضیحات';
            }
            if ($doc->attachments_pages === null) {
                $missing[] = 'تعداد برگ ثبت نشده';
            }

            $rows[] = [
                $doc->document_number ? fa($doc->document_number) : '—',
                jdate_fa($doc->document_date),
                $doc->branch?->name ?? '—',
                $doc->karpardaz_name,
                $doc->isFinal() ? 'قطعی' : 'پیش‌نویس',
                implode('، ', $missing),
            ];
            $export[] = [
                $doc->document_number ?? '—',
                jdate($doc->document_date),
                $doc->branch?->name ?? '—',
                $doc->karpardaz_name,
                $doc->isFinal() ? 'قطعی' : 'پیش‌نویس',
                implode('، ', $missing),
            ];
        }

        return [
            'type' => 'incomplete',
            'title' => self::TYPES['incomplete'],
            'headings' => ['شماره سند', 'تاریخ', 'شعبه', 'کارپرداز', 'وضعیت', 'نواقص'],
            'rows' => $rows,
            'exportRows' => $export,
            'totalsRow' => null,
        ];
    }
}
