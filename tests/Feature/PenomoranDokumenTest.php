<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Services\Anggota\ResignService;
use App\Services\Dokumen\PenomoranDokumenService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class PenomoranDokumenTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    public function test_nomor_urut_dan_reset_tiap_tahun(): void
    {
        $layanan = app(PenomoranDokumenService::class);

        $this->assertSame('001/KOP-PJM/IX/2026', $layanan->berikutnya(PenomoranDokumenService::JENIS_PINJAMAN, Carbon::parse('2026-09-11')));
        $this->assertSame('002/KOP-PJM/IX/2026', $layanan->berikutnya(PenomoranDokumenService::JENIS_PINJAMAN, Carbon::parse('2026-09-12')));
        $this->assertSame('001/KOP-PJM/I/2027', $layanan->berikutnya(PenomoranDokumenService::JENIS_PINJAMAN, Carbon::parse('2027-01-05')));
        $this->assertSame('001/KOP-RSGN/IX/2026', $layanan->berikutnya(PenomoranDokumenService::JENIS_RESIGN, Carbon::parse('2026-09-11')));
    }

    public function test_slip_resign_memakai_nomor_tersimpan_dan_tanpa_no_anggota(): void
    {
        $this->seed();
        $anggota = $this->buatAnggota('TOP-900001');
        $aktor = $this->masuk('ADM-000001');

        app(ResignService::class)->proses($anggota, 'Pindah kerja', now()->format('Y-m-d'), $aktor);

        $data = Anggota::find($anggota->id)->dataSlipResign();

        $this->assertMatchesRegularExpression('#^\d{3}/KOP-RSGN/[IVX]+/\d{4}$#', $data['doc_no']);
        $this->assertArrayNotHasKey('no_anggota', $data['anggota']);
        $this->assertArrayHasKey('terbilang_total', $data['settlement']);
        $this->assertArrayHasKey('pinjaman_rincian', $data['settlement']);
    }
}
