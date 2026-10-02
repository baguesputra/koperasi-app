<?php

use App\Http\Controllers\AnggotaController;
use App\Http\Controllers\Auth\SsoController;
use App\Http\Controllers\Bendahara\AngsuranController;
use App\Http\Controllers\Bendahara\KlaimDanaSosialController as BendaharaKlaimDanaSosialController;
use App\Http\Controllers\Bendahara\PengajuanLimitController as BendaharaPengajuanLimitController;
use App\Http\Controllers\Bendahara\PercepatanController as BendaharaPercepatanController;
use App\Http\Controllers\Bendahara\PinjamanController as BendaharaPinjamanController;
use App\Http\Controllers\Bendahara\SimpananController as BendaharaSimpananController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JurnalKasController;
use App\Http\Controllers\KasKoperasiController;
use App\Http\Controllers\Ketua\AktivasiController as KetuaAktivasiController;
use App\Http\Controllers\Ketua\KlaimDanaSosialController as KetuaKlaimDanaSosialController;
use App\Http\Controllers\Ketua\PengajuanLimitController as KetuaPengajuanLimitController;
use App\Http\Controllers\Ketua\PercepatanController as KetuaPercepatanController;
use App\Http\Controllers\Ketua\PinjamanController as KetuaPinjamanController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MigrasiController;
use App\Http\Controllers\Pengaturan\PenggunaController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\PinjamanController;
use App\Http\Controllers\Portal\AktivasiController as PortalAktivasiController;
use App\Http\Controllers\Portal\DashboardController as PortalDashboardController;
use App\Http\Controllers\Portal\KlaimDanaSosialController as PortalKlaimDanaSosialController;
use App\Http\Controllers\Portal\PengajuanLimitController as PortalPengajuanLimitController;
use App\Http\Controllers\Portal\PercepatanController as PortalPercepatanController;
use App\Http\Controllers\Portal\PinjamanController as PortalPinjamanController;
use App\Http\Controllers\Portal\ProfilController;
use App\Http\Controllers\Portal\RiwayatController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SimpananController;
use App\Http\Controllers\VerifikasiController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// --------------------- Portal SSO -------------------------
Route::get('/auth/sso/redirect', [SsoController::class, 'redirect'])->name('sso.redirect');
Route::match(['get', 'post'], '/auth/sso/callback', [SsoController::class, 'callback'])
    ->name('sso.callback')
    ->middleware('throttle:sso-callback');
Route::get('/auth/sso/logout', [SsoController::class, 'logout'])->name('sso.logout');
Route::match(['get', 'post'], '/auth/sso/slo', [SsoController::class, 'slo'])->name('sso.slo');
Route::get('/auth/sso/metadata', [SsoController::class, 'metadata'])->name('sso.metadata');

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
    // ------------ Aktivasi (anggota nonaktif, di luar guard aktif) ----------------
    Route::get('/aktivasi', [PortalAktivasiController::class, 'landing'])->name('aktivasi.landing');
    Route::get('/aktivasi/form', [PortalAktivasiController::class, 'create'])->name('aktivasi.create');
    Route::post('/aktivasi', [PortalAktivasiController::class, 'store'])->name('aktivasi.store')->middleware('idempotent');

    // Menu Utama
    Route::get('/dashboard', [PortalDashboardController::class, 'index'])->name('dashboard')->middleware('anggota.aktif');
    Route::get('/riwayat', [RiwayatController::class, 'index'])->name('riwayat')->middleware('anggota.aktif');

    Route::middleware('anggota.aktif')->group(function () {
        // ------------ Pinjaman -----------------
        Route::get('/pinjaman/ajukan', [PortalPinjamanController::class, 'create'])->name('pinjaman.create');
        Route::post('/pinjaman/cek-nominal', [PortalPinjamanController::class, 'cekNominal'])->name('pinjaman.cek-nominal');
        Route::post('/pinjaman/simulasi', [PortalPinjamanController::class, 'simulasi'])->name('pinjaman.simulasi');
        Route::post('/pinjaman', [PortalPinjamanController::class, 'store'])->name('pinjaman.store')->middleware('idempotent');

        // ------------ Profile ----------------
        Route::get('/profil', [ProfilController::class, 'index'])->name('profil');
        Route::post('/profil/rekening', [ProfilController::class, 'storeRekening'])->name('profil.rekening.store');
        Route::put('/profil/rekening/{rekening}/default', [ProfilController::class, 'setDefaultRekening'])->name('profil.rekening.default');
        Route::delete('/profil/rekening/{rekening}', [ProfilController::class, 'destroyRekening'])->name('profil.rekening.destroy');

        // ------------ Pengajuan Limit ----------------
        Route::get('/pengajuan-limit', [PortalPengajuanLimitController::class, 'create'])->name('pengajuan-limit.create');
        Route::post('/pengajuan-limit', [PortalPengajuanLimitController::class, 'store'])->name('pengajuan-limit.store')->middleware('idempotent');

        // ------------ Perubahan Tenor ----------------
        Route::get('/percepatan', [PortalPercepatanController::class, 'create'])->name('percepatan.create');
        Route::post('/percepatan', [PortalPercepatanController::class, 'store'])->name('percepatan.store')->middleware('idempotent');
        Route::post('/percepatan/preview', [PortalPercepatanController::class, 'preview'])->name('percepatan.preview');

        // ------------ Klaim Dana Sosial ----------------
        Route::get('/klaim-dana-sosial', [PortalKlaimDanaSosialController::class, 'create'])->name('klaim-dana-sosial.create');
        Route::post('/klaim-dana-sosial', [PortalKlaimDanaSosialController::class, 'store'])->name('klaim-dana-sosial.store')->middleware('idempotent');
    });
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
    Route::get('/pinjaman/{pinjaman}', [PinjamanController::class, 'show'])->name('pinjaman.show');
    Route::get('/pinjaman/{pinjaman}/cetak-bukti', [PinjamanController::class, 'cetakBukti'])->name('pinjaman.cetak-bukti');
});

