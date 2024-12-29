<?php
require_once 'config/database.php';
require_once 'classes/RoomBooking.php';

header('Content-Type: application/json');

$roomId = $_GET['room_id'] ?? null;
$date = $_GET['date'] ?? null;

if (!$roomId || !$date) {
    echo json_encode(['error' => 'Parameter tidak lengkap']);
    exit;
}

$booking = new RoomBooking($db);
$bookings = $booking->getExistingBookings($roomId, $date);

echo json_encode(['bookings' => $bookings]); 