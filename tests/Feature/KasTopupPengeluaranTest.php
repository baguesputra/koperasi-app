<?php

namespace Tests\Feature;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class KasTopupPengeluaranTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_topup_menaikkan_saldo_dan_mencatat_jurnal(): void
    {
        $this->masuk('ADM-000001'); // admin punya kas.topup
        $saldoSebelum = (float) KasKoperasi::first()->saldo_pinjaman;

        $this->post(route('kas-koperasi.topup'), [
            'kantong' => 'pinjaman',
            'jumlah' => 5_000_000,
            'keterangan' => 'Topup uji dari keuntungan bulan lalu',
        ])->assertStatus(302);

        $this->assertEquals($saldoSebelum + 5_000_000, (float) KasKoperasi::first()->saldo_pinjaman);
        $this->assertDatabaseHas('jurnal_kas', [
            'kategori' => 'topup_bulanan', 'kantong' => 'pinjaman',
            'tipe' => 'masuk', 'jumlah' => 5_000_000,
        ]);
    }

    public function test_topup_kantong_transit_ditolak(): void
    {
        $this->masuk('ADM-000001');

        $this->post(route('kas-koperasi.topup'), [
            'kantong' => 'pengembalian_simpanan',
            'jumlah' => 1_000_000,
            'keterangan' => 'Harus gagal',
        ])->assertSessionHasErrors('kantong');
    }

    public function test_pengeluaran_koperasi_mengurangi_saldo_pinjaman(): void
    {
        $this->masuk('BEN-000001');
        $saldoSebelum = (float) KasKoperasi::first()->saldo_pinjaman;

        $this->post(route('pengeluaran.store'), [
            'jenis' => 'koperasi',
            'jumlah' => 250_000,
            'keterangan' => 'Bel ATK kantor sekretariat',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertStatus(302);

        $this->assertEquals($saldoSebelum - 250_000, (float) KasKoperasi::first()->saldo_pinjaman);
        $this->assertTrue(JurnalKas::where('kategori', 'pengeluaran_koperasi')->where('tipe', 'keluar')->exists());
    }

    public function test_pengeluaran_dana_sosial_tak_bersentuhan_saldo_pinjaman(): void
    {
        $this->masuk('BEN-000001');
        $sebelum = KasKoperasi::first()->only(['saldo_pinjaman', 'saldo_dana_sosial']);

        $this->post(route('pengeluaran.store'), [
            'jenis' => 'dana_sosial',
            'jumlah' => 100_000,
            'keterangan' => 'Santunan anggota sakit',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertStatus(302);

        $sesudah = KasKoperasi::first();
        $this->assertEquals($sebelum['saldo_pinjaman'], $sesudah->saldo_pinjaman);
        $this->assertEquals((float) $sebelum['saldo_dana_sosial'] - 100_000, (float) $sesudah->saldo_dana_sosial);
    }
}
