<?php
/**
 * Logic đồng bộ giao dịch SePay (Dùng chung cho cả manual sync và auto polling)
 * Yêu cầu: Đã có $pdo và $user_id (ID người dùng hiện tại)
 */

if (!isset($pdo) || !isset($user_id)) {
    return; // Tránh chạy độc lập
}

// 1. Gọi API SePay để lấy danh sách giao dịch mới nhất
$limit = 50;
$url = "https://my.sepay.vn/userapi/transactions/list?limit={$limit}";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . SEPAY_API_TOKEN,
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    $transactions = $data['transactions'] ?? [];

    foreach ($transactions as $tx) {
        $sepay_id = $tx['id'];
        $amount = (float)$tx['amount_in'];
        $content = $tx['transaction_content'];
        
        // Chỉ xử lý giao dịch tiền vào
        if ($amount <= 0) continue;

        // 2. Kiểm tra xem giao dịch này đã được xử lý chưa
        $stmt = $pdo->prepare("SELECT id FROM payments WHERE sepay_id = ? LIMIT 1");
        $stmt->execute([$sepay_id]);
        if ($stmt->fetch()) continue; 

        // 3. Logic khớp mã giao dịch (Cực kỳ quan trọng)
        $target_user_id = null;
        $payment_id = null;
        $foundCode = null;

        // Ưu tiên 1: Tìm mã NAPxxxxx trong nội dung
        if (preg_match('/(NAP\d{5,})/i', $content, $matches)) {
            $foundCode = strtoupper($matches[1]);
        } 
        
        // Ưu tiên 2: Trường 'code' từ SePay
        if (!$foundCode && !empty($tx['code'])) {
            $foundCode = strtoupper(trim($tx['code']));
        }

        if ($foundCode) {
            $stmt = $pdo->prepare("SELECT id, user_id FROM payments WHERE transaction_code = ? AND status = 'pending' LIMIT 1");
            $stmt->execute([$foundCode]);
            $pending = $stmt->fetch();
            if ($pending) {
                $target_user_id = $pending['user_id'];
                $payment_id = $pending['id'];
            }
        }

        // Ưu tiên 3: Fallback theo username (NAP <username>)
        if (!$target_user_id) {
            if (preg_match('/NAP\s*([a-zA-Z0-9_]{3,})/i', $content, $matches)) {
                $foundUsername = $matches[1];
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$foundUsername]);
                $u = $stmt->fetch();
                if ($u) {
                    $target_user_id = $u['id'];
                }
            }
        }

        // 4. Nếu khớp, cộng tiền
        if ($target_user_id) {
            $pdo->beginTransaction();
            try {
                $updateStmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $updateStmt->execute([$amount, $target_user_id]);

                if ($payment_id) {
                    $payStmt = $pdo->prepare("UPDATE payments SET amount = ?, status = 'success', payment_method = 'VietQR Sync', sepay_id = ?, updated_at = NOW() WHERE id = ?");
                    $payStmt->execute([$amount, $sepay_id, $payment_id]);
                } else {
                    $payStmt = $pdo->prepare("INSERT INTO payments (user_id, amount, payment_method, status, transaction_code, sepay_id) VALUES (?, ?, 'SePay Sync', 'success', ?, ?)");
                    $payStmt->execute([$target_user_id, $amount, $content, $sepay_id]);
                }
                $pdo->commit();
                
                // Trả về biến đếm cho file gọi
                if (!isset($matchedCount)) $matchedCount = 0;
                $matchedCount++;
            } catch (Exception $e) {
                $pdo->rollBack();
            }
        }
    }
}
