<?php
require_once 'includes/load_user.php';

// Automatic Database Migration/Check
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_landing_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        site_name VARCHAR(255) DEFAULT NULL,
        cta_text VARCHAR(255) DEFAULT 'ANZA SASA',
        primary_color VARCHAR(50) DEFAULT '#e50914',
        secondary_color VARCHAR(50) DEFAULT '#fbbf24',
        bg_color VARCHAR(50) DEFAULT '#0a0a0a',
        hero_title VARCHAR(255) DEFAULT NULL,
        hero_description TEXT,
        hero_image VARCHAR(255) DEFAULT 'assets/defaults/landing-bg.jpg',
        favicon VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
} catch (PDOException $e) {
    // Handle error or log it if needed
}

// Add all columns if not already present
try { $pdo->exec("ALTER TABLE user_landing_settings ADD COLUMN site_name VARCHAR(255) DEFAULT NULL;"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE user_landing_settings ADD COLUMN cta_text VARCHAR(255) DEFAULT 'ANZA SASA';"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE user_landing_settings ADD COLUMN primary_color VARCHAR(50) DEFAULT '#e50914';"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE user_landing_settings ADD COLUMN secondary_color VARCHAR(50) DEFAULT '#fbbf24';"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE user_landing_settings ADD COLUMN bg_color VARCHAR(50) DEFAULT '#0a0a0a';"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE user_landing_settings ADD COLUMN hero_title VARCHAR(255) DEFAULT NULL;"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE user_landing_settings ADD COLUMN hero_description TEXT;"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE user_landing_settings ADD COLUMN hero_image VARCHAR(255) DEFAULT 'assets/defaults/landing-bg.jpg';"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE user_landing_settings ADD COLUMN favicon VARCHAR(255) DEFAULT NULL;"); } catch (PDOException $e) {}

// Clean up old default strings from database so it falls back dynamically to admin platform name
try {
    $stmt = $pdo->prepare("UPDATE user_landing_settings SET site_name = NULL WHERE site_name = ?");
    $stmt->execute([$platform_name]);
    // Also clean up legacy hardcoded values
    $pdo->exec("UPDATE user_landing_settings SET site_name = NULL WHERE site_name = 'Flowtune Cinema'");
    $pdo->exec("UPDATE user_landing_settings SET hero_title = NULL WHERE hero_title = 'Karibu Flowtune Academy'");
} catch (PDOException $e) {
    // Ignore error
}

$msg = '';
$msg_type = '';

// Fetch current user landing settings
$stmt = $pdo->prepare("SELECT * FROM user_landing_settings WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle local POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token.");
    }
    
    $action = trim($_POST['action'] ?? 'update');
    
    if ($action === 'reset_defaults') {
        try {
            $stmt = $pdo->prepare("DELETE FROM user_landing_settings WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            // Redirect back with success message using PRG pattern
            header("Location: builder?msg=reset_success");
            exit;
        } catch (PDOException $e) {
            $msg = "Error resetting settings: " . $e->getMessage();
            $msg_type = "danger";
        }
    } else {
        $site_name = trim($_POST['site_name'] ?? '');
        $cta_text = trim($_POST['cta_text'] ?? '');
        $primary_color = trim($_POST['primary_color'] ?? '');
        $secondary_color = trim($_POST['secondary_color'] ?? '');
        $bg_color = trim($_POST['bg_color'] ?? '');
        $hero_title = trim($_POST['hero_title'] ?? '');
        $hero_description = trim($_POST['hero_description'] ?? '');
        $hero_image = trim($_POST['hero_image'] ?? '');
        $favicon = $settings['favicon'] ?? null;
        
        // Process Background File Upload if provided
        if (isset($_FILES['hero_image_file']) && $_FILES['hero_image_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['hero_image_file'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (in_array($file['type'], $allowed_types)) {
                $upload_dir = 'uploads/landing_bg/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                if (empty($ext)) {
                    $ext = 'jpg';
                }
                $filename = 'bg_' . $user_id . '_' . time() . '.' . $ext;
                $destination = $upload_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $hero_image = $destination;
                } else {
                    $msg = "Warning: Failed to save uploaded background image file. Used the URL instead.";
                    $msg_type = "warning";
                }
            } else {
                $msg = "Warning: Invalid background image file type. Used the URL instead.";
                $msg_type = "warning";
            }
        }

        // Process Favicon File Upload if provided
        if (isset($_FILES['favicon_file']) && $_FILES['favicon_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['favicon_file'];
            $allowed_types = ['image/png', 'image/x-icon', 'image/jpeg', 'image/webp'];
            if (in_array($file['type'], $allowed_types)) {
                $upload_dir = 'uploads/favicons/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                if (empty($ext)) {
                    $ext = 'png';
                }
                $filename = 'favicon_' . $user_id . '_' . time() . '.' . $ext;
                $destination = $upload_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $favicon = $destination;
                } else {
                    if (empty($msg)) {
                        $msg = "Warning: Failed to save uploaded favicon file.";
                        $msg_type = "warning";
                    }
                }
            } else {
                if (empty($msg)) {
                    $msg = "Warning: Invalid favicon file type. Supported: PNG, ICO, JPG, WEBP.";
                    $msg_type = "warning";
                }
            }
        }
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO user_landing_settings (user_id, site_name, cta_text, primary_color, secondary_color, bg_color, hero_title, hero_description, hero_image, favicon)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    site_name = VALUES(site_name),
                    cta_text = VALUES(cta_text),
                    primary_color = VALUES(primary_color),
                    secondary_color = VALUES(secondary_color),
                    bg_color = VALUES(bg_color),
                    hero_title = VALUES(hero_title),
                    hero_description = VALUES(hero_description),
                    hero_image = VALUES(hero_image),
                    favicon = VALUES(favicon)
            ");
            $stmt->execute([
                $user_id,
                $site_name,
                $cta_text,
                $primary_color,
                $secondary_color,
                $bg_color,
                $hero_title,
                $hero_description,
                $hero_image,
                $favicon
            ]);
            
            // Redirect back with success message using PRG pattern
            header("Location: builder.php?msg=" . ($msg_type === 'warning' ? 'warning' : 'success'));
            exit;
        } catch (PDOException $e) {
            $msg = "Error updating settings: " . $e->getMessage();
            $msg_type = "danger";
        }
    }
}

