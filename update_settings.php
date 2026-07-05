<?php
$file = 'admin/settings.php';
$content = file_get_contents($file);

// 1. Add platform_fee_percentage to $_POST handling
$search1 = "\$maintenance_mode = isset(\$_POST['maintenance_mode']) ? '1' : '0';";
$replace1 = "\$maintenance_mode = isset(\$_POST['maintenance_mode']) ? '1' : '0';\n            \$platform_fee_percentage = trim(\$_POST['platform_fee_percentage'] ?? '0');";
$content = str_replace($search1, $replace1, $content);

// 2. Add platform_fee_percentage to DB save
$search2 = "\$stmt->execute(['maintenance_mode', \$maintenance_mode]);";
$replace2 = "\$stmt->execute(['maintenance_mode', \$maintenance_mode]);\n            \$stmt->execute(['platform_fee_percentage', \$platform_fee_percentage]);";
$content = str_replace($search2, $replace2, $content);

// 3. Add the HTML field
$search3 = '<div class="mb-3">
                                        <label class="form-label fw-semibold text-muted mb-1">Maintenance Mode</label>';
$replace3 = '<div class="mb-3">
                                        <label class="form-label fw-semibold text-muted mb-1">Platform Fee Percentage (%)</label>
                                        <input type="number" step="0.01" name="platform_fee_percentage" class="form-control"
                                            value="<?= htmlspecialchars($settings[\'platform_fee_percentage\'] ?? \'0\') ?>" placeholder="e.g. 10">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-muted mb-1">Maintenance Mode</label>';
$content = str_replace($search3, $replace3, $content);

file_put_contents($file, $content);
echo "Settings updated.\n";
