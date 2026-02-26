<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\NatureOfRequestController;
use App\Http\Controllers\Admin\ProfileSlideController;
use App\Http\Controllers\Admin\StatusManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\NatureOfRequestOptionsController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\ReferenceValueOptionsController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\TicketRequestController;
use App\Models\IssuedUid;
use App\Models\ProfileSlide;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

Route::get('/', function () {
    $profileSlides = ProfileSlide::query()
        ->notArchived()
        ->active()
        ->ordered()
        ->get()
        ->map(function (ProfileSlide $slide) {
            $disk = Storage::disk('public');

            return [
                'id' => $slide->id,
                'imageUrl' => $disk->exists($slide->image_path) ? '/storage/'.$slide->image_path : null,
                'title' => $slide->title,
                'subtitle' => $slide->subtitle,
                'textPosition' => $slide->text_position->value,
            ];
        })
        ->values()
        ->all();

    return Inertia::render('public/Welcome', [
        'canRegister' => false, // Registration is disabled for this application
        'profileSlides' => $profileSlides,
    ]);
});

Route::middleware([
    'auth',
    'verified',
    'active',
])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('admin/dashboard', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])
        ->middleware('admin')
        ->name('admin.dashboard');

    Route::middleware(['admin', 'two-factor-verified:admin'])->group(function () {
        Route::get('admin/users', [UserManagementController::class, 'index'])
            ->name('admin.users.index');
        Route::get('admin/users/{user}', [UserManagementController::class, 'show'])
            ->name('admin.users.show');
        Route::match(['post', 'patch'], 'admin/users/{user}', [UserManagementController::class, 'update'])
            ->name('admin.users.update');
        Route::match(['post', 'patch'], 'admin/users/{user}/work', [UserManagementController::class, 'updateWork'])
            ->name('admin.users.work');
        Route::match(['post', 'patch'], 'admin/users/{user}/role', [UserManagementController::class, 'updateRole'])
            ->name('admin.users.role');
        Route::match(['post', 'patch'], 'admin/users/{user}/status', [UserManagementController::class, 'updateStatus'])
            ->name('admin.users.status');
        Route::match(['post', 'patch'], 'admin/users/{user}/password', [UserManagementController::class, 'updatePassword'])
            ->name('admin.users.password');
        Route::post('admin/users/{user}/verify-email', [UserManagementController::class, 'forceVerifyEmail'])
            ->name('admin.users.verify-email');

        Route::get('admin/audit-logs', [AuditLogController::class, 'index'])
            ->name('admin.audit-logs.index');

        Route::get('admin/dashboard/archive-export', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'exportArchived'])
            ->name('admin.dashboard.archive-export');
    });

    Route::get('admin/enrollments/create', [EnrollmentController::class, 'create'])
        ->middleware('admin')
        ->name('admin.enrollments.create');

    Route::get('admin/nature-of-request', [NatureOfRequestController::class, 'index'])
        ->middleware('admin')
        ->name('admin.nature-of-requests.index');
    Route::post('admin/nature-of-request', [NatureOfRequestController::class, 'store'])
        ->middleware('admin')
        ->name('admin.nature-of-requests.store');
    Route::patch('admin/nature-of-request/{natureOfRequest}', [NatureOfRequestController::class, 'update'])
        ->middleware('admin')
        ->name('admin.nature-of-requests.update');
    Route::delete('admin/nature-of-request/{natureOfRequest}', [NatureOfRequestController::class, 'destroy'])
        ->middleware('admin')
        ->name('admin.nature-of-requests.destroy');

    // Remarks, Office Designation, Category, Status: both admin and super_admin may access and CRUD.
    Route::get('admin/status', [StatusManagementController::class, 'index'])
        ->middleware('admin')
        ->name('admin.status.index');
    Route::post('admin/status', [StatusManagementController::class, 'store'])
        ->middleware('admin')
        ->name('admin.status.store');
    Route::get('admin/status/{referenceValue}', fn () => redirect()->route('admin.status.index'))
        ->middleware('admin')
        ->name('admin.status.show');
    Route::patch('admin/status/{referenceValue}', [StatusManagementController::class, 'update'])
        ->middleware('admin')
        ->name('admin.status.update');
    Route::delete('admin/status/{referenceValue}', [StatusManagementController::class, 'destroy'])
        ->middleware('admin')
        ->name('admin.status.destroy');

    Route::get('inventory/{uniqueId}/edit', [EnrollmentController::class, 'edit'])
        ->middleware('admin')
        ->name('inventory.edit');

    Route::post('admin/enrollments', [EnrollmentController::class, 'store'])
        ->middleware('admin')
        ->name('admin.enrollments.store');

    Route::put('admin/enrollments/{uniqueId}', [EnrollmentController::class, 'update'])
        ->middleware('admin')
        ->name('admin.enrollments.update');

    Route::middleware(['super_admin'])->prefix('admin/posts')->name('admin.posts.')->group(function () {
        Route::get('/', [ProfileSlideController::class, 'index'])->name('index');
        Route::get('/create', [ProfileSlideController::class, 'create'])->name('create');
        Route::post('/', [ProfileSlideController::class, 'store'])->name('store');
        Route::get('/{profileSlide}/edit', [ProfileSlideController::class, 'edit'])->name('edit');
        Route::patch('/{profileSlide}/archive', [ProfileSlideController::class, 'archive'])->name('archive');
        Route::patch('/{profileSlide}', [ProfileSlideController::class, 'update'])->name('update');
        Route::delete('/{profileSlide}', [ProfileSlideController::class, 'destroy'])->name('destroy');
    });

    Route::get('admin/qr-generator', function () {
        $nextStart = (IssuedUid::max('sequence') ?? 0) + 1;

        return Inertia::render('admin/AdminQrGenerator', [
            'nextStart' => $nextStart,
            'totalGenerated' => IssuedUid::count(),
        ]);
    })->middleware('admin')->name('admin.qr-generator');
    Route::post('admin/qr-generator/batch', [QrCodeController::class, 'store'])
        ->middleware('admin')
        ->name('admin.qr-generator.batch');
    Route::get('admin/qr-generator/batches', [QrCodeController::class, 'index'])
        ->middleware('admin')
        ->name('admin.qr-generator.batches.index');
    Route::get('admin/qr-generator/batches/{batch}', [QrCodeController::class, 'show'])
        ->middleware('admin')
        ->name('admin.qr-generator.batches.show');
    Route::get('admin/qr-generator/validate/{uid}', [QrCodeController::class, 'validateUid'])
        ->middleware('admin')
        ->name('admin.qr-generator.validate');

    Route::get('requests', [TicketRequestController::class, 'index'])
        ->name('requests');
    Route::get('requests/archive', [TicketRequestController::class, 'archive'])
        ->name('requests.archive');
    Route::get('requests/it-governance', fn () => Inertia::render('requests/ItGovernanceRequest'))
        ->name('requests.it-governance');
    Route::get('requests/{ticketRequest}/it-governance', [TicketRequestController::class, 'itGovernance'])
        ->middleware('admin')
        ->name('requests.it-governance.show');
    Route::patch('requests/{ticketRequest}/it-governance', [TicketRequestController::class, 'updateItGovernance'])
        ->middleware('admin')
        ->name('requests.it-governance.update');
    Route::post('requests/{ticketRequest}/it-governance/generate-qr', [TicketRequestController::class, 'generateQrForRequest'])
        ->middleware('admin')
        ->name('requests.it-governance.generate-qr');
    Route::get('requests/equipment-and-network', fn () => redirect()->route('requests'))
        ->name('requests.equipment-network');
    Route::get('requests/system-error-bug-report', fn () => Inertia::render('requests/System_error_bug_report_Form'))
        ->name('requests.system-error-bug-report');
    Route::get('requests/{ticketRequest}/equipment-and-network', [TicketRequestController::class, 'equipmentAndNetwork'])
        ->name('requests.equipment-network.show');
    Route::match(['post', 'patch'], 'requests/{ticketRequest}/equipment-and-network', [TicketRequestController::class, 'updateEquipmentAndNetwork'])
        ->name('requests.equipment-network.update');

    Route::get('nature-of-request/options', NatureOfRequestOptionsController::class)
        ->name('nature-of-request.options');
    Route::get('reference-values/options', ReferenceValueOptionsController::class)
        ->name('reference-values.options');

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

    Route::get('submit-request', [TicketRequestController::class, 'create'])
        ->name('submit-request');
    Route::post('submit-request', [TicketRequestController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('submit-request.store');
    Route::get('requests/{ticketRequest}', [TicketRequestController::class, 'show'])
        ->name('requests.show');

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