// Check for redirect message
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'success') {
        $msg = "Landing page customizer settings successfully updated!";
        $msg_type = "success";
    } elseif ($_GET['msg'] === 'warning') {
        $msg = "Settings updated, but there was an issue uploading the image file. We used the default or text URL background.";
        $msg_type = "warning";
    } elseif ($_GET['msg'] === 'reset_success') {
        $msg = "All landing page customizer settings have been reset back to default!";
        $msg_type = "success";
    }
}

// Re-fetch or apply safe fallbacks with admin settings
$settings = array_merge([
    'site_name' => $platform_name,
    'cta_text' => 'ANZA SASA',
    'primary_color' => '#e50914',
    'secondary_color' => '#fbbf24',
    'bg_color' => '#0a0a0a',
    'hero_title' => 'Karibu ' . $platform_name . ' Streaming',
    'hero_description' => 'Jifunze kidijitali na uangalie video bora zaidi hapa.',
    'hero_image' => 'assets/defaults/landing-bg.jpg',
    'favicon' => null
], $settings ? $settings : []);

// If site_name is empty/null, explicitly fallback to platform_name
if (empty($settings['site_name'])) {
    $settings['settings_name'] = $platform_name; // Keep compatibility if needed, but primary is site_name
    $settings['site_name'] = $platform_name;
}
if (empty($settings['hero_title'])) {
    $settings['hero_title'] = 'Karibu ' . $platform_name . ' Streaming';
}
if (empty($settings['cta_text'])) {
    $settings['cta_text'] = 'ANZA SASA';
}
if (empty($settings['primary_color'])) {
    $settings['primary_color'] = '#e50914';
}
if (empty($settings['secondary_color'])) {
    $settings['secondary_color'] = '#fbbf24';
}
if (empty($settings['bg_color'])) {
    $settings['bg_color'] = '#0a0a0a';
}
if (empty($settings['hero_description'])) {
    $settings['hero_description'] = 'Jifunze kidijitali na uangalie video bora zaidi hapa.';
}
if (empty($settings['hero_image'])) {
    $settings['hero_image'] = 'assets/defaults/landing-bg.jpg';
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
  <title><?= htmlspecialchars($platform_name) ?> - Landing Customizer</title>
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
  <link class="main-css" rel="stylesheet" href="assets/libs/flaticon/css/all/all.css">
  <link rel="stylesheet" href="assets/libs/lucide/lucide.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="assets/libs/simplebar/simplebar.css">
  <link rel="stylesheet" href="assets/libs/node-waves/waves.css">
  <link rel="stylesheet" href="assets/libs/bootstrap-select/css/bootstrap-select.min.css">
  <!-- end::GXON Required Stylesheet -->

  <!-- begin::GXON CSS Stylesheet -->
  <link rel="stylesheet" href="assets/css/styles.css">
  <!-- end::GXON CSS Stylesheet -->
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
  <style>
      .preview-box {
          border: 2px dashed #ccc;
          padding: 20px;
          border-radius: 12px;
          background: #f9f9f9;
          text-align: center;
      }
      .color-preview {
          width: 100%;
          height: 40px;
          border-radius: 8px;
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
                      data-bs-auto-close="outside" aria-expanded="true">
                      <div class="text-end me-2 d-none d-lg-inline-block">
                          <div class="fw-bold text-dark"><?= htmlspecialchars($user['name']) ?></div>
                          <small class="text-body d-block lh-sm">
                              <i class="fa-solid fa-angle-down text-3xs me-1"></i> <?= htmlspecialchars($user['role']) ?>
                          </small>
                      </div>
                      <div class="avatar avatar-sm rounded-circle avatar-status-success">
                          <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="">
                      </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end w-225px mt-1">
                      <li class="d-flex align-items-center p-2">
                          <div class="avatar avatar-sm rounded-circle">
                              <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="">
                          </div>
                          <div class="ms-2">
                              <div class="fw-bold text-dark"><?= htmlspecialchars($user['name']) ?></div>
                              <small class="text-body d-block lh-sm"><?= htmlspecialchars($user['email']) ?></small>
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
                          <span class="text-uppercase text-2xs fw-semibold text-muted d-block mb-2">Recently Searched:</span>
                      </div>
                      <div id="searchContainer"></div>
                  </div>
              </div>
          </div>
      </div>

      <!-- begin::GXON Sidebar Menu -->
      <aside class="app-menubar" id="appMenubar">
          <div class="app-navbar-brand">
              <a class="navbar-brand-logo" href="#">
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
              <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
                  <div class="clearfix">
                      <h1 class="app-page-title">Landing Customizer</h1>
                  </div>
              </div>

              <div class="row">
                  <div class="col-12">
                      <?php if (!empty($msg)): ?>
                          <div class="alert alert-<?= htmlspecialchars($msg_type) ?> alert-dismissible fade show border-0 shadow-sm rounded-3 p-3 mb-4" role="alert" style="border-radius: 12px !important;">
                              <div class="d-flex align-items-center">
                                  <i class="fa-solid <?= $msg_type === 'success' ? 'fa-circle-check text-success' : ($msg_type === 'warning' ? 'fa-circle-exclamation text-warning' : 'fa-circle-exclamation text-danger') ?> me-3 fs-4"></i>
                                  <div class="fw-semibold">
                                      <?= htmlspecialchars($msg) ?>
                                  </div>
                              </div>
                              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                          </div>
                      <?php endif; ?>

                      <?php if ($user['active_landing'] !== 'landing8'): ?>
                          <div class="alert alert-warning border-0 shadow-sm rounded-3 p-3 mb-4" role="alert" style="border-radius: 12px !important; background-color: #fff3cd; border-left: 4px solid #ffc107;">
                              <div class="d-flex align-items-center">
                                  <i class="fa-solid fa-triangle-exclamation text-warning me-3 fs-4"></i>
                                  <div>
                                      <span class="fw-bold text-dark">Landing 8 is not your active template!</span><br>
                                      <span class="small text-muted">Your active storefront is currently set to <strong><?= htmlspecialchars(ucfirst($user['active_landing'])) ?></strong>. The customizer settings on this page will only apply if you activate <strong>Landing 8</strong> in your <a href="settings" class="fw-bold text-decoration-underline text-warning-800">Store Settings</a>.</span>
                                  </div>
                              </div>
                          </div>
                      <?php endif; ?>

                      <form action="builder" method="POST" enctype="multipart/form-data">
                          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" autocomplete="off">                        
                          <input type="hidden" name="action" value="update" autocomplete="off">
                          <div class="card mb-4">
                              <div class="card-header bg-primary text-white">
                                  <h5 class="card-title mb-0">1. Branding & Identity</h5>
                              </div>
                              <div class="card-body">
                                  <div class="row gy-3">
                                      <div class="col-md-6">
                                          <label class="form-label fw-bold">Site Name / Logo Text</label>
                                          <input type="text" class="form-control" name="site_name" 
                                                 value="<?= htmlspecialchars($settings['site_name']) ?>" placeholder="e.g. My Cinema" required>
                                      </div>
                                      <div class="col-md-6">
                                          <label class="form-label fw-bold">Call to Action (CTA) Button Text</label>
                                          <input type="text" class="form-control" name="cta_text" 
                                                 value="<?= htmlspecialchars($settings['cta_text']) ?>" required>
                                      </div>
                                  </div>
                              </div>
                          </div>

                          <div class="card mb-4">
                              <div class="card-header bg-dark text-white">
                                  <h5 class="card-title mb-0">2. Visual Colors</h5>
                              </div>
                              <div class="card-body">
                                  <div class="row gy-3">
                                      <div class="col-md-4">
                                          <label class="form-label fw-bold">Primary Color (Buttons/Icons)</label>
                                          <input type="color" class="form-control form-control-color w-100" name="primary_color" 
                                                 value="<?= htmlspecialchars($settings['primary_color']) ?>">
                                      </div>
                                      <div class="col-md-4">
                                          <label class="form-label fw-bold">Secondary Color (Badges)</label>
                                          <input type="color" class="form-control form-control-color w-100" name="secondary_color" 
                                                 value="<?= htmlspecialchars($settings['secondary_color']) ?>">
                                      </div>
                                      <div class="col-md-4">
                                          <label class="form-label fw-bold">Background Color</label>
                                          <input type="color" class="form-control form-control-color w-100" name="bg_color" 
                                                 value="<?= htmlspecialchars($settings['bg_color']) ?>">
                                      </div>
                                  </div>
                              </div>
                          </div>

                          <div class="card mb-4">
                              <div class="card-header bg-info text-white">
                                  <h5 class="card-title mb-0">3. Hero Content (Main Banner)</h5>
                              </div>
                              <div class="card-body">
                                  <div class="row gy-3">
                                      <div class="col-12">
                                          <label class="form-label fw-bold">Main Hero Title</label>
                                          <input type="text" class="form-control" name="hero_title" 
                                                 value="<?= htmlspecialchars($settings['hero_title']) ?>" required>
                                      </div>
                                      <div class="col-12">
                                          <label class="form-label fw-bold">Hero Description</label>
                                          <textarea class="form-control" name="hero_description" rows="3" required><?= htmlspecialchars($settings['hero_description']) ?></textarea>
                                      </div>
                                      
                                      <div class="col-md-12 mb-2">
                                          <label class="form-label fw-bold">Upload Custom Hero Background Image</label>
                                          <input type="file" class="form-control mb-2" name="hero_image_file" accept="image/*">
                                          <div class="text-muted" style="font-size: 0.85rem;">
                                              <i class="fa-solid fa-circle-info me-1"></i> Supported formats: JPG, PNG, WEBP, GIF.
                                          </div>
                                      </div>
                                      
                                      <div class="col-md-12">
                                          <label class="form-label fw-bold">Or Use Hero Background Image URL</label>
                                          <input type="text" class="form-control" name="hero_image" 
                                                 value="<?= htmlspecialchars($settings['hero_image']) ?>" placeholder="https://image-link.com/bg.jpg" required>
                                          <small class="text-muted">Tip: If you upload a file above, this text field will automatically update with the saved upload path.</small>
                                      </div>
                                  </div>
                              </div>
                          </div>

                          <div class="mb-3">
                              <button type="submit" class="btn btn-success btn-lg w-100 shadow-lg" style="border-radius: 12px !important;">
                                  <i class="fa-solid fa-floppy-disk me-2"></i> Update Landing 8 Details
                              </button>
                          </div>

                      </form>

                      <form action="builder" method="POST" onsubmit="return confirm('Are you sure you want to reset all customizer settings back to platform defaults? This will erase your customized logo text, colors, hero banner, and uploaded background image.');">
                          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" autocomplete="off">
                          <input type="hidden" name="action" value="reset_defaults">
                          <div class="mb-5">
                              <button type="submit" class="btn btn-outline-danger btn-lg w-100 shadow-sm" style="border-radius: 12px !important;">
                                  <i class="fa-solid fa-rotate-left me-2"></i> Reset all to Default Settings
                              </button>
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
              <p class="mb-0">© <span class="currentYear"><?= date('Y') ?></span> <?= htmlspecialchars($platform_name) ?>. Proudly powered by <a href="javascript:void(0);">UnknowDev</a>.</p>
            </div>
            <div class="col-lg-6 col-md-5">
              <ul class="d-flex list-inline mb-0 gap-3 flex-wrap justify-content-center justify-content-md-end">
                <li>
                  <a class="text-body" href="">Tutorials</a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </footer>
  </div>

  <!-- begin::GXON Page Scripts -->
  <script src="assets/libs/global/global.min.js"></script>
  <script src="assets/js/appSettings.js"></script>
  <script src="assets/js/main.js"></script>
  <!-- end::GXON Page Scripts -->

</body>
</html>
