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
        $bankSebelum = (float) KasKoperasi::first()->saldo_bank;

        $this->post(route('kas-koperasi.topup'), [
            'kantong' => 'pinjaman',
            'jumlah' => 5_000_000,
            'keterangan' => 'Topup uji dari keuntungan bulan lalu',
        ])->assertStatus(302);

        $this->assertEquals($saldoSebelum + 5_000_000, (float) KasKoperasi::first()->saldo_pinjaman);
        $this->assertEquals($bankSebelum + 5_000_000, (float) KasKoperasi::first()->saldo_bank);
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

    public function test_pengeluaran_koperasi_dari_saldo_kas_kecil(): void
    {
        $this->masuk('BEN-000001');
        $sebelum = KasKoperasi::first()->only(['saldo_pinjaman', 'saldo_kas_kecil', 'saldo_bank']);

        $this->post(route('pengeluaran.store'), [
            'jenis' => 'koperasi',
            'jumlah' => 250_000,
            'keterangan' => 'Bel ATK kantor sekretariat',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertStatus(302);

        $sesudah = KasKoperasi::first();
        $this->assertEquals((float) $sebelum['saldo_pinjaman'], (float) $sesudah->saldo_pinjaman);
        $this->assertEquals((float) $sebelum['saldo_kas_kecil'] - 250_000, (float) $sesudah->saldo_kas_kecil);
        $this->assertEquals((float) $sebelum['saldo_bank'], (float) $sesudah->saldo_bank);
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
        $sebelum = KasKoperasi::first()->only(['saldo_pinjaman', 'saldo_dana_sosial', 'saldo_kas_kecil']);

        $this->post(route('pengeluaran.store'), [
            'jenis' => 'dana_sosial',
            'jumlah' => 100_000,
            'keterangan' => 'Santunan anggota sakit',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertStatus(302);

        $sesudah = KasKoperasi::first();
        $this->assertEquals((float) $sebelum['saldo_pinjaman'], (float) $sesudah->saldo_pinjaman);
        $this->assertEquals((float) $sebelum['saldo_dana_sosial'], (float) $sesudah->saldo_dana_sosial);
        $this->assertEquals((float) $sebelum['saldo_kas_kecil'] - 100_000, (float) $sesudah->saldo_kas_kecil);
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

    public function test_pengeluaran_ditolak_bila_saldo_kas_kecil_habis(): void
    {
        $this->masuk('BEN-000001');
        $kas = KasKoperasi::first();
        $kasKecil = (float) $kas->saldo_kas_kecil;

        // Set saldo kas kecil habis
        $kas->update(['saldo_kas_kecil' => 0]);
        $kas->refresh();

        $this->post(route('pengeluaran.store'), [
            'jenis' => 'dana_sosial',
            'jumlah' => 1,
            'keterangan' => 'Santunan saat kas kecil habis',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertSessionHasErrors('jumlah');

        $this->assertSame(0, Pengeluaran::where('jenis', 'dana_sosial')->where('keterangan', 'Santunan saat kas kecil habis')->count());
    }

    public function test_pengeluaran_koperasi_melebihi_saldo_kas_kecil_ditolak(): void
    {
        $this->masuk('BEN-000001');
        $kas = KasKoperasi::first();
        $kasKecil = (float) $kas->saldo_kas_kecil;

        $this->post(route('pengeluaran.store'), [
            'jenis' => 'koperasi',
            'jumlah' => $kasKecil + 1,
            'keterangan' => 'Melebihi saldo kas kecil',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertSessionHasErrors('jumlah');

        $this->assertTrue(JurnalKas::where('kategori', 'pengeluaran_koperasi')->where('keterangan', 'Melebihi saldo kas kecil')->doesntExist());
    }

    public function test_sisih_kas_kecil_memindahkan_dari_bank(): void
    {
        $this->masuk('ADM-000001');
        $sebelum = KasKoperasi::first()->only(['saldo_bank', 'saldo_kas_kecil']);

        $this->post(route('kas-koperasi.sisih-kas-kecil'), [
            'jumlah' => 1_000_000,
            'keterangan' => 'Sisih untuk kas operasional',
        ])->assertStatus(302);

        $sesudah = KasKoperasi::first();
        $this->assertEquals((float) $sebelum['saldo_bank'] - 1_000_000, (float) $sesudah->saldo_bank);
        $this->assertEquals((float) $sebelum['saldo_kas_kecil'] + 1_000_000, (float) $sesudah->saldo_kas_kecil);
        $this->assertDatabaseHas('jurnal_kas', [
            'kategori' => 'sisih_kas_kecil', 'kantong' => 'bank', 'tipe' => 'keluar', 'jumlah' => 1_000_000,
        ]);
        $this->assertDatabaseHas('jurnal_kas', [
            'kategori' => 'terima_sisih_kas_kecil', 'kantong' => 'kas_kecil', 'tipe' => 'masuk', 'jumlah' => 1_000_000,
        ]);
    }

    public function test_sisih_kas_kecil_ditolak_bila_saldo_bank_kurang(): void
    {
        $this->masuk('ADM-000001');
        $kas = KasKoperasi::first();
        $bank = (float) $kas->saldo_bank;

        $this->post(route('kas-koperasi.sisih-kas-kecil'), [
            'jumlah' => $bank + 1,
            'keterangan' => 'Melebihi saldo bank',
        ])->assertSessionHasErrors('jumlah');
    }
}
