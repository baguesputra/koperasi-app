# PRD — Koperasi App (Product Requirements Document)

**Versi Dokumen:** 1.1
**Tanggal:** 25 Agustus 2026
**Status:** Development — seluruh alur bisnis inti + modul pendukung + testing suite berjalan
**Bahasa:** Indonesia (sesuai konvensi project)
**Sumber analisa:** kode aktual (`app/`, `routes/`, `resources/js/`, `database/`), `docs/Manual-Book-Koperasi-App.md` (v2.0), `docs/ERD-Koperasi-App.md`, `docs/DEVELOPMENT_LOG.md`, `docs/TESTING.md`, dan ringkasan implementasi Agustus 2026.

> **Catatan status tiap fitur:** `Realized` = sudah ada di code · `Planned` = belum dibangun (roadmap/backlog).

---

## 1. Pendahuluan

### 1.1 Tujuan Dokumen
Dokumen ini menjadi acuan kebutuhan produk Koperasi App: merangkum fitur yang **sudah realisasi** (hasil analisa code, bukan sekadar manual book usang) dan **rencana pengembangan** (roadmap/backlog). Tiap modul dilengkapi *user story* dan *acceptance criteria* (kriteria penerimaan) yang bisa dipakai sebagai acuan test.

### 1.2 Konteks
Manual Book v2.0 (25 Agustus) sudah mencerminkan kondisi code terkini. Semua fitur roadmap awal (4 kantong kas, modul pengeluaran, limit khusus, percepatan, resign, laporan, notifikasi WA, laporan, testing) **sudah diimplementasikan**. Sisa backlog: validasi keanggotaan, edit ajuan pinjaman, multi-tenant, profil lengkap, tutup buku, kelola user staff via UI.

### 1.3 Stack & Arsitektur
- **Backend:** Laravel 13, MySQL, Spatie Laravel Permission.
- **Frontend:** Inertia v2 + React 18 + Tailwind 3 + Vite.
- **Pola controller per-role:** `Portal/` (layanan anggota), `Bendahara/` (aksi bendahara), `Ketua/` (approval final), namespace akar (Admin).
- **Logika bisnis** di `app/Services/` (keuangan, pinjaman, simpanan, SSO) — tidak di-inline di controller.
- **Audit** via `AuditLog::catat(...)`.
- **Export/Import Excel** di `app/Imports` & `app/Exports`.
- **Notifikasi WA** via Baileys gateway (Docker) + antrean FIFO 2 detik.

---

## 2. Persona & Hak Akses

| Role | Fungsi Utama |
|---|---|
| **Admin** | Kelola anggota, resign & reaktivasi, pengaturan sistem, role & hak akses |
| **Bendahara** | Konfirmasi simpanan/angsuran, tinjau pinjaman (tahap 1), pencairan mandiri Ketua, kelola kas, catat pengeluaran, cetak laporan |
| **Ketua Koperasi** | Approval final pinjaman (tahap 2), approval perubahan tenor, persetujuan limit khusus, cetak laporan |
| **Anggota** | Ajukan pinjaman, ajukan limit khusus, ajukan perubahan tenor/pelunasan, lihat saldo & riwayat via portal |

**Permission (Spatie) — 15 item ter-seed:**
`anggota.lihat`, `anggota.kelola`, `anggota.resign`, `simpanan.lihat`, `simpanan.konfirmasi`, `pinjaman.lihat`, `pinjaman.tinjau-bendahara`, `pinjaman.approve-ketua`, `angsuran.konfirmasi`, `kas.lihat`, `kas.topup`, `laporan.lihat`, `pengaturan.kelola`, `user.kelola`, `portal.akses`.

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
- Admin: list (cari nama, filter cabang/status), tambah (No. Anggota `ANG-2026-0001` auto-generate, simpanan pokok auto + jurnal `simpanan_pokok_masuk`, user login auto + role anggota + wajib ganti password), edit, limit khusus per-anggota + Audit Log, resign & reaktivasi.
- **Import Excel** anggota + download template (`anggota.import`, `anggota.template`).
- **Cabang:** Banjarmasin, Samarinda, Palangka, Jakarta (sudah ditambah).
- **Limit khusus** di edit anggota: isi nominal + alasan → tercatat di Audit Log.
- **Resign:** Admin klik Resign → settlement dihitung (simpanan dikembalikan, pinjaman dilunasi) → status resign, settlement JSON, slip PDF, akun login diblokir (middleware).
- **Reaktivasi:** Admin klik Aktifkan Kembali → status aktif, histori resign tetap tersimpan.

