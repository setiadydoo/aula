<?php
session_start();
require_once 'config/database.php';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $no_whatsapp = trim($_POST['no_whatsapp']);
    $pekerjaan = trim($_POST['pekerjaan']);
    $password = $_POST['password'];

    // Validasi input
    if (empty($username) || empty($nama_lengkap) || empty($no_whatsapp) || empty($pekerjaan) || empty($password)) {
        $error = 'Semua field harus diisi';
    } else {
        try {
            // Cek apakah username sudah ada
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->fetch()) {
                $error = 'Username sudah digunakan';
            } else {
                // Insert user baru
                $stmt = $db->prepare("
                    INSERT INTO users (username, nama_lengkap, password, role, status, no_whatsapp, pekerjaan) 
                    VALUES (?, ?, ?, 'user', 'pending', ?, ?)
                ");
                
                $stmt->execute([
                    $username,
                    $nama_lengkap,
                    password_hash($password, PASSWORD_DEFAULT),
                    $no_whatsapp,
                    $pekerjaan
                ]);

                // Redirect ke halaman sukses
                header('Location: register_success.php');
                exit;
            }
        } catch(PDOException $e) {
            $error = 'Terjadi kesalahan. Silakan coba lagi.';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Sistem Peminjaman Ruangan Aula Disdikpora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .register-container {
            max-width: 500px;
            margin: 50px auto;
        }
        .card {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #ddd;
            padding: 15px;
        }
        .btn-primary {
            width: 100%;
        }
        .back-to-home {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="register-container">
        <div class="card">
            <div class="card-header text-center">
                <h4 class="mb-0">Register</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                               value="<?php echo isset($_POST['nama_lengkap']) ? htmlspecialchars($_POST['nama_lengkap']) : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="no_whatsapp" class="form-label">Nomor WhatsApp</label>
                        <input type="text" class="form-control" id="no_whatsapp" name="no_whatsapp" 
                               value="<?= htmlspecialchars($_POST['no_whatsapp'] ?? '') ?>" required>
                        <div class="form-text">Contoh: +6281234567890</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="pekerjaan" class="form-label">Pekerjaan</label>
                        <select class="form-select" id="pekerjaan" name="pekerjaan" required>
                            <option value="">Pilih Pekerjaan</option>
                            <option value="ASN" <?= isset($_POST['pekerjaan']) && $_POST['pekerjaan'] === 'ASN' ? 'selected' : '' ?>>ASN</option>
                            <option value="Umum" <?= isset($_POST['pekerjaan']) && $_POST['pekerjaan'] === 'Umum' ? 'selected' : '' ?>>Umum</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                        <input type="password" class="form-control" id="confirm_password" 
                               name="confirm_password" required minlength="6">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Register</button>
                </form>
            </div>
        </div>
        <div class="back-to-home">
            <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
            <a href="index.php" class="text-decoration-none">← Kembali ke Beranda</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 