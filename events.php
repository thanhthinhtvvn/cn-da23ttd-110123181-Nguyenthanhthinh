<?php
require_once 'config/db.php';
require_once 'includes/event_image.php';
require_once 'includes/event_description.php';
$page_title = 'Sự Kiện - Bảo Tàng Văn Hóa Dân Tộc Khmer';
include 'includes/header.php';

$events_result = $conn->query("SELECT * FROM events ORDER BY created_at DESC, id DESC");

// Cập nhật truy vấn lấy banner đồng bộ với bảng media mới (không dùng cột `table` bị lỗi)
$event_slides_result = false;
$event_video_result = false;
$mediaColumns = $conn->query("SHOW COLUMNS FROM `media` LIKE 'table'");
if ($mediaColumns && $mediaColumns->num_rows > 0) {
    $event_slides_result = $conn->query("SELECT id, content, url FROM media WHERE `table` = 'event_slides' ORDER BY id ASC");
    $event_video_result = $conn->query("SELECT id, content, url FROM media WHERE `table` = 'event_video' ORDER BY id ASC LIMIT 1");
}

$events = [];
$event_slides = [];
$event_video = null;
if ($events_result) {
    while ($event = $events_result->fetch_assoc()) {
        $events[] = $event;
    }
}

if ($event_slides_result) {
while ($slide = $event_slides_result->fetch_assoc()) {
    $rawUrl = trim($slide['url']);
    $filename = basename($rawUrl);
    
    // Tự động quét và tìm file thực tế trên hosting (giống y hệt trang chủ)
    $slide_image = 'images/' . $filename;
    if ($rawUrl !== '' && filter_var($rawUrl, FILTER_VALIDATE_URL)) {
        $slide_image = $rawUrl;
    } else {
        if (is_file(__DIR__ . '/images/' . $filename)) {
            $slide_image = 'images/' . $filename;
        } elseif (is_file(__DIR__ . '/uploads/' . $filename)) {
            $slide_image = 'uploads/' . $filename;
        } elseif (is_file(__DIR__ . '/' . ltrim($rawUrl, '/'))) {
            $slide_image = ltrim($rawUrl, '/');
        }
    }

        if (!empty($slide_image)) {
            $event_slides[] = [
                'content' => $slide['content'] ?? '',
                'image_src' => $slide_image,
            ];
        }
    }
}
if ($event_video_result && $event_video_result->num_rows > 0) {
    $videoRow = $event_video_result->fetch_assoc();
    $rawVideoUrl = trim($videoRow['url']);
    if (filter_var($rawVideoUrl, FILTER_VALIDATE_URL)) {
        $videoSrc = $rawVideoUrl;
    } else {
        $videoPath = ltrim(str_replace('\\', '/', $rawVideoUrl), '/');
        $videoSrc = in_array('..', explode('/', $videoPath), true) ? '' : $videoPath;
    }
    if ($videoSrc !== '') {
        $event_video = ['content' => $videoRow['content'] ?? '', 'video_src' => $videoSrc];
    }
}
?>

