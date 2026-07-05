<?php
require_once 'includes/load_user.php';

// Fetch domains
$stmt = $pdo->prepare("SELECT * FROM domains WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$domains = $stmt->fetchAll();

// Build the base display string ONCE here so it's available in JS and modals
$baseDisplay = rtrim($_SERVER['HTTP_HOST'], '/') .
               rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';

/**
 * Build the public landing URL for a given domain suffix.
 * Format: yourdomain.com/mystore  (or localhost/flowtune/mystore on XAMPP)
 * .htaccess routes the path → landing.php automatically.
 */
function get_subdomain_url(string $prefix): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'];
    $dir    = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return $scheme . '://' . $host . $dir . '/' . rawurlencode($prefix);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="theme-color" content="#316AFF">
  <meta name="robots" content="index, follow">
  <meta name="author" content="LayoutDrop">
  <meta name="format-detection" content="telephone=no">
  <meta name="keywords" content="<?= htmlspecialchars($platform_name) ?>, video hosting, video monetization, custom landing page, video sales, online video platform, video marketing, video distribution, video analytics, video management">
  <meta name="description" content="<?= htmlspecialchars($platform_name) ?> turns your videos into a sellable product—host, brand, and monetize from a single custom landing page without building a full site.">
  
  <meta property="og:url" content="<?= $full_base_url ?>/">
  <meta property="og:site_name" content="<?= htmlspecialchars($platform_name) ?>">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="en_US">
  <meta property="og:title" content="<?= htmlspecialchars($platform_name) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($platform_name) ?> turns your videos into a sellable product—host, brand, and monetize from a single custom landing page without building a full site.">
  <meta property="og:image" content="https://gxon.layoutdrop.com/demo/preview.png">
  
  <meta name="twitter:card" content="summary">
  <meta name="twitter:url" content="<?= $full_base_url ?>/">
  <meta name="twitter:creator" content="@layoutdrop">
  <meta name="twitter:title" content="<?= htmlspecialchars($platform_name) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($platform_name) ?> turns your videos into a sellable product—host, brand, and monetize from a single custom landing page without building a full site.">
  
  <title><?= htmlspecialchars($platform_name) ?> - Domain Management</title>
  
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= BASE_URL ?>/assets/images/apple-touch-icon.png">
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="assets/libs/flaticon/css/all/all.css">
  <link rel="stylesheet" href="assets/libs/lucide/lucide.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="assets/libs/simplebar/simplebar.css">
  <link rel="stylesheet" href="assets/libs/node-waves/waves.css">
  <link rel="stylesheet" href="assets/libs/bootstrap-select/css/bootstrap-select.min.css">
  
  <link rel="stylesheet" href="assets/css/styles.css">
  
  <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

  <style>
      .toast-container {
          z-index: 2000;
      }

      .custom-toast {
          min-width: 320px;
          border-radius: 16px !important;
          box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
          backdrop-filter: blur(10px);
          animation: slideInCustom 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
          position: relative;
          overflow: hidden;
      }

      .toast-header {
          background: rgba(255, 255, 255, 0.9) !important;
          padding: 12px 16px;
          color: #111;
      }

      .toast-icon-wrapper {
          font-size: 1.2rem;
          display: flex;
          align-items: center;
      }

      .toast-body {
          padding: 12px 16px 18px;
          font-weight: 500;
          letter-spacing: -0.2px;
      }

      /* Progress Bar Logic */
      .toast-progress {
          position: absolute;
          bottom: 0;
          left: 0;
          height: 4px;
          width: 100%;
          background: rgba(255, 255, 255, 0.3);
      }

      .toast-progress::before {
          content: "";
          position: absolute;
          height: 100%;
          width: 100%;
          background: rgba(255, 255, 255, 0.7);
          animation: progressAnim 4s linear forwards;
          transform-origin: left;
      }

      /* Improved Gradients */
      .custom-toast.text-bg-success {
          background: linear-gradient(135deg, #10b981, #059669) !important;
      }

      .custom-toast.text-bg-danger {
          background: linear-gradient(135deg, #f43f5e, #e11d48) !important;
      }

      .custom-toast.text-bg-warning {
          background: linear-gradient(135deg, #fbbf24, #f59e0b) !important;
      }

      .custom-toast.text-bg-info {
          background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
      }

      @keyframes progressAnim {
          from {
              transform: scaleX(1);
          }

          to {
              transform: scaleX(0);
          }
      }

      @keyframes slideInCustom {
          from {
              transform: translateY(20px) scale(0.9);
              opacity: 0;
          }

          to {
              transform: translateY(0) scale(1);
              opacity: 1;
          }
      }
  </style>
</head>

<body>


  <div class="page-layout">

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

    <main class="app-wrapper py-4">
        <div class="container">

            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h1 class="h3 mb-1 text-gray-800 fw-bold">Domain Management</h1>
                    <p class="text-muted small mb-0">Route your store through a custom domain or our subdomain.</p>
                </div>
                <button type="button" class="btn btn-primary d-flex align-items-center gap-2 px-4 shadow-sm"
                    data-bs-toggle="modal" data-bs-target="#addDomainModal">
                    <iconify-icon icon="lucide:globe"></iconify-icon>
                    <span class="fw-medium">Connect Domain</span>
                </button>
            </div>

            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" id="dataTable">
                            <thead class="bg-light">
                                <tr class="text-muted small text-uppercase">
                                    <th class="ps-4 py-3 fw-bold">Domain Name</th>
                                    <th class="py-3 fw-bold text-center">Type</th>
                                    <th class="py-3 fw-bold">Added Date</th>
                                    <th class="py-3 fw-bold">DNS Status</th>
                                    <th class="py-3 fw-bold text-end pe-4">Manage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($domains as $domain):
                                    $domainUrl   = get_subdomain_url($domain['domain_prefix']);
                                    $domainName  = $domainUrl; // e.g. https://yourdomain.com/mystore
                                    $statusColor = $domain['status'] === 'Connected' ? 'text-success' : 'text-warning';
                                    $statusIcon  = $domain['status'] === 'Connected' ? 'lucide:shield-check' : 'lucide:shield-alert';
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar avatar-xs bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center">
                                                <i class="fa-solid fa-globe"></i>
                                            </div>
                                            <a href="<?php echo $domainUrl; ?>" target="_blank" class="fw-bold text-dark text-decoration-none">
                                                <?php
                                                    // Display as: host/prefix
                                                    $display = rtrim($_SERVER['HTTP_HOST'], '/') . '/' . htmlspecialchars($domain['domain_prefix']);
                                                    echo $display;
                                                ?>
                                            </a>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="small py-1 px-2 rounded bg-light border text-secondary">
                                            Subdomain
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-sm text-secondary">
                                            <?php echo date('M d, Y', strtotime($domain['created_at'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center <?php echo $statusColor; ?> gap-2">
                                            <iconify-icon icon="<?php echo $statusIcon; ?>"></iconify-icon>
                                            <span class="fw-medium small"><?php echo htmlspecialchars($domain['status']); ?></span>
                                        </div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex gap-2 justify-content-end">
                                            <!-- Visit -->
                                            <a href="<?php echo $domainUrl; ?>" target="_blank"
                                               class="btn btn-sm btn-light border" title="Visit Page">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                            <!-- Edit -->
                                            <button type="button"
                                                class="btn btn-sm btn-outline-primary border"
                                                title="Edit Suffix"
                                                data-domain-id="<?php echo (int)$domain['id']; ?>"
                                                data-domain-prefix="<?php echo htmlspecialchars($domain['domain_prefix']); ?>"
                                                onclick="openEditDomain(this)">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <!-- Delete -->
                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger border"
                                                title="Delete"
                                                data-domain-id="<?php echo (int)$domain['id']; ?>"
                                                data-domain-name="<?php echo htmlspecialchars($domain['domain_prefix']); ?>"
                                                onclick="openDeleteDomain(this)">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <?php
    // Flash messages from PRG redirects
    $flashMsg  = '';
    $flashType = '';
    $msg = $_GET['msg'] ?? '';
    if ($msg === 'success')      { $flashMsg = 'Domain connected successfully!';          $flashType = 'success'; }
    elseif ($msg === 'updated')  { $flashMsg = 'Domain suffix updated successfully!';     $flashType = 'success'; }
    elseif ($msg === 'deleted')  { $flashMsg = 'Domain removed.';                         $flashType = 'warning'; }
    elseif ($msg === 'domain_taken')   { $flashMsg = 'That suffix is already taken. Try another.'; $flashType = 'danger'; }
    elseif ($msg === 'invalid_prefix') { $flashMsg = 'Invalid suffix format.';            $flashType = 'danger'; }
    ?>
    <?php if ($flashMsg): ?>
    <div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index:2000">
        <div class="toast custom-toast text-bg-<?= $flashType ?> show border-0 shadow-lg" role="alert">
            <div class="toast-body fw-semibold py-3 px-4">
                <?= htmlspecialchars($flashMsg) ?>
                <button type="button" class="btn-close btn-close-white ms-3 float-end" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>
    <script>setTimeout(function(){ document.querySelector('.toast')?.classList.remove('show'); }, 4000);</script>
    <?php endif; ?>

    <!-- ── Edit Domain Modal ────────────────────────────────────────────── -->
    <div class="modal fade" id="editDomainModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen me-2 text-primary"></i>Edit Domain Suffix</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="domain-update" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" autocomplete="off">
                        <input type="hidden" name="domain_id" id="editDomainId">

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold text-uppercase">Page Suffix</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light text-muted fw-semibold" id="editBaseDisplay">
                                    <?php echo htmlspecialchars($baseDisplay); ?>
                                </span>
                                <input type="text" name="domain_prefix" id="editDomainPrefix"
                                       class="form-control" placeholder="mystore"
                                       pattern="[a-zA-Z0-9\-]+" required>
                            </div>
                            <div class="form-text mt-2">
                                Your page URL will be:
                                <strong id="editDomainPreview" class="text-primary"></strong>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                                <i class="fa-solid fa-save me-2"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Delete Confirm Modal ─────────────────────────────────────────── -->
    <div class="modal fade" id="deleteDomainModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span style="font-size:2.5rem;">🗑️</span>
                    </div>
                    <h5 class="fw-bold mb-1">Delete Domain?</h5>
                    <p class="text-muted small mb-3">
                        <strong id="deleteDomainName" class="text-danger"></strong> will be removed
                        and its landing page will go offline.
                    </p>
                    <form action="domain-delete" method="POST" id="deleteForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" autocomplete="off">
                        <input type="hidden" name="domain_id" id="deleteDomainId">
                        <div class="d-flex gap-2 justify-content-center">
                            <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger px-4">
                                <i class="fa-solid fa-trash me-1"></i> Delete
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    var domainBase = <?php echo json_encode($baseDisplay); ?>;

    function openEditDomain(btn) {
        var id     = btn.getAttribute('data-domain-id');
        var prefix = btn.getAttribute('data-domain-prefix');
        document.getElementById('editDomainId').value     = id;
        document.getElementById('editDomainPrefix').value = prefix;
        document.getElementById('editDomainPreview').textContent = domainBase + prefix;
        // Live preview inside edit modal
        var input = document.getElementById('editDomainPrefix');
        input.oninput = function() {
            document.getElementById('editDomainPreview').textContent = domainBase + (this.value.trim() || 'mystore');
        };
        new bootstrap.Modal(document.getElementById('editDomainModal')).show();
    }

    function openDeleteDomain(btn) {
        var id   = btn.getAttribute('data-domain-id');
        var name = btn.getAttribute('data-domain-name');
        document.getElementById('deleteDomainId').value           = id;
        document.getElementById('deleteDomainName').textContent   = domainBase + name;
        new bootstrap.Modal(document.getElementById('deleteDomainModal')).show();
    }
    </script>

    <!-- ── Connect Domain Modal ──────────────────────────────────────────── -->
    <div class="modal fade" id="addDomainModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Connect Custom Domain</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <form action="domain-store" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" autocomplete="off">

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold text-uppercase">Choose Your Page Suffix</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light text-muted fw-semibold">
                                    <?php
                                        $baseDisplay = rtrim($_SERVER['HTTP_HOST'], '/') .
                                                       rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
                                        echo htmlspecialchars($baseDisplay);
                                    ?>
                                </span>
                                <input type="text" name="domain_prefix" id="domainSuffixInput"
                                       class="form-control" placeholder="mystore"
                                       pattern="[a-zA-Z0-9\-]+" required>
                            </div>
                            <div class="form-text mt-2">
                                Your page URL will be:
                                <strong id="domainPreview">
                                    <?php echo htmlspecialchars($baseDisplay); ?><span class="text-primary">mystore</span>
                                </strong>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                                <i class="fa-solid fa-link me-2"></i> Connect Page
                            </button>
                        </div>
                    </form>

                    <script>
                    (function(){
                        var input   = document.getElementById('domainSuffixInput');
                        var preview = document.getElementById('domainPreview');
                        if (!input || !preview) return;
                        var base = preview.textContent.replace(/[^/]*$/, '');
                        input.addEventListener('input', function(){
                            var val = this.value.trim() || 'mystore';
                            preview.innerHTML = base + '<span style="color:#316AFF;font-weight:700">' +
                                val.replace(/&/g,'&amp;').replace(/</g,'&lt;') + '</span>';
                        });
                    }());
                    </script>
                </div>
            </div>
        </div>
    </div>

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

  <!-- begin::GXON Page Scripts -->
  <script src="assets/libs/global/global.min.js"></script>
  <script src="assets/js/appSettings.js"></script>
  <script src="assets/js/main.js"></script>
  <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
  <!-- end::GXON Page Scripts -->

</body>
</html>
