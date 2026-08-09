# Manual Book — Koperasi App

**Versi Dokumen:** 1.0
**Terakhir Diperbarui:** 8 Agustus 2026
**Status Aplikasi:** Development — alur bisnis inti sudah berjalan

---

## 1. Gambaran Umum

Koperasi App adalah sistem simpan pinjam internal untuk mengelola keanggotaan, simpanan, pinjaman, dan kas koperasi. Dibangun dengan Laravel 13 + Inertia + React + Tailwind, database MySQL.

**Role yang tersedia saat ini:**

| Role | Fungsi Utama |
|---|---|
| **Admin** | Kelola data anggota, kelola pengaturan sistem, kelola role & hak akses |
| **Bendahara** | Input simpanan, tinjau pengajuan pinjaman (tahap 1), konfirmasi angsuran, kelola kas |
| **Ketua Koperasi** | Approval final pinjaman (tahap 2), pencairan dana |
| **Anggota** | Ajukan pinjaman, lihat saldo simpanan & riwayat, lewat portal khusus |

---

## 2. Alur Bisnis per Modul

### 2.1 Keanggotaan

1. Admin membuka menu **Anggota** → **Tambah Anggota**
2. Isi: nama, cabang, unit bisnis, jabatan (Staff/HOD), tanggal mulai kerja, tanggal jadi anggota
3. Sistem otomatis:
   - Membuat No. Anggota (format `ANG-2026-0001`)
   - Mencatat Simpanan Pokok sesuai nominal di Pengaturan

**Catatan:** akun login untuk Anggota saat ini **belum otomatis dibuat** dari alur ini — masih perlu dibuat manual.

### 2.2 Simpanan

Ada 3 jenis simpanan:

| Jenis | Kapan Tercatat | Catatan |
|---|---|---|
| Pokok | Otomatis, sekali saat anggota dibuat | Nominal diatur di Pengaturan |
| Wajib | Bulanan, dikonfirmasi manual oleh Bendahara | Tidak termasuk dana sosial |
| Dana Sosial | Otomatis bersamaan dengan konfirmasi Simpanan Wajib | Terpisah dari saldo simpanan anggota |

**Alur konfirmasi bulanan** (Bendahara → menu **Konfirmasi Simpanan**):
1. Pilih bulan
2. Sistem tampilkan anggota aktif yang belum ada catatan simpanan wajib bulan itu
3. Centang anggota yang sudah dipotong gajinya (berdasarkan laporan dari tim Payroll)
4. Klik **Konfirmasi Terpilih** — sistem otomatis buat 2 baris (Wajib + Dana Sosial) untuk tiap anggota terpilih

### 2.3 Pengajuan Pinjaman (sisi Anggota)

Anggota login ke portal → menu **Ajukan Pinjaman**, alur wizard 3 langkah:

1. **Nominal** — sistem cek terhadap limit maksimal anggota tersebut
2. **Tenor** — pilihan dibatasi tenor maksimal sesuai nominal (dari Tabel Tenor di Pengaturan)
3. **Ringkasan** — simulasi cicilan per bulan, konfirmasi pengajuan

**Aturan limit pinjaman** (bisa diubah di Pengaturan):

| Kategori | Limit Default |
|---|---|
| Anggota < 1 tahun | Rp 1.000.000 |
| Staff, 1–5 tahun | Rp 7.000.000 |
| HOD, 1–5 tahun | Rp 10.000.000 |
| Anggota ≥ 5 tahun | > Rp 10.000.000 |

**Limit Khusus (override manual):** Admin bisa mengatur limit berbeda untuk anggota tertentu lewat halaman **Edit Anggota** → bagian "Limit Pinjaman Khusus". Kalau diisi, sistem mengikuti angka ini, bukan kategori otomatis. Wajib disertai alasan, dan tercatat di Audit Log.

**Aturan pengajuan ulang (reloan):**
- Anggota dengan pinjaman aktif harus lunas dulu, KECUALI
- Anggota (bukan kategori <1 tahun) dengan sisa angsuran ≤ 2 kali — boleh ajukan baru **satu kali** per siklus pinjaman

### 2.4 Approval Pinjaman

```
Diajukan (Anggota submit)
    ↓
Bendahara meninjau → Setujui / Tolak (wajib isi catatan)
    ↓ (jika disetujui)
Ketua Koperasi meninjau → Setujui / Tolak (wajib isi catatan, bisa lihat catatan Bendahara)
    ↓ (jika disetujui)
Sistem cek saldo Kas Koperasi mencukupi atau tidak
    ↓ (jika cukup)
Status "Aktif" — jadwal angsuran otomatis dibuat, dana dicairkan, kas berkurang
```

