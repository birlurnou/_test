<?php
session_start();

$session_lifetime = 14400;

// проверка времени входа
if (!isset($_SESSION['login_time'])) {
    $_SESSION['login_time'] = time();
}

// проверка, не истекло ли время жизни сессии
if (time() - $_SESSION['login_time'] > $session_lifetime) {
    $_SESSION = array();
    session_unset();
    session_destroy();
    header('Location: ../login/index.php?expired=1');
    exit();
}

// проверка авторизации
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login/index.php');
    exit();
}

// проверка роли
// if ($_SESSION['role'] !== 'user') {
//     header('Location: ../login/index.php');
//     exit();
// }

$login_time = $_SESSION['login_time'];
$time_left = $session_lifetime - (time() - $login_time);

function truncateText($text, $length) {
    if (mb_strlen($text) > $length) {
        return mb_substr($text, 0, $length) . '...';
    }
    return $text;
}

$login = htmlspecialchars($_SESSION['login']) . ' ' . gmdate('H:i:s', $time_left);
// $username = 'Осталось: ' . gmdate('H:i:s', $time_left);
$username = htmlspecialchars($_SESSION['username']);

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Accounting</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="layout">
        <div class="area">

            <!-- верхний блок -->
            <header class="top-block">
                <div class="top-block-content">

                    <!-- лого -->
                    <div class="logo-section">
                        <img src="assets/logo.webp" alt="Logo" class="logo-image">
                    </div>

                    <!-- поисковая строка -->
                    <div class="search-section">
                        <input type="text" class="search-input" placeholder="Search room...">
                    </div>

                    <!-- правая секция -->
                    <div class="right-section">

                        <!-- информация о пользователе -->
                        <div class="user-info">
                            <p class="username"><?php echo $login; ?></p>
                            <p><?php echo $username; ?></p>
                        </div>

                        <!-- кнопка выхода -->
                        <button 
                          onclick="confirmLogout()" 
                          style="
                            background-image: url('assets/logout2.svg');
                            background-size: contain;
                            background-repeat: no-repeat;
                            background-position: center;
                            background-color: transparent;
                            width: 33px;
                            height: 33px;
                            border: none;
                            cursor: pointer;
                            text-indent: -9999px;
                            transition: transform 0.2s ease;
                            padding: 0;
                            margin-left: 15px;
                          "
                          onmouseenter="this.style.transform='scale(1.05)'"
                          onmouseleave="this.style.transform='scale(1)'"
                          onmousedown="this.style.transform='scale(1.05)'"
                          onmouseup="this.style.transform='scale(1.05)'"
                        >
                          Выйти
                        </button>

                    </div>

                </div>
            </header>

            <!-- основной блок -->
            <main class="content">

            </main>

        </div>
    </div>
    
    <script src="script.js"></script>
    <script>
        window.loginTime = <?php echo $login_time; ?>;
        window.sessionLifetime = <?php echo $session_lifetime; ?>;
    </script>
</body>
</html>