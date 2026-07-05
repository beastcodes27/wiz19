<?php
require_once 'includes/load_user.php';

// Fetch personal stats for this user
$stats = [
    'views' => $pdo->query("SELECT SUM(views) FROM videos WHERE user_id = $user_id AND status != 'deleted'")->fetchColumn() ?: 0,
    'videos' => $pdo->query("SELECT COUNT(*) FROM videos WHERE user_id = $user_id AND status != 'deleted'")->fetchColumn() ?: 0,
    'revenue' => $pdo->query("SELECT SUM(amount) FROM transactions WHERE user_id = $user_id AND type = 'earning' AND status = 'completed'")->fetchColumn() ?: 0,
    'transactions' => $pdo->query("SELECT COUNT(*) FROM transactions WHERE user_id = $user_id")->fetchColumn() ?: 0,
    'subscriptions' => 0 // Placeholder
];

// Fetch personal top videos
$topVideosStmt = $pdo->prepare("
    SELECT 
        id as raw_id, 
        CONCAT('#', id) as id, 
        title, 
        clicks as sales, 
        earnings as revenue, 
        thumbnail_url as thumbnail 
    FROM videos 
    WHERE user_id = ? AND status != 'deleted'
    ORDER BY earnings DESC 
    LIMIT 5
");
$topVideosStmt->execute([$user_id]);
$topVideos = $topVideosStmt->fetchAll();
if (!$topVideos) {
    $topVideos = [];
}
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
  <title><?= htmlspecialchars($platform_name) ?> - Dashboard</title>
  <!-- end::GXON Website Page Title -->

  <!-- begin::GXON Mobile Specific -->
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- end::GXON Mobile Specific -->

  <!-- begin::GXON Favicon Tags -->
  <link class="favicon" rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
  <link class="apple-touch-icon" sizes="180x180" href="<?= BASE_URL ?>/assets/images/apple-touch-icon.png">
  <!-- end::GXON Favicon Tags -->

  <!-- begin::GXON Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">
  <!-- end::GXON Google Fonts -->

  <!-- begin::GXON Required Stylesheet -->
  <link rel="stylesheet" href="assets/libs/flaticon/css/all/all.css">
  <link rel="stylesheet" href="assets/libs/lucide/lucide.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="assets/libs/simplebar/simplebar.css">
  <link rel="stylesheet" href="assets/libs/node-waves/waves.css">
  <link rel="stylesheet" href="assets/libs/bootstrap-select/css/bootstrap-select.min.css">
  <!-- end::GXON Required Stylesheet -->

  <!-- begin::GXON CSS Stylesheet -->
  <link rel="stylesheet" href="assets/css/styles.css">
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
<style>
.custom-alert {
    background-color: rgb(34 176 126 / 10%);
    border-left: 4px solid #111827;
    border-radius: 14px;
    transition: all 0.2s ease;
}

.custom-alert:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(0,0,0,0.05);
}

.alert-icon-box {
    background-color: #f3f4f6;
    color: #111827;
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.alert-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: #111827;
}

.alert-text {
    font-size: 0.85rem;
    color: #4b5563;
}

.alert-support {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 4px;
}

.alert-support a {
    color: #316AFF;
    text-decoration: none;
    font-weight: 500;
}

.alert-support a:hover {
    text-decoration: underline;
}

.alert-action {
    font-size: 0.8rem;
    font-weight: 500;
    color: #111827;
    text-decoration: none;
    white-space: nowrap;
}

.alert-action:hover {
    text-decoration: underline;
}