**US-ANG-1:** Sebagai Admin, saya menambah anggota agar sistem membuat No. Anggota, user login, & mencatat simpanan pokok + jurnal otomatis.
- *Acceptance:* No. Anggota ter-generate unik; user login dibuat + role anggota; simpanan pokok + jurnal `simpanan_pokok_masuk` ke kantong Simpanan.

**US-ANG-2:** Sebagai Admin, saya mengimpor data anggota massal dari Excel.
- *Acceptance:* Template ter-download; impor menghasilkan baris anggota + simpanan pokok tanpa error duplikat.

**US-ANG-3:** Sebagai Admin, saya menetapkan limit khusus untuk anggota tertentu.
- *Acceptance:* Limit override tersimpan di `anggota.limit_custom`; perubahan tercatat di Audit Log dengan alasan.

**US-ANG-4:** Sebagai Admin, saya memproses resign anggota.
- *Acceptance:* Settlement dihitung (simpanan dikembalikan, pinjaman dilunasi dari simpanan); status resign + settlement JSON + slip PDF; user login diblokir; jurnal return ke kantong Pengembalian Simpanan.

**US-ANG-5:** Sebagai Admin, saya mengaktifkan kembali anggota resign.
- *Acceptance:* Status kembali `aktif`; histori resign & settlement tetap tersimpan.

### 3.3 Modul Simpanan (`Realized`)
Tiga jenis: **Pokok** (sekali, auto saat anggota dibuat + jurnal `simpanan_pokok_masuk`), **Wajib** (bulanan, konfirmasi Bendahara, jurnal `simpanan_wajib_masuk`), **Dana Sosial** (auto bersama konfirmasi Wajib, jurnal `dana_sosial_bulanan`, terpisah dari saldo pribadi).
- Konfirmasi massal: pilih bulan → sistem tampilkan anggota aktif belum ada simpanan wajib → centang → konfirmasi → 2 baris (Wajib + Dana Sosial) tercipta; dedup otomatis.
- Nominal (dari seeder): Pokok Rp100.000; Wajib Rp45.000/bulan; Dana Sosial Rp5.000/bulan.

**US-SIM-1:** Sebagai Bendahara, saya mengonfirmasi simpanan wajib bulanan per anggota.
- *Acceptance:* Setelah konfirmasi, 2 baris (wajib + dana_sosial) tercatat + jurnal ke kantong masing-masing; total Dana Sosial Terkumpul bertambah.

**US-SIM-2:** Sebagai Bendahara/Admin, saya melihat total simpanan per anggota & riwayat lengkap.
- *Acceptance:* Halaman list & detail menampilkan akumulasi dan riwayat per jenis dengan benar.

### 3.4 Modul Pinjaman — Sisi Anggota (`Realized`)
Wizard 3 langkah: **Nominal** (cek limit), **Tenor** (dibatasi `tabel_tenor`), **Ringkasan** (simulasi cicilan bunga menurun). Submit → status `diajukan`.
- Form tambahan: keperluan, bank, no rekening, atas nama (snapshot ke `pinjaman`).

**US-PIN-1:** Sebagai Anggota, saya mengajukan pinjaman dengan nominal dalam limit & tenor sesuai aturan.
- *Acceptance:* Sistem menolak nominal melebihi limit (otomatis/kustom); tenor dibatasi `tabel_tenor` (10 rentang sampai 10jt); simulasi cicilan (declining balance) tampil benar; submit menghasilkan status `diajukan`.

