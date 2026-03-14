<?php
// views/user/buy.php — Chi tiết sản phẩm & Mua hàng
$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.status != 'hidden'");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    require 'views/layout/dashboard_header.php';
    echo "<div class='alert alert-danger'>Sản phẩm không tồn tại. <a href='" . BASE_URL . "cua-hang' class='alert-link'>Quay lại</a></div>";
    require 'views/layout/dashboard_footer.php';
    exit;
}

// Xử lý mua hàng
$purchase_result = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $quantity = 1;
    $total_price = $product['price'] * $quantity;

    // Apply promo discount if present in session
    $promo_discount = 0;
    $applied_promo = $_SESSION['applied_promo'] ?? null;
    if ($applied_promo && !empty($_POST['promo_code'])) {
        $promo_discount = (float)$applied_promo['discount'];
        $total_price = max(0, $total_price - $promo_discount);
    }

    if ($product['status'] === 'out_of_stock' || $product['stock'] <= 0) {
        $error = "Gói dịch vụ này hiện đang hết hàng.";
    } elseif ($_SESSION['balance'] < $total_price) {
        $error = "Số dư không đủ! <a href='" . BASE_URL . "nap-tien' class='fw-bold'>Nạp thêm tiền</a>";
    } else {
        try {
            $pdo->beginTransaction();

            // Trừ kho (race-condition safe)
            $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
            $stmt->execute([$quantity, $product_id, $quantity]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Sản phẩm vừa hết hàng');
            }

            // Trừ tiền (race-condition safe)
            $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?");
            $stmt->execute([$total_price, $_SESSION['user_id'], $total_price]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Số dư không đủ');
            }

            $_SESSION['balance'] -= $total_price;

            // Tạo đơn hàng
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, product_id, quantity, total_price, input_data, status) VALUES (?, ?, ?, ?, ?, 'completed')");
            $stmt->execute([$_SESSION['user_id'], $product_id, $quantity, $total_price, '']);

            $order_id = $pdo->lastInsertId();

            // Record promo usage
            if ($applied_promo && $promo_discount > 0) {
                $pdo->prepare("INSERT INTO promotion_uses (promotion_id, user_id, order_id, discount_amount) VALUES (?, ?, ?, ?)")
                    ->execute([$applied_promo['id'], $_SESSION['user_id'], $order_id, $promo_discount]);
                $pdo->prepare("UPDATE promotions SET used_count = used_count + 1 WHERE id = ?")
                    ->execute([$applied_promo['id']]);
                unset($_SESSION['applied_promo']);
            }

            $pdo->commit();

            // Tạo mã bản quyền/tài khoản tự động qua Github API
            require_once 'includes/key_generator.php';

            $catId = (int) $product['category_id'];
            $prodName = strtolower($product['name']);
            $catName = isset($product['category_name']) ? strtolower($product['category_name']) : '';
            $keyType = false;

            // Parse custom days from product name
            $customDays = 30; // Mặc định
            if (strpos($prodName, '7 days') !== false || strpos($prodName, '7 ngày') !== false) {
                $customDays = 7;
            } elseif (strpos($prodName, '1 month') !== false || strpos($prodName, '1 tháng') !== false || strpos($prodName, '30 days') !== false || strpos($prodName, '30 ngày') !== false) {
                $customDays = 30;
            }

            // Phân loại Tool / Key. Mặc định là không tự tạo key ($keyType = false)
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

            // Lấy 4 chữ cái đầu của tên người dùng làm mã prefix
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
                // Fallback (dự phòng) nếu SP không thuộc loại trên hoặc API Github lỗi tạm thời
                $finalKey = 'NOKA-' . strtoupper(bin2hex(random_bytes(4))) . '-' . strtoupper(bin2hex(random_bytes(4)));
            }

            // Lưu kèm Link tải thẳng vào CSDL thông qua mảng JSON để tiện hiển thị ở Lịch sử giao dịch sau này.
            // Vì hiện tại cột input_data đang lưu dạng plain text, ta sẽ append Link Tải vào đằng sau cùng chuỗi này bằng delimiter nếu cần, 
            // hoặc đơn giản là lưu Key. History có thể lấy Link trực tiếp từ bảng Products thông qua JOIN.
            
            // Cập nhật lại key vào orders table (chỉ lưu Key/Thông báo)
            $stmtUpdate = $pdo->prepare("UPDATE orders SET input_data = ? WHERE id = ?");
            $stmtUpdate->execute([$finalKey, $order_id]);

            $purchase_result = [
                'order_id' => $order_id,
                'key' => $finalKey,
                'link' => !empty($product['download_link']) ? $product['download_link'] : '#'
            ];

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Buy error: ' . $e->getMessage());
            $error = "Có lỗi xảy ra, vui lòng thử lại.";
        }
    }
}

