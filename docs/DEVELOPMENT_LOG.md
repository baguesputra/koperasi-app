# Koperasi App
**Tanggal:** 7 Agustus 2026
- Design relasi database (mysql)
- merancang alur proses bisnis koperasi
- merancang fitur penunjang sistem
- menentukan bahasa pemrograman (laravel 13 + inertia js)


**Tanggal:** 8 dan 10 Agustus 2026

## 1. Login & Role
- [x] Login sebagai Admin
- [x] Login sebagai Bendahara
- [x] Login sebagai Ketua Koperasi
- [x] Login sebagai Anggota (beberapa akun: baru, sedang, lama, reloan)
- [x] Redirect otomatis ke Portal saat login sebagai Anggota
- [x] Redirect otomatis ke Dashboard saat login sebagai Admin/Bendahara/Ketua
- [x] Logout berhasil dari semua role

## 2. Dashboard Koperasi (Admin/Bendahara/Ketua)
- [x] 6 widget tampil dengan angka benar (Anggota Aktif, Simpanan, Pinjaman Outstanding, Saldo Kas, Keuntungan Bulan Ini, Dana Sosial)
- [x] Section "Perlu Ditindaklanjuti" — 4 kartu tampil dan bisa diklik ke halaman terkait
- [x] Grafik tren Simpanan vs Pinjaman tampil tanpa error
- [x] Aktivitas Terbaru tampil (gabungan pinjaman + angsuran)
- [x] Sidebar menu sesuai role yang login (grup & item hak akses)

## 3. Modul Anggota
- [x] List Anggota — tampil, cari nama, filter cabang, filter status
- [x] Tambah Anggota — No. Anggota auto-generate, Simpanan Pokok otomatis tercatat
- [x] Edit Anggota — ubah data tersimpan dengan benar
- [x] Limit Pinjaman Khusus — isi nominal + alasan, cek tersimpan & tercatat di Audit Log

## 4. Modul Simpanan
- [x] Halaman List Simpanan (sisi Koperasi) — total per anggota benar
- [x] Detail Simpanan per anggota — riwayat lengkap tampil
- [x] Konfirmasi Simpanan Wajib (Bendahara) — pilih bulan, centang massal, konfirmasi
- [x] Setelah konfirmasi — cek 2 baris tercipta (Wajib + Dana Sosial)
- [x] Total Dana Sosial Terkumpul bertambah setelah konfirmasi

## 5. Modul Pinjaman — Sisi Anggota
- [x] Wizard Ajukan Pinjaman — input nominal, auto-validasi limit
- [x] Pilih tenor — sesuai batas Tabel Tenor
- [x] Simulasi cicilan tampil benar (bunga menurun)
- [x] Submit pengajuan berhasil
- [x] Uji privilege reloan (anggota dengan sisa 2x angsuran, boleh ajukan lagi)
- [ ] Uji privilege reloan terpakai (coba ajukan lagi setelah pakai privilege — harus ditolak)
- [ ] Uji anggota <1 tahun dengan pinjaman aktif — coba ajukan baru (harus ditolak, wajib lunas dulu)

## 6. Modul Pinjaman — Sisi Bendahara & Ketua
- [x] List "Menunggu Tinjauan" (Bendahara) tampil
- [x] Approve pinjaman (Bendahara) — wajib isi catatan
- [x] Reject pinjaman (Bendahara) — wajib isi catatan
- [x] List "Menunggu Approval Final" (Ketua) tampil, termasuk catatan Bendahara
- [x] Approve final (Ketua) — jadwal angsuran otomatis ter-generate
- [x] Approve final — saldo Kas Koperasi berkurang sesuai nominal
- [x] Approve final saat saldo kas TIDAK cukup — harus ditolak dengan pesan jelas
- [x] Reject final (Ketua) — status jadi ditolak

## 7. Modul Angsuran
- [x] Halaman Angsuran (Bendahara) — pilih bulan, cicilan jatuh tempo tampil
- [x] Centang massal + konfirmasi — status jadi lunas
- [x] Saldo Kas Koperasi bertambah setelah konfirmasi
- [ ] Keuntungan (bunga) bulan ini & keseluruhan terhitung benar
- [ ] Cicilan terakhir dikonfirmasi — pinjaman otomatis jadi "Lunas"

