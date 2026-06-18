<?php
// Подключаем необходимые файлы
require_once 'config.php';
require_once 'encryption_key.php';

// Инициализация переменных
$login = '';
$error = '';

// Обработка POST-запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Валидация входных данных
    if (empty($login) || empty($password)) {
        $error = 'Пожалуйста, заполните все поля';
    } else {
        try {
            // Поиск пользователя по логину
            $stmt = $pdo->prepare("SELECT user_id, login, password, role FROM users WHERE login = :login");
            $stmt->execute([':login' => $login]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Расшифровываем пароль из БД
                $decryptedPassword = decryptPassword($user['password']);
                
                // Сравниваем с введенным паролем
                if ($password === $decryptedPassword) {
                    // Успешная авторизация - редирект
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $error = 'Неверный логин или пароль';
                }
            } else {
                $error = 'Неверный логин или пароль';
            }
        } catch (PDOException $e) {
            error_log("Authorization error: " . $e->getMessage());
            $error = 'Произошла ошибка при авторизации';
        }
    }
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
            <!-- Заголовок -->
            <div class="login-header">
                <h1>Restaurant Accounting</h1>
                <p>Please enter your credentials to continue</p>
            </div>
            
            <!-- Отображение ошибки -->
            <?php if (!empty($error)): ?>
                <div class="error-message" style="
                    background: rgba(217, 74, 74, 0.1);
                    border: 1px solid #d94a4a;
                    color: #d94a4a;
                    padding: 10px 16px;
                    border-radius: 1px;
                    margin-bottom: 20px;
                    font-size: 0.9rem;
                    text-align: center;
                ">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Форма авторизации -->
            <form method="POST" action="" class="login-form" id="loginForm" autocomplete="off">
                <!-- Поле логина -->
                <div class="form-group">
                    <label for="login" class="form-label">Login</label>
                    <input 
                        type="text" 
                        id="login" 
                        name="login" 
                        class="form-input <?php echo !empty($error) ? 'error' : ''; ?>"
                        placeholder="Enter your login"
                        value="<?php echo htmlspecialchars($login); ?>"
                        required
                        autofocus
                    >
                </div>
                
                <!-- Поле пароля -->
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input <?php echo !empty($error) ? 'error' : ''; ?>"
                            placeholder="Enter your password"
                            required
                        >
                    </div>
                </div>
                
                <!-- Кнопка входа -->
                <button type="submit" class="login-btn" id="loginBtn">Sign In</button>
            </form>
            
            <!-- Нижняя часть -->
            <div class="login-footer">
                <p>&copy; Hyatt Regency 2026</p>
            </div>
        </div>
    </div>
</body>
</html>