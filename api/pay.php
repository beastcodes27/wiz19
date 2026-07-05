<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

// Allow POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$phone = $input['phone'] ?? '';
$amount = (float)($input['amount'] ?? 1000);
$token = $input['token'] ?? ''; // This is the gateway API key

if (empty($phone) || empty($token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing phone number or authorization token']);
    exit;
}

if (strpos($phone, '0') === 0) {
    $phone = '255' . substr($phone, 1);
}

// Find creator user ID from the gateway API key (token)
$stmt = $pdo->prepare("SELECT id FROM users WHERE gateway_api_key = ? LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    // If not found by gateway_api_key, fallback to check any admin
    $stmtAdm = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $user = $stmtAdm->fetch();
}

$userId = $user ? (int)$user['id'] : 1;

// Initiate payment via PesaLink
$apiUrl = 'https://pesalink.online/api/create-transaction';
$postData = json_encode([
    'number' => $phone,
    'amount' => $amount,
    'name' => 'Streaming Pass'
]);

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_TIMEOUT => 45
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!$response) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to connect to payment gateway']);
    exit;
}

$responseData = json_decode($response, true);

if (!is_array($responseData)) {
    http_response_code(502);
    echo json_encode(['success' => false, 'message' => 'Payment gateway is currently unavailable (500 Error).']);
    exit;
}

if ($httpCode === 201 || ($responseData['status'] === 'success' && isset($responseData['data']['tranID']))) {
    $tranID = $responseData['data']['tranID'];
    
    try {
        // Insert pending transaction
        $stmt = $pdo->prepare("
            INSERT INTO transactions (user_id, video_id, type, amount, reference_id, status) 
            VALUES (?, NULL, 'deposit', ?, ?, 'pending')
        ");
        $stmt->execute([$userId, $amount, $tranID]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error while saving transaction.', 'details' => $e->getMessage()]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'transaction_id' => $tranID,
        'redirect_url' => 'streaming.php'
    ]);
} else {
    $errorMsg = $responseData['message'] ?? 'Payment initiation failed';
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $errorMsg]);
}
