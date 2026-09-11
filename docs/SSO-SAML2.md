# SSO SAML2 OptiGate

Login karyawan memakai akun perusahaan via IdP `gate.appdutamall.com`. Aplikasi ini sebagai SP (Service Provider).

## Alur kerja

1. User buka `/` atau `/login` saat `AUTH_MODE=sso` → redirect ke `sso.redirect`.
2. `SsoController@redirect` membuat `AuthnRequest` SAML lalu `Inertia::location(url IdP)`.
   - Pakai full-page load, bukan XHR. Ini penting supaya browser boleh pindah ke domain IdP tanpa kena blokir CORS.
3. User login di IdP. IdP auto-POST `SAMLResponse` ke `/auth/sso/callback`.
4. `SsoController@callback` validasi assertion, cari user lokal, login Laravel, redirect ke `/dashboard`.
5. Gagal kapan pun → redirect ke `/auth/sso/gagal` dengan pesan `error` di session + catat `audit_log`.

```
[Browser] → GET /auth/sso/redirect → 409 Location ke IdP (Inertia::location)
[Browser] → IdP login → POST SAMLResponse ke /auth/sso/callback
[SP] validasi → Auth::login → /dashboard
```

## Paket yang dipakai

- `socialiteproviders/saml2` — bangun `AuthnRequest`, terima `SAMLResponse`, validasi `Issuer`, `Recipient`, `Signature`, `Conditions/NotBefore/NotOnOrAfter`, petakan atribut ke user Socialite.
- `socialiteproviders/manager` — daftarkan driver `perusahaan` lewat event `SocialiteWasCalled`.
- `litesaml/lightsaml` (transitif) — validator waktu SAML.

## File yang terlibat

- `app/Services/SSO/PerusahaanProvider.php` — subclass provider SAML kustom.
  - `additionalConfigKeys()` tambah `validation` agar `clock_skew` dari `config/services.php` benar-benar diteruskan ke provider (tanpa ini nilai diabaikan).
  - `validateTimestamps()` baca `config('services.perusahaan.validation.clock_skew', 600)`.
- `app/Providers/AppServiceProvider.php`
  - `Event::listen(SocialiteWasCalled::class, ... extendSocialite('perusahaan', PerusahaanProvider::class))`.
  - `RateLimiter sso-callback`: 10x/menit per IP.
  - `PreventRequestForgery::except(['/auth/sso/callback'])` — POST dari IdP tidak punya token CSRF.
- `app/Http/Controllers/Auth/SsoController.php`
  - `redirect()`: `stateless()->redirect()->getTargetUrl()` + `Inertia::location()`.
  - `callback()`: `stateless()->user()`, fallback `email = ssoUser->email ?? id (NameID)`, cek `FILTER_VALIDATE_EMAIL`.
  - Pencarian user: `where sso_id` dulu, lalu `where email`. Tidak pakai `nik` karena IdP OptiGate tidak mengirim `nik`.
  - Aturan: role `anggota` wajib punya relasi `anggota`. Staf (`admin`, `bendahara`, `ketua_koperasi`) boleh tanpa relasi `anggota`.
  - Tolak dengan pesan jelas: `email_missing`, `user_not_found`, `no_anggota_relation`, `email_mismatch`, `inactive`.
  - Sukses: update `sso_id`, `auth_provider='sso'`, `Auth::login + session regenerate`, catat `AuditLog sso_login`.
  - `catch`: `report($e)` + `AuditLog sso_login_failed/exception` + redirect `sso.gagal`.
- `config/services.php` (`perusahaan`)
  - `metadata` dari `SAML_METADATA_URL`.
  - `sp_entityid` / `sp_acs` = `http://localhost:8000/auth/sso/callback`.
  - `sp_name_id_format = urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress` (sesuai dok OptiGate; NameID = email).
  - `sp_sign_assertions = false`.
  - `validation.clock_skew` default `600` (detik).
  - `attribute_map`: `email → [email, mail, userPrincipalName]`, `name → [name, displayName, cn, givenName]`.
- `routes/web.php`
  - `GET /auth/sso/redirect` (`sso.redirect`).
  - `MATCH GET+POST /auth/sso/callback` + `throttle:sso-callback` (`sso.callback`).
  - `GET /auth/sso/logout` (`sso.logout`) — mulai logout dari SP: bersihkan sesi lokal lalu kirim `LogoutRequest` ke IdP.
  - `MATCH GET+POST /auth/sso/slo` (`sso.slo`) — terima `LogoutRequest`/`LogoutResponse` dari IdP, bersihkan sesi, balas `LogoutResponse`. Dikecualikan CSRF.
  - `GET /auth/sso/metadata`, `GET /auth/sso/gagal`.
