<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';

session_start();
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token.");
    }

    $domain_prefix = trim($_POST['domain_prefix'] ?? '');
    // Basic validation for subdomain format
    if (empty($domain_prefix) || !preg_match('/^[a-z0-9-]+$/i', $domain_prefix)) {
        // Redirect back with an error (for now just simple redirect)
        header("Location: domain?msg=invalid_prefix");
        exit;
    }

    // Check if domain exists globally
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM domains WHERE domain_prefix = ?");
    $stmt->execute([$domain_prefix]);
    if ($stmt->fetchColumn() > 0) {
        header("Location: domain?msg=domain_taken");
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO domains (user_id, domain_prefix, status) VALUES (?, ?, 'Connected')");
    $stmt->execute([$_SESSION['user_id'], $domain_prefix]);

    header("Location: domain?msg=success");
    exit;
}
?>
