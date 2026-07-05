<?php
$file = 'settings.php';
$content = file_get_contents($file);

$search1 = "\$webhookUrl = \$user['webhook_url'] ?? '';
  if (empty(\$webhookUrl)) {
      // Generate a default webhook URL for the user
      \$webhookUrl = \$full_base_url . \"/api/gateway_webhook.php?u=\" . \$user_id;
  }";

$replace1 = "// Webhook URL for PesaLink integration
  \$webhookUrl = rtrim(\$full_base_url, '/') . \"/api/pesalink_webhook.php\";";

$content = str_replace($search1, $replace1, $content);

$search2 = "<div class=\"mt-2 text-muted\">
                                                        <small>
                                                            <i class=\"fa-solid fa-circle-info me-1\"></i>
                                                            <strong>Note:</strong> This is a random selection from 
your
                                                            active";

$replace2 = "<div class=\"mt-2 text-muted\">
                                                        <small>
                                                            <i class=\"fa-solid fa-circle-info me-1\"></i>
                                                            <strong>Note:</strong> Copy this URL and paste it into your PesaLink Merchant Dashboard to receive real-time payment confirmations.
                                                        </small>
                                                    </div>";
// Wait, the search string might be cut off or formatted differently in HTML. Let's use preg_replace for the Note block.

file_put_contents('update_settings_user.php', '<?php
$file = "settings.php";
$content = file_get_contents($file);

$search1 = \'$webhookUrl = $user["webhook_url"] ?? "";
  if (empty($webhookUrl)) {
      // Generate a default webhook URL for the user
      $webhookUrl = $full_base_url . "/api/gateway_webhook.php?u=" . $user_id;
  }\';
  
$search1 = str_replace(\'"\', "\'", $search1); // adjust quotes
// Actually regex is safer.
$pattern1 = \'/\\$webhookUrl = \\$user\\[\\\'webhook_url\\\'\\] \\?\\? \\\'\\\';\s*if \\(empty\\(\\$webhookUrl\\)\\) \\{\s*\\/\\/ Generate a default webhook URL for the user\s*\\$webhookUrl = \\$full_base_url \\. "\\/api\\/gateway_webhook\\.php\\?u=" \\. \\$user_id;\s*\\}/\';
$replace1 = "// PesaLink Webhook URL\n  \$webhookUrl = rtrim(\$full_base_url, \'/\') . \'/api/pesalink_webhook.php\';";
$content = preg_replace($pattern1, $replace1, $content);

$pattern2 = \'/<strong>Note:<\\/strong> This is a random selection from\\s*your\\s*active(.*?)</s\';
$replace2 = "<strong>Note:</strong> Copy this URL and paste it into your PesaLink Merchant Dashboard to receive real-time payment confirmations.<";
$content = preg_replace($pattern2, $replace2, $content);

file_put_contents($file, $content);
echo "Updated settings.php\n";
');
