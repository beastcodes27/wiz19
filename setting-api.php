<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';

session_start();
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token.");
    }
    
    $apiKey = trim($_POST['APIKey'] ?? '');
    $monetizationMode = ($_POST['monetization_mode'] ?? 'single') === 'channel' ? 'channel' : 'single';
    $globalRedirectUrl = trim($_POST['global_redirect_url'] ?? '');
    
    $stmt = $pdo->prepare("UPDATE users SET gateway_api_key = ?, monetization_mode = ?, global_redirect_url = ? WHERE id = ?");
    $stmt->execute([$apiKey, $monetizationMode, $globalRedirectUrl, $_SESSION['user_id']]);
    
    header("Location: settings?msg=success");
    exit;
}
?>