## 8. Kas Koperasi
- [x] Saldo tampil benar
- [x] Topup saldo (Bendahara) — saldo bertambah, tercatat di jurnal
- [x] Riwayat mutasi lengkap (masuk/keluar, kategori benar)
- [x] Peringatan saldo rendah tampil kalau di bawah ambang batas

## 9. Portal Anggota — Dashboard & Riwayat
- [x] Hero card — sapaan, lama keanggotaan format benar (tidak desimal)
- [x] Card Pinjaman Aktif — progress bar, cicilan berikutnya, bisa diklik ke Riwayat
- [x] Card Pengajuan Berjalan — step indicator (tinjau Bendahara / approval Ketua)
- [ ] Card Pengajuan Ditolak — alasan tampil
- [ ] 3 widget keuangan (Total Simpanan, Pokok, Wajib) akurat
- [x] Panel kanan — Tenor & Simpanan tampil dinamis sesuai Pengaturan
- [x] Aktivitas Terbaru — compact, tidak perlu scroll berlebihan
- [x] Halaman Riwayat — tab Pinjaman/Simpanan, ringkasan atas, filter periode simpanan
- [ ] Alasan penolakan tampil di kartu pinjaman yang ditolak (Riwayat)

## 10. Pengaturan (Admin)
- [x] Akses via tombol gear di Topbar, minta konfirmasi password
- [ ] Tab Bunga — ubah persentase, cek snapshot pinjaman lama tidak berubah
- [x] Tab Limit Pinjaman — ubah nominal per kategori
- [x] Tab Tenor — tambah/hapus rentang
- [x] Tab Simpanan — ubah nominal Pokok/Wajib/Dana Sosial
- [x] Semua perubahan tercatat di Audit Log

## 11. Kelola Role & Permission (Admin)
- [x] List Role tampil, jumlah user per role benar
- [x] Tambah role baru — berhasil dibuat
- [x] Edit hak akses role — checklist permission, simpan
- [x] Role bawaan (Admin/Bendahara/Ketua/Anggota) — tombol hapus tidak muncul
- [ ] Coba hapus role bawaan via request langsung — ditolak backend
- [x] Hapus role baru yang belum dipakai user — berhasil
- [x] Sidebar/menu otomatis menyesuaikan setelah permission role diubah

## 12. Responsivitas
- [ ] Sisi Koperasi (Sidebar) — cek di layar besar & kecil
- [x] Sisi Anggota (Navbar) — cek di HP, tablet, desktop
- [x] Wizard Pengajuan Pinjaman — panel info kanan hilang di layar kecil
- [x] Semua tombol & teks tetap terbaca jelas di ukuran layar kecil


## Noted
- Jika saldo tidak mencukupi apakah pengajuan ditolak atau direvisi?
  * Jika direvisi apakah dari pihak koperasi atau pihak pengaju?
- untuk pembayaran angsuran apakah tetap diotomatis kan?
  * Jika otomatis maka ditentukan dipotong akhir bulan namun akan ada miss jika gaji masuk duluan namun sudah dipotong tetapi di sistem belum berubah status angsuran nya karena menunggu waktu otomatis nya update
- Keperluan report apakah ada?

## Fix
- [ ] Tabel anggota belum riil dari koperasi nya
- [x] pengajuan masih belum ada terbilang, keterangan, bank, no rekening, atas nama rekening ( ditambahkan jika ada bisa pengecekan rekening)
- [ ] validasi setelah jadi anggota harus 3 bulan terdahulu baru bisa pengajuan
- [ ] validasi harus sudah simpanan pokok selama 1 tahun atau ditampilkan pada form approval bendahara dan ketua
- [x] validasi awal simpanan 100.000 50.000 wajib di awal saja 45.000 tiap bulan dan 5.000 dana sosial tidak muncul di anggota


# Testing Checklist — Koperasi App
**Tanggal:** 11 Agustus 2026
 
