<?php
// logout.php - выход из системы
session_start();

// проверяем, был ли запрос через AJAX (navigator.sendBeacon)
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// очищаем все данные сессии
$_SESSION = array();

// удаляем cookie сессии
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// уничтожаем сессию
session_destroy();

// если AJAX запрос - возвращаем JSON
if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit();
}

// перенаправляем на страницу входа
header('Location: ../login/index.php?logout=1');
// header('Location: index.php');
exit();
?>