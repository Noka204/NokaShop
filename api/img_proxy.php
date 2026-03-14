<?php
/**
 * Product Image Proxy
 */

// Chỉ khởi tạo session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$encoded_url = isset($_GET['url']) ? $_GET['url'] : '';

if (empty($encoded_url)) {
    http_response_code(400);
    exit;
}

// Giải mã URL (hỗ trợ cả base64 chuẩn và base64url)
$url = base64_decode(strtr($encoded_url, '-_', '+/'));

if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    exit;
}

// Chặn các URL nội bộ nhạy cảm (SSRF protection)
$host = parse_url($url, PHP_URL_HOST);
if (in_array($host, ['localhost', '127.0.0.1', '192.168.1.1'])) {
    http_response_code(403);
    exit;
}

// Fetch remote image
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

$imageData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($httpCode !== 200 || !$imageData) {
    // Trả về một ảnh placeholder nếu không fetch được
    header('Content-Type: image/svg+xml');
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200"><rect width="100%" height="100%" fill="#f1f5f9"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#94a3b8" font-family="sans-serif">Image Error</text></svg>';
    exit;
}

if (ob_get_length()) ob_clean();

header('Content-Type: ' . ($contentType ?: 'image/jpeg'));
header('Content-Length: ' . strlen($imageData));
header('Cache-Control: public, max-age=86400'); // Cache 24h
echo $imageData;
exit;
