<?php
// views/user/cart.php
require 'views/layout/dashboard_header.php';

$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$cart_total = 0;
foreach ($cart_items as $item) {
    $cart_total += $item['price'] * $item['quantity'];
}
?>

    <div class="mb-4">
        <h2 class="fs-3 fw-bold text-dark">Giỏ hàng của tôi</h2>
        <p class="text-secondary mt-1">Xem và thanh toán các sản phẩm bạn đã chọn.</p>
    </div>

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


    <?php display_flash_message('success'); ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="dashboard-card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="card-title text-dark mb-0">Danh sách sản phẩm</h5>
                    <?php if (!empty($cart_items)): ?>
                        <button class="btn btn-sm btn-outline-danger rounded-pill px-3" id="btnClearCart">
                            <i class="bi bi-trash3 me-1"></i> Xóa tất cả
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="cartTable">
                            <thead>
                                <tr class="bg-light">
                                    <th class="ps-4 text-secondary text-uppercase py-3" style="font-size: 0.8rem;">Sản phẩm</th>
                                    <th class="text-secondary text-uppercase py-3" style="font-size: 0.8rem;">Đơn giá</th>
                                    <th class="text-secondary text-uppercase py-3" style="font-size: 0.8rem;">Số lượng</th>
                                    <th class="text-secondary text-uppercase py-3" style="font-size: 0.8rem;">Thành tiền</th>
                                    <th class="text-end pe-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody id="cartBody">
                                <?php if (empty($cart_items)): ?>
                                    <tr id="emptyRow">
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <div class="d-flex flex-column align-items-center">
                                                <i class="bi bi-cart-x display-4 text-muted mb-3 opacity-50"></i>
                                                <p class="mb-3 fs-5">Giỏ hàng của bạn đang trống</p>
                                                <a href="<?= BASE_URL ?>cua-hang" class="btn btn-primary rounded-3 px-4 shadow-sm fw-bold">Tiếp tục mua sắm</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($cart_items as $id => $item): ?>
                                        <tr data-product-id="<?= $id ?>" class="cart-row">
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="bg-light rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                                        <i class="bi bi-box-seam text-primary fs-4"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark"><?= htmlspecialchars($item['name']) ?></div>
                                                        <div class="small text-muted"><?= htmlspecialchars($item['category'] ?? 'Dịch vụ') ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="fw-medium"><?= format_currency($item['price']) ?></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2" style="max-width: 120px;">
                                                    <button class="btn btn-sm btn-light border p-1 btn-qty-minus" data-id="<?= $id ?>" style="width: 28px; height: 28px;"><i class="bi bi-dash"></i></button>
                                                    <input type="number" class="form-control form-control-sm text-center fw-bold qty-input" value="<?= $item['quantity'] ?>" min="1" data-id="<?= $id ?>" readonly>
                                                    <button class="btn btn-sm btn-light border p-1 btn-qty-plus" data-id="<?= $id ?>" style="width: 28px; height: 28px;"><i class="bi bi-plus"></i></button>
                                                </div>
                                            </td>
                                            <td class="fw-bold text-primary item-total"><?= format_currency($item['price'] * $item['quantity']) ?></td>
                                            <td class="text-end pe-4">
                                                <button class="btn btn-sm btn-outline-danger border-0 rounded-circle btn-remove" data-id="<?= $id ?>" title="Xóa"><i class="bi bi-trash"></i></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info border-0 shadow-sm rounded-4 p-4 d-flex gap-3">
                <i class="bi bi-info-circle-fill fs-4 text-primary"></i>
                <div>
                    <h6 class="fw-bold mb-1">Chính sách bảo hành</h6>
                    <p class="mb-0 small text-secondary">Mọi sản phẩm tại Noka Shop đều được bảo hành theo đúng thời gian cam kết. Vui lòng liên hệ hỗ trợ nếu gặp trục trặc kỹ thuật.</p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden position-sticky" style="top: 100px;">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark mb-4">Chi tiết thanh toán</h5>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Tạm tính</span>
                        <span class="fw-medium" id="subtotal"><?= format_currency($cart_total) ?></span>
                    </div>

                    <!-- Promo Code Input -->
                    <div class="mb-3" id="promoSectionCart">
                        <div class="input-group input-group-sm">
                            <input type="text" id="promoCodeCart" class="form-control" placeholder="Mã khuyến mãi" style="text-transform: uppercase;">
                            <button class="btn btn-outline-dark" type="button" id="btnApplyPromoCart">Áp dụng</button>
                        </div>
                        <div id="promoResultCart" class="mt-1" style="display:none;"></div>
                    </div>

                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom" id="promoDiscountRow" style="display:none !important;">
                        <span class="text-secondary">Giảm giá <span id="promoCodeDisplay" class="badge bg-success ms-1"></span></span>
                        <span class="text-success fw-medium" id="promoDiscountAmount">- 0 VNĐ</span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="h5 fw-bold text-dark mb-0">Tổng cộng</span>
                        <span class="h4 fw-bold text-primary mb-0" id="grandTotal" data-raw-total="<?= $cart_total ?>"><?= format_currency($cart_total) ?></span>
                    </div>
                    
                    <button class="btn btn-dark btn-lg w-100 rounded-3 fw-bold shadow-sm py-3 mb-3" id="btnCheckout" <?= empty($cart_items) ? 'disabled' : '' ?>>
                        <span class="btn-text">Tiến hành thanh toán</span>
                        <span class="btn-loading d-none">
                            <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                            Đang xử lý...
                        </span>
                    </button>
                    
                    <div class="text-center">
                        <p class="small text-muted mb-0"><i class="bi bi-shield-check me-1"></i> Thanh toán an toàn 100%</p>
                    </div>
                </div>
                <div class="card-footer bg-light border-0 p-4 text-center">
                    <p class="small text-secondary mb-0">Số dư hiện tại: <span class="fw-bold text-dark" id="currentBalance"><?= format_currency($_SESSION['balance']) ?></span></p>
                    <?php if ($_SESSION['balance'] <= 0): ?>
                        <a href="<?= BASE_URL ?>deposit" class="small fw-bold text-primary text-decoration-none d-block mt-2">Nạp thêm tiền vào ví →</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<!-- Checkout Success Modal -->
