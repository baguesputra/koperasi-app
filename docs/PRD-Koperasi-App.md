# PRD — Koperasi App (Product Requirements Document)

**Versi Dokumen:** 1.0
**Tanggal:** 18 Agustus 2026
**Status:** Development — sebagian besar alur bisnis inti sudah berjalan
**Bahasa:** Indonesia (sesuai konvensi project)
**Sumber analisa:** kode aktual (`app/`, `routes/`, `resources/js/`, `database/`), `docs/Manual-Book-Koperasi-App.md` (v1.0, 8 Agu), `docs/ERD-Koperasi-App.md`, `docs/DEVELOPMENT_LOG.md`, dan ringkasan meeting koperasi (15 Agu).

> **Catatan status tiap fitur:** `Realized` = sudah ada di code · `Planned` = belum dibangun (roadmap/backlog).

---

## 1. Pendahuluan

### 1.1 Tujuan Dokumen
Dokumen ini menjadi acuan kebutuhan produk Koperasi App: merangkum fitur yang **sudah realisasi** (hasil analisa code, bukan sekadar manual book usang) dan **rencana pengembangan** (roadmap meeting + backlog). Tiap modul dilengkapi *user story* dan *acceptance criteria* (kriteria penerimaan) yang bisa dipakai sebagai acuan test.

### 1.2 Konteks
Manual Book v1.0 (8 Agustus) sudah tidak mencerminkan kondisi code terkini. Beberapa fitur roadmap (2 kantong kas, modul pengeluaran, limit khusus via pengajuan) **sudah diimplementasikan**, sementara validasi keanggotaan, notifikasi, laporan, dan modul lanjutan lainnya **belum**. PRD ini menyelaraskan ketiganya.

### 1.3 Stack & Arsitektur
- **Backend:** Laravel 13, MySQL, Spatie Laravel Permission.
- **Frontend:** Inertia v2 + React 18 + Tailwind 3 + Vite.
- **Pola controller per-role:** `Portal/` (layanan anggota), `Bendahara/` (aksi bendahara), `Ketua/` (approval final), namespace akar (Admin).
- **Logika bisnis** di `app/Services/` (keuangan, pinjaman, simpanan, SSO) — tidak di-inline di controller.
- **Audit** via `AuditLog::catat(...)`.
- **Export/Import Excel** di `app/Imports` & `app/Exports`.

---

## 2. Persona & Hak Akses

| Role | Fungsi Utama |
|---|---|
| **Admin** | Kelola anggota, pengaturan sistem, role & hak akses |
| **Bendahara** | Konfirmasi simpanan/angsuran, tinjau pinjaman (tahap 1), pencairan dana, topup & catat pengeluaran |
| **Ketua Koperasi** | Approval final pinjaman (tahap 2), persetujuan pengajuan limit khusus |
| **Anggota** | Ajukan pinjaman & limit khusus, lihat saldo & riwayat via portal |

**Permission (Spatie) — 13 item ter-seed:**
`anggota.lihat`, `anggota.kelola`, `simpanan.lihat`, `simpanan.konfirmasi`, `pinjaman.lihat`, `pinjaman.tinjau-bendahara`, `pinjaman.approve-ketua`, `angsuran.konfirmasi`, `kas.lihat`, `kas.topup`, `laporan.lihat`, `pengaturan.kelola`, `portal.akses`.

Sidebar memfilter menu dari prop `auth.permissions` (di-share `HandleInertiaRequests`); penambahan route wajib diiringi penambahan permission di sidebar.

---

## 3. Fitur Realized (User Story + Acceptance)

### 3.1 Autentikasi & Akses (`Realized`)
- Login memakai **`no_karyawan` + password** (bukan email) — `LoginRequest`.
- Flag `harus_ganti_password` memaksa ganti password saat login pertama (`EnsurePasswordChanged`).
- Role anggota langsung diarahkan ke `portal.*` saat login; role lain ke dashboard.
- **SSO** Socialite provider custom `perusahaan` (`sso.redirect` / `sso.callback`) — butuh endpoint SSO asli; `SSO_*` kosong di `.env` adalah wajar untuk dev lokal.

