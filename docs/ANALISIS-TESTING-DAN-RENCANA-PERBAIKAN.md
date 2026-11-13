# Analisis Test Suite & Rencana Perbaikan (Testing-Only)

> Status: **418/418 test HIJAU** (sebelumnya 27 gagal + 13 error = 40 merah).
> Prinsip: **tidak mengubah alur/perilaku produksi yang sudah berjalan**; hanya memperbaiki *testing layer* + dua cacat yang secara nyata memblokir produksi (lihat §5).

---

## 1. Ringkasan Temuan

| Aspek | Sebelum | Sesudah |
|---|---|---|
| Total test | 418 | 418 |
| Lulus | 378 | **418** |
| Gagal (failures) | 27 | 0 |
| Error | 13 | 0 |
| Skipped | 0 | 0 |
| Waktu suite | ~25 mnt | ~25 mnt |

### "Apakah ada unit test yang tidak digunakan?"
Hasil audit (jawaban langsung):
- **0** test di-skip / incomplete (`markTestSkipped`/`@group skip`/`@depends` = tidak ada).
- **0** method uji yang tak ikut konvensi PHPUnit (225 method, semua `test_*`).
- **0** test tanpa assertion (semua punya assert).
- **0** helper trait mati — `masuk` 162×, `buatAnggota` 202×, `propertiWa` 3×.
- `tests/e2e/` bukan mati — dipakai `npm run docs:screenshots` (Playwright).
- **Duplikat nyata: 1** → `test_login_diblokir_saat_mode_sso` identik di `AnggotaApiTest` & `MobileAuthTest` (fixture beda). Kandidat dedupe (opsional, tidak memblokir).
- 2× `ExampleTest` (scaffold Laravel) — nilai rendah, boleh dihapus.

**Kesimpulan:** tidak ada "test tak terpakai"; masalah sebenarnya adalah **suite merah yang menumpuk diam-diam** karena **tidak ada CI**.

---

## 2. Alur Existing yang Dianalisa (mengapa test bisa merah)

1. **Runner**: `phpunit.xml` hanya menjalankan `tests/Unit` + `tests/Feature`, DB `sqlite :memory:`, `migrate:fresh` tiap test (tidak ada schema dump).
2. **Seeder**: `$this->seed()` menjalankan `DatabaseSeeder` penuh (User/Role/Org/Kas/Anggota). `KasKoperasiSeeder` & `AnggotaSeeder` menulis `JurnalKas` dengan tanggal **relatif `now()`**.
3. **Auth/RBAC**: route dijaga `permission:*` (Spatie). Sejak **RBAC split** (bendahara/ketua/super_admin), banyak izin **berpindah** dari role `admin`.
4. **Gate Sync**: `SinkronisasiAnggotaService`/`SinkronisasiMasterService` menulis `users.gate_id`, padahal **tidak ada migrasi kolom `gate_id` di tabel `users`**.
5. **Strict mode**: `Model::shouldBeStrict(! isProduction())` → lazy-load & atribut tak ada langsung melempar exception (bagus untuk menangkap bug).
6. **Tidak ada CI**: `.github/` tidak ada; `composer test` = `php artisan test` manual → merah lolos diam-diam.

---

## 3. Akar Masalah (root cause) per Kategori

| # | Kategori | File test | Akar masalah (terverifikasi) | Sifat |
|---|---|---|---|---|
| 1 | **Schema drift `users.gate_id`** | SinkronGate(8), SinkronMaster(2), SinkronSemuaGate(5), SsoCallback(2) | `User::$fillable` + service sinkron menulis `gate_id`, tapi **tak ada migrasi** kolom `gate_id` di tabel `users`. Error `table users has no column named gate_id`, lalu merembet: create Anggota gagal → `No query results for Anggota`, `gagal-enrich`, `count_diperbarui:0`. **Nol migrasi hilang di git** = cacat sejak awal. | Infrastruktur/produksi |
| 2 | **RBAC stale (test usang)** | KasTopupPengeluaran(7), LimitDinamis(3), SettingChipNominal(2), PengaturanWa(4), AnggotaResignReaktivasi(1) | Test login sebagai `ADM-000001` (role `admin`) mengharap `kas.topup`/`limit.*`/`chip`/`pengaturan.wa`/`angsuran.konfirmasi`, padahal pasca RBAC-split izin itu milik **bendahara / ketua / super_admin** → 403. | Test usang |
| 3 | **Bug produksi: tambah anggota 500** | AnggotaCrud(3) | `StoreAnggotaRequest` mewajibkan `email`; `AnggotaController::store()` menyebar `...$request->validated()` (termasuk `email`) ke `Anggota::create()`, tetapi tabel `anggota` **tak punya kolom `email`** → `MassAssignmentException` → **500 saat tambah anggota di produksi**. | Bug produksi |
| 4 | Profil 500 | ProfileTest(1) | `/profile` → `MissingAttributeException: no_karyawan` karena `User::factory()` tanpa `no_karyawan`. | Setup test |
| 5 | **Regresi logika/ekspektasi** | KasKoperasiHalaman(1), AnggotaResignReaktivasi(1), MobilePengajuan(1), SinkronGate(1), SsoCallback(2) | Kas: rentang tanggal hard-code `2024-05` bertabrakan dengan jurnal seed relatif `now()` (simpanan pokok `now()->subYears(2)->subMonths(6)` masuk rentang) → agregat meleset 50rb. Resign: langkah konfirmasi angsuran butuh `angsuran.konfirmasi` (bendahara), masih `ADM`. Limit: teks pesan guard beda. `harus_ganti_password`: sejak SSO-only bernilai `false`, test masih mengharap `true`. | Campur (lihat §5) |

