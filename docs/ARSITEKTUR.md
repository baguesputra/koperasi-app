# Arsitektur Koperasi App

Memory permanen proyek. Sesi baru baca file ini + `AGENTS.md`, tanpa tanya ulang.
Rinci skema: `docs/ERD-Koperasi-App.md`. Rinci progres: `docs/DEVELOPMENT_LOG.md`.

## 1. Tech stack

- Bahasa/framework: PHP ^8.3, Laravel ^13.8, Inertia Laravel ^2.0 + `@inertiajs/react` ^2.0, React 18.2, Vite 8 + `laravel-vite-plugin` 3.1, Tailwind 3.2.1 (+forms), PostCSS, `@vitejs/plugin-react` 6.1.
- Database: MySQL `koperasi_db` lokal (`.env`); `.env.example` SQLite; tes SQLite `:memory:` (`phpunit.xml`). Migrasi wajib DB-agnostic.
- Auth/API: Sanctum ^4.0 (guard `api`), session guard `web`; Socialite ^5.29 + `socialiteproviders/manager` + `saml2` provider custom `perusahaan`; `AUTH_MODE=local|sso`; throttle `sso-callback`.
- Otorisasi: `spatie/laravel-permission` ^8.3; alias `role`, `permission`, `idempotent` di `bootstrap/app.php`.
- Frontend lib: `lucide-react`, `recharts`, `@headlessui/react`, `workbox-window` + `vite-plugin-pwa` (PWA, `NetworkOnly` untuk `/api/*`, denylist `/auth/sso`); Ziggy global (`@routes`, `route()` di JSX).
- Dokumen/data: `barryvdh/laravel-dompdf` (bukti pinjaman, slip resign, laporan PDF), `maatwebsite/excel` + `phpspreadsheet` (import/export), `endroid/qr-code` (verifikasi publik bukti), `darkaonline/l5-swagger` (docs API), `doctrine/dbal`.
- Background: queue `queue:listen`, `laravel/pail`, tabel `jobs` + `cache`; `WaService` + `baileys-service/` (notif WA antrean); PDF WA dibangkitkan via queue.
- Dev/test: `laravel/breeze`, `laravel/pint` (format), PHPUnit ^12.5, Faker, `concurrently`; `npm install --legacy-peer-deps`, `.npmrc ignore-scripts=true`.

## 2. Struktur folder

- `app/Http/Controllers/`: akar = admin/lihat (`Anggota`, `Pinjaman`, `Simpanan`, `KasKoperasi`, `Pengaturan`, `Role`, `Laporan`, `Pengeluaran`, `Migrasi`, `Verifikasi`, `Dashboard`, `Profile`); `Portal/` = layanan mandiri anggota; `Bendahara/` = tinjau + konfirmasi; `Ketua/` = approval final + limit; `API/` = JSON Sanctum (mobile/core lain); `Auth/SsoController` = SSO; `Pengaturan/PenggunaController`.
- `app/Services/`: `Pinjaman/` (7: `PengajuanPinjaman`, `EligibilitasPinjaman`, `PerhitunganBunga`, `PersetujuanPinjaman`, `KonfirmasiAngsuran`, `PercepatanPinjaman`, `PengajuanLimit`); `Keuangan/` (`JurnalKas`, `Pengeluaran`); `Simpanan/` (`KonfirmasiSimpanan`); `SSO/`, `Wa/` (`WaService`, `WaPesan`, `WaLog`), `Gate/`, `Migrasi/`, `Dokumen/` (`PenomoranDokumenService`), `Anggota/`.
- `app/Models/` (23): `User`, `Anggota`, `Pinjaman`, `Angsuran`, `AngsuranPercepatan`, `Simpanan`, `RekeningAnggota`, `PengajuanLimit`, `PengajuanPercepatan`, `KasKoperasi`, `JurnalKas`, `Pengeluaran`, `SettingBunga`, `SettingLimitPinjaman`, `SettingSimpanan`, `TabelTenor`, `AuditLog`, `PenomoranDokumen`, `WaLog`, master organisasi (`Perusahaan`, `Jabatan`, `Divisi`, `Departemen`) + cabang.
- `app/Jobs/`, `app/Laporan/`, `app/Exports/`, `app/Imports/`, `app/Helpers/` (`TerbilangHelper`), `app/OpenApi/`, `app/Providers/`, `app/Console/`.
- `bootstrap/app.php`: middleware web (`HandleInertiaRequests`, `EnsurePasswordChanged`), alias, mapping exception 403/404/419/500 ke `Errors/Error` (atau JSON modal bila header `X-Inertia`); `api/*` selalu JSON.
- `routes/`: `web.php` (portal, dashboard, lihat per-permission, laporan, topup, kelola anggota, resign, pengaturan + `password.confirm`, bendahara, ketua, migrasi, verifikasi publik QR); `api.php` (guest register/login; Sanctum resource `pengajuan-anggota`, `pinjaman`, `pengajuan-limit`, `percepatan` + cek/sim Barack/preview + dashboard stats/actionable/charts/aktivitas + master-data); `auth.php`, `console.php`.
- `resources/js/`: `app.jsx` (`createInertiaApp` + `ErrorModalProvider` + Workbox register PROD saja); `Pages/` cerminkan namespace route (`Portal/`, `Bendahara/`, `Ketua/`, `Anggota/`, `Pinjaman/`, `Simpanan/`, `KasKoperasi/`, `Laporan/`, `Pengaturan/`, `Migrasi/`, `Auth/`, `Errors/`); `Layouts/` (`App`, `Authenticated`, `Sidebar`, `Navbar`, `Anggota`, `Guest`) + `Partials/` (`Sidebar`, `Topbar`, `Navbar`, `MobileNav`); `Components/`, `Utils/`.
- `database/`: 56 migrasi evolutif (kas multi-kantong, resign, percepatan, idempotency, WA log, master organisasi, foto, gate_id); `seeders/` (`PermissionSeeder`, akun demo); `factories/`, `export/`.
- `config/`: `auth.mode`, `koperasi.php` (identitas + kota TTD), `syarat_pinjaman.php` (versi S&K), `idempotency.php`, `cabang.php`, `department.php`, `sanctum`, `queue`, `l5-swagger`.
- Lain: `docs/` (ERD, log, SSO-SAML2, TESTING, manual), `baileys-service/`, `tests/`, `public/images/logo.png` (logo sidebar + ikon PWA).

