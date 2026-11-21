-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for db_reimbursement
CREATE DATABASE IF NOT EXISTS `db_reimbursement` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `db_reimbursement`;

-- Dumping structure for table db_reimbursement.anggaran
CREATE TABLE IF NOT EXISTS `anggaran` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tahun` smallint unsigned NOT NULL,
  `kuartal` tinyint unsigned NOT NULL,
  `total_anggaran` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_anggaran` (`tahun`,`kuartal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table db_reimbursement.anggaran: ~0 rows (approximately)

-- Dumping structure for table db_reimbursement.divisi
CREATE TABLE IF NOT EXISTS `divisi` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nama_divisi` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama_divisi` (`nama_divisi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table db_reimbursement.divisi: ~0 rows (approximately)

-- Dumping structure for table db_reimbursement.jabatan
CREATE TABLE IF NOT EXISTS `jabatan` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nama_jabatan` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama_jabatan` (`nama_jabatan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table db_reimbursement.jabatan: ~0 rows (approximately)

-- Dumping structure for table db_reimbursement.kategori_klaim
CREATE TABLE IF NOT EXISTS `kategori_klaim` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ikon` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table db_reimbursement.kategori_klaim: ~0 rows (approximately)

-- Dumping structure for table db_reimbursement.klaim
CREATE TABLE IF NOT EXISTS `klaim` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `kode_klaim` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int unsigned NOT NULL,
  `kategori_id` int unsigned NOT NULL,
  `tanggal_transaksi` date NOT NULL,
  `nominal` int unsigned NOT NULL,
  `keterangan` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_nota` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','diajukan','menunggu_pembayaran','dibayar','ditolak') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `alasan_penolakan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_diajukan` datetime DEFAULT NULL,
  `tanggal_bayar` date DEFAULT NULL,
  `catatan_bayar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_klaim` (`kode_klaim`),
  KEY `idx_klaim_plafon` (`user_id`,`kategori_id`,`tanggal_transaksi`),
  KEY `idx_klaim_status` (`status`),
  KEY `fk_klaim_kategori` (`kategori_id`),
  CONSTRAINT `fk_klaim_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_klaim` (`id`),
  CONSTRAINT `fk_klaim_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table db_reimbursement.klaim: ~0 rows (approximately)

-- Dumping structure for table db_reimbursement.klaim_log
CREATE TABLE IF NOT EXISTS `klaim_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `klaim_id` int unsigned NOT NULL,
  `aktor_id` int unsigned NOT NULL,
  `status_lama` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_baru` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `catatan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_log_klaim` (`klaim_id`),
  KEY `fk_log_aktor` (`aktor_id`),
  CONSTRAINT `fk_log_aktor` FOREIGN KEY (`aktor_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_log_klaim` FOREIGN KEY (`klaim_id`) REFERENCES `klaim` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table db_reimbursement.klaim_log: ~0 rows (approximately)

-- Dumping structure for table db_reimbursement.plafon
CREATE TABLE IF NOT EXISTS `plafon` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `jabatan_id` int unsigned NOT NULL,
  `kategori_id` int unsigned NOT NULL,
  `kuota_bulanan` int unsigned NOT NULL,
  `akumulasi` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_plafon` (`jabatan_id`,`kategori_id`),
  KEY `fk_plafon_kategori` (`kategori_id`),
  CONSTRAINT `fk_plafon_jabatan` FOREIGN KEY (`jabatan_id`) REFERENCES `jabatan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_plafon_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_klaim` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table db_reimbursement.plafon: ~0 rows (approximately)

-- Dumping structure for table db_reimbursement.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nik` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('karyawan','atasan','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'karyawan',
  `divisi_id` int unsigned DEFAULT NULL,
  `jabatan_id` int unsigned DEFAULT NULL,
  `atasan_id` int unsigned DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nik` (`nik`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_users_divisi` (`divisi_id`),
  KEY `fk_users_jabatan` (`jabatan_id`),
  KEY `fk_users_atasan` (`atasan_id`),
  CONSTRAINT `fk_users_atasan` FOREIGN KEY (`atasan_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_users_divisi` FOREIGN KEY (`divisi_id`) REFERENCES `divisi` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_users_jabatan` FOREIGN KEY (`jabatan_id`) REFERENCES `jabatan` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table db_reimbursement.users: ~0 rows (approximately)

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
