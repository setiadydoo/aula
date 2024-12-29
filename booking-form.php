<?php
require_once 'config/database.php';
require_once 'classes/RoomBooking.php';

session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$roomId = $_GET['room_id'] ?? null;
if (!$roomId) {
    header('Location: rooms.php');
    exit;
}

$booking = new RoomBooking($db);
$existingBookings = $booking->getExistingBookings($roomId);

// Ambil detail ruangan
$stmt = $db->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$roomId]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingDate = $_POST['booking_date'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $purpose = $_POST['purpose'] ?? '';

    // Validasi
    if (empty($bookingDate) || empty($startTime) || empty($endTime) || empty($purpose)) {
        $errors[] = "Semua field harus diisi";
    }

    if (strtotime($bookingDate) < strtotime(date('Y-m-d'))) {
        $errors[] = "Tanggal peminjaman tidak valid";
    }

    if ($startTime >= $endTime) {
        $errors[] = "Jam selesai harus lebih besar dari jam mulai";
    }

    // Cek overlap
    if (empty($errors)) {
        if ($booking->checkOverlap($roomId, $bookingDate, $startTime, $endTime)) {
            $errors[] = "Ruangan sudah dibooking pada waktu tersebut";
        }
    }

    // Simpan booking
    if (empty($errors)) {
        if ($booking->create($_SESSION['user_id'], $roomId, $bookingDate, $startTime, $endTime, $purpose)) {
            $success = "Permintaan peminjaman ruangan berhasil diajukan";
        } else {
            $errors[] = "Gagal menyimpan peminjaman";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peminjaman Ruangan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h4>Peminjaman Ruangan: <?= htmlspecialchars($room['name']) ?></h4>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Tanggal Peminjaman</label>
                        <input type="date" name="booking_date" class="form-control" 
                               min="<?= date('Y-m-d') ?>" 
                               value="<?= $_POST['booking_date'] ?? '' ?>" 
                               required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Jam Mulai</label>
                                <input type="time" name="start_time" class="form-control" 
                                       value="<?= $_POST['start_time'] ?? '' ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Jam Selesai</label>
                                <input type="time" name="end_time" class="form-control" 
                                       value="<?= $_POST['end_time'] ?? '' ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tujuan Peminjaman</label>
                        <textarea name="purpose" class="form-control" rows="3" required><?= $_POST['purpose'] ?? '' ?></textarea>
                    </div>

                    <div class="mb-4">
                        <h5>Jadwal Peminjaman yang Sudah Ada:</h5>
                        <div id="existing-bookings">
                            <?php if (empty($existingBookings)): ?>
                                <p class="text-muted">Belum ada peminjaman untuk ruangan ini.</p>
                            <?php else: ?>
                                <?php foreach ($existingBookings as $booking): ?>
                                    <div class="alert alert-info">
                                        <?= date('d/m/Y', strtotime($booking['booking_date'])) ?> : 
                                        <?= date('H:i', strtotime($booking['start_time'])) ?> - 
                                        <?= date('H:i', strtotime($booking['end_time'])) ?>
                                        (<?= htmlspecialchars($booking['user_name']) ?>)
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="rooms.php" class="btn btn-secondary me-2">Batal</a>
                        <button type="submit" class="btn btn-primary">Ajukan Peminjaman</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.querySelector('input[name="booking_date"]').addEventListener('change', function(e) {
        const date = e.target.value;
        fetch(`check-availability.php?room_id=<?= $roomId ?>&date=${date}`)
            .then(response => response.json())
            .then(data => {
                const bookingsDiv = document.getElementById('existing-bookings');
                bookingsDiv.innerHTML = '';
                
                if (data.bookings.length === 0) {
                    bookingsDiv.innerHTML = '<p class="text-muted">Tidak ada peminjaman pada tanggal ini.</p>';
                    return;
                }

                data.bookings.forEach(booking => {
                    const div = document.createElement('div');
                    div.className = 'alert alert-info';
                    div.textContent = `${booking.start_time} - ${booking.end_time} (${booking.user_name})`;
                    bookingsDiv.appendChild(div);
                });
            });
    });
    </script>
</body>
</html> 