**US-AUTH-1:** Sebagai anggota, saya login dengan no_karyawan & password agar bisa masuk ke portal.
- *Acceptance:* Login gagal bila no_karyawan/password salah; redirect ke `portal.dashboard` bila role anggota.

**US-AUTH-2:** Sebagai user baru, saya diwajibkan ganti password saat login pertama.
- *Acceptance:* Bila `harus_ganti_password = true`, akses tertahan di halaman ganti password wajib sampai diubah.

**US-AUTH-3 (sebagian):** Sebagai karyawan perusahaan, saya login lewat SSO tanpa input manual.
- *Acceptance (Planned):* `sso.redirect` memanggil IdP; `sso.callback` membuat/menghubungkan user. *Catatan:* butuh endpoint SSO nyata, belum teruji di dev lokal.

### 3.2 Modul Keanggotaan (`Realized`)
- Admin: list (cari nama, filter cabang/status), tambah (No. Anggota `ANG-2026-0001` auto-generate, simpanan pokok auto), edit, limit khusus per-anggota.
- **Import Excel** anggota + download template (`anggota.import`, `anggota.template`).
- **Cabang:** Banjarmasin, Samarinda, Palangka, **Jakarta** (sudah ditambah).
- **Limit khusus** di edit anggota: isi nominal + alasan → tercatat di Audit Log.

**US-ANG-1:** Sebagai Admin, saya menambah anggota agar sistem membuat No. Anggota & mencatat simpanan pokok otomatis.
- *Acceptance:* No. Anggota ter-generate unik; 1 baris simpanan `pokok` otomatis mengikuti nominal Pengaturan.

**US-ANG-2:** Sebagai Admin, saya mengimpor data anggota massal dari Excel.
- *Acceptance:* Template ter-download; impor menghasilkan baris anggota + simpanan pokok tanpa error duplikat.

**US-ANG-3:** Sebagai Admin, saya menetapkan limit khusus untuk anggota tertentu.
- *Acceptance:* Limit override tersimpan di `anggota.limit_custom`; perubahan tercatat di Audit Log dengan alasan.

### 3.3 Modul Simpanan (`Realized`)
Tiga jenis: **Pokok** (sekali, auto saat anggota dibuat), **Wajib** (bulanan, konfirmasi Bendahara), **Dana Sosial** (auto bersama konfirmasi Wajib, terpisah dari saldo pribadi).
- Konfirmasi massal: pilih bulan → sistem tampilkan anggota aktif belum ada simpanan wajib → centang → konfirmasi → 2 baris (Wajib + Dana Sosial) tercipta.
- Nominal (dari seeder/fix): Pokok Rp100.000; Wajib awal Rp50.000 + Rp45.000/bulan; Dana Sosial Rp5.000.

**US-SIM-1:** Sebagai Bendahara, saya mengonfirmasi simpanan wajib bulanan per anggota.
- *Acceptance:* Setelah konfirmasi, 2 baris (wajib + dana_sosial) tercatat; total Dana Sosial Terkumpul bertambah.

**US-SIM-2:** Sebagai Bendahara/Admin, saya melihat total simpanan per anggota & riwayat lengkap.
- *Acceptance:* Halaman list & detail menampilkan akumulasi dan riwayat per jenis dengan benar.

### 3.4 Modul Pinjaman — Sisi Anggota (`Realized`)
Wizard 3 langkah: **Nominal** (cek limit), **Tenor** (dibatasi `tabel_tenor`), **Ringkasan** (simulasi cicilan bunga menurun). Submit → status `diajukan`.
- Tambahan form (sudah ada): keperluan, bank, no rekening, atas nama (snapshot ke `pinjaman`).

**US-PIN-1:** Sebagai Anggota, saya mengajukan pinjaman dengan nominal dalam limit & tenor sesuai aturan.
- *Acceptance:* Sistem menolak nominal melebihi limit (otomatis/kustom); tenor dibatasi `tabel_tenor`; simulasi cicilan (declining balance) tampil benar; submit menghasilkan status `diajukan`.

**US-PIN-2 (Reloan):** Sebagai anggota non-<1 tahun dengan sisa ≤2 angsuran, saya boleh ajukan pinjaman baru sekali.
- *Acceptance:* `EligibilitasPinjamanService` mengizinkan 1x via flag `sudah_pakai_privilege_reloan`; pengajuan ke-2 ditolak. Anggota <1 tahun wajib lunas dulu.

