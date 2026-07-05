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
        'site_name' => $platform_name,
        'cta_text' => 'ANZA SASA',
        'primary_color' => '#e50914',
        'secondary_color' => '#fbbf24',
        'bg_color' => '#0a0a0a',
        'hero_title' => 'Karibu ' . $platform_name . ' Streaming',
        'hero_description' => 'Jifunze kidijitali na uangalie video bora zaidi hapa.',
        'hero_image' => 'assets/defaults/landing-bg.jpg',
        'favicon' => null
    ];
}

// If site_name is empty/null, fallback to the admin site name
if (empty($landing_settings['site_name'])) {
    $landing_settings['site_name'] = $platform_name;
}
if (empty($landing_settings['hero_title'])) {
    $landing_settings['hero_title'] = 'Karibu ' . $platform_name . ' Streaming';
}
if (empty($landing_settings['cta_text'])) {
    $landing_settings['cta_text'] = 'ANZA SASA';
}
if (empty($landing_settings['primary_color'])) {
    $landing_settings['primary_color'] = '#e50914';
}
if (empty($landing_settings['secondary_color'])) {
    $landing_settings['secondary_color'] = '#fbbf24';
}
if (empty($landing_settings['bg_color'])) {
    $landing_settings['bg_color'] = '#0a0a0a';
}
if (empty($landing_settings['hero_description'])) {
    $landing_settings['hero_description'] = 'Jifunze kidijitali na uangalie video bora zaidi hapa.';
}
if (empty($landing_settings['hero_image'])) {
    $landing_settings['hero_image'] = 'assets/defaults/landing-bg.jpg';
}

