<?php
date_default_timezone_set('Asia/Yekaterinburg');

$host = 'localhost';
$port = '5432';
$dbname = 'hotel_breakfast';
$user = 'postgres';
$password = '';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET TIME ZONE 'Asia/Yekaterinburg'");
} catch(PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    die(json_encode(['success' => false, 'error' => 'Database connection failed']));
}
?>

