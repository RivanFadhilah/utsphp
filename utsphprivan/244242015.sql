-- ==========================================================
-- DATABASE ABSENSI WEB - NIM / DB CONFIG: 244242015
-- Memenuhi Sub-CPMK 1 s/d Sub-CPMK 7 (Normalisasi 3NF & Relasi ERD)
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `244242015`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `244242015`;

-- ----------------------------------------------------------
-- 1. Tabel Master `divisi` (Entitas Independen - 3NF)
-- Menyimpan daftar Kelas / Divisi / Departemen
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `divisi` (
  `id_divisi` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_divisi` VARCHAR(20) NOT NULL UNIQUE,
  `nama_divisi` VARCHAR(100) NOT NULL,
  `lokasi_ruang` VARCHAR(100) DEFAULT 'Gedung Utama Lt. 2',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 2. Tabel Master `anggota` (Berelasi Many-to-One ke `divisi`)
-- Menyimpan data Mahasiswa / Pegawai yang melakukan absensi
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `anggota` (
  `id_anggota` INT AUTO_INCREMENT PRIMARY KEY,
  `nomor_induk` VARCHAR(30) NOT NULL UNIQUE,
  `nama_lengkap` VARCHAR(120) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `id_divisi` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_anggota_divisi`
    FOREIGN KEY (`id_divisi`) REFERENCES `divisi`(`id_divisi`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- 3. Tabel Transaksi `absensi` (Berelasi Many-to-One ke `anggota`)
-- Menyimpan log kehadiran harian (Create, Read, Update, Delete)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `absensi` (
  `id_absensi` INT AUTO_INCREMENT PRIMARY KEY,
  `id_anggota` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `jam_masuk` TIME NOT NULL,
  `jam_keluar` TIME DEFAULT NULL,
  `status_kehadiran` ENUM('Hadir', 'Izin', 'Sakit', 'Alpha') NOT NULL DEFAULT 'Hadir',
  `keterangan` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_absensi_anggota`
    FOREIGN KEY (`id_anggota`) REFERENCES `anggota`(`id_anggota`)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- Data Awal (Seeder) `divisi`
-- ----------------------------------------------------------
INSERT IGNORE INTO `divisi` (`id_divisi`, `kode_divisi`, `nama_divisi`, `lokasi_ruang`) VALUES
(1, 'TI-A', 'Teknik Informatika - Kelas A', 'Lab Komputer 1'),
(2, 'TI-B', 'Teknik Informatika - Kelas B', 'Lab Komputer 2'),
(3, 'SI-A', 'Sistem Informasi - Kelas A', 'Ruang R.204'),
(4, 'RPL-A', 'Rekayasa Perangkat Lunak', 'Lab Pemrograman Web');

-- ----------------------------------------------------------
-- Data Awal (Seeder) `anggota`
-- ----------------------------------------------------------
INSERT IGNORE INTO `anggota` (`id_anggota`, `nomor_induk`, `nama_lengkap`, `email`, `id_divisi`) VALUES
(1, '244242015', 'Rivan', 'rivan@kampus.ac.id', 1),
(2, '244242016', 'Budi Santoso', 'budi.santoso@kampus.ac.id', 1),
(3, '244242017', 'Siti Aminah', 'siti.aminah@kampus.ac.id', 2),
(4, '244242018', 'Andi Pratama', 'andi.pratama@kampus.ac.id', 3),
(5, '244242019', 'Dewi Lestari', 'dewi.lestari@kampus.ac.id', 4);

-- ----------------------------------------------------------
-- Data Awal (Seeder) `absensi`
-- ----------------------------------------------------------
INSERT IGNORE INTO `absensi` (`id_absensi`, `id_anggota`, `tanggal`, `jam_masuk`, `jam_keluar`, `status_kehadiran`, `keterangan`) VALUES
(1, 1, CURDATE(), '07:45:00', '16:00:00', 'Hadir', 'Hadir tepat waktu - Praktikum Web Database'),
(2, 2, CURDATE(), '07:55:00', '16:05:00', 'Hadir', 'Mengikuti sesi praktikum CRUD PHP'),
(3, 3, CURDATE(), '08:00:00', NULL, 'Izin', 'Izin mengikuti lomba akademik kampus'),
(4, 4, CURDATE(), '08:10:00', NULL, 'Sakit', 'Surat keterangan dokter terlampir'),
(5, 5, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '07:50:00', '15:55:00', 'Hadir', 'Praktikum Koneksi Database & ERD');
