<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\Pengeluaran;
use App\Models\Pinjaman;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LaporanLabaRugiTest extends TestCase
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
    }

    private function bendahara(): User
    {
        $user = User::where('no_karyawan', 'BEN-000001')->firstOrFail();
        $this->actingAs($user);

        return $user;
    }

    private function buatPinjaman(Anggota $anggota, int $userId): Pinjaman
    {
        return Pinjaman::create([
            'anggota_id' => $anggota->id, 'pengaju_user_id' => $userId,
            'nominal' => 1_000_000, 'tenor_bulan' => 2, 'keperluan' => 'Uji',
            'persentase_bunga' => 1, 'status' => 'aktif',
            'tanggal_pengajuan' => now()->format('Y-m-d'),
        ]);
    }

    public function test_laba_rugi_mirror_excel_dengan_rincian_beban(): void
    {
        $user = $this->bendahara();
        $lalu = (string) now()->subYear()->format('Y');

        $anggota = Anggota::create([
            'user_id' => null, 'no_anggota' => 'ANG-LR-001', 'nama' => 'Uji Laba',
            'cabang' => 'Banjarmasin', 'unit_bisnis' => 'Ops', 'jabatan' => 'staff',
            'tanggal_mulai_kerja' => now()->subYears(2), 'tanggal_jadi_anggota' => now()->subYears(2),
            'status' => 'aktif',
        ]);
        $pinjaman = $this->buatPinjaman($anggota, $user->id);

        // Feb: bunga 100rb lunas.
        Angsuran::create([
            'pinjaman_id' => $pinjaman->id, 'cicilan_ke' => 1,
            'nominal_pokok' => 250_000, 'nominal_bunga' => 100_000, 'total_bayar' => 350_000,
            'status' => 'lunas', 'tanggal_jatuh_tempo' => "{$lalu}-02-05",
            'tanggal_konfirmasi_bayar' => "{$lalu}-02-15",
        ]);
        // Mar: bunga bank 25rb.
        JurnalKas::create([
            'tipe' => 'masuk', 'kategori' => 'topup_bulanan', 'sumber_topup' => 'bunga_bank', 'kantong' => 'bank',
            'jumlah' => 25_000, 'saldo_setelah' => 25_000,
            'keterangan' => 'Bunga bank tes', 'tanggal' => "{$lalu}-03-10", 'created_by' => $user->id,
        ]);
        // Beban: pajak 40rb (Feb) + perlengkapan 170rb (Mar).
        Pengeluaran::create([
            'jenis' => 'koperasi', 'tipe' => 'pajak', 'akun' => 'bank', 'jumlah' => 40_000,
            'keterangan' => 'Pajak tes', 'tanggal' => "{$lalu}-02-20", 'input_by' => $user->id,
        ]);
        JurnalKas::create([
            'tipe' => 'keluar', 'kategori' => 'pengeluaran_koperasi', 'kantong' => 'bank',
            'jumlah' => 40_000, 'saldo_setelah' => 0,
            'keterangan' => 'Pajak tes', 'tanggal' => "{$lalu}-02-20", 'created_by' => $user->id,
        ]);
        Pengeluaran::create([
            'jenis' => 'koperasi', 'tipe' => 'perlengkapan', 'akun' => 'bank', 'jumlah' => 170_000,
            'keterangan' => 'Perlengkapan tes', 'tanggal' => "{$lalu}-03-11", 'input_by' => $user->id,
        ]);
        JurnalKas::create([
            'tipe' => 'keluar', 'kategori' => 'pengeluaran_koperasi', 'kantong' => 'bank',
            'jumlah' => 170_000, 'saldo_setelah' => 0,
            'keterangan' => 'Perlengkapan tes', 'tanggal' => "{$lalu}-03-11", 'created_by' => $user->id,
        ]);

        // Kontrol negatif: tidak boleh masuk laba.
        Angsuran::create([
            'pinjaman_id' => $pinjaman->id, 'cicilan_ke' => 2,
            'nominal_pokok' => 250_000, 'nominal_bunga' => 999_000, 'total_bayar' => 1_249_000,
            'status' => 'belum_bayar', 'tanggal_jatuh_tempo' => "{$lalu}-02-05",
            'tanggal_konfirmasi_bayar' => "{$lalu}-02-15",
        ]);
        Angsuran::create([
            'pinjaman_id' => $pinjaman->id, 'cicilan_ke' => 3,
            'nominal_pokok' => 250_000, 'nominal_bunga' => 888_000, 'total_bayar' => 1_138_000,
            'status' => 'lunas', 'tanggal_jatuh_tempo' => "{$lalu}-02-05",
            'tanggal_konfirmasi_bayar' => null,
        ]);
        JurnalKas::create([
            'tipe' => 'masuk', 'kategori' => 'topup_bulanan', 'sumber_topup' => 'modal', 'kantong' => 'bank',
            'jumlah' => 999_000, 'saldo_setelah' => 999_000,
            'keterangan' => 'Modal bukan pendapatan', 'tanggal' => "{$lalu}-04-01", 'created_by' => $user->id,
        ]);
        Pengeluaran::create([
            'jenis' => 'dana_sosial', 'tipe' => 'santunan', 'akun' => 'bank', 'jumlah' => 77_000,
            'keterangan' => 'Santunan bukan beban SHU', 'tanggal' => "{$lalu}-05-01", 'input_by' => $user->id,
        ]);

        $this->get(route('laporan.show', ['jenis' => 'laba-rugi', 'tahun' => $lalu]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Laporan/Show')
                ->where('hasil.kolom.0', 'Uraian')
                ->where('hasil.kolom.13', 'Total')
                ->where('hasil.rows', function ($rows) {
                    $cari = fn ($label) => collect($rows)->firstWhere(0, $label);
                    // Indeks 1..12 = Jan..Des, 13 = Total.
                    $bunga = $cari('Pendapatan Bunga');
                    $bank = $cari('Bunga Bank');
                    $bruto = $cari('Laba Bruto');
                    $pajak = $cari('Beban Pajak');
                    $lengkap = $cari('Beban Perlengkapan');
                    $totBeban = $cari('Total Beban');
                    $shu = $cari('Selisih Hasil Usaha');

                    return $bunga && $bunga[2] == 100000 && $bunga[13] == 100000
                        && $bank && $bank[3] == 25000 && $bank[13] == 25000
                        && $bruto && $bruto[2] == 100000 && $bruto[3] == 25000 && $bruto[13] == 125000
                        && $pajak && $pajak[2] == 40000 && $pajak[13] == 40000
                        && $lengkap && $lengkap[3] == 170000 && $lengkap[13] == 170000
                        && $totBeban && $totBeban[2] == 40000 && $totBeban[3] == 170000 && $totBeban[13] == 210000
                        && $shu && $shu[2] == 60000 && $shu[3] == -145000 && $shu[13] == -85000
                        // Baris bertotal nol disembunyikan.
                        && ! $cari('Beban Perijinan') && ! $cari('Beban Bank') && ! $cari('Beban Lain-lain');
                })
                ->where('hasil.ringkasan.0.1', 'Rp 125.000')
                ->where('hasil.ringkasan.1.1', 'Rp 210.000')
                ->where('hasil.ringkasan.2.1', 'Rp -85.000'));

        // Rekonsiliasi: total beban laporan = total jurnal pengeluaran koperasi.
        $jurnalBeban = (float) DB::table('jurnal_kas')
            ->where('tipe', 'keluar')
            ->where('kategori', 'pengeluaran_koperasi')
            ->whereBetween('tanggal', ["{$lalu}-01-01 00:00:00", "{$lalu}-12-31 23:59:59"])
            ->sum('jumlah');
        $this->assertEquals(210000.0, $jurnalBeban);
    }

    public function test_tipe_pengeluaran_baru_bisa_dicatat(): void
    {
        $this->bendahara();
        KasKoperasi::create([
            'saldo_pinjaman' => 0, 'saldo_dana_sosial' => 0,
            'saldo_pengembalian_simpanan' => 0, 'saldo_simpanan' => 0,
            'saldo_bank' => 1_000_000_000, 'saldo_kas_kecil' => 1_000_000_000,
        ]);

        foreach (['perlengkapan', 'perijinan', 'bank'] as $tipe) {
            $this->post(route('pengeluaran.store'), [
                'tipe' => $tipe,
                'akun' => 'kas_kecil',
                'jumlah' => 10_000,
                'keterangan' => "Uji {$tipe}",
                'tanggal' => now()->format('Y-m-d'),
            ])->assertRedirect();
        }

        $this->assertDatabaseCount('pengeluaran', 3);
    }
}
