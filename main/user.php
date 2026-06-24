<?php
// user.php - страница для обычных пользователей
session_start();

// время жизни сессии (2 часа)
$session_lifetime = 5;//7200;

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
<html>
<head>
    <meta charset="UTF-8">
    <title>User Panel</title>
</head>
<body>
    <p>Логин: <?php echo htmlspecialchars($_SESSION['login']); ?></p>
    <p>Роль: <?php echo htmlspecialchars($_SESSION['role']); ?></p>
    <p>Осталось: <span id="timer"><?php echo gmdate('i:s', $time_left); ?></span></p>
    <button onclick="confirmLogout()">Выйти</button>

    <script>
        let loginTime = <?php echo $login_time; ?>;
        const sessionLifetime = <?php echo $session_lifetime; ?>;
        const timerElement = document.getElementById('timer');
        let timerInterval;

        function resetTimer() {
            fetch('../auth/reset_session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loginTime = data.login_time;
                    updateTimer();
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        function updateTimer() {
            const now = Math.floor(Date.now() / 1000);
            const elapsed = now - loginTime;
            const timeLeft = sessionLifetime - elapsed;
            
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                timerElement.textContent = '00:00';
                return;
            }
            
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerElement.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }

        function checkSession() {
            fetch('../auth/check_session.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.active) {
                    clearInterval(timerInterval);
                    // Мгновенный переход
                    window.location.href = '../login/index.php?expired=1';
                } else if (data.login_time) {
                    // Обновляем время с сервера
                    loginTime = data.login_time;
                }
            })
            .catch(() => {
                window.location.href = '../login/index.php?expired=1';
            });
        }

        function handleUserActivity() {
            resetTimer();
        }

        // События для отслеживания активности
        document.addEventListener('click', handleUserActivity);
        document.addEventListener('mousemove', handleUserActivity);
        document.addEventListener('keydown', handleUserActivity);
        document.addEventListener('scroll', handleUserActivity);
        document.addEventListener('touchstart', handleUserActivity);
        document.addEventListener('touchmove', handleUserActivity);
        document.addEventListener('wheel', handleUserActivity);
        document.addEventListener('input', handleUserActivity);

        updateTimer();
        timerInterval = setInterval(updateTimer, 1000);
        setInterval(checkSession, 5000);

        function confirmLogout() {
            if (confirm('Вы уверены, что хотите выйти?')) {
                window.location.href = '../auth/logout.php';
            }
        }
    </script>
</body>
</html>