// ==========================================
// VERIFIKASI BUKTI PINJAMAN (Public via QR Code)
// ==========================================
Route::get('/verifikasi/bukti/{pinjaman}', [VerifikasiController::class, 'show'])
    ->name('verifikasi.bukti');
Route::get('/verifikasi/resign/{anggota}', [VerifikasiController::class, 'resign'])
    ->name('verifikasi.resign');

Route::middleware(['auth', 'permission:simpanan.lihat'])->group(function () {
    Route::get('/simpanan', [SimpananController::class, 'index'])->name('simpanan.index');
    Route::get('/simpanan/{anggota}', [SimpananController::class, 'show'])->name('simpanan.show');
});

Route::middleware(['auth', 'permission:kas.lihat'])->group(function () {
    Route::get('/kas-koperasi', [KasKoperasiController::class, 'index'])->name('kas-koperasi.index');
});

Route::middleware(['auth', 'permission:jurnal.lihat'])->group(function () {
    Route::get('/jurnal-kas', [JurnalKasController::class, 'index'])->name('jurnal-kas.index');
});

// ==========================================
// LAPORAN
// ==========================================
Route::middleware(['auth', 'permission:laporan.lihat'])->prefix('laporan')->name('laporan.')->group(function () {
    Route::get('/', [LaporanController::class, 'index'])->name('index');
    Route::get('/{jenis}', [LaporanController::class, 'show'])->where('jenis', '[a-z0-9-]+')->name('show');
    Route::get('/{jenis}/pdf', [LaporanController::class, 'pdf'])->where('jenis', '[a-z0-9-]+')->name('pdf');
    Route::get('/{jenis}/export', [LaporanController::class, 'export'])->where('jenis', '[a-z0-9-]+')->name('export');
});

// ==========================================
// KAS KOPERASI - TOPUP (khusus Bendahara)
// ==========================================
Route::middleware(['auth', 'permission:kas.topup'])->group(function () {
    Route::post('/kas-koperasi/topup', [KasKoperasiController::class, 'topup'])->name('kas-koperasi.topup');
    Route::post('/kas-koperasi/sisih-kas-kecil', [KasKoperasiController::class, 'sisihKasKecil'])->name('kas-koperasi.sisih-kas-kecil')->middleware('idempotent');
});

// ==========================================
// KELOLA ANGGOTA (khusus Admin)
// ==========================================
Route::middleware(['auth', 'permission:anggota.kelola'])->group(function () {
    Route::post('/anggota', [AnggotaController::class, 'store'])->name('anggota.store');
    Route::put('/anggota/{anggota}', [AnggotaController::class, 'update'])->name('anggota.update');
    Route::get('/anggota/template', [AnggotaController::class, 'downloadTemplate'])->name('anggota.template');
    Route::get('/anggota/import', [AnggotaController::class, 'importIndex'])->name('anggota.import.index');
    Route::post('/anggota/import', [AnggotaController::class, 'import'])->name('anggota.import');
});

