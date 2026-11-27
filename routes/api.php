<?php

use App\Http\Controllers\API\AktivasiMandiriController;
use App\Http\Controllers\API\AnggotaController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\LaporanController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\PengajuanLimitController;
use App\Http\Controllers\API\Pengurus\AktivasiController as PengurusAktivasiController;
use App\Http\Controllers\API\Pengurus\AnggotaKelolaController as PengurusAnggotaKelolaController;
use App\Http\Controllers\API\Pengurus\AngsuranController as PengurusAngsuranController;
use App\Http\Controllers\API\Pengurus\KasController as PengurusKasController;
use App\Http\Controllers\API\Pengurus\LimitController as PengurusLimitController;
use App\Http\Controllers\API\Pengurus\ListController as PengurusListController;
use App\Http\Controllers\API\Pengurus\PenghapusanPinjamanController as PengurusPenghapusanPinjamanController;
use App\Http\Controllers\API\Pengurus\PercepatanController as PengurusPercepatanController;
use App\Http\Controllers\API\Pengurus\PersetujuanController as PengurusPersetujuanController;
use App\Http\Controllers\API\Pengurus\PinjamanController as PengurusPinjamanController;
use App\Http\Controllers\API\Pengurus\SimpananController as PengurusSimpananController;
use App\Http\Controllers\API\Pengurus\TabunganController as PengurusTabunganController;
use App\Http\Controllers\API\PercepatanController;
use App\Http\Controllers\API\PinjamanController;
use App\Http\Controllers\API\RekeningController;
use App\Http\Controllers\API\SecurityPinController;
use App\Http\Controllers\API\TabunganController;
use App\Http\Controllers\Auth\MobileSsoController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->group(function () {
    Route::post('/sso/mobile/exchange', [MobileSsoController::class, 'exchange'])->name('api.sso.mobile.exchange');
    // Guest routes (no authentication required, local mode only)
    Route::middleware('guest')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login-api');
    });

    // Authenticated routes (require Sanctum authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
        Route::get('/user', [AuthController::class, 'user']);
        Route::get('/me', [AuthController::class, 'me'])->name('api.me');

        // Security 6-digit PIN (mobile)
        Route::get('/security/pin/status', [SecurityPinController::class, 'status'])->name('api.security.pin.status');
        Route::middleware('throttle:pin')->group(function () {
            Route::post('/security/pin/setup', [SecurityPinController::class, 'setup'])->name('api.security.pin.setup');
            Route::post('/security/pin/verify', [SecurityPinController::class, 'verify'])->name('api.security.pin.verify');
        });

        Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('api.dashboard');
        Route::get('/pengurus/ringkasan', [DashboardController::class, 'ringkasan'])->name('api.pengurus.ringkasan');
        Route::get('/pengurus/kas', [PengurusKasController::class, 'index'])->name('api.pengurus.kas.index');

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
            Route::put('/pinjaman/{pinjaman}', [PinjamanController::class, 'update'])->name('api.pinjaman.update');
            Route::post('/pinjaman/{pinjaman}/batal', [PinjamanController::class, 'batal'])->name('api.pinjaman.batal');
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

        // Member self-activation & voluntary savings (tabungan)
        Route::get('/aktivasi-mandiri', [AktivasiMandiriController::class, 'show'])->name('api.aktivasi-mandiri.show');
        Route::get('/tabungan', [TabunganController::class, 'index'])->name('api.tabungan.index');
        Route::middleware('idempotent')->group(function () {
            Route::post('/aktivasi-mandiri', [AktivasiMandiriController::class, 'store'])->name('api.aktivasi-mandiri.store');
            Route::post('/tabungan/buka', [TabunganController::class, 'buka'])->name('api.tabungan.buka');
            Route::post('/tabungan/setor', [TabunganController::class, 'setor'])->name('api.tabungan.setor');
            Route::post('/tabungan/tarik', [TabunganController::class, 'tarik'])->name('api.tabungan.tarik');
        });

        // Staff queues: unified persetujuan hub + loan, limit, tenor-change, member activations, penghapusan
        Route::get('/pengurus/persetujuan', [PengurusPersetujuanController::class, 'index'])->name('api.pengurus.persetujuan.index');
        Route::get('/pengurus/pinjaman', [PengurusPinjamanController::class, 'index'])->name('api.pengurus.pinjaman.index');
        Route::get('/pengurus/pengajuan-limit', [PengurusLimitController::class, 'index'])->name('api.pengurus.pengajuan-limit.index');
        Route::get('/pengurus/percepatan', [PengurusPercepatanController::class, 'index'])->name('api.pengurus.percepatan.index');
        Route::get('/pengurus/aktivasi', [PengurusAktivasiController::class, 'index'])->name('api.pengurus.aktivasi.index');
        Route::get('/pengurus/penghapusan-pinjaman', [PengurusPenghapusanPinjamanController::class, 'index'])->name('api.pengurus.penghapusan-pinjaman.index');

        // Staff confirmations: angsuran & simpanan queues (batch 6: mobile contract)
        Route::get('/pengurus/angsuran', [PengurusAngsuranController::class, 'index'])->name('api.pengurus.angsuran.index');
        Route::get('/pengurus/simpanan', [PengurusSimpananController::class, 'index'])->name('api.pengurus.simpanan.index');
        Route::get('/pengurus/tabungan/bunga/preview', [PengurusTabunganController::class, 'previewBunga'])->name('api.pengurus.tabungan.bunga.preview');

        // Staff data lists: all loans + member savings + tabungan + members
        Route::get('/pengurus/list-pinjaman', [PengurusListController::class, 'pinjaman'])->name('api.pengurus.list-pinjaman');
        Route::get('/pengurus/list-simpanan', [PengurusListController::class, 'simpanan'])->name('api.pengurus.list-simpanan');
        Route::get('/pengurus/list-simpanan/{anggota}', [PengurusListController::class, 'simpananDetail'])->name('api.pengurus.list-simpanan.detail');
        Route::get('/pengurus/list-tabungan', [PengurusListController::class, 'tabungan'])->name('api.pengurus.list-tabungan');
        Route::get('/pengurus/list-tabungan/{rekening}', [PengurusListController::class, 'tabunganDetail'])->name('api.pengurus.list-tabungan.detail');
        Route::get('/pengurus/list-anggota', [PengurusListController::class, 'anggota'])->name('api.pengurus.list-anggota');
        Route::get('/pengurus/anggota/{anggota}/resign-ringkasan', [PengurusAnggotaKelolaController::class, 'ringkasanResign'])->name('api.pengurus.anggota.resign-ringkasan');

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
            Route::post('/pengurus/aktivasi/{aktivasi}/approve', [PengurusAktivasiController::class, 'approve'])->name('api.pengurus.aktivasi.approve');
            Route::post('/pengurus/aktivasi/{aktivasi}/reject', [PengurusAktivasiController::class, 'reject'])->name('api.pengurus.aktivasi.reject');

            // Staff confirmations, cancellations (storno), & notification reads
            Route::post('/pengurus/angsuran/konfirmasi', [PengurusAngsuranController::class, 'konfirmasi'])->name('api.pengurus.angsuran.konfirmasi');
            Route::post('/pengurus/angsuran/{item}/batalkan', [PengurusAngsuranController::class, 'batalkan'])->name('api.pengurus.angsuran.batalkan');
            Route::post('/pengurus/kas/topup', [PengurusKasController::class, 'topup'])->name('api.pengurus.kas.topup');
            Route::post('/pengurus/kas/pengeluaran', [PengurusKasController::class, 'pengeluaran'])->name('api.pengurus.kas.pengeluaran');
            Route::post('/pengurus/kas/sisihkan', [PengurusKasController::class, 'sisihkan'])->name('api.pengurus.kas.sisihkan');
            Route::post('/pengurus/kas/{jurnalKas}/batalkan', [PengurusKasController::class, 'batalkan'])->name('api.pengurus.kas.batalkan');
            Route::post('/pengurus/simpanan/konfirmasi', [PengurusSimpananController::class, 'konfirmasi'])->name('api.pengurus.simpanan.konfirmasi');
            Route::post('/pengurus/simpanan/{anggota}/bayar-dimuka', [PengurusSimpananController::class, 'bayarDiMuka'])->name('api.pengurus.simpanan.bayar-dimuka');
            Route::post('/pengurus/simpanan/{anggota}/batalkan', [PengurusSimpananController::class, 'batalkan'])->name('api.pengurus.simpanan.batalkan');

            // Tabungan pengurus & bunga bulanan
            Route::post('/pengurus/tabungan/buka/{rekening}/approve', [PengurusTabunganController::class, 'approveBuka'])->name('api.pengurus.tabungan.buka.approve');
            Route::post('/pengurus/tabungan/buka/{rekening}/reject', [PengurusTabunganController::class, 'rejectBuka'])->name('api.pengurus.tabungan.buka.reject');
            Route::post('/pengurus/tabungan/pengajuan/{pengajuan}/approve', [PengurusTabunganController::class, 'approvePengajuan'])->name('api.pengurus.tabungan.pengajuan.approve');
            Route::post('/pengurus/tabungan/pengajuan/{pengajuan}/cair', [PengurusTabunganController::class, 'cairPengajuan'])->name('api.pengurus.tabungan.pengajuan.cair');
            Route::post('/pengurus/tabungan/pengajuan/{pengajuan}/reject', [PengurusTabunganController::class, 'rejectPengajuan'])->name('api.pengurus.tabungan.pengajuan.reject');
            Route::post('/pengurus/tabungan/setor-tunai', [PengurusTabunganController::class, 'setorTunai'])->name('api.pengurus.tabungan.setor-tunai');
            Route::post('/pengurus/tabungan/transaksi/{transaksi}/batalkan', [PengurusTabunganController::class, 'batalkanTransaksi'])->name('api.pengurus.tabungan.transaksi.batalkan');
            Route::post('/pengurus/tabungan/bunga/posting', [PengurusTabunganController::class, 'postingBunga'])->name('api.pengurus.tabungan.bunga.posting');

            // Penghapusan pinjaman (write-off 5-tahap)
            Route::post('/pengurus/penghapusan-pinjaman', [PengurusPenghapusanPinjamanController::class, 'store'])->name('api.pengurus.penghapusan-pinjaman.store');
            Route::post('/pengurus/penghapusan-pinjaman/{pengajuan}/approve', [PengurusPenghapusanPinjamanController::class, 'approve'])->name('api.pengurus.penghapusan-pinjaman.approve');
            Route::post('/pengurus/penghapusan-pinjaman/{pengajuan}/reject', [PengurusPenghapusanPinjamanController::class, 'reject'])->name('api.pengurus.penghapusan-pinjaman.reject');

            // Manajemen anggota (tambah, edit/limit custom, resign, reaktivasi, cair resign)
            Route::post('/pengurus/anggota', [PengurusAnggotaKelolaController::class, 'store'])->name('api.pengurus.anggota.store');
            Route::put('/pengurus/anggota/{anggota}', [PengurusAnggotaKelolaController::class, 'update'])->name('api.pengurus.anggota.update');
            Route::post('/pengurus/anggota/{anggota}/resign', [PengurusAnggotaKelolaController::class, 'resign'])->name('api.pengurus.anggota.resign');
            Route::post('/pengurus/anggota/{anggota}/reaktivasi', [PengurusAnggotaKelolaController::class, 'reaktivasi'])->name('api.pengurus.anggota.reaktivasi');
            Route::post('/pengurus/anggota/{anggota}/cairkan-resign', [PengurusAnggotaKelolaController::class, 'cairkanResign'])->name('api.pengurus.anggota.cairkan-resign');

            Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->where('id', '[0-9a-f]{32}')->name('api.notifications.read');
            Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('api.notifications.read-all');
        });
    });
});
