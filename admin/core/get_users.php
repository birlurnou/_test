<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/encryption_key.php';

header('Content-Type: application/json');

// получение поискового запроса
$search = isset($_POST['search']) ? trim($_POST['search']) : '';

try {
    $sql = "SELECT user_id, login, role FROM users";
    $params = [];
    
    // если есть поисковый запрос, добавляем условие LIKE
    if (!empty($search)) {
        $sql .= " WHERE login ILIKE :search"; // ILIKE для регистронезависимого поиска в PostgreSQL
        $params[':search'] = '%' . $search . '%';
    }
    
    $sql .= " ORDER BY login ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
    
} catch (PDOException $e) {
    error_log("Error loading users: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}