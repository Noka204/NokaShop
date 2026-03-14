<?php
/**
 * API kiểm tra xem có giao dịch nạp tiền mới thành công không
 * Dùng cho polling toàn trang
 */

// session_start(); // Already started in index.php
ob_start();
header('Content-Type: application/json');
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Auto-migration: Check if columns exist
try {
    $pdo->query("SELECT updated_at FROM payments LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE payments ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
}

try {
    $pdo->query("SELECT sepay_id FROM payments LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE payments ADD COLUMN sepay_id VARCHAR(100) DEFAULT NULL AFTER transaction_code");
}

// 0. Proactive Sync: Nếu user có đơn "đang chờ", ta chủ động hỏi SePay (giống các web lớn)
$stmt = $pdo->prepare("SELECT id FROM payments WHERE user_id = ? AND status = 'pending' LIMIT 1");
$stmt->execute([$user_id]);
if ($stmt->fetch()) {
    // Chỉ sync nếu đã trôi qua ít nhất 30 giây kể từ lần cập nhật cuối (để tránh spam API)
    // Hoặc đơn giản hơn là sync luôn mỗi khi polling nếu thấy cần thiết
    require_once 'api/sync_sepay_logic.php'; 
}

// 0.1 Tự động dọn dẹp (hủy) các đơn nạp tiền quá hạn (ví dụ > 10 phút)
try {
    $pdo->exec("UPDATE payments SET status = 'failed' WHERE status = 'pending' AND created_at < NOW() - INTERVAL 10 MINUTE");
} catch (Exception $e) {
    // Không làm gián đoạn luồng chính nếu lỗi dọn dẹp
}

try {
    // 1. Lấy số dư mới nhất
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    $current_balance = $user ? (float)$user['balance'] : 0;
    
    // Cập nhật session balance luôn
    $_SESSION['balance'] = $current_balance;

    // 2. Tìm giao dịch thành công mới nhất (trong vòng 60 giây qua)
    $stmt = $pdo->prepare("
        SELECT id, amount, transaction_code, created_at 
        FROM payments 
        WHERE user_id = ? 
        AND status = 'success' 
        AND updated_at >= NOW() - INTERVAL 1 MINUTE
        ORDER BY updated_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $latest_payment = $stmt->fetch();

    // 3. Kiểm tra còn đơn pending nào không (để frontend quyết định tiếp tục poll hay dừng)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);
    $pending_count = (int) $stmt->fetchColumn();

    ob_clean();
    echo json_encode([
        'success' => true,
        'balance' => $current_balance,
        'balance_formatted' => number_format($current_balance, 0, ',', '.') . ' VNĐ',
        'has_pending' => $pending_count > 0,
        'new_payment' => $latest_payment ? [
            'id' => $latest_payment['id'],
            'amount' => (float)$latest_payment['amount'],
            'amount_formatted' => number_format($latest_payment['amount'], 0, ',', '.') . ' VNĐ',
            'code' => $latest_payment['transaction_code']
        ] : null
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
