<?php
class RoomBooking {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function checkOverlap($roomId, $date, $startTime, $endTime) {
        $query = "SELECT COUNT(*) FROM room_bookings 
                 WHERE room_id = ? 
                 AND booking_date = ? 
                 AND status = 'approved'
                 AND (
                     (start_time BETWEEN ? AND ?) 
                     OR (end_time BETWEEN ? AND ?)
                     OR (start_time <= ? AND end_time >= ?)
                 )";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$roomId, $date, $startTime, $endTime, $startTime, $endTime, $startTime, $endTime]);
        
        return $stmt->fetchColumn() > 0;
    }

    public function create($userId, $roomId, $date, $startTime, $endTime, $purpose) {
        $query = "INSERT INTO room_bookings (user_id, room_id, booking_date, start_time, end_time, purpose) 
                 VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$userId, $roomId, $date, $startTime, $endTime, $purpose]);
    }

    public function getExistingBookings($roomId, $date = null) {
        $query = "SELECT rb.*, u.name as user_name 
                 FROM room_bookings rb
                 JOIN users u ON rb.user_id = u.id
                 WHERE rb.room_id = ? 
                 AND rb.status = 'approved'";
        
        $params = [$roomId];
        
        if ($date) {
            $query .= " AND rb.booking_date = ?";
            $params[] = $date;
        } else {
            $query .= " AND rb.booking_date >= CURDATE()";
        }
        
        $query .= " ORDER BY rb.booking_date, rb.start_time";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 