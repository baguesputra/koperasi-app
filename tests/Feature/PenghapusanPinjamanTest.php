<?php

namespace Tests\Feature;

use App\Laporan\LaporanRegistry;
use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\AuditLog;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\PengajuanPenghapusanPinjaman;
use App\Models\Pinjaman;
use App\Models\Simpanan;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PenghapusanPinjamanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
        ]);

        KasKoperasi::firstOrCreate([], [
            'saldo_pinjaman' => 0,
            'saldo_dana_sosial' => 0,
            'saldo_pengembalian_simpanan' => 0,
            'saldo_simpanan' => 0,
            'saldo_bank' => 10_000_000,
            'saldo_kas_kecil' => 0,
        ]);
    }

    private function loginSebagai(string $noKaryawan): User
    {
        $user = User::where('no_karyawan', $noKaryawan)->firstOrFail();
        $this->actingAs($user);

        return $user;
    }

    private function buatPinjamanMenunggak(string $suffix = '1'): array
    {
        $kode = str_pad($suffix, 3, '0', STR_PAD_LEFT);
        $userAnggota = User::create([
            'no_karyawan' => "TOP-900{$kode}",
            'name' => "Anggota Menghilang {$suffix}",
            'email' => "hilang{$suffix}@koperasi.test",
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);
        $userAnggota->assignRole('anggota');

        $anggota = Anggota::create([
            'user_id' => $userAnggota->id,
            'no_anggota' => "ANG-HLG-{$kode}",
            'no_karyawan' => "TOP-900{$kode}",
            'nama' => "Anggota Menghilang {$suffix}",
            'cabang' => 'Banjarmasin',
            'unit_bisnis' => 'Operasional',
            'jabatan' => 'Staff',
            'tanggal_mulai_kerja' => now()->subYears(2),
            'tanggal_jadi_anggota' => now()->subYears(2),
            'status' => 'aktif',
        ]);

        Simpanan::create([
            'anggota_id' => $anggota->id,
            'jenis' => 'pokok',
            'jumlah' => 100_000,
            'bulan_periode' => now()->subMonths(6)->format('Y-m'),
            'tanggal_input' => now()->subMonths(6),
            'input_by' => $userAnggota->id,
        ]);
        Simpanan::create([
            'anggota_id' => $anggota->id,
            'jenis' => 'wajib',
            'jumlah' => 200_000,
            'bulan_periode' => now()->subMonths(5)->format('Y-m'),
            'tanggal_input' => now()->subMonths(5),
            'input_by' => $userAnggota->id,
        ]);

        $hariCair = now()->subMonths(3)->toDateString();
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $userAnggota->id,
            'nomor_dokumen' => "{$kode}/KOP-PJM/VIII/2026",
            'nominal' => 1_200_000,
            'tenor_bulan' => 3,
            'persentase_bunga' => 1.0,
            'status' => 'aktif',
            'tanggal_pengajuan' => $hariCair,
            'tanggal_pencairan' => $hariCair,
        ]);

        // Cicilan 1 sudah lunas (pokok 400.000, bunga 12.000)
        Angsuran::create([
            'pinjaman_id' => $pinjaman->id,
            'cicilan_ke' => 1,
            'nominal_pokok' => 400_000,
            'nominal_bunga' => 12_000,
            'total_bayar' => 412_000,
            'status' => 'lunas',
            'tanggal_jatuh_tempo' => now()->subMonths(2)->toDateString(),
            'tanggal_konfirmasi_bayar' => now()->subMonths(2)->toDateString(),
        ]);

        // Cicilan 2 & 3 belum bayar (menunggak, sisa pokok = 800.000, sisa bunga = 12.000)
        Angsuran::create([
            'pinjaman_id' => $pinjaman->id,
            'cicilan_ke' => 2,
            'nominal_pokok' => 400_000,
            'nominal_bunga' => 8_000,
            'total_bayar' => 408_000,
            'status' => 'belum_bayar',
            'tanggal_jatuh_tempo' => now()->subMonth()->toDateString(),
        ]);
        Angsuran::create([
            'pinjaman_id' => $pinjaman->id,
            'cicilan_ke' => 3,
            'nominal_pokok' => 400_000,
            'nominal_bunga' => 4_000,
            'total_bayar' => 404_000,
            'status' => 'belum_bayar',
            'tanggal_jatuh_tempo' => now()->subDays(5)->toDateString(),
        ]);

        return [$anggota, $pinjaman];
    }

    public function test_tiga_user_pengawas_terbentuk_dengan_jenjang_3_ke_1(): void
    {
        $was1 = User::where('no_karyawan', 'WAS-000001')->firstOrFail();
        $was2 = User::where('no_karyawan', 'WAS-000002')->firstOrFail();
        $was3 = User::where('no_karyawan', 'WAS-000003')->firstOrFail();

        $this->assertSame(1, $was1->levelPengawas());
        $this->assertSame('Pengawas 1 (Ketua Pengawas)', $was1->labelJabatanPengawas());
        $this->assertTrue($was1->hasRole('pengawas'));

        $this->assertSame(2, $was2->levelPengawas());
        $this->assertSame('Pengawas 2', $was2->labelJabatanPengawas());
        $this->assertTrue($was2->hasRole('pengawas'));

        $this->assertSame(3, $was3->levelPengawas());
        $this->assertSame('Pengawas 3', $was3->labelJabatanPengawas());
        $this->assertTrue($was3->hasRole('pengawas'));
    }

    public function test_hanya_ketua_dan_super_admin_yang_bisa_mengajukan_penghapusan_pinjaman(): void
    {
        [, $pinjaman1] = $this->buatPinjamanMenunggak('1');
        [, $pinjaman2] = $this->buatPinjamanMenunggak('2');

        foreach (['ADM-000001', 'BEN-000001', 'WAS-000003'] as $peranLain) {
            $this->loginSebagai($peranLain);
            $this->post(route('pengurus.penghapusan-pinjaman.store'), [
                'pinjaman_id' => $pinjaman1->id,
                'alasan' => 'Peran selain Ketua dan Super Admin tidak boleh mengajukan.',
            ])->assertForbidden();
        }

        // Ketua Koperasi berhasil mengajukan -> langsung masuk antrean Pengawas 3
        $this->loginSebagai('KET-000001');
        $this->post(route('pengurus.penghapusan-pinjaman.store'), [
            'pinjaman_id' => $pinjaman1->id,
            'alasan' => 'Diajukan oleh Ketua Koperasi untuk verifikasi pengawas.',
        ])->assertRedirect(route('pengurus.penghapusan-pinjaman.index'));

        $pengajuan1 = PengajuanPenghapusanPinjaman::where('pinjaman_id', $pinjaman1->id)->sole();
        $this->assertSame('diajukan', $pengajuan1->status);

        // Bendahara & Ketua tidak memiliki antrean approval penghapusan
        foreach (['BEN-000001', 'KET-000001'] as $pengurus) {
            $this->loginSebagai($pengurus);
            $this->get(route('pengurus.penghapusan-pinjaman.index'))
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->where('notifications.menunggu_penghapusan_pinjaman', 0));
        }

        // Pengawas 3 melihat tepat 1 antrean menunggu
        $this->loginSebagai('WAS-000003');
        $this->get(route('pengurus.penghapusan-pinjaman.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('notifications.menunggu_penghapusan_pinjaman', 1));

        // Super Admin juga dapat mengajukan
        $this->loginSebagai('SPADM-000001');
        $this->post(route('pengurus.penghapusan-pinjaman.store'), [
            'pinjaman_id' => $pinjaman2->id,
            'alasan' => 'Diajukan oleh Super Admin untuk persetujuan pengawas.',
        ])->assertRedirect(route('pengurus.penghapusan-pinjaman.index'));

        $this->assertSame('diajukan', PengajuanPenghapusanPinjaman::where('pinjaman_id', $pinjaman2->id)->sole()->status);
    }

    public function test_alur_lengkap_penghapusan_pinjaman_ketua_hingga_pengawas_3_2_1_dan_neraca_seimbang(): void
    {
        [$anggota, $pinjaman] = $this->buatPinjamanMenunggak('3');

        // Setup jurnal kas agar Neraca bisa diuji keseimbangannya
        $bendahara = User::where('no_karyawan', 'BEN-000001')->firstOrFail();
        $hariIni = now()->toDateString();

        JurnalKas::create([
            'tipe' => 'masuk',
            'kategori' => 'topup_bulanan',
            'kantong' => 'bank',
            'jumlah' => 5_000_000,
            'saldo_setelah' => 5_000_000,
            'keterangan' => 'Kas awal',
            'tanggal' => now(),
            'created_by' => $bendahara->id,
        ]);

        config([
            'koperasi.modal_tetap' => 3_000_000,
            'koperasi.laba_ditahan_historis' => 2_488_000,
        ]);

        $neracaAwal = LaporanRegistry::ambil('neraca')['data'](request()->merge(['tanggal' => $hariIni]));
        $nilaiAwal = fn (string $pos) => collect($neracaAwal['rows'])->firstWhere(fn ($r) => $r[0] === $pos)[1];
        $this->assertEquals(800_000, $nilaiAwal('Piutang'));
        $this->assertSame('Rp 0 — Seimbang', collect($neracaAwal['ringkasan'])->firstWhere(fn ($r) => str_contains($r[0], 'Selisih'))[1]);

        $saldoBankAwal = (float) KasKoperasi::first()->saldo_bank;

        // 1. Ketua Koperasi mengajukan penghapusan pinjaman (langsung menunggu Pengawas 3)
        $this->loginSebagai('KET-000001');
        $this->post(route('pengurus.penghapusan-pinjaman.store'), [
            'pinjaman_id' => $pinjaman->id,
            'alasan' => 'Anggota menghilang sejak 2 bulan lalu dan tidak dapat dihubungi.',
        ])->assertRedirect(route('pengurus.penghapusan-pinjaman.index'));

        $pengajuan = PengajuanPenghapusanPinjaman::where('pinjaman_id', $pinjaman->id)->sole();
        $this->assertSame('diajukan', $pengajuan->status);
        $this->assertEquals(800_000, (float) $pengajuan->sisa_pokok);
        $this->assertEquals(12_000, (float) $pengajuan->sisa_bunga);
        $this->assertSame(2, $pengajuan->sisa_cicilan);

        // Guard: Pengawas 1 & Pengawas 2 tidak boleh mendahului Pengawas 3
        foreach (['WAS-000001', 'WAS-000002'] as $pengawasAtas) {
            $this->loginSebagai($pengawasAtas);
            $this->post(route('pengurus.penghapusan-pinjaman.approve', $pengajuan), [
                'catatan' => 'Coba lompat jenjang',
            ])->assertSessionHasErrors('catatan');
            $this->assertSame('diajukan', $pengajuan->fresh()->status);
        }

        // 2. Pengawas 3 menyetujui (status -> approved_pengawas_3)
        $this->loginSebagai('WAS-000003');
        $this->get(route('pengurus.penghapusan-pinjaman.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('notifications.menunggu_penghapusan_pinjaman', 1));

        $this->post(route('pengurus.penghapusan-pinjaman.approve', $pengajuan), [
            'catatan' => 'Telah diperiksa Pengawas 3, diteruskan ke Pengawas 2.',
        ])->assertRedirect(route('pengurus.penghapusan-pinjaman.index'));

        $this->assertSame('approved_pengawas_3', $pengajuan->fresh()->status);

        // 3. Pengawas 2 menyetujui (status -> approved_pengawas_2)
        $this->loginSebagai('WAS-000002');
        $this->post(route('pengurus.penghapusan-pinjaman.approve', $pengajuan), [
            'catatan' => 'Telah diperiksa Pengawas 2, diteruskan ke Ketua Pengawas (Pengawas 1).',
        ])->assertRedirect(route('pengurus.penghapusan-pinjaman.index'));

        $this->assertSame('approved_pengawas_2', $pengajuan->fresh()->status);

        // 4. Pengawas 1 (Ketua Pengawas) menyetujui FINAL (status -> disetujui)
        $this->loginSebagai('WAS-000001');
        $this->post(route('pengurus.penghapusan-pinjaman.approve', $pengajuan), [
            'catatan' => 'Disetujui final Ketua Pengawas untuk dihapusbukukan.',
        ])->assertRedirect(route('pengurus.penghapusan-pinjaman.index'));

        $pengajuan->refresh();
        $pinjaman->refresh();
        $anggota->refresh();

        $this->assertSame('disetujui', $pengajuan->status);
        $this->assertNotNull($pengajuan->nomor_dokumen);
        $this->assertStringContainsString('KOP-PHPS', $pengajuan->nomor_dokumen);
        $this->assertNotNull($pengajuan->disetujui_pada);

        $auditFinal = AuditLog::where('aksi', 'penghapusan_pinjaman_disetujui_final')->latest('id')->firstOrFail();
        $this->assertGreaterThan(255, mb_strlen($auditFinal->keterangan));

        // Pinjaman & sisa angsuran berubah menjadi dihapusbukukan
        $this->assertSame('dihapusbukukan', $pinjaman->status);
        $this->assertSame(0, Angsuran::where('pinjaman_id', $pinjaman->id)->where('status', 'belum_bayar')->count());
        $this->assertSame(2, Angsuran::where('pinjaman_id', $pinjaman->id)->where('status', 'dihapusbukukan')->count());

        // Anggota nonaktif, simpanan tetap utuh mengendap
        $this->assertSame('nonaktif', $anggota->status);
        $this->assertSame('nonaktif', $anggota->user->fresh()->status);
        $this->assertEquals(300_000, (float) $anggota->simpanan()->sum('jumlah'));

        // Saldo bank tidak terpotong 2 kali
        $this->assertEquals($saldoBankAwal, (float) KasKoperasi::first()->saldo_bank);

        // Laporan Neraca tetap seimbang dan Laba Rugi mencatat Beban Penghapusan Rp 800.000
        $neracaAkhir = LaporanRegistry::ambil('neraca')['data'](request()->merge(['tanggal' => $hariIni]));
        $nilaiAkhir = fn (string $pos) => collect($neracaAkhir['rows'])->firstWhere(fn ($r) => $r[0] === $pos)[1];

        $this->assertEquals(0, $nilaiAkhir('Piutang'));
        $this->assertEquals(100_000, $nilaiAkhir('Simpanan Pokok'));
        $this->assertEquals(200_000, $nilaiAkhir('Simpanan Wajib'));
        $this->assertEquals(-788_000, $nilaiAkhir('Selisih Hasil Usaha'));
        $this->assertSame('Rp 0 — Seimbang', collect($neracaAkhir['ringkasan'])->firstWhere(fn ($r) => str_contains($r[0], 'Selisih'))[1]);

        $labaRugi = LaporanRegistry::ambil('laba-rugi')['data'](request()->merge(['tahun' => now()->year]));
        $barisBebanHapus = collect($labaRugi['rows'])->firstWhere(fn ($r) => $r[0] === 'Beban Penghapusan Pinjaman');
        $this->assertNotNull($barisBebanHapus);
        $this->assertEquals(800_000, end($barisBebanHapus));
    }

    public function test_penolakan_terus_ditagih_oleh_pengawas_3_2_atau_1_menghentikan_pengajuan_dan_pinjaman_tetap_aktif(): void
    {
        // 1. Ditolak oleh Pengawas 3 dengan alasan 'Terus ditagih' -> langsung ditolak & angsuran bisa dikonfirmasi lagi
        [$anggotaA, $pinjamanA] = $this->buatPinjamanMenunggak('4');
        $this->loginSebagai('KET-000001');
        $this->post(route('pengurus.penghapusan-pinjaman.store'), [
            'pinjaman_id' => $pinjamanA->id,
            'alasan' => 'Usulan hapus pinjaman menunggak.',
        ]);
        $pengA = PengajuanPenghapusanPinjaman::where('pinjaman_id', $pinjamanA->id)->sole();

        $this->loginSebagai('WAS-000003');
        $this->post(route('pengurus.penghapusan-pinjaman.reject', $pengA), [
            'catatan' => 'Terus ditagih',
        ])->assertRedirect(route('pengurus.penghapusan-pinjaman.index'));

        $this->assertSame('ditolak', $pengA->fresh()->status);
        $this->assertSame('pengawas_3', $pengA->fresh()->tahap_ditolak);
        $this->assertSame('Terus ditagih', $pengA->fresh()->catatan_tolak);
        $this->assertSame('aktif', $pinjamanA->fresh()->status);
        $this->assertSame('aktif', $anggotaA->fresh()->status);

        // Pengawas 2 tidak dapat memproses pengajuan yang sudah ditolak Pengawas 3
        $this->loginSebagai('WAS-000002');
        $this->post(route('pengurus.penghapusan-pinjaman.approve', $pengA), [
            'catatan' => 'Coba lanjut',
        ])->assertSessionHasErrors('catatan');

        // Karena pengajuan ditolak, angsuran pinjaman kembali terbuka untuk dikonfirmasi Bendahara
        $cicilan2 = $pinjamanA->angsuran()->where('cicilan_ke', 2)->firstOrFail();
        $this->loginSebagai('BEN-000001');
        $this->post(route('bendahara.angsuran.konfirmasi'), [
            'angsuran_ids' => ['n-'.$cicilan2->id],
        ])->assertRedirect();
        $this->assertSame('lunas', $cicilan2->fresh()->status);

        // 2. Disetujui Pengawas 3 lalu ditolak Pengawas 2 dengan alasan 'Terus ditagih' -> langsung ditolak
        [, $pinjamanB] = $this->buatPinjamanMenunggak('5');
        $this->loginSebagai('KET-000001');
        $this->post(route('pengurus.penghapusan-pinjaman.store'), [
            'pinjaman_id' => $pinjamanB->id,
            'alasan' => 'Usulan hapus tahap P2.',
        ]);
        $pengB = PengajuanPenghapusanPinjaman::where('pinjaman_id', $pinjamanB->id)->sole();

        $this->loginSebagai('WAS-000003');
        $this->post(route('pengurus.penghapusan-pinjaman.approve', $pengB), ['catatan' => 'Lanjut ke P2']);

        $this->loginSebagai('WAS-000002');
        $this->post(route('pengurus.penghapusan-pinjaman.reject', $pengB), [
            'catatan' => 'Terus ditagih',
        ])->assertRedirect(route('pengurus.penghapusan-pinjaman.index'));

        $this->assertSame('ditolak', $pengB->fresh()->status);
        $this->assertSame('pengawas_2', $pengB->fresh()->tahap_ditolak);
        $this->assertSame('Terus ditagih', $pengB->fresh()->catatan_tolak);
        $this->assertSame('aktif', $pinjamanB->fresh()->status);

        // 3. Disetujui Pengawas 3 & Pengawas 2 lalu ditolak Pengawas 1 dengan alasan 'Terus ditagih' -> langsung ditolak
        [$anggotaC, $pinjamanC] = $this->buatPinjamanMenunggak('6');
        $this->loginSebagai('KET-000001');
        $this->post(route('pengurus.penghapusan-pinjaman.store'), [
            'pinjaman_id' => $pinjamanC->id,
            'alasan' => 'Usulan hapus tahap P1.',
        ]);
        $pengC = PengajuanPenghapusanPinjaman::where('pinjaman_id', $pinjamanC->id)->sole();

        $this->loginSebagai('WAS-000003');
        $this->post(route('pengurus.penghapusan-pinjaman.approve', $pengC), ['catatan' => 'Lanjut ke P2']);
        $this->loginSebagai('WAS-000002');
        $this->post(route('pengurus.penghapusan-pinjaman.approve', $pengC), ['catatan' => 'Lanjut ke P1']);

        $this->loginSebagai('WAS-000001');
        $this->post(route('pengurus.penghapusan-pinjaman.reject', $pengC), [
            'catatan' => 'Terus ditagih',
        ])->assertRedirect(route('pengurus.penghapusan-pinjaman.index'));

        $this->assertSame('ditolak', $pengC->fresh()->status);
        $this->assertSame('pengawas_1', $pengC->fresh()->tahap_ditolak);
        $this->assertSame('Terus ditagih', $pengC->fresh()->catatan_tolak);
        $this->assertSame('aktif', $pinjamanC->fresh()->status);
        $this->assertSame('aktif', $anggotaC->fresh()->status);
    }

    public function test_data_pinjaman_menunggak_filter_dan_penghapusan_pinjaman_milik_pengawas_tanpa_self_approve(): void
    {
        [, $pinjamanReguler] = $this->buatPinjamanMenunggak('7');

        // Sekretaris bisa melihat filter ?status=menunggak namun tidak bisa mengajukan
        $this->loginSebagai('ADM-000001');
        $this->get(route('pinjaman.index', ['status' => 'menunggak']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Pinjaman/Index')
                ->where('statistik.menunggak', 1)
                ->where('pinjaman.data.0.id', $pinjamanReguler->id)
                ->where('pinjaman.data.0.bisa_ajukan_penghapusan', false)
            );

        // Ketua Koperasi & Super Admin memiliki hak ajukan penghapusan di Data Pinjaman
        foreach (['KET-000001', 'SPADM-000001'] as $pengaju) {
            $this->loginSebagai($pengaju);
            $this->get(route('pinjaman.index', ['status' => 'menunggak']))
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->component('Pinjaman/Index')
                    ->where('pinjaman.data.0.bisa_ajukan_penghapusan', true)
                );
        }

        // Pinjaman menunggak milik Pengawas 3 (WAS-000003): tahap Pengawas 3 dilewati otomatis -> langsung ke Pengawas 2
        $was3 = User::where('no_karyawan', 'WAS-000003')->firstOrFail();
        $anggotaWas3 = Anggota::create([
            'user_id' => $was3->id,
            'no_anggota' => 'ANG-WAS3-001',
            'no_karyawan' => 'WAS-000003',
            'nama' => 'Pengawas 3 Macet',
            'cabang' => 'Banjarmasin',
            'unit_bisnis' => 'Pengawas',
            'jabatan' => 'staff',
            'tanggal_mulai_kerja' => now()->subYears(4),
            'tanggal_jadi_anggota' => now()->subYears(3),
            'status' => 'aktif',
        ]);
        $pinjamanWas3 = Pinjaman::create([
            'anggota_id' => $anggotaWas3->id,
            'pengaju_user_id' => $was3->id,
            'nomor_dokumen' => '098/KOP-PJM/VIII/2026',
            'nominal' => 500_000,
            'tenor_bulan' => 2,
            'persentase_bunga' => 1.0,
            'status' => 'aktif',
            'tanggal_pengajuan' => now()->subMonths(3),
            'tanggal_pencairan' => now()->subMonths(3),
        ]);
        $pinjamanWas3->angsuran()->create([
            'cicilan_ke' => 1,
            'nominal_pokok' => 500_000,
            'nominal_bunga' => 5_000,
            'total_bayar' => 505_000,
            'status' => 'belum_bayar',
            'tanggal_jatuh_tempo' => now()->subMonth(),
        ]);

        $this->loginSebagai('KET-000001');
        $this->post(route('pengurus.penghapusan-pinjaman.store'), [
            'pinjaman_id' => $pinjamanWas3->id,
            'alasan' => 'Pinjaman Pengawas 3 menunggak melewati jatuh tempo.',
        ])->assertRedirect(route('pengurus.penghapusan-pinjaman.index'));

        $pengWas3 = PengajuanPenghapusanPinjaman::where('pinjaman_id', $pinjamanWas3->id)->sole();
        $this->assertSame('approved_pengawas_3', $pengWas3->status);

        // Pinjaman menunggak milik Pengawas 1 (WAS-000001): tahap Pengawas 1 dilewati dan final pada Pengawas 2
        $was1 = User::where('no_karyawan', 'WAS-000001')->firstOrFail();
        $anggotaWas1 = Anggota::create([
            'user_id' => $was1->id,
            'no_anggota' => 'ANG-WAS1-001',
            'no_karyawan' => 'WAS-000001',
            'nama' => 'Pengawas 1 Macet',
            'cabang' => 'Banjarmasin',
            'unit_bisnis' => 'Pengawas',
            'jabatan' => 'hod',
            'tanggal_mulai_kerja' => now()->subYears(5),
            'tanggal_jadi_anggota' => now()->subYears(4),
            'status' => 'aktif',
        ]);
        $pinjamanWas1 = Pinjaman::create([
            'anggota_id' => $anggotaWas1->id,
            'pengaju_user_id' => $was1->id,
            'nomor_dokumen' => '099/KOP-PJM/VIII/2026',
            'nominal' => 600_000,
            'tenor_bulan' => 2,
            'persentase_bunga' => 1.0,
            'status' => 'aktif',
            'tanggal_pengajuan' => now()->subMonths(3),
            'tanggal_pencairan' => now()->subMonths(3),
        ]);
        $pinjamanWas1->angsuran()->create([
            'cicilan_ke' => 1,
            'nominal_pokok' => 600_000,
            'nominal_bunga' => 6_000,
            'total_bayar' => 606_000,
            'status' => 'belum_bayar',
            'tanggal_jatuh_tempo' => now()->subMonth(),
        ]);

        $this->loginSebagai('KET-000001');
        $this->post(route('pengurus.penghapusan-pinjaman.store'), [
            'pinjaman_id' => $pinjamanWas1->id,
            'alasan' => 'Pinjaman Pengawas 1 menunggak melewati jatuh tempo.',
        ])->assertRedirect(route('pengurus.penghapusan-pinjaman.index'));

        $pengWas1 = PengajuanPenghapusanPinjaman::where('pinjaman_id', $pinjamanWas1->id)->sole();
        $this->assertSame('diajukan', $pengWas1->status);

        $this->loginSebagai('WAS-000003');
        $this->post(route('pengurus.penghapusan-pinjaman.approve', $pengWas1), ['catatan' => 'Pengawas 3 setuju.']);

        // Pengawas 2 langsung memfinalisasi tanpa meminta approval Pengawas 1 (pemilik pinjaman)
        $this->loginSebagai('WAS-000002');
        $this->post(route('pengurus.penghapusan-pinjaman.approve', $pengWas1), ['catatan' => 'Pengawas 2 setuju & final.'])
            ->assertRedirect(route('pengurus.penghapusan-pinjaman.index'));

        $this->assertSame('disetujui', $pengWas1->fresh()->status);
        $this->assertSame('dihapusbukukan', $pinjamanWas1->fresh()->status);
        $this->assertNull($pengWas1->fresh()->approved_pengawas_1_by);
    }
}
