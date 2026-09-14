<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // Registrasi mandiri dinonaktifkan: akses dikelola GATE.

    // Route::get('login', [AuthenticatedSessionController::class, 'create'])
    //     ->name('login');

    Route::get('login', function () {
        if (config('auth.mode') === 'sso') {
            return redirect()->route('sso.redirect');
        }

        return app(AuthenticatedSessionController::class)->create();
    })->middleware('guest')->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Lupa/reset password mandiri dinonaktifkan: akses dikelola GATE.
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    // Ubah password mandiri dinonaktifkan: akses dikelola GATE.

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('auth.logout');
});
