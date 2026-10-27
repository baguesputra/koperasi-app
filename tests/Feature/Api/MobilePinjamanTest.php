<?php

namespace Tests\Feature\Api;

use App\Models\Pinjaman;
use App\Services\Pinjaman\EligibilitasPinjamanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class MobilePinjamanTest extends TestCase
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

    private function buatPinjaman($anggota, array $override = []): Pinjaman
    {
        return Pinjaman::create(array_merge([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 1_000_000,
            'tenor_bulan' => 3,
            'keperluan' => 'Uji show',
            'persentase_bunga' => 1,
            'status' => 'diajukan',
            'tanggal_pengajuan' => now()->format('Y-m-d'),
        ], $override));
    }

    private function buatRekening($anggota, array $override = [])
    {
        return $anggota->rekening()->create(array_merge([
            'nama_bank' => 'BRI',
            'no_rekening' => '00112233'.random_int(10, 99),
            'atas_nama' => $anggota->nama,
            'is_default' => ! $anggota->rekening()->exists(),
        ], $override));
    }

    private function payloadStore($rekeningId): array
    {
        return [
            'nominal' => 1_000_000,
            'tenor_bulan' => 3,
            'keperluan' => 'Modal usaha kecil',
            'rekening_mode' => 'tersimpan',
            'rekening_id' => $rekeningId,
            'persetujuan' => true,
        ];
    }

    public function test_tamu_ditolak_di_endpoint_batch3(): void
    {
        $this->getJson('/api/v1/pinjaman/1')->assertUnauthorized();
        $this->postJson('/api/v1/pinjaman/cek-nominal', ['nominal' => 1000])->assertUnauthorized();
        $this->postJson('/api/v1/pinjaman/simulasi', ['nominal' => 1000, 'tenor_bulan' => 3])->assertUnauthorized();
        $this->postJson('/api/v1/pinjaman', [])->assertUnauthorized();
        $this->getJson('/api/v1/rekening')->assertUnauthorized();
        $this->postJson('/api/v1/rekening', [])->assertUnauthorized();
        $this->putJson('/api/v1/rekening/1/default')->assertUnauthorized();
        $this->deleteJson('/api/v1/rekening/1')->assertUnauthorized();
    }

    public function test_show_milik_sendiri_200(): void
    {
        $anggota = $this->buatAnggota('TOP-960001');
        $pinjaman = $this->buatPinjaman($anggota);
        $token = $anggota->user->createToken('uji')->plainTextToken;

        $res = $this->headerToken($token)
            ->getJson("/api/v1/pinjaman/{$pinjaman->id}")
            ->assertOk();

        $res->assertJsonPath('data.id', $pinjaman->id);
        $res->assertJsonPath('data.status', 'diajukan');
        $res->assertJsonStructure(['data' => ['tahapan', 'angsuran', 'angsuran_berikutnya']]);
    }

    public function test_show_tidak_ada_404(): void
    {
        $anggota = $this->buatAnggota('TOP-960002');
        $token = $anggota->user->createToken('uji')->plainTextToken;

        $this->headerToken($token)->getJson('/api/v1/pinjaman/999999')->assertNotFound();
    }

    public function test_show_orang_lain_butuh_izin(): void
    {
        $a1 = $this->buatAnggota('TOP-960011');
        $a2 = $this->buatAnggota('TOP-960012');
        $pinjaman = $this->buatPinjaman($a2);
        $token = $a1->user->createToken('uji')->plainTextToken;

        $this->headerToken($token)
            ->getJson("/api/v1/pinjaman/{$pinjaman->id}")
            ->assertForbidden();

        $a1->user->givePermissionTo('pinjaman.lihat');

        // Guard di-resolve ulang (di test, instance guard dipakai ulang antar request).
        auth()->forgetGuards();

        $this->headerToken($token)
            ->getJson("/api/v1/pinjaman/{$pinjaman->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $pinjaman->id);
    }

    public function test_cek_nominal_valid(): void
    {
        $anggota = $this->buatAnggota('TOP-960021');
        $token = $anggota->user->createToken('uji')->plainTextToken;

        $this->headerToken($token)
            ->postJson('/api/v1/pinjaman/cek-nominal', ['nominal' => 1_000_000])
            ->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('tenor_maksimal', 3);
    }

    public function test_cek_nominal_over_limit_422(): void
    {
        $anggota = $this->buatAnggota('TOP-960022');
        $token = $anggota->user->createToken('uji')->plainTextToken;

        $this->headerToken($token)
            ->postJson('/api/v1/pinjaman/cek-nominal', ['nominal' => 9_000_000])
            ->assertUnprocessable()
            ->assertJsonPath('valid', false);
    }

    public function test_simulasi_struktur(): void
    {
        $anggota = $this->buatAnggota('TOP-960031');
        $token = $anggota->user->createToken('uji')->plainTextToken;

        $res = $this->headerToken($token)
            ->postJson('/api/v1/pinjaman/simulasi', ['nominal' => 1_000_000, 'tenor_bulan' => 3])
            ->assertOk();

        $jadwal = $res->json('jadwal');
        $this->assertCount(3, $jadwal);
        $this->assertEquals(1, $jadwal[0]['cicilan_ke']);
        $this->assertEqualsWithDelta(1_000_000, array_sum(array_column($jadwal, 'nominal_pokok')), 0.01);
        $this->assertEqualsWithDelta(
            array_sum(array_column($jadwal, 'total_bayar')),
            $res->json('total_dibayar'),
            0.01
        );
    }

    public function test_store_201_dan_idempotency(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota('TOP-960041');
        $rekening = $this->buatRekening($anggota);
        $token = $anggota->user->createToken('uji')->plainTextToken;
        $key = Str::uuid()->toString();

        $kirim = fn () => $this->headerToken($token)->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/pinjaman', $this->payloadStore($rekening->id));

        $kirim()->assertCreated()->assertJsonPath('message', 'Pengajuan pinjaman berhasil dikirim.');
        $kedua = $kirim();
        $kedua->assertCreated();
        $kedua->assertHeader('X-Idempotency-Replay', 'true');

        $this->assertSame(1, Pinjaman::where('anggota_id', $anggota->id)->count());

        $pinjaman = Pinjaman::where('anggota_id', $anggota->id)->first();
        $this->assertSame($rekening->nama_bank, $pinjaman->snapshot_bank);
        $this->assertSame($rekening->no_rekening, $pinjaman->snapshot_no_rekening);
    }

    public function test_store_rekening_baru_ikut_tersimpan(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota('TOP-960042');
        $token = $anggota->user->createToken('uji')->plainTextToken;

        $this->headerToken($token)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson('/api/v1/pinjaman', [
                'nominal' => 1_000_000,
                'tenor_bulan' => 3,
                'keperluan' => 'Modal usaha kecil',
                'rekening_mode' => 'baru',
                'nama_bank' => 'Mandiri',
                'no_rekening' => '88990011',
                'atas_nama' => $anggota->nama,
                'persetujuan' => true,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('rekening_anggota', [
            'anggota_id' => $anggota->id,
            'nama_bank' => 'Mandiri',
            'no_rekening' => '88990011',
        ]);
    }

    public function test_store_over_limit_422(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota('TOP-960043');
        $rekening = $this->buatRekening($anggota);
        $token = $anggota->user->createToken('uji')->plainTextToken;

        $this->headerToken($token)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson('/api/v1/pinjaman', array_merge(
                $this->payloadStore($rekening->id),
                ['nominal' => 9_000_000, 'tenor_bulan' => 12]
            ))
            ->assertUnprocessable()
            ->assertJsonStructure(['message']);

        $this->assertSame(0, Pinjaman::where('anggota_id', $anggota->id)->count());
    }

    public function test_store_rekening_orang_lain_422(): void
    {
        $a1 = $this->buatAnggota('TOP-960051');
        $a2 = $this->buatAnggota('TOP-960052');
        $milikA2 = $this->buatRekening($a2);
        $token = $a1->user->createToken('uji')->plainTextToken;

        $this->headerToken($token)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson('/api/v1/pinjaman', $this->payloadStore($milikA2->id))
            ->assertUnprocessable();
    }

    public function test_rekening_crud_dan_default(): void
    {
        $anggota = $this->buatAnggota('TOP-960061');
        $token = $anggota->user->createToken('uji')->plainTextToken;
        $key = fn () => ['Idempotency-Key' => Str::uuid()->toString()];

        $r1 = $this->headerToken($token)->withHeaders($key())
            ->postJson('/api/v1/rekening', [
                'nama_bank' => 'BRI', 'no_rekening' => '111', 'atas_nama' => $anggota->nama,
            ])
            ->assertCreated()
            ->assertJsonPath('data.is_default', true)
            ->json('data');

        $r2 = $this->headerToken($token)->withHeaders($key())
            ->postJson('/api/v1/rekening', [
                'nama_bank' => 'Mandiri', 'no_rekening' => '222', 'atas_nama' => $anggota->nama,
            ])
            ->assertCreated()
            ->assertJsonPath('data.is_default', false)
            ->json('data');

        $this->headerToken($token)->withHeaders($key())
            ->putJson("/api/v1/rekening/{$r2['id']}/default")
            ->assertOk()
            ->assertJsonPath('message', 'Rekening utama diperbarui.');

        $daftar = $this->headerToken($token)->getJson('/api/v1/rekening')->assertOk()->json('data');
        $this->assertSame($r2['id'], $daftar[0]['id']);
        $this->assertTrue($daftar[0]['is_default']);
        $this->assertFalse(collect($daftar)->firstWhere('id', $r1['id'])['is_default']);

        // Hapus yang default -> sisa pertama jadi default.
        $this->headerToken($token)->withHeaders($key())
            ->deleteJson("/api/v1/rekening/{$r2['id']}")
            ->assertOk()
            ->assertJsonPath('message', 'Rekening dihapus.');

        $sisa = $this->headerToken($token)->getJson('/api/v1/rekening')->assertOk()->json('data');
        $this->assertCount(1, $sisa);
        $this->assertTrue($sisa[0]['is_default']);
    }

    public function test_rekening_orang_lain_tidak_bisa_diakses(): void
    {
        $a1 = $this->buatAnggota('TOP-960071');
        $a2 = $this->buatAnggota('TOP-960072');
        $milikA2 = $this->buatRekening($a2);
        $token = $a1->user->createToken('uji')->plainTextToken;
        $key = ['Idempotency-Key' => Str::uuid()->toString()];

        $this->headerToken($token)->withHeaders($key)->putJson("/api/v1/rekening/{$milikA2->id}/default")->assertNotFound();
        $this->headerToken($token)->withHeaders($key)->deleteJson("/api/v1/rekening/{$milikA2->id}")->assertNotFound();

        // Daftar hanya milik sendiri.
        $daftar = $this->headerToken($token)->getJson('/api/v1/rekening')->assertOk()->json('data');
        $this->assertEmpty($daftar);
    }

    public function test_rekening_validasi_422(): void
    {
        $anggota = $this->buatAnggota('TOP-960081');
        $token = $anggota->user->createToken('uji')->plainTextToken;

        $this->headerToken($token)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson('/api/v1/rekening', ['nama_bank' => 'BRI'])
            ->assertUnprocessable();
    }
}
