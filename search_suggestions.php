<?php
require_once 'config/db.php';

$q = isset($_GET['q']) ? $conn->real_escape_string(trim($_GET['q'])) : '';
if ($q !== '') {
    $result = $conn->query("SELECT id, name, image FROM artifacts WHERE name LIKE '%$q%' LIMIT 5");
    if ($result && $result->num_rows > 0) {
        echo '<div class="list-group position-absolute w-100 shadow-lg bg-white border" style="z-index: 1000; max-height: 300px; overflow-y: auto;">';
        while ($row = $result->fetch_assoc()) {
            $img = (!empty($row['image']) && file_exists("uploads/artifacts/" . $row['image'])) 
                   ? "uploads/artifacts/" . htmlspecialchars($row['image']) 
                   : '';
            echo '<a href="artifacts.php?search=' . urlencode($row['name']) . '" class="list-group-item list-group-item-action d-flex align-items-center py-2 text-decoration-none">';
            if ($img) {
                echo '<img src="' . $img . '" style="width: 35px; height: 35px; object-fit: cover; border-radius: 4px;" class="me-2">';
            } else {
                echo '<div class="bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px; border-radius: 4px;"><i class="fas fa-landmark fa-xs"></i></div>';
            }
            echo '<span class="small fw-semibold text-dark">' . htmlspecialchars($row['name']) . '</span>';
            echo '</a>';
        }
        echo '</div>';
    }
}
?>