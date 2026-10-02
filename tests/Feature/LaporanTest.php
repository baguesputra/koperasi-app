<?php

namespace Tests\Feature;

use App\Laporan\LaporanRegistry;
use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\JurnalKas;
use App\Models\Pinjaman;
use App\Models\Simpanan;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // ponytail: seed penuh rusak oleh CHECK constraint jurnal_kas lama (bug pre-existing),
        // cukup permission+role+user untuk halaman laporan
        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
        ]);
    }

    private function loginSebagai(string $noKaryawan): User
    {
        $user = User::where('no_karyawan', $noKaryawan)->firstOrFail();
        $this->actingAs($user);

        return $user;
    }

    public function test_tanpa_permission_ditolak(): void
    {
        $tanpaRole = User::factory()->create();
        $this->actingAs($tanpaRole);

        $this->get(route('laporan.index'))->assertForbidden();
    }

    public function test_index_tampil_untuk_bendahara(): void
    {
        $this->loginSebagai('BEN-000001');

        $this->get(route('laporan.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Laporan/Index')
                ->has('kelompok.Keuangan', 3));
    }

    public function test_arus_kas_basis_fisik_dengan_rekonsiliasi(): void
    {
        $user = $this->loginSebagai('BEN-000001');
        $bulanIni = now()->format('Y-m');
        $bulanLalu = now()->subMonth()->format('Y-m-d');

        JurnalKas::create([
            'tipe' => 'masuk', 'kategori' => 'topup_bulanan', 'kantong' => 'bank',
            'jumlah' => 5_000_000, 'saldo_setelah' => 5_000_000,
            'keterangan' => 'Topup bulan lalu', 'tanggal' => $bulanLalu, 'created_by' => $user->id,
        ]);
        JurnalKas::create([
            'tipe' => 'masuk', 'kategori' => 'pembayaran_angsuran', 'kantong' => 'pinjaman',
            'jumlah' => 1_000_000, 'saldo_setelah' => 6_000_000,
            'keterangan' => 'Angsuran tes', 'tanggal' => now(), 'created_by' => $user->id,
        ]);
        JurnalKas::create([
            'tipe' => 'keluar', 'kategori' => 'pencairan_pinjaman', 'kantong' => 'pinjaman',
            'jumlah' => 400_000, 'saldo_setelah' => 5_600_000,
            'keterangan' => 'Cair tes', 'tanggal' => now(), 'created_by' => $user->id,
        ]);
        JurnalKas::create([
            'tipe' => 'masuk', 'kategori' => 'pelunasan_resign_pinjaman', 'kantong' => 'pinjaman',
            'jumlah' => 2_000_000, 'saldo_setelah' => 0,
            'keterangan' => 'Non kas tes', 'tanggal' => now(), 'created_by' => $user->id,
        ]);

        $this->get(route('laporan.show', ['jenis' => 'arus-kas', 'dari' => $bulanIni, 'sampai' => $bulanIni]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Laporan/Show')
                ->where('hasil.kolom', ['Uraian', 'Nilai'])
                ->where('hasil.ringkasan.0.1', 'Rp 5.000.000')
                ->where('hasil.ringkasan.1.1', 'Rp 1.000.000')
                ->where('hasil.ringkasan.2.1', 'Rp 400.000')
                ->where('hasil.ringkasan.3.1', 'Rp 600.000')
                ->where('hasil.ringkasan.4.1', 'Rp 5.600.000')
                ->where('hasil.rows', fn ($rows) => collect($rows)->flatten()->contains('Saldo akhir aktual (buku kas)')));
    }

    public function test_arus_kas_filter_kantong_sebagai_label(): void
    {
        $user = $this->loginSebagai('BEN-000001');
        $bulanIni = now()->format('Y-m');

        JurnalKas::create([
            'tipe' => 'masuk', 'kategori' => 'topup_bulanan', 'kantong' => 'bank',
            'jumlah' => 1_000_000, 'saldo_setelah' => 1_000_000,
            'keterangan' => 'Topup tes', 'tanggal' => now(), 'created_by' => $user->id,
        ]);
        JurnalKas::create([
            'tipe' => 'masuk', 'kategori' => 'simpanan_wajib_masuk', 'kantong' => 'simpanan',
            'jumlah' => 200_000, 'saldo_setelah' => 1_200_000,
            'keterangan' => 'Wajib tes', 'tanggal' => now(), 'created_by' => $user->id,
        ]);

        $this->get(route('laporan.show', ['jenis' => 'arus-kas', 'dari' => $bulanIni, 'sampai' => $bulanIni, 'kantong' => 'bank']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('opsi.kantong.pinjaman', 'Dana Pinjaman')
                ->where('hasil.ringkasan.1.1', 'Rp 1.000.000')
                // Saldo akhir aktual = buku kas utuh (tanpa filter label).
                ->where('hasil.ringkasan.4.1', 'Rp 1.200.000'));

        $this->get(route('laporan.show', ['jenis' => 'arus-kas', 'dari' => $bulanIni, 'sampai' => $bulanIni]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('hasil.ringkasan.1.1', 'Rp 1.200.000'));
    }

    public function test_neraca_seimbang_aktiva_sama_pasiva(): void
    {
        $user = $this->loginSebagai('BEN-000001');
        $hariIni = now()->format('Y-m-d');

        $anggota = Anggota::create([
            'user_id' => null, 'no_anggota' => 'ANG-NRC-001', 'nama' => 'Uji Neraca',
            'cabang' => 'Banjarmasin', 'unit_bisnis' => 'Ops', 'jabatan' => 'staff',
            'tanggal_mulai_kerja' => now()->subYears(2), 'tanggal_jadi_anggota' => now()->subYears(2),
            'status' => 'aktif',
        ]);
        Simpanan::create([
            'anggota_id' => $anggota->id, 'jenis' => 'pokok', 'jumlah' => 100_000,
            'bulan_periode' => now()->format('Y-m'), 'tanggal_input' => now(), 'input_by' => $user->id,
        ]);
        Simpanan::create([
            'anggota_id' => $anggota->id, 'jenis' => 'wajib', 'jumlah' => 50_000,
            'bulan_periode' => now()->format('Y-m'), 'tanggal_input' => now(), 'input_by' => $user->id,
        ]);

        JurnalKas::create([
            'tipe' => 'masuk', 'kategori' => 'topup_bulanan', 'kantong' => 'bank',
            'jumlah' => 5_000_000, 'saldo_setelah' => 5_000_000,
            'keterangan' => 'Kas neraca', 'tanggal' => now(), 'created_by' => $user->id,
        ]);

        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id, 'nominal' => 1_200_000, 'tenor_bulan' => 3,
            'persentase_bunga' => 1, 'status' => 'aktif', 'tanggal_pengajuan' => $hariIni,
            'tanggal_pencairan' => $hariIni,
        ]);
        Angsuran::create([
            'pinjaman_id' => $pinjaman->id, 'cicilan_ke' => 1,
            'nominal_pokok' => 400_000, 'nominal_bunga' => 12_000, 'total_bayar' => 412_000,
            'tanggal_jatuh_tempo' => $hariIni, 'tanggal_konfirmasi_bayar' => $hariIni, 'status' => 'lunas',
        ]);
        Angsuran::create([
            'pinjaman_id' => $pinjaman->id, 'cicilan_ke' => 2,
            'nominal_pokok' => 400_000, 'nominal_bunga' => 8_000, 'total_bayar' => 408_000,
            'tanggal_jatuh_tempo' => $hariIni, 'status' => 'belum_bayar',
        ]);

        $def = LaporanRegistry::ambil('neraca');
        $hasil = $def['data'](request()->merge(['tanggal' => $hariIni]));

        $nilai = fn (string $pos) => collect($hasil['rows'])->firstWhere(fn ($r) => $r[0] === $pos)[1];

        // Aktiva: kas 5jt + piutang (1,2jt − 400rb) = 5,8jt.
        $this->assertEquals(800_000, $nilai('Piutang Pinjaman (sisa pokok)'));
        $this->assertEquals(5_800_000, $nilai('TOTAL AKTIVA'));
        // Pasiva: simpanan 150rb + dana sosial 0 + SHU (12rb − 0) + modal penyeimbang.
        $this->assertEquals(150_000, $nilai('Simpanan Anggota (Pokok + Wajib)'));
        $this->assertEquals(12_000, $nilai('SHU Tahun '.now()->year.' berjalan (bunga − beban)'));
        $this->assertEquals(5_638_000, $nilai('Modal (penyeimbang)'));
        $this->assertEquals(5_800_000, $nilai('TOTAL PASIVA'));

        $this->assertSame('Rp 0 — Seimbang', collect($hasil['ringkasan'])->firstWhere(fn ($r) => $r[0] === 'Selisih (Aktiva − Pasiva)')[1]);

        $this->get(route('laporan.show', ['jenis' => 'neraca', 'tanggal' => $hariIni]))->assertOk();
    }

    public function test_neraca_piutang_mengikuti_cutoff(): void
    {
        $this->loginSebagai('BEN-000001');
        $hariIni = now()->format('Y-m-d');

        $anggota = Anggota::create([
            'user_id' => null, 'no_anggota' => 'ANG-NRC-002', 'nama' => 'Uji Cutoff',
            'cabang' => 'Banjarmasin', 'unit_bisnis' => 'Ops', 'jabatan' => 'staff',
            'tanggal_mulai_kerja' => now()->subYears(2), 'tanggal_jadi_anggota' => now()->subYears(2),
            'status' => 'aktif',
        ]);
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id, 'nominal' => 900_000, 'tenor_bulan' => 3,
            'persentase_bunga' => 1, 'status' => 'aktif', 'tanggal_pengajuan' => $hariIni,
            'tanggal_pencairan' => $hariIni,
        ]);
        Angsuran::create([
            'pinjaman_id' => $pinjaman->id, 'cicilan_ke' => 1,
            'nominal_pokok' => 300_000, 'nominal_bunga' => 9_000, 'total_bayar' => 309_000,
            'tanggal_jatuh_tempo' => $hariIni, 'tanggal_konfirmasi_bayar' => $hariIni, 'status' => 'lunas',
        ]);

        $def = LaporanRegistry::ambil('neraca');

        // Cut-off kemarin: pinjaman belum cair → piutang 0.
        $kemarin = $def['data'](request()->merge(['tanggal' => now()->subDay()->format('Y-m-d')]));
        $nilaiKemarin = fn (string $pos) => collect($kemarin['rows'])->firstWhere(fn ($r) => $r[0] === $pos)[1];
        $this->assertEquals(0, $nilaiKemarin('Piutang Pinjaman (sisa pokok)'));

        // Cut-off hari ini: 900rb − 300rb = 600rb.
        $kini = $def['data'](request()->merge(['tanggal' => $hariIni]));
        $nilaiKini = fn (string $pos) => collect($kini['rows'])->firstWhere(fn ($r) => $r[0] === $pos)[1];
        $this->assertEquals(600_000, $nilaiKini('Piutang Pinjaman (sisa pokok)'));
    }

    public function test_export_excel_dan_pdf_berjalan(): void
    {
        $this->loginSebagai('BEN-000001');

        $this->get(route('laporan.export', ['jenis' => 'neraca', 'tanggal' => now()->format('Y-m-d')]))
            ->assertOk();

        $this->get(route('laporan.pdf', ['jenis' => 'neraca', 'tanggal' => now()->format('Y-m-d')]))
            ->assertOk();
    }

    public function test_audit_log_tersembunyi_dari_non_admin(): void
    {
        // Bendahara: tidak lihat kartu audit di index, dan akses langsung ditolak
        $this->loginSebagai('BEN-000001');

        $this->get(route('laporan.index'))
            ->assertInertia(fn ($page) => $page->component('Laporan/Index')
                ->has('kelompok.Operasional', 2));

        $this->get(route('laporan.show', 'audit-log'))->assertForbidden();
    }

    public function test_audit_log_tampil_untuk_admin(): void
    {
        $this->loginSebagai('ADM-000001'); // admin (punya pengaturan.kelola)

        $this->get(route('laporan.show', 'audit-log'))->assertOk();
    }

    public function test_rekap_iuran_pinjaman_satu_baris_per_anggota(): void
    {
        $this->loginSebagai('BEN-000001');
        $bulan = now()->format('Y-m');

        $anggota = Anggota::create([
            'user_id' => null, 'no_anggota' => 'ANG-LAP-001', 'nama' => 'Uji Rekap',
            'cabang' => 'Banjarmasin', 'unit_bisnis' => 'Ops', 'jabatan' => 'staff',
            'tanggal_mulai_kerja' => now()->subYears(2), 'tanggal_jadi_anggota' => now()->subYears(2),
            'status' => 'aktif',
        ]);
        Simpanan::create([
            'anggota_id' => $anggota->id, 'jenis' => 'wajib', 'jumlah' => 45_000,
            'bulan_periode' => $bulan, 'tanggal_input' => now(), 'input_by' => 1,
        ]);
        Simpanan::create([
            'anggota_id' => $anggota->id, 'jenis' => 'dana_sosial', 'jumlah' => 5_000,
            'bulan_periode' => $bulan, 'tanggal_input' => now(), 'input_by' => 1,
        ]);
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id, 'nominal' => 1_000_000, 'tenor_bulan' => 3,
            'persentase_bunga' => 1, 'status' => 'aktif', 'tanggal_pengajuan' => now()->format('Y-m-d'),
        ]);
        Angsuran::create([
            'pinjaman_id' => $pinjaman->id, 'cicilan_ke' => 1,
            'nominal_pokok' => 333_333, 'nominal_bunga' => 10_000, 'total_bayar' => 343_333,
            'tanggal_jatuh_tempo' => now()->format('Y-m-d'), 'status' => 'belum_bayar',
        ]);

        $def = LaporanRegistry::ambil('iuran-pinjaman-rekap');
        $hasil = $def['data'](request()->merge(['dari' => $bulan, 'sampai' => $bulan]));

        $baris = collect($hasil['rows'])->firstWhere(fn ($r) => $r[1] === 'Uji Rekap');
        $this->assertNotNull($baris);
        $this->assertEquals(0, $baris[2]); // iuran pokok
        $this->assertEquals(45_000, $baris[3]); // iuran wajib
        $this->assertEquals(5_000, $baris[4]); // asuransi sosial
        $this->assertEquals(333_333, $baris[5]); // pinjaman (pokok cicilan)
        $this->assertEquals(10_000, $baris[6]); // bunga
        $this->assertEquals(393_333, $baris[7]); // total
    }

    public function test_semua_laporan_dan_pdf_bisa_dirender(): void
    {
        $this->loginSebagai('ADM-000001');

        foreach (array_keys(LaporanRegistry::semua()) as $jenis) {
            $this->get(route('laporan.show', $jenis))->assertOk();
            $this->get(route('laporan.pdf', ['jenis' => $jenis, 'tanggal' => now()->format('Y-m-d')]))->assertOk();
            $this->get(route('laporan.export', ['jenis' => $jenis, 'dari' => now()->format('Y-m'), 'sampai' => now()->format('Y-m')]))->assertOk();
        }
    }
}
