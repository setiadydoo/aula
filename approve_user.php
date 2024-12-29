<?php
require_once 'config/database.php';

session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Check if user ID is provided
if (!isset($_GET['id'])) {
    header('Location: manage_users.php');
    exit;
}

$user_id = $_GET['id'];

try {
    // Update user status to 'active'
    $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE id = ?");
    $stmt->execute([$user_id]);

    // Redirect back to manage_users.php with approved filter
    header('Location: manage_users.php?filter=approved&msg=user_approved');
    exit;
} catch(PDOException $e) {
    // If there's an error, redirect back with error message
    header('Location: manage_users.php?error=' . urlencode($e->getMessage()));
    exit;
} 