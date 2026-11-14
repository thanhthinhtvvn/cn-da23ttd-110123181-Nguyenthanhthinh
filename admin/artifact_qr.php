<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once 'admin_helpers.php';
check_admin_login();

$artifactId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$artifactId || $artifactId < 1) {
    http_response_code(400);
    exit('Mã hiện vật không hợp lệ.');
}

$artifactQuery = $conn->prepare('SELECT id, name FROM artifacts WHERE id = ?');
if (!$artifactQuery) {
    error_log('Could not prepare artifact QR lookup: ' . $conn->error);
    http_response_code(500);
    exit('Không thể tải thông tin hiện vật lúc này.');
}
$artifactQuery->bind_param('i', $artifactId);
if (!$artifactQuery->execute()) {
    error_log('Could not load artifact for QR: ' . $artifactQuery->error);
    $artifactQuery->close();
    http_response_code(500);
    exit('Không thể tải thông tin hiện vật lúc này.');
}
$artifact = $artifactQuery->get_result()->fetch_assoc();
$artifactQuery->close();

if (!$artifact) {
    http_response_code(404);
    exit('Không tìm thấy hiện vật.');
}

$host = $_SERVER['HTTP_HOST'] ?? '';
$scheme = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off'
    ? 'https'
    : 'http';
$origin = $scheme . '://' . $host;
if ($host === '' || filter_var($origin, FILTER_VALIDATE_URL) === false) {
    http_response_code(400);
    exit('Không xác định được địa chỉ website để tạo mã QR.');
}

$scriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/admin/artifact_qr.php'));
$publicDirectory = dirname(dirname($scriptPath));
$publicDirectory = $publicDirectory === '/' || $publicDirectory === '.'
    ? ''
    : '/' . trim($publicDirectory, '/');
$artifactUrl = $origin . $publicDirectory . '/artifact_detail.php?id=' . (int) $artifact['id'];
$qrServiceUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=360x360&format=png&data='
    . rawurlencode($artifactUrl);

admin_page_start('Mã QR hiện vật');
?>
<style>
    .qr-page { display: flex; min-height: 70vh; flex-direction: column; align-items: center; justify-content: center; gap: 1rem; }
    .qr-image { width: min(100%, 360px); height: auto; image-rendering: pixelated; }
    @media print {
        @page { margin: 0; }
        body, main {
            min-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            background: #fff !important;
            box-shadow: none !important;
        }
        .admin-header, .qr-print-button { display: none !important; }
        .qr-page { min-height: 100vh; }
        .qr-image { width: 300px; max-width: 100%; }
    }
</style>
<main class="container admin-main qr-page py-4">
    <img class="qr-image"
         src="<?= htmlspecialchars($qrServiceUrl, ENT_QUOTES, 'UTF-8') ?>"
         alt="Mã QR hiện vật">
    <button class="btn btn-success qr-print-button" type="button" onclick="window.print()">
        <i class="fas fa-print me-1"></i>In mã QR
    </button>
</main>
<?php admin_page_end(); ?>
