<?php
/**
 * API update amount cho payment đang chờ
 * Endpoint: ?route=api/update-amount&code=NAP12345&amount=50000
 */

// Ensure no accidental output breaks JSON
ob_start();

header('Content-Type: application/json');
require_once 'config/database.php';

if (!isset($_GET['code']) || !isset($_GET['amount'])) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Thiếu thông tin']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Chưa đăng nhập']);
    exit;
}

$code = $_GET['code'];
$amount = (int) $_GET['amount'];

if ($amount < 2000) {
    echo json_encode(['status' => 'error', 'message' => 'Số tiền không hợp lệ']);
    exit;
}

try {
    // Check if record exists
    $stmt = $pdo->prepare("SELECT id FROM payments WHERE transaction_code = ? AND status = 'pending' LIMIT 1");
    $stmt->execute([$code]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update amount
        $stmt = $pdo->prepare("UPDATE payments SET amount = ? WHERE id = ?");
        $stmt->execute([$amount, $existing['id']]);
    } else {
        // Insert new pending record
        $stmt = $pdo->prepare("INSERT INTO payments (user_id, amount, payment_method, transaction_code, status) VALUES (?, ?, 'VietQR', ?, 'pending')");
        $stmt->execute([$_SESSION['user_id'], $amount, $code]);
    }

    ob_clean();
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    ob_clean();
    error_log('update_amount error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống, vui lòng thử lại.']);
}
