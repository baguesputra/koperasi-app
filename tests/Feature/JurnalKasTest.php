<?php

namespace Tests\Feature;

use App\Models\JurnalKas;
use App\Services\Keuangan\JurnalKasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class JurnalKasTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function buatJurnal(array $data, int $userId): JurnalKas
    {
        return JurnalKas::create(array_merge([
            'jumlah' => 100_000,
            'saldo_setelah' => 0,
            'tanggal' => now(),
            'created_by' => $userId,
        ], $data));
    }

    public function test_halaman_jurnal_butuh_permission_jurnal_lihat(): void
    {
        $anggota = $this->buatAnggota();

        $this->masuk($anggota->user->no_karyawan);
        $this->get(route('jurnal-kas.index'))->assertForbidden();

        $this->masuk('ADM-000001');
        $this->get(route('jurnal-kas.index'))->assertOk();
    }

    public function test_filter_kantong_dan_bulan(): void
    {
        $this->masuk('BEN-000001');

        $res = $this->get(route('jurnal-kas.index', ['kantong' => 'bank']))->assertOk();
        $props = $res->viewData('page')['props'];

        $this->assertSame('bank', $props['filters']['kantong']);
        $this->assertArrayHasKey('bank', $props['saldo']);
        $this->assertArrayHasKey('kas_kecil', $props['saldo']);
        $this->assertArrayNotHasKey('outstanding', $props['saldo']);
        $this->assertNotEmpty($props['kantongOptions']);
        $this->assertNotEmpty($props['kategoriOptions']);

        foreach ($props['riwayat'] as $row) {
            $this->assertSame('bank', $row['kantong']);
        }

        $bulanLalu = now()->subMonth()->format('Y-m');
        $resBulan = $this->get(route('jurnal-kas.index', ['bulan' => $bulanLalu]))->assertOk();
        $this->assertSame($bulanLalu, $resBulan->viewData('page')['props']['bulanFilter']);
    }

    public function test_cari_keterangan(): void
    {
        $this->masuk('BEN-000001');

        $res = $this->get(route('jurnal-kas.index', ['cari' => 'ZZZ-TIDAK-ADA']))->assertOk();
        $props = $res->viewData('page')['props'];

        $this->assertCount(0, $props['riwayat']);
    }

    public function test_urutan_menango_dengan_no_bukti(): void
    {
        $user = $this->masuk('BEN-000001');
        $tanggalAwal = now()->startOfMonth()->addDays(3);
        $tanggalAkhir = now()->startOfMonth()->addDays(10);

        $jurnalService = app(JurnalKasService::class);
        $jurnalService->catat('masuk', 'topup_bulanan', 'bank', 500_000, 'Ujiurutanbukti masuk', null, $tanggalAwal->format('Y-m-d'), $user->id);
        $jurnalService->catat('keluar', 'pengeluaran_koperasi', 'kas_kecil', 50_000, 'Ujiurutanbukti keluar', null, $tanggalAkhir->format('Y-m-d'), $user->id);

        $props = $this->get(route('jurnal-kas.index', ['cari' => 'Ujiurutanbukti']))->assertOk()->viewData('page')['props'];

        $this->assertCount(2, $props['riwayat']);
        $this->assertSame('Ujiurutanbukti masuk', $props['riwayat'][0]['keterangan']);
        $this->assertSame('Ujiurutanbukti keluar', $props['riwayat'][1]['keterangan']);

        foreach ($props['riwayat'] as $baris) {
            $this->assertNotNull($baris['no_bukti']);
            $this->assertMatchesRegularExpression('/^\d{3}\/JK[MK]\/[IVX]+\/\d{4}$/', $baris['no_bukti']);
        }
    }

    public function test_saldo_awal_dan_akhir_periode(): void
    {
        $user = $this->masuk('BEN-000001');

        $this->buatJurnal([
            'tipe' => 'keluar',
            'kategori' => 'pencairan_pinjaman',
            'kantong' => 'pinjaman',
            'jumlah' => 1_000_000,
            'saldo_setelah' => 7_000_000,
            'keterangan' => 'Cair pinjaman uji saldo periode',
            'tanggal' => now()->startOfMonth()->addDays(5),
        ], $user->id);

        // Ekspektasi independen: saldo_setelah baris fisik bank terakhir sebelum / dalam bulan ini.
        $akhirBulanLalu = JurnalKas::whereNotIn('kategori', JurnalKasService::KATEGORI_NON_FISIK)
            ->where('kantong', '!=', 'kas_kecil')
            ->where('tanggal', '<', now()->startOfMonth())
            ->orderByDesc('tanggal')->orderByDesc('id')
            ->first();
        $terakhirBulanIni = JurnalKas::whereNotIn('kategori', JurnalKasService::KATEGORI_NON_FISIK)
            ->where('kantong', '!=', 'kas_kecil')
            ->whereBetween('tanggal', [now()->startOfMonth(), now()->endOfMonth()])
            ->orderByDesc('tanggal')->orderByDesc('id')
            ->first();

        $props = $this->get(route('jurnal-kas.index'))->assertOk()->viewData('page')['props'];
        $ringkasan = $props['ringkasanPeriode'];

        $this->assertTrue($ringkasan['saldo_valid']);
        $this->assertSame((float) ($akhirBulanLalu?->saldo_setelah ?? 0), $ringkasan['saldo_awal']['bank']);
        $this->assertSame((float) $terakhirBulanIni->saldo_setelah, $ringkasan['saldo_akhir']['bank']);
        $this->assertGreaterThanOrEqual(1_000_000.0, $ringkasan['total_keluar']);

        $terfilter = $this->get(route('jurnal-kas.index', ['cari' => 'katakunciunik']))->assertOk()->viewData('page')['props'];
        $this->assertFalse($terfilter['ringkasanPeriode']['saldo_valid']);
    }

    public function test_filter_akun_memisahkan_bank_dan_kas_kecil(): void
    {
        $user = $this->masuk('BEN-000001');

        $this->buatJurnal([
            'tipe' => 'masuk',
            'kategori' => 'topup_bulanan',
            'kantong' => 'bank',
            'keterangan' => 'Baris bank uji akun',
            'saldo_setelah' => 1_000_000,
        ], $user->id);
        $this->buatJurnal([
            'tipe' => 'keluar',
            'kategori' => 'pengeluaran_koperasi',
            'kantong' => 'kas_kecil',
            'keterangan' => 'Baris kas kecil uji akun',
            'saldo_setelah' => 90_000,
        ], $user->id);

        $bank = $this->get(route('jurnal-kas.index', ['akun' => 'bank']))->assertOk()->viewData('page')['props'];
        $this->assertContains('Baris bank uji akun', array_column($bank['riwayat'], 'keterangan'));
        $this->assertNotContains('Baris kas kecil uji akun', array_column($bank['riwayat'], 'keterangan'));
        foreach ($bank['riwayat'] as $baris) {
            $this->assertSame('bank', $baris['akun']);
        }

        $kas = $this->get(route('jurnal-kas.index', ['akun' => 'kas_kecil']))->assertOk()->viewData('page')['props'];
        $this->assertContains('Baris kas kecil uji akun', array_column($kas['riwayat'], 'keterangan'));
        foreach ($kas['riwayat'] as $baris) {
            $this->assertSame('kas_kecil', $baris['akun']);
        }
    }

    public function test_transaksi_non_kas_terpisah_dari_daftar_utama(): void
    {
        $user = $this->masuk('BEN-000001');

        $this->buatJurnal([
            'tipe' => 'masuk',
            'kategori' => 'topup_bulanan',
            'kantong' => 'bank',
            'keterangan' => 'Fisik uji non kas',
            'saldo_setelah' => 5_000_000,
        ], $user->id);
        $this->buatJurnal([
            'tipe' => 'masuk',
            'kategori' => 'talangan_sosial_ke_pinjaman',
            'kantong' => 'pinjaman',
            'keterangan' => 'Non kas uji non kas',
            'saldo_setelah' => 0,
        ], $user->id);

        $props = $this->get(route('jurnal-kas.index'))->assertOk()->viewData('page')['props'];

        $this->assertContains('Fisik uji non kas', array_column($props['riwayat'], 'keterangan'));
        $this->assertNotContains('Non kas uji non kas', array_column($props['riwayat'], 'keterangan'));

        $this->assertContains('Non kas uji non kas', array_column($props['nonKas'], 'keterangan'));
        foreach ($props['nonKas'] as $baris) {
            $this->assertSame('audit', $baris['akun']);
        }
    }

    public function test_backfill_no_bukti_mengisi_baris_kosong(): void
    {
        $user = $this->masuk('BEN-000001');

        $jurnal = $this->buatJurnal([
            'tipe' => 'masuk',
            'kategori' => 'topup_bulanan',
            'kantong' => 'bank',
            'keterangan' => 'Baris tanpa bukti',
        ], $user->id);

        $this->assertNull($jurnal->fresh()->no_bukti);

        $this->artisan('jurnal:backfill-bukti')->assertSuccessful();

        $this->assertNotNull($jurnal->fresh()->no_bukti);
        $this->assertMatchesRegularExpression('/^\d{3}\/JKM\/[IVX]+\/\d{4}$/', $jurnal->fresh()->no_bukti);
        $this->assertSame(0, JurnalKas::whereNull('no_bukti')->count());
    }

    public function test_jurnal_baris_punya_no_bukti_dan_akun(): void
    {
        $user = $this->masuk('BEN-000001');

        $this->buatJurnal([
            'tipe' => 'keluar',
            'kategori' => 'pengeluaran_koperasi',
            'kantong' => 'kas_kecil',
            'no_bukti' => '001/JKK/X/2026',
            'keterangan' => 'Baris berbukti',
            'saldo_setelah' => 50_000,
        ], $user->id);

        $props = $this->get(route('jurnal-kas.index', ['cari' => 'Baris berbukti']))->assertOk()->viewData('page')['props'];

        $this->assertSame('001/JKK/X/2026', $props['riwayat'][0]['no_bukti']);
        $this->assertSame('kas_kecil', $props['riwayat'][0]['akun']);
    }
}
