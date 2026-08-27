-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 22, 2026 at 03:30 AM
-- Server version: 8.4.3
-- PHP Version: 8.4.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `koperasi_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `anggota`
--

CREATE TABLE `anggota` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `no_anggota` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_karyawan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_ktp` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cabang` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit_bisnis` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `divisi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jabatan` enum('staff','hod') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_mulai_kerja` date NOT NULL,
  `tanggal_jadi_anggota` date NOT NULL,
  `status` enum('aktif','nonaktif','resign') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `tanggal_resign` date DEFAULT NULL,
  `alasan_resign` text COLLATE utf8mb4_unicode_ci,
  `resigned_by` bigint UNSIGNED DEFAULT NULL,
  `resigned_settlement_json` json DEFAULT NULL,
  `reaktivasi_history_json` json DEFAULT NULL,
  `limit_custom` decimal(15,2) DEFAULT NULL,
  `limit_custom_keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `anggota`
--

INSERT INTO `anggota` (`id`, `user_id`, `no_anggota`, `no_karyawan`, `no_ktp`, `nama`, `cabang`, `unit_bisnis`, `department`, `divisi`, `jabatan`, `tanggal_mulai_kerja`, `tanggal_jadi_anggota`, `status`, `tanggal_resign`, `alasan_resign`, `resigned_by`, `resigned_settlement_json`, `reaktivasi_history_json`, `limit_custom`, `limit_custom_keterangan`, `created_at`, `updated_at`) VALUES
(1, 4, 'ANG-2026-0001', 'TOP-100001', NULL, 'Budi Santoso', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'staff', '2025-11-22', '2026-02-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(2, 5, 'ANG-2023-0045', 'TOP-100002', NULL, 'Siti Aminah', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'hod', '2023-06-22', '2023-08-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(3, 6, 'ANG-2019-0012', 'TOP-100003', NULL, 'Ahmad Ridwan', 'Palangka', 'Operasional', 'Operasional', 'Gudang', 'staff', '2019-08-22', '2020-08-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(4, 7, 'ANG-2018-0003', 'TOP-100004', NULL, 'Dewi Lestari', 'Banjarmasin', 'Marketing', 'Marketing', 'Promosi', 'hod', '2018-08-22', '2019-08-22', 'aktif', NULL, NULL, NULL, NULL, NULL, 15000000.00, NULL, '2026-08-21 19:19:54', '2026-08-21 19:29:26'),
(5, 8, 'ANG-2026-0002', 'TOP-100005', '3207000000000000', 'Agus Wijaya', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2026-01-22', '2026-04-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(6, 9, 'ANG-2026-0003', 'TOP-100006', '3207000000000001', 'Rina Marlina', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2024-03-22', '2024-07-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(7, 10, 'ANG-2026-0004', 'TOP-100007', '3207000000000002', 'Bambang Sutrisno', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2023-01-22', '2023-06-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(8, 11, 'ANG-2026-0005', 'TOP-100008', '3207000000000003', 'Sari Rahayu', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2017-11-22', '2018-05-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(9, 12, 'ANG-2026-0006', 'TOP-100009', '3207000000000004', 'Hendra Gunawan', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2025-05-22', '2025-12-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(10, 13, 'ANG-2026-0007', 'TOP-100010', '3207000000000005', 'Dewi Anggraini', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2023-07-22', '2024-03-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(11, 14, 'ANG-2026-0008', 'TOP-100011', '3207000000000006', 'Joko Susanto', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2022-05-22', '2023-02-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(12, 15, 'ANG-2026-0009', 'TOP-100012', '3207000000000007', 'Maya Sari', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2019-08-22', '2020-06-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(13, 16, 'ANG-2026-0010', 'TOP-100013', '3207000000000008', 'Adi Nugroho', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2025-10-22', '2026-01-22', 'nonaktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(14, 17, 'ANG-2026-0011', 'TOP-100014', '3207000000000009', 'Lina Wijayanti', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2023-07-22', '2023-11-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(15, 18, 'ANG-2026-0012', 'TOP-100015', '3207000000000010', 'Rizky Pratama', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2022-12-22', '2023-05-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(16, 19, 'ANG-2026-0013', 'TOP-100016', '3207000000000011', 'Nia Kurniawati', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2016-01-22', '2016-07-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(17, 20, 'ANG-2026-0014', 'TOP-100017', '3207000000000012', 'Eko Prasetyo', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2025-07-22', '2026-02-22', 'resign', '2026-08-22', 'mengundurkan diri', 1, '{\"aktor\": \"ADM-000001\", \"kembali_pokok\": 0, \"kembali_wajib\": 40000, \"tanggal_proses\": \"2026-08-22\", \"tagihan_pelunasan\": 505000, \"alokasi_dari_pokok\": 50000, \"alokasi_dari_wajib\": 455000, \"dana_sosial_hangus\": 55000, \"total_dikembalikan\": 40000, \"simpanan_pokok_total\": 50000, \"simpanan_wajib_total\": 495000}', NULL, NULL, NULL, '2026-08-21 19:19:54', '2026-08-21 19:22:33'),
(18, 21, 'ANG-2026-0015', 'TOP-100018', '3207000000000013', 'Putri Handayani', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2023-10-22', '2024-06-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(19, 22, 'ANG-2026-0016', 'TOP-100019', '3207000000000014', 'Fajar Ramadhan', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2022-11-22', '2023-08-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(20, 23, 'ANG-2026-0017', 'TOP-100020', '3207000000000015', 'Indah Permata', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2017-10-22', '2018-08-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(21, 24, 'ANG-2026-0018', 'TOP-100021', '3207000000000016', 'Yudha Pradana', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2025-12-22', '2026-03-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(22, 25, 'ANG-2026-0019', 'TOP-100022', '3207000000000017', 'Sri Wahyuni', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2023-10-22', '2024-02-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(23, 26, 'ANG-2026-0020', 'TOP-100023', '3207000000000018', 'Andi Firmansyah', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2022-11-22', '2023-04-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(24, 27, 'ANG-2026-0021', 'TOP-100024', '3207000000000019', 'Ratna Sari', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2019-10-22', '2020-04-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(25, 28, 'ANG-2026-0022', 'TOP-100025', '3207000000000020', 'Deni Setiawan', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2025-09-22', '2026-04-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(26, 29, 'ANG-2026-0023', 'TOP-100026', '3207000000000021', 'Fitriani', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2023-02-22', '2023-10-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(27, 30, 'ANG-2026-0024', 'TOP-100027', '3207000000000022', 'Rudi Hartono', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2022-10-22', '2023-07-22', 'nonaktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(28, 31, 'ANG-2026-0025', 'TOP-100028', '3207000000000023', 'Susi Susanti', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2015-07-22', '2016-05-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(29, 32, 'ANG-2026-0026', 'TOP-100029', '3207000000000024', 'Bayu Saputra', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2025-09-22', '2025-12-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(30, 33, 'ANG-2026-0027', 'TOP-100030', '3207000000000025', 'Ayu Lestari', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2024-01-22', '2024-05-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(31, 34, 'ANG-2026-0028', 'TOP-100031', '3207000000000026', 'Toni Kurniawan', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2022-10-22', '2023-03-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(32, 35, 'ANG-2026-0029', 'TOP-100032', '3207000000000027', 'Tuti Herawati', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2017-12-22', '2018-06-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(33, 36, 'ANG-2026-0030', 'TOP-100033', '3207000000000028', 'Ferry Ardiansyah', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2025-06-22', '2026-01-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(34, 37, 'ANG-2026-0031', 'TOP-100034', '3207000000000029', 'Desi Ratnasari', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2023-05-22', '2024-01-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(35, 38, 'ANG-2026-0032', 'TOP-100035', '3207000000000030', 'Imam Santoso', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2022-09-22', '2023-06-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(36, 39, 'ANG-2026-0033', 'TOP-100036', '3207000000000031', 'Widya Astuti', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2019-09-22', '2020-07-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(37, 40, 'ANG-2026-0034', 'TOP-100037', '3207000000000032', 'Galih Prakoso', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2025-11-22', '2026-02-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(38, 41, 'ANG-2026-0035', 'TOP-100038', '3207000000000033', 'Nur Aini', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2024-04-22', '2024-08-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(39, 42, 'ANG-2026-0036', 'TOP-100039', '3207000000000034', 'Satria Bima', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2022-09-22', '2023-02-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(40, 43, 'ANG-2026-0037', 'TOP-100040', '3207000000000035', 'Laila Amalia', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2016-02-22', '2016-08-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(41, 44, 'ANG-2026-0038', 'TOP-100041', '3207000000000036', 'Wisnu Prasetyo', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2025-08-22', '2026-03-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(42, 45, 'ANG-2026-0039', 'TOP-100042', '3207000000000037', 'Mega Puspita', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2023-08-22', '2024-04-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(43, 46, 'ANG-2026-0040', 'TOP-100043', '3207000000000038', 'Dimas Anggara', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2022-08-22', '2023-05-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(44, 47, 'ANG-2026-0041', 'TOP-100044', '3207000000000039', 'Nabila Putri', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2017-06-22', '2018-04-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(45, 48, 'ANG-2026-0042', 'TOP-100045', '3207000000000040', 'Candra Wijaya', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2026-01-22', '2026-04-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(46, 49, 'ANG-2026-0043', 'TOP-100046', '3207000000000041', 'Yuni Astuti', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2023-08-22', '2023-12-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(47, 50, 'ANG-2026-0044', 'TOP-100047', '3207000000000042', 'Arif Hidayat', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2023-03-22', '2023-08-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(48, 51, 'ANG-2026-0045', 'TOP-100048', '3207000000000043', 'Rina Kusuma', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2019-11-22', '2020-05-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(49, 52, 'ANG-2026-0046', 'TOP-100049', '3207000000000044', 'Bagus Pamungkas', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2025-05-22', '2025-12-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(50, 53, 'ANG-2026-0047', 'TOP-100050', '3207000000000045', 'Citra Ramadhani', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2023-11-22', '2024-07-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(51, 2, 'ANG-2020-0001', 'BEN-000001', NULL, 'Bendahara Koperasi', 'Banjarmasin', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2018-08-22', '2019-08-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(52, 3, 'ANG-2019-0001', 'KET-000001', NULL, 'Ketua Koperasi', 'Banjarmasin', 'Keuangan', 'Keuangan', 'Akuntansi', 'hod', '2016-08-22', '2017-08-22', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:19:55', '2026-08-21 19:19:55');

-- --------------------------------------------------------

--
-- Table structure for table `angsuran`
--

CREATE TABLE `angsuran` (
  `id` bigint UNSIGNED NOT NULL,
  `pinjaman_id` bigint UNSIGNED NOT NULL,
  `cicilan_ke` int UNSIGNED NOT NULL,
  `nominal_pokok` decimal(15,2) NOT NULL,
  `nominal_bunga` decimal(15,2) NOT NULL,
  `total_bayar` decimal(15,2) NOT NULL,
  `status` enum('belum_bayar','lunas','digantikan') COLLATE utf8mb4_unicode_ci DEFAULT 'belum_bayar',
  `pengajuan_percepatan_id` bigint UNSIGNED DEFAULT NULL,
  `tanggal_jatuh_tempo` date NOT NULL,
  `tanggal_konfirmasi_bayar` date DEFAULT NULL,
  `confirmed_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `angsuran`
--

INSERT INTO `angsuran` (`id`, `pinjaman_id`, `cicilan_ke`, `nominal_pokok`, `nominal_bunga`, `total_bayar`, `status`, `pengajuan_percepatan_id`, `tanggal_jatuh_tempo`, `tanggal_konfirmasi_bayar`, `confirmed_by`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 500000.00, 20000.00, 520000.00, 'lunas', NULL, '2026-06-25', '2026-06-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(2, 2, 2, 500000.00, 15000.00, 515000.00, 'lunas', NULL, '2026-07-25', '2026-07-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(3, 2, 3, 500000.00, 10000.00, 510000.00, 'belum_bayar', NULL, '2026-08-25', NULL, NULL, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(4, 2, 4, 500000.00, 5000.00, 505000.00, 'belum_bayar', NULL, '2026-09-25', NULL, NULL, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(5, 3, 1, 500000.00, 30000.00, 530000.00, 'lunas', NULL, '2026-01-25', '2026-01-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(6, 3, 2, 500000.00, 25000.00, 525000.00, 'lunas', NULL, '2026-02-25', '2026-02-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(7, 3, 3, 500000.00, 20000.00, 520000.00, 'lunas', NULL, '2026-03-25', '2026-03-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(8, 3, 4, 500000.00, 15000.00, 515000.00, 'lunas', NULL, '2026-04-25', '2026-04-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(9, 3, 5, 500000.00, 10000.00, 510000.00, 'lunas', NULL, '2026-05-25', '2026-05-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(10, 3, 6, 500000.00, 5000.00, 505000.00, 'lunas', NULL, '2026-06-25', '2026-06-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(11, 4, 1, 416666.67, 50000.00, 466666.67, 'lunas', NULL, '2025-11-25', '2025-11-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(12, 4, 2, 416666.67, 45833.33, 462500.00, 'lunas', NULL, '2025-12-25', '2025-12-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(13, 4, 3, 416666.67, 41666.67, 458333.33, 'lunas', NULL, '2026-01-25', '2026-01-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(14, 4, 4, 416666.67, 37500.00, 454166.67, 'lunas', NULL, '2026-02-25', '2026-02-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(15, 4, 5, 416666.67, 33333.33, 450000.00, 'lunas', NULL, '2026-03-25', '2026-03-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(16, 4, 6, 416666.67, 29166.67, 445833.33, 'lunas', NULL, '2026-04-25', '2026-04-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(17, 4, 7, 416666.67, 25000.00, 441666.67, 'lunas', NULL, '2026-05-25', '2026-05-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(18, 4, 8, 416666.67, 20833.33, 437500.00, 'lunas', NULL, '2026-06-25', '2026-06-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(19, 4, 9, 416666.67, 16666.67, 433333.33, 'lunas', NULL, '2026-07-25', '2026-07-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(20, 4, 10, 416666.67, 12500.00, 429166.67, 'lunas', NULL, '2026-08-25', '2026-08-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(21, 4, 11, 416666.67, 8333.33, 425000.00, 'digantikan', NULL, '2026-09-25', NULL, NULL, '2026-08-21 19:20:10', '2026-08-21 19:27:18'),
(22, 4, 12, 416666.67, 4166.67, 420833.33, 'digantikan', NULL, '2026-10-25', NULL, NULL, '2026-08-21 19:20:10', '2026-08-21 19:27:18'),
(23, 11, 1, 333333.33, 10000.00, 343333.33, 'lunas', NULL, '2026-07-25', '2026-07-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(24, 11, 2, 333333.33, 6666.67, 340000.00, 'belum_bayar', NULL, '2026-08-25', NULL, NULL, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(25, 11, 3, 333333.33, 3333.33, 336666.67, 'belum_bayar', NULL, '2026-09-25', NULL, NULL, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(26, 12, 1, 500000.00, 20000.00, 520000.00, 'lunas', NULL, '2026-06-25', '2026-06-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(27, 12, 2, 500000.00, 15000.00, 515000.00, 'lunas', NULL, '2026-07-25', '2026-07-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(28, 12, 3, 500000.00, 10000.00, 510000.00, 'lunas', NULL, '2026-08-25', '2026-08-22', 1, '2026-08-21 19:20:11', '2026-08-21 19:21:16'),
(29, 12, 4, 500000.00, 5000.00, 505000.00, 'lunas', NULL, '2026-09-25', '2026-08-22', 1, '2026-08-21 19:20:11', '2026-08-21 19:22:33'),
(30, 13, 1, 500000.00, 30000.00, 530000.00, 'lunas', NULL, '2026-04-25', '2026-04-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(31, 13, 2, 500000.00, 25000.00, 525000.00, 'lunas', NULL, '2026-05-25', '2026-05-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(32, 13, 3, 500000.00, 20000.00, 520000.00, 'lunas', NULL, '2026-06-25', '2026-06-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(33, 13, 4, 500000.00, 15000.00, 515000.00, 'belum_bayar', NULL, '2026-07-25', NULL, NULL, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(34, 13, 5, 500000.00, 10000.00, 510000.00, 'belum_bayar', NULL, '2026-08-25', NULL, NULL, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(35, 13, 6, 500000.00, 5000.00, 505000.00, 'belum_bayar', NULL, '2026-09-25', NULL, NULL, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(36, 14, 1, 444444.44, 40000.00, 484444.44, 'lunas', NULL, '2025-11-25', '2025-11-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(37, 14, 2, 444444.44, 35555.56, 480000.00, 'lunas', NULL, '2025-12-25', '2025-12-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(38, 14, 3, 444444.44, 31111.11, 475555.56, 'lunas', NULL, '2026-01-25', '2026-01-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(39, 14, 4, 444444.44, 26666.67, 471111.11, 'lunas', NULL, '2026-02-25', '2026-02-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(40, 14, 5, 444444.44, 22222.22, 466666.67, 'lunas', NULL, '2026-03-25', '2026-03-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(41, 14, 6, 444444.44, 17777.78, 462222.22, 'lunas', NULL, '2026-04-25', '2026-04-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(42, 14, 7, 444444.44, 13333.33, 457777.78, 'lunas', NULL, '2026-05-25', '2026-05-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(43, 14, 8, 444444.44, 8888.89, 453333.33, 'lunas', NULL, '2026-06-25', '2026-06-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(44, 14, 9, 444444.44, 4444.44, 448888.89, 'lunas', NULL, '2026-07-25', '2026-07-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(45, 15, 1, 500000.00, 60000.00, 560000.00, 'lunas', NULL, '2025-07-25', '2025-07-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(46, 15, 2, 500000.00, 55000.00, 555000.00, 'lunas', NULL, '2025-08-25', '2025-08-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(47, 15, 3, 500000.00, 50000.00, 550000.00, 'lunas', NULL, '2025-09-25', '2025-09-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(48, 15, 4, 500000.00, 45000.00, 545000.00, 'lunas', NULL, '2025-10-25', '2025-10-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(49, 15, 5, 500000.00, 40000.00, 540000.00, 'lunas', NULL, '2025-11-25', '2025-11-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(50, 15, 6, 500000.00, 35000.00, 535000.00, 'lunas', NULL, '2025-12-25', '2025-12-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(51, 15, 7, 500000.00, 30000.00, 530000.00, 'lunas', NULL, '2026-01-25', '2026-01-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(52, 15, 8, 500000.00, 25000.00, 525000.00, 'lunas', NULL, '2026-02-25', '2026-02-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(53, 15, 9, 500000.00, 20000.00, 520000.00, 'lunas', NULL, '2026-03-25', '2026-03-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(54, 15, 10, 500000.00, 15000.00, 515000.00, 'lunas', NULL, '2026-04-25', '2026-04-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(55, 15, 11, 500000.00, 10000.00, 510000.00, 'lunas', NULL, '2026-05-25', '2026-05-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(56, 15, 12, 500000.00, 5000.00, 505000.00, 'lunas', NULL, '2026-06-25', '2026-06-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(57, 16, 1, 416666.67, 25000.00, 441666.67, 'lunas', NULL, '2026-01-25', '2026-01-25', 2, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(58, 16, 2, 416666.67, 20833.33, 437500.00, 'lunas', NULL, '2026-02-25', '2026-02-25', 2, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(59, 16, 3, 416666.67, 16666.67, 433333.33, 'lunas', NULL, '2026-03-25', '2026-03-25', 2, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(60, 16, 4, 416666.67, 12500.00, 429166.67, 'lunas', NULL, '2026-04-25', '2026-04-25', 2, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(61, 16, 5, 416666.67, 8333.33, 425000.00, 'lunas', NULL, '2026-05-25', '2026-05-25', 2, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(62, 16, 6, 416666.67, 4166.67, 420833.33, 'lunas', NULL, '2026-06-25', '2026-06-25', 2, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(63, 17, 1, 800000.00, 80000.00, 880000.00, 'belum_bayar', NULL, '2026-08-31', NULL, NULL, '2026-08-21 19:25:46', '2026-08-21 19:25:46'),
(64, 17, 2, 800000.00, 72000.00, 872000.00, 'belum_bayar', NULL, '2026-09-30', NULL, NULL, '2026-08-21 19:25:46', '2026-08-21 19:25:46'),
(65, 17, 3, 800000.00, 64000.00, 864000.00, 'belum_bayar', NULL, '2026-10-31', NULL, NULL, '2026-08-21 19:25:46', '2026-08-21 19:25:46'),
(66, 17, 4, 800000.00, 56000.00, 856000.00, 'belum_bayar', NULL, '2026-11-30', NULL, NULL, '2026-08-21 19:25:46', '2026-08-21 19:25:46'),
(67, 17, 5, 800000.00, 48000.00, 848000.00, 'belum_bayar', NULL, '2026-12-31', NULL, NULL, '2026-08-21 19:25:46', '2026-08-21 19:25:46'),
(68, 17, 6, 800000.00, 40000.00, 840000.00, 'belum_bayar', NULL, '2027-01-31', NULL, NULL, '2026-08-21 19:25:46', '2026-08-21 19:25:46'),
(69, 17, 7, 800000.00, 32000.00, 832000.00, 'belum_bayar', NULL, '2027-02-28', NULL, NULL, '2026-08-21 19:25:46', '2026-08-21 19:25:46'),
(70, 17, 8, 800000.00, 24000.00, 824000.00, 'belum_bayar', NULL, '2027-03-31', NULL, NULL, '2026-08-21 19:25:46', '2026-08-21 19:25:46'),
(71, 17, 9, 800000.00, 16000.00, 816000.00, 'belum_bayar', NULL, '2027-04-30', NULL, NULL, '2026-08-21 19:25:46', '2026-08-21 19:25:46'),
(72, 17, 10, 800000.00, 8000.00, 808000.00, 'belum_bayar', NULL, '2027-05-31', NULL, NULL, '2026-08-21 19:25:46', '2026-08-21 19:25:46');

-- --------------------------------------------------------

--
-- Table structure for table `angsuran_percepatan`
--

CREATE TABLE `angsuran_percepatan` (
  `id` bigint UNSIGNED NOT NULL,
  `pengajuan_percepatan_id` bigint UNSIGNED NOT NULL,
  `cicilan_ke` int UNSIGNED NOT NULL,
  `nominal_pokok` decimal(15,2) NOT NULL,
  `nominal_bunga` decimal(15,2) NOT NULL,
  `total_bayar` decimal(15,2) NOT NULL,
  `status` enum('belum_bayar','lunas') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'belum_bayar',
  `tanggal_jatuh_tempo` date NOT NULL,
  `tanggal_konfirmasi_bayar` date DEFAULT NULL,
  `confirmed_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `angsuran_percepatan`
--

INSERT INTO `angsuran_percepatan` (`id`, `pengajuan_percepatan_id`, `cicilan_ke`, `nominal_pokok`, `nominal_bunga`, `total_bayar`, `status`, `tanggal_jatuh_tempo`, `tanggal_konfirmasi_bayar`, `confirmed_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 833333.34, 8333.33, 841666.67, 'lunas', '2026-08-31', '2026-08-22', 1, '2026-08-21 19:27:18', '2026-08-21 19:28:41');

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `aksi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_lama` json DEFAULT NULL,
  `data_baru` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `aksi`, `keterangan`, `data_lama`, `data_baru`, `created_at`, `updated_at`) VALUES
(1, 1, 'anggota_resign', 'Resign anggota Eko Prasetyo (ANG-2026-0014). Pelunasan: Rp 505.000, pengembalian simpanan: Rp 40.000, dana_sosial hangus: Rp 55.000. Alasan: mengundurkan diri', '{\"status\": \"aktif\", \"dana_sosial\": 55000, \"simpanan_pokok\": 50000, \"simpanan_wajib\": 495000, \"sisa_tagihan_pinjaman\": 505000}', '{\"status\": \"resign\", \"settlement\": {\"aktor\": \"ADM-000001\", \"kembali_pokok\": 0, \"kembali_wajib\": 40000, \"tanggal_proses\": \"2026-08-22\", \"tagihan_pelunasan\": 505000, \"alokasi_dari_pokok\": 50000, \"alokasi_dari_wajib\": 455000, \"dana_sosial_hangus\": 55000, \"total_dikembalikan\": 40000, \"simpanan_pokok_total\": 50000, \"simpanan_wajib_total\": 495000}, \"resigned_by\": \"ADM-000001\", \"alasan_resign\": \"mengundurkan diri\", \"tanggal_resign\": \"2026-08-22\"}', '2026-08-21 19:22:33', '2026-08-21 19:22:33'),
(2, 1, 'setujui_pengajuan_limit', 'Limit khusus Dewi Lestari disetujui menjadi Rp 15.000.000', '{\"limit_custom\": \"15000000.00\"}', '{\"limit_custom\": \"15000000.00\"}', '2026-08-21 19:29:26', '2026-08-21 19:29:26');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-spatie.permission.cache', 'a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:15:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:13:\"anggota.lihat\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:14:\"anggota.kelola\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:14:\"anggota.resign\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:14:\"simpanan.lihat\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:19:\"simpanan.konfirmasi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:14:\"pinjaman.lihat\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:25:\"pinjaman.tinjau-bendahara\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:22:\"pinjaman.approve-ketua\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:19:\"angsuran.konfirmasi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:9:\"kas.lihat\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:9:\"kas.topup\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:13:\"laporan.lihat\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:17:\"pengaturan.kelola\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:11:\"user.kelola\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:12:\"portal.akses\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}}s:5:\"roles\";a:4:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:5:\"admin\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:9:\"bendahara\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:14:\"ketua_koperasi\";s:1:\"c\";s:3:\"web\";}i:3;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:7:\"anggota\";s:1:\"c\";s:3:\"web\";}}}', 1787455241);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jurnal_kas`
--

CREATE TABLE `jurnal_kas` (
  `id` bigint UNSIGNED NOT NULL,
  `tipe` enum('masuk','keluar') COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori` enum('topup_bulanan','pencairan_pinjaman','pembayaran_angsuran','dana_sosial_bulanan','pengeluaran_koperasi','pengeluaran_dana_sosial','saldo_awal','pelunasan_resign_pinjaman','pelunasan_resign_simpanan','simpanan_resign_masuk','return_simpanan_pokok','return_simpanan_wajib','simpanan_pokok_masuk','simpanan_wajib_masuk','transfer_ke_dana_pinjaman','terima_dari_pengembalian_simpanan') COLLATE utf8mb4_unicode_ci NOT NULL,
  `kantong` enum('pinjaman','dana_sosial','pengembalian_simpanan','simpanan') COLLATE utf8mb4_unicode_ci NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `saldo_setelah` decimal(15,2) NOT NULL DEFAULT '0.00',
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `sub_judul` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referensi_id` bigint UNSIGNED DEFAULT NULL,
  `tanggal` date NOT NULL,
  `created_by` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jurnal_kas`
--

INSERT INTO `jurnal_kas` (`id`, `tipe`, `kategori`, `kantong`, `jumlah`, `saldo_setelah`, `keterangan`, `sub_judul`, `referensi_id`, `tanggal`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'masuk', 'saldo_awal', 'pinjaman', 100000000.00, 200000000.00, 'Saldo awal koperasi', NULL, NULL, '2026-08-22', 1, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(2, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 45000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 1, '2026-03-25', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(3, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3005000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 1, '2026-03-25', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(4, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 90000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 1, '2026-04-25', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(5, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3010000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 1, '2026-04-25', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(6, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 135000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 1, '2026-05-25', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(7, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3015000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 1, '2026-05-25', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(8, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 180000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 1, '2026-06-25', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(9, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3020000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 1, '2026-06-25', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(10, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 225000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 1, '2026-07-25', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(11, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3025000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 1, '2026-07-25', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(12, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 270000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 1, '2026-08-22', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(13, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3030000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 1, '2026-08-22', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(14, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 315000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 2, '2026-03-25', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(15, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3035000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 2, '2026-03-25', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(16, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 360000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 2, '2026-04-25', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(17, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3040000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 2, '2026-04-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(18, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 405000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 2, '2026-05-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(19, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3045000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 2, '2026-05-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(20, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 450000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 2, '2026-06-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(21, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3050000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 2, '2026-06-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(22, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 495000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 2, '2026-07-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(23, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3055000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 2, '2026-07-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(24, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 540000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 2, '2026-08-22', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(25, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3060000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 2, '2026-08-22', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(26, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 585000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 3, '2026-03-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(27, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3065000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 3, '2026-03-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(28, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 630000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 3, '2026-04-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(29, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3070000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 3, '2026-04-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(30, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 675000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 3, '2026-05-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(31, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3075000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 3, '2026-05-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(32, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 720000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 3, '2026-06-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(33, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3080000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 3, '2026-06-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(34, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 765000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 3, '2026-07-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(35, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3085000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 3, '2026-07-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(36, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 810000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 3, '2026-08-22', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(37, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3090000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 3, '2026-08-22', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(38, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 855000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 4, '2026-03-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(39, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3095000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 4, '2026-03-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(40, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 900000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 4, '2026-04-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(41, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3100000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 4, '2026-04-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(42, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 945000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 4, '2026-05-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(43, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3105000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 4, '2026-05-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(44, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 990000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 4, '2026-06-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(45, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3110000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 4, '2026-06-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(46, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 1035000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 4, '2026-07-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(47, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3115000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 4, '2026-07-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(48, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 1080000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 4, '2026-08-22', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(49, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3120000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 4, '2026-08-22', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(50, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 1125000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 5, '2026-04-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(51, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3125000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 5, '2026-04-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(52, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 1170000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 5, '2026-05-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(53, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3130000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 5, '2026-05-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(54, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 1215000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 5, '2026-06-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(55, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3135000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 5, '2026-06-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(56, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 1260000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 5, '2026-07-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(57, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3140000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 5, '2026-07-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(58, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 1305000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 6, '2026-03-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(59, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3145000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 6, '2026-03-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(60, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 1350000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 6, '2026-04-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(61, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3150000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 6, '2026-04-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(62, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 1395000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 6, '2026-05-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(63, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3155000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 6, '2026-05-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(64, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 1440000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 6, '2026-06-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(65, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3160000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 6, '2026-06-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(66, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 1485000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 6, '2026-07-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(67, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3165000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 6, '2026-07-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(68, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 1530000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 6, '2026-08-22', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(69, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3170000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 6, '2026-08-22', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(70, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 1575000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 7, '2026-03-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(71, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3175000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 7, '2026-03-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(72, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 1620000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 7, '2026-04-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(73, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3180000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 7, '2026-04-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(74, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 1665000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 7, '2026-05-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(75, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3185000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 7, '2026-05-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(76, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 1710000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 7, '2026-06-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(77, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3190000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 7, '2026-06-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(78, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 1755000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 7, '2026-07-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(79, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3195000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 7, '2026-07-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(80, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 1800000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 7, '2026-08-22', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(81, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3200000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 7, '2026-08-22', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(82, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 1845000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 8, '2026-03-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(83, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3205000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 8, '2026-03-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(84, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 1890000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 8, '2026-04-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(85, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3210000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 8, '2026-04-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(86, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 1935000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 8, '2026-05-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(87, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3215000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 8, '2026-05-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(88, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 1980000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 8, '2026-06-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(89, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3220000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 8, '2026-06-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(90, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 2025000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 8, '2026-07-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(91, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3225000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 8, '2026-07-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(92, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 2070000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 8, '2026-08-22', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(93, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3230000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 8, '2026-08-22', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(94, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 2115000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 9, '2026-03-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(95, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3235000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 9, '2026-03-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(96, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 2160000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 9, '2026-04-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(97, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3240000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 9, '2026-04-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(98, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 2205000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 9, '2026-05-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(99, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3245000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 9, '2026-05-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(100, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 2250000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 9, '2026-06-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(101, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3250000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 9, '2026-06-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(102, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 2295000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 9, '2026-07-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(103, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3255000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 9, '2026-07-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(104, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 2340000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 9, '2026-08-22', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(105, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3260000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 9, '2026-08-22', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(106, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 2385000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 10, '2026-03-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(107, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3265000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 10, '2026-03-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(108, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 2430000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 10, '2026-04-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(109, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3270000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 10, '2026-04-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(110, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 2475000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 10, '2026-05-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(111, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3275000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 10, '2026-05-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(112, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 2520000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 10, '2026-06-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(113, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3280000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 10, '2026-06-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(114, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 2565000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 10, '2026-07-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(115, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3285000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 10, '2026-07-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(116, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 2610000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 11, '2026-03-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(117, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3290000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 11, '2026-03-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(118, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 2655000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 11, '2026-04-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(119, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3295000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 11, '2026-04-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(120, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 2700000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 11, '2026-05-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(121, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3300000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 11, '2026-05-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(122, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 2745000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 11, '2026-06-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(123, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3305000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 11, '2026-06-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(124, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 2790000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 11, '2026-07-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(125, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3310000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 11, '2026-07-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(126, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 2835000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 11, '2026-08-22', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(127, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3315000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 11, '2026-08-22', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(128, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 2880000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 12, '2026-03-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(129, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3320000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 12, '2026-03-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(130, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 2925000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 12, '2026-04-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(131, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3325000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 12, '2026-04-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(132, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 2970000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 12, '2026-05-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(133, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3330000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 12, '2026-05-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(134, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 3015000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 12, '2026-06-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(135, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3335000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 12, '2026-06-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(136, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 3060000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 12, '2026-07-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(137, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3340000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 12, '2026-07-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(138, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 3105000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 12, '2026-08-22', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(139, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3345000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 12, '2026-08-22', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(140, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 3150000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 14, '2026-03-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(141, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3350000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 14, '2026-03-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(142, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 3195000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 14, '2026-04-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(143, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3355000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 14, '2026-04-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(144, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 3240000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 14, '2026-05-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(145, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3360000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 14, '2026-05-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(146, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 3285000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 14, '2026-06-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(147, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3365000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 14, '2026-06-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(148, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 3330000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 14, '2026-07-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(149, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3370000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 14, '2026-07-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(150, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 3375000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 14, '2026-08-22', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(151, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3375000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 14, '2026-08-22', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(152, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 3420000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 15, '2026-03-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(153, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3380000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 15, '2026-03-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(154, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 3465000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 15, '2026-04-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(155, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3385000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 15, '2026-04-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(156, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 3510000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 15, '2026-05-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(157, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3390000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 15, '2026-05-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(158, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 3555000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 15, '2026-06-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(159, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3395000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 15, '2026-06-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(160, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 3600000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 15, '2026-07-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(161, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3400000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 15, '2026-07-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(162, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 3645000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 16, '2026-03-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(163, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3405000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 16, '2026-03-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(164, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 3690000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 16, '2026-04-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(165, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3410000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 16, '2026-04-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(166, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 3735000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 16, '2026-05-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(167, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3415000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 16, '2026-05-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(168, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 3780000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 16, '2026-06-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(169, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3420000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 16, '2026-06-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(170, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 3825000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 16, '2026-07-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(171, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3425000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 16, '2026-07-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(172, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 3870000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 16, '2026-08-22', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(173, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3430000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 16, '2026-08-22', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(174, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 3915000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 17, '2026-03-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(175, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3435000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 17, '2026-03-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(176, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 3960000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 17, '2026-04-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(177, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3440000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 17, '2026-04-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(178, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 4005000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 17, '2026-05-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(179, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3445000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 17, '2026-05-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(180, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 4050000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 17, '2026-06-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(181, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3450000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 17, '2026-06-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(182, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 4095000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 17, '2026-07-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(183, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3455000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 17, '2026-07-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(184, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 4140000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 17, '2026-08-22', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(185, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3460000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 17, '2026-08-22', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(186, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 4185000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 18, '2026-03-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(187, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3465000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 18, '2026-03-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(188, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 4230000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 18, '2026-04-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(189, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3470000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 18, '2026-04-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(190, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 4275000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 18, '2026-05-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(191, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3475000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 18, '2026-05-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(192, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 4320000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 18, '2026-06-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(193, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3480000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 18, '2026-06-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(194, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 4365000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 18, '2026-07-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(195, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3485000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 18, '2026-07-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(196, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 4410000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 18, '2026-08-22', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(197, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3490000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 18, '2026-08-22', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(198, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 4455000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 19, '2026-03-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(199, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3495000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 19, '2026-03-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(200, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 4500000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 19, '2026-04-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(201, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3500000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 19, '2026-04-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(202, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 4545000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 19, '2026-05-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(203, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3505000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 19, '2026-05-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(204, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 4590000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 19, '2026-06-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(205, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3510000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 19, '2026-06-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(206, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 4635000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 19, '2026-07-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(207, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3515000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 19, '2026-07-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(208, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 4680000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 19, '2026-08-22', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(209, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3520000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 19, '2026-08-22', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(210, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 4725000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 20, '2026-03-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(211, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3525000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 20, '2026-03-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(212, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 4770000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 20, '2026-04-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(213, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3530000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 20, '2026-04-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(214, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 4815000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 20, '2026-05-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(215, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3535000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 20, '2026-05-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(216, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 4860000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 20, '2026-06-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(217, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3540000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 20, '2026-06-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(218, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 4905000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 20, '2026-07-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(219, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3545000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 20, '2026-07-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(220, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 4950000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 21, '2026-03-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(221, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3550000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 21, '2026-03-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(222, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 4995000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 21, '2026-04-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(223, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3555000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 21, '2026-04-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(224, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 5040000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 21, '2026-05-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(225, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3560000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 21, '2026-05-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(226, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 5085000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 21, '2026-06-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(227, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3565000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 21, '2026-06-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(228, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 5130000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 21, '2026-07-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(229, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3570000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 21, '2026-07-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(230, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 5175000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 21, '2026-08-22', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(231, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3575000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 21, '2026-08-22', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(232, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 5220000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 22, '2026-03-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(233, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3580000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 22, '2026-03-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(234, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 5265000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 22, '2026-04-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(235, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3585000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 22, '2026-04-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(236, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 5310000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 22, '2026-05-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(237, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3590000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 22, '2026-05-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(238, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 5355000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 22, '2026-06-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(239, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3595000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 22, '2026-06-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(240, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 5400000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 22, '2026-07-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(241, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3600000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 22, '2026-07-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(242, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 5445000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 22, '2026-08-22', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(243, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3605000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 22, '2026-08-22', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(244, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 5490000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 23, '2026-03-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(245, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3610000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 23, '2026-03-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(246, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 5535000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 23, '2026-04-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(247, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3615000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 23, '2026-04-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(248, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 5580000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 23, '2026-05-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(249, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3620000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 23, '2026-05-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(250, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 5625000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 23, '2026-06-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(251, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3625000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 23, '2026-06-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(252, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 5670000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 23, '2026-07-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(253, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3630000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 23, '2026-07-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(254, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 5715000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 23, '2026-08-22', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(255, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3635000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 23, '2026-08-22', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(256, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 5760000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 24, '2026-03-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(257, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3640000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 24, '2026-03-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(258, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 5805000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 24, '2026-04-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(259, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3645000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 24, '2026-04-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(260, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 5850000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 24, '2026-05-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02');
INSERT INTO `jurnal_kas` (`id`, `tipe`, `kategori`, `kantong`, `jumlah`, `saldo_setelah`, `keterangan`, `sub_judul`, `referensi_id`, `tanggal`, `created_by`, `created_at`, `updated_at`) VALUES
(261, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3650000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 24, '2026-05-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(262, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 5895000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 24, '2026-06-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(263, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3655000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 24, '2026-06-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(264, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 5940000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 24, '2026-07-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(265, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3660000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 24, '2026-07-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(266, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 5985000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 24, '2026-08-22', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(267, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3665000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 24, '2026-08-22', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(268, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 6030000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 25, '2026-04-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(269, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3670000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 25, '2026-04-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(270, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 6075000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 25, '2026-05-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(271, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3675000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 25, '2026-05-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(272, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 6120000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 25, '2026-06-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(273, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3680000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 25, '2026-06-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(274, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 6165000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 25, '2026-07-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(275, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3685000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 25, '2026-07-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(276, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 6210000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 26, '2026-03-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(277, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3690000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 26, '2026-03-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(278, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 6255000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 26, '2026-04-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(279, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3695000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 26, '2026-04-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(280, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 6300000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 26, '2026-05-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(281, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3700000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 26, '2026-05-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(282, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 6345000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 26, '2026-06-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(283, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3705000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 26, '2026-06-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(284, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 6390000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 26, '2026-07-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(285, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3710000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 26, '2026-07-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(286, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 6435000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 26, '2026-08-22', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(287, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3715000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 26, '2026-08-22', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(288, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 6480000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 28, '2026-03-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(289, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3720000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 28, '2026-03-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(290, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 6525000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 28, '2026-04-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(291, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3725000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 28, '2026-04-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(292, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 6570000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 28, '2026-05-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(293, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3730000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 28, '2026-05-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(294, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 6615000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 28, '2026-06-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(295, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3735000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 28, '2026-06-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(296, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 6660000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 28, '2026-07-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(297, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3740000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 28, '2026-07-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(298, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 6705000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 28, '2026-08-22', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(299, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3745000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 28, '2026-08-22', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(300, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 6750000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 29, '2026-03-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(301, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3750000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 29, '2026-03-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(302, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 6795000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 29, '2026-04-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(303, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3755000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 29, '2026-04-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(304, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 6840000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 29, '2026-05-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(305, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3760000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 29, '2026-05-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(306, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 6885000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 29, '2026-06-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(307, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3765000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 29, '2026-06-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(308, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 6930000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 29, '2026-07-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(309, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3770000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 29, '2026-07-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(310, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 6975000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 29, '2026-08-22', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(311, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3775000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 29, '2026-08-22', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(312, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 7020000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 30, '2026-03-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(313, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3780000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 30, '2026-03-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(314, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 7065000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 30, '2026-04-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(315, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3785000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 30, '2026-04-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(316, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 7110000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 30, '2026-05-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(317, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3790000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 30, '2026-05-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(318, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 7155000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 30, '2026-06-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(319, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3795000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 30, '2026-06-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(320, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 7200000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 30, '2026-07-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(321, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3800000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 30, '2026-07-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(322, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 7245000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 30, '2026-08-22', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(323, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3805000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 30, '2026-08-22', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(324, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 7290000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 31, '2026-03-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(325, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3810000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 31, '2026-03-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(326, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 7335000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 31, '2026-04-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(327, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3815000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 31, '2026-04-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(328, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 7380000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 31, '2026-05-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(329, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3820000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 31, '2026-05-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(330, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 7425000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 31, '2026-06-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(331, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3825000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 31, '2026-06-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(332, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 7470000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 31, '2026-07-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(333, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3830000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 31, '2026-07-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(334, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 7515000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 31, '2026-08-22', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(335, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3835000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 31, '2026-08-22', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(336, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 7560000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 32, '2026-03-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(337, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3840000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 32, '2026-03-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(338, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 7605000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 32, '2026-04-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(339, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3845000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 32, '2026-04-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(340, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 7650000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 32, '2026-05-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(341, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3850000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 32, '2026-05-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(342, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 7695000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 32, '2026-06-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(343, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3855000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 32, '2026-06-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(344, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 7740000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 32, '2026-07-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(345, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3860000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 32, '2026-07-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(346, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 7785000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 32, '2026-08-22', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(347, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3865000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 32, '2026-08-22', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(348, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 7830000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 33, '2026-03-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(349, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3870000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 33, '2026-03-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(350, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 7875000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 33, '2026-04-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(351, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3875000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 33, '2026-04-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(352, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 7920000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 33, '2026-05-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(353, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3880000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 33, '2026-05-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(354, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 7965000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 33, '2026-06-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(355, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3885000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 33, '2026-06-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(356, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 8010000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 33, '2026-07-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(357, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3890000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 33, '2026-07-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(358, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 8055000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 33, '2026-08-22', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(359, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3895000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 33, '2026-08-22', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(360, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 8100000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 34, '2026-03-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(361, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3900000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 34, '2026-03-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(362, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 8145000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 34, '2026-04-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(363, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3905000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 34, '2026-04-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(364, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 8190000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 34, '2026-05-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(365, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3910000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 34, '2026-05-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(366, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 8235000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 34, '2026-06-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(367, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3915000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 34, '2026-06-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(368, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 8280000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 34, '2026-07-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(369, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3920000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 34, '2026-07-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(370, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 8325000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 34, '2026-08-22', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(371, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3925000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 34, '2026-08-22', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(372, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 8370000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 35, '2026-03-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(373, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3930000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 35, '2026-03-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(374, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 8415000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 35, '2026-04-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(375, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3935000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 35, '2026-04-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(376, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 8460000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 35, '2026-05-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(377, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3940000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 35, '2026-05-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(378, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 8505000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 35, '2026-06-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(379, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3945000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 35, '2026-06-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(380, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 8550000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 35, '2026-07-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(381, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3950000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 35, '2026-07-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(382, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 8595000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 35, '2026-08-22', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(383, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3955000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 35, '2026-08-22', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(384, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 8640000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 36, '2026-03-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(385, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3960000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 36, '2026-03-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(386, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 8685000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 36, '2026-04-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(387, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3965000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 36, '2026-04-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(388, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 8730000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 36, '2026-05-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(389, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3970000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 36, '2026-05-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(390, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 8775000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 36, '2026-06-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(391, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3975000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 36, '2026-06-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(392, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 8820000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 36, '2026-07-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(393, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3980000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 36, '2026-07-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(394, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 8865000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 36, '2026-08-22', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(395, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3985000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 36, '2026-08-22', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(396, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 8910000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 37, '2026-03-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(397, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3990000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 37, '2026-03-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(398, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 8955000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 37, '2026-04-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(399, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 3995000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 37, '2026-04-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(400, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 9000000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 37, '2026-05-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(401, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4000000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 37, '2026-05-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(402, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 9045000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 37, '2026-06-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(403, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4005000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 37, '2026-06-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(404, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 9090000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 37, '2026-07-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(405, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4010000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 37, '2026-07-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(406, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 9135000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 37, '2026-08-22', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(407, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4015000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 37, '2026-08-22', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(408, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 9180000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 38, '2026-03-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(409, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4020000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 38, '2026-03-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(410, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 9225000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 38, '2026-04-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(411, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4025000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 38, '2026-04-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(412, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 9270000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 38, '2026-05-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(413, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4030000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 38, '2026-05-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(414, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 9315000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 38, '2026-06-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(415, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4035000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 38, '2026-06-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(416, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 9360000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 38, '2026-07-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(417, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4040000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 38, '2026-07-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(418, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 9405000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 38, '2026-08-22', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(419, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4045000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 38, '2026-08-22', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(420, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 9450000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 39, '2026-03-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(421, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4050000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 39, '2026-03-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(422, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 9495000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 39, '2026-04-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(423, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4055000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 39, '2026-04-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(424, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 9540000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 39, '2026-05-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(425, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4060000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 39, '2026-05-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(426, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 9585000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 39, '2026-06-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(427, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4065000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 39, '2026-06-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(428, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 9630000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 39, '2026-07-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(429, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4070000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 39, '2026-07-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(430, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 9675000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 39, '2026-08-22', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(431, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4075000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 39, '2026-08-22', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(432, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 9720000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 40, '2026-03-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(433, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4080000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 40, '2026-03-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(434, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 9765000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 40, '2026-04-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(435, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4085000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 40, '2026-04-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(436, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 9810000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 40, '2026-05-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(437, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4090000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 40, '2026-05-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(438, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 9855000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 40, '2026-06-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(439, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4095000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 40, '2026-06-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(440, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 9900000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 40, '2026-07-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(441, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4100000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 40, '2026-07-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(442, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 9945000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 40, '2026-08-22', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(443, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4105000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 40, '2026-08-22', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(444, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 9990000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 41, '2026-03-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(445, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4110000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 41, '2026-03-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(446, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 10035000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 41, '2026-04-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(447, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4115000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 41, '2026-04-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(448, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 10080000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 41, '2026-05-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(449, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4120000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 41, '2026-05-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(450, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 10125000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 41, '2026-06-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(451, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4125000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 41, '2026-06-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(452, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 10170000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 41, '2026-07-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(453, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4130000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 41, '2026-07-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(454, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 10215000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 41, '2026-08-22', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(455, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4135000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 41, '2026-08-22', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(456, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 10260000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 42, '2026-03-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(457, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4140000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 42, '2026-03-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(458, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 10305000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 42, '2026-04-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(459, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4145000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 42, '2026-04-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(460, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 10350000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 42, '2026-05-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(461, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4150000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 42, '2026-05-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(462, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 10395000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 42, '2026-06-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(463, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4155000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 42, '2026-06-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(464, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 10440000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 42, '2026-07-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(465, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4160000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 42, '2026-07-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(466, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 10485000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 42, '2026-08-22', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(467, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4165000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 42, '2026-08-22', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(468, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 10530000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 43, '2026-03-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(469, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4170000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 43, '2026-03-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(470, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 10575000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 43, '2026-04-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(471, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4175000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 43, '2026-04-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(472, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 10620000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 43, '2026-05-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(473, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4180000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 43, '2026-05-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(474, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 10665000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 43, '2026-06-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(475, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4185000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 43, '2026-06-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(476, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 10710000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 43, '2026-07-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(477, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4190000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 43, '2026-07-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(478, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 10755000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 43, '2026-08-22', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(479, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4195000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 43, '2026-08-22', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(480, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 10800000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 44, '2026-03-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(481, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4200000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 44, '2026-03-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(482, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 10845000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 44, '2026-04-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(483, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4205000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 44, '2026-04-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(484, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 10890000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 44, '2026-05-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(485, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4210000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 44, '2026-05-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(486, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 10935000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 44, '2026-06-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(487, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4215000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 44, '2026-06-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(488, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 10980000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 44, '2026-07-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(489, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4220000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 44, '2026-07-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(490, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 11025000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 44, '2026-08-22', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(491, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4225000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 44, '2026-08-22', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(492, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 11070000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 45, '2026-04-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(493, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4230000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 45, '2026-04-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(494, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 11115000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 45, '2026-05-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(495, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4235000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 45, '2026-05-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(496, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 11160000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 45, '2026-06-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(497, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4240000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 45, '2026-06-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(498, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 11205000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 45, '2026-07-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(499, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4245000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 45, '2026-07-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(500, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 11250000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 45, '2026-08-22', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(501, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4250000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 45, '2026-08-22', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(502, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 11295000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 46, '2026-03-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(503, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4255000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 46, '2026-03-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(504, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 11340000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 46, '2026-04-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(505, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4260000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 46, '2026-04-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(506, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 11385000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 46, '2026-05-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(507, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4265000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 46, '2026-05-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(508, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 11430000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 46, '2026-06-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(509, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4270000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 46, '2026-06-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(510, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 11475000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 46, '2026-07-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(511, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4275000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 46, '2026-07-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(512, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 11520000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 46, '2026-08-22', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(513, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4280000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 46, '2026-08-22', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(514, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 11565000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 47, '2026-03-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(515, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4285000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 47, '2026-03-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(516, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 11610000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 47, '2026-04-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(517, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4290000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 47, '2026-04-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(518, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 11655000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 47, '2026-05-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08');
INSERT INTO `jurnal_kas` (`id`, `tipe`, `kategori`, `kantong`, `jumlah`, `saldo_setelah`, `keterangan`, `sub_judul`, `referensi_id`, `tanggal`, `created_by`, `created_at`, `updated_at`) VALUES
(519, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4295000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 47, '2026-05-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(520, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 11700000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 47, '2026-06-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(521, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4300000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 47, '2026-06-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(522, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 11745000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 47, '2026-07-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(523, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4305000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 47, '2026-07-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(524, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 11790000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 47, '2026-08-22', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(525, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4310000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 47, '2026-08-22', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(526, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 11835000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 48, '2026-03-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(527, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4315000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 48, '2026-03-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(528, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 11880000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 48, '2026-04-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(529, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4320000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 48, '2026-04-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(530, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 11925000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 48, '2026-05-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(531, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4325000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 48, '2026-05-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(532, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 11970000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 48, '2026-06-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(533, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4330000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 48, '2026-06-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(534, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 12015000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 48, '2026-07-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(535, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4335000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 48, '2026-07-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(536, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 12060000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 48, '2026-08-22', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(537, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4340000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 48, '2026-08-22', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(538, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 12105000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 49, '2026-03-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(539, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4345000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 49, '2026-03-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(540, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 12150000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 49, '2026-04-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(541, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4350000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 49, '2026-04-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(542, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 12195000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 49, '2026-05-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(543, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4355000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 49, '2026-05-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(544, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 12240000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 49, '2026-06-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(545, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4360000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 49, '2026-06-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(546, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 12285000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 49, '2026-07-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(547, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4365000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 49, '2026-07-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(548, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 12330000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 49, '2026-08-22', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(549, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4370000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 49, '2026-08-22', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(550, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 12375000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 50, '2026-03-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(551, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4375000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 50, '2026-03-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(552, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 12420000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 50, '2026-04-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(553, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4380000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 50, '2026-04-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(554, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 12465000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 50, '2026-05-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(555, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4385000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 50, '2026-05-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(556, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 12510000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 50, '2026-06-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(557, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4390000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 50, '2026-06-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(558, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 12555000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 50, '2026-07-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(559, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4395000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 50, '2026-07-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(560, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 12600000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 50, '2026-08-22', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(561, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4400000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 50, '2026-08-22', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(562, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 12645000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 51, '2026-03-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(563, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4405000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 51, '2026-03-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(564, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 12690000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 51, '2026-04-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(565, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4410000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 51, '2026-04-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(566, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 12735000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 51, '2026-05-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(567, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4415000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 51, '2026-05-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(568, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 12780000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 51, '2026-06-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(569, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4420000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 51, '2026-06-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(570, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 12825000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 51, '2026-07-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(571, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4425000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 51, '2026-07-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(572, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 12870000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 51, '2026-08-22', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(573, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4430000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 51, '2026-08-22', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(574, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 12915000.00, 'Simpanan wajib bulan 2026-03', 'Simpanan wajib masuk', 52, '2026-03-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(575, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4435000.00, 'Dana sosial bulan 2026-03', 'Dana sosial masuk', 52, '2026-03-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(576, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 12960000.00, 'Simpanan wajib bulan 2026-04', 'Simpanan wajib masuk', 52, '2026-04-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(577, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4440000.00, 'Dana sosial bulan 2026-04', 'Dana sosial masuk', 52, '2026-04-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(578, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 13005000.00, 'Simpanan wajib bulan 2026-05', 'Simpanan wajib masuk', 52, '2026-05-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(579, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4445000.00, 'Dana sosial bulan 2026-05', 'Dana sosial masuk', 52, '2026-05-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(580, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 13050000.00, 'Simpanan wajib bulan 2026-06', 'Simpanan wajib masuk', 52, '2026-06-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(581, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4450000.00, 'Dana sosial bulan 2026-06', 'Dana sosial masuk', 52, '2026-06-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(582, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 13095000.00, 'Simpanan wajib bulan 2026-07', 'Simpanan wajib masuk', 52, '2026-07-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(583, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4455000.00, 'Dana sosial bulan 2026-07', 'Dana sosial masuk', 52, '2026-07-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(584, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 13140000.00, 'Simpanan wajib bulan 2026-08', 'Simpanan wajib masuk', 52, '2026-08-22', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(585, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4460000.00, 'Dana sosial bulan 2026-08', 'Dana sosial masuk', 52, '2026-08-22', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(586, 'keluar', 'pencairan_pinjaman', 'pinjaman', 2000000.00, 0.00, 'Pencairan pinjaman - Siti Aminah', NULL, 2, '2026-05-25', 3, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(587, 'masuk', 'pembayaran_angsuran', 'pinjaman', 520000.00, 0.00, 'Angsuran ke-1 - Siti Aminah', NULL, 1, '2026-06-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(588, 'masuk', 'pembayaran_angsuran', 'pinjaman', 515000.00, 0.00, 'Angsuran ke-2 - Siti Aminah', NULL, 2, '2026-07-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(589, 'keluar', 'pencairan_pinjaman', 'pinjaman', 3000000.00, 0.00, 'Pencairan pinjaman - Ahmad Ridwan', NULL, 3, '2025-12-25', 3, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(590, 'masuk', 'pembayaran_angsuran', 'pinjaman', 530000.00, 0.00, 'Angsuran ke-1 - Ahmad Ridwan', NULL, 5, '2026-01-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(591, 'masuk', 'pembayaran_angsuran', 'pinjaman', 525000.00, 0.00, 'Angsuran ke-2 - Ahmad Ridwan', NULL, 6, '2026-02-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(592, 'masuk', 'pembayaran_angsuran', 'pinjaman', 520000.00, 0.00, 'Angsuran ke-3 - Ahmad Ridwan', NULL, 7, '2026-03-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(593, 'masuk', 'pembayaran_angsuran', 'pinjaman', 515000.00, 0.00, 'Angsuran ke-4 - Ahmad Ridwan', NULL, 8, '2026-04-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(594, 'masuk', 'pembayaran_angsuran', 'pinjaman', 510000.00, 0.00, 'Angsuran ke-5 - Ahmad Ridwan', NULL, 9, '2026-05-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(595, 'masuk', 'pembayaran_angsuran', 'pinjaman', 505000.00, 0.00, 'Angsuran ke-6 - Ahmad Ridwan', NULL, 10, '2026-06-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(596, 'keluar', 'pencairan_pinjaman', 'pinjaman', 5000000.00, 0.00, 'Pencairan pinjaman - Dewi Lestari', NULL, 4, '2025-10-25', 3, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(597, 'masuk', 'pembayaran_angsuran', 'pinjaman', 466666.67, 0.00, 'Angsuran ke-1 - Dewi Lestari', NULL, 11, '2025-11-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(598, 'masuk', 'pembayaran_angsuran', 'pinjaman', 462500.00, 0.00, 'Angsuran ke-2 - Dewi Lestari', NULL, 12, '2025-12-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(599, 'masuk', 'pembayaran_angsuran', 'pinjaman', 458333.33, 0.00, 'Angsuran ke-3 - Dewi Lestari', NULL, 13, '2026-01-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(600, 'masuk', 'pembayaran_angsuran', 'pinjaman', 454166.67, 0.00, 'Angsuran ke-4 - Dewi Lestari', NULL, 14, '2026-02-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(601, 'masuk', 'pembayaran_angsuran', 'pinjaman', 450000.00, 0.00, 'Angsuran ke-5 - Dewi Lestari', NULL, 15, '2026-03-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(602, 'masuk', 'pembayaran_angsuran', 'pinjaman', 445833.33, 0.00, 'Angsuran ke-6 - Dewi Lestari', NULL, 16, '2026-04-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(603, 'masuk', 'pembayaran_angsuran', 'pinjaman', 441666.67, 0.00, 'Angsuran ke-7 - Dewi Lestari', NULL, 17, '2026-05-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(604, 'masuk', 'pembayaran_angsuran', 'pinjaman', 437500.00, 0.00, 'Angsuran ke-8 - Dewi Lestari', NULL, 18, '2026-06-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(605, 'masuk', 'pembayaran_angsuran', 'pinjaman', 433333.33, 0.00, 'Angsuran ke-9 - Dewi Lestari', NULL, 19, '2026-07-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(606, 'masuk', 'pembayaran_angsuran', 'pinjaman', 429166.67, 0.00, 'Angsuran ke-10 - Dewi Lestari', NULL, 20, '2026-08-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(607, 'keluar', 'pencairan_pinjaman', 'pinjaman', 1000000.00, 0.00, 'Pencairan pinjaman - Bambang Sutrisno', NULL, 11, '2026-06-25', 3, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(608, 'masuk', 'pembayaran_angsuran', 'pinjaman', 343333.33, 0.00, 'Angsuran ke-1 - Bambang Sutrisno', NULL, 23, '2026-07-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(609, 'keluar', 'pencairan_pinjaman', 'pinjaman', 2000000.00, 0.00, 'Pencairan pinjaman - Eko Prasetyo', NULL, 12, '2026-05-25', 3, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(610, 'masuk', 'pembayaran_angsuran', 'pinjaman', 520000.00, 0.00, 'Angsuran ke-1 - Eko Prasetyo', NULL, 26, '2026-06-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(611, 'masuk', 'pembayaran_angsuran', 'pinjaman', 515000.00, 0.00, 'Angsuran ke-2 - Eko Prasetyo', NULL, 27, '2026-07-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(612, 'keluar', 'pencairan_pinjaman', 'pinjaman', 3000000.00, 0.00, 'Pencairan pinjaman - Dewi Anggraini', NULL, 13, '2026-03-25', 3, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(613, 'masuk', 'pembayaran_angsuran', 'pinjaman', 530000.00, 0.00, 'Angsuran ke-1 - Dewi Anggraini', NULL, 30, '2026-04-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(614, 'masuk', 'pembayaran_angsuran', 'pinjaman', 525000.00, 0.00, 'Angsuran ke-2 - Dewi Anggraini', NULL, 31, '2026-05-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(615, 'masuk', 'pembayaran_angsuran', 'pinjaman', 520000.00, 0.00, 'Angsuran ke-3 - Dewi Anggraini', NULL, 32, '2026-06-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(616, 'keluar', 'pencairan_pinjaman', 'pinjaman', 4000000.00, 0.00, 'Pencairan pinjaman - Ayu Lestari', NULL, 14, '2025-10-25', 3, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(617, 'masuk', 'pembayaran_angsuran', 'pinjaman', 484444.44, 0.00, 'Angsuran ke-1 - Ayu Lestari', NULL, 36, '2025-11-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(618, 'masuk', 'pembayaran_angsuran', 'pinjaman', 480000.00, 0.00, 'Angsuran ke-2 - Ayu Lestari', NULL, 37, '2025-12-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(619, 'masuk', 'pembayaran_angsuran', 'pinjaman', 475555.56, 0.00, 'Angsuran ke-3 - Ayu Lestari', NULL, 38, '2026-01-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(620, 'masuk', 'pembayaran_angsuran', 'pinjaman', 471111.11, 0.00, 'Angsuran ke-4 - Ayu Lestari', NULL, 39, '2026-02-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(621, 'masuk', 'pembayaran_angsuran', 'pinjaman', 466666.67, 0.00, 'Angsuran ke-5 - Ayu Lestari', NULL, 40, '2026-03-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(622, 'masuk', 'pembayaran_angsuran', 'pinjaman', 462222.22, 0.00, 'Angsuran ke-6 - Ayu Lestari', NULL, 41, '2026-04-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(623, 'masuk', 'pembayaran_angsuran', 'pinjaman', 457777.78, 0.00, 'Angsuran ke-7 - Ayu Lestari', NULL, 42, '2026-05-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(624, 'masuk', 'pembayaran_angsuran', 'pinjaman', 453333.33, 0.00, 'Angsuran ke-8 - Ayu Lestari', NULL, 43, '2026-06-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(625, 'masuk', 'pembayaran_angsuran', 'pinjaman', 448888.89, 0.00, 'Angsuran ke-9 - Ayu Lestari', NULL, 44, '2026-07-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(626, 'keluar', 'pencairan_pinjaman', 'pinjaman', 6000000.00, 0.00, 'Pencairan pinjaman - Laila Amalia', NULL, 15, '2025-06-25', 3, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(627, 'masuk', 'pembayaran_angsuran', 'pinjaman', 560000.00, 0.00, 'Angsuran ke-1 - Laila Amalia', NULL, 45, '2025-07-25', 2, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(628, 'masuk', 'pembayaran_angsuran', 'pinjaman', 555000.00, 0.00, 'Angsuran ke-2 - Laila Amalia', NULL, 46, '2025-08-25', 2, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(629, 'masuk', 'pembayaran_angsuran', 'pinjaman', 550000.00, 0.00, 'Angsuran ke-3 - Laila Amalia', NULL, 47, '2025-09-25', 2, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(630, 'masuk', 'pembayaran_angsuran', 'pinjaman', 545000.00, 0.00, 'Angsuran ke-4 - Laila Amalia', NULL, 48, '2025-10-25', 2, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(631, 'masuk', 'pembayaran_angsuran', 'pinjaman', 540000.00, 0.00, 'Angsuran ke-5 - Laila Amalia', NULL, 49, '2025-11-25', 2, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(632, 'masuk', 'pembayaran_angsuran', 'pinjaman', 535000.00, 0.00, 'Angsuran ke-6 - Laila Amalia', NULL, 50, '2025-12-25', 2, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(633, 'masuk', 'pembayaran_angsuran', 'pinjaman', 530000.00, 0.00, 'Angsuran ke-7 - Laila Amalia', NULL, 51, '2026-01-25', 2, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(634, 'masuk', 'pembayaran_angsuran', 'pinjaman', 525000.00, 0.00, 'Angsuran ke-8 - Laila Amalia', NULL, 52, '2026-02-25', 2, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(635, 'masuk', 'pembayaran_angsuran', 'pinjaman', 520000.00, 0.00, 'Angsuran ke-9 - Laila Amalia', NULL, 53, '2026-03-25', 2, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(636, 'masuk', 'pembayaran_angsuran', 'pinjaman', 515000.00, 0.00, 'Angsuran ke-10 - Laila Amalia', NULL, 54, '2026-04-25', 2, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(637, 'masuk', 'pembayaran_angsuran', 'pinjaman', 510000.00, 0.00, 'Angsuran ke-11 - Laila Amalia', NULL, 55, '2026-05-25', 2, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(638, 'masuk', 'pembayaran_angsuran', 'pinjaman', 505000.00, 0.00, 'Angsuran ke-12 - Laila Amalia', NULL, 56, '2026-06-25', 2, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(639, 'keluar', 'pencairan_pinjaman', 'pinjaman', 2500000.00, 0.00, 'Pencairan pinjaman - Citra Ramadhani', NULL, 16, '2025-12-25', 3, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(640, 'masuk', 'pembayaran_angsuran', 'pinjaman', 441666.67, 0.00, 'Angsuran ke-1 - Citra Ramadhani', NULL, 57, '2026-01-25', 2, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(641, 'masuk', 'pembayaran_angsuran', 'pinjaman', 437500.00, 0.00, 'Angsuran ke-2 - Citra Ramadhani', NULL, 58, '2026-02-25', 2, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(642, 'masuk', 'pembayaran_angsuran', 'pinjaman', 433333.33, 0.00, 'Angsuran ke-3 - Citra Ramadhani', NULL, 59, '2026-03-25', 2, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(643, 'masuk', 'pembayaran_angsuran', 'pinjaman', 429166.67, 0.00, 'Angsuran ke-4 - Citra Ramadhani', NULL, 60, '2026-04-25', 2, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(644, 'masuk', 'pembayaran_angsuran', 'pinjaman', 425000.00, 0.00, 'Angsuran ke-5 - Citra Ramadhani', NULL, 61, '2026-05-25', 2, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(645, 'masuk', 'pembayaran_angsuran', 'pinjaman', 420833.33, 0.00, 'Angsuran ke-6 - Citra Ramadhani', NULL, 62, '2026-06-25', 2, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(646, 'masuk', 'topup_bulanan', 'pinjaman', 20000000.00, 0.00, 'Topup saldo koperasi', NULL, 990001, '2026-04-02', 2, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(647, 'masuk', 'topup_bulanan', 'pinjaman', 15000000.00, 0.00, 'Topup saldo koperasi', NULL, 990002, '2026-06-02', 2, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(648, 'masuk', 'pembayaran_angsuran', 'pinjaman', 510000.00, 231760000.00, 'Angsuran ke-3 - Eko Prasetyo', NULL, 28, '2026-08-22', 1, '2026-08-21 19:21:16', '2026-08-21 19:21:16'),
(649, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4465000.00, 'Dana sosial bulan 2026-09', NULL, 17, '2026-08-22', 1, '2026-08-21 19:21:26', '2026-08-21 19:21:26'),
(650, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 13185000.00, 'Simpanan wajib bulan 2026-09', 'Simpanan wajib masuk', 17, '2026-08-22', 1, '2026-08-21 19:21:26', '2026-08-21 19:21:26'),
(651, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4470000.00, 'Dana sosial bulan 2026-10', NULL, 17, '2026-08-22', 1, '2026-08-21 19:21:32', '2026-08-21 19:21:32'),
(652, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 13230000.00, 'Simpanan wajib bulan 2026-10', 'Simpanan wajib masuk', 17, '2026-08-22', 1, '2026-08-21 19:21:33', '2026-08-21 19:21:33'),
(653, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4475000.00, 'Dana sosial bulan 2026-11', NULL, 17, '2026-08-22', 1, '2026-08-21 19:21:38', '2026-08-21 19:21:38'),
(654, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 13275000.00, 'Simpanan wajib bulan 2026-11', 'Simpanan wajib masuk', 17, '2026-08-22', 1, '2026-08-21 19:21:38', '2026-08-21 19:21:38'),
(655, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4480000.00, 'Dana sosial bulan 2026-12', NULL, 17, '2026-08-22', 1, '2026-08-21 19:21:42', '2026-08-21 19:21:42'),
(656, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 13320000.00, 'Simpanan wajib bulan 2026-12', 'Simpanan wajib masuk', 17, '2026-08-22', 1, '2026-08-21 19:21:42', '2026-08-21 19:21:42'),
(657, 'masuk', 'dana_sosial_bulanan', 'dana_sosial', 5000.00, 4485000.00, 'Dana sosial bulan 2027-01', NULL, 17, '2026-08-22', 1, '2026-08-21 19:21:51', '2026-08-21 19:21:51'),
(658, 'masuk', 'simpanan_wajib_masuk', 'simpanan', 45000.00, 13365000.00, 'Simpanan wajib bulan 2027-01', 'Simpanan wajib masuk', 17, '2026-08-22', 1, '2026-08-21 19:21:51', '2026-08-21 19:21:51'),
(659, 'masuk', 'simpanan_resign_masuk', 'pengembalian_simpanan', 545000.00, 545000.00, 'Simpanan anggota masuk (proses resign) - Eko Prasetyo', 'Simpanan anggota ditarik ke kantong pengembalian', 17, '2026-08-22', 1, '2026-08-21 19:22:33', '2026-08-21 19:22:33'),
(660, 'masuk', 'pelunasan_resign_pinjaman', 'pinjaman', 505000.00, 232265000.00, 'Pelunasan resign angsuran ke-4 - Eko Prasetyo', 'Pelunasan dari uang simpanan anggota', 29, '2026-08-22', 1, '2026-08-21 19:22:33', '2026-08-21 19:22:33'),
(661, 'keluar', 'pelunasan_resign_simpanan', 'pengembalian_simpanan', 505000.00, 40000.00, 'Pelunasan resign angsuran ke-4 - Eko Prasetyo', 'Uang simpanan dibayarkan angsuran', 29, '2026-08-22', 1, '2026-08-21 19:22:33', '2026-08-21 19:22:33'),
(662, 'keluar', 'return_simpanan_wajib', 'pengembalian_simpanan', 40000.00, 0.00, 'Pengembalian simpanan wajib resign - Eko Prasetyo', 'Pengembalian ke anggota', 17, '2026-08-22', 1, '2026-08-21 19:22:33', '2026-08-21 19:22:33'),
(663, 'keluar', 'pencairan_pinjaman', 'pinjaman', 8000000.00, 224265000.00, 'Pencairan pinjaman - Dewi Lestari', NULL, 17, '2026-08-22', 1, '2026-08-21 19:25:46', '2026-08-21 19:25:46'),
(664, 'masuk', 'pembayaran_angsuran', 'pinjaman', 841666.67, 225106666.67, 'Angsuran (perubahan tenor) ke-1 - Dewi Lestari', NULL, 1, '2026-08-22', 1, '2026-08-21 19:28:41', '2026-08-21 19:28:41');

-- --------------------------------------------------------

--
-- Table structure for table `kas_koperasi`
--

CREATE TABLE `kas_koperasi` (
  `id` bigint UNSIGNED NOT NULL,
  `saldo_pinjaman` decimal(15,2) NOT NULL DEFAULT '0.00',
  `saldo_dana_sosial` decimal(15,2) NOT NULL DEFAULT '0.00',
  `saldo_pengembalian_simpanan` decimal(15,2) NOT NULL DEFAULT '0.00',
  `saldo_simpanan` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kas_koperasi`
--

INSERT INTO `kas_koperasi` (`id`, `saldo_pinjaman`, `saldo_dana_sosial`, `saldo_pengembalian_simpanan`, `saldo_simpanan`, `created_at`, `updated_at`) VALUES
(1, 225106666.67, 4485000.00, 0.00, 13365000.00, '2026-08-21 19:19:54', '2026-08-21 19:28:41');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_08_07_020055_create_permission_tables', 1),
(5, '2026_08_07_121219_create_anggota_table', 1),
(6, '2026_08_07_121221_create_tabel_tenor_table', 1),
(7, '2026_08_07_121222_create_setting_bunga_table', 1),
(8, '2026_08_07_121223_create_simpanan_table', 1),
(9, '2026_08_07_121224_create_pinjaman_table', 1),
(10, '2026_08_07_121225_create_angsuran_table', 1),
(11, '2026_08_07_121226_create_kas_koperasi_table', 1),
(12, '2026_08_07_121227_create_jurnal_kas_table', 1),
(13, '2026_08_08_050402_create_setting_limit_pinjaman_table', 1),
(14, '2026_08_08_200350_create_setting_simpanan_table', 1),
(15, '2026_08_09_005516_create_audit_log_table', 1),
(16, '2026_08_09_013745_add_limit_custom_to_anggota_table', 1),
(17, '2026_08_10_090224_create_rekening_anggota_table', 1),
(18, '2026_08_10_090314_add_keperluan_dan_rekening_to_pinjaman_table', 1),
(19, '2026_08_11_044954_add_data_karyawan_to_anggota_table', 1),
(20, '2026_08_11_045025_add_no_karyawan_to_users_table', 1),
(21, '2026_08_11_070904_make_email_nullable_in_users_table', 1),
(22, '2026_08_12_005642_add_sso_fields_to_users_table', 1),
(23, '2026_08_14_000000_add_pengaju_dan_cair_oleh_bendahara_to_pinjaman_table', 1),
(24, '2026_08_16_020809_restructure_kas_koperasi_table', 1),
(25, '2026_08_16_020936_add_kantong_to_jurnal_kas_table', 1),
(26, '2026_08_16_023219_add_kategori_options_to_jurnal_kas_table', 1),
(27, '2026_08_16_042909_create_pengeluaran_table', 1),
(28, '2026_08_16_051647_create_pengajuan_limit_table', 1),
(29, '2026_08_16_100645_add_saldo_setelah_to_jurnal_kas_table', 1),
(30, '2026_08_16_100844_add_saldo_awal_kategori_to_jurnal_kas_table', 1),
(31, '2026_08_19_055127_create_pengajuan_percepatan_table', 1),
(32, '2026_08_19_055212_update_angsuran_and_pinjaman_for_percepatan', 1),
(33, '2026_08_19_055241_create_angsuran_percepatan_table', 1),
(34, '2026_08_20_042903_add_status_to_users_table', 1),
(35, '2026_08_21_035725_tambah_field_resign_ke_anggota', 1),
(36, '2026_08_21_052312_tambah_kategori_resign_ke_jurnal_kas', 1),
(37, '2026_08_21_055746_add_persetujuan_audit_to_pinjaman_table', 1),
(38, '2026_08_21_065416_tambah_kantong_pengembalian_simpanan_ke_kas_koperasi', 1),
(39, '2026_08_21_065543_tambah_sub_judul_dan_kategori_transfer_ke_jurnal_kas', 1),
(40, '2026_08_21_231000_fix_jurnal_kas_sqlite_check_constraint', 1),
(41, '2026_08_22_000000_add_resign_simpanan_kategori_to_jurnal_kas', 1),
(42, '2026_08_22_100001_add_saldo_simpanan', 1);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1),
(2, 'App\\Models\\User', 2),
(3, 'App\\Models\\User', 3),
(4, 'App\\Models\\User', 4),
(4, 'App\\Models\\User', 5),
(4, 'App\\Models\\User', 6),
(4, 'App\\Models\\User', 7),
(4, 'App\\Models\\User', 8),
(4, 'App\\Models\\User', 9),
(4, 'App\\Models\\User', 10),
(4, 'App\\Models\\User', 11),
(4, 'App\\Models\\User', 12),
(4, 'App\\Models\\User', 13),
(4, 'App\\Models\\User', 14),
(4, 'App\\Models\\User', 15),
(4, 'App\\Models\\User', 16),
(4, 'App\\Models\\User', 17),
(4, 'App\\Models\\User', 18),
(4, 'App\\Models\\User', 19),
(4, 'App\\Models\\User', 20),
(4, 'App\\Models\\User', 21),
(4, 'App\\Models\\User', 22),
(4, 'App\\Models\\User', 23),
(4, 'App\\Models\\User', 24),
(4, 'App\\Models\\User', 25),
(4, 'App\\Models\\User', 26),
(4, 'App\\Models\\User', 27),
(4, 'App\\Models\\User', 28),
(4, 'App\\Models\\User', 29),
(4, 'App\\Models\\User', 30),
(4, 'App\\Models\\User', 31),
(4, 'App\\Models\\User', 32),
(4, 'App\\Models\\User', 33),
(4, 'App\\Models\\User', 34),
(4, 'App\\Models\\User', 35),
(4, 'App\\Models\\User', 36),
(4, 'App\\Models\\User', 37),
(4, 'App\\Models\\User', 38),
(4, 'App\\Models\\User', 39),
(4, 'App\\Models\\User', 40),
(4, 'App\\Models\\User', 41),
(4, 'App\\Models\\User', 42),
(4, 'App\\Models\\User', 43),
(4, 'App\\Models\\User', 44),
(4, 'App\\Models\\User', 45),
(4, 'App\\Models\\User', 46),
(4, 'App\\Models\\User', 47),
(4, 'App\\Models\\User', 48),
(4, 'App\\Models\\User', 49),
(4, 'App\\Models\\User', 50),
(4, 'App\\Models\\User', 51),
(4, 'App\\Models\\User', 52),
(4, 'App\\Models\\User', 53);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan_limit`
--

CREATE TABLE `pengajuan_limit` (
  `id` bigint UNSIGNED NOT NULL,
  `anggota_id` bigint UNSIGNED NOT NULL,
  `limit_saat_ini` decimal(15,2) NOT NULL,
  `limit_diminta` decimal(15,2) NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('diajukan','disetujui','ditolak') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'diajukan',
  `catatan_ketua` text COLLATE utf8mb4_unicode_ci,
  `tanggal_pengajuan` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengajuan_limit`
--

INSERT INTO `pengajuan_limit` (`id`, `anggota_id`, `limit_saat_ini`, `limit_diminta`, `keterangan`, `status`, `catatan_ketua`, `tanggal_pengajuan`, `created_at`, `updated_at`) VALUES
(1, 4, 10000000.00, 15000000.00, 'keperluan urgent', 'disetujui', 'approve', '2026-08-22', '2026-08-21 19:29:07', '2026-08-21 19:29:26');

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan_percepatan`
--

CREATE TABLE `pengajuan_percepatan` (
  `id` bigint UNSIGNED NOT NULL,
  `pinjaman_id` bigint UNSIGNED NOT NULL,
  `tipe` enum('percepat','perpanjang','lunas_total') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenor_lama` int UNSIGNED NOT NULL,
  `tenor_baru` int UNSIGNED DEFAULT NULL,
  `sisa_pokok_saat_approval` decimal(15,2) DEFAULT NULL,
  `nominal_final` decimal(15,2) DEFAULT NULL,
  `bulan_berlaku` enum('bulan_ini','bulan_depan') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('diajukan','approved_bendahara','aktif','ditolak') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'diajukan',
  `catatan_bendahara` text COLLATE utf8mb4_unicode_ci,
  `catatan_ketua` text COLLATE utf8mb4_unicode_ci,
  `tanggal_pengajuan` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengajuan_percepatan`
--

INSERT INTO `pengajuan_percepatan` (`id`, `pinjaman_id`, `tipe`, `tenor_lama`, `tenor_baru`, `sisa_pokok_saat_approval`, `nominal_final`, `bulan_berlaku`, `keterangan`, `status`, `catatan_bendahara`, `catatan_ketua`, `tanggal_pengajuan`, `created_at`, `updated_at`) VALUES
(1, 4, 'lunas_total', 12, NULL, 833333.34, 841666.67, 'bulan_ini', 'langsung lunas', 'aktif', 'disetujui', 'approve', '2026-08-22', '2026-08-21 19:26:46', '2026-08-21 19:27:18');

-- --------------------------------------------------------

--
-- Table structure for table `pengeluaran`
--

CREATE TABLE `pengeluaran` (
  `id` bigint UNSIGNED NOT NULL,
  `jenis` enum('koperasi','dana_sosial') COLLATE utf8mb4_unicode_ci NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal` date NOT NULL,
  `input_by` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'anggota.lihat', 'web', '2026-08-21 19:19:39', '2026-08-21 19:19:39'),
(2, 'anggota.kelola', 'web', '2026-08-21 19:19:39', '2026-08-21 19:19:39'),
(3, 'anggota.resign', 'web', '2026-08-21 19:19:39', '2026-08-21 19:19:39'),
(4, 'simpanan.lihat', 'web', '2026-08-21 19:19:39', '2026-08-21 19:19:39'),
(5, 'simpanan.konfirmasi', 'web', '2026-08-21 19:19:39', '2026-08-21 19:19:39'),
(6, 'pinjaman.lihat', 'web', '2026-08-21 19:19:39', '2026-08-21 19:19:39'),
(7, 'pinjaman.tinjau-bendahara', 'web', '2026-08-21 19:19:39', '2026-08-21 19:19:39'),
(8, 'pinjaman.approve-ketua', 'web', '2026-08-21 19:19:39', '2026-08-21 19:19:39'),
(9, 'angsuran.konfirmasi', 'web', '2026-08-21 19:19:39', '2026-08-21 19:19:39'),
(10, 'kas.lihat', 'web', '2026-08-21 19:19:39', '2026-08-21 19:19:39'),
(11, 'kas.topup', 'web', '2026-08-21 19:19:39', '2026-08-21 19:19:39'),
(12, 'laporan.lihat', 'web', '2026-08-21 19:19:39', '2026-08-21 19:19:39'),
(13, 'pengaturan.kelola', 'web', '2026-08-21 19:19:40', '2026-08-21 19:19:40'),
(14, 'user.kelola', 'web', '2026-08-21 19:19:40', '2026-08-21 19:19:40'),
(15, 'portal.akses', 'web', '2026-08-21 19:19:40', '2026-08-21 19:19:40');

-- --------------------------------------------------------

--
-- Table structure for table `pinjaman`
--

CREATE TABLE `pinjaman` (
  `id` bigint UNSIGNED NOT NULL,
  `anggota_id` bigint UNSIGNED NOT NULL,
  `pengaju_user_id` bigint UNSIGNED DEFAULT NULL,
  `nominal` decimal(15,2) NOT NULL,
  `tenor_bulan` int UNSIGNED NOT NULL,
  `keperluan` text COLLATE utf8mb4_unicode_ci,
  `snapshot_bank` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `snapshot_no_rekening` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `snapshot_atas_nama` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `persentase_bunga` decimal(5,2) NOT NULL,
  `status` enum('diajukan','ditinjau_bendahara','approved_bendahara','approved_ketua','aktif','lunas','ditolak') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'diajukan',
  `cair_oleh_bendahara` tinyint(1) NOT NULL DEFAULT '0',
  `sudah_pakai_privilege_reloan` tinyint(1) NOT NULL DEFAULT '0',
  `sudah_pakai_percepatan` tinyint(1) NOT NULL DEFAULT '0',
  `tanggal_pengajuan` date NOT NULL,
  `tanggal_pencairan` date DEFAULT NULL,
  `disetujui_pada` timestamp NULL DEFAULT NULL,
  `versi_syarat` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address_setuju` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent_setuju` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catatan_bendahara` text COLLATE utf8mb4_unicode_ci,
  `catatan_ketua` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pinjaman`
--

INSERT INTO `pinjaman` (`id`, `anggota_id`, `pengaju_user_id`, `nominal`, `tenor_bulan`, `keperluan`, `snapshot_bank`, `snapshot_no_rekening`, `snapshot_atas_nama`, `persentase_bunga`, `status`, `cair_oleh_bendahara`, `sudah_pakai_privilege_reloan`, `sudah_pakai_percepatan`, `tanggal_pengajuan`, `tanggal_pencairan`, `disetujui_pada`, `versi_syarat`, `ip_address_setuju`, `user_agent_setuju`, `catatan_bendahara`, `catatan_ketua`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 1000000.00, 3, 'Kebutuhan harian', 'BCA', '1234001001', 'Budi Santoso', 1.00, 'diajukan', 0, 0, 0, '2026-08-20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(2, 2, NULL, 2000000.00, 4, 'Biaya pendidikan anak', 'Mandiri', '8213400220', 'Siti Aminah', 1.00, 'aktif', 0, 0, 0, '2026-05-22', '2026-05-25', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(3, 3, NULL, 3000000.00, 6, 'Perbaikan rumah', 'BRI', '72810033', 'Ahmad Ridwan', 1.00, 'lunas', 0, 0, 0, '2025-12-22', '2025-12-25', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(4, 4, NULL, 5000000.00, 12, 'Pembelian kendaraan', 'BNI', '20987654', 'Dewi Lestari', 1.00, 'lunas', 0, 1, 1, '2025-10-22', '2025-10-25', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:20:10', '2026-08-21 19:28:41'),
(5, 6, NULL, 1500000.00, 4, 'Kebutuhan hari raya', 'BCA', '1234002002', 'Agus Wijaya', 1.00, 'diajukan', 0, 0, 0, '2026-08-21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(6, 16, NULL, 2500000.00, 6, 'Biaya pendidikan anak', 'Mandiri', '8213400221', 'Adi Nugroho', 1.00, 'diajukan', 0, 0, 0, '2026-08-19', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(7, 26, NULL, 5000000.00, 12, 'Perbaikan rumah', 'BRI', '72810034', 'Deni Setiawan', 1.00, 'diajukan', 0, 0, 0, '2026-08-17', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(8, 8, NULL, 3500000.00, 9, 'Biaya pengobatan', 'BNI', '20987655', 'Maya Sari', 1.00, 'approved_bendahara', 0, 0, 0, '2026-08-14', NULL, NULL, NULL, NULL, NULL, 'Verifikasi dokumen lengkap, layak diteruskan ke Ketua.', NULL, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(9, 18, NULL, 6000000.00, 12, 'Pembelian kendaraan', 'Bank Kalsel', '55990011', 'Yudha Pradana', 1.00, 'approved_bendahara', 0, 0, 0, '2026-08-12', NULL, NULL, NULL, NULL, NULL, 'Riwayat angsuran baik, disetujui.', NULL, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(10, 28, NULL, 2000000.00, 4, 'Modal usaha', 'BCA', '1234003003', 'Galih Prakoso', 1.00, 'approved_bendahara', 0, 0, 0, '2026-08-10', NULL, NULL, NULL, NULL, NULL, 'Dokumen sesuai ketentuan.', NULL, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(11, 7, NULL, 1000000.00, 3, 'Perlengkapan rumah tangga', 'BCA', '1234004004', 'Hendra Gunawan', 1.00, 'aktif', 0, 0, 0, '2026-06-22', '2026-06-25', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(12, 17, NULL, 2000000.00, 4, 'Biaya pendidikan anak', 'Mandiri', '8213400222', 'Indah Permata', 1.00, 'lunas', 0, 0, 0, '2026-05-22', '2026-05-25', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:20:11', '2026-08-21 19:22:33'),
(13, 10, NULL, 3000000.00, 6, 'Perbaikan rumah', 'BRI', '72810035', 'Joko Susanto', 1.00, 'aktif', 0, 0, 0, '2026-03-22', '2026-03-25', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(14, 30, NULL, 4000000.00, 9, 'Modal usaha', 'BNI', '20987656', 'Ferry Ardiansyah', 1.00, 'lunas', 0, 0, 0, '2025-10-22', '2025-10-25', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(15, 40, NULL, 6000000.00, 12, 'Pembelian kendaraan', 'Bank Kalsel', '55990012', 'Candra Wijaya', 1.00, 'lunas', 0, 0, 0, '2025-06-22', '2025-06-25', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:20:11', '2026-08-21 19:20:11'),
(16, 50, NULL, 2500000.00, 6, 'Kebutuhan hari raya', 'BCA', '1234005005', 'Citra Ramadhani', 1.00, 'lunas', 0, 0, 0, '2025-12-22', '2025-12-25', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 19:20:12', '2026-08-21 19:20:12'),
(17, 4, 7, 8000000.00, 10, 'biayay anak', 'BCA', '9893483948', 'TESTING', 1.00, 'aktif', 0, 0, 0, '2026-08-22', '2026-08-22', '2026-08-21 19:24:56', 'v1.1-2026-08-21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'DATA LENGKAP', 'APPROVE', '2026-08-21 19:24:56', '2026-08-21 19:25:46');

-- --------------------------------------------------------

--
-- Table structure for table `rekening_anggota`
--

CREATE TABLE `rekening_anggota` (
  `id` bigint UNSIGNED NOT NULL,
  `anggota_id` bigint UNSIGNED NOT NULL,
  `nama_bank` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_rekening` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `atas_nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rekening_anggota`
--

INSERT INTO `rekening_anggota` (`id`, `anggota_id`, `nama_bank`, `no_rekening`, `atas_nama`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 4, 'BCA', '9893483948', 'TESTING', 1, '2026-08-21 19:24:56', '2026-08-21 19:24:56');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'web', '2026-08-21 19:19:40', '2026-08-21 19:19:40'),
(2, 'bendahara', 'web', '2026-08-21 19:19:40', '2026-08-21 19:19:40'),
(3, 'ketua_koperasi', 'web', '2026-08-21 19:19:40', '2026-08-21 19:19:40'),
(4, 'anggota', 'web', '2026-08-21 19:19:40', '2026-08-21 19:19:40');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `role_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 1),
(5, 1),
(6, 1),
(7, 1),
(8, 1),
(9, 1),
(10, 1),
(11, 1),
(12, 1),
(13, 1),
(14, 1),
(1, 2),
(4, 2),
(5, 2),
(6, 2),
(7, 2),
(9, 2),
(10, 2),
(11, 2),
(12, 2),
(15, 2),
(1, 3),
(4, 3),
(6, 3),
(8, 3),
(10, 3),
(12, 3),
(15, 3),
(15, 4);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `setting_bunga`
--

CREATE TABLE `setting_bunga` (
  `id` bigint UNSIGNED NOT NULL,
  `persentase` decimal(5,2) NOT NULL,
  `berlaku_dari_tanggal` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `setting_bunga`
--

INSERT INTO `setting_bunga` (`id`, `persentase`, `berlaku_dari_tanggal`, `created_at`, `updated_at`) VALUES
(1, 1.00, '2026-01-01', '2026-08-21 19:19:54', '2026-08-21 19:19:54');

-- --------------------------------------------------------

--
-- Table structure for table `setting_limit_pinjaman`
--

CREATE TABLE `setting_limit_pinjaman` (
  `id` bigint UNSIGNED NOT NULL,
  `kategori` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `limit_maksimal` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `setting_limit_pinjaman`
--

INSERT INTO `setting_limit_pinjaman` (`id`, `kategori`, `label`, `limit_maksimal`, `created_at`, `updated_at`) VALUES
(1, 'kurang_1_tahun', 'Anggota < 1 Tahun', 1000000.00, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(2, 'satu_sampai_3_tahun', 'Anggota 1-3 Tahun', 5000000.00, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(3, 'tiga_sampai_5_tahun', 'Anggota 3-5 Tahun', 7000000.00, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(4, 'lebih_5_tahun', 'Anggota > 5 Tahun', 10000000.00, '2026-08-21 19:19:54', '2026-08-21 19:19:54');

-- --------------------------------------------------------

--
-- Table structure for table `setting_simpanan`
--

CREATE TABLE `setting_simpanan` (
  `id` bigint UNSIGNED NOT NULL,
  `jenis` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nominal` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `setting_simpanan`
--

INSERT INTO `setting_simpanan` (`id`, `jenis`, `label`, `nominal`, `created_at`, `updated_at`) VALUES
(1, 'pokok', 'Simpanan Pokok', 50000.00, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(2, 'wajib', 'Simpanan Wajib', 45000.00, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(3, 'dana_sosial', 'Dana Sosial', 5000.00, '2026-08-21 19:19:54', '2026-08-21 19:19:54');

-- --------------------------------------------------------

--
-- Table structure for table `simpanan`
--

CREATE TABLE `simpanan` (
  `id` bigint UNSIGNED NOT NULL,
  `anggota_id` bigint UNSIGNED NOT NULL,
  `jenis` enum('pokok','wajib','dana_sosial') COLLATE utf8mb4_unicode_ci NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `bulan_periode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_input` date NOT NULL,
  `input_by` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `simpanan`
--

INSERT INTO `simpanan` (`id`, `anggota_id`, `jenis`, `jumlah`, `bulan_periode`, `tanggal_input`, `input_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'pokok', 50000.00, '2026-02', '2026-02-22', 4, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(2, 2, 'pokok', 50000.00, '2023-08', '2023-08-22', 5, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(3, 3, 'pokok', 50000.00, '2020-08', '2020-08-22', 6, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(4, 4, 'pokok', 50000.00, '2019-08', '2019-08-22', 7, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(5, 5, 'pokok', 50000.00, '2026-04', '2026-04-22', 8, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(6, 6, 'pokok', 50000.00, '2024-07', '2024-07-22', 9, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(7, 7, 'pokok', 50000.00, '2023-06', '2023-06-22', 10, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(8, 8, 'pokok', 50000.00, '2018-05', '2018-05-22', 11, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(9, 9, 'pokok', 50000.00, '2025-12', '2025-12-22', 12, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(10, 10, 'pokok', 50000.00, '2024-03', '2024-03-22', 13, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(11, 11, 'pokok', 50000.00, '2023-02', '2023-02-22', 14, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(12, 12, 'pokok', 50000.00, '2020-06', '2020-06-22', 15, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(13, 13, 'pokok', 50000.00, '2026-01', '2026-01-22', 16, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(14, 14, 'pokok', 50000.00, '2023-11', '2023-11-22', 17, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(15, 15, 'pokok', 50000.00, '2023-05', '2023-05-22', 18, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(16, 16, 'pokok', 50000.00, '2016-07', '2016-07-22', 19, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(17, 17, 'pokok', 50000.00, '2026-02', '2026-02-22', 20, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(18, 18, 'pokok', 50000.00, '2024-06', '2024-06-22', 21, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(19, 19, 'pokok', 50000.00, '2023-08', '2023-08-22', 22, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(20, 20, 'pokok', 50000.00, '2018-08', '2018-08-22', 23, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(21, 21, 'pokok', 50000.00, '2026-03', '2026-03-22', 24, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(22, 22, 'pokok', 50000.00, '2024-02', '2024-02-22', 25, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(23, 23, 'pokok', 50000.00, '2023-04', '2023-04-22', 26, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(24, 24, 'pokok', 50000.00, '2020-04', '2020-04-22', 27, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(25, 25, 'pokok', 50000.00, '2026-04', '2026-04-22', 28, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(26, 26, 'pokok', 50000.00, '2023-10', '2023-10-22', 29, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(27, 27, 'pokok', 50000.00, '2023-07', '2023-07-22', 30, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(28, 28, 'pokok', 50000.00, '2016-05', '2016-05-22', 31, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(29, 29, 'pokok', 50000.00, '2025-12', '2025-12-22', 32, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(30, 30, 'pokok', 50000.00, '2024-05', '2024-05-22', 33, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(31, 31, 'pokok', 50000.00, '2023-03', '2023-03-22', 34, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(32, 32, 'pokok', 50000.00, '2018-06', '2018-06-22', 35, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(33, 33, 'pokok', 50000.00, '2026-01', '2026-01-22', 36, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(34, 34, 'pokok', 50000.00, '2024-01', '2024-01-22', 37, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(35, 35, 'pokok', 50000.00, '2023-06', '2023-06-22', 38, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(36, 36, 'pokok', 50000.00, '2020-07', '2020-07-22', 39, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(37, 37, 'pokok', 50000.00, '2026-02', '2026-02-22', 40, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(38, 38, 'pokok', 50000.00, '2024-08', '2024-08-22', 41, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(39, 39, 'pokok', 50000.00, '2023-02', '2023-02-22', 42, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(40, 40, 'pokok', 50000.00, '2016-08', '2016-08-22', 43, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(41, 41, 'pokok', 50000.00, '2026-03', '2026-03-22', 44, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(42, 42, 'pokok', 50000.00, '2024-04', '2024-04-22', 45, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(43, 43, 'pokok', 50000.00, '2023-05', '2023-05-22', 46, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(44, 44, 'pokok', 50000.00, '2018-04', '2018-04-22', 47, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(45, 45, 'pokok', 50000.00, '2026-04', '2026-04-22', 48, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(46, 46, 'pokok', 50000.00, '2023-12', '2023-12-22', 49, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(47, 47, 'pokok', 50000.00, '2023-08', '2023-08-22', 50, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(48, 48, 'pokok', 50000.00, '2020-05', '2020-05-22', 51, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(49, 49, 'pokok', 50000.00, '2025-12', '2025-12-22', 52, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(50, 50, 'pokok', 50000.00, '2024-07', '2024-07-22', 53, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(51, 51, 'pokok', 50000.00, '2019-08', '2019-08-22', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(52, 52, 'pokok', 50000.00, '2017-08', '2017-08-22', 3, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(53, 1, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(54, 1, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(55, 1, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(56, 1, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(57, 1, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(58, 1, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(59, 1, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(60, 1, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(61, 1, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(62, 1, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(63, 1, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(64, 1, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(65, 2, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(66, 2, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(67, 2, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:55', '2026-08-21 19:19:55'),
(68, 2, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(69, 2, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(70, 2, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(71, 2, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(72, 2, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(73, 2, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(74, 2, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(75, 2, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(76, 2, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(77, 3, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(78, 3, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(79, 3, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(80, 3, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(81, 3, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(82, 3, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(83, 3, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(84, 3, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(85, 3, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(86, 3, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(87, 3, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(88, 3, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(89, 4, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(90, 4, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(91, 4, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(92, 4, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(93, 4, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(94, 4, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(95, 4, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(96, 4, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(97, 4, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(98, 4, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(99, 4, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(100, 4, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(101, 5, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(102, 5, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(103, 5, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(104, 5, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(105, 5, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(106, 5, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(107, 5, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(108, 5, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:56', '2026-08-21 19:19:56'),
(109, 6, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(110, 6, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(111, 6, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(112, 6, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(113, 6, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(114, 6, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(115, 6, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(116, 6, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(117, 6, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(118, 6, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(119, 6, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(120, 6, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(121, 7, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(122, 7, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(123, 7, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(124, 7, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(125, 7, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(126, 7, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(127, 7, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(128, 7, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(129, 7, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(130, 7, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(131, 7, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(132, 7, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(133, 8, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(134, 8, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(135, 8, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(136, 8, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(137, 8, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(138, 8, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(139, 8, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(140, 8, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(141, 8, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(142, 8, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(143, 8, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(144, 8, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(145, 9, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(146, 9, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(147, 9, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(148, 9, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(149, 9, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(150, 9, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:57', '2026-08-21 19:19:57'),
(151, 9, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(152, 9, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(153, 9, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(154, 9, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(155, 9, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(156, 9, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(157, 10, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(158, 10, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(159, 10, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(160, 10, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(161, 10, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(162, 10, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(163, 10, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(164, 10, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(165, 10, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(166, 10, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(167, 11, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(168, 11, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(169, 11, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(170, 11, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(171, 11, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(172, 11, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(173, 11, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(174, 11, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(175, 11, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(176, 11, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(177, 11, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(178, 11, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(179, 12, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(180, 12, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(181, 12, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(182, 12, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(183, 12, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(184, 12, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(185, 12, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(186, 12, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(187, 12, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(188, 12, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:58', '2026-08-21 19:19:58'),
(189, 12, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(190, 12, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(191, 14, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(192, 14, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(193, 14, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(194, 14, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(195, 14, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(196, 14, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(197, 14, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(198, 14, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(199, 14, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(200, 14, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(201, 14, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(202, 14, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(203, 15, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(204, 15, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(205, 15, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(206, 15, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(207, 15, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(208, 15, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(209, 15, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(210, 15, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(211, 15, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(212, 15, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(213, 16, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(214, 16, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(215, 16, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(216, 16, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(217, 16, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(218, 16, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(219, 16, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(220, 16, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(221, 16, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(222, 16, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(223, 16, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(224, 16, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(225, 17, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(226, 17, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(227, 17, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(228, 17, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:19:59', '2026-08-21 19:19:59'),
(229, 17, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(230, 17, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(231, 17, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(232, 17, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(233, 17, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(234, 17, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(235, 17, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(236, 17, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(237, 18, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(238, 18, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(239, 18, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(240, 18, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(241, 18, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(242, 18, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(243, 18, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(244, 18, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(245, 18, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(246, 18, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(247, 18, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(248, 18, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(249, 19, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(250, 19, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(251, 19, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(252, 19, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(253, 19, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(254, 19, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(255, 19, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(256, 19, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(257, 19, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(258, 19, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(259, 19, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(260, 19, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(261, 20, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(262, 20, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(263, 20, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(264, 20, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(265, 20, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(266, 20, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(267, 20, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(268, 20, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(269, 20, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:00', '2026-08-21 19:20:00'),
(270, 20, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(271, 21, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(272, 21, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(273, 21, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(274, 21, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(275, 21, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(276, 21, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(277, 21, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(278, 21, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(279, 21, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(280, 21, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(281, 21, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(282, 21, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(283, 22, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(284, 22, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(285, 22, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(286, 22, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(287, 22, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(288, 22, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(289, 22, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(290, 22, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(291, 22, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(292, 22, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(293, 22, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(294, 22, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(295, 23, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(296, 23, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(297, 23, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(298, 23, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(299, 23, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(300, 23, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(301, 23, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(302, 23, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(303, 23, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(304, 23, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(305, 23, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(306, 23, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:01', '2026-08-21 19:20:01'),
(307, 24, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(308, 24, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(309, 24, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(310, 24, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(311, 24, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(312, 24, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(313, 24, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(314, 24, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(315, 24, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(316, 24, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(317, 24, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(318, 24, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(319, 25, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(320, 25, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(321, 25, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(322, 25, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(323, 25, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(324, 25, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(325, 25, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(326, 25, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(327, 26, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(328, 26, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(329, 26, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(330, 26, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(331, 26, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(332, 26, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(333, 26, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(334, 26, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(335, 26, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(336, 26, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(337, 26, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(338, 26, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(339, 28, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(340, 28, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(341, 28, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(342, 28, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(343, 28, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(344, 28, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(345, 28, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(346, 28, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(347, 28, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:02', '2026-08-21 19:20:02'),
(348, 28, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(349, 28, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(350, 28, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(351, 29, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(352, 29, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(353, 29, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(354, 29, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(355, 29, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(356, 29, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(357, 29, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(358, 29, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(359, 29, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(360, 29, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(361, 29, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(362, 29, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(363, 30, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(364, 30, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(365, 30, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(366, 30, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(367, 30, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(368, 30, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(369, 30, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(370, 30, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(371, 30, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(372, 30, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(373, 30, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(374, 30, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(375, 31, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(376, 31, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(377, 31, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(378, 31, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(379, 31, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(380, 31, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(381, 31, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(382, 31, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(383, 31, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(384, 31, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(385, 31, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(386, 31, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:03', '2026-08-21 19:20:03'),
(387, 32, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(388, 32, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(389, 32, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(390, 32, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(391, 32, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(392, 32, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(393, 32, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(394, 32, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(395, 32, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(396, 32, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(397, 32, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(398, 32, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(399, 33, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(400, 33, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(401, 33, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(402, 33, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(403, 33, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(404, 33, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(405, 33, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(406, 33, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(407, 33, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(408, 33, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(409, 33, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(410, 33, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(411, 34, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(412, 34, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(413, 34, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(414, 34, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(415, 34, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(416, 34, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(417, 34, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(418, 34, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(419, 34, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(420, 34, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(421, 34, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(422, 34, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(423, 35, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(424, 35, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(425, 35, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:04', '2026-08-21 19:20:04'),
(426, 35, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(427, 35, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(428, 35, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(429, 35, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(430, 35, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(431, 35, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(432, 35, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(433, 35, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(434, 35, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(435, 36, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(436, 36, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(437, 36, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(438, 36, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(439, 36, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(440, 36, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(441, 36, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(442, 36, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(443, 36, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(444, 36, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(445, 36, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(446, 36, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(447, 37, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(448, 37, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(449, 37, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(450, 37, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(451, 37, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(452, 37, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(453, 37, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(454, 37, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(455, 37, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(456, 37, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(457, 37, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(458, 37, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(459, 38, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(460, 38, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(461, 38, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(462, 38, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(463, 38, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:05', '2026-08-21 19:20:05'),
(464, 38, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(465, 38, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(466, 38, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(467, 38, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(468, 38, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(469, 38, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(470, 38, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(471, 39, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(472, 39, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(473, 39, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(474, 39, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(475, 39, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(476, 39, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(477, 39, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(478, 39, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(479, 39, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(480, 39, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06');
INSERT INTO `simpanan` (`id`, `anggota_id`, `jenis`, `jumlah`, `bulan_periode`, `tanggal_input`, `input_by`, `created_at`, `updated_at`) VALUES
(481, 39, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(482, 39, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(483, 40, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(484, 40, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(485, 40, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(486, 40, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(487, 40, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(488, 40, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(489, 40, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(490, 40, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(491, 40, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(492, 40, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(493, 40, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(494, 40, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(495, 41, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(496, 41, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(497, 41, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(498, 41, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(499, 41, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(500, 41, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(501, 41, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(502, 41, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(503, 41, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(504, 41, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(505, 41, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(506, 41, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(507, 42, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:06', '2026-08-21 19:20:06'),
(508, 42, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(509, 42, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(510, 42, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(511, 42, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(512, 42, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(513, 42, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(514, 42, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(515, 42, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(516, 42, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(517, 42, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(518, 42, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(519, 43, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(520, 43, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(521, 43, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(522, 43, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(523, 43, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(524, 43, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(525, 43, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(526, 43, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(527, 43, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(528, 43, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(529, 43, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(530, 43, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(531, 44, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(532, 44, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(533, 44, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(534, 44, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(535, 44, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(536, 44, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(537, 44, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(538, 44, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(539, 44, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(540, 44, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(541, 44, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(542, 44, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(543, 45, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(544, 45, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(545, 45, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(546, 45, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:07', '2026-08-21 19:20:07'),
(547, 45, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(548, 45, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(549, 45, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(550, 45, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(551, 45, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(552, 45, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(553, 46, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(554, 46, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(555, 46, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(556, 46, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(557, 46, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(558, 46, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(559, 46, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(560, 46, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(561, 46, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(562, 46, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(563, 46, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(564, 46, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(565, 47, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(566, 47, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(567, 47, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(568, 47, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(569, 47, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(570, 47, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(571, 47, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(572, 47, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(573, 47, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(574, 47, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(575, 47, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(576, 47, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(577, 48, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(578, 48, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(579, 48, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(580, 48, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(581, 48, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(582, 48, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(583, 48, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(584, 48, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(585, 48, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(586, 48, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(587, 48, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:08', '2026-08-21 19:20:08'),
(588, 48, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(589, 49, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(590, 49, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(591, 49, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(592, 49, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(593, 49, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(594, 49, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(595, 49, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(596, 49, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(597, 49, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(598, 49, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(599, 49, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(600, 49, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(601, 50, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(602, 50, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(603, 50, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(604, 50, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(605, 50, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(606, 50, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(607, 50, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(608, 50, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(609, 50, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(610, 50, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(611, 50, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(612, 50, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(613, 51, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(614, 51, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(615, 51, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(616, 51, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(617, 51, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(618, 51, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(619, 51, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(620, 51, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(621, 51, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(622, 51, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(623, 51, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(624, 51, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(625, 52, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(626, 52, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(627, 52, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(628, 52, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(629, 52, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:09', '2026-08-21 19:20:09'),
(630, 52, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(631, 52, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(632, 52, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(633, 52, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(634, 52, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(635, 52, 'wajib', 45000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(636, 52, 'dana_sosial', 5000.00, '2026-08', '2026-08-22', 2, '2026-08-21 19:20:10', '2026-08-21 19:20:10'),
(637, 17, 'wajib', 45000.00, '2026-09', '2026-08-22', 1, '2026-08-21 19:21:26', '2026-08-21 19:21:26'),
(638, 17, 'dana_sosial', 5000.00, '2026-09', '2026-08-22', 1, '2026-08-21 19:21:26', '2026-08-21 19:21:26'),
(639, 17, 'wajib', 45000.00, '2026-10', '2026-08-22', 1, '2026-08-21 19:21:32', '2026-08-21 19:21:32'),
(640, 17, 'dana_sosial', 5000.00, '2026-10', '2026-08-22', 1, '2026-08-21 19:21:32', '2026-08-21 19:21:32'),
(641, 17, 'wajib', 45000.00, '2026-11', '2026-08-22', 1, '2026-08-21 19:21:38', '2026-08-21 19:21:38'),
(642, 17, 'dana_sosial', 5000.00, '2026-11', '2026-08-22', 1, '2026-08-21 19:21:38', '2026-08-21 19:21:38'),
(643, 17, 'wajib', 45000.00, '2026-12', '2026-08-22', 1, '2026-08-21 19:21:42', '2026-08-21 19:21:42'),
(644, 17, 'dana_sosial', 5000.00, '2026-12', '2026-08-22', 1, '2026-08-21 19:21:42', '2026-08-21 19:21:42'),
(645, 17, 'wajib', 45000.00, '2027-01', '2026-08-22', 1, '2026-08-21 19:21:51', '2026-08-21 19:21:51'),
(646, 17, 'dana_sosial', 5000.00, '2027-01', '2026-08-22', 1, '2026-08-21 19:21:51', '2026-08-21 19:21:51');

-- --------------------------------------------------------

--
-- Table structure for table `tabel_tenor`
--

CREATE TABLE `tabel_tenor` (
  `id` bigint UNSIGNED NOT NULL,
  `nominal_min` decimal(15,2) NOT NULL,
  `nominal_max` decimal(15,2) NOT NULL,
  `tenor_maksimal_bulan` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tabel_tenor`
--

INSERT INTO `tabel_tenor` (`id`, `nominal_min`, `nominal_max`, `tenor_maksimal_bulan`, `created_at`, `updated_at`) VALUES
(1, 0.00, 1000000.00, 3, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(2, 1000001.00, 2000000.00, 4, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(3, 2000001.00, 3000000.00, 6, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(4, 3000001.00, 4000000.00, 9, '2026-08-21 19:19:54', '2026-08-21 19:19:54'),
(5, 4000001.00, 10000000.00, 12, '2026-08-21 19:19:54', '2026-08-21 19:19:54');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_karyawan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sso_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `auth_provider` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'local',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `harus_ganti_password` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `no_karyawan`, `sso_id`, `auth_provider`, `email_verified_at`, `password`, `harus_ganti_password`, `status`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin Koperasi', 'admin@koperasi.test', 'ADM-000001', NULL, 'local', NULL, '$2y$12$2sJ5T51ba2MeC/IdRci6v.317Q7ZKaGhVgr6cTfwLjZDpzKcx6jZa', 0, 'aktif', NULL, '2026-08-21 19:19:40', '2026-08-21 19:19:40'),
(2, 'Bendahara Koperasi', 'bendahara@koperasi.test', 'BEN-000001', NULL, 'local', NULL, '$2y$12$NRKQVmF273WukgtfceH2iOCcmh58BoZPr5iXqQ7oLGbPSYt42AJQe', 0, 'aktif', NULL, '2026-08-21 19:19:40', '2026-08-21 19:19:40'),
(3, 'Ketua Koperasi', 'ketua@koperasi.test', 'KET-000001', NULL, 'local', NULL, '$2y$12$y.LD399R6TDdcIPbImrWeemrbHGjmXno.qrO.qBqGGF5BJLmCOiZi', 0, 'aktif', NULL, '2026-08-21 19:19:41', '2026-08-21 19:19:41'),
(4, 'Anggota Baru', 'anggota.baru@koperasi.test', 'TOP-100001', NULL, 'local', NULL, '$2y$12$kVPjz1ugLD0Q5iQfYYHsPOdaY8l9xYqCxw30uOsAw6EbY8MJqBdNu', 0, 'aktif', NULL, '2026-08-21 19:19:41', '2026-08-21 19:19:41'),
(5, 'Anggota Sedang', 'anggota.sedang@koperasi.test', 'TOP-100002', NULL, 'local', NULL, '$2y$12$lNf25GsGJcA/JsaklCxSCujSQdFbK.oU.9448fuQ4nivBU.WJndKa', 0, 'aktif', NULL, '2026-08-21 19:19:41', '2026-08-21 19:19:41'),
(6, 'Anggota Lama', 'anggota.lama@koperasi.test', 'TOP-100003', NULL, 'local', NULL, '$2y$12$0RR1zDCAnrDfa9wb068A4uvofyqLwf5Oa77K1MK1/UUwQVXkzeA1G', 0, 'aktif', NULL, '2026-08-21 19:19:41', '2026-08-21 19:19:41'),
(7, 'Anggota Reloan', 'anggota.reloan@koperasi.test', 'TOP-100004', NULL, 'local', NULL, '$2y$12$iJmY9cZLkmFeC49d6F9.X.NDUzpAodR06R3TDKcfDfNueUlBWe8R6', 0, 'aktif', NULL, '2026-08-21 19:19:42', '2026-08-21 19:19:42'),
(8, 'Agus Wijaya', 'anggota.aguswijaya@koperasi.test', 'TOP-100005', NULL, 'local', NULL, '$2y$12$EvVtkgy04J6qGypeEfK/JupGM4is7lv7wQeGxo8lfhLDpWvWvyoKm', 0, 'aktif', NULL, '2026-08-21 19:19:42', '2026-08-21 19:19:42'),
(9, 'Rina Marlina', 'anggota.rinamarlina@koperasi.test', 'TOP-100006', NULL, 'local', NULL, '$2y$12$QC/rPzXVDLJbKZxn6cTaHe/hQWX19NDJFi8tSH6yQDUuuCkcI1K36', 0, 'aktif', NULL, '2026-08-21 19:19:42', '2026-08-21 19:19:42'),
(10, 'Bambang Sutrisno', 'anggota.bambangsutrisno@koperasi.test', 'TOP-100007', NULL, 'local', NULL, '$2y$12$gtw16QnztLgmpnOBA2lTbeXO.pa.2wqnfrqKVey/jfI1YTnw0PNka', 0, 'aktif', NULL, '2026-08-21 19:19:42', '2026-08-21 19:19:42'),
(11, 'Sari Rahayu', 'anggota.sarirahayu@koperasi.test', 'TOP-100008', NULL, 'local', NULL, '$2y$12$QbF5vU3Nl2brm07TOLiX8ehB7gVbKNpRTk8v8IjO.CA5kN.Pq7Wga', 0, 'aktif', NULL, '2026-08-21 19:19:43', '2026-08-21 19:19:43'),
(12, 'Hendra Gunawan', 'anggota.hendragunawan@koperasi.test', 'TOP-100009', NULL, 'local', NULL, '$2y$12$tbW9oBn.SLOYGNWL8eg9we5o6aXsQQLpzmtoQI0Z7aqmq1JEm8lqa', 0, 'aktif', NULL, '2026-08-21 19:19:43', '2026-08-21 19:19:43'),
(13, 'Dewi Anggraini', 'anggota.dewianggraini@koperasi.test', 'TOP-100010', NULL, 'local', NULL, '$2y$12$1EOhsCVKy8Hm7wiyvJbVju5VkcTtmfjeUY/QuTfqcUDNgT8B8az7u', 0, 'aktif', NULL, '2026-08-21 19:19:43', '2026-08-21 19:19:43'),
(14, 'Joko Susanto', 'anggota.jokosusanto@koperasi.test', 'TOP-100011', NULL, 'local', NULL, '$2y$12$65CQIyZxYnVU2BLui6t3mOjzkvza0VdzCFaIVfzH.Eq4rRkdBqKka', 0, 'aktif', NULL, '2026-08-21 19:19:43', '2026-08-21 19:19:43'),
(15, 'Maya Sari', 'anggota.mayasari@koperasi.test', 'TOP-100012', NULL, 'local', NULL, '$2y$12$YTifkxpAre7q4NrxVjD3HO1rIS7uOIyZL0sfm8XfSzV8o46hLVv4u', 0, 'aktif', NULL, '2026-08-21 19:19:44', '2026-08-21 19:19:44'),
(16, 'Adi Nugroho', 'anggota.adinugroho@koperasi.test', 'TOP-100013', NULL, 'local', NULL, '$2y$12$z4y.UvHycrc3meds4WpeKeA/RGxJDEE3lhxQtztA5RVV1boudZFwi', 0, 'aktif', NULL, '2026-08-21 19:19:44', '2026-08-21 19:19:44'),
(17, 'Lina Wijayanti', 'anggota.linawijayanti@koperasi.test', 'TOP-100014', NULL, 'local', NULL, '$2y$12$xxZ/5VQQEU2z9tveLPNFMuG4yukrQdX/OX7RMKqOjWHpBc5WB8Vti', 0, 'aktif', NULL, '2026-08-21 19:19:44', '2026-08-21 19:19:44'),
(18, 'Rizky Pratama', 'anggota.rizkypratama@koperasi.test', 'TOP-100015', NULL, 'local', NULL, '$2y$12$UvGCOQSI.8E2Bh4wLzqXm.CYGKqwc7hDGGXWxstIdPfxk2R3mLEEm', 0, 'aktif', NULL, '2026-08-21 19:19:44', '2026-08-21 19:19:44'),
(19, 'Nia Kurniawati', 'anggota.niakurniawati@koperasi.test', 'TOP-100016', NULL, 'local', NULL, '$2y$12$YUp46lzENP6D1jWWnSJLzujHQ1mr53cJCkJRrU4i3Je/pDMygueba', 0, 'aktif', NULL, '2026-08-21 19:19:45', '2026-08-21 19:19:45'),
(20, 'Eko Prasetyo', 'anggota.ekoprasetyo@koperasi.test', 'TOP-100017', NULL, 'local', NULL, '$2y$12$G8NJy6.4iipraEqGiblKceO.kjGHrAcbbyQsul9EOhXOAeqhMqEV6', 0, 'nonaktif', NULL, '2026-08-21 19:19:45', '2026-08-21 19:22:33'),
(21, 'Putri Handayani', 'anggota.putrihandayani@koperasi.test', 'TOP-100018', NULL, 'local', NULL, '$2y$12$s0DYkJ2EzDIIDkTeCN3pceyjcnEdnq/H9RpYYIqhoVe4b2YpbNPAG', 0, 'aktif', NULL, '2026-08-21 19:19:45', '2026-08-21 19:19:45'),
(22, 'Fajar Ramadhan', 'anggota.fajarramadhan@koperasi.test', 'TOP-100019', NULL, 'local', NULL, '$2y$12$mVuAnFQMkhEjxyfpPXI40.e8qaE4h1N6omixEYn0U0ZNPzd3C4JxS', 0, 'aktif', NULL, '2026-08-21 19:19:45', '2026-08-21 19:19:45'),
(23, 'Indah Permata', 'anggota.indahpermata@koperasi.test', 'TOP-100020', NULL, 'local', NULL, '$2y$12$I1ndOQSFmEMHpaCG/eK1VefFcg6YKtZLbiHS0hJE.fgkx7.KZPD9O', 0, 'aktif', NULL, '2026-08-21 19:19:46', '2026-08-21 19:19:46'),
(24, 'Yudha Pradana', 'anggota.yudhapradana@koperasi.test', 'TOP-100021', NULL, 'local', NULL, '$2y$12$3EziT/fHdwNM57JEA23TOe4U7BleOdRjgumT7KqF2TV4TKrfCZL5S', 0, 'aktif', NULL, '2026-08-21 19:19:46', '2026-08-21 19:19:46'),
(25, 'Sri Wahyuni', 'anggota.sriwahyuni@koperasi.test', 'TOP-100022', NULL, 'local', NULL, '$2y$12$g9QsP50N8epYeD0k0.ZPk.LveQOvjxpYYXMTW0RE5NaX00lZuRJKW', 0, 'aktif', NULL, '2026-08-21 19:19:46', '2026-08-21 19:19:46'),
(26, 'Andi Firmansyah', 'anggota.andifirmansyah@koperasi.test', 'TOP-100023', NULL, 'local', NULL, '$2y$12$kj.FDT07O2fsMPJFtDsQ8uPFQifkzBQ17hFyO1MaXU9ryymvFik/y', 0, 'aktif', NULL, '2026-08-21 19:19:47', '2026-08-21 19:19:47'),
(27, 'Ratna Sari', 'anggota.ratnasari@koperasi.test', 'TOP-100024', NULL, 'local', NULL, '$2y$12$t4VacPt0fWpUqd4T9SESqOXs7nmONptNvcA13I1unfSHB30YhJfbS', 0, 'aktif', NULL, '2026-08-21 19:19:47', '2026-08-21 19:19:47'),
(28, 'Deni Setiawan', 'anggota.denisetiawan@koperasi.test', 'TOP-100025', NULL, 'local', NULL, '$2y$12$Nb6YYhHk4Op2YLyvOv935eLc0uu4c8zA7H5uAErhe94tjtEkIBfsu', 0, 'aktif', NULL, '2026-08-21 19:19:47', '2026-08-21 19:19:47'),
(29, 'Fitriani', 'anggota.fitriani@koperasi.test', 'TOP-100026', NULL, 'local', NULL, '$2y$12$pLoRxFf8WPev2jANsBXoEecxdsXHrqrfem2WOfH.akElhS5eT2mLK', 0, 'aktif', NULL, '2026-08-21 19:19:47', '2026-08-21 19:19:47'),
(30, 'Rudi Hartono', 'anggota.rudihartono@koperasi.test', 'TOP-100027', NULL, 'local', NULL, '$2y$12$52fIKRZ1780Y5IN.UgD7u.3cHhqn77KN1nmnc2lDO7XFnbbSNRMAm', 0, 'aktif', NULL, '2026-08-21 19:19:48', '2026-08-21 19:19:48'),
(31, 'Susi Susanti', 'anggota.susisusanti@koperasi.test', 'TOP-100028', NULL, 'local', NULL, '$2y$12$rsnLmqlddEpRdgmdMD266e1588ducFNXnhSmxhXXvlRddQ0LZuwmK', 0, 'aktif', NULL, '2026-08-21 19:19:48', '2026-08-21 19:19:48'),
(32, 'Bayu Saputra', 'anggota.bayusaputra@koperasi.test', 'TOP-100029', NULL, 'local', NULL, '$2y$12$E8vquKgGY3Elui8H7ezUi.rcSNYefJeNz/jQ73UhZ.FnUDQdMLmSm', 0, 'aktif', NULL, '2026-08-21 19:19:48', '2026-08-21 19:19:48'),
(33, 'Ayu Lestari', 'anggota.ayulestari@koperasi.test', 'TOP-100030', NULL, 'local', NULL, '$2y$12$f2NjBczHTW294qAtind8ve9ZUgvUPgDEILsnLaHArWYlMmafc.8Ma', 0, 'aktif', NULL, '2026-08-21 19:19:48', '2026-08-21 19:19:48'),
(34, 'Toni Kurniawan', 'anggota.tonikurniawan@koperasi.test', 'TOP-100031', NULL, 'local', NULL, '$2y$12$UR6rbxHV1ja/37TnXEmIfOjwrJJVx4oZVhHWv1B8ldICG.Xnft3i6', 0, 'aktif', NULL, '2026-08-21 19:19:49', '2026-08-21 19:19:49'),
(35, 'Tuti Herawati', 'anggota.tutiherawati@koperasi.test', 'TOP-100032', NULL, 'local', NULL, '$2y$12$ii286a9rysoYvhD6NMG50uVzr5lPSzNp4lLC1c424xtdZEEEZgZom', 0, 'aktif', NULL, '2026-08-21 19:19:49', '2026-08-21 19:19:49'),
(36, 'Ferry Ardiansyah', 'anggota.ferryardiansyah@koperasi.test', 'TOP-100033', NULL, 'local', NULL, '$2y$12$w7WiYKn8OAN2x6cZmC18Qev0NLMqhe7ZjH7z7urLRsZgPLa7aUvE.', 0, 'aktif', NULL, '2026-08-21 19:19:49', '2026-08-21 19:19:49'),
(37, 'Desi Ratnasari', 'anggota.desiratnasari@koperasi.test', 'TOP-100034', NULL, 'local', NULL, '$2y$12$Y5MaLjoZA/XIFvmHFXRMe.d8c10H1knUTsw1cTrDP.cCarzaatSGO', 0, 'aktif', NULL, '2026-08-21 19:19:49', '2026-08-21 19:19:49'),
(38, 'Imam Santoso', 'anggota.imamsantoso@koperasi.test', 'TOP-100035', NULL, 'local', NULL, '$2y$12$VNnUFFxJg3Uq1dxC3zR47.gFLFhkmxFBTmfrwBlQQ06gcVzET2fii', 0, 'aktif', NULL, '2026-08-21 19:19:50', '2026-08-21 19:19:50'),
(39, 'Widya Astuti', 'anggota.widyaastuti@koperasi.test', 'TOP-100036', NULL, 'local', NULL, '$2y$12$gHqYbDBulTJks8cWwbjMnusGTMUpQkjykZvNjsoGlIDZwS55ylwm.', 0, 'aktif', NULL, '2026-08-21 19:19:50', '2026-08-21 19:19:50'),
(40, 'Galih Prakoso', 'anggota.galihprakoso@koperasi.test', 'TOP-100037', NULL, 'local', NULL, '$2y$12$qs6YCGHjVFQhtcnhJYwnPOkyYjgQODj/Y60VYli4e8yQE.yZQYqLG', 0, 'aktif', NULL, '2026-08-21 19:19:50', '2026-08-21 19:19:50'),
(41, 'Nur Aini', 'anggota.nuraini@koperasi.test', 'TOP-100038', NULL, 'local', NULL, '$2y$12$UnoDI/Wq6cwoIs3fzYqtSeMRSaubjR4DH5xxmr8ERh5dz7g5omfmS', 0, 'aktif', NULL, '2026-08-21 19:19:51', '2026-08-21 19:19:51'),
(42, 'Satria Bima', 'anggota.satriabima@koperasi.test', 'TOP-100039', NULL, 'local', NULL, '$2y$12$91.ikp.Q1iBwAJ9/mLL4yuATzemHRIdh4mOhZ/GXl5p6O8SUKAl0.', 0, 'aktif', NULL, '2026-08-21 19:19:51', '2026-08-21 19:19:51'),
(43, 'Laila Amalia', 'anggota.lailaamalia@koperasi.test', 'TOP-100040', NULL, 'local', NULL, '$2y$12$YcF3Fqce44nWzE6QTHaNo.SIXCVHIh46qwr79erGs4/DiOk.hyhAO', 0, 'aktif', NULL, '2026-08-21 19:19:51', '2026-08-21 19:19:51'),
(44, 'Wisnu Prasetyo', 'anggota.wisnuprasetyo@koperasi.test', 'TOP-100041', NULL, 'local', NULL, '$2y$12$mgtPrn3MSVc/oBn5KEuMUuBgmJIxaypkcJyuqAkMtzTTVIdmogQci', 0, 'aktif', NULL, '2026-08-21 19:19:51', '2026-08-21 19:19:51'),
(45, 'Mega Puspita', 'anggota.megapuspita@koperasi.test', 'TOP-100042', NULL, 'local', NULL, '$2y$12$wggHGZaKfyMSx152W.CbrO0AgelimNNi1w2W4KTxbMVbnYqtF91i.', 0, 'aktif', NULL, '2026-08-21 19:19:52', '2026-08-21 19:19:52'),
(46, 'Dimas Anggara', 'anggota.dimasanggara@koperasi.test', 'TOP-100043', NULL, 'local', NULL, '$2y$12$Z1p6g1x8xubm2/wyo/cMPOkqCLaCr6V0vGOFJ1G1STpBSjv3Pas2S', 0, 'aktif', NULL, '2026-08-21 19:19:52', '2026-08-21 19:19:52'),
(47, 'Nabila Putri', 'anggota.nabilaputri@koperasi.test', 'TOP-100044', NULL, 'local', NULL, '$2y$12$pjX8dgl0Y7jR50EVnSkXr.3dnzVjZuArVIdkuM73h2Y1gqCtMtZSS', 0, 'aktif', NULL, '2026-08-21 19:19:52', '2026-08-21 19:19:52'),
(48, 'Candra Wijaya', 'anggota.candrawijaya@koperasi.test', 'TOP-100045', NULL, 'local', NULL, '$2y$12$H9X8a5VNJEuS.DvibBspuuqXCyQ5TZYpT3VjtKdaJ/VDTaWo/4c2u', 0, 'aktif', NULL, '2026-08-21 19:19:52', '2026-08-21 19:19:52'),
(49, 'Yuni Astuti', 'anggota.yuniastuti@koperasi.test', 'TOP-100046', NULL, 'local', NULL, '$2y$12$Um00PSucSzGeZzoKfXg48.YUT36N4uYMeYbvvBqNYFhCyoTYssjk.', 0, 'aktif', NULL, '2026-08-21 19:19:53', '2026-08-21 19:19:53'),
(50, 'Arif Hidayat', 'anggota.arifhidayat@koperasi.test', 'TOP-100047', NULL, 'local', NULL, '$2y$12$LYYONpwb8oyae/4zFmvWVuWrTNWaYMacjIbSw5hKNMrpFLap.N8Ei', 0, 'aktif', NULL, '2026-08-21 19:19:53', '2026-08-21 19:19:53'),
(51, 'Rina Kusuma', 'anggota.rinakusuma@koperasi.test', 'TOP-100048', NULL, 'local', NULL, '$2y$12$b7xRBa9ukEP2biFVx/Q4Y.G6XzChhVKu7pIPoBEi2pH6T6fJXK0P6', 0, 'aktif', NULL, '2026-08-21 19:19:53', '2026-08-21 19:19:53'),
(52, 'Bagus Pamungkas', 'anggota.baguspamungkas@koperasi.test', 'TOP-100049', NULL, 'local', NULL, '$2y$12$a2z0TcFfKFRCfSrT0uOaveqHw5RZY1/dZAk6aReZpCdAubDUYgngi', 0, 'aktif', NULL, '2026-08-21 19:19:53', '2026-08-21 19:19:53'),
(53, 'Citra Ramadhani', 'anggota.citraramadhani@koperasi.test', 'TOP-100050', NULL, 'local', NULL, '$2y$12$JatP5TGJZJtpBnsDPFGV9eaAo2jkJ3Bh0reL9pgJxaHrbmoa6HAq2', 0, 'aktif', NULL, '2026-08-21 19:19:54', '2026-08-21 19:19:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `anggota`
--
ALTER TABLE `anggota`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `anggota_no_anggota_unique` (`no_anggota`),
  ADD UNIQUE KEY `anggota_no_karyawan_unique` (`no_karyawan`),
  ADD KEY `anggota_user_id_foreign` (`user_id`),
  ADD KEY `anggota_resigned_by_foreign` (`resigned_by`);

--
-- Indexes for table `angsuran`
--
ALTER TABLE `angsuran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `angsuran_pinjaman_id_foreign` (`pinjaman_id`),
  ADD KEY `angsuran_confirmed_by_foreign` (`confirmed_by`),
  ADD KEY `angsuran_pengajuan_percepatan_id_foreign` (`pengajuan_percepatan_id`);

--
-- Indexes for table `angsuran_percepatan`
--
ALTER TABLE `angsuran_percepatan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `angsuran_percepatan_pengajuan_percepatan_id_foreign` (`pengajuan_percepatan_id`),
  ADD KEY `angsuran_percepatan_confirmed_by_foreign` (`confirmed_by`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_log_user_id_foreign` (`user_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  ADD KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jurnal_kas`
--
ALTER TABLE `jurnal_kas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jurnal_kas_created_by_foreign` (`created_by`);

--
-- Indexes for table `kas_koperasi`
--
ALTER TABLE `kas_koperasi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `pengajuan_limit`
--
ALTER TABLE `pengajuan_limit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pengajuan_limit_anggota_id_foreign` (`anggota_id`);

--
-- Indexes for table `pengajuan_percepatan`
--
ALTER TABLE `pengajuan_percepatan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pengajuan_percepatan_pinjaman_id_foreign` (`pinjaman_id`);

--
-- Indexes for table `pengeluaran`
--
ALTER TABLE `pengeluaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pengeluaran_input_by_foreign` (`input_by`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `pinjaman`
--
ALTER TABLE `pinjaman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pinjaman_anggota_id_foreign` (`anggota_id`),
  ADD KEY `pinjaman_pengaju_user_id_foreign` (`pengaju_user_id`);

--
-- Indexes for table `rekening_anggota`
--
ALTER TABLE `rekening_anggota`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rekening_anggota_anggota_id_foreign` (`anggota_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `setting_bunga`
--
ALTER TABLE `setting_bunga`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `setting_limit_pinjaman`
--
ALTER TABLE `setting_limit_pinjaman`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `setting_simpanan`
--
ALTER TABLE `setting_simpanan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_simpanan_jenis_unique` (`jenis`);

--
-- Indexes for table `simpanan`
--
ALTER TABLE `simpanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `simpanan_anggota_id_foreign` (`anggota_id`),
  ADD KEY `simpanan_input_by_foreign` (`input_by`);

--
-- Indexes for table `tabel_tenor`
--
ALTER TABLE `tabel_tenor`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_no_karyawan_unique` (`no_karyawan`),
  ADD UNIQUE KEY `users_sso_id_unique` (`sso_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `anggota`
--
ALTER TABLE `anggota`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `angsuran`
--
ALTER TABLE `angsuran`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `angsuran_percepatan`
--
ALTER TABLE `angsuran_percepatan`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jurnal_kas`
--
ALTER TABLE `jurnal_kas`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=665;

--
-- AUTO_INCREMENT for table `kas_koperasi`
--
ALTER TABLE `kas_koperasi`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `pengajuan_limit`
--
ALTER TABLE `pengajuan_limit`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pengajuan_percepatan`
--
ALTER TABLE `pengajuan_percepatan`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pengeluaran`
--
ALTER TABLE `pengeluaran`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `pinjaman`
--
ALTER TABLE `pinjaman`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `rekening_anggota`
--
ALTER TABLE `rekening_anggota`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `setting_bunga`
--
ALTER TABLE `setting_bunga`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `setting_limit_pinjaman`
--
ALTER TABLE `setting_limit_pinjaman`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `setting_simpanan`
--
ALTER TABLE `setting_simpanan`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `simpanan`
--
ALTER TABLE `simpanan`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=647;

--
-- AUTO_INCREMENT for table `tabel_tenor`
--
ALTER TABLE `tabel_tenor`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `anggota`
--
ALTER TABLE `anggota`
  ADD CONSTRAINT `anggota_resigned_by_foreign` FOREIGN KEY (`resigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `anggota_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `angsuran`
--
ALTER TABLE `angsuran`
  ADD CONSTRAINT `angsuran_confirmed_by_foreign` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `angsuran_pengajuan_percepatan_id_foreign` FOREIGN KEY (`pengajuan_percepatan_id`) REFERENCES `pengajuan_percepatan` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `angsuran_pinjaman_id_foreign` FOREIGN KEY (`pinjaman_id`) REFERENCES `pinjaman` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `angsuran_percepatan`
--
ALTER TABLE `angsuran_percepatan`
  ADD CONSTRAINT `angsuran_percepatan_confirmed_by_foreign` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `angsuran_percepatan_pengajuan_percepatan_id_foreign` FOREIGN KEY (`pengajuan_percepatan_id`) REFERENCES `pengajuan_percepatan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `jurnal_kas`
--
ALTER TABLE `jurnal_kas`
  ADD CONSTRAINT `jurnal_kas_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pengajuan_limit`
--
ALTER TABLE `pengajuan_limit`
  ADD CONSTRAINT `pengajuan_limit_anggota_id_foreign` FOREIGN KEY (`anggota_id`) REFERENCES `anggota` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pengajuan_percepatan`
--
ALTER TABLE `pengajuan_percepatan`
  ADD CONSTRAINT `pengajuan_percepatan_pinjaman_id_foreign` FOREIGN KEY (`pinjaman_id`) REFERENCES `pinjaman` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pengeluaran`
--
ALTER TABLE `pengeluaran`
  ADD CONSTRAINT `pengeluaran_input_by_foreign` FOREIGN KEY (`input_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `pinjaman`
--
ALTER TABLE `pinjaman`
  ADD CONSTRAINT `pinjaman_anggota_id_foreign` FOREIGN KEY (`anggota_id`) REFERENCES `anggota` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pinjaman_pengaju_user_id_foreign` FOREIGN KEY (`pengaju_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `rekening_anggota`
--
ALTER TABLE `rekening_anggota`
  ADD CONSTRAINT `rekening_anggota_anggota_id_foreign` FOREIGN KEY (`anggota_id`) REFERENCES `anggota` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `simpanan`
--
ALTER TABLE `simpanan`
  ADD CONSTRAINT `simpanan_anggota_id_foreign` FOREIGN KEY (`anggota_id`) REFERENCES `anggota` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `simpanan_input_by_foreign` FOREIGN KEY (`input_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
