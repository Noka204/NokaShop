<?php
// views/admin/promotions.php

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = $_POST['id'] ?? '';
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $type = $_POST['type'] ?? 'percent';
        $value = (float)$_POST['value'];
        $min_order = (float)$_POST['min_order'];
        $max_uses = (int)$_POST['max_uses'];
        $starts_at = $_POST['starts_at'] ?: null;
        $expires_at = $_POST['expires_at'] ?: null;
        $status = $_POST['status'] ?? 'active';

        if (empty($code) || $value <= 0) {
            set_flash_message('error', 'Vui lòng nhập đầy đủ Mã và Giá trị giảm giá.');
        } else {
            try {
                if ($id) {
                    $stmt = $pdo->prepare("UPDATE promotions SET code=?, type=?, value=?, min_order=?, max_uses=?, starts_at=?, expires_at=?, status=? WHERE id=?");
                    $stmt->execute([$code, $type, $value, $min_order, $max_uses, $starts_at, $expires_at, $status, $id]);
                    set_flash_message('success', 'Đã cập nhật mã khuyến mãi.');
                } else {
                    $stmt = $pdo->prepare("INSERT INTO promotions (code, type, value, min_order, max_uses, starts_at, expires_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$code, $type, $value, $min_order, $max_uses, $starts_at, $expires_at, $status]);
                    set_flash_message('success', 'Đã tạo mã khuyến mãi mới.');
                }
                redirect('admin/promotions');
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate') !== false) {
                    set_flash_message('error', 'Mã khuyến mãi này đã tồn tại.');
                } else {
                    set_flash_message('error', 'Lỗi: ' . $e->getMessage());
                }
            }
        }
    } elseif ($action === 'delete') {
        try {
            $pdo->prepare("DELETE FROM promotions WHERE id = ?")->execute([$_POST['id']]);
            set_flash_message('success', 'Đã xóa mã khuyến mãi.');
        } catch (Exception $e) {
            set_flash_message('error', 'Lỗi xóa: ' . $e->getMessage());
        }
        redirect('admin/promotions');
    }
}

