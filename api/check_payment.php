<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

$tranID = $_GET['tranid'] ?? '';

if (empty($tranID)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing tranid']);
    exit;
}

// Fetch transaction and user details
// Wrapped in try-catch: if global_redirect_url column is missing the query won't crash the API
$transaction = null;
try {
    $stmt = $pdo->prepare("
        SELECT t.*, u.gateway_api_key, u.monetization_mode, u.global_redirect_url 
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        WHERE t.reference_id = ?
    ");
    $stmt->execute([$tranID]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Column may not exist — retry without global_redirect_url
    try {
        $stmt = $pdo->prepare("
            SELECT t.*, u.gateway_api_key, u.monetization_mode
            FROM transactions t
            JOIN users u ON t.user_id = u.id
            WHERE t.reference_id = ?
        ");
        $stmt->execute([$tranID]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($transaction) {
            $transaction['global_redirect_url'] = null;
        }
    } catch (Exception $e2) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e2->getMessage()]);
        exit;
    }
}

if (!$transaction) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Transaction not found']);
    exit;
}

// If already completed or failed, return immediately
if ($transaction['status'] !== 'pending') {
    if ($transaction['status'] === 'completed') {
        // Safety update: Ensure video_access is completed
        try {
            $stmtAccess = $pdo->prepare("UPDATE video_access SET status = 'completed', expires_at = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE reference = ? AND status = 'pending'");
            $stmtAccess->execute([$transaction['reference_id']]);
        } catch (Exception $e) {}

        // Ensure streaming_access pass is granted for this IP/creator
        try {
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmtAccessStream = $pdo->prepare("
                INSERT INTO streaming_access (ip_address, creator_id, expires_at) 
                VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR)) 
                ON DUPLICATE KEY UPDATE expires_at = DATE_ADD(NOW(), INTERVAL 24 HOUR)
            ");
            $stmtAccessStream->execute([$ip, $transaction['user_id']]);
        } catch (Exception $e) {}
    }

    echo json_encode([
        'status' => 'success', 
        'payment_status' => strtoupper($transaction['status']),
        'monetization_mode' => $transaction['monetization_mode'],
        'global_redirect_url' => $transaction['global_redirect_url'],
        'video_id' => $transaction['video_id']
    ]);
    exit;
}

if (empty($transaction['gateway_api_key'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Payment gateway not configured']);
    exit;
}

// Status is pending, check with PesaLink
$apiUrl = 'https://pesalink.online/api/status-transaction?tranid=' . urlencode($tranID);

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $transaction['gateway_api_key']
    ],
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
curl_close($ch);

if (!$response) {
    // If we fail to reach PesaLink, just return the current pending status
    echo json_encode([
        'status' => 'success', 
        'payment_status' => 'PENDING'
    ]);
    exit;
}

$responseData = json_decode($response, true);
$pesaStatus = strtoupper($responseData['data']['payment_status'] ?? 'PENDING');

if ($pesaStatus === 'COMPLETED' || $pesaStatus === 'FAILED' || $pesaStatus === 'CANCELLED') {
    $dbStatus = ($pesaStatus === 'COMPLETED') ? 'completed' : 'failed';
    
    // Update local database (using transaction to prevent double counting if webhook hits at same time)
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
            
            $stmtUpdate = $pdo->prepare("UPDATE transactions SET status = ?, fee_amount = ?, completed_at = NOW() WHERE id = ?");
            $stmtUpdate->execute([$dbStatus, $fee_amount, $transaction['id']]);
            
            if ($dbStatus === 'completed') {
                // Update user balance
                $stmtUser = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmtUser->execute([$net_amount, $transaction['user_id']]);
                
                // Update video earnings
                if ($transaction['video_id']) {
                    $stmtVideo = $pdo->prepare("UPDATE videos SET earnings = earnings + ? WHERE id = ?");
                    $stmtVideo->execute([$transaction['amount'], $transaction['video_id']]);
                    
                    // Analytics update
                    $today = date('Y-m-d');
                    $stmtAnalyt = $pdo->prepare("
                        INSERT INTO analytics (video_id, view_date, earnings) 
                        VALUES (?, ?, ?) 
                        ON DUPLICATE KEY UPDATE earnings = earnings + ?
                    ");
                    $stmtAnalyt->execute([$transaction['video_id'], $today, $transaction['amount'], $transaction['amount']]);
                }
                
                // Update video access
                $stmtAccess = $pdo->prepare("UPDATE video_access SET status = 'completed', expires_at = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE reference = ? AND status = 'pending'");
                $stmtAccess->execute([$transaction['reference_id']]);

                // Also grant streaming_access pass for this IP/creator
                try {
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $stmtAccessStream = $pdo->prepare("
                        INSERT INTO streaming_access (ip_address, creator_id, expires_at) 
                        VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR)) 
                        ON DUPLICATE KEY UPDATE expires_at = DATE_ADD(NOW(), INTERVAL 24 HOUR)
                    ");
                    $stmtAccessStream->execute([$ip, $transaction['user_id']]);
                } catch (Exception $e) {}
            }
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('[check_payment] DB update failed for tranID ' . $tranID . ': ' . $e->getMessage());
    }
}

echo json_encode([
    'status' => 'success', 
    'payment_status' => $pesaStatus,
    'monetization_mode' => $transaction['monetization_mode'],
    'global_redirect_url' => $transaction['global_redirect_url'],
    'video_id' => $transaction['video_id']
]);
