<?php
// views/user/dashboard.php

// Check login, redirect if not logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Fetch quick stats for user
$user_id = $_SESSION['user_id'];

// Get total spent
$stmt = $pdo->prepare("SELECT SUM(total_price) as spent FROM orders WHERE user_id = ? AND status IN ('completed', 'processing')");
$stmt->execute([$user_id]);
$total_spent = $stmt->fetch()['spent'] ?? 0;

// Get total orders
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_orders = $stmt->fetch()['count'] ?? 0;

// Get 5 recent orders
$stmt = $pdo->prepare("
    SELECT o.*, p.name as product_name 
    FROM orders o 
    JOIN products p ON o.product_id = p.id 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll();
?>
<?php require 'views/layout/dashboard_header.php'; ?>

<?php display_flash_message('success'); ?>

<style>
/* Dashboard page — Mobile responsive */
@media (max-width: 991.98px) {
    .stats-grid {
        grid-template-columns: 1fr 1fr !important;
        gap: 0.65rem !important;
    }
    .stat-card {
        padding: 0.85rem !important;
        gap: 0.65rem !important;
    }
    .stat-icon {
        width: 36px !important;
        height: 36px !important;
        font-size: 1rem !important;
    }
    .stat-value {
        font-size: 1.1rem !important;
    }
    .stat-info h3 {
        font-size: 0.6rem !important;
    }
    .dashboard-card .card-header {
        padding: 0.65rem 0.85rem !important;
    }
    .card-title {
        font-size: 0.85rem !important;
    }
    .table-responsive-custom {
        border: none !important;
        border-radius: 0 !important;
    }
    .table-responsive-custom .table {
        min-width: 500px !important;
    }
    .table th {
        font-size: 0.6rem !important;
        padding: 0.5rem 0.35rem !important;
    }
    .table td {
        font-size: 0.78rem !important;
        padding: 0.6rem 0.35rem !important;
    }
}
@media (max-width: 575.98px) {
    .stats-grid {
        grid-template-columns: 1fr !important;
    }
    .dashboard-card .card-header {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 0.35rem !important;
    }
}
</style>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon icon-blue">
            <i class="bi bi-wallet2"></i>
        </div>
        <div class="stat-info">
            <h3>Số dư</h3>
            <div class="stat-value"><?= format_currency($_SESSION['balance']) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-green">
            <i class="bi bi-cart-check"></i>
        </div>
        <div class="stat-info">
            <h3>Chi tiêu</h3>
            <div class="stat-value"><?= format_currency($total_spent) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-orange">
            <i class="bi bi-box-seam"></i>
        </div>
        <div class="stat-info">
            <h3>Đơn hàng</h3>
            <div class="stat-value"><?= number_format($total_orders) ?></div>
        </div>
    </div>
</div>

<!-- Recent Orders Table -->
<div class="dashboard-card mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title text-dark">Đơn hàng gần đây</h5>
        <a href="<?= BASE_URL ?>history" class="btn btn-sm btn-light fw-medium">Xem thêm</a>
    </div>
    <div class="table-responsive-custom">
        <table class="table table-hover align-middle mb-0 border-top">
            <thead>
                <tr class="bg-light">
                    <th class="ps-4 text-secondary text-uppercase" style="font-size: 0.8rem;">Mã ĐH</th>
                    <th class="text-secondary text-uppercase" style="font-size: 0.8rem;">Dịch vụ</th>
                    <th class="text-secondary text-uppercase" style="font-size: 0.8rem;">Giá</th>
                    <th class="text-secondary text-uppercase" style="font-size: 0.8rem;">Trạng thái</th>
                    <th class="text-end pe-4 text-secondary text-uppercase" style="font-size: 0.8rem;">Thời gian</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_orders)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">Chưa có đơn hàng nào</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_orders as $order): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-dark">#<?= $order['id'] ?></td>
                            <td class="fw-medium text-slate-800"><?= htmlspecialchars($order['product_name']) ?></td>
                            <td class="fw-bold text-indigo-600"><?= format_currency($order['total_price']) ?></td>
                            <td>
                                <?php
                                $status_classes = [
                                    'pending' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                    'processing' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                                    'completed' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
                                    'cancelled' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle'
                                ];
                                $status_labels = [
                                    'pending' => 'Chờ xử lý',
                                    'processing' => 'Đang chạy',
                                    'completed' => 'Hoàn thành',
                                    'cancelled' => 'Đã hủy'
                                ];
                                $class = $status_classes[$order['status']] ?? 'bg-secondary-subtle text-secondary-emphasis';
                                $label = $status_labels[$order['status']] ?? $order['status'];
                                ?>
                                <span
                                    class="badge rounded-pill <?= $class ?> px-3 py-2 fw-semibold shadow-sm"><?= $label ?></span>
                            </td>
                            <td class="text-end pe-4 text-muted small"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require 'views/layout/dashboard_footer.php'; ?>