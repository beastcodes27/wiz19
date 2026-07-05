<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth_functions.php';
require_once 'includes/mailer.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = "Invalid request. Please try again.";
        $messageType = "danger";
    } else {
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $password = $_POST['password'] ?? '';
        $password_confirmation = $_POST['password_confirmation'] ?? '';
        $terms = isset($_POST['terms']);
        
        if (empty($name) || empty($email) || empty($phone) || empty($password)) {
            $message = "Please fill in all required fields.";
            $messageType = "danger";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email address.";
            $messageType = "danger";
        } elseif ($password !== $password_confirmation) {
            $message = "Passwords do not match.";
            $messageType = "danger";
        } elseif (!$terms) {
            $message = "You must agree to the privacy policy & terms.";
            $messageType = "danger";
        } else {
            try {
                // Check if email exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->rowCount() > 0) {
                    $message = "An account with this email already exists.";
                    $messageType = "danger";
                } else {
                    // Hash the password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert into database
                    $insertStmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, 'user')");
                    $insertStmt->execute([$name, $email, $phone, $hashed_password]);
                    
                    // Send Welcome Email
                    $subject = "Welcome to " . $platform_name . "!";
                    $email_body = "<h3>Hello " . htmlspecialchars($name) . ",</h3>
                                   <p>Thank you for registering on " . htmlspecialchars($platform_name) . ". Your account has been successfully created.</p>
                                   <p>You can now start uploading videos and monetizing your content.</p>
                                   <br><p>Best Regards,<br>The " . htmlspecialchars($platform_name) . " Team</p>";
                    send_email($email, $subject, $email_body);
                    
                    // Log the user in
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    $_SESSION['user_role'] = 'user';
                    $_SESSION['user_name'] = $name;
                    
                    header("Location: dashboard");
                    exit;
                }
            } catch(PDOException $e) {
                $message = "Registration failed. Please try again later.";
                $messageType = "danger";
                error_log("Registration error: " . $e->getMessage());
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
  <title><?= htmlspecialchars($platform_name) ?> - Register</title>
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
                <p class="text-white"></p>
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
              <p>Sign up to create your secure admin.</p>
            </div>
            
            <?php if ($message): ?>
              <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php endif; ?>

            <form action="" method="POST">
              <?php csrf_field(); ?>
              <div class="mb-4">
                <label class="form-label" for="registerName">Name</label>
                <input type="text" class="form-control" id="registerName" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" placeholder="Full Name" required>
              </div>

              <div class="mb-4">
                <label class="form-label" for="registerEmail">Email Address</label>
                <input type="email" class="form-control" id="registerEmail" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" placeholder="info@example.com" required>
              </div>

              <div class="mb-4">
                <label class="form-label" for="registerPhone">Phone Number (M-Pesa)</label>
                <input type="text" class="form-control" id="registerPhone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" placeholder="255700000000" required>
              </div>

              <div class="mb-4">
                <label class="form-label" for="registerPassword">Password</label>
                <div class="input-group">
                  <input type="password" class="form-control" id="registerPassword" name="password" placeholder="********" required>
                  <button class="btn btn-outline-secondary" type="button" id="toggleRegisterPassword" tabindex="-1" aria-label="Toggle password visibility">
                    <i class="fa-regular fa-eye" id="toggleRegisterPasswordIcon"></i>
                  </button>
                </div>
              </div>

              <div class="mb-4">
                <label class="form-label" for="password_confirmation">Confirm Password</label>
                <div class="input-group">
                  <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="********" required>
                  <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword" tabindex="-1" aria-label="Toggle confirm password visibility">
                    <i class="fa-regular fa-eye" id="toggleConfirmPasswordIcon"></i>
                  </button>
                </div>
              </div>

              <div class="mb-4">
                <div class="form-check mb-0">
                  <input class="form-check-input" type="checkbox" id="termsConditions" name="terms" required>
                  <label class="form-check-label" for="termsConditions">
                    I agree to <a href="javascript:void(0);">privacy policy & terms</a>
                  </label>
                </div>
              </div>

              <div class="mb-3">
                <button type="submit" class="btn btn-primary waves-effect waves-light w-100">Sign up</button>
              </div>

              <p class="mb-5 text-center">Have any account? <a href="login">Sign In here</a>
              </p>
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

  <script>
    function togglePasswordVisibility(btnId, inputId, iconId) {
      const btn = document.getElementById(btnId);
      if (!btn) return;
      btn.addEventListener('click', function () {
        const input = document.getElementById(inputId);
        const icon  = document.getElementById(iconId);
        if (input.type === 'password') {
          input.type = 'text';
          icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
          input.type = 'password';
          icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
      });
    }
    togglePasswordVisibility('toggleRegisterPassword',  'registerPassword',    'toggleRegisterPasswordIcon');
    togglePasswordVisibility('toggleConfirmPassword',   'password_confirmation', 'toggleConfirmPasswordIcon');
  </script>
  
  <script defer src="https://static.cloudflareinsights.com/beacon.min.js/v833ccba57c9e4d2798f2e76cebdd09a11778172276447" integrity="sha512-57MDmcccJXYtNnH+ZiBwzC4jb2rvgVCEokYN+L/nLlmO8rfYT/gIpW2A569iJ/3b+0UEasghjuZH/ma3wIs/EQ==" data-cf-beacon='{"version":"2024.11.0","token":"8710ddc558dc4267b79681cdb061c29e","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>
</body>

</html>
