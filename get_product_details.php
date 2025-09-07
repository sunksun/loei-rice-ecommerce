<?php
// get_product_details.php

header('Content-Type: application/json');

error_reporting(0);
ini_set('display_errors', 0);

$db_config_path = __DIR__ . '/config/database.php';
if (!file_exists($db_config_path)) {
    http_response_code(500);
    echo json_encode(['error' => 'ไม่พบไฟล์ตั้งค่าฐานข้อมูล (database.php)']);
    exit;
}
require_once $db_config_path;

if (!class_exists('Database') || !function_exists('getDB')) {
    http_response_code(500);
    echo json_encode(['error' => 'ไฟล์ตั้งค่าฐานข้อมูล (database.php) ไม่ถูกต้อง']);
    exit;
}

try {
    $pdo = getDB();
    $ids = isset($_GET['ids']) ? $_GET['ids'] : '';

    if (empty($ids)) {
        echo json_encode([]);
        exit;
    }

    $product_ids = array_filter(array_map('intval', explode(',', $ids)));

    if (empty($product_ids)) {
        echo json_encode([]);
        exit;
    }

    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';

    $stmt = $pdo->prepare("
        SELECT id, name, price, sale_price, image_main, stock_quantity, status
        FROM products
        WHERE id IN ($placeholders) AND status = 'active'
    ");

    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($products);
} catch (Exception $e) {
    error_log("API Error in get_product_details.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'เกิดข้อผิดพลาดฝั่งเซิร์ฟเวอร์ในการดึงข้อมูลสินค้า']);
    exit;
}
