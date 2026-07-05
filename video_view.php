<?php
require_once 'includes/db.php';

$slug = filter_input(INPUT_GET, 'v', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$video_id = filter_input(INPUT_GET, 'video_id', FILTER_SANITIZE_NUMBER_INT);
$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if (!$slug && !$video_id) {
    die("Video not found.");
}

if ($slug) {
    $stmt = $pdo->prepare("SELECT v.*, u.full_name as author_name, u.avatar as author_avatar, u.monetization_mode FROM videos v JOIN users u ON v.user_id = u.id WHERE v.slug = ? AND v.status = 'active'");
    $stmt->execute([$slug]);
} else {
    $stmt = $pdo->prepare("SELECT v.*, u.full_name as author_name, u.avatar as author_avatar, u.monetization_mode FROM videos v JOIN users u ON v.user_id = u.id WHERE v.id = ? AND v.status = 'active'");
    $stmt->execute([$video_id]);
}
$video = $stmt->fetch();

if (!$video) {
    die("Video not found or is unavailable.");
}

$hasAccess = false;
$isAdmin = isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin';

if ($isAdmin) {
    $hasAccess = true;
} else {
    // Try to resolve token from GET param or from cookies to handle dynamic IP changes on mobile networks
    $active_token = $token;
    if (!$active_token) {
        $active_token = $_COOKIE['sf_video_' . $video['id']] ?? '';
    }
    if (!$active_token && $video['monetization_mode'] === 'channel') {
        $active_token = $_COOKIE['sf_channel_' . $video['user_id']] ?? '';
    }

    if ($active_token) {
        // Check if the token grants access to this video or the vendor's channel
        $stmtAccess = $pdo->prepare("
            SELECT * FROM video_access 
            WHERE reference = ? AND status = 'completed' AND expires_at > NOW()
        ");
        $stmtAccess->execute([$active_token]);
        $access = $stmtAccess->fetch();
        
        if ($access) {
            if ($access['video_id'] == $video['id']) {
                $hasAccess = true;
            } elseif ($video['monetization_mode'] === 'channel' && $access['vendor_id'] == $video['user_id']) {
                $hasAccess = true;
            }
            
            // If access is valid, ensure the cookie is set/refreshed for 24h
            if ($hasAccess) {
                $cookie_expiry = strtotime($access['expires_at']);
                if ($cookie_expiry > time()) {
                    $cookie_name = ($video['monetization_mode'] === 'channel') 
                        ? 'sf_channel_' . $video['user_id'] 
                        : 'sf_video_' . $video['id'];
                    setcookie($cookie_name, $active_token, [
                        'expires' => $cookie_expiry,
                        'path' => '/',
                        'samesite' => 'Lax'
                    ]);
                }
            }
        }
    }
}

// Generate URL for SEO tags
$page_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <title><?= htmlspecialchars($video['title']) ?> - <?= htmlspecialchars($platform_name) ?></title>
    <meta name="description" content="Watch <?= htmlspecialchars($video['title']) ?> on <?= htmlspecialchars($platform_name) ?>.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= htmlspecialchars($page_url) ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="video.other">
    <meta property="og:url" content="<?= htmlspecialchars($page_url) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($video['title']) ?>">
    <meta property="og:description" content="Watch <?= htmlspecialchars($video['title']) ?> on <?= htmlspecialchars($platform_name) ?>.">
    <meta property="og:image" content="<?= htmlspecialchars($video['thumbnail_url']) ?>">
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= htmlspecialchars($page_url) ?>">
    <meta name="twitter:title" content="<?= htmlspecialchars($video['title']) ?>">
    <meta name="twitter:description" content="Watch <?= htmlspecialchars($video['title']) ?> on <?= htmlspecialchars($platform_name) ?>.">
    <meta name="twitter:image" content="<?= htmlspecialchars($video['thumbnail_url']) ?>">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Context menu prevention */
        img, video { -webkit-touch-callout: none; -webkit-user-select: none; -khtml-user-select: none; -moz-user-select: none; -ms-user-select: none; user-select: none; }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen" oncontextmenu="return false;">

    <div class="max-w-4xl mx-auto p-4 py-8">
        <!-- Creator Header -->
        <div class="flex items-center gap-4 mb-6 pb-6 border-b border-gray-800">
            <img src="<?= htmlspecialchars($video['author_avatar'] ?? 'https://ui-avatars.com/api/?name='.urlencode($video['author_name']).'&background=0D8ABC&color=fff') ?>" alt="Creator" class="w-12 h-12 rounded-full border-2 border-blue-500">
            <div>
                <h1 class="text-xl font-bold"><?= htmlspecialchars($video['title']) ?></h1>
                <p class="text-sm text-gray-400">By <?= htmlspecialchars($video['author_name']) ?></p>
            </div>
        </div>

        <!-- Video Player Container -->
        <div class="relative bg-black rounded-xl overflow-hidden shadow-2xl aspect-video border border-gray-800 group" id="videoContainer">
            <!-- Thumbnail Overlay -->
            <div id="thumbnailOverlay" class="absolute inset-0 z-10 cursor-pointer bg-cover bg-center" style="background-image: url('<?= htmlspecialchars($video['thumbnail_url']) ?>');">
                <div class="absolute inset-0 bg-black/40 flex items-center justify-center group-hover:bg-black/30 transition-all">
                    <div class="w-20 h-20 bg-blue-600/90 rounded-full flex items-center justify-center transform group-hover:scale-110 transition-transform shadow-lg shadow-blue-500/50">
                        <svg class="w-10 h-10 text-white ml-2" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4l12 6-12 6z"></path></svg>
                    </div>
                </div>
            </div>

            <!-- Actual Video -->
            <video id="mainVideo" class="w-full h-full object-contain hidden" playsinline controlsList="nodownload">
                <source src="<?= htmlspecialchars($video['video_url']) ?>" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>
        
        <!-- Description / Payment CTA -->
        <div class="mt-8 bg-gray-800/50 rounded-xl p-6 border border-gray-700">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-200">Unlock Full Access</h3>
                    <p class="text-gray-400 text-sm mt-1">Purchase to watch the complete video and support the creator.</p>
                </div>
                <button onclick="triggerPayment()" class="w-full md:w-auto px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors shadow-lg shadow-blue-600/30 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    Pay TZS <?= number_format($video['price'] ?? 1000, 2) ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Analytics & Interactions Script -->
    <script>
        const videoId = <?= json_encode($video['id']) ?>;
        
        function trackEvent(eventType, details = {}) {
            const data = {
                video_id: videoId,
                event_type: eventType,
                ...details
            };
            
            fetch('<?= BASE_URL ?>/api/track', {
                method: 'POST',
                body: JSON.stringify(data),
                headers: { 'Content-Type': 'application/json' },
                keepalive: true
            }).catch(err => console.log('Tracking error', err));
        }

        // Track page view on load
        window.addEventListener('DOMContentLoaded', () => {
            trackEvent('view');
        });

        // Handle Play button click
        const thumbnailOverlay = document.getElementById('thumbnailOverlay');
        const mainVideo = document.getElementById('mainVideo');
        const hasAccess = <?= json_encode($hasAccess) ?>;

        thumbnailOverlay.addEventListener('click', () => {
            trackEvent('play');
            thumbnailOverlay.style.display = 'none';
            mainVideo.classList.remove('hidden');
            mainVideo.play();
            
            if (!hasAccess) {
                // Redirect after 7 seconds of viewing
                setTimeout(() => {
                    triggerPayment();
                }, 7000);
            }
        });

        function triggerPayment() {
            trackEvent('click_cta');
            // Check monetization mode for smart redirect
            const mode = <?= json_encode($video['monetization_mode'] ?? 'single') ?>;
            const vendorId = <?= json_encode($video['user_id']) ?>;
            if (mode === 'channel') {
                window.location.href = 'channel.php?vendor_id=' + vendorId;
            } else {
                window.location.href = 'pay?v=' + <?= json_encode($video['slug']) ?>;
            }
        }
    </script>
</body>
</html>
