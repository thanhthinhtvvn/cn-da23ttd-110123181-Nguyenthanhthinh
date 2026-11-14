<?php
require_once 'config/db.php';
require_once 'includes/event_image.php';
require_once 'includes/event_description.php';

$page_title = 'Trang chủ - Bảo tàng Văn hóa Dân tộc Khmer';

// 1. Chỉ gọi include header MỘT LẦN DUY NHẤT ở đây
include 'includes/header.php';

// Thống kê (kiểm tra bảng dữ liệu để không làm trang chủ lỗi khi database còn thiếu bảng cũ)
$countTables = static function (mysqli $database, string $table, string $fallbackTable = ''): int {
    $hasTable = $database->query("SHOW TABLES LIKE '" . $database->real_escape_string($table) . "'");
    if ($hasTable && $hasTable->num_rows > 0) {
        $countResult = $database->query("SELECT COUNT(*) AS total FROM `" . str_replace('`', '', $table) . '`');
        if (!$countResult) {
            error_log("Homepage table count failed for {$table}: " . $database->error);
            return 0;
        }
        $row = $countResult->fetch_assoc();
        return (int) ($row['total'] ?? 0);
    }
    if ($fallbackTable !== '') {
        $hasFallback = $database->query("SHOW TABLES LIKE '" . $database->real_escape_string($fallbackTable) . "'");
        if ($hasFallback && $hasFallback->num_rows > 0) {
            $countResult = $database->query("SELECT COUNT(*) AS total FROM `" . str_replace('`', '', $fallbackTable) . '`');
            if (!$countResult) {
                error_log("Homepage table count failed for {$fallbackTable}: " . $database->error);
                return 0;
            }
            $row = $countResult->fetch_assoc();
            return (int) ($row['total'] ?? 0);
        }
    }
    return 0;
};
$total_artifacts = $countTables($conn, 'artifacts');
$total_visits    = $countTables($conn, 'visits', 'visit_registrations');
$total_reviews   = $countTables($conn, 'reviews');
$total_events    = $countTables($conn, 'events');

// Sự kiện mới nhất
$events = $conn->query("SELECT * FROM events ORDER BY created_at DESC, id DESC LIMIT 4");
if (!$events) {
    $events = $conn->query('SELECT 1 AS empty_result WHERE 1 = 0');
}

// Hiện vật nổi bật
$artifacts = $conn->query("SELECT a.* FROM artifacts a ORDER BY a.created_at DESC LIMIT 6");
if (!$artifacts) {
    $artifacts = $conn->query('SELECT 1 AS empty_result WHERE 1 = 0');
}

