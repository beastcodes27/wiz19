<?php
require_once 'includes/load_user.php';

// Automatic Database Migration to add sort_order column if not exists
try {
    $pdo->exec("ALTER TABLE videos ADD COLUMN sort_order INT DEFAULT 0;");
} catch (PDOException $e) {
    // Ignore error if column already exists
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle AJAX video reordering action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order') {
    header('Content-Type: application/json');
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Security check failed.']);
        exit;
    }
    
    $order = $_POST['order'] ?? [];
    if (is_array($order)) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE videos SET sort_order = ? WHERE id = ? AND user_id = ?");
            foreach ($order as $index => $vidId) {
                $stmt->execute([(int)$index, (int)$vidId, $user_id]);
            }
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid order data.']);
    }
    exit;
}

$flash = '';
$flashType = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $flash = 'Security check failed.';
        $flashType = 'danger';
    } else {
        $vid = (int)$_POST['id'];
        $action = $_POST['action'];
        // Ownership check
        $own = $pdo->prepare("SELECT id, status FROM videos WHERE id = ? AND user_id = ?");
        $own->execute([$vid, $user_id]);
        $row = $own->fetch();
        if ($row) {
            if ($action === 'activate') {
                $pdo->prepare("UPDATE videos SET status = 'active' WHERE id = ?")->execute([$vid]);
                $flash = 'Video activated successfully.';
                $flashType = 'success';
            } elseif ($action === 'deactivate') {
                $pdo->prepare("UPDATE videos SET status = 'pending' WHERE id = ?")->execute([$vid]);
                $flash = 'Video deactivated successfully.';
                $flashType = 'warning';
            } elseif ($action === 'delete') {
                $pdo->prepare("UPDATE videos SET status = 'deleted' WHERE id = ?")->execute([$vid]);
                $flash = 'Video deleted.';
                $flashType = 'danger';
            }
        } else {
            $flash = 'Video not found or access denied.';
            $flashType = 'danger';
        }
    }
}

