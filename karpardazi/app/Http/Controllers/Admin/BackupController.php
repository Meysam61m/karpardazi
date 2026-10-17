<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        if (! in_array(config('database.default'), ['mysql', 'mariadb'], true)) {
            return back()->with('error', 'تهیه پشتیبان فقط با پایگاه داده MySQL/MariaDB در دسترس است.');
        }

        $file = storage_path('app/karpardazi-backup-'.now()->format('Ymd-His').'.sql');
        $handle = @fopen($file, 'wb');
        if (! $handle) {
            return back()->with('error', 'امکان ایجاد فایل پشتیبان وجود ندارد؛ دسترسی نوشتن پوشه storage را بررسی کنید.');
        }

        try {
            $pdo = DB::connection()->getPdo();
            fwrite($handle, "-- Karpardazi database backup\nSET FOREIGN_KEY_CHECKS=0;\n\n");

            $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
            while ($tableRow = $tables->fetch(\PDO::FETCH_NUM)) {
                $table = (string) $tableRow[0];
                $quotedTable = '`'.str_replace('`', '``', $table).'`';
                $createRow = $pdo->query("SHOW CREATE TABLE {$quotedTable}")->fetch(\PDO::FETCH_NUM);
                if (! isset($createRow[1]) || fwrite($handle, "DROP TABLE IF EXISTS {$quotedTable};\n{$createRow[1]};\n") === false) {
                    throw new \RuntimeException("Could not write schema for table {$table}");
                }

                $rows = $pdo->query("SELECT * FROM {$quotedTable}");
                $columnCount = $rows->columnCount();
                while ($row = $rows->fetch(\PDO::FETCH_NUM)) {
                    $values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } else {
                            $quoted = $pdo->quote((string) $value);
                            if ($quoted === false) {
                                throw new \RuntimeException("Could not quote data from table {$table}");
                            }
                            $values[] = $quoted;
                        }
                    }
                    if (count($values) !== $columnCount || fwrite($handle, "INSERT INTO {$quotedTable} VALUES (".implode(', ', $values).");\n") === false) {
                        throw new \RuntimeException("Could not write data for table {$table}");
                    }
                }
                fwrite($handle, "\n");
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($handle);
        } catch (\Throwable $exception) {
            fclose($handle);
            @unlink($file);
            Log::error('Karpardazi database backup failed', ['exception' => $exception]);

            return back()->with('error', 'پشتیبان‌گیری ناموفق بود. گزارش خطا در لاگ سرور ثبت شد؛ دسترسی پایگاه داده و فضای ذخیره‌سازی را بررسی کنید.');
        }

        if (! is_file($file) || filesize($file) === 0) {
            @unlink($file);

            return back()->with('error', 'فایل پشتیبان خالی ایجاد شد؛ گزارش خطا در لاگ سرور را بررسی کنید.');
        }

        AuditLog::record('backup_download');

        return response()->download($file, basename($file))->deleteFileAfterSend();
    }

    public function restore(Request $request)
    {
        abort(404);
    }
}
