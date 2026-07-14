<?php
session_start();

require_once '../config/config.php';
require_once 'data_loader.php';

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

$login = htmlspecialchars($_SESSION['login']);
$username = htmlspecialchars($_SESSION['username']);
$login_time = $_SESSION['login_time'];
$time_left = $session_lifetime - (time() - $login_time);

// Инициализируем загрузчик данных
$dataLoader = new DataLoader($pdo);

// Получаем список комнат
$rooms = $dataLoader->getRooms();

// Функция для форматирования имени
function formatName($fullName) {
    if (empty($fullName)) return 'Unknown';
    
    $parts = explode(' ', trim($fullName));
    if (count($parts) < 2) return $fullName;
    
    // Формат: "Фамилия Имя Отчество" -> "Имя Ф*"
    $firstName = $parts[1] ?? $parts[0];
    $lastName = $parts[0] ?? '';
    
    if (!empty($lastName) && !empty($firstName)) {
        return $firstName . ' ' . substr($lastName, 0, 2) . '*';
    }
    
    return $firstName;
}

// Функция для определения пола
function getGender($title) {
    if (empty($title)) return 'Unknown';
    $titleLower = strtolower(trim($title));
    if ($titleLower === 'mr' || $titleLower === 'mr.') return 'Male';
    if (in_array($titleLower, ['mrs', 'ms', 'miss', 'mrs.', 'ms.'])) return 'Female';
    return 'Unknown';
}

// Функция для определения возраста
function getAge($birthDate) {
    if (empty($birthDate)) return null;
    try {
        $birth = new DateTime($birthDate);
        $today = new DateTime();
        return $today->diff($birth)->y;
    } catch (Exception $e) {
        return null;
    }
}

// Функция для определения статуса дня рождения
function getBirthdayStatus($birthDate) {
    if (empty($birthDate)) return null;
    
    try {
        $birth = new DateTime($birthDate);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        
        // День рождения в этом году
        $birthThisYear = new DateTime($birthDate);
        $birthThisYear->setDate($today->format('Y'), $birth->format('m'), $birth->format('d'));
        $birthThisYear->setTime(0, 0, 0);
        
        $diff = $today->diff($birthThisYear);
        $days = (int)$diff->days;
        
        // Если день рождения сегодня
        if ($diff->invert == 0 && $days == 0) {
            return ['type' => 'today', 'days' => 0];
        }
        
        // Если день рождения был (в прошлом) - invert == 1
        if ($diff->invert == 1) {
            if ($days >= 1 && $days <= 3) {
                return ['type' => 'was', 'days' => $days];
            }
        }
        
        // Если день рождения будет (в будущем) - invert == 0 и days > 0
        if ($diff->invert == 0 && $days > 0) {
            if ($days >= 1 && $days <= 3) {
                return ['type' => 'will', 'days' => $days];
            }
        }
        
        return null;
    } catch (Exception $e) {
        return null;
    }
}

// Функция для получения статуса VIP
function getVipStatus($roomInfo) {
    foreach ($roomInfo as $guest) {
        if (!empty($guest['vip_code_description'])) {
            return ucwords(trim($guest['vip_code_description']));
        }
    }
    return null;
}

// Функция для получения типа комнаты
function getRoomType($roomInfo) {
    foreach ($roomInfo as $guest) {
        if (!empty($guest['room_type'])) {
            return strtoupper($guest['room_type']);
        }
    }
    return 'STANDARD';
}

// Функция для форматирования времени
function formatTime($time) {
    if (empty($time)) return '--:--';
    try {
        $timeObj = new DateTime($time);
        return $timeObj->format('H:i');
    } catch (Exception $e) {
        return '--:--';
    }
}

// Функция для форматирования даты
function formatDate($date) {
    if (empty($date)) return '--.--.----';
    try {
        $dateObj = new DateTime($date);
        return $dateObj->format('d.m.Y');
    } catch (Exception $e) {
        return '--.--.----';
    }
}

// Функция для форматирования даты и времени
function formatDateTime($datetime) {
    if (empty($datetime)) return '--.--.---- --:--';
    try {
        $dt = new DateTime($datetime);
        return $dt->format('d.m.Y H:i');
    } catch (Exception $e) {
        return '--.--.---- --:--';
    }
}

