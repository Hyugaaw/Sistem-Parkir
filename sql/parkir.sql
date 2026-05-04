-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 12, 2026 at 08:57 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `parkir`
--

-- --------------------------------------------------------

--
-- Table structure for table `tb_area_parkir`
--

CREATE TABLE `tb_area_parkir` (
  `id_area` int NOT NULL,
  `nama_area` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kapasitas` int DEFAULT NULL,
  `terisi` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_kendaraan`
--

CREATE TABLE `tb_kendaraan` (
  `id_kendaraan` int NOT NULL,
  `plat_nomor` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jenis_kendaraan` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `warna` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pemilik` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_user` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_log_aktivitas`
--

CREATE TABLE `tb_log_aktivitas` (
  `id_log` int NOT NULL,
  `id_user` int DEFAULT NULL,
  `aktivitas` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `waktu_aktivitas` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_tarif`
--

CREATE TABLE `tb_tarif` (
  `id_tarif` int NOT NULL,
  `jenis_kendaraan` enum('motor','mobil','lainnya','') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tarif_per_jam` decimal(10,0) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_transaksi`
--

CREATE TABLE `tb_transaksi` (
  `id_parkir` int NOT NULL,
  `id_kendaraan` int DEFAULT NULL,
  `waktu_masuk` datetime DEFAULT NULL,
  `waktu_keluar` datetime DEFAULT NULL,
  `id_tarif` int DEFAULT NULL,
  `durasi_jam` int DEFAULT NULL,
  `biaya_total` decimal(10,0) DEFAULT NULL,
  `status` enum('masuk','keluar','') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_user` int DEFAULT NULL,
  `id_area` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_user`
--

CREATE TABLE `tb_user` (
  `id_user` int NOT NULL,
  `nama_lengkap` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` enum('admin','petugas','owner','') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_aktif` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tb_area_parkir`
--
ALTER TABLE `tb_area_parkir`
  ADD PRIMARY KEY (`id_area`);

--
-- Indexes for table `tb_kendaraan`
--
ALTER TABLE `tb_kendaraan`
  ADD PRIMARY KEY (`id_kendaraan`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `tb_log_aktivitas`
--
ALTER TABLE `tb_log_aktivitas`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `tb_tarif`
--
ALTER TABLE `tb_tarif`
  ADD PRIMARY KEY (`id_tarif`);

--
-- Indexes for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  ADD PRIMARY KEY (`id_parkir`),
  ADD KEY `id_kendaraan` (`id_kendaraan`),
  ADD KEY `id_tarif` (`id_tarif`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_area` (`id_area`);

--
-- Indexes for table `tb_user`
--
ALTER TABLE `tb_user`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tb_area_parkir`
--
ALTER TABLE `tb_area_parkir`
  MODIFY `id_area` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `tb_kendaraan`
--
ALTER TABLE `tb_kendaraan`
  MODIFY `id_kendaraan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `tb_log_aktivitas`
--
ALTER TABLE `tb_log_aktivitas`
  MODIFY `id_log` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=845;

--
-- AUTO_INCREMENT for table `tb_tarif`
--
ALTER TABLE `tb_tarif`
  MODIFY `id_tarif` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  MODIFY `id_parkir` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT for table `tb_user`
--
ALTER TABLE `tb_user`
  MODIFY `id_user` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tb_log_aktivitas`
--
ALTER TABLE `tb_log_aktivitas`
  ADD CONSTRAINT `tb_log_aktivitas_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `tb_user` (`id_user`);

--
-- Constraints for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  ADD CONSTRAINT `tb_transaksi_ibfk_1` FOREIGN KEY (`id_kendaraan`) REFERENCES `tb_kendaraan` (`id_kendaraan`) ON DELETE SET NULL,
  ADD CONSTRAINT `tb_transaksi_ibfk_2` FOREIGN KEY (`id_tarif`) REFERENCES `tb_tarif` (`id_tarif`) ON DELETE SET NULL,
  ADD CONSTRAINT `tb_transaksi_ibfk_3` FOREIGN KEY (`id_user`) REFERENCES `tb_user` (`id_user`),
  ADD CONSTRAINT `tb_transaksi_ibfk_4` FOREIGN KEY (`id_area`) REFERENCES `tb_area_parkir` (`id_area`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
