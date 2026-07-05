<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

// Allow POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$orderId = $payload['order_id'] ?? '';
$status = $payload['payment_status'] ?? $payload['status'] ?? '';

if (empty($orderId) || empty($status)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing fields']);
    exit;
}

$pesaStatus = strtoupper($status);
$dbStatus = ($pesaStatus === 'COMPLETED') ? 'completed' : (($pesaStatus === 'FAILED' || $pesaStatus === 'CANCELLED') ? 'failed' : 'pending');

if ($dbStatus === 'pending') {
    http_response_code(200);
    echo json_encode(['received' => true]);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Lock the transaction row
    $stmt = $pdo->prepare("SELECT id, user_id, video_id, amount, status FROM transactions WHERE reference_id = ? FOR UPDATE");
    $stmt->execute([$orderId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($transaction && $transaction['status'] === 'pending') {
        $fee_percent = (float)get_setting($pdo, 'platform_fee_percentage', '0');
        $fee_amount = ($dbStatus === 'completed') ? ($transaction['amount'] * $fee_percent / 100) : 0;
        $net_amount = $transaction['amount'] - $fee_amount;
        
        // Update transaction status + record completion time
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
            $stmtAccess->execute([$orderId]);

            // Grant streaming_access — was previously missing from the webhook path
            try {
                $stmtStream = $pdo->prepare("
                    INSERT INTO streaming_access (ip_address, creator_id, expires_at)
                    VALUES ('webhook', ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))
                    ON DUPLICATE KEY UPDATE expires_at = DATE_ADD(NOW(), INTERVAL 24 HOUR)
                ");
                $stmtStream->execute([$transaction['user_id']]);
            } catch (Exception $streamEx) {
                error_log('[pesalink_webhook] streaming_access insert failed: ' . $streamEx->getMessage());
            }
        }
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('[pesalink_webhook] Transaction failed for order ' . $orderId . ': ' . $e->getMessage());
    http_response_code(500);
    exit;
}

http_response_code(200);
echo json_encode(['received' => true]);
