<?php
session_start();
require_once '../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$guestId = $data['guest_id'] ?? null;

if (!$guestId) {
    echo json_encode(['success' => false, 'error' => 'Guest ID required']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        UPDATE records 
        SET attended_at = CURRENT_TIMESTAMP 
        WHERE guest_id = :guest_id 
            AND DATE(created_at) = CURRENT_DATE
    ");
    $stmt->execute([':guest_id' => $guestId]);
    
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->prepare("
            SELECT attended_at 
            FROM records 
            WHERE guest_id = :guest_id 
                AND DATE(created_at) = CURRENT_DATE
            LIMIT 1
        ");
        $stmt->execute([':guest_id' => $guestId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'attended_at' => $result['attended_at']
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Guest not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>