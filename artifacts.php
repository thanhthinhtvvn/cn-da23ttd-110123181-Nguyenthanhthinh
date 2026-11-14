<?php
require_once 'config/db.php';
$page_title = 'Hiện Vật - Bảo Tàng Văn Hóa Dân Tộc Khmer';
include 'includes/header.php';

$search = isset($_GET['search']) ? $conn->real_escape_string(trim($_GET['search'])) : '';
$sort = isset($_GET['sort']) && $_GET['sort'] === 'desc' ? 'DESC' : 'ASC';

$where = "WHERE 1=1";
if ($search !== '') $where .= " AND a.name LIKE '%$search%'";

// --- Xử lý phân trang ---
$limit = 12; // Số hiện vật hiển thị trên mỗi trang
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Đếm tổng số lượng hiện vật thỏa điều kiện
$countResult = $conn->query("SELECT COUNT(*) as total FROM artifacts a $where");
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// Truy vấn danh sách hiện vật có phân trang
$artifacts = $conn->query("SELECT a.* FROM artifacts a $where ORDER BY a.name $sort, a.id $sort LIMIT $limit OFFSET $offset");
?>

<style>
    /* Xóa sạch khoảng đệm thừa của phần chứa tiêu đề */
.card-header, .artifact-header, .card-body:first-child {
    padding-top: 4px !important;
    padding-bottom: 4px !important;
    margin-bottom: 0 !important;
}

/* Ép chữ nhỏ gọn và căn giữa tuyệt đối, không có khoảng cách thừa */
.card-title, .artifact-card-title {
    font-size: 0.8rem !important;
    text-align: center !important;
    margin: 2px 0 !important;
    padding: 0 !important;
}
  
    /* Căn giữa và thu nhỏ chữ tiêu đề hiện vật */
.card-title, .artifact-card-title, .admin-card .card-title {
    font-size: 0.85rem !important; /* Chỉnh kích thước chữ nhỏ gọn theo ý muốn */
    text-align: center !important;  /* Căn giữa chữ */
    font-weight: 600;
    width: 100%;
}


/* Cố định khung chứa ảnh có chiều cao chuẩn và tự động căn giữa */
.card-img-top, .artifact-card-image {
    width: 100%;
    height: 220px !important; /* Bạn có thể tăng giảm chiều cao này tùy ý */
    object-fit: cover !important; /* Giúp ảnh không bị méo, tự động cắt lấp đầy khung */
    background: #fdf6e3;
}

/* Ép phần nội dung chữ co giãn đều và đẩy nút "Xem chi tiết" xuống đáy thẳng hàng */
.card-body {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    flex-grow: 1;
}

