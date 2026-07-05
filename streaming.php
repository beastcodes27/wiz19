<?php
if (file_exists('config.php')) {
    require_once 'config.php';
}
require_once 'includes/db.php';

// Safe fallback for SITE_URL if not defined in config.php
if (!defined('SITE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base_url = defined('BASE_URL') ? BASE_URL : '';
    define('SITE_URL', $protocol . $host . $base_url);
}

// Check database schema dynamically to handle database variations
$has_fake_views = false;
$api_key_col = 'api_key';
$webhook_token_col = 'webhook_token';

try {
    $videos_cols = $pdo->query("DESCRIBE videos")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('fake_views', $videos_cols)) {
        $has_fake_views = true;
    }
} catch (Exception $e) {}

try {
    $users_cols = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('gateway_api_key', $users_cols)) {
        $api_key_col = 'gateway_api_key';
    } elseif (in_array('api_key', $users_cols)) {
        $api_key_col = 'api_key';
    }
    if (in_array('webhook_url', $users_cols)) {
        $webhook_token_col = 'webhook_url';
    } elseif (in_array('webhook_token', $users_cols)) {
        $webhook_token_col = 'webhook_token';
    }
} catch (Exception $e) {}

// Check subdomain routing or direct user ID filtering
$subdomain_prefix = trim($_GET['subdomain'] ?? $_GET['prefix'] ?? '');
$url_creator_id = (int)($_GET['creator_id'] ?? $_GET['user_id'] ?? 0);
$domain_owner = null;

if (!empty($subdomain_prefix)) {
    try {
        $stmt = $pdo->prepare("
            SELECT d.user_id, u.{$api_key_col}, u.{$webhook_token_col}
            FROM domains d
            JOIN users u ON u.id = d.user_id
            WHERE d.domain_prefix = ?
            LIMIT 1
        ");
        $stmt->execute([$subdomain_prefix]);
        $domain_owner = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

if (!$domain_owner && $url_creator_id > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT id AS user_id, {$api_key_col}, {$webhook_token_col}
            FROM users
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$url_creator_id]);
        $domain_owner = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// Ensure table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS streaming_access (
        ip_address VARCHAR(45) PRIMARY KEY,
        creator_id INT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Ensure creator_id column exists
    $cols = $pdo->query("DESCRIBE streaming_access")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('creator_id', $cols)) {
        $pdo->exec("ALTER TABLE streaming_access ADD COLUMN creator_id INT NULL AFTER ip_address");
    }
} catch (Exception $e) {}

$has_access = false;
$ip = $_SERVER['REMOTE_ADDR'];

// Rule 1: Admin bypass
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $has_access = true;
}

// Rule 2: Access is granted ONLY via paid IP pass or secure cookie from landing page payments.
// Referrer-based access has been intentionally removed — anyone visiting streaming.php
// without a valid IP record or payment cookie must go through the paywall.

// Rule 3: 24-hour IP pass
$access_creator_id = null;
if (!$has_access) {
    $stmt = $pdo->prepare("SELECT creator_id FROM streaming_access WHERE ip_address = ? AND expires_at > NOW() LIMIT 1");
    $stmt->execute([$ip]);
    $access_creator_id = $stmt->fetchColumn();
    if ($access_creator_id !== false) {
        $has_access = true;
    }
} else {
    try {
        $stmt = $pdo->prepare("SELECT creator_id FROM streaming_access WHERE ip_address = ? AND expires_at > NOW() LIMIT 1");
        $stmt->execute([$ip]);
        $access_creator_id = $stmt->fetchColumn();
    } catch (Exception $e) {}
}

// Rule 4: Secure Cookie 24-hour Pass (Immune to dynamic IP changes)
if (!$has_access) {
    $check_creator_id = $domain_owner ? $domain_owner['user_id'] : 'admin';
    $cookie_tx_id = $_COOKIE['sf_pass_' . $check_creator_id] ?? '';
    if (!empty($cookie_tx_id)) {
        try {
            $stmt = $pdo->prepare("
                SELECT reference_id FROM transactions 
                WHERE reference_id = ? AND status = 'completed' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) 
                LIMIT 1
            ");
            $stmt->execute([$cookie_tx_id]);
            if ($stmt->fetch()) {
                $has_access = true;
                if ($check_creator_id !== 'admin') {
                    $access_creator_id = (int)$check_creator_id;
                }
            }
        } catch (Exception $e) {}
    }
}

// Fallback Cookie Detection: If no creator is specified in URL and not unlocked, find valid creator cookies
if (!$domain_owner && !$has_access) {
    foreach ($_COOKIE as $key => $val) {
        if (strpos($key, 'sf_pass_') === 0) {
            $cookie_creator_id = str_replace('sf_pass_', '', $key);
            if ($cookie_creator_id === 'admin') continue;
            $cookie_creator_id = (int)$cookie_creator_id;
            if ($cookie_creator_id > 0) {
                try {
                    $stmt = $pdo->prepare("
                        SELECT reference_id FROM transactions 
                        WHERE reference_id = ? AND status = 'completed' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) 
                        LIMIT 1
                    ");
                    $stmt->execute([$val]);
                    if ($stmt->fetch()) {
                        $access_creator_id = $cookie_creator_id;
                        $has_access = true;
                        break;
                    }
                } catch (Exception $e) {}
            }
        }
    }
}

// Fallback: If no creator is specified in URL, load the one from the paid IP pass or cookie
if (!$domain_owner && $access_creator_id > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT id AS user_id, {$api_key_col}, {$webhook_token_col}
            FROM users
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$access_creator_id]);
        $domain_owner = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// Fetch videos
