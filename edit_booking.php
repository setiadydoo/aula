<?php
require_once 'config/database.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if booking ID is provided
if (!isset($_GET['id'])) {
    header('Location: dashboard.php?error=ID peminjaman tidak ditemukan');
    exit;
}

$booking_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    // Get booking details
    $stmt = $db->prepare("
        SELECT p.*, r.nama_ruangan, r.kapasitas, r.fasilitas
        FROM peminjaman p
        JOIN ruangan r ON p.ruangan_id = r.id
        WHERE p.id = ? AND p.user_id = ? AND p.status_peminjaman = 'menunggu'
    ");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        header('Location: dashboard.php?error=Peminjaman tidak ditemukan atau tidak dapat diedit');
        exit;
    }

    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $tanggal = $_POST['tanggal'];
        $jam_mulai = $_POST['jam_mulai'];
        $jam_selesai = $_POST['jam_selesai'];
        $keperluan = $_POST['keperluan'];

        $tanggal_mulai = $tanggal . ' ' . $jam_mulai;
        $tanggal_selesai = $tanggal . ' ' . $jam_selesai;

        // Validate date and time
        if (strtotime($tanggal_mulai) < time()) {
            throw new Exception("Tanggal dan waktu peminjaman harus lebih dari waktu sekarang");
        }

        if (strtotime($jam_mulai) >= strtotime($jam_selesai)) {
            throw new Exception("Waktu selesai harus lebih dari waktu mulai");
        }

        // Check for conflicts
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM peminjaman 
            WHERE ruangan_id = ? 
            AND id != ?
            AND status_peminjaman != 'ditolak'
            AND (
                (tanggal_mulai BETWEEN ? AND ?) OR
                (tanggal_selesai BETWEEN ? AND ?) OR
                (tanggal_mulai <= ? AND tanggal_selesai >= ?)
            )
        ");
        $stmt->execute([
            $booking['ruangan_id'],
            $booking_id,
            $tanggal_mulai,
            $tanggal_selesai,
            $tanggal_mulai,
            $tanggal_selesai,
            $tanggal_mulai,
            $tanggal_selesai
        ]);

        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Ruangan sudah dipesan untuk waktu tersebut");
        }

        // Update booking
        $stmt = $db->prepare("
            UPDATE peminjaman 
            SET tanggal_mulai = ?, tanggal_selesai = ?, keperluan = ?
            WHERE id = ? AND user_id = ? AND status_peminjaman = 'menunggu'
        ");
        $stmt->execute([
            $tanggal_mulai,
            $tanggal_selesai,
            $keperluan,
            $booking_id,
            $user_id
        ]);

        header('Location: dashboard.php?msg=booking_updated');
        exit;
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Peminjaman Ruangan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Edit Peminjaman Ruangan</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Ruangan</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($booking['nama_ruangan']) ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tanggal</label>
                                <input type="date" name="tanggal" class="form-control" 
                                       value="<?= date('Y-m-d', strtotime($booking['tanggal_mulai'])) ?>"
                                       min="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Jam Mulai</label>
                                        <input type="time" name="jam_mulai" class="form-control"
                                               value="<?= date('H:i', strtotime($booking['tanggal_mulai'])) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Jam Selesai</label>
                                        <input type="time" name="jam_selesai" class="form-control"
                                               value="<?= date('H:i', strtotime($booking['tanggal_selesai'])) ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Keperluan</label>
                                <textarea name="keperluan" class="form-control" rows="3" required><?= htmlspecialchars($booking['keperluan']) ?></textarea>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="dashboard.php" class="btn btn-secondary">Batal</a>
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