-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 20, 2025 at 09:53 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `loei_rice_ecommerce`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('billing','shipping') DEFAULT 'shipping' COMMENT 'ประเภทที่อยู่',
  `first_name` varchar(50) NOT NULL COMMENT 'ชื่อ',
  `last_name` varchar(50) NOT NULL COMMENT 'นามสกุล',
  `company` varchar(100) DEFAULT NULL COMMENT 'ชื่อบริษัท',
  `address_line1` varchar(255) NOT NULL COMMENT 'ที่อยู่ บรรทัดที่ 1',
  `address_line2` varchar(255) DEFAULT NULL COMMENT 'ที่อยู่ บรรทัดที่ 2',
  `district` varchar(100) DEFAULT NULL COMMENT 'อำเภอ/เขต',
  `city` varchar(100) NOT NULL COMMENT 'จังหวัด',
  `state` varchar(100) NOT NULL COMMENT 'ภาค',
  `postal_code` varchar(20) NOT NULL COMMENT 'รหัสไปรษณีย์',
  `country` varchar(100) DEFAULT 'Thailand' COMMENT 'ประเทศ',
  `phone` varchar(20) DEFAULT NULL COMMENT 'เบอร์โทรศัพท์',
  `is_default` tinyint(1) DEFAULT 0 COMMENT 'ที่อยู่เริ่มต้น',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL COMMENT 'ชื่อผู้ใช้',
  `email` varchar(100) NOT NULL COMMENT 'อีเมล',
  `password` varchar(255) NOT NULL COMMENT 'รหัสผ่าน (encrypted)',
  `first_name` varchar(50) NOT NULL COMMENT 'ชื่อ',
  `last_name` varchar(50) NOT NULL COMMENT 'นามสกุล',
  `role` enum('super_admin','admin','editor') DEFAULT 'admin' COMMENT 'บทบาท',
  `permissions` text DEFAULT NULL COMMENT 'สิทธิ์การใช้งาน (JSON)',
  `profile_image` varchar(255) DEFAULT NULL COMMENT 'รูปโปรไฟล์',
  `status` enum('active','inactive') DEFAULT 'active' COMMENT 'สถานะ',
  `last_login` timestamp NULL DEFAULT NULL COMMENT 'เข้าสู่ระบบครั้งสุดท้าย',
  `last_login_ip` varchar(45) DEFAULT NULL COMMENT 'IP ที่เข้าสู่ระบบครั้งสุดท้าย',
  `login_attempts` int(11) DEFAULT 0 COMMENT 'จำนวนครั้งที่พยายามเข้าสู่ระบบ',
  `locked_until` timestamp NULL DEFAULT NULL COMMENT 'ล็อกบัญชีจนถึง',
  `created_by` int(11) DEFAULT NULL COMMENT 'สร้างโดย',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `first_name`, `last_name`, `role`, `permissions`, `profile_image`, `status`, `last_login`, `last_login_ip`, `login_attempts`, `locked_until`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@loeirice.com', '$2y$10$bUEAyL02qCo3zQuf92Ys4OWkaKoo5Eh0XvU16/V6tvo8AqB8ETzm.', 'ผู้ดูแล', 'ระบบ', 'super_admin', NULL, NULL, 'active', '2025-09-08 15:10:05', '::1', 5, '2025-09-20 03:00:00', NULL, '2025-07-06 12:42:55', '2025-09-20 07:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL COMMENT 'Session ID สำหรับผู้ใช้ที่ไม่ได้ล็อกอิน',
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1 COMMENT 'จำนวน',
  `price` decimal(10,2) NOT NULL COMMENT 'ราคา ณ เวลาที่เพิ่มลงตะกร้า',
  `product_options` text DEFAULT NULL COMMENT 'ตัวเลือกสินค้า (JSON)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'ชื่อหมวดหมู่',
  `description` text DEFAULT NULL COMMENT 'คำอธิบายหมวดหมู่',
  `image` varchar(255) DEFAULT NULL COMMENT 'รูปภาพหมวดหมู่',
  `slug` varchar(255) DEFAULT NULL COMMENT 'URL slug',
  `sort_order` int(11) DEFAULT 0 COMMENT 'ลำดับการแสดง',
  `status` enum('active','inactive') DEFAULT 'active' COMMENT 'สถานะ',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `image`, `slug`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(1, 'ข้าวพันธุ์พื้นเมือง', 'ข้าวเหนียวแดงและข้าวเหนียวซิวเกลี้ยงเมืองเลย พันธุ์พื้นเมืองแท้จากจังหวัดเลย', NULL, 'rice-varieties', 1, 'active', '2025-07-06 12:42:55', '2025-07-07 03:16:00'),
