<?php
require_once 'includes/load_user.php';

// Settings-specific vars (already fetched in load_user.php with full column list)
$apiKey = $user['gateway_api_key'] ?? '';
$webhookUrl = rtrim($full_base_url, '/') . "/api/pesalink_webhook.php";
$activeLanding = $user['active_landing'] ?? 'landing1';

// Scan the templates directory dynamically for landing*.php files
$landingFiles = glob(__DIR__ . '/templates/landing*.php');
$landings = [];
if ($landingFiles) {
    $landingKeys = array_map(fn($f) => basename($f, '.php'), $landingFiles);
    usort($landingKeys, fn($a, $b) => strnatcmp($a, $b));
    foreach ($landingKeys as $k) {
        $thumbPath = __DIR__ . '/assets/landing/' . $k . '.png';
        $thumbUrl  = BASE_URL . '/assets/landing/' . $k . '.png'
                   . (file_exists($thumbPath) ? '?v=' . filemtime($thumbPath) : '');
        $landings[$k] = [
            'image'       => $thumbUrl,
            'thumb_exists' => file_exists($thumbPath),
        ];
    }
} else {
    $thumbPath = __DIR__ . '/assets/landing/landing1.png';
    $landings = [
        'landing1' => [
            'image'        => BASE_URL . '/assets/landing/landing1.png' . (file_exists($thumbPath) ? '?v=' . filemtime($thumbPath) : ''),
            'thumb_exists' => file_exists($thumbPath),
        ],
    ];
}

