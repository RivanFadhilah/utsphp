<?php
/**
 * Sub-CPMK 3: Database Connectivity (Connection Strings & Driver DB)
 * Konfigurasi Database: 244242015
 * Nama Database       : 244242015
 */

date_default_timezone_set('Asia/Jakarta');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', '244242015');
define('DB_PORT', 3306);

$db_status = [
    'connected' => false,
    'message'   => '',
    'database'  => DB_NAME,
    'host'      => DB_HOST,
    'driver'    => 'MySQLi (PHP Extension)'
];

mysqli_report(MYSQLI_REPORT_OFF);

// 1. Inisialisasi koneksi ke MySQL Server
$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);

if ($conn->connect_errno) {
    $db_status['connected'] = false;
    $db_status['message']   = "Gagal terhubung ke server MySQL (" . $conn->connect_errno . "): " . $conn->connect_error;
} else {
    $conn->set_charset('utf8mb4');

    // 2. Pastikan database `244242015` tersedia (Auto-Setup jika belum ada)
    $createDbSql = "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if ($conn->query($createDbSql) && $conn->select_db(DB_NAME)) {
        $db_status['connected'] = true;
        $db_status['message']   = "Status Koneksi Sukses — Terhubung ke database '" . DB_NAME . "' pada " . DB_HOST;

        // 3. Auto-migrasi skema tabel berelasi (Sub-CPMK 2) jika tabel belum dibuat
        initDatabaseSchema($conn);
    } else {
        $db_status['connected'] = false;
        $db_status['message']   = "Gagal memilih atau membuat database '" . DB_NAME . "': " . $conn->error;
    }
}

/**
 * Fungsi inisialisasi otomatis tabel dan data awal (3NF Relasi: divisi -> anggota -> absensi)
 */
function initDatabaseSchema(mysqli $conn): void
{
    $conn->query("CREATE TABLE IF NOT EXISTS `divisi` (
        `id_divisi` INT AUTO_INCREMENT PRIMARY KEY,
        `kode_divisi` VARCHAR(20) NOT NULL UNIQUE,
        `nama_divisi` VARCHAR(100) NOT NULL,
        `lokasi_ruang` VARCHAR(100) DEFAULT 'Gedung Utama Lt. 2',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS `anggota` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS `absensi` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Cek apakah data awal sudah ada
    $resDivisi = $conn->query("SELECT COUNT(*) AS total FROM `divisi`");
    if ($resDivisi && ($row = $resDivisi->fetch_assoc()) && (int)$row['total'] === 0) {
        $conn->query("INSERT INTO `divisi` (`id_divisi`, `kode_divisi`, `nama_divisi`, `lokasi_ruang`) VALUES
            (1, 'TI-A', 'Teknik Informatika - Kelas A', 'Lab Komputer 1'),
            (2, 'TI-B', 'Teknik Informatika - Kelas B', 'Lab Komputer 2'),
            (3, 'SI-A', 'Sistem Informasi - Kelas A', 'Ruang R.204'),
            (4, 'RPL-A', 'Rekayasa Perangkat Lunak', 'Lab Pemrograman Web')");

        $conn->query("INSERT INTO `anggota` (`id_anggota`, `nomor_induk`, `nama_lengkap`, `email`, `id_divisi`) VALUES
            (1, '244242015', 'Rivan', 'rivan@kampus.ac.id', 1),
            (2, '244242016', 'Budi Santoso', 'budi.santoso@kampus.ac.id', 1),
            (3, '244242017', 'Siti Aminah', 'siti.aminah@kampus.ac.id', 2),
            (4, '244242018', 'Andi Pratama', 'andi.pratama@kampus.ac.id', 3),
            (5, '244242019', 'Dewi Lestari', 'dewi.lestari@kampus.ac.id', 4)");

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $conn->query("INSERT INTO `absensi` (`id_anggota`, `tanggal`, `jam_masuk`, `jam_keluar`, `status_kehadiran`, `keterangan`) VALUES
            (1, '$today', '07:45:00', '16:00:00', 'Hadir', 'Hadir tepat waktu - Praktikum Web Database'),
            (2, '$today', '07:55:00', '16:05:00', 'Hadir', 'Mengikuti sesi praktikum CRUD PHP'),
            (3, '$today', '08:00:00', NULL, 'Izin', 'Izin mengikuti lomba akademik kampus'),
            (4, '$today', '08:10:00', NULL, 'Sakit', 'Surat keterangan dokter terlampir'),
            (5, '$yesterday', '07:50:00', '15:55:00', 'Hadir', 'Praktikum Koneksi Database & ERD')");
    }
}

/**
 * Helper sanitasi output HTML (mencegah XSS)
 */
function e(?string $str): string
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
