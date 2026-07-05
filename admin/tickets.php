<?php
require_once '../includes/load_user.php';
require_once '../includes/mailer.php';
require_admin();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$flash = '';
$flashType = '';

// Helper function to format avatar URL
function get_user_avatar_url($avatar) {
    if (empty($avatar) || $avatar === 'assets/images/avatar/avatar1.webp' || $avatar === '/assets/images/avatar/avatar1.webp') {
        return BASE_URL . '/assets/images/avatar/avatar1.webp';
    } else if (strpos($avatar, 'http') === 0) {
        return $avatar;
    } else if (strpos($avatar, '/assets') === 0 && strpos($avatar, BASE_URL) !== 0) {
        return BASE_URL . $avatar;
    } else if (strpos($avatar, 'assets/') === 0) {
        return BASE_URL . '/' . $avatar;
    }
    return BASE_URL . '/assets/images/avatar/avatar1.webp';
}

// Handle Admin Actions (Reply or Resolve)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_msg'] = 'Security verification failed. Please try again.';
        $_SESSION['flash_type'] = 'danger';
    } else {
        $ticket_id = (int)($_POST['ticket_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        
        if ($ticket_id > 0) {
            if ($action === 'reply') {
                $reply_msg = trim($_POST['admin_reply'] ?? '');
                if (!empty($reply_msg)) {
                    $stmt = $pdo->prepare("UPDATE support_tickets SET admin_reply = ?, status = 'in_progress', user_read = 0, admin_read = 1 WHERE id = ?");
                    $stmt->execute([$reply_msg, $ticket_id]);
                    
                    // Optionally email the user that their ticket has a reply
                    $stmt = $pdo->prepare("SELECT u.email, u.full_name, t.subject FROM support_tickets t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
                    $stmt->execute([$ticket_id]);
                    if ($ticket_user = $stmt->fetch()) {
                        $email_body = "<h3>Hello " . htmlspecialchars($ticket_user['full_name']) . ",</h3>
                                       <p>An admin has replied to your support ticket: <b>" . htmlspecialchars($ticket_user['subject']) . "</b></p>
                                       <p><b>Admin Reply:</b><br>" . nl2br(htmlspecialchars($reply_msg)) . "</p>";
                        send_email($ticket_user['email'], "Update on your Support Ticket", $email_body);
                    }
                    $_SESSION['flash_msg'] = 'Reply sent successfully.';
                    $_SESSION['flash_type'] = 'success';
                } else {
                    $_SESSION['flash_msg'] = 'Please enter a reply message.';
                    $_SESSION['flash_type'] = 'warning';
                }
            } elseif ($action === 'resolve') {
                $stmt = $pdo->prepare("UPDATE support_tickets SET status = 'closed', user_read = 0, admin_read = 1 WHERE id = ?");
                $stmt->execute([$ticket_id]);
                $_SESSION['flash_msg'] = 'Ticket resolved and closed successfully.';
                $_SESSION['flash_type'] = 'success';
            }
        } else {
            $_SESSION['flash_msg'] = 'Invalid ticket ID.';
            $_SESSION['flash_type'] = 'danger';
        }
    }
    // Redirect to avoid form resubmission
    header('Location: tickets.php' . (isset($_GET['id']) ? '?id=' . (int)$_GET['id'] : '') . (isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''));
    exit;
}

// Read and clear session flash messages
if (isset($_SESSION['flash_msg'])) {
    $flash = $_SESSION['flash_msg'];
    $flashType = $_SESSION['flash_type'];
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
}

// Get status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Fetch filtered tickets with user info
$query = "
    SELECT t.*, u.full_name, u.email, u.avatar 
    FROM support_tickets t 
    JOIN users u ON t.user_id = u.id 
";

$where_clauses = [];
$params = [];

if ($status_filter === 'open') {
    $where_clauses[] = "t.status = 'open'";
} elseif ($status_filter === 'pending') {
    $where_clauses[] = "t.status = 'in_progress'";
} elseif ($status_filter === 'resolved') {
    $where_clauses[] = "t.status = 'closed'";
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(' AND ', $where_clauses);
}

$query .= " ORDER BY CASE WHEN t.status = 'open' THEN 1 WHEN t.status = 'in_progress' THEN 2 ELSE 3 END, t.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Get active ticket
$active_ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : (!empty($tickets) ? $tickets[0]['id'] : null);
$active_ticket = null;
if ($active_ticket_id) {
    // Mark as read by admin
    $pdo->prepare("UPDATE support_tickets SET admin_read = 1 WHERE id = ?")->execute([$active_ticket_id]);
    
    $stmt = $pdo->prepare("SELECT t.*, u.full_name, u.email, u.avatar FROM support_tickets t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
    $stmt->execute([$active_ticket_id]);
    $active_ticket = $stmt->fetch();
}

// Global Stats (independent of list filter)
$stmtStats = $pdo->query("SELECT status, COUNT(*) as cnt FROM support_tickets GROUP BY status");
$globalStats = $stmtStats->fetchAll(PDO::FETCH_KEY_PAIR);
$open_count = $globalStats['open'] ?? 0;
$pending_count = $globalStats['in_progress'] ?? 0;
$closed_count = $globalStats['closed'] ?? 0;
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
  <title><?= htmlspecialchars($platform_name) ?> Admin - Support Tickets</title>
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

    <main class="app-wrapper">
        <div class="container-fluid px-4">
            <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between my-4">
                <div class="clearfix">
                    <h1 class="app-page-title h3 mb-0 text-gray-800">Support Tickets</h1>
                    <p class="text-muted small mb-0">View and respond to user support requests</p>
                </div>
            </div>

            <!-- Flash Alerts -->
            <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= htmlspecialchars($flashType) ?> alert-dismissible fade show mb-4 shadow-sm" role="alert">
                <i class="fa-solid <?= $flashType === 'success' ? 'fa-circle-check text-success' : ($flashType === 'warning' ? 'fa-triangle-exclamation text-warning' : 'fa-circle-xmark text-danger') ?> me-2"></i>
                <?= htmlspecialchars($flash) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="row mb-4">
                <div class="col-6 col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <h4 class="text-danger fw-bold mb-1"><?= (int)$open_count ?></h4>
                            <small class="text-muted">Open Tickets</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <h4 class="text-warning fw-bold mb-1"><?= (int)$pending_count ?></h4>
                            <small class="text-muted">In Progress</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <h4 class="text-success fw-bold mb-1"><?= (int)$closed_count ?></h4>
                            <small class="text-muted">Resolved</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-5 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold">Ticket Inbox</h5>
                            <select class="form-select form-select-sm w-auto bg-light border-0 shadow-sm" onchange="location.href='tickets?status=' + this.value + '<?= $active_ticket_id ? '&id='.$active_ticket_id : '' ?>'">
                                <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All</option>
                                <option value="open" <?= $status_filter === 'open' ? 'selected' : '' ?>>Open</option>
                                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="resolved" <?= $status_filter === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                            </select>
                        </div>
                        <div class="card-body p-0" style="max-height: 520px; overflow-y: auto;">
                            <div class="list-group list-group-flush">
                                <?php if (count($tickets) > 0): ?>
                                    <?php foreach ($tickets as $t): ?>
                                        <a href="tickets?id=<?= $t['id'] ?>&status=<?= urlencode($status_filter) ?>" class="list-group-item list-group-item-action px-4 py-3 <?= $t['id'] == $active_ticket_id ? 'bg-primary bg-opacity-10 border-start border-primary border-3' : '' ?>">
                                            <div class="d-flex gap-3 align-items-start">
                                                <img src="<?= htmlspecialchars(get_user_avatar_url($t['avatar'])) ?>" class="avatar avatar-sm rounded-circle flex-shrink-0" style="width:36px;height:36px;object-fit:cover;">
                                                <div class="flex-grow-1 w-100" style="min-width:0;">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <h6 class="mb-0 fw-bold text-truncate" style="max-width: 70%;"><?= htmlspecialchars($t['subject']) ?></h6>
                                                        <small class="text-muted flex-shrink-0"><?= date('M d, H:i', strtotime($t['created_at'])) ?></small>
                                                    </div>
                                                    <p class="text-muted small mb-1 text-truncate"><?= htmlspecialchars($t['message']) ?></p>
                                                    <div class="d-flex align-items-center justify-content-between mt-2">
                                                        <?php
                                                            $bg = 'bg-danger-subtle text-danger';
                                                            if ($t['status'] === 'in_progress') $bg = 'bg-warning-subtle text-warning';
                                                            if ($t['status'] === 'closed') $bg = 'bg-success-subtle text-success';
                                                        ?>
                                                        <span class="badge <?= $bg ?>"><?= ucfirst(str_replace('_', ' ', $t['status'])) ?></span>
                                                        <small class="text-muted text-truncate" style="max-width: 50%;"><?= htmlspecialchars($t['full_name']) ?></small>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="p-4 text-center text-muted">No tickets found.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <?php if ($active_ticket): ?>
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0 fw-bold"><?= htmlspecialchars($active_ticket['subject']) ?></h5>
                                    <small class="text-muted">Ticket #<?= $active_ticket['id'] ?> &middot; Opened by <?= htmlspecialchars($active_ticket['full_name']) ?> &middot; <?= date('M d, Y', strtotime($active_ticket['created_at'])) ?></small>
                                </div>
                                <div class="d-flex gap-2">
                                    <?php if ($active_ticket['status'] === 'open'): ?>
                                        <span class="badge bg-danger">Open</span>
                                    <?php elseif ($active_ticket['status'] === 'in_progress'): ?>
                                        <span class="badge bg-warning text-dark">In Progress</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Resolved</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body" style="max-height: 440px; overflow-y: auto;">
                                <!-- User message -->
                                <div class="d-flex mb-4">
                                    <img src="<?= htmlspecialchars(get_user_avatar_url($active_ticket['avatar'])) ?>" class="avatar avatar-sm rounded-circle me-3 flex-shrink-0" style="width:40px;height:40px;object-fit:cover;">
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between mb-1">
                                            <strong><?= htmlspecialchars($active_ticket['full_name']) ?></strong>
                                            <small class="text-muted"><?= date('M d, h:i A', strtotime($active_ticket['created_at'])) ?></small>
                                        </div>
                                        <div class="bg-light rounded-3 p-3 text-dark">
                                            <p class="mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($active_ticket['message']) ?></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Admin reply -->
                                <?php if (!empty($active_ticket['admin_reply'])): ?>
                                <div class="d-flex mb-4">
                                    <div class="avatar avatar-sm rounded-circle me-3 bg-dark text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px;">
                                        <i class="fa-solid fa-shield-halved" style="font-size:0.8rem;"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between mb-1">
                                            <strong>Admin <span class="badge bg-dark ms-1" style="font-size:0.6rem;">STAFF</span></strong>
                                            <small class="text-muted"><?= date('M d, h:i A', strtotime($active_ticket['updated_at'])) ?></small>
                                        </div>
                                        <div class="bg-primary bg-opacity-10 rounded-3 p-3 text-dark">
                                            <p class="mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($active_ticket['admin_reply']) ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($active_ticket['status'] !== 'closed'): ?>
                            <div class="card-footer bg-transparent border-top py-3">
                                <form method="POST" action="tickets?id=<?= $active_ticket['id'] ?><?= isset($_GET['status']) ? '&status='.urlencode($_GET['status']) : '' ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="ticket_id" value="<?= $active_ticket['id'] ?>">
                                    
                                    <div class="mb-3">
                                        <textarea name="admin_reply" class="form-control" rows="3" placeholder="Type your reply here..." required><?= htmlspecialchars($active_ticket['admin_reply'] ?? '') ?></textarea>
                                    </div>
                                    <div class="d-flex gap-2 justify-content-end">
                                        <button type="submit" name="action" value="reply" class="btn btn-primary shadow-sm"><i class="fa-solid fa-paper-plane me-1"></i> Send Reply</button>
                                        <button type="submit" name="action" value="resolve" class="btn btn-success shadow-sm" formnovalidate><i class="fa-solid fa-check me-1"></i> Mark Resolved</button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="card border-0 shadow-sm text-center py-5">
                            <h5 class="text-muted">Select a ticket to view details</h5>
                        </div>
                    <?php endif; ?>
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