// Flash message from redirect
$flash_msg  = $_SESSION['flash_message'] ?? '';
$flash_type = $_SESSION['flash_type']    ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// Also handle simple ?msg=success from setting-landing.php redirect
if (!$flash_msg && isset($_GET['msg'])) {
    if ($_GET['msg'] === 'success') {
        $flash_msg  = 'Landing page template saved successfully!';
        $flash_type = 'success';
    } elseif ($_GET['msg'] === 'error') {
        $flash_msg  = 'Failed to save template. Please try again.';
        $flash_type = 'danger';
    }
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
  <title><?= htmlspecialchars($platform_name) ?> - Settings</title>
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

  <style>
      /* Hide the actual radio input */
      .card-input-element {
          display: none;
      }

      /* Base style for the clickable card */
      .card-input {
          cursor: pointer;
          transition: all 0.3s ease;
          border: 2px solid transparent;
          box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      }

      .card-input:hover {
          transform: translateY(-5px);
          box-shadow: 0 10px 20px rgba(0, 0, 0, 0.12);
      }

      /* Styling for the SELECTED state */
      .card-input-element:checked+.card-input {
          border-color: #0d6efd;
          /* Bootstrap primary color */
          background-color: #f8faff;
          position: relative;
      }

      /* Optional: Add a checkmark icon when selected */
      .card-input-element:checked+.card-input::after {
          content: '\2713';
          /* Unicode checkmark */
          position: absolute;
          top: 10px;
          right: 10px;
          background: #0d6efd;
          color: white;
          width: 25px;
          height: 25px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 14px;
          font-weight: bold;
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
                          <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="">
                      </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end w-225px mt-1">
                      <li class="d-flex align-items-center p-2">
                          <div class="avatar avatar-sm rounded-circle">
                              <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="">
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
                  <a class="menu-link" href="support">
                      <i class="fa-solid fa-headset"></i>
                      <span class="menu-label">Support</span>
                      <?php if (isset($user_unread_tickets_count) && $user_unread_tickets_count > 0): ?>
                          <span class="badge rounded-pill user-support-badge" style="font-size: 0.72rem; padding: 0.25rem 0.6rem; background: linear-gradient(135deg, #ff416c, #ff4b2b) !important; color: #ffffff !important; box-shadow: 0 2px 6px rgba(255, 65, 108, 0.45); font-weight: 700; margin-left: auto !important; display: inline-flex !important; align-items: center; justify-content: center; min-width: 1.5rem; height: 1.5rem;"><?= $user_unread_tickets_count ?></span>
                      <?php endif; ?>
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


    <main class="app-wrapper">

        <div class="container">

            <!-- Flash message -->
            <?php if (!empty($flash_msg)): ?>
              <div class="alert alert-<?= htmlspecialchars($flash_type) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($flash_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>
            <?php endif; ?>

            <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
                <div class="clearfix">
                    <h1 class="app-page-title">Setting</h1>
                </div>

            </div>

            <div class="row">
                <div class="card h-100 p-0 radius-12">
                    <div class="card-body p-24">
                        <form action="setting-api" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" autocomplete="off">
                            <div class="row gy-4">
                                <div class="col-xxl-6">
                                    <div class="card radius-12 shadow-none border overflow-hidden">
                                        <div
                                            class="card-header bg-neutral-100 border-bottom py-16 px-24 d-flex align-items-center flex-wrap gap-3 justify-content-between">
                                            <div class="d-flex align-items-center gap-10">

                                                <span class="text-lg fw-semibold text-primary-light">Payment Gateway</span>
                                            </div>
                                            <div
                                                class="form-switch switch-primary d-flex align-items-center justify-content-center">
                                                <input class="form-check-input" type="checkbox" role="switch" checked
                                                    disabled>
                                            </div>
                                        </div>
                                        <div class="card-body p-24">
                                            <div class="row gy-3">

                                                <div class="col-sm-6">
                                                    <label for="APIKey"
                                                        class="form-label fw-semibold text-primary-light text-md mb-8">API
                                                        Key
                                                        <span class="text-danger-600">*</span></label>
                                                    <input type="text" class="form-control radius-8" id="APIKey"
                                                        name="APIKey" placeholder="Enter your Gateway API Key Here"
                                                        value="<?php echo htmlspecialchars($apiKey); ?>">
                                                </div>
                                                <div class="col-12">
                                                    <label for="Webhook"
                                                        class="form-label fw-semibold text-primary-light text-md mb-8">
                                                        Webhook URL <span class="text-danger-600">*</span>
                                                    </label>

                                                    <div class="input-group">
                                                        <input type="text" class="form-control radius-8" id="Webhook"
                                                            name="webhook_url" readonly
                                                            value="<?php echo htmlspecialchars($webhookUrl); ?>">
                                                        <button class="btn btn-outline-primary" type="button"
                                                            onclick="copyWebhook()">Copy</button>
                                                    </div>

                                                    <div class="mt-2 text-muted">
                                                        <small>
                                                            <i class="fa-solid fa-circle-info me-1"></i>
                                                            <strong>Note:</strong> Copy this URL and paste it into your <strong>PesaLink Merchant Dashboard</strong>. This ensures that PesaLink can automatically notify your system of any background payments or delayed confirmations.
                                                        </small>
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <label for="GlobalRedirectUrl"
                                                        class="form-label fw-semibold text-primary-light text-md mb-8">
                                                        Global Redirect URL <span class="text-muted fw-normal">(After successful payment)</span>
                                                    </label>

                                                    <input type="text" class="form-control radius-8" id="GlobalRedirectUrl"
                                                        name="global_redirect_url"
                                                        placeholder="https://example.com/thank-you"
                                                        value="<?php echo htmlspecialchars($user['global_redirect_url'] ?? ''); ?>">

                                                    <div class="mt-2 text-muted">
                                                        <small>
                                                            <i class="fa-solid fa-circle-info me-1"></i>
                                                            <strong>Optional:</strong> After a successful payment, users will be redirected to this URL instead of the default page. Leave blank to use the default behaviour.
                                                        </small>
                                                    </div>
                                                </div>

                                                <div class="col-12 mt-4">
                                                    <label for="monetization_mode"
                                                        class="form-label fw-semibold text-primary-light text-md mb-8">
                                                        Video Monetization Mode
                                                    </label>
                                                    <select class="form-select radius-8" id="monetization_mode" name="monetization_mode">
                                                        <option value="single" <?php echo ($user['monetization_mode'] ?? 'single') === 'single' ? 'selected' : ''; ?>>Pay-Per-Video (Users pay for one specific video)</option>
                                                        <option value="channel" <?php echo ($user['monetization_mode'] ?? 'single') === 'channel' ? 'selected' : ''; ?>>Pay-Per-Channel (Users pay to unlock all your videos for 24h)</option>
                                                    </select>
                                                    <div class="mt-2 text-muted">
                                                        <small>Choose how you want to charge your customers for video access.</small>
                                                    </div>
                                                </div>

                                                <div class="col-12 mt-4">
                                                    <button type="submit"
                                                        class="btn btn-primary border border-primary-600 text-md px-24 py-12 radius-8 w-100 text-center shadow-sm">
                                                        <i class="fa-solid fa-save me-2"></i> Save All Settings
                                                    </button>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>

                <br>
                <div class="card">
                    <div class="card-body">
                        <form action="setting-landing" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" autocomplete="off">                            
                            <div class="row g-3">
                                <?php foreach($landings as $id => $landing): ?>
                                    <div class="col-xxl-3 col-sm-3">
                                        <input type="radio" name="cardSelection" id="<?php echo $id; ?>"
                                            class="card-input-element" value="<?php echo $id; ?>"
                                            <?php echo ($activeLanding === $id) ? 'checked' : ''; ?>>

                                         <label for="<?php echo $id; ?>" class="card card-default card-input">
                                             <?php if ($landing['thumb_exists']): ?>
                                                 <img src="<?php echo htmlspecialchars($landing['image']); ?>"
                                                     class="card-img-top" alt="<?= htmlspecialchars($id) ?>">
                                             <?php else: ?>
                                                 <div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height:140px;">
                                                     <i class="fa-solid fa-image text-muted" style="font-size:2.5rem;"></i>
                                                 </div>
                                             <?php endif; ?>
                                            <div class="card-body text-center">
                                                <span class="fw-bold d-block text-uppercase text-xs mb-1" style="font-size: 0.75rem; letter-spacing: 0.05em; color: #555;"><?= htmlspecialchars('Template ' . ltrim(str_replace('landing', '', $id), '0')) ?></span>
                                                <?php if($activeLanding === $id): ?>
                                                    <span class="badge bg-success mt-1">Currently Active</span>
                                                <?php endif; ?>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-sm-3">
                                    <button type="submit"
                                        class="btn btn-primary border border-primary-600 text-md px-24 py-12 radius-8 w-100 text-center">
                                        Save Changes
                                    </button>
                                </div>
                                <div class="col-sm-3" id="customize-btn-container" style="display: none;">
                                    <a href="builder"
                                        class="btn btn-outline-warning text-md px-24 py-12 radius-8 w-100 text-center">
                                        <i class="fa-solid fa-wand-magic-sparkles me-2"></i> Customize Landing 8
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
    </main>
    <footer class="footer-wrapper bg-body">
      <div class="container">
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
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const customizeContainer = document.getElementById('customize-btn-container');
            const radios = document.querySelectorAll('input[name="cardSelection"]');

            function checkLandingSelection() {
                // Find the selected radio
                const selected = document.querySelector('input[name="cardSelection"]:checked');

                if (selected && selected.value === 'landing8') {
                    customizeContainer.style.display = 'block';
                } else {
                    customizeContainer.style.display = 'none';
                }
            }

            // Run on page load (to check if landing8 is already active)
            checkLandingSelection();

            // Listen for changes when user clicks other landings
            radios.forEach(radio => {
                radio.addEventListener('change', checkLandingSelection);
            });
        });

        // Your existing copy function
        function copyWebhook() {
            var copyText = document.getElementById("Webhook");
            copyText.select();
            navigator.clipboard.writeText(copyText.value);
            alert("Webhook URL copied!");
        }
    </script>
    
  <!-- begin::GXON Page Scripts -->
  <script src="assets/libs/global/global.min.js"></script>
  <script src="assets/js/appSettings.js"></script>
  <script src="assets/js/main.js"></script>
  <!-- end::GXON Page Scripts -->
</body>
</html>