**US-PIN-2 (Reloan):** Sebagai anggota non-<1 tahun dengan sisa ≤2 angsuran, saya boleh ajukan pinjaman baru sekali.
- *Acceptance:* `EligibilitasPinjamanService` mengizinkan 1x via flag `sudah_pakai_privilege_reloan`; pengajuan ke-2 ditolak. Anggota <1 tahun wajib lunas dulu.

### 3.5 Modul Pinjaman — Approval & Pencairan (`Realized`)
Alur normal: `diajukan` → (Bendahara approve/reject + catatan) `approved_bendahara` → (Ketua approve/reject + catatan, lihat catatan Bendahara) `approved_ketua` → (Bendahara **cair**) `aktif` (jadwal angsuran generate, jurnal pencairan, WA + PDF) → `lunas`.

**2 kantong kas utama:** pencairan mengurangi `saldo_pinjaman`; saldo tidak cukup → ditolak dengan pesan jelas.

**Alur mandiri Ketua (pengajuan sendiri oleh Ketua):**
```
Diajukan oleh Ketua (cair_oleh_bendahara = true)
    ↓
Bendahara meninjau → Setujui (verifikasi dokumen saja)
    ↓
Bendahara mencairkan → Status "Aktif", jadwal angsuran, jurnal pencairan, WA + PDF
```

**US-PIN-3:** Sebagai Bendahara, saya meninjau & memutuskan pengajuan (setujui/tolak wajib catatan min 5 karakter).
- *Acceptance:* Approve → `approved_bendahara`; reject → `ditolak` dengan catatan tersimpan.

**US-PIN-4:** Sebagai Ketua, saya memberikan approval final.
- *Acceptance:* Approve → `approved_ketua` (lihat catatan Bendahara); reject → `ditolak`.

**US-PIN-5:** Sebagai Bendahara, saya mencairkan dana pinjaman yang sudah disetujui Ketua.
- *Acceptance:* `cair` mengurangi `saldo_pinjaman`, men-generate jadwal angsuran, status → `aktif`, `tanggal_pencairan` & `cair_oleh_bendahara` tercatat; gagal bila saldo kantong pinjaman tidak cukup (pesan jelas).

**US-PIN-6:** Sebagai sistem, saya men-snapshot bunga saat pinjaman dibuat.
- *Acceptance:* `persentase_bunga` tersimpan; perubahan `setting_bunga` di kemudian hari tidak memengaruhi pinjaman berjalan.

### 3.6 Modul Angsuran (`Realized`)
- Jadwal auto saat `aktif`, **bunga menurun** (1% dari sisa pokok bulan berjalan, snapshot). Cicilan terakhir menyerap sisa pembulatan supaya Σpokok == nominal persis.
- Konfirmasi massal (Bendahara): pilih bulan (navigasi ‹ › + "Bulan ini") → centang cicilan → konfirmasi → status lunas, saldo kas bertambah, jurnal tercatat; cicilan terakhir → pinjaman `lunas`.
- Halaman menampilkan keuntungan koperasi dari bunga (per bulan & akumulasi).
- Badge angsuran: 🔴 Terlambat (jatuh tempo lewat), 🟡 Perubahan tenor diajukan (pengajuan aktif).
- Search nama/no. anggota live; selection bar sticky dengan total rupiah; empty state dinamis.

**US-ANG-1:** Sebagai Bendahara, saya mengonfirmasi angsuran bulanan lintas anggota.
- *Acceptance:* Centang + konfirmasi → status lunas; `saldo_pinjaman` bertambah; `jurnal_kas` tercatat; bila cicilan terakhir → pinjaman `lunas`.

**US-ANG-2:** Sebagai Bendahara/Ketua, saya melihat keuntungan bunga.
- *Acceptance:* Keuntungan bulan berjalan & akumulasi dihitung benar dari `nominal_bunga`.

