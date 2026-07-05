<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($platform_name) ?> | Premium Streaming</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
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
            --primary: #e50914;
            --primary-hover: #f40612;
            --bg: #0f0f0f;
            --card-bg: #181818;
            --text-main: #ffffff;
            --text-dim: #b3b3b3;
            --premium: #ffd700;
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
            --safe-left: env(safe-area-inset-left, 0px);
            --safe-right: env(safe-area-inset-right, 0px);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            font-size: 16px;
            scroll-behavior: smooth;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text-main);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            -webkit-tap-highlight-color: transparent;
            overflow-x: hidden;
            padding-top: var(--safe-top);
            padding-bottom: var(--safe-bottom);
        }

        /* Responsive Typography */
        @media (max-width: 768px) {
            html {
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            html {
                font-size: 13px;
            }
        }

        /* Improved Navbar */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            padding: 12px max(5%, 16px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(to bottom, rgba(15, 15, 15, 0.95) 0%, rgba(15, 15, 15, 0.8) 100%);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: calc(12px + var(--safe-top));
        }

        .logo {
            font-size: clamp(1.25rem, 4vw, 1.5rem);
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
            letter-spacing: -0.5px;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: clamp(12px, 3vw, 20px);
        }

        .nav-right i {
            font-size: clamp(1rem, 3vw, 1.2rem);
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: background-color 0.2s;
        }

        .nav-right i:active {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .avatar {
            width: clamp(32px, 8vw, 40px);
            height: clamp(32px, 8vw, 40px);
            background: var(--primary);
            border-radius: 4px;
            display: grid;
            place-items: center;
            font-weight: bold;
            font-size: clamp(0.7rem, 2vw, 0.8rem);
        }

        /* Enhanced Hero Section */
        .hero {
            min-height: 80vh;
            max-height: 90vh;
            position: relative;
            display: flex;
            align-items: flex-end;
            padding: 80px max(5%, 16px) clamp(40px, 8vh, 60px);
            margin-top: calc(60px + var(--safe-top));
            background: linear-gradient(to top, var(--bg) 0%, transparent 30%),
                linear-gradient(to right, rgba(0, 0, 0, 0.9) 0%, transparent 50%),
                linear-gradient(to left, rgba(0, 0, 0, 0.4) 0%, transparent 30%),
                url('..../assets/landing/landing2.png') center/cover no-repeat;
        }

        @media (max-width: 768px) {
            .hero {
                min-height: 70vh;
                padding: 60px max(4%, 12px) 30px;
                background: linear-gradient(to top, var(--bg) 0%, transparent 20%),
                    linear-gradient(to right, rgba(0, 0, 0, 0.8) 0%, transparent 40%),
                    url('..../assets/landing/landing2.png') center/cover no-repeat;
            }
        }

        @media (max-width: 480px) {
            .hero {
                min-height: 60vh;
                padding: 50px max(3%, 8px) 20px;
            }
        }

        .hero-content {
            width: 100%;
            max-width: min(800px, 90vw);
            z-index: 2;
        }

        .hero-tag {
            background: var(--primary);
            padding: 6px 14px;
            border-radius: 4px;
            font-size: clamp(0.65rem, 1.5vw, 0.75rem);
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: clamp(12px, 3vh, 20px);
        }

        .hero-title {
            font-size: clamp(2rem, 7vw, 3.5rem);
            font-weight: 800;
            margin-bottom: clamp(10px, 2vh, 15px);
            line-height: 1.1;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .hero-meta {
            color: var(--text-dim);
            font-size: clamp(0.8rem, 2vw, 0.95rem);
            margin-bottom: clamp(15px, 4vh, 25px);
            display: flex;
            flex-wrap: wrap;
            gap: clamp(10px, 2vw, 15px);
            align-items: center;
        }

        .hero-meta span {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .hero-meta span[style*="border"] {
            padding: 2px 8px;
            border: 1px solid #666;
            border-radius: 4px;
            font-size: 0.75em;
        }

        /* Button Improvements */
        .btn {
            padding: clamp(12px, 2.5vh, 16px) clamp(20px, 4vw, 30px);
            border-radius: 8px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: clamp(0.9rem, 2vw, 1rem);
            min-height: 44px;
            /* Minimum touch target size */
            min-width: 44px;
        }

        .btn-play {
            background: #fff;
            color: #000;
            width: 100%;
            max-width: 300px;
        }

        .btn-play:hover,
        .btn-play:active {
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        @media (max-width: 480px) {
            .btn-play {
                max-width: 100%;
            }
        }

        /* Grid System Improvements */
        .section-container {
            padding: clamp(30px, 5vw, 50px) max(5%, 16px);
        }

        .row-header {
            margin-bottom: clamp(15px, 3vw, 25px);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .row-title {
            font-size: clamp(1.1rem, 4vw, 1.5rem);
            font-weight: 700;
        }

        .row-title i {
            color: var(--primary);
        }

        /* Enhanced Movie Grid */
        .movie-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(180px, 45vw), 1fr));
            gap: clamp(12px, 3vw, 20px);
            grid-auto-rows: 1fr;
        }

        @media (max-width: 1024px) {
            .movie-grid {
                grid-template-columns: repeat(auto-fill, minmax(min(150px, 40vw), 1fr));
            }
        }

        @media (max-width: 768px) {
            .movie-grid {
                grid-template-columns: repeat(auto-fill, minmax(min(140px, 45vw), 1fr));
                gap: 12px;
            }
        }

        @media (max-width: 480px) {
            .movie-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
        }

        @media (max-width: 360px) {
            .movie-grid {
                grid-template-columns: 1fr;
                max-width: 300px;
                margin: 0 auto;
            }
        }

        /* Improved Movie Card */
        .movie-card {
            background: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            aspect-ratio: 2/3;
            display: flex;
            flex-direction: column;
        }

        .movie-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
        }

        /* Mobile hover removal */
        @media (hover: none) {
            .movie-card:hover {
                transform: none;
                box-shadow: none;
            }

            .movie-card:active {
                transform: scale(0.98);
                opacity: 0.9;
            }
        }

        .card-thumb {
            flex: 1;
            min-height: 0;
            background: #222;
            position: relative;
            overflow: hidden;
        }

        .card-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .movie-card:hover .card-thumb img {
            transform: scale(1.05);
        }

        .badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: var(--primary);
            padding: 4px 10px;
            font-size: clamp(0.55rem, 1.5vw, 0.65rem);
            font-weight: 800;
            border-radius: 4px;
            z-index: 2;
            white-space: nowrap;
        }

        .price-tag {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.85);
            color: var(--premium);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: clamp(0.65rem, 1.5vw, 0.75rem);
            font-weight: 700;
            border: 1px solid var(--premium);
            z-index: 2;
        }

        .card-info {
            padding: clamp(10px, 2vw, 15px);
            background: var(--card-bg);
            flex-shrink: 0;
        }

        .card-info h3 {
            font-size: clamp(0.85rem, 2vw, 0.95rem);
            margin-bottom: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.3;
            min-height: 2.4em;
        }

        .card-info p {
            font-size: clamp(0.7rem, 1.5vw, 0.8rem);
            color: var(--text-dim);
            display: flex;
            align-items: center;
            gap: 4px;
            flex-wrap: wrap;
        }

        /* Enhanced Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            padding: max(20px, var(--safe-top)) max(16px, var(--safe-left)) max(20px, var(--safe-bottom)) max(16px, var(--safe-right));
        }

        .modal-content {
            background: var(--card-bg);
            width: 100%;
            max-width: min(450px, 95vw);
            border-radius: 16px;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: modalSlideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            max-height: 90vh;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        @keyframes modalSlideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: clamp(20px, 4vw, 25px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            position: sticky;
            top: 0;
            background: var(--card-bg);
            z-index: 1;
            border-radius: 16px 16px 0 0;
        }

        .modal-body {
            padding: clamp(20px, 4vw, 25px);
        }

        .close-modal {
            position: absolute;
            top: 16px;
            right: 16px;
            color: var(--text-dim);
            cursor: pointer;
            font-size: 1.5rem;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s;
            z-index: 2;
        }

        .close-modal:hover,
        .close-modal:active {
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* Payment Options */
        .payment-method {
            background: #222;
            border: 2px solid #333;
            border-radius: 12px;
            padding: clamp(12px, 3vw, 16px);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
        }

        .payment-method:active {
            transform: scale(0.98);
        }

        .payment-method i {
            font-size: clamp(1.2rem, 3vw, 1.5rem);
            color: var(--premium);
        }

        .payment-method.active {
            border-color: var(--primary);
            background: rgba(229, 9, 20, 0.1);
        }

        .input-group {
            margin-top: 20px;
        }

        .input-group label {
            display: block;
            font-size: clamp(0.75rem, 2vw, 0.85rem);
            color: var(--text-dim);
            margin-bottom: 8px;
            font-weight: 600;
        }

        .input-group input {
            width: 100%;
            background: #222;
            border: 1px solid #444;
            padding: clamp(12px, 3vw, 16px);
            color: #fff;
            border-radius: 8px;
            outline: none;
            font-size: clamp(0.9rem, 2vw, 1rem);
            transition: border-color 0.2s;
            -webkit-appearance: none;
            appearance: none;
        }

        .input-group input:focus {
            border-color: var(--primary);
        }

        .btn-full {
            width: 100%;
            margin-top: 20px;
            justify-content: center;
            background: var(--primary);
            color: white;
            font-size: clamp(0.9rem, 2vw, 1rem);
            padding: clamp(14px, 3vh, 16px);
            border-radius: 10px;
            border: none;
            cursor: pointer;
        }

        .btn-full:hover,
        .btn-full:active {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }

        /* Improved responsive styles */
        @media (max-width: 768px) {
            .modal-content {
                max-height: 85vh;
            }

            .payment-method {
                padding: 14px;
            }

            .btn-full {
                min-height: 48px;
            }
        }

        @media (max-width: 480px) {
            .modal-header h2 {
                font-size: 1.3rem;
            }

            .payment-method {
                gap: 12px;
                padding: 12px;
            }

            .payment-method i {
                font-size: 1.3rem;
            }
        }

        /* Landscape mode optimization */
        @media (max-height: 600px) and (orientation: landscape) {
            .hero {
                min-height: 100vh;
            }

            .modal-content {
                max-height: 80vh;
            }
        }

        /* Ultra-wide screens */
        @media (min-width: 1600px) {
            .section-container {
                max-width: 1600px;
                margin: 0 auto;
            }

            .movie-grid {
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            }
        }

        /* Prevent text selection on interactive elements */
        .btn,
        .payment-method,
        .close-modal {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        /* Loading state */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        /* Hide scrollbar for modal on mobile */
        .modal-content::-webkit-scrollbar {
            width: 4px;
        }

        .modal-content::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 2px;
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
        <a href="#" class="logo"><?= htmlspecialchars($platform_name) ?></a>
        <div class="nav-right">
            <i class="fas fa-search" aria-label="Search"></i>
            <div class="avatar" aria-label="Adult Content">18+</div>
        </div>
    </nav>

    <header class="hero">
        <div class="hero-content">
            <span class="hero-tag"><i class="fas fa-crown" aria-hidden="true"></i> PREMIUM ACCESS</span>
            <h1 class="hero-title">Full Connection</h1>
            <div class="hero-meta">
                <span>98% Match</span>
                <span>2026</span>
                <span>18+</span>
            </div>
            <button class="btn btn-play" aria-label="Unlock all premium content">
                <i class="fas fa-play" aria-hidden="true"></i> UNLOCK ALL ACCESS
            </button>
        </div>
    </header>

    <main class="section-container">
        <div class="row-header">
            <h2 class="row-title"><i class="fas fa-fire" aria-hidden="true"></i> TRENDING NOW</h2>
        </div>
        <div class="movie-grid">
            <?php if (!empty($user_videos)): ?>
                <?php foreach ($user_videos as $video): ?>
                    <a href="#"
                       onclick="openPaymentModal(event, '<?= htmlspecialchars((string)($video['price'] ?? 1000)) ?>', '<?= htmlspecialchars((string)$video['id']) ?>')"
                       style="text-decoration: none; color: inherit;">
                        <div class="movie-card">
                            <div class="card-thumb">
                                <img src="<?= htmlspecialchars($video['thumbnail_url'] ?? 'assets/images/default_thumb.jpg') ?>" alt="<?= htmlspecialchars($video['title']) ?>">
                                <span class="badge">PREMIUM</span>
                                <span class="price-tag">TSH <?= number_format($video['price'] ?? 1000) ?></span>
                            </div>
                            <div class="card-info">
                                <h3><?= htmlspecialchars($video['title']) ?></h3>
                                <p><?= number_format($video['views'] ?? 0) ?> views • <i class="fas fa-star" style="color:var(--premium)"></i></p>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-videos" style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--text-dim, #888);">
                    <i class="fas fa-video-slash" style="font-size: 3rem; margin-bottom: 15px; color: var(--premium, #ff416c);"></i>
                    <p style="font-size: 1.1rem; font-weight: 600;">No videos uploaded yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <div class="modal-overlay" id="paymentModal" aria-hidden="true" role="dialog">
        <div class="modal-content">
            <i class="fas fa-times close-modal" onclick="closePaymentModal()" aria-label="Close modal"></i>
            <div class="modal-header">
                <h2 style="color: var(--premium)"><i class="fas fa-shield-alt" aria-hidden="true"></i> Secure Checkout
                </h2>
                <p style="font-size: 0.8rem; color: var(--text-dim)">Unlock Premium Video Content</p>
            </div>
            <div class="modal-body">
                <input type="hidden" id="videoID" value="">
                <div style="text-align: center; margin-bottom: 20px;">
                    <span style="font-size: 0.9rem; color: var(--text-dim)">Amount to Pay:</span>
                    <h1 id="modalPrice">TSH 0</h1>
                </div>
                <label style="font-size: 0.8rem; color: var(--text-dim); margin-bottom: 10px; display: block;">
                    Select Payment Method:
                </label>
                <div class="payment-method active">
                    <i class="fas fa-mobile-alt" aria-hidden="true"></i>
                    <div>
                        <strong>Mobile Money</strong>
                        <p style="font-size: 0.7rem; color: var(--text-dim)">M-Pesa, Tigo Pesa, Airtel Money</p>
                    </div>
                </div>
                <div id="paymentError" class="modal-error"></div>
                <div class="input-group">
                    <label for="phoneNumber">Enter Phone Number (0xxx xxx xxx)</label>
                    <input type="tel" id="phoneNumber" placeholder="0700 000 000" inputmode="tel" pattern="[0-9]*">
                </div>
                <button class="btn btn-full" onclick="processPayment()" aria-label="Pay securely now">
                    PAY SECURELY NOW
                </button>
                <button class="btn" onclick="closePaymentModal()" aria-label="Ghairi - Rudi" style="width:100%;margin-top:10px;background:transparent;border:1px solid rgba(255,255,255,0.2);color:var(--text-dim);font-size:clamp(0.85rem,2vw,0.95rem);justify-content:center;">
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

        function openPaymentModal(event, price, videoId) {
            event.preventDefault();
            document.getElementById('modalPrice').innerText = 'TSH ' + price;
            document.getElementById('videoID').value = videoId;
            // Always clear phone field when switching videos
            var phoneEl = document.getElementById('phoneNumber') || document.getElementById('phoneInput');
            if (phoneEl) phoneEl.value = '';
            // Clear any previous errors
            var errEl = document.getElementById('paymentError');
            if (errEl) errEl.style.display = 'none';
            document.getElementById('paymentModal').style.display = 'flex';

            // Track view & click CTA
            fetch('<?= BASE_URL ?>/api/track', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ video_id: videoId, event_type: 'view' }),
                keepalive: true
            }).catch(err => {});
            fetch('<?= BASE_URL ?>/api/track', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ video_id: videoId, event_type: 'click_cta' }),
                keepalive: true
            }).catch(err => {});
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
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

