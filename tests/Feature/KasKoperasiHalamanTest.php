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
}
