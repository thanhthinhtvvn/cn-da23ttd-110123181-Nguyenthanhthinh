<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once 'admin_helpers.php';
check_admin_login();

$message = '';
$messageType = 'success';
$edit = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
    $id = $id && $id > 0 ? $id : 0;

    if ($action === 'delete' && $id > 0) {
        $stmt = $conn->prepare("DELETE FROM media WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        echo '<script>window.location.href="slides.php?msg=deleted";</script>';
        exit();
    }

    if ($action === 'save') {
        $content = trim(is_string($_POST['content'] ?? null) ? $_POST['content'] : '');
        $section = in_array($_POST['section'] ?? '', ['hero_slides', 'event_slides', 'event_video'], true)
            ? $_POST['section']
            : 'hero_slides';
        
        $url = '';
        if ($id > 0) {
            $oldStmt = $conn->prepare("SELECT url, `table` FROM media WHERE id = ?");
            $oldStmt->bind_param('i', $id);
            $oldStmt->execute();
            $oldRes = $oldStmt->get_result()->fetch_assoc();
            $oldStmt->close();
            $url = $oldRes['url'] ?? '';
            if (!isset($_POST['section'])) {
                $section = $oldRes['table'] ?? 'hero_slides';
            }
        }

        // Video và ảnh được lưu theo đúng loại nội dung.
        if ($section === 'event_video') {
            $uploadedVideo = admin_upload_video('slide_file');
            if ($uploadedVideo !== null) {
                $url = $uploadedVideo;
            }
        } else {
            $uploadedImage = admin_upload_image('slide_file', '');
            if ($uploadedImage !== null) {
                $url = 'images/' . $uploadedImage;
            }
        }

        if ($url === '') {
            $message = $section === 'event_video'
                ? 'Vui lòng chọn tệp video hợp lệ.'
                : 'Vui lòng chọn tệp hình ảnh hợp lệ cho banner.';
            $messageType = 'danger';
            $edit = ['id' => $id, 'content' => $content, 'url' => $url];
        } elseif ($id > 0) {
            $stmt = $conn->prepare("UPDATE media SET `table` = ?, content = ?, url = ? WHERE id = ?");
            $stmt->bind_param('sssi', $section, $content, $url, $id);
            $stmt->execute();
            $stmt->close();
            
            echo '<script>window.location.href="slides.php?msg=saved";</script>';
            exit();
        } else {
            $stmt = $conn->prepare("INSERT INTO media (`table`, content, url) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $section, $content, $url);
            $stmt->execute();
            $stmt->close();
            
            echo '<script>window.location.href="slides.php?msg=added";</script>';
            exit();
        }
    } else {
        if ($action !== 'delete') {
            $message = 'Thao tác không hợp lệ.';
            $messageType = 'danger';
        }
    }
}

if ($message === '' && isset($_GET['msg'])) {
    $message = match ($_GET['msg']) {
        'added' => 'Đã thêm banner thành công.',
        'saved' => 'Đã cập nhật banner thành công.',
        'deleted' => 'Đã xóa banner.',
        default => '',
    };
    $messageType = $_GET['msg'] === 'deleted' ? 'warning' : 'success';
}

if ($edit === null && isset($_GET['edit'])) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($editId && $editId > 0) {
        $stmt = $conn->prepare("SELECT id, `table`, content, url FROM media WHERE id = ?");
        $stmt->bind_param('i', $editId);
        $stmt->execute();
        $edit = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$edit) {
            $message = 'Không tìm thấy banner cần sửa.';
            $messageType = 'danger';
        }
    }
}

$selectedSection = in_array($_GET['section'] ?? '', ['hero_slides', 'event_slides', 'event_video'], true)
    ? $_GET['section']
    : 'hero_slides';
$slidesStmt = $conn->prepare("SELECT id, `table`, content, url FROM media WHERE `table` = ? ORDER BY id ASC");
$slidesStmt->bind_param('s', $selectedSection);
$slidesStmt->execute();
$slides = $slidesStmt->get_result();

