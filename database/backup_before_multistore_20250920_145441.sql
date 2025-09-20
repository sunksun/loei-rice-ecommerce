-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for osx10.10 (x86_64)
--
-- Host: localhost    Database: loei_rice_ecommerce
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `admin_id` (`admin_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_default` (`is_default`),
  CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `addresses`
--

LOCK TABLES `addresses` WRITE;
/*!40000 ALTER TABLE `addresses` DISABLE KEYS */;
/*!40000 ALTER TABLE `addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `created_by` (`created_by`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_role` (`role`),
  CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'admin','admin@loeirice.com','$2y$10$bUEAyL02qCo3zQuf92Ys4OWkaKoo5Eh0XvU16/V6tvo8AqB8ETzm.','ผู้ดูแล','ระบบ','super_admin',NULL,NULL,'active','2025-09-08 15:10:05','::1',5,'2025-09-20 03:00:00',NULL,'2025-07-06 12:42:55','2025-09-20 07:30:00');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL COMMENT 'Session ID สำหรับผู้ใช้ที่ไม่ได้ล็อกอิน',
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1 COMMENT 'จำนวน',
  `price` decimal(10,2) NOT NULL COMMENT 'ราคา ณ เวลาที่เพิ่มลงตะกร้า',
  `product_options` text DEFAULT NULL COMMENT 'ตัวเลือกสินค้า (JSON)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_session` (`session_id`),
  KEY `idx_product` (`product_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart`
--

LOCK TABLES `cart` WRITE;
/*!40000 ALTER TABLE `cart` DISABLE KEYS */;
/*!40000 ALTER TABLE `cart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT 'ชื่อหมวดหมู่',
  `description` text DEFAULT NULL COMMENT 'คำอธิบายหมวดหมู่',
  `image` varchar(255) DEFAULT NULL COMMENT 'รูปภาพหมวดหมู่',
  `slug` varchar(255) DEFAULT NULL COMMENT 'URL slug',
  `sort_order` int(11) DEFAULT 0 COMMENT 'ลำดับการแสดง',
  `status` enum('active','inactive') DEFAULT 'active' COMMENT 'สถานะ',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'ข้าวพันธุ์พื้นเมือง','ข้าวเหนียวแดงและข้าวเหนียวซิวเกลี้ยงเมืองเลย พันธุ์พื้นเมืองแท้จากจังหวัดเลย',NULL,'rice-varieties',1,'active','2025-07-06 12:42:55','2025-07-07 03:16:00'),(2,'ผลิตภัณฑ์อาหาร','ข้าวพอง ข้าวกระยาสารท และผลิตภัณฑ์อาหารแปรรูปจากข้าวพันธุ์พื้นเมือง',NULL,'food-products',2,'active','2025-07-06 12:42:55','2025-07-06 12:42:55'),(3,'เครื่องสำอางธรรมชาติ','ครีมบำรุงผิว สบู่ และผลิตภัณฑ์เครื่องสำอางที่ทำจากข้าวพันธุ์พื้นเมือง',NULL,'natural-cosmetics',3,'active','2025-07-06 12:42:55','2025-07-06 12:42:55');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `order_details`
--

DROP TABLE IF EXISTS `order_details`;
/*!50001 DROP VIEW IF EXISTS `order_details`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `order_details` AS SELECT
 1 AS `id`,
  1 AS `user_id`,
  1 AS `order_number`,
  1 AS `status`,
  1 AS `subtotal`,
  1 AS `shipping_cost`,
  1 AS `tax_amount`,
  1 AS `discount_amount`,
  1 AS `total_amount`,
  1 AS `payment_method`,
  1 AS `payment_status`,
  1 AS `payment_reference`,
  1 AS `paid_at`,
  1 AS `shipping_method_id`,
  1 AS `tracking_number`,
  1 AS `shipping_notes`,
  1 AS `billing_address`,
  1 AS `shipping_address`,
  1 AS `customer_notes`,
  1 AS `admin_notes`,
  1 AS `ordered_at`,
  1 AS `confirmed_at`,
  1 AS `shipped_at`,
  1 AS `delivered_at`,
  1 AS `cancelled_at`,
  1 AS `cancel_reason`,
  1 AS `created_at`,
  1 AS `updated_at`,
  1 AS `customer_name`,
  1 AS `customer_email`,
  1 AS `customer_phone`,
  1 AS `shipping_method_name`,
  1 AS `shipping_estimated_days` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(200) NOT NULL COMMENT 'ชื่อสินค้า (ณ เวลาที่สั่งซื้อ)',
  `product_price` decimal(10,2) NOT NULL COMMENT 'ราคาสินค้า (ณ เวลาที่สั่งซื้อ)',
  `product_image` varchar(255) DEFAULT NULL COMMENT 'รูปภาพสินค้า',
  `quantity` int(11) NOT NULL COMMENT 'จำนวน',
  `total_price` decimal(10,2) NOT NULL COMMENT 'ราคารวม',
  `product_options` text DEFAULT NULL COMMENT 'ตัวเลือกสินค้า (JSON)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_product` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `shipping_method_id` (`shipping_method_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_order_number` (`order_number`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_ordered_date` (`ordered_at`),
  KEY `idx_orders_date_range` (`ordered_at`,`status`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_methods` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_notifications`
--

DROP TABLE IF EXISTS `payment_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `order_number` (`order_number`),
  CONSTRAINT `payment_notifications_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_notifications`
--

LOCK TABLES `payment_notifications` WRITE;
/*!40000 ALTER TABLE `payment_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `product_details`
--

DROP TABLE IF EXISTS `product_details`;
/*!50001 DROP VIEW IF EXISTS `product_details`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `product_details` AS SELECT
 1 AS `id`,
  1 AS `category_id`,
  1 AS `name`,
  1 AS `description`,
  1 AS `short_description`,
  1 AS `price`,
  1 AS `sale_price`,
  1 AS `stock_quantity`,
  1 AS `min_stock_level`,
  1 AS `weight`,
  1 AS `unit`,
  1 AS `image_main`,
  1 AS `image_gallery`,
  1 AS `features`,
  1 AS `ingredients`,
  1 AS `benefits`,
  1 AS `usage_instructions`,
  1 AS `storage_instructions`,
  1 AS `origin`,
  1 AS `harvest_season`,
  1 AS `certification`,
  1 AS `status`,
  1 AS `featured`,
  1 AS `is_new`,
  1 AS `view_count`,
  1 AS `rating_average`,
  1 AS `rating_count`,
  1 AS `meta_title`,
  1 AS `meta_description`,
  1 AS `slug`,
  1 AS `created_at`,
  1 AS `updated_at`,
  1 AS `category_name`,
  1 AS `category_slug`,
  1 AS `current_price`,
  1 AS `discount_percentage` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_category` (`category_id`),
  KEY `idx_status` (`status`),
  KEY `idx_featured` (`featured`),
  KEY `idx_price` (`price`),
  KEY `idx_slug` (`slug`),
  KEY `idx_products_search` (`name`),
  KEY `idx_products_price_range` (`price`,`sale_price`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (8,2,'Sticky Rice Crackers','วัตถุดิบ\r\n• ข้าวเหนียวสายพันธุ์ซิวเกลี้ยงที่ผ่านการอบพอง 50%\r\n• ข้าวสารหอมมะลิ 50%','ผลิตภัณฑ์ Sticky Rice Crackers จากข้าวเหนียว GI จังหวัดเลย สายพันธุ์ซิวเกลี้ยง',35.00,NULL,50,10,50.00,'ห่อ','product_1757321995_6158.png',NULL,'','ผลิตภัณฑ์นี้ใช้วัตถุดิบจาก ข้าวเหนียวซิวเกลี้ยงอบพอง 50% ผสมกับ ข้าวหอมมะลิอีก 50% ผ่านกรรมวิธีเฉพาะ จนได้เป็นข้าวพองแผ่นกลม สีเหลืองอ่อน สม่ำเสมอ กรอบอร่อยมาก ๆ','','•ทา แยมผลไม้ อย่างสตรอว์เบอร์รี่ บลูเบอร์รี่ หรือส้ม จะได้รสหวานอมเปรี้ยว ตัดกับความกรอบอย่างลงตัว\r\n•หรือชอบช็อกโกแลต ลองทา นูเทลล่า หวานมันเข้มข้น\r\n•กินคู่กับชา กาแฟ หรือเป็นของว่างยามบ่ายก็ดี','','','','อย. 42-2-00353-2-0011','active',0,1,3,0.00,0,NULL,NULL,NULL,'2025-09-08 08:59:55','2025-09-20 07:27:43');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_rating` (`rating`),
  KEY `idx_reviews_approved` (`product_id`,`status`,`rating`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `sales_statistics`
--

DROP TABLE IF EXISTS `sales_statistics`;
/*!50001 DROP VIEW IF EXISTS `sales_statistics`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `sales_statistics` AS SELECT
 1 AS `sale_date`,
  1 AS `total_orders`,
  1 AS `total_revenue`,
  1 AS `average_order_value`,
  1 AS `confirmed_revenue` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `shipping_methods`
--

DROP TABLE IF EXISTS `shipping_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shipping_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipping_methods`
--

LOCK TABLES `shipping_methods` WRITE;
/*!40000 ALTER TABLE `shipping_methods` DISABLE KEYS */;
/*!40000 ALTER TABLE `shipping_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_settings`
--

DROP TABLE IF EXISTS `site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL COMMENT 'คีย์การตั้งค่า',
  `setting_value` text DEFAULT NULL COMMENT 'ค่าการตั้งค่า',
  `setting_type` enum('text','textarea','number','boolean','json','file') DEFAULT 'text' COMMENT 'ประเภทการตั้งค่า',
  `category` varchar(50) DEFAULT 'general' COMMENT 'หมวดหมู่การตั้งค่า',
  `description` text DEFAULT NULL COMMENT 'คำอธิบาย',
  `is_editable` tinyint(1) DEFAULT 1 COMMENT 'แก้ไขได้หรือไม่',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_key` (`setting_key`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
INSERT INTO `site_settings` VALUES (1,'site_title','ข้าวพันธุ์พื้นเมืองเลย','text','general','ชื่อเว็บไซต์',1,'2025-07-06 12:42:55','2025-07-06 12:42:55'),(2,'site_tagline','อนุรักษ์และสืบสานความเป็นไทย','text','general','คำขวัญเว็บไซต์',1,'2025-07-06 12:42:55','2025-07-06 12:42:55'),(3,'site_description','ร้านขายข้าวพันธุ์พื้นเมืองและผลิตภัณฑ์จากจังหวัดเลย อนุรักษ์และสืบสานความเป็นไทยด้วยสินค้าคุณภาพจากธรรมชาติ','textarea','general','คำอธิบายเว็บไซต์',1,'2025-07-06 12:42:55','2025-07-06 12:42:55'),(4,'contact_phone','081-234-5678','text','contact','เบอร์โทรติดต่อ',1,'2025-07-06 12:42:55','2025-07-06 12:42:55'),(5,'contact_email','info@loeirice.com','text','contact','อีเมลติดต่อ',1,'2025-07-06 12:42:55','2025-07-06 12:42:55'),(6,'contact_address','บ้านศรีเจริญ อำเภอภูหลวง จังหวัดเลย','textarea','contact','ที่อยู่',1,'2025-07-06 12:42:55','2025-07-06 12:42:55'),(7,'facebook_url','https://facebook.com/loeirice','text','social','Facebook Page',1,'2025-07-06 12:42:55','2025-07-06 12:42:55'),(8,'line_id','@loeirice','text','social','LINE Official Account',1,'2025-07-06 12:42:55','2025-07-06 12:42:55'),(9,'bank_account_name','กลุ่มวิสาหกิจชุมชนข้าวพันธุ์พื้นเมืองเลย','text','payment','ชื่อบัญชีธนาคาร',1,'2025-07-06 12:42:55','2025-07-06 12:42:55'),(10,'bank_account_number','123-4-56789-0','text','payment','เลขที่บัญชีธนาคาร',1,'2025-07-06 12:42:55','2025-07-06 12:42:55'),(11,'bank_name','ธนาคารกรุงไทย','text','payment','ชื่อธนาคาร',1,'2025-07-06 12:42:55','2025-07-06 12:42:55'),(12,'promptpay_number','0812345678','text','payment','เบอร์พร้อมเพย์',1,'2025-07-06 12:42:55','2025-07-06 12:42:55'),(13,'free_shipping_amount','1000.00','number','shipping','ยอดสั่งซื้อขั้นต่ำสำหรับฟรีค่าจัดส่ง',1,'2025-07-06 12:42:55','2025-07-06 12:42:55'),(14,'tax_rate','0.00','number','pricing','อัตราภาษี (%)',1,'2025-07-06 12:42:55','2025-07-06 12:42:55'),(15,'currency_symbol','฿','text','pricing','สัญลักษณ์สกุลเงิน',1,'2025-07-06 12:42:55','2025-07-06 12:42:55'),(16,'timezone','Asia/Bangkok','text','general','เขตเวลา',1,'2025-07-06 12:42:55','2025-07-06 12:42:55'),(17,'products_per_page','12','number','display','จำนวนสินค้าต่อหน้า',1,'2025-07-06 12:42:55','2025-07-06 12:42:55'),(18,'featured_products_count','6','number','display','จำนวนสินค้าแนะนำในหน้าแรก',1,'2025-07-06 12:42:55','2025-07-06 12:42:55'),(19,'reviews_require_approval','true','boolean','reviews','รีวิวต้องอนุมัติก่อนแสดง',1,'2025-07-06 12:42:55','2025-07-06 12:42:55'),(20,'order_number_prefix','LR','text','orders','คำนำหน้าเลขที่ออเดอร์',1,'2025-07-06 12:42:55','2025-07-06 12:42:55'),(21,'email_from_name','ข้าวพันธุ์พื้นเมืองเลย','text','email','ชื่อผู้ส่งอีเมล',1,'2025-07-06 12:42:55','2025-07-06 12:42:55'),(22,'email_from_address','noreply@loeirice.com','text','email','อีเมลผู้ส่ง',1,'2025-07-06 12:42:55','2025-07-06 12:42:55'),(23,'maintenance_mode','false','boolean','system','โหมดปิดปรับปรุง',1,'2025-07-06 12:42:55','2025-07-06 12:42:55');
/*!40000 ALTER TABLE `site_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'วันที่แก้ไขล่าสุด',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางสมาชิก';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'SANGSAN','LAPUNT','sunksunlapunt@gmail.com','098-103-2797','$2y$10$RnWXpVWcI8C5JRrDDcm.buRAsvQtpnMibWA64fYBuv1iq8jSlWXBe','2523-08-31','male',NULL,'active',0,'735cc637444a84b48646e3d3fb257135ce627d77e780b36b5f4210ccce6e9f62',NULL,NULL,'2025-09-20 07:26:41',0,0.00,'2025-09-20 07:19:43','2025-09-20 07:26:41');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Final view structure for view `order_details`
--

/*!50001 DROP VIEW IF EXISTS `order_details`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `order_details` AS select `o`.`id` AS `id`,`o`.`user_id` AS `user_id`,`o`.`order_number` AS `order_number`,`o`.`status` AS `status`,`o`.`subtotal` AS `subtotal`,`o`.`shipping_cost` AS `shipping_cost`,`o`.`tax_amount` AS `tax_amount`,`o`.`discount_amount` AS `discount_amount`,`o`.`total_amount` AS `total_amount`,`o`.`payment_method` AS `payment_method`,`o`.`payment_status` AS `payment_status`,`o`.`payment_reference` AS `payment_reference`,`o`.`paid_at` AS `paid_at`,`o`.`shipping_method_id` AS `shipping_method_id`,`o`.`tracking_number` AS `tracking_number`,`o`.`shipping_notes` AS `shipping_notes`,`o`.`billing_address` AS `billing_address`,`o`.`shipping_address` AS `shipping_address`,`o`.`customer_notes` AS `customer_notes`,`o`.`admin_notes` AS `admin_notes`,`o`.`ordered_at` AS `ordered_at`,`o`.`confirmed_at` AS `confirmed_at`,`o`.`shipped_at` AS `shipped_at`,`o`.`delivered_at` AS `delivered_at`,`o`.`cancelled_at` AS `cancelled_at`,`o`.`cancel_reason` AS `cancel_reason`,`o`.`created_at` AS `created_at`,`o`.`updated_at` AS `updated_at`,concat(`u`.`first_name`,' ',`u`.`last_name`) AS `customer_name`,`u`.`email` AS `customer_email`,`u`.`phone` AS `customer_phone`,`sm`.`name` AS `shipping_method_name`,`sm`.`estimated_days` AS `shipping_estimated_days` from ((`orders` `o` left join `users` `u` on(`o`.`user_id` = `u`.`id`)) left join `shipping_methods` `sm` on(`o`.`shipping_method_id` = `sm`.`id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `product_details`
--

/*!50001 DROP VIEW IF EXISTS `product_details`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `product_details` AS select `p`.`id` AS `id`,`p`.`category_id` AS `category_id`,`p`.`name` AS `name`,`p`.`description` AS `description`,`p`.`short_description` AS `short_description`,`p`.`price` AS `price`,`p`.`sale_price` AS `sale_price`,`p`.`stock_quantity` AS `stock_quantity`,`p`.`min_stock_level` AS `min_stock_level`,`p`.`weight` AS `weight`,`p`.`unit` AS `unit`,`p`.`image_main` AS `image_main`,`p`.`image_gallery` AS `image_gallery`,`p`.`features` AS `features`,`p`.`ingredients` AS `ingredients`,`p`.`benefits` AS `benefits`,`p`.`usage_instructions` AS `usage_instructions`,`p`.`storage_instructions` AS `storage_instructions`,`p`.`origin` AS `origin`,`p`.`harvest_season` AS `harvest_season`,`p`.`certification` AS `certification`,`p`.`status` AS `status`,`p`.`featured` AS `featured`,`p`.`is_new` AS `is_new`,`p`.`view_count` AS `view_count`,`p`.`rating_average` AS `rating_average`,`p`.`rating_count` AS `rating_count`,`p`.`meta_title` AS `meta_title`,`p`.`meta_description` AS `meta_description`,`p`.`slug` AS `slug`,`p`.`created_at` AS `created_at`,`p`.`updated_at` AS `updated_at`,`c`.`name` AS `category_name`,`c`.`slug` AS `category_slug`,case when `p`.`sale_price` is not null and `p`.`sale_price` > 0 then `p`.`sale_price` else `p`.`price` end AS `current_price`,case when `p`.`sale_price` is not null and `p`.`sale_price` > 0 then round((`p`.`price` - `p`.`sale_price`) / `p`.`price` * 100,2) else 0 end AS `discount_percentage` from (`products` `p` left join `categories` `c` on(`p`.`category_id` = `c`.`id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `sales_statistics`
--

/*!50001 DROP VIEW IF EXISTS `sales_statistics`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `sales_statistics` AS select cast(`orders`.`ordered_at` as date) AS `sale_date`,count(0) AS `total_orders`,sum(`orders`.`total_amount`) AS `total_revenue`,avg(`orders`.`total_amount`) AS `average_order_value`,sum(case when `orders`.`status` = 'delivered' then `orders`.`total_amount` else 0 end) AS `confirmed_revenue` from `orders` where `orders`.`status` not in ('cancelled','refunded') group by cast(`orders`.`ordered_at` as date) order by cast(`orders`.`ordered_at` as date) desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-09-20 14:54:41
