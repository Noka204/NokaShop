<?php
// includes/functions.php

/**
 * Generate a CSRF token and store in session
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generate a hidden CSRF input field for forms
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Verify CSRF token from POST request
 */
function verify_csrf_token() {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Yêu cầu không hợp lệ (CSRF). Vui lòng thử lại.');
    }
    // Regenerate token after successful verification
    unset($_SESSION['csrf_token']);
}

/**
 * Check if the user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if the current user is an admin
 */
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if the current user is a manager or admin
 */
function is_manager() {
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'manager']);
}

/**
 * Redirect to a specific route
 */
function redirect($route) {
    header("Location: " . BASE_URL . $route);
    exit();
}

/**
 * Format currency to VND
 */
function format_currency($amount) {
    return number_format($amount, 0, ',', '.') . ' VNĐ';
}

/**
 * Get current user data
 */
function get_current_user_data($pdo) {
    if (!is_logged_in()) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Set a flash message
 */
function set_flash_message($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

/**
 * Display and clear a flash message
 */
function display_flash_message($type) {
    if (isset($_SESSION['flash'][$type])) {
        $msg = htmlspecialchars($_SESSION['flash'][$type]);
        unset($_SESSION['flash'][$type]);
        
        // Output a hidden element that the JS in dashboard_footer.php will pick up
        echo "<div class='php-flash-message' style='display: none;' data-type='{$type}' data-message='{$msg}'></div>";
    }
}
/**
 * Generate a proxied image URL to hide the original source
 */
function get_proxy_image_url($url) {
    if (empty($url)) return '';
    // Nếu là ảnh local (bắt đầu bằng assets/ hoặc uploads/)
    if (strpos($url, 'assets/') === 0 || strpos($url, 'uploads/') === 0) {
        return BASE_URL . $url;
    }
    // Encode URL using base64url
    $encoded = rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
    return BASE_URL . "index.php?route=api/img-proxy&url=" . $encoded;
}
?>
