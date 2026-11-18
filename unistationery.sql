-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 18, 2026 lúc 11:06 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `unistationery`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `addresses`
--

CREATE TABLE `addresses` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `recipient_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Sổ tay', 'Sổ và planner phục vụ học tập', '2026-10-29 04:53:16'),
(2, 'Bút viết', 'Bút bi, bút gel và bút highlight', '2026-10-29 04:53:16'),
(3, 'Giấy ghi chú', 'Giấy note dùng ghi nhớ công việc', '2026-10-29 04:53:16'),
(4, 'Combo', 'Bộ dụng cụ học tập tiết kiệm', '2026-10-29 04:53:16'),
(5, 'Dụng cụ học tập', 'Thước và các dụng cụ học tập khác', '2026-10-29 04:53:16');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `coupons`
--

CREATE TABLE `coupons` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('fixed','percent') NOT NULL DEFAULT 'fixed',
  `discount_value` decimal(12,2) NOT NULL,
  `min_order_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `usage_limit` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `used_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `expires_at` datetime DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `min_order` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `discount_type`, `discount_value`, `min_order_amount`, `usage_limit`, `used_count`, `expires_at`, `status`, `created_at`, `min_order`) VALUES
(1, 'UNILETOWIN', 'fixed', 10000.00, 50000.00, 100, 1, '2026-12-31 23:59:59', 'active', '2026-11-06 09:24:43', 0.00),
(2, 'UNISALE20', 'percent', 20.00, 100000.00, 50, 0, '2026-12-31 23:59:59', 'active', '2026-11-06 09:24:43', 0.00),
(3, 'WELCOME10', 'fixed', 10.00, 0.00, 0, 0, NULL, 'active', '2026-11-12 03:32:10', 0.00),
(4, 'STUDENT20K', 'fixed', 20000.00, 0.00, 0, 0, NULL, 'active', '2026-11-12 03:32:10', 100000.00),
(5, 'FREESHIP', 'fixed', 15000.00, 0.00, 0, 0, NULL, 'active', '2026-11-12 03:32:10', 50000.00),
(6, 'UNISALE50', 'fixed', 50.00, 0.00, 0, 0, NULL, 'active', '2026-11-12 03:32:10', 500000.00),
(7, 'SUMMERVOUCHER', 'fixed', 30000.00, 0.00, 0, 0, NULL, 'active', '2026-11-12 03:32:10', 150000.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `recipient_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `note` text DEFAULT NULL,
  `shipping_carrier` varchar(100) DEFAULT NULL,
  `tracking_code` varchar(100) DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(50) NOT NULL DEFAULT 'COD',
  `status` enum('pending','confirmed','shipping','completed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `recipient_name`, `phone`, `address`, `note`, `shipping_carrier`, `tracking_code`, `total_amount`, `payment_method`, `status`, `created_at`) VALUES
(1, 1, 'vũ duy đại', '0983461048', '122 trại cá', 'giao giờ hành chính', NULL, NULL, 15000.00, 'COD', 'cancelled', '2026-10-30 13:49:34'),
(2, 1, 'An béo', '0123456789', '296 minh khai', 'hay boom hàng', NULL, NULL, 24000.00, 'COD', 'completed', '2026-10-31 07:39:53'),
(3, 1, 'văn bách', '1234509876', 'yên mô ninh bình', '', NULL, NULL, 60000.00, 'COD', 'cancelled', '2026-11-06 09:52:39'),
(4, 1, 'vũ duy đại', '0239384747', '122 dsvs', '', NULL, NULL, 10000.00, 'VNPAY', 'confirmed', '2026-11-10 03:25:54'),
(5, 1, 'vũ duy đại', '0563463631', 'sdb', '', NULL, NULL, 18000.00, 'VNPAY', 'cancelled', '2026-11-10 03:31:01'),
(6, 1, 'vũ duy đại', '1234509876', 'fafafva', 'Mã giảm giá: UNILETOWIN.', NULL, NULL, 69000.00, 'BANK_TRANSFER', 'pending', '2026-11-10 04:28:44'),
(7, 1, 'vũ duy đại', '0123456789', 'chbbc', '', NULL, NULL, 10000.00, 'BANK_TRANSFER', 'pending', '2026-11-10 04:42:40');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_variant_id` int(10) UNSIGNED DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `variant_name` varchar(100) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_variant_id`, `product_name`, `variant_name`, `sku`, `unit_price`, `quantity`, `subtotal`) VALUES
(1, 1, 1, 'Bút highlight', 'Mặc định', 'UNI-HIGHLIGHT-001', 15000.00, 1, 15000.00),
(2, 2, 8, 'Bút gel xanh', 'Màu đen', 'UNI-PEN-002', 12000.00, 2, 24000.00),
(3, 3, 8, 'Bút gel xanh', 'Màu đen', 'UNI-PEN-002', 12000.00, 5, 60000.00),
(4, 4, 4, 'Thước kẻ', 'Mặc định', 'UNI-RULER-001', 10000.00, 1, 10000.00),
(5, 5, 5, 'Giấy ghi chú', 'Mặc định', 'UNI-NOTE-001', 18000.00, 1, 18000.00),
(6, 6, 3, 'Bộ dụng cụ học tập', 'Mặc định', 'UNI-COMBO-001', 79000.00, 1, 79000.00),
(7, 7, 4, 'Thước kẻ', 'Mặc định', 'UNI-RULER-001', 10000.00, 1, 10000.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `image_emoji` varchar(20) DEFAULT '?',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `image_emoji`, `status`, `created_at`, `image`) VALUES
(1, 2, 'Bút highlight', 'Bút đánh dấu nội dung quan trọng', '🖍️', 'active', '2026-10-29 04:54:05', 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=500&auto=format&fit=crop&q=60'),
(2, 2, 'Bút gel xanh', 'Bút gel viết mượt cho ghi chép hằng ngày', '🖊️', 'active', '2026-10-29 04:54:05', 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=500&auto=format&fit=crop&q=60'),
(3, 4, 'Bộ dụng cụ học tập', 'Bộ dụng cụ cơ bản dành cho sinh viên', '🎒', 'active', '2026-10-29 04:54:05', 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=500&auto=format&fit=crop&q=60'),
(4, 5, 'Thước kẻ', 'Thước kẻ phục vụ học tập', '📏', 'active', '2026-10-29 04:54:05', 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=500&auto=format&fit=crop&q=60'),
(5, 3, 'Giấy ghi chú', 'Giấy note tiện lợi để đánh dấu bài học', '🗒️', 'active', '2026-10-29 04:54:05', 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=500&auto=format&fit=crop&q=60'),
(6, 1, 'Sổ tay kế hoạch', 'Sổ ghi chép và lập kế hoạch học tập', '📒', 'active', '2026-10-29 04:54:05', 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=500&auto=format&fit=crop&q=60'),
(7, 2, 'Bút bi Thiên Long gel', 'Bút bi mực trơn, viết êm tay, phù hợp cho học sinh sinh viên.', '🖊️', 'active', '2026-11-12 03:14:46', 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=500&auto=format&fit=crop&q=60'),
(8, 1, 'Sổ tay bìa lò xo A5', 'Giấy chống lóa, dòng kẻ ngang rõ nét, định lượng 80gsm.', '📓', 'active', '2026-11-12 03:14:46', 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=500&auto=format&fit=crop&q=60'),
(9, 5, 'Hộp bút vải canvas', 'Thiết kế tối giản, sức chứa lớn, đựng được nhiều dụng cụ học tập.', '👝', 'active', '2026-11-12 03:14:46', 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=500&auto=format&fit=crop&q=60'),
(10, 5, 'Compa học sinh kim loại', 'Độ bền cao, vẽ đường tròn chính xác cho môn Toán hình.', '📐', 'active', '2026-11-12 03:14:46', 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=500&auto=format&fit=crop&q=60'),
(11, 4, 'Túi đựng tài liệu A4', 'Chất liệu nhựa dẻo cao cấp, chống thấm nước, bảo quản giấy tờ tốt.', '📁', 'active', '2026-11-12 03:14:46', 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=500&auto=format&fit=crop&q=60'),
(12, 5, 'Bấm kim nhỏ Deli', 'Kèm theo kim bấm tiện lợi, lực bấm nhẹ nhàng, thiết kế nhỏ gọn.', '📌', 'active', '2026-11-12 03:14:46', 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=500&auto=format&fit=crop&q=60'),
(13, 2, 'Bút xóa kéo thông minh', 'Băng xóa dài, êm mượt, không bị đứt quãng khi sử dụng.', '✏️', 'active', '2026-11-12 03:14:46', 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=500&auto=format&fit=crop&q=60'),
(14, 3, 'Giấy Note nhiều màu', 'Dán note ghi chú công việc, học tập tiện lợi, keo dán chắc chắn.', '📑', 'active', '2026-11-12 03:14:46', 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=500&auto=format&fit=crop&q=60'),
(15, 4, 'Combo Back-to-School (Sổ + Bút + Thước)', 'Bút bi mực trơn, viết êm tay, phù hợp cho học sinh sinh viên.', '🖊️', 'active', '2026-11-12 03:15:56', 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=500&auto=format&fit=crop&q=60'),
(16, 4, 'Combo Take Notes Sáng Tạo', 'Giấy chống lóa, dòng kẻ ngang rõ nét, định lượng 80gsm.', '📓', 'active', '2026-11-12 03:15:56', 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=500&auto=format&fit=crop&q=60'),
(17, 5, 'Hộp bút vải canvas', 'Thiết kế tối giản, sức chứa lớn, đựng được nhiều dụng cụ học tập.', '👝', 'active', '2026-11-12 03:15:56', 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=500&auto=format&fit=crop&q=60'),
(18, 5, 'Compa học sinh kim loại', 'Độ bền cao, vẽ đường tròn chính xác cho môn Toán hình.', '📐', 'active', '2026-11-12 03:15:56', 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=500&auto=format&fit=crop&q=60'),
(19, 4, 'Túi đựng tài liệu A4', 'Chất liệu nhựa dẻo cao cấp, chống thấm nước, bảo quản giấy tờ tốt.', '📁', 'active', '2026-11-12 03:15:56', 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=500&auto=format&fit=crop&q=60'),
(20, 5, 'Bấm kim nhỏ Deli', 'Kèm theo kim bấm tiện lợi, lực bấm nhẹ nhàng, thiết kế nhỏ gọn.', '📌', 'active', '2026-11-12 03:15:56', 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=500&auto=format&fit=crop&q=60'),
(21, 2, 'Bút xóa kéo thông minh', 'Băng xóa dài, êm mượt, không bị đứt quãng khi sử dụng.', '✏️', 'active', '2026-11-12 03:15:56', 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=500&auto=format&fit=crop&q=60'),
(22, 3, 'Giấy Note nhiều màu', 'Dán note ghi chú công việc, học tập tiện lợi, keo dán chắc chắn.', '📑', 'active', '2026-11-12 03:15:56', 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=500&auto=format&fit=crop&q=60'),
(23, 4, 'Combo Back-to-School (Sổ + Bút + Thước)', 'Bộ sản phẩm thiết yếu đầu năm học gồm 1 sổ tay A5, 2 bút gel và 1 thước kẻ trong suốt.', '🎁', 'active', '2026-11-12 03:19:15', 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=500&auto=format&fit=crop&q=60'),
(24, 4, 'Combo Take Notes Sáng Tạo', 'Trọn gói dụng cụ ghi chép thông minh gồm 1 tập giấy note nhiều màu và 3 bút highlight pastel.', '🎀', 'active', '2026-11-12 03:19:15', 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=500&auto=format&fit=crop&q=60');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `sku` varchar(80) NOT NULL,
  `variant_name` varchar(100) NOT NULL DEFAULT 'Mặc định',
  `price` decimal(12,2) NOT NULL,
  `stock` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `sku`, `variant_name`, `price`, `stock`, `status`, `created_at`) VALUES
(1, 1, 'UNI-HIGHLIGHT-001', 'Mặc định', 15000.00, 60, 'active', '2026-10-29 04:54:27'),
(2, 2, 'UNI-PEN-001', 'Mặc định', 12000.00, 100, 'active', '2026-10-29 04:54:27'),
(3, 3, 'UNI-COMBO-001', 'Mặc định', 79000.00, 19, 'active', '2026-10-29 04:54:27'),
(4, 4, 'UNI-RULER-001', 'Mặc định', 10000.00, 78, 'active', '2026-10-29 04:54:27'),
(5, 5, 'UNI-NOTE-001', 'Mặc định', 18000.00, 50, 'active', '2026-10-29 04:54:27'),
(6, 6, 'UNI-BOOK-001', 'Mặc định', 45000.00, 30, 'active', '2026-10-29 04:54:27'),
(8, 2, 'UNI-PEN-002', 'Màu đen', 12000.00, 98, 'active', '2026-10-31 04:58:27'),
(9, 2, 'UNI-PEN-003', 'Màu đỏ', 12000.00, 100, 'active', '2026-10-31 04:58:27'),
(18, 7, 'SKU-BUT-01', 'Mực xanh - Hộp 1 chiếc', 5000.00, 100, 'active', '2026-11-12 03:15:56'),
(19, 8, 'SKU-SO-01', 'A5 - 100 trang', 25000.00, 50, 'active', '2026-11-12 03:15:56'),
(20, 9, 'SKU-HOP-01', 'Màu be basic', 35000.00, 40, 'active', '2026-11-12 03:15:56'),
(21, 10, 'SKU-COM-01', 'Bộ tiêu chuẩn', 12000.00, 30, 'active', '2026-11-12 03:15:56'),
(22, 11, 'SKU-TUI-01', 'Màu pastel trong suốt', 8000.00, 60, 'active', '2026-11-12 03:15:56'),
(23, 12, 'SKU-BAM-01', 'Cỡ mini kèm kim', 15000.00, 45, 'active', '2026-11-12 03:15:56'),
(24, 13, 'SKU-XOA-01', 'Băng dài 12m', 14000.00, 80, 'active', '2026-11-12 03:15:56'),
(25, 14, 'SKU-NOTE-01', 'Tập 5 tập nhiều màu', 22000.00, 90, 'active', '2026-11-12 03:15:56'),
(26, 7, 'SKU-TL-XUAN', 'Mực xanh (Hộp 1 chiếc)', 5000.00, 50, 'active', '2026-11-12 03:18:26'),
(27, 7, 'SKU-TL-DEN', 'Mực đen (Hộp 1 chiếc)', 5000.00, 50, 'active', '2026-11-12 03:18:26'),
(28, 7, 'SKU-TL-DO', 'Mực đỏ (Hộp 1 chiếc)', 5000.00, 30, 'active', '2026-11-12 03:18:26'),
(29, 8, 'SKU-SO-A5-KE', 'Ruột kẻ ngang (100 trang)', 25000.00, 40, 'active', '2026-11-12 03:18:26'),
(30, 8, 'SKU-SO-A5-TR', 'Ruột trơn không dòng kẻ', 25000.00, 30, 'active', '2026-11-12 03:18:26'),
(31, 9, 'SKU-HOP-BE', 'Màu Be Vintage', 35000.00, 20, 'active', '2026-11-12 03:18:26'),
(32, 9, 'SKU-HOP-XAM', 'Màu Xám Tro', 35000.00, 20, 'active', '2026-11-12 03:18:26'),
(33, 10, 'SKU-COM-BAC', 'Màu Bạc cổ điển', 12000.00, 30, 'active', '2026-11-12 03:18:26'),
(34, 10, 'SKU-COM-DEN', 'Màu Đen nhám', 14000.00, 25, 'active', '2026-11-12 03:18:26'),
(35, 11, 'SKU-TUI-TRONG', 'Màu trong suốt', 8000.00, 40, 'active', '2026-11-12 03:18:26'),
(36, 11, 'SKU-TUI-XANH', 'Xanh pastel', 9000.00, 30, 'active', '2026-11-12 03:18:26'),
(37, 12, 'SKU-BAM-XANH', 'Xanh dương', 15000.00, 25, 'active', '2026-11-12 03:18:26'),
(38, 12, 'SKU-BAM-HONG', 'Hồng pastel', 15000.00, 25, 'active', '2026-11-12 03:18:26'),
(39, 13, 'SKU-XOA-12M', 'Băng dài 12m (Bản tiêu chuẩn)', 14000.00, 50, 'active', '2026-11-12 03:18:26'),
(40, 14, 'SKU-NOTE-5T', 'Tập 5 màu Pastel', 22000.00, 60, 'active', '2026-11-12 03:18:26'),
(41, 14, 'SKU-NOTE-NEO', 'Tập 5 màu Neon nổi bật', 22000.00, 40, 'active', '2026-11-12 03:18:26'),
(42, 15, 'SKU-COMBO-BTS1', 'Bộ tiêu chuẩn Tone Pastel', 45000.00, 30, 'active', '2026-11-12 03:19:15'),
(43, 16, 'SKU-COMBO-TN1', 'Set màu hồng/xanh', 39000.00, 25, 'active', '2026-11-12 03:19:15'),
(47, 15, 'SKU-BTS-PASTEL', 'Tone Pastel nhẹ nhàng (Sổ + 2 bút + Thước)', 45000.00, 30, 'active', '2026-11-12 03:26:18'),
(50, 15, 'SKU-BTS-VINTAGE', 'Tone Vintage cổ điển (Sổ + 2 bút + Thước)', 48000.00, 25, 'active', '2026-11-12 03:26:18'),
(53, 16, 'SKU-TN-HONG', 'Set Màu Hồng Pastel (Note + 3 bút highlight)', 39000.00, 40, 'active', '2026-11-12 03:26:18'),
(56, 16, 'SKU-TN-XANH', 'Set Màu Xanh Mint (Note + 3 bút highlight)', 39000.00, 35, 'active', '2026-11-12 03:26:18');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reviews`
--

CREATE TABLE `reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `order_item_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(1) UNSIGNED NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('customer','staff','admin') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `phone`, `role`, `created_at`) VALUES
(1, 'vũ duy đại', 'vuduydai020405@gmail.com', '$2y$10$2jMmlcu6.dP0KCQd6utFheUu31ZAHWhLRPVeF0IckpzfLndVfOyOC', NULL, 'customer', '2026-10-30 08:57:13'),
(2, 'leomax', 'admin@gmail.com', '$2y$10$OzfupM/xjTBdAhIpv.3qjuyr/IOGXgORtJSQyyjNaQNcWB1VijqxO', NULL, 'admin', '2026-10-30 13:58:28'),
(3, 'abc', 'sfas@gmail.com', '$2y$10$NsCGuDE6S99/tfdqNnaKWeWqOBYsHbq9rFaBkCJeUjkLONt1WuvZW', NULL, 'customer', '2026-11-06 09:44:33'),
(4, 'nhanvien1', 'nhanvien1@gmail.com', '$2y$10$SW2X/QgnM1hX6Nr6K7gCB.7b862CaGNxE9vgLZFa9mQ5YtWU.CZQ.', NULL, 'staff', '2026-11-11 01:02:50');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `wishlists`
--

CREATE TABLE `wishlists` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_addresses_user` (`user_id`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Chỉ mục cho bảng `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orders_user` (`user_id`);

--
-- Chỉ mục cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_items_order` (`order_id`),
  ADD KEY `fk_order_items_variant` (`product_variant_id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_products_category` (`category_id`);

--
-- Chỉ mục cho bảng `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `fk_variants_product` (`product_id`);

--
-- Chỉ mục cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reviews_user` (`user_id`),
  ADD KEY `fk_reviews_product` (`product_id`),
  ADD KEY `fk_reviews_order_item` (`order_item_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Chỉ mục cho bảng `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_wishlist_user` (`user_id`),
  ADD KEY `fk_wishlist_product` (`product_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT cho bảng `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT cho bảng `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `fk_addresses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_items_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `fk_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `fk_wishlist_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
