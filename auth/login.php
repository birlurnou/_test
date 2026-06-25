<?php
// login.php - API endpoint для авторизации
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/encryption_key.php';

// запускаем сессию
session_start();

// получаем данные из JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['login']) || !isset($input['password'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Неверный формат запроса'
    ]);
    exit();
}

$login = trim($input['login']);
$password = $input['password'];

// валидация
if (empty($login) || empty($password)) {
    echo json_encode([
        'success' => false,
        'error' => 'Пожалуйста, заполните все поля'
    ]);
    exit();
}

try {
    // поиск пользователя по логину
    $stmt = $pdo->prepare("SELECT user_id, login, password, role, username FROM users WHERE login = :login");
    $stmt->execute([':login' => $login]);
    $user = $stmt->fetch();
    
    if ($user) {
        // расшифровываем пароль из БД
        $decryptedPassword = decryptPassword($user['password']);
        
        // сравниваем с введенным паролем
        if ($password === $decryptedPassword) {
            // успешная авторизация - создаем сессию
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['login'] = $user['login'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            
            // определяем страницу для редиректа в зависимости от роли
            // $redirect = in_array($user['role'], ['manager', 'admin']) ? '../processing/index.php' : '../main/user.php';
            $redirect = in_array($user['role'], ['manager', 'admin']) ? '../processing/index.php' : '../_test_main/index.php';
            
            echo json_encode([
                'success' => true,
                'redirect' => $redirect,
                'role' => $user['role']
            ]);
            exit();
        }
    }
    
    // неверный логин или пароль
    echo json_encode([
        'success' => false,
        'error' => 'Неверный логин или пароль'
    ]);
    exit();
    
} catch (PDOException $e) {
    error_log("Authorization error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Произошла ошибка при авторизации'
    ]);
    exit();
}
?>