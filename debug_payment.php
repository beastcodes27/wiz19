<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/includes/db.php';

echo "<h2>Payment Debug</h2>";

// Test 1: Check transactions table structure
echo "<h3>1. Transactions table columns:</h3><pre>";
$stmt = $pdo->query("DESCRIBE transactions");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

// Test 2: Check video_access table
echo "</pre><h3>2. Video access table columns:</h3><pre>";
$stmt = $pdo->query("DESCRIBE video_access");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

// Test 3: Check if get_network_from_phone function exists
echo "</pre><h3>3. Testing get_network_from_phone():</h3><pre>";
echo "Function exists: " . (function_exists('get_network_from_phone') ? 'YES' : 'NO') . "\n";
if (function_exists('get_network_from_phone')) {
    echo "Test 255712345678 => " . get_network_from_phone('255712345678') . "\n";
}

// Test 4: Check a sample video
echo "</pre><h3>4. Sample video query:</h3><pre>";
$stmt = $pdo->query("SELECT v.id, v.price, v.slug, u.gateway_api_key, u.monetization_mode FROM videos v JOIN users u ON v.user_id = u.id WHERE v.status = 'active' LIMIT 1");
$video = $stmt->fetch(PDO::FETCH_ASSOC);
if ($video) {
    print_r($video);
    echo "\ngateway_api_key empty: " . (empty($video['gateway_api_key']) ? 'YES (PROBLEM!)' : 'NO') . "\n";
} else {
    echo "No active videos found.\n";
}

// Test 5: Check users with gateway_api_key
echo "</pre><h3>5. Users with gateway_api_key:</h3><pre>";
$stmt = $pdo->query("SELECT id, full_name, email, gateway_api_key IS NOT NULL AND gateway_api_key != '' as has_key FROM users WHERE role = 'admin'");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($admins);

// Test 6: Check PHP error log last 20 lines
echo "</pre><h3>6. Last 20 PHP errors:</h3><pre>";
$logFile = ini_get('error_log');
if ($logFile && file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -20);
    foreach ($lastLines as $line) {
        echo htmlspecialchars($line);
    }
} else {
    echo "Error log not found at: " . ($logFile ?: 'not set') . "\n";
    // Try common locations
    $paths = [
        '/home/pesachap/public_html/api/error_log',
        '/home/pesachap/public_html/error_log',
        '/tmp/php-errors.log',
        '/var/log/php_errors.log'
    ];
    foreach ($paths as $p) {
        if (file_exists($p)) {
            echo "Found at: $p\n";
            $lines = file($p);
            $lastLines = array_slice($lines, -20);
            foreach ($lastLines as $line) {
                echo htmlspecialchars($line);
            }
            break;
        }
    }
}
echo "</pre>";
