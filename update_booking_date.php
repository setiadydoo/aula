<?php
session_start();
require_once 'config/database.php';

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'];
    $tanggal = $_POST['tanggal'];
    $waktu_mulai = $_POST['waktu_mulai'];
    $waktu_selesai = $_POST['waktu_selesai'];

    // Gabungkan tanggal dan waktu
    $tanggal_mulai = $tanggal . ' ' . $waktu_mulai;
    $tanggal_selesai = $tanggal . ' ' . $waktu_selesai;

    try {
        // Start transaction
        $db->beginTransaction();

        // Cek apakah ada bentrok dengan peminjaman lain
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM peminjaman p1
            JOIN peminjaman p2 ON p1.ruangan_id = p2.ruangan_id
            WHERE p1.id = ?
            AND p2.id != p1.id
            AND p2.status_peminjaman != 'ditolak'
            AND p2.tanggal_mulai = ?
            AND p2.tanggal_selesai = ?
        ");
        $stmt->execute([$booking_id, $tanggal_mulai, $tanggal_selesai]);
        $conflict_count = $stmt->fetchColumn();

        if ($conflict_count > 0) {
            throw new Exception("Waktu yang dipilih bentrok dengan peminjaman lain");
        }

        // Update tanggal peminjaman
        $stmt = $db->prepare("
            UPDATE peminjaman 
            SET tanggal_mulai = ?, tanggal_selesai = ?
            WHERE id = ?
        ");
        $stmt->execute([$tanggal_mulai, $tanggal_selesai, $booking_id]);

        $db->commit();
        header('Location: manage_bookings.php?msg=date_updated');
        exit;
    } catch(Exception $e) {
        $db->rollBack();
        header('Location: manage_bookings.php?error=' . urlencode($e->getMessage()));
        exit;
    }
} else {
    header('Location: manage_bookings.php');
    exit;
} 