### 3.7 Kas Koperasi (4 Kantong) (`Realized`)
| Kantong | Fungsi | Masuk | Keluar |
|---------|--------|-------|--------|
| **Dana Pinjaman** | Dana operasional pinjaman | Topup, angsuran, pelunasan resign | Pencairan pinjaman, pengeluaran koperasi |
| **Dana Sosial** | Dana kemanusiaan | Dana sosial bulanan | Santunan/bantuan |
| **Simpanan Anggota** | Titipan pokok + wajib | Pokok/wajib masuk | Return simpanan (resign) |
| **Pengembalian Simpanan** | Transit resign | Masuk dari simpanan resign | Dibayar ke anggota |

- Mutasi tercatat di Jurnal Kas (`tipe`, `kategori`, `kantong`, `saldo_setelah`, referensi).
- Topup manual per kantong (Bendahara); saldo awal via jurnal `saldo_awal`.
- Riwayat tab: Dana Pinjaman / Dana Sosial / Pengembalian Simpanan (real-time saldo outstanding).
- Saldo awal via jurnal `saldo_awal` per kantong → Neraca konsisten dari awal.

**US-KAS-1:** Sebagai Bendahara, saya menambah saldo kas per kantong tiap awal bulan.
- *Acceptance:* Topup menambah saldo kantong terkait & jurnal dengan `saldo_setelah`.

**US-KAS-2:** Sebagai Bendahara/Admin, saya melihat riwayat mutasi semua kantong.
- *Acceptance:* Riwayat lengkap (masuk/keluar, kategori, kantong) tampil benar.

### 3.8 Modul Pengeluaran (`Realized`)
- Catat pengeluaran `koperasi` (potong `saldo_pinjaman`) atau `dana_sosial` (potong `saldo_dana_sosial`).
- Validasi saldo cukup di `JurnalKasService`; total per jenis ditampilkan.

**US-PENG-1:** Sebagai Bendahara, saya mencatat pengeluaran koperasi/dana sosial.
- *Acceptance:* Pengeluaran tersimpan + jurnal ke kantong yang benar; gagal bila saldo kantong tidak cukup.

### 3.11 Percepatan / Perubahan Tenor (`Realized` — Tahap 4 Roadmap)
Anggota dengan pinjaman aktif yang belum pernah menggunakan hak ini mengajukan:

| Jenis | Efek |
|---|-------|
| **Percepat Pelunasan** (tenor diperpendek) | Cicilan naik, total bunga turun |
| **Perpanjangan Tenor** | Cicilan turun, total bunga naik |
| **Pelunasan Total** | Satu cicilan final = sisa pokok + bunga 1 bln |

Alur: Anggota ajukan → Bendahara approve/reject → Ketua approve (pilih berlaku bulan ini/depan) → sistem ganti jadwal (angsuran lama → `digantikan`, jadwal baru dari sisa pokok, flag `sudah_pakai_percepatan = true`).
- Hanya 1× per siklus pinjaman; tidak boleh ada 2 pengajuan berjalan.

**US-PER-1:** Sebagai Anggota, saya mengajukan percepatan pelunasan.
- *Acceptance:* Tenor terpendek, jadwal baru terbentuk, flag ter-set, WA notif keanggota + pengurus.

**US-PER-2:** Sebagai Anggota, saya mengajukan perpanjangan tenor.
- *Acceptance:* Tenor diperpanjang, cicilan turun, total bunga naik, WA notif.

**US-PER-3:** Sebagai Anggota, saya mengajukan pelunasan total.
- *Acceptance:* 1 cicilan final = sisa pokok + bunga 1 bln, WA notif.

**US-PER-4:** Sebagai Bendahara/Ketua, saya memutuskan pengajuan percepatan.
- *Acceptance:* Approve → jadwal baru terbentuk dari sisa pokok; angsuran lama jadi `digantikan`; reject → catatan tersimpan.