/* Giới hạn tiêu đề hiện vật tối đa 2 dòng, tránh bị vỡ khung khi tên dài */
.card-title, .artifact-card-title {
    display: -webkit-box;
    -webkit-line-clamp: 2; /* Số dòng tối đa hiển thị */
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    min-height: 2.4em; /* Giữ chỗ cố định cho tên ngắn/dài */
}
    .artifact-detail-modal .modal-content {
        overflow: hidden;
        border: 1px solid rgba(200, 150, 30, 0.45);
        border-radius: 14px;
        background: #171613;
        color: #f8f5ed;
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.35);
    }

    .artifact-detail-modal .modal-header {
        position: relative;
        justify-content: center;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        background: linear-gradient(135deg, #c8961e, #a07010);
    }

    .artifact-detail-modal .modal-title {
        text-align: center;
    }

    .artifact-detail-modal .modal-header .btn-close {
        position: absolute;
        right: 1.5rem;
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

    <!-- Tìm kiếm & lọc kèm khung gợi ý -->
    <form method="GET" class="row g-2 mb-4 position-relative">
        <input type="hidden" name="sort" value="<?= strtolower($sort) ?>">
        <div class="col-md-10 position-relative">
            <div class="input-group">
                <span class="input-group-text bg-warning text-white border-0"><i class="fas fa-search"></i></span>
                <input type="text" id="search-input" name="search" class="form-control border-warning" placeholder="Tìm theo tên hiện vật..." value="<?= htmlspecialchars($search) ?>" autocomplete="off">
            </div>
            <!-- Khung hiển thị gợi ý tìm kiếm -->
            <div id="search-suggestions" class="position-absolute w-100 mt-1" style="z-index: 1050;"></div>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-warning text-white w-100">
                <i class="fas fa-search me-1"></i>Tìm kiếm
            </button>
        </div>
    </form>

    <!-- Số kết quả -->
    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
        <p class="text-muted mb-0">
            <i class="fas fa-cubes me-1 text-warning"></i>
            <strong><?= $totalRows ?></strong> hiện vật
        </p>
        <form method="GET" class="mb-0">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
            <select name="sort" class="form-select form-select-sm border-warning" aria-label="Sắp xếp hiện vật theo tên" onchange="this.form.submit()">
                <option value="asc" <?= $sort === 'ASC' ? 'selected' : '' ?>>Tên: A đến Z</option>
                <option value="desc" <?= $sort === 'DESC' ? 'selected' : '' ?>>Tên: Z đến A</option>
            </select>
        </form>
    </div>

    <!-- Danh sách hiện vật -->
    <div class="row">
        <?php if ($artifacts->num_rows > 0): ?>
            <?php while ($a = $artifacts->fetch_assoc()): ?>
            <div class="col-lg-3 col-md-4 col-6 mb-4">
                <div class="card h-100 border-0 shadow-sm card-hover">
                    <div class="card-body text-center py-2">
                        <!-- Tên hiện vật với kích thước chữ đã được thu nhỏ gọn gàng -->
                        <h6 class="card-title fw-bold text-center mb-0" style="font-size: 0.82rem;"><?= htmlspecialchars($a['name']) ?></h6>
                    </div>
                    <!-- Ảnh -->
                    <?php if (!empty($a['image']) && file_exists("uploads/artifacts/" . $a['image'])): ?>
                        <img src="uploads/artifacts/<?= htmlspecialchars($a['image']) ?>"
                             class="card-img-top" style="height:300px;object-fit:contain;background:#fdf6e3;" alt="">
                    <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center"
                             style="height:300px;background:linear-gradient(135deg,#f0e0a0,#e8c96a);">
                            <i class="fas fa-landmark fa-4x" style="color:#C8961E;opacity:0.5"></i>
                        </div>
                    <?php endif; ?>

                    <div class="card-body pt-3">
                        <button class="btn btn-sm btn-warning text-white w-100" type="button"
                                data-bs-toggle="modal" data-bs-target="#artifactModal<?= (int) $a['id'] ?>">
                            <i class="fas fa-eye me-1"></i>Xem chi tiết
                        </button>
                    </div>
                </div>
                <div class="modal fade" id="artifactModal<?= (int) $a['id'] ?>" tabindex="-1"
                     aria-labelledby="artifactModalTitle<?= (int) $a['id'] ?>" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable artifact-detail-modal">
                        <div class="modal-content border-0">
                            <div class="modal-header text-white">
                                <h5 class="modal-title fw-bold" id="artifactModalTitle<?= (int) $a['id'] ?>">
                                    <?= htmlspecialchars($a['name']) ?>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
                            </div>
                            <div class="modal-body">
                                <?php if (!empty($a['image']) && file_exists("uploads/artifacts/" . $a['image'])): ?>
                                    <div class="text-center mb-4">
                                        <img src="uploads/artifacts/<?= htmlspecialchars($a['image'], ENT_QUOTES, 'UTF-8') ?>"
                                             class="artifact-detail-image" alt="<?= htmlspecialchars($a['name'], ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                <?php endif; ?>
                                <div class="artifact-description">
                                    <?= nl2br(htmlspecialchars($a['description'] ?? 'Chưa có mô tả.', ENT_QUOTES, 'UTF-8')) ?>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-sm artifact-close-button" data-bs-dismiss="modal">Đóng</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
        <div class="col-12 text-center py-5">
            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
            <p class="text-muted">Không tìm thấy hiện vật nào.</p>
            <a href="artifacts.php" class="btn btn-warning text-white">Xem tất cả</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Thanh phân trang 1 2 3 -->
    <?php if ($totalPages > 1): ?>
    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center">
            <!-- Nút Trang trước -->
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link text-warning" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&sort=<?= strtolower($sort) ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>

            <!-- Các nút số trang -->
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                    <a class="page-link <?= ($page == $i) ? 'bg-warning border-warning text-white' : 'text-warning' ?>" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&sort=<?= strtolower($sort) ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>

            <!-- Nút Trang sau -->
            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                <a class="page-link text-warning" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&sort=<?= strtolower($sort) ?>" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>

</div>

<!-- Script xử lý gợi ý tìm kiếm nhanh -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById('search-input');
    const suggestionsBox = document.getElementById('search-suggestions');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            let query = this.value.trim();
            if (query.length > 0) {
                fetch('search_suggestions.php?q=' + encodeURIComponent(query))
                    .then(response => response.text())
                    .then(data => {
                        suggestionsBox.innerHTML = data;
                    });
            } else {
                suggestionsBox.innerHTML = '';
            }
        });

        // Ẩn bảng gợi ý khi click ra ngoài
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                suggestionsBox.innerHTML = '';
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>