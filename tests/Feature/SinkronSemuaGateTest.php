<?php

namespace Tests\Feature;

use App\Jobs\SinkronAnggotaJit;
use App\Models\Anggota;
use App\Models\GateSyncLog;
use App\Models\GateSyncSetting;
use App\Services\Gate\GateClient;
use App\Services\Gate\SinkronisasiAnggotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class SinkronSemuaGateTest extends TestCase
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
            'nik' => '6371012304950001',
            'is_active' => true,
            'company_id' => 'comp-uuid-1',
        ], $override);
    }

    private function fakeGate(array $daftar, array $companies = []): void
    {
        Http::fake([
            '*/api/users*' => Http::response(['success' => true, 'data' => $daftar], 200),
            '*/api/departments*' => Http::response(['success' => true, 'data' => []], 200),
            '*/api/divisions*' => Http::response(['success' => true, 'data' => []], 200),
            '*/api/positions*' => Http::response(['success' => true, 'data' => []], 200),
            '*/api/companies*' => Http::response(['success' => true, 'data' => $companies], 200),
        ]);
    }

    public function test_sinkron_semua_dan_catat_riwayat(): void
    {
        $this->fakeGate([$this->karyawanGate()], [['id' => 'comp-uuid-1', 'code' => 'BM', 'name' => 'Big Mall Samarinda']]);
        $this->masuk('ADM-000001');

        $this->post(route('pengaturan.sinkron-semua-gate'))->assertRedirect();

        $this->assertTrue(Anggota::where('no_karyawan', '6371012304950001')->exists());
        $log = GateSyncLog::where('kind', 'semua')->where('source', 'manual')->firstOrFail();
        $this->assertSame(1, $log->count_baru);
    }

    public function test_sinkron_semua_pratinjau_tidak_menyimpan(): void
    {
        $this->fakeGate([$this->karyawanGate()]);
        $this->masuk('ADM-000001');

        $this->post(route('pengaturan.sinkron-semua-gate'), ['dry_run' => true])->assertRedirect();

        $this->assertFalse(Anggota::where('no_karyawan', '6371012304950001')->exists());
        $this->assertSame(0, GateSyncLog::count());
    }

    public function test_simpan_jadwal_validasi_dan_simpan(): void
    {
        $this->masuk('ADM-000001');

        $this->post(route('pengaturan.jadwal-gate'), [
            'schedule_enabled' => true,
            'karyawan_interval_minutes' => 30,
            'master_daily_at' => '03:15',
            'jit_enabled' => true,
            'grace_miss_count' => 3,
        ])->assertRedirect();

        $set = GateSyncSetting::current();
        $this->assertSame(30, (int) $set->karyawan_interval_minutes);
        $this->assertSame('03:15', $set->master_daily_at);

        $this->post(route('pengaturan.jadwal-gate'), [
            'karyawan_interval_minutes' => 1, 'master_daily_at' => 'xx', 'grace_miss_count' => 0,
        ])->assertSessionHasErrors(['karyawan_interval_minutes', 'master_daily_at', 'grace_miss_count']);
    }

    public function test_hilang_dua_kali_jadi_nonaktif(): void
    {
        GateSyncSetting::simpan(['grace_miss_count' => 2]);
        $this->fakeGate([$this->karyawanGate()]);
        $this->masuk('ADM-000001');
        $this->post(route('pengaturan.sinkron-semua-gate'))->assertRedirect();
        $this->assertSame('aktif', Anggota::where('no_karyawan', '6371012304950001')->firstOrFail()->status);

        $this->mock(GateClient::class, fn ($mock) => $mock->shouldReceive('ambilKaryawan')->andReturn([]));

        $this->post(route('pengaturan.sinkron-gate'))->assertRedirect();
        $anggota = Anggota::where('no_karyawan', '6371012304950001')->firstOrFail();
        $this->assertSame(1, (int) $anggota->gate_miss_count);
        $this->assertSame('aktif', $anggota->status);

        $this->post(route('pengaturan.sinkron-gate'))->assertRedirect();
        $anggota = Anggota::where('no_karyawan', '6371012304950001')->firstOrFail();
        $this->assertSame('nonaktif', $anggota->status);
        $this->assertSame('nonaktif', $anggota->user->status);
    }

    public function test_jit_job_enrich_tanpa_menimpa(): void
    {
        $this->fakeGate([$this->karyawanGate()]);
        $this->masuk('ADM-000001');
        $this->post(route('pengaturan.sinkron-semua-gate'))->assertRedirect();

        Queue::fake();
        $anggota = Anggota::where('no_karyawan', '6371012304950001')->firstOrFail();
        SinkronAnggotaJit::dispatch($anggota->user_id, 'budi.santoso@dutamall.com');
        Queue::assertPushed(SinkronAnggotaJit::class);

        Anggota::whereKey($anggota->id)->update(['gate_synced_at' => now()->subMinutes(30)]);
        $baris = $this->karyawanGate(['photo_url' => 'https://gate.appdutamall.com/storage/photos/budi.jpg']);
        $this->mock(GateClient::class, fn ($mock) => $mock->shouldReceive('cariKaryawanByEmail')->andReturn($baris));
        (new SinkronAnggotaJit($anggota->user_id, 'budi.santoso@dutamall.com'))
            ->handle(app(GateClient::class), app(SinkronisasiAnggotaService::class));

        $this->assertTrue(Anggota::whereKey($anggota->id)->firstOrFail()->gate_synced_at->gt(now()->subMinutes(5)));
    }

    public function test_jit_login_mencatat_riwayat_ok(): void
    {
        $anggota = $this->buatAnggota();
        $baris = $this->karyawanGate(['email' => $anggota->user->email, 'nik' => 'TOP-900001']);
        $this->mock(GateClient::class, fn ($mock) => $mock->shouldReceive('cariKaryawanByEmail')->andReturn($baris));

        $hasil = (new SinkronAnggotaJit($anggota->user_id, $anggota->user->email))
            ->handle(app(GateClient::class), app(SinkronisasiAnggotaService::class));

        $this->assertSame('ok', $hasil);
        $this->assertDatabaseHas('gate_sync_logs', [
            'kind' => 'karyawan', 'source' => 'login',
            'user_id' => $anggota->user_id, 'count_diperbarui' => 1, 'count_gagal' => 0,
        ]);
        $this->assertDatabaseHas('audit_log', [
            'aksi' => 'sinkron_gate_login', 'user_id' => $anggota->user_id,
        ]);
    }

    public function test_jit_login_mencatat_dilewati_saat_tak_ketemu(): void
    {
        $anggota = $this->buatAnggota();
        $this->mock(GateClient::class, fn ($mock) => $mock->shouldReceive('cariKaryawanByEmail')->andReturn(null));

        $hasil = (new SinkronAnggotaJit($anggota->user_id, $anggota->user->email))
            ->handle(app(GateClient::class), app(SinkronisasiAnggotaService::class));

        $this->assertSame('tak-ketemu', $hasil);
        $this->assertDatabaseHas('gate_sync_logs', [
            'source' => 'login', 'user_id' => $anggota->user_id, 'count_dilewati' => 1,
        ]);
    }

    public function test_jit_login_mencatat_gagal_saat_gate_error(): void
    {
        $anggota = $this->buatAnggota();
        $this->mock(GateClient::class, fn ($mock) => $mock->shouldReceive('cariKaryawanByEmail')->andThrow(new \RuntimeException('Gate down')));

        $hasil = (new SinkronAnggotaJit($anggota->user_id, $anggota->user->email))
            ->handle(app(GateClient::class), app(SinkronisasiAnggotaService::class));

        $this->assertSame('gagal-gate', $hasil);
        $this->assertDatabaseHas('gate_sync_logs', [
            'source' => 'login', 'user_id' => $anggota->user_id, 'count_gagal' => 1,
        ]);
        $this->assertDatabaseHas('audit_log', [
            'aksi' => 'sinkron_gate_login_gagal', 'user_id' => $anggota->user_id,
        ]);
    }

    public function test_jit_login_diam_saat_masih_segar(): void
    {
        $anggota = $this->buatAnggota();
        $anggota->update(['gate_synced_at' => now()]);

        $hasil = (new SinkronAnggotaJit($anggota->user_id, $anggota->user->email))
            ->handle(app(GateClient::class), app(SinkronisasiAnggotaService::class));

        $this->assertSame('segar', $hasil);
        $this->assertSame(0, GateSyncLog::where('source', 'login')->count());
    }

    public function test_resign_tidak_disentuh_dan_nonaktif_pulih_aktif(): void
    {
        GateSyncSetting::simpan(['grace_miss_count' => 2]);
        $this->fakeGate([$this->karyawanGate()]);
        $this->masuk('ADM-000001');
        $this->post(route('pengaturan.sinkron-semua-gate'))->assertRedirect();
        $anggota = Anggota::where('no_karyawan', '6371012304950001')->firstOrFail();
        $anggota->update(['status' => 'resign']);
        $anggota->user->update(['status' => 'nonaktif']);

        $this->post(route('pengaturan.sinkron-gate'))->assertRedirect();
        $this->assertSame('resign', $anggota->fresh()->status);

        $anggota->update(['status' => 'nonaktif']);
        $this->post(route('pengaturan.sinkron-gate'))->assertRedirect();
        $this->assertSame('aktif', $anggota->fresh()->status);
        $this->assertSame('aktif', $anggota->fresh()->user->status);
    }
}
