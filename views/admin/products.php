<?php
// views/admin/products.php

// 1. Handle POST Logic (Must be before any output)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $id = $_POST['id'] ?? '';
        $cat_id = (int)$_POST['category_id'];
        $name = trim($_POST['name'] ?? '');
        $price = (float)$_POST['price'];
        $stock = (int)($_POST['stock'] ?? 0);
        $desc = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'active';
        $download_link = trim($_POST['download_link'] ?? '');
        
        if (empty($name) || $price < 0 || !$cat_id) {
            set_flash_message('error', 'Vui lòng điền đầy đủ thông tin Tên, Giá và chọn Danh mục hợp lệ.');
        } else {
            // Need to check image for saving
            $existing_image = null;
            if ($id) {
                $checkStmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
                $checkStmt->execute([$id]);
                $existing_image = $checkStmt->fetchColumn();
            }
            $image_path = $existing_image;

            // Handle Image Upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/products/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('prod_') . '.' . $ext;
                $target = $upload_dir . $filename;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                    if ($existing_image && file_exists($existing_image)) {
                        unlink($existing_image);
                    }
                    $image_path = $target;
                }
            }

            try {
                if ($id) {
                    $stmt = $pdo->prepare("UPDATE products SET category_id = ?, name = ?, price = ?, stock = ?, description = ?, status = ?, image = ?, download_link = ? WHERE id = ?");
                    $stmt->execute([$cat_id, $name, $price, $stock, $desc, $status, $image_path, $download_link, $id]);
                    set_flash_message('success', 'Đã cập nhật dịch vụ.');
                } else {
                    $stmt = $pdo->prepare("INSERT INTO products (category_id, name, price, stock, description, status, image, download_link) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$cat_id, $name, $price, $stock, $desc, $status, $image_path, $download_link]);
                    set_flash_message('success', 'Đã thêm dịch vụ mới.');
                }
                redirect('admin/products');
            } catch (Exception $e) {
                set_flash_message('error', 'Lỗi Database: ' . $e->getMessage());
                // Không redirect để người dùng thấy lỗi nếu cần, hoặc redirect để hiện flash
                redirect('admin/products');
            }
        }
    } elseif ($action === 'delete') {
        try {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE product_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                set_flash_message('error', 'Không thể xóa dịch vụ này vì đã có đơn hàng liên kết. Vui lòng chuyển trạng thái thành Đang ẩn.');
            } else {
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$id]);
                set_flash_message('success', 'Đã xóa dịch vụ.');
            }
        } catch (Exception $e) {
            set_flash_message('error', 'Lỗi xóa sản phẩm: ' . $e->getMessage());
        }
        redirect('admin/products');
    }
}

// 2. Prepare Data for Display
$categories = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();

// Auto-migration
try {
    $pdo->query("SELECT image FROM products LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE products ADD COLUMN image VARCHAR(255) DEFAULT NULL AFTER description");
}
try {
    $pdo->query("SELECT stock FROM products LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE products ADD COLUMN stock INT DEFAULT 0 AFTER price");
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editing = $stmt->fetch();
}

// 3. Output Header
require 'views/admin/layout_header.php';

if (empty($categories)) {
    echo "<div class='p-6 bg-yellow-50 text-yellow-800 rounded border border-yellow-200 mb-6'>Vui lòng tạo Danh Mục trước khi thêm sản phẩm. <a href='".BASE_URL."admin/categories' class='font-bold underline'>Chuyển tới Danh Mục</a></div>";
    require 'views/admin/layout_footer.php';
    exit;
}

// Search & List Products
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$where = "1=1";
$params = [];
if ($search !== '') {
    $where = "p.name LIKE ?";
    $params[] = "%$search%";
}

$query = "
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE $where
    ORDER BY p.id DESC
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Quản Lý Gói Dịch Vụ</h1>
        <p class="text-slate-500 text-sm mt-1">Sản phẩm, bảng giá và chi tiết dịch vụ.</p>
    </div>
    <form action="" method="GET" class="relative">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Tên dịch vụ..." class="pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 w-full sm:w-64 text-sm">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
    </form>
</div>

