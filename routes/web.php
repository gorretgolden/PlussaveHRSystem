<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\RolePermissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('permission:view-employee-file')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
    });

    Route::get('/leave', [LeaveController::class, 'index'])->name('leave.index');
    Route::middleware('permission:apply-leave')->group(function () {
        Route::get('/leave/apply', [LeaveController::class, 'create'])->name('leave.create');
        Route::post('/leave', [LeaveController::class, 'store'])->name('leave.store');
    });

    Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::get('/approvals/{hrRequest}', [ApprovalController::class, 'show'])->name('approvals.show');
    Route::post('/approvals/{hrRequest}/act', [ApprovalController::class, 'act'])->name('approvals.act');
    Route::post('/approvals/{hrRequest}/resubmit', [ApprovalController::class, 'resubmit'])->name('approvals.resubmit');

    Route::post('/notifications/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    Route::get('/admin/roles', [RolePermissionController::class, 'index'])
        ->middleware('role:hr|md|admin')->name('admin.roles');
    Route::put('/admin/roles/{roleCode}', [RolePermissionController::class, 'update'])
        ->middleware('role:hr|md|admin')->name('admin.roles.update');

    Route::get('/admin/audit', [AuditController::class, 'index'])
        ->middleware('permission:audit-trail')->name('admin.audit');

    Route::get('/password/change', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password/change', [PasswordController::class, 'update'])->name('password.update');
});
