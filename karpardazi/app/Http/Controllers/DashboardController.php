<?php

namespace App\Http\Controllers;

use App\Models\ExpenseDocument;
use App\Models\User;
use Hekmatinasser\Verta\Verta;

class DashboardController extends Controller
{
    public function index()
    {
        $allowedBranches = auth()->user()->branches()->select('branches.id');
        $scope = fn ($query) => auth()->user()->isAdmin() ? $query : $query->whereIn('branch_id', $allowedBranches);
        $final = $scope(ExpenseDocument::where('status', ExpenseDocument::STATUS_FINAL));

        $stats = [
            'docs_total' => $scope(ExpenseDocument::query())->count(),
            'docs_final' => (clone $final)->count(),
            'docs_draft' => $scope(ExpenseDocument::where('status', ExpenseDocument::STATUS_DRAFT))->count(),
            'sum_amount' => (int) (clone $final)->sum('total_amount'),
            'sum_tax' => (int) (clone $final)->sum('total_tax'),
            'sum_insurance' => (int) (clone $final)->sum('total_insurance'),
        ];
        $stats['grand'] = $stats['sum_amount'] + $stats['sum_tax'] + $stats['sum_insurance'];

        // اسناد ماه جاری شمسی
        $monthStart = Verta::now()->startMonth()->datetime()->format('Y-m-d');
        $monthEnd = Verta::now()->endMonth()->datetime()->format('Y-m-d');
        $monthQuery = (clone $final)->whereBetween('document_date', [$monthStart, $monthEnd]);
        $stats['month_count'] = (clone $monthQuery)->count();
        $stats['month_sum'] = (int) (clone $monthQuery)
            ->selectRaw('COALESCE(SUM(total_amount + total_tax + total_insurance), 0) AS s')
            ->value('s');

        // نمودار هزینه‌ها به تفکیک ماه (۶ ماه اخیر)
        $chart = ['labels' => [], 'data' => []];
        for ($i = 5; $i >= 0; $i--) {
            $v = Verta::now()->subMonths($i);
            $from = $v->copy()->startMonth()->datetime()->format('Y-m-d');
            $to = $v->copy()->endMonth()->datetime()->format('Y-m-d');
            $chart['labels'][] = fa($v->format('F Y'));
            $chart['data'][] = (int) $scope(ExpenseDocument::where('status', ExpenseDocument::STATUS_FINAL))
                ->whereBetween('document_date', [$from, $to])
                ->selectRaw('COALESCE(SUM(total_amount + total_tax + total_insurance), 0) AS s')
                ->value('s');
        }

        $latestQuery = ExpenseDocument::with(['branch', 'user'])->latest('id');
        $latest = $scope($latestQuery)->take(8)->get();

        $user = auth()->user();
        $tankhahCards = $user->isAdmin()
            ? User::where('role', User::ROLE_KARPARDAZ)->where('is_active', true)->whereNotNull('tankhah_amount')->get()
            : collect([$user])->filter(fn (User $u) => $u->tankhah_amount !== null)->values();

        return view('dashboard', compact('stats', 'chart', 'latest', 'tankhahCards'));
    }
}
