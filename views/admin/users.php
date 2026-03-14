<?php
// views/admin/users.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_balance') {
    $uid = (int)$_POST['user_id'];
    $amount = (float)$_POST['amount'];
    $type = $_POST['type']; // add or deduct
    
    // Get current user balance
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $u = $stmt->fetch();
    
    if ($u && $amount > 0) {
        $new_bal = $type === 'add' ? $u['balance'] + $amount : max(0, $u['balance'] - $amount);
        $stmt = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
        $stmt->execute([$new_bal, $uid]);
        set_flash_message('success', 'Đã cập nhật số dư thành công!');
        redirect('admin/users');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_role') {
    if (!is_admin()) {
        die('Access denied');
    }
    $uid = (int)$_POST['user_id'];
    $new_role = $_POST['role'] ?? 'user';
    
    // Validate role
    if (in_array($new_role, ['admin', 'manager', 'user'])) {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $uid]);
        set_flash_message('success', 'Đã phân quyền thành viên thành công!');
        redirect('admin/users');
    }
}

// Prepare header
require 'views/admin/layout_header.php';

// Search & Pagination
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$where = "1=1";
$params = [];
if ($search !== '') {
    $where = "(username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $where");
$stmt->execute($params);
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $limit);

$query = "SELECT * FROM users WHERE $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Quản Lý Thành Viên</h1>
        <p class="text-slate-500 text-sm mt-1">Tìm kiếm, cập nhật số dư thành viên.</p>
    </div>
    
    <form action="" method="GET" class="relative">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Tên / Email..." class="pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 w-full sm:w-64 text-sm">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500">
                    <th class="px-6 py-4 font-semibold">ID</th>
                    <th class="px-6 py-4 font-semibold">Username / Email</th>
                    <th class="px-6 py-4 font-semibold text-right">Số Dư</th>
                    <th class="px-6 py-4 font-semibold">Quyền</th>
                    <th class="px-6 py-4 font-semibold">Ngày ĐK</th>
                    <th class="px-6 py-4 font-semibold text-center">Thao Tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach($users as $u): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 font-mono">#<?= $u['id'] ?></td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-800"><?= htmlspecialchars($u['username']) ?></div>
                            <div class="text-xs text-slate-500"><?= htmlspecialchars($u['email']) ?></div>
                        </td>
                        <td class="px-6 py-4 font-bold text-emerald-600 text-right">
                            <?= format_currency($u['balance']) ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if($u['role']==='admin'): ?>
                                <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs font-bold">Admin</span>
                            <?php elseif($u['role']==='manager'): ?>
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-bold border border-blue-200">Manager</span>
                            <?php else: ?>
                                <span class="bg-slate-100 text-slate-600 px-2 py-1 rounded text-xs font-medium border border-slate-200">User</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-slate-500"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick="openBalanceModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>', <?= $u['balance'] ?>)" class="text-indigo-600 bg-indigo-50 hover:bg-indigo-600 hover:text-white px-3 py-1.5 rounded transition-colors tooltip flex-1 whitespace-nowrap" title="Cộng/Trừ tiền">
                                    <i class="fa-solid fa-coins"></i> ± Tiền
                                </button>
                                <?php if(is_admin() && $u['id'] !== $_SESSION['user_id']): ?>
                                <button onclick="openRoleModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>', '<?= $u['role'] ?>')" class="text-emerald-600 bg-emerald-50 hover:bg-emerald-600 hover:text-white px-3 py-1.5 rounded transition-colors tooltip flex-1 whitespace-nowrap" title="Phân Quyền">
                                    <i class="fa-solid fa-user-shield"></i> Quyền
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Paginator -->
    <?php if($total_pages > 1): ?>
    <div class="p-4 border-t border-slate-100 flex gap-2 justify-center">
        <?php for($i=1; $i<=$total_pages; $i++): ?>
            <a href="?page=<?= $i ?><?= $search ? '&q='.urlencode($search) : '' ?>" class="px-3 py-1 rounded border <?= $i==$page ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50' ?> text-sm">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Balance Modal -->
