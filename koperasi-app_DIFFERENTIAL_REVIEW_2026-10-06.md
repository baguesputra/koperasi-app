# Differential Security Review — koperasi-app

Tanggal: 2026-10-06. Scope: 3 titik disetujui (import, QR/XSS, lib).

## Executive Summary

| Severity | Count |
|----------|-------|
| HIGH | 0 (sisa) |
| MEDIUM | 3 (diperbaiki) |
| LOW | 0 |

**Overall Risk:** MEDIUM → LOW setelah patch
**Recommendation:** CONDITIONAL (lanjut 4 titik sisa: token abadi, API throttle, password awal, SSO)

**Key Metrics:**
- Files analyzed: 15 (diff kerja saat ini) + baseline auth/session/routes
- Test coverage: CetakBuktiTest + MigrasiImportTest + LaporanTest + SsoCallbackTest = 32/32 hijau
- composer audit: 3 advisory → 0. npm audit: 0
- Build: hijau

## What Changed

| File | Perubahan | Risk |
|------|-----------|------|
| composer.lock | commonmark 2.10.0→2.10.3, phpseclib 3.0.56→3.0.57 | MEDIUM → fixed |
| resources/js/Pages/Pinjaman/CetakBukti.jsx | Hapus QR Google + dangerouslySetInnerHTML, ganti box link salin | MEDIUM → fixed |
| resources/js/Pages/Migrasi/Index.jsx | Hapus dangerouslySetInnerHTML catatan statis | LOW → fixed |
| app/Http/Controllers/MigrasiController.php:40,55 | + max:2048 | MEDIUM → fixed |
| app/Http/Controllers/AnggotaController.php:228 | + max:2048 | MEDIUM → fixed |
| app/Imports/*.php | Guard 5000 baris di collection() | MEDIUM → fixed |
| resources/js/Pages/Anggota/Import.jsx + Migrasi/Index.jsx | Hint 2MB/5000 baris | LOW → fixed |

## Temuan & Perbaikan

### MEDIUM: Import tanpa batas → file besar jebol memori
- Lokasi: MigrasiController.php:40,55, AnggotaController.php:228, Imports ToCollection
- Skenario: admin upload xlsx 50MB / 100rb baris → ToCollection telan semua → OOM/timeout
- Fix: max:2048 + guard count > 5000 tolak awal + hint UI
- Bukti: lint OK, MigrasiImportTest hijau

### MEDIUM: QR Google + XSS di preview bukti
- Lokasi: CetakBukti.jsx:205-211, Migrasi/Index.jsx:20
- Skenario: URL signed bocor ke chart.googleapis.com; script inline via dangerouslySetInnerHTML
- Fix: QR Google hapus total, ganti box salin link + buka verifikasi; QR sah tetap di PDF backend (endroid 6.1.3)
- Bukti: grep chart.googleapis.com + dangerouslySetInnerHTML di resources/js = nol

### MEDIUM: Lib bocor (transitif)
- commonmark 2.10.0: high DoS tabel + medium HTML bypass (fix 2.10.3)
- phpseclib 3.0.56: CVE-2026-84308 X25519 timing (fix 3.0.57)
- Fix: composer update 2 paket, audit 0, 32 test hijau

## Sisa (belum dikerjakan)
1. Token Sanctum abadi (expiration=null) + API tanpa throttle
2. Password awal = no_karyawan + wajib ganti mati
3. SSO: SP unsigned, skew 600, auto-provision email
4. Session encrypt false + secure kosong + contoh DEBUG true

## Methodology
- Strategy: SURGICAL (3 titik), baseline via baca langsung (subagent tak tersedia)
- Techniques: composer/npm audit, grep surface, lint, test, build
- Limitations: tanpa adversarial-modeler agent, tanpa cek CVE online selain audit DB
- Confidence: HIGH untuk 3 titik, MEDIUM keseluruhan
