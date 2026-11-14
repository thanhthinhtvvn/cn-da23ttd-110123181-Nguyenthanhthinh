<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once 'admin_helpers.php';
check_admin_login();

$event_count = $conn->query("SELECT COUNT(*) AS total FROM events")->fetch_assoc()['total'];
$artifact_count = $conn->query("SELECT COUNT(*) AS total FROM artifacts")->fetch_assoc()['total'];
$user_count = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
$review_count = $conn->query("SELECT COUNT(*) AS total FROM reviews")->fetch_assoc()['total'];
$registration_count = $conn->query("SELECT COUNT(*) AS total FROM visit_registrations")->fetch_assoc()['total'];
$events = $conn->query('SELECT id, title, description, image FROM events ORDER BY created_at DESC, id DESC LIMIT 4');
$artifacts = $conn->query('SELECT id, name, image FROM artifacts ORDER BY created_at DESC LIMIT 8');
$slides = $conn->query("SELECT content, url FROM media WHERE `table` = 'hero_slides' ORDER BY id ASC");
$museumIntro = [
    'intro_eyebrow' => 'Bảo tàng Văn hóa Dân tộc Khmer tỉnh Vĩnh Long',
    'intro_title' => 'Giới thiệu về bảo tàng',
    'intro_lead' => 'Nơi lưu giữ và giới thiệu những giá trị văn hóa đặc sắc của đồng bào Khmer.',
    'intro_image' => 'images/slide1.jpg',
    'intro_image_caption' => 'Bảo tàng Văn hóa Dân tộc Khmer tỉnh Vĩnh Long',
    'intro_history_title' => 'Lịch sử hình thành và sứ mệnh',
    'intro_history_first' => 'Bảo tàng được hình thành với mong muốn gìn giữ, nghiên cứu và giới thiệu di sản văn hóa Khmer Nam Bộ, để những giá trị truyền thống được trân trọng và tiếp nối qua các thế hệ.',
    'intro_history_second' => 'Thông qua các hiện vật và không gian trưng bày, khách tham quan có dịp tìm hiểu về đời sống, phong tục, tín ngưỡng, nghệ thuật và sự sáng tạo của cộng đồng Khmer. Bảo tàng cũng là điểm gặp gỡ giữa di sản với công chúng, góp phần lan tỏa tình yêu và ý thức gìn giữ bản sắc văn hóa dân tộc.',
];
$settingsTable = $conn->query("SHOW TABLES LIKE 'site_settings'");
if ($settingsTable && $settingsTable->num_rows > 0) {
    $introSettingsResult = $conn->query('SELECT setting_key, setting_value FROM site_settings');
    if ($introSettingsResult) {
        while ($setting = $introSettingsResult->fetch_assoc()) {
            if (array_key_exists($setting['setting_key'], $museumIntro)) {
                $museumIntro[$setting['setting_key']] = $setting['setting_value'];
            }
        }
    }
}

