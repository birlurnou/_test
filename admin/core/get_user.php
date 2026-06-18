<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/encryption_key.php';

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
    $stmt = $pdo->prepare("SELECT user_id, login, password, role FROM users WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch();
    
    if ($user) {
        // расшифровываем пароль
        if (isset($user['password'])) {
            $user['password'] = decryptPassword($user['password']);
        }
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'error' => 'User not found']);
    }
    
} catch (PDOException $e) {
    error_log("Error loading user: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}