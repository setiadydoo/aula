<?php
session_start();
require_once 'config/database.php';

// Cek login dan status active
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Ambil data user
$stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND status = 'active'");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Jika user tidak ditemukan atau tidak active, redirect ke dashboard
if (!$user) {
    header('Location: dashboard.php');
    exit;
}

$success = '';
$error = '';

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $no_whatsapp = trim($_POST['no_whatsapp']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        // Start transaction
        $db->beginTransaction();

        // Jika user ingin mengubah password
        if (!empty($new_password)) {
            // Validasi password lama
            if (empty($current_password)) {
                throw new Exception("Password saat ini harus diisi untuk mengubah password");
            }
            if ($current_password !== $user['password']) {
                throw new Exception("Password saat ini tidak sesuai");
            }
            if (strlen($new_password) < 6) {
                throw new Exception("Password baru minimal 6 karakter");
            }
            if ($new_password !== $confirm_password) {
                throw new Exception("Konfirmasi password baru tidak sesuai");
            }

            // Update data dengan password baru
            $stmt = $db->prepare("
                UPDATE users 
                SET nama_lengkap = ?, no_whatsapp = ?, password = ?
                WHERE id = ?
            ");
            $stmt->execute([$nama_lengkap, $no_whatsapp, $new_password, $user['id']]);
        } else {
            // Update data tanpa mengubah password
            $stmt = $db->prepare("
                UPDATE users 
                SET nama_lengkap = ?, no_whatsapp = ?
                WHERE id = ?
            ");
            $stmt->execute([$nama_lengkap, $no_whatsapp, $user['id']]);
        }

        $db->commit();
        $success = "Profil berhasil diperbarui!";
        
        // Refresh user data
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - Sistem Peminjaman Ruangan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Sistem Peminjaman Ruangan</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="edit_profile.php">
                            <i class="bi bi-person-gear"></i> Edit Profil
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-white me-3">Welcome, <?= htmlspecialchars($user['username']) ?></span>
                    <a href="logout.php" class="btn btn-light">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="form-container">
            <div class="card">
                <div class="card-header g-primary text-Blue d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Edit Profil</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $success ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $error ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                            <div class="form-text">Username tidak dapat diubah</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" name="nama_lengkap" 
                                   value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nomor WhatsApp</label>
                            <div class="input-group">
                                <span class="input-group-text">+62</span>
                                <input type="tel" class="form-control" name="no_whatsapp" 
                                       value="<?= substr($user['no_whatsapp'], 3) ?>"
                                       placeholder="8xxxxxxxxxx" pattern="8[0-9]{8,11}"
                                       title="Masukkan nomor WhatsApp yang valid (contoh: 81234567890)" required>
                            </div>
                            <div class="form-text">Masukkan nomor tanpa angka 0 di depan. Contoh: 81234567890</div>
                        </div>

                        <hr class="my-4">

                        <div class="mb-3">
                            <label class="form-label">Password Saat Ini</label>
                            <input type="password" class="form-control" name="current_password">
                            <div class="form-text">Kosongkan jika tidak ingin mengubah password</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" class="form-control" name="new_password" minlength="6">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" name="confirm_password" minlength="6">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Simpan Perubahan
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 