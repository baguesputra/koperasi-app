<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Services\Anggota\ResignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class SlipResignPdfTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_slip_resign_terunduh_sebagai_pdf(): void
    {
        $anggota = $this->buatAnggota('TOP-900001');
        $aktor = $this->masuk('ADM-000001');

        app(ResignService::class)->proses($anggota, 'Pindah kerja', now()->format('Y-m-d'), $aktor);

        $res = $this->get(route('anggota.slip-resign', $anggota));

        $res->assertOk();
        $res->assertHeader('content-type', 'application/pdf');
        $res->assertHeader('content-disposition', 'attachment; filename=slip-resign-'.$anggota->no_anggota.'.pdf');
        $this->assertStringStartsWith('%PDF', $res->getContent());
    }

    public function test_slip_resign_ditolak_untuk_anggota_belum_resign(): void
    {
        $anggota = $this->buatAnggota('TOP-900002');
        $this->masuk('ADM-000001');

        $this->get(route('anggota.slip-resign', $anggota))->assertNotFound();
    }

    public function test_data_slip_memuat_snapshot_settlement(): void
    {
        $anggota = $this->buatAnggota('TOP-900003');
        $aktor = $this->masuk('ADM-000001');

        app(ResignService::class)->proses($anggota, 'Pindah kerja', now()->format('Y-m-d'), $aktor);

        $data = Anggota::find($anggota->id)->dataSlipResign();

        $this->assertSame($anggota->no_anggota, $data['anggota']['no_anggota']);
        $this->assertArrayHasKey('total_dikembalikan', $data['settlement']);
        $this->assertStringStartsWith('SLIP-RESIGN/', $data['doc_no']);
    }
}
