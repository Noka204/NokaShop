<?php
// views/admin/payments.php
require 'views/admin/layout_header.php';

// Handle Accept/Reject Deposit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action']; // 'accept' or 'reject'
    $id = (int)$_POST['id'];
    
    // Fetch this payment to verify status
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ? AND status = 'pending'");
    $stmt->execute([$id]);
    $payment = $stmt->fetch();
    
    if ($payment) {
        if ($action === 'accept') {
            try {
                // Begin Transaction for accepting
                $pdo->beginTransaction();
                
                // Update payment status to success
                $stmt = $pdo->prepare("UPDATE payments SET status = 'success' WHERE id = ?");
                $stmt->execute([$id]);
                
                // Add balance to user
                $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmt->execute([$payment['amount'], $payment['user_id']]);
                
                $pdo->commit();
                set_flash_message('success', 'Đã DUYỆT yêu cầu nạp tiền. Đã cộng ' . format_currency($payment['amount']) . ' cho user.');
            } catch (Exception $e) {
                $pdo->rollBack();
                set_flash_message('error', 'Lỗi hệ thống khi duyệt: ' . $e->getMessage());
            }
        } elseif ($action === 'reject') {
            // Just update payment status to failed
            $stmt = $pdo->prepare("UPDATE payments SET status = 'failed' WHERE id = ?");
            if ($stmt->execute([$id])) {
                set_flash_message('success', 'Đã TỪ CHỐI yêu cầu nạp tiền này.');
            }
        }
    } else {
        set_flash_message('error', 'Giao dịch không tồn tại hoặc đã được xử lý.');
    }
    
    // Redirect
    $s = isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : '';
    redirect('admin/payments' . $s);
}

// Pagination & Filter
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'pending'; // Default show pending
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$where = "1=1";
$params = [];
if ($filter_status !== 'all') {
    $where .= " AND p.status = ?";
    $params[] = $filter_status;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM payments p WHERE $where");
$stmt->execute($params);
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $limit);

$query = "
    SELECT p.*, u.username 
    FROM payments p 
    JOIN users u ON p.user_id = u.id 
    WHERE $where 
    ORDER BY p.id DESC 
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll();
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Quản Lý Nạp Tiền</h1>
        <p class="text-slate-500 text-sm mt-1">Duyệt yêu cầu nạp tiền để cộng số dư cho thành viên.</p>
    </div>
    
    <div class="bg-white p-1 rounded-lg shadow-sm border border-slate-200 inline-flex flex-wrap text-sm font-medium">
        <a href="?status=pending" class="px-4 py-2 rounded-md transition-colors <?= $filter_status === 'pending' ? 'bg-amber-100 text-amber-700' : 'text-slate-600 hover:bg-slate-50' ?>">
            Đang Chờ
        </a>
        <a href="?status=success" class="px-4 py-2 rounded-md transition-colors <?= $filter_status === 'success' ? 'bg-emerald-100 text-emerald-700' : 'text-slate-600 hover:bg-slate-50' ?>">
            Thành Công
        </a>
        <a href="?status=all" class="px-4 py-2 rounded-md transition-colors <?= $filter_status === 'all' ? 'bg-blue-100 text-blue-700' : 'text-slate-600 hover:bg-slate-50' ?>">
            Tất Cả
        </a>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto min-h-[400px]">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500">
                    <th class="px-6 py-4 font-semibold w-24">ID Giao Dịch</th>
                    <th class="px-6 py-4 font-semibold">Thành Viên</th>
                    <th class="px-6 py-4 font-semibold">Mã Tham Chiếu / PP</th>
                    <th class="px-6 py-4 font-semibold text-right">Mức Nạp</th>
                    <th class="px-6 py-4 font-semibold">Tình Trạng</th>
                    <th class="px-6 py-4 font-semibold text-center">Thao Tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if(empty($payments)): ?>
                    <tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">Không có dữ liệu hiển thị.</td></tr>
                <?php else: ?>
                    <?php foreach($payments as $pay): ?>
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="px-6 py-4 font-mono text-slate-500">#<?= $pay['id'] ?></td>
                            <td class="px-6 py-4">
                                <a href="<?= BASE_URL ?>admin/users?q=<?= urlencode($pay['username']) ?>" class="font-bold text-indigo-600 hover:underline"><?= htmlspecialchars($pay['username']) ?></a>
                                <div class="text-xs text-slate-500 mt-1"><?= date('d/m/Y H:i', strtotime($pay['created_at'])) ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-mono font-bold text-slate-800 bg-slate-100 px-2 py-1 inline-block rounded border border-slate-200"><?= htmlspecialchars($pay['transaction_code']) ?></div>
                                <div class="text-xs text-slate-500 mt-1"><i class="fa-solid fa-wallet text-slate-400 mr-1"></i> <?= htmlspecialchars($pay['payment_method']) ?></div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="font-bold text-lg text-emerald-600"><?= format_currency($pay['amount']) ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $s_c = ['pending'=>'bg-amber-100 text-amber-800','success'=>'bg-emerald-100 text-emerald-800','failed'=>'bg-red-100 text-red-800'];
                                $s_l = ['pending'=>'Đang Chờ','success'=>'Thành Công','failed'=>'Thất Bại'];
                                ?>
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold <?= $s_c[$pay['status']] ?>"><?= $s_l[$pay['status']] ?></span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if($pay['status'] === 'pending'): ?>
                                    <div class="flex items-center justify-center gap-2">
                                        <form action="" method="POST" onsubmit="return confirm('Duyệt khoản nạp này? Tài khoản người dùng sẽ được cộng thêm tiền ngay lập tức.');">
                                            <input type="hidden" name="action" value="accept">
                                            <input type="hidden" name="id" value="<?= $pay['id'] ?>">
                                            <button type="submit" class="bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white px-3 py-1.5 rounded-lg font-bold text-xs transition border border-emerald-200">
                                                <i class="fa-solid fa-check"></i> Duyệt
                                            </button>
                                        </form>
                                        
                                        <form action="" method="POST" onsubmit="return confirm('Từ chối yêu cầu này? Người dùng sẽ KHÔNG ĐƯỢC CỘNG TIỀN.');">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="id" value="<?= $pay['id'] ?>">
                                            <button type="submit" class="bg-red-50 text-red-600 hover:bg-red-600 hover:text-white px-3 py-1.5 rounded-lg font-bold text-xs transition border border-red-200">
                                                <i class="fa-solid fa-xmark"></i> Hủy
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="text-slate-400 text-xs italic">Đã xử lý</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Paginator -->
    <?php if($total_pages > 1): ?>
    <div class="p-4 border-t border-slate-100 flex gap-2 justify-center">
        <?php for($i=1; $i<=$total_pages; $i++): ?>
            <a href="?status=<?= $filter_status ?>&page=<?= $i ?>" class="px-3 py-1 rounded border <?= $i==$page ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50' ?> text-sm">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php require 'views/admin/layout_footer.php'; ?>
