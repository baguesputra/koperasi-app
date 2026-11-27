<?php

namespace Tests\Feature\Api;

use App\Models\Angsuran;
use App\Models\JurnalKas;
use App\Models\KasKoperasi;
use App\Models\PengajuanAktivasi;
use App\Models\PengajuanLimit;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use App\Models\SettingSimpanan;
use App\Models\Simpanan;
use App\Models\User;
use App\Services\Pinjaman\EligibilitasPinjamanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class MobileRedesignApiTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        EligibilitasPinjamanService::resetCache();
    }

    protected function tearDown(): void
    {
        EligibilitasPinjamanService::resetCache();
        parent::tearDown();
    }

    private function headerToken(string $token): static
    {
        return $this->withHeader('Authorization', "Bearer {$token}");
    }

    private function key(): array
    {
        return ['Idempotency-Key' => Str::uuid()->toString()];
    }

    public function test_pin_setup_verify_and_stateless_lockout(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota('TOP-990001');
        $user = $anggota->user;
        $token = $user->createToken('uji')->plainTextToken;

        $this->headerToken($token)->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('user.has_pin', false)
            ->assertJsonPath('user.anggota_status', 'aktif');

        $this->headerToken($token)->getJson('/api/v1/security/pin/status')
            ->assertOk()
            ->assertJsonPath('has_pin', false)
            ->assertJsonPath('terkunci', false);

        $this->headerToken($token)->postJson('/api/v1/security/pin/setup', [
            'pin' => '111111',
            'pin_confirmation' => '111111',
        ])->assertUnprocessable();

        $this->headerToken($token)->postJson('/api/v1/security/pin/setup', [
            'pin' => '246810',
            'pin_confirmation' => '246810',
        ])->assertOk()->assertJsonPath('has_pin', true);

        $this->headerToken($token)->postJson('/api/v1/security/pin/verify', ['pin' => '246810'])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        // Uji lockout 3x salah PIN pada pengguna terpisah agar tetap di bawah throttle:pin (5/menit).
        $user2 = $this->buatAnggota('TOP-990099')->user;
        $user2->update(['pin' => Hash::make('246810')]);
        auth()->forgetGuards();
        $token2 = $user2->createToken('uji')->plainTextToken;

        $this->headerToken($token2)->postJson('/api/v1/security/pin/verify', ['pin' => '000001'])
            ->assertUnprocessable()
            ->assertJsonPath('remaining', 2);

        $this->headerToken($token2)->postJson('/api/v1/security/pin/verify', ['pin' => '000002'])
            ->assertUnprocessable()
            ->assertJsonPath('remaining', 1);

        $this->headerToken($token2)->postJson('/api/v1/security/pin/verify', ['pin' => '000003'])
            ->assertStatus(423)
            ->assertJsonPath('status', 'locked_out');

        auth()->forgetGuards();
        $this->headerToken($token2)->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_loan_store_validates_pin_or_biometric_when_has_pin(): void
    {
        Queue::fake();
        $a1 = $this->buatAnggota('TOP-990002');
        $a1->user->update(['pin' => Hash::make('135790')]);
        $t1 = $a1->user->createToken('uji')->plainTextToken;

        $basePayload = [
            'nominal' => 1_000_000,
            'tenor_bulan' => 3,
            'keperluan' => 'Biaya pendidikan anak',
            'rekening_mode' => 'baru',
            'nama_bank' => 'BCA',
            'no_rekening' => '1234567890',
            'atas_nama' => $a1->nama,
            'persetujuan' => true,
        ];

        $this->headerToken($t1)->withHeaders($this->key())
            ->postJson('/api/v1/pinjaman', $basePayload)
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['pin']]);

        $this->headerToken($t1)->withHeaders($this->key())
            ->postJson('/api/v1/pinjaman', [...$basePayload, 'pin' => '999999'])
            ->assertUnprocessable();

        $resOkPin = $this->headerToken($t1)->withHeaders($this->key())
            ->postJson('/api/v1/pinjaman', [...$basePayload, 'pin' => '135790']);
        $this->assertSame(201, $resOkPin->status(), $resOkPin->getContent());

        $a2 = $this->buatAnggota('TOP-990003');
        $a2->user->update(['pin' => Hash::make('246813')]);
        auth()->forgetGuards();
        $t2 = $a2->user->createToken('uji')->plainTextToken;

        $resBio = $this->headerToken($t2)->withHeaders($this->key())
            ->postJson('/api/v1/pinjaman', [
                ...$basePayload,
                'atas_nama' => $a2->nama,
                'biometric_verified' => true,
            ]);
        $this->assertSame(201, $resBio->status(), $resBio->getContent());
    }

    public function test_dashboard_and_simpanan_expanded_fields(): void
    {
        $anggota = $this->buatAnggota('TOP-990004', ['tanggal_jadi_anggota' => now()->subYears(2)]);
        Simpanan::create([
            'anggota_id' => $anggota->id,
            'jenis' => 'wajib',
            'jumlah' => 45_000,
            'bulan_periode' => now()->format('Y-m'),
            'tanggal_input' => now()->format('Y-m-d'),
            'input_by' => $anggota->user_id,
        ]);
        Simpanan::create([
            'anggota_id' => $anggota->id,
            'jenis' => 'dana_sosial',
            'jumlah' => 5_000,
            'bulan_periode' => now()->format('Y-m'),
            'tanggal_input' => now()->format('Y-m-d'),
            'input_by' => $anggota->user_id,
        ]);
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 1_000_000,
            'tenor_bulan' => 2,
            'keperluan' => 'Uji dashboard baru',
            'persentase_bunga' => 1,
            'status' => 'aktif',
            'tanggal_pengajuan' => now()->format('Y-m-d'),
            'tanggal_pencairan' => now()->format('Y-m-d'),
        ]);
        Angsuran::create([
            'pinjaman_id' => $pinjaman->id,
            'cicilan_ke' => 1,
            'nominal_pokok' => 500_000,
            'nominal_bunga' => 10_000,
            'total_bayar' => 510_000,
            'tanggal_jatuh_tempo' => now()->endOfMonth()->format('Y-m-d'),
            'status' => 'belum_bayar',
        ]);

        $token = $anggota->user->createToken('uji')->plainTextToken;

        $res = $this->headerToken($token)->getJson('/api/v1/dashboard')->assertOk();
        $res->assertJsonStructure([
            'anggota' => ['lama_keanggotaan_label', 'tanggal_mulai_kerja', 'tanggal_jadi_anggota'],
            'ringkasan' => ['boleh_tambah_limit', 'status_limit_badge', 'state_limit_label', 'plafon_kebijakan'],
            'angsuran_berikutnya' => ['pinjaman_id', 'cicilan_ke', 'total_bayar', 'tanggal_jatuh_tempo'],
            'syarat_pinjaman' => ['versi', 'poin'],
        ]);
        $res->assertJsonPath('angsuran_berikutnya.pinjaman_id', $pinjaman->id);

        $simpanan = $this->headerToken($token)->getJson('/api/v1/anggota/simpanan')->assertOk();
        $simpanan->assertJsonPath('ringkasan.dana_sosial', 5000);
        $this->assertContains(now()->format('Y-m'), $simpanan->json('daftar_bulan_tersedia'));
    }

    public function test_pengawas_ringkasan_grafik_dan_list_anggota(): void
    {
        $this->buatAnggota('TOP-990005');

        $user = User::factory()->create(['no_karyawan' => 'PGW-990005']);
        Role::firstOrCreate(['name' => 'pengawas', 'guard_name' => 'web']);
        $user->assignRole('pengawas');
        auth()->forgetGuards();
        $token = $user->createToken('uji')->plainTextToken;

        $res = $this->headerToken($token)->getJson('/api/v1/pengurus/ringkasan')->assertOk();
        $res->assertJsonStructure([
            'antrean' => ['aktivasi_anggota'],
            'kas' => ['saldo_dana_sosial', 'total_keseluruhan'],
            'grafik_tren',
            'grafik_kas',
            'aktivitas_terbaru',
        ]);
        $this->assertCount(6, $res->json('grafik_tren'));
        $this->assertCount(6, $res->json('grafik_kas'));

        $list = $this->headerToken($token)->getJson('/api/v1/pengurus/list-anggota?cari=ANG-UJI-0005')->assertOk();
        $list->assertJsonStructure(['data', 'meta', 'rekap' => ['total', 'aktif', 'nonaktif', 'resign']]);
        $this->assertSame(1, $list->json('meta.total'));
    }

    public function test_aktivasi_ketua_approve_dan_reject(): void
    {
        Queue::fake();
        $a1 = $this->buatAnggota('TOP-990006', ['status' => 'nonaktif']);
        $a2 = $this->buatAnggota('TOP-990007', ['status' => 'nonaktif']);

        $p1 = PengajuanAktivasi::create([
            'anggota_id' => $a1->id,
            'status' => 'diajukan',
            'versi_syarat' => 'v1',
            'data_benar' => true,
            'setuju_syarat' => true,
            'tanggal_pengajuan' => now()->format('Y-m-d'),
        ]);
        $p2 = PengajuanAktivasi::create([
            'anggota_id' => $a2->id,
            'status' => 'diajukan',
            'versi_syarat' => 'v1',
            'data_benar' => true,
            'setuju_syarat' => true,
            'tanggal_pengajuan' => now()->format('Y-m-d'),
        ]);

        $biasaToken = $this->buatAnggota('TOP-990008')->user->createToken('uji')->plainTextToken;
        $this->headerToken($biasaToken)->getJson('/api/v1/pengurus/aktivasi')->assertForbidden();

        $ketua = User::factory()->create(['no_karyawan' => 'KET-990006']);
        $ketua->givePermissionTo('aktivasi.approve-ketua');
        auth()->forgetGuards();
        $ketuaToken = $ketua->createToken('uji')->plainTextToken;

        $this->headerToken($ketuaToken)->getJson('/api/v1/pengurus/aktivasi')
            ->assertOk()
            ->assertJsonStructure(['menunggu', 'riwayat', 'ketentuan_finansial']);

        $this->headerToken($ketuaToken)->withHeaders($this->key())
            ->postJson("/api/v1/pengurus/aktivasi/{$p1->id}/approve", ['catatan' => 'Disetujui aktif oleh Ketua.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'disetujui');
        $this->assertSame('aktif', $a1->fresh()->status);

        $this->headerToken($ketuaToken)->withHeaders($this->key())
            ->postJson("/api/v1/pengurus/aktivasi/{$p2->id}/reject", ['catatan' => 'Data kepegawaian belum sesuai.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'ditolak');
    }

    public function test_batalkan_angsuran_simpanan_dan_kas_storno(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota('TOP-990009');
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 1_000_000,
            'tenor_bulan' => 2,
            'keperluan' => 'Uji storno',
            'persentase_bunga' => 1,
            'status' => 'aktif',
            'tanggal_pengajuan' => now()->format('Y-m-d'),
            'tanggal_pencairan' => now()->format('Y-m-d'),
        ]);
        $angsuran = Angsuran::create([
            'pinjaman_id' => $pinjaman->id,
            'cicilan_ke' => 1,
            'nominal_pokok' => 500_000,
            'nominal_bunga' => 10_000,
            'total_bayar' => 510_000,
            'status' => 'belum_bayar',
            'tanggal_jatuh_tempo' => now()->endOfMonth(),
        ]);

        $bendahara = User::factory()->create(['no_karyawan' => 'BEN-990009']);
        $bendahara->givePermissionTo('angsuran.konfirmasi', 'simpanan.konfirmasi', 'kas.lihat', 'kas.topup');
        auth()->forgetGuards();
        $token = $bendahara->createToken('uji')->plainTextToken;
        $bulan = now()->format('Y-m');

        // 1. Konfirmasi & Batalkan Angsuran
        $this->headerToken($token)->withHeaders($this->key())
            ->postJson('/api/v1/pengurus/angsuran/konfirmasi', ['angsuran_ids' => ["n-{$angsuran->id}"]])
            ->assertOk();
        $this->assertSame('lunas', $angsuran->fresh()->status);

        $resAng = $this->headerToken($token)->getJson("/api/v1/pengurus/angsuran?bulan={$bulan}")->assertOk();
        $this->assertNotEmpty($resAng->json('sudah_dikonfirmasi'));

        $this->headerToken($token)->withHeaders($this->key())
            ->postJson("/api/v1/pengurus/angsuran/n-{$angsuran->id}/batalkan", ['alasan' => 'Salah konfirmasi anggota'])
            ->assertOk();
        $this->assertSame('belum_bayar', $angsuran->fresh()->status);

        // 2. Konfirmasi & Batalkan Simpanan
        config(['koperasi.simpanan_terlambat_mulai' => $bulan]);
        $this->headerToken($token)->withHeaders($this->key())
            ->postJson('/api/v1/pengurus/simpanan/konfirmasi', ['anggota_ids' => [$anggota->id], 'bulan_periode' => $bulan])
            ->assertOk();
        $resSim = $this->headerToken($token)->getJson("/api/v1/pengurus/simpanan?bulan={$bulan}")->assertOk();
        $this->assertContains($anggota->id, collect($resSim->json('sudah_dikonfirmasi'))->pluck('id')->all());

        $this->headerToken($token)->withHeaders($this->key())
            ->postJson("/api/v1/pengurus/simpanan/{$anggota->id}/batalkan", [
                'bulan_periode' => $bulan,
                'alasan' => 'Koreksi potongan bulan berjalan',
            ])->assertOk();
        $this->assertSame(0, Simpanan::where('anggota_id', $anggota->id)->where('bulan_periode', $bulan)->count());

        // 3. Topup & Batalkan Kas (storno)
        $saldoAwal = (float) KasKoperasi::first()->saldo_bank;
        $this->headerToken($token)->withHeaders($this->key())
            ->postJson('/api/v1/pengurus/kas/topup', [
                'jumlah' => 250_000,
                'sumber_topup' => 'modal',
                'keterangan' => 'Uji topup-unik-990009',
                'tanggal' => now()->format('Y-m-d'),
            ])->assertOk();

        $jurnal = JurnalKas::where('keterangan', 'Uji topup-unik-990009')->firstOrFail();
        $resKas = $this->headerToken($token)->getJson("/api/v1/pengurus/kas?bulan={$bulan}&cari=topup-unik-990009")->assertOk();
        $this->assertSame(1, $resKas->json('meta.total'));
        $this->assertTrue((bool) $resKas->json('data.0.bisa_dibatalkan'));

        $this->headerToken($token)->withHeaders($this->key())
            ->postJson("/api/v1/pengurus/kas/{$jurnal->id}/batalkan", ['alasan' => 'Nominal salah input'])
            ->assertOk();
        $this->assertSame($saldoAwal, (float) KasKoperasi::first()->saldo_bank);
        $this->assertNotNull($jurnal->fresh()->dibatalkan_pada);
    }

    public function test_laporan_12_slugs_dan_semua_tipe_filter_serta_tab_riwayat(): void
    {
        $anggota = $this->buatAnggota('TOP-990010');
        SettingSimpanan::first();
        $ketua = User::factory()->create(['no_karyawan' => 'KET-990010']);
        $ketua->givePermissionTo('laporan.lihat', 'pinjaman.approve-ketua', 'limit.approve-ketua', 'pinjaman.tinjau-bendahara');
        auth()->forgetGuards();
        $token = $ketua->createToken('uji')->plainTextToken;

        $resIndex = $this->headerToken($token)->getJson('/api/v1/laporan')->assertOk();
        $this->assertCount(12, $resIndex->json('data'));

        $tgl = now()->format('Y-m-d');
        $bulan = now()->format('Y-m');
        $tahun = now()->format('Y');

        $this->headerToken($token)->getJson("/api/v1/laporan/neraca?tanggal={$tgl}")->assertOk()->assertJsonStructure(['judul', 'kolom', 'baris', 'ringkasan', 'gaya_baris']);
        $this->headerToken($token)->getJson("/api/v1/laporan/iuran-pinjaman-rekap?bulan={$bulan}")->assertOk();
        $this->headerToken($token)->getJson('/api/v1/laporan/simpanan-per-anggota')->assertOk();
        $this->headerToken($token)->getJson("/api/v1/laporan/arus-kas?dari={$bulan}&sampai={$bulan}")->assertOk();
        $this->headerToken($token)->getJson("/api/v1/laporan/laba-rugi?tahun={$tahun}")->assertOk();

        $this->headerToken($token)->getJson('/api/v1/pengurus/pinjaman?tahap=ketua&tab=riwayat')->assertOk()->assertJsonPath('tab', 'riwayat');
        $this->headerToken($token)->getJson('/api/v1/pengurus/pengajuan-limit?tab=riwayat')->assertOk()->assertJsonPath('tab', 'riwayat');
        $this->headerToken($token)->getJson('/api/v1/pengurus/percepatan?tab=riwayat')->assertOk()->assertJsonPath('tab', 'riwayat');

        // Clean reference usage
        PengajuanLimit::where('anggota_id', $anggota->id)->count();
        PengajuanPercepatan::count();
    }
}
