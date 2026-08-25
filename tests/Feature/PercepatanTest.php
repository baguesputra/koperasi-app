<?php

namespace Tests\Feature;

use App\Models\Angsuran;
use App\Models\Pinjaman;
use App\Services\Pinjaman\PercepatanPinjamanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class PercepatanTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function buatPinjamanAktif(int $tenor = 4): Pinjaman
    {
        $anggota = $this->buatAnggota();
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 2_500_000, 'tenor_bulan' => $tenor, 'keperluan' => 'Uji',
            'persentase_bunga' => 1, 'status' => 'aktif',
            'tanggal_pengajuan' => now()->subMonths(1), 'tanggal_pencairan' => now()->subMonths(1),
        ]);

        foreach (range(1, $tenor) as $ke) {
            $pinjaman->angsuran()->create([
                'cicilan_ke' => $ke, 'nominal_pokok' => 500_000, 'nominal_bunga' => 20_000,
                'total_bayar' => 520_000, 'status' => 'belum_bayar',
                'tanggal_jatuh_tempo' => now()->subMonths(1)->copy()->addMonths($ke - 1)->endOfMonth(),
            ]);
        }

        return $pinjaman;
    }

    public function test_validasi_pengajuan_ditolak_service(): void
    {
        // Pinjaman belum aktif → tolak
        $anggota = $this->buatAnggota();
        $nonAktif = Pinjaman::create([
            'anggota_id' => $anggota->id, 'pengaju_user_id' => $anggota->user_id,
            'nominal' => 500_000, 'tenor_bulan' => 2, 'persentase_bunga' => 1,
            'status' => 'diajukan', 'tanggal_pengajuan' => now(),
        ]);
        $service = app(PercepatanPinjamanService::class);

        $this->expectException(RuntimeException::class);
        $service->ajukan($nonAktif, 'perpanjang', 5, 'Butuh perpanjangan tenor.');
    }

    public function test_approve_perpanjang_membuat_jadwal_baru_dan_menandai_digantikan(): void
    {
        $pinjaman = $this->buatPinjamanAktif(4);
        $service = app(PercepatanPinjamanService::class);
        $pengajuan = $service->ajukan($pinjaman, 'perpanjang', 6, 'Berat bulan ini, mohon perpanjang.');

        $this->masuk('KET-000001');
        $this->post(route('ketua.percepatan.approve', $pengajuan), [
            'catatan' => 'Disetujui dengan pertimbangan tertentu.',
            'bulan_berlaku' => 'bulan_ini',
        ])->assertStatus(302);

        $pengajuan->refresh();
        $this->assertSame('aktif', $pengajuan->status);
        $this->assertTrue((bool) $pinjaman->refresh()->sudah_pakai_percepatan);

        // Semua angsuran lama digantikan; jadwal baru 6 cicilan dari sisa pokok
        $this->assertSame(0, $pinjaman->angsuran()->where('status', 'belum_bayar')->count());
        $this->assertSame(6, $pengajuan->angsuranBaru()->where('status', 'belum_bayar')->count());
        $this->assertEqualsWithDelta(2_000_000, (float) $pengajuan->angsuranBaru()->sum('nominal_pokok'), 0.01);
    }

    public function test_approve_lunas_total_menyisakan_satu_cicilan(): void
    {
        $pinjaman = $this->buatPinjamanAktif(4);
        $service = app(PercepatanPinjamanService::class);
        $pengajuan = $service->ajukan($pinjaman, 'lunas_total', null, 'Ada dana tambahan, mau lunas total.');

        $this->masuk('KET-000001');
        $this->post(route('ketua.percepatan.approve', $pengajuan), [
            'catatan' => 'Silakan lunas.',
            'bulan_berlaku' => 'bulan_ini',
        ])->assertStatus(302);

        $this->assertSame(1, $pengajuan->angsuranBaru()->count());
        $this->assertEquals((float) $pengajuan->fresh()->nominal_final, (float) $pengajuan->angsuranBaru()->first()->total_bayar);
        $this->assertGreaterThan(0, (float) $pengajuan->angsuranBaru()->first()->total_bayar);
    }

    public function test_reject_ketua_tidak_mengubah_angsuran(): void
    {
        $pinjaman = $this->buatPinjamanAktif(4);
        $service = app(PercepatanPinjamanService::class);
        $pengajuan = $service->ajukan($pinjaman, 'perpanjang', 6, 'Mohon perpanjang.');

        $this->masuk('KET-000001');
        $this->post(route('ketua.percepatan.reject', $pengajuan), ['catatan' => 'Belum bisa disetujui.'])->assertStatus(302);

        $this->assertSame('ditolak', $pengajuan->refresh()->status);
        $this->assertSame(4, $pinjaman->angsuran()->where('status', 'belum_bayar')->count());
    }

    public function test_hanya_satu_pengajuan_berjalan_per_pinjaman(): void
    {
        $this->expectException(RuntimeException::class);

        $pinjaman = $this->buatPinjamanAktif(4);
        $service = app(PercepatanPinjamanService::class);
        $service->ajukan($pinjaman, 'perpanjang', 6, 'Pengajuan pertama.');
        $service->ajukan($pinjaman, 'lunas_total', null, 'Pengajuan kedua harus gagal.');
    }
}
