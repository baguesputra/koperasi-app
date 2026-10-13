<?php

namespace Tests\Feature;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\Pengeluaran;
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
        $bankSebelum = (float) KasKoperasi::first()->saldo_bank;

        $this->post(route('kas-koperasi.topup'), [
            'jumlah' => 5_000_000,
            'sumber_topup' => 'modal',
            'keterangan' => 'Topup uji dari keuntungan bulan lalu',
        ])->assertStatus(302);

        $this->assertEquals($bankSebelum + 5_000_000, (float) KasKoperasi::first()->saldo_bank);
        $this->assertDatabaseHas('jurnal_kas', [
            'kategori' => 'topup_bulanan', 'kantong' => 'bank',
            'tipe' => 'masuk', 'jumlah' => 5_000_000, 'sumber_topup' => 'modal',
        ]);
    }

    public function test_topup_wajib_pilih_sumber(): void
    {
        $this->masuk('ADM-000001');

        $this->post(route('kas-koperasi.topup'), [
            'jumlah' => 1_000_000,
        ])->assertSessionHasErrors('sumber_topup');

        $this->post(route('kas-koperasi.topup'), [
            'jumlah' => 1_000_000,
            'sumber_topup' => 'dana_asing',
        ])->assertSessionHasErrors('sumber_topup');
    }

    public function test_topup_bunga_bank_masuk_bank(): void
    {
        $this->masuk('ADM-000001');
        $bankSebelum = (float) KasKoperasi::first()->saldo_bank;

        $this->post(route('kas-koperasi.topup'), [
            'jumlah' => 250_000,
            'sumber_topup' => 'bunga_bank',
            'keterangan' => 'Bunga bank bulan ini',
        ])->assertStatus(302);

        $this->assertEquals($bankSebelum + 250_000, (float) KasKoperasi::first()->saldo_bank);
        $this->assertDatabaseHas('jurnal_kas', [
            'kategori' => 'topup_bulanan', 'kantong' => 'bank',
            'tipe' => 'masuk', 'jumlah' => 250_000, 'sumber_topup' => 'bunga_bank',
        ]);
    }

    public function test_topup_selalu_masuk_bank_tanpa_pilih_kantong(): void
    {
        $this->masuk('ADM-000001');
        $bankSebelum = (float) KasKoperasi::first()->saldo_bank;

        // Konsep Kas Tunggal: parameter kantong diabaikan, topup selalu ke Bank.
        $this->post(route('kas-koperasi.topup'), [
            'kantong' => 'pengembalian_simpanan',
            'jumlah' => 1_000_000,
            'sumber_topup' => 'modal',
            'keterangan' => 'Kantong diabaikan',
        ])->assertStatus(302);

        $this->assertEquals($bankSebelum + 1_000_000, (float) KasKoperasi::first()->saldo_bank);
        $this->assertDatabaseHas('jurnal_kas', [
            'kategori' => 'topup_bulanan', 'kantong' => 'bank',
            'tipe' => 'masuk', 'jumlah' => 1_000_000,
        ]);
    }

    public function test_topup_lain_lain_wajib_isi_keterangan(): void
    {
        $this->masuk('ADM-000001');

        // Dicentang tapi tanpa keterangan → ditolak
        $this->post(route('kas-koperasi.topup'), [
            'jumlah' => 1_000_000,
            'sumber_topup' => 'modal',
            'lain_lain' => true,
        ])->assertSessionHasErrors('keterangan_lain');

        // Dengan keterangan → tersimpan berawalan [Lain-lain]
        $this->post(route('kas-koperasi.topup'), [
            'jumlah' => 1_000_000,
            'sumber_topup' => 'modal',
            'keterangan' => 'Transfer masuk',
            'lain_lain' => true,
            'keterangan_lain' => 'Hibah donatur',
        ])->assertStatus(302);

        $this->assertDatabaseHas('jurnal_kas', [
            'kategori' => 'topup_bulanan', 'kantong' => 'bank',
            'tipe' => 'masuk', 'jumlah' => 1_000_000,
            'keterangan' => '[Lain-lain] Hibah donatur — Transfer masuk',
        ]);
    }

    public function test_pengeluaran_koperasi_dari_saldo_kas_kecil(): void
    {
        $this->masuk('BEN-000001');
        $sebelum = KasKoperasi::first()->only(['saldo_pinjaman', 'saldo_kas_kecil', 'saldo_bank']);

        $this->post(route('pengeluaran.store'), [
            'jenis' => 'koperasi',
            'jumlah' => 250_000,
            'keterangan' => 'Bel ATK kantor sekretariat',
            'tipe' => 'lain_lain',
            'akun' => 'kas_kecil',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertStatus(302);

        $sesudah = KasKoperasi::first();
        $this->assertEquals((float) $sebelum['saldo_pinjaman'], (float) $sesudah->saldo_pinjaman);
        $this->assertEquals((float) $sebelum['saldo_kas_kecil'] - 250_000, (float) $sesudah->saldo_kas_kecil);
        $this->assertEquals((float) $sebelum['saldo_bank'], (float) $sesudah->saldo_bank);
        $this->assertTrue(JurnalKas::where('kategori', 'pengeluaran_koperasi')->where('tipe', 'keluar')->exists());
    }

    public function test_pengeluaran_koperasi_bisa_dari_bank(): void
    {
        $this->masuk('BEN-000001');
        $sebelum = KasKoperasi::first()->only(['saldo_kas_kecil', 'saldo_bank']);

        $this->post(route('pengeluaran.store'), [
            'jenis' => 'koperasi',
            'jumlah' => 250_000,
            'keterangan' => 'Bayar sewa server dari bank',
            'tipe' => 'lain_lain',
            'akun' => 'bank',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertStatus(302);

        $sesudah = KasKoperasi::first();
        $this->assertEquals((float) $sebelum['saldo_bank'] - 250_000, (float) $sesudah->saldo_bank);
        $this->assertEquals((float) $sebelum['saldo_kas_kecil'], (float) $sesudah->saldo_kas_kecil);
        $this->assertDatabaseHas('pengeluaran', ['keterangan' => 'Bayar sewa server dari bank', 'akun' => 'bank']);
        $this->assertDatabaseHas('jurnal_kas', [
            'kategori' => 'pengeluaran_koperasi', 'kantong' => 'bank', 'tipe' => 'keluar', 'jumlah' => 250_000,
        ]);
    }

    public function test_pengeluaran_wajib_pilih_akun(): void
    {
        $this->masuk('BEN-000001');

        $this->post(route('pengeluaran.store'), [
            'jenis' => 'koperasi',
            'jumlah' => 100_000,
            'keterangan' => 'Tanpa akun',
            'tipe' => 'lain_lain',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertSessionHasErrors('akun');
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
            'tipe' => 'lain_lain',
            'akun' => 'kas_kecil',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertStatus(302);

        $this->post(route('pengeluaran.store'), [
            'jenis' => 'koperasi',
            'jumlah' => 100_000,
            'keterangan' => 'Biaya lama beda bulan qzx123',
            'tipe' => 'pajak',
            'akun' => 'kas_kecil',
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
            'tipe' => 'santunan',
            'akun' => 'kas_kecil',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertStatus(302);

        $sesudah = KasKoperasi::first();
        $this->assertEquals((float) $sebelum['saldo_pinjaman'], (float) $sesudah->saldo_pinjaman);
        $this->assertEquals((float) $sebelum['saldo_dana_sosial'], (float) $sesudah->saldo_dana_sosial);
        $this->assertEquals((float) $sebelum['saldo_kas_kecil'] - 100_000, (float) $sesudah->saldo_kas_kecil);
    }

    public function test_pengeluaran_dana_sosial_bisa_dari_bank_tanpa_pagu(): void
    {
        $this->masuk('BEN-000001');
        $sebelum = KasKoperasi::first()->only(['saldo_kas_kecil', 'saldo_bank']);

        $this->post(route('pengeluaran.store'), [
            'jenis' => 'dana_sosial',
            'jumlah' => 6_000_000,
            'keterangan' => 'Santunan besar dari bank tanpa pagu',
            'tipe' => 'santunan',
            'akun' => 'bank',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertStatus(302);

        $sesudah = KasKoperasi::first();
        $this->assertEquals((float) $sebelum['saldo_bank'] - 6_000_000, (float) $sesudah->saldo_bank);
        $this->assertEquals((float) $sebelum['saldo_kas_kecil'], (float) $sesudah->saldo_kas_kecil);
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
            'tipe' => 'santunan',
            'akun' => 'kas_kecil',
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
            'tipe' => 'lain_lain',
            'akun' => 'kas_kecil',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertSessionHasErrors('jumlah');

        $this->assertTrue(JurnalKas::where('kategori', 'pengeluaran_koperasi')->where('keterangan', 'Melebihi saldo kas kecil')->doesntExist());
    }

    public function test_pengeluaran_tipe_wajib_dan_bisa_difilter(): void
    {
        $this->masuk('BEN-000001');

        // Tanpa tipe → validasi menolak
        $this->post(route('pengeluaran.store'), [
            'jenis' => 'koperasi',
            'jumlah' => 100_000,
            'keterangan' => 'Tanpa tipe',
            'akun' => 'kas_kecil',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertSessionHasErrors('tipe');

        // Tipe di luar pilihan → validasi menolak
        $this->post(route('pengeluaran.store'), [
            'jenis' => 'koperasi',
            'jumlah' => 100_000,
            'keterangan' => 'Tipe asing',
            'tipe' => 'operasional',
            'akun' => 'kas_kecil',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertSessionHasErrors('tipe');

        $this->post(route('pengeluaran.store'), [
            'jenis' => 'koperasi',
            'jumlah' => 120_000,
            'keterangan' => 'Pajak parkir kantor',
            'tipe' => 'pajak',
            'akun' => 'kas_kecil',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertStatus(302);

        $this->assertDatabaseHas('pengeluaran', ['keterangan' => 'Pajak parkir kantor', 'tipe' => 'pajak']);

        $res = $this->get(route('pengeluaran.index', ['jenis' => 'koperasi', 'tipe' => 'pajak']))->assertOk();
        $props = $res->viewData('page')['props'];
        $this->assertSame(1, count($props['pengeluaran']['data']));
        $this->assertSame('Pajak', $props['pengeluaran']['data'][0]['tipe_label']);

        $resLain = $this->get(route('pengeluaran.index', ['jenis' => 'koperasi', 'tipe' => 'santunan']))->assertOk();
        $this->assertSame(0, count($resLain->viewData('page')['props']['pengeluaran']['data']));
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
