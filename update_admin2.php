<?php
$file = 'admin/index.php';
$content = file_get_contents($file);

// 3. Update HTML to display Platform Revenue
$pattern = '/<small class="text-success fw-bold d-block">Platform Revenue<\/small>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>/';
$replace = '<small class="text-success fw-bold d-block">Total Processed</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card bg-success bg-opacity-10 shadow-none border-0 h-100" style="border-left: 4px solid #198754 !important;">
                                <div class="card-body d-flex align-items-center p-3">
                                    <div class="avatar avatar-sm bg-success shadow-success rounded-circle text-white me-3 flex-shrink-0">
                                        <i class="fa-solid fa-coins"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-0">TZS <?php echo $stats[\'platform_revenue\']; ?></h4>
                                        <small class="text-success fw-bold d-block">Your Commission</small>
                                    </div>
                                </div>
                            </div>
                        </div>';

$content = preg_replace($pattern, $replace, $content);
file_put_contents($file, $content);
echo "Updated HTML block in admin/index.php\n";
