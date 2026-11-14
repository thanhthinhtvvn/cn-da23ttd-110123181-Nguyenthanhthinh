<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once 'admin_helpers.php';
check_admin_login();

$type = is_string($_GET['type'] ?? null) ? $_GET['type'] : 'events';
$sections = [
    'events' => ['title' => 'Quản lý sự kiện', 'table' => 'events', 'folder' => 'events'],
    'artifacts' => ['title' => 'Quản lý hiện vật', 'table' => 'artifacts', 'folder' => 'artifacts'],
    'reviews' => ['title' => 'Quản lý đánh giá', 'table' => 'reviews', 'folder' => null],
];
if (!isset($sections[$type])) {
    http_response_code(404);
    exit('Không tìm thấy mục quản lý.');
}
$section = $sections[$type];
$message = '';
$messageType = 'success';
$edit = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
    if (!$id || $id < 0) {
        $id = 0;
    }

    try {
        if ($type === 'reviews' && $action === 'delete' && $id > 0) {
            $stmt = $conn->prepare('DELETE FROM reviews WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
            }
            header('Location: manage_content.php?type=reviews&msg=deleted');
            exit();
        }

        if ($type !== 'reviews' && in_array($action, ['save', 'delete'], true)) {
            if ($action === 'delete' && $id > 0) {
                $stmt = $conn->prepare("DELETE FROM {$section['table']} WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $stmt->close();
                }
                header('Location: manage_content.php?type=' . $type . '&msg=deleted');
                exit();
            }

            if ($action === 'save') {
                $image = '';
                if ($id > 0) {
                    $imageStmt = $conn->prepare("SELECT image FROM {$section['table']} WHERE id = ?");
                    if ($imageStmt) {
                        $imageStmt->bind_param('i', $id);
                        $imageStmt->execute();
                        $existingImage = $imageStmt->get_result()->fetch_assoc();
                        $imageStmt->close();
                        if (!$existingImage) {
                            throw new RuntimeException('Không tìm thấy nội dung cần chỉnh sửa.');
                        }
                        $image = $existingImage['image'] ?? '';
                    }
                }

                if ($type === 'events') {
                    $title = trim($_POST['title'] ?? '');
                    $description = trim($_POST['description'] ?? '');
                    if ($title === '') {
                        throw new RuntimeException('Vui lòng nhập tên sự kiện.');
                    }

                    $uploadedImage = admin_upload_image('image_file', $section['folder']);
                    if ($uploadedImage !== null) {
                        $image = $uploadedImage;
                    }

                    if ($id > 0) {
                        $stmt = $conn->prepare('UPDATE events SET title = ?, description = ?, image = ? WHERE id = ?');
                        if ($stmt) {
                            $stmt->bind_param('sssi', $title, $description, $image, $id);
                        }
                    } else {
                        $adminId = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : 1;
                        $stmt = $conn->prepare('INSERT INTO events (title, description, image, admin_id) VALUES (?, ?, ?, ?)');
                        if ($stmt) {
                            $stmt->bind_param('sssi', $title, $description, $image, $adminId);
                        }
                    }
                } else {
                    $name = trim($_POST['name'] ?? '');
                    $description = is_string($_POST['description'] ?? null) ? trim($_POST['description']) : '';
                    if ($name === '') {
                        throw new RuntimeException('Vui lòng nhập tên hiện vật.');
                    }

                    $uploadedImage = admin_upload_image('image_file', $section['folder']);
                    if ($uploadedImage !== null) {
                        $image = $uploadedImage;
                    }

                    if ($id > 0) {
                        $stmt = $conn->prepare('UPDATE artifacts SET name = ?, description = ?, image = ? WHERE id = ?');
                        if ($stmt) {
                            $stmt->bind_param('sssi', $name, $description, $image, $id);
                        }
                    } else {
                        $adminId = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : 1;
                        $stmt = $conn->prepare('INSERT INTO artifacts (name, description, image, admin_id) VALUES (?, ?, ?, ?)');
                        if ($stmt) {
                            $stmt->bind_param('sssi', $name, $description, $image, $adminId);
                        }
                    }
                }

                if (isset($stmt) && $stmt) {
                    if (!$stmt->execute()) {
                        throw new RuntimeException('Không thể lưu dữ liệu: ' . $stmt->error);
                    }
                    $stmt->close();
                }
                header('Location: manage_content.php?type=' . $type . '&msg=saved');
                exit();
            }
        }
    } catch (RuntimeException $exception) {
        $message = $exception->getMessage();
        $messageType = 'danger';
    }
}

if ($type !== 'reviews' && isset($_GET['edit'])) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($editId) {
        $stmt = $conn->prepare("SELECT * FROM {$section['table']} WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $editId);
            $stmt->execute();
            $edit = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    }
}

if ($type === 'artifacts') {
    $artifactDescription = $_SERVER['REQUEST_METHOD'] === 'POST'
        && is_string($_POST['description'] ?? null)
        ? $_POST['description']
        : ($edit['description'] ?? '');
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'saved') {
        $message = 'Đã lưu nội dung thành công.';
    } elseif ($_GET['msg'] === 'deleted') {
        $message = 'Đã xóa nội dung thành công.';
    }
}

