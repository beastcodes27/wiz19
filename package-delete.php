<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';

session_start();
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token.");
    }

    $id = (int) ($_POST['id'] ?? 0);

    // Only delete if the package belongs to this user
    $stmt = $pdo->prepare("DELETE FROM packages WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);

    header("Location: package?msg=deleted");
    exit;
}
?>
