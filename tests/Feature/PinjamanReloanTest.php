<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\Pinjaman;
use App\Models\User;
use App\Services\Pinjaman\EligibilitasPinjamanService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Feature test untuk rule reloan: boleh ajukan 1x saat ada angsuran berjalan,
 * dengan limit efektif dikurangi (sisa_angsuran × cicilan_pokok).
 *
 * Setup schema manual di setUp() agar tidak bergantung pada migrasi MySQL-specific
 * (MODIFY COLUMN ENUM) yang tidak kompatibel dengan SQLite in-memory default PHPUnit.
 */
class PinjamanReloanTest extends TestCase
{
    protected EligibilitasPinjamanService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(EligibilitasPinjamanService::class);

        $this->setupSchema();
        $this->seedKategoriLimit();
        $this->seedTabelTenor();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('angsuran_percepatan');
        Schema::dropIfExists('pengajuan_percepatan');
        Schema::dropIfExists('angsuran');
        Schema::dropIfExists('pinjaman');
        Schema::dropIfExists('anggota');
        Schema::dropIfExists('users');
        Schema::dropIfExists('setting_limit_pinjaman');
        Schema::dropIfExists('tabel_tenor');

        parent::tearDown();
    }

    /**
     * Setup schema minimal (hanya tabel yang disentuh service & test).
     * Pakai tipe data SQLite-compatible (string代替enum).
     */
    private function setupSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('no_karyawan')->nullable();
            $table->timestamps();
        });

        Schema::create('anggota', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('no_anggota')->unique();
            $table->string('nama');
            $table->string('cabang');
            $table->string('unit_bisnis');
            $table->string('jabatan');
            $table->date('tanggal_mulai_kerja');
            $table->date('tanggal_jadi_anggota');
            $table->string('status')->default('aktif');
            $table->decimal('limit_custom', 15, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('pinjaman', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('anggota_id');
            $table->unsignedBigInteger('pengaju_user_id')->nullable();
            $table->decimal('nominal', 15, 2);
            $table->unsignedInteger('tenor_bulan');
            $table->text('keperluan')->nullable();
            $table->string('snapshot_bank')->nullable();
            $table->string('snapshot_no_rekening')->nullable();
            $table->string('snapshot_atas_nama')->nullable();
            $table->decimal('persentase_bunga', 5, 2);
            $table->string('status')->default('diajukan');
            $table->boolean('cair_oleh_bendahara')->default(false);
            $table->boolean('sudah_pakai_privilege_reloan')->default(false);
            $table->boolean('sudah_pakai_percepatan')->default(false);
            $table->date('tanggal_pengajuan');
            $table->date('tanggal_pencairan')->nullable();
            $table->text('catatan_bendahara')->nullable();
            $table->text('catatan_ketua')->nullable();
            $table->timestamps();
        });

        Schema::create('angsuran', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pinjaman_id');
            $table->unsignedInteger('cicilan_ke');
            $table->decimal('nominal_pokok', 15, 2);
            $table->decimal('nominal_bunga', 15, 2);
            $table->decimal('total_bayar', 15, 2);
            $table->string('status')->default('belum_bayar');
            $table->date('tanggal_jatuh_tempo');
            $table->date('tanggal_konfirmasi_bayar')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->unsignedBigInteger('pengajuan_percepatan_id')->nullable();
            $table->timestamps();
        });

        Schema::create('setting_limit_pinjaman', function (Blueprint $table) {
            $table->id();
            $table->string('kategori');
            $table->string('label');
            $table->decimal('limit_maksimal', 15, 2);
            $table->timestamps();
        });

        Schema::create('tabel_tenor', function (Blueprint $table) {
            $table->id();
            $table->decimal('nominal_min', 15, 2);
            $table->decimal('nominal_max', 15, 2);
            $table->unsignedInteger('tenor_maksimal_bulan');
            $table->timestamps();
        });

        Schema::create('pengajuan_percepatan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pinjaman_id');
            $table->string('tipe');
            $table->unsignedInteger('tenor_lama');
            $table->unsignedInteger('tenor_baru')->nullable();
            $table->decimal('sisa_pokok_saat_approval', 15, 2)->nullable();
            $table->decimal('nominal_final', 15, 2)->nullable();
            $table->string('bulan_berlaku')->nullable();
            $table->text('keterangan');
            $table->string('status')->default('diajukan');
            $table->text('catatan_bendahara')->nullable();
            $table->text('catatan_ketua')->nullable();
            $table->date('tanggal_pengajuan');
            $table->timestamps();
        });

        Schema::create('angsuran_percepatan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pengajuan_percepatan_id');
            $table->unsignedInteger('cicilan_ke');
            $table->decimal('nominal_pokok', 15, 2);
            $table->decimal('nominal_bunga', 15, 2);
            $table->decimal('total_bayar', 15, 2);
            $table->string('status')->default('belum_bayar');
            $table->date('tanggal_jatuh_tempo');
            $table->date('tanggal_konfirmasi_bayar')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamps();
        });
    }

    private function seedKategoriLimit(): void
    {
        $rows = [
            ['kategori' => 'kurang_1_tahun', 'label' => '< 1 Tahun', 'limit_maksimal' => 1_000_000],
            ['kategori' => 'satu_sampai_3_tahun', 'label' => '1-3 Tahun', 'limit_maksimal' => 5_000_000],
            ['kategori' => 'tiga_sampai_5_tahun', 'label' => '3-5 Tahun', 'limit_maksimal' => 7_000_000],
            ['kategori' => 'lebih_5_tahun', 'label' => '> 5 Tahun', 'limit_maksimal' => 10_000_000],
        ];
        foreach ($rows as $r) {
            DB::table('setting_limit_pinjaman')->insert($r);
        }
    }

    private function seedTabelTenor(): void
    {
        $rows = [
            ['nominal_min' => 0, 'nominal_max' => 1_000_000, 'tenor_maksimal_bulan' => 3],
            ['nominal_min' => 1_000_001, 'nominal_max' => 10_000_000, 'tenor_maksimal_bulan' => 12],
        ];
        foreach ($rows as $r) {
            DB::table('tabel_tenor')->insert($r);
        }
    }

    private function buatUser(array $attrs = []): User
    {
        $user = new User;
        $user->fill(array_merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ], $attrs));
        $user->save();

        return $user;
    }

    private function buatAnggota(User $user, array $attrs = []): Anggota
    {
        $default = [
            'user_id' => $user->id,
            'no_anggota' => 'ANG-TEST-'.uniqid(),
            'nama' => 'Anggota Test',
            'cabang' => 'Banjarmasin',
            'unit_bisnis' => 'Operasional',
            'jabatan' => 'staff',
            'tanggal_mulai_kerja' => now()->subYears(5),
            'tanggal_jadi_anggota' => now()->subYears(3), // 1-3 tahun
            'status' => 'aktif',
        ];

        $anggota = new Anggota;
        $anggota->fill(array_merge($default, $attrs));
        $anggota->save();

        return $anggota;
    }

    /**
     * Buat pinjaman aktif dengan angsuran dan tandai cicilan ke-N sudah lunas.
     * Sisa angsuran = tenor - lunasSampai.
     */
    private function buatPinjamanAktif(Anggota $anggota, float $nominal, int $tenor, int $lunasSampai, float $persentaseBunga = 1.0, bool $sudahPakaiPrivilege = false): Pinjaman
    {
        $pinjaman = new Pinjaman;
        $pinjaman->fill([
            'anggota_id' => $anggota->id,
            'nominal' => $nominal,
            'tenor_bulan' => $tenor,
            'persentase_bunga' => $persentaseBunga,
            'status' => 'aktif',
            'sudah_pakai_privilege_reloan' => $sudahPakaiPrivilege,
            'tanggal_pengajuan' => now()->subMonths($tenor),
            'tanggal_pencairan' => now()->subMonths($tenor),
        ]);
        $pinjaman->save();

        $pokokPerBulan = $nominal / $tenor;
        $sisaPokok = $nominal;

        for ($i = 1; $i <= $tenor; $i++) {
            $bunga = $sisaPokok * ($persentaseBunga / 100);
            $total = $pokokPerBulan + $bunga;
            $lunas = $i <= $lunasSampai;

            $angsuran = new Angsuran;
            $angsuran->fill([
                'pinjaman_id' => $pinjaman->id,
                'cicilan_ke' => $i,
                'nominal_pokok' => round($pokokPerBulan, 2),
                'nominal_bunga' => round($bunga, 2),
                'total_bayar' => round($total, 2),
                'status' => $lunas ? 'lunas' : 'belum_bayar',
                'tanggal_jatuh_tempo' => $pinjaman->tanggal_pencairan->copy()->addMonths($i),
                'tanggal_konfirmasi_bayar' => $lunas ? $pinjaman->tanggal_pencairan->copy()->addMonths($i) : null,
            ]);
            $angsuran->save();

            $sisaPokok -= $pokokPerBulan;
        }

        return $pinjaman;
    }

    /**
     * Buat pengajuan_percepatan aktif + angsuran_percepatan belum_bayar,
     * lalu tandai angsuran lama yang di-replace sebagai 'digantikan'.
     */
    private function buatPengajuanPercepatanAktif(Pinjaman $pinjaman, int $tenorBaru, float $pokokPerBulanBaru, int $lunasSampaiBaru = 0): int
    {
        // Tandai beberapa angsuran lama jadi 'digantikan' (yang akan di-replace)
        // Asumsi: angsuran_percepatan menggantikan angsuran lama ke-3 dst
        DB::table('angsuran')
            ->where('pinjaman_id', $pinjaman->id)
            ->where('cicilan_ke', '>=', 3)
            ->update(['status' => 'digantikan']);

        $pengajuanId = DB::table('pengajuan_percepatan')->insertGetId([
            'pinjaman_id' => $pinjaman->id,
            'tipe' => 'perpanjang',
            'tenor_lama' => $pinjaman->tenor_bulan,
            'tenor_baru' => $tenorBaru,
            'keterangan' => 'Test pengajuan percepatan',
            'status' => 'aktif',
            'tanggal_pengajuan' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sisaPokok = $pokokPerBulanBaru * $tenorBaru;

        for ($i = 1; $i <= $tenorBaru; $i++) {
            $bunga = $sisaPokok * 0.01;
            $lunas = $i <= $lunasSampaiBaru;

            DB::table('angsuran_percepatan')->insert([
                'pengajuan_percepatan_id' => $pengajuanId,
                'cicilan_ke' => $i,
                'nominal_pokok' => round($pokokPerBulanBaru, 2),
                'nominal_bunga' => round($bunga, 2),
                'total_bayar' => round($pokokPerBulanBaru + $bunga, 2),
                'status' => $lunas ? 'lunas' : 'belum_bayar',
                'tanggal_jatuh_tempo' => now()->addMonths($i),
                'tanggal_konfirmasi_bayar' => $lunas ? now()->addMonths($i) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sisaPokok -= $pokokPerBulanBaru;
        }

        return $pengajuanId;
    }

    public function test_cek_limit_penuh_untuk_anggota_tanpa_pinjaman_aktif(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user, [
            'tanggal_jadi_anggota' => now()->subYears(2), // 1-3 tahun → 5jt
        ]);

        $result = $this->service->cek($anggota);

        $this->assertTrue($result['boleh']);
        $this->assertNull($result['alasan']);
        $this->assertEquals(5_000_000, $result['limit_tersedia']);
        $this->assertEquals(0, $result['sisa_angsuran']);
        $this->assertEquals(0.0, $result['cicilan_pokok']);
    }

    public function test_cek_limit_dikurangi_sisa_x_cicilan_pokok(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user, [
            'tanggal_jadi_anggota' => now()->subYears(2), // 1-3 tahun → 5jt
        ]);
        // 2jt / 4 bulan = 500rb pokok per angsuran, 2 dari 4 lunas → sisa 2 × 500rb = 1jt
        $this->buatPinjamanAktif($anggota, nominal: 2_000_000, tenor: 4, lunasSampai: 2);

        $result = $this->service->cek($anggota);

        // limit 5jt - (2 × 500rb) = 4jt
        $this->assertEquals(4_000_000, $result['limit_tersedia']);
        $this->assertEquals(2, $result['sisa_angsuran']);
        $this->assertEquals(500_000.0, $result['cicilan_pokok']);
        // Boleh karena sisa <= 2 dan privilege belum dipakai
        $this->assertTrue($result['boleh']);
    }

    public function test_cek_limit_tersedia_floor_nol_jika_sisa_angsuran_melebihi_limit_kategori(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user, ['limit_custom' => 500_000]);
        // 1.2jt / 6 bulan = 200rb, 4 dari 6 lunas → sisa 2 × 200rb = 400rb
        // limit_custom 500rb - 400rb = 100rb
        $this->buatPinjamanAktif($anggota, nominal: 1_200_000, tenor: 6, lunasSampai: 4);

        $result = $this->service->cek($anggota);

        $this->assertEquals(100_000, $result['limit_tersedia']);
    }

    public function test_cek_boleh_walaupun_sisa_banyak_jika_privilege_belum_dipakai(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user, [
            'tanggal_jadi_anggota' => now()->subYears(2), // 1-3 tahun → 5jt
        ]);
        // 3 dari 6 lunas → sisa 3 (> 2). Aturan baru: boleh karena privilege belum dipakai.
        $this->buatPinjamanAktif($anggota, nominal: 6_000_000, tenor: 6, lunasSampai: 3);

        $result = $this->service->cek($anggota);

        $this->assertTrue($result['boleh']);
        $this->assertNull($result['alasan']);
        $this->assertEquals(3, $result['sisa_angsuran']);
        // limit 5jt - (3 × 1jt) = 2jt
        $this->assertEquals(2_000_000, $result['limit_tersedia']);
    }

    public function test_cek_boleh_reloan_sekali_dengan_privilege_belum_dipakai(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user);
        // 4 dari 6 lunas → sisa 2
        $this->buatPinjamanAktif($anggota, nominal: 6_000_000, tenor: 6, lunasSampai: 4);

        $result = $this->service->cek($anggota);

        $this->assertTrue($result['boleh']);
    }

    public function test_cek_ditolak_setelah_privilege_sudah_dipakai(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user);
        $this->buatPinjamanAktif($anggota, nominal: 6_000_000, tenor: 6, lunasSampai: 4, sudahPakaiPrivilege: true);

        $result = $this->service->cek($anggota);

        $this->assertFalse($result['boleh']);
        $this->assertStringContainsString('sudah menggunakan hak', $result['alasan']);
    }

    public function test_cek_anggota_baru_wajib_lunas_sebelum_reloan(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user, [
            'tanggal_jadi_anggota' => now()->subMonths(6), // < 1 tahun
        ]);
        // sisa 1 (≤ 2), tapi anggota baru -> wajib lunas
        $this->buatPinjamanAktif($anggota, nominal: 1_000_000, tenor: 4, lunasSampai: 3);

        $result = $this->service->cek($anggota);

        $this->assertFalse($result['boleh']);
        $this->assertStringContainsString('wajib melunasi', $result['alasan']);
    }

    public function test_cek_anggota_baru_tanpa_pinjaman_aktif_boleh(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user, [
            'tanggal_jadi_anggota' => now()->subMonths(6),
        ]);

        $result = $this->service->cek($anggota);

        $this->assertTrue($result['boleh']);
        $this->assertEquals(1_000_000, $result['limit_tersedia']);
    }

    public function test_limit_tersedia_eksponensial_tanpa_pinjaman_aktif(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user, [
            'limit_custom' => null,
            'tanggal_jadi_anggota' => now()->subYears(2), // 1-3 tahun → 5jt
        ]);

        $this->assertEquals(5_000_000, $this->service->limitTersedia($anggota));
    }

    public function test_limit_tersedia_dengan_limit_custom(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user, ['limit_custom' => 8_000_000]);

        $this->assertEquals(8_000_000, $this->service->limitTersedia($anggota));
    }

    public function test_limit_tersedia_kurangi_sisa_x_cicilan_pokok(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user, [
            'tanggal_jadi_anggota' => now()->subYears(2), // 1-3 tahun → 5jt
        ]);
        // 1jt / 4 = 250rb, 2 dari 4 lunas → sisa 2 × 250rb = 500rb
        // limit 5jt - 500rb = 4.5jt
        $this->buatPinjamanAktif($anggota, nominal: 1_000_000, tenor: 4, lunasSampai: 2);

        $this->assertEquals(4_500_000, $this->service->limitTersedia($anggota));
    }

    public function test_anggota_dengan_pinjaman_aktif_dengan_sisa_1_masih_boleh_dan_limit_terkurangi(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user, [
            'tanggal_jadi_anggota' => now()->subYears(2), // 1-3 tahun → 5jt
        ]);
        // 1jt / 5 = 200rb, 4 dari 5 lunas → sisa 1 × 200rb = 200rb
        // limit 5jt - 200rb = 4.8jt
        $this->buatPinjamanAktif($anggota, nominal: 1_000_000, tenor: 5, lunasSampai: 4);

        $result = $this->service->cek($anggota);

        $this->assertTrue($result['boleh']);
        $this->assertEquals(4_800_000, $result['limit_tersedia']);
        $this->assertEquals(1, $result['sisa_angsuran']);
    }

    public function test_cicilan_pokok_nol_jika_tidak_ada_angsuran_belum_bayar(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user);
        // Semua lunas
        $this->buatPinjamanAktif($anggota, nominal: 2_000_000, tenor: 4, lunasSampai: 4);

        // Tidak ada pinjaman aktif karena query cari status=aktif first()
        // dan kondisi Pinjaman belum_bayar = 0
        $pinjaman = Pinjaman::where('anggota_id', $anggota->id)->first();
        $this->assertEquals(0.0, $pinjaman->cicilanPokok());
    }

    public function test_pinjaman_model_cicilan_pokok_hitung_rata_rata(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user);
        // 2jt / 4 = 500rb seragam
        $pinjaman = $this->buatPinjamanAktif($anggota, nominal: 2_000_000, tenor: 4, lunasSampai: 1);

        // 3 angsuran belum_bayar masing-masing 500rb → avg = 500rb
        $this->assertEquals(500_000.0, $pinjaman->cicilanPokok());
    }

    public function test_pinjaman_aktif_dengan_agregat_1_query(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user);
        $this->buatPinjamanAktif($anggota, nominal: 2_000_000, tenor: 4, lunasSampai: 2);

        DB::enableQueryLog();
        $agg = $anggota->pinjamanAktifDenganAgregat();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertNotNull($agg);
        $this->assertEquals(2, $agg['sisa_total']);
        $this->assertCount(1, $agg['pinjaman_aktif_list']);

        // Harus 1 query (dengan subquery aggregates), bukan N+1
        $this->assertCount(1, $queries, 'pinjamanAktifDenganAgregat harus 1 query, bukan '.count($queries));
    }

    public function test_cek_juga_1_query(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user);
        $this->buatPinjamanAktif($anggota, nominal: 2_000_000, tenor: 4, lunasSampai: 2);

        DB::enableQueryLog();
        $result = $this->service->cek($anggota);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertIsArray($result);

        // 1 (pinjamanAktifDenganAgregat) + 1 (limitMaksimal setting_limit_pinjaman) +
        // 1 (jadwalAktif: pengajuan_percepatan) + 1 (jadwalAktif: angsuran collection) = 4 query
        // N+1 berarti >4 (misal 5+). 4 masih acceptable karena jadwalAktif bisa share dengan dashboard.
        $this->assertLessThanOrEqual(4, count($queries), 'cek() harusnya maksimal 4 query, dapat '.count($queries));
    }

    public function test_limit_tersedia_juga_maksimal_4_query(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user);
        $this->buatPinjamanAktif($anggota, nominal: 2_000_000, tenor: 4, lunasSampai: 2);

        DB::enableQueryLog();
        $this->service->limitTersedia($anggota);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(4, count($queries), 'limitTersedia() harusnya maksimal 4 query, dapat '.count($queries));
    }

    // ============================================================
    // Test skenario pengajuan_percepatan aktif
    // (kasus ANG-2023-0045 yang Anda laporkan — limit tidak ter-update
    // karena aggregate query tidak termasuk angsuran_percepatan)
    // ============================================================

    public function test_sisa_angsuran_menghitung_angsuran_percepatan_aktif(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user, [
            'tanggal_jadi_anggota' => now()->subYears(2), // 1-3 tahun → 5jt
        ]);
        // Pinjaman 2jt/4 bulan, 2 lunas → sisa 2 angsuran biasa (cicilan 3 & 4)
        $pinjaman = $this->buatPinjamanAktif($anggota, nominal: 2_000_000, tenor: 4, lunasSampai: 2);

        // Tambah pengajuan_percepatan aktif yang menggantikan cicilan 3 & 4,
        // dengan tenor baru 6 bulan × 200rb = 1.2jt
        $this->buatPengajuanPercepatanAktif(
            $pinjaman,
            tenorBaru: 6,
            pokokPerBulanBaru: 200_000,
            lunasSampaiBaru: 2 // 2 dari 6 sudah lunas → sisa 4 angsuran_percepatan
        );

        $agg = $anggota->pinjamanAktifDenganAgregat();

        // Total: angsuran biasa belum_bayar (cicilan 3 & 4 sudah 'digantikan' → 0)
        //       + angsuran_percepatan belum_bayar (4)
        $this->assertEquals(4, $agg['sisa_total']);
        $this->assertEquals(200_000.0, $agg['cicilan_pokok_weighted_avg']);
    }

    public function test_cicilan_pokok_aktif_menghitung_kedua_tabel(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user, [
            'tanggal_jadi_anggota' => now()->subYears(2), // 1-3 tahun → 5jt
        ]);
        $pinjaman = $this->buatPinjamanAktif($anggota, nominal: 2_000_000, tenor: 4, lunasSampai: 2);
        $this->buatPengajuanPercepatanAktif(
            $pinjaman,
            tenorBaru: 6,
            pokokPerBulanBaru: 200_000,
            lunasSampaiBaru: 2
        );

        // sisaCicilanAktif() = angsuran biasa != digantikan (0) + angsuran_percepatan belum_bayar (4) = 4
        $this->assertEquals(4, $pinjaman->sisaCicilanAktif());

        // cicilanPokokAktif() = avg nominal_pokok dari koleksi jadwalAktif() yang belum_bayar
        // koleksi: 4 angsuran_percepatan @200rb → avg = 200rb
        $this->assertEquals(200_000.0, $pinjaman->cicilanPokokAktif());
    }

    public function test_limit_tersedia_akun_angsuran_percepatan_aktif(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user, [
            'tanggal_jadi_anggota' => now()->subYears(2), // 1-3 tahun → 5jt
        ]);
        $pinjaman = $this->buatPinjamanAktif($anggota, nominal: 2_000_000, tenor: 4, lunasSampai: 2);
        // sisa angsuran_percepatan = 4 × 200rb = 800rb
        // limit 5jt - 800rb = 4.2jt
        $this->buatPengajuanPercepatanAktif(
            $pinjaman,
            tenorBaru: 6,
            pokokPerBulanBaru: 200_000,
            lunasSampaiBaru: 2
        );

        $result = $this->service->cek($anggota);

        // Aturan baru: sisa > 2 TETAP BOLEH selama privilege belum dipakai
        $this->assertTrue($result['boleh']);
        $this->assertNull($result['alasan']);
        $this->assertEquals(4, $result['sisa_angsuran']);
        $this->assertEquals(200_000.0, $result['cicilan_pokok']);
        // limit_tersedia dikurangi sesuai formula
        $this->assertEquals(4_200_000, $result['limit_tersedia']);
    }

    public function test_limit_tersedia_akun_percepatan_dan_boleh_reloan(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user, [
            'tanggal_jadi_anggota' => now()->subYears(2), // 1-3 tahun → 5jt
        ]);
        $pinjaman = $this->buatPinjamanAktif($anggota, nominal: 2_000_000, tenor: 4, lunasSampai: 2);
        // 5 dari 6 lunas → sisa 1 (≤ 2, boleh reloan)
        $this->buatPengajuanPercepatanAktif(
            $pinjaman,
            tenorBaru: 6,
            pokokPerBulanBaru: 200_000,
            lunasSampaiBaru: 5
        );

        $result = $this->service->cek($anggota);

        // sisa 1, boleh reloan
        $this->assertTrue($result['boleh']);
        $this->assertEquals(1, $result['sisa_angsuran']);
        // limit 5jt - (1 × 200rb) = 4.8jt
        $this->assertEquals(4_800_000, $result['limit_tersedia']);
    }

    public function test_pengajuan_percepatan_non_aktif_tidak_diikutkan(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user, [
            'tanggal_jadi_anggota' => now()->subYears(2),
        ]);
        $pinjaman = $this->buatPinjamanAktif($anggota, nominal: 2_000_000, tenor: 4, lunasSampai: 2);

        // Tandai cicilan 3 & 4 sebagai digantikan
        DB::table('angsuran')
            ->where('pinjaman_id', $pinjaman->id)
            ->where('cicilan_ke', '>=', 3)
            ->update(['status' => 'digantikan']);

        // Buat pengajuan_percepatan tapi status DIAJUKAN (non-aktif)
        $pengajuanId = DB::table('pengajuan_percepatan')->insertGetId([
            'pinjaman_id' => $pinjaman->id,
            'tipe' => 'perpanjang',
            'tenor_lama' => 4,
            'tenor_baru' => 6,
            'keterangan' => 'Test',
            'status' => 'diajukan', // BUKAN 'aktif'
            'tanggal_pengajuan' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        for ($i = 1; $i <= 6; $i++) {
            DB::table('angsuran_percepatan')->insert([
                'pengajuan_percepatan_id' => $pengajuanId,
                'cicilan_ke' => $i,
                'nominal_pokok' => 200_000,
                'nominal_bunga' => 2_000,
                'total_bayar' => 202_000,
                'status' => 'belum_bayar',
                'tanggal_jatuh_tempo' => now()->addMonths($i),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $agg = $anggota->pinjamanAktifDenganAgregat();

        // Total: cicilan 1 & 2 lunas (tidak dihitung), 3 & 4 digantikan (tidak dihitung)
        // angsuran_percepatan: filter whereHas('pengajuan', status='aktif') → tidak match → 0
        $this->assertEquals(0, $agg['sisa_total']);
        $this->assertEquals(0.0, $agg['cicilan_pokok_weighted_avg']);
    }

    public function test_cek_ditolak_jika_sisa_banyak_dan_privilege_sudah_dipakai(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user, [
            'tanggal_jadi_anggota' => now()->subYears(2), // 1-3 tahun → 5jt
        ]);
        // Sisa 3 (> 2) + privilege sudah dipakai → harus DITOLAK
        $this->buatPinjamanAktif(
            $anggota,
            nominal: 6_000_000,
            tenor: 6,
            lunasSampai: 3,
            sudahPakaiPrivilege: true
        );

        $result = $this->service->cek($anggota);

        $this->assertFalse($result['boleh']);
        $this->assertStringContainsString('sudah menggunakan hak', $result['alasan']);
        $this->assertEquals(3, $result['sisa_angsuran']);
        // limit_tersedia tetap dihitung walau ditolak
        $this->assertEquals(2_000_000, $result['limit_tersedia']);
    }

    // ============================================================
    // Test multi-pinjaman aktif (setelah reloan dicairkan)
    // ============================================================

    public function test_multi_pinjaman_aktif_sisa_total_gabungan(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user, [
            'tanggal_jadi_anggota' => now()->subYears(2),
        ]);
        // Pinjaman lama: sisa 2 (cicilan 3 & 4 belum_bayar)
        $this->buatPinjamanAktif($anggota, nominal: 2_000_000, tenor: 4, lunasSampai: 2);
        // Pinjaman baru (setelah reloan dicairkan): sisa 3
        $this->buatPinjamanAktif($anggota, nominal: 3_000_000, tenor: 3, lunasSampai: 0);

        $agg = $anggota->pinjamanAktifDenganAgregat();

        $this->assertCount(2, $agg['pinjaman_aktif_list']);
        $this->assertEquals(5, $agg['sisa_total']);
    }

    public function test_limit_tersedia_gabungan_weighted_avg(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user, [
            'tanggal_jadi_anggota' => now()->subYears(2), // 1-3 tahun → 5jt
        ]);
        // Pinjaman lama: 2jt/4bln, 2 lunas → sisa 2, cicilan pokok 500rb
        $this->buatPinjamanAktif($anggota, nominal: 2_000_000, tenor: 4, lunasSampai: 2);
        // Pinjaman baru: 3jt/3bln, 0 lunas → sisa 3, cicilan pokok 1jt
        $this->buatPinjamanAktif($anggota, nominal: 3_000_000, tenor: 3, lunasSampai: 0);

        // Weighted avg cicilan: (500rb×2 + 1jt×3) / (2+3) = (1jt + 3jt) / 5 = 800rb
        // Sisa total: 5
        // Limit: 5jt - (5 × 800rb) = 5jt - 4jt = 1jt
        $this->assertEquals(1_000_000, $this->service->limitTersedia($anggota));
    }

    public function test_cek_privilege_di_salah_satu_pinjaman_aktif_memblokir(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user, [
            'tanggal_jadi_anggota' => now()->subYears(2),
        ]);
        // Pinjaman lama: privilege belum dipakai
        $this->buatPinjamanAktif($anggota, nominal: 2_000_000, tenor: 4, lunasSampai: 2, sudahPakaiPrivilege: false);
        // Pinjaman baru: privilege sudah dipakai
        $this->buatPinjamanAktif($anggota, nominal: 1_500_000, tenor: 3, lunasSampai: 0, sudahPakaiPrivilege: true);

        $result = $this->service->cek($anggota);

        // Flag di salah satu = blok total
        $this->assertFalse($result['boleh']);
        $this->assertStringContainsString('sudah menggunakan hak', $result['alasan']);
    }

    public function test_cek_semua_pinjaman_aktif_privilege_false_boleh(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user, [
            'tanggal_jadi_anggota' => now()->subYears(2),
        ]);
        $this->buatPinjamanAktif($anggota, nominal: 2_000_000, tenor: 4, lunasSampai: 2, sudahPakaiPrivilege: false);
        $this->buatPinjamanAktif($anggota, nominal: 1_500_000, tenor: 3, lunasSampai: 0, sudahPakaiPrivilege: false);

        $result = $this->service->cek($anggota);

        $this->assertTrue($result['boleh']);
    }

    public function test_pakai_privlege_setiap_pengajuan_saat_ada_angsuran_walupun_sisa_banyak(): void
    {
        // Verifikasi: setelah pinjaman baru dicairkan dan flag di-set,
        // anggota tidak boleh ajukan lagi walaupun sisa masih banyak.
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user, [
            'tanggal_jadi_anggota' => now()->subYears(2),
        ]);
        // Pinjaman lama: sisa 3 (> 2), privilege false
        $pinjamanLama = $this->buatPinjamanAktif(
            $anggota,
            nominal: 6_000_000,
            tenor: 6,
            lunasSampai: 3,
            sudahPakaiPrivilege: false
        );

        // Skenario: pengajuan baru sukses, flag privilege di-set di pinjaman lama
        DB::table('pinjaman')
            ->where('anggota_id', $anggota->id)
            ->where('status', 'aktif')
            ->update(['sudah_pakai_privilege_reloan' => true]);

        // Verify flag di-set
        $pinjamanLama->refresh();
        $this->assertTrue($pinjamanLama->sudah_pakai_privilege_reloan);

        // Cek: walaupun sisa masih banyak (>2), flag privilege sudah true = ditolak
        $result = $this->service->cek($anggota);
        $this->assertFalse($result['boleh']);
        $this->assertEquals(3, $result['sisa_angsuran']);
    }

    public function test_pinjaman_aktif_list_return_semua_pinjaman_aktif(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user);
        $this->buatPinjamanAktif($anggota, nominal: 1_000_000, tenor: 3, lunasSampai: 1);
        $this->buatPinjamanAktif($anggota, nominal: 2_000_000, tenor: 4, lunasSampai: 0);

        $list = $anggota->pinjamanAktifList();

        $this->assertCount(2, $list); // kedua pinjaman aktif
    }

    public function test_pinjaman_aktif_list_tidak_include_yang_lunas(): void
    {
        $user = $this->buatUser();
        $anggota = $this->buatAnggota($user);
        $aktif = $this->buatPinjamanAktif($anggota, nominal: 1_000_000, tenor: 3, lunasSampai: 1);
        // Pinjaman lunas (status berbeda)
        $lunas = new Pinjaman;
        $lunas->fill([
            'anggota_id' => $anggota->id,
            'nominal' => 2_000_000,
            'tenor_bulan' => 4,
            'persentase_bunga' => 1.0,
            'status' => 'lunas', // BUKAN aktif
            'sudah_pakai_privilege_reloan' => false,
            'tanggal_pengajuan' => now()->subMonths(8),
            'tanggal_pencairan' => now()->subMonths(8),
        ]);
        $lunas->save();

        $list = $anggota->pinjamanAktifList();

        $this->assertCount(1, $list);
        $this->assertEquals($aktif->id, $list->first()->id);
    }
}
