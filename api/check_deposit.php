<?php
/**
 * API check status nạp tiền
 * Endpoint: ?route=api/check-deposit&code=NAP12345
 */

ob_start();
header('Content-Type: application/json');

if (!isset($_GET['code'])) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Thiếu mã cần kiểm tra']);
    exit;
}

$code = trim($_GET['code']);

try {
    // Kiểm tra trong bảng payments xem mã này đã Success chưa
    $stmt = $pdo->prepare("SELECT status, amount FROM payments WHERE transaction_code = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$code]);
    $payment = $stmt->fetch();

    ob_clean();
    if ($payment) {
        echo json_encode([
            'status' => 'success',
            'payment_status' => $payment['status'],
            'amount' => (float)$payment['amount'],
            'has_record' => true
        ]);
    } else {
        echo json_encode([
            'status' => 'success',
            'payment_status' => 'not_found',
            'has_record' => false
        ]);
    }

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
