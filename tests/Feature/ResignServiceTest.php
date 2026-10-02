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

    public function test_resign_offset_simpanan_dan_kembalikan_sisa_dari_bank(): void
    {
        $user = $this->actor();
        $this->actingAs($user);
        KasKoperasi::create([
            'saldo_pinjaman' => 0,
            'saldo_dana_sosial' => 0,
            'saldo_pengembalian_simpanan' => 0,
            'saldo_bank' => 5_000_000,
            'saldo_kas_kecil' => 1_000_000,
        ]);
        $anggota = $this->anggotaDenganSimpananDanPinjaman($user);

        app(ResignService::class)->proses(
            $anggota,
            'Resign pribadi',
            now()->format('Y-m-d'),
            $user
        );

        $kas = KasKoperasi::first();

        // Pelunasan 600rb di-offset simpanan (tanpa gerak kas); sisa 400rb
        // kembali ke anggota dari bank.
        $this->assertEquals(4_600_000, (float) $kas->saldo_bank);
        $this->assertEquals(1_000_000, (float) $kas->saldo_kas_kecil);

        // Jurnal pelunasan = audit saja (2 baris, saldo_setelah 0).
        $this->assertEquals(
            600_000,
            (float) JurnalKas::where('kategori', 'pelunasan_resign_pinjaman')
                ->where('tipe', 'masuk')
                ->sum('jumlah')
        );
        $this->assertSame(0, JurnalKas::where('kategori', 'pelunasan_resign_simpanan')->count());
        $this->assertSame(0, JurnalKas::where('kategori', 'simpanan_resign_masuk')->count());

        // Return wajib 400rb keluar dari bank.
        $this->assertDatabaseHas('jurnal_kas', [
            'kantong' => 'pengembalian_simpanan',
            'kategori' => 'return_simpanan_wajib',
            'tipe' => 'keluar',
            'jumlah' => 400_000,
        ]);

        // Angsuran & pinjaman lunas.
        $this->assertDatabaseHas('angsuran', ['cicilan_ke' => 1, 'status' => 'lunas']);
        $this->assertDatabaseHas('pinjaman', ['status' => 'lunas']);
        $this->assertDatabaseHas('anggota', ['no_anggota' => 'TST-001', 'status' => 'resign']);
    }
}
