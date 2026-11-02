<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\ExpenseDocument;
use App\Services\DocumentNumberGenerator;
use App\Support\Jalali;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = ExpenseDocument::with(['branch', 'user'])->latest('id');
        if (! $request->user()->isAdmin()) {
            $query->whereIn('branch_id', $request->user()->branches()->select('branches.id'));
        }

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('document_number', 'like', "%{$search}%")
                    ->orWhere('karpardaz_name', 'like', "%{$search}%")
                    ->orWhereHas('items', fn ($i) => $i->where('description', 'like', "%{$search}%"));
            });
        }
        if ($branchId = $request->integer('branch_id')) {
            $query->where('branch_id', $branchId);
        }
        if (in_array($request->input('status'), ['draft', 'final'], true)) {
            $query->where('status', $request->input('status'));
        }
        if ($from = Jalali::toGregorian($request->input('from'))) {
            $query->whereDate('document_date', '>=', $from);
        }
        if ($to = Jalali::toGregorian($request->input('to'))) {
            $query->whereDate('document_date', '<=', $to);
        }

        // reorder(): حذف ORDER BY — در MySQL با ONLY_FULL_GROUP_BY ترکیب تجمیع + مرتب‌سازی خطا می‌دهد
        $sums = (clone $query)->reorder()->selectRaw(
            'COUNT(*) AS c, COALESCE(SUM(total_amount),0) AS a, COALESCE(SUM(total_tax),0) AS t, COALESCE(SUM(total_insurance),0) AS i'
        )->first();

        $documents = $query->paginate(15)->withQueryString();
        $branches = $request->user()->isAdmin() ? Branch::orderBy('id')->get() : $request->user()->branches()->get();

        return view('documents.index', compact('documents', 'branches', 'sums'));
    }

    public function create()
    {
        return view('documents.create', [
            'branches' => auth()->user()->isAdmin() ? Branch::orderBy('id')->get() : auth()->user()->branches()->get(),
            'defaultBranch' => auth()->user()->isAdmin() ? Branch::default() : auth()->user()->branches()->first(),
            'todayJalali' => jdate(now()),
        ]);
    }

    public function store(StoreDocumentRequest $request, DocumentNumberGenerator $numbers)
    {
        $data = $request->validated();
        $user = $request->user();
        abort_unless($user->canAccessBranch((int) $data['branch_id']), 403);

        $document = DB::transaction(function () use ($data, $request) {
            $document = ExpenseDocument::create([
                'branch_id' => $data['branch_id'],
                'user_id' => $request->user()->id,
                'karpardaz_name' => $data['karpardaz_name'],
                'document_date' => Jalali::toGregorian($data['document_date']),
                'tankhah_deposit_amount' => $data['tankhah_deposit_amount'],
                'tankhah_deposit_date' => Jalali::toGregorian($data['tankhah_deposit_date']),
                'description' => $data['description'] ?? null,
                'attachments_pages' => $data['attachments_pages'] ?? null,
                'status' => ExpenseDocument::STATUS_DRAFT,
            ]);

            $this->syncItems($document, $data['items']);
            $document->recalcTotals();
            $document->save();

            return $document;
        });

        AuditLog::record('document_create', $document, null, $this->snapshot($document->fresh('items')));

        if ($data['action'] === 'save_final') {
            $numbers->assign($document->refresh());
            AuditLog::record('document_finalize', $document, null, ['document_number' => $document->document_number]);

            return redirect()->route('documents.show', $document)
                ->with('success', 'سند با شماره «'.fa($document->document_number).'» قطعی ثبت شد.');
        }

        return redirect()->route('documents.show', $document)
            ->with('success', 'پیش‌نویس سند ذخیره شد. پس از کنترل می‌توانید آن را قطعی کنید.');
    }

    public function show(ExpenseDocument $document)
    {
        abort_unless(auth()->user()->canAccessBranch((int) $document->branch_id), 403);
        $document->load(['items', 'branch', 'user']);

        $logs = AuditLog::where('auditable_type', $document->getMorphClass())
            ->where('auditable_id', $document->id)
            ->with('user')
            ->latest('id')
            ->take(15)
            ->get();

        return view('documents.show', compact('document', 'logs'));
    }

    public function edit(ExpenseDocument $document)
    {
        abort_unless(auth()->user()->canAccessBranch((int) $document->branch_id), 403);
        $document->load('items');

        return view('documents.edit', [
            'document' => $document,
            'branches' => auth()->user()->isAdmin() ? Branch::orderBy('id')->get() : auth()->user()->branches()->get(),
        ]);
    }

    public function update(UpdateDocumentRequest $request, ExpenseDocument $document, DocumentNumberGenerator $numbers)
    {
        $data = $request->validated();
        abort_unless($request->user()->canAccessBranch((int) $document->branch_id)
            && $request->user()->canAccessBranch((int) $data['branch_id']), 403);
        $old = $this->snapshot($document->loadMissing('items'));

        DB::transaction(function () use ($document, $data) {
            $document->fill([
                'branch_id' => $data['branch_id'],
                'karpardaz_name' => $data['karpardaz_name'],
                'document_date' => Jalali::toGregorian($data['document_date']),
                'tankhah_deposit_amount' => $data['tankhah_deposit_amount'],
                'tankhah_deposit_date' => Jalali::toGregorian($data['tankhah_deposit_date']),
                'description' => $data['description'] ?? null,
                'attachments_pages' => $data['attachments_pages'] ?? null,
            ])->save();

            $this->syncItems($document, $data['items']);
            $document->recalcTotals();
            $document->save();
        });

        $document->refresh();
        AuditLog::record('document_update', $document, $old, $this->snapshot($document->loadMissing('items')));

        if ($document->isDraft() && $data['action'] === 'save_final') {
            $numbers->assign($document);
            AuditLog::record('document_finalize', $document, null, ['document_number' => $document->document_number]);

            return redirect()->route('documents.show', $document)
                ->with('success', 'سند با شماره «'.fa($document->document_number).'» قطعی ثبت شد.');
        }

        return redirect()->route('documents.show', $document)
            ->with('success', 'تغییرات سند ذخیره و در سوابق ثبت شد.');
    }

    public function destroy(ExpenseDocument $document)
    {
        abort_unless(auth()->user()->canAccessBranch((int) $document->branch_id), 403);
        if (! $document->canDelete()) {
            return back()->with('error', 'سند قطعی قابل حذف نیست. برای اصلاح، از «ویرایش» استفاده کنید تا سابقه تغییرات بماند.');
        }

        $snapshot = $this->snapshot($document->loadMissing('items'));
        $document->delete();
        AuditLog::record('document_delete', null, $snapshot, null);

        return redirect()->route('documents.index')->with('success', 'پیش‌نویس سند حذف شد.');
    }

    public function print(ExpenseDocument $document)
    {
        abort_unless(auth()->user()->canAccessBranch((int) $document->branch_id), 403);
        $document->load(['items', 'branch', 'user']);

        // اطلاعات واریز جزئی از خود سند است؛ سند ناقص قابل چاپ نیست.
        if (! $document->tankhah_deposit_date || ! $document->tankhah_deposit_amount) {
            return redirect()->route('documents.show', $document)
                ->with('error', 'چاپ سند ممکن نیست: مبلغ و تاریخ واریز تنخواه در این سند ثبت نشده است.');
        }

        return view('documents.print', compact('document'));
    }

    /** بازنویسی کامل اقلام سند (ردیف‌های خالی نادیده گرفته می‌شوند) */
    private function syncItems(ExpenseDocument $document, array $items): void
    {
        $document->items()->delete();

        $row = 0;
        foreach ($items as $item) {
            $item = (array) $item;
            $isUsed = ($item['description'] ?? '') !== ''
                || (int) ($item['amount'] ?? 0) > 0
                || (int) ($item['tax'] ?? 0) > 0
                || (int) ($item['insurance'] ?? 0) > 0
                || trim((string) ($item['expense_date'] ?? '')) !== '';
            if (! $isUsed) {
                continue;
            }

            $row++;
            $document->items()->create([
                'row_number' => $row,
                'expense_date' => Jalali::toGregorian($item['expense_date']),
                'description' => $item['description'],
                'amount' => (int) ($item['amount'] ?? 0),
                'tax' => (int) ($item['tax'] ?? 0),
                'insurance' => (int) ($item['insurance'] ?? 0),
            ]);
        }
    }

    /** تصویر لحظه‌ای سند برای ثبت در سوابق */
    private function snapshot(ExpenseDocument $document): array
    {
        return [
            'branch_id' => $document->branch_id,
            'karpardaz_name' => $document->karpardaz_name,
            'document_number' => $document->document_number,
            'document_date' => jdate($document->document_date),
            'tankhah_deposit_amount' => $document->tankhah_deposit_amount,
            'tankhah_deposit_date' => jdate($document->tankhah_deposit_date),
            'description' => $document->description,
            'attachments_pages' => $document->attachments_pages,
            'status' => $document->status,
            'total_amount' => $document->total_amount,
            'total_tax' => $document->total_tax,
            'total_insurance' => $document->total_insurance,
            'items' => $document->items->map(fn ($i) => [
                'row' => $i->row_number,
                'date' => jdate($i->expense_date),
                'description' => $i->description,
                'amount' => $i->amount,
                'tax' => $i->tax,
                'insurance' => $i->insurance,
            ])->all(),
        ];
    }
}
