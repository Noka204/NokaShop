<?php
/**
 * Router cho PHP Built-in Server (php -S)
 * 
 * PHP built-in server không hỗ trợ .htaccess, nên cần file này
 * để điều hướng tất cả request qua index.php
 * 
 * Dùng: php -S localhost:8000 router.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Nếu là file thật (CSS, JS, hình ảnh...) → phục vụ trực tiếp
if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false; // PHP built-in server sẽ tự phục vụ file tĩnh
}

// Tất cả request khác → chuyển qua index.php
require __DIR__ . '/index.php';
