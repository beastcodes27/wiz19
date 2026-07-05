<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['video_id']) || !isset($data['event_type'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing data']);
    exit;
}

$video_id = (int) $data['video_id'];
$event = $data['event_type'];
$today = date('Y-m-d');

try {
    $pdo->beginTransaction();

    if ($event === 'view') {
        // Update main video table
        $pdo->query("UPDATE videos SET views = views + 1 WHERE id = $video_id");
        
        // Update analytics table
        $stmt = $pdo->prepare("INSERT INTO analytics (video_id, view_date, views) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE views = views + 1");
        $stmt->execute([$video_id, $today]);
        
    } elseif ($event === 'click_cta') {
        // Update main video table
        $pdo->query("UPDATE videos SET clicks = clicks + 1 WHERE id = $video_id");
        
        // Update analytics table
        $stmt = $pdo->prepare("INSERT INTO analytics (video_id, view_date, clicks) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE clicks = clicks + 1");
        $stmt->execute([$video_id, $today]);
    }

    $pdo->commit();
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error']);
}
