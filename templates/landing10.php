<?php
// Fetch customized landing page settings for this domain owner
$landing_settings = null;
if (isset($domain_owner['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM user_landing_settings WHERE user_id = ? LIMIT 1");
        $stmt->execute([$domain_owner['user_id']]);
        $landing_settings = $stmt->fetch();
    } catch (PDOException $e) {
        // Fallback to default settings
    }
}

// Fallback values if no settings are customized yet
if (!$landing_settings) {
    $landing_settings = [
        'site_name'         => $platform_name,
        'cta_text'          => 'LIPIA TZS 1,000 KUPATA ACCESS',
        'primary_color'     => '#ff6b35',
        'secondary_color'   => '#d4af37',
        'bg_color'          => '#fafafa',
        'hero_title'        => 'Connection Mpya',
        'hero_description'  => 'Tazama preview bure, lipia kuendelea',
        'hero_image'        => 'assets/defaults/landing-bg.jpg',
        'favicon'           => null,
    ];
}

// Ensure required fields are populated
if (empty($landing_settings['site_name']))        $landing_settings['site_name']        = $platform_name;
if (empty($landing_settings['hero_title']))       $landing_settings['hero_title']       = 'Connection Mpya';
if (empty($landing_settings['cta_text']))         $landing_settings['cta_text']         = 'LIPIA TZS 1,000 KUPATA ACCESS';
if (empty($landing_settings['primary_color']))    $landing_settings['primary_color']    = '#ff6b35';
if (empty($landing_settings['secondary_color']))  $landing_settings['secondary_color']  = '#d4af37';
if (empty($landing_settings['bg_color']))         $landing_settings['bg_color']         = '#fafafa';
if (empty($landing_settings['hero_description'])) $landing_settings['hero_description'] = 'Tazama preview bure, lipia kuendelea';
if (empty($landing_settings['hero_image']))       $landing_settings['hero_image']       = 'assets/defaults/landing-bg.jpg';

// Resolve hero background image URL
$hero_bg_image = $landing_settings['hero_image'];
if (
    strpos($hero_bg_image, 'http://') !== 0 &&
    strpos($hero_bg_image, 'https://') !== 0 &&
    strpos($hero_bg_image, '/') !== 0
) {
    $hero_bg_image = BASE_URL . '/' . $hero_bg_image;
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= htmlspecialchars($landing_settings['site_name']) ?> | Premium Streaming</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Crimson+Text:wght@400;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <style>
        :root {
            --primary: #ffffff;
            --secondary: #0a0a0a;
            --accent: <?= htmlspecialchars($landing_settings['primary_color']) ?>;
            --accent-light: <?= htmlspecialchars($landing_settings['primary_color']) ?>cc;
            --accent-dark: <?= htmlspecialchars($landing_settings['primary_color']) ?>99;
            --text: #1a1a1a;
            --text-light: #666666;
            --border: #e8e8e8;
            --surface: #f8f8f8;
            --premium-gold: <?= htmlspecialchars($landing_settings['secondary_color']) ?>;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body {
            background: linear-gradient(135deg, <?= htmlspecialchars($landing_settings['bg_color']) ?> 0%, #f0f0f0 100%);
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text);
            line-height: 1.6;
            overflow-x: hidden;
            min-height: 100vh;
        }
        .nav-bar {
            position: sticky; top: 0; z-index: 1000;
            background: rgba(255,255,255,0.95); backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 20px 28px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 20px rgba(0,0,0,0.03);
        }
        .logo-section { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .logo-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%);
            border-radius: 12px; display: flex; align-items: center; justify-content: center;
            color: white; font-size: 20px; font-weight: 700;
            box-shadow: 0 8px 24px rgba(255,107,53,0.25);
        }
        .logo-text {
            font-family: 'Crimson Text', serif; font-size: 18px; font-weight: 600;
            letter-spacing: 2px; color: var(--secondary); text-transform: uppercase;
        }
        .premium-badge {
            background: linear-gradient(135deg, var(--premium-gold) 0%, #e8c547 100%);
            color: var(--secondary); padding: 8px 16px; border-radius: 50px;
            font-size: 12px; font-weight: 700; letter-spacing: 1px;
            display: flex; align-items: center; gap: 6px;
            box-shadow: 0 8px 24px rgba(212,175,55,0.2); text-transform: uppercase;
            cursor: pointer;
        }
        .feed-container { max-width: 1400px; margin: 0 auto; padding: 40px 28px; }
        .feed-header { margin-bottom: 48px; animation: fadeInDown 0.8s ease-out; }
        .feed-title { font-family: 'Crimson Text', serif; font-size: 42px; font-weight: 600; color: var(--secondary); margin-bottom: 12px; letter-spacing: -1px; }
        .feed-subtitle { color: var(--text-light); font-size: 15px; font-weight: 400; }
        .feed-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 24px; }
        .pro-card {
            background: white; border-radius: 20px; overflow: hidden; position: relative;
            border: 1px solid var(--border); cursor: pointer;
            transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1);
            box-shadow: 0 4px 16px rgba(0,0,0,0.04);
            animation: fadeInUp 0.6s ease-out backwards;
        }
        .pro-card:nth-child(1){animation-delay:.1s}.pro-card:nth-child(2){animation-delay:.2s}
        .pro-card:nth-child(3){animation-delay:.3s}.pro-card:nth-child(n+4){animation-delay:.4s}
        .pro-card:hover { transform: translateY(-12px) scale(1.02); box-shadow: 0 20px 48px rgba(0,0,0,0.12); border-color: var(--accent); }
        .media-container { position: relative; aspect-ratio: 9/14; background: linear-gradient(135deg,#f0f0f0,#e8e8e8); overflow: hidden; }
        .thumb-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease-out; }
        .pro-card:hover .thumb-img { transform: scale(1.08); }
        .card-overlay {
            position: absolute; inset: 0;
            background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.1) 50%, rgba(0,0,0,0.85) 100%);
            padding: 16px; display: flex; flex-direction: column; justify-content: flex-end;
            pointer-events: none;
        }
        .overlay-badge {
            position: absolute; top: 12px; right: 12px;
            background: rgba(255,107,53,0.9); backdrop-filter: blur(10px);
            color: white; padding: 6px 10px; border-radius: 8px;
            font-size: 11px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase;
        }
        .card-title { font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.95); margin-bottom: 8px; }
        .card-stats { display: flex; gap: 14px; font-size: 11px; color: rgba(255,255,255,0.7); font-weight: 500; }
        .card-stats span { display: flex; align-items: center; gap: 5px; }
        
        /* Preview state classes */
        .preview-playing .thumb-img { opacity: 0; }
        .preview-playing .card-preview-video { display: block !important; }
        .preview-playing .preview-overlay { display: flex !important; }
        .preview-playing .overlay-badge { display: none; }

        /* No videos state */
        .empty-state { text-align: center; padding: 80px 24px; color: var(--text-light); grid-column: 1 / -1; }
        .empty-state i { font-size: 64px; margin-bottom: 20px; display: block; opacity: .3; }

        /* PLAYER */
        #player-ui {
            position: fixed; inset: 0; background: var(--secondary); z-index: 2000;
            display: none; animation: fadeIn 0.3s ease-out;
        }
        #fs-video { width: 100%; height: 100%; object-fit: contain; }
        .video-ui-top {
            position: absolute; top: 0; left: 0; right: 0;
            padding: 20px 24px;
            background: linear-gradient(to bottom,rgba(0,0,0,0.6),transparent);
            display: flex; justify-content: space-between; align-items: center; z-index: 2001;
        }
        .close-btn {
            width: 44px; height: 44px; background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px; color: white; font-size: 18px; cursor: pointer;
            display: flex; align-items: center; justify-content: center; transition: all .3s;
        }
        .close-btn:hover { background: rgba(255,255,255,0.2); }
        .status-tag { font-size: 12px; font-weight: 700; color: rgba(255,255,255,0.8); letter-spacing: 1px; text-transform: uppercase; }
        .preview-bar { height: 3px; background: rgba(255,255,255,0.15); position: absolute; bottom: 0; left: 0; right: 0; }
        .preview-progress { height: 100%; background: linear-gradient(90deg,var(--accent),var(--accent-light)); width: 0%; transition: width linear; box-shadow: 0 0 10px rgba(255,107,53,.5); }

        /* MODAL */
        .modal-blur {
            position: fixed; inset: 0; background: rgba(10,10,10,0.85);
            backdrop-filter: blur(8px); z-index: 3000; display: none;
            align-items: center; justify-content: center; padding: 24px;
            animation: fadeIn 0.4s ease-out;
        }
        .modal-content {
            width: 100%; max-width: 420px; background: white; border-radius: 24px;
            padding: 48px 36px; text-align: center; position: relative;
            box-shadow: 0 25px 60px rgba(0,0,0,0.25);
            animation: slideUp 0.5s cubic-bezier(0.34,1.56,0.64,1);
        }
        .close-modal {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 1.2rem;
            color: var(--text-light);
            cursor: pointer;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.2s;
        }
        .close-modal:hover { background: var(--surface); color: var(--text); }

        .icon-box {
            width: 88px; height: 88px;
            background: linear-gradient(135deg,var(--accent),var(--accent-light));
            border-radius: 20px; display: flex; align-items: center; justify-content: center;
            margin: 0 auto 28px; font-size: 40px; color: white;
            box-shadow: 0 12px 32px rgba(255,107,53,0.3);
        }
        .modal-title { font-family: 'Crimson Text', serif; font-size: 28px; font-weight: 600; color: var(--secondary); margin-bottom: 12px; }
        .modal-subtitle { color: var(--text-light); font-size: 14px; margin-bottom: 28px; line-height: 1.6; }
        .price-section { margin: 32px 0; }
        .price-tag { font-size: 48px; font-weight: 900; color: var(--secondary); margin-bottom: 4px; }
        .price-subtitle { font-size: 12px; color: var(--text-light); text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
        
        .input-wrapper { position: relative; }
        .input-wrapper i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: var(--text-light); z-index: 2; }
        .phone-input {
            width: 100%; background: var(--surface); border: 2px solid var(--border);
            padding: 16px 18px 16px 45px; border-radius: 14px; color: var(--text); font-size: 16px;
            text-align: left; margin: 0 0 24px 0; outline: none; font-weight: 500;
            transition: all .3s; font-family: 'Outfit', sans-serif;
        }
        .phone-input:focus { border-color: var(--accent); background: white; box-shadow: 0 0 0 4px rgba(255,107,53,0.1); }
        .phone-input::placeholder { color: #999; }
        
        .btn-primary {
            width: 100%; background: linear-gradient(135deg,var(--accent),var(--accent-dark));
            color: white; padding: 16px 24px; border-radius: 14px; font-weight: 700;
            font-size: 15px; border: none; cursor: pointer; letter-spacing: .5px;
            text-transform: uppercase; transition: all .3s;
            box-shadow: 0 8px 24px rgba(255,107,53,0.3); font-family: 'Outfit', sans-serif;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(255,107,53,0.4); }
        .btn-primary:disabled { opacity: 0.7; cursor: not-allowed; }
        .btn-secondary {
            background: transparent; color: var(--text-light); border: none;
            margin-top: 16px; font-size: 13px; font-weight: 600; cursor: pointer;
            transition: color .3s; text-transform: uppercase; letter-spacing: .5px;
            font-family: 'Outfit', sans-serif;
        }
        .btn-secondary:hover { color: var(--accent); }

        /* Packages List styling to fit this theme */
        #pkgList { text-align: left; margin-bottom: 24px; }
        .pkg-item {
            background: var(--surface);
            border: 2px solid var(--border);
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: 0.25s;
        }
        .pkg-item.active {
            border-color: var(--accent);
            background: rgba(255,107,53,0.05);
        }
        .pkg-item-name { font-weight: 700; font-size: 15px; display: block; color: var(--secondary); }
        .pkg-item-sub  { font-size: 12px; color: var(--text-light); }
        .pkg-item-price { font-size: 16px; font-weight: 800; color: var(--secondary); }
        .pkg-radio {
            width: 20px; height: 20px;
            border: 2px solid #ccc;
            border-radius: 50%;
            position: relative;
            flex-shrink: 0;
            margin-left: 12px;
        }
        .pkg-item.active .pkg-radio { border-color: var(--accent); }
        .pkg-item.active .pkg-radio::after {
            content: ''; position: absolute; inset: 3px; background: var(--accent); border-radius: 50%;
        }

        #message-container { position: fixed; top: 80px; right: 20px; z-index: 4000; display: flex; flex-direction: column; gap: 10px; pointer-events: none; }
        .message { padding: 14px 20px; border-radius: 14px; font-size: 13px; font-weight: 600; max-width: 320px; pointer-events: all; animation: slideInRight .4s cubic-bezier(.34,1.56,.64,1); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
        .message.success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .message.error   { background: #fff1f2; color: #881337; border: 1px solid #fecdd3; }
        .message.info    { background: #eff6ff; color: #1e3a8a; border: 1px solid #bfdbfe; }

        @keyframes fadeInDown { from{opacity:0;transform:translateY(-20px)} to{opacity:1;transform:translateY(0)} }
        @keyframes fadeInUp   { from{opacity:0;transform:translateY(20px)}  to{opacity:1;transform:translateY(0)} }
        @keyframes fadeIn     { from{opacity:0} to{opacity:1} }
        @keyframes slideUp    { from{opacity:0;transform:translateY(30px)} to{opacity:1;transform:translateY(0)} }
        @keyframes slideInRight { from{opacity:0;transform:translateX(20px)} to{opacity:1;transform:translateX(0)} }
        @keyframes spin { to{transform:rotate(360deg)} }

        @media(max-width:768px){.feed-grid{grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px}.feed-title{font-size:28px}.modal-content{padding:36px 24px}.nav-bar{padding:16px 20px}}
        @media(max-width:480px){.feed-grid{grid-template-columns:repeat(2,1fr);gap:12px}.feed-container{padding:24px 16px}.feed-title{font-size:24px}.price-tag{font-size:36px}}
    
        /* Error Message Box */
        .modal-error {
            background: #fff1f2;
            color: #e11d48;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            display: none;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            border: 1px solid #fecdd3;
            text-align: left;
            animation: fadeInDown 0.3s ease-out;
        }
        .modal-error i { font-size: 18px; }

    </style>
</head>
<body>

<div id="message-container"></div>

<nav class="nav-bar">
    <a href="#" class="logo-section">
        <div class="logo-icon"><i class="fas fa-play"></i></div>
        <div class="logo-text"><?= htmlspecialchars($landing_settings['site_name']) ?></div>
    </a>
    <div class="premium-badge" onclick="openPackageModal()">
        <i class="fas fa-crown"></i> Premium
    </div>
</nav>

<div class="feed-container">
    <div class="feed-header">
        <h1 class="feed-title"><?= htmlspecialchars($landing_settings['hero_title']) ?></h1>
        <p class="feed-subtitle"><?= htmlspecialchars($landing_settings['hero_description']) ?></p>
    </div>

    <div class="feed-grid" id="feed">
        <?php if (!empty($user_videos)): ?>
            <?php foreach ($user_videos as $video): ?>
                <div class="pro-card" onclick="openPlayer('<?= htmlspecialchars((string)$video['id']) ?>', '<?= htmlspecialchars((string)$video['slug']) ?>', <?= (int)($video['price'] ?? 1000) ?>, '<?= htmlspecialchars(addslashes($video['title'] ?? '')) ?>', '<?= htmlspecialchars($video['video_url'] ?? '') ?>')">
                    <div class="media-container">
                        <img src="<?= htmlspecialchars($video['thumbnail_url'] ?? 'assets/defaults/landing-bg.jpg') ?>" class="thumb-img" loading="lazy" alt="<?= htmlspecialchars($video['title']) ?>">
                        <div class="overlay-badge"><i class="fas fa-lock-open"></i> Preview</div>
                        <div class="card-overlay">
                            <div class="card-title"><?= htmlspecialchars($video['title']) ?></div>
                            <div class="card-stats">
                                <span><i class="fas fa-eye"></i> <?= number_format($video['views'] ?? 0) ?></span>
                                <span><i class="fas fa-heart"></i> <?= number_format(($video['views'] ?? 0) * 0.2) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-video-slash"></i>
                <p>Hakuna video zilizopakiwa bado.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── Package Modal (CTA Hero) ── -->
<div class="modal-blur" id="packageModal">
    <div class="modal-content">
        <i class="fas fa-times close-modal" onclick="closeModals()"></i>
        <div class="icon-box"><i class="fas fa-crown"></i></div>
        <h2 class="modal-title">Premium Access</h2>
        <p class="modal-subtitle">Angalia video zote bila kikomo.</p>

        <div id="pkgList">
            <?php
            // Fetch packages for this domain owner
            $packages = [];
            if (isset($domain_owner['user_id'])) {
                try {
                    $pkgStmt = $pdo->prepare("SELECT * FROM packages WHERE user_id = ? ORDER BY price ASC");
                    $pkgStmt->execute([$domain_owner['user_id']]);
                    $packages = $pkgStmt->fetchAll();
                } catch (PDOException $e) { /* silent */ }
            }

            if (!empty($packages)):
                foreach ($packages as $i => $pkg):
            ?>
                <div class="pkg-item <?= $i === 0 ? 'active' : '' ?>"
                     onclick="selectPkg('<?= htmlspecialchars((string)$pkg['id']) ?>', this)">
                    <div>
                        <span class="pkg-item-name"><?= htmlspecialchars($pkg['name']) ?></span>
                        <span class="pkg-item-sub"><?= htmlspecialchars($pkg['duration_days'] ?? '—') ?> Days Access</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span class="pkg-item-price"><?= number_format($pkg['price']) ?>/=</span>
                        <div class="pkg-radio"></div>
                    </div>
                </div>
            <?php
                endforeach;
            else:
            ?>
                <div class="pkg-item active" onclick="selectPkg('default', this)">
                    <div>
                        <span class="pkg-item-name">LEO</span>
                        <span class="pkg-item-sub">Masaa 3 Access</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span class="pkg-item-price">1,000/=</span>
                        <div class="pkg-radio"></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div id="pkgError" class="modal-error"></div>
        <div class="input-wrapper">
            <i class="fas fa-phone"></i>
            <input type="tel" id="pkgPhone" class="phone-input" placeholder="Namba ya Simu (07xx...)" inputmode="tel">
        </div>
        <input type="hidden" id="selectedPkgId" value="<?= !empty($packages) ? htmlspecialchars((string)$packages[0]['id']) : 'default' ?>">
        <button class="btn-primary" id="pkgBtn" onclick="processPackagePay()">
            <i class="fas fa-lock-open"></i> LIPA SASA
        </button>
    </div>
</div>

<!-- ── Single Video Payment Modal ── -->
<div class="modal-blur" id="videoModal">
    <div class="modal-content">
        <i class="fas fa-times close-modal" onclick="closeModals()"></i>
        <div class="icon-box" style="background: linear-gradient(135deg,var(--premium-gold),#e8c547); box-shadow: 0 12px 32px rgba(212,175,55,0.3);">
            <i class="fas fa-play-circle"></i>
        </div>
        <div style="display:inline-flex; align-items:center; gap:6px; background:rgba(212,175,55,0.1); color:var(--premium-gold); padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; margin-bottom:10px; text-transform:uppercase;">
            <i class="fas fa-eye"></i> Preview imekwisha
        </div>
        <h2 class="modal-title" id="vTitle" style="font-size: 20px; margin-bottom: 0;"></h2>
        <div class="price-section" style="margin: 20px 0;">
            <div class="price-tag" id="vPrice"></div>
            <div class="price-subtitle">Lipia kuendelea kutazama</div>
        </div>
        <div id="vError" class="modal-error"></div>
        <div class="input-wrapper">
            <i class="fas fa-phone"></i>
            <input type="tel" id="vPhone" class="phone-input" placeholder="Namba ya Simu (07xx...)" inputmode="tel">
        </div>
        <input type="hidden" id="selectedVidId">
        <button class="btn-primary" id="vBtn" onclick="processVideoPay()">
            <i class="fas fa-unlock-alt"></i> UNLOCK VIDEO
        </button>
        <button onclick="closeModals()" style="width:100%;margin-top:12px;padding:14px;background:transparent;border:2px solid var(--border);border-radius:14px;color:var(--text-light);font-size:0.9rem;font-weight:700;cursor:pointer;font-family:'Outfit',sans-serif;display:flex;align-items:center;justify-content:center;gap:8px;">
            <i class="fas fa-times-circle"></i> Ghairi
        </button>
    </div>
</div>

<!-- ── Fullscreen Player Modal ── -->
<div id="player-ui">
    <div class="video-ui-top">
        <div class="status-tag">PREVIEW</div>
        <div class="close-btn" onclick="closePlayer()"><i class="fas fa-times"></i></div>
    </div>
    <video id="fs-video" playsinline preload="auto"></video>
    <div class="preview-bar">
        <div class="preview-progress" id="fs-progress"></div>
    </div>
</div>

<script>

    function showError(type, msg) {
        let errId = '';
        if (type === 'package') errId = 'pkgError';
        else if (type === 'video') errId = 'vError';
        else errId = 'paymentError'; // default for landing1-7

        const errDiv = document.getElementById(errId);
        if (errDiv) {
            errDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + msg;
            errDiv.style.display = 'flex';
        } else {
            alert(msg); // fallback
        }
    }

    function hideError(type) {
        let errId = '';
        if (type === 'package') errId = 'pkgError';
        else if (type === 'video') errId = 'vError';
        else errId = 'paymentError';

        const errDiv = document.getElementById(errId);
        if (errDiv) errDiv.style.display = 'none';
    }

    // ─── Modal helpers ────────────────────────────────────────────
    const openPackageModal = () => document.getElementById('packageModal').style.display = 'flex';

    const closeModals = () => {
        document.querySelectorAll('.modal-blur').forEach(m => m.style.display = 'none');
    };

    // ─── Preview state ─────────────────────────────────────────────
    let activeHls = null;
    let previewTimer = null;
    let previewBarInterval = null;
    const PREVIEW_SECONDS = 5;

    function closePlayer() {
        document.getElementById('player-ui').style.display = 'none';
        const vid = document.getElementById('fs-video');
        vid.pause();
        vid.removeAttribute('src');
        vid.load();
        document.getElementById('fs-progress').style.transition = 'none';
        document.getElementById('fs-progress').style.width = '0%';
        
        if (activeHls) { activeHls.destroy(); activeHls = null; }
        if (previewTimer) { clearTimeout(previewTimer); previewTimer = null; }
        if (previewBarInterval) { clearInterval(previewBarInterval); previewBarInterval = null; }
    }

    function openPlayer(id, slug, price, title, previewUrl) {
        closePlayer();
        document.getElementById('player-ui').style.display = 'block';
        
        const videoEl = document.getElementById('fs-video');
        const src = (previewUrl && previewUrl.endsWith('.mp4')) 
            ? previewUrl 
            : `/preview/video/${slug}/index.m3u8`;

        if (src.endsWith('.mp4')) {
            videoEl.src = src;
            videoEl.play().catch(() => {});
        } else if (typeof Hls !== 'undefined' && Hls.isSupported()) {
            activeHls = new Hls({
                maxBufferLength: 6, maxMaxBufferLength: 6,
                startLevel: 0, capLevelToPlayerSize: false, autoStartLoad: true,
                manifestLoadingTimeOut: 3000, manifestLoadingMaxRetry: 1, levelLoadingTimeOut: 3000,
            });
            activeHls.loadSource(src);
            activeHls.attachMedia(videoEl);
            activeHls.on(Hls.Events.FRAG_BUFFERED, function onFirst() {
                videoEl.play().catch(() => {});
                activeHls.off(Hls.Events.FRAG_BUFFERED, onFirst);
            });
            activeHls.on(Hls.Events.MANIFEST_PARSED, () => videoEl.play().catch(() => {}));
            activeHls.on(Hls.Events.ERROR, (e, d) => { if (d.fatal) closePlayer(); });
        } else if (videoEl.canPlayType('application/vnd.apple.mpegurl')) {
            videoEl.src = src;
            videoEl.play().catch(() => {});
        }

        videoEl.addEventListener('playing', function onPlaying() {
            videoEl.removeEventListener('playing', onPlaying);
            startPreviewCountdown(id, price, title);
        });
    }

    function startPreviewCountdown(id, price, title) {
        const bar = document.getElementById('fs-progress');
        let elapsed = 0;
        const TICK = 100;

        bar.style.transition = 'none';
        bar.style.width = '0%';

        previewBarInterval = setInterval(() => {
            elapsed += TICK;
            const pct = Math.min((elapsed / (PREVIEW_SECONDS * 1000)) * 100, 100);
            bar.style.transition = `width ${TICK}ms linear`;
            bar.style.width = pct + '%';
        }, TICK);

        previewTimer = setTimeout(() => {
            closePlayer();
            openSingleVideoModal(id, price, title);
        }, PREVIEW_SECONDS * 1000);
    }

    // ─── Single Video Modal ───────────────────────────────────────
    function openSingleVideoModal(id, price, title) {
        // Always clear phone field and reset button state when switching videos
        var phoneEl = document.getElementById('vPhone');
        if (phoneEl) phoneEl.value = '';
        hideError('video');
        var vBtn = document.getElementById('vBtn');
        if (vBtn) { vBtn.disabled = false; vBtn.innerHTML = '<i class="fas fa-unlock-alt"></i> UNLOCK VIDEO'; }

        document.getElementById('videoModal').style.display = 'flex';
        document.getElementById('vTitle').innerText = title;
        document.getElementById('vPrice').innerText = parseInt(price).toLocaleString() + '/=';
        document.getElementById('selectedVidId').value = id;
    }

    // ─── Package selection ────────────────────────────────────────
    function selectPkg(id, el) {
        document.querySelectorAll('.pkg-item').forEach(i => i.classList.remove('active'));
        el.classList.add('active');
        document.getElementById('selectedPkgId').value = id;
    }

    // ─── Payments ─────────────────────────────────────────────────
    // Helper to fetch and handle non-JSON / error responses
    async function fetchAPI(url, options) {
        console.log("Fetching API URL:", url);
        const res = await fetch(url, options);
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch (err) {
            console.error("Non-JSON response from " + url, text);
            throw new Error("Server returned invalid response (Status: " + res.status + ").\n\nContent:\n" + text.substring(0, 300));
        }
    }

    async function processPackagePay() {
        hideError('package');
        const id    = document.getElementById('selectedPkgId').value;
        const phone = document.getElementById('pkgPhone').value.trim();
        if (!id)   return showError('package', 'Chagua kifurushi.');
        if (!/^0[0-9]{9}$/.test(phone)) return showError('package', 'Weka namba sahihi (07xx xxxxxx).');

        toggleLoading('pkgBtn', true);
        try {
            const data = await fetchAPI("<?= BASE_URL ?>/api/process_package.php", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': 'flGU8LM8PkkIaCNbNsRw3gKfz3PmMwaDMk1VFj9B' },
                body: JSON.stringify({ package_id: id, phone })
            });
            handleResponse(data, 'pkgBtn', 'package', id);
        } catch (e) {
            showError('package', 'Hitilafu ya mtandao. Jaribu tena.\n\n[Error Details]:\n' + e.message);
            toggleLoading('pkgBtn', false);
        }
    }

    async function processVideoPay() {
        hideError('video');
        const id    = document.getElementById('selectedVidId').value;
        const phone = document.getElementById('vPhone').value.trim();
        if (!/^0[0-9]{9}$/.test(phone)) return showError('package', 'Weka namba sahihi (07xx xxxxxx).');

        toggleLoading('vBtn', true);
        try {
            const data = await fetchAPI("<?= BASE_URL ?>/api/process_payment.php", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': 'flGU8LM8PkkIaCNbNsRw3gKfz3PmMwaDMk1VFj9B' },
                body: JSON.stringify({ video_id: id, phone })
            });
            handleResponse(data, 'vBtn', 'video', id);
        } catch (e) {
            showError('video', 'Hitilafu ya mtandao. Jaribu tena.\n\n[Error Details]:\n' + e.message);
            toggleLoading('vBtn', false);
        }
    }

    function handleResponse(data, btnId, type, id) {
        if (data.status === 'success') {
            document.getElementById(btnId).innerHTML = '<i class="fas fa-spinner fa-spin"></i> WEKA PIN KWENYE SIMU...';
            checkStatus(data.tranID, type, id);
        } else {
            showError(type, data.message || 'Malipo yamefeli. Jaribu tena.');
            toggleLoading(btnId, false);
        }
    }

    function toggleLoading(id, isLoading) {
        const btn = document.getElementById(id);
        btn.disabled = isLoading;
        btn.innerHTML = isLoading
            ? '<i class="fas fa-spinner fa-spin"></i> TAFADHALI SUBIRI...'
            : 'JARIBU TENA';
    }

    function checkStatus(txId, type, id) {
        const interval = setInterval(async () => {
            try {
                const data = await fetchAPI(`<?= BASE_URL ?>/api/check_payment.php?tranid=${txId}`);
                const s    = (data.payment_status || data.status || '').toLowerCase();
                if (s === 'completed' || s === 'success') {
                    clearInterval(interval);
                    
                    // Set 24-hour cookies to handle dynamic IP changes on mobile networks
                    const expires = new Date(Date.now() + 24 * 60 * 60 * 1000).toUTCString();
                    const creatorId = '<?= isset($domain_owner["user_id"]) ? $domain_owner["user_id"] : (isset($user_videos[0]["user_id"]) ? $user_videos[0]["user_id"] : "") ?>';
                    
                    // Set specific item access
                    if (type === 'video') {
                        document.cookie = "sf_video_" + id + "=" + encodeURIComponent(txId) + "; expires=" + expires + "; path=/; SameSite=Lax";
                    } else if (type === 'package') {
                        document.cookie = "sf_channel_" + creatorId + "=" + encodeURIComponent(txId) + "; expires=" + expires + "; path=/; SameSite=Lax";
                    }
                    
                    // Set portal pass cookie
                    document.cookie = "sf_pass_" + creatorId + "=" + encodeURIComponent(txId) + "; expires=" + expires + "; path=/; SameSite=Lax";
                    
                    // Route: channel → streaming.php | single → watch.php
                    const _wid = data.video_id || (type === 'video' ? id : null);
                    if (data.monetization_mode === 'channel') {
                        location.href = '<?= BASE_URL ?>/streaming.php?creator_id=' + creatorId;
                    } else if (_wid) {
                        location.href = '<?= BASE_URL ?>/watch.php?id=' + _wid;
                    } else if (data.global_redirect_url) {
                        location.href = data.global_redirect_url;
                    } else {
                        location.href = `<?= BASE_URL ?>/streaming.php?creator_id=` + creatorId;
                    }
                } else if (s === 'failed') {
                    clearInterval(interval);
                    showError(type, 'Malipo yamefeli. Jaribu tena.');
                    location.reload();
                }
            } catch (e) {
                console.error("Payment status check failed:", e);
            }
        }, 3000);
    }


</script>
</body>
</html>
