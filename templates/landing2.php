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
        'primary_color' => '#00A8E1',
        'secondary_color' => '#008fbe',
        'bg_color' => '#0f171e',
        'hero_title' => 'Karibu ' . $platform_name . ' Streaming',
        'hero_description' => 'Jifunze kidijitali na uangalie video bora zaidi hapa.',
        'hero_image' => 'assets/defaults/landing-bg.jpg',
        'favicon' => null
    ];
}

// Ensure required fields are populated
if (empty($landing_settings['site_name']))        $landing_settings['site_name']        = $platform_name;
if (empty($landing_settings['hero_title']))       $landing_settings['hero_title']       = 'Karibu ' . $platform_name . ' Streaming';
if (empty($landing_settings['cta_text']))         $landing_settings['cta_text']         = 'ANZA SASA';
if (empty($landing_settings['primary_color']))    $landing_settings['primary_color']    = '#00A8E1';
if (empty($landing_settings['secondary_color']))  $landing_settings['secondary_color']  = '#008fbe';
if (empty($landing_settings['bg_color']))         $landing_settings['bg_color']         = '#0f171e';
if (empty($landing_settings['hero_description'])) $landing_settings['hero_description'] = 'Jifunze kidijitali na uangalie video bora zaidi hapa.';
if (empty($landing_settings['hero_image']))       $landing_settings['hero_image']       = 'assets/defaults/landing-bg.jpg';

