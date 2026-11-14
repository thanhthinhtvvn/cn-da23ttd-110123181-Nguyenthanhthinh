<?php
session_start();
require_once 'config/db.php';
require_once 'config/mail.php';
require_once 'phpmailer/PHPMailer.php';
require_once 'phpmailer/SMTP.php';
require_once 'phpmailer/Exception.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

header('Cache-Control: no-store');

$storageReady = $conn->query(
    "CREATE TABLE IF NOT EXISTS user_password_reset_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        code_hash VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        last_sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_user_password_reset_codes_user (user_id),
        CONSTRAINT fk_user_password_reset_codes_user
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$step = 1;
$error = '';
$message = '';
$completed = false;
$email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';

if (!$storageReady) {
    error_log('Could not initialize user password reset storage: ' . $conn->error);
    $error = 'Chức năng khôi phục mật khẩu hiện chưa khả dụng. Vui lòng liên hệ ban quản lý.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $storageReady) {
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';

    try {
        if ($action === 'request_code' || $action === 'resend_code') {
            $step = 2;
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $step = 1;
                $error = 'Vui lòng nhập địa chỉ email hợp lệ.';
            } else {
                $query = $conn->prepare('SELECT id, full_name, email FROM users WHERE email = ? LIMIT 1');
                $query->bind_param('s', $email);
                $query->execute();
                $user = $query->get_result()->fetch_assoc();
                $query->close();

                $message = 'Nếu email tồn tại trong hệ thống, mã xác nhận đã được gửi. Hãy kiểm tra cả thư mục thư rác.';

                if ($user) {
                    $userId = (int) $user['id'];
                    $cooldown = $conn->prepare(
                        'SELECT id FROM user_password_reset_codes
                         WHERE user_id = ? AND last_sent_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE) LIMIT 1'
                    );
                    $cooldown->bind_param('i', $userId);
                    $cooldown->execute();
                    $onCooldown = $cooldown->get_result()->num_rows > 0;
                    $cooldown->close();

                    if (!$onCooldown) {
                        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                        $codeHash = password_hash($code, PASSWORD_DEFAULT);
                        $save = $conn->prepare(
                            'INSERT INTO user_password_reset_codes
                             (user_id, code_hash, expires_at, attempts, created_at, last_sent_at)
                             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0, NOW(), NOW())
                             ON DUPLICATE KEY UPDATE
                             code_hash = VALUES(code_hash), expires_at = VALUES(expires_at), attempts = 0,
                             created_at = NOW(), last_sent_at = NOW()'
                        );
                        $save->bind_param('is', $userId, $codeHash);
                        $save->execute();
                        $save->close();

                        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                        $mail->isSMTP();
                        $mail->Host = MAIL_HOST;
                        $mail->SMTPAuth = true;
                        $mail->Username = MAIL_USERNAME;
                        $mail->Password = MAIL_PASSWORD;
                        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = MAIL_PORT;
                        $mail->CharSet = 'UTF-8';
                        $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
                        $mail->addAddress($user['email'], $user['full_name']);
                        $mail->Subject = 'Mã đặt lại mật khẩu - Bảo tàng Khmer';
                        $mail->Body = "Xin chào {$user['full_name']},\n\nMã đặt lại mật khẩu của bạn là: {$code}\nMã có hiệu lực trong 10 phút và chỉ sử dụng một lần.";
                        try {
                            $mail->send();
                        } catch (\PHPMailer\PHPMailer\Exception $mailError) {
                            $delete = $conn->prepare('DELETE FROM user_password_reset_codes WHERE user_id = ?');
                            $delete->bind_param('i', $userId);
                            $delete->execute();
                            $delete->close();
                            throw $mailError;
                        }
                    } elseif ($action === 'resend_code') {
                        $message = 'Vui lòng chờ một phút trước khi gửi lại mã.';
                    }
                }
                $_SESSION['password_reset_email'] = $email;
            }
        } elseif ($action === 'verify_code') {
            $step = 2;
            $code = is_string($_POST['verification_code'] ?? null) ? trim($_POST['verification_code']) : '';
            $resetEmail = is_string($_SESSION['password_reset_email'] ?? null) ? $_SESSION['password_reset_email'] : '';

            if (!preg_match('/\A[0-9]{6}\z/', $code) || !filter_var($resetEmail, FILTER_VALIDATE_EMAIL)) {
                $error = 'Vui lòng nhập mã xác nhận gồm 6 chữ số.';
            } else {
                $query = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                $query->bind_param('s', $resetEmail);
                $query->execute();
                $user = $query->get_result()->fetch_assoc();
                $query->close();

                if (!$user) {
                    $error = 'Phiên xác nhận không hợp lệ.';
                } else {
                    $userId = (int) $user['id'];
                    $query = $conn->prepare('SELECT code_hash, attempts FROM user_password_reset_codes WHERE user_id = ? AND expires_at > NOW()');
                    $query->bind_param('i', $userId);
                    $query->execute();
                    $reset = $query->get_result()->fetch_assoc();
                    $query->close();

                    if (!$reset || (int) $reset['attempts'] >= 5) {
                        $error = 'Mã đã hết hạn hoặc đã vượt quá 5 lần nhập. Vui lòng yêu cầu mã mới.';
                    } elseif (password_verify($code, $reset['code_hash'])) {
                        $_SESSION['password_reset_verified_user'] = $userId;
                        $step = 3;
                    } else {
                        $update = $conn->prepare('UPDATE user_password_reset_codes SET attempts = attempts + 1 WHERE user_id = ?');
                        $update->bind_param('i', $userId);
                        $update->execute();
                        $update->close();
                        $error = 'Mã xác nhận không đúng. Vui lòng kiểm tra lại.';
                    }
                }
            }
        } elseif ($action === 'reset_password') {
            $step = 3;
            $userId = (int) ($_SESSION['password_reset_verified_user'] ?? 0);
            $newPassword = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
            $confirmPassword = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';

            if ($userId <= 0) {
                $step = 1;
                $error = 'Phiên đặt lại mật khẩu đã hết hạn. Vui lòng yêu cầu mã mới.';
            } elseif (strlen($newPassword) < 6) {
                $error = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'Mật khẩu xác nhận không khớp.';
            } else {
                $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $conn->begin_transaction();
                $update = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
                $update->bind_param('si', $passwordHash, $userId);
                $update->execute();
                $update->close();
                $delete = $conn->prepare('DELETE FROM user_password_reset_codes WHERE user_id = ?');
                $delete->bind_param('i', $userId);
                $delete->execute();
                $delete->close();
                $conn->commit();

                unset($_SESSION['password_reset_email'], $_SESSION['password_reset_verified_user']);
                $completed = true;
            }
        }
    } catch (mysqli_sql_exception $dbError) {
        if ($conn->errno) {
            $conn->rollback();
        }
        error_log('User password reset failed: ' . $dbError->getMessage());
        $error = 'Không thể xử lý yêu cầu lúc này. Vui lòng thử lại sau.';
    } catch (\PHPMailer\PHPMailer\Exception $mailError) {
        error_log('User password reset email failed: ' . $mailError->getMessage());
        $error = 'Không gửi được mã xác nhận. Vui lòng thử lại sau.';
        $step = 1;
    }
}