Route::middleware(['auth', 'permission:anggota.resign'])->group(function () {
    Route::get('/anggota/{anggota}/ringkasan-resign', [AnggotaController::class, 'ringkasanResign'])->name('anggota.ringkasan-resign');
    Route::post('/anggota/{anggota}/resign', [AnggotaController::class, 'resign'])->name('anggota.resign');
    Route::post('/anggota/{anggota}/aktifkan-kembali', [AnggotaController::class, 'aktifkanKembali'])->name('anggota.aktifkan-kembali');
    Route::get('/anggota/{anggota}/slip-resign', [AnggotaController::class, 'slipResign'])->name('anggota.slip-resign');
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
    Route::post('/pengaturan/kas/{setting}', [PengaturanController::class, 'updateKas'])->name('pengaturan.kas.update');
    Route::post('/pengaturan/sinkron-gate', [PengaturanController::class, 'sinkronGate'])->name('pengaturan.sinkron-gate');
    Route::post('/pengaturan/sinkron-master-gate', [PengaturanController::class, 'sinkronMasterGate'])->name('pengaturan.sinkron-master-gate');
    // --------- WhatsApp (Baileys) ------------
    Route::get('/pengaturan/wa', [PengaturanController::class, 'waData'])->name('pengaturan.wa.data');
    Route::post('/pengaturan/wa/logout', [PengaturanController::class, 'waLogout'])->name('pengaturan.wa.logout');
    // --------- Role ------------
    Route::get('/role', [RoleController::class, 'index'])->name('role.index');
    Route::post('/role', [RoleController::class, 'store'])->name('role.store');
    Route::get('/role/{role}/edit', [RoleController::class, 'edit'])->name('role.edit');
    Route::put('/role/{role}', [RoleController::class, 'update'])->name('role.update');
    Route::delete('/role/{role}', [RoleController::class, 'destroy'])->name('role.destroy');
});

// ==========================================
// KELOLA PENGGUNA (khusus Admin, di dalam Pengaturan)
// ==========================================
Route::middleware(['auth', 'permission:user.kelola', 'password.confirm'])->prefix('pengaturan')->name('pengaturan.')->group(function () {
    Route::get('/pengguna', [PenggunaController::class, 'index'])->name('pengguna.index');
    Route::post('/pengguna', [PenggunaController::class, 'store'])->name('pengguna.store');
    Route::put('/pengguna/{user}', [PenggunaController::class, 'update'])->name('pengguna.update');
    Route::post('/pengguna/{user}/reset-password', [PenggunaController::class, 'resetPassword'])->name('pengguna.reset-password');
    Route::post('/pengguna/{user}/toggle-status', [PenggunaController::class, 'toggleStatus'])->name('pengguna.toggle-status');
    Route::delete('/pengguna/{user}', [PenggunaController::class, 'destroy'])->name('pengguna.destroy');
});

