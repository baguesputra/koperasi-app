<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\Pinjaman;
use App\Models\SettingKas;
use App\Models\TabelTenor;
use App\Models\User;
use App\Services\Keuangan\JurnalKasService;
use App\Services\Pinjaman\PersetujuanPinjamanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class PinjamanTalanganTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        TabelTenor::firstOrCreate(
            ['nominal_min' => 10_000_001, 'nominal_max' => 100_000_000],
            ['tenor_maksimal_bulan' => 12]
        );
    }

    private function ajukanDisetujuiBendahara($anggota, float $nominal = 3_000_000, int $tenor = 3): Pinjaman
    {
        // Nominal uji besar (di luar tabel tenor portal): buat langsung + approve bendahara via service.
        $anggota->update(['limit_custom' => 100_000_000]);
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => $nominal,
            'nominal_diminta' => $nominal,
            'tenor_bulan' => $tenor,
            'tenor_diminta' => $tenor,
            'keperluan' => 'Modal usaha sampingan',
            'persentase_bunga' => 1.0,
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
        ]);

        app(PersetujuanPinjamanService::class)
            ->approveBendahara($pinjaman->refresh(), 'Setuju, data lengkap.', $nominal, $tenor);

        return $pinjaman->refresh();
    }

    private function aturKas(float $pinjaman, float $sosial, float $simpanan): void
    {
        // Samakan virtual ke target. Penurunan virtual via catat juga memakan
        // bank, jadi danai dulu bank sebesar total penurunan agar tidak gagal.
        $kas = KasKoperasi::firstOrFail();
        $jurnal = app(JurnalKasService::class);
        $adminId = User::where('no_karyawan', 'ADM-000001')->value('id');
        $butuhBank = 0.0;

        foreach (['pinjaman' => $pinjaman, 'dana_sosial' => $sosial, 'simpanan' => $simpanan] as $kantong => $target) {
            $kolom = JurnalKasService::KANTONG_SALDO[$kantong];
            $selisih = $target - (float) $kas->refresh()->{$kolom};

            if ($selisih < 0) {
                $butuhBank += -$selisih;
            }
        }

        if ($butuhBank > 0) {
            $jurnal->catat('masuk', 'topup_bulanan', 'bank', $butuhBank, 'Dana penyesuaian kas uji', null, now()->format('Y-m-d'), $adminId);
        }

        foreach (['pinjaman' => $pinjaman, 'dana_sosial' => $sosial, 'simpanan' => $simpanan] as $kantong => $target) {
            $kolom = JurnalKasService::KANTONG_SALDO[$kantong];
            $selisih = $target - (float) $kas->refresh()->{$kolom};

            if ($selisih > 0) {
                $jurnal->catat('masuk', 'topup_bulanan', $kantong, $selisih, 'Penyesuaian kas uji', null, now()->format('Y-m-d'), $adminId);
            } elseif ($selisih < 0) {
                $jurnal->catat('keluar', 'topup_bulanan', $kantong, -$selisih, 'Penyesuaian kas uji', null, now()->format('Y-m-d'), $adminId);
            }
        }

        // Set fisik bank = total virtual (pool cair = saldo bank).
        $bankTarget = $pinjaman + $sosial + $simpanan;
        $selisihBank = $bankTarget - (float) $kas->refresh()->saldo_bank;

        if ($selisihBank > 0) {
            $jurnal->catat('masuk', 'topup_bulanan', 'bank', $selisihBank, 'Penyesuaian bank uji', null, now()->format('Y-m-d'), $adminId);
        } elseif ($selisihBank < 0) {
            $jurnal->catat('keluar', 'topup_bulanan', 'bank', -$selisihBank, 'Penyesuaian bank uji', null, now()->format('Y-m-d'), $adminId);
        }

        SettingKas::updateOrCreate(['kunci' => SettingKas::PAGU], ['label' => 'Pagu', 'nominal' => 95_000_000]);
        SettingKas::updateOrCreate(['kunci' => SettingKas::CADANGAN], ['label' => 'Cadangan', 'nominal' => 5_000_000]);
    }

    public function test_cair_talangi_sosial_lalu_simpanan_sosial_boleh_nol(): void
    {
        // 80 + 10 + 10 = 100 operasional; cair 95 → sosial 10 + simpanan 5.
        $this->aturKas(80_000_000, 10_000_000, 10_000_000);

        $anggota = $this->buatAnggota();
        $pinjaman = $this->ajukanDisetujuiBendahara($anggota, 95_000_000, 12);

        $this->masuk('KET-000001');
        $this->post(route('ketua.pinjaman.approve', $pinjaman), ['catatan' => 'Cairkan.', 'nominal' => 95_000_000])
            ->assertStatus(302);

        $this->assertSame('aktif', $pinjaman->refresh()->status);

        $kas = KasKoperasi::first()->refresh();
        $this->assertEquals(0, (float) $kas->saldo_pinjaman);
        $this->assertEquals(0, (float) $kas->saldo_dana_sosial);
        $this->assertEquals(5_000_000, (float) $kas->saldo_simpanan);

        $this->assertEquals(10_000_000, (float) JurnalKas::where('kategori', 'talangan_sosial_ke_pinjaman')->sum('jumlah'));
        $this->assertEquals(5_000_000, (float) JurnalKas::where('kategori', 'talangan_simpanan_ke_pinjaman')->sum('jumlah'));

        $utang = app(JurnalKasService::class)->utangTalanganTerbuka();
        $this->assertEquals(10_000_000, $utang['dana_sosial']);
        $this->assertEquals(5_000_000, $utang['simpanan']);

        // Hak simpanan anggota tidak tersentuh talangan kas.
        $this->assertTrue(
            AuditLog::where('aksi', 'pinjaman_setujui_ketua')->where('keterangan', 'like', '%Talangan%')->exists()
        );
    }

    public function test_angsuran_kembalikan_simpanan_dulu_lalu_sosial(): void
    {
        $this->aturKas(80_000_000, 10_000_000, 10_000_000);

        $anggota = $this->buatAnggota();
        $pinjaman = $this->ajukanDisetujuiBendahara($anggota, 95_000_000, 12);

        $this->masuk('KET-000001');
        $this->post(route('ketua.pinjaman.approve', $pinjaman), ['catatan' => 'Cairkan.', 'nominal' => 95_000_000])
            ->assertStatus(302);

        $cicilan = $pinjaman->refresh()->angsuran()->where('status', 'belum_bayar')->orderBy('cicilan_ke')->firstOrFail();
        $totalCicilan = (float) $cicilan->total_bayar;

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.angsuran.konfirmasi'), ['angsuran_ids' => ["n-{$cicilan->id}"]])->assertStatus(302);

        // Kembali prioritas simpanan: min(utang simpanan, saldo pinjaman tersedia).
        $kembaliSimpanan = (float) JurnalKas::where('kategori', 'kembali_talangan_ke_simpanan')->sum('jumlah');
        $kembaliSosial = (float) JurnalKas::where('kategori', 'kembali_talangan_ke_sosial')->sum('jumlah');

        $this->assertEquals(min(5_000_000, $totalCicilan), $kembaliSimpanan);

        $sisaUntukSosial = max(0, $totalCicilan - $kembaliSimpanan);
        $this->assertEquals(min(10_000_000, $sisaUntukSosial), $kembaliSosial);

        $this->assertTrue(AuditLog::where('aksi', 'pinjaman_talangan_kembali')->exists());
    }

    public function test_cair_ditolak_bila_saldo_bank_kurang(): void
    {
        // Virtual boleh cukup, tetapi pool fisik bank dibuat di bawah nominal:
        // guard menolak sebelum talangan.
        $this->aturKas(4_000_000, 3_000_000, 1_000_000);

        $kas = KasKoperasi::firstOrFail();
        $adminId = User::where('no_karyawan', 'ADM-000001')->value('id');
        app(JurnalKasService::class)->catat(
            'keluar', 'topup_bulanan', 'bank', 6_000_000,
            'Turunkan pool bank uji', null, now()->format('Y-m-d'), $adminId
        );

        $anggota = $this->buatAnggota();
        $pinjaman = $this->ajukanDisetujuiBendahara($anggota, 5_000_000, 3);

        $this->masuk('KET-000001');
        $this->post(route('ketua.pinjaman.approve', $pinjaman), ['catatan' => 'Cairkan.', 'nominal' => 5_000_000])
            ->assertSessionHasErrors('keputusan');

        $this->assertSame('approved_bendahara', $pinjaman->refresh()->status);
        $this->assertSame(0, JurnalKas::where('kategori', 'talangan_sosial_ke_pinjaman')->count());
    }

    public function test_rekonsiliasi_ok_setelah_talangan_dan_kembali(): void
    {
        $this->aturKas(80_000_000, 10_000_000, 10_000_000);

        $anggota = $this->buatAnggota();
        $pinjaman = $this->ajukanDisetujuiBendahara($anggota, 95_000_000, 12);

        $this->masuk('KET-000001');
        $this->post(route('ketua.pinjaman.approve', $pinjaman), ['catatan' => 'Cairkan.', 'nominal' => 95_000_000])
            ->assertStatus(302);

        $this->artisan('kas:rekonsiliasi')->assertSuccessful();
    }
}
