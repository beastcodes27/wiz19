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
        'primary_color'     => '#d946ef',
        'secondary_color'   => '#fbbf24',
        'bg_color'          => '#090a10',
        'hero_title'        => $platform_name,
        'hero_description'  => 'Maudhui ya Kipekee. Lipia na uangalie sasa hivi.',
        'hero_image'        => 'assets/defaults/landing-bg.jpg',
        'favicon'           => null,
    ];
}

// Ensure required fields are populated
if (empty($landing_settings['site_name']))        $landing_settings['site_name']        = $platform_name;
if (empty($landing_settings['hero_title']))       $landing_settings['hero_title']       = $platform_name;
if (empty($landing_settings['cta_text']))         $landing_settings['cta_text']         = 'LIPIA TZS 1,000 KUPATA ACCESS';
if (empty($landing_settings['primary_color']))    $landing_settings['primary_color']    = '#d946ef';
if (empty($landing_settings['secondary_color']))  $landing_settings['secondary_color']  = '#fbbf24';
if (empty($landing_settings['bg_color']))         $landing_settings['bg_color']         = '#090a10';
if (empty($landing_settings['hero_description'])) $landing_settings['hero_description'] = 'Maudhui ya Kipekee. Lipia na uangalie sasa hivi.';
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
<html lang="sw" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($landing_settings['site_name']) ?> | Premium Streaming</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-WFSQPTZZLJ"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-WFSQPTZZLJ');
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <style>
        :root {
            --primary:       <?= htmlspecialchars($landing_settings['primary_color']) ?>;
            --primary-glow:  <?= htmlspecialchars($landing_settings['primary_color']) ?>44;
            --premium:       <?= htmlspecialchars($landing_settings['secondary_color']) ?>;
            --bg:            <?= htmlspecialchars($landing_settings['bg_color']) ?>;
            --card-bg:       #121420;
            --text-main:     #f8fafc;
            --text-dim:      #94a3b8;
            --border:        rgba(255,255,255,0.06);
            --glass:         rgba(255,255,255,0.03);
            --safe-top:      env(safe-area-inset-top, 0px);
            --safe-bottom:   env(safe-area-inset-bottom, 0px);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }

        html { scroll-behavior: smooth; -webkit-text-size-adjust: 100%; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
            padding-bottom: calc(96px + var(--safe-bottom));
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* Hide scrollbar */
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }

        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* ── Top Lock Banner ── */
        .lock-banner {
            position: sticky;
            top: 0;
            z-index: 100;
            background: #dc2626;
            color: #fff;
            text-align: center;
            padding: 10px 16px;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.3px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.4);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        /* ── Main Content Container ── */
        .content-wrap {
            max-width: 448px;
            margin: 0 auto;
            padding: 12px 16px 0;
        }

        /* ── Site Header Row ── */
        .site-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(51,65,85,0.6);
            margin-bottom: 20px;
        }

        .site-logo {
            font-size: 1.2rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: -0.5px;
            text-decoration: none;
            color: var(--text-main);
        }

        .site-logo span { color: var(--primary); }

        .currency-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(15,23,42,0.9);
            border: 1px solid rgba(51,65,85,0.8);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
            color: #fbbf24;
        }

        /* ── Video Player Area ── */
        .video-player-area {
            display: none;
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(51,65,85,0.8);
            background: #000;
            aspect-ratio: 16/9;
            box-shadow: 0 20px 60px rgba(0,0,0,0.8);
            position: relative;
            margin-bottom: 20px;
        }

        .video-player-area video {
            width: 100%;
            height: 100%;
        }

        /* ── Stories / Avatar Row ── */
        .stories-row {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            padding: 4px 0;
            margin-bottom: 20px;
        }

        .story-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex-shrink: 0;
            gap: 4px;
            cursor: pointer;
        }

        .story-ring {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            padding: 2px;
            background: linear-gradient(135deg, #eab308, #ec4899, #9333ea);
        }

        .story-ring img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid var(--bg);
        }

        .story-label {
            font-size: 10px;
            color: var(--text-dim);
            font-weight: 600;
            white-space: nowrap;
        }

        /* ── Section Label ── */
        .section-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 900;
            color: var(--text-dim);
            margin-bottom: 12px;
        }

        .section-label i { color: var(--primary); }

        /* ── Video Grid ── */
        .video-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }

        /* ── Video Card ── */
        .video-card {
            background: var(--card-bg);
            border: 1px solid var(--primary);
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            position: relative;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .video-card:active { transform: scale(0.985); }

        @media (hover: hover) {
            .video-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 12px 32px rgba(0,0,0,0.5);
                border-color: var(--primary);
            }
        }

        /* Thumbnail */
        .card-thumb {
            position: relative;
            overflow: hidden;
            background: #000;
            flex-shrink: 0;
            width: 100%;
            height: 128px;
        }

        .card-thumb-bg {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            opacity: 0.7;
            transition: transform 0.3s ease;
        }

        .video-card:hover .card-thumb-bg { transform: scale(1.05); }

        /* Preview video inside card */
        .card-preview-video {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
            z-index: 3;
        }

        /* Preview countdown overlay */
        .preview-overlay {
            position: absolute;
            inset: 0;
            z-index: 5;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            padding-bottom: 10px;
            background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, transparent 60%);
        }

        .preview-bar-wrap {
            width: calc(100% - 16px);
            height: 3px;
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 6px;
        }

        .preview-bar {
            height: 100%;
            background: var(--primary);
            width: 0%;
            border-radius: 10px;
            transition: width linear;
        }

        .preview-label {
            font-size: 9px;
            font-weight: 800;
            color: rgba(255,255,255,0.8);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .preview-playing .preview-overlay { display: flex; }
        .preview-playing .card-preview-video { display: block; }
        .preview-playing .card-thumb-bg { opacity: 0; }
        .preview-playing .card-badge { display: none; }
        .preview-playing .card-price { display: none; }
        .preview-playing .card-play-icon { display: none; }

        /* Card overlays */
        .card-price {
            position: absolute;
            bottom: 4px;
            left: 6px;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(4px);
            color: #fbbf24;
            font-size: 8px;
            font-weight: 900;
            padding: 2px 6px;
            border-radius: 4px;
            z-index: 4;
        }

        .card-badge {
            position: absolute;
            top: 6px;
            right: 6px;
            background: #7c3aed;
            color: #fff;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 7px;
            z-index: 4;
            box-shadow: 0 2px 6px rgba(0,0,0,0.4);
        }

        .card-play-icon {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0.1);
            transition: background 0.2s;
            z-index: 4;
        }

        .video-card:hover .card-play-icon { background: rgba(0,0,0,0.3); }

        .card-play-icon i {
            font-size: 1.25rem;
            color: #fff;
            opacity: 0.85;
            transition: transform 0.2s;
        }

        .video-card:hover .card-play-icon i { transform: scale(1.1); }

        /* Card Info */
        .card-info {
            padding: 6px 6px 8px;
            flex: 1;
            min-width: 0;
        }

        .card-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-main);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            letter-spacing: -0.2px;
        }

        .card-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 9px;
            color: var(--text-dim);
            margin-top: 6px;
            font-weight: 500;
        }

        .card-meta span { display: flex; align-items: center; gap: 3px; }
        .card-meta i { color: #64748b; }

        /* No videos state */
        .no-videos {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px 16px;
            color: var(--text-dim);
        }

        .no-videos i { font-size: 3rem; margin-bottom: 15px; color: var(--primary); display: block; }
        .no-videos p { font-size: 1rem; font-weight: 600; color: var(--text-main); }

        /* ── Payment Modal (Video) ── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.85);
            backdrop-filter: blur(16px) saturate(160%);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            padding: 20px;
        }

        .modal-content {
            background: linear-gradient(145deg, #1a1a2e, #0f0f1a);
            width: 100%;
            max-width: 400px;
            border-radius: 24px;
            padding: 36px 24px;
            position: relative;
            text-align: center;
            border: 1px solid rgba(217,70,239,0.15);
            box-shadow: 0 20px 60px rgba(0,0,0,0.6), inset 0 1px 0 rgba(255,255,255,0.04);
            animation: modalUp 0.3s cubic-bezier(0.4,0,0.2,1);
        }

        @keyframes modalUp {
            from { transform: translateY(40px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }

        .modal-icon {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: rgba(217,70,239,0.12);
            border: 1px solid rgba(217,70,239,0.25);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin: 0 auto 14px;
        }

        .close-modal {
            position: absolute;
            top: 18px;
            right: 18px;
            font-size: 1.1rem;
            color: var(--text-dim);
            cursor: pointer;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.2s;
        }

        .close-modal:hover { background: rgba(255,255,255,0.1); }

        .modal-price {
            font-size: 1.5rem;
            font-weight: 900;
            color: var(--premium);
            margin: 14px 0;
        }

        .modal-title {
            font-size: 1rem;
            font-weight: 700;
        }

        .modal-subtitle {
            font-size: 0.8rem;
            color: var(--text-dim);
            margin: 6px 0 18px;
        }

        /* Package items */
        .pkg-item {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border);
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: 0.25s;
            text-align: left;
        }

        .pkg-item.active {
            border-color: var(--primary);
            background: rgba(217,70,239,0.08);
        }

        .pkg-item-name { font-weight: 700; font-size: 0.9rem; display: block; }
        .pkg-item-sub  { font-size: 0.7rem; color: var(--text-dim); }
        .pkg-item-price { font-size: 0.95rem; font-weight: 800; color: var(--premium); }

        .pkg-radio {
            width: 18px; height: 18px;
            border: 2px solid #444;
            border-radius: 50%;
            position: relative;
            flex-shrink: 0;
            margin-left: 12px;
        }

        .pkg-item.active .pkg-radio { border-color: var(--primary); }
        .pkg-item.active .pkg-radio::after {
            content: '';
            position: absolute;
            inset: 3px;
            background: var(--primary);
            border-radius: 50%;
        }

        /* Input */
        .input-wrapper {
            position: relative;
            margin-top: 18px;
        }

        .input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-dim);
            font-size: 0.85rem;
        }

        .input-wrapper input {
            width: 100%;
            padding: 14px 14px 14px 42px;
            background: rgba(0,0,0,0.5);
            border: 1px solid #2d3748;
            border-radius: 12px;
            color: var(--text-main);
            outline: none;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.2s;
        }

        .input-wrapper input:focus { border-color: var(--primary); }

        /* Pay button */
        .btn-pay {
            width: 100%;
            margin-top: 18px;
            padding: 15px;
            border-radius: 14px;
            border: none;
            cursor: pointer;
            background: var(--primary);
            color: #fff;
            font-size: 0.95rem;
            font-weight: 800;
            font-family: inherit;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: opacity 0.2s, transform 0.2s;
            min-height: 50px;
        }

        .btn-pay:hover:not(:disabled) { opacity: 0.9; transform: translateY(-2px); }
        .btn-pay:disabled { opacity: 0.6; cursor: not-allowed; }

        /* Preview teaser badge */
        .preview-teaser-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(217,70,239,0.12);
            border: 1px solid rgba(217,70,239,0.25);
            color: var(--primary);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 800;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        /* Modal Error */
        .modal-error {
            display: none;
            color: #fca5a5;
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.3);
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 0.85rem;
            text-align: left;
            line-height: 1.4;
            animation: fadeIn 0.3s ease;
        }
        .modal-error i {
            margin-right: 6px;
            color: #ef4444;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 480px) {
            .modal-content { padding: 28px 18px; border-radius: 20px; }
            .card-thumb { height: 110px; }
        }
    </style>
