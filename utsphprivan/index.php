<?php
session_start();
require_once __DIR__ . '/koneksi.php';

// -------------------------------------------------------------------
// Sub-CPMK 4: CRUD Part 1 - Data Insertion & Form Handling (Quick Create)
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_create') {
    $id_anggota       = (int)($_POST['id_anggota'] ?? 0);
    $tanggal          = trim($_POST['tanggal'] ?? '');
    $jam_masuk        = trim($_POST['jam_masuk'] ?? '');
    $jam_keluar       = !empty($_POST['jam_keluar']) ? trim($_POST['jam_keluar']) : null;
    $status_kehadiran = trim($_POST['status_kehadiran'] ?? 'Hadir');
    $keterangan       = trim($_POST['keterangan'] ?? '');

    $allowed_status = ['Hadir', 'Izin', 'Sakit', 'Alpha'];

    if ($id_anggota <= 0 || empty($tanggal) || empty($jam_masuk) || !in_array($status_kehadiran, $allowed_status, true)) {
        $_SESSION['flash_error'] = "Validasi gagal: Pastikan anggota, tanggal, jam masuk, dan status diisi dengan benar.";
    } else {
        $stmt = $conn->prepare("INSERT INTO absensi (id_anggota, tanggal, jam_masuk, jam_keluar, status_kehadiran, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $id_anggota, $tanggal, $jam_masuk, $jam_keluar, $status_kehadiran, $keterangan);

        if ($stmt->execute()) {
            $_SESSION['flash_success'] = "Data absensi berhasil disimpan ke database '" . DB_NAME . "'.";
        } else {
            $_SESSION['flash_error'] = "Gagal menyimpan data absensi: " . $stmt->error;
        }
        $stmt->close();
    }
    header("Location: index.php");
    exit;
}

// -------------------------------------------------------------------
// Sub-CPMK 5: CRUD Part 2 - Data Retrieval & Displaying Table (Read)
// -------------------------------------------------------------------
$filter_tanggal = trim($_GET['tanggal'] ?? '');
$filter_status  = trim($_GET['status'] ?? '');
$filter_divisi  = (int)($_GET['divisi'] ?? 0);
$keyword        = trim($_GET['q'] ?? '');

