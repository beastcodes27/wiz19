import os
import re

dir_path = r'C:\xampp\htdocs\flowtune\templates'

for i in range(1, 10):
    filepath = os.path.join(dir_path, f'landing{i}.php')
    if not os.path.exists(filepath):
        continue
        
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # In check_payment.php poll response we return pollData.monetization_mode, pollData.global_redirect_url, pollData.video_id
    # We need to replace the window.location.href... line with the smart logic
    
    # Pattern to find the location.href or window.location.href redirecting to streaming.php
    pattern = r'((?:window\.)?location\.href\s*=\s*(?:baseUrl|BASE_URL)\s*\+\s*[\'"`]/streaming\.php\?creator_id=[\'"`]\s*\+\s*creatorId;)'
    
    # Notice that for landing1 to 7, the polling response is `pollData`, so we should use `pollData.global_redirect_url` if it exists
    # but in landing2/8/9, the polling response is `data`, or `pollData`. Let's just use the variable we check.
    # We will use `const rd = typeof pollData !== "undefined" ? pollData : data;`
    
    replacement = r'''
                                    const resData = typeof pollData !== "undefined" ? pollData : (typeof data !== "undefined" ? data : {});
                                    if (resData.global_redirect_url) {
                                        window.location.href = resData.global_redirect_url;
                                    } else if (resData.monetization_mode === 'single' && resData.video_id) {
                                        window.location.href = (typeof baseUrl !== "undefined" ? baseUrl : BASE_URL) + '/watch.php?id=' + resData.video_id;
                                    } else {
                                        \1
                                    }'''
                        
    new_content, count = re.subn(pattern, replacement, content)
    if count > 0:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f'Updated landing{i}.php (replaced {count} times)')
    else:
        print(f'Pattern not found in landing{i}.php')