**Penting:** kalau saldo Kas Koperasi tidak mencukupi saat Ketua approve, sistem akan menolak proses pencairan dengan pesan error, meski keputusan approval-nya sendiri sudah "disetujui" secara administratif.

### 2.5 Angsuran

Jadwal angsuran dibuat otomatis saat pinjaman aktif, dengan **bunga menurun** (dihitung dari sisa pokok tiap bulan, bukan pokok awal).

**Alur konfirmasi bulanan** (Bendahara → menu **Angsuran**):
1. Pilih bulan
2. Sistem tampilkan semua cicilan jatuh tempo bulan itu (lintas semua anggota)
3. Centang yang sudah dibayar/dipotong
4. Konfirmasi — sistem otomatis:
   - Update status angsuran jadi lunas
   - Tambah saldo Kas Koperasi
   - Catat ke Jurnal Kas
   - Kalau itu cicilan terakhir, pinjaman otomatis jadi "Lunas"

Halaman ini juga menampilkan **keuntungan koperasi** dari bunga (per bulan dan akumulasi keseluruhan).

### 2.6 Kas Koperasi

- Saldo tunggal (digabung semua cabang)
- Berkurang otomatis saat pencairan pinjaman
- Bertambah otomatis saat konfirmasi angsuran
- Bertambah manual lewat tombol **Topup Saldo** (khusus Bendahara), biasanya tiap awal bulan
- Semua mutasi tercatat di **Riwayat Mutasi** (Jurnal Kas)

---

## 3. Pengaturan Sistem (Khusus Admin)

Diakses lewat ikon gear di pojok kanan atas (bukan menu sidebar), **wajib konfirmasi ulang password** setiap sesi karena berkaitan data sensitif.

| Tab | Isi |
|---|---|
| **Bunga** | Persentase bunga pinjaman aktif. Perubahan hanya berlaku untuk pengajuan baru, tidak memengaruhi pinjaman berjalan. |
| **Limit Pinjaman** | Nominal limit per kategori (anggota baru, staff, HOD, anggota lama) |
| **Tenor** | Rentang nominal pinjaman → tenor maksimal (bisa tambah/hapus rentang) |
| **Simpanan** | Nominal Simpanan Pokok, Simpanan Wajib, Dana Sosial |

**Kelola Role & Hak Akses** — tombol terpisah dari halaman Pengaturan:
- Bisa membuat role baru (misal "Accounting")
- Role bawaan (Admin, Bendahara, Ketua Koperasi, Anggota) tidak dapat dihapus
- Tiap role bisa diatur hak akses per fitur lewat checklist

Setiap perubahan pengaturan tercatat otomatis di **Audit Log** (siapa yang mengubah, kapan, dari nilai berapa ke berapa).

---

## 4. Hal yang Perlu Diperhatikan / Belum Selesai

- **Tabel Tenor perlu dilengkapi** — data awal cuma mencakup sampai Rp 5.000.000. Kalau ada pengajuan dengan nominal di atas itu (termasuk lewat Limit Khusus), harus ditambah dulu rentang tenornya lewat Pengaturan, atau sistem akan menolak dengan pesan "ketentuan belum diatur".
- **Akun login Anggota** belum otomatis dibuat saat Admin menambahkan anggota baru.
- **Halaman Profil Anggota** — menunya sudah ada di portal, isinya belum dibangun.
- **Laporan** — belum ada halaman laporan tercetak/terekap.
- **Kelola User Staff** (tambah akun Admin/Bendahara/Ketua lewat UI, nonaktifkan user) — belum dibangun, saat ini masih via akses langsung ke database.
- **Import Excel data anggota** — ditunda sampai data asli tersedia.

---

## 5. Istilah Penting

- **Reloan** — pengajuan pinjaman baru saat pinjaman lama belum lunas sepenuhnya, hanya boleh dengan syarat tertentu (lihat 2.3)
- **Limit Custom / Limit Khusus** — pengecualian limit pinjaman untuk anggota tertentu, di luar aturan kategori umum
- **Dana Sosial** — potongan dari simpanan wajib yang dicatat terpisah, tidak termasuk saldo simpanan pribadi anggota
- **Audit Log** — jejak setiap perubahan data sensitif (pengaturan, role), untuk keperluan akuntabilitas
