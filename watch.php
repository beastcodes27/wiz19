<?php
if (file_exists('config.php')) {
    require_once 'config.php';
}
require_once 'includes/db.php';

// Safe fallback for SITE_URL
if (!defined('SITE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('SITE_URL', $protocol . $host . (defined('BASE_URL') ? BASE_URL : ''));
}

$video_id = (int)($_GET['id'] ?? 0);
if (!$video_id) {
    header("Location: " . SITE_URL);
    exit;
}

// Always load video data so we can show the paywall with context
try {
    $stmt = $pdo->prepare("
        SELECT v.*, u.id as owner_id, u.gateway_api_key, u.monetization_mode
        FROM videos v
        JOIN users u ON v.user_id = u.id
        WHERE v.id = ? AND v.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$video_id]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { $video = null; }

if (!$video) {
    header("Location: " . SITE_URL);
    exit;
}

$has_access      = false;
$access_expires_ts = null;

// Rule 1: Admin bypass
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $has_access = true;
    $access_expires_ts = time() + 86400;
}

// Rule 2: Per-video 24h cookie — the primary mechanism for Pay-Per-Video
if (!$has_access) {
    $cookie_tx = $_COOKIE['sf_video_' . $video_id] ?? '';
    if (!empty($cookie_tx)) {
        try {
            $stmt = $pdo->prepare("
                SELECT expires_at FROM video_access
                WHERE video_id = ? AND reference = ? AND status = 'completed' AND expires_at > NOW()
                LIMIT 1
            ");
            $stmt->execute([$video_id, $cookie_tx]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $has_access = true;
                $access_expires_ts = strtotime($row['expires_at']);
            }
        } catch (Exception $e) {}
    }
}

// Rule 3: INTENTIONALLY REMOVED — IP-based access is too broad for watch.php.
// Anyone on the same network as the payer would pass this check just by visiting the URL.
// watch.php enforces strictly cookie-based access only (Rules 2 & 4 above).
// IP-based streaming_access remains in streaming.php for channel-mode use only.

// Rule 4: sf_pass_ cookie (channel access cookie also covers videos from that creator)
if (!$has_access) {
    $pass_cookie = $_COOKIE['sf_pass_' . $video['owner_id']] ?? '';
    if (!empty($pass_cookie)) {
        try {
            $stmt = $pdo->prepare("
                SELECT id FROM transactions
                WHERE reference_id = ? AND status = 'completed'
                  AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                LIMIT 1
            ");
            $stmt->execute([$pass_cookie]);
            if ($stmt->fetch()) {
                $has_access = true;
                // Expiry is within 24h of transaction — use a safe conservative estimate
                $access_expires_ts = time() + 3600; // at least 1h remaining
            }
        } catch (Exception $e) {}
    }
}

// Resolve the api key for embedded payment
$admin_api_key = $video['gateway_api_key'] ?? '';
if (empty($admin_api_key)) {
    try {
        $stmt = $pdo->query("SELECT gateway_api_key FROM users WHERE role = 'admin' LIMIT 1");
        $admin_api_key = $stmt->fetchColumn() ?: '';
    } catch (Exception $e) {}
}

function fmtViews(int $n): string {
    if ($n >= 1_000_000) return round($n / 1_000_000, 1) . 'M';
    if ($n >= 1_000)     return round($n / 1_000, 1) . 'K';
    return (string)$n;
}

$totalViews = (int)($video['views'] ?? 0) + (int)($video['fake_views'] ?? 0);
$viewsStr   = fmtViews($totalViews);
$thumbUrl   = htmlspecialchars($video['thumbnail_url'] ?: 'assets/images/placeholder.jpg');
$videoUrl   = $has_access ? htmlspecialchars($video['video_url'] ?? '') : '';
$title      = htmlspecialchars($video['title'] ?? 'Untitled');
$duration   = !empty($video['duration']) ? htmlspecialchars($video['duration']) : '--:--';
$price      = number_format((float)($video['price'] ?? 1000));
$creator_id = (int)$video['owner_id'];
$expires_js = $has_access && $access_expires_ts ? (int)$access_expires_ts * 1000 : 0;
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> – Premium Watch</title>
    <meta name="description" content="Watch <?= $title ?> – Pay once, watch for 24 hours.">
    <meta name="theme-color" content="#6366f1">
    <meta property="og:title" content="<?= $title ?> – Premium Watch">
    <meta property="og:image" content="<?= $thumbUrl ?>">

    <link href="https://vjs.zencdn.net/8.0.4/video-js.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ─── TOKENS ─────────────────────────────────────────── */
        :root {
            --p:       #6366f1;
            --p-h:     #4f46e5;
            --acc:     #ec4899;
            --bg:      #06070d;
            --bg-card: #111219;
            --bg-wrap: #181a27;
            --text:    #f1f5f9;
            --sub:     #94a3b8;
            --dim:     #475569;
            --border:  rgba(255,255,255,0.07);
            --bh:      rgba(99,102,241,0.5);
            --sh-xl:   0 24px 60px rgba(0,0,0,0.7);
            --red:     #ef4444;
            --green:   #10b981;
        }
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Poppins',sans-serif;
            background:var(--bg);
            color:var(--text);
            min-height:100vh;
            display:flex;
            flex-direction:column;
            align-items:center;
        }
        body::before {
            content:'';
            position:fixed; inset:0;
            background:
                radial-gradient(circle at 12% 20%, rgba(99,102,241,.08) 0%, transparent 50%),
                radial-gradient(circle at 88% 80%, rgba(236,72,153,.08) 0%, transparent 50%);
            pointer-events:none; z-index:0;
        }

        /* ─── NAV ─────────────────────────────────────────────── */
        .topbar {
            width:100%; position:sticky; top:0; z-index:100;
            background:rgba(6,7,13,0.92);
            backdrop-filter:blur(20px) saturate(180%);
            border-bottom:1px solid var(--border);
            display:flex; align-items:center; justify-content:space-between;
            padding:.9rem 2rem;
        }
        .brand {
            display:flex; align-items:center; gap:.6rem;
            text-decoration:none;
        }
        .brand-icon {
            width:36px; height:36px;
            background:linear-gradient(135deg,var(--p),var(--acc));
            border-radius:9px;
            display:flex; align-items:center; justify-content:center;
            color:#fff; font-size:.9rem;
            box-shadow:0 4px 12px rgba(99,102,241,.4);
        }
        .brand-name {
            font-size:1.3rem; font-weight:800;
            background:linear-gradient(135deg,var(--p),var(--acc));
            -webkit-background-clip:text; -webkit-text-fill-color:transparent;
            background-clip:text; letter-spacing:-.5px;
        }
        .topbar-right { display:flex; align-items:center; gap:.8rem; }

        /* share button */
        .btn-share {
            display:flex; align-items:center; gap:6px;
            background:var(--bg-card); border:1px solid var(--border);
            color:var(--sub); padding:7px 16px; border-radius:25px;
            font-size:.82rem; font-weight:600; cursor:pointer;
            font-family:'Poppins',sans-serif; transition:all .25s;
        }
        .btn-share:hover { background:var(--p); color:#fff; border-color:var(--p); }

        /* ─── MAIN CONTAINER ──────────────────────────────────── */
        .player-wrap {
            width:100%; max-width:1100px;
            padding:2rem 2rem 5rem;
            position:relative; z-index:1;
        }

        /* ─── VIDEO CONTAINER ─────────────────────────────────── */
        .video-container {
            position:relative;
            background:var(--bg-wrap);
            border-radius:18px;
            border:1px solid var(--border);
            box-shadow:var(--sh-xl);
            overflow:hidden;
            margin-bottom:1.5rem;
        }

        /* Loading shimmer */
        .video-container.shimmer {
            background:linear-gradient(90deg,var(--bg-card) 25%,var(--bg-wrap) 50%,var(--bg-card) 75%);
            background-size:200% 100%;
            animation:shimmer 1.5s infinite;
        }
        @keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }

        /* Video.js theming */
        .video-js {
            width:100% !important; border-radius:0;
        }
        .video-js .vjs-big-play-button {
            background:linear-gradient(135deg,var(--p),var(--acc)) !important;
            border:none !important; border-radius:50% !important;
            width:84px !important; height:84px !important;
            font-size:2.2rem !important;
            top:50% !important; left:50% !important;
            transform:translate(-50%,-50%) !important; margin:0 !important;
            transition:transform .3s, box-shadow .3s !important;
        }
        .video-js .vjs-big-play-button:hover {
            transform:translate(-50%,-50%) scale(1.1) !important;
            box-shadow:0 10px 30px rgba(99,102,241,.6) !important;
        }
        .video-js .vjs-control-bar {
            background:rgba(0,0,0,.85) !important;
            backdrop-filter:blur(10px);
        }
        .video-js .vjs-play-progress { background:var(--p) !important; }
        .video-js .vjs-volume-level  { background:var(--p) !important; }
        .video-js .vjs-progress-holder { background:rgba(255,255,255,.2) !important; border-radius:4px !important; }

        /* ─── LOCKED OVERLAY (paywall over thumbnail) ─────────── */
        .locked-overlay {
            position:relative;
            aspect-ratio:16/9;
            overflow:hidden;
            background:#000;
            border-radius:0;
        }
        .locked-thumb {
            width:100%; height:100%;
            object-fit:cover;
            filter:blur(14px) brightness(.35);
            transform:scale(1.08);
        }
        .locked-center {
            position:absolute; inset:0;
            display:flex; flex-direction:column;
            align-items:center; justify-content:center;
            text-align:center; padding:2rem;
            gap:.8rem;
        }
        .lock-icon-ring {
            width:90px; height:90px;
            background:linear-gradient(135deg,var(--p),var(--acc));
            border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            font-size:2.2rem; color:#fff;
            box-shadow:0 8px 32px rgba(99,102,241,.5);
            animation:pulse-ring 2s ease-in-out infinite;
        }
        @keyframes pulse-ring {
            0%,100%{ box-shadow:0 8px 32px rgba(99,102,241,.5); }
            50%    { box-shadow:0 8px 48px rgba(99,102,241,.85); }
        }
        .locked-center h3 { font-size:1.6rem; font-weight:700; }
        .locked-center p  { color:var(--sub); font-size:.95rem; max-width:380px; }
        .btn-unlock {
            background:linear-gradient(135deg,var(--p),var(--acc));
            color:#fff; border:none; padding:14px 36px; border-radius:50px;
            font-size:1rem; font-weight:700; cursor:pointer;
            font-family:'Poppins',sans-serif; letter-spacing:.5px;
            box-shadow:0 8px 24px rgba(99,102,241,.45);
            transition:transform .25s, opacity .25s;
            display:flex; align-items:center; gap:8px;
        }
        .btn-unlock:hover { transform:translateY(-2px); opacity:.92; }

        /* ─── ACCESS TIMER BAR ────────────────────────────────── */
        .access-bar {
            display:flex; align-items:center; justify-content:space-between;
            background:rgba(16,185,129,.08);
            border:1px solid rgba(16,185,129,.22);
            border-radius:12px; padding:.8rem 1.2rem;
            margin-bottom:1.2rem; flex-wrap:wrap; gap:.5rem;
        }
        .access-bar-left { display:flex; align-items:center; gap:.6rem; font-size:.88rem; }
        .access-bar-left i { color:var(--green); }
        .access-bar-left strong { color:var(--green); }
        #accessCountdown {
            font-size:1rem; font-weight:700;
            font-variant-numeric:tabular-nums;
            color:var(--green);
            background:rgba(16,185,129,.12);
            padding:4px 14px; border-radius:20px;
            border:1px solid rgba(16,185,129,.25);
        }
        #accessCountdown.warning { color:#f59e0b; background:rgba(245,158,11,.1); border-color:rgba(245,158,11,.25); }
        #accessCountdown.expired { color:var(--red); background:rgba(239,68,68,.1); border-color:rgba(239,68,68,.25); }

        /* ─── TITLE CARD ──────────────────────────────────────── */
        .title-card {
            background:var(--bg-card);
            border-radius:16px; padding:1.8rem 2rem;
            border:1px solid var(--border);
            box-shadow:0 4px 24px rgba(0,0,0,.4);
            position:relative; overflow:hidden;
            margin-bottom:1.2rem;
        }
        .title-card::before {
            content:'';
            position:absolute; top:0; left:0;
            width:5px; height:100%;
            background:linear-gradient(180deg,var(--p),var(--acc));
        }
        .title-card h1 {
            font-size:1.55rem; font-weight:700;
            padding-left:1rem; margin-bottom:.8rem; line-height:1.35;
        }
        .title-meta {
            display:flex; align-items:center; gap:1.2rem;
            padding-left:1rem; flex-wrap:wrap; font-size:.88rem; color:var(--sub);
        }
        .title-meta span { display:flex; align-items:center; gap:5px; }
        .title-meta i { color:var(--p); }

        /* badges */
        .badge-access {
            display:inline-flex; align-items:center; gap:5px;
            background:rgba(16,185,129,.15);
            border:1px solid rgba(16,185,129,.3);
            color:var(--green); padding:4px 12px; border-radius:20px;
            font-size:.75rem; font-weight:700;
        }
        .badge-lock {
            display:inline-flex; align-items:center; gap:5px;
            background:rgba(239,68,68,.12);
            border:1px solid rgba(239,68,68,.25);
            color:var(--red); padding:4px 12px; border-radius:20px;
            font-size:.75rem; font-weight:700;
        }

        /* shortcuts */
        .shortcuts-bar {
            margin-top:1rem; padding:.8rem 1.2rem;
            background:rgba(99,102,241,.05);
            border:1px solid rgba(99,102,241,.1);
            border-radius:10px; font-size:.76rem; color:var(--dim);
            display:flex; flex-wrap:wrap; gap:.4rem 1.2rem;
        }
        kbd {
            background:var(--bg-card); border:1px solid var(--border);
            border-radius:5px; padding:2px 7px; font-size:.7rem;
            font-family:'Poppins',sans-serif; color:var(--text);
        }

        /* ─── PAYWALL MODAL ───────────────────────────────────── */
        .modal-overlay {
            position:fixed; inset:0;
            background:rgba(6,7,13,.96);
            backdrop-filter:blur(16px) saturate(160%);
            z-index:3000;
            display:flex; align-items:center; justify-content:center;
            padding:20px;
        }
        .modal-overlay.hidden { display:none; }
        .pay-card {
            background:var(--bg-card);
            border-radius:24px; padding:2.2rem 2rem;
            max-width:430px; width:100%;
            border:1px solid var(--border);
            box-shadow:0 30px 70px rgba(0,0,0,.65);
            text-align:center;
            animation:fadeUp .4s ease;
        }
        @keyframes fadeUp { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }
        .pay-thumb-preview {
            width:100%; aspect-ratio:16/9;
            object-fit:cover; border-radius:14px;
            margin-bottom:1.4rem;
            filter:brightness(.6) saturate(.8);
        }
        .pay-icon {
            width:64px; height:64px;
            background:linear-gradient(135deg,var(--p),var(--acc));
            border-radius:50%; display:flex; align-items:center; justify-content:center;
            margin:0 auto 1rem; font-size:1.6rem; color:#fff;
            box-shadow:0 8px 24px rgba(99,102,241,.4);
        }
        .pay-card h2 { font-size:1.4rem; font-weight:700; margin-bottom:.3rem; }
        .pay-card .pay-video-title {
            color:var(--sub); font-size:.88rem; margin-bottom:1rem;
            display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
        }
        .price-row { margin-bottom:1.1rem; }
        .price-lbl { font-size:.75rem; color:var(--dim); }
        .price-amt {
            font-size:2.6rem; font-weight:800; letter-spacing:-1.5px;
            background:linear-gradient(135deg,var(--p),var(--acc));
            -webkit-background-clip:text; -webkit-text-fill-color:transparent;
            background-clip:text;
        }
        .price-note { font-size:.75rem; color:var(--dim); margin-top:2px; }

        .inp-grp { text-align:left; margin-bottom:1rem; }
        .inp-grp label { display:block; font-size:.8rem; font-weight:600; color:var(--sub); margin-bottom:6px; }
        .inp-grp input {
            width:100%; background:#000; border:1px solid var(--border);
            padding:13px 14px; color:var(--text);
            border-radius:12px; font-size:1rem; outline:none;
            font-family:'Poppins',sans-serif; transition:border-color .2s;
        }
        .inp-grp input:focus { border-color:var(--p); }

        .steps-box {
            background:rgba(99,102,241,.07);
            border:1px solid rgba(99,102,241,.13);
            border-radius:12px; padding:12px 14px;
            font-size:.75rem; color:var(--sub);
            text-align:left; margin-bottom:1.1rem;
        }
        .step-row { display:flex; gap:9px; margin-bottom:6px; align-items:flex-start; }
        .step-row:last-child { margin-bottom:0; }
        .step-num {
            width:18px; height:18px; flex-shrink:0;
            background:linear-gradient(135deg,var(--p),var(--acc));
            color:#fff; border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            font-size:.6rem; font-weight:800; margin-top:2px;
        }

        .btn-pay-now {
            width:100%;
            background:linear-gradient(135deg,var(--p),var(--acc));
            color:#fff; padding:14px; border-radius:12px; border:none;
            font-size:1rem; font-weight:700; cursor:pointer;
            font-family:'Poppins',sans-serif; letter-spacing:.5px;
            transition:opacity .2s, transform .2s;
        }
        .btn-pay-now:disabled { opacity:.6; pointer-events:none; }
        .btn-pay-now:not(:disabled):hover { opacity:.9; transform:translateY(-1px); }
        #payStatus { margin-top:10px; font-size:.83rem; color:var(--sub); min-height:1.2em; }

        .modal-close-btn {
            position:absolute; top:16px; right:16px;
            background:var(--bg-wrap); border:1px solid var(--border);
            color:var(--sub); width:36px; height:36px;
            border-radius:50%; display:flex; align-items:center; justify-content:center;
            cursor:pointer; font-size:1rem; transition:all .2s;
        }
        .modal-close-btn:hover { background:var(--red); color:#fff; border-color:var(--red); }

        /* ─── RESPONSIVE ──────────────────────────────────────── */
        @media (max-width:768px) {
            .player-wrap { padding:1.2rem 1rem 4rem; }
            .topbar { padding:.7rem 1rem; }
            .title-card { padding:1.4rem 1.2rem; }
            .title-card h1 { font-size:1.2rem; }
            .shortcuts-bar { display:none; }
            .locked-center h3 { font-size:1.2rem; }
        }
    </style>
</head>
<body>

<!-- ─── TOPBAR ─── -->
<nav class="topbar">
    <a href="<?= SITE_URL ?>" class="brand">
        <div class="brand-icon"><i class="fas fa-play"></i></div>
        <span class="brand-name">StreamFlow</span>
    </a>
    <div class="topbar-right">
        <button class="btn-share" id="shareBtn" onclick="shareLink()">
            <i class="fas fa-share-nodes"></i> Share
        </button>
    </div>
</nav>

<!-- ─── MAIN ─── -->
<div class="player-wrap">

<?php if ($has_access): ?>
    <!-- ─── ACCESS TIMER BAR ─── -->
    <div class="access-bar">
        <div class="access-bar-left">
            <i class="fas fa-check-circle"></i>
            <strong>Access Active</strong>
            <span style="color:var(--sub);font-size:.82rem;">– Your 24-hour pass expires in:</span>
        </div>
        <div id="accessCountdown">00:00:00</div>
    </div>

    <!-- ─── VIDEO PLAYER ─── -->
    <div class="video-container" id="vjsContainer">
        <video id="watch-player"
               class="video-js vjs-default-skin vjs-big-play-centered"
               controls preload="auto"
               poster="<?= $thumbUrl ?>"
               data-setup='{"fluid":true,"responsive":true,"playbackRates":[0.5,1,1.25,1.5,2]}'>
            <source src="<?= $videoUrl ?>" type="video/mp4">
            <p class="vjs-no-js">Please enable JavaScript to watch this video.</p>
        </video>
    </div>
<?php else: ?>
    <!-- ─── LOCKED OVERLAY ─── -->
    <div class="video-container">
        <div class="locked-overlay">
            <img class="locked-thumb" src="<?= $thumbUrl ?>" alt="<?= $title ?>">
            <div class="locked-center">
                <div class="lock-icon-ring"><i class="fas fa-lock"></i></div>
                <h3>This Video is Locked</h3>
                <p>Pay <strong>TSH <?= $price ?></strong> to unlock 24-hour access to this video.</p>
                <button class="btn-unlock" onclick="showPaywall()">
                    <i class="fas fa-unlock"></i> Unlock Now – TSH <?= $price ?>
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

    <!-- ─── TITLE CARD ─── -->
    <div class="title-card">
        <h1><?= $title ?></h1>
        <div class="title-meta">
            <span><i class="fas fa-eye"></i> <?= $viewsStr ?> views</span>
            <span><i class="fas fa-clock"></i> <?= $duration ?></span>
            <span><i class="fas fa-shield-alt"></i> Premium Quality</span>
            <?php if ($has_access): ?>
            <span class="badge-access"><i class="fas fa-check-circle"></i> 24-Hour Access</span>
            <?php else: ?>
            <span class="badge-lock"><i class="fas fa-lock"></i> Locked – TSH <?= $price ?></span>
            <?php endif; ?>
        </div>
        <?php if ($has_access): ?>
        <div class="shortcuts-bar">
            <span><kbd>Space</kbd> Play/Pause</span>
            <span><kbd>←</kbd> -10s</span>
            <span><kbd>→</kbd> +10s</span>
            <span><kbd>↑</kbd> Vol+</span>
            <span><kbd>↓</kbd> Vol−</span>
            <span><kbd>F</kbd> Fullscreen</span>
            <span><kbd>M</kbd> Mute</span>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /player-wrap -->

<?php if (!$has_access): ?>
<!-- ─── PAYWALL MODAL ─── -->
<div class="modal-overlay hidden" id="paywallModal">
    <div class="pay-card" style="position:relative;">
        <button class="modal-close-btn" onclick="hidePaywall()" title="Close">
            <i class="fas fa-times"></i>
        </button>

        <div class="pay-icon"><i class="fas fa-shield-alt"></i></div>
        <h2>Unlock This Video</h2>
        <p class="pay-video-title"><?= $title ?></p>

        <div class="price-row">
            <div class="price-lbl">One-time access fee:</div>
            <div class="price-amt">TSH <?= $price ?></div>
            <div class="price-note"><i class="fas fa-clock" style="margin-right:4px;"></i>24-hour access – watch anytime</div>
        </div>

        <div class="inp-grp">
            <label>Phone Number <span style="font-weight:400;">(M-Pesa / Tigo Pesa / Airtel)</span></label>
            <input type="tel" id="payPhone" placeholder="07xx xxx xxx" pattern="[0-9]*" autocomplete="tel">
        </div>

        <div class="steps-box">
            <div class="step-row"><div class="step-num">1</div><div>Enter your mobile money number above.</div></div>
            <div class="step-row"><div class="step-num">2</div><div>Tap <strong>Pay Now</strong> – a PIN prompt will arrive on your phone.</div></div>
            <div class="step-row"><div class="step-num">3</div><div>Enter your PIN. Video unlocks instantly for 24 hours.</div></div>
        </div>

        <button class="btn-pay-now" id="paywallBtn" onclick="doPayment()">
            <i class="fas fa-lock" style="margin-right:6px;"></i>PAY NOW – TSH <?= $price ?>
        </button>
        <p id="payStatus"></p>
    </div>
</div>
<?php endif; ?>

<!-- ─── SCRIPTS ─── -->
<script src="https://vjs.zencdn.net/8.0.4/video.min.js"></script>
<script>
/* ─── CONFIG ─── */
const VIDEO_ID    = <?= $video_id ?>;
const CREATOR_ID  = <?= $creator_id ?>;
const HAS_ACCESS  = <?= $has_access ? 'true' : 'false' ?>;
const EXPIRES_TS  = <?= $expires_js ?>; // ms timestamp, 0 if no access
const BASE_URL    = '<?= defined('BASE_URL') ? BASE_URL : '' ?>';
const PRICE       = '<?= $price ?>';

/* ─── PLAYER ─── */
<?php if ($has_access): ?>
let vp;
document.addEventListener('DOMContentLoaded', () => {
    vp = videojs('watch-player', {
        fluid: true, responsive: true,
        playbackRates: [0.5, 1, 1.25, 1.5, 2],
        controls: true, preload: 'auto'
    });
    vp.el().addEventListener('contextmenu', e => e.preventDefault());
    vp.on('waiting', () => document.getElementById('vjsContainer').classList.add('shimmer'));
    vp.on('canplay', () => document.getElementById('vjsContainer').classList.remove('shimmer'));
    startCountdown();
});
<?php endif; ?>

/* ─── 24-HOUR COUNTDOWN ─── */
function startCountdown() {
    if (!EXPIRES_TS) return;
    const el = document.getElementById('accessCountdown');
    if (!el) return;
    function tick() {
        const diff = Math.max(0, Math.floor((EXPIRES_TS - Date.now()) / 1000));
        const h = Math.floor(diff / 3600);
        const m = Math.floor((diff % 3600) / 60);
        const s = diff % 60;
        el.textContent = String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
        el.className = diff < 3600 ? (diff < 600 ? 'expired' : 'warning') : '';
        if (diff <= 0) {
            el.textContent = 'EXPIRED';
            el.className = 'expired';
            // Show paywall after expiry
            setTimeout(() => { location.reload(); }, 2000);
            return;
        }
        setTimeout(tick, 1000);
    }
    tick();
}

/* ─── SHARE ─── */
function shareLink() {
    const url = window.location.href;
    if (navigator.share) {
        navigator.share({ title: document.title, url });
    } else if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            const b = document.getElementById('shareBtn');
            const orig = b.innerHTML;
            b.innerHTML = '<i class="fas fa-check"></i> Copied!';
            setTimeout(() => { b.innerHTML = orig; }, 2000);
        });
    } else {
        prompt('Copy this link:', url);
    }
}

