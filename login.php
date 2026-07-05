<?php
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    if (is_admin()) {
        header("Location: admin/index");
    } else {
        header("Location: dashboard");
    }
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = "Invalid request. Please try again.";
        $messageType = "danger";
    } else {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $message = "Please fill in all required fields.";
            $messageType = "danger";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, full_name, password, role FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_name'] = $user['full_name'];
                    
                    // Prevent session fixation
                    session_regenerate_id(true);
                    
                    if ($user['role'] === 'admin') {
                        header("Location: admin/index");
                    } else {
                        header("Location: dashboard");
                    }
                    exit;
                } else {
                    $message = "Invalid email or password.";
                    $messageType = "danger";
                }
            } catch(PDOException $e) {
                $message = "Login failed. Please try again later.";
                $messageType = "danger";
                error_log("Login error: " . $e->getMessage());
            }
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
  <title><?= htmlspecialchars($platform_name) ?></title>
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
  <meta name="csrf-token" content="VITH3465xo6CMIK6k8iG0jeYb1d6NvTKwvHjTe5R">
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


          <div class="auth-cover-wrapper">
      <div class="row g-0">
        <div class="col-lg-6">
          <div class="auth-cover" style="background-image: url(assets/images/auth/auth-cover-bg.png);">
            <div class="clearfix">
              <img src="assets/images/auth/auth.png" alt="" class="img-fluid cover-img ms-5">
              <div class="auth-content">
                <h1 class="display-6 fw-bold">Welcome Back!</h1>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-6 align-self-center">
          <div class="p-3 p-sm-5 maxw-450px m-auto auth-inner" data-simplebar>
            <div class="mb-4 text-center">
              <a href="#" aria-label="<?= htmlspecialchars($platform_name) ?> logo">
                <img class="visible-light" src="<?= BASE_URL ?>/assets/images/logo.png" alt="<?= htmlspecialchars($platform_name) ?> logo" style="max-height: 50px; width: auto;">
                <img class="visible-dark" src="<?= BASE_URL ?>/assets/images/logo.png" alt="<?= htmlspecialchars($platform_name) ?> logo" style="max-height: 50px; width: auto;">
              </a>
            </div>
            <div class="text-center mb-5">
              <h5 class="mb-1">Welcome to <?= htmlspecialchars($platform_name) ?></h5>
              <p>Sign in to access your secure dashboard.</p>
            </div>
            
            <?php if ($message): ?>
              <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php endif; ?>

            <form action="login" method="POST">
              <?php csrf_field(); ?>
              <div class="mb-4">
                <label class="form-label" for="loginEmail">Email Address</label>
                <input type="email" class="form-control" id="loginEmail" placeholder="info@example.com" name="email">
              </div>
              <div class="mb-4">
                <label class="form-label" for="loginPassword">Password</label>
                <div class="input-group">
                  <input type="password" class="form-control" id="loginPassword" placeholder="********" name="password">
                  <button class="btn btn-outline-secondary" type="button" id="toggleLoginPassword" tabindex="-1" aria-label="Toggle password visibility">
                    <i class="fa-regular fa-eye" id="toggleLoginPasswordIcon"></i>
                  </button>
                </div>
              </div>
              <div class="mb-4">
                <div class="d-flex justify-content-between">
                  <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="rememberMe">
                    <label class="form-check-label" for="rememberMe"> Remember Me </label>
                  </div>
                  <a href="password-reset">Forgot Password?</a>
                </div>
              </div>
              <div class="mb-3">
                <button type="submit" value="Submit" class="btn btn-primary waves-effect waves-light w-100">Login</button>
              </div>
              <p class="mb-5 text-center">Don’t have an account? <a href="register">Sign Up here</a>
              </p>
             
            </form>
          </div>
        </div>
      </div>
    </div>
    <!-- begin::GXON Page Scripts -->
  <script src="assets/libs/global/global.min.js"></script>
  <script src="assets/js/appSettings.js"></script>
  <script src="assets/js/main.js"></script>
  <!-- end::GXON Page Scripts -->

  <script>
    (function () {
      const btn = document.getElementById('toggleLoginPassword');
      if (!btn) return;
      btn.addEventListener('click', function () {
        const input = document.getElementById('loginPassword');
        const icon  = document.getElementById('toggleLoginPasswordIcon');
        if (input.type === 'password') {
          input.type = 'text';
          icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
          input.type = 'password';
          icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
      });
    })();
  </script>
                

<script defer src="https://static.cloudflareinsights.com/beacon.min.js/v833ccba57c9e4d2798f2e76cebdd09a11778172276447" integrity="sha512-57MDmcccJXYtNnH+ZiBwzC4jb2rvgVCEokYN+L/nLlmO8rfYT/gIpW2A569iJ/3b+0UEasghjuZH/ma3wIs/EQ==" data-cf-beacon='{"version":"2024.11.0","token":"8710ddc558dc4267b79681cdb061c29e","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>
</body>

</html>
