<?php
$file = "c:/xampp/htdocs/flowtune/video-edit.php";
$content = file_get_contents($file);

// Replace the entire POST handling logic since it's moved to edit-upload-handler.php
$pattern = '/\/\/ Handle form submission.*?if \(\$_SERVER\[\'REQUEST_METHOD\'\] === \'POST\'\) \{.*?\n\}\n\?>/s';
$replace = "?>";
$content = preg_replace($pattern, $replace, $content);

// We need to inject AlpineJS, Axios, SweetAlert into the head or before closing body
$headInjection = '
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
      [x-cloak] { display: none !important; }
  </style>
</head>';
$content = str_replace('</head>', $headInjection, $content);

// Now for the body replacement
// We will replace `<main class="app-wrapper">` to `</main>`
$mainPattern = '/<main class="app-wrapper">.*?<\/main>/s';
$newMain = '
  <main class="app-wrapper">
      <div class="container py-4">
          <div class="app-page-head d-flex align-items-center justify-content-between my-4">
              <div>
                  <h1 class="h3 mb-0">Edit Video</h1>
                  <p class="text-muted small mb-0">Update your video details or replace the video file</p>
              </div>
              <a href="videos" class="btn btn-outline-secondary btn-sm">
                  <i class="fa-solid fa-arrow-left me-1"></i> Back to Videos
              </a>
          </div>

          <div class="row g-4" x-data="videoEditHandler()">
              <!-- Edit Form -->
              <div class="col-lg-8">
                  <div class="card shadow-sm">
                      <div class="card-header">
                          <h6 class="card-title mb-0 fw-bold">Video Information</h6>
                      </div>
                      <div class="card-body p-4">
                          <form @submit.prevent="submitForm">
                              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION[\'csrf_token\']); ?>">
                              <input type="hidden" name="id" value="<?php echo (int)$video[\'id\']; ?>">

                              <div class="mb-4">
                                  <label class="form-label fw-semibold">Video Title</label>
                                  <input type="text" x-model="formData.title" class="form-control form-control-lg" required>
                              </div>

                              <div class="mb-4">
                                  <label class="form-label fw-semibold">Price (TZS)</label>
                                  <input type="number" x-model="formData.price" class="form-control form-control-lg" min="0" step="0.01" required>
                              </div>

                              <div class="mb-4">
                                  <label class="form-label fw-semibold">Status</label>
                                  <select x-model="formData.status" class="form-select form-select-lg">
                                      <option value="active">✅ Active — Visible to viewers</option>
                                      <option value="pending">⏸ Inactive — Hidden from viewers</option>
                                  </select>
                              </div>

                              <div class="mb-4">
                                  <label class="form-label fw-semibold">Replace Video File (Optional)</label>
                                  <div class="upload-drop-zone text-center position-relative overflow-hidden" style="background: #282b44 !important; min-height: 250px; display: flex; flex-direction: column; align-items: center; justify-content: center;"
                                      :class="isUploading ? \'disabled\' : (isDragging ? \'dragging\' : \'\')"
                                      @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
                                      @drop.prevent="handleDrop($event)" @click="$refs.fileInput.click()">

                                      <input type="file" x-ref="fileInput" @change="fileChosen" class="d-none" accept="video/*">

                                      <div x-show="!formData.video_file">
                                          <i class="ph ph-cloud-arrow-up display-4 text-primary"></i>
                                          <h6 class="mt-3 mb-1 text-white">Click or drag video here to replace</h6>
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
                              </div>
                              
                              <div x-show="isUploading" class="mt-4" x-cloak>
                                  <div class="d-flex justify-content-between mb-1">
                                      <span class="small fw-bold text-primary" x-text="statusMessage"></span>
                                      <span class="small fw-bold" x-text="progress + \'%\'"></span>
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

                              <div class="d-flex gap-2 pt-2 mt-4">
                                  <button type="submit" class="btn btn-primary px-4" :disabled="isUploading">
                                      <i class="fa-solid fa-floppy-disk me-2"></i> <span x-text="isUploading ? \'Saving...\' : \'Save Changes\'"></span>
                                  </button>
                                  <a href="videos" class="btn btn-light px-4" :class="isUploading ? \'disabled\' : \'\'">Cancel</a>
                              </div>
                          </form>
                      </div>
                  </div>
              </div>

              <!-- Old Thumbnail Preview / Stats -->
              <div class="col-lg-4">
                  <div class="card shadow-sm">
                      <div class="card-header">
                          <h6 class="card-title mb-0 fw-bold">Current Thumbnail</h6>
                      </div>
                      <div class="card-body text-center p-3">
                          <?php if (!empty($video[\'thumbnail_url\'])): ?>
                              <img src="<?php echo htmlspecialchars($video[\'thumbnail_url\']); ?>"
                                   class="img-fluid rounded-3 w-100 object-fit-cover"
                                   style="max-height: 200px;" alt="Thumbnail">
                          <?php else: ?>
                              <div class="bg-dark rounded-3 d-flex align-items-center justify-content-center" style="height:160px;">
                                  <i class="fa-solid fa-film text-white fa-2x"></i>
                              </div>
                              <p class="text-muted small mt-2 mb-0">No thumbnail available</p>
                          <?php endif; ?>
                      </div>
                  </div>

                  <!-- Video Stats -->
                  <div class="card shadow-sm mt-3">
                      <div class="card-header">
                          <h6 class="card-title mb-0 fw-bold">Stats</h6>
                      </div>
                      <div class="card-body">
                          <div class="d-flex justify-content-between py-2 border-bottom">
                              <span class="text-muted small">Views</span>
                              <span class="fw-semibold"><?php echo number_format($video[\'views\']); ?></span>
                          </div>
                          <div class="d-flex justify-content-between py-2 border-bottom">
                              <span class="text-muted small">Clicks</span>
                              <span class="fw-semibold"><?php echo number_format($video[\'clicks\']); ?></span>
                          </div>
                          <div class="d-flex justify-content-between py-2">
                              <span class="text-muted small">Earnings</span>
                              <span class="fw-semibold text-success">TZS <?php echo number_format($video[\'earnings\']); ?></span>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      </div>
  </main>';

$content = preg_replace($mainPattern, $newMain, $content);

$jsInjection = '
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <script>
    function videoEditHandler() {
      return {
        isUploading: false,
        isDragging: false,
        progress: 0,
        startTime: null,
        estimatedTime: \'--:--\',
        uploadStats: \'\',
        statusMessage: \'Saving...\',
        formData: {
          title: ' . json_encode($video["title"] ?? "") . ',
          price: ' . json_encode($video["price"] ?? 0) . ',
          status: ' . json_encode($video["status"] ?? "active") . ',
          video_file: null,
          thumbnail: null
        },

        extractThumbnail(file) {
          const self = this;
          const video = document.createElement(\'video\');
          const canvas = document.createElement(\'canvas\');
          video.muted = true;
          video.playsInline = true;
          video.preload = \'metadata\';
          const objectUrl = URL.createObjectURL(file);
          video.src = objectUrl;

          video.addEventListener(\'loadedmetadata\', function() {
            video.currentTime = Math.min(1, video.duration * 0.1);
          });

          video.addEventListener(\'seeked\', function() {
            canvas.width  = video.videoWidth  || 320;
            canvas.height = video.videoHeight || 180;
            const ctx = canvas.getContext(\'2d\');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            self.formData.thumbnail = canvas.toDataURL(\'image/jpeg\', 0.75);
            URL.revokeObjectURL(objectUrl);
          });

          video.addEventListener(\'error\', function() {
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
          if (file && file.type.startsWith(\'video/\')) {
            this.formData.video_file = file;
            this.extractThumbnail(file);
          } else {
            this.toast(\'Invalid file type\', \'error\');
          }
        },

        formatBytes(bytes, decimals = 2) {
          if (bytes === 0) return \'0 Bytes\';
          const k = 1024;
          const dm = decimals < 0 ? 0 : decimals;
          const sizes = [\'Bytes\', \'KB\', \'MB\', \'GB\'];
          const i = Math.floor(Math.log(bytes) / Math.log(k));
          return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + \' \' + sizes[i];
        },

        toast(message, icon = \'success\') {
          const Toast = Swal.mixin({
            toast: true,
            position: \'top-end\',
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
          this.statusMessage = \'Saving details...\';

          let data = new FormData();
          data.append(\'id\', document.querySelector(\'input[name="id"]\').value);
          data.append(\'title\', this.formData.title);
          data.append(\'price\', this.formData.price);
          data.append(\'status\', this.formData.status);
          if (this.formData.video_file) {
              data.append(\'video_file\', this.formData.video_file);
              this.statusMessage = \'Uploading video file...\';
          }
          if (this.formData.thumbnail) {
            data.append(\'base64_thumbnail\', this.formData.thumbnail);
          }
          data.append(\'csrf_token\', document.querySelector(\'input[name="csrf_token"]\').value);

          axios.post(\'edit-upload-handler.php\', data, {
            onUploadProgress: (p) => {
              if (this.formData.video_file) {
                  this.progress = Math.round((p.loaded * 100) / p.total);
                  let now = new Date().getTime();
                  let duration = (now - this.startTime) / 1000;
                  let bps = p.loaded / duration;
                  let remainingBytes = p.total - p.loaded;
                  let secondsRemaining = remainingBytes / bps;
                  this.uploadStats = `${this.formatBytes(p.loaded)} / ${this.formatBytes(p.total)}`;
                  this.estimatedTime = p.loaded === p.total ? \'00:00\' : new Date(secondsRemaining * 1000).toISOString().substring(14, 19);
                  if (this.progress < 50) {
                    this.statusMessage = \'Uploading video file...\';
                  } else if (this.progress < 80) {
                    this.statusMessage = \'Processing...\';
                  } else {
                    this.statusMessage = \'Finalizing...\';
                  }
              }
            }
          })
          .then(res => {
            if (res.data.status === \'success\') {
                this.statusMessage = \'Upload complete!\';
                Swal.fire(\'Success!\', res.data.message, \'success\').then(() => {
                  window.location.reload();
                });
            } else {
                this.isUploading = false;
                Swal.fire(\'Error\', res.data.message, \'error\');
            }
          })
          .catch(err => {
            this.isUploading = false;
            Swal.fire(\'Error\', \'Something went wrong. Please try again.\', \'error\');
          });
        }
      }
    }
  </script>
</body>';

$content = str_replace('</body>', $jsInjection, $content);

// We need to fetch $video["title"] dynamically via PHP inside the script block.
// To do this, I will output the PHP tags instead of evaluating json_encode now!
$content = str_replace(
    'title: ""', 
    'title: \'<?php echo addslashes($video["title"]); ?>\'', 
    $content
);
$content = str_replace(
    'price: 0', 
    'price: \'<?php echo (float)$video["price"]; ?>\'', 
    $content
);
$content = str_replace(
    'status: "active"', 
    'status: \'<?php echo addslashes($video["status"]); ?>\'', 
    $content
);


file_put_contents($file, $content);
echo "Updated video-edit.php\n";
