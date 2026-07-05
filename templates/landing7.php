<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($platform_name) ?> | Premium Streaming</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --primary: #e50914;
            --primary-hover: #b20710;
            --bg-dark: #0f0f0f;
            --card-bg: #1a1a1a;
            --text-main: #ffffff;
            --text-dim: #b3b3b3;
            --success: #2ecc71;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* --- Navigation --- */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 5%;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            background: linear-gradient(to bottom, rgba(0,0,0,0.9), transparent);
        }

        .logo { font-size: 1.8rem; font-weight: 800; color: var(--primary); }
        .user-badge { background: var(--primary); padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }

        /* --- Hero Section --- */
        .hero {
            height: 70vh;
            display: flex;
            align-items: center;
            padding: 0 5%;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to right, rgba(0,0,0,0.9) 30%, transparent 100%);
        }

        .hero-content { position: relative; z-index: 10; max-width: 600px; }
        .hero-content h1 { font-size: clamp(2rem, 5vw, 3.5rem); margin-bottom: 20px; line-height: 1.1; }

        /* --- Buttons --- */
        .btn {
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            font-size: 1rem;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-hover); transform: scale(1.05); }

        /* --- Movie Grid --- */
        .container { padding: 40px 5%; }
        .movie-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
        }

        .movie-card {
            background: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            transition: 0.3s;
            border: 1px solid #222;
        }

        .movie-card:hover { transform: translateY(-5px); border-color: var(--primary); }
        .poster-wrapper { position: relative; aspect-ratio: 2/3; }
        .poster-wrapper img { width: 100%; height: 100%; object-fit: cover; }
        .price-tag {
            position: absolute; top: 10px; right: 10px;
            background: var(--primary); padding: 4px 10px;
            border-radius: 6px; font-size: 0.7rem; font-weight: 700;
        }

        .card-details { padding: 12px; }
        .card-details h3 { font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* --- Payment Modal --- */
        .modal {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.9); z-index: 2000;
            align-items: center; justify-content: center; backdrop-filter: blur(10px);
        }

        .modal.active { display: flex; }
        .modal-box {
            background: #181818; width: 90%; max-width: 400px;
            border-radius: 24px; overflow: hidden; border: 1px solid #333;
        }

        .modal-header { background: var(--primary); padding: 30px; text-align: center; }
        .modal-body { padding: 30px; text-align: center; }

        .input-field {
            width: 100%; padding: 16px; background: #252525;
            border: 2px solid #333; border-radius: 12px; color: white;
            font-size: 1.1rem; margin-bottom: 20px; outline: none;
        }

        .input-field:focus { border-color: var(--primary); }

        .spinner {
            width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff; border-radius: 50%; animation: spin 0.8s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }
    
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
        <div class="logo"><?= htmlspecialchars($platform_name) ?></div>
        <div class="user-badge">PREMIUM ACCESS</div>
    </nav>

    
    <section class="hero" style="background-image: url('https://cdn1.tanzaniahub.click/videos/f0b3ca7b-b582-484d-9ec0-070a71f692fe/preview.jpg');">
        <div class="hero-content">
            <h1>MJUZI WA MAMBO</h1>
                            <button class="btn btn-primary" onclick="openPaymentModal('MJUZI WA MAMBO', '1000', '1024')">
                    <i class="fas fa-lock-open"></i> UNLOCK FOR TSH 1,000
                </button>
                    </div>
    </section>

    <div class="container">
        <div class="movie-grid">
            <?php if (!empty($user_videos)): ?>
                <?php foreach ($user_videos as $video): ?>
                    <div class="movie-card" onclick="openPaymentModal('<?= htmlspecialchars($video['title']) ?>', '<?= htmlspecialchars((string)($video['price'] ?? 1000)) ?>', '<?= htmlspecialchars((string)$video['id']) ?>', '<?= htmlspecialchars($video['slug']) ?>')">
                        <div class="poster-wrapper">
                            <img src="<?= htmlspecialchars($video['thumbnail_url'] ?? 'assets/images/default_thumb.jpg') ?>" loading="lazy" alt="<?= htmlspecialchars($video['title']) ?>">
                            <span class="price-tag">
                                TSH <?= number_format($video['price'] ?? 1000) ?>
                            </span>
                        </div>
                        <div class="card-details">
                            <h3><?= htmlspecialchars($video['title']) ?></h3>
                            <p style="color:var(--text-dim); font-size:0.75rem;"><?= number_format($video['views'] ?? 0) ?> views</p>
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
    </div>

    <div id="paymentModal" class="modal">
        <div class="modal-box">
            <div class="modal-header">
                <h2 id="modalTitle">Unlock Video</h2>
                <p id="modalPrice">TSH 0</p>
            </div>
            <div class="modal-body">
                <input type="tel" id="phoneNumber" class="input-field" placeholder="07XXXXXXXX" maxlength="10">
                
                <button class="btn btn-primary" id="payBtn" onclick="processPayment()" style="width: 100%; justify-content: center;">
                    <span id="btnText">LIPA SASA</span>
                    <div id="btnSpinner" class="spinner" style="display:none"></div>
                </button>
                
                <p id="statusMsg" style="margin-top:15px; font-size:0.85rem; display:none;"></p>
                <button onclick="closeModal()" style="width:100%;background:transparent;border:1px solid #333;border-radius:8px;color:var(--text-dim);padding:12px;margin-top:12px;cursor:pointer;font-size:0.9rem;display:flex;align-items:center;justify-content:center;gap:8px;">
                    <i class="fas fa-times-circle"></i> Ghairi
                </button>
            </div>
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

        let selectedVideoID = null;
        let selectedVideoSlug = '';

        function openPaymentModal(title, price, id, slug) {
            selectedVideoID = id;
            selectedVideoSlug = slug || '';
            document.getElementById('modalTitle').innerText = title;
            document.getElementById('modalPrice').innerText = 'TSH ' + parseInt(price).toLocaleString();

            // Always clear phone field and errors when switching videos
            var phoneEl = document.getElementById('phoneNumber');
            if (phoneEl) phoneEl.value = '';
            var errEl = document.getElementById('paymentError');
            if (errEl) errEl.style.display = 'none';
            var statusEl = document.getElementById('statusMsg');
            if (statusEl) statusEl.style.display = 'none';
            var payBtn = document.getElementById('payBtn');
            if (payBtn) { payBtn.disabled = false; }
            var btnText = document.getElementById('btnText');
            if (btnText) btnText.innerText = 'LIPA SASA';
            var spinner = document.getElementById('btnSpinner');
            if (spinner) spinner.style.display = 'none';

            document.getElementById('paymentModal').classList.add('active');

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

        function closeModal() {
            document.getElementById('paymentModal').classList.remove('active');
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