// If the hero image starts with "assets/", it's relative to the site root, so we should prefix it with BASE_URL
$hero_bg_image = $landing_settings['hero_image'];
if (strpos($hero_bg_image, 'http://') !== 0 && strpos($hero_bg_image, 'https://') !== 0 && strpos($hero_bg_image, '/') !== 0) {
    $hero_bg_image = BASE_URL . '/' . $hero_bg_image;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($landing_settings['site_name']) ?> | Premium Streaming</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <style>
        :root {
            --primary: <?= htmlspecialchars($landing_settings['primary_color']) ?>;
            --primary-glow: <?= htmlspecialchars($landing_settings['primary_color']) ?>66;
            --bg: <?= htmlspecialchars($landing_settings['bg_color']) ?>;
            --card-bg: #161616;
            --text-main: #ffffff;
            --text-dim: #9ca3af;
            --premium: <?= htmlspecialchars($landing_settings['secondary_color']) ?>;
            --glass: rgba(255, 255, 255, 0.03);
            --border: rgba(255, 255, 255, 0.08);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            color: var(--text-main);
            line-height: 1.6;
            overflow-x: hidden;
        }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }

        /* Navbar */
        .navbar {
            position: fixed;
            top: 0; width: 100%; z-index: 1000;
            padding: 15px 6%;
            display: flex; align-items: center; justify-content: space-between;
            background: rgba(10, 10, 10, 0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
        }

        .logo {
            font-size: 1.5rem; font-weight: 800; letter-spacing: -1px;
            color: var(--primary); text-decoration: none; text-transform: uppercase;
        }

        /* Hero */
        .hero {
            height: 80vh;
            display: flex; align-items: center; padding: 0 6%;
            background: linear-gradient(to right, var(--bg) 30%, transparent 100%),
                        linear-gradient(to top, var(--bg) 5%, transparent 30%),
                        url('<?= $hero_bg_image ?>') center/cover;
        }

        .hero-content { max-width: 600px; z-index: 2; }
        .hero-tag {
            display: inline-block; background: var(--primary); padding: 4px 12px;
            border-radius: 6px; font-size: 0.7rem; font-weight: 800;
            margin-bottom: 15px; text-transform: uppercase;
        }
        .hero-title {
            font-size: clamp(2.2rem, 7vw, 4rem); line-height: 1.1; font-weight: 800;
            margin-bottom: 20px; color: #fff;
        }

        /* Buttons */
        .btn {
            padding: 16px 32px; border-radius: 14px; font-weight: 700;
            border: none; cursor: pointer; display: inline-flex;
            align-items: center; gap: 10px; transition: 0.3s;
            text-decoration: none; font-size: 1rem;
        }
        .btn-play { background: #fff; color: #000; }
        .btn-play:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(255,255,255,0.1); }
        .btn-full { width: 100%; background: var(--primary); color: #fff; justify-content: center; }

        /* Grid & Cards */
        .section-container { padding: 40px 6%; }
        .section-title { font-size: 1.3rem; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; }
        .movie-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 20px;
        }
        .movie-card {
            background: var(--card-bg); border-radius: 18px; overflow: hidden;
            transition: 0.4s; cursor: pointer; border: 1px solid var(--border);
            position: relative;
        }
        .movie-card:hover { transform: translateY(-8px); border-color: #444; }

        .card-thumb { aspect-ratio: 2/3; position: relative; overflow: hidden; }
        .card-thumb img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
        .movie-card:hover .card-thumb img { transform: scale(1.08); }

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

        .badge {
            position: absolute; top: 10px; left: 10px; padding: 4px 8px;
            border-radius: 6px; font-size: 0.65rem; font-weight: 800; z-index: 4;
            background: var(--primary);
        }
        .price-tag {
            position: absolute; bottom: 10px; right: 10px;
            background: rgba(0,0,0,0.8); color: var(--premium);
            border: 1px solid var(--premium); padding: 3px 8px;
            border-radius: 6px; font-size: 0.7rem; font-weight: 700; z-index: 4;
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
            padding-bottom: 12px;
            background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, transparent 60%);
        }

        .preview-bar-wrap {
            width: calc(100% - 20px);
            height: 3px;
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        .preview-bar {
            height: 100%;
            background: var(--primary);
            width: 0%;
            border-radius: 10px;
            transition: width linear;
        }

        .preview-label {
            font-size: 0.65rem;
            font-weight: 800;
            color: rgba(255,255,255,0.8);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .preview-playing .preview-overlay { display: flex; }
        .preview-playing .card-preview-video { display: block; }
        .preview-playing .card-thumb > img { opacity: 0; }
        .preview-playing .badge { display: none; }
        .preview-playing .price-tag { display: none; }

        .card-info { padding: 12px; }
        .card-info h3 { font-size: 0.95rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* Modals */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.8);
            backdrop-filter: blur(15px) saturate(160%);
            display: none; justify-content: center; align-items: center;
            z-index: 2000; padding: 20px;
        }
        .modal-content {
            background: linear-gradient(145deg, #1a1a1a, #0d0d0d);
            width: 100%; max-width: 400px; border-radius: 28px;
            padding: 40px 25px; position: relative; text-align: center;
            border: 1px solid var(--border);
        }
        .modal-header-icon {
            width: 55px; height: 55px; background: rgba(229, 9, 20, 0.1);
            color: var(--primary); border-radius: 50%; display: flex;
            align-items: center; justify-content: center; font-size: 1.4rem;
            margin: 0 auto 15px; border: 1px solid rgba(229, 9, 20, 0.2);
        }
        .close-modal {
            position: absolute; top: 20px; right: 20px; font-size: 1.2rem;
            color: var(--text-dim); cursor: pointer;
        }

        /* Package Items */
        .pkg-item {
            background: rgba(255,255,255,0.03); border: 1px solid var(--border);
            padding: 16px; border-radius: 16px; margin-bottom: 10px;
            display: flex; justify-content: space-between; align-items: center; transition: 0.3s;
        }
        .pkg-item.active { border-color: var(--primary); background: rgba(229, 9, 20, 0.1); }
        .pkg-radio { width: 18px; height: 18px; border: 2px solid #444; border-radius: 50%; position: relative; }
        .pkg-item.active .pkg-radio { border-color: var(--primary); }
        .pkg-item.active .pkg-radio::after { content: ''; position: absolute; inset: 3px; background: var(--primary); border-radius: 50%; }

        /* Inputs */
        .input-wrapper { position: relative; margin-top: 20px; }
        .input-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-dim); }
        .input-wrapper input {
            width: 100%; padding: 15px 15px 15px 45px; background: #000;
            border: 1px solid #333; border-radius: 12px; color: #fff; outline: none;
        }
        .input-wrapper input:focus { border-color: var(--primary); }

        /* Preview modal teaser */
        .preview-teaser-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(229,9,20,0.15); border: 1px solid rgba(229,9,20,0.3);
            color: var(--primary); padding: 4px 12px; border-radius: 20px;
            font-size: 0.7rem; font-weight: 800; margin-bottom: 12px;
            text-transform: uppercase;
        }

        @media (max-width: 768px) {
            .hero { height: 65vh; }
            .movie-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .section-container { padding: 30px 4%; }
        }
    
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
    
    <nav class="navbar">
        <a href="#" class="logo"><?= htmlspecialchars($landing_settings['site_name']) ?> Cinema</a>
        <div style="display: flex; gap: 12px;">
            <div style="color: var(--text-dim); font-weight: 700; font-size: 0.75rem;"><i class="fa-solid fa-earth-africa"></i> TZ</div>
            <div style="background: var(--primary); padding: 2px 8px; border-radius: 4px; font-weight: 800; font-size: 0.7rem;">18+</div>
        </div>
    </nav>

    <header class="hero">
        <div class="hero-content">
            <span class="hero-tag"><i class="fas fa-bolt"></i> Trending Now</span>
            <h1 class="hero-title"><?= htmlspecialchars($landing_settings['hero_title']) ?></h1>
            <p style="color: var(--text-dim); margin-bottom: 25px; font-size: 1rem;">
                <?= htmlspecialchars($landing_settings['hero_description']) ?>
            </p>
            <button class="btn btn-play" onclick="openPackageModal()">
                <i class="fas fa-crown" style="color: #d4af37"></i>
                <?= htmlspecialchars($landing_settings['cta_text']) ?>
            </button>
        </div>
    </header>

    <main class="section-container">
        <h2 class="section-title">
            <span style="width: 4px; height: 20px; background: var(--primary); border-radius: 10px;"></span>
            Recommended For You
        </h2>

        <div class="movie-grid">
            <?php if (!empty($user_videos)): ?>
                <?php foreach ($user_videos as $video): ?>
                    <div class="movie-card"
                         data-id="<?= htmlspecialchars((string)$video['id']) ?>"
                         data-price="<?= htmlspecialchars((string)($video['price'] ?? 1000)) ?>"
                         data-title="<?= htmlspecialchars($video['title']) ?>"
                         data-folder="<?= htmlspecialchars($video['slug']) ?>"
                         data-slug="<?= htmlspecialchars($video['slug']) ?>"
                         data-preview-url="<?= htmlspecialchars($video['video_url']) ?>"
                         data-paid="0"
                         onclick="handleCardClick(this)">

                        <div class="card-thumb">
                            <img src="<?= htmlspecialchars($video['thumbnail_url'] ?? 'assets/images/default_thumb.jpg') ?>" alt="<?= htmlspecialchars($video['title']) ?>" loading="lazy">

                            <!-- Preview element -->
                            <video class="card-preview-video" playsinline preload="none"></video>

                            <div class="preview-overlay">
                                <div class="preview-bar-wrap">
                                    <div class="preview-bar"></div>
                                </div>
                                <span class="preview-label"><i class="fas fa-play" style="font-size:0.55rem;margin-right:3px;"></i> Preview · <span class="preview-countdown">5</span>s</span>
                            </div>

                            <span class="badge"><i class="fas fa-lock"></i> PREMIUM</span>
                            <span class="price-tag">TSH <?= number_format($video['price'] ?? 1000) ?></span>
                        </div>

                        <div class="card-info">
                            <h3><?= htmlspecialchars($video['title']) ?></h3>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px; font-size: 0.7rem; color: var(--text-dim);">
                                <span><i class="far fa-eye"></i> <?= number_format($video['views'] ?? 0) ?></span>
                                <span style="color: var(--primary); font-weight: 700;">Full HD</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-videos" style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--text-dim);">
                    <i class="fas fa-video-slash" style="font-size: 3rem; margin-bottom: 15px; color: var(--primary);"></i>
                    <p style="font-size: 1.1rem; font-weight: 600; color: white;">Hakuna video zilizopakiwa bado.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    
    <div class="modal-overlay" id="packageModal">
        <div class="modal-content">
            <i class="fas fa-times close-modal" onclick="closeModals()"></i>
            <div class="modal-header-icon"><i class="fas fa-crown"></i></div>
            <h2 style="font-weight: 800;">Premium Access</h2>
            <p style="color: var(--text-dim); font-size: 0.85rem; margin: 10px 0 20px;">Angalia video zote bila kikomo.</p>

            <div id="pkgList">
                <?php
                // Fetch packages for this owner dynamically
                try {
                    $stmt = $pdo->prepare("SELECT * FROM packages WHERE user_id = ? ORDER BY price ASC");
                    $stmt->execute([$domain_owner['user_id']]);
                    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    $packages = [];
                }

                if (!empty($packages)):
                    foreach ($packages as $pkg):
                ?>
                    <div class="pkg-item" onclick="selectPkg('<?= $pkg['id'] ?>', this)">
                        <div style="text-align: left;">
                            <span style="display: block; font-weight: 700;"><?= htmlspecialchars($pkg['name']) ?></span>
                            <span style="font-size: 0.7rem; color: var(--text-dim)"><?= htmlspecialchars($pkg['duration_days'] ?? 30) ?> Days Access</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span style="font-weight: 800; color: var(--premium)"><?= number_format($pkg['price']) ?>/=</span>
                            <div class="pkg-radio"></div>
                        </div>
                    </div>
                <?php 
                    endforeach;
                else:
                ?>
                    <div class="pkg-item active" onclick="selectPkg('default', this)">
                        <div style="text-align: left;">
                            <span style="display: block; font-weight: 700;">Full Pass</span>
                            <span style="font-size: 0.7rem; color: var(--text-dim)">24 Hours Access</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span style="font-weight: 800; color: var(--premium)">1,000/=</span>
                            <div class="pkg-radio"></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div id="pkgError" class="modal-error"></div>
        <div class="input-wrapper">
                <i class="fas fa-phone"></i>
                <input type="tel" id="pkgPhone" placeholder="Namba ya Simu (07xx...)">
            </div>
            <input type="hidden" id="selectedPkgId" value="<?= !empty($packages) ? htmlspecialchars((string)$packages[0]['id']) : 'default' ?>">
            <button class="btn btn-full" id="pkgBtn" onclick="processPackagePay()" style="margin-top: 20px;">LIPA SASA</button>
        </div>
    </div>

    
    <div class="modal-overlay" id="videoModal">
        <div class="modal-content">
            <i class="fas fa-times close-modal" onclick="closeModals()"></i>
            <div class="modal-header-icon" style="color: var(--premium)"><i class="fas fa-play-circle"></i></div>
            <div class="preview-teaser-badge"><i class="fas fa-eye"></i> Preview imekwisha</div>
            <h2 id="vTitle" style="font-size: 1.1rem;"></h2>
            <div style="margin: 20px 0; background: var(--glass); padding: 15px; border-radius: 15px;">
                <span id="vPrice" style="color: var(--premium); font-weight: 800; font-size: 1.4rem;"></span>
            </div>
            <div id="vError" class="modal-error"></div>
        <div class="input-wrapper">
                <i class="fas fa-phone"></i>
                <input type="tel" id="vPhone" placeholder="Namba ya Simu">
            </div>
            <input type="hidden" id="selectedVidId">
            <button class="btn btn-full" id="vBtn" onclick="processVideoPay()" style="margin-top: 20px;">UNLOCK VIDEO</button>
            <button onclick="closeModals()" style="width:100%;margin-top:10px;padding:14px;background:transparent;border:1px solid var(--border);border-radius:14px;color:var(--text-dim);font-size:0.9rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;">
                <i class="fas fa-times-circle"></i> Ghairi
            </button>
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
            document.querySelectorAll('.modal-overlay').forEach(m => m.style.display = 'none');
            // ✅ DON'T call stopAllPreviews here — modal close should not kill preview state
            // Preview is already stopped before modal opens anyway
        };

        // ─── Preview state ────────────────────────────────────────────
        let activePreviewCard = null;
        let activeHls = null;
        let previewTimer = null;
        let previewBarInterval = null;
        let currentVideoSlug = '';
        const PREVIEW_SECONDS = 5;

        function stopAllPreviews() {
            document.querySelectorAll('.movie-card.preview-playing').forEach(card => {
                card.classList.remove('preview-playing');
                const vid = card.querySelector('.card-preview-video');
                vid.pause();
                // ✅ Properly reset video element so next HLS attach is clean
                vid.removeAttribute('src');
                vid.load();
                card.querySelector('.preview-bar').style.transition = 'none';
                card.querySelector('.preview-bar').style.width = '0%';
                const countdown = card.querySelector('.preview-countdown');
                if (countdown) countdown.textContent = PREVIEW_SECONDS;
            });
            if (activeHls) { activeHls.destroy(); activeHls = null; }
            if (previewTimer) { clearTimeout(previewTimer); previewTimer = null; }
            if (previewBarInterval) { clearInterval(previewBarInterval); previewBarInterval = null; }
            activePreviewCard = null;
        }

        function handleCardClick(card) {
            const isPaid = card.dataset.paid === '1';
            const folder = card.dataset.folder;
            currentVideoSlug = card.dataset.slug || '';

            // Paid → go straight to player
            if (isPaid) {
                const redirectUrl = folder.includes('-') && folder.length > 20 
                    ? `/player/${folder}` 
                    : `<?= BASE_URL ?>/video_view.php?v=${folder}`;
                window.location.href = redirectUrl;
                return;
            }

            // ✅ If clicking the SAME card that is currently previewing → open modal immediately
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

            // Track preview view
            const videoId = card.dataset.id;
            fetch('<?= BASE_URL ?>/api/track', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ video_id: videoId, event_type: 'view' }),
                keepalive: true
            }).catch(err => console.error(err));

            const videoEl = card.querySelector('.card-preview-video');

            // Fully reset video element before attaching new HLS instance
            videoEl.pause();
            videoEl.removeAttribute('src');
            videoEl.load();

            const previewUrl = card.dataset.previewUrl || '';
            const src = previewUrl.endsWith('.mp4') 
                ? previewUrl 
                : `/preview/video/${folder}/index.m3u8`;

            if (previewUrl.endsWith('.mp4')) {
                // Play standard MP4 natively!
                videoEl.src = src;
                videoEl.play().catch(() => {});
            } else if (Hls.isSupported()) {
                activeHls = new Hls({
                    maxBufferLength: 6,
                    maxMaxBufferLength: 6,
                    startLevel: 0,
                    capLevelToPlayerSize: false,
                    autoStartLoad: true,
                    manifestLoadingTimeOut: 3000,
                    manifestLoadingMaxRetry: 1,
                    levelLoadingTimeOut: 3000,
                });

                activeHls.loadSource(src);
                activeHls.attachMedia(videoEl);

                activeHls.on(Hls.Events.FRAG_BUFFERED, function onFirstFrag() {
                    videoEl.play().catch(() => {});
                    activeHls.off(Hls.Events.FRAG_BUFFERED, onFirstFrag);
                });

                // Fallback
                activeHls.on(Hls.Events.MANIFEST_PARSED, () => {
                    videoEl.play().catch(() => {});
                });

                activeHls.on(Hls.Events.ERROR, (event, data) => {
                    if (data.fatal) stopAllPreviews();
                });

            } else if (videoEl.canPlayType('application/vnd.apple.mpegurl')) {
                videoEl.src = src;
                videoEl.play().catch(() => {});
            }

            // ✅ Countdown starts ONLY when video is actually rendering frames
            // 'playing' fires after buffering is done and first frame is shown
            // This means the 5s timer is accurate — user sees full 5s of video
            videoEl.addEventListener('playing', function onPlaying() {
                videoEl.removeEventListener('playing', onPlaying); // fire once only
                startPreviewCountdown(card);
            });
        }

        function startPreviewCountdown(card) {
            const bar = card.querySelector('.preview-bar');
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

            // Timer starts from when video actually plays — user always gets full 5s
            previewTimer = setTimeout(() => {
                const { id, price, title, slug } = card.dataset;
                stopAllPreviews();
                currentVideoSlug = slug || '';
                openSingleVideoModal(id, price, title);
            }, PREVIEW_SECONDS * 1000);
        }

        // ─── Single video modal ───────────────────────────────────────
        function openSingleVideoModal(id, price, title) {
            // Always clear phone field and reset button state when switching videos
            var phoneEl = document.getElementById('vPhone');
            if (phoneEl) phoneEl.value = '';
            var errEl = document.getElementById('vError');
            if (errEl) errEl.style.display = 'none';
            var vBtn = document.getElementById('vBtn');
            if (vBtn) { vBtn.disabled = false; vBtn.innerHTML = 'UNLOCK VIDEO'; }

            document.getElementById('videoModal').style.display = 'flex';
            document.getElementById('vTitle').innerText = title;
            document.getElementById('vPrice').innerText = 'TSH ' + parseInt(price).toLocaleString();
            document.getElementById('selectedVidId').value = id;

            // Track click CTA
            fetch('<?= BASE_URL ?>/api/track', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ video_id: id, event_type: 'click_cta' }),
                keepalive: true
            }).catch(err => console.error(err));
        }

        // ─── Package selection ────────────────────────────────────────
        function selectPkg(id, el) {
            document.querySelectorAll('.pkg-item').forEach(i => i.classList.remove('active'));
            el.classList.add('active');
            document.getElementById('selectedPkgId').value = id;
        }

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

        // ─── Payments ─────────────────────────────────────────────────
        async function processPackagePay() {
        hideError('package');
            const id = document.getElementById('selectedPkgId').value;
            const phone = document.getElementById('pkgPhone').value;
            if (!id) return showError('package', 'Chagua kifurushi');
            if (!/^0[0-9]{9}$/.test(phone)) return showError('package', 'Namba haijakamilika');

            toggleLoading('pkgBtn', true);
            try {
                const data = await fetchAPI("<?= BASE_URL ?>/api/process_package.php", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': 'flGU8LM8PkkIaCNbNsRw3gKfz3PmMwaDMk1VFj9B' },
                    body: JSON.stringify({ package_id: id, phone })
                });
                handleResponse(data, 'pkgBtn', 'package', id);
            } catch (e) {
                toggleLoading('pkgBtn', false);
                showError('package', 'Hitilafu ya mtandao. Jaribu tena.\n\n[Error Details]:\n' + e.message);
            }
        }

        async function processVideoPay() {
        hideError('video');
            const id = document.getElementById('selectedVidId').value;
            const phone = document.getElementById('vPhone').value;
            if (!/^0[0-9]{9}$/.test(phone)) return showError('video', 'Namba haijakamilika');

            toggleLoading('vBtn', true);
            try {
                const data = await fetchAPI("<?= BASE_URL ?>/api/process_payment.php", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': 'flGU8LM8PkkIaCNbNsRw3gKfz3PmMwaDMk1VFj9B' },
                    body: JSON.stringify({ video_id: id, phone })
                });
                handleResponse(data, 'vBtn', 'video', id);
            } catch (e) {
                toggleLoading('vBtn', false);
                showError('video', 'Hitilafu ya mtandao. Jaribu tena.\n\n[Error Details]:\n' + e.message);
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
                    const s = (data.payment_status || data.status || '').toLowerCase();
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

        // ✅ Stop preview only on outside click — not on modal clicks
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