// Fetch videos from database (after handling actions so list is fresh)
$stmt = $pdo->prepare("SELECT * FROM videos WHERE user_id = ? AND status != 'deleted' ORDER BY sort_order ASC, created_at DESC");
$stmt->execute([$user_id]);
$videos = $stmt->fetchAll();
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
  
  <title><?= htmlspecialchars($platform_name) ?> - Manage Videos</title>
  
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
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
  
  <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

  <style>
      /* Drag & Drop Styling */
      .drag-handle { cursor: grab; color: #adb5bd; transition: all 0.2s; width: 40px; }
      .drag-handle:hover { color: #5d66e5; }
      .drag-handle:active { cursor: grabbing; }
      .sortable-ghost { opacity: 0.4; background: #f8f9fa !important; border: 2px dashed #5d66e5; }
      .sortable-chosen { background: #fff; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1); }

      /* Table Aesthetics */
      .table thead th { 
          background-color: #3a3b4d; 
          text-transform: uppercase; 
          font-size: 0.75rem; 
          letter-spacing: 0.5px; 
          font-weight: 700;
          border-bottom: 1px solid #edf2f9;
          color: white; /* Added to make header text visible */
      }
      .table tbody td { vertical-align: middle; padding: 1rem 0.75rem; color: #4b5563; }
      
      /* Modern Status Badges */
      .badge-soft { padding: 0.4em 0.8em; border-radius: 50rem; font-weight: 600; font-size: 0.7rem; }
      .bg-soft-success { background-color: #e1f6e5; color: #15803d; }
      .bg-soft-warning { background-color: #fef3c7; color: #b45309; }
      .bg-soft-secondary { background-color: #f3f4f6; color: #4b5563; }

      /* Action Buttons */
      .btn-action {
          width: 32px;
          height: 32px;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          border-radius: 8px;
          transition: transform 0.2s;
      }
      .btn-action:hover { transform: translateY(-2px); }
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

    <main class="app-wrapper">
        <div class="container-fluid px-4">
            <div class="app-page-head d-flex align-items-center justify-content-between my-4">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Video Content</h1>
                    <p class="text-muted small mb-0">Manage and reorder your video library</p>
                </div>
                <a href="video-upload" class="btn btn-primary btn-sm px-3 shadow-sm">
                    <i class="fi fi-rr-plus small me-1"></i> Upload New
                </a>
            </div>

            <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flashType; ?> alert-dismissible fade show mb-3" role="alert">
                <?php echo htmlspecialchars($flash); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm mb-4">
                  <div class="card-header">
                    <h5 class="card-title mb-0 fw-bold">Library List</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="dataTableExample" class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center">Order</th>
                                    <th>Video Details</th>
                                    <th>Price</th>
                                    <th>Stats</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="sortable-video-list">
                                <?php foreach ($videos as $video): 
    // Determine status styling
    $status_color = 'bg-soft-secondary';
    $status_text = ucfirst($video['status']);
    if ($video['status'] === 'active') {
        $status_color = 'bg-soft-success';
    } elseif ($video['status'] === 'pending') {
        $status_color = 'bg-soft-warning';
    } elseif ($video['status'] === 'deleted') {
        $status_color = 'bg-soft-danger';
    }
?>
<tr data-id="<?php echo htmlspecialchars($video['id']); ?>">
    <td class="drag-handle text-center">
        <i class="fi fi-sr-grip-dots-vertical"></i>
    </td>
    <td>
        <div class="d-flex align-items-center">
            <?php if(!empty($video['thumbnail_url'])): ?>
                <img src="<?php echo htmlspecialchars($video['thumbnail_url']); ?>" class="rounded-3 me-3 object-fit-cover" style="width: 45px; height: 45px;" alt="Video">
            <?php else: ?>
                <div class="rounded me-3 bg-dark d-flex align-items-center justify-content-center" style="width:45px;height:45px;">
                    <i class="fa-solid fa-play text-white" style="font-size:0.7rem;"></i>
                </div>
            <?php endif; ?>
            <div>
                <div class="fw-bold text-dark"><?php echo htmlspecialchars($video['title']); ?></div>
                <span class="text-muted small">ID: #<?php echo htmlspecialchars($video['id']); ?></span>
            </div>
        </div>
    </td>
    <td>
        <span class="fw-semibold text-success">TZS <?php echo number_format($video['price'] ?? 0); ?></span>
    </td>
    <td>
        <div class="d-flex align-items-center">
            <i class="fi fi-rr-eye me-2 text-muted"></i> <?php echo htmlspecialchars($video['views']); ?>
        </div>
    </td>
    <td>
        <span class="badge-soft <?php echo $status_color; ?> text-uppercase"><?php echo htmlspecialchars($status_text); ?></span>
    </td>
    <td><?php echo date('d M, Y', strtotime($video['created_at'])); ?></td>
    <td class="text-end pe-3">
        <div class="dropdown">
            <button class="btn btn-sm btn-light rounded-pill px-3" type="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-solid fa-ellipsis-vertical"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <!-- Edit -->
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2"
                       href="video-edit?id=<?php echo $video['id']; ?>">
                        <i class="fa-solid fa-pen-to-square text-primary"></i> Edit
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <!-- Activate / Deactivate toggle -->
                <?php if ($video['status'] === 'active'): ?>
                <li>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="deactivate">
                        <input type="hidden" name="id" value="<?php echo $video['id']; ?>">
                        <button type="submit" class="dropdown-item d-flex align-items-center gap-2"
                                onclick="return confirm('Deactivate this video? It will be hidden from viewers.')">
                            <i class="fa-solid fa-circle-pause text-warning"></i> Deactivate
                        </button>
                    </form>
                </li>
                <?php else: ?>
                <li>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="activate">
                        <input type="hidden" name="id" value="<?php echo $video['id']; ?>">
                        <button type="submit" class="dropdown-item d-flex align-items-center gap-2">
                            <i class="fa-solid fa-circle-play text-success"></i> Activate
                        </button>
                    </form>
                </li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <!-- Delete -->
                <li>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $video['id']; ?>">
                        <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-danger"
                                onclick="return confirm('Permanently delete this video? This cannot be undone.')">
                            <i class="fa-solid fa-trash"></i> Delete
                        </button>
                    </form>
                </li>
            </ul>
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

  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
  <!-- SortableJS for drag and drop -->
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

  <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#dataTableExample').DataTable({
            "pageLength": 10,
            "ordering": false
        });

        // Initialize Sortable for drag-and-drop reordering
        const sortableList = document.getElementById('sortable-video-list');
        if (sortableList) {
            new Sortable(sortableList, {
                handle: '.drag-handle',
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                onEnd: function (evt) {
                    const orderIds = [];
                    $('#sortable-video-list tr').each(function() {
                        const id = $(this).attr('data-id');
                        if (id) {
                            orderIds.push(id);
                        }
                    });
                    
                    $.ajax({
                        url: 'videos',
                        type: 'POST',
                        data: {
                            action: 'update_order',
                            order: orderIds,
                            csrf_token: $('meta[name="csrf-token"]').attr('content')
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (!response.success) {
                                alert('Failed to save arrangement: ' + (response.message || 'Unknown error'));
                            }
                        },
                        error: function() {
                            console.error('Reorder network error.');
                        }
                    });
                }
            });
        }

        // Fix dropdown clipping inside table-responsive
        document.addEventListener('show.bs.dropdown', function(e) {
            const tr = e.target.closest('.table-responsive');
            if (tr) tr.style.overflow = 'visible';
        });
        document.addEventListener('hide.bs.dropdown', function(e) {
            const tr = e.target.closest('.table-responsive');
            if (tr) tr.style.overflow = '';
        });
    });
  </script>
</body>
</html>
