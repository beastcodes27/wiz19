<?php
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$ticket_id = isset($data['ticket_id']) ? (int)$data['ticket_id'] : 0;

if ($ticket_id > 0) {
    $stmt = $pdo->prepare("UPDATE support_tickets SET user_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$ticket_id, $_SESSION['user_id']]);
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ticket ID']);
}
?>
