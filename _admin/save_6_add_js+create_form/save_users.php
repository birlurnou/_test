<?php
require_once 'encryption_key.php';

$users_file = '../config/users.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($data) {
        // Шифруем пароли перед сохранением
        foreach ($data as &$user) {
            if (isset($user['password'])) {
                // Если пароль ещё не зашифрован (не содержит ::)
                if (strpos($user['password'], '::') === false) {
                    $user['password'] = encryptPassword($user['password']);
                }
            }
        }
        
        if (file_put_contents($users_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Не удалось записать файл']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Нет данных']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Неподдерживаемый метод']);
}
?>