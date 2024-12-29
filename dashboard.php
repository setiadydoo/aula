<?php
require_once 'config/database.php';
require_once 'classes/RoomBooking.php';

session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Ambil data user
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Ambil daftar ruangan yang tersedia
$stmt = $db->query("
    SELECT r.*, 
           (
               SELECT COUNT(*) 
               FROM peminjaman p 
               WHERE p.ruangan_id = r.id 
               AND p.status_peminjaman = 'disetujui'
               AND p.tanggal_mulai <= NOW() 
               AND p.tanggal_selesai >= NOW()
           ) as is_used,
           (
               SELECT GROUP_CONCAT(
                   CONCAT(
                       DATE_FORMAT(p2.tanggal_mulai, '%H:%i'), 
                       ' - ', 
                       DATE_FORMAT(p2.tanggal_selesai, '%H:%i')
                   ) SEPARATOR ', '
               )
               FROM peminjaman p2 
               WHERE p2.ruangan_id = r.id 
               AND p2.status_peminjaman != 'ditolak'
               AND DATE(p2.tanggal_mulai) = CURDATE()
           ) as today_bookings
    FROM ruangan r
    ORDER BY r.nama_ruangan
");
$ruangan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil peminjaman user yang sedang login
$stmt = $db->prepare("
    SELECT p.*, r.nama_ruangan,
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
    JOIN ruangan r ON p.ruangan_id = r.id
    WHERE p.user_id = ?
    ORDER BY p.tanggal_mulai DESC
");
$stmt->execute([$_SESSION['user_id']]);
$peminjaman = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Peminjaman Ruangan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .room-card {
            height: 100%;
            transition: transform 0.2s;
        }
        .room-card:hover {
            transform: translateY(-5px);
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
        .today-bookings {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Sistem Peminjaman Ruangan</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <?php if ($user['status'] === 'active'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="edit_profile.php">
                            <i class="bi bi-person-gear"></i> Edit Profil
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-white me-3">Welcome, <?= htmlspecialchars($user['username']) ?></span>
                    <a href="logout.php" class="btn btn-light">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <!-- Daftar Ruangan -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Daftar Ruangan</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($ruangan as $room): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card room-card">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($room['nama_ruangan']) ?></h5>
                                <div class="room-details mb-3">
                                    <div class="mb-2">
                                        <i class="bi bi-people-fill text-primary"></i>
                                        <span>Kapasitas: <?= htmlspecialchars($room['kapasitas']) ?> orang</span>
                                    </div>
                                    <div class="mb-2">
                                        <i class="bi bi-gear-fill text-primary"></i>
                                        <span>Fasilitas:</span>
                                        <p class="text-muted small mb-0"><?= htmlspecialchars($room['fasilitas']) ?></p>
                                    </div>
                                    <?php if (!empty($room['today_bookings'])): ?>
                                        <div class="today-bookings">
                                            <i class="bi bi-clock-history"></i>
                                            Jadwal hari ini: <?= htmlspecialchars($room['today_bookings']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="d-grid">
                                    <a href="form_peminjaman.php?ruangan_id=<?= $room['id'] ?>" 
                                       class="btn btn-primary">
                                        <i class="bi bi-calendar-plus"></i> Ajukan Peminjaman
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Riwayat Peminjaman -->
        <div class="card mt-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Riwayat Peminjaman Anda</h5>
            </div>
            <div class="card-body">
                <?php if (empty($peminjaman)): ?>
                    <p class="text-muted">Belum ada riwayat peminjaman</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Ruangan</th>
                                    <th>Tanggal</th>
                                    <th>Waktu</th>
                                    <th>Keperluan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($peminjaman as $booking): ?>
                                <tr>
                                    <td><?= htmlspecialchars($booking['nama_ruangan']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($booking['tanggal_mulai'])) ?></td>
                                    <td>
                                        <?= date('H:i', strtotime($booking['tanggal_mulai'])) ?> - 
                                        <?= date('H:i', strtotime($booking['tanggal_selesai'])) ?>
                                    </td>
                                    <td><?= htmlspecialchars($booking['keperluan']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $booking['status_peminjaman'] === 'disetujui' ? 'success' : 
                                            ($booking['status_peminjaman'] === 'ditolak' ? 'danger' : 'warning') 
                                        ?>">
                                            <?= ucfirst($booking['status_peminjaman']) ?>
                                        </span>
                                        <?php if ($booking['status_peminjaman'] !== 'ditolak'): ?>
                                            <div class="mt-2">
                                                <?php if ($booking['status_peminjaman'] === 'menunggu'): ?>
                                                    <a href="edit_booking.php?id=<?= $booking['id'] ?>" 
                                                       class="btn btn-primary btn-sm">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </a>
                                                <?php endif; ?>
                                                <a href="cancel_booking.php?id=<?= $booking['id'] ?>" 
                                                   class="btn btn-danger btn-sm"
                                                   onclick="return confirm('Yakin ingin membatalkan peminjaman ini?')">
                                                    <i class="bi bi-x-circle"></i> Batalkan
                                                </a>
                                                <?php if ($booking['status_peminjaman'] === 'disetujui'): ?>
                                                    <a href="print_booking_user.php?id=<?= $booking['id'] ?>" 
                                                       class="btn btn-info btn-sm"
                                                       target="_blank">
                                                        <i class="bi bi-printer"></i> Cetak Bukti
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($booking['status_peminjaman'] === 'menunggu'): ?>
                                            <a href="edit_booking.php?id=<?= $booking['id'] ?>" 
                                               class="btn btn-primary btn-sm mb-1">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <a href="delete_booking.php?id=<?= $booking['id'] ?>" 
                                               class="btn btn-danger btn-sm mb-1"
                                               onclick="return confirm('Yakin ingin menghapus peminjaman ini?')">
                                                <i class="bi bi-trash"></i> Hapus
                                            </a>
                                        <?php endif; ?>
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

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
                switch($_GET['msg']) {
                    case 'booking_success':
                        echo 'Peminjaman berhasil diajukan';
                        break;
                    case 'booking_updated':
                        echo 'Peminjaman berhasil diperbarui';
                        break;
                    case 'booking_deleted':
                        echo 'Peminjaman berhasil dihapus';
                        break;
                    case 'booking_cancelled':
                        echo 'Peminjaman berhasil dibatalkan';
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 