// ==========================================
// PROSES BENDAHARA
// ==========================================
Route::middleware('auth')->prefix('bendahara')->name('bendahara.')->group(function () {
    Route::middleware('permission:pinjaman.tinjau-bendahara')->group(function () {
        Route::get('/pinjaman', [BendaharaPinjamanController::class, 'index'])->name('pinjaman.index');
        Route::get('/pinjaman/{pinjaman}', [BendaharaPinjamanController::class, 'show'])->name('pinjaman.show');
        Route::post('/pinjaman/{pinjaman}/preview', [BendaharaPinjamanController::class, 'preview'])->name('pinjaman.preview');
        Route::post('/pinjaman/{pinjaman}/approve', [BendaharaPinjamanController::class, 'approve'])->name('pinjaman.approve')->middleware('idempotent');
        Route::post('/pinjaman/{pinjaman}/reject', [BendaharaPinjamanController::class, 'reject'])->name('pinjaman.reject')->middleware('idempotent');
        Route::post('/pinjaman/{pinjaman}/cair', [BendaharaPinjamanController::class, 'cair'])->name('pinjaman.cair')->middleware('idempotent');
        Route::get('/percepatan', [BendaharaPercepatanController::class, 'index'])->name('percepatan.index');
        Route::get('/percepatan/{percepatan}', [BendaharaPercepatanController::class, 'show'])->name('percepatan.show');
        Route::post('/percepatan/{percepatan}/approve', [BendaharaPercepatanController::class, 'approve'])->name('percepatan.approve')->middleware('idempotent');
        Route::post('/percepatan/{percepatan}/reject', [BendaharaPercepatanController::class, 'reject'])->name('percepatan.reject')->middleware('idempotent');
    });

    Route::middleware('permission:limit.tinjau-bendahara')->group(function () {
        Route::get('/pengajuan-limit', [BendaharaPengajuanLimitController::class, 'index'])->name('pengajuan-limit.index');
        Route::get('/pengajuan-limit/{pengajuanLimit}', [BendaharaPengajuanLimitController::class, 'show'])->name('pengajuan-limit.show');
        Route::post('/pengajuan-limit/{pengajuanLimit}/approve', [BendaharaPengajuanLimitController::class, 'approve'])->name('pengajuan-limit.approve')->middleware('idempotent');
        Route::post('/pengajuan-limit/{pengajuanLimit}/reject', [BendaharaPengajuanLimitController::class, 'reject'])->name('pengajuan-limit.reject')->middleware('idempotent');
    });

    Route::middleware('permission:angsuran.konfirmasi')->group(function () {
        Route::get('/angsuran', [AngsuranController::class, 'index'])->name('angsuran.index');
        Route::post('/angsuran/konfirmasi', [AngsuranController::class, 'konfirmasi'])->name('angsuran.konfirmasi')->middleware('idempotent');
        Route::post('/angsuran/konfirmasi-percepatan', [AngsuranController::class, 'konfirmasiPercepatan'])->name('angsuran.konfirmasi-percepatan')->middleware('idempotent');
    });

    Route::middleware('permission:simpanan.konfirmasi')->group(function () {
        Route::get('/simpanan', [BendaharaSimpananController::class, 'index'])->name('simpanan.index');
        Route::post('/simpanan/konfirmasi', [BendaharaSimpananController::class, 'konfirmasi'])->name('simpanan.konfirmasi')->middleware('idempotent');
    });

    Route::middleware('permission:klaim.tinjau-bendahara')->group(function () {
        Route::get('/klaim-dana-sosial', [BendaharaKlaimDanaSosialController::class, 'index'])->name('klaim-dana-sosial.index');
        Route::get('/klaim-dana-sosial/{klaimDanaSosial}', [BendaharaKlaimDanaSosialController::class, 'show'])->name('klaim-dana-sosial.show');
        Route::post('/klaim-dana-sosial/{klaimDanaSosial}/approve', [BendaharaKlaimDanaSosialController::class, 'approve'])->name('klaim-dana-sosial.approve')->middleware('idempotent');
        Route::post('/klaim-dana-sosial/{klaimDanaSosial}/reject', [BendaharaKlaimDanaSosialController::class, 'reject'])->name('klaim-dana-sosial.reject')->middleware('idempotent');
    });
});