if ($type === 'events') {
    $rows = $conn->query('SELECT id, title, description, image FROM events ORDER BY created_at DESC, id DESC');
} elseif ($type === 'artifacts') {
    $rows = $conn->query('SELECT id, name, description, image FROM artifacts ORDER BY created_at DESC');
} else {
    $rows = $conn->query('SELECT r.id, r.content, r.rating, r.created_at, u.full_name, u.email FROM reviews r LEFT JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC');
}

admin_page_start($section['title']);
?>
<main class="container-fluid admin-main py-4">
    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($type !== 'reviews'): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span><?= $edit ? 'Chỉnh sửa sự kiện (ID: ' . (int)$edit['id'] . ')' : 'Thêm sự kiện mới' ?></span>
            <div class="d-flex flex-wrap gap-2">
                <?php if ($type === 'events'): ?>
                    <a href="slides.php?section=event_video" class="btn btn-sm btn-danger">
                        <i class="fas fa-video me-1"></i>Chỉnh sửa video sự kiện
                    </a>
                <?php endif; ?>
                <?php if ($edit): ?>
                    <a href="manage_content.php?type=events" class="btn btn-sm btn-outline-secondary">Thêm mới sự kiện</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">

                <?php if ($type === 'events'): ?>
                <div class="col-md-6">
                    <label class="form-label">Tên sự kiện</label>
                    <input class="form-control" name="title" maxlength="200" required value="<?= htmlspecialchars($edit['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <?php else: ?>
                <div class="col-md-6">
                    <label class="form-label">Tên hiện vật</label>
                    <input class="form-control" name="name" maxlength="200" required value="<?= htmlspecialchars($edit['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <?php endif; ?>

                <?php if ($type === 'artifacts'): ?>
                <div class="col-12">
                    <label class="form-label">Mô tả</label>
                    <textarea class="form-control" name="description" rows="4"><?= htmlspecialchars($artifactDescription, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <?php else: ?>
                <div class="col-12">
                    <label class="form-label">Mô tả</label>
                    <textarea class="form-control" name="description" rows="4"><?= htmlspecialchars($edit['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <?php endif; ?>
                <div class="col-md-8">
                    <label class="form-label">Ảnh <?= $edit ? '(để trống nếu giữ ảnh hiện tại)' : '(không bắt buộc)' ?></label>
                    <input class="form-control" type="file" name="image_file" accept="image/jpeg,image/png,image/gif,image/webp">
                    <div class="form-text">JPG, PNG, GIF hoặc WEBP, tối đa 5 MB.</div>
                    <?php if (!empty($edit['image'])): ?>
                        <div class="small text-muted mt-1">Ảnh hiện tại: <?= htmlspecialchars($edit['image'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-12">
                    <button class="btn btn-warning text-white" type="submit">
                        <i class="fas fa-save me-1"></i><?= $edit ? 'Lưu thay đổi' : 'Thêm mới' ?>
                    </button>
                    <?php if ($edit): ?><a class="btn btn-outline-secondary" href="manage_content.php?type=<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">Hủy</a><?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
    <p class="text-muted">Chọn xóa để gỡ một đánh giá khỏi website. Thao tác này không thể hoàn tác.</p>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <?php if ($type === 'reviews'): ?>
                            <th>Người đánh giá</th><th>Nội dung</th><th>Số sao</th><th>Ngày gửi</th><th></th>
                        <?php else: ?>
                            <th>Ảnh</th><th>Tên</th><th>Mô tả</th>
                            <?php if ($type === 'artifacts'): ?><th>Mã QR</th><?php endif; ?>
                            <th></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if ($rows): while ($row = $rows->fetch_assoc()): ?>
                    <tr>
                        <td><?= (int) $row['id'] ?></td>
                        <?php if ($type === 'reviews'): ?>
                            <td><?= htmlspecialchars($row['full_name'] ?? 'Ẩn danh', ENT_QUOTES, 'UTF-8') ?><div class="small text-muted"><?= htmlspecialchars($row['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></div></td>
                            <td style="min-width:240px;white-space:pre-wrap"><?= htmlspecialchars($row['content'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= str_repeat('★', (int) $row['rating']) ?></td>
                            <td><?= htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <form method="post" onsubmit="return confirm('Bạn có chắc muốn xóa đánh giá này?')">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                                </form>
                            </td>
                        <?php else: ?>
                            <td><?php if (!empty($row['image'])): ?><img src="../uploads/<?= htmlspecialchars($section['folder'], ENT_QUOTES, 'UTF-8') ?>/<?= rawurlencode(basename($row['image'])) ?>" alt=""><?php else: ?><span class="text-muted">Chưa có ảnh</span><?php endif; ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($row[$type === 'events' ? 'title' : 'name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="min-width:220px; white-space: pre-line;">
                                <?= nl2br(htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8')) ?>
                            </td>
                            <?php if ($type === 'artifacts'): ?>
                                <td>
                                    <a class="btn btn-sm btn-outline-success text-nowrap"
                                        href="artifact_qr.php?id=<?= (int) $row['id'] ?>">
                                        <i class="fas fa-qrcode me-1"></i> In QR
                                    </a>
                                </td>
                            <?php endif; ?>
                            <td class="text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="manage_content.php?type=<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>&edit=<?= (int) $row['id'] ?>">Sửa</a>
                                <form class="d-inline" method="post" onsubmit="return confirm('Bạn có chắc muốn xóa mục này?')">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<?php admin_page_end(); ?>