<?php
session_start();
require_once 'config/database.php';

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Cek apakah ada data yang dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $pekerjaan = trim($_POST['pekerjaan']);

    try {
        // Update pekerjaan user
        $stmt = $db->prepare("UPDATE users SET pekerjaan = ? WHERE id = ?");
        $stmt->execute([$pekerjaan, $user_id]);

        // Redirect kembali dengan pesan sukses
        header('Location: manage_users.php?msg=job_updated');
        exit;
    } catch(PDOException $e) {
        // Jika terjadi error, redirect dengan pesan error
        header('Location: manage_users.php?error=' . urlencode($e->getMessage()));
        exit;
    }
} else {
    // Jika bukan POST request, redirect ke halaman manage users
    header('Location: manage_users.php');
    exit;
} 