- [x] Perbaikan login dengan nomor karyawan dan change password pertama kali login
- [x] Fitur import excel untuk data anggota dan kelola anggota
- [x] Improve fitur pinjaman, simpanan dan pembayaran angsuran untuk ketepatan data
- [x] Testing kembali alur proses bisnis dari input anggota hingga pengajuan aktif

# Testing Checklist — Koperasi App
**Tanggal:** 12 Agustus 2026
- [x] Persiapan Services dan Controller untuk SSO
- [x] Perbaikan bug ui dashboard sidebar  

# Testing Checklist — Koperasi App
**Tanggal:** 13 Agustus 2026
- [x] Testing deploy aplikasi
- [x] Persiapan callback untuk integrasi portal

# Testing Checklist — Koperasi App
**Tanggal:** 14 Agustus 2026
- [x] integrasi login portal gate dengan koperasi
- [x] improve tampilan dashboard untuk memudahkan user dalam navigasi menu
- [x] install gitlab self host untuk repository project internal
- [x] setting development mode sso dan mode local

# Testing Checklist — Koperasi App
**Tanggal:** 15 Agustus 2026

Fitur
- [ ] notifikasi setelah pengajuan awal, setelah approve bendahara dan approve ketua
- [ ] notifikasi angsuran dan simpanan sudah terbayarkan ke bendahara atau ketua
- [ ] fitur edit ajuan pinjaman (tenor) dari pihak bendahara dan ketua
- [ ] multi tenant ( opsional )
- [ ] import excel atau tambah anggota otomatis buat user juga


# Meeting dengan Koperasi
**Tanggal:** 15 Agustus 2026
Summary hasil meeting
Tahap 1 (Fondasi Data) — Limit baru, 2 Kantong Kas, Cabang Jakarta, Perluasan Akses
Tahap 2 — Modul Pengeluaran
Tahap 3 — Fitur Limit Khusus (pengajuan)
Tahap 4 — Fitur Percepatan (paling kompleks, butuh waktu lebih)
Tahap 5 — Modul Resign
Tahap 6 — Tutup Buku
Tahap 7 — Tab per Cabang, Cetak Slip, Tanda Tangan Digital (penyempurnaan UI)

Optimisasi
- LockUpdate ( mengatasi jika terjadi data yang sama masuk ke kas sehingga perlu dibuatkan antrian )
- Idempotency key ( mentgatasi jika terjadi pengguana action bersamaan sehingga data double atau tidak masuk )
- Reconciliation ( jika terjadi minus antara kas kantong )

# Testing Checklist — Koperasi App
**Tanggal:** 18 Agustus 2026
Summary hasil meeting dengan koperasi 15 Agustus 2026
- [x] Update alur sistem arus kas koperasi
- [x] Rekonstruksi Pondasi Data Limit baru, 2 Kantong Kas (Pinjaman dan dana sosial), Cabang Jakarta, Perluasan Akses
- [x] Pembuatan Modul Pengeluaran
- [x] Pembuatan Fitur Limit Khusus (pengajuan) *ui nya di portal dashboard belum ada
- [x] Sentralisasi Pengelolaan Arus Kas Koperasi dan Pembatasan Perubahan Data Arus Kas

# Testing Checklist — Koperasi App
**Tanggal:** 19 Agustus 2026
Summary hasil meeting dengan koperasi 15 Agustus 2026
- [x] Perbaikan race condition pada data konfirmasi angsuran dan simpanan\
- [x] Perbaikan Navigasi dan user interface Master Simpanan
- [x] Penambahan statistik pinjaman pada master pinjaman
- [ ] Menambah idempotency key jika terjadi double klik action crud
- [ ] Perancangan audit trail dari sisi keuangan
- [x] Menambah reconciliation otomatis
- [ ] Fitur percepatan, penamabahan bulan dan pelunasan langsung yang berjalan

# Testing Checklist — Koperasi App
**Tanggal:** 20 Agustus 2026
Summary hasil meeting dengan koperasi 15 Agustus 2026
- [ ] Gabung jadi 1 halaman approval bendahara dan ketua
