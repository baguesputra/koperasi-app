<?php

namespace Tests\Feature\Api;

use App\Models\Angsuran;
use App\Models\AngsuranPercepatan;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use App\Models\Simpanan;
use App\Models\User;
use App\Services\Pinjaman\EligibilitasPinjamanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class AnggotaApiTest extends TestCase
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

    private function tokenUntuk(string $noKaryawan): string
    {
        $anggota = $this->buatAnggota($noKaryawan);

        return $anggota->user->createToken('uji')->plainTextToken;
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
            'keperluan' => 'Uji',
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

    public function test_tamu_ditolak_di_semua_endpoint(): void
    {
        $this->getJson('/api/v1/anggota/pinjaman-aktif')->assertUnauthorized();
        $this->getJson('/api/v1/anggota/riwayat-pinjaman')->assertUnauthorized();
        $this->getJson('/api/v1/anggota/limit')->assertUnauthorized();
        $this->getJson('/api/v1/anggota/simpanan')->assertUnauthorized();
    }

    public function test_user_tanpa_anggota_mendapat_404(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('uji')->plainTextToken;

        $this->headerToken($token)
            ->getJson('/api/v1/anggota/limit')
            ->assertNotFound()
            ->assertJsonPath('message', 'Anggota not found for user');
    }

    public function test_login_logout_dengan_token(): void
    {
        $anggota = $this->buatAnggota('TOP-950001');

        $login = $this->postJson('/api/v1/login', [
            'no_karyawan' => $anggota->user->no_karyawan,
            'password' => 'TOP-950001',
        ])->assertOk();

        $token = $login->json('token');
        $this->assertNotEmpty($token);

        $this->headerToken($token)->getJson('/api/v1/user')->assertOk();

        $this->headerToken($token)->postJson('/api/v1/logout')->assertOk();

        // Guard di-resolve ulang (di test, instance guard dipakai ulang antar request).
        auth()->forgetGuards();

        // Token sudah direvoke
        $this->headerToken($token)->getJson('/api/v1/user')->assertUnauthorized();
    }

    public function test_login_diblokir_saat_mode_sso(): void
    {
        config(['auth.mode' => 'sso']);
        $anggota = $this->buatAnggota('TOP-950002');

        $this->postJson('/api/v1/login', [
            'no_karyawan' => $anggota->user->no_karyawan,
            'password' => 'TOP-950002',
        ])->assertForbidden();
    }

    public function test_pinjaman_aktif_hanya_milik_sendiri(): void
    {
        $a1 = $this->buatAnggota('TOP-950011');
        $a2 = $this->buatAnggota('TOP-950012');
        $p1 = $this->buatPinjamanAktif($a1);
        $this->buatPinjamanAktif($a2, ['nominal' => 2_000_000]);

        $res = $this->headerToken($a1->user->createToken('uji')->plainTextToken)
            ->getJson('/api/v1/anggota/pinjaman-aktif')
            ->assertOk();

        $res->assertJsonPath('count', 1);
        $res->assertJsonPath('data.0.id', $p1->id);
        $res->assertJsonPath('data.0.sisa_angsuran', 2);
        $res->assertJsonPath('data.0.total_angsuran', 2);
        $this->assertEquals(600000.0, $res->json('data.0.sisa_total_bayar'));
        $res->assertJsonPath('data.0.angsuran_berikutnya.cicilan_ke', 1);
    }

    public function test_riwayat_pinjaman_dengan_filter_dan_paginasi(): void
    {
        $anggota = $this->buatAnggota('TOP-950021');
        $this->buatPinjamanAktif($anggota);
        Pinjaman::create([
            'anggota_id' => $anggota->id, 'pengaju_user_id' => $anggota->user_id,
            'nominal' => 500_000, 'tenor_bulan' => 1, 'keperluan' => 'Uji',
            'persentase_bunga' => 1, 'status' => 'lunas',
            'tanggal_pengajuan' => now()->format('Y-m-d'),
        ]);

        $token = $anggota->user->createToken('uji')->plainTextToken;

        $this->headerToken($token)
            ->getJson('/api/v1/anggota/riwayat-pinjaman')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->headerToken($token)
            ->getJson('/api/v1/anggota/riwayat-pinjaman?status=lunas')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.status', 'lunas');

        $this->headerToken($token)
            ->getJson('/api/v1/anggota/riwayat-pinjaman?status=salah')
            ->assertUnprocessable();
    }

    public function test_limit_sama_dengan_service(): void
    {
        $anggota = $this->buatAnggota('TOP-950031', ['tanggal_jadi_anggota' => now()->subYears(2)]);
        $service = app(EligibilitasPinjamanService::class);

        $res = $this->headerToken($anggota->user->createToken('uji')->plainTextToken)
            ->getJson('/api/v1/anggota/limit')
            ->assertOk();

        $this->assertEquals((float) $service->limitMaksimal($anggota), $res->json('limit_maksimal'));
        $this->assertEquals((float) $service->limitMaksimal($anggota), $res->json('limit_tersedia'));
        $res->assertJsonPath('boleh', true)
            ->assertJsonPath('sisa_angsuran', 0);
    }

    public function test_simpanan_ringkasan_dan_filter_bulan(): void
    {
        $a1 = $this->buatAnggota('TOP-950041');
        $a2 = $this->buatAnggota('TOP-950042');
        $bulanIni = now()->format('Y-m');

        Simpanan::create([
            'anggota_id' => $a1->id, 'jenis' => 'pokok', 'jumlah' => 50_000,
            'bulan_periode' => $bulanIni, 'tanggal_input' => now(), 'input_by' => $a1->user_id,
        ]);
        Simpanan::create([
            'anggota_id' => $a1->id, 'jenis' => 'wajib', 'jumlah' => 30_000,
            'bulan_periode' => '2020-01', 'tanggal_input' => '2020-01-15', 'input_by' => $a1->user_id,
        ]);
        Simpanan::create([
            'anggota_id' => $a2->id, 'jenis' => 'wajib', 'jumlah' => 999_000,
            'bulan_periode' => $bulanIni, 'tanggal_input' => now(), 'input_by' => $a2->user_id,
        ]);

        $token = $a1->user->createToken('uji')->plainTextToken;

        $res = $this->headerToken($token)
            ->getJson('/api/v1/anggota/simpanan')
            ->assertOk();

        $this->assertEquals(80000.0, $res->json('ringkasan.total'));
        $this->assertEquals(50000.0, $res->json('ringkasan.pokok'));
        $this->assertEquals(30000.0, $res->json('ringkasan.wajib'));

        // Data milik anggota lain tidak bocor
        $this->headerToken($token)
            ->getJson('/api/v1/anggota/simpanan')
            ->assertJsonMissing(['jumlah' => 999000.0]);

        $this->headerToken($token)
            ->getJson("/api/v1/anggota/simpanan?bulan_periode={$bulanIni}")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->headerToken($token)
            ->getJson('/api/v1/anggota/simpanan?bulan_periode=salah')
            ->assertUnprocessable();
    }

    public function test_riwayat_maksimal_sepuluh_query_dengan_tiga_pinjaman(): void
    {
        $anggota = $this->buatAnggota('TOP-950061');
        $p1 = $this->buatPinjamanAktif($anggota);
        $this->buatPinjamanAktif($anggota, ['nominal' => 2_000_000]);
        $p3 = $this->buatPinjamanAktif($anggota, ['nominal' => 3_000_000]);

        // Satu percepatan aktif agar jalur concat jadwal ikut teruji.
        $pp = PengajuanPercepatan::create([
            'pinjaman_id' => $p3->id, 'tipe' => 'percepat',
            'tenor_lama' => 2, 'tenor_baru' => 4, 'status' => 'aktif',
            'keterangan' => 'Uji', 'tanggal_pengajuan' => now()->format('Y-m-d'),
        ]);
        AngsuranPercepatan::create([
            'pengajuan_percepatan_id' => $pp->id, 'cicilan_ke' => 1,
            'nominal_pokok' => 100_000, 'nominal_bunga' => 10_000, 'total_bayar' => 110_000,
            'status' => 'belum_bayar', 'tanggal_jatuh_tempo' => now()->format('Y-m-d'),
        ]);
        // Jadwal lama yang digantikan tidak boleh muncul.
        Angsuran::create([
            'pinjaman_id' => $p3->id, 'cicilan_ke' => 9,
            'nominal_pokok' => 1, 'nominal_bunga' => 1, 'total_bayar' => 2,
            'status' => 'digantikan', 'tanggal_jatuh_tempo' => now()->format('Y-m-d'),
        ]);

        $token = $anggota->user->createToken('uji')->plainTextToken;

        DB::enableQueryLog();
        $res = $this->headerToken($token)->getJson('/api/v1/anggota/riwayat-pinjaman');
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $res->assertOk();
        // Auth + anggota + count + page + 3 eager = ~8; tanpa eager-reuse > 13.
        $this->assertLessThanOrEqual(10, count($queries), 'Riwayat boros query: '.count($queries));

        $item = collect($res->json('data'))->firstWhere('id', $p3->id);
        $cicilan = collect($item['angsuran'])->pluck('cicilan_ke')->all();
        $this->assertContains(1, $cicilan);
        $this->assertNotContains(9, $cicilan);
        $this->assertNotEmpty($item['riwayat_perubahan']);
        $this->assertSame($p1->id, collect($res->json('data'))->firstWhere('id', $p1->id)['id']);
    }

    public function test_dokumentasi_api_custom(): void
    {
        $res = $this->get('/api/documentation')->assertOk();

        // Semua endpoint harus terdokumentasi di halaman.
        foreach (['/api/v1/login', '/api/v1/logout', '/api/v1/user', '/api/v1/sso/token', '/api/v1/anggota/pinjaman-aktif', '/api/v1/anggota/riwayat-pinjaman', '/api/v1/anggota/limit', '/api/v1/anggota/simpanan'] as $path) {
            $res->assertSee($path, false);
        }

        // Branding koperasi, tanpa jejak pihak ketiga.
        $res->assertSee('Dokumentasi API v1', false)
            ->assertSee('/images/logo.png', false)
            ->assertDontSee('scalar', false)
            ->assertDontSee('cdn.jsdelivr', false)
            ->assertDontSee('fonts.', false);

        // Sidebar navigasi + pencarian + data cURL per endpoint.
        $res->assertSee('Daftar endpoint', false)
            ->assertSee('Cari endpoint', false)
            ->assertSee('Konsol coba', false)
            ->assertSee('data-method="GET"', false)
            ->assertSee('data-method="POST"', false);

        // Spec JSON Scramble sudah dibuang total.
        $this->get('/docs/api.json')->assertNotFound();
    }

    public function test_sso_token_dari_sesi_web(): void
    {
        $anggota = $this->buatAnggota('TOP-950051');
        $this->actingAs($anggota->user);

        $res = $this->postJson('/api/v1/sso/token')->assertOk();
        $token = $res->json('token');
        $this->assertNotEmpty($token);

        $this->headerToken($token)->getJson('/api/v1/anggota/limit')->assertOk();
    }
}
