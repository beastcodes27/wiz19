<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

// Allow POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$video_id = $input['video_id'] ?? null;
$phone = $input['phone'] ?? '';
$name = $input['name'] ?? 'Customer';

if (!$video_id || empty($phone)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing video_id or phone number']);
    exit;
}

// Normalize phone: strip spaces, handle +255 prefix, convert leading 0
$phone = trim($phone);
$phone = preg_replace('/\s+/', '', $phone);
$phone = preg_replace('/^\+255/', '0', $phone);
if (strpos($phone, '0') === 0) {
    $phone = '255' . substr($phone, 1);
}

// Retrieve video details and owner's gateway API key
$stmt = $pdo->prepare("
    SELECT v.id as video_id, v.price, v.slug, u.id as user_id, u.gateway_api_key, u.monetization_mode 
    FROM videos v
    JOIN users u ON v.user_id = u.id
    WHERE v.id = ? AND v.status = 'active'
");
$stmt->execute([$video_id]);
$video = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$video) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Video not found or inactive']);
    exit;
}

if (empty($video['gateway_api_key'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Payment gateway not configured by the owner']);
    exit;
}

$amount = (float)($video['price'] ?? 1000);

$apiUrl = 'https://pesalink.online/api/create-transaction';
$postData = json_encode([
    'number' => $phone,
    'amount' => $amount,
    'name' => $name
]);

$maxRetries = 3;
$attempt = 0;
$retryDelay = 2; // seconds
$response = false;
$responseData = null;
$httpCode = 0;
$curlErrno = 0;
$curlError = '';

while ($attempt < $maxRetries) {
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $video['gateway_api_key'],
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 45
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    curl_close($ch);

    if (!$response || $curlErrno) {
        $attempt++;
        if ($attempt < $maxRetries) {
            sleep($retryDelay);
            continue;
        }
        break;
    }

    $responseData = json_decode($response, true);
    
    if (!is_array($responseData) || $httpCode >= 500) {
        $attempt++;
        if ($attempt < $maxRetries) {
            sleep($retryDelay);
            continue;
        }
        break;
    }
    
    if ($httpCode === 201 || (isset($responseData['status']) && $responseData['status'] === 'success')) {
        break;
    }
    
    $errorMsg = $responseData['message'] ?? '';
    if (stripos($errorMsg, 'curl error') !== false || stripos($errorMsg, 'connect') !== false || stripos($errorMsg, 'gateway') !== false) {
        $attempt++;
        if ($attempt < $maxRetries) {
            sleep($retryDelay);
            continue;
        }
    }
    
    break;
}

if (!$response || $curlErrno) {
    error_log('[process_payment] CURL error ' . $curlErrno . ': ' . $curlError . ' | HTTP ' . $httpCode . ' after ' . $attempt . ' attempts');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to connect to payment gateway after retries']);
    exit;
}

if (!is_array($responseData) || $httpCode >= 500) {
    error_log('[process_payment] Gateway unavailable HTTP ' . $httpCode . ' after ' . $attempt . ' attempts');
    http_response_code(502); // Bad Gateway
    echo json_encode(['status' => 'error', 'message' => 'Payment gateway is currently unavailable. Please try again later.', 'details' => null]);
    exit;
}

if ($httpCode === 201 || ($responseData['status'] === 'success' && isset($responseData['data']['tranID']))) {
    $tranID = $responseData['data']['tranID'];
    
    try {
        $network = 'Unknown';
        $p = $phone;
        if (strpos($p, '255') === 0) $p = '0' . substr($p, 3);
        $prefix = substr($p, 0, 3);
        if (in_array($prefix, ['074','075','076'])) $network = 'Vodacom';
        elseif (in_array($prefix, ['065','067','071','077'])) $network = 'Tigo';
        elseif (in_array($prefix, ['068','069','078','079'])) $network = 'Airtel';
        elseif (in_array($prefix, ['062','061'])) $network = 'Halotel';
        elseif (in_array($prefix, ['073'])) $network = 'TTCL';
        // Insert pending transaction
        $stmt = $pdo->prepare("
            INSERT INTO transactions (user_id, video_id, type, amount, reference_id, status, network) 
            VALUES (?, ?, 'earning', ?, ?, 'pending', ?)
        ");
        $stmt->execute([$video['user_id'], $video_id, $amount, $tranID, $network]);
        
        // Insert video_access record
        $stmt = $pdo->prepare("INSERT INTO video_access (video_id, vendor_id, customer_phone, reference, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([$video_id, $video['user_id'], $phone, $tranID]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error while saving transaction.', 'details' => $e->getMessage()]);
        exit;
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Payment initiated',
        'tranID' => $tranID,
        'amount' => $amount,
        'monetization_mode' => $video['monetization_mode'] ?? 'single'
    ]);
} else {
    $errorMsg = $responseData['message'] ?? 'Payment initiation failed';
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $errorMsg, 'details' => $responseData]);
}
