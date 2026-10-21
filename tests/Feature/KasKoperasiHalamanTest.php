<?php

namespace Tests\Feature;

use App\Models\JurnalKas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class KasKoperasiHalamanTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_kartu_hanya_fisik_tanpa_pot_virtual(): void
    {
        $this->masuk('ADM-000001');

        $props = $this->get(route('kas-koperasi.index'))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertArrayHasKey('saldoBank', $props);
        $this->assertArrayHasKey('saldoKasKecil', $props);
        $this->assertArrayNotHasKey('saldoPinjaman', $props);
        $this->assertArrayNotHasKey('saldoDanaSosial', $props);
        $this->assertArrayNotHasKey('totalSimpananOutstanding', $props);
        $this->assertArrayNotHasKey('kantongAktif', $props);
    }

    public function test_arus_kas_gabungan_semua_kantong_dalam_satu_daftar(): void
    {
        $user = $this->masuk('ADM-000001');

        JurnalKas::create([
            'tipe' => 'masuk',
            'kategori' => 'pembayaran_angsuran',
            'kantong' => 'pinjaman',
            'jumlah' => 750_000,
            'saldo_setelah' => 0,
            'keterangan' => 'Angsuran uji gabungan',
            'tanggal' => now(),
            'created_by' => $user->id,
        ]);
        JurnalKas::create([
            'tipe' => 'keluar',
            'kategori' => 'pengeluaran_koperasi',
            'kantong' => 'kas_kecil',
            'jumlah' => 150_000,
            'saldo_setelah' => 0,
            'keterangan' => 'Pengeluaran uji gabungan',
            'tanggal' => now(),
            'created_by' => $user->id,
        ]);

        $props = $this->get(route('kas-koperasi.index', ['cari' => 'uji gabungan']))
            ->assertOk()
            ->viewData('page')['props'];

        $kantong = array_column($props['riwayat']['data'], 'kantong');
        $this->assertContains('pinjaman', $kantong);
        $this->assertContains('kas_kecil', $kantong);
    }

    public function test_filter_bulan_menghapus_mutasi_bulan_lain(): void
    {
        $user = $this->masuk('ADM-000001');
        $tanggalLama = now()->subYears(2)->startOfMonth()->addDays(5);
        $bulanLama = $tanggalLama->format('Y-m');

        JurnalKas::create([
            'tipe' => 'masuk',
            'kategori' => 'topup_bulanan',
            'kantong' => 'bank',
            'jumlah' => 1_000_000,
            'saldo_setelah' => 0,
            'keterangan' => 'Topup bulan lama',
            'tanggal' => $tanggalLama,
            'created_by' => $user->id,
        ]);

        $kini = $this->get(route('kas-koperasi.index'))
            ->assertOk()
            ->viewData('page')['props'];
        $this->assertSame(now()->format('Y-m'), $kini['bulanFilter']);
        $this->assertNotContains('Topup bulan lama', array_column($kini['riwayat']['data'], 'keterangan'));

        $lalu = $this->get(route('kas-koperasi.index', ['bulan' => $bulanLama]))
            ->assertOk()
            ->viewData('page')['props'];
        $this->assertSame($bulanLama, $lalu['bulanFilter']);
        $this->assertContains('Topup bulan lama', array_column($lalu['riwayat']['data'], 'keterangan'));
    }

    public function test_tiap_baris_punya_kanal_fisik(): void
    {
        $user = $this->masuk('ADM-000001');
        $jurnal = [
            ['kategori' => 'pencairan_pinjaman', 'kantong' => 'pinjaman', 'tipe' => 'keluar', 'ket' => 'Kanal uji bank'],
            ['kategori' => 'pengeluaran_koperasi', 'kantong' => 'kas_kecil', 'tipe' => 'keluar', 'ket' => 'Kanal uji kas kecil'],
            ['kategori' => 'pelunasan_resign_pinjaman', 'kantong' => 'pinjaman', 'tipe' => 'masuk', 'ket' => 'Kanal uji resign bank'],
        ];
        foreach ($jurnal as $j) {
            JurnalKas::create([
                'tipe' => $j['tipe'],
                'kategori' => $j['kategori'],
                'kantong' => $j['kantong'],
                'jumlah' => 100_000,
                'saldo_setelah' => 0,
                'keterangan' => $j['ket'],
                'tanggal' => now(),
                'created_by' => $user->id,
            ]);
        }

        $props = $this->get(route('kas-koperasi.index', ['cari' => 'Kanal uji']))
            ->assertOk()
            ->viewData('page')['props'];

        $kanalPerKeterangan = array_column($props['riwayat']['data'], 'kanal', 'keterangan');
        $this->assertSame('bank', $kanalPerKeterangan['Kanal uji bank']);
        $this->assertSame('kas_kecil', $kanalPerKeterangan['Kanal uji kas kecil']);
        $this->assertSame('bank', $kanalPerKeterangan['Kanal uji resign bank']);
    }

    public function test_filter_kanal_mengisolasi_kas_fisik(): void
    {
        $user = $this->masuk('ADM-000001');
        JurnalKas::create([
            'tipe' => 'masuk',
            'kategori' => 'topup_bulanan',
            'kantong' => 'bank',
            'jumlah' => 500_000,
            'saldo_setelah' => 0,
            'keterangan' => 'Topup uji filter kanal',
            'tanggal' => now(),
            'created_by' => $user->id,
        ]);
        JurnalKas::create([
            'tipe' => 'keluar',
            'kategori' => 'pengeluaran_koperasi',
            'kantong' => 'kas_kecil',
            'jumlah' => 50_000,
            'saldo_setelah' => 0,
            'keterangan' => 'Pengeluaran uji filter kanal',
            'tanggal' => now(),
            'created_by' => $user->id,
        ]);

        $bank = $this->get(route('kas-koperasi.index', ['kanal' => 'bank', 'cari' => 'Topup uji filter kanal']))
            ->assertOk()
            ->viewData('page')['props'];
        $this->assertContains('Topup uji filter kanal', array_column($bank['riwayat']['data'], 'keterangan'));
        $this->assertNotContains('Pengeluaran uji filter kanal', array_column($bank['riwayat']['data'], 'keterangan'));

        $kas = $this->get(route('kas-koperasi.index', ['kanal' => 'kas_kecil']))
            ->assertOk()
            ->viewData('page')['props'];
        foreach ($kas['riwayat']['data'] as $baris) {
            $this->assertSame('kas_kecil', $baris['kantong']);
        }
    }

    public function test_filter_cari_mencari_keterangan_dan_subjudul(): void
    {
        $user = $this->masuk('ADM-000001');
        JurnalKas::create([
            'tipe' => 'masuk',
            'kategori' => 'pembayaran_angsuran',
            'kantong' => 'pinjaman',
            'jumlah' => 250_000,
            'saldo_setelah' => 0,
            'keterangan' => 'Angsuran katakunciunik',
            'sub_judul' => null,
            'tanggal' => now(),
            'created_by' => $user->id,
        ]);
        JurnalKas::create([
            'tipe' => 'masuk',
            'kategori' => 'simpanan_wajib_masuk',
            'kantong' => 'bank',
            'jumlah' => 100_000,
            'saldo_setelah' => 0,
            'keterangan' => 'Wajib masuk',
            'sub_judul' => 'Subjek katakunciunik',
            'tanggal' => now(),
            'created_by' => $user->id,
        ]);

        $props = $this->get(route('kas-koperasi.index', ['cari' => 'katakunciunik']))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertCount(2, $props['riwayat']['data']);
        $this->assertSame('katakunciunik', $props['filters']['cari']);
    }

    public function test_ringkasan_kanal_terkirim_per_kas(): void
    {
        $user = $this->masuk('ADM-000001');
        JurnalKas::create([
            'tipe' => 'masuk',
            'kategori' => 'topup_bulanan',
            'kantong' => 'bank',
            'jumlah' => 700_000,
            'saldo_setelah' => 0,
            'keterangan' => 'Topup uji ringkasan',
            'tanggal' => now(),
            'created_by' => $user->id,
        ]);

        $props = $this->get(route('kas-koperasi.index'))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertArrayHasKey('bank', $props['ringkasanKanal']);
        $this->assertArrayHasKey('kas_kecil', $props['ringkasanKanal']);
        $this->assertArrayNotHasKey('audit', $props['ringkasanKanal']);
        $this->assertGreaterThanOrEqual(700_000, $props['ringkasanKanal']['bank']['masuk']);
        $this->assertSame(
            $props['ringkasanKanal']['bank']['masuk'] + $props['ringkasanKanal']['kas_kecil']['masuk'],
            $props['ringkasanPeriode']['total_masuk']
        );
    }

    public function test_filter_tipe_mengisolasi_masuk_dan_keluar(): void
    {
        $user = $this->masuk('ADM-000001');
        JurnalKas::create([
            'tipe' => 'masuk',
            'kategori' => 'topup_bulanan',
            'kantong' => 'bank',
            'jumlah' => 500_000,
            'saldo_setelah' => 0,
            'keterangan' => 'Topup uji tipe masuk',
            'tanggal' => now(),
            'created_by' => $user->id,
        ]);
        JurnalKas::create([
            'tipe' => 'keluar',
            'kategori' => 'pengeluaran_koperasi',
            'kantong' => 'kas_kecil',
            'jumlah' => 50_000,
            'saldo_setelah' => 0,
            'keterangan' => 'Pengeluaran uji tipe keluar',
            'tanggal' => now(),
            'created_by' => $user->id,
        ]);

        $masuk = $this->get(route('kas-koperasi.index', ['tipe' => 'masuk', 'cari' => 'Uji tipe masuk']))
            ->assertOk()
            ->viewData('page')['props'];
        $this->assertContains('Topup uji tipe masuk', array_column($masuk['riwayat']['data'], 'keterangan'));
        $this->assertNotContains('Pengeluaran uji tipe keluar', array_column($masuk['riwayat']['data'], 'keterangan'));
        foreach ($masuk['riwayat']['data'] as $baris) {
            $this->assertSame('masuk', $baris['tipe']);
        }

        $keluar = $this->get(route('kas-koperasi.index', ['tipe' => 'keluar', 'cari' => 'Uji tipe keluar']))
            ->assertOk()
            ->viewData('page')['props'];
        $this->assertContains('Pengeluaran uji tipe keluar', array_column($keluar['riwayat']['data'], 'keterangan'));
        $this->assertNotContains('Topup uji tipe masuk', array_column($keluar['riwayat']['data'], 'keterangan'));
        foreach ($keluar['riwayat']['data'] as $baris) {
            $this->assertSame('keluar', $baris['tipe']);
        }
    }

    public function test_filter_tanggal_mengisolasi_satu_hari(): void
    {
        $user = $this->masuk('ADM-000001');
        $tanggalUji = now()->addYear()->startOfMonth()->addDays(4)->format('Y-m-d');
        $tanggalLain = now()->addYear()->startOfMonth()->addDays(3)->format('Y-m-d');

        JurnalKas::create([
            'tipe' => 'masuk',
            'kategori' => 'topup_bulanan',
            'kantong' => 'bank',
            'jumlah' => 500_000,
            'saldo_setelah' => 0,
            'keterangan' => 'Topup uji tanggal hari ini',
            'tanggal' => $tanggalUji,
            'created_by' => $user->id,
        ]);
        JurnalKas::create([
            'tipe' => 'masuk',
            'kategori' => 'topup_bulanan',
            'kantong' => 'bank',
            'jumlah' => 300_000,
            'saldo_setelah' => 0,
            'keterangan' => 'Topup uji tanggal kemarin',
            'tanggal' => $tanggalLain,
            'created_by' => $user->id,
        ]);

        $props = $this->get(route('kas-koperasi.index', ['tanggal' => $tanggalUji]))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertSame($tanggalUji, $props['filters']['tanggal']);
        $this->assertContains('Topup uji tanggal hari ini', array_column($props['riwayat']['data'], 'keterangan'));
        $this->assertNotContains('Topup uji tanggal kemarin', array_column($props['riwayat']['data'], 'keterangan'));
    }

    public function test_riwayat_urut_naik_dan_halaman_pertama_mulai_dari_terlama(): void
    {
        $user = $this->masuk('ADM-000001');
        $bulan = now()->addYear()->startOfMonth();

        foreach ([
            [5, 'Uji urut lama'],
            [10, 'Uji urut baru'],
        ] as [$hari, $keterangan]) {
            JurnalKas::create([
                'tipe' => 'masuk',
                'kategori' => 'topup_bulanan',
                'kantong' => 'bank',
                'jumlah' => 100_000,
                'saldo_setelah' => 0,
                'keterangan' => $keterangan,
                'tanggal' => $bulan->copy()->day($hari),
                'created_by' => $user->id,
            ]);
        }

        $props = $this->get(route('kas-koperasi.index', ['bulan' => $bulan->format('Y-m')]))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertSame('Uji urut lama', $props['riwayat']['data'][0]['keterangan']);
        $this->assertSame('Uji urut baru', $props['riwayat']['data'][1]['keterangan']);
    }

    public function test_saldo_awal_dan_akhir_periode_dihitung_dari_mutasi(): void
    {
        $user = $this->masuk('ADM-000001');
        $bulan = now()->addYear()->startOfMonth();

        JurnalKas::create([
            'tipe' => 'masuk',
            'kategori' => 'topup_bulanan',
            'kantong' => 'bank',
            'jumlah' => 500_000,
            'saldo_setelah' => 0,
            'keterangan' => 'Topup uji saldo periode',
            'tanggal' => $bulan->copy()->day(5),
            'created_by' => $user->id,
        ]);
        JurnalKas::create([
            'tipe' => 'keluar',
            'kategori' => 'pengeluaran_koperasi',
            'kantong' => 'kas_kecil',
            'jumlah' => 100_000,
            'saldo_setelah' => 0,
            'keterangan' => 'Pengeluaran uji saldo periode',
            'tanggal' => $bulan->copy()->day(10),
            'created_by' => $user->id,
        ]);

        $mutasi = fn (JurnalKas $j) => $j->tipe === 'masuk' ? (float) $j->jumlah : -(float) $j->jumlah;

        $saldoAwal = (float) JurnalKas::query()
            ->where('tanggal', '<', $bulan->toDateString())
            ->get()
            ->sum($mutasi);

        $saldoAwalBank = (float) JurnalKas::query()
            ->where('tanggal', '<', $bulan->toDateString())
            ->where('kantong', '!=', 'kas_kecil')
            ->get()
            ->sum($mutasi);

        $saldoAwalKas = (float) JurnalKas::query()
            ->where('tanggal', '<', $bulan->toDateString())
            ->where('kantong', 'kas_kecil')
            ->get()
            ->sum($mutasi);

        $props = $this->get(route('kas-koperasi.index', ['bulan' => $bulan->format('Y-m')]))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertTrue($props['ringkasanPeriode']['saldo_valid']);
        $this->assertEqualsWithDelta(500_000, $props['ringkasanPeriode']['total_masuk'], 0.01);
        $this->assertEqualsWithDelta(100_000, $props['ringkasanPeriode']['total_keluar'], 0.01);
        $this->assertEqualsWithDelta($saldoAwal, $props['ringkasanPeriode']['saldo_awal'], 0.01);
        $this->assertEqualsWithDelta($saldoAwal + 400_000, $props['ringkasanPeriode']['saldo_akhir'], 0.01);
        $this->assertEqualsWithDelta($saldoAwalBank + 500_000, $props['riwayat']['data'][0]['saldo_setelah'], 0.01);
        $this->assertEqualsWithDelta($saldoAwalKas - 100_000, $props['riwayat']['data'][1]['saldo_setelah'], 0.01);

        $terfilter = $this->get(route('kas-koperasi.index', ['bulan' => $bulan->format('Y-m'), 'cari' => 'Uji']))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertFalse($terfilter['ringkasanPeriode']['saldo_valid']);
    }

    public function test_saldo_baris_halaman_kedua_melanjutkan_saldo_halaman_pertama(): void
    {
        $user = $this->masuk('ADM-000001');
        $bulan = now()->addYear()->startOfMonth();

        for ($i = 1; $i <= 21; $i++) {
            JurnalKas::create([
                'tipe' => 'masuk',
                'kategori' => 'topup_bulanan',
                'kantong' => 'bank',
                'jumlah' => 100_000,
                'saldo_setelah' => 0,
                'keterangan' => "Uji halaman dua {$i}",
                'tanggal' => $bulan->copy()->day(5),
                'created_by' => $user->id,
            ]);
        }

        $saldoAwalBank = (float) JurnalKas::query()
            ->where('tanggal', '<', $bulan->toDateString())
            ->where('kantong', '!=', 'kas_kecil')
            ->get()
            ->sum(fn (JurnalKas $j) => $j->tipe === 'masuk' ? (float) $j->jumlah : -(float) $j->jumlah);

        $props = $this->get(route('kas-koperasi.index', ['bulan' => $bulan->format('Y-m'), 'page' => 2]))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertSame(2, $props['riwayat']['current_page']);
        $this->assertCount(1, $props['riwayat']['data']);
        $this->assertSame('Uji halaman dua 21', $props['riwayat']['data'][0]['keterangan']);
        $this->assertEqualsWithDelta($saldoAwalBank + 2_100_000, $props['riwayat']['data'][0]['saldo_setelah'], 0.01);
    }

    public function test_ringkasan_per_tanggal_terkirim_dengan_total_masuk_keluar(): void
    {
        $user = $this->masuk('ADM-000001');
        $tgl = now()->addYear()->startOfMonth()->addDays(3)->format('Y-m-d');

        JurnalKas::create([
            'tipe' => 'masuk',
            'kategori' => 'topup_bulanan',
            'kantong' => 'bank',
            'jumlah' => 500_000,
            'saldo_setelah' => 0,
            'keterangan' => 'Topup uji ringkasan tanggal',
            'tanggal' => $tgl,
            'created_by' => $user->id,
        ]);
        JurnalKas::create([
            'tipe' => 'keluar',
            'kategori' => 'pengeluaran_koperasi',
            'kantong' => 'kas_kecil',
            'jumlah' => 100_000,
            'saldo_setelah' => 0,
            'keterangan' => 'Pengeluaran uji ringkasan tanggal',
            'tanggal' => $tgl,
            'created_by' => $user->id,
        ]);

        $props = $this->get(route('kas-koperasi.index', ['tanggal' => $tgl]))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertArrayHasKey('ringkasanPerTanggal', $props);
        $this->assertArrayHasKey($tgl, $props['ringkasanPerTanggal']);
        $this->assertGreaterThanOrEqual(500_000, $props['ringkasanPerTanggal'][$tgl]['masuk']);
        $this->assertGreaterThanOrEqual(100_000, $props['ringkasanPerTanggal'][$tgl]['keluar']);
    }
}
