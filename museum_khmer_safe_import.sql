-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: sql112.infinityfree.com
-- Thời gian đã tạo: Th10 01, 2026 lúc 01:20 AM
-- Phiên bản máy phục vụ: 11.4.13-MariaDB
-- Phiên bản PHP: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Phiên bản an toàn để nhập lại: dùng CREATE TABLE IF NOT EXISTS và INSERT IGNORE.
-- Không DROP, TRUNCATE, UPDATE dữ liệu cũ và không ghi đè giá trị có sẵn.
-- Các dòng dữ liệu trùng khóa/chỉ mục sẽ được bỏ qua, dữ liệu hiện có vẫn được giữ nguyên.


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `if0_43274156_museum_khmer`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admins`
--

CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `admins`
--

INSERT IGNORE INTO `admins` (`id`, `username`, `password`, `full_name`, `email`, `created_at`) VALUES
(1, 'admin', '$2y$10$tKEqrhZs3ecBE0nbSzn7i.8zo3P3ldRIK27R13Jl8U.QWwSo3wALG', 'Quản trị viên', 'admin@museum.com', '2026-10-21 03:05:50');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admin_password_resets`
--

CREATE TABLE IF NOT EXISTS `admin_password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `idx_admin_password_resets_admin_created` (`admin_id`,`created_at`),
  CONSTRAINT `fk_admin_password_resets_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admin_password_reset_codes`
--

CREATE TABLE IF NOT EXISTS `admin_password_reset_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `code_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_sent_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_password_reset_codes_admin` (`admin_id`),
  CONSTRAINT `fk_admin_password_reset_codes_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `admin_password_reset_codes`
--

INSERT IGNORE INTO `admin_password_reset_codes` (`id`, `admin_id`, `code_hash`, `expires_at`, `attempts`, `created_at`, `last_sent_at`) VALUES
(1, 1, '$2y$10$7HHRrGUdqv0eAzCoQFAjT.17q6AnVuJjHSrnm1VKMCqkDI51EFi/q', '2026-10-25 15:08:14', 0, '2026-10-25 14:58:14', '2026-10-25 14:58:14');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `artifacts`
--

CREATE TABLE IF NOT EXISTS `artifacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_artifact_admin` (`admin_id`),
  CONSTRAINT `fk_artifact_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `artifacts`
--

