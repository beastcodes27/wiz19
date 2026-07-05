<?php
/**
 * ============================================================
 *  Web Application Installer
 *  Multi-step shared hosting installation wizard
 * ============================================================
 */

define('INSTALLER_VERSION', '1.0.0');
define('LOCK_FILE', __DIR__ . '/install.lock');

// ── Security: block access once installed ───────────────────
if (file_exists(LOCK_FILE) && !isset($_GET['force'])) {
    http_response_code(403);
    die(renderLocked());
}

session_start();

// ── Step router ─────────────────────────────────────────────
$step    = (int)($_GET['step'] ?? $_SESSION['install_step'] ?? 1);
$message = '';
$errors  = [];

// ── POST handlers ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Step 2 → 3 : test DB connection
    if ($action === 'test_db') {
        $db_host = trim($_POST['db_host'] ?? 'localhost');
        $db_name = trim($_POST['db_name'] ?? '');
        $db_user = trim($_POST['db_user'] ?? '');
        $db_pass = $_POST['db_pass'] ?? '';
        $db_port = trim($_POST['db_port'] ?? '3306');

        if (empty($db_name) || empty($db_user)) {
            $errors[] = 'Database name and username are required.';
        } else {
            try {
                $dsn = "mysql:host=$db_host;port=$db_port;charset=utf8mb4";
                $pdo = new PDO($dsn, $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                // Try to create/select DB
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `$db_name`");

                $_SESSION['db_host'] = $db_host;
                $_SESSION['db_name'] = $db_name;
                $_SESSION['db_user'] = $db_user;
                $_SESSION['db_pass'] = $db_pass;
                $_SESSION['db_port'] = $db_port;
                $_SESSION['install_step'] = 3;
                header('Location: install.php?step=3');
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Database connection failed: ' . htmlspecialchars($e->getMessage());
            }
        }
        $step = 2;
    }

    // Step 3 → 4 : run SQL & save config
    if ($action === 'run_sql') {
        try {
            $pdo = new PDO(
                "mysql:host={$_SESSION['db_host']};port={$_SESSION['db_port']};dbname={$_SESSION['db_name']};charset=utf8mb4",
                $_SESSION['db_user'], $_SESSION['db_pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $sql = getSchema();
            // Split on delimiter and run each statement
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $stmt) {
                if ($stmt) $pdo->exec($stmt);
            }

            $_SESSION['install_step'] = 4;
            header('Location: install.php?step=4');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'SQL execution failed: ' . htmlspecialchars($e->getMessage());
            $step = 3;
        }
    }

    // Step 4 → 5 : create admin & platform settings
    if ($action === 'create_admin') {
        $platform_name   = trim($_POST['platform_name'] ?? 'MyApp');
        $support_email   = trim($_POST['support_email'] ?? '');
        $telegram        = trim($_POST['telegram'] ?? '');
        $currency        = $_POST['currency'] ?? 'TZS';
        $admin_name      = trim($_POST['admin_name'] ?? '');
        $admin_email     = trim($_POST['admin_email'] ?? '');
        $admin_phone     = trim($_POST['admin_phone'] ?? '');
        $admin_password  = $_POST['admin_password'] ?? '';
        $confirm_pass    = $_POST['confirm_password'] ?? '';

        if (empty($platform_name))  $errors[] = 'Platform name is required.';
        if (empty($support_email))  $errors[] = 'Support email is required.';
        if (empty($admin_name))     $errors[] = 'Admin full name is required.';
        if (empty($admin_email))    $errors[] = 'Admin email is required.';
        if (empty($admin_phone))    $errors[] = 'Admin phone is required.';
        if (strlen($admin_password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if ($admin_password !== $confirm_pass) $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            try {
                $pdo = new PDO(
                    "mysql:host={$_SESSION['db_host']};port={$_SESSION['db_port']};dbname={$_SESSION['db_name']};charset=utf8mb4",
                    $_SESSION['db_user'], $_SESSION['db_pass'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );

                // Insert or update admin user
                $hashed = password_hash($admin_password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role)
                    VALUES (?, ?, ?, ?, 'admin')
                    ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), password=VALUES(password), phone=VALUES(phone), role='admin'");
                $stmt->execute([$admin_name, $admin_email, $admin_phone, $hashed]);

                // Insert settings
                $settings = [
                    'platform_name'    => $platform_name,
                    'support_email'    => $support_email,
                    'telegram_username'=> $telegram,
                    'default_currency' => $currency,
                    'maintenance_mode' => '0',
                    'mpesa_test_mode'  => '1',
                    'system_commission'=> '10',
                ];
                $s = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?)
                    ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
                foreach ($settings as $k => $v) $s->execute([$k, $v]);

                // Write db.php config
                writeDbConfig(
                    $_SESSION['db_host'],
                    $_SESSION['db_name'],
                    $_SESSION['db_user'],
                    $_SESSION['db_pass'],
                    $_SESSION['db_port']
                );

                // Create lock file
                file_put_contents(LOCK_FILE, date('Y-m-d H:i:s') . ' - Installed successfully');

                $_SESSION['install_step']      = 5;
                $_SESSION['installed_platform'] = $platform_name;
                $_SESSION['installed_admin']    = $admin_email;
                header('Location: install.php?step=5');
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Setup failed: ' . htmlspecialchars($e->getMessage());
            }
        }
        $step = 4;
    }
}

// ── Requirement checks ───────────────────────────────────────
function checkRequirements(): array {
    $checks = [];

    $checks[] = ['label' => 'PHP Version ≥ 7.4', 'pass' => version_compare(PHP_VERSION, '7.4', '>='), 'value' => PHP_VERSION];
    $checks[] = ['label' => 'PDO Extension', 'pass' => extension_loaded('pdo'), 'value' => extension_loaded('pdo') ? 'Enabled' : 'Missing'];
    $checks[] = ['label' => 'PDO MySQL Driver', 'pass' => extension_loaded('pdo_mysql'), 'value' => extension_loaded('pdo_mysql') ? 'Enabled' : 'Missing'];
    $checks[] = ['label' => 'GD Library (Image)', 'pass' => extension_loaded('gd'), 'value' => extension_loaded('gd') ? 'Enabled' : 'Missing'];
    $checks[] = ['label' => 'cURL Extension', 'pass' => extension_loaded('curl'), 'value' => extension_loaded('curl') ? 'Enabled' : 'Missing'];
    $checks[] = ['label' => 'OpenSSL Extension', 'pass' => extension_loaded('openssl'), 'value' => extension_loaded('openssl') ? 'Enabled' : 'Missing'];
    $checks[] = ['label' => 'Session Support', 'pass' => extension_loaded('session'), 'value' => extension_loaded('session') ? 'Enabled' : 'Missing'];
    $checks[] = ['label' => 'JSON Extension', 'pass' => extension_loaded('json'), 'value' => extension_loaded('json') ? 'Enabled' : 'Missing'];
    $checks[] = ['label' => 'Mbstring Extension', 'pass' => extension_loaded('mbstring'), 'value' => extension_loaded('mbstring') ? 'Enabled' : 'Missing'];

    $uploadsDir = __DIR__ . '/uploads';
    if (!is_dir($uploadsDir)) @mkdir($uploadsDir, 0755, true);
    $checks[] = ['label' => '/uploads Writable', 'pass' => is_writable($uploadsDir), 'value' => is_writable($uploadsDir) ? 'Writable' : 'Not Writable'];

    $assetsImgDir = __DIR__ . '/assets/images';
    if (!is_dir($assetsImgDir)) @mkdir($assetsImgDir, 0755, true);
    $checks[] = ['label' => '/assets/images Writable', 'pass' => is_writable($assetsImgDir), 'value' => is_writable($assetsImgDir) ? 'Writable' : 'Not Writable'];

    $includesDir = __DIR__ . '/includes';
    $checks[] = ['label' => '/includes Writable', 'pass' => is_writable($includesDir), 'value' => is_writable($includesDir) ? 'Writable' : 'Not Writable'];

    return $checks;
}

// ── Write the db.php config file ─────────────────────────────
function writeDbConfig($host, $name, $user, $pass, $port = '3306'): void {
    $passEsc = addslashes($pass);
    $date = date('Y-m-d H:i:s');
    $content = <<<PHP
<?php
/**
 * Database Connection configuration
 * Auto-generated by installer on {$date}
 */

\$db_host = '{$host}';
\$db_name = '{$name}';
\$db_user = '{$user}';
\$db_pass = '{$passEsc}';
\$db_port = '{$port}';

\$project_root = str_replace('\\\\', '/', realpath(__DIR__ . '/..'));
\$doc_root = str_replace('\\\\', '/', \$_SERVER['DOCUMENT_ROOT'] ?? '');
\$project_root = rtrim(\$project_root, '/');
\$doc_root = rtrim(\$doc_root, '/');
\$dynamic_base_url = '';
if (!empty(\$doc_root) && stripos(\$project_root, \$doc_root) === 0) {
    \$dynamic_base_url = substr(\$project_root, strlen(\$doc_root));
}
define('BASE_URL', \$dynamic_base_url);

\$protocol = (!empty(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
\$host = \$_SERVER['HTTP_HOST'] ?? 'localhost';
\$domain = \$protocol . \$host;
\$full_base_url = \$domain . BASE_URL;

try {
    \$pdo = new PDO("mysql:host={\$db_host};port={\$db_port};dbname={\$db_name};charset=utf8mb4", \$db_user, \$db_pass);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Set Timezone for both PHP and MySQL
    date_default_timezone_set('Africa/Dar_es_Salaam');
    \$pdo->exec("SET time_zone = '+03:00'");
} catch(PDOException \$e) {
    die("Database Connection failed: " . \$e->getMessage());
}

// Helper function to fetch a single setting
function get_setting(\$pdo, \$key, \$default = null) {
    global \$global_settings;
    if (isset(\$global_settings[\$key])) return \$global_settings[\$key];
    \$stmt = \$pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    \$stmt->execute([\$key]);
    \$result = \$stmt->fetch();
    return \$result ? \$result['setting_value'] : \$default;
}

// Fetch all settings globally
\$global_settings = [];
try {
    \$stmt = \$pdo->query("SELECT setting_key, setting_value FROM settings");
    while (\$row = \$stmt->fetch()) {
        \$global_settings[\$row['setting_key']] = \$row['setting_value'];
    }
} catch(PDOException \$e) {
    // Settings table might not exist yet
}

\$platform_name = \$global_settings['platform_name'] ?? 'App';
\$telegram_username = \$global_settings['telegram_username'] ?? '';

// Maintenance Mode Enforcement
\$is_maintenance = (\$global_settings['maintenance_mode'] ?? '0') === '1';
if (\$is_maintenance) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    \$is_admin = (isset(\$_SESSION['user_role']) && \$_SESSION['user_role'] === 'admin');
    if (!\$is_admin) {
        \$current_script = basename(\$_SERVER['SCRIPT_NAME']);
        \$is_allowed_script = (\$current_script === 'login.php' || \$current_script === 'logout.php');
        \$request_uri = \$_SERVER['REQUEST_URI'] ?? '';
        \$is_allowed_path = (strpos(\$request_uri, '/assets/') !== false || strpos(\$request_uri, '/api/webhook/') !== false);
        if (!\$is_allowed_script && !\$is_allowed_path) {
            \$from_email = \$global_settings['support_email'] ?? 'support@example.com';
            if (ob_get_level()) { ob_end_clean(); }
            http_response_code(503);
            die('<h1>Under Maintenance</h1><p>We will be back shortly. Contact: <a href="mailto:' . htmlspecialchars(\$from_email) . '">' . htmlspecialchars(\$from_email) . '</a></p>');
        }
    }
}
PHP;

    file_put_contents(__DIR__ . '/includes/db.php', $content);
}

// ── Database schema ──────────────────────────────────────────
function getSchema(): string {
    return "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','admin') DEFAULT 'user',
    balance DECIMAL(10,2) DEFAULT 0.00,
    gateway_api_key VARCHAR(255) NULL,
    webhook_url VARCHAR(255) NULL,
    global_redirect_url VARCHAR(255) NULL,
    active_landing VARCHAR(50) DEFAULT 'landing1',
    monetization_mode ENUM('single', 'channel') DEFAULT 'single',
    avatar VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    video_url TEXT NOT NULL,
    thumbnail_url TEXT,
    views INT DEFAULT 0,
    clicks INT DEFAULT 0,
    earnings DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('active','pending','rejected','deleted') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('deposit','withdrawal','earning') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    fee_amount DECIMAL(10,2) DEFAULT 0.00,
    network VARCHAR(50) NULL,
    currency VARCHAR(10) DEFAULT 'TZS',
    reference_id VARCHAR(100) UNIQUE,
    status ENUM('completed','pending','failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS video_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    video_id INT NULL,
    vendor_id INT NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    reference VARCHAR(100) NOT NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open','in_progress','closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    video_id INT NOT NULL,
    view_date DATE NOT NULL,
    views INT DEFAULT 0,
    clicks INT DEFAULT 0,
    earnings DECIMAL(10,2) DEFAULT 0.00,
    UNIQUE KEY unique_video_date (video_id, view_date),
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    domain_prefix VARCHAR(100) NOT NULL,
    status ENUM('Connected','Pending') DEFAULT 'Connected',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration_days INT NOT NULL DEFAULT 30,
    max_videos INT DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS package_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    subscriber_phone VARCHAR(20) NOT NULL,
    status ENUM('Active', 'Expired') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
);

INSERT INTO settings (setting_key, setting_value) VALUES
('maintenance_mode','0'),
('mpesa_test_mode','1'),
('system_commission','10')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)
";
}

// ── Locked page ──────────────────────────────────────────────
function renderLocked(): string {
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Already Installed</title>
    <style>body{margin:0;font-family:system-ui,sans-serif;background:#0b0f19;color:#f3f4f6;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center}
    .box{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:20px;padding:48px;max-width:480px}
    h1{color:#f87171;margin-bottom:12px}p{color:#9ca3af;line-height:1.6}
    a{color:#316aff;text-decoration:none}a:hover{text-decoration:underline}</style></head>
    <body><div class="box"><h1>🔒 Already Installed</h1>
    <p>This application is already installed. The installer has been locked for security.</p>
    <p>If you need to re-install, delete the <code>install.lock</code> file from the server, then visit <a href="install.php?force=1">install.php?force=1</a>.</p>
    <p><a href="login.php">← Go to Login</a></p></div></body></html>';
}

// ── Requirement pass/fail count ──────────────────────────────
$requirements = checkRequirements();
$reqFailed    = count(array_filter($requirements, fn($r) => !$r['pass']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Installer — Step <?= $step ?> of 5</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ── Reset & Tokens ─────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:           #070b14;
            --surface:      #0d1424;
            --surface-2:    #121a2e;
            --border:       rgba(255,255,255,0.07);
            --border-light: rgba(255,255,255,0.12);
            --text:         #f0f4ff;
            --text-muted:   #8892a4;
            --text-dim:     #4b5563;
            --primary:      #3b6cfa;
            --primary-glow: rgba(59,108,250,0.18);
            --primary-dark: #2a52d4;
            --success:      #22c55e;
            --success-bg:   rgba(34,197,94,0.1);
            --danger:       #ef4444;
            --danger-bg:    rgba(239,68,68,0.1);
            --warning:      #f59e0b;
            --warning-bg:   rgba(245,158,11,0.1);
            --radius:       16px;
            --radius-sm:    10px;
            --shadow:       0 25px 60px rgba(0,0,0,0.5);
        }

        html, body {
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', system-ui, sans-serif;
            font-size: 15px;
            line-height: 1.6;
        }

        /* ── Background decoration ──────────────────────── */
        body::before {
            content: '';
            position: fixed;
            top: -200px; left: 50%;
            transform: translateX(-50%);
            width: 900px; height: 900px;
            background: radial-gradient(circle, rgba(59,108,250,0.07) 0%, transparent 65%);
            pointer-events: none;
            z-index: 0;
        }

        /* ── Layout ─────────────────────────────────────── */
        .installer-wrap {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px 60px;
        }

        /* ── Top brand bar ──────────────────────────────── */
        .brand-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 36px;
        }
        .brand-icon {
            width: 44px; height: 44px;
            background: linear-gradient(135deg, #3b6cfa, #7c3aed);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            box-shadow: 0 0 24px rgba(59,108,250,0.35);
        }
        .brand-text {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.3px;
            background: linear-gradient(90deg, #a5b4fc, #fff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .brand-badge {
            font-size: 11px;
            color: var(--text-muted);
            border: 1px solid var(--border-light);
            border-radius: 20px;
            padding: 2px 8px;
        }

        /* ── Progress stepper ───────────────────────────── */
        .stepper {
            display: flex;
            align-items: center;
            gap: 0;
            margin-bottom: 36px;
            width: 100%;
            max-width: 640px;
        }
        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
        }
        .step-item:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 18px;
            left: 60%;
            width: 80%;
            height: 2px;
            background: var(--border);
            z-index: 0;
            transition: background .4s;
        }
        .step-item.done:not(:last-child)::after,
        .step-item.active:not(:last-child)::after {
            background: linear-gradient(90deg, var(--primary), var(--border));
        }
        .step-item.done:not(:last-child)::after {
            background: var(--primary);
        }
        .step-circle {
            width: 36px; height: 36px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700;
            z-index: 1;
            transition: all .3s;
            border: 2px solid var(--border);
            background: var(--surface);
            color: var(--text-muted);
        }
        .step-item.active .step-circle {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
            box-shadow: 0 0 20px rgba(59,108,250,0.5);
        }
        .step-item.done .step-circle {
            background: var(--success);
            border-color: var(--success);
            color: #fff;
        }
        .step-label {
            margin-top: 6px;
            font-size: 11px;
            font-weight: 500;
            color: var(--text-muted);
            text-align: center;
            white-space: nowrap;
        }
        .step-item.active .step-label { color: var(--text); }
        .step-item.done .step-label   { color: var(--success); }

        /* ── Card ───────────────────────────────────────── */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            width: 100%;
            max-width: 680px;
            box-shadow: var(--shadow);
            overflow: hidden;
            animation: slideUp .4s cubic-bezier(.16,1,.3,1);
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .card-header {
            padding: 28px 32px 24px;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(180deg, rgba(59,108,250,0.04) 0%, transparent 100%);
        }
        .card-title {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.4px;
            margin-bottom: 6px;
        }
        .card-subtitle {
            color: var(--text-muted);
            font-size: 14px;
        }
        .card-body { padding: 28px 32px; }
        .card-footer {
            padding: 20px 32px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            background: rgba(255,255,255,0.01);
        }

        /* ── Form elements ──────────────────────────────── */
        .form-group { margin-bottom: 20px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 520px) { .form-row { grid-template-columns: 1fr; } }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 7px;
            letter-spacing: 0.3px;
        }
        label span.req { color: var(--danger); margin-left: 2px; }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        select {
            width: 100%;
            background: var(--surface-2);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-sm);
            color: var(--text);
            font-size: 14px;
            font-family: inherit;
            padding: 11px 14px;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        input:focus, select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
        }
        select option { background: #1a2236; }

        .input-icon-wrap {
            position: relative;
        }
        .input-icon-wrap i {
            position: absolute;
            left: 13px; top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 14px;
            pointer-events: none;
        }
        .input-icon-wrap input { padding-left: 38px; }

        .input-hint {
            margin-top: 5px;
            font-size: 12px;
            color: var(--text-dim);
        }

        /* ── Buttons ────────────────────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 24px;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all .2s;
            text-decoration: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3b6cfa, #5b4cff);
            color: #fff;
            box-shadow: 0 4px 16px rgba(59,108,250,0.35);
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 24px rgba(59,108,250,0.5);
        }
        .btn-ghost {
            background: transparent;
            color: var(--text-muted);
            border: 1px solid var(--border-light);
        }
        .btn-ghost:hover { color: var(--text); border-color: rgba(255,255,255,.25); }
        .btn-success {
            background: linear-gradient(135deg, #16a34a, #22c55e);
            color: #fff;
            box-shadow: 0 4px 16px rgba(34,197,94,0.3);
        }
        .btn-success:hover { transform: translateY(-1px); box-shadow: 0 6px 24px rgba(34,197,94,.45); }

        /* ── Alert ──────────────────────────────────────── */
        .alert {
            padding: 14px 16px;
            border-radius: var(--radius-sm);
            font-size: 13.5px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }
        .alert-danger  { background: var(--danger-bg);  border: 1px solid rgba(239,68,68,.25);  color: #fca5a5; }
        .alert-success { background: var(--success-bg); border: 1px solid rgba(34,197,94,.25);  color: #86efac; }
        .alert-warning { background: var(--warning-bg); border: 1px solid rgba(245,158,11,.25); color: #fcd34d; }
        .alert i { margin-top: 2px; flex-shrink: 0; }

        /* ── Requirement grid ───────────────────────────── */
        .req-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        @media (max-width: 520px) { .req-grid { grid-template-columns: 1fr; } }
        .req-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            border-radius: var(--radius-sm);
            background: var(--surface-2);
            border: 1px solid var(--border);
            font-size: 13px;
        }
        .req-item .req-icon {
            width: 28px; height: 28px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px;
            flex-shrink: 0;
        }
        .req-pass .req-icon { background: var(--success-bg); color: var(--success); }
        .req-fail .req-icon { background: var(--danger-bg);  color: var(--danger); }
        .req-item .req-info { flex: 1; min-width: 0; }
        .req-label { font-weight: 600; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .req-value { font-size: 11px; color: var(--text-muted); margin-top: 1px; }
        .req-pass .req-value { color: var(--success); }
        .req-fail .req-value { color: var(--danger); }

        /* ── Step 3 SQL info box ────────────────────────── */
        .info-box {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 18px;
            margin-bottom: 20px;
        }
        .info-box-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .table-list { list-style: none; display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
        .table-list li {
            font-size: 12.5px;
            color: var(--text-muted);
            display: flex; align-items: center; gap: 6px;
        }
        .table-list li::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: var(--primary); flex-shrink: 0; }

        /* ── Step 5 success ─────────────────────────────── */
        .success-hero {
            text-align: center;
            padding: 12px 0 28px;
        }
        .success-icon-ring {
            width: 90px; height: 90px;
            margin: 0 auto 24px;
            background: var(--success-bg);
            border: 2px solid rgba(34,197,94,.3);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 38px;
            animation: popIn .5s cubic-bezier(.16,1,.3,1);
        }
        @keyframes popIn {
            from { transform: scale(.5); opacity: 0; }
            to   { transform: scale(1);  opacity: 1; }
        }
        .success-title {
            font-size: 26px; font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 10px;
            background: linear-gradient(90deg, #86efac, #4ade80);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .creds-box {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 18px 20px;
            text-align: left;
            margin: 20px 0;
        }
        .creds-box p { font-size: 13.5px; color: var(--text-muted); margin-bottom: 6px; }
        .creds-box p:last-child { margin-bottom: 0; }
        .creds-box strong { color: var(--text); }

        .security-notice {
            background: rgba(239,68,68,.08);
            border: 1px solid rgba(239,68,68,.2);
            border-radius: var(--radius-sm);
            padding: 14px 16px;
            font-size: 13px;
            color: #fca5a5;
            margin-top: 16px;
            display: flex; gap: 10px; align-items: flex-start;
        }

        /* ── Misc ────────────────────────────────────────── */
        .separator {
            display: flex; align-items: center; gap: 12px;
            margin: 24px 0 20px;
        }
        .separator::before, .separator::after {
            content: ''; flex: 1; height: 1px; background: var(--border);
        }
        .separator span { font-size: 12px; color: var(--text-dim); white-space: nowrap; }

        .section-title {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: var(--text-dim);
            margin-bottom: 14px;
        }

        .footer-note {
            margin-top: 28px;
            font-size: 12px;
            color: var(--text-dim);
            text-align: center;
        }

        /* Password strength */
        .pass-strength { margin-top: 6px; display: flex; gap: 4px; height: 4px; }
        .pass-bar {
            flex: 1; border-radius: 2px;
            background: var(--border);
            transition: background .3s;
        }
        .pass-bar.weak   { background: var(--danger); }
        .pass-bar.medium { background: var(--warning); }
        .pass-bar.strong { background: var(--success); }

        .toggle-pass {
            position: absolute;
            right: 12px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: var(--text-muted);
            cursor: pointer; font-size: 14px;
            padding: 4px;
            transition: color .2s;
        }
        .toggle-pass:hover { color: var(--text); }
        .input-icon-wrap input[type="password"] { padding-right: 40px; }
        .input-icon-wrap input[type="text"].pass-visible { padding-right: 40px; }
    </style>
</head>
<body>
<div class="installer-wrap">

    <!-- Brand -->
    <div class="brand-bar">
        <div class="brand-icon">⚡</div>
        <div class="brand-text">Application Installer</div>
        <div class="brand-badge">v<?= INSTALLER_VERSION ?></div>
    </div>

    <!-- Stepper -->
    <nav class="stepper" aria-label="Installation steps">
        <?php
        $stepDefs = ['Welcome', 'Database', 'Import SQL', 'Configure', 'Complete'];
        foreach ($stepDefs as $i => $label):
            $num    = $i + 1;
            $class  = $num < $step ? 'done' : ($num === $step ? 'active' : '');
            $icon   = $num < $step ? '<i class="fa-solid fa-check" style="font-size:13px"></i>' : $num;
        ?>
        <div class="step-item <?= $class ?>" aria-current="<?= $num === $step ? 'step' : 'false' ?>">
            <div class="step-circle"><?= $icon ?></div>
            <div class="step-label"><?= $label ?></div>
        </div>
        <?php endforeach; ?>
    </nav>

    <!-- ═══════════════════════════════════════════════════════
         STEP 1 — Welcome & Requirements
    ═══════════════════════════════════════════════════════ -->
    <?php if ($step === 1): ?>
    <div class="card">
        <div class="card-header">
            <div class="card-title">👋 Welcome to the Installer</div>
            <div class="card-subtitle">Before we begin, let's make sure your server meets all requirements.</div>
        </div>
        <div class="card-body">

            <?php if ($reqFailed > 0): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                <div><strong><?= $reqFailed ?> requirement(s) not met.</strong> Please resolve the issues below before continuing.</div>
            </div>
            <?php else: ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <div><strong>All requirements passed!</strong> Your server is ready for installation.</div>
            </div>
            <?php endif; ?>

            <div class="section-title">Server Requirements</div>
            <div class="req-grid">
                <?php foreach ($requirements as $req): ?>
                <div class="req-item <?= $req['pass'] ? 'req-pass' : 'req-fail' ?>">
                    <div class="req-icon">
                        <i class="fa-solid <?= $req['pass'] ? 'fa-check' : 'fa-xmark' ?>"></i>
                    </div>
                    <div class="req-info">
                        <div class="req-label"><?= htmlspecialchars($req['label']) ?></div>
                        <div class="req-value"><?= htmlspecialchars($req['value']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="separator"><span>What will be installed</span></div>
            <ul class="table-list" style="margin-bottom:0">
                <li>Users &amp; Authentication</li>
                <li>Videos &amp; Media</li>
                <li>Transactions</li>
                <li>Withdrawals</li>
                <li>Support Tickets</li>
                <li>Analytics</li>
                <li>Domains</li>
                <li>Packages</li>
                <li>Platform Settings</li>
                <li>Admin Panel</li>
            </ul>
        </div>
        <div class="card-footer">
            <span style="font-size:13px;color:var(--text-muted);">PHP <?= PHP_VERSION ?> &bull; <?= PHP_OS ?></span>
            <?php if ($reqFailed === 0): ?>
            <a href="install.php?step=2" class="btn btn-primary">
                Continue <i class="fa-solid fa-arrow-right"></i>
            </a>
            <?php else: ?>
            <a href="install.php?step=1" class="btn btn-ghost">
                <i class="fa-solid fa-rotate-right"></i> Re-check
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         STEP 2 — Database
    ═══════════════════════════════════════════════════════ -->
    <?php elseif ($step === 2): ?>
    <div class="card">
        <div class="card-header">
            <div class="card-title">🗄️ Database Connection</div>
            <div class="card-subtitle">Enter your MySQL database credentials. The database will be created if it doesn't exist.</div>
        </div>
        <form method="POST" action="install.php?step=2" id="dbForm">
        <div class="card-body">
            <input type="hidden" name="action" value="test_db">

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                <ul style="margin:0;padding-left:16px"><?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul>
            </div>
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label>Database Host <span class="req">*</span></label>
                    <div class="input-icon-wrap">
                        <i class="fa-solid fa-server"></i>
                        <input type="text" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" placeholder="localhost" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Port</label>
                    <div class="input-icon-wrap">
                        <i class="fa-solid fa-network-wired"></i>
                        <input type="number" name="db_port" value="<?= htmlspecialchars($_POST['db_port'] ?? '3306') ?>" placeholder="3306">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Database Name <span class="req">*</span></label>
                <div class="input-icon-wrap">
                    <i class="fa-solid fa-database"></i>
                    <input type="text" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" placeholder="e.g. myapp_db" required>
                </div>
                <p class="input-hint">Will be created automatically if it does not exist.</p>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Database Username <span class="req">*</span></label>
                    <div class="input-icon-wrap">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" placeholder="e.g. root" required autocomplete="off">
                    </div>
                </div>
                <div class="form-group">
                    <label>Database Password</label>
                    <div class="input-icon-wrap">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="db_pass" placeholder="Leave blank if none" autocomplete="new-password">
                        <button type="button" class="toggle-pass" onclick="togglePass(this)"><i class="fa-regular fa-eye"></i></button>
                    </div>
                </div>
            </div>

            <div class="alert alert-warning" style="margin-bottom:0">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div><strong>Shared hosting tip:</strong> Your database user must have <code>CREATE</code>, <code>ALTER</code>, <code>INSERT</code>, <code>SELECT</code>, <code>UPDATE</code>, <code>DELETE</code> and <code>INDEX</code> privileges.</div>
            </div>
        </div>
        <div class="card-footer">
            <a href="install.php?step=1" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back</a>
            <button type="submit" class="btn btn-primary" id="dbBtn">
                <i class="fa-solid fa-plug"></i> Test &amp; Connect
            </button>
        </div>
        </form>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         STEP 3 — Import SQL
    ═══════════════════════════════════════════════════════ -->
    <?php elseif ($step === 3): ?>
    <div class="card">
        <div class="card-header">
            <div class="card-title">📋 Import Database Tables</div>
            <div class="card-subtitle">
                Connected to <strong style="color:var(--success)"><?= htmlspecialchars($_SESSION['db_name']) ?></strong> on <strong><?= htmlspecialchars($_SESSION['db_host']) ?></strong>. Ready to create tables.
            </div>
        </div>
        <form method="POST" action="install.php?step=3" id="sqlForm">
        <div class="card-body">
            <input type="hidden" name="action" value="run_sql">

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                <ul style="margin:0;padding-left:16px"><?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul>
            </div>
            <?php endif; ?>

            <div class="info-box">
                <div class="info-box-title"><i class="fa-solid fa-table-list"></i> Tables to be created</div>
                <ul class="table-list">
                    <li>users</li>
                    <li>videos</li>
                    <li>transactions</li>
                    <li>withdrawals</li>
                    <li>support_tickets</li>
                    <li>analytics</li>
                    <li>settings</li>
                    <li>domains</li>
                    <li>packages</li>
                </ul>
            </div>

            <div class="alert alert-warning" style="margin-bottom:0">
                <i class="fa-solid fa-info-circle"></i>
                <div>All tables use <code>IF NOT EXISTS</code> — existing data will <strong>not</strong> be overwritten. This is safe to run on an existing database.</div>
            </div>
        </div>
        <div class="card-footer">
            <a href="install.php?step=2" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back</a>
            <button type="submit" class="btn btn-primary" id="sqlBtn">
                <i class="fa-solid fa-bolt"></i> Run Import
            </button>
        </div>
        </form>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         STEP 4 — Configure
    ═══════════════════════════════════════════════════════ -->
    <?php elseif ($step === 4): ?>
    <div class="card">
        <div class="card-header">
            <div class="card-title">⚙️ Platform Configuration</div>
            <div class="card-subtitle">Set up your platform details and create the administrator account.</div>
        </div>
        <form method="POST" action="install.php?step=4" id="configForm">
        <div class="card-body">
            <input type="hidden" name="action" value="create_admin">

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                <ul style="margin:0;padding-left:16px"><?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul>
            </div>
            <?php endif; ?>

            <div class="section-title">Platform Settings</div>

            <div class="form-row">
                <div class="form-group">
                    <label>Platform / App Name <span class="req">*</span></label>
                    <div class="input-icon-wrap">
                        <i class="fa-solid fa-tag"></i>
                        <input type="text" name="platform_name" value="<?= htmlspecialchars($_POST['platform_name'] ?? '') ?>" placeholder="e.g. StreamHub" required maxlength="60">
                    </div>
                </div>
                <div class="form-group">
                    <label>Support Email <span class="req">*</span></label>
                    <div class="input-icon-wrap">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" name="support_email" value="<?= htmlspecialchars($_POST['support_email'] ?? '') ?>" required placeholder="support@domain.com">
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Telegram Username</label>
                    <div class="input-icon-wrap">
                        <i class="fa-brands fa-telegram"></i>
                        <input type="text" name="telegram" value="<?= htmlspecialchars($_POST['telegram'] ?? '') ?>" placeholder="@yourusername">
                    </div>
                </div>
                <div class="form-group">
                    <label>Currency</label>
                    <div class="input-icon-wrap">
                        <i class="fa-solid fa-coins"></i>
                        <input type="text" name="currency" value="<?= htmlspecialchars($_POST['currency'] ?? 'TZS') ?>" required>
                    </div>
                </div>
            </div>

            <div class="section-title" style="margin-top: 10px;">Admin Account</div>

            <div class="form-row">
                <div class="form-group">
                    <label>Admin Full Name <span class="req">*</span></label>
                    <div class="input-icon-wrap">
                        <i class="fa-solid fa-user-shield"></i>
                        <input type="text" name="admin_name" value="<?= htmlspecialchars($_POST['admin_name'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Admin Email <span class="req">*</span></label>
                    <div class="input-icon-wrap">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" name="admin_email" value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Admin Phone <span class="req">*</span></label>
                    <div class="input-icon-wrap">
                        <i class="fa-solid fa-phone"></i>
                        <input type="text" name="admin_phone" value="<?= htmlspecialchars($_POST['admin_phone'] ?? '') ?>" required>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Password <span class="req">*</span></label>
                    <div class="input-icon-wrap">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="admin_password" required minlength="8">
                        <button type="button" class="toggle-pass" onclick="togglePass(this)"><i class="fa-regular fa-eye"></i></button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm Password <span class="req">*</span></label>
                    <div class="input-icon-wrap">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="confirm_password" required minlength="8">
                        <button type="button" class="toggle-pass" onclick="togglePass(this)"><i class="fa-regular fa-eye"></i></button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <a href="install.php?step=3" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-check-double"></i> Complete Setup
            </button>
        </div>
        </form>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         STEP 5 — Complete
    ═══════════════════════════════════════════════════════ -->
    <?php elseif ($step === 5): ?>
    <div class="card">
        <div class="card-body" style="text-align: center;">
            <div class="success-hero">
                <div class="success-icon-ring"><i class="fa-solid fa-check"></i></div>
                <div class="success-title">Installation Complete!</div>
                <p>You have successfully installed <strong><?= htmlspecialchars($_SESSION['installed_platform'] ?? 'the application') ?></strong>.</p>
            </div>
            
            <div class="creds-box">
                <p><strong>Admin Email:</strong> <?= htmlspecialchars($_SESSION['installed_admin'] ?? '') ?></p>
            </div>
            
            <div class="security-notice">
                <i class="fa-solid fa-shield-halved"></i>
                <div>For security reasons, the installer has been locked. It is highly recommended to delete the <strong>install.php</strong> file from your server.</div>
            </div>
        </div>
        <div class="card-footer" style="justify-content: center;">
            <a href="login.php" class="btn btn-success">Go to Admin Login <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
function togglePass(btn) {
    const input = btn.previousElementSibling;
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>
</body>
</html>
