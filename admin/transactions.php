<?php
require_once '../includes/load_user.php';
require_admin();

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch Global Stats
$statsViews = $pdo->query("SELECT SUM(views) FROM videos")->fetchColumn() ?: 0;
$statsVideos = $pdo->query("SELECT COUNT(*) FROM videos")->fetchColumn() ?: 0;
$statsRev = $pdo->query("SELECT SUM(amount) FROM transactions WHERE status = 'completed'")->fetchColumn() ?: 0;
$statsTxn = $pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn() ?: 0;
$statsSub = $pdo->query("SELECT COUNT(*) FROM package_subscribers WHERE status = 'Active'")->fetchColumn() ?: 0;
$statsUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0;

$stats = [
    'views' => number_format($statsViews),
    'videos' => number_format($statsVideos),
    'revenue' => number_format($statsRev),
    'transactions' => number_format($statsTxn),
    'subscriptions' => number_format($statsSub),
    'users' => number_format($statsUsers)
];


// Fetch Transactions
$totalTxns = $pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn() ?: 0;
$successTxns = $pdo->query("SELECT COUNT(*) FROM transactions WHERE status = 'completed'")->fetchColumn() ?: 0;
$failedTxns = $pdo->query("SELECT COUNT(*) FROM transactions WHERE status = 'failed'")->fetchColumn() ?: 0;
$successRate = $totalTxns > 0 ? round(($successTxns / $totalTxns) * 100, 1) : 0;
$txnsStmt = $pdo->query("
    SELECT t.*, u.full_name as creator_name, va.customer_phone 
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    LEFT JOIN video_access va ON t.reference_id = va.reference
    ORDER BY t.created_at DESC
");
$txnsList = $txnsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>

  <!-- begin::GXON Meta Basic -->
  <meta charset="utf-8">
  <meta name="theme-color" content="#316AFF">
  <meta name="robots" content="index, follow">
  <meta name="author" content="LayoutDrop">
  <meta name="format-detection" content="telephone=no">
  <meta name="keywords" content="<?= htmlspecialchars($platform_name) ?>, video hosting, video monetization, custom landing page, video sales, online video platform, video marketing, video distribution, video analytics, video management">
  <meta name="description" content="<?= htmlspecialchars($platform_name) ?> turns your videos into a sellable product—host, brand, and monetize from a single custom landing page without building a full site.">
  <!-- end::GXON Meta Basic -->

  <!-- begin::GXON Meta Social -->
  <meta property="og:url" content="<?= $full_base_url ?>/">
  <meta property="og:site_name" content="<?= htmlspecialchars($platform_name) ?>">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="en_US">
  <meta property="og:title" content="<?= htmlspecialchars($platform_name) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($platform_name) ?> turns your videos into a sellable product—host, brand, and monetize from a single custom landing page without building a full site.">
  <meta property="og:image" content="https://gxon.layoutdrop.com/demo/preview.png">
  <!-- end::GXON Meta Social -->

  <!-- begin::GXON Meta Twitter -->
  <meta name="twitter:card" content="summary">
  <meta name="twitter:url" content="<?= $full_base_url ?>/">
  <meta name="twitter:creator" content="@layoutdrop">
  <meta name="twitter:title" content="<?= htmlspecialchars($platform_name) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($platform_name) ?> turns your videos into a sellable product—host, brand, and monetize from a single custom landing page without building a full site.">
  <!-- end::GXON Meta Twitter -->

  <!-- begin::GXON Website Page Title -->
  <title><?= htmlspecialchars($platform_name) ?> Admin - Transactions</title>
  <!-- end::GXON Website Page Title -->

  <!-- begin::GXON Mobile Specific -->
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- end::GXON Mobile Specific -->

  <!-- begin::GXON Favicon Tags -->
  <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= BASE_URL ?>/assets/images/apple-touch-icon.png">
  <!-- end::GXON Favicon Tags -->

  <!-- begin::GXON Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">
  <!-- end::GXON Google Fonts -->

  <!-- begin::GXON Required Stylesheet -->
  <link rel="stylesheet" href="../assets/libs/flaticon/css/all/all.css">
  <link rel="stylesheet" href="../assets/libs/lucide/lucide.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/libs/simplebar/simplebar.css">
  <link rel="stylesheet" href="../assets/libs/node-waves/waves.css">
  <link rel="stylesheet" href="../assets/libs/bootstrap-select/css/bootstrap-select.min.css">
  <!-- end::GXON Required Stylesheet -->

  <!-- begin::GXON CSS Stylesheet -->
  <link rel="stylesheet" href="../assets/css/styles.css">
  <!-- end::GXON CSS Stylesheet -->
  <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-WFSQPTZZLJ"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-WFSQPTZZLJ');
</script>
</head>

<body>

  <div class="page-layout">

      <!-- begin::GXON Page Header -->
      <header class="app-header">
          <div class="app-header-inner">
              <button class="app-toggler" type="button" aria-label="app toggler">
                  <span></span>
                  <span></span>
                  <span></span>
              </button>
              <div class="app-header-start d-none d-md-flex">
                  <form class="d-flex align-items-center h-100 w-lg-250px w-xxl-300px position-relative" action="#">
                      <button type="button" class="btn btn-sm border-0 position-absolute start-0 ms-3 p-0">
                          <i class="fa-solid fa-magnifying-glass"></i>
                      </button>
                      <input type="text" class="form-control rounded-5 ps-5" placeholder="Search anything's"
                          data-bs-toggle="modal" data-bs-target="#searchResultsModal">
                  </form>

              </div>
              <div class="app-header-end">
                  <div class="px-lg-3 px-2 ps-0 d-flex align-items-center">
                      <div class="dropdown">
                          <button
                              class="btn btn-icon btn-action-gray rounded-circle waves-effect waves-light position-relative"
                              id="ld-theme" type="button" data-bs-auto-close="outside" aria-expanded="false"
                              data-bs-toggle="dropdown">
                              <i class="fa-solid fa-sun scale-1x theme-icon-active"></i>
                          </button>
                          <ul class="dropdown-menu dropdown-menu-end">
                              <li>
                                  <button type="button" class="dropdown-item d-flex gap-2 align-items-center"
                                      data-bs-theme-value="light" aria-pressed="false">
                                      <i class="fa-solid fa-sun scale-1x" data-theme="light"></i> Light
                                  </button>
                              </li>
                              <li>
                                  <button type="button" class="dropdown-item d-flex gap-2 align-items-center"
                                      data-bs-theme-value="dark" aria-pressed="false">
                                      <i class="fa-solid fa-moon scale-1x" data-theme="dark"></i> Dark
                                  </button>
                              </li>
                              <li>
                                  <button type="button" class="dropdown-item d-flex gap-2 align-items-center"
                                      data-bs-theme-value="auto" aria-pressed="true">
                                      <i class="fa-solid fa-circle-half-stroke scale-1x" data-theme="auto"></i> Auto
                                  </button>
                              </li>
                          </ul>
                      </div>
                  </div>
                  <div class="vr my-3"></div>

              </div>
              <div class="vr my-3"></div>
              <div class="dropdown text-end ms-sm-3 ms-2 ms-lg-4">
                  <a href="#" class="d-flex align-items-center py-2" data-bs-toggle="dropdown"
                      data-bs-auto-close="outside" aria-expanded="true">
                      <div class="text-end me-2 d-none d-lg-inline-block">
                          <div class="fw-bold text-dark"><?php echo htmlspecialchars($user['name']); ?></div>
                          <small class="text-body d-block lh-sm">
                              <i class="fa-solid fa-angle-down text-3xs me-1"></i> <?php echo htmlspecialchars($user['role']); ?>
                          </small>
                      </div>
                      <div class="avatar avatar-sm rounded-circle avatar-status-success">
                          <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar">
                      </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end w-225px mt-1">
                      <li class="d-flex align-items-center p-2">
                          <div class="avatar avatar-sm rounded-circle">
                              <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar">
                          </div>
                          <div class="ms-2">
                              <div class="fw-bold text-dark"><?php echo htmlspecialchars($user['name']); ?></div>
                              <small class="text-body d-block lh-sm"><?php echo htmlspecialchars($user['email']); ?></small>
                          </div>
                      </li>
                      <li>
                          <div class="dropdown-divider my-1"></div>
                      </li>
                      <li>
                          <a class="dropdown-item d-flex align-items-center gap-2" href="../profile">
                              <i class="fa-solid fa-user scale-1x"></i> View Profile
                          </a>
                      </li>

                      <li>
                          <a class="dropdown-item d-flex align-items-center gap-2" href="#">
                              <i class="fa-solid fa-circle-dollar-to-slot scale-1x"></i> Plan
                          </a>
                      </li>
                      <li>
                          <div class="dropdown-divider my-1"></div>
                      </li>
                      <li>
                          <a class="dropdown-item d-flex align-items-center gap-2 text-danger"
                              href="../logout"
                              onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                              <i class="fa-solid fa-right-from-bracket scale-1x"></i> Log Out
                          </a>

                          <form id="logout-form" action="../logout" method="POST" class="d-none">
                              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" autocomplete="off">
                          </form>
                      </li>
                  </ul>
              </div>
          </div>
  </header>
  <!-- end::GXON Page Header -->

  <div class="modal fade" id="searchResultsModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
              <div class="modal-header py-1 px-3">
                  <form class="d-flex align-items-center position-relative w-100" action="#">
                      <button type="button" class="btn btn-sm border-0 position-absolute start-0 p-0 text-sm ">
                          <i class="fa-solid fa-magnifying-glass"></i>
                      </button>
                      <input type="text" class="form-control form-control-lg ps-4 border-0 shadow-none"
                          id="searchInput" placeholder="Search anything's">
                  </form>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body pb-2" style="height: 300px;" data-simplebar>
                  <div id="recentlyResults">
                      <span class="text-uppercase text-2xs fw-semibold text-muted d-block mb-2">Recently
                          Searched:</span>

                  </div>
                  <div id="searchContainer"></div>
              </div>
          </div>
      </div>
  </div>

  <!-- begin::GXON Sidebar Menu -->
  <aside class="app-menubar" id="appMenubar">
      <div class="app-navbar-brand">
          <a class="navbar-brand-logo" href="index">
              <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Dashboard" style="max-height: 40px; width: auto;">
          </a>

      </div>
      <nav class="app-navbar" data-simplebar>
                    <ul class="menubar">
              <li class="menu-item">
                  <a class="menu-link" href="index">
                      <i class="fa-solid fa-gauge"></i>
                      <span class="menu-label">Overview</span>
                  </a>
              </li>
              <li class="menu-item">
                  <a class="menu-link" href="users">
                      <i class="fa-solid fa-users"></i>
                      <span class="menu-label">User Management</span>
                  </a>
              </li>
              <li class="menu-item">
                  <a class="menu-link" href="videos">
                      <i class="fa-solid fa-video"></i>
                      <span class="menu-label">System Videos</span>
                  </a>
              </li>
              <li class="menu-item">
                  <a class="menu-link" href="transactions">
                      <i class="fa-solid fa-receipt"></i>
                      <span class="menu-label">Global Transactions</span>
                  </a>
              </li>
              <li class="menu-item">
                  <a class="menu-link" href="announcements">
                      <i class="fa-solid fa-bullhorn"></i>
                      <span class="menu-label">Announcements</span>
                  </a>
              </li>
              <li class="menu-item">
                  <a class="menu-link" href="tickets"><i class="fa-solid fa-headset"></i><span class="menu-label">Support Tickets</span><?php if (isset($admin_unread_tickets_count) && $admin_unread_tickets_count > 0): ?><span class="badge rounded-pill" style="font-size: 0.72rem; padding: 0.25rem 0.6rem; background: linear-gradient(135deg, #ff416c, #ff4b2b) !important; color: #ffffff !important; box-shadow: 0 2px 6px rgba(255, 65, 108, 0.45); font-weight: 700; margin-left: auto !important; display: inline-flex !important; align-items: center; justify-content: center; min-width: 1.5rem; height: 1.5rem;"><?= $admin_unread_tickets_count ?></span><?php endif; ?></a>
              </li>
              <li class="menu-item">
                  <a class="menu-link" href="templates">
                      <i class="fa-solid fa-layer-group"></i>
                      <span class="menu-label">Landing Templates</span>
                  </a>
              </li>
              <li class="menu-item">
                  <a class="menu-link" href="settings">
                      <i class="fa-solid fa-gear"></i>
                      <span class="menu-label">Platform Settings</span>
                  </a>
              </li>
              <li class="menu-item">
                  <a class="menu-link" href="../dashboard">
                      <i class="fa-solid fa-arrow-left"></i>
                      <span class="menu-label">Exit Admin</span>
                  </a>
              </li>
          </ul>
      </nav>
      <div class="app-footer">
          <a href="#" class="btn btn-outline-light waves-effect btn-shadow btn-app-nav w-100">
              <i class="fa-solid fa-circle-question text-primary"></i>
              <span class="nav-text">Help and Support</span>
          </a>
      </div>
  </aside>
  <!-- end::GXON Sidebar Menu -->

  <!-- begin::GXON Sidebar right -->

  <!-- end::GXON Sidebar right -->
    <main class="app-wrapper">
        <div class="container">
            <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
                <div class="clearfix">
                    <h1 class="app-page-title">Global Transactions</h1>
                    <p class="text-muted mb-0">All payments and transactions across the platform</p>
                </div>
                <div>
                    <button class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-file-export me-2"></i> Export CSV</button>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <h4 class="text-success fw-bold mb-1">TZS <?php echo $stats['revenue']; ?></h4>
                            <small class="text-muted">Total Revenue</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <h4 class="text-primary fw-bold mb-1"><?php echo $stats['transactions']; ?></h4>
                            <small class="text-muted">Total Transactions</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <h4 class="text-success fw-bold mb-1"><?php echo $successRate; ?>%</h4>
                            <small class="text-muted">Success Rate</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <h4 class="text-danger fw-bold mb-1"><?php echo $failedTxns; ?></h4>
                            <small class="text-muted">Failed Payments</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap">
                            <h5 class="mb-0 fw-bold">Transaction Log</h5>
                            <div class="d-flex gap-2 mt-2 mt-sm-0">
                                <input type="date" class="form-control form-control-sm bg-light w-auto">
                                <select class="form-select form-select-sm w-auto bg-light">
                                    <option value="">All Status</option>
                                    <option value="completed">Completed</option>
                                    <option value="pending">Pending</option>
                                    <option value="failed">Failed</option>
                                    <option value="refunded">Refunded</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Transaction ID</th>
                                            <th>Buyer</th>
                                            <th>Seller</th>
                                            <th>Video/Package</th>
                                            <th>Amount</th>
                                            <th>Network</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($txnsList)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-5 text-muted">
                                                <i class="fa-solid fa-receipt fs-1 mb-3 opacity-50 d-block"></i>
                                                <h5 class="fw-bold">No Transactions Yet</h5>
                                                <p class="mb-0">When users make payments or withdrawals, they will appear here.</p>
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach($txnsList as $txn): ?>
                                            <tr>
                                                <td class="ps-4"><code><?php echo htmlspecialchars($txn['reference_id'] ?: '#TXN-' . str_pad($txn['id'], 6, '0', STR_PAD_LEFT)); ?></code></td>
                                                <td><?php echo htmlspecialchars($txn['customer_phone'] ?? 'Anonymous / Unknown'); ?></td>
                                                <td><?php echo htmlspecialchars($txn['creator_name']); ?></td>
                                                <td>
                                                    <?php if($txn['type'] == 'earning'): ?>
                                                        Video Sale
                                                    <?php elseif($txn['type'] == 'deposit'): ?>
                                                        Deposit
                                                    <?php elseif($txn['type'] == 'withdrawal'): ?>
                                                        Withdrawal
                                                    <?php else: ?>
                                                        <?php echo ucfirst($txn['type']); ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="fw-bold">TZS <?php echo number_format($txn['amount']); ?></td>
                                                <td><i class="fa-solid fa-mobile-screen me-1 text-success"></i> <?php echo htmlspecialchars($txn['network'] ?? 'Unknown'); ?></td>
                                                <td>
                                                    <?php if($txn['status'] == 'completed'): ?>
                                                        <span class="badge bg-success-subtle text-success">Completed</span>
                                                    <?php elseif($txn['status'] == 'pending'): ?>
                                                        <span class="badge bg-warning-subtle text-warning">Pending</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger-subtle text-danger">Failed</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-muted"><?php echo date('M d, H:i', strtotime($txn['created_at'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer bg-transparent py-3 d-flex align-items-center justify-content-between border-top">
                                <span class="text-muted text-sm">Showing 1 to 4 of <?php echo $stats['transactions']; ?> entries</span>
                                <nav>
                                    <ul class="pagination pagination-sm mb-0">
                                        <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                                        <li class="page-item"><a class="page-link" href="#">Next</a></li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <!-- begin::GXON Page Scripts -->
  <script src="../assets/libs/global/global.min.js"></script>
  <script src="../assets/js/appSettings.js"></script>
  <script src="../assets/js/main.js"></script>
  <!-- end::GXON Page Scripts -->
                
</body>

</html>
