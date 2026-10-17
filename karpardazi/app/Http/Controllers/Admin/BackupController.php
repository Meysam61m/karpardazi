<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class BackupController extends Controller
{
    public function index()
    {
        return view('admin.backup.index', [
            'driver' => config('database.default'),
            'database' => config('database.connections.'.config('database.default').'.database'),
        ]);
    }

    public function download()
    {
        if (config('database.default') !== 'mysql') {
            return back()->with('error', 'پشتیبان‌گیری خودکار فقط با پایگاه داده MySQL/MariaDB در دسترس است.');
        }

        $db = config('database.connections.mysql');
        $file = storage_path('app/karpardazi-backup-'.now()->format('Ymd-His').'.sql');

        $process = new Process([
            'mysqldump',
            '--host='.($db['host'] ?? '127.0.0.1'),
            '--port='.($db['port'] ?? '3306'),
            '--user='.$db['username'],
            '--single-transaction',
            '--quick',
            '--no-tablespaces',
            '--result-file='.$file,
            $db['database'],
        ], env: ['MYSQL_PWD' => (string) ($db['password'] ?? '')]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful() || ! is_file($file) || filesize($file) === 0) {
            @unlink($file);

            return back()->with('error', 'پشتیبان‌گیری ناموفق بود. اجرابودن mysqldump روی سرور را بررسی کنید.');
        }

        AuditLog::record('backup_download');

        return response()->download($file, basename($file))->deleteFileAfterSend();
    }

    public function restore(Request $request)
    {
        abort(404);
    }
}
