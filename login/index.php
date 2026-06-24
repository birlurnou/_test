<?php
// login/index.php
session_start();

// Если пользователь уже авторизован - перенаправляем
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if ($_SESSION['role'] === 'user') {
        header('Location: ../main/user.php');
    } elseif (in_array($_SESSION['role'], ['manager', 'admin'])) {
        header('Location: ../processing/index.php');
    }
    exit();
}

// Проверяем параметры
$expired = isset($_GET['expired']) && $_GET['expired'] == 1;
$logout = isset($_GET['logout']) && $_GET['logout'] == 1;

// Если сессия истекла или вышел - показываем сообщение
if ($expired) {
    $message = 'Сессия истекла. Пожалуйста, войдите заново.';
    $message_type = 'warning';
} elseif ($logout) {
    $message = 'Вы успешно вышли из системы.';
    $message_type = 'success';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authorization - Restaurant Accounting</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- заголовок -->
            <div class="login-header">
                <h1>Restaurant Accounting</h1>
                <p>Please enter your credentials to continue</p>
            </div>
            
            <!-- форма авторизации -->
            <form id="loginForm" class="login-form" autocomplete="off">
                <!-- поле логина -->
                <div class="form-group">
                    <label for="login" class="form-label">Login</label>
                    <input 
                        type="text" 
                        id="login" 
                        name="login" 
                        class="form-input" 
                        placeholder="Enter your login"
                        
                        autofocus
                    >
                </div>
                
                <!-- поле пароля -->
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="Enter your password"
                            
                        >
                    </div>
                </div>
                
                <!-- кнопка входа -->
                <button type="submit" class="login-btn" id="loginBtn">Sign In</button>
            </form>
            
            <!-- нижняя часть -->
            <div class="login-footer">
                <p>&copy; Hyatt Regency 2026</p>
            </div>
        </div>
    </div>
    
    <script src="script.js"></script>
</body>
</html>