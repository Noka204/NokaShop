<?php
// views/auth/login.php
if (is_logged_in()) {
    redirect('dashboard');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Vui lòng nhập Email và Mật khẩu.';
    } else {
        // Find user by email only
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Hỗ trợ cả password_hash (bcrypt) và MD5 cũ (tự động nâng cấp)
            $password_valid = false;
            if (password_verify($password, $user['password'])) {
                $password_valid = true;
            } elseif ($user['password'] === md5($password)) {
                // Nâng cấp MD5 lên bcrypt
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$new_hash, $user['id']]);
                $password_valid = true;
            }

            if ($password_valid) {
                // Regenerate session ID để chống Session Fixation
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['balance'] = $user['balance'];
                
                set_flash_message('success', 'Đăng nhập thành công!');
                redirect('dashboard');
            } else {
                $error = 'Tài khoản hoặc mật khẩu không chính xác.';
            }
        } else {
            $error = 'Tài khoản hoặc mật khẩu không chính xác.';
        }
    }
}

require __DIR__ . '/../../header.php';
?>

<!-- Thêm trực tiếp CSS Bootstrap vào trang này để đồng bộ giao diện Index mà không ảnh hưởng Dashboard -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container d-flex justify-content-center align-items-center" style="min-height: calc(100vh - 150px); padding-top: 5rem; padding-bottom: 5rem; font-family: 'Inter', sans-serif;">
    <div class="card shadow-sm rounded-3 border-0" style="max-width: 450px; width: 100%; border: 1px solid var(--border-color) !important;">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-5">
                <div class="bg-dark text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 60px; height: 60px;">
                    <i class="fa-solid fa-lock fs-3"></i>
                </div>
                <h2 class="fw-bold mb-2">Chào mừng trở lại</h2>
                <p class="text-muted mb-0">Chưa có tài khoản? <a href="<?= BASE_URL ?>register" class="fw-bold text-dark text-decoration-none">Đăng ký ngay</a></p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <?php display_flash_message('success'); ?>

            <form action="<?= BASE_URL ?>login" method="POST">
                <?= csrf_field() ?>
                <div class="mb-4">
                    <label for="email" class="form-label fw-semibold">Email đăng nhập</label>
                    <input id="email" name="email" type="email" class="form-control form-control-lg bg-light border-0" required placeholder="Nhập địa chỉ email" style="font-size: 1rem;">
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">Mật khẩu</label>
                    <input id="password" name="password" type="password" class="form-control form-control-lg bg-light border-0" required placeholder="Nhập mật khẩu" style="font-size: 1rem;">
                </div>

                <div class="d-flex justify-content-between align-items-center mb-5 small">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="remember-me">
                        <label class="form-check-label text-muted" for="remember-me">
                            Ghi nhớ đăng nhập
                        </label>
                    </div>
                    <a href="<?= BASE_URL ?>forgot_password" class="text-dark fw-semibold text-decoration-none">Quên mật khẩu?</a>
                </div>

                <button type="submit" class="btn btn-dark btn-lg w-100 fw-bold py-3 rounded-1">
                    <i class="fa-solid fa-arrow-right-to-bracket me-2 text-white-50"></i> Đăng Nhập
                </button>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../footer.php'; ?>
