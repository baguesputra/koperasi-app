<?php

namespace Tests\Feature;

use App\Jobs\KirimWaJob;
use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\Pinjaman;
use App\Models\Simpanan;
use App\Models\User;
use App\Services\Anggota\ResignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class AnggotaResignReaktivasiTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function buatAnggotaDenganSaldo(): Anggota
    {
        $user = User::firstOrCreate(
            ['no_karyawan' => 'TOP-920001'],
            ['name' => 'Calon Resign', 'email' => 'resign@test.id', 'password' => bcrypt('x'), 'harus_ganti_password' => false]
        );
        $anggota = Anggota::create([
            'user_id' => $user->id, 'no_anggota' => 'ANG-RSG-0001', 'nama' => 'Calon Resign',
            'cabang' => 'Banjarmasin', 'unit_bisnis' => 'Ops', 'jabatan' => 'staff',
            'tanggal_mulai_kerja' => now()->subYears(3), 'tanggal_jadi_anggota' => now()->subYears(3),
            'status' => 'aktif',
        ]);

        // Saldo simpanan pokok+wajib 600rb (cukup, tanpa pinjaman aktif)
        foreach ([['pokok', 300_000], ['wajib', 300_000]] as [$jenis, $jumlah]) {
            Simpanan::create([
                'anggota_id' => $anggota->id, 'jenis' => $jenis, 'jumlah' => $jumlah,
                'bulan_periode' => now()->format('Y-m'), 'tanggal_input' => now(), 'input_by' => 1,
            ]);
        }

        return $anggota;
    }

    public function test_resign_membekukan_akun_mencatat_settlement_dan_jurnal_return(): void
    {
        Queue::fake();
        Storage::fake('local');

        $anggota = $this->buatAnggotaDenganSaldo();
        $this->masuk('ADM-000001');
        $bankSebelum = (float) KasKoperasi::first()->saldo_bank;

        $this->post(route('anggota.resign', $anggota), [
            'alasan_resign' => 'Pindah domisili ke luar kota.',
            'tanggal_resign' => now()->format('Y-m-d'),
            'rekening_bank' => 'Mandiri',
            'rekening_nomor' => '1400098765',
            'rekening_atas_nama' => 'Calon Resign',
        ])->assertRedirect(route('anggota.index'));

        $anggota->refresh();
        $this->assertSame('resign', $anggota->status);
        $this->assertSame('menunggu', $anggota->status_pencairan_resign);
        $this->assertSame('Mandiri', $anggota->resign_snapshot_bank);
        $this->assertNotNull($anggota->resigned_settlement_json);
        $this->assertEquals(600_000.0, (float) $anggota->resigned_settlement_json['total_dikembalikan']);

        // Tahap 1 tidak memotong kas Bank & tidak mengirim WA sebelum Bendahara mencairkan
        $this->assertEquals($bankSebelum, (float) KasKoperasi::first()->saldo_bank);
        Queue::assertNothingPushed();

        // Tahap 2: Bendahara mencairkan pengembalian resign via Pusat Persetujuan
        $this->masuk('BEN-000001');
        $this->from('/persetujuan?kategori=pencairan')
            ->post(route('bendahara.pencairan-resign.cair', $anggota), [
                'akun_kas' => 'bank',
                'tanggal_pencairan' => now()->format('Y-m-d'),
                'bukti_pencairan' => UploadedFile::fake()->image('bukti-transfer-resign.jpg'),
                'catatan' => 'Sudah ditransfer ke Mandiri.',
            ])->assertRedirect('/persetujuan?kategori=pencairan');

        $anggota->refresh();
        $this->assertSame('dicairkan', $anggota->status_pencairan_resign);
        $this->assertNotNull($anggota->bukti_pencairan_resign_path);

        // Jurnal: tanpa funding transit; return pokok & wajib keluar dari bank saat dicairkan.
        $this->assertFalse(JurnalKas::where('kategori', 'simpanan_resign_masuk')->where('referensi_id', $anggota->id)->exists());
        $this->assertTrue(
            JurnalKas::where('kategori', 'return_simpanan_pokok')->where('referensi_id', $anggota->id)->exists()
            || JurnalKas::where('kategori', 'return_simpanan_wajib')->where('referensi_id', $anggota->id)->exists()
        );
        $this->assertEquals($bankSebelum - 600_000, (float) KasKoperasi::first()->saldo_bank);

        // WA dikirim 1x saat pencairan Bendahara (bukti transfer + slip PDF)
        Queue::assertPushed(KirimWaJob::class, fn (KirimWaJob $job) => $this->propertiWa($job)['event'] === 'resign_dicairkan');
        Queue::assertPushed(KirimWaJob::class, fn (KirimWaJob $job) => $this->propertiWa($job)['event'] === 'resign_slip_pdf');

        // Akses bukti privat anti-IDOR: Bendahara boleh lihat, anggota lain ditolak 403
        $this->get(route('anggota.bukti-pencairan-resign', $anggota))->assertOk();
        $orangLain = $this->buatAnggota('TOP-920099');
        $this->actingAs($orangLain->user);
        $this->get(route('anggota.bukti-pencairan-resign', $anggota))->assertForbidden();
    }

    public function test_user_resign_diblokir_saat_login_berikutnya(): void
    {
        $anggota = $this->buatAnggotaDenganSaldo();
        $this->masuk('ADM-000001');
        $this->post(route('anggota.resign', $anggota), [
            'alasan_resign' => 'Berhenti menjadi anggota koperasi.',
            'tanggal_resign' => now()->format('Y-m-d'),
        ])->assertRedirect();

        // Middleware memblokir user resign
        $this->actingAs($anggota->user);
        $res = $this->get(route('dashboard'));
        $res->assertRedirect(route('login'));
    }

    public function test_reaktivasi_menghidupkan_kembali_anggota(): void
    {
        Queue::fake();
        Storage::fake('local');

        $anggota = $this->buatAnggotaDenganSaldo();
        $this->masuk('ADM-000001');
        $this->post(route('anggota.resign', $anggota), [
            'alasan_resign' => 'Resign untuk pengujian reaktivasi.',
            'tanggal_resign' => now()->format('Y-m-d'),
        ])->assertRedirect();

        // Sebelum pencairan Bendahara selesai, reaktivasi ditolak
        $this->post(route('anggota.aktifkan-kembali', $anggota), [
            'alasan_reaktivasi' => 'Karyawan kembali bekerja di perusahaan.',
        ])->assertSessionHasErrors('alasan_reaktivasi');
        $this->assertSame('resign', $anggota->refresh()->status);

        // Setelah Bendahara mencairkan pengembalian resign, reaktivasi diizinkan
        $this->masuk('BEN-000001');
        $this->post(route('bendahara.pencairan-resign.cair', $anggota), [
            'akun_kas' => 'bank',
            'tanggal_pencairan' => now()->format('Y-m-d'),
            'bukti_pencairan' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect();

        $this->masuk('ADM-000001');
        $this->post(route('anggota.aktifkan-kembali', $anggota), [
            'alasan_reaktivasi' => 'Karyawan kembali bekerja di perusahaan.',
        ])->assertRedirect(route('anggota.index'));

        $this->assertSame('aktif', $anggota->refresh()->status);
    }

    public function test_resign_shortfall_menjadi_menunggu_dan_final_saat_lunas(): void
    {
        $anggota = $this->buatAnggotaDenganSaldo();
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 2_000_000,
            'tenor_bulan' => 2,
            'persentase_bunga' => 0,
            'status' => 'aktif',
            'tanggal_pengajuan' => now()->format('Y-m-d'),
        ]);
        Angsuran::create([
            'pinjaman_id' => $pinjaman->id, 'cicilan_ke' => 1,
            'nominal_pokok' => 1_000_000, 'nominal_bunga' => 0, 'total_bayar' => 1_000_000,
            'tanggal_jatuh_tempo' => now()->format('Y-m-d'), 'status' => 'belum_bayar',
        ]);
        Angsuran::create([
            'pinjaman_id' => $pinjaman->id, 'cicilan_ke' => 2,
            'nominal_pokok' => 1_000_000, 'nominal_bunga' => 0, 'total_bayar' => 1_000_000,
            'tanggal_jatuh_tempo' => now()->format('Y-m-d'), 'status' => 'belum_bayar',
        ]);

        $this->masuk('ADM-000001');
        // Simpanan 600rb vs tagihan 2jt → shortfall 1,4jt menunggu
        $this->post(route('anggota.resign', $anggota), [
            'alasan_resign' => 'Resign dengan sisa tagihan cicilan.',
            'tanggal_resign' => now()->format('Y-m-d'),
        ])->assertRedirect(route('anggota.index'));

        $anggota->refresh();
        $this->assertSame('resign_menunggu', $anggota->status);
        $settlement = $anggota->resigned_settlement_json;
        $this->assertSame('menunggu_pelunasan_akhir', $settlement['mode']);
        $this->assertEquals(1_400_000, (float) $settlement['shortfall']);
        $this->assertSame('aktif', $anggota->user->refresh()->status);

        // Lunasi cicilan akhir via konfirmasi → final resign + user nonaktif
        // Konfirmasi angsuran = tugas bendahara (permission angsuran.konfirmasi).
        $this->masuk('BEN-000001');
        $cicilanAkhir = Angsuran::findOrFail($settlement['cicilan_akhir_id']);
        $this->post(route('bendahara.angsuran.konfirmasi'), [
            'angsuran_ids' => ['n-'.$cicilanAkhir->id],
        ])->assertRedirect();

        $this->assertSame('resign', $anggota->refresh()->status);
        $this->assertSame('nonaktif', $anggota->user->refresh()->status);
        $this->assertSame('selesai', $anggota->refresh()->resigned_settlement_json['mode']);
    }

    public function test_tautan_rincian_signed_kedaluwarsa(): void
    {
        $anggota = $this->buatAnggotaDenganSaldo();
        $this->masuk('ADM-000001');
        $this->post(route('anggota.resign', $anggota), [
            'alasan_resign' => 'Resign untuk pengujian tautan rincian.',
            'tanggal_resign' => now()->format('Y-m-d'),
        ])->assertRedirect();

        $url = app(ResignService::class)->tautanRincian($anggota->refresh());
        $this->get($url)->assertOk();

        // Signature rusak → 403
        $rusak = preg_replace('/signature=[^&]+/', 'signature=rusak', $url);
        $this->get($rusak)->assertForbidden();
    }

    public function test_tanggal_resign_boleh_bulan_depan(): void
    {
        $anggota = $this->buatAnggotaDenganSaldo();
        $this->masuk('ADM-000001');

        $this->post(route('anggota.resign', $anggota), [
            'alasan_resign' => 'Resign bulan depan sesuai kontrak kerja.',
            'tanggal_resign' => now()->addMonthNoOverflow()->format('Y-m-d'),
        ])->assertRedirect(route('anggota.index'));

        $this->assertSame('resign', $anggota->refresh()->status);
    }

    public function test_resign_tanpa_sisa_pengembalian_tidak_perlu_pencairan_dan_kirim_wa_1x(): void
    {
        Queue::fake();

        $anggota = $this->buatAnggotaDenganSaldo(); // Simpanan pokok+wajib = 600rb
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 600_000,
            'tenor_bulan' => 1,
            'persentase_bunga' => 0,
            'status' => 'aktif',
            'tanggal_pengajuan' => now()->format('Y-m-d'),
        ]);
        Angsuran::create([
            'pinjaman_id' => $pinjaman->id,
            'cicilan_ke' => 1,
            'nominal_pokok' => 600_000,
            'nominal_bunga' => 0,
            'total_bayar' => 600_000,
            'tanggal_jatuh_tempo' => now()->format('Y-m-d'),
            'status' => 'belum_bayar',
        ]);

        $this->masuk('ADM-000001');
        $this->post(route('anggota.resign', $anggota), [
            'alasan_resign' => 'Resign impas tanpa pengembalian kas.',
            'tanggal_resign' => now()->format('Y-m-d'),
        ])->assertRedirect(route('anggota.index'));

        $anggota->refresh();
        $this->assertSame('resign', $anggota->status);
        $this->assertSame('tidak_perlu', $anggota->status_pencairan_resign);

        Queue::assertPushed(KirimWaJob::class, 1);
        Queue::assertPushed(KirimWaJob::class, fn (KirimWaJob $job) => $this->propertiWa($job)['event'] === 'resign_selesai_tanpa_transfer');
    }
}
