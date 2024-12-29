<?php
session_start();
require_once 'config/database.php';

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Ambil daftar ruangan
try {
    $stmt = $db->query("SELECT * FROM ruangan ORDER BY nama_ruangan");
    $ruangan = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ambil peminjaman yang pending
    $stmt = $db->query("
        SELECT p.*, u.username, u.nama_lengkap, u.no_whatsapp, r.nama_ruangan,
               (
                   SELECT COUNT(*) 
                   FROM peminjaman p2 
                   WHERE p2.ruangan_id = p.ruangan_id 
                   AND p2.id != p.id
                   AND p2.tanggal_mulai = p.tanggal_mulai
                   AND p2.tanggal_selesai = p.tanggal_selesai
                   AND p2.status_peminjaman != 'ditolak'
               ) as conflict_count
        FROM peminjaman p
        JOIN users u ON p.user_id = u.id
        JOIN ruangan r ON p.ruangan_id = r.id
        WHERE p.status_peminjaman = 'menunggu'
        ORDER BY p.tanggal_mulai ASC, p.tanggal_selesai ASC
    ");
    $pending_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ambil semua peminjaman dengan detail
    $stmt = $db->query("
        SELECT p.*, u.username, u.nama_lengkap, u.no_whatsapp, r.nama_ruangan, r.kapasitas, r.fasilitas,
               p.keperluan as tujuan,
               (
                   SELECT COUNT(*) 
                   FROM peminjaman p2 
                   WHERE p2.ruangan_id = p.ruangan_id 
                   AND p2.id != p.id
                   AND p2.tanggal_mulai = p.tanggal_mulai
                   AND p2.tanggal_selesai = p.tanggal_selesai
                   AND p2.status_peminjaman != 'ditolak'
               ) as conflict_count
        FROM peminjaman p
        JOIN users u ON p.user_id = u.id
        JOIN ruangan r ON p.ruangan_id = r.id
        ORDER BY p.tanggal_mulai ASC, p.tanggal_selesai ASC
    ");
    $all_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Proses hapus ruangan
if (isset($_POST['delete_ruangan'])) {
    $id = $_POST['ruangan_id'];
    try {
        $stmt = $db->prepare("DELETE FROM ruangan WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: manage_bookings.php?msg=deleted');
        exit;
    } catch(PDOException $e) {
        $error = "Gagal menghapus ruangan: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Ruangan - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .status-form select {
            min-width: 120px;
        }
        .status-form select.form-select-sm {
            padding-top: 0.25rem;
            padding-bottom: 0.25rem;
        }
        .status-form select[value="disetujui"] {
            color: #198754;
        }
        .status-form select[value="ditolak"] {
            color: #dc3545;
        }
        .status-form select[value="menunggu"] {
            color: #ffc107;
        }
        .booking-conflict {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .conflict-warning {
            color: #856404;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 4px;
            padding: 0.25rem 0.5rem;
            margin-top: 0.25rem;
            font-size: 0.875rem;
            display: inline-block;
        }
        .whatsapp-link {
            color: #25D366;
            text-decoration: none;
            margin-left: 0.5rem;
        }
        .whatsapp-link:hover {
            color: #128C7E;
        }
        .whatsapp-number {
            font-family: monospace;
            color: #666;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">Admin Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">Kelola Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_bookings.php">Kelola Ruangan</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="logout.php" class="btn btn-light">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                    switch($_GET['msg']) {
                        case 'added':
                            echo 'Ruangan berhasil ditambahkan';
                            break;
                        case 'updated':
                            echo 'Ruangan berhasil diperbarui';
                            break;
                        case 'deleted':
                            echo 'Data berhasil dihapus';
                            break;
                        case 'approved':
                            echo 'Peminjaman berhasil disetujui';
                            break;
                        case 'rejected':
                            echo 'Peminjaman berhasil ditolak';
                            break;
                        case 'date_updated':
                            echo 'Waktu peminjaman berhasil diperbarui';
                            break;
                    }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_GET['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Daftar Ruangan -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Ruangan</h5>
                <a href="add_ruangan.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle"></i> Tambah Ruangan
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nama Ruangan</th>
                                <th>Kapasitas</th>
                                <th>Fasilitas</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ruangan as $room): ?>
                            <tr>
                                <td><?= htmlspecialchars($room['nama_ruangan']) ?></td>
                                <td><?= htmlspecialchars($room['kapasitas']) ?> orang</td>
                                <td><?= htmlspecialchars($room['fasilitas']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $room['status'] === 'tersedia' ? 'success' : 'danger' ?>">
                                        <?= ucfirst($room['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit_ruangan.php?id=<?= $room['id'] ?>" class="btn btn-warning btn-sm">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="" method="POST" class="d-inline" 
                                          onsubmit="return confirm('Yakin ingin menghapus ruangan ini?')">
                                        <input type="hidden" name="ruangan_id" value="<?= $room['id'] ?>">
                                        <button type="submit" name="delete_ruangan" class="btn btn-danger btn-sm">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Peminjaman yang Disetujui -->
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Peminjaman yang Disetujui</h5>
            </div>
            <div class="card-body">
                <?php
                try {
                    $stmt = $db->query("
                        SELECT p.*, u.username, u.nama_lengkap, r.nama_ruangan
                        FROM peminjaman p
                        JOIN users u ON p.user_id = u.id
                        JOIN ruangan r ON p.ruangan_id = r.id
                        WHERE p.status_peminjaman = 'disetujui'
                        AND p.tanggal_mulai >= CURDATE()
                        ORDER BY p.tanggal_mulai ASC, p.tanggal_selesai ASC
                    ");
                    $approved_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch(PDOException $e) {
                    echo "Error: " . $e->getMessage();
                }
                ?>

                <?php if (empty($approved_bookings)): ?>
                    <p class="text-muted">Tidak ada peminjaman yang disetujui</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Peminjam</th>
                                    <th>Ruangan</th>
                                    <th>Tanggal</th>
                                    <th>Waktu</th>
                                    <th>Keperluan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $current_date = null;
                                foreach ($approved_bookings as $booking): 
                                    $booking_date = date('Y-m-d', strtotime($booking['tanggal_mulai']));
                                    if ($booking_date !== $current_date):
                                        $current_date = $booking_date;
                                ?>
                                <tr class="table-light">
                                    <td colspan="5" class="fw-bold">
                                        <i class="bi bi-calendar-event"></i>
                                        <?= date('l, d F Y', strtotime($booking['tanggal_mulai'])) ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($booking['nama_lengkap']) ?></strong>
                                        <small class="text-muted d-block"><?= $booking['username'] ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($booking['nama_ruangan']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($booking['tanggal_mulai'])) ?></td>
                                    <td>
                                        <?= date('H:i', strtotime($booking['tanggal_mulai'])) ?> - 
                                        <?= date('H:i', strtotime($booking['tanggal_selesai'])) ?>
                                    </td>
                                    <td><?= htmlspecialchars($booking['keperluan']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Semua Peminjaman -->
        <div class="card mt-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Semua Peminjaman</h5>
                <div class="d-flex align-items-center gap-2">
                    <select id="reportPeriod" class="form-select form-select-sm" style="width: auto;">
                        <option value="">Pilih Periode Laporan</option>
                        <option value="week">1 Minggu Berjalan</option>
                        <option value="month">1 Bulan Berjalan</option>
                        <option value="year">1 Tahun Berjalan</option>
                    </select>
                    <button onclick="generateReport()" class="btn btn-light btn-sm">
                        <i class="bi bi-file-earmark-pdf"></i> Cetak Laporan
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($all_bookings)): ?>
                    <p class="text-muted">Belum ada peminjaman</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Peminjam</th>
                                    <th>Ruangan</th>
                                    <th>Detail Ruangan</th>
                                    <th>Waktu Peminjaman</th>
                                    <th>Tujuan Peminjaman</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $current_date = null;
                                foreach ($all_bookings as $booking): 
                                    $booking_date = date('Y-m-d', strtotime($booking['tanggal_mulai']));
                                    if ($booking_date !== $current_date):
                                        $current_date = $booking_date;
                                ?>
                                <tr class="table-light">
                                    <td colspan="7" class="fw-bold">
                                        <i class="bi bi-calendar-event"></i>
                                        <?= date('l, d F Y', strtotime($booking['tanggal_mulai'])) ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr class="<?= $booking['conflict_count'] > 0 ? 'booking-conflict' : '' ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($booking['nama_lengkap']) ?></strong>
                                        <small class="text-muted d-block"><?= $booking['username'] ?></small>
                                        <?php if (!empty($booking['no_whatsapp'])): ?>
                                            <div class="mt-1">
                                                <span class="whatsapp-number"><?= htmlspecialchars($booking['no_whatsapp']) ?></span>
                                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $booking['no_whatsapp']) ?>" 
                                                   class="whatsapp-link" 
                                                   target="_blank"
                                                   title="Hubungi via WhatsApp">
                                                    <i class="bi bi-whatsapp"></i>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($booking['nama_ruangan']) ?></strong>
                                        <?php if ($booking['conflict_count'] > 0): ?>
                                            <div class="conflict-warning">
                                                <i class="bi bi-exclamation-triangle-fill"></i>
                                                Bentrok dengan <?= $booking['conflict_count'] ?> peminjaman lain
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small>
                                            <strong>Kapasitas:</strong> <?= htmlspecialchars($booking['kapasitas']) ?> orang<br>
                                            <strong>Fasilitas:</strong> <?= htmlspecialchars($booking['fasilitas']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="mb-1">
                                            <i class="bi bi-calendar"></i> 
                                            <?= date('d/m/Y', strtotime($booking['tanggal_mulai'])) ?>
                                            <button type="button" 
                                                    class="btn btn-link btn-sm text-primary p-0 ms-2"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editDateModal<?= $booking['id'] ?>">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                        </div>
                                        <div>
                                            <i class="bi bi-clock"></i>
                                            <?= date('H:i', strtotime($booking['tanggal_mulai'])) ?> - 
                                            <?= date('H:i', strtotime($booking['tanggal_selesai'])) ?>
                                        </div>

                                        <!-- Modal Edit Tanggal -->
                                        <div class="modal fade" id="editDateModal<?= $booking['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Waktu Peminjaman</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="update_booking_date.php" method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label">Tanggal</label>
                                                                <input type="date" class="form-control" name="tanggal" 
                                                                       value="<?= date('Y-m-d', strtotime($booking['tanggal_mulai'])) ?>" 
                                                                       required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Waktu Mulai</label>
                                                                <input type="time" class="form-control" name="waktu_mulai" 
                                                                       value="<?= date('H:i', strtotime($booking['tanggal_mulai'])) ?>" 
                                                                       required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Waktu Selesai</label>
                                                                <input type="time" class="form-control" name="waktu_selesai" 
                                                                       value="<?= date('H:i', strtotime($booking['tanggal_selesai'])) ?>" 
                                                                       required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($booking['tujuan']) ?>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <form action="update_booking_status.php" method="POST" class="status-form mb-2">
                                                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                                <select name="status" class="form-select form-select-sm" 
                                                        onchange="if(confirm('Ubah status peminjaman?')) this.form.submit()">
                                                    <option value="menunggu" <?= $booking['status_peminjaman'] === 'menunggu' ? 'selected' : '' ?>>
                                                        Menunggu
                                                    </option>
                                                    <option value="disetujui" <?= $booking['status_peminjaman'] === 'disetujui' ? 'selected' : '' ?>>
                                                        Disetujui
                                                    </option>
                                                    <option value="ditolak" <?= $booking['status_peminjaman'] === 'ditolak' ? 'selected' : '' ?>>
                                                        Ditolak
                                                    </option>
                                                </select>
                                            </form>
                                            <div class="btn-group">
                                                <?php if ($booking['status_peminjaman'] === 'disetujui'): ?>
                                                    <a href="print_booking.php?id=<?= $booking['id'] ?>" 
                                                       class="btn btn-info btn-sm"
                                                       target="_blank"
                                                       title="Cetak bukti peminjaman">
                                                        <i class="bi bi-printer"></i> Cetak Bukti
                                                    </a>
                                                <?php endif; ?>
                                                <a href="delete_booking.php?id=<?= $booking['id'] ?>" 
                                                   class="btn btn-danger btn-sm"
                                                   onclick="return confirm('Yakin ingin menghapus peminjaman ini?')"
                                                   title="Hapus peminjaman">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function generateReport() {
            const period = document.getElementById('reportPeriod').value;
            if (!period) {
                alert('Silakan pilih periode laporan terlebih dahulu');
                return;
            }
            window.location.href = `generate_report.php?period=${period}`;
        }
    </script>
</body>
</html> 