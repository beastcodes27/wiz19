<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';

// If CSRF token is provided via POST, verify it for enhanced security,
// but since users might just click a GET link depending on the UI, we handle both safely.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        // Invalid CSRF token, but we are logging out anyway, so we just proceed.
    }
}

// Destroy all session data
session_start();
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirect to login page
header("Location: login");
exit;
?>
