<?php
/**
 * Authentication and Security functions
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is an admin
 */
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Redirect to login if not logged in
 */
function require_login($redirect_url = null) {
    if ($redirect_url === null) {
        $redirect_url = (defined('BASE_URL') ? BASE_URL : '') . '/login.php';
    }
    if (!is_logged_in()) {
        header("Location: $redirect_url");
        exit;
    }
}

/**
 * Redirect to dashboard or login if not an admin
 */
function require_admin($redirect_url = null) {
    if ($redirect_url === null) {
        $redirect_url = (defined('BASE_URL') ? BASE_URL : '') . '/login.php';
    }
    if (!is_logged_in()) {
        header("Location: $redirect_url");
        exit;
    }
    if (!is_admin()) {
        $dashboard_url = (defined('BASE_URL') ? BASE_URL : '') . '/dashboard.php';
        header("Location: $dashboard_url");
        exit;
    }
}

/**
 * Generate CSRF Token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output CSRF Token as a hidden field
 */
function csrf_field() {
    $token = generate_csrf_token();
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}
?>
