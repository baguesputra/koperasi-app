<?php

namespace Tests\Feature;

use App\Jobs\KirimWaJob;
use App\Models\Angsuran;
use App\Models\AuditLog;
use App\Models\KasKoperasi;
use App\Models\Pinjaman;
use App\Models\RekeningAnggota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class EditBatalPinjamanTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Queue::fake();
    }

    private function pasangPin(User $user, string $pin = '246810'): void
    {
        $user->update(['pin' => Hash::make($pin)]);
    }

    public function test_anggota_dapat_edit_pengajuan_diajukan_dan_divalidasi_pin(): void
    {
        $anggota = $this->buatAnggota('TOP-920001');
        $user = $anggota->user;
        $this->pasangPin($user, '246810');

        $rek1 = RekeningAnggota::create([
            'anggota_id' => $anggota->id,
            'nama_bank' => 'BCA',
            'no_rekening' => '111222333',
            'atas_nama' => $anggota->nama,
            'is_default' => true,
        ]);

        $rek2 = RekeningAnggota::create([
            'anggota_id' => $anggota->id,
            'nama_bank' => 'Mandiri',
            'no_rekening' => '444555666',
            'atas_nama' => $anggota->nama,
            'is_default' => false,
        ]);

        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $user->id,
            'nominal' => 1_000_000,
            'nominal_diminta' => 1_000_000,
            'tenor_bulan' => 3,
            'tenor_diminta' => 3,
            'persentase_bunga' => 1.0,
            'keperluan' => 'Keperluan awal pengajuan',
            'snapshot_bank' => $rek1->nama_bank,
            'snapshot_no_rekening' => $rek1->no_rekening,
            'snapshot_atas_nama' => $rek1->atas_nama,
            'status' => 'diajukan',
            'tanggal_pengajuan' => now(),
        ]);

        $this->actingAs($user);

        // Halaman edit dapat dibuka
        $this->get(route('portal.pinjaman.edit', $pinjaman->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Portal/Pinjaman/Create')
                ->where('modeEdit', true)
                ->where('pinjamanEdit.id', $pinjaman->id)
                ->where('pinjamanEdit.akan_reset_verifikasi', false));

        // cekNominal mengabaikan pinjaman yg sedang diedit
        $this->postJson(route('portal.pinjaman.cek-nominal'), [
            'nominal' => 2_000_000,
            'pinjaman_id' => $pinjaman->id,
        ])->assertOk()->assertJson(['valid' => true]);

        // PIN salah -> ditolak
        $this->put(route('portal.pinjaman.update', $pinjaman->id), [
            'nominal' => 2_000_000,
            'tenor_bulan' => 4,
            'keperluan' => 'Revisi nominal menjadi dua juta',
            'rekening_mode' => 'tersimpan',
            'rekening_id' => $rek2->id,
            'persetujuan' => true,
            'pin' => '000000',
        ])->assertSessionHasErrors('pin');

        // Edit valid -> tersimpan, jumlah_edit naik, tidak kirim WA
        $this->put(route('portal.pinjaman.update', $pinjaman->id), [
            'nominal' => 2_000_000,
            'tenor_bulan' => 4,
            'keperluan' => 'Revisi nominal menjadi dua juta',
            'rekening_mode' => 'tersimpan',
            'rekening_id' => $rek2->id,
            'persetujuan' => true,
            'pin' => '246810',
        ])->assertRedirect(route('portal.dashboard'));

        $pinjaman->refresh();
        $this->assertEquals(2_000_000, (float) $pinjaman->nominal);
        $this->assertEquals(4, (int) $pinjaman->tenor_bulan);
        $this->assertEquals('Revisi nominal menjadi dua juta', $pinjaman->keperluan);
        $this->assertEquals('Mandiri', $pinjaman->snapshot_bank);
        $this->assertEquals('444555666', $pinjaman->snapshot_no_rekening);
        $this->assertEquals(1, $pinjaman->jumlah_edit);
        $this->assertNotNull($pinjaman->diedit_pada);
        $this->assertEquals('diajukan', $pinjaman->status);
        $this->assertDatabaseHas('audit_log', ['aksi' => 'pinjaman_diedit']);
        Queue::assertNothingPushed();
    }

    public function test_edit_saat_approved_admin_atau_approved_bendahara_otomatis_reset_ke_tahap_awal(): void
    {
        $anggota = $this->buatAnggota('TOP-920002');
        $user = $anggota->user;
        $this->pasangPin($user);

        $rek = RekeningAnggota::create([
            'anggota_id' => $anggota->id,
            'nama_bank' => 'BRI',
            'no_rekening' => '777888999',
            'atas_nama' => $anggota->nama,
            'is_default' => true,
        ]);

        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $user->id,
            'nominal' => 1_500_000,
            'nominal_diminta' => 1_500_000,
            'tenor_bulan' => 3,
            'tenor_diminta' => 3,
            'nominal_disetujui_bendahara' => 1_200_000,
            'tenor_disetujui_bendahara' => 3,
            'kas_saldo_bendahara' => 50_000_000,
            'persentase_bunga' => 1.0,
            'keperluan' => 'Modal awal usaha',
            'snapshot_bank' => $rek->nama_bank,
            'snapshot_no_rekening' => $rek->no_rekening,
            'snapshot_atas_nama' => $rek->atas_nama,
            'status' => 'approved_bendahara',
            'catatan_admin' => 'Lolos Sekretaris',
            'catatan_bendahara' => 'Usulan 1.2jt oleh Bendahara',
            'tanggal_pengajuan' => now(),
        ]);

        $this->actingAs($user);

        $this->get(route('portal.pinjaman.edit', $pinjaman->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('pinjamanEdit.akan_reset_verifikasi', true));

        $this->put(route('portal.pinjaman.update', $pinjaman->id), [
            'nominal' => 1_800_000,
            'tenor_bulan' => 4,
            'keperluan' => 'Revisi setelah approved bendahara',
            'rekening_mode' => 'tersimpan',
            'rekening_id' => $rek->id,
            'persetujuan' => true,
            'pin' => '246810',
        ])->assertRedirect(route('portal.dashboard'));

        $pinjaman->refresh();
        $this->assertEquals('diajukan', $pinjaman->status);
        $this->assertEquals(1_800_000, (float) $pinjaman->nominal);
        $this->assertEquals(4, (int) $pinjaman->tenor_bulan);
        $this->assertNull($pinjaman->nominal_disetujui_bendahara);
        $this->assertNull($pinjaman->tenor_disetujui_bendahara);
        $this->assertNull($pinjaman->kas_saldo_bendahara);
        $this->assertNull($pinjaman->catatan_admin);
        $this->assertNull($pinjaman->catatan_bendahara);
    }

    public function test_pemohon_tidak_bisa_edit_atau_batal_setelah_approved_ketua_atau_aktif(): void
    {
        $anggota = $this->buatAnggota('TOP-920003');
        $user = $anggota->user;
        $this->pasangPin($user);

        $rek = RekeningAnggota::create([
            'anggota_id' => $anggota->id,
            'nama_bank' => 'BCA',
            'no_rekening' => '12345678',
            'atas_nama' => $anggota->nama,
            'is_default' => true,
        ]);

        $pinjamanKetua = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $user->id,
            'nominal' => 1_000_000,
            'tenor_bulan' => 3,
            'persentase_bunga' => 1.0,
            'keperluan' => 'Sudah disetujui ketua',
            'snapshot_bank' => $rek->nama_bank,
            'snapshot_no_rekening' => $rek->no_rekening,
            'snapshot_atas_nama' => $rek->atas_nama,
            'status' => 'approved_ketua',
            'tanggal_pengajuan' => now(),
            'disetujui_ketua_pada' => now(),
        ]);

        $this->actingAs($user);

        $this->get(route('portal.pinjaman.edit', $pinjamanKetua->id))
            ->assertRedirect(route('portal.dashboard'));

        $this->put(route('portal.pinjaman.update', $pinjamanKetua->id), [
            'nominal' => 1_200_000,
            'tenor_bulan' => 3,
            'keperluan' => 'Mencoba edit saat approved ketua',
            'rekening_mode' => 'tersimpan',
            'rekening_id' => $rek->id,
            'persetujuan' => true,
            'pin' => '246810',
        ])->assertSessionHasErrors('pengajuan');

        $this->post(route('portal.pinjaman.batal', $pinjamanKetua->id), [
            'alasan' => 'Ingin batal sendiri',
            'pin' => '246810',
        ])->assertSessionHasErrors('batal');

        $this->assertEquals('approved_ketua', $pinjamanKetua->fresh()->status);
    }

    public function test_batal_mandiri_mengubah_status_dibatalkan_dan_memulihkan_privilege_reloan(): void
    {
        $anggota = $this->buatAnggota('TOP-920004');
        $user = $anggota->user;
        $this->pasangPin($user);

        $rek = RekeningAnggota::create([
            'anggota_id' => $anggota->id,
            'nama_bank' => 'BCA',
            'no_rekening' => '99887766',
            'atas_nama' => $anggota->nama,
            'is_default' => true,
        ]);

        // Pinjaman lama dengan 1 angsuran tersisa + flag privilege reloan sudah terpakai karena ada pengajuan reloan
        $pinjamanLama = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $user->id,
            'nominal' => 1_000_000,
            'tenor_bulan' => 2,
            'persentase_bunga' => 1.0,
            'keperluan' => 'Pinjaman sebelumnya',
            'snapshot_bank' => $rek->nama_bank,
            'snapshot_no_rekening' => $rek->no_rekening,
            'snapshot_atas_nama' => $rek->atas_nama,
            'status' => 'aktif',
            'sudah_pakai_privilege_reloan' => true,
            'tanggal_pengajuan' => now()->subMonths(2),
            'tanggal_pencairan' => now()->subMonths(2),
        ]);
        Angsuran::create([
            'pinjaman_id' => $pinjamanLama->id,
            'cicilan_ke' => 1,
            'nominal_pokok' => 500_000,
            'nominal_bunga' => 10_000,
            'total_bayar' => 510_000,
            'status' => 'lunas',
            'tanggal_jatuh_tempo' => now()->subMonth(),
            'tanggal_konfirmasi_bayar' => now()->subMonth(),
        ]);
        Angsuran::create([
            'pinjaman_id' => $pinjamanLama->id,
            'cicilan_ke' => 2,
            'nominal_pokok' => 500_000,
            'nominal_bunga' => 5_000,
            'total_bayar' => 505_000,
            'status' => 'belum_bayar',
            'tanggal_jatuh_tempo' => now()->addMonth(),
        ]);

        $reloanDiajukan = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $user->id,
            'nominal' => 1_000_000,
            'tenor_bulan' => 3,
            'persentase_bunga' => 1.0,
            'keperluan' => 'Reloan yang akan dibatalkan',
            'snapshot_bank' => $rek->nama_bank,
            'snapshot_no_rekening' => $rek->no_rekening,
            'snapshot_atas_nama' => $rek->atas_nama,
            'status' => 'approved_bendahara',
            'tanggal_pengajuan' => now(),
        ]);

        $this->actingAs($user);

        $this->post(route('portal.pinjaman.batal', $reloanDiajukan->id), [
            'alasan' => 'Salah pilih tenor cicilan',
            'pin' => '246810',
        ])->assertRedirect(route('portal.dashboard'));

        $reloanDiajukan->refresh();
        $pinjamanLama->refresh();

        $this->assertEquals('dibatalkan', $reloanDiajukan->status);
        $this->assertEquals($user->id, $reloanDiajukan->dibatalkan_oleh);
        $this->assertEquals('Salah pilih tenor cicilan', $reloanDiajukan->alasan_batal);
        $this->assertNotNull($reloanDiajukan->dibatalkan_pada);

        // Kuota reloan pulih kembali sehingga bisa mengajukan baru lagi
        $this->assertFalse($pinjamanLama->sudah_pakai_privilege_reloan);

        Queue::assertPushed(KirimWaJob::class, function ($job) {
            return $this->propertiWa($job)['event'] === 'pinjaman_dibatalkan';
        });

        $this->assertDatabaseHas('audit_log', ['aksi' => 'pinjaman_dibatalkan_pemohon']);
    }

    public function test_bendahara_dapat_membatalkan_pencairan_approved_ketua_tanpa_mengurangi_kas(): void
    {
        $anggota = $this->buatAnggota('TOP-920005');
        $kasAwal = (float) KasKoperasi::firstOrFail()->saldo_bank;

        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $anggota->user_id,
            'nominal' => 2_500_000,
            'nominal_disetujui' => 2_500_000,
            'tenor_bulan' => 5,
            'tenor_disetujui' => 5,
            'persentase_bunga' => 1.0,
            'keperluan' => 'Biaya renovasi',
            'snapshot_bank' => 'BCA',
            'snapshot_no_rekening' => '10101010',
            'snapshot_atas_nama' => $anggota->nama,
            'status' => 'approved_ketua',
            'tanggal_pengajuan' => now(),
            'disetujui_ketua_pada' => now(),
        ]);

        $bendahara = $this->masuk('BEN-000001');

        $this->post(route('bendahara.pencairan-pinjaman.batal', $pinjaman->id), [
            'catatan' => 'Anggota konfirmasi batal pinjam sebelum transfer',
        ])->assertRedirect(route('bendahara.pencairan-pinjaman.index'));

        $pinjaman->refresh();
        $this->assertEquals('dibatalkan', $pinjaman->status);
        $this->assertEquals($bendahara->id, $pinjaman->dibatalkan_oleh);
        $this->assertEquals('Anggota konfirmasi batal pinjam sebelum transfer', $pinjaman->alasan_batal);
        $this->assertEquals($kasAwal, (float) KasKoperasi::firstOrFail()->saldo_bank);

        $audit = AuditLog::where('aksi', 'pinjaman_pencairan_dibatalkan')->first();
        $this->assertNotNull($audit);

        Queue::assertPushed(KirimWaJob::class, function ($job) {
            return $this->propertiWa($job)['event'] === 'pinjaman_dibatalkan';
        });
    }

    public function test_api_edit_dan_batal_pengajuan_pinjaman_bekerja_sesuai_kontrak(): void
    {
        $anggota = $this->buatAnggota('TOP-920006');
        $user = $anggota->user;
        $this->pasangPin($user, '246810');

        $rek = RekeningAnggota::create([
            'anggota_id' => $anggota->id,
            'nama_bank' => 'BNI',
            'no_rekening' => '55667788',
            'atas_nama' => $anggota->nama,
            'is_default' => true,
        ]);

        $pinjaman = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $user->id,
            'nominal' => 1_000_000,
            'tenor_bulan' => 3,
            'persentase_bunga' => 1.0,
            'keperluan' => 'Awal API',
            'snapshot_bank' => $rek->nama_bank,
            'snapshot_no_rekening' => $rek->no_rekening,
            'snapshot_atas_nama' => $rek->atas_nama,
            'status' => 'approved_admin',
            'catatan_admin' => 'Sudah dicek Sekretaris',
            'tanggal_pengajuan' => now(),
        ]);

        Sanctum::actingAs($user);

        // Edit via API
        $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->putJson("/api/v1/pinjaman/{$pinjaman->id}", [
                'nominal' => 1_500_000,
                'tenor_bulan' => 4,
                'keperluan' => 'Revisi keperluan lewat API mobile',
                'rekening_mode' => 'tersimpan',
                'rekening_id' => $rek->id,
                'persetujuan' => true,
                'pin' => '246810',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'diajukan')
            ->assertJsonPath('data.nominal', 1_500_000)
            ->assertJsonPath('data.jumlah_edit', 1);

        // Batal via API
        $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson("/api/v1/pinjaman/{$pinjaman->id}/batal", [
                'alasan' => 'Batal via mobile',
                'pin' => '246810',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'dibatalkan')
            ->assertJsonPath('data.alasan_batal', 'Batal via mobile');

        // Bendahara membatalkan pencairan approved_ketua via API pengurus reject
        $pinjamanKetua = Pinjaman::create([
            'anggota_id' => $anggota->id,
            'pengaju_user_id' => $user->id,
            'nominal' => 1_000_000,
            'nominal_disetujui' => 1_000_000,
            'tenor_bulan' => 3,
            'tenor_disetujui' => 3,
            'persentase_bunga' => 1.0,
            'keperluan' => 'Menunggu pencairan API',
            'snapshot_bank' => $rek->nama_bank,
            'snapshot_no_rekening' => $rek->no_rekening,
            'snapshot_atas_nama' => $rek->atas_nama,
            'status' => 'approved_ketua',
            'tanggal_pengajuan' => now(),
            'disetujui_ketua_pada' => now(),
        ]);

        $bendahara = User::where('no_karyawan', 'BEN-000001')->firstOrFail();
        Sanctum::actingAs($bendahara);

        $this->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson("/api/v1/pengurus/pinjaman/{$pinjamanKetua->id}/reject", [
                'catatan' => 'Batal cair sebelum transfer mobile',
            ])
            ->assertOk()
            ->assertJsonPath('pinjaman.status', 'dibatalkan')
            ->assertJsonPath('pinjaman.alasan_batal', 'Batal cair sebelum transfer mobile');
    }
}
