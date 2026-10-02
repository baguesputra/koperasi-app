<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\KlaimDanaSosial;
use App\Models\SettingKas;
use App\Services\DanaSosial\KlaimDanaSosialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class KlaimDanaSosialTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    private function ajukan($anggota, array $override = []): KlaimDanaSosial
    {
        return app(KlaimDanaSosialService::class)->ajukan(
            $anggota,
            array_merge([
                'jenis' => 'duka',
                'hubungan' => 'orang_tua',
                'tanggal_kejadian' => now()->subDays(5)->format('Y-m-d'),
                'keterangan' => 'Mohon santunan duka atas berpulangnya orang tua.',
            ], $override),
            UploadedFile::fake()->image('bukti.jpg'),
            $anggota->user_id,
        );
    }

    public function test_tidak_boleh_dua_pengajuan_berjalan(): void
    {
        $this->expectException(\RuntimeException::class);
        $anggota = $this->buatAnggota();

        $this->ajukan($anggota);
        $this->ajukan($anggota);
    }

    public function test_sakit_wajib_sub_tipe_dan_minimal_hari(): void
    {
        $this->expectException(\RuntimeException::class);
        $anggota = $this->buatAnggota();

        $this->ajukan($anggota, [
            'jenis' => 'sakit', 'sub_tipe' => 'rajal', 'lama_hari' => 2,
            'tanggal_kejadian' => now()->subDays(4)->format('Y-m-d'),
        ]);
    }

    public function test_sakit_rajal_3_hari_lolos(): void
    {
        $anggota = $this->buatAnggota();

        $klaim = $this->ajukan($anggota, [
            'jenis' => 'sakit', 'sub_tipe' => 'rajal', 'lama_hari' => 3,
            'tanggal_kejadian' => now()->subDays(4)->format('Y-m-d'),
        ]);

        $this->assertSame('diajukan', $klaim->status);
        Storage::disk('public')->assertExists($klaim->foto_path);
    }

    public function test_bendahara_verifikasi_dengan_nominal_lalu_ketua_setujui_catat_pengeluaran(): void
    {
        $anggota = $this->buatAnggota();
        $klaim = $this->ajukan($anggota);

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.klaim-dana-sosial.approve', $klaim), [
            'catatan' => 'Dokumen lengkap, layak diteruskan.',
            'nominal' => 1_000_000,
        ])->assertStatus(302);

        $klaim->refresh();
        $this->assertSame('approved_bendahara', $klaim->status);
        $this->assertEquals(1_000_000, (float) $klaim->nominal_bendahara);
        $this->assertNull($klaim->pengeluaran_id);

        $this->masuk('KET-000001');
        $this->post(route('ketua.klaim-dana-sosial.approve', $klaim), [
            'catatan' => 'Disetujui sesuai ketentuan.',
            'nominal' => 750_000,
        ])->assertStatus(302);

        $klaim->refresh();
        $this->assertSame('disetujui', $klaim->status);
        $this->assertEquals(750_000, (float) $klaim->nominal_final);
        $this->assertNotNull($klaim->pengeluaran_id);
        $this->assertDatabaseHas('pengeluaran', [
            'id' => $klaim->pengeluaran_id, 'jenis' => 'dana_sosial', 'jumlah' => 750_000,
        ]);

        $this->assertTrue(
            AuditLog::where('aksi', 'klaim_disetujui')->where('keterangan', 'like', '%'.$anggota->nama.'%')->exists()
        );
    }

    public function test_bendahara_tolak_final_tidak_mencatat_pengeluaran(): void
    {
        $anggota = $this->buatAnggota();
        $klaim = $this->ajukan($anggota);

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.klaim-dana-sosial.reject', $klaim), ['catatan' => 'Dokumen belum memenuhi ketentuan.'])
            ->assertStatus(302);

        $this->assertSame('ditolak', $klaim->refresh()->status);
        $this->assertNull($klaim->pengeluaran_id);
    }

    public function test_ketua_setujui_melebihi_pagu_sosial_ditolak(): void
    {
        $jumlahSebelum = \App\Models\Pengeluaran::where('jenis', 'dana_sosial')
            ->whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)
            ->sum('jumlah');
        $countSebelum = \App\Models\Pengeluaran::where('jenis', 'dana_sosial')->count();
        SettingKas::updateOrCreate(
            ['kunci' => SettingKas::CADANGAN],
            ['label' => 'Cadangan Sosial Bulanan', 'nominal' => (float) $jumlahSebelum + 500_000]
        );

        $anggota = $this->buatAnggota();
        $klaim = $this->ajukan($anggota);

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.klaim-dana-sosial.approve', $klaim), [
            'catatan' => 'Dokumen lengkap.',
            'nominal' => 750_000,
        ])->assertStatus(302);

        $this->masuk('KET-000001');
        $this->post(route('ketua.klaim-dana-sosial.approve', $klaim), [
            'catatan' => 'Melebihi pagu sosial.',
            'nominal' => 750_000,
        ])->assertSessionHasErrors('keputusan');

        $klaim->refresh();
        $this->assertSame('approved_bendahara', $klaim->status);
        $this->assertNull($klaim->pengeluaran_id);
        $this->assertSame(0, \App\Models\Pengeluaran::where('jenis', 'dana_sosial')->count() - $countSebelum);
    }

    public function test_ketua_setujui_melebihi_kas_kecil_ditolak(): void
    {
        $countSebelum = \App\Models\Pengeluaran::where('jenis', 'dana_sosial')->count();
        $kasKecil = (float) \App\Models\KasKoperasi::first()->saldo_kas_kecil;
        $terpakaiBulanIni = (float) \App\Models\Pengeluaran::where('jenis', 'dana_sosial')
            ->whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)
            ->sum('jumlah');
        SettingKas::updateOrCreate(
            ['kunci' => SettingKas::CADANGAN],
            ['label' => 'Cadangan Sosial Bulanan', 'nominal' => $terpakaiBulanIni + $kasKecil + 200_000]
        );

        $anggota = $this->buatAnggota();
        $klaim = $this->ajukan($anggota);

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.klaim-dana-sosial.approve', $klaim), [
            'catatan' => 'Dokumen lengkap.',
            'nominal' => $kasKecil + 100_000,
        ])->assertStatus(302);

        $this->masuk('KET-000001');
        $this->post(route('ketua.klaim-dana-sosial.approve', $klaim), [
            'catatan' => 'Melebihi kas kecil.',
            'nominal' => $kasKecil + 100_000,
        ])->assertSessionHasErrors('keputusan');

        $klaim->refresh();
        $this->assertSame('approved_bendahara', $klaim->status);
        $this->assertNull($klaim->pengeluaran_id);
        $this->assertSame(0, \App\Models\Pengeluaran::where('jenis', 'dana_sosial')->count() - $countSebelum);
    }

    public function test_portal_validasi_tanggal_masa_depan_dan_foto_wajib(): void
    {
        $anggota = $this->buatAnggota();
        $this->masuk($anggota->user->no_karyawan);

        $this->post(route('portal.klaim-dana-sosial.store'), [
            'jenis' => 'duka',
            'hubungan' => 'orang_tua',
            'tanggal_kejadian' => now()->addDay()->format('Y-m-d'),
            'keterangan' => 'Mohon santunan duka atas berpulangnya orang tua.',
        ])->assertSessionHasErrors(['tanggal_kejadian', 'foto']);
    }

    public function test_portal_berhasil_ajukan_dan_terkunci_selama_berjalan(): void
    {
        $anggota = $this->buatAnggota();
        $this->masuk($anggota->user->no_karyawan);

        $this->post(route('portal.klaim-dana-sosial.store'), [
            'jenis' => 'bahagia_menikah',
            'tanggal_kejadian' => now()->subDays(10)->format('Y-m-d'),
            'keterangan' => 'Telah melangsungkan pernikahan pada tanggal tersebut.',
            'foto' => UploadedFile::fake()->image('nikah.jpg'),
        ])->assertStatus(302);

        $this->assertSame('diajukan', $anggota->klaimDanaSosial()->latest()->first()->status);

        $this->post(route('portal.klaim-dana-sosial.store'), [
            'jenis' => 'duka',
            'hubungan' => 'anak',
            'tanggal_kejadian' => now()->subDays(2)->format('Y-m-d'),
            'keterangan' => 'Mohon santunan duka atas berpulangnya anak.',
            'foto' => UploadedFile::fake()->image('duka.jpg'),
        ])->assertSessionHasErrors(['pengajuan']);
    }
}
