<?php
// views/admin/tickets.php — Admin/Manager ticket management

// Handle POST actions (reply, change status)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'reply' && !empty($_POST['ticket_id']) && !empty($_POST['message'])) {
        $ticket_id = (int)$_POST['ticket_id'];
        $message = trim($_POST['message']);
        $user_id = $_SESSION['user_id'];

        $stmt = $pdo->prepare("INSERT INTO ticket_replies (ticket_id, user_id, message, is_admin) VALUES (?, ?, ?, 1)");
        $stmt->execute([$ticket_id, $user_id, $message]);

        // Update ticket status to answered
        $pdo->prepare("UPDATE tickets SET status = 'answered', updated_at = NOW() WHERE id = ?")->execute([$ticket_id]);

        set_flash_message('success', 'Đã gửi phản hồi cho ticket #' . $ticket_id);
        redirect('admin/tickets?view=' . $ticket_id);
    } elseif ($action === 'update_status' && !empty($_POST['ticket_id'])) {
        $ticket_id = (int)$_POST['ticket_id'];
        $new_status = $_POST['status'] ?? 'open';
        $allowed = ['open', 'answered', 'closed'];
        if (in_array($new_status, $allowed)) {
            $pdo->prepare("UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$new_status, $ticket_id]);
            set_flash_message('success', 'Đã cập nhật trạng thái ticket #' . $ticket_id);
        }
        redirect('admin/tickets?view=' . $ticket_id);
    }
}

require 'views/admin/layout_header.php';

// View single ticket
if (isset($_GET['view'])) {
    $ticket_id = (int)$_GET['view'];
    $stmt = $pdo->prepare("SELECT t.*, u.username, u.email FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        echo '<div class="p-6 bg-red-50 text-red-800 rounded-lg border border-red-200">Ticket không tồn tại.</div>';
        require 'views/admin/layout_footer.php';
        exit;
    }

    // Get replies
    $stmt = $pdo->prepare("SELECT tr.*, u.username FROM ticket_replies tr JOIN users u ON tr.user_id = u.id WHERE tr.ticket_id = ? ORDER BY tr.created_at ASC");
    $stmt->execute([$ticket_id]);
    $replies = $stmt->fetchAll();
?>

<div class="mb-6">
    <a href="<?= BASE_URL ?>admin/tickets" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
        <i class="fa-solid fa-arrow-left mr-1"></i> Quay lại danh sách
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
    <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 mb-1">#<?= $ticket['id'] ?> — <?= htmlspecialchars($ticket['subject']) ?></h1>
            <div class="flex flex-wrap gap-3 text-sm text-slate-500">
                <span><i class="fa-solid fa-user mr-1"></i><?= htmlspecialchars($ticket['username']) ?> (<?= htmlspecialchars($ticket['email']) ?>)</span>
                <span><i class="fa-solid fa-clock mr-1"></i><?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></span>
            </div>
        </div>
        <form method="POST" class="flex items-center gap-2">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
            <select name="status" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Mở</option>
                <option value="answered" <?= $ticket['status'] === 'answered' ? 'selected' : '' ?>>Đã trả lời</option>
                <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Đóng</option>
            </select>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition">Cập nhật</button>
        </form>
    </div>

    <!-- Original message -->
    <div class="p-6 bg-slate-50 border-b border-slate-100">
        <div class="text-sm text-slate-400 mb-2 font-medium">Nội dung gốc:</div>
        <div class="text-slate-700 whitespace-pre-wrap"><?= nl2br(htmlspecialchars($ticket['message'])) ?></div>
    </div>

    <!-- Replies -->
    <div class="p-6 space-y-4 max-h-[500px] overflow-y-auto">
        <?php foreach ($replies as $r): ?>
            <div class="flex <?= $r['is_admin'] ? 'justify-start' : 'justify-end' ?>">
                <div class="max-w-[80%] <?= $r['is_admin'] ? 'bg-indigo-50 border-indigo-200' : 'bg-slate-50 border-slate-200' ?> border rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-bold <?= $r['is_admin'] ? 'text-indigo-600' : 'text-slate-600' ?>"><?= htmlspecialchars($r['username']) ?></span>
                        <?php if ($r['is_admin']): ?>
                            <span class="bg-indigo-600 text-white text-[10px] px-1.5 py-0.5 rounded font-bold">STAFF</span>
                        <?php endif; ?>
                        <span class="text-[10px] text-slate-400"><?= date('d/m H:i', strtotime($r['created_at'])) ?></span>
                    </div>
                    <div class="text-sm text-slate-700 whitespace-pre-wrap"><?= nl2br(htmlspecialchars($r['message'])) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Reply form -->
    <div class="p-6 border-t border-slate-200 bg-white">
        <form method="POST" class="flex gap-3">
            <input type="hidden" name="action" value="reply">
            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
            <input type="text" name="message" placeholder="Nhập phản hồi..." required class="flex-1 px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 text-sm">
            <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold text-sm hover:bg-indigo-700 transition shrink-0">
                <i class="fa-solid fa-paper-plane mr-1"></i> Gửi
            </button>
        </form>
    </div>
</div>

<?php
    require 'views/admin/layout_footer.php';
    exit;
}