INSERT IGNORE INTO `artifacts` (`id`, `name`, `description`, `image`, `admin_id`, `created_at`) VALUES
(1, 'TƯỢNG NỮ THẦN UMA', 'Chất liệu: Đá sa thạch\r\nHiện vật phục chế', 'a7ea7dcf937afb1f6f384391c756a40b.jpg', 1, '2026-10-21 03:05:50'),
(2, 'YONI', 'Chất liệu: Đá sa thạch\r\nNiên đại: Thế kỷ 8\r\nNguồn gốc: Chùa Sâm Bua (Sampuriddhisak)', 'fa64f20caa4c9b9242d88e02e36775dc.jpg', 1, '2026-10-21 03:05:50'),
(3, 'TƯỢNG BỒ TÁT QUAN THẾ ÂM', '(Avalokitesvara)\r\nChất liệu: Đá sa thạch\r\nHiện vật phục chế', 'ba7b32848655854fb000fc13c136702f.jpg', 1, '2026-10-21 03:05:50'),
(4, 'CHÂN TƯỢNG', 'Chất liệu: Đá sa thạch\r\nNiên đại: Thế kỷ VIII\r\nNguồn gốc: Ấp Lưu Cừu II, xã Lưu Nghiệp Anh, huyện Trà Cú, tỉnh Trà Vinh', 'e39e04bb23feee30bd6f449d0cee8948.jpg', 1, '2026-10-21 03:05:50'),
(5, 'TƯỢNG THẦN VISHNU', 'Chất liệu: Đá sa thạch\r\nNiên đại: Thế kỷ 18\r\nNguồn gốc: Ấp Lưu Cừu II, xã Lưu Nghiệp Anh, huyện Trà Vinh, tỉnh Trà Vinh', 'eb1e17594dbd14bb07f5a354d361138b.jpg', 1, '2026-10-21 03:05:50'),
(6, 'SLẤT SÂYMA', 'Chất liệu: Đá\r\nNiên đại: Thế kỷ 19\r\nNguồn gốc: Chùa Ô Mịch (Ratanathibaram), ấp Ô Mịch, xã Châu Điền, huyện Cầu Kè, tỉnh Trà Vinh', '3dacd4ccb211d734857477e53549ec02.jpg', 1, '2026-10-21 03:05:50'),
(7, 'TƯỢNG CHIM THẦN GARUĐA', 'Chất liệu: Gỗ\r\nNiên đại: Giữa thế kỷ 19\r\nNguồn gốc: Chùa Âng (Angarrajapuri) Khóm 4, phường 8, tỉnh Trà Vinh', '15e1c4f57afc1628d5b251507045aa12.jpg', 1, '2026-10-21 03:05:50'),
(8, 'TƯỢNG PHẬT THÍCH CA', 'Chất liệu: Gỗ\r\nNiên đại: Thế kỷ 19', '5c22da6b83bd88e5a8f4a1e87259c0a0.jpg', 1, '2026-10-21 03:05:50'),
(9, 'TƯỢNG PHẬT THÍCH CA', 'Chất liệu: Gỗ\r\nNiên đại: Thế kỷ 20', 'a968f23c5354a3435e760cfe701700d7.jpg', 1, '2026-10-21 03:05:50'),
(10, 'TƯỢNG CHẰN', 'Chất liệu: Gỗ\r\nNiên đại: Đầu thế kỷ 20', 'f6ece570846a32ccf1755193afad47a2.jpg', 1, '2026-10-21 03:05:50'),
(11, 'TƯỢNG PHẬT CHÙA HANG', '(Kamponnigrodha) Khóm 4, thị trấn Châu Thành, tỉnh Trà Vinh \r\nBị hư hỏng do pháo địch bắn vào chùa năm 1968', 'fc7d722ec1ea810fd3ab939653643efd.jpg', 1, '2026-10-21 03:05:50'),
(12, 'TƯỢNG ĐẦU RỒNG', 'Chất liệu: Gỗ\r\nNiên đại: Đầu thế kỷ 19\r\nNguồn gốc: Chùa Kompong (Bodhisalaraja) Khóm 2, phường 1, tỉnh Trà Vinh', '3b0e32fcf7c74b1a73db5ffbfff6d296.jpg', 1, '2026-10-21 03:05:50'),
(13, 'ĐẦU RỒNG', 'Chất liệu: Gỗ\r\nNiên đại: Thế kỷ 19\r\nNguồn gốc: Chùa Kompong (Bodhisalaraja) Khóm 2, phường 1, tỉnh Trà Vinh', 'dd247f5a03658b1f092ab9dc9cba051b.jpg', 1, '2026-10-21 03:05:50'),
(14, 'TƯỢNG ĐẦU RỒNG', 'Chất liệu: Gỗ\r\nNiên đại: Đầu thế kỷ 20\r\nNguồn gốc: Chùa Compong (Bođhisalaraja) Khóm 2, phường 1, tỉnh Trà Vinh', '8279644ea65525404e34ff02bc719821.jpg', 1, '2026-10-21 03:05:50'),
(15, 'QUẠT THOMIA', 'Chất liệu: Gỗ\r\nNiên đại: Giữa thế kỷ 20', '2f69c91539343a533d28cd06daa67bd2.jpg', 1, '2026-10-21 03:05:50'),
(16, 'TÁC PHẨM ĐIÊU KHẮC 12 CON GIÁP', 'Niên đại: Thế kỷ 20', '3b3e0ece42bddc67206ed6f46a126f19.jpg', 1, '2026-10-21 03:05:50'),
(17, 'TƯỢNG TIÊN NỮ', 'Chất liệu: Gỗ\r\nNiên đại: Giữa thế kỷ 20', '6415020a8f47a37d9671bc19c2a0ebf6.jpg', 1, '2026-10-25 17:03:05'),
(18, 'THÁP ĐỰNG HÀI CỐT', 'Chất liệu: Gỗ\r\nNiên đại: Cuối thế kỷ 20\r\nNguồn gốc: Chùa Lò Gạch (Padumavonkampongthmo) ấp Ba Se A, xã Lương Hòa, huyện Châu Thành, tỉnh Trà Vinh (nay là xã Lương Hòa, tỉnh Vĩnh Long)', '883bae7bca85d26aaf3440283bcfa8ed.jpg', 1, '2026-10-25 17:44:41'),
(19, 'NÔM CÁC KÈO', 'Chất liệu: Tre\r\nNiên đại: Thế kỷ 20\r\nNguồn gốc: Của ông Thạch Niêng, ấp Sóc Chùa, xã Thuận Hòa, huyện Cầu Ngang, tỉnh Trà Vinh', 'f11dbdd6bff0b6c477a01c585100c508.jpg', 1, '2026-10-25 17:48:22'),
(20, 'THÚNG', 'Chất liệu: Tre\r\nNiên đại: Thế kỷ 20', 'a1a68155622bc256b880a02c7ddb4d36.jpg', 1, '2026-10-25 17:53:41'),
(21, 'GIỎ ĐỰNG CÁ', 'Chất liệu: Tre\r\nNiên đại: Cuối thế kỷ 20\r\nNguồn gốc: Khóm 4, tỉnh Trà Vinh', '2b74d314bded99a75e9d609d021ff530.jpg', 1, '2026-10-25 17:59:24'),
(22, 'CÁI TÁO', 'Chất liệu: Tre\r\nNiên đại: Thế kỷ 20', '50f8473c7418a1cb193e8a8bad462d81.jpg', 1, '2026-10-25 18:00:42'),
(23, 'CHÂN TRỤC SE CHỈ DỆT VẢI', 'Chất liệu: Gỗ\r\nNiên đại: Giữa thế kỷ 20', '1c3f55733af922194449a8abcc3b6ecb.jpg', 1, '2026-10-25 20:01:08'),
(24, 'Ô TRẦU', 'Chất liệu: Gỗ\r\nNiên đại: Giữa thế kỷ 20', '1cc3cf70d8cbe3c2022fb25d43ccf005.jpg', 1, '2026-10-25 20:02:35'),
(25, 'QUẢ TIẾP', 'Chất liệu: Gỗ \r\nNiên đại: Giữa thế kỷ 20', '4f0294b85a0cf9c68a8f38a5f56ba12c.jpg', 1, '2026-10-25 20:05:47'),
(26, 'RỖ TIÊN VÀ GIỎ TRE', 'Chất liệu: Gỗ \r\nNiên đại: Cuối thế kỷ 20', '855ac735f17a8d6a2360f72ffd23c2d3.jpg', 1, '2026-10-25 20:08:30'),
(27, 'TRỐNG ARAK', 'Chất liệu: Gỗ \r\nNiên đại: Cuối thế kỷ 20', 'b9d6576b864e926a57b2a58ae0a389b5.jpg', 1, '2026-10-25 20:10:26'),
(28, 'TRỐNG TAY', 'Chất liệu: Gỗ \r\nNiên đại: Cuối thế kỷ 20', 'db9633f3369fb8864c289f1683402d4a.jpg', 1, '2026-10-25 20:11:50'),
(29, 'KHUNG DỆT VẢI', 'Chất liệu: Gỗ\r\nNiên đại: Thế kỷ 20\r\nNguồn gốc: Cửa ông Thạch Sao, xã Đa Lộc, huyện Châu Thành, tỉnh Trà Vinh', 'ab6c16c4cffbee005a9d65792d1cff77.jpg', 1, '2026-10-25 20:14:58'),
(30, 'XÀ NENG', 'Chất liệu: Tre\r\nNiên đại: Thế kỷ 20', '027fb8449ac925b20912aa5c37c44a06.jpg', 1, '2026-10-25 20:16:53'),
(31, 'KẸP ĐẬP LÚA', 'Chất liệu: Tre \r\nNiên đại: Thế kỷ 20\r\nNguồn gốc: Của ông Kim Cân, xã Tân Hiệp, huyện Trà Cú, tỉnh Trà Vinh', '7e1e05e5f32ce3f86ef77260701d9fdb.jpg', 1, '2026-10-25 20:21:31'),
(32, 'THÚNG ĐONG LÚA', 'Chất liệu: Tre \r\nNiên đại: Thế kỷ 20 \r\nNguồn gốc: Của ông Sơn Chiên, ấp Sam-Bua, xã Lương Hòa, huyện Châu Thành, tỉnh Trà Vinh', 'b28e9a85df804e023ea64d99c19508d8.jpg', 1, '2026-10-25 20:23:56'),
(33, 'CỐI XAY LÚA', 'Chất liệu: Tre \r\nNiên đại: Giữa thế kỷ 20 \r\nNguồn gốc: Bà Thạch Thị Sa Hương, ấp Sóc Ruộng, xã Tân Hiệp, huyện Trà Cú, tỉnh Trà Vinh', '0cc4b96cc6cc006387d948999fe3a073.jpg', 1, '2026-10-25 20:31:07'),
(34, 'XÀ NGÔM', 'Chât liệu: Tre\r\nNiên đại: Cuối thế kỷ 20', '88ab57c8cc5826773d7f8bbd2f79065b.jpg', 1, '2026-10-25 20:37:09'),
(37, 'CÁI BÁT', 'Chùa Kompong Chrây (Kamponnigrodha) Khóm 4, thị trấn Châu Thành, tỉnh Trà Vinh bị hư hỏng do pháo địch bắn vào chùa năm 1968', 'cde02305e166daebb35693debb1a7da2.jpg', 1, '2026-10-26 12:03:27'),
(38, 'MẢNH BOM CHÙA KOMPONG CHRÂY', '(Kamponnigrodha) Khóm 4, thị trấn Châu Thành, tỉnh Trà Vinh chứng tích chiến tranh do Mỹ - Ngụy bắn pháo vào chùa năm 1968', '5baa4e124c98a8eaa8bfb63aaaac409b.jpg', 1, '2026-10-26 12:37:33'),
(39, 'ỐNG ĐIẾU', 'Của ông Maha Sơn Thông sử dụng giai đoạn 1975 - 1995', '09bf80a75723cb96caa19c335dd087af.jpg', 1, '2026-10-26 12:39:26'),
(40, 'MẶT NẠ CHẰN KRONG - RIEP TRONG NGHỆ THUẬT MÚA RÔBAM', 'Chất liệu: Giấy\r\nNiên đại: Cuối thế kỷ 20', '7c36f0e227f5afc325849e963fe1a392.jpg', 1, '2026-10-26 12:41:26'),
(41, 'CỐI GIẢ GẠO', 'Chất liệu: Gỗ\r\nNiên đại: Giữa thế kỷ 20', '548c43c1b47524f0499f6901d71a423c.jpg', 1, '2026-10-26 12:52:06'),
(42, 'CỐI GIẢ CỐM DẸP', 'Chất liệu: Gỗ\r\nNiên đại: Giữa thế kỷ 20', '6a314e3b3326d011e6df4bf9a586fa18.jpg', 1, '2026-10-26 12:53:45'),
(43, 'GÀU SÒNG', 'Chất liệu: Tre\r\nNiên đại: Thế kỷ 20', 'abc678899f24b45859d6946dab8efd2c.jpg', 1, '2026-10-26 12:58:29'),
(44, 'MẶT NẠ HUUMAN TRONG NGHỆ THUẬT MÚA RÔBAM', 'Chất liệu: Giấy\r\nNiên đại: Cuối thế kỷ 20', '676718f9fbf00839fbfdc7919e07c28d.jpg', 1, '2026-10-27 01:16:45'),
(45, 'MẶT NẠ CHẰN PHI-PHÉT TRONG NGHỆ THUẬT MÚA RÔBAM', 'Chất liệu: Giấy \r\nNiên đại: Cuối thế kỷ 20', 'a5848d3b3b56220cb95ac6a5ea1dc78c.jpg', 1, '2026-10-27 01:21:48'),
(46, 'MÃO NÀNG SÊ ĐA', 'Chất liệu: Giấy\r\nNiên đại: Cuối thế kỷ 20', '0092d7166c51b4e294fab5442ac84df4.jpg', 1, '2026-10-27 01:25:40'),
(47, 'QUẢ BÁNH', 'Chất liệu: Gỗ\r\nNiên đại: Đầu thế kỷ 20', '69dad1d96f1d575a3b7c468af57876cf.jpg', 1, '2026-10-27 01:28:10'),
(48, 'HỘP ĐỰNG THUỐC', 'Chất liệu: Gỗ\r\nNiên đại: Đầu thế kỷ 20', 'd17773961a02068c3ef36516f9427c26.jpg', 1, '2026-10-27 01:29:15'),
(49, 'TRỐNG SAM PHÔ', 'Chất liệu: Gỗ da\r\nNiên đại: Cuối thế kỷ 20', '0e2da080b928f70fbbf92c30ca5d0386.jpg', 1, '2026-10-27 01:44:55'),
(50, 'CHHƯNG', 'Chất liệu: Đồng\r\nNiên đại: Giữa thế kỷ 20', '1ee93ff485919f07074d455927a848f4.jpg', 1, '2026-10-27 01:52:17'),
(51, 'TRỐNG LỚN', 'Chất liệu: Da\r\nNiên đại: Cuối thế kỷ 20', '644632db228d753007f124fee2c1b853.jpg', 1, '2026-10-27 01:53:15'),
(52, 'RÔ NEAK THUNG', 'Chất liệu: Mộc\r\nNiên đại: Cuối thế kỷ 20', '7141dbee349b2599367b439c54df4cba.jpg', 1, '2026-10-27 01:55:27'),
(53, 'CÒNG NHỎ', 'Chất liệu: Đồng\r\nNiên đại: Cuối thế kỷ 20', '599c1aa4301e24b6cabdf4105c1836a5.jpg', 1, '2026-10-27 01:56:47'),
(54, 'MẶT NẠ TRONG NGHỆ THUẬT MÚA CHHAYDAM', 'Chất liệu: Giấy\r\nNiên đại: Giữa thế kỷ 20', '7f8c41d100140b888ac89986b25fe0cd.jpg', 1, '2026-10-27 01:58:34'),
(55, 'MẶT NẠ TRONG NGHỆ THUẬT MÚA CHHAYDAM', 'Chất liệu: Giấy\r\nNiên đại: Giữa thế kỷ 20', '3e9775e71991a61b71ef476aedaec08d.jpg', 1, '2026-10-27 01:59:44'),
(56, 'MẶT NẠ TRONG NGHỆ THUẬT MÚA CHHAYDAM', 'Chất liệu: Giấy\r\nNiên đại: Giữa thế kỷ 20', '7f6ecf6f74b32cfc5168a0cdd2dcd118.jpg', 1, '2026-10-27 02:00:55'),
(57, 'HUY CHƯƠNG VÀNG GIẢI ĐUA GHE NGO TOÀN QUỐC', 'Chất liệu: Kim loại\r\nNiên đại: Cuối thế kỷ 20\r\nNguồn gốc: Ông Sơn Ngọc Thanh Xuân, ấp Nguyệt Lãng A, xã Bình Phú, huyện Càng Long, tỉnh Trà Vinh', '9c1e9c23598c4e9691317753ee3e9831.jpg', 1, '2026-10-27 02:05:43'),
(58, 'BỘ SƯU TẬP MÃO TRONG NGHỆ THUẬT SÂN KHẤU  RÔBAM CỦA ĐOÀN NGHỆ THUẬT KHMER ÁNH BÌNH MINH', '', '36b0017a80d9eab07fd550477deffb92.jpg', 1, '2026-10-27 02:07:57'),
(59, 'TRỐNG CHHAYDAM', 'Chất liệu: Mộc\r\nNiên đại: Giữa thế kỷ 20', 'd8fa6c3704cead9f630643e32b1bb50d.jpg', 1, '2026-10-27 02:09:48'),
(60, 'MÃO SÊ ĐA TRONG NGHỆ THUẬT MÚA RÔBAM', 'Chất liệu: Giấy\r\nNiên đại: Cuối thế kỷ 20', '8cfb2ca6aed6f5e55f3a04ff91ddfd26.jpg', 1, '2026-10-27 02:10:44'),
(64, 'ĐỤC VŨM', 'Chất liệu: Sắt\r\nNiên đại: Giữa thế kỷ 20', 'af2946df3a2686147204b2956cad59cc.jpg', 1, '2026-10-27 17:22:21'),
(65, 'GIŨA', 'Chất liệu: Sắt\r\nNiên đại: Giữa thế kỷ 20', '5de5cbe7bb4d692ca5e8dd3e46d39bed.jpg', 1, '2026-10-27 17:29:48'),
(66, 'ĐỤC BẠT', 'Chất liệu: Sắt\r\nNiên đại: Giữa thế kỷ 20', '7ea88d64dd2ba2682b519d1e70443e24.jpg', 1, '2026-10-27 17:31:25'),
(67, 'BÀO', 'Chất liệu: Gỗ\r\nNiên đại: Giữa thế kỷ 20', 'd867fb58fe8791c187e69f4a7ff1d862.jpg', 1, '2026-10-27 17:33:15'),
(68, 'ĐỤC TÁCH', 'Chất liệu: Sắt\r\nNiên đại: Giữa thế kỷ 20', '642e53af04ba9e2534ebb3f0f07f1d57.jpg', 1, '2026-10-27 17:34:28'),
(69, 'VÒ', 'Chất liệu: Gỗ\r\nNiên đại: Giữa thế kỷ 20', '75fd2822d0770aeb39b3edf0b004c531.jpg', 1, '2026-10-27 17:37:44'),
(70, 'SA QUẠT LÚA', 'Chất liệu: Gỗ\r\nNiên đại: Thế kỷ 20\r\nNguyền gốc: Bà Huỳnh Thị Chay Dô, ấp Hương Phụ A, xã Đa Lộc, huyện Châu Thành, tỉnh Trà Vinh', 'e1f726f4c02056606bbbbb746c278229.jpg', 1, '2026-10-27 17:41:53'),
(71, 'TRANG PHỤC THIẾU NỮ TRONG LỄ HỘI', '', 'e0be7e48493fcf8a5f25911016366aa6.jpg', 1, '2026-10-27 17:45:05'),
(72, 'PHÒNG TRƯNG BÀY CHUYÊN ĐỀ 1', '', '5b45fd5d17768f96a94dfa3d7bc626ec.jpg', 1, '2026-10-27 17:47:06'),
(73, 'PHÒNG TRƯNG BÀY CHUYÊN ĐỀ 2', '', 'c0765f1fb33edda60fc6a0d94be20912.jpg', 1, '2026-10-27 17:47:43'),
(74, 'PHÒNG TRƯNG BÀY CHUYÊN ĐỀ 3', '', 'bdda509a8c90d3ee10d6dec849d5650b.jpg', 1, '2026-10-27 17:48:08'),
(75, 'PHÒNG TRƯNG BÀY CHUYÊN ĐỀ 4', '', '4a31d834f6caca72d23e73a3e3c9fffe.jpg', 1, '2026-10-27 17:48:51'),
(76, 'PHÒNG TRƯNG BÀY CHUYÊN ĐỀ 5', '', 'ec237a3314ab3dc0756cef81c2179e3a.jpg', 1, '2026-10-27 17:50:10'),
(77, 'PHÒNG TRƯNG BÀY CHUYÊN ĐỀ 6', '', '32f5bfd2731dc78933bdeef894719f12.jpg', 1, '2026-10-27 17:50:53'),
(78, 'MỎ XẢY', 'Chất liệu: Gỗ\r\nNiên đại: Thế kỷ 20', '376f500d7bbfdab57c5133a4b572d5cb.jpg', 1, '2026-10-27 17:53:29'),
(79, 'PHÒNG TRƯNG BÀY CHUYÊN ĐỀ 7', '', 'cddd7a0b978c9a7a2eac8b8ca9f6fc0a.jpg', 1, '2026-10-27 17:57:16'),
(80, 'PHÒNG TRƯNG BÀY CHUYÊN ĐỀ 8', '', '5f666b5f1551fa5ce7d65873355942c2.jpg', 1, '2026-10-27 18:03:14'),
(81, 'KHUNG DỆT CHIẾU', '', '039a181ee915b8511b83964ff6caf6c0.jpg', 1, '2026-10-30 06:02:55'),
(82, 'BẮC PHƯƠNG ĐA VẬN THIÊN VƯƠNG', 'Thần cai quản phương Bắc trong Tứ Đại Thiên Vương\r\nChất liệu: Kiếng\r\nNiên đại: Cuối thế kỉ 20', 'afb3f210efa8b7e8c7c8a8231708486c.jpg', 1, '2026-10-30 06:05:16'),
(83, 'TÂY PHƯƠNG QUẢNG MỤC THIÊN VƯƠNG', 'Thần cai quản phương Tây trong Tứ Đại Thiên Vương\r\nChất liệu: Kiếng\r\nNiên đại: Cuối thế kỉ 20', 'ad238357adb0a1eb50293374b2f991c9.jpg', 1, '2026-10-30 06:06:34'),
(84, 'NAM PHƯƠNG TĂNG TRƯỞNG THIÊN VƯƠNG', 'Thần cai quản phương Nam trong Tứ Đại Thiên Vương\r\nChất liệu: Kiếng\r\nNiên đại: Cuối thế kỉ 20', 'badd21b9a840f62bf9f96388129861ab.jpg', 1, '2026-10-30 06:07:43'),
(85, 'ĐÔNG PHƯƠNG TRÌ QUỐC THIÊN VƯƠNG', 'Thần cai quản phương Đông trong Tứ Đại Thiên Vương\r\nChất liệu: Kiếng\r\nNiên đại: Cuối thế kỉ 20', '331703fc605e71f67d4b6b532070c232.jpg', 1, '2026-10-30 06:09:05'),
(86, 'SẢN PHẨM CHIẾU CÀ HOM', 'Chất liệu: Sợi lát\r\nNiên đại: Cuối thế kỉ 20', 'd1284c0a8100857b9d58cca2e6990212.jpg', 1, '2026-10-30 06:10:32'),
(87, 'RÔ NEAK ĐEK', 'Chất liệu: Kim loại\r\nNiên đại: Cuối thế kỉ 20', '2644242abed7053a5286127f1b5d7e23.jpg', 1, '2026-10-30 06:12:35'),
(88, 'MÔ HÌNH CHÍNH ĐIỆN CHÙA KHMER', '', 'b12cef2a6cc64274b77e6c436d3c42c5.jpg', 1, '2026-10-30 06:14:41'),
(89, 'BỨC TRANH ĐỀN THỜ BÁC HỒ', 'Chất liệu: Lá buông\r\nNiên đại: Đầu thế kỷ XXI\r\nHọa sĩ: Thạch Lộc, thị trấn Châu Thành, huyện Châu Thành, tỉnh Trà Vinh', 'c65015a2471a6763825eaf317e9f43c4.jpg', 1, '2026-10-30 06:17:03'),
(90, 'BỨC TRANH CỔNG CHÀO TRÀ VINH', 'Chất liệu: Lá buông\r\nNiên đại: Đầu thế kỷ XXI\r\nHọa sĩ: Thạch Lộc, thị trấn Châu Thành, huyện Châu Thành, tỉnh Trà Vinh', 'bbbc6f93615b4768fcec51dc46e5254a.jpg', 1, '2026-10-30 06:18:04'),
(91, 'BỨC TRANH NGÔI CHÍNH ĐIỆN CỦA KHMER', 'Chất liệu: Lá buông\r\nNiên đại: Đầu thế kỷ XXI\r\nHọa sĩ: Thạch Lộc, thị trấn Châu Thành, huyện Châu Thành, tỉnh Trà Vinh', '3fca9d624941c48be823aca4c4940c3d.jpg', 1, '2026-10-30 06:19:46'),
(92, 'TÁC PHẨM SỰ TÍCH PHẬT THÍCH CA', 'Chất liệu: Kiếng\r\nNiên đại: Đầu thế kỉ 20', '9694f1dae9c7bb3c1ade4afadfe17ef2.jpg', 1, '2026-10-30 06:20:54'),
(93, 'TÁC PHẨM SỰ TÍCH PHẬT THÍCH CA', 'Chất liệu: Kiếng\r\nNiên đại: Đầu thế kỉ 20', '47c6c93ce5500d3efa5b90dbb4e7e427.jpg', 1, '2026-10-30 06:21:54'),
(94, 'MÓC NÓN', 'Chất liệu: Gỗ\r\nNiên đại: Giữa thế kỉ 20', '077e59928b43922848b6e8815259ae3d.jpg', 1, '2026-10-30 06:24:55'),
(95, 'ÁCH ĐƠN', 'Chất liệu: Gỗ\r\nNiên đại: Thế kỉ 20\r\nNguồn gốc: Của ông Trương Cheo, khóm 5, phường 8, thành phố Trà Vinh, tỉnh Trà Vinh', '29b4634a615bb8a60077580a82819574.jpg', 1, '2026-10-30 06:26:40'),
(96, 'SA QUẠT NƯỚC', 'Chất liệu: Gỗ\r\nNiên đại: Thế kỉ 20', '55bb137d2c3d48f5f1f693aeff9db841.jpg', 1, '2026-10-30 06:29:59'),
(97, 'BỘ SƯU TẬP HŨ GIA VỊ', 'Chất liệu: Gốm\r\nNiên đại: Thế kỉ 20', '4953dad5d8fda6597ac7ef58a75e9245.jpg', 1, '2026-10-30 06:31:51'),
(98, 'BỘ SƯU TẬP HŨ GIA VỊ', 'Chất liệu: Gốm\r\nNiên đại: Thế kỉ 20', 'ea50fc97c02fba8c36dffc6c2a710fae.jpg', 1, '2026-10-30 06:32:35'),
(99, 'LU ĐỰNG NƯỚC', 'Chất liệu: Gốm\r\nNiên đại: Cuối thế kỷ 20\r\nNguồn gốc: Ông Sơn Sang, phường 9, tỉnh Trà Vinh', '5137c8ec1297b0af9fccc4617e855b09.jpg', 1, '2026-10-30 06:33:58'),
(100, 'GIỎ ĐỰNG CÁ', 'Chất liệu: Tre\r\nNiên đại: Thế kỷ 20\r\nNguồn gốc: Ông Thạch Nghiêm, ấp Sâm Bua, xã Lương Hòa, huyện Châu Thành, tỉnh Trà Vinh', '1cb7bbc6f949df761747b18abc7ab04c.jpg', 1, '2026-10-30 06:37:29'),
(101, 'XUỒNG TRE', 'Chất liệu: Tre\r\nNiên đại: Thế kỷ XX\r\nNguồn gốc: Ông Trần Sanh, ấp Kim Hòa, xã Kim Hòa, huyện Cầu Ngang, tỉnh Trà Vinh', 'dc8706c9920b8e39e5c7c1b3d5b10ab2.jpg', 1, '2026-10-30 06:39:37'),
(102, 'ĐÀN CÁN DÀI', 'Chất liệu: Gỗ\r\nNiên đại: Giữa thế kỉ 20', 'ff51f91a465720cb01642f303298ca3a.jpg', 1, '2026-10-30 06:41:00'),
(103, 'ĐÀN CÒ BA DÂY', 'Chất liệu: Gỗ\r\nNiên đại: Giữa thế kỉ 20', '40cec63b4859fc3afbcbe2dcab6b3a56.jpg', 1, '2026-10-30 06:42:10');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `events`
--

