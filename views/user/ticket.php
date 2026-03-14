<?php
// views/user/ticket.php
require 'views/layout/dashboard_header.php';

// Handle reply POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message'], $_POST['ticket_id'])) {
    $ticket_id = (int) $_POST['ticket_id'];
    $message = trim($_POST['reply_message']);
    $is_ajax = isset($_POST['ajax']) && $_POST['ajax'] == '1';

    if (!empty($message)) {
        // Verify ticket belongs to user
        $check = $pdo->prepare("SELECT id FROM tickets WHERE id = ? AND user_id = ?");
        $check->execute([$ticket_id, $_SESSION['user_id']]);
        if ($check->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO ticket_replies (ticket_id, user_id, message, is_admin) VALUES (?, ?, ?, 0)");
            $stmt->execute([$ticket_id, $_SESSION['user_id'], $message]);
            // Re-open ticket if it was closed
            $pdo->prepare("UPDATE tickets SET status = 'open', updated_at = NOW() WHERE id = ? AND status = 'closed'")->execute([$ticket_id]);
            
            if ($is_ajax) {
                // Return JSON for AJAX
                $time = date('d/m H:i');
                $html = '
                <div class="d-flex flex-column align-items-end mb-3 animation-fade-in">
                    <div class="ticket-msg ticket-msg-user">' . nl2br(htmlspecialchars($message)) . '</div>
                    <div class="ticket-msg-time text-end">' . $time . '</div>
                </div>';
                echo json_encode(['success' => true, 'html' => $html]);
                exit;
            } else {
                set_flash_message('success', 'Đã gửi phản hồi thành công!');
            }
        } else if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Ticket không hợp lệ.']);
            exit;
        }
    } else if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Tin nhắn không được để trống.']);
        exit;
    }
    
    if (!$is_ajax) {
        redirect('ticket?id=' . $ticket_id);
    }
}

// Detail mode
$ticket_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($ticket_id > 0) {
    // Fetch ticket
    $stmt = $pdo->prepare("SELECT t.*, u.username FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.id = ? AND t.user_id = ?");
    $stmt->execute([$ticket_id, $_SESSION['user_id']]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        set_flash_message('error', 'Ticket không tồn tại.');
        redirect('ticket');
    }

    // Fetch replies
    $stmt = $pdo->prepare("SELECT r.*, u.username FROM ticket_replies r JOIN users u ON r.user_id = u.id WHERE r.ticket_id = ? ORDER BY r.created_at ASC");
    $stmt->execute([$ticket_id]);
    $replies = $stmt->fetchAll();
}