// ==========================================
// PROSES KETUA KOPERASI
// ==========================================
Route::middleware('auth')->prefix('ketua')->name('ketua.')->group(function () {
    Route::middleware('permission:pinjaman.approve-ketua')->group(function () {
        Route::get('/pinjaman', [KetuaPinjamanController::class, 'index'])->name('pinjaman.index');
        Route::get('/pinjaman/{pinjaman}', [KetuaPinjamanController::class, 'show'])->name('pinjaman.show');
        Route::post('/pinjaman/{pinjaman}/preview', [KetuaPinjamanController::class, 'preview'])->name('pinjaman.preview');
        Route::post('/pinjaman/{pinjaman}/approve', [KetuaPinjamanController::class, 'approve'])->name('pinjaman.approve')->middleware('idempotent');
        Route::post('/pinjaman/{pinjaman}/reject', [KetuaPinjamanController::class, 'reject'])->name('pinjaman.reject')->middleware('idempotent');
        Route::get('/percepatan', [KetuaPercepatanController::class, 'index'])->name('percepatan.index');
        Route::get('/percepatan/{percepatan}', [KetuaPercepatanController::class, 'show'])->name('percepatan.show');
        Route::post('/percepatan/{percepatan}/approve', [KetuaPercepatanController::class, 'approve'])->name('percepatan.approve')->middleware('idempotent');
        Route::post('/percepatan/{percepatan}/reject', [KetuaPercepatanController::class, 'reject'])->name('percepatan.reject')->middleware('idempotent');
    });

    Route::middleware('permission:limit.approve-ketua')->group(function () {
        Route::get('/pengajuan-limit', [KetuaPengajuanLimitController::class, 'index'])->name('pengajuan-limit.index');
        Route::get('/pengajuan-limit/{pengajuanLimit}', [KetuaPengajuanLimitController::class, 'show'])->name('pengajuan-limit.show');
        Route::post('/pengajuan-limit/{pengajuanLimit}/approve', [KetuaPengajuanLimitController::class, 'approve'])->name('pengajuan-limit.approve')->middleware('idempotent');
        Route::post('/pengajuan-limit/{pengajuanLimit}/reject', [KetuaPengajuanLimitController::class, 'reject'])->name('pengajuan-limit.reject')->middleware('idempotent');
    });

    Route::middleware('permission:aktivasi.approve-ketua')->group(function () {
        Route::get('/aktivasi', [KetuaAktivasiController::class, 'index'])->name('aktivasi.index');
        Route::post('/aktivasi/{aktivasi}/approve', [KetuaAktivasiController::class, 'approve'])->name('aktivasi.approve')->middleware('idempotent');
        Route::post('/aktivasi/{aktivasi}/reject', [KetuaAktivasiController::class, 'reject'])->name('aktivasi.reject')->middleware('idempotent');
    });

    Route::middleware('permission:klaim.approve-ketua')->group(function () {
        Route::get('/klaim-dana-sosial', [KetuaKlaimDanaSosialController::class, 'index'])->name('klaim-dana-sosial.index');
        Route::get('/klaim-dana-sosial/{klaimDanaSosial}', [KetuaKlaimDanaSosialController::class, 'show'])->name('klaim-dana-sosial.show');
        Route::post('/klaim-dana-sosial/{klaimDanaSosial}/approve', [KetuaKlaimDanaSosialController::class, 'approve'])->name('klaim-dana-sosial.approve')->middleware('idempotent');
        Route::post('/klaim-dana-sosial/{klaimDanaSosial}/reject', [KetuaKlaimDanaSosialController::class, 'reject'])->name('klaim-dana-sosial.reject')->middleware('idempotent');
    });
});

// ==========================================
// PROFILE (semua user login)
// ==========================================
Route::middleware('auth')->group(function () {
    // Wajib ganti password dinonaktifkan: akses dikelola GATE.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
});

Route::middleware(['auth', 'permission:kas.lihat'])->group(function () {
    Route::get('/pengeluaran', [PengeluaranController::class, 'index'])->name('pengeluaran.index');
    Route::post('/pengeluaran', [PengeluaranController::class, 'store'])->name('pengeluaran.store')->middleware(['permission:kas.topup', 'idempotent']);
});

// SSO logout - must be before auth.php to take precedence
Route::get('logout', function () {
    if (config('auth.mode') === 'sso') {
        return redirect()->away('https://gate.appdutamall.com/dashboard');
    }

    return redirect()->route('login');
})->name('logout');

// SSO gagal - accessible without auth, does NOT redirect to SSO
Route::get('/auth/sso/gagal', function () {
    return Inertia::render('Auth/SsoGagal', [
        'error' => session('error'),
    ]);
})->middleware('guest')->name('sso.gagal');

Route::middleware(['auth', 'permission:migrasi.kelola'])->prefix('migrasi')->name('migrasi.')->group(function () {
    Route::get('/', [MigrasiController::class, 'index'])->name('index');
    Route::get('/template-pinjaman', [MigrasiController::class, 'templatePinjaman'])->name('template-pinjaman');
    Route::get('/template-simpanan', [MigrasiController::class, 'templateSimpanan'])->name('template-simpanan');
    Route::post('/import-pinjaman', [MigrasiController::class, 'importPinjaman'])->name('import-pinjaman');
    Route::post('/import-simpanan', [MigrasiController::class, 'importSimpanan'])->name('import-simpanan');
    Route::get('/progres-pinjaman', [MigrasiController::class, 'progresPinjaman'])->name('progres-pinjaman');
    Route::get('/progres-simpanan', [MigrasiController::class, 'progresSimpanan'])->name('progres-simpanan');
});

require __DIR__.'/auth.php';