<style>
    /* Cố định chiều cao khung slide và hiển thị ảnh nền sắc nét */
    #eventsCarousel .carousel-item {
        height: 420px;
        position: relative;
        background: #111;
    }
    #eventsCarousel .carousel-overlay {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.45);
        z-index: 2;
    }
    .carousel-caption-custom {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
        width: 90%;
        z-index: 5;
    }
    #eventsCarousel .carousel-banner-image {
        object-fit: cover;
        object-position: center center;
    }
    @media (max-width: 991.98px) {
        #eventsCarousel .carousel-item {
            height: 320px;
        }
    }
    @media (max-width: 575.98px) {
        #eventsCarousel .carousel-item {
            height: 230px;
        }
        #eventsCarousel .carousel-caption-custom h1 {
            font-size: 0.95rem !important;
        }
    }

    .event-detail-modal .modal-content {
        overflow: hidden;
        border: 1px solid rgba(200, 150, 30, 0.45) !important;
        border-radius: 14px;
        background: #171613;
        color: #f8f5ed;
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.35);
    }

    .event-detail-modal .modal-header {
        min-height: 68px;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        background: linear-gradient(135deg, #c8961e, #a07010) !important;
    }

    .event-detail-modal .modal-title {
        flex: 1 1 auto;
    }

    .event-detail-modal .modal-body {
        padding: clamp(1rem, 4vw, 2rem);
    }

    .event-detail-modal .event-detail-image {
        max-width: 100%;
        max-height: 380px;
        border: 1px solid rgba(200, 150, 30, 0.3);
        border-radius: 12px;
        object-fit: contain;
        box-shadow: 0 0.75rem 1.75rem rgba(0, 0, 0, 0.3);
    }

    .event-detail-modal .event-description {
        padding: 1.25rem 1.5rem;
        border: 1px solid rgba(200, 150, 30, 0.2);
        border-left: 3px solid #c8961e;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.035);
        color: #e5e1d8;
        font-size: 1rem;
        line-height: 1.9;
        text-align: left;
        white-space: normal;
    }

    .event-detail-modal .modal-footer {
        padding: 0.75rem 1.5rem 1rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .event-detail-modal .event-close-button {
        border: 0;
        background: #c8961e;
        color: #fff;
    }

    .event-detail-modal .event-close-button:hover {
        background: #a07010;
        color: #fff;
    }

    .event-gallery-banner {
        overflow: hidden;
        border-radius: 12px;
        border: 1px solid rgba(200, 150, 30, 0.38);
        box-shadow: 0 0.75rem 1.75rem rgba(0, 0, 0, 0.14);
        background: #171613;
    }

    #eventGalleryCarousel,
    #eventGalleryCarousel .carousel-item {
        height: 300px;
    }

    #eventGalleryCarousel .gallery-image {
        width: 100%;
        height: 300px;
        object-fit: cover;
        display: block;
    }

    @media (max-width: 767.98px) {
        #eventGalleryCarousel,
        #eventGalleryCarousel .carousel-item { height: 210px; }
        #eventGalleryCarousel .gallery-image { height: 210px; }
    }
</style>

<!-- Banner sự kiện chuẩn đồng bộ với index -->
<?php if (!empty($event_slides)): ?>
<div id="eventsCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="4000">
    <?php if (count($event_slides) > 1): ?>
    <div class="carousel-indicators">
        <?php foreach ($event_slides as $i => $slide): ?>
        <button type="button" data-bs-target="#eventsCarousel" data-bs-slide-to="<?= $i ?>"
            <?= $i === 0 ? 'class="active" aria-current="true"' : '' ?>
            aria-label="Chuyển đến ảnh <?= $i + 1 ?>"></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="carousel-inner">
        <?php foreach ($event_slides as $i => $slide): ?>
        <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
            <img src="<?= htmlspecialchars($slide['image_src'], ENT_QUOTES, 'UTF-8') ?>" class="carousel-banner-image d-block w-100 h-100" alt="Banner sự kiện">
            <div class="carousel-overlay"></div>
            <div class="carousel-caption-custom">
                <h1 class="fw-bold text-white mb-2 shadow-sm" style="font-size: 1rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.8);">
    			<?= htmlspecialchars($slide['content'] ?? '', ENT_QUOTES, 'UTF-8') ?>
				</h1>
                <?php if ($i === 0): ?>
                
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php if (count($event_slides) > 1): ?>
    <button class="carousel-control-prev" type="button" data-bs-target="#eventsCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Ảnh trước</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#eventsCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Ảnh tiếp theo</span>
    </button>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="text-center text-white p-5" style="background:linear-gradient(135deg,#C8961E,#a07010);">
    <i class="fas fa-calendar-alt fa-3x mb-3"></i>
    <h2 class="h3 fw-bold">Sự Kiện &amp; Lễ Hội</h2>
    <p class="mb-0">Các sự kiện văn hóa đặc sắc tại Bảo tàng Văn hóa Dân tộc Khmer</p>
</div>
<?php endif; ?>

