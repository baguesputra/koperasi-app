<?php

namespace Tests\Feature;

use App\Laporan\LaporanRegistry;
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
        $this->masuk('BEN-000001'); // bendahara punya kas.topup
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
        $this->masuk('BEN-000001');

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
        $this->masuk('BEN-000001');
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
        $this->masuk('BEN-000001');
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
        $this->masuk('BEN-000001');

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
            'jumlah' => 250_000,
            'keterangan' => 'Belanja spidol zebra qzx123',
            'tipe' => 'lain_lain',
            'akun' => 'kas_kecil',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertStatus(302);

        $this->post(route('pengeluaran.store'), [
            'jumlah' => 100_000,
            'keterangan' => 'Biaya lama beda bulan qzx123',
            'tipe' => 'pajak',
            'akun' => 'kas_kecil',
            'tanggal' => now()->subMonth()->format('Y-m-d'),
        ])->assertStatus(302);

        $res = $this->get(route('pengeluaran.index', ['cari' => 'qzx123']))->assertOk();
        $props = $res->viewData('page')['props'];
        $this->assertSame(2, count($props['pengeluaran']['data']));
        $this->assertEquals(350_000, (float) $props['totalTampil']);

        $resBulan = $this->get(route('pengeluaran.index', ['cari' => 'qzx123', 'bulan' => $bulanLalu]))->assertOk();
        $propsBulan = $resBulan->viewData('page')['props'];
        $this->assertSame(1, count($propsBulan['pengeluaran']['data']));
        $this->assertEquals(100_000, (float) $propsBulan['totalTampil']);

        $resSemua = $this->get(route('pengeluaran.index', ['cari' => 'qzx123', 'bulan' => $bulanIni]))->assertOk();
        $propsSemua = $resSemua->viewData('page')['props'];
        $this->assertSame(1, count($propsSemua['pengeluaran']['data']));
        $this->assertEquals(250_000, (float) $propsSemua['totalTampil']);

        // Daftar gabungan: tanpa filter jenis, kedua record tampil.
        $resGabung = $this->get(route('pengeluaran.index', ['cari' => 'qzx123']))->assertOk();
        $kets = array_column($resGabung->viewData('page')['props']['pengeluaran']['data'], 'keterangan');
        $this->assertContains('Belanja spidol zebra qzx123', $kets);
        $this->assertContains('Biaya lama beda bulan qzx123', $kets);
    }

    public function test_store_tanpa_jenis_tercatat_koperasi(): void
    {
        $this->masuk('BEN-000001');

        $this->post(route('pengeluaran.store'), [
            'jumlah' => 75_000,
            'keterangan' => 'Tanpa jenis jadi koperasi',
            'tipe' => 'lain_lain',
            'akun' => 'kas_kecil',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertStatus(302);

        $this->assertDatabaseHas('pengeluaran', ['keterangan' => 'Tanpa jenis jadi koperasi', 'jenis' => 'koperasi']);
        $this->assertDatabaseHas('jurnal_kas', [
            'kategori' => 'pengeluaran_koperasi', 'tipe' => 'keluar', 'jumlah' => 75_000,
        ]);
    }

    public function test_pengeluaran_tak_bersentuhan_saldo_lain(): void
    {
        $this->masuk('BEN-000001');
        $sebelum = KasKoperasi::first()->only(['saldo_pinjaman', 'saldo_dana_sosial', 'saldo_kas_kecil', 'saldo_bank']);

        $this->post(route('pengeluaran.store'), [
            'jumlah' => 100_000,
            'keterangan' => 'Santunan anggota sakit',
            'tipe' => 'santunan',
            'akun' => 'kas_kecil',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertStatus(302);

        $sesudah = KasKoperasi::first();
        $this->assertEquals((float) $sebelum['saldo_pinjaman'], (float) $sesudah->saldo_pinjaman);
        $this->assertEquals((float) $sebelum['saldo_dana_sosial'], (float) $sesudah->saldo_dana_sosial);
        $this->assertEquals((float) $sebelum['saldo_bank'], (float) $sesudah->saldo_bank);
        $this->assertEquals((float) $sebelum['saldo_kas_kecil'] - 100_000, (float) $sesudah->saldo_kas_kecil);
    }

    public function test_pengeluaran_besar_dari_bank_tanpa_pagu(): void
    {
        $this->masuk('BEN-000001');
        $sebelum = KasKoperasi::first()->only(['saldo_kas_kecil', 'saldo_bank']);

        $this->post(route('pengeluaran.store'), [
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
            'jumlah' => 1,
            'keterangan' => 'Santunan saat kas kecil habis',
            'tipe' => 'santunan',
            'akun' => 'kas_kecil',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertSessionHasErrors('jumlah');

        $this->assertSame(0, Pengeluaran::where('keterangan', 'Santunan saat kas kecil habis')->count());
    }

    public function test_pengeluaran_koperasi_melebihi_saldo_kas_kecil_ditolak(): void
    {
        $this->masuk('BEN-000001');
        $kas = KasKoperasi::first();
        $kasKecil = (float) $kas->saldo_kas_kecil;

        $this->post(route('pengeluaran.store'), [
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
            'jumlah' => 100_000,
            'keterangan' => 'Tanpa tipe',
            'akun' => 'kas_kecil',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertSessionHasErrors('tipe');

        // Tipe di luar pilihan → validasi menolak
        $this->post(route('pengeluaran.store'), [
            'jumlah' => 100_000,
            'keterangan' => 'Tipe asing',
            'tipe' => 'operasional',
            'akun' => 'kas_kecil',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertSessionHasErrors('tipe');

        $this->post(route('pengeluaran.store'), [
            'jumlah' => 120_000,
            'keterangan' => 'Pajak parkir kantor',
            'tipe' => 'pajak',
            'akun' => 'kas_kecil',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertStatus(302);

        $this->assertDatabaseHas('pengeluaran', ['keterangan' => 'Pajak parkir kantor', 'tipe' => 'pajak']);

        $res = $this->get(route('pengeluaran.index', ['tipe' => 'pajak']))->assertOk();
        $props = $res->viewData('page')['props'];
        $this->assertSame(1, count($props['pengeluaran']['data']));
        $this->assertSame('Pajak', $props['pengeluaran']['data'][0]['tipe_label']);

        $resLain = $this->get(route('pengeluaran.index', ['tipe' => 'santunan']))->assertOk();
        $rowsLain = $resLain->viewData('page')['props']['pengeluaran']['data'];
        $this->assertNotEmpty($rowsLain);
        foreach ($rowsLain as $row) {
            $this->assertSame('santunan', $row['tipe']);
        }
    }

    public function test_sisih_kas_kecil_memindahkan_dari_bank(): void
    {
        $this->masuk('BEN-000001');
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
        $this->masuk('BEN-000001');
        $kas = KasKoperasi::first();
        $bank = (float) $kas->saldo_bank;

        $this->post(route('kas-koperasi.sisih-kas-kecil'), [
            'jumlah' => $bank + 1,
            'keterangan' => 'Melebihi saldo bank',
        ])->assertSessionHasErrors('jumlah');
    }

    public function test_batalkan_pengeluaran_membalik_saldo_dan_jaga_laporan(): void
    {
        $this->masuk('BEN-000001');
        $tahun = (int) now()->format('Y');
        $hariIni = now()->format('Y-m-d');
        $kasAwal = KasKoperasi::first()->only(['saldo_bank', 'saldo_kas_kecil']);
        $shuAwal = LaporanRegistry::totalShu($tahun);
        $neracaAwal = LaporanRegistry::ambil('neraca')['data'](request()->merge(['tanggal' => $hariIni]));
        $shuNeracaAwal = collect($neracaAwal['rows'])->firstWhere(fn ($r) => $r[0] === 'Selisih Hasil Usaha')[1];

        $this->post(route('pengeluaran.store'), [
            'jumlah' => 250_000,
            'keterangan' => 'Beli ATK salah pilih akun',
            'tipe' => 'perlengkapan',
            'akun' => 'kas_kecil',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertStatus(302);

        $jurnalSalah = JurnalKas::where('keterangan', 'Beli ATK salah pilih akun')->firstOrFail();
        $pengeluaranSalah = Pengeluaran::findOrFail($jurnalSalah->referensi_id);
        $this->assertEquals((float) $kasAwal['saldo_kas_kecil'] - 250_000, (float) KasKoperasi::first()->saldo_kas_kecil);
        $this->assertEquals($shuAwal - 250_000, LaporanRegistry::totalShu($tahun));

        $this->post(route('kas-koperasi.batalkan', $jurnalSalah->id), [
            'alasan' => 'Salah pilih Kas Kecil, seharusnya Bank',
        ])->assertStatus(302)->assertSessionHasNoErrors();

        $kasSetelahBatal = KasKoperasi::first();
        $this->assertEquals((float) $kasAwal['saldo_kas_kecil'], (float) $kasSetelahBatal->saldo_kas_kecil);
        $this->assertEquals((float) $kasAwal['saldo_bank'], (float) $kasSetelahBatal->saldo_bank);

        $jurnalSalah->refresh();
        $pengeluaranSalah->refresh();
        $this->assertNotNull($jurnalSalah->dibatalkan_pada);
        $this->assertNotNull($pengeluaranSalah->dibatalkan_pada);
        $this->assertSame('Salah pilih Kas Kecil, seharusnya Bank', $jurnalSalah->alasan_batal);

        $this->assertDatabaseHas('jurnal_kas', [
            'koreksi_dari_id' => $jurnalSalah->id,
            'tipe' => 'masuk',
            'kategori' => 'pengeluaran_koperasi',
            'kantong' => 'kas_kecil',
            'jumlah' => 250_000,
        ]);

        // Input ulang ke akun yang benar (Bank) → Laba Rugi / SHU hanya terhitung 1x (Rp 250.000)
        $this->post(route('pengeluaran.store'), [
            'jumlah' => 250_000,
            'keterangan' => 'Beli ATK dari Bank (benar)',
            'tipe' => 'perlengkapan',
            'akun' => 'bank',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertStatus(302);

        $kasAkhir = KasKoperasi::first();
        $this->assertEquals((float) $kasAwal['saldo_kas_kecil'], (float) $kasAkhir->saldo_kas_kecil);
        $this->assertEquals((float) $kasAwal['saldo_bank'] - 250_000, (float) $kasAkhir->saldo_bank);
        $this->assertEquals($shuAwal - 250_000, LaporanRegistry::totalShu($tahun));

        $neraca = LaporanRegistry::ambil('neraca')['data'](request()->merge(['tanggal' => $hariIni]));
        $aktiva = collect($neraca['rows'])->firstWhere(fn ($r) => $r[0] === 'TOTAL AKTIVA')[1];
        $shuNeraca = collect($neraca['rows'])->firstWhere(fn ($r) => $r[0] === 'Selisih Hasil Usaha')[1];
        $this->assertEquals($shuNeracaAwal - 250_000, $shuNeraca);

        $neracaAkhirTahun = LaporanRegistry::ambil('neraca')['data'](request()->merge(['tanggal' => sprintf('%04d-12-31', $tahun)]));
        $shuNeracaAkhirTahun = collect($neracaAkhirTahun['rows'])->firstWhere(fn ($r) => $r[0] === 'Selisih Hasil Usaha')[1];
        $this->assertEquals($shuAwal - 250_000, $shuNeracaAkhirTahun);
        $this->assertGreaterThan(0, $aktiva);
    }

    public function test_batalkan_topup_bunga_bank_membalik_saldo_dan_shu(): void
    {
        $this->masuk('BEN-000001');
        $tahun = (int) now()->format('Y');
        $bankAwal = (float) KasKoperasi::first()->saldo_bank;
        $shuAwal = LaporanRegistry::totalShu($tahun);

        $this->post(route('kas-koperasi.topup'), [
            'jumlah' => 300_000,
            'sumber_topup' => 'bunga_bank',
            'keterangan' => 'Salah input bunga bank',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertStatus(302);

        $jurnal = JurnalKas::where('keterangan', 'Salah input bunga bank')->firstOrFail();
        $this->assertEquals($bankAwal + 300_000, (float) KasKoperasi::first()->saldo_bank);
        $this->assertEquals($shuAwal + 300_000, LaporanRegistry::totalShu($tahun));

        $this->post(route('kas-koperasi.batalkan', $jurnal->id), [
            'alasan' => 'Koreksi salah catat bunga bank',
        ])->assertStatus(302)->assertSessionHasNoErrors();

        $this->assertEquals($bankAwal, (float) KasKoperasi::first()->saldo_bank);
        $this->assertEquals($shuAwal, LaporanRegistry::totalShu($tahun));
        $this->assertDatabaseHas('jurnal_kas', [
            'koreksi_dari_id' => $jurnal->id,
            'tipe' => 'keluar',
            'kantong' => 'bank',
            'jumlah' => 300_000,
        ]);
    }

    public function test_batalkan_sisih_kas_kecil_membalik_kedua_baris_atomik(): void
    {
        $this->masuk('BEN-000001');
        $kasAwal = KasKoperasi::first()->only(['saldo_bank', 'saldo_kas_kecil']);

        $this->post(route('kas-koperasi.sisih-kas-kecil'), [
            'jumlah' => 750_000,
            'keterangan' => 'Sisih salah nominal',
        ])->assertStatus(302);

        $barisKeluarBank = JurnalKas::where('kategori', 'sisih_kas_kecil')
            ->where('keterangan', 'Sisih salah nominal')
            ->firstOrFail();
        $barisMasukKasKecil = JurnalKas::where('kategori', 'terima_sisih_kas_kecil')
            ->where('keterangan', 'Sisih salah nominal')
            ->firstOrFail();

        $this->post(route('kas-koperasi.batalkan', $barisKeluarBank->id), [
            'alasan' => 'Nominal sisih salah',
        ])->assertStatus(302)->assertSessionHasNoErrors();

        $kasAkhir = KasKoperasi::first();
        $this->assertEquals((float) $kasAwal['saldo_bank'], (float) $kasAkhir->saldo_bank);
        $this->assertEquals((float) $kasAwal['saldo_kas_kecil'], (float) $kasAkhir->saldo_kas_kecil);

        $this->assertNotNull($barisKeluarBank->fresh()->dibatalkan_pada);
        $this->assertNotNull($barisMasukKasKecil->fresh()->dibatalkan_pada);
        $this->assertDatabaseHas('jurnal_kas', [
            'koreksi_dari_id' => $barisKeluarBank->id,
            'tipe' => 'masuk',
            'kantong' => 'bank',
            'jumlah' => 750_000,
        ]);
        $this->assertDatabaseHas('jurnal_kas', [
            'koreksi_dari_id' => $barisMasukKasKecil->id,
            'tipe' => 'keluar',
            'kantong' => 'kas_kecil',
            'jumlah' => 750_000,
        ]);
    }

    public function test_tolak_pembatalan_ganda_baris_pembalik_kategori_non_operasional_dan_tanpa_izin(): void
    {
        $this->masuk('BEN-000001');

        $this->post(route('pengeluaran.store'), [
            'jumlah' => 100_000,
            'keterangan' => 'Uji tolak batal ganda',
            'tipe' => 'lain_lain',
            'akun' => 'kas_kecil',
            'tanggal' => now()->format('Y-m-d'),
        ])->assertStatus(302);

        $asal = JurnalKas::where('keterangan', 'Uji tolak batal ganda')->firstOrFail();

        $this->post(route('kas-koperasi.batalkan', $asal->id), [
            'alasan' => 'Pembatalan pertama',
        ])->assertStatus(302)->assertSessionHasNoErrors();

        // Pembatalan kedua atas baris yang sama ditolak
        $this->post(route('kas-koperasi.batalkan', $asal->id), [
            'alasan' => 'Coba batal ulang',
        ])->assertSessionHasErrors('alasan');

        // Pembatalan atas baris storno ditolak
        $pembalik = JurnalKas::where('koreksi_dari_id', $asal->id)->firstOrFail();
        $this->post(route('kas-koperasi.batalkan', $pembalik->id), [
            'alasan' => 'Coba batal pembalik',
        ])->assertSessionHasErrors('alasan');

        // Kategori non-operasional (pembayaran_angsuran) ditolak
        $angsuranJurnal = JurnalKas::create([
            'tipe' => 'masuk',
            'kategori' => 'pembayaran_angsuran',
            'kantong' => 'pinjaman',
            'jumlah' => 200_000,
            'saldo_setelah' => 0,
            'keterangan' => 'Angsuran uji tolak batal',
            'tanggal' => now(),
            'created_by' => auth()->id(),
        ]);
        $this->post(route('kas-koperasi.batalkan', $angsuranJurnal->id), [
            'alasan' => 'Coba batal angsuran',
        ])->assertSessionHasErrors('alasan');

        // Admin tanpa permission kas.topup ditolak 403
        $this->masuk('ADM-000001');
        $this->post(route('kas-koperasi.batalkan', $asal->id), [
            'alasan' => 'Akses tidak sah',
        ])->assertStatus(403);
    }
}