/* ─── PAYWALL ─── */
function showPaywall() {
    const m = document.getElementById('paywallModal');
    if (m) m.classList.remove('hidden');
}
function hidePaywall() {
    const m = document.getElementById('paywallModal');
    if (m) m.classList.add('hidden');
}

/* ─── PAYMENT FLOW ─── */
let pollInterval;
let payInProgress = false;  /* debounce guard */

/* ── On load: resume any pending payment from localStorage (tab closed mid-payment) ── */
window.addEventListener('DOMContentLoaded', () => {
    if (HAS_ACCESS) return;
    const storedTran = localStorage.getItem('watch_tran_' + VIDEO_ID);
    const storedTs   = parseInt(localStorage.getItem('watch_tran_ts_' + VIDEO_ID) || '0');
    if (storedTran && (Date.now() - storedTs) < 10 * 60 * 1000) {
        showPaywall();
        const status = document.getElementById('payStatus');
        if (status) status.textContent = '⏳ Resuming your previous payment check...';
        const btn = document.getElementById('paywallBtn');
        if (btn) { btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i>CHECKING PAYMENT...'; btn.disabled = true; }
        setTimeout(() => checkOnce(storedTran), 1500);
    }
});

async function doPayment() {
    if (payInProgress) return;

    const phoneEl = document.getElementById('payPhone');
    const btn     = document.getElementById('paywallBtn');
    const status  = document.getElementById('payStatus');

    /* ── normalize: strip spaces, handle +255 prefix ── */
    const rawPhone = (phoneEl ? phoneEl.value : '').trim()
                      .replace(/\s+/g, '').replace(/^\+255/, '0');

    /* ── Tanzania validation: 07xx or 06xx, exactly 10 digits ── */
    if (!/^0[67]\d{8}$/.test(rawPhone)) {
        if (status) status.textContent = '❌ Enter a valid number: 07xx or 06xx (10 digits)';
        return;
    }

    payInProgress = true;
    btn.disabled   = true;
    btn.innerHTML  = '<i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i>INITIATING...';
    status.textContent = '';

    try {
        const res  = await fetch(BASE_URL + '/api/process_payment.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ video_id: VIDEO_ID, phone: rawPhone })
        });
        const data = await res.json();

        if (data.status === 'success') {
            const tranID = data.tranID;

            /* ── persist so tab close / page reload can recover ── */
            localStorage.setItem('watch_tran_' + VIDEO_ID, tranID);
            localStorage.setItem('watch_tran_ts_' + VIDEO_ID, Date.now());

            btn.innerHTML  = '<i class="fas fa-mobile-alt" style="margin-right:6px;"></i>CHECK YOUR PHONE FOR PIN...';
            status.textContent = 'Please enter your Mobile Money PIN when prompted.';
            startPoll(tranID);
        } else {
            if (status) status.textContent = '❌ ' + (data.message || 'Payment initiation failed.');
            resetPayBtn();
        }
    } catch (e) {
        if (status) status.textContent = '❌ Network error. Please try again.';
        resetPayBtn();
    }
}

