<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengajuanPercepatanTest extends TestCase
{
    use RefreshDatabase;

    private $testerUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function buatAnggotaAktif(): Pinjaman
    {
        $user = User::factory()->create(['no_karyawan' => 'TST-000001', 'password' => bcrypt('TST-000001')]);
        $anggota = Anggota::create([
            'nama' => 'Tester Percepatan',
            'no_anggota' => 'TST-000001',
            'no_karyawan' => 'TST-000001',
            'cabang' => 'Jakarta',
            'jabatan' => 'staff',
            'unit_bisnis' => 'IT',
            'tanggal_mulai_kerja' => now()->subYears(3),
            'tanggal_jadi_anggota' => now()->subYears(3),
            'status' => 'aktif',
            'user_id' => $user->id,
        ]);

        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'nominal' => 12_000_000,
            'tenor_bulan' => 12,
            'keperluan' => 'Tes',
            'persentase_bunga' => 1.00,
            'status' => 'aktif',
            'tanggal_pengajuan' => now()->subMonths(6),
            'tanggal_pencairan' => now()->subMonths(6),
        ]);

        // 2 cicilan lunas, 10 belum bayar
        $sisa = 12_000_000;
        $pokok = 1_000_000;
        for ($i = 1; $i <= 12; $i++) {
            $bunga = round($sisa * 0.01, 2);
            Angsuran::create([
                'pinjaman_id' => $pinjaman->id,
                'cicilan_ke' => $i,
                'nominal_pokok' => $i === 12 ? $sisa : $pokok,
                'nominal_bunga' => $bunga,
                'total_bayar' => ($i === 12 ? $sisa : $pokok) + $bunga,
                'status' => $i <= 2 ? 'lunas' : 'belum_bayar',
                'tanggal_jatuh_tempo' => now()->subMonths(6)->addMonths($i - 1)->endOfMonth(),
                'tanggal_konfirmasi_bayar' => $i <= 2 ? now() : null,
                'confirmed_by' => $i <= 2 ? $user->id : null,
            ]);
            $sisa -= $pokok;
        }

        $this->testerUser = $user;
        $user->assignRole('anggota');

        return $pinjaman;
    }

    public function test_anggota_dapat_mengajukan_ubah_tenor(): void
    {
        $pinjaman = $this->buatAnggotaAktif();
        $user = $this->testerUser;

        $this->actingAs($user)
            ->post(route('portal.pengajuan-percepatan.store'), [
                'pinjaman_id' => $pinjaman->id,
                'tipe' => 'ubah_tenor',
                'tenor_baru' => 8,
                'keterangan' => 'Ingin melunasi lebih cepat',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('pengajuan_percepatan', [
            'pinjaman_id' => $pinjaman->id,
            'tipe' => 'ubah_tenor',
            'status' => 'diajukan',
        ]);
    }

    public function test_tidak_boleh_2x_pengajuan(): void
    {
        $pinjaman = $this->buatAnggotaAktif();
        $user = $this->testerUser;

        $payload = ['pinjaman_id' => $pinjaman->id, 'tipe' => 'ubah_tenor', 'tenor_baru' => 8, 'keterangan' => 'Alasan pengajuan percepatan yang cukup panjang'];
        $this->actingAs($user)->post(route('portal.pengajuan-percepatan.store'), $payload)->assertRedirect();
        $this->actingAs($user)->post(route('portal.pengajuan-percepatan.store'), $payload)
            ->assertSessionHasErrors('percepatan');
    }

    public function test_approval_membuat_jadwal_baru_dan_menandai_lama(): void
    {
        $pinjaman = $this->buatAnggotaAktif();
        $user = $this->testerUser;

        $this->actingAs($user)->post(route('portal.pengajuan-percepatan.store'), [
            'pinjaman_id' => $pinjaman->id,
            'tipe' => 'ubah_tenor',
            'tenor_baru' => 8,
            'keterangan' => 'Alasan pengajuan percepatan yang cukup panjang',
        ]);

        $pengajuan = PengajuanPercepatan::first();
        $bendahara = User::where('no_karyawan', 'BEN-000001')->firstOrFail();
        $ketua = User::where('no_karyawan', 'KET-000001')->firstOrFail();

        $this->actingAs($bendahara)
            ->post(route('bendahara.pengajuan-percepatan.keputusan', $pengajuan), ['aksi' => 'setuju'])
            ->assertRedirect();
        $pengajuan->refresh();
        $this->assertEquals('approved_bendahara', $pengajuan->status);

        $this->actingAs($ketua)
            ->post(route('ketua.pengajuan-percepatan.keputusan', $pengajuan), ['aksi' => 'setuju'])
            ->assertRedirect();
        $pengajuan->refresh();

        $this->assertEquals('aktif', $pengajuan->status);
        $this->assertEquals(8, $pinjaman->fresh()->tenor_bulan);
        $this->assertEquals(8, $pengajuan->angsuranPercepatan()->count());

        $digantikan = Angsuran::where('pinjaman_id', $pinjaman->id)->where('status', 'digantikan')->count();
        $this->assertEquals(10, $digantikan);
    }

    public function test_bulan_mulai_otomatis_bulan_ini_jika_angsuran_bulan_ini_belum_lunas(): void
    {
        $pinjaman = $this->buatAnggotaAktif();
        $user = $this->testerUser;

        $this->actingAs($user)->post(route('portal.pengajuan-percepatan.store'), [
            'pinjaman_id' => $pinjaman->id,
            'tipe' => 'lunas_total',
            'keterangan' => 'Alasan pengajuan percepatan yang cukup panjang',
        ]);

        $pengajuan = PengajuanPercepatan::first();
        $bendahara = User::where('no_karyawan', 'BEN-000001')->firstOrFail();
        $ketua = User::where('no_karyawan', 'KET-000001')->firstOrFail();

        $this->actingAs($bendahara)->post(route('bendahara.pengajuan-percepatan.keputusan', $pengajuan), ['aksi' => 'setuju'])->assertRedirect();
        $this->actingAs($ketua)->post(route('ketua.pengajuan-percepatan.keputusan', $pengajuan), ['aksi' => 'setuju'])->assertRedirect();

        // Angsuran bulan berjalan dalam skenario ini belum lunas -> jadwal percepatan mulai bulan ini
        $pertama = $pengajuan->angsuranPercepatan()->orderBy('cicilan_ke')->first();
        $this->assertEquals(now()->endOfMonth()->format('Y-m-d'), $pertama->tanggal_jatuh_tempo->format('Y-m-d'));
        $this->assertEquals('bulan_ini', $pengajuan->fresh()->bulan_berlaku);
    }

    public function test_bulan_mulai_otomatis_bulan_depan_jika_angsuran_bulan_ini_sudah_lunas(): void
    {
        $pinjaman = $this->buatAnggotaAktif();
        $user = $this->testerUser;

        // Tandai angsuran bulan berjalan sebagai lunas
        $angsuranBulanIni = Angsuran::where('pinjaman_id', $pinjaman->id)
            ->whereYear('tanggal_jatuh_tempo', now()->year)
            ->whereMonth('tanggal_jatuh_tempo', now()->month)
            ->first();
        $angsuranBulanIni->update(['status' => 'lunas', 'tanggal_konfirmasi_bayar' => now(), 'confirmed_by' => $user->id]);

        $this->actingAs($user)->post(route('portal.pengajuan-percepatan.store'), [
            'pinjaman_id' => $pinjaman->id,
            'tipe' => 'lunas_total',
            'keterangan' => 'Alasan pengajuan percepatan yang cukup panjang',
        ]);

        $pengajuan = PengajuanPercepatan::first();
        $bendahara = User::where('no_karyawan', 'BEN-000001')->firstOrFail();
        $ketua = User::where('no_karyawan', 'KET-000001')->firstOrFail();

        $this->actingAs($bendahara)->post(route('bendahara.pengajuan-percepatan.keputusan', $pengajuan), ['aksi' => 'setuju'])->assertRedirect();
        $this->actingAs($ketua)->post(route('ketua.pengajuan-percepatan.keputusan', $pengajuan), ['aksi' => 'setuju'])->assertRedirect();

        // Angsuran bulan berjalan sudah lunas -> jadwal percepatan mulai bulan depan
        $pertama = $pengajuan->angsuranPercepatan()->orderBy('cicilan_ke')->first();
        $this->assertEquals(now()->addMonthNoOverflow()->endOfMonth()->format('Y-m-d'), $pertama->tanggal_jatuh_tempo->format('Y-m-d'));
        $this->assertEquals('bulan_depan', $pengajuan->fresh()->bulan_berlaku);
    }

    public function test_lunas_total_menghasilkan_1_tagihan_dan_melunasi_pinjaman(): void
    {
        $pinjaman = $this->buatAnggotaAktif();
        $user = $this->testerUser;

        $this->actingAs($user)->post(route('portal.pengajuan-percepatan.store'), [
            'pinjaman_id' => $pinjaman->id,
            'tipe' => 'lunas_total',
            'keterangan' => 'Alasan pengajuan percepatan yang cukup panjang',
        ]);

        $pengajuan = PengajuanPercepatan::first();
        $bendahara = User::where('no_karyawan', 'BEN-000001')->firstOrFail();
        $ketua = User::where('no_karyawan', 'KET-000001')->firstOrFail();

        $this->actingAs($bendahara)->post(route('bendahara.pengajuan-percepatan.keputusan', $pengajuan), ['aksi' => 'setuju'])->assertRedirect();
        $this->actingAs($ketua)->post(route('ketua.pengajuan-percepatan.keputusan', $pengajuan), ['aksi' => 'setuju'])->assertRedirect();

        $pengajuan->refresh();
        $this->assertEquals('aktif', $pengajuan->status);
        $this->assertEquals(1, $pengajuan->angsuranPercepatan()->count());

        // Setelah Ketua approve, tagihan final masih belum_bayar (pending) dan pinjaman belum lunas
        $angsuranFinal = $pengajuan->angsuranPercepatan()->first();
        $this->assertEquals('belum_bayar', $angsuranFinal->status);
        $this->assertEquals('aktif', $pinjaman->fresh()->status);

        // Bendahara mengonfirmasi tagihan final -> kas masuk & pinjaman lunas
        $this->actingAs($bendahara)
            ->post(route('bendahara.angsuran.percepatan.konfirmasi'), ['angsuran_percepatan_ids' => [$angsuranFinal->id]])
            ->assertRedirect();

        $angsuranFinal->refresh();
        $this->assertEquals('lunas', $angsuranFinal->status);
        $this->assertEquals('lunas', $pinjaman->fresh()->status);
    }

    public function test_preview_menolak_tenor_sama_dengan_sisa(): void
    {
        $pinjaman = $this->buatAnggotaAktif();
        $user = $this->testerUser;

        // Pinjaman 12jt / 12 bulan, 2 lunas -> sisa 10. Tenor = sisa ditolak.
        $resp = $this->actingAs($user)->postJson(route('portal.pengajuan-percepatan.preview'), [
            'pinjaman_id' => $pinjaman->id,
            'tipe' => 'ubah_tenor',
            'tenor_baru' => 10,
        ]);

        $resp->assertOk();
        $this->assertNotNull($resp->json('error'));
        $this->assertEmpty($resp->json('jadwal'));

        // Tenor valid (lebih kecil dari sisa) menghasilkan jadwal
        $respValid = $this->actingAs($user)->postJson(route('portal.pengajuan-percepatan.preview'), [
            'pinjaman_id' => $pinjaman->id,
            'tipe' => 'ubah_tenor',
            'tenor_baru' => 8,
        ]);
        $respValid->assertOk();
        $this->assertNull($respValid->json('error'));
        $this->assertCount(8, $respValid->json('jadwal'));
    }
}
