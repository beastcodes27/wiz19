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
$title = trim($_POST['title'] ?? '');
$price = (float) ($_POST['price'] ?? 0);

if (empty($title)) {
    echo json_encode(['status' => 'error', 'message' => 'Video title is missing']);
    exit;
}

// Check if post_max_size was exceeded
if (empty($_FILES) && empty($_POST) && isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
    $pms = ini_get('post_max_size');
    echo json_encode(['status' => 'error', 'message' => "The uploaded file exceeds the maximum allowed post size ({$pms})."]);
    exit;
}

if (!isset($_FILES['video_file'])) {
    echo json_encode(['status' => 'error', 'message' => 'Video file is missing']);
    exit;
}

if ($_FILES['video_file']['error'] !== UPLOAD_ERR_OK) {
    $upload_errors = array(
        UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
        UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
        UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.'
    );
    $err_msg = isset($upload_errors[$_FILES['video_file']['error']]) ? $upload_errors[$_FILES['video_file']['error']] : 'Unknown upload error.';
    echo json_encode(['status' => 'error', 'message' => $err_msg]);
    exit;
}

// Generate SEO Slug
$slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
$slug = trim($slug, '-');
// Ensure slug is unique
$stmt = $pdo->prepare("SELECT COUNT(*) FROM videos WHERE slug = ?");
$stmt->execute([$slug]);
if ($stmt->fetchColumn() > 0) {
    $slug .= '-' . time();
}

$file = $_FILES['video_file'];
$uploadDir = 'uploads/videos/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create uploads/videos/ directory. Please check server permissions.']);
        exit;
    }
}

if (!is_writable($uploadDir)) {
    echo json_encode(['status' => 'error', 'message' => "The directory {$uploadDir} is not writable. Please CHMOD to 755 or 777."]);
    exit;
}

// Generate unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '.' . $ext;
$targetPath = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    // Process base64 thumbnail
    $thumbnail_url = null; 
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
                $thumbnail_url = $thumbPath;
            }
        }
    }
    
    // Insert into database
    try {
        $stmt = $pdo->prepare("INSERT INTO videos (user_id, title, slug, video_url, thumbnail_url, price, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute([$user_id, $title, $slug, $targetPath, $thumbnail_url, $price]);
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Upload complete', 
            'slug' => $slug,
            'link' => 'video_view.php?v=' . $slug
        ]);
    } catch (PDOException $e) {
        // Rollback uploaded files to prevent junk accumulation
        if (file_exists($targetPath)) unlink($targetPath);
        if ($thumbnail_url && file_exists($thumbnail_url)) unlink($thumbnail_url);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save file']);
}
