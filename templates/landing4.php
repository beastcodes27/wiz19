<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($platform_name) ?> | Future of Streaming</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
    <link
        href="https://fonts.googleapis.com/css2?family=Syncopate:wght@400;700&family=Space+Grotesk:wght@300;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --neon: #00f2ff;
            --neon-pink: #ff00c8;
            --bg-deep: #050508;
            --card-glass: rgba(255, 255, 255, 0.05);
            --border-glass: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Space Grotesk', sans-serif;
            background: var(--bg-deep);
            color: #fff;
            overflow-x: hidden;
            background-image:
                radial-gradient(circle at 10% 20%, rgba(0, 242, 255, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(255, 0, 200, 0.05) 0%, transparent 40%);
        }

        /* --- Liquid Vertical Sidebar --- */
        .sidebar {
            position: fixed;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            width: 60px;
            background: var(--card-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass);
            border-radius: 100px;
            display: flex;
            flex-direction: column;
            gap: 30px;
            padding: 30px 0;
            align-items: center;
            z-index: 1000;
        }

        .sidebar i {
            font-size: 1.2rem;
            color: #555;
            cursor: pointer;
            transition: 0.3s;
        }

        .sidebar i:hover,
        .sidebar i.active {
            color: var(--neon);
            text-shadow: 0 0 10px var(--neon);
        }

        /* --- Main Content Area --- */
        main {
            margin-left: 100px;
            padding: 40px;
        }

        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 50px;
        }

        .logo {
            font-family: 'Syncopate', sans-serif;
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: -2px;
        }

        .logo span {
            color: var(--neon);
        }

        /* --- Spotlight Section --- */
        .spotlight {
            position: relative;
            height: 450px;
            background: linear-gradient(45deg, #111, #1a1a1a);
            border-radius: 40px;
            overflow: hidden;
            margin-bottom: 60px;
            display: flex;
            align-items: center;
            border: 1px solid var(--border-glass);
        }

        .spotlight-content {
            padding: 60px;
            z-index: 2;
            width: 50%;
        }

        .spotlight-visual {
            position: absolute;
            right: 0;
            top: 0;
            width: 60%;
            height: 100%;
            background: url('..../assets/landing3.jpg') center/cover;
            mask-image: linear-gradient(to left, black 70%, transparent 100%);
            -webkit-mask-image: linear-gradient(to left, black 70%, transparent 100%);
        }

        .btn-neon {
            background: var(--neon);
            color: #000;
            padding: 15px 35px;
            border-radius: 12px;
            font-weight: 700;
            text-transform: uppercase;
            border: none;
            cursor: pointer;
            box-shadow: 0 0 20px rgba(0, 242, 255, 0.4);
            transition: 0.3s;
        }

        /* --- 3D Floating Grid --- */
        .grid-header {
            margin-bottom: 30px;
            font-family: 'Syncopate';
            font-size: 0.8rem;
            color: var(--neon);
        }

        .neon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }

        .neon-card {
            background: #111116;
            border-radius: 30px;
            padding: 10px;
            border: 1px solid #222;
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
        }

        .neon-card:hover {
            transform: translateY(-10px) rotateX(5deg);
            border-color: var(--neon);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
        }

        .card-inner {
            border-radius: 25px;
            overflow: hidden;
            aspect-ratio: 16/10;
            position: relative;
        }

        .card-inner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .card-meta {
            padding: 20px 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .lock-shield {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            display: grid;
            place-items: center;
            color: var(--neon-pink);
            border: 1px solid rgba(255, 0, 200, 0.3);
        }

        /* --- Payment Interface (Cyber Glass) --- */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(10px);
            z-index: 2000;
            display: none;
            place-items: center;
        }

        .cyber-panel {
            background: rgba(20, 20, 25, 0.8);
            border: 1px solid var(--border-glass);
            padding: 40px;
            border-radius: 40px;
            width: 90%;
            max-width: 450px;
            position: relative;
            box-shadow: 0 0 100px rgba(0, 0, 0, 1);
        }

        .cyber-panel::before {
            content: '';
            position: absolute;
            top: -2px;
            left: 10%;
            width: 80%;
            height: 2px;
            background: linear-gradient(to right, transparent, var(--neon), transparent);
        }

        .cyber-input {
            width: 100%;
            background: #000;
            border: 1px solid #333;
            padding: 20px;
            border-radius: 15px;
            color: var(--neon);
            font-family: 'Syncopate';
            font-size: 1.2rem;
            text-align: center;
            margin: 20px 0;
        }

        @media (max-width: 768px) {
            .sidebar {
                bottom: 20px;
                top: auto;
                left: 50%;
                transform: translateX(-50%);
                width: 80%;
                height: 60px;
                flex-direction: row;
                justify-content: space-around;
                padding: 0;
            }

            main {
                margin-left: 0;
                padding: 20px;
                padding-bottom: 100px;
            }

            .spotlight {
                flex-direction: column;
                height: auto;
            }

            .spotlight-content {
                width: 100%;
                padding: 30px;
            }

            .spotlight-visual {
                position: relative;
                width: 100%;
                height: 200px;
                mask-image: none;
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

    <aside class="sidebar">
        <i class="fas fa-home active"></i>
        <i class="fas fa-bolt"></i>
        <i class="fas fa-heart"></i>
        <i class="fas fa-plus-circle"></i>
        <i class="fas fa-cog"></i>
    </aside>

    <main>
        <nav class="top-nav">
            <div class="logo"><?= htmlspecialchars($platform_name) ?></div>
            <div
                style="background: var(--card-glass); padding: 10px 20px; border-radius: 15px; border: 1px solid var(--border-glass);">
                <i class="fas fa-search" style="margin-right: 10px; color: #666;"></i>
                <span style="font-size: 0.8rem; color: #888;">Search Archive...</span>
            </div>
        </nav>

        <section class="spotlight">
            <div class="spotlight-content">
                <span
                    style="color: var(--neon-pink); font-weight: 700; font-size: 0.8rem; letter-spacing: 2px;">EXCLUSIVE
                    PREMIERE</span>
                <h1 style="font-size: 3rem; font-family: 'Syncopate'; margin: 10px 0 20px;">BONGO <br>LEAK V.4</h1>
                <p style="color: #888; margin-bottom: 30px; font-weight: 300;"></p>
            </div>
            <div class="spotlight-visual"></div>
        </section>

        <h2 class="grid-header">// Video Mpya</h2>

        <div class="neon-grid">
            <?php if (!empty($user_videos)): ?>
                <?php foreach ($user_videos as $video): ?>
                    <div class="neon-card" onclick="openCyberModal('<?= htmlspecialchars((string)($video['price'] ?? 1000)) ?>', '<?= htmlspecialchars((string)$video['id']) ?>')">
                        <div class="card-inner">
                            <img src="<?= htmlspecialchars($video['thumbnail_url'] ?? 'assets/images/default_thumb.jpg') ?>" alt="<?= htmlspecialchars($video['title']) ?>">
                            <div style="position: absolute; top: 15px; left: 15px; background: rgba(0,0,0,0.8); padding: 5px 12px; border-radius: 8px; font-size: 0.7rem;">
                                4K HDR
                            </div>
                        </div>
                        <div class="card-meta">
                            <div>
                                <h3 style="font-size: 1rem; margin-bottom: 5px;"><?= htmlspecialchars($video['title']) ?></h3>
                                <p style="font-size: 0.7rem; color: #666;"><?= number_format($video['views'] ?? 0) ?> FLOWERS</p>
                            </div>
                            <div class="lock-shield">
                                <i class="fas fa-fingerprint"></i>
                            </div>
                        </div>
                        <div style="position: absolute; bottom: -10px; left: 50%; transform: translateX(-50%); background: var(--neon-pink); font-size: 0.6rem; padding: 4px 15px; border-radius: 4px; font-weight: 800;">
                            <?= number_format($video['price'] ?? 1000) ?> TSH
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-videos" style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-video-slash" style="font-size: 3rem; margin-bottom: 15px; color: var(--neon-pink);"></i>
                    <p style="font-size: 1.1rem; font-weight: 600;">No videos uploaded yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <div class="modal-overlay" id="paymentModal">
        <div class="cyber-panel">
            <div style="text-align: center;">
                <div
                    style="width: 60px; height: 60px; background: rgba(255,0,200,0.1); border-radius: 50%; display: grid; place-items: center; margin: 0 auto 20px;">
                    <i class="fas fa-shield-halved" style="color: var(--neon-pink); font-size: 1.5rem;"></i>
                </div>
                <h2 style="font-family: 'Syncopate'; font-size: 1rem; letter-spacing: 2px;">GATEWAY AUTHORIZATION</h2>
                <p style="color: #666; font-size: 0.8rem; margin-top: 10px;">Transaction secured by encrypted protocol.
                </p>
            </div>

            <input type="tel" id="phoneNumber" class="cyber-input" placeholder="0XXXXXXXXX">

            <input type="hidden" id="videoID">
            <button class="btn-neon" style="width: 100%;" onclick="processPayment()">
                INITIALIZE PAYMENT
            </button>

            <button onclick="closeCyberModal()" style="width:100%;margin-top:15px;padding:14px;background:transparent;border:1px solid rgba(0,242,255,0.2);border-radius:12px;color:#666;font-size:0.75rem;font-family:'Syncopate';letter-spacing:2px;cursor:pointer;">
                [ GHAIRI / CANCEL ]
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

        // Define the interval variable globally
        let paymentCheckInterval;

        function openCyberModal(price, id) {
            // Store the ID and display the modal
            document.getElementById('videoID').value = id;

            // Store price in a way the process function can grab it
            document.getElementById('paymentModal').setAttribute('data-price', price);

            // Always clear phone field and errors when switching videos
            var phoneEl = document.getElementById('phoneNumber');
            if (phoneEl) phoneEl.value = '';
            var errEl = document.getElementById('paymentError');
            if (errEl) errEl.style.display = 'none';
            var payBtn = document.querySelector('.btn-neon');
            if (payBtn) { payBtn.disabled = false; payBtn.innerHTML = 'INITIALIZE PAYMENT'; }

            document.getElementById('paymentModal').style.display = 'grid';

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

        function closeCyberModal() {
            document.getElementById('paymentModal').style.display = 'none';
            if (paymentCheckInterval) clearInterval(paymentCheckInterval);
            resetCyberPaymentForm();
        }

        // Close if clicking outside the panel
        document.getElementById('paymentModal').addEventListener('click', function(e) {
            if (e.target === this) closeCyberModal();
        });

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
