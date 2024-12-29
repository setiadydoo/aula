<?php
require_once 'config/database.php';

session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Check if booking ID and status are provided
if (!isset($_POST['booking_id']) || !isset($_POST['status'])) {
    header('Location: manage_bookings.php?error=Missing required data');
    exit;
}

$booking_id = $_POST['booking_id'];
$status = $_POST['status'];

// Validate status
$allowed_statuses = ['menunggu', 'disetujui', 'ditolak'];
if (!in_array($status, $allowed_statuses)) {
    header('Location: manage_bookings.php?error=Invalid status');
    exit;
}

try {
    // Start transaction
    $db->beginTransaction();

    // Update booking status
    $stmt = $db->prepare("UPDATE peminjaman SET status_peminjaman = ? WHERE id = ?");
    $stmt->execute([$status, $booking_id]);

    // If approved, update room status
    if ($status === 'disetujui') {
        $stmt = $db->prepare("
            UPDATE ruangan r
            JOIN peminjaman p ON r.id = p.ruangan_id
            SET r.status = 'dipinjam'
            WHERE p.id = ?
        ");
        $stmt->execute([$booking_id]);
    }

    // If rejected or pending, check if room can be marked as available
    if ($status === 'ditolak' || $status === 'menunggu') {
        $stmt = $db->prepare("
            UPDATE ruangan r
            JOIN peminjaman p ON r.id = p.ruangan_id
            SET r.status = 'tersedia'
            WHERE p.id = ? AND NOT EXISTS (
                SELECT 1 FROM peminjaman p2
                WHERE p2.ruangan_id = r.id
                AND p2.status_peminjaman = 'disetujui'
                AND p2.id != ?
            )
        ");
        $stmt->execute([$booking_id, $booking_id]);
    }

    $db->commit();

    // Redirect with success message
    $msg = $status === 'disetujui' ? 'approved' : ($status === 'ditolak' ? 'rejected' : 'updated');
    header('Location: manage_bookings.php?msg=' . $msg);
    exit;
} catch(PDOException $e) {
    // Rollback transaction on error
    $db->rollBack();
    header('Location: manage_bookings.php?error=' . urlencode($e->getMessage()));
    exit;
} 