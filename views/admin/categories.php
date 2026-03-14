<?php
// views/admin/categories.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $id = $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'active';
        
        if (empty($name)) {
            set_flash_message('error', 'Tên danh mục không được để trống.');
        } else {
            if ($id) {
                // Update
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $desc, $status, $id]);
                set_flash_message('success', 'Đã cập nhật danh mục.');
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO categories (name, description, status) VALUES (?, ?, ?)");
                $stmt->execute([$name, $desc, $status]);
                set_flash_message('success', 'Đã thêm danh mục mới.');
            }
            redirect('admin/categories');
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        // Check if there are products
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            set_flash_message('error', 'Không thể xóa danh mục đang có dịch vụ. Hãy xóa dịch vụ trước.');
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            set_flash_message('success', 'Đã xóa danh mục.');
        }
        redirect('admin/categories');
    }
}

// Prepare data
$categories = $pdo->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll();

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editing = $stmt->fetch();
}

// Output
require 'views/admin/layout_header.php';
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Quản Lý Danh Mục</h1>
        <p class="text-slate-500 text-sm mt-1">Sắp xếp và phân loại các dịch vụ của bạn.</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Form Side -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sticky top-24">
            <h2 class="font-bold text-slate-800 mb-4"><?= $editing ? 'Sửa Danh Mục' : 'Thêm Mới Danh Mục' ?></h2>
            <form action="<?= BASE_URL ?>admin/categories" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="save">
                <?php if($editing): ?>
                    <input type="hidden" name="id" value="<?= $editing['id'] ?>">
                <?php endif; ?>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tên Danh Mục <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="<?= $editing ? htmlspecialchars($editing['name']) : '' ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Mô tả ngắn</label>
                    <textarea name="description" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500"><?= $editing ? htmlspecialchars($editing['description']) : '' ?></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Trạng thái</label>
                    <select name="status" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="active" <?= ($editing && $editing['status'] === 'active') ? 'selected' : '' ?>>Hiển thị</option>
                        <option value="hidden" <?= ($editing && $editing['status'] === 'hidden') ? 'selected' : '' ?>>Đang ẩn</option>
                    </select>
                </div>
                
                <div class="pt-2 flex gap-2">
                    <button type="submit" class="flex-1 bg-indigo-600 text-white font-bold py-2.5 rounded-lg hover:bg-indigo-700 transition">
                        <?= $editing ? 'Cập Nhật' : 'Thêm Mới' ?>
                    </button>
                    <?php if($editing): ?>
                        <a href="<?= BASE_URL ?>admin/categories" class="flex-1 bg-slate-100 text-slate-700 font-bold py-2.5 rounded-lg hover:bg-slate-200 transition text-center">Hủy</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Table Side -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500">
                            <th class="px-6 py-4 font-semibold w-16">ID</th>
                            <th class="px-6 py-4 font-semibold">Tên Danh Mục</th>
                            <th class="px-6 py-4 font-semibold">Trạng Thái</th>
                            <th class="px-6 py-4 font-semibold text-right">Lựa chọn</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if(empty($categories)): ?>
                            <tr><td colspan="4" class="px-6 py-8 text-center text-slate-500">Chưa có danh mục nào.</td></tr>
                        <?php else: ?>
                            <?php foreach($categories as $cat): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4 font-mono text-slate-500">#<?= $cat['id'] ?></td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-800"><?= htmlspecialchars($cat['name']) ?></div>
                                        <div class="text-xs text-slate-500 max-w-xs truncate" title="<?= htmlspecialchars($cat['description']) ?>"><?= htmlspecialchars($cat['description']) ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if($cat['status'] === 'active'): ?>
                                            <span class="bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded-full text-xs font-semibold">Hiển thị</span>
                                        <?php else: ?>
                                            <span class="bg-slate-100 text-slate-600 px-2.5 py-1 rounded-full text-xs font-semibold">Đã ẩn</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="?edit=<?= $cat['id'] ?>" class="text-blue-600 bg-blue-50 hover:bg-blue-600 hover:text-white px-3 py-1.5 rounded transition" title="Sửa">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <form action="" method="POST" class="inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn xóa danh mục này?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                                <button type="submit" class="text-red-600 bg-red-50 hover:bg-red-600 hover:text-white px-3 py-1.5 rounded transition" title="Xóa">
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
    </div>
</div>

<?php require 'views/admin/layout_footer.php'; ?>
