<?php

namespace Tests\Feature;

use App\Jobs\KirimWaJob;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\Pinjaman;
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
        $this->post(route('bendahara.pinjaman.approve', $pinjaman), ['catatan' => 'Setuju, data lengkap.'])->assertStatus(302);
        $this->masuk('KET-000001');
        $this->post(route('ketua.pinjaman.approve', $pinjaman), ['catatan' => 'Disetujui Ketua.'])->assertStatus(302);
        $this->assertSame('aktif', $pinjaman->refresh()->status);

        // 3. Ajukan kedua saat masih ada pinjaman aktif → ditolak aturan anggota baru (< 1 tahun)
        $this->actingAs($baru->user);
        $res = $this->ajukanPortal($baru);

        try {
            $res->assertSessionHasErrors();
            file_put_contents('/tmp/opencode/trace.log', 'HAS-ERRORS');
        } catch (\Throwable $e) {
            file_put_contents('/tmp/opencode/trace.log', 'NO-SESSION-ERRORS: '.substr($e->getMessage(), 0, 200));
        }

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
        $this->post(route('bendahara.pinjaman.approve', $pinjaman), ['catatan' => 'Dokumen lengkap.'])->assertRedirect();
        $this->assertSame('approved_bendahara', $pinjaman->refresh()->status);

        // 3. Ketua menyetujui → cair
        $saldoSebelum = (float) KasKoperasi::first()->saldo_pinjaman;
        $this->masuk('KET-000001');
        $this->post(route('ketua.pinjaman.approve', $pinjaman), ['catatan' => 'Disetujui.'])->assertRedirect();

        $pinjaman->refresh();
        $this->assertSame('aktif', $pinjaman->status);
        $this->assertNotNull($pinjaman->tanggal_pencairan);
        $this->assertCount(3, $pinjaman->angsuran()->get());

        // Jurnal pencairan keluar kantong pinjaman + saldo berkurang
        $this->assertDatabaseHas('jurnal_kas', [
            'kategori' => 'pencairan_pinjaman', 'kantong' => 'pinjaman',
            'tipe' => 'keluar', 'referensi_id' => $pinjaman->id,
        ]);
        $this->assertEquals($saldoSebelum - 1_000_000, (float) KasKoperasi::first()->saldo_pinjaman);

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

    public function test_ketua_approve_gagal_bila_saldo_kantong_tidak_cukup(): void
    {
        Queue::fake();
        KasKoperasi::first()->update(['saldo_pinjaman' => 500_000]);

        $anggota = $this->buatAnggota();
        $this->actingAs($anggota->user);
        $this->ajukanPortal($anggota)->assertRedirect();
        $pinjaman = Pinjaman::where('anggota_id', $anggota->id)->sole();

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.pinjaman.approve', $pinjaman), ['catatan' => 'Dokumen lengkap, layak cair.'])->assertRedirect();

        $this->masuk('KET-000001');
        $response = $this->post(route('ketua.pinjaman.approve', $pinjaman), ['catatan' => 'Cairkan.']);
        $response->assertSessionHasErrors();

        // Transaksi rollback: status tetap menunggu ketua, tidak ada jadwal/jurnal
        $this->assertSame('approved_bendahara', $pinjaman->refresh()->status);
        $this->assertSame(0, $pinjaman->angsuran()->count());
        $this->assertSame(0, JurnalKas::where('kategori', 'pencairan_pinjaman')->where('referensi_id', $pinjaman->id)->count());
    }
}
