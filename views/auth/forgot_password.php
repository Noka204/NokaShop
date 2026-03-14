<?php
// views/auth/forgot_password.php
if (is_logged_in()) {
    redirect('dashboard');
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = '';
$step = isset($_SESSION['reset_step']) ? (int)$_SESSION['reset_step'] : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    
    // Step 1: Request OTP
    if ($step === 1 && isset($_POST['email'])) {
        $email = trim($_POST['email']);
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email không hợp lệ.';
        } else {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate 6 digit OTP
                $otp = sprintf("%06d", mt_rand(1, 999999));
                $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                
                // Clear old OTPs for this email
                $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
                
                // Save new OTP
                $pdo->prepare("INSERT INTO password_resets (email, otp_code, expires_at) VALUES (?, ?, ?)")
                    ->execute([$email, $otp, $expires_at]);

                // Send Email via PHPMailer
                require_once 'includes/PHPMailer/Exception.php';
                require_once 'includes/PHPMailer/PHPMailer.php';
                require_once 'includes/PHPMailer/SMTP.php';
                
                // Load env if not loaded
                if (!function_exists('load_env')) {
                    require_once __DIR__ . '/../../config/env.php';
                }

                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = env('SMTP_HOST', 'smtp.gmail.com');
                    $mail->SMTPAuth   = true;
                    $mail->Username   = env('SMTP_USER', '');
                    $mail->Password   = env('SMTP_PASS', '');
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = env('SMTP_PORT', 587);
                    $mail->CharSet    = 'UTF-8';

                    $mail->setFrom(env('SMTP_USER', 'trymosly@gmail.com'), 'NokaShop Support');
                    $mail->addAddress($email, $user['username']);

                    $mail->isHTML(true);
                    $mail->Subject = 'Mã xác nhận khôi phục mật khẩu';
                    $mail->Body    = "<div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
                                        <h2>Khôi phục mật khẩu</h2>
                                        <p>Xin chào {$user['username']},</p>
                                        <p>Bạn đã yêu cầu đặt lại mật khẩu. Vui lòng sử dụng mã OTP gồm 6 chữ số dưới đây để tiếp tục. Mã này sẽ hết hạn sau 15 phút.</p>
                                        <div style='background-color: #f4f4f5; padding: 15px; margin: 20px 0; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 5px; border-radius: 8px;'>{$otp}</div>
                                        <p>Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này.</p>
                                      </div>";

                    $mail->send();
                    
                    $_SESSION['reset_email'] = $email;
                    $_SESSION['reset_step'] = 2;
                    $step = 2;
                    $success = 'Mã OTP đã được gửi đến email của bạn.';
                } catch (Exception $e) {
                    $error = "Không thể gửi email. Lỗi: {$mail->ErrorInfo}";
                }
            } else {
                // To prevent email enumeration, still pretend we sent it
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_step'] = 2;
                $step = 2;
                $success = 'Mã OTP đã được gửi đến email của bạn nếu hệ thống tìm thấy tài khoản.';
            }
        }
    } 
    // Step 2: Verify OTP
    elseif ($step === 2 && isset($_POST['otp'])) {
        $otp = trim($_POST['otp']);
        $email = $_SESSION['reset_email'] ?? '';
        
        if (empty($otp)) {
            $error = 'Vui lòng nhập mã OTP.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM password_resets WHERE email = ? AND otp_code = ? AND expires_at > NOW()");
            $stmt->execute([$email, $otp]);
            
            if ($stmt->fetch()) {
                $_SESSION['reset_step'] = 3;
                $step = 3;
                $success = 'Xác minh thành công. Vui lòng nhập mật khẩu mới.';
            } else {
                $error = 'Mã OTP không hợp lệ hoặc đã hết hạn.';
            }
        }
    }
    // Step 3: Change Password
    elseif ($step === 3 && isset($_POST['new_password']) && isset($_POST['confirm_password'])) {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $email = $_SESSION['reset_email'] ?? '';
        
        if (empty($new_password) || empty($confirm_password)) {
            $error = 'Vui lòng nhập đầy đủ thông tin.';
        } elseif (strlen($new_password) < 6) {
            $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Mật khẩu xác nhận không khớp.';
        } else {
            // Update password
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            if ($stmt->execute([$hashed, $email])) {
                // Cleanup
                $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
                unset($_SESSION['reset_step']);
                unset($_SESSION['reset_email']);
                
                set_flash_message('success', 'Mật khẩu đã được thay đổi thành công. Bạn có thể đăng nhập ngay bây giờ.');
                redirect('login');
            } else {
                $error = 'Có lỗi xảy ra, vui lòng thử lại sau.';
            }
        }
    }
}

