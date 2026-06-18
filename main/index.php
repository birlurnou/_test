<?php
// задаём временную зону (для корректной даты, времени посещения)
date_default_timezone_set('Asia/Yekaterinburg');

// подключаем файл с настройками для бд
require_once '../config/config.php';

// текущая дата в формате yyyy-mm-dd
$today = date('Y-m-d');

// AJAX запрос при отметке гостя
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // $data - получаем сырой json и создаём массив
    $data = json_decode(file_get_contents('php://input'), true);

    // проверяем существование и значение переменной action
    if (isset($data['action']) && $data['action'] === 'toggle_attended') {

        // получаем id гостя
        $guestId = $data['guest_id'];
        $roomNumber = $data['room_number'];
        
        // сначала получаем текущее состояние attended
        $stmt = $pdo->prepare("
            SELECT attended_date IS NOT NULL as is_attended
            FROM guests 
            WHERE guest_id = ?
        ");
        $stmt->execute([$guestId]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        $currentAttended = $current ? $current['is_attended'] : false;
        $newAttended = !$currentAttended;
        $attendedDate = $newAttended ? date('Y-m-d H:i:s') : null;
        
        // запрос на обновление таблицы (изменение attended_date)
        $stmt = $pdo->prepare("
            UPDATE guests 
            SET attended_date = :attended_date 
            WHERE guest_id = :guest_id
        ");
        $stmt->bindValue(':attended_date', $attendedDate, $attendedDate === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':guest_id', $guestId, PDO::PARAM_INT);
        $stmt->execute();
        
        // запрос, который выводит общее количество и количество отметившихся гостей за сегодня в комнате
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN g.attended_date IS NOT NULL THEN 1 END) as attended_count
            FROM guests g
            JOIN rooms r ON g.room_id = r.room_id
            WHERE r.room_number = ? AND DATE(g.created_at) = ?
        ");
        $stmt->execute([$roomNumber, $today]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // запрос, который получает обновлённые данные о госте
        $stmt = $pdo->prepare("
            SELECT name, guest_id, guest_type, birth_date, gender, 
                   attended_date IS NOT NULL as attended, attended_date
            FROM guests 
            WHERE guest_id = ?
        ");
        $stmt->execute([$guestId]);
        $guest = $stmt->fetch(PDO::FETCH_ASSOC);
        $guest['attended'] = (bool)$guest['attended'];
        $name = $guest['name'];
        // создаём массив json
        echo json_encode([
            'success' => true,
            'guest' => $guest,
            'total' => (int)$stats['total'],
            'attended_count' => (int)$stats['attended_count']
        ]);
        exit;
    }
}

// запрос, который выбирает уникальные комнаты, в которых есть гости, созданные сегодня
$stmt = $pdo->prepare("
    SELECT DISTINCT r.room_number, r.room_type
    FROM guests g
    JOIN rooms r ON g.room_id = r.room_id
    WHERE DATE(g.created_at) = ?
    ORDER BY r.room_number
");
$stmt->execute([$today]);
$rooms_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$rooms = [];
foreach ($rooms_list as $room_data) {
    $room_number = $room_data['room_number'];
    $room_type = $room_data['room_type'];

    // запрос, который выбирает гостей, проживающих в номере (созданных сегодня)
    $stmt = $pdo->prepare("
        SELECT name, guest_id, guest_type, birth_date, gender, 
               attended_date IS NOT NULL as attended, attended_date
        FROM guests 
        WHERE room_id = (SELECT room_id FROM rooms WHERE room_number = ?)
          AND DATE(created_at) = ?
        ORDER BY guest_type, birth_date
    ");
    $stmt->execute([$room_number, $today]);
    $guests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // преобразуем attended в bool
    foreach ($guests as $key => $guest) {
        $guests[$key]['attended'] = (bool)$guest['attended'];
    }
    
    // считаем общее количество гостей и отметившихся
    $total_guests = count($guests);
    $attended_guests = 0;
    foreach ($guests as $guest) {
        if ($guest['attended']) $attended_guests++;
    }
    
    // заполняем список
    $rooms[] = [
        'room_number' => $room_number,
        'room_type' => $room_type,
        'total_guests' => $total_guests,
        'attended_guests' => $attended_guests,
        'guests' => $guests
    ];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Accounting</title>
    <link href="https://fonts.googleapis.com/css2?family=B612:ital,wght@0,400;0,700;1,400;1,700&family=Cairo:wght@200..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="sticky-header">
        <div class="header-inner">
            <div class="logo">
                <h1>Hyatt Regency</h1>
            </div>
            <div class="search-compact">
                <input type="text" id="searchInput" placeholder="Room number ..." autocomplete="off">
            </div>
            <div class="today-compact">
                <?= date('d.m.Y') ?>
            </div>
        </div>
    </header>

    <main class="app">
        <div class="rooms-container" id="roomsContainer">
            <?php foreach ($rooms as $room): 
                $allChecked = ($room['attended_guests'] === $room['total_guests'] && $room['total_guests'] > 0);
                $completedClass = $allChecked ? 'completed' : '';
            ?>
                <div class="room-card" data-room="<?= htmlspecialchars($room['room_number']) ?>">
                    <div class="room-card-header">
                        <div class="room-main-info">
                            <div class="room-number">
                                <span class="number"><?= htmlspecialchars($room['room_number']) ?></span>
                            </div>
                            <div class="room-stats">
                                <div class="stat-badge status-stat <?= getStatusClass($room['room_type']) ?>">
                                    <span class="stat-value"><?= getStatusText($room['room_type']) ?></span>
                                </div>
                                <div class="stat-badge attendance-stat <?= $completedClass ?>">
                                    <span class="attended-count stat-value" data-total="<?= $room['total_guests'] ?>"><?= $room['attended_guests'] ?></span>
                                    <span class="stat-label">/ <?= $room['total_guests'] ?> checked in</span>
                                </div>
                            </div>
                        </div>
                        <div class="room-toggle">
                            <span class="toggle-icon">▼</span>
                        </div>
                    </div>
                    
                    <div class="guests-list" style="display: none;">
                        <?php foreach ($room['guests'] as $guest): 
                            $birthdayStatus = getBirthdayStatus($guest['birth_date']);
                        ?>
                            <div class="guest-card <?= $guest['attended'] ? 'attended' : '' ?>" 
                                 data-guest-id="<?= $guest['guest_id'] ?>" 
                                 data-room="<?= htmlspecialchars($room['room_number']) ?>"
                                 data-current-attended="<?= $guest['attended'] ? '1' : '0' ?>">
                                <div class="guest-avatar">
                                    <?= getAvatarIcon($guest['guest_type'], $guest['gender']) ?>
                                </div>
                                <div class="guest-info">
                                    <div class="guest-type <?= $guest['guest_type'] ?>">
                                        <?= $guest['guest_type'] === 'adult' ? 'Adult' : 'Child' ?>
                                    </div>
                                    <?php if ($birthdayStatus === 'exact'): ?>
                                        <div class="birthday-badge exact">Happy Birthday!</div>
                                    <?php elseif ($birthdayStatus === 'near'): 
                                        $birth = new DateTime($guest['birth_date']);
                                        $birthThisYear = new DateTime(date('Y') . '-' . $birth->format('m-d'));
                                        $diff = $todayDate = new DateTime();
                                        $interval = $todayDate->diff($birthThisYear);
                                        $diffDays = (int)$interval->format('%r%a');
                                        $absDiff = abs($diffDays);
                                        
                                        if ($diffDays > 0) {
                                            $daysText = "Birthday in $absDiff " . declensionNum($absDiff, ['day', 'days', 'days']);
                                        } elseif ($diffDays < 0) {
                                            $daysText = "Birthday was $absDiff " . declensionNum($absDiff, ['day', 'days', 'days']) . " ago";
                                        } else {
                                            $daysText = "Today!";
                                        }
                                    ?>
                                        <div class="birthday-badge near"><?= $daysText ?></div>
                                    <?php endif; ?>
                                    <div class="guest-name">
                                        <?= $guest['name'] ?>
                                    </div>
                                    <div class="guest-birth">
                                        <?= date('d.m.Y', strtotime($guest['birth_date'])) ?> (<?= calculateAge($guest['birth_date']) ?> years)
                                    </div>
                                    <?php if ($guest['attended'] && $guest['attended_date']): ?>
                                        <div class="guest-attended-time">
                                            Checked in: <?= date('H:i:s', strtotime($guest['attended_date'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="guest-status">
                                    <button class="check-btn <?= $guest['attended'] ? 'checked' : '' ?>">
                                        <span class="btn-text"><?= $guest['attended'] ? '✓ Checked In' : 'Check In' ?></span>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script src="script.js"></script>
</body>
</html>

<?php
// Функция склонения числительных
function declensionNum($num, $forms) {
    $num = abs($num) % 100;
    if ($num > 10 && $num < 20) return $forms[2];
    $num = $num % 10;
    if ($num > 1 && $num < 5) return $forms[1];
    if ($num == 1) return $forms[0];
    return $forms[2];
}

// Функция определения статуса дня рождения
function getBirthdayStatus($birthDate) {
    $today = new DateTime();
    $birth = new DateTime($birthDate);
    $currentYear = $today->format('Y');
    $birthThisYear = DateTime::createFromFormat('Y-m-d', $currentYear . '-' . $birth->format('m-d'));
    if (!$birthThisYear) {
        return null;
    }
    $diff = $today->diff($birthThisYear)->days;
    $sign = $today <=> $birthThisYear;
    
    if ($diff == 0) {
        return 'exact';
    }
    if (($sign === -1 && $diff <= 3) || ($sign === 1 && $diff <= 3)) {
        return 'near';
    }
    return null;
}

function calculateAge($birthDate) {
    $today = new DateTime();
    $birth = new DateTime($birthDate);
    return $today->diff($birth)->y;
}

function getStatusClass($roomType) {
    return match($roomType) {
        'standard' => 'status-standard',
        'club'     => 'status-club',
        'deluxe'   => 'status-deluxe',
        'luxe'     => 'status-luxe',
        default    => 'status-standard'
    };
}

function getStatusText($roomType) {
    return match($roomType) {
        'standard' => 'Standard',
        'club'     => 'Club',
        'deluxe'   => 'Deluxe',
        'luxe'     => 'Luxe',
        default    => 'Standard'
    };
}

function getAvatarIcon($guestType, $gender) {
    if ($guestType === 'adult') {
        if ($gender === 'male') return '🧑🏻';
        elseif ($gender === 'female') return '👩🏻';
        else return '👤';
    } else {
        if ($gender === 'male') return '👦🏻';
        elseif ($gender === 'female') return '👧🏻';
        else return '👶🏻';
    }
}
?>