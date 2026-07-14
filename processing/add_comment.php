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
$comment = $data['comment'] ?? null;
$createdBy = $data['created_by'] ?? $_SESSION['login'] ?? null;

if (!$guestId || !$comment) {
    echo json_encode(['success' => false, 'error' => 'Guest ID and comment are required']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO comments (guest_id, comment, created_by) 
        VALUES (:guest_id, :comment, :created_by)
        RETURNING comment_id
    ");
    $stmt->execute([
        ':guest_id' => $guestId,
        ':comment' => $comment,
        ':created_by' => $createdBy
    ]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'comment_id' => $result['comment_id']
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>