## 3. Alur request/data

- Web Inertia: React `route(nama)` (Ziggy) → middleware `auth` + `permission:` + `idempotent` (POST penting) + `EnsurePasswordChanged` + `HandleInertiaRequests` (share `auth.user` roles/permissions, `flash`, `notifications` count antrean) → Controller tipis → Service (`DB::transaction` + `lockForUpdate`) → Eloquent → MySQL → `Inertia::render` props → halaman React. Sidebar filter menu dari `auth.permissions`; badge dari `notifications`.
- Pinjaman: `PortalPinjamanService::ajukan` (cek `Eligibilitas`, limit tersedia, `tenorMaksimal`, snapshot rekening + `persentase_bunga` + `versi_syarat` + IP/UA, WA) status `diajukan` (pengecualian: bendahara mandiri langsung `approved_bendahara`; ketua mandiri `diajukan` + `cair_oleh_bendahara=true`) → Bendahara approve/reject (`PersetujuanPinjamanService` + `AuditLog::catat` + WA, catatan wajib) → Ketua approve → `cairkan()`: `aktif` + `tanggal_pencairan` + `nomor_dokumen`, `simpanJadwal` bunga menurun, `JurnalKasService::catat` keluar kantong `pinjaman` (gagal bila saldo kurang), audit, WA + PDF bukti → konfirmasi angsuran (`KonfirmasiAngsuranService`, masuk kantong `pinjaman`) → cicilan terakhir → `lunas` otomatis. Jalur khusus: `cairBendahara` untuk pengajuan mandiri Ketua.
- Kas: `JurnalKasService::catat` / `transferAntarKantong` satu-satunya pintu ubah saldo; peta `KANTONG_SALDO` (pinjaman, dana_sosial, pengembalian_simpanan/transit, simpanan); `lockForUpdate`, validasi saldo (kecuali transit), tulis `saldo_setelah` + `saldo_awal` untuk audit/rekonsiliasi.
- Simpanan: konfirmasi massal Bendahara (`KonfirmasiSimpananService`) hasilkan 2 baris per anggota (Wajib + Dana Sosial); dana sosial disembunyikan dari riwayat anggota.
- Auth: login `no_karyawan` + password (`LoginRequest`); flag `harus_ganti_password`; anggota redirect `portal.dashboard`, pengurus `dashboard`; mode SSO via Gate SAML2 (`sso.redirect`/`callback`, `/` redirect ke SSO bila guest).
- API mobile: token Sanctum → `apiResource` JSON → Service sama → OpenAPI; PWA `NetworkOnly` untuk API.

## 4. Pattern/konvensi

- Bahasa Indonesia untuk UI, model/kolom, route, komentar.
- Controller per role; logika bisnis di `app/Services`, tanpa inline di controller.
- Route grup per `permission:`; tambah route = tambah permission seeder + item sidebar.
- `Pages/` mirror namespace route; pola tab Approval + Riwayat di 3 halaman Bendahara/Ketua.
- POST ganda-dampak pakai `idempotent`; proses kas pakai `lockForUpdate` + transaksi atomic; agregat dashboard pakai `GROUP BY` (hindari N+1).
- Snapshot harga/aturan saat transaksi (bunga, tenor, rekening, nomor dokumen, versi S&K) agar perubahan pengaturan tak ubah data lama; bunga menurun (declining balance).
- Perubahan pengaturan + approval via `AuditLog::catat`; error kas eksplisit Bahasa Indonesia dengan saldo saat ini.
- Pint preset Laravel; Editorconfig 4 spasi LF; akun seeder password = `no_karyawan` (`ADM-000001`, `BEN-000001`, `KET-000001`, `TOP-100001`–`TOP-100004`).
- `DEVELOPMENT_LOG.md` checklist hidup: belum centang = belum jadi (mis. validasi 3 bulan / simpanan pokok 1 tahun masih TODO).

## 5. Dependensi modul

- `User` ↔ `Anggota` (`user_id` + `no_karyawan` + `gate_id` + master `Perusahaan`/cabang/unit/`Jabatan`/`Divisi`/`Departemen` + foto); tambah anggota otomatis buat user.
- `Anggota` → `Simpanan` / `RekeningAnggota` / `Pinjaman` → `Angsuran` (+`AngsuranPercepatan`) / `PengajuanLimit` / `PengajuanPercepatan`.
- `Pinjaman` butuh `SettingBunga` (snapshot), `TabelTenor`, `SettingLimitPinjaman` (+`limit_custom`), `SettingSimpanan`, `syarat_pinjaman.versi`; pencairan butuh `KasKoperasi` + `JurnalKas` + `PenomoranDokumen` + jadwal `Angsuran`.
- `KasKoperasi` (1 baris, multi-kolom kantong) ↔ `JurnalKas` ↔ `Pengeluaran`; dashboard/laporan/migrasi agregat semua modul; verifikasi QR publik baca `Pinjaman` + `nomor_dokumen`; WA/Gate/SSO cross-cutting (notif tiap transisi status).
