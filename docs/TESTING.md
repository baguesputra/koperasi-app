# Panduan Testing

Aplikasi ini menggunakan **PHPUnit 12** (via `php artisan test`) dengan 18 file test, **110 kasus / 411 assertion**, menghindari dependency eksternal (tidak butuh Docker, MySQL, queue, atau WhatsApp gateway).

---

## Menjalankan Test

```bash
# Semua test (≈5 menit)
php artisan test

# Satu file
php artisan test tests/Feature/PinjamanApprovalTest.php

# Beberapa file sekaligus
php artisan test tests/Feature/LaporanTest.php tests/Feature/KirimWaTest.php

# Satu fungsi tertentu
php artisan test --filter=test_alur_lengkap_anggota_hingga_cair_ketua_dengan_wa_pdf

# Alternatif langsung vendor
vendor/bin/phpunit
```

---

## Lingkungan Test

| Elemen | Nilai |
|--------|-------|
| Database | **SQLite in-memory** (`:memory:`) — didefinisikan di `phpunit.xml` |
| Migrasi | `RefreshDatabase` → fresh migrate + seed tiap test class |
| Seed | `$this->seed()` di `setUp()` tiap class (full seed = permission, role, user, setting, anggota, dll.) |
| Queue | `Queue::fake()` — job WhatsApp diuji via payload |
| HTTP | `Http::fake()` untuk gateway BAILEYS |
| Storage | `Storage::fake('local')` untuk PDF & Excel |

> **Tidak perlu** menjalankan `docker compose up`, worker queue, atau gateway BAILEYS — seluruh sistem diuji isolasi.

---

## Konvensi Penulisan

### 1. Trait `tests/Concerns/MembuatDataUji`
Kumpulan helper standar, **wajib** dipakai:

```php
use Tests\Concerns\MembuatDataUji;

class MyTest extends TestCase
{
    use MembuatDataUji;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();              // wajib: permission, role, user seeder
    }

    public function test_something(): void
    {
        $this->masuk('BEN-000001');          // login via no_karyawan
        $anggota = $this->buatAnggota();      // Anggota lengkap + user + role 'anggota'
        // ...
    }
}
```

**Helper tersedia:**
| Method | Fungsi |
|--------|--------|
| `masuk('KET-000001')` | Login sebagai user seeder (`BEN-000001`, `KET-000001`, `ADM-000001`, dll.) |
| `buatAnggota('TOP-999999', ['tanggal_jadi_anggota' => ...])` | Buat Anggota lengkap (User, role anggota, no_hp, status aktif) |
| `propertiWa($job)` | Extract payload `KirimWaJob` → `['noHp','event','pesan','dokumen']` |

---

### 2. Pola WA & Antrean
```php
Queue::fake();
$this->post(route('ketua.pinjaman.approve', $pinjaman), ['catatan' => 'Ok'])
    ->assertStatus(302);

Queue::assertPushed(KirimWaJob::class, function ($job) use ($pinjaman) {
    $p = $this->propertiWa($job);
    return $p['event'] === 'pinjaman_disetujui_ketua'
        && ($p['dokumen']['filename'] ?? '') === "Bukti-Peminjaman-{$pinjaman->id}.pdf";
});
```

---

### 3. Asersi Uang (wajib via Jurnal & Saldo)
Jangan hanya cek status — uang pasti bocor jika hanya cek status.

```php
$saldoSebelum = (float) KasKoperasi::first()->saldo_pinjaman;

$this->post(route('bendahara.angsuran.konfirmasi'), [
    'angsuran_ids' => ['n-'.$angsuran->id],
]);

$this->assertEquals($saldoSebelum + 309_000, (float) KasKoperasi::first()->saldo_pinjaman);
$this->assertDatabaseHas('jurnal_kas', [
    'kategori' => 'pembayaran_angsuran', 'tipe' => 'masuk',
    'jumlah' => 309_000, 'referensi_id' => $angsuran->id,
]);
```

**Tip anti-tabrakan ID jurnal seeder:**
```php
$jurnalSebelum = (int) (JurnalKas::max('id') ?? 0);
$this->post(route('bendahara.angsuran.konfirmasi'), ['angsuran_ids' => $ids]);
$totalBayar = JurnalKas::where('kategori', 'pembayaran_angsuran')
    ->where('id', '>', $jurnalSebelum)->sum('jumlah');
```

---

### 4. Arsitektur Test Class (template)
```php
<?php

namespace Tests\Feature;

use App\Models\Pinjaman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class FeatureNameTest extends TestCase
{
    use RefreshDatabase;
    use MembuatDataUji;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    // test_... methods
}
```

---

## Peta File Test (18 file)

