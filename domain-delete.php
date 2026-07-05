<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';

session_start();
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token.");
    }

    $domain_id = (int) ($_POST['domain_id'] ?? 0);

    $stmt = $pdo->prepare("DELETE FROM domains WHERE id = ? AND user_id = ?");
    $stmt->execute([$domain_id, $_SESSION['user_id']]);

    header("Location: domain?msg=deleted");
    exit;
}
?>
