<?php
session_start();
require_once 'config/database.php';

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Ambil data ruangan yang akan diedit
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: manage_bookings.php');
    exit;
}

try {
    // Ambil detail ruangan
    $stmt = $db->prepare("SELECT * FROM ruangan WHERE id = ?");
    $stmt->execute([$id]);
    $ruangan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ruangan) {
        header('Location: manage_bookings.php');
        exit;
    }

    // Proses update data
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nama_ruangan = $_POST['nama_ruangan'];
        $kapasitas = $_POST['kapasitas'];
        $fasilitas = $_POST['fasilitas'];
        $status = $_POST['status'];

        $stmt = $db->prepare("
            UPDATE ruangan 
            SET nama_ruangan = ?, kapasitas = ?, fasilitas = ?, status = ?
            WHERE id = ?
        ");
        
        $stmt->execute([$nama_ruangan, $kapasitas, $fasilitas, $status, $id]);
        header('Location: manage_bookings.php?msg=updated');
        exit;
    }
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Ruangan - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Edit Ruangan</h5>
                        <a href="manage_bookings.php" class="btn btn-secondary btn-sm">Kembali</a>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Nama Ruangan</label>
                                <input type="text" name="nama_ruangan" class="form-control" 
                                       value="<?= htmlspecialchars($ruangan['nama_ruangan']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Kapasitas</label>
                                <input type="number" name="kapasitas" class="form-control" 
                                       value="<?= htmlspecialchars($ruangan['kapasitas']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Fasilitas</label>
                                <textarea name="fasilitas" class="form-control" rows="3" required><?= htmlspecialchars($ruangan['fasilitas']) ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="tersedia" <?= $ruangan['status'] === 'tersedia' ? 'selected' : '' ?>>
                                        Tersedia
                                    </option>
                                    <option value="tidak tersedia" <?= $ruangan['status'] === 'tidak tersedia' ? 'selected' : '' ?>>
                                        Tidak Tersedia
                                    </option>
                                </select>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="manage_bookings.php" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
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