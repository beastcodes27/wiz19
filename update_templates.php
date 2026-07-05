<?php
$templatesDir = __DIR__ . '/templates';
$files = glob($templatesDir . '/landing*.php');

$newFunction = <<<'EOD'
async function processPayment() {
            const phoneInput = document.getElementById('phoneNumber');
            const videoInput = document.getElementById('videoID');
            const phone = phoneInput ? phoneInput.value : '';
            const videoId = videoInput ? videoInput.value : '';
            
            if (!phone || phone.length < 10) {
                alert("Tafadhali weka namba ya simu sahihi / Please enter a valid phone number.");
                return;
            }
            
            // Find the button to show loading state
            const btn = document.querySelector('button[onclick="processPayment()"]') || document.querySelector('.btn-full');
            const originalText = btn ? btn.innerHTML : 'Pay';
            if (btn) {
                btn.innerHTML = 'Initiating Payment...';
                btn.disabled = true;
            }

            try {
                const baseUrl = '<?= defined("BASE_URL") ? BASE_URL : "" ?>';
                const res = await fetch(baseUrl + '/api/process_payment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ video_id: videoId, phone: phone })
                });
                
                const data = await res.json();
                if (data.status === 'success') {
                    if (btn) btn.innerHTML = 'Waiting for PIN...';
                    
                    // Start polling
                    let attempts = 0;
                    const maxAttempts = 24; // 2 minutes
                    const interval = setInterval(async () => {
                        attempts++;
                        if (attempts > maxAttempts) {
                            clearInterval(interval);
                            alert('Payment timed out. Please try again.');
                            if (btn) { btn.innerHTML = originalText; btn.disabled = false; }
                            return;
                        }
                        
                        try {
                            const pollRes = await fetch(baseUrl + '/api/check_payment.php?tranid=' + data.tranID);
                            const pollData = await pollRes.json();
                            
                            if (pollData.payment_status === 'COMPLETED') {
                                clearInterval(interval);
                                if (btn) btn.innerHTML = 'SUCCESS!';
                                setTimeout(() => {
                                    window.location.href = baseUrl + '/video_view.php?video_id=' + videoId + '&token=' + data.tranID;
                                }, 1000);
                            } else if (pollData.payment_status === 'FAILED' || pollData.payment_status === 'CANCELLED') {
                                clearInterval(interval);
                                alert('Payment failed or was cancelled.');
                                if (btn) { btn.innerHTML = originalText; btn.disabled = false; }
                            }
                        } catch (e) {
                            console.error('Polling error', e);
                        }
                    }, 5000);
                    
                } else {
                    alert('Error: ' + (data.message || 'Payment initiation failed'));
                    if (btn) { btn.innerHTML = originalText; btn.disabled = false; }
                }
            } catch (err) {
                alert('Connection error');
                if (btn) { btn.innerHTML = originalText; btn.disabled = false; }
            }
        }
EOD;

$pattern = '/(?:async\s+)?function\s+processPayment\s*\(\)\s*\{.*?\}(?=\s*<\/?script>|\s*(?:async\s+)?function|\s*$)/s';

// A better pattern: match `function processPayment()` up to the last closing brace before `</script>` or the next function.
// Since JS blocks in these templates might just be at the end, we can use a more precise regex.
// Wait, regex for balanced braces is hard. Let's do it with a simpler regex since we know the structure.
// Most end with `// Submit form to your payment gateway or backend endpoint here\s*}`
// or `// Proceed to actual payment integration...\s*}`
// or we can just replace everything from `function processPayment()` to the end of the script block (excluding </script>).
$pattern = '/(?:async\s+)?function\s+processPayment\s*\(\)\s*\{.*?(?=\s*<\/script>)/s';

foreach ($files as $file) {
    $content = file_get_contents($file);
    // Let's first check if we can safely replace it. 
    // In landing7.php, there's `async function processPayment()` and it ends before `</script>`.
    
    // We can match everything from `function processPayment()` to `</script>`
    // But what if there are other functions after it? Let's check landing1.php.
    // landing1.php: openPaymentModal, closePaymentModal, processPayment. processPayment is the last one.
    
    // So if processPayment is the last function, matching to `</script>` is safe. Let's verify this manually or just use it and append `</script>`.
    
    $updatedContent = preg_replace_callback('/(?:async\s+)?function\s+processPayment\s*\(\)\s*\{.*?(?=\s*<\/script>)/s', function($matches) use ($newFunction) {
        return $newFunction;
    }, $content);
    
    file_put_contents($file, $updatedContent);
    echo "Updated " . basename($file) . "\n";
}
echo "Done.\n";
