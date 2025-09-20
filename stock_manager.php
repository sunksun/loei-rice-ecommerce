<?php
// stock_manager.php - Stock Management Utilities

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'error_handler.php';

/**
 * Stock Management Class
 * จัดการสต็อกสินค้าและการตรวจสอบความพร้อม
 */
class StockManager {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDB();
    }
    
    /**
     * ตรวจสอบ stock availability สำหรับหลายสินค้า
     */
    public function checkStockAvailability($items) {
        $results = [];
        $errors = [];
        
        foreach ($items as $item) {
            $product_id = (int)$item['id'];
            $requested_quantity = (int)$item['quantity'];
            
            $check_result = $this->checkSingleProductStock($product_id, $requested_quantity);
            
            if ($check_result['available']) {
                $results[] = $check_result['product_data'];
            } else {
                $errors[] = $check_result['error'];
            }
        }
        
        return [
            'success' => empty($errors),
            'available_items' => $results,
            'errors' => $errors
        ];
    }
    
    /**
     * ตรวจสอบ stock สำหรับสินค้าเดียว
     */
    public function checkSingleProductStock($product_id, $requested_quantity) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, name, price, sale_price, stock_quantity, status, 
                       max_quantity_per_order, min_stock_alert
                FROM products 
                WHERE id = ?
            ");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if (!$product) {
                return [
                    'available' => false,
                    'error' => "ไม่พบสินค้า ID: {$product_id}"
                ];
            }
            
            // ตรวจสอบสถานะสินค้า
            if ($product['status'] !== 'active') {
                return [
                    'available' => false,
                    'error' => "สินค้า '{$product['name']}' ไม่พร้อมขาย"
                ];
            }
            
            // ตรวจสอบสต็อก
            if ($product['stock_quantity'] < $requested_quantity) {
                if ($product['stock_quantity'] <= 0) {
                    return [
                        'available' => false,
                        'error' => "สินค้า '{$product['name']}' หมดสต็อก"
                    ];
                } else {
                    return [
                        'available' => false,
                        'error' => "สินค้า '{$product['name']}' มีสต็อกเหลือเพียง {$product['stock_quantity']} ชิ้น"
                    ];
                }
            }
            
            // ตรวจสอบจำนวนสูงสุดต่อออเดอร์
            $max_qty = $product['max_quantity_per_order'] ?? 999;
            if ($requested_quantity > $max_qty) {
                return [
                    'available' => false,
                    'error' => "สินค้า '{$product['name']}' สั่งได้สูงสุด {$max_qty} ชิ้นต่อออเดอร์"
                ];
            }
            
            return [
                'available' => true,
                'product_data' => [
                    'id' => $product_id,
                    'name' => $product['name'],
                    'price' => $product['sale_price'] ?: $product['price'],
                    'quantity' => $requested_quantity,
                    'current_stock' => $product['stock_quantity'],
                    'low_stock_alert' => $product['stock_quantity'] <= ($product['min_stock_alert'] ?? 5)
                ]
            ];
            
        } catch (Exception $e) {
            logError($e, 'stock_check', ['product_id' => $product_id, 'quantity' => $requested_quantity]);
            return [
                'available' => false,
                'error' => "เกิดข้อผิดพลาดในการตรวจสอบสต็อก"
            ];
        }
    }
    
    /**
     * ตัดสต็อกแบบปลอดภัย
     */
    public function deductStock($product_id, $quantity, $order_number = null) {
        try {
            $this->pdo->beginTransaction();
            
            // ตรวจสอบสต็อกปัจจุบันอีกครั้ง
            $stmt = $this->pdo->prepare("
                SELECT name, stock_quantity 
                FROM products 
                WHERE id = ? AND stock_quantity >= ?
                FOR UPDATE
            ");
            $stmt->execute([$product_id, $quantity]);
            $product = $stmt->fetch();
            
            if (!$product) {
                throw new Exception("สต็อกไม่เพียงพอสำหรับการตัด");
            }
            
            // ตัดสต็อก
            $stmt = $this->pdo->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity - ?,
                    updated_at = NOW()
                WHERE id = ? AND stock_quantity >= ?
            ");
            $stmt->execute([$quantity, $product_id, $quantity]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("ไม่สามารถตัดสต็อกได้");
            }
            
            // บันทึก stock movement log
            $this->logStockMovement($product_id, -$quantity, 'sale', $order_number);
            
            $this->pdo->commit();
            
            // ตรวจสอบและแจ้งเตือน low stock
            $this->checkLowStockAlert($product_id);
            
            return [
                'success' => true,
                'remaining_stock' => $product['stock_quantity'] - $quantity
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            logError($e, 'stock_deduction', ['product_id' => $product_id, 'quantity' => $quantity]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * เพิ่มสต็อก (สำหรับการยกเลิกออเดอร์)
     */
    public function restoreStock($product_id, $quantity, $reason = 'order_cancelled') {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity + ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$quantity, $product_id]);
            
            // บันทึก stock movement log
            $this->logStockMovement($product_id, $quantity, $reason);
            
            return [
                'success' => true,
                'message' => "เพิ่มสต็อกสำเร็จ"
            ];
            
        } catch (Exception $e) {
            logError($e, 'stock_restoration', ['product_id' => $product_id, 'quantity' => $quantity]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * บันทึก stock movement log
     */
    private function logStockMovement($product_id, $quantity_change, $type, $reference = null) {
        try {
            // สร้างตาราง stock_movements หากยังไม่มี
            $this->createStockMovementsTable();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO stock_movements 
                (product_id, quantity_change, movement_type, reference, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$product_id, $quantity_change, $type, $reference]);
            
        } catch (Exception $e) {
            // ไม่ให้ error ของ logging ทำให้ main process fail
            error_log("Failed to log stock movement: " . $e->getMessage());
        }
    }
    
    /**
     * สร้างตาราง stock_movements หากยังไม่มี
     */
    private function createStockMovementsTable() {
        try {
            $sql = "
                CREATE TABLE IF NOT EXISTS stock_movements (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    product_id INT NOT NULL,
                    quantity_change INT NOT NULL,
                    movement_type VARCHAR(50) NOT NULL,
                    reference VARCHAR(100),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_product_id (product_id),
                    INDEX idx_created_at (created_at)
                )
            ";
            $this->pdo->exec($sql);
        } catch (Exception $e) {
            error_log("Failed to create stock_movements table: " . $e->getMessage());
        }
    }
    
    /**
     * ตรวจสอบและแจ้งเตือน low stock
     */
    private function checkLowStockAlert($product_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT name, stock_quantity, min_stock_alert 
                FROM products 
                WHERE id = ?
            ");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if ($product) {
                $min_alert = $product['min_stock_alert'] ?? 5;
                if ($product['stock_quantity'] <= $min_alert) {
                    // บันทึก alert log
                    logActivity('system', null, 'low_stock_alert', 
                        "Product '{$product['name']}' has low stock: {$product['stock_quantity']} units remaining");
                    
                    // อาจส่งอีเมลแจ้งเตือนที่นี่ในอนาคต
                }
            }
        } catch (Exception $e) {
            error_log("Failed to check low stock alert: " . $e->getMessage());
        }
    }
    
    /**
     * ดึงรายงาน stock movements
     */
    public function getStockMovements($product_id = null, $limit = 100) {
        try {
            $sql = "
                SELECT sm.*, p.name as product_name
                FROM stock_movements sm
                LEFT JOIN products p ON sm.product_id = p.id
            ";
            
            $params = [];
            if ($product_id) {
                $sql .= " WHERE sm.product_id = ?";
                $params[] = $product_id;
            }
            
            $sql .= " ORDER BY sm.created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            logError($e, 'stock_movements_report');
            return [];
        }
    }
    
    /**
     * ดึงรายงาน low stock products
     */
    public function getLowStockProducts() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, name, stock_quantity, min_stock_alert
                FROM products 
                WHERE status = 'active' 
                AND stock_quantity <= COALESCE(min_stock_alert, 5)
                ORDER BY stock_quantity ASC
            ");
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            logError($e, 'low_stock_report');
            return [];
        }
    }
}

// Helper functions
function checkProductStock($product_id, $quantity) {
    $manager = new StockManager();
    return $manager->checkSingleProductStock($product_id, $quantity);
}

function checkCartStock($items) {
    $manager = new StockManager();
    return $manager->checkStockAvailability($items);
}

function deductProductStock($product_id, $quantity, $order_number = null) {
    $manager = new StockManager();
    return $manager->deductStock($product_id, $quantity, $order_number);
}

function restoreProductStock($product_id, $quantity, $reason = 'order_cancelled') {
    $manager = new StockManager();
    return $manager->restoreStock($product_id, $quantity, $reason);
}
?>