<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\JurnalKas;
use App\Models\Simpanan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class SimpananKonfirmasiTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function buatAnggota(string $no): Anggota
    {
        $user = User::firstOrCreate(
            ['no_karyawan' => $no],
            ['name' => 'Uji '.$no, 'email' => strtolower($no).'@t.id', 'password' => bcrypt($no), 'harus_ganti_password' => false]
        );

        return Anggota::create([
            'user_id' => $user->id,
            'no_anggota' => 'ANG-SC-'.substr($no, -4),
            'nama' => 'Simpanan '.$no,
            'cabang' => 'Banjarmasin',
            'unit_bisnis' => 'Ops',
            'jabatan' => 'staff',
            'tanggal_mulai_kerja' => now()->subYears(2),
            'tanggal_jadi_anggota' => now()->subYears(2),
            'status' => 'aktif',
        ]);
    }

    public function test_konfirmasi_massal_membuat_simpanan_wajib_dana_sosial_dan_jurnal(): void
    {
        $a1 = $this->buatAnggota('TOP-910001');
        $a2 = $this->buatAnggota('TOP-910002');
        $bulan = now()->format('Y-m');

        $saldoSosial = (float) JurnalKas::where('kantong', 'dana_sosial')->sum('jumlah');
        $this->masuk('BEN-000001');
        $this->post(route('bendahara.simpanan.konfirmasi'), [
            'anggota_ids' => [$a1->id, $a2->id],
            'bulan_periode' => $bulan,
        ])->assertStatus(302);

        // 2 anggota × (wajib + dana sosial)
        $this->assertSame(4, Simpanan::whereIn('anggota_id', [$a1->id, $a2->id])->count());
        $this->assertDatabaseHas('simpanan', ['anggota_id' => $a1->id, 'jenis' => 'wajib', 'bulan_periode' => $bulan]);
        $this->assertDatabaseHas('simpanan', ['anggota_id' => $a1->id, 'jenis' => 'dana_sosial', 'bulan_periode' => $bulan]);

        // Jurnal dana sosial bertambah 2×5.000
        $this->assertEquals($saldoSosial + 10_000, (float) JurnalKas::where('kantong', 'dana_sosial')->sum('jumlah'));
    }

    public function test_anggota_yang_sudah_dikonfirmasi_dilewati_otomatis(): void
    {
        $a1 = $this->buatAnggota('TOP-910003');
        $a2 = $this->buatAnggota('TOP-910004');
        $bulan = now()->format('Y-m');

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.simpanan.konfirmasi'), [
            'anggota_ids' => [$a1->id], 'bulan_periode' => $bulan,
        ])->assertStatus(302);

        // Konfirmasi kedua mencakup a1 (sudah) + a2 (baru) → hanya a2 dibuat
        $this->post(route('bendahara.simpanan.konfirmasi'), [
            'anggota_ids' => [$a1->id, $a2->id], 'bulan_periode' => $bulan,
        ])->assertStatus(302);

        $this->assertSame(2, Simpanan::where('anggota_id', $a1->id)->count());
        $this->assertSame(2, Simpanan::where('anggota_id', $a2->id)->count());
    }
}
