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
$statsFee = $pdo->query("SELECT SUM(fee_amount) FROM transactions WHERE status = 'completed'")->fetchColumn() ?: 0;
$stats = [
    'views' => number_format($statsViews),
    'videos' => number_format($statsVideos),
    'revenue' => number_format($statsRev),
    'transactions' => number_format($statsTxn),
    'subscriptions' => number_format($statsSub),
    'users' => number_format($statsUsers),
    'platform_revenue' => number_format($statsFee)
];

// Fetch top 5 videos globally
$topVideosStmt = $pdo->query("
    SELECT 
        id as raw_id, 
        CONCAT('#', id) as id, 
        title, 
        clicks as sales, 
        earnings as revenue, 
        thumbnail_url as thumbnail 
    FROM videos 
    ORDER BY earnings DESC 
    LIMIT 5
");
$topVideos = $topVideosStmt->fetchAll();

// Fetch recent transactions globally
$recentTxnStmt = $pdo->query("
    SELECT t.*, u.full_name as creator_name 
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    ORDER BY t.created_at DESC 
    LIMIT 5
");
$recentTransactions = $recentTxnStmt->fetchAll();
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
  <title><?= htmlspecialchars($platform_name) ?> Admin - Overview</title>
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
                      data-bs-auto-close="outside" aria-expanded="false">
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
                  <a class="menu-link" href="withdrawals">
                      <i class="fa-solid fa-money-bill-wave"></i>
                      <span class="menu-label">Payouts</span>
                  </a>
              </li>
              <li class="menu-item">
                  <a class="menu-link" href="announcements">
                      <i class="fa-solid fa-bullhorn"></i>
                      <span class="menu-label">Announcements</span>
                  </a>
              </li>
              <li class="menu-item">
                  <a class="menu-link" href="tickets">
                      <i class="fa-solid fa-headset"></i>
                      <span class="menu-label">Support Tickets</span>
                      <?php if (isset($admin_unread_tickets_count) && $admin_unread_tickets_count > 0): ?>
                          <span class="badge rounded-pill" style="font-size: 0.72rem; padding: 0.25rem 0.6rem; background: linear-gradient(135deg, #ff416c, #ff4b2b) !important; color: #ffffff !important; box-shadow: 0 2px 6px rgba(255, 65, 108, 0.45); font-weight: 700; margin-left: auto !important; display: inline-flex !important; align-items: center; justify-content: center; min-width: 1.5rem; height: 1.5rem;"><?= $admin_unread_tickets_count ?></span>
                      <?php endif; ?>
                  </a>
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
                    <h1 class="app-page-title">Admin Overview</h1>
                    <p class="text-muted mb-0">Platform-wide statistics and activity</p>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-xxl-12">
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <div class="card bg-success bg-opacity-10 shadow-none border-0 h-100">
                                <div class="card-body d-flex align-items-center p-3">
                                    <div class="avatar avatar-sm bg-success shadow-success rounded-circle text-white me-3 flex-shrink-0">
                                        <i class="fa-solid fa-money-bill-wave"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-0">TZS <?php echo $stats['revenue']; ?></h4>
                                        <small class="text-success fw-bold d-block">Total Processed</small>
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
                                        <h4 class="mb-0">TZS <?php echo $stats['platform_revenue']; ?></h4>
                                        <small class="text-success fw-bold d-block">Your Commission</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card bg-primary bg-opacity-10 shadow-none border-0 h-100">
                                <div class="card-body d-flex align-items-center p-3">
                                    <div class="avatar avatar-sm bg-primary shadow-primary rounded-circle text-white me-3 flex-shrink-0">
                                        <i class="fa-solid fa-users"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-0"><?php echo $stats['users']; ?></h4>
                                          <small class="text-primary fw-bold d-block">Users</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card bg-info bg-opacity-10 shadow-none border-0 h-100">
                                <div class="card-body d-flex align-items-center p-3">
                                    <div class="avatar avatar-sm bg-info shadow-info rounded-circle text-white me-3 flex-shrink-0">
                                        <i class="fa-solid fa-video"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-0"><?php echo $stats['videos']; ?></h4>
                                        <small class="text-info fw-bold d-block">Videos</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card bg-warning bg-opacity-10 shadow-none border-0 h-100">
                                <div class="card-body d-flex align-items-center p-3">
                                    <div class="avatar avatar-sm bg-warning shadow-warning rounded-circle text-white me-3 flex-shrink-0">
                                        <i class="fa-solid fa-crown"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-0"><?php echo $stats['subscriptions']; ?></h4>
                                        <small class="text-warning fw-bold d-block">Subs</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-bottom py-3">
                            <h5 class="mb-0 fw-bold">Recent Platform Activity</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">User</th>
                                            <th>Action</th>
                                            <th>Target</th>
                                            <th>Status</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                          <?php foreach($recentTransactions as $txn): ?>
                                          <tr>
                                              <td class="ps-4"><strong><?php echo htmlspecialchars($txn['creator_name']); ?></strong><br><small class="text-muted">Creator</small></td>
                                              <td>
                                                <?php if($txn['type'] == 'earning'): ?>
                                                    <span class="badge bg-success-subtle text-success">Sale Earning</span>
                                                <?php elseif($txn['type'] == 'withdrawal'): ?>
                                                    <span class="badge bg-danger-subtle text-danger">Withdrawal</span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary-subtle text-primary"><?php echo ucfirst($txn['type']); ?></span>
                                                <?php endif; ?>
                                              </td>
                                              <td>TZS <?php echo number_format($txn['amount']); ?></td>
                                              <td>
                                                <?php if($txn['status'] == 'completed'): ?>
                                                    <i class="fa-solid fa-circle-check text-success me-1"></i> Completed
                                                <?php elseif($txn['status'] == 'pending'): ?>
                                                    <i class="fa-solid fa-spinner text-warning me-1"></i> Pending
                                                <?php else: ?>
                                                    <i class="fa-solid fa-circle-xmark text-danger me-1"></i> Failed
                                                <?php endif; ?>
                                              </td>
                                              <td class="text-muted"><?php echo date('M d, H:i', strtotime($txn['created_at'])); ?></td>
                                          </tr>
                                          <?php endforeach; ?>
                                      </tbody>
                                </table>
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