(2, 'ผลิตภัณฑ์อาหาร', 'ข้าวพอง ข้าวกระยาสารท และผลิตภัณฑ์อาหารแปรรูปจากข้าวพันธุ์พื้นเมือง', NULL, 'food-products', 2, 'active', '2025-07-06 12:42:55', '2025-07-06 12:42:55'),
(3, 'เครื่องสำอางธรรมชาติ', 'ครีมบำรุงผิว สบู่ และผลิตภัณฑ์เครื่องสำอางที่ทำจากข้าวพันธุ์พื้นเมือง', NULL, 'natural-cosmetics', 3, 'active', '2025-07-06 12:42:55', '2025-07-06 12:42:55');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_number` varchar(50) NOT NULL COMMENT 'เลขที่คำสั่งซื้อ',
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled','returned','refunded') DEFAULT 'pending' COMMENT 'สถานะ',
  `subtotal` decimal(10,2) NOT NULL COMMENT 'ราคารวมสินค้า',
  `shipping_cost` decimal(10,2) DEFAULT 0.00 COMMENT 'ค่าจัดส่ง',
  `tax_amount` decimal(10,2) DEFAULT 0.00 COMMENT 'ภาษี',
  `discount_amount` decimal(10,2) DEFAULT 0.00 COMMENT 'ส่วนลด',
  `total_amount` decimal(10,2) NOT NULL COMMENT 'ยอดรวมทั้งหมด',
  `payment_method` enum('bank_transfer','promptpay') NOT NULL COMMENT 'วิธีการชำระเงิน',
  `payment_status` enum('pending','paid','failed','refunded','partial_refund') DEFAULT 'pending' COMMENT 'สถานะการชำระเงิน',
  `payment_reference` varchar(100) DEFAULT NULL COMMENT 'เลขที่อ้างอิงการชำระเงิน',
  `paid_at` timestamp NULL DEFAULT NULL COMMENT 'วันที่ชำระเงิน',
  `shipping_method_id` int(11) DEFAULT NULL COMMENT 'วิธีการจัดส่ง',
  `tracking_number` varchar(100) DEFAULT NULL COMMENT 'หมายเลขติดตาม',
  `shipping_notes` text DEFAULT NULL COMMENT 'หมายเหตุการจัดส่ง',
  `billing_address` text NOT NULL COMMENT 'ที่อยู่เรียกเก็บเงิน (JSON)',
  `shipping_address` text NOT NULL COMMENT 'ที่อยู่จัดส่ง (JSON)',
  `customer_notes` text DEFAULT NULL COMMENT 'หมายเหตุจากลูกค้า',
  `admin_notes` text DEFAULT NULL COMMENT 'หมายเหตุจากแอดมิน',
  `ordered_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่สั่งซื้อ',
  `confirmed_at` timestamp NULL DEFAULT NULL COMMENT 'วันที่ยืนยัน',
  `shipped_at` timestamp NULL DEFAULT NULL COMMENT 'วันที่จัดส่ง',
  `delivered_at` timestamp NULL DEFAULT NULL COMMENT 'วันที่ส่งถึง',
  `cancelled_at` timestamp NULL DEFAULT NULL COMMENT 'วันที่ยกเลิก',
  `cancel_reason` text DEFAULT NULL COMMENT 'เหตุผลการยกเลิก',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `order_details`
