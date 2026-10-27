<?php

namespace Tests\Feature\Api;

use App\Models\Anggota;
use App\Models\KasKoperasi;
use App\Models\PengajuanLimit;
use App\Models\PengajuanPercepatan;
use App\Models\Pinjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class MobilePengurusTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function headerToken(string $token)
    {
        return $this->withHeader('Authorization', "Bearer {$token}");
    }

    private function tokenIzin(string $noKaryawan, array $izin): string
    {
        $user = User::factory()->create(['no_karyawan' => $noKaryawan]);
        $user->givePermissionTo(...$izin);
        // Guard di-resolve ulang agar permission baru terbaca request berikutnya.
        auth()->forgetGuards();

        return $user->createToken('uji')->plainTextToken;
    }

    private function buatDiajukan(Anggota $anggota, array $override = []): Pinjaman
    {
        return Pinjaman::create(array_merge([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            // Dalam limit anggota 1-3 tahun (5jt) agar lolos validasi bendahara/ketua.
            'nominal' => 1_000_000,
            'nominal_diminta' => 1_000_000,
            'tenor_bulan' => 3,
            'tenor_diminta' => 3,
            'keperluan' => 'Uji antrean pengurus',
            'persentase_bunga' => 1,
            'status' => 'diajukan',
            'tanggal_pengajuan' => now()->format('Y-m-d'),
        ], $override));
    }

    public function test_tamu_ditolak_di_endpoint_pengurus(): void
    {
        $this->getJson('/api/v1/pengurus/pinjaman')->assertUnauthorized();
        $this->getJson('/api/v1/pengurus/pengajuan-limit')->assertUnauthorized();
        $this->getJson('/api/v1/pengurus/percepatan')->assertUnauthorized();
        $this->postJson('/api/v1/pengurus/pinjaman/1/preview', [])->assertUnauthorized();
        $this->postJson('/api/v1/pengurus/pinjaman/1/approve', [])->assertUnauthorized();
        $this->postJson('/api/v1/pengurus/pinjaman/1/reject', [])->assertUnauthorized();
    }

    public function test_antrean_tanpa_izin_ditolak_403(): void
    {
        $anggota = $this->buatAnggota('TOP-980001');
        $this->buatDiajukan($anggota);
        $token = $anggota->user->createToken('uji')->plainTextToken;

        $this->headerToken($token)->getJson('/api/v1/pengurus/pinjaman?tahap=admin')->assertForbidden();
        $this->headerToken($token)->getJson('/api/v1/pengurus/pengajuan-limit')->assertForbidden();
        $this->headerToken($token)->getJson('/api/v1/pengurus/percepatan')->assertForbidden();
    }

    public function test_antrean_pinjaman_per_tahap_dan_total(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota('TOP-980002');
        $diajukan = $this->buatDiajukan($anggota);
        $token = $this->tokenIzin('ADM-980002', ['pinjaman.tinjau-admin']);

        $res = $this->headerToken($token)->getJson('/api/v1/pengurus/pinjaman?tahap=admin')->assertOk();
        $res->assertJsonPath('tahap', 'admin');
        $ids = collect($res->json('data'))->pluck('id')->all();
        $this->assertContains($diajukan->id, $ids);
        $this->assertGreaterThanOrEqual(1, $res->json('total'));
        $res->assertJsonStructure(['data' => [['tahapan', 'anggota']]]);

        $this->headerToken($token)->getJson('/api/v1/pengurus/pinjaman')->assertOk()->assertJsonPath('tahap', 'admin');
        $this->headerToken($token)->getJson('/api/v1/pengurus/pinjaman?tahap=ketua')->assertForbidden();
        $this->headerToken($token)->getJson('/api/v1/pengurus/pinjaman?tahap=salah')->assertUnprocessable();
    }

    public function test_admin_teruskan_lalu_bendahara_setujui(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota('TOP-980003');
        $p = $this->buatDiajukan($anggota);

        $admin = $this->tokenIzin('ADM-980003', ['pinjaman.tinjau-admin']);
        $resAdmin = $this->headerToken($admin)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson("/api/v1/pengurus/pinjaman/{$p->id}/approve", ['catatan' => 'Dokumen lengkap, diteruskan.']);
        $resAdmin->assertOk()
            ->assertJsonPath('message', 'Pengajuan diteruskan ke Bendahara.')
            ->assertJsonPath('pinjaman.status', 'approved_admin');
        $this->assertSame('approved_admin', $p->refresh()->status);

        $bendahara = $this->tokenIzin('BEN-980003', ['pinjaman.tinjau-bendahara']);
        $resBen = $this->headerToken($bendahara)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson("/api/v1/pengurus/pinjaman/{$p->id}/approve", [
                'catatan' => 'Setuju, nominal sesuai limit.',
            ]);
        $resBen->assertOk()
            ->assertJsonPath('pinjaman.status', 'approved_bendahara')
            ->assertJsonPath('pinjaman.nominal_disetujui_bendahara', 1_000_000);
    }

    public function test_ketua_cairkan_menjadi_aktif_dan_kas_berkurang(): void
    {
        Queue::fake();
        // Keanggotaan 2 tahun -> limit 5jt; nominal 1jt lolos validasi pencairan.
        $anggota = $this->buatAnggota('TOP-980004');
        $p = $this->buatDiajukan($anggota, ['status' => 'approved_bendahara']);

        $bankSebelum = (float) KasKoperasi::first()->saldo_bank;
        $ketua = $this->tokenIzin('KET-980004', ['pinjaman.approve-ketua']);

        $this->headerToken($ketua)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson("/api/v1/pengurus/pinjaman/{$p->id}/approve", [
                'catatan' => 'Disetujui dan dicairkan ketua.',
            ])
            ->assertOk()
            ->assertJsonPath('pinjaman.status', 'aktif');

        $p->refresh();
        $this->assertSame('aktif', $p->status);
        $this->assertNotNull($p->tanggal_pencairan);
        $this->assertCount(3, $p->angsuran()->get());
        $this->assertEquals($bankSebelum - 1_000_000, (float) KasKoperasi::first()->saldo_bank);
    }

    public function test_pencairan_mandiri_oleh_bendahara(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota('TOP-980005');
        $p = $this->buatDiajukan($anggota, [
            'status' => 'approved_bendahara',
            'cair_oleh_bendahara' => true,
            'nominal_disetujui_bendahara' => 1_000_000,
            'tenor_disetujui_bendahara' => 3,
        ]);

        $token = $this->tokenIzin('BEN-980005', ['pinjaman.tinjau-bendahara', 'pinjaman.lihat']);

        $res = $this->headerToken($token)->getJson('/api/v1/pengurus/pinjaman?tahap=pencairan')->assertOk();
        $this->assertContains($p->id, collect($res->json('data'))->pluck('id')->all());

        $this->headerToken($token)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson("/api/v1/pengurus/pinjaman/{$p->id}/approve", ['catatan' => 'Dicairkan untuk ketua mandiri.'])
            ->assertOk()
            ->assertJsonPath('pinjaman.status', 'aktif');
    }

    public function test_reject_tanpa_catatan_422_dan_self_approval_403(): void
    {
        $anggota = $this->buatAnggota('TOP-980006');
        $p = $this->buatDiajukan($anggota);
        $admin = $this->tokenIzin('ADM-980006', ['pinjaman.tinjau-admin']);

        $this->headerToken($admin)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson("/api/v1/pengurus/pinjaman/{$p->id}/reject", ['catatan' => 'ok'])
            ->assertUnprocessable();

        // Pemohon sendiri yang memegang izin tahap tidak boleh memproses.
        $anggota->user->update(['no_karyawan' => 'BEN-980006']);
        $anggota->update(['no_karyawan' => 'BEN-980006']);
        $anggota->user->givePermissionTo('pinjaman.tinjau-admin');
        auth()->forgetGuards();
        $tokenSendiri = $anggota->user->createToken('uji2')->plainTextToken;

        $this->headerToken($tokenSendiri)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson("/api/v1/pengurus/pinjaman/{$p->id}/approve", ['catatan' => 'Saya setujui sendiri.'])
            ->assertForbidden();
    }

    public function test_approve_status_salah_409(): void
    {
        $anggota = $this->buatAnggota('TOP-980007');
        $p = $this->buatDiajukan($anggota, ['status' => 'aktif']);
        $token = $this->tokenIzin('ADM-980007', ['pinjaman.tinjau-admin']);

        $this->headerToken($token)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson("/api/v1/pengurus/pinjaman/{$p->id}/approve", ['catatan' => 'Sudah aktif, coba lagi.'])
            ->assertConflict();
    }

    public function test_preview_keputusan_struktur(): void
    {
        $anggota = $this->buatAnggota('TOP-980008');
        $p = $this->buatDiajukan($anggota);
        $token = $this->tokenIzin('BEN-980008', ['pinjaman.tinjau-bendahara']);

        $res = $this->headerToken($token)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson("/api/v1/pengurus/pinjaman/{$p->id}/preview", ['nominal' => 1_500_000, 'tenor_bulan' => 3])
            ->assertOk();

        $res->assertJsonStructure([
            'nominal', 'tenor_bulan', 'tenor_maksimal', 'jadwal', 'total_cicilan',
            'total_pokok', 'total_bunga', 'total_bayar', 'cicilan_pertama',
            'cicilan_terakhir', 'cicilan_rata_rata', 'kas_saldo', 'kas_sisa',
        ]);
        $res->assertJsonPath('nominal', 1_500_000);
        $res->assertJsonPath('tenor_bulan', 3);
        $this->assertCount(3, $res->json('jadwal'));
    }

    public function test_limit_dua_tahap_dan_limit_custom_terset(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota('TOP-980009');
        $pengajuan = PengajuanLimit::create([
            'anggota_id' => $anggota->id,
            'limit_saat_ini' => 1_000_000,
            'limit_diminta' => 2_000_000,
            'keterangan' => 'Uji antrean limit pengurus.',
            'status' => 'diajukan',
            'tanggal_pengajuan' => now()->format('Y-m-d'),
        ]);

        $bendahara = $this->tokenIzin('BEN-980009', ['limit.tinjau-bendahara']);
        $res = $this->headerToken($bendahara)->getJson('/api/v1/pengurus/pengajuan-limit')->assertOk();
        $this->assertContains($pengajuan->id, collect($res->json('data'))->pluck('id')->all());

        $this->headerToken($bendahara)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson("/api/v1/pengurus/pengajuan-limit/{$pengajuan->id}/approve", [
                'catatan' => 'Layak, diteruskan ketua.',
                'limit_disetujui' => 1_800_000,
            ])
            ->assertOk()
            ->assertJsonPath('pengajuan.status', 'approved_bendahara');

        $ketua = $this->tokenIzin('KET-980009', ['limit.approve-ketua']);
        $this->headerToken($ketua)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson("/api/v1/pengurus/pengajuan-limit/{$pengajuan->id}/approve", [
                'catatan' => 'Setuju final ketua.',
            ])
            ->assertOk()
            ->assertJsonPath('pengajuan.status', 'disetujui')
            ->assertJsonPath('pengajuan.limit_disetujui', 1_800_000);

        $this->assertEquals(1_800_000, (float) $anggota->fresh()->limit_custom);
    }

    public function test_percepatan_dua_tahap_menerapkan_jadwal(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota('TOP-980010');
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 2_000_000,
            'tenor_bulan' => 4,
            'keperluan' => 'Uji percepatan pengurus',
            'persentase_bunga' => 1,
            'status' => 'aktif',
            'tanggal_pengajuan' => now()->subMonth()->format('Y-m-d'),
            'tanggal_pencairan' => now()->subMonth()->format('Y-m-d'),
        ]);
        foreach (range(1, 4) as $ke) {
            $pinjaman->angsuran()->create([
                'cicilan_ke' => $ke,
                'nominal_pokok' => 500_000,
                'nominal_bunga' => 20_000,
                'total_bayar' => 520_000,
                'status' => 'belum_bayar',
                'tanggal_jatuh_tempo' => now()->subMonth()->copy()->addMonths($ke - 1)->endOfMonth(),
            ]);
        }
        $pengajuan = PengajuanPercepatan::create([
            'pinjaman_id' => $pinjaman->id,
            'tipe' => 'percepat',
            'tenor_lama' => 4,
            'tenor_baru' => 2,
            'keterangan' => 'Uji percepatan dua tahap.',
            'status' => 'diajukan',
            'tanggal_pengajuan' => now()->format('Y-m-d'),
        ]);
        // Isolasi dari antrean bawaan seeder agar final apply deterministik.
        PengajuanPercepatan::where('id', '!=', $pengajuan->id)
            ->whereIn('status', ['diajukan', 'approved_bendahara'])
            ->update(['status' => 'ditolak']);

        $bendahara = $this->tokenIzin('BEN-980010', ['pinjaman.tinjau-bendahara']);
        $res = $this->headerToken($bendahara)->getJson('/api/v1/pengurus/percepatan')->assertOk();
        $this->assertContains($pengajuan->id, collect($res->json('data'))->pluck('id')->all());

        $this->headerToken($bendahara)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson("/api/v1/pengurus/percepatan/{$pengajuan->id}/approve", [
                'catatan' => 'Layak, diteruskan ketua.',
            ])
            ->assertOk()
            ->assertJsonPath('pengajuan.status', 'approved_bendahara');

        $ketua = $this->tokenIzin('KET-980010', ['pinjaman.approve-ketua']);
        $this->headerToken($ketua)->withHeader('Idempotency-Key', Str::uuid()->toString())
            ->postJson("/api/v1/pengurus/percepatan/{$pengajuan->id}/approve", [
                'catatan' => 'Setuju, berlaku bulan depan.',
                'bulan_berlaku' => 'bulan_depan',
            ])
            ->assertOk()
            ->assertJsonPath('pengajuan.status', 'aktif');

        $pengajuan->refresh();
        $this->assertSame('aktif', $pengajuan->status);
        $this->assertTrue((bool) $pinjaman->fresh()->sudah_pakai_percepatan);
        $this->assertSame(2, $pengajuan->angsuranBaru()->count());
    }
}
