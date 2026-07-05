<?php
/**
 * Shared user data loader.
 * Include this file at the top of every user-facing page.
 * It enforces login, fetches the current user from DB, and
 * guarantees the $user array always has safe default values.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__ . '/..');
}

require_once BASE_PATH . '/includes/db.php';
require_once BASE_PATH . '/includes/auth_functions.php';

// Enforce authentication — redirects to login if not logged in
require_login();

$user_id = (int) $_SESSION['user_id'];

// Fetch the logged-in user's data
$__userStmt = $pdo->prepare(
    "SELECT id, full_name AS name, email, phone, role, balance,
            gateway_api_key, webhook_url, active_landing, avatar, monetization_mode
     FROM users WHERE id = ? LIMIT 1"
);
$__userStmt->execute([$user_id]);
$user = $__userStmt->fetch(PDO::FETCH_ASSOC);

// If the DB record is gone (deleted account etc.), destroy session and redirect
if (!$user) {
    session_destroy();
    header('Location: ' . (defined('BASE_URL') ? BASE_URL : '') . '/login.php');
    exit;
}

// Safe defaults so pages never throw "Undefined array key" errors
$user = array_merge([
    'id'               => 0,
    'name'             => 'User',
    'email'            => '',
    'phone'            => '',
    'role'             => 'user',
    'balance'          => 0.00,
    'gateway_api_key'  => '',
    'webhook_url'      => '',
    'active_landing'   => 'landing1',
    'monetization_mode'=> 'single',
    'avatar'           => BASE_URL . '/assets/images/avatar/avatar1.webp',
], $user);

// If avatar is empty or it's using the old relative path, use absolute default
if (empty($user['avatar']) || $user['avatar'] === 'assets/images/avatar/avatar1.webp' || $user['avatar'] === '/assets/images/avatar/avatar1.webp') {
    $user['avatar'] = BASE_URL . '/assets/images/avatar/avatar1.webp';
} else if (strpos($user['avatar'], '/assets') === 0 && strpos($user['avatar'], BASE_URL) !== 0) {
    // If the DB has a path starting with /assets but missing BASE_URL, prepend BASE_URL
    $user['avatar'] = BASE_URL . $user['avatar'];
} else if (strpos($user['avatar'], 'assets/') === 0) {
    // If the DB has a relative path, prepend BASE_URL/
    $user['avatar'] = BASE_URL . '/' . $user['avatar'];
}

// Fetch user's unread support tickets count
$user_unread_tickets_count = 0;
if (isset($user_id) && $user_id > 0) {
    $__unreadUserStmt = $pdo->prepare("SELECT COUNT(*) FROM support_tickets WHERE user_id = ? AND user_read = 0");
    $__unreadUserStmt->execute([$user_id]);
    $user_unread_tickets_count = (int) $__unreadUserStmt->fetchColumn();
}

// Fetch admin unread tickets count if the user is an admin
$admin_unread_tickets_count = 0;
if (isset($user['role']) && $user['role'] === 'admin') {
    $admin_unread_tickets_count = (int) $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE admin_read = 0")->fetchColumn();
}
?>

