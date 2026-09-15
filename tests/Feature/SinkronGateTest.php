<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Simpanan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class SinkronGateTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config()->set('services.gate.token', 'token-uji');
        $this->withSession(['auth.password_confirmed_at' => time()]);
    }

    private function karyawanGate(array $override = []): array
    {
        return array_merge([
            'id' => '9e1a2b3c-4d5e-6f7a-8b9c-0d1e2f3a4b5c',
            'name' => 'Budi Santoso',
            'email' => 'budi.santoso@dutamall.com',
            'whatsapp_number' => '081234567890',
            'nik' => '6371012304950001',
            'is_active' => true,
            'company_id' => 'comp-uuid-1',
            'department_id' => 'dept-uuid-2',
            'position_id' => 'pos-uuid-12',
        ], $override);
    }

    private function fakeGate(array $daftar): void
    {
        Http::fake([
            '*/api/users*' => Http::response(['success' => true, 'data' => $daftar], 200),
            '*/api/departments*' => Http::response(['success' => true, 'data' => []], 200),
            '*/api/divisions*' => Http::response(['success' => true, 'data' => []], 200),
            '*/api/positions*' => Http::response(['success' => true, 'data' => []], 200),
            '*/api/companies*' => Http::response(['success' => true, 'data' => []], 200),
        ]);
    }

    public function test_sinkron_membuat_user_anggota_simpanan_pokok(): void
    {
        $this->fakeGate([$this->karyawanGate()]);
        $this->masuk('ADM-000001');

        $this->post(route('pengaturan.sinkron-gate'))->assertRedirect();

        $anggota = Anggota::where('no_karyawan', '6371012304950001')->sole();
        $this->assertSame('9e1a2b3c-4d5e-6f7a-8b9c-0d1e2f3a4b5c', $anggota->gate_id);
        $this->assertSame('6371012304950001', $anggota->no_ktp);
        $this->assertNull($anggota->department);
        $this->assertTrue($anggota->user->hasRole('anggota'));
        $this->assertTrue($anggota->user->harus_ganti_password);
        $this->assertTrue(Simpanan::where('anggota_id', $anggota->id)->where('jenis', 'pokok')->exists());
    }

    public function test_sinkron_menyimpan_foto(): void
    {
        $foto = 'https://gate.appdutamall.com/storage/photos/avatar-budi.jpg';
        $this->fakeGate([$this->karyawanGate(['photo_url' => $foto])]);
        $this->masuk('ADM-000001');

        $this->post(route('pengaturan.sinkron-gate'))->assertRedirect();
        $this->assertSame($foto, Anggota::where('no_karyawan', '6371012304950001')->sole()->foto_url);
    }

    public function test_sinkron_menolak_foto_url_tidak_aman(): void
    {
        $this->fakeGate([$this->karyawanGate(['photo_url' => 'javascript:alert(1)'])]);
        $this->masuk('ADM-000001');

        $this->post(route('pengaturan.sinkron-gate'))->assertRedirect();
        $this->assertNull(Anggota::where('no_karyawan', '6371012304950001')->sole()->foto_url);
    }

    public function test_sinkron_idempoten_dan_pratinjau_tidak_menyimpan(): void
    {
        $this->fakeGate([$this->karyawanGate()]);
        $this->masuk('ADM-000001');

        $this->post(route('pengaturan.sinkron-gate'))->assertRedirect();
        $this->post(route('pengaturan.sinkron-gate'))->assertRedirect();

        $this->assertSame(1, Anggota::where('no_karyawan', '6371012304950001')->count());
        $this->assertSame(1, User::where('no_karyawan', '6371012304950001')->count());

        $this->artisan('gate:sinkron-anggota', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame(1, Anggota::where('no_karyawan', '6371012304950001')->count());
    }

    public function test_sinkron_mengambil_alih_email_akun_tanpa_gate(): void
    {
        $dummy = User::where('no_karyawan', 'TOP-100001')->firstOrFail();
        $emailGate = 'budi.santoso@dutamall.com';
        $dummy->update(['email' => $emailGate]);

        $this->fakeGate([$this->karyawanGate()]);
        $this->masuk('ADM-000001');

        $this->post(route('pengaturan.sinkron-gate'))->assertRedirect();

        $anggota = Anggota::where('no_karyawan', '6371012304950001')->sole();
        $this->assertSame($emailGate, $anggota->user->email);
        $this->assertNull($dummy->fresh()->email);
    }

    public function test_sinkron_menolak_email_milik_akun_gate_lain(): void
    {
        $lain = $this->buatAnggota('TOP-900099');
        $lain->update(['gate_id' => 'uuid-lain']);
        $lain->user->update(['email' => 'budi.santoso@dutamall.com']);

        $this->fakeGate([$this->karyawanGate()]);
        $this->masuk('ADM-000001');

        $this->post(route('pengaturan.sinkron-gate'))->assertRedirect();
        $this->assertFalse(Anggota::where('no_karyawan', '6371012304950001')->exists());
    }

    public function test_sinkron_menolak_nik_terdaftar_pada_gate_lain(): void
    {
        $ada = $this->buatAnggota('6371012304950001');
        $ada->update(['gate_id' => 'uuid-lama']);

        $this->fakeGate([$this->karyawanGate()]);
        $this->masuk('ADM-000001');

        $this->post(route('pengaturan.sinkron-gate'))->assertRedirect();
        $this->assertSame('uuid-lama', $ada->fresh()->gate_id);
    }

    public function test_sinkron_butuh_permission_pengaturan(): void
    {
        $this->fakeGate([$this->karyawanGate()]);
        $tanpaRole = User::factory()->create();
        $this->actingAs($tanpaRole);

        $this->post(route('pengaturan.sinkron-gate'))->assertForbidden();
    }
}
