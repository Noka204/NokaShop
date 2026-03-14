<?php
// config/database.php

// Define base URL for absolute linking
define('BASE_URL', '/'); // Sau khi có .htaccess thì dùng '/' cho chuyên nghiệp

// Load env variables
require_once __DIR__ . '/env.php';

// SePay Credentials
define('SEPAY_MERCHANT_ID', env('SEPAY_MERCHANT_ID', ''));
define('SEPAY_API_TOKEN', env('SEPAY_API_TOKEN', ''));
define('SEPAY_WEBHOOK_KEY', env('SEPAY_WEBHOOK_KEY', ''));

// Database connection settings
$host = env('DB_HOST', 'localhost');
$dbname = env('DB_NAME', '');
$user = env('DB_USER', '');
$pass = env('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // Bảo mật: Dùng real prepared statements
} catch (PDOException $e) {
    // Không hiển thị chi tiết lỗi DB cho người dùng
    error_log('Database connection error: ' . $e->getMessage());
    die('Hệ thống đang bảo trì, vui lòng thử lại sau.');
}
?>