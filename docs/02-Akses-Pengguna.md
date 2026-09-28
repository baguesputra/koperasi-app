---
aliases: [Akses Pengguna Koperasi App]
tags: [koperasi-app/produk]
---

# 02 — Akses & Pengguna

Lihat [[00-Indeks-Produk]]. Teknis: [[ARSITEKTUR]].

## Role

- Admin: anggota, resign, pengaturan, role.
- Bendahara: simpanan, angsuran, tinjau tahap 1, cair, kas, pengeluaran.
- Ketua: approval final, tenor, limit khusus.
- Anggota: portal mandiri. Rinci alur: [[03-Alur-Bisnis-Utama]].

## Login

- `no_karyawan` + password. Bukan email. `LoginRequest.php`.
- `harus_ganti_password` paksa ganti saat login pertama. `EnsurePasswordChanged`.
- Anggota → `portal.*`. Pengurus → `dashboard`. `DashboardController`.
- Akun seed: `ADM-000001`, `BEN-000001`, `KET-000001`, `TOP-100001`–`TOP-100004`. Password = `no_karyawan`.

## Permission

- Spatie, alias `permission:` di `bootstrap/app.php`. Seed: `PermissionSeeder`.
- 15 item: `anggota.lihat`, `anggota.kelola`, `anggota.resign`, `simpanan.lihat`, `simpanan.konfirmasi`, `pinjaman.lihat`, `pinjaman.tinjau-bendahara`, `pinjaman.approve-ketua`, `angsuran.konfirmasi`, `kas.lihat`, `kas.topup`, `laporan.lihat`, `pengaturan.kelola`, `user.kelola`, `portal.akses`.
- Tambah route = tambah permission + item sidebar.

## Sidebar

- `Sidebar.jsx` filter menu dari `auth.permissions`. Share via `HandleInertiaRequests`.
- Badge antrean dari `notifications`.

## SSO

- Socialite provider `perusahaan`. Route `sso.redirect`/`sso.callback`.
- `AUTH_MODE=local|sso`. `SSO_*` kosong wajar di lokal.
- Rinci: [[SSO-SAML2]].
