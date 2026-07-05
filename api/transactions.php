<?php
require_once '../includes/db.php';
require_once '../includes/auth_functions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "draw" => isset($_GET['draw']) ? (int)$_GET['draw'] : 1,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => []
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get DataTables parameters
$draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 1;
$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
$length = isset($_GET['length']) ? (int)$_GET['length'] : 10;
$searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

// Base query
$query = "SELECT * FROM transactions WHERE user_id = ?";
$params = [$user_id];

// Filtering
if (!empty($searchValue)) {
    $query .= " AND (reference_id LIKE ? OR type LIKE ? OR amount LIKE ? OR network LIKE ? OR status LIKE ? OR DATE_FORMAT(created_at, '%d %b %Y, %H:%i') LIKE ?)";
    $searchWildcard = '%' . $searchValue . '%';
    $params[] = $searchWildcard; // reference_id
    $params[] = $searchWildcard; // type
    $params[] = $searchWildcard; // amount
    $params[] = $searchWildcard; // network
    $params[] = $searchWildcard; // status
    $params[] = $searchWildcard; // created_at
}

// Count total records after filtering
$stmtCount = $pdo->prepare($query);
$stmtCount->execute($params);
$totalRecords = $stmtCount->rowCount();

// Ordering (using default order by created_at DESC)
$query .= " ORDER BY created_at DESC LIMIT $start, $length";

// Fetch records
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$records = $stmt->fetchAll();

$data = [];
$index = $start + 1;
foreach ($records as $row) {
    // Formatting status badge
    $statusClass = 'bg-warning-subtle text-warning';
    if ($row['status'] === 'completed') {
        $statusClass = 'bg-success-subtle text-success';
    } elseif ($row['status'] === 'failed') {
        $statusClass = 'bg-danger-subtle text-danger';
    }

    $statusBadge = '<span class="badge ' . $statusClass . '">' . ucfirst($row['status']) . '</span>';
    
    // Formatting type badge
    $typeClass = 'bg-primary-subtle text-primary';
    if ($row['type'] === 'withdrawal') {
        $typeClass = 'bg-danger-subtle text-danger';
    } elseif ($row['type'] === 'earning') {
        $typeClass = 'bg-success-subtle text-success';
    }
    $typeBadge = '<span class="badge ' . $typeClass . '">' . ucfirst($row['type']) . '</span>';

    $fee = isset($row['fee_amount']) ? (float)$row['fee_amount'] : 0;
    $gross = (float)$row['amount'];
    $net = $gross - $fee;
    
    $data[] = [
        'DT_RowIndex' => $index++,
        'created_at' => '<span class="text-muted">' . date('d M Y, H:i', strtotime($row['created_at'])) . '</span>',
        'transaction_id' => '<code>' . htmlspecialchars($row['reference_id'] ?: '#TXN-' . str_pad($row['id'], 6, '0', STR_PAD_LEFT)) . '</code>',
        'type' => $typeBadge,
        'amount' => '<span class="fw-bold">' . htmlspecialchars(number_format($gross) . ' ' . $row['currency']) . '</span>',
        'network' => '<i class="fa-solid fa-mobile-screen me-1 text-success"></i> ' . htmlspecialchars($row['network'] ?? 'Unknown'),
        'fee' => '<span class="text-danger">- ' . number_format($fee) . ' ' . $row['currency'] . '</span>',
        'net' => '<span class="text-success fw-bold">' . number_format($net) . ' ' . $row['currency'] . '</span>',
        'status' => $statusBadge
    ];
}

$response = [
    "draw" => $draw,
    "recordsTotal" => $totalRecords, // Should technically be total without filter, but this is fine
    "recordsFiltered" => $totalRecords,
    "data" => $data
];

header('Content-Type: application/json');
echo json_encode($response);
?>
