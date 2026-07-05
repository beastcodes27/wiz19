<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';

session_start();
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token.");
    }

    $name = trim($_POST['package_name'] ?? '');
    $price = (float) ($_POST['package_price'] ?? 0);
    $days = (int) ($_POST['package_day'] ?? 0);

    if (empty($name) || $price <= 0 || $days <= 0) {
        header("Location: package?msg=invalid");
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO packages (user_id, name, price, duration_days) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $name, $price, $days]);

    header("Location: package?msg=success");
    exit;
}
?>
