<?php
// views/user/product.php

// Fetch Categories for filtering
$categories = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();

// Fetch Products with category names
$cat_filter = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$where = "p.status != 'hidden'";
$params = [];
if ($cat_filter > 0) {
    $where .= " AND p.category_id = ?";
    $params[] = $cat_filter;
}

$query = "
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE $where
    ORDER BY 
        CASE WHEN p.stock > 0 AND p.status = 'active' THEN 0 ELSE 1 END ASC,
        p.name ASC
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

require 'views/layout/dashboard_header.php';
?>

<div class="mb-5">
    <h2 class="fw-bold display-6 mb-2">Kho sản phẩm dịch vụ</h2>
    <p class="text-muted fs-5 mb-0">Lựa chọn gói dịch vụ phù hợp với nhu cầu của bạn.</p>
</div>

<!-- Category Filter -->
<div class="d-flex flex-wrap gap-2 mb-5">
    <a href="<?= BASE_URL ?>product" class="btn <?= $cat_filter == 0 ? 'btn-dark' : 'btn-outline-dark' ?> rounded-pill px-4">Tất cả</a>
    <?php foreach($categories as $cat): ?>
        <a href="<?= BASE_URL ?>product&cat=<?= $cat['id'] ?>" 
           class="btn <?= $cat_filter == $cat['id'] ? 'btn-dark' : 'btn-outline-dark' ?> rounded-pill px-4">
            <?= htmlspecialchars($cat['name']) ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <?php if(empty($products)): ?>
        <div class="col-12 text-center py-5">
            <div class="dashboard-card p-5">
                <i class="bi bi-box-seam display-1 text-light mb-3"></i>
                <p class="text-muted fs-5">Hiện chưa có sản phẩm nào trong danh mục này.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach($products as $p): ?>
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                <div class="product-card h-100 bg-white rounded-4 overflow-hidden position-relative d-flex flex-column transition-all duration-300">
                    <!-- Image Wrapper -->
                    <div class="product-image-wrapper position-relative overflow-hidden w-100">
                        <?php if($p['image']): ?>
                            <img src="<?= get_proxy_image_url($p['image']) ?>" class="product-img w-100 object-cover transition-all duration-500" alt="<?= htmlspecialchars($p['name']) ?>">
                        <?php else: ?>
                            <div class="product-img w-100 h-100 bg-light d-flex align-items-center justify-content-center text-muted">
                                <i class="bi bi-image display-4 opacity-25"></i>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Overlay gradient -->
                        <div class="position-absolute inset-0 bg-gradient-to-t from-gray-900/40 to-transparent opacity-0 hover-overlay transition-opacity duration-300"></div>

                        <!-- Category Badge with Glassmorphism -->
                        <div class="position-absolute top-0 start-0 m-3 z-10">
                            <span class="badge category-badge text-dark px-3 py-2 rounded-pill small fw-bold shadow-sm d-flex align-items-center gap-1 border border-white/50">
                                <i class="bi bi-tag-fill text-primary"></i> <?= htmlspecialchars($p['category_name']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="card-body p-4 d-flex flex-column flex-grow-1">
                        <h3 class="product-title h6 fw-bold text-dark mb-3 text-truncate-2" title="<?= htmlspecialchars($p['name']) ?>">
                            <?= htmlspecialchars($p['name']) ?>
                        </h3>
                        
                        <div class="mt-auto border-top border-slate-100 pt-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-slate-500 small d-flex align-items-center gap-1">
                                    <i class="bi bi-cash-coin text-muted"></i> Giá:
                                </span>
                                <span class="fs-5 fw-black text-primary font-monospace tracking-tight">
                                    <?= format_currency($p['price']) ?>
                                </span>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <?php $current_stock = isset($p['stock']) ? (int)$p['stock'] : 0; ?>
                                <span class="text-slate-500 small d-flex align-items-center gap-1">
                                    <i class="bi bi-box-seam text-muted"></i> Kho:
                                </span>
                                <?php if($current_stock > 0 && isset($p['status']) && $p['status'] == 'active'): ?>
                                    <span class="badge bg-emerald-50 text-emerald-600 border border-emerald-200 px-2 py-1 rounded-2 fw-semibold">
                                        <i class="bi bi-check-circle-fill me-1"></i>Còn <?= $current_stock ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-red-50 text-red-600 border border-red-200 px-2 py-1 rounded-2 fw-semibold">
                                        <i class="bi bi-x-circle-fill me-1"></i>Hết hàng
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if($current_stock > 0 && isset($p['status']) && $p['status'] == 'active'): ?>
                                <a href="<?= BASE_URL ?>chi-tiet?id=<?= $p['id'] ?>" class="btn btn-product w-100 py-2.5 rounded-3 text-white fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2 transition-all text-decoration-none">
                                    <i class="bi bi-cart-plus fs-5"></i> Mua Ngay
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary w-100 py-2.5 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-2 opacity-75" disabled>
                                    <i class="bi bi-cart-x fs-5"></i> Hết hàng
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
/* Product Card Premium Design */
.product-card {
    border: 1px solid rgba(0,0,0,0.06);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
}
.product-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    border-color: rgba(99, 102, 241, 0.3);
}
.product-image-wrapper {
    aspect-ratio: 16/10;
    background: #f8fafc;
}
.product-img {
    height: 100%;
}
.product-card:hover .product-img {
    transform: scale(1.08);
}
.category-badge {
    background-color: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}
.product-title {
    line-height: 1.5;
    min-height: 3rem;
}
.text-truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.fw-black {
    font-weight: 900;
}
.tracking-tight {
    letter-spacing: -0.025em;
}
.btn-product {
    background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
    border: none;
    background-size: 200% auto;
}
.btn-product:hover {
    background-position: right center;
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.35);
    color: #fff;
}
.hover-overlay {
    top: 0; right: 0; bottom: 0; left: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.3) 0%, transparent 40%);
    pointer-events: none;
}
.product-card:hover .hover-overlay {
    opacity: 1;
}

/* Base custom Tailwind-like utility adjustments if not present */
.duration-300 { transition-duration: 300ms; }
.duration-500 { transition-duration: 500ms; }
.transition-all { transition-property: all; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); }
.border-slate-100 { border-color: #f1f5f9; }
.text-slate-500 { color: #64748b; }
.bg-emerald-50 { background-color: #ecfdf5 !important; }
.text-emerald-600 { color: #059669 !important; }
.border-emerald-200 { border-color: #a7f3d0 !important; }
.bg-red-50 { background-color: #fef2f2 !important; }
.text-red-600 { color: #dc2626 !important; }
.border-red-200 { border-color: #fecaca !important; }
</style>

<?php require 'views/layout/dashboard_footer.php'; ?>