### 3.12 Pengajuan Limit Khusus (`Realized` — Tahap 3 Roadmap)
Anggota ajukan limit (`portal.pengajuan-limit`) → Ketua tinjau/approve (`ketua.pengajuan-limit`) → `anggota.limit_custom` diperbarui + Audit Log.
- Validasi: tidak ada pengajuan menunggu; `limit_diminta` > `limit_saat_ini`.
- Bila disetujui → `anggota.limit_custom` = `limit_diminta` + Audit Log.

**US-LIM-1:** Sebagai Anggota, saya mengajukan kenaikan limit khusus.
- *Acceptance:* Ditolak bila ada pengajuan menunggu atau `limit_diminta` ≤ limit saat ini.

**US-LIM-2:** Sebagai Ketua, saya menyetujui pengajuan limit.
- *Acceptance:* Approve → `anggota.limit_custom` = `limit_diminta`; perubahan tercatat di Audit Log.

### 3.13 Resign & Reaktivasi (`Realized` — Tahap 5 Roadmap)
**Alur Resign (Admin):**
1. Pilih anggota aktif → klik Resign
2. Sistem hitung: sisa tagihan pinjaman vs total simpanan (pokok + wajib)
3. Validasi: simpanan harus cukup untuk melunasi pinjaman aktif
4. Proses: simpanan ditarik ke kantong transit "Pengembalian Simpanan" → bayar angsuran/lunas → sisanya dikembalikan ke anggota
5. Status → **resign**, settlement JSON, slip PDF, akun login diblokir (middleware)

**Alur Reaktivasi:**
1. Pilih anggota resign → klik **Aktifkan Kembali**
2. Isi alasan reaktivasi
3. Status → **aktif**, histori resign tetap tersimpan.

**US-RSG-1:** Sebagai Admin, saya memproses resign anggota.
- *Acceptance:* Settlement dihitung (simpanan dikembalikan, pinjaman dilunasi); status resign + settlement JSON + slip PDF; user login diblokir; jurnal return ke kantong Pengembalian Simpanan.

**US-RSG-2:** Sebagai Admin, saya mengaktifkan kembali anggota resign.
- *Acceptance:* Status kembali `aktif`; histori resign & settlement tetap tersimpan.

### 3.14 Laporan (`Realized` — Tahap 6 Roadmap)
13 laporan dengan filter periode + export PDF & Excel:

| Kategori | Laporan | Fungsi |
|---|---|---|
| **Keuangan** | Arus Kas | Mutasi masuk/keluar per kantong, rentang bebas |
| | Neraca Sederhana | Posisi saldo per tanggal cut-off |
| | Keuntungan Bunga | Akumulasi bunga per bulan/tahun (basis SHU) |
| **Pinjaman** | Rekap per Status | Daftar pinjaman beserta statusnya |
| | Pinjaman Jatuh Tempo | Angsuran belum bayar bulan terpilih (follow-up) |
| | Perubahan Tenor | Riwayat pengajuan percepatan/perpanjangan & hasilnya |
| **Simpanan** | Rekap per Anggota | Akumulasi tiap orang (slip tahunan) |
| | Setoran Bulanan | Tren penerimaan wajib & dana sosial per bulan |
| **Anggota** | Daftar Anggota | Aktif/nonaktif + breakdown cabang & lama |
| | Laporan Resign | Siapa resign, kapan, nilai dikembalikan |
| **Operasional** | Rekap Pengeluaran | Per jenis (Koperasi/Dana Sosial), per periode |
| | Rekap Dana Sosial | Terkumpul vs tersalurkan + sisa saldo |
| | Laporan Audit (admin) | Jejak aktivitas: siapa ubah apa kapan |

Fitur: filter periode auto-terapkan, tombol Excel/PDF selalu terlihat, tabel responsive sticky-first, empty state dinamis.

**US-LAP-1:** Sebagai Bendahara/Ketua/Admin, saya mencetak/unduh laporan keuangan & operasional.
- *Acceptance:* Laporan render PDF/Excel dengan filter benar, data benar, format rapi.

