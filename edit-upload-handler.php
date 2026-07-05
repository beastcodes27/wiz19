<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

$user_id = $_SESSION['user_id'];
$id = (int)($_POST['id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$price = (float) ($_POST['price'] ?? 0);
$status = in_array($_POST['status'] ?? '', ['active', 'pending']) ? $_POST['status'] : 'active';

if ($id <= 0 || empty($title)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

if ($price < 0) {
    echo json_encode(['status' => 'error', 'message' => 'Price cannot be negative']);
    exit;
}

// Fetch existing video to verify ownership and get old file paths
$stmt = $pdo->prepare("SELECT * FROM videos WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);
$video = $stmt->fetch();

if (!$video) {
    echo json_encode(['status' => 'error', 'message' => 'Video not found or access denied']);
    exit;
}

$targetPath = $video['video_url'];
$thumbnail_url = $video['thumbnail_url'];

// Process new video file if provided
if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['video_file'];
    $uploadDir = 'uploads/videos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $ext;
    $newTargetPath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $newTargetPath)) {
        // Delete old video file
        if (!empty($targetPath) && file_exists($targetPath) && is_file($targetPath)) {
            unlink($targetPath);
        }
        $targetPath = $newTargetPath;
        
        // Process base64 thumbnail
        if (!empty($_POST['base64_thumbnail'])) {
            $thumbDir = 'uploads/thumbnails/';
            if (!is_dir($thumbDir)) {
                mkdir($thumbDir, 0777, true);
            }
            
            $base64_string = $_POST['base64_thumbnail'];
            $base64_string = preg_replace('#^data:image/\w+;base64,#i', '', $base64_string);
            $thumbnail_data = base64_decode($base64_string);
            
            if ($thumbnail_data !== false) {
                $thumbFilename = uniqid('thumb_') . '.jpg';
                $thumbPath = $thumbDir . $thumbFilename;
                if (file_put_contents($thumbPath, $thumbnail_data)) {
                    // Delete old thumbnail
                    if (!empty($thumbnail_url) && file_exists($thumbnail_url) && is_file($thumbnail_url)) {
                        unlink($thumbnail_url);
                    }
                    $thumbnail_url = $thumbPath;
                }
            }
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save new video file']);
        exit;
    }
}

// Update database
$update = $pdo->prepare("UPDATE videos SET title = ?, price = ?, status = ?, video_url = ?, thumbnail_url = ? WHERE id = ? AND user_id = ?");
$update->execute([$title, $price, $status, $targetPath, $thumbnail_url, $id, $user_id]);

echo json_encode([
    'status' => 'success', 
    'message' => 'Video updated successfully'
]);
