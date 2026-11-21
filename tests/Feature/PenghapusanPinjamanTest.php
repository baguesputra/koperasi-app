<?php

namespace Tests\Feature;

use App\Laporan\LaporanRegistry;
use App\Models\Anggota;
use App\Models\Angsuran;
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

    private function buatPinjamanMenunggak(): array
    {
        $userAnggota = User::create([
            'no_karyawan' => 'TOP-900001',
            'name' => 'Anggota Menghilang',
            'email' => 'hilang@koperasi.test',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);
        $userAnggota->assignRole('anggota');

        $anggota = Anggota::create([
            'user_id' => $userAnggota->id,
            'no_anggota' => 'ANG-HLG-001',
            'no_karyawan' => 'TOP-900001',
            'nama' => 'Anggota Menghilang',
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
            'nomor_dokumen' => '001/KOP-PJM/VIII/2026',
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

    public function test_alur_lengkap_penghapusan_pinjaman_berjenjang_sekretaris_hingga_pengawas_3_2_1_dan_neraca_seimbang(): void
    {
        [$anggota, $pinjaman] = $this->buatPinjamanMenunggak();

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

        // Sebelum penghapusan: Aktiva = Bank 5.000.000 + Piutang 800.000 = 5.800.000
        // Pasiva = Simpanan Pokok 100.000 + Wajib 200.000 + SHU 12.000 + Modal 5.488.000 = 5.800.000
        $neracaAwal = LaporanRegistry::ambil('neraca')['data'](request()->merge(['tanggal' => $hariIni]));
        $nilaiAwal = fn (string $pos) => collect($neracaAwal['rows'])->firstWhere(fn ($r) => $r[0] === $pos)[1];
        $this->assertEquals(800_000, $nilaiAwal('Piutang'));
        $this->assertSame('Rp 0 — Seimbang', collect($neracaAwal['ringkasan'])->firstWhere(fn ($r) => str_contains($r[0], 'Selisih'))[1]);

        $saldoBankAwal = (float) KasKoperasi::first()->saldo_bank;

        // 1. Sekretaris mengajukan penghapusan pinjaman
        $this->loginSebagai('ADM-000001');
        $this->post(route('pengurus.penghapusan-pinjaman.store'), [
            'pinjaman_id' => $pinjaman->id,
            'alasan' => 'Anggota menghilang sejak 2 bulan lalu dan tidak dapat dihubungi.',
        ])->assertRedirect(route('pengurus.penghapusan-pinjaman.index'));

        $pengajuan = PengajuanPenghapusanPinjaman::where('pinjaman_id', $pinjaman->id)->sole();
        $this->assertSame('diajukan', $pengajuan->status);
        $this->assertEquals(800_000, (float) $pengajuan->sisa_pokok);
        $this->assertEquals(12_000, (float) $pengajuan->sisa_bunga);
        $this->assertSame(2, $pengajuan->sisa_cicilan);

        // 2. Bendahara memverifikasi (status -> approved_bendahara)
        $this->loginSebagai('BEN-000001');
        $this->get(route('pengurus.penghapusan-pinjaman.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('notifications.menunggu_penghapusan_pinjaman', 1));

        $this->post(route('pengurus.penghapusan-pinjaman.approve', $pengajuan), [
            'catatan' => 'Sisa pokok Rp 800.000 sesuai buku pinjaman, diteruskan ke Ketua.',
        ])->assertRedirect(route('pengurus.penghapusan-pinjaman.index'));

        $this->assertSame('approved_bendahara', $pengajuan->fresh()->status);

        // 3. Ketua Koperasi menyetujui (status -> approved_ketua, masuk ke Pengawas 3)
        $this->loginSebagai('KET-000001');
        $this->post(route('pengurus.penghapusan-pinjaman.approve', $pengajuan), [
            'catatan' => 'Disetujui pengurus, mohon persetujuan berjenjang Badan Pengawas.',
        ])->assertRedirect(route('pengurus.penghapusan-pinjaman.index'));

        $this->assertSame('approved_ketua', $pengajuan->fresh()->status);

        // Guard: Pengawas 1 atau Pengawas 2 tidak boleh mendahului Pengawas 3
        $this->loginSebagai('WAS-000001');
        $this->post(route('pengurus.penghapusan-pinjaman.approve', $pengajuan), [
            'catatan' => 'Coba lompat jenjang',
        ])->assertSessionHasErrors('catatan');
        $this->assertSame('approved_ketua', $pengajuan->fresh()->status);

        // 4. Pengawas 3 menyetujui (status -> approved_pengawas_3)
        $this->loginSebagai('WAS-000003');
        $this->get(route('pengurus.penghapusan-pinjaman.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('notifications.menunggu_penghapusan_pinjaman', 1));

        $this->post(route('pengurus.penghapusan-pinjaman.approve', $pengajuan), [
            'catatan' => 'Telah diperiksa Pengawas 3, diteruskan ke Pengawas 2.',
        ])->assertRedirect(route('pengurus.penghapusan-pinjaman.index'));

        $this->assertSame('approved_pengawas_3', $pengajuan->fresh()->status);

        // 5. Pengawas 2 menyetujui (status -> approved_pengawas_2)
        $this->loginSebagai('WAS-000002');
        $this->post(route('pengurus.penghapusan-pinjaman.approve', $pengajuan), [
            'catatan' => 'Telah diperiksa Pengawas 2, diteruskan ke Ketua Pengawas (Pengawas 1).',
        ])->assertRedirect(route('pengurus.penghapusan-pinjaman.index'));

        $this->assertSame('approved_pengawas_2', $pengajuan->fresh()->status);

        // 6. Pengawas 1 (Ketua Pengawas) menyetujui FINAL (status -> disetujui)
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

        // Pinjaman & sisa angsuran berubah menjadi dihapusbukukan
        $this->assertSame('dihapusbukukan', $pinjaman->status);
        $this->assertSame(0, Angsuran::where('pinjaman_id', $pinjaman->id)->where('status', 'belum_bayar')->count());
        $this->assertSame(2, Angsuran::where('pinjaman_id', $pinjaman->id)->where('status', 'dihapusbukukan')->count());

        // Opsi 4A: Anggota & user jadi nonaktif, tetapi simpanan tetap utuh mengendap
        $this->assertSame('nonaktif', $anggota->status);
        $this->assertSame('nonaktif', $anggota->user->fresh()->status);
        $this->assertEquals(300_000, (float) $anggota->simpanan()->sum('jumlah'));

        // Opsi A: Saldo bank tidak terpotong 2 kali
        $this->assertEquals($saldoBankAwal, (float) KasKoperasi::first()->saldo_bank);

        // Verifikasi Laporan Neraca setelah penghapusan:
        // Piutang turun dari 800.000 menjadi 0; SHU turun dari 12.000 menjadi -788.000;
        // Simpanan Pokok (100.000) & Wajib (200.000) tetap utuh; Neraca 100% SEIMBANG!
        $neracaAkhir = LaporanRegistry::ambil('neraca')['data'](request()->merge(['tanggal' => $hariIni]));
        $nilaiAkhir = fn (string $pos) => collect($neracaAkhir['rows'])->firstWhere(fn ($r) => $r[0] === $pos)[1];

        $this->assertEquals(0, $nilaiAkhir('Piutang'));
        $this->assertEquals(100_000, $nilaiAkhir('Simpanan Pokok'));
        $this->assertEquals(200_000, $nilaiAkhir('Simpanan Wajib'));
        $this->assertEquals(-788_000, $nilaiAkhir('Selisih Hasil Usaha'));
        $this->assertSame('Rp 0 — Seimbang', collect($neracaAkhir['ringkasan'])->firstWhere(fn ($r) => str_contains($r[0], 'Selisih'))[1]);

        // Verifikasi Laporan Laba Rugi memuat Beban Penghapusan Pinjaman Rp 800.000
        $labaRugi = LaporanRegistry::ambil('laba-rugi')['data'](request()->merge(['tahun' => now()->year]));
        $barisBebanHapus = collect($labaRugi['rows'])->firstWhere(fn ($r) => $r[0] === 'Beban Penghapusan Pinjaman');
        $this->assertNotNull($barisBebanHapus);
        $this->assertEquals(800_000, end($barisBebanHapus));
    }

    public function test_pengajuan_oleh_bendahara_langsung_masuk_ke_ketua(): void
    {
        [, $pinjaman] = $this->buatPinjamanMenunggak();

        $this->loginSebagai('BEN-000001');
        $this->post(route('pengurus.penghapusan-pinjaman.store'), [
            'pinjaman_id' => $pinjaman->id,
            'alasan' => 'Diajukan langsung oleh Bendahara karena menunggak.',
        ])->assertRedirect(route('pengurus.penghapusan-pinjaman.index'));

        $pengajuan = PengajuanPenghapusanPinjaman::where('pinjaman_id', $pinjaman->id)->sole();
        $this->assertSame('approved_bendahara', $pengajuan->status);
        $this->assertNotNull($pengajuan->approved_bendahara_at);
    }

    public function test_penolakan_di_tengah_jenjang_menghentikan_proses_dan_pinjaman_tetap_aktif(): void
    {
        [, $pinjaman] = $this->buatPinjamanMenunggak();

        $this->loginSebagai('BEN-000001');
        $this->post(route('pengurus.penghapusan-pinjaman.store'), [
            'pinjaman_id' => $pinjaman->id,
            'alasan' => 'Anggota menunggak lama.',
        ]);

        $pengajuan = PengajuanPenghapusanPinjaman::where('pinjaman_id', $pinjaman->id)->sole();

        // Ketua setuju -> lanjut ke Pengawas 3
        $this->loginSebagai('KET-000001');
        $this->post(route('pengurus.penghapusan-pinjaman.approve', $pengajuan), [
            'catatan' => 'Setuju diteruskan ke pengawas.',
        ]);

        // Pengawas 3 menolak
        $this->loginSebagai('WAS-000003');
        $this->post(route('pengurus.penghapusan-pinjaman.reject', $pengajuan), [
            'catatan' => 'Masih bisa ditagih lewat keluarga, tunda penghapusan.',
        ])->assertRedirect(route('pengurus.penghapusan-pinjaman.index'));

        $this->assertSame('ditolak', $pengajuan->fresh()->status);
        $this->assertSame('pengawas_3', $pengajuan->fresh()->tahap_ditolak);
        $this->assertSame('aktif', $pinjaman->fresh()->status);
    }
}
