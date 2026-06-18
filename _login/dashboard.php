<?php
// dashboard.php - временная страница после успешной авторизации
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Restaurant Accounting</title>
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
        }
        .dashboard-card h1 {
            color: #5B5778;
            font-size: 2rem;
            margin-bottom: 15px;
        }
        .dashboard-card p {
            color: #9D9AAE;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-card">
        <h1>✅ Авторизация успешна!</h1>
        <p>Добро пожаловать в систему учета ресторана</p>
        <p style="margin-top: 20px; font-size: 0.9rem; color: #5B5778;">
            Временная страница (сессии еще не настроены)
        </p>
    </div>
</body>
</html>