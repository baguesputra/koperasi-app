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

    public function test_update_hanya_ubah_field_non_gate(): void
    {
        $this->masuk('ADM-000001');
        $this->post(route('anggota.store'), $this->payload())->assertRedirect();
        $anggota = Anggota::whereHas('user', fn ($q) => $q->where('no_karyawan', 'TOP-930001'))->sole();

        $this->put(route('anggota.update', $anggota), [
            'tanggal_jadi_anggota' => now()->subMonth()->format('Y-m-d'),
            'status' => 'nonaktif',
            'limit_custom' => 8_000_000,
            'limit_custom_keterangan' => 'Kebijakan khusus uji',
        ])->assertRedirect();

        $anggota->refresh();
        $this->assertSame(now()->subMonth()->format('Y-m-d'), $anggota->tanggal_jadi_anggota->format('Y-m-d'));
        $this->assertSame('nonaktif', $anggota->status);
        $this->assertEquals(8_000_000, (float) $anggota->limit_custom);
    }

    public function test_update_menolak_field_gate(): void
    {
        $this->masuk('ADM-000001');
        $this->post(route('anggota.store'), $this->payload())->assertRedirect();
        $anggota = Anggota::whereHas('user', fn ($q) => $q->where('no_karyawan', 'TOP-930001'))->sole();

        $this->put(route('anggota.update', $anggota), [
            'nama' => 'Nama Baru Hasil Update',
            'cabang' => 'Samarinda',
            'unit_bisnis' => 'Keuangan',
            'no_hp' => '081234567890',
            'tanggal_mulai_kerja' => now()->subYear()->format('Y-m-d'),
            'tanggal_jadi_anggota' => now()->format('Y-m-d'),
            'status' => 'aktif',
        ])->assertRedirect();

        $anggota->refresh();
        $this->assertSame('Anggota Baru Uji', $anggota->nama);
        $this->assertSame('Banjarmasin', $anggota->cabang);
        $this->assertSame('Operasional', $anggota->unit_bisnis);
        $this->assertSame('aktif', $anggota->status);
    }

    public function test_index_butuh_permission(): void
    {
        $tanpaRole = User::factory()->create();
        $this->actingAs($tanpaRole);

        $this->get(route('anggota.index'))->assertForbidden();
    }

    public function test_index_merender_tanpa_error_saat_divisi_string_terisi(): void
    {
        $this->masuk('ADM-000001');
        $anggota = Anggota::firstOrFail();
        $anggota->update(['divisi' => 'Lapangan']);

        $this->get(route('anggota.index'))->assertOk();
    }

    public function test_template_export_bisa_diunduh(): void
    {
        $this->masuk('ADM-000001');

        $res = $this->get(route('anggota.template'));
        $res->assertOk();
    }
}
