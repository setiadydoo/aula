<?php
session_start();
require_once 'config/database.php';

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Get filter from URL, default to 'pending'
$filter = $_GET['filter'] ?? 'pending';

// Prepare the query based on filter
$query = "SELECT id, username, nama_lengkap, tgl_daftar, status, password, no_whatsapp, pekerjaan FROM users WHERE role != 'admin'";
if ($filter === 'approved') {
    $query .= " AND status = 'active'";
} elseif ($filter === 'pending') {
    $query .= " AND status = 'pending'";
} elseif ($filter === 'inactive') {
    $query .= " AND status = 'inactive'";
}
$query .= " ORDER BY tgl_daftar DESC";

try {
    $stmt = $db->query($query);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Users - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .password-cell {
            position: relative;
        }
        .password-toggle {
            cursor: pointer;
            color: #0d6efd;
        }
        .password-toggle:hover {
            color: #0a58ca;
        }
        .password-field {
            font-family: monospace;
            background: #f8f9fa;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            margin-right: 0.5rem;
        }
        .whatsapp-link {
            color: #25D366;
            text-decoration: none;
        }
        .whatsapp-link:hover {
            color: #128C7E;
        }
        .whatsapp-number {
            font-family: monospace;
            color: #666;
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
                        <a class="nav-link active" href="manage_users.php">Kelola Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_bookings.php">Kelola Ruangan</a>
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
                        case 'user_approved':
                            echo 'User berhasil disetujui';
                            break;
                        case 'user_rejected':
                            echo 'User berhasil ditolak';
                            break;
                        case 'job_updated':
                            echo 'Pekerjaan user berhasil diperbarui';
                            break;
                        case 'user_updated':
                            echo 'Data user berhasil diperbarui';
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

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Users</h5>
                <div class="btn-group">
                    <a href="?filter=pending" class="btn btn-<?= $filter === 'pending' ? 'primary' : 'outline-primary' ?>">
                        Pending
                    </a>
                    <a href="?filter=approved" class="btn btn-<?= $filter === 'approved' ? 'primary' : 'outline-primary' ?>">
                        Disetujui
                    </a>
                    <a href="?filter=inactive" class="btn btn-<?= $filter === 'inactive' ? 'primary' : 'outline-primary' ?>">
                        Nonaktif
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($users)): ?>
                    <p class="text-muted">Tidak ada user yang <?= $filter === 'pending' ? 'menunggu persetujuan' : ($filter === 'approved' ? 'disetujui' : 'dinonaktifkan') ?></p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Nama Lengkap</th>
                                    <th>Password</th>
                                    <th>WhatsApp</th>
                                    <th>Pekerjaan</th>
                                    <th>Tanggal Daftar</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['nama_lengkap']) ?></td>
                                    <td class="password-cell">
                                        <span class="password-field" style="display: none;">
                                            <?= htmlspecialchars($user['password']) ?>
                                        </span>
                                        <i class="bi bi-eye password-toggle" 
                                           onclick="togglePassword(this)" 
                                           title="Tampilkan/Sembunyikan Password"></i>
                                    </td>
                                    <td>
                                        <?php if (!empty($user['no_whatsapp'])): ?>
                                            <span class="whatsapp-number"><?= htmlspecialchars($user['no_whatsapp']) ?></span>
                                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $user['no_whatsapp']) ?>" 
                                               class="whatsapp-link" 
                                               target="_blank" 
                                               title="Hubungi via WhatsApp">
                                                <i class="bi bi-whatsapp"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($user['pekerjaan'])): ?>
                                            <?= htmlspecialchars($user['pekerjaan']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($user['tgl_daftar'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $user['status'] === 'active' ? 'success' : 
                                            ($user['status'] === 'inactive' ? 'danger' : 'warning') 
                                        ?>">
                                            <?= ucfirst($user['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit_user.php?id=<?= $user['id'] ?>" 
                                           class="btn btn-primary btn-sm mb-1">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <?php if ($user['status'] === 'pending'): ?>
                                            <a href="approve_user.php?id=<?= $user['id'] ?>" 
                                               class="btn btn-success btn-sm mb-1"
                                               onclick="return confirm('Setujui user ini?')">
                                                <i class="bi bi-check-circle"></i> Setujui
                                            </a>
                                            <a href="reject_user.php?id=<?= $user['id'] ?>" 
                                               class="btn btn-danger btn-sm mb-1"
                                               onclick="return confirm('Tolak user ini?')">
                                                <i class="bi bi-x-circle"></i> Tolak
                                            </a>
                                        <?php elseif ($user['status'] === 'active'): ?>
                                            <a href="deactivate_user.php?id=<?= $user['id'] ?>" 
                                               class="btn btn-warning btn-sm mb-1"
                                               onclick="return confirm('Nonaktifkan user ini?')">
                                                <i class="bi bi-pause-circle"></i> Nonaktifkan
                                            </a>
                                        <?php else: ?>
                                            <a href="approve_user.php?id=<?= $user['id'] ?>" 
                                               class="btn btn-success btn-sm mb-1"
                                               onclick="return confirm('Aktifkan kembali user ini?')">
                                                <i class="bi bi-play-circle"></i> Aktifkan
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
    <script>
        function togglePassword(element) {
            const passwordField = element.previousElementSibling;
            if (passwordField.style.display === 'none') {
                passwordField.style.display = 'inline';
                element.classList.remove('bi-eye');
                element.classList.add('bi-eye-slash');
            } else {
                passwordField.style.display = 'none';
                element.classList.remove('bi-eye-slash');
                element.classList.add('bi-eye');
            }
        }
    </script>
</body>
</html> 