| File | Fitur Utama | Kasus Utama |
|------|-------------|-------------|
| `PinjamanApprovalTest` | Alur persetujuan | Portal→Bendahara→Ketua, WA+PDF, rollback saldo kurang, tolak dua level |
| `AngsuranKonfirmasiTest` | Konfirmasi angsuran | Satuan/massal, auto-lunas pinjaman, badge percepatan (N+1 prefetch) |
| `PercepatanTest` | Perubahan tenor | Validasi, perpanjang/percepat/lunas-total, bulan berlaku, reject, 1×/pinjaman |
| `PengajuanLimitTest` | Kenaikan limit | Validasi >limit kini, dedup, setujui→limit_custom+AuditLog, tolak |
| `SimpananKonfirmasiTest` | Konfirmasi simpanan | Massal wajib+sosial+jurnal, dedup periode otomatis |
| `AnggotaResignReaktivasiTest` | Resign/Reaktivasi | Settlement 600rb, blokir login resign, reaktivasi |
| `AnggotaCrudTest` | CRUD Anggota | Store (user+simpanan pokok+no_urut), update, gate permission, template |
| `KasTopupPengeluaranTest` | Kas & Pengeluaran | Topup (transit ditolak), pengeluaran per kantong, isolate dana sosial |
| `PortalRiwayatTest` | Portal Anggota | Isolasi data pinjaman & simpanan per anggota |
| `CetakBuktiTest` | Cetak bukti | Gate status aktif, data lengkap, regresi pembulatan Σpokok |
| `LaporanTest` | Modul Laporan | 13 laporan render/PDF/Excel, gate admin (audit), semua render |
| `KirimWaTest.php` | Gateway WA | Normalisasi nomor, log berhasil/gagal, dokumen temp dihapus |
| `PinjamanMandiriTest.php` | Alur mandiri | Bendahara/Ketua ajukan sendiri, antrean benar, approval |
| `PinjamanReloanTest.php` | Logika rel/limit | 8 kasus: privilege 1×, floor 0, anggota baru, no pinjaman aktif |
| `ResignServiceTest.php` | Service resign | Unit service pelunasan dari simpanan |
| `KirimWaTest.php` | Gateway WA | Normalisasi nomor, log, dokumen temp cleanup |
| `Auth\*` (6 files) | Autentikasi | Login (no_karyawan), logout, password reset, verifikasi email |
| `ProfileTest.php` | Profil | Halaman profil, update info |
| `ExampleTest.php` | Smoke | Root redirect ke login |

---

## Menambah Test Baru (Checklist 5 Langkah)

1. **Buat file** `tests/Feature/NamaFiturTest.php` (pakai `RefreshDatabase` + `MembuatDataUji`)
2. **Definisikan `setUp()`** → `parent::setUp(); $this->seed();`
3. **Gunakan helper** `masuk()`, `buatAnggota()`, `propertiWa()`, scope jurnal via `id > $maxIdBefore`
4. **Asersi uang** lewat `KasKoperasi::first()->saldo_*` + `JurnalKas::where('kategori', ...)`
5. **Jalankan & bersihkan**: `php artisan test tests/Feature/NamaFiturTest.php` → `./vendor/bin/pint`

---

## Contoh Skeleton Baru

```php
<?php

namespace Tests\Feature;

use App\Models\Pinjaman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class FiturBaruTest extends TestCase
{
    use RefreshDatabase;
    use MembuatDataUji;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_fitur_baru_bekerja(): void
    {
        $this->masuk('BEN-000001');
        $anggota = $this->buatAnggota();

        // Aksi
        $res = $this->post(route('rute.aksi'), [...]);
        $res->assertStatus(302);

        // Asersi uang
        $this->assertEquals(..., (float) KasKoperasi::first()->saldo_pinjaman);
        $this->assertDatabaseHas('jurnal_kas', [...]);
    }
}
```

---

## Men-debug Test Gagal

1. **Lihat trace lengkap** (tanpa framework noise):
   ```bash
   vendor/bin/phpunit tests/Feature/NamaTest.php --filter=nama_test 2>&1 | head -40
   ```

2. **Cek output Inertia props**:
   ```php
   $props = $res->viewData('page')['props'];
   dd($props['pinjaman'][0]['nominal']); // int vs float
   ```

3. **Simpan trace error ke file** (bypass output JSON):
   ```php
   try { ... } catch (\Throwable $e) {
       file_put_contents('/tmp/opencode/trace.log', get_class($e).': '.$e->getMessage()."\n".$e->getTraceAsString());
       throw $e;
   }
   ```

---

## Catatan Teknis Penting

| Topik | Detail |
|-------|--------|
| **SQLite enum** | Migrasi `2026_08_07_121227_create_jurnal_kas_table` pakai `string` (MySQL tetap ENUM via ALTER) |
| **Role seeder** | Role `anggota` dibuat ulang tiap test class via trait `MembuatDataUji` (fix `RoleDoesNotExist`) |
| **Login** | Form pakai `no_karyawan` + `password` (bukan email). Seeder: `BEN-000001`, `KET-000001`, `ADM-000001` |
| **RefreshDatabase** | Transaksi + rollback, **tidak** `migrate:fresh` tiap test (cepat) |
| **pdf/export** | Test pakai `assertOk()` + type check; file fisik tidak disimpan |

---

## Referensi Cepat

| Perintah | Kegunaan |
|----------|----------|
| `php artisan test --compact` | Ringkasan singkat (pass/fail) |
| `php artisan test --filter=test_nama` | Jalankan satu test |
| `./vendor/bin/pint` | Lint & fix style (PSR-12 + Laravel preset) |
| `php artisan test --compact 2>&1 \| tail -2` | Ringkasan akhir CI |

---

*Dokumen ini dihasilkan dari audit test suite penuh (110/110 hijau, 0 skip). Update bila konvensi berubah.*