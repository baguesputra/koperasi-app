<?php

namespace Tests\Feature;

use App\Models\Pinjaman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class PinjamanIndexTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_halaman_pinjaman_index_bisa_dibuka_tanpa_missing_attribute_exception(): void
    {
        $admin = $this->masuk('ADM-000001');
        $anggota = $this->buatAnggota(override: ['cabang' => 'Banjarmasin']);

        // Buat pinjaman dengan status aktif dan tanggal pencairan terisi
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'nominal' => 2_000_000,
            'nominal_diminta' => 2_000_000,
            'tenor_bulan' => 6,
            'tenor_diminta' => 6,
            'persentase_bunga' => 1.5,
            'keperluan' => 'Renovasi Rumah',
            'status' => 'aktif',
            'tanggal_pengajuan' => now()->subDays(10),
            'tanggal_pencairan' => now()->subDays(5),
            'snapshot_bank' => 'BCA',
            'snapshot_no_rekening' => '1234567890',
            'snapshot_atas_nama' => $anggota->nama,
            'versi_syarat' => 'v1.0',
            'ip_address_setuju' => '127.0.0.1',
            'user_agent_setuju' => 'PHPUnit',
        ]);

        $response = $this->actingAs($admin)->get(route('pinjaman.index'));
        $response->assertOk();

        $showResponse = $this->actingAs($admin)->get(route('pinjaman.show', $pinjaman));
        $showResponse->assertOk();
    }
}
