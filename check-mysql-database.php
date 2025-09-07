<?php
// ตรวจสอบฐานข้อมูลจาก MySQL โดยตรง
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ตั้งค่า charset และ locale สำหรับภาษาไทย
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

echo '<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจสอบฐานข้อมูล MySQL - ข้าวพันธุ์พื้นเมืองเลย</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>';

echo "<style>
    body { 
        font-family: 'Sarabun', 'Kanit', 'Noto Sans Thai', Arial, sans-serif; 
        margin: 0; 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 20px;
    }
    .container {
        max-width: 1200px; 
        margin: 0 auto; 
        background: white; 
        padding: 30px; 
        border-radius: 15px; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    h1, h2, h3 { 
        font-family: 'Kanit', 'Sarabun', sans-serif;
        color: #2d5016;
        margin-top: 0;
    }
    h1 { text-align: center; color: #27ae60; font-size: 2.5rem; margin-bottom: 2rem; }
    .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 5px solid #28a745; }
    .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 5px solid #dc3545; }
    .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 5px solid #17a2b8; }
    .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 5px solid #ffc107; }
    table { 
        border-collapse: collapse; 
        width: 100%; 
        margin: 20px 0; 
        background: white; 
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-radius: 10px;
        overflow: hidden;
    }
    th, td { 
        padding: 15px; 
        text-align: left; 
        border-bottom: 1px solid #e0e0e0;
    }
    th { 
        background: linear-gradient(135deg, #27ae60, #2d5016); 
        color: white; 
        font-weight: 600;
        font-family: 'Kanit', sans-serif;
    }
    tr:hover { background-color: #f8f9fa; }
    .status-online { color: #28a745; font-weight: bold; }
    .status-offline { color: #dc3545; font-weight: bold; }
    .footer {
        text-align: center;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 2px solid #e0e0e0;
    }
    .footer a {
        display: inline-block;
        margin: 5px 15px;
        padding: 10px 20px;
        background: #27ae60;
        color: white;
        text-decoration: none;
        border-radius: 25px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    .footer a:hover {
        background: #219a52;
        transform: translateY(-2px);
    }
    .database-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin: 20px 0;
    }
    .info-card {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        border: 1px solid #e0e0e0;
    }
    .info-card h3 {
        margin-top: 0;
        color: #27ae60;
    }
</style>";

echo "<div class='container'>";
echo "<h1>🔍 ตรวจสอบฐานข้อมูล MySQL</h1>";

// 1. ตรวจสอบการเชื่อมต่อ MySQL
echo "<h2>1. สถานะการเชื่อมต่อ MySQL</h2>";
try {
    $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "<div class='success'>✅ เชื่อมต่อ MySQL สำเร็จ</div>";
    
    // ข้อมูล MySQL
    $version = $pdo->query("SELECT VERSION() as version")->fetch();
    $currentTime = $pdo->query("SELECT NOW() as current_datetime")->fetch();
    
    echo "<div class='database-info'>";
    echo "<div class='info-card'>";
    echo "<h3>📊 ข้อมูล MySQL Server</h3>";
    echo "<p><strong>เวอร์ชัน:</strong> " . $version['version'] . "</p>";
    echo "<p><strong>เวลาปัจจุบัน:</strong> " . $currentTime['current_datetime'] . "</p>";
    echo "<p><strong>สถานะ:</strong> <span class='status-online'>🟢 Online</span></p>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ ไม่สามารถเชื่อมต่อ MySQL ได้: " . $e->getMessage() . "</div>";
    echo "</div></body></html>";
    exit;
}

// 2. ตรวจสอบฐานข้อมูลทั้งหมด
echo "<h2>2. รายการฐานข้อมูลทั้งหมด</h2>";
try {
    $databases = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<table>";
    echo "<tr><th>ชื่อฐานข้อมูล</th><th>สถานะ</th><th>หมายเหตุ</th></tr>";
    
    $target_db_found = false;
    foreach ($databases as $db) {
        $status = "";
        $note = "";
        
        if (in_array($db, ['information_schema', 'mysql', 'performance_schema', 'sys'])) {
            $status = "🔧 ระบบ";
            $note = "ฐานข้อมูลระบบ";
        } elseif ($db === 'loei_rice_ecommerce') {
            $status = "<span style='color: #28a745; font-weight: bold;'>🎯 เป้าหมาย</span>";
            $note = "ฐานข้อมูลของโปรเจค";
            $target_db_found = true;
        } else {
            $status = "📁 ผู้ใช้";
            $note = "ฐานข้อมูลผู้ใช้อื่น";
        }
        
        echo "<tr>";
        echo "<td><strong>$db</strong></td>";
        echo "<td>$status</td>";
        echo "<td>$note</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if ($target_db_found) {
        echo "<div class='success'>✅ พบฐานข้อมูล 'loei_rice_ecommerce'</div>";
    } else {
        echo "<div class='error'>❌ ไม่พบฐานข้อมูล 'loei_rice_ecommerce'</div>";
        echo "<div class='warning'>💡 ต้องสร้างฐานข้อมูลก่อน หรือ import ไฟล์ SQL</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ ไม่สามารถดึงรายการฐานข้อมูลได้: " . $e->getMessage() . "</div>";
}

// 3. ตรวจสอบตารางในฐานข้อมูลเป้าหมาย (หากมี)
if ($target_db_found) {
    echo "<h2>3. ตารางในฐานข้อมูล 'loei_rice_ecommerce'</h2>";
    try {
        $pdo->exec("USE loei_rice_ecommerce");
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tables)) {
            echo "<div class='warning'>⚠️ ฐานข้อมูลว่างเปล่า - ไม่มีตารางใดๆ</div>";
            echo "<div class='info'>💡 ต้อง import ไฟล์ SQL เพื่อสร้างตาราง</div>";
        } else {
            echo "<div class='success'>✅ พบ " . count($tables) . " ตาราง</div>";
            
            echo "<table>";
            echo "<tr><th>ชื่อตาราง</th><th>จำนวนแถว</th><th>ขนาด (แถว)</th><th>สถานะ</th></tr>";
            
            $total_rows = 0;
            foreach ($tables as $table) {
                try {
                    $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                    $total_rows += $count;
                    
                    // ตรวจสอบว่าเป็นตารางสำคัญหรือไม่
                    $important_tables = ['products', 'categories', 'users', 'orders', 'admins'];
                    $status = in_array($table, $important_tables) ? "🔥 สำคัญ" : "📋 ปกติ";
                    
                    echo "<tr>";
                    echo "<td><strong>$table</strong></td>";
                    echo "<td>" . number_format($count) . "</td>";
                    echo "<td>" . ($count > 0 ? "มีข้อมูล" : "ว่าง") . "</td>";
                    echo "<td>$status</td>";
                    echo "</tr>";
                } catch (Exception $e) {
                    echo "<tr>";
                    echo "<td><strong>$table</strong></td>";
                    echo "<td colspan='3'><span style='color: #dc3545;'>Error: " . $e->getMessage() . "</span></td>";
                    echo "</tr>";
                }
            }
            echo "</table>";
            
            echo "<div class='info-card'>";
            echo "<h3>📈 สรุปข้อมูล</h3>";
            echo "<p><strong>จำนวนตารางทั้งหมด:</strong> " . count($tables) . " ตาราง</p>";
            echo "<p><strong>จำนวนข้อมูลทั้งหมด:</strong> " . number_format($total_rows) . " แถว</p>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ ไม่สามารถตรวจสอบตารางได้: " . $e->getMessage() . "</div>";
    }
}

// 4. ข้อมูล admin (หากมี)
if ($target_db_found) {
    echo "<h2>4. ข้อมูลผู้ดูแลระบบ</h2>";
    try {
        $pdo->exec("USE loei_rice_ecommerce");
        $admin_check = $pdo->query("SHOW TABLES LIKE 'admins'")->fetch();
        
        if ($admin_check) {
            $admins = $pdo->query("SELECT id, username, email, first_name, last_name, role, status, created_at FROM admins ORDER BY id")->fetchAll();
            
            if (empty($admins)) {
                echo "<div class='warning'>⚠️ ไม่มีข้อมูลผู้ดูแลระบบ</div>";
            } else {
                echo "<div class='success'>✅ พบ " . count($admins) . " บัญชีผู้ดูแลระบบ</div>";
                
                echo "<table>";
                echo "<tr><th>ID</th><th>Username</th><th>ชื่อ-นามสกุล</th><th>อีเมล</th><th>บทบาท</th><th>สถานะ</th><th>วันที่สร้าง</th></tr>";
                
                foreach ($admins as $admin) {
                    $status_badge = $admin['status'] === 'active' ? 
                        "<span style='color: #28a745;'>🟢 ใช้งาน</span>" : 
                        "<span style='color: #dc3545;'>🔴 ปิด</span>";
                    
                    $role_badge = '';
                    switch($admin['role']) {
                        case 'super_admin': $role_badge = '👑 Super Admin'; break;
                        case 'admin': $role_badge = '⚙️ Admin'; break;
                        case 'editor': $role_badge = '✏️ Editor'; break;
                        default: $role_badge = $admin['role'];
                    }
                    
                    echo "<tr>";
                    echo "<td>" . $admin['id'] . "</td>";
                    echo "<td><strong>" . htmlspecialchars($admin['username']) . "</strong></td>";
                    echo "<td>" . htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($admin['email']) . "</td>";
                    echo "<td>$role_badge</td>";
                    echo "<td>$status_badge</td>";
                    echo "<td>" . date('d/m/Y', strtotime($admin['created_at'])) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } else {
            echo "<div class='error'>❌ ไม่พบตาราง 'admins'</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ ไม่สามารถตรวจสอบข้อมูลผู้ดูแลได้: " . $e->getMessage() . "</div>";
    }
}

// 5. สรุปและแนะนำ
echo "<h2>5. สรุปและขั้นตอนถัดไป</h2>";

if ($target_db_found) {
    echo "<div class='success'>🎉 <strong>สถานะ:</strong> ฐานข้อมูลพร้อมใช้งาน</div>";
    echo "<div class='info'>💡 <strong>แนะนำ:</strong> สามารถทดสอบระบบได้แล้ว</div>";
} else {
    echo "<div class='error'>❌ <strong>สถานะ:</strong> ยังไม่พร้อมใช้งาน</div>";
    echo "<div class='warning'>📋 <strong>ขั้นตอนถัดไป:</strong><br>
        1. สร้างฐานข้อมูล 'loei_rice_ecommerce'<br>
        2. Import ไฟล์ loei_rice_ecommerce.sql<br>
        3. ทดสอบระบบใหม่
    </div>";
}

echo "<div class='footer'>";
echo "<p><strong>เวลาตรวจสอบ:</strong> " . date('d/m/Y H:i:s') . " น.</p>";
echo "<a href='index.php'>🏠 หน้าแรก</a>";
echo "<a href='test-database.php'>🔧 ทดสอบ Database</a>";
echo "<a href='http://localhost/phpmyadmin/' target='_blank'>💾 phpMyAdmin</a>";
echo "</div>";

echo "</div>";
echo "</body></html>";
?>