<?php

namespace Tests\Feature;

use App\Models\Pinjaman;
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
        $this->assertTrue($admin->hasRole('sekretaris'));

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

        // Admin bisa kelola pengguna & organisasi, tapi dilarang ubah kebijakan, WA, dan role
        $this->get(route('pengaturan.pengguna.index'))->assertOk();
        $this->post(route('pengaturan.bunga.update'), ['persentase' => 2.0])->assertForbidden();
        $this->get(route('pengaturan.wa.data'))->assertForbidden();
        $this->get(route('role.index'))->assertForbidden();
    }

    public function test_ketua_koperasi_bisa_kelola_kebijakan_tapi_dilarang_akses_sistem_teknis(): void
    {
        $ketua = User::where('no_karyawan', 'KET-000001')->firstOrFail();
        $this->assertTrue($ketua->hasRole('ketua_koperasi'));

        $this->actingAs($ketua);
        session(['auth.password_confirmed_at' => time()]);

        // Ketua BISA lihat & update kebijakan
        $this->get(route('pengaturan.index', ['tab' => 'aturan-pinjaman']))->assertOk();
        $this->post(route('pengaturan.bunga.update'), ['persentase' => 2.0])->assertRedirect();
        $this->assertDatabaseHas('setting_bunga', ['persentase' => 2.0]);

        // Ketua BISA lihat audit log
        $this->get(route('pengaturan.index', ['tab' => 'audit']))->assertOk();

        // Ketua DILARANG akses WA, GATE, Kelola Pengguna, dan Kelola Role
        $this->get(route('pengaturan.wa.data'))->assertForbidden();
        $this->post(route('pengaturan.wa.logout'))->assertForbidden();
        $this->post(route('pengaturan.sinkron-gate'))->assertForbidden();
        $this->get(route('pengaturan.pengguna.index'))->assertForbidden();
        $this->get(route('role.index'))->assertForbidden();
    }

    public function test_pengawas_hanya_bisa_view_menu_yang_diizinkan_dan_export_laporan(): void
    {
        $pengawas = User::where('no_karyawan', 'WAS-000001')->firstOrFail();
        $this->assertTrue($pengawas->hasRole('pengawas'));

        $anggota = $this->buatAnggota();
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'nominal' => 1_000_000,
            'tenor_bulan' => 3,
            'persentase_bunga' => 1.0,
            'keperluan' => 'Tes akses pengawas',
            'snapshot_bank' => 'BCA',
            'snapshot_no_rekening' => '123456',
            'snapshot_atas_nama' => $anggota->nama,
            'status' => 'aktif',
            'tanggal_pengajuan' => now(),
            'tanggal_pencairan' => now(),
        ]);

        $this->actingAs($pengawas);

        // 1. Pengawas BISA melihat dashboard, data anggota, simpanan, tabungan, pinjaman, kas, laporan (+ export/PDF), dan penghapusan pinjaman
        $this->get(route('dashboard'))->assertOk();
        $this->get(route('anggota.index'))->assertOk();
        $this->get(route('simpanan.index'))->assertOk();
        $this->get(route('simpanan.show', $anggota->id))->assertOk();
        $this->get(route('tabungan.index'))->assertOk();
        $this->get(route('pinjaman.index'))->assertOk();
        $this->get(route('pinjaman.show', $pinjaman->id))->assertOk();
        $this->get(route('kas-koperasi.index'))->assertOk();
        $this->get(route('laporan.index'))->assertOk();
        $this->get(route('laporan.show', 'arus-kas'))->assertOk();
        $this->get(route('laporan.export', 'arus-kas'))->assertOk();
        $this->get(route('laporan.pdf', 'arus-kas'))->assertOk();
        $this->get(route('pengurus.penghapusan-pinjaman.index'))->assertOk();

        // Portal anggota boleh diakses jika Pengawas memiliki profil anggota aktif;
        // jika belum memiliki profil anggota, diarahkan aman ke dashboard tanpa error 500.
        if ($pengawas->anggota) {
            $this->get(route('portal.dashboard'))->assertOk();

            $pinjamanSendiri = Pinjaman::create([
                'anggota_id' => $pengawas->anggota->id,
                'pengaju_user_id' => $pengawas->id,
                'nominal' => 500_000,
                'tenor_bulan' => 2,
                'persentase_bunga' => 1.0,
                'keperluan' => 'Tes cetak bukti milik sendiri',
                'snapshot_bank' => 'BCA',
                'snapshot_no_rekening' => '123456',
                'snapshot_atas_nama' => $pengawas->name,
                'status' => 'aktif',
                'tanggal_pengajuan' => now(),
                'tanggal_pencairan' => now(),
            ]);

            // Pengawas BOLEH cetak bukti pinjamannya sendiri, namun DILARANG cetak bukti anggota lain
            $this->get(route('pinjaman.cetak-bukti', $pinjamanSendiri->id))->assertOk();
        } else {
            $this->get(route('portal.dashboard'))->assertRedirect(route('dashboard'));
            $this->postJson(route('portal.pinjaman.simulasi'), ['nominal' => 1_000_000, 'tenor_bulan' => 3])->assertForbidden();
        }

        // Pengawas non-anggota (tanpa relasi anggota) tetap diarahkan aman ke dashboard tanpa error 500
        $pengawasNonAnggota = User::factory()->create(['no_karyawan' => 'WAS-NON-001']);
        $pengawasNonAnggota->assignRole('pengawas');
        $this->actingAs($pengawasNonAnggota);
        $this->get(route('portal.dashboard'))->assertRedirect(route('dashboard'));
        $this->postJson(route('portal.pinjaman.simulasi'), ['nominal' => 1_000_000, 'tenor_bulan' => 3])->assertForbidden();
        $this->actingAs($pengawas);

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

        // 2. Pengawas DILARANG mengakses jurnal kas, SHU, cetak bukti pinjaman, dan pengaturan
        $this->get(route('jurnal-kas.index'))->assertForbidden();
        $this->get(route('shu.index'))->assertForbidden();
        $this->get(route('shu.show', $shu->id))->assertForbidden();
        $this->get(route('shu.export', $shu->id))->assertForbidden();
        $this->get(route('pinjaman.cetak-bukti', $pinjaman->id))->assertForbidden();

        // 3. Pengawas DILARANG melakukan aksi mutasi apapun
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

        // 4. Pengawas DILARANG mengakses seluruh halaman & aksi Pengaturan (termasuk audit & kebijakan)
        session(['auth.password_confirmed_at' => time()]);
        $this->get(route('pengaturan.index'))->assertForbidden();
        $this->get(route('pengaturan.index', ['tab' => 'audit']))->assertForbidden();
        $this->get(route('pengaturan.index', ['tab' => 'aturan-pinjaman']))->assertForbidden();
        $this->get(route('pengaturan.audit.export'))->assertForbidden();

        // Pengawas DILARANG melakukan mutasi kebijakan
        $this->post(route('pengaturan.bunga.update'), ['persentase' => 2.0])->assertForbidden();
        $this->post(route('pengaturan.limit.store'), [
            'label' => 'Tes Limit',
            'min_bulan' => 0,
            'limit_maksimal' => 5000000,
        ])->assertForbidden();

        // Pengawas DILARANG akses sistem (WA, GATE, Pengguna, Role)
        $this->get(route('pengaturan.wa.data'))->assertForbidden();
        $this->post(route('pengaturan.wa.logout'))->assertForbidden();
        $this->post(route('pengaturan.sinkron-gate'))->assertForbidden();
        $this->get(route('pengaturan.pengguna.index'))->assertForbidden();
        $this->get(route('role.index'))->assertForbidden();
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
