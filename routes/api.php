<?php

use App\Http\Controllers\API\AnggotaController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\LaporanController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\PengajuanLimitController;
use App\Http\Controllers\API\Pengurus\AngsuranController as PengurusAngsuranController;
use App\Http\Controllers\API\Pengurus\LimitController as PengurusLimitController;
use App\Http\Controllers\API\Pengurus\ListController as PengurusListController;
use App\Http\Controllers\API\Pengurus\PercepatanController as PengurusPercepatanController;
use App\Http\Controllers\API\Pengurus\PinjamanController as PengurusPinjamanController;
use App\Http\Controllers\API\Pengurus\SimpananController as PengurusSimpananController;
use App\Http\Controllers\API\PercepatanController;
use App\Http\Controllers\API\PinjamanController;
use App\Http\Controllers\API\RekeningController;
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
        Route::get('/me', [AuthController::class, 'me'])->name('api.me');

        Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('api.dashboard');
        Route::get('/pengurus/ringkasan', [DashboardController::class, 'ringkasan'])->name('api.pengurus.ringkasan');

        // Self-scoped member data (from login token, no ID parameter)
        Route::get('/anggota/pinjaman-aktif', [AnggotaController::class, 'pinjamanAktif'])->name('api.anggota.pinjaman-aktif');
        Route::get('/anggota/riwayat-pinjaman', [AnggotaController::class, 'riwayatPinjaman'])->name('api.anggota.riwayat-pinjaman');
        Route::get('/anggota/limit', [AnggotaController::class, 'limit'])->name('api.anggota.limit');
        Route::get('/anggota/simpanan', [AnggotaController::class, 'simpanan'])->name('api.anggota.simpanan');

        // Member loan application (batch 3: mobile contract)
        Route::middleware('idempotent')->group(function () {
            Route::post('/pinjaman/cek-nominal', [PinjamanController::class, 'cekNominal'])->name('api.pinjaman.cek-nominal');
            Route::post('/pinjaman/simulasi', [PinjamanController::class, 'simulasi'])->name('api.pinjaman.simulasi');
            Route::post('/pinjaman', [PinjamanController::class, 'store'])->name('api.pinjaman.store');
            Route::post('/rekening', [RekeningController::class, 'store'])->name('api.rekening.store');
            Route::put('/rekening/{id}/default', [RekeningController::class, 'setDefault'])->name('api.rekening.default');
            Route::delete('/rekening/{id}', [RekeningController::class, 'destroy'])->name('api.rekening.destroy');

            // Member limit & tenor-change applications (batch 4: mobile contract)
            Route::post('/pengajuan-limit', [PengajuanLimitController::class, 'store'])->name('api.pengajuan-limit.store');
            Route::post('/percepatan/preview', [PercepatanController::class, 'preview'])->name('api.percepatan.preview');
            Route::post('/percepatan', [PercepatanController::class, 'store'])->name('api.percepatan.store');
        });
        Route::get('/pinjaman/{pinjaman}', [PinjamanController::class, 'show'])->name('api.pinjaman.show');
        Route::get('/rekening', [RekeningController::class, 'index'])->name('api.rekening.index');
        Route::get('/pengajuan-limit', [PengajuanLimitController::class, 'index'])->name('api.pengajuan-limit.index');
        Route::get('/percepatan', [PercepatanController::class, 'index'])->name('api.percepatan.index');

        // Staff queues: loan reviews, limit reviews, tenor-change reviews (batch 5: mobile contract)
        Route::get('/pengurus/pinjaman', [PengurusPinjamanController::class, 'index'])->name('api.pengurus.pinjaman.index');
        Route::get('/pengurus/pengajuan-limit', [PengurusLimitController::class, 'index'])->name('api.pengurus.pengajuan-limit.index');
        Route::get('/pengurus/percepatan', [PengurusPercepatanController::class, 'index'])->name('api.pengurus.percepatan.index');

        // Staff confirmations: angsuran & simpanan queues (batch 6: mobile contract)
        Route::get('/pengurus/angsuran', [PengurusAngsuranController::class, 'index'])->name('api.pengurus.angsuran.index');
        Route::get('/pengurus/simpanan', [PengurusSimpananController::class, 'index'])->name('api.pengurus.simpanan.index');

        // Staff data lists: all loans + member savings (batch 6: mobile contract)
        Route::get('/pengurus/list-pinjaman', [PengurusListController::class, 'pinjaman'])->name('api.pengurus.list-pinjaman');
        Route::get('/pengurus/list-simpanan', [PengurusListController::class, 'simpanan'])->name('api.pengurus.list-simpanan');
        Route::get('/pengurus/list-simpanan/{anggota}', [PengurusListController::class, 'simpananDetail'])->name('api.pengurus.list-simpanan.detail');

        // Tabular reports for mobile download (batch 6: mobile contract)
        Route::get('/laporan', [LaporanController::class, 'index'])->name('api.laporan.index');
        Route::get('/laporan/{slug}', [LaporanController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('api.laporan.show');
        Route::get('/laporan/{slug}/pdf', [LaporanController::class, 'pdf'])->where('slug', '[a-z0-9-]+')->name('api.laporan.pdf');

        // Derived notifications (batch 6: mobile contract)
        Route::get('/notifications', [NotificationController::class, 'index'])->name('api.notifications.index');

        Route::middleware('idempotent')->group(function () {
            Route::post('/pengurus/pinjaman/{pinjaman}/preview', [PengurusPinjamanController::class, 'preview'])->name('api.pengurus.pinjaman.preview');
            Route::post('/pengurus/pinjaman/{pinjaman}/approve', [PengurusPinjamanController::class, 'approve'])->name('api.pengurus.pinjaman.approve');
            Route::post('/pengurus/pinjaman/{pinjaman}/reject', [PengurusPinjamanController::class, 'reject'])->name('api.pengurus.pinjaman.reject');
            Route::post('/pengurus/pengajuan-limit/{pengajuan}/approve', [PengurusLimitController::class, 'approve'])->name('api.pengurus.pengajuan-limit.approve');
            Route::post('/pengurus/pengajuan-limit/{pengajuan}/reject', [PengurusLimitController::class, 'reject'])->name('api.pengurus.pengajuan-limit.reject');
            Route::post('/pengurus/percepatan/{pengajuan}/approve', [PengurusPercepatanController::class, 'approve'])->name('api.pengurus.percepatan.approve');
            Route::post('/pengurus/percepatan/{pengajuan}/reject', [PengurusPercepatanController::class, 'reject'])->name('api.pengurus.percepatan.reject');

            // Staff confirmations + notification reads (batch 6: mobile contract)
            Route::post('/pengurus/angsuran/konfirmasi', [PengurusAngsuranController::class, 'konfirmasi'])->name('api.pengurus.angsuran.konfirmasi');
            Route::post('/pengurus/simpanan/konfirmasi', [PengurusSimpananController::class, 'konfirmasi'])->name('api.pengurus.simpanan.konfirmasi');
            Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->where('id', '[0-9a-f]{32}')->name('api.notifications.read');
            Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('api.notifications.read-all');
        });
    });
});
