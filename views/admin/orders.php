<?php
// views/admin/orders.php
require 'views/admin/layout_header.php';

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $id = (int)$_POST['id'];
    $status = $_POST['status'];
    
    // In a real app, if cancelled, you might want to refund the user's balance.
    // For simplicity, we just update status here.
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $id])) {
        set_flash_message('success', 'Đã cập nhật trạng thái đơn hàng #' . $id);
    }
    
    // Preserve URL parameters for redirect
    $q = isset($_GET['q']) ? '&q=' . urlencode($_GET['q']) : '';
    $s = isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : '';
    $p = isset($_GET['page']) ? '&page=' . (int)$_GET['page'] : '';
    redirect('admin/orders?' . ltrim($q . $s . $p, '&'));
}

// Filters & Pagination
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$where = "1=1";
$params = [];

if ($search !== '') {
    // Search by ID or Username or Input Data
    $where .= " AND (o.id = ? OR u.username LIKE ? OR o.input_data LIKE ?)";
    $params[] = is_numeric($search) ? $search : 0;
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_status !== '') {
    $where .= " AND o.status = ?";
    $params[] = $filter_status;
}

$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE $where
");
$stmt->execute($params);
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $limit);

$query = "
    SELECT o.*, u.username, p.name as product_name
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    JOIN products p ON o.product_id = p.id
    WHERE $where 
    ORDER BY o.id DESC 
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// All statuses
$statuses = [
    'pending' => 'Chờ xử lý',
    'processing' => 'Đang chạy',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy'
];
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Quản Lý Đơn Hàng</h1>
    <p class="text-slate-500 text-sm mt-1">Theo dõi, xét duyệt và cập nhật tiến độ dịch vụ.</p>
</div>

<!-- Filters -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 mb-6">
    <form action="" method="GET" class="flex flex-col sm:flex-row gap-4 items-end">
        <div class="flex-1">
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Tìm kiếm</label>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Mã ĐH, Username, Link/ID..." class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 text-sm">
        </div>
        <div class="sm:w-48">
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Trạng thái</label>
            <select name="status" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 text-sm">
                <option value="">Tất cả</option>
                <?php foreach($statuses as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $filter_status === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition text-sm flex items-center gap-2">
                <i class="fa-solid fa-filter"></i> Lọc
            </button>
        </div>
        <?php if($search !== '' || $filter_status !== ''): ?>
        <div>
            <a href="<?= BASE_URL ?>admin/orders" class="bg-slate-100 text-slate-600 px-4 py-2 rounded-lg font-medium hover:bg-slate-200 transition text-sm block">Xóa lọc</a>
        </div>
        <?php endif; ?>
    </form>
</div>

<!-- Table -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto min-h-[400px]">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500">
                    <th class="px-6 py-4 font-semibold w-24">Mã ĐH</th>
                    <th class="px-6 py-4 font-semibold">Tài Khoản</th>
                    <th class="px-6 py-4 font-semibold">Dịch Vụ & Link/ID</th>
                    <th class="px-6 py-4 font-semibold">Tổng Tiền</th>
                    <th class="px-6 py-4 font-semibold">Ghi Nhận lúc</th>
                    <th class="px-6 py-4 font-semibold">Trạng Thái</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if(empty($orders)): ?>
                    <tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">Không tìm thấy đơn hàng nào.</td></tr>
                <?php else: ?>
                    <?php foreach($orders as $o): ?>
                        <tr class="hover:bg-slate-50 group">
                            <td class="px-6 py-4 font-mono font-bold text-slate-700">#<?= $o['id'] ?></td>
                            <td class="px-6 py-4">
                                <a href="<?= BASE_URL ?>admin/users?q=<?= urlencode($o['username']) ?>" class="font-bold text-indigo-600 hover:underline"><?= htmlspecialchars($o['username']) ?></a>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($o['product_name']) ?> <span class="text-xs text-slate-500 font-normal ml-1">x<?= $o['quantity'] ?></span></div>
                                <div class="mt-1 bg-slate-100 border border-slate-200 text-slate-600 font-mono text-xs px-2 py-1 rounded inline-block max-w-[250px] truncate" title="<?= htmlspecialchars($o['input_data']) ?>">
                                    <?= htmlspecialchars($o['input_data']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-bold text-emerald-600">
                                <?= format_currency($o['total_price']) ?>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                <?= date('H:i - d/m/Y', strtotime($o['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4">
                                <form action="" method="POST" class="flex items-center gap-2">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                    <?php
                                    $bg_colors = [
                                        'pending' => 'bg-yellow-50 focus:ring-yellow-500 text-yellow-800 border-yellow-200',
                                        'processing' => 'bg-blue-50 focus:ring-blue-500 text-blue-800 border-blue-200',
                                        'completed' => 'bg-emerald-50 focus:ring-emerald-500 text-emerald-800 border-emerald-200',
                                        'cancelled' => 'bg-red-50 focus:ring-red-500 text-red-800 border-red-200'
                                    ];
                                    $select_class = $bg_colors[$o['status']] ?? 'bg-white';
                                    ?>
                                    <select name="status" class="text-xs font-semibold rounded-lg px-2 py-1.5 border focus:ring-2 outline-none cursor-pointer <?= $select_class ?>" onchange="this.form.submit()">
                                        <?php foreach($statuses as $k => $v): ?>
                                            <option value="<?= $k ?>" <?= $o['status'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <noscript><button type="submit" class="bg-indigo-50 text-indigo-600 px-2 py-1 rounded text-xs">Lưu</button></noscript>
                                </form>
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
        <?php
        $base_url = "?q=" . urlencode($search) . "&status=" . urlencode($filter_status) . "&page=";
        for($i=1; $i<=$total_pages; $i++): 
        ?>
            <a href="<?= $base_url . $i ?>" class="px-3 py-1 rounded border <?= $i==$page ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50' ?> text-sm">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php require 'views/admin/layout_footer.php'; ?>
