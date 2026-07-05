<?php
require_once __DIR__ . '/../includes/load_user.php';

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');
if (empty($query)) {
    echo json_encode([]);
    exit;
}

$isAdmin = isset($user['role']) && $user['role'] === 'admin';
$userId = $user['id'] ?? 0;

try {
    if ($isAdmin) {
        // Admin can search all videos
        $stmt = $pdo->prepare("
            SELECT id, title, slug, thumbnail_url 
            FROM videos 
            WHERE (title LIKE ? OR id = ?) AND status != 'deleted' 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $stmt->execute(["%$query%", (int)$query]);
    } else {
        // Creator can only search their own videos
        $stmt = $pdo->prepare("
            SELECT id, title, slug, thumbnail_url 
            FROM videos 
            WHERE user_id = ? AND (title LIKE ? OR id = ?) AND status != 'deleted' 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $stmt->execute([$userId, "%$query%", (int)$query]);
    }
    
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($videos);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
