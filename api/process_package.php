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
$package_id = $input['package_id'] ?? null;
$phone = $input['phone'] ?? '';
$name = $input['name'] ?? 'Customer';

if (!$package_id || empty($phone)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing package_id or phone number']);
    exit;
}

// Normalize phone: strip spaces, handle +255 prefix, convert leading 0
$phone = trim($phone);
$phone = preg_replace('/\s+/', '', $phone);
$phone = preg_replace('/^\+255/', '0', $phone);
if (strpos($phone, '0') === 0) {
    $phone = '255' . substr($phone, 1);
}

// Retrieve package details and owner's gateway API key
$stmt = $pdo->prepare("
    SELECT p.id as package_id, p.price, u.id as user_id, u.gateway_api_key 
    FROM packages p
    JOIN users u ON p.user_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$package_id]);
$package = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$package) {
    // Check if it's the default package
    if ($package_id === 'default') {
        // Fallback default package
        $package = [
            'package_id' => null,
            'price' => 1000,
            'user_id' => 1, // Will be overriden later if needed, but this is a fallback
            'gateway_api_key' => ''
        ];
        // We need the admin's or domain owner's API key.
        // Try getting an API key from ANY admin.
        $stmtAdm = $pdo->query("SELECT id, gateway_api_key FROM users WHERE role = 'admin' LIMIT 1");
        if ($admin = $stmtAdm->fetch()) {
            $package['user_id'] = $admin['id'];
            $package['gateway_api_key'] = $admin['gateway_api_key'];
        }
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Package not found or inactive']);
        exit;
    }
}

if (empty($package['gateway_api_key'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Payment gateway not configured by the owner']);
    exit;
}

$amount = (float)($package['price'] ?? 1000);

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
            'Authorization: Bearer ' . $package['gateway_api_key'],
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
    error_log('[process_package] CURL error ' . $curlErrno . ': ' . $curlError . ' | HTTP ' . $httpCode . ' after ' . $attempt . ' attempts');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to connect to payment gateway after retries']);
    exit;
}

if (!is_array($responseData) || $httpCode >= 500) {
    error_log('[process_package] Gateway unavailable HTTP ' . $httpCode . ' after ' . $attempt . ' attempts');
    http_response_code(502); // Bad Gateway
    echo json_encode(['status' => 'error', 'message' => 'Payment gateway is currently unavailable. Please try again later.', 'details' => null]);
    exit;
}

if ($httpCode === 201 || ($responseData['status'] === 'success' && isset($responseData['data']['tranID']))) {
    $tranID = $responseData['data']['tranID'];
    
    try {
        $network = get_network_from_phone($phone);
        // Insert pending transaction (video_id = NULL for packages)
        $stmt = $pdo->prepare("
            INSERT INTO transactions (user_id, video_id, type, amount, reference_id, status, network) 
            VALUES (?, NULL, 'earning', ?, ?, 'pending', ?)
        ");
        $stmt->execute([$package['user_id'], $amount, $tranID, $network]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error while saving transaction.', 'details' => $e->getMessage()]);
        exit;
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Payment initiated',
        'tranID' => $tranID,
        'amount' => $amount
    ]);
} else {
    $errorMsg = $responseData['message'] ?? 'Payment initiation failed';
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $errorMsg, 'details' => $responseData]);
}
