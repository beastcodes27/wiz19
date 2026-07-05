<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

$tranID = $_GET['tran_id'] ?? '';
$token = $_GET['token'] ?? ''; // Gateway API Key

if (empty($tranID) || empty($token)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing tran_id or token']);
    exit;
}

// Fetch transaction and user details
$stmt = $pdo->prepare("
    SELECT t.*, u.id as creator_id 
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    WHERE t.reference_id = ?
");
$stmt->execute([$tranID]);
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaction) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Transaction not found']);
    exit;
}

// If already completed or failed, return immediately
if ($transaction['status'] !== 'pending') {
    // Make sure IP access is granted just in case (e.g. if the webhook hit but IP pass is not added yet)
    if ($transaction['status'] === 'completed') {
        $ip = $_SERVER['REMOTE_ADDR'];
        try {
            $stmtAccess = $pdo->prepare("
                INSERT INTO streaming_access (ip_address, creator_id, expires_at) 
                VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR)) 
                ON DUPLICATE KEY UPDATE expires_at = DATE_ADD(NOW(), INTERVAL 24 HOUR)
            ");
            $stmtAccess->execute([$ip, $transaction['creator_id']]);
        } catch (Exception $e) {}
    }
    
    echo json_encode([
        'status' => $transaction['status']
    ]);
    exit;
}

// Status is pending, check with PesaLink
$apiUrl = 'https://pesalink.online/api/status-transaction?tranid=' . urlencode($tranID);

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token
    ],
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
curl_close($ch);

if (!$response) {
    echo json_encode(['status' => 'pending']);
    exit;
}

$responseData = json_decode($response, true);
$pesaStatus = strtoupper($responseData['data']['payment_status'] ?? 'PENDING');

if ($pesaStatus === 'COMPLETED' || $pesaStatus === 'FAILED' || $pesaStatus === 'CANCELLED') {
    $dbStatus = ($pesaStatus === 'COMPLETED') ? 'completed' : 'failed';
    
    try {
        $pdo->beginTransaction();
        
        $stmtLock = $pdo->prepare("SELECT status FROM transactions WHERE id = ? FOR UPDATE");
        $stmtLock->execute([$transaction['id']]);
        $currentStatus = $stmtLock->fetchColumn();
        
        if ($currentStatus === 'pending') {
            // Update transaction
            $fee_percent = (float)get_setting($pdo, 'platform_fee_percentage', '0');
            $fee_amount = ($dbStatus === 'completed') ? ($transaction['amount'] * $fee_percent / 100) : 0;
            $net_amount = $transaction['amount'] - $fee_amount;
            $network = $responseData['data']['network'] ?? $responseData['data']['channel'] ?? 'Unknown';
            
            $stmtUpdate = $pdo->prepare("UPDATE transactions SET status = ?, fee_amount = ?, network = ? WHERE id = ?");
            $stmtUpdate->execute([$dbStatus, $fee_amount, $network, $transaction['id']]);
            
            if ($dbStatus === 'completed') {
                // Update user balance
                $stmtUser = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmtUser->execute([$net_amount, $transaction['user_id']]);
                
                // Grant IP based streaming access
                $ip = $_SERVER['REMOTE_ADDR'];
                $stmtAccess = $pdo->prepare("
                    INSERT INTO streaming_access (ip_address, creator_id, expires_at) 
                    VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR)) 
                    ON DUPLICATE KEY UPDATE expires_at = DATE_ADD(NOW(), INTERVAL 24 HOUR)
                ");
                $stmtAccess->execute([$ip, $transaction['creator_id']]);
            }
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
    }
}

$finalStatus = ($pesaStatus === 'COMPLETED') ? 'completed' : (($pesaStatus === 'FAILED' || $pesaStatus === 'CANCELLED') ? 'failed' : 'pending');
echo json_encode([
    'status' => $finalStatus
]);
