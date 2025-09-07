<?php
require_once '../config/database.php';
$pdo = getDB();
header('Content-Type: application/json; charset=utf-8');

try {
    // ดึงเฉพาะตัวเลขจาก order_number ที่ผู้ใช้กรอก
    $order_number = isset($_GET['order_number']) ? trim($_GET['order_number']) : '';
    if ($order_number === '') {
        echo json_encode(['success' => false, 'orders' => [], 'message' => 'กรุณากรอกเลขที่คำสั่งซื้อ']);
        exit;
    }
    // ดึงเฉพาะตัวเลขจาก input
    $digits = preg_replace('/\D/', '', $order_number);
    if ($digits === '') {
        echo json_encode(['success' => false, 'orders' => [], 'message' => 'กรุณากรอกเฉพาะตัวเลข']);
        exit;
    }
    $stmt = $pdo->prepare('SELECT order_number, total_amount, status, created_at FROM orders WHERE order_number LIKE ? LIMIT 10');
    $stmt->execute(["%$digits%"]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'orders' => $orders]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'orders' => [], 'message' => 'Server error: ' . $e->getMessage()]);
}
