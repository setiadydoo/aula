<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_ruangan = trim($_POST['nama_ruangan']);
    $kapasitas = (int)$_POST['kapasitas'];
    $fasilitas = trim($_POST['fasilitas']);
    
    if (empty($nama_ruangan) || empty($kapasitas) || empty($fasilitas)) {
        $error = "Semua field harus diisi!";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO ruangan (nama_ruangan, kapasitas, fasilitas) VALUES (?, ?, ?)");
            if ($stmt->execute([$nama_ruangan, $kapasitas, $fasilitas])) {
                header('Location: admin_dashboard.php?success=Ruangan berhasil ditambahkan');
                exit();
            } else {
                $error = "Gagal menambahkan ruangan";
            }
        } catch(PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Ruangan - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Tambah Ruangan Baru</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="nama_ruangan" class="form-label">Nama Ruangan</label>
                        <input type="text" class="form-control" id="nama_ruangan" name="nama_ruangan" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="kapasitas" class="form-label">Kapasitas (orang)</label>
                        <input type="number" class="form-control" id="kapasitas" name="kapasitas" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="fasilitas" class="form-label">Fasilitas</label>
                        <textarea class="form-control" id="fasilitas" name="fasilitas" rows="3" required
                                  placeholder="Contoh: AC, Sound System, Proyektor, dll"></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="admin_dashboard.php" class="btn btn-secondary">Kembali</a>
                        <button type="submit" class="btn btn-primary">Simpan Ruangan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 