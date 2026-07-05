<?php
require_once '../includes/load_user.php';
require_admin();

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$domain = $protocol . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$host = $_SERVER['HTTP_HOST'] ?? 'flowtune.com';
$host = explode(':', $host)[0];
$default_support_email = 'support@' . $host;


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// PRG: Read flash message from session (set after redirect)
$message = $_SESSION['flash_message'] ?? '';
$messageType = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// Function to crop image to circle
function crop_to_circle($source_path, $dest_path, $size)
{
    $info = getimagesize($source_path);
    if (!$info)
        return false;

    $mime = $info['mime'];
    switch ($mime) {
        case 'image/jpeg':
            $img = imagecreatefromjpeg($source_path);
            break;
        case 'image/png':
            $img = imagecreatefrompng($source_path);
            break;
        case 'image/webp':
            $img = imagecreatefromwebp($source_path);
            break;
        default:
            return false;
    }

    $width = imagesx($img);
    $height = imagesy($img);
    $min = min($width, $height);

    // Crop to square first
    $square = imagecreatetruecolor($min, $min);
    imagecopyresampled($square, $img, 0, 0, ($width - $min) / 2, ($height - $min) / 2, $min, $min, $min, $min);

    // Create destination image
    $dst = imagecreatetruecolor($size, $size);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
    imagefill($dst, 0, 0, $transparent);

    // Resize square to destination size
    $resized = imagecreatetruecolor($size, $size);
    imagecopyresampled($resized, $square, 0, 0, 0, 0, $size, $size, $min, $min);

    // Apply circular mask
    for ($x = 0; $x < $size; $x++) {
        for ($y = 0; $y < $size; $y++) {
            $dx = $x - ($size / 2);
            $dy = $y - ($size / 2);
            if (($dx * $dx) + ($dy * $dy) <= ($size / 2) * ($size / 2)) {
                $color = imagecolorat($resized, $x, $y);
                imagesetpixel($dst, $x, $y, $color);
            }
        }
    }

    imagepng($dst, $dest_path);
    imagedestroy($img);
    imagedestroy($square);
    imagedestroy($resized);
    imagedestroy($dst);
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = "Invalid CSRF token.";
        $_SESSION['flash_type'] = "danger";
        header('Location: settings');
        exit;
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_general') {
            $platform_name = trim($_POST['platform_name'] ?? '');
            $support_email = trim($_POST['support_email'] ?? '');
            $telegram_username = trim($_POST['telegram_username'] ?? '');
            $default_currency = trim($_POST['default_currency'] ?? '');
            $maintenance_mode = isset($_POST['maintenance_mode']) ? '1' : '0';
            $platform_fee_percentage = trim($_POST['platform_fee_percentage'] ?? '0');

            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute(['platform_name', $platform_name]);
            $stmt->execute(['support_email', $support_email]);
            $stmt->execute(['telegram_username', $telegram_username]);
            $stmt->execute(['default_currency', $default_currency]);
            $stmt->execute(['maintenance_mode', $maintenance_mode]);
            $stmt->execute(['platform_fee_percentage', $platform_fee_percentage]);

            $_SESSION['flash_message'] = "General settings updated successfully.";
            $_SESSION['flash_type'] = "success";
            header('Location: settings');
            exit;
        } elseif ($action === 'upload_logo') {
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['logo'];
                $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                if (in_array($file['type'], $allowed) && $file['size'] <= 2 * 1024 * 1024) {
                    $tmp = $file['tmp_name'];

                    // Create Logo (200x200)
                    $logoPath = '../assets/images/logo.png';
                    crop_to_circle($tmp, $logoPath, 200);

                    // Create Favicon (32x32)
                    $faviconPath = '../assets/images/favicon.png';
                    crop_to_circle($tmp, $faviconPath, 32);

                    $_SESSION['flash_message'] = "Logo and Favicon updated successfully.";
                    $_SESSION['flash_type'] = "success";
                    header('Location: settings');
                    exit;
                } else {
                    $_SESSION['flash_message'] = "Invalid file type or size too large (Max 2MB).";
                    $_SESSION['flash_type'] = "danger";
                    header('Location: settings');
                    exit;
                }
            }
        }
    }
}