<!-- Add/Edit Form, togglable -->
<div class="mb-8 <?= $editing ? '' : 'hidden' ?>" id="formContainer">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-bold text-slate-800 text-lg"><?= $editing ? 'Cập Nhật Dịch Vụ' : 'Thêm Dịch Vụ Mới' ?></h2>
            <?php if(!$editing): ?>
                <button type="button" onclick="document.getElementById('formContainer').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i> Hủy</button>
            <?php endif; ?>
        </div>
        
        <form action="<?= BASE_URL ?>admin/products" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="action" value="save">
            <?php if($editing): ?>
                <input type="hidden" name="id" value="<?= $editing['id'] ?>">
            <?php endif; ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tên Dịch Vụ <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="<?= $editing ? htmlspecialchars($editing['name']) : '' ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Giá Tiền (VNĐ) <span class="text-red-500">*</span></label>
                    <input type="number" name="price" value="<?= $editing ? htmlspecialchars($editing['price']) : '0' ?>" required min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Thuộc Danh Mục <span class="text-red-500">*</span></label>
                    <select name="category_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <?php foreach($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($editing && $editing['category_id'] == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Link Tải Tool / Sản Phẩm</label>
                    <input type="url" name="download_link" value="<?= $editing ? htmlspecialchars($editing['download_link'] ?? '') : '' ?>" placeholder="https://..." class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Số lượng tồn kho</label>
                    <input type="number" name="stock" value="<?= $editing ? (int)$editing['stock'] : '0' ?>" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <p class="text-[10px] text-slate-400 mt-1">Sẽ tự động trừ khi có khách mua.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Trạng Thái Kinh Doanh</label>
                    <select name="status" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="active" <?= ($editing && $editing['status'] == 'active') ? 'selected' : '' ?>>Đang Bán</option>
                        <option value="out_of_stock" <?= ($editing && $editing['status'] == 'out_of_stock') ? 'selected' : '' ?>>Hết Hàng</option>
                        <option value="hidden" <?= ($editing && $editing['status'] == 'hidden') ? 'selected' : '' ?>>Đang Ẩn (Không hiện trên shop)</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Ảnh Sản Phẩm</label>
                    <input type="file" name="image" accept="image/*" class="w-full px-3 py-1.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 text-sm">
                    <?php if($editing && $editing['image']): ?>
                        <div class="mt-2 flex items-center gap-2">
                            <img src="<?= BASE_URL . $editing['image'] ?>" class="w-12 h-12 object-cover rounded border">
                            <span class="text-xs text-slate-500">Ảnh hiện tại</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Mô tả & Hướng dẫn</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500"><?= $editing ? htmlspecialchars($editing['description']) : '' ?></textarea>
            </div>
            
            <div class="pt-2 flex gap-3">
                <button type="submit" class="bg-indigo-600 text-white font-bold py-2.5 px-6 rounded-lg hover:bg-indigo-700 transition shadow-md">
                    <i class="fa-solid fa-floppy-disk mr-1"></i> <?= $editing ? 'Lưu Thay Đổi' : 'Thêm Dịch Vụ Mới' ?>
                </button>
                <?php if($editing): ?>
                    <a href="<?= BASE_URL ?>admin/products" class="bg-slate-100 text-slate-700 font-bold py-2.5 px-6 rounded-lg hover:bg-slate-200 transition">Hủy sửa</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Header for Table -->
<div class="flex justify-between items-center mb-4 <?= $editing ? 'hidden' : '' ?>">
    <h3 class="font-bold text-slate-800 text-lg">Danh Sách Dịch Vụ</h3>
    <button onclick="document.getElementById('formContainer').classList.remove('hidden')" class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-emerald-700 shadow-sm transition-colors text-sm flex items-center gap-2">
        <i class="fa-solid fa-plus"></i> Thêm Mới
    </button>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500">
                    <th class="px-6 py-4 font-semibold w-16">ID</th>
                    <th class="px-6 py-4 font-semibold">Tên Dịch Vụ & Danh Mục</th>
                    <th class="px-6 py-4 font-semibold">Giá Tiền</th>
                    <th class="px-6 py-4 font-semibold">Trạng Thái</th>
                    <th class="px-6 py-4 font-semibold text-right">Lựa Chọn</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if(empty($products)): ?>
                    <tr><td colspan="5" class="px-6 py-8 text-center text-slate-500">Chưa có dịch vụ nào phù hợp.</td></tr>
                <?php else: ?>
                    <?php foreach($products as $p): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 font-mono text-slate-500">#<?= $p['id'] ?></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <?php if($p['image']): ?>
                                        <img src="<?= BASE_URL . $p['image'] ?>" class="w-10 h-10 object-cover rounded border">
                                    <?php else: ?>
                                        <div class="w-10 h-10 bg-slate-100 rounded border flex items-center justify-center text-slate-400">
                                            <i class="fa-solid fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="font-bold text-slate-800"><?= htmlspecialchars($p['name']) ?></div>
                                        <div class="text-xs text-indigo-600 font-medium">↳ <?= htmlspecialchars($p['category_name']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-bold text-emerald-600">
                                <?= format_currency($p['price']) ?>
                                <div class="text-[10px] text-slate-400 font-normal">Kho: <?= (int)$p['stock'] ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $c = ['active'=>'bg-emerald-100 text-emerald-800', 'out_of_stock'=>'bg-red-100 text-red-800', 'hidden'=>'bg-slate-100 text-slate-600'];
                                $l = ['active'=>'Đang Bán', 'out_of_stock'=>'Hết Hàng', 'hidden'=>'Đang Ẩn'];
                                ?>
                                <span class="bg-<?= $c[$p['status']] ?> px-2.5 py-1 rounded text-xs font-semibold <?= $c[$p['status']] ?>"><?= $l[$p['status']] ?></span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="?edit=<?= $p['id'] ?>" class="text-blue-600 bg-blue-50 hover:bg-blue-600 hover:text-white px-3 py-1.5 rounded transition tooltip" title="Chỉnh sửa">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <form action="" method="POST" class="inline-block" onsubmit="return confirm('Xác nhận xóa gói dịch vụ này? Nếu đã có người mua, hành động sẽ thất bại hoặc gây lỗi.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="text-red-600 bg-red-50 hover:bg-red-600 hover:text-white px-3 py-1.5 rounded transition tooltip" title="Xóa">
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
