<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->middleware('throttle:5,1');
});

Route::post('logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('tankhah', [\App\Http\Controllers\TankhahController::class, 'edit'])->name('tankhah.edit');
    Route::put('tankhah', [\App\Http\Controllers\TankhahController::class, 'update'])->name('tankhah.update');

    Route::get('documents/{document}/print', [DocumentController::class, 'print'])->name('documents.print');
    Route::resource('documents', DocumentController::class);

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', Admin\UserController::class)->except(['show', 'destroy']);
        Route::post('users/{user}/toggle', [Admin\UserController::class, 'toggle'])->name('users.toggle');

        Route::resource('branches', Admin\BranchController::class)->except(['show', 'create', 'edit']);
        Route::post('branches/{branch}/default', [Admin\BranchController::class, 'setDefault'])->name('branches.default');

        Route::get('settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [Admin\SettingController::class, 'update'])->name('settings.update');

        Route::get('audit-logs', [Admin\AuditLogController::class, 'index'])->name('audit.index');

        Route::get('backup', [Admin\BackupController::class, 'index'])->name('backup.index');
        Route::post('backup/download', [Admin\BackupController::class, 'download'])->name('backup.download');
    });
});
