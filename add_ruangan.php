<?php
session_start();
require_once 'config/database.php';

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_ruangan = $_POST['nama_ruangan'];
    $kapasitas = $_POST['kapasitas'];
    $fasilitas = $_POST['fasilitas'];
    $status = $_POST['status'];

    try {
        $stmt = $db->prepare("
            INSERT INTO ruangan (nama_ruangan, kapasitas, fasilitas, status) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$nama_ruangan, $kapasitas, $fasilitas, $status]);
        header('Location: manage_bookings.php?msg=added');
        exit;
    } catch(PDOException $e) {
        $error = "Gagal menambahkan ruangan: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Ruangan - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Tambah Ruangan Baru</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Nama Ruangan</label>
                                <input type="text" name="nama_ruangan" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Kapasitas</label>
                                <input type="number" name="kapasitas" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Fasilitas</label>
                                <textarea name="fasilitas" class="form-control" rows="3" required></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="tersedia">Tersedia</option>
                                    <option value="tidak tersedia">Tidak Tersedia</option>
                                </select>
                            </div>

                            <div class="d-flex justify-content-end">
                                <a href="manage_bookings.php" class="btn btn-secondary me-2">Batal</a>
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 