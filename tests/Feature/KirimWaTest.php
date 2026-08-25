<?php

namespace Tests\Feature;

use App\Jobs\KirimWaJob;
use App\Models\Anggota;
use App\Models\User;
use App\Models\WaLog;
use App\Services\Wa\WaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class KirimWaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.wa.url' => 'http://baileys-test:3000',
            'services.wa.token' => 'token-rahasia',
            'services.wa.timeout' => 5,
        ]);
    }

    public function test_normalisasi_nomor(): void
    {
        $this->assertSame('628123456789', KirimWaJob::normalisasi('0812-3456-789'));
        $this->assertSame('628123456789', KirimWaJob::normalisasi('628123456789'));
        $this->assertSame('628123456789', KirimWaJob::normalisasi('+62 812 3456 789'));
        $this->assertSame('628123456789', KirimWaJob::normalisasi('8123456789'));
        $this->assertNull(KirimWaJob::normalisasi(null));
        $this->assertNull(KirimWaJob::normalisasi('abc'));
        $this->assertNull(KirimWaJob::normalisasi('123'));
    }

    public function test_kirim_berhasil_mencatat_log(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);

        (new KirimWaJob('08123456789', null, 'tes_event', 'Halo tes'))->handle();

        $log = WaLog::sole();
        $this->assertSame('terkirim', $log->status);
        $this->assertSame('628123456789', $log->penerima);
        $this->assertSame('tes_event', $log->event);

        Http::assertSent(fn ($request) => $request['to'] === '628123456789'
            && $request->hasHeader('Authorization', 'Bearer token-rahasia'));
    }

    public function test_gagal_service_mati_mencatat_log_gagal(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        (new KirimWaJob('08123456789', null, 'tes_event', 'Halo tes'))->handle();

        $log = WaLog::sole();
        $this->assertSame('gagal', $log->status);
        $this->assertNotNull($log->error);
    }

    public function test_tanpa_nomor_dilewati(): void
    {
        Http::fake();

        (new KirimWaJob(null, null, 'tes_event', 'Halo tes'))->handle();

        Http::assertNothingSent();
        $this->assertSame('dilewati', WaLog::sole()->status);
    }

    public function test_keanggota_dispatch_job(): void
    {
        Queue::fake();
        $anggota = Anggota::create([
            'user_id' => User::factory()->create()->id,
            'no_anggota' => 'TST-WA-001',
            'nama' => 'Budi Wa',
            'cabang' => 'Banjarmasin',
            'unit_bisnis' => 'Operasional',
            'jabatan' => 'staff',
            'tanggal_mulai_kerja' => '2024-01-01',
            'tanggal_jadi_anggota' => '2024-01-01',
            'no_hp' => '08123456789',
            'status' => 'aktif',
        ]);

        WaService::keAnggota($anggota, 'tes_event', 'pesan');

        Queue::assertPushed(KirimWaJob::class);
    }
}