CREATE TABLE IF NOT EXISTS `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_event_admin` (`admin_id`),
  CONSTRAINT `fk_event_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `events`
--

INSERT IGNORE INTO `events` (`id`, `title`, `description`, `image`, `admin_id`, `created_at`) VALUES
(1, 'Ok Om Bok', '- Thời gian: Vào rằm tháng 10 âm lịch hoặc đầu tháng 12 dương lịch.\r\n\r\n- Ý nghĩa: Tạ ơn thần mặt trăng đã bảo vệ mùa màng, đem lại thời tiết tốt và cây trái tươi tốt cho người nông dân.\r\n\r\n- Hoạt động: Đêm rằm, mọi người bày mâm cỗ gồm lễ vật chính là cốm dẹp, dừa tươi, khoai, chuối... Để hướng về mặt trăng làm lễ cúng, người lớn sẽ đút cốm dẹp cho trẻ em và hỏi ước nguyện của chúng. Lễ hội vô cùng rực rỡ với các hoạt động như đua ghe Ngo trên sông, thả đèn nước và thả đèn gió lên bầu trời.', 'images\\okombok.jpg', 1, '2026-10-21 03:05:50'),
(2, 'Lễ Chôl Vôsa', '- Thời gian: Với đồng bào Khmer Nam Bộ, Lễ Chôl Vôsa (Lễ Nhập hạ) vô cùng quan trọng diễn ra ngày 15 tháng 6 âm lịch hằng năm (thường kéo dài trong 3 tháng mùa mưa, từ rằm tháng 6 đến rằm tháng 9 âm lịch). Hằng năm bà con lại tổ chức Lễ Nhập hạ, cầu cho mưa thuận gió hòa, gia đình yên vui hạnh phúc.\r\n\r\n- Ý nghĩa: Đây là nghi lễ tôn giáo quan trọng trong Phật giáo Nam tông của người Khmer, đánh dấu thời điểm bắt đầu mùa an cư kiết hạ của các vị chư tăng (sư sãi). Trong 3 tháng mùa mưa, các vị sư sẽ tập trung tại chùa để tịnh tu, trau dồi kinh luật, không đi lại hay ngủ nghỉ ở bên ngoài nhằm tránh dẫm đạp lên côn trùng và lúa mạ của người dân. Đây cũng là dịp để đồng bào Phật tử dâng cúng vật phẩm, nến lớn, thực phẩm để hỗ trợ các sư.\r\n\r\n- Hoạt động: Lễ Nhập hạ gồm 2 ngày chính. Ngày thứ nhất diễn ra vào buổi chiều, bà con sẽ đem lễ vật đến chùa để làm lễ. Người ta thắp những cây nến rất lớn trong chùa, những ngôi chùa trở nên lung linh huyền ảo. Ngày thứ hai, bà con đem cơm, nước, gạo đến chùa dâng lên sư sãi để cầu siêu cho người quá cố và cầu bình an, hạnh phúc cho phum sóc.', 'images/cholvosa.jpg', 1, '2026-10-21 03:05:50'),
(3, 'Chôl Chnăm Thmây', '- Thời gian: Khoảng giữa tháng 4 dương lịch hằng năm thường diễn ra trong 3 ngày.\r\n\r\n- Ý nghĩa: Là tết cổ truyền lớn nhất của người Khmer, mừng năm mới theo lịch cổ truyền, tưởng nhớ tổ tiên và đánh dấu thời điểm chuyển giao giữa mùa nắng, mùa mưa, cầu cho mưa thuận gió hòa và đón chào năm mới may mắn.\r\n\r\n- Hoạt động: Lễ tắm Phật (dùng nước thơm vẩy lên tượng Phật), đắp núi cát tại chùa để tích phước và cầu bình an, tụng kinh chúc phúc và các trò chơi dân gian tại chùa và các phum sóc.', 'cholchnamthmay.jpg', 1, '2026-10-21 03:05:50'),
(4, 'Sen Đôn Ta', '- Thời gian: Thường diễn ra trong tháng 8 hoặc tháng 9 âm lịch kéo dài khoảng 15 ngày.\r\n\r\n- Ý nghĩa: Là lễ hội mang đậm đạo lý \"uống nước nhớ nguồn\", tưởng nhớ công ơn cha mẹ, tổ tiên và hương linh những người đã khuất (tương tự như lễ Vu Lan của người Kinh).\r\n\r\n- Hoạt động: Con cháu quây quần chuẩn bị mâm cơm cúng gia tiên tại nhà, sau đó mang lễ vật đặc biệt là bánh gừng, bánh tét lên chùa dâng sãi để cầu siêu cho người thân quá cố. Ở một số nơi như An Giang, lễ hội này còn gắn liền với hội đua bò Bảy Núi rất náo nhiệt.', '8267e6b50e46050861463b0939ffaf81.jpg', NULL, '2026-10-24 18:40:34'),
(6, 'dghdgh', '', '', 1, '2026-10-27 05:31:10');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `media`
--

CREATE TABLE IF NOT EXISTS `media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content` varchar(255) DEFAULT NULL,
  `url` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `media`
--

INSERT IGNORE INTO `media` (`id`, `content`, `url`) VALUES
(1, 'Bảo tàng không phải là nơi giấu đi quá khứ, mà là nơi thắp sáng ngọn lửa truyền thống cho mai sau', 'images/7d762852240adf22945ec2491fcb517a.jpg'),
(2, 'Văn hóa là nền tảng tinh thần của xã hội, là mục tiêu, động lực và nguồn lực nội sinh quan trọng cho phát triển bền vững đất nước', 'images/4804146380a2c03486b9fef115c8e82d.jpg'),
(3, '', 'images/d5f9483ff1a260a1ec2e890d04e0a1fd.jpg'),
(4, '', 'images/6f04583134538ba35a3051cc51d49444.jpg'),
(5, '', 'images/c7751e14d6d8a20a9abf328dc3ca1b84.jpg');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `pending_user_registrations`
--

CREATE TABLE IF NOT EXISTS `pending_user_registrations` (
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `verification_code_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `request_ip` varchar(45) NOT NULL,
  `last_sent_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`email`),
  KEY `idx_pending_user_registrations_ip_sent` (`request_ip`,`last_sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `pending_user_registrations`
