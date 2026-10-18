<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\Pinjaman;
use App\Models\ShuDetail;
use App\Models\ShuKomponenPembayaran;
use App\Models\ShuPeriode;
use App\Models\Simpanan;
use App\Models\User;
use App\Services\Keuangan\ShuService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
        ]);
        KasKoperasi::create([
            'saldo_pinjaman' => 0, 'saldo_dana_sosial' => 0,
            'saldo_pengembalian_simpanan' => 0, 'saldo_simpanan' => 0,
            'saldo_bank' => 100_000_000, 'saldo_kas_kecil' => 5_000_000,
        ]);
    }

    private function admin(): User
    {
        $user = User::where('no_karyawan', 'ADM-000001')->firstOrFail();
        $this->actingAs($user);

        return $user;
    }

    private function buatAnggota(string $nama, string $no): Anggota
    {
        return Anggota::create([
            'user_id' => null, 'no_anggota' => $no, 'no_karyawan' => $no, 'nama' => $nama,
            'cabang' => 'Banjarmasin', 'unit_bisnis' => 'Ops', 'jabatan' => 'staff',
            'tanggal_mulai_kerja' => now()->subYears(2), 'tanggal_jadi_anggota' => now()->subYears(2),
            'status' => 'aktif',
        ]);
    }

    private function setor(Anggota $anggota, string $jenis, float $jumlah, string $tanggal, int $userId): void
    {
        Simpanan::create([
            'anggota_id' => $anggota->id, 'jenis' => $jenis, 'jumlah' => $jumlah,
            'bulan_periode' => substr($tanggal, 0, 7), 'tanggal_input' => $tanggal, 'input_by' => $userId,
        ]);
    }

    public function test_hitung_mirror_rumus_excel(): void
    {
        $user = $this->admin();
        $tahun = (int) now()->subYear()->format('Y');

        // A: modal 1.310.000 (50rb pokok + 1.260.000 wajib), tanpa transaksi.
        $a = $this->buatAnggota('Anggota A', 'ANG-SHU-A');
        $this->setor($a, 'pokok', 50_000, "{$tahun}-01-15", $user->id);
        $this->setor($a, 'wajib', 1_260_000, "{$tahun}-06-15", $user->id);

        // B: modal 680.000, transaksi cair 7.800.000 tahun berjalan.
        $b = $this->buatAnggota('Anggota B', 'ANG-SHU-B');
        $this->setor($b, 'pokok', 50_000, "{$tahun}-01-15", $user->id);
        $this->setor($b, 'wajib', 630_000, "{$tahun}-06-15", $user->id);
        Pinjaman::create([
            'anggota_id' => $b->id, 'pengaju_user_id' => $user->id,
            'nominal' => 7_800_000, 'tenor_bulan' => 10, 'keperluan' => 'Uji',
            'persentase_bunga' => 1, 'status' => 'aktif',
            'tanggal_pengajuan' => "{$tahun}-03-01", 'tanggal_pencairan' => "{$tahun}-03-05",
        ]);
        // Pinjaman tahun lain tidak ikut transaksi.
        Pinjaman::create([
            'anggota_id' => $b->id, 'pengaju_user_id' => $user->id,
            'nominal' => 99_000_000, 'tenor_bulan' => 10, 'keperluan' => 'Lama',
            'persentase_bunga' => 1, 'status' => 'lunas',
            'tanggal_pengajuan' => '2020-01-01', 'tanggal_pencairan' => '2020-01-05',
        ]);

        // C: resign → diexclude walau punya simpanan.
        $c = $this->buatAnggota('Anggota C', 'ANG-SHU-C');
        $c->update(['status' => 'resign']);
        $this->setor($c, 'wajib', 90_000, "{$tahun}-06-15", $user->id);

        $service = app(ShuService::class);
        $periode = $service->buatPeriode($tahun, null, 50_885_180.62, $user->id);
        $ringkasan = $service->hitung($periode, $user->id);

        $this->assertSame(2, $ringkasan['anggota']);
        $this->assertEquals(1_310_000 + 680_000, $ringkasan['total_modal']);
        $this->assertEquals(7_800_000, $ringkasan['total_transaksi']);

        // Pool Excel: jasa simpanan 20% dan jasa transaksi 40%.
        $poolSimpanan = round(50_885_180.62 * 0.20, 2);
        $poolTransaksi = round(50_885_180.62 * 0.40, 2);

        $detailA = ShuDetail::where('shu_periode_id', $periode->id)->where('anggota_id', $a->id)->firstOrFail();
        $detailB = ShuDetail::where('shu_periode_id', $periode->id)->where('anggota_id', $b->id)->firstOrFail();

        $expA = 1_310_000 / 1_990_000 * $poolSimpanan;
        $this->assertEqualsWithDelta($expA, (float) $detailA->shu_modal, 1);
        $this->assertEquals(0.0, (float) $detailA->shu_transaksi);
        $this->assertEquals(floor(((float) $detailA->shu_bruto) / 1000) * 1000, (float) $detailA->shu_bersih);

        // B satu-satunya transaksi → dapat penuh pool transaksi.
        $this->assertEqualsWithDelta($poolTransaksi, (float) $detailB->shu_transaksi, 1);

        // Total bruto = pool anggota (proporsional penuh).
        $this->assertEqualsWithDelta($poolSimpanan + $poolTransaksi, $ringkasan['total_bruto'], 1);
    }

    public function test_finalisasi_kunci_dan_bayar_catat_jurnal(): void
    {
        $user = $this->admin();
        $tahun = (int) now()->subYear()->format('Y');

        $a = $this->buatAnggota('Anggota Bayar', 'ANG-SHU-D');
        $this->setor($a, 'pokok', 50_000, "{$tahun}-01-15", $user->id);
        $this->setor($a, 'wajib', 950_000, "{$tahun}-06-15", $user->id);

        $service = app(ShuService::class);
        $periode = $service->buatPeriode($tahun, null, 10_000_000, $user->id);
        $service->hitung($periode, $user->id);

        // Bayar sebelum final → ditolak.
        $detail = ShuDetail::where('shu_periode_id', $periode->id)->firstOrFail();
        try {
            $service->bayarDetail($detail, 'bank', "{$tahun}-12-31", $user->id);
            $this->fail('Bayar sebelum final harus ditolak.');
        } catch (\RuntimeException) {
            $this->assertTrue(true);
        }

        // Finalisasi langsung dari draft → ditolak (wajib Diajukan dulu).
        try {
            $service->finalisasi($periode, $user->id);
            $this->fail('Finalisasi dari draft harus ditolak.');
        } catch (\RuntimeException) {
            $this->assertTrue(true);
        }

        $service->ajukan($periode, $user->id);
        $this->assertSame('diajukan', $periode->refresh()->status);

        // Ubah persen setelah diajukan → ditolak.
        try {
            $service->ubahPersen($periode->refresh(), ShuService::PERSEN_DEFAULT, $user->id);
            $this->fail('Ubah persen setelah diajukan harus ditolak.');
        } catch (\RuntimeException) {
            $this->assertTrue(true);
        }

        $service->finalisasi($periode->refresh(), $user->id);
        $this->assertTrue($periode->refresh()->isFinal());

        $saldoSebelum = (float) KasKoperasi::first()->saldo_bank;
        $service->bayarDetail($detail->refresh(), 'bank', "{$tahun}-12-31", $user->id);

        $detail->refresh();
        $this->assertSame('sudah', $detail->status_bayar);
        $this->assertNotNull($detail->jurnal_id);

        $jurnal = JurnalKas::findOrFail($detail->jurnal_id);
        $this->assertSame('pembayaran_shu', $jurnal->kategori);
        $this->assertSame('keluar', $jurnal->tipe);
        $this->assertEquals((float) $detail->shu_bersih, (float) $jurnal->jumlah);
        $this->assertEqualsWithDelta($saldoSebelum - (float) $detail->shu_bersih, (float) KasKoperasi::first()->saldo_bank, 1);

        // Bayar dua kali → ditolak.
        try {
            $service->bayarDetail($detail->refresh(), 'bank', "{$tahun}-12-31", $user->id);
            $this->fail('Bayar ganda harus ditolak.');
        } catch (\RuntimeException) {
            $this->assertTrue(true);
        }
    }

    public function test_aju_kembali_dan_hitung_ulang_ditolak(): void
    {
        $user = $this->admin();
        $tahun = (int) now()->subYear()->format('Y');

        $service = app(ShuService::class);
        $periode = $service->buatPeriode($tahun, null, 5_000_000, $user->id);

        // Ajukan tanpa rincian hitung → ditolak.
        try {
            $service->ajukan($periode, $user->id);
            $this->fail('Ajukan tanpa hitung harus ditolak.');
        } catch (\RuntimeException) {
            $this->assertTrue(true);
        }

        $a = $this->buatAnggota('Anggota Aju', 'ANG-SHU-E');
        $this->setor($a, 'pokok', 50_000, "{$tahun}-02-15", $user->id);
        $service->hitung($periode, $user->id);
        $service->ajukan($periode, $user->id);

        // Hitung ulang setelah diajukan → ditolak.
        try {
            $service->hitung($periode->refresh(), $user->id);
            $this->fail('Hitung ulang setelah diajukan harus ditolak.');
        } catch (\RuntimeException) {
            $this->assertTrue(true);
        }

        // Kembalikan → bisa diubah lagi.
        $service->kembalikanKeDraft($periode->refresh(), $user->id);
        $this->assertSame('draft', $periode->refresh()->status);
        $service->hitung($periode->refresh(), $user->id);
        $this->assertSame(1, $periode->detail()->count());

        // Kembalikan dari draft → ditolak.
        try {
            $service->kembalikanKeDraft($periode->refresh(), $user->id);
            $this->fail('Kembalikan dari draft harus ditolak.');
        } catch (\RuntimeException) {
            $this->assertTrue(true);
        }
    }

    public function test_buat_periode_langsung_terhitung(): void
    {
        $user = $this->admin();
        $tahun = (int) now()->subYear()->format('Y');

        $a = $this->buatAnggota('Anggota Auto', 'ANG-SHU-F');
        $this->setor($a, 'pokok', 50_000, "{$tahun}-02-15", $user->id);

        $res = $this->post(route('shu.store'), ['tahun_buku' => $tahun]);

        $periode = ShuPeriode::where('tahun_buku', $tahun)->firstOrFail();
        $res->assertRedirect(route('shu.show', ['shu' => $periode->id, 'tab' => 'distribusi']));
        $this->assertSame(1, $periode->detail()->count());
    }

    public function test_distribusi_default_urut_no_dan_export_mirror_excel(): void
    {
        $user = $this->admin();
        $tahun = (int) now()->subYear()->format('Y');

        $a = $this->buatAnggota('Anggota Awal', 'ANG-SHU-G');
        $b = $this->buatAnggota('Anggota Akhir', 'ANG-SHU-H');
        $this->setor($a, 'pokok', 50_000, "{$tahun}-02-15", $user->id);
        $this->setor($b, 'pokok', 50_000, "{$tahun}-02-15", $user->id);

        $service = app(ShuService::class);
        $periode = $service->buatPeriode($tahun, null, 10_000_000, $user->id);
        $service->hitung($periode, $user->id);

        $this->get(route('shu.show', $periode))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Shu/Show')
                ->where('detail.data.0.nama', 'Anggota Awal')
                ->where('detail.data.1.nama', 'Anggota Akhir')
                ->where('detail.data.0.no', 1)
                ->where('detail.data.1.no', 2));

        $res = $this->get(route('shu.export', $periode));
        $res->assertOk();
    }

    public function test_pengurus_dari_anggota_disnapshot_namanya(): void
    {
        $user = $this->admin();
        $tahun = (int) now()->subYear()->format('Y');

        $a = $this->buatAnggota('Calon Pengurus', 'ANG-SHU-I');

        $service = app(ShuService::class);
        $periode = $service->buatPeriode($tahun, null, 10_000_000, $user->id);
        $service->simpanRincianPengelola(
            $periode,
            [['anggota_id' => $a->id, 'jabatan' => 'Ketua', 'nominal' => 1_000_000]],
            [['nama' => 'Pengawas Luar', 'nominal' => 900_000]],
            $user->id,
        );

        $rincian = $periode->refresh()->pengurus_rincian;
        $this->assertSame($a->id, $rincian[0]['anggota_id']);
        $this->assertSame('Calon Pengurus', $rincian[0]['nama']);
        $this->assertSame('Ketua', $rincian[0]['jabatan']);
        $this->assertSame('Pengawas Luar', $periode->pengawas_rincian[0]['nama']);
    }

    public function test_persen_harus_total_100(): void
    {
        $user = $this->admin();
        $tahun = (int) now()->subYear()->format('Y');

        try {
            app(ShuService::class)->buatPeriode($tahun, ['pct_cadangan' => 0.5], 1_000_000, $user->id);
            $this->fail('Persen tak 100% harus ditolak.');
        } catch (\RuntimeException) {
            $this->assertTrue(true);
        }

        $this->assertFalse(ShuPeriode::where('tahun_buku', $tahun)->exists());
    }

    public function test_komponen_tambahan_masuk_dana_tak_dibagi(): void
    {
        $user = $this->admin();
        $tahun = (int) now()->subYear()->format('Y');

        $persen = ShuService::PERSEN_DEFAULT;
        // Ambil 3% dari wilker untuk komponen tambahan.
        $persen['pct_wilker'] = 0.02;

        $service = app(ShuService::class);
        $periode = $service->buatPeriode($tahun, $persen, 10_000_000, $user->id, [
            ['label' => 'Dana Khusus', 'persen' => 0.03],
        ]);

        $this->assertSame(1, $periode->customKomponen()->count());
        $ringkasan = $service->ringkasan($periode);

        // Nominal custom = 3% × total.
        $this->assertEqualsWithDelta(300_000, $ringkasan['komponen']['dana_khusus'] ?? 0, 1);
        // Dana dibagi tetap pool + pengurus + pengawas; custom ikut tak dibagi.
        $this->assertEqualsWithDelta(
            10_000_000 - $ringkasan['dana_dibagi'],
            $ringkasan['dana_tak_dibagi'],
            1
        );
        $this->assertCount(1, $ringkasan['custom']);
        $this->assertSame('Dana Khusus', $ringkasan['custom'][0]['label']);
    }

    public function test_komponen_tambahan_total_tetap_100_dan_tolak_nama_bawaan(): void
    {
        $user = $this->admin();
        $tahun = (int) now()->subYear()->format('Y');
        $service = app(ShuService::class);

        // Total 103% → ditolak.
        try {
            $service->buatPeriode($tahun, ShuService::PERSEN_DEFAULT, 1_000_000, $user->id, [
                ['label' => 'Dana X', 'persen' => 0.03],
            ]);
            $this->fail('Total >100% harus ditolak.');
        } catch (\RuntimeException) {
            $this->assertTrue(true);
        }

        // Nama menyerupai bawaan → ditolak.
        try {
            $persen = ShuService::PERSEN_DEFAULT;
            $persen['pct_wilker'] = 0.0;
            $service->buatPeriode($tahun, $persen, 1_000_000, $user->id, [
                ['label' => 'Cadangan', 'persen' => 0.05],
            ]);
            $this->fail('Nama bawaan harus ditolak.');
        } catch (\RuntimeException) {
            $this->assertTrue(true);
        }

        $this->assertFalse(ShuPeriode::where('tahun_buku', $tahun)->exists());
    }

    public function test_komponen_tambahan_bisa_dihapus_bawaan_tetap_ada(): void
    {
        $user = $this->admin();
        $tahun = (int) now()->subYear()->format('Y');
        $service = app(ShuService::class);

        $persen = ShuService::PERSEN_DEFAULT;
        $persen['pct_wilker'] = 0.02;
        $periode = $service->buatPeriode($tahun, $persen, 5_000_000, $user->id, [
            ['label' => 'Dana Sementara', 'persen' => 0.03],
        ]);
        $this->assertSame(1, $periode->refresh()->customKomponen()->count());

        // Hapus custom dengan sync kosong + kembalikan wilker ke 5%.
        $service->ubahPersen($periode->refresh(), ShuService::PERSEN_DEFAULT, $user->id, []);
        $this->assertSame(0, $periode->refresh()->customKomponen()->count());

        // 8 bawaan tetap utuh.
        $this->assertEqualsWithDelta(1.0, $periode->refresh()->totalPersen(), 0.0001);
        foreach (ShuPeriode::KOMPONEN as $kolom) {
            $this->assertNotNull($periode->{$kolom});
        }
    }

    public function test_finalisasi_otomatis_sync_komponen_pembayaran_kecuali_cadangan_dan_anggota(): void
    {
        $user = $this->admin();
        $tahun = (int) now()->subYear()->format('Y');

        $a = $this->buatAnggota('Anggota Test Sync', 'ANG-SHU-SYNC');
        $this->setor($a, 'pokok', 50_000, "{$tahun}-02-15", $user->id);

        $service = app(ShuService::class);
        $periode = $service->buatPeriode($tahun, null, 10_000_000, $user->id);
        $service->hitung($periode, $user->id);
        $service->ajukan($periode, $user->id);
        $service->finalisasi($periode, $user->id);

        $komponenBayar = $periode->pembayaranKomponen()->get();
        $kodes = $komponenBayar->pluck('kode_komponen')->all();
        $this->assertNotContains('cadangan', $kodes);
        $this->assertNotContains('jasa_simpanan', $kodes);
        $this->assertNotContains('jasa_transaksi', $kodes);

        $this->assertContains('pengurus', $kodes);
        $this->assertContains('pengawas', $kodes);
        $this->assertContains('pendidikan', $kodes);
        $this->assertContains('wilker', $kodes);
    }

    public function test_bayar_komponen_catat_jurnal_dan_kurangi_kas_fisik_dengan_rincian(): void
    {
        $user = $this->admin();
        $tahun = (int) now()->subYear()->format('Y');

        $a = $this->buatAnggota('Pengurus Satu', 'ANG-SHU-P1');
        $this->setor($a, 'pokok', 50_000, "{$tahun}-02-15", $user->id);

        $service = app(ShuService::class);
        $periode = $service->buatPeriode($tahun, null, 10_000_000, $user->id);
        $service->simpanRincianPengelola($periode, [
            ['anggota_id' => $a->id, 'nama' => $a->nama, 'jabatan' => 'Ketua', 'nominal' => 1_000_000],
            ['anggota_id' => null, 'nama' => 'Pengurus Luar', 'jabatan' => 'Sekretaris', 'nominal' => 500_000],
        ], null, $user->id);

        $service->hitung($periode, $user->id);
        $service->ajukan($periode, $user->id);
        $service->finalisasi($periode, $user->id);

        $bayarPengurus = ShuKomponenPembayaran::where('shu_periode_id', $periode->id)
            ->where('kode_komponen', 'pengurus')
            ->firstOrFail();

        $saldoSebelum = (float) KasKoperasi::first()->saldo_bank;
        $service->bayarKomponen($bayarPengurus, 'bank', "{$tahun}-12-31", $user->id);

        $bayarPengurus->refresh();
        $this->assertSame('sudah', $bayarPengurus->status_bayar);
        $this->assertNotNull($bayarPengurus->jurnal_id);

        $jurnal = JurnalKas::findOrFail($bayarPengurus->jurnal_id);
        $this->assertSame('pembayaran_shu', $jurnal->kategori);
        $this->assertSame('keluar', $jurnal->tipe);
        $this->assertEquals((float) $bayarPengurus->nominal, (float) $jurnal->jumlah);
        $this->assertStringContainsString('Pengurus Satu', $jurnal->sub_judul);
        $this->assertStringContainsString('Ketua', $jurnal->sub_judul);
        $this->assertEqualsWithDelta($saldoSebelum - (float) $bayarPengurus->nominal, (float) KasKoperasi::first()->saldo_bank, 1);

        try {
            $service->bayarKomponen($bayarPengurus, 'bank', "{$tahun}-12-31", $user->id);
            $this->fail('Bayar ganda komponen harus ditolak.');
        } catch (\RuntimeException) {
            $this->assertTrue(true);
        }
    }

    public function test_bayar_semua_komponen_sekaligus(): void
    {
        $user = $this->admin();
        $tahun = (int) now()->subYear()->format('Y');

        $a = $this->buatAnggota('Anggota Tes Massal', 'ANG-SHU-M1');
        $this->setor($a, 'pokok', 50_000, "{$tahun}-02-15", $user->id);

        $service = app(ShuService::class);
        $periode = $service->buatPeriode($tahun, null, 10_000_000, $user->id);
        $service->hitung($periode, $user->id);
        $service->ajukan($periode, $user->id);
        $service->finalisasi($periode, $user->id);

        $count = $service->bayarSemuaKomponen($periode, 'bank', "{$tahun}-12-31", $user->id);
        $this->assertGreaterThan(0, $count);

        $sisaBelum = ShuKomponenPembayaran::where('shu_periode_id', $periode->id)
            ->where('status_bayar', 'belum')
            ->where('nominal', '>', 0)
            ->count();
        $this->assertSame(0, $sisaBelum);
    }
}
