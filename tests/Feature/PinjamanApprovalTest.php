<?php

namespace Tests\Feature;

use App\Jobs\KirimWaJob;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\Pinjaman;
use App\Services\Keuangan\JurnalKasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class PinjamanApprovalTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function ajukanPortal($anggota, array $override = [])
    {
        return $this->post(route('portal.pinjaman.store'), array_merge([
            'nominal' => 1_000_000,
            'tenor_bulan' => 3,
            'keperluan' => 'Modal usaha sampingan',
            'rekening_mode' => 'baru',
            'nama_bank' => 'BCA',
            'no_rekening' => '1234567890',
            'atas_nama' => $anggota->nama,
            'persetujuan' => true,
        ], $override));
    }

    public function test_anggota_dengan_pinjaman_aktif_belum_bisa_ajukan_lagi_jika_kurang_dari_satu_tahun(): void
    {
        Queue::fake();
        // Anggota baru (< 1 tahun keanggotaan)
        $baru = $this->buatAnggota('TOP-900001', ['tanggal_jadi_anggota' => now()->subMonths(6)]);

        // 1. Pengajuan pertama oleh anggota sendiri → boleh (limit penuh)
        $this->actingAs($baru->user);
        $this->ajukanPortal($baru)->assertStatus(302);
        $this->assertSame(1, Pinjaman::where('anggota_id', $baru->id)->count());
        $pinjaman = Pinjaman::where('anggota_id', $baru->id)->sole();

        // 2. Cair hingga aktif: admin → bendahara → ketua
        $this->masuk('ADM-000001');
        $this->post(route('admin.pinjaman.approve', $pinjaman), ['catatan' => 'Dokumen lengkap, diteruskan.'])->assertStatus(302);
        $this->assertSame('approved_admin', $pinjaman->refresh()->status);
        $this->masuk('BEN-000001');
        $this->post(route('bendahara.pinjaman.approve', $pinjaman), ['catatan' => 'Setuju, data lengkap.', 'nominal' => 1_000_000])->assertStatus(302);
        $this->masuk('KET-000001');
        $this->post(route('ketua.pinjaman.approve', $pinjaman), ['catatan' => 'Disetujui Ketua.', 'nominal' => 1_000_000])->assertStatus(302);
        $this->assertSame('aktif', $pinjaman->refresh()->status);

        // 3. Ajukan kedua saat masih ada pinjaman aktif → ditolak aturan anggota baru (< 1 tahun)
        $this->actingAs($baru->user);
        $res = $this->ajukanPortal($baru);

        $res->assertSessionHasErrors();

        $this->assertSame(1, Pinjaman::where('anggota_id', $baru->id)->count());
    }

    public function test_alur_lengkap_anggota_hingga_cair_ketua_dengan_wa_pdf(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota();

        // 1. Pengajuan via portal
        $this->actingAs($anggota->user);
        $res = $this->ajukanPortal($anggota);
        $res->assertStatus(302);

        $pinjaman = Pinjaman::where('anggota_id', $anggota->id)->sole();
        $this->assertSame('diajukan', $pinjaman->status);

        // 2. Admin meneruskan
        $this->masuk('ADM-000001');
        $this->post(route('admin.pinjaman.approve', $pinjaman), ['catatan' => 'Dokumen lengkap, diteruskan.'])->assertRedirect();
        $this->assertSame('approved_admin', $pinjaman->refresh()->status);

        // 3. Bendahara menyetujui
        $this->masuk('BEN-000001');
        $this->post(route('bendahara.pinjaman.approve', $pinjaman), ['catatan' => 'Dokumen lengkap.', 'nominal' => 1_000_000])->assertRedirect();
        $this->assertSame('approved_bendahara', $pinjaman->refresh()->status);

        // 4. Ketua menyetujui → cair
        $saldoSebelum = (float) KasKoperasi::first()->saldo_bank;
        $this->masuk('KET-000001');
        $this->post(route('ketua.pinjaman.approve', $pinjaman), ['catatan' => 'Disetujui.', 'nominal' => 1_000_000])->assertRedirect();

        $pinjaman->refresh();
        $this->assertSame('aktif', $pinjaman->status);
        $this->assertNotNull($pinjaman->tanggal_pencairan);
        $this->assertCount(3, $pinjaman->angsuran()->get());

        // Jurnal pencairan keluar dari bank + saldo bank berkurang
        $this->assertDatabaseHas('jurnal_kas', [
            'kategori' => 'pencairan_pinjaman', 'kantong' => 'pinjaman',
            'tipe' => 'keluar', 'referensi_id' => $pinjaman->id,
        ]);
        $this->assertEquals($saldoSebelum - 1_000_000, (float) KasKoperasi::first()->saldo_bank);

        // WA ke anggota membawa dokumen bukti peminjaman
        $namaFileBukti = "Bukti-Peminjaman-{$pinjaman->id}.pdf";
        Queue::assertPushed(KirimWaJob::class, function (KirimWaJob $job) use ($namaFileBukti) {
            $p = $this->propertiWa($job);

            return $p['event'] === 'pinjaman_disetujui_ketua'
                && ($p['dokumen']['filename'] ?? '') === $namaFileBukti;
        });
    }

    public function test_penolakan_oleh_bendahara_dan_ketua_mengirim_wa(): void
    {
        Queue::fake();
        $a1 = $this->buatAnggota('TOP-900002');
        $a2 = $this->buatAnggota('TOP-900003');

        $this->actingAs($a1->user);
        $this->ajukanPortal($a1)->assertRedirect();
        $p1 = Pinjaman::where('anggota_id', $a1->id)->sole();

        $this->actingAs($a2->user);
        $this->ajukanPortal($a2)->assertRedirect();
        $p2 = Pinjaman::where('anggota_id', $a2->id)->sole();

        $this->masuk('ADM-000001');
        $this->post(route('admin.pinjaman.approve', $p1), ['catatan' => 'Diteruskan ke Bendahara.'])->assertRedirect();
        $this->post(route('admin.pinjaman.approve', $p2), ['catatan' => 'Diteruskan ke Bendahara.'])->assertRedirect();

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.pinjaman.reject', $p1), ['catatan' => 'Gaji belum cukup lama.'])->assertRedirect();
        $this->assertSame('ditolak', $p1->refresh()->status);
        $this->assertNotNull($p1->catatan_admin);

        $this->post(route('bendahara.pinjaman.approve', $p2), ['catatan' => 'Diteruskan ke Ketua.', 'nominal' => 1_000_000])->assertRedirect();

        $this->masuk('KET-000001');
        $this->post(route('ketua.pinjaman.reject', $p2), ['catatan' => 'Menunggu periode berikutnya.'])->assertRedirect();
        $this->assertSame('ditolak', $p2->refresh()->status);

        Queue::assertPushed(KirimWaJob::class, fn (KirimWaJob $job) => $this->propertiWa($job)['event'] === 'pinjaman_ditolak');
    }

    public function test_nominal_editable_dua_tahap_dengan_jejak_kas(): void
    {
        $anggota = $this->buatAnggota('TOP-900010');
        $this->actingAs($anggota->user);
        $this->ajukanPortal($anggota, ['nominal' => 5_000_000, 'tenor_bulan' => 12])->assertStatus(302);
        $pinjaman = Pinjaman::where('anggota_id', $anggota->id)->sole();

        $this->assertEquals(5_000_000, (float) $pinjaman->nominal_diminta);
        $this->assertSame(12, (int) $pinjaman->tenor_diminta);

        $kasAwal = app(JurnalKasService::class)->saldoOperasional();
        $bankAwal = (float) KasKoperasi::first()->saldo_bank;

        // Bendahara turunkan ke 4jt, tenor ikut auto-clamp bila perlu
        $this->masuk('ADM-000001');
        $this->post(route('admin.pinjaman.approve', $pinjaman), ['catatan' => 'Diteruskan ke Bendahara.'])->assertRedirect();
        $this->masuk('BEN-000001');
        $this->post(route('bendahara.pinjaman.approve', $pinjaman), [
            'catatan' => 'Disesuaikan kemampuan kas.',
            'nominal' => 4_000_000,
        ])->assertRedirect();

        $pinjaman->refresh();
        $this->assertSame('approved_bendahara', $pinjaman->status);
        $this->assertEquals(4_000_000, (float) $pinjaman->nominal_disetujui_bendahara);
        $this->assertEquals($kasAwal, (float) $pinjaman->kas_saldo_bendahara);
        // Kas belum berkurang di tahap bendahara
        $this->assertEquals($kasAwal, app(JurnalKasService::class)->saldoOperasional());

        // Ketua naikkan lagi ke 4.5jt → cair final
        $this->masuk('KET-000001');
        $this->post(route('ketua.pinjaman.approve', $pinjaman), [
            'catatan' => 'Final disetujui.',
            'nominal' => 4_500_000,
        ])->assertRedirect();

        $pinjaman->refresh();
        $this->assertSame('aktif', $pinjaman->status);
        $this->assertEquals(4_500_000, (float) $pinjaman->nominal);
        $this->assertEquals(4_500_000, (float) $pinjaman->nominal_disetujui);
        $this->assertEquals($bankAwal - 4_500_000, (float) KasKoperasi::first()->saldo_bank);
        $this->assertEquals($kasAwal - 4_500_000, app(JurnalKasService::class)->saldoOperasional());
        $this->assertEquals($kasAwal, (float) $pinjaman->kas_saldo_ketua);
        $this->assertEquals($kasAwal - 4_500_000, (float) $pinjaman->kas_sisa_ketua);
        // Jejak diminta awet
        $this->assertEquals(5_000_000, (float) $pinjaman->nominal_diminta);
        // Jadwal mengikuti final
        $this->assertEquals(4_500_000, (float) $pinjaman->angsuran()->sum('nominal_pokok'));
    }

    public function test_nominal_ditolak_bila_melebihi_limit_tersedia(): void
    {
        $anggota = $this->buatAnggota('TOP-900011');
        $this->actingAs($anggota->user);
        $this->ajukanPortal($anggota, ['nominal' => 1_000_000, 'tenor_bulan' => 3])->assertStatus(302);
        $pinjaman = Pinjaman::where('anggota_id', $anggota->id)->sole();

        $this->masuk('ADM-000001');
        $this->post(route('admin.pinjaman.approve', $pinjaman), ['catatan' => 'Diteruskan ke Bendahara.'])->assertRedirect();

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.pinjaman.approve', $pinjaman), [
            'catatan' => 'Coba naikkan.',
            'nominal' => 9_000_000,
        ])->assertSessionHasErrors('keputusan');

        $this->assertSame('approved_admin', $pinjaman->refresh()->status);
    }

    public function test_ketua_approve_cair_dari_bank_tanpa_talangan(): void
    {
        Queue::fake();
        KasKoperasi::first()->update(['saldo_bank' => 2_000_000]);

        $anggota = $this->buatAnggota();
        $this->actingAs($anggota->user);
        $this->ajukanPortal($anggota)->assertRedirect();
        $pinjaman = Pinjaman::where('anggota_id', $anggota->id)->sole();

        $this->masuk('ADM-000001');
        $this->post(route('admin.pinjaman.approve', $pinjaman), ['catatan' => 'Diteruskan ke Bendahara.'])->assertRedirect();

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.pinjaman.approve', $pinjaman), ['catatan' => 'Dokumen lengkap, layak cair.', 'nominal' => 1_000_000])->assertRedirect();

        // Konsep Kas Tunggal: cukup saldo bank → cair langsung, tanpa talangan.
        $this->masuk('KET-000001');
        $this->post(route('ketua.pinjaman.approve', $pinjaman), ['catatan' => 'Cairkan.', 'nominal' => 1_000_000])->assertRedirect();

        $this->assertSame('aktif', $pinjaman->refresh()->status);
        $this->assertSame(1, JurnalKas::where('kategori', 'pencairan_pinjaman')->where('referensi_id', $pinjaman->id)->count());
        $this->assertSame(0, JurnalKas::whereIn('kategori', ['talangan_sosial_ke_pinjaman', 'talangan_simpanan_ke_pinjaman'])->count());
    }

    public function test_antrean_approval_bergerak_admin_bendahara_ketua(): void
    {
        $a1 = $this->buatAnggota('TOP-900020');
        $a2 = $this->buatAnggota('TOP-900021');

        $this->actingAs($a1->user);
        $this->ajukanPortal($a1, ['nominal' => 2_000_000, 'tenor_bulan' => 4])->assertStatus(302);
        $this->actingAs($a2->user);
        $this->ajukanPortal($a2, ['nominal' => 3_000_000, 'tenor_bulan' => 6])->assertStatus(302);

        $ukurAntrean = function (string $route, string $kunci) use ($a1, $a2) {
            $res = $this->get(route($route))->assertOk();
            $daftar = $res->original->getData()['page']['props'][$kunci];

            return collect($daftar)->whereIn('anggota.no_karyawan', [$a1->no_karyawan, $a2->no_karyawan])->values();
        };

        // Admin teruskan keduanya dulu agar masuk antrean bendahara
        $this->masuk('ADM-000001');
        $this->assertCount(2, $ukurAntrean('admin.pinjaman.index', 'menungguTinjauan'));
        $p1 = Pinjaman::where('anggota_id', $a1->id)->sole();
        $p2 = Pinjaman::where('anggota_id', $a2->id)->sole();
        $this->post(route('admin.pinjaman.approve', $p1), ['catatan' => 'Diteruskan ke Bendahara.'])->assertRedirect();
        $this->post(route('admin.pinjaman.approve', $p2), ['catatan' => 'Diteruskan ke Bendahara.'])->assertRedirect();
        $this->assertCount(0, $ukurAntrean('admin.pinjaman.index', 'menungguTinjauan'));

        // Keduanya masuk antrean bendahara
        $this->masuk('BEN-000001');
        $this->assertCount(2, $ukurAntrean('bendahara.pinjaman.index', 'menungguTinjauan'));

        $this->post(route('bendahara.pinjaman.approve', $p1), ['catatan' => 'Usulan disesuaikan.', 'nominal' => 1_500_000])
            ->assertRedirect();

        // p1 keluar dari antrean bendahara, masuk antrean ketua
        $this->assertCount(1, $ukurAntrean('bendahara.pinjaman.index', 'menungguTinjauan'));

        $this->masuk('KET-000001');
        $this->assertCount(1, $ukurAntrean('ketua.pinjaman.index', 'menungguApproval'));

        $this->get(route('ketua.pinjaman.show', $p1))->assertOk();

        $this->post(route('ketua.pinjaman.approve', $p1), ['catatan' => 'Cairkan final.', 'nominal' => 1_500_000])
            ->assertRedirect();

        $this->assertCount(0, $ukurAntrean('ketua.pinjaman.index', 'menungguApproval'));
    }

    public function test_ringkasan_pinjaman_tampil_di_tiga_halaman_approval(): void
    {
        Queue::fake();
        KasKoperasi::first()->update(['saldo_bank' => 50_000_000]);

        $a1 = $this->buatAnggota('TOP-900030');
        $a2 = $this->buatAnggota('TOP-900031');

        $this->actingAs($a1->user);
        $this->ajukanPortal($a1, ['nominal' => 2_000_000, 'tenor_bulan' => 4])->assertStatus(302);
        $this->actingAs($a2->user);
        $this->ajukanPortal($a2, ['nominal' => 3_000_000, 'tenor_bulan' => 6])->assertStatus(302);

        $p1 = Pinjaman::where('anggota_id', $a1->id)->sole();

        $ringkasan = function (string $akun, string $route): array {
            $this->masuk($akun);
            $res = $this->get(route($route))->assertOk();

            return $res->original->getData()['page']['props']['ringkasanKas'];
        };

        $awal = $ringkasan('ADM-000001', 'admin.pinjaman.index');
        $this->assertEquals(50_000_000, $awal['saldo_bank']);
        $this->assertGreaterThanOrEqual(2, $awal['jumlah_antrean']);
        $this->assertGreaterThanOrEqual(5_000_000, $awal['total_antrean']);
        $this->assertEquals($awal['saldo_bank'] - $awal['total_antrean'], $awal['sisa_proyeksi']);

        $this->post(route('admin.pinjaman.approve', $p1), ['catatan' => 'Diteruskan ke Bendahara.'])->assertRedirect();

        $setelahAdmin = $ringkasan('ADM-000001', 'admin.pinjaman.index');
        $this->assertSame($awal['jumlah_antrean'], $setelahAdmin['jumlah_antrean']);
        $this->assertEquals($awal['total_antrean'], $setelahAdmin['total_antrean']);
        $this->assertSame($awal['jumlah_disetujui'] + 1, $setelahAdmin['jumlah_disetujui']);
        $this->assertEquals($setelahAdmin['saldo_bank'] - $setelahAdmin['total_antrean'], $setelahAdmin['sisa_proyeksi']);

        foreach ([
            $ringkasan('BEN-000001', 'bendahara.pinjaman.index'),
            $ringkasan('KET-000001', 'ketua.pinjaman.index'),
        ] as $halaman) {
            $this->assertSame($setelahAdmin['jumlah_antrean'], $halaman['jumlah_antrean']);
            $this->assertEquals($setelahAdmin['total_antrean'], $halaman['total_antrean']);
            $this->assertSame($setelahAdmin['jumlah_disetujui'], $halaman['jumlah_disetujui']);
            $this->assertEquals($setelahAdmin['sisa_proyeksi'], $halaman['sisa_proyeksi']);
        }

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.pinjaman.approve', $p1), [
            'catatan' => 'Nominal disesuaikan.',
            'nominal' => 1_500_000,
        ])->assertRedirect();

        $setelahBendahara = $ringkasan('BEN-000001', 'bendahara.pinjaman.index');
        $this->assertSame($setelahAdmin['jumlah_antrean'], $setelahBendahara['jumlah_antrean']);
        $this->assertEquals($setelahAdmin['total_antrean'] - 500_000, $setelahBendahara['total_antrean']);
        $this->assertSame($setelahAdmin['jumlah_disetujui'], $setelahBendahara['jumlah_disetujui']);

        $ketuaSebelum = $ringkasan('KET-000001', 'ketua.pinjaman.index');
        $bankSebelum = (float) KasKoperasi::first()->saldo_bank;

        $this->post(route('ketua.pinjaman.approve', $p1), [
            'catatan' => 'Cairkan final.',
            'nominal' => 1_500_000,
        ])->assertRedirect();

        $ketuaSesudah = $ringkasan('KET-000001', 'ketua.pinjaman.index');
        $this->assertSame($ketuaSebelum['jumlah_antrean'] - 1, $ketuaSesudah['jumlah_antrean']);
        $this->assertEquals($ketuaSebelum['total_antrean'] - 1_500_000, $ketuaSesudah['total_antrean']);
        $this->assertSame($ketuaSebelum['jumlah_disetujui'], $ketuaSesudah['jumlah_disetujui']);
        $this->assertEquals($bankSebelum - 1_500_000, $ketuaSesudah['saldo_bank']);
        $this->assertEquals($ketuaSesudah['saldo_bank'] - $ketuaSesudah['total_antrean'], $ketuaSesudah['sisa_proyeksi']);
    }
}
