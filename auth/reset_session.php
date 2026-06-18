<?php
header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit();
}

// обновляем время входа (сбрасываем таймер)
$_SESSION['login_time'] = time();

// обновляем время последней активности
$_SESSION['last_activity'] = time();

echo json_encode([
    'success' => true,
    'login_time' => $_SESSION['login_time']
]);
?>