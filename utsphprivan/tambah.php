<?php
session_start();
require_once __DIR__ . '/koneksi.php';

/**
 * Sub-CPMK 4: CRUD Part 1 - Data Insertion & Form Handling
 * Membuat form input dan mengirimkan data ke database `244242015`
 */

$errors = [];
$old = [
    'id_anggota'       => '',
    'tanggal'          => date('Y-m-d'),
    'jam_masuk'        => date('H:i'),
    'jam_keluar'       => '',
    'status_kehadiran' => 'Hadir',
    'keterangan'       => ''
];

// Ambil daftar anggota untuk dropdown
$anggota_list = [];
if ($db_status['connected']) {
    $res = $conn->query("SELECT a.id_anggota, a.nomor_induk, a.nama_lengkap, d.kode_divisi, d.nama_divisi
                         FROM anggota a
                         INNER JOIN divisi d ON a.id_divisi = d.id_divisi
                         ORDER BY a.nomor_induk ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $anggota_list[] = $row;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['id_anggota']       = (int)($_POST['id_anggota'] ?? 0);
    $old['tanggal']          = trim($_POST['tanggal'] ?? '');
    $old['jam_masuk']        = trim($_POST['jam_masuk'] ?? '');
    $old['jam_keluar']       = trim($_POST['jam_keluar'] ?? '');
    $old['status_kehadiran'] = trim($_POST['status_kehadiran'] ?? 'Hadir');
    $old['keterangan']       = trim($_POST['keterangan'] ?? '');

    if ($old['id_anggota'] <= 0) {
        $errors[] = "Silakan pilih mahasiswa / anggota terlebih dahulu.";
    }
    if (empty($old['tanggal'])) {
        $errors[] = "Tanggal absensi wajib diisi.";
    }
    if (empty($old['jam_masuk'])) {
        $errors[] = "Jam masuk wajib diisi.";
    }
    if (!in_array($old['status_kehadiran'], ['Hadir', 'Izin', 'Sakit', 'Alpha'], true)) {
        $errors[] = "Status kehadiran tidak valid.";
    }

    if (empty($errors)) {
        $jam_keluar_db = $old['jam_keluar'] !== '' ? $old['jam_keluar'] : null;
        $stmt = $conn->prepare("INSERT INTO absensi (id_anggota, tanggal, jam_masuk, jam_keluar, status_kehadiran, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "isssss",
            $old['id_anggota'],
            $old['tanggal'],
            $old['jam_masuk'],
            $jam_keluar_db,
            $old['status_kehadiran'],
            $old['keterangan']
        );

        if ($stmt->execute()) {
            $_SESSION['flash_success'] = "Record absensi baru berhasil ditambahkan ke database '" . DB_NAME . "'.";
            $stmt->close();
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Gagal menyimpan ke database: " . $stmt->error;
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Data Absensi (Create) — DB 244242015</title>
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
                <a href="tambah.php" class="nav-link active">+ Input Absensi (Create)</a>
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
                <strong>Modul:</strong> Sub-CPMK 4 (CRUD Part 1: Data Insertion &amp; Form Handling)
            </div>
        </div>
    </div>

    <main class="container">
        <section class="card" style="max-width: 780px; margin: 0 auto;">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Form Tambah Data Kehadiran (Create)</h2>
                    <p class="card-subtitle">Sub-CPMK 4: Mengimplementasikan fitur penyimpanan data ke database <code>244242015</code></p>
                </div>
                <a href="index.php" class="btn btn-secondary btn-sm">&larr; Kembali ke Tabel</a>
            </div>
            <div class="card-body">

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <div>
                            <strong>Terjadi Kesalahan Input:</strong>
                            <ul style="margin-left: 1.2rem; margin-top: 0.35rem;">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= e($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="tambah.php" method="POST">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="id_anggota">Pilih Mahasiswa / Anggota <span style="color:red">*</span></label>
                            <select name="id_anggota" id="id_anggota" required>
                                <option value="">-- Pilih Mahasiswa / Anggota --</option>
                                <?php foreach ($anggota_list as $ag): ?>
                                    <option value="<?= (int)$ag['id_anggota'] ?>" <?= (int)$old['id_anggota'] === (int)$ag['id_anggota'] ? 'selected' : '' ?>>
                                        <?= e($ag['nomor_induk']) ?> — <?= e($ag['nama_lengkap']) ?> (<?= e($ag['kode_divisi']) ?> - <?= e($ag['nama_divisi']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="tanggal">Tanggal Absensi <span style="color:red">*</span></label>
                            <input type="date" name="tanggal" id="tanggal" value="<?= e($old['tanggal']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="status_kehadiran">Status Kehadiran <span style="color:red">*</span></label>
                            <select name="status_kehadiran" id="status_kehadiran" required>
                                <?php foreach (['Hadir', 'Izin', 'Sakit', 'Alpha'] as $st): ?>
                                    <option value="<?= $st ?>" <?= $old['status_kehadiran'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="jam_masuk">Jam Masuk <span style="color:red">*</span></label>
                            <input type="time" name="jam_masuk" id="jam_masuk" value="<?= e($old['jam_masuk']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="jam_keluar">Jam Keluar (Opsional)</label>
                            <input type="time" name="jam_keluar" id="jam_keluar" value="<?= e($old['jam_keluar']) ?>">
                        </div>

                        <div class="form-group full-width">
                            <label for="keterangan">Keterangan / Catatan Kehadiran</label>
                            <textarea name="keterangan" id="keterangan" rows="3" placeholder="Tuliskan materi perkuliahan, alasan izin/sakit, atau keterangan tambahan..."><?= e($old['keterangan']) ?></textarea>
                        </div>
                    </div>

                    <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
                        <a href="index.php" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Data ke Database</button>
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
