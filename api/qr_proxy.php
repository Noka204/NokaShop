<?php
/**
 * QR Proxy — Ẩn thông tin ngân hàng và API VietQR khỏi frontend.
 */

// Debug log function
function qr_log($msg) {
    error_log("[QR_PROXY] " . $msg);
}

// Chỉ khởi tạo session nếu chưa có (phòng trường hợp gọi trực tiếp)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// Chỉ cho phép user đã đăng nhập
if (!isset($_SESSION['user_id'])) {
    qr_log("Unauthorized access attempt.");
    http_response_code(403);
    exit;
}

// --- Cấu hình ngân hàng ---
define('QR_BANK_CODE', 'mb');
define('QR_ACCOUNT_NO', '0353977178');
define('QR_ACCOUNT_NAME', 'TRAN NHU KHANH');
define('QR_TEMPLATE', 'compact2');

// --- Secret key cho mã hoá token ---
if (!defined('QR_SECRET')) {
    define('QR_SECRET', hash('sha256', 'NokaShop_QR_Secret_2024_!@#'));
}

/**
 * Tạo token mã hoá
 */
function qr_create_token($amount, $code) {
    $payload = json_encode(['a' => (int)$amount, 'c' => $code, 't' => time()]);
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($payload, 'AES-256-CBC', QR_SECRET, OPENSSL_RAW_DATA, $iv);
    return rtrim(strtr(base64_encode($iv . $encrypted), '+/', '-_'), '=');
}

/**
 * Giải mã token
 */
function qr_decode_token($token) {
    $raw = base64_decode(strtr($token, '-_', '+/'));
    if (!$raw || strlen($raw) < 17) return false;
    
    $iv = substr($raw, 0, 16);
    $encrypted = substr($raw, 16);
    $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', QR_SECRET, OPENSSL_RAW_DATA, $iv);
    if (!$decrypted) return false;
    
    $data = json_decode($decrypted, true);
    if (!$data || !isset($data['a']) || !isset($data['c'])) return false;
    
    // Token hết hạn sau 30 phút (tăng lên chút cho thoải mái)
    if (isset($data['t']) && (time() - $data['t']) > 1800) return false;
    
    return $data;
}

// --- Xử lý request ---
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'image';

if ($mode === 'token') {
    header('Content-Type: application/json');
    $amount = isset($_GET['amount']) ? (int)$_GET['amount'] : 0;
    $code = isset($_GET['code']) ? preg_replace('/[^A-Za-z0-9]/', '', $_GET['code']) : '';
    
    if ($amount < 2000 || empty($code)) {
        echo json_encode(['success' => false, 'error' => 'Invalid params']);
        exit;
    }
    
    echo json_encode(['success' => true, 'token' => qr_create_token($amount, $code)]);
    exit;
}

// Mode: Proxy ảnh QR
$token = isset($_GET['t']) ? $_GET['t'] : '';
if (empty($token)) {
    qr_log("Missing token payload.");
    http_response_code(400);
    exit;
}

$data = qr_decode_token($token);
if (!$data) {
    qr_log("Invalid or expired token: " . $token);
    http_response_code(403);
    exit;
}

$amount = $data['a'];
$code = $data['c'];

$qrUrl = sprintf(
    'https://img.vietqr.io/image/%s-%s-%s.png?amount=%d&addInfo=%s&accountName=%s',
    QR_BANK_CODE,
    QR_ACCOUNT_NO,
    QR_TEMPLATE,
    $amount,
    urlencode($code),
    urlencode(QR_ACCOUNT_NAME)
);

// Fetch ảnh qua CURL
$ch = curl_init($qrUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
$imageData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($httpCode !== 200 || !$imageData) {
    qr_log("CURL failure. HTTP: $httpCode. URL: $qrUrl");
    http_response_code(502);
    exit;
}

// Xoá buffer để tránh rác làm hỏng ảnh
if (ob_get_length()) ob_clean();

header('Content-Type: ' . ($contentType ?: 'image/png'));
header('Content-Length: ' . strlen($imageData));
header('Cache-Control: no-store, no-cache, must-revalidate');
echo $imageData;
exit;