### 3.5 Modul Pinjaman — Approval & Pencairan (`Realized`)
Alur: `diajukan` → (Bendahara approve/reject + catatan) `approved_bendahara` → (Ketua approve/reject + catatan, bisa lihat catatan Bendahara) `approved_ketua` → (Bendahara **cair**) `aktif` (jadwal angsuran generate, saldo kas berkurang) → `lunas`.
- **2 kantong kas**: pencairan mengurangi `saldo_pinjaman`; saldo tidak cukup → ditolak dengan pesan jelas (`JurnalKasService` validasi per kantong).

**US-PIN-3:** Sebagai Bendahara, saya meninjau & memutuskan pengajuan (setujui/tolak wajib catatan).
- *Acceptance:* Approve → `approved_bendahara`; reject → `ditolak` dengan catatan tersimpan.

**US-PIN-4:** Sebagai Ketua, saya memberikan approval final.
- *Acceptance:* Approve → `approved_ketua` (lihat catatan Bendahara); reject → `ditolak`.

**US-PIN-5:** Sebagai Bendahara, saya mencairkan dana pinjaman yang sudah disetujui Ketua.
- *Acceptance:* `cair` mengurangi `saldo_pinjaman`, men-generate jadwal angsuran, status → `aktif`, `tanggal_pencairan` & `cair_oleh_bendahara` tercatat; gagal bila saldo kantong pinjaman tidak cukup (pesan jelas).

**US-PIN-6:** Sebagai sistem, saya men-snapshot bunga saat pinjaman dibuat.
- *Acceptance:* `persentase_bunga` tersimpan; perubahan `setting_bunga` di kemudian hari tidak memengaruhi pinjaman berjalan.

### 3.6 Modul Angsuran (`Realized`)
- Jadwal auto saat `aktif`, **bunga menurun** (1% dari sisa pokok tiap bulan, snapshot).
- Konfirmasi massal (Bendahara): pilih bulan → centang cicilan jatuh tempo → konfirmasi → status lunas, saldo kas bertambah, jurnal tercatat; cicilan terakhir → pinjaman `lunas`.
- Halaman menampilkan keuntungan koperasi dari bunga (per bulan & akumulasi).

**US-ANG-1:** Sebagai Bendahara, saya mengonfirmasi angsuran bulanan lintas anggota.
- *Acceptance:* Centang + konfirmasi → status lunas; `saldo_pinjaman` bertambah; `jurnal_kas` tercatat; bila cicilan terakhir → pinjaman `lunas`.

**US-ANG-2:** Sebagai Bendahara/Ketua, saya melihat keuntungan bunga.
- *Acceptance:* Keuntungan bulan berjalan & akumulasi dihitung benar dari `nominal_bunga`.

### 3.7 Kas Koperasi (`Realized`)
- **2 kantong**: `saldo_pinjaman` & `saldo_dana_sosial` (hasil restrukturisasi tabel kas).
- Topup manual (Bendahara) → tambah saldo + jurnal; **Saldo Awal** (`catatSaldoAwal`).
- Semua mutasi di `jurnal_kas` (tipe masuk/keluar, kategori, kantong, `saldo_setelah`, referensi).
- Peringatan saldo rendah bila di bawah ambang batas.

**US-KAS-1:** Sebagai Bendahara, saya menambah saldo kas tiap awal bulan.
- *Acceptance:* Topup menambah saldo kantong terkait & mencatat jurnal dengan `saldo_setelah`.

**US-KAS-2:** Sebagai Bendahara/Admin, saya melihat riwayat mutasi semua kantong.
- *Acceptance:* Riwayat lengkap (masuk/keluar, kategori, kantong) tampil benar.

### 3.8 Modul Pengeluaran (`Realized` — Tahap 2 roadmap)
- Catat pengeluaran `koperasi` (potong `saldo_pinjaman`) atau `dana_sosial` (potong `saldo_dana_sosial`).
- Validasi saldo cukup ditangani di `JurnalKasService`; total per jenis ditampilkan.

