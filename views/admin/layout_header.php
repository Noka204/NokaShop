<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Trị Hệ Thống - NokaService</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="<?= BASE_URL ?>assets/images/logo_wed.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    </style>
</head>
<body class="text-slate-800 antialiased flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 text-white flex flex-col h-full flex-shrink-0 transition-transform duration-300 md:translate-x-0 -translate-x-full fixed md:relative z-50 shadow-xl" id="adminSidebar">
        <div class="h-16 flex items-center px-6 border-b border-slate-800 shrink-0">
            <a href="<?= BASE_URL ?>admin" class="flex items-center gap-2 font-bold text-xl tracking-wide text-white">
                <img src="<?= BASE_URL ?>assets/images/logo_wed.jpg" alt="Logo" class="w-8 h-8 rounded shadow-sm">
                <span>NokaAdmin</span>
            </a>
            <button class="md:hidden ml-auto text-slate-400 hover:text-white" onclick="document.getElementById('adminSidebar').classList.toggle('-translate-x-full')">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            <?php
            $current_route = isset($_GET['route']) ? $_GET['route'] : 'admin';
            $menu_items = [
                ['url' => 'admin', 'icon' => 'fa-chart-line', 'label' => 'Tổng Quan', 'role' => 'admin'],
                ['url' => 'admin/users', 'icon' => 'fa-users', 'label' => 'Thành Viên', 'role' => 'admin'],
                ['url' => 'admin/categories', 'icon' => 'fa-layer-group', 'label' => 'Danh Mục', 'role' => 'admin'],
                ['url' => 'admin/products', 'icon' => 'fa-box', 'label' => 'Dịch Vụ', 'role' => 'manager'],
                ['url' => 'admin/orders', 'icon' => 'fa-cart-shopping', 'label' => 'Đơn Hàng', 'role' => 'admin'],
                ['url' => 'admin/payments', 'icon' => 'fa-money-bill-transfer', 'label' => 'Nạp Tiền', 'role' => 'admin'],
                ['url' => 'admin/tickets', 'icon' => 'fa-headset', 'label' => 'Ticket Hỗ Trợ', 'role' => 'manager'],
                ['url' => 'admin/promotions', 'icon' => 'fa-tags', 'label' => 'Khuyến Mãi', 'role' => 'manager'],
            ];

            foreach ($menu_items as $item):
                // Role filtering: 'admin' = admin only, 'manager' = admin + manager
                if ($item['role'] === 'admin' && !is_admin()) continue;
                if ($item['role'] === 'manager' && !is_manager()) continue;

                $active = ($current_route === $item['url']) ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:bg-slate-800 hover:text-white';
            ?>
                <a href="<?= BASE_URL . $item['url'] ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all <?= $active ?>">
                    <i class="fa-solid <?= $item['icon'] ?> w-5 text-center"></i>
                    <span class="font-medium text-sm"><?= $item['label'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="p-4 border-t border-slate-800 shrink-0">
            <a href="<?= BASE_URL ?>dashboard" class="flex items-center gap-2 text-sm text-slate-400 hover:text-white transition-colors py-2">
                <i class="fa-solid fa-arrow-left"></i> Về Dashboard
            </a>
        </div>
    </aside>

    <!-- Overlay for mobile sidebar -->
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-40 hidden md:hidden" id="sidebarOverlay" onclick="document.getElementById('adminSidebar').classList.add('-translate-x-full'); this.classList.add('hidden');"></div>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full min-w-0 overflow-hidden relative">
        <!-- Top Navbar -->
        <header class="h-16 shrink-0 bg-white border-b border-slate-200 flex items-center justify-between px-4 sm:px-6 z-10 sticky top-0 shadow-sm">
            <button class="md:hidden text-slate-500 hover:text-slate-700" onclick="document.getElementById('adminSidebar').classList.remove('-translate-x-full'); document.getElementById('sidebarOverlay').classList.remove('hidden');">
                <i class="fa-solid fa-bars text-xl"></i>
            </button>
            
            <div class="ml-auto flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <img src="https://ui-avatars.com/api/?name=Admin&background=4f46e5&color=fff&rounded=true" alt="Admin" class="w-8 h-8 rounded-full">
                    <span class="text-sm font-semibold text-slate-700 hidden sm:block">Quản Trị Viên</span>
                </div>
            </div>
        </header>

        <!-- Main Scrollable Area -->
        <div class="flex-1 overflow-y-auto bg-slate-50 p-4 sm:p-6 lg:p-8">
            <?php display_flash_message('success'); ?>
            <?php display_flash_message('error'); ?>