// Ambil daftar anggota untuk pilihan form
$anggota_list = [];
if ($db_status['connected']) {
    $resAnggota = $conn->query("SELECT a.id_anggota, a.nomor_induk, a.nama_lengkap, d.kode_divisi, d.nama_divisi 
                                FROM anggota a 
                                INNER JOIN divisi d ON a.id_divisi = d.id_divisi 
                                ORDER BY a.nomor_induk ASC");
    if ($resAnggota) {
        while ($row = $resAnggota->fetch_assoc()) {
            $anggota_list[] = $row;
        }
    }
}

// Ambil daftar divisi untuk filter
$divisi_list = [];
if ($db_status['connected']) {
    $resDivisi = $conn->query("SELECT * FROM divisi ORDER BY id_divisi ASC");
    if ($resDivisi) {
        while ($row = $resDivisi->fetch_assoc()) {
            $divisi_list[] = $row;
        }
    }
}

// Statistik ringkasan kehadiran
$stats = [
    'total' => 0,
    'Hadir' => 0,
    'Izin'  => 0,
    'Sakit' => 0,
    'Alpha' => 0
];

if ($db_status['connected']) {
    $resStats = $conn->query("SELECT status_kehadiran, COUNT(*) AS jumlah FROM absensi GROUP BY status_kehadiran");
    if ($resStats) {
        while ($row = $resStats->fetch_assoc()) {
            $st = $row['status_kehadiran'];
            $jml = (int)$row['jumlah'];
            if (isset($stats[$st])) {
                $stats[$st] = $jml;
            }
            $stats['total'] += $jml;
        }
    }
}

// Query utama menampilkan daftar absensi berelasi (JOIN absensi -> anggota -> divisi)
$absensi_rows = [];
if ($db_status['connected']) {
    $sql = "SELECT ab.id_absensi, ab.tanggal, ab.jam_masuk, ab.jam_keluar, ab.status_kehadiran, ab.keterangan,
                   ag.id_anggota, ag.nomor_induk, ag.nama_lengkap,
                   dv.kode_divisi, dv.nama_divisi, dv.lokasi_ruang
            FROM absensi ab
            INNER JOIN anggota ag ON ab.id_anggota = ag.id_anggota
            INNER JOIN divisi dv ON ag.id_divisi = dv.id_divisi
            WHERE 1=1";

    $params = [];
    $types  = "";

    if ($filter_tanggal !== '') {
        $sql .= " AND ab.tanggal = ?";
        $types .= "s";
        $params[] = $filter_tanggal;
    }
    if ($filter_status !== '') {
        $sql .= " AND ab.status_kehadiran = ?";
        $types .= "s";
        $params[] = $filter_status;
    }
    if ($filter_divisi > 0) {
        $sql .= " AND ag.id_divisi = ?";
        $types .= "i";
        $params[] = $filter_divisi;
    }
    if ($keyword !== '') {
        $sql .= " AND (ag.nama_lengkap LIKE ? OR ag.nomor_induk LIKE ? OR ab.keterangan LIKE ?)";
        $types .= "sss";
        $like = "%" . $keyword . "%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $sql .= " ORDER BY ab.tanggal DESC, ab.jam_masuk DESC, ab.id_absensi DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $absensi_rows[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi Absensi Web — DB 244242015</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

    <!-- Navigasi Utama -->
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
                <a href="index.php" class="nav-link active">Dashboard &amp; Data Absensi</a>
                <a href="tambah.php" class="nav-link">+ Input Absensi (Create)</a>
                <a href="anggota.php" class="nav-link">Data Anggota &amp; Divisi (Relasi 3NF)</a>
            </nav>
        </div>
    </header>

    <!-- Sub-CPMK 1 & Sub-CPMK 3: Indikator Status Server & Koneksi Database -->
    <div class="status-bar">
        <div class="container status-bar-inner">
            <div>
                <?php if ($db_status['connected']): ?>
                    <span class="db-badge ok">
                        <span class="pulse-dot"></span>
                        <?= e($db_status['message']) ?> (Driver: <?= e($db_status['driver']) ?>)
                    </span>
                <?php else: ?>
                    <span class="db-badge err">
                        <span class="pulse-dot err"></span>
                        <?= e($db_status['message']) ?>
                    </span>
                <?php endif; ?>
            </div>
            <div>
                <strong>Server:</strong> <?= e($_SERVER['SERVER_SOFTWARE'] ?? 'PHP Localhost') ?> |
                <strong>Waktu:</strong> <span id="live-clock"><?= date('H:i:s') ?> WIB</span>
            </div>
        </div>
    </div>

    <main class="container">

        <!-- Notifikasi Flash Message -->
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

        <!-- Ringkasan Statistik Kehadiran -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <span>Total Record</span>
                    <h3><?= $stats['total'] ?></h3>
                </div>
                <div class="stat-badge" style="background:#eff6ff; color:#1d4ed8;">ALL</div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <span>Hadir</span>
                    <h3><?= $stats['Hadir'] ?></h3>
                </div>
                <div class="stat-badge" style="background:#ecfdf5; color:#047857;">H</div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <span>Izin</span>
                    <h3><?= $stats['Izin'] ?></h3>
                </div>
                <div class="stat-badge" style="background:#f0f9ff; color:#0369a1;">I</div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <span>Sakit</span>
                    <h3><?= $stats['Sakit'] ?></h3>
                </div>
                <div class="stat-badge" style="background:#fffbeb; color:#b45309;">S</div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <span>Alpha</span>
                    <h3><?= $stats['Alpha'] ?></h3>
                </div>
                <div class="stat-badge" style="background:#fef2f2; color:#b91c1c;">A</div>
            </div>
        </section>

        <!-- Sub-CPMK 4: Form Tambah Absensi Cepat (Create) -->
        <section class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Input Presensi / Kehadiran Cepat (Create - Sub-CPMK 4)</h2>
                    <p class="card-subtitle">Simpan data kehadiran baru ke dalam tabel <code>absensi</code> pada database <code>244242015</code></p>
                </div>
                <a href="tambah.php" class="btn btn-secondary btn-sm">Buka Form Halaman Penuh &rarr;</a>
            </div>
            <div class="card-body">
                <form action="index.php" method="POST">
                    <input type="hidden" name="action" value="quick_create">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="id_anggota">Mahasiswa / Anggota <span style="color:red">*</span></label>
                            <select name="id_anggota" id="id_anggota" required>
                                <option value="">-- Pilih Nama / NIM --</option>
                                <?php foreach ($anggota_list as $ag): ?>
                                    <option value="<?= (int)$ag['id_anggota'] ?>">
                                        <?= e($ag['nomor_induk']) ?> - <?= e($ag['nama_lengkap']) ?> (<?= e($ag['kode_divisi']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="tanggal">Tanggal Presensi <span style="color:red">*</span></label>
                            <input type="date" name="tanggal" id="tanggal" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="jam_masuk">Jam Masuk <span style="color:red">*</span></label>
                            <input type="time" name="jam_masuk" id="jam_masuk" value="<?= date('H:i') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="jam_keluar">Jam Keluar (Opsional)</label>
                            <input type="time" name="jam_keluar" id="jam_keluar">
                        </div>

                        <div class="form-group">
                            <label for="status_kehadiran">Status Kehadiran <span style="color:red">*</span></label>
                            <select name="status_kehadiran" id="status_kehadiran" required>
                                <option value="Hadir">Hadir</option>
                                <option value="Izin">Izin</option>
                                <option value="Sakit">Sakit</option>
                                <option value="Alpha">Alpha</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="keterangan">Keterangan / Aktivitas</label>
                            <input type="text" name="keterangan" id="keterangan" placeholder="Contoh: Hadir praktikum Web Database">
                        </div>
                    </div>
                    <div style="margin-top: 1.15rem; display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary">Simpan Presensi ke Database</button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Sub-CPMK 5, 6, 7: Tabel Data Absensi (Read, Update, Delete) -->
        <section class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Daftar Riwayat Kehadiran (Read, Update, Delete - Sub-CPMK 5, 6, 7)</h2>
                    <p class="card-subtitle">Data ditampilkan secara dinamis menggunakan relasi INNER JOIN (<code>absensi</code> &rarr; <code>anggota</code> &rarr; <code>divisi</code>)</p>
                </div>
                <a href="tambah.php" class="btn btn-primary btn-sm">+ Tambah Data Baru</a>
            </div>
            <div class="card-body">

                <!-- Filter Pencarian -->
                <form action="index.php" method="GET" class="filter-bar">
                    <div class="filter-item">
                        <label for="q">Cari Nama / NIM / Ket.</label>
                        <input type="text" name="q" id="q" value="<?= e($keyword) ?>" placeholder="Ketik kata kunci...">
                    </div>
                    <div class="filter-item">
                        <label for="filter_tanggal">Filter Tanggal</label>
                        <input type="date" name="tanggal" id="filter_tanggal" value="<?= e($filter_tanggal) ?>">
                    </div>
                    <div class="filter-item">
                        <label for="filter_divisi">Kelas / Divisi</label>
                        <select name="divisi" id="filter_divisi">
                            <option value="0">Semua Kelas/Divisi</option>
                            <?php foreach ($divisi_list as $dv): ?>
                                <option value="<?= (int)$dv['id_divisi'] ?>" <?= $filter_divisi === (int)$dv['id_divisi'] ? 'selected' : '' ?>>
                                    <?= e($dv['kode_divisi']) ?> - <?= e($dv['nama_divisi']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label for="filter_status">Status</label>
                        <select name="status" id="filter_status">
                            <option value="">Semua Status</option>
                            <?php foreach (['Hadir', 'Izin', 'Sakit', 'Alpha'] as $st): ?>
                                <option value="<?= $st ?>" <?= $filter_status === $st ? 'selected' : '' ?>><?= $st ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:flex; gap:0.5rem;">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="index.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>

                <!-- Tabel HTML Dinamis -->
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIM / ID</th>
                                <th>Nama Lengkap</th>
                                <th>Kelas / Divisi</th>
                                <th>Tanggal</th>
                                <th>Jam Masuk</th>
                                <th>Jam Keluar</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                                <th>Aksi (Update / Delete)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($absensi_rows)): ?>
                                <tr>
                                    <td colspan="10" style="text-align:center; padding: 2rem; color: var(--slate-500);">
                                        Belum ada data absensi yang sesuai dengan filter pencarian.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($absensi_rows as $idx => $row): ?>
                                    <tr>
                                        <td><?= $idx + 1 ?></td>
                                        <td><strong><?= e($row['nomor_induk']) ?></strong></td>
                                        <td><?= e($row['nama_lengkap']) ?></td>
                                        <td>
                                            <span style="font-weight:600; color:var(--primary);"><?= e($row['kode_divisi']) ?></span>
                                            <small style="display:block; color:var(--slate-500);"><?= e($row['lokasi_ruang']) ?></small>
                                        </td>
                                        <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                                        <td><?= e(substr($row['jam_masuk'], 0, 5)) ?></td>
                                        <td><?= !empty($row['jam_keluar']) ? e(substr($row['jam_keluar'], 0, 5)) : '<span style="color:#94a3b8;">-</span>' ?></td>
                                        <td>
                                            <span class="badge-status status-<?= e($row['status_kehadiran']) ?>">
                                                <?= e($row['status_kehadiran']) ?>
                                            </span>
                                        </td>
                                        <td><?= !empty($row['keterangan']) ? e($row['keterangan']) : '-' ?></td>
                                        <td>
                                            <div class="action-group">
                                                <!-- Sub-CPMK 6: Edit Data berdasarkan ID spesifik -->
                                                <a href="edit.php?id=<?= (int)$row['id_absensi'] ?>" class="btn btn-warning btn-sm">
                                                    Edit
                                                </a>
                                                <!-- Sub-CPMK 7: Hapus Data dengan Confirmation Alert -->
                                                <a href="hapus.php?id=<?= (int)$row['id_absensi'] ?>"
                                                   class="btn btn-danger btn-sm"
                                                   onclick="return confirmDeleteAbsensi(<?= (int)$row['id_absensi'] ?>, '<?= e(addslashes($row['nama_lengkap'])) ?>', '<?= e($row['tanggal']) ?>');">
                                                    Hapus
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </section>

    </main>

    <footer>
        <div class="container">
            Aplikasi Absensi Web Berbasis PHP &amp; MySQL | Config &amp; Nama Database: <strong>244242015</strong> | Memenuhi Sub-CPMK 1 s/d Sub-CPMK 7
        </div>
    </footer>

    <script src="assets/script.js"></script>
</body>
</html>
