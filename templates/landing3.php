<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($platform_name) ?> | The Social Feed</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --accent: #9d50ff;
            --accent-glow: rgba(157, 80, 255, 0.5);
            --bg-dark: #000000;
            --surface: #121214;
            --text: #ffffff;
            --text-muted: #a1a1aa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-dark);
            color: var(--text);
            padding-bottom: 80px;
            /* Space for bottom nav */
        }

        /* --- Custom Scrollbar --- */
        ::-webkit-scrollbar {
            width: 0px;
        }

        /* --- Header / Top Bar --- */
        header {
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            z-index: 100;
        }

        .brand {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -1px;
            background: linear-gradient(to right, #fff, var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* --- Stories / Hot Tags --- */
        .stories-container {
            display: flex;
            gap: 15px;
            overflow-x: auto;
            padding: 10px 20px 20px;
        }

        .story-circle {
            min-width: 70px;
            height: 70px;
            border-radius: 50%;
            padding: 3px;
            background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
            position: relative;
        }

        .story-circle img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #000;
        }

        .story-label {
            font-size: 0.65rem;
            text-align: center;
            margin-top: 5px;
            color: var(--text-muted);
            font-weight: 600;
        }

        /* --- The Feed (Masonry Style) --- */
        .feed-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            padding: 0 15px;
        }

        .post-card {
            background: var(--surface);
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }

        /* Vertical "TikTok" aspect ratio */
        .post-thumb {
            width: 100%;
            aspect-ratio: 9/14;
            position: relative;
        }

        .post-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .post-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.9) 0%, transparent 40%);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 12px;
        }

        .post-title {
            font-size: 0.85rem;
            font-weight: 600;
            line-height: 1.2;
            margin-bottom: 5px;
        }

        .post-stats {
            font-size: 0.7rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .floating-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--accent);
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 0.6rem;
            font-weight: 800;
            box-shadow: 0 0 15px var(--accent-glow);
        }

        .price-chip {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.7);
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 0.65rem;
            font-weight: 700;
            color: #ffd700;
            border: 1px solid #ffd700;
        }

        /* --- Bottom Navigation --- */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 70px;
            background: rgba(18, 18, 20, 0.95);
            backdrop-filter: blur(20px);
            display: flex;
            justify-content: space-around;
            align-items: center;
            border-top: 1px solid #27272a;
            z-index: 1000;
        }

        .nav-item {
            color: var(--text-muted);
            font-size: 1.2rem;
            cursor: pointer;
            transition: 0.3s;
            position: relative;
        }

        .nav-item.active {
            color: var(--accent);
        }

        .nav-item.active::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 4px;
            height: 4px;
            background: var(--accent);
            border-radius: 50%;
        }

        .nav-center {
            width: 50px;
            height: 50px;
            background: var(--accent);
            border-radius: 15px;
            display: grid;
            place-items: center;
            color: #fff;
            transform: translateY(-20px);
            box-shadow: 0 10px 20px var(--accent-glow);
        }

        /* --- Modal (Modern Sheet Style) --- */
        .modal-overlay {
            position: fixed;
            bottom: -100%;
            left: 0;
            right: 0;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            z-index: 2000;
            transition: bottom 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: flex-end;
        }

        .modal-overlay.open {
            bottom: 0;
        }

        .modal-sheet {
            width: 100%;
            background: var(--surface);
            border-radius: 30px 30px 0 0;
            padding: 30px 20px;
            border-top: 2px solid var(--accent);
        }

        .pay-btn {
            width: 100%;
            padding: 18px;
            background: var(--accent);
            border: none;
            border-radius: 18px;
            color: white;
            font-size: 1rem;
            font-weight: 800;
            margin-top: 20px;
            box-shadow: 0 10px 20px var(--accent-glow);
        }

        @media (min-width: 1024px) {
            .feed-container {
                grid-template-columns: repeat(4, 1fr);
                max-width: 1200px;
                margin: 0 auto;
            }
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

    <header>
        <div class="brand"><?= htmlspecialchars($platform_name) ?></div>
        <div style="display: flex; gap: 20px; align-items: center;">
            <i class="fas fa-bell"></i>
            <div style="width: 35px; height: 35px; border-radius: 10px; background: #333;"></div>
        </div>
    </header>

    <div class="stories-container">
        <div class="story-item">
            <div class="story-circle"><img src="<?= BASE_URL ?>/assets/photo_2026-01-15_06-47-27.jpg" alt="">
            </div>
            <div class="story-label">Mamuu Mtamu</div>
        </div>
        <div class="story-item">
            <div class="story-circle"><img src="<?= BASE_URL ?>/assets/photo_2026-01-15_06-47-56.jpg" alt="">
            </div>
            <div class="story-label">Ashaa</div>
        </div>
        <div class="story-item">
            <div class="story-circle"><img src="<?= BASE_URL ?>/assets/photo_2026-01-15_06-47-50.jpg" alt="">
            </div>
            <div class="story-label">Boss Lady</div>
        </div>
        <div class="story-item">
            <div class="story-circle"><img src="<?= BASE_URL ?>/assets/photo_2026-01-15_06-48-44.jpg" alt="">
            </div>
            <div class="story-label">Dalali Kidoti</div>
        </div>
    </div>

    <main class="feed-container">
        <?php if (!empty($user_videos)): ?>
            <?php foreach ($user_videos as $video): ?>
                <div class="post-card"
                   onclick="openPaymentSheet('<?= htmlspecialchars((string)($video['price'] ?? 1000)) ?>', '<?= htmlspecialchars((string)$video['id']) ?>', '<?= htmlspecialchars($video['slug']) ?>')">
                    <div class="post-thumb">
                        <img src="<?= htmlspecialchars($video['thumbnail_url'] ?? 'assets/images/default_thumb.jpg') ?>" alt="<?= htmlspecialchars($video['title']) ?>">
                        <span class="price-chip">TSH <?= number_format($video['price'] ?? 1000) ?></span>
                        <span class="floating-badge">
                            <i class="fas fa-lock"></i>
                        </span>
                        <div class="post-overlay">
                            <h3 class="post-title"><?= htmlspecialchars($video['title']) ?></h3>
                            <div class="post-stats">
                                <span><i class="fas fa-eye"></i> <?= number_format($video['views'] ?? 0) ?></span>
                                <span><i class="fas fa-heart"></i> <?= rand(50, 500) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-videos" style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--text-muted, #888);">
                <i class="fas fa-video-slash" style="font-size: 3rem; margin-bottom: 15px; color: var(--accent, #9d50ff);"></i>
                <p style="font-size: 1.1rem; font-weight: 600;">No videos uploaded yet.</p>
            </div>
        <?php endif; ?>
    </main>

    <nav class="bottom-nav">
        <div class="nav-item active"><i class="fas fa-home"></i></div>
        <div class="nav-item"><i class="fas fa-compass"></i></div>
        <div class="nav-center"><i class="fas fa-crown"></i></div>
        <div class="nav-item"><i class="fas fa-search"></i></div>
        <div class="nav-item"><i class="fas fa-user"></i></div>
    </nav>

    <div class="modal-overlay" id="paymentModal">
        <div class="modal-sheet">
            <div style="width: 40px; height: 5px; background: #333; border-radius: 10px; margin: 0 auto 20px;"></div>

            <div style="text-align: center;">
                <h2 style="font-size: 1.5rem; margin-bottom: 5px;">Unlock Content</h2>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Join the premium club to watch this full video.
                </p>

                <div
                    style="margin: 25px 0; background: rgba(157, 80, 255, 0.1); padding: 20px; border-radius: 20px; border: 1px dashed var(--accent);">
                    <span
                        style="display: block; font-size: 0.8rem; color: var(--accent); font-weight: 800; margin-bottom: 5px;">TOTAL
                        AMOUNT</span>
                    <h1 id="modalPrice" style="font-size: 2.5rem;">TSH 0</h1>
                </div>
            </div>

            <div
                style="background: #1d1d21; padding: 15px; border-radius: 15px; display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-mobile-alt" style="color: var(--accent); font-size: 1.5rem;"></i>
                <input type="tel" id="phoneNumber" placeholder="Enter Mobile Number"
                    style="background: transparent; border: none; color: #fff; font-size: 1rem; outline: none; width: 100%;">
            </div>

            <input type="hidden" id="videoID">
            <button class="pay-btn" onclick="processPayment()">CONFIRM &amp; PAY</button>
            <button onclick="closePaymentSheet()" style="width:100%;margin-top:12px;padding:14px;background:transparent;border:1px solid rgba(157,80,255,0.3);border-radius:18px;color:var(--text-muted);font-size:0.9rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;">
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

let paymentCheckInterval;

// OPEN MODAL
function openPaymentSheet(price, id, folder) {
    const videoInput = document.getElementById('videoID');

    document.getElementById('modalPrice').innerText = 'TSH ' + price;

    videoInput.value = id;

    // ✅ store folder correctly
    videoInput.dataset.folder = folder;

    // Always clear phone field and errors when switching videos
    var phoneEl = document.getElementById('phoneNumber');
    if (phoneEl) phoneEl.value = '';
    var errEl = document.getElementById('paymentError');
    if (errEl) errEl.style.display = 'none';
    var payBtn = document.querySelector('.pay-btn');
    if (payBtn) { payBtn.disabled = false; payBtn.innerHTML = 'CONFIRM &amp; PAY'; }

    document.getElementById('paymentModal').classList.add('open');

    // Track view & click CTA
    fetch('<?= BASE_URL ?>/api/track', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ video_id: id, event_type: 'view' }),
        keepalive: true
    }).catch(err => {});
    fetch('<?= BASE_URL ?>/api/track', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ video_id: id, event_type: 'click_cta' }),
        keepalive: true
    }).catch(err => {});
}

