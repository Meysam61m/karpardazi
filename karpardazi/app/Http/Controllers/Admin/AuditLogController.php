<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\Jalali;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->latest('id');

        if ($userId = $request->integer('user_id')) {
            $query->where('user_id', $userId);
        }
        if ($event = trim((string) $request->input('event'))) {
            $query->where('event', $event);
        }
        if ($from = Jalali::toGregorian($request->input('from'))) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = Jalali::toGregorian($request->input('to'))) {
            $query->whereDate('created_at', '<=', $to);
        }

        return view('admin.audit.index', [
            'logs' => $query->paginate(30)->withQueryString(),
            'users' => User::orderBy('name')->get(),
            'events' => AuditLog::select('event')->distinct()->orderBy('event')->pluck('event'),
        ]);
    }
}
