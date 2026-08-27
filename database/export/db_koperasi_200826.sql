-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 18, 2026 at 01:13 AM
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
(1, 4, 'ANG-2026-0001', 'TOP-100001', NULL, 'Budi Santoso', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'staff', '2025-11-18', '2026-02-18', 'aktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(2, 5, 'ANG-2023-0045', 'TOP-100002', NULL, 'Siti Aminah', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'hod', '2023-06-18', '2023-08-18', 'aktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(3, 6, 'ANG-2019-0012', 'TOP-100003', NULL, 'Ahmad Ridwan', 'Palangka', 'Operasional', 'Operasional', 'Gudang', 'staff', '2019-08-18', '2020-08-18', 'aktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(4, 7, 'ANG-2018-0003', 'TOP-100004', NULL, 'Dewi Lestari', 'Banjarmasin', 'Marketing', 'Marketing', 'Promosi', 'hod', '2018-08-18', '2019-08-18', 'aktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(5, 8, 'ANG-2026-0002', 'TOP-100005', '3207000000000000', 'Agus Wijaya', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2026-01-18', '2026-04-18', 'aktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(6, 9, 'ANG-2026-0003', 'TOP-100006', '3207000000000001', 'Rina Marlina', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2024-03-18', '2024-07-18', 'aktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(7, 10, 'ANG-2026-0004', 'TOP-100007', '3207000000000002', 'Bambang Sutrisno', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2023-01-18', '2023-06-18', 'aktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(8, 11, 'ANG-2026-0005', 'TOP-100008', '3207000000000003', 'Sari Rahayu', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2017-11-18', '2018-05-18', 'aktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(9, 12, 'ANG-2026-0006', 'TOP-100009', '3207000000000004', 'Hendra Gunawan', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2025-05-18', '2025-12-18', 'aktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(10, 13, 'ANG-2026-0007', 'TOP-100010', '3207000000000005', 'Dewi Anggraini', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2023-07-18', '2024-03-18', 'aktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(11, 14, 'ANG-2026-0008', 'TOP-100011', '3207000000000006', 'Joko Susanto', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2022-05-18', '2023-02-18', 'aktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(12, 15, 'ANG-2026-0009', 'TOP-100012', '3207000000000007', 'Maya Sari', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2019-08-18', '2020-06-18', 'aktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(13, 16, 'ANG-2026-0010', 'TOP-100013', '3207000000000008', 'Adi Nugroho', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2025-10-18', '2026-01-18', 'nonaktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(14, 17, 'ANG-2026-0011', 'TOP-100014', '3207000000000009', 'Lina Wijayanti', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2023-07-18', '2023-11-18', 'aktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(15, 18, 'ANG-2026-0012', 'TOP-100015', '3207000000000010', 'Rizky Pratama', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2022-12-18', '2023-05-18', 'aktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(16, 19, 'ANG-2026-0013', 'TOP-100016', '3207000000000011', 'Nia Kurniawati', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2016-01-18', '2016-07-18', 'aktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(17, 20, 'ANG-2026-0014', 'TOP-100017', '3207000000000012', 'Eko Prasetyo', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2025-07-18', '2026-02-18', 'aktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(18, 21, 'ANG-2026-0015', 'TOP-100018', '3207000000000013', 'Putri Handayani', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2023-10-18', '2024-06-18', 'aktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(19, 22, 'ANG-2026-0016', 'TOP-100019', '3207000000000014', 'Fajar Ramadhan', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2022-11-18', '2023-08-18', 'aktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(20, 23, 'ANG-2026-0017', 'TOP-100020', '3207000000000015', 'Indah Permata', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2017-10-18', '2018-08-18', 'aktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(21, 24, 'ANG-2026-0018', 'TOP-100021', '3207000000000016', 'Yudha Pradana', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2025-12-18', '2026-03-18', 'aktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(22, 25, 'ANG-2026-0019', 'TOP-100022', '3207000000000017', 'Sri Wahyuni', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2023-10-18', '2024-02-18', 'aktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(23, 26, 'ANG-2026-0020', 'TOP-100023', '3207000000000018', 'Andi Firmansyah', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2022-11-18', '2023-04-18', 'aktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(24, 27, 'ANG-2026-0021', 'TOP-100024', '3207000000000019', 'Ratna Sari', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2019-10-18', '2020-04-18', 'aktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(25, 28, 'ANG-2026-0022', 'TOP-100025', '3207000000000020', 'Deni Setiawan', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2025-09-18', '2026-04-18', 'aktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(26, 29, 'ANG-2026-0023', 'TOP-100026', '3207000000000021', 'Fitriani', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2023-02-18', '2023-10-18', 'aktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(27, 30, 'ANG-2026-0024', 'TOP-100027', '3207000000000022', 'Rudi Hartono', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2022-10-18', '2023-07-18', 'nonaktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(28, 31, 'ANG-2026-0025', 'TOP-100028', '3207000000000023', 'Susi Susanti', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2015-07-18', '2016-05-18', 'aktif', NULL, NULL, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(29, 32, 'ANG-2026-0026', 'TOP-100029', '3207000000000024', 'Bayu Saputra', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2025-09-18', '2025-12-18', 'aktif', NULL, NULL, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(30, 33, 'ANG-2026-0027', 'TOP-100030', '3207000000000025', 'Ayu Lestari', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2024-01-18', '2024-05-18', 'aktif', NULL, NULL, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(31, 34, 'ANG-2026-0028', 'TOP-100031', '3207000000000026', 'Toni Kurniawan', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2022-10-18', '2023-03-18', 'aktif', NULL, NULL, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(32, 35, 'ANG-2026-0029', 'TOP-100032', '3207000000000027', 'Tuti Herawati', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2017-12-18', '2018-06-18', 'aktif', NULL, NULL, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(33, 36, 'ANG-2026-0030', 'TOP-100033', '3207000000000028', 'Ferry Ardiansyah', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2025-06-18', '2026-01-18', 'aktif', NULL, NULL, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(34, 37, 'ANG-2026-0031', 'TOP-100034', '3207000000000029', 'Desi Ratnasari', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2023-05-18', '2024-01-18', 'aktif', NULL, NULL, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(35, 38, 'ANG-2026-0032', 'TOP-100035', '3207000000000030', 'Imam Santoso', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2022-09-18', '2023-06-18', 'aktif', NULL, NULL, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(36, 39, 'ANG-2026-0033', 'TOP-100036', '3207000000000031', 'Widya Astuti', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2019-09-18', '2020-07-18', 'aktif', NULL, NULL, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(37, 40, 'ANG-2026-0034', 'TOP-100037', '3207000000000032', 'Galih Prakoso', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2025-11-18', '2026-02-18', 'aktif', NULL, NULL, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(38, 41, 'ANG-2026-0035', 'TOP-100038', '3207000000000033', 'Nur Aini', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2024-04-18', '2024-08-18', 'aktif', NULL, NULL, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(39, 42, 'ANG-2026-0036', 'TOP-100039', '3207000000000034', 'Satria Bima', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2022-09-18', '2023-02-18', 'aktif', NULL, NULL, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(40, 43, 'ANG-2026-0037', 'TOP-100040', '3207000000000035', 'Laila Amalia', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2016-02-18', '2016-08-18', 'aktif', NULL, NULL, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(41, 44, 'ANG-2026-0038', 'TOP-100041', '3207000000000036', 'Wisnu Prasetyo', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2025-08-18', '2026-03-18', 'aktif', NULL, NULL, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(42, 45, 'ANG-2026-0039', 'TOP-100042', '3207000000000037', 'Mega Puspita', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2023-08-18', '2024-04-18', 'aktif', NULL, NULL, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(43, 46, 'ANG-2026-0040', 'TOP-100043', '3207000000000038', 'Dimas Anggara', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2022-08-18', '2023-05-18', 'aktif', NULL, NULL, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(44, 47, 'ANG-2026-0041', 'TOP-100044', '3207000000000039', 'Nabila Putri', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2017-06-18', '2018-04-18', 'aktif', NULL, NULL, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(45, 48, 'ANG-2026-0042', 'TOP-100045', '3207000000000040', 'Candra Wijaya', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2026-01-18', '2026-04-18', 'aktif', NULL, NULL, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(46, 49, 'ANG-2026-0043', 'TOP-100046', '3207000000000041', 'Yuni Astuti', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2023-08-18', '2023-12-18', 'aktif', NULL, NULL, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(47, 50, 'ANG-2026-0044', 'TOP-100047', '3207000000000042', 'Arif Hidayat', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2023-03-18', '2023-08-18', 'aktif', NULL, NULL, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(48, 51, 'ANG-2026-0045', 'TOP-100048', '3207000000000043', 'Rina Kusuma', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2019-11-18', '2020-05-18', 'aktif', NULL, NULL, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(49, 52, 'ANG-2026-0046', 'TOP-100049', '3207000000000044', 'Bagus Pamungkas', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2025-05-18', '2025-12-18', 'aktif', NULL, NULL, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(50, 53, 'ANG-2026-0047', 'TOP-100050', '3207000000000045', 'Citra Ramadhani', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2023-11-18', '2024-07-18', 'aktif', NULL, NULL, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(51, 2, 'ANG-2020-0001', 'BEN-000001', NULL, 'Bendahara Koperasi', 'Banjarmasin', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2018-08-18', '2019-08-18', 'aktif', NULL, NULL, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(52, 3, 'ANG-2019-0001', 'KET-000001', NULL, 'Ketua Koperasi', 'Banjarmasin', 'Keuangan', 'Keuangan', 'Akuntansi', 'hod', '2016-08-18', '2017-08-18', 'aktif', NULL, NULL, '2026-08-17 16:53:54', '2026-08-17 16:53:54');

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
(1, 2, 1, 500000.00, 20000.00, 520000.00, 'lunas', '2026-06-21', '2026-06-21', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(2, 2, 2, 500000.00, 15000.00, 515000.00, 'lunas', '2026-07-21', '2026-07-21', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(3, 2, 3, 500000.00, 10000.00, 510000.00, 'belum_bayar', '2026-08-21', NULL, NULL, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(4, 2, 4, 500000.00, 5000.00, 505000.00, 'belum_bayar', '2026-09-21', NULL, NULL, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(5, 3, 1, 500000.00, 30000.00, 530000.00, 'lunas', '2026-01-21', '2026-01-21', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(6, 3, 2, 500000.00, 25000.00, 525000.00, 'lunas', '2026-02-21', '2026-02-21', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(7, 3, 3, 500000.00, 20000.00, 520000.00, 'lunas', '2026-03-21', '2026-03-21', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(8, 3, 4, 500000.00, 15000.00, 515000.00, 'lunas', '2026-04-21', '2026-04-21', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(9, 3, 5, 500000.00, 10000.00, 510000.00, 'lunas', '2026-05-21', '2026-05-21', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(10, 3, 6, 500000.00, 5000.00, 505000.00, 'lunas', '2026-06-21', '2026-06-21', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(11, 4, 1, 416666.67, 50000.00, 466666.67, 'lunas', '2025-11-21', '2025-11-21', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(12, 4, 2, 416666.67, 45833.33, 462500.00, 'lunas', '2025-12-21', '2025-12-21', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(13, 4, 3, 416666.67, 41666.67, 458333.33, 'lunas', '2026-01-21', '2026-01-21', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(14, 4, 4, 416666.67, 37500.00, 454166.67, 'lunas', '2026-02-21', '2026-02-21', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(15, 4, 5, 416666.67, 33333.33, 450000.00, 'lunas', '2026-03-21', '2026-03-21', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(16, 4, 6, 416666.67, 29166.67, 445833.33, 'lunas', '2026-04-21', '2026-04-21', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(17, 4, 7, 416666.67, 25000.00, 441666.67, 'lunas', '2026-05-21', '2026-05-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(18, 4, 8, 416666.67, 20833.33, 437500.00, 'lunas', '2026-06-21', '2026-06-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(19, 4, 9, 416666.67, 16666.67, 433333.33, 'lunas', '2026-07-21', '2026-07-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(20, 4, 10, 416666.67, 12500.00, 429166.67, 'lunas', '2026-08-21', '2026-08-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(21, 4, 11, 416666.67, 8333.33, 425000.00, 'belum_bayar', '2026-09-21', NULL, NULL, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(22, 4, 12, 416666.67, 4166.67, 420833.33, 'belum_bayar', '2026-10-21', NULL, NULL, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(23, 11, 1, 333333.33, 10000.00, 343333.33, 'lunas', '2026-07-21', '2026-07-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(24, 11, 2, 333333.33, 6666.67, 340000.00, 'belum_bayar', '2026-08-21', NULL, NULL, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(25, 11, 3, 333333.33, 3333.33, 336666.67, 'belum_bayar', '2026-09-21', NULL, NULL, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(26, 12, 1, 500000.00, 20000.00, 520000.00, 'lunas', '2026-06-21', '2026-06-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(27, 12, 2, 500000.00, 15000.00, 515000.00, 'lunas', '2026-07-21', '2026-07-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(28, 12, 3, 500000.00, 10000.00, 510000.00, 'belum_bayar', '2026-08-21', NULL, NULL, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(29, 12, 4, 500000.00, 5000.00, 505000.00, 'belum_bayar', '2026-09-21', NULL, NULL, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(30, 13, 1, 500000.00, 30000.00, 530000.00, 'lunas', '2026-04-21', '2026-04-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(31, 13, 2, 500000.00, 25000.00, 525000.00, 'lunas', '2026-05-21', '2026-05-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(32, 13, 3, 500000.00, 20000.00, 520000.00, 'lunas', '2026-06-21', '2026-06-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(33, 13, 4, 500000.00, 15000.00, 515000.00, 'belum_bayar', '2026-07-21', NULL, NULL, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(34, 13, 5, 500000.00, 10000.00, 510000.00, 'belum_bayar', '2026-08-21', NULL, NULL, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(35, 13, 6, 500000.00, 5000.00, 505000.00, 'belum_bayar', '2026-09-21', NULL, NULL, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(36, 14, 1, 444444.44, 40000.00, 484444.44, 'lunas', '2025-11-21', '2025-11-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(37, 14, 2, 444444.44, 35555.56, 480000.00, 'lunas', '2025-12-21', '2025-12-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(38, 14, 3, 444444.44, 31111.11, 475555.56, 'lunas', '2026-01-21', '2026-01-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(39, 14, 4, 444444.44, 26666.67, 471111.11, 'lunas', '2026-02-21', '2026-02-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(40, 14, 5, 444444.44, 22222.22, 466666.67, 'lunas', '2026-03-21', '2026-03-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(41, 14, 6, 444444.44, 17777.78, 462222.22, 'lunas', '2026-04-21', '2026-04-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(42, 14, 7, 444444.44, 13333.33, 457777.78, 'lunas', '2026-05-21', '2026-05-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(43, 14, 8, 444444.44, 8888.89, 453333.33, 'lunas', '2026-06-21', '2026-06-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(44, 14, 9, 444444.44, 4444.44, 448888.89, 'lunas', '2026-07-21', '2026-07-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(45, 15, 1, 500000.00, 60000.00, 560000.00, 'lunas', '2025-07-21', '2025-07-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(46, 15, 2, 500000.00, 55000.00, 555000.00, 'lunas', '2025-08-21', '2025-08-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(47, 15, 3, 500000.00, 50000.00, 550000.00, 'lunas', '2025-09-21', '2025-09-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(48, 15, 4, 500000.00, 45000.00, 545000.00, 'lunas', '2025-10-21', '2025-10-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(49, 15, 5, 500000.00, 40000.00, 540000.00, 'lunas', '2025-11-21', '2025-11-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(50, 15, 6, 500000.00, 35000.00, 535000.00, 'lunas', '2025-12-21', '2025-12-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(51, 15, 7, 500000.00, 30000.00, 530000.00, 'lunas', '2026-01-21', '2026-01-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(52, 15, 8, 500000.00, 25000.00, 525000.00, 'lunas', '2026-02-21', '2026-02-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(53, 15, 9, 500000.00, 20000.00, 520000.00, 'lunas', '2026-03-21', '2026-03-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(54, 15, 10, 500000.00, 15000.00, 515000.00, 'lunas', '2026-04-21', '2026-04-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(55, 15, 11, 500000.00, 10000.00, 510000.00, 'lunas', '2026-05-21', '2026-05-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(56, 15, 12, 500000.00, 5000.00, 505000.00, 'lunas', '2026-06-21', '2026-06-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(57, 16, 1, 416666.67, 25000.00, 441666.67, 'lunas', '2026-01-21', '2026-01-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(58, 16, 2, 416666.67, 20833.33, 437500.00, 'lunas', '2026-02-21', '2026-02-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(59, 16, 3, 416666.67, 16666.67, 433333.33, 'lunas', '2026-03-21', '2026-03-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(60, 16, 4, 416666.67, 12500.00, 429166.67, 'lunas', '2026-04-21', '2026-04-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(61, 16, 5, 416666.67, 8333.33, 425000.00, 'lunas', '2026-05-21', '2026-05-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(62, 16, 6, 416666.67, 4166.67, 420833.33, 'lunas', '2026-06-21', '2026-06-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02');

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
(1, 1, 'update_permission_role', 'Hak akses role \'admin\' diperbarui.', '{\"permissions\": [\"anggota.lihat\", \"anggota.kelola\", \"simpanan.lihat\", \"pinjaman.lihat\", \"kas.lihat\", \"laporan.lihat\", \"pengaturan.kelola\"]}', '{\"permissions\": [\"anggota.lihat\", \"anggota.kelola\", \"simpanan.lihat\", \"pinjaman.lihat\", \"kas.lihat\", \"laporan.lihat\", \"pengaturan.kelola\", \"angsuran.konfirmasi\", \"kas.topup\", \"pinjaman.approve-ketua\", \"pinjaman.tinjau-bendahara\", \"portal.akses\", \"simpanan.konfirmasi\"]}', '2026-08-17 16:55:18', '2026-08-17 16:55:18');

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
('laravel-cache-spatie.permission.cache', 'a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:13:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:13:\"anggota.lihat\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:14:\"anggota.kelola\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:14:\"simpanan.lihat\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:19:\"simpanan.konfirmasi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:14:\"pinjaman.lihat\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:25:\"pinjaman.tinjau-bendahara\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:22:\"pinjaman.approve-ketua\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:19:\"angsuran.konfirmasi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:9:\"kas.lihat\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:9:\"kas.topup\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:13:\"laporan.lihat\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:17:\"pengaturan.kelola\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:12:\"portal.akses\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;}}}s:5:\"roles\";a:4:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:5:\"admin\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:9:\"bendahara\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:14:\"ketua_koperasi\";s:1:\"c\";s:3:\"web\";}i:3;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:7:\"anggota\";s:1:\"c\";s:3:\"web\";}}}', 1787100919);

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
  `kategori` enum('saldo_awal','topup_bulanan','pencairan_pinjaman','pembayaran_angsuran','dana_sosial_bulanan','pengeluaran_koperasi','pengeluaran_dana_sosial') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kantong` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pinjaman',
  `jumlah` decimal(15,2) NOT NULL,
  `saldo_setelah` decimal(15,2) NOT NULL DEFAULT '0.00',
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

INSERT INTO `jurnal_kas` (`id`, `tipe`, `kategori`, `kantong`, `jumlah`, `saldo_setelah`, `keterangan`, `referensi_id`, `tanggal`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'masuk', 'saldo_awal', 'pinjaman', 100000000.00, 200000000.00, 'Saldo awal koperasi', NULL, '2026-08-18', 1, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(2, 'keluar', 'pencairan_pinjaman', 'pinjaman', 2000000.00, 0.00, 'Pencairan pinjaman - Siti Aminah', 2, '2026-05-21', 3, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(3, 'masuk', 'pembayaran_angsuran', 'pinjaman', 520000.00, 0.00, 'Angsuran ke-1 - Siti Aminah', 1, '2026-06-21', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(4, 'masuk', 'pembayaran_angsuran', 'pinjaman', 515000.00, 0.00, 'Angsuran ke-2 - Siti Aminah', 2, '2026-07-21', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(5, 'keluar', 'pencairan_pinjaman', 'pinjaman', 3000000.00, 0.00, 'Pencairan pinjaman - Ahmad Ridwan', 3, '2025-12-21', 3, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(6, 'masuk', 'pembayaran_angsuran', 'pinjaman', 530000.00, 0.00, 'Angsuran ke-1 - Ahmad Ridwan', 5, '2026-01-21', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(7, 'masuk', 'pembayaran_angsuran', 'pinjaman', 525000.00, 0.00, 'Angsuran ke-2 - Ahmad Ridwan', 6, '2026-02-21', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(8, 'masuk', 'pembayaran_angsuran', 'pinjaman', 520000.00, 0.00, 'Angsuran ke-3 - Ahmad Ridwan', 7, '2026-03-21', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(9, 'masuk', 'pembayaran_angsuran', 'pinjaman', 515000.00, 0.00, 'Angsuran ke-4 - Ahmad Ridwan', 8, '2026-04-21', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(10, 'masuk', 'pembayaran_angsuran', 'pinjaman', 510000.00, 0.00, 'Angsuran ke-5 - Ahmad Ridwan', 9, '2026-05-21', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(11, 'masuk', 'pembayaran_angsuran', 'pinjaman', 505000.00, 0.00, 'Angsuran ke-6 - Ahmad Ridwan', 10, '2026-06-21', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(12, 'keluar', 'pencairan_pinjaman', 'pinjaman', 5000000.00, 0.00, 'Pencairan pinjaman - Dewi Lestari', 4, '2025-10-21', 3, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(13, 'masuk', 'pembayaran_angsuran', 'pinjaman', 466666.67, 0.00, 'Angsuran ke-1 - Dewi Lestari', 11, '2025-11-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(14, 'masuk', 'pembayaran_angsuran', 'pinjaman', 462500.00, 0.00, 'Angsuran ke-2 - Dewi Lestari', 12, '2025-12-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(15, 'masuk', 'pembayaran_angsuran', 'pinjaman', 458333.33, 0.00, 'Angsuran ke-3 - Dewi Lestari', 13, '2026-01-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(16, 'masuk', 'pembayaran_angsuran', 'pinjaman', 454166.67, 0.00, 'Angsuran ke-4 - Dewi Lestari', 14, '2026-02-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(17, 'masuk', 'pembayaran_angsuran', 'pinjaman', 450000.00, 0.00, 'Angsuran ke-5 - Dewi Lestari', 15, '2026-03-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(18, 'masuk', 'pembayaran_angsuran', 'pinjaman', 445833.33, 0.00, 'Angsuran ke-6 - Dewi Lestari', 16, '2026-04-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(19, 'masuk', 'pembayaran_angsuran', 'pinjaman', 441666.67, 0.00, 'Angsuran ke-7 - Dewi Lestari', 17, '2026-05-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(20, 'masuk', 'pembayaran_angsuran', 'pinjaman', 437500.00, 0.00, 'Angsuran ke-8 - Dewi Lestari', 18, '2026-06-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(21, 'masuk', 'pembayaran_angsuran', 'pinjaman', 433333.33, 0.00, 'Angsuran ke-9 - Dewi Lestari', 19, '2026-07-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(22, 'masuk', 'pembayaran_angsuran', 'pinjaman', 429166.67, 0.00, 'Angsuran ke-10 - Dewi Lestari', 20, '2026-08-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(23, 'keluar', 'pencairan_pinjaman', 'pinjaman', 1000000.00, 0.00, 'Pencairan pinjaman - Bambang Sutrisno', 11, '2026-06-21', 3, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(24, 'masuk', 'pembayaran_angsuran', 'pinjaman', 343333.33, 0.00, 'Angsuran ke-1 - Bambang Sutrisno', 23, '2026-07-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(25, 'keluar', 'pencairan_pinjaman', 'pinjaman', 2000000.00, 0.00, 'Pencairan pinjaman - Eko Prasetyo', 12, '2026-05-21', 3, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(26, 'masuk', 'pembayaran_angsuran', 'pinjaman', 520000.00, 0.00, 'Angsuran ke-1 - Eko Prasetyo', 26, '2026-06-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(27, 'masuk', 'pembayaran_angsuran', 'pinjaman', 515000.00, 0.00, 'Angsuran ke-2 - Eko Prasetyo', 27, '2026-07-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(28, 'keluar', 'pencairan_pinjaman', 'pinjaman', 3000000.00, 0.00, 'Pencairan pinjaman - Dewi Anggraini', 13, '2026-03-21', 3, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(29, 'masuk', 'pembayaran_angsuran', 'pinjaman', 530000.00, 0.00, 'Angsuran ke-1 - Dewi Anggraini', 30, '2026-04-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(30, 'masuk', 'pembayaran_angsuran', 'pinjaman', 525000.00, 0.00, 'Angsuran ke-2 - Dewi Anggraini', 31, '2026-05-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(31, 'masuk', 'pembayaran_angsuran', 'pinjaman', 520000.00, 0.00, 'Angsuran ke-3 - Dewi Anggraini', 32, '2026-06-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(32, 'keluar', 'pencairan_pinjaman', 'pinjaman', 4000000.00, 0.00, 'Pencairan pinjaman - Ayu Lestari', 14, '2025-10-21', 3, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(33, 'masuk', 'pembayaran_angsuran', 'pinjaman', 484444.44, 0.00, 'Angsuran ke-1 - Ayu Lestari', 36, '2025-11-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(34, 'masuk', 'pembayaran_angsuran', 'pinjaman', 480000.00, 0.00, 'Angsuran ke-2 - Ayu Lestari', 37, '2025-12-21', 2, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(35, 'masuk', 'pembayaran_angsuran', 'pinjaman', 475555.56, 0.00, 'Angsuran ke-3 - Ayu Lestari', 38, '2026-01-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(36, 'masuk', 'pembayaran_angsuran', 'pinjaman', 471111.11, 0.00, 'Angsuran ke-4 - Ayu Lestari', 39, '2026-02-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(37, 'masuk', 'pembayaran_angsuran', 'pinjaman', 466666.67, 0.00, 'Angsuran ke-5 - Ayu Lestari', 40, '2026-03-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(38, 'masuk', 'pembayaran_angsuran', 'pinjaman', 462222.22, 0.00, 'Angsuran ke-6 - Ayu Lestari', 41, '2026-04-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(39, 'masuk', 'pembayaran_angsuran', 'pinjaman', 457777.78, 0.00, 'Angsuran ke-7 - Ayu Lestari', 42, '2026-05-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(40, 'masuk', 'pembayaran_angsuran', 'pinjaman', 453333.33, 0.00, 'Angsuran ke-8 - Ayu Lestari', 43, '2026-06-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(41, 'masuk', 'pembayaran_angsuran', 'pinjaman', 448888.89, 0.00, 'Angsuran ke-9 - Ayu Lestari', 44, '2026-07-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(42, 'keluar', 'pencairan_pinjaman', 'pinjaman', 6000000.00, 0.00, 'Pencairan pinjaman - Laila Amalia', 15, '2025-06-21', 3, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(43, 'masuk', 'pembayaran_angsuran', 'pinjaman', 560000.00, 0.00, 'Angsuran ke-1 - Laila Amalia', 45, '2025-07-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(44, 'masuk', 'pembayaran_angsuran', 'pinjaman', 555000.00, 0.00, 'Angsuran ke-2 - Laila Amalia', 46, '2025-08-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(45, 'masuk', 'pembayaran_angsuran', 'pinjaman', 550000.00, 0.00, 'Angsuran ke-3 - Laila Amalia', 47, '2025-09-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(46, 'masuk', 'pembayaran_angsuran', 'pinjaman', 545000.00, 0.00, 'Angsuran ke-4 - Laila Amalia', 48, '2025-10-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(47, 'masuk', 'pembayaran_angsuran', 'pinjaman', 540000.00, 0.00, 'Angsuran ke-5 - Laila Amalia', 49, '2025-11-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(48, 'masuk', 'pembayaran_angsuran', 'pinjaman', 535000.00, 0.00, 'Angsuran ke-6 - Laila Amalia', 50, '2025-12-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(49, 'masuk', 'pembayaran_angsuran', 'pinjaman', 530000.00, 0.00, 'Angsuran ke-7 - Laila Amalia', 51, '2026-01-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(50, 'masuk', 'pembayaran_angsuran', 'pinjaman', 525000.00, 0.00, 'Angsuran ke-8 - Laila Amalia', 52, '2026-02-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(51, 'masuk', 'pembayaran_angsuran', 'pinjaman', 520000.00, 0.00, 'Angsuran ke-9 - Laila Amalia', 53, '2026-03-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(52, 'masuk', 'pembayaran_angsuran', 'pinjaman', 515000.00, 0.00, 'Angsuran ke-10 - Laila Amalia', 54, '2026-04-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(53, 'masuk', 'pembayaran_angsuran', 'pinjaman', 510000.00, 0.00, 'Angsuran ke-11 - Laila Amalia', 55, '2026-05-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(54, 'masuk', 'pembayaran_angsuran', 'pinjaman', 505000.00, 0.00, 'Angsuran ke-12 - Laila Amalia', 56, '2026-06-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(55, 'keluar', 'pencairan_pinjaman', 'pinjaman', 2500000.00, 0.00, 'Pencairan pinjaman - Citra Ramadhani', 16, '2025-12-21', 3, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(56, 'masuk', 'pembayaran_angsuran', 'pinjaman', 441666.67, 0.00, 'Angsuran ke-1 - Citra Ramadhani', 57, '2026-01-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(57, 'masuk', 'pembayaran_angsuran', 'pinjaman', 437500.00, 0.00, 'Angsuran ke-2 - Citra Ramadhani', 58, '2026-02-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(58, 'masuk', 'pembayaran_angsuran', 'pinjaman', 433333.33, 0.00, 'Angsuran ke-3 - Citra Ramadhani', 59, '2026-03-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(59, 'masuk', 'pembayaran_angsuran', 'pinjaman', 429166.67, 0.00, 'Angsuran ke-4 - Citra Ramadhani', 60, '2026-04-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(60, 'masuk', 'pembayaran_angsuran', 'pinjaman', 425000.00, 0.00, 'Angsuran ke-5 - Citra Ramadhani', 61, '2026-05-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(61, 'masuk', 'pembayaran_angsuran', 'pinjaman', 420833.33, 0.00, 'Angsuran ke-6 - Citra Ramadhani', 62, '2026-06-21', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(62, 'masuk', 'topup_bulanan', 'pinjaman', 20000000.00, 0.00, 'Topup saldo koperasi', 990001, '2026-04-02', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(63, 'masuk', 'topup_bulanan', 'pinjaman', 15000000.00, 0.00, 'Topup saldo koperasi', 990002, '2026-06-02', 2, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(64, 'keluar', 'pengeluaran_koperasi', 'pinjaman', 1250000.00, 230000000.00, 'perbaikan ruangan koperasi', 1, '2026-08-18', 1, '2026-08-17 16:56:32', '2026-08-17 16:56:32');

-- --------------------------------------------------------

--
-- Table structure for table `kas_koperasi`
--

CREATE TABLE `kas_koperasi` (
  `id` bigint UNSIGNED NOT NULL,
  `saldo_pinjaman` decimal(15,2) NOT NULL DEFAULT '0.00',
  `saldo_dana_sosial` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kas_koperasi`
--

INSERT INTO `kas_koperasi` (`id`, `saldo_pinjaman`, `saldo_dana_sosial`, `created_at`, `updated_at`) VALUES
(1, 230000000.00, 3000000.00, '2026-08-17 16:53:53', '2026-08-17 16:56:32');

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
(30, '2026_08_16_100844_add_saldo_awal_kategori_to_jurnal_kas_table', 1);

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

--
-- Dumping data for table `pengeluaran`
--

INSERT INTO `pengeluaran` (`id`, `jenis`, `jumlah`, `keterangan`, `tanggal`, `input_by`, `created_at`, `updated_at`) VALUES
(1, 'koperasi', 1250000.00, 'perbaikan ruangan koperasi', '2026-08-18', 1, '2026-08-17 16:56:32', '2026-08-17 16:56:32');

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
(1, 'anggota.lihat', 'web', '2026-08-17 16:53:38', '2026-08-17 16:53:38'),
(2, 'anggota.kelola', 'web', '2026-08-17 16:53:38', '2026-08-17 16:53:38'),
(3, 'simpanan.lihat', 'web', '2026-08-17 16:53:38', '2026-08-17 16:53:38'),
(4, 'simpanan.konfirmasi', 'web', '2026-08-17 16:53:38', '2026-08-17 16:53:38'),
(5, 'pinjaman.lihat', 'web', '2026-08-17 16:53:38', '2026-08-17 16:53:38'),
(6, 'pinjaman.tinjau-bendahara', 'web', '2026-08-17 16:53:38', '2026-08-17 16:53:38'),
(7, 'pinjaman.approve-ketua', 'web', '2026-08-17 16:53:38', '2026-08-17 16:53:38'),
(8, 'angsuran.konfirmasi', 'web', '2026-08-17 16:53:38', '2026-08-17 16:53:38'),
(9, 'kas.lihat', 'web', '2026-08-17 16:53:38', '2026-08-17 16:53:38'),
(10, 'kas.topup', 'web', '2026-08-17 16:53:38', '2026-08-17 16:53:38'),
(11, 'laporan.lihat', 'web', '2026-08-17 16:53:38', '2026-08-17 16:53:38'),
(12, 'pengaturan.kelola', 'web', '2026-08-17 16:53:38', '2026-08-17 16:53:38'),
(13, 'portal.akses', 'web', '2026-08-17 16:53:38', '2026-08-17 16:53:38');

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

INSERT INTO `pinjaman` (`id`, `anggota_id`, `pengaju_user_id`, `nominal`, `tenor_bulan`, `keperluan`, `snapshot_bank`, `snapshot_no_rekening`, `snapshot_atas_nama`, `persentase_bunga`, `status`, `cair_oleh_bendahara`, `sudah_pakai_privilege_reloan`, `tanggal_pengajuan`, `tanggal_pencairan`, `catatan_bendahara`, `catatan_ketua`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 1000000.00, 3, 'Kebutuhan harian', 'BCA', '1234001001', 'Budi Santoso', 1.00, 'diajukan', 0, 0, '2026-08-16', NULL, NULL, NULL, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(2, 2, NULL, 2000000.00, 4, 'Biaya pendidikan anak', 'Mandiri', '8213400220', 'Siti Aminah', 1.00, 'aktif', 0, 0, '2026-05-18', '2026-05-21', NULL, NULL, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(3, 3, NULL, 3000000.00, 6, 'Perbaikan rumah', 'BRI', '72810033', 'Ahmad Ridwan', 1.00, 'lunas', 0, 0, '2025-12-18', '2025-12-21', NULL, NULL, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(4, 4, NULL, 5000000.00, 12, 'Pembelian kendaraan', 'BNI', '20987654', 'Dewi Lestari', 1.00, 'aktif', 0, 0, '2025-10-18', '2025-10-21', NULL, NULL, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(5, 6, NULL, 1500000.00, 4, 'Kebutuhan hari raya', 'BCA', '1234002002', 'Agus Wijaya', 1.00, 'diajukan', 0, 0, '2026-08-17', NULL, NULL, NULL, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(6, 16, NULL, 2500000.00, 6, 'Biaya pendidikan anak', 'Mandiri', '8213400221', 'Adi Nugroho', 1.00, 'diajukan', 0, 0, '2026-08-15', NULL, NULL, NULL, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(7, 26, NULL, 5000000.00, 12, 'Perbaikan rumah', 'BRI', '72810034', 'Deni Setiawan', 1.00, 'diajukan', 0, 0, '2026-08-13', NULL, NULL, NULL, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(8, 8, NULL, 3500000.00, 9, 'Biaya pengobatan', 'BNI', '20987655', 'Maya Sari', 1.00, 'approved_bendahara', 0, 0, '2026-08-10', NULL, 'Verifikasi dokumen lengkap, layak diteruskan ke Ketua.', NULL, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(9, 18, NULL, 6000000.00, 12, 'Pembelian kendaraan', 'Bank Kalsel', '55990011', 'Yudha Pradana', 1.00, 'approved_bendahara', 0, 0, '2026-08-08', NULL, 'Riwayat angsuran baik, disetujui.', NULL, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(10, 28, NULL, 2000000.00, 4, 'Modal usaha', 'BCA', '1234003003', 'Galih Prakoso', 1.00, 'approved_bendahara', 0, 0, '2026-08-06', NULL, 'Dokumen sesuai ketentuan.', NULL, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(11, 7, NULL, 1000000.00, 3, 'Perlengkapan rumah tangga', 'BCA', '1234004004', 'Hendra Gunawan', 1.00, 'aktif', 0, 0, '2026-06-18', '2026-06-21', NULL, NULL, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(12, 17, NULL, 2000000.00, 4, 'Biaya pendidikan anak', 'Mandiri', '8213400222', 'Indah Permata', 1.00, 'aktif', 0, 0, '2026-05-18', '2026-05-21', NULL, NULL, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(13, 10, NULL, 3000000.00, 6, 'Perbaikan rumah', 'BRI', '72810035', 'Joko Susanto', 1.00, 'aktif', 0, 0, '2026-03-18', '2026-03-21', NULL, NULL, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(14, 30, NULL, 4000000.00, 9, 'Modal usaha', 'BNI', '20987656', 'Ferry Ardiansyah', 1.00, 'lunas', 0, 0, '2025-10-18', '2025-10-21', NULL, NULL, '2026-08-17 16:54:01', '2026-08-17 16:54:01'),
(15, 40, NULL, 6000000.00, 12, 'Pembelian kendaraan', 'Bank Kalsel', '55990012', 'Candra Wijaya', 1.00, 'lunas', 0, 0, '2025-06-18', '2025-06-21', NULL, NULL, '2026-08-17 16:54:02', '2026-08-17 16:54:02'),
(16, 50, NULL, 2500000.00, 6, 'Kebutuhan hari raya', 'BCA', '1234005005', 'Citra Ramadhani', 1.00, 'lunas', 0, 0, '2025-12-18', '2025-12-21', NULL, NULL, '2026-08-17 16:54:02', '2026-08-17 16:54:02');

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
(1, 'admin', 'web', '2026-08-17 16:53:38', '2026-08-17 16:53:38'),
(2, 'bendahara', 'web', '2026-08-17 16:53:38', '2026-08-17 16:53:38'),
(3, 'ketua_koperasi', 'web', '2026-08-17 16:53:39', '2026-08-17 16:53:39'),
(4, 'anggota', 'web', '2026-08-17 16:53:39', '2026-08-17 16:53:39');

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
(1, 2),
(3, 2),
(4, 2),
(5, 2),
(6, 2),
(8, 2),
(9, 2),
(10, 2),
(11, 2),
(13, 2),
(1, 3),
(3, 3),
(5, 3),
(7, 3),
(9, 3),
(11, 3),
(13, 3),
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
(1, 1.00, '2026-01-01', '2026-08-17 16:53:53', '2026-08-17 16:53:53');

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
(1, 'kurang_1_tahun', 'Anggota < 1 Tahun', 1000000.00, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(2, 'satu_sampai_3_tahun', 'Anggota 1-3 Tahun', 5000000.00, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(3, 'tiga_sampai_5_tahun', 'Anggota 3-5 Tahun', 7000000.00, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(4, 'lebih_5_tahun', 'Anggota > 5 Tahun', 10000000.00, '2026-08-17 16:53:53', '2026-08-17 16:53:53');

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
(1, 'pokok', 'Simpanan Pokok', 50000.00, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(2, 'wajib', 'Simpanan Wajib', 45000.00, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(3, 'dana_sosial', 'Dana Sosial', 5000.00, '2026-08-17 16:53:53', '2026-08-17 16:53:53');

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
(1, 1, 'pokok', 50000.00, '2026-02', '2026-02-18', 4, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(2, 2, 'pokok', 50000.00, '2023-08', '2023-08-18', 5, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(3, 3, 'pokok', 50000.00, '2020-08', '2020-08-18', 6, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(4, 4, 'pokok', 50000.00, '2019-08', '2019-08-18', 7, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(5, 5, 'pokok', 50000.00, '2026-04', '2026-04-18', 8, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(6, 6, 'pokok', 50000.00, '2024-07', '2024-07-18', 9, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(7, 7, 'pokok', 50000.00, '2023-06', '2023-06-18', 10, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(8, 8, 'pokok', 50000.00, '2018-05', '2018-05-18', 11, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(9, 9, 'pokok', 50000.00, '2025-12', '2025-12-18', 12, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(10, 10, 'pokok', 50000.00, '2024-03', '2024-03-18', 13, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(11, 11, 'pokok', 50000.00, '2023-02', '2023-02-18', 14, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(12, 12, 'pokok', 50000.00, '2020-06', '2020-06-18', 15, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(13, 13, 'pokok', 50000.00, '2026-01', '2026-01-18', 16, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(14, 14, 'pokok', 50000.00, '2023-11', '2023-11-18', 17, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(15, 15, 'pokok', 50000.00, '2023-05', '2023-05-18', 18, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(16, 16, 'pokok', 50000.00, '2016-07', '2016-07-18', 19, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(17, 17, 'pokok', 50000.00, '2026-02', '2026-02-18', 20, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(18, 18, 'pokok', 50000.00, '2024-06', '2024-06-18', 21, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(19, 19, 'pokok', 50000.00, '2023-08', '2023-08-18', 22, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(20, 20, 'pokok', 50000.00, '2018-08', '2018-08-18', 23, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(21, 21, 'pokok', 50000.00, '2026-03', '2026-03-18', 24, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(22, 22, 'pokok', 50000.00, '2024-02', '2024-02-18', 25, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(23, 23, 'pokok', 50000.00, '2023-04', '2023-04-18', 26, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(24, 24, 'pokok', 50000.00, '2020-04', '2020-04-18', 27, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(25, 25, 'pokok', 50000.00, '2026-04', '2026-04-18', 28, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(26, 26, 'pokok', 50000.00, '2023-10', '2023-10-18', 29, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(27, 27, 'pokok', 50000.00, '2023-07', '2023-07-18', 30, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(28, 28, 'pokok', 50000.00, '2016-05', '2016-05-18', 31, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(29, 29, 'pokok', 50000.00, '2025-12', '2025-12-18', 32, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(30, 30, 'pokok', 50000.00, '2024-05', '2024-05-18', 33, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(31, 31, 'pokok', 50000.00, '2023-03', '2023-03-18', 34, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(32, 32, 'pokok', 50000.00, '2018-06', '2018-06-18', 35, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(33, 33, 'pokok', 50000.00, '2026-01', '2026-01-18', 36, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(34, 34, 'pokok', 50000.00, '2024-01', '2024-01-18', 37, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(35, 35, 'pokok', 50000.00, '2023-06', '2023-06-18', 38, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(36, 36, 'pokok', 50000.00, '2020-07', '2020-07-18', 39, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(37, 37, 'pokok', 50000.00, '2026-02', '2026-02-18', 40, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(38, 38, 'pokok', 50000.00, '2024-08', '2024-08-18', 41, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(39, 39, 'pokok', 50000.00, '2023-02', '2023-02-18', 42, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(40, 40, 'pokok', 50000.00, '2016-08', '2016-08-18', 43, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(41, 41, 'pokok', 50000.00, '2026-03', '2026-03-18', 44, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(42, 42, 'pokok', 50000.00, '2024-04', '2024-04-18', 45, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(43, 43, 'pokok', 50000.00, '2023-05', '2023-05-18', 46, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(44, 44, 'pokok', 50000.00, '2018-04', '2018-04-18', 47, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(45, 45, 'pokok', 50000.00, '2026-04', '2026-04-18', 48, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(46, 46, 'pokok', 50000.00, '2023-12', '2023-12-18', 49, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(47, 47, 'pokok', 50000.00, '2023-08', '2023-08-18', 50, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(48, 48, 'pokok', 50000.00, '2020-05', '2020-05-18', 51, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(49, 49, 'pokok', 50000.00, '2025-12', '2025-12-18', 52, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(50, 50, 'pokok', 50000.00, '2024-07', '2024-07-18', 53, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(51, 51, 'pokok', 50000.00, '2019-08', '2019-08-18', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(52, 52, 'pokok', 50000.00, '2017-08', '2017-08-18', 3, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(53, 1, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(54, 1, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(55, 1, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(56, 1, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(57, 1, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(58, 1, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(59, 1, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(60, 1, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(61, 1, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(62, 1, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(63, 1, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(64, 1, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(65, 2, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(66, 2, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(67, 2, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(68, 2, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(69, 2, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(70, 2, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(71, 2, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(72, 2, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(73, 2, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(74, 2, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(75, 2, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(76, 2, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(77, 3, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(78, 3, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(79, 3, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(80, 3, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(81, 3, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(82, 3, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(83, 3, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(84, 3, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(85, 3, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(86, 3, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(87, 3, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(88, 3, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(89, 4, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(90, 4, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(91, 4, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(92, 4, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(93, 4, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(94, 4, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(95, 4, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(96, 4, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(97, 4, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(98, 4, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(99, 4, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(100, 4, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(101, 5, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(102, 5, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(103, 5, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(104, 5, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(105, 5, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(106, 5, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(107, 5, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(108, 5, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(109, 6, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:54', '2026-08-17 16:53:54'),
(110, 6, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(111, 6, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(112, 6, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(113, 6, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(114, 6, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(115, 6, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(116, 6, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(117, 6, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(118, 6, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(119, 6, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(120, 6, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(121, 7, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(122, 7, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(123, 7, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(124, 7, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(125, 7, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(126, 7, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(127, 7, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(128, 7, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(129, 7, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(130, 7, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(131, 7, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(132, 7, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(133, 8, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(134, 8, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(135, 8, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(136, 8, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(137, 8, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(138, 8, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(139, 8, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(140, 8, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(141, 8, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(142, 8, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(143, 8, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(144, 8, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(145, 9, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(146, 9, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(147, 9, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(148, 9, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(149, 9, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(150, 9, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(151, 9, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(152, 9, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(153, 9, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(154, 9, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(155, 9, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(156, 9, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(157, 10, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(158, 10, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(159, 10, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(160, 10, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(161, 10, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(162, 10, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(163, 10, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(164, 10, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(165, 10, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(166, 10, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(167, 11, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(168, 11, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(169, 11, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(170, 11, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(171, 11, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(172, 11, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(173, 11, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(174, 11, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(175, 11, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(176, 11, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(177, 11, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(178, 11, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(179, 12, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(180, 12, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(181, 12, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(182, 12, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(183, 12, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(184, 12, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(185, 12, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(186, 12, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(187, 12, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(188, 12, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(189, 12, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(190, 12, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(191, 14, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(192, 14, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(193, 14, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(194, 14, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(195, 14, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(196, 14, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(197, 14, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(198, 14, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(199, 14, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(200, 14, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(201, 14, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(202, 14, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(203, 15, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(204, 15, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(205, 15, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(206, 15, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(207, 15, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(208, 15, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(209, 15, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(210, 15, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(211, 15, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:55', '2026-08-17 16:53:55'),
(212, 15, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(213, 16, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(214, 16, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(215, 16, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(216, 16, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(217, 16, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(218, 16, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(219, 16, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(220, 16, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(221, 16, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(222, 16, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(223, 16, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(224, 16, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(225, 17, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(226, 17, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(227, 17, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(228, 17, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(229, 17, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(230, 17, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(231, 17, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(232, 17, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(233, 17, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(234, 17, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(235, 17, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(236, 17, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(237, 18, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(238, 18, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(239, 18, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(240, 18, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(241, 18, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(242, 18, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(243, 18, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(244, 18, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(245, 18, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(246, 18, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(247, 18, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(248, 18, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(249, 19, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(250, 19, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(251, 19, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(252, 19, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(253, 19, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(254, 19, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(255, 19, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(256, 19, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(257, 19, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(258, 19, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(259, 19, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(260, 19, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(261, 20, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(262, 20, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(263, 20, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(264, 20, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(265, 20, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(266, 20, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(267, 20, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(268, 20, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(269, 20, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(270, 20, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(271, 21, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(272, 21, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(273, 21, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(274, 21, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(275, 21, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(276, 21, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(277, 21, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(278, 21, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(279, 21, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(280, 21, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(281, 21, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(282, 21, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(283, 22, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(284, 22, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(285, 22, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(286, 22, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(287, 22, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(288, 22, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(289, 22, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(290, 22, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(291, 22, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(292, 22, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(293, 22, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(294, 22, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(295, 23, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(296, 23, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(297, 23, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(298, 23, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(299, 23, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(300, 23, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(301, 23, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(302, 23, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(303, 23, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(304, 23, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(305, 23, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(306, 23, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(307, 24, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(308, 24, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(309, 24, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(310, 24, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(311, 24, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(312, 24, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(313, 24, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(314, 24, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:56', '2026-08-17 16:53:56'),
(315, 24, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(316, 24, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(317, 24, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(318, 24, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(319, 25, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(320, 25, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(321, 25, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(322, 25, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(323, 25, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(324, 25, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(325, 25, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(326, 25, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(327, 26, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(328, 26, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(329, 26, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(330, 26, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(331, 26, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(332, 26, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(333, 26, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(334, 26, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(335, 26, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(336, 26, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(337, 26, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(338, 26, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(339, 28, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(340, 28, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(341, 28, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(342, 28, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(343, 28, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(344, 28, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(345, 28, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(346, 28, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(347, 28, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(348, 28, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(349, 28, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(350, 28, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(351, 29, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(352, 29, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(353, 29, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(354, 29, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(355, 29, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(356, 29, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(357, 29, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(358, 29, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(359, 29, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(360, 29, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(361, 29, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(362, 29, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(363, 30, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(364, 30, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(365, 30, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(366, 30, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(367, 30, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(368, 30, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(369, 30, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(370, 30, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(371, 30, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(372, 30, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(373, 30, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(374, 30, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(375, 31, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(376, 31, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(377, 31, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(378, 31, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(379, 31, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(380, 31, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(381, 31, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(382, 31, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(383, 31, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(384, 31, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(385, 31, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(386, 31, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(387, 32, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(388, 32, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(389, 32, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(390, 32, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(391, 32, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(392, 32, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(393, 32, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(394, 32, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(395, 32, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(396, 32, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(397, 32, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(398, 32, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(399, 33, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(400, 33, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(401, 33, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(402, 33, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(403, 33, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:57', '2026-08-17 16:53:57'),
(404, 33, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(405, 33, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(406, 33, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(407, 33, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(408, 33, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(409, 33, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(410, 33, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(411, 34, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(412, 34, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(413, 34, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(414, 34, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(415, 34, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(416, 34, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(417, 34, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(418, 34, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(419, 34, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(420, 34, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(421, 34, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(422, 34, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(423, 35, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(424, 35, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(425, 35, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(426, 35, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(427, 35, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(428, 35, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(429, 35, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(430, 35, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(431, 35, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(432, 35, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(433, 35, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(434, 35, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(435, 36, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(436, 36, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(437, 36, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(438, 36, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(439, 36, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(440, 36, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(441, 36, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(442, 36, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(443, 36, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(444, 36, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(445, 36, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(446, 36, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(447, 37, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(448, 37, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(449, 37, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(450, 37, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(451, 37, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(452, 37, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(453, 37, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(454, 37, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(455, 37, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(456, 37, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(457, 37, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(458, 37, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(459, 38, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(460, 38, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(461, 38, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(462, 38, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(463, 38, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(464, 38, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(465, 38, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(466, 38, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(467, 38, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(468, 38, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(469, 38, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(470, 38, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(471, 39, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(472, 39, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(473, 39, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(474, 39, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(475, 39, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(476, 39, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(477, 39, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(478, 39, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(479, 39, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(480, 39, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58');
INSERT INTO `simpanan` (`id`, `anggota_id`, `jenis`, `jumlah`, `bulan_periode`, `tanggal_input`, `input_by`, `created_at`, `updated_at`) VALUES
(481, 39, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(482, 39, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(483, 40, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(484, 40, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(485, 40, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(486, 40, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(487, 40, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(488, 40, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(489, 40, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(490, 40, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(491, 40, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(492, 40, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(493, 40, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(494, 40, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:58', '2026-08-17 16:53:58'),
(495, 41, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(496, 41, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(497, 41, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(498, 41, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(499, 41, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(500, 41, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(501, 41, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(502, 41, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(503, 41, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(504, 41, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(505, 41, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(506, 41, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(507, 42, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(508, 42, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(509, 42, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(510, 42, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(511, 42, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(512, 42, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(513, 42, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(514, 42, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(515, 42, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(516, 42, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(517, 42, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(518, 42, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(519, 43, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(520, 43, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(521, 43, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(522, 43, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(523, 43, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(524, 43, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(525, 43, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(526, 43, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(527, 43, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(528, 43, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(529, 43, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(530, 43, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(531, 44, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(532, 44, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(533, 44, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(534, 44, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(535, 44, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(536, 44, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(537, 44, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(538, 44, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(539, 44, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(540, 44, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(541, 44, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(542, 44, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(543, 45, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(544, 45, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(545, 45, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(546, 45, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(547, 45, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(548, 45, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(549, 45, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(550, 45, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(551, 45, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(552, 45, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(553, 46, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(554, 46, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(555, 46, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(556, 46, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(557, 46, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(558, 46, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(559, 46, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(560, 46, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(561, 46, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(562, 46, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(563, 46, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(564, 46, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(565, 47, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(566, 47, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(567, 47, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(568, 47, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(569, 47, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(570, 47, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(571, 47, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(572, 47, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(573, 47, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(574, 47, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(575, 47, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(576, 47, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(577, 48, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(578, 48, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(579, 48, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(580, 48, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(581, 48, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(582, 48, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(583, 48, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(584, 48, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(585, 48, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(586, 48, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:53:59', '2026-08-17 16:53:59'),
(587, 48, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(588, 48, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(589, 49, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(590, 49, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(591, 49, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(592, 49, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(593, 49, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(594, 49, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(595, 49, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(596, 49, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(597, 49, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(598, 49, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(599, 49, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(600, 49, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(601, 50, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(602, 50, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(603, 50, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(604, 50, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(605, 50, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(606, 50, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(607, 50, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(608, 50, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(609, 50, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(610, 50, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(611, 50, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(612, 50, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(613, 51, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(614, 51, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(615, 51, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(616, 51, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(617, 51, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(618, 51, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(619, 51, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(620, 51, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(621, 51, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(622, 51, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(623, 51, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(624, 51, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(625, 52, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(626, 52, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(627, 52, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(628, 52, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(629, 52, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(630, 52, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(631, 52, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(632, 52, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(633, 52, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(634, 52, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(635, 52, 'wajib', 45000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00'),
(636, 52, 'dana_sosial', 5000.00, '2026-08', '2026-08-18', 2, '2026-08-17 16:54:00', '2026-08-17 16:54:00');

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
(1, 0.00, 1000000.00, 3, '2026-08-17 16:53:52', '2026-08-17 16:53:52'),
(2, 1000001.00, 2000000.00, 4, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(3, 2000001.00, 3000000.00, 6, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(4, 3000001.00, 4000000.00, 9, '2026-08-17 16:53:53', '2026-08-17 16:53:53'),
(5, 4000001.00, 10000000.00, 12, '2026-08-17 16:53:53', '2026-08-17 16:53:53');

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
(1, 'Admin Koperasi', 'admin@koperasi.test', 'ADM-000001', NULL, 'local', NULL, '$2y$12$VNr9JNTVr9H5fjOOQe77ce2APmrlJ.TCP1nuZbsAqRpVQgorjEzSa', 0, NULL, '2026-08-17 16:53:39', '2026-08-17 16:53:39'),
(2, 'Bendahara Koperasi', 'bendahara@koperasi.test', 'BEN-000001', NULL, 'local', NULL, '$2y$12$HZEJ/c1tCGFgsxkxaE5nEeVHKyni7G2d5DUUpp4r1dgIkB2b1MCdy', 0, NULL, '2026-08-17 16:53:39', '2026-08-17 16:53:39'),
(3, 'Ketua Koperasi', 'ketua@koperasi.test', 'KET-000001', NULL, 'local', NULL, '$2y$12$jr6iKcOMsiuQ/DRSpishlO7TvJoboV3hgRFNsMom8eXyHSa70FKuy', 0, NULL, '2026-08-17 16:53:39', '2026-08-17 16:53:39'),
(4, 'Anggota Baru', 'anggota.baru@koperasi.test', 'TOP-100001', NULL, 'local', NULL, '$2y$12$innxVo78fYnQVn914JLE5.dbFDasBwb2U.GIo0RqNPUszY.vGAqna', 0, NULL, '2026-08-17 16:53:40', '2026-08-17 16:53:40'),
(5, 'Anggota Sedang', 'anggota.sedang@koperasi.test', 'TOP-100002', NULL, 'local', NULL, '$2y$12$r.Cpi/lbXWOoqct8EgpPU.zTTPFF0hAw1UPDFutpfvxlttpeaEnWm', 0, NULL, '2026-08-17 16:53:40', '2026-08-17 16:53:40'),
(6, 'Anggota Lama', 'anggota.lama@koperasi.test', 'TOP-100003', NULL, 'local', NULL, '$2y$12$.gsdykfvYNSOmSzi7k/O3exG6HonSnLnoNGp9GUNFZSVTBAdoGvWe', 0, NULL, '2026-08-17 16:53:40', '2026-08-17 16:53:40'),
(7, 'Anggota Reloan', 'anggota.reloan@koperasi.test', 'TOP-100004', NULL, 'local', NULL, '$2y$12$G4pBD/D1uPjlq1ZcdGCVgegDwEvuel.aJpzn7q//nOWCv1S8bkOFG', 0, NULL, '2026-08-17 16:53:40', '2026-08-17 16:53:40'),
(8, 'Agus Wijaya', 'anggota.aguswijaya@koperasi.test', 'TOP-100005', NULL, 'local', NULL, '$2y$12$MqNxrpjjVNmjiJQBxotT0e0CCgo1jW3feqPppqjYbguBhnuC/DyZ6', 0, NULL, '2026-08-17 16:53:41', '2026-08-17 16:53:41'),
(9, 'Rina Marlina', 'anggota.rinamarlina@koperasi.test', 'TOP-100006', NULL, 'local', NULL, '$2y$12$mUFW6dyF7vYvffVC4OYXfuzHbgUpVeeyjD3Q9n2kMK3M8AkV8U7gu', 0, NULL, '2026-08-17 16:53:41', '2026-08-17 16:53:41'),
(10, 'Bambang Sutrisno', 'anggota.bambangsutrisno@koperasi.test', 'TOP-100007', NULL, 'local', NULL, '$2y$12$Jtqzu1u0geMTfbQ1sPE9i.kZ.4K8cHpOzIS1MqwjjH4UxIGfSEHzy', 0, NULL, '2026-08-17 16:53:41', '2026-08-17 16:53:41'),
(11, 'Sari Rahayu', 'anggota.sarirahayu@koperasi.test', 'TOP-100008', NULL, 'local', NULL, '$2y$12$yhBRIIaYUNYCn8b3ZldieuXYF83riEYg7cSLNIdUk374eCcnyO28.', 0, NULL, '2026-08-17 16:53:42', '2026-08-17 16:53:42'),
(12, 'Hendra Gunawan', 'anggota.hendragunawan@koperasi.test', 'TOP-100009', NULL, 'local', NULL, '$2y$12$kkWduKueoqux/LnRT4AD4.HxmLfYS8Ku0umY6/qe34SIu9193tp2.', 0, NULL, '2026-08-17 16:53:42', '2026-08-17 16:53:42'),
(13, 'Dewi Anggraini', 'anggota.dewianggraini@koperasi.test', 'TOP-100010', NULL, 'local', NULL, '$2y$12$HVgz71PqfkHxbtmnBIZztOadsTWGtImBFTkgZsfV51YkLoGW5i8zi', 0, NULL, '2026-08-17 16:53:42', '2026-08-17 16:53:42'),
(14, 'Joko Susanto', 'anggota.jokosusanto@koperasi.test', 'TOP-100011', NULL, 'local', NULL, '$2y$12$LEd/..RmnMTCpVBs3yxuhO4eU9QsVQIew7m7iovep89Nzf8DKSPz2', 0, NULL, '2026-08-17 16:53:42', '2026-08-17 16:53:42'),
(15, 'Maya Sari', 'anggota.mayasari@koperasi.test', 'TOP-100012', NULL, 'local', NULL, '$2y$12$iBGPBPUl9xSS0rG9qqRaAOVirJAdI2bR2QxZuIYBG.rKIB2S8QCsK', 0, NULL, '2026-08-17 16:53:43', '2026-08-17 16:53:43'),
(16, 'Adi Nugroho', 'anggota.adinugroho@koperasi.test', 'TOP-100013', NULL, 'local', NULL, '$2y$12$5Ny90o7.VGLUCuI/Jx9xdefDz3mcp/j/layiu2Lq08OyWvx30F45a', 0, NULL, '2026-08-17 16:53:43', '2026-08-17 16:53:43'),
(17, 'Lina Wijayanti', 'anggota.linawijayanti@koperasi.test', 'TOP-100014', NULL, 'local', NULL, '$2y$12$wMufNFCWln8E3KlS8o949.a2XupRt3Dh6o8mqFjcdXjUIpJapeBje', 0, NULL, '2026-08-17 16:53:43', '2026-08-17 16:53:43'),
(18, 'Rizky Pratama', 'anggota.rizkypratama@koperasi.test', 'TOP-100015', NULL, 'local', NULL, '$2y$12$eTfSWAVSaLIaiboTc6ciheJ1a5uvTrnAodN5hPAfSa0jCcc1Va5vW', 0, NULL, '2026-08-17 16:53:43', '2026-08-17 16:53:43'),
(19, 'Nia Kurniawati', 'anggota.niakurniawati@koperasi.test', 'TOP-100016', NULL, 'local', NULL, '$2y$12$d2zBl.Q2KA9.081ziTH0CO577FL3Mk8kSUN3Ny.OaHExKQUGq7VL2', 0, NULL, '2026-08-17 16:53:44', '2026-08-17 16:53:44'),
(20, 'Eko Prasetyo', 'anggota.ekoprasetyo@koperasi.test', 'TOP-100017', NULL, 'local', NULL, '$2y$12$YGPWvMEBr3B1K214kgP4m.9JK3vLWRxHSvn0AY4jyWevtehbU40ZO', 0, NULL, '2026-08-17 16:53:44', '2026-08-17 16:53:44'),
(21, 'Putri Handayani', 'anggota.putrihandayani@koperasi.test', 'TOP-100018', NULL, 'local', NULL, '$2y$12$rOeNJFzxWpYvz9yyZDbXc.jlAumBudZ96VfYDittOcVhWcsmhbAou', 0, NULL, '2026-08-17 16:53:44', '2026-08-17 16:53:44'),
(22, 'Fajar Ramadhan', 'anggota.fajarramadhan@koperasi.test', 'TOP-100019', NULL, 'local', NULL, '$2y$12$h7qxtxjw50i5Uq//KmlKrOhHz6wYWgEkNA3P3ntPfJag8Sd36tJtS', 0, NULL, '2026-08-17 16:53:44', '2026-08-17 16:53:44'),
(23, 'Indah Permata', 'anggota.indahpermata@koperasi.test', 'TOP-100020', NULL, 'local', NULL, '$2y$12$a2j37y6xLbMow063xGnKBeoJi5yoN7ndJdliQKJ6HbISQaxFmzvBa', 0, NULL, '2026-08-17 16:53:45', '2026-08-17 16:53:45'),
(24, 'Yudha Pradana', 'anggota.yudhapradana@koperasi.test', 'TOP-100021', NULL, 'local', NULL, '$2y$12$fT03TXKPaO8/4bJZ.T3gFuycHjZXl5O9j6JqIpAOT9WmwE8sX.j4C', 0, NULL, '2026-08-17 16:53:45', '2026-08-17 16:53:45'),
(25, 'Sri Wahyuni', 'anggota.sriwahyuni@koperasi.test', 'TOP-100022', NULL, 'local', NULL, '$2y$12$rwbTI9GPcxK2U1IZdrcjx.LD8KtCphXGZuUuSr91Xc6uRmj9mKd7u', 0, NULL, '2026-08-17 16:53:45', '2026-08-17 16:53:45'),
(26, 'Andi Firmansyah', 'anggota.andifirmansyah@koperasi.test', 'TOP-100023', NULL, 'local', NULL, '$2y$12$HDmjAqEoj0BvpQcUaenZGuWS..WTEztqQ.9LFGqknM1q4WC7gkIM2', 0, NULL, '2026-08-17 16:53:45', '2026-08-17 16:53:45'),
(27, 'Ratna Sari', 'anggota.ratnasari@koperasi.test', 'TOP-100024', NULL, 'local', NULL, '$2y$12$CAfnN4bG7BO9bNLhtH41vOE3xka.d4oweDS6mrAPopSaXzqsVeqeG', 0, NULL, '2026-08-17 16:53:46', '2026-08-17 16:53:46'),
(28, 'Deni Setiawan', 'anggota.denisetiawan@koperasi.test', 'TOP-100025', NULL, 'local', NULL, '$2y$12$RR54/IS8MrGiQNq.7OIO.u795Mv3prWoKShf9jy1KggNeBVQyzyzG', 0, NULL, '2026-08-17 16:53:46', '2026-08-17 16:53:46'),
(29, 'Fitriani', 'anggota.fitriani@koperasi.test', 'TOP-100026', NULL, 'local', NULL, '$2y$12$U/LsGGN0Qcet5yAt0.K75On45XPxaYb5M6NZDdIU.l1X9V3ypBGji', 0, NULL, '2026-08-17 16:53:46', '2026-08-17 16:53:46'),
(30, 'Rudi Hartono', 'anggota.rudihartono@koperasi.test', 'TOP-100027', NULL, 'local', NULL, '$2y$12$nDjIXYEzAmFsesUzpNbSFOEQxG6crT9KHaYqXXVOtTzfdjoLBOLi6', 0, NULL, '2026-08-17 16:53:46', '2026-08-17 16:53:46'),
(31, 'Susi Susanti', 'anggota.susisusanti@koperasi.test', 'TOP-100028', NULL, 'local', NULL, '$2y$12$ssl.fiUwNuT7jy9eX4efiOkHqCNbqQWEGAyE5npW1xfASGBKkFN0e', 0, NULL, '2026-08-17 16:53:47', '2026-08-17 16:53:47'),
(32, 'Bayu Saputra', 'anggota.bayusaputra@koperasi.test', 'TOP-100029', NULL, 'local', NULL, '$2y$12$efDSxWm8jhOEZUbGQJCjPe8OFayi6EPexvrDeQ4vZp2FhsHrn6y2m', 0, NULL, '2026-08-17 16:53:47', '2026-08-17 16:53:47'),
(33, 'Ayu Lestari', 'anggota.ayulestari@koperasi.test', 'TOP-100030', NULL, 'local', NULL, '$2y$12$6zIsdZ44pbQhwmHULkBm9O3DOO8ryeiIiLfuSbD9Sd1d/SirX5xZW', 0, NULL, '2026-08-17 16:53:47', '2026-08-17 16:53:47'),
(34, 'Toni Kurniawan', 'anggota.tonikurniawan@koperasi.test', 'TOP-100031', NULL, 'local', NULL, '$2y$12$9UdDChAALJQkCpp9K.y7huPAneHeQTWCWAJQUSdrRfzx.cbk36J4e', 0, NULL, '2026-08-17 16:53:48', '2026-08-17 16:53:48'),
(35, 'Tuti Herawati', 'anggota.tutiherawati@koperasi.test', 'TOP-100032', NULL, 'local', NULL, '$2y$12$IjpPOCDbe.Jyd3KDY8aY4OWa.nb6hsQQYgffQY7SZR.uuQ5mSikO2', 0, NULL, '2026-08-17 16:53:48', '2026-08-17 16:53:48'),
(36, 'Ferry Ardiansyah', 'anggota.ferryardiansyah@koperasi.test', 'TOP-100033', NULL, 'local', NULL, '$2y$12$AJ/X68WjCFWmt/5SSoUuFe2HPcUnD9Gpe1JIbQvfM87Iqgr0oUEHa', 0, NULL, '2026-08-17 16:53:48', '2026-08-17 16:53:48'),
(37, 'Desi Ratnasari', 'anggota.desiratnasari@koperasi.test', 'TOP-100034', NULL, 'local', NULL, '$2y$12$OnPQ49EIgn2NyWanYNswpeMUoA3MrK9vMhSBpvpL0ZOk/LTJ650sS', 0, NULL, '2026-08-17 16:53:48', '2026-08-17 16:53:48'),
(38, 'Imam Santoso', 'anggota.imamsantoso@koperasi.test', 'TOP-100035', NULL, 'local', NULL, '$2y$12$jps9GnVV3Pw4WwubBjqxuuDhzBTNYLU8jjivqE5r44W90QDEJS9EW', 0, NULL, '2026-08-17 16:53:49', '2026-08-17 16:53:49'),
(39, 'Widya Astuti', 'anggota.widyaastuti@koperasi.test', 'TOP-100036', NULL, 'local', NULL, '$2y$12$TYAq7bKPC3vICynA7x3vHeVL231lOP9FHpTxsyBBWf0i.d3lBvjGO', 0, NULL, '2026-08-17 16:53:49', '2026-08-17 16:53:49'),
(40, 'Galih Prakoso', 'anggota.galihprakoso@koperasi.test', 'TOP-100037', NULL, 'local', NULL, '$2y$12$yN0MCjRWBI0IWtkdLd9yHeBxXVplxblbD1N8WDVCRsvz/zt5fYtka', 0, NULL, '2026-08-17 16:53:49', '2026-08-17 16:53:49'),
(41, 'Nur Aini', 'anggota.nuraini@koperasi.test', 'TOP-100038', NULL, 'local', NULL, '$2y$12$hce52xrrImyri58WXOTIOeNoYucVmubQoUeuxpMBtoiXHKA6SZkx.', 0, NULL, '2026-08-17 16:53:49', '2026-08-17 16:53:49'),
(42, 'Satria Bima', 'anggota.satriabima@koperasi.test', 'TOP-100039', NULL, 'local', NULL, '$2y$12$Qi4DWE76M6/ciDtc6VXyMewU40prVnxEMtMkR6Z2qmxgp8MAWGHH.', 0, NULL, '2026-08-17 16:53:50', '2026-08-17 16:53:50'),
(43, 'Laila Amalia', 'anggota.lailaamalia@koperasi.test', 'TOP-100040', NULL, 'local', NULL, '$2y$12$iT1ZhcITYyzbDxG7.Y9CVuHkMhN8teNl4rITGYpJvEqy0mjxrPyI2', 0, NULL, '2026-08-17 16:53:50', '2026-08-17 16:53:50'),
(44, 'Wisnu Prasetyo', 'anggota.wisnuprasetyo@koperasi.test', 'TOP-100041', NULL, 'local', NULL, '$2y$12$vHSR/TNIBcODwFddqCG8peyiAbgnyOugSo2WL6qsSlI7NlTpaWBVy', 0, NULL, '2026-08-17 16:53:50', '2026-08-17 16:53:50'),
(45, 'Mega Puspita', 'anggota.megapuspita@koperasi.test', 'TOP-100042', NULL, 'local', NULL, '$2y$12$8k/k/4Y.uT4N4fDZ9rjf7.rIKBv0ilszWwHnX3MX9jIvoH4Q0/X/G', 0, NULL, '2026-08-17 16:53:50', '2026-08-17 16:53:50'),
(46, 'Dimas Anggara', 'anggota.dimasanggara@koperasi.test', 'TOP-100043', NULL, 'local', NULL, '$2y$12$BDiNyY/iJpycA0Tplv2icOge3NbcHWMLZxD9mXrEXXcXVb9hiQvNu', 0, NULL, '2026-08-17 16:53:51', '2026-08-17 16:53:51'),
(47, 'Nabila Putri', 'anggota.nabilaputri@koperasi.test', 'TOP-100044', NULL, 'local', NULL, '$2y$12$gZBGHVg.3X3YdhU7R3cZ5eW/7Kha9r3TAt.yohryEu0xa0D9PlHgi', 0, NULL, '2026-08-17 16:53:51', '2026-08-17 16:53:51'),
(48, 'Candra Wijaya', 'anggota.candrawijaya@koperasi.test', 'TOP-100045', NULL, 'local', NULL, '$2y$12$7m2Wvnl0s6dJox/JL9Fwpub4T8TnD.7IaO.5DGMsCy3nDcvO2xvWK', 0, NULL, '2026-08-17 16:53:51', '2026-08-17 16:53:51'),
(49, 'Yuni Astuti', 'anggota.yuniastuti@koperasi.test', 'TOP-100046', NULL, 'local', NULL, '$2y$12$5zSoRYOlhoZKQruAaKRKkeYocG8d7nuMA5OTuxNxHI5aXQRYj1wJK', 0, NULL, '2026-08-17 16:53:51', '2026-08-17 16:53:51'),
(50, 'Arif Hidayat', 'anggota.arifhidayat@koperasi.test', 'TOP-100047', NULL, 'local', NULL, '$2y$12$6GDrbXOC6x9OV0ZbP7sDYu4FIQvvny7gbHEad40PXdjNfa.aGqqDe', 0, NULL, '2026-08-17 16:53:52', '2026-08-17 16:53:52'),
(51, 'Rina Kusuma', 'anggota.rinakusuma@koperasi.test', 'TOP-100048', NULL, 'local', NULL, '$2y$12$0khuqKknrKyS7snyIJ0.OullhDqPMpDK3DNCwPqpIWSECJOLk88tm', 0, NULL, '2026-08-17 16:53:52', '2026-08-17 16:53:52'),
(52, 'Bagus Pamungkas', 'anggota.baguspamungkas@koperasi.test', 'TOP-100049', NULL, 'local', NULL, '$2y$12$0BtlUmMABGxk0QARjSqNoeeYTDCNZz.sE5o78Y.fBiePJaX6yCjIy', 0, NULL, '2026-08-17 16:53:52', '2026-08-17 16:53:52'),
(53, 'Citra Ramadhani', 'anggota.citraramadhani@koperasi.test', 'TOP-100050', NULL, 'local', NULL, '$2y$12$/XgRoR.ZV7okJmwqFnxvK.RIlO/XxsJZrNHv9J/B97Rv41MhyFN06', 0, NULL, '2026-08-17 16:53:52', '2026-08-17 16:53:52');

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
-- Indexes for table `pengajuan_limit`
--
ALTER TABLE `pengajuan_limit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pengajuan_limit_anggota_id_foreign` (`anggota_id`);

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
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `kas_koperasi`
--
ALTER TABLE `kas_koperasi`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `pengajuan_limit`
--
ALTER TABLE `pengajuan_limit`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pengeluaran`
--
ALTER TABLE `pengeluaran`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=637;

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
-- Constraints for table `pengajuan_limit`
--
ALTER TABLE `pengajuan_limit`
  ADD CONSTRAINT `pengajuan_limit_anggota_id_foreign` FOREIGN KEY (`anggota_id`) REFERENCES `anggota` (`id`) ON DELETE CASCADE;

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