--

INSERT IGNORE INTO `pending_user_registrations` (`email`, `full_name`, `phone`, `password_hash`, `verification_code_hash`, `expires_at`, `attempts`, `request_ip`, `last_sent_at`, `created_at`) VALUES
('catomat1804@gmail.com', 'Duca', '0932826859', '$2y$12$USOp4fz43d5JAMRpFrRZ4On4U/38LgNIyA/LFQEiJ7OGcVvi0DsXG', '$2y$12$DAo6rG5YDLbLjmReNUmlRetH9NVQrB60xO94j7izzo7V9yjcBZeje', '2026-10-26 21:59:46', 0, '42.116.229.13', '2026-10-26 21:49:46', '2026-10-26 21:48:31');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reviews`
--

CREATE TABLE IF NOT EXISTS `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_reviews_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `reviews`
--

INSERT IGNORE INTO `reviews` (`id`, `user_id`, `content`, `rating`, `created_at`) VALUES
(1, 5, 'This is the first time I have had the opportunity to visit this museum. This place has a very beautiful architecture, both modern and ancient. Not only visited, I also got more knowledge about another culture\r\nYour museum also has a website for my convenience in consulting and booking. Thanks your team for a wonderful experience.', 5, '2026-10-26 07:27:43'),
(2, 7, 'ĐÁNH GIÁ VỀ BẢO TÀNG\r\n\r\nQua chuyến tham quan, em nhận thấy bảo tàng là nơi lưu giữ, bảo tồn và giới thiệu những giá trị lịch sử, văn hóa quý báu của dân tộc. Các hiện vật, hình ảnh và tư liệu được trưng bày giúp người tham quan hiểu rõ hơn về quá trình hình thành, phát triển của đất nước, đồng thời nâng cao nhận thức về truyền thống lịch sử và lòng tự hào dân tộc.\r\n\r\nNhìn chung, không gian bảo tàng được bố trí tương đối khoa học, các hiện vật được sắp xếp theo từng chủ đề, giúp người tham quan dễ dàng theo dõi và tìm hiểu thông tin. Nội dung trưng bày có ý nghĩa giáo dục cao, mang lại nhiều kiến thức bổ ích và tạo điều kiện để người tham quan tiếp cận lịch sử một cách trực quan, sinh động.\r\n\r\nBên cạnh những ưu điểm, bảo tàng có thể cải thiện thêm các phương tiện thuyết minh, bảng thông tin và ứng dụng công nghệ tương tác để tăng sự hấp dẫn, giúp khách tham quan tiếp thu kiến thức hiệu quả hơn.\r\n\r\nTóm lại, chuyến tham quan bảo tàng là một trải nghiệm bổ ích, giúp em mở rộng kiến thức, hiểu thêm về các giá trị lịch sử và văn hóa, từ đó nâng cao ý thức trách nhiệm trong việc giữ gìn, bảo tồn và phát huy những giá trị quý báu của dân tộc.', 5, '2026-10-26 07:28:39'),
(3, 8, 'Chuyến đi rất tuyệt, người hướng dẫn rất chu đáo và thân thiện.', 5, '2026-10-26 23:09:05'),
(4, 9, 'Bảo tàng Văn hóa Dân tộc Khmer Trà Vinh là một điểm đến rất đáng ghé thăm để tìm hiểu về đời sống và văn hóa của đồng bào Khmer.\r\nNằm ngay cạnh Ao Bà Om và Chùa Âng, bảo tàng có kiến trúc mô phỏng theo những ngôi chùa Nam Bộ rất đặc sắc và không gian xanh mát. Bên trong lưu giữ rất nhiều hiện vật phong phú như trang phục truyền thống, nông cụ, nhạc cụ, cùng những bộ sách lá buông cổ quý giá. Dù cơ sở vật chất hơi cũ và thiếu các tiện ích hiện đại, nơi đây vẫn mang lại nhiều kiến thức bổ ích. Bạn nên kết hợp tham quan bảo tàng cùng Chùa Âng và Ao Bà Om trong cùng một chuyến đi để có trải nghiệm trọn vẹn nhất.', 5, '2026-10-27 02:53:46'),
(5, 6, 'tôi đã đến nơi và tận mắt chứng kiến, một website tuyệt vời, tôi chưa bao giờ thấy website nào hữu ích như thế này', 5, '2026-10-28 00:39:01');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `site_settings`
--