-- (See below for the actual view)
--
CREATE TABLE `order_details` (
`id` int(11)
,`user_id` int(11)
,`order_number` varchar(50)
,`status` enum('pending','confirmed','processing','shipped','delivered','cancelled','returned','refunded')
,`subtotal` decimal(10,2)
,`shipping_cost` decimal(10,2)
,`tax_amount` decimal(10,2)
,`discount_amount` decimal(10,2)
,`total_amount` decimal(10,2)
,`payment_method` enum('bank_transfer','promptpay')
,`payment_status` enum('pending','paid','failed','refunded','partial_refund')
,`payment_reference` varchar(100)
,`paid_at` timestamp
,`shipping_method_id` int(11)
,`tracking_number` varchar(100)
,`shipping_notes` text
,`billing_address` text
,`shipping_address` text
,`customer_notes` text
,`admin_notes` text
,`ordered_at` timestamp
,`confirmed_at` timestamp
,`shipped_at` timestamp
,`delivered_at` timestamp
,`cancelled_at` timestamp
,`cancel_reason` text
,`created_at` timestamp
,`updated_at` timestamp
,`customer_name` varchar(201)
,`customer_email` varchar(255)
,`customer_phone` varchar(20)
,`shipping_method_name` varchar(100)
,`shipping_estimated_days` varchar(50)
);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(200) NOT NULL COMMENT 'ชื่อสินค้า (ณ เวลาที่สั่งซื้อ)',
  `product_price` decimal(10,2) NOT NULL COMMENT 'ราคาสินค้า (ณ เวลาที่สั่งซื้อ)',
  `product_image` varchar(255) DEFAULT NULL COMMENT 'รูปภาพสินค้า',
  `quantity` int(11) NOT NULL COMMENT 'จำนวน',
  `total_price` decimal(10,2) NOT NULL COMMENT 'ราคารวม',
  `product_options` text DEFAULT NULL COMMENT 'ตัวเลือกสินค้า (JSON)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_notifications`
--