- `resources/js/Pages/Auth/SsoGagal.jsx` — tombol `Coba Lagi` pakai `<a href>` biasa, bukan Inertia `Link`. Kalau pakai `Link`, Inertia kirim XHR ke IdP dan kena blokir CORS.
- `resources/js/app.jsx` — Service Worker hanya daftar saat `PROD`; saat dev unregister SW lama.
- `vite.config.js` — `workbox.navigateFallback = null` + `navigateFallbackDenylist: [/^\/auth\/sso/, /^\/api\//]` agar SW tidak intersep callback SSO/API.

## Konfigurasi `.env`

```env
AUTH_MODE=sso
SSO_REDIRECT_URI=http://localhost:8000/auth/sso/callback
SAML_METADATA_URL=https://gate.appdutamall.com/saml/metadata
SAML_SPENTITY_ID=http://localhost:8000/auth/sso/callback
SAML_SP_ACS_URL=http://localhost:8000/auth/sso/callback
SAML_SP_SLS_URL=http://localhost:8000/auth/sso/slo
SAML_NAME_ID_FORMAT=urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress
SAML_SP_SIGN_ASSERTIONS=false
SAML_CLOCK_SKEW=600
```

## URL untuk didaftarkan ke Gate

Ganti `http://localhost:8000` dengan domain publik SP saat deploy.

- Entity ID: `http://localhost:8000/auth/sso/callback`
- ACS (Assertion Consumer Service): `http://localhost:8000/auth/sso/callback` (HTTP-POST default, HTTP-Redirect didukung)
- SLO (Single Logout Service): `http://localhost:8000/auth/sso/slo` (HTTP-Redirect + HTTP-POST)
- Metadata SP: `http://localhost:8000/auth/sso/metadata`
- NameID format: `urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress` (nilainya = email user)
- Atribut: `email`, `name` (+ opsional `company`, `department`, `position`, `role` — diabaikan SP)

Alur logout: tombol keluar (tetap `POST /logout` lama) otomatis dialihkan ke `SsoController@logout` saat mode SSO — kirim `LogoutRequest` ke IdP → IdP → `GET/POST /auth/sso/slo` (SP balas `LogoutResponse` + bersihkan sesi). Tombol dashboard tidak perlu diubah.

Contoh lengkap lihat `.env.example`. Pastikan ACS di atas terdaftar di OptiGate, kalau tidak `Recipient` tidak cocok dan assertion ditolak.

## Data user lokal

- `sso_id` + `auth_provider` baru terisi setelah login SSO sukses pertama kali. Kosong itu wajar, bukan penyebab gagal.
- Email lokal harus sama persis dengan email IdP (`*@tataoptima.com`), bukan email seeder `*@koperasi.test`.
- Role `anggota` wajib punya baris `anggota`. Staf tidak wajib.

## Verifikasi

```bash
php artisan test --filter=SsoCallbackTest
php artisan test tests/Feature/Auth
vendor/bin/pint --test app/Http/Controllers/Auth/SsoController.php app/Services/SSO/PerusahaanProvider.php
```

Test mencakup: sukses user+anggota, fallback NameID email, staf tanpa relasi anggota lolos, email hilang, user tidak ketemu, tanpa relasi anggota (role anggota), email tidak cocok, nonaktif, audit sukses/gagal.

Diagnosa manual:

```bash
php artisan tinker --execute="print_r(App\Models\AuditLog::orderByDesc('id')->take(5)->get(['aksi','keterangan','user_id','created_at'])->toArray());"
Get-Content storage/logs/laravel.log -Tail 40
```

## Masalah yang pernah terjadi dan solusinya

| Gejala | Penyebab | Perbaikan |
|---|---|---|
| `419 expired` di `/auth/sso/callback` | POST IdP tanpa token CSRF | `PreventRequestForgery::except(['/auth/sso/callback'])` |
| CORS preflight ke `gate.../saml/sso` diblokir | Redirect dipicu via XHR Inertia | `Inertia::location()` + tombol `<a>` biasa di `SsoGagal.jsx` |
| `Conditions.NotBefore must not be in the future` | Jam IdP lebih maju dari server lokal; `clock_skew` tidak diteruskan manager | `PerusahaanProvider` kustom + `validation.clock_skew=600`. Kalau selisih > 10 menit, sinkronkan jam OS dulu |
| `admin@koperasi.test` tetap gagal | Admin tanpa relasi `anggota` ditolak aturan lama + exception waktu di atas | Syarat `anggota` hanya untuk role `anggota` |
| `manifest.webmanifest 404`, error workbox `registerSW/index.html` | Artefak PWA basi di `public/`, SW intersep route SSO | Hapus artefak, gitignore `sw.js/workbox-*/registerSW/manifest`, SW hanya PROD, `navigateFallback=null` + denylist |
