<?php

namespace Tests\Feature;

use App\Models\Angsuran;
use App\Models\Pinjaman;
use App\Services\Pinjaman\PerhitunganBungaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class CetakBuktiTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function buatPinjaman(string $status = 'aktif'): Pinjaman
    {
        $anggota = $this->buatAnggota();
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 1_000_000,
            'tenor_bulan' => 3,
            'keperluan' => 'Uji cetak bukti',
            'persentase_bunga' => 1,
            'status' => $status,
            'tanggal_pengajuan' => now(),
            'tanggal_pencairan' => $status === 'aktif' ? now() : null,
        ]);

        if ($status === 'aktif') {
            app(PerhitunganBungaService::class)->simpanJadwal($pinjaman);
        }

        return $pinjaman;
    }

    public function test_cetak_bukti_pinjaman_aktif_menampilkan_data_lengkap(): void
    {
        $pinjaman = $this->buatPinjaman('aktif');
        $this->masuk('BEN-000001');

        $res = $this->get(route('pinjaman.cetak-bukti', $pinjaman));

        $res->assertOk()->assertInertia(fn ($page) => $page
            ->component('Pinjaman/CetakBukti')
            ->where('pinjaman.tenor_bulan', 3)
            ->where('pinjaman.rekening.bank', fn ($v) => is_string($v) || $v === null)
            ->has('angsuran', 3)
            ->has('totals.pokok')
            ->has('totals.bunga')
            ->has('totals.angsuran'));
    }

    public function test_cetak_bukti_ditolak_untuk_pinjaman_belum_aktif(): void
    {
        $pinjaman = $this->buatPinjaman('diajukan');
        $this->masuk('BEN-000001');

        $this->get(route('pinjaman.cetak-bukti', $pinjaman))->assertForbidden();
    }

    public function test_angsuran_terbentuk_dengan_total_pokok_sama_nominal(): void
    {
        // Regresi pembulatan: Σpokok jadwal harus == nominal persis
        $pinjaman = $this->buatPinjaman('aktif');

        $totalPokok = (float) Angsuran::where('pinjaman_id', $pinjaman->id)->sum('nominal_pokok');
        $this->assertEqualsWithDelta(1_000_000, $totalPokok, 0.001);
    }
}
