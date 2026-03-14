<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dịch Vụ Mạng Xã Hội</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS from AuroraVN -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-theme="dark">

    <nav class="navbar" data-nav>
        <div class="nav-container">
            <a href="<?= BASE_URL ?>" class="logo">
                <span style="color:white;">NOKA</span><span style="color:var(--primary-color);">SERVICE</span>
            </a>
            <ul class="nav-menu">
                <li><a href="<?= BASE_URL ?>" data-nav-link>Trang chủ</a></li>
                
                <?php if(is_logged_in()): ?>
                    <li><a href="<?= BASE_URL ?>dashboard" data-nav-link>Bảng điều khiển</a></li>
                    <li><a href="<?= BASE_URL ?>history" data-nav-link>Lịch sử đơn</a></li>
                    <li><a href="<?= BASE_URL ?>deposit" data-nav-link>Nạp tiền</a></li>
                    
                    <?php if(is_admin()): ?>
                        <li><a href="<?= BASE_URL ?>admin" data-nav-link>Quản trị</a></li>
                    <?php endif; ?>
                    
                    <li style="margin-left: 10px;">
                        <span style="color:var(--primary-color); font-weight: bold;">
                            <?= isset($_SESSION['balance']) ? format_currency($_SESSION['balance']) : '0 đ' ?>
                        </span>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>logout" class="nav-cta-btn" style="background-color: var(--card-bg); border: 1px solid var(--border);">Đăng xuất</a>
                    </li>
                <?php else: ?>
                    <li><a href="<?= BASE_URL ?>login" data-nav-link>Đăng nhập</a></li>
                    <li>
                        <a href="<?= BASE_URL ?>register" class="nav-cta-btn btn-primary" style="background: linear-gradient(135deg, #FF5757, #FF003C);">Tạo tài khoản</a>
                    </li>
                <?php endif; ?>
            </ul>
            <div class="hamburger">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>
    
    <!-- Main Content Area -->
    <main class="flex-grow">
