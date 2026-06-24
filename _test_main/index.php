<?php
session_start();

$session_lifetime = 7200;

// Проверяем время входа
if (!isset($_SESSION['login_time'])) {
    $_SESSION['login_time'] = time();
}

// Проверяем, не истекла ли сессия
if (time() - $_SESSION['login_time'] > $session_lifetime) {
    $_SESSION = array();
    session_unset();
    session_destroy();
    header('Location: ../login/index.php?expired=1');
    exit();
}

// Проверка авторизации
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login/index.php');
    exit();
}

// Проверка роли
if ($_SESSION['role'] !== 'user') {
    header('Location: ../login/index.php');
    exit();
}

$login_time = $_SESSION['login_time'];
$time_left = $session_lifetime - (time() - $login_time);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="layout">
        <div class="area">

            <!-- верхний блок -->
            <header class="top-block">
                <div class="top-block-content">
                    <div class="search-section">
                        <input type="text" class="search-input" placeholder="Search room...">
                    </div>
                    
                    <div class="right-section">
                        <div class="user-info">
                            <p class="username"><?php echo htmlspecialchars($_SESSION['login']); ?></p>
                            <p>Осталось: <span id="timer"><?php echo gmdate('H:i:s', $time_left); ?></span></p>
                        </div>
                        <button 
                          onclick="confirmLogout()" 
                          style="
                            background-image: url('assets/logout2.svg');
                            background-size: contain;
                            background-repeat: no-repeat;
                            background-position: center;
                            background-color: transparent;
                            width: 40px;
                            height: 40px;
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

            <!-- основной контент -->
            <main class="content">

            </main>
        </div>
    </div>
    
    <script src="script.js"></script>
    <script>
        // Передаем данные из PHP в JS
        window.loginTime = <?php echo $login_time; ?>;
        window.sessionLifetime = <?php echo $session_lifetime; ?>;
    </script>
</body>
</html>