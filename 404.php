<?php
http_response_code(404);
require_once 'includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="theme-color" content="#316AFF">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>404 Not Found - <?= htmlspecialchars($platform_name) ?></title>

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= BASE_URL ?>/assets/images/apple-touch-icon.png">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">

  <!-- Stylesheets -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
</head>

<body style="background-color: var(--bs-body-bg); color: var(--bs-body-color);">
  <div style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px;">
      <div class="text-center">
          <div class="mb-5">
              <a href="<?= BASE_URL ?>/">
                  <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="<?= htmlspecialchars($platform_name) ?> logo" style="max-height: 60px;">
              </a>
          </div>
          <h1 class="fw-bold text-primary" style="font-size: 8rem; line-height: 1; margin-bottom: 20px;">404</h1>
          <h3 class="mb-3 fw-bold">Oops! Page Not Found</h3>
          <p class="text-muted mb-5 mx-auto" style="max-width: 400px; font-size: 1.1rem;">
              The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.
          </p>
          <a href="<?= BASE_URL ?>/" class="btn btn-primary px-5 py-3 rounded-pill fw-bold shadow-sm">
              <i class="fa-solid fa-house me-2"></i> Return to Homepage
          </a>
      </div>
  </div>

  <script src="<?= BASE_URL ?>/assets/libs/global/global.min.js"></script>
</body>

</html>