try {
    $views_orderBy = $has_fake_views ? "(v.views + COALESCE(v.fake_views, 0))" : "v.views";

    if ($domain_owner) {
        // Filter by the subdomain owner's videos
        $stmt = $pdo->prepare("
            SELECT v.* FROM videos v
            WHERE v.user_id = ? AND v.status = 'active'
            ORDER BY {$views_orderBy} DESC, v.created_at DESC
        ");
        $stmt->execute([$domain_owner['user_id']]);
        $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $creator_token = $_GET['token'] ?? $_GET['api_key'] ?? '';
        if (!empty($creator_token)) {
            $stmt = $pdo->prepare("
                SELECT v.* FROM videos v
                JOIN users u ON v.user_id = u.id
                WHERE v.status = 'active' AND (u.{$api_key_col} = ? OR u.{$webhook_token_col} = ?)
                ORDER BY {$views_orderBy} DESC, v.created_at DESC
            ");
            $stmt->execute([$creator_token, $creator_token]);
            $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->query("SELECT * FROM videos WHERE status = 'active' ORDER BY {$views_orderBy} DESC, created_at DESC");
            $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (PDOException $e) {
    $videos = [];
}

// Admin / Creator API Key for payment
$admin_api_key = '';
if ($domain_owner && !empty($domain_owner[$api_key_col])) {
    $admin_api_key = $domain_owner[$api_key_col];
} else {
    $creator_token = $_GET['token'] ?? $_GET['api_key'] ?? '';
    if (!empty($creator_token)) {
        try {
            $stmt = $pdo->prepare("SELECT {$api_key_col} FROM users WHERE {$api_key_col} = ? OR {$webhook_token_col} = ? LIMIT 1");
            $stmt->execute([$creator_token, $creator_token]);
            $admin_api_key = $stmt->fetchColumn() ?: '';
        } catch (Exception $e) {}
    }
    if (empty($admin_api_key)) {
        try {
            $stmt = $pdo->query("SELECT {$api_key_col} FROM users WHERE role = 'admin' LIMIT 1");
            $admin_api_key = $stmt->fetchColumn() ?: '';
        } catch (Exception $e) {}
    }
}

function formatViews(int $n): string {
    if ($n >= 1_000_000) return round($n / 1_000_000, 1) . 'M';
    if ($n >= 1_000)     return round($n / 1_000, 1) . 'K';
    return (string)$n;
}

// ── ACCESS GATE ──────────────────────────────────────────────────────────────
// $has_access is false → user has not paid. Show paywall and exit.
// Never expose the video list without a valid payment cookie or IP pass.
if (!$has_access) {
    $paywall_creator_id = (int)($domain_owner['user_id'] ?? 0);
    $channel_packages   = [];

    if ($paywall_creator_id > 0) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM packages WHERE user_id = ? ORDER BY price ASC LIMIT 6");
            $stmt->execute([$paywall_creator_id]);
            $channel_packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Premium Access Required</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            *{margin:0;padding:0;box-sizing:border-box}
            body{
                font-family:'Inter',sans-serif;background:#09090f;color:#f8fafc;
                min-height:100vh;display:flex;align-items:center;justify-content:center;
                padding:20px;
                background-image:radial-gradient(ellipse 80% 50% at 50% -10%,rgba(99,102,241,.22),transparent);
            }
            .card{
                background:linear-gradient(145deg,#13141f,#0d0e17);
                border:1px solid rgba(99,102,241,.2);border-radius:24px;
                padding:48px 40px;max-width:460px;width:100%;
                box-shadow:0 25px 60px rgba(0,0,0,.6),inset 0 1px 0 rgba(255,255,255,.04);
                animation:up .4s ease;
            }
            @keyframes up{from{opacity:0;transform:translateY(28px)}to{opacity:1;transform:translateY(0)}}
            .lock{
                width:64px;height:64px;background:rgba(99,102,241,.12);
                border:1px solid rgba(99,102,241,.3);border-radius:50%;
                display:flex;align-items:center;justify-content:center;
                font-size:1.6rem;color:#818cf8;margin:0 auto 20px;
            }
            h1{font-size:1.4rem;font-weight:800;text-align:center;margin-bottom:6px}
            .sub{color:#94a3b8;font-size:.85rem;text-align:center;margin-bottom:26px}
            .pkgs{display:flex;flex-direction:column;gap:10px;margin-bottom:18px}
            .pkg{
                background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);
                border-radius:14px;padding:14px 16px;
                display:flex;justify-content:space-between;align-items:center;
                cursor:pointer;transition:.2s;
            }
            .pkg:hover{border-color:rgba(99,102,241,.4)}
            .pkg.active{border-color:#6366f1;background:rgba(99,102,241,.1)}
            .pkg-name{font-weight:700;font-size:.9rem;display:block}
            .pkg-dur{font-size:.72rem;color:#64748b}
            .pkg-price{font-weight:800;color:#818cf8;font-size:.95rem}
            .radio{width:18px;height:18px;border:2px solid #334155;border-radius:50%;position:relative;flex-shrink:0;margin-left:10px}
            .pkg.active .radio{border-color:#6366f1}
            .pkg.active .radio::after{content:'';position:absolute;inset:3px;background:#6366f1;border-radius:50%}
            .inp{position:relative;margin-top:4px}
            .inp i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#64748b;font-size:.85rem}
            .inp input{
                width:100%;padding:14px 14px 14px 42px;background:rgba(0,0,0,.5);
                border:1px solid #1e293b;border-radius:12px;color:#fff;outline:none;
                font-size:1rem;font-family:inherit;transition:border-color .2s;
            }
            .inp input:focus{border-color:#6366f1}
            .pay-btn{
                width:100%;margin-top:16px;padding:15px;border-radius:14px;border:none;
                cursor:pointer;background:linear-gradient(135deg,#6366f1,#8b5cf6);
                color:#fff;font-size:1rem;font-weight:800;font-family:inherit;
                display:flex;align-items:center;justify-content:center;gap:8px;
                transition:opacity .2s,transform .2s;min-height:52px;
            }
            .pay-btn:hover:not(:disabled){opacity:.9;transform:translateY(-2px)}
            .pay-btn:disabled{opacity:.5;cursor:not-allowed;transform:none}
            .note{font-size:.7rem;color:#64748b;text-align:center;margin-top:12px}
            .success{display:none;text-align:center;padding:20px 0}
            .success i{font-size:3rem;color:#22c55e;margin-bottom:14px;display:block}
            @media(max-width:480px){.card{padding:32px 22px;border-radius:20px}}
        </style>
    </head>
    <body>
        <div class="card">
            <div id="payContent">
                <div class="lock"><i class="fas fa-lock"></i></div>
                <h1>Premium Access Required</h1>
                <p class="sub">Pay once — unlock all videos for 24 hours.</p>

                <div class="pkgs" id="pkgList">
                    <?php if (!empty($channel_packages)): ?>
                        <?php foreach ($channel_packages as $i => $pkg): ?>
                            <div class="pkg <?= $i === 0 ? 'active' : '' ?>"
                                 onclick="selectPkg('<?= htmlspecialchars((string)$pkg['id']) ?>', <?= $i ?>)">
                                <div>
                                    <span class="pkg-name"><?= htmlspecialchars($pkg['name']) ?></span>
                                    <span class="pkg-dur"><?= htmlspecialchars((string)($pkg['duration_days'] ?? 1)) ?> Day(s) Access</span>
                                </div>
                                <div style="display:flex;align-items:center;gap:8px">
                                    <span class="pkg-price">TSH <?= number_format($pkg['price']) ?></span>
                                    <div class="radio"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="pkg active" onclick="selectPkg('default', 0)">
                            <div>
                                <span class="pkg-name">24-Hour Full Access</span>
                                <span class="pkg-dur">Unlimited access for 24 Hours</span>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px">
                                <span class="pkg-price">TSH 1,000</span>
                                <div class="radio"></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="inp">
                    <i class="fas fa-phone"></i>
                    <input type="tel" id="phoneInput" placeholder="Phone number (07XXXXXXXXX)">
                </div>
                <input type="hidden" id="selPkg" value="<?= !empty($channel_packages) ? htmlspecialchars((string)$channel_packages[0]['id']) : 'default' ?>">

                <button class="pay-btn" id="payBtn" onclick="submitPay()">
                    <i class="fas fa-lock-open"></i> UNLOCK ALL VIDEOS
                </button>
                <p class="note"><i class="fas fa-shield-alt"></i> Secure mobile money payment. No account needed.</p>
            </div>

            <div class="success" id="successBox">
                <i class="fas fa-check-circle"></i>
                <h2 style="margin-bottom:8px">Access Granted!</h2>
                <p style="color:#94a3b8;font-size:.875rem">Redirecting to your videos...</p>
            </div>
        </div>

        <script>
        const _BASE = '<?= defined("BASE_URL") ? BASE_URL : "" ?>';
        const _CID  = <?= (int)($domain_owner['user_id'] ?? 0) ?>;
        let _poll;

        function selectPkg(id, idx) {
            document.querySelectorAll('.pkg').forEach((el, i) => el.classList.toggle('active', i === idx));
            document.getElementById('selPkg').value = id;
        }

        async function api(url, opts) {
            const r = await fetch(url, opts), t = await r.text();
            try { return JSON.parse(t); } catch(e) { throw new Error(t.substring(0,200)); }
        }

        async function submitPay() {
            const phone = document.getElementById('phoneInput').value.trim();
            const pkg   = document.getElementById('selPkg').value;
            const btn   = document.getElementById('payBtn');
            if (!/^0[0-9]{9}$/.test(phone)) { alert('Enter a valid phone number (07XXXXXXXXX).'); return; }

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            try {
                const d = await api(_BASE + '/api/process_package.php', {
                    method: 'POST', headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({ package_id: pkg, phone })
                });
                if (d.status === 'success') {
                    btn.innerHTML = '<i class="fas fa-key"></i> Enter PIN on your phone...';
                    poll(d.tranID);
                } else {
                    alert(d.message || 'Payment failed. Try again.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-lock-open"></i> UNLOCK ALL VIDEOS';
                }
            } catch(e) {
                alert('Connection error: ' + e.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-lock-open"></i> UNLOCK ALL VIDEOS';
            }
        }

        function poll(tranID) {
            if (_poll) clearInterval(_poll);
            _poll = setInterval(async () => {
                try {
                    const d = await api(_BASE + '/api/check_payment.php?tranid=' + tranID);
                    const s = (d.payment_status || d.status || '').toUpperCase();
                    if (s === 'COMPLETED' || s === 'SUCCESS') {
                        clearInterval(_poll);
                        const exp = new Date(Date.now() + 86400000).toUTCString();
                        document.cookie = 'sf_pass_' + _CID + '=' + encodeURIComponent(tranID) + '; expires=' + exp + '; path=/; SameSite=Lax';
                        document.getElementById('payContent').style.display = 'none';
                        document.getElementById('successBox').style.display  = 'block';
                        setTimeout(() => { window.location.href = _BASE + '/streaming.php?creator_id=' + _CID; }, 1500);
                    } else if (s === 'FAILED' || s === 'CANCELLED') {
                        clearInterval(_poll);
                        alert('Payment failed or was cancelled.');
                        const btn = document.getElementById('payBtn');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-lock-open"></i> UNLOCK ALL VIDEOS';
                    }
                } catch(e) { console.error('Poll error', e); }
            }, 3000);
        }
        </script>
    </body>
    </html>
    <?php
    exit();
}
?>
<!DOCTYPE html>
<html lang="sw" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Streaming – StreamFlow</title>
    <meta name="description" content="StreamFlow – angalia video za premium kwa bei nafuu. Lipia mara moja, tazama masaa 24.">
    <meta name="theme-color" content="#6366f1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://vjs.zencdn.net/8.0.4/video-js.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ============================================================
           DESIGN TOKENS
        ============================================================ */
        :root,
        [data-theme="dark"] {
            --primary:        #6366f1;
            --primary-h:      #4f46e5;
            --accent:         #ec4899;
            --bg-body:        #0a0b10;
            --bg-nav:         rgba(10,11,16,0.97);
            --bg-card:        #151720;
            --bg-wrapper:     #1a1c28;
            --bg-input:       #000;
            --text-main:      #f8fafc;
            --text-sub:       #94a3b8;
            --text-dim:       #64748b;
            --border:         rgba(255,255,255,0.07);
            --border-h:       rgba(99,102,241,0.45);
            --shadow-card:    0 4px 24px rgba(0,0,0,0.45);
            --shadow-xl:      0 20px 50px rgba(0,0,0,0.6);
            --overlay-bg:     rgba(10,11,16,0.97);
            --scrollbar-bg:   #111;
            --title-bar:      linear-gradient(135deg,rgba(99,102,241,.15),rgba(236,72,153,.08));
        }
        [data-theme="light"] {
            --primary:        #6366f1;
            --primary-h:      #4f46e5;
            --accent:         #ec4899;
            --bg-body:        #f8fafc;
            --bg-nav:         rgba(255,255,255,0.97);
            --bg-card:        #ffffff;
            --bg-wrapper:     #ffffff;
            --bg-input:       #f1f5f9;
            --text-main:      #0f172a;
            --text-sub:       #475569;
            --text-dim:       #94a3b8;
            --border:         #e2e8f0;
            --border-h:       rgba(99,102,241,0.5);
            --shadow-card:    0 4px 18px rgba(0,0,0,0.08);
            --shadow-xl:      0 20px 40px rgba(0,0,0,0.14);
            --overlay-bg:     rgba(10,11,16,0.97);
            --scrollbar-bg:   #e2e8f0;
            --title-bar:      linear-gradient(135deg,rgba(99,102,241,.06),rgba(236,72,153,.04));
        }

        /* ============================================================
           BASE
        ============================================================ */
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            overflow-x: hidden;
            transition: background .35s, color .35s;
        }

        body::before {
            content:'';
            position:fixed; inset:0;
            background:
                radial-gradient(circle at 15% 15%, rgba(99,102,241,.07) 0%, transparent 50%),
                radial-gradient(circle at 85% 85%, rgba(236,72,153,.07) 0%, transparent 50%);
            pointer-events:none; z-index:0;
        }

        /* scrollbar */
        ::-webkit-scrollbar { width:8px; }
        ::-webkit-scrollbar-track { background:var(--scrollbar-bg); }
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius:10px;
        }

        /* ============================================================
           NAVBAR
        ============================================================ */
        .navbar {
            position:fixed; top:0; width:100%; z-index:1000;
            background: var(--bg-nav);
            backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid var(--border);
            padding:.9rem 2rem;
            display:flex; align-items:center; justify-content:space-between;
            transition: background .35s, border-color .35s, box-shadow .3s;
        }
        .navbar.scrolled { box-shadow:0 4px 20px rgba(0,0,0,.3); }

        .brand-wrap { display:flex; align-items:center; gap:.6rem; text-decoration:none; }
        .brand-icon {
            width:38px; height:38px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius:10px;
            display:flex; align-items:center; justify-content:center;
            color:#fff; font-size:1rem;
            box-shadow:0 4px 12px rgba(99,102,241,.4);
            flex-shrink:0;
        }
        .brand-name {
            font-size:1.5rem; font-weight:800;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip:text; -webkit-text-fill-color:transparent;
            background-clip:text; letter-spacing:-0.5px;
        }

        .nav-right { display:flex; align-items:center; gap:.8rem; }

        /* Back btn in navbar (visible in player view) */
        .nav-back-btn {
            display:none;
            align-items:center; gap:8px;
            background: var(--bg-card);
            border:1px solid var(--border);
            color: var(--text-main);
            padding:7px 18px; border-radius:25px;
            font-size:.85rem; font-weight:600;
            cursor:pointer; transition:all .25s;
            font-family:'Poppins',sans-serif;
        }
        .nav-back-btn:hover { background:var(--primary); color:#fff; border-color:var(--primary); }
        .nav-back-btn.visible { display:flex; }

        .theme-toggle {
            background: transparent;
            border:1px solid var(--border);
            border-radius:50px; padding:6px 14px;
            cursor:pointer; display:flex; align-items:center; gap:8px;
            color:var(--text-sub); font-size:.85rem; font-weight:600;
            transition:all .3s; font-family:'Poppins',sans-serif;
        }
        .theme-toggle:hover { background:var(--primary); color:#fff; border-color:var(--primary); }
        .theme-toggle i { font-size:1rem; }

        /* ============================================================
           HERO
        ============================================================ */
        .hero {
            padding:130px 2rem 40px;
            text-align:center; position:relative; z-index:1;
            transition: opacity .3s, transform .3s;
        }
        .hero-title {
            font-size:clamp(2rem,5vw,3.5rem); font-weight:800;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip:text; -webkit-text-fill-color:transparent;
            background-clip:text; line-height:1.2; margin-bottom:.8rem;
            animation: fadeUp .8s ease both;
        }
        .hero-sub {
            font-size:1.1rem; font-weight:400; color:var(--text-sub);
            animation: fadeUp .8s ease .15s both;
        }
        @keyframes fadeUp {
            from { opacity:0; transform:translateY(28px); }
            to   { opacity:1; transform:translateY(0); }
        }

        /* ============================================================
           VIDEO GRID
        ============================================================ */
        .main-wrap {
            max-width:1440px; margin:0 auto; padding:0 2rem;
            position:relative; z-index:1;
            transition: filter .3s;
        }
        .video-grid {
            display:grid;
            grid-template-columns:repeat(auto-fill, minmax(300px,1fr));
            gap:1.8rem; padding:1rem 0 5rem;
        }
        .video-card {
            background:var(--bg-card);
            border-radius:0; overflow:hidden; cursor:pointer;
            border:1px solid var(--border);
            box-shadow:var(--shadow-card);
            transition:transform .35s cubic-bezier(.25,.46,.45,.94), box-shadow .35s, border-color .25s;
            animation:slideUp .55s ease both;
            position:relative;
        }
        .video-card:nth-child(1){animation-delay:.05s}
        .video-card:nth-child(2){animation-delay:.10s}
        .video-card:nth-child(3){animation-delay:.15s}
        .video-card:nth-child(4){animation-delay:.20s}
        .video-card:nth-child(5){animation-delay:.25s}
        .video-card:nth-child(6){animation-delay:.30s}
        @keyframes slideUp {
            from{opacity:0;transform:translateY(30px)}
            to  {opacity:1;transform:translateY(0)}
        }
        .video-card:hover { transform:translateY(-10px); box-shadow:var(--shadow-xl); border-color:var(--border-h); }

        .video-thumb { position:relative; aspect-ratio:16/9; overflow:hidden; background:#111; border-radius:0; }
        .video-thumb img { width:100%;height:100%;object-fit:cover;transition:transform .5s ease; }
        .video-card:hover .video-thumb img { transform:scale(1.12); }

        .res-badge {
            position:absolute; top:10px; right:10px;
            background:rgba(99,102,241,.85); backdrop-filter:blur(6px);
            color:#fff; padding:4px 12px; border-radius:20px;
            font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px;
            pointer-events:none;
        }
        .play-overlay {
            position:absolute; top:50%; left:50%;
            transform:translate(-50%,-50%);
            width:64px;height:64px; background:rgba(255,255,255,.92);
            border-radius:50%; display:flex; align-items:center; justify-content:center;
            opacity:0; transition:opacity .3s, transform .3s;
            box-shadow:0 8px 24px rgba(0,0,0,.4); pointer-events:none;
        }
        .play-overlay i { color:var(--primary); font-size:24px; margin-left:4px; }
        .video-card:hover .play-overlay { opacity:1; transform:translate(-50%,-50%) scale(1.08); }

        .card-body-info { padding:1.1rem 1.25rem 1.25rem; }
        .card-title-text {
            font-size:.98rem; font-weight:600; color:var(--text-main);
            margin-bottom:.75rem;
            display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;
            overflow:hidden; line-height:1.5;
        }
        .card-meta { display:flex; align-items:center; justify-content:space-between; font-size:.85rem; }
        .card-views { display:flex; align-items:center; gap:6px; color:var(--text-sub); }
        .card-views i { color:var(--primary); font-size:.9rem; }
        .card-duration {
            background:var(--border); color:var(--text-main);
            padding:4px 10px; border-radius:8px; font-size:.78rem; font-weight:600;
        }

        /* empty state */
        .empty-state { text-align:center; padding:80px 20px; color:var(--text-dim); }
        .empty-state i { font-size:3rem; margin-bottom:1rem; color:var(--primary); }

        /* ============================================================
           PLAYER VIEW  (full-page slide-in)
        ============================================================ */
        .player-view {
            position:fixed; inset:0; z-index:500;
            background:var(--bg-body);
            overflow-y:auto;
            transform:translateY(100%);
            transition:transform .45s cubic-bezier(.25,.46,.45,.94);
            padding-top:80px; /* below navbar */
        }
        .player-view.open { transform:translateY(0); }

        .player-inner {
            max-width:1100px; margin:0 auto;
            padding:2rem 2rem 4rem;
            animation: fadeUp .5s ease;
        }

        /* Video.js wrapper */
        .vjs-wrapper {
            background:var(--bg-wrapper);
            border-radius:0;
            padding:1.25rem;
            box-shadow:var(--shadow-xl);
            margin-bottom:1.5rem;
            border:1px solid var(--border);
            position:relative; overflow:hidden;
        }

        /* Video.js theming */
        .video-js {
            width:100% !important; border-radius:0; overflow:hidden;
        }
        .video-js .vjs-control-bar {
            background:rgba(0,0,0,.88) !important;
            backdrop-filter:blur(10px);
            border-radius:0;
        }
        .video-js .vjs-big-play-button {
            background:linear-gradient(135deg,var(--primary),var(--accent)) !important;
            border:none !important; border-radius:50% !important;
            width:80px !important; height:80px !important;
            font-size:2rem !important;
            top:50% !important; left:50% !important;
            transform:translate(-50%,-50%) !important;
            transition:transform .3s, box-shadow .3s !important;
            margin:0 !important;
        }
        .video-js .vjs-big-play-button:hover {
            transform:translate(-50%,-50%) scale(1.1) !important;
            box-shadow:0 8px 30px rgba(99,102,241,.5) !important;
        }
        .video-js .vjs-big-play-button .vjs-icon-placeholder::before { color:#fff !important; }
        .video-js .vjs-progress-control .vjs-progress-holder {
            background:rgba(255,255,255,.25) !important; border-radius:4px !important;
        }
        .video-js .vjs-play-progress {
            background:var(--primary) !important; border-radius:4px !important;
        }
        .video-js .vjs-load-progress { background:rgba(255,255,255,.4) !important; border-radius:4px !important; }
        .video-js .vjs-volume-level { background:var(--primary) !important; }
        .video-js .vjs-control:focus { outline:2px solid var(--primary) !important; }

        /* loading shimmer on wrapper */
        .vjs-wrapper.loading {
            background: linear-gradient(90deg, var(--bg-card) 25%, var(--bg-wrapper) 50%, var(--bg-card) 75%);
            background-size:200% 100%;
            animation:shimmer 1.5s infinite;
        }
        @keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }

        /* Title card */
        .title-card {
            background:var(--bg-card);
            border-radius:0; padding:1.8rem 2rem;
            border:1px solid var(--border);
            box-shadow:var(--shadow-card);
            position:relative; overflow:hidden;
        }
        .title-card::before {
            content:'';
            position:absolute; top:0; left:0;
            width:5px; height:100%;
            background:linear-gradient(135deg,var(--primary),var(--accent));
        }
        .title-card h2 {
            font-size:1.6rem; font-weight:700;
            color:var(--text-main); line-height:1.4;
            padding-left:1rem; margin-bottom:.8rem;
        }
        .title-meta {
            display:flex; align-items:center; gap:1.5rem;
            padding-left:1rem; font-size:.9rem;
            color:var(--text-sub); flex-wrap:wrap;
        }
        .title-meta span { display:flex; align-items:center; gap:6px; }
        .title-meta i { color:var(--primary); }

        /* keyboard shortcuts hint */
        .shortcuts-hint {
            margin-top:1.2rem;
            padding:.9rem 1.2rem;
            background:rgba(99,102,241,.06);
            border:1px solid rgba(99,102,241,.12);
            border-radius:0;
            font-size:.78rem; color:var(--text-dim);
            display:flex; flex-wrap:wrap; gap:.5rem 1.5rem;
        }
        .shortcut-key {
            display:inline-flex; align-items:center; gap:5px;
        }
        .shortcut-key kbd {
            background:var(--bg-card);
            border:1px solid var(--border);
            border-radius:5px; padding:2px 7px;
            font-size:.72rem; font-family:'Poppins',sans-serif;
            color:var(--text-main);
        }

        /* ============================================================
           PAYWALL
        ============================================================ */
        .paywall-overlay {
            position:fixed; inset:0;
            background:var(--overlay-bg);
            backdrop-filter:blur(14px) saturate(150%);
            z-index:2000;
            display:flex; justify-content:center; align-items:center;
            padding:20px;
        }
        .paywall-card {
            background:var(--bg-card);
            border-radius:20px; padding:2rem 1.8rem;
            max-width:420px; width:100%;
            border:1px solid var(--border);
            box-shadow:0 30px 60px rgba(0,0,0,.5);
            text-align:center; animation:fadeUp .5s ease;
        }
        .paywall-icon {
            width:60px;height:60px;
            background:linear-gradient(135deg,var(--primary),var(--accent));
            border-radius:50%; display:flex; align-items:center; justify-content:center;
            margin:0 auto 1rem; font-size:1.5rem; color:#fff;
            box-shadow:0 8px 20px rgba(99,102,241,.35);
        }
        .paywall-card h2 { font-size:1.4rem; font-weight:700; color:var(--text-main); margin-bottom:.3rem; }
        .paywall-card > p { color:var(--text-sub); font-size:.9rem; margin-bottom:1.2rem; }

        .price-block { margin-bottom:1.2rem; }
        .price-label { font-size:.78rem; color:var(--text-dim); }
        .price-amount {
            font-size:2.4rem; font-weight:800; letter-spacing:-1px;
            background:linear-gradient(135deg,var(--primary),var(--accent));
            -webkit-background-clip:text; -webkit-text-fill-color:transparent;
            background-clip:text;
        }
        .price-note { font-size:.78rem; color:var(--text-dim); margin-top:3px; }

        .pay-input-group { text-align:left; margin-bottom:1rem; }
        .pay-input-group label { display:block; font-size:.82rem; font-weight:600; color:var(--text-sub); margin-bottom:6px; }
        .pay-input-group input {
            width:100%; background:var(--bg-input); border:1px solid var(--border);
            padding:12px 14px; color:var(--text-main);
            border-radius:10px; font-size:1rem; outline:none;
            font-family:'Poppins',sans-serif; transition:border-color .2s;
        }
        .pay-input-group input:focus { border-color:var(--primary); }

        .payment-steps {
            margin:.8rem 0 1.2rem; padding:12px 14px;
            background:rgba(99,102,241,.07);
            border-radius:10px; font-size:.76rem; color:var(--text-sub);
            text-align:left; border:1px solid rgba(99,102,241,.12);
        }
        .step { display:flex; gap:10px; margin-bottom:7px; align-items:flex-start; }
        .step:last-child { margin-bottom:0; }
        .step-num {
            width:18px;height:18px; flex-shrink:0;
            background:linear-gradient(135deg,var(--primary),var(--accent));
            color:#fff; border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            font-size:.6rem; font-weight:800; margin-top:2px;
        }

        .btn-pay {
            width:100%;
            background:linear-gradient(135deg,var(--primary),var(--accent));
            color:#fff; padding:14px; border-radius:10px; border:none;
            font-size:1rem; font-weight:700; cursor:pointer;
            transition:opacity .2s, transform .2s;
            font-family:'Poppins',sans-serif; letter-spacing:.5px;
        }
        .btn-pay:disabled { opacity:.65; pointer-events:none; }
        .btn-pay:not(:disabled):hover { opacity:.9; transform:translateY(-1px); }
        #payStatus { margin-top:12px; font-size:.85rem; color:var(--text-sub); min-height:1.2em; }

        /* ============================================================
           RESPONSIVE
        ============================================================ */
        @media (max-width:768px) {
            .main-wrap { padding:0 1rem; }
            .hero { padding-top:110px; }
            .video-grid { grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:1.2rem; }
            .navbar { padding:.7rem 1rem; }
            .player-inner { padding:1.2rem 1rem 4rem; }
            .title-card h2 { font-size:1.2rem; }
            .shortcuts-hint { display:none; }
        }
        @media (max-width:480px) {
            .video-grid { grid-template-columns:1fr; }
            .hero-title { font-size:1.9rem; }
        }
    </style>
</head>
<body>

<!-- ==================== NAVBAR ==================== -->
<nav class="navbar" id="navbar">
    <a href="#" class="brand-wrap" id="brandLink" onclick="handleBrandClick(event)">
        <div class="brand-icon"><i class="fas fa-play"></i></div>
        <span class="brand-name">StreamFlow</span>
    </a>
    <div class="nav-right">
        <!-- Back button shown only in player view -->
        <button class="nav-back-btn" id="navBackBtn" onclick="closePlayer()">
            <i class="fas fa-arrow-left"></i> Rudi Nyuma
        </button>
        <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()">
            <i class="fas fa-moon" id="themeIcon"></i>
            <span id="themeLabel">Light</span>
        </button>
    </div>
</nav>

<!-- ==================== HERO ==================== -->
<div class="hero" id="heroSection">
    <h1 class="hero-title">Premium Video Streaming</h1>
    <p class="hero-sub">Lipia mara moja – tazama video zote kwa masaa 24</p>
</div>

<!-- ==================== VIDEO GRID ==================== -->
<main class="main-wrap" id="mainContainer"
      style="<?= !$has_access ? 'filter:blur(8px);pointer-events:none;user-select:none;' : '' ?>">

    <?php if (empty($videos)): ?>
    <div class="empty-state">
        <i class="fas fa-film"></i>
        <p>Hakuna video zinazopatikana kwa sasa.</p>
    </div>
    <?php else: ?>
    <div class="video-grid">
        <?php foreach($videos as $vid):
            $totalViews = (int)($vid['views'] ?? 0) + (int)($vid['fake_views'] ?? 0);
            $viewsStr   = formatViews($totalViews);
            $thumbUrl   = htmlspecialchars($vid['thumbnail_url'] ?: 'assets/images/placeholder.jpg');
            $videoUrl   = htmlspecialchars($vid['video_url'] ?? '');
            $title      = htmlspecialchars($vid['title'] ?? 'Untitled');
            $res        = !empty($vid['quality']) ? htmlspecialchars($vid['quality']) : '720p';
            $duration   = !empty($vid['duration']) ? htmlspecialchars($vid['duration']) : '—';
        ?>
        <div class="video-card"
             onclick="openPlayer('<?= $videoUrl ?>', '<?= addslashes($vid['title'] ?? '') ?>', '<?= $viewsStr ?>', '<?= $duration ?>')">
            <div class="video-thumb">
                <img src="<?= $thumbUrl ?>" alt="<?= $title ?>" loading="lazy">
                <div class="res-badge"><?= $res ?></div>
                <div class="play-overlay"><i class="fas fa-play"></i></div>
            </div>
            <div class="card-body-info">
                <div class="card-title-text"><?= $title ?></div>
                <div class="card-meta">
                    <div class="card-views">
                        <i class="fas fa-eye"></i>
                        <span><?= $viewsStr ?> Views</span>
                    </div>
                    <div class="card-duration">
                        <i class="fas fa-clock" style="font-size:.7rem;margin-right:3px;"></i>
                        <?= $duration ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>

<!-- ==================== PAYWALL ==================== -->
<?php if (!$has_access): ?>
<div class="paywall-overlay" id="paywallOverlay">
    <div class="paywall-card">
        <div class="paywall-icon"><i class="fas fa-shield-alt"></i></div>
        <h2>Malipo Salama</h2>
        <p>Fungua ufikiaji wa premium kwa Pesa za Simu</p>

        <div class="price-block">
            <div class="price-label">Jumla ya Kulipa:</div>
            <div class="price-amount">TSH 1,000</div>
            <div class="price-note">Tiketi ya masaa 24 – video zote bila kikomo</div>
        </div>

        <div class="pay-input-group">
            <label>Nambari ya Simu <span style="font-weight:400;">(Ombi la PIN litatumwa hapa)</span></label>
            <input type="tel" id="phoneNumber" placeholder="07xx xxx xxx" pattern="[0-9]*" autocomplete="tel">
        </div>

        <div class="payment-steps">
            <div class="step"><div class="step-num">1</div><div>Weka nambari yako ya simu inayofanya kazi hapo juu.</div></div>
            <div class="step"><div class="step-num">2</div><div>Bonyeza <strong>Lipa Sasa</strong> — utapata <strong>ombi la PIN</strong> kwenye simu yako.</div></div>
            <div class="step"><div class="step-num">3</div><div>Weka PIN yako ya Pesa za Simu kukamilisha malipo.</div></div>
        </div>

        <button class="btn-pay" id="payBtn" onclick="processPayment()">
            <i class="fas fa-lock" style="margin-right:6px;"></i>LIPA SASA
        </button>
        <p id="payStatus"></p>
    </div>
</div>
<script>
    let paymentCheckInterval;
    const adminKey = "<?= htmlspecialchars($admin_api_key) ?>";

    async function processPayment() {
        const phone      = document.getElementById('phoneNumber').value.trim();
        const btn        = document.getElementById('payBtn');
        const statusText = document.getElementById('payStatus');
        if (!phone || phone.length < 10) { alert('Tafadhali weka nambari sahihi ya simu (angalau tarakimu 10).'); return; }
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i>INAANZA...';
        statusText.innerText = '';
        try {
            let res = await fetch('<?= BASE_URL ?>/api/pay.php', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({ phone, amount:'1000', videoID:'PASS', token:adminKey })
            });
            let data = await res.json();
            if (data.success) {
                btn.innerHTML = '<i class="fas fa-mobile-alt" style="margin-right:6px;"></i>ANGALIA SIMU YAKO...';
                statusText.innerText = 'Tafadhali angalia simu yako kwa ombi la PIN.';
                checkStatus(data.transaction_id, data.redirect_url);
            } else { alert('Hitilafu: ' + data.message); resetPayBtn(); }
        } catch(e) { alert('Hitilafu ya mtandao. Tafadhali jaribu tena.'); resetPayBtn(); }
    }

    function resetPayBtn() {
        const b = document.getElementById('payBtn');
        b.innerHTML = '<i class="fas fa-lock" style="margin-right:6px;"></i>LIPA SASA';
        b.disabled = false;
    }

    function checkStatus(tranId, redirectUrl) {
        let attempts = 0;
        paymentCheckInterval = setInterval(async () => {
            attempts++;
            if (attempts > 20) { clearInterval(paymentCheckInterval); alert('Muda wa malipo umeisha. Tafadhali jaribu tena.'); resetPayBtn(); return; }
            try {
                let res = await fetch('<?= BASE_URL ?>/api/check_status.php?tran_id=' + encodeURIComponent(tranId) + '&token=' + encodeURIComponent(adminKey) + '&_=' + Date.now());
                let d = await res.json();
                if (['completed','success','paid'].includes(d.status)) {
                    clearInterval(paymentCheckInterval);
                    const btn = document.getElementById('payBtn');
                    btn.style.background = '#10b981';
                    btn.innerHTML = '<i class="fas fa-check-circle" style="margin-right:6px;"></i>IMETHIBITISHWA!';
                    document.getElementById('payStatus').innerText = '✅ Malipo Yamethibitishwa! Inafungua...';
                    
                    // Set a secure 24-hour cookie for this creator/page to persist across dynamic mobile IP address changes!
                    const creatorId = "<?= $domain_owner ? $domain_owner['user_id'] : 'admin' ?>";
                    const cookieName = "sf_pass_" + creatorId;
                    const expires = new Date(Date.now() + 24 * 60 * 60 * 1000).toUTCString();
                    document.cookie = cookieName + "=" + encodeURIComponent(tranId) + "; expires=" + expires + "; path=/; SameSite=Lax";

                    setTimeout(() => {
                        if (redirectUrl && redirectUrl !== 'streaming.php') {
                            let finalUrl = redirectUrl;
                            if (!finalUrl.startsWith('http')) {
                                finalUrl = '<?= BASE_URL ?>/' + finalUrl;
                            }
                            window.location.href = finalUrl;
                        } else {
                            const overlay = document.getElementById('paywallOverlay');
                            if (overlay) overlay.style.display = 'none';
                            const main = document.getElementById('mainContainer');
                            if (main) { main.style.filter='none'; main.style.pointerEvents='auto'; main.style.userSelect='auto'; }
                        }
                    }, 1500);
                } else if (['failed','cancelled','rejected','expired'].includes(d.status)) {
                    clearInterval(paymentCheckInterval);
                    alert('Malipo yameshindwa au yameghairiwa.');
                    resetPayBtn();
                    document.getElementById('payStatus').innerText = '';
                }
            } catch(e) {}
        }, 5000);
    }
</script>
<?php endif; ?>

<!-- ==================== PLAYER VIEW ==================== -->
<div class="player-view" id="playerView">
    <div class="player-inner">

        <!-- Video.js player -->
        <div class="vjs-wrapper" id="vjsWrapper">
            <video id="stream-player"
                   class="video-js vjs-default-skin vjs-big-play-centered"
                   controls preload="none"
                   data-setup='{"fluid":true,"responsive":true,"playbackRates":[0.5,1,1.25,1.5,2]}'>
                <source id="videoSrc" src="" type="video/mp4">
                <p class="vjs-no-js">Tafadhali wezesha JavaScript kuona video hii.</p>
            </video>
        </div>

        <!-- Title & Meta -->
        <div class="title-card">
            <h2 id="playerTitle">Inapakia...</h2>
            <div class="title-meta">
                <span><i class="fas fa-play-circle"></i> Inaendelea</span>
                <span><i class="fas fa-clock"></i> <span id="playerDuration">--:--</span></span>
                <span><i class="fas fa-eye"></i> <span id="playerViews">—</span> Views</span>
                <span><i class="fas fa-shield-alt"></i> Premium Quality</span>
            </div>

            <!-- Keyboard shortcuts hint -->
            <div class="shortcuts-hint">
                <span class="shortcut-key"><kbd>Space</kbd> Play/Pause</span>
                <span class="shortcut-key"><kbd>←</kbd> -10s</span>
                <span class="shortcut-key"><kbd>→</kbd> +10s</span>
                <span class="shortcut-key"><kbd>↑</kbd> Vol+</span>
                <span class="shortcut-key"><kbd>↓</kbd> Vol-</span>
                <span class="shortcut-key"><kbd>F</kbd> Fullscreen</span>
                <span class="shortcut-key"><kbd>Esc</kbd> Rudi Nyuma</span>
            </div>
        </div>

    </div>
</div>

<!-- ==================== SCRIPTS ==================== -->
<script src="https://vjs.zencdn.net/8.0.4/video.min.js"></script>
<script>
    /* ---- Theme ---- */
    (function() {
        const saved = localStorage.getItem('sf_theme') || 'dark';
        document.documentElement.setAttribute('data-theme', saved);
        updateThemeUI(saved);
    })();

    function toggleTheme() {
        const cur  = document.documentElement.getAttribute('data-theme');
        const next = cur === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('sf_theme', next);
        updateThemeUI(next);
    }

    function updateThemeUI(theme) {
        const icon  = document.getElementById('themeIcon');
        const label = document.getElementById('themeLabel');
        if (!icon || !label) return;
        icon.className    = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        label.textContent = theme === 'dark' ? 'Light' : 'Dark';
    }

    /* ---- Theme ---- */
    /* ---- Navbar scroll ---- */
    window.addEventListener('scroll', () => {
        document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 40);
    });

    /* ---- Video.js player init ---- */
    let vjsPlayer = null;

    function getPlayer() {
        if (!vjsPlayer) {
            vjsPlayer = videojs('stream-player', {
                fluid: true,
                responsive: true,
                playbackRates: [0.5, 1, 1.25, 1.5, 2],
                controls: true,
                preload: 'none'
            });

            // Disable right-click
            vjsPlayer.el().addEventListener('contextmenu', e => e.preventDefault());

            // Loading shimmer
            vjsPlayer.on('waiting', () => document.getElementById('vjsWrapper').classList.add('loading'));
            vjsPlayer.on('canplay', () => document.getElementById('vjsWrapper').classList.remove('loading'));

            // Show real duration once metadata loads
            vjsPlayer.on('loadedmetadata', () => {
                const dur = vjsPlayer.duration();
                if (dur) {
                    const m = Math.floor(dur / 60);
                    const s = Math.floor(dur % 60);
                    document.getElementById('playerDuration').textContent = m + ':' + String(s).padStart(2, '0');
                }
            });
        }
        return vjsPlayer;
    }

    /* ---- Open player ---- */
    function openPlayer(url, title, views, duration) {
        // Populate meta
        document.getElementById('playerTitle').textContent   = title || 'Untitled';
        document.getElementById('playerViews').textContent   = views || '—';
        document.getElementById('playerDuration').textContent = duration || '--:--';
        document.title = (title || 'StreamFlow') + ' – StreamFlow';

        // Load video
        const p = getPlayer();
        p.src({ src: url, type: 'video/mp4' });
        p.load();

        // Slide player view up
        document.getElementById('playerView').classList.add('open');
        document.getElementById('playerView').scrollTop = 0;
        document.getElementById('navBackBtn').classList.add('visible');
        document.getElementById('heroSection').style.opacity = '0';
        document.body.style.overflow = 'hidden'; // prevent grid scrolling behind

        // Autoplay
        setTimeout(() => { p.play().catch(() => {}); }, 300);
    }

    /* ---- Close player ---- */
    function closePlayer() {
        const p = getPlayer();
        p.pause();
        p.src('');

        document.getElementById('playerView').classList.remove('open');
        document.getElementById('navBackBtn').classList.remove('visible');
        document.getElementById('heroSection').style.opacity = '1';
        document.body.style.overflow = '';
        document.title = 'Premium Streaming – StreamFlow';
    }

    /* Brand click: if player open → close it */
    function handleBrandClick(e) {
        if (document.getElementById('playerView').classList.contains('open')) {
            e.preventDefault();
            closePlayer();
        }
    }

    /* ---- Keyboard shortcuts ---- */
    document.addEventListener('keydown', e => {
        const playerOpen = document.getElementById('playerView').classList.contains('open');
        if (!playerOpen || e.target.tagName.toLowerCase() === 'input') return;
        const p = getPlayer();
        switch (e.code) {
            case 'Space':      e.preventDefault(); p.paused() ? p.play() : p.pause(); break;
            case 'ArrowLeft':  e.preventDefault(); p.currentTime(Math.max(0, p.currentTime() - 10)); break;
            case 'ArrowRight': e.preventDefault(); p.currentTime(p.currentTime() + 10); break;
            case 'ArrowUp':    e.preventDefault(); p.volume(Math.min(1, p.volume() + 0.1)); break;
            case 'ArrowDown':  e.preventDefault(); p.volume(Math.max(0, p.volume() - 0.1)); break;
            case 'KeyF':       e.preventDefault(); p.isFullscreen() ? p.exitFullscreen() : p.requestFullscreen(); break;
            case 'Escape':     closePlayer(); break;
        }
    });
</script>
</body>
</html>
