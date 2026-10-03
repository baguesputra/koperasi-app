<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Departemen;
use App\Models\Divisi;
use App\Models\Jabatan;
use App\Models\Perusahaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class SinkronMasterTest extends TestCase
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

    private function fakeMaster(): void
    {
        Http::fake([
            '*/api/departments*' => Http::response(['success' => true, 'data' => [
                ['id' => 'dept-uuid-2', 'code' => 'IT', 'name' => 'Information Technology', 'company_id' => 'comp-uuid-1', 'leadership_title' => 'Head of IT'],
            ]], 200),
            '*/api/divisions*' => Http::response(['success' => true, 'data' => [
                ['id' => 'div-uuid-2', 'code' => 'DIV-IT', 'name' => 'IT', 'company_id' => 'comp-uuid-1', 'department_id' => 'dept-uuid-2'],
            ]], 200),
            '*/api/positions*' => Http::response(['success' => true, 'data' => [
                ['id' => 'pos-uuid-12', 'name' => 'Fullstack Developer', 'level' => 7, 'level_label' => 'Staff', 'department_id' => 'dept-uuid-2', 'division_id' => 'div-uuid-2'],
            ]], 200),
            '*/api/companies/*' => Http::response(['success' => true, 'data' => [
                'id' => 'comp-uuid-1', 'code' => 'DUTA', 'name' => 'Duta Mall Banjarmasin',
            ]], 200),
            '*/api/companies*' => Http::response(['success' => true, 'data' => [
                ['id' => 'comp-uuid-1', 'code' => 'DUTA', 'name' => 'Duta Mall Banjarmasin'],
            ]], 200),
            '*/api/users*' => Http::response(['success' => true, 'data' => [[
                'id' => 'uuid-karyawan-1',
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@dutamall.com',
                'whatsapp_number' => '081234567890',
                'nik' => '6371012304950001',
                'is_active' => true,
                'company_id' => 'comp-uuid-1',
                'department_id' => 'dept-uuid-2',
                'position_id' => 'pos-uuid-12',
            ]]], 200),
        ]);
    }

    public function test_sinkron_master_idempoten(): void
    {
        $this->fakeMaster();
        $this->masuk('ADM-000001');

        $this->post(route('pengaturan.sinkron-master-gate'))->assertRedirect();
        $this->post(route('pengaturan.sinkron-master-gate'))->assertRedirect();

        $this->assertSame(1, Perusahaan::count());
        $this->assertSame(1, Departemen::count());
        $this->assertSame(1, Divisi::count());
        $this->assertSame(1, Jabatan::count());
        $this->assertSame('Information Technology', Departemen::sole()->nama);
    }

    public function test_sinkron_karyawan_menautkan_fk_master(): void
    {
        $this->fakeMaster();
        $this->masuk('ADM-000001');

        $this->post(route('pengaturan.sinkron-master-gate'))->assertRedirect();
        $this->post(route('pengaturan.sinkron-gate', ['company_id' => 'comp-uuid-1']))->assertRedirect();

        $anggota = Anggota::where('no_karyawan', '6371012304950001')->sole();
        $this->assertSame('Fullstack Developer', $anggota->jabatan);
        $this->assertSame('Information Technology', $anggota->departemen->nama);
        $this->assertSame('Fullstack Developer', $anggota->jabatanMaster->nama);
        $this->assertSame('Duta Mall Banjarmasin', $anggota->perusahaan->nama);
        $this->assertSame('Information Technology', $anggota->department);
        $this->assertSame('IT', $anggota->getRelationValue('divisiMaster')->nama);
    }

    public function test_sinkron_menimpa_organisasi_dengan_data_gate(): void
    {
        $this->fakeMaster();
        $this->masuk('ADM-000001');

        $this->post(route('pengaturan.sinkron-master-gate'))->assertRedirect();
        $this->post(route('pengaturan.sinkron-gate', ['company_id' => 'comp-uuid-1']))->assertRedirect();

        $anggota = Anggota::where('no_karyawan', '6371012304950001')->sole();
        $divisiLain = Divisi::create(['gate_id' => 'manual-1', 'nama' => 'Divisi Manual']);
        $anggota->update(['divisi_id' => $divisiLain->id, 'cabang' => 'Samarinda']);

        $this->post(route('pengaturan.sinkron-gate', ['company_id' => 'comp-uuid-1']))->assertRedirect();

        $anggota->refresh();
        $this->assertNotSame($divisiLain->id, $anggota->divisi_id);
        $this->assertSame('Duta Mall Banjarmasin', $anggota->perusahaan->nama);
        $this->assertSame('Information Technology', $anggota->departemen->nama);
        $this->assertSame('Samarinda', $anggota->cabang);
    }

    public function test_sinkron_master_via_tree_hierarki(): void
    {
        Http::fake([
            '*/api/companies/*/tree' => Http::response(['success' => true, 'data' => [
                'departments' => [[
                    'id' => 'dept-uuid-9', 'code' => 'TOP-IT', 'name' => 'DM - IT',
                    'divisions' => [[
                        'id' => 'div-uuid-9', 'code' => 'DM-IT', 'name' => 'IT',
                        'positions' => [
                            ['id' => 'pos-uuid-90', 'name' => 'IT Supervisor'],
                        ],
                    ]],
                    'positions' => [
                        ['id' => 'pos-uuid-91', 'name' => 'IT Staff'],
                    ],
                ]],
                'direct_divisions' => [
                    ['id' => 'div-uuid-92', 'code' => 'DIV-CCTV', 'name' => 'CCTV'],
                ],
            ]], 200),
            '*/api/companies*' => Http::response(['success' => true, 'data' => [
                ['id' => 'comp-uuid-1', 'code' => 'DUTA', 'name' => 'Duta Mall Banjarmasin'],
            ]], 200),
        ]);
        $this->masuk('ADM-000001');

        $this->post(route('pengaturan.sinkron-master-gate'))->assertRedirect();

        $this->assertSame(1, Departemen::count());
        $this->assertSame(2, Divisi::count());
        $this->assertSame(2, Jabatan::count());
        $it = Divisi::where('gate_id', 'div-uuid-9')->sole();
        $this->assertSame('DM-IT', $it->kode);
        $this->assertSame(Departemen::sole()->id, $it->departemen_id);
        $this->assertSame($it->id, Jabatan::where('gate_id', 'pos-uuid-90')->sole()->division_id);
    }

    public function test_sinkron_master_fallback_flat_bila_bukan_tree(): void
    {
        // Respons tanpa kunci departments/direct_divisions → pakai jalur flat lama.
        Http::fake([
            '*/api/companies/*' => Http::response(['success' => true, 'data' => [
                'id' => 'comp-uuid-1', 'code' => 'DUTA', 'name' => 'Duta Mall Banjarmasin',
            ]], 200),
            '*/api/companies*' => Http::response(['success' => true, 'data' => [
                ['id' => 'comp-uuid-1', 'code' => 'DUTA', 'name' => 'Duta Mall Banjarmasin'],
            ]], 200),
            '*/api/departments*' => Http::response(['success' => true, 'data' => [
                ['id' => 'dept-uuid-2', 'code' => 'IT', 'name' => 'Information Technology', 'company_id' => 'comp-uuid-1'],
            ]], 200),
            '*/api/divisions*' => Http::response(['success' => true, 'data' => []], 200),
            '*/api/positions*' => Http::response(['success' => true, 'data' => []], 200),
        ]);
        $this->masuk('ADM-000001');

        $this->post(route('pengaturan.sinkron-master-gate'))->assertRedirect();

        $this->assertSame(1, Perusahaan::count());
        $this->assertSame(1, Departemen::count());
    }

    public function test_sinkron_master_butuh_permission(): void
    {
        $this->fakeMaster();
        $tanpaRole = User::factory()->create();
        $this->actingAs($tanpaRole);

        $this->post(route('pengaturan.sinkron-master-gate'))->assertForbidden();
    }

    public function test_master_tetap_tersimpan_bila_tahap_child_gagal(): void
    {
        Http::fake([
            '*/api/departments*' => Http::response(['message' => 'Server Error'], 500),
            '*/api/divisions*' => Http::response(['message' => 'Server Error'], 500),
            '*/api/positions*' => Http::response(['message' => 'Server Error'], 500),
            '*/api/companies*' => Http::response(['success' => true, 'data' => [
                ['id' => 'comp-uuid-1', 'code' => 'DUTA', 'name' => 'Duta Mall Banjarmasin'],
            ]], 200),
        ]);
        $this->masuk('ADM-000001');

        $this->post(route('pengaturan.sinkron-master-gate'))
            ->assertRedirect()
            ->assertSessionHas('status', fn ($pesan) => str_contains($pesan, 'Sinkron master GATE selesai'));

        $this->assertSame(1, Perusahaan::count());
        $this->assertSame(0, Departemen::count());
    }

    public function test_gagal_total_tampilkan_pesan_ramah(): void
    {
        Http::fake([
            '*/api/*' => Http::response(['message' => 'Server Error'], 500),
        ]);
        $this->masuk('ADM-000001');

        $this->post(route('pengaturan.sinkron-master-gate'))
            ->assertRedirect()
            ->assertSessionHas('status', fn ($pesan) => str_contains($pesan, 'Sinkron master GATE selesai')
                && str_contains($pesan, 'Server GATE bermasalah'));

        $this->assertSame(0, Perusahaan::count());
    }
}
