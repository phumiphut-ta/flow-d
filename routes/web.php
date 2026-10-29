<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdminRecoveryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RouteManagementController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Mobile LIFF Parcel Tracking (Public read-only)
Route::get('/liff/tracking', [TrackingController::class, 'index'])->name('liff.tracking');

// Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Authenticated Staff & Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    // Dual-Pane PC Desktop Intake & Worklist
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/intake', [DashboardController::class, 'intake'])->name('dashboard.intake');
    Route::post('/dashboard/movements/{movement}/checkout', [DashboardController::class, 'checkout'])->name('dashboard.checkout');
    Route::post('/dashboard/movements/{movement}/advance', [DashboardController::class, 'advance'])->name('dashboard.advance');
    Route::post('/dashboard/batch/checkout', [DashboardController::class, 'batchCheckout'])->name('dashboard.batch.checkout');
    Route::post('/dashboard/batch/advance', [DashboardController::class, 'batchAdvance'])->name('dashboard.batch.advance');
    Route::post('/dashboard/movements/{movement}/edit-doc-number', [DashboardController::class, 'editDocNumber'])->name('dashboard.movements.edit_doc_number');
    Route::post('/dashboard/movements/{movement}/void', [DashboardController::class, 'voidIntake'])->name('dashboard.movements.void');

    // Document History Ledger
    Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
    Route::get('/history/{document}', [HistoryController::class, 'show'])->name('history.show');

    // Periodic Summary Reports & A4 PDF Print (Q1-A, Q2-PDF A4, Q3-A)
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // User Profile & Self-Service Password
    Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile.show');
    Route::post('/profile/password', [AuthController::class, 'updatePassword'])->name('profile.password');

    /*
    |--------------------------------------------------------------------------
    | Admin Only Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        // Same-day Single Undo Recovery
        Route::post('/recovery/movements/{movement}/reopen', [AdminRecoveryController::class, 'reopen'])->name('recovery.reopen');
        Route::post('/recovery/movements/{movement}/rollback', [AdminRecoveryController::class, 'rollback'])->name('recovery.rollback');

        // Route Management
        Route::get('/routes', [RouteManagementController::class, 'index'])->name('routes.index');
        Route::post('/routes', [RouteManagementController::class, 'store'])->name('routes.store');
        Route::put('/routes/{route}', [RouteManagementController::class, 'update'])->name('routes.update');
        Route::post('/routes/{route}/toggle', [RouteManagementController::class, 'toggle'])->name('routes.toggle');

        // User Management
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset_password');
        Route::post('/users/{user}/toggle', [UserManagementController::class, 'toggle'])->name('users.toggle');

        // Audit & Activity Logs
        Route::get('/logs', [ActivityLogController::class, 'index'])->name('logs.index');
    });
});