function dashboard_image_src(?string $image, string $folder): ?string
{
    if ($image === null || trim($image) === '') {
        return null;
    }

    $image = trim($image);
    if (filter_var($image, FILTER_VALIDATE_URL)) {
        $scheme = strtolower((string) parse_url($image, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true) ? $image : null;
    }

    $path = ltrim(str_replace('\\', '/', $image), '/');
    if (in_array('..', explode('/', $path), true)) {
        return null;
    }
    if (strpos($path, '/') === false) {
        $root = dirname(__DIR__);
        foreach (['uploads/' . $folder . '/' . $path, 'images/' . $path] as $candidate) {
            if (is_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate))) {
                $path = $candidate;
                break;
            }
        }
    }

    return '../' . implode('/', array_map('rawurlencode', explode('/', $path)));
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản trị - Bảo tàng Khmer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <style>
        :root { --gold: #c8961e; --gold-dark: #a07010; --page: #fff; --card: #121212; }
        body { min-height: 100vh; background: var(--page); color: var(--gold-dark); }
        .admin-nav { background: var(--gold); }
        .admin-nav .nav-link, .admin-nav .navbar-brand { color: #fff; }
        .admin-hero .carousel-bg { height: 400px; }
        .admin-hero .carousel-bg img { width: 100%; height: 100%; object-fit: cover; }
        .admin-hero .carousel-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.05) 40%, rgba(0,0,0,0.65) 100%);
        }
        .admin-hero .carousel-caption-custom {
            position: absolute;
            z-index: 2;
            top: 50%;
            transform: translateY(-50%);
            left: 0;
            right: 0;
            text-align: center;
            color: white;
            padding: 0 30px;
        }
        .admin-hero .carousel-caption-custom h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-shadow: 1px 2px 8px rgba(0,0,0,0.95);
            margin-bottom: 6px;
            color: #f5dfa0;
        }
        .admin-hero .carousel-caption-custom p {
            font-family: 'Cormorant Garamond', serif;
            font-style: italic;
            font-size: 0.92rem;
            letter-spacing: 0.06em;
            text-shadow: 1px 1px 4px rgba(0,0,0,0.9);
            margin-bottom: 0;
            color: #e8d08a;
        }
        .admin-hero { position: relative; }
        .hero-manage-actions {
            position: absolute;
            right: 1rem;
            bottom: 1rem;
            z-index: 10;
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: .5rem;
        }
        .hero-manage-actions .btn { box-shadow: 0 3px 12px rgba(0,0,0,.35); }
        .admin-hero-empty { min-height: 240px; background: linear-gradient(135deg, #6b5215, #c8961e); }
        .section-heading { color: var(--gold); border-left: 4px solid var(--gold); padding-left: 10px; }
        .content-section {
            position: relative;
            padding: clamp(1rem, 2.5vw, 2rem);
            border: 1px solid #a07010;
            border-radius: 18px;
            background: #c8961e;
            box-shadow: 0 14px 36px rgba(0,0,0,.24);
        }
        .content-section .section-heading { color: #fff; border-color: rgba(255,255,255,.8); }
        .content-section .btn-outline-warning {
            color: #fff;
            border-color: rgba(255,255,255,.75);
        }
        .content-section .btn-outline-warning:hover {
            color: #8a620d;
            background: #fff;
            border-color: #fff;
        }
        .empty-reviews {
            color: #fff !important;
            font-weight: 600;
            margin: 0;
        }
        .admin-site-footer {
            margin-top: 3rem;
            padding: 2.5rem 0 1rem;
            background: var(--gold);
            color: #fff;
            border-top: 3px solid var(--gold);
        }
        .admin-site-footer h5,
        .admin-site-footer h6 { color: #fff; }
        .admin-site-footer .footer-muted { color: #fff; }
        .admin-site-footer hr { border-color: rgba(255,255,255,.5); }
        .admin-site-footer a { color: #fff; }
        .admin-site-footer a:hover { color: #fff; }
        .admin-card { background: #fff; color: #212529; border: 0; border-radius: 8px; overflow: hidden; }
        .content-section .admin-card {
            border: 1px solid rgba(200,150,30,.2);
            border-radius: 12px;
            box-shadow: 0 8px 22px rgba(0,0,0,.24) !important;
            transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
        }
        .content-section .admin-card:hover {
            transform: translateY(-4px);
            border-color: rgba(240,192,64,.65);
            box-shadow: 0 14px 28px rgba(0,0,0,.34) !important;
        }
        .admin-card .card-body { background: #fff; }
        .admin-card .card-title { color: #212529; }
        .admin-card .card-text { color: #212529 !important; }
        .artifact-card-image { height: 280px !important; object-fit: contain !important; background: #fdf6e3; }
        .artifact-card-title { font-size: 1rem; }
        .content-section .admin-card .btn-outline-warning {
            color: var(--gold-dark);
            border-color: var(--gold-dark);
        }
        .content-section .admin-card .btn-outline-warning:hover {
            color: #fff;
            background: var(--gold-dark);
            border-color: var(--gold-dark);
        }
        .admin-card .card-img-top { height: 180px; object-fit: cover; }
        .intro-preview-surface {
            padding: clamp(1.25rem, 4vw, 3rem);
            border-radius: 12px;
            background: #fff;
            color: #514735;
        }
        .intro-preview-eyebrow {
            display: inline-block;
            margin-bottom: .6rem;
            color: #a07010;
            font-size: .82rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
        }
        .intro-preview-title {
            margin-bottom: 1rem;
            color: #c8961e;
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.8rem, 4vw, 2.6rem);
            font-weight: 700;
        }
        .intro-preview-lead {
            margin: 0 auto;
            color: #514735;
            font-size: 1.1rem;
            line-height: 1.8;
            text-align: justify;
            text-indent: 0;
        }
        .intro-preview-lead::first-letter {
            float: left;
            margin: .08em .12em 0 0;
            color: #a07010;
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 1.7em;
            font-weight: 700;
            line-height: .78;
        }
        .intro-preview-figure { margin: 2rem auto 2.5rem; text-align: center; }
        .intro-preview-image {
            display: block;
            width: min(100%, 880px);
            height: clamp(240px, 38vw, 440px);
            margin: 0 auto;
            border-radius: 14px;
            object-fit: cover;
            object-position: center 56%;
            box-shadow: 0 12px 30px rgba(50, 37, 12, .18);
        }
        .intro-preview-caption { margin-top: .75rem; color: #756b58; font-size: .9rem; font-style: italic; }
        .intro-preview-history {
            width: min(100%, 1200px);
            margin: 0 auto;
            color: #514735;
            font-size: 1.02rem;
            line-height: 1.9;
        }
        .intro-preview-history h4 {
            margin-bottom: .8rem;
            color: #8a620d;
            font-family: 'Playfair Display', serif;
            font-size: 1.45rem;
            font-weight: 700;
        }
        .intro-preview-history p { text-align: justify; }
        .intro-preview-edit { display: flex; justify-content: flex-end; margin-bottom: 1rem; }
        .stat-card { background: var(--gold); color: #fff; border: 0; border-left: 4px solid var(--gold-dark); }
        .stat-card .text-secondary { color: #fff !important; }
        .stat-card .btn-outline-warning { color: #fff; border-color: #fff; }
        .stat-card .btn-outline-warning:hover { color: var(--gold-dark); background: #fff; border-color: #fff; }
        .review-card { background: var(--card); color: #f8f5ed; border: 1px solid rgba(200,150,30,.25); }
        .btn-gold { background: var(--gold); border-color: var(--gold); color: #fff; }
        .btn-gold:hover { background: var(--gold-dark); border-color: var(--gold-dark); color: #fff; }
        @media (max-width: 576px) {
            .admin-hero .carousel-bg { height: 250px; }
            .admin-hero .carousel-caption-custom h1 { font-size: 1.2rem; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg admin-nav">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php" style="font-family:'Playfair Display',serif">Bảo tàng Văn hóa Dân tộc Khmer</a>
            <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Mở menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-shield me-1"></i>Admin
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-id-card me-2 text-warning"></i>Thông tin tài khoản</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="post" action="logout.php" class="m-0">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                    <button class="dropdown-item" type="submit"><i class="fas fa-sign-out-alt me-2 text-warning"></i>Đăng xuất</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
<?php if ($slides && $slides->num_rows > 0): ?>
    <div id="adminHero" class="carousel slide carousel-fade admin-hero" data-bs-ride="carousel" data-bs-interval="4000">
        <div class="carousel-inner">
            <?php 
            $slideIndex = 0; 
            while ($slide = $slides->fetch_assoc()): 
                $rawUrl = trim($slide['url']);
                $filename = basename($rawUrl);
                $slideImage = '../images/' . $filename;
                if ($rawUrl !== '' && filter_var($rawUrl, FILTER_VALIDATE_URL)) {
                    $slideImage = $rawUrl;
                } else {
                    if (is_file(dirname(__DIR__) . '/images/' . $filename)) {
                        $slideImage = '../images/' . $filename;
                    } elseif (is_file(dirname(__DIR__) . '/uploads/' . $filename)) {
                        $slideImage = '../uploads/' . $filename;
                    } elseif (is_file(dirname(__DIR__) . '/' . ltrim($rawUrl, '/'))) {
                        $slideImage = '../' . ltrim($rawUrl, '/');
                    }
                }
            ?>
            <div class="carousel-item <?= $slideIndex === 0 ? 'active' : '' ?>">
                <div class="carousel-bg position-relative">
                    <?php if ($slideImage): ?><img class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover" src="<?= htmlspecialchars($slideImage, ENT_QUOTES, 'UTF-8') ?>" alt=""><?php else: ?><div class="position-absolute top-0 start-0 w-100 h-100" style="background:linear-gradient(135deg,#6b5215,#c8961e)"></div><?php endif; ?>
                    <div class="carousel-overlay"></div>
                    <div class="carousel-caption-custom">
                        <h1 class="fw-bold text-white mb-2 shadow-sm" style="font-size: 1rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.8);">
    					<?= htmlspecialchars($slide['content'] ?? '', ENT_QUOTES, 'UTF-8') ?>
						</h1>
                        <?php if ($slideIndex === 0): ?>
                      
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php $slideIndex++; endwhile; ?>
        </div>
        <div class="hero-manage-actions">
            <a href="slides.php" class="btn btn-outline-light btn-sm">
                <i class="fas fa-images me-1"></i>Quản lý banner
            </a>
        </div>
    </div>
    <?php else: ?>
    <div class="admin-hero-empty d-flex flex-column justify-content-center align-items-center text-center text-white p-4">
        <h1 class="h3 fw-bold mb-3">Chưa có banner trang chủ</h1>
        <a href="slides.php" class="btn btn-light">
            <i class="fas fa-plus me-1"></i>Thêm banner
        </a>
    </div>
    <?php endif; ?>

    <main class="container py-4">
        <div class="row g-3 mb-5">
            <div class="col-6 col-xl-3"><div class="card stat-card h-100"><div class="card-body"><div class="small text-secondary">Sự kiện</div><div class="fs-3 fw-bold"><?= (int) $event_count ?></div></div></div></div>
            <div class="col-6 col-xl-3"><div class="card stat-card h-100"><div class="card-body"><div class="small text-secondary">Hiện vật</div><div class="fs-3 fw-bold"><?= (int) $artifact_count ?></div></div></div></div>
            <div class="col-6 col-xl-3">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="small text-secondary">Tài khoản người dùng</div>
                        <div class="fs-3 fw-bold"><?= (int) $user_count ?></div>
                        <div class="d-flex justify-content-end">
                            <a class="btn btn-sm btn-outline-warning mt-2 small" href="users.php">
                                <i class="fas fa-users me-1"></i>Quản lý người dùng
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="small text-secondary">Đánh giá</div>
                        <div class="fs-3 fw-bold"><?= (int) $review_count ?></div>
                        <div class="d-flex justify-content-end">
                            <a class="btn btn-sm btn-outline-warning mt-2 small" href="manage_content.php?type=reviews">
                                <i class="fas fa-star me-1"></i>Quản lý đánh giá
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-3 mb-5 bg-white border rounded shadow-sm">
            <div>
                <span class="fw-semibold">Đăng ký tham quan</span>
                <span class="text-muted ms-2"><?= (int) $registration_count ?> khách</span>
            </div>
            <a class="btn btn-sm btn-gold" href="visit_registrations.php">
                <i class="fas fa-clipboard-list me-1"></i>Quản lý thông tin khách đăng ký
            </a>
        </div>

        <section class="content-section mb-5">
            <div class="intro-preview-edit">
                <a href="museum_intro.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-pen-to-square me-1"></i>Chỉnh sửa giới thiệu
                </a>
            </div>
            <div class="intro-preview-surface">
                <div class="text-center mb-4">
                    <div class="intro-preview-eyebrow"><?= htmlspecialchars($museumIntro['intro_eyebrow'], ENT_QUOTES, 'UTF-8') ?></div>
                    <h3 class="intro-preview-title"><?= htmlspecialchars($museumIntro['intro_title'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p class="intro-preview-lead"><?= nl2br(htmlspecialchars($museumIntro['intro_lead'], ENT_QUOTES, 'UTF-8')) ?></p>
                </div>

                <figure class="intro-preview-figure">
                    <?php $introImage = dashboard_image_src($museumIntro['intro_image'], 'museum-intro'); ?>
                    <?php if ($introImage): ?>
                        <img class="intro-preview-image" src="<?= htmlspecialchars($introImage, ENT_QUOTES, 'UTF-8') ?>"
                             alt="<?= htmlspecialchars($museumIntro['intro_image_caption'], ENT_QUOTES, 'UTF-8') ?>">
                    <?php else: ?>
                        <div class="intro-preview-image d-flex align-items-center justify-content-center text-white"
                             style="background:linear-gradient(135deg,#6b5215,#c8961e)">
                            <i class="fas fa-landmark fa-3x opacity-50"></i>
                        </div>
                    <?php endif; ?>
                    <figcaption class="intro-preview-caption"><?= htmlspecialchars($museumIntro['intro_image_caption'], ENT_QUOTES, 'UTF-8') ?></figcaption>
                </figure>

                <div class="intro-preview-history">
                    <h4><?= htmlspecialchars($museumIntro['intro_history_title'], ENT_QUOTES, 'UTF-8') ?></h4>
                    <p><?= nl2br(htmlspecialchars($museumIntro['intro_history_first'], ENT_QUOTES, 'UTF-8')) ?></p>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($museumIntro['intro_history_second'], ENT_QUOTES, 'UTF-8')) ?></p>
                </div>
            </div>
          
        </section>

        <section class="content-section mb-5">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                <h2 class="h4 section-heading fw-bold mb-0">Sự kiện nổi bật</h2>
                <div class="d-flex gap-2">
                    <a href="manage_content.php?type=events" class="btn btn-outline-warning btn-sm">Quản lý tất cả</a>
                    <a href="manage_content.php?type=events" class="btn btn-gold btn-sm"><i class="fas fa-plus me-1"></i>Thêm sự kiện</a>
                </div>
            </div>
            <div class="row g-4">
                <?php while ($event = $events->fetch_assoc()): $eventImage = dashboard_image_src($event['image'] ?? null, 'events'); ?>
                <div class="col-lg-3 col-md-6">
                    <div class="card admin-card h-100 shadow-sm">
                        <?php if ($eventImage): ?><img class="card-img-top" src="<?= htmlspecialchars($eventImage, ENT_QUOTES, 'UTF-8') ?>" alt=""><?php else: ?><div class="d-flex align-items-center justify-content-center" style="height:180px;background:linear-gradient(135deg,#6b5215,#c8961e)"><i class="fas fa-calendar-star fa-3x text-white opacity-50"></i></div><?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h3 class="h5 card-title"><?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                           
                            <div class="d-flex gap-2">
                                <a href="manage_content.php?type=events&edit=<?= (int) $event['id'] ?>" class="btn btn-outline-warning btn-sm flex-grow-1"><i class="fas fa-edit me-1"></i>Sửa</a>
                                <form method="post" action="manage_content.php?type=events" onsubmit="return confirm('Bạn có chắc muốn xóa sự kiện này?')">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $event['id'] ?>">
                                    <button class="btn btn-outline-danger btn-sm" type="submit" aria-label="Xóa sự kiện"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </section>

        <section class="content-section mb-5">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                <h2 class="h4 section-heading fw-bold mb-0">Hiện vật nổi bật</h2>
                <div class="d-flex gap-2">
                    <a href="manage_content.php?type=artifacts" class="btn btn-outline-warning btn-sm">Quản lý tất cả</a>
                    <a href="manage_content.php?type=artifacts" class="btn btn-gold btn-sm"><i class="fas fa-plus me-1"></i>Thêm hiện vật</a>
                </div>
            </div>
            <div class="row g-4">
                <?php while ($artifact = $artifacts->fetch_assoc()): $artifactImage = dashboard_image_src($artifact['image'] ?? null, 'artifacts'); ?>
                <div class="col-lg-3 col-md-6">
                    <div class="card admin-card h-100 shadow-sm">
                        <?php if ($artifactImage): ?>
                            <img class="card-img-top artifact-card-image" src="<?= htmlspecialchars($artifactImage, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($artifact['name'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center" style="height:280px;background:linear-gradient(135deg,#6b5215,#c8961e)">
                                <i class="fas fa-image fa-3x text-white opacity-50"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column justify-content-between">
                            <h3 class="h6 card-title artifact-card-title text-center"><?= htmlspecialchars($artifact['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <div class="d-flex gap-2">
                                <a href="manage_content.php?type=artifacts&edit=<?= (int) $artifact['id'] ?>" class="btn btn-outline-warning btn-sm flex-grow-1"><i class="fas fa-edit me-1"></i>Sửa</a>
                                <form method="post" action="manage_content.php?type=artifacts" onsubmit="return confirm('Bạn có chắc muốn xóa hiện vật này?')">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $artifact['id'] ?>">
                                    <button class="btn btn-outline-danger btn-sm" type="submit" aria-label="Xóa hiện vật"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </section>

    </main>
<?php
$isAdminDashboard = true;
include '../includes/footer.php';
?>