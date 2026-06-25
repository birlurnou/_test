<?php
header('Content-Type: application/json');

session_start();

// время жизни сессии
$session_lifetime = 14400;

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
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();
        }
    }
}

echo json_encode($response);
?>