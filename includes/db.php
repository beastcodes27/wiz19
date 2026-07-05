<?php
/**
 * Database Connection configuration
 */

$db_host = 'localhost';
$db_name = 'pesachap_pesachap';
$db_user = 'pesachap_pesachap';
$db_pass = 'Tanzania2000@';
$db_port = '3306';

$project_root = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
$project_root = rtrim($project_root, '/');
$doc_root = rtrim($doc_root, '/');
$dynamic_base_url = '';
if (!empty($doc_root) && stripos($project_root, $doc_root) === 0) {
    $dynamic_base_url = substr($project_root, strlen($doc_root));
}
define('BASE_URL', $dynamic_base_url);

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$domain = $protocol . $host;
$full_base_url = $domain . BASE_URL;

try {
    $pdo = new PDO("mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Set Timezone for both PHP and MySQL
    date_default_timezone_set('Africa/Dar_es_Salaam');
    $pdo->exec("SET time_zone = '+03:00'");
} catch(PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
}

// Helper function to fetch a single setting
function get_setting($pdo, $key, $default = null) {
    global $global_settings;
    if (isset($global_settings[$key])) return $global_settings[$key];
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : $default;
}

// Helper function to extract network from Tanzanian phone numbers
function get_network_from_phone($phone) {
    if (empty($phone)) return 'Unknown';
    if (strpos($phone, '255') === 0) {
        $phone = '0' . substr($phone, 3);
    } elseif (strpos($phone, '+255') === 0) {
        $phone = '0' . substr($phone, 4);
    }

    $prefix = substr($phone, 0, 3);

    if (in_array($prefix, ['074', '075', '076'])) return 'Vodacom';
    if (in_array($prefix, ['065', '067', '071', '077'])) return 'Tigo';
    if (in_array($prefix, ['068', '069', '078', '079'])) return 'Airtel';
    if (in_array($prefix, ['062', '061'])) return 'Halotel';
    if (in_array($prefix, ['073'])) return 'TTCL';

    return 'Unknown';
}

// Fetch all settings globally
$global_settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmt->fetch()) {
        $global_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch(PDOException $e) {
    // Settings table might not exist yet
}

$platform_name = $global_settings['platform_name'] ?? 'App';
$telegram_username = $global_settings['telegram_username'] ?? '';

// Maintenance Mode Enforcement
$is_maintenance = ($global_settings['maintenance_mode'] ?? '0') === '1';
if ($is_maintenance) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $is_admin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
    if (!$is_admin) {
        $current_script = basename($_SERVER['SCRIPT_NAME']);
        $is_allowed_script = ($current_script === 'login.php' || $current_script === 'logout.php');
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $is_allowed_path = (strpos($request_uri, '/assets/') !== false || strpos($request_uri, '/api/webhook/') !== false);
        if (!$is_allowed_script && !$is_allowed_path) {
            $from_email = $global_settings['support_email'] ?? 'support@example.com';
            if (ob_get_level()) { ob_end_clean(); }
            http_response_code(503);
            die('<h1>Under Maintenance</h1><p>We will be back shortly. Contact: <a href="mailto:' . htmlspecialchars($from_email) . '">' . htmlspecialchars($from_email) . '</a></p>');
        }
    }
}
