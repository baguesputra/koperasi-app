<?php

namespace Tests\Feature;

use App\Exports\AuditLogExport;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}

