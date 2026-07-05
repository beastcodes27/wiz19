<?php
$file = 'api/transactions.php';
$content = file_get_contents($file);

$search = "    \$data[] = [
        'DT_RowIndex' => \$index++,
        'created_at' => date('d M Y, H:i', strtotime(\$row['created_at'])),
        'transaction_id' => htmlspecialchars(\$row['reference_id'] ?? '-'),
        'type' => \$typeBadge,
        'amount' => htmlspecialchars(number_format(\$row['amount']) . ' ' . \$row['currency']),
        'status' => \$statusBadge
    ];";

$replace = "    \$fee = isset(\$row['fee_amount']) ? (float)\$row['fee_amount'] : 0;
    \$gross = (float)\$row['amount'];
    \$net = \$gross - \$fee;
    
    \$data[] = [
        'DT_RowIndex' => \$index++,
        'created_at' => date('d M Y, H:i', strtotime(\$row['created_at'])),
        'transaction_id' => htmlspecialchars(\$row['reference_id'] ?? '-'),
        'type' => \$typeBadge,
        'amount' => htmlspecialchars(number_format(\$gross) . ' ' . \$row['currency']),
        'fee' => '<span class=\"text-danger\">- ' . number_format(\$fee) . ' ' . \$row['currency'] . '</span>',
        'net' => '<span class=\"text-success fw-bold\">' . number_format(\$net) . ' ' . \$row['currency'] . '</span>',
        'status' => \$statusBadge
    ];";

$content = str_replace($search, $replace, $content);

file_put_contents($file, $content);
echo "Updated api/transactions.php\n";
