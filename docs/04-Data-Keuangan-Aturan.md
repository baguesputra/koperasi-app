---
aliases: [Data Keuangan Aturan Koperasi App]
tags: [koperasi-app/produk]
---

# 04 — Data, Keuangan & Aturan

Lihat [[00-Indeks-Produk]]. Skema rinci: [[ERD-Koperasi-App]]. Teknis: [[ARSITEKTUR]].

## Kas 4 kantong

- Dana Pinjaman: topup, angsuran, pelunasan masuk. Pencairan, pengeluaran keluar.
- Dana Sosial: sosial bulanan masuk. Santunan keluar.
- Simpanan Anggota: pokok/wajib masuk. Return resign keluar.
- Pengembalian Simpanan: transit resign masuk, bayar ke anggota keluar.
- Satu pintu: `JurnalKasService::catat` / `transferAntarKantong`. `lockForUpdate`, cek saldo kecuali transit. Tulis `saldo_awal` + `saldo_setelah`.

## Aturan limit

- `<1th` Rp1jt, `1–3th` Rp5jt, `3–5th` Rp7jt, `>5th` Rp10jt. Override `limit_custom`.
- Reloan: sisa ≤2, non-<1th, 1x per siklus.

## Bunga

- Menurun 1% sisa pokok. Snapshot `persentase_bunga` saat buat. Ubah `setting_bunga` tak sentuh pinjaman lama.
- Cicilan terakhir serap pembulatan.

## Snapshot

- Rekening, versi S&K, bunga, tenor, nomor dokumen. Data lama beku.

## Aman tulis ganda

- `idempotent` middleware di POST finansial. Rinci: [[IDEMPOTENCY_KEYS_SPEC]].
- Transaksi atomic + `lockForUpdate`. Agregat `GROUP BY`, hindari N+1.

## Model inti

- 23 model: User, Anggota, Pinjaman, Angsuran, Simpanan, RekeningAnggota, KasKoperasi, JurnalKas, Pengeluaran, SettingBunga, SettingLimitPinjaman, SettingSimpanan, TabelTenor, AuditLog, PenomoranDokumen, WaLog, master Perusahaan/Jabatan/Divisi/Departemen/cabang.
- 56 migrasi evolutif. DB-agnostic. `docs/ERD-*.md` usang sebagian.

## Audit

- `AuditLog::catat` untuk pengaturan, role, limit, approval.