<div class="container py-5">
    <?php if (count($events) === 0): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-calendar-times fa-3x mb-3"></i>
            <p>Chưa có sự kiện nào.</p>
        </div>
    <?php else: ?>

    <div class="row g-4">
        <?php foreach ($events as $e):
            $event_image = event_image_src($e['image'] ?? '');
            $event_description = event_description_without_time($e['description'] ?? null);
            $event_description_detail = trim($e['description'] ?? '');
        ?>
        <div class="col-lg-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm card-hover" style="border-top:3px solid #C8961E !important;">

                <!-- Ảnh sự kiện -->
                <?php if ($event_image !== null): ?>
                    <img src="<?= htmlspecialchars($event_image, ENT_QUOTES, 'UTF-8') ?>"
                         class="card-img-top" style="height:220px;object-fit:cover;" alt="">
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center"
                         style="height:220px;background:linear-gradient(135deg,#f0e0a0,#e8c96a);">
                        <i class="fas fa-calendar-star fa-4x" style="color:#C8961E;opacity:0.5"></i>
                    </div>
                <?php endif; ?>

                <div class="card-body d-flex flex-column text-center">
                    <div class="d-flex justify-content-center align-items-center flex-wrap gap-2 mb-2">
                        <h5 class="card-title fw-bold mb-0"><?= htmlspecialchars($e['title']) ?></h5>
                    </div>
                    <p class="card-text text-muted small flex-grow-1">
                        <?= htmlspecialchars(mb_substr($event_description, 0, 150)) ?>...
                    </p>

                    <button class="btn btn-warning text-white btn-sm mt-2"
                            data-bs-toggle="modal" data-bs-target="#modal<?= $e['id'] ?>">
                        <i class="fas fa-info-circle me-1"></i>Xem chi tiết
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal chi tiết sự kiện -->
        <div class="modal fade" id="modal<?= $e['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable event-detail-modal">
                <div class="modal-content border-0">
                    <div class="modal-header text-white flex-wrap gap-2" style="background:#C8961E;">
                        <h5 class="modal-title fw-bold">
                            <?= htmlspecialchars($e['title']) ?>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <?php if ($event_image !== null): ?>
                            <div class="text-center mb-4">
                                <img src="<?= htmlspecialchars($event_image, ENT_QUOTES, 'UTF-8') ?>"
                                     class="event-detail-image" alt="">
                            </div>
                        <?php endif; ?>
                        <div class="event-description">
                            <?= nl2br(htmlspecialchars($event_description_detail, ENT_QUOTES, 'UTF-8')) ?>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-sm event-close-button" data-bs-dismiss="modal">Đóng</button>
                    </div>
                </div>
            </div>
        </div>

        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php if ($event_video !== null): ?>
<section class="container pb-5" aria-label="Video nổi bật của sự kiện">
    <div class="mb-3 text-center">
        <h3 class="h4 fw-bold text-warning mb-1">Video sự kiện nổi bật</h3>
        <div class="mx-auto" style="width: 70px; height: 3px; background: #C8961E;"></div>
    </div>
    <div class="event-gallery-banner">
        <video class="d-block w-100" style="max-height: 420px; object-fit: contain; background: #000;" controls autoplay muted loop playsinline>
            <source src="<?= htmlspecialchars($event_video['video_src'], ENT_QUOTES, 'UTF-8') ?>">
            Trình duyệt của bạn không hỗ trợ phát video.
        </video>
    </div>
</section>
<?php elseif (!empty($event_slides)): ?>
<section class="container pb-5" aria-label="Hình ảnh nổi bật của sự kiện">
    <div class="mb-3 text-center">
        <h3 class="h4 fw-bold text-warning mb-1">Hình ảnh sự kiện nổi bật</h3>
        <div class="mx-auto" style="width: 70px; height: 3px; background: #C8961E;"></div>
    </div>
    <div class="event-gallery-banner">
        <div id="eventGalleryCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="3500">
            <div class="carousel-inner">
                <?php foreach ($event_slides as $i => $slide): ?>
                <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                    <img src="<?= htmlspecialchars($slide['image_src'], ENT_QUOTES, 'UTF-8') ?>" class="gallery-image"
                         alt="<?= htmlspecialchars($slide['content'] ?: 'Hình ảnh sự kiện', ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>