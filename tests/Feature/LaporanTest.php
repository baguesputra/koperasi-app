<?php

namespace Tests\Feature;

use App\Laporan\LaporanRegistry;
use App\Models\JurnalKas;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // ponytail: seed penuh rusak oleh CHECK constraint jurnal_kas lama (bug pre-existing),
        // cukup permission+role+user untuk halaman laporan
        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
        ]);
    }

    private function loginSebagai(string $noKaryawan): User
    {
        $user = User::where('no_karyawan', $noKaryawan)->firstOrFail();
        $this->actingAs($user);

        return $user;
    }

    public function test_tanpa_permission_ditolak(): void
    {
        $tanpaRole = User::factory()->create();
        $this->actingAs($tanpaRole);

        $this->get(route('laporan.index'))->assertForbidden();
    }

    public function test_index_tampil_untuk_bendahara(): void
    {
        $this->loginSebagai('BEN-000001');

        $this->get(route('laporan.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Laporan/Index')
                ->has('kelompok.Keuangan', 3));
    }

    public function test_show_arus_kas_menampilkan_mutasi(): void
    {
        $user = $this->loginSebagai('BEN-000001');

        JurnalKas::create([
            'tipe' => 'masuk', 'kategori' => 'topup_bulanan', 'kantong' => 'pinjaman',
            'jumlah' => 1_000_000, 'saldo_setelah' => 1_000_000,
            'keterangan' => 'Topup tes', 'tanggal' => now(), 'created_by' => $user->id,
        ]);

        $this->get(route('laporan.show', 'arus-kas'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Laporan/Show')
                ->where('hasil.rows.0.4', 1000000)
                ->where('periodeLabel', fn ($v) => str_contains($v, '2026')));
    }

    public function test_export_excel_dan_pdf_berjalan(): void
    {
        $this->loginSebagai('BEN-000001');

        $this->get(route('laporan.export', ['jenis' => 'neraca', 'tanggal' => now()->format('Y-m-d')]))
            ->assertOk();

        $this->get(route('laporan.pdf', ['jenis' => 'neraca', 'tanggal' => now()->format('Y-m-d')]))
            ->assertOk();
    }

    public function test_audit_log_tersembunyi_dari_non_admin(): void
    {
        // Bendahara: tidak lihat kartu audit di index, dan akses langsung ditolak
        $this->loginSebagai('BEN-000001');

        $this->get(route('laporan.index'))
            ->assertInertia(fn ($page) => $page->component('Laporan/Index')
                ->has('kelompok.Operasional', 2));

        $this->get(route('laporan.show', 'audit-log'))->assertForbidden();
    }

    public function test_audit_log_tampil_untuk_admin(): void
    {
        $this->loginSebagai('ADM-000001'); // admin (punya pengaturan.kelola)

        $this->get(route('laporan.show', 'audit-log'))->assertOk();
    }

    public function test_semua_laporan_dan_pdf_bisa_dirender(): void
    {
        $this->loginSebagai('ADM-000001');

        foreach (array_keys(LaporanRegistry::semua()) as $jenis) {
            $this->get(route('laporan.show', $jenis))->assertOk();
            $this->get(route('laporan.pdf', ['jenis' => $jenis, 'tanggal' => now()->format('Y-m-d')]))->assertOk();
            $this->get(route('laporan.export', ['jenis' => $jenis, 'dari' => now()->format('Y-m'), 'sampai' => now()->format('Y-m')]))->assertOk();
        }
    }
}
