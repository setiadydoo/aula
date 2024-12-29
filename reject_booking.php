<?php
require_once 'config/database.php';

session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Check if booking ID is provided
if (!isset($_GET['id'])) {
    header('Location: manage_bookings.php');
    exit;
}

$booking_id = $_GET['id'];

try {
    // Update booking status to 'ditolak'
    $stmt = $db->prepare("UPDATE peminjaman SET status_peminjaman = 'ditolak' WHERE id = ?");
    $stmt->execute([$booking_id]);

    // Redirect back with success message
    header('Location: manage_bookings.php?msg=rejected');
    exit;
} catch(PDOException $e) {
    // If there's an error, redirect back with error message
    header('Location: manage_bookings.php?error=' . urlencode($e->getMessage()));
    exit;
} 