// List all tickets
$status_filter = $_GET['status'] ?? '';
$where = "1=1";
$params = [];
if ($status_filter && in_array($status_filter, ['open', 'answered', 'closed'])) {
    $where = "t.status = ?";
    $params[] = $status_filter;
}

$stmt = $pdo->prepare("SELECT t.*, u.username, u.email, 
    (SELECT COUNT(*) FROM ticket_replies WHERE ticket_id = t.id) as reply_count
    FROM tickets t 
    JOIN users u ON t.user_id = u.id 
    WHERE $where
    ORDER BY 
        CASE t.status WHEN 'open' THEN 0 WHEN 'answered' THEN 1 WHEN 'closed' THEN 2 END ASC,
        t.updated_at DESC");
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Stats
$stats = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(status = 'open') as open_count,
    SUM(status = 'answered') as answered_count,
    SUM(status = 'closed') as closed_count
    FROM tickets")->fetch();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Quản Lý Ticket Hỗ Trợ</h1>
    <p class="text-slate-500 text-sm mt-1">Xem và phản hồi yêu cầu hỗ trợ từ người dùng.</p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <a href="<?= BASE_URL ?>admin/tickets" class="bg-white rounded-xl border border-slate-200 p-4 hover:shadow-md transition <?= !$status_filter ? 'ring-2 ring-indigo-500' : '' ?>">
        <div class="text-2xl font-bold text-slate-900"><?= $stats['total'] ?? 0 ?></div>
        <div class="text-xs text-slate-500 font-medium">Tổng ticket</div>
    </a>
    <a href="<?= BASE_URL ?>admin/tickets?status=open" class="bg-white rounded-xl border border-slate-200 p-4 hover:shadow-md transition <?= $status_filter === 'open' ? 'ring-2 ring-amber-500' : '' ?>">
        <div class="text-2xl font-bold text-amber-600"><?= $stats['open_count'] ?? 0 ?></div>
        <div class="text-xs text-slate-500 font-medium">Đang chờ</div>
    </a>
    <a href="<?= BASE_URL ?>admin/tickets?status=answered" class="bg-white rounded-xl border border-slate-200 p-4 hover:shadow-md transition <?= $status_filter === 'answered' ? 'ring-2 ring-emerald-500' : '' ?>">
        <div class="text-2xl font-bold text-emerald-600"><?= $stats['answered_count'] ?? 0 ?></div>
        <div class="text-xs text-slate-500 font-medium">Đã trả lời</div>
    </a>
    <a href="<?= BASE_URL ?>admin/tickets?status=closed" class="bg-white rounded-xl border border-slate-200 p-4 hover:shadow-md transition <?= $status_filter === 'closed' ? 'ring-2 ring-slate-400' : '' ?>">
        <div class="text-2xl font-bold text-slate-500"><?= $stats['closed_count'] ?? 0 ?></div>
        <div class="text-xs text-slate-500 font-medium">Đã đóng</div>
    </a>
</div>

<!-- Tickets Table -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500">
                    <th class="px-6 py-4 font-semibold">ID</th>
                    <th class="px-6 py-4 font-semibold">Tiêu đề</th>
                    <th class="px-6 py-4 font-semibold">Người gửi</th>
                    <th class="px-6 py-4 font-semibold">Trả lời</th>
                    <th class="px-6 py-4 font-semibold">Trạng thái</th>
                    <th class="px-6 py-4 font-semibold">Cập nhật</th>
                    <th class="px-6 py-4 font-semibold text-right">Hành động</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($tickets)): ?>
                    <tr><td colspan="7" class="px-6 py-8 text-center text-slate-500">Không có ticket nào.</td></tr>
                <?php else: ?>
                    <?php foreach ($tickets as $t): ?>
                        <tr class="hover:bg-slate-50 <?= $t['status'] === 'open' ? 'bg-amber-50/30' : '' ?>">
                            <td class="px-6 py-4 font-mono text-slate-500">#<?= $t['id'] ?></td>
                            <td class="px-6 py-4 font-bold text-slate-800 max-w-[250px] truncate"><?= htmlspecialchars($t['subject']) ?></td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-700"><?= htmlspecialchars($t['username']) ?></div>
                                <div class="text-[10px] text-slate-400"><?= htmlspecialchars($t['email']) ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="bg-slate-100 text-slate-600 px-2 py-1 rounded text-xs font-bold"><?= $t['reply_count'] ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $tc = ['open'=>'bg-amber-100 text-amber-800', 'answered'=>'bg-emerald-100 text-emerald-800', 'closed'=>'bg-slate-100 text-slate-600'];
                                $tl = ['open'=>'Đang chờ', 'answered'=>'Đã trả lời', 'closed'=>'Đã đóng'];
                                ?>
                                <span class="<?= $tc[$t['status']] ?? '' ?> px-2.5 py-1 rounded text-xs font-semibold"><?= $tl[$t['status']] ?? $t['status'] ?></span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500"><?= date('d/m/Y H:i', strtotime($t['updated_at'])) ?></td>
                            <td class="px-6 py-4 text-right">
                                <a href="<?= BASE_URL ?>admin/tickets?view=<?= $t['id'] ?>" class="bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white px-3 py-1.5 rounded transition text-sm font-medium">
                                    <i class="fa-solid fa-eye mr-1"></i> Xem
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require 'views/admin/layout_footer.php'; ?>
