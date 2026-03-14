<?php
// api/cart_api.php — AJAX Cart Operations
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

switch ($action) {

    // ===== THÊM VÀO GIỎ =====
    case 'add':
        $product_id = (int)($_POST['product_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));

        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ']);
            exit;
        }

        // Fetch product from DB
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.status = 'active'");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại hoặc đã ngừng bán']);
            exit;
        }

        if ($product['stock'] <= 0) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm đã hết hàng']);
            exit;
        }

        // Check if already in cart
        if (isset($_SESSION['cart'][$product_id])) {
            $new_qty = $_SESSION['cart'][$product_id]['quantity'] + $quantity;
            if ($new_qty > $product['stock']) {
                echo json_encode(['success' => false, 'message' => 'Số lượng vượt quá kho (còn ' . $product['stock'] . ')']);
                exit;
            }
            $_SESSION['cart'][$product_id]['quantity'] = $new_qty;
        } else {
            if ($quantity > $product['stock']) {
                echo json_encode(['success' => false, 'message' => 'Số lượng vượt quá kho (còn ' . $product['stock'] . ')']);
                exit;
            }
            $_SESSION['cart'][$product_id] = [
                'name' => $product['name'],
                'price' => (float)$product['price'],
                'quantity' => $quantity,
                'category' => $product['category_name'],
                'image' => $product['image'] ?? '',
                'stock' => (int)$product['stock']
            ];
        }

        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm "' . $product['name'] . '" vào giỏ hàng',
            'cart_count' => array_sum(array_column($_SESSION['cart'], 'quantity')),
            'cart_total' => getCartTotal()
        ]);
        break;

    // ===== CẬP NHẬT SỐ LƯỢNG =====
    case 'update':
        $product_id = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);

        if (!isset($_SESSION['cart'][$product_id])) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không có trong giỏ']);
            exit;
        }

        // Check stock
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ? AND status = 'active'");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product) {
            unset($_SESSION['cart'][$product_id]);
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không còn khả dụng, đã xóa khỏi giỏ']);
            exit;
        }

        if ($quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa sản phẩm khỏi giỏ',
                'removed' => true,
                'cart_count' => array_sum(array_column($_SESSION['cart'], 'quantity')),
                'cart_total' => getCartTotal(),
                'cart_items' => buildCartData()
            ]);
            exit;
        }

        if ($quantity > $product['stock']) {
            echo json_encode(['success' => false, 'message' => 'Số lượng vượt quá kho (còn ' . $product['stock'] . ')']);
            exit;
        }

        $_SESSION['cart'][$product_id]['quantity'] = $quantity;

        $item_total = $_SESSION['cart'][$product_id]['price'] * $quantity;

        echo json_encode([
            'success' => true,
            'message' => 'Đã cập nhật số lượng',
            'item_total' => format_currency($item_total),
            'cart_count' => array_sum(array_column($_SESSION['cart'], 'quantity')),
            'cart_total' => getCartTotal(),
            'cart_items' => buildCartData()
        ]);
        break;

    // ===== XÓA SẢN PHẨM =====
    case 'remove':
        $product_id = (int)($_POST['product_id'] ?? 0);

        if (isset($_SESSION['cart'][$product_id])) {
            $name = $_SESSION['cart'][$product_id]['name'];
            unset($_SESSION['cart'][$product_id]);
            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa "' . $name . '" khỏi giỏ',
                'cart_count' => array_sum(array_column($_SESSION['cart'], 'quantity')),
                'cart_total' => getCartTotal(),
                'cart_items' => buildCartData()
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không có trong giỏ']);
        }
        break;

    // ===== XÓA TOÀN BỘ GIỎ =====
    case 'clear':
        $_SESSION['cart'] = [];
        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa toàn bộ giỏ hàng',
            'cart_count' => 0,
            'cart_total' => '0 VNĐ'
        ]);
        break;

    // ===== THANH TOÁN GIỎ =====
    case 'checkout':
        if (empty($_SESSION['cart'])) {
            echo json_encode(['success' => false, 'message' => 'Giỏ hàng trống']);
            exit;
        }

        $total = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        // Apply promo discount
        $promo_discount = 0;
        $applied_promo = $_SESSION['applied_promo'] ?? null;
        if ($applied_promo) {
            $promo_discount = (float)$applied_promo['discount'];
            // Re-validate min_order just in case cart changed
            if ($total >= $applied_promo['min_order']) {
                $total = max(0, $total - $promo_discount);
            } else {
                $applied_promo = null;
                $promo_discount = 0;
                unset($_SESSION['applied_promo']);
            }
        }

        if ($_SESSION['balance'] < $total) {
            echo json_encode([
                'success' => false,
                'message' => 'Số dư không đủ. Cần ' . format_currency($total) . ', hiện có ' . format_currency($_SESSION['balance'])
            ]);
            exit;
        }

        $orders_result = [];
        // We need to track the first order ID to attach the promo usage
        $first_order_id = null;

        try {
            $pdo->beginTransaction();

            // Trừ tiền tổng (race-condition safe) - We do it once for the whole cart
            $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?");
            $stmt->execute([$total, $_SESSION['user_id'], $total]);
            if ($stmt->rowCount() === 0) {
                throw new Exception('Số dư không đủ');
            }
            $_SESSION['balance'] -= $total;

            foreach ($_SESSION['cart'] as $product_id => $item) {
                $qty = $item['quantity'];
                $item_total = $item['price'] * $qty;

                // Trừ kho (race-condition safe)
                $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
                $stmt->execute([$qty, $product_id, $qty]);
                if ($stmt->rowCount() === 0) {
                    throw new Exception('Sản phẩm "' . $item['name'] . '" đã hết hàng');
                }

                // Fetch product for key generation
                $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();

                // Tạo đơn hàng
                $stmt = $pdo->prepare("INSERT INTO orders (user_id, product_id, quantity, total_price, input_data, status) VALUES (?, ?, ?, ?, ?, 'completed')");
                $stmt->execute([$_SESSION['user_id'], $product_id, $qty, $item_total, '']);
                $order_id = $pdo->lastInsertId();
                if (!$first_order_id) $first_order_id = $order_id;

                // Generate key
                require_once 'includes/key_generator.php';
                $catName = isset($product['category_name']) ? strtolower($product['category_name']) : '';
                $prodName = strtolower($product['name']);
                $keyType = false;

                // Parse custom days from product name
                $customDays = 30; // Mặc định
                if (strpos($prodName, '7 days') !== false || strpos($prodName, '7 ngày') !== false) {
                    $customDays = 7;
                } elseif (strpos($prodName, '1 month') !== false || strpos($prodName, '1 tháng') !== false || strpos($prodName, '30 days') !== false || strpos($prodName, '30 ngày') !== false) {
                    $customDays = 30;
                }

                if (strpos($catName, 'ngưng') !== false || strpos($catName, 'ngung') !== false || strpos($prodName, 'ngưng') !== false || strpos($prodName, 'ngung') !== false || strpos($prodName, 'dll discord') !== false || strpos($catName, 'dll discord') !== false) {
                    $keyType = 'ngungdong';
                } elseif (strpos($catName, 'panel') !== false || strpos($prodName, 'panel') !== false || strpos($prodName, 'aimbot') !== false) {
                    $keyType = 'panel';
                } elseif (strpos($catName, 'fixlag') !== false || strpos($prodName, 'fixlag') !== false) {
                    $keyType = 'fixlag';
                } elseif (strpos($prodName, 'fakefps') !== false || strpos($prodName, 'fake fps') !== false || strpos($prodName, 'fps') !== false) {
                    $keyType = 'fakefps';
                } elseif (strpos($prodName, 'mạng cali') !== false || strpos($prodName, 'mang cali') !== false || strpos($prodName, 'mangcali') !== false || strpos($prodName, 'cali') !== false) {
                    $keyType = 'mangcali';
                }

                $usernamePrefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $_SESSION['username']), 0, 4));
                $genResult = false;
                if ($keyType) {
                    $genResult = KeyGenerator::generateAndPushKey($keyType, $usernamePrefix, $customDays);
                }

                if ($genResult) {
                    if ($keyType === 'panel') {
                        $finalKey = "Tài khoản: " . $genResult['username'] . " | Mật khẩu: " . $genResult['password'];
                    } else {
                        $finalKey = $genResult['key'];
                    }
                } else {
                    $finalKey = 'NOKA-' . strtoupper(bin2hex(random_bytes(4))) . '-' . strtoupper(bin2hex(random_bytes(4)));
                }

                $stmtUpdate = $pdo->prepare("UPDATE orders SET input_data = ? WHERE id = ?");
                $stmtUpdate->execute([$finalKey, $order_id]);

                $orders_result[] = [
                    'order_id' => $order_id,
                    'name' => $item['name'],
                    'quantity' => $qty,
                    'total' => format_currency($item_total),
                    'key' => $finalKey,
                    'link' => !empty($product['download_link']) ? $product['download_link'] : ''
                ];
            }

            // Record promo usage
            if ($applied_promo && $promo_discount > 0 && $first_order_id) {
                $pdo->prepare("INSERT INTO promotion_uses (promotion_id, user_id, order_id, discount_amount) VALUES (?, ?, ?, ?)")
                    ->execute([$applied_promo['id'], $_SESSION['user_id'], $first_order_id, $promo_discount]);
                $pdo->prepare("UPDATE promotions SET used_count = used_count + 1 WHERE id = ?")
                    ->execute([$applied_promo['id']]);
                unset($_SESSION['applied_promo']);
            }

            $pdo->commit();

            // Clear cart after success
            $_SESSION['cart'] = [];

            echo json_encode([
                'success' => true,
                'message' => 'Thanh toán thành công!',
                'orders' => $orders_result,
                'new_balance' => format_currency($_SESSION['balance']),
                'cart_count' => 0
            ]);

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Cart checkout error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ===== LẤY DỮ LIỆU GIỎ =====
    case 'get':
        echo json_encode([
            'success' => true,
            'cart_count' => array_sum(array_column($_SESSION['cart'], 'quantity')),
            'cart_total' => getCartTotal(),
            'cart_items' => buildCartData()
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
}

// ===== HELPER FUNCTIONS =====
function getCartTotal() {
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return format_currency($total);
}

function getCartTotalRaw() {
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

function buildCartData() {
    $items = [];
    foreach ($_SESSION['cart'] as $id => $item) {
        $items[] = [
            'id' => $id,
            'name' => $item['name'],
            'price' => format_currency($item['price']),
            'price_raw' => $item['price'],
            'quantity' => $item['quantity'],
            'category' => $item['category'],
            'total' => format_currency($item['price'] * $item['quantity'])
        ];
    }
    return $items;
}
?>
