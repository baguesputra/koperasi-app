---
aliases: [Alur Bisnis Utama Koperasi App]
tags: [koperasi-app/produk]
---

# 03 — Alur Bisnis Utama

Lihat [[00-Indeks-Produk]]. Manual: [[Manual-Book-Koperasi-App]].

## Anggota

- Tambah: No. Anggota `ANG-2026-0001` auto, user + role anggota + wajib ganti password, simpanan pokok auto + jurnal `simpanan_pokok_masuk`.
- Import Excel + template. Edit. Limit khusus + Audit Log.
- Resign: settlement hitung, status resign + JSON, slip PDF, login blokir. Reaktivasi: status aktif, histori tetap.

## Simpanan

- Pokok: sekali, auto saat anggota dibuat.
- Wajib: bulanan, konfirmasi massal Bendahara. Hasilkan 2 baris: Wajib + Dana Sosial.
- Dana Sosial: auto bersama Wajib. Sembunyi dari riwayat anggota.
- Nominal seed: Pokok Rp100rb, Wajib Rp45rb, Sosial Rp5rb.

## Pinjaman

- Portal wizard 3 langkah: Nominal (cek limit), Tenor (`tabel_tenor`), Ringkasan (simulasi bunga menurun).
- Alur: `diajukan` → `approved_bendahara` → `aktif` (Ketua approve → jadwal generate, kas kurang, saldo cek) → `lunas`.
- Bendahara mandiri langsung `approved_bendahara`. Ketua mandiri `diajukan` + `cair_oleh_bendahara=true`, cair via `cairBendahara`.
- Catatan tolak/approve wajib min 5 karakter.
- Reloan: non-<1th, sisa ≤2 angsuran, 1x via `sudah_pakai_privilege_reloan`. <1th wajib lunas.

## Angsuran

- Jadwal auto saat aktif. Bunga menurun 1% sisa pokok. Terakhir serap pembulatan, Σpokok == nominal.
- Konfirmasi massal per bulan. Terakhir → `lunas` otomatis.
- Badge: Terlambat, Perubahan tenor diajukan.

## Percepatan tenor

- 3 jenis: Percepat (cicilan naik, bunga turun), Perpanjang (cicilan turun, bunga naik), Lunas total (sisa pokok + bunga 1 bln).
- Alur: anggota ajukan → Bendahara → Ketua (bulan ini/depan) → jadwal lama `digantikan`, baru dari sisa pokok. 1x per pinjaman.

## Limit khusus

- Anggota ajukan → Ketua tinjau → `anggota.limit_custom` + Audit Log.
- Validasi: tanpa antrean, `limit_diminta` > limit kini.

## Pengeluaran

- `koperasi` potong `saldo_pinjaman`. `dana_sosial` potong `saldo_dana_sosial`. Validasi saldo.
