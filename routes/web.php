<?php

use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\QrCodeController;
use App\Models\IssuedUid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome'));

Route::middleware([
    'auth',
])->group(function () {
    Route::get('dashboard', function (Request $request) {
        if ($request->user()?->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('admin/dashboard', function () {
        return Inertia::render('AdminDashboard', [
            'totalGenerated' => IssuedUid::count(),
        ]);
    })->middleware('admin')->name('admin.dashboard');

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

    Route::get('inventory', [InventoryController::class, 'index'])
        ->name('inventory');

    Route::get('inventory/lookup/{uniqueId}', [InventoryController::class, 'lookup'])
        ->name('inventory.lookup');

    Route::get('inventory/{uniqueId}', [InventoryController::class, 'show'])
        ->name('inventory.show');

    Route::post('inventory/{uniqueId}/archive', [InventoryController::class, 'archive'])
        ->middleware('admin')
        ->name('inventory.archive');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
