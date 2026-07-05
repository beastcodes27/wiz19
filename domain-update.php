<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';

session_start();
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token.");
    }

    $domain_id     = (int) ($_POST['domain_id'] ?? 0);
    $domain_prefix = trim($_POST['domain_prefix'] ?? '');

    if ($domain_id <= 0 || empty($domain_prefix) || !preg_match('/^[a-z0-9][a-z0-9\-]*$/i', $domain_prefix)) {
        header("Location: domain?msg=invalid_prefix");
        exit;
    }

    // Check not already taken by another domain (excluding this one)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM domains WHERE domain_prefix = ? AND id != ?");
    $stmt->execute([$domain_prefix, $domain_id]);
    if ($stmt->fetchColumn() > 0) {
        header("Location: domain?msg=domain_taken");
        exit;
    }

    // Only update if this domain belongs to the logged-in user
    $stmt = $pdo->prepare("UPDATE domains SET domain_prefix = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$domain_prefix, $domain_id, $_SESSION['user_id']]);

    header("Location: domain?msg=updated");
    exit;
}
?>
