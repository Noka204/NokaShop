<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Noka Shop</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="<?= BASE_URL ?>assets/images/logo_wed.jpg">
    <!-- Site Style -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <!-- Dashboard Style -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/dashboard.css">
</head>

<body class="dashboard-layout bg-slate-50">

    <!-- Sidebar (Left Navbar) -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <a href="<?= BASE_URL ?>" class="sidebar-brand">
                <img src="<?= BASE_URL ?>assets/images/logo_wed.jpg" alt="Logo" class="rounded shadow-sm" style="width: 35px !important; height: 35px !important; object-fit: cover; margin-right: 10px;">
                <span><span class="brand-accent">NOKA</span>SHOP</span>
            </a>
            <button class="btn btn-link text-white d-lg-none" id="sidebarToggleClose">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <?php
                // Sử dụng biến $route đã được index.php xử lý (đã qua mapping slug -> target)
                $current_route = isset($route) ? $route : (isset($_GET['route']) ? $_GET['route'] : 'product');
                ?>
                <a href="<?= BASE_URL ?>cua-hang" class="nav-item <?= $current_route == 'product' ? 'active' : '' ?>">
                    <i class="bi bi-house-door nav-icon"></i>
                    <span>Sản phẩm</span>
                </a>
                <a href="<?= BASE_URL ?>bang-dieu-khien"
                    class="nav-item <?= $current_route == 'dashboard' ? 'active' : '' ?>">
                    <i class="bi bi-grid-1x2 nav-icon"></i>
                    <span>Bảng Điều Khiển</span>
                </a>
                <a href="<?= BASE_URL ?>nap-tien" class="nav-item <?= $current_route == 'deposit' ? 'active' : '' ?>">
                    <i class="bi bi-wallet2 nav-icon"></i>
                    <span>Nạp Tiền</span>
                </a>
                <a href="<?= BASE_URL ?>lich-su" class="nav-item <?= $current_route == 'history' ? 'active' : '' ?>">
                    <i class="bi bi-clock-history nav-icon"></i>
                    <span>Lịch Sử Giao Dịch</span>
                </a>
                <?php 
                    $cart_count = 0;
                    if (isset($_SESSION['cart'])) {
                        foreach ($_SESSION['cart'] as $item) {
                            $cart_count += $item['quantity'];
                        }
                    }
                ?>
                <a href="<?= BASE_URL ?>gio-hang" class="nav-item <?= $current_route == 'cart' ? 'active' : '' ?>">
                    <i class="bi bi-cart3 nav-icon"></i>
                    <span>Giỏ hàng</span>
                    <span id="cart-badge" class="badge bg-danger rounded-pill ms-auto" style="font-size: 0.7rem; min-width: 20px; <?= $cart_count > 0 ? '' : 'display:none;' ?>"><?= $cart_count ?></span>
                </a>
                <a href="<?= BASE_URL ?>ho-tro" class="nav-item <?= $current_route == 'support' ? 'active' : '' ?>">
                    <i class="bi bi-life-preserver nav-icon"></i>
                    <span>Hỗ trợ</span>
                </a>
            </div>
        </nav>

        <div class="sidebar-footer">
            <a href="<?= BASE_URL ?>logout" class="nav-item text-danger">
                <i class="bi bi-box-arrow-right nav-icon"></i>
                <span>Đăng xuất</span>
            </a>
        </div>
    </aside>
    
    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Main Content -->
    <main class="admin-main">
        <!-- Top Header -->
        <header class="admin-header">
            <div class="topbar-left d-flex align-items-center">
                <button class="btn btn-link link-dark d-lg-none me-3" id="sidebarToggleOpen">
                    <i class="bi bi-list fs-3"></i>
                </button>
                <h1 class="page-title mb-0 fs-4 fw-bold text-slate-800">
                    <?php
                    $titles = [
                        'product' => 'Sản phẩm',
                        'dashboard' => 'Bảng điều khiển',
                        'deposit' => 'Nạp tiền',
                        'history' => 'Lịch sử giao dịch',
                        'cart' => 'Giỏ hàng',
                        'support' => 'Hỗ trợ'
                    ];
                    echo $titles[$current_route] ?? 'Bảng điều khiển';
                    ?>
                </h1>
            </div>

            <div class="topbar-right">
                <div class="user-balance shadow-sm" title="Số dư tài khoản của bạn">
                    <span><?= format_currency($_SESSION['balance']) ?></span>
                </div>
                <div class="dropdown">
                    <button class="btn btn-white border-0 rounded-circle shadow-sm user-dropdown-btn p-1" type="button"
                        data-bs-toggle="dropdown">
                        <div class="avatar-placeholder">
                            <i class="bi bi-person-fill fs-5"></i>
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                        <li><span
                                class="dropdown-item-text fw-bold"><?= htmlspecialchars($_SESSION['username']) ?></span>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>lich-su"><i
                                    class="bi bi-clock-history me-2"></i> Lịch sử</a></li>
                        <?php if (is_manager()): ?>
                            <li><a class="dropdown-item text-primary" href="<?= BASE_URL ?>admin/products"><i
                                        class="bi bi-shield-lock me-2"></i> Trang Quản Trị</a></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout"><i
                                    class="bi bi-box-arrow-right me-2"></i> Thoát</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Main Dashboard Content -->
        <div class="content-wrapper content-area p-4">
            <?php 
                // Render any pending flash messages (JS will pick these up and show Toasts)
                display_flash_message('success');
                display_flash_message('error');
                display_flash_message('warning');
                display_flash_message('info');
            ?>