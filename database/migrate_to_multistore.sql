-- Migration Script: Multi-Store System
-- Version: 1.0
-- Description: เพิ่มระบบหลายร้านค้าและระบบจัดการสิทธิ์

SET FOREIGN_KEY_CHECKS = 0;

-- 1. สร้างตาราง stores
CREATE TABLE IF NOT EXISTS `stores` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `store_name` VARCHAR(255) NOT NULL COMMENT 'ชื่อร้าน',
    `store_code` VARCHAR(50) UNIQUE NOT NULL COMMENT 'รหัสร้าน',
    `owner_name` VARCHAR(255) COMMENT 'ชื่อเจ้าของร้าน',
    `phone` VARCHAR(20) COMMENT 'เบอร์โทรศัพท์',
    `email` VARCHAR(255) COMMENT 'อีเมล',
    `address` TEXT COMMENT 'ที่อยู่',
    `logo_url` VARCHAR(500) COMMENT 'URL โลโก้ร้าน',
    `description` TEXT COMMENT 'คำอธิบายร้าน',
    `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active' COMMENT 'สถานะร้าน',
    `settings` JSON COMMENT 'การตั้งค่าร้าน',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. สร้างตาราง admin_roles
CREATE TABLE IF NOT EXISTS `admin_roles` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `role_name` VARCHAR(100) NOT NULL COMMENT 'ชื่อตำแหน่ง',
    `role_code` VARCHAR(50) UNIQUE NOT NULL COMMENT 'รหัสตำแหน่ง',
    `description` TEXT COMMENT 'คำอธิบายตำแหน่ง',
    `level` INT DEFAULT 1 COMMENT 'ระดับสิทธิ์ (1-5)',
    `can_manage_stores` BOOLEAN DEFAULT FALSE COMMENT 'สามารถจัดการร้านได้',
    `can_manage_admins` BOOLEAN DEFAULT FALSE COMMENT 'สามารถจัดการ admin ได้',
    `permissions` JSON COMMENT 'สิทธิ์การเข้าถึง',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Insert default roles
INSERT INTO `admin_roles` (`role_name`, `role_code`, `description`, `level`, `can_manage_stores`, `can_manage_admins`, `permissions`) VALUES
('Super Administrator', 'super_admin', 'ผู้ดูแลระบบสูงสุด สามารถจัดการทุกอย่างในระบบ', 5, TRUE, TRUE, '["all", "system_config", "store_manage", "admin_manage", "global_reports", "financial_reports"]'),
('Store Owner', 'store_owner', 'เจ้าของร้าน สามารถจัดการร้านและพนักงานของตัวเอง', 3, FALSE, FALSE, '["store_manage", "product_manage", "order_manage", "staff_manage", "store_reports"]'),
('Store Manager', 'store_manager', 'ผู้จัดการร้าน สามารถจัดการสินค้าและคำสั่งซื้อ', 2, FALSE, FALSE, '["product_manage", "order_manage", "inventory_manage", "customer_service"]'),
('Store Staff', 'store_staff', 'พนักงานร้าน สามารถดูและจัดการคำสั่งซื้อ', 1, FALSE, FALSE, '["order_view", "order_update", "product_view", "customer_service"]');

-- 4. สร้าง default store สำหรับข้อมูลเดิม
INSERT INTO `stores` (`store_name`, `store_code`, `owner_name`, `description`, `status`) VALUES
('ข้าวพันธุ์พื้นเมืองเลย - สำนักงานหลัก', 'MAIN', 'ผู้ดูแลระบบ', 'สำนักงานหลักของระบบข้าวพันธุ์พื้นเมืองเลย', 'active');

-- 5. เพิ่มคอลัมน์ store_id และ role_id ในตาราง admins
ALTER TABLE `admins` 
ADD COLUMN `store_id` INT NULL AFTER `id`,
ADD COLUMN `role_id` INT NULL AFTER `store_id`;

-- 6. เพิ่ม Foreign Key constraints
ALTER TABLE `admins` 
ADD CONSTRAINT `fk_admins_store` FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE SET NULL,
ADD CONSTRAINT `fk_admins_role` FOREIGN KEY (`role_id`) REFERENCES `admin_roles`(`id`) ON DELETE SET NULL;

-- 7. อัพเดต admin ที่มีอยู่ให้เป็น super_admin ในร้านหลัก
UPDATE `admins` SET 
    `store_id` = 1,
    `role_id` = 1 
WHERE `role` = 'admin' OR `role` = 'super_admin';

-- 8. เพิ่มคอลัมน์ store_id ในตาราง products
ALTER TABLE `products` 
ADD COLUMN `store_id` INT NULL AFTER `id`;

-- 9. เพิ่ม Foreign Key constraint สำหรับ products
ALTER TABLE `products` 
ADD CONSTRAINT `fk_products_store` FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE SET NULL;

-- 10. อัพเดตสินค้าที่มีอยู่ให้เป็นของร้านหลัก
UPDATE `products` SET `store_id` = 1 WHERE `store_id` IS NULL;

-- 11. เพิ่มคอลัมน์ store_id ในตาราง categories (ถ้าต้องการแยกหมวดหมู่ตามร้าน)
ALTER TABLE `categories` 
ADD COLUMN `store_id` INT NULL AFTER `id`;

ALTER TABLE `categories` 
ADD CONSTRAINT `fk_categories_store` FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE SET NULL;

-- 12. อัพเดตหมวดหมู่ที่มีอยู่ให้เป็นของร้านหลัก
UPDATE `categories` SET `store_id` = 1 WHERE `store_id` IS NULL;

-- 13. สร้างตาราง store_settings สำหรับการตั้งค่าเฉพาะร้าน
CREATE TABLE IF NOT EXISTS `store_settings` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `store_id` INT NOT NULL,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT,
    `setting_type` ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    `description` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_store_setting` (`store_id`, `setting_key`),
    FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. สร้างตาราง admin_activities สำหรับติดตาม activity ของ admin
