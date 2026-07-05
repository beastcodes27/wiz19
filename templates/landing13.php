<?php
/**
 * templates/landing13.php
 * YouTube-Style Theme — integrated with platform API, HLS previews & payment system.
 */

// ── Settings ────────────────────────────────────────────────────────────────
$landing_settings = null;
if (isset($domain_owner['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM user_landing_settings WHERE user_id = ? LIMIT 1");
        $stmt->execute([$domain_owner['user_id']]);
        $landing_settings = $stmt->fetch();
    } catch (PDOException $e) { /* silent */ }
}
if (!$landing_settings) {
    $landing_settings = [
        'site_name'        => $platform_name,
        'cta_text'         => 'LIPIA SASA',
        'primary_color'    => '#16a34a',
        'secondary_color'  => '#f97316',
        'bg_color'         => '#f9f9f9',
        'hero_title'       => $platform_name,
        'hero_description' => 'Tazama preview bure, lipia kuendelea',
        'hero_image'       => 'assets/defaults/landing-bg.jpg',
        'favicon'          => null,
    ];
}
if (empty($landing_settings['site_name']))        $landing_settings['site_name']        = $platform_name;
if (empty($landing_settings['hero_title']))        $landing_settings['hero_title']       = $platform_name;
if (empty($landing_settings['cta_text']))          $landing_settings['cta_text']         = 'LIPIA SASA';
if (empty($landing_settings['primary_color']))     $landing_settings['primary_color']    = '#16a34a';
if (empty($landing_settings['secondary_color']))   $landing_settings['secondary_color']  = '#f97316';
if (empty($landing_settings['bg_color']))          $landing_settings['bg_color']         = '#f9f9f9';
if (empty($landing_settings['hero_description']))  $landing_settings['hero_description'] = 'Tazama preview bure, lipia kuendelea';
if (empty($landing_settings['hero_image']))        $landing_settings['hero_image']       = 'assets/defaults/landing-bg.jpg';

// ── Packages ─────────────────────────────────────────────────────────────────
$packages = [];
if (isset($domain_owner['user_id'])) {
    try {
        $pkgStmt = $pdo->prepare("SELECT * FROM packages WHERE user_id = ? ORDER BY price ASC");
        $pkgStmt->execute([$domain_owner['user_id']]);
        $packages = $pkgStmt->fetchAll();
    } catch (PDOException $e) { /* silent */ }
}

// ── Creator ID ───────────────────────────────────────────────────────────────
$creator_id = isset($domain_owner['user_id']) ? $domain_owner['user_id']
            : (isset($user_videos[0]['user_id']) ? $user_videos[0]['user_id'] : '');

// ── First video (hero) ───────────────────────────────────────────────────────
$hero_video = !empty($user_videos) ? $user_videos[0] : null;
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($landing_settings['site_name']) ?> | Premium Streaming</title>
    <meta name="description" content="<?= htmlspecialchars($landing_settings['hero_description']) ?>">
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <style>
        :root {
            --primary:   <?= htmlspecialchars($landing_settings['primary_color']) ?>;
            --secondary: <?= htmlspecialchars($landing_settings['secondary_color']) ?>;
            --bg:        <?= htmlspecialchars($landing_settings['bg_color']) ?>;
            --surface:   #fff;
            --border:    #e5e5e5;
            --text:      #0f0f0f;
            --muted:     #606060;
            --radius:    8px;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Roboto', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
            padding-bottom: 70px;
            -webkit-tap-highlight-color: transparent;
        }
        a { text-decoration: none; color: inherit; }
        button { cursor: pointer; font-family: inherit; border: none; background: none; }

        /* ── Header ── */
        .yt-header {
            position: fixed; top: 0; left: 0; right: 0; height: 56px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 16px; z-index: 200; gap: 12px;
        }
        .header-logo {
            display: flex; align-items: center; gap: 8px;
            font-size: 1.2rem; font-weight: 700; color: var(--text); flex-shrink: 0;
        }
        .header-logo .yt-icon { color: var(--primary); font-size: 1.6rem; }
        .header-search {
            flex: 1; max-width: 540px;
            display: flex; align-items: center;
            border: 1px solid var(--border); border-radius: 40px;
            overflow: hidden; background: #fff;
        }
        .header-search input {
            flex: 1; border: none; outline: none;
            padding: 8px 16px; font-size: .9rem;
            font-family: 'Roboto', sans-serif; background: transparent;
        }
        .header-search .search-btn {
            padding: 8px 20px; background: #f8f8f8;
            border-left: 1px solid var(--border); color: var(--muted);
        }
        .header-actions button {
            width: 36px; height: 36px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; color: var(--text); transition: .15s;
        }
        .header-actions button:hover { background: #f0f0f0; }

        /* ── Main Wrap ── */
        .yt-wrap { max-width: 900px; margin: 0 auto; padding: 64px 12px 8px; }

        /* ── Player ── */
        .player-wrapper {
            position: relative; background: #000;
            border-radius: var(--radius); overflow: hidden;
            aspect-ratio: 16/9; width: 100%;
        }
        #playerBox { width: 100%; height: 100%; }
        #playerBox video { width: 100%; height: 100%; display: block; border: none; }

        /* Poster (before play) */
        #playerPoster {
            width: 100%; height: 100%; position: relative;
            background: #000; cursor: pointer;
        }
        #playerPoster img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .main-play-wrap {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            background: rgba(0,0,0,.28); transition: .2s;
        }
        #playerPoster:hover .main-play-wrap { background: rgba(0,0,0,.4); }
        .main-play-btn {
            width: 80px; height: 80px; border-radius: 50%;
            background: rgba(255,255,255,.95);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 6px 28px rgba(0,0,0,.55); transition: .2s;
        }
        #playerPoster:hover .main-play-btn { transform: scale(1.08); }
        .main-play-btn i { font-size: 2rem; color: var(--primary); margin-left: 5px; }
        .main-play-badge {
            position: absolute; bottom: 14px; left: 50%; transform: translateX(-50%);
            background: rgba(0,0,0,.7); color: #fff;
            font-size: .78rem; font-weight: 600;
            padding: 6px 16px; border-radius: 30px;
            display: flex; align-items: center; gap: 6px; white-space: nowrap;
        }
        .main-play-badge i { color: #ffcc00; }

        /* Gate overlay */
        .gate-overlay {
            position: absolute; inset: 0; background: rgba(0,0,0,.8);
            display: none; flex-direction: column; align-items: center; justify-content: center;
            z-index: 10; text-align: center; padding: 20px;
        }
        .gate-overlay.show { display: flex; }
        .gate-lock { font-size: 3rem; margin-bottom: 12px; }
        .gate-title { font-size: 1.3rem; font-weight: 700; color: #fff; margin-bottom: 8px; }
        .gate-sub { font-size: .9rem; color: rgba(255,255,255,.8); margin-bottom: 20px; }
        .gate-pay-btn {
            background: var(--primary); color: #fff;
            padding: 12px 30px; border-radius: 40px;
            font-size: 1rem; font-weight: 700;
            display: flex; align-items: center; gap: 8px; transition: .2s;
            border: none; cursor: pointer;
        }
        .gate-pay-btn:hover { opacity: .88; transform: scale(1.03); }

        /* ── Video info ── */
        .vid-info { padding: 12px 0; }
        .vid-main-title { font-size: 1.15rem; font-weight: 600; line-height: 1.4; margin-bottom: 8px; }
        .vid-meta-row {
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 8px;
            padding-bottom: 12px; border-bottom: 1px solid var(--border); margin-bottom: 12px;
        }
        .vid-views { font-size: .85rem; color: var(--muted); }
        .vid-actions { display: flex; gap: 4px; }
        .action-btn {
            display: flex; align-items: center; gap: 6px;
            padding: 8px 14px; border-radius: 40px;
            background: #f0f0f0; font-size: .85rem; font-weight: 500; transition: .2s;
        }
        .action-btn:hover { background: #e0e0e0; }
        .action-btn.liked { background: #dcfce7; color: var(--primary); }
        .channel-row { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .channel-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; font-weight: 700; color: #fff;
            background: var(--primary); flex-shrink: 0; overflow: hidden;
        }
        .channel-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .channel-name { font-weight: 600; font-size: .95rem; }
        .channel-sub { font-size: .78rem; color: var(--muted); }

        /* ── Related grid ── */
        .rel-section { max-width: 900px; margin: 0 auto; padding: 0 12px 8px; }
        .section-heading {
            font-size: 1rem; font-weight: 700; margin-bottom: 14px;
            display: flex; align-items: center; gap: 8px;
        }
        .section-heading::before {
            content: ''; display: inline-block; width: 4px; height: 20px;
            background: var(--primary); border-radius: 2px;
        }
        .rel-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }

        /* ── More videos ── */
        .more-section { max-width: 1280px; margin: 0 auto; padding: 0 12px 24px; }
        .vid-row-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; }

        /* ── Video card ── */
        .vid-card {
            cursor: pointer; border-radius: var(--radius); overflow: hidden;
            background: var(--surface); transition: .2s;
        }
        .vid-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,.12); }
        .vid-card-thumb {
            position: relative; aspect-ratio: 16/9;
            background: #111; overflow: hidden;
        }
        .vid-card-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .3s; }
        .vid-card:hover .vid-card-thumb img { transform: scale(1.05); }
        .vid-card-dur {
            position: absolute; bottom: 6px; right: 6px;
            background: rgba(0,0,0,.8); color: #fff;
            font-size: .72rem; padding: 2px 5px; border-radius: 3px; font-weight: 600;
        }
        .vid-card-play-icon {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            background: rgba(0,0,0,.18); transition: .2s;
        }
        .pb-circle {
            width: 48px; height: 48px; border-radius: 50%;
            background: rgba(255,255,255,.92);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 14px rgba(0,0,0,.4); transform: scale(.85); transition: .2s;
        }
        .pb-circle i { font-size: 1rem; color: var(--primary); margin-left: 3px; }
        .vid-card:hover .vid-card-play-icon { background: rgba(0,0,0,.4); }
        .vid-card:hover .pb-circle { transform: scale(1); }
        .vid-card-info { padding: 10px; }
        .vid-card-title {
            font-size: .83rem; font-weight: 600; line-height: 1.35;
            display: -webkit-box; -webkit-line-clamp: 2;
            -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 4px;
        }
        .vid-card-meta { font-size: .73rem; color: var(--muted); }

        /* ── Nav buttons ── */
        .row-nav {
            display: flex; align-items: center; justify-content: space-between;
            margin-top: 20px; padding-bottom: 8px;
        }
        .nav-btn {
            display: flex; align-items: center; gap: 8px;
            padding: 10px 24px; border-radius: 40px;
            font-size: .9rem; font-weight: 600;
            border: 2px solid var(--border); transition: .2s;
        }
        .nav-btn:hover:not(:disabled) { background: var(--text); color: #fff; border-color: var(--text); }
        .nav-btn:disabled { opacity: .3; cursor: not-allowed; }
        .page-info { font-size: .85rem; color: var(--muted); }

        /* ── Payment Modal ── */
        .pay-modal {
            position: fixed; inset: 0;
            background: rgba(10,10,10,.65);
            backdrop-filter: blur(4px);
            display: none; align-items: center; justify-content: center;
            z-index: 2000; padding: 16px;
        }
        .pay-modal.open { display: flex; animation: fadeIn .25s ease; }
        .pay-sheet {
            position: relative; background: #fff;
            width: 100%; max-width: 400px; border-radius: 24px;
            padding: 0 0 26px;
            animation: popIn .35s cubic-bezier(.2,.9,.3,1.2);
            max-height: 92vh; overflow-y: auto;
            box-shadow: 0 24px 70px rgba(0,0,0,.45);
        }
        .pay-close {
            position: absolute; top: 12px; right: 12px;
            width: 34px; height: 34px; border-radius: 50%;
            background: rgba(255,255,255,.25); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; z-index: 3; transition: .15s;
        }
        .pay-close:hover { background: rgba(255,255,255,.45); }
        .pay-hero {
            background: linear-gradient(135deg, var(--primary) 0%, color-mix(in srgb, var(--primary) 70%, #000) 100%);
            border-radius: 24px 24px 0 0; padding: 26px 22px 22px;
            text-align: center; position: relative; overflow: hidden;
        }
        .pay-hero::before {
            content: ''; position: absolute; top: -40px; right: -30px;
            width: 140px; height: 140px; background: rgba(255,255,255,.08); border-radius: 50%;
        }
        .pay-hero::after {
            content: ''; position: absolute; bottom: -50px; left: -20px;
            width: 120px; height: 120px; background: rgba(255,255,255,.06); border-radius: 50%;
        }
        .pay-avatar {
            width: 62px; height: 62px;
            background: rgba(255,255,255,.18); border: 2px solid rgba(255,255,255,.5);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 1.7rem; margin: 0 auto 10px; color: #fff; position: relative; z-index: 1;
        }
        .pay-name  { font-size: 1.15rem; font-weight: 800; color: #fff; margin-bottom: 3px; position: relative; z-index: 1; }
        .pay-sub   { font-size: .85rem; color: rgba(255,255,255,.9); position: relative; z-index: 1; }
        .pay-amount-box {
            background: #fff; border: 2px solid #d1fae5; border-radius: 18px;
            padding: 14px; text-align: center;
            margin: -18px 22px 18px; position: relative; z-index: 2;
            box-shadow: 0 8px 24px rgba(0,0,0,.08);
        }
        .amt-label { font-size: .72rem; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .amt { font-size: 2.2rem; font-weight: 900; color: #0f0f0f; line-height: 1.1; }
        .cur { font-size: .9rem; color: var(--muted); font-weight: 700; }
        .pay-body { padding: 0 22px; }
        .vid-tag-label {
            font-size: .78rem; font-weight: 700; color: var(--primary);
            background: rgba(22,163,74,.1); padding: 5px 14px;
            border-radius: 20px; margin-bottom: 14px; display: inline-block;
        }

        /* Packages */
        .pkg-list { margin-bottom: 16px; }
        .pkg-item {
            display: flex; align-items: center; justify-content: space-between;
            border: 2px solid var(--border); border-radius: 14px;
            padding: 12px 16px; margin-bottom: 8px; cursor: pointer; transition: .2s;
        }
        .pkg-item.active { border-color: var(--primary); background: rgba(22,163,74,.06); }
        .pkg-item-name  { font-weight: 700; font-size: .9rem; display: block; }
        .pkg-item-sub   { font-size: .72rem; color: var(--muted); }
        .pkg-item-price { font-size: .95rem; font-weight: 800; color: var(--text); margin-left: 12px; }
        .pkg-radio {
            width: 18px; height: 18px; border: 2px solid #ccc;
            border-radius: 50%; position: relative; flex-shrink: 0; margin-left: 10px;
        }
        .pkg-item.active .pkg-radio { border-color: var(--primary); }
        .pkg-item.active .pkg-radio::after {
            content: ''; position: absolute; inset: 3px;
            background: var(--primary); border-radius: 50%;
        }

        /* Phone */
        .phone-label { font-size: .8rem; font-weight: 700; color: #0f0f0f; margin-bottom: 7px; display: block; }
        .phone-row {
            display: flex; align-items: center;
            border: 2px solid var(--border); border-radius: 14px; overflow: hidden;
            margin-bottom: 6px; transition: .2s; background: #fafafa;
        }
        .phone-row:focus-within { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 3px rgba(22,163,74,.08); }
        .phone-flag {
            padding: 14px 12px; background: #fff; border-right: 1px solid var(--border);
            font-size: .9rem; font-weight: 700; white-space: nowrap; flex-shrink: 0;
        }
        .phone-input {
            flex: 1; padding: 14px 12px; border: none; outline: none;
            font-size: 1.05rem; font-family: 'Roboto', sans-serif; background: transparent; letter-spacing: .5px;
        }
        .phone-hint { font-size: .72rem; color: #999; margin-bottom: 4px; padding: 0 2px; }
        .phone-err {
            font-size: .77rem; color: #dc2626;
            background: #fff1f2; border: 1px solid #fecdd3;
            padding: 8px 12px; border-radius: 10px;
            display: none; margin-bottom: 12px; font-weight: 600;
        }
        .btn-pay {
            width: 100%; padding: 16px; border-radius: 40px;
            font-size: 1rem; font-weight: 800;
            background: linear-gradient(135deg, var(--primary), color-mix(in srgb, var(--primary) 60%, #000));
            color: #fff; border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: .5rem;
            transition: .2s; box-shadow: 0 6px 18px rgba(0,0,0,.2);
        }
        .btn-pay:hover:not(:disabled) { transform: translateY(-2px); opacity: .92; }
        .btn-pay:disabled { opacity: .6; cursor: not-allowed; transform: none; }
        .btn-cancel {
            width: 100%; margin-top: 10px; padding: 13px;
            border-radius: 40px; font-size: .9rem; font-weight: 700;
            color: var(--muted); border: 2px solid var(--border);
            background: transparent; display: flex; align-items: center; justify-content: center; gap: 6px;
            transition: .2s;
        }
        .btn-cancel:hover { border-color: #aaa; color: var(--text); }

        /* Waiting state */
        .wait-view { display: none; text-align: center; padding: 8px 22px 4px; }
        .wait-view.show { display: block; }
        .wait-icon { font-size: 3rem; margin-bottom: 12px; animation: pulse 1.5s infinite; }
        .wait-title { font-size: 1.1rem; font-weight: 800; margin-bottom: 8px; }
        .wait-sub { font-size: .86rem; color: var(--muted); line-height: 1.7; }
        .wait-steps {
            text-align: left; background: #f8f8f8; border-radius: 14px;
            padding: 14px 16px; margin-top: 16px;
        }
        .wait-step {
            display: flex; align-items: flex-start; gap: 10px;
            font-size: .8rem; color: #444; margin-bottom: 10px;
        }
        .wait-step:last-child { margin-bottom: 0; }
        .wn {
            flex-shrink: 0; width: 22px; height: 22px; border-radius: 50%;
            background: var(--primary); color: #fff;
            font-size: .72rem; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
        }
        .wait-cancel-link {
            margin-top: 16px; font-size: .8rem; color: var(--muted);
            font-weight: 700; cursor: pointer; text-decoration: underline; display: inline-block;
        }

        /* Toast */
        .toast {
            position: fixed; bottom: 80px; left: 50%;
            transform: translateX(-50%) translateY(200px);
            opacity: 0; z-index: 9999; background: #111; color: #fff;
            border-radius: 30px; padding: .7rem 1.4rem;
            font-size: .83rem; font-weight: 600; white-space: nowrap;
            transition: transform .3s, opacity .3s; pointer-events: none;
        }
        .toast.show  { transform: translateX(-50%) translateY(0); opacity: 1; }
        .toast.ok  { background: #16a34a; }
        .toast.err { background: #dc2626; }

        /* Animations */
        @keyframes fadeIn { from { opacity: 0 } to { opacity: 1 } }
        @keyframes popIn  { from { opacity: 0; transform: scale(.88) translateY(20px) } to { opacity: 1; transform: scale(1) translateY(0) } }
        @keyframes pulse  { 0%,100% { transform: scale(1) } 50% { transform: scale(1.1) } }
        @keyframes spin   { to { transform: rotate(360deg) } }

        .spin-icon {
            display: inline-block; width: 16px; height: 16px;
            border: 2.5px solid rgba(255,255,255,.3); border-top-color: #fff;
            border-radius: 50%; animation: spin .65s linear infinite;
        }

        /* ── No videos ── */
        .empty-state { text-align: center; padding: 60px 24px; color: var(--muted); grid-column: 1/-1; }
        .empty-state i { font-size: 3.5rem; margin-bottom: 16px; display: block; opacity: .3; }

        /* ── Search ── */
        #searchInput:focus { outline: none; }

        /* ── Responsive ── */
        @media (max-width: 900px) {
            .yt-wrap { padding: 60px 8px 8px; }
            .more-section { padding: 0 8px 20px; }
            .vid-row-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .rel-section { padding: 0 8px 8px; }
            .header-search { display: none; }
        }
        @media (max-width: 600px) {
            .rel-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
            .vid-row-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
            .vid-main-title { font-size: 1rem; }
        }
    </style>
</head>
<body>

<!-- ── Header ── -->
<header class="yt-header">
    <div class="header-logo">
        <i class="fas fa-play-circle yt-icon"></i>
        <span><?= htmlspecialchars($landing_settings['site_name']) ?></span>
    </div>
    <div class="header-search">
        <input type="text" id="searchInput" placeholder="Tafuta video..." oninput="filterVideos(this.value)">
        <button class="search-btn"><i class="fas fa-search"></i></button>
    </div>
    <div class="header-actions">
        <button title="Notifications"><i class="fas fa-bell"></i></button>
    </div>
</header>

<!-- ── Main Player + Info ── -->
<div class="yt-wrap">
<?php if ($hero_video): ?>
    <!-- Player -->
    <div class="player-wrapper">
        <div id="playerBox">
            <div id="playerPoster" onclick="startPreview()">
                <img src="<?= htmlspecialchars($hero_video['thumbnail_url'] ?? '') ?>"
                     alt="<?= htmlspecialchars($hero_video['title']) ?>"
                     onerror="this.style.display='none'">
                <div class="main-play-wrap">
                    <div class="main-play-btn"><i class="fas fa-play"></i></div>
                </div>
                <div class="main-play-badge">
                    <i class="fas fa-gift"></i> Bofya kuanza — Preview ya bure 5 sek
                </div>
            </div>
        </div>
        <!-- Gate overlay (shown after preview) -->
        <div class="gate-overlay" id="gateOverlay">
            <div class="gate-lock">🔒</div>
            <div class="gate-title">Preview Imisha!</div>
            <div class="gate-sub">Lipia <strong id="gateAmt"><?= number_format($hero_video['price'] ?? 1000) ?></strong> TZS kuendelea kutazama</div>
            <button class="gate-pay-btn" onclick="openPayModal()">
                <i class="fas fa-lock-open"></i> Lipia Sasa
            </button>
        </div>
    </div>

    <!-- Video info -->
    <div class="vid-info">
        <h1 class="vid-main-title" id="vidTitle"><?= htmlspecialchars($hero_video['title']) ?></h1>
        <div class="vid-meta-row">
            <span class="vid-views" id="vidViews"><?= number_format($hero_video['views'] ?? 0) ?> maoni · <?= htmlspecialchars($hero_video['created_at'] ?? '') ?></span>
            <div class="vid-actions">
                <button class="action-btn" id="likeBtn" onclick="this.classList.toggle('liked')">
                    <i class="fas fa-thumbs-up"></i> <span><?= number_format(($hero_video['views'] ?? 0) * 0.04) ?></span>
                </button>
                <button class="action-btn" onclick="showToast('Link imenakiliwa!','ok')">
                    <i class="fas fa-share"></i> Share
                </button>
            </div>
        </div>
        <div class="channel-row">
            <div class="channel-avatar" id="channelAvatar">
                <?= mb_strtoupper(mb_substr($landing_settings['site_name'], 0, 1)) ?>
            </div>
            <div>
                <div class="channel-name"><?= htmlspecialchars($landing_settings['site_name']) ?></div>
                <div class="channel-sub"><?= htmlspecialchars($landing_settings['hero_description']) ?></div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="empty-state"><i class="fas fa-video-slash"></i><p>Hakuna video zilizopakiwa bado.</p></div>
<?php endif; ?>
</div>

<!-- ── Related Videos ── -->
<?php if (!empty($user_videos)): ?>
<div class="rel-section">
    <div class="section-heading">Videos Nyingine</div>
    <div class="rel-grid" id="relGrid">
        <?php foreach (array_slice($user_videos, 1, 6) as $v): ?>
        <div class="vid-card"
             onclick="switchVideo('<?= htmlspecialchars((string)$v['id']) ?>','<?= htmlspecialchars($v['slug']) ?>',<?= (int)($v['price'] ?? 1000) ?>,'<?= htmlspecialchars(addslashes($v['title'])) ?>','<?= htmlspecialchars($v['thumbnail_url'] ?? '') ?>','<?= htmlspecialchars($v['video_url'] ?? '') ?>')"
             data-title="<?= htmlspecialchars($v['title']) ?>">
            <div class="vid-card-thumb">
                <img src="<?= htmlspecialchars($v['thumbnail_url'] ?? 'assets/images/default_thumb.jpg') ?>" alt="" loading="lazy">
                <div class="vid-card-play-icon"><div class="pb-circle"><i class="fas fa-play"></i></div></div>
            </div>
            <div class="vid-card-info">
                <div class="vid-card-title"><?= htmlspecialchars($v['title']) ?></div>
                <div class="vid-card-meta"><?= number_format($v['views'] ?? 0) ?> maoni</div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ── More Videos (paginated) ── -->
<div class="more-section" id="moreSection">
    <div class="section-heading">Videos Zaidi</div>
    <div class="vid-row-grid" id="vidRowGrid"></div>
    <div class="row-nav">
        <button class="nav-btn" id="prevBtn" onclick="changePage(-1)" disabled>
            <i class="fas fa-arrow-left"></i> Nyuma
        </button>
        <span class="page-info" id="pageInfo"></span>
        <button class="nav-btn" id="nextBtn" onclick="changePage(1)">
            Mbele <i class="fas fa-arrow-right"></i>
        </button>
    </div>
</div>
<?php endif; ?>

<!-- ── Payment Modal ── -->
<div class="pay-modal" id="payModal">
    <div class="pay-sheet">
        <button class="pay-close" onclick="closePayModal()" aria-label="Funga">&times;</button>

        <!-- Payment form -->
        <div id="payForm">
            <div class="pay-hero">
                <div class="pay-avatar"><i class="fas fa-crown"></i></div>
                <div class="pay-name"><?= htmlspecialchars($landing_settings['site_name']) ?></div>
                <div class="pay-sub" id="paySubLabel">Lipia Kutazama Video</div>
            </div>
            <div class="pay-amount-box">
                <div class="amt-label">Lipa mara moja</div>
                <div class="amt" id="payAmt">1,000 <span class="cur">TZS</span></div>
            </div>
            <div class="pay-body">
                <div class="vid-tag-label" id="payVidLabel"></div>

                <!-- Package list (if any) -->
                <?php if (!empty($packages)): ?>
                <div class="pkg-list" id="pkgList">
                    <?php foreach ($packages as $i => $pkg): ?>
                    <div class="pkg-item <?= $i === 0 ? 'active' : '' ?>"
                         onclick="selectPkg('<?= htmlspecialchars((string)$pkg['id']) ?>', <?= (int)$pkg['price'] ?>, this)">
                        <div>
                            <span class="pkg-item-name"><?= htmlspecialchars($pkg['name']) ?></span>
                            <span class="pkg-item-sub"><?= (int)($pkg['duration_days'] ?? 1) ?> Days Access</span>
                        </div>
                        <div style="display:flex;align-items:center;">
                            <span class="pkg-item-price"><?= number_format($pkg['price']) ?>/=</span>
                            <div class="pkg-radio"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="selectedPkgId" value="<?= htmlspecialchars((string)$packages[0]['id']) ?>">
                <?php else: ?>
                <input type="hidden" id="selectedPkgId" value="">
                <?php endif; ?>

                <input type="hidden" id="selectedVidId" value="<?= htmlspecialchars((string)($hero_video['id'] ?? '')) ?>">
                <input type="hidden" id="payType" value="video">

                <label class="phone-label" for="phoneInput">Namba ya Simu (M-Pesa / Tigo / Airtel / Halotel)</label>
                <div class="phone-row">
                    <div class="phone-flag">🇹🇿 +255</div>
                    <input class="phone-input" type="tel" id="phoneInput"
                           placeholder="07xx au 06xx" maxlength="12" inputmode="numeric">
                </div>
                <div class="phone-hint">Tumia 07xx au 06xx (nambari 10)</div>
                <div class="phone-err" id="phoneErr"><i class="fas fa-exclamation-circle"></i> Namba si sahihi. Jaribu Tena</div>

                <button class="btn-pay" id="btnPay" onclick="processPayment()">
                    <i class="fas fa-mobile-alt"></i>
                    <span id="btnLabel"><?= htmlspecialchars($landing_settings['cta_text']) ?></span>
                </button>
                <button class="btn-cancel" onclick="closePayModal()">
                    <i class="fas fa-times-circle"></i> Ghairi
                </button>
            </div>
        </div>

        <!-- Waiting state -->
        <div class="wait-view" id="waitView">
            <div class="wait-icon">📲</div>
            <div class="wait-title">USSD Imetumwa!</div>
            <div class="wait-sub">Ombi la malipo limetumwa kwenye simu yako...</div>
            <div class="wait-steps">
                <div class="wait-step"><span class="wn">1</span><span>Angalia ujumbe wa USSD kwenye simu yako</span></div>
                <div class="wait-step"><span class="wn">2</span><span>Ingiza <strong>PIN</strong> yako ya M-Pesa/Tigo/Airtel</span></div>
                <div class="wait-step"><span class="wn">3</span><span>Video itafunguliwa kiotomatiki ⚡ — usifunge dirisha</span></div>
            </div>
            <span class="wait-cancel-link" onclick="closePayModal()">Ghairi</span>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
// ── Config from PHP ──────────────────────────────────────────────────────────
const BASE_URL    = '<?= BASE_URL ?>';
const CREATOR_ID  = '<?= $creator_id ?>';
const PREVIEW_S   = 5;
const VIDS_PER_PG = 10;
const CSRF_TOKEN  = 'flGU8LM8PkkIaCNbNsRw3gKfz3PmMwaDMk1VFj9B';

// All videos from PHP (used for pagination & search)
const ALL_VIDS = <?php
    $js_vids = [];
    foreach ($user_videos as $v) {
        $js_vids[] = [
            'id'        => (string)$v['id'],
            'title'     => $v['title'] ?? '',
            'slug'      => $v['slug'] ?? '',
            'price'     => (int)($v['price'] ?? 1000),
            'thumb'     => $v['thumbnail_url'] ?? '',
            'preview'   => $v['video_url'] ?? '',
            'views'     => (int)($v['views'] ?? 0),
            'created'   => $v['created_at'] ?? '',
        ];
    }
    echo json_encode($js_vids, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
?>;

// ── State ────────────────────────────────────────────────────────────────────
let currentVid   = ALL_VIDS[0] || null;
let activeHls    = null;
let previewTimer = null;
let pollInterval = null;
let relPage      = 0;
let filteredVids = ALL_VIDS.slice();

// ── Helpers ──────────────────────────────────────────────────────────────────
function fmtViews(n) {
    n = parseInt(n) || 0;
    if (n >= 1e6) return (n/1e6).toFixed(1)+'M';
    if (n >= 1e3) return (n/1e3).toFixed(1)+'K';
    return n.toString();
}
function escHtml(s) {
    return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function fetchAPI(url, opts) {
    const res  = await fetch(url, opts);
    const text = await res.text();
    try { return JSON.parse(text); }
    catch(e) { throw new Error('Server error ('+res.status+'): '+text.substring(0,200)); }
}

function showToast(msg, type) {
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.className   = 'toast ' + (type||'');
    void el.offsetWidth;
    el.classList.add('show');
    setTimeout(() => el.classList.remove('show'), 3500);
}

function trackEvent(videoId, type) {
    fetch(BASE_URL + '/api/track', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ video_id: videoId, event_type: type }),
        keepalive: true
    }).catch(() => {});
}

// ── Preview Player ────────────────────────────────────────────────────────────
function destroyPreview() {
    if (activeHls)    { activeHls.destroy(); activeHls = null; }
    if (previewTimer) { clearTimeout(previewTimer); previewTimer = null; }
    const box = document.getElementById('playerBox');
    const vid = box.querySelector('video');
    if (vid) { vid.pause(); vid.removeAttribute('src'); vid.load(); }
}

function startPreview() {
    if (!currentVid) return;
    trackEvent(currentVid.id, 'view');
    const box   = document.getElementById('playerBox');
    const src   = currentVid.preview && currentVid.preview.endsWith('.mp4')
                ? currentVid.preview
                : BASE_URL + '/preview/video/' + currentVid.slug + '/index.m3u8';

    destroyPreview();
    box.innerHTML = '<video id="previewVid" playsinline autoplay controls style="width:100%;height:100%;display:block;background:#000"></video>';
    const vid = document.getElementById('previewVid');

    function onPlayStart() {
        vid.removeEventListener('playing', onPlayStart);
        previewTimer = setTimeout(() => {
            destroyPreview();
            document.getElementById('gateOverlay').classList.add('show');
            openPayModal();
        }, PREVIEW_S * 1000);
    }
    vid.addEventListener('playing', onPlayStart);
    vid.addEventListener('pause',   () => { if (previewTimer) { clearTimeout(previewTimer); previewTimer = null; }});

    if (src.endsWith('.mp4')) {
        vid.src = src;
        vid.play().catch(() => {});
    } else if (typeof Hls !== 'undefined' && Hls.isSupported()) {
        activeHls = new Hls({ maxBufferLength:6, maxMaxBufferLength:6, startLevel:0,
                               manifestLoadingTimeOut:3000, manifestLoadingMaxRetry:1 });
        activeHls.loadSource(src);
        activeHls.attachMedia(vid);
        activeHls.on(Hls.Events.MANIFEST_PARSED, () => vid.play().catch(()=>{}));
        activeHls.on(Hls.Events.ERROR, (e,d) => { if (d.fatal) openPayModal(); });
    } else if (vid.canPlayType('application/vnd.apple.mpegurl')) {
        vid.src = src;
        vid.play().catch(() => {});
    } else {
        openPayModal();
    }
}

// ── Switch Video (on card click) ─────────────────────────────────────────────
function switchVideo(id, slug, price, title, thumb, preview) {
    currentVid = { id, slug, price, title, thumb, preview };
    destroyPreview();
    document.getElementById('gateOverlay').classList.remove('show');

    // Update info panel
    document.getElementById('vidTitle').textContent = title;

    // Rebuild poster
    const box = document.getElementById('playerBox');
    box.innerHTML =
        '<div id="playerPoster" onclick="startPreview()">' +
            (thumb ? '<img src="'+escHtml(thumb)+'" style="width:100%;height:100%;object-fit:cover;display:block" onerror="this.style.display=\'none\'">' : '') +
            '<div class="main-play-wrap"><div class="main-play-btn"><i class="fas fa-play"></i></div></div>' +
            '<div class="main-play-badge"><i class="fas fa-gift"></i> Bofya kuanza — Preview ya bure 5 sek</div>' +
        '</div>';

    // Update gate amount
    const ga = document.getElementById('gateAmt');
    if (ga) ga.textContent = parseInt(price||1000).toLocaleString();

    trackEvent(id, 'view');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ── Payment Modal ────────────────────────────────────────────────────────────
function openPayModal() {
    if (!currentVid) return;
    trackEvent(currentVid.id, 'click_cta');

    // Reset form
    const phone = document.getElementById('phoneInput');
    if (phone) phone.value = '';
    document.getElementById('phoneErr').style.display = 'none';
    resetPayBtn();
    document.getElementById('payForm').style.display = 'block';
    document.getElementById('waitView').classList.remove('show');

    // Update modal info
    const price = currentVid.price || 1000;
    const hasPkg = document.getElementById('pkgList');

    document.getElementById('payType').value      = hasPkg ? 'package' : 'video';
    document.getElementById('selectedVidId').value = currentVid.id;
    document.getElementById('paySubLabel').textContent = hasPkg
        ? 'Chagua Kifurushi chako'
        : 'Lipia Kutazama Video Hii';

    const vidLabel = document.getElementById('payVidLabel');
    if (vidLabel) vidLabel.textContent = currentVid.title
        ? '▶ ' + currentVid.title.substring(0, 45) + (currentVid.title.length > 45 ? '…' : '')
        : '';

    if (!hasPkg) {
        document.getElementById('payAmt').innerHTML = parseInt(price).toLocaleString() + ' <span class="cur">TZS</span>';
    }

    document.getElementById('payModal').classList.add('open');
}

function closePayModal() {
    document.getElementById('payModal').classList.remove('open');
    if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
}

function selectPkg(id, price, el) {
    document.querySelectorAll('.pkg-item').forEach(i => i.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('selectedPkgId').value = id;
    document.getElementById('payAmt').innerHTML = parseInt(price).toLocaleString() + ' <span class="cur">TZS</span>';
    document.getElementById('payType').value = 'package';
}

function resetPayBtn() {
    const btn = document.getElementById('btnPay');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-mobile-alt"></i> <span id="btnLabel"><?= htmlspecialchars(addslashes($landing_settings['cta_text'])) ?></span>';
}

// ── Process payment ──────────────────────────────────────────────────────────
async function processPayment() {
    const rawPhone = document.getElementById('phoneInput').value.trim();
    const errEl    = document.getElementById('phoneErr');
    errEl.style.display = 'none';

    // Validate: accept 07xx or 06xx (10 digits)
    if (!/^0[67][0-9]{8}$/.test(rawPhone)) {
        errEl.style.display = 'block';
        document.getElementById('phoneInput').focus();
        return;
    }

    const type   = document.getElementById('payType').value;
    const vidId  = document.getElementById('selectedVidId').value;
    const pkgId  = document.getElementById('selectedPkgId').value;

    const btn = document.getElementById('btnPay');
    btn.disabled  = true;
    btn.innerHTML = '<span class="spin-icon"></span> INAWASILIANA...';

    let url, payload;
    if (type === 'package' && pkgId) {
        url     = BASE_URL + '/api/process_package.php';
        payload = { package_id: pkgId, phone: rawPhone };
    } else {
        url     = BASE_URL + '/api/process_payment.php';
        payload = { video_id: vidId, phone: rawPhone };
    }

    try {
        const data = await fetchAPI(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify(payload)
        });

        if (data.status === 'success') {
            document.getElementById('payForm').style.display = 'none';
            document.getElementById('waitView').classList.add('show');
            showToast('Ombi la malipo limetumwa!', 'ok');
            startPolling(data.tranID, type, vidId);
        } else {
            errEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (data.message || 'Malipo yameshindwa. Jaribu tena.');
            errEl.style.display = 'block';
            resetPayBtn();
        }
    } catch(e) {
        errEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> Hitilafu ya mtandao. Jaribu tena.';
        errEl.style.display = 'block';
        resetPayBtn();
    }
}

// ── Poll payment status ──────────────────────────────────────────────────────
function startPolling(txId, type, vidId) {
    if (pollInterval) clearInterval(pollInterval);
    let polls = 0;
    pollInterval = setInterval(async () => {
        polls++;
        if (polls > 48) { // 2.4 min max
            clearInterval(pollInterval);
            showToast('Muda umekwisha. Jaribu tena.', 'err');
            closePayModal();
            return;
        }
        try {
            const data = await fetchAPI(BASE_URL + '/api/check_payment.php?tranid=' + encodeURIComponent(txId));
            const s = (data.payment_status || data.status || '').toLowerCase();

            if (s === 'completed' || s === 'success') {
                clearInterval(pollInterval);
                showToast('Malipo yamepokelewa! 🎉', 'ok');

                // Set 24-hour access cookies
                const exp = new Date(Date.now() + 86400000).toUTCString();
                if (type === 'video') {
                    document.cookie = 'sf_video_' + vidId + '=' + encodeURIComponent(txId) + '; expires=' + exp + '; path=/; SameSite=Lax';
                } else {
                    document.cookie = 'sf_channel_' + CREATOR_ID + '=' + encodeURIComponent(txId) + '; expires=' + exp + '; path=/; SameSite=Lax';
                }
                document.cookie = 'sf_pass_' + CREATOR_ID + '=' + encodeURIComponent(txId) + '; expires=' + exp + '; path=/; SameSite=Lax';

                // Redirect
                const wid = data.video_id || (type === 'video' ? vidId : null);
                if (data.monetization_mode === 'channel') {
                    location.href = BASE_URL + '/streaming.php?creator_id=' + CREATOR_ID;
                } else if (wid) {
                    location.href = BASE_URL + '/watch.php?id=' + wid;
                } else if (data.global_redirect_url) {
                    location.href = data.global_redirect_url;
                } else {
                    location.href = BASE_URL + '/streaming.php?creator_id=' + CREATOR_ID;
                }
            } else if (s === 'failed' || s === 'cancelled') {
                clearInterval(pollInterval);
                showToast('Malipo yameshindwa. Jaribu tena.', 'err');
                document.getElementById('waitView').classList.remove('show');
                document.getElementById('payForm').style.display = 'block';
                resetPayBtn();
            }
        } catch(e) { console.error('Poll error:', e); }
    }, 3000);
}

// ── More Videos Pagination ───────────────────────────────────────────────────
function renderPage() {
    const start = relPage * VIDS_PER_PG;
    const page  = filteredVids.slice(start, start + VIDS_PER_PG);
    const grid  = document.getElementById('vidRowGrid');
    if (!grid) return;

    if (!page.length) {
        grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1"><i class="fas fa-search"></i><p>Hakuna video zilizopatikana.</p></div>';
    } else {
        grid.innerHTML = page.map(v => `
            <div class="vid-card" onclick="switchVideo('${escHtml(v.id)}','${escHtml(v.slug)}',${v.price},'${escHtml(v.title).replace(/'/g,"\\'")}','${escHtml(v.thumb)}','${escHtml(v.preview)}')" data-title="${escHtml(v.title)}">
                <div class="vid-card-thumb">
                    <img src="${escHtml(v.thumb)}" alt="" loading="lazy" onerror="this.src=''">
                    <div class="vid-card-play-icon"><div class="pb-circle"><i class="fas fa-play"></i></div></div>
                </div>
                <div class="vid-card-info">
                    <div class="vid-card-title">${escHtml(v.title)}</div>
                    <div class="vid-card-meta">${fmtViews(v.views)} maoni · ${escHtml(v.created)}</div>
                </div>
            </div>`).join('');
    }

    const total = Math.ceil(filteredVids.length / VIDS_PER_PG) || 1;
    document.getElementById('pageInfo').textContent  = 'Ukurasa ' + (relPage+1) + ' / ' + total;
    document.getElementById('prevBtn').disabled      = relPage === 0;
    document.getElementById('nextBtn').disabled      = relPage >= total - 1;
}

function changePage(dir) {
    relPage = Math.max(0, relPage + dir);
    renderPage();
    document.getElementById('moreSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ── Search ───────────────────────────────────────────────────────────────────
function filterVideos(q) {
    q = (q || '').toLowerCase();
    filteredVids = q ? ALL_VIDS.filter(v => v.title.toLowerCase().includes(q)) : ALL_VIDS.slice();
    relPage = 0;
    renderPage();
}

// ── Init ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    renderPage();

    // Close modal on backdrop click
    document.getElementById('payModal').addEventListener('click', function(e) {
        if (e.target === this) closePayModal();
    });

    // Keyboard ESC
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closePayModal(); });
});
</script>

</body>
</html>
