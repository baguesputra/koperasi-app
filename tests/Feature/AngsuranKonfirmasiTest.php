<?php

namespace Tests\Feature;

use App\Laporan\LaporanRegistry;
use App\Models\AuditLog;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\PengajuanPenghapusanPinjaman;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class AngsuranKonfirmasiTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        // Pastikan role 'anggota' ter-refresh di transaction ini
        if (! Role::where('name', 'anggota')->exists()) {
            Role::firstOrCreate(['name' => 'anggota', 'guard_name' => 'web']);
        }
    }

    private function buatPinjamanAktifDenganAngsuran(int $tenor = 3): Pinjaman
    {
        $anggota = $this->buatAnggota();
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 900_000,
            'tenor_bulan' => 3,
            'keperluan' => 'Uji',
            'persentase_bunga' => 1,
            'status' => 'aktif',
            'tanggal_pengajuan' => now()->subMonths(2),
            'tanggal_pencairan' => now()->subMonths(2),
        ]);

        foreach ([1 => [300_000, 9_000], 2 => [300_000, 6_000], 3 => [300_000, 3_000]] as $ke => [$pokok, $bunga]) {
            $pinjaman->angsuran()->create([
                'cicilan_ke' => $ke, 'nominal_pokok' => $pokok, 'nominal_bunga' => $bunga,
                'total_bayar' => $pokok + $bunga, 'status' => 'belum_bayar',
                'tanggal_jatuh_tempo' => now()->subMonths(2 - ($ke - 1))->endOfMonth(),
            ]);
        }

        return $pinjaman;
    }

    public function test_konfirmasi_satuan_mencatat_jurnal_dan_lunas(): void
    {
        $pinjaman = $this->buatPinjamanAktifDenganAngsuran();
        $angsuran = $pinjaman->angsuran()->where('cicilan_ke', 1)->first();

        $this->masuk('BEN-000001');
        $saldoSebelum = (float) KasKoperasi::first()->saldo_bank;

        $this->post(route('bendahara.angsuran.konfirmasi'), [
            'angsuran_ids' => ['n-'.$angsuran->id],
        ])->assertStatus(302);

        $this->assertDatabaseHas('angsuran', ['id' => $angsuran->id, 'status' => 'lunas']);
        $this->assertDatabaseHas('jurnal_kas', [
            'kategori' => 'pembayaran_angsuran', 'tipe' => 'masuk',
            'jumlah' => 309_000, 'referensi_id' => $angsuran->id,
        ]);
        $this->assertEquals($saldoSebelum + 309_000, (float) KasKoperasi::first()->saldo_bank);
    }

    public function test_pinjaman_otomatis_lunas_saats_semua_cicilan_selesai(): void
    {
        $pinjaman = $this->buatPinjamanAktifDenganAngsuran();
        $ids = $pinjaman->angsuran()->pluck('id')->map(fn ($id) => 'n-'.$id)->all();

        $jurnalSebelum = (int) (JurnalKas::max('id') ?? 0);
        $this->masuk('BEN-000001');
        $this->post(route('bendahara.angsuran.konfirmasi'), ['angsuran_ids' => $ids])->assertStatus(302);

        $this->assertSame('lunas', $pinjaman->refresh()->status);
        // ponytail: referensi_id polimorfik tanpa kolom tipe — scope via id jurnal baru agar bebas tabrakan data seeder
        $totalBayar = JurnalKas::where('kategori', 'pembayaran_angsuran')
            ->where('id', '>', $jurnalSebelum)->sum('jumlah');
        $this->assertEquals(918_000, (float) $totalBayar);
    }

    public function test_index_menandai_angsuran_dengan_pengajuan_percepatan_berjalan(): void
    {
        $pinjaman = $this->buatPinjamanAktifDenganAngsuran();
        PengajuanPercepatan::create([
            'pinjaman_id' => $pinjaman->id, 'tipe' => 'perpanjang',
            'tenor_lama' => 3, 'tenor_baru' => 5, 'keterangan' => 'Berat bulan ini',
            'status' => 'diajukan', 'tanggal_pengajuan' => now(),
        ]);

        $this->masuk('BEN-000001');
        $res = $this->get(route('bendahara.angsuran.index'));

        $res->assertOk()->assertInertia(fn ($page) => $page->component('Bendahara/Angsuran/Index'));

        $adaTanda = collect($res->viewData('page')['props']['daftarAngsuran'] ?? [])
            ->contains(fn ($item) => $item['ada_pengajuan_percepatan'] === true && $item['no_anggota'] === $pinjaman->anggota->no_anggota);
        $this->assertTrue($adaTanda);
    }

    public function test_rollback_konfirmasi_angsuran_mengembalikan_status_pinjaman_saldo_dan_neraca(): void
    {
        $pinjaman = $this->buatPinjamanAktifDenganAngsuran();
        $angsuran1 = $pinjaman->angsuran()->where('cicilan_ke', 1)->firstOrFail();
        $angsuran2 = $pinjaman->angsuran()->where('cicilan_ke', 2)->firstOrFail();
        $angsuran3 = $pinjaman->angsuran()->where('cicilan_ke', 3)->firstOrFail();

        $this->masuk('BEN-000001');
        $hariIni = now()->format('Y-m-d');

        // Konfirmasi cicilan 1 & 2 terlebih dahulu, lalu catat baseline neraca sebelum cicilan 3
        $this->post(route('bendahara.angsuran.konfirmasi'), [
            'angsuran_ids' => ['n-'.$angsuran1->id, 'n-'.$angsuran2->id],
        ])->assertStatus(302);

        $bankSebelum3 = (float) KasKoperasi::first()->saldo_bank;
        $neracaSebelum3 = LaporanRegistry::ambil('neraca')['data'](request()->merge(['tanggal' => $hariIni]));
        $nilaiSebelum3 = fn (string $pos) => collect($neracaSebelum3['rows'])->firstWhere(fn ($r) => $r[0] === $pos)[1];
        $piutangSebelum3 = $nilaiSebelum3('Piutang');
        $shuSebelum3 = $nilaiSebelum3('Selisih Hasil Usaha');

        // Konfirmasi cicilan ke-3 → pinjaman otomatis jadi lunas
        $this->post(route('bendahara.angsuran.konfirmasi'), [
            'angsuran_ids' => ['n-'.$angsuran3->id],
        ])->assertStatus(302);
        $this->assertSame('lunas', $pinjaman->refresh()->status);
        $this->assertEquals($bankSebelum3 + 303_000, (float) KasKoperasi::first()->saldo_bank);

        // Rollback cicilan ke-3
        $this->post(route('bendahara.angsuran.batalkan', 'n-'.$angsuran3->id), [
            'alasan' => 'Salah contreng pelunasan cicilan ke-3',
        ])->assertStatus(302)->assertSessionHasNoErrors();

        $angsuran3->refresh();
        $this->assertSame('belum_bayar', $angsuran3->status);
        $this->assertNull($angsuran3->tanggal_konfirmasi_bayar);
        $this->assertNull($angsuran3->confirmed_by);
        $this->assertSame('aktif', $pinjaman->refresh()->status);
        $this->assertEquals($bankSebelum3, (float) KasKoperasi::first()->saldo_bank);

        $jurnalAsal = JurnalKas::where('kategori', 'pembayaran_angsuran')
            ->where('referensi_id', $angsuran3->id)
            ->where('keterangan', 'like', 'Angsuran ke-3 - %')
            ->whereNull('koreksi_dari_id')
            ->firstOrFail();
        $this->assertNotNull($jurnalAsal->dibatalkan_pada);
        $this->assertDatabaseHas('jurnal_kas', [
            'koreksi_dari_id' => $jurnalAsal->id,
            'tipe' => 'keluar',
            'kategori' => 'pembayaran_angsuran',
            'jumlah' => 303_000,
        ]);

        // Cek tampilan tab di halaman konfirmasi angsuran (bulan jatuh tempo cicilan ke-3)
        $bulan3 = $angsuran3->tanggal_jatuh_tempo->format('Y-m');
        $props = $this->get(route('bendahara.angsuran.index', ['bulan' => $bulan3]))
            ->assertOk()
            ->viewData('page')['props'];
        $this->assertContains('n-'.$angsuran3->id, array_column($props['daftarAngsuran'], 'id'));
        $this->assertNotContains('n-'.$angsuran3->id, array_column($props['sudahDikonfirmasi'], 'id'));

        $neracaSetelah = LaporanRegistry::ambil('neraca')['data'](request()->merge(['tanggal' => $hariIni]));
        $nilaiSetelah = fn (string $pos) => collect($neracaSetelah['rows'])->firstWhere(fn ($r) => $r[0] === $pos)[1];
        $this->assertEquals($piutangSebelum3, $nilaiSetelah('Piutang'));
        $this->assertEquals($shuSebelum3, $nilaiSetelah('Selisih Hasil Usaha'));
        $this->assertEquals($bankSebelum3, $nilaiSetelah('Bank'));

        $this->assertTrue(AuditLog::where('aksi', 'angsuran_konfirmasi_dibatalkan')->exists());
    }

    public function test_rollback_angsuran_menolak_jika_tidak_urut_ada_pinjaman_baru_atau_tanpa_izin(): void
    {
        $pinjaman = $this->buatPinjamanAktifDenganAngsuran();
        $angsuran1 = $pinjaman->angsuran()->where('cicilan_ke', 1)->firstOrFail();
        $angsuran2 = $pinjaman->angsuran()->where('cicilan_ke', 2)->firstOrFail();

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.angsuran.konfirmasi'), [
            'angsuran_ids' => ['n-'.$angsuran1->id, 'n-'.$angsuran2->id],
        ])->assertStatus(302);

        // Tidak boleh rollback cicilan 1 selama cicilan 2 masih lunas
        $this->post(route('bendahara.angsuran.batalkan', 'n-'.$angsuran1->id), [
            'alasan' => 'Coba rollback cicilan 1',
        ])->assertSessionHasErrors('alasan');
        $this->assertSame('lunas', $angsuran1->refresh()->status);

        // Bila ada pinjaman baru setelahnya, cicilan 2 juga ditolak rollback
        Pinjaman::create([
            'anggota_id' => $pinjaman->anggota_id,
            'pengaju_user_id' => $pinjaman->pengaju_user_id,
            'nominal' => 500_000,
            'tenor_bulan' => 2,
            'keperluan' => 'Pinjaman baru',
            'persentase_bunga' => 1,
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
        ]);

        $this->post(route('bendahara.angsuran.batalkan', 'n-'.$angsuran2->id), [
            'alasan' => 'Coba rollback setelah ada pinjaman baru',
        ])->assertSessionHasErrors('alasan');
        $this->assertSame('lunas', $angsuran2->refresh()->status);

        // Admin tanpa permission angsuran.konfirmasi ditolak 403
        $this->masuk('ADM-000001');
        $this->post(route('bendahara.angsuran.batalkan', 'n-'.$angsuran2->id), [
            'alasan' => 'Akses tidak sah',
        ])->assertStatus(403);
    }

    public function test_angsuran_terlambat_masuk_tab_terlambat_bulan_berjalan_dan_rollover_dari_bulan_lalu(): void
    {
        $pinjaman = $this->buatPinjamanAktifDenganAngsuran();
        $angsuran1 = $pinjaman->angsuran()->where('cicilan_ke', 1)->firstOrFail();
        $angsuran2 = $pinjaman->angsuran()->where('cicilan_ke', 2)->firstOrFail();
        $angsuran3 = $pinjaman->angsuran()->where('cicilan_ke', 3)->firstOrFail();

        $bulanSatuLalu = now()->startOfMonth()->subMonthsNoOverflow(1)->format('Y-m');

        $this->masuk('BEN-000001');

        // 1. Pada bulan berjalan: daftarAngsuran hanya memuat cicilan bulan ini, daftarTerlambat memuat cicilan bulan lalu
        $propsKini = $this->get(route('bendahara.angsuran.index'))
            ->assertOk()
            ->viewData('page')['props'];

        $regulerIds = array_column($propsKini['daftarAngsuran'], 'id');
        $terlambatItems = collect($propsKini['daftarTerlambat']);
        $terlambatIds = $terlambatItems->pluck('id')->all();

        $this->assertContains('n-'.$angsuran3->id, $regulerIds);
        $this->assertNotContains('n-'.$angsuran1->id, $regulerIds);
        $this->assertNotContains('n-'.$angsuran2->id, $regulerIds);

        $this->assertContains('n-'.$angsuran1->id, $terlambatIds);
        $this->assertContains('n-'.$angsuran2->id, $terlambatIds);
        $this->assertNotContains('n-'.$angsuran3->id, $terlambatIds);
        $this->assertTrue($terlambatItems->firstWhere('id', 'n-'.$angsuran1->id)['terlambat']);

        // Urutan terlambat kronologis (cicilan 1 lebih dulu dari cicilan 2)
        $pos1 = array_search('n-'.$angsuran1->id, $terlambatIds, true);
        $pos2 = array_search('n-'.$angsuran2->id, $terlambatIds, true);
        $this->assertLessThan($pos2, $pos1);

        // 2. Pada bulan lalu (Opsi 1): daftarAngsuran & daftarTerlambat kosong, dipindahKeBulanIni menghitung tunggakan periode itu
        $propsLalu = $this->get(route('bendahara.angsuran.index', ['bulan' => $bulanSatuLalu]))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertSame([], $propsLalu['daftarAngsuran']);
        $this->assertSame([], $propsLalu['daftarTerlambat']);
        $this->assertGreaterThanOrEqual(1, $propsLalu['dipindahKeBulanIni']['jumlah']);

        // 3. Konfirmasi cicilan terlambat ke-1 dari bulan berjalan -> hilang dari Terlambat & langsung muncul di Sudah Dikonfirmasi bulan berjalan
        $this->post(route('bendahara.angsuran.konfirmasi'), [
            'angsuran_ids' => ['n-'.$angsuran1->id],
        ])->assertStatus(302);

        $propsSesudah = $this->get(route('bendahara.angsuran.index'))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertNotContains('n-'.$angsuran1->id, array_column($propsSesudah['daftarTerlambat'], 'id'));
        $itemLunas = collect($propsSesudah['sudahDikonfirmasi'])->firstWhere('id', 'n-'.$angsuran1->id);
        $this->assertNotNull($itemLunas);
        $this->assertTrue($itemLunas['dari_bulan_lain']);
    }

    public function test_validasi_konfirmasi_urut_periode_mendatang_dan_kunci_percepatan_penghapusan(): void
    {
        $pinjaman = $this->buatPinjamanAktifDenganAngsuran();
        $angsuran1 = $pinjaman->angsuran()->where('cicilan_ke', 1)->firstOrFail();
        $angsuran2 = $pinjaman->angsuran()->where('cicilan_ke', 2)->firstOrFail();
        $angsuran3 = $pinjaman->angsuran()->where('cicilan_ke', 3)->firstOrFail();

        $this->masuk('BEN-000001');

        // 1. Melompati cicilan ke-1/2 (konfirmasi cicilan ke-3 saja) harus ditolak
        $this->post(route('bendahara.angsuran.konfirmasi'), [
            'angsuran_ids' => ['n-'.$angsuran3->id],
        ])->assertStatus(302)->assertSessionHasErrors('konfirmasi');
        $this->assertSame('belum_bayar', $angsuran3->refresh()->status);

        // Props index menandai angsuran3 tetap ada namun bisa_dipilih = false dengan info tunggakan ke-1
        $props = $this->get(route('bendahara.angsuran.index'))->assertOk()->viewData('page')['props'];
        $baris3 = collect($props['daftarAngsuran'])->firstWhere('id', 'n-'.$angsuran3->id);
        $this->assertNotNull($baris3);
        $this->assertFalse($baris3['bisa_dipilih']);
        $this->assertSame(1, $baris3['tunggakan_sebelumnya']['cicilan_ke']);

        // 2. Melompat sebagian (konfirmasi [1, 3] tanpa 2) tetap ditolak
        $this->post(route('bendahara.angsuran.konfirmasi'), [
            'angsuran_ids' => ['n-'.$angsuran1->id, 'n-'.$angsuran3->id],
        ])->assertStatus(302)->assertSessionHasErrors('konfirmasi');
        $this->assertSame('belum_bayar', $angsuran1->refresh()->status);

        // 3. Batch berurutan [1, 2] lalu [3] valid
        $this->post(route('bendahara.angsuran.konfirmasi'), [
            'angsuran_ids' => ['n-'.$angsuran1->id, 'n-'.$angsuran2->id],
        ])->assertStatus(302)->assertSessionHasNoErrors();
        $this->assertSame('lunas', $angsuran1->refresh()->status);
        $this->assertSame('lunas', $angsuran2->refresh()->status);

        // 4. Pinjaman dengan pengajuan percepatan pending mengunci konfirmasi cicilan ke-3
        $perc = PengajuanPercepatan::create([
            'pinjaman_id' => $pinjaman->id,
            'tipe' => 'percepat',
            'tenor_lama' => 3,
            'tenor_baru' => 2,
            'keterangan' => 'Uji kunci',
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
        ]);
        $this->post(route('bendahara.angsuran.konfirmasi'), [
            'angsuran_ids' => ['n-'.$angsuran3->id],
        ])->assertStatus(302)->assertSessionHasErrors('konfirmasi');
        $perc->update(['status' => 'ditolak']);

        // 5. Pinjaman dengan pengajuan penghapusan aktif mengunci konfirmasi cicilan ke-3
        $hapus = PengajuanPenghapusanPinjaman::create([
            'pinjaman_id' => $pinjaman->id,
            'anggota_id' => $pinjaman->anggota_id,
            'tanggal_pengajuan' => now()->toDateString(),
            'alasan' => 'Macet uji',
            'nominal_pinjaman' => 900_000,
            'total_pokok_terbayar' => 600_000,
            'total_bunga_terbayar' => 15_000,
            'sisa_cicilan' => 1,
            'sisa_pokok' => 300_000,
            'sisa_bunga' => 3_000,
            'sisa_total_tagihan' => 303_000,
            'status' => 'diajukan',
        ]);
        $this->post(route('bendahara.angsuran.konfirmasi'), [
            'angsuran_ids' => ['n-'.$angsuran3->id],
        ])->assertStatus(302)->assertSessionHasErrors('konfirmasi');
        $hapus->update(['status' => 'ditolak']);

        // 6. Cicilan periode mendatang tidak boleh dikonfirmasi
        $angsuran3->update(['tanggal_jatuh_tempo' => now()->addMonthNoOverflow()->endOfMonth()]);
        $this->post(route('bendahara.angsuran.konfirmasi'), [
            'angsuran_ids' => ['n-'.$angsuran3->id],
        ])->assertStatus(302)->assertSessionHasErrors('konfirmasi');
    }
}