CREATE TABLE IF NOT EXISTS `site_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `site_settings`
--

INSERT IGNORE INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('address', 'Nguyễn Du, khóm 3, phường Nguyệt Hóa, tỉnh Vĩnh Long'),
('copyright', ''),
('description', 'Đưa bảo tàng lên không gian mạng chính là mở rộng cánh cửa trái tim văn hóa đến với mọi miền Tổ quốc và bạn bè quốc tế.'),
('email', 'baotangvhdtkhmer@gmail.com'),
('facebook_url', ''),
('intro_eyebrow', 'Bảo tàng Văn hóa Dân tộc Khmer tỉnh Vĩnh Long'),
('intro_history_first', 'Được khởi công và khánh thành vào ngày 26 tháng 8 năm 1995 từ tâm huyết bảo tồn di sản của Đảng bộ, chính quyền và đồng bào Khmer. Bảo tàng Văn hóa Dân tộc Khmer đã trải qua một hành trình phát triển đầy ý nghĩa, từ những ngày đầu sưu tầm các hiện vật sinh hoạt, trang phục, nhạc cụ hay tư liệu chữ viết cổ trên lá buông, bảo tàng nay đã khoác lên mình một diện mạo khang trang nhưng vẫn gìn giữ trọn vẹn giá trị truyền thống. Trải qua thời gian, nơi đây không chỉ đón tiếp hàng triệu lượt khách tham quan và đóng góp lớn cho du lịch văn hóa, mà còn đóng vai trò là nhịp cầu thắt chặt khối đoàn kết các dân tộc Kinh - Khmer - Hoa.'),
('intro_history_second', 'Bảo tàng Văn hóa Dân tộc Khmer tỉnh Vĩnh Long tựa như một cuốn sử thi sống động, gìn giữ trọn vẹn  tinh hoa của đồng bào Khmer Nam Bộ. Nơi đây cất giữ cả một bầu trời ký ức từ tiếng nhạc ngũ âm ngân vang, cho đến những trang kinh cổ khắc trên lá buông nhuốm màu thời gian. Không chỉ là nhịp cầu kết nối quá khứ với hiện tại, bảo tàng còn là niềm tự hào sâu sắc, nơi bồi đắp tình yêu quê hương và lan tỏa nét đẹp văn hóa độc đáo.'),
('intro_history_title', 'Lịch sử hình thành và ý nghĩa'),
('intro_image', 'uploads/museum-intro/b46fdadccf75e032bd779b47c5029b60.jpg'),
('intro_image_caption', 'Bảo tàng Văn hóa Dân tộc Khmer tỉnh Vĩnh Long'),
('intro_lead', 'Vĩnh Long là một trong những địa phương có đông đảo đồng bào dân tộc Khmer sinh sống nhất tại khu vực Đồng bằng sông Cửu Long với những ngôi chùa Nam tông mang kiến trúc độc đáo và một kho tàng di sản văn hóa phi vật thể vô cùng phong phú. Để bảo tồn, gìn giữ và phát huy những giá trị truyền thống tốt đẹp đó, Bảo tàng Văn hóa Dân tộc Khmer tỉnh Vĩnh Long đã được thành lập. Nằm tọa lạc trong danh thắng Ao Bà Om, bảo tàng gây ấn tượng mạnh mẽ với du khách ngay từ cái nhìn đầu tiên nhờ lối kiến trúc hài hòa truyền thống của đồng bào Khmer. Nơi đây không chỉ là điểm tham quan du lịch, văn hóa mà còn là trung tâm nghiên cứu, giáo dục truyền thống lịch sử, giúp các thế hệ hôm nay và mai sau thêm tự hào về bản sắc văn hóa của dân tộc mình.'),
('intro_title', 'Giới thiệu về bảo tàng'),
('museum_name', 'Bảo Tàng Văn Hóa Dân Tộc Khmer'),
('opening_days', 'Thứ hai - Chủ nhật'),
('opening_hours', '07:00 - 11:00\r\n13:00 - 17:00'),
('phone', '0294.3842.188'),
('website', 'https://baotangkhmer.free.je/?i=1'),
('youtube_url', 'https://www.youtube.com/watch?v=kLSQ1JWhpMU');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `slides`
--

CREATE TABLE IF NOT EXISTS `slides` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL COMMENT 'Tiêu đề banner',
  `description` text DEFAULT NULL COMMENT 'Mô tả chi tiết',
  `image` varchar(255) NOT NULL COMMENT 'Tên file ảnh',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `slides`
--

INSERT IGNORE INTO `slides` (`id`, `title`, `description`, `image`, `created_at`) VALUES
(1, '', '', 'd927c3c813369e7556d071555892c124.jpg', '2026-10-27 03:01:19');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT IGNORE INTO `users` (`id`, `full_name`, `email`, `password`, `phone`, `created_at`) VALUES
(3, 'Nguyễn Thanh Thịnh', 'nguyenthanhthinh078@gmail.com', '$2y$10$2KIuPfXlUWtr/n.Uowit3.MvJ2.eXYA92tocimPaJhNlTqomiQQOO', '0326896869', '2026-10-25 00:05:27'),
(5, 'Nguyễn Huỳnh Phương Vy', 'nguyenhuynhphuongvi.dmx2020@gmail.com', '$2y$10$pjq7YbbrnVY6HeMIUkYfue.gP6TRsCEdmeyPZ3jCo6hm9lmwATNYi', '0388456634', '2026-10-25 00:16:55'),
(6, 'hangvu', 'hangvuzxjv@gmail.com', '$2y$12$2HBFuSmbrDhHzuyJ4UENJOtEFGdIwP59v7MPyslgJWUMHC04KLTpq', '0332586371', '2026-10-26 05:44:57'),
(7, 'Huỳnh Đăng Khoa', 'huynhdangkhoa1004@gmail.com', '$2y$12$OCLNQDqe8ka9JKloW3B5ruwgSDXWPHXF1nO33Us0yrr7nXiH0Iz.K', '0988079624', '2026-10-26 07:27:11'),
(8, 'Nguyễn Kiến Quốc', 'quocmrh3llo@gmail.com', '$2y$12$VTIRcsbr.QiZxIJtcCd4PeYZGF25bhxh6USE6biqxjzyZV9wy3Ym6', '0705317150', '2026-10-26 23:08:20'),
(9, 'Nguyễn Ngọc Phương Vy', 'nguyenngocphuongvy715022@gmail.com', '$2y$12$qvs7kcEqsbIXyuR9dkjk8OZ/v6Al9I/7rS42SHcDAQkzmUwUylNYS', '0817614764', '2026-10-27 02:51:20');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `visits`
--

CREATE TABLE IF NOT EXISTS `visits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `visit_date` date NOT NULL,
  `num_people` int(11) NOT NULL DEFAULT 1,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_visit_user` (`user_id`),
  CONSTRAINT `fk_visit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `visit_registrations`
