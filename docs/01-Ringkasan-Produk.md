---
aliases: [Ringkasan Produk Koperasi App]
tags: [koperasi-app/produk]
---

# 01 — Ringkasan Produk

Aplikasi koperasi simpan pinjam internal. Lihat [[00-Indeks-Produk]].

## Apa ini

- Kelola anggota, simpanan, pinjaman, kas, laporan. Satu repo.
- Stack: Laravel 13 + Inertia v2 + React 18 + Tailwind 3 + Vite. DB MySQL `koperasi_db`, tes SQLite `:memory:`.
- Bahasa UI, model, kolom, route: Bahasa Indonesia.
- Status: dev, alur inti + pendukung jalan. Sumber: [[PRD-Koperasi-App]], [[Manual-Book-Koperasi-App]], [[DEVELOPMENT_LOG]].

## Persona

- Admin: anggota, resign, pengaturan, role.
- Bendahara: konfirmasi simpanan/angsuran, tinjau tahap 1, cair, kas, pengeluaran.
- Ketua: approval final, limit khusus, perubahan tenor.
- Anggota: portal mandiri. Rinci: [[02-Akses-Pengguna]].

## Modul inti

- Keanggotaan + resign/reaktivasi.
- Simpanan: pokok, wajib, dana sosial.
- Pinjaman: wizard portal → Bendahara → Ketua → cair → angsuran → lunas.
- Percepatan tenor, limit khusus, pengeluaran, 13 laporan, WA. Rinci: [[03-Alur-Bisnis-Utama]].
- Kas 4 kantong, bunga menurun, snapshot, lock + idempotency. Rinci: [[04-Data-Keuangan-Aturan]].
- Ops, roadmap, backlog: [[05-Operasional-Roadmap]].

## Teknis singkat

- Controller per role: `Portal/`, `Bendahara/`, `Ketua/`, akar = admin. Logika di `app/Services/`. Audit via `AuditLog::catat`.
- Rinci: [[ARSITEKTUR]].
