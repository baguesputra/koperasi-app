<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AnggotaController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\Portal\DashboardController as PortalDashboardController;
use App\Http\Controllers\Portal\RiwayatController;
use App\Http\Controllers\Portal\PinjamanController;
use App\Http\Controllers\Bendahara\PinjamanController as BendaharaPinjamanController;
use App\Http\Controllers\Ketua\PinjamanController as KetuaPinjamanController;


Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'role:anggota'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/dashboard', [PortalDashboardController::class, 'index'])->name('dashboard');
    Route::get('/riwayat', [RiwayatController::class, 'index'])->name('riwayat');

    Route::get('/pinjaman/ajukan', [PinjamanController::class, 'create'])->name('pinjaman.create');
    Route::post('/pinjaman/cek-nominal', [PinjamanController::class, 'cekNominal'])->name('pinjaman.cek-nominal');
    Route::post('/pinjaman/simulasi', [PinjamanController::class, 'simulasi'])->name('pinjaman.simulasi');
    Route::post('/pinjaman', [PinjamanController::class, 'store'])->name('pinjaman.store');
    Route::get('/pinjaman/berhasil', [PinjamanController::class, 'berhasil'])->name('pinjaman.berhasil');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'role:admin|bendahara|ketua_koperasi'])->group(function () {
    Route::get('/anggota', [AnggotaController::class, 'index'])->name('anggota.index');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/anggota/create', [AnggotaController::class, 'create'])->name('anggota.create');
    Route::post('/anggota', [AnggotaController::class, 'store'])->name('anggota.store');
    Route::get('/anggota/{anggota}/edit', [AnggotaController::class, 'edit'])->name('anggota.edit');
    Route::put('/anggota/{anggota}', [AnggotaController::class, 'update'])->name('anggota.update');

    Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');
    Route::put('/pengaturan/limit/{limit}', [PengaturanController::class, 'updateLimit'])->name('pengaturan.limit.update');
    Route::post('/pengaturan/tenor', [PengaturanController::class, 'storeTenor'])->name('pengaturan.tenor.store');
    Route::put('/pengaturan/tenor/{tenor}', [PengaturanController::class, 'updateTenor'])->name('pengaturan.tenor.update');
    Route::delete('/pengaturan/tenor/{tenor}', [PengaturanController::class, 'destroyTenor'])->name('pengaturan.tenor.destroy');
    Route::post('/pengaturan/bunga', [PengaturanController::class, 'updateBunga'])->name('pengaturan.bunga.update');
});

Route::middleware(['auth', 'role:bendahara'])->prefix('bendahara')->name('bendahara.')->group(function () {
    Route::get('/pinjaman', [BendaharaPinjamanController::class, 'index'])->name('pinjaman.index');
    Route::get('/pinjaman/{pinjaman}', [BendaharaPinjamanController::class, 'show'])->name('pinjaman.show');
    Route::post('/pinjaman/{pinjaman}/approve', [BendaharaPinjamanController::class, 'approve'])->name('pinjaman.approve');
    Route::post('/pinjaman/{pinjaman}/reject', [BendaharaPinjamanController::class, 'reject'])->name('pinjaman.reject');
});

Route::middleware(['auth', 'role:ketua_koperasi'])->prefix('ketua')->name('ketua.')->group(function () {
    Route::get('/pinjaman', [KetuaPinjamanController::class, 'index'])->name('pinjaman.index');
    Route::get('/pinjaman/{pinjaman}', [KetuaPinjamanController::class, 'show'])->name('pinjaman.show');
    Route::post('/pinjaman/{pinjaman}/approve', [KetuaPinjamanController::class, 'approve'])->name('pinjaman.approve');
    Route::post('/pinjaman/{pinjaman}/reject', [KetuaPinjamanController::class, 'reject'])->name('pinjaman.reject');
});



Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
