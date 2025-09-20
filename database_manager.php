<?php
// database_manager.php - Database Management Utilities

require_once 'config/config.php';
require_once 'config/database.php';

/**
 * Database Management Class
 * จัดการ database views, tables และ maintenance tasks
 */
class DatabaseManager {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDB();
    }
    
    /**
     * ตรวจสอบว่า table หรือ view มีอยู่หรือไม่
     */
    public function checkTableExists($table_name) {
        try {
            $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table_name]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Error checking table existence: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * สร้าง order_details view
     */
    public function createOrderDetailsView() {
        $sql = "
            CREATE VIEW order_details AS 
            SELECT 
                o.id, o.user_id, o.order_number, o.status, o.subtotal, 
                o.shipping_cost, o.tax_amount, o.discount_amount, o.total_amount,
                o.payment_method, o.payment_status, o.payment_reference, o.paid_at,
                o.shipping_method_id, o.tracking_number, o.shipping_notes,
                o.billing_address, o.shipping_address, o.customer_notes, o.admin_notes,
                o.ordered_at, o.confirmed_at, o.shipped_at, o.delivered_at,
                o.cancelled_at, o.cancel_reason, o.created_at, o.updated_at,
                CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS customer_name,
                u.email AS customer_email, u.phone AS customer_phone,
                sm.name AS shipping_method_name, sm.estimated_days AS shipping_estimated_days
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN shipping_methods sm ON o.shipping_method_id = sm.id
        ";
        
        try {
            $this->pdo->exec($sql);
            return true;
        } catch (Exception $e) {
            error_log("Error creating order_details view: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ลบ view
     */
    public function dropView($view_name) {
        try {
            $sql = "DROP VIEW IF EXISTS " . $view_name;
            $this->pdo->exec($sql);
            return true;
        } catch (Exception $e) {
            error_log("Error dropping view {$view_name}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ตรวจสอบและสร้าง views ที่จำเป็น
     */
    public function ensureRequiredViews() {
        $results = [];
        
        // ตรวจสอบ order_details view
        if (!$this->checkTableExists('order_details')) {
            $results['order_details'] = $this->createOrderDetailsView();
        } else {
            $results['order_details'] = true;
        }
        
        return $results;
    }
    
    /**
     * ตรวจสอบสถานะฐานข้อมูล
     */
    public function getDatabaseStatus() {
        $status = [
            'connection' => false,
            'required_tables' => [],
            'views' => [],
            'errors' => []
        ];
        
        try {
            // ตรวจสอบการเชื่อมต่อ
            $this->pdo->query("SELECT 1");
            $status['connection'] = true;
            
            // ตรวจสอบ tables ที่จำเป็น
            $required_tables = ['orders', 'users', 'products', 'categories', 'order_items'];
            foreach ($required_tables as $table) {
                $status['required_tables'][$table] = $this->checkTableExists($table);
            }
            
            // ตรวจสอบ views
            $status['views']['order_details'] = $this->checkTableExists('order_details');
            
        } catch (Exception $e) {
            $status['errors'][] = $e->getMessage();
        }
        
        return $status;
    }
    
    /**
     * ซ่อมแซมฐานข้อมูล
     */
    public function repairDatabase() {
        $results = [];
        
        try {
            // สร้าง views ที่หายไป
            $view_results = $this->ensureRequiredViews();
            $results['views'] = $view_results;
            
            // อัปเดต timestamps หากจำเป็น
            $this->updateTimestamps();
            
            $results['success'] = true;
            $results['message'] = 'Database repair completed successfully';
            
        } catch (Exception $e) {
            $results['success'] = false;
            $results['error'] = $e->getMessage();
            error_log("Database repair failed: " . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * อัปเดต timestamps สำหรับ records เก่า
     */
    private function updateTimestamps() {
        try {
            // อัปเดต orders ที่ไม่มี ordered_at
            $sql = "UPDATE orders SET ordered_at = created_at WHERE ordered_at IS NULL AND created_at IS NOT NULL";
            $this->pdo->exec($sql);
            
            // อัปเดต users ที่ไม่มี created_at  
            $sql = "UPDATE users SET created_at = NOW() WHERE created_at IS NULL";
            $this->pdo->exec($sql);
            
        } catch (Exception $e) {
            error_log("Error updating timestamps: " . $e->getMessage());
        }
    }
    
    /**
     * ทำความสะอาดข้อมูลเก่า
     */
    public function cleanupOldData() {
        $results = [];
        
        try {
            // ลบ cart items เก่า (มากกว่า 30 วัน)
            $sql = "DELETE FROM cart WHERE updated_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $stmt = $this->pdo->exec($sql);
            $results['deleted_cart_items'] = $this->pdo->rowCount();
            
            // ลบ activity logs เก่า (มากกว่า 90 วัน)
            if ($this->checkTableExists('activity_logs')) {
                $sql = "DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)";
                $this->pdo->exec($sql);
                $results['deleted_activity_logs'] = $this->pdo->rowCount();
            }
            
            $results['success'] = true;
            
        } catch (Exception $e) {
            $results['success'] = false;
            $results['error'] = $e->getMessage();
            error_log("Cleanup failed: " . $e->getMessage());
        }
        
        return $results;
    }
}

// Command line interface
if (php_sapi_name() === 'cli') {
    $manager = new DatabaseManager();
    
    $command = $argv[1] ?? 'status';
    
    switch ($command) {
        case 'status':
            $status = $manager->getDatabaseStatus();
            echo "Database Status:\n";
            echo "Connection: " . ($status['connection'] ? 'OK' : 'FAILED') . "\n";
            echo "Required Tables:\n";
            foreach ($status['required_tables'] as $table => $exists) {
                echo "  - {$table}: " . ($exists ? 'OK' : 'MISSING') . "\n";
            }
            echo "Views:\n";
            foreach ($status['views'] as $view => $exists) {
                echo "  - {$view}: " . ($exists ? 'OK' : 'MISSING') . "\n";
            }
            if (!empty($status['errors'])) {
                echo "Errors:\n";
                foreach ($status['errors'] as $error) {
                    echo "  - {$error}\n";
                }
            }
            break;
            
        case 'repair':
            echo "Repairing database...\n";
            $results = $manager->repairDatabase();
            if ($results['success']) {
                echo "Database repair completed successfully\n";
            } else {
                echo "Database repair failed: " . $results['error'] . "\n";
            }
            break;
            
        case 'cleanup':
            echo "Cleaning up old data...\n";
            $results = $manager->cleanupOldData();
            if ($results['success']) {
                echo "Cleanup completed\n";
                echo "Deleted cart items: " . ($results['deleted_cart_items'] ?? 0) . "\n";
                echo "Deleted activity logs: " . ($results['deleted_activity_logs'] ?? 0) . "\n";
            } else {
                echo "Cleanup failed: " . $results['error'] . "\n";
            }
            break;
            
        default:
            echo "Usage: php database_manager.php [status|repair|cleanup]\n";
            break;
    }
}
?>