require 'views/layout/dashboard_header.php';
?>

<!-- Breadcrumb -->
<div class="mb-4">
    <a href="<?= BASE_URL ?>cua-hang" class="text-decoration-none text-muted small fw-medium">
        <i class="bi bi-arrow-left me-1"></i> Quay lại cửa hàng
    </a>
</div>

<?php if ($purchase_result): ?>
    <!-- Tailwind CDN cho Success Form -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: '#3b82f6', 'primary-dark': '#2563eb' },
                    borderRadius: { 'card': '12px' }
                }
            }
        }
    </script>
    <style>
        .success-card {
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            animation: fadeUp 0.4s ease both;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .btn-action { transition: all 0.15s ease; }
        .btn-action:active { transform: scale(0.97); }
    </style>

    <!-- ===== KẾT QUẢ MUA HÀNG THÀNH CÔNG ===== -->
    <div class="success-card overflow-hidden max-w-2xl mx-auto my-6">
        <!-- Header Success -->
        <div class="bg-emerald-500 text-white text-center py-8 px-6 relative overflow-hidden">
            <!-- Decorative circle -->
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-white opacity-10 rounded-full blur-2xl"></div>
            <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-white opacity-10 rounded-full blur-2xl"></div>
            
            <svg class="w-16 h-16 mx-auto mb-3 text-white drop-shadow-md" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="text-2xl font-bold tracking-tight mb-1">Thanh toán chớp nhoáng!</h3>
            <p class="text-emerald-100 text-sm font-medium tracking-wide">Mã giao dịch: <span class="uppercase">#<?= $purchase_result['order_id'] ?></span></p>
        </div>

        <div class="p-6 md:p-8 space-y-6">
            <!-- Order Details -->
            <div>
                <h6 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Biên lai giao dịch</h6>
                <div class="bg-slate-50 border border-slate-100 rounded-xl p-4 space-y-3">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500 font-medium">Sản phẩm</span>
                        <span class="text-slate-900 font-bold"><?= htmlspecialchars($product['name']) ?></span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500 font-medium">Tổng thanh toán</span>
                        <span class="text-primary font-bold text-base"><?= format_currency($product['price']) ?></span>
                    </div>
                </div>
            </div>

            <!-- License Key -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h6 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Tài nguyên của bạn</h6>
                    <span class="flex h-2 w-2">
                      <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-emerald-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                </div>
                
                <div class="bg-gradient-to-br from-slate-50 to-white border border-slate-200 rounded-xl p-5 relative group overflow-hidden">
                    <div class="absolute inset-0 bg-primary/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <code class="block text-center text-lg lg:text-xl font-bold text-slate-900 tracking-wide break-all relative z-10 selection:bg-primary selection:text-white" id="productKey">
                        <?= htmlspecialchars($purchase_result['key']) ?>
                    </code>
                </div>

                <div class="mt-4 flex flex-col sm:flex-row gap-3">
                    <!-- Nút Copy bằng Tailwind -->
                    <button class="btn-action w-full sm:w-auto flex-1 bg-slate-900 hover:bg-slate-800 text-white font-semibold py-3 px-4 rounded-xl flex items-center justify-center gap-2 shadow-sm"
                        onclick="navigator.clipboard.writeText(document.getElementById('productKey').textContent.trim()); const icon = this.querySelector('svg'); const text = this.querySelector('span'); const oldIcon = icon.innerHTML; const oldText = text.innerText; icon.innerHTML = '<path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M5 13l4 4L19 7\'/>'; text.innerText = 'Đã lưu vào bộ nhớ tạm!'; this.classList.replace('bg-slate-900', 'bg-emerald-600'); this.classList.replace('hover:bg-slate-800', 'hover:bg-emerald-700'); setTimeout(() => { icon.innerHTML = oldIcon; text.innerText = oldText; this.classList.replace('bg-emerald-600', 'bg-slate-900'); this.classList.replace('hover:bg-emerald-700', 'hover:bg-slate-800'); }, 2500);">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                        </svg>
                        <span>Sao chép mã</span>
                    </button>
                    <!-- Tải xuống nếu có link -->
                    <?php if (!empty($product['download_link']) && $product['download_link'] !== '#'): ?>
                        <a href="<?= htmlspecialchars($product['download_link']) ?>" target="_blank" class="btn-action w-full sm:w-auto flex-1 bg-primary hover:bg-primary-dark text-white font-semibold py-3 px-4 rounded-xl flex items-center justify-center gap-2 shadow-sm">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Tải file đính kèm
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Footer actions -->
            <div class="pt-6 border-t border-slate-100 flex gap-3">
                <a href="<?= BASE_URL ?>cua-hang" class="btn-action flex-1 bg-white border border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold py-2.5 rounded-xl text-sm text-center">
                    Tiếp tục mua sắm
                </a>
                <a href="<?= BASE_URL ?>lich-su" class="btn-action flex-1 bg-white border border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold py-2.5 rounded-xl text-sm text-center">
                    Xem lịch sử
                </a>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ===== TRANG CHI TIẾT SẢN PHẨM ===== -->
    <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div><?= $error ?></div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Thông tin sản phẩm -->
        <div class="col-lg-7">
            <div class="dashboard-card p-0 overflow-hidden">
                <?php if ($product['image']): ?>
                    <div style="height: 220px; overflow: hidden;">
                        <img src="<?= get_proxy_image_url($product['image']) ?>" class="w-100 h-100" style="object-fit: cover;"
                            alt="<?= htmlspecialchars($product['name']) ?>">
                    </div>
                <?php else: ?>
                    <div class="bg-light d-flex align-items-center justify-content-center" style="height: 220px;">
                        <i class="bi bi-image display-1 text-muted opacity-25"></i>
                    </div>
                <?php endif; ?>

                <div class="p-4">
                    <span class="badge bg-dark mb-3 rounded-1"><?= htmlspecialchars($product['category_name']) ?></span>
                    <h2 class="fw-bold fs-4 mb-3"><?= htmlspecialchars($product['name']) ?></h2>

                    <h6 class="fw-bold text-uppercase small text-muted mb-2" style="letter-spacing: 0.06em;">Mô tả chi tiết
                    </h6>
                    <div class="text-muted lh-lg mb-4">
                        <?php
                        // Split by period + space OR newline
                        $desc_raw = htmlspecialchars($product['description']);
                        // Chuẩn hoá xuống dòng và dấu chấm
                        $desc_raw = str_replace(["\r\n", "\r", "\n"], ". ", $desc_raw);
                        $lines = explode(". ", $desc_raw);

                        foreach ($lines as $line) {
                            $line = trim($line);
                            // Xoá dấu chấm thừa ở cuối nếu có
                            if (substr($line, -1) === '.') {
                                $line = substr($line, 0, -1);
                            }
                            if (!empty($line)) {
                                echo '<div class="d-flex align-items-start mb-2">';
                                echo '<img src="' . BASE_URL . 'assets/images/safety_tick.png" width="18" height="18" class="me-2 mt-1" alt="tick">';
                                echo '<span>' . $line . '.</span>';
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>

                    <div class="p-3 border rounded-2 bg-light">
                        <div class="d-flex gap-2">
                            <i class="bi bi-info-circle-fill text-primary flex-shrink-0 mt-1"></i>
                            <div class="small text-muted">
                                Sau khi mua, bạn sẽ nhận được <strong>Key/Link</strong> ngay lập tức.
                                Vui lòng lưu lại thông tin vì hệ thống sẽ không gửi lại.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel mua hàng -->
        <div class="col-lg-5">
            <div class="dashboard-card position-sticky" style="top: 80px;">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Thanh toán</h5>
                </div>
                <div class="p-4">
                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                        <span class="text-muted">Giá sản phẩm</span>
                        <span class="fs-4 fw-bold text-primary"><?= format_currency($product['price']) ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-4">
                        <span class="text-muted">Số dư hiện tại</span>
                        <span
                            class="fw-bold <?= $_SESSION['balance'] >= $product['price'] ? 'text-success' : 'text-danger' ?>">
                            <?= format_currency($_SESSION['balance']) ?>
                        </span>
                    </div>

                    <!-- Promo Code Input -->
                    <div class="mb-4 pb-3 border-bottom" id="promoSection">
                        <label class="form-label small fw-semibold text-muted mb-2">Mã khuyến mãi</label>
                        <div class="input-group" id="promoInputGroup">
                            <input type="text" id="promoCodeInput" class="form-control" placeholder="Nhập mã giảm giá..." style="text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">
                            <button class="btn btn-outline-dark fw-bold" type="button" id="btnApplyPromo">Áp dụng</button>
                        </div>
                        <div id="promoResult" class="mt-2" style="display:none;"></div>
                        <div id="promoDiscount" class="d-flex justify-content-between mt-3" style="display:none;">
                            <span class="text-success fw-semibold small"><i class="bi bi-tag-fill me-1"></i>Giảm giá</span>
                            <span class="text-success fw-bold" id="discountAmount"></span>
                        </div>
                        <div id="promoNewTotal" class="d-flex justify-content-between mt-2" style="display:none;">
                            <span class="fw-bold">Tổng thanh toán</span>
                            <span class="fw-bold text-primary fs-5" id="newTotalAmount"></span>
                        </div>
                    </div>

                    <?php if ($_SESSION['balance'] < $product['price']): ?>
                        <div class="alert alert-warning small mb-4 py-2">
                            <i class="bi bi-exclamation-triangle me-1"></i> Số dư không đủ.
                            <a href="<?= BASE_URL ?>nap-tien" class="fw-bold">Nạp tiền ngay</a>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="buyForm" class="mb-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="promo_code" id="promoCodeHidden" value="">
                        <button type="submit" id="btnBuy" class="btn btn-dark btn-lg w-100 fw-bold py-3"
                            <?= ($product['status'] === 'out_of_stock' || $_SESSION['balance'] < $product['price']) ? 'disabled' : '' ?>>
                            <span class="btn-text">
                                <i class="bi bi-bag-check me-2"></i>
                                <?php if ($product['status'] === 'out_of_stock' || $product['stock'] <= 0): ?>
                                    Hết hàng
                                <?php else: ?>
                                    Mua ngay — <?= format_currency($product['price']) ?> (Còn <?= (int)$product['stock'] ?>)
                                <?php endif; ?>
                            </span>
                            <span class="btn-loading d-none">
                                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                Đang xử lý...
                            </span>
                        </button>
                    </form>

                    <?php if ($product['stock'] > 0 && $product['status'] === 'active'): ?>
                    <button class="btn btn-outline-dark btn-lg w-100 fw-bold py-3 mt-2" id="btnAddToCart">
                        <i class="bi bi-cart-plus me-2"></i> Thêm vào giỏ hàng
                    </button>
                    <?php endif; ?>

                    <script>
                    document.getElementById('buyForm').addEventListener('submit', function(e) {
                        e.preventDefault();
                        const btn = document.getElementById('btnBuy');
                        const form = this;

                        // Disable button & show spinner
                        btn.disabled = true;
                        btn.querySelector('.btn-text').classList.add('d-none');
                        btn.querySelector('.btn-loading').classList.remove('d-none');

                        // Wait 1 second then submit
                        setTimeout(function() {
                            form.submit();
                        }, 1000);
                    });

                    // Add to Cart AJAX
                    const btnAddCart = document.getElementById('btnAddToCart');
                    if (btnAddCart) {
                        btnAddCart.addEventListener('click', async function() {
                            const btn = this;
                            const originalHTML = btn.innerHTML;
                            const productId = <?= (int)$product_id ?>;

                            // Loading state
                            btn.disabled = true;
                            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Đang thêm...';

                            try {
                                const formData = new FormData();
                                formData.append('action', 'add');
                                formData.append('product_id', productId);
                                formData.append('quantity', 1);

                                const response = await fetch('<?= BASE_URL ?>api/cart', {
                                    method: 'POST',
                                    body: formData
                                });
                                const data = await response.json();

                                if (data.success) {
                                    showToast('success', data.message);
                                    // Update cart badge
                                    let badge = document.getElementById('cart-badge');
                                    if (badge) {
                                        badge.textContent = data.cart_count;
                                        badge.style.display = 'inline-flex';
                                    }
                                    // Success animation
                                    btn.classList.remove('btn-outline-dark');
                                    btn.classList.add('btn-success', 'text-white');
                                    btn.innerHTML = '<i class="bi bi-check-lg me-2"></i> Đã thêm vào giỏ';
                                    setTimeout(() => {
                                        btn.classList.remove('btn-success', 'text-white');
                                        btn.classList.add('btn-outline-dark');
                                        btn.innerHTML = originalHTML;
                                        btn.disabled = false;
                                    }, 2000);
                                } else {
                                    showToast('error', data.message);
                                    btn.innerHTML = originalHTML;
                                    btn.disabled = false;
                                }
                            } catch (e) {
                                showToast('error', 'Lỗi kết nối máy chủ');
                                btn.innerHTML = originalHTML;
                                btn.disabled = false;
                            }
                        });
                    }
                    
                    // Promo Code AJAX
                    const promoInput = document.getElementById('promoCodeInput');
                    const btnPromo = document.getElementById('btnApplyPromo');
                    const promoResult = document.getElementById('promoResult');
                    const promoDiscount = document.getElementById('promoDiscount');
                    const promoNewTotal = document.getElementById('promoNewTotal');
                    const promoHidden = document.getElementById('promoCodeHidden');
                    
                    const btnBuy = document.getElementById('btnBuy');
                    const btnBuyText = btnBuy ? btnBuy.querySelector('.btn-text') : null;
                    const originalBtnBuyHTML = btnBuyText ? btnBuyText.innerHTML : '';
                    
                    let promoApplied = false;

                    btnPromo.addEventListener('click', async function() {
                        const code = promoInput.value.trim();
                        if (!code) return;

                        if (promoApplied) {
                            // Remove promo
                            const fd = new FormData();
                            fd.append('action', 'remove');
                            await fetch('<?= BASE_URL ?>api/promo', { method: 'POST', body: fd });
                            promoApplied = false;
                            promoInput.disabled = false;
                            promoInput.value = '';
                            promoHidden.value = '';
                            btnPromo.textContent = 'Áp dụng';
                            btnPromo.classList.remove('btn-danger');
                            btnPromo.classList.add('btn-outline-dark');
                            promoResult.style.display = 'none';
                            promoDiscount.style.display = 'none';
                            promoNewTotal.style.display = 'none';
                            
                            // Restore original buy button html
                            if (btnBuyText) {
                                btnBuyText.innerHTML = originalBtnBuyHTML;
                            }
                            return;
                        }

                        btnPromo.disabled = true;
                        btnPromo.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

                        try {
                            const fd = new FormData();
                            fd.append('action', 'apply');
                            fd.append('code', code);
                            fd.append('total', <?= (float)$product['price'] ?>);

                            const res = await fetch('<?= BASE_URL ?>api/promo', { method: 'POST', body: fd });
                            const data = await res.json();

                            if (data.success) {
                                promoResult.innerHTML = '<div class="alert alert-success py-2 px-3 small mb-0"><i class="bi bi-check-circle me-1"></i>' + data.message + '</div>';
                                promoResult.style.display = 'block';
                                document.getElementById('discountAmount').textContent = '-' + data.discount;
                                document.getElementById('newTotalAmount').textContent = data.new_total;
                                promoDiscount.style.display = 'flex';
                                promoNewTotal.style.display = 'flex';
                                promoInput.disabled = true;
                                promoHidden.value = code;
                                promoApplied = true;
                                btnPromo.textContent = 'Hủy mã';
                                btnPromo.classList.remove('btn-outline-dark');
                                btnPromo.classList.add('btn-danger');
                                
                                // Update buy button text with new price
                                if (btnBuyText) {
                                    btnBuyText.innerHTML = '<i class="bi bi-bag-check me-2"></i> Mua ngay &mdash; ' + data.new_total + ' (Còn <?= (int)$product['stock'] ?>)';
                                }
                            } else {
                                promoResult.innerHTML = '<div class="alert alert-danger py-2 px-3 small mb-0"><i class="bi bi-x-circle me-1"></i>' + data.message + '</div>';
                                promoResult.style.display = 'block';
                            }
                        } catch (e) {
                            promoResult.innerHTML = '<div class="alert alert-danger py-2 px-3 small mb-0">Lỗi kết nối</div>';
                            promoResult.style.display = 'block';
                        }
                        btnPromo.disabled = false;
                    });
                    </script>

                    <p class="text-center text-muted small mt-3 mb-0">
                        <i class="bi bi-shield-check me-1"></i> Giao dịch được bảo mật
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require 'views/layout/dashboard_footer.php'; ?>