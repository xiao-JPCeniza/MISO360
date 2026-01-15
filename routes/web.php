<?php

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
        return Inertia::render('AdminDashboard');
    })->middleware('admin')->name('admin.dashboard');

    Route::get('requests', function () {
        return Inertia::render('Requests');
    })->name('requests');

    Route::get('inventory', function () {
        return Inertia::render('Inventory');
    })->name('inventory');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