### 3.15 Notifikasi WhatsApp (`Realized` — Roadmap Baru)
Sistem mengirim pesan WA formal (format: kop `*KOPERASI KARYAWAN*`, salam Yth., rincian lengkap, status bold, penutup Hormat kami) untuk:
- Pengajuan pinjaman diterima (anggota + pengurus)
- Disetujui Bendahara
- **Disetujui Ketua & dicairkan** → **lampiran Bukti Peminjaman PDF** (jadwal angsuran lengkap)
- Ditolak Bendahara/Ketua
- Konfirmasi simpanan/angsuran → toast in-app (WA massal belum)

**Anti-spam:** antrean FIFO di gateway BAILEYS, jeda minimum 2 detik (`SEND_INTERVAL_MS=2000`); HTTP balas `202` saat masuk antrean → worker tidak menunggu.

**US-WA-1:** Sebagai Anggota, saya menerima notifikasi WA saat pinjaman saya dicairkan dengan lampiran PDF bukti.
- *Acceptance:* WA tiba, PDF terlampir, isi: kop, data anggota, detail pinjaman, jadwal angsuran lengkap, ttd.

**US-WA-2:** Sebagai sistem, saya memastikan WA tidak spam meski banyak pengajuan sekaligus.
- *Acceptance:* Antrean FIFO, jeda 2 detik, `202 Accepted` instan untuk worker.

### 3.16 Cetak Bukti Peminjaman (`Realized`)
Halaman cetak (Admin/Bendahara/Ketua) + WA lampiran:
- Hanya untuk pinjaman `aktif`
- Isi: kop, data anggota, terbilang, detail pinjaman, rekening tujuan, tabel jadwal angsuran lengkap + total, catatan, ttd Anggota/Bendahara
- Format PDF via dompdf, landscape otomatis bila kolom >6

**US-CET-1:** Sebagai Ketua/Bendahara, saya mencetak bukti peminjaman resmi.
- *Acceptance:* PDF render benar dengan data lengkap & format rapi.

### 3.17 Anti-Spam WA Queue (`Realized` — Roadmap Baru)
Antrean FIFO di gateway BAILEYS (Node.js), jeda minimum 2 detik (`SEND_INTERVAL_MS=2000`), HTTP `202 Accepted` instan. Pesan dikirim berurutan, worker Laravel tidak blocking.

**US-SPM-1:** Sebagai sistem, saya memastikan tidak ada spam WA meski banyak pengajuan bersamaan.
- *Acceptance:* Antrean FIFO, jeda 2 detik, `202 Accepted` instan untuk worker.

### 3.17 Testing Suite (`Realized` — Roadmap Baru)
110 test Feature (PHPUnit), 411 assertions, 100% hijau:
- Coverage: Auth (5), WA (8), Laporan (7), Approval (4), Angsuran (3), Percepatan (5), Limit (4), Simpanan (2), Anggota CRUD (5), Resign/Reaktivasi (3), Cetak Bukti (3), Kas/Pengeluaran (4), Portal Riwayat (2), Angsuran (3), Percepatan (5), Limit (4), Simpanan (2), PinjamanMandiri (4), Auth (5), Profile (4), Example (1).
- Pattern: `RefreshDatabase` + `seed()` + `MembuatDataUji` trait + `Queue::fake()`/`Http::fake()` untuk WA.

**US-TST-1:** Sebagai developer, saya memiliki test suite yang memvalidasi seluruh alur bisnis.
- *Acceptance:* `php artisan test` → 110/110 hijau; pipeline CI hijau tanpa flaky test.

---

## 4. Aturan Bisnis Inti (Ringkasan)

