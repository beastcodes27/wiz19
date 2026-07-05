<?php
require_once __DIR__ . '/includes/db.php';

$vendorId = filter_input(INPUT_GET, 'vendor_id', FILTER_SANITIZE_NUMBER_INT);
$ref = filter_input(INPUT_GET, 'ref', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if (!$vendorId) {
    die("Vendor ID not provided.");
}

// Fetch vendor info
$stmt = $pdo->prepare("SELECT id, full_name, avatar, monetization_mode FROM users WHERE id = ?");
$stmt->execute([$vendorId]);
$vendor = $stmt->fetch();

if (!$vendor) {
    die("Vendor not found.");
}

$hasAccess = false;
$expires = "";
$isAdmin = isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin';

if ($isAdmin) {
    $hasAccess = true;
    $expires = "Unlimited (Admin Bypass)";
} else {
    // Try to resolve token from GET param or from cookies to handle dynamic IP changes on mobile networks
    $active_ref = $ref;
    if (!$active_ref) {
        $active_ref = $_COOKIE['sf_channel_' . $vendorId] ?? '';
    }
    
    if ($active_ref) {
        $stmt = $pdo->prepare("
            SELECT * FROM video_access 
            WHERE reference = ? AND vendor_id = ? AND status = 'completed'
        ");
        $stmt->execute([$active_ref, $vendorId]);
        $access = $stmt->fetch();
        
        if ($access) {
            if (strtotime($access['expires_at']) > time()) {
                $hasAccess = true;
                $expires = htmlspecialchars(date('M d, Y h:i A', strtotime($access['expires_at'])));
                
                // Refresh/set cookie for 24h
                setcookie('sf_channel_' . $vendorId, $active_ref, [
                    'expires' => strtotime($access['expires_at']),
                    'path' => '/',
                    'samesite' => 'Lax'
                ]);
            }
        }
    }
}

// Fetch all active videos for this vendor
$stmt = $pdo->prepare("SELECT * FROM videos WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC");
$stmt->execute([$vendorId]);
$videos = $stmt->fetchAll();

$vendorName = htmlspecialchars($vendor['full_name']);
$vendorAvatar = htmlspecialchars($vendor['avatar'] ?? 'https://ui-avatars.com/api/?name='.urlencode($vendor['full_name']).'&background=random');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $vendorName ?>'s Channel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #0f172a; color: #f8fafc; }
        .glass-panel { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body>

    <!-- Header / Banner -->
    <div class="relative h-48 md:h-64 w-full bg-gradient-to-r from-blue-900 to-indigo-900 overflow-hidden">
        <div class="absolute inset-0 bg-black/30"></div>
    </div>
    
    <!-- Profile Info -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative -mt-16">
        <div class="flex flex-col md:flex-row items-center md:items-end gap-6 pb-6 border-b border-gray-800">
            <img src="<?= $vendorAvatar ?>" class="w-32 h-32 rounded-full border-4 border-[#0f172a] shadow-xl object-cover">
            <div class="flex-1 text-center md:text-left">
                <h1 class="text-3xl font-bold"><?= $vendorName ?></h1>
                <p class="text-gray-400 mt-1"><?= count($videos) ?> Premium Videos</p>
            </div>
            
            <?php if ($hasAccess): ?>
                <div class="glass-panel px-6 py-3 rounded-xl text-center">
                    <div class="text-emerald-400 font-bold flex items-center gap-2">
                        <i class="fas fa-check-circle"></i> ACCESS GRANTED
                    </div>
                    <div class="text-xs text-gray-400 mt-1">Expires: <?= $expires ?></div>
                </div>
            <?php else: ?>
                <div class="glass-panel px-6 py-4 rounded-xl max-w-sm w-full">
                    <h3 class="font-bold mb-2 text-lg text-center md:text-left">Unlock Full Channel</h3>
                    <p class="text-sm text-gray-400 mb-4 text-center md:text-left">Get 24-hour access to all videos by <?= $vendorName ?>.</p>
                    
                    <!-- Quick Payment Form -->
                    <div class="flex flex-col gap-2">
                        <input type="tel" id="channelPhone" placeholder="Phone Number (e.g. 0700...)" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-blue-500">
                        <button onclick="payForChannel()" id="payBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-colors flex items-center justify-center gap-2">
                            <i class="fas fa-unlock"></i> Pay & Unlock
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Video Grid -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
            <i class="fas fa-play-circle text-blue-500"></i> Latest Uploads
        </h2>
        
        <?php if (empty($videos)): ?>
            <div class="text-center py-20 text-gray-500">
                <i class="fas fa-video-slash text-4xl mb-3"></i>
                <p>No videos available yet.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php foreach ($videos as $vid): 
                    $targetUrl = $hasAccess ? "video_view.php?video_id={$vid['id']}&token=".urlencode($ref ?? '') : "video_view.php?v={$vid['slug']}";
                ?>
                    <a href="<?= htmlspecialchars($targetUrl) ?>" class="group">
                        <div class="glass-panel rounded-xl overflow-hidden hover:scale-105 transition-transform duration-300 shadow-lg">
                            <!-- Thumbnail -->
                            <div class="aspect-video relative overflow-hidden bg-gray-800">
                                <img src="<?= htmlspecialchars($vid['thumbnail_url']) ?>" class="w-full h-full object-cover">
                                <?php if ($hasAccess): ?>
                                    <div class="absolute top-2 right-2 bg-emerald-500 text-white text-xs font-bold px-2 py-1 rounded">
                                        UNLOCKED
                                    </div>
                                <?php else: ?>
                                    <div class="absolute top-2 right-2 bg-rose-500 text-white text-xs font-bold px-2 py-1 rounded">
                                        LOCKED
                                    </div>
                                <?php endif; ?>
                                
                                <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                    <i class="fas fa-play-circle text-4xl text-white drop-shadow-lg"></i>
                                </div>
                            </div>
                            <!-- Details -->
                            <div class="p-4">
                                <h3 class="font-semibold text-gray-200 line-clamp-2 leading-snug group-hover:text-blue-400 transition-colors">
                                    <?= htmlspecialchars($vid['title']) ?>
                                </h3>
                                <div class="flex items-center gap-2 mt-2 text-sm text-gray-400">
                                    <span><?= number_format($vid['views']) ?> views</span>
                                    <span>•</span>
                                    <span><?= date('M j, Y', strtotime($vid['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Use the first video ID to initiate the channel payment, since payment goes to the vendor anyway
        <?php if (!empty($videos)): ?>
        const triggerVideoId = <?= json_encode($videos[0]['id']) ?>;
        <?php else: ?>
        const triggerVideoId = null;
        <?php endif; ?>
        
        async function payForChannel() {
            if (!triggerVideoId) {
                alert("No videos available to process payment.");
                return;
            }
            const phone = document.getElementById('channelPhone').value;
            if (!phone || phone.length < 10) {
                alert("Please enter a valid phone number.");
                return;
            }
            
            const btn = document.getElementById('payBtn');
            const ogText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Initiating...';
            btn.disabled = true;
            
            try {
                const res = await fetch('api/process_payment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ video_id: triggerVideoId, phone: phone })
                });
                
                const data = await res.json();
                if (data.status === 'success') {
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Awaiting PIN...';
                    
                    let attempts = 0;
                    const interval = setInterval(async () => {
                        attempts++;
                        if (attempts > 24) {
                            clearInterval(interval);
                            alert("Payment timed out.");
                            btn.innerHTML = ogText;
                            btn.disabled = false;
                            return;
                        }
                        
                        const pollRes = await fetch('api/check_payment.php?tranid=' + data.tranID);
                        const pollData = await pollRes.json();
                        
                        if (pollData.payment_status === 'COMPLETED') {
                            clearInterval(interval);
                            btn.innerHTML = '<i class="fas fa-check"></i> Success!';
                            setTimeout(() => {
                                window.location.href = '?vendor_id=<?= $vendorId ?>&ref=' + data.tranID;
                            }, 1000);
                        } else if (pollData.payment_status === 'FAILED') {
                            clearInterval(interval);
                            alert("Payment failed.");
                            btn.innerHTML = ogText;
                            btn.disabled = false;
                        }
                    }, 5000);
                } else {
                    alert("Error: " + data.message);
                    btn.innerHTML = ogText;
                    btn.disabled = false;
                }
            } catch (err) {
                alert("Connection error.");
                btn.innerHTML = ogText;
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