CREATE TABLE `payment_notifications` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `transfer_amount` decimal(10,2) NOT NULL COMMENT 'จำนวนเงินที่โอน',
  `transfer_date` date NOT NULL COMMENT 'วันที่โอน',
  `transfer_time` time NOT NULL COMMENT 'เวลาที่โอน',
  `slip_image` varchar(255) NOT NULL COMMENT 'ไฟล์รูปภาพสลิป',
  `status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending' COMMENT 'สถานะการตรวจสอบ',
  `admin_notes` text DEFAULT NULL COMMENT 'หมายเหตุจากแอดมิน',
  `verified_by` int(11) DEFAULT NULL COMMENT 'ID แอดมินที่ตรวจสอบ',
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(200) NOT NULL COMMENT 'ชื่อสินค้า',
  `description` text DEFAULT NULL COMMENT 'คำอธิบายสินค้า',
  `short_description` varchar(500) DEFAULT NULL COMMENT 'คำอธิบายสั้น',
  `price` decimal(10,2) NOT NULL COMMENT 'ราคา',
  `sale_price` decimal(10,2) DEFAULT NULL COMMENT 'ราคาลดพิเศษ',
  `stock_quantity` int(11) DEFAULT 0 COMMENT 'จำนวนสต็อก',
  `min_stock_level` int(11) DEFAULT 5 COMMENT 'สต็อกขั้นต่ำแจ้งเตือน',
  `weight` decimal(8,2) DEFAULT NULL COMMENT 'น้ำหนัก',
  `unit` varchar(50) DEFAULT NULL COMMENT 'หน่วย (กก., ถุง, หลอด, ก้อน)',
  `image_main` varchar(255) DEFAULT NULL COMMENT 'รูปภาพหลัก',
  `image_gallery` text DEFAULT NULL COMMENT 'รูปภาพเพิ่มเติม (JSON format)',
  `features` text DEFAULT NULL COMMENT 'คุณสมบัติเด่น',
  `ingredients` text DEFAULT NULL COMMENT 'ส่วนผสม/วัตถุดิบ',
  `benefits` text DEFAULT NULL COMMENT 'คุณประโยชน์',
  `usage_instructions` text DEFAULT NULL COMMENT 'วิธีการใช้งาน',
  `storage_instructions` text DEFAULT NULL COMMENT 'วิธีการเก็บรักษา',
  `origin` varchar(200) DEFAULT NULL COMMENT 'แหล่งที่มา/ถิ่นกำเนิด',
  `harvest_season` varchar(100) DEFAULT NULL COMMENT 'ฤดูเก็บเกี่ยว',
  `certification` varchar(200) DEFAULT NULL COMMENT 'การรับรอง (อินทรีย์, GAP, etc.)',
  `status` enum('active','inactive','out_of_stock') DEFAULT 'active' COMMENT 'สถานะ',
  `featured` tinyint(1) DEFAULT 0 COMMENT 'สินค้าแนะนำ',
  `is_new` tinyint(1) DEFAULT 0 COMMENT 'สินค้าใหม่',
  `view_count` int(11) DEFAULT 0 COMMENT 'จำนวนครั้งที่ดู',
  `rating_average` decimal(3,2) DEFAULT 0.00 COMMENT 'คะแนนเฉลี่ย',
  `rating_count` int(11) DEFAULT 0 COMMENT 'จำนวนคนให้คะแนน',
  `meta_title` varchar(255) DEFAULT NULL COMMENT 'Meta title สำหรับ SEO',
  `meta_description` text DEFAULT NULL COMMENT 'Meta description สำหรับ SEO',
  `slug` varchar(255) DEFAULT NULL COMMENT 'URL slug',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `short_description`, `price`, `sale_price`, `stock_quantity`, `min_stock_level`, `weight`, `unit`, `image_main`, `image_gallery`, `features`, `ingredients`, `benefits`, `usage_instructions`, `storage_instructions`, `origin`, `harvest_season`, `certification`, `status`, `featured`, `is_new`, `view_count`, `rating_average`, `rating_count`, `meta_title`, `meta_description`, `slug`, `created_at`, `updated_at`) VALUES
(8, 2, 'Sticky Rice Crackers', 'วัตถุดิบ\r\n• ข้าวเหนียวสายพันธุ์ซิวเกลี้ยงที่ผ่านการอบพอง 50%\r\n• ข้าวสารหอมมะลิ 50%', 'ผลิตภัณฑ์ Sticky Rice Crackers จากข้าวเหนียว GI จังหวัดเลย สายพันธุ์ซิวเกลี้ยง', 35.00, NULL, 50, 10, 50.00, 'ห่อ', 'product_1757321995_6158.png', NULL, '', 'ผลิตภัณฑ์นี้ใช้วัตถุดิบจาก ข้าวเหนียวซิวเกลี้ยงอบพอง 50% ผสมกับ ข้าวหอมมะลิอีก 50% ผ่านกรรมวิธีเฉพาะ จนได้เป็นข้าวพองแผ่นกลม สีเหลืองอ่อน สม่ำเสมอ กรอบอร่อยมาก ๆ', '', '•ทา แยมผลไม้ อย่างสตรอว์เบอร์รี่ บลูเบอร์รี่ หรือส้ม จะได้รสหวานอมเปรี้ยว ตัดกับความกรอบอย่างลงตัว\r\n•หรือชอบช็อกโกแลต ลองทา นูเทลล่า หวานมันเข้มข้น\r\n•กินคู่กับชา กาแฟ หรือเป็นของว่างยามบ่ายก็ดี', '', '', '', 'อย. 42-2-00353-2-0011', 'active', 0, 1, 3, 0.00, 0, NULL, NULL, NULL, '2025-09-08 08:59:55', '2025-09-20 07:27:43');

-- --------------------------------------------------------

--
-- Stand-in structure for view `product_details`
-- (See below for the actual view)
--
CREATE TABLE `product_details` (
`id` int(11)
,`category_id` int(11)
,`name` varchar(200)
,`description` text
,`short_description` varchar(500)
,`price` decimal(10,2)
,`sale_price` decimal(10,2)
,`stock_quantity` int(11)
,`min_stock_level` int(11)
,`weight` decimal(8,2)
,`unit` varchar(50)
,`image_main` varchar(255)
,`image_gallery` text
,`features` text
,`ingredients` text
,`benefits` text
,`usage_instructions` text
,`storage_instructions` text
,`origin` varchar(200)
,`harvest_season` varchar(100)
,`certification` varchar(200)
,`status` enum('active','inactive','out_of_stock')
,`featured` tinyint(1)
,`is_new` tinyint(1)
,`view_count` int(11)
,`rating_average` decimal(3,2)
,`rating_count` int(11)
,`meta_title` varchar(255)
,`meta_description` text
,`slug` varchar(255)
,`created_at` timestamp
,`updated_at` timestamp
,`category_name` varchar(100)
,`category_slug` varchar(255)
,`current_price` decimal(10,2)
,`discount_percentage` decimal(17,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL COMMENT 'คำสั่งซื้อที่เกี่ยวข้อง',
  `rating` int(11) NOT NULL COMMENT 'คะแนน 1-5 (ต้องตรวจสอบในแอปพลิเคชั่น)',
  `title` varchar(200) DEFAULT NULL COMMENT 'หัวข้อรีวิว',
  `comment` text DEFAULT NULL COMMENT 'ความคิดเห็น',
  `images` text DEFAULT NULL COMMENT 'รูปภาพรีวิว (JSON)',
  `pros` text DEFAULT NULL COMMENT 'ข้อดี',
  `cons` text DEFAULT NULL COMMENT 'ข้อเสีย',
  `would_recommend` tinyint(1) DEFAULT 1 COMMENT 'แนะนำให้ผู้อื่นหรือไม่',
  `helpful_count` int(11) DEFAULT 0 COMMENT 'จำนวนคนที่คิดว่ารีวิวนี้มีประโยชน์',
  `verified_purchase` tinyint(1) DEFAULT 0 COMMENT 'ซื้อสินค้าจริง',
  `status` enum('pending','approved','rejected') DEFAULT 'pending' COMMENT 'สถานะ',
  `admin_reply` text DEFAULT NULL COMMENT 'การตอบกลับจากแอดมิน',
  `replied_at` timestamp NULL DEFAULT NULL COMMENT 'วันที่ตอบกลับ',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `sales_statistics`
-- (See below for the actual view)
--
CREATE TABLE `sales_statistics` (
`sale_date` date
,`total_orders` bigint(21)
,`total_revenue` decimal(32,2)
,`average_order_value` decimal(14,6)
,`confirmed_revenue` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `shipping_methods`
--

CREATE TABLE `shipping_methods` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'ชื่อวิธีการจัดส่ง',
  `description` text DEFAULT NULL COMMENT 'คำอธิบาย',
  `cost` decimal(10,2) NOT NULL COMMENT 'ค่าจัดส่ง',
  `free_shipping_min_amount` decimal(10,2) DEFAULT NULL COMMENT 'ยอดขั้นต่ำสำหรับฟรีค่าจัดส่ง',
  `estimated_days` varchar(50) DEFAULT NULL COMMENT 'ระยะเวลาจัดส่งโดยประมาณ',
  `weight_limit` decimal(8,2) DEFAULT NULL COMMENT 'น้ำหนักสูงสุด (กก.)',
  `coverage_area` text DEFAULT NULL COMMENT 'พื้นที่ให้บริการ',
  `status` enum('active','inactive') DEFAULT 'active' COMMENT 'สถานะ',
  `sort_order` int(11) DEFAULT 0 COMMENT 'ลำดับการแสดง',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL COMMENT 'คีย์การตั้งค่า',
  `setting_value` text DEFAULT NULL COMMENT 'ค่าการตั้งค่า',
  `setting_type` enum('text','textarea','number','boolean','json','file') DEFAULT 'text' COMMENT 'ประเภทการตั้งค่า',
  `category` varchar(50) DEFAULT 'general' COMMENT 'หมวดหมู่การตั้งค่า',
  `description` text DEFAULT NULL COMMENT 'คำอธิบาย',
  `is_editable` tinyint(1) DEFAULT 1 COMMENT 'แก้ไขได้หรือไม่',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `category`, `description`, `is_editable`, `created_at`, `updated_at`) VALUES
(1, 'site_title', 'ข้าวพันธุ์พื้นเมืองเลย', 'text', 'general', 'ชื่อเว็บไซต์', 1, '2025-07-06 12:42:55', '2025-07-06 12:42:55'),
(2, 'site_tagline', 'อนุรักษ์และสืบสานความเป็นไทย', 'text', 'general', 'คำขวัญเว็บไซต์', 1, '2025-07-06 12:42:55', '2025-07-06 12:42:55'),
(3, 'site_description', 'ร้านขายข้าวพันธุ์พื้นเมืองและผลิตภัณฑ์จากจังหวัดเลย อนุรักษ์และสืบสานความเป็นไทยด้วยสินค้าคุณภาพจากธรรมชาติ', 'textarea', 'general', 'คำอธิบายเว็บไซต์', 1, '2025-07-06 12:42:55', '2025-07-06 12:42:55'),
(4, 'contact_phone', '081-234-5678', 'text', 'contact', 'เบอร์โทรติดต่อ', 1, '2025-07-06 12:42:55', '2025-07-06 12:42:55'),
(5, 'contact_email', 'info@loeirice.com', 'text', 'contact', 'อีเมลติดต่อ', 1, '2025-07-06 12:42:55', '2025-07-06 12:42:55'),
(6, 'contact_address', 'บ้านศรีเจริญ อำเภอภูหลวง จังหวัดเลย', 'textarea', 'contact', 'ที่อยู่', 1, '2025-07-06 12:42:55', '2025-07-06 12:42:55'),
(7, 'facebook_url', 'https://facebook.com/loeirice', 'text', 'social', 'Facebook Page', 1, '2025-07-06 12:42:55', '2025-07-06 12:42:55'),
(8, 'line_id', '@loeirice', 'text', 'social', 'LINE Official Account', 1, '2025-07-06 12:42:55', '2025-07-06 12:42:55'),
(9, 'bank_account_name', 'กลุ่มวิสาหกิจชุมชนข้าวพันธุ์พื้นเมืองเลย', 'text', 'payment', 'ชื่อบัญชีธนาคาร', 1, '2025-07-06 12:42:55', '2025-07-06 12:42:55'),
(10, 'bank_account_number', '123-4-56789-0', 'text', 'payment', 'เลขที่บัญชีธนาคาร', 1, '2025-07-06 12:42:55', '2025-07-06 12:42:55'),
(11, 'bank_name', 'ธนาคารกรุงไทย', 'text', 'payment', 'ชื่อธนาคาร', 1, '2025-07-06 12:42:55', '2025-07-06 12:42:55'),
(12, 'promptpay_number', '0812345678', 'text', 'payment', 'เบอร์พร้อมเพย์', 1, '2025-07-06 12:42:55', '2025-07-06 12:42:55'),
(13, 'free_shipping_amount', '1000.00', 'number', 'shipping', 'ยอดสั่งซื้อขั้นต่ำสำหรับฟรีค่าจัดส่ง', 1, '2025-07-06 12:42:55', '2025-07-06 12:42:55'),
(14, 'tax_rate', '0.00', 'number', 'pricing', 'อัตราภาษี (%)', 1, '2025-07-06 12:42:55', '2025-07-06 12:42:55'),
(15, 'currency_symbol', '฿', 'text', 'pricing', 'สัญลักษณ์สกุลเงิน', 1, '2025-07-06 12:42:55', '2025-07-06 12:42:55'),
(16, 'timezone', 'Asia/Bangkok', 'text', 'general', 'เขตเวลา', 1, '2025-07-06 12:42:55', '2025-07-06 12:42:55'),
(17, 'products_per_page', '12', 'number', 'display', 'จำนวนสินค้าต่อหน้า', 1, '2025-07-06 12:42:55', '2025-07-06 12:42:55'),
(18, 'featured_products_count', '6', 'number', 'display', 'จำนวนสินค้าแนะนำในหน้าแรก', 1, '2025-07-06 12:42:55', '2025-07-06 12:42:55'),
(19, 'reviews_require_approval', 'true', 'boolean', 'reviews', 'รีวิวต้องอนุมัติก่อนแสดง', 1, '2025-07-06 12:42:55', '2025-07-06 12:42:55'),
(20, 'order_number_prefix', 'LR', 'text', 'orders', 'คำนำหน้าเลขที่ออเดอร์', 1, '2025-07-06 12:42:55', '2025-07-06 12:42:55'),
(21, 'email_from_name', 'ข้าวพันธุ์พื้นเมืองเลย', 'text', 'email', 'ชื่อผู้ส่งอีเมล', 1, '2025-07-06 12:42:55', '2025-07-06 12:42:55'),
(22, 'email_from_address', 'noreply@loeirice.com', 'text', 'email', 'อีเมลผู้ส่ง', 1, '2025-07-06 12:42:55', '2025-07-06 12:42:55'),
(23, 'maintenance_mode', 'false', 'boolean', 'system', 'โหมดปิดปรับปรุง', 1, '2025-07-06 12:42:55', '2025-07-06 12:42:55');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL COMMENT 'ชื่อ',
  `last_name` varchar(100) NOT NULL COMMENT 'นามสกุล',
  `email` varchar(255) NOT NULL COMMENT 'อีเมล (ไม่ซ้ำ)',
  `phone` varchar(20) DEFAULT NULL COMMENT 'เบอร์โทรศัพท์',
  `password` varchar(255) NOT NULL COMMENT 'รหัสผ่าน (hashed)',
  `date_of_birth` date DEFAULT NULL COMMENT 'วันเกิด',
  `gender` enum('male','female','other') DEFAULT NULL COMMENT 'เพศ',
  `profile_image` varchar(500) DEFAULT NULL COMMENT 'รูปโปรไฟล์',
  `status` enum('active','inactive','banned') NOT NULL DEFAULT 'active' COMMENT 'สถานะบัญชี',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'ยืนยันอีเมลแล้ว (0=ยัง, 1=แล้ว)',
  `verification_token` varchar(255) DEFAULT NULL COMMENT 'โทเค็นยืนยันอีเมล',
  `reset_token` varchar(255) DEFAULT NULL COMMENT 'โทเค็นรีเซ็ตรหัสผ่าน/Remember Me',
  `reset_token_expires` datetime DEFAULT NULL COMMENT 'วันหมดอายุโทเค็นรีเซ็ต',
  `last_login` timestamp NULL DEFAULT NULL COMMENT 'เข้าสู่ระบบครั้งสุดท้าย',
  `total_orders` int(11) NOT NULL DEFAULT 0 COMMENT 'จำนวนคำสั่งซื้อทั้งหมด',
  `total_spent` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'ยอดใช้จ่ายทั้งหมด (บาท)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่สร้างบัญชี',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'วันที่แก้ไขล่าสุด'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางสมาชิก';

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `phone`, `password`, `date_of_birth`, `gender`, `profile_image`, `status`, `email_verified`, `verification_token`, `reset_token`, `reset_token_expires`, `last_login`, `total_orders`, `total_spent`, `created_at`, `updated_at`) VALUES
(1, 'SANGSAN', 'LAPUNT', 'sunksunlapunt@gmail.com', '098-103-2797', '$2y$10$RnWXpVWcI8C5JRrDDcm.buRAsvQtpnMibWA64fYBuv1iq8jSlWXBe', '2523-08-31', 'male', NULL, 'active', 0, '735cc637444a84b48646e3d3fb257135ce627d77e780b36b5f4210ccce6e9f62', NULL, NULL, '2025-09-20 07:26:41', 0, 0.00, '2025-09-20 07:19:43', '2025-09-20 07:26:41');

-- --------------------------------------------------------

--
-- Structure for view `order_details`
--
DROP TABLE IF EXISTS `order_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `order_details`  AS SELECT `o`.`id` AS `id`, `o`.`user_id` AS `user_id`, `o`.`order_number` AS `order_number`, `o`.`status` AS `status`, `o`.`subtotal` AS `subtotal`, `o`.`shipping_cost` AS `shipping_cost`, `o`.`tax_amount` AS `tax_amount`, `o`.`discount_amount` AS `discount_amount`, `o`.`total_amount` AS `total_amount`, `o`.`payment_method` AS `payment_method`, `o`.`payment_status` AS `payment_status`, `o`.`payment_reference` AS `payment_reference`, `o`.`paid_at` AS `paid_at`, `o`.`shipping_method_id` AS `shipping_method_id`, `o`.`tracking_number` AS `tracking_number`, `o`.`shipping_notes` AS `shipping_notes`, `o`.`billing_address` AS `billing_address`, `o`.`shipping_address` AS `shipping_address`, `o`.`customer_notes` AS `customer_notes`, `o`.`admin_notes` AS `admin_notes`, `o`.`ordered_at` AS `ordered_at`, `o`.`confirmed_at` AS `confirmed_at`, `o`.`shipped_at` AS `shipped_at`, `o`.`delivered_at` AS `delivered_at`, `o`.`cancelled_at` AS `cancelled_at`, `o`.`cancel_reason` AS `cancel_reason`, `o`.`created_at` AS `created_at`, `o`.`updated_at` AS `updated_at`, concat(`u`.`first_name`,' ',`u`.`last_name`) AS `customer_name`, `u`.`email` AS `customer_email`, `u`.`phone` AS `customer_phone`, `sm`.`name` AS `shipping_method_name`, `sm`.`estimated_days` AS `shipping_estimated_days` FROM ((`orders` `o` left join `users` `u` on(`o`.`user_id` = `u`.`id`)) left join `shipping_methods` `sm` on(`o`.`shipping_method_id` = `sm`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `product_details`
--
DROP TABLE IF EXISTS `product_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `product_details`  AS SELECT `p`.`id` AS `id`, `p`.`category_id` AS `category_id`, `p`.`name` AS `name`, `p`.`description` AS `description`, `p`.`short_description` AS `short_description`, `p`.`price` AS `price`, `p`.`sale_price` AS `sale_price`, `p`.`stock_quantity` AS `stock_quantity`, `p`.`min_stock_level` AS `min_stock_level`, `p`.`weight` AS `weight`, `p`.`unit` AS `unit`, `p`.`image_main` AS `image_main`, `p`.`image_gallery` AS `image_gallery`, `p`.`features` AS `features`, `p`.`ingredients` AS `ingredients`, `p`.`benefits` AS `benefits`, `p`.`usage_instructions` AS `usage_instructions`, `p`.`storage_instructions` AS `storage_instructions`, `p`.`origin` AS `origin`, `p`.`harvest_season` AS `harvest_season`, `p`.`certification` AS `certification`, `p`.`status` AS `status`, `p`.`featured` AS `featured`, `p`.`is_new` AS `is_new`, `p`.`view_count` AS `view_count`, `p`.`rating_average` AS `rating_average`, `p`.`rating_count` AS `rating_count`, `p`.`meta_title` AS `meta_title`, `p`.`meta_description` AS `meta_description`, `p`.`slug` AS `slug`, `p`.`created_at` AS `created_at`, `p`.`updated_at` AS `updated_at`, `c`.`name` AS `category_name`, `c`.`slug` AS `category_slug`, CASE WHEN `p`.`sale_price` is not null AND `p`.`sale_price` > 0 THEN `p`.`sale_price` ELSE `p`.`price` END AS `current_price`, CASE WHEN `p`.`sale_price` is not null AND `p`.`sale_price` > 0 THEN round((`p`.`price` - `p`.`sale_price`) / `p`.`price` * 100,2) ELSE 0 END AS `discount_percentage` FROM (`products` `p` left join `categories` `c` on(`p`.`category_id` = `c`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `sales_statistics`
--
DROP TABLE IF EXISTS `sales_statistics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `sales_statistics`  AS SELECT cast(`orders`.`ordered_at` as date) AS `sale_date`, count(0) AS `total_orders`, sum(`orders`.`total_amount`) AS `total_revenue`, avg(`orders`.`total_amount`) AS `average_order_value`, sum(case when `orders`.`status` = 'delivered' then `orders`.`total_amount` else 0 end) AS `confirmed_revenue` FROM `orders` WHERE `orders`.`status` not in ('cancelled','refunded') GROUP BY cast(`orders`.`ordered_at` as date) ORDER BY cast(`orders`.`ordered_at` as date) DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `action` (`action`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_default` (`is_default`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_role` (`role`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `shipping_method_id` (`shipping_method_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_order_number` (`order_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_ordered_date` (`ordered_at`),
  ADD KEY `idx_orders_date_range` (`ordered_at`,`status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `payment_notifications`
--
ALTER TABLE `payment_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `order_number` (`order_number`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_featured` (`featured`),
  ADD KEY `idx_price` (`price`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_products_search` (`name`),
  ADD KEY `idx_products_price_range` (`price`,`sale_price`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_reviews_approved` (`product_id`,`status`,`rating`);

--
-- Indexes for table `shipping_methods`
--
ALTER TABLE `shipping_methods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_key` (`setting_key`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `payment_notifications`
--
ALTER TABLE `payment_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipping_methods`
--
ALTER TABLE `shipping_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_methods` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payment_notifications`
--
ALTER TABLE `payment_notifications`
  ADD CONSTRAINT `payment_notifications_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
