<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';

session_start();
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token.");
    }
    
    $activeLanding = trim($_POST['cardSelection'] ?? 'landing1');
    
    $stmt = $pdo->prepare("UPDATE users SET active_landing = ? WHERE id = ?");
    $stmt->execute([$activeLanding, $_SESSION['user_id']]);
    
    header("Location: settings?msg=success");
    exit;
}
?>
