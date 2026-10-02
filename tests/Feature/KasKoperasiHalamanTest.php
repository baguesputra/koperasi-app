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

        $props = $this->get(route('kas-koperasi.index'))
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
            ['kategori' => 'talangan_sosial_ke_pinjaman', 'kantong' => 'pinjaman', 'tipe' => 'masuk', 'ket' => 'Kanal uji audit'],
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
        $this->assertSame('audit', $kanalPerKeterangan['Kanal uji audit']);

        $audit = array_column($props['riwayat']['data'], 'kategori', 'kanal');
        $this->assertArrayHasKey('audit', $audit);
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

        $bank = $this->get(route('kas-koperasi.index', ['kanal' => 'bank']))
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

        $audit = $this->get(route('kas-koperasi.index', ['kanal' => 'audit']))
            ->assertOk()
            ->viewData('page')['props'];
        foreach ($audit['riwayat']['data'] as $baris) {
            $this->assertSame('audit', $baris['kanal']);
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
        $this->assertArrayHasKey('audit', $props['ringkasanKanal']);
        $this->assertGreaterThanOrEqual(700_000, $props['ringkasanKanal']['bank']['masuk']);
        $this->assertSame(
            $props['ringkasanKanal']['bank']['masuk'] + $props['ringkasanKanal']['kas_kecil']['masuk'] + $props['ringkasanKanal']['audit']['masuk'],
            $props['ringkasanPeriode']['total_masuk']
        );
    }
}
