<?php
session_start();
require_once __DIR__ . '/koneksi.php';

/**
 * Sub-CPMK 7: CRUD Part 4 - Data Deletion & Confirmation Alerts
 * Menghapus record secara permanen dari database `244242015` berdasarkan ID
 */

$id_absensi = (int)($_GET['id'] ?? 0);

if ($id_absensi <= 0) {
    $_SESSION['flash_error'] = "ID absensi tidak valid untuk dihapus.";
    header("Location: index.php");
    exit;
}

// Periksa keberadaan data sebelum dihapus
$stmtCheck = $conn->prepare("SELECT ab.id_absensi, ab.tanggal, ag.nama_lengkap
                             FROM absensi ab
                             INNER JOIN anggota ag ON ab.id_anggota = ag.id_anggota
                             WHERE ab.id_absensi = ? LIMIT 1");
$stmtCheck->bind_param("i", $id_absensi);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();
$target = $resCheck->fetch_assoc();
$stmtCheck->close();

if (!$target) {
    $_SESSION['flash_error'] = "Data absensi #$id_absensi sudah tidak ditemukan di database.";
    header("Location: index.php");
    exit;
}

// Eksekusi penghapusan permanen (DELETE)
$stmtDelete = $conn->prepare("DELETE FROM absensi WHERE id_absensi = ?");
$stmtDelete->bind_param("i", $id_absensi);

if ($stmtDelete->execute()) {
    $_SESSION['flash_success'] = "Data absensi #" . $id_absensi . " (" . $target['nama_lengkap'] . " - " . $target['tanggal'] . ") berhasil dihapus permanen dari database '" . DB_NAME . "'.";
} else {
    $_SESSION['flash_error'] = "Gagal menghapus data absensi: " . $stmtDelete->error;
}

$stmtDelete->close();
header("Location: index.php");
exit;