// Đánh giá mới nhất
$reviews = $conn->query("SELECT r.*, u.full_name FROM reviews r 
                         LEFT JOIN users u ON r.user_id = u.id 
                         ORDER BY r.created_at DESC LIMIT 3");
if (!$reviews) {
    $reviews = $conn->query('SELECT 1 AS empty_result WHERE 1 = 0');
}

// Slides trang chủ
$mediaTable = $conn->query("SHOW COLUMNS FROM `media` LIKE 'table'");
if ($mediaTable && $mediaTable->num_rows > 0) {
    $slides = $conn->query("SELECT id, content, url FROM media WHERE `table` = 'hero_slides' ORDER BY id ASC");
} else {
    // Tương thích database cũ chưa có cột table: hiển thị các ảnh hiện có làm banner trang chủ.
    $slides = $conn->query("SELECT id, content, url FROM media ORDER BY id ASC LIMIT 5");
}
$slides_arr = [];
if ($slides && $slides->num_rows > 0) {
    while ($s = $slides->fetch_assoc()) {
        $slides_arr[] = $s;
    }
}

$museumIntroSettings = [
    'intro_eyebrow' => 'Bảo tàng Văn hóa Dân tộc Khmer tỉnh Vĩnh Long',
    'intro_title' => 'Giới thiệu về bảo tàng',
    'intro_lead' => 'Nơi lưu giữ và giới thiệu những giá trị văn hóa đặc sắc của đồng bào Khmer.',
    'intro_image' => 'images/slide1.jpg',
    'intro_image_caption' => 'Bảo tàng Văn hóa Dân tộc Khmer tỉnh Vĩnh Long',
    'intro_history_title' => 'Lịch sử hình thành và sứ mệnh',
    'intro_history_first' => 'Bảo tàng được hình thành với mong muốn gìn giữ, nghiên cứu và giới thiệu di sản văn hóa Khmer Nam Bộ...',
    'intro_history_second' => 'Thông qua các hiện vật và không gian trưng bày...',
];
$settingsTable = $conn->query("SHOW TABLES LIKE 'site_settings'");
if ($settingsTable && $settingsTable->num_rows > 0) {
    $introSettingsResult = $conn->query('SELECT setting_key, setting_value FROM site_settings');
    if ($introSettingsResult) {
        while ($setting = $introSettingsResult->fetch_assoc()) {
            if (array_key_exists($setting['setting_key'], $museumIntroSettings)) {
                $museumIntroSettings[$setting['setting_key']] = $setting['setting_value'];
            }
        }
    }
}

// Xử lý đường dẫn ảnh giới thiệu
$introImageSrc = $museumIntroSettings['intro_image'];
if (!str_starts_with($introImageSrc, 'http') && !str_starts_with($introImageSrc, 'images/') && !str_starts_with($introImageSrc, 'uploads/')) {
    $introImageSrc = 'images/' . ltrim($introImageSrc, '/');
}
?>

<style>
    /* Cố định chiều cao khung slide và hiển thị ảnh nền sắc nét trên mọi thiết bị */
    #heroCarousel .carousel-item {
        height: 520px;
        background-color: #1a1612;
    }
    #heroCarousel .carousel-bg {
        width: 100%;
        height: 100%;
        background-size: cover;
        background-position: center center;
        background-repeat: no-repeat;
        position: relative;
    }
    #heroCarousel .carousel-overlay {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.45);
    }
    .carousel-caption-custom {
        position: absolute;
        bottom: 25%;
        left: 10%;
        right: 10%;
        text-align: center;
        z-index: 5;
    }
    #heroCarousel .carousel-banner-image {
        object-fit: cover;
        object-position: center center;
    }
    @media (max-width: 991.98px) {
        #heroCarousel .carousel-item {
            height: 420px;
        }
    }
    @media (max-width: 575.98px) {
        #heroCarousel .carousel-item {
            height: 280px;
        }
        #heroCarousel .carousel-caption-custom h1 {
            font-size: 0.95rem !important;
        }
    }
</style>

<?php if (!empty($slides_arr)): ?>
<div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="4000">
    <div class="carousel-indicators">
        <?php foreach ($slides_arr as $i => $s): ?>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?= $i ?>"
            <?= $i === 0 ? 'class="active" aria-current="true"' : '' ?>></button>
        <?php endforeach; ?>
    </div>
    <div class="carousel-inner">
        <?php foreach ($slides_arr as $i => $slide): 
            $rawUrl = trim($slide['url']);
            $filename = basename($rawUrl);
            $imgUrl = 'images/' . $filename;
            if ($rawUrl !== '' && filter_var($rawUrl, FILTER_VALIDATE_URL)) {
                $imgUrl = $rawUrl;
            } else {
                if (is_file(__DIR__ . '/images/' . $filename)) {
                    $imgUrl = 'images/' . $filename;
                } elseif (is_file(__DIR__ . '/uploads/' . $filename)) {
                    $imgUrl = 'uploads/' . $filename;
                } elseif (is_file(__DIR__ . '/' . ltrim($rawUrl, '/'))) {
                    $imgUrl = ltrim($rawUrl, '/');
                }
            }
        ?>
        <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>" style="position: relative; background: #111;">
            <img src="<?= htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') ?>" class="carousel-banner-image d-block w-100 h-100" alt="Banner slide">
            <div class="carousel-overlay" style="position: absolute; top:0; left:0; right:0; bottom:0; background: rgba(0,0,0,0.45);"></div>
            <div class="carousel-caption-custom" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; width: 90%; z-index: 5;">
                <h1 class="fw-bold text-white mb-2 shadow-sm" style="font-size: 1rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.8);">
    			<?= htmlspecialchars($slide['content'] ?? '', ENT_QUOTES, 'UTF-8') ?>
				</h1>
                <?php if ($i === 0): ?>
     
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
    </button>
</div>
<?php endif; ?>

