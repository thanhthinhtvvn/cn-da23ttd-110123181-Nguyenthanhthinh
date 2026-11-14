<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once 'admin_helpers.php';
check_admin_login();

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $registrationId = filter_var($_POST['registration_id'] ?? null, FILTER_VALIDATE_INT);

    if (!$registrationId || $registrationId < 1) {
        http_response_code(400);
        exit('Thông tin đăng ký không hợp lệ.');
    }

    $deleteRegistration = $conn->prepare('DELETE FROM visit_registrations WHERE id = ?');
    if (!$deleteRegistration) {
        error_log('Could not prepare visit registration deletion: ' . $conn->error);
        http_response_code(500);
        exit('Không thể xóa đăng ký lúc này. Vui lòng thử lại sau.');
    }
    $deleteRegistration->bind_param('i', $registrationId);
    if (!$deleteRegistration->execute()) {
        error_log('Could not delete visit registration: ' . $deleteRegistration->error);
        $deleteRegistration->close();
        http_response_code(500);
        exit('Không thể xóa đăng ký lúc này. Vui lòng thử lại sau.');
    }
    $deleted = $deleteRegistration->affected_rows > 0;
    $deleteRegistration->close();

    header('Location: visit_registrations.php?msg=' . ($deleted ? 'deleted' : 'not_found'));
    exit();
}

if (($_GET['msg'] ?? '') === 'deleted') {
    $message = 'Đã xóa đăng ký tham quan.';
} elseif (($_GET['msg'] ?? '') === 'not_found') {
    $message = 'Không tìm thấy đăng ký cần xóa.';
    $messageType = 'warning';
}

$search = trim((string) ($_GET['search'] ?? ''));
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
$page = $page && $page > 0 ? $page : 1;
$perPage = 25;
$searchPattern = '%' . $search . '%';

$countQuery = $conn->prepare(
    'SELECT COUNT(*) AS total
     FROM visit_registrations
     WHERE full_name LIKE ? OR email LIKE ? OR phone LIKE ?'
);
if (!$countQuery) {
    error_log('Could not prepare visit registration count: ' . $conn->error);
    http_response_code(500);
    exit('Không thể tải danh sách đăng ký lúc này.');
}
$countQuery->bind_param('sss', $searchPattern, $searchPattern, $searchPattern);
if (!$countQuery->execute()) {
    error_log('Could not count visit registrations: ' . $countQuery->error);
    $countQuery->close();
    http_response_code(500);
    exit('Không thể tải danh sách đăng ký lúc này.');
}
$totalRegistrations = (int) $countQuery->get_result()->fetch_assoc()['total'];
$countQuery->close();

$totalPages = max(1, (int) ceil($totalRegistrations / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$registrationsQuery = $conn->prepare(
    'SELECT id, full_name, email, phone, visit_date, visit_time, group_size,
            organization, notes, created_at
     FROM visit_registrations
     WHERE full_name LIKE ? OR email LIKE ? OR phone LIKE ?
     ORDER BY created_at DESC, id DESC
     LIMIT ? OFFSET ?'
);
if (!$registrationsQuery) {
    error_log('Could not prepare visit registration list: ' . $conn->error);
    http_response_code(500);
    exit('Không thể tải danh sách đăng ký lúc này.');
}
$registrationsQuery->bind_param(
    'sssii',
    $searchPattern,
    $searchPattern,
    $searchPattern,
    $perPage,
    $offset
);
if (!$registrationsQuery->execute()) {
    error_log('Could not load visit registrations: ' . $registrationsQuery->error);
    $registrationsQuery->close();
    http_response_code(500);
    exit('Không thể tải danh sách đăng ký lúc này.');
}
$registrations = $registrationsQuery->get_result();

admin_page_start('Quản lý đăng ký tham quan');
?>
<main class="container-fluid admin-main py-4">
    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="get" class="row g-2 align-items-center">
                <div class="col-sm">
                    <label class="visually-hidden" for="registration-search">Tìm đăng ký</label>
                    <input id="registration-search" class="form-control" type="search" name="search"
                           value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Tìm theo tên, email hoặc số điện thoại">
                </div>
                <div class="col-sm-auto">
                    <button class="btn btn-warning" type="submit"><i class="fas fa-search me-1"></i>Tìm kiếm</button>
                    <?php if ($search !== ''): ?>
                        <a class="btn btn-outline-secondary" href="visit_registrations.php">Xóa bộ lọc</a>
                    <?php endif; ?>
                </div>
            </form>
            <div class="text-muted small mt-3">Tổng số: <?= $totalRegistrations ?> đăng ký</div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Khách đăng ký</th>
                        <th>Liên hệ</th>
                        <th>Lịch tham quan</th>
                        <th>Số người</th>
                        <th>Tỉnh</th>
                        <th>Ghi chú</th>
                        <th>Ngày gửi</th>
                        <th>Xóa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($registrations->num_rows === 0): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">Chưa có đăng ký tham quan nào.</td></tr>
                    <?php else: ?>
                        <?php while ($registration = $registrations->fetch_assoc()): ?>
                            <tr>
                                <td><?= (int) $registration['id'] ?></td>
                                <td><?= htmlspecialchars($registration['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <div><?= htmlspecialchars($registration['email'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div><?= htmlspecialchars($registration['phone'], ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars(date('d/m/Y', strtotime($registration['visit_date'])), ENT_QUOTES, 'UTF-8') ?></div>
                                    <div><?= htmlspecialchars($registration['visit_time'], ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td><?= (int) $registration['group_size'] ?></td>
                                <td><?= htmlspecialchars($registration['organization'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-break"><?= nl2br(htmlspecialchars($registration['notes'] ?? '', ENT_QUOTES, 'UTF-8')) ?></td>
                                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($registration['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <form method="post" onsubmit="return confirm('Bạn có chắc muốn xóa đăng ký tham quan này?')">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="registration_id" value="<?= (int) $registration['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            <i class="fas fa-trash me-1"></i>Xóa
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="mt-4" aria-label="Phân trang đăng ký tham quan">
            <ul class="pagination justify-content-center">
                <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                    <li class="page-item <?= $pageNumber === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(['search' => $search, 'page' => $pageNumber]) ?>">
                            <?= $pageNumber ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</main>
<?php
$registrationsQuery->close();
admin_page_end();
?>
