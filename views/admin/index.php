<?php
// views/admin/index.php
require 'views/admin/layout_header.php';

// Quick Stats
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'revenue' => $pdo->query("SELECT SUM(total_price) FROM orders WHERE status IN ('completed', 'processing')")->fetchColumn() ?? 0,
    'pending_deposits' => $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn()
];

// Recent Orders
$recent_orders = $pdo->query("
    SELECT o.id, u.username, p.name as product_name, o.total_price, o.status, o.created_at
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN products p ON o.product_id = p.id
    ORDER BY o.created_at DESC LIMIT 5
")->fetchAll();
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900">Tổng Quan Hệ Thống</h1>
    <p class="text-slate-500 text-sm mt-1">Theo dõi các chỉ số quan trọng hôm nay.</p>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl shrink-0">
            <i class="fa-solid fa-users"></i>
        </div>
        <div>
            <p class="text-sm text-slate-500 font-medium">Tổng Thành Viên</p>
            <h3 class="text-2xl font-bold text-slate-800"><?= number_format($stats['users']) ?></h3>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl shrink-0">
            <i class="fa-solid fa-cart-shopping"></i>
        </div>
        <div>
            <p class="text-sm text-slate-500 font-medium">Tổng Đơn Hàng</p>
            <h3 class="text-2xl font-bold text-slate-800"><?= number_format($stats['orders']) ?></h3>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl shrink-0">
            <i class="fa-solid fa-sack-dollar"></i>
        </div>
        <div>
            <p class="text-sm text-slate-500 font-medium">Doanh Thu Thuần</p>
            <h3 class="text-xl font-bold text-slate-800"><?= format_currency($stats['revenue']) ?></h3>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex items-center gap-4 relative overflow-hidden">
        <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-xl shrink-0">
            <i class="fa-solid fa-bell"></i>
        </div>
        <div>
            <p class="text-sm text-slate-500 font-medium">Nạp Tiền Chờ Duyệt</p>
            <div class="flex items-center gap-2">
                <h3 class="text-2xl font-bold text-slate-800"><?= number_format($stats['pending_deposits']) ?></h3>
                <?php if($stats['pending_deposits'] > 0): ?>
                    <span class="flex h-3 w-3 relative">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <a href="<?= BASE_URL ?>admin/payments" class="absolute inset-0 z-10"></a>
    </div>
</div>

<!-- Widgets -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Recent Orders List -->
    <div class="lg:col-span-2 bg-white shadow-sm border border-slate-200 rounded-2xl overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center">
            <h2 class="font-bold text-slate-800">Đơn Hàng Mới Nhất</h2>
            <a href="<?= BASE_URL ?>admin/orders" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Xem tất cả</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                <tr class="bg-slate-50 text-slate-500">
                    <th class="px-6 py-3 font-semibold uppercase">ID</th>
                    <th class="px-6 py-3 font-semibold uppercase">User</th>
                    <th class="px-6 py-3 font-semibold uppercase">Dịch Vụ</th>
                    <th class="px-6 py-3 font-semibold uppercase">Trạng Thái</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if(empty($recent_orders)): ?>
                        <tr><td colspan="4" class="p-6 text-center text-slate-500">Chưa có dữ liệu.</td></tr>
                    <?php else: ?>
                        <?php foreach($recent_orders as $ord): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 font-mono">#<?= $ord['id'] ?></td>
                                <td class="px-6 py-4 font-bold text-indigo-600"><?= htmlspecialchars($ord['username']) ?></td>
                                <td class="px-6 py-4 text-slate-700 font-medium"><?= htmlspecialchars($ord['product_name']) ?></td>
                                <td class="px-6 py-4">
                                    <?php
                                    $sarr = ['pending'=>'bg-amber-100 text-amber-800','processing'=>'bg-blue-100 text-blue-800','completed'=>'bg-emerald-100 text-emerald-800','cancelled'=>'bg-red-100 text-red-800'];
                                    $lbl = ['pending'=>'Chờ xử lý','processing'=>'Đang chạy','completed'=>'Hoàn thành','cancelled'=>'Đã hủy'];
                                    ?>
                                    <span class="px-2 py-1 rounded text-xs font-semibold <?= $sarr[$ord['status']] ?>"><?= $lbl[$ord['status']] ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="bg-white shadow-sm border border-slate-200 rounded-2xl p-6">
        <h2 class="font-bold text-slate-800 mb-4">Lối Tắt</h2>
        <div class="space-y-3">
            <a href="<?= BASE_URL ?>admin/users" class="group flex items-center p-3 rounded-xl hover:bg-slate-50 border border-transparent hover:border-slate-200 transition-all">
                <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-bold text-slate-800">Cộng/Trừ Tiền</p>
                    <p class="text-xs text-slate-500">Thao tác số dư thủ công</p>
                </div>
            </a>
            
            <a href="<?= BASE_URL ?>admin/products" class="group flex items-center p-3 rounded-xl hover:bg-slate-50 border border-transparent hover:border-slate-200 transition-all">
                <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                    <i class="fa-solid fa-plus"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-bold text-slate-800">Thêm Dịch Vụ Mới</p>
                    <p class="text-xs text-slate-500">Cập nhật bảng giá</p>
                </div>
            </a>
        </div>
    </div>
</div>

<?php require 'views/admin/layout_footer.php'; ?>
