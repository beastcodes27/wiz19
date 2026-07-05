<?php
/**
 * landing.php — Path-based Landing Page Router
 *
 * URL format: yourdomain.com/mystore  (or localhost/flowtune/mystore on XAMPP)
 *
 * .htaccess rewrites /PREFIX → landing.php?subdomain=PREFIX
 * This file looks up the domain, finds the owner's selected template, and serves it.
 */

require_once __DIR__ . '/includes/db.php';

// ── 1. Get the subdomain prefix (always from query param — set by .htaccess) ──

$subdomain_prefix = trim($_GET['subdomain'] ?? '');

if (empty($subdomain_prefix) || !preg_match('/^[a-z0-9][a-z0-9\-]*$/i', $subdomain_prefix)) {
    http_response_code(400);
    show_error_page('Invalid or missing domain suffix.', 400);
    exit;
}

// ── 2. Look up the domain and its owner ──────────────────────────────────────

$stmt = $pdo->prepare("
    SELECT d.id, d.user_id, d.status,
           u.full_name, u.email, u.active_landing, u.gateway_api_key, u.webhook_url
    FROM   domains d
    JOIN   users   u ON u.id = d.user_id
    WHERE  d.domain_prefix = ?
    LIMIT  1
");
$stmt->execute([$subdomain_prefix]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    show_error_page(
        "The page <strong>/" . htmlspecialchars($subdomain_prefix) . "</strong> does not exist on this platform.",
        404
    );
    exit;
}

// ── 3. Load the selected template ────────────────────────────────────────────

$active_landing = $row['active_landing'] ?? 'landing1';

if (!preg_match('/^landing\d+$/', $active_landing) || !file_exists(__DIR__ . '/templates/' . $active_landing . '.php')) {
    $active_landing = 'landing1';
}

$template_path = __DIR__ . '/templates/' . $active_landing . '.php';

if (!file_exists($template_path)) {
    http_response_code(500);
    show_error_page('Landing template not found.', 500);
    exit;
}

// Expose domain owner data to the template (available as $domain_owner inside templates)
$domain_owner = [
    'user_id'          => (int) $row['user_id'],
    'name'             => $row['full_name'],
    'email'            => $row['email'],
    'active_landing'   => $active_landing,
    'gateway_api_key' => $row['gateway_api_key'],
    'webhook_url'      => $row['webhook_url'],
    'suffix'           => $subdomain_prefix,
];

// Fetch active videos uploaded by this specific user
$stmt = $pdo->prepare("SELECT * FROM videos WHERE user_id = ? AND status = 'active' ORDER BY sort_order ASC, created_at DESC");
$stmt->execute([$domain_owner['user_id']]);
$user_videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

include $template_path;
exit;

// ── Helper: styled error page ─────────────────────────────────────────────────

function show_error_page(string $message, int $code = 404): void {
    $titles = [400 => 'Bad Request', 404 => 'Page Not Found', 500 => 'Server Error'];
    $title  = $titles[$code] ?? 'Error';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= $code ?> — <?= htmlspecialchars($title) ?></title>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
        <style>
            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
            body {
                min-height: 100vh; display: flex; align-items: center; justify-content: center;
                background: #0f0f0f; color: #fff;
                font-family: 'Plus Jakarta Sans', sans-serif;
                padding: 24px;
            }
            .card {
                background: #1a1a1a; border: 1px solid #2a2a2a;
                border-radius: 24px; padding: 48px 40px;
                max-width: 480px; width: 100%; text-align: center;
                box-shadow: 0 40px 80px rgba(0,0,0,.6);
            }
            .code {
                font-size: 6rem; font-weight: 800; line-height: 1;
                background: linear-gradient(135deg,#ff416c,#ff4b2b);
                -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            }
            h1 { font-size: 1.4rem; font-weight: 700; margin: 16px 0 10px; }
            p  { color: #999; line-height: 1.6; font-size: .95rem; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="code"><?= $code ?></div>
            <h1><?= htmlspecialchars($title) ?></h1>
            <p><?= $message ?></p>
        </div>
    </body>
    </html>
    <?php
}
