<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\MasterDataController;
use App\Http\Controllers\API\PengajuanAnggotaController;
use App\Http\Controllers\API\PengajuanLimitController;
use App\Http\Controllers\API\PercepatanController;
use App\Http\Controllers\API\PinjamanController;
use Illuminate\Support\Facades\Route;

// Guest routes (no authentication required)
Route::middleware('guest')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated routes (require Sanctum authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/user', [AuthController::class, 'user']);

    // Member Applications (Pengajuan Anggota)
    Route::apiResource('/pengajuan-anggota', PengajuanAnggotaController::class)
        ->except(['create', 'edit'])
        ->names('api.pengajuan-anggota');

    // Loan Applications
    Route::apiResource('/pinjaman', PinjamanController::class)
        ->except(['create', 'edit'])
        ->names('api.pinjaman');
    Route::post('/pinjaman/{pinjaman}/cek-nominal', [PinjamanController::class, 'cekNominal'])->name('api.pinjaman.cek-nominal');
    Route::post('/pinjaman/{pinjaman}/simulasi', [PinjamanController::class, 'simulasi'])->name('api.pinjaman.simulasi');

    // Limit Increase Applications
    Route::apiResource('/pengajuan-limit', PengajuanLimitController::class)
        ->except(['create', 'edit'])
        ->names('api.pengajuan-limit');

    // Tenor Change Applications
    Route::apiResource('/percepatan', PercepatanController::class)
        ->except(['create', 'edit'])
        ->names('api.percepatan');
    Route::post('/percepatan/{percepatan}/preview', [PercepatanController::class, 'preview'])->name('api.percepatan.preview');

    // Dashboard Endpoints
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/actionable', [DashboardController::class, 'actionable']);
    Route::get('/dashboard/charts', [DashboardController::class, 'charts']);
    Route::get('/dashboard/aktivitas', [DashboardController::class, 'aktivitas']);

    // Master Data (for forms/dropdowns)
    Route::get('/master-data/cabang', [MasterDataController::class, 'cabang']);
    Route::get('/master-data/unit-bisnis', [MasterDataController::class, 'unitBisnis']);
    Route::get('/master-data/jabatan', [MasterDataController::class, 'jabatan']);
    Route::get('/master-data/divisi', [MasterDataController::class, 'divisi']);
    Route::get('/master-data/department', [MasterDataController::class, 'department']);
    Route::get('/master-data/tabel-tenor', [MasterDataController::class, 'tabelTenor']);
    Route::get('/master-data/setting-simpanan', [MasterDataController::class, 'settingSimpanan']);
});
