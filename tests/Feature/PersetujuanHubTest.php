<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\PengajuanLimit;
use App\Models\PengajuanPenghapusanPinjaman;
use App\Models\PengajuanPercepatan;
use App\Models\PengajuanTransaksiTabungan;
use App\Models\Pinjaman;
use App\Models\RekeningTabungan;
use App\Models\Simpanan;
use App\Services\Pinjaman\PenghapusanPinjamanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class PersetujuanHubTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_semua_role_melihat_tab_persetujuan_sesuai_permission_dan_anggota_ditolak(): void
    {
        $anggota = $this->buatAnggota('HUB-900001');
        $this->actingAs($anggota->user);
        $this->get(route('persetujuan.index'))->assertForbidden();

        // Sekretaris: Pinjaman, Tabungan, Penghapusan
        $this->masuk('ADM-000001');
        $this->get(route('persetujuan.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Persetujuan/Index')
                ->where('tabs', fn ($tabs) => collect($tabs)->pluck('key')->all() === ['pinjaman', 'tabungan', 'penghapusan'])
            );

        // Bendahara: Pinjaman, Pencairan Dana, Tabungan, Ubah Tenor, Tambah Limit, Penghapusan
        $this->masuk('BEN-000001');
        $this->get(route('persetujuan.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Persetujuan/Index')
                ->where('tabs', fn ($tabs) => collect($tabs)->pluck('key')->all() === ['pinjaman', 'pencairan', 'tabungan', 'percepatan', 'limit', 'penghapusan'])
            );

        // Ketua Koperasi: Pinjaman, Tabungan, Ubah Tenor, Tambah Limit, Aktivasi, Penghapusan
        $this->masuk('KET-000001');
        $this->get(route('persetujuan.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Persetujuan/Index')
                ->where('tabs', fn ($tabs) => collect($tabs)->pluck('key')->all() === ['pinjaman', 'tabungan', 'percepatan', 'limit', 'aktivasi', 'penghapusan'])
            );

        // Pengawas (A3): hanya memiliki tab Penghapusan, filter ?kategori=pinjaman diabaikan menjadi 'semua'
        $this->masuk('WAS-000001');
        $this->get(route('persetujuan.index', ['kategori' => 'pinjaman']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Persetujuan/Index')
                ->where('tabs', fn ($tabs) => collect($tabs)->pluck('key')->all() === ['penghapusan'])
                ->where('viewAktif', 'menunggu')
                ->where('kategoriFilter', 'semua')
                ->where('tahapFilter', 'semua')
                ->where('inbox.kasRingkas', null)
            );

        // Super Admin: seluruh 7 kategori dengan multi-stage pill & inbox Konsep A lengkap beserta kasRingkas
        $this->masuk('SPADM-000001');
        $this->get(route('persetujuan.index', ['kategori' => 'pinjaman', 'tahap' => 'ketua']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Persetujuan/Index')
                ->where('tabs', fn ($tabs) => collect($tabs)->pluck('key')->all() === ['pinjaman', 'pencairan', 'tabungan', 'percepatan', 'limit', 'aktivasi', 'penghapusan'])
                ->where('viewAktif', 'menunggu')
                ->where('kategoriFilter', 'pinjaman')
                ->where('tahapFilter', 'ketua')
                ->has('tabs.0.stages', 3)
                ->has('inbox.antrean')
                ->where('inbox.riwayat', [])
                ->has('inbox.kasRingkas.saldo_bank')
                ->has('inbox.kasRingkas.saldo_kas_kecil')
                ->has('inbox.kasRingkas.total_antrean_pinjaman')
                ->has('inbox.kasRingkas.jumlah_antrean_pinjaman')
                ->has('inbox.kasRingkas.persen_terpakai')
                ->has('inbox.kasRingkas.sisa_proyeksi')
                ->has('inbox.kasRingkas.status_likuiditas')
            );
    }

    public function test_bendahara_total_persetujuan_tidak_ganda_antara_tab_tabungan_dan_pencairan(): void
    {
        Pinjaman::query()->delete();
        PengajuanTransaksiTabungan::query()->delete();
        RekeningTabungan::query()->delete();
        PengajuanPercepatan::query()->delete();
        PengajuanLimit::query()->delete();
        PengajuanPenghapusanPinjaman::query()->delete();
        Anggota::where('status_pencairan_resign', 'menunggu')->update(['status_pencairan_resign' => null]);

        $anggota = $this->buatAnggota('HUB-900002');
        $rekening = RekeningTabungan::create([
            'anggota_id' => $anggota->id,
            'no_rekening' => 'TAB-2026-0888',
            'status' => 'aktif',
            'saldo' => 1_000_000,
            'suku_bunga_saat_buka' => 2.00,
            'setuju_syarat' => true,
            'tanggal_pengajuan' => now(),
            'tanggal_buka' => now(),
        ]);

        // 1 setoran menunggu verifikasi Bendahara (kategori tabungan)
        PengajuanTransaksiTabungan::create([
            'rekening_tabungan_id' => $rekening->id,
            'anggota_id' => $anggota->id,
            'jenis' => 'setor',
            'kanal' => 'transfer',
            'nominal' => 250_000,
            'tanggal_transaksi' => now()->toDateString(),
            'status' => 'menunggu',
        ]);

        // 1 penarikan approved_ketua menunggu pencairan Bendahara (kategori pencairan)
        PengajuanTransaksiTabungan::create([
            'rekening_tabungan_id' => $rekening->id,
            'anggota_id' => $anggota->id,
            'jenis' => 'tarik',
            'kanal' => 'transfer',
            'nominal' => 300_000,
            'tanggal_transaksi' => now()->toDateString(),
            'status' => 'approved_ketua',
            'disetujui_ketua_pada' => now(),
        ]);

        // 1 pinjaman approved_ketua menunggu pencairan Bendahara (kategori pencairan)
        Pinjaman::create([
            'anggota_id' => $anggota->id,
            'nominal' => 2_000_000,
            'tenor_bulan' => 6,
            'persentase_bunga' => 1.5,
            'keperluan' => 'Tes pencairan hub',
            'snapshot_bank' => 'BCA',
            'snapshot_no_rekening' => '11223344',
            'snapshot_atas_nama' => $anggota->nama,
            'status' => 'approved_ketua',
            'tanggal_pengajuan' => now(),
            'disetujui_ketua_pada' => now(),
        ]);

        $this->masuk('BEN-000001');

        // Unified Single-Stream Inbox memuat tepat 3 antrean unik tanpa penghitungan ganda
        $this->get(route('persetujuan.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Persetujuan/Index')
                ->where('notifications.menunggu_pencairan_pinjaman', 1)
                ->where('notifications.menunggu_pencairan_tabungan', 1)
                ->where('notifications.menunggu_tabungan_bendahara', 1)
                ->where('notifications.menunggu_tabungan', 2) // backward compat
                ->where('notifications.total_persetujuan', 3) // non-overlapping exact sum: 1 + 1 + 1 = 3
                ->where('totalMenunggu', 3)
                ->where('viewAktif', 'menunggu')
                ->where('kategoriFilter', 'semua')
                ->has('inbox.antrean', 3)
                ->where('inbox.antrean', fn ($antrean) => collect($antrean)->pluck('jenis_kode')->sort()->values()->all() === ['cair_pinjaman', 'cair_tabungan', 'tabungan_setor']
                    && collect($antrean)->where('kategori', 'pencairan')->count() === 2
                    && collect($antrean)->where('kategori', 'tabungan')->count() === 1)
                ->where('inbox.kasRingkas.total_antrean_pinjaman', 2_000_000)
                ->where('inbox.kasRingkas.jumlah_antrean_pinjaman', 1)
            );
    }

    public function test_view_riwayat_hanya_dimuat_saat_tab_riwayat_dibuka(): void
    {
        Queue::fake();

        $anggota = $this->buatAnggota('HUB-900005');
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'nominal' => 1_800_000,
            'tenor_bulan' => 6,
            'persentase_bunga' => 1.5,
            'keperluan' => 'Tes lazy load riwayat hub',
            'snapshot_bank' => 'Mandiri',
            'snapshot_no_rekening' => '55667788',
            'snapshot_atas_nama' => $anggota->nama,
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
        ]);

        $this->masuk('ADM-000001');

        $this->from('/persetujuan?kategori=pinjaman&tahap=admin')
            ->post(route('admin.pinjaman.approve', $pinjaman), ['catatan' => 'Verifikasi riwayat hub.'])
            ->assertRedirect('/persetujuan?kategori=pinjaman&tahap=admin');

        $this->get(route('persetujuan.index', ['view' => 'riwayat']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Persetujuan/Index')
                ->where('viewAktif', 'riwayat')
                ->where('inbox.antrean', [])
                ->where('inbox.riwayat', fn ($riwayat) => collect($riwayat)->contains(
                    fn ($item) => $item['kategori'] === 'pinjaman'
                        && $item['tahap'] === 'admin'
                        && ($item['detail']['id'] ?? null) === $pinjaman->id
                ))
            );
    }

    public function test_pencairan_resign_masuk_ke_pencairan_dana_dan_riwayat_hub(): void
    {
        Queue::fake();
        Storage::fake('local');

        $anggota = $this->buatAnggota('HUB-900006');
        Simpanan::create([
            'anggota_id' => $anggota->id,
            'jenis' => 'pokok',
            'jumlah' => 300_000,
            'bulan_periode' => now()->format('Y-m'),
            'tanggal_input' => now(),
            'input_by' => 1,
        ]);

        // Sekretaris memproses resign -> menunggu pencairan Bendahara
        $this->masuk('ADM-000001');
        $this->post(route('anggota.resign', $anggota), [
            'alasan_resign' => 'Resign uji integrasi Pusat Persetujuan.',
            'tanggal_resign' => now()->format('Y-m-d'),
            'rekening_bank' => 'BCA',
            'rekening_nomor' => '8877665544',
            'rekening_atas_nama' => $anggota->nama,
        ])->assertRedirect(route('anggota.index'));

        // Bendahara melihat antrean cair_resign di Pusat Persetujuan & dapat mengunduh slip PDF
        $this->masuk('BEN-000001');
        $this->get(route('anggota.slip-resign', $anggota))->assertOk();

        $this->get(route('persetujuan.index', ['kategori' => 'pencairan']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Persetujuan/Index')
                ->where('notifications.menunggu_pencairan_resign', 1)
                ->where('inbox.antrean', fn ($antrean) => collect($antrean)->contains(
                    fn ($item) => $item['jenis_kode'] === 'cair_resign'
                        && ($item['detail']['id'] ?? null) === $anggota->id
                        && ($item['detail']['rekening']['no_rekening'] ?? null) === '8877665544'
                ))
            );

        // Eksekusi pencairan resign dari Pusat Persetujuan -> masuk ke tab Riwayat
        $this->from('/persetujuan?kategori=pencairan')
            ->post(route('bendahara.pencairan-resign.cair', $anggota), [
                'akun_kas' => 'kas_kecil',
                'tanggal_pencairan' => now()->format('Y-m-d'),
                'bukti_pencairan' => UploadedFile::fake()->image('bukti-kas-kecil.png'),
                'catatan' => 'Dibayar tunai kas kecil.',
            ])->assertRedirect('/persetujuan?kategori=pencairan');

        $this->get(route('persetujuan.index', ['view' => 'riwayat']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('notifications.menunggu_pencairan_resign', 0)
                ->where('inbox.riwayat', fn ($riwayat) => collect($riwayat)->contains(
                    fn ($item) => $item['jenis_kode'] === 'cair_resign'
                        && $item['status'] === 'dicairkan'
                        && ($item['detail']['id'] ?? null) === $anggota->id
                ))
            );
    }

    public function test_approve_dari_pusat_persetujuan_tetap_di_hub_dan_tolak_referer_eksternal(): void
    {
        Queue::fake();

        $anggota = $this->buatAnggota('HUB-900003');
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'nominal' => 1_500_000,
            'tenor_bulan' => 6,
            'persentase_bunga' => 1.5,
            'keperluan' => 'Renovasi rumah',
            'snapshot_bank' => 'BRI',
            'snapshot_no_rekening' => '99887766',
            'snapshot_atas_nama' => $anggota->nama,
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
        ]);

        $this->masuk('ADM-000001');

        // Aksi dari halaman Pusat Persetujuan harus kembali ke /persetujuan beserta query-nya
        $this->from('/persetujuan?kategori=pinjaman&tahap=admin')
            ->post(route('admin.pinjaman.approve', $pinjaman), ['catatan' => 'Valid dan lengkap.'])
            ->assertRedirect('/persetujuan?kategori=pinjaman&tahap=admin');

        $this->assertSame('approved_admin', $pinjaman->refresh()->status);

        // Referer eksternal tidak boleh memicu open redirect
        $pinjaman2 = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'nominal' => 1_000_000,
            'tenor_bulan' => 3,
            'persentase_bunga' => 1.5,
            'keperluan' => 'Keperluan mendadak',
            'snapshot_bank' => 'BRI',
            'snapshot_no_rekening' => '99887766',
            'snapshot_atas_nama' => $anggota->nama,
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
        ]);

        $this->withHeaders(['Referer' => 'https://evil.example/persetujuan?kategori=pinjaman'])
            ->post(route('admin.pinjaman.reject', $pinjaman2), ['catatan' => 'Ditolak uji keamanan referer.'])
            ->assertRedirect(route('admin.pinjaman.index'));
    }

    public function test_halaman_rekening_tabungan_tampilkan_operasional_hanya_untuk_pengelola(): void
    {
        // Sekretaris hanya punya tabungan.lihat (bukan tabungan.kelola): dipaksa tetap tab 'rekening'
        $this->masuk('ADM-000001');
        $this->get(route('tabungan.index', ['tab' => 'bunga']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Tabungan/Index')
                ->where('bisaKelola', false)
                ->where('tabAktif', 'rekening')
                ->where('previewBunga', null)
            );

        // Pengawas hanya view-only
        $this->masuk('WAS-000001');
        $this->get(route('tabungan.index', ['tab' => 'bunga']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Tabungan/Index')
                ->where('bisaKelola', false)
                ->where('tabAktif', 'rekening')
                ->where('previewBunga', null)
            );

        // Bendahara punya tabungan.kelola: mendapat opsi setor tunai + tab posting bunga bulanan
        $this->masuk('BEN-000001');
        $this->get(route('tabungan.index', ['tab' => 'bunga']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Tabungan/Index')
                ->where('bisaKelola', true)
                ->where('tabAktif', 'bunga')
                ->has('previewBunga.baris')
                ->has('rekeningAktifOptions')
            );
    }

    public function test_ringkasan_notifikasi_dimemoize_per_request_dan_fresh_antar_request(): void
    {
        Queue::fake();

        $anggota = $this->buatAnggota('HUB-900004');
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'nominal' => 1_200_000,
            'tenor_bulan' => 6,
            'persentase_bunga' => 1.5,
            'keperluan' => 'Tes memoization',
            'snapshot_bank' => 'BCA',
            'snapshot_no_rekening' => '77889900',
            'snapshot_atas_nama' => $anggota->nama,
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
        ]);

        $this->masuk('ADM-000001');

        DB::flushQueryLog();
        DB::enableQueryLog();

        $resAwal = $this->get(route('persetujuan.index', ['kategori' => 'pinjaman', 'tahap' => 'admin']));
        $resAwal->assertOk();

        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();

        // Hitung agregat group by milik PersetujuanHubService pada tabel pinjaman hanya dijalankan
        // SEKALI meski PersetujuanController dan HandleInertiaRequests sama-sama memanggil ringkasanNotifikasi().
        $pinjamanAggQueries = $queries->filter(
            fn ($q) => str_contains($q, 'cair_oleh_bendahara')
                && str_contains($q, 'group by')
                && ! str_contains($q, 'total_usulan')
        );
        $this->assertCount(1, $pinjamanAggQueries);

        $jumlahAwal = (int) $resAwal->viewData('page')['props']['notifications']['menunggu_tinjauan_admin'];

        // Approve pada request berikutnya harus membaca angka terbaru (tidak bocor antar-request)
        $this->from('/persetujuan?kategori=pinjaman&tahap=admin')
            ->post(route('admin.pinjaman.approve', $pinjaman), ['catatan' => 'Verifikasi oke.'])
            ->assertRedirect('/persetujuan?kategori=pinjaman&tahap=admin');

        $this->get(route('persetujuan.index', ['kategori' => 'pinjaman', 'tahap' => 'admin']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('notifications.menunggu_tinjauan_admin', $jumlahAwal - 1)
            );
    }

    public function test_pinjaman_menunggak_dimuat_lazy_via_optional_dan_jadwal_aktif_tanpa_n_plus_1(): void
    {
        Angsuran::query()->delete();
        Pinjaman::query()->delete();

        $anggota = $this->buatAnggota('HUB-900010');
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'nominal' => 1_200_000,
            'tenor_bulan' => 3,
            'persentase_bunga' => 1.5,
            'keperluan' => 'Uji lazy prop dan jadwalAktif eager',
            'snapshot_bank' => 'BCA',
            'snapshot_no_rekening' => '11220011',
            'snapshot_atas_nama' => $anggota->nama,
            'status' => 'aktif',
            'tanggal_pengajuan' => now()->subMonths(2),
            'tanggal_pencairan' => now()->subMonths(2),
        ]);

        for ($i = 1; $i <= 3; $i++) {
            Angsuran::create([
                'pinjaman_id' => $pinjaman->id,
                'cicilan_ke' => $i,
                'nominal_pokok' => 400_000,
                'nominal_bunga' => 18_000,
                'total_bayar' => 418_000,
                'status' => $i === 1 ? 'lunas' : 'belum_bayar',
                'tanggal_jatuh_tempo' => now()->subMonths(2 - $i)->toDateString(),
            ]);
        }

        // 1. Kunjungan awal /persetujuan oleh Sekretaris: bisa lihat tab Penghapusan, namun tidak bisa mengajukan
        $this->masuk('ADM-000001');
        $this->get(route('persetujuan.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Persetujuan/Index')
                ->where('inbox.bisaAjukanPenghapusan', false)
                ->missing('inbox.pinjamanMenunggak')
                ->missing('pinjamanMenunggak')
            );

        // Ketua Koperasi: bisaAjukanPenghapusan = true & pinjamanMenunggak dimuat lazy
        $this->masuk('KET-000001');
        $this->get(route('persetujuan.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Persetujuan/Index')
                ->where('inbox.bisaAjukanPenghapusan', true)
                ->missing('inbox.pinjamanMenunggak')
                ->missing('pinjamanMenunggak')
                ->reloadOnly(['pinjamanMenunggak'], fn (AssertableInertia $reload) => $reload
                    ->has('pinjamanMenunggak', 1)
                    ->where('pinjamanMenunggak.0.pinjaman_id', $pinjaman->id)
                    ->where('pinjamanMenunggak.0.sisa_cicilan', 2)
                )
            );

        // 2. Pastikan Pinjaman::jadwalAktif() dan agregatJadwalAktif() memakai relasi eager (0 query tambahan)
        $loaded = Pinjaman::with([
            'angsuran' => fn ($q) => $q->where('status', '!=', 'digantikan')->orderBy('cicilan_ke'),
            'pengajuanPercepatan' => fn ($q) => $q->where('status', 'aktif')->latest()->with('angsuranBaru'),
        ])->findOrFail($pinjaman->id);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $agg = $loaded->agregatJadwalAktif();
        $sisaCount = $loaded->sisaAngsuran();
        $queriesAfterEager = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(0, $queriesAfterEager);
        $this->assertSame(3, $agg['total']);
        $this->assertSame(2, $agg['sisa']);
        $this->assertSame(2, $sisaCount);

        // 3. daftarPinjamanMenunggak() tetap menghitung rincian secara akurat dalam query konstan (tanpa N+1)
        DB::flushQueryLog();
        DB::enableQueryLog();
        $menunggakList = app(PenghapusanPinjamanService::class)->daftarPinjamanMenunggak();
        $menunggakQueries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(1, $menunggakList);
        $this->assertSame(800_000.0, (float) $menunggakList[0]['sisa_pokok']);
        $this->assertLessThanOrEqual(5, count($menunggakQueries));
    }
}
