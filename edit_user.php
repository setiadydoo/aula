<?php
session_start();
require_once 'config/database.php';

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Get user ID from URL
$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    header('Location: manage_users.php?error=ID user tidak ditemukan');
    exit;
}

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $no_whatsapp = trim($_POST['no_whatsapp']);
    $pekerjaan = trim($_POST['pekerjaan']);
    $new_password = trim($_POST['new_password']);

    try {
        // Start transaction
        $db->beginTransaction();

        // Update basic info
        $sql = "UPDATE users SET 
                username = ?, 
                nama_lengkap = ?, 
                no_whatsapp = ?, 
                pekerjaan = ?";
        $params = [$username, $nama_lengkap, $no_whatsapp, $pekerjaan];

        // If new password is provided, update it
        if (!empty($new_password)) {
            $sql .= ", password = ?";
            $params[] = password_hash($new_password, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = ?";
        $params[] = $user_id;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $db->commit();
        header('Location: manage_users.php?msg=user_updated');
        exit;
    } catch(PDOException $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}

// Get user data
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: manage_users.php?error=User tidak ditemukan');
        exit;
    }
} catch(PDOException $e) {
    header('Location: manage_users.php?error=' . urlencode($e->getMessage()));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Edit User</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?= htmlspecialchars($user['username']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                                       value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="no_whatsapp" class="form-label">Nomor WhatsApp</label>
                                <input type="text" class="form-control" id="no_whatsapp" name="no_whatsapp" 
                                       value="<?= htmlspecialchars($user['no_whatsapp']) ?>">
                            </div>

                            <div class="mb-3">
                                <label for="pekerjaan" class="form-label">Pekerjaan</label>
                                <select class="form-select" id="pekerjaan" name="pekerjaan" required>
                                    <option value="">Pilih Pekerjaan</option>
                                    <option value="ASN" <?= isset($user['pekerjaan']) && $user['pekerjaan'] === 'ASN' ? 'selected' : '' ?>>ASN</option>
                                    <option value="Umum" <?= isset($user['pekerjaan']) && $user['pekerjaan'] === 'Umum' ? 'selected' : '' ?>>Umum</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label">Password Baru (kosongkan jika tidak ingin mengubah)</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="manage_users.php" class="btn btn-secondary">Kembali</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 