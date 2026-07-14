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
$commentId = $data['comment_id'] ?? null;
$comment = $data['comment'] ?? null;

if (!$commentId || !$comment) {
    echo json_encode(['success' => false, 'error' => 'Comment ID and comment are required']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        UPDATE comments 
        SET comment = :comment 
        WHERE comment_id = :comment_id
    ");
    $stmt->execute([
        ':comment_id' => $commentId,
        ':comment' => $comment
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Comment not found or no changes made']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>