// Функция для форматирования страны с заглавной буквы
function formatCountry($country) {
    if (empty($country)) return 'Unknown ';
    $words = explode(' ', trim($country));
    $formatted = array_map(function($word) {
        return ucfirst(strtolower(trim($word)));
    }, $words);
    return implode(' ', $formatted);
}

// Функция для форматирования статуса бронирования
function formatReservationStatus($status) {
    if (empty($status)) return 'Unknown Reservation Status';
    $words = explode(' ', trim($status));
    $formatted = array_map(function($word) {
        return ucfirst(strtolower(trim($word)));
    }, $words);
    return implode(' ', $formatted);
}

function truncateText($text, $length) {
    if (mb_strlen($text) > $length) {
        return mb_substr($text, 0, $length) . '...';
    }
    return $text;
}
?>






<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Accounting</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
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
                        <img src="../assets/images/logo.webp" alt="Logo" class="logo-image">
                    </div>

                    <!-- поисковая строка -->
                    <div class="search-section">
                        <input type="text" class="search-input" placeholder="Search room..." id="searchRoom">
                    </div>

                    <!-- правая секция -->
                    <div class="right-section">

                        <!-- информация о пользователе -->
                        <div class="user-info">
                            <p class="username"><?php echo htmlspecialchars($_SESSION['login']) #. ' ' . gmdate('H:i:s', $time_left); ?></p>
                            <p><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                        </div>

                        <!-- кнопка выхода -->
                        <button 
                          onclick="confirmLogout()" 
                          style="
                            background-image: url('../assets/icons/logout.svg');
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
                <div class="rooms-list" id="roomsList">
                    <?php foreach ($rooms as $roomNumber): 
                        $roomInfo = $dataLoader->getRoomInfo($roomNumber);
                        $guestCount = $dataLoader->getGuestCount($roomNumber);
                        $vipStatus = getVipStatus($roomInfo);
                        $roomType = getRoomType($roomInfo);
                        
                        // Проверяем дни рождения в комнате
                        $birthdayStatus = null;
                        $closestBirthday = null;
                        foreach ($roomInfo as $guest) {
                            $status = getBirthdayStatus($guest['birth_date']);
                            if ($status) {
                                if ($status['type'] === 'today') {
                                    $birthdayStatus = 'today';
                                    $closestBirthday = 0;
                                    break;
                                } elseif ($closestBirthday === null || $status['days'] < $closestBirthday) {
                                    $closestBirthday = $status['days'];
                                    $birthdayStatus = $status['type'];
                                }
                            }
                        }
                        
                        // проверяем содержимое нижней строки
                        $hasBottomContent = !empty($vipStatus) || ($birthdayStatus !== null);
                        $roomContentClass = 'room-content' . ($hasBottomContent ? '' : ' no-bottom');

                        // Проверяем, все ли гости отмечены
                        $allAttended = true;
                        $attendedCount = 0;
                        foreach ($roomInfo as $guest) {
                            if (!empty($guest['attended_at'])) {
                                $attendedCount++;
                            } else {
                                $allAttended = false;
                            }
                        }
                    ?>
                    <div class="room-card" data-room="<?php echo $roomNumber; ?>">

                        <div class="room-header" onclick="toggleRoom(this)">
                            <div class="room-content">
                                <div class="<?php echo $roomContentClass; ?>">
                                
                                    <!-- верхняя строка комнаты -->
                                    <div class="room-row-top">
                                        <div class="room-badge room-number-badge"><?php echo $roomNumber; ?></div>
                                        <div class="room-badge room-type-badge"><?php echo $roomType; ?></div>
                                        <div class="room-badge room-guests-badge <?php echo ($allAttended && $guestCount > 0) ? 'all-attended' : ''; ?>">
                                            Attended <?php echo $attendedCount; ?> / <?php echo $guestCount; ?>
                                        </div>
                                    </div>

                                    <!-- нижняя строка комнаты -->
                                    <div class="room-row-bottom">
                                        <?php if ($vipStatus): ?>
                                        <div class="room-badge status-badge"><?php echo htmlspecialchars($vipStatus); ?></div>
                                        <?php endif; ?>
                                        
                                        <?php if ($birthdayStatus === 'today'): ?>
                                        <div class="room-badge birthday-badge exact">Happy Birthday</div>
                                        <?php elseif ($birthdayStatus === 'was'): ?>
                                        <div class="room-badge birthday-badge near">Birthday was <?php echo $closestBirthday; ?> day<?php echo $closestBirthday > 1 ? 's' : ''; ?> ago</div>
                                        <?php elseif ($birthdayStatus === 'will'): ?>
                                        <div class="room-badge birthday-badge near">Birthday in <?php echo $closestBirthday; ?> day<?php echo $closestBirthday > 1 ? 's' : ''; ?></div>
                                        <?php endif; ?>
                                    </div>

                                </div>
                            </div>

                            <!-- кнопка переключателя -->
                            <div class="room-badge toggle-badge" onclick="event.stopPropagation(); toggleRoom(this.closest('.room-card').querySelector('.room-header'));">
                                <span class="toggle-icon">▼</span>
                            </div>

                        </div>

                        <!-- блоки с гостями -->
                        <div class="room-guests">
                            <?php foreach ($roomInfo as $guest): 
                                $formattedName = formatName($guest['alt_f_name'] ?? $guest['f_name']);
                                $gender = getGender($guest['title']);
                                $age = getAge($guest['birth_date']);
                                $isChild = $age !== null && $age < 13;
                                $commentsCount = $dataLoader->getCommentsCount($guest['guest_id']);
                                $isAttended = !empty($guest['attended_at']);
                                $attentionTime = $isAttended ? $dataLoader->calculateAttentionTime($guest['attended_at']) : null;
                                $guestComments = $dataLoader->getGuestComments($guest['guest_id']);
                                
                                // Статус гостя
                                $guestVipStatus = !empty($guest['vip_code_description']) ? ucwords(strtolower(trim($guest['vip_code_description']))) : null;
                                
                                // Статус дня рождения гостя
                                $guestBirthdayStatus = getBirthdayStatus($guest['birth_date']);
                            ?>
                            <div class="guest-wrapper">
                                <div class="guest-item" onclick="toggleGuest(this)">
                                    <div class="guest-header">

                                        <!-- верхняя строка гостя -->
                                        <div class="guest-row-top">
                                            <span class="guest-badge guest-name-badge"><?php echo htmlspecialchars($formattedName); ?></span>
                                            <?php if ($guestVipStatus): ?>
                                            <span class="guest-badge guest-status-badge"><?php echo htmlspecialchars($guestVipStatus); ?></span>
                                            <?php endif; ?>
                                            <span class="guest-badge guest-gender-badge" style="background: <?php echo $gender === 'Male' ? '#6495ED' : '#FFB6C1'; ?>;">
                                                <?php echo htmlspecialchars($gender); ?>
                                            </span>
                                            <span class="guest-badge guest-nationality-badge">
                                                <?php echo htmlspecialchars(formatCountry($guest['nationality_code_description'] ?? 'Unknown Country')); ?>
                                            </span>
                                            <?php if ($isChild): ?>
                                            <span class="guest-badge guest-maturity-badge">Child</span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- средняя строка гостя -->
                                        <div class="guest-row-middle">
                                            <span class="guest-badge guest-arrival-date-badge"><?php echo formatDate($guest['arrival_date']); ?></span>
                                            <span class="guest-badge guest-arrival-time-badge"><?php echo formatTime($guest['arrival_time']); ?></span>
                                            <span class="guest-badge guest-departure-date-badge"><?php echo formatDate($guest['departure_date']); ?></span>
                                            <span class="guest-badge guest-departure-time-badge"><?php echo formatTime($guest['departure_time']); ?></span>
                                            <?php if ($commentsCount > 0): ?>
                                            <span class="guest-badge guest-comment-badge"><?php echo $commentsCount; ?> comment<?php echo $commentsCount > 1 ? 's' : ''; ?></span>
                                            <?php else: ?>
                                            <span class="guest-badge guest-comment-badge" style="display: none;">0 comments</span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- нижняя строка гостя -->
                                        <div class="guest-row-bottom">
                                            <?php if (strtolower($guest['reservation_status'] ?? '') !== 'check in' && !empty($guest['reservation_status'])): ?>
                                            <span class="guest-badge guest-res-stat-badge"><?php echo htmlspecialchars(formatReservationStatus($guest['reservation_status'])); ?></span>
                                            <?php endif; ?>
                                            
                                            <?php if ($guestBirthdayStatus && $guestBirthdayStatus['type'] === 'today'): ?>
                                            <span class="guest-badge guest-birthday-badge exact">Happy Birthday</span>
                                            <?php elseif ($guestBirthdayStatus && $guestBirthdayStatus['type'] === 'was'): ?>
                                            <span class="guest-badge guest-birthday-badge near">Birthday was <?php echo $guestBirthdayStatus['days']; ?> day<?php echo $guestBirthdayStatus['days'] > 1 ? 's' : ''; ?> ago</span>
                                            <?php elseif ($guestBirthdayStatus && $guestBirthdayStatus['type'] === 'will'): ?>
                                            <span class="guest-badge guest-birthday-badge near">Birthday in <?php echo $guestBirthdayStatus['days']; ?> day<?php echo $guestBirthdayStatus['days'] > 1 ? 's' : ''; ?></span>
                                            <?php endif; ?>
                                            
                                            <?php if ($isAttended && $attentionTime): ?>
                                            <span class="guest-badge guest-attention-badge" style="background: rgba(244, 67, 54, 0.8); color: white;">Attention: <?php echo $attentionTime; ?></span>
                                            <?php endif; ?>
                                        </div>

                                    </div>
                                    <!-- кликабельная область справа -->
                                    <div class="guest-click-area <?php echo $isAttended ? 'checked-in' : ''; ?>" 
                                         onclick="event.stopPropagation(); toggleCheckIn(this, <?php echo $guest['guest_id']; ?>, <?php echo $roomNumber; ?>)"><?php echo $isAttended ? "Unmark\nCheck In" : "Check In"; ?></div>
                                
                                </div>
                                
                                <!-- выпадающее поле (под блоком) -->
                                <div class="guest-dropdown <?php echo empty($guestComments) ? 'no-comments' : ''; ?>" data-guest-id="<?php echo $guest['guest_id']; ?>">
                                    
                                    <button class="add-comment-btn" onclick="openModal(this, <?php echo $guest['guest_id']; ?>)">Add Comment</button>

                                    <?php foreach ($guestComments as $comment): ?>
                                    <div class="comment-item" data-comment-id="<?php echo $comment['comment_id']; ?>">
                                        <div class="comment-header">
                                            <div class="comment-info">
                                                <span class="comment-time"><?php echo formatDateTime($comment['created_at']); ?></span>
                                                <span class="comment-creator"><?php echo htmlspecialchars($comment['created_by'] ?? 'Unknown'); ?></span>
                                            </div>
                                            <div class="comment-actions">
                                                <button class="comment-btn edit-btn" onclick="editComment(this, <?php echo $comment['comment_id']; ?>)">Change</button>
                                                <button class="comment-btn delete-btn" onclick="deleteComment(this, <?php echo $comment['comment_id']; ?>)">Delete</button>
                                            </div>
                                        </div>
                                        <div class="comment-text"><?php echo htmlspecialchars($comment['comment']); ?></div>
                                    </div>
                                    <?php endforeach; ?>

                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </main>

        </div>
    </div>
    
    <!-- Модальное окно для добавления комментария -->
    <div id="commentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>New Comment</h2>
            </div>
            <div class="modal-body">
                <textarea id="commentText" placeholder="Enter your comment text..." rows="5"></textarea>
            </div>
            <div class="modal-footer">
                <button class="modal-btn cancel-btn" onclick="closeModal()">Cancel</button>
                <button class="modal-btn add-btn" onclick="saveComment()">Add Comment</button>
            </div>
        </div>
    </div>
    
    <script src="script.js"></script>
    <script>
        window.loginTime = <?php echo $login_time; ?>;
        window.sessionLifetime = <?php echo $session_lifetime; ?>;
        window.currentUser = '<?php echo htmlspecialchars($_SESSION['login']); ?>';
    </script>
</body>
</html>