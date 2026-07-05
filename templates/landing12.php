<?php
/**
 * templates/landing12.php
 * RahaUtamu Theme — Integrated with platform API
 * Plays video preview for 3 seconds before opening payment modal.
 */

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
        'site_name'         => 'RahaUtamu',
        'cta_text'          => 'LIPIA SASA',
        'primary_color'     => '#16a34a',
        'secondary_color'   => '#f97316',
        'bg_color'          => '#f8fafc',
        'hero_title'        => 'Video kali za wakubwa tu',
        'hero_description'  => 'Tazama preview bure, lipia kuendelea',
        'hero_image'        => 'assets/defaults/landing-bg.jpg',
        'favicon'           => null,
    ];
}

// Ensure required fields are populated
if (empty($landing_settings['site_name']))        $landing_settings['site_name']        = 'RahaUtamu';
if (empty($landing_settings['hero_title']))        $landing_settings['hero_title']       = 'Video kali za wakubwa tu';
if (empty($landing_settings['cta_text']))          $landing_settings['cta_text']         = 'LIPIA SASA';
if (empty($landing_settings['primary_color']))     $landing_settings['primary_color']    = '#16a34a';
if (empty($landing_settings['secondary_color']))   $landing_settings['secondary_color']  = '#f97316';
if (empty($landing_settings['bg_color']))          $landing_settings['bg_color']         = '#f8fafc';
if (empty($landing_settings['hero_description']))  $landing_settings['hero_description'] = 'Tazama preview bure, lipia kuendelea';
if (empty($landing_settings['hero_image']))        $landing_settings['hero_image']       = 'assets/defaults/landing-bg.jpg';

