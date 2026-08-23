# Koperasi App — Sistem Manajemen Koperasi Karyawan

![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![Tests](https://img.shields.io/badge/Tests-Pest%2FPHPUnit-25A162?logo=phpunit&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-yellow)

Aplikasi manajemen koperasi karyawan dengan alur pinjaman bertingkat, manajemen simpanan multi-jenis, kas 2 kantong, dan portal anggota (SSO-ready).

---

## 📋 Fitur Utama

### Autentikasi & Akses
- Login via **NIP/NIK karyawan + password** (bukan email)
- SSO perusahaan (custom Socialite provider `perusahaan`)
- Password wajib ganti saat login pertama (`harus_ganti_password`)
- Role-based: **Admin**, **Bendahara**, **Ketua**, **Anggota**
- Permission berbasis Spatie (13 permission ter-seed)

### Manajemen Anggota
- CRUD anggota + auto-generate No. Anggota (`ANG-2026-XXXX`)
- Simpanan pokok otomatis saat registrasi
- Import/Export Excel dengan template
- Limit pinjaman kustom per anggota (audit trail via `AuditLog`)

### Simpanan (3 Jenis)
| Jenis | Frekuensi | Konfirmasi |
|-------|-----------|------------|
| Pokok | Sekali (saat daftar) | Auto |
| Wajib | Bulanan | Bendahara |
| Dana Sosial | Bulanan (bersama Wajib) | Bendahara |

### Pinjaman — Alur Lengkap
1. **Anggota ajukan** (wizard 3 step: nominal → tenor → simulasi cicilan)
2. **Bendahara tinjau** (approve/reject + catatan wajib)
3. **Ketua approve final** (lihat catatan Bendahara)
4. **Bendahara cair** (generate jadwal angsuran, potong saldo kas pinjaman)
5. **Angsuran bulanan** (bunga menurun 1% dari sisa pokok)

### Aturan Bisnis Kunci
| Aturan | Detail |
|--------|--------|
| **Limit pinjaman** | 4 kategori: <1th=1jt, 1–3th=5jt, 3–5th=7jt, >5th=10jt. Override via `limit_custom` |
| **Reloan** | Anggota non-<1th dgn sisa ≤2 angsuran → 1x privilege (`sudah_pakai_privilege_reloan`) |
| **Approval berjenjang** | Bendahara (tahap 1) → Ketua (final) → Bendahara cair |
| **Bunga menurun** | 1% dari sisa pokok bulan berjalan; di-snapshot saat pinjaman dibuat |
| **2 Kantong Kas** | `saldo_pinjaman` & `saldo_dana_sosial` (atomic via `lockForUpdate`) |
| **Simpanan wajib** | Diinput manual Bendahara per bulan (tidak auto-generate) |

### Portal Anggota (Inertia + React)
- Dashboard: ringkasan pinjaman aktif, pengajuan berjalan, tenor & simpanan dinamis
- Riwayat pinjaman & simpanan (filter periode)
- Profil + manajemen rekening bank (tambah, set default, hapus)
- Ajukan pinjaman, limit khusus, percepatan tenor

### Pengaturan & Admin
- Bunga, Limit Pinjaman (4 kategori), Tenor (rentang + maksimal), Simpanan
- Role & Permission management (buat/edit/hapus role, atur permission via checklist)
- Audit Log pada semua perubahan sensitif

---

## 🛠 Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 13, PHP 8.3+ |
| Frontend | Inertia v2, React 18, Tailwind 3, Vite |
| Database | MySQL (migrations DB-agnostic, test di SQLite `:memory:`) |
| Auth & ACL | Spatie Laravel Permission |
| Export/Import | Maatwebsite Excel |
| Charts | Recharts |
| Icons | Lucide React |

---

## 🚀 Quick Start

```bash
# Clone & install dependencies
composer install
npm install

# Environment & key
cp .env.example .env
php artisan key:generate

# Database (migrasi + seeder)
php artisan migrate --seed

# Build assets
npm run build

# Development server (perlu 2 terminal)
# Terminal 1:
php artisan serve
# Terminal 2:
npm run dev
```

**Default login (dari seeder):**
| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `password` |
| Bendahara | `bendahara` | `password` |
| Ketua | `ketua` | `password` |
| Anggota | `anggota` | `password` |

> **SSO**: Butuh env `SSO_CLIENT_ID`, `SSO_CLIENT_SECRET`, `SSO_REDIRECT_URI` — kosongkan untuk dev lokal.

---

## 📁 Struktur Project (Highlight)

```
app/
├── Http/Controllers/
│   ├── Portal/          # Anggota: dashboard, pinjaman, profil, limit, percepatan
│   ├── Bendahara/       # Konfirmasi simpanan/angsuran, tinjau pinjaman, cair dana
│   ├── Ketua/           # Approval final pinjaman, limit khusus, percepatan
│   └── Pengaturan/      # Admin: user, role, setting sistem
├── Models/              # 17 models (Anggota, Pinjaman, Simpanan, KasKoperasi, dll)
├── Services/
│   ├── Pinjaman/        # 7 service: eligibility, perhitungan bunga, approval, dll
│   ├── Keuangan/        # JurnalKas, Pengeluaran
│   ├── Anggota/         # Resign, Reaktivasi
│   └── Simpanan/        # Konfirmasi
└── Imports/Exports/     # Anggota Excel (Maatwebsite)
```

---

## 📚 Dokumentasi

- [`docs/PRD-Koperasi-App.md`](docs/PRD-Koperasi-App.md) — Product Requirements (fitur realized + planned)
- [`docs/Manual-Book-Koperasi-App.md`](docs/Manual-Book-Koperasi-App.md) — Panduan pengguna
- [`docs/ERD-Koperasi-App.md`](docs/ERD-Koperasi-App.md) — Diagram ERD
- [`docs/DEVELOPMENT_LOG.md`](docs/DEVELOPMENT_LOG.md) — Changelog teknis

---

## 🧪 Testing

```bash
# Jalankan semua test
php artisan test

# Filter spesifik
php artisan test --filter=Pinjaman
php artisan test --filter=Simpanan
php artisan test --filter=ResignService
```

---

## 🗺 Roadmap (Meeting 15 Agustus 2026)

| Tahap | Fitur | Status |
|-------|-------|--------|
| 1 | Fondasi: 4 kategori limit, 2 kantong kas, cabang Jakarta, SSO, import | ✅ Realized |
| 2 | Modul Pengeluaran (koperasi & dana sosial) | ✅ Realized |
| 3 | Limit Khusus via Pengajuan (anggota ajukan → ketua setujui) | ✅ Realized |
| 4 | Percepatan (pelunasan langsung & penambahan tenor) | 🔄 Planned |
| 5 | Modul Resign (pelunasan/penutupan anggota) | 🔄 Planned |
| 6 | Tutup Buku (proses periode) | 🔄 Planned |
| 7 | UI Polish (tab per cabang, cetak slip, TTD digital) | 🔄 Planned |

---

## 🐳 Deployment (Docker)

### Dockerfile (sudah tersedia)
```dockerfile
# Multi-stage build: composer → npm build → production image
FROM php:8.3-fpm-alpine AS base
# ... (lihat Dockerfile untuk detail)
```

### Build & Run
```bash
# Build image
docker build -t koperasi-app .

# Run container (perlu database & env terpisah)
docker run -d \
  --name koperasi-app \
  -p 8000:8000 \
  --env-file .env.production \
  koperasi-app
```

### Production Checklist
- [ ] `APP_ENV=production` & `APP_DEBUG=false`
- [ ] `APP_KEY` sudah di-generate (`php artisan key:generate --force`)
- [ ] Database MySQL terpisah (tidak SQLite)
- [ ] `SESSION_DRIVER=database` atau `redis`
- [ ] `QUEUE_CONNECTION=database` atau `redis` + worker berjalan
- [ ] `MAIL_*` konfigurasi untuk notifikasi
- [ ] `SSO_*` konfigurasi jika menggunakan SSO perusahaan
- [ ] `FILESYSTEM_DISK=s3` atau cloud storage lain untuk upload
- [ ] HTTPS/SSL terminated di reverse proxy (Nginx/Traefik)
- [ ] Backup database terjadwal

### Queue Worker (wajib untuk background jobs)
```bash
# Supervisor config contoh
[program:koperasi-worker]
command=php artisan queue:work --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
numprocs=2
```

---

## 📄 Lisensi

MIT License — project internal untuk Koperasi Karyawan.