<section class="museum-intro py-5">
    <div class="container">
        <div class="museum-intro-content mx-auto">
            <div class="text-center mb-4">
                <span class="museum-intro-eyebrow text-muted fw-bold"><?= htmlspecialchars($museumIntroSettings['intro_eyebrow'], ENT_QUOTES, 'UTF-8') ?></span>
                <h2 class="museum-intro-title fw-bold mt-2"><?= htmlspecialchars($museumIntroSettings['intro_title'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="museum-intro-lead text-secondary"><?= nl2br(htmlspecialchars($museumIntroSettings['intro_lead'], ENT_QUOTES, 'UTF-8')) ?></p>
            </div>

            <figure class="museum-intro-figure text-center">
                <?php if (!empty($introImageSrc)): ?>
                    <img src="<?= htmlspecialchars($introImageSrc, ENT_QUOTES, 'UTF-8') ?>"
                         alt="<?= htmlspecialchars($museumIntroSettings['intro_image_caption'], ENT_QUOTES, 'UTF-8') ?>"
                         class="museum-intro-image img-fluid rounded shadow">
                <?php endif; ?>
                <figcaption class="museum-intro-caption text-muted mt-2 small"><?= htmlspecialchars($museumIntroSettings['intro_image_caption'], ENT_QUOTES, 'UTF-8') ?></figcaption>
            </figure>

            <div class="museum-intro-history mt-4">
                <h3><?= htmlspecialchars($museumIntroSettings['intro_history_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p><?= nl2br(htmlspecialchars($museumIntroSettings['intro_history_first'], ENT_QUOTES, 'UTF-8')) ?></p>
                <p class="mb-0"><?= nl2br(htmlspecialchars($museumIntroSettings['intro_history_second'], ENT_QUOTES, 'UTF-8')) ?></p>
                
				</div>
            </div>
        </div>
    </div>
</section>
<style>
    /* Tăng khoảng cách giữa các chữ và các từ cho thoáng, dễ đọc */
.carousel-caption p, .banner-caption p, .hero-text p {
    letter-spacing: 1.5px !important; /* Khoảng cách giữa các chữ cái */
    word-spacing: 3px !important;     /* Khoảng cách giữa các từ */
}
@media (max-width: 991.98px) {
    .navbar-collapse, .navbar-toggler + .collapse, .navbar-collapse.show {
        background-color: #ffffff !important;
        border-radius: 12px;
        padding: 15px;
        margin-top: 10px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }
    
    /* Đồng bộ chữ và icon bên trong menu mobile hiển thị màu tối rõ nét */
    .navbar-collapse .nav-link, 
    .navbar-collapse .dropdown-item,
    .navbar-collapse span,
    .navbar-collapse a {
        color: #333333 !important;
    }
    
    .navbar-collapse .nav-link:hover, 
    .navbar-collapse .dropdown-item:hover {
        color: #C8961E !important;
        background-color: rgba(0, 0, 0, 0.04);
        border-radius: 6px;
    }
}
    .museum-intro {
        padding: clamp(3rem, 7vw, 5.5rem) 0;
        background: #fff;
    }
    .museum-intro > .container {
        max-width: 100%;
        padding-right: clamp(1rem, 4vw, 4rem);
        padding-left: clamp(1rem, 4vw, 4rem);
    }
    .museum-intro-content {
        max-width: 100%;
    }
    .museum-intro-eyebrow {
        display: inline-block;
        margin-bottom: .6rem;
        color: #a07010;
        font-size: .82rem;
        font-weight: 700;
        letter-spacing: .12em;
        text-transform: uppercase;
    }
    .museum-intro-title {
        margin-bottom: 1rem;
        color: #C8961E;
        font-family: 'Playfair Display', serif;
        font-size: clamp(1.8rem, 4vw, 2.6rem);
        font-weight: 700;
    }
    .museum-intro-lead {
        width: min(100%, 1200px);
        margin: 0 auto;
        color: #514735;
        font-size: 1.1rem;
        line-height: 2.3; /* Đã tăng khoảng cách dòng cho thoáng */
        margin-bottom: 1.5rem; /* Thêm khoảng hở dưới đoạn văn */
        text-align: justify;
        text-indent: 0;
    }
    .museum-intro-lead::first-letter {
        float: left;
        margin: .08em .12em 0 0;
        color: #a07010;
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 1.7em;
        font-weight: 700;
        line-height: .78;
    }
    .museum-intro-figure {
        margin: 2rem auto 2.5rem;
        text-align: center;
    }
    .museum-intro-image {
        display: block;
        width: min(100%, 880px);
        height: clamp(240px, 38vw, 440px);
        margin: 0 auto;
        border-radius: 14px;
        object-fit: cover;
        object-position: center 56%;
        box-shadow: 0 12px 30px rgba(50, 37, 12, .18);
    }
    .museum-intro-caption {
        margin-top: .75rem;
        color: #756b58;
        font-size: .9rem;
        font-style: italic;
    }
    .museum-intro-history {
        width: min(100%, 1200px);
        margin: 0 auto;
        color: #514735;
        font-size: 1.02rem;
        line-height: 2.3; /* Đã tăng khoảng cách dòng cho phần lịch sử */
    }
    .museum-intro-history p {
        text-align: justify;
        margin-bottom: 1.5rem; /* Tăng khoảng cách giữa các đoạn văn */
    }
    .museum-intro-history h3 {
        margin-bottom: .8rem;
        color: #8a620d;
        font-family: 'Playfair Display', serif;
        font-size: 1.45rem;
        font-weight: 700;
    }
</style>

<!-- Sự kiện -->
<section class="bg-white py-5">
    <div class="container">
        <div class="d-flex justify-content-center align-items-center mb-4">
            <h3 class="text-primary-custom fw-bold text-center mb-0" style="font-family:'Playfair Display',serif;font-size:clamp(1.7rem,3vw,2.3rem);letter-spacing:.03em;border-left:0;padding-left:0;">
                Sự kiện nổi bật
            </h3>
        </div>
        <div class="row">
            <?php if ($events->num_rows > 0): ?>
                <?php while ($event = $events->fetch_assoc()): ?>
                <?php $event_image = event_image_src($event['image'] ?? ''); ?>
                <?php $event_description = event_description_without_time($event['description'] ?? null); ?>
                <div class="col-lg-3 col-md-6 mb-4">
                    <a href="#homeEventModal<?= (int) $event['id'] ?>" class="text-decoration-none text-dark"
                       data-bs-toggle="modal" role="button"
                       aria-label="Xem chi tiết <?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="card h-100 shadow-sm card-hover" style="border:1px solid #C8961E;background-color:#C8961E;color:#fff;">
                        <?php if ($event_image !== null): ?>
                            <img src="<?= htmlspecialchars($event_image, ENT_QUOTES, 'UTF-8') ?>" class="card-img-top" style="height:240px;object-fit:cover" alt="">
                        <?php else: ?>
                            <div class="bg-secondary d-flex align-items-center justify-content-center" style="height:240px">
                                <i class="fas fa-calendar-star fa-3x text-white opacity-50"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body d-flex align-items-center justify-content-center">
    					<h5 class="card-title text-white text-center m-0"><?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?></h5>
						</div>
                    </div>
                    </a>
                </div>
                <div class="modal fade" id="homeEventModal<?= (int) $event['id'] ?>" tabindex="-1"
                     aria-labelledby="homeEventModalTitle<?= (int) $event['id'] ?>" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content" style="background:#171613;color:#f8f5ed;border:1px solid rgba(200,150,30,.45);border-radius:14px;overflow:hidden;">
                            <div class="modal-header text-white justify-content-center position-relative" style="background:#C8961E;">
                                <h5 class="modal-title fw-bold text-center" id="homeEventModalTitle<?= (int) $event['id'] ?>">
                                    <?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?>
                                </h5>
                                <button type="button" class="btn-close btn-close-white position-absolute end-0 me-3" data-bs-dismiss="modal" aria-label="Đóng"></button>
                            </div>
                            <div class="modal-body p-4">
                                <?php if ($event_image !== null): ?>
                                    <div class="text-center mb-4">
                                        <img src="<?= htmlspecialchars($event_image, ENT_QUOTES, 'UTF-8') ?>"
                                             alt="<?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?>"
                                             style="max-width:100%;max-height:380px;object-fit:contain;border-radius:12px;">
                                    </div>
                                <?php endif; ?>
                                <div style="padding:1.25rem 1.5rem;border:1px solid rgba(200,150,30,.2);border-left:3px solid #C8961E;border-radius:8px;background:rgba(255,255,255,.035);color:#e5e1d8;line-height:1.9;">
                                    <?= nl2br(htmlspecialchars(trim($event['description'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>
                                </div>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-sm text-white" style="background:#C8961E;" data-bs-dismiss="modal">Đóng</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center text-muted py-4">
                    <i class="fas fa-calendar-times fa-2x mb-2"></i>
                    <p>Chưa có sự kiện nào.</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="text-center mt-2">
            <a href="events.php" class="btn btn-warning btn-sm">Xem tất cả</a>
        </div>
    </div>
</section>
<!-- Hiện vật nổi bật -->
<div class="bg-light py-5">
    <div class="container">
        <div class="d-flex justify-content-center align-items-center mb-4">
            <h3 class="text-primary-custom fw-bold text-center mb-0" style="font-family:'Playfair Display',serif;font-size:clamp(1.7rem,3vw,2.3rem);letter-spacing:.03em;border-left:0;padding-left:0;">
                Hiện vật nổi bật
            </h3>
        </div>
        <div class="row">
            <?php if ($artifacts->num_rows > 0): ?>
                <?php while ($artifact = $artifacts->fetch_assoc()): ?>
                <div class="col-md-4 col-6 mb-4">
                    <div class="card h-100 shadow-sm card-hover border-0" style="background-color:#C8961E;color:#fff;">
                        <?php if (!empty($artifact['image'])): ?>
                            <!-- Ảnh tự động điều chỉnh chiều cao: Điện thoại cao 170px, Máy tính cao 280px -->
                            <img src="uploads/artifacts/<?= htmlspecialchars($artifact['image']) ?>" class="card-img-top artifact-img-home" alt="">
                        <?php else: ?>
                            <div class="bg-secondary d-flex align-items-center justify-content-center artifact-img-home">
                                <i class="fas fa-image fa-2x text-white opacity-50"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body text-center py-2">
                            <h6 class="card-title mb-0 text-white" style="font-size: 0.82rem;"><?= htmlspecialchars($artifact['name']) ?></h6>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center text-muted py-4">
                    <i class="fas fa-box-open fa-2x mb-2"></i>
                    <p>Chưa có hiện vật nào.</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="text-center mt-2">
            <a href="artifacts.php" class="btn btn-warning btn-sm">Xem tất cả</a>
        </div>
    </div>
</div>

<!-- Đoạn CSS bổ sung giúp tùy biến giao diện mượt mà trên cả Điện thoại và Laptop -->
<style>
    /* Mặc định trên Điện thoại: chiều cao ảnh vừa vặn, không bị dài */
    .artifact-img-home {
        height: 170px;
        object-fit: cover;
    }
    
    /* Trên màn hình Máy tính (Laptop/PC từ 768px trở lên): tăng chiều cao ảnh lên đẹp mắt hơn */
    @media (min-width: 768px) {
        .artifact-img-home {
            height: 280px;
        }
    }
</style>

<!-- Đánh giá của khách tham quan -->
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-4 w-100">
        <!-- Tiêu đề bên trái -->
        <h3 class="text-primary-custom fw-bold mb-0" style="border-left:0;padding-left:0;">Đánh giá của khách tham quan</h3>
        
        <!-- Cụm hai nút nằm gọn gàng sát bên phải -->
        <div class="d-flex align-items-center gap-2 ms-auto flex-shrink-0">
            <a href="review.php" class="btn btn-warning btn-sm">Xem tất cả</a>
            <a href="review.php" class="btn btn-warning btn-sm">Viết đánh giá</a>
        </div>
    </div>
</div>
    <!-- Khung lớn bao trùm toàn bộ các đánh giá -->
<!-- Khung tổng gom các đánh giá sử dụng container để đồng bộ chiều ngang với phía trên -->
    <div class="card border-0 shadow-sm p-3 mb-4 container" style="border-radius: 16px; background-color: #ffffff;">
        <div class="row g-3">
            <?php if ($reviews->num_rows > 0): ?>
                <?php while ($review = $reviews->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="p-3 h-100 border rounded-3 bg-light d-flex flex-column justify-content-between">
                        <div>
                            <div class="star-rating mb-1" style="color: var(--gold); font-size: 0.85rem;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star<?= $i > $review['rating'] ? ' text-muted' : '' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            
                            <!-- Nội dung đánh giá thu gọn 2 dòng -->
                            <p class="card-text fst-italic text-muted mb-2" style="white-space: pre-line; font-size: 0.9rem; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis;">
                                "<?= nl2br(htmlspecialchars($review['content'])) ?>"
                            </p>
                        </div>
                        
                        <div class="d-flex align-items-center mt-2 pt-2 border-top">
                            <i class="fas fa-user-circle fa-xl text-secondary me-2"></i>
                            <div>
                                <strong class="small text-dark" style="font-size: 0.85rem;"><?= htmlspecialchars($review['full_name'] ?? 'Ẩn danh') ?></strong><br>
                                <small class="text-muted" style="font-size: 0.7rem;"><?= date('d/m/Y', strtotime($review['created_at'])) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center text-muted py-3">
                    <i class="fas fa-comment-slash fa-2x mb-2"></i>
                    <p class="mb-0">Chưa có đánh giá nào.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>