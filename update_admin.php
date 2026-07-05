<?php
$file = 'admin/index.php';
$content = file_get_contents($file);

// 1. Add SQL fetch for fee
$search1 = "\$statsUsers = \$pdo->query(\"SELECT COUNT(*) FROM users\")->fetchColumn() ?: 0;";
$replace1 = "\$statsUsers = \$pdo->query(\"SELECT COUNT(*) FROM users\")->fetchColumn() ?: 0;\n\$statsFee = \$pdo->query(\"SELECT SUM(fee_amount) FROM transactions WHERE status = 'completed'\")->fetchColumn() ?: 0;";
$content = str_replace($search1, $replace1, $content);

// 2. Add to $stats array
$search2 = "'users' => number_format(\$statsUsers)";
$replace2 = "'users' => number_format(\$statsUsers),\n    'platform_revenue' => number_format(\$statsFee)";
$content = str_replace($search2, $replace2, $content);

// 3. Update HTML to display Platform Revenue
// Let's just insert a new card after the first one
$search3 = '                                        <small class="text-success fw-bold d-block">Platform Revenue</small>
                                    </div>
                                </div>
                            </div>
                        </div>';
$replace3 = '                                        <small class="text-success fw-bold d-block">Total Processed</small>
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
// Wait, the original had:
/*
<h4 class="mb-0">TZS <?php echo $stats['revenue']; ?></h4>
<small class="text-success fw-bold d-block">Platform Revenue</small>
*/
// The original author called Total Processed "Platform Revenue". I should change the original to "Total Processed" and my new one to "Your Commission".
$content = str_replace($search3, $replace3, $content);

file_put_contents($file, $content);
echo "Updated admin/index.php\n";
