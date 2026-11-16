<?php

namespace Tests\Feature;

use App\Laporan\LaporanRegistry;
use App\Models\Anggota;
use App\Models\AuditLog;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\Simpanan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class SimpananKonfirmasiTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function buatAnggota(string $no): Anggota
    {
        $user = User::firstOrCreate(
            ['no_karyawan' => $no],
            ['name' => 'Uji '.$no, 'email' => strtolower($no).'@t.id', 'password' => bcrypt($no), 'harus_ganti_password' => false]
        );

        return Anggota::create([
            'user_id' => $user->id,
            'no_anggota' => 'ANG-SC-'.substr($no, -4),
            'nama' => 'Simpanan '.$no,
            'cabang' => 'Banjarmasin',
            'unit_bisnis' => 'Ops',
            'jabatan' => 'staff',
            'tanggal_mulai_kerja' => now()->subYears(2),
            'tanggal_jadi_anggota' => now()->subYears(2),
            'status' => 'aktif',
        ]);
    }

    public function test_konfirmasi_massal_membuat_simpanan_wajib_dana_sosial_dan_jurnal(): void
    {
        $a1 = $this->buatAnggota('TOP-910001');
        $a2 = $this->buatAnggota('TOP-910002');
        $bulan = now()->format('Y-m');

        $saldoSosial = (float) JurnalKas::where('kantong', 'dana_sosial')->sum('jumlah');
        $this->masuk('BEN-000001');
        $this->post(route('bendahara.simpanan.konfirmasi'), [
            'anggota_ids' => [$a1->id, $a2->id],
            'bulan_periode' => $bulan,
        ])->assertStatus(302);

        // 2 anggota × (wajib + dana sosial)
        $this->assertSame(4, Simpanan::whereIn('anggota_id', [$a1->id, $a2->id])->count());
        $this->assertDatabaseHas('simpanan', ['anggota_id' => $a1->id, 'jenis' => 'wajib', 'bulan_periode' => $bulan]);
        $this->assertDatabaseHas('simpanan', ['anggota_id' => $a1->id, 'jenis' => 'dana_sosial', 'bulan_periode' => $bulan]);

        // Jurnal dana sosial bertambah 2×5.000
        $this->assertEquals($saldoSosial + 10_000, (float) JurnalKas::where('kantong', 'dana_sosial')->sum('jumlah'));
    }

    public function test_anggota_yang_sudah_dikonfirmasi_dilewati_otomatis(): void
    {
        $a1 = $this->buatAnggota('TOP-910003');
        $a2 = $this->buatAnggota('TOP-910004');
        $bulan = now()->format('Y-m');

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.simpanan.konfirmasi'), [
            'anggota_ids' => [$a1->id], 'bulan_periode' => $bulan,
        ])->assertStatus(302);

        // Konfirmasi kedua mencakup a1 (sudah) + a2 (baru) → hanya a2 dibuat
        $this->post(route('bendahara.simpanan.konfirmasi'), [
            'anggota_ids' => [$a1->id, $a2->id], 'bulan_periode' => $bulan,
        ])->assertStatus(302);

        $this->assertSame(2, Simpanan::where('anggota_id', $a1->id)->count());
        $this->assertSame(2, Simpanan::where('anggota_id', $a2->id)->count());
    }

    public function test_index_mengirimkan_daftar_cabang_sebagai_array_dan_komponen_benar(): void
    {
        $this->buatAnggota('TOP-910005');

        $this->masuk('BEN-000001');
        $response = $this->get(route('bendahara.simpanan.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Bendahara/Simpanan/Index')
                ->has('daftarCabang')
            );

        $daftarCabang = $response->viewData('page')['props']['daftarCabang'];
        $this->assertIsArray($daftarCabang);
        $this->assertTrue(array_is_list($daftarCabang));
        $this->assertContains('Banjarmasin', $daftarCabang);
    }

    public function test_rollback_konfirmasi_simpanan_membalik_saldo_hapus_simpanan_dan_jaga_neraca(): void
    {
        $a1 = $this->buatAnggota('TOP-910006');
        $bulan = now()->format('Y-m');
        $hariIni = now()->format('Y-m-d');

        $this->masuk('BEN-000001');
        $bankAwal = (float) KasKoperasi::first()->saldo_bank;
        $neracaAwal = LaporanRegistry::ambil('neraca')['data'](request()->merge(['tanggal' => $hariIni]));
        $nilaiAwal = fn (string $pos) => collect($neracaAwal['rows'])->firstWhere(fn ($r) => $r[0] === $pos)[1];
        $wajibAwal = $nilaiAwal('Simpanan Wajib');
        $sosialAwal = $nilaiAwal('Asuransi Sosial');

        $this->post(route('bendahara.simpanan.konfirmasi'), [
            'anggota_ids' => [$a1->id],
            'bulan_periode' => $bulan,
        ])->assertStatus(302);

        $propsSetelahKonfirmasi = $this->get(route('bendahara.simpanan.index', ['bulan' => $bulan]))
            ->assertOk()
            ->viewData('page')['props'];
        $this->assertNotContains($a1->id, array_column($propsSetelahKonfirmasi['belumSimpanan'], 'id'));
        $this->assertContains($a1->id, array_column($propsSetelahKonfirmasi['sudahSimpanan'], 'id'));

        $this->post(route('bendahara.simpanan.batalkan', $a1->id), [
            'bulan_periode' => $bulan,
            'alasan' => 'Salah contreng anggota',
        ])->assertStatus(302)->assertSessionHasNoErrors();

        $this->assertEquals($bankAwal, (float) KasKoperasi::first()->saldo_bank);
        $this->assertSame(0, Simpanan::where('anggota_id', $a1->id)->where('bulan_periode', $bulan)->count());

        $jurnalWajib = JurnalKas::where('kategori', 'simpanan_wajib_masuk')
            ->where('referensi_id', $a1->id)
            ->where('keterangan', "Simpanan wajib bulan {$bulan}")
            ->whereNull('koreksi_dari_id')
            ->firstOrFail();
        $this->assertNotNull($jurnalWajib->dibatalkan_pada);
        $this->assertDatabaseHas('jurnal_kas', [
            'koreksi_dari_id' => $jurnalWajib->id,
            'tipe' => 'keluar',
            'kategori' => 'simpanan_wajib_masuk',
        ]);

        $jurnalSosial = JurnalKas::where('kategori', 'dana_sosial_bulanan')
            ->where('referensi_id', $a1->id)
            ->where('keterangan', "Dana sosial bulan {$bulan}")
            ->whereNull('koreksi_dari_id')
            ->firstOrFail();
        $this->assertNotNull($jurnalSosial->dibatalkan_pada);
        $this->assertDatabaseHas('jurnal_kas', [
            'koreksi_dari_id' => $jurnalSosial->id,
            'tipe' => 'keluar',
            'kategori' => 'dana_sosial_bulanan',
        ]);

        $propsSetelahBatal = $this->get(route('bendahara.simpanan.index', ['bulan' => $bulan]))
            ->assertOk()
            ->viewData('page')['props'];
        $this->assertContains($a1->id, array_column($propsSetelahBatal['belumSimpanan'], 'id'));
        $this->assertNotContains($a1->id, array_column($propsSetelahBatal['sudahSimpanan'], 'id'));

        $neracaAkhir = LaporanRegistry::ambil('neraca')['data'](request()->merge(['tanggal' => $hariIni]));
        $nilaiAkhir = fn (string $pos) => collect($neracaAkhir['rows'])->firstWhere(fn ($r) => $r[0] === $pos)[1];
        $this->assertEquals($wajibAwal, $nilaiAkhir('Simpanan Wajib'));
        $this->assertEquals($sosialAwal, $nilaiAkhir('Asuransi Sosial'));
        $this->assertEquals($bankAwal, $nilaiAkhir('Bank'));

        $this->assertTrue(AuditLog::where('aksi', 'simpanan_konfirmasi_dibatalkan')->exists());
    }

    public function test_rollback_simpanan_menolak_anggota_nonaktif_dan_tanpa_izin(): void
    {
        $a1 = $this->buatAnggota('TOP-910007');
        $bulan = now()->format('Y-m');

        $this->masuk('BEN-000001');
        $this->post(route('bendahara.simpanan.konfirmasi'), [
            'anggota_ids' => [$a1->id],
            'bulan_periode' => $bulan,
        ])->assertStatus(302);

        $a1->update(['status' => 'resign']);
        $this->post(route('bendahara.simpanan.batalkan', $a1->id), [
            'bulan_periode' => $bulan,
            'alasan' => 'Coba rollback anggota resign',
        ])->assertSessionHasErrors('alasan');

        $this->masuk('ADM-000001');
        $this->post(route('bendahara.simpanan.batalkan', $a1->id), [
            'bulan_periode' => $bulan,
            'alasan' => 'Akses tidak sah',
        ])->assertStatus(403);
    }
}
