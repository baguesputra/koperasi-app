<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\JurnalKas;
use App\Models\Pinjaman;
use App\Models\Simpanan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class AnggotaResignReaktivasiTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function buatAnggotaDenganSaldo(): Anggota
    {
        $user = User::firstOrCreate(
            ['no_karyawan' => 'TOP-920001'],
            ['name' => 'Calon Resign', 'email' => 'resign@test.id', 'password' => bcrypt('x'), 'harus_ganti_password' => false]
        );
        $anggota = Anggota::create([
            'user_id' => $user->id, 'no_anggota' => 'ANG-RSG-0001', 'nama' => 'Calon Resign',
            'cabang' => 'Banjarmasin', 'unit_bisnis' => 'Ops', 'jabatan' => 'staff',
            'tanggal_mulai_kerja' => now()->subYears(3), 'tanggal_jadi_anggota' => now()->subYears(3),
            'status' => 'aktif',
        ]);

        // Saldo simpanan pokok+wajib 600rb (cukup, tanpa pinjaman aktif)
        foreach ([['pokok', 300_000], ['wajib', 300_000]] as [$jenis, $jumlah]) {
            Simpanan::create([
                'anggota_id' => $anggota->id, 'jenis' => $jenis, 'jumlah' => $jumlah,
                'bulan_periode' => now()->format('Y-m'), 'tanggal_input' => now(), 'input_by' => 1,
            ]);
        }

        return $anggota;
    }

    public function test_resign_membekukan_akun_mencatat_settlement_dan_jurnal_return(): void
    {
        $anggota = $this->buatAnggotaDenganSaldo();
        $this->masuk('ADM-000001');

        $this->post(route('anggota.resign', $anggota), [
            'alasan_resign' => 'Pindah domisili ke luar kota.',
            'tanggal_resign' => now()->format('Y-m-d'),
            'konfirmasi_pelunasan' => '1',
        ])->assertRedirect(route('anggota.index'));

        $anggota->refresh();
        $this->assertSame('resign', $anggota->status);
        $this->assertNotNull($anggota->resigned_settlement_json);
        $this->assertEquals(600_000.0, (float) $anggota->resigned_settlement_json['total_dikembalikan']);

        // Jurnal: masuk transit + keluar (return pokok & wajib)
        $this->assertTrue(JurnalKas::where('kategori', 'simpanan_resign_masuk')->where('referensi_id', $anggota->id)->exists());
        $this->assertTrue(
            JurnalKas::where('kategori', 'return_simpanan_pokok')->where('referensi_id', $anggota->id)->exists()
            || JurnalKas::where('kategori', 'return_simpanan_wajib')->where('referensi_id', $anggota->id)->exists()
        );
    }

    public function test_user_resign_diblokir_saat_login_berikutnya(): void
    {
        $anggota = $this->buatAnggotaDenganSaldo();
        $this->masuk('ADM-000001');
        $this->post(route('anggota.resign', $anggota), [
            'alasan_resign' => 'Berhenti menjadi anggota koperasi.',
            'tanggal_resign' => now()->format('Y-m-d'),
            'konfirmasi_pelunasan' => '1',
        ])->assertRedirect();

        // Middleware memblokir user resign
        $this->actingAs($anggota->user);
        $res = $this->get(route('dashboard'));
        $res->assertRedirect(route('login'));
    }

    public function test_reaktivasi_menghidupkan_kembali_anggota(): void
    {
        $anggota = $this->buatAnggotaDenganSaldo();
        $this->masuk('ADM-000001');
        $this->post(route('anggota.resign', $anggota), [
            'alasan_resign' => 'Resign untuk pengujian reaktivasi.',
            'tanggal_resign' => now()->format('Y-m-d'),
            'konfirmasi_pelunasan' => '1',
        ])->assertRedirect();

        $this->post(route('anggota.aktifkan-kembali', $anggota), [
            'alasan_reaktivasi' => 'Karyawan kembali bekerja di perusahaan.',
        ])->assertRedirect(route('anggota.index'));

        $this->assertSame('aktif', $anggota->refresh()->status);
    }
}
