<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PengajuanAnggotaController;
use App\Http\Controllers\API\PinjamanController;
use App\Http\Controllers\API\PengajuanLimitController;
use App\Http\Controllers\API\PercepatanController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\MasterDataController;

// Guest routes (no authentication required)
Route::middleware('guest')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated routes (require Sanctum authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Member Applications (Pengajuan Anggota)
    Route::apiResource('/pengajuan-anggota', PengajuanAnggotaController::class)
        ->except(['create', 'edit']); // API doesn't need these
    
    // Loan Applications
    Route::apiResource('/pinjaman', PinjamanController::class)
        ->except(['create', 'edit']);
    Route::post('/pinjaman/{pinjaman}/cek-nominal', [PinjamanController::class, 'cekNominal']);
    Route::post('/pinjaman/{pinjaman}/simulasi', [PinjamanController::class, 'simulasi']);
    
    // Limit Increase Applications
    Route::apiResource('/pengajuan-limit', PengajuanLimitController::class)
        ->except(['create', 'edit']);
    
    // Tenor Change Applications
    Route::apiResource('/percepatan', PercepatanController::class)
        ->except(['create', 'edit']);
    Route::post('/percepatan/{percepatan}/preview', [PercepatanController::class, 'preview']);
    
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