// Fetch packages for this domain owner
$packages = [];
if (isset($domain_owner['user_id'])) {
    try {
        $pkgStmt = $pdo->prepare("SELECT * FROM packages WHERE user_id = ? ORDER BY price ASC");
        $pkgStmt->execute([$domain_owner['user_id']]);
        $packages = $pkgStmt->fetchAll();
    } catch (PDOException $e) { /* silent */ }
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-WFSQPTZZLJ"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-WFSQPTZZLJ');
    </script>
    <meta charset="UTF-8">
    <meta name="referrer" content="no-referrer">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($landing_settings['site_name']) ?> | Premium Videos</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: <?= htmlspecialchars($landing_settings['bg_color']) ?>; color: #1e293b; -webkit-tap-highlight-color: transparent; overflow-x: hidden; }
        .glass-nav { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(0, 0, 0, 0.05); }
        .video-card { transition: all 0.3s ease; border: 1px solid transparent; cursor: pointer; }
        .video-card:hover { transform: translateY(-5px); border-color: rgba(22, 163, 74, 0.2); }
        
        @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
        .animate-pay { animation: slideUp 0.4s cubic-bezier(0, 0, 0.2, 1); }
        
        .loader { border-top-color: white; animation: spinner 0.8s linear infinite; }
        @keyframes spinner { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        .plan-card { background: #1e293b; border: 1px solid rgba(255,255,255,0.05); transition: all 0.2s; }
        input[name="plan"]:checked + .plan-card { background: <?= htmlspecialchars($landing_settings['primary_color']) ?>; border-color: <?= htmlspecialchars($landing_settings['primary_color']) ?>; transform: scale(1.05); }

        /* Preview player overlay */
        #previewPlayer {
            position: fixed; inset: 0; background: #000; z-index: 2000;
            display: none; align-items: center; justify-content: center;
        }
        #previewPlayer video { width: 100%; height: 100%; object-fit: contain; }
        .preview-top-bar {
            position: absolute; top: 0; left: 0; right: 0; z-index: 2001;
            padding: 16px 20px;
            background: linear-gradient(to bottom, rgba(0,0,0,0.7), transparent);
            display: flex; justify-content: space-between; align-items: center;
        }
        .preview-progress-bar { position: absolute; bottom: 0; left: 0; right: 0; height: 3px; background: rgba(255,255,255,0.15); }
        .preview-progress-fill { height: 100%; background: <?= htmlspecialchars($landing_settings['primary_color']) ?>; width: 0%; transition: width linear; }

        /* Status message styles */
        .status-toast {
            position: fixed; top: 20px; right: 20px; z-index: 3000;
            padding: 12px 20px; border-radius: 12px; font-size: 13px; font-weight: 600;
            max-width: 300px; animation: popupIn 0.4s ease;
        }
        .status-toast.success { background: #166534; color: #bbf7d0; border: 1px solid #22c55e; }
        .status-toast.error { background: #881337; color: #fecdd3; border: 1px solid #ef4444; }
        .status-toast.info { background: #1e3a8a; color: #bfdbfe; border: 1px solid #3b82f6; }

        @keyframes popupIn {
            0% { transform: translateX(100%) scale(0.5); opacity: 0; }
            100% { transform: translateX(0) scale(1); opacity: 1; }
        }
        @keyframes popupOut {
            0% { transform: translateX(0) scale(1); opacity: 1; }
            100% { transform: translateX(100%) scale(0.5); opacity: 0; }
        }
    </style>
</head>
<body class="bg-slate-50 pb-20">

    <div id="toastContainer" style="position:fixed;top:20px;right:20px;z-index:3000;display:flex;flex-direction:column;gap:8px;pointer-events:none;"></div>

    <!-- Floating Notification Banner -->
    <div id="floatingBanner" onclick="toggleModal(true)" class="fixed top-5 left-1/2 -translate-x-1/2 w-[90%] max-w-sm bg-white/95 backdrop-blur shadow-2xl rounded-full p-2 flex items-center gap-3 z-[100] border border-pink-100 cursor-pointer animate-bounce-in" style="animation: 0.5s ease-out 0s 1 normal forwards running slideDown;">
        <div class="relative">
            <img src="<?= BASE_URL ?>/assets/images/malkia.jpg" class="w-12 h-12 rounded-full object-cover border-2 border-green-500">
            <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></span>
        </div>
        <div class="flex-1">
            <h4 class="text-sm font-bold text-gray-800">Malkia</h4>
            <p class="text-xs text-gray-500">Nipigie sasa hivi nipo online...</p>
        </div>
        <div class="pr-4">
            <i class="fa-solid fa-comment-dots text-pink-500"></i>
        </div>
    </div>

    <!-- Header & Search -->
    <div class="glass-nav p-4 md:p-6 sticky top-0 z-[50]">
        <div class="max-w-7xl mx-auto flex justify-between items-center mb-4 md:mb-6">
            <h1 class="text-xl md:text-3xl font-black tracking-tighter" style="color: <?= htmlspecialchars($landing_settings['primary_color']) ?>">Raha<span style="color: <?= htmlspecialchars($landing_settings['secondary_color']) ?>">Utamu</span></h1>
            
            <div class="flex items-center gap-2">
                <button onclick="openRefreshModal()" class="text-[9px] md:text-xs font-black uppercase px-4 py-2.5 rounded-xl flex items-center gap-1.5 transition-all text-white shadow-md shadow-orange-500/20 hover:scale-[1.02] active:scale-[0.98]" style="background: linear-gradient(135deg, <?= htmlspecialchars($landing_settings['primary_color']) ?>, <?= htmlspecialchars($landing_settings['secondary_color']) ?>)">
                    <i class="fa-solid fa-arrows-rotate animate-spin-slow"></i> Refresh Malipo
                </button>
            </div>
        </div>
        <div class="max-w-3xl mx-auto">
            <div class="relative group">
                <input type="text" id="searchInput" onkeyup="searchVideos()" placeholder="Tafuta videos tamu hapa..." class="w-full bg-slate-100/80 p-3.5 md:p-4 rounded-2xl pl-10 md:pl-12 outline-none focus:ring-2 transition-all border-none text-sm focus:ring-[#16a34a]">
                <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-slate-600 text-xs md:text-base"></i>
            </div>
            
            <!-- Scrolling Marquee Banner -->
            <div class="mt-3.5 flex items-center gap-2 px-4 py-2 rounded-2xl bg-white text-xs font-semibold text-slate-600 overflow-hidden shadow-sm border border-slate-100/80">
                <span class="flex-shrink-0 flex items-center gap-1.5 font-bold uppercase tracking-wider text-[10px]" style="color: <?= htmlspecialchars($landing_settings['primary_color']) ?>">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                    </span>
                    MSAADA:
                </span>
                <marquee scrollamount="4.5" class="font-medium text-slate-700">
                    Habari ikiwa umefanya malipo na hujapatiwa huduma tafadhari bonyeza botton ya refresh ku activate account yako
                </marquee>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto">
        <!-- Featured Banner -->
        <div class="px-3 pt-3">
            <div class="w-full h-36 md:h-56 rounded-[2rem] bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 p-6 md:p-12 flex flex-col justify-center relative overflow-hidden shadow-2xl shadow-slate-900/20">
                <div class="absolute -right-20 -top-20 w-64 h-64 bg-white/5 rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute -left-20 -bottom-20 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
                
                <div class="relative z-10">
                    <p class="text-[9px] md:text-xs font-black text-orange-500 uppercase tracking-[0.2em] mb-2 md:mb-3 opacity-90">Zote mpya ziko hapa</p>
                    <h2 class="text-white text-2xl md:text-5xl font-black leading-[1.1] tracking-tight">
                        <?= htmlspecialchars($landing_settings['hero_title']) ?>
                    </h2>
                </div>
            </div>
        </div>

        <!-- Video Grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 md:gap-6 p-3 md:p-6" id="videoContainer">
            <?php if (!empty($user_videos)): ?>
                <?php $counter = 0; foreach ($user_videos as $video): 
                    $thumb = $video['thumbnail_url'] ?? 'assets/images/default_thumb.jpg';
                ?>
                <div class="bg-white rounded-[1.5rem] overflow-hidden shadow-sm video-card relative"
                     data-id="<?= htmlspecialchars((string)$video['id']) ?>"
                     data-title="<?= htmlspecialchars($video['title']) ?>"
                     data-price="<?= htmlspecialchars((string)($video['price'] ?? 1000)) ?>"
                     data-slug="<?= htmlspecialchars($video['slug']) ?>"
                     data-preview-url="<?= htmlspecialchars($video['video_url']) ?>"
                     onclick="handleVideoClick(this, <?= $counter ?>)">
                    <div class="h-44 relative overflow-hidden">
                        <img src="<?= htmlspecialchars($thumb) ?>" class="w-full h-full object-cover" loading="lazy" alt="<?= htmlspecialchars($video['title']) ?>">
                        
                        <div class="absolute top-3 right-3 backdrop-blur-md px-2 py-1 rounded-lg text-[9px] font-black text-white flex items-center gap-1 shadow-lg z-10 bg-orange-500/80">
                            <i class="fa-solid fa-crown"></i> PREMIUM
                        </div>

                        <div class="absolute inset-0 flex items-center justify-center z-20">
                            <div class="w-12 h-12 rounded-full bg-black/40 backdrop-blur-md flex items-center justify-center text-white text-xl border border-white/20">
                                <i class="fa-solid fa-lock"></i>
                            </div>
                        </div>
                    </div>

                    <div class="p-3">
                        <h3 class="text-xs font-bold text-slate-800 line-clamp-1 mb-2"><?= htmlspecialchars($video['title']) ?></h3>
                        <div class="flex justify-between items-center text-[9px] text-slate-400 font-bold uppercase">
                            <span class="flex items-center gap-1"><i class="fa-solid fa-fire text-orange-500"></i> <?= number_format($video['views'] ?? 0) ?></span>
                            <span class="bg-slate-100 px-2 py-0.5 rounded-md">TSH <?= number_format($video['price'] ?? 1000) ?></span>
                        </div>
                    </div>
                </div>
                <?php $counter++; endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-20 text-slate-500">
                    <i class="fa-solid fa-video-slash text-5xl mb-4 block opacity-30"></i>
                    <p class="text-lg font-bold text-slate-800">Hakuna video zilizopakiwa bado.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Fullscreen Preview Player -->
    <div id="previewPlayer">
        <div class="preview-top-bar">
            <button onclick="closePreview()" class="w-10 h-10 bg-white/10 backdrop-blur rounded-xl flex items-center justify-center text-white">
                <i class="fas fa-arrow-left"></i>
            </button>
            <span class="text-white/70 text-xs font-bold uppercase tracking-widest">Preview</span>
        </div>
        <video id="previewVideo" playsinline muted></video>
        <div class="preview-progress-bar"><div class="preview-progress-fill" id="previewBar"></div></div>
    </div>

    <!-- Payment Modal -->
    <div id="payModal" class="fixed inset-0 z-50 hidden bg-black/80 backdrop-blur-sm flex items-end">
        <div class="w-full bg-[#0f172a] rounded-t-[40px] p-8 animate-pay border-t border-white/5">
            <div class="w-12 h-1.5 bg-gray-700 rounded-full mx-auto mb-8"></div>
            
            <div class="text-center mb-6">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-green-500 to-green-700 flex items-center justify-center mx-auto mb-4 shadow-lg shadow-green-500/30">
                    <i class="fas fa-lock text-white text-2xl"></i>
                </div>
                <h2 class="text-2xl font-black text-white uppercase">Lipia Kifurushi</h2>
                <p id="sub-text" class="text-gray-400 text-[10px] mt-1 font-bold uppercase tracking-widest">CHAGUA KIFURUSHI UTAKACHO</p>
            </div>

            <div id="packageArea" class="grid grid-cols-3 gap-3 mb-6">
                <?php if (!empty($packages)): ?>
                    <?php foreach ($packages as $i => $pkg): ?>
                    <label class="cursor-pointer">
                        <input type="radio" name="plan" value="<?= (int)$pkg['price'] ?>" data-pkg-id="<?= htmlspecialchars((string)$pkg['id']) ?>" data-days="<?= (int)($pkg['duration_days'] ?? 1) ?>" <?= $i === 0 ? 'checked' : '' ?> class="hidden peer">
                        <div class="plan-card p-4 rounded-2xl text-center border">
                            <div class="text-[9px] text-gray-400 font-bold uppercase mb-1"><?= htmlspecialchars($pkg['name'] ?? ($pkg['duration_days'] ?? 1) . ' SIKU') ?></div>
                            <div class="text-white font-black text-sm"><?= number_format($pkg['price']) ?>/=</div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <label class="cursor-pointer col-span-3">
                        <input type="radio" name="plan" value="1000" data-pkg-id="default" data-days="1" checked class="hidden peer">
                        <div class="plan-card p-4 rounded-2xl text-center border">
                            <div class="text-[9px] text-gray-400 font-bold uppercase mb-1">STANDARD</div>
                            <div class="text-white font-black text-sm">1,000/=</div>
                        </div>
                    </label>
                <?php endif; ?>
            </div>

            <div id="phoneArea">
                <input type="tel" id="phone" placeholder="07XXXXXXXX / 06XXXXXXXX" inputmode="tel" autocomplete="tel" maxlength="16" class="w-full bg-white/5 border border-white/10 p-5 rounded-2xl font-black text-xl focus:border-green-500 outline-none text-white mb-2 text-center">
                <div id="phoneError" class="text-red-400 text-xs font-bold text-center mb-4 hidden"></div>
            </div>
            
            <button onclick="initiatePayment()" id="payBtn" class="w-full bg-green-600 py-5 rounded-2xl font-black text-lg text-white shadow-2xl shadow-green-600/40 uppercase flex items-center justify-center gap-3">
                <span id="btnLabel"><?= htmlspecialchars($landing_settings['cta_text']) ?></span>
                <div id="btnLoader" class="hidden loader w-5 h-5 border-4 border-white/20 rounded-full"></div>
            </button>
            <button onclick="resetAndClose()" class="w-full mt-4 py-4 rounded-2xl font-black uppercase tracking-widest text-sm flex items-center justify-center gap-2" style="background:transparent;border:1px solid rgba(255,255,255,0.1);color:#94a3b8;">
                <i class="fas fa-times-circle"></i> GHAIRI
            </button>

            <!-- Waiting state (hidden initially) -->
            <div id="waitingState" class="hidden text-center py-4">
                <div class="loader w-12 h-12 border-4 border-white/20 rounded-full mx-auto mb-4" style="border-top-color: #16a34a;"></div>
                <h3 class="text-white font-bold text-lg mb-2">Kamilisha Malipo</h3>
                <p class="text-gray-400 text-sm mb-4">Angalia simu yako — weka PIN kukamilisha</p>
                <div class="space-y-2 text-left max-w-xs mx-auto mb-4">
                    <div class="flex items-center gap-3 bg-white/5 p-3 rounded-xl text-sm text-gray-300">
                        <span class="text-lg">📱</span> Angalia USSD prompt
                    </div>
                    <div class="flex items-center gap-3 bg-white/5 p-3 rounded-xl text-sm text-gray-300">
                        <span class="text-lg">🔐</span> Ingiza PIN yako
                    </div>
                    <div class="flex items-center gap-3 bg-white/5 p-3 rounded-xl text-sm text-gray-300">
                        <span class="text-lg">⏳</span> Tunasubiri uthibitisho...
                    </div>
                </div>
                <button onclick="resetAndClose()" class="text-gray-500 text-[11px] font-bold uppercase tracking-widest">GHAIRI</button>
            </div>

            <!-- Success state -->
            <div id="successState" class="hidden text-center py-4">
                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-green-400 to-emerald-600 flex items-center justify-center mx-auto mb-4 shadow-lg shadow-green-500/30">
                    <i class="fas fa-check text-white text-3xl"></i>
                </div>
                <h3 class="text-green-400 font-black text-xl mb-2">Malipo Yamefanikiwa!</h3>
                <p class="text-gray-400 text-sm">Unafunguliwa sasa...</p>
            </div>

            <!-- Failed state -->
            <div id="failedState" class="hidden text-center py-4">
                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-red-400 to-pink-600 flex items-center justify-center mx-auto mb-4 shadow-lg shadow-red-500/30">
                    <i class="fas fa-times text-white text-3xl"></i>
                </div>
                <h3 class="text-red-400 font-black text-xl mb-2">Malipo Yameshindwa</h3>
                <p class="text-gray-400 text-sm mb-4">Malipo yako hayakukamilika.</p>
                <button onclick="location.reload()" class="bg-red-600 text-white px-8 py-3 rounded-xl font-bold uppercase">Jaribu Tena</button>
            </div>
        </div>
    </div>

    <script>
        const BASE = '<?= BASE_URL ?>';
        const DOMAIN_ID = typeof window.domainId !== 'undefined' ? window.domainId : 0;
        const PREVIEW_SECONDS = 3;

        let currentVideoId = null;
        let currentVideoCard = null;
        let previewTimer = null;
        let pollInterval = null;
        let pollCount = 0;
        const MAX_POLLS = 48;

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

        // ─── Phone validation ───
        function validatePhone(raw) {
            const digits = raw.replace(/\D/g, '');
            if (/^0[67][0-9]{8}$/.test(digits)) return '255' + digits.substring(1);
            if (/^255[0-9]{9}$/.test(digits)) return digits;
            if (/^[67][0-9]{8}$/.test(digits)) return '255' + digits;
            return null;
        }

        // ─── Toast messages ───
        function showToast(text, type = 'info') {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            const el = document.createElement('div');
            el.className = 'status-toast ' + type;
            el.textContent = text;
            el.style.pointerEvents = 'auto';
            container.appendChild(el);
            setTimeout(() => {
                el.style.animation = 'popupOut 0.3s ease forwards';
                setTimeout(() => el.remove(), 300);
            }, 5000);
        }

        // ─── Card Click → Preview or Modal ───
        function handleVideoClick(card, index) {
            const videoId = card.dataset.id;
            const slug = card.dataset.slug || '';
            const previewUrl = card.dataset.previewUrl || '';
            const price = parseInt(card.dataset.price) || 1000;

            currentVideoId = videoId;
            currentVideoCard = card;

            // Track view event
            fetch(BASE + '/api/track', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ video_id: videoId, event_type: 'view', domain_id: DOMAIN_ID || null }),
                keepalive: true
            }).catch(() => {});

            // Construct preview source
            const src = (previewUrl && previewUrl.endsWith('.mp4')) 
                ? previewUrl 
                : (slug ? BASE + `/preview/video/${slug}/index.m3u8` : '');

            if (src) {
                openPreview(src);
            } else {
                trackCTA();
                toggleModal(true);
            }
        }

        // ─── Fullscreen Preview Player ───
        function openPreview(url) {
            const player = document.getElementById('previewPlayer');
            const video = document.getElementById('previewVideo');
            const bar = document.getElementById('previewBar');
            const banner = document.getElementById('floatingBanner');
            if (banner) banner.style.display = 'none';

            player.style.display = 'flex';
            bar.style.transition = 'none';
            bar.style.width = '0%';

            // Determine source type
            if (url.endsWith('.mp4')) {
                video.src = url;
                attemptPreviewPlay(video, bar);
            } else if (typeof Hls !== 'undefined' && Hls.isSupported()) {
                const hls = new Hls({
                    maxBufferLength: 6, maxMaxBufferLength: 6,
                    startLevel: 0, autoStartLoad: true,
                    manifestLoadingTimeOut: 3000, manifestLoadingMaxRetry: 1,
                });
                hls.loadSource(url);
                hls.attachMedia(video);
                hls.on(Hls.Events.MANIFEST_PARSED, () => attemptPreviewPlay(video, bar));
                hls.on(Hls.Events.ERROR, (e, d) => {
                    if (d.fatal) { closePreview(); toggleModal(true); }
                });
                video._hlsInstance = hls;
            } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                video.src = url;
                attemptPreviewPlay(video, bar);
            } else {
                closePreview();
                toggleModal(true);
            }
        }

        function attemptPreviewPlay(video, bar) {
            let playPromise = video.play();
            if (playPromise !== undefined) {
                playPromise.then(() => {
                    bar.style.transitionDuration = PREVIEW_SECONDS + 's';
                    setTimeout(() => bar.style.width = '100%', 100);
                    previewTimer = setTimeout(() => {
                        closePreview();
                        trackCTA();
                        toggleModal(true);
                    }, PREVIEW_SECONDS * 1000);
                }).catch(() => {
                    closePreview();
                    toggleModal(true);
                });
            }
        }

        function closePreview() {
            clearTimeout(previewTimer);
            previewTimer = null;
            const video = document.getElementById('previewVideo');
            if (video._hlsInstance) {
                video._hlsInstance.destroy();
                video._hlsInstance = null;
            }
            video.pause();
            video.removeAttribute('src');
            video.load();
            document.getElementById('previewPlayer').style.display = 'none';
            document.getElementById('previewBar').style.width = '0%';
            const banner = document.getElementById('floatingBanner');
            if (banner && document.getElementById('payModal').classList.contains('hidden')) banner.style.display = 'flex';
        }

        // ─── Track CTA ───
        function trackCTA() {
            if (!currentVideoId) return;
            fetch(BASE + '/api/track', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ video_id: currentVideoId, event_type: 'click_cta', domain_id: DOMAIN_ID || null }),
                keepalive: true
            }).catch(() => {});
        }

        function toggleModal(show) { 
            document.getElementById('payModal').classList.toggle('hidden', !show);
            const banner = document.getElementById('floatingBanner');
            if (banner) banner.style.display = show ? 'none' : 'flex';
            if (show) {
                // Always clear phone field and errors when switching videos
                var phoneEl = document.getElementById('phone');
                if (phoneEl) phoneEl.value = '';
                var errEl = document.getElementById('phoneError');
                if (errEl) { errEl.classList.add('hidden'); errEl.textContent = ''; }
                // Reset to form state
                showFormState();
                setTimeout(() => {
                    const inp = document.getElementById('phone');
                    if (inp) inp.focus();
                }, 300);
            }
        }

        function showFormState() {
            document.getElementById('packageArea').style.display = 'grid';
            document.getElementById('phoneArea').style.display = 'block';
            document.getElementById('payBtn').style.display = 'flex';
            document.querySelector('#payModal button:last-of-type').style.display = 'block'; // Ghairi btn
            document.getElementById('waitingState').classList.add('hidden');
            document.getElementById('successState').classList.add('hidden');
            document.getElementById('failedState').classList.add('hidden');
            resetPayBtn();
        }

        function showWaitingState() {
            document.getElementById('packageArea').style.display = 'none';
            document.getElementById('phoneArea').style.display = 'none';
            document.getElementById('payBtn').style.display = 'none';
            document.querySelector('#payModal button:last-of-type').style.display = 'none';
            document.getElementById('waitingState').classList.remove('hidden');
            document.getElementById('successState').classList.add('hidden');
            document.getElementById('failedState').classList.add('hidden');
        }

        function showSuccessState() {
            document.getElementById('waitingState').classList.add('hidden');
            document.getElementById('successState').classList.remove('hidden');
        }

        function showFailedState() {
            document.getElementById('waitingState').classList.add('hidden');
            document.getElementById('failedState').classList.remove('hidden');
        }

        // ─── Payment ───
        async function initiatePayment() {
            const phoneInput = document.getElementById('phone');
            const rawPhone = phoneInput ? phoneInput.value.trim() : '';
            const errEl = document.getElementById('phoneError');
            const plan = document.querySelector('input[name="plan"]:checked');

            // Clear previous error
            if (errEl) { errEl.classList.add('hidden'); errEl.textContent = ''; }

            const phone = validatePhone(rawPhone);
            if (!phone) {
                if (errEl) {
                    errEl.textContent = 'Namba si sahihi. Mifano: 0712345678 · 255712345678';
                    errEl.classList.remove('hidden');
                }
                if (phoneInput) phoneInput.focus();
                return;
            }

            document.getElementById('btnLabel').innerText = "INAWASILIANA...";
            document.getElementById('btnLoader').classList.remove('hidden');
            document.getElementById('payBtn').disabled = true;

            const pkgId = plan ? plan.dataset.pkgId : null;
            let targetUrl = '';
            let payloadBody = {};
            let paymentType = '';
            let paymentItemId = '';

            if (pkgId && pkgId !== 'default') {
                targetUrl = BASE + '/api/process_package.php';
                payloadBody = { package_id: pkgId, phone: phone };
                paymentType = 'package';
                paymentItemId = pkgId;
            } else if (currentVideoId) {
                targetUrl = BASE + '/api/process_payment.php';
                payloadBody = { video_id: currentVideoId, phone: phone };
                paymentType = 'video';
                paymentItemId = currentVideoId;
            } else {
                targetUrl = BASE + '/api/process_package.php';
                payloadBody = { package_id: 'default', phone: phone };
                paymentType = 'package';
                paymentItemId = 'default';
            }

            try {
                const data = await fetchAPI(targetUrl, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': 'flGU8LM8PkkIaCNbNsRw3gKfz3PmMwaDMk1VFj9B' 
                    },
                    body: JSON.stringify(payloadBody)
                });

                if (data.status === 'success') {
                    showToast('Ombi la malipo limetumwa!', 'success');
                    showWaitingState();
                    localStorage.setItem('pending_order', JSON.stringify({ id: data.tranID, type: paymentType, itemId: paymentItemId }));
                    startPolling(data.tranID, paymentType, paymentItemId);
                } else {
                    showToast(data.message || 'Malipo yameshindwa.', 'error');
                    resetPayBtn();
                }
            } catch (e) {
                showToast(e.message || 'Hitilafu ya mtandao. Jaribu tena.', 'error');
                resetPayBtn();
            }
        }

        // ─── Status Polling ───
        function startPolling(txId, type, id) {
            pollCount = 0;
            if (pollInterval) clearInterval(pollInterval);
            pollInterval = setInterval(async () => {
                pollCount++;

                if (pollCount > MAX_POLLS) {
                    clearInterval(pollInterval);
                    showToast('Muda umekwisha. Jaribu tena.', 'error');
                    showFormState();
                    return;
                }

                try {
                    const data = await fetchAPI(`${BASE}/api/check_payment.php?tranid=${encodeURIComponent(txId)}`);
                    const s = (data.payment_status || data.status || '').toLowerCase();

                    if (s === 'completed' || s === 'success') {
                        clearInterval(pollInterval);
                        localStorage.removeItem('pending_order');
                        showToast('Malipo yamepokelewa!', 'success');
                        showSuccessState();

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
                        let finalRedirect = '';
                        if (data.monetization_mode === 'channel') {
                            finalRedirect = BASE + '/streaming.php?creator_id=' + creatorId;
                        } else if (_wid) {
                            finalRedirect = BASE + '/watch.php?id=' + _wid;
                        } else if (data.global_redirect_url) {
                            finalRedirect = data.global_redirect_url;
                        } else {
                            finalRedirect = BASE + '/streaming.php?creator_id=' + creatorId;
                        }

                        setTimeout(() => { window.location.href = finalRedirect; }, 2000);
                    } else if (s === 'failed' || s === 'cancelled') {
                        clearInterval(pollInterval);
                        localStorage.removeItem('pending_order');
                        showToast('Malipo yameshindwa.', 'error');
                        showFailedState();
                    }
                } catch (e) {
                    console.error("Payment status check failed:", e);
                    if (pollCount > MAX_POLLS) {
                        clearInterval(pollInterval);
                        showFormState();
                    }
                }
            }, 3000);
        }

        function resetPayBtn() {
            const label = document.getElementById('btnLabel');
            if (label) label.innerText = "<?= addslashes(htmlspecialchars($landing_settings['cta_text'])) ?>";
            const loader = document.getElementById('btnLoader');
            if (loader) loader.classList.add('hidden');
            const btn = document.getElementById('payBtn');
            if (btn) btn.disabled = false;
        }

        function resetAndClose() {
            clearInterval(pollInterval);
            resetPayBtn();
            toggleModal(false);
        }

        // ─── Search ───
        function searchVideos() {
            let input = document.getElementById('searchInput').value.toLowerCase();
            let cards = document.getElementsByClassName('video-card');
            for (let i = 0; i < cards.length; i++) {
                let title = (cards[i].dataset.title || '').toLowerCase();
                cards[i].style.display = title.includes(input) ? "block" : "none";
            }
        }

        // ─── Refresh Malipo Click Handler ───
        function openRefreshModal() {
            const pending = JSON.parse(localStorage.getItem('pending_order'));
            if (pending && pending.id) {
                toggleModal(true);
                showToast("Tunakagua malipo yanayosubiriwa...", "info");
                showWaitingState();
                startPolling(pending.id, pending.type, pending.itemId);
            } else {
                showToast("Hakuna malipo yanayosubiriwa kwa sasa. Tafadhali lipia ili kuanza.", "info");
                toggleModal(true);
            }
        }

        // ─── Keyboard ───
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                if (document.getElementById('previewPlayer').style.display !== 'none') {
                    closePreview();
                }
                resetAndClose();
            }
        });

        // Prevent right-click on videos/images
        document.addEventListener('contextmenu', function(e) {
            if (e.target.tagName === 'VIDEO' || e.target.tagName === 'IMG') {
                e.preventDefault();
            }
        });

        // ─── Init ───
        window.onload = () => {
            const pending = JSON.parse(localStorage.getItem('pending_order'));
            if (pending && pending.id) {
                toggleModal(true);
                showWaitingState();
                startPolling(pending.id, pending.type, pending.itemId);
            } else {
                // Auto show modal after 5 seconds if no video is playing
                setTimeout(() => {
                    if (document.getElementById('payModal').classList.contains('hidden') &&
                        document.getElementById('previewPlayer').style.display === 'none') {
                        const firstCard = document.querySelector('.video-card');
                        if (firstCard) {
                            currentVideoId = firstCard.dataset.id;
                            trackCTA();
                            toggleModal(true);
                        }
                    }
                }, 5000);
            }
        };
    </script>
</body>
</html>
