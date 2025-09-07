<?php
// เริ่ม output buffering ก่อน
ob_start();

// ตั้งค่า session ก่อน headers
ini_set('session.name', 'test_session');
ini_set('session.gc_maxlifetime', 1800);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.use_strict_mode', 1);

// ทดสอบการเชื่อมต่อฐานข้อมูล
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ตั้งค่า charset และ locale สำหรับภาษาไทย
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'th_TH.UTF-8');

echo '<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ทดสอบการเชื่อมต่อฐานข้อมูล - ข้าวพันธุ์พื้นเมืองเลย</title>
</head>
<body>';

echo "<h1>🔧 ทดสอบการเชื่อมต่อฐานข้อมูล</h1>";
echo "<style>
    body { 
        font-family: 'Sarabun', 'Kanit', 'Noto Sans Thai', Arial, sans-serif; 
        margin: 20px; 
        background: #f5f5f5; 
        line-height: 1.6;
    }
    h1, h2, h3 { 
        font-family: 'Kanit', 'Sarabun', sans-serif;
        color: #2d5016;
    }
    .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 15px 0; border: 1px solid #c3e6cb; }
    .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0; border: 1px solid #f5c6cb; }
    .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; margin: 15px 0; border: 1px solid #bee5eb; }
    .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 15px 0; border: 1px solid #ffeaa7; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #e9ecef; overflow-x: auto; font-size: 14px; }
    table { border-collapse: collapse; width: 100%; margin: 15px 0; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background-color: #27ae60; color: white; font-weight: bold; }
    tr:nth-child(even) { background-color: #f9f9f9; }
    a { color: #27ae60; text-decoration: none; font-weight: bold; }
    a:hover { color: #219a52; text-decoration: underline; }
    code { background: #f1f3f4; padding: 2px 4px; border-radius: 3px; font-family: 'Courier New', monospace; }
    .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
</style>

<link href='https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600&display=swap' rel='stylesheet'>

<div class='container'>";

// 1. ทดสอบการเชื่อมต่อฐานข้อมูลพื้นฐาน
echo "<h2>1. ทดสอบการเชื่อมต่อ MySQL</h2>";
try {
    $host = 'localhost';
    $port = 3306;
    $username = 'root';
    $password = '';
    
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "<div class='success'>✅ การเชื่อมต่อ MySQL สำเร็จ</div>";
    
    // แสดงเวอร์ชัน MySQL
    $stmt = $pdo->query("SELECT VERSION() as version");
    $version = $stmt->fetch();
    echo "<div class='info'>📊 MySQL Version: " . $version['version'] . "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ การเชื่อมต่อ MySQL ล้มเหลว: " . $e->getMessage() . "</div>";
    echo "<div class='warning'>💡 กรุณาตรวจสอบ:<br>
    - XAMPP MySQL service ทำงานอยู่หรือไม่<br>
    - Username/Password ถูกต้องหรือไม่<br>
    - Port 3306 ถูกใช้งานโดยโปรแกรมอื่นหรือไม่</div>";
    exit;
}

// 2. ตรวจสอบฐานข้อมูล loei_rice_ecommerce
echo "<h2>2. ตรวจสอบฐานข้อมูล loei_rice_ecommerce</h2>";
try {
    $stmt = $pdo->query("SHOW DATABASES LIKE 'loei_rice_ecommerce'");
    $db_exists = $stmt->rowCount() > 0;
    
    if ($db_exists) {
        echo "<div class='success'>✅ ฐานข้อมูล 'loei_rice_ecommerce' มีอยู่ในระบบ</div>";
        
        // เชื่อมต่อไปยังฐานข้อมูลเฉพาะ
        $dsn = "mysql:host=$host;port=$port;dbname=loei_rice_ecommerce;charset=utf8mb4";
        $db = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
    } else {
        echo "<div class='error'>❌ ไม่พบฐานข้อมูล 'loei_rice_ecommerce'</div>";
        echo "<div class='warning'>💡 กรุณาสร้างฐานข้อมูลโดย:<br>
        1. เปิด phpMyAdmin (http://localhost/phpmyadmin)<br>
        2. คลิก 'New' เพื่อสร้างฐานข้อมูลใหม่<br>
        3. ตั้งชื่อ: loei_rice_ecommerce<br>
        4. เลือก Collation: utf8mb4_unicode_ci</div>";
        
        // สร้างฐานข้อมูลอัตโนมัติ
        echo "<h3>🔧 กำลังสร้างฐานข้อมูลอัตโนมัติ...</h3>";
        try {
            $pdo->exec("CREATE DATABASE loei_rice_ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "<div class='success'>✅ สร้างฐานข้อมูล 'loei_rice_ecommerce' สำเร็จ</div>";
            
            // เชื่อมต่อไปยังฐานข้อมูลที่สร้างใหม่
            $dsn = "mysql:host=$host;port=$port;dbname=loei_rice_ecommerce;charset=utf8mb4";
            $db = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
        } catch (Exception $e) {
            echo "<div class='error'>❌ ไม่สามารถสร้างฐานข้อมูลได้: " . $e->getMessage() . "</div>";
            exit;
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ เกิดข้อผิดพลาดในการตรวจสอบฐานข้อมูล: " . $e->getMessage() . "</div>";
    exit;
}

// 3. ตรวจสอบตารางที่จำเป็น
echo "<h2>3. ตรวจสอบตารางที่จำเป็น</h2>";
$required_tables = [
    'users' => 'ตารางผู้ใช้',
    'categories' => 'ตารางหมวดหมู่สินค้า',
    'products' => 'ตารางสินค้า',
    'orders' => 'ตารางคำสั่งซื้อ',
    'order_items' => 'ตารางรายการสินค้าในคำสั่งซื้อ',
    'cart' => 'ตารางตะกร้าสินค้า',
    'reviews' => 'ตารางรีวิว',
    'addresses' => 'ตารางที่อยู่',
    'site_settings' => 'ตารางการตั้งค่าระบบ'
];

$missing_tables = [];
$existing_tables = [];

foreach ($required_tables as $table => $description) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $existing_tables[] = $table;
            echo "<div class='success'>✅ $description ($table)</div>";
        } else {
            $missing_tables[] = $table;
            echo "<div class='error'>❌ $description ($table) - ไม่พบตาราง</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ ไม่สามารถตรวจสอบตาราง $table: " . $e->getMessage() . "</div>";
    }
}

// 4. แสดงรายละเอียดตารางที่มีอยู่
if (!empty($existing_tables)) {
    echo "<h2>4. รายละเอียดตารางที่มีอยู่</h2>";
    echo "<table>";
    echo "<tr><th>ตาราง</th><th>จำนวนแถว</th><th>คอลัมน์หลัก</th></tr>";
    
    foreach ($existing_tables as $table) {
        try {
            // นับจำนวนแถว
            $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            
            // ดึงคอลัมน์
            $stmt = $db->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $column_list = implode(', ', array_slice($columns, 0, 5));
            if (count($columns) > 5) $column_list .= '...';
            
            echo "<tr>";
            echo "<td>$table</td>";
            echo "<td>$count แถว</td>";
            echo "<td>$column_list</td>";
            echo "</tr>";
        } catch (Exception $e) {
            echo "<tr><td>$table</td><td colspan='2'>Error: " . $e->getMessage() . "</td></tr>";
        }
    }
    echo "</table>";
}

// 5. ทดสอบการ Query จริง
if (in_array('products', $existing_tables) && in_array('categories', $existing_tables)) {
    echo "<h2>5. ทดสอบ Query ที่มีปัญหา</h2>";
    try {
        $stmt = $db->query("
            SELECT p.*, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.featured = 1 AND p.status = 'active'
            ORDER BY p.created_at DESC
            LIMIT 6
        ");
        $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<div class='success'>✅ Query สินค้าแนะนำทำงานได้ (" . count($featured_products) . " รายการ)</div>";
        
        if (!empty($featured_products)) {
            echo "<h3>ตัวอย่างข้อมูลสินค้าแนะนำ:</h3>";
            echo "<pre>" . print_r($featured_products[0], true) . "</pre>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Query สินค้าแนะนำล้มเหลว: " . $e->getMessage() . "</div>";
    }
}

// 6. ทดสอบการเชื่อมต่อผ่าน config files (แบบ isolated)
echo "<h2>6. ทดสอบการเชื่อมต่อผ่าน Database Class</h2>";
try {
    // เฉพาะ database.php เท่านั้น เพื่อหลีกเลี่ยง session conflicts
    require_once 'config/database.php';
    
    $conn = getDB();
    echo "<div class='success'>✅ การเชื่อมต่อผ่าน Database class สำเร็จ</div>";
    
    // ทดสอบ query ง่าย ๆ
    $stmt = $conn->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "<div class='success'>✅ ทดสอบ query พื้นฐานสำเร็จ (result: " . $result['test'] . ")</div>";
    
    // ทดสอบการเชื่อมต่อฐานข้อมูลเป้าหมาย
    try {
        $conn->exec("USE loei_rice_ecommerce");
        echo "<div class='success'>✅ เชื่อมต่อฐานข้อมูล 'loei_rice_ecommerce' สำเร็จ</div>";
    } catch (Exception $e) {
        echo "<div class='error'>❌ ไม่สามารถใช้ฐานข้อมูล 'loei_rice_ecommerce': " . $e->getMessage() . "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ การเชื่อมต่อผ่าน Database class ล้มเหลว: " . $e->getMessage() . "</div>";
    echo "<div class='info'>📝 รายละเอียด Error:<br><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></div>";
}

// 7. สรุปและคำแนะนำ
echo "<h2>7. สรุปและคำแนะนำ</h2>";

if (empty($missing_tables)) {
    echo "<div class='success'>🎉 ระบบฐานข้อมูลพร้อมใช้งาน!</div>";
} else {
    echo "<div class='warning'>⚠️ ตารางที่ยังไม่มี: " . implode(', ', $missing_tables) . "</div>";
    echo "<div class='info'>💡 แนะนำ:<br>
    1. ตรวจสอบว่ามีไฟล์ .sql สำหรับสร้างตารางหรือไม่ (ในโฟลเดอร์ database/)<br>
    2. Import โครงสร้างฐานข้อมูลผ่าน phpMyAdmin<br>
    3. หากไม่มีไฟล์ .sql ต้องสร้างตารางเหล่านี้ด้วยตนเอง</div>";
}

echo "<hr>";
echo "<p><strong>เวลาทดสอบ:</strong> " . date('d/m/Y H:i:s', time()) . " น.</p>";
echo "<p><a href='index.php'>🏠 กลับไปหน้าแรก</a> | <a href='test-index.html'>🔧 ทดสอบ Server</a> | <a href='http://localhost/phpmyadmin/' target='_blank'>💾 phpMyAdmin</a></p>";

echo '</div>
</body>
</html>';
?>