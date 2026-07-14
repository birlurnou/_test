<?php
require_once '../config/config.php';

class DataLoader {
    private $pdo;
    private $today;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->today = date('Y-m-d');
    }
    
    public function getRooms() {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT room_number 
            FROM records 
            WHERE DATE(created_at) = :today
                -- AND LOWER(reservation_status) IN ('checked in', 'due out', 'walk in', 'walkin')
                -- ('checked in', 'due out', 'due in', 'no show', walk in', 'walkin')
                AND (LOWER(reservation_status) IN ('checked in', 'due out', 'walk in', 'walkin') OR arrival_time < '08:00' OR arrival_time IS NULL OR arrival_time IS NOT NULL)
            ORDER BY room_number ASC
        ");
        $stmt->execute([':today' => $this->today]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    public function getRoomInfo($roomNumber) {
        $stmt = $this->pdo->prepare("
            SELECT 
                r.room_number,
                r.title,
                r.vip_code_description,
                r.arrival_date,
                r.departure_date,
                r.room_type,
                r.nationality_code_description,
                r.reservation_status,
                r.arrival_time,
                r.departure_time,
                r.attended_at,
                r.created_at,
                r.guest_id,
                g.f_name,
                g.alt_f_name,
                g.birth_date,
                g.profile_id
            FROM records r
            INNER JOIN guests g ON r.guest_id = g.guest_id
            WHERE r.room_number = :room_number 
                AND DATE(r.created_at) = :today
                -- AND LOWER(reservation_status) IN ('checked in', 'due out', 'walk in', 'walkin')
                -- ('checked in', 'due out', 'due in', 'no show', walk in', 'walkin')
                AND (LOWER(reservation_status) IN ('checked in', 'due out', 'walk in', 'walkin') OR arrival_time < '08:00' OR arrival_time IS NULL OR arrival_time IS NOT NULL)
            ORDER BY r.created_at ASC, r.room_number ASC, r.arrival_date ASC, r.vip_code_description ASC NULLS LAST, g.birth_date ASC
        ");
        $stmt->execute([
            ':room_number' => $roomNumber,
            ':today' => $this->today
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getGuestCount($roomNumber) {
        $stmt = $this->pdo->prepare("
            SELECT room_number, COUNT(*) as guest_count
            FROM records
            WHERE DATE(created_at) = :today
                AND room_number = :room_number
                AND (LOWER(reservation_status) IN ('checked in', 'due out', 'walk in', 'walkin') OR arrival_time < '08:00' OR arrival_time IS NULL OR arrival_time IS NOT NULL)
            GROUP BY room_number
        ");
        $stmt->execute([
            ':today' => $this->today,
            ':room_number' => $roomNumber
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['guest_count'] : 0;
    }
    
    public function getCommentsCount($guestId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as comment_count 
            FROM comments 
            WHERE guest_id = :guest_id
        ");
        $stmt->execute([':guest_id' => $guestId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['comment_count'] : 0;
    }
    
    public function getGuestComments($guestId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                comment_id,
                comment,
                created_at,
                created_by
            FROM comments 
            WHERE guest_id = :guest_id
            ORDER BY created_at DESC
        ");
        $stmt->execute([':guest_id' => $guestId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function checkGuestAttended($guestId) {
        $stmt = $this->pdo->prepare("
            SELECT attended_at 
            FROM records 
            WHERE guest_id = :guest_id 
                AND DATE(created_at) = :today
                AND (LOWER(reservation_status) IN ('checked in', 'due out', 'walk in', 'walkin') OR arrival_time < '08:00' OR arrival_time IS NULL OR arrival_time IS NOT NULL)
            LIMIT 1
        ");
        $stmt->execute([
            ':guest_id' => $guestId,
            ':today' => $this->today
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['attended_at'] !== null;
    }

    public function getRoomInfoByGuestId($guestId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                r.room_number,
                r.arrival_date,
                r.departure_date,
                r.arrival_time,
                r.departure_time,
                r.title,
                r.vip_code_description,
                r.nationality_code_description,
                r.reservation_status,
                r.room_type,
                r.attended_at,
                g.f_name,
                g.alt_f_name,
                g.birth_date,
                g.guest_id
            FROM records r
            INNER JOIN guests g ON r.guest_id = g.guest_id
            WHERE r.guest_id = :guest_id 
                AND DATE(r.created_at) = :today
            LIMIT 1
        ");
        $stmt->execute([
            ':guest_id' => $guestId,
            ':today' => $this->today
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>