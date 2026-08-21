<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\Pinjaman;
use App\Models\Simpanan;
use App\Models\User;
use App\Services\Anggota\ResignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResignServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        return User::factory()->create();
    }

    private function anggotaDenganSimpananDanPinjaman(User $user): Anggota
    {
        $anggota = Anggota::create([
            'user_id' => $user->id,
            'no_anggota' => 'TST-001',
            'nama' => 'Budi Test',
            'cabang' => 'Banjarmasin',
            'unit_bisnis' => 'Operasional',
            'jabatan' => 'staff',
            'tanggal_mulai_kerja' => '2024-01-01',
            'tanggal_jadi_anggota' => '2024-01-01',
            'status' => 'aktif',
        ]);

        Simpanan::create([
            'anggota_id' => $anggota->id,
            'jenis' => 'pokok',
            'jumlah' => 600_000,
            'bulan_periode' => now()->format('Y-m'),
            'tanggal_input' => now(),
            'input_by' => $user->id,
        ]);
        Simpanan::create([
            'anggota_id' => $anggota->id,
            'jenis' => 'wajib',
            'jumlah' => 400_000,
            'bulan_periode' => now()->format('Y-m'),
            'tanggal_input' => now(),
            'input_by' => $user->id,
        ]);

        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $user->id,
            'nominal' => 1_000_000,
            'tenor_bulan' => 2,
            'persentase_bunga' => 5,
            'status' => 'aktif',
            'tanggal_pengajuan' => now()->format('Y-m-d'),
        ]);

        Angsuran::create([
            'pinjaman_id' => $pinjaman->id,
            'cicilan_ke' => 1,
            'nominal_pokok' => 250_000,
            'nominal_bunga' => 50_000,
            'total_bayar' => 300_000,
            'tanggal_jatuh_tempo' => now()->format('Y-m-d'),
            'status' => 'belum_bayar',
        ]);
        Angsuran::create([
            'pinjaman_id' => $pinjaman->id,
            'cicilan_ke' => 2,
            'nominal_pokok' => 250_000,
            'nominal_bunga' => 50_000,
            'total_bayar' => 300_000,
            'tanggal_jatuh_tempo' => now()->format('Y-m-d'),
            'status' => 'belum_bayar',
        ]);

        return $anggota;
    }

    public function test_resign_melunasi_angsuran_dari_simpanan_dan_naikkan_saldo_pinjaman(): void
    {
        $user = $this->actor();
        $this->actingAs($user);
        KasKoperasi::create([
            'saldo_pinjaman' => 1_000_000,
            'saldo_dana_sosial' => 0,
            'saldo_pengembalian_simpanan' => 0,
        ]);
        $anggota = $this->anggotaDenganSimpananDanPinjaman($user);

        app(ResignService::class)->proses(
            $anggota,
            'Resign pribadi',
            now()->format('Y-m-d'),
            $user
        );

        $kas = KasKoperasi::first();

        // saldo_pinjaman NAIK sebesar pelunasan (300rb x2), tidak berkurang.
        $this->assertEquals(1_600_000, (float) $kas->saldo_pinjaman);
        // transit pengembalian_simpanan kembali ke 0.
        $this->assertEquals(0, (float) $kas->saldo_pengembalian_simpanan);

        // Riwayat pinjaman: masuk dengan sub "Pelunasan dari uang simpanan anggota".
        $this->assertDatabaseHas('jurnal_kas', [
            'kantong' => 'pinjaman',
            'kategori' => 'pelunasan_resign_pinjaman',
            'tipe' => 'masuk',
            'sub_judul' => 'Pelunasan dari uang simpanan anggota',
        ]);
        $this->assertEquals(
            600_000,
            (float) JurnalKas::where('kantong', 'pinjaman')
                ->where('kategori', 'pelunasan_resign_pinjaman')
                ->sum('jumlah')
        );

        // Riwayat pengembalian: keluar dengan sub "Uang simpanan dibayarkan angsuran".
        $jurnalPengembalian = JurnalKas::where('kantong', 'pengembalian_simpanan')
            ->where('kategori', 'pelunasan_resign_simpanan')
            ->first();
        $this->assertNotNull($jurnalPengembalian);
        $this->assertEquals('keluar', $jurnalPengembalian->tipe);
        $this->assertEquals('Uang simpanan dibayarkan angsuran', $jurnalPengembalian->sub_judul);
        $this->assertEquals(
            600_000,
            (float) JurnalKas::where('kantong', 'pengembalian_simpanan')
                ->where('kategori', 'pelunasan_resign_simpanan')
                ->sum('jumlah')
        );

        // Angsuran & pinjaman lunas.
        $this->assertDatabaseHas('angsuran', ['cicilan_ke' => 1, 'status' => 'lunas']);
        $this->assertDatabaseHas('pinjaman', ['status' => 'lunas']);
        $this->assertDatabaseHas('anggota', ['no_anggota' => 'TST-001', 'status' => 'resign']);
    }
}