if (isset($_SESSION['password_reset_verified_user']) && $step !== 3 && $completed === false) {
    $step = 3;
}
if (!isset($_SESSION['password_reset_verified_user']) && isset($_SESSION['password_reset_email']) && $step === 1 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = 2;
}

$page_title = 'Quên mật khẩu - Bảo Tàng Khmer Vĩnh Long';
include 'includes/header.php';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <?php if ($completed): ?>
                        <div class="text-center">
                            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                            <h5 class="fw-bold text-primary-custom">Đặt lại mật khẩu thành công</h5>
                            <p class="text-muted">Bạn có thể đăng nhập bằng mật khẩu mới.</p>
                            <a href="user_login.php" class="btn btn-primary-custom"><i class="fas fa-sign-in-alt me-2"></i>Đăng nhập</a>
                        </div>
                    <?php else: ?>
                        <h5 class="fw-bold mb-4 text-primary-custom text-center"><i class="fas fa-key me-2"></i>Quên mật khẩu</h5>
                        <?php if ($error): ?><div class="alert alert-danger py-2"><i class="fas fa-exclamation-circle me-1"></i><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

                        <?php if ($step === 1): ?>
                            <p class="text-muted small">Nhập email đã đăng ký để nhận mã xác nhận gồm 6 chữ số.</p>
                            <form method="POST">
                                <input type="hidden" name="action" value="request_code">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" name="email" class="form-control" placeholder="example@email.com" value="<?php echo htmlspecialchars($email); ?>" required autofocus>
                                    </div>
                                </div>
                                <button class="btn btn-primary-custom w-100"><i class="fas fa-paper-plane me-2"></i>Gửi mã xác nhận</button>
                            </form>
                        <?php elseif ($step === 2): ?>
                            <?php if ($message): ?><div class="alert alert-info py-2"><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                            <form method="POST" class="mb-3">
                                <input type="hidden" name="action" value="verify_code">
                                <input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['password_reset_email'] ?? ''); ?>">
                                <label class="form-label fw-semibold">Mã xác nhận 6 chữ số</label>
                                <input type="text" name="verification_code" class="form-control text-center fs-4 mb-3" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" required autofocus>
                                <button class="btn btn-primary-custom w-100">Xác nhận mã</button>
                            </form>
                            <form method="POST" class="text-center">
                                <input type="hidden" name="action" value="resend_code">
                                <input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['password_reset_email'] ?? ''); ?>">
                                <button class="btn btn-link text-primary-custom text-decoration-none small">Gửi lại mã</button>
                            </form>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="reset_password">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Mật khẩu mới</label>
                                    <input type="password" name="new_password" class="form-control" minlength="6" required autofocus>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nhập lại mật khẩu mới</label>
                                    <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                                </div>
                                <button class="btn btn-primary-custom w-100"><i class="fas fa-key me-2"></i>Đặt lại mật khẩu</button>
                            </form>
                        <?php endif; ?>
                        <hr>
                        <p class="text-center mb-0"><a href="user_login.php" class="text-primary-custom text-decoration-none small"><i class="fas fa-arrow-left me-1"></i>Quay lại đăng nhập</a></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
