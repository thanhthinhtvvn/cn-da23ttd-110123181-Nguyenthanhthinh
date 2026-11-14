<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once 'admin_helpers.php';
check_admin_login();

if (!$conn->query("CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")) {
    throw new RuntimeException('Không thể khởi tạo bảng cài đặt website: ' . $conn->error);
}

$defaults = [
    'intro_eyebrow' => 'Bảo tàng Văn hóa Dân tộc Khmer tỉnh Vĩnh Long',
    'intro_title' => 'Giới thiệu về bảo tàng',
    'intro_lead' => 'Nơi lưu giữ và giới thiệu những giá trị văn hóa đặc sắc của đồng bào Khmer.',
    'intro_image' => 'images/slide1.jpg',
    'intro_image_caption' => 'Bảo tàng Văn hóa Dân tộc Khmer tỉnh Vĩnh Long',
    'intro_history_title' => 'Lịch sử hình thành và sứ mệnh',
    'intro_history_first' => 'Bảo tàng được hình thành với mong muốn gìn giữ, nghiên cứu và giới thiệu di sản văn hóa Khmer Nam Bộ, để những giá trị truyền thống được trân trọng và tiếp nối qua các thế hệ.',
    'intro_history_second' => 'Thông qua các hiện vật và không gian trưng bày, khách tham quan có dịp tìm hiểu về đời sống, phong tục, tín ngưỡng, nghệ thuật và sự sáng tạo của cộng đồng Khmer. Bảo tàng cũng là điểm gặp gỡ giữa di sản với công chúng, góp phần lan tỏa tình yêu và ý thức gìn giữ bản sắc văn hóa dân tộc.',
];
$settings = $defaults;
$result = $conn->query('SELECT setting_key, setting_value FROM site_settings');
if (!$result) {
    throw new RuntimeException('Không thể tải nội dung giới thiệu: ' . $conn->error);
}
while ($row = $result->fetch_assoc()) {
    if (array_key_exists($row['setting_key'], $settings)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

$message = '';
$messageType = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $submitted = [];
    foreach ($defaults as $key => $default) {
        if ($key === 'intro_image') {
            continue;
        }
        $value = $_POST[$key] ?? '';
        $value = is_string($value) ? trim($value) : '';
        if (mb_strlen($value) > 5000) {
            $message = 'Nội dung mỗi trường không được vượt quá 5.000 ký tự.';
            $messageType = 'danger';
            break;
        }
        $submitted[$key] = $value;
    }

    if ($message === '') {
        $stmt = null;
        $transactionStarted = false;
        try {
            $uploadedImage = admin_upload_image('intro_image_file', 'museum-intro');
            $submitted['intro_image'] = $uploadedImage === null
                ? $settings['intro_image']
                : 'uploads/museum-intro/' . $uploadedImage;

            $stmt = $conn->prepare(
                'INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
            );
            if (!$stmt) {
                throw new RuntimeException('Không thể chuẩn bị lưu nội dung giới thiệu: ' . $conn->error);
            }
            if (!$conn->begin_transaction()) {
                throw new RuntimeException('Không thể bắt đầu lưu nội dung giới thiệu: ' . $conn->error);
            }
            $transactionStarted = true;
            foreach ($defaults as $key => $default) {
                $value = $submitted[$key];
                $stmt->bind_param('ss', $key, $value);
                if (!$stmt->execute()) {
                    throw new RuntimeException('Không thể lưu trường ' . $key . ': ' . $stmt->error);
                }
            }
            if (!$conn->commit()) {
                throw new RuntimeException('Không thể hoàn tất lưu nội dung giới thiệu: ' . $conn->error);
            }
            $transactionStarted = false;
            $stmt->close();
            header('Location: museum_intro.php?msg=saved');
            exit();
        } catch (RuntimeException $error) {
            if ($transactionStarted) {
                $conn->rollback();
            }
            if ($stmt instanceof mysqli_stmt) {
                $stmt->close();
            }
            error_log('Could not save museum introduction: ' . $error->getMessage());
            $message = $error->getMessage();
            $messageType = 'danger';
            $settings = array_merge($settings, $submitted);
        }
    } else {
        $settings = array_merge($settings, $submitted);
    }
}

if (($_GET['msg'] ?? '') === 'saved') {
    $message = 'Đã cập nhật phần giới thiệu bảo tàng.';
}

admin_page_start('Chỉnh sửa giới thiệu bảo tàng');
?>
<main class="container-fluid admin-main py-4">
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Nội dung giới thiệu bảo tàng</h1>
    </div>

    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold">Thông tin giới thiệu</div>
        <div class="card-body">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="intro_eyebrow">Dòng tên / nhãn nhỏ</label>
                    <input class="form-control" id="intro_eyebrow" name="intro_eyebrow" maxlength="255"
                        value="<?= htmlspecialchars($settings['intro_eyebrow'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="intro_title">Tiêu đề giới thiệu</label>
                    <input class="form-control" id="intro_title" name="intro_title" maxlength="255" required
                        value="<?= htmlspecialchars($settings['intro_title'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="intro_lead">Nội dung giới thiệu ngắn</label>
                    <textarea class="form-control" id="intro_lead" name="intro_lead" rows="3"><?= htmlspecialchars($settings['intro_lead'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="intro_image_file">Ảnh giới thiệu</label>
                    <input class="form-control" id="intro_image_file" name="intro_image_file" type="file" accept="image/jpeg,image/png,image/gif,image/webp">
                    <div class="form-text">JPG, PNG, GIF hoặc WEBP, tối đa 5 MB. Để trống nếu muốn giữ ảnh hiện tại.</div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="intro_image_caption">Chú thích ảnh</label>
                    <input class="form-control" id="intro_image_caption" name="intro_image_caption" maxlength="255"
                        value="<?= htmlspecialchars($settings['intro_image_caption'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12"><hr></div>
                <div class="col-12">
                    <label class="form-label" for="intro_history_title">Tiêu đề phần lịch sử</label>
                    <input class="form-control" id="intro_history_title" name="intro_history_title" maxlength="255" required
                        value="<?= htmlspecialchars($settings['intro_history_title'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="intro_history_first">Đoạn lịch sử thứ nhất</label>
                    <textarea class="form-control" id="intro_history_first" name="intro_history_first" rows="4"><?= htmlspecialchars($settings['intro_history_first'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="intro_history_second">Đoạn lịch sử thứ hai</label>
                    <textarea class="form-control" id="intro_history_second" name="intro_history_second" rows="5"><?= htmlspecialchars($settings['intro_history_second'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>
        </div>

          
        <div class="card-footer bg-white d-flex flex-wrap gap-2">
            <button class="btn btn-warning text-white" type="submit"><i class="fas fa-save me-1"></i>Lưu thay đổi</button>
        </div>
    </form>
</main>
<?php admin_page_end(); ?>
