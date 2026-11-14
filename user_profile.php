<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'user_profile.php';
    header('Location: user_login.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];
$error = '';
$success = '';

if (!isset($_SESSION['user_profile_csrf'])) {
    $_SESSION['user_profile_csrf'] = bin2hex(random_bytes(32));
}

$userQuery = $conn->prepare('SELECT id, full_name, email, phone, password, created_at FROM users WHERE id = ? LIMIT 1');
$userQuery->bind_param('i', $userId);
$userQuery->execute();
$user = $userQuery->get_result()->fetch_assoc();
$userQuery->close();

if (!$user) {
    session_destroy();
    header('Location: user_login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';

    if (!hash_equals($_SESSION['user_profile_csrf'], $submittedToken)) {
        $error = 'Phiên thao tác không hợp lệ. Vui lòng tải lại trang.';
    } elseif ($action === 'update_profile') {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));

        if (mb_strlen($fullName, 'UTF-8') < 2) {
            $error = 'Họ tên phải có ít nhất 2 ký tự.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Vui lòng nhập địa chỉ email hợp lệ.';
        } elseif ($phone !== '' && !preg_match('/\A[0-9]{9,11}\z/', $phone)) {
            $error = 'Số điện thoại phải gồm 9 đến 11 chữ số.';
        } else {
            $emailQuery = $conn->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
            $emailQuery->bind_param('si', $email, $userId);
            $emailQuery->execute();
            $emailExists = $emailQuery->get_result()->fetch_assoc();
            $emailQuery->close();

            if ($emailExists) {
                $error = 'Email này đã được tài khoản khác sử dụng.';
            } else {
                $update = $conn->prepare('UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?');
                $update->bind_param('sssi', $fullName, $email, $phone, $userId);
                if ($update->execute()) {
                    $_SESSION['user_name'] = $fullName;
                    $_SESSION['user_email'] = $email;
                    $success = 'Đã cập nhật họ tên, email và số điện thoại.';
                    $user['full_name'] = $fullName;
                    $user['email'] = $email;
                    $user['phone'] = $phone;
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

        if (!password_verify($currentPassword, $user['password'])) {
            $error = 'Mật khẩu hiện tại không đúng.';
        } elseif (strlen($newPassword) < 6) {
            $error = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Mật khẩu xác nhận không khớp.';
        } elseif (password_verify($newPassword, $user['password'])) {
            $error = 'Mật khẩu mới phải khác mật khẩu hiện tại.';
        } else {
            $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $update = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
            $update->bind_param('si', $passwordHash, $userId);
            if ($update->execute()) {
                $user['password'] = $passwordHash;
                $success = 'Đã đổi mật khẩu thành công.';
            } else {
                $error = 'Không thể đổi mật khẩu. Vui lòng thử lại.';
            }
            $update->close();
        }
    }
}

$page_title = 'Thông tin chi tiết - Bảo Tàng Khmer Vĩnh Long';
include 'includes/header.php';
?>
<div class="container py-5">
    <div class="row justify-content-center g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h4 class="fw-bold text-primary-custom mb-4">
                        <i class="fas fa-user-circle me-2"></i>Thông tin chi tiết
                    </h4>

                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger py-2"><i class="fas fa-exclamation-circle me-1"></i><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                    <?php if ($success !== ''): ?>
                        <div class="alert alert-success py-2"><i class="fas fa-check-circle me-1"></i><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['user_profile_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Họ và tên</label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?>" required minlength="2" maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" required maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Số điện thoại</label>
                            <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" pattern="[0-9]{9,11}" placeholder="Ví dụ: 0987654321" maxlength="11">
                            <div class="form-text">Có thể để trống; nếu nhập thì dùng 9 đến 11 chữ số.</div>
                        </div>
                        <button type="submit" class="btn btn-primary-custom">
                            <i class="fas fa-save me-2"></i>Lưu thông tin
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-primary-custom mb-4">
                        <i class="fas fa-lock me-2"></i>Đổi mật khẩu
                    </h5>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['user_profile_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="change_password">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Mật khẩu hiện tại</label>
                            <input type="password" name="current_password" class="form-control" required minlength="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Mật khẩu mới</label>
                            <input type="password" name="new_password" class="form-control" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nhập lại mật khẩu mới</label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6">
                        </div>
                        <button type="submit" class="btn btn-primary-custom w-100">
                            <i class="fas fa-key me-2"></i>Cập nhật mật khẩu
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
