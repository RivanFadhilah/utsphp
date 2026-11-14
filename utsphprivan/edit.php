<?php
session_start();
require_once __DIR__ . '/koneksi.php';

/**
 * Sub-CPMK 6: CRUD Part 3 - Data Updating & Form Pre-filling
 * Membuat mekanisme edit data berdasarkan ID spesifik pada database `244242015`
 */

$id_absensi = (int)($_GET['id'] ?? $_POST['id_absensi'] ?? 0);

if ($id_absensi <= 0) {
    $_SESSION['flash_error'] = "ID absensi tidak valid atau tidak ditemukan.";
    header("Location: index.php");
    exit;
}

// Ambil data record yang akan di-edit untuk Form Pre-filling
$stmt = $conn->prepare("SELECT * FROM absensi WHERE id_absensi = ? LIMIT 1");
$stmt->bind_param("i", $id_absensi);
$stmt->execute();
$res = $stmt->get_result();
$record = $res->fetch_assoc();
$stmt->close();

if (!$record) {
    $_SESSION['flash_error'] = "Record absensi dengan ID #$id_absensi tidak ditemukan di database.";
    header("Location: index.php");
    exit;
}

// Ambil daftar anggota untuk pilihan dropdown
$anggota_list = [];
$resAnggota = $conn->query("SELECT a.id_anggota, a.nomor_induk, a.nama_lengkap, d.kode_divisi, d.nama_divisi
                            FROM anggota a
                            INNER JOIN divisi d ON a.id_divisi = d.id_divisi
                            ORDER BY a.nomor_induk ASC");
if ($resAnggota) {
    while ($row = $resAnggota->fetch_assoc()) {
        $anggota_list[] = $row;
    }
}

$errors = [];

// Proses penyimpanan perubahan (UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_anggota       = (int)($_POST['id_anggota'] ?? 0);
    $tanggal          = trim($_POST['tanggal'] ?? '');
    $jam_masuk        = trim($_POST['jam_masuk'] ?? '');
    $jam_keluar       = trim($_POST['jam_keluar'] ?? '');
    $status_kehadiran = trim($_POST['status_kehadiran'] ?? 'Hadir');
    $keterangan       = trim($_POST['keterangan'] ?? '');

    if ($id_anggota <= 0) {
        $errors[] = "Mahasiswa / anggota wajib dipilih.";
    }
    if (empty($tanggal)) {
        $errors[] = "Tanggal presensi wajib diisi.";
    }
    if (empty($jam_masuk)) {
        $errors[] = "Jam masuk wajib diisi.";
    }
    if (!in_array($status_kehadiran, ['Hadir', 'Izin', 'Sakit', 'Alpha'], true)) {
        $errors[] = "Status kehadiran tidak valid.";
    }

    if (empty($errors)) {
        $jam_keluar_db = $jam_keluar !== '' ? $jam_keluar : null;
        $stmtUpdate = $conn->prepare("UPDATE absensi 
                                      SET id_anggota = ?, tanggal = ?, jam_masuk = ?, jam_keluar = ?, status_kehadiran = ?, keterangan = ?
                                      WHERE id_absensi = ?");
        $stmtUpdate->bind_param(
            "isssssi",
            $id_anggota,
            $tanggal,
            $jam_masuk,
            $jam_keluar_db,
            $status_kehadiran,
            $keterangan,
            $id_absensi
        );

        if ($stmtUpdate->execute()) {
            $_SESSION['flash_success'] = "Record absensi #$id_absensi berhasil diperbarui (Update sukses).";
            $stmtUpdate->close();
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Gagal memperbarui data: " . $stmtUpdate->error;
            $stmtUpdate->close();
        }
    }

    // Pertahankan nilai input jika terjadi error validasi
    $record['id_anggota']       = $id_anggota;
    $record['tanggal']          = $tanggal;
    $record['jam_masuk']        = $jam_masuk;
    $record['jam_keluar']       = $jam_keluar;
    $record['status_kehadiran'] = $status_kehadiran;
    $record['keterangan']       = $keterangan;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Absensi #<?= (int)$id_absensi ?> (Update) — DB 244242015</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

    <header class="navbar">
        <div class="container navbar-inner">
            <a href="index.php" class="brand">
                <div class="brand-icon">AB</div>
                <div class="brand-text">
                    <h1>Sistem Absensi Web Database</h1>
                    <span>Config &amp; Database: 244242015 (Localhost PHP &amp; MySQL)</span>
                </div>
            </a>
            <nav class="nav-links">
                <a href="index.php" class="nav-link">Dashboard &amp; Data Absensi</a>
                <a href="tambah.php" class="nav-link">+ Input Absensi (Create)</a>
                <a href="anggota.php" class="nav-link">Data Anggota &amp; Divisi (Relasi 3NF)</a>
            </nav>
        </div>
    </header>

    <div class="status-bar">
        <div class="container status-bar-inner">
            <div>
                <span class="db-badge <?= $db_status['connected'] ? 'ok' : 'err' ?>">
                    <span class="pulse-dot <?= $db_status['connected'] ? '' : 'err' ?>"></span>
                    <?= e($db_status['message']) ?>
                </span>
            </div>
            <div>
                <strong>Modul:</strong> Sub-CPMK 6 (CRUD Part 3: Data Updating &amp; Form Pre-filling)
            </div>
        </div>
    </div>

    <main class="container">
        <section class="card" style="max-width: 780px; margin: 0 auto;">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Edit Data Absensi #<?= (int)$id_absensi ?> (Form Pre-filling)</h2>
                    <p class="card-subtitle">Sub-CPMK 6: Memperbarui record absensi berdasarkan ID spesifik pada database <code>244242015</code></p>
                </div>
                <a href="index.php" class="btn btn-secondary btn-sm">&larr; Kembali</a>
            </div>
            <div class="card-body">

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <div>
                            <strong>Gagal Memperbarui Data:</strong>
                            <ul style="margin-left: 1.2rem; margin-top: 0.35rem;">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= e($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="edit.php?id=<?= (int)$id_absensi ?>" method="POST">
                    <input type="hidden" name="id_absensi" value="<?= (int)$id_absensi ?>">

                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="id_anggota">Mahasiswa / Anggota <span style="color:red">*</span></label>
                            <select name="id_anggota" id="id_anggota" required>
                                <option value="">-- Pilih Mahasiswa / Anggota --</option>
                                <?php foreach ($anggota_list as $ag): ?>
                                    <option value="<?= (int)$ag['id_anggota'] ?>" <?= (int)$record['id_anggota'] === (int)$ag['id_anggota'] ? 'selected' : '' ?>>
                                        <?= e($ag['nomor_induk']) ?> — <?= e($ag['nama_lengkap']) ?> (<?= e($ag['kode_divisi']) ?> - <?= e($ag['nama_divisi']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="tanggal">Tanggal Absensi <span style="color:red">*</span></label>
                            <input type="date" name="tanggal" id="tanggal" value="<?= e($record['tanggal']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="status_kehadiran">Status Kehadiran <span style="color:red">*</span></label>
                            <select name="status_kehadiran" id="status_kehadiran" required>
                                <?php foreach (['Hadir', 'Izin', 'Sakit', 'Alpha'] as $st): ?>
                                    <option value="<?= $st ?>" <?= $record['status_kehadiran'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="jam_masuk">Jam Masuk <span style="color:red">*</span></label>
                            <input type="time" name="jam_masuk" id="jam_masuk" value="<?= e(substr($record['jam_masuk'], 0, 5)) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="jam_keluar">Jam Keluar (Opsional)</label>
                            <input type="time" name="jam_keluar" id="jam_keluar" value="<?= !empty($record['jam_keluar']) ? e(substr($record['jam_keluar'], 0, 5)) : '' ?>">
                        </div>

                        <div class="form-group full-width">
                            <label for="keterangan">Keterangan / Catatan Kehadiran</label>
                            <textarea name="keterangan" id="keterangan" rows="3"><?= e($record['keterangan']) ?></textarea>
                        </div>
                    </div>

                    <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
                        <a href="index.php" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan (Update)</button>
                    </div>
                </form>

            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            Aplikasi Absensi Web Berbasis PHP &amp; MySQL | Config &amp; Nama Database: <strong>244242015</strong>
        </div>
    </footer>

    <script src="assets/script.js"></script>
</body>
</html>
