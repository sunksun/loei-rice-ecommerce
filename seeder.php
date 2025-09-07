<?php
// ป้องกันการรันบน Production Server โดยไม่ได้ตั้งใจ
if (getenv('APP_ENV') === 'production') {
    die('This script is for development environment only.');
}

set_time_limit(300); // เพิ่มเวลาในการรันสคริปต์เป็น 5 นาที
require_once 'config/database.php';
$pdo = getDB();

echo "<!DOCTYPE html><html lang='th'><head><meta charset='UTF-8'><title>Test Data Generator</title>";
echo "<style>body { font-family: sans-serif; padding: 2rem; } .log { margin-bottom: 0.5rem; } .success { color: green; } .error { color: red; }</style>";
echo "</head><body><h1>กำลังสร้างข้อมูลทดสอบ...</h1>";

function log_message($message, $type = 'info')
{
    $class = ($type === 'success') ? 'success' : (($type === 'error') ? 'error' : '');
    echo "<div class='log {$class}'>{$message}</div>";
    flush(); // แสดงผลทันที
    ob_flush();
}

try {
    // --- การตั้งค่า ---
    $num_customers_to_create = 10;
    $num_orders_to_create = 25;

    // --- 1. ดึงข้อมูลสินค้าที่มีอยู่จริง ---
    log_message("กำลังดึงข้อมูลสินค้า...");
    $stmt_products = $pdo->query("SELECT id, name, price, sale_price FROM products WHERE status = 'active'");
    $products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);
    if (empty($products)) {
        throw new Exception("ไม่พบสินค้าในระบบ ไม่สามารถสร้างคำสั่งซื้อได้");
    }
    log_message("พบสินค้าทั้งหมด " . count($products) . " รายการ", "success");

    // --- 2. สร้างข้อมูลลูกค้าจำลอง ---
    log_message("กำลังสร้างลูกค้าจำลอง {$num_customers_to_create} คน...");
    $first_names = ['สมชาย', 'สมหญิง', 'ประเสริฐ', 'มานี', 'ปิติ', 'วีระ', 'ชูใจ', 'จินตนา', 'สุรพล', 'ทิพวรรณ'];
    $last_names = ['รักไทย', 'ใจดี', 'มีสุข', 'ทำนา', 'เรียนดี', 'กล้าหาญ', 'รุ่งเรือง', 'ศรีสวัสดิ์', 'ทองมี', 'บุญมา'];

    $stmt_customer = $pdo->prepare(
        "INSERT INTO users (first_name, last_name, email, phone, password, status) VALUES (?, ?, ?, ?, ?, 'active')"
    );
    $customer_ids = [];
    for ($i = 0; $i < $num_customers_to_create; $i++) {
        $fname = $first_names[array_rand($first_names)];
        $lname = $last_names[array_rand($last_names)];
        $email = strtolower("testuser" . time() . $i . "@example.com");
        $phone = "08" . rand(10000000, 99999999);
        $password = password_hash('password123', PASSWORD_DEFAULT);

        $stmt_customer->execute([$fname, $lname, $email, $phone, $password]);
        $customer_ids[] = $pdo->lastInsertId();
    }
    log_message("สร้างลูกค้าสำเร็จ!", "success");

    // --- 3. สร้างข้อมูลคำสั่งซื้อจำลอง ---
    log_message("กำลังสร้างคำสั่งซื้อจำลอง {$num_orders_to_create} รายการ...");
    $order_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    $payment_statuses = ['pending', 'paid'];

    // --- ส่วนที่แก้ไข ---
    $stmt_order = $pdo->prepare(
        "INSERT INTO orders (user_id, order_number, status, subtotal, total_amount, payment_method, payment_status, shipping_address, billing_address, ordered_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    // --- สิ้นสุดส่วนที่แก้ไข ---

    $stmt_items = $pdo->prepare(
        "INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, total_price) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    for ($i = 0; $i < $num_orders_to_create; $i++) {
        $customer_id = $customer_ids[array_rand($customer_ids)];

        $order_items_data = [];
        $subtotal = 0;
        $num_items_in_order = rand(1, 3);
        for ($j = 0; $j < $num_items_in_order; $j++) {
            $product = $products[array_rand($products)];
            $quantity = rand(1, 2);
            $price = $product['sale_price'] ?? $product['price'];
            $total_price = $price * $quantity;

            $order_items_data[] = [
                'product_id' => $product['id'],
                'product_name' => $product['name'],
                'product_price' => $price,
                'quantity' => $quantity,
                'total_price' => $total_price
            ];
            $subtotal += $total_price;
        }

        $address_json = json_encode([
            'first_name' => 'ลูกค้า',
            'last_name' => 'ทดสอบ',
            'address_line1' => '123/45 หมู่ 6 ต.ทดสอบ',
            'city' => 'เลย',
            'postal_code' => '42000',
            'phone' => '0812345678'
        ]);

        $order_number = 'SIM-' . time() . $i;
        $status = $order_statuses[array_rand($order_statuses)];
        $payment_status = ($status === 'pending' || $status === 'cancelled') ? 'pending' : 'paid';
        $ordered_at = date('Y-m-d H:i:s', time() - rand(0, 30 * 24 * 60 * 60));

        // --- ส่วนที่แก้ไข ---
        $stmt_order->execute([
            $customer_id,
            $order_number,
            $status,
            $subtotal,
            $subtotal, // ใช้ subtotal แทน total_amount เพื่อความง่าย
            'bank_transfer', // payment_method
            $payment_status,
            $address_json,
            $address_json,
            $ordered_at
        ]);
        // --- สิ้นสุดส่วนที่แก้ไข ---

        $order_id = $pdo->lastInsertId();

        foreach ($order_items_data as $item) {
            $stmt_items->execute([
                $order_id,
                $item['product_id'],
                $item['product_name'],
                $item['product_price'],
                $item['quantity'],
                $item['total_price']
            ]);
        }
    }
    log_message("สร้างคำสั่งซื้อสำเร็จ!", "success");

    echo "<h1>การสร้างข้อมูลทดสอบเสร็จสมบูรณ์!</h1>";
    echo "<p><a href='admin/index.php'>ไปที่ระบบหลังบ้านเพื่อดูข้อมูล</a></p>";
} catch (Exception $e) {
    log_message("เกิดข้อผิดพลาดร้ายแรง: " . $e->getMessage(), "error");
}

echo "</body></html>";
