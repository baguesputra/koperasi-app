---
aliases: [Operasional Roadmap Koperasi App]
tags: [koperasi-app/produk]
---

# 05 — Operasional & Roadmap

Lihat [[00-Indeks-Produk]]. Progres: [[DEVELOPMENT_LOG]].

## WA

- Baileys gateway + antrean FIFO 2 detik. `202 Accepted`, worker tak blokir.
- Event: ajuan diterima, approve Bendahara, cair + PDF bukti, tolak.
- Simpanan/angsuran: toast in-app, WA massal belum.
- PDF via queue. `WaService`, `WaLog`.

## Laporan

- 13 laporan: Arus Kas, Neraca, Bunga, Rekap Status, Jatuh Tempo, Tenor, Rekap Anggota, Setoran Bulanan, Daftar Anggota, Resign, Pengeluaran, Dana Sosial, Audit (admin).
- Filter periode + export PDF & Excel.

## Testing

- 110 test, 411 assert, hijau. SQLite `:memory:`. `RefreshDatabase` + seed + `MembuatDataUji`.
- `Queue::fake`, `Http::fake`. Rinci: [[TESTING]].
- Jalankan: `php artisan test`. Satu file: `php artisan test --filter=NamaTest`.

## Setup

- `composer run setup`, `composer run dev`, `php artisan migrate:fresh --seed`.
- `npm install --legacy-peer-deps`. Rinci: [[SETUP-TANPA-DOCKER]].

## Realized

- Limit 4 kategori, 4 kantong, cabang Jakarta, pengeluaran, limit khusus, percepatan, resign, WA + PDF, 13 laporan, idempotency, audit, API mobile, PWA.

## Planned

- Validasi 3 bulan, simpanan pokok 1 tahun, edit ajuan tenor, tutup buku, multi-tenant, kelola user staff UI, profil lengkap, WA simpanan/angsuran, tutup buku formal.
- Belum centang di [[DEVELOPMENT_LOG]] = belum jadi.

## Open questions

- Saldo kurang saat cair: tolak (kini) vs revisi?
- Angsuran: manual (kini) vs auto potong?
- Report: answered, 13 tersedia.
