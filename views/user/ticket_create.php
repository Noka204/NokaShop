<?php
// views/user/ticket_create.php
require 'views/layout/dashboard_header.php';

// Handle POST — Create ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $priority = 'medium'; // Default priority, admin will evaluate
    $order_id = !empty($_POST['order_id']) ? (int) $_POST['order_id'] : null;
    $message = trim($_POST['message'] ?? '');
    $is_ajax = isset($_POST['ajax']) && $_POST['ajax'] == '1';

    $errors = [];
    if (empty($subject)) $errors[] = 'Vui lòng nhập chủ đề.';
    if (empty($message)) $errors[] = 'Vui lòng nhập nội dung.';
    if (strlen($subject) > 255) $errors[] = 'Chủ đề quá dài (tối đa 255 ký tự).';

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Insert ticket
            $stmt = $pdo->prepare("INSERT INTO tickets (user_id, subject, order_id, priority) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $subject, $order_id, $priority]);
            $ticket_id = $pdo->lastInsertId();

            // Insert first message
            $stmt = $pdo->prepare("INSERT INTO ticket_replies (ticket_id, user_id, message, is_admin) VALUES (?, ?, ?, 0)");
            $stmt->execute([$ticket_id, $_SESSION['user_id'], $message]);

            $pdo->commit();
            
            if ($is_ajax) {
                echo json_encode(['success' => true, 'redirect' => BASE_URL . 'ticket?id=' . $ticket_id]);
                exit;
            } else {
                set_flash_message('success', 'Ticket đã được tạo thành công!');
                redirect('ticket?id=' . $ticket_id);
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Có lỗi xảy ra, vui lòng thử lại.';
        }
    }
    
    if ($is_ajax && !empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode('<br>', $errors)]);
        exit;
    }
}

// Fetch user's orders for dropdown
$stmt = $pdo->prepare("SELECT o.id, p.name as product_name FROM orders o JOIN products p ON o.product_id = p.id WHERE o.user_id = ? ORDER BY o.created_at DESC LIMIT 20");
$stmt->execute([$_SESSION['user_id']]);
$user_orders = $stmt->fetchAll();
?>

<style>
/* Ticket create styles */
.create-card {
    background: #fff;
    border: 1px solid rgba(226,232,240,0.8);
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    overflow: hidden;
}
.create-card .card-header-custom {
    background: linear-gradient(135deg, #0f172a, #1e293b);
    color: #fff;
    padding: 1.5rem 2rem;
}

@media (max-width: 991.98px) {
    .create-card .card-header-custom { padding: 1rem 1.25rem; }
    .create-card .card-header-custom h4 { font-size: 1.1rem !important; }
    .create-card .card-body { padding: 1rem !important; }
}
</style>

<div class="mb-3">
    <a href="<?= BASE_URL ?>ticket" class="btn btn-sm btn-outline-secondary rounded-3 px-3">
        <i class="bi bi-arrow-left me-1"></i> Quay lại danh sách
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger rounded-3 border-0 shadow-sm mb-3">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $e): ?>
                <li class="small"><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="create-card">
    <div class="card-header-custom">
        <h4 class="fw-bold mb-1"><i class="bi bi-ticket-perforated me-2"></i>Tạo Ticket Mới</h4>
        <p class="mb-0 small opacity-75">Mô tả chi tiết vấn đề để chúng tôi hỗ trợ nhanh nhất.</p>
    </div>
    <div class="card-body p-4">
        <form id="createTicketForm" method="POST">
            <input type="hidden" name="ajax" value="1">
            <div class="row g-3">
                <!-- Subject -->
                <div class="col-12">
                    <label class="form-label fw-bold text-secondary small">Chủ đề <span class="text-danger">*</span></label>
                    <input type="text" name="subject" class="form-control rounded-3 py-2" 
                           placeholder="Ví dụ: Lỗi nạp tiền, Key không hoạt động..." required maxlength="255">
                </div>

                <!-- Order ID -->
                <div class="col-12">
                    <label class="form-label fw-bold text-secondary small">Liên kết đơn hàng (nếu có)</label>
                    <select name="order_id" class="form-select rounded-3 py-2">
                        <option value="">— Không liên kết —</option>
                        <?php foreach ($user_orders as $o): ?>
                            <option value="<?= $o['id'] ?>">#<?= $o['id'] ?> — <?= htmlspecialchars($o['product_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Message -->
                <div class="col-12">
                    <label class="form-label fw-bold text-secondary small">Nội dung chi tiết <span class="text-danger">*</span></label>
                    <textarea name="message" class="form-control rounded-3" rows="6" 
                              placeholder="Mô tả chi tiết vấn đề bạn đang gặp phải..." required></textarea>
                </div>

                <!-- Submit -->
                <div class="col-12 d-flex gap-2 justify-content-end">
                    <a href="<?= BASE_URL ?>ticket" class="btn btn-outline-secondary rounded-3 px-4 py-2 fw-medium">Hủy</a>
                    <button type="submit" id="btnCreateSubmit" class="btn btn-dark rounded-3 px-5 py-2 fw-bold shadow-sm position-relative">
                        <span id="btnText"><i class="bi bi-send-fill me-2"></i> Gửi Ticket</span>
                        <div class="spinner-border spinner-border-sm position-absolute top-50 start-50 translate-middle d-none" id="createSpinner" role="status"></div>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('createTicketForm');
    const btnSubmit = document.getElementById('btnCreateSubmit');
    const btnText = document.getElementById('btnText');
    const spinner = document.getElementById('createSpinner');

    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Loading state
            btnSubmit.disabled = true;
            btnText.style.opacity = '0';
            spinner.classList.remove('d-none');

            try {
                const formData = new FormData(form);
                const response = await fetch('<?= BASE_URL ?>tao-ticket', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast('success', 'Ticket đang được gửi đi...');
                    // Redirect smoothly to the new ticket view
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 500);
                } else {
                    showToast('error', data.message || 'Có lỗi xảy ra!');
                    // Reset UI
                    btnSubmit.disabled = false;
                    btnText.style.opacity = '1';
                    spinner.classList.add('d-none');
                }
            } catch (error) {
                showToast('error', 'Lỗi mạng khi kết nối server.');
                btnSubmit.disabled = false;
                btnText.style.opacity = '1';
                spinner.classList.add('d-none');
            }
        });
    }
});
</script>

<?php require 'views/layout/dashboard_footer.php'; ?>
