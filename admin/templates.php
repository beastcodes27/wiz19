<?php
require_once '../includes/load_user.php';
require_admin();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Flash messages
$message     = $_SESSION['flash_message'] ?? '';
$messageType = $_SESSION['flash_type']    ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// ── Helpers ───────────────────────────────────────────────────────────────────

function templates_dir(): string  { return realpath(__DIR__ . '/../templates') . DIRECTORY_SEPARATOR; }
function thumbs_dir():    string  { return realpath(__DIR__ . '/../assets/landing') . DIRECTORY_SEPARATOR; }
function thumbs_url():    string  { return BASE_URL . '/assets/landing/'; }

/**
 * Loads an image from a temp path, resizes it to target dimensions, and saves as PNG.
 * Preserves transparency for PNG/WebP images.
 */
function process_and_resize_image(string $tmpPath, string $destPath, int $width = 1440, int $height = 2960): bool {
    $info = getimagesize($tmpPath);
    if (!$info) {
        return false;
    }

    switch ($info['mime']) {
        case 'image/jpeg':
            $src = imagecreatefromjpeg($tmpPath);
            break;
        case 'image/png':
            $src = imagecreatefrompng($tmpPath);
            break;
        case 'image/webp':
            $src = imagecreatefromwebp($tmpPath);
            break;
        default:
            return false;
    }

    if (!$src) {
        return false;
    }

    $dst = imagecreatetruecolor($width, $height);
    if (!$dst) {
        imagedestroy($src);
        return false;
    }

    // Preserve alpha/transparency channel
    imagealphablending($dst, false);
    imagesavealpha($dst, true);

    $origWidth  = imagesx($src);
    $origHeight = imagesy($src);

    $success = imagecopyresampled(
        $dst,
        $src,
        0, 0, 0, 0,
        $width,
        $height,
        $origWidth,
        $origHeight
    );

    if ($success) {
        $success = imagepng($dst, $destPath);
    }

    imagedestroy($src);
    imagedestroy($dst);

    return $success;
}


/** Return all landing*.php template keys, sorted numerically */
function get_all_template_keys(): array {
    $files = glob(templates_dir() . 'landing*.php');
    if (!$files) return [];
    $keys = array_map(fn($f) => basename($f, '.php'), $files);
    usort($keys, fn($a, $b) => strnatcmp($a, $b));
    return $keys;
}

/** Next sequential key: landing9, landing10 … */
function next_template_key(): string {
    $keys = get_all_template_keys();
    $max  = 0;
    foreach ($keys as $k) {
        if (preg_match('/^landing(\d+)$/', $k, $m)) {
            $max = max($max, (int)$m[1]);
        }
    }
    return 'landing' . ($max + 1);
}

