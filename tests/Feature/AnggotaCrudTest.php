<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Simpanan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class AnggotaCrudTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'nama' => 'Anggota Baru Uji',
            'no_karyawan' => 'TOP-930001',
            'email' => 'baru-930001@koperasi.test',
            'cabang' => 'Banjarmasin',
            'unit_bisnis' => 'Operasional',
            'jabatan' => 'staff',
            'department' => 'Produksi',
            'tanggal_mulai_kerja' => now()->subYear()->format('Y-m-d'),
            'tanggal_jadi_anggota' => now()->format('Y-m-d'),
        ], $override);
    }

    public function test_store_membuat_user_anggota_simpanan_pokok_dan_no_urut(): void
    {
        $this->masuk('ADM-000001');
        $this->post(route('anggota.store'), $this->payload())->assertRedirect();

        $anggota = Anggota::whereHas('user', fn ($q) => $q->where('no_karyawan', 'TOP-930001'))->sole();
        $this->assertMatchesRegularExpression('/^ANG-\d{4}-\d{4}$/', $anggota->no_anggota);
        $this->assertSame('aktif', $anggota->status);

        // Simpanan pokok otomatis saat registrasi (nominal seed: 50.000)
        $this->assertTrue(Simpanan::where('anggota_id', $anggota->id)->where('jenis', 'pokok')->exists());

        // Akun login dibuat & wajib ganti password
        $this->assertTrue((bool) $anggota->user->harus_ganti_password);
    }

    public function test_store_validasi_gagal_tanpa_cabang(): void
    {
        $this->masuk('ADM-000001');
        $data = $this->payload();
        unset($data['cabang']);

        $this->post(route('anggota.store'), $data)->assertSessionHasErrors('cabang');
    }

    public function test_update_mengubah_data_tanpa_mengubah_status(): void
    {
        $this->masuk('ADM-000001');
        $this->post(route('anggota.store'), $this->payload())->assertRedirect();
        $anggota = Anggota::whereHas('user', fn ($q) => $q->where('no_karyawan', 'TOP-930001'))->sole();

        $this->put(route('anggota.update', $anggota), [
            'nama' => 'Nama Baru Hasil Update',
            'cabang' => 'Samarinda',
            'unit_bisnis' => 'Keuangan',
            'jabatan' => 'hod',
            'department' => 'Finance',
            'no_hp' => '081234567890',
            'tanggal_mulai_kerja' => now()->subYear()->format('Y-m-d'),
            'tanggal_jadi_anggota' => now()->format('Y-m-d'),
            'status' => 'aktif',
        ])->assertRedirect();

        $anggota->refresh();
        $this->assertSame('Nama Baru Hasil Update', $anggota->nama);
        $this->assertSame('Samarinda', $anggota->cabang);
        $this->assertSame('aktif', $anggota->status); // update biasa tak menyentuh status
    }

    public function test_index_butuh_permission(): void
    {
        $tanpaRole = User::factory()->create();
        $this->actingAs($tanpaRole);

        $this->get(route('anggota.index'))->assertForbidden();
    }

    public function test_template_export_bisa_diunduh(): void
    {
        $this->masuk('ADM-000001');

        $res = $this->get(route('anggota.template'));
        $res->assertOk();
    }
}
