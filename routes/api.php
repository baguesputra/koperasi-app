<?php

use App\Http\Controllers\API\AnggotaController;
use App\Http\Controllers\API\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->group(function () {
    // Guest routes (no authentication required, local mode only)
    Route::middleware('guest')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Authenticated routes (require Sanctum authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
        Route::get('/user', [AuthController::class, 'user']);

        // Self-scoped member data (from login token, no ID parameter)
        Route::get('/anggota/pinjaman-aktif', [AnggotaController::class, 'pinjamanAktif'])->name('api.anggota.pinjaman-aktif');
        Route::get('/anggota/riwayat-pinjaman', [AnggotaController::class, 'riwayatPinjaman'])->name('api.anggota.riwayat-pinjaman');
        Route::get('/anggota/limit', [AnggotaController::class, 'limit'])->name('api.anggota.limit');
        Route::get('/anggota/simpanan', [AnggotaController::class, 'simpanan'])->name('api.anggota.simpanan');
    });
});
