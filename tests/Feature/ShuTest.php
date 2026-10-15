<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\Pinjaman;
use App\Models\ShuDetail;
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

        $service->finalisasi($periode, $user->id);
        $this->assertTrue($periode->refresh()->isFinal());

        // Ubah persen setelah final → ditolak.
        try {
            $service->ubahPersen($periode->refresh(), ShuService::PERSEN_DEFAULT, $user->id);
            $this->fail('Ubah persen setelah final harus ditolak.');
        } catch (\RuntimeException) {
            $this->assertTrue(true);
        }

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
}