**US-PENG-1:** Sebagai Bendahara, saya mencatat pengeluaran koperasi/dana sosial.
- *Acceptance:* Pengeluaran tersimpan + jurnal ke kantong yang benar; gagal bila saldo kantong tidak cukup.

### 3.9 Limit Khusus via Pengajuan (`Realized` — Tahap 3 roadmap)
- Anggota ajukan limit (`portal.pengajuan-limit`) → Ketua tinjau/approve (`ketua.pengajuan-limit`) → `anggota.limit_custom` diperbarui + Audit Log.
- Wajib: tidak ada pengajuan menunggu; `limit_diminta` > `limit_saat_ini`.

**US-LIM-1:** Sebagai Anggota, saya mengajukan kenaikan limit khusus.
- *Acceptance:* Ditolak bila masih ada pengajuan `diajukan` atau `limit_diminta` ≤ limit saat ini.

**US-LIM-2:** Sebagai Ketua, saya menyetujui pengajuan limit.
- *Acceptance:* Approve → `anggota.limit_custom` = `limit_diminta`; perubahan tercatat di Audit Log.

### 3.10 Pengaturan & Role/Permission (`Realized`)
- Tab **Bunga**, **Limit Pinjaman** (4 kategori), **Tenor** (rentang + tenor maksimal), **Simpanan** (pokok/wajib/dana sosial).
- Akses via tombol gear (wajib konfirmasi password — `password.confirm`).
- **Kelola Role**: buat/edit/hapus role baru (bawaan tidak bisa dihapus), atur permission via checklist; sidebar menyesuaikan otomatis.
- Semua perubahan → Audit Log.

**US-SET-1:** Sebagai Admin, saya mengubah pengaturan sistem.
- *Acceptance:* Perubahan tersimpan & tercatat di Audit Log; pengaturan berlaku untuk pengajuan baru (bunga tidak memengaruhi pinjaman berjalan).

**US-SET-2:** Sebagai Admin, saya mengelola role & hak akses.
- *Acceptance:* Role baru terbuat; permission tersimpan; role bawaan tidak bisa dihapus (UI & backend).

### 3.11 Portal Anggota (`Realized`)
- **Dashboard**: hero card (sapaan, lama keanggotaan), card pinjaman aktif (progress, cicilan berikutnya), card pengajuan berjalan (step indicator), panel tenor & simpanan dinamis, aktivitas terbaru.
- **Riwayat**: tab Pinjaman/Simpanan, ringkasan atas, filter periode.
- **Profil**: halaman profil + **rekening anggota** (tambah, set default, hapus) — `RekeningAnggota`.

**US-PORT-1:** Sebagai Anggota, saya melihat ringkasan keuangan & status pengajuan di portal.
- *Acceptance:* Card pinjaman aktif & pengajuan berjalan menampilkan progress/step dengan benar.

**US-PORT-2:** Sebagai Anggota, saya mengelola rekening pribadi untuk pencairan.
- *Acceptance:* Rekening bisa ditambah, di-set default, dihapus; snapshot ke `pinjaman` saat mengajukan.

---

## 4. Aturan Bisnis Inti (Ringkasan)

| Aturan | Detail |
|---|---|
| **Limit pinjaman** | 4 kategori: `<1th`=Rp1jt, `1–3th`=Rp5jt, `3–5th`=Rp7jt, `>5th`=Rp10jt. Override via `limit_custom`. |
| **Reloan** | Anggota non-<1th dengan sisa ≤2 angsuran boleh 1x (`sudah_pakai_privilege_reloan`); <1th wajib lunas. |
| **Approval berjenjang** | Bendahara (tahap 1) → Ketua (final) → Bendahara cair. |
| **Bunga menurun** | 1% dari sisa pokok bulan berjalan; di-snapshot saat pinjaman dibuat. |
| **2 kantong kas** | `saldo_pinjaman` & `saldo_dana_sosial`; mutasi terkunci (`lockForUpdate`) agar aman race condition. |
| **Simpanan wajib** | Diinput manual Bendahara per bulan (tidak auto-generate). |
| **Pencairan** | Hanya bila saldo kantong pinjaman cukup; else ditolak dengan pesan. |

---

## 5. Roadmap Pengembangan (Meeting 15 Agustus 2026)

