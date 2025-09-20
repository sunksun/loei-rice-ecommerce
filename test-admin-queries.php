<?php
// ทดสอบ SQL queries ในหน้า admin dashboard
require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html lang='th'>";
echo "<head>";
echo "    <meta charset='UTF-8'>";
echo "    <title>ทดสอบ Admin Queries</title>";
echo "    <style>";
echo "        body { font-family: 'Arial', sans-serif; margin: 20px; background: #f5f5f5; }";
echo "        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }";
echo "        h1, h2 { color: #333; }";
echo "        .query-result { background: #f8f9fa; border-left: 4px solid #007bff; padding: 15px; margin: 15px 0; }";
echo "        .error { background: #f8d7da; border-left: 4px solid #dc3545; }";
echo "        .success { background: #d4edda; border-left: 4px solid #28a745; }";
echo "        table { width: 100%; border-collapse: collapse; margin: 10px 0; }";
echo "        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }";
echo "        th { background: #007bff; color: white; }";
echo "        code { background: #e9ecef; padding: 2px 4px; border-radius: 3px; }";
echo "    </style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";

echo "<h1>🔍 ทดสอบ SQL Queries สำหรับ Admin Dashboard</h1>";

try {
    $pdo = getDB();
    echo "<div class='query-result success'>";
    echo "<strong>✅ เชื่อมต่อฐานข้อมูลสำเร็จ</strong><br>";
    echo "Database: " . $pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "</div>";

    // 1. ทดสอบการนับสินค้าทั้งหมด
    echo "<h2>📊 สถิติพื้นฐาน</h2>";
    
    echo "<div class='query-result'>";
    echo "<h3>1. จำนวนสินค้าทั้งหมด</h3>";
    echo "<code>SELECT COUNT(*) FROM products</code><br>";
    $stmt_products = $pdo->query("SELECT COUNT(*) FROM products");
    $products_count = $stmt_products->fetchColumn();
    echo "<strong>ผลลัพธ์:</strong> $products_count รายการ";
    echo "</div>";

    // 2. ทดสอบการนับคำสั่งซื้อ
    echo "<div class='query-result'>";
    echo "<h3>2. จำนวนคำสั่งซื้อ (ไม่รวมที่ยกเลิก)</h3>";
    echo "<code>SELECT COUNT(*) FROM orders WHERE status != 'cancelled'</code><br>";
    $stmt_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status != 'cancelled'");
    $orders_count = $stmt_orders->fetchColumn();
    echo "<strong>ผลลัพธ์:</strong> $orders_count คำสั่งซื้อ";
    echo "</div>";

    // 3. ทดสอบการนับลูกค้า
    echo "<div class='query-result'>";
    echo "<h3>3. จำนวนลูกค้าที่ใช้งานอยู่</h3>";
    echo "<code>SELECT COUNT(*) FROM users WHERE status = 'active'</code><br>";
    $stmt_customers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
    $customers_count = $stmt_customers->fetchColumn();
    echo "<strong>ผลลัพธ์:</strong> $customers_count คน";
    echo "</div>";

    // 4. แสดงตาราง users เพื่อตรวจสอบ status
    echo "<h2>👥 ตรวจสอบตาราง Users</h2>";
    echo "<div class='query-result'>";
    $stmt_all_users = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as full_name, email, status, created_at FROM users LIMIT 10");
    $all_users = $stmt_all_users->fetchAll(PDO::FETCH_ASSOC);
    
    if ($all_users) {
        echo "<table>";
        echo "<tr><th>ID</th><th>ชื่อ</th><th>อีเมล</th><th>สถานะ</th><th>วันที่สร้าง</th></tr>";
        foreach ($all_users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['full_name'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['status']) . "</td>";
            echo "<td>" . htmlspecialchars($user['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<strong>ไม่มีข้อมูลในตาราง users</strong>";
    }
    echo "</div>";

    // 5. แสดงตาราง orders เพื่อตรวจสอบ status
    echo "<h2>🛒 ตรวจสอบตาราง Orders</h2>";
    echo "<div class='query-result'>";
    $stmt_all_orders = $pdo->query("SELECT id, order_number, status, total_amount, ordered_at FROM orders LIMIT 10");
    $all_orders = $stmt_all_orders->fetchAll(PDO::FETCH_ASSOC);
    
    if ($all_orders) {
        echo "<table>";
        echo "<tr><th>ID</th><th>เลขที่คำสั่งซื้อ</th><th>สถานะ</th><th>ยอดรวม</th><th>วันที่สั่งซื้อ</th></tr>";
        foreach ($all_orders as $order) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($order['id']) . "</td>";
            echo "<td>" . htmlspecialchars($order['order_number']) . "</td>";
            echo "<td>" . htmlspecialchars($order['status']) . "</td>";
            echo "<td>" . number_format($order['total_amount'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($order['ordered_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<strong>ไม่มีข้อมูลในตาราง orders</strong>";
    }
    echo "</div>";

    // 6. ทดสอบสต็อกสินค้า
    echo "<h2>📦 ตรวจสอบสต็อกสินค้า</h2>";
    echo "<div class='query-result'>";
    $stmt_stock = $pdo->query("SELECT name, stock_quantity, min_stock_level FROM products WHERE stock_quantity > 0 LIMIT 10");
    $stock_data = $stmt_stock->fetchAll(PDO::FETCH_ASSOC);
    
    if ($stock_data) {
        echo "<table>";
        echo "<tr><th>ชื่อสินค้า</th><th>สต็อกปัจจุบัน</th><th>สต็อกขั้นต่ำ</th><th>สถานะ</th></tr>";
        foreach ($stock_data as $item) {
            $status = '';
            if ($item['stock_quantity'] <= 0) {
                $status = '❌ หมด';
            } elseif ($item['stock_quantity'] <= $item['min_stock_level']) {
                $status = '⚠️ ใกล้หมด';
            } else {
                $status = '✅ ปกติ';
            }
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($item['name']) . "</td>";
            echo "<td>" . $item['stock_quantity'] . "</td>";
            echo "<td>" . $item['min_stock_level'] . "</td>";
            echo "<td>" . $status . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<strong>ไม่มีข้อมูลสินค้าที่มีสต็อก</strong>";
    }
    echo "</div>";

    // สรุปปัญหาที่พบ
    echo "<h2>🔧 สรุปการตรวจสอบ</h2>";
    echo "<div class='query-result'>";
    echo "<h3>ผลการตรวจสอบ:</h3>";
    echo "<ul>";
    echo "<li><strong>สินค้าทั้งหมด:</strong> $products_count รายการ</li>";
    echo "<li><strong>คำสั่งซื้อ:</strong> $orders_count คำสั่ง</li>";
    echo "<li><strong>ลูกค้า (active):</strong> $customers_count คน</li>";
    echo "</ul>";
    
    if ($products_count == 0 || $orders_count == 0 || $customers_count == 0) {
        echo "<div class='error'>";
        echo "<h4>⚠️ พบปัญหา:</h4>";
        
        if ($products_count == 0) {
            echo "<p>❌ <strong>ไม่มีข้อมูลสินค้าในตาราง products</strong> - ต้องเพิ่มข้อมูลสินค้าก่อน</p>";
        }
        
        if ($orders_count == 0) {
            echo "<p>❌ <strong>ไม่มีคำสั่งซื้อในระบบ</strong> - ปกติสำหรับระบบใหม่</p>";
        }
        
        if ($customers_count == 0) {
            echo "<p>❌ <strong>ไม่มีลูกค้าที่มี status = 'active'</strong> - ตรวจสอบค่า status ในตาราง users</p>";
        }
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<h4>✅ ระบบทำงานปกติ</h4>";
        echo "<p>ข้อมูลทั้งหมดแสดงผลถูกต้องใน Admin Dashboard</p>";
        echo "</div>";
    }
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='query-result error'>";
    echo "<strong>❌ เกิดข้อผิดพลาด:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<a href='admin/index.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>🏠 ไปยัง Admin Dashboard</a>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>