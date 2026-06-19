import os
import re

backend_dir = r"c:\xampp\htdocs\sendnaw\Backend"
exclude_file = r"c:\xampp\htdocs\sendnaw\Backend\config\db.php"

def process_file(filepath):
    if os.path.abspath(filepath).lower() == os.path.abspath(exclude_file).lower():
        return
        
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
        
    original_content = content
    
    # Remove any Access-Control headers
    content = re.sub(r'^\s*header\s*\(\s*[\'"]Access-Control-[^\n]*;\s*\n?', '', content, flags=re.IGNORECASE | re.MULTILINE)
    
    # Also attempt to remove the OPTIONS block we just added or similar ones
    options_block_pattern = r'if\s*\(\s*\$_SERVER\[[\'"]REQUEST_METHOD[\'"]\]\s*(===|==)\s*[\'"]OPTIONS[\'"]\s*\)\s*\{[^}]*exit\(\)\s*;\s*\n?\}\s*\n?'
    content = re.sub(options_block_pattern, '', content, flags=re.IGNORECASE | re.DOTALL)
    
    # Clean up empty lines at the start of the file right after <?php
    content = re.sub(r'<\?php\s*\n\s*\n+', '<?php\n', content)
    
    if content != original_content:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Updated: {filepath}")

for root, dirs, files in os.walk(backend_dir):
    # skip vendor
    if 'vendor' in root:
        continue
    for file in files:
        if file.endswith('.php'):
            process_file(os.path.join(root, file))

print("Done.")
