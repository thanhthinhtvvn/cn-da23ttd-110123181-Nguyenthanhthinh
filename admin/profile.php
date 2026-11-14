<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once 'admin_helpers.php';
check_admin_login();

$error = '';
$success = '';
$adminId = (int) $_SESSION['admin_id'];

$stmt = $conn->prepare('SELECT id, username, full_name, email FROM admins WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $adminId);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$admin) {
    session_destroy();
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $action = (string) ($_POST['action'] ?? 'update_profile');

    if ($action === 'update_profile') {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));

        if (mb_strlen($fullName, 'UTF-8') < 2) {
            $error = 'Họ tên phải có ít nhất 2 ký tự.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Vui lòng nhập địa chỉ email hợp lệ.';
        } else {
            $duplicate = $conn->prepare('SELECT id FROM admins WHERE email = ? AND id <> ? LIMIT 1');
            $duplicate->bind_param('si', $email, $adminId);
            $duplicate->execute();
            $exists = $duplicate->get_result()->fetch_assoc();
            $duplicate->close();

            if ($exists) {
                $error = 'Email này đang được một tài khoản quản trị khác sử dụng.';
            } else {
                $update = $conn->prepare('UPDATE admins SET full_name = ?, email = ? WHERE id = ?');
                $update->bind_param('ssi', $fullName, $email, $adminId);
                if ($update->execute()) {
                    $_SESSION['admin_name'] = $fullName;
                    $_SESSION['admin_email'] = $email;
                    $admin['full_name'] = $fullName;
                    $admin['email'] = $email;
                    $success = 'Đã cập nhật thông tin và email quản trị.';
                } else {
                    $error = 'Không thể cập nhật thông tin. Vui lòng thử lại.';
                }
                $update->close();
            }
        }
    } elseif ($action === 'change_password') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if (strlen($newPassword) < 6) {
            $error = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Mật khẩu xác nhận không khớp.';
        } else {
            $passwordStmt = $conn->prepare('SELECT password FROM admins WHERE id = ? LIMIT 1');
            $passwordStmt->bind_param('i', $adminId);
            $passwordStmt->execute();
            $passwordAdmin = $passwordStmt->get_result()->fetch_assoc();
            $passwordStmt->close();

            if (!$passwordAdmin || !password_verify($currentPassword, $passwordAdmin['password'])) {
                $error = 'Mật khẩu hiện tại không đúng.';
            } elseif (password_verify($newPassword, $passwordAdmin['password'])) {
                $error = 'Mật khẩu mới phải khác mật khẩu hiện tại.';
            } else {
                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updatePassword = $conn->prepare('UPDATE admins SET password = ? WHERE id = ?');
                $updatePassword->bind_param('si', $passwordHash, $adminId);
                if ($updatePassword->execute()) {
                    $success = 'Đổi mật khẩu thành công.';
                } else {
                    $error = 'Không thể cập nhật mật khẩu. Vui lòng thử lại.';
                }
                $updatePassword->close();
            }
        }
    }
}

admin_page_start('Thông tin tài khoản Admin');
?>
<main class="container admin-main py-4">
    <div class="row justify-content-center g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h1 class="h4 fw-bold mb-4 text-warning">
                        <i class="fas fa-user-shield me-2"></i>Thông tin tài khoản quản trị
                    </h1>

                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger py-2"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <?php if ($success !== ''): ?>
                        <div class="alert alert-success py-2"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tên đăng nhập</label>
                            <input class="form-control" value="<?= htmlspecialchars($admin['username'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                            <div class="form-text">Tên đăng nhập không thể thay đổi tại đây.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="full_name">Họ và tên</label>
                            <input class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($admin['full_name'], ENT_QUOTES, 'UTF-8') ?>" required minlength="2" maxlength="100">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="email">Email quản trị</label>
                            <input class="form-control" id="email" name="email" type="email" value="<?= htmlspecialchars($admin['email'], ENT_QUOTES, 'UTF-8') ?>" required maxlength="100">
                            <div class="form-text">Email này được dùng cho chức năng quên mật khẩu của quản trị viên.</div>
                        </div>
                        <button class="btn btn-warning" type="submit"><i class="fas fa-save me-2"></i>Lưu thông tin</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-4 text-danger"><i class="fas fa-lock me-2"></i>Đổi mật khẩu</h2>
                    <form method="post" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="change_password">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="current_password">Mật khẩu hiện tại</label>
                            <input class="form-control" id="current_password" name="current_password" type="password" required autocomplete="current-password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="new_password">Mật khẩu mới</label>
                            <input class="form-control" id="new_password" name="new_password" type="password" minlength="6" required autocomplete="new-password">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="confirm_password">Xác nhận mật khẩu mới</label>
                            <input class="form-control" id="confirm_password" name="confirm_password" type="password" minlength="6" required autocomplete="new-password">
                        </div>
                        <button class="btn btn-danger" type="submit"><i class="fas fa-key me-2"></i>Cập nhật mật khẩu</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
<?php admin_page_end(); ?>