---

## 4. Alur Perbaikan (urutan eksekusi)

1. **Kategori 1** — tambah migrasi `users.gate_id` (aditif, nullable). Satu migrasi ini menutup 4 file (SinkronGate/Master/SemuaGate/SsoCallback).
2. **Kategori 2** — tukar aktor test ke role yang benar sesuai RBAC sekarang.
3. **Kategori 3** — jangan teruskan `email` ke `Anggota::create()` (bug 500).
4. **Kategori 4** — `no_karyawan` di factory user pada test.
5. **Kategori 5** — perbaiki ekspektasi/tanggal yang rapuh + aktor konfirmasi.
6. **Verifikasi** per-file → lalu **seluruh suite** + build.

---

## 5. Patch (ringkas per file)

### 5a. Perbaikan PRODUKSI (2 file, minimal & terisolasi)

**(P1) `database/migrations/2026_11_13_000000_add_gate_id_to_users_table.php`** — *baru*
`users.gate_id` memang dipakai `User::$fillable` + seluruh service sinkron, tetapi kolomnya tidak pernah dimigrasikan. Ini cacat skema nyata. Migrasi **aditif & nullable** (guard `hasColumn`) → tidak mengubah perilaku; hanya melengkapi kolom yang seharusnya ada.
```php
// up()
if (! Schema::hasColumn('users', 'gate_id')) {
    Schema::table('users', function (Blueprint $table) {
        $table->string('gate_id')->nullable()->after('no_karyawan');
    });
}
```

**(P2) `app/Http/Controllers/AnggotaController.php::store()`** — *perbaiki bug 500*
```php
$user->assignRole('anggota');

// `email` hanya milik akun User; tabel anggota tidak punya kolom ini.
$dataAnggota = $request->validated();
unset($dataAnggota['email']);

$anggota = Anggota::create([
    ...$dataAnggota,   // sebelumnya: ...$request->validated()
    'user_id' => $user->id,
    ...
]);
```
> Efek: jalur **sukses** "Tambah Anggota" yang tadinya **500** kini berfungsi. Tidak ada perilaku lain yang berubah.

### 5b. Perbaikan TEST-ONLY (9 file)

| File | Perubahan |
|---|---|
| `KasTopupPengeluaranTest.php` | `masuk('ADM-000001')` → `masuk('BEN-000001')` (bendahara punya `kas.topup`). |
| `LimitDinamisTest.php` | helper `sebagaiAdmin()` → `masuk('KET-000001')` (ketua: `pengaturan.kebijakan.kelola`). |
| `SettingChipNominalTest.php` | helper `sebagaiAdmin()` → `masuk('KET-000001')`. |
| `PengaturanWaTest.php` | `setUp` → `masuk('SPADM-000001')` (hanya super_admin punya `pengaturan.wa`). |
| `AnggotaResignReaktivasiTest.php` | sebelum langkah `bendahara.angsuran.konfirmasi`, tambah `masuk('BEN-000001')` (bendahara: `angsuran.konfirmasi`). |
| `Api/MobilePengajuanTest.php` | ekspektasi pesan 422 → `'Anda masih memiliki pengajuan limit yang belum diproses.'` (sesuai `EligibilitasPinjamanService`). |
| `AnggotaCrudTest.php` | ekspektasi `harus_ganti_password` `true` → `false` (SSO-only). |
| `SinkronGateTest.php` | ekspektasi `harus_ganti_password` `true` → `false` (provisioning SSO-only). |
| `ProfileTest.php` | `User::factory()->create(['no_karyawan' => 'PROF-000001'])`. |
| `KasKoperasiHalamanTest.php` | rentang tanggal hard-code `2024-05` → `2022-05` (hindari tabrakan dengan jurnal seed relatif `now()`). |

---

## 6. Bukti Verifikasi

Per-file (sebelum → sesudah):
- KasTopupPengeluaran 10/17 → **17/17**
- SinkronGate 5/13 → **13/13**
- SinkronSemuaGate 5/10 → **10/10**
- PengaturanWa 0/4 → **4/4**
- AnggotaCrud 4/7 → **7/7**
- LimitDinamis 1/4 → **4/4**
- SettingChipNominal 2/4 → **4/4**
- SsoCallback 13/15 → **15/15**
- SinkronMaster 6/8 → **8/8**
- KasKoperasiHalaman 16/17 → **17/17**
- Profile 2/3 → **3/3**
- AnggotaResignReaktivasi 5/6 → **6/6**
- MobilePengajuan 12/13 → **13/13**

Full suite: **`{"result":"passed","tests":418,"passed":418,"assertions":2557}`**

---

## 7. Rekomendasi Lanjutan (pencegahan)

1. **Tambah CI** (`.github/workflows/tests.yml`) menjalankan `composer test` + `npm run build` pada PR — agar merah tidak menumpuk diam-diam.
2. **Perbaiki "time-bomb" tanggal di seeder**: `KasKoperasiSeeder`/`AnggotaSeeder` memakai `now()->subYears(2)...` yang bisa bertabrakan dengan rentang tanggal hard-code di test. Idealnya test memakai tanggal yang dijamin unik / seeder memakai tanggal fixed yang jauh. (Saat ini sudah dihindari di sisi test `2022-05`.)
3. **Dedupe** `test_login_diblokir_saat_mode_sso` (identik di 2 file) dan hapus `ExampleTest` scaffold.
4. Pertimbangkan memindahkan izin `pengaturan.wa` bila memang perlu diakses lebih luas (saat ini super_admin saja) — keputusan domain, bukan bagian perbaikan test.