// CLOSE MODAL
function closePaymentSheet() {
    document.getElementById('paymentModal').classList.remove('open');
    resetPaymentForm();
}

// CLICK OUTSIDE TO CLOSE
document.getElementById('paymentModal').addEventListener('click', function(e) {
    if (e.target === this) closePaymentSheet();
});

// PROCESS PAYMENT
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

        async function processPayment() {
        hideError('payment');
            const phoneInput = document.getElementById('phoneNumber') || document.getElementById('phoneInput');
            const videoInput = document.getElementById('videoID');
            const phone = phoneInput ? phoneInput.value.trim() : '';
            
            // Resolve creator ID dynamically from PHP
            const creatorId = '<?= isset($domain_owner["user_id"]) ? $domain_owner["user_id"] : (isset($video["user_id"]) ? $video["user_id"] : (isset($user_videos[0]["user_id"]) ? $user_videos[0]["user_id"] : "")) ?>';
            
            // Dynamic resolution of video ID
            let videoId = '';
            if (videoInput && videoInput.value) {
                videoId = videoInput.value;
            } else if (typeof selectedVideoID !== 'undefined' && selectedVideoID) {
                videoId = selectedVideoID;
            } else if (typeof currentVideoId !== 'undefined' && currentVideoId) {
                videoId = currentVideoId;
            }
            
            // Dynamic resolution of video slug (for redirects)
            let videoSlug = '';
            if (typeof selectedVideoSlug !== 'undefined' && selectedVideoSlug) {
                videoSlug = selectedVideoSlug;
            } else if (typeof currentVideoSlug !== 'undefined' && currentVideoSlug) {
                videoSlug = currentVideoSlug;
            }
            
            if (!phone || phone.length < 10) {
                showError('payment', "Tafadhali weka namba ya simu sahihi / Please enter a valid phone number.");
                return;
            }
            if (!videoId) {
                showError('payment', "Error: Video ID not found.");
                return;
            }
            
            // Find the button to show loading state
            const btn = document.querySelector('button[onclick="processPayment()"]') || document.querySelector('.btn-full') || document.getElementById('payBtn');
            const originalText = btn ? btn.innerHTML : 'Pay';
            if (btn) {
                btn.innerHTML = 'Initiating Payment...';
                btn.disabled = true;
            }

            try {
                const baseUrl = '<?= defined("BASE_URL") ? BASE_URL : "" ?>';
                const data = await fetchAPI(baseUrl + '/api/process_payment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ video_id: videoId, phone: phone })
                });
                
                if (data.status === 'success') {
                    if (btn) btn.innerHTML = 'Waiting for PIN...';
                    
                    // Start polling
                    let attempts = 0;
                    const maxAttempts = 24; // 2 minutes
                    const interval = setInterval(async () => {
                        attempts++;
                        if (attempts > maxAttempts) {
                            clearInterval(interval);
                            showError('payment', 'Payment timed out. Please try again.');
                            if (btn) { btn.innerHTML = originalText; btn.disabled = false; }
                            return;
                        }
                        
                        try {
                            const pollData = await fetchAPI(baseUrl + '/api/check_payment.php?tranid=' + data.tranID);
                            const s = (pollData.payment_status || pollData.status || '').toUpperCase();
                            if (s === 'COMPLETED' || s === 'SUCCESS') {
                                clearInterval(interval);
                                if (btn) btn.innerHTML = 'SUCCESS!';
                                
                                // Set secure 24-hour cookie for streaming.php to avoid mobile data IP rotation lockouts
                                const expires = new Date(Date.now() + 24 * 60 * 60 * 1000).toUTCString();
                                document.cookie = "sf_pass_" + creatorId + "=" + encodeURIComponent(data.tranID) + "; expires=" + expires + "; path=/; SameSite=Lax";
                                // Set per-video cookie so watch.php grants 24-hour Pay-Per-Video access
                                const _vid = (pollData && pollData.video_id) ? pollData.video_id : '';
                                if (_vid) document.cookie = "sf_video_" + _vid + "=" + encodeURIComponent(data.tranID) + "; expires=" + expires + "; path=/; SameSite=Lax";
                                
                                setTimeout(() => {
                                    
                                    const resData = typeof pollData !== "undefined" ? pollData : (typeof data !== "undefined" ? data : {});
                                    // Route: channel → streaming.php | single → watch.php
                                    if (resData.monetization_mode === 'channel') {
                                        window.location.href = (typeof baseUrl !== "undefined" ? baseUrl : BASE_URL) + '/streaming.php?creator_id=' + creatorId;
                                    } else if (resData.video_id) {
                                        window.location.href = (typeof baseUrl !== "undefined" ? baseUrl : BASE_URL) + '/watch.php?id=' + resData.video_id;
                                    } else if (resData.global_redirect_url) {
                                        window.location.href = resData.global_redirect_url;
                                    } else {
                                        window.location.href = baseUrl + '/streaming.php?creator_id=' + creatorId;
                                    }
                                }, 1000);
                            } else if (s === 'FAILED' || s === 'CANCELLED') {
                                clearInterval(interval);
                                showError('payment', 'Payment failed or was cancelled.');
                                if (btn) { btn.innerHTML = originalText; btn.disabled = false; }
                            }
                        } catch (e) {
                            console.error('Polling error', e);
                        }
                    }, 5000);
                    
                } else {
                    showError('payment', 'Error: ' + (data.message || 'Payment initiation failed'));
                    if (btn) { btn.innerHTML = originalText; btn.disabled = false; }
                }
            } catch (err) {
                showError('payment', 'Connection error.\n\n[Details]:\n' + err.message);
                if (btn) { btn.innerHTML = originalText; btn.disabled = false; }
            }
        }
</script>
</body>

</html>
