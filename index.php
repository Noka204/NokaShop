<?php
// Session Security Configuration
ini_set('session.cookie_lifetime', 7200); // 2 hours
ini_set('session.gc_maxlifetime', 7200);  // 2 hours
ini_set('session.cookie_httponly', 1);    // Chống XSS đánh cắp cookie
ini_set('session.cookie_samesite', 'Strict'); // Chống CSRF qua cookie
ini_set('session.use_strict_mode', 1);    // Chống Session Fixation
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Tích hợp Router hiện đại (SEO-Friendly)
// 1. Ưu tiên lấy từ biến 'route' (do .htaccess rewrite)
$route = isset($_GET['route']) ? $_GET['route'] : '';

// 2. Nếu không có, tự parse REQUEST_URI (Dùng cho php -S hoặc server không hỗ trợ rewrite tốt)
if (empty($route)) {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $route = trim($uri, '/');
}

// Loại bỏ tiền tố index.php/ nếu có
if (strpos($route, 'index.php/') === 0) {
    $route = substr($route, 10);
}

// Làm sạch route (loại bỏ gạch chéo thừa đầu/cuối và index.php)
if (empty($route) || $route === 'index.php' || $route === 'index') {
    $route = 'home';
}

// --- DATABASE ROUTING ---
// Kiểm tra xem slug này có trong bảng routes không
try {
    $stmt = $pdo->prepare("SELECT target, is_api FROM routes WHERE slug = ? LIMIT 1");
    $stmt->execute([$route]);
    $db_route = $stmt->fetch();
    if ($db_route) {
        $route = $db_route['target'];
        // Nếu là API route thì có thể xử lý tiền tố api/ ở đây nếu cần
    }
} catch (Exception $e) {
    // Bỏ qua lỗi DB nếu bảng chưa tồn tại hoặc lỗi kết nối
}

// Nếu truy cập các trang Dashboard, Login, v.v.. thì require file tương ứng rồi dừng (exit)
if ($route !== 'home' && $route !== 'index.php' && $route !== 'index') {
    switch ($route) {
        case 'login':
        case 'register':
        case 'logout':
        case 'forgot_password':
            require "views/auth/{$route}.php";
            exit;
        case 'chinh-sach':
        case 'dieu-khoan':
        case 'faq':
            require "views/user/{$route}.php";
            exit;
        case 'dashboard':
        case 'history':
        case 'deposit':
        case 'buy':
        case 'cart':
        case 'support':
        case 'product':
        case 'ticket':
        case 'ticket_create':
        case 'deposit_checkout':
            if (!is_logged_in())
                redirect('login');

            // Special logic for SePay checkout redirect
            if ($route === 'deposit_checkout') {
                require_once 'includes/sepay_api.php';
                $sepay = new SePayAPI(SEPAY_MERCHANT_ID, SEPAY_API_TOKEN, false); // Live mode

                $amount = isset($_POST['amount']) ? (float) $_POST['amount'] : 0;
                if ($amount < 2000 || $amount > 1000000) {
                    set_flash_message('error', 'Số tiền nạp phải từ 2,000đ đến 1,000,000đ.');
                    redirect('nap-tien');
                }

                $orderData = [
                    'amount' => $amount,
                    'user_id' => $_SESSION['user_id'],
                    'description' => 'NAP ' . $_SESSION['username'],
                    'invoice_number' => 'INV-' . time() . '-' . $_SESSION['user_id'],
                    'success_url' => 'https://brenna-ultramodern-ghostily.ngrok-free.dev/nap-tien?status=success',
                    'error_url' => 'https://brenna-ultramodern-ghostily.ngrok-free.dev/nap-tien?status=error',
                    'cancel_url' => 'https://brenna-ultramodern-ghostily.ngrok-free.dev/nap-tien?status=cancel',
                    'payment_method' => 'BANK_TRANSFER'
                ];

                echo $sepay->generateCheckoutForm($orderData);
                exit;
            }

            require "views/user/{$route}.php";
            exit;
        default:
            if (strpos($route, 'admin') === 0) {
                // Manager can access: products, tickets, promotions
                $manager_allowed = ['products', 'tickets', 'promotions'];
                $admin_route = str_replace('admin/', '', $route);
                if ($admin_route === 'admin' || $admin_route === 'dashboard' || empty($admin_route))
                    $admin_route = 'index';

                if (!is_logged_in()) redirect('login');

                // Check permissions
                if (is_admin()) {
                    // Admin can access everything
                } elseif (is_manager()) {
                    if ($admin_route === 'index') {
                        // Redirect manager from dashboard to products
                        redirect('admin/products');
                    } elseif (!in_array($admin_route, $manager_allowed)) {
                         // Manager cannot access this specific route
                         redirect('login');
                    }
                } else {
                    redirect('login');
                }

                $file = "views/admin/{$admin_route}.php";
                if (file_exists($file)) {
                    require $file;
                    exit;
                }
            } else if (file_exists("views/user/{$route}.php")) {
                if (!is_logged_in())
                    redirect('login');
                require "views/user/{$route}.php";
                exit;
            } else if ($route === 'api/sepay' || $route === 'hooks/sepay-payment') {
                require "api/sepay_webhook.php";
                exit;
            } else if ($route === 'api/check-deposit') {
                require "api/check_deposit.php";
                exit;
            } else if ($route === 'api/update-amount') {
                require "api/update_amount.php";
                exit;
            } else if ($route === 'api/sync-sepay') {
                require "api/sync_sepay.php";
                exit;
            } else if ($route === 'api/check-new-payments') {
                require "api/check_new_payments.php";
                exit;
            } else if ($route === 'api/qr-proxy') {
                require "api/qr_proxy.php";
                exit;
            } else if ($route === 'api/img-proxy') {
                require "api/img_proxy.php";
                exit;
            } else if ($route === 'api/cart') {
                if (!is_logged_in()) { echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit; }
                require "api/cart_api.php";
                exit;
            } else if ($route === 'api/promo') {
                if (!is_logged_in()) { echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit; }
                require "api/promo_api.php";
                exit;
            }
            break;
    }
}

