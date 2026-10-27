<?php

namespace Tests\Feature\Api;

use App\Models\Angsuran;
use App\Models\PengajuanLimit;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use App\Models\SettingSimpanan;
use App\Models\Simpanan;
use App\Models\User;
use App\Services\Pinjaman\EligibilitasPinjamanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class MobileDashboardTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        EligibilitasPinjamanService::resetCache();
    }

    protected function tearDown(): void
    {
        EligibilitasPinjamanService::resetCache();
        parent::tearDown();
    }

    private function headerToken(string $token): static
    {
        return $this->withHeader('Authorization', "Bearer {$token}");
    }

    private function buatPinjamanAktif($anggota, array $override = []): Pinjaman
    {
        $pinjaman = Pinjaman::create(array_merge([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 1_000_000,
            'tenor_bulan' => 2,
            'keperluan' => 'Uji dashboard',
            'persentase_bunga' => 1,
            'status' => 'aktif',
            'tanggal_pengajuan' => now()->format('Y-m-d'),
            'tanggal_pencairan' => now()->format('Y-m-d'),
        ], $override));

        foreach ([1, 2] as $ke) {
            Angsuran::create([
                'pinjaman_id' => $pinjaman->id,
                'cicilan_ke' => $ke,
                'nominal_pokok' => 250_000,
                'nominal_bunga' => 50_000,
                'total_bayar' => 300_000,
                'tanggal_jatuh_tempo' => now()->format('Y-m-d'),
                'status' => 'belum_bayar',
            ]);
        }

        return $pinjaman;
    }

    public function test_dashboard_struktur_lengkap(): void
    {
        $anggota = $this->buatAnggota('TOP-970001', ['tanggal_jadi_anggota' => now()->subYears(2)]);
        $bulanIni = now()->format('Y-m');
        Simpanan::create([
            'anggota_id' => $anggota->id, 'jenis' => 'pokok', 'jumlah' => 50_000,
            'bulan_periode' => $bulanIni, 'tanggal_input' => now()->format('Y-m-d'), 'input_by' => $anggota->user_id,
        ]);
        Simpanan::create([
            'anggota_id' => $anggota->id, 'jenis' => 'wajib', 'jumlah' => 30_000,
            'bulan_periode' => $bulanIni, 'tanggal_input' => now()->format('Y-m-d'), 'input_by' => $anggota->user_id,
        ]);
        $this->buatPinjamanAktif($anggota);
        $berjalan = Pinjaman::create([
            'anggota_id' => $anggota->id, 'pengaju_user_id' => $anggota->user_id,
            'nominal' => 500_000, 'tenor_bulan' => 2, 'keperluan' => 'Uji proses',
            'persentase_bunga' => 1, 'status' => 'diajukan',
            'tanggal_pengajuan' => now()->format('Y-m-d'),
        ]);

        $token = $anggota->user->createToken('uji')->plainTextToken;

        $res = $this->headerToken($token)->getJson('/api/v1/dashboard')->assertOk();

        $res->assertJsonStructure([
            'anggota' => ['id', 'no_anggota', 'no_karyawan', 'nama', 'jabatan', 'cabang', 'unit_bisnis', 'department', 'divisi', 'no_hp', 'email', 'status', 'foto_url', 'tanggal_masuk', 'limit_custom', 'resign_menunggu'],
            'ringkasan' => ['total_simpanan', 'simpanan_pokok', 'simpanan_wajib', 'limit_maksimal', 'limit_tersedia', 'boleh', 'alasan', 'sisa_angsuran', 'cicilan_pokok'],
            'pinjaman_aktif' => [['id', 'nominal', 'tenor_bulan', 'status', 'tanggal_pengajuan', 'tanggal_pencairan', 'sisa_angsuran', 'total_angsuran', 'sisa_total_bayar', 'angsuran_berikutnya', 'sudah_pakai_percepatan']],
            'pengajuan_berjalan' => ['id', 'tahapan'],
            'pengajuan_limit_berjalan',
            'pengajuan_percepatan_menunggu',
            'pengajuan_ditolak',
            'angsuran_berikutnya',
            'riwayat_gabungan',
            'tabel_tenor',
            'setting_simpanan',
        ]);

        $this->assertEquals(80000.0, $res->json('ringkasan.total_simpanan'));
        $res->assertJsonPath('pengajuan_berjalan.id', $berjalan->id);
        $this->assertCount(1, $res->json('pinjaman_aktif'));
        $this->assertCount(2, $res->json('riwayat_gabungan'));
        $this->assertLessThanOrEqual(5, count($res->json('riwayat_gabungan')));
        $this->assertNotEmpty($res->json('tabel_tenor'));
        $this->assertSame(
            SettingSimpanan::count(),
            count($res->json('setting_simpanan'))
        );
        $this->assertNotNull($res->json('angsuran_berikutnya'));
    }

    public function test_dashboard_404_bila_user_tanpa_anggota(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('uji')->plainTextToken;

        $this->headerToken($token)
            ->getJson('/api/v1/dashboard')
            ->assertNotFound()
            ->assertJsonPath('message', 'Anggota not found for user');
    }

    public function test_dashboard_butuh_auth(): void
    {
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();
        $this->getJson('/api/v1/pengurus/ringkasan')->assertUnauthorized();
    }

    public function test_ringkasan_counter_benar_dan_kas_null_tanpa_izin(): void
    {
        $plain = User::factory()->create();
        $plain->givePermissionTo('pinjaman.lihat');
        $token = $plain->createToken('uji')->plainTextToken;

        $sebelum = $this->headerToken($token)->getJson('/api/v1/pengurus/ringkasan')->assertOk()->json('antrean');

        $anggotaBerjalan = $this->buatAnggota('TOP-970011', ['tanggal_jadi_anggota' => now()->subYears(2)]);
        $aktif = $this->buatPinjamanAktif($anggotaBerjalan);

        Pinjaman::create([
            'anggota_id' => $anggotaBerjalan->id, 'pengaju_user_id' => $anggotaBerjalan->user_id,
            'nominal' => 500_000, 'tenor_bulan' => 2, 'keperluan' => 'Uji antrean',
            'persentase_bunga' => 1, 'status' => 'diajukan',
            'tanggal_pengajuan' => now()->format('Y-m-d'),
        ]);
        $this->buatAnggota('TOP-970012');
        PengajuanLimit::create([
            'anggota_id' => $anggotaBerjalan->id, 'limit_saat_ini' => 1_000_000, 'limit_diminta' => 2_000_000,
            'keterangan' => 'Uji antrean limit', 'status' => 'diajukan',
            'tanggal_pengajuan' => now()->format('Y-m-d'),
        ]);
        PengajuanPercepatan::create([
            'pinjaman_id' => $aktif->id, 'tipe' => 'perpanjang',
            'tenor_lama' => 2, 'tenor_baru' => 4, 'status' => 'diajukan',
            'keterangan' => 'Uji antrean percepatan', 'tanggal_pengajuan' => now()->format('Y-m-d'),
        ]);

        $res = $this->headerToken($token)->getJson('/api/v1/pengurus/ringkasan')->assertOk();

        $res->assertJsonPath('antrean.pinjaman_admin', $sebelum['pinjaman_admin'] + 1);
        $res->assertJsonPath('antrean.angsuran_belum_bayar', $sebelum['angsuran_belum_bayar'] + 2);
        $res->assertJsonPath('antrean.limit_bendahara', $sebelum['limit_bendahara'] + 1);
        $res->assertJsonPath('antrean.percepatan_bendahara', $sebelum['percepatan_bendahara'] + 1);
        $res->assertJsonPath('antrean.simpanan_belum_konfirmasi', $sebelum['simpanan_belum_konfirmasi'] + 2);
        $res->assertJsonPath('kas', null);
        $res->assertJsonStructure(['user' => ['id', 'name', 'no_karyawan', 'email', 'roles', 'permissions', 'is_anggota']]);
    }

    public function test_ringkasan_tanpa_izin_pengurus_ditolak(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('uji')->plainTextToken;

        $this->headerToken($token)
            ->getJson('/api/v1/pengurus/ringkasan')
            ->assertForbidden();
    }

    public function test_ringkasan_dengan_izin_kas_menampilkan_kas(): void
    {
        $anggota = $this->buatAnggota('TOP-970021');
        $bendahara = User::factory()->create(['no_karyawan' => 'BEN-970021']);
        $bendahara->givePermissionTo('pinjaman.lihat', 'kas.lihat');
        $token = $bendahara->createToken('uji')->plainTextToken;

        $res = $this->headerToken($token)->getJson('/api/v1/pengurus/ringkasan')->assertOk();

        $res->assertJsonPath('user.no_karyawan', 'BEN-970021');
        $res->assertJsonStructure(['kas' => ['saldo_bank', 'saldo_kas_kecil', 'kas_operasional', 'pinjaman_outstanding', 'simpanan_outstanding', 'pendapatan_bunga_bulan_ini']]);
        $this->assertSame($anggota->id, $anggota->fresh()->id);
    }
}
