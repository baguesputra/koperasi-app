<?php

namespace Tests\Feature;

use App\Laporan\LaporanRegistry;
use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\AngsuranPercepatan;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\PengajuanPercepatan;
use App\Models\PengajuanTransaksiTabungan;
use App\Models\Pengeluaran;
use App\Models\Pinjaman;
use App\Models\RekeningAnggota;
use App\Models\RekeningTabungan;
use App\Models\SettingBungaTabungan;
use App\Models\SettingTabungan;
use App\Models\ShuPeriode;
use App\Models\Simpanan;
use App\Models\TabunganBungaPeriode;
use App\Models\TabunganTransaksi;
use App\Services\Anggota\ResignService;
use App\Services\Keuangan\JurnalKasService;
use App\Services\Tabungan\PerhitunganBungaTabunganService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class TabunganTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
    }

    public function test_anggota_dapat_mengajukan_dan_bendahara_menyetujui_pembukaan_rekening(): void
    {
        $anggota = $this->buatAnggota('TAB-900001');
        $this->actingAs($anggota->user);

        $this->get(route('portal.tabungan.index'))->assertOk();

        $this->post(route('portal.tabungan.buka'), [
            'setuju_syarat' => true,
        ])->assertRedirect();

        $rekening = RekeningTabungan::where('anggota_id', $anggota->id)->sole();
        $this->assertSame('diajukan', $rekening->status);
        $this->assertEqualsWithDelta(2.00, (float) $rekening->suku_bunga_saat_buka, 0.01);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'tabungan_rekening_diajukan']);

        // Pengajuan kedua saat masih diajukan harus ditolak
        $this->post(route('portal.tabungan.buka'), [
            'setuju_syarat' => true,
        ])->assertSessionHasErrors('buka');

        // Bendahara tidak bisa melompati tahap Sekretaris
        $this->masuk('BEN-000001');
        $this->post(route('bendahara.tabungan.buka.setujui', $rekening->id), ['catatan' => 'Lompat'])
            ->assertSessionHasErrors('aksi');

        // Tahap 1: Sekretaris memverifikasi pembukaan rekening
        $this->masuk('ADM-000001');
        $this->post(route('admin.tabungan.buka.approve', $rekening->id), [
            'catatan' => 'Data keanggotaan valid',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('approved_admin', $rekening->refresh()->status);

        // Tahap 2: Bendahara meninjau pembukaan rekening
        $this->masuk('BEN-000001');
        $this->post(route('bendahara.tabungan.buka.setujui', $rekening->id), [
            'catatan' => 'Oke lanjut ke Ketua',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('approved_bendahara', $rekening->refresh()->status);

        // Tahap 3: Ketua Koperasi menyetujui dan mengaktifkan rekening
        $this->masuk('KET-000001');
        $this->post(route('ketua.tabungan.buka.approve', $rekening->id), [
            'catatan' => 'Disetujui Ketua',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $rekening->refresh();
        $this->assertSame('aktif', $rekening->status);
        $this->assertMatchesRegularExpression('/^TAB-\d{4}-\d{4}$/', (string) $rekening->no_rekening);
        $this->assertNotNull($rekening->tanggal_buka);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'tabungan_rekening_disetujui']);
    }

    public function test_pengajuan_buka_ditolak_jika_fitur_dinonaktifkan_di_pengaturan(): void
    {
        SettingTabungan::current()->update(['buka_rekening_aktif' => false]);

        $anggota = $this->buatAnggota('TAB-900002');
        $this->actingAs($anggota->user);

        $this->post(route('portal.tabungan.buka'), [
            'setuju_syarat' => true,
        ])->assertSessionHasErrors('buka');

        $this->assertDatabaseCount('rekening_tabungan', 0);
    }

    public function test_setor_transfer_dengan_bukti_privat_anti_idor_dan_jurnal_kas(): void
    {
        $anggota = $this->buatAnggota('TAB-900003');
        $anggotaLain = $this->buatAnggota('TAB-900004');

        $rekening = $this->buatRekeningAktif($anggota);
        $bankAwal = (float) KasKoperasi::first()->saldo_bank;

        $this->actingAs($anggota->user);
        $file = UploadedFile::fake()->image('bukti-transfer.jpg', 400, 400);

        $this->post(route('portal.tabungan.setor'), [
            'nominal' => 500_000,
            'tanggal_transaksi' => now()->toDateString(),
            'bukti' => $file,
            'catatan' => 'Setor bulan ini',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $pengajuan = PengajuanTransaksiTabungan::where('rekening_tabungan_id', $rekening->id)->sole();
        $this->assertSame('menunggu', $pengajuan->status);
        Storage::disk('local')->assertExists($pengajuan->bukti_path);

        // Proteksi IDOR: anggota pemilik boleh lihat, anggota lain dilarang (403)
        $this->get(route('tabungan.bukti.pengajuan', ['pengajuan' => $pengajuan->id, 'tipe' => 'setor']))->assertOk();

        $this->actingAs($anggotaLain->user);
        $this->get(route('tabungan.bukti.pengajuan', ['pengajuan' => $pengajuan->id, 'tipe' => 'setor']))->assertForbidden();

        // Bendahara boleh lihat & setujui
        $this->masuk('BEN-000001');
        $this->get(route('tabungan.bukti.pengajuan', ['pengajuan' => $pengajuan->id, 'tipe' => 'setor']))->assertOk();

        $this->post(route('bendahara.tabungan.pengajuan.setujui', $pengajuan->id), [
            'catatan' => 'Mutasi cocok',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $rekening->refresh();
        $pengajuan->refresh();
        $this->assertSame('disetujui', $pengajuan->status);
        $this->assertEqualsWithDelta(500_000, (float) $rekening->saldo, 0.01);
        $this->assertEqualsWithDelta($bankAwal + 500_000, (float) KasKoperasi::first()->saldo_bank, 0.01);

        $this->assertDatabaseHas('jurnal_kas', [
            'tipe' => 'masuk',
            'kategori' => 'setor_tabungan',
            'kantong' => 'tabungan',
            'jumlah' => 500_000,
        ]);

        // Bukti transaksi juga terlindungi anti-IDOR
        $trx = TabunganTransaksi::where('pengajuan_id', $pengajuan->id)->sole();
        $this->actingAs($anggotaLain->user);
        $this->get(route('tabungan.bukti.transaksi', $trx->id))->assertForbidden();
        $this->actingAs($anggota->user);
        $this->get(route('tabungan.bukti.transaksi', $trx->id))->assertOk();
    }

    public function test_setor_tunai_penarikan_saldo_minimal_pin_dan_tutup_rekening(): void
    {
        $anggota = $this->buatAnggota('TAB-900005');
        $anggota->user->update(['pin' => '135790']);
        $rekTujuan = RekeningAnggota::create([
            'anggota_id' => $anggota->id,
            'nama_bank' => 'BCA',
            'no_rekening' => '888123456',
            'atas_nama' => $anggota->nama,
            'is_default' => true,
        ]);
        $rekening = $this->buatRekeningAktif($anggota);

        // Bendahara input setor tunai ke kas kecil
        $kasKecilAwal = (float) KasKoperasi::first()->saldo_kas_kecil;
        $this->masuk('BEN-000001');
        $this->post(route('bendahara.tabungan.setor-tunai'), [
            'rekening_tabungan_id' => $rekening->id,
            'nominal' => 1_000_000,
            'akun_kas' => 'kas_kecil',
            'tanggal' => now()->toDateString(),
            'keterangan' => 'Setor tunai loket',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $rekening->refresh();
        $this->assertEqualsWithDelta(1_000_000, (float) $rekening->saldo, 0.01);
        $this->assertEqualsWithDelta($kasKecilAwal + 1_000_000, (float) KasKoperasi::first()->saldo_kas_kecil, 0.01);

        // Anggota tarik dengan PIN salah -> gagal
        $this->actingAs($anggota->user);
        $this->post(route('portal.tabungan.tarik'), [
            'tutup_rekening' => false,
            'nominal' => 200_000,
            'kanal' => 'transfer',
            'rekening_anggota_id' => $rekTujuan->id,
            'pin' => '000000',
        ])->assertSessionHasErrors('pin');

        // Anggota tarik melanggar saldo minimal (1jt - 970rb = 30rb < 50rb) -> gagal
        $this->post(route('portal.tabungan.tarik'), [
            'tutup_rekening' => false,
            'nominal' => 970_000,
            'kanal' => 'transfer',
            'rekening_anggota_id' => $rekTujuan->id,
            'pin' => '135790',
        ])->assertSessionHasErrors('tarik');

        // Anggota ajukan tarik valid 300rb -> berhasil
        $this->post(route('portal.tabungan.tarik'), [
            'tutup_rekening' => false,
            'nominal' => 300_000,
            'kanal' => 'transfer',
            'rekening_anggota_id' => $rekTujuan->id,
            'pin' => '135790',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $pengajuanTarik = PengajuanTransaksiTabungan::where('rekening_tabungan_id', $rekening->id)
            ->where('jenis', 'tarik')
            ->sole();

        // Tahap 1-3 Approval Berjenjang Penarikan (Sekretaris -> Bendahara -> Ketua) belum memotong saldo / kas
        $bankSebelumTarik = (float) KasKoperasi::first()->saldo_bank;

        $this->masuk('ADM-000001');
        $this->post(route('admin.tabungan.pengajuan.approve', $pengajuanTarik->id), [
            'catatan' => 'Data penarikan valid',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('approved_admin', $pengajuanTarik->refresh()->status);

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.tabungan.pengajuan.setujui', $pengajuanTarik->id), [
            'catatan' => 'Likuiditas aman',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('approved_bendahara', $pengajuanTarik->refresh()->status);

        $this->masuk('KET-000001');
        $this->post(route('ketua.tabungan.pengajuan.approve', $pengajuanTarik->id), [
            'catatan' => 'Disetujui Ketua untuk pencairan',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('approved_ketua', $pengajuanTarik->refresh()->status);

        // Sebelum pencairan Bendahara: saldo dan kas bank belum berubah
        $this->assertEqualsWithDelta(1_000_000, (float) $rekening->refresh()->saldo, 0.01);
        $this->assertEqualsWithDelta($bankSebelumTarik, (float) KasKoperasi::first()->saldo_bank, 0.01);

        // Bendahara eksekusi pencairan dengan tanggal transfer + upload bukti
        $this->masuk('BEN-000001');
        $this->post(route('bendahara.tabungan.pengajuan.cair', $pengajuanTarik->id), [
            'akun_kas' => 'bank',
            'tanggal_pencairan' => now()->toDateString(),
            'bukti_pencairan' => UploadedFile::fake()->image('bukti-cair-tabungan.jpg'),
            'catatan' => 'Sudah ditransfer ke rekening BCA anggota',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $rekening->refresh();
        $pengajuanTarik->refresh();
        $this->assertSame('disetujui', $pengajuanTarik->status);
        $this->assertNotNull($pengajuanTarik->bukti_pencairan_path);
        Storage::disk('local')->assertExists($pengajuanTarik->bukti_pencairan_path);
        $this->assertEqualsWithDelta(700_000, (float) $rekening->saldo, 0.01);
        $this->assertEqualsWithDelta($bankSebelumTarik - 300_000, (float) KasKoperasi::first()->saldo_bank, 0.01);
    }

    public function test_masa_tunggu_tarik_tabungan_1_tahun_tetap_bisa_setor_dan_resign(): void
    {
        $anggota = $this->buatAnggota('TAB-900010');
        $anggota->user->update(['pin' => '135790']);
        $rekTujuan = RekeningAnggota::create([
            'anggota_id' => $anggota->id,
            'nama_bank' => 'BRI',
            'no_rekening' => '777888999',
            'atas_nama' => $anggota->nama,
            'is_default' => true,
        ]);

        // Rekening baru dibuka hari ini (< 12 bulan) dengan saldo awal Rp 1.000.000
        $rekening = $this->buatRekeningAktif($anggota, now()->toDateString());
        $rekening->update(['saldo' => 1_000_000]);

        $this->actingAs($anggota->user);

        // Penarikan biasa & tutup rekening sukarela diblokir sebelum 1 tahun
        $this->post(route('portal.tabungan.tarik'), [
            'tutup_rekening' => false,
            'nominal' => 200_000,
            'kanal' => 'transfer',
            'rekening_anggota_id' => $rekTujuan->id,
            'pin' => '135790',
        ])->assertSessionHasErrors('tarik');

        $this->post(route('portal.tabungan.tarik'), [
            'tutup_rekening' => true,
            'kanal' => 'transfer',
            'rekening_anggota_id' => $rekTujuan->id,
            'pin' => '135790',
        ])->assertSessionHasErrors('tarik');

        // Menambah setoran tabungan tetap diperbolehkan kapan saja
        $this->post(route('portal.tabungan.setor'), [
            'nominal' => 250_000,
            'tanggal_transaksi' => now()->toDateString(),
            'bukti' => UploadedFile::fake()->image('setor-awal.jpg'),
        ])->assertRedirect()->assertSessionHasNoErrors();

        // Resign sebelum 1 tahun tetap memproses pengembalian tabungan tanpa terhalang lock-in
        $ben = $this->masuk('BEN-000001');
        app(ResignService::class)->proses($anggota->fresh(), 'Resign sebelum 1 tahun', now()->toDateString(), $ben);
        $rekening->refresh();
        $this->assertSame('ditutup', $rekening->status);
        $this->assertEqualsWithDelta(0, (float) $rekening->saldo, 0.01);

        // Setelah >= 12 bulan sejak tanggal buka, penarikan normal dapat diajukan
        $anggotaLama = $this->buatAnggota('TAB-900011');
        $anggotaLama->user->update(['pin' => '135790']);
        $rekTujuanLama = RekeningAnggota::create([
            'anggota_id' => $anggotaLama->id,
            'nama_bank' => 'Mandiri',
            'no_rekening' => '1234432100',
            'atas_nama' => $anggotaLama->nama,
            'is_default' => true,
        ]);
        $rekeningLama = $this->buatRekeningAktif($anggotaLama, now()->subMonthsNoOverflow(12)->toDateString());
        $rekeningLama->update(['saldo' => 1_000_000]);

        $this->actingAs($anggotaLama->user);
        $this->post(route('portal.tabungan.tarik'), [
            'tutup_rekening' => false,
            'nominal' => 200_000,
            'kanal' => 'transfer',
            'rekening_anggota_id' => $rekTujuanLama->id,
            'pin' => '135790',
        ])->assertRedirect()->assertSessionHasNoErrors();
    }

    public function test_storno_transaksi_tabungan_membalik_saldo_dan_jurnal(): void
    {
        $anggota = $this->buatAnggota('TAB-900006');
        $rekening = $this->buatRekeningAktif($anggota);
        $bankAwal = (float) KasKoperasi::first()->saldo_bank;

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.tabungan.setor-tunai'), [
            'rekening_tabungan_id' => $rekening->id,
            'nominal' => 400_000,
            'akun_kas' => 'bank',
            'tanggal' => now()->toDateString(),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $trx = TabunganTransaksi::where('rekening_tabungan_id', $rekening->id)->sole();

        $this->post(route('bendahara.tabungan.transaksi.batalkan', $trx->id), [
            'alasan' => 'Salah input nominal setoran',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $rekening->refresh();
        $trx->refresh();
        $this->assertNotNull($trx->dibatalkan_pada);
        $this->assertEqualsWithDelta(0, (float) $rekening->saldo, 0.01);
        $this->assertEqualsWithDelta($bankAwal, (float) KasKoperasi::first()->saldo_bank, 0.01);
        $this->assertDatabaseHas('tabungan_transaksi', [
            'koreksi_dari_id' => $trx->id,
            'jenis' => 'koreksi_keluar',
        ]);
    }

    public function test_perhitungan_bunga_harian_dan_snapshot_immutable_saat_tarif_berubah(): void
    {
        Carbon::setTestNow('2026-11-30 20:00:00');
        try {
            $anggota = $this->buatAnggota('TAB-900007');
            $rekening = RekeningTabungan::create([
                'anggota_id' => $anggota->id,
                'no_rekening' => 'TAB-2026-0999',
                'status' => 'aktif',
                'saldo' => 10_000_000,
                'suku_bunga_saat_buka' => 2.00,
                'setuju_syarat' => true,
                'tanggal_pengajuan' => '2026-11-01',
                'tanggal_buka' => '2026-11-01',
            ]);

            TabunganTransaksi::create([
                'rekening_tabungan_id' => $rekening->id,
                'anggota_id' => $anggota->id,
                'no_bukti' => '001/JKM/XI/2026-T',
                'jenis' => 'setor',
                'kanal' => 'transfer',
                'nominal' => 10_000_000,
                'saldo_sebelum' => 0,
                'saldo_sesudah' => 10_000_000,
                'tanggal' => '2026-11-01',
                'keterangan' => 'Setoran awal',
            ]);

            // Tarif berubah di tengah bulan: 1-15 Nov = 2.00%, 16-30 Nov = 3.00%
            SettingBungaTabungan::create([
                'persentase' => 3.00,
                'berlaku_dari_tanggal' => '2026-11-16',
                'keterangan' => 'Kenaikan tengah bulan',
            ]);

            $service = app(PerhitunganBungaTabunganService::class);
            $hasil = $service->postingPeriode('2026-11', null);

            $this->assertSame(1, $hasil['diproses']);

            // 15 hari @ 2% + 15 hari @ 3% atas saldo 10.000.000
            $expected = round((10_000_000 * 0.02 / 365 * 15) + (10_000_000 * 0.03 / 365 * 15), 2);
            $bungaPeriode = TabunganBungaPeriode::where('rekening_tabungan_id', $rekening->id)
                ->where('periode', '2026-11')
                ->sole();

            $this->assertEqualsWithDelta($expected, (float) $bungaPeriode->bunga_bersih, 0.01);
            $this->assertCount(2, $bungaPeriode->rincian_tarif_json);
            $this->assertEqualsWithDelta(10_000_000 + $expected, (float) $rekening->fresh()->saldo, 0.01);

            // Idempotensi: posting ulang periode sama tidak menduplikasi bunga
            $ulang = $service->postingPeriode('2026-11', null);
            $this->assertSame(0, $ulang['diproses']);
            $this->assertSame(1, TabunganBungaPeriode::where('rekening_tabungan_id', $rekening->id)->count());

            // Ubah tarif jadi 5.00% di Desember -> snapshot November TIDAK boleh ikut berubah
            SettingBungaTabungan::create([
                'persentase' => 5.00,
                'berlaku_dari_tanggal' => '2026-12-01',
                'keterangan' => 'Tarif baru Des',
            ]);

            $bungaPeriode->refresh();
            $this->assertEqualsWithDelta($expected, (float) $bungaPeriode->bunga_bersih, 0.01);
            $this->assertEqualsWithDelta(2.50, (float) $bungaPeriode->suku_bunga_snapshot, 0.01);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_akuntansi_neraca_tetap_seimbang_seluruh_siklus_tabungan_dan_resign(): void
    {
        Carbon::setTestNow('2026-11-30 12:00:00');
        try {
            $ben = $this->masuk('BEN-000001');
            $anggota = $this->buatAnggota('TAB-900008');
            $rekening = RekeningTabungan::create([
                'anggota_id' => $anggota->id,
                'no_rekening' => 'TAB-2026-0888',
                'status' => 'aktif',
                'saldo' => 0,
                'suku_bunga_saat_buka' => 2.00,
                'setuju_syarat' => true,
                'tanggal_pengajuan' => '2026-11-01',
                'tanggal_buka' => '2026-11-01',
            ]);

            // Bersihkan transaksi dummy hasil seed() agar baseline Neraca mulai tepat dari Rp 0 seimbang
            AngsuranPercepatan::query()->delete();
            PengajuanPercepatan::query()->delete();
            Angsuran::query()->delete();
            Pinjaman::query()->delete();
            Simpanan::query()->delete();
            Pengeluaran::query()->delete();
            ShuPeriode::query()->delete();
            JurnalKas::query()->delete();

            $kas = KasKoperasi::first();
            $kas->update([
                'saldo_bank' => 0,
                'saldo_kas_kecil' => 0,
                'saldo_pinjaman' => 0,
                'saldo_dana_sosial' => 0,
                'saldo_pengembalian_simpanan' => 0,
                'saldo_simpanan' => 0,
            ]);

            app(JurnalKasService::class)->catat(
                tipe: 'masuk',
                kategori: 'topup_bulanan',
                kantong: 'bank',
                jumlah: 20_000_000,
                keterangan: 'Modal awal uji neraca tabungan',
                referensiId: null,
                tanggal: '2026-11-01',
                userId: $ben->id,
                sumberTopup: 'modal',
            );

            config([
                'koperasi.modal_tetap' => 20_000_000,
                'koperasi.laba_ditahan_historis' => 0,
            ]);

            // 1. Setor tunai ke bank pada 2026-11-01
            $this->post(route('bendahara.tabungan.setor-tunai'), [
                'rekening_tabungan_id' => $rekening->id,
                'nominal' => 5_000_000,
                'akun_kas' => 'bank',
                'tanggal' => '2026-11-01',
            ])->assertRedirect()->assertSessionHasNoErrors();

            // 2. Posting bunga November 2026
            $this->post(route('bendahara.tabungan.posting-bunga'), [
                'periode' => '2026-11',
            ])->assertRedirect()->assertSessionHasNoErrors();

            $bunga = (float) TabunganBungaPeriode::where('rekening_tabungan_id', $rekening->id)->value('bunga_bersih');
            $this->assertGreaterThan(0, $bunga);

            $tgl = '2026-11-30';
            $neraca1 = LaporanRegistry::ambil('neraca')['data'](request()->merge(['tanggal' => $tgl]));
            $selisih1 = collect($neraca1['ringkasan'])->firstWhere(fn ($r) => str_contains($r[0], 'Selisih'))[1];
            $this->assertSame('Rp 0 — Seimbang', $selisih1);

            $nilai1 = fn (string $pos) => collect($neraca1['rows'])->firstWhere(fn ($r) => $r[0] === $pos)[1];
            $this->assertEqualsWithDelta(5_000_000 + $bunga, $nilai1('Tabungan Anggota'), 0.01);

            // Laba Rugi & SHU berkurang sebesar beban bunga tabungan
            $labaRugi = LaporanRegistry::ambil('laba-rugi')['data'](request()->merge(['tahun' => 2026]));
            $barisBebanBunga = collect($labaRugi['rows'])->firstWhere(fn ($r) => $r[0] === 'Beban Bunga Tabungan');
            $this->assertNotNull($barisBebanBunga);
            $this->assertEqualsWithDelta($bunga, $barisBebanBunga[13], 0.01);
            $this->assertEqualsWithDelta(-$bunga, LaporanRegistry::totalShu(2026), 0.01);

            // 3. Proses Resign -> rekening tabungan otomatis ditutup & dikembalikan
            app(ResignService::class)->proses($anggota, 'Mengundurkan diri', $tgl, $ben);
            $rekening->refresh();
            $this->assertSame('ditutup', $rekening->status);
            $this->assertEqualsWithDelta(0, (float) $rekening->saldo, 0.01);

            $neraca2 = LaporanRegistry::ambil('neraca')['data'](request()->merge(['tanggal' => $tgl]));
            $selisih2 = collect($neraca2['ringkasan'])->firstWhere(fn ($r) => str_contains($r[0], 'Selisih'))[1];
            $this->assertSame('Rp 0 — Seimbang', $selisih2);
            $nilai2 = fn (string $pos) => collect($neraca2['rows'])->firstWhere(fn ($r) => $r[0] === $pos)[1];
            $this->assertEqualsWithDelta(0, $nilai2('Tabungan Anggota'), 0.01);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_rbac_tabungan_dan_pengaturan_suku_bunga_snapshot(): void
    {
        $anggota = $this->buatAnggota('TAB-900009');
        $rekening = $this->buatRekeningAktif($anggota);

        // Sekretaris bisa verifikasi tahap 1 dan lihat daftar/detail/kebijakan (read-only), tapi dilarang di proses bendahara & ketua
        $this->masuk('ADM-000001');
        $this->get(route('tabungan.index'))->assertOk();
        $this->get(route('tabungan.show', $rekening->id))->assertOk();
        $this->get(route('admin.tabungan.index'))->assertOk();
        $this->get(route('bendahara.tabungan.index'))->assertForbidden();
        $this->get(route('ketua.tabungan.index'))->assertForbidden();

        session(['auth.password_confirmed_at' => time()]);
        $this->get(route('pengaturan.index', ['tab' => 'aturan-tabungan']))->assertOk();
        $this->post(route('pengaturan.tabungan.bunga.update'), [
            'persentase' => 3.5,
            'berlaku_dari_tanggal' => now()->toDateString(),
        ])->assertForbidden();

        // Pengawas hanya view data tabungan (Revisi 4), dilarang akses approval & pengaturan
        $this->masuk('WAS-000001');
        $this->get(route('tabungan.index'))->assertOk();
        $this->get(route('tabungan.show', $rekening->id))->assertOk();
        $this->get(route('admin.tabungan.index'))->assertForbidden();
        $this->get(route('bendahara.tabungan.index'))->assertForbidden();
        $this->get(route('ketua.tabungan.index'))->assertForbidden();
        session(['auth.password_confirmed_at' => time()]);
        $this->get(route('pengaturan.index', ['tab' => 'aturan-tabungan']))->assertForbidden();

        // Bendahara bisa akses proses tabungan, tapi tidak bisa ubah kebijakan bunga
        $this->masuk('BEN-000001');
        $this->get(route('bendahara.tabungan.index'))->assertOk();
        session(['auth.password_confirmed_at' => time()]);
        $this->post(route('pengaturan.tabungan.bunga.update'), [
            'persentase' => 3.5,
            'berlaku_dari_tanggal' => now()->toDateString(),
        ])->assertForbidden();

        // Ketua Koperasi bisa akses approval ketua dan ubah suku bunga & ketentuan tabungan
        $this->masuk('KET-000001');
        $this->get(route('ketua.tabungan.index'))->assertOk();
        session(['auth.password_confirmed_at' => time()]);
        $this->post(route('pengaturan.tabungan.bunga.update'), [
            'persentase' => 2.75,
            'berlaku_dari_tanggal' => '2026-12-01',
            'keterangan' => 'Penyesuaian RAT',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('setting_bunga_tabungan', [
            'persentase' => 2.75,
            'keterangan' => 'Penyesuaian RAT',
        ]);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'update_bunga_tabungan']);

        $this->post(route('pengaturan.tabungan.ketentuan.update'), [
            'metode_bunga' => 'saldo_harian',
            'min_setor' => 100_000,
            'saldo_minimal' => 50_000,
            'min_tarik' => 50_000,
            'masa_tunggu_tarik_bulan' => 18,
            'bank_tujuan_nama' => 'Bank BRI',
            'bank_tujuan_rekening' => '1122334455',
            'bank_tujuan_atas_nama' => 'Koperasi Karya Mandiri',
            'buka_rekening_aktif' => true,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(100_000.0, (float) SettingTabungan::current()->min_setor);
        $this->assertSame(18, (int) SettingTabungan::current()->masa_tunggu_tarik_bulan);
        $this->assertSame('Bank BRI', SettingTabungan::current()->bank_tujuan_nama);
    }

    private function buatRekeningAktif(Anggota $anggota, ?string $tanggalBuka = null): RekeningTabungan
    {
        $tgl = $tanggalBuka ?? now()->subMonthsNoOverflow(13)->toDateString();

        return RekeningTabungan::create([
            'anggota_id' => $anggota->id,
            'no_rekening' => 'TAB-2026-'.str_pad((string) random_int(100, 9999), 4, '0', STR_PAD_LEFT),
            'status' => 'aktif',
            'saldo' => 0,
            'suku_bunga_saat_buka' => 2.00,
            'versi_syarat' => 'v1.0-2026-11-23',
            'setuju_syarat' => true,
            'tanggal_pengajuan' => $tgl,
            'tanggal_buka' => $tgl,
        ]);
    }
}
