<?php
header('Content-Type: application/json');

session_start();

// время жизни сессии
$session_lifetime = 10;

$response = ['active' => false];

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if (isset($_SESSION['login_time'])) {
        $time_left = $session_lifetime - (time() - $_SESSION['login_time']);
        if ($time_left > 0) {
            $response['active'] = true;
            $response['time_left'] = $time_left;
            $response['login_time'] = $_SESSION['login_time'];
        } else {
            // сессия истекла - очищаем
            $_SESSION = array();
            session_destroy();
        }
    }
}

echo json_encode($response);
?>