// List mode — fetch all tickets
if ($ticket_id === 0) {
    $stmt = $pdo->prepare("
        SELECT t.*, 
               (SELECT COUNT(*) FROM ticket_replies WHERE ticket_id = t.id) as reply_count
        FROM tickets t 
        WHERE t.user_id = ? 
        ORDER BY t.updated_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $tickets = $stmt->fetchAll();
}
?>

<style>
/* Ticket page styles */
.ticket-msg {
    max-width: 85%;
    padding: 0.85rem 1.1rem;
    border-radius: 14px;
    font-size: 0.9rem;
    line-height: 1.55;
    position: relative;
    word-break: break-word;
}
.ticket-msg-user {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: #fff;
    margin-left: auto;
    border-bottom-right-radius: 4px;
}
.ticket-msg-admin {
    background: #f1f5f9;
    color: #334155;
    margin-right: auto;
    border-bottom-left-radius: 4px;
    border: 1px solid #e2e8f0;
}
.ticket-msg-time {
    font-size: 0.65rem;
    opacity: 0.6;
    margin-top: 4px;
}
.ticket-status-open { background: #dbeafe; color: #1d4ed8; }
.ticket-status-replied { background: #dcfce7; color: #15803d; }
.ticket-status-closed { background: #f1f5f9; color: #64748b; }
.ticket-priority-high { background: #fef2f2; color: #dc2626; }
.ticket-priority-medium { background: #fffbeb; color: #d97706; }
.ticket-priority-low { background: #f0fdf4; color: #16a34a; }

.ticket-row {
    transition: all 0.15s ease;
    border-left: 3px solid transparent;
}
.ticket-row:hover {
    background: #f8fafc;
    border-left-color: #3b82f6;
    transform: translateX(2px);
}

.animation-fade-in {
    animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 991.98px) {
    .ticket-msg { max-width: 95%; font-size: 0.82rem; }
    .ticket-thread { padding: 0.75rem !important; }
}
</style>

<?php display_flash_message('success'); ?>
<?php display_flash_message('error'); ?>

<?php if ($ticket_id > 0 && $ticket): ?>
<!-- ===== TICKET DETAIL ===== -->
<div class="mb-3 d-flex align-items-center gap-2">
    <a href="<?= BASE_URL ?>ticket" class="btn btn-sm btn-outline-secondary rounded-3 px-3">
        <i class="bi bi-arrow-left me-1"></i> Quay lại
    </a>
    <span class="text-muted small fw-medium">Ticket #<?= $ticket['id'] ?></span>
</div>

<div class="dashboard-card mb-4">
    <!-- Ticket Header -->
    <div class="card-header bg-white px-4 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="card-title mb-1"><?= htmlspecialchars($ticket['subject']) ?></h5>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="badge rounded-pill px-3 py-1 ticket-status-<?= $ticket['status'] ?> fw-semibold" style="font-size:0.7rem;">
                    <?= $ticket['status'] === 'open' ? 'Mở' : ($ticket['status'] === 'replied' ? 'Đã phản hồi' : 'Đóng') ?>
                </span>
                <span class="badge rounded-pill px-3 py-1 ticket-priority-<?= $ticket['priority'] ?> fw-semibold" style="font-size:0.7rem;">
                    <?= $ticket['priority'] === 'high' ? 'Cao' : ($ticket['priority'] === 'medium' ? 'Trung bình' : 'Thấp') ?>
                </span>
                <?php if ($ticket['order_id']): ?>
                    <span class="text-muted small">Đơn hàng: <strong>#<?= $ticket['order_id'] ?></strong></span>
                <?php endif; ?>
                <span class="text-muted small ms-auto"><?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></span>
            </div>
        </div>
    </div>

    <!-- Chat Thread -->
    <div class="card-body ticket-thread p-4" style="max-height: 500px; overflow-y: auto; background: #fafbfd;">
        <div class="d-flex flex-column gap-3">
            <?php if (empty($replies)): ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-chat-square-text fs-1 opacity-25 d-block mb-2"></i>
                    <span class="small">Chưa có tin nhắn nào.</span>
                </div>
            <?php else: ?>
                <?php foreach ($replies as $r): ?>
                    <div class="d-flex flex-column <?= $r['is_admin'] ? 'align-items-start' : 'align-items-end' ?>">
                        <div class="ticket-msg <?= $r['is_admin'] ? 'ticket-msg-admin' : 'ticket-msg-user' ?>">
                            <?php if ($r['is_admin']): ?>
                                <div class="fw-bold small mb-1" style="color:#3b82f6;">
                                    <i class="bi bi-shield-check me-1"></i>Admin
                                </div>
                            <?php endif; ?>
                            <?= nl2br(htmlspecialchars($r['message'])) ?>
                        </div>
                        <div class="ticket-msg-time <?= $r['is_admin'] ? 'text-start' : 'text-end' ?>">
                            <?= date('d/m H:i', strtotime($r['created_at'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reply Form -->
    <?php if ($ticket['status'] !== 'closed'): ?>
    <div class="card-footer bg-white border-top p-3">
        <form id="replyForm" method="POST" class="d-flex gap-2">
            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
            <input type="hidden" name="ajax" value="1">
            <input type="text" name="reply_message" id="replyInput" class="form-control rounded-3 border-light bg-light" 
                   placeholder="Nhập tin nhắn..." required autocomplete="off">
            <button type="submit" id="btnReplySubmit" class="btn btn-dark rounded-3 px-4 fw-bold flex-shrink-0">
                <i class="bi bi-send-fill" id="replyIcon"></i>
                <div class="spinner-border spinner-border-sm d-none" id="replySpinner" role="status"></div>
            </button>
        </form>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const replyForm = document.getElementById('replyForm');
        const replyInput = document.getElementById('replyInput');
        const chatThread = document.querySelector('.ticket-thread .d-flex.flex-column');
        const btnSubmit = document.getElementById('btnReplySubmit');
        const icon = document.getElementById('replyIcon');
        const spinner = document.getElementById('replySpinner');
        const threadContainer = document.querySelector('.ticket-thread');

        // Scroll to bottom initially
        if(threadContainer) threadContainer.scrollTop = threadContainer.scrollHeight;

        if(replyForm) {
            replyForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const msg = replyInput.value.trim();
                if(!msg) return;

                // UI Loading state
                btnSubmit.disabled = true;
                replyInput.disabled = true;
                icon.classList.add('d-none');
                spinner.classList.remove('d-none');

                try {
                    const formData = new FormData(replyForm);
                    const response = await fetch('<?= BASE_URL ?>ticket?id=<?= $ticket['id'] ?>', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if(data.success) {
                        // Remove empty state message if exists
                        const emptyState = chatThread.querySelector('.text-center.text-muted');
                        if(emptyState) emptyState.remove();

                        // Append new HTML to chat
                        chatThread.insertAdjacentHTML('beforeend', data.html);
                        
                        // Clear input and scroll down
                        replyInput.value = '';
                        threadContainer.scrollTop = threadContainer.scrollHeight;
                        
                        // Show success toast
                        if(typeof showToast === 'function') {
                            showToast('success', 'Đã chèn tin nhắn!');
                        }
                    } else {
                        if(typeof showToast === 'function') {
                            showToast('error', data.message || 'Lỗi gửi tin nhắn');
                        }
                    }
                } catch (error) {
                    if(typeof showToast === 'function') {
                        showToast('error', 'Lỗi kết nối mạng');
                    }
                } finally {
                    // Reset UI
                    btnSubmit.disabled = false;
                    replyInput.disabled = false;
                    icon.classList.remove('d-none');
                    spinner.classList.add('d-none');
                    replyInput.focus();
                }
            });
        }
    });
    </script>
    <?php else: ?>
    <div class="card-footer bg-light text-center py-3">
        <span class="text-muted small fw-medium"><i class="bi bi-lock me-1"></i> Ticket này đã được đóng.</span>
    </div>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- ===== TICKET LIST ===== -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="fs-3 fw-bold text-dark mb-1">Ticket Hỗ Trợ</h2>
        <p class="text-secondary small mb-0">Xem và quản lý các yêu cầu hỗ trợ của bạn.</p>
    </div>
    <a href="<?= BASE_URL ?>tao-ticket" class="btn btn-dark rounded-3 fw-bold px-4 py-2 shadow-sm">
        <i class="bi bi-plus-circle me-2"></i> Tạo Ticket Mới
    </a>
</div>

<?php if (empty($tickets)): ?>
    <div class="dashboard-card text-center py-5 px-4">
        <div class="mb-3">
            <i class="bi bi-ticket-perforated display-1 text-muted opacity-25"></i>
        </div>
        <h5 class="fw-bold text-muted">Chưa có ticket nào</h5>
        <p class="text-secondary small mb-4">Bạn chưa tạo yêu cầu hỗ trợ nào. Hãy tạo ticket mới khi cần giúp đỡ!</p>
        <a href="<?= BASE_URL ?>tao-ticket" class="btn btn-outline-dark rounded-3 px-4 fw-bold">
            <i class="bi bi-plus-circle me-2"></i> Tạo Ticket Đầu Tiên
        </a>
    </div>
<?php else: ?>
    <div class="dashboard-card overflow-hidden">
        <?php foreach ($tickets as $t): ?>
            <a href="<?= BASE_URL ?>ticket?id=<?= $t['id'] ?>" class="text-decoration-none d-block ticket-row border-bottom px-4 py-3">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div class="flex-grow-1 min-width-0">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="fw-bold text-dark text-truncate"><?= htmlspecialchars($t['subject']) ?></span>
                            <span class="badge rounded-pill px-2 py-1 ticket-status-<?= $t['status'] ?> fw-semibold" style="font-size:0.6rem; flex-shrink:0;">
                                <?= $t['status'] === 'open' ? 'Mở' : ($t['status'] === 'replied' ? 'Đã phản hồi' : 'Đóng') ?>
                            </span>
                        </div>
                        <div class="d-flex gap-3 text-muted small">
                            <span>#<?= $t['id'] ?></span>
                            <span><i class="bi bi-chat-dots me-1"></i><?= $t['reply_count'] ?> tin nhắn</span>
                            <span class="badge rounded-pill px-2 py-1 ticket-priority-<?= $t['priority'] ?>" style="font-size:0.55rem;">
                                <?= $t['priority'] === 'high' ? 'Cao' : ($t['priority'] === 'medium' ? 'TB' : 'Thấp') ?>
                            </span>
                        </div>
                    </div>
                    <div class="text-muted small text-nowrap flex-shrink-0">
                        <?= date('d/m/Y H:i', strtotime($t['updated_at'])) ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php endif; ?>

<?php require 'views/layout/dashboard_footer.php'; ?>
