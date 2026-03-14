<?php
// views/user/history.php
require 'views/layout/dashboard_header.php';

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total for pagination
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$total_rows = $stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Fetch orders with product names
$stmt = $pdo->prepare("
    SELECT o.*, p.name as product_name, p.download_link 
    FROM orders o 
    JOIN products p ON o.product_id = p.id 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll();
?>

    <div class="mb-4">
        <h2 class="fs-3 fw-bold text-dark">Lịch Sử Đơn Hàng</h2>
        <p class="text-secondary mt-1">Quản lý và theo dõi tiến độ các dịch vụ bạn đã mua.</p>
    </div>

<style>
/* History page — Mobile responsive */
@media (max-width: 991.98px) {
    .content-area h2.fs-3 {
        font-size: 1.2rem !important;
    }
    .content-area .text-secondary.mt-1 {
        font-size: 0.8rem !important;
    }
    .table-responsive-custom {
        border: none !important;
        border-radius: 0 !important;
    }
    .table-responsive-custom .table {
        min-width: 550px !important;
    }
    .table th {
        font-size: 0.6rem !important;
        padding: 0.5rem 0.35rem !important;
    }
    .table td {
        font-size: 0.78rem !important;
        padding: 0.6rem 0.35rem !important;
    }
    /* Key field */
    .table td .text-truncate {
        max-width: 120px !important;
        font-size: 0.7rem !important;
    }
    /* Buttons */
    .table td .btn-sm {
        font-size: 0.65rem !important;
        padding: 0.2rem 0.5rem !important;
    }
    /* Pagination */
    .card-footer {
        flex-direction: column !important;
        gap: 0.5rem !important;
        text-align: center !important;
    }
}
@media (max-width: 575.98px) {
    .content-area h2.fs-3 {
        font-size: 1rem !important;
    }
    .content-area .mb-4 {
        margin-bottom: 0.75rem !important;
    }
}
</style>

    <?php display_flash_message('success'); ?>

    <div class="dashboard-card mb-4 border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive-custom">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="bg-light">
                        <th class="ps-4 text-secondary text-uppercase" style="font-size: 0.8rem;">Mã ĐH</th>
                        <th class="text-secondary text-uppercase" style="font-size: 0.8rem;">Dịch Vụ</th>
                        <th class="text-secondary text-uppercase" style="font-size: 0.8rem;">Mã Key / Tài khoản</th>
                        <th class="text-secondary text-uppercase" style="font-size: 0.8rem;">Link Tải</th>
                        <th class="text-secondary text-uppercase" style="font-size: 0.8rem;">Giá Tiền</th>
                        <th class="text-secondary text-uppercase" style="font-size: 0.8rem;">Trạng Thái</th>
                        <th class="text-end pe-4 text-secondary text-uppercase" style="font-size: 0.8rem;">Ngày Tạo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="bi bi-box2 display-4 text-muted mb-3 opacity-50"></i>
                                    <p class="mb-3">Bạn chưa có đơn hàng nào.</p>
                                    <a href="<?= BASE_URL ?>" class="btn btn-primary rounded-3 px-4 shadow-sm">Khám phá dịch vụ</a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="font-monospace fw-medium text-dark bg-light px-2 py-1 rounded border small">#<?= $order['id'] ?></span>
                                </td>
                                <td>
                                    <div class="fw-bold text-slate-800"><?= htmlspecialchars($order['product_name'] ?? 'Sản phẩm đã xóa') ?></div>
                                    <div class="small text-muted mt-1">SL: <span class="fw-medium text-dark"><?= $order['quantity'] ?></span></div>
                                </td>
                                <td>
                                    <div class="text-truncate text-secondary font-monospace bg-light px-2 py-1 rounded small border border-light position-relative" 
                                         style="max-width: 200px; cursor: pointer;" 
                                         title="Click để copy: <?= htmlspecialchars($order['input_data']) ?>"
                                         onclick="copyToClipboard('<?= addslashes(htmlspecialchars($order['input_data'])) ?>', this)">
                                        <?= htmlspecialchars($order['input_data'] ?: 'N/A') ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if(!empty($order['download_link'])): ?>
                                        <a href="<?= htmlspecialchars($order['download_link']) ?>" target="_blank" class="btn btn-sm btn-dark px-3 py-1 rounded-pill" style="font-size: 0.75rem;">
                                            <i class="bi bi-download me-1"></i> Tải Tool
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small italic">Không có link</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold text-indigo-600">
                                    <?= format_currency($order['total_price']) ?>
                                </td>
                                <td>
                                    <?php
                                    $status_classes = [
                                        'pending' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                        'processing' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                                        'completed' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
                                        'cancelled' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle'
                                    ];
                                    $status_labels = [
                                        'pending' => '<i class="bi bi-clock me-1"></i> Chờ xử lý',
                                        'processing' => '<i class="bi bi-arrow-repeat me-1"></i> Đang chạy',
                                        'completed' => '<i class="bi bi-check2 me-1"></i> Hoàn thành',
                                        'cancelled' => '<i class="bi bi-x-lg me-1"></i> Đã hủy'
                                    ];
                                    $class = $status_classes[$order['status']] ?? 'bg-secondary-subtle text-secondary-emphasis';
                                    $label = $status_labels[$order['status']] ?? $order['status'];
                                    ?>
                                    <span class="badge rounded-pill <?= $class ?> px-3 py-2 fw-semibold shadow-sm">
                                        <?= $label ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4 text-muted small">
                                    <?= date('H:i - d/m/Y', strtotime($order['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="card-footer bg-light border-top px-4 py-3 d-flex align-items-center justify-content-between">
                <div class="small text-muted">
                    Hiển thị trang <span class="fw-bold text-dark"><?= $page ?></span> trên <span class="fw-bold text-dark"><?= $total_pages ?></span>
                </div>
                <div class="d-flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="<?= BASE_URL ?>history&page=<?= $page - 1 ?>" class="btn btn-sm btn-white border shadow-sm">Trước</a>
                    <?php endif; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="<?= BASE_URL ?>history&page=<?= $page + 1 ?>" class="btn btn-sm btn-white border shadow-sm">Sau</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

<script>
function copyToClipboard(text, element) {
    if (!text || text === 'N/A') return;
    
    // Copy the text
    navigator.clipboard.writeText(text).then(() => {
        // Show temporary feedback
        const originalTitle = element.getAttribute('title');
        element.style.color = '#10b981'; // accent green
        element.style.borderColor = '#10b981';
        
        // Brief tooltip-like alert (can be more sophisticated, but simple is fast)
        alert('Đã copy: ' + text);
        
        setTimeout(() => {
            element.style.color = '';
            element.style.borderColor = '';
        }, 1500);
    }).catch(err => {
        console.error('Lỗi khi copy: ', err);
    });
}
</script>

<?php require 'views/layout/dashboard_footer.php'; ?>