| Aturan | Detail |
|---|---|
| **Limit pinjaman** | 4 kategori: `<1th`=Rp1jt, `1–3th`=Rp5jt, `3–5th`=Rp7jt, `>5th`=Rp10jt. Override via `limit_custom`. |
| **Reloan** | Anggota non-<1th dengan sisa ≤2 angsuran boleh 1× (`sudah_pakai_privilege_reloan`); <1th wajib lunas. |
| **Approval berjenjang** | Bendahara (tahap 1) → Ketua (final) → Bendahara cair. Mandiri Ketua: Bendahara approve → Bendahara cair. |
| **Bunga menurun** | 1% dari sisa pokok bulan berjalan; di-snapshot saat pinjaman dibuat. Cicilan terakhir menyerap sisa pembulatan. |
| **4 kantong kas** | `saldo_pinjaman`, `saldo_dana_sosial`, `saldo_simpanan`, `saldo_pengembalian_simpanan`; mutasi terkunci (`lockForUpdate`). |
| **Simpanan wajib** | Diinput manual Bendahara per bulan (tidak auto-generate). |
| **Pencairan** | Hanya bila saldo kantong pinjaman cukup; else ditolak dengan pesan jelas. |
| **Tutup buku** | Periode bebas (tanggal cut-off bebas), bukan hanya bulanan. |
| **Pembulatan cicilan** | Cicilan terakhir menyerap sisa pembulatan supaya Σpokok == nominal persis. |

---

## 5. Roadmap Pengembangan (Meeting 15 Agustus 2026 — Update 25 Agustus 2026)

| Tahap | Fitur | Status |
|---|---|---|
| **1 — Fondasi Data** | Limit baru (4 kategori), 4 Kantong Kas, Cabang Jakarta, Akses (SSO, profil rekening, import) | `Realized` |
| **2 — Modul Pengeluaran** | Pencatatan pengeluaran koperasi & dana sosial | `Realized` |
| **3 — Limit Khusus (pengajuan)** | Anggota ajukan → Ketua setujui → `limit_custom` | `Realized` |
| **4 — Percepatan** | Pelunasan langsung & penambahan bulan pada pinjaman berjalan | `Realized` |
| **5 — Modul Resign** | Penanganan anggota resign (pelunasan/penutupan) | `Realized` |
| **6 — Notifikasi WA** | Formal message + PDF lampiran + antrean anti-spam 2 detik | `Realized` |
| **7 — Laporan** | 13 laporan (Keuangan, Pinjaman, Simpanan, Anggota, Operasional) + PDF/Excel | `Realized` |
| **8 — Tutup Buku** | Proses tutup buku periode | `Planned` |
| **9 — Penyempurnaan UI** | Tab per Cabang, Cetak Slip, Tanda Tangan Digital | `Planned` (sebagian: Cabang sudah ada) |
| **10 — Multi-tenant** (opsional) | | `Planned` |
| **11 — Testing Suite** | 110/110 test hijau, 411 assertions | `Realized` |

---

## 6. Backlog / TODO Belum Realisasi (`Planned`)

- **Validasi keanggotaan**: wajib 3 bulan sejak jadi anggota sebelum boleh pengajuan.
- **Validasi simpanan pokok 1 tahun**: atau ditampilkan di form approval Bendahara/Ketua.
- **Notifikasi WA untuk konfirmasi simpanan & angsuran** — baru toast in-app.
- **Edit ajuan pinjaman (tenor)** oleh Bendahara/Ketua.
- **Multi-tenant** (opsional).
- **Auto-buat user login** saat Admin menambah anggota — **sudah Realized** (sudah auto-create user + role + wajib ganti password).
- **Kelola User Staff** via UI (tambah/nonaktifkan Admin/Bendahara/Ketua) — belum.
- **Halaman Profil Anggota** lengkap (menu sudah ada, isi belum penuh).
- **Edit ajuan pinjaman (tenor)** oleh Bendahara/Ketua — belum.
- **Tutup Buku periode** — proses formal belum ada.
- **Multi-tenant** (opsional) — belum.

---

## 7. Kebutuhan Non-Fungsional

