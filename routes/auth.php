<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\TwoFactorController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('register.store');
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::get('two-factor/challenge', [TwoFactorController::class, 'create'])
    ->middleware('throttle:6,1')
    ->name('two-factor.challenge');
Route::post('two-factor/challenge', [TwoFactorController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('two-factor.store');
Route::post('two-factor/resend', [TwoFactorController::class, 'resend'])
    ->middleware('throttle:3,1')
    ->name('two-factor.resend');

Route::get('email/verify', [EmailVerificationController::class, 'create'])
    ->middleware('auth')
    ->name('verification.notice');
Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['auth', 'signed', 'throttle:6,1'])
    ->name('verification.verify');
Route::post('email/verification-notification', [EmailVerificationController::class, 'store'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.send');