--

CREATE TABLE IF NOT EXISTS `visit_registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `visit_date` date NOT NULL,
  `visit_time` varchar(20) NOT NULL,
  `group_size` int(11) NOT NULL DEFAULT 1,
  `organization` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_visit_registrations_created_at` (`created_at`),
  KEY `idx_visit_registrations_visit_date` (`visit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `visit_registrations`
--

INSERT IGNORE INTO `visit_registrations` (`id`, `full_name`, `email`, `phone`, `visit_date`, `visit_time`, `group_size`, `organization`, `notes`, `created_at`) VALUES
(2, 'Nguyễn Thanh Thịnh', 'nguyenthanhthinh078@gmail.com', '0326896869', '2026-11-20', '07:00 - 11:00', 1, 'Vĩnh Long', 'không yêu cầu', '2026-10-26 12:28:45'),
(3, 'Nguyễn Kiến Quốc', 'quocmrh3llo@gmail.com', '0705317150', '2026-11-04', '07:00 - 11:00', 6, 'Vĩnh Long', 'Gặp tôi ở cổng', '2026-10-26 05:23:51'),
(4, 'Bùi thanh du ca', 'catomat1804@gmail.com', '0932826859', '2026-10-31', '07:00 - 11:00', 1, 'Vĩnh Long', '', '2026-10-26 06:40:54');

--
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
