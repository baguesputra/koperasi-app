<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\SettingKas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class KasPenuhiPaguTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function aturKas(float $bank, float $kasKecil): void
    {
        KasKoperasi::first()->update(['saldo_bank' => $bank, 'saldo_kas_kecil' => $kasKecil]);
    }

    public function test_refill_tepat_sebesar_kekurangan(): void
    {
        $this->assertEquals(5_000_000, (float) SettingKas::where('kunci', SettingKas::CADANGAN)->value('nominal'));
        $this->aturKas(100_000_000, 2_000_000);

        $this->artisan('kas:penuhi-pagu')->assertSuccessful();

        $kas = KasKoperasi::first();
        $this->assertEquals(97_000_000, (float) $kas->saldo_bank);
        $this->assertEquals(5_000_000, (float) $kas->saldo_kas_kecil);
        $this->assertDatabaseHas('jurnal_kas', [
            'kategori' => 'sisih_kas_kecil', 'kantong' => 'bank', 'tipe' => 'keluar', 'jumlah' => 3_000_000,
        ]);
        $this->assertDatabaseHas('jurnal_kas', [
            'kategori' => 'terima_sisih_kas_kecil', 'kantong' => 'kas_kecil', 'tipe' => 'masuk', 'jumlah' => 3_000_000,
        ]);
        $this->assertTrue(AuditLog::where('aksi', 'penuhi_pagu_kas_kecil')->exists());
    }

    public function test_tidak_isi_ulang_bila_sudah_penuh(): void
    {
        $this->aturKas(100_000_000, 6_000_000);

        $this->artisan('kas:penuhi-pagu')->assertSuccessful();

        $kas = KasKoperasi::first();
        $this->assertEquals(100_000_000, (float) $kas->saldo_bank);
        $this->assertEquals(6_000_000, (float) $kas->saldo_kas_kecil);
    }

    public function test_bank_kurang_pindahkan_sebisanya(): void
    {
        $this->aturKas(1_000_000, 2_000_000);

        $this->artisan('kas:penuhi-pagu')->assertSuccessful();

        $kas = KasKoperasi::first();
        $this->assertEquals(0, (float) $kas->saldo_bank);
        $this->assertEquals(3_000_000, (float) $kas->saldo_kas_kecil);
    }

    public function test_bank_kosong_gagal(): void
    {
        $this->aturKas(0, 2_000_000);

        $this->artisan('kas:penuhi-pagu')->assertFailed();

        $this->assertEquals(2_000_000, (float) KasKoperasi::first()->saldo_kas_kecil);
    }

    public function test_idempoten_dua_kali_dalam_sebulan(): void
    {
        $this->aturKas(100_000_000, 2_000_000);

        $this->artisan('kas:penuhi-pagu')->assertSuccessful();
        $this->artisan('kas:penuhi-pagu')->assertSuccessful();

        $this->assertEquals(5_000_000, (float) KasKoperasi::first()->saldo_kas_kecil);
        $this->assertSame(1, JurnalKas::where('kategori', 'terima_sisih_kas_kecil')
            ->where('keterangan', 'like', 'Penuhi pagu kas kecil%')->count());
    }

    public function test_mengikuti_target_terbaru(): void
    {
        SettingKas::where('kunci', SettingKas::CADANGAN)->update(['nominal' => 8_000_000]);
        $this->aturKas(100_000_000, 5_000_000);

        $this->artisan('kas:penuhi-pagu')->assertSuccessful();

        $this->assertEquals(8_000_000, (float) KasKoperasi::first()->saldo_kas_kecil);
    }
}
