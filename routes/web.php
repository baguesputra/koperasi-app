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
use App\Http\Controllers\Portal\PinjamanController as PortalPinjamanController;
use App\Http\Controllers\Bendahara\PinjamanController as BendaharaPinjamanController;
use App\Http\Controllers\Ketua\PinjamanController as KetuaPinjamanController;
use App\Http\Controllers\PinjamanController;
use App\Http\Controllers\KasKoperasiController;
use App\Http\Controllers\Bendahara\AngsuranController;
use App\Http\Controllers\SimpananController;
use App\Http\Controllers\Bendahara\SimpananController as BendaharaSimpananController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Portal\ProfilController;
use App\Http\Controllers\Auth\GantiPasswordWajibController;
use App\Http\Controllers\Auth\SsoController;

//--------------------- Portal SSO -------------------------
Route::get('/auth/sso/redirect', [SsoController::class, 'redirect'])->name('sso.redirect');
Route::get('/auth/sso/callback', [SsoController::class, 'callback'])->name('sso.callback');
// Route::get('/auth/sso/logout', [SsoController::class, 'logout'])
//     ->name('sso.logout');

// Route::get('/auth/sso/logout/callback', [SsoController::class, 'logoutCallback'])
//     ->name('sso.logout.callback');

// Route::get('/', function () {
//     if (auth()->check()) {
//         return redirect()->route('dashboard');
//     }

//     return redirect()->route('login');
// });

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('sso.redirect');
});


// ==========================================
// PORTAL ANGGOTA
// ==========================================
Route::middleware(['auth', 'permission:portal.akses'])->prefix('portal')->name('portal.')->group(function () {
    //Menu Utama
    Route::get('/dashboard', [PortalDashboardController::class, 'index'])->name('dashboard');
    Route::get('/riwayat', [RiwayatController::class, 'index'])->name('riwayat');

    //------------ Pinjaman -----------------
    Route::get('/pinjaman/ajukan', [PortalPinjamanController::class, 'create'])->name('pinjaman.create');
    Route::post('/pinjaman/cek-nominal', [PortalPinjamanController::class, 'cekNominal'])->name('pinjaman.cek-nominal');
    Route::post('/pinjaman/simulasi', [PortalPinjamanController::class, 'simulasi'])->name('pinjaman.simulasi');
    Route::post('/pinjaman', [PortalPinjamanController::class, 'store'])->name('pinjaman.store');
    Route::get('/pinjaman/berhasil', [PortalPinjamanController::class, 'berhasil'])->name('pinjaman.berhasil');

    //------------ Profile ----------------
    Route::get('/profil', [ProfilController::class, 'index'])->name('profil');
    Route::post('/profil/rekening', [ProfilController::class, 'storeRekening'])->name('profil.rekening.store');
    Route::put('/profil/rekening/{rekening}/default', [ProfilController::class, 'setDefaultRekening'])->name('profil.rekening.default');
    Route::delete('/profil/rekening/{rekening}', [ProfilController::class, 'destroyRekening'])->name('profil.rekening.destroy');
});

// ==========================================
// DASHBOARD KOPERASI (Admin/Bendahara/Ketua)
// ==========================================
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// ==========================================
// LIHAT DATA (Admin/Bendahara/Ketua)
// ==========================================
Route::middleware(['auth', 'permission:anggota.lihat'])->group(function () {
    Route::get('/anggota', [AnggotaController::class, 'index'])->name('anggota.index');
});

Route::middleware(['auth', 'permission:pinjaman.lihat'])->group(function () {
    Route::get('/pinjaman', [PinjamanController::class, 'index'])->name('pinjaman.index');
});

Route::middleware(['auth', 'permission:simpanan.lihat'])->group(function () {
    Route::get('/simpanan', [SimpananController::class, 'index'])->name('simpanan.index');
    Route::get('/simpanan/{anggota}', [SimpananController::class, 'show'])->name('simpanan.show');
});

Route::middleware(['auth', 'permission:kas.lihat'])->group(function () {
    Route::get('/kas-koperasi', [KasKoperasiController::class, 'index'])->name('kas-koperasi.index');
});

// ==========================================
// KAS KOPERASI - TOPUP (khusus Bendahara)
// ==========================================
Route::middleware(['auth', 'permission:kas.topup'])->group(function () {
    Route::post('/kas-koperasi/topup', [KasKoperasiController::class, 'topup'])->name('kas-koperasi.topup');
});

