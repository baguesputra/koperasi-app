<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class JurnalKasTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_halaman_jurnal_butuh_permission_jurnal_lihat(): void
    {
        $anggota = $this->buatAnggota();

        $this->masuk($anggota->user->no_karyawan);
        $this->get(route('jurnal-kas.index'))->assertForbidden();

        $this->masuk('ADM-000001');
        $this->get(route('jurnal-kas.index'))->assertOk();
    }

    public function test_filter_kantong_dan_bulan(): void
    {
        $this->masuk('BEN-000001');

        $res = $this->get(route('jurnal-kas.index', ['kantong' => 'bank']))->assertOk();
        $props = $res->viewData('page')['props'];

        $this->assertSame('bank', $props['filters']['kantong']);
        $this->assertArrayHasKey('bank', $props['saldo']);
        $this->assertArrayHasKey('kas_kecil', $props['saldo']);
        $this->assertArrayHasKey('outstanding', $props['saldo']);
        $this->assertNotEmpty($props['kantongOptions']);
        $this->assertNotEmpty($props['kategoriOptions']);

        foreach ($props['riwayat']['data'] as $row) {
            $this->assertSame('bank', $row['kantong']);
        }

        $bulanLalu = now()->subMonth()->format('Y-m');
        $resBulan = $this->get(route('jurnal-kas.index', ['bulan' => $bulanLalu]))->assertOk();
        $this->assertSame($bulanLalu, $resBulan->viewData('page')['props']['bulanFilter']);
    }

    public function test_cari_keterangan(): void
    {
        $this->masuk('BEN-000001');

        $res = $this->get(route('jurnal-kas.index', ['cari' => 'ZZZ-TIDAK-ADA']))->assertOk();
        $props = $res->viewData('page')['props'];

        $this->assertSame(0, count($props['riwayat']['data']));
    }
}
