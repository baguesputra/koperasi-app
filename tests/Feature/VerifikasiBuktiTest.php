<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Pinjaman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifikasiBuktiTest extends TestCase
{
    use RefreshDatabase;

    private function buatPinjaman(array $override = []): Pinjaman
    {
        $anggota = Anggota::create([
            'user_id' => null,
            'no_anggota' => 'ANG-VRF-001',
            'nama' => 'Uji Verifikasi',
            'cabang' => 'Banjarmasin',
            'unit_bisnis' => 'Ops',
            'jabatan' => 'staff',
            'tanggal_mulai_kerja' => now()->subYears(2),
            'tanggal_jadi_anggota' => now()->subYears(2),
            'status' => 'aktif',
        ]);

        return Pinjaman::create(array_merge([
            'anggota_id' => $anggota->id,
            'nomor_dokumen' => 'PJM-VRF-001',
            'nominal' => 1_200_000,
            'tenor_bulan' => 12,
            'persentase_bunga' => 1,
            'status' => 'aktif',
            'keperluan' => 'Uji verifikasi bukti',
            'snapshot_bank' => 'BCA',
            'snapshot_no_rekening' => '1234567890',
            'snapshot_atas_nama' => 'Uji Verifikasi',
            'tanggal_pengajuan' => '2026-01-01',
            'tanggal_pencairan' => '2026-01-15',
        ], $override));
    }

    public function test_url_verifikasi_valid_selama_umur_pinjaman(): void
    {
        $pinjaman = $this->buatPinjaman();

        // Tepat setelah pencairan: link masih valid.
        $this->travelTo(now()->parse('2026-02-01 10:00:00'));

        $url = $pinjaman->verificationUrl();

        // URL harus bertanda tangan (temporarySignedRoute menambahkan expires + signature).
        $this->assertStringContainsString('expires=', $url);
        $this->assertStringContainsString('signature=', $url);

        $this->get($url)->assertOk();
    }

    public function test_url_verifikasi_kedaluwarsa_setelah_tenor_plus_tenggang(): void
    {
        $pinjaman = $this->buatPinjaman();

        // Anchor 2026-01-15 + 12 bulan tenor + 3 bulan tenggang = 2027-04-15.
        // Simulasikan waktu jauh melewati expiry → link harus ditolak (403).
        $this->travelTo(now()->parse('2027-06-01 10:00:00'));

        $url = $pinjaman->verificationUrl();

        $this->get($url)->assertForbidden();
    }

    public function test_url_verifikasi_tanpa_signature_ditolak(): void
    {
        $pinjaman = $this->buatPinjaman();

        // Tanpa parameter tanda tangan valid → middleware 'signed' menolak.
        $this->get(route('verifikasi.bukti', ['pinjaman' => $pinjaman->id]))
            ->assertForbidden();
    }
}
