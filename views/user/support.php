<?php
// views/user/support.php
require 'views/layout/dashboard_header.php';

// Handle quick ticket creation from the form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['subject']) && !empty($_POST['message'])) {
    $subject = trim($_POST['subject']);
    $order_id = !empty($_POST['order_id']) ? (int)$_POST['order_id'] : null;
    $message = trim($_POST['message']);
    $is_ajax = isset($_POST['ajax']) && $_POST['ajax'] == '1';

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO tickets (user_id, subject, order_id, priority) VALUES (?, ?, ?, 'medium')");
        $stmt->execute([$_SESSION['user_id'], $subject, $order_id]);
        $ticket_id = $pdo->lastInsertId();
        
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
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra, vui lòng thử lại.']);
            exit;
        } else {
            set_flash_message('error', 'Có lỗi xảy ra, vui lòng thử lại.');
        }
    }
}

// Count open tickets
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE user_id = ? AND status != 'closed'");
$stmt->execute([$_SESSION['user_id']]);
$open_tickets = $stmt->fetchColumn();
?>

    <div class="mb-4">
        <h2 class="fs-3 fw-bold text-dark">Trung tâm hỗ trợ</h2>
        <p class="text-secondary mt-1">Chúng tôi luôn sẵn sàng hỗ trợ bạn 24/7.</p>
    </div>

    <?php display_flash_message('success'); ?>
    <?php display_flash_message('error'); ?>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-2 h-100 text-center p-4" style="transition: transform 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                    <i class="bi bi-chat-dots-fill fs-1"></i>
                </div>
                <h5 class="fw-bold text-dark">Trò chuyện trực tiếp</h5>
                <p class="text-secondary small mb-4">Liên hệ qua Messenger hoặc Zalo để được hỗ trợ tức thì.</p>
                <div class="d-grid gap-2">
                    <a href="#" class="btn btn-outline-primary rounded-3 fw-bold py-2"><i class="bi bi-messenger me-2"></i> Messenger</a>
                    <a href="#" class="btn btn-outline-primary rounded-3 fw-bold py-2" style="color: #5865F2; border-color: #5865F2;"><i class="bi bi-discord me-2"></i> Discord Support</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-2 h-100 text-center p-4 border border-info border-2" style="transition: transform 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                    <i class="bi bi-envelope-check-fill fs-1"></i>
                </div>
                <h5 class="fw-bold text-dark">Ticket Hỗ Trợ</h5>
                <p class="text-secondary small mb-4">Gửi yêu cầu hỗ trợ kỹ thuật chi tiết qua hệ thống Ticket nội bộ.</p>
                <div class="d-grid gap-2">
                    <a href="<?= BASE_URL ?>ticket" class="btn btn-info text-white rounded-3 fw-bold py-2 shadow-sm position-relative">
                        <i class="bi bi-ticket-perforated me-2"></i> Quản lý Ticket
                        <?php if ($open_tickets > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= $open_tickets ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= BASE_URL ?>tao-ticket" class="btn btn-outline-info rounded-3 fw-bold py-2">
                        <i class="bi bi-plus-circle me-2"></i> Tạo form mới
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-2 h-100 text-center p-4" style="transition: transform 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                    <i class="bi bi-question-diamond-fill fs-1"></i>
                </div>
                <h5 class="fw-bold text-dark">Câu hỏi thường gặp</h5>
                <p class="text-secondary small mb-4">Tìm kiếm câu trả lời nhanh chóng cho các vấn đề phổ biến nhất.</p>
                <div class="d-grid mt-auto">
                    <a href="<?= BASE_URL ?>faq" class="btn btn-warning text-white rounded-3 fw-bold py-2 shadow-sm">Xem FAQ</a>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-card border-0 shadow-sm rounded-2 overflow-hidden mb-4">
        <div class="card-header bg-white py-3 px-4 border-bottom">
            <h5 class="card-title text-dark mb-0">Tạo Ticket siêu tốc</h5>
        </div>
        <div class="card-body p-4">
            <form id="quickTicketForm" method="POST">
                <input type="hidden" name="ajax" value="1">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-secondary small">Chủ đề <span class="text-danger">*</span></label>
                        <input type="text" name="subject" class="form-control rounded-3" placeholder="Ví dụ: Lỗi nạp tiền, Đơn hàng chưa chạy..." required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-secondary small">Mã đơn hàng (nếu có)</label>
                        <input type="text" name="order_id" class="form-control rounded-3" placeholder="Ví dụ: 12345">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold text-secondary small">Nội dung yêu cầu <span class="text-danger">*</span></label>
                        <textarea class="form-control rounded-3" name="message" rows="5" placeholder="Mô tả chi tiết vấn đề bạn đang gặp phải..." required></textarea>
                    </div>
                    <div class="col-12 text-end mt-4">
                        <button type="submit" id="btnQuickSubmit" class="btn btn-dark rounded-3 px-5 fw-bold py-2 shadow-sm position-relative">
                            <span id="quickBtnText">Gửi Ticket</span>
                            <div class="spinner-border spinner-border-sm position-absolute top-50 start-50 translate-middle d-none" id="quickSpinner" role="status"></div>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('quickTicketForm');
    const btnSubmit = document.getElementById('btnQuickSubmit');
    const btnText = document.getElementById('quickBtnText');
    const spinner = document.getElementById('quickSpinner');

    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // UI Loading State
            btnSubmit.disabled = true;
            btnText.style.opacity = '0';
            spinner.classList.remove('d-none');

            try {
                const formData = new FormData(form);
                const response = await fetch('<?= BASE_URL ?>ho-tro', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast('success', 'Đang chuyển hướng tới Ticket...');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 500);
                } else {
                    showToast('error', data.message || 'Lỗi tạo Ticket!');
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
