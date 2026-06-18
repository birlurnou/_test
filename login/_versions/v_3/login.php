<?php
// login.php - API endpoint для авторизации
header('Content-Type: application/json');

require_once 'config.php';
require_once 'encryption_key.php';

// Получаем данные из JSON
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

// Валидация
if (empty($login) || empty($password)) {
    echo json_encode([
        'success' => false,
        'error' => 'Пожалуйста, заполните все поля'
    ]);
    exit();
}

try {
    // Поиск пользователя по логину
    $stmt = $pdo->prepare("SELECT user_id, login, password, role FROM users WHERE login = :login");
    $stmt->execute([':login' => $login]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Расшифровываем пароль из БД
        $decryptedPassword = decryptPassword($user['password']);
        
        // Сравниваем с введенным паролем
        if ($password === $decryptedPassword) {
            // Успешная авторизация
            echo json_encode([
                'success' => true,
                'redirect' => 'dashboard.php'
            ]);
            exit();
        }
    }
    
    // Неверный логин или пароль
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