CREATE TABLE IF NOT EXISTS `admin_activities` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `admin_id` INT NOT NULL,
    `store_id` INT NULL,
    `action` VARCHAR(100) NOT NULL,
    `target_type` VARCHAR(50) NULL COMMENT 'product, order, user, etc.',
    `target_id` INT NULL,
    `description` TEXT,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_admin_activities_admin` (`admin_id`),
    INDEX `idx_admin_activities_store` (`store_id`),
    INDEX `idx_admin_activities_date` (`created_at`),
    FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. สร้าง Views สำหรับรายงาน
CREATE OR REPLACE VIEW `v_store_summary` AS
SELECT 
    s.id as store_id,
    s.store_name,
    s.store_code,
    s.status as store_status,
    COUNT(DISTINCT a.id) as admin_count,
    COUNT(DISTINCT p.id) as product_count,
    COUNT(DISTINCT CASE WHEN p.status = 'active' THEN p.id END) as active_products,
    COALESCE(SUM(p.stock_quantity), 0) as total_stock,
    s.created_at as store_created
FROM stores s
LEFT JOIN admins a ON s.id = a.store_id
LEFT JOIN products p ON s.id = p.store_id
GROUP BY s.id, s.store_name, s.store_code, s.status, s.created_at;

-- 16. สร้าง Indexes สำหรับ performance
CREATE INDEX `idx_admins_store_role` ON `admins`(`store_id`, `role_id`);
CREATE INDEX `idx_products_store_status` ON `products`(`store_id`, `status`);
CREATE INDEX `idx_categories_store` ON `categories`(`store_id`);

-- 17. สร้าง default store settings
INSERT INTO `store_settings` (`store_id`, `setting_key`, `setting_value`, `setting_type`, `description`) VALUES
(1, 'commission_rate', '0', 'number', 'อัตราค่าคอมมิชชั่น (%)'),
(1, 'auto_approve_products', 'true', 'boolean', 'อนุมัติสินค้าอัตโนมัติ'),
(1, 'max_products', '1000', 'number', 'จำนวนสินค้าสูงสุดที่อนุญาต'),
(1, 'notification_email', '', 'string', 'อีเมลสำหรับรับการแจ้งเตือน');

SET FOREIGN_KEY_CHECKS = 1;

-- Migration completed successfully
SELECT 'Multi-Store Migration Completed Successfully!' as status;