function resetPayBtn() {
    payInProgress = false;
    const btn = document.getElementById('paywallBtn');
    if (btn) {
        btn.innerHTML = '<i class="fas fa-lock" style="margin-right:6px;"></i>PAY NOW – TSH ' + PRICE;
        btn.disabled  = false;
        btn.onclick   = doPayment;
        btn.style.background = '';
    }
}

function startPoll(tranID) {
    let attempts = 0;
    const MAX_ATTEMPTS = 36; /* 36 × 5s = 3 minutes */

    pollInterval = setInterval(async () => {
        attempts++;
        if (attempts > MAX_ATTEMPTS) {
            clearInterval(pollInterval);
            showRecoveryBtn(tranID);
            return;
        }
        try {
            const r = await fetch(BASE_URL + '/api/check_payment.php?tranid=' + encodeURIComponent(tranID) + '&_=' + Date.now());
            const d = await r.json();
            const s = (d.payment_status || d.status || '').toUpperCase();

            if (s === 'COMPLETED' || s === 'SUCCESS') {
                clearInterval(pollInterval);
                onPaymentConfirmed(tranID);
            } else if (['FAILED','CANCELLED','REJECTED','EXPIRED'].includes(s)) {
                clearInterval(pollInterval);
                localStorage.removeItem('watch_tran_' + VIDEO_ID);
                localStorage.removeItem('watch_tran_ts_' + VIDEO_ID);
                const status = document.getElementById('payStatus');
                if (status) status.textContent = '❌ Payment failed or was cancelled.';
                resetPayBtn();
            }
        } catch (e) {
            /* silently ignore transient network errors */
        }
    }, 5000);
}