| Kebutuhan | Status | Catatan |
|---|---|---|
| **Anti race condition** | `Realized` | `KasKoperasi::lockForUpdate()` di `JurnalKasService` — mutasi kas atomic. |
| **Idempotency key** | `Planned` | Mencegah double-write pada aksi CRUD bersamaan (double klik). |
| **Reconciliation otomatis** | `Planned` | Menyeimbangkan bila terjadi minus antar kantong kas. |
| **Audit trail keuangan** | `Realized` (sebagian) | `AuditLog` untuk pengaturan, role, limit khusus, pengajuan; perluasan ke mutasi kas direncanakan. |
| **DB-agnostic migration** | `Realized` | Tes jalan di SQLite `:memory:`; jaga migration tetap agnostic. |
| **Responsivitas** | `Realized` | Portal (navbar) OK di HP/tablet/desktop; wizard panel info kecil & sidebar perlu cek lanjut. |
| **Anti-spam WA queue** | `Realized` | FIFO 2 detik, `202 Accepted`, worker non-blocking. |
| **Testing Suite** | `Realized` | 110/110 test hijau, 411 assertions, zero flaky. |
| **Test-driven seeder** | `Realized` | Seeder menggunakan service layer supaya jurnal & saldo konsisten. |

---

## 8. Open Questions / Keputusan Ditunda

1. **Saldo kas tidak cukup saat pencairan** — revisi pengajuan atau langsung tolak? Bila revisi, dari pihak koperasi atau pengaju? *(Saat ini: ditolak dengan pesan.)*
2. **Pembayaran angsuran** — tetap manual (konfirmasi Bendahara) atau otomatis? Bila otomatis (dipotong akhir bulan), ada risiko gaji sudah dipotong tapi status di sistem belum berubah karena menunggu update otomatis.
3. **Kebutuhan report** — **answered**: 13 laporan tersedia (PDF/Excel), format bisa dikustomisasi.
4. **Cicilan terakhir pembulatan** — **implemented**: cicilan terakhir menyerap sisa pembulatan supaya Σpokok == nominal persis.

---

## 9. Metrik Keberhasilan (Proposal)

- 100% alur pinjaman (ajukan → lunas) berjalan tanpa intervensi manual di DB.
- Nol inkonsistensi saldo kas (reconciliation seimbang per kantong).
- Waktu onboarding anggota baru < 5 menit (termasuk auto-create user, role, wajib ganti password).
- Kepatuhan audit: seluruh perubahan data sensitif tercatat di Audit Log.
- Test suite 110/110 hijau, zero flaky, CI hijau.

---

## 10. Ringkasan Perubahan v1.0 → v1.1

| Area | v1.0 | v1.1 |
|---|---|---|
| **Kantong Kas** | 2 kantong | 4 kantong (pinjaman, sosial, simpanan, pengembalian) |
| **Modul Percepatan** | Planned | Realized (3 jenis + alur approval) |
| **Modul Resign** | Planned | Realized (settlement, slip PDF, blokir login, reaktivasi) |
| **Modul Limit Khusus** | Planned | Realized (ajukan → Ketua approve → limit_custom + Audit) |
| **Modul Pengeluaran** | Planned | Realized (2 jenis + validasi saldo) |
| **Modul Laporan** | Planned | Realized (13 laporan + PDF/Excel) |
| **Notifikasi WA** | Planned | Realized (formal + PDF lampiran + antrean 2 detik) |
| **Cetak Bukti Peminjaman** | Tidak ada | Realized (PDF dompdf + WA lampiran) |
| **Anti-spam WA Queue** | Tidak ada | Realized (FIFO 2 detik, 202 Accepted) |
| **Test Suite** | Tidak ada | 110 test, 411 assertions, 100% hijau |
| **Seeder** | Parsial | Lengkap (14 seeder + finalizer, service-based) |
| **Roadmap** | 7 tahap | 11 tahap (8 Realized, 3 Planned) |

---

*Dokumen ini live-doc: item `Planned` naik status ke `Realized` seiring implementasi, mengikuti `docs/DEVELOPMENT_LOG.md`.*