<div id="balanceModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center px-4">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeBalanceModal()"></div>
    <div class="bg-white rounded-2xl shadow-2xl z-10 w-full max-w-md overflow-hidden transform transition-all scale-95 opacity-0 duration-200" id="balanceModalContent">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-lg text-slate-800">Cập Nhật Số Dư</h3>
            <button onclick="closeBalanceModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <form action="" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="update_balance">
            <input type="hidden" name="user_id" id="modal_user_id">
            
            <div>
                <label class="block text-sm font-medium text-slate-700">Tài Khoản</label>
                <input type="text" id="modal_username" readonly class="mt-1 block w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-600 font-bold">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <label class="cursor-pointer">
                    <input type="radio" name="type" value="add" class="peer sr-only" checked>
                    <div class="rounded-xl border border-slate-200 p-3 text-center peer-checked:bg-emerald-50 peer-checked:border-emerald-500 peer-checked:text-emerald-700 font-medium transition-all">
                        <i class="fa-solid fa-plus-circle mb-1 text-lg block"></i> Cộng Tiền
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="type" value="deduct" class="peer sr-only">
                    <div class="rounded-xl border border-slate-200 p-3 text-center peer-checked:bg-red-50 peer-checked:border-red-500 peer-checked:text-red-700 font-medium transition-all">
                        <i class="fa-solid fa-minus-circle mb-1 text-lg block"></i> Trừ Tiền
                    </div>
                </label>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700">Số Tiền (VNĐ) <span class="text-red-500">*</span></label>
                <input type="number" name="amount" min="1" required class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            
            <button type="submit" class="w-full mt-4 bg-indigo-600 text-white font-bold py-3 rounded-xl hover:bg-indigo-700 transition-colors">Xác Nhận</button>
        </form>
    </div>
</div>

<script>
    function openBalanceModal(id, username, currentBalance) {
        document.getElementById('modal_user_id').value = id;
        document.getElementById('modal_username').value = username + ' (Đang có: ' + currentBalance.toLocaleString('vi-VN') + ' đ)';
        
        const modal = document.getElementById('balanceModal');
        const content = document.getElementById('balanceModalContent');
        
        modal.classList.remove('hidden');
        // Slight delay to allow display:block to apply before animating opacity/transform
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }
    
    function closeBalanceModal() {
        const modal = document.getElementById('balanceModal');
        const content = document.getElementById('balanceModalContent');
        
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 200);
    }

    // Role Modal
    function openRoleModal(id, username, currentRole) {
        document.getElementById('modal_role_user_id').value = id;
        document.getElementById('modal_role_username').value = username;
        
        // Select current role radio
        if(document.getElementById('role_' + currentRole)) {
            document.getElementById('role_' + currentRole).checked = true;
        }
        
        const modal = document.getElementById('roleModal');
        const content = document.getElementById('roleModalContent');
        
        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }
    
    function closeRoleModal() {
        const modal = document.getElementById('roleModal');
        const content = document.getElementById('roleModalContent');
        
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 200);
    }
</script>

<!-- Role Modal -->
<div id="roleModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center px-4">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeRoleModal()"></div>
    <div class="bg-white rounded-2xl shadow-2xl z-10 w-full max-w-md overflow-hidden transform transition-all scale-95 opacity-0 duration-200" id="roleModalContent">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-lg text-slate-800">Phân Quyền Tài Khoản</h3>
            <button type="button" onclick="closeRoleModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <form action="<?= BASE_URL ?>admin/users" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="set_role">
            <input type="hidden" name="user_id" id="modal_role_user_id">
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tài Khoản</label>
                <input type="text" id="modal_role_username" readonly class="mt-1 block w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-slate-600 font-bold">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-3">Chọn Quyền Hạn</label>
                <div class="space-y-3">
                    <label class="flex items-center p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition-colors">
                        <input type="radio" name="role" value="user" id="role_user" class="w-4 h-4 text-emerald-600 focus:ring-emerald-500 border-gray-300">
                        <span class="ml-3 block">
                            <span class="block text-sm font-medium text-slate-900">Người dùng (User)</span>
                            <span class="block text-xs text-slate-500 mt-0.5">Thành viên bình thường mua sắm trên web.</span>
                        </span>
                    </label>

                    <label class="flex items-center p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition-colors">
                        <input type="radio" name="role" value="manager" id="role_manager" class="w-4 h-4 text-emerald-600 focus:ring-emerald-500 border-gray-300">
                        <span class="ml-3 block">
                            <span class="block text-sm font-medium text-slate-900">Quản lý (Manager)</span>
                            <span class="block text-xs text-slate-500 mt-0.5">Trợ lý admin, quản lý Sản phẩm, Ticket & Mã Giảm Giá.</span>
                        </span>
                    </label>

                    <label class="flex items-center p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition-colors">
                        <input type="radio" name="role" value="admin" id="role_admin" class="w-4 h-4 text-emerald-600 focus:ring-emerald-500 border-gray-300">
                        <span class="ml-3 block">
                            <span class="block text-sm font-medium text-slate-900">Quản trị viên (Admin)</span>
                            <span class="block text-xs text-slate-500 mt-0.5">Trùm cuối. Quyền sinh quyền sát cực hạn.</span>
                        </span>
                    </label>
                </div>
            </div>
            
            <button type="submit" class="w-full mt-4 bg-emerald-600 text-white font-bold py-3 rounded-xl hover:bg-emerald-700 transition-colors">Cập Nhật Quyền</button>
        </form>
    </div>
</div>

<?php require 'views/admin/layout_footer.php'; ?>
