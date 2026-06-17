<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/encryption_key.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'user';

// валидация
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

try {
    // проверка на существование пользователя
    $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE login = :login");
    $checkStmt->execute([':login' => $login]);
    
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'User already exists']);
        exit;
    }
    
    // шифрование пароля
    $encrypted_password = encryptPassword($password);
    
    // вставка нового пользователя
    $stmt = $pdo->prepare("
        INSERT INTO users (login, password, role) 
        VALUES (:login, :password, :role)
    ");
    
    $result = $stmt->execute([
        ':login' => $login,
        ':password' => $encrypted_password,
        ':role' => $role
    ]);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create user']);
    }
    
} catch (PDOException $e) {
    error_log("Error creating user: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}