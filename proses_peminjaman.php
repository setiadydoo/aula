<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

if (isset($_GET['id']) && isset($_POST['status'])) {
    $peminjaman_id = $_GET['id'];
    $status = $_POST['status'];
    
    try {
        $pdo->beginTransaction();
        
        // Update status peminjaman
        $stmt = $pdo->prepare("UPDATE peminjaman SET status = ? WHERE id = ?");
        $stmt->execute([$status, $peminjaman_id]);
        
        // Update status ruangan jika peminjaman disetujui
        if ($status == 'disetujui') {
            $stmt = $pdo->prepare("UPDATE ruangan SET status = 'dipinjam' 
                                  WHERE id = (SELECT ruangan_id FROM peminjaman WHERE id = ?)");
            $stmt->execute([$peminjaman_id]);
        }
        
        $pdo->commit();
        header('Location: admin_dashboard.php?success=Status peminjaman berhasil diupdate');
    } catch(PDOException $e) {
        $pdo->rollBack();
        header('Location: admin_dashboard.php?error=' . urlencode($e->getMessage()));
    }
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Proses Peminjaman - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Proses Peminjaman</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Status Peminjaman</label>
                        <select name="status" class="form-control" required>
                            <option value="disetujui">Setujui</option>
                            <option value="ditolak">Tolak</option>
                        </select>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="admin_dashboard.php" class="btn btn-secondary">Kembali</a>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 