// Resolve hero background image URL
$hero_bg_image = $landing_settings['hero_image'];
if (strpos($hero_bg_image, 'http://') !== 0 && strpos($hero_bg_image, 'https://') !== 0 && strpos($hero_bg_image, '/') !== 0) {
    $hero_bg_image = BASE_URL . '/' . $hero_bg_image;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($landing_settings['site_name']) ?> | Prime Streaming</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-WFSQPTZZLJ"></script>
    <script>

      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-WFSQPTZZLJ');
    </script>
    <style>
        :root {
            --primary: <?= htmlspecialchars($landing_settings['primary_color']) ?>;
            --bg: <?= htmlspecialchars($landing_settings['bg_color']) ?>;
            --card-bg: #1b2530;
            --text-main: #ffffff;
            --text-dim: #8197a4;
            --nav-bg: #1a242f;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: var(--bg);
            color: var(--text-main);
            line-height: 1.4;
        }

        /* --- Prime Solid Navbar --- */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            padding: 0 4%;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--nav-bg);
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 900;
            color: #fff;
            text-decoration: none;
            letter-spacing: -1px;
        }

        .logo span {
            color: var(--primary);
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 25px;
            color: var(--text-dim);
            font-size: 1.1rem;
        }

        /* --- Prime Wide Hero --- */
        .hero {
            height: 75vh;
            position: relative;
            display: flex;
            align-items: center;
            padding: 0 4%;
            background: linear-gradient(to right, var(--bg) 0%, rgba(15, 23, 30, 0.4) 50%, transparent 100%),
                linear-gradient(to top, var(--bg) 0%, transparent 20%),
                url('<?= $hero_bg_image ?>') center/cover;
        }

        .hero-content {
            max-width: 500px;
            z-index: 2;
        }

        .hero-title {
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 700;
            margin-bottom: 15px;
            line-height: 1.1;
        }

        .hero-desc {
            color: var(--text-dim);
            font-size: 1.1rem;
            margin-bottom: 25px;
        }

        .btn {
            padding: 14px 40px;
            border-radius: 4px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: 0.2s;
            text-decoration: none;
            font-size: 1rem;
        }

        .btn-play {
            background: var(--primary);
            color: #fff;
        }

        .btn-play:hover {
            opacity: 0.9;
        }

        /* --- Grid System (16:9 Aspect) --- */
        .section-container {
            padding: 40px 4%;
        }

        .row-header {
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .row-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #fff;
        }

        .movie-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
        }

        .movie-card {
            background: transparent;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .card-thumb {
            width: 100%;
            aspect-ratio: 16/9;
            position: relative;
            border-radius: 4px;
            overflow: hidden;
            background: #252e39;
        }

        .card-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: opacity 0.3s;
        }

        .movie-card:hover {
            transform: scale(1.05);
            z-index: 10;
        }

        .movie-card:hover .card-thumb {
            outline: 3px solid #fff;
            outline-offset: -3px;
        }

        .badge-premium {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--primary);
            color: white;
            padding: 4px 8px;
            font-size: 0.65rem;
            font-weight: 700;
            border-radius: 2px;
        }

        .card-info {
            padding: 10px 0;
        }

        .card-info h3 {
            font-size: 0.95rem;
            font-weight: 500;
            color: #fff;
            margin-bottom: 2px;
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis;
        }

        .card-info p {
            font-size: 0.8rem;
            color: var(--text-dim);
        }

        /* --- Prime Style Modal --- */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.85);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            padding: 20px;
        }

        .modal-content {
            background: var(--bg);
            width: 100%;
            max-width: 480px;
            border: 1px solid #303c4a;
            padding: 40px;
            position: relative;
        }

        .modal-header h2 {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .payment-method {
            border: 1px solid #303c4a;
            padding: 20px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
        }

        .payment-method.active {
            border-color: var(--primary);
            background: rgba(0, 168, 225, 0.05);
        }

        .input-group input {
            width: 100%;
            background: #fff;
            border: 1px solid #8197a4;
            padding: 14px;
            color: #000;
            font-size: 1rem;
            margin-top: 10px;
        }

        .btn-full {
            width: 100%;
            margin-top: 20px;
            background: var(--primary);
            color: white;
            justify-content: center;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Packages Grid */
        .pkg-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin: 20px 0;
        }

        .pkg-item {
            border: 1px solid #303c4a;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: 0.3s;
        }

        .pkg-item.active {
            border-color: var(--primary);
            background: rgba(0, 168, 225, 0.05);
        }

        .pkg-radio {
            width: 18px;
            height: 18px;
            border: 2px solid #8197a4;
            border-radius: 50%;
            position: relative;
        }

        .pkg-item.active .pkg-radio {
            border-color: var(--primary);
        }

        .pkg-item.active .pkg-radio::after {
            content: '';
            position: absolute;
            inset: 3px;
            background: var(--primary);
            border-radius: 50%;
        }

        @media (max-width: 768px) {
            .movie-grid {
                grid-template-columns: repeat(1, 1fr);
            }

            .hero {
                height: 50vh;
            }

            .hero-title {
                font-size: 1.8rem;
            }
            .modal-content {
                padding: 20px;
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

    <nav class="navbar">
        <a href="#" class="logo"><?= htmlspecialchars($landing_settings['site_name']) ?></a>
        <div class="nav-right">
            <i class="fas fa-search"></i>
            <i class="fas fa-user-circle"></i>
        </div>
    </nav>

    <header class="hero">
        <div class="hero-content">
            <h1 class="hero-title"><?= htmlspecialchars($landing_settings['hero_title']) ?></h1>
            <p class="hero-desc"><?= htmlspecialchars($landing_settings['hero_description']) ?></p>
            <button class="btn btn-play" onclick="openPackageModal()">
                <i class="fas fa-play"></i> <?= htmlspecialchars($landing_settings['cta_text']) ?>
            </button>
        </div>
    </header>

    <main class="section-container">
        <div class="row-header">
            <h2 class="row-title">Included with <?= htmlspecialchars($landing_settings['site_name']) ?></h2>
        </div>

        <div class="movie-grid">
            <?php if (!empty($user_videos)): ?>
                <?php foreach ($user_videos as $video): ?>
                    <div class="movie-card"
                         data-id="<?= htmlspecialchars((string)$video['id']) ?>"
                         data-price="<?= htmlspecialchars((string)($video['price'] ?? 1000)) ?>"
                         data-title="<?= htmlspecialchars($video['title']) ?>"
                         data-slug="<?= htmlspecialchars($video['slug']) ?>"
                         data-paid="0"
                         onclick="openPaymentModal(event, '<?= htmlspecialchars((string)($video['price'] ?? 1000)) ?>', '<?= htmlspecialchars((string)$video['id']) ?>')">

                        <div class="card-thumb">
                            <img src="<?= htmlspecialchars($video['thumbnail_url'] ?? 'assets/images/default_thumb.jpg') ?>" alt="Thumbnail">
                            <div class="badge-premium"><i class="fas fa-shopping-cart"></i> RENT TSH <?= number_format($video['price'] ?? 1000) ?></div>
                        </div>

                        <div class="card-info">
                            <h3><?= htmlspecialchars($video['title']) ?></h3>
                            <p><?= number_format($video['views'] ?? 0) ?> views • Premium Video</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--text-dim);">
                    <i class="fas fa-video-slash" style="font-size: 3rem; margin-bottom: 15px;"></i>
                    <p style="font-size: 1.1rem; font-weight: 600;">No videos uploaded yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Package Modal -->
    <div class="modal-overlay" id="packageModal">
        <div class="modal-content">
            <i class="fas fa-times"
                style="position: absolute; top: 20px; right: 20px; cursor: pointer; color: var(--text-dim);"
                onclick="closeModals()"></i>

            <div class="modal-header">
                <h2>Premium Access</h2>
                <p style="color: var(--text-dim);">Choose a package to unlock unlimited videos.</p>
            </div>

            <div class="pkg-list">
                <?php
                $stmt = $pdo->prepare("SELECT * FROM packages WHERE user_id = ? ORDER BY price ASC");
                $stmt->execute([$domain_owner['user_id']]);
                $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($packages)):
                    foreach ($packages as $pkg):
                ?>
                    <div class="pkg-item" onclick="selectPkg('<?= $pkg['id'] ?>', this)">
                        <div>
                            <strong style="display: block;"><?= htmlspecialchars($pkg['name']) ?></strong>
                            <span style="font-size: 0.8rem; color: var(--text-dim)"><?= htmlspecialchars($pkg['duration_days'] ?? 30) ?> Days Access</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <strong><?= number_format($pkg['price']) ?>/=</strong>
                            <div class="pkg-radio"></div>
                        </div>
                    </div>
                <?php 
                    endforeach;
                else:
                ?>
                    <div class="pkg-item active" onclick="selectPkg('default', this)">
                        <div>
                            <strong style="display: block;">Full Pass</strong>
                            <span style="font-size: 0.8rem; color: var(--text-dim)">24 Hours Access</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <strong>1,000/=</strong>
                            <div class="pkg-radio"></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="input-group">
                <label style="font-size: 0.9rem; font-weight: 700;">Enter phone number</label>
                <input type="tel" placeholder="07XXXXXXXX" id="pkgPhone">
            </div>
            <input type="hidden" id="selectedPkgId" value="<?= !empty($packages) ? htmlspecialchars((string)$packages[0]['id']) : 'default' ?>">
            
            <button class="btn btn-full" id="pkgBtn" onclick="processPackagePay()">
                SUBSCRIBE NOW
            </button>
        </div>
    </div>

    <!-- Video Payment Modal -->
    <div class="modal-overlay" id="paymentModal">
        <div class="modal-content">
            <i class="fas fa-times"
                style="position: absolute; top: 20px; right: 20px; cursor: pointer; color: var(--text-dim);"
                onclick="closePaymentModal()"></i>

            <div class="modal-header">
                <h2>Rent this video</h2>
                <p style="color: var(--text-dim);">Ready to watch? Rent this video instantly with Mobile Money.</p>
            </div>

            <input type="hidden" id="videoID" value="">

            <div class="payment-method active">
                <i class="fas fa-mobile-alt" style="font-size: 1.5rem; color: var(--primary);"></i>
                <div>
                    <strong id="modalPrice">TSH 0</strong>
                    <p style="font-size: 0.8rem; color: var(--text-dim)">Mobile Money (M-Pesa, Tigo, Airtel)</p>
                </div>
            </div>

            <div class="input-group">
                <label style="font-size: 0.9rem; font-weight: 700;">Enter phone number</label>
                <input type="tel" placeholder="07XXXXXXXX" id="phoneNumber">
            </div>

            <button class="btn btn-full" id="payBtn" onclick="processPayment()">
                Complete Rental
            </button>

            <button onclick="closePaymentModal()" style="width:100%;margin-top:10px;padding:12px;background:transparent;border:1px solid #303c4a;color:var(--text-dim);font-size:0.9rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;">
                <i class="fas fa-times-circle"></i> Ghairi
            </button>

            <p style="font-size: 0.75rem; color: var(--text-dim); text-align: center; margin-top: 20px;">
                Payments are secure and encrypted.
            </p>
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
        const BASE_URL = '<?= BASE_URL ?>';

        function openPackageModal() {
            document.getElementById('packageModal').style.display = 'flex';
        }

        function openPaymentModal(event, price, id) {
            if (event) event.preventDefault();

            document.getElementById('modalPrice').innerText = 'TSH ' + parseInt(price || '1000').toLocaleString();
            document.getElementById('videoID').value = id;
            // Always clear phone field and errors when switching videos
            var phoneEl = document.getElementById('phoneNumber');
            if (phoneEl) phoneEl.value = '';
            var errEl = document.getElementById('paymentError');
            if (errEl) errEl.style.display = 'none';
            var payBtn = document.getElementById('payBtn');
            if (payBtn) { payBtn.disabled = false; payBtn.innerHTML = 'Complete Rental'; }
            document.getElementById('paymentModal').style.display = 'flex';

            fetch(BASE_URL + '/api/track', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ video_id: id, event_type: 'click_cta' }),
                keepalive: true
            }).catch(err => console.error(err));
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
            if (paymentCheckInterval) clearInterval(paymentCheckInterval);
        }

        function closeModals() {
            document.querySelectorAll('.modal-overlay').forEach(m => m.style.display = 'none');
            if (paymentCheckInterval) clearInterval(paymentCheckInterval);
        }

        function selectPkg(id, el) {
            document.querySelectorAll('.pkg-item').forEach(i => i.classList.remove('active'));
            el.classList.add('active');
            document.getElementById('selectedPkgId').value = id;
        }

        async function fetchAPI(url, options) {
            const res = await fetch(url, options);
            const text = await res.text();
            try {
                return JSON.parse(text);
            } catch (err) {
                console.error("Non-JSON response from " + url, text);
                throw new Error("Server returned invalid response (Status: " + res.status + ").");
            }
        }

        function toggleLoading(id, isLoading) {
            const btn = document.getElementById(id);
            btn.disabled = isLoading;
            btn.innerHTML = isLoading ? 'Processing...' : (id === 'pkgBtn' ? 'SUBSCRIBE NOW' : 'Complete Rental');
        }

        async function processPackagePay() {
        hideError('package');
            const id = document.getElementById('selectedPkgId').value;
            const phone = document.getElementById('pkgPhone').value;
            if (!id) return showError('package', 'Please select a package');
            if (!/^0[0-9]{9}$/.test(phone)) return showError('package', 'Invalid phone number format. Use 07XXXXXXXX');

            toggleLoading('pkgBtn', true);
            try {
                const data = await fetchAPI(BASE_URL + "/api/process_package.php", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ package_id: id, phone })
                });
                handleResponse(data, 'pkgBtn', 'package', id);
            } catch (e) {
                toggleLoading('pkgBtn', false);
                showError('package', 'Connection Error. Try again.\n' + e.message);
            }
        }

        async function processPayment() {
        hideError('payment');
            const id = document.getElementById('videoID').value;
            const phone = document.getElementById('phoneNumber').value;
            if (!/^0[0-9]{9}$/.test(phone)) return showError('package', 'Invalid phone number format. Use 07XXXXXXXX');

            toggleLoading('payBtn', true);
            try {
                const data = await fetchAPI(BASE_URL + "/api/process_payment.php", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ video_id: id, phone })
                });
                handleResponse(data, 'payBtn', 'video', id);
            } catch (e) {
                toggleLoading('payBtn', false);
                showError('payment', 'Connection Error. Try again.\n' + e.message);
            }
        }

        function handleResponse(data, btnId, type, id) {
            if (data.status === 'success') {
                document.getElementById(btnId).innerHTML = 'Awaiting PIN...';
                checkStatus(data.tranID, type, id);
            } else {
                showError('payment', data.message || 'Payment failed. Try again.');
                toggleLoading(btnId, false);
            }
        }

        function checkStatus(txId, type, id) {
            if (paymentCheckInterval) clearInterval(paymentCheckInterval);
            paymentCheckInterval = setInterval(async () => {
                try {
                    const data = await fetchAPI(BASE_URL + `/api/check_payment.php?tranid=${txId}`);
                    const s = (data.payment_status || data.status || '').toLowerCase();
                    if (s === 'completed' || s === 'success') {
                        clearInterval(paymentCheckInterval);
                        
                        const expires = new Date(Date.now() + 24 * 60 * 60 * 1000).toUTCString();
                        const creatorId = '<?= isset($domain_owner["user_id"]) ? $domain_owner["user_id"] : (isset($user_videos[0]["user_id"]) ? $user_videos[0]["user_id"] : "") ?>';
                        
                        if (type === 'video') {
                            document.cookie = "sf_video_" + id + "=" + encodeURIComponent(txId) + "; expires=" + expires + "; path=/; SameSite=Lax";
                        } else if (type === 'package') {
                            document.cookie = "sf_channel_" + creatorId + "=" + encodeURIComponent(txId) + "; expires=" + expires + "; path=/; SameSite=Lax";
                        }
                        
                        document.cookie = "sf_pass_" + creatorId + "=" + encodeURIComponent(txId) + "; expires=" + expires + "; path=/; SameSite=Lax";
                        
                        // Route: channel → streaming.php | single → watch.php
                        const _wid = data.video_id || (type === 'video' ? id : null);
                        if (data.monetization_mode === 'channel') {
                            location.href = BASE_URL + '/streaming.php?creator_id=' + creatorId;
                        } else if (_wid) {
                            location.href = BASE_URL + '/watch.php?id=' + _wid;
                        } else if (data.global_redirect_url) {
                            location.href = data.global_redirect_url;
                        } else {
                            location.href = BASE_URL + `/streaming.php?creator_id=` + creatorId;
                        }
                    } else if (s === 'failed') {
                        clearInterval(paymentCheckInterval);
                        showError('payment', 'Payment failed. Try again.');
                        location.reload();
                    }
                } catch (e) {
                    console.error("Status check failed", e);
                }
            }, 3000);
        }
    </script>
</body>
</html>