| Tahap | Fitur | Status |
|---|---|---|
| **1 — Fondasi Data** | Limit baru (4 kategori), 2 Kantong Kas, Cabang Jakarta, Perluasan Akses (SSO, profil rekening, import) | `Realized` |
| **2 — Modul Pengeluaran** | Pencatatan pengeluaran koperasi & dana sosial | `Realized` |
| **3 — Limit Khusus (pengajuan)** | Anggota ajukan → Ketua setujui → `limit_custom` | `Realized` |
| **4 — Percepatan** | Pelunasan langsung & penambahan bulan pada pinjaman berjalan | `Planned` |
| **5 — Modul Resign** | Penanganan anggota resign (pelunasan/penutupan) | `Planned` |
| **6 — Tutup Buku** | Proses tutup buku periode | `Planned` |
| **7 — Penyempurnaan UI** | Tab per Cabang, Cetak Slip, Tanda Tangan Digital | `Planned` (sebagian: Cabang sudah ada) |

---

## 6. Backlog / TODO Belum Realisasi (`Planned`)

- **Validasi keanggotaan**: wajib 3 bulan sejak jadi anggota sebelum boleh pengajuan (`DEVELOPMENT_LOG` Fix).
- **Validasi simpanan pokok 1 tahun**: atau ditampilkan di form approval Bendahara/Ketua.
- **Notifikasi**: setelah pengajuan awal, approve bendahara, approve ketua, angsuran & simpanan terbayar.
- **Edit ajuan pinjaman (tenor)** oleh Bendahara/Ketua.
- **Laporan / rekap tercetak**: permission `laporan.lihat` sudah ada tapi halaman belum dibangun.
- **Multi-tenant** (opsional).
- **Auto-buat user login** saat Admin menambah anggota (saat ini manual).
- **Kelola User Staff** via UI (tambah/nonaktifkan Admin/Bendahara/Ketua).
- **Halaman Profil Anggota** lengkap (menu sudah ada, isi belum penuh).

---

## 7. Kebutuhan Non-Fungsional

| Kebutuhan | Status | Catatan |
|---|---|---|
| **Anti race condition** | `Realized` | `KasKoperasi::lockForUpdate()` di `JurnalKasService` — mutasi kas atomic. |
| **Idempotency key** | `Planned` | Mencegah double-write pada aksi CRUD bersamaan (double klik). |
| **Reconciliation otomatis** | `Planned` | Menyeimbangkan bila terjadi minus antar kantong kas. |
| **Audit trail keuangan** | `Realized` (sebagian) | `AuditLog` untuk pengaturan, role, limit khusus; perluasan ke mutasi kas direncanakan. |
| **DB-agnostic migration** | `Realized` | Tes jalan di SQLite `:memory:`; jaga migration tetap agnostic. |
| **Responsivitas** | `Realized` (sebagian) | Portal (navbar) OK di HP/tablet/desktop; wizard panel info kecil & sidebar perlu cek lanjut. |

---

## 8. Open Questions / Keputusan Ditunda

1. **Saldo kas tidak cukup saat pencairan** — revisi pengajuan atau langsung tolak? Bila revisi, dari pihak koperasi atau pengaju? *(Saat ini: ditolak dengan pesan.)*
2. **Pembayaran angsuran** — tetap manual (konfirmasi Bendahara) atau otomatis? Bila otomatis (dipotong akhir bulan), ada risiko gaji sudah dipotong tapi status di sistem belum berubah karena menunggu update otomatis.
3. **Kebutuhan report** — apakah ada format laporan wajib (harian/bulanan/tahunan) yang harus disediakan?

---

## 9. Metrik Keberhasilan (Proposal)

- 100% alur pinjaman (ajukan → lunas) berjalan tanpa intervensi manual di DB.
- Nol inkonsistensi saldo kas (reconciliation seimbang).
- Waktu onboarding anggota baru < 5 menit (termasuk auto-create user, bila direalisasi).
- Kepatuhan audit: seluruh perubahan data sensitif tercatat di Audit Log.

---

*Dokumen ini live-doc: item `Planned` naik status ke `Realized` seiring implementasi, mengikuti `docs/DEVELOPMENT_LOG.md`.*