// ============== GIAO DIỆN TRANG CHỦ ==============
require_once 'header.php';

// Dữ liệu Nhóm dịch vụ chính (Mô hình Bento)
$bento_services = [
    [
        'title' => 'Premium',
        'desc' => 'Tài khoản premium giá tối ưu.',
        'badge' => 'HOT',
        'col' => 'col-md-6 col-lg-8' // Kích thước bento box
    ],
    [
        'title' => 'Bản quyền',
        'desc' => 'Key chính hãng, kích hoạt nhanh.',
        'badge' => '',
        'col' => 'col-md-6 col-lg-4'
    ],
    [
        'title' => 'Tự động',
        'desc' => 'Giao ngay sau khi thanh toán.',
        'badge' => '',
        'col' => 'col-md-6 col-lg-4'
    ],
    [
        'title' => 'An toàn',
        'desc' => 'Lịch sử giao dịch minh bạch.',
        'badge' => 'TRUST',
        'col' => 'col-md-6 col-lg-8'
    ]
];
?>

<!-- Hero Section -->
<section class="container my-5 py-5 d-flex flex-column align-items-center text-center">
    <h1 class="display-3 fw-bold mb-3 text-uppercase tracking-wide" style="letter-spacing: -1px;">NOKA DIGITAL MARKET
    </h1>
    <h2 class="h3 fw-semibold text-muted mb-4">Mua tài nguyên số theo cách nhanh và rõ ràng</h2>
    <p class="lead text-muted mx-auto mb-5" style="max-width: 700px; line-height: 1.6;">
        Trải nghiệm mua tài khoản / key / dịch vụ số với giao diện tối giản, kiểm soát đơn hàng minh bạch, và hệ thống
        xử lý tự động liên tục.
    </p>
    <div class="d-flex gap-3">
        <a href="<?= BASE_URL ?>dashboard" class="btn btn-dark btn-lg px-5 py-3 fw-bold rounded-1">Vào Dashboard</a>
        <a href="#services" class="btn btn-outline-dark btn-lg px-5 py-3 fw-bold rounded-1">Xem kho sản phẩm</a>
    </div>
</section>

