<?php
require_once __DIR__ . '/includes/db.php';

$slug = filter_input(INPUT_GET, 'v', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
if (!$slug) {
    die("Video not found.");
}

// Fetch video and vendor details
$stmt = $pdo->prepare("SELECT v.*, u.full_name as author_name, u.avatar as author_avatar, u.monetization_mode FROM videos v JOIN users u ON v.user_id = u.id WHERE v.slug = ? AND v.status = 'active'");
$stmt->execute([$slug]);
$video = $stmt->fetch();

if (!$video) {
    die("Video not found or is unavailable.");
}

// Redirect to channel if vendor is in channel mode
if ($video['monetization_mode'] === 'channel') {
    header("Location: channel.php?vendor_id=" . $video['user_id']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay for <?= htmlspecialchars($video['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #0f172a; color: white; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-gray-800 border border-gray-700 rounded-2xl p-6 shadow-2xl">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-blue-500 mb-2">Secure Checkout</h2>
            <p class="text-gray-400 text-sm">Unlock Premium Content</p>
        </div>
        
        <div class="bg-gray-900 rounded-xl p-4 mb-6 border border-gray-700 flex gap-4 items-center">
            <img src="<?= htmlspecialchars($video['thumbnail_url']) ?>" class="w-20 h-20 object-cover rounded-lg">
            <div>
                <h3 class="font-semibold line-clamp-2"><?= htmlspecialchars($video['title']) ?></h3>
                <p class="text-sm text-gray-400 mt-1">By <?= htmlspecialchars($video['author_name']) ?></p>
            </div>
        </div>

        <div class="text-center mb-6">
            <p class="text-gray-400 text-sm mb-1">Amount to Pay</p>
            <h1 class="text-4xl font-bold">TZS <?= number_format($video['price'] ?? 1000) ?></h1>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-sm text-gray-400 mb-2">Mobile Number</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-mobile-alt text-gray-500"></i>
                    </div>
                    <input type="tel" id="phoneNumber" placeholder="0700 000 000" class="w-full bg-gray-900 border border-gray-700 rounded-xl py-3 pl-10 pr-4 text-white focus:outline-none focus:border-blue-500 transition-colors">
                </div>
            </div>

            <button onclick="processPayment()" id="payBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl transition-all shadow-lg shadow-blue-500/30 flex justify-center items-center gap-2">
                Pay Now
            </button>
        </div>
    </div>

    <script>
        const videoId   = <?= json_encode($video['id']) ?>;
        const PRICE_AMT = '<?= number_format($video['price'] ?? 1000) ?>';

        /* ── debounce guard ── */
        let payInProgress = false;

        /* ── on load: resume any pending payment from localStorage ── */
        window.addEventListener('DOMContentLoaded', () => {
            const storedTran = localStorage.getItem('pay_tran_' + videoId);
            const storedTs   = parseInt(localStorage.getItem('pay_tran_ts_' + videoId) || '0');
            if (storedTran && (Date.now() - storedTs) < 10 * 60 * 1000) {
                const btn = document.getElementById('payBtn');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking previous payment...';
                btn.disabled  = true;
                checkOnce(storedTran);
            }
        });

        async function processPayment() {
            if (payInProgress) return;

            /* ── normalize phone: strip spaces, handle +255 prefix ── */
            const rawPhone = document.getElementById('phoneNumber').value.trim()
                               .replace(/\s+/g, '').replace(/^\+255/, '0');

            /* ── Tanzania number validation: 07xx or 06xx, 10 digits ── */
            if (!/^0[67]\d{8}$/.test(rawPhone)) {
                alert('Please enter a valid Tanzanian number (07xx or 06xx, 10 digits).');
                return;
            }

            payInProgress = true;
            const btn    = document.getElementById('payBtn');
            const ogText = '<i class="fas fa-lock"></i> Pay Now – TZS ' + PRICE_AMT;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Initiating...';
            btn.disabled  = true;

            try {
                const res  = await fetch('api/process_payment.php', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify({ video_id: videoId, phone: rawPhone })
                });

                const data = await res.json();

                if (data.status === 'success') {
                    const tranID = data.tranID;

                    /* ── persist order so page reload / tab-close can recover ── */
                    localStorage.setItem('pay_tran_' + videoId, tranID);
                    localStorage.setItem('pay_tran_ts_' + videoId, Date.now());

                    btn.innerHTML = '<i class="fas fa-mobile-alt"></i> CHECK YOUR PHONE FOR PIN...';
                    startPoll(tranID, ogText);
                } else {
                    alert('Error: ' + (data.message || 'Payment initiation failed.'));
                    resetBtn(ogText);
                }
            } catch (err) {
                alert('Connection error. Please try again.\n\n' + err.message);
                resetBtn(ogText);
            }
        }

        function resetBtn(ogText) {
            payInProgress = false;
            const btn = document.getElementById('payBtn');
            if (btn) { btn.innerHTML = ogText || 'Pay Now'; btn.disabled = false; }
        }

        let pollInterval;
        function startPoll(tranID, ogText) {
            let attempts = 0;
            const MAX_ATTEMPTS = 36; /* 36 × 5s = 3 minutes */

            pollInterval = setInterval(async () => {
                attempts++;
                if (attempts > MAX_ATTEMPTS) {
                    clearInterval(pollInterval);
                    showRecovery(tranID, ogText);
                    return;
                }

                try {
                    const r = await fetch(
                        'api/check_payment.php?tranid=' + encodeURIComponent(tranID) + '&_=' + Date.now()
                    );
                    const d = await r.json();
                    const s = (d.payment_status || d.status || '').toUpperCase();

                    if (s === 'COMPLETED' || s === 'SUCCESS') {
                        clearInterval(pollInterval);
                        grantAccess(tranID, ogText);
                    } else if (['FAILED', 'CANCELLED', 'REJECTED', 'EXPIRED'].includes(s)) {
                        clearInterval(pollInterval);
                        localStorage.removeItem('pay_tran_' + videoId);
                        localStorage.removeItem('pay_tran_ts_' + videoId);
                        alert('Payment failed or was cancelled. Please try again.');
                        resetBtn(ogText);
                    }
                } catch (e) {
                    /* silently ignore transient network errors during polling */
                }
            }, 5000);
        }

        /* ── shown when poll times out ── */
        function showRecovery(tranID, ogText) {
            payInProgress = false;
            const btn = document.getElementById('payBtn');
            if (btn) {
                btn.innerHTML  = '<i class="fas fa-search"></i> Check Payment Status';
                btn.disabled   = false;
                btn.onclick    = () => checkOnce(tranID);
                btn.className  = btn.className.replace('bg-blue-600 hover:bg-blue-700', 'bg-yellow-600 hover:bg-yellow-700');
            }
        }

        /* ── single manual check — recovery + localStorage resume ── */
        async function checkOnce(tranID) {
            const btn = document.getElementById('payBtn');
            if (btn) { btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...'; btn.disabled = true; }

            try {
                const r = await fetch('api/check_payment.php?tranid=' + encodeURIComponent(tranID) + '&_=' + Date.now());
                const d = await r.json();
                const s = (d.payment_status || d.status || '').toUpperCase();

                if (s === 'COMPLETED' || s === 'SUCCESS') {
                    grantAccess(tranID, '');
                } else if (['FAILED', 'CANCELLED', 'REJECTED', 'EXPIRED'].includes(s)) {
                    localStorage.removeItem('pay_tran_' + videoId);
                    localStorage.removeItem('pay_tran_ts_' + videoId);
                    alert('Payment not completed. Status: ' + s + '. If you were charged, please contact support.');
                    if (btn) { btn.innerHTML = 'Pay Now'; btn.disabled = false; }
                } else {
                    alert('Payment still pending. Please wait and try checking again shortly.');
                    if (btn) { btn.innerHTML = '<i class="fas fa-search"></i> Check Payment Status'; btn.disabled = false; }
                }
            } catch (e) {
                alert('Network error while checking. Please try again.');
                if (btn) { btn.innerHTML = '<i class="fas fa-search"></i> Check Payment Status'; btn.disabled = false; }
            }
        }

        /* ── called when payment confirmed COMPLETED ── */
        function grantAccess(tranID, ogText) {
            localStorage.removeItem('pay_tran_' + videoId);
            localStorage.removeItem('pay_tran_ts_' + videoId);

            const btn = document.getElementById('payBtn');
            if (btn) {
                btn.style.background = '#10b981';
                btn.innerHTML = '<i class="fas fa-check-circle"></i> PAYMENT CONFIRMED!';
            }

            setTimeout(() => {
                window.location.href = 'video_view.php?video_id=' + videoId + '&token=' + encodeURIComponent(tranID);
            }, 1500);
        }
    </script>
</body>
</html>
