<?php
session_start();
require_once 'config/database.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Cek status user
$stmt = $db->prepare("SELECT status FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_status = $stmt->fetchColumn();

if ($user_status !== 'active') {
    header('Location: dashboard.php?error=not_active');
    exit;
}

// Ambil ID ruangan dari parameter URL
$ruangan_id = $_GET['ruangan_id'] ?? null;
if (!$ruangan_id) {
    header('Location: dashboard.php');
    exit;
}

// Ambil detail ruangan
try {
    $stmt = $db->prepare("SELECT * FROM ruangan WHERE id = ?");
    $stmt->execute([$ruangan_id]);
    $ruangan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ruangan) {
        header('Location: dashboard.php');
        exit;
    }

    // Proses form peminjaman
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $tanggal_mulai = $_POST['tanggal'] . ' ' . $_POST['jam_mulai'];
        $tanggal_selesai = $_POST['tanggal'] . ' ' . $_POST['jam_selesai'];
        $keperluan = $_POST['keperluan'];

        // Validasi waktu
        if (strtotime($tanggal_mulai) >= strtotime($tanggal_selesai)) {
            $error = "Jam selesai harus lebih besar dari jam mulai";
        } else {
            // Cek ketersediaan ruangan
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM peminjaman 
                WHERE ruangan_id = ? 
                AND status_peminjaman = 'disetujui'
                AND (
                    (tanggal_mulai BETWEEN ? AND ?) 
                    OR (tanggal_selesai BETWEEN ? AND ?)
                    OR (tanggal_mulai <= ? AND tanggal_selesai >= ?)
                )
            ");
            $stmt->execute([
                $ruangan_id, 
                $tanggal_mulai, 
                $tanggal_selesai,
                $tanggal_mulai, 
                $tanggal_selesai,
                $tanggal_mulai,
                $tanggal_selesai
            ]);
            
            if ($stmt->fetchColumn() > 0) {
                $error = "Ruangan sudah dibooking pada waktu tersebut";
            } else {
                // Simpan peminjaman
                $stmt = $db->prepare("
                    INSERT INTO peminjaman (user_id, ruangan_id, tanggal_mulai, tanggal_selesai, keperluan, status_peminjaman) 
                    VALUES (?, ?, ?, ?, ?, 'menunggu')
                ");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $ruangan_id,
                    $tanggal_mulai,
                    $tanggal_selesai,
                    $keperluan
                ]);

                header('Location: dashboard.php?msg=booking_submitted');
                exit;
            }
        }
    }
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Peminjaman Ruangan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Form Peminjaman Ruangan: <?= htmlspecialchars($ruangan['nama_ruangan']) ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Tanggal Peminjaman</label>
                                <input type="date" name="tanggal" class="form-control" 
                                       min="<?= date('Y-m-d') ?>" 
                                       value="<?= $_POST['tanggal'] ?? '' ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Jam Mulai</label>
                                        <input type="time" name="jam_mulai" class="form-control" 
                                               value="<?= $_POST['jam_mulai'] ?? '' ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Jam Selesai</label>
                                        <input type="time" name="jam_selesai" class="form-control" 
                                               value="<?= $_POST['jam_selesai'] ?? '' ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Keperluan</label>
                                <textarea name="keperluan" class="form-control" rows="3" required><?= $_POST['keperluan'] ?? '' ?></textarea>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="dashboard.php" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-primary">Ajukan Peminjaman</button>
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