<!-- Stats / Chỉ số vận hành -->
<section class="container my-5 py-4 border-top border-bottom border-light">
    <div class="row text-center g-4">
        <div class="col-md-4">
            <div class="p-3">
                <h3 class="display-4 fw-bold mb-2">100+</h3>
                <p class="text-uppercase fw-semibold text-muted mb-0 letter-spacing">Sản phẩm</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 border-start border-end border-light">
                <h3 class="display-4 fw-bold mb-2">24/7</h3>
                <p class="text-uppercase fw-semibold text-muted mb-0 letter-spacing">Tự động</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3">
                <h3 class="display-4 fw-bold mb-2">99.9%</h3>
                <p class="text-uppercase fw-semibold text-muted mb-0 letter-spacing">Ổn định</p>
            </div>
        </div>
    </div>
</section>

<!-- Nhóm dịch vụ chính (Bento Grid) -->
<section id="services" class="container my-5 py-5">
    <div class="mb-5">
        <h2 class="fw-bold display-6 mb-2">Nhóm dịch vụ chính</h2>
        <p class="text-muted fs-5">Thiết kế theo mô hình bento, ưu tiên đọc nhanh và thao tác nhanh.</p>
    </div>

    <div class="row g-4">
        <?php foreach ($bento_services as $service): ?>
            <div class="<?= $service['col'] ?>">
                <div class="product-card d-flex flex-column h-100 p-5 position-relative bg-light border-0"
                    style="min-height: 250px;">
                    <?php if (!empty($service['badge'])): ?>
                        <span class="position-absolute top-0 end-0 m-4 badge bg-dark text-white rounded-1 px-3 py-2">
                            <?= htmlspecialchars($service['badge']) ?>
                        </span>
                    <?php endif; ?>
                    <div class="mt-auto">
                        <h3 class="display-6 fw-bold mb-3"><?= htmlspecialchars($service['title']) ?></h3>
                        <p class="fs-5 text-muted mb-0"><?= htmlspecialchars($service['desc']) ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Cam kết -->
<section class="container my-5 py-5 bg-dark text-white rounded-3 p-5">
    <div class="row g-5 align-items-center">
        <div class="col-lg-5">
            <h2 class="display-5 fw-bold mb-4">Cam kết dịch vụ</h2>
            <a href="#" class="btn btn-light btn-lg px-4 fw-bold rounded-1 mt-3">Đi tới trung tâm hỗ trợ</a>
        </div>
        <div class="col-lg-7">
            <ul class="list-unstyled fs-5 mb-0">
                <li class="mb-4 d-flex align-items-start">
                    <span class="me-3 fw-bold text-secondary">01</span>
                    <span>Đúng mô tả, đúng giá, đúng thông tin.</span>
                </li>
                <li class="mb-4 d-flex align-items-start">
                    <span class="me-3 fw-bold text-secondary">02</span>
                    <span>Lưu lịch sử giao dịch và đơn hàng đầy đủ.</span>
                </li>
                <li class="d-flex align-items-start">
                    <span class="me-3 fw-bold text-secondary">03</span>
                    <span>Hỗ trợ trực tiếp khi có phát sinh.</span>
                </li>
            </ul>
        </div>
    </div>
</section>

<!-- Về NokaShop -->
<section class="container my-5 py-5 mb-5 border-top border-light pt-5">
    <h2 class="fw-bold display-6 mb-5">Về NokaShop</h2>
    <div class="row g-5">
        <div class="col-md-6">
            <div class="mb-5">
                <h4 class="fw-bold mb-3 border-bottom pb-2" style="display:inline-block;">Tầm nhìn</h4>
                <p class="text-muted fs-5 lh-lg mt-3">Xây dựng hệ sinh thái mua bán tài nguyên số nhanh, rõ ràng và dễ
                    dùng cho mọi đối tượng từ người mới đến người dùng chuyên sâu.</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-5">
                <h4 class="fw-bold mb-3 border-bottom pb-2" style="display:inline-block;">Điểm mạnh hệ thống</h4>
                <ul class="text-muted fs-5 lh-lg mt-3 ps-3">
                    <li>Quy trình mua hàng tự động, nhận sản phẩm ngay sau thanh toán.</li>
                    <li>Kho sản phẩm đa dạng: tài khoản premium, key phần mềm, dịch vụ số.</li>
                    <li>Thiết kế tối ưu cho desktop và mobile, thao tác nhanh, trực quan.</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<?php
require_once 'footer.php';
?>