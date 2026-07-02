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
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet"> <!---->
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
                            background-image: url('assets/logout.svg');
                            background-size: contain;
                            background-repeat: no-repeat;
                            background-position: center;
                            background-color: transparent;
                            width: 32px;
                            height: 32px;
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
                <div class="rooms-list">
                    <?php for ($i = 1; $i <= 20; $i++): 
                        // Для разнообразия статусов
                        $statuses = ['Priority', 'Standard', 'High Priority'];
                        $status = $statuses[array_rand($statuses)];
                        $nationalities = ['RU', 'BY', 'KZ', 'US', 'GB'];
                        $langs = ['Russian', 'English'];
                        $nationality1 = $nationalities[array_rand($nationalities)];
                        $nationality2 = $nationalities[array_rand($nationalities)];
                        $lang1 = $langs[array_rand($langs)];
                        $lang2 = $langs[array_rand($langs)];
                        $hasBirthday = $i % 3 == 0;
                    ?>
                    <div class="room-card">
                        <div class="room-header" onclick="toggleRoom(this)">
                            <div class="room-content">
                                <!-- Верхняя строка -->
                                <div class="room-row-top">
                                    <div class="room-badge room-number-badge">100<?php echo $i; ?></div>
                                    <div class="room-badge room-type-badge">KING</div>
                                    <div class="room-badge room-guests-badge">Attended 0 / 3</div>
                                    <?php if ($hasBirthday): ?>
                                    <div class="room-badge birthday-badge exact">Happy Birthday</div>
                                    <?php endif; ?>
                                </div>
                                <!-- Нижняя строка -->
                                <div class="room-row-bottom">
                                    <div class="room-badge status-badge"><?php echo $status; ?></div>
                                    <div class="room-badge nationality-badge"><?php echo $nationality1; ?></div>
                                    <div class="room-badge nationality-badge"><?php echo $nationality2; ?></div>
                                    <div class="room-badge language-badge"><?php echo $lang1; ?></div>
                                    <div class="room-badge language-badge"><?php echo $lang2; ?></div>
                                </div>
                            </div>
                            <!-- Кнопка переключателя отдельно -->
                            <div class="room-badge toggle-badge" onclick="event.stopPropagation(); toggleRoom(this.closest('.room-card').querySelector('.room-header'));">
                                <span class="toggle-icon">▼</span>
                            </div>
                        </div>
                        <div class="room-guests">
                            <?php for ($j = 1; $j <= 3; $j++): ?>
                            <div class="guest-item" onclick="guestClick(this)">
                                <span class="guest-name">Гость <?php echo $j; ?> (комната 100<?php echo $i; ?>)</span>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
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