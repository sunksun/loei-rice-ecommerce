<?php

/**
 * Multi-Store Management System
 * จัดการระบบหลายร้านค้าและระบบสิทธิ์
 * 
 * @author Loei Rice E-commerce Team
 * @version 1.0
 */

require_once __DIR__ . '/../config/database.php';

class MultiStoreManager 
{
    protected $conn;
    protected $admin_id;
    protected $admin_store_id;
    protected $admin_role_code;
    protected $admin_level;
    protected $permissions = [];
    protected $is_super_admin = false;
    
    public function __construct($admin_id) 
    {
        $this->conn = getDB();
        $this->admin_id = $admin_id;
        $this->loadAdminData();
    }
    
    /**
     * โหลดข้อมูล admin และสิทธิ์
     */
    private function loadAdminData() 
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT a.store_id, a.role_id, r.role_code, r.level, r.permissions,
                       r.can_manage_stores, r.can_manage_admins
                FROM admins a 
                LEFT JOIN admin_roles r ON a.role_id = r.id 
                WHERE a.id = ? AND a.status = 'active'
            ");
            $stmt->execute([$this->admin_id]);
            $admin = $stmt->fetch();
            
            if ($admin) {
                $this->admin_store_id = $admin['store_id'];
                $this->admin_role_code = $admin['role_code'];
                $this->admin_level = $admin['level'] ?? 1;
                $this->permissions = json_decode($admin['permissions'] ?? '[]', true);
                $this->is_super_admin = ($admin['role_code'] === 'super_admin');
            }
        } catch (Exception $e) {
            error_log("Error loading admin data: " . $e->getMessage());
        }
    }
    
    /**
     * ตรวจสอบสิทธิ์การเข้าถึง
     */
    public function canAccess($permission) 
    {
        if ($this->is_super_admin) {
            return true; // Super admin มีสิทธิ์ทุกอย่าง
        }
        
        return in_array($permission, $this->permissions) || 
               in_array('all', $this->permissions);
    }
    
    /**
     * ตรวจสอบว่าเป็น Super Admin หรือไม่
     */
    public function isSuperAdmin() 
    {
        return $this->is_super_admin;
    }
    
    /**
     * ได้ Store ID ของ admin
     */
    public function getAdminStoreId() 
    {
        return $this->admin_store_id;
    }
    
    /**
     * ได้ Role Code ของ admin
     */
    public function getAdminRole() 
    {
        return $this->admin_role_code;
    }
    
    /**
     * ได้ Level ของ admin
     */
    public function getAdminLevel() 
    {
        return $this->admin_level;
    }
    
    /**
     * สร้าง SQL Filter สำหรับกรองข้อมูลตามร้าน
     */
    public function getStoreFilter($table_alias = '') 
    {
        if ($this->is_super_admin) {
            return ''; // Super Admin เห็นทุกร้าน
        }
        
        $prefix = $table_alias ? $table_alias . '.' : '';
        return " AND {$prefix}store_id = " . intval($this->admin_store_id);
    }
    
    /**
     * สร้าง WHERE clause สำหรับกรองข้อมูลตามร้าน
     */
    public function getStoreWhereClause($table_alias = '') 
    {
        if ($this->is_super_admin) {
            return '1=1'; // Super Admin เห็นทุกร้าน
        }
        
        $prefix = $table_alias ? $table_alias . '.' : '';
        return "{$prefix}store_id = " . intval($this->admin_store_id);
    }
    
    /**
     * ตรวจสอบว่าสามารถเข้าถึงร้านนี้ได้หรือไม่
     */
    public function canAccessStore($store_id) 
    {
        if ($this->is_super_admin) {
            return true;
        }
        
        return $this->admin_store_id == $store_id;
    }
    
    /**
     * ตรวจสอบว่าสามารถจัดการ admin อื่นได้หรือไม่
     */
    public function canManageAdmin($target_admin_id) 
    {
        if ($this->admin_id == $target_admin_id) {
            return false; // ไม่สามารถจัดการตัวเองได้
        }
        
        if ($this->is_super_admin) {
            return true;
        }
        
        // ตรวจสอบว่า target admin อยู่ในร้านเดียวกันและมี level ต่ำกว่า
        try {
            $stmt = $this->conn->prepare("
                SELECT a.store_id, r.level 
                FROM admins a 
                LEFT JOIN admin_roles r ON a.role_id = r.id 
                WHERE a.id = ?
            ");
            $stmt->execute([$target_admin_id]);
            $target = $stmt->fetch();
            
            if (!$target) return false;
            
            return ($target['store_id'] == $this->admin_store_id) && 
                   ($target['level'] < $this->admin_level) &&
                   $this->canAccess('staff_manage');
        } catch (Exception $e) {
            error_log("Error checking admin management permission: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * บันทึก activity log
     */
    public function logActivity($action, $target_type = null, $target_id = null, $description = null) 
    {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO admin_activities 
                (admin_id, store_id, action, target_type, target_id, description, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $this->admin_id,
                $this->admin_store_id,
                $action,
                $target_type,
                $target_id,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Error logging admin activity: " . $e->getMessage());
        }
    }
    
    /**
     * ดึงข้อมูลร้านของ admin
     */
    public function getStoreInfo() 
    {
        if (!$this->admin_store_id) {
            return null;
        }
        
        try {
            $stmt = $this->conn->prepare("SELECT * FROM stores WHERE id = ?");
            $stmt->execute([$this->admin_store_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error getting store info: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * ดึงสถิติของร้าน
     */
    public function getStoreStats($store_id = null) 
    {
        $target_store_id = $store_id ?? $this->admin_store_id;
        
        if (!$this->canAccessStore($target_store_id)) {
            return null;
        }
        
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM products WHERE store_id = ?) as total_products,
                    (SELECT COUNT(*) FROM products WHERE store_id = ? AND status = 'active') as active_products,
                    (SELECT COUNT(*) FROM admins WHERE store_id = ?) as total_admins,
                    (SELECT COALESCE(SUM(stock_quantity), 0) FROM products WHERE store_id = ?) as total_stock
            ");
            $stmt->execute([$target_store_id, $target_store_id, $target_store_id, $target_store_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error getting store stats: " . $e->getMessage());
            return null;
        }
    }
}

/**
 * Store Management Class
 * จัดการร้านค้า (สำหรับ Super Admin)
 */
class StoreManager extends MultiStoreManager 
{
    /**
     * สร้างร้านใหม่
     */
    public function createStore($data) 
    {
        if (!$this->canAccess('store_manage')) {
            throw new Exception('ไม่มีสิทธิ์ในการสร้างร้าน');
        }
        
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO stores (store_name, store_code, owner_name, phone, email, address, description) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['store_name'],
                $data['store_code'],
                $data['owner_name'] ?? null,
                $data['phone'] ?? null,
                $data['email'] ?? null,
                $data['address'] ?? null,
                $data['description'] ?? null
            ]);
            
            $store_id = $this->conn->lastInsertId();
            $this->logActivity('create_store', 'store', $store_id, 'สร้างร้าน: ' . $data['store_name']);
            
            return $store_id;
        } catch (Exception $e) {
            error_log("Error creating store: " . $e->getMessage());
            throw new Exception('ไม่สามารถสร้างร้านได้: ' . $e->getMessage());
        }
    }
    
    /**
     * อัพเดตข้อมูลร้าน
     */
    public function updateStore($store_id, $data) 
    {
        if (!$this->canAccessStore($store_id) || !$this->canAccess('store_manage')) {
            throw new Exception('ไม่มีสิทธิ์ในการแก้ไขร้านนี้');
        }
        
        try {
            $set_clauses = [];
            $values = [];
            
            $allowed_fields = ['store_name', 'owner_name', 'phone', 'email', 'address', 'description', 'status'];
            
            foreach ($allowed_fields as $field) {
                if (isset($data[$field])) {
                    $set_clauses[] = "{$field} = ?";
                    $values[] = $data[$field];
                }
            }
            
            if (empty($set_clauses)) {
                throw new Exception('ไม่มีข้อมูลที่ต้องอัพเดต');
            }
            
            $values[] = $store_id;
            
            $stmt = $this->conn->prepare("
                UPDATE stores SET " . implode(', ', $set_clauses) . ", updated_at = NOW() 
                WHERE id = ?
            ");
            
            $stmt->execute($values);
            $this->logActivity('update_store', 'store', $store_id, 'อัพเดตข้อมูลร้าน');
            
            return true;
        } catch (Exception $e) {
            error_log("Error updating store: " . $e->getMessage());
            throw new Exception('ไม่สามารถอัพเดตร้านได้: ' . $e->getMessage());
        }
    }
    
    /**
     * ดึงรายการร้านทั้งหมด (สำหรับ Super Admin)
     */
    public function getAllStores() 
    {
        if (!$this->is_super_admin) {
            throw new Exception('ไม่มีสิทธิ์ในการดูข้อมูลร้านทั้งหมด');
        }
        
        try {
            $stmt = $this->conn->query("
                SELECT s.*, 
                       COUNT(DISTINCT a.id) as admin_count,
                       COUNT(DISTINCT p.id) as product_count,
                       COALESCE(SUM(p.stock_quantity), 0) as total_stock
                FROM stores s 
                LEFT JOIN admins a ON s.id = a.store_id 
                LEFT JOIN products p ON s.id = p.store_id 
                GROUP BY s.id 
                ORDER BY s.created_at DESC
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting all stores: " . $e->getMessage());
            return [];
        }
    }
}