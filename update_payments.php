<?php
$files = ['api/check_payment.php', 'api/pesalink_webhook.php'];

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // We need to fetch the fee percentage globally inside the try block
    $search1 = "if (\$currentStatus === 'pending') {";
    $replace1 = "\$fee_percent = (float)get_setting(\$pdo, 'platform_fee_percentage', '0');\n        if (\$currentStatus === 'pending') {";
    // wait, in webhook, the check is: `if ($transaction && $transaction['status'] === 'pending') {`
    
    // It's safer to just fetch it right before updating the transaction.
    $search2 = "\$stmtUpdate = \$pdo->prepare(\"UPDATE transactions SET status = ? WHERE id = ?\");\n            \$stmtUpdate->execute([\$dbStatus, \$transaction['id']]);";
    $replace2 = "\$fee_percent = (float)get_setting(\$pdo, 'platform_fee_percentage', '0');\n            \$fee_amount = (\$dbStatus === 'completed') ? (\$transaction['amount'] * \$fee_percent / 100) : 0;\n            \$net_amount = \$transaction['amount'] - \$fee_amount;\n            \n            \$stmtUpdate = \$pdo->prepare(\"UPDATE transactions SET status = ?, fee_amount = ? WHERE id = ?\");\n            \$stmtUpdate->execute([\$dbStatus, \$fee_amount, \$transaction['id']]);";
    
    $content = str_replace($search2, $replace2, $content);
    
    // Now update user balance using $net_amount
    $search3 = "\$stmtUser = \$pdo->prepare(\"UPDATE users SET balance = balance + ? WHERE id = ?\");\n                \$stmtUser->execute([\$transaction['amount'], \$transaction['user_id']]);";
    $replace3 = "\$stmtUser = \$pdo->prepare(\"UPDATE users SET balance = balance + ? WHERE id = ?\");\n                \$stmtUser->execute([\$net_amount, \$transaction['user_id']]);";
    
    $content = str_replace($search3, $replace3, $content);
    
    // Wait, in check_payment.php: `$stmtLock->fetchColumn()` doesn't get `$transaction['amount']`, it uses `$transaction['amount']` from earlier fetch. So we are good.
    // In pesalink_webhook.php, it's also `$transaction['amount']`.
    
    file_put_contents($file, $content);
    echo "Updated $file\n";
}
