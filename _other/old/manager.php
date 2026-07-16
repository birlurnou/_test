<?php
// manager.php - страница для менеджеров
session_start();

// Проверка авторизации
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

// Проверка роли
if (!in_array($_SESSION['role'], ['manager', 'admin'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Panel - Restaurant Accounting</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: #9D9AAE;
            background: url('assets/images/background.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .dashboard-card {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(12px);
            border-radius: 1px;
            padding: 60px 50px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
            max-width: 500px;
            width: 100%;
        }
        .dashboard-card h1 {
            color: #5B5778;
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .dashboard-card .role-badge {
            display: inline-block;
            background: #2e7d32;
            color: white;
            padding: 5px 20px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .dashboard-card p {
            color: #9D9AAE;
            font-size: 1.1rem;
            margin-bottom: 10px;
        }
        .dashboard-card .user-info {
            color: #5B5778;
            font-size: 0.95rem;
            margin-bottom: 30px;
        }
        .dashboard-card .manager-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 30px;
        }
        .dashboard-card .manager-actions button {
            padding: 10px 20px;
            background: #5B5778;
            color: white;
            border: none;
            border-radius: 1px;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .dashboard-card .manager-actions button:hover {
            background: #4a4762;
            transform: scale(1.02);
        }
        .logout-btn {
            padding: 12px 30px;
            background: #d94a4a;
            color: white;
            border: none;
            border-radius: 1px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .logout-btn:hover {
            background: #c0392b;
            transform: scale(1.02);
            box-shadow: 0 4px 16px rgba(217, 74, 74, 0.3);
        }
        .logout-btn:active {
            transform: scale(0.98);
        }
    </style>
</head>
<body>
    <div class="dashboard-card">
        <h1>📊 Manager Panel</h1>
        <div class="role-badge"><?php echo htmlspecialchars($_SESSION['role']); ?></div>
        <p>Добро пожаловать в панель управления</p>
        <div class="user-info">
            Вы вошли как: <strong><?php echo htmlspecialchars($_SESSION['login']); ?></strong>
        </div>
        
        <div class="manager-actions">
            <button onclick="alert('Функция в разработке')">📋 Управление заказами</button>
            <button onclick="alert('Функция в разработке')">👥 Управление сотрудниками</button>
            <button onclick="alert('Функция в разработке')">📊 Отчеты и статистика</button>
        </div>
        
        <button class="logout-btn" onclick="confirmLogout()">Выйти</button>
    </div>

    <script>
        function confirmLogout() {
            if (confirm('Вы уверены, что хотите выйти из системы?')) {
                window.location.href = 'logout.php';
            }
        }
    </script>
</body>
</html>