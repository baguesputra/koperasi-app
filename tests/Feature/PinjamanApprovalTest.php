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

        // 2. Cair hingga aktif
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

        // 2. Bendahara menyetujui
        $this->masuk('BEN-000001');
        $this->post(route('bendahara.pinjaman.approve', $pinjaman), ['catatan' => 'Dokumen lengkap.', 'nominal' => 1_000_000])->assertRedirect();
        $this->assertSame('approved_bendahara', $pinjaman->refresh()->status);

        // 3. Ketua menyetujui → cair
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

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.pinjaman.reject', $p1), ['catatan' => 'Gaji belum cukup lama.'])->assertRedirect();
        $this->assertSame('ditolak', $p1->refresh()->status);

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

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.pinjaman.approve', $pinjaman), [
            'catatan' => 'Coba naikkan.',
            'nominal' => 9_000_000,
        ])->assertSessionHasErrors('keputusan');

        $this->assertSame('diajukan', $pinjaman->refresh()->status);
    }

    public function test_ketua_approve_cair_dari_bank_tanpa_talangan(): void
    {
        Queue::fake();
        KasKoperasi::first()->update(['saldo_bank' => 2_000_000]);

        $anggota = $this->buatAnggota();
        $this->actingAs($anggota->user);
        $this->ajukanPortal($anggota)->assertRedirect();
        $pinjaman = Pinjaman::where('anggota_id', $anggota->id)->sole();

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.pinjaman.approve', $pinjaman), ['catatan' => 'Dokumen lengkap, layak cair.', 'nominal' => 1_000_000])->assertRedirect();

        // Konsep Kas Tunggal: cukup saldo bank → cair langsung, tanpa talangan.
        $this->masuk('KET-000001');
        $this->post(route('ketua.pinjaman.approve', $pinjaman), ['catatan' => 'Cairkan.', 'nominal' => 1_000_000])->assertRedirect();

        $this->assertSame('aktif', $pinjaman->refresh()->status);
        $this->assertSame(1, JurnalKas::where('kategori', 'pencairan_pinjaman')->where('referensi_id', $pinjaman->id)->count());
        $this->assertSame(0, JurnalKas::whereIn('kategori', ['talangan_sosial_ke_pinjaman', 'talangan_simpanan_ke_pinjaman'])->count());
    }

    public function test_ringkasan_kas_agregat_di_halaman_approval(): void
    {
        $a1 = $this->buatAnggota('TOP-900020');
        $a2 = $this->buatAnggota('TOP-900021');

        $this->actingAs($a1->user);
        $this->ajukanPortal($a1, ['nominal' => 2_000_000, 'tenor_bulan' => 4])->assertStatus(302);
        $this->actingAs($a2->user);
        $this->ajukanPortal($a2, ['nominal' => 3_000_000, 'tenor_bulan' => 6])->assertStatus(302);

        $saldo = app(JurnalKasService::class)->saldoOperasional();

        $ukurBendahara = function () {
            $this->masuk('BEN-000001');
            $res = $this->get(route('bendahara.pinjaman.index'))->assertOk();

            return $res->original->getData()['page']['props']['ringkasanKas'];
        };

        $sebelum = $ukurBendahara();
        $this->assertGreaterThanOrEqual(5_000_000, $sebelum['total_menunggu']);
        $this->assertGreaterThanOrEqual(2, $sebelum['jumlah_menunggu']);
        $this->assertEquals($saldo, $sebelum['saldo']);
        $this->assertEquals($saldo - $sebelum['total_menunggu'], $sebelum['sisa_proyeksi']);

        $p1 = Pinjaman::where('anggota_id', $a1->id)->sole();
        $this->masuk('BEN-000001');
        $this->post(route('bendahara.pinjaman.approve', $p1), ['catatan' => 'Usulan disesuaikan.', 'nominal' => 1_500_000])
            ->assertRedirect();

        // p1 keluar dari antrean bendahara (berkurang 2jt), masuk antrean ketua (1,5jt)
        $sesudah = $ukurBendahara();
        $this->assertEquals($sebelum['total_menunggu'] - 2_000_000, $sesudah['total_menunggu']);
        $this->assertSame($sebelum['jumlah_menunggu'] - 1, $sesudah['jumlah_menunggu']);

        $ukurKetua = function () {
            $this->masuk('KET-000001');
            $res = $this->get(route('ketua.pinjaman.index'))->assertOk();

            return $res->original->getData()['page']['props']['ringkasanKas'];
        };

        $ketuaSebelum = $ukurKetua();
        $this->assertGreaterThanOrEqual(1_500_000, $ketuaSebelum['total_menunggu']);

        $this->masuk('KET-000001');
        $this->get(route('ketua.pinjaman.show', $p1))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('ringkasanKas'));

        $this->post(route('ketua.pinjaman.approve', $p1), ['catatan' => 'Cairkan final.', 'nominal' => 1_500_000])
            ->assertRedirect();

        $ketuaSesudah = $ukurKetua();
        $this->assertEquals($ketuaSebelum['total_menunggu'] - 1_500_000, $ketuaSesudah['total_menunggu']);
    }
}