// ── POST Handlers ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = 'Invalid CSRF token.';
        $_SESSION['flash_type']    = 'danger';
        header('Location: templates'); exit;
    }

    $action = $_POST['action'] ?? '';

    // ── Upload new template ───────────────────────────────────────────────────
    if ($action === 'upload_template') {
        $tplFile   = $_FILES['template_file']  ?? null;
        $thumbFile = $_FILES['thumbnail_file'] ?? null;
        $errors    = [];

        // Validate PHP template file
        if (!$tplFile || $tplFile['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Please upload a valid PHP template file.';
        } elseif (strtolower(pathinfo($tplFile['name'], PATHINFO_EXTENSION)) !== 'php') {
            $errors[] = 'Template must be a .php file.';
        } elseif ($tplFile['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Template file must be under 5 MB.';
        }

        // Validate thumbnail image
        if (!$thumbFile || $thumbFile['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Please upload a thumbnail image.';
        } else {
            $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($thumbFile['type'], $allowedMime)) {
                $errors[] = 'Thumbnail must be JPG, PNG, or WebP.';
            } elseif ($thumbFile['size'] > 8 * 1024 * 1024) {
                $errors[] = 'Thumbnail must be under 8 MB.';
            }
        }

        if ($errors) {
            $_SESSION['flash_message'] = implode('<br>', $errors);
            $_SESSION['flash_type']    = 'danger';
            header('Location: templates'); exit;
        }

        $key        = next_template_key();
        $tplDest    = templates_dir() . $key . '.php';
        $thumbDest  = thumbs_dir()    . $key . '.png';

        // Save PHP template
        if (!move_uploaded_file($tplFile['tmp_name'], $tplDest)) {
            $_SESSION['flash_message'] = 'Failed to save template file. Check folder permissions.';
            $_SESSION['flash_type']    = 'danger';
            header('Location: templates'); exit;
        }

        // Process and resize thumbnail to 1440x2960
        if (!process_and_resize_image($thumbFile['tmp_name'], $thumbDest, 1440, 2960)) {
            unlink($tplDest);
            $_SESSION['flash_message'] = 'Failed to process and resize thumbnail image.';
            $_SESSION['flash_type']    = 'danger';
            header('Location: templates'); exit;
        }

        $_SESSION['flash_message'] = "Template <strong>{$key}</strong> uploaded successfully!";
        $_SESSION['flash_type']    = 'success';
        header('Location: templates'); exit;
    }

    // ── Edit template ─────────────────────────────────────────────────────────
    if ($action === 'edit_template') {
        $key       = trim($_POST['template_key'] ?? '');
        $tplFile   = $_FILES['template_file']  ?? null;
        $thumbFile = $_FILES['thumbnail_file'] ?? null;
        $errors    = [];

        // Validate key
        if (!preg_match('/^landing[a-z0-9_-]+$/i', $key)) {
            $_SESSION['flash_message'] = 'Invalid template key.';
            $_SESSION['flash_type']    = 'danger';
            header('Location: templates'); exit;
        }

        $tplDest   = templates_dir() . $key . '.php';
        $thumbDest = thumbs_dir()    . $key . '.png';

        if (!file_exists($tplDest)) {
            $_SESSION['flash_message'] = 'Template file not found.';
            $_SESSION['flash_type']    = 'danger';
            header('Location: templates'); exit;
        }

        $hasTpl   = ($tplFile && $tplFile['error'] === UPLOAD_ERR_OK);
        $hasThumb = ($thumbFile && $thumbFile['error'] === UPLOAD_ERR_OK);

        if (!$hasTpl && !$hasThumb) {
            $_SESSION['flash_message'] = 'Please select a template file or a thumbnail image to update.';
            $_SESSION['flash_type']    = 'warning';
            header('Location: templates'); exit;
        }

        // Validate PHP template file if uploaded
        if ($hasTpl) {
            if (strtolower(pathinfo($tplFile['name'], PATHINFO_EXTENSION)) !== 'php') {
                $errors[] = 'Template must be a .php file.';
            } elseif ($tplFile['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Template file must be under 5 MB.';
            }
        }

        // Validate thumbnail image if uploaded
        if ($hasThumb) {
            $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($thumbFile['type'], $allowedMime)) {
                $errors[] = 'Thumbnail must be JPG, PNG, or WebP.';
            } elseif ($thumbFile['size'] > 8 * 1024 * 1024) {
                $errors[] = 'Thumbnail must be under 8 MB.';
            }
        }

        if ($errors) {
            $_SESSION['flash_message'] = implode('<br>', $errors);
            $_SESSION['flash_type']    = 'danger';
            header('Location: templates'); exit;
        }

        // Save PHP template if uploaded
        if ($hasTpl) {
            if (!move_uploaded_file($tplFile['tmp_name'], $tplDest)) {
                $_SESSION['flash_message'] = 'Failed to save template file. Check folder permissions.';
                $_SESSION['flash_type']    = 'danger';
                header('Location: templates'); exit;
            }
        }

        // Save thumbnail image if uploaded
        if ($hasThumb) {
            if (!process_and_resize_image($thumbFile['tmp_name'], $thumbDest, 1440, 2960)) {
                $_SESSION['flash_message'] = 'Failed to process and resize thumbnail image.';
                $_SESSION['flash_type']    = 'danger';
                header('Location: templates'); exit;
            }
        }

        $_SESSION['flash_message'] = "Template <strong>{$key}</strong> updated successfully!";
        $_SESSION['flash_type']    = 'success';
        header('Location: templates'); exit;
    }

    // ── Delete a template ─────────────────────────────────────────────────────
    if ($action === 'delete_template') {
        $key = trim($_POST['template_key'] ?? '');

        // Only allow deleting non-built-in templates
        $builtIn = ['landing1','landing2','landing3','landing4','landing5','landing6','landing7','landing8'];
        if (!preg_match('/^landing[a-z0-9_-]+$/i', $key) || in_array($key, $builtIn, true)) {
            $_SESSION['flash_message'] = 'Cannot delete a built-in template.';
            $_SESSION['flash_type']    = 'warning';
            header('Location: templates'); exit;
        }

        $tplPath   = templates_dir() . $key . '.php';
        $thumbPath = thumbs_dir()    . $key . '.png';

        if (file_exists($tplPath))  unlink($tplPath);
        if (file_exists($thumbPath)) unlink($thumbPath);

        // Reset any users who had this template active
        $pdo->prepare("UPDATE users SET active_landing = 'landing1' WHERE active_landing = ?")
            ->execute([$key]);

        $_SESSION['flash_message'] = "Template <strong>{$key}</strong> deleted.";
        $_SESSION['flash_type']    = 'success';
        header('Location: templates'); exit;
    }
}

// ── Load data ─────────────────────────────────────────────────────────────────
$templateKeys = get_all_template_keys();
$builtInKeys  = ['landing1','landing2','landing3','landing4','landing5','landing6','landing7','landing8'];

// Count how many users use each template
$usageMap = [];
$usageStmt = $pdo->query("SELECT active_landing, COUNT(*) as cnt FROM users GROUP BY active_landing");
foreach ($usageStmt->fetchAll() as $row) {
    $usageMap[$row['active_landing']] = (int)$row['cnt'];
}

$nextKey = next_template_key();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="theme-color" content="#316AFF">
  <meta name="robots" content="noindex, nofollow">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($platform_name) ?> Admin — Template Manager</title>

  <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="../assets/libs/flaticon/css/all/all.css">
  <link rel="stylesheet" href="../assets/libs/lucide/lucide.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/libs/simplebar/simplebar.css">
  <link rel="stylesheet" href="../assets/libs/node-waves/waves.css">
  <link rel="stylesheet" href="../assets/libs/bootstrap-select/css/bootstrap-select.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">

  <style>
    /* ── Template grid card ─────────────────────────────────────────────── */
    .tpl-card {
        position: relative;
        border-radius: 14px;
        overflow: hidden;
        border: 2px solid var(--bs-border-color, #e9ecef);
        background: var(--bs-card-bg, #fff);
        transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
        box-shadow: 0 2px 12px rgba(0,0,0,.07);
    }
    .tpl-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(49,106,255,.18);
        border-color: #316AFF;
    }
    .tpl-thumb {
        width: 100%;
        aspect-ratio: 16/9;
        object-fit: cover;
        display: block;
        background: #1a1a2e;
    }
    .tpl-thumb-placeholder {
        width: 100%;
        aspect-ratio: 16/9;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        color: #556;
        font-size: 2.5rem;
    }
    .tpl-footer {
        padding: 12px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    .tpl-name {
        font-weight: 700;
        font-size: .9rem;
        letter-spacing: .01em;
        text-transform: uppercase;
    }
    .tpl-badge-builtin {
        font-size: .68rem;
        padding: 2px 8px;
        border-radius: 999px;
        background: #e8f0fe;
        color: #3c4fe0;
        font-weight: 600;
        letter-spacing: .03em;
    }
    .tpl-badge-custom {
        font-size: .68rem;
        padding: 2px 8px;
        border-radius: 999px;
        background: #fff3e0;
        color: #e65100;
        font-weight: 600;
        letter-spacing: .03em;
    }
    .tpl-usage {
        font-size: .75rem;
        color: #888;
    }
    .btn-del {
        color: #dc3545;
        border: 1px solid #dc354530;
        background: transparent;
        border-radius: 8px;
        padding: 4px 10px;
        font-size: .78rem;
        transition: all .18s;
    }
    .btn-del:hover {
        background: #dc3545;
        color: #fff;
        border-color: #dc3545;
    }
    .btn-edit {
        color: #316AFF;
        border: 1px solid #316AFF30;
        background: transparent;
        border-radius: 8px;
        padding: 4px 10px;
        font-size: .78rem;
        transition: all .18s;
    }
    .btn-edit:hover {
        background: #316AFF;
        color: #fff;
        border-color: #316AFF;
    }

    /* ── Upload drop zone ───────────────────────────────────────────────── */
    .drop-zone {
        border: 2px dashed #316AFF80;
        border-radius: 14px;
        padding: 36px 24px;
        text-align: center;
        transition: background .2s, border-color .2s;
        cursor: pointer;
        background: #316AFF08;
    }
    .drop-zone:hover, .drop-zone.drag-over {
        background: #316AFF14;
        border-color: #316AFF;
    }
    .drop-zone .dz-icon { font-size: 2.2rem; color: #316AFF; margin-bottom: 10px; }
    .drop-zone .dz-label { font-weight: 600; font-size: .95rem; }
    .drop-zone .dz-sub { font-size: .8rem; color: #888; margin-top: 4px; }
    .drop-zone .selected-name {
        margin-top: 10px;
        font-size: .82rem;
        color: #316AFF;
        font-weight: 600;
        word-break: break-all;
    }
    .thumb-preview-wrap {
        display: none;
        margin-top: 12px;
    }
    .thumb-preview-wrap img {
        width: 100%;
        max-height: 180px;
        object-fit: cover;
        border-radius: 10px;
        border: 1px solid #ddd;
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
                  <li><div class="dropdown-divider my-1"></div></li>
                  <li>
                      <a class="dropdown-item d-flex align-items-center gap-2" href="../profile">
                          <i class="fa-solid fa-user scale-1x"></i> View Profile
                      </a>
                  </li>
                  <li><div class="dropdown-divider my-1"></div></li>
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
                      <button type="button" class="btn btn-sm border-0 position-absolute start-0 p-0 text-sm">
                          <i class="fa-solid fa-magnifying-glass"></i>
                      </button>
                      <input type="text" class="form-control form-control-lg ps-4 border-0 shadow-none"
                          id="searchInput" placeholder="Search anything's">
                  </form>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body pb-2" style="height: 300px;" data-simplebar>
                  <div id="recentlyResults">
                      <span class="text-uppercase text-2xs fw-semibold text-muted d-block mb-2">Recently Searched:</span>
                  </div>
                  <div id="searchContainer"></div>
              </div>
          </div>
      </div>
  </div>

  <!-- ── Sidebar ─────────────────────────────────────────────────────────── -->
  <aside class="app-menubar" id="appMenubar">
    <div class="app-navbar-brand">
      <a class="navbar-brand-logo" href="index">
        <img src="<?= BASE_URL ?>/assets/images/logo.png?v=<?= file_exists('../assets/images/logo.png') ? filemtime('../assets/images/logo.png') : time() ?>"
             alt="Dashboard" style="max-height:40px;width:auto;">
      </a>
    </div>
    <nav class="app-navbar" data-simplebar>
      <ul class="menubar">
        <li class="menu-item">
          <a class="menu-link" href="index"><i class="fa-solid fa-gauge"></i><span class="menu-label">Overview</span></a>
        </li>
        <li class="menu-item">
          <a class="menu-link" href="users"><i class="fa-solid fa-users"></i><span class="menu-label">User Management</span></a>
        </li>
        <li class="menu-item">
          <a class="menu-link" href="videos"><i class="fa-solid fa-video"></i><span class="menu-label">System Videos</span></a>
        </li>
        <li class="menu-item">
          <a class="menu-link" href="transactions"><i class="fa-solid fa-receipt"></i><span class="menu-label">Global Transactions</span></a>
        </li>
        <li class="menu-item">
          <a class="menu-link" href="announcements"><i class="fa-solid fa-bullhorn"></i><span class="menu-label">Announcements</span></a>
        </li>
        <li class="menu-item">
          <a class="menu-link" href="tickets"><i class="fa-solid fa-headset"></i><span class="menu-label">Support Tickets</span></a>
        </li>
        <li class="menu-item active">
          <a class="menu-link" href="templates"><i class="fa-solid fa-layer-group"></i><span class="menu-label">Landing Templates</span></a>
        </li>
        <li class="menu-item">
          <a class="menu-link" href="settings"><i class="fa-solid fa-gear"></i><span class="menu-label">Platform Settings</span></a>
        </li>
        <li class="menu-item">
          <a class="menu-link" href="../dashboard"><i class="fa-solid fa-arrow-left"></i><span class="menu-label">Exit Admin</span></a>
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

  <!-- ── Main content ────────────────────────────────────────────────────── -->
  <main class="app-wrapper">
    <div class="container">

      <!-- Page head -->
      <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div class="clearfix">
          <h1 class="app-page-title">Landing Page Templates</h1>
          <p class="text-muted mb-0">Upload and manage templates available to all users</p>
        </div>
        <div>
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="fa-solid fa-upload me-2"></i>Upload New Template
          </button>
        </div>
      </div>

      <!-- Flash message -->
      <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
          <?= $message ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Stats strip -->
      <div class="row g-3 mb-4">
        <div class="col-sm-4">
          <div class="card border-0 shadow-sm text-center py-3">
            <div class="fw-bold fs-3 text-primary"><?= count($templateKeys) ?></div>
            <div class="text-muted small">Total Templates</div>
          </div>
        </div>
        <div class="col-sm-4">
          <div class="card border-0 shadow-sm text-center py-3">
            <div class="fw-bold fs-3 text-success"><?= count(array_diff($templateKeys, $builtInKeys)) ?></div>
            <div class="text-muted small">Custom Uploaded</div>
          </div>
        </div>
        <div class="col-sm-4">
          <div class="card border-0 shadow-sm text-center py-3">
            <div class="fw-bold fs-3 text-info"><?= htmlspecialchars($nextKey) ?></div>
            <div class="text-muted small">Next Template Slot</div>
          </div>
        </div>
      </div>

      <!-- Template grid -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-bottom py-3">
          <h5 class="mb-0 fw-bold"><i class="fa-solid fa-grid-2 me-2"></i>All Templates</h5>
        </div>
        <div class="card-body">
          <div class="row g-4">
            <?php foreach ($templateKeys as $key):
                $thumbSrc  = thumbs_url() . $key . '.png';
                $thumbFile = thumbs_dir() . $key . '.png';
                $isBuiltIn = in_array($key, $builtInKeys, true);
                $usedBy    = $usageMap[$key] ?? 0;
                $label     = 'Template ' . ltrim(str_replace('landing', '', $key), '0');
            ?>
            <div class="col-xl-3 col-lg-4 col-sm-6">
              <div class="tpl-card">
                <?php if (file_exists($thumbFile)): ?>
                  <img src="<?= htmlspecialchars($thumbSrc) ?>?v=<?= filemtime($thumbFile) ?>"
                       alt="<?= htmlspecialchars($key) ?>" class="tpl-thumb">
                <?php else: ?>
                  <div class="tpl-thumb-placeholder">
                    <i class="fa-solid fa-image"></i>
                  </div>
                <?php endif; ?>

                <div class="tpl-footer">
                  <div>
                    <div class="tpl-name"><?= htmlspecialchars($label) ?></div>
                    <div class="mt-1 d-flex align-items-center gap-2">
                      <span class="<?= $isBuiltIn ? 'tpl-badge-builtin' : 'tpl-badge-custom' ?>">
                        <?= $isBuiltIn ? 'Built-in' : 'Custom' ?>
                      </span>
                      <?php if ($usedBy > 0): ?>
                        <span class="tpl-usage"><i class="fa-solid fa-users fa-xs"></i> <?= $usedBy ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="d-flex align-items-center gap-1">
                    <button type="button" class="btn-edit" title="Edit Template" onclick="openEditTemplateModal('<?= htmlspecialchars($key) ?>', '<?= htmlspecialchars($label) ?>')">
                      <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <?php if (!$isBuiltIn): ?>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete <?= htmlspecialchars($key) ?>? Users on this template will be reset to Template 1.');">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                      <input type="hidden" name="action" value="delete_template">
                      <input type="hidden" name="template_key" value="<?= htmlspecialchars($key) ?>">
                      <button type="submit" class="btn-del">
                        <i class="fa-solid fa-trash-can"></i>
                      </button>
                    </form>
                    <?php else: ?>
                      <span title="Built-in templates cannot be deleted" style="color:#ccc; font-size:.8rem; padding: 4px 6px;">
                        <i class="fa-solid fa-lock"></i>
                      </span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Instructions card -->
      <div class="card border-0 shadow-sm mb-5">
        <div class="card-header bg-transparent border-bottom py-3">
          <h5 class="mb-0 fw-bold"><i class="fa-solid fa-circle-info me-2 text-primary"></i>Template Requirements</h5>
        </div>
        <div class="card-body">
          <div class="row g-4">
            <div class="col-md-6">
              <h6 class="fw-bold mb-2"><i class="fa-brands fa-php me-2 text-primary"></i>PHP Template File</h6>
              <ul class="text-muted small mb-0 ps-3">
                <li>Extension must be <code>.php</code></li>
                <li>Max size: <strong>5 MB</strong></li>
                <li>Available variables: <code>$domain_owner</code>, <code>$user_videos</code>, <code>$pdo</code>, <code>$platform_name</code></li>
                <li>Will be saved as <code>templates/<?= htmlspecialchars($nextKey) ?>.php</code></li>
              </ul>
            </div>
            <div class="col-md-6">
              <h6 class="fw-bold mb-2"><i class="fa-solid fa-image me-2 text-success"></i>Thumbnail Image</h6>
              <ul class="text-muted small mb-0 ps-3">
                <li>Formats: JPG, PNG, WebP</li>
                <li>Max size: <strong>8 MB</strong></li>
                <li>Recommended: <strong>1280 × 720 px</strong> (16:9)</li>
                <li>Will be saved as <code>assets/landing/<?= htmlspecialchars($nextKey) ?>.png</code></li>
              </ul>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /container -->
  </main>

</div><!-- /page-layout -->

<!-- ── Upload Modal ────────────────────────────────────────────────────────── -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold" id="uploadModalLabel">
          <i class="fa-solid fa-upload me-2 text-primary"></i>Upload New Template
          <span class="badge bg-primary ms-2" style="font-size:.7rem;"><?= htmlspecialchars($nextKey) ?></span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <div class="modal-body pt-3">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
          <input type="hidden" name="action" value="upload_template">

          <div class="row g-4">
            <!-- PHP file -->
            <div class="col-md-6">
              <label class="form-label fw-semibold mb-2">
                <i class="fa-brands fa-php me-1 text-primary"></i>PHP Template File <span class="text-danger">*</span>
              </label>
              <div class="drop-zone" id="phpDropZone" onclick="document.getElementById('templateFileInput').click()">
                <div class="dz-icon"><i class="fa-brands fa-php"></i></div>
                <div class="dz-label">Click to choose PHP file</div>
                <div class="dz-sub">or drag &amp; drop here</div>
                <div class="selected-name" id="phpFileName"></div>
              </div>
              <input type="file" id="templateFileInput" name="template_file" accept=".php" class="d-none" required>
            </div>

            <!-- Thumbnail -->
            <div class="col-md-6">
              <label class="form-label fw-semibold mb-2">
                <i class="fa-solid fa-image me-1 text-success"></i>Thumbnail Image <span class="text-danger">*</span>
              </label>
              <div class="drop-zone" id="imgDropZone" onclick="document.getElementById('thumbnailFileInput').click()">
                <div class="dz-icon"><i class="fa-solid fa-image"></i></div>
                <div class="dz-label">Click to choose thumbnail</div>
                <div class="dz-sub">JPG, PNG or WebP · 16:9 recommended</div>
                <div class="selected-name" id="imgFileName"></div>
              </div>
              <input type="file" id="thumbnailFileInput" name="thumbnail_file" accept="image/jpeg,image/png,image/webp" class="d-none" required>
              <div class="thumb-preview-wrap" id="thumbPreviewWrap">
                <img id="thumbPreviewImg" src="" alt="Thumbnail preview">
              </div>
            </div>
          </div>

          <div class="alert alert-info mt-4 mb-0 small">
            <i class="fa-solid fa-lightbulb me-1"></i>
            The template will be auto-assigned the key <strong><?= htmlspecialchars($nextKey) ?></strong>.
            Users can then select it from their Settings page.
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4">
            <i class="fa-solid fa-cloud-arrow-up me-2"></i>Upload Template
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── Edit Modal ────────────────────────────────────────────────────────── -->
<div class="modal fade" id="editTemplateModal" tabindex="-1" aria-labelledby="editTemplateModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold" id="editTemplateModalLabel">
          <i class="fa-solid fa-pen-to-square me-2 text-primary"></i>Edit Template
          <span class="badge bg-primary ms-2" id="editModalKeyBadge" style="font-size:.7rem;"></span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <div class="modal-body pt-3">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
          <input type="hidden" name="action" value="edit_template">
          <input type="hidden" name="template_key" id="editTemplateKeyInput">

          <div class="row g-4">
            <!-- PHP file -->
            <div class="col-md-6">
              <label class="form-label fw-semibold mb-2">
                <i class="fa-brands fa-php me-1 text-primary"></i>PHP Template File <span class="text-muted">(Optional)</span>
              </label>
              <div class="drop-zone" id="editPhpDropZone" onclick="document.getElementById('editTemplateFileInput').click()">
                <div class="dz-icon"><i class="fa-brands fa-php"></i></div>
                <div class="dz-label">Click to choose PHP file</div>
                <div class="dz-sub">or drag &amp; drop here</div>
                <div class="selected-name" id="editPhpFileName"></div>
              </div>
              <input type="file" id="editTemplateFileInput" name="template_file" accept=".php" class="d-none">
            </div>

            <!-- Thumbnail -->
            <div class="col-md-6">
              <label class="form-label fw-semibold mb-2">
                <i class="fa-solid fa-image me-1 text-success"></i>Thumbnail Image <span class="text-muted">(Optional)</span>
              </label>
              <div class="drop-zone" id="editImgDropZone" onclick="document.getElementById('editThumbnailFileInput').click()">
                <div class="dz-icon"><i class="fa-solid fa-image"></i></div>
                <div class="dz-label">Click to choose thumbnail</div>
                <div class="dz-sub">JPG, PNG or WebP · 16:9 recommended</div>
                <div class="selected-name" id="editImgFileName"></div>
              </div>
              <input type="file" id="editThumbnailFileInput" name="thumbnail_file" accept="image/jpeg,image/png,image/webp" class="d-none">
              <div class="thumb-preview-wrap" id="editThumbPreviewWrap">
                <img id="editThumbPreviewImg" src="" alt="Thumbnail preview">
              </div>
            </div>
          </div>

          <div class="alert alert-info mt-4 mb-0 small">
            <i class="fa-solid fa-lightbulb me-1"></i>
            Leave either input empty to preserve the template's current file or thumbnail.
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4">
            <i class="fa-solid fa-save me-2"></i>Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── Scripts ─────────────────────────────────────────────────────────────── -->
<script src="../assets/libs/global/global.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
(function () {
    // PHP file drop-zone
    const phpInput    = document.getElementById('templateFileInput');
    const phpDropZone = document.getElementById('phpDropZone');
    const phpFileName = document.getElementById('phpFileName');

    phpInput.addEventListener('change', function () {
        const f = this.files[0];
        phpFileName.textContent = f ? f.name : '';
        phpDropZone.classList.toggle('drag-over', !!f);
    });
    setupDrop(phpDropZone, phpInput, phpFileName);

    // Image file drop-zone
    const imgInput      = document.getElementById('thumbnailFileInput');
    const imgDropZone   = document.getElementById('imgDropZone');
    const imgFileName   = document.getElementById('imgFileName');
    const thumbWrap     = document.getElementById('thumbPreviewWrap');
    const thumbPreview  = document.getElementById('thumbPreviewImg');

    imgInput.addEventListener('change', function () {
        const f = this.files[0];
        if (!f) return;
        imgFileName.textContent = f.name;
        imgDropZone.classList.add('drag-over');
        const reader = new FileReader();
        reader.onload = e => {
            thumbPreview.src  = e.target.result;
            thumbWrap.style.display = 'block';
        };
        reader.readAsDataURL(f);
    });
    setupDrop(imgDropZone, imgInput, imgFileName, true);

    // Edit PHP file drop-zone
    const editPhpInput    = document.getElementById('editTemplateFileInput');
    const editPhpDropZone = document.getElementById('editPhpDropZone');
    const editPhpFileName = document.getElementById('editPhpFileName');

    editPhpInput.addEventListener('change', function () {
        const f = this.files[0];
        editPhpFileName.textContent = f ? f.name : '';
        editPhpDropZone.classList.toggle('drag-over', !!f);
    });
    setupDrop(editPhpDropZone, editPhpInput, editPhpFileName);

    // Edit Image file drop-zone
    const editImgInput      = document.getElementById('editThumbnailFileInput');
    const editImgDropZone   = document.getElementById('editImgDropZone');
    const editImgFileName   = document.getElementById('editImgFileName');
    const editThumbWrap     = document.getElementById('editThumbPreviewWrap');
    const editThumbPreview  = document.getElementById('editThumbPreviewImg');

    editImgInput.addEventListener('change', function () {
        const f = this.files[0];
        if (!f) return;
        editImgFileName.textContent = f.name;
        editImgDropZone.classList.add('drag-over');
        const reader = new FileReader();
        reader.onload = e => {
            editThumbPreview.src  = e.target.result;
            editThumbWrap.style.display = 'block';
        };
        reader.readAsDataURL(f);
    });
    setupDrop(editImgDropZone, editImgInput, editImgFileName, true);

    function setupDrop(zone, input, nameEl, preview) {
        zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
        zone.addEventListener('drop', e => {
            e.preventDefault();
            zone.classList.remove('drag-over');
            const dt = e.dataTransfer;
            if (dt.files.length) {
                const transfer = new DataTransfer();
                transfer.items.add(dt.files[0]);
                input.files = transfer.files;
                input.dispatchEvent(new Event('change'));
            }
        });
    }

    window.openEditTemplateModal = function(key, label) {
        document.getElementById('editTemplateKeyInput').value = key;
        document.getElementById('editModalKeyBadge').textContent = key;

        // Reset input fields
        editPhpInput.value = '';
        editImgInput.value = '';
        editPhpFileName.textContent = '';
        editImgFileName.textContent = '';
        editPhpDropZone.classList.remove('drag-over');
        editImgDropZone.classList.remove('drag-over');

        // Load existing thumbnail preview
        editThumbPreview.src = '../assets/landing/' + key + '.png?v=' + Date.now();
        editThumbWrap.style.display = 'block';

        const modal = new bootstrap.Modal(document.getElementById('editTemplateModal'));
        modal.show();
    };
}());
</script>

</body>
</html>
