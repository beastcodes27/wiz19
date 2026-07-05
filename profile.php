<?php
require_once 'includes/load_user.php';

// Handle profile update
$profile_message = '';
$profile_message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token.");
    }

    // Profile Update
    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($full_name) || empty($email)) {
            $profile_message = "Name and email cannot be empty.";
            $profile_message_type = "danger";
        } else {
            // Check email uniqueness (exclude current user)
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $checkStmt->execute([$email, $user_id]);
            if ($checkStmt->fetch()) {
                $profile_message = "That email is already taken by another account.";
                $profile_message_type = "danger";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $user_id]);
                $profile_message = "Profile updated successfully!";
                $profile_message_type = "success";
                // Update session email if changed
                $_SESSION['user_email'] = $email;
            }
        }
    }

    // Password Update
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $pwStmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $pwStmt->execute([$user_id]);
        $stored_hash = $pwStmt->fetchColumn();

        if (!password_verify($current_password, $stored_hash)) {
            $profile_message = "Current password is incorrect.";
            $profile_message_type = "danger";
        } elseif (strlen($new_password) < 6) {
            $profile_message = "New password must be at least 6 characters.";
            $profile_message_type = "danger";
        } elseif ($new_password !== $confirm_password) {
            $profile_message = "New passwords do not match.";
            $profile_message_type = "danger";
        } else {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$new_hash, $user_id]);
            $profile_message = "Password updated successfully!";
            $profile_message_type = "success";
        }
    }

    // Avatar Update
    if (isset($_POST['update_avatar']) && isset($_FILES['avatar_file'])) {
        $file = $_FILES['avatar_file'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
            if (in_array($file['type'], $allowed_types)) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
                $upload_dir = 'assets/images/avatars/';
                $destination = $upload_dir . $filename;
                $db_path = '/' . $destination;
                
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                    $stmt->execute([$db_path, $user_id]);
                    $profile_message = "Avatar updated successfully!";
                    $profile_message_type = "success";
                } else {
                    $profile_message = "Failed to save uploaded file.";
                    $profile_message_type = "danger";
                }
            } else {
                $profile_message = "Invalid file type. Only JPG, PNG, and WebP are allowed.";
                $profile_message_type = "danger";
            }
        } else {
            $profile_message = "Error uploading file. Please try again.";
            $profile_message_type = "danger";
        }
    }
}

// Re-fetch user data after possible updates
$__refetch = $pdo->prepare("SELECT full_name as name, email, role, phone, avatar FROM users WHERE id = ? LIMIT 1");
$__refetch->execute([$user_id]);
$fetched_data = $__refetch->fetch(PDO::FETCH_ASSOC);
if ($fetched_data) {
    $user = array_merge($user, $fetched_data);
}