/* ── shown when 3-min poll window expires ── */
function showRecoveryBtn(tranID) {
    payInProgress = false;
    const btn    = document.getElementById('paywallBtn');
    const status = document.getElementById('payStatus');
    if (btn) {
        btn.innerHTML = '<i class="fas fa-search" style="margin-right:6px;"></i>CHECK PAYMENT STATUS';
        btn.disabled  = false;
        btn.style.background = 'linear-gradient(135deg,#d97706,#b45309)';
        btn.onclick   = () => checkOnce(tranID);
    }
    if (status) status.textContent = '⏳ Timed out waiting. Tap above to check if your payment went through.';
}

/* ── single manual status check — recovery button + localStorage resume ── */
async function checkOnce(tranID) {
    const btn    = document.getElementById('paywallBtn');
    const status = document.getElementById('payStatus');
    if (btn) { btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i>CHECKING...'; btn.disabled = true; }
    if (status) status.textContent = 'Checking payment status...';

    try {
        const r = await fetch(BASE_URL + '/api/check_payment.php?tranid=' + encodeURIComponent(tranID) + '&_=' + Date.now());
        const d = await r.json();
        const s = (d.payment_status || d.status || '').toUpperCase();

        if (s === 'COMPLETED' || s === 'SUCCESS') {
            onPaymentConfirmed(tranID);
        } else if (['FAILED','CANCELLED','REJECTED','EXPIRED'].includes(s)) {
            localStorage.removeItem('watch_tran_' + VIDEO_ID);
            localStorage.removeItem('watch_tran_ts_' + VIDEO_ID);
            if (status) status.textContent = '❌ Status: ' + s + '. If you were charged, contact support.';
            resetPayBtn();
        } else {
            if (status) status.textContent = '⏳ Still pending. Wait a moment and check again.';
            if (btn) {
                btn.innerHTML = '<i class="fas fa-search" style="margin-right:6px;"></i>CHECK PAYMENT STATUS';
                btn.disabled  = false;
                btn.onclick   = () => checkOnce(tranID);
            }
        }
    } catch (e) {
        if (status) status.textContent = '❌ Network error. Please try checking again.';
        if (btn) { btn.innerHTML = '<i class="fas fa-search" style="margin-right:6px;"></i>CHECK PAYMENT STATUS'; btn.disabled = false; }
    }
}

/* ── called when payment confirmed COMPLETED ── */
function onPaymentConfirmed(tranID) {
    localStorage.removeItem('watch_tran_' + VIDEO_ID);
    localStorage.removeItem('watch_tran_ts_' + VIDEO_ID);

    /* ✅ Set per-video 24-hour cookie → watch.php grants access on reload */
    const expiresDate = new Date(Date.now() + 24 * 60 * 60 * 1000).toUTCString();
    document.cookie = 'sf_video_' + VIDEO_ID + '=' + encodeURIComponent(tranID) + '; expires=' + expiresDate + '; path=/; SameSite=Lax';
    /* Also set channel cookie for streaming.php */
    document.cookie = 'sf_pass_' + CREATOR_ID + '=' + encodeURIComponent(tranID) + '; expires=' + expiresDate + '; path=/; SameSite=Lax';

    const btn    = document.getElementById('paywallBtn');
    const status = document.getElementById('payStatus');
    if (btn) {
        btn.style.background = '#10b981';
        btn.innerHTML = '<i class="fas fa-check-circle" style="margin-right:6px;"></i>PAYMENT CONFIRMED!';
    }
    if (status) status.textContent = '✅ Payment verified! Unlocking your video...';

    setTimeout(() => { location.reload(); }, 1500);
}

/* ─── KEYBOARD SHORTCUTS (player only) ─── */
<?php if ($has_access): ?>
document.addEventListener('keydown', e => {
    if (!vp || e.target.tagName.toLowerCase() === 'input') return;
    switch (e.code) {
        case 'Space':      e.preventDefault(); vp.paused() ? vp.play() : vp.pause(); break;
        case 'ArrowLeft':  e.preventDefault(); vp.currentTime(Math.max(0, vp.currentTime() - 10)); break;
        case 'ArrowRight': e.preventDefault(); vp.currentTime(vp.currentTime() + 10); break;
        case 'ArrowUp':    e.preventDefault(); vp.volume(Math.min(1, vp.volume() + .1)); break;
        case 'ArrowDown':  e.preventDefault(); vp.volume(Math.max(0, vp.volume() - .1)); break;
        case 'KeyF':       e.preventDefault(); vp.isFullscreen() ? vp.exitFullscreen() : vp.requestFullscreen(); break;
        case 'KeyM':       e.preventDefault(); vp.muted(!vp.muted()); break;
    }
});
<?php endif; ?>
</script>
</body>
</html>
