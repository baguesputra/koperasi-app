<?php

namespace Tests\Feature;

use App\Models\Angsuran;
use App\Models\AngsuranPercepatan;
use App\Models\PengajuanPenghapusanPinjaman;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class DataPinjamanDrawerTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_status_diajukan_dan_approved_ketua_menyertakan_simulasi_rekening_timeline_serta_aksi_sesuai_role(): void
    {
        $anggota = $this->buatAnggota('TOP-910001');
        $ketua = User::where('no_karyawan', 'KET-000001')->firstOrFail();

        $pDiajukan = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 1_200_000,
            'nominal_diminta' => 1_200_000,
            'tenor_bulan' => 4,
            'tenor_diminta' => 4,
            'persentase_bunga' => 1.0,
            'keperluan' => 'Modal usaha keluarga',
            'snapshot_bank' => 'BCA',
            'snapshot_no_rekening' => '8800112233',
            'snapshot_atas_nama' => $anggota->nama,
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
        ]);

        $pKetua = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 900_000,
            'nominal_diminta' => 1_000_000,
            'tenor_bulan' => 3,
            'tenor_diminta' => 3,
            'nominal_disetujui_bendahara' => 900_000,
            'tenor_disetujui_bendahara' => 3,
            'nominal_disetujui' => 900_000,
            'tenor_disetujui' => 3,
            'persentase_bunga' => 1.0,
            'keperluan' => 'Biaya sekolah',
            'snapshot_bank' => 'Mandiri',
            'snapshot_no_rekening' => '13700998877',
            'snapshot_atas_nama' => $anggota->nama,
            'status' => 'approved_ketua',
            'tanggal_pengajuan' => now()->subDay(),
            'disetujui_ketua_pada' => now(),
            'catatan_admin' => 'Berkas lengkap oleh Sekretaris',
            'catatan_bendahara' => 'Kas mencukupi oleh Bendahara',
            'catatan_ketua' => 'Disetujui Ketua untuk pencairan akhir bulan',
        ]);

        // Sekretaris melihat pinjaman 'diajukan' beserta simulasi, rekening, timeline & tombol aksi Sekretaris
        $this->masuk('ADM-000001');
        $this->get(route('pinjaman.index', ['status' => 'diajukan']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Pinjaman/Index')
                ->where('pinjaman.data.0.id', $pDiajukan->id)
                ->has('pinjaman.data.0.simulasi_angsuran', 4)
                ->has('pinjaman.data.0.timeline_verifikasi', 5)
                ->where('pinjaman.data.0.rekening.bank', 'BCA')
                ->where('pinjaman.data.0.rekening.no_rekening', '8800112233')
                ->where('pinjaman.data.0.aksi_persetujuan.label', 'Verifikasi di Pusat Persetujuan')
            );

        // Bendahara melihat 'approved_ketua' dengan jejak nominal & aksi Cairkan Dana
        $this->masuk('BEN-000001');
        $this->get(route('pinjaman.index', ['status' => 'approved_ketua']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pinjaman.data.0.id', $pKetua->id)
                ->where('pinjaman.data.0.nominal_diminta', 1_000_000)
                ->where('pinjaman.data.0.nominal_disetujui', 900_000)
                ->where('pinjaman.data.0.aksi_persetujuan.label', 'Cairkan Dana')
            );

        // Pengawas tetap view-only (aksi_persetujuan null)
        $this->masuk('WAS-000001');
        $this->get(route('pinjaman.index', ['status' => 'diajukan']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pinjaman.data.0.aksi_persetujuan', null)
            );
        $this->assertNotNull($ketua);
    }

    public function test_status_ditolak_menyertakan_info_penolakan_akurat(): void
    {
        $anggota = $this->buatAnggota('TOP-910002');
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 3_000_000,
            'nominal_diminta' => 3_000_000,
            'tenor_bulan' => 6,
            'tenor_diminta' => 6,
            'persentase_bunga' => 1.5,
            'keperluan' => 'Renovasi rumah',
            'snapshot_bank' => 'BRI',
            'snapshot_no_rekening' => '0011223344',
            'snapshot_atas_nama' => $anggota->nama,
            'status' => 'ditolak',
            'tanggal_pengajuan' => now()->subDays(3),
            'catatan_admin' => 'Diteruskan Sekretaris',
            'catatan_bendahara' => 'Kas operasional bulan ini diprioritaskan pinjaman darurat',
        ]);

        $this->masuk('ADM-000001');
        $this->get(route('pinjaman.index', ['status' => 'ditolak']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pinjaman.data.0.id', $pinjaman->id)
                ->where('pinjaman.data.0.info_penolakan.tahap', 'Bendahara')
                ->where('pinjaman.data.0.info_penolakan.alasan', 'Kas operasional bulan ini diprioritaskan pinjaman darurat')
            );

        $this->get(route('pinjaman.show', $pinjaman))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Pinjaman/Show')
                ->where('pinjaman.info_penolakan.tahap', 'Bendahara')
            );
    }

    public function test_status_aktif_dengan_percepatan_memakai_jadwal_aktif_bukan_angsuran_digantikan(): void
    {
        $anggota = $this->buatAnggota('TOP-910003');
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nomor_dokumen' => '010/KOP-PJM/XI/2026',
            'nominal' => 1_200_000,
            'tenor_bulan' => 4,
            'persentase_bunga' => 1.0,
            'keperluan' => 'Uji percepatan jadwal',
            'status' => 'aktif',
            'tanggal_pengajuan' => now()->subMonths(2),
            'tanggal_pencairan' => now()->subMonths(2),
        ]);

        // Cicilan 1 lunas, cicilan 2-4 digantikan tenor baru (total sisa pokok = 900.000 menjadi 2x cicilan baru)
        Angsuran::create([
            'pinjaman_id' => $pinjaman->id,
            'cicilan_ke' => 1,
            'nominal_pokok' => 300_000,
            'nominal_bunga' => 12_000,
            'total_bayar' => 312_000,
            'status' => 'lunas',
            'tanggal_jatuh_tempo' => now()->subMonth(),
            'tanggal_konfirmasi_bayar' => now()->subMonth(),
        ]);

        foreach ([2, 3, 4] as $ke) {
            Angsuran::create([
                'pinjaman_id' => $pinjaman->id,
                'cicilan_ke' => $ke,
                'nominal_pokok' => 300_000,
                'nominal_bunga' => 12_000,
                'total_bayar' => 312_000,
                'status' => 'digantikan',
                'tanggal_jatuh_tempo' => now()->addMonths($ke - 2),
            ]);
        }

        $pp = PengajuanPercepatan::create([
            'pinjaman_id' => $pinjaman->id,
            'tipe' => 'percepat',
            'tenor_lama' => 4,
            'tenor_baru' => 3,
            'sisa_pokok_saat_approval' => 900_000,
            'nominal_final' => 900_000,
            'bulan_berlaku' => 'bulan_ini',
            'keterangan' => 'Ingin lebih cepat lunas',
            'status' => 'aktif',
            'tanggal_pengajuan' => now()->subDays(5),
        ]);

        AngsuranPercepatan::create([
            'pengajuan_percepatan_id' => $pp->id,
            'cicilan_ke' => 2,
            'nominal_pokok' => 450_000,
            'nominal_bunga' => 9_000,
            'total_bayar' => 459_000,
            'status' => 'belum_bayar',
            'tanggal_jatuh_tempo' => now()->endOfMonth(),
        ]);
        AngsuranPercepatan::create([
            'pengajuan_percepatan_id' => $pp->id,
            'cicilan_ke' => 3,
            'nominal_pokok' => 450_000,
            'nominal_bunga' => 4_500,
            'total_bayar' => 454_500,
            'status' => 'belum_bayar',
            'tanggal_jatuh_tempo' => now()->addMonth()->endOfMonth(),
        ]);

        $this->masuk('ADM-000001');
        $this->get(route('pinjaman.index', ['status' => 'aktif']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pinjaman.data.0.id', $pinjaman->id)
                ->where('pinjaman.data.0.total_cicilan_aktif', 3)
                ->where('pinjaman.data.0.lunas_cicilan_aktif', 1)
                ->where('pinjaman.data.0.sisa_cicilan', 2)
                ->where('pinjaman.data.0.sisa_pokok', 900_000)
                ->where('pinjaman.data.0.percepatan_aktif.tipe_label', 'Percepat Pelunasan')
                ->has('pinjaman.data.0.angsuran', 3)
                ->where('pinjaman.data.0.angsuran.1.is_percepatan', true)
                ->has('pinjaman.data.0.angsuran_digantikan', 3)
            );
    }

    public function test_status_menunggak_memuat_rincian_tunggakan_dan_riwayat_tolak_penghapusan(): void
    {
        $anggota = $this->buatAnggota('TOP-910004');
        $ketua = User::where('no_karyawan', 'KET-000001')->firstOrFail();
        $was3 = User::where('no_karyawan', 'WAS-000003')->firstOrFail();

        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 800_000,
            'tenor_bulan' => 2,
            'persentase_bunga' => 1.0,
            'status' => 'aktif',
            'tanggal_pengajuan' => now()->subMonths(3),
            'tanggal_pencairan' => now()->subMonths(3),
        ]);

        Angsuran::create([
            'pinjaman_id' => $pinjaman->id,
            'cicilan_ke' => 1,
            'nominal_pokok' => 400_000,
            'nominal_bunga' => 8_000,
            'total_bayar' => 408_000,
            'status' => 'belum_bayar',
            'tanggal_jatuh_tempo' => now()->subMonth(),
        ]);
        Angsuran::create([
            'pinjaman_id' => $pinjaman->id,
            'cicilan_ke' => 2,
            'nominal_pokok' => 400_000,
            'nominal_bunga' => 4_000,
            'total_bayar' => 404_000,
            'status' => 'belum_bayar',
            'tanggal_jatuh_tempo' => now()->subDays(3),
        ]);

        PengajuanPenghapusanPinjaman::create([
            'pinjaman_id' => $pinjaman->id,
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $ketua->id,
            'tanggal_pengajuan' => now()->subDay()->toDateString(),
            'alasan' => 'Usulan awal hapus pinjaman',
            'nominal_pinjaman' => 800_000,
            'total_pokok_terbayar' => 0,
            'total_bunga_terbayar' => 0,
            'sisa_cicilan' => 2,
            'sisa_pokok' => 800_000,
            'sisa_bunga' => 12_000,
            'sisa_total_tagihan' => 812_000,
            'status' => 'ditolak',
            'ditolak_oleh_user_id' => $was3->id,
            'ditolak_pada' => now(),
            'tahap_ditolak' => 'pengawas_3',
            'catatan_tolak' => 'Terus ditagih',
        ]);

        $this->masuk('BEN-000001');
        $this->get(route('pinjaman.index', ['status' => 'menunggak']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pinjaman.data.0.id', $pinjaman->id)
                ->where('pinjaman.data.0.tunggakan.jumlah_terlambat', 2)
                ->where('pinjaman.data.0.tunggakan.nominal_tunggakan', 812_000)
                ->where('pinjaman.data.0.angsuran.0.is_terlambat', true)
                ->where('pinjaman.data.0.penghapusan_ditolak_terakhir.tahap_ditolak_label', 'Pengawas 3')
                ->where('pinjaman.data.0.penghapusan_ditolak_terakhir.catatan_tolak', 'Terus ditagih')
                ->where('pinjaman.data.0.aksi_persetujuan.label', 'Konfirmasi Angsuran')
            );
    }

    public function test_status_lunas_dan_dihapusbukukan_memuat_penyelesaian_serta_dokumen_kop_phps(): void
    {
        $anggotaLunas = $this->buatAnggota('TOP-910005');
        $pinjamanLunas = Pinjaman::create([
            'anggota_id' => $anggotaLunas->id,
            'pengaju_user_id' => $anggotaLunas->user_id,
            'nominal' => 500_000,
            'tenor_bulan' => 1,
            'persentase_bunga' => 1.0,
            'status' => 'lunas',
            'tanggal_pengajuan' => now()->subMonths(2),
            'tanggal_pencairan' => now()->subMonths(2),
        ]);
        Angsuran::create([
            'pinjaman_id' => $pinjamanLunas->id,
            'cicilan_ke' => 1,
            'nominal_pokok' => 500_000,
            'nominal_bunga' => 5_000,
            'total_bayar' => 505_000,
            'status' => 'lunas',
            'tanggal_jatuh_tempo' => now()->subMonth(),
            'tanggal_konfirmasi_bayar' => now()->subMonth(),
        ]);

        $anggotaHapus = $this->buatAnggota('TOP-910006');
        $ketua = User::where('no_karyawan', 'KET-000001')->firstOrFail();
        $was3 = User::where('no_karyawan', 'WAS-000003')->firstOrFail();
        $was2 = User::where('no_karyawan', 'WAS-000002')->firstOrFail();
        $was1 = User::where('no_karyawan', 'WAS-000001')->firstOrFail();

        $pinjamanHapus = Pinjaman::create([
            'anggota_id' => $anggotaHapus->id,
            'pengaju_user_id' => $anggotaHapus->user_id,
            'nominal' => 1_000_000,
            'tenor_bulan' => 2,
            'persentase_bunga' => 1.0,
            'status' => 'dihapusbukukan',
            'tanggal_pengajuan' => now()->subMonths(4),
            'tanggal_pencairan' => now()->subMonths(4),
        ]);
        Angsuran::create([
            'pinjaman_id' => $pinjamanHapus->id,
            'cicilan_ke' => 1,
            'nominal_pokok' => 500_000,
            'nominal_bunga' => 10_000,
            'total_bayar' => 510_000,
            'status' => 'lunas',
            'tanggal_jatuh_tempo' => now()->subMonths(3),
            'tanggal_konfirmasi_bayar' => now()->subMonths(3),
        ]);
        Angsuran::create([
            'pinjaman_id' => $pinjamanHapus->id,
            'cicilan_ke' => 2,
            'nominal_pokok' => 500_000,
            'nominal_bunga' => 5_000,
            'total_bayar' => 505_000,
            'status' => 'dihapusbukukan',
            'tanggal_jatuh_tempo' => now()->subMonths(2),
        ]);

        PengajuanPenghapusanPinjaman::create([
            'pinjaman_id' => $pinjamanHapus->id,
            'anggota_id' => $anggotaHapus->id,
            'nomor_dokumen' => '005/KOP-PHPS/XI/2026',
            'pengaju_user_id' => $ketua->id,
            'tanggal_pengajuan' => now()->subWeek()->toDateString(),
            'alasan' => 'Karyawan mengundurkan diri tanpa jejak.',
            'nominal_pinjaman' => 1_000_000,
            'total_pokok_terbayar' => 500_000,
            'total_bunga_terbayar' => 10_000,
            'sisa_cicilan' => 1,
            'sisa_pokok' => 500_000,
            'sisa_bunga' => 5_000,
            'sisa_total_tagihan' => 505_000,
            'status' => 'disetujui',
            'approved_pengawas_3_by' => $was3->id,
            'approved_pengawas_3_at' => now()->subDays(3),
            'catatan_pengawas_3' => 'Setuju P3',
            'approved_pengawas_2_by' => $was2->id,
            'approved_pengawas_2_at' => now()->subDays(2),
            'catatan_pengawas_2' => 'Setuju P2',
            'approved_pengawas_1_by' => $was1->id,
            'approved_pengawas_1_at' => now()->subDay(),
            'catatan_pengawas_1' => 'Disetujui final',
            'disetujui_pada' => now()->subDay(),
        ]);

        $this->masuk('WAS-000001');
        $this->get(route('pinjaman.index', ['status' => 'lunas']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pinjaman.data.0.id', $pinjamanLunas->id)
                ->where('pinjaman.data.0.penyelesaian.metode', 'Angsuran Reguler Penuh')
                ->where('pinjaman.data.0.total_pokok_terbayar', 500_000)
                ->where('pinjaman.data.0.total_bunga_terbayar', 5_000)
            );

        $this->get(route('pinjaman.index', ['status' => 'dihapusbukukan']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pinjaman.data.0.id', $pinjamanHapus->id)
                ->where('pinjaman.data.0.penghapusan_disetujui.nomor_dokumen', '005/KOP-PHPS/XI/2026')
                ->where('pinjaman.data.0.penghapusan_disetujui.sisa_pokok', 500_000)
                ->has('pinjaman.data.0.penghapusan_disetujui.timeline', 4)
                ->where('pinjaman.data.0.angsuran.1.status', 'dihapusbukukan')
            );
    }

    public function test_halaman_pinjaman_index_konstan_tanpa_n_plus_1_lintas_status(): void
    {
        $anggota = $this->buatAnggota('TOP-910007');
        $this->masuk('ADM-000001');

        $buat = function (string $status) use ($anggota): Pinjaman {
            $p = Pinjaman::create([
                'anggota_id' => $anggota->id,
                'pengaju_user_id' => $anggota->user_id,
                'nominal' => 600_000,
                'tenor_bulan' => 2,
                'persentase_bunga' => 1.0,
                'status' => $status,
                'tanggal_pengajuan' => now()->subMonth(),
                'tanggal_pencairan' => in_array($status, ['aktif', 'lunas', 'dihapusbukukan'], true) ? now()->subMonth() : null,
            ]);
            if (in_array($status, ['aktif', 'lunas', 'dihapusbukukan'], true)) {
                $p->angsuran()->create([
                    'cicilan_ke' => 1,
                    'nominal_pokok' => 600_000,
                    'nominal_bunga' => 6_000,
                    'total_bayar' => 606_000,
                    'status' => $status === 'lunas' ? 'lunas' : ($status === 'dihapusbukukan' ? 'dihapusbukukan' : 'belum_bayar'),
                    'tanggal_jatuh_tempo' => now()->subDays(2),
                ]);
            }

            return $p;
        };

        $buat('aktif');

        // Warm-up cache izin/daftar cabang sebelum pengukuran query per-request
        $this->get(route('pinjaman.index'))->assertOk();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('pinjaman.index'))->assertOk();
        $queriesSatu = count(DB::getQueryLog());
        DB::disableQueryLog();

        foreach (['diajukan', 'approved_admin', 'approved_bendahara', 'approved_ketua', 'lunas', 'ditolak', 'dihapusbukukan'] as $st) {
            $buat($st);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('pinjaman.index'))->assertOk();
        $queriesBanyak = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual($queriesSatu, $queriesBanyak, "Query N+1 terdeteksi: {$queriesSatu} vs {$queriesBanyak}");
        $this->assertLessThanOrEqual(20, $queriesBanyak);
    }
}
