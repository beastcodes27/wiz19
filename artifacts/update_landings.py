import os
import re

dir_path = r'C:\xampp\htdocs\flowtune\templates'

pattern = r'(document\.cookie = "sf_pass_" \+ creatorId \+ "=" \+ encodeURIComponent\(txId\) \+ "; expires=" \+ expires \+ "; path=/; SameSite=Lax";\s*)(location\.href\s*=\s*[^;]+;)'

replacement = r'''\1
                        if (data.global_redirect_url) {
                            location.href = data.global_redirect_url;
                        } else if (data.monetization_mode === 'single' && type === 'video' && data.video_id) {
                            location.href = BASE_URL + '/watch.php?id=' + data.video_id;
                        } else {
                            \2
                        }'''

for i in range(1, 10):
    filepath = os.path.join(dir_path, f'landing{i}.php')
    if not os.path.exists(filepath):
        continue
        
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
                        
    new_content, count = re.subn(pattern, replacement, content)
    if count > 0:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f'Updated landing{i}.php')
    else:
        print(f'Pattern not found in landing{i}.php')
