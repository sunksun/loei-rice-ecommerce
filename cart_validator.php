<?php
// cart_validator.php - Server-side cart validation

session_start();

// โหลดการตั้งค่า
require_once 'config/config.php';
require_once 'config/database.php';

header('Content-Type: application/json');

// ตรวจสอบ method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendJsonResponse(['error' => 'Method not allowed']);
}

// ตรวจสอบ CSRF token
$csrf_token = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrf_token)) {
    http_response_code(403);
    sendJsonResponse(['error' => 'Invalid CSRF token']);
}

// รับข้อมูล cart
$cart_data = $_POST['cart_data'] ?? '';
if (empty($cart_data)) {
    sendJsonResponse(['error' => 'No cart data provided']);
}

try {
    $cart_items = json_decode($cart_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON format');
    }

    $pdo = getDB();
    $validated_items = [];
    $total_amount = 0;
    $issues = [];

    foreach ($cart_items as $item) {
        // Validation พื้นฐาน
        if (!isset($item['id']) || !isset($item['quantity'])) {
            $issues[] = 'ข้อมูลสินค้าไม่ครบถ้วน';
            continue;
        }

        $product_id = (int) $item['id'];
        $quantity = (int) $item['quantity'];

        if ($product_id <= 0 || $quantity <= 0) {
            $issues[] = 'ข้อมูลสินค้าไม่ถูกต้อง';
            continue;
        }

        // ตรวจสอบสินค้าในฐานข้อมูล
        $stmt = $pdo->prepare("
            SELECT id, name, price, sale_price, stock_quantity, status, max_quantity_per_order
            FROM products 
            WHERE id = ? AND status = 'active'
        ");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product) {
            $issues[] = "ไม่พบสินค้า ID: {$product_id}";
            continue;
        }

        // ตรวจสอบสต็อก
        if ($product['stock_quantity'] < $quantity) {
            $issues[] = "สินค้า '{$product['name']}' มีสต็อกเหลือ {$product['stock_quantity']} ชิ้น";
            $quantity = max(0, $product['stock_quantity']); // ปรับจำนวนให้ตรงกับสต็อก
        }

        // ตรวจสอบจำนวนสูงสุดต่อออเดอร์
        $max_qty = $product['max_quantity_per_order'] ?? 999;
        if ($quantity > $max_qty) {
            $issues[] = "สินค้า '{$product['name']}' สั่งได้สูงสุด {$max_qty} ชิ้นต่อออเดอร์";
            $quantity = $max_qty;
        }

        if ($quantity > 0) {
            $price = $product['sale_price'] ?: $product['price'];
            $item_total = $price * $quantity;
            
            $validated_items[] = [
                'id' => $product_id,
                'name' => $product['name'],
                'price' => $price,
                'quantity' => $quantity,
                'stock_quantity' => $product['stock_quantity'],
                'item_total' => $item_total
            ];
            
            $total_amount += $item_total;
        }
    }

    // บันทึก validated cart ลง session
    $_SESSION['validated_cart'] = $validated_items;
    $_SESSION['validated_cart_total'] = $total_amount;
    $_SESSION['validated_cart_timestamp'] = time();

    sendJsonResponse([
        'success' => true,
        'validated_items' => $validated_items,
        'total_amount' => $total_amount,
        'issues' => $issues,
        'item_count' => count($validated_items)
    ]);

} catch (Exception $e) {
    error_log("Cart validation error: " . $e->getMessage());
    http_response_code(500);
    sendJsonResponse(['error' => 'เกิดข้อผิดพลาดในการตรวจสอบตะกร้าสินค้า']);
}
?>