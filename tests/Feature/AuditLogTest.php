<?php

namespace Tests\Feature;

use App\Exports\AuditLogExport;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->masuk('ADM-000001');
        $this->withSession(['auth.password_confirmed_at' => time()]);
    }

    public function test_pencatatan_audit_log_berhasil(): void
    {
        AuditLog::catat(
            aksi: 'pinjaman_diajukan',
            keterangan: 'Pengajuan pinjaman baru',
            dataLama: null,
            dataBaru: ['nominal' => 2000000],
            userId: 1
        );

        $this->assertDatabaseHas('audit_log', [
            'aksi' => 'pinjaman_diajukan',
            'keterangan' => 'Pengajuan pinjaman baru',
            'user_id' => 1,
        ]);
    }

    public function test_tab_audit_memuat_props_baru(): void
    {
        $response = $this->get(route('pengaturan.index', ['tab' => 'audit']));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Pengaturan/Index')
                ->where('tabAktif', 'audit')
                ->has('auditLogs')
                ->has('statAudit')
                ->has('daftarUserAudit')
                ->has('daftarKategoriAudit')
                ->has('daftarAksiAudit')
                ->has('filterAudit'));
    }

    public function test_export_audit_excel_dapat_diunduh(): void
    {
        $response = $this->get(route('pengaturan.audit.export'));

        $response->assertOk();
        $response->assertHeader('content-disposition');
    }

    public function test_emergency_log_menyaring_kunci_sensitif(): void
    {
        Log::shouldReceive('emergency')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return str_contains($message, 'KEGAGALAN PENCATATAN AUDIT LOG')
                    && $context['data_lama']['pin'] === '[REDACTED]'
                    && $context['data_lama']['nested']['token'] === '[REDACTED]'
                    && $context['data_baru']['password'] === '[REDACTED]'
                    && $context['data_baru']['nominal'] === 500000;
            });

        Event::listen('eloquent.creating: '.AuditLog::class, function () {
            throw new RuntimeException('Simulasi gagal tulis DB');
        });

        try {
            $this->expectException(RuntimeException::class);

            AuditLog::catat(
                aksi: 'ubah_pengaturan',
                keterangan: 'Uji sanitasi payload darurat',
                dataLama: ['pin' => '123456', 'nested' => ['token' => 'rahasia']],
                dataBaru: ['password' => 'secret123', 'nominal' => 500000],
                userId: 1
            );
        } finally {
            Event::forget('eloquent.creating: '.AuditLog::class);
        }
    }
}

