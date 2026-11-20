<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\PengajuanLimit;
use App\Models\Pinjaman;
use App\Models\Simpanan;
use App\Services\Pinjaman\EligibilitasPinjamanService;
use App\Services\Simpanan\KonfirmasiSimpananService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class SyaratEnamBulanPinjamanTest extends TestCase
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

    private function idempotentHeader(): array
    {
        return ['Idempotency-Key' => Str::uuid()->toString()];
    }

    public function test_anggota_dibawah_enam_bulan_terkunci_di_dashboard_form_dan_api(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota('TOP-980001', [
            'tanggal_jadi_anggota' => now()->subMonths(2),
        ]);
        Simpanan::where('anggota_id', $anggota->id)->delete();

        $this->actingAs($anggota->user);

        // 1. Dashboard web mengunci pengajuan pinjaman & tambah limit serta mengirim detail progres 6 bulan
        $props = $this->get(route('portal.dashboard'))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertFalse($props['bisaAjukan']);
        $this->assertFalse($props['bolehAjukanLimit']);
        $this->assertNotNull($props['syaratMasaKeanggotaan']);
        $this->assertFalse($props['syaratMasaKeanggotaan']['terpenuhi']);
        $this->assertSame(0, $props['syaratMasaKeanggotaan']['bulan_simpanan_wajib']);
        $this->assertSame(6, $props['syaratMasaKeanggotaan']['kekurangan_bulan']);
        $this->assertEquals(300_000.0, (float) $props['syaratMasaKeanggotaan']['estimasi_pelunasan_dimuka']);

        // 2. Cek nominal & submit pinjaman / tambah limit di web terblokir
        $this->postJson(route('portal.pinjaman.cek-nominal'), ['nominal' => 500_000])
            ->assertUnprocessable()
            ->assertJsonPath('valid', false);

        $this->withHeaders($this->idempotentHeader())->post(route('portal.pinjaman.store'), [
            'nominal' => 500_000,
            'tenor_bulan' => 3,
            'keperluan' => 'Keperluan uji coba pinjaman',
            'rekening_mode' => 'baru',
            'nama_bank' => 'BCA',
            'no_rekening' => '1234567890',
            'atas_nama' => $anggota->nama,
            'persetujuan' => true,
        ])->assertSessionHasErrors('nominal');
        $this->assertSame(0, Pinjaman::where('anggota_id', $anggota->id)->count());

        $this->withHeaders($this->idempotentHeader())->post(route('portal.pengajuan-limit.store'), [
            'limit_diminta' => 2_000_000,
            'keterangan' => 'Kebutuhan tambahan biaya keluarga',
        ])->assertSessionHasErrors('limit_diminta');
        $this->assertSame(0, PengajuanLimit::where('anggota_id', $anggota->id)->count());

        // 3. API Mobile dashboard, pinjaman, dan tambah limit ikut mengunci
        $token = $anggota->user->createToken('uji')->plainTextToken;
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('ringkasan.boleh', false)
            ->assertJsonPath('ringkasan.boleh_tambah_limit', false)
            ->assertJsonPath('ringkasan.syarat_masa_keanggotaan.kekurangan_bulan', 6)
            ->assertJsonPath('ringkasan.syarat_masa_keanggotaan.estimasi_pelunasan_dimuka', 300000);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->withHeaders($this->idempotentHeader())
            ->postJson('/api/v1/pinjaman', [
                'nominal' => 500_000,
                'tenor_bulan' => 3,
                'keperluan' => 'Keperluan uji coba pinjaman',
                'rekening_mode' => 'baru',
                'nama_bank' => 'BCA',
                'no_rekening' => '1234567890',
                'atas_nama' => $anggota->nama,
                'persetujuan' => true,
            ])->assertUnprocessable();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->withHeaders($this->idempotentHeader())
            ->postJson('/api/v1/pengajuan-limit', [
                'limit_diminta' => 2_000_000,
                'keterangan' => 'Kebutuhan tambahan biaya keluarga',
            ])->assertUnprocessable();
    }

    public function test_bendahara_bayar_di_muka_melengkapi_kekurangan_enam_bulan_dan_membuka_pinjaman(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota('TOP-980002', [
            'tanggal_jadi_anggota' => now()->subMonths(2),
        ]);
        Simpanan::where('anggota_id', $anggota->id)->delete();

        $bulanLalu2 = now()->subMonths(2)->format('Y-m');
        $bulanLalu1 = now()->subMonth()->format('Y-m');

        // Sudah bayar 2 bulan simpanan wajib sebelumnya → sisa kekurangan 4 bulan (Rp 200.000)
        Simpanan::create([
            'anggota_id' => $anggota->id,
            'jenis' => 'wajib',
            'jumlah' => 45_000,
            'bulan_periode' => $bulanLalu2,
            'tanggal_input' => now()->subMonths(2),
            'input_by' => $anggota->user_id,
        ]);
        Simpanan::create([
            'anggota_id' => $anggota->id,
            'jenis' => 'wajib',
            'jumlah' => 45_000,
            'bulan_periode' => $bulanLalu1,
            'tanggal_input' => now()->subMonth(),
            'input_by' => $anggota->user_id,
        ]);

        $cekAwal = app(EligibilitasPinjamanService::class)->cek($anggota);
        $this->assertFalse($cekAwal['boleh']);
        $this->assertSame(2, $cekAwal['syarat_masa_keanggotaan']['bulan_simpanan_wajib']);
        $this->assertSame(4, $cekAwal['syarat_masa_keanggotaan']['kekurangan_bulan']);
        $this->assertEquals(200_000.0, (float) $cekAwal['syarat_masa_keanggotaan']['estimasi_pelunasan_dimuka']);

        $kasSebelum = KasKoperasi::firstOrFail();
        $saldoBankAwal = (float) $kasSebelum->saldo_bank;
        $saldoDansosAwal = (float) JurnalKas::where('kantong', 'dana_sosial')->sum('jumlah');

        // Bendahara mencatat pembayaran di muka 4 bulan agar genap 6 bulan
        $this->masuk('BEN-000001');
        $indexProps = $this->get(route('bendahara.simpanan.index'))
            ->assertOk()
            ->viewData('page')['props'];
        $entryBelumGenap = collect($indexProps['anggotaBelumGenapEnamBulan'])->firstWhere('id', $anggota->id);
        $this->assertNotNull($entryBelumGenap);
        $this->assertSame(4, $entryBelumGenap['kekurangan_bulan']);

        $this->withHeaders($this->idempotentHeader())->post(route('bendahara.simpanan.bayar-dimuka', $anggota->id), [
            'jumlah_bulan' => 4,
        ])->assertRedirect();

        // 2 bulan lama + 4 bulan baru wajib = 6 baris wajib, dan 4 baris dana sosial baru
        $this->assertSame(6, Simpanan::where('anggota_id', $anggota->id)->where('jenis', 'wajib')->count());
        $this->assertSame(4, Simpanan::where('anggota_id', $anggota->id)->where('jenis', 'dana_sosial')->count());

        // KasKoperasi (saldo_bank) & JurnalKas dana sosial bertambah tepat Rp 200.000 (wajib 180k + dansos 20k)
        $kasSesudah = $kasSebelum->fresh();
        $this->assertEquals($saldoBankAwal + 200_000.0, (float) $kasSesudah->saldo_bank);
        $this->assertEquals($saldoDansosAwal + 20_000.0, (float) JurnalKas::where('kantong', 'dana_sosial')->sum('jumlah'));
        $this->assertTrue(AuditLog::where('aksi', 'simpanan_bayar_dimuka')->exists());
        $this->assertSame(8, JurnalKas::where('referensi_id', $anggota->id)->whereIn('kategori', ['simpanan_wajib_masuk', 'dana_sosial_bulanan'])->count());

        // Periode bulan ini sudah tercatat sehingga tidak muncul di antrean Belum Bayar bulan ini
        $belumBulanIni = app(KonfirmasiSimpananService::class)->anggotaBelumSimpananWajib(now()->format('Y-m'));
        $this->assertFalse($belumBulanIni->contains('id', $anggota->id));

        // Anggota kini terbuka untuk pinjaman & tambah limit
        $this->actingAs($anggota->user);
        $propsSesudah = $this->get(route('portal.dashboard'))
            ->assertOk()
            ->viewData('page')['props'];
        $this->assertTrue($propsSesudah['bisaAjukan']);
        $this->assertTrue($propsSesudah['bolehAjukanLimit']);
        $this->assertTrue($propsSesudah['syaratMasaKeanggotaan']['terpenuhi']);

        // Bisa mengajukan pinjaman & tambah limit
        $this->withHeaders($this->idempotentHeader())->post(route('portal.pinjaman.store'), [
            'nominal' => 500_000,
            'tenor_bulan' => 3,
            'keperluan' => 'Keperluan mendesak setelah lunas 6 bln',
            'rekening_mode' => 'baru',
            'nama_bank' => 'BCA',
            'no_rekening' => '1234567890',
            'atas_nama' => $anggota->nama,
            'persetujuan' => true,
        ])->assertRedirect(route('portal.dashboard'));
        $this->assertSame(1, Pinjaman::where('anggota_id', $anggota->id)->count());

        // Uji juga tambah limit pada anggota lain yang baru lunas 6 bulan di muka
        $anggota2 = $this->buatAnggota('TOP-980003', ['tanggal_jadi_anggota' => now()->subMonth()]);
        $this->masuk('BEN-000001');
        $this->withHeaders($this->idempotentHeader())->post(route('bendahara.simpanan.bayar-dimuka', $anggota2->id), [
            'jumlah_bulan' => 6,
        ])->assertRedirect();

        $this->actingAs($anggota2->user);
        $this->withHeaders($this->idempotentHeader())->post(route('portal.pengajuan-limit.store'), [
            'limit_diminta' => 2_000_000,
            'keterangan' => 'Pengajuan tambah limit setelah lunas 6 bulan',
        ])->assertRedirect(route('portal.dashboard'));
        $this->assertSame(1, PengajuanLimit::where('anggota_id', $anggota2->id)->count());
    }

    public function test_guard_bayar_di_muka_otorisasi_dan_batas_kekurangan(): void
    {
        $anggota = $this->buatAnggota('TOP-980004', [
            'tanggal_jadi_anggota' => now()->subMonth(),
        ]);
        Simpanan::create([
            'anggota_id' => $anggota->id,
            'jenis' => 'wajib',
            'jumlah' => 45_000,
            'bulan_periode' => now()->format('Y-m'),
            'tanggal_input' => now(),
            'input_by' => $anggota->user_id,
        ]);

        // Non-Bendahara ditolak 403
        $this->actingAs($anggota->user);
        $this->withHeaders($this->idempotentHeader())
            ->post(route('bendahara.simpanan.bayar-dimuka', $anggota->id), ['jumlah_bulan' => 5])
            ->assertForbidden();

        // Bendahara kirim melebihi kekurangan (kekurangan hanya 5, minta 6) → error validasi
        $this->masuk('BEN-000001');
        $this->withHeaders($this->idempotentHeader())
            ->post(route('bendahara.simpanan.bayar-dimuka', $anggota->id), ['jumlah_bulan' => 6])
            ->assertSessionHasErrors('jumlah_bulan');
    }

    public function test_anggota_lama_genap_enam_bulan_lolos_tanpa_enam_baris_simpanan(): void
    {
        $anggotaLama = $this->buatAnggota('TOP-980005', [
            'tanggal_jadi_anggota' => now()->subMonths(6),
        ]);
        Simpanan::where('anggota_id', $anggotaLama->id)->delete();

        $eligibilitas = app(EligibilitasPinjamanService::class);
        $cek = $eligibilitas->cek($anggotaLama);
        $cekLimit = $eligibilitas->cekEligibilitasTambahLimit($anggotaLama);

        $this->assertTrue($cek['boleh']);
        $this->assertTrue($cek['syarat_masa_keanggotaan']['terpenuhi']);
        $this->assertTrue($cekLimit['boleh']);
    }

    public function test_api_bendahara_bayar_di_muka_berfungsi(): void
    {
        $anggota = $this->buatAnggota('TOP-980006', [
            'tanggal_jadi_anggota' => now()->subMonths(1),
        ]);
        Simpanan::where('anggota_id', $anggota->id)->delete();

        $bendahara = $this->masuk('BEN-000001');
        auth()->forgetGuards();
        $token = $bendahara->createToken('uji')->plainTextToken;

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->withHeaders($this->idempotentHeader())
            ->postJson("/api/v1/pengurus/simpanan/{$anggota->id}/bayar-dimuka", [
                'jumlah_bulan' => 6,
            ])->assertOk();

        $res->assertJsonPath('data.jumlah_bulan', 6)
            ->assertJsonPath('data.total_nominal', 300000);

        $this->assertTrue(app(EligibilitasPinjamanService::class)->cek($anggota->fresh())['boleh']);
    }
}