.fade.show {
    animation: slideDown 0.35s ease-out;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
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
                          <a class="dropdown-item d-flex align-items-center gap-2" href="profile">
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
                              href="logout"
                              onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                              <i class="fa-solid fa-right-from-bracket scale-1x"></i> Log Out
                          </a>

                          <form id="logout-form" action="logout" method="POST" class="d-none">
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
          <a class="navbar-brand-logo" href="dashboard">
              <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Dashboard" style="max-height: 40px; width: auto;">
          </a>
      </div>
      <nav class="app-navbar" data-simplebar>
          <ul class="menubar">
              <li class="menu-item">
                  <a class="menu-link" href="dashboard">
                      <i class="fa-solid fa-chart-pie"></i>
                      <span class="menu-label">Dashboard</span>
                  </a>
              </li>

              <li class="menu-item menu-arrow">
                  <a class="menu-link" href="javascript:void(0);" role="button">
                      <i class="fa-solid fa-video"></i>
                      <span class="menu-label">Video</span>
                      <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.75rem;"></i>
                  </a>
                  <ul class="menu-inner">
                      <li class="menu-item">
                          <a class="menu-link" href="video-upload">
                              <span class="menu-label">Upload</span>
                          </a>
                      </li>
                      <li class="menu-item">
                          <a class="menu-link" href="videos">
                              <span class="menu-label">Manage Videos</span>
                          </a>
                      </li>
                  </ul>
              </li>

              <li class="menu-item">
                  <a class="menu-link" href="transactions">
                      <i class="fa-solid fa-receipt"></i>
                      <span class="menu-label">Transactions</span>
                  </a>
              </li>
              <li class="menu-item">
                  <a class="menu-link" href="withdrawals">
                      <i class="fa-solid fa-money-bill-wave"></i>
                      <span class="menu-label">Withdrawals</span>
                  </a>
              </li>

              <li class="menu-item">
                  <a class="menu-link" href="domain">
                      <i class="fa-solid fa-globe"></i>
                      <span class="menu-label">Domains</span>
                  </a>
              </li>

              <li class="menu-item menu-arrow">
                  <a class="menu-link" href="javascript:void(0);" role="button">
                      <i class="fa-solid fa-box-open"></i>
                      <span class="menu-label">Packages</span>
                      <i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.75rem;"></i>
                  </a>
                  <ul class="menu-inner">
                      <li class="menu-item">
                          <a class="menu-link" href="package">
                              <span class="menu-label">Create Package</span>
                          </a>
                      </li>
                      <li class="menu-item">
                          <a class="menu-link" href="package-subscribers">
                              <span class="menu-label">Active Subscribers</span>
                          </a>
                      </li>
                  </ul>
              </li>
              <li class="menu-item">
                  <a class="menu-link" href="settings">
                      <i class="fa-solid fa-gear"></i>
                      <span class="menu-label">Settings</span>
                  </a>
              </li>

              <li class="menu-item">
                  <a class="menu-link" href="#">
                      <i class="fa-solid fa-crown"></i>
                      <span class="menu-label">Subscription</span>
                  </a>
              </li>
              <?php if (is_admin()): ?>
              <li class="menu-item">
                  <a class="menu-link" href="admin/index">
                      <i class="fa-solid fa-shield-halved"></i>
                      <span class="menu-label">Admin Dashboard</span>
                  </a>
              </li>
              <?php endif; ?>
              <li class="menu-item">
                  <a class="menu-link" href="support"><i class="fa-solid fa-headset"></i><span class="menu-label">Support</span><?php if (isset($user_unread_tickets_count) && $user_unread_tickets_count > 0): ?><span class="badge rounded-pill user-support-badge" style="font-size: 0.72rem; padding: 0.25rem 0.6rem; background: linear-gradient(135deg, #ff416c, #ff4b2b) !important; color: #ffffff !important; box-shadow: 0 2px 6px rgba(255, 65, 108, 0.45); font-weight: 700; margin-left: auto !important; display: inline-flex !important; align-items: center; justify-content: center; min-width: 1.5rem; height: 1.5rem;"><?= $user_unread_tickets_count ?></span><?php endif; ?></a>
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

        <div class="container-fluid px-4">

            <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between my-4">
                <div class="clearfix">
                    <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
                    <p class="text-muted small mb-0">Overview of your channel's metrics and video performance</p>
                </div>
            </div>

            <div class="alert alert-dismissible fade show custom-alert shadow-sm border-0 py-3 px-4 d-flex align-items-center mb-4" role="alert">
                <div class="alert-icon-box me-3 d-none d-sm-flex">
                    <i class="fa-solid fa-bell"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="alert-title mb-1">
                        New update
                    </div>
                    <div class="alert-text">
                        Template 8 is now live with preview playback and full customization.
                    </div>
                    <div class="alert-support">
                         Need help? Contact us on Telegram 
                         <a href="https://t.me/<?= htmlspecialchars(ltrim($telegram_username, '@')) ?>" target="_blank">@<?= htmlspecialchars(ltrim($telegram_username, '@')) ?></a>
                    </div>
                </div>
                <button type="button" class="btn-close ms-3" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4 col-lg">
                    <div class="card bg-secondary bg-opacity-05 shadow-none border-0 h-100">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="avatar avatar-sm bg-secondary shadow-secondary rounded-circle text-white me-3 flex-shrink-0">
                                <i class="fa-solid fa-eye"></i>
                            </div>
                            <div>
                                <h4 class="mb-0"><?php echo number_format($stats['views']); ?></h4>
                                <small class="text-muted d-block">Total Views</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg">
                    <div class="card bg-white bg-opacity-05 shadow-none border-0 h-100">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="avatar avatar-sm bg-primary shadow-primary rounded-circle text-white me-3 flex-shrink-0">
                                <i class="fa-solid fa-video"></i>
                            </div>
                            <div>
                                <h4 class="mb-0"><?php echo number_format($stats['videos']); ?></h4>
                                <small class="text-muted d-block">Total Videos</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg">
                    <div class="card bg-success bg-opacity-05 shadow-none border-0 h-100">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="avatar avatar-sm bg-success shadow-success rounded-circle text-white me-3 flex-shrink-0">
                                <i class="fa-solid fa-money-bill-wave"></i>
                            </div>
                            <div>
                                <h4 class="mb-0">TZS <?php echo number_format($stats['revenue'], 2); ?></h4>
                                <small class="text-muted d-block">Total Revenue</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-6 col-lg">
                    <div class="card bg-warning bg-opacity-05 shadow-none border-0 h-100">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="avatar avatar-sm bg-warning shadow-warning rounded-circle text-white me-3 flex-shrink-0">
                                <i class="fa-solid fa-receipt"></i>
                            </div>
                            <div>
                                <h4 class="mb-0"><?php echo number_format($stats['transactions']); ?></h4>
                                <small class="text-muted d-block">Transactions</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-6 col-lg">
                    <div class="card bg-info bg-opacity-05 shadow-none border-0 h-100">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="avatar avatar-sm bg-info shadow-info rounded-circle text-white me-3 flex-shrink-0">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <div>
                                <h4 class="mb-0"><?php echo number_format($stats['subscriptions']); ?></h4>
                                <small class="text-muted d-block">Subscriptions</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm overflow-hidden">
                        <div class="card-header bg-transparent border-bottom py-3">
                            <h5 class="card-title mb-0 fw-bold">Top Performing Videos</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table id="dt_TopVideos" class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Video Title</th>
                                            <th class="text-center">Sales Count</th>
                                            <th class="text-center pe-4">Total Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($topVideos) > 0): ?>
                                            <?php foreach ($topVideos as $video): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-wrapper me-3 flex-shrink-0" style="width: 45px; height: 45px;">
                                                            <img src="<?php echo htmlspecialchars($video['thumbnail'] ?: 'assets/images/video_placeholder.png'); ?>"
                                                                class="rounded-3 object-fit-cover shadow-sm w-100 h-100"
                                                                alt="Thumbnail">
                                                        </div>
                                                        <div>
                                                            <h6 class="text-sm fw-bold mb-0 text-line-1"><?php echo htmlspecialchars($video['title']); ?></h6>
                                                            <span class="text-muted small">ID: #<?php echo htmlspecialchars($video['raw_id']); ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-light text-primary rounded-pill px-3 py-1">
                                                        <?php echo number_format($video['sales']); ?> Sales
                                                    </span>
                                                </td>
                                                <td class="text-center pe-4">
                                                    <span class="text-success fw-bold">
                                                        TZS <?php echo number_format($video['revenue'], 2); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center py-4 text-muted">
                                                    <i class="fa-solid fa-play mb-2" style="font-size: 2rem; opacity: 0.3;"></i>
                                                    <p class="mb-0">No stats available for your videos yet.</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <footer class="footer-wrapper bg-body">
      <div class="container-fluid px-4">
        <div class="row g-2">
          <div class="col-lg-6 col-md-7 text-center text-md-start">
            <p class="mb-0">© <span class="currentYear"><?php echo date('Y'); ?></span> <?php echo htmlspecialchars($platform_name); ?>. Proudly powered by <a href="javascript:void(0);">UnknowDev</a>.</p>
          </div>
          <div class="col-lg-6 col-md-5">
            <ul class="d-flex list-inline mb-0 gap-3 flex-wrap justify-content-center justify-content-md-end">
              <li>
                <a class="text-body" href="#">Tutorials</a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </footer>

  </div>

  <script src="assets/libs/global/global.min.js"></script>
  <script src="assets/js/appSettings.js"></script>
  <script src="assets/js/main.js"></script>
</body>
</html>