// ==========================================
// KELOLA ANGGOTA (khusus Admin)
// ==========================================
Route::middleware(['auth', 'permission:anggota.kelola'])->group(function () {
    Route::get('/anggota/create', [AnggotaController::class, 'create'])->name('anggota.create');
    Route::post('/anggota', [AnggotaController::class, 'store'])->name('anggota.store');
    Route::get('/anggota/{anggota}/edit', [AnggotaController::class, 'edit'])->name('anggota.edit');
    Route::put('/anggota/{anggota}', [AnggotaController::class, 'update'])->name('anggota.update');
    Route::get('/anggota/template', [AnggotaController::class, 'downloadTemplate'])->name('anggota.template');
    Route::get('/anggota/import', [AnggotaController::class, 'importIndex'])->name('anggota.import.index');
    Route::post('/anggota/import', [AnggotaController::class, 'import'])->name('anggota.import');
});

// ==========================================
// PENGATURAN (khusus Admin)
// ==========================================
Route::middleware(['auth', 'permission:pengaturan.kelola', 'password.confirm'])->group(function () {
    Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');
    Route::put('/pengaturan/limit/{limit}', [PengaturanController::class, 'updateLimit'])->name('pengaturan.limit.update');
    Route::post('/pengaturan/tenor', [PengaturanController::class, 'storeTenor'])->name('pengaturan.tenor.store');
    Route::put('/pengaturan/tenor/{tenor}', [PengaturanController::class, 'updateTenor'])->name('pengaturan.tenor.update');
    Route::delete('/pengaturan/tenor/{tenor}', [PengaturanController::class, 'destroyTenor'])->name('pengaturan.tenor.destroy');
    Route::post('/pengaturan/bunga', [PengaturanController::class, 'updateBunga'])->name('pengaturan.bunga.update');
    Route::post('/pengaturan/simpanan/{setting}', [PengaturanController::class, 'updateSimpanan'])->name('pengaturan.simpanan.update');
    //--------- Role ------------
    Route::get('/role', [RoleController::class, 'index'])->name('role.index');
    Route::post('/role', [RoleController::class, 'store'])->name('role.store');
    Route::get('/role/{role}/edit', [RoleController::class, 'edit'])->name('role.edit');
    Route::put('/role/{role}', [RoleController::class, 'update'])->name('role.update');
    Route::delete('/role/{role}', [RoleController::class, 'destroy'])->name('role.destroy');
});

// ==========================================
// PROSES BENDAHARA
// ==========================================
Route::middleware(['auth', 'permission:pinjaman.tinjau-bendahara'])->prefix('bendahara')->name('bendahara.')->group(function () {
    Route::get('/pinjaman', [BendaharaPinjamanController::class, 'index'])->name('pinjaman.index');
    Route::get('/pinjaman/{pinjaman}', [BendaharaPinjamanController::class, 'show'])->name('pinjaman.show');
    Route::post('/pinjaman/{pinjaman}/approve', [BendaharaPinjamanController::class, 'approve'])->name('pinjaman.approve');
    Route::post('/pinjaman/{pinjaman}/reject', [BendaharaPinjamanController::class, 'reject'])->name('pinjaman.reject');
    Route::post('/pinjaman/{pinjaman}/cair', [BendaharaPinjamanController::class, 'cair'])->name('pinjaman.cair');
});

Route::middleware(['auth', 'permission:angsuran.konfirmasi'])->prefix('bendahara')->name('bendahara.')->group(function () {
    Route::get('/angsuran', [AngsuranController::class, 'index'])->name('angsuran.index');
    Route::post('/angsuran/konfirmasi', [AngsuranController::class, 'konfirmasi'])->name('angsuran.konfirmasi');
});

Route::middleware(['auth', 'permission:simpanan.konfirmasi'])->prefix('bendahara')->name('bendahara.')->group(function () {
    Route::get('/simpanan', [BendaharaSimpananController::class, 'index'])->name('simpanan.index');
    Route::post('/simpanan/konfirmasi', [BendaharaSimpananController::class, 'konfirmasi'])->name('simpanan.konfirmasi');
});

// ==========================================
// PROSES KETUA KOPERASI
// ==========================================
Route::middleware(['auth', 'permission:pinjaman.approve-ketua'])->prefix('ketua')->name('ketua.')->group(function () {
    Route::get('/pinjaman', [KetuaPinjamanController::class, 'index'])->name('pinjaman.index');
    Route::get('/pinjaman/{pinjaman}', [KetuaPinjamanController::class, 'show'])->name('pinjaman.show');
    Route::post('/pinjaman/{pinjaman}/approve', [KetuaPinjamanController::class, 'approve'])->name('pinjaman.approve');
    Route::post('/pinjaman/{pinjaman}/reject', [KetuaPinjamanController::class, 'reject'])->name('pinjaman.reject');
});

// ==========================================
// PROFILE (semua user login)
// ==========================================
Route::middleware('auth')->group(function () {
    Route::get('/ganti-password-wajib', [GantiPasswordWajibController::class, 'index'])->name('password.wajib-ganti');
    Route::post('/ganti-password-wajib', [GantiPasswordWajibController::class, 'update'])->name('password.wajib-ganti.update');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';