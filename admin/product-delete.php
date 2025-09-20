<?php
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'ไม่ได้รับอนุญาต']);
    exit();
}

// ตรวจสอบ HTTP method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'วิธีการเรียกใช้ไม่ถูกต้อง']);
    exit();
}

// รวมไฟล์การตั้งค่า
require_once '../config/database.php';

try {
    // รับ JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data || (!isset($data['product_id']) && !isset($data['id']))) {
        throw new Exception('ไม่พบข้อมูลสินค้าที่ต้องการลบ');
    }
    
    // รองรับทั้ง product_id และ id
    $product_id = (int)($data['product_id'] ?? $data['id'] ?? 0);
    
    // Debug: บันทึกข้อมูลที่ได้รับ
    error_log("Product delete request - Raw data: " . $json);
    error_log("Product delete request - Parsed product_id: " . $product_id);
    
    if ($product_id <= 0) {
        throw new Exception('รหัสสินค้าไม่ถูกต้อง (ID: ' . $product_id . ')');
    }
    
    // เชื่อมต่อฐานข้อมูล
    $host = 'localhost';
    $dbname = 'loei_rice_ecommerce';
    $username_db = 'root';
    $password_db = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // ตรวจสอบว่าสินค้ามีอยู่จริง
    $stmt_check = $pdo->prepare("SELECT id, name, image_main FROM products WHERE id = ?");
    $stmt_check->execute([$product_id]);
    $product = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('ไม่พบสินค้าที่ต้องการลบ');
    }
    
    // เริ่ม transaction
    $pdo->beginTransaction();
    
    try {
        // ลบข้อมูลที่เกี่ยวข้องทั้งหมด (บังคับลบ)
        
        // 1. ลบข้อมูลจาก order_items (ถ้ามี)
        $stmt_order_items = $pdo->prepare("DELETE FROM order_items WHERE product_id = ?");
        $stmt_order_items->execute([$product_id]);
        
        // 2. ลบรีวิว
        $stmt_reviews = $pdo->prepare("DELETE FROM reviews WHERE product_id = ?");
        $stmt_reviews->execute([$product_id]);
        
        // 3. ลบจาก cart
        $stmt_cart = $pdo->prepare("DELETE FROM cart WHERE product_id = ?");
        $stmt_cart->execute([$product_id]);
        
        // 4. ลบไฟล์รูปภาพหลัก (ถ้ามี)
        if (!empty($product['image_main'])) {
            $image_path = '../uploads/products/' . $product['image_main'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        // 5. ลบรูปภาพใน gallery (ถ้ามี)
        if (!empty($product['image_gallery'])) {
            $gallery_images = json_decode($product['image_gallery'], true);
            if (is_array($gallery_images)) {
                foreach ($gallery_images as $image) {
                    $image_path = '../uploads/products/' . $image;
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
            }
        }
        
        // 6. ลบสินค้าออกจากตาราง products
        $stmt_delete = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt_delete->execute([$product_id]);
        
        $message = 'ลบสินค้าออกจากระบบเรียบร้อยแล้ว';
        
        // Commit transaction
        $pdo->commit();
        
        // บันทึก log
        error_log("Admin {$_SESSION['admin_id']} deleted/deactivated product ID: $product_id ({$product['name']})");
        
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'action' => 'deleted'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Product delete error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>