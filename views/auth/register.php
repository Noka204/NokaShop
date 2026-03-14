<?php
// views/auth/register.php
if (is_logged_in()) {
    redirect('dashboard');
}

$error = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $repassword = $_POST['repassword'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Vui lòng điền đầy đủ thông tin.';
    } elseif ($password !== $repassword) {
        $error = 'Mật khẩu nhập lại không khớp.';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Định dạng email không hợp lệ.';
    } else {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Tên đăng nhập đã tồn tại.';
        } else {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Email này đã được sử dụng.';
            } else {
                // Insert new user (bcrypt)
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, balance, role) VALUES (?, ?, ?, 0, 'user')");
                if ($stmt->execute([$username, $hashed_password, $email])) {
                    set_flash_message('success', 'Đăng ký thành công! Vui lòng đăng nhập.');
                    redirect('login');
                } else {
                    $error = 'Có lỗi xảy ra, vui lòng thử lại sau.';
                }
            }
        }
    }
}

require __DIR__ . '/../../header.php';
?>

<!-- Thêm trực tiếp CSS Bootstrap vào trang này để đồng bộ giao diện Index mà không ảnh hưởng Dashboard -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container d-flex justify-content-center align-items-center" style="min-height: calc(100vh - 150px); padding-top: 5rem; padding-bottom: 5rem; font-family: 'Inter', sans-serif;">
    <div class="card shadow-sm rounded-3 border-0" style="max-width: 500px; width: 100%; border: 1px solid var(--border-color) !important;">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-5">
                <div class="bg-dark text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 60px; height: 60px;">
                    <i class="fa-solid fa-user-plus fs-3"></i>
                </div>
                <h2 class="fw-bold mb-2">Tạo tài khoản mới</h2>
                <p class="text-muted mb-0">Đã có tài khoản? <a href="<?= BASE_URL ?>login" class="fw-bold text-dark text-decoration-none">Đăng nhập tại đây</a></p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>register" method="POST">
                <?= csrf_field() ?>
                <div class="mb-4">
                    <label for="username" class="form-label fw-semibold">Tên đăng nhập</label>
                    <input id="username" name="username" type="text" value="<?= htmlspecialchars($username) ?>" class="form-control form-control-lg bg-light border-0" required placeholder="Tên đăng nhập (Ví dụ: noka123)" style="font-size: 1rem;">
                </div>
                <div class="mb-4">
                    <label for="email" class="form-label fw-semibold">Địa chỉ Email</label>
                    <input id="email" name="email" type="email" value="<?= htmlspecialchars($email) ?>" class="form-control form-control-lg bg-light border-0" required placeholder="Địa chỉ Email" style="font-size: 1rem;">
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">Mật khẩu</label>
                    <input id="password" name="password" type="password" class="form-control form-control-lg bg-light border-0" required placeholder="Mật khẩu (ít nhất 6 ký tự)" style="font-size: 1rem;">
                </div>
                <div class="mb-5">
                    <label for="repassword" class="form-label fw-semibold">Nhập lại mật khẩu</label>
                    <input id="repassword" name="repassword" type="password" class="form-control form-control-lg bg-light border-0" required placeholder="Nhập lại mật khẩu" style="font-size: 1rem;">
                </div>

                <button type="submit" class="btn btn-dark btn-lg w-100 fw-bold py-3 rounded-1">
                    Đăng Ký Tài Khoản
                </button>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../footer.php'; ?>