function slide_preview_src(string $url): string
{
    if (trim($url) === '') {
        return '';
    }
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true) ? $url : '';
    }

    $path = str_replace('\\', '/', trim($url));
    if ($path === '' || str_starts_with($path, '//')
        || preg_match('/^[a-z][a-z0-9+.-]*:/i', $path)
        || in_array('..', explode('/', $path), true)) {
        return '';
    }

    // Lọc tên file sạch từ đường dẫn
    $filename = basename($path);
    
    // Tự động kiểm tra file thực tế đang nằm ở thư mục nào trên hosting
    $root = dirname(__DIR__);
    $realPath = 'images/' . $filename;
    
    if (is_file($root . '/uploads/' . $filename)) {
        $realPath = 'uploads/' . $filename;
    } elseif (is_file($root . '/images/' . $filename)) {
        $realPath = 'images/' . $filename;
    } elseif (!str_starts_with($path, 'images/') && !str_starts_with($path, 'uploads/')) {
        $realPath = 'images/' . ltrim($path, '/');
    } else {
        $realPath = ltrim($path, '/');
    }

    return '../' . $realPath;
}
admin_page_start('Quản lý ảnh trang chủ');
?>
<style>
    .slides-main { max-width: 1440px; }
    .slides-card { border: 0; border-radius: 14px; overflow: hidden; }
    .slides-card .card-header { background: linear-gradient(135deg, #c8961e, #a07010); }
    .slide-preview { width: 100%; height: 190px; object-fit: cover; background: #211d16; }
    .slide-empty-preview { height: 190px; background: linear-gradient(135deg, #6b5215, #c8961e); }
</style>
<main class="container-fluid admin-main slides-main py-4">
    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Cột form thêm/sửa banner -->
        <div class="col-12 col-lg-4">
            <section class="card slides-card shadow-sm">
                <div class="card-header text-white fw-bold py-3">
                    <i class="fas fa-<?= $edit ? 'edit' : 'plus-circle' ?> me-2"></i><?= $edit ? 'Chỉnh sửa banner' : 'Thêm banner mới' ?>
                </div>
                <div class="card-body p-4">
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="slide-content">Tiêu đề / nội dung</label>
                            <input class="form-control" id="slide-content" name="content" maxlength="255"
                                placeholder="Ví dụ: Khám phá văn hóa Khmer"
                                value="<?= htmlspecialchars($edit['content'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <input type="hidden" id="slide-section" name="section" value="<?= htmlspecialchars($edit['table'] ?? $selectedSection, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" id="slide-file-label" for="slide-file">Chọn ảnh banner <span class="text-danger"><?= $edit ? '' : '*' ?></span></label>
                            <input class="form-control" type="file" id="slide-file" name="slide_file"
                                accept="image/jpeg,image/png,image/gif,image/webp" <?= $edit ? '' : 'required' ?>>
                            <div class="form-text" id="slide-file-help">JPG, PNG, GIF hoặc WEBP, tối đa 5 MB.</div>
                            
                            <?php $editPreview = slide_preview_src($edit['url'] ?? ''); ?>
                            <?php $editIsVideo = (($edit['table'] ?? '') === 'event_video'); ?>
                            <?php if ($editIsVideo): ?>
                            <video id="slide-video-preview" class="w-100 rounded mt-3" controls <?= $editPreview === '' ? 'hidden' : '' ?>>
                                <source src="<?= htmlspecialchars($editPreview, ENT_QUOTES, 'UTF-8') ?>">
                            </video>
                            <?php else: ?>
                            <img id="slide-preview" class="img-fluid rounded mt-3 <?= $editPreview === '' ? 'd-none' : '' ?>"
                                src="<?= htmlspecialchars($editPreview, ENT_QUOTES, 'UTF-8') ?>" alt="Xem trước banner">
                            <?php endif; ?>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-warning text-white" type="submit">
                                <i class="fas fa-save me-1"></i><?= $edit ? 'Lưu thay đổi' : 'Thêm banner' ?>
                            </button>
                            <?php if ($edit): ?><a href="slides.php" class="btn btn-outline-secondary">Hủy sửa</a><?php endif; ?>
                        </div>
                    </form>
                </div>
            </section>
            <div class="alert alert-light border mt-3 small">
                <i class="fas fa-lightbulb text-warning me-1"></i>
                Có thể chọn banner trang chủ hoặc banner dưới trang Sự kiện. Dùng ảnh ngang, khuyến nghị chiều rộng từ 1280 px trở lên.
            </div>
        </div>

        <!-- Cột hiển thị danh sách banner (Tương thích tốt trên mobile & laptop) -->
        <div class="col-12 col-lg-8">
            <ul class="nav nav-pills gap-2 mb-3">
                <li class="nav-item"><a class="nav-link <?= $selectedSection === 'hero_slides' ? 'active bg-warning text-white' : 'border border-warning text-warning' ?>" href="slides.php?section=hero_slides"><i class="fas fa-home me-1"></i>Banner trang chủ</a></li>
                <li class="nav-item"><a class="nav-link <?= $selectedSection === 'event_slides' ? 'active bg-warning text-white' : 'border border-warning text-warning' ?>" href="slides.php?section=event_slides"><i class="fas fa-calendar-alt me-1"></i>Banner sự kiện</a></li>
                <li class="nav-item"><a class="nav-link <?= $selectedSection === 'event_video' ? 'active bg-warning text-white' : 'border border-warning text-warning' ?>" href="slides.php?section=event_video"><i class="fas fa-video me-1"></i>Video sự kiện</a></li>
            </ul>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold mb-0">Danh sách banner</h2>
                <span class="badge text-bg-warning"><?= (int) $slides->num_rows ?> banner</span>
            </div>
            <?php if ($slides->num_rows > 0): ?>
            <div class="row g-3">
                <?php while ($slide = $slides->fetch_assoc()): $preview = slide_preview_src($slide['url']); ?>
                <div class="col-12 col-md-6 col-xl-6">
                    <article class="card slides-card shadow-sm h-100">
                        <?php if ($preview !== '' && $slide['table'] === 'event_video'): ?>
                            <video class="slide-preview" controls>
                                <source src="<?= htmlspecialchars($preview, ENT_QUOTES, 'UTF-8') ?>">
                            </video>
                        <?php elseif ($preview !== ''): ?>
                            <img class="slide-preview" src="<?= htmlspecialchars($preview, ENT_QUOTES, 'UTF-8') ?>" alt="">
                        <?php else: ?>
                            <div class="slide-empty-preview d-flex align-items-center justify-content-center text-white"><i class="fas fa-image fa-3x opacity-50"></i></div>
                        <?php endif; ?>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                <h3 class="h6 fw-bold mb-0"><?= htmlspecialchars($slide['content'] ?: 'Banner chưa có tiêu đề', ENT_QUOTES, 'UTF-8') ?></h3>
                                <span class="badge text-bg-light">#<?= (int) $slide['id'] ?></span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="slides.php?edit=<?= (int) $slide['id'] ?>" class="btn btn-sm btn-outline-warning">
                                    <i class="fas fa-edit me-1"></i>Sửa
                                </a>
                                <form method="post" onsubmit="return confirm('Bạn có chắc muốn xóa banner này?')">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $slide['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fas fa-trash me-1"></i>Xóa</button>
                                </form>
                            </div>
                        </div>
                    </article>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
                <div class="card slides-card shadow-sm"><div class="card-body text-center py-5 text-muted">
                    <i class="fas fa-images fa-3x mb-3 text-warning"></i>
                    <p class="mb-0">Chưa có banner nào. Hãy dùng biểu mẫu để thêm banner đầu tiên.</p>
                </div></div>
            <?php endif; ?>
        </div>
    </div>
</main>
<script>
(function () {
    const section = document.getElementById('slide-section');
    const file = document.getElementById('slide-file');
    const label = document.getElementById('slide-file-label');
    const help = document.getElementById('slide-file-help');

    function updateFileType() {
        const isVideo = section.value === 'event_video';
        label.innerHTML = (isVideo ? 'Chọn video sự kiện' : 'Chọn ảnh banner') + ' <span class="text-danger"><?= $edit ? '' : '*' ?></span>';
        file.accept = isVideo ? 'video/mp4,video/webm,video/ogg' : 'image/jpeg,image/png,image/gif,image/webp';
        help.textContent = isVideo ? 'MP4, WEBM hoặc OGV, tối đa 50 MB.' : 'JPG, PNG, GIF hoặc WEBP, tối đa 5 MB.';
        file.required = <?= $edit ? 'false' : 'true' ?>;
        file.value = '';
    }

    section.addEventListener('change', updateFileType);
    updateFileType();
})();
</script>
<?php admin_page_end(); ?>