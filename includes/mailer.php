<?php

function send_email($to, $subject, $message) {
    // In a real production environment, you would use PHPMailer here.
    // For this implementation, we use PHP's native mail() with basic headers.
    
    global $pdo;
    
    // Attempt to get support email from settings
    $from_email = 'support@flowtune.com';
    $platform_name = 'Flowtune';
    
    if (isset($pdo)) {
        try {
            $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'support_email'");
            if ($row = $stmt->fetch()) {
                $from_email = $row['setting_value'];
            }
            $stmt2 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'platform_name'");
            if ($row2 = $stmt2->fetch()) {
                $platform_name = $row2['setting_value'];
            }
        } catch (Exception $e) {
            // Silently fall back to defaults
        }
    }
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: $platform_name <$from_email>" . "\r\n";
    $headers .= "Reply-To: $from_email" . "\r\n";

    // Format message nicely
    $html_message = "
    <html>
    <head>
    <title>$subject</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px; }
        .header { background: #316AFF; color: white; padding: 10px 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { padding: 20px; }
        .footer { text-align: center; padding-top: 20px; font-size: 12px; color: #777; border-top: 1px solid #eee; }
    </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>$platform_name</h2>
            </div>
            <div class='content'>
                $message
            </div>
            <div class='footer'>
                &copy; " . date('Y') . " $platform_name. All rights reserved.
            </div>
        </div>
    </body>
    </html>
    ";

    return mail($to, $subject, $html_message, $headers);
}
