<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/encryption_key.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$user_id = $_POST['user_id'] ?? 0;
$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'user';
$username = trim($_POST['username'] ?? '');

if (empty($user_id)) {
    echo json_encode(['success' => false, 'error' => 'User ID is required']);
    exit;
}

if (empty($login)) {
    echo json_encode(['success' => false, 'error' => 'Login is required']);
    exit;
}

if (strlen($login) < 3) {
    echo json_encode(['success' => false, 'error' => 'Login must be at least 3 characters']);
    exit;
}

if (empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Password is required']);
    exit;
}

if (strlen($password) < 3) {
    echo json_encode(['success' => false, 'error' => 'Password must be at least 3 characters']);
    exit;
}

$allowed_roles = ['user', 'manager', 'admin'];
if (!in_array($role, $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Invalid role']);
    exit;
}

if (empty($username)) {
    echo json_encode(['success' => false, 'error' => 'Username is required']);
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
    
    // шифруем пароль
    $encrypted_password = encryptPassword($password);
    
    // обновляем пользователя
    $stmt = $pdo->prepare("
        UPDATE users 
        SET login = :login, password = :password, role = :role, username = :username
        WHERE user_id = :user_id
    ");
    
    $result = $stmt->execute([
        ':login' => $login,
        ':password' => $encrypted_password,
        ':role' => $role,
        ':user_id' => $user_id,
        ':username' => $username
    ]);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update user']);
    }
    
} catch (PDOException $e) {
    error_log("Error updating user: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}