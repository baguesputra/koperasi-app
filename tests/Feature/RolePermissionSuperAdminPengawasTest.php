<?php

namespace Tests\Feature;

use App\Models\ShuPeriode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class RolePermissionSuperAdminPengawasTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_super_admin_memiliki_akses_penuh_ke_seluruh_modul(): void
    {
        $superAdmin = User::where('no_karyawan', 'SPADM-000001')->firstOrFail();
        $this->assertTrue($superAdmin->hasRole('super_admin'));

        $this->actingAs($superAdmin);
        session(['auth.password_confirmed_at' => time()]);

        // Cek akses super admin ke dashboard, anggota, pinjaman, kas, pengaturan, dan SHU
        $this->get(route('dashboard'))->assertOk();
        $this->get(route('anggota.index'))->assertOk();
        $this->get(route('pinjaman.index'))->assertOk();
        $this->get(route('kas-koperasi.index'))->assertOk();
        $this->get(route('pengaturan.index'))->assertOk();
        $this->get(route('shu.index'))->assertOk();
    }

    public function test_admin_hanya_bisa_kerjaan_admin_dan_dibatasi_dari_approval_keuangan(): void
    {
        $admin = User::where('no_karyawan', 'ADM-000001')->firstOrFail();
        $this->assertTrue($admin->hasRole('admin'));

        $this->actingAs($admin);
        session(['auth.password_confirmed_at' => time()]);

        // Boleh akses administrasi
        $this->get(route('anggota.index'))->assertOk();
        $this->get(route('admin.pinjaman.index'))->assertOk();
        $this->get(route('pengaturan.index'))->assertOk();
        $this->get(route('shu.index'))->assertOk();

        // Dilarang akses approval tahap 2 Bendahara & tahap 3 Ketua
        $this->get(route('bendahara.pinjaman.index'))->assertForbidden();
        $this->get(route('ketua.pinjaman.index'))->assertForbidden();

        // Dilarang mutasi kas fisik / topup
        $this->post(route('kas-koperasi.topup'), ['jumlah' => 1000000, 'keterangan' => 'Topup test'])->assertForbidden();

        // Dilarang konfirmasi simpanan massal
        $this->post(route('bendahara.simpanan.konfirmasi'), ['anggota_ids' => []])->assertForbidden();
    }

    public function test_pengawas_hanya_bisa_view_dan_tarik_laporan_tanpa_bisa_mutasi(): void
    {
        $pengawas = User::where('no_karyawan', 'WAS-000001')->firstOrFail();
        $this->assertTrue($pengawas->hasRole('pengawas'));

        $this->actingAs($pengawas);

        // 1. Pengawas BISA melihat dashboard, data anggota, simpanan, pinjaman, kas, jurnal, laporan, dan SHU
        $this->get(route('dashboard'))->assertOk();
        $this->get(route('anggota.index'))->assertOk();
        $this->get(route('simpanan.index'))->assertOk();
        $this->get(route('pinjaman.index'))->assertOk();
        $this->get(route('kas-koperasi.index'))->assertOk();
        $this->get(route('jurnal-kas.index'))->assertOk();
        $this->get(route('laporan.index'))->assertOk();
        $this->get(route('shu.index'))->assertOk();

        $shu = ShuPeriode::create([
            'tahun_buku' => 2026,
            'total_shu' => 100_000_000,
            'pct_cadangan' => 0.25,
            'pct_jasa_simpanan' => 0.20,
            'pct_jasa_transaksi' => 0.25,
            'pct_pengurus' => 0.10,
            'pct_pengawas' => 0.05,
            'pct_pendidikan' => 0.05,
            'pct_sosial' => 0.05,
            'pct_wilker' => 0.05,
            'status' => 'draft',
        ]);

        // Pengawas BISA melihat detail SHU dan BISA download export Excel SHU
        $this->get(route('shu.show', $shu->id))->assertOk();
        $this->get(route('shu.export', $shu->id))->assertOk();

        // 2. Pengawas DILARANG melakukan aksi mutasi apapun
        // Dilarang tambah anggota
        $this->post(route('anggota.store'), [
            'nama' => 'Penyusup',
            'no_karyawan' => 'PEN-999',
            'email' => 'penyusup@test.com',
            'cabang' => 'Banjarmasin',
        ])->assertForbidden();

        // Dilarang topup kas
        $this->post(route('kas-koperasi.topup'), ['jumlah' => 500000])->assertForbidden();

        // Dilarang hitung atau finalisasi SHU
        $this->post(route('shu.hitung', $shu->id))->assertForbidden();
        $this->post(route('shu.finalisasi', $shu->id))->assertForbidden();

        // Dilarang masuk ke antrean approval pinjaman
        $this->get(route('admin.pinjaman.index'))->assertForbidden();
        $this->get(route('bendahara.pinjaman.index'))->assertForbidden();
        $this->get(route('ketua.pinjaman.index'))->assertForbidden();

        // Dilarang masuk menu pengaturan
        $this->get(route('pengaturan.index'))->assertForbidden();
    }

    public function test_role_super_admin_dan_pengawas_dilindungi_dari_penghapusan(): void
    {
        $superAdmin = User::where('no_karyawan', 'SPADM-000001')->firstOrFail();
        $this->actingAs($superAdmin);
        session(['auth.password_confirmed_at' => time()]);

        $roleSuperAdmin = Role::findByName('super_admin');
        $rolePengawas = Role::findByName('pengawas');

        // Mencoba hapus role super_admin dan pengawas harus ditolak dengan error session
        $this->delete(route('role.destroy', $roleSuperAdmin->id))
            ->assertSessionHasErrors(['role']);
        $this->assertDatabaseHas('roles', ['id' => $roleSuperAdmin->id]);

        $this->delete(route('role.destroy', $rolePengawas->id))
            ->assertSessionHasErrors(['role']);
        $this->assertDatabaseHas('roles', ['id' => $rolePengawas->id]);
    }
}
