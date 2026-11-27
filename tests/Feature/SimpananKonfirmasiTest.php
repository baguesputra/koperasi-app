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
        // Default test non-terlambat dimulai bulan berjalan agar fixture uji lama independen dari tanggal kalender.
        config(['koperasi.simpanan_terlambat_mulai' => now()->format('Y-m')]);
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

    public function test_simpanan_terlambat_masuk_tab_terlambat_bulan_berjalan_dan_konfirmasi_lintas_periode(): void
    {
        $bulanIni = now()->format('Y-m');
        $bulanSatuLalu = now()->startOfMonth()->subMonthsNoOverflow(1)->format('Y-m');
        $bulanDuaLalu = now()->startOfMonth()->subMonthsNoOverflow(2)->format('Y-m');

        config(['koperasi.simpanan_terlambat_mulai' => $bulanDuaLalu]);

        $a1 = $this->buatAnggota('TOP-910008'); // tanggal_jadi_anggota = 2 tahun lalu
        $userBaru = User::create([
            'no_karyawan' => 'TOP-910009',
            'name' => 'Anggota Baru',
            'email' => 'top-910009@t.id',
            'password' => bcrypt('TOP-910009'),
            'harus_ganti_password' => false,
        ]);
        $a2 = Anggota::create([
            'user_id' => $userBaru->id,
            'no_anggota' => 'ANG-SC-0009',
            'nama' => 'Anggota Baru Bulan Ini',
            'cabang' => 'Banjarmasin',
            'unit_bisnis' => 'Ops',
            'jabatan' => 'staff',
            'tanggal_mulai_kerja' => now(),
            'tanggal_jadi_anggota' => now(),
            'status' => 'aktif',
        ]);

        $this->masuk('BEN-000001');

        // 1. Bulan berjalan: a1 tampil di belumSimpanan DAN punya 2 tagihan terlambat; a2 tidak masuk terlambat
        $propsKini = $this->get(route('bendahara.simpanan.index'))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertContains($a1->id, array_column($propsKini['belumSimpanan'], 'id'));
        $terlambatIds = array_column($propsKini['terlambatSimpanan'], 'id');
        $this->assertContains("{$a1->id}|{$bulanDuaLalu}", $terlambatIds);
        $this->assertContains("{$a1->id}|{$bulanSatuLalu}", $terlambatIds);
        $this->assertNotContains($a2->id, array_column($propsKini['terlambatSimpanan'], 'anggota_id'));

        // 2. Bulan lalu (Opsi 1): belumSimpanan & terlambatSimpanan kosong, dipindahKeBulanIni menghitung tunggakan bulan tsb
        $propsLalu = $this->get(route('bendahara.simpanan.index', ['bulan' => $bulanSatuLalu]))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertSame([], $propsLalu['belumSimpanan']);
        $this->assertSame([], $propsLalu['terlambatSimpanan']);
        $this->assertGreaterThanOrEqual(1, $propsLalu['dipindahKeBulanIni']['jumlah']);

        // 3. Konfirmasi lintas periode dari tab Terlambat via `items`
        $this->post(route('bendahara.simpanan.konfirmasi'), [
            'items' => [
                ['anggota_id' => $a1->id, 'bulan_periode' => $bulanDuaLalu],
                ['anggota_id' => $a1->id, 'bulan_periode' => $bulanSatuLalu],
            ],
        ])->assertStatus(302)->assertSessionHasNoErrors();

        $this->assertDatabaseHas('simpanan', ['anggota_id' => $a1->id, 'jenis' => 'wajib', 'bulan_periode' => $bulanDuaLalu]);
        $this->assertDatabaseHas('simpanan', ['anggota_id' => $a1->id, 'jenis' => 'dana_sosial', 'bulan_periode' => $bulanDuaLalu]);
        $this->assertDatabaseHas('simpanan', ['anggota_id' => $a1->id, 'jenis' => 'wajib', 'bulan_periode' => $bulanSatuLalu]);
        $this->assertDatabaseMissing('simpanan', ['anggota_id' => $a1->id, 'jenis' => 'wajib', 'bulan_periode' => $bulanIni]);

        $propsSesudah = $this->get(route('bendahara.simpanan.index'))
            ->assertOk()
            ->viewData('page')['props'];

        $terlambatIdsSesudah = array_column($propsSesudah['terlambatSimpanan'], 'id');
        $this->assertNotContains("{$a1->id}|{$bulanDuaLalu}", $terlambatIdsSesudah);
        $this->assertNotContains("{$a1->id}|{$bulanSatuLalu}", $terlambatIdsSesudah);
        $this->assertContains($a1->id, array_column($propsSesudah['belumSimpanan'], 'id'));

        $sudahMap = collect($propsSesudah['sudahSimpanan'])->keyBy('key');
        $this->assertTrue($sudahMap->has("{$a1->id}|{$bulanDuaLalu}"));
        $this->assertTrue($sudahMap->has("{$a1->id}|{$bulanSatuLalu}"));
        $this->assertTrue($sudahMap->get("{$a1->id}|{$bulanSatuLalu}")['dari_bulan_lain']);

        // 4. Rollback periode terlambat mengembalikannya ke Tab Terlambat
        $this->post(route('bendahara.simpanan.batalkan', $a1->id), [
            'bulan_periode' => $bulanSatuLalu,
            'alasan' => 'Salah konfirmasi simpanan terlambat',
        ])->assertStatus(302)->assertSessionHasNoErrors();

        $propsRollback = $this->get(route('bendahara.simpanan.index'))
            ->assertOk()
            ->viewData('page')['props'];
        $this->assertContains("{$a1->id}|{$bulanSatuLalu}", array_column($propsRollback['terlambatSimpanan'], 'id'));
    }

    public function test_validasi_tunggakan_urut_periode_mendatang_dan_rollback_berurutan(): void
    {
        $bulanIni = now()->format('Y-m');
        $bulanSatuLalu = now()->startOfMonth()->subMonthsNoOverflow(1)->format('Y-m');
        $bulanDuaLalu = now()->startOfMonth()->subMonthsNoOverflow(2)->format('Y-m');
        $bulanDepan = now()->startOfMonth()->addMonthNoOverflow(1)->format('Y-m');

        config(['koperasi.simpanan_terlambat_mulai' => $bulanDuaLalu]);

        $a1 = $this->buatAnggota('TOP-910099');
        $this->masuk('BEN-000001');

        // 1. Konfirmasi bulan berjalan saat ada tunggakan di Tab Terlambat harus ditolak
        $this->post(route('bendahara.simpanan.konfirmasi'), [
            'anggota_ids' => [$a1->id],
            'bulan_periode' => $bulanIni,
        ])->assertStatus(302)->assertSessionHasErrors('konfirmasi');
        $this->assertDatabaseMissing('simpanan', ['anggota_id' => $a1->id, 'jenis' => 'wajib', 'bulan_periode' => $bulanIni]);

        // Props belumSimpanan tetap memuat a1 tetapi bisa_dipilih = false
        $props = $this->get(route('bendahara.simpanan.index'))->assertOk()->viewData('page')['props'];
        $barisA1 = collect($props['belumSimpanan'])->firstWhere('id', $a1->id);
        $this->assertNotNull($barisA1);
        $this->assertFalse($barisA1['bisa_dipilih']);
        $this->assertTrue($barisA1['ada_tunggakan_terlambat']);

        // 2. Melompati bulanDuaLalu (konfirmasi bulanSatuLalu saja) ditolak
        $this->post(route('bendahara.simpanan.konfirmasi'), [
            'items' => [['anggota_id' => $a1->id, 'bulan_periode' => $bulanSatuLalu]],
        ])->assertStatus(302)->assertSessionHasErrors('konfirmasi');

        // 3. Periode mendatang ditolak
        $this->post(route('bendahara.simpanan.konfirmasi'), [
            'anggota_ids' => [$a1->id],
            'bulan_periode' => $bulanDepan,
        ])->assertStatus(302)->assertSessionHasErrors('konfirmasi');

        // 4. Konfirmasi berurutan [bulanDuaLalu, bulanSatuLalu] lalu bulanIni berhasil
        $this->post(route('bendahara.simpanan.konfirmasi'), [
            'items' => [
                ['anggota_id' => $a1->id, 'bulan_periode' => $bulanDuaLalu],
                ['anggota_id' => $a1->id, 'bulan_periode' => $bulanSatuLalu],
            ],
        ])->assertStatus(302)->assertSessionHasNoErrors();

        $this->post(route('bendahara.simpanan.konfirmasi'), [
            'anggota_ids' => [$a1->id],
            'bulan_periode' => $bulanIni,
        ])->assertStatus(302)->assertSessionHasNoErrors();

        // 5. Rollback periode lebih lama ditolak selama periode lebih baru masih ada
        $this->post(route('bendahara.simpanan.batalkan', $a1->id), [
            'bulan_periode' => $bulanSatuLalu,
            'alasan' => 'Coba batalkan bulan lama',
        ])->assertSessionHasErrors('alasan');
        $this->assertDatabaseHas('simpanan', ['anggota_id' => $a1->id, 'jenis' => 'wajib', 'bulan_periode' => $bulanSatuLalu]);
    }
}
