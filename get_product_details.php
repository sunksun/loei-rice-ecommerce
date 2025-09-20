<?php
// get_product_details.php

session_start();

header('Content-Type: application/json');

error_reporting(0);
ini_set('display_errors', 0);

// เพิ่มการตรวจสอบ method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// โหลด config และ database
$config_path = __DIR__ . '/config/config.php';
$db_config_path = __DIR__ . '/config/database.php';

if (!file_exists($config_path) || !file_exists($db_config_path)) {
    http_response_code(500);
    echo json_encode(['error' => 'ไม่พบไฟล์ตั้งค่าระบบ']);
    exit;
}

require_once $config_path;
require_once $db_config_path;

if (!class_exists('Database') || !function_exists('getDB')) {
    http_response_code(500);
    echo json_encode(['error' => 'ไฟล์ตั้งค่าฐานข้อมูล (database.php) ไม่ถูกต้อง']);
    exit;
}

// เพิ่มการตรวจสอบ rate limiting
$user_ip = getUserIpAddress();
$rate_limit_key = "api_rate_limit_" . md5($user_ip);

if (isset($_SESSION[$rate_limit_key])) {
    $requests = $_SESSION[$rate_limit_key];
    if ($requests['count'] >= 60 && (time() - $requests['start_time']) < 3600) {
        http_response_code(429);
        echo json_encode(['error' => 'Too many requests. Please try again later.']);
        exit;
    }
    
    if ((time() - $requests['start_time']) >= 3600) {
        $_SESSION[$rate_limit_key] = ['count' => 1, 'start_time' => time()];
    } else {
        $_SESSION[$rate_limit_key]['count']++;
    }
} else {
    $_SESSION[$rate_limit_key] = ['count' => 1, 'start_time' => time()];
}

try {
    $pdo = getDB();
    $ids = isset($_GET['ids']) ? trim($_GET['ids']) : '';

    // ปรับปรุงการ validation
    if (empty($ids)) {
        echo json_encode([]);
        exit;
    }

    // ตรวจสอบรูปแบบของ IDs (ต้องเป็นตัวเลขและคอมม่าเท่านั้น)
    if (!preg_match('/^[0-9,]+$/', $ids)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid product IDs format']);
        exit;
    }

    $product_ids = array_filter(array_map('intval', explode(',', $ids)));

    if (empty($product_ids) || count($product_ids) > 50) { // จำกัดไม่เกิน 50 รายการต่อครั้ง
        http_response_code(400);
        echo json_encode(['error' => 'Invalid number of product IDs']);
        exit;
    }

    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';

    $stmt = $pdo->prepare("
        SELECT id, name, price, sale_price, image_main, stock_quantity, status, 
               CASE WHEN stock_quantity > 0 THEN 'in_stock' ELSE 'out_of_stock' END as availability
        FROM products
        WHERE id IN ($placeholders) AND status = 'active'
    ");

    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // เพิ่มการ sanitize ข้อมูลก่อนส่งกลับ
    foreach ($products as &$product) {
        $product['name'] = htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8');
        $product['price'] = (float) $product['price'];
        $product['sale_price'] = $product['sale_price'] ? (float) $product['sale_price'] : null;
        $product['stock_quantity'] = (int) $product['stock_quantity'];
    }

    echo json_encode($products, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("API Error in get_product_details.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'เกิดข้อผิดพลาดฝั่งเซิร์ฟเวอร์ในการดึงข้อมูลสินค้า']);
    exit;
}