// Fetch current settings
$settings = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
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
    <meta name="keywords"
        content="<?= htmlspecialchars($platform_name) ?>, video hosting, video monetization, custom landing page, video sales, online video platform, video marketing, video distribution, video analytics, video management">
    <meta name="description"
        content="<?= htmlspecialchars($platform_name) ?> turns your videos into a sellable product—host, brand, and monetize from a single custom landing page without building a full site.">
    <!-- end::GXON Meta Basic -->

    <!-- begin::GXON Meta Social -->
    <meta property="og:url" content="<?= $domain . BASE_URL ?>/">
    <meta property="og:site_name" content="<?= htmlspecialchars($platform_name) ?>">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="en_US">
    <meta property="og:title" content="<?= htmlspecialchars($platform_name) ?>">
    <meta property="og:description"
        content="<?= htmlspecialchars($platform_name) ?> turns your videos into a sellable product—host, brand, and monetize from a single custom landing page without building a full site.">
    <meta property="og:image" content="https://gxon.layoutdrop.com/demo/preview.png">
    <!-- end::GXON Meta Social -->

    <!-- begin::GXON Meta Twitter -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:url" content="<?= $domain . BASE_URL ?>/">
    <meta name="twitter:creator" content="@layoutdrop">
    <meta name="twitter:title" content="<?= htmlspecialchars($platform_name) ?>">
    <meta name="twitter:description"
        content="<?= htmlspecialchars($platform_name) ?> turns your videos into a sellable product—host, brand, and monetize from a single custom landing page without building a full site.">
    <!-- end::GXON Meta Twitter -->

    <!-- begin::GXON Website Page Title -->
    <title><?= htmlspecialchars($platform_name) ?> Admin - Settings</title>
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap"
        rel="stylesheet">
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
        function gtag() { dataLayer.push(arguments); }
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
                                <i class="fa-solid fa-angle-down text-3xs me-1"></i>
                                <?php echo htmlspecialchars($user['role']); ?>
                            </small>
                        </div>
                        <div
                            class="avatar avatar-sm rounded-circle avatar-status-success bg-primary text-white d-flex align-items-center justify-content-center fw-bold">
                            A
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end w-225px mt-1">
                        <li class="d-flex align-items-center p-2">
                            <div
                                class="avatar avatar-sm rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold">
                                A
                            </div>
                            <div class="ms-2">
                                <div class="fw-bold text-dark">Admin</div>
                                <small
                                    class="text-body d-block lh-sm"><?= htmlspecialchars($settings["support_email"] ?? $default_support_email) ?></small>
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
                            <a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="../logout"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fa-solid fa-right-from-bracket scale-1x"></i> Log Out
                            </a>

                            <form id="logout-form" action="../logout" method="POST" class="d-none">
                                <input type="hidden" name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" autocomplete="off">
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
                    <img src="<?= BASE_URL ?>/assets/images/logo.png?v=<?= file_exists('../assets/images/logo.png') ? filemtime('../assets/images/logo.png') : time() ?>"
                        alt="Dashboard" style="max-height: 40px; width: auto;">
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
                        <a class="menu-link" href="tickets"><i class="fa-solid fa-headset"></i><span
                                class="menu-label">Support
                                Tickets</span><?php if (isset($admin_unread_tickets_count) && $admin_unread_tickets_count > 0): ?><span
                                    class="badge rounded-pill"
                                    style="font-size: 0.72rem; padding: 0.25rem 0.6rem; background: linear-gradient(135deg, #ff416c, #ff4b2b) !important; color: #ffffff !important; box-shadow: 0 2px 6px rgba(255, 65, 108, 0.45); font-weight: 700; margin-left: auto !important; display: inline-flex !important; align-items: center; justify-content: center; min-width: 1.5rem; height: 1.5rem;"><?= $admin_unread_tickets_count ?></span><?php endif; ?></a>
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
                        <h1 class="app-page-title">Platform Settings</h1>
                        <p class="text-muted mb-0">Manage global settings, branding, and integrations</p>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row g-4 d-flex align-items-stretch">
                    <div class="col-lg-7 d-flex flex-column">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <h5 class="mb-0 fw-bold"><i class="fa-solid fa-palette me-2"></i> Branding & Logo
                                    Settings</h5>
                            </div>
                            <div class="card-body pt-4">
                                <div class="row g-4 mb-4">
                                    <div class="col-sm-6 col-md-4 d-flex flex-column align-items-center text-center">
                                        <span class="text-xs fw-semibold text-muted mb-2">CURRENT LOGO</span>
                                        <div class="p-2 border rounded-circle bg-light d-flex align-items-center justify-content-center shadow-sm"
                                            style="width: 110px; height: 110px; overflow: hidden;">
                                            <img id="logoPreview"
                                                src="<?= BASE_URL ?>/assets/images/logo.png?v=<?= file_exists('../assets/images/logo.png') ? filemtime('../assets/images/logo.png') : time() ?>"
                                                alt="Current Logo" class="rounded-circle"
                                                style="width: 100px; height: 100px; object-fit: cover;">
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-md-4 d-flex flex-column align-items-center text-center">
                                        <span class="text-xs fw-semibold text-muted mb-2">FAVICON PREVIEW (32x32)</span>
                                        <div class="p-2 border rounded-circle bg-light d-flex align-items-center justify-content-center shadow-sm mb-2"
                                            style="width: 50px; height: 50px;">
                                            <canvas id="faviconPreview" width="32" height="32"
                                                style="width: 32px; height: 32px;"></canvas>
                                        </div>
                                        <small class="text-muted text-2xs">32 x 32 px</small>
                                    </div>
                                    <div class="col-sm-6 col-md-4 d-flex flex-column align-items-center text-center">
                                        <span class="text-xs fw-semibold text-muted mb-2">FAVICON TAB (16x16)</span>
                                        <div class="p-2 border rounded-circle bg-light d-flex align-items-center justify-content-center shadow-sm mb-2"
                                            style="width: 34px; height: 34px;">
                                            <canvas id="faviconTabPreview" width="16" height="16"
                                                style="width: 16px; height: 16px;"></canvas>
                                        </div>
                                        <small class="text-muted text-2xs">16 x 16 px</small>
                                    </div>
                                </div>

                                <hr class="my-4 opacity-10">

                                <form action="" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token"
                                        value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
                                        autocomplete="off">
                                    <input type="hidden" name="action" value="upload_logo">
                                    <div class="row align-items-center g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold text-muted mb-1">Select New Logo
                                                File</label>
                                            <input class="form-control" type="file" name="logo" id="logoUpload"
                                                accept="image/jpeg, image/png, image/webp" required>
                                            <small class="text-muted d-block mt-1">Recommended square size. Formats:
                                                PNG, JPG, WEBP. Max 2MB.</small>
                                        </div>
                                        <div class="col-md-6 d-flex gap-2 justify-content-md-end mt-4">
                                            <button type="submit" class="btn btn-primary rounded-pill px-4"><i
                                                    class="fa-solid fa-cloud-arrow-up me-2"></i> Save & Apply</button>
                                            <button type="button" class="btn btn-outline-danger rounded-pill px-3"
                                                id="removeLogo" title="Reset Selection"><i
                                                    class="fa-solid fa-rotate-left"></i> Reset</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mb-0">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <h5 class="mb-0 fw-bold"><i class="fa-solid fa-globe me-2"></i> General Settings</h5>
                            </div>
                            <div class="card-body pt-3">
                                <form action="" method="POST">
                                    <input type="hidden" name="csrf_token"
                                        value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
                                        autocomplete="off">
                                    <input type="hidden" name="action" value="update_general">

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-muted mb-1">Platform Name</label>
                                        <input type="text" name="platform_name" class="form-control"
                                            value="<?= htmlspecialchars($settings['platform_name'] ?? $platform_name) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-muted mb-1">Support Email</label>
                                        <input type="email" name="support_email" class="form-control"
                                            value="<?= htmlspecialchars($settings['support_email'] ?? $default_support_email) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-muted mb-1">Telegram Username / Handle</label>
                                        <input type="text" name="telegram_username" class="form-control"
                                            value="<?= htmlspecialchars($settings['telegram_username'] ?? 'Flowtune') ?>" placeholder="e.g. Flowtune">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-muted mb-1">Default Currency</label>
                                        <select name="default_currency" class="form-select">
                                            <option value="TZS" <?= ($settings['default_currency'] ?? 'TZS') === 'TZS' ? 'selected' : '' ?>>TZS - Tanzanian Shilling</option>
                                            <option value="USD" <?= ($settings['default_currency'] ?? '') === 'USD' ? 'selected' : '' ?>>USD - US Dollar</option>
                                            <option value="KES" <?= ($settings['default_currency'] ?? '') === 'KES' ? 'selected' : '' ?>>KES - Kenyan Shilling</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-muted mb-1">Maintenance Mode</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="maintenance_mode"
                                                role="switch" id="maintenanceToggle" <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="maintenanceToggle">Enable maintenance
                                                mode</label>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary rounded-pill px-4"><i
                                            class="fa-solid fa-save me-2"></i> Save Settings</button>
                                </form>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mb-0">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <h5 class="mb-0 fw-bold text-success"><i class="fa-solid fa-mobile-screen me-2"></i>
                                    Payment Gateway</h5>
                            </div>
                            <div class="card-body pt-3">
                                <form action="#" method="POST">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-muted mb-1">API Key</label>
                                        <input type="password" class="form-control" value="••••••••••••••••">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-muted mb-1">Secret Key</label>
                                        <input type="password" class="form-control" value="••••••••••••••••">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-muted mb-1">Webhook URL</label>
                                        <input type="text" class="form-control"
                                            value="<?= $domain . BASE_URL ?>/api/webhook/payment" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-muted mb-1">Test Mode</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                id="testModeToggle" checked>
                                            <label class="form-check-label" for="testModeToggle">Enable sandbox/test
                                                mode</label>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-success rounded-pill px-4"><i
                                            class="fa-solid fa-save me-2"></i> Update Gateway</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 d-flex flex-column gap-3 justify-content-start">
                        <div class="card border-0 shadow-sm mb-0">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <h5 class="mb-0 fw-bold"><i class="fa-solid fa-server me-2"></i> System Info</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between px-0"><span
                                            class="text-muted">PHP Version</span> <span class="fw-bold">8.2.12</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between px-0"><span
                                            class="text-muted">MySQL Version</span> <span class="fw-bold">8.0.35</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between px-0"><span
                                            class="text-muted">Server</span> <span class="fw-bold">Apache 2.4</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between px-0"><span
                                            class="text-muted">Disk Usage</span> <span class="fw-bold">12.4 GB / 50
                                            GB</span></li>
                                    <li class="list-group-item d-flex justify-content-between px-0"><span
                                            class="text-muted">App Version</span> <span class="fw-bold">1.0.0</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm bg-danger bg-opacity-10">
                            <div class="card-body text-center py-4">
                                <i class="fa-solid fa-triangle-exclamation text-danger mb-3"
                                    style="font-size: 2rem;"></i>
                                <h6 class="fw-bold">Danger Zone</h6>
                                <p class="text-muted small mb-3">These actions are irreversible.</p>
                                <button class="btn btn-outline-danger btn-sm w-100 mb-2"><i
                                        class="fa-solid fa-broom me-2"></i> Clear All Cache</button>
                                <button class="btn btn-danger btn-sm w-100"><i class="fa-solid fa-database me-2"></i>
                                    Reset Database</button>
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

        <!-- Logo Upload & Favicon Preview Script -->
        <script>
            // Run immediately — scripts are at bottom of body so DOM is already ready.
            // Do NOT use DOMContentLoaded: global.min.js may already have fired it.
            (function () {
                var logoUpload    = document.getElementById('logoUpload');
                var logoPreview   = document.getElementById('logoPreview');
                var faviconCanvas = document.getElementById('faviconPreview');
                var faviconTabCanvas = document.getElementById('faviconTabPreview');
                var removeLogo    = document.getElementById('removeLogo');

                // ── helpers ──────────────────────────────────────────────────
                function resizeAndCrop(img, targetSize) {
                    var canvas = document.createElement('canvas');
                    canvas.width = canvas.height = targetSize;
                    var ctx = canvas.getContext('2d');
                    var srcSize = Math.min(img.width, img.height);
                    var srcX = (img.width  - srcSize) / 2;
                    var srcY = (img.height - srcSize) / 2;
                    ctx.drawImage(img, srcX, srcY, srcSize, srcSize, 0, 0, targetSize, targetSize);
                    return canvas.toDataURL('image/png');
                }

                function drawCircularFavicon(img, canvas, size) {
                    if (!canvas) return;
                    var ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, size, size);
                    ctx.save();
                    ctx.beginPath();
                    ctx.arc(size / 2, size / 2, size / 2, 0, Math.PI * 2);
                    ctx.closePath();
                    ctx.clip();
                    var scale = Math.max(size / img.width, size / img.height);
                    var w = img.width  * scale;
                    var h = img.height * scale;
                    ctx.drawImage(img, (size - w) / 2, (size - h) / 2, w, h);
                    ctx.restore();
                }

                // Draw favicon previews from a URL (no crossOrigin — avoids CORS block on localhost)
                function loadAndDrawFavicons(src) {
                    var img = new Image();
                    img.onload = function () {
                        drawCircularFavicon(this, faviconCanvas, 32);
                        drawCircularFavicon(this, faviconTabCanvas, 16);
                    };
                    img.src = src;
                }

                // ── initial favicon draw ──────────────────────────────────────
                if (logoPreview) {
                    loadAndDrawFavicons(logoPreview.src);
                }

                // ── file-change preview ───────────────────────────────────────
                if (logoUpload) {
                    logoUpload.addEventListener('change', function (e) {
                        var file = e.target.files[0];
                        if (!file) return;

                        if (file.size > 2 * 1024 * 1024) {
                            alert('File size must be under 2MB.');
                            this.value = '';
                            return;
                        }

                        var reader = new FileReader();
                        reader.onload = function (evt) {
                            var img = new Image();
                            img.onload = function () {
                                var resized = resizeAndCrop(this, 200);
                                logoPreview.src = resized;

                                var faviconImg = new Image();
                                faviconImg.onload = function () {
                                    drawCircularFavicon(this, faviconCanvas, 32);
                                    drawCircularFavicon(this, faviconTabCanvas, 16);

                                    var tmp = document.createElement('canvas');
                                    tmp.width = tmp.height = 32;
                                    drawCircularFavicon(this, tmp, 32);
                                    var link = document.querySelector("link[rel~='icon']");
                                    if (link) link.href = tmp.toDataURL('image/png');
                                };
                                faviconImg.src = resized;
                            };
                            img.src = evt.target.result;
                        };
                        reader.readAsDataURL(file);
                    });
                }

                // ── RESET button ──────────────────────────────────────────────
                if (removeLogo) {
                    removeLogo.addEventListener('click', function () {
                        // Clear file input (clone is the only cross-browser reliable way)
                        if (logoUpload) {
                            var fresh = logoUpload.cloneNode(true);
                            logoUpload.parentNode.replaceChild(fresh, logoUpload);
                            // re-attach change listener to the new element
                            fresh.addEventListener('change', logoUpload.onchange || function(){});
                        }

                        // Reset logo preview to saved server image
                        var serverSrc = '<?= BASE_URL ?>/assets/images/logo.png?v=' + Date.now();
                        if (logoPreview) logoPreview.src = serverSrc;

                        // Redraw favicon canvases (no crossOrigin needed)
                        loadAndDrawFavicons(serverSrc);
                    });
                }

            }());
        </script>

</body>

</html>