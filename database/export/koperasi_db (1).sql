-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 15, 2026 at 01:23 AM
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
(1, 4, 'ANG-2026-0001', 'TOP-100001', NULL, 'Budi Santoso', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'staff', '2025-11-14', '2026-02-14', 'aktif', NULL, NULL, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(2, 5, 'ANG-2023-0045', 'TOP-100002', NULL, 'Siti Aminah', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'hod', '2023-06-14', '2023-08-14', 'aktif', NULL, NULL, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(3, 6, 'ANG-2019-0012', 'TOP-100003', NULL, 'Ahmad Ridwan', 'Palangka', 'Operasional', 'Operasional', 'Gudang', 'staff', '2019-08-14', '2020-08-14', 'aktif', NULL, NULL, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(4, 7, 'ANG-2018-0003', 'TOP-100004', NULL, 'Dewi Lestari', 'Banjarmasin', 'Marketing', 'Marketing', 'Promosi', 'hod', '2018-08-14', '2019-08-14', 'aktif', NULL, NULL, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(5, 8, 'ANG-2026-0002', 'TOP-100005', '3207000000000000', 'Agus Wijaya', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2026-01-14', '2026-04-14', 'aktif', NULL, NULL, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(6, 9, 'ANG-2026-0003', 'TOP-100006', '3207000000000001', 'Rina Marlina', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2024-03-14', '2024-07-14', 'aktif', NULL, NULL, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(7, 10, 'ANG-2026-0004', 'TOP-100007', '3207000000000002', 'Bambang Sutrisno', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2023-01-14', '2023-06-14', 'aktif', NULL, NULL, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(8, 11, 'ANG-2026-0005', 'TOP-100008', '3207000000000003', 'Sari Rahayu', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2017-11-14', '2018-05-14', 'aktif', NULL, NULL, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(9, 12, 'ANG-2026-0006', 'TOP-100009', '3207000000000004', 'Hendra Gunawan', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2025-05-14', '2025-12-14', 'aktif', NULL, NULL, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(10, 13, 'ANG-2026-0007', 'TOP-100010', '3207000000000005', 'Dewi Anggraini', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2023-07-14', '2024-03-14', 'aktif', NULL, NULL, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(11, 14, 'ANG-2026-0008', 'TOP-100011', '3207000000000006', 'Joko Susanto', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2022-05-14', '2023-02-14', 'aktif', NULL, NULL, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(12, 15, 'ANG-2026-0009', 'TOP-100012', '3207000000000007', 'Maya Sari', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2019-08-14', '2020-06-14', 'aktif', NULL, NULL, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(13, 16, 'ANG-2026-0010', 'TOP-100013', '3207000000000008', 'Adi Nugroho', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2025-10-14', '2026-01-14', 'nonaktif', NULL, NULL, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(14, 17, 'ANG-2026-0011', 'TOP-100014', '3207000000000009', 'Lina Wijayanti', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2023-07-14', '2023-11-14', 'aktif', NULL, NULL, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(15, 18, 'ANG-2026-0012', 'TOP-100015', '3207000000000010', 'Rizky Pratama', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2022-12-14', '2023-05-14', 'aktif', NULL, NULL, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(16, 19, 'ANG-2026-0013', 'TOP-100016', '3207000000000011', 'Nia Kurniawati', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2016-01-14', '2016-07-14', 'aktif', NULL, NULL, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(17, 20, 'ANG-2026-0014', 'TOP-100017', '3207000000000012', 'Eko Prasetyo', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2025-07-14', '2026-02-14', 'aktif', NULL, NULL, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(18, 21, 'ANG-2026-0015', 'TOP-100018', '3207000000000013', 'Putri Handayani', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2023-10-14', '2024-06-14', 'aktif', NULL, NULL, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(19, 22, 'ANG-2026-0016', 'TOP-100019', '3207000000000014', 'Fajar Ramadhan', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2022-11-14', '2023-08-14', 'aktif', NULL, NULL, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(20, 23, 'ANG-2026-0017', 'TOP-100020', '3207000000000015', 'Indah Permata', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2017-10-14', '2018-08-14', 'aktif', NULL, NULL, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(21, 24, 'ANG-2026-0018', 'TOP-100021', '3207000000000016', 'Yudha Pradana', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2025-12-14', '2026-03-14', 'aktif', NULL, NULL, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(22, 25, 'ANG-2026-0019', 'TOP-100022', '3207000000000017', 'Sri Wahyuni', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2023-10-14', '2024-02-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(23, 26, 'ANG-2026-0020', 'TOP-100023', '3207000000000018', 'Andi Firmansyah', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2022-11-14', '2023-04-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(24, 27, 'ANG-2026-0021', 'TOP-100024', '3207000000000019', 'Ratna Sari', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2019-10-14', '2020-04-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(25, 28, 'ANG-2026-0022', 'TOP-100025', '3207000000000020', 'Deni Setiawan', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2025-09-14', '2026-04-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(26, 29, 'ANG-2026-0023', 'TOP-100026', '3207000000000021', 'Fitriani', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2023-02-14', '2023-10-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(27, 30, 'ANG-2026-0024', 'TOP-100027', '3207000000000022', 'Rudi Hartono', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2022-10-14', '2023-07-14', 'nonaktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(28, 31, 'ANG-2026-0025', 'TOP-100028', '3207000000000023', 'Susi Susanti', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2015-07-14', '2016-05-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(29, 32, 'ANG-2026-0026', 'TOP-100029', '3207000000000024', 'Bayu Saputra', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2025-09-14', '2025-12-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(30, 33, 'ANG-2026-0027', 'TOP-100030', '3207000000000025', 'Ayu Lestari', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2024-01-14', '2024-05-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(31, 34, 'ANG-2026-0028', 'TOP-100031', '3207000000000026', 'Toni Kurniawan', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2022-10-14', '2023-03-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(32, 35, 'ANG-2026-0029', 'TOP-100032', '3207000000000027', 'Tuti Herawati', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2017-12-14', '2018-06-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(33, 36, 'ANG-2026-0030', 'TOP-100033', '3207000000000028', 'Ferry Ardiansyah', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2025-06-14', '2026-01-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(34, 37, 'ANG-2026-0031', 'TOP-100034', '3207000000000029', 'Desi Ratnasari', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2023-05-14', '2024-01-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(35, 38, 'ANG-2026-0032', 'TOP-100035', '3207000000000030', 'Imam Santoso', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2022-09-14', '2023-06-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(36, 39, 'ANG-2026-0033', 'TOP-100036', '3207000000000031', 'Widya Astuti', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2019-09-14', '2020-07-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(37, 40, 'ANG-2026-0034', 'TOP-100037', '3207000000000032', 'Galih Prakoso', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2025-11-14', '2026-02-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(38, 41, 'ANG-2026-0035', 'TOP-100038', '3207000000000033', 'Nur Aini', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2024-04-14', '2024-08-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(39, 42, 'ANG-2026-0036', 'TOP-100039', '3207000000000034', 'Satria Bima', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2022-09-14', '2023-02-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(40, 43, 'ANG-2026-0037', 'TOP-100040', '3207000000000035', 'Laila Amalia', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2016-02-14', '2016-08-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(41, 44, 'ANG-2026-0038', 'TOP-100041', '3207000000000036', 'Wisnu Prasetyo', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2025-08-14', '2026-03-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(42, 45, 'ANG-2026-0039', 'TOP-100042', '3207000000000037', 'Mega Puspita', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2023-08-14', '2024-04-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(43, 46, 'ANG-2026-0040', 'TOP-100043', '3207000000000038', 'Dimas Anggara', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2022-08-14', '2023-05-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(44, 47, 'ANG-2026-0041', 'TOP-100044', '3207000000000039', 'Nabila Putri', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2017-06-14', '2018-04-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(45, 48, 'ANG-2026-0042', 'TOP-100045', '3207000000000040', 'Candra Wijaya', 'Samarinda', 'Teknologi', 'Teknologi', 'Gudang', 'staff', '2026-01-14', '2026-04-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(46, 49, 'ANG-2026-0043', 'TOP-100046', '3207000000000041', 'Yuni Astuti', 'Palangka', 'Produksi', 'Produksi', 'Dukungan', 'staff', '2023-08-14', '2023-12-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(47, 50, 'ANG-2026-0044', 'TOP-100047', '3207000000000042', 'Arif Hidayat', 'Banjarmasin', 'Operasional', 'Operasional', 'Lapangan', 'hod', '2023-03-14', '2023-08-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(48, 51, 'ANG-2026-0045', 'TOP-100048', '3207000000000043', 'Rina Kusuma', 'Samarinda', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2019-11-14', '2020-05-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(49, 52, 'ANG-2026-0046', 'TOP-100049', '3207000000000044', 'Bagus Pamungkas', 'Palangka', 'Marketing', 'Marketing', 'Promosi', 'staff', '2025-05-14', '2025-12-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(50, 53, 'ANG-2026-0047', 'TOP-100050', '3207000000000045', 'Citra Ramadhani', 'Banjarmasin', 'HRD', 'HRD', 'Umum', 'staff', '2023-11-14', '2024-07-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(51, 2, 'ANG-2020-0001', 'BEN-000001', NULL, 'Bendahara Koperasi', 'Banjarmasin', 'Keuangan', 'Keuangan', 'Akuntansi', 'staff', '2018-08-14', '2019-08-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(52, 3, 'ANG-2019-0001', 'KET-000001', NULL, 'Ketua Koperasi', 'Banjarmasin', 'Keuangan', 'Keuangan', 'Akuntansi', 'hod', '2016-08-14', '2017-08-14', 'aktif', NULL, NULL, '2026-08-13 22:48:00', '2026-08-13 22:48:00');

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
(1, 2, 1, 500000.00, 20000.00, 520000.00, 'lunas', '2026-06-17', '2026-06-17', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(2, 2, 2, 500000.00, 15000.00, 515000.00, 'lunas', '2026-07-17', '2026-07-17', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(3, 2, 3, 500000.00, 10000.00, 510000.00, 'belum_bayar', '2026-08-17', NULL, NULL, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(4, 2, 4, 500000.00, 5000.00, 505000.00, 'belum_bayar', '2026-09-17', NULL, NULL, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(5, 3, 1, 500000.00, 30000.00, 530000.00, 'lunas', '2026-01-17', '2026-01-17', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(6, 3, 2, 500000.00, 25000.00, 525000.00, 'lunas', '2026-02-17', '2026-02-17', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(7, 3, 3, 500000.00, 20000.00, 520000.00, 'lunas', '2026-03-17', '2026-03-17', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(8, 3, 4, 500000.00, 15000.00, 515000.00, 'lunas', '2026-04-17', '2026-04-17', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(9, 3, 5, 500000.00, 10000.00, 510000.00, 'lunas', '2026-05-17', '2026-05-17', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(10, 3, 6, 500000.00, 5000.00, 505000.00, 'lunas', '2026-06-17', '2026-06-17', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(11, 4, 1, 416666.67, 50000.00, 466666.67, 'lunas', '2025-11-17', '2025-11-17', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(12, 4, 2, 416666.67, 45833.33, 462500.00, 'lunas', '2025-12-17', '2025-12-17', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(13, 4, 3, 416666.67, 41666.67, 458333.33, 'lunas', '2026-01-17', '2026-01-17', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(14, 4, 4, 416666.67, 37500.00, 454166.67, 'lunas', '2026-02-17', '2026-02-17', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(15, 4, 5, 416666.67, 33333.33, 450000.00, 'lunas', '2026-03-17', '2026-03-17', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(16, 4, 6, 416666.67, 29166.67, 445833.33, 'lunas', '2026-04-17', '2026-04-17', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(17, 4, 7, 416666.67, 25000.00, 441666.67, 'lunas', '2026-05-17', '2026-05-17', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(18, 4, 8, 416666.67, 20833.33, 437500.00, 'lunas', '2026-06-17', '2026-06-17', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(19, 4, 9, 416666.67, 16666.67, 433333.33, 'lunas', '2026-07-17', '2026-07-17', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(20, 4, 10, 416666.67, 12500.00, 429166.67, 'lunas', '2026-08-17', '2026-08-17', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(21, 4, 11, 416666.67, 8333.33, 425000.00, 'belum_bayar', '2026-09-17', NULL, NULL, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(22, 4, 12, 416666.67, 4166.67, 420833.33, 'belum_bayar', '2026-10-17', NULL, NULL, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(23, 11, 1, 333333.33, 10000.00, 343333.33, 'lunas', '2026-07-17', '2026-07-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(24, 11, 2, 333333.33, 6666.67, 340000.00, 'belum_bayar', '2026-08-17', NULL, NULL, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(25, 11, 3, 333333.33, 3333.33, 336666.67, 'belum_bayar', '2026-09-17', NULL, NULL, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(26, 12, 1, 500000.00, 20000.00, 520000.00, 'lunas', '2026-06-17', '2026-06-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(27, 12, 2, 500000.00, 15000.00, 515000.00, 'lunas', '2026-07-17', '2026-07-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(28, 12, 3, 500000.00, 10000.00, 510000.00, 'belum_bayar', '2026-08-17', NULL, NULL, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(29, 12, 4, 500000.00, 5000.00, 505000.00, 'belum_bayar', '2026-09-17', NULL, NULL, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(30, 13, 1, 500000.00, 30000.00, 530000.00, 'lunas', '2026-04-17', '2026-04-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(31, 13, 2, 500000.00, 25000.00, 525000.00, 'lunas', '2026-05-17', '2026-05-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(32, 13, 3, 500000.00, 20000.00, 520000.00, 'lunas', '2026-06-17', '2026-06-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(33, 13, 4, 500000.00, 15000.00, 515000.00, 'belum_bayar', '2026-07-17', NULL, NULL, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(34, 13, 5, 500000.00, 10000.00, 510000.00, 'belum_bayar', '2026-08-17', NULL, NULL, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(35, 13, 6, 500000.00, 5000.00, 505000.00, 'belum_bayar', '2026-09-17', NULL, NULL, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(36, 14, 1, 444444.44, 40000.00, 484444.44, 'lunas', '2025-11-17', '2025-11-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(37, 14, 2, 444444.44, 35555.56, 480000.00, 'lunas', '2025-12-17', '2025-12-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(38, 14, 3, 444444.44, 31111.11, 475555.56, 'lunas', '2026-01-17', '2026-01-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(39, 14, 4, 444444.44, 26666.67, 471111.11, 'lunas', '2026-02-17', '2026-02-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(40, 14, 5, 444444.44, 22222.22, 466666.67, 'lunas', '2026-03-17', '2026-03-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(41, 14, 6, 444444.44, 17777.78, 462222.22, 'lunas', '2026-04-17', '2026-04-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(42, 14, 7, 444444.44, 13333.33, 457777.78, 'lunas', '2026-05-17', '2026-05-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(43, 14, 8, 444444.44, 8888.89, 453333.33, 'lunas', '2026-06-17', '2026-06-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(44, 14, 9, 444444.44, 4444.44, 448888.89, 'lunas', '2026-07-17', '2026-07-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(45, 15, 1, 500000.00, 60000.00, 560000.00, 'lunas', '2025-07-17', '2025-07-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(46, 15, 2, 500000.00, 55000.00, 555000.00, 'lunas', '2025-08-17', '2025-08-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(47, 15, 3, 500000.00, 50000.00, 550000.00, 'lunas', '2025-09-17', '2025-09-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(48, 15, 4, 500000.00, 45000.00, 545000.00, 'lunas', '2025-10-17', '2025-10-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(49, 15, 5, 500000.00, 40000.00, 540000.00, 'lunas', '2025-11-17', '2025-11-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(50, 15, 6, 500000.00, 35000.00, 535000.00, 'lunas', '2025-12-17', '2025-12-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(51, 15, 7, 500000.00, 30000.00, 530000.00, 'lunas', '2026-01-17', '2026-01-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(52, 15, 8, 500000.00, 25000.00, 525000.00, 'lunas', '2026-02-17', '2026-02-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(53, 15, 9, 500000.00, 20000.00, 520000.00, 'lunas', '2026-03-17', '2026-03-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(54, 15, 10, 500000.00, 15000.00, 515000.00, 'lunas', '2026-04-17', '2026-04-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(55, 15, 11, 500000.00, 10000.00, 510000.00, 'lunas', '2026-05-17', '2026-05-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(56, 15, 12, 500000.00, 5000.00, 505000.00, 'lunas', '2026-06-17', '2026-06-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(57, 16, 1, 416666.67, 25000.00, 441666.67, 'lunas', '2026-01-17', '2026-01-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(58, 16, 2, 416666.67, 20833.33, 437500.00, 'lunas', '2026-02-17', '2026-02-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(59, 16, 3, 416666.67, 16666.67, 433333.33, 'lunas', '2026-03-17', '2026-03-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(60, 16, 4, 416666.67, 12500.00, 429166.67, 'lunas', '2026-04-17', '2026-04-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(61, 16, 5, 416666.67, 8333.33, 425000.00, 'lunas', '2026-05-17', '2026-05-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(62, 16, 6, 416666.67, 4166.67, 420833.33, 'lunas', '2026-06-17', '2026-06-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09');

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
(1, 1, 'update_permission_role', 'Hak akses role \'admin\' diperbarui.', '{\"permissions\": [\"anggota.lihat\", \"anggota.kelola\", \"simpanan.lihat\", \"pinjaman.lihat\", \"kas.lihat\", \"laporan.lihat\", \"pengaturan.kelola\"]}', '{\"permissions\": [\"anggota.lihat\", \"anggota.kelola\", \"simpanan.lihat\", \"pinjaman.lihat\", \"kas.lihat\", \"laporan.lihat\", \"pengaturan.kelola\", \"angsuran.konfirmasi\", \"kas.topup\", \"pinjaman.approve-ketua\", \"pinjaman.tinjau-bendahara\", \"portal.akses\", \"simpanan.konfirmasi\"]}', '2026-08-13 22:48:53', '2026-08-13 22:48:53');

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
('laravel-cache-spatie.permission.cache', 'a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:13:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:13:\"anggota.lihat\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:14:\"anggota.kelola\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:14:\"simpanan.lihat\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:19:\"simpanan.konfirmasi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:14:\"pinjaman.lihat\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:25:\"pinjaman.tinjau-bendahara\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:22:\"pinjaman.approve-ketua\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:19:\"angsuran.konfirmasi\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:9:\"kas.lihat\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:9:\"kas.topup\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:13:\"laporan.lihat\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:17:\"pengaturan.kelola\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:12:\"portal.akses\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;}}}s:5:\"roles\";a:4:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:5:\"admin\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:9:\"bendahara\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:14:\"ketua_koperasi\";s:1:\"c\";s:3:\"web\";}i:3;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:7:\"anggota\";s:1:\"c\";s:3:\"web\";}}}', 1786842971);

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
(1, 'keluar', 'pencairan_pinjaman', 2000000.00, 'Pencairan pinjaman - Siti Aminah', 2, '2026-05-17', 3, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(2, 'masuk', 'pembayaran_angsuran', 520000.00, 'Angsuran ke-1 - Siti Aminah', 1, '2026-06-17', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(3, 'masuk', 'pembayaran_angsuran', 515000.00, 'Angsuran ke-2 - Siti Aminah', 2, '2026-07-17', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(4, 'keluar', 'pencairan_pinjaman', 3000000.00, 'Pencairan pinjaman - Ahmad Ridwan', 3, '2025-12-17', 3, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(5, 'masuk', 'pembayaran_angsuran', 530000.00, 'Angsuran ke-1 - Ahmad Ridwan', 5, '2026-01-17', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(6, 'masuk', 'pembayaran_angsuran', 525000.00, 'Angsuran ke-2 - Ahmad Ridwan', 6, '2026-02-17', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(7, 'masuk', 'pembayaran_angsuran', 520000.00, 'Angsuran ke-3 - Ahmad Ridwan', 7, '2026-03-17', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(8, 'masuk', 'pembayaran_angsuran', 515000.00, 'Angsuran ke-4 - Ahmad Ridwan', 8, '2026-04-17', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(9, 'masuk', 'pembayaran_angsuran', 510000.00, 'Angsuran ke-5 - Ahmad Ridwan', 9, '2026-05-17', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(10, 'masuk', 'pembayaran_angsuran', 505000.00, 'Angsuran ke-6 - Ahmad Ridwan', 10, '2026-06-17', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(11, 'keluar', 'pencairan_pinjaman', 5000000.00, 'Pencairan pinjaman - Dewi Lestari', 4, '2025-10-17', 3, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(12, 'masuk', 'pembayaran_angsuran', 466666.67, 'Angsuran ke-1 - Dewi Lestari', 11, '2025-11-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(13, 'masuk', 'pembayaran_angsuran', 462500.00, 'Angsuran ke-2 - Dewi Lestari', 12, '2025-12-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(14, 'masuk', 'pembayaran_angsuran', 458333.33, 'Angsuran ke-3 - Dewi Lestari', 13, '2026-01-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(15, 'masuk', 'pembayaran_angsuran', 454166.67, 'Angsuran ke-4 - Dewi Lestari', 14, '2026-02-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(16, 'masuk', 'pembayaran_angsuran', 450000.00, 'Angsuran ke-5 - Dewi Lestari', 15, '2026-03-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(17, 'masuk', 'pembayaran_angsuran', 445833.33, 'Angsuran ke-6 - Dewi Lestari', 16, '2026-04-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(18, 'masuk', 'pembayaran_angsuran', 441666.67, 'Angsuran ke-7 - Dewi Lestari', 17, '2026-05-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(19, 'masuk', 'pembayaran_angsuran', 437500.00, 'Angsuran ke-8 - Dewi Lestari', 18, '2026-06-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(20, 'masuk', 'pembayaran_angsuran', 433333.33, 'Angsuran ke-9 - Dewi Lestari', 19, '2026-07-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(21, 'masuk', 'pembayaran_angsuran', 429166.67, 'Angsuran ke-10 - Dewi Lestari', 20, '2026-08-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(22, 'keluar', 'pencairan_pinjaman', 1000000.00, 'Pencairan pinjaman - Bambang Sutrisno', 11, '2026-06-17', 3, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(23, 'masuk', 'pembayaran_angsuran', 343333.33, 'Angsuran ke-1 - Bambang Sutrisno', 23, '2026-07-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(24, 'keluar', 'pencairan_pinjaman', 2000000.00, 'Pencairan pinjaman - Eko Prasetyo', 12, '2026-05-17', 3, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(25, 'masuk', 'pembayaran_angsuran', 520000.00, 'Angsuran ke-1 - Eko Prasetyo', 26, '2026-06-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(26, 'masuk', 'pembayaran_angsuran', 515000.00, 'Angsuran ke-2 - Eko Prasetyo', 27, '2026-07-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(27, 'keluar', 'pencairan_pinjaman', 3000000.00, 'Pencairan pinjaman - Dewi Anggraini', 13, '2026-03-17', 3, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(28, 'masuk', 'pembayaran_angsuran', 530000.00, 'Angsuran ke-1 - Dewi Anggraini', 30, '2026-04-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(29, 'masuk', 'pembayaran_angsuran', 525000.00, 'Angsuran ke-2 - Dewi Anggraini', 31, '2026-05-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(30, 'masuk', 'pembayaran_angsuran', 520000.00, 'Angsuran ke-3 - Dewi Anggraini', 32, '2026-06-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(31, 'keluar', 'pencairan_pinjaman', 4000000.00, 'Pencairan pinjaman - Ayu Lestari', 14, '2025-10-17', 3, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(32, 'masuk', 'pembayaran_angsuran', 484444.44, 'Angsuran ke-1 - Ayu Lestari', 36, '2025-11-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(33, 'masuk', 'pembayaran_angsuran', 480000.00, 'Angsuran ke-2 - Ayu Lestari', 37, '2025-12-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(34, 'masuk', 'pembayaran_angsuran', 475555.56, 'Angsuran ke-3 - Ayu Lestari', 38, '2026-01-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(35, 'masuk', 'pembayaran_angsuran', 471111.11, 'Angsuran ke-4 - Ayu Lestari', 39, '2026-02-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(36, 'masuk', 'pembayaran_angsuran', 466666.67, 'Angsuran ke-5 - Ayu Lestari', 40, '2026-03-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(37, 'masuk', 'pembayaran_angsuran', 462222.22, 'Angsuran ke-6 - Ayu Lestari', 41, '2026-04-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(38, 'masuk', 'pembayaran_angsuran', 457777.78, 'Angsuran ke-7 - Ayu Lestari', 42, '2026-05-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(39, 'masuk', 'pembayaran_angsuran', 453333.33, 'Angsuran ke-8 - Ayu Lestari', 43, '2026-06-17', 2, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(40, 'masuk', 'pembayaran_angsuran', 448888.89, 'Angsuran ke-9 - Ayu Lestari', 44, '2026-07-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(41, 'keluar', 'pencairan_pinjaman', 6000000.00, 'Pencairan pinjaman - Laila Amalia', 15, '2025-06-17', 3, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(42, 'masuk', 'pembayaran_angsuran', 560000.00, 'Angsuran ke-1 - Laila Amalia', 45, '2025-07-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(43, 'masuk', 'pembayaran_angsuran', 555000.00, 'Angsuran ke-2 - Laila Amalia', 46, '2025-08-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(44, 'masuk', 'pembayaran_angsuran', 550000.00, 'Angsuran ke-3 - Laila Amalia', 47, '2025-09-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(45, 'masuk', 'pembayaran_angsuran', 545000.00, 'Angsuran ke-4 - Laila Amalia', 48, '2025-10-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(46, 'masuk', 'pembayaran_angsuran', 540000.00, 'Angsuran ke-5 - Laila Amalia', 49, '2025-11-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(47, 'masuk', 'pembayaran_angsuran', 535000.00, 'Angsuran ke-6 - Laila Amalia', 50, '2025-12-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(48, 'masuk', 'pembayaran_angsuran', 530000.00, 'Angsuran ke-7 - Laila Amalia', 51, '2026-01-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(49, 'masuk', 'pembayaran_angsuran', 525000.00, 'Angsuran ke-8 - Laila Amalia', 52, '2026-02-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(50, 'masuk', 'pembayaran_angsuran', 520000.00, 'Angsuran ke-9 - Laila Amalia', 53, '2026-03-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(51, 'masuk', 'pembayaran_angsuran', 515000.00, 'Angsuran ke-10 - Laila Amalia', 54, '2026-04-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(52, 'masuk', 'pembayaran_angsuran', 510000.00, 'Angsuran ke-11 - Laila Amalia', 55, '2026-05-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(53, 'masuk', 'pembayaran_angsuran', 505000.00, 'Angsuran ke-12 - Laila Amalia', 56, '2026-06-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(54, 'keluar', 'pencairan_pinjaman', 2500000.00, 'Pencairan pinjaman - Citra Ramadhani', 16, '2025-12-17', 3, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(55, 'masuk', 'pembayaran_angsuran', 441666.67, 'Angsuran ke-1 - Citra Ramadhani', 57, '2026-01-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(56, 'masuk', 'pembayaran_angsuran', 437500.00, 'Angsuran ke-2 - Citra Ramadhani', 58, '2026-02-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(57, 'masuk', 'pembayaran_angsuran', 433333.33, 'Angsuran ke-3 - Citra Ramadhani', 59, '2026-03-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(58, 'masuk', 'pembayaran_angsuran', 429166.67, 'Angsuran ke-4 - Citra Ramadhani', 60, '2026-04-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(59, 'masuk', 'pembayaran_angsuran', 425000.00, 'Angsuran ke-5 - Citra Ramadhani', 61, '2026-05-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(60, 'masuk', 'pembayaran_angsuran', 420833.33, 'Angsuran ke-6 - Citra Ramadhani', 62, '2026-06-17', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(61, 'masuk', 'topup_bulanan', 20000000.00, 'Topup saldo koperasi', 990001, '2026-04-02', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(62, 'masuk', 'topup_bulanan', 15000000.00, 'Topup saldo koperasi', 990002, '2026-06-02', 2, '2026-08-13 22:48:09', '2026-08-13 22:48:09');

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
(1, 131250000.00, '2026-08-13 22:47:59', '2026-08-13 22:48:09');

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
(23, '2026_08_14_000000_add_pengaju_dan_cair_oleh_bendahara_to_pinjaman_table', 1);

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
(1, 'anggota.lihat', 'web', '2026-08-13 22:47:44', '2026-08-13 22:47:44'),
(2, 'anggota.kelola', 'web', '2026-08-13 22:47:44', '2026-08-13 22:47:44'),
(3, 'simpanan.lihat', 'web', '2026-08-13 22:47:45', '2026-08-13 22:47:45'),
(4, 'simpanan.konfirmasi', 'web', '2026-08-13 22:47:45', '2026-08-13 22:47:45'),
(5, 'pinjaman.lihat', 'web', '2026-08-13 22:47:45', '2026-08-13 22:47:45'),
(6, 'pinjaman.tinjau-bendahara', 'web', '2026-08-13 22:47:45', '2026-08-13 22:47:45'),
(7, 'pinjaman.approve-ketua', 'web', '2026-08-13 22:47:45', '2026-08-13 22:47:45'),
(8, 'angsuran.konfirmasi', 'web', '2026-08-13 22:47:45', '2026-08-13 22:47:45'),
(9, 'kas.lihat', 'web', '2026-08-13 22:47:45', '2026-08-13 22:47:45'),
(10, 'kas.topup', 'web', '2026-08-13 22:47:45', '2026-08-13 22:47:45'),
(11, 'laporan.lihat', 'web', '2026-08-13 22:47:45', '2026-08-13 22:47:45'),
(12, 'pengaturan.kelola', 'web', '2026-08-13 22:47:45', '2026-08-13 22:47:45'),
(13, 'portal.akses', 'web', '2026-08-13 22:47:45', '2026-08-13 22:47:45');

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
(1, 1, NULL, 1000000.00, 3, 'Kebutuhan harian', 'BCA', '1234001001', 'Budi Santoso', 1.00, 'diajukan', 0, 0, '2026-08-12', NULL, NULL, NULL, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(2, 2, NULL, 2000000.00, 4, 'Biaya pendidikan anak', 'Mandiri', '8213400220', 'Siti Aminah', 1.00, 'aktif', 0, 0, '2026-05-14', '2026-05-17', NULL, NULL, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(3, 3, NULL, 3000000.00, 6, 'Perbaikan rumah', 'BRI', '72810033', 'Ahmad Ridwan', 1.00, 'lunas', 0, 0, '2025-12-14', '2025-12-17', NULL, NULL, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(4, 4, NULL, 5000000.00, 12, 'Pembelian kendaraan', 'BNI', '20987654', 'Dewi Lestari', 1.00, 'aktif', 0, 0, '2025-10-14', '2025-10-17', NULL, NULL, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(5, 6, NULL, 1500000.00, 4, 'Kebutuhan hari raya', 'BCA', '1234002002', 'Agus Wijaya', 1.00, 'diajukan', 0, 0, '2026-08-13', NULL, NULL, NULL, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(6, 16, NULL, 2500000.00, 6, 'Biaya pendidikan anak', 'Mandiri', '8213400221', 'Adi Nugroho', 1.00, 'diajukan', 0, 0, '2026-08-11', NULL, NULL, NULL, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(7, 26, NULL, 5000000.00, 12, 'Perbaikan rumah', 'BRI', '72810034', 'Deni Setiawan', 1.00, 'diajukan', 0, 0, '2026-08-09', NULL, NULL, NULL, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(8, 8, NULL, 3500000.00, 9, 'Biaya pengobatan', 'BNI', '20987655', 'Maya Sari', 1.00, 'approved_bendahara', 0, 0, '2026-08-06', NULL, 'Verifikasi dokumen lengkap, layak diteruskan ke Ketua.', NULL, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(9, 18, NULL, 6000000.00, 12, 'Pembelian kendaraan', 'Bank Kalsel', '55990011', 'Yudha Pradana', 1.00, 'approved_bendahara', 0, 0, '2026-08-04', NULL, 'Riwayat angsuran baik, disetujui.', NULL, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(10, 28, NULL, 2000000.00, 4, 'Modal usaha', 'BCA', '1234003003', 'Galih Prakoso', 1.00, 'approved_bendahara', 0, 0, '2026-08-02', NULL, 'Dokumen sesuai ketentuan.', NULL, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(11, 7, NULL, 1000000.00, 3, 'Perlengkapan rumah tangga', 'BCA', '1234004004', 'Hendra Gunawan', 1.00, 'aktif', 0, 0, '2026-06-14', '2026-06-17', NULL, NULL, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(12, 17, NULL, 2000000.00, 4, 'Biaya pendidikan anak', 'Mandiri', '8213400222', 'Indah Permata', 1.00, 'aktif', 0, 0, '2026-05-14', '2026-05-17', NULL, NULL, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(13, 10, NULL, 3000000.00, 6, 'Perbaikan rumah', 'BRI', '72810035', 'Joko Susanto', 1.00, 'aktif', 0, 0, '2026-03-14', '2026-03-17', NULL, NULL, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(14, 30, NULL, 4000000.00, 9, 'Modal usaha', 'BNI', '20987656', 'Ferry Ardiansyah', 1.00, 'lunas', 0, 0, '2025-10-14', '2025-10-17', NULL, NULL, '2026-08-13 22:48:08', '2026-08-13 22:48:08'),
(15, 40, NULL, 6000000.00, 12, 'Pembelian kendaraan', 'Bank Kalsel', '55990012', 'Candra Wijaya', 1.00, 'lunas', 0, 0, '2025-06-14', '2025-06-17', NULL, NULL, '2026-08-13 22:48:09', '2026-08-13 22:48:09'),
(16, 50, NULL, 2500000.00, 6, 'Kebutuhan hari raya', 'BCA', '1234005005', 'Citra Ramadhani', 1.00, 'lunas', 0, 0, '2025-12-14', '2025-12-17', NULL, NULL, '2026-08-13 22:48:09', '2026-08-13 22:48:09');

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
(1, 'admin', 'web', '2026-08-13 22:47:45', '2026-08-13 22:47:45'),
(2, 'bendahara', 'web', '2026-08-13 22:47:45', '2026-08-13 22:47:45'),
(3, 'ketua_koperasi', 'web', '2026-08-13 22:47:45', '2026-08-13 22:47:45'),
(4, 'anggota', 'web', '2026-08-13 22:47:45', '2026-08-13 22:47:45');

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
(1, 1.00, '2026-01-01', '2026-08-13 22:47:59', '2026-08-13 22:47:59');

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
(1, 'anggota_baru', 'Anggota < 1 Tahun', 1000000.00, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(2, 'staff', 'Staff (1-5 Tahun)', 7000000.00, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(3, 'hod', 'HOD (1-5 Tahun)', 10000000.00, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(4, 'anggota_lama', 'Anggota ≥ 5 Tahun', 10000000.00, '2026-08-13 22:47:59', '2026-08-13 22:47:59');

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
(1, 'pokok', 'Simpanan Pokok', 50000.00, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(2, 'wajib', 'Simpanan Wajib', 45000.00, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(3, 'dana_sosial', 'Dana Sosial', 5000.00, '2026-08-13 22:47:59', '2026-08-13 22:47:59');

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
(1, 1, 'pokok', 50000.00, '2026-02', '2026-02-14', 4, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(2, 2, 'pokok', 50000.00, '2023-08', '2023-08-14', 5, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(3, 3, 'pokok', 50000.00, '2020-08', '2020-08-14', 6, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(4, 4, 'pokok', 50000.00, '2019-08', '2019-08-14', 7, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(5, 5, 'pokok', 50000.00, '2026-04', '2026-04-14', 8, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(6, 6, 'pokok', 50000.00, '2024-07', '2024-07-14', 9, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(7, 7, 'pokok', 50000.00, '2023-06', '2023-06-14', 10, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(8, 8, 'pokok', 50000.00, '2018-05', '2018-05-14', 11, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(9, 9, 'pokok', 50000.00, '2025-12', '2025-12-14', 12, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(10, 10, 'pokok', 50000.00, '2024-03', '2024-03-14', 13, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(11, 11, 'pokok', 50000.00, '2023-02', '2023-02-14', 14, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(12, 12, 'pokok', 50000.00, '2020-06', '2020-06-14', 15, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(13, 13, 'pokok', 50000.00, '2026-01', '2026-01-14', 16, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(14, 14, 'pokok', 50000.00, '2023-11', '2023-11-14', 17, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(15, 15, 'pokok', 50000.00, '2023-05', '2023-05-14', 18, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(16, 16, 'pokok', 50000.00, '2016-07', '2016-07-14', 19, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(17, 17, 'pokok', 50000.00, '2026-02', '2026-02-14', 20, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(18, 18, 'pokok', 50000.00, '2024-06', '2024-06-14', 21, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(19, 19, 'pokok', 50000.00, '2023-08', '2023-08-14', 22, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(20, 20, 'pokok', 50000.00, '2018-08', '2018-08-14', 23, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(21, 21, 'pokok', 50000.00, '2026-03', '2026-03-14', 24, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(22, 22, 'pokok', 50000.00, '2024-02', '2024-02-14', 25, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(23, 23, 'pokok', 50000.00, '2023-04', '2023-04-14', 26, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(24, 24, 'pokok', 50000.00, '2020-04', '2020-04-14', 27, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(25, 25, 'pokok', 50000.00, '2026-04', '2026-04-14', 28, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(26, 26, 'pokok', 50000.00, '2023-10', '2023-10-14', 29, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(27, 27, 'pokok', 50000.00, '2023-07', '2023-07-14', 30, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(28, 28, 'pokok', 50000.00, '2016-05', '2016-05-14', 31, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(29, 29, 'pokok', 50000.00, '2025-12', '2025-12-14', 32, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(30, 30, 'pokok', 50000.00, '2024-05', '2024-05-14', 33, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(31, 31, 'pokok', 50000.00, '2023-03', '2023-03-14', 34, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(32, 32, 'pokok', 50000.00, '2018-06', '2018-06-14', 35, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(33, 33, 'pokok', 50000.00, '2026-01', '2026-01-14', 36, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(34, 34, 'pokok', 50000.00, '2024-01', '2024-01-14', 37, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(35, 35, 'pokok', 50000.00, '2023-06', '2023-06-14', 38, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(36, 36, 'pokok', 50000.00, '2020-07', '2020-07-14', 39, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(37, 37, 'pokok', 50000.00, '2026-02', '2026-02-14', 40, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(38, 38, 'pokok', 50000.00, '2024-08', '2024-08-14', 41, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(39, 39, 'pokok', 50000.00, '2023-02', '2023-02-14', 42, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(40, 40, 'pokok', 50000.00, '2016-08', '2016-08-14', 43, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(41, 41, 'pokok', 50000.00, '2026-03', '2026-03-14', 44, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(42, 42, 'pokok', 50000.00, '2024-04', '2024-04-14', 45, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(43, 43, 'pokok', 50000.00, '2023-05', '2023-05-14', 46, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(44, 44, 'pokok', 50000.00, '2018-04', '2018-04-14', 47, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(45, 45, 'pokok', 50000.00, '2026-04', '2026-04-14', 48, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(46, 46, 'pokok', 50000.00, '2023-12', '2023-12-14', 49, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(47, 47, 'pokok', 50000.00, '2023-08', '2023-08-14', 50, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(48, 48, 'pokok', 50000.00, '2020-05', '2020-05-14', 51, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(49, 49, 'pokok', 50000.00, '2025-12', '2025-12-14', 52, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(50, 50, 'pokok', 50000.00, '2024-07', '2024-07-14', 53, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(51, 51, 'pokok', 50000.00, '2019-08', '2019-08-14', 2, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(52, 52, 'pokok', 50000.00, '2017-08', '2017-08-14', 3, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(53, 1, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(54, 1, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(55, 1, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(56, 1, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(57, 1, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(58, 1, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(59, 1, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(60, 1, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(61, 1, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(62, 1, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(63, 1, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(64, 1, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(65, 2, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(66, 2, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(67, 2, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:00', '2026-08-13 22:48:00'),
(68, 2, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(69, 2, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(70, 2, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(71, 2, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(72, 2, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(73, 2, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(74, 2, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(75, 2, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(76, 2, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(77, 3, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(78, 3, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(79, 3, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(80, 3, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(81, 3, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(82, 3, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(83, 3, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(84, 3, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(85, 3, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(86, 3, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(87, 3, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(88, 3, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(89, 4, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(90, 4, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(91, 4, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(92, 4, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(93, 4, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(94, 4, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(95, 4, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(96, 4, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(97, 4, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(98, 4, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(99, 4, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(100, 4, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(101, 5, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(102, 5, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(103, 5, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(104, 5, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(105, 5, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(106, 5, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(107, 5, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(108, 5, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(109, 6, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(110, 6, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(111, 6, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(112, 6, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(113, 6, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(114, 6, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(115, 6, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(116, 6, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(117, 6, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(118, 6, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(119, 6, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(120, 6, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(121, 7, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(122, 7, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(123, 7, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(124, 7, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(125, 7, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(126, 7, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(127, 7, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(128, 7, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(129, 7, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(130, 7, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(131, 7, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(132, 7, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(133, 8, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(134, 8, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(135, 8, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(136, 8, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(137, 8, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(138, 8, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(139, 8, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(140, 8, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(141, 8, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(142, 8, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(143, 8, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(144, 8, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(145, 9, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(146, 9, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(147, 9, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(148, 9, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(149, 9, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(150, 9, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(151, 9, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:01', '2026-08-13 22:48:01'),
(152, 9, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(153, 9, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(154, 9, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(155, 9, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(156, 9, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(157, 10, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(158, 10, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(159, 10, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(160, 10, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(161, 10, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(162, 10, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(163, 10, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(164, 10, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(165, 10, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(166, 10, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(167, 11, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(168, 11, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(169, 11, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(170, 11, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(171, 11, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(172, 11, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(173, 11, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(174, 11, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(175, 11, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(176, 11, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(177, 11, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(178, 11, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(179, 12, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(180, 12, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(181, 12, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(182, 12, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(183, 12, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(184, 12, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(185, 12, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(186, 12, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(187, 12, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(188, 12, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(189, 12, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(190, 12, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(191, 14, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(192, 14, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(193, 14, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(194, 14, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(195, 14, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(196, 14, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(197, 14, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(198, 14, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(199, 14, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(200, 14, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(201, 14, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(202, 14, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(203, 15, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(204, 15, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(205, 15, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(206, 15, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(207, 15, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(208, 15, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(209, 15, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(210, 15, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(211, 15, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(212, 15, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(213, 16, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(214, 16, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(215, 16, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(216, 16, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(217, 16, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(218, 16, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(219, 16, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(220, 16, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(221, 16, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(222, 16, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(223, 16, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(224, 16, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(225, 17, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(226, 17, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(227, 17, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(228, 17, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(229, 17, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(230, 17, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(231, 17, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(232, 17, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(233, 17, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(234, 17, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(235, 17, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(236, 17, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(237, 18, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:02', '2026-08-13 22:48:02'),
(238, 18, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(239, 18, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(240, 18, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(241, 18, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(242, 18, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(243, 18, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(244, 18, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(245, 18, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(246, 18, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(247, 18, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(248, 18, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(249, 19, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(250, 19, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(251, 19, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(252, 19, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(253, 19, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(254, 19, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(255, 19, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(256, 19, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(257, 19, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(258, 19, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(259, 19, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(260, 19, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(261, 20, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(262, 20, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(263, 20, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(264, 20, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(265, 20, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(266, 20, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(267, 20, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(268, 20, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(269, 20, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(270, 20, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(271, 21, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(272, 21, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(273, 21, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(274, 21, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(275, 21, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(276, 21, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(277, 21, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(278, 21, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(279, 21, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(280, 21, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(281, 21, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(282, 21, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(283, 22, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(284, 22, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(285, 22, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(286, 22, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(287, 22, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(288, 22, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(289, 22, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(290, 22, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(291, 22, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(292, 22, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(293, 22, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(294, 22, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(295, 23, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(296, 23, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(297, 23, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(298, 23, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(299, 23, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(300, 23, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(301, 23, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(302, 23, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(303, 23, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(304, 23, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(305, 23, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(306, 23, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(307, 24, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(308, 24, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(309, 24, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(310, 24, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(311, 24, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(312, 24, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(313, 24, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(314, 24, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(315, 24, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(316, 24, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(317, 24, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(318, 24, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(319, 25, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(320, 25, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(321, 25, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(322, 25, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(323, 25, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(324, 25, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:03', '2026-08-13 22:48:03'),
(325, 25, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(326, 25, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(327, 26, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(328, 26, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(329, 26, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(330, 26, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(331, 26, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(332, 26, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(333, 26, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(334, 26, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(335, 26, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(336, 26, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(337, 26, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(338, 26, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(339, 28, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(340, 28, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(341, 28, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(342, 28, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(343, 28, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(344, 28, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(345, 28, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(346, 28, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(347, 28, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(348, 28, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(349, 28, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(350, 28, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(351, 29, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(352, 29, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(353, 29, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(354, 29, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(355, 29, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(356, 29, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(357, 29, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(358, 29, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(359, 29, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(360, 29, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(361, 29, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(362, 29, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(363, 30, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(364, 30, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(365, 30, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(366, 30, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(367, 30, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(368, 30, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(369, 30, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(370, 30, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(371, 30, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(372, 30, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(373, 30, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(374, 30, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(375, 31, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(376, 31, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(377, 31, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(378, 31, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(379, 31, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(380, 31, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(381, 31, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(382, 31, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(383, 31, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(384, 31, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(385, 31, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(386, 31, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(387, 32, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(388, 32, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(389, 32, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(390, 32, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(391, 32, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(392, 32, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(393, 32, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(394, 32, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(395, 32, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(396, 32, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(397, 32, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(398, 32, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(399, 33, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(400, 33, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(401, 33, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(402, 33, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(403, 33, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(404, 33, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(405, 33, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(406, 33, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(407, 33, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(408, 33, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(409, 33, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(410, 33, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(411, 34, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(412, 34, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(413, 34, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:04', '2026-08-13 22:48:04'),
(414, 34, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(415, 34, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(416, 34, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(417, 34, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(418, 34, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(419, 34, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(420, 34, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(421, 34, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(422, 34, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(423, 35, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(424, 35, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(425, 35, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(426, 35, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(427, 35, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(428, 35, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(429, 35, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(430, 35, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(431, 35, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(432, 35, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(433, 35, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(434, 35, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(435, 36, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(436, 36, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(437, 36, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(438, 36, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(439, 36, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(440, 36, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(441, 36, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(442, 36, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(443, 36, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(444, 36, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(445, 36, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(446, 36, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(447, 37, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(448, 37, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(449, 37, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(450, 37, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(451, 37, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(452, 37, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(453, 37, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(454, 37, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(455, 37, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(456, 37, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(457, 37, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(458, 37, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(459, 38, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(460, 38, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(461, 38, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(462, 38, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(463, 38, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(464, 38, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(465, 38, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(466, 38, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(467, 38, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(468, 38, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(469, 38, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(470, 38, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(471, 39, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(472, 39, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(473, 39, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(474, 39, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(475, 39, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(476, 39, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(477, 39, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(478, 39, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(479, 39, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(480, 39, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05');
INSERT INTO `simpanan` (`id`, `anggota_id`, `jenis`, `jumlah`, `bulan_periode`, `tanggal_input`, `input_by`, `created_at`, `updated_at`) VALUES
(481, 39, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(482, 39, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(483, 40, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(484, 40, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(485, 40, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(486, 40, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(487, 40, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(488, 40, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(489, 40, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(490, 40, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(491, 40, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(492, 40, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(493, 40, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(494, 40, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(495, 41, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(496, 41, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(497, 41, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(498, 41, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:05', '2026-08-13 22:48:05'),
(499, 41, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(500, 41, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(501, 41, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(502, 41, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(503, 41, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(504, 41, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(505, 41, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(506, 41, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(507, 42, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(508, 42, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(509, 42, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(510, 42, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(511, 42, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(512, 42, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(513, 42, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(514, 42, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(515, 42, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(516, 42, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(517, 42, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(518, 42, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(519, 43, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(520, 43, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(521, 43, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(522, 43, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(523, 43, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(524, 43, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(525, 43, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(526, 43, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(527, 43, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(528, 43, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(529, 43, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(530, 43, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(531, 44, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(532, 44, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(533, 44, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(534, 44, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(535, 44, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(536, 44, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(537, 44, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(538, 44, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(539, 44, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(540, 44, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(541, 44, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(542, 44, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(543, 45, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(544, 45, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(545, 45, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(546, 45, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(547, 45, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(548, 45, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(549, 45, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(550, 45, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(551, 45, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(552, 45, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(553, 46, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(554, 46, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(555, 46, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(556, 46, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(557, 46, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(558, 46, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(559, 46, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(560, 46, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(561, 46, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(562, 46, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(563, 46, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(564, 46, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(565, 47, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(566, 47, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(567, 47, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(568, 47, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(569, 47, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(570, 47, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(571, 47, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(572, 47, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(573, 47, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(574, 47, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(575, 47, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(576, 47, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(577, 48, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(578, 48, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(579, 48, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(580, 48, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(581, 48, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(582, 48, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(583, 48, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(584, 48, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(585, 48, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(586, 48, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(587, 48, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(588, 48, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(589, 49, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(590, 49, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:06', '2026-08-13 22:48:06'),
(591, 49, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(592, 49, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(593, 49, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(594, 49, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(595, 49, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(596, 49, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(597, 49, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(598, 49, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(599, 49, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(600, 49, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(601, 50, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(602, 50, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(603, 50, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(604, 50, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(605, 50, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(606, 50, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(607, 50, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(608, 50, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(609, 50, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(610, 50, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(611, 50, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(612, 50, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(613, 51, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(614, 51, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(615, 51, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(616, 51, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(617, 51, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(618, 51, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(619, 51, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(620, 51, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(621, 51, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(622, 51, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(623, 51, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(624, 51, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(625, 52, 'wajib', 45000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(626, 52, 'dana_sosial', 5000.00, '2026-03', '2026-03-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(627, 52, 'wajib', 45000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(628, 52, 'dana_sosial', 5000.00, '2026-04', '2026-04-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(629, 52, 'wajib', 45000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(630, 52, 'dana_sosial', 5000.00, '2026-05', '2026-05-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(631, 52, 'wajib', 45000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(632, 52, 'dana_sosial', 5000.00, '2026-06', '2026-06-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(633, 52, 'wajib', 45000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(634, 52, 'dana_sosial', 5000.00, '2026-07', '2026-07-25', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(635, 52, 'wajib', 45000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07'),
(636, 52, 'dana_sosial', 5000.00, '2026-08', '2026-08-14', 2, '2026-08-13 22:48:07', '2026-08-13 22:48:07');

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
(1, 0.00, 1000000.00, 3, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(2, 1000001.00, 2000000.00, 4, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(3, 2000001.00, 3000000.00, 6, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(4, 3000001.00, 4000000.00, 9, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(5, 4000001.00, 10000000.00, 12, '2026-08-13 22:47:59', '2026-08-13 22:47:59');

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
(1, 'Admin Koperasi', 'superadmin@appdutamall.com', 'ADM-000001', '019ee93c-fd94-7108-a9d3-9ca2d950c592', 'sso', NULL, '$2y$12$0MfR0Kq67UXt9tkvG2P1FeQO6l5ew/NJR/d8vGIsYhsmGNqpnQdFy', 0, NULL, '2026-08-13 22:47:45', '2026-08-14 16:41:26'),
(2, 'Bendahara Koperasi', 'bendahara@koperasi.test', 'BEN-000001', NULL, 'local', NULL, '$2y$12$Xsx.mvykG28IzaoXpdFAreEvw72Gf8sls103Y5cPw5moXtdLCJmOy', 0, NULL, '2026-08-13 22:47:45', '2026-08-13 22:47:45'),
(3, 'Ketua Koperasi', 'ketua@koperasi.test', 'KET-000001', NULL, 'local', NULL, '$2y$12$F3KeaEgU7nU9P29IvgTxYOA14S2bUd3.3jyiDycQI3a.VqQypAiZq', 0, NULL, '2026-08-13 22:47:46', '2026-08-13 22:47:46'),
(4, 'Anggota Baru', 'anggota.baru@koperasi.test', 'TOP-100001', NULL, 'local', NULL, '$2y$12$I019lUMlVqyaeDwLZ/GIquTBdTDpRQCosfFR8WiGMVZWUAtDsMvou', 0, NULL, '2026-08-13 22:47:46', '2026-08-13 22:47:46'),
(5, 'Anggota Sedang', 'anggota.sedang@koperasi.test', 'TOP-100002', NULL, 'local', NULL, '$2y$12$yYTOJKvFaS0xgCmsvxhUD.uwcpQdwkkbHnuChM3jnV8oDLJWBuqna', 0, NULL, '2026-08-13 22:47:46', '2026-08-13 22:47:46'),
(6, 'Anggota Lama', 'anggota.lama@koperasi.test', 'TOP-100003', NULL, 'local', NULL, '$2y$12$AtKaWyoa4ppmPr9efCe.gOUouUugB7U9Fc/Pf6nU/Y8yyktMbrM0q', 0, NULL, '2026-08-13 22:47:46', '2026-08-13 22:47:46'),
(7, 'Anggota Reloan', 'anggota.reloan@koperasi.test', 'TOP-100004', NULL, 'local', NULL, '$2y$12$keEJojGDjTSpZP2ZQoGWduPzk/3hKbA2uiBq4dFMJjoyBK62C8taa', 0, NULL, '2026-08-13 22:47:47', '2026-08-13 22:47:47'),
(8, 'Agus Wijaya', 'anggota.aguswijaya@koperasi.test', 'TOP-100005', NULL, 'local', NULL, '$2y$12$q5cIxxsrCETyDFlCHHxZn.aUz.oQBJM33BxzIme521MYNdUH10hcO', 0, NULL, '2026-08-13 22:47:47', '2026-08-13 22:47:47'),
(9, 'Rina Marlina', 'anggota.rinamarlina@koperasi.test', 'TOP-100006', NULL, 'local', NULL, '$2y$12$LhCTME1Ozzr0ILvt1TBQr.bbFOfe8HzODE0lQb.rCW7mEM/6ZvsYC', 0, NULL, '2026-08-13 22:47:47', '2026-08-13 22:47:47'),
(10, 'Bambang Sutrisno', 'anggota.bambangsutrisno@koperasi.test', 'TOP-100007', NULL, 'local', NULL, '$2y$12$6E4r7cILMUOWJiL4I5RSN.LyePoGlX2Fcl8SdVjPESBPtG93HlVyy', 0, NULL, '2026-08-13 22:47:47', '2026-08-13 22:47:47'),
(11, 'Sari Rahayu', 'anggota.sarirahayu@koperasi.test', 'TOP-100008', NULL, 'local', NULL, '$2y$12$7H68K4qH7bJzmCUNLjQCDe1erzDmAAtGRWw132Vxxh2oQpyjnbc4G', 0, NULL, '2026-08-13 22:47:48', '2026-08-13 22:47:48'),
(12, 'Hendra Gunawan', 'anggota.hendragunawan@koperasi.test', 'TOP-100009', NULL, 'local', NULL, '$2y$12$iK2dq93p8jKEmXurGd09z.y20FeEYjgWuH/mJbYLX/stvyNmsqPqG', 0, NULL, '2026-08-13 22:47:48', '2026-08-13 22:47:48'),
(13, 'Dewi Anggraini', 'anggota.dewianggraini@koperasi.test', 'TOP-100010', NULL, 'local', NULL, '$2y$12$VwGYis7jnX6VBD6HGXoM7uqmLMAgQLkmJ.z32dYbdYmxrm5GDLAXO', 0, NULL, '2026-08-13 22:47:48', '2026-08-13 22:47:48'),
(14, 'Joko Susanto', 'anggota.jokosusanto@koperasi.test', 'TOP-100011', NULL, 'local', NULL, '$2y$12$owxPagdXYjQ8eozdgHdCZ.e6FwgIMtkfHgTu2AwbRN/AWJR6K6Uge', 0, NULL, '2026-08-13 22:47:48', '2026-08-13 22:47:48'),
(15, 'Maya Sari', 'anggota.mayasari@koperasi.test', 'TOP-100012', NULL, 'local', NULL, '$2y$12$n6gP8A3X4AMzaghLZ4jgnOb2/VI23qMlumQNQz2RjIZKXDY0MjmTq', 0, NULL, '2026-08-13 22:47:49', '2026-08-13 22:47:49'),
(16, 'Adi Nugroho', 'anggota.adinugroho@koperasi.test', 'TOP-100013', NULL, 'local', NULL, '$2y$12$93wPtYGF1ct/pvsHkCGxuOox3E.cF18etrdBgtBZu/eAVAgVr1Yxi', 0, NULL, '2026-08-13 22:47:49', '2026-08-13 22:47:49'),
(17, 'Lina Wijayanti', 'anggota.linawijayanti@koperasi.test', 'TOP-100014', NULL, 'local', NULL, '$2y$12$5hR4hK710Yswyn5c046Hz.8P0e934Jmi22cWjoViO275SuU8rAVcm', 0, NULL, '2026-08-13 22:47:49', '2026-08-13 22:47:49'),
(18, 'Rizky Pratama', 'anggota.rizkypratama@koperasi.test', 'TOP-100015', NULL, 'local', NULL, '$2y$12$.Yy2VvshbTe9BoMsPl6/i.ZwpDqysDLcPNr8w7BSL.gjwOqLQvxaC', 0, NULL, '2026-08-13 22:47:50', '2026-08-13 22:47:50'),
(19, 'Nia Kurniawati', 'anggota.niakurniawati@koperasi.test', 'TOP-100016', NULL, 'local', NULL, '$2y$12$COgglVmhgliDoSc3sdjMEehyax0Wqqf6x0YbafST3ktTTUziKdILy', 0, NULL, '2026-08-13 22:47:50', '2026-08-13 22:47:50'),
(20, 'Eko Prasetyo', 'anggota.ekoprasetyo@koperasi.test', 'TOP-100017', NULL, 'local', NULL, '$2y$12$UI/Bj.LFZieErUMdaiqLH.Ro8x57QN43476SAtWabaPbLLkjoXkzW', 0, NULL, '2026-08-13 22:47:50', '2026-08-13 22:47:50'),
(21, 'Putri Handayani', 'anggota.putrihandayani@koperasi.test', 'TOP-100018', NULL, 'local', NULL, '$2y$12$v93331DGbxsTL3sAoB6z8.Z8yaE2U/CUmoEuJTZn1uyc2q2LQMQWe', 0, NULL, '2026-08-13 22:47:50', '2026-08-13 22:47:50'),
(22, 'Fajar Ramadhan', 'anggota.fajarramadhan@koperasi.test', 'TOP-100019', NULL, 'local', NULL, '$2y$12$YRQQ9y57a2O1iNzqfESTSOXi1Q6/S8weNfDxH9vtO2u3/XUAd/kQW', 0, NULL, '2026-08-13 22:47:51', '2026-08-13 22:47:51'),
(23, 'Indah Permata', 'anggota.indahpermata@koperasi.test', 'TOP-100020', NULL, 'local', NULL, '$2y$12$zy1/x0Fmzl8zk./dvHkUsO2DYBXwS81bIIl7j0LKs3C58WnaAfylm', 0, NULL, '2026-08-13 22:47:51', '2026-08-13 22:47:51'),
(24, 'Yudha Pradana', 'anggota.yudhapradana@koperasi.test', 'TOP-100021', NULL, 'local', NULL, '$2y$12$cEK5BDNOXaiEGZDwwo.x6uBVeduc7n3evFAmSmi825QGCOmDH7j3G', 0, NULL, '2026-08-13 22:47:51', '2026-08-13 22:47:51'),
(25, 'Sri Wahyuni', 'anggota.sriwahyuni@koperasi.test', 'TOP-100022', NULL, 'local', NULL, '$2y$12$8GWEDYKKAUagD2AAORGxEeO9h4Hu7IR/zuKNdR.VV1eRRQ112UegO', 0, NULL, '2026-08-13 22:47:51', '2026-08-13 22:47:51'),
(26, 'Andi Firmansyah', 'anggota.andifirmansyah@koperasi.test', 'TOP-100023', NULL, 'local', NULL, '$2y$12$klDc9lLBvmVwT8HfXAkFueohNLSWjwywHzDdUQFk4cst0/vvSRYm2', 0, NULL, '2026-08-13 22:47:52', '2026-08-13 22:47:52'),
(27, 'Ratna Sari', 'anggota.ratnasari@koperasi.test', 'TOP-100024', NULL, 'local', NULL, '$2y$12$kydYjI51MYMDBcHt99uSw./WzcSHhQvvlb5OCswrmFIzOvNH0eG42', 0, NULL, '2026-08-13 22:47:52', '2026-08-13 22:47:52'),
(28, 'Deni Setiawan', 'anggota.denisetiawan@koperasi.test', 'TOP-100025', NULL, 'local', NULL, '$2y$12$r1gwSDG811bXrAOp9G6fx.0oqCdHwu7QGR10kMP/HiNI0QsmPjVLu', 0, NULL, '2026-08-13 22:47:52', '2026-08-13 22:47:52'),
(29, 'Fitriani', 'anggota.fitriani@koperasi.test', 'TOP-100026', NULL, 'local', NULL, '$2y$12$bhCdwfPQj2sDAwWHUxdwGevdaE.TxWEsy0PQb.YxbE/EaOO2F1mbm', 0, NULL, '2026-08-13 22:47:53', '2026-08-13 22:47:53'),
(30, 'Rudi Hartono', 'anggota.rudihartono@koperasi.test', 'TOP-100027', NULL, 'local', NULL, '$2y$12$vSuLn/ps8L5BKr.rAuoNAuLmYNHWyZBXihiEYbn7dqRVwLUlwloue', 0, NULL, '2026-08-13 22:47:53', '2026-08-13 22:47:53'),
(31, 'Susi Susanti', 'anggota.susisusanti@koperasi.test', 'TOP-100028', NULL, 'local', NULL, '$2y$12$BaanpfaXFWXF9rJ..k5HCuzcY7Phn3q0t37jDQBED3kzF8pUPPkfK', 0, NULL, '2026-08-13 22:47:53', '2026-08-13 22:47:53'),
(32, 'Bayu Saputra', 'anggota.bayusaputra@koperasi.test', 'TOP-100029', NULL, 'local', NULL, '$2y$12$YpYU51H4hpEycXOBhU598uek5/dZD4OX.ORzRVpEU96HuW8Mx7tDO', 0, NULL, '2026-08-13 22:47:53', '2026-08-13 22:47:53'),
(33, 'Ayu Lestari', 'anggota.ayulestari@koperasi.test', 'TOP-100030', NULL, 'local', NULL, '$2y$12$JUfSlQL/.23iQvcdxU3UVOH55mp9ZBizn4nHwN628U9ZAY4ygEi9C', 0, NULL, '2026-08-13 22:47:54', '2026-08-13 22:47:54'),
(34, 'Toni Kurniawan', 'anggota.tonikurniawan@koperasi.test', 'TOP-100031', NULL, 'local', NULL, '$2y$12$qMW.9SAwjN2xe7Fbx9y1QuRW4WY/RggbxOJklbRU8qekWqi0reFlK', 0, NULL, '2026-08-13 22:47:54', '2026-08-13 22:47:54'),
(35, 'Tuti Herawati', 'anggota.tutiherawati@koperasi.test', 'TOP-100032', NULL, 'local', NULL, '$2y$12$kv.hV9tl/MADJMLVZaRgv.LfLmI4rg0Rgs3NF.MKUFiGqgWJ2hWiy', 0, NULL, '2026-08-13 22:47:54', '2026-08-13 22:47:54'),
(36, 'Ferry Ardiansyah', 'anggota.ferryardiansyah@koperasi.test', 'TOP-100033', NULL, 'local', NULL, '$2y$12$dbH07u/yIgViuFGtf8VEguKUC9m/Bo.NrYjzOxpBXCASKlx2YyE6u', 0, NULL, '2026-08-13 22:47:54', '2026-08-13 22:47:54'),
(37, 'Desi Ratnasari', 'anggota.desiratnasari@koperasi.test', 'TOP-100034', NULL, 'local', NULL, '$2y$12$mUkOVSB8fOVejMj5M.S2iezqG2LDgkUnVo035z7nCAga2Ybc9dMqK', 0, NULL, '2026-08-13 22:47:55', '2026-08-13 22:47:55'),
(38, 'Imam Santoso', 'anggota.imamsantoso@koperasi.test', 'TOP-100035', NULL, 'local', NULL, '$2y$12$nMNHgnab2mEG4m9Wu6XAJOlAfQu82iKNhSRGTa6mGIsITpFvwiEv2', 0, NULL, '2026-08-13 22:47:55', '2026-08-13 22:47:55'),
(39, 'Widya Astuti', 'anggota.widyaastuti@koperasi.test', 'TOP-100036', NULL, 'local', NULL, '$2y$12$UuKjbEpHb.G6TCI2ilCr8uXgetSgNCJmxFCwqrU/PlHy1nCuh7Xem', 0, NULL, '2026-08-13 22:47:55', '2026-08-13 22:47:55'),
(40, 'Galih Prakoso', 'anggota.galihprakoso@koperasi.test', 'TOP-100037', NULL, 'local', NULL, '$2y$12$fV45htvycNw3gGjgbwfxfujb0smFXD4.BS3OsFK.k3emrHBXYkCJO', 0, NULL, '2026-08-13 22:47:55', '2026-08-13 22:47:55'),
(41, 'Nur Aini', 'anggota.nuraini@koperasi.test', 'TOP-100038', NULL, 'local', NULL, '$2y$12$JFlMxeI0OtHCiZ4DFidYHu3k2Hf8vt57fz7shO2LWX57e.DoP0jJ6', 0, NULL, '2026-08-13 22:47:56', '2026-08-13 22:47:56'),
(42, 'Satria Bima', 'anggota.satriabima@koperasi.test', 'TOP-100039', NULL, 'local', NULL, '$2y$12$n9Q3UxZAwCf0FzBM1wPvV.1/aglV0.vO6NekeGRKtmZoBkpnPpfES', 0, NULL, '2026-08-13 22:47:56', '2026-08-13 22:47:56'),
(43, 'Laila Amalia', 'anggota.lailaamalia@koperasi.test', 'TOP-100040', NULL, 'local', NULL, '$2y$12$BrzsgDDSnBuZU.5iEKi/b.Ap0JXGEYFwtFZIlyx4Slf9dQpEwUZna', 0, NULL, '2026-08-13 22:47:56', '2026-08-13 22:47:56'),
(44, 'Wisnu Prasetyo', 'anggota.wisnuprasetyo@koperasi.test', 'TOP-100041', NULL, 'local', NULL, '$2y$12$hYMFICJjqUDXqY9ijk4o8OrZoB2bk8fvLi.dPBVFcNW/ONXpBm5YW', 0, NULL, '2026-08-13 22:47:56', '2026-08-13 22:47:56'),
(45, 'Mega Puspita', 'anggota.megapuspita@koperasi.test', 'TOP-100042', NULL, 'local', NULL, '$2y$12$EvDE5ibTpq60Yn42WwNo1O2sGSKL8/ke2kE8ocYNd8o.vGUFXI17O', 0, NULL, '2026-08-13 22:47:57', '2026-08-13 22:47:57'),
(46, 'Dimas Anggara', 'anggota.dimasanggara@koperasi.test', 'TOP-100043', NULL, 'local', NULL, '$2y$12$fl8eFQM9evC2PL6g94wdYeKYVOJOyKYK9BZadlDPpWgfQSfCTNysa', 0, NULL, '2026-08-13 22:47:57', '2026-08-13 22:47:57'),
(47, 'Nabila Putri', 'anggota.nabilaputri@koperasi.test', 'TOP-100044', NULL, 'local', NULL, '$2y$12$KyPqt8e1V5u/BOXpq1AHkOkJB1ejN1QlVz.lIWHorZ7eViFovIobO', 0, NULL, '2026-08-13 22:47:57', '2026-08-13 22:47:57'),
(48, 'Candra Wijaya', 'anggota.candrawijaya@koperasi.test', 'TOP-100045', NULL, 'local', NULL, '$2y$12$TBdvUib14QFefeYx5MMrGufxgiGA2.kKBjSKj6.TJ2hNT5GazRlga', 0, NULL, '2026-08-13 22:47:58', '2026-08-13 22:47:58'),
(49, 'Yuni Astuti', 'anggota.yuniastuti@koperasi.test', 'TOP-100046', NULL, 'local', NULL, '$2y$12$BxLnHNotSN7.OZ5DMCAcvO4sPHd/R2lFz41L/Krn8a37FvteBGwrK', 0, NULL, '2026-08-13 22:47:58', '2026-08-13 22:47:58'),
(50, 'Arif Hidayat', 'anggota.arifhidayat@koperasi.test', 'TOP-100047', NULL, 'local', NULL, '$2y$12$V85yXxUegU06/4Mxk7F0uuceGXJK2JT0OjGCqEs1SceCFUJU5ZruS', 0, NULL, '2026-08-13 22:47:58', '2026-08-13 22:47:58'),
(51, 'Rina Kusuma', 'anggota.rinakusuma@koperasi.test', 'TOP-100048', NULL, 'local', NULL, '$2y$12$GsKeggvKP10tHwwMMdyR1usheHCFBuPuHQNYCDF8LVzsatpWRAEsu', 0, NULL, '2026-08-13 22:47:58', '2026-08-13 22:47:58'),
(52, 'Bagus Pamungkas', 'anggota.baguspamungkas@koperasi.test', 'TOP-100049', NULL, 'local', NULL, '$2y$12$A4ZWaHf4tDIpCugM/pvMI.yM4aH9EwLUtYvvUQmHFEzS0h9szcBb.', 0, NULL, '2026-08-13 22:47:59', '2026-08-13 22:47:59'),
(53, 'Citra Ramadhani', 'anggota.citraramadhani@koperasi.test', 'TOP-100050', NULL, 'local', NULL, '$2y$12$OGic31HjDF3k1nnP8FjxKuVGGDx6mRW8i48q6uPg8cB6iFdiYN.yS', 0, NULL, '2026-08-13 22:47:59', '2026-08-13 22:47:59');

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
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

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