// Re-apply avatar path logic in case it was updated
if (empty($user['avatar']) || $user['avatar'] === 'assets/images/avatar/avatar1.webp' || $user['avatar'] === '/assets/images/avatar/avatar1.webp') {
    $user['avatar'] = BASE_URL . '/assets/images/avatar/avatar1.webp';
} else if (strpos($user['avatar'], '/assets') === 0 && strpos($user['avatar'], BASE_URL) !== 0) {
    $user['avatar'] = BASE_URL . $user['avatar'];
} else if (strpos($user['avatar'], 'assets/') === 0) {
    $user['avatar'] = BASE_URL . '/' . $user['avatar'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="theme-color" content="#316AFF">
  <meta name="robots" content="index, follow">
  <meta name="author" content="<?= htmlspecialchars($platform_name) ?>">
  <meta name="description" content="Manage your profile and account security.">
  <meta property="og:site_name" content="<?= htmlspecialchars($platform_name) ?>">
  <meta property="og:type" content="website">
  <title><?= htmlspecialchars($platform_name) ?> - My Profile</title>
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
</head>

<body>

  <div class="page-layout">

      <!-- begin::GXON Page Header -->
      <header class="app-header">
          <div class="app-header-inner">
              <button class="app-toggler" type="button" aria-label="app toggler">
                  <span></span><span></span><span></span>
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
                          <button class="btn btn-icon btn-action-gray rounded-circle waves-effect waves-light position-relative"
                              id="ld-theme" type="button" data-bs-auto-close="outside" aria-expanded="false"
                              data-bs-toggle="dropdown">
                              <i class="fa-solid fa-sun scale-1x theme-icon-active"></i>
                          </button>
                          <ul class="dropdown-menu dropdown-menu-end">
                              <li><button type="button" class="dropdown-item d-flex gap-2 align-items-center" data-bs-theme-value="light" aria-pressed="false"><i class="fa-solid fa-sun scale-1x" data-theme="light"></i> Light</button></li>
                              <li><button type="button" class="dropdown-item d-flex gap-2 align-items-center" data-bs-theme-value="dark" aria-pressed="false"><i class="fa-solid fa-moon scale-1x" data-theme="dark"></i> Dark</button></li>
                              <li><button type="button" class="dropdown-item d-flex gap-2 align-items-center" data-bs-theme-value="auto" aria-pressed="true"><i class="fa-solid fa-circle-half-stroke scale-1x" data-theme="auto"></i> Auto</button></li>
                          </ul>
                      </div>
                  </div>
                  <div class="vr my-3"></div>
              </div>
              <div class="vr my-3"></div>
              <div class="dropdown text-end ms-sm-3 ms-2 ms-lg-4">
                  <a href="#" class="d-flex align-items-center py-2" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                      <div class="text-end me-2 d-none d-lg-inline-block">
                          <div class="fw-bold text-dark"><?php echo htmlspecialchars($user['name']); ?></div>
                          <small class="text-body d-block lh-sm"><i class="fa-solid fa-angle-down text-3xs me-1"></i> <?php echo htmlspecialchars($user['role']); ?></small>
                      </div>
                      <div class="avatar avatar-sm rounded-circle avatar-status-success">
                          <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="">
                      </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end w-225px mt-1">
                      <li class="d-flex align-items-center p-2">
                          <div class="avatar avatar-sm rounded-circle"><img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt=""></div>
                          <div class="ms-2">
                              <div class="fw-bold text-dark"><?php echo htmlspecialchars($user['name']); ?></div>
                              <small class="text-body d-block lh-sm"><?php echo htmlspecialchars($user['email']); ?></small>
                          </div>
                      </li>
                      <li><div class="dropdown-divider my-1"></div></li>
                      <li><a class="dropdown-item d-flex align-items-center gap-2" href="profile"><i class="fa-solid fa-user scale-1x"></i> View Profile</a></li>
                      <li><a class="dropdown-item d-flex align-items-center gap-2" href="#"><i class="fa-solid fa-circle-dollar-to-slot scale-1x"></i> Plan</a></li>
                      <li><div class="dropdown-divider my-1"></div></li>
                      <li>
                          <a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="logout"
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

      <!-- begin::GXON Sidebar Menu -->
      <aside class="app-menubar" id="appMenubar">
          <div class="app-navbar-brand">
              <a class="navbar-brand-logo" href="dashboard">
                  <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Dashboard" style="max-height: 40px; width: auto;">
              </a>
          </div>
          <nav class="app-navbar" data-simplebar>
              <ul class="menubar">
                  <li class="menu-item"><a class="menu-link" href="dashboard"><i class="fa-solid fa-chart-pie"></i><span class="menu-label">Dashboard</span></a></li>
                  <li class="menu-item menu-arrow">
                      <a class="menu-link" href="javascript:void(0);" role="button"><i class="fa-solid fa-video"></i><span class="menu-label">Video</span><i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.75rem;"></i></a>
                      <ul class="menu-inner">
                          <li class="menu-item"><a class="menu-link" href="video-upload"><span class="menu-label">Upload</span></a></li>
                          <li class="menu-item"><a class="menu-link" href="videos"><span class="menu-label">Manage Videos</span></a></li>
                      </ul>
                  </li>
                  <li class="menu-item"><a class="menu-link" href="transactions"><i class="fa-solid fa-receipt"></i><span class="menu-label">Transactions</span></a></li>
                  <li class="menu-item"><a class="menu-link" href="domain"><i class="fa-solid fa-globe"></i><span class="menu-label">Domains</span></a></li>
                  <li class="menu-item menu-arrow">
                      <a class="menu-link" href="javascript:void(0);" role="button"><i class="fa-solid fa-box-open"></i><span class="menu-label">Packages</span><i class="fa-solid fa-chevron-down ms-auto" style="font-size: 0.75rem;"></i></a>
                      <ul class="menu-inner">
                          <li class="menu-item"><a class="menu-link" href="package"><span class="menu-label">Create Package</span></a></li>
                          <li class="menu-item"><a class="menu-link" href="package-subscribers"><span class="menu-label">Active Subscribers</span></a></li>
                      </ul>
                  </li>
                  <li class="menu-item"><a class="menu-link" href="settings"><i class="fa-solid fa-gear"></i><span class="menu-label">Settings</span></a></li>
                  <li class="menu-item"><a class="menu-link" href="#"><i class="fa-solid fa-crown"></i><span class="menu-label">Subscription</span></a></li>
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
              <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
                  <div class="clearfix">
                      <h1 class="app-page-title">My Profile</h1>
                  </div>
              </div>

              <?php if ($profile_message): ?>
              <div class="alert alert-<?php echo $profile_message_type; ?> alert-dismissible fade show" role="alert">
                  <?php echo htmlspecialchars($profile_message); ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
              <?php endif; ?>

              <div class="row">
                  <div class="col-lg-4 mb-4">
                      <div class="card border-0 shadow-sm">
                          <div class="card-body text-center pb-5 pt-4">
                              <div class="avatar avatar-xl rounded-circle mb-3 mx-auto shadow position-relative" style="width: 140px; height: 140px; border: 4px solid #fff;">
                                  <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar" class="w-100 h-100 rounded-circle object-fit-cover">
                                  
                                  <!-- Avatar Upload Button -->
                                  <form action="profile" method="POST" enctype="multipart/form-data" class="position-absolute bottom-0 end-0" style="margin-bottom: -5px; margin-right: -5px;">
                                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                      <input type="hidden" name="update_avatar" value="1">
                                      <input type="file" name="avatar_file" id="avatarInput" class="d-none" accept="image/jpeg, image/png, image/webp" onchange="this.form.submit()">
                                      <label for="avatarInput" class="btn btn-primary btn-sm rounded-circle shadow-sm" style="width: 36px; height: 36px; padding: 0; line-height: 36px; cursor: pointer;" title="Change Avatar">
                                          <i class="fa-solid fa-camera"></i>
                                      </label>
                                  </form>
                              </div>
                              <h4 class="mb-1 fw-bold"><?php echo htmlspecialchars($user['name']); ?></h4>
                              <p class="text-muted mb-2"><?php echo htmlspecialchars($user['email']); ?></p>
                              <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 mb-3"><?php echo ucfirst(htmlspecialchars($user['role'])); ?></span>
                          </div>
                      </div>
                  </div>

                  <div class="col-lg-8">
                      <div class="card mb-4 border-0 shadow-sm">
                          <div class="card-header bg-transparent border-bottom py-3">
                              <h5 class="mb-0 fw-bold">Personal Information</h5>
                          </div>
                          <div class="card-body pt-4">
                              <form action="profile" method="POST">
                                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" autocomplete="off">
                                  <input type="hidden" name="update_profile" value="1">
                                  <div class="row mb-4">
                                      <div class="col-md-6">
                                          <label class="form-label fw-semibold text-muted">Full Name</label>
                                          <input type="text" name="full_name" class="form-control form-control-lg" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                      </div>
                                      <div class="col-md-6 mt-3 mt-md-0">
                                          <label class="form-label fw-semibold text-muted">Email Address</label>
                                          <input type="email" name="email" class="form-control form-control-lg" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                      </div>
                                  </div>
                                  <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5"><i class="fa-solid fa-save me-2"></i> Save Changes</button>
                              </form>
                          </div>
                      </div>

                      <div class="card border-0 shadow-sm">
                          <div class="card-header bg-transparent border-bottom py-3">
                              <h5 class="mb-0 fw-bold text-danger"><i class="fa-solid fa-shield-halved me-2"></i> Security</h5>
                          </div>
                          <div class="card-body pt-4">
                              <form action="profile" method="POST">
                                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" autocomplete="off">
                                  <input type="hidden" name="update_password" value="1">
                                  <div class="mb-4">
                                      <label class="form-label fw-semibold text-muted">Current Password</label>
                                      <input type="password" name="current_password" class="form-control form-control-lg" placeholder="••••••••" required>
                                  </div>
                                  <div class="row mb-4">
                                      <div class="col-md-6">
                                          <label class="form-label fw-semibold text-muted">New Password</label>
                                          <input type="password" name="new_password" class="form-control form-control-lg" placeholder="••••••••" required>
                                      </div>
                                      <div class="col-md-6 mt-3 mt-md-0">
                                          <label class="form-label fw-semibold text-muted">Confirm New Password</label>
                                          <input type="password" name="confirm_password" class="form-control form-control-lg" placeholder="••••••••" required>
                                      </div>
                                  </div>
                                  <button type="submit" class="btn btn-danger btn-lg rounded-pill px-4"><i class="fa-solid fa-key me-2"></i> Update Password</button>
                              </form>
                          </div>
                      </div>
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
                          <li><a class="text-body" href="#">Tutorials</a></li>
                      </ul>
                  </div>
              </div>
          </div>
      </footer>

  <!-- begin::GXON Page Scripts -->
  <script src="assets/libs/global/global.min.js"></script>
  <script src="assets/js/appSettings.js"></script>
  <script src="assets/js/main.js"></script>
  <!-- end::GXON Page Scripts -->

</body>
</html>