// Reset process if user clicks back to start
if (isset($_GET['cancel'])) {
    unset($_SESSION['reset_step']);
    unset($_SESSION['reset_email']);
    $step = 1;
}

require __DIR__ . '/../../header.php';
?>

<!-- Thêm trực tiếp CSS Bootstrap vào trang này để đồng bộ giao diện Index -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container d-flex justify-content-center align-items-center" style="min-height: calc(100vh - 150px); padding-top: 5rem; padding-bottom: 5rem; font-family: 'Inter', sans-serif;">
    <div class="card shadow-sm rounded-3 border-0" style="max-width: 450px; width: 100%; border: 1px solid var(--border-color) !important;">
        <div class="card-body p-4 p-md-5">
            <?php if ($step === 1): ?>
                <h3 class="fw-bold mb-3 text-center text-dark" style="letter-spacing: -0.5px;">Quên mật khẩu?</h3>
                <p class="text-muted text-center mb-4 pb-2" style="font-size: 0.95rem;">Đừng lo lắng! Nhập email của bạn và chúng tôi sẽ gửi mã khôi phục cho bạn.</p>
            <?php elseif ($step === 2): ?>
                <h3 class="fw-bold mb-3 text-center text-dark" style="letter-spacing: -0.5px;">Xác thực OTP</h3>
                <p class="text-muted text-center mb-4 pb-2" style="font-size: 0.95rem;">Nhập mã gồm 6 chữ số vừa được gửi đến <strong><?= htmlspecialchars($_SESSION['reset_email']) ?></strong>.</p>
            <?php elseif ($step === 3): ?>
                <h3 class="fw-bold mb-3 text-center text-dark" style="letter-spacing: -0.5px;">Tạo mật khẩu mới</h3>
                <p class="text-muted text-center mb-4 pb-2" style="font-size: 0.95rem;">Mật khẩu mới của bạn phải khác với các mật khẩu đã sử dụng trước đây.</p>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2 px-3 small border-0" style="background: #fee2e2; color: #991b1b; border-radius: 8px;">
                    <i class="bi bi-exclamation-octagon me-1"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success py-2 px-3 small border-0" style="background: #dcfce7; color: #166534; border-radius: 8px;">
                    <i class="bi bi-check-circle me-1"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>forgot_password">
                <?= csrf_field() ?>
                
                <?php if ($step === 1): ?>
                    <div class="mb-4">
                        <label class="form-label text-dark fw-semibold small mb-2">Email đăng nhập</label>
                        <input type="email" name="email" class="form-control form-control-lg bg-light" style="font-size: 0.95rem; border: 1px solid #e2e8f0; border-radius: 8px;" placeholder="name@example.com" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-dark btn-lg w-100 fw-bold mb-3" style="border-radius: 8px; font-size: 0.95rem;">
                        Gửi mã khôi phục
                    </button>
                    <div class="text-center">
                        <a href="<?= BASE_URL ?>login" class="text-decoration-none text-muted small fw-medium">
                            <i class="bi bi-arrow-left me-1"></i> Quay lại đăng nhập
                        </a>
                    </div>

                <?php elseif ($step === 2): ?>
                    <div class="mb-4">
                        <label class="form-label text-dark fw-semibold small mb-2">Mã OTP</label>
                        <input type="text" name="otp" class="form-control form-control-lg bg-light text-center fw-bold" style="font-size: 1.25rem; letter-spacing: 4px; border: 1px solid #e2e8f0; border-radius: 8px;" placeholder="------" maxlength="6" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-dark btn-lg w-100 fw-bold mb-3" style="border-radius: 8px; font-size: 0.95rem;">
                        Xác minh
                    </button>
                    <div class="text-center">
                        <a href="<?= BASE_URL ?>forgot_password?cancel=1" class="text-decoration-none text-muted small fw-medium">
                            Nhập lại email khác
                        </a>
                    </div>

                <?php elseif ($step === 3): ?>
                    <div class="mb-3">
                        <label class="form-label text-dark fw-semibold small mb-2">Mật khẩu mới</label>
                        <input type="password" name="new_password" class="form-control form-control-lg bg-light" style="font-size: 0.95rem; border: 1px solid #e2e8f0; border-radius: 8px;" placeholder="Ít nhất 6 ký tự" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-dark fw-semibold small mb-2">Nhập lại mật khẩu mới</label>
                        <input type="password" name="confirm_password" class="form-control form-control-lg bg-light" style="font-size: 0.95rem; border: 1px solid #e2e8f0; border-radius: 8px;" placeholder="Xác nhận mật khẩu" required>
                    </div>
                    <button type="submit" class="btn btn-dark btn-lg w-100 fw-bold mb-3" style="border-radius: 8px; font-size: 0.95rem;">
                        Lưu mật khẩu và đăng nhập
                    </button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../footer.php'; ?>
