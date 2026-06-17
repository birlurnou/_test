<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$user_id = $_POST['user_id'] ?? 0;

if (empty($user_id)) {
    echo json_encode(['success' => false, 'error' => 'User ID is required']);
    exit;
}

try {
    // проверяем, существует ли пользователь
    $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = :user_id");
    $checkStmt->execute([':user_id' => $user_id]);
    
    if (!$checkStmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }
    
    // удаляем пользователя
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = :user_id");
    $result = $stmt->execute([':user_id' => $user_id]);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete user']);
    }
    
} catch (PDOException $e) {
    error_log("Error deleting user: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}