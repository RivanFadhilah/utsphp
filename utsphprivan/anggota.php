<?php
session_start();
require_once __DIR__ . '/koneksi.php';

/**
 * Sub-CPMK 2: Database Design - Normalisasi (3NF) & Relasi Tabel (ERD)
 * Mengelola tabel master `anggota` dan `divisi` yang berelasi dengan `absensi`
 */

// Tambah Anggota Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_anggota') {
    $nomor_induk  = trim($_POST['nomor_induk'] ?? '');
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $id_divisi    = (int)($_POST['id_divisi'] ?? 0);

    if (empty($nomor_induk) || empty($nama_lengkap) || empty($email) || $id_divisi <= 0) {
        $_SESSION['flash_error'] = "Semua kolom data anggota wajib diisi.";
    } else {
        $stmt = $conn->prepare("INSERT INTO anggota (nomor_induk, nama_lengkap, email, id_divisi) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $nomor_induk, $nama_lengkap, $email, $id_divisi);
        if ($stmt->execute()) {
            $_SESSION['flash_success'] = "Anggota baru '$nama_lengkap' ($nomor_induk) berhasil ditambahkan.";
        } else {
            $_SESSION['flash_error'] = "Gagal menambahkan anggota (pastikan NIM belum terdaftar): " . $stmt->error;
        }
        $stmt->close();
    }
    header("Location: anggota.php");
    exit;
}

// Hapus Anggota
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    if ($del_id > 0) {
        $stmtDel = $conn->prepare("DELETE FROM anggota WHERE id_anggota = ?");
        $stmtDel->bind_param("i", $del_id);
        if ($stmtDel->execute()) {
            $_SESSION['flash_success'] = "Data anggota berhasil dihapus permanen beserta riwayat absensinya (ON DELETE CASCADE).";
        } else {
            $_SESSION['flash_error'] = "Gagal menghapus anggota: " . $stmtDel->error;
        }
        $stmtDel->close();
    }
    header("Location: anggota.php");
    exit;
}

