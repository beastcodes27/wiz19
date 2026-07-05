<?php
session_start();

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = "Invalid request. Please try again.";
        $messageType = "danger";
    } else {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // TODO: Add your database connection and email sending logic here
            // 1. Check if email exists in database
            // 2. Generate reset token
            // 3. Save token to database
            // 4. Send email with reset link
            
            $message = "If an account with that email exists, a password reset link has been sent.";
            $messageType = "success";
        } else {
            $message = "Please enter a valid email address.";
            $messageType = "danger";
        }
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
  <title><?= htmlspecialchars($platform_name) ?> - Reset Password</title>
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

    <div class="auth-frame-wrapper">
      <div class="row g-0 h-100">
        <div class="col-lg-6">
          <div class="auth-frame" style="background-image: url(assets/images/auth/auth-frame.webp);">
            <div class="clearfix">
              <div class="auth-content">
                <h1 class="display-6 text-white fw-bold">Welcome Back!</h1>
                <p class="text-white">Our HR Management & Administration ensure your organization runs
                  smoothly, focusing on people, compliance, and efficiency to drive growth and employee
                  satisfaction.</p>
              </div>
              <div class="auth-imgs position-relative">
                <img src="assets/images/auth/img1.png" alt="" class="img-fluid">
                <img src="assets/images/auth/img2.png" alt="" class="img-fluid position1 position-absolute">
                <img src="assets/images/auth/img3.png" alt="" class="img-fluid position2 position-absolute">
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-6 align-self-center">
          <div class="p-4 p-sm-5 maxw-450px m-auto auth-inner" data-simplebar>
            <div class="mb-4 text-center">
              <a href="index.html" aria-label="<?= htmlspecialchars($platform_name) ?> logo">
                <img class="visible-light" src="<?= BASE_URL ?>/assets/images/logo.png" alt="<?= htmlspecialchars($platform_name) ?> logo" style="max-height: 50px; width: auto;">
                <img class="visible-dark" src="<?= BASE_URL ?>/assets/images/logo.png" alt="<?= htmlspecialchars($platform_name) ?> logo" style="max-height: 50px; width: auto;">
              </a>
            </div>
            <div class="text-center mb-5">
              <h5 class="mb-1">Welcome to <?= htmlspecialchars($platform_name) ?></h5>
              <p>Enter your email to reset your password.</p>
            </div>
            
            <?php if ($message): ?>
              <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php endif; ?>

            <form method="POST" action="">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
              <div class="mb-4">
                <label class="form-label" for="resetEmail">Email address</label>
                <input type="email" class="form-control" name="email" id="resetEmail" placeholder="info@example.com" required>
              </div>
              <div class="clearfix">
                <button type="submit" value="Submit" class="btn btn-primary waves-effect waves-light w-100 mb-3">Forgot Password</button>
                <a href="login" class="btn btn-light waves-effect waves-light w-100"> Cancel </a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>
  <!-- begin::GXON Page Scripts -->
  <script src="assets/libs/global/global.min.js"></script>
  <script src="assets/js/appSettings.js"></script>
  <script src="assets/js/main.js"></script>
  <!-- end::GXON Page Scripts -->
  
  <script defer src="https://static.cloudflareinsights.com/beacon.min.js/v833ccba57c9e4d2798f2e76cebdd09a11778172276447" integrity="sha512-57MDmcccJXYtNnH+ZiBwzC4jb2rvgVCEokYN+L/nLlmO8rfYT/gIpW2A569iJ/3b+0UEasghjuZH/ma3wIs/EQ==" data-cf-beacon='{"version":"2024.11.0","token":"8710ddc558dc4267b79681cdb061c29e","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>
</body>

</html>
