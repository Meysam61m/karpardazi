<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index()
    {
        return view('admin.branches.index', [
            'branches' => Branch::withCount('documents')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateBranch($request);

        $branch = Branch::create($data);
        AuditLog::record('branch_create', $branch, null, $data);

        return back()->with('success', 'شعبه «'.$branch->name.'» اضافه شد.');
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $this->validateBranch($request, $branch->id);

        $old = $branch->only(['name', 'code']);
        $branch->update($data);
        AuditLog::record('branch_update', $branch, $old, $data);

        return back()->with('success', 'شعبه به‌روزرسانی شد.');
    }

    public function destroy(Branch $branch)
    {
        if ($branch->documents()->exists()) {
            return back()->with('error', 'برای این شعبه سند ثبت شده است و قابل حذف نیست؛ می‌توانید آن را ویرایش کنید.');
        }

        $branch->delete();
        AuditLog::record('branch_delete', null, $branch->only(['name', 'code']), null);

        return back()->with('success', 'شعبه حذف شد.');
    }

    public function setDefault(Branch $branch)
    {
        DB::transaction(function () use ($branch) {
            Branch::query()->update(['is_default' => false]);
            $branch->update(['is_default' => true]);
        });

        AuditLog::record('branch_set_default', $branch);

        return back()->with('success', 'شعبه «'.$branch->name.'» به‌عنوان پیش‌فرض تنظیم شد.');
    }

    private function validateBranch(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('branches', 'name')->ignore($ignoreId)],
            'code' => ['nullable', 'string', 'max:20'],
        ], [
            'required' => 'وارد کردن :attribute الزامی است.',
            'unique' => 'این :attribute قبلاً ثبت شده است.',
        ], [
            'name' => 'نام شعبه',
            'code' => 'کد شعبه',
        ]);
    }
}