// Ambil data divisi
$divisi_list = [];
$resDiv = $conn->query("SELECT d.*, COUNT(a.id_anggota) AS total_anggota
                        FROM divisi d
                        LEFT JOIN anggota a ON d.id_divisi = a.id_divisi
                        GROUP BY d.id_divisi
                        ORDER BY d.id_divisi ASC");
if ($resDiv) {
    while ($row = $resDiv->fetch_assoc()) {
        $divisi_list[] = $row;
    }
}

// Ambil data anggota beserta total kehadirannya
$anggota_list = [];
$resAng = $conn->query("SELECT a.*, d.kode_divisi, d.nama_divisi, d.lokasi_ruang,
                               COUNT(ab.id_absensi) AS total_absensi
                        FROM anggota a
                        INNER JOIN divisi d ON a.id_divisi = d.id_divisi
                        LEFT JOIN absensi ab ON a.id_anggota = ab.id_anggota
                        GROUP BY a.id_anggota
                        ORDER BY a.nomor_induk ASC");
if ($resAng) {
    while ($row = $resAng->fetch_assoc()) {
        $anggota_list[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Anggota &amp; Relasi Tabel (3NF) — DB 244242015</title>
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
                <a href="anggota.php" class="nav-link active">Data Anggota &amp; Divisi (Relasi 3NF)</a>
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
                <strong>Modul:</strong> Sub-CPMK 2 (Normalisasi 3NF &amp; Relasi Tabel ERD)
            </div>
        </div>
    </div>

    <main class="container">

        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="alert alert-success">
                <span><strong>Berhasil!</strong> <?= e($_SESSION['flash_success']) ?></span>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger">
                <span><strong>Perhatian!</strong> <?= e($_SESSION['flash_error']) ?></span>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <!-- Form Tambah Anggota -->
        <section class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Tambah Mahasiswa / Anggota Baru</h2>
                    <p class="card-subtitle">Berelasi Foreign Key (<code>id_divisi</code>) ke tabel master <code>divisi</code></p>
                </div>
            </div>
            <div class="card-body">
                <form action="anggota.php" method="POST">
                    <input type="hidden" name="action" value="add_anggota">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nomor_induk">NIM / Nomor Induk <span style="color:red">*</span></label>
                            <input type="text" name="nomor_induk" id="nomor_induk" placeholder="Contoh: 244242020" required>
                        </div>
                        <div class="form-group">
                            <label for="nama_lengkap">Nama Lengkap <span style="color:red">*</span></label>
                            <input type="text" name="nama_lengkap" id="nama_lengkap" placeholder="Nama lengkap mahasiswa" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Kampus <span style="color:red">*</span></label>
                            <input type="email" name="email" id="email" placeholder="nama@kampus.ac.id" required>
                        </div>
                        <div class="form-group">
                            <label for="id_divisi">Kelas / Divisi <span style="color:red">*</span></label>
                            <select name="id_divisi" id="id_divisi" required>
                                <option value="">-- Pilih Kelas / Divisi --</option>
                                <?php foreach ($divisi_list as $dv): ?>
                                    <option value="<?= (int)$dv['id_divisi'] ?>">
                                        <?= e($dv['kode_divisi']) ?> - <?= e($dv['nama_divisi']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div style="margin-top: 1.15rem; display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary">+ Simpan Anggota</button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Daftar Anggota (Tabel Master 2) -->
        <section class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Tabel Master Anggota / Mahasiswa (<code>anggota</code>)</h2>
                    <p class="card-subtitle">Menampilkan relasi <code>anggota</code> &rarr; <code>divisi</code> serta jumlah log kehadiran di tabel <code>absensi</code></p>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIM / Nomor Induk</th>
                                <th>Nama Lengkap</th>
                                <th>Email</th>
                                <th>Kelas / Divisi (FK)</th>
                                <th>Ruang</th>
                                <th>Total Log Absensi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($anggota_list as $idx => $ag): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td><strong><?= e($ag['nomor_induk']) ?></strong></td>
                                    <td><?= e($ag['nama_lengkap']) ?></td>
                                    <td><?= e($ag['email']) ?></td>
                                    <td><span class="badge-status status-Izin"><?= e($ag['kode_divisi']) ?> - <?= e($ag['nama_divisi']) ?></span></td>
                                    <td><?= e($ag['lokasi_ruang']) ?></td>
                                    <td><strong><?= (int)$ag['total_absensi'] ?></strong> record</td>
                                    <td>
                                        <a href="anggota.php?delete_id=<?= (int)$ag['id_anggota'] ?>"
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirmDeleteAnggota(<?= (int)$ag['id_anggota'] ?>, '<?= e(addslashes($ag['nama_lengkap'])) ?>', '<?= e($ag['nomor_induk']) ?>');">
                                            Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Daftar Divisi (Tabel Master 1) -->
        <section class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Tabel Master Kelas / Divisi (<code>divisi</code>)</h2>
                    <p class="card-subtitle">Entitas induk (1NF, 2NF, 3NF) untuk menghindari redundansi nama kelas dan lokasi ruang</p>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID Divisi (PK)</th>
                                <th>Kode Kelas / Divisi</th>
                                <th>Nama Kelas / Divisi</th>
                                <th>Lokasi Ruang</th>
                                <th>Jumlah Anggota Terdaftar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($divisi_list as $dv): ?>
                                <tr>
                                    <td>#<?= (int)$dv['id_divisi'] ?></td>
                                    <td><strong><?= e($dv['kode_divisi']) ?></strong></td>
                                    <td><?= e($dv['nama_divisi']) ?></td>
                                    <td><?= e($dv['lokasi_ruang']) ?></td>
                                    <td><?= (int)$dv['total_anggota'] ?> orang</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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
