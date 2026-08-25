<?php

namespace Tests\Feature;

use App\Models\Pinjaman;
use App\Models\Simpanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class PortalRiwayatTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_anggota_hanya_melihat_pinjaman_sendiri(): void
    {
        $a1 = $this->buatAnggota('TOP-940001');
        $a2 = $this->buatAnggota('TOP-940002');
        Pinjaman::create([
            'anggota_id' => $a1->id, 'pengaju_user_id' => $a1->user_id,
            'nominal' => 1_000_000, 'tenor_bulan' => 3, 'keperluan' => 'Uji',
            'persentase_bunga' => 1, 'status' => 'aktif',
            'tanggal_pengajuan' => now(), 'tanggal_pencairan' => now(),
        ]);
        Pinjaman::create([
            'anggota_id' => $a2->id, 'pengaju_user_id' => $a2->user_id,
            'nominal' => 2_000_000, 'tenor_bulan' => 3, 'keperluan' => 'Uji',
            'persentase_bunga' => 1, 'status' => 'aktif',
            'tanggal_pengajuan' => now(), 'tanggal_pencairan' => now(),
        ]);

        $this->actingAs($a1->user);
        $res = $this->get(route('portal.riwayat'));

        $res->assertOk();
        $pinjaman = collect($res->viewData('page')['props']['pinjaman']);
        $this->assertCount(1, $pinjaman);
        $this->assertEquals($a1->id, Pinjaman::find($pinjaman[0]['id'])->anggota_id);
    }

    public function test_anggota_hanya_melihat_simpanan_sendiri(): void
    {
        $a1 = $this->buatAnggota('TOP-940003');
        $a2 = $this->buatAnggota('TOP-940004');
        Simpanan::create([
            'anggota_id' => $a1->id, 'jenis' => 'wajib', 'jumlah' => 45_000,
            'bulan_periode' => now()->format('Y-m'), 'tanggal_input' => now(), 'input_by' => 1,
        ]);
        Simpanan::create([
            'anggota_id' => $a2->id, 'jenis' => 'wajib', 'jumlah' => 45_000,
            'bulan_periode' => now()->format('Y-m'), 'tanggal_input' => now(), 'input_by' => 1,
        ]);

        $this->actingAs($a1->user);
        $res = $this->get(route('portal.riwayat'));

        $res->assertOk();
        $props = $res->viewData('page')['props'];
        // Temukan kumpulan simpanan di props (nama key bisa bervariasi)
        // Riwayat hanya menampilkan simpanan milik anggota yang login (2 baris: wajib+? hanya 1 dibuat)
        $simpanan = collect($res->viewData('page')['props']['simpanan']);
        $this->assertCount(1, $simpanan);
        $this->assertSame('wajib', $simpanan[0]['jenis']);
    }
}
