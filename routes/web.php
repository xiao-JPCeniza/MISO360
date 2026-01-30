<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\ScanController;
use App\Models\IssuedUid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome', [
    'canRegister' => false, // Registration is disabled for this application
]));

Route::middleware([
    'auth',
    'active',
])->group(function () {
    Route::get('dashboard', function (Request $request) {
        if ($request->user()?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('admin/dashboard', function () {
        return Inertia::render('AdminDashboard', [
            'totalGenerated' => IssuedUid::count(),
        ]);
    })->middleware('admin')->name('admin.dashboard');

    Route::middleware(['admin', 'two-factor-verified:admin'])->group(function () {
        Route::get('admin/users', [UserManagementController::class, 'index'])
            ->name('admin.users.index');
        Route::get('admin/users/{user}', [UserManagementController::class, 'show'])
            ->name('admin.users.show');
        Route::patch('admin/users/{user}', [UserManagementController::class, 'update'])
            ->name('admin.users.update');
        Route::patch('admin/users/{user}/role', [UserManagementController::class, 'updateRole'])
            ->name('admin.users.role');
        Route::patch('admin/users/{user}/status', [UserManagementController::class, 'updateStatus'])
            ->name('admin.users.status');
        Route::patch('admin/users/{user}/password', [UserManagementController::class, 'updatePassword'])
            ->name('admin.users.password');

        Route::get('admin/audit-logs', [AuditLogController::class, 'index'])
            ->name('admin.audit-logs.index');
    });

    Route::get('admin/enrollments/create', [EnrollmentController::class, 'create'])
        ->middleware('admin')
        ->name('admin.enrollments.create');

    Route::get('inventory/{uniqueId}/edit', [EnrollmentController::class, 'edit'])
        ->middleware('admin')
        ->name('inventory.edit');

    Route::post('admin/enrollments', [EnrollmentController::class, 'store'])
        ->middleware('admin')
        ->name('admin.enrollments.store');

    Route::put('admin/enrollments/{uniqueId}', [EnrollmentController::class, 'update'])
        ->middleware('admin')
        ->name('admin.enrollments.update');

    Route::get('admin/qr-generator', function () {
        $nextStart = (IssuedUid::max('sequence') ?? 0) + 1;

        return Inertia::render('AdminQrGenerator', [
            'nextStart' => $nextStart,
            'totalGenerated' => IssuedUid::count(),
        ]);
    })->middleware('admin')->name('admin.qr-generator');
    Route::post('admin/qr-generator/batch', [QrCodeController::class, 'store'])
        ->middleware('admin')
        ->name('admin.qr-generator.batch');
    Route::get('admin/qr-generator/validate/{uid}', [QrCodeController::class, 'validateUid'])
        ->middleware('admin')
        ->name('admin.qr-generator.validate');

    Route::get('requests', function () {
        return Inertia::render('Requests');
    })->name('requests');

    Route::get('scan', [ScanController::class, 'index'])
        ->name('scan.index');
    Route::get('scan/lookup/{uniqueId}', [ScanController::class, 'lookup'])
        ->name('scan.lookup');
    Route::get('scan/{uniqueId}', [ScanController::class, 'show'])
        ->name('scan.show');
    Route::post('scan/{uniqueId}/review', [ScanController::class, 'review'])
        ->name('scan.review');
    Route::put('scan/{uniqueId}/assign', [ScanController::class, 'assign'])
        ->name('scan.assign');

    Route::get('submit-request', function () {
        return Inertia::render('SubmitRequest');
    })->name('submit-request');

    Route::get('inventory', [InventoryController::class, 'index'])
        ->middleware('admin')
        ->name('inventory');

    Route::get('inventory/lookup/{uniqueId}', [InventoryController::class, 'lookup'])
        ->middleware('admin')
        ->name('inventory.lookup');

    Route::get('inventory/{uniqueId}', [InventoryController::class, 'show'])
        ->middleware('admin')
        ->name('inventory.show');

    Route::post('inventory/{uniqueId}/archive', [InventoryController::class, 'archive'])
        ->middleware('admin')
        ->name('inventory.archive');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
