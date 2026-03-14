<?php
/**
 * SePay Webhook / IPN Handler
 * Endpoint: ?route=api/sepay
 */

// Ensure no accidental output breaks JSON
ob_start();

header('Content-Type: application/json');

// 1. Nhận dữ liệu từ SePay gửi qua POST (JSON)
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// LOGGING: Ghi log an toàn vào error log của server (không public)
error_log("[SePay Webhook] " . date('Y-m-d H:i:s') . " PAYLOAD: " . $input);

if (!$data) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

// Auto-migration: Check if 'sepay_id' column exists in payments table
try {
    $pdo->query("SELECT sepay_id FROM payments LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE payments ADD COLUMN sepay_id VARCHAR(100) DEFAULT NULL AFTER transaction_code");
}

// 2. Kiểm tra bảo mật (API Key)
// SePay gửi: Authorization: Apikey <Your_API_Key>
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
$token = trim(str_replace('Apikey', '', $authHeader));

if ($token !== SEPAY_WEBHOOK_KEY) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Xác thực thất bại']);
    exit;
}

// 3. Phân tích dữ liệu giao dịch (Theo docs SePay)
$amount = (float) ($data['transferAmount'] ?? 0);
$content = $data['content'] ?? '';
$transaction_id = $data['id'] ?? '';
$transfer_type = $data['transferType'] ?? 'in';

if ($amount <= 0 || empty($content) || $transfer_type !== 'in') {
    ob_clean();
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Giao dịch bỏ qua']);
    exit;
}

// 4. Tìm người dùng từ mã giao dịch (Thử nhiều cách để tăng tỉ lệ khớp)
$user_id = null;
$payment_id = null;
$foundCode = null;

// Ưu tiên 1: Tìm mã NAPxxxxx trong nội dung chuyển khoản
if (preg_match('/(NAP\d{5,})/i', $content, $matches)) {
    $foundCode = strtoupper($matches[1]);
}

// Ưu tiên 2: Nếu không thấy trong nội dung, thử dùng trường 'code' từ SePay (nếu có)
if (!$foundCode && !empty($data['code'])) {
    $foundCode = strtoupper(trim($data['code']));
}

if ($foundCode) {
    $stmt = $pdo->prepare("SELECT id, user_id FROM payments WHERE transaction_code = ? AND status = 'pending' LIMIT 1");
    $stmt->execute([$foundCode]);
    $pendingPayment = $stmt->fetch();

    if ($pendingPayment) {
        $user_id = $pendingPayment['user_id'];
        $payment_id = $pendingPayment['id'];
    }
}

// Ưu tiên 3: Fallback theo username (NAP <username> hoặc NAP<username>)
if (!$user_id) {
    if (preg_match('/NAP\s*([a-zA-Z0-9_]{3,})/i', $content, $matches)) {
        $foundUsername = $matches[1];
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$foundUsername]);
        $u = $stmt->fetch();
        if ($u) {
            $user_id = $u['id'];
        }
    }
}

if (!$user_id) {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Giao dịch bỏ qua (Không tìm thấy user/mã)']);
    exit;
}

// 5. Cập nhật cơ sở dữ liệu
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user) {
        $updateStmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $updateStmt->execute([$amount, $user_id]);

        if ($payment_id) {
            $payStmt = $pdo->prepare("UPDATE payments SET amount = ?, status = 'success', payment_method = 'VietQR', sepay_id = ?, updated_at = NOW() WHERE id = ?");
            $payStmt->execute([$amount, $transaction_id, $payment_id]);
        } else {
            $payStmt = $pdo->prepare("INSERT INTO payments (user_id, amount, payment_method, status, transaction_code, sepay_id) VALUES (?, ?, 'SePay Auto', 'success', ?, ?)");
            $payStmt->execute([$user_id, $amount, $content, $transaction_id]);
        }

        $pdo->commit();
        ob_clean();
        echo json_encode(['success' => true, 'message' => 'Nạp tiền thành công']);
    } else {
        $pdo->rollBack();
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }

} catch (Exception $e) {
    $pdo->rollBack();
    ob_clean();
    http_response_code(200); // Trả về 200 để tránh SePay retry vô hạn nếu lỗi logic
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
