# Setup Tanpa Docker

Panduan menjalankan Koperasi App langsung di komputer (Windows/Linux/macOS) **tanpa Docker**, termasuk layanan WhatsApp (Baileys).

## Prasyarat

| Perangkat | Versi | Catatan |
|---|---|---|
| PHP | ≥ 8.3 | Ekstensi wajib: `dom`, `mbstring`, `xml`, `sqlite3`/`pdo_mysql`, `gd`* |
| Composer | ≥ 2 | — |
| Node.js | ≥ 18 | Untuk Vite + layanan WhatsApp |
| Database | SQLite / MySQL / PostgreSQL | Sesuaikan `.env` |

\* Ekstensi `gd` dipakai dompdf hanya untuk gambar. Lampiran PDF WhatsApp kita berisi teks/tabel saja, jadi tanpa `gd` tetap aman — gunakan flag pada langkah install.

## 1. Install Dependensi

```bash
git clone <repo-url> koperasi-app && cd koperasi-app
cp .env.example .env

# Jika PHP tidak punya ekstensi gd:
composer install --ignore-platform-req=ext-gd
# Jika ada gd:
# composer install

npm install
php artisan key:generate
```

## 2. Konfigurasi `.env`

Sesuaikan database, lalu pastikan blok WhatsApp:

```env
BAILEYS_URL=http://localhost:3000   # default .env.example sudah benar untuk non-Docker
BAILEYS_TOKEN=change-me-in-production
BAILEYS_TIMEOUT=10
```

> ⚠️ Nilai `http://baileys:3000` **hanya** berlaku di dalam jaringan Docker Compose. Di mesin biasa WA akan gagal jika URL ini tidak diganti.

Opsional: `SEND_INTERVAL_MS=2000` untuk mengubah jeda antar pesan WhatsApp (anti-spam).

Lanjutkan migrasi & seed:

```bash
php artisan migrate --seed
npm run build   # atau biarkan vite dev lewat composer dev
```

## 3. Jalankan Aplikasi

Butuh **dua terminal**:

```bash
# Terminal 1 — web server + queue worker WhatsApp (otomatis bersamaan)
composer dev

# Terminal 2 — gateway WhatsApp
cd baileys-service
npm install
npm start
```

`composer dev` menjalankan `artisan serve`, `queue:listen` (worker pengiriman WA), Pail, dan Vite sekaligus.

## 4. Hubungkan WhatsApp (sekali saja)

1. Login sebagai admin → menu **Pengaturan → tab WhatsApp**
2. Scan QR dengan aplikasi WhatsApp di HP
3. Status berubah "terhubung" — sesi tersimpan permanen di `baileys-service/session/`

> 🔒 Folder `session/` berisi kredensial akun WhatsApp. **Jangan dibagikan atau di-commit** (sudah masuk `.gitignore`). Jika ingin ganti nomor: jalankan logout dari Pengaturan lalu scan ulang.

## Troubleshooting

| Gejala | Penyebab | Solusi |
|---|---|---|
| WA gagal, log `ConnectionException` | `BAILEYS_URL` salah / service belum jalan | Pastikan terminal 2 aktif dan URL `localhost:3000` |
| Log WA status `gagal: 404 not found` saat kirim dokumen | Image/container baileys lawas | Rebuild: `docker compose build baileys && docker compose up -d baileys` (khusus pengguna Docker) |
| `composer install` gagal soal `ext-gd` / versi PHP | Ekstensi/versi kurang | `composer install --ignore-platform-req=ext-gd` (dan `--ignore-platform-req=php` bila PHP 8.5) |
| Pesan WA terkirim berurutan sangat cepat | Jeda antrean terlalu kecil | Naikkan `SEND_INTERVAL_MS` |
