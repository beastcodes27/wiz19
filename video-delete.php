<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }

    $id = (int)($_POST['id'] ?? 0);
    $user_id = $_SESSION['user_id'];

    if ($id > 0) {
        // Verify ownership before deleting
        $stmt = $pdo->prepare("SELECT id FROM videos WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        if ($stmt->fetch()) {
            // Soft delete
            $deleteStmt = $pdo->prepare("UPDATE videos SET status = 'deleted' WHERE id = ? AND user_id = ?");
            $deleteStmt->execute([$id, $user_id]);
        }
    }
}

header('Location: videos');
exit;
