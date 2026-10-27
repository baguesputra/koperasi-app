<?php

namespace Tests\Feature\Api;

use App\Models\PengajuanLimit;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use App\Services\Pinjaman\EligibilitasPinjamanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class MobilePengajuanTest extends TestCase
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

    private function tokenAnggota($anggota): string
    {
        return $anggota->user->createToken('uji')->plainTextToken;
    }

    private function limitSaatIni($anggota): float
    {
        return app(EligibilitasPinjamanService::class)->limitMaksimal($anggota);
    }

    private function buatPinjamanAktif($anggota, array $override = []): Pinjaman
    {
        $pinjaman = Pinjaman::create(array_merge([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 2_000_000,
            'tenor_bulan' => 4,
            'keperluan' => 'Uji percepatan',
            'persentase_bunga' => 1,
            'status' => 'aktif',
            'tanggal_pengajuan' => now()->subMonths(1)->format('Y-m-d'),
            'tanggal_pencairan' => now()->subMonths(1)->format('Y-m-d'),
        ], $override));

        foreach (range(1, $pinjaman->tenor_bulan) as $ke) {
            $pinjaman->angsuran()->create([
                'cicilan_ke' => $ke,
                'nominal_pokok' => 500_000,
                'nominal_bunga' => 20_000,
                'total_bayar' => 520_000,
                'status' => 'belum_bayar',
                'tanggal_jatuh_tempo' => now()->subMonths(1)->copy()->addMonths($ke - 1)->endOfMonth(),
            ]);
        }

        return $pinjaman;
    }

    /* ------------------------------------------------------- pengajuan limit */

    public function test_tamu_ditolak_di_endpoint_batch4(): void
    {
        $this->getJson('/api/v1/pengajuan-limit')->assertUnauthorized();
        $this->postJson('/api/v1/pengajuan-limit', [])->assertUnauthorized();
        $this->getJson('/api/v1/percepatan')->assertUnauthorized();
        $this->postJson('/api/v1/percepatan/preview', [])->assertUnauthorized();
        $this->postJson('/api/v1/percepatan', [])->assertUnauthorized();
    }

    public function test_index_limit_struktur(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota('TOP-970001');
        $token = $this->tokenAnggota($anggota);

        $res = $this->headerToken($token)
            ->getJson('/api/v1/pengajuan-limit')
            ->assertOk();

        $res->assertJsonStructure(['data', 'limit_saat_ini', 'plafon_kebijakan']);
        $res->assertJsonPath('data', []);
        $this->assertEquals($this->limitSaatIni($anggota), $res->json('limit_saat_ini'));
        $this->assertEquals(10_000_000, $res->json('plafon_kebijakan'));

        // Setelah mengajukan, riwayat muncul terbaru dulu.
        $this->headerToken($token)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson('/api/v1/pengajuan-limit', [
                'limit_diminta' => $this->limitSaatIni($anggota) + 1_000_000,
                'keterangan' => 'Butuh modal usaha lebih besar.',
            ])
            ->assertCreated();

        $this->headerToken($token)
            ->getJson('/api/v1/pengajuan-limit')
            ->assertOk()
            ->assertJsonPath('data.0.status', 'diajukan')
            ->assertJsonStructure(['data' => [['tahapan']]]);
    }

    public function test_store_limit_201_dan_idempotency(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota('TOP-970002');
        $token = $this->tokenAnggota($anggota);
        $key = Str::uuid()->toString();

        $kirim = fn () => $this->headerToken($token)->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/pengajuan-limit', [
                'limit_diminta' => $this->limitSaatIni($anggota) + 1_000_000,
                'keterangan' => 'Butuh modal usaha lebih besar.',
            ]);

        $kirim()
            ->assertCreated()
            ->assertJsonPath('message', 'Pengajuan limit berhasil dikirim.')
            ->assertJsonPath('data.status', 'diajukan');

        $kedua = $kirim();
        $kedua->assertCreated();
        $kedua->assertHeader('X-Idempotency-Replay', 'true');

        $this->assertSame(1, PengajuanLimit::where('anggota_id', $anggota->id)->count());
    }

    public function test_store_limit_diminta_tidak_lebih_besar_422(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota('TOP-970003');
        $token = $this->tokenAnggota($anggota);

        $this->headerToken($token)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson('/api/v1/pengajuan-limit', [
                'limit_diminta' => $this->limitSaatIni($anggota),
                'keterangan' => 'Keterangan cukup panjang.',
            ])
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors' => ['limit_diminta']]);

        $this->assertSame(0, PengajuanLimit::where('anggota_id', $anggota->id)->count());
    }

    public function test_store_limit_duplikat_berjalan_422(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota('TOP-970004');
        $token = $this->tokenAnggota($anggota);
        $diminta = $this->limitSaatIni($anggota) + 1_000_000;

        $this->headerToken($token)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson('/api/v1/pengajuan-limit', [
                'limit_diminta' => $diminta,
                'keterangan' => 'Butuh modal usaha lebih besar.',
            ])
            ->assertCreated();

        $this->headerToken($token)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson('/api/v1/pengajuan-limit', [
                'limit_diminta' => $diminta,
                'keterangan' => 'Pengajuan kedua tetap panjang.',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Masih ada pengajuan limit yang sedang diproses.');

        $this->assertSame(1, PengajuanLimit::where('anggota_id', $anggota->id)->count());
    }

    /* ------------------------------------------------------------ percepatan */

    public function test_index_percepatan_tanpa_pinjaman_aktif_null(): void
    {
        $anggota = $this->buatAnggota('TOP-970011');
        $token = $this->tokenAnggota($anggota);

        $this->headerToken($token)
            ->getJson('/api/v1/percepatan')
            ->assertOk()
            ->assertJsonPath('pinjaman', null)
            ->assertJsonPath('data', []);
    }

    public function test_index_percepatan_dengan_pinjaman_aktif(): void
    {
        $anggota = $this->buatAnggota('TOP-970012');
        $pinjaman = $this->buatPinjamanAktif($anggota);
        $token = $this->tokenAnggota($anggota);

        $this->headerToken($token)
            ->getJson('/api/v1/percepatan')
            ->assertOk()
            ->assertJsonPath('pinjaman.id', $pinjaman->id)
            ->assertJsonPath('pinjaman.sisa_angsuran', 4)
            ->assertJsonPath('pinjaman.sudah_pakai_percepatan', false)
            ->assertJsonPath('data', []);
    }

    public function test_preview_percepat_struktur(): void
    {
        $anggota = $this->buatAnggota('TOP-970013');
        $this->buatPinjamanAktif($anggota);
        $token = $this->tokenAnggota($anggota);

        $res = $this->headerToken($token)
            ->postJson('/api/v1/percepatan/preview', ['tipe' => 'percepat', 'tenor_baru' => 2])
            ->assertOk();

        $res->assertJsonStructure([
            'tipe',
            'tenor_baru',
            'cicilan_lama',
            'cicilan_baru',
            'sisa_pokok',
            'total_bayar_baru',
            'total_bunga_baru',
            'jadwal_baru',
            'pelunasan_sekarang',
        ]);

        $res->assertJsonPath('tipe', 'percepat');
        $res->assertJsonPath('tenor_baru', 2);
        $res->assertJsonPath('cicilan_lama', 520_000);
        $res->assertJsonPath('pelunasan_sekarang', null);
        $this->assertEqualsWithDelta(2_000_000, $res->json('sisa_pokok'), 0.01);
        $this->assertCount(2, $res->json('jadwal_baru'));
        $this->assertEqualsWithDelta(
            2_000_000,
            array_sum(array_column($res->json('jadwal_baru'), 'nominal_pokok')),
            0.01
        );
        $this->assertEqualsWithDelta(
            $res->json('total_bayar_baru'),
            array_sum(array_column($res->json('jadwal_baru'), 'total_bayar')),
            0.01
        );
    }

    public function test_preview_lunas_total_struktur(): void
    {
        $anggota = $this->buatAnggota('TOP-970014');
        $this->buatPinjamanAktif($anggota);
        $token = $this->tokenAnggota($anggota);

        $res = $this->headerToken($token)
            ->postJson('/api/v1/percepatan/preview', ['tipe' => 'lunas_total'])
            ->assertOk();

        $res->assertJsonPath('tipe', 'lunas_total');
        $res->assertJsonPath('tenor_baru', null);
        $res->assertJsonPath('jadwal_baru', []);
        $res->assertJsonPath('pelunasan_sekarang', 2_020_000);
        $this->assertEqualsWithDelta(2_000_000, $res->json('sisa_pokok'), 0.01);
        $this->assertEqualsWithDelta(2_020_000, $res->json('total_bayar_baru'), 0.01);
        $this->assertEqualsWithDelta(20_000, $res->json('total_bunga_baru'), 0.01);
    }

    public function test_store_percepatan_201(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota('TOP-970015');
        $pinjaman = $this->buatPinjamanAktif($anggota);
        $token = $this->tokenAnggota($anggota);
        $key = Str::uuid()->toString();

        $kirim = fn () => $this->headerToken($token)->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/percepatan', [
                'tipe' => 'percepat',
                'tenor_baru' => 2,
                'keterangan' => 'Dapat bonus tahunan, mau percepat.',
            ]);

        $kirim()
            ->assertCreated()
            ->assertJsonPath('message', 'Pengajuan perubahan tenor berhasil dikirim.')
            ->assertJsonPath('data.pinjaman_id', $pinjaman->id)
            ->assertJsonPath('data.tipe', 'percepat')
            ->assertJsonPath('data.tenor_baru', 2)
            ->assertJsonStructure(['data' => ['tahapan']]);

        $kedua = $kirim();
        $kedua->assertCreated();
        $kedua->assertHeader('X-Idempotency-Replay', 'true');

        $this->assertSame(1, PengajuanPercepatan::where('pinjaman_id', $pinjaman->id)->count());
    }

    public function test_store_percepatan_kedua_422_sudah_ada_menunggu(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota('TOP-970016');
        $pinjaman = $this->buatPinjamanAktif($anggota, ['nominal' => 2_500_000, 'tenor_bulan' => 5]);
        $token = $this->tokenAnggota($anggota);

        $payload = fn () => [
            'tipe' => 'perpanjang',
            'tenor_baru' => 6,
            'keterangan' => 'Kebutuhan mendesak bulan ini.',
        ];

        $this->headerToken($token)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson('/api/v1/percepatan', $payload())
            ->assertCreated();

        $this->headerToken($token)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson('/api/v1/percepatan', $payload())
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Masih ada pengajuan perubahan tenor yang sedang diproses.');

        $this->assertSame(1, PengajuanPercepatan::where('pinjaman_id', $pinjaman->id)->count());
    }

    public function test_store_percepatan_422_sudah_pernah_pakai(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota('TOP-970017');
        $pinjaman = $this->buatPinjamanAktif($anggota, ['sudah_pakai_percepatan' => true]);
        $token = $this->tokenAnggota($anggota);

        $this->headerToken($token)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson('/api/v1/percepatan', [
                'tipe' => 'percepat',
                'tenor_baru' => 2,
                'keterangan' => 'Sudah pernah pakai sebelumnya.',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Perubahan tenor hanya dapat dilakukan satu kali per pinjaman.');

        $this->assertSame(0, PengajuanPercepatan::where('pinjaman_id', $pinjaman->id)->count());
    }

    public function test_store_percepatan_tenor_tidak_valid_422(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota('TOP-970018');
        $pinjaman = $this->buatPinjamanAktif($anggota);
        $token = $this->tokenAnggota($anggota);

        // percepat tapi tenor_baru >= tenor saat ini.
        $this->headerToken($token)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson('/api/v1/percepatan', [
                'tipe' => 'percepat',
                'tenor_baru' => 4,
                'keterangan' => 'Tenor baru tidak lebih pendek.',
            ])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['tenor_baru']]);

        // perpanjang tapi tenor_baru <= tenor saat ini.
        $this->headerToken($token)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson('/api/v1/percepatan', [
                'tipe' => 'perpanjang',
                'tenor_baru' => 3,
                'keterangan' => 'Tenor baru tidak lebih panjang.',
            ])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['tenor_baru']]);

        $this->assertSame(0, PengajuanPercepatan::where('pinjaman_id', $pinjaman->id)->count());
    }
}
