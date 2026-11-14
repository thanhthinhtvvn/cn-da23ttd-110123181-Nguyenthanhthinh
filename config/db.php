<?php
// Cấu hình kết nối database
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'museum_khmer');

// Kết nối MySQLi
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Thiết lập charset UTF-8
$conn->set_charset("utf8mb4");

// Bổ sung cột phân loại banner nếu database hosting còn dùng cấu trúc cũ.
$mediaColumns = $conn->query("SHOW COLUMNS FROM `media` LIKE 'table'");
if ($mediaColumns && $mediaColumns->num_rows === 0) {
    $conn->query("ALTER TABLE `media` ADD COLUMN `table` varchar(100) NOT NULL DEFAULT 'hero_slides' AFTER `id`");
}

// Hàm thực thi query và trả về tất cả kết quả
function query($sql) {
    global $conn;
    $result = $conn->query($sql);
    if (!$result) {
        die("Query failed: " . $conn->error);
    }
    return $result;
}

// Hàm escape dữ liệu
function escape($str) {
    global $conn;
    return $conn->real_escape_string($str);
}
?>