</head>
<body>

    <!-- Lock Banner -->
    <div class="lock-banner">
        <i class="fa-solid fa-lock" style="margin-right: 6px;"></i>
        <?= htmlspecialchars($landing_settings['cta_text']) ?> — MAUDHUI YAMELIFUNGWA.
    </div>

    <div class="content-wrap">

        <!-- Site Header -->
        <div class="site-header">
            <a href="#" class="site-logo">
                <?= htmlspecialchars($landing_settings['site_name']) ?><span>.LIVE</span>
            </a>
            <div class="currency-badge">
                <i class="fa-solid fa-wallet" style="font-size:9px;"></i>
                <span>TZ SHILLING</span>
            </div>
        </div>

        <!-- Inline Video Player (shown after unlock) -->
        <div class="video-player-area" id="videoPlayerArea">
            <video id="mainHtmlVideoPlayer" controls autoplay controlsList="nodownload" playsinline>
                <source id="videoSourceTag" src="" type="video/mp4">
                Kivinjari chako hakisupport kucheza video hii.
            </video>
        </div>

        <!-- Stories / Category Row -->
        <div class="stories-row no-scrollbar">
            <div class="story-item">
                <div class="story-ring">
                    <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop" alt="Trending">
                </div>
                <span class="story-label">Trending</span>
            </div>
            <div class="story-item">
                <div class="story-ring">
                    <img src="https://images.unsplash.com/photo-1517841905240-472988babdf9?w=100&auto=format&fit=crop" alt="Leaks">
                </div>
                <span class="story-label">Leaks</span>
            </div>
            <div class="story-item">
                <div class="story-ring">
                    <img src="https://images.unsplash.com/photo-1488161628813-04466f872be2?w=100&auto=format&fit=crop" alt="New">
                </div>
                <span class="story-label">Mpya</span>
            </div>
            <div class="story-item">
                <div class="story-ring">
                    <img src="https://images.unsplash.com/photo-1520813792240-56fc4a3765a7?w=100&auto=format&fit=crop" alt="Hot">
                </div>
                <span class="story-label">Moto 🔥</span>
            </div>
        </div>

        <!-- Section Label -->
        <div class="section-label">
            <i class="fa-solid fa-bolt fa-fade"></i>
            <span>Zilizopendwa Leo</span>
        </div>

        <!-- Video Grid -->
        <div class="video-grid">
            <?php if (!empty($user_videos)): ?>
                <?php foreach ($user_videos as $video): ?>
                    <div class="video-card"
                         data-id="<?= htmlspecialchars((string)$video['id']) ?>"
                         data-price="<?= htmlspecialchars((string)($video['price'] ?? 1000)) ?>"
                         data-title="<?= htmlspecialchars($video['title']) ?>"
                         data-folder="<?= htmlspecialchars($video['slug']) ?>"
                         data-slug="<?= htmlspecialchars($video['slug']) ?>"
                         data-preview-url="<?= htmlspecialchars($video['video_url']) ?>"
                         data-paid="0"
                         onclick="handleCardClick(this)">

                        <div class="card-thumb">
                            <!-- Thumbnail background -->
                            <div class="card-thumb-bg"
                                 style="background-image: url('<?= htmlspecialchars($video['thumbnail_url'] ?? 'assets/images/default_thumb.jpg') ?>');"></div>

                            <!-- Preview video element -->
                            <video class="card-preview-video" playsinline preload="none"></video>

                            <!-- Preview progress overlay -->
                            <div class="preview-overlay">
                                <div class="preview-bar-wrap">
                                    <div class="preview-bar"></div>
                                </div>
                                <span class="preview-label">
                                    <i class="fas fa-play" style="font-size:8px;margin-right:3px;"></i>
                                    Preview · <span class="preview-countdown">5</span>s
                                </span>
                            </div>

                            <!-- Price label -->
                            <span class="card-price">TSH <?= number_format($video['price'] ?? 1000) ?></span>

                            <!-- Lock badge -->
                            <span class="card-badge"><i class="fa-solid fa-lock"></i></span>

                            <!-- Play icon overlay -->
                            <div class="card-play-icon">
                                <i class="fa-solid fa-circle-play"></i>
                            </div>
                        </div>

                        <div class="card-info">
                            <div class="card-title"><?= htmlspecialchars($video['title']) ?></div>
                            <div class="card-meta">
                                <span><i class="fa-solid fa-eye"></i><?= number_format($video['views'] ?? 0) ?></span>
                                <span><i class="fa-solid fa-clock"></i>Kipindi hiki</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-videos">
                    <i class="fas fa-video-slash"></i>
                    <p>Hakuna video zilizopakiwa bado.</p>
                </div>
            <?php endif; ?>
        </div>

    </div><!-- /.content-wrap -->

    <!-- ── Package Modal (CTA Hero) ── -->
    <div class="modal-overlay" id="packageModal">
        <div class="modal-content">
            <i class="fas fa-times close-modal" onclick="closeModals()"></i>
            <div class="modal-icon"><i class="fas fa-crown"></i></div>
            <h2 class="modal-title">Premium Access</h2>
            <p class="modal-subtitle">Angalia video zote bila kikomo.</p>

            <div id="pkgError" class="modal-error"></div>

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

            <div class="input-wrapper">
                <i class="fas fa-phone"></i>
                <input type="tel" id="pkgPhone" placeholder="Namba ya Simu (07xx...)" inputmode="tel">
            </div>
            <input type="hidden" id="selectedPkgId" value="<?= !empty($packages) ? htmlspecialchars((string)$packages[0]['id']) : 'default' ?>">
            <button class="btn-pay" id="pkgBtn" onclick="processPackagePay()">
                <i class="fas fa-lock-open"></i> LIPA SASA
            </button>
        </div>
    </div>

    <!-- ── Single Video Payment Modal ── -->
    <div class="modal-overlay" id="videoModal">
        <div class="modal-content">
            <i class="fas fa-times close-modal" onclick="closeModals()"></i>
            <div class="modal-icon" style="color:var(--premium);border-color:rgba(251,191,36,0.25);background:rgba(251,191,36,0.08);">
                <i class="fas fa-play-circle"></i>
            </div>
            <div class="preview-teaser-badge"><i class="fas fa-eye"></i> Preview imekwisha</div>
            <h2 class="modal-title" id="vTitle"></h2>
            <div class="modal-price" id="vPrice"></div>

            <div id="vError" class="modal-error"></div>

            <div class="input-wrapper">
                <i class="fas fa-phone"></i>
                <input type="tel" id="vPhone" placeholder="Namba ya Simu (07xx...)" inputmode="tel">
            </div>
            <input type="hidden" id="selectedVidId">
            <button class="btn-pay" id="vBtn" onclick="processVideoPay()">
                <i class="fas fa-unlock-alt"></i> UNLOCK VIDEO
            </button>
            <button onclick="closeModals()" style="width:100%;margin-top:10px;padding:14px;background:transparent;border:1px solid rgba(217,70,239,0.2);border-radius:14px;color:var(--text-dim);font-size:0.9rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;">
                <i class="fas fa-times-circle"></i> Ghairi
            </button>
        </div>
    </div>

    <script>
        // ─── Modal helpers ────────────────────────────────────────────
        const openPackageModal = () => document.getElementById('packageModal').style.display = 'flex';

        const closeModals = () => {
            document.querySelectorAll('.modal-overlay').forEach(m => m.style.display = 'none');
            hideError('package');
            hideError('video');
        };

        function showError(type, message) {
            const errDiv = document.getElementById(type === 'package' ? 'pkgError' : 'vError');
            errDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
            errDiv.style.display = 'block';
        }

        function hideError(type) {
            const errDiv = document.getElementById(type === 'package' ? 'pkgError' : 'vError');
            if (errDiv) errDiv.style.display = 'none';
        }

        // ─── Preview state ─────────────────────────────────────────────
        let activePreviewCard  = null;
        let activeHls          = null;
        let previewTimer       = null;
        let previewBarInterval = null;
        let currentVideoSlug   = '';
        const PREVIEW_SECONDS  = 5;

        function stopAllPreviews() {
            document.querySelectorAll('.video-card.preview-playing').forEach(card => {
                card.classList.remove('preview-playing');
                const vid = card.querySelector('.card-preview-video');
                vid.pause();
                vid.removeAttribute('src');
                vid.load();
                card.querySelector('.preview-bar').style.transition = 'none';
                card.querySelector('.preview-bar').style.width = '0%';
                const countdown = card.querySelector('.preview-countdown');
                if (countdown) countdown.textContent = PREVIEW_SECONDS;
            });
            if (activeHls)          { activeHls.destroy(); activeHls = null; }
            if (previewTimer)       { clearTimeout(previewTimer); previewTimer = null; }
            if (previewBarInterval) { clearInterval(previewBarInterval); previewBarInterval = null; }
            activePreviewCard = null;
        }

        function handleCardClick(card) {
            const isPaid  = card.dataset.paid === '1';
            const folder  = card.dataset.folder;
            currentVideoSlug = card.dataset.slug || '';

            // Already paid → go to player
            if (isPaid) {
                const redirectUrl = folder.includes('-') && folder.length > 20
                    ? `/player/${folder}`
                    : `<?= BASE_URL ?>/video_view.php?v=${folder}`;
                window.location.href = redirectUrl;
                return;
            }

            // Same card already previewing → open modal immediately
            if (activePreviewCard === card) {
                stopAllPreviews();
                openSingleVideoModal(card.dataset.id, card.dataset.price, card.dataset.title);
                return;
            }

            stopAllPreviews();
            startPreview(card, folder);
        }

        async function startPreview(card, folder) {
            activePreviewCard = card;
            card.classList.add('preview-playing');

            // Track view
            const videoId = card.dataset.id;
            fetch('<?= BASE_URL ?>/api/track', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ video_id: videoId, event_type: 'view' }),
                keepalive: true
            }).catch(() => {});

            const videoEl = card.querySelector('.card-preview-video');
            videoEl.pause();
            videoEl.removeAttribute('src');
            videoEl.load();

            const previewUrl = card.dataset.previewUrl || '';
            const src = previewUrl.endsWith('.mp4')
                ? previewUrl
                : `/preview/video/${folder}/index.m3u8`;

            if (previewUrl.endsWith('.mp4')) {
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
                activeHls.on(Hls.Events.ERROR, (e, d) => { if (d.fatal) stopAllPreviews(); });
            } else if (videoEl.canPlayType('application/vnd.apple.mpegurl')) {
                videoEl.src = src;
                videoEl.play().catch(() => {});
            }

            videoEl.addEventListener('playing', function onPlaying() {
                videoEl.removeEventListener('playing', onPlaying);
                startPreviewCountdown(card);
            });
        }

        function startPreviewCountdown(card) {
            const bar      = card.querySelector('.preview-bar');
            const countdown = card.querySelector('.preview-countdown');
            let elapsed = 0;
            const TICK = 100;

            bar.style.transition = 'none';
            bar.style.width = '0%';

            previewBarInterval = setInterval(() => {
                elapsed += TICK;
                const pct = Math.min((elapsed / (PREVIEW_SECONDS * 1000)) * 100, 100);
                bar.style.transition = `width ${TICK}ms linear`;
                bar.style.width = pct + '%';
                const secsLeft = Math.max(0, PREVIEW_SECONDS - Math.floor(elapsed / 1000));
                if (countdown) countdown.textContent = secsLeft;
            }, TICK);

            previewTimer = setTimeout(() => {
                const { id, price, title, slug } = card.dataset;
                stopAllPreviews();
                currentVideoSlug = slug || '';
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
            document.getElementById('vPrice').innerText = 'TSH ' + parseInt(price).toLocaleString();
            document.getElementById('selectedVidId').value = id;

            fetch('<?= BASE_URL ?>/api/track', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ video_id: id, event_type: 'click_cta' }),
                keepalive: true
            }).catch(() => {});
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
            const id    = document.getElementById('selectedPkgId').value;
            const phone = document.getElementById('pkgPhone').value.trim();
            hideError('package');
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
                showError('package', 'Hitilafu ya mtandao. Jaribu tena.<br><small>' + e.message + '</small>');
                toggleLoading('pkgBtn', false);
            }
        }

        async function processVideoPay() {
            const id    = document.getElementById('selectedVidId').value;
            const phone = document.getElementById('vPhone').value.trim();
            hideError('video');
            if (!/^0[0-9]{9}$/.test(phone)) return showError('video', 'Weka namba sahihi (07xx xxxxxx).');

            toggleLoading('vBtn', true);
            try {
                const data = await fetchAPI("<?= BASE_URL ?>/api/process_payment.php", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': 'flGU8LM8PkkIaCNbNsRw3gKfz3PmMwaDMk1VFj9B' },
                    body: JSON.stringify({ video_id: id, phone })
                });
                handleResponse(data, 'vBtn', 'video', id);
            } catch (e) {
                showError('video', 'Hitilafu ya mtandao. Jaribu tena.<br><small>' + e.message + '</small>');
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
                        const btnId = type === 'package' ? 'pkgBtn' : 'vBtn';
                        toggleLoading(btnId, false);
                    }
                } catch (e) {
                    console.error("Payment status check failed:", e);
                }
            }, 3000);
        }

        // Stop preview when clicking outside card/modal
        document.addEventListener('click', function(e) {
            if (
                activePreviewCard &&
                !activePreviewCard.contains(e.target) &&
                !e.target.closest('.modal-overlay')
            ) {
                stopAllPreviews();
            }
        });
    </script>
</body>
</html>
