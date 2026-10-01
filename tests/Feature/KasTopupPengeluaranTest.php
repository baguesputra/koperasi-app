<?php

namespace Tests\Feature;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\Pengeluaran;
use App\Models\SettingKas;
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

    public function test_index_filter_cari_dan_bulan(): void
    {
        $this->masuk('BEN-000001');
        $bulanIni = now()->format('Y-m');
        $bulanLalu = now()->subMonth()->format('Y-m');

        $this->post(route('pengeluaran.store'), [
            'jenis' => 'koperasi',
            'jumlah' => 250_000,
            'keterangan' => 'Belanja spidol zebra qzx123',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertStatus(302);

        $this->post(route('pengeluaran.store'), [
            'jenis' => 'koperasi',
            'jumlah' => 100_000,
            'keterangan' => 'Biaya lama beda bulan qzx123',
            'tanggal' => now()->subMonth()->format('Y-m-d'),
        ])->assertStatus(302);

        $res = $this->get(route('pengeluaran.index', ['jenis' => 'koperasi', 'cari' => 'qzx123']))->assertOk();
        $props = $res->viewData('page')['props'];
        $this->assertSame(2, count($props['pengeluaran']['data']));
        $this->assertEquals(350_000, (float) $props['totalTampil']);

        $resBulan = $this->get(route('pengeluaran.index', ['jenis' => 'koperasi', 'cari' => 'qzx123', 'bulan' => $bulanLalu]))->assertOk();
        $propsBulan = $resBulan->viewData('page')['props'];
        $this->assertSame(1, count($propsBulan['pengeluaran']['data']));
        $this->assertEquals(100_000, (float) $propsBulan['totalTampil']);

        $resSemua = $this->get(route('pengeluaran.index', ['jenis' => 'koperasi', 'cari' => 'qzx123', 'bulan' => $bulanIni]))->assertOk();
        $propsSemua = $resSemua->viewData('page')['props'];
        $this->assertSame(1, count($propsSemua['pengeluaran']['data']));
        $this->assertEquals(250_000, (float) $propsSemua['totalTampil']);

        $resKosong = $this->get(route('pengeluaran.index', ['jenis' => 'dana_sosial', 'cari' => 'qzx123']))->assertOk();
        $propsKosong = $resKosong->viewData('page')['props'];
        $this->assertSame(0, count($propsKosong['pengeluaran']['data']));
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

    public function test_pengeluaran_dana_sosial_dibatasi_pagu_bulanan(): void
    {
        $this->masuk('BEN-000001');
        $terpakai = (float) Pengeluaran::where('jenis', 'dana_sosial')
            ->whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)
            ->sum('jumlah');
        SettingKas::updateOrCreate(
            ['kunci' => SettingKas::CADANGAN],
            ['label' => 'Cadangan Sosial Bulanan', 'nominal' => $terpakai + 600_000]
        );

        $this->post(route('pengeluaran.store'), [
            'jenis' => 'dana_sosial',
            'jumlah' => 700_000,
            'keterangan' => 'Melebihi sisa pagu',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertSessionHasErrors('jumlah');

        $this->post(route('pengeluaran.store'), [
            'jenis' => 'dana_sosial',
            'jumlah' => 600_000,
            'keterangan' => 'Santunan dalam pagu',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertSessionHasNoErrors();

        $this->post(route('pengeluaran.store'), [
            'jenis' => 'dana_sosial',
            'jumlah' => 1,
            'keterangan' => 'Sisa pagu habis',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertSessionHasErrors('jumlah');

        $this->assertSame(1, Pengeluaran::where('jenis', 'dana_sosial')->where('keterangan', 'Santunan dalam pagu')->count());
        $this->assertSame(0, Pengeluaran::where('jenis', 'dana_sosial')->where('keterangan', 'Melebihi sisa pagu')->count());
        $this->assertSame(0, Pengeluaran::where('jenis', 'dana_sosial')->where('keterangan', 'Sisa pagu habis')->count());
    }

    public function test_pengeluaran_koperasi_tak_dibatasi_pagu_sosial(): void
    {
        $this->masuk('BEN-000001');
        SettingKas::updateOrCreate(
            ['kunci' => SettingKas::CADANGAN],
            ['label' => 'Cadangan Sosial Bulanan', 'nominal' => 1_000_000]
        );

        $this->post(route('pengeluaran.store'), [
            'jenis' => 'koperasi',
            'jumlah' => 2_000_000,
            'keterangan' => 'Belanja melebihi pagu sosial tapi koperasi bebas',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertStatus(302);

        $this->assertTrue(JurnalKas::where('kategori', 'pengeluaran_koperasi')->where('tipe', 'keluar')->exists());
    }
}