<div class="modal fade" id="checkoutModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content overflow-visible bg-transparent border-0 shadow-none" id="checkoutModalContent">
            <!-- Dynamic Tailwind content inserted here -->
        </div>
    </div>
</div>

<script>
const API_URL = '<?= BASE_URL ?>api/cart';

// ===== QUANTITY BUTTONS =====
document.querySelectorAll('.btn-qty-minus').forEach(btn => {
    btn.addEventListener('click', () => updateQty(btn.dataset.id, -1));
});
document.querySelectorAll('.btn-qty-plus').forEach(btn => {
    btn.addEventListener('click', () => updateQty(btn.dataset.id, 1));
});

async function updateQty(productId, delta) {
    const row = document.querySelector(`tr[data-product-id="${productId}"]`);
    const input = row.querySelector('.qty-input');
    const newQty = parseInt(input.value) + delta;

    if (newQty <= 0) {
        return removeItem(productId);
    }

    try {
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('product_id', productId);
        formData.append('quantity', newQty);

        const res = await fetch(API_URL, { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            input.value = newQty;
            row.querySelector('.item-total').textContent = data.item_total;
            updateTotals(data.cart_total, data.cart_count);
        } else {
            showToast('error', data.message);
        }
    } catch (e) {
        showToast('error', 'Lỗi kết nối');
    }
}

// ===== REMOVE ITEM =====
document.querySelectorAll('.btn-remove').forEach(btn => {
    btn.addEventListener('click', () => removeItem(btn.dataset.id));
});

async function removeItem(productId) {
    const row = document.querySelector(`tr[data-product-id="${productId}"]`);

    try {
        const formData = new FormData();
        formData.append('action', 'remove');
        formData.append('product_id', productId);

        const res = await fetch(API_URL, { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            // Fade out animation
            row.style.transition = 'all 0.3s ease';
            row.style.opacity = '0';
            row.style.transform = 'translateX(30px)';
            setTimeout(() => {
                row.remove();
                updateTotals(data.cart_total, data.cart_count);
                showToast('success', data.message);

                // If cart is empty, show empty state
                if (data.cart_count === 0) {
                    showEmptyState();
                }
            }, 300);
        } else {
            showToast('error', data.message);
        }
    } catch (e) {
        showToast('error', 'Lỗi kết nối');
    }
}

// ===== CLEAR CART =====
const btnClear = document.getElementById('btnClearCart');
if (btnClear) {
    btnClear.addEventListener('click', async () => {
        if (!confirm('Xóa toàn bộ giỏ hàng?')) return;

        try {
            const formData = new FormData();
            formData.append('action', 'clear');

            const res = await fetch(API_URL, { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                document.querySelectorAll('.cart-row').forEach(row => {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '0';
                });
                setTimeout(() => {
                    document.querySelectorAll('.cart-row').forEach(r => r.remove());
                    showEmptyState();
                    updateTotals('0 VNĐ', 0);
                    showToast('success', data.message);
                    btnClear.style.display = 'none';
                }, 300);
            }
        } catch (e) {
            showToast('error', 'Lỗi kết nối');
        }
    });
}

// ===== CHECKOUT =====
document.getElementById('btnCheckout').addEventListener('click', async function() {
    const btn = this;
    btn.disabled = true;
    btn.querySelector('.btn-text').classList.add('d-none');
    btn.querySelector('.btn-loading').classList.remove('d-none');

    try {
        const formData = new FormData();
        formData.append('action', 'checkout');

        const res = await fetch(API_URL, { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            // Build results HTML using Tailwind Premium Design
            let html = `
                <div class="success-card overflow-hidden w-100 mx-auto">
                    <!-- Header Success -->
                    <div class="bg-emerald-500 text-white text-center py-8 px-6 relative overflow-hidden">
                        <div class="absolute -top-10 -right-10 w-32 h-32 bg-white opacity-10 rounded-full blur-2xl"></div>
                        <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-white opacity-10 rounded-full blur-2xl"></div>
                        
                        <svg class="w-16 h-16 mx-auto mb-3 text-white drop-shadow-md" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-2xl font-bold tracking-tight mb-1">Thanh toán chớp nhoáng!</h3>
                        <p class="text-emerald-100 text-sm font-medium tracking-wide">Bạn đã thanh toán thành công ${data.orders.length} đơn hàng</p>
                    </div>

                    <div class="p-6 md:p-8 space-y-6">
            `;

            data.orders.forEach((order, index) => {
                html += `
                        <div class="${index > 0 ? 'pt-6 border-t border-slate-100' : ''}">
                            <h6 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Sản phẩm #${index + 1}</h6>
                            <div class="bg-slate-50 border border-slate-100 rounded-xl p-4 space-y-3 mb-4">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-slate-500 font-medium">${order.name} <span class="text-slate-400">(x${order.quantity})</span></span>
                                    <span class="text-slate-900 font-bold">${order.total}</span>
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-slate-400">Mã giao dịch</span>
                                    <span class="text-slate-500 uppercase tracking-wider">#${order.order_id}</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between mb-3">
                                <h6 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Tài nguyên của bạn</h6>
                                <span class="flex h-2 w-2">
                                  <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-emerald-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                            </div>
                            
                            <div class="bg-gradient-to-br from-slate-50 to-white border border-slate-200 rounded-xl p-5 relative group overflow-hidden">
                                <div class="absolute inset-0 bg-primary/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                <code class="block text-center text-lg font-bold text-slate-900 tracking-wide break-all relative z-10 selection:bg-primary selection:text-white" id="productKey_${order.order_id}">
                                    ${order.key}
                                </code>
                            </div>

                            <div class="mt-4 flex flex-col sm:flex-row gap-3">
                                <button class="btn-action w-full sm:w-auto flex-1 bg-slate-900 hover:bg-slate-800 text-white font-semibold py-3 px-4 rounded-xl flex items-center justify-center gap-2 shadow-sm"
                                    onclick="navigator.clipboard.writeText(document.getElementById('productKey_${order.order_id}').textContent.trim()); const icon = this.querySelector('svg'); const text = this.querySelector('span'); const oldIcon = icon.innerHTML; const oldText = text.innerText; icon.innerHTML = '<path stroke-linecap=\\'round\\' stroke-linejoin=\\'round\\' stroke-width=\\'2\\' d=\\'M5 13l4 4L19 7\\'/>'; text.innerText = 'Đã lưu vào bộ nhớ tạm!'; this.classList.replace('bg-slate-900', 'bg-emerald-600'); this.classList.replace('hover:bg-slate-800', 'hover:bg-emerald-700'); setTimeout(() => { icon.innerHTML = oldIcon; text.innerText = oldText; this.classList.replace('bg-emerald-600', 'bg-slate-900'); this.classList.replace('hover:bg-emerald-700', 'hover:bg-slate-800'); }, 2500);">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                    </svg>
                                    <span>Sao chép mã</span>
                                </button>
                                ${order.link ? `
                                <a href="${order.link}" target="_blank" class="btn-action w-full sm:w-auto flex-1 bg-primary hover:bg-primary-dark text-white font-semibold py-3 px-4 rounded-xl flex items-center justify-center gap-2 shadow-sm text-decoration-none">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    <span>Tải file</span>
                                </a>` : ''}
                            </div>
                        </div>
                `;
            });
            
            html += `
                        <div class="pt-6 border-t border-slate-100 text-center">
                            <p class="text-sm text-slate-500 mb-4">Số dư còn lại: <span class="font-bold text-slate-900">${data.new_balance}</span></p>
                            <div class="flex gap-3">
                                <a href="<?= BASE_URL ?>cua-hang" class="btn-action flex-1 bg-white border border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold py-2.5 rounded-xl text-sm text-center text-decoration-none" data-bs-dismiss="modal">
                                    Tiếp tục mua sắm
                                </a>
                                <a href="<?= BASE_URL ?>lich-su" class="btn-action flex-1 bg-white border border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold py-2.5 rounded-xl text-sm text-center text-decoration-none">
                                    Xem lịch sử
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('checkoutModalContent').innerHTML = html;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('checkoutModal'));
            modal.show();

            // Clear cart UI
            document.querySelectorAll('.cart-row').forEach(r => r.remove());
            showEmptyState();
            updateTotals('0 VNĐ', 0);

            // Update balance display
            const balEls = document.querySelectorAll('.user-balance span, #currentBalance');
            balEls.forEach(el => el.textContent = data.new_balance);

            if (btnClear) btnClear.style.display = 'none';

        } else {
            showToast('error', data.message);
        }
    } catch (e) {
        showToast('error', 'Lỗi kết nối máy chủ');
    }

    btn.disabled = false;
    btn.querySelector('.btn-text').classList.remove('d-none');
    btn.querySelector('.btn-loading').classList.add('d-none');
});

// ===== PROMO CODE =====
let promoData = null;

const btnApplyPromoCart = document.getElementById('btnApplyPromoCart');
if (btnApplyPromoCart) {
    btnApplyPromoCart.addEventListener('click', async function() {
        const input = document.getElementById('promoCodeCart');
        const code = input.value.trim();
        const resultDiv = document.getElementById('promoResultCart');
        const discountRow = document.getElementById('promoDiscountRow');
        const discountAmountEl = document.getElementById('promoDiscountAmount');
        const promoDisplay = document.getElementById('promoCodeDisplay');
        
        if (!code && !promoData) return;

        // If promo already applied, click means remove
        if (promoData) {
            const fd = new FormData();
            fd.append('action', 'remove');
            await fetch('<?= BASE_URL ?>api/promo', { method: 'POST', body: fd });
            
            promoData = null;
            input.disabled = false;
            input.value = '';
            
            this.textContent = 'Áp dụng';
            this.classList.remove('btn-danger');
            this.classList.add('btn-outline-dark');
            
            resultDiv.style.display = 'none';
            discountRow.style.setProperty('display', 'none', 'important');
            
            // Recalculate total without discount
            const rawTotal = parseInt(document.getElementById('grandTotal').dataset.rawTotal || 0);
            document.getElementById('grandTotal').textContent = new Intl.NumberFormat('vi-VN').format(rawTotal) + ' VNĐ';
            return;
        }

        // Apply new promo
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        
        const rawTotal = parseInt(document.getElementById('grandTotal').dataset.rawTotal || 0);

        try {
            const fd = new FormData();
            fd.append('action', 'apply');
            fd.append('code', code);
            fd.append('total', rawTotal);

            const res = await fetch('<?= BASE_URL ?>api/promo', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.success) {
                promoData = data;
                input.disabled = true;
                this.textContent = 'Hủy mã';
                this.classList.remove('btn-outline-dark');
                this.classList.add('btn-danger');

                resultDiv.innerHTML = '<span class="text-success small fw-medium"><i class="bi bi-check-circle me-1"></i>Vừa áp dụng</span>';
                resultDiv.style.display = 'block';

                promoDisplay.textContent = data.promo_code;
                discountAmountEl.textContent = '-' + data.discount;
                discountRow.style.setProperty('display', 'flex', 'important');
                
                document.getElementById('grandTotal').textContent = data.new_total;
            } else {
                resultDiv.innerHTML = '<span class="text-danger small fw-medium"><i class="bi bi-x-circle me-1"></i>' + data.message + '</span>';
                resultDiv.style.display = 'block';
            }
        } catch (e) {
            resultDiv.innerHTML = '<span class="text-danger small fw-medium">Lỗi kết nối</span>';
            resultDiv.style.display = 'block';
        }
        
        this.disabled = false;
    });
}

// ===== HELPERS =====
function updateTotals(totalFormatted, count, rawTotal = null) {
    document.getElementById('subtotal').textContent = totalFormatted;
    
    // Extract numerical value if rawTotal isn't provided
    if (rawTotal === null) {
        rawTotal = parseInt(totalFormatted.replace(/\D/g, ''));
    }
    document.getElementById('grandTotal').dataset.rawTotal = rawTotal;

    // Re-apply promo if exists
    if (promoData) {
        // Simple recalculation on client side temporarily, but real validation happens on server
        fetch('<?= BASE_URL ?>api/promo', {
            method: 'POST',
            body: new URLSearchParams({
                'action': 'apply',
                'code': promoData.promo_code,
                'total': rawTotal
            })
        }).then(r => r.json()).then(data => {
            if (data.success) {
                document.getElementById('promoDiscountAmount').textContent = '-' + data.discount;
                document.getElementById('grandTotal').textContent = data.new_total;
            } else {
                // Promo invalid due to new total (e.g. min_order not met)
                document.getElementById('btnApplyPromoCart').click(); // Auto remove
                document.getElementById('promoResultCart').innerHTML = '<span class="text-warning small fw-medium"><i class="bi bi-exclamation-triangle me-1"></i>Mã giảm giá đã bị gỡ do thay đổi giỏ hàng</span>';
                document.getElementById('promoResultCart').style.display = 'block';
            }
        });
    } else {
        document.getElementById('grandTotal').textContent = totalFormatted;
    }

    const btnCheckout = document.getElementById('btnCheckout');
    if (btnCheckout) btnCheckout.disabled = (count === 0);

    // Update sidebar badge
    const badge = document.getElementById('cart-badge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'inline-flex';
        } else {
            badge.style.display = 'none';
        }
    }
}

function showEmptyState() {
    document.getElementById('cartBody').innerHTML = `
        <tr id="emptyRow">
            <td colspan="5" class="text-center py-5 text-muted">
                <div class="d-flex flex-column align-items-center">
                    <i class="bi bi-cart-x display-4 text-muted mb-3 opacity-50"></i>
                    <p class="mb-3 fs-5">Giỏ hàng của bạn đang trống</p>
                    <a href="<?= BASE_URL ?>cua-hang" class="btn btn-primary rounded-3 px-4 shadow-sm fw-bold">Tiếp tục mua sắm</a>
                </div>
            </td>
        </tr>
    `;
}
</script>

<?php require 'views/layout/dashboard_footer.php'; ?>
