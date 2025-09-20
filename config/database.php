<?php

/**
 * Database Configuration Class
 * สำหรับจัดการการเชื่อมต่อฐานข้อมูล MySQL/MariaDB
 * 
 * @author Loei Rice E-commerce Team
 * @version 1.0
 */

class Database
{
    // การตั้งค่าฐานข้อมูล
    private $host = 'localhost';           // โฮสต์ฐานข้อมูล
    private $port = 3306;                  // พอร์ตฐานข้อมูล
    private $db_name = 'loei_rice_ecommerce';      // ชื่อฐานข้อมูล (XAMPP local)
    private $username = 'root';            // ชื่อผู้ใช้ฐานข้อมูล (XAMPP default)
    private $password = '';                // รหัสผ่านฐานข้อมูล (XAMPP default - empty)
    private $charset = 'utf8mb4';          // Character set

    // ตัวแปรเก็บการเชื่อมต่อ
    private $conn = null;
    private static $instance = null;

    // การตั้งค่า PDO
    private $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,     // แสดง error แบบ exception
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,           // ดึงข้อมูลแบบ associative array
        PDO::ATTR_EMULATE_PREPARES   => false,                     // ไม่ใช้ emulated prepared statements
        PDO::ATTR_PERSISTENT         => false,                     // ไม่ใช้ persistent connections
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",       // ตั้งค่า charset
        PDO::ATTR_TIMEOUT            => 30,                        // timeout 30 วินาที
    ];

    /**
     * Constructor - ป้องกันการสร้าง instance ใหม่
     */
    private function __construct()
    {
        // Singleton pattern
    }

    /**
     * ป้องกันการ clone object
     */
    private function __clone()
    {
        // ป้องกันการ clone
    }

    /**
     * ป้องกันการ unserialize
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }

    /**
     * รับ instance ของ Database (Singleton Pattern)
     * 
     * @return Database
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * เชื่อมต่อฐานข้อมูล
     * 
     * @return PDO
     * @throws Exception
     */
    public function getConnection()
    {
        if ($this->conn === null) {
            try {
                // สร้าง DSN (Data Source Name)
                $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset={$this->charset}";

                // เชื่อมต่อฐานข้อมูล
                $this->conn = new PDO($dsn, $this->username, $this->password, $this->options);

                // ตั้งค่า timezone
                $this->conn->exec("SET time_zone = '+07:00'");

                // ตั้งค่า SQL mode
                $this->conn->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");

                // Log การเชื่อมต่อสำเร็จ (ปิดไว้เพื่อลด error logs)
                // if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                //     error_log("Database connection established successfully");
                // }
            } catch (PDOException $e) {
                // จัดการ error การเชื่อมต่อ
                $this->handleConnectionError($e);
            }
        }

        return $this->conn;
    }

    /**
     * จัดการ error การเชื่อมต่อฐานข้อมูล
     * 
     * @param PDOException $e
     * @throws Exception
     */
    private function handleConnectionError(PDOException $e)
    {
        // บันทึก error log
        error_log("Database Connection Error: " . $e->getMessage());

        // แสดง error message ตามสภาพแวดล้อม
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            throw new Exception("Database Connection Error: " . $e->getMessage());
        } else {
            throw new Exception("ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาลองใหม่อีกครั้ง");
        }
    }

    /**
     * ทดสอบการเชื่อมต่อฐานข้อมูล
     * 
     * @return boolean
     */
    public function testConnection()
    {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->query("SELECT 1");
            return $stmt !== false;
        } catch (Exception $e) {
            error_log("Database test connection failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ปิดการเชื่อมต่อฐานข้อมูล
     */
    public function closeConnection()
    {
        $this->conn = null;
    }

    /**
     * ดึงข้อมูลสถิติฐานข้อมูล
     * 
     * @return array
     */
    public function getDatabaseStats()
    {
        try {
            $conn = $this->getConnection();

            $stats = [];

            // ข้อมูลฐานข้อมูล
            $stmt = $conn->query("SELECT DATABASE() as current_db");
            $stats['current_database'] = $stmt->fetch()['current_db'];

            // เวอร์ชัน MySQL/MariaDB
            $stmt = $conn->query("SELECT VERSION() as version");
            $stats['database_version'] = $stmt->fetch()['version'];

            // จำนวนตาราง
            $stmt = $conn->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = DATABASE()");
            $stats['table_count'] = $stmt->fetch()['table_count'];

            // ขนาดฐานข้อมูล
            $stmt = $conn->query("
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS db_size_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
            ");
            $stats['database_size_mb'] = $stmt->fetch()['db_size_mb'];

            return $stats;
        } catch (Exception $e) {
            error_log("Error getting database stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ตรวจสอบและสร้างตารางที่จำเป็น
     * 
     * @return boolean
     */
    public function checkRequiredTables()
    {
        $required_tables = [
            'admins',
            'users',
            'categories',
            'products',
            'orders',
            'order_items',
            'cart',
            'reviews',
            'addresses',
            'shipping_methods',
            'site_settings'
        ];

        try {
            $conn = $this->getConnection();

            foreach ($required_tables as $table) {
                $stmt = $conn->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table]);

                if ($stmt->rowCount() === 0) {
                    error_log("Required table '{$table}' not found");
                    return false;
                }
            }

            return true;
        } catch (Exception $e) {
            error_log("Error checking required tables: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Backup ฐานข้อมูล (เฉพาะโครงสร้าง)
     * 
     * @return string|false
     */
    public function backupDatabaseStructure()
    {
        try {
            $conn = $this->getConnection();
            $backup_sql = "";

            // ดึงรายชื่อตารางทั้งหมด
            $stmt = $conn->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                // ดึงโครงสร้างตาราง
                $stmt = $conn->query("SHOW CREATE TABLE `{$table}`");
                $create_table = $stmt->fetch();

                $backup_sql .= "-- Structure for table `{$table}`\n";
                $backup_sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $backup_sql .= $create_table['Create Table'] . ";\n\n";
            }

            return $backup_sql;
        } catch (Exception $e) {
            error_log("Error backing up database structure: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ทำความสะอาดข้อมูลเก่า
     */
    public function cleanupOldData()
    {
        try {
            $conn = $this->getConnection();

            // ลบ cart items เก่าที่ไม่มี user_id (มากกว่า 7 วัน)
            $stmt = $conn->prepare("
                DELETE FROM cart 
                WHERE user_id IS NULL 
                AND updated_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $stmt->execute();
            $deleted_cart = $stmt->rowCount();

            // ลบ reset tokens ที่หมดอายุ
            $stmt = $conn->prepare("
                UPDATE users 
                SET reset_token = NULL, reset_token_expires = NULL 
                WHERE reset_token_expires IS NOT NULL 
                AND reset_token_expires < NOW()
            ");
            $stmt->execute();
            $cleaned_tokens = $stmt->rowCount();

            // ลบ activity logs เก่า (มากกว่า 90 วัน)
            $stmt = $conn->prepare("
                DELETE FROM activity_logs 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
            ");
            $stmt->execute();
            $deleted_logs = $stmt->rowCount();

            return [
                'deleted_cart_items' => $deleted_cart,
                'cleaned_reset_tokens' => $cleaned_tokens,
                'deleted_old_logs' => $deleted_logs
            ];
        } catch (Exception $e) {
            error_log("Error cleaning up old data: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ตรวจสอบ performance ฐานข้อมูล
     * 
     * @return array
     */
    public function getPerformanceStats()
    {
        try {
            $conn = $this->getConnection();
            $stats = [];

            // Slow queries
            $stmt = $conn->query("SHOW GLOBAL STATUS LIKE 'Slow_queries'");
            $result = $stmt->fetch();
            $stats['slow_queries'] = $result['Value'];

            // Connection attempts
            $stmt = $conn->query("SHOW GLOBAL STATUS LIKE 'Connections'");
            $result = $stmt->fetch();
            $stats['total_connections'] = $result['Value'];

            // Uptime
            $stmt = $conn->query("SHOW GLOBAL STATUS LIKE 'Uptime'");
            $result = $stmt->fetch();
            $stats['uptime_seconds'] = $result['Value'];

            return $stats;
        } catch (Exception $e) {
            error_log("Error getting performance stats: " . $e->getMessage());
            return [];
        }
    }
}

/**
 * Helper function สำหรับการเชื่อมต่อฐานข้อมูลแบบง่าย
 * 
 * @return PDO
 */
function getDB()
{
    return Database::getInstance()->getConnection();
}

/**
 * Helper function สำหรับทดสอบการเชื่อมต่อ
 * 
 * @return boolean
 */
function testDBConnection()
{
    return Database::getInstance()->testConnection();
}

// ตั้งค่า error reporting สำหรับ PDO
if (!defined('PDO_ERROR_REPORTING_SET')) {
    // แสดง PDO errors ในโหมด development เท่านั้น
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
    }
    define('PDO_ERROR_REPORTING_SET', true);
}
