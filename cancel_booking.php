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
    // Start transaction
    $db->beginTransaction();

    // Get booking info to check ownership and status
    $stmt = $db->prepare("
        SELECT status_peminjaman, ruangan_id, user_id 
        FROM peminjaman 
        WHERE id = ?
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new PDOException("Peminjaman tidak ditemukan");
    }

    // Check if user owns this booking
    if ($booking['user_id'] != $user_id && $_SESSION['role'] !== 'admin') {
        throw new PDOException("Anda tidak memiliki akses untuk membatalkan peminjaman ini");
    }

    // Check if booking can be cancelled (not already rejected)
    if ($booking['status_peminjaman'] === 'ditolak') {
        throw new PDOException("Peminjaman yang sudah ditolak tidak dapat dibatalkan");
    }

    // Update booking status to cancelled
    $stmt = $db->prepare("UPDATE peminjaman SET status_peminjaman = 'ditolak' WHERE id = ?");
    $stmt->execute([$booking_id]);

    // If booking was approved, check if room can be marked as available
    if ($booking['status_peminjaman'] === 'disetujui') {
        $stmt = $db->prepare("
            UPDATE ruangan 
            SET status = 'tersedia'
            WHERE id = ? AND NOT EXISTS (
                SELECT 1 FROM peminjaman 
                WHERE ruangan_id = ? 
                AND status_peminjaman = 'disetujui'
                AND id != ?
            )
        ");
        $stmt->execute([$booking['ruangan_id'], $booking['ruangan_id'], $booking_id]);
    }

    $db->commit();
    header('Location: dashboard.php?msg=booking_cancelled');
} catch(PDOException $e) {
    $db->rollBack();
    header('Location: dashboard.php?error=' . urlencode($e->getMessage()));
}
exit; 