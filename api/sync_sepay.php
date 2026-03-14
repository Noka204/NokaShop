<?php
/**
 * API đồng bộ giao dịch SePay thủ công
 * Endpoint: ?route=api/sync-sepay
 */

ob_start();
header('Content-Type: application/json');
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Include logic đồng bộ dùng chung
require_once 'api/sync_sepay_logic.php';

ob_clean();
echo json_encode([
    'success' => true, 
    'message' => "Đã quét và xử lý giao dịch mới nhất.",
    'matched' => $matchedCount ?? 0
]);
exit;
