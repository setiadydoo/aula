<?php
session_start();
require_once 'config/database.php';

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Ambil data statistik
try {
    // Total Users
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role != 'admin'");
    $totalUsers = $stmt->fetchColumn();

    // Users Pending
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE status = 'pending'");
    $pendingUsers = $stmt->fetchColumn();

    // Total Peminjaman
    $stmt = $db->query("SELECT COUNT(*) FROM peminjaman");
    $totalBookings = $stmt->fetchColumn();

    // Peminjaman Pending
    $stmt = $db->query("SELECT COUNT(*) FROM peminjaman WHERE status_peminjaman = 'menunggu'");
    $pendingBookings = $stmt->fetchColumn();

    // Daftar User Pending
    $stmt = $db->query("
        SELECT id, username, nama_lengkap, tgl_daftar
        FROM users 
        WHERE status = 'pending'
        ORDER BY tgl_daftar DESC
        LIMIT 5
    ");
    $pendingUsersList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Daftar Peminjaman Terbaru
    $stmt = $db->query("
        SELECT 
            p.*,
            u.username,
            r.nama_ruangan,
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
        WHERE p.tanggal_mulai >= CURDATE()
        ORDER BY p.tanggal_mulai ASC, p.tanggal_selesai ASC
        LIMIT 5
    ");
    $recentBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sistem Peminjaman Ruangan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
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
                        <a class="nav-link active" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">Kelola Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_bookings.php">Kelola Peminjaman</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="logout.php" class="btn btn-light">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <!-- Statistik -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Users</h5>
                        <h2 class="mb-0"><?= $totalUsers ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Users Pending</h5>
                        <h2 class="mb-0"><?= $pendingUsers ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Peminjaman</h5>
                        <h2 class="mb-0"><?= $totalBookings ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">
                            Peminjaman Pending
                            <?php if ($pendingBookings > 0): ?>
                                <span class="badge bg-warning"><?= $pendingBookings ?></span>
                            <?php endif; ?>
                        </h5>
                        <h2 class="mb-0"><?= $pendingBookings ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Pending -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">User Menunggu Persetujuan</h5>
                <?php if (!empty($pendingUsersList)): ?>
                <a href="manage_users.php?filter=pending" class="btn btn-light btn-sm">
                    <i class="bi bi-list"></i> Lihat Semua
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($pendingUsersList)): ?>
                    <p class="text-muted text-center py-3">
                        <i class="bi bi-check-circle text-success"></i> 
                        Tidak ada user yang menunggu persetujuan
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Nama Lengkap</th>
                                    <th>Tanggal Daftar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingUsersList as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['nama_lengkap']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($user['tgl_daftar'])) ?></td>
                                    <td>
                                        <a href="approve_user.php?id=<?= $user['id'] ?>" 
                                           class="btn btn-success btn-sm"
                                           onclick="return confirm('Setujui user ini?')">
                                            <i class="bi bi-check-circle"></i> Setujui
                                        </a>
                                        <a href="reject_user.php?id=<?= $user['id'] ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Tolak user ini?')">
                                            <i class="bi bi-x-circle"></i> Tolak
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Peminjaman Pending -->
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    Peminjaman Pending
                    <?php if ($pendingBookings > 0): ?>
                        <span class="badge bg-warning ms-2"><?= $pendingBookings ?></span>
                    <?php endif; ?>
                </h5>
                <?php if (!empty($recentBookings)): ?>
                <a href="manage_bookings.php" class="btn btn-light btn-sm">
                    <i class="bi bi-list"></i> Lihat Semua
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($recentBookings)): ?>
                    <p class="text-muted">Tidak ada peminjaman mendatang</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Ruangan</th>
                                    <th>Tanggal</th>
                                    <th>Waktu</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $current_date = null;
                                $shown_conflicts = array();
                                foreach ($recentBookings as $booking): 
                                    $booking_date = date('Y-m-d', strtotime($booking['tanggal_mulai']));
                                    if ($booking_date !== $current_date):
                                        $current_date = $booking_date;
                                ?>
                                <tr class="table-light">
                                    <td colspan="6" class="fw-bold">
                                        <i class="bi bi-calendar-event"></i>
                                        <?= date('l, d F Y', strtotime($booking['tanggal_mulai'])) ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr class="<?= $booking['conflict_count'] > 0 ? 'booking-conflict' : '' ?>">
                                    <td><?= htmlspecialchars($booking['username']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($booking['nama_ruangan']) ?>
                                        <?php if ($booking['conflict_count'] > 0): ?>
                                            <div class="conflict-warning">
                                                <i class="bi bi-exclamation-triangle-fill"></i>
                                                Bentrok dengan <?= $booking['conflict_count'] ?> peminjaman lain
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($booking['tanggal_mulai'])) ?></td>
                                    <td>
                                        <?= date('H:i', strtotime($booking['tanggal_mulai'])) ?> - 
                                        <?= date('H:i', strtotime($booking['tanggal_selesai'])) ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $booking['status_peminjaman'] === 'disetujui' ? 'success' : 
                                            ($booking['status_peminjaman'] === 'ditolak' ? 'danger' : 'warning') 
                                        ?>">
                                            <?= ucfirst($booking['status_peminjaman']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($booking['status_peminjaman'] === 'menunggu'): ?>
                                        <a href="approve_booking.php?id=<?= $booking['id'] ?>" 
                                           class="btn btn-success btn-sm"
                                           onclick="return confirm('Setujui peminjaman ini?')">
                                            <i class="bi bi-check-circle"></i>
                                        </a>
                                        <a href="reject_booking.php?id=<?= $booking['id'] ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Tolak peminjaman ini?')">
                                            <i class="bi bi-x-circle"></i>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 