// Auto-create table if not exists
try {
    $pdo->query("SELECT 1 FROM promotions LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS promotions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE,
        type ENUM('percent', 'fixed') NOT NULL DEFAULT 'percent',
        value DECIMAL(15, 2) NOT NULL DEFAULT 0,
        min_order DECIMAL(15, 2) NOT NULL DEFAULT 0,
        max_uses INT NOT NULL DEFAULT 0,
        used_count INT NOT NULL DEFAULT 0,
        starts_at DATETIME DEFAULT NULL,
        expires_at DATETIME DEFAULT NULL,
        status ENUM('active', 'expired', 'disabled') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// Fetch data
$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM promotions WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editing = $stmt->fetch();
}

$promos = $pdo->query("SELECT * FROM promotions ORDER BY id DESC")->fetchAll();

require 'views/admin/layout_header.php';
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Quản Lý Khuyến Mãi</h1>
        <p class="text-slate-500 text-sm mt-1">Tạo và quản lý mã giảm giá cho khách hàng.</p>
    </div>
</div>

<!-- Add/Edit Form -->
<div class="mb-8 <?= $editing ? '' : 'hidden' ?>" id="formContainer">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-bold text-slate-800 text-lg"><?= $editing ? 'Cập Nhật Mã' : 'Tạo Mã Khuyến Mãi Mới' ?></h2>
            <?php if(!$editing): ?>
                <button type="button" onclick="document.getElementById('formContainer').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i> Hủy</button>
            <?php endif; ?>
        </div>

        <form action="<?= BASE_URL ?>admin/promotions" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="save">
            <?php if($editing): ?>
                <input type="hidden" name="id" value="<?= $editing['id'] ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Mã Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" value="<?= $editing ? htmlspecialchars($editing['code']) : '' ?>" required placeholder="VD: SALE50" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 uppercase font-mono font-bold tracking-wider">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Loại giảm <span class="text-red-500">*</span></label>
                    <select name="type" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="percent" <?= ($editing && $editing['type'] == 'percent') ? 'selected' : '' ?>>Giảm theo % (Phần trăm)</option>
                        <option value="fixed" <?= ($editing && $editing['type'] == 'fixed') ? 'selected' : '' ?>>Giảm cố định (VNĐ)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Giá trị giảm <span class="text-red-500">*</span></label>
                    <input type="number" name="value" value="<?= $editing ? $editing['value'] : '' ?>" required min="0" step="0.01" placeholder="VD: 10 (%) hoặc 50000 (VNĐ)" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Đơn tối thiểu (VNĐ)</label>
                    <input type="number" name="min_order" value="<?= $editing ? $editing['min_order'] : '0' ?>" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Số lần sử dụng tối đa</label>
                    <input type="number" name="max_uses" value="<?= $editing ? $editing['max_uses'] : '0' ?>" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <p class="text-[10px] text-slate-400 mt-1">0 = Không giới hạn</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Trạng thái</label>
                    <select name="status" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="active" <?= ($editing && $editing['status'] == 'active') ? 'selected' : '' ?>>Đang hoạt động</option>
                        <option value="disabled" <?= ($editing && $editing['status'] == 'disabled') ? 'selected' : '' ?>>Tạm dừng</option>
                        <option value="expired" <?= ($editing && $editing['status'] == 'expired') ? 'selected' : '' ?>>Hết hạn</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Bắt đầu từ</label>
                    <input type="datetime-local" name="starts_at" value="<?= $editing && $editing['starts_at'] ? date('Y-m-d\TH:i', strtotime($editing['starts_at'])) : '' ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Hết hạn lúc</label>
                    <input type="datetime-local" name="expires_at" value="<?= $editing && $editing['expires_at'] ? date('Y-m-d\TH:i', strtotime($editing['expires_at'])) : '' ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div class="pt-2 flex gap-3">
                <button type="submit" class="bg-indigo-600 text-white font-bold py-2.5 px-6 rounded-lg hover:bg-indigo-700 transition shadow-md">
                    <i class="fa-solid fa-floppy-disk mr-1"></i> <?= $editing ? 'Lưu Thay Đổi' : 'Tạo Mã Khuyến Mãi' ?>
                </button>
                <?php if($editing): ?>
                    <a href="<?= BASE_URL ?>admin/promotions" class="bg-slate-100 text-slate-700 font-bold py-2.5 px-6 rounded-lg hover:bg-slate-200 transition">Hủy sửa</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Header for Table -->
<div class="flex justify-between items-center mb-4 <?= $editing ? 'hidden' : '' ?>">
    <h3 class="font-bold text-slate-800 text-lg">Danh Sách Mã Khuyến Mãi</h3>
    <button onclick="document.getElementById('formContainer').classList.remove('hidden')" class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-emerald-700 shadow-sm transition-colors text-sm flex items-center gap-2">
        <i class="fa-solid fa-plus"></i> Tạo Mã Mới
    </button>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500">
                    <th class="px-6 py-4 font-semibold">Mã Code</th>
                    <th class="px-6 py-4 font-semibold">Loại & Giá trị</th>
                    <th class="px-6 py-4 font-semibold">Đơn tối thiểu</th>
                    <th class="px-6 py-4 font-semibold">Sử dụng</th>
                    <th class="px-6 py-4 font-semibold">Thời hạn</th>
                    <th class="px-6 py-4 font-semibold">Trạng thái</th>
                    <th class="px-6 py-4 font-semibold text-right">Hành động</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if(empty($promos)): ?>
                    <tr><td colspan="7" class="px-6 py-8 text-center text-slate-500">Chưa có mã khuyến mãi nào.</td></tr>
                <?php else: ?>
                    <?php foreach($promos as $p): ?>
                        <?php
                        $is_expired = ($p['expires_at'] && strtotime($p['expires_at']) < time()) || $p['status'] === 'expired';
                        $is_maxed = $p['max_uses'] > 0 && $p['used_count'] >= $p['max_uses'];
                        ?>
                        <tr class="hover:bg-slate-50 <?= ($is_expired || $is_maxed) ? 'opacity-50' : '' ?>">
                            <td class="px-6 py-4">
                                <span class="font-mono font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded text-sm tracking-wider"><?= htmlspecialchars($p['code']) ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($p['type'] === 'percent'): ?>
                                    <span class="font-bold text-emerald-600"><?= (int)$p['value'] ?>%</span>
                                <?php else: ?>
                                    <span class="font-bold text-emerald-600"><?= format_currency($p['value']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-slate-600"><?= $p['min_order'] > 0 ? format_currency($p['min_order']) : '—' ?></td>
                            <td class="px-6 py-4">
                                <span class="font-bold"><?= $p['used_count'] ?></span>
                                <span class="text-slate-400">/ <?= $p['max_uses'] > 0 ? $p['max_uses'] : '∞' ?></span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                <?php if ($p['starts_at']): ?>
                                    <?= date('d/m/Y H:i', strtotime($p['starts_at'])) ?>
                                <?php else: ?>—<?php endif; ?>
                                <br>→
                                <?php if ($p['expires_at']): ?>
                                    <?= date('d/m/Y H:i', strtotime($p['expires_at'])) ?>
                                <?php else: ?>Vĩnh viễn<?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $sc = ['active'=>'bg-emerald-100 text-emerald-800', 'disabled'=>'bg-yellow-100 text-yellow-800', 'expired'=>'bg-red-100 text-red-800'];
                                $sl = ['active'=>'Hoạt động', 'disabled'=>'Tạm dừng', 'expired'=>'Hết hạn'];
                                ?>
                                <span class="<?= $sc[$p['status']] ?? '' ?> px-2.5 py-1 rounded text-xs font-semibold"><?= $sl[$p['status']] ?? $p['status'] ?></span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="?edit=<?= $p['id'] ?>" class="text-blue-600 bg-blue-50 hover:bg-blue-600 hover:text-white px-3 py-1.5 rounded transition">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <form action="" method="POST" class="inline-block" onsubmit="return confirm('Xóa mã khuyến mãi này?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="text-red-600 bg-red-50 hover:bg-red-600 hover:text-white px-3 py-1.5 rounded transition">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require 'views/admin/layout_footer.php'; ?>
