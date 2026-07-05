<?php
require_once 'includes/load_user.php';
require_login();

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
  <meta name="description" content="<?= htmlspecialchars($platform_name) ?> turns your videos into a sellable productâ€”host, brand, and monetize from a single custom landing page without building a full site.">
  <!-- end::GXON Meta Basic -->

  <!-- begin::GXON Meta Social -->
  <meta property="og:url" content="<?= $full_base_url ?>/">
  <meta property="og:site_name" content="<?= htmlspecialchars($platform_name) ?>">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="en_US">
  <meta property="og:title" content="<?= htmlspecialchars($platform_name) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($platform_name) ?> turns your videos into a sellable productâ€”host, brand, and monetize from a single custom landing page without building a full site.">
  <meta property="og:image" content="https://gxon.layoutdrop.com/demo/preview.png">
  <!-- end::GXON Meta Social -->

  <!-- begin::GXON Meta Twitter -->
  <meta name="twitter:card" content="summary">
  <meta name="twitter:url" content="<?= $full_base_url ?>/">
  <meta name="twitter:creator" content="@layoutdrop">
  <meta name="twitter:title" content="<?= htmlspecialchars($platform_name) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($platform_name) ?> turns your videos into a sellable productâ€”host, brand, and monetize from a single custom landing page without building a full site.">
  <!-- end::GXON Meta Twitter -->

  <!-- begin::GXON Website Page Title -->
  <title><?= htmlspecialchars($platform_name) ?> - Video Upload</title>
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
    <style>
        .upload-drop-zone {
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            padding: 40px 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            background: #282b44;
        }

        .upload-drop-zone:hover,
        .upload-drop-zone.dragging {
            border-color: #0d6efd;
            background: #282b44;
        }

        .upload-drop-zone.disabled {
            pointer-events: none;
            opacity: 0.6;
        }

        [x-cloak] {
            display: none !important;
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
  <!-- end::GXON Sidebar Menu -->

  <!-- begin::GXON Sidebar right -->

  <!-- end::GXON Sidebar right -->

    <main class="app-wrapper">
        <div class="container py-4">

            
            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
                <i class="ph ph-warning-octagon fs-2 me-3"></i>
                <div>
                    <h6 class="alert-heading fw-bold mb-1">Strict Prohibition: Child Exploitation Content</h6>
                    <p class="mb-0 small">Uploading prohibited content results in <strong>permanent account bans</strong>,
                        blacklisting via <strong>the payment gateway</strong>, and <strong>total forfeiture of funds</strong>.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h6 class="card-title">Upload New Video</h6>
                </div>

                <div class="card-body p-4" x-data="videoUploadHandler()">
                    <form @submit.prevent="submitForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" autocomplete="off">
                        <div class="row g-4">
                            <div class="col-lg-5">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Video Title</label>
                                    <input type="text" x-model="formData.title" class="form-control form-control-lg"
                                        placeholder="e.g. My Premium Tutorial" :disabled="isUploading" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Price (TZS)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">TZS</span>
                                        <input type="number" x-model="formData.price" min="1"
                                            class="form-control form-control-lg" placeholder="0.00" :disabled="isUploading"
                                            required>
                                    </div>
                                    <div class="form-text">Set the price users pay to unlock this video.</div>
                                </div>
                                <br>
                                <div class="form-check mt-4 bg-light p-3 rounded">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" id="termsCheck"
                                        x-model="formData.agreed" required>
                                    <label class="form-check-label small" for="termsCheck">
                                        I certify this video complies with all safety guidelines. I understand the penalties
                                        for violating content policies.
                                    </label>
                                </div>
                            </div>

                            
                            <div class="col-lg-7">
                                <label class="form-label fw-bold">Video File</label>
                                <div class="upload-drop-zone text-center position-relative overflow-hidden" style="background: #282b44 !important; min-height: 250px; display: flex; flex-direction: column; align-items: center; justify-content: center;"
                                    :class="isUploading ? 'disabled' : (isDragging ? 'dragging' : '')"
                                    @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
                                    @drop.prevent="handleDrop($event)" @click="$refs.fileInput.click()">

                                    <input type="file" x-ref="fileInput" @change="fileChosen" class="d-none"
                                        accept="video/*">

                                    <div x-show="!formData.video_file">
                                        <i class="ph ph-cloud-arrow-up display-4 text-primary"></i>
                                        <h6 class="mt-3 mb-1">Click or drag video here</h6>
                                        <span class="text-muted small">MP4, MOV, or AVI (Max 100MB)</span>
                                    </div>

                                    <div x-show="formData.video_file" x-cloak class="w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="position: absolute; inset: 0; z-index: 2;">
                                        <!-- Thumbnail Image filling the entire box -->
                                        <div x-show="formData.thumbnail" class="position-absolute start-0 top-0 w-100 h-100" style="z-index: 1;">
                                            <img :src="formData.thumbnail" style="width: 100%; height: 100%; object-fit: cover;" alt="Thumbnail Preview">
                                            <!-- Gradient overlay for text contrast -->
                                            <div class="position-absolute start-0 top-0 w-100 h-100" style="background: linear-gradient(to top, rgba(0,0,0,0.85) 40%, rgba(0,0,0,0.3) 100%);"></div>
                                        </div>

                                        <!-- Content Overlay -->
                                        <div class="position-relative w-100 px-3 py-4" style="z-index: 2;">
                                            <!-- Loader if generating -->
                                            <div x-show="!formData.thumbnail" class="mb-2">
                                                <i class="fa-solid fa-spinner fa-spin fs-1 text-primary"></i>
                                                <div class="small text-muted mt-2">Generating preview...</div>
                                            </div>

                                            <!-- Thumbnail Ready Icon -->
                                            <div x-show="formData.thumbnail" class="mb-2">
                                                <div class="d-inline-flex align-items-center justify-content-center bg-success text-white rounded-circle shadow-sm" style="width: 48px; height: 48px;">
                                                    <i class="fa-solid fa-circle-check fs-4"></i>
                                                </div>
                                                <div class="text-success small fw-bold mt-2 text-uppercase" style="letter-spacing: 0.5px;">Video Ready</div>
                                            </div>

                                            <h6 class="mt-2 mb-1 text-truncate w-75 mx-auto text-white" x-text="formData.video_file?.name"></h6>
                                            <span class="badge bg-light text-dark border shadow-sm" x-text="formatBytes(formData.video_file?.size)"></span>
                                            
                                            <div class="mt-3 small text-white-50">
                                                <i class="fa-solid fa-arrows-rotate me-1"></i> Click to change video
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                
                                <div x-show="isUploading" class="mt-4" x-cloak>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="small fw-bold text-primary" x-text="statusMessage"></span>
                                        <span class="small fw-bold" x-text="progress + '%'"></span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated"
                                            :style="`width: ${progress}%`"></div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-2 text-muted small">
                                        <span x-text="uploadStats"></span>
                                        <span>Est. Time: <strong x-text="estimatedTime"></strong></span>
                                    </div>
                                </div>

                                <button class="btn btn-primary btn-lg w-100 mt-4 shadow-sm" type="submit"
                                    :disabled="isUploading || !formData.video_file || !formData.agreed">
                                    <i class="ph ph-rocket-launch me-2"></i>
                                    <span x-show="!isUploading">Upload</span>
                                    <span x-show="isUploading">Processing...</span>
                                </button>
                            </div>
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
    </footer>    <!-- begin::GXON Page Scripts -->
  <script src="assets/libs/global/global.min.js"></script>
  <script src="assets/js/appSettings.js"></script>
  <script src="assets/js/main.js"></script>
  <!-- end::GXON Page Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <script>
    function videoUploadHandler() {
      return {
        isUploading: false,
        isDragging: false,
        progress: 0,
        startTime: null,
        estimatedTime: '--:--',
        uploadStats: '',
        statusMessage: 'Uploading...',
        formData: {
          title: '',
          price: '1000',
          video_file: null,
          thumbnail: null,
          agreed: true
        },

        extractThumbnail(file) {
          const self = this;
          const video = document.createElement('video');
          const canvas = document.createElement('canvas');
          video.muted = true;
          video.playsInline = true;
          video.preload = 'metadata';
          const objectUrl = URL.createObjectURL(file);
          video.src = objectUrl;

          video.addEventListener('loadedmetadata', function() {
            video.currentTime = Math.min(1, video.duration * 0.1);
          });

          video.addEventListener('seeked', function() {
            canvas.width  = video.videoWidth  || 320;
            canvas.height = video.videoHeight || 180;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            self.formData.thumbnail = canvas.toDataURL('image/jpeg', 0.75);
            URL.revokeObjectURL(objectUrl);
          });

          video.addEventListener('error', function() {
            URL.revokeObjectURL(objectUrl);
          });

          video.load();
        },

        fileChosen(event) {
          const file = event.target.files[0];
          if (file) {
            this.formData.video_file = file;
            this.extractThumbnail(file);
          }
        },

        handleDrop(e) {
          this.isDragging = false;
          if (this.isUploading) return;
          const file = e.dataTransfer.files[0];
          if (file && file.type.startsWith('video/')) {
            this.formData.video_file = file;
            this.extractThumbnail(file);
          } else {
            this.toast('Invalid file type', 'error');
          }
        },

        formatBytes(bytes, decimals = 2) {
          if (bytes === 0) return '0 Bytes';
          const k = 1024;
          const dm = decimals < 0 ? 0 : decimals;
          const sizes = ['Bytes', 'KB', 'MB', 'GB'];
          const i = Math.floor(Math.log(bytes) / Math.log(k));
          return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        },

        toast(message, icon = 'success') {
          const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
          });
          Toast.fire({ icon: icon, title: message });
        },

        submitForm() {
          this.isUploading = true;
          this.progress = 0;
          this.startTime = new Date().getTime();
          this.statusMessage = 'Uploading video file...';

          let data = new FormData();
          data.append('title', this.formData.title);
          data.append('price', this.formData.price);
          data.append('video_file', this.formData.video_file);
          if (this.formData.thumbnail) {
            data.append('base64_thumbnail', this.formData.thumbnail);
          }
          data.append('csrf_token', '<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>');

          axios.post('upload-handler.php', data, {
            onUploadProgress: (p) => {
              this.progress = Math.round((p.loaded * 100) / p.total);
              let now = new Date().getTime();
              let duration = (now - this.startTime) / 1000;
              let bps = p.loaded / duration;
              let remainingBytes = p.total - p.loaded;
              let secondsRemaining = remainingBytes / bps;
              this.uploadStats = `${this.formatBytes(p.loaded)} / ${this.formatBytes(p.total)}`;
              this.estimatedTime = p.loaded === p.total ? '00:00' : new Date(secondsRemaining * 1000).toISOString().substring(14, 19);
              if (this.progress < 50) {
                this.statusMessage = 'Uploading video file...';
              } else if (this.progress < 80) {
                this.statusMessage = 'Processing...';
              } else {
                this.statusMessage = 'Finalizing...';
              }
            }
          })
          .then(res => {
            if (res.data.status === 'error') {
                this.isUploading = false;
                Swal.fire('Upload Error', res.data.message || 'Server rejected the upload.', 'error');
                return;
            }
            this.statusMessage = 'Upload complete!';
            Swal.fire('Success!', 'Your video is live.', 'success').then(() => {
              window.location.href = 'videos';
            });
          })
          .catch(err => {
            this.isUploading = false;
            let errMsg = 'Something went wrong. Please try again.';
            if (err.response) {
                if (err.response.status === 413) {
                    errMsg = 'File is too large for the server configuration (HTTP 413 Payload Too Large).';
                } else if (err.response.data && err.response.data.message) {
                    errMsg = err.response.data.message;
                } else {
                    errMsg = `Server Error: ${err.response.status} ${err.response.statusText}`;
                }
            } else if (err.request) {
                errMsg = 'No response from server. It might have timed out.';
            } else {
                errMsg = err.message;
            }
            Swal.fire('Upload Error', errMsg, 'error');
          });
        }
      }
    }
  </script>

</body>

</html>
