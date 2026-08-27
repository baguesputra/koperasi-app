-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 13, 2026 at 01:15 AM
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
  `status` enum('aktif','nonaktif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `limit_custom` decimal(15,2) DEFAULT NULL,
  `limit_custom_keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `anggota`
--

INSERT INTO `anggota` (`id`, `user_id`, `no_anggota`, `no_karyawan`, `no_ktp`, `nama`, `cabang`, `unit_bisnis`, `department`, `divisi`, `jabatan`, `tanggal_mulai_kerja`, `tanggal_jadi_anggota`, `status`, `limit_custom`, `limit_custom_keterangan`, `created_at`, `updated_at`) VALUES
(1, 4, 'ANG-2026-0001', 'TOP-100001', NULL, 'Budi Santoso', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'staff', '2025-11-12', '2026-02-12', 'aktif', NULL, NULL, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(2, 5, 'ANG-2023-0045', 'TOP-100002', NULL, 'Siti Aminah', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'hod', '2023-06-12', '2023-08-12', 'aktif', NULL, NULL, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(3, 6, 'ANG-2019-0012', 'TOP-100003', NULL, 'Ahmad Ridwan', 'Palangka', 'Operasional', 'Operasional', 'Gudang', 'staff', '2019-08-12', '2020-08-12', 'aktif', NULL, NULL, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(4, 7, 'ANG-2018-0003', 'TOP-100004', NULL, 'Dewi Lestari', 'Banjarmasin', 'Marketing', 'Marketing', 'Promosi', 'hod', '2018-08-12', '2019-08-12', 'aktif', NULL, NULL, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(5, 8, 'ANG-2026-0002', 'TOP-100005', '3207000000000000', 'Agus Wijaya', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2026-01-12', '2026-04-12', 'aktif', NULL, NULL, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(6, 9, 'ANG-2026-0003', 'TOP-100006', '3207000000000001', 'Rina Marlina', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2024-03-12', '2024-07-12', 'aktif', NULL, NULL, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(7, 10, 'ANG-2026-0004', 'TOP-100007', '3207000000000002', 'Bambang Sutrisno', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2023-01-12', '2023-06-12', 'aktif', NULL, NULL, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(8, 11, 'ANG-2026-0005', 'TOP-100008', '3207000000000003', 'Sari Rahayu', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2017-11-12', '2018-05-12', 'aktif', NULL, NULL, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(9, 12, 'ANG-2026-0006', 'TOP-100009', '3207000000000004', 'Hendra Gunawan', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2025-05-12', '2025-12-12', 'aktif', NULL, NULL, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(10, 13, 'ANG-2026-0007', 'TOP-100010', '3207000000000005', 'Dewi Anggraini', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2023-07-12', '2024-03-12', 'aktif', NULL, NULL, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(11, 14, 'ANG-2026-0008', 'TOP-100011', '3207000000000006', 'Joko Susanto', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2022-05-12', '2023-02-12', 'aktif', NULL, NULL, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(12, 15, 'ANG-2026-0009', 'TOP-100012', '3207000000000007', 'Maya Sari', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2019-08-12', '2020-06-12', 'aktif', NULL, NULL, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(13, 16, 'ANG-2026-0010', 'TOP-100013', '3207000000000008', 'Adi Nugroho', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2025-10-12', '2026-01-12', 'nonaktif', NULL, NULL, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(14, 17, 'ANG-2026-0011', 'TOP-100014', '3207000000000009', 'Lina Wijayanti', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2023-07-12', '2023-11-12', 'aktif', NULL, NULL, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(15, 18, 'ANG-2026-0012', 'TOP-100015', '3207000000000010', 'Rizky Pratama', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2022-12-12', '2023-05-12', 'aktif', NULL, NULL, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(16, 19, 'ANG-2026-0013', 'TOP-100016', '3207000000000011', 'Nia Kurniawati', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2016-01-12', '2016-07-12', 'aktif', NULL, NULL, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(17, 20, 'ANG-2026-0014', 'TOP-100017', '3207000000000012', 'Eko Prasetyo', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2025-07-12', '2026-02-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(18, 21, 'ANG-2026-0015', 'TOP-100018', '3207000000000013', 'Putri Handayani', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2023-10-12', '2024-06-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(19, 22, 'ANG-2026-0016', 'TOP-100019', '3207000000000014', 'Fajar Ramadhan', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2022-11-12', '2023-08-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(20, 23, 'ANG-2026-0017', 'TOP-100020', '3207000000000015', 'Indah Permata', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2017-10-12', '2018-08-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(21, 24, 'ANG-2026-0018', 'TOP-100021', '3207000000000016', 'Yudha Pradana', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2025-12-12', '2026-03-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(22, 25, 'ANG-2026-0019', 'TOP-100022', '3207000000000017', 'Sri Wahyuni', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2023-10-12', '2024-02-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(23, 26, 'ANG-2026-0020', 'TOP-100023', '3207000000000018', 'Andi Firmansyah', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2022-11-12', '2023-04-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(24, 27, 'ANG-2026-0021', 'TOP-100024', '3207000000000019', 'Ratna Sari', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2019-10-12', '2020-04-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(25, 28, 'ANG-2026-0022', 'TOP-100025', '3207000000000020', 'Deni Setiawan', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2025-09-12', '2026-04-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(26, 29, 'ANG-2026-0023', 'TOP-100026', '3207000000000021', 'Fitriani', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2023-02-12', '2023-10-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(27, 30, 'ANG-2026-0024', 'TOP-100027', '3207000000000022', 'Rudi Hartono', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2022-10-12', '2023-07-12', 'nonaktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(28, 31, 'ANG-2026-0025', 'TOP-100028', '3207000000000023', 'Susi Susanti', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2015-07-12', '2016-05-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(29, 32, 'ANG-2026-0026', 'TOP-100029', '3207000000000024', 'Bayu Saputra', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2025-09-12', '2025-12-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(30, 33, 'ANG-2026-0027', 'TOP-100030', '3207000000000025', 'Ayu Lestari', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2024-01-12', '2024-05-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(31, 34, 'ANG-2026-0028', 'TOP-100031', '3207000000000026', 'Toni Kurniawan', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2022-10-12', '2023-03-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(32, 35, 'ANG-2026-0029', 'TOP-100032', '3207000000000027', 'Tuti Herawati', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2017-12-12', '2018-06-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(33, 36, 'ANG-2026-0030', 'TOP-100033', '3207000000000028', 'Ferry Ardiansyah', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2025-06-12', '2026-01-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(34, 37, 'ANG-2026-0031', 'TOP-100034', '3207000000000029', 'Desi Ratnasari', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2023-05-12', '2024-01-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(35, 38, 'ANG-2026-0032', 'TOP-100035', '3207000000000030', 'Imam Santoso', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2022-09-12', '2023-06-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(36, 39, 'ANG-2026-0033', 'TOP-100036', '3207000000000031', 'Widya Astuti', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2019-09-12', '2020-07-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(37, 40, 'ANG-2026-0034', 'TOP-100037', '3207000000000032', 'Galih Prakoso', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2025-11-12', '2026-02-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(38, 41, 'ANG-2026-0035', 'TOP-100038', '3207000000000033', 'Nur Aini', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2024-04-12', '2024-08-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(39, 42, 'ANG-2026-0036', 'TOP-100039', '3207000000000034', 'Satria Bima', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2022-09-12', '2023-02-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(40, 43, 'ANG-2026-0037', 'TOP-100040', '3207000000000035', 'Laila Amalia', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2016-02-12', '2016-08-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(41, 44, 'ANG-2026-0038', 'TOP-100041', '3207000000000036', 'Wisnu Prasetyo', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2025-08-12', '2026-03-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(42, 45, 'ANG-2026-0039', 'TOP-100042', '3207000000000037', 'Mega Puspita', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2023-08-12', '2024-04-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(43, 46, 'ANG-2026-0040', 'TOP-100043', '3207000000000038', 'Dimas Anggara', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2022-08-12', '2023-05-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(44, 47, 'ANG-2026-0041', 'TOP-100044', '3207000000000039', 'Nabila Putri', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2017-06-12', '2018-04-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(45, 48, 'ANG-2026-0042', 'TOP-100045', '3207000000000040', 'Candra Wijaya', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2026-01-12', '2026-04-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(46, 49, 'ANG-2026-0043', 'TOP-100046', '3207000000000041', 'Yuni Astuti', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2023-08-12', '2023-12-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(47, 50, 'ANG-2026-0044', 'TOP-100047', '3207000000000042', 'Arif Hidayat', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2023-03-12', '2023-08-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(48, 51, 'ANG-2026-0045', 'TOP-100048', '3207000000000043', 'Rina Kusuma', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2019-11-12', '2020-05-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(49, 52, 'ANG-2026-0046', 'TOP-100049', '3207000000000044', 'Bagus Pamungkas', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2025-05-12', '2025-12-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(50, 53, 'ANG-2026-0047', 'TOP-100050', '3207000000000045', 'Citra Ramadhani', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2023-11-12', '2024-07-12', 'aktif', NULL, NULL, '2026-08-11 21:14:35', '2026-08-11 21:14:35');

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
  `status` enum('belum_bayar','lunas') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'belum_bayar',
  `tanggal_jatuh_tempo` date NOT NULL,
  `tanggal_konfirmasi_bayar` date DEFAULT NULL,
  `confirmed_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `angsuran`
--

INSERT INTO `angsuran` (`id`, `pinjaman_id`, `cicilan_ke`, `nominal_pokok`, `nominal_bunga`, `total_bayar`, `status`, `tanggal_jatuh_tempo`, `tanggal_konfirmasi_bayar`, `confirmed_by`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 500000.00, 20000.00, 520000.00, 'lunas', '2026-06-15', '2026-06-15', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(2, 2, 2, 500000.00, 15000.00, 515000.00, 'lunas', '2026-07-15', '2026-07-15', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(3, 2, 3, 500000.00, 10000.00, 510000.00, 'belum_bayar', '2026-08-15', NULL, NULL, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(4, 2, 4, 500000.00, 5000.00, 505000.00, 'belum_bayar', '2026-09-15', NULL, NULL, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(5, 3, 1, 500000.00, 30000.00, 530000.00, 'lunas', '2026-01-15', '2026-01-15', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(6, 3, 2, 500000.00, 25000.00, 525000.00, 'lunas', '2026-02-15', '2026-02-15', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(7, 3, 3, 500000.00, 20000.00, 520000.00, 'lunas', '2026-03-15', '2026-03-15', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(8, 3, 4, 500000.00, 15000.00, 515000.00, 'lunas', '2026-04-15', '2026-04-15', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(9, 3, 5, 500000.00, 10000.00, 510000.00, 'lunas', '2026-05-15', '2026-05-15', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(10, 3, 6, 500000.00, 5000.00, 505000.00, 'lunas', '2026-06-15', '2026-06-15', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(11, 4, 1, 416666.67, 50000.00, 466666.67, 'lunas', '2025-11-15', '2025-11-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(12, 4, 2, 416666.67, 45833.33, 462500.00, 'lunas', '2025-12-15', '2025-12-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(13, 4, 3, 416666.67, 41666.67, 458333.33, 'lunas', '2026-01-15', '2026-01-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(14, 4, 4, 416666.67, 37500.00, 454166.67, 'lunas', '2026-02-15', '2026-02-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(15, 4, 5, 416666.67, 33333.33, 450000.00, 'lunas', '2026-03-15', '2026-03-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(16, 4, 6, 416666.67, 29166.67, 445833.33, 'lunas', '2026-04-15', '2026-04-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(17, 4, 7, 416666.67, 25000.00, 441666.67, 'lunas', '2026-05-15', '2026-05-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(18, 4, 8, 416666.67, 20833.33, 437500.00, 'lunas', '2026-06-15', '2026-06-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(19, 4, 9, 416666.67, 16666.67, 433333.33, 'lunas', '2026-07-15', '2026-07-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(20, 4, 10, 416666.67, 12500.00, 429166.67, 'lunas', '2026-08-15', '2026-08-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(21, 4, 11, 416666.67, 8333.33, 425000.00, 'belum_bayar', '2026-09-15', NULL, NULL, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(22, 4, 12, 416666.67, 4166.67, 420833.33, 'belum_bayar', '2026-10-15', NULL, NULL, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(23, 11, 1, 333333.33, 10000.00, 343333.33, 'lunas', '2026-07-15', '2026-07-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(24, 11, 2, 333333.33, 6666.67, 340000.00, 'belum_bayar', '2026-08-15', NULL, NULL, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(25, 11, 3, 333333.33, 3333.33, 336666.67, 'belum_bayar', '2026-09-15', NULL, NULL, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(26, 12, 1, 500000.00, 20000.00, 520000.00, 'lunas', '2026-06-15', '2026-06-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(27, 12, 2, 500000.00, 15000.00, 515000.00, 'lunas', '2026-07-15', '2026-07-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(28, 12, 3, 500000.00, 10000.00, 510000.00, 'belum_bayar', '2026-08-15', NULL, NULL, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(29, 12, 4, 500000.00, 5000.00, 505000.00, 'belum_bayar', '2026-09-15', NULL, NULL, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(30, 13, 1, 500000.00, 30000.00, 530000.00, 'lunas', '2026-04-15', '2026-04-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(31, 13, 2, 500000.00, 25000.00, 525000.00, 'lunas', '2026-05-15', '2026-05-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(32, 13, 3, 500000.00, 20000.00, 520000.00, 'lunas', '2026-06-15', '2026-06-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(33, 13, 4, 500000.00, 15000.00, 515000.00, 'belum_bayar', '2026-07-15', NULL, NULL, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(34, 13, 5, 500000.00, 10000.00, 510000.00, 'belum_bayar', '2026-08-15', NULL, NULL, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(35, 13, 6, 500000.00, 5000.00, 505000.00, 'belum_bayar', '2026-09-15', NULL, NULL, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(36, 14, 1, 444444.44, 40000.00, 484444.44, 'lunas', '2025-11-15', '2025-11-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(37, 14, 2, 444444.44, 35555.56, 480000.00, 'lunas', '2025-12-15', '2025-12-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(38, 14, 3, 444444.44, 31111.11, 475555.56, 'lunas', '2026-01-15', '2026-01-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(39, 14, 4, 444444.44, 26666.67, 471111.11, 'lunas', '2026-02-15', '2026-02-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(40, 14, 5, 444444.44, 22222.22, 466666.67, 'lunas', '2026-03-15', '2026-03-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(41, 14, 6, 444444.44, 17777.78, 462222.22, 'lunas', '2026-04-15', '2026-04-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(42, 14, 7, 444444.44, 13333.33, 457777.78, 'lunas', '2026-05-15', '2026-05-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(43, 14, 8, 444444.44, 8888.89, 453333.33, 'lunas', '2026-06-15', '2026-06-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(44, 14, 9, 444444.44, 4444.44, 448888.89, 'lunas', '2026-07-15', '2026-07-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(45, 15, 1, 500000.00, 60000.00, 560000.00, 'lunas', '2025-07-15', '2025-07-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(46, 15, 2, 500000.00, 55000.00, 555000.00, 'lunas', '2025-08-15', '2025-08-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(47, 15, 3, 500000.00, 50000.00, 550000.00, 'lunas', '2025-09-15', '2025-09-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(48, 15, 4, 500000.00, 45000.00, 545000.00, 'lunas', '2025-10-15', '2025-10-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(49, 15, 5, 500000.00, 40000.00, 540000.00, 'lunas', '2025-11-15', '2025-11-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(50, 15, 6, 500000.00, 35000.00, 535000.00, 'lunas', '2025-12-15', '2025-12-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(51, 15, 7, 500000.00, 30000.00, 530000.00, 'lunas', '2026-01-15', '2026-01-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(52, 15, 8, 500000.00, 25000.00, 525000.00, 'lunas', '2026-02-15', '2026-02-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(53, 15, 9, 500000.00, 20000.00, 520000.00, 'lunas', '2026-03-15', '2026-03-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(54, 15, 10, 500000.00, 15000.00, 515000.00, 'lunas', '2026-04-15', '2026-04-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(55, 15, 11, 500000.00, 10000.00, 510000.00, 'lunas', '2026-05-15', '2026-05-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(56, 15, 12, 500000.00, 5000.00, 505000.00, 'lunas', '2026-06-15', '2026-06-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(57, 16, 1, 416666.67, 25000.00, 441666.67, 'lunas', '2026-01-15', '2026-01-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(58, 16, 2, 416666.67, 20833.33, 437500.00, 'lunas', '2026-02-15', '2026-02-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(59, 16, 3, 416666.67, 16666.67, 433333.33, 'lunas', '2026-03-15', '2026-03-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(60, 16, 4, 416666.67, 12500.00, 429166.67, 'lunas', '2026-04-15', '2026-04-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(61, 16, 5, 416666.67, 8333.33, 425000.00, 'lunas', '2026-05-15', '2026-05-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(62, 16, 6, 416666.67, 4166.67, 420833.33, 'lunas', '2026-06-15', '2026-06-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43');

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
(1, 1, 'update_permission_role', 'Hak akses role \'admin\' diperbarui.', '{\"permissions\": [\"anggota.lihat\", \"anggota.kelola\", \"simpanan.lihat\", \"pinjaman.lihat\", \"kas.lihat\", \"laporan.lihat\", \"pengaturan.kelola\"]}', '{\"permissions\": [\"anggota.lihat\", \"anggota.kelola\", \"simpanan.lihat\", \"pinjaman.lihat\", \"kas.lihat\", \"laporan.lihat\", \"pengaturan.kelola\", \"angsuran.konfirmasi\", \"kas.topup\", \"pinjaman.approve-ketua\", \"pinjaman.tinjau-bendahara\", \"portal.akses\", \"simpanan.konfirmasi\"]}', '2026-08-11 21:16:52', '2026-08-11 21:16:52'),
(2, 1, 'update_permission_role', 'Hak akses role \'admin\' diperbarui.', '{\"permissions\": [\"anggota.lihat\", \"anggota.kelola\", \"simpanan.lihat\", \"simpanan.konfirmasi\", \"pinjaman.lihat\", \"pinjaman.tinjau-bendahara\", \"pinjaman.approve-ketua\", \"angsuran.konfirmasi\", \"kas.lihat\", \"kas.topup\", \"laporan.lihat\", \"pengaturan.kelola\", \"portal.akses\"]}', '{\"permissions\": [\"anggota.lihat\", \"simpanan.lihat\", \"simpanan.konfirmasi\", \"pinjaman.lihat\", \"pinjaman.tinjau-bendahara\", \"pinjaman.approve-ketua\", \"angsuran.konfirmasi\", \"kas.lihat\", \"kas.topup\", \"laporan.lihat\", \"pengaturan.kelola\", \"portal.akses\"]}', '2026-08-11 22:13:21', '2026-08-11 22:13:21');

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
('laravel-cache-spatie.permission.cache', 'a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:13:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:13:\"anggota.lihat\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:14:\"anggota.kelola\";s:1:\"c\";s:3:\"web\";}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:14:\"simpanan.lihat\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:19:\"simpanan.konfirmasi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:14:\"pinjaman.lihat\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:25:\"pinjaman.tinjau-bendahara\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:22:\"pinjaman.approve-ketua\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:19:\"angsuran.konfirmasi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:9:\"kas.lihat\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:9:\"kas.topup\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:13:\"laporan.lihat\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:17:\"pengaturan.kelola\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:12:\"portal.akses\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}}s:5:\"roles\";a:4:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:5:\"admin\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:9:\"bendahara\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:14:\"ketua_koperasi\";s:1:\"c\";s:3:\"web\";}i:3;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:7:\"anggota\";s:1:\"c\";s:3:\"web\";}}}', 1786601602);

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
  `kategori` enum('topup_bulanan','pencairan_pinjaman','pembayaran_angsuran') COLLATE utf8mb4_unicode_ci NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `referensi_id` bigint UNSIGNED DEFAULT NULL,
  `tanggal` date NOT NULL,
  `created_by` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jurnal_kas`
--

INSERT INTO `jurnal_kas` (`id`, `tipe`, `kategori`, `jumlah`, `keterangan`, `referensi_id`, `tanggal`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'keluar', 'pencairan_pinjaman', 2000000.00, 'Pencairan pinjaman - Siti Aminah', 2, '2026-05-15', 3, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(2, 'masuk', 'pembayaran_angsuran', 520000.00, 'Angsuran ke-1 - Siti Aminah', 1, '2026-06-15', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(3, 'masuk', 'pembayaran_angsuran', 515000.00, 'Angsuran ke-2 - Siti Aminah', 2, '2026-07-15', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(4, 'keluar', 'pencairan_pinjaman', 3000000.00, 'Pencairan pinjaman - Ahmad Ridwan', 3, '2025-12-15', 3, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(5, 'masuk', 'pembayaran_angsuran', 530000.00, 'Angsuran ke-1 - Ahmad Ridwan', 5, '2026-01-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(6, 'masuk', 'pembayaran_angsuran', 525000.00, 'Angsuran ke-2 - Ahmad Ridwan', 6, '2026-02-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(7, 'masuk', 'pembayaran_angsuran', 520000.00, 'Angsuran ke-3 - Ahmad Ridwan', 7, '2026-03-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(8, 'masuk', 'pembayaran_angsuran', 515000.00, 'Angsuran ke-4 - Ahmad Ridwan', 8, '2026-04-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(9, 'masuk', 'pembayaran_angsuran', 510000.00, 'Angsuran ke-5 - Ahmad Ridwan', 9, '2026-05-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(10, 'masuk', 'pembayaran_angsuran', 505000.00, 'Angsuran ke-6 - Ahmad Ridwan', 10, '2026-06-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(11, 'keluar', 'pencairan_pinjaman', 5000000.00, 'Pencairan pinjaman - Dewi Lestari', 4, '2025-10-15', 3, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(12, 'masuk', 'pembayaran_angsuran', 466666.67, 'Angsuran ke-1 - Dewi Lestari', 11, '2025-11-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(13, 'masuk', 'pembayaran_angsuran', 462500.00, 'Angsuran ke-2 - Dewi Lestari', 12, '2025-12-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(14, 'masuk', 'pembayaran_angsuran', 458333.33, 'Angsuran ke-3 - Dewi Lestari', 13, '2026-01-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(15, 'masuk', 'pembayaran_angsuran', 454166.67, 'Angsuran ke-4 - Dewi Lestari', 14, '2026-02-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(16, 'masuk', 'pembayaran_angsuran', 450000.00, 'Angsuran ke-5 - Dewi Lestari', 15, '2026-03-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(17, 'masuk', 'pembayaran_angsuran', 445833.33, 'Angsuran ke-6 - Dewi Lestari', 16, '2026-04-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(18, 'masuk', 'pembayaran_angsuran', 441666.67, 'Angsuran ke-7 - Dewi Lestari', 17, '2026-05-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(19, 'masuk', 'pembayaran_angsuran', 437500.00, 'Angsuran ke-8 - Dewi Lestari', 18, '2026-06-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(20, 'masuk', 'pembayaran_angsuran', 433333.33, 'Angsuran ke-9 - Dewi Lestari', 19, '2026-07-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(21, 'masuk', 'pembayaran_angsuran', 429166.67, 'Angsuran ke-10 - Dewi Lestari', 20, '2026-08-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(22, 'keluar', 'pencairan_pinjaman', 1000000.00, 'Pencairan pinjaman - Bambang Sutrisno', 11, '2026-06-15', 3, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(23, 'masuk', 'pembayaran_angsuran', 343333.33, 'Angsuran ke-1 - Bambang Sutrisno', 23, '2026-07-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(24, 'keluar', 'pencairan_pinjaman', 2000000.00, 'Pencairan pinjaman - Eko Prasetyo', 12, '2026-05-15', 3, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(25, 'masuk', 'pembayaran_angsuran', 520000.00, 'Angsuran ke-1 - Eko Prasetyo', 26, '2026-06-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(26, 'masuk', 'pembayaran_angsuran', 515000.00, 'Angsuran ke-2 - Eko Prasetyo', 27, '2026-07-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(27, 'keluar', 'pencairan_pinjaman', 3000000.00, 'Pencairan pinjaman - Dewi Anggraini', 13, '2026-03-15', 3, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(28, 'masuk', 'pembayaran_angsuran', 530000.00, 'Angsuran ke-1 - Dewi Anggraini', 30, '2026-04-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(29, 'masuk', 'pembayaran_angsuran', 525000.00, 'Angsuran ke-2 - Dewi Anggraini', 31, '2026-05-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(30, 'masuk', 'pembayaran_angsuran', 520000.00, 'Angsuran ke-3 - Dewi Anggraini', 32, '2026-06-15', 2, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(31, 'keluar', 'pencairan_pinjaman', 4000000.00, 'Pencairan pinjaman - Ayu Lestari', 14, '2025-10-15', 3, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(32, 'masuk', 'pembayaran_angsuran', 484444.44, 'Angsuran ke-1 - Ayu Lestari', 36, '2025-11-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(33, 'masuk', 'pembayaran_angsuran', 480000.00, 'Angsuran ke-2 - Ayu Lestari', 37, '2025-12-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(34, 'masuk', 'pembayaran_angsuran', 475555.56, 'Angsuran ke-3 - Ayu Lestari', 38, '2026-01-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(35, 'masuk', 'pembayaran_angsuran', 471111.11, 'Angsuran ke-4 - Ayu Lestari', 39, '2026-02-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(36, 'masuk', 'pembayaran_angsuran', 466666.67, 'Angsuran ke-5 - Ayu Lestari', 40, '2026-03-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(37, 'masuk', 'pembayaran_angsuran', 462222.22, 'Angsuran ke-6 - Ayu Lestari', 41, '2026-04-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(38, 'masuk', 'pembayaran_angsuran', 457777.78, 'Angsuran ke-7 - Ayu Lestari', 42, '2026-05-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(39, 'masuk', 'pembayaran_angsuran', 453333.33, 'Angsuran ke-8 - Ayu Lestari', 43, '2026-06-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(40, 'masuk', 'pembayaran_angsuran', 448888.89, 'Angsuran ke-9 - Ayu Lestari', 44, '2026-07-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(41, 'keluar', 'pencairan_pinjaman', 6000000.00, 'Pencairan pinjaman - Laila Amalia', 15, '2025-06-15', 3, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(42, 'masuk', 'pembayaran_angsuran', 560000.00, 'Angsuran ke-1 - Laila Amalia', 45, '2025-07-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(43, 'masuk', 'pembayaran_angsuran', 555000.00, 'Angsuran ke-2 - Laila Amalia', 46, '2025-08-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(44, 'masuk', 'pembayaran_angsuran', 550000.00, 'Angsuran ke-3 - Laila Amalia', 47, '2025-09-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(45, 'masuk', 'pembayaran_angsuran', 545000.00, 'Angsuran ke-4 - Laila Amalia', 48, '2025-10-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(46, 'masuk', 'pembayaran_angsuran', 540000.00, 'Angsuran ke-5 - Laila Amalia', 49, '2025-11-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(47, 'masuk', 'pembayaran_angsuran', 535000.00, 'Angsuran ke-6 - Laila Amalia', 50, '2025-12-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(48, 'masuk', 'pembayaran_angsuran', 530000.00, 'Angsuran ke-7 - Laila Amalia', 51, '2026-01-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(49, 'masuk', 'pembayaran_angsuran', 525000.00, 'Angsuran ke-8 - Laila Amalia', 52, '2026-02-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(50, 'masuk', 'pembayaran_angsuran', 520000.00, 'Angsuran ke-9 - Laila Amalia', 53, '2026-03-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(51, 'masuk', 'pembayaran_angsuran', 515000.00, 'Angsuran ke-10 - Laila Amalia', 54, '2026-04-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(52, 'masuk', 'pembayaran_angsuran', 510000.00, 'Angsuran ke-11 - Laila Amalia', 55, '2026-05-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(53, 'masuk', 'pembayaran_angsuran', 505000.00, 'Angsuran ke-12 - Laila Amalia', 56, '2026-06-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(54, 'keluar', 'pencairan_pinjaman', 2500000.00, 'Pencairan pinjaman - Citra Ramadhani', 16, '2025-12-15', 3, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(55, 'masuk', 'pembayaran_angsuran', 441666.67, 'Angsuran ke-1 - Citra Ramadhani', 57, '2026-01-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(56, 'masuk', 'pembayaran_angsuran', 437500.00, 'Angsuran ke-2 - Citra Ramadhani', 58, '2026-02-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(57, 'masuk', 'pembayaran_angsuran', 433333.33, 'Angsuran ke-3 - Citra Ramadhani', 59, '2026-03-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(58, 'masuk', 'pembayaran_angsuran', 429166.67, 'Angsuran ke-4 - Citra Ramadhani', 60, '2026-04-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(59, 'masuk', 'pembayaran_angsuran', 425000.00, 'Angsuran ke-5 - Citra Ramadhani', 61, '2026-05-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(60, 'masuk', 'pembayaran_angsuran', 420833.33, 'Angsuran ke-6 - Citra Ramadhani', 62, '2026-06-15', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(61, 'masuk', 'topup_bulanan', 20000000.00, 'Topup saldo koperasi', 990001, '2026-04-02', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(62, 'masuk', 'topup_bulanan', 15000000.00, 'Topup saldo koperasi', 990002, '2026-06-02', 2, '2026-08-11 21:14:43', '2026-08-11 21:14:43');

-- --------------------------------------------------------

--
-- Table structure for table `kas_koperasi`
--

CREATE TABLE `kas_koperasi` (
  `id` bigint UNSIGNED NOT NULL,
  `saldo_saat_ini` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kas_koperasi`
--

INSERT INTO `kas_koperasi` (`id`, `saldo_saat_ini`, `created_at`, `updated_at`) VALUES
(1, 131250000.00, '2026-08-11 21:14:34', '2026-08-11 21:14:43');

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
(22, '2026_08_12_005642_add_sso_fields_to_users_table', 1);

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
(1, 'anggota.lihat', 'web', '2026-08-11 21:14:19', '2026-08-11 21:14:19'),
(2, 'anggota.kelola', 'web', '2026-08-11 21:14:19', '2026-08-11 21:14:19'),
(3, 'simpanan.lihat', 'web', '2026-08-11 21:14:19', '2026-08-11 21:14:19'),
(4, 'simpanan.konfirmasi', 'web', '2026-08-11 21:14:19', '2026-08-11 21:14:19'),
(5, 'pinjaman.lihat', 'web', '2026-08-11 21:14:19', '2026-08-11 21:14:19'),
(6, 'pinjaman.tinjau-bendahara', 'web', '2026-08-11 21:14:19', '2026-08-11 21:14:19'),
(7, 'pinjaman.approve-ketua', 'web', '2026-08-11 21:14:19', '2026-08-11 21:14:19'),
(8, 'angsuran.konfirmasi', 'web', '2026-08-11 21:14:19', '2026-08-11 21:14:19'),
(9, 'kas.lihat', 'web', '2026-08-11 21:14:19', '2026-08-11 21:14:19'),
(10, 'kas.topup', 'web', '2026-08-11 21:14:19', '2026-08-11 21:14:19'),
(11, 'laporan.lihat', 'web', '2026-08-11 21:14:19', '2026-08-11 21:14:19'),
(12, 'pengaturan.kelola', 'web', '2026-08-11 21:14:19', '2026-08-11 21:14:19'),
(13, 'portal.akses', 'web', '2026-08-11 21:14:19', '2026-08-11 21:14:19');

-- --------------------------------------------------------

--
-- Table structure for table `pinjaman`
--

CREATE TABLE `pinjaman` (
  `id` bigint UNSIGNED NOT NULL,
  `anggota_id` bigint UNSIGNED NOT NULL,
  `nominal` decimal(15,2) NOT NULL,
  `tenor_bulan` int UNSIGNED NOT NULL,
  `keperluan` text COLLATE utf8mb4_unicode_ci,
  `snapshot_bank` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `snapshot_no_rekening` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `snapshot_atas_nama` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `persentase_bunga` decimal(5,2) NOT NULL,
  `status` enum('diajukan','ditinjau_bendahara','approved_bendahara','approved_ketua','aktif','lunas','ditolak') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'diajukan',
  `sudah_pakai_privilege_reloan` tinyint(1) NOT NULL DEFAULT '0',
  `tanggal_pengajuan` date NOT NULL,
  `tanggal_pencairan` date DEFAULT NULL,
  `catatan_bendahara` text COLLATE utf8mb4_unicode_ci,
  `catatan_ketua` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pinjaman`
--

INSERT INTO `pinjaman` (`id`, `anggota_id`, `nominal`, `tenor_bulan`, `keperluan`, `snapshot_bank`, `snapshot_no_rekening`, `snapshot_atas_nama`, `persentase_bunga`, `status`, `sudah_pakai_privilege_reloan`, `tanggal_pengajuan`, `tanggal_pencairan`, `catatan_bendahara`, `catatan_ketua`, `created_at`, `updated_at`) VALUES
(1, 1, 1000000.00, 3, 'Kebutuhan harian', 'BCA', '1234001001', 'Budi Santoso', 1.00, 'diajukan', 0, '2026-08-10', NULL, NULL, NULL, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(2, 2, 2000000.00, 4, 'Biaya pendidikan anak', 'Mandiri', '8213400220', 'Siti Aminah', 1.00, 'aktif', 0, '2026-05-12', '2026-05-15', NULL, NULL, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(3, 3, 3000000.00, 6, 'Perbaikan rumah', 'BRI', '72810033', 'Ahmad Ridwan', 1.00, 'lunas', 0, '2025-12-12', '2025-12-15', NULL, NULL, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(4, 4, 5000000.00, 12, 'Pembelian kendaraan', 'BNI', '20987654', 'Dewi Lestari', 1.00, 'aktif', 0, '2025-10-12', '2025-10-15', NULL, NULL, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(5, 6, 1500000.00, 4, 'Kebutuhan hari raya', 'BCA', '1234002002', 'Agus Wijaya', 1.00, 'diajukan', 0, '2026-08-11', NULL, NULL, NULL, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(6, 16, 2500000.00, 6, 'Biaya pendidikan anak', 'Mandiri', '8213400221', 'Adi Nugroho', 1.00, 'diajukan', 0, '2026-08-09', NULL, NULL, NULL, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(7, 26, 5000000.00, 12, 'Perbaikan rumah', 'BRI', '72810034', 'Deni Setiawan', 1.00, 'diajukan', 0, '2026-08-07', NULL, NULL, NULL, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(8, 8, 3500000.00, 9, 'Biaya pengobatan', 'BNI', '20987655', 'Maya Sari', 1.00, 'approved_bendahara', 0, '2026-08-04', NULL, 'Verifikasi dokumen lengkap, layak diteruskan ke Ketua.', NULL, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(9, 18, 6000000.00, 12, 'Pembelian kendaraan', 'Bank Kalsel', '55990011', 'Yudha Pradana', 1.00, 'approved_bendahara', 0, '2026-08-02', NULL, 'Riwayat angsuran baik, disetujui.', NULL, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(10, 28, 2000000.00, 4, 'Modal usaha', 'BCA', '1234003003', 'Galih Prakoso', 1.00, 'approved_bendahara', 0, '2026-07-31', NULL, 'Dokumen sesuai ketentuan.', NULL, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(11, 7, 1000000.00, 3, 'Perlengkapan rumah tangga', 'BCA', '1234004004', 'Hendra Gunawan', 1.00, 'aktif', 0, '2026-06-12', '2026-06-15', NULL, NULL, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(12, 17, 2000000.00, 4, 'Biaya pendidikan anak', 'Mandiri', '8213400222', 'Indah Permata', 1.00, 'aktif', 0, '2026-05-12', '2026-05-15', NULL, NULL, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(13, 10, 3000000.00, 6, 'Perbaikan rumah', 'BRI', '72810035', 'Joko Susanto', 1.00, 'aktif', 0, '2026-03-12', '2026-03-15', NULL, NULL, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(14, 30, 4000000.00, 9, 'Modal usaha', 'BNI', '20987656', 'Ferry Ardiansyah', 1.00, 'lunas', 0, '2025-10-12', '2025-10-15', NULL, NULL, '2026-08-11 21:14:42', '2026-08-11 21:14:42'),
(15, 40, 6000000.00, 12, 'Pembelian kendaraan', 'Bank Kalsel', '55990012', 'Candra Wijaya', 1.00, 'lunas', 0, '2025-06-12', '2025-06-15', NULL, NULL, '2026-08-11 21:14:43', '2026-08-11 21:14:43'),
(16, 50, 2500000.00, 6, 'Kebutuhan hari raya', 'BCA', '1234005005', 'Citra Ramadhani', 1.00, 'lunas', 0, '2025-12-12', '2025-12-15', NULL, NULL, '2026-08-11 21:14:43', '2026-08-11 21:14:43');

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
(1, 'admin', 'web', '2026-08-11 21:14:19', '2026-08-11 21:14:19'),
(2, 'bendahara', 'web', '2026-08-11 21:14:19', '2026-08-11 21:14:19'),
(3, 'ketua_koperasi', 'web', '2026-08-11 21:14:19', '2026-08-11 21:14:19'),
(4, 'anggota', 'web', '2026-08-11 21:14:19', '2026-08-11 21:14:19');

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
(1, 2),
(3, 2),
(4, 2),
(5, 2),
(6, 2),
(8, 2),
(9, 2),
(10, 2),
(11, 2),
(1, 3),
(3, 3),
(5, 3),
(7, 3),
(9, 3),
(11, 3),
(13, 4);

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

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('NZhgQvuCOVlthtUA9SxAcfQUTM0bJhlif6u2oMk9', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJsaUdPYmo5ZmFpc0xyTGQ3b1c4TnVkMEVMN25WTWhsNjBWUjQyM3g3IiwidXJsIjpbXSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9hbmdnb3RhIiwicm91dGUiOiJhbmdnb3RhLmluZGV4In0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjEsImF1dGgiOnsicGFzc3dvcmRfY29uZmlybWVkX2F0IjoxNzg2NTExODAxfX0=', 1786517942);

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
(1, 1.00, '2026-01-01', '2026-08-11 21:14:34', '2026-08-11 21:14:34');

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
(1, 'anggota_baru', 'Anggota < 1 Tahun', 1000000.00, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(2, 'staff', 'Staff (1-5 Tahun)', 7000000.00, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(3, 'hod', 'HOD (1-5 Tahun)', 10000000.00, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(4, 'anggota_lama', 'Anggota ≥ 5 Tahun', 10000000.00, '2026-08-11 21:14:34', '2026-08-11 21:14:34');

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
(1, 'pokok', 'Simpanan Pokok', 50000.00, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(2, 'wajib', 'Simpanan Wajib', 45000.00, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(3, 'dana_sosial', 'Dana Sosial', 5000.00, '2026-08-11 21:14:34', '2026-08-11 21:14:34');

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
(1, 1, 'pokok', 50000.00, '2026-02', '2026-02-12', 4, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(2, 2, 'pokok', 50000.00, '2023-08', '2023-08-12', 5, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(3, 3, 'pokok', 50000.00, '2020-08', '2020-08-12', 6, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(4, 4, 'pokok', 50000.00, '2019-08', '2019-08-12', 7, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(5, 5, 'pokok', 50000.00, '2026-04', '2026-04-12', 8, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(6, 6, 'pokok', 50000.00, '2024-07', '2024-07-12', 9, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(7, 7, 'pokok', 50000.00, '2023-06', '2023-06-12', 10, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(8, 8, 'pokok', 50000.00, '2018-05', '2018-05-12', 11, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(9, 9, 'pokok', 50000.00, '2025-12', '2025-12-12', 12, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(10, 10, 'pokok', 50000.00, '2024-03', '2024-03-12', 13, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(11, 11, 'pokok', 50000.00, '2023-02', '2023-02-12', 14, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(12, 12, 'pokok', 50000.00, '2020-06', '2020-06-12', 15, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(13, 13, 'pokok', 50000.00, '2026-01', '2026-01-12', 16, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(14, 14, 'pokok', 50000.00, '2023-11', '2023-11-12', 17, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(15, 15, 'pokok', 50000.00, '2023-05', '2023-05-12', 18, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(16, 16, 'pokok', 50000.00, '2016-07', '2016-07-12', 19, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(17, 17, 'pokok', 50000.00, '2026-02', '2026-02-12', 20, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(18, 18, 'pokok', 50000.00, '2024-06', '2024-06-12', 21, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(19, 19, 'pokok', 50000.00, '2023-08', '2023-08-12', 22, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(20, 20, 'pokok', 50000.00, '2018-08', '2018-08-12', 23, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(21, 21, 'pokok', 50000.00, '2026-03', '2026-03-12', 24, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(22, 22, 'pokok', 50000.00, '2024-02', '2024-02-12', 25, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(23, 23, 'pokok', 50000.00, '2023-04', '2023-04-12', 26, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(24, 24, 'pokok', 50000.00, '2020-04', '2020-04-12', 27, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(25, 25, 'pokok', 50000.00, '2026-04', '2026-04-12', 28, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(26, 26, 'pokok', 50000.00, '2023-10', '2023-10-12', 29, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(27, 27, 'pokok', 50000.00, '2023-07', '2023-07-12', 30, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(28, 28, 'pokok', 50000.00, '2016-05', '2016-05-12', 31, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(29, 29, 'pokok', 50000.00, '2025-12', '2025-12-12', 32, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(30, 30, 'pokok', 50000.00, '2024-05', '2024-05-12', 33, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(31, 31, 'pokok', 50000.00, '2023-03', '2023-03-12', 34, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(32, 32, 'pokok', 50000.00, '2018-06', '2018-06-12', 35, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(33, 33, 'pokok', 50000.00, '2026-01', '2026-01-12', 36, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(34, 34, 'pokok', 50000.00, '2024-01', '2024-01-12', 37, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(35, 35, 'pokok', 50000.00, '2023-06', '2023-06-12', 38, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(36, 36, 'pokok', 50000.00, '2020-07', '2020-07-12', 39, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(37, 37, 'pokok', 50000.00, '2026-02', '2026-02-12', 40, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(38, 38, 'pokok', 50000.00, '2024-08', '2024-08-12', 41, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(39, 39, 'pokok', 50000.00, '2023-02', '2023-02-12', 42, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(40, 40, 'pokok', 50000.00, '2016-08', '2016-08-12', 43, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(41, 41, 'pokok', 50000.00, '2026-03', '2026-03-12', 44, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(42, 42, 'pokok', 50000.00, '2024-04', '2024-04-12', 45, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(43, 43, 'pokok', 50000.00, '2023-05', '2023-05-12', 46, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(44, 44, 'pokok', 50000.00, '2018-04', '2018-04-12', 47, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(45, 45, 'pokok', 50000.00, '2026-04', '2026-04-12', 48, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(46, 46, 'pokok', 50000.00, '2023-12', '2023-12-12', 49, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(47, 47, 'pokok', 50000.00, '2023-08', '2023-08-12', 50, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(48, 48, 'pokok', 50000.00, '2020-05', '2020-05-12', 51, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(49, 49, 'pokok', 50000.00, '2025-12', '2025-12-12', 52, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(50, 50, 'pokok', 50000.00, '2024-07', '2024-07-12', 53, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(51, 1, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(52, 1, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(53, 1, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(54, 1, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(55, 1, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(56, 1, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(57, 1, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(58, 1, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(59, 1, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(60, 1, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(61, 1, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(62, 1, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(63, 2, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(64, 2, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(65, 2, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(66, 2, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:35', '2026-08-11 21:14:35'),
(67, 2, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(68, 2, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(69, 2, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(70, 2, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(71, 2, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(72, 2, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(73, 2, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(74, 2, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(75, 3, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(76, 3, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(77, 3, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(78, 3, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(79, 3, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(80, 3, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(81, 3, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(82, 3, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(83, 3, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(84, 3, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(85, 3, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(86, 3, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(87, 4, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(88, 4, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(89, 4, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(90, 4, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(91, 4, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(92, 4, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(93, 4, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(94, 4, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(95, 4, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(96, 4, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(97, 4, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(98, 4, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(99, 5, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(100, 5, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(101, 5, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(102, 5, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(103, 5, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(104, 5, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(105, 5, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(106, 5, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(107, 6, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(108, 6, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(109, 6, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(110, 6, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(111, 6, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(112, 6, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(113, 6, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(114, 6, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(115, 6, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(116, 6, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(117, 6, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(118, 6, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(119, 7, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(120, 7, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(121, 7, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(122, 7, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(123, 7, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(124, 7, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(125, 7, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(126, 7, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(127, 7, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(128, 7, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(129, 7, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(130, 7, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(131, 8, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(132, 8, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(133, 8, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(134, 8, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(135, 8, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(136, 8, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(137, 8, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(138, 8, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(139, 8, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(140, 8, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(141, 8, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(142, 8, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(143, 9, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(144, 9, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(145, 9, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(146, 9, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(147, 9, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(148, 9, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(149, 9, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(150, 9, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(151, 9, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(152, 9, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(153, 9, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(154, 9, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(155, 10, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(156, 10, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(157, 10, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(158, 10, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(159, 10, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(160, 10, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(161, 10, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(162, 10, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(163, 10, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:36', '2026-08-11 21:14:36'),
(164, 10, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(165, 11, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(166, 11, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(167, 11, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(168, 11, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(169, 11, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(170, 11, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(171, 11, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(172, 11, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(173, 11, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(174, 11, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(175, 11, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(176, 11, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(177, 12, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(178, 12, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(179, 12, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(180, 12, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(181, 12, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(182, 12, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(183, 12, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(184, 12, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(185, 12, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(186, 12, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(187, 12, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(188, 12, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(189, 14, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(190, 14, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(191, 14, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(192, 14, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(193, 14, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(194, 14, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(195, 14, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(196, 14, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(197, 14, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(198, 14, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(199, 14, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(200, 14, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(201, 15, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(202, 15, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(203, 15, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(204, 15, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(205, 15, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(206, 15, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(207, 15, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(208, 15, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(209, 15, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(210, 15, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(211, 16, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(212, 16, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(213, 16, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(214, 16, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(215, 16, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(216, 16, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(217, 16, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(218, 16, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(219, 16, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(220, 16, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(221, 16, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(222, 16, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(223, 17, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(224, 17, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(225, 17, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(226, 17, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(227, 17, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(228, 17, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(229, 17, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(230, 17, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(231, 17, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(232, 17, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(233, 17, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(234, 17, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(235, 18, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(236, 18, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(237, 18, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(238, 18, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(239, 18, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(240, 18, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(241, 18, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(242, 18, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(243, 18, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(244, 18, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(245, 18, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(246, 18, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(247, 19, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(248, 19, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(249, 19, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(250, 19, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(251, 19, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(252, 19, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(253, 19, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(254, 19, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(255, 19, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(256, 19, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(257, 19, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(258, 19, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(259, 20, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:37', '2026-08-11 21:14:37'),
(260, 20, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(261, 20, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(262, 20, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(263, 20, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(264, 20, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(265, 20, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(266, 20, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(267, 20, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(268, 20, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(269, 21, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(270, 21, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(271, 21, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(272, 21, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(273, 21, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(274, 21, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(275, 21, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(276, 21, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(277, 21, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(278, 21, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(279, 21, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(280, 21, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(281, 22, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(282, 22, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(283, 22, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(284, 22, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(285, 22, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(286, 22, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(287, 22, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(288, 22, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(289, 22, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(290, 22, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(291, 22, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(292, 22, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(293, 23, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(294, 23, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(295, 23, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(296, 23, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(297, 23, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(298, 23, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(299, 23, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(300, 23, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(301, 23, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(302, 23, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(303, 23, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(304, 23, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(305, 24, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(306, 24, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(307, 24, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(308, 24, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(309, 24, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(310, 24, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(311, 24, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(312, 24, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(313, 24, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(314, 24, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(315, 24, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(316, 24, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(317, 25, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(318, 25, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(319, 25, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(320, 25, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(321, 25, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(322, 25, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(323, 25, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(324, 25, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(325, 26, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(326, 26, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(327, 26, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(328, 26, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(329, 26, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(330, 26, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(331, 26, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(332, 26, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(333, 26, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(334, 26, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(335, 26, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(336, 26, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(337, 28, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(338, 28, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(339, 28, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(340, 28, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(341, 28, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(342, 28, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(343, 28, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(344, 28, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(345, 28, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(346, 28, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(347, 28, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(348, 28, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(349, 29, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(350, 29, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(351, 29, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(352, 29, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(353, 29, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(354, 29, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(355, 29, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(356, 29, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:38', '2026-08-11 21:14:38'),
(357, 29, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(358, 29, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(359, 29, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(360, 29, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(361, 30, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(362, 30, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(363, 30, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(364, 30, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(365, 30, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(366, 30, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(367, 30, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(368, 30, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(369, 30, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(370, 30, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(371, 30, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(372, 30, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(373, 31, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(374, 31, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(375, 31, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(376, 31, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(377, 31, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(378, 31, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(379, 31, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(380, 31, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(381, 31, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(382, 31, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(383, 31, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(384, 31, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(385, 32, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(386, 32, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(387, 32, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(388, 32, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(389, 32, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(390, 32, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(391, 32, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(392, 32, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(393, 32, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(394, 32, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(395, 32, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(396, 32, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(397, 33, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(398, 33, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(399, 33, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(400, 33, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(401, 33, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(402, 33, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(403, 33, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(404, 33, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(405, 33, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(406, 33, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(407, 33, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(408, 33, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(409, 34, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(410, 34, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(411, 34, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(412, 34, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(413, 34, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(414, 34, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(415, 34, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(416, 34, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(417, 34, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(418, 34, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(419, 34, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(420, 34, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(421, 35, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(422, 35, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(423, 35, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(424, 35, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(425, 35, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(426, 35, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(427, 35, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(428, 35, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(429, 35, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(430, 35, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(431, 35, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(432, 35, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(433, 36, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(434, 36, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(435, 36, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(436, 36, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(437, 36, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(438, 36, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(439, 36, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(440, 36, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(441, 36, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(442, 36, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(443, 36, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(444, 36, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(445, 37, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(446, 37, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(447, 37, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(448, 37, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(449, 37, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(450, 37, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(451, 37, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(452, 37, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(453, 37, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(454, 37, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:39', '2026-08-11 21:14:39'),
(455, 37, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(456, 37, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(457, 38, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(458, 38, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(459, 38, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(460, 38, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(461, 38, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(462, 38, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(463, 38, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(464, 38, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(465, 38, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(466, 38, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(467, 38, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(468, 38, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(469, 39, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(470, 39, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(471, 39, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(472, 39, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(473, 39, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(474, 39, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(475, 39, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(476, 39, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(477, 39, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(478, 39, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(479, 39, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40');
INSERT INTO `simpanan` (`id`, `anggota_id`, `jenis`, `jumlah`, `bulan_periode`, `tanggal_input`, `input_by`, `created_at`, `updated_at`) VALUES
(480, 39, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(481, 40, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(482, 40, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(483, 40, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(484, 40, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(485, 40, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(486, 40, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(487, 40, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(488, 40, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(489, 40, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(490, 40, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(491, 40, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(492, 40, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(493, 41, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(494, 41, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(495, 41, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(496, 41, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(497, 41, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(498, 41, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(499, 41, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(500, 41, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(501, 41, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(502, 41, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(503, 41, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(504, 41, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(505, 42, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(506, 42, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(507, 42, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(508, 42, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(509, 42, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(510, 42, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(511, 42, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(512, 42, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(513, 42, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(514, 42, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(515, 42, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(516, 42, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(517, 43, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(518, 43, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(519, 43, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(520, 43, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(521, 43, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(522, 43, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(523, 43, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(524, 43, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(525, 43, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(526, 43, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(527, 43, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(528, 43, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(529, 44, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(530, 44, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(531, 44, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(532, 44, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(533, 44, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(534, 44, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(535, 44, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(536, 44, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(537, 44, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(538, 44, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(539, 44, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(540, 44, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(541, 45, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(542, 45, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(543, 45, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(544, 45, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(545, 45, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(546, 45, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(547, 45, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(548, 45, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(549, 45, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(550, 45, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(551, 46, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:40', '2026-08-11 21:14:40'),
(552, 46, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(553, 46, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(554, 46, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(555, 46, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(556, 46, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(557, 46, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(558, 46, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(559, 46, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(560, 46, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(561, 46, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(562, 46, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(563, 47, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(564, 47, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(565, 47, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(566, 47, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(567, 47, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(568, 47, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(569, 47, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(570, 47, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(571, 47, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(572, 47, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(573, 47, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(574, 47, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(575, 48, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(576, 48, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(577, 48, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(578, 48, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(579, 48, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(580, 48, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(581, 48, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(582, 48, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(583, 48, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(584, 48, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(585, 48, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(586, 48, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(587, 49, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(588, 49, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(589, 49, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(590, 49, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(591, 49, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(592, 49, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(593, 49, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(594, 49, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(595, 49, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(596, 49, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(597, 49, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(598, 49, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(599, 50, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(600, 50, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(601, 50, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(602, 50, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(603, 50, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(604, 50, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(605, 50, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(606, 50, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(607, 50, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(608, 50, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(609, 50, 'wajib', 45000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41'),
(610, 50, 'dana_sosial', 5000.00, '2026-08', '2026-08-12', 2, '2026-08-11 21:14:41', '2026-08-11 21:14:41');

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
(1, 0.00, 1000000.00, 3, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(2, 1000001.00, 2000000.00, 4, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(3, 2000001.00, 3000000.00, 6, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(4, 3000001.00, 4000000.00, 9, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(5, 4000001.00, 10000000.00, 12, '2026-08-11 21:14:34', '2026-08-11 21:14:34');

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
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `no_karyawan`, `sso_id`, `auth_provider`, `email_verified_at`, `password`, `harus_ganti_password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin Koperasi', 'admin@koperasi.test', 'ADM-000001', NULL, 'local', NULL, '$2y$12$JoV6xv6CdpJoiN2VCSDyweBTLze3csyi8japoTmITiSWa4bpAQazu', 0, NULL, '2026-08-11 21:14:19', '2026-08-11 21:14:19'),
(2, 'Bendahara Koperasi', 'bendahara@koperasi.test', 'BEN-000001', NULL, 'local', NULL, '$2y$12$zUdfa0JjUwOztJ1t/aZttuSLgfzzRwNk5yo3z.b5Pl0ETK2CMLANS', 0, NULL, '2026-08-11 21:14:20', '2026-08-11 21:14:20'),
(3, 'Ketua Koperasi', 'ketua@koperasi.test', 'KET-000001', NULL, 'local', NULL, '$2y$12$QesKofHpWvzHtgbP7FlA8ucT24gia5fjCzOXBuxmM9zxhkhG8pftO', 0, NULL, '2026-08-11 21:14:20', '2026-08-11 21:14:20'),
(4, 'Anggota Baru', 'anggota.baru@koperasi.test', 'TOP-100001', NULL, 'local', NULL, '$2y$12$qr9ufnl6.m0r5BwYFcey/..gfIBHn4wGJeP705J7P.mb04IaswdBG', 0, NULL, '2026-08-11 21:14:20', '2026-08-11 21:14:20'),
(5, 'Anggota Sedang', 'anggota.sedang@koperasi.test', 'TOP-100002', NULL, 'local', NULL, '$2y$12$Y2kyobEIri2OOTTffQr7wepTLkL.8oGuWeSR2rqFdL/F6LlXjGsWC', 0, NULL, '2026-08-11 21:14:20', '2026-08-11 21:14:20'),
(6, 'Anggota Lama', 'anggota.lama@koperasi.test', 'TOP-100003', NULL, 'local', NULL, '$2y$12$MPu8kTli30vb7irxuCpBx.Jg6DhBKRLIHve4Clvo3NpHUoGacgcb2', 0, NULL, '2026-08-11 21:14:21', '2026-08-11 21:14:21'),
(7, 'Anggota Reloan', 'anggota.reloan@koperasi.test', 'TOP-100004', NULL, 'local', NULL, '$2y$12$sagHSgckruRltJENjpVgeuOTsgWe/XnA/dKNsGnh5kzVKjD5QqQRi', 0, NULL, '2026-08-11 21:14:21', '2026-08-11 21:14:21'),
(8, 'Agus Wijaya', 'anggota.aguswijaya@koperasi.test', 'TOP-100005', NULL, 'local', NULL, '$2y$12$XD1n.Yae48EzKgB7Y6eEaudDKwAXzLeMtaaIbYGGs/WnRiVVsrJVW', 0, NULL, '2026-08-11 21:14:21', '2026-08-11 21:14:21'),
(9, 'Rina Marlina', 'anggota.rinamarlina@koperasi.test', 'TOP-100006', NULL, 'local', NULL, '$2y$12$iTbASzI8lrxfNP3NKhxvjOEcOe0DU5xt0eRc3Qx3t3MLQp9ftmg72', 0, NULL, '2026-08-11 21:14:22', '2026-08-11 21:14:22'),
(10, 'Bambang Sutrisno', 'anggota.bambangsutrisno@koperasi.test', 'TOP-100007', NULL, 'local', NULL, '$2y$12$pS4iAn5kXtGPFly0EGooEesKtffr/IpXqpIpE5S4zSCrOmoGpZwpa', 0, NULL, '2026-08-11 21:14:22', '2026-08-11 21:14:22'),
(11, 'Sari Rahayu', 'anggota.sarirahayu@koperasi.test', 'TOP-100008', NULL, 'local', NULL, '$2y$12$VW36TLEZsJcbm0Fj8S6v9eQdqlROj/iALaxzNx6cjLvXJw90b0H.q', 0, NULL, '2026-08-11 21:14:22', '2026-08-11 21:14:22'),
(12, 'Hendra Gunawan', 'anggota.hendragunawan@koperasi.test', 'TOP-100009', NULL, 'local', NULL, '$2y$12$7fHJol6XD3xV.cYvPp7M7Ov1O3OJlV1Sg164UNcwYovWKYJbT1Ydm', 0, NULL, '2026-08-11 21:14:22', '2026-08-11 21:14:22'),
(13, 'Dewi Anggraini', 'anggota.dewianggraini@koperasi.test', 'TOP-100010', NULL, 'local', NULL, '$2y$12$Xs6j7jwHQHjLszMtzQW3ROxxkq/CLaPhseywrQ7zkeHmn7RWceG8m', 0, NULL, '2026-08-11 21:14:23', '2026-08-11 21:14:23'),
(14, 'Joko Susanto', 'anggota.jokosusanto@koperasi.test', 'TOP-100011', NULL, 'local', NULL, '$2y$12$oihsFyZfbQz0S8qIc8E6cO6rSMcBiStYRvghaVl4rz92c3SMX1RXS', 0, NULL, '2026-08-11 21:14:23', '2026-08-11 21:14:23'),
(15, 'Maya Sari', 'anggota.mayasari@koperasi.test', 'TOP-100012', NULL, 'local', NULL, '$2y$12$I3WjPaEoQsUK63sPO/IaFuZsY6a6E938cAk1Xj8MNENezrDpGFQyq', 0, NULL, '2026-08-11 21:14:23', '2026-08-11 21:14:23'),
(16, 'Adi Nugroho', 'anggota.adinugroho@koperasi.test', 'TOP-100013', NULL, 'local', NULL, '$2y$12$LTtFprV3j6HeFRCpWfLGheMHpmkvNqtglNo2YWlMQqHwMpLaqO2l.', 0, NULL, '2026-08-11 21:14:24', '2026-08-11 21:14:24'),
(17, 'Lina Wijayanti', 'anggota.linawijayanti@koperasi.test', 'TOP-100014', NULL, 'local', NULL, '$2y$12$ntBZRVX8vUAcRi507cES5ehSKviFwkv8imCVD6pOIV9EiDYP5claS', 0, NULL, '2026-08-11 21:14:24', '2026-08-11 21:14:24'),
(18, 'Rizky Pratama', 'anggota.rizkypratama@koperasi.test', 'TOP-100015', NULL, 'local', NULL, '$2y$12$kJdqdfTZtzGfIx7BA6/tceRn.A4EZgAgmpdG1x30uztu0wNw9yNS2', 0, NULL, '2026-08-11 21:14:24', '2026-08-11 21:14:24'),
(19, 'Nia Kurniawati', 'anggota.niakurniawati@koperasi.test', 'TOP-100016', NULL, 'local', NULL, '$2y$12$fAPEksaXGSMBseigW0k.cuCYFtsXU.qJYgV6MUP6zJpBVrsM6DhGS', 0, NULL, '2026-08-11 21:14:24', '2026-08-11 21:14:24'),
(20, 'Eko Prasetyo', 'anggota.ekoprasetyo@koperasi.test', 'TOP-100017', NULL, 'local', NULL, '$2y$12$6B/.AjgVAeKEwLpbfiVlEOCPEXlElo2H6Xitg27gMtH7QVgab6WLK', 0, NULL, '2026-08-11 21:14:25', '2026-08-11 21:14:25'),
(21, 'Putri Handayani', 'anggota.putrihandayani@koperasi.test', 'TOP-100018', NULL, 'local', NULL, '$2y$12$tQRlCLgptPCjzrrJlNhdLO2tOrW4lOkK7mH4ygobPan913MFW3fCq', 0, NULL, '2026-08-11 21:14:25', '2026-08-11 21:14:25'),
(22, 'Fajar Ramadhan', 'anggota.fajarramadhan@koperasi.test', 'TOP-100019', NULL, 'local', NULL, '$2y$12$bgG4UzcaSVTpe4WzCIaYM.meox6RYWuc7mFI9tlzykETTPp/z0Gca', 0, NULL, '2026-08-11 21:14:25', '2026-08-11 21:14:25'),
(23, 'Indah Permata', 'anggota.indahpermata@koperasi.test', 'TOP-100020', NULL, 'local', NULL, '$2y$12$j7ZRilimFoMbHd4eeHyOqOhyE3kkx7ldbP1/hfEqD6CsI1x5S7.dO', 0, NULL, '2026-08-11 21:14:26', '2026-08-11 21:14:26'),
(24, 'Yudha Pradana', 'anggota.yudhapradana@koperasi.test', 'TOP-100021', NULL, 'local', NULL, '$2y$12$HICvPGBW0eQptm298e5a1unaNKOekmcg.3hBlD72Ypazck6Fpsvp.', 0, NULL, '2026-08-11 21:14:26', '2026-08-11 21:14:26'),
(25, 'Sri Wahyuni', 'anggota.sriwahyuni@koperasi.test', 'TOP-100022', NULL, 'local', NULL, '$2y$12$2E3WNbly5daXmhEC2OnuSufuL89BO6zQrJvwxLMHHl6puL2BDpDde', 0, NULL, '2026-08-11 21:14:26', '2026-08-11 21:14:26'),
(26, 'Andi Firmansyah', 'anggota.andifirmansyah@koperasi.test', 'TOP-100023', NULL, 'local', NULL, '$2y$12$9QFe3TUiXYtgMMVqZmaPpeoDDwOgKn9RTSEAdOJX4U4Dzjm4pPI.S', 0, NULL, '2026-08-11 21:14:26', '2026-08-11 21:14:26'),
(27, 'Ratna Sari', 'anggota.ratnasari@koperasi.test', 'TOP-100024', NULL, 'local', NULL, '$2y$12$syK.DWUwEncBVIquTmJ.Uu8gjMqmmDn8s.rMQd1v0ZVEgtxeYco/C', 0, NULL, '2026-08-11 21:14:27', '2026-08-11 21:14:27'),
(28, 'Deni Setiawan', 'anggota.denisetiawan@koperasi.test', 'TOP-100025', NULL, 'local', NULL, '$2y$12$f9eqlyoC/8SvBDS3W.D4DOxz4yg6ctDkedHu77yeWRTbL70g07ebO', 0, NULL, '2026-08-11 21:14:27', '2026-08-11 21:14:27'),
(29, 'Fitriani', 'anggota.fitriani@koperasi.test', 'TOP-100026', NULL, 'local', NULL, '$2y$12$khYOvO1v1tfMv67nLWixsueUUG9jyk/E.ZsP3j26orqfoP5JxdsfG', 0, NULL, '2026-08-11 21:14:27', '2026-08-11 21:14:27'),
(30, 'Rudi Hartono', 'anggota.rudihartono@koperasi.test', 'TOP-100027', NULL, 'local', NULL, '$2y$12$agMfCyVlvBuZo5Jd8w3NJOYUV/FjL6P1XRTL7ZEkv1SKBVGMcfFeC', 0, NULL, '2026-08-11 21:14:28', '2026-08-11 21:14:28'),
(31, 'Susi Susanti', 'anggota.susisusanti@koperasi.test', 'TOP-100028', NULL, 'local', NULL, '$2y$12$Ol/uz/AXir3I27qDk5SvNOt3DTA.k3Z8M9hf4VELAKfrBk.A6aC5O', 0, NULL, '2026-08-11 21:14:28', '2026-08-11 21:14:28'),
(32, 'Bayu Saputra', 'anggota.bayusaputra@koperasi.test', 'TOP-100029', NULL, 'local', NULL, '$2y$12$fyeWSjagRB7d0z2GxftteeJHizZTTH08nTHo.9ZZGFKW9WUVnoy3m', 0, NULL, '2026-08-11 21:14:28', '2026-08-11 21:14:28'),
(33, 'Ayu Lestari', 'anggota.ayulestari@koperasi.test', 'TOP-100030', NULL, 'local', NULL, '$2y$12$R8WID6R2w9btvJ3AhKO6sOlWUp7Odjgs.ffMI60gHV26PeUbc00KC', 0, NULL, '2026-08-11 21:14:28', '2026-08-11 21:14:28'),
(34, 'Toni Kurniawan', 'anggota.tonikurniawan@koperasi.test', 'TOP-100031', NULL, 'local', NULL, '$2y$12$PLi61uvVfsApw1UDZhbE3.lHpOtMFmOF8bJBpuOZSpTWDsdYomPvO', 0, NULL, '2026-08-11 21:14:29', '2026-08-11 21:14:29'),
(35, 'Tuti Herawati', 'anggota.tutiherawati@koperasi.test', 'TOP-100032', NULL, 'local', NULL, '$2y$12$fCDzTXNoWpQ5TqXY/7ALWOlUffaaGo.zABd.kkFZBzaVoqCaXcvzS', 0, NULL, '2026-08-11 21:14:29', '2026-08-11 21:14:29'),
(36, 'Ferry Ardiansyah', 'anggota.ferryardiansyah@koperasi.test', 'TOP-100033', NULL, 'local', NULL, '$2y$12$YfOOLXBRRHBMx9AE9nEhau0GH3SoRa51tPpS5WMtjaL8bFhgQh/6u', 0, NULL, '2026-08-11 21:14:29', '2026-08-11 21:14:29'),
(37, 'Desi Ratnasari', 'anggota.desiratnasari@koperasi.test', 'TOP-100034', NULL, 'local', NULL, '$2y$12$16yzAZ7ypajGPum4O6noQ.O.E0MOnvm1Oc5FwBQne4NofgUU0YrKq', 0, NULL, '2026-08-11 21:14:29', '2026-08-11 21:14:29'),
(38, 'Imam Santoso', 'anggota.imamsantoso@koperasi.test', 'TOP-100035', NULL, 'local', NULL, '$2y$12$HaQwb9LUvJu6DFpyReTwVu7XhgJmT906crNB3ZEa1dpOBzvDA8gra', 0, NULL, '2026-08-11 21:14:30', '2026-08-11 21:14:30'),
(39, 'Widya Astuti', 'anggota.widyaastuti@koperasi.test', 'TOP-100036', NULL, 'local', NULL, '$2y$12$81uECIPaUn868TUTVz2GCu0R8nRGHteA4u82/U0bbmc3X1pYXpVlK', 0, NULL, '2026-08-11 21:14:30', '2026-08-11 21:14:30'),
(40, 'Galih Prakoso', 'anggota.galihprakoso@koperasi.test', 'TOP-100037', NULL, 'local', NULL, '$2y$12$NtS0D0uIK.5HmQWGw0hKa.ihTRYuGIDArCRG5Uw26fBf5nnKwRMWu', 0, NULL, '2026-08-11 21:14:30', '2026-08-11 21:14:30'),
(41, 'Nur Aini', 'anggota.nuraini@koperasi.test', 'TOP-100038', NULL, 'local', NULL, '$2y$12$4V7v1n5.N7pJNuBeOzaUxu1fuwl1k899sbl7/KKKCMwr2e7DZnzsq', 0, NULL, '2026-08-11 21:14:31', '2026-08-11 21:14:31'),
(42, 'Satria Bima', 'anggota.satriabima@koperasi.test', 'TOP-100039', NULL, 'local', NULL, '$2y$12$MB5/jehFwzdYvTi5J5dro.JyMixccY5uOn6Wo8x126ysftjJoOj/y', 0, NULL, '2026-08-11 21:14:31', '2026-08-11 21:14:31'),
(43, 'Laila Amalia', 'anggota.lailaamalia@koperasi.test', 'TOP-100040', NULL, 'local', NULL, '$2y$12$4SoQDqg2y5LOnOP/Ly5Y/OX2s3XxU7/im9oai/Sshh/apaAyY/Y.a', 0, NULL, '2026-08-11 21:14:31', '2026-08-11 21:14:31'),
(44, 'Wisnu Prasetyo', 'anggota.wisnuprasetyo@koperasi.test', 'TOP-100041', NULL, 'local', NULL, '$2y$12$qzwGthRl28aFo.C71RnUJOyeDsnTQQLmSDKg1aKsQg9aspJRcS28m', 0, NULL, '2026-08-11 21:14:31', '2026-08-11 21:14:31'),
(45, 'Mega Puspita', 'anggota.megapuspita@koperasi.test', 'TOP-100042', NULL, 'local', NULL, '$2y$12$ViX9hxr08.MuX7gbyvw5Yeup57Gq/9MqRQrtUF.a0UOiMN7j6Sulu', 0, NULL, '2026-08-11 21:14:32', '2026-08-11 21:14:32'),
(46, 'Dimas Anggara', 'anggota.dimasanggara@koperasi.test', 'TOP-100043', NULL, 'local', NULL, '$2y$12$GWfNeeVh53P.zzfY1q8QieORMfwVQrQ/Gt9Jd3aE5nDsHqQ3geLdG', 0, NULL, '2026-08-11 21:14:32', '2026-08-11 21:14:32'),
(47, 'Nabila Putri', 'anggota.nabilaputri@koperasi.test', 'TOP-100044', NULL, 'local', NULL, '$2y$12$Epqu039RC3aJoaF4vdKFPujhzDBTpBpYbGOK74I7kCVPhyXChTFoK', 0, NULL, '2026-08-11 21:14:32', '2026-08-11 21:14:32'),
(48, 'Candra Wijaya', 'anggota.candrawijaya@koperasi.test', 'TOP-100045', NULL, 'local', NULL, '$2y$12$L7x7wmWQaHFSzjG.ZCYVX.UQw.wEgJK7237LO1e1tK/vjmFqfqEBm', 0, NULL, '2026-08-11 21:14:32', '2026-08-11 21:14:32'),
(49, 'Yuni Astuti', 'anggota.yuniastuti@koperasi.test', 'TOP-100046', NULL, 'local', NULL, '$2y$12$skPn8Whz4If/udW7AQ2lcOGsUOsShs5qv1tm92PYdmQLPnORoEq9S', 0, NULL, '2026-08-11 21:14:33', '2026-08-11 21:14:33'),
(50, 'Arif Hidayat', 'anggota.arifhidayat@koperasi.test', 'TOP-100047', NULL, 'local', NULL, '$2y$12$CnxiMrZNsjXmpyumLW0QTecXvttuqvrYmvDSf/SuUR03GKtRlQWgW', 0, NULL, '2026-08-11 21:14:33', '2026-08-11 21:14:33'),
(51, 'Rina Kusuma', 'anggota.rinakusuma@koperasi.test', 'TOP-100048', NULL, 'local', NULL, '$2y$12$lMSQsUaP/xd/wAup4kcIpuDRKyUfZ5qsNggk3ZFm2R8quXXaLdzBC', 0, NULL, '2026-08-11 21:14:33', '2026-08-11 21:14:33'),
(52, 'Bagus Pamungkas', 'anggota.baguspamungkas@koperasi.test', 'TOP-100049', NULL, 'local', NULL, '$2y$12$FlPpaWgcKEqoLZ4zXzZtYeUafPfmsjpbETiXRHocuB2zNA21rLTDK', 0, NULL, '2026-08-11 21:14:34', '2026-08-11 21:14:34'),
(53, 'Citra Ramadhani', 'anggota.citraramadhani@koperasi.test', 'TOP-100050', NULL, 'local', NULL, '$2y$12$VIsD.jjKyvO0VchQJhWDmuHJ76ifICjVqGfWUXdCddZyZWflzRuPO', 0, NULL, '2026-08-11 21:14:34', '2026-08-11 21:14:34');

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
  ADD KEY `anggota_user_id_foreign` (`user_id`);

--
-- Indexes for table `angsuran`
--
ALTER TABLE `angsuran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `angsuran_pinjaman_id_foreign` (`pinjaman_id`),
  ADD KEY `angsuran_confirmed_by_foreign` (`confirmed_by`);

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
  ADD KEY `pinjaman_anggota_id_foreign` (`anggota_id`);

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
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `angsuran`
--
ALTER TABLE `angsuran`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

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
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `kas_koperasi`
--
ALTER TABLE `kas_koperasi`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `pinjaman`
--
ALTER TABLE `pinjaman`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `rekening_anggota`
--
ALTER TABLE `rekening_anggota`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=611;

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
  ADD CONSTRAINT `anggota_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `angsuran`
--
ALTER TABLE `angsuran`
  ADD CONSTRAINT `angsuran_confirmed_by_foreign` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `angsuran_pinjaman_id_foreign` FOREIGN KEY (`pinjaman_id`) REFERENCES `pinjaman` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `pinjaman`
--
ALTER TABLE `pinjaman`
  ADD CONSTRAINT `pinjaman_anggota_id_foreign` FOREIGN KEY (`anggota_id`) REFERENCES `anggota` (`id`) ON DELETE CASCADE;

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
