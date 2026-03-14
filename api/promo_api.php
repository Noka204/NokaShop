<?php
// api/promo_api.php — AJAX endpoint for applying promo codes
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'apply') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $total = (float)($_POST['total'] ?? 0);

    if (empty($code)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mã khuyến mãi.']);
        exit;
    }

    // Find promo
    $stmt = $pdo->prepare("SELECT * FROM promotions WHERE code = ? AND status = 'active'");
    $stmt->execute([$code]);
    $promo = $stmt->fetch();

    if (!$promo) {
        echo json_encode(['success' => false, 'message' => 'Mã khuyến mãi không tồn tại hoặc đã hết hạn.']);
        exit;
    }

    // Check expiry
    if ($promo['starts_at'] && strtotime($promo['starts_at']) > time()) {
        echo json_encode(['success' => false, 'message' => 'Mã khuyến mãi chưa có hiệu lực.']);
        exit;
    }
    if ($promo['expires_at'] && strtotime($promo['expires_at']) < time()) {
        echo json_encode(['success' => false, 'message' => 'Mã khuyến mãi đã hết hạn.']);
        exit;
    }

    // Check max uses
    if ($promo['max_uses'] > 0 && $promo['used_count'] >= $promo['max_uses']) {
        echo json_encode(['success' => false, 'message' => 'Mã khuyến mãi đã hết lượt sử dụng.']);
        exit;
    }

    // Check min order
    if ($total < $promo['min_order']) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm/Đơn hàng không hợp lệ! Mệnh giá tối thiểu để dùng mã này là ' . format_currency($promo['min_order'])]);
        exit;
    }

    // Check if user already used this promo
    $stmt = $pdo->prepare("SELECT id FROM promotion_uses WHERE promotion_id = ? AND user_id = ?");
    $stmt->execute([$promo['id'], $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Bạn đã sử dụng mã này rồi.']);
        exit;
    }

    // Calculate discount
    if ($promo['type'] === 'percent') {
        $discount = $total * ($promo['value'] / 100);
    } else {
        $discount = $promo['value'];
    }

    // Discount cannot exceed total
    if ($discount > $total) {
        $discount = $total;
    }

    $new_total = $total - $discount;

    // Store in session for checkout
    $_SESSION['applied_promo'] = [
        'id' => $promo['id'],
        'code' => $promo['code'],
        'type' => $promo['type'],
        'value' => $promo['value'],
        'discount' => $discount,
        'min_order' => $promo['min_order']
    ];

    echo json_encode([
        'success' => true,
        'message' => 'Áp dụng mã thành công! Giảm ' . format_currency($discount),
        'discount' => format_currency($discount),
        'discount_raw' => $discount,
        'new_total' => format_currency($new_total),
        'new_total_raw' => $new_total,
        'promo_code' => $promo['code'],
        'promo_type' => $promo['type'],
        'promo_value' => $promo['value'],
    ]);
    exit;

} elseif ($action === 'remove') {
    unset($_SESSION['applied_promo']);
    echo json_encode([
        'success' => true,
        'message' => 'Đã hủy mã khuyến mãi.',
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
