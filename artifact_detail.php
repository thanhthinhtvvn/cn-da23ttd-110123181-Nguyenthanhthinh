<?php
require_once 'config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header("Location: artifacts.php"); exit; }

$result = $conn->query("SELECT a.* FROM artifacts a WHERE a.id = $id");

if ($result->num_rows === 0) { header("Location: artifacts.php"); exit; }
$a = $result->fetch_assoc();

$page_title = htmlspecialchars($a['name']) . ' - Bảo Tàng Khmer';
include 'includes/header.php';
?>

<style>
    .artifact-detail-modal .modal-content {
        overflow: hidden;
        border: 1px solid rgba(200, 150, 30, 0.45);
        border-radius: 14px;
        background: #171613;
        color: #f8f5ed;
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.35);
    }

    .artifact-detail-modal .modal-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        background: linear-gradient(135deg, #c8961e, #a07010);
    }

    .artifact-detail-modal .modal-body {
        padding: clamp(1rem, 4vw, 2rem);
    }

    .artifact-detail-modal .artifact-detail-image {
        max-width: 100%;
        max-height: 380px;
        border: 1px solid rgba(200, 150, 30, 0.3);
        border-radius: 12px;
        object-fit: contain;
        box-shadow: 0 0.75rem 1.75rem rgba(0, 0, 0, 0.3);
    }

    .artifact-detail-modal .artifact-description {
        padding: 1.25rem 1.5rem;
        border: 1px solid rgba(200, 150, 30, 0.2);
        border-left: 3px solid #c8961e;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.035);
        color: #e5e1d8;
        font-size: 1rem;
        line-height: 1.9;
        text-align: left;
        white-space: normal;
    }

    .artifact-detail-modal .modal-footer {
        padding: 0.75rem 1.5rem 1rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .artifact-detail-modal .artifact-close-button {
        border: 0;
        background: #c8961e;
        color: #fff;
    }

    .artifact-detail-modal .artifact-close-button:hover {
        background: #a07010;
        color: #fff;
    }
</style>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="artifact-detail-modal">
                <div class="modal-content border-0">
                    <div class="modal-header text-white justify-content-center">
                        <h2 class="modal-title fs-5 fw-bold mb-0 text-center">
                            <?= htmlspecialchars($a['name'], ENT_QUOTES, 'UTF-8') ?>
                        </h2>
                    </div>
                    <div class="modal-body">
                    <?php if (!empty($a['image']) && file_exists("uploads/artifacts/" . $a['image'])): ?>
                        <div class="text-center mb-4">
                            <img src="uploads/artifacts/<?= htmlspecialchars($a['image'], ENT_QUOTES, 'UTF-8') ?>"
                                 class="artifact-detail-image"
                                 alt="<?= htmlspecialchars($a['name'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    <?php endif; ?>
                        <div class="artifact-description">
                            <?= nl2br(htmlspecialchars($a['description'] ?? 'Chưa có mô tả.', ENT_QUOTES, 'UTF-8')) ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="artifacts.php" class="btn btn-sm artifact-close-button">
                            <i class="fas fa-arrow-left me-1"></i>Quay lại danh sách
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>