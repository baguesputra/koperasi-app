<?php

namespace Tests\Feature\Api;

use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MobileKasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function loginWith(array $permissions): array
    {
        $user = User::factory()->create();
        $user->givePermissionTo(...$permissions);
        auth()->forgetGuards();

        return [$user, $user->createToken('uji')->plainTextToken];
    }

    private function requestAs(string $token)
    {
        return $this->withHeader('Authorization', "Bearer {$token}");
    }

    private function key(): string
    {
        return Str::uuid()->toString();
    }

    public function test_hanya_pengurus_dengan_izin_melihat_kas_yang_dapat_membaca(): void
    {
        [, $token] = $this->loginWith([]);
        $this->requestAs($token)->getJson('/api/v1/pengurus/kas')->assertForbidden();
    }

    public function test_mutasi_kas_memakai_service_dan_izin_topup(): void
    {
        [, $token] = $this->loginWith(['kas.lihat', 'kas.topup']);
        $saldoAwal = (float) KasKoperasi::first()->saldo_bank;
        $kasKecilAwal = (float) KasKoperasi::first()->saldo_kas_kecil;
        $jurnalSisihAwal = JurnalKas::whereIn('kategori', ['sisih_kas_kecil', 'terima_sisih_kas_kecil'])->count();

        $this->requestAs($token)->withHeader('Idempotency-Key', $this->key())
            ->postJson('/api/v1/pengurus/kas/topup', [
                'jumlah' => 100_000, 'sumber_topup' => 'modal', 'tanggal' => now()->format('Y-m-d'),
            ])->assertOk();
        $this->requestAs($token)->withHeader('Idempotency-Key', $this->key())
            ->postJson('/api/v1/pengurus/kas/pengeluaran', [
                'tipe' => 'perlengkapan', 'akun' => 'kas_kecil', 'jumlah' => 50_000,
                'keterangan' => 'Pembelian ATK', 'tanggal' => now()->format('Y-m-d'),
            ])->assertOk();
        $this->requestAs($token)->withHeader('Idempotency-Key', $this->key())
            ->postJson('/api/v1/pengurus/kas/sisihkan', ['jumlah' => 25_000])->assertOk();

        $this->assertSame($saldoAwal + 75_000, (float) KasKoperasi::first()->saldo_bank);
        $this->assertSame($kasKecilAwal - 25_000, (float) KasKoperasi::first()->saldo_kas_kecil);
        $this->assertSame($jurnalSisihAwal + 2, JurnalKas::whereIn('kategori', ['sisih_kas_kecil', 'terima_sisih_kas_kecil'])->count());

        $this->requestAs($token)->getJson('/api/v1/pengurus/kas?bulan='.now()->format('Y-m'))->assertOk()
            ->assertJsonStructure(['saldo_bank', 'saldo_kas_kecil', 'ringkasan' => ['total_masuk', 'total_keluar'], 'data', 'meta']);
    }

    public function test_izin_lihat_saja_tidak_bisa_mencatat(): void
    {
        [, $token] = $this->loginWith(['kas.lihat']);
        $this->requestAs($token)->withHeader('Idempotency-Key', $this->key())
            ->postJson('/api/v1/pengurus/kas/sisihkan', ['jumlah' => 10_000])->assertForbidden();
    }
}
