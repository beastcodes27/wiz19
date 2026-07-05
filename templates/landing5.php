<!DOCTYPE html>
<html lang="sw">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($platform_name) ?> | Burudani Yako</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --tz-green: #1eb53a;
            --tz-gold: #ffda00;
            --bg: #0f1115;
            --card: #1a1d23;
            --text-gray: #9ba3af;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: white;
            padding-top: 60px;
        }

        /* --- Header: Clean & Fast --- */
        .header {
            position: fixed;
            top: 0;
            width: 100%;
            height: 60px;
            background: rgba(15, 17, 21, 0.95);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 15px;
            z-index: 1000;
            border-bottom: 1px solid #222;
        }

        .logo {
            font-weight: 900;
            font-size: 1.4rem;
            color: var(--tz-gold);
            letter-spacing: -1px;
        }

        .logo span {
            color: white;
        }

        /* --- Featured "Hot" Banner --- */
        .promo-banner {
            margin: 15px;
            background: linear-gradient(135deg, #1eb53a 0%, #000 100%);
            border-radius: 15px;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        .promo-banner h2 {
            font-size: 1.2rem;
            margin-bottom: 5px;
        }

        .promo-banner p {
            font-size: 0.8rem;
            opacity: 0.9;
            margin-bottom: 15px;
        }

        .badge-live {
            background: #ff3e3e;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.6rem;
            font-weight: 900;
            animation: blink 1s infinite;
        }

        @keyframes blink {
            50% {
                opacity: 0.2;
            }
        }

        /* --- Simplified Video List --- */
        .content-label {
            padding: 0 15px;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-gray);
            text-transform: uppercase;
            margin-bottom: 15px;
        }

        .video-list {
            padding: 0 15px;
        }

        .video-row {
            display: flex;
            background: var(--card);
            border-radius: 12px;
            margin-bottom: 12px;
            padding: 8px;
            gap: 12px;
            border: 1px solid #282c35;
            cursor: pointer;
        }

        .video-row:active {
            transform: scale(0.98);
            background: #222;
        }

        .video-img {
            width: 110px;
            height: 75px;
            border-radius: 8px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .video-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .video-info h3 {
            font-size: 0.9rem;
            line-height: 1.2;
            margin-bottom: 5px;
        }

        .price-chip {
            background: rgba(255, 218, 0, 0.15);
            color: var(--tz-gold);
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 800;
            display: inline-block;
        }

        .stats {
            font-size: 0.7rem;
            color: var(--text-gray);
            margin-top: 5px;
        }

        /* --- The "Trust" Modal (Bottom Sheet) --- */
        .bottom-sheet {
            position: fixed;
            bottom: -100%;
            left: 0;
            right: 0;
            background: #fff;
            color: #000;
            border-radius: 20px 20px 0 0;
            padding: 25px;
            z-index: 2000;
            transition: 0.4s cubic-bezier(0, 0.55, 0.45, 1);
        }

        .bottom-sheet.show {
            bottom: 0;
        }

        .overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            pointer-events: none;
            /* 🔑 */
            z-index: 1500;
        }


        .provider-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin: 20px 0;
        }

        .provider-box {
            border: 2px solid #f0f0f0;
            border-radius: 10px;
            padding: 10px;
            text-align: center;
            font-size: 0.6rem;
            font-weight: 700;
        }

        .provider-box.active {
            border-color: var(--tz-green);
            background: #f0fff4;
        }

        .tel-input-group {
            background: #f4f4f4;
            border-radius: 12px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .tel-input-group input {
            border: none;
            background: transparent;
            font-size: 1.1rem;
            font-weight: 700;
            outline: none;
            width: 100%;
        }

        .pay-btn-final {
            width: 100%;
            background: var(--tz-green);
            color: white;
            border: none;
            padding: 18px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 1rem;
            box-shadow: 0 4px 15px rgba(30, 181, 58, 0.3);
        }

        /* Bottom Nav */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            width: 100%;
            height: 65px;
            background: #1a1d23;
            display: flex;
            align-items: center;
            justify-content: space-around;
            border-top: 1px solid #333;
        }

        .nav-link {
            color: var(--text-gray);
            text-align: center;
            font-size: 0.6rem;
            text-decoration: none;
        }

        .nav-link.active {
            color: var(--tz-gold);
        }

        .nav-link i {
            display: block;
            font-size: 1.2rem;
            margin-bottom: 4px;
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

    <div class="header">
        <div class="logo"><?= htmlspecialchars($platform_name) ?></div>
        <div style="font-size: 0.8rem; color: var(--tz-green); font-weight: 800;">
            <i class="fas fa-wallet"></i> TZ SHILLING
        </div>
    </div>

    <div class="promo-banner">
        <span class="badge-live">HOT NEW</span>
        <h2 style="margin-top: 10px;">LIL OMMY SPECIAL</h2>
        <p>Watch full exclusive interview and leaks.</p>
        <button onclick="window.scrollTo(0, 500)"
            style="background: white; color: black; border: none; padding: 8px 15px; border-radius: 6px; font-weight: 800; font-size: 0.7rem;">ANGALIA
            SASA</button>
    </div>

    <p class="content-label">Zilizopendwa Leo</p>

    <div class="video-list">
        <?php if (!empty($user_videos)): ?>
            <?php foreach ($user_videos as $video): ?>
                <div class="video-row" onclick="openSheet('<?= htmlspecialchars((string)($video['price'] ?? 1000)) ?>', '<?= htmlspecialchars((string)$video['id']) ?>')">
                    <img src="<?= htmlspecialchars($video['thumbnail_url'] ?? 'assets/images/default_thumb.jpg') ?>" class="video-img" alt="<?= htmlspecialchars($video['title']) ?>">
                    <div class="video-info">
                        <div>
                            <h3 style="color: white; font-weight: 600;"><?= htmlspecialchars($video['title']) ?></h3>
                            <span class="price-chip">TSH <?= number_format($video['price'] ?? 1000) ?></span>
                        </div>
                        <div class="stats">
                            <i class="fas fa-eye"></i> <?= number_format($video['views'] ?? 0) ?> views • <i class="fas fa-clock"></i> Leo
                        </div>
                    </div>
                    <div style="display: flex; align-items: center;">
                        <i class="fas fa-chevron-right" style="color: var(--text-gray);"></i>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-videos" style="text-align: center; padding: 40px 15px; color: var(--text-gray);">
                <i class="fas fa-video-slash" style="font-size: 3rem; margin-bottom: 15px; color: var(--tz-gold);"></i>
                <p style="font-size: 1.1rem; font-weight: 600; color: white;">Hakuna video zilizopakiwa bado.</p>
                <p style="font-size: 0.85rem; margin-top: 5px; opacity: 0.8;">Tafadhali angalia baadae.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="bottom-nav">
        <a href="#" class="nav-link active"><i class="fas fa-home"></i>Nyumbani</a>
        <a href="#" class="nav-link"><i class="fas fa-fire"></i>Moto</a>
        <a href="#" class="nav-link"><i class="fas fa-play-circle"></i>Video Zangu</a>
        <a href="#" class="nav-link"><i class="fas fa-user"></i>Akaunti</a>
    </div>

    <div class="overlay" id="overlay" onclick="closeSheet()"></div>
    <div class="bottom-sheet" id="sheet">
        <div style="width: 40px; height: 4px; background: #ddd; border-radius: 10px; margin: 0 auto 20px;"></div>

        <h2 style="font-size: 1.2rem; font-weight: 800; text-align: center;">Lipia Utazame</h2>
        <p style="text-align: center; color: #666; font-size: 0.8rem; margin-top: 5px;">Weka Number Yako Ukamilishe
            Malipo</p>

        <div class="provider-grid" style="position: center;">
            <img src="https://tz.selcom.online/static/ecommgw/img/all_logo.png" width="50%">

        </div>

        <div id="paymentError" class="modal-error"></div>
        <div class="tel-input-group">
            <span style="font-weight: 800; color: #888;">+255</span>
            <input type="tel" id="phoneNumber" placeholder="7XXXXXXXX" maxlength="9">
        </div>

        <input type="hidden" id="videoID">
        <button class="pay-btn-final" onclick="submitPayment()">
            LIPIA TSH <span id="modalPrice">0</span>
        </button>

        <button onclick="closeSheet()" style="width:100%;margin-top:10px;padding:14px;background:transparent;border:2px solid #ddd;border-radius:12px;color:#666;font-size:0.9rem;font-weight:800;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;">
            <i class="fas fa-times-circle"></i> Ghairi
        </button>

        <p style="font-size: 0.65rem; color: #999; text-align: center; margin-top: 15px;">
            <i class="fas fa-lock"></i> Malipo ni salama 100%. Subiri ujumbe wa namba ya siri (PIN).
        </p>
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

        // 1. Global state management
        let paymentCheckInterval;

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

        // 2. UI Control: Open/Close Bottom Sheet
        function openSheet(price, id) {
            document.getElementById('modalPrice').innerText = price;
            document.getElementById('videoID').value = id;
            // Always clear phone field and errors when switching videos
            var phoneEl = document.getElementById('phoneNumber');
            if (phoneEl) phoneEl.value = '';
            var errEl = document.getElementById('paymentError');
            if (errEl) errEl.style.display = 'none';
            var payBtn = document.querySelector('.pay-btn-final');
            if (payBtn) { payBtn.disabled = false; payBtn.style.background = 'var(--tz-green)'; payBtn.innerHTML = 'LIPIA TSH ' + price; }
            document.getElementById('sheet').classList.add('show');
            document.getElementById('overlay').style.display = 'block';

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

        function closeSheet() {
            document.getElementById('sheet').classList.remove('show');
            document.getElementById('overlay').style.display = 'none';
            resetSheetPaymentForm();
        }


        // 3. Main Payment Logic
        async function submitPayment() {
            const phoneInput = document.getElementById('phoneNumber').value.trim();
            const videoID = document.getElementById('videoID').value;
            const amount = document.getElementById('modalPrice').innerText;
            const payBtn = document.querySelector('.pay-btn-final');

            // Simple validation for TZ numbers (9 digits after +255)
            if (phoneInput.length < 9) {
                showError('payment', "Tafadhali weka namba sahihi ya simu.");
                return;
            }

            const formattedPhone = "0" + phoneInput;

            // UI Feedback: Loading state
            payBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> TUNATUMA...';
            payBtn.disabled = true;

            try {
                const baseUrl = '<?= defined("BASE_URL") ? BASE_URL : "" ?>';
                const data = await fetchAPI(baseUrl + '/api/process_payment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ video_id: videoID, phone: formattedPhone })
                });

                if (data.status === 'success') {
                    // Successful push sent, now wait for PIN
                    payBtn.innerHTML = '<i class="fas fa-key"></i> WEKA PIN KWA SIMU...';
                    checkStatus(data.tranID, videoID);
                } else {
                    showError('payment', 'Tatizo: ' + (data.message || 'Payment initiation failed'));
                    resetPayButton(payBtn, amount);
                }
            } catch (error) {
                console.error('JS Error:', error);
                showError('payment', 'Mtandao unasumbua. Jaribu tena baadae.\n\n[Details]:\n' + error.message);
                resetPayButton(payBtn, amount);
            }
        }

        // 4. Polling: Checking if user entered their PIN
        function checkStatus(transactionId, videoID) {
            if (paymentCheckInterval) clearInterval(paymentCheckInterval);

            const baseUrl = '<?= defined("BASE_URL") ? BASE_URL : "" ?>';
            paymentCheckInterval = setInterval(async () => {
                try {
                    const statusData = await fetchAPI(`${baseUrl}/api/check_payment.php?tranid=${transactionId}`);
                    const payBtn = document.querySelector('.pay-btn-final');
                    const s = (statusData.payment_status || statusData.status || '').toLowerCase();

                    if (s === 'completed' || s === 'success') {
                        clearInterval(paymentCheckInterval);

                        // Success UI
                        payBtn.style.background = '#1eb53a';
                        payBtn.innerHTML = '<i class="fas fa-check"></i> TAYARI! KAFUNGUA...';
                        
                        // Set cookies
                        const expires = new Date(Date.now() + 24 * 60 * 60 * 1000).toUTCString();
                        const creatorId = '<?= isset($domain_owner["user_id"]) ? $domain_owner["user_id"] : (isset($user_videos[0]["user_id"]) ? $user_videos[0]["user_id"] : "") ?>';
                        document.cookie = "sf_video_" + videoID + "=" + encodeURIComponent(transactionId) + "; expires=" + expires + "; path=/; SameSite=Lax";
                        document.cookie = "sf_pass_" + creatorId + "=" + encodeURIComponent(transactionId) + "; expires=" + expires + "; path=/; SameSite=Lax";

                        setTimeout(() => {
                            // Route: channel → streaming.php | single → watch.php
                        const _wid = statusData.video_id || videoID;
                        if (statusData.monetization_mode === 'channel') {
                            window.location.href = baseUrl + '/streaming.php?creator_id=' + creatorId;
                        } else if (_wid) {
                            window.location.href = baseUrl + '/watch.php?id=' + _wid;
                        } else if (statusData.global_redirect_url) {
                            window.location.href = statusData.global_redirect_url;
                        } else {
                            window.location.href = baseUrl + '/streaming.php?creator_id=' + creatorId;
                        }
                        }, 1500);
                    } else if (s === 'failed' || s === 'cancelled') {
                        clearInterval(paymentCheckInterval);
                        showError('payment', 'Malipo yameshindikana au yameghairiwa.');
                        resetPayButton(payBtn, document.getElementById('modalPrice').innerText);
                    }
                } catch (e) {
                    console.error("Status check failed", e);
                }
            }, 5000);
        }

        // 5. Reset Button Utility
        function resetPayButton(btn, price) {
            btn.innerHTML = `LIPIA TSH ${price}`;
            btn.disabled = false;
            btn.style.background = 'var(--tz-green)';
        }

        function resetSheetPaymentForm() {
            // stop polling
            if (paymentCheckInterval) {
                clearInterval(paymentCheckInterval);
                paymentCheckInterval = null;
            }

            // reset inputs
            document.getElementById('phoneNumber').value = '';
            document.getElementById('videoID').value = '';

            // reset button
            const btn = document.querySelector('.pay-btn-final');
            if (btn) {
                const price = document.getElementById('modalPrice').innerText;
                btn.innerHTML = `LIPIA TSH ${price}`;
                btn.disabled = false;
                btn.style.background = 'var(--tz-green)';
            }
        }
    </script>
</body>

</html>
