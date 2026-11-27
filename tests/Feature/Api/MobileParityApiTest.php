<?php

namespace Tests\Feature\Api;

use App\Models\Anggota;
use App\Models\KasKoperasi;
use App\Models\Pinjaman;
use App\Models\RekeningTabungan;
use App\Models\SettingTabungan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class MobileParityApiTest extends TestCase
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
        auth()->forgetGuards();

        return $this->withHeader('Authorization', "Bearer {$token}");
    }

    private function tokenRole(string $noKaryawan, string $role, ?int $jabatanPengawas = null): string
    {
        $user = User::factory()->create([
            'no_karyawan' => $noKaryawan,
            'jabatan_pengawas' => $jabatanPengawas,
        ]);
        $user->assignRole($role);
        auth()->forgetGuards();

        return $user->createToken('uji')->plainTextToken;
    }

    public function test_aktivasi_mandiri_anggota_nonaktif(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota('TOP-970001', ['status' => 'nonaktif']);
        $token = $anggota->user->createToken('uji')->plainTextToken;

        $this->headerToken($token)
            ->getJson('/api/v1/aktivasi-mandiri')
            ->assertOk()
            ->assertJsonPath('anggota.status', 'nonaktif');

        $this->headerToken($token)
            ->postJson('/api/v1/aktivasi-mandiri', [
                'data_benar' => true,
                'setuju_syarat' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'diajukan');

        // Anggota aktif ditolak 403
        $anggotaAktif = $this->buatAnggota('TOP-970002', ['status' => 'aktif']);
        $tokenAktif = $anggotaAktif->user->createToken('uji')->plainTextToken;
        $this->headerToken($tokenAktif)
            ->postJson('/api/v1/aktivasi-mandiri', ['data_benar' => true, 'setuju_syarat' => true])
            ->assertForbidden();
    }

    public function test_tabungan_anggota_buka_approve_3_tahap_dan_setor(): void
    {
        Queue::fake();
        Storage::fake('local');

        $setting = SettingTabungan::current();
        $setting->update(['buka_rekening_aktif' => true, 'min_setor' => 50_000]);

        $anggota = $this->buatAnggota('TOP-970010');
        $tokenAnggota = $anggota->user->createToken('uji')->plainTextToken;

        $this->headerToken($tokenAnggota)
            ->postJson('/api/v1/tabungan/buka', ['setuju_syarat' => true])
            ->assertCreated()
            ->assertJsonPath('data.status', 'diajukan');

        $rek = RekeningTabungan::where('anggota_id', $anggota->id)->firstOrFail();

        $tokSek = $this->tokenRole('SEK-970010', 'sekretaris');
        $tokBen = $this->tokenRole('BEN-970010', 'bendahara');
        $tokKet = $this->tokenRole('KET-970010', 'ketua_koperasi');

        // Pusat persetujuan menampilkan kategori tabungan dan tidak pernah memuat shu
        $hubSek = $this->headerToken($tokSek)->getJson('/api/v1/pengurus/persetujuan');
        $hubSek->assertOk();
        $keys = collect($hubSek->json('tabs'))->pluck('key')->all();
        $this->assertContains('tabungan', $keys);
        $this->assertNotContains('shu', $keys);

        // Tahap 1: Sekretaris
        $this->headerToken($tokSek)
            ->postJson("/api/v1/pengurus/tabungan/buka/{$rek->id}/approve", ['catatan' => 'Data sesuai'])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved_admin');

        // Tahap 2: Bendahara
        $this->headerToken($tokBen)
            ->postJson("/api/v1/pengurus/tabungan/buka/{$rek->id}/approve", ['catatan' => 'Siap aktif'])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved_bendahara');

        // Tahap 3: Ketua -> Aktif
        $this->headerToken($tokKet)
            ->postJson("/api/v1/pengurus/tabungan/buka/{$rek->id}/approve", ['catatan' => 'Disetujui'])
            ->assertOk()
            ->assertJsonPath('data.status', 'aktif');

        // Setor transfer oleh anggota
        $this->headerToken($tokenAnggota)
            ->post('/api/v1/tabungan/setor', [
                'nominal' => 150000,
                'tanggal_transaksi' => now()->format('Y-m-d'),
                'bukti' => UploadedFile::fake()->image('struk.jpg'),
                'catatan' => 'Setoran awal',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'menunggu');

        $pengajuanId = $rek->pengajuanTransaksi()->latest('id')->value('id');

        // Bendahara verifikasi setor
        $this->headerToken($tokBen)
            ->postJson("/api/v1/pengurus/tabungan/pengajuan/{$pengajuanId}/approve", ['catatan' => 'Mutasi cocok'])
            ->assertOk()
            ->assertJsonPath('data.status', 'disetujui');

        $this->headerToken($tokBen)
            ->getJson('/api/v1/pengurus/list-tabungan')
            ->assertOk()
            ->assertJsonPath('statistik.jumlah_aktif', 1);

        $this->headerToken($tokBen)
            ->getJson("/api/v1/pengurus/list-tabungan/{$rek->id}")
            ->assertOk()
            ->assertJsonPath('rekening.saldo', 150000);
    }

    public function test_penghapusan_pinjaman_berjenjang_ketua_dan_pengawas_3_ke_1_termasuk_tolak(): void
    {
        Queue::fake();
        $anggota = $this->buatAnggota('TOP-970020');
        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 1_200_000,
            'tenor_bulan' => 2,
            'keperluan' => 'Macet panjang',
            'persentase_bunga' => 1,
            'status' => 'aktif',
            'tanggal_pengajuan' => now()->subMonths(6)->format('Y-m-d'),
            'tanggal_pencairan' => now()->subMonths(6)->format('Y-m-d'),
        ]);
        $pinjaman->angsuran()->create([
            'cicilan_ke' => 1,
            'nominal_pokok' => 600_000,
            'nominal_bunga' => 12_000,
            'total_bayar' => 612_000,
            'status' => 'belum_bayar',
            'tanggal_jatuh_tempo' => now()->subMonths(4)->format('Y-m-d'),
        ]);
        $pinjaman->angsuran()->create([
            'cicilan_ke' => 2,
            'nominal_pokok' => 600_000,
            'nominal_bunga' => 12_000,
            'total_bayar' => 612_000,
            'status' => 'belum_bayar',
            'tanggal_jatuh_tempo' => now()->subMonths(3)->format('Y-m-d'),
        ]);

        $tokSek = $this->tokenRole('SEK-970020', 'sekretaris');
        $tokBen = $this->tokenRole('BEN-970020', 'bendahara');
        $tokKet = $this->tokenRole('KET-970020', 'ketua_koperasi');
        $tokP3 = $this->tokenRole('PGW3-970020', 'pengawas', 3);
        $tokP2 = $this->tokenRole('PGW2-970020', 'pengawas', 2);
        $tokP1 = $this->tokenRole('PGW1-970020', 'pengawas', 1);

        // Sekretaris dan Bendahara tidak berhak mengajukan penghapusan pinjaman.
        $this->headerToken($tokSek)
            ->postJson('/api/v1/pengurus/penghapusan-pinjaman', [
                'pinjaman_id' => $pinjaman->id,
                'alasan' => 'Sekretaris mencoba mengajukan',
            ])
            ->assertForbidden();

        $this->headerToken($tokBen)
            ->postJson('/api/v1/pengurus/penghapusan-pinjaman', [
                'pinjaman_id' => $pinjaman->id,
                'alasan' => 'Bendahara mencoba mengajukan',
            ])
            ->assertForbidden();

        // Ketua Koperasi mengajukan -> langsung masuk antrean Pengawas 3 (status: diajukan).
        $resStore = $this->headerToken($tokKet)
            ->postJson('/api/v1/pengurus/penghapusan-pinjaman', [
                'pinjaman_id' => $pinjaman->id,
                'alasan' => 'Karyawan sudah tidak dapat dihubungi lebih dari 90 hari',
            ])
            ->assertCreated();
        $id = $resStore->json('data.id');
        $this->assertSame('diajukan', $resStore->json('data.status'));

        // Pengawas 2 tidak bisa mendahului Pengawas 3
        $this->headerToken($tokP2)->postJson("/api/v1/pengurus/penghapusan-pinjaman/{$id}/approve", ['catatan' => 'Terlalu cepat'])->assertForbidden();

        $this->headerToken($tokP3)->postJson("/api/v1/pengurus/penghapusan-pinjaman/{$id}/approve", ['catatan' => 'Setuju P3'])->assertOk();
        // Anti-double approve oleh Pengawas 3
        $this->headerToken($tokP3)->postJson("/api/v1/pengurus/penghapusan-pinjaman/{$id}/approve", ['catatan' => 'Coba ulang'])->assertForbidden();

        $this->headerToken($tokP2)->postJson("/api/v1/pengurus/penghapusan-pinjaman/{$id}/approve", ['catatan' => 'Setuju P2'])->assertOk();
        $this->headerToken($tokP1)
            ->postJson("/api/v1/pengurus/penghapusan-pinjaman/{$id}/approve", ['catatan' => 'Final hapus buku'])
            ->assertOk()
            ->assertJsonPath('data.status', 'disetujui');

        $this->assertEquals('dihapusbukukan', $pinjaman->fresh()->status);

        // Pinjaman terhapus dapat difilter di list-pinjaman
        $this->headerToken($tokBen)
            ->getJson('/api/v1/pengurus/list-pinjaman?status=dihapusbukukan')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_kelola_anggota_tambah_update_resign_reaktivasi(): void
    {
        Queue::fake();
        KasKoperasi::firstOrCreate([], ['saldo_bank' => 50_000_000, 'saldo_kas_kecil' => 10_000_000]);

        $tokSek = $this->tokenRole('SEK-970030', 'sekretaris');

        $resCreate = $this->headerToken($tokSek)
            ->postJson('/api/v1/pengurus/anggota', [
                'nama' => 'Anggota Baru Mobile',
                'no_karyawan' => 'TOP-970031',
                'email' => 'baru.mobile@koperasi.test',
                'cabang' => 'Banjarmasin',
                'unit_bisnis' => 'Operasional',
                'jabatan' => 'Staff',
                'department' => 'IT',
                'tanggal_mulai_kerja' => now()->subYear()->format('Y-m-d'),
                'tanggal_jadi_anggota' => now()->subMonth()->format('Y-m-d'),
            ])
            ->assertCreated();
        $anggotaId = $resCreate->json('data.id');

        $this->headerToken($tokSek)
            ->putJson("/api/v1/pengurus/anggota/{$anggotaId}", [
                'tanggal_jadi_anggota' => now()->subMonth()->format('Y-m-d'),
                'status' => 'aktif',
                'limit_custom' => 5_000_000,
                'limit_custom_keterangan' => 'Kebijakan khusus direksi',
            ])
            ->assertOk()
            ->assertJsonPath('data.limit_custom', 5_000_000);

        $this->headerToken($tokSek)
            ->getJson("/api/v1/pengurus/anggota/{$anggotaId}/resign-ringkasan")
            ->assertOk()
            ->assertJsonStructure(['anggota', 'ringkasan']);

        $this->headerToken($tokSek)
            ->postJson("/api/v1/pengurus/anggota/{$anggotaId}/resign", [
                'alasan_resign' => 'Pindah domisili keluar pulau',
                'tanggal_resign' => now()->format('Y-m-d'),
            ])
            ->assertOk();

        $tokBen = $this->tokenRole('BEN-970030', 'bendahara');
        Storage::fake('local');

        $this->headerToken($tokBen)
            ->post("/api/v1/pengurus/anggota/{$anggotaId}/cairkan-resign", [
                'tanggal_pencairan' => now()->format('Y-m-d'),
                'akun_kas' => 'bank',
                'bukti_pencairan' => UploadedFile::fake()->image('resign.jpg'),
            ])
            ->assertOk()
            ->assertJsonPath('data.status_pencairan_resign', 'dicairkan');

        $this->headerToken($tokSek)
            ->postJson("/api/v1/pengurus/anggota/{$anggotaId}/reaktivasi", [
                'alasan_reaktivasi' => 'Bergabung kembali sebagai karyawan aktif',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'aktif');
    }
}
