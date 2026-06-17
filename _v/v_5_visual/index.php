<?php

require_once 'encryption_key.php';
require_once 'config.php';

function loadUsers($pdo, $conditions = []) {
    try {
        $sql = "SELECT user_id, login, password, role FROM users";
        $params = [];
        
        // выполняем запрос
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // получаем все записи
        $users = $stmt->fetchAll();
        
        // расшифровываем пароли (если нужно)
        foreach ($users as &$user) {
            if (isset($user['password'])) {
                $user['password'] = decryptPassword($user['password']);
            }
        }
        
        return $users;
        
    } catch(PDOException $e) {
        error_log("Error loading users: " . $e->getMessage());
        return [];
    }
}

$users = loadUsers($pdo);
foreach ($users as $user):
    echo ('<pre>');
    print_r($user);
endforeach;

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
                    <button class="add-btn">Create New User</button>
                    <div class="center-text">
                        <h2>Admin Panel</h2>
                        <p>User Management</p>
                    </div>
                    <button class="go-btn">Guest Management</button>
                </div>
            </header>

            <!-- основной контент -->
            <main class="content">

                <!-- строка поиска -->
                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Search users...">
                </div>

                <!-- строка с заголовками -->
                <div class="table-header">
                    <div class="col-name">Login</div>
                    <div class="col-role">Role</div>
                    <div class="col-actions">Actions</div>
                </div>

                <!-- список пользователей -->
                <div class="users-list">
                    <?php foreach ($users as $user): ?>
                        <div class="user-row">
                            <div class="col-name"><?php echo htmlspecialchars($user['login']); ?></div>
                            <div class="col-role"><?php echo htmlspecialchars($user['role']); ?></div>
                            <div class="col-actions">
                                <button class="action-btn edit-btn">Change</button>
                                <button class="action-btn copy-btn">Copy</button>
                                <button class="action-btn delete-btn">Delete</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </main>
        </div>
    </div>
</body>
</html>