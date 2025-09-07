<?php
// สคริปต์ Export ฐานข้อมูลขึ้น Server
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

echo '<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Database to Server - ข้าวพันธุ์พื้นเมืองเลย</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: "Kanit", sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0; padding: 20px; min-height: 100vh;
        }
        .container {
            max-width: 1000px; margin: 0 auto; background: white; 
            padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        h1 { color: #27ae60; text-align: center; margin-bottom: 2rem; }
        .method { background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 10px; border-left: 5px solid #27ae60; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; margin: 15px 0; }
        pre, code { background: #f1f3f4; padding: 10px; border-radius: 5px; font-family: "Courier New", monospace; }
        .btn { display: inline-block; padding: 10px 20px; background: #27ae60; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn:hover { background: #219a52; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #27ae60; color: white; }
    </style>
</head>
<body>
<div class="container">';

echo "<h1>🚀 Export Database to Server</h1>";

// 1. ตรวจสอบฐานข้อมูล Local
echo "<h2>1. ตรวจสอบฐานข้อมูล Local</h2>";
try {
    $local_pdo = new PDO('mysql:host=localhost;dbname=loei_rice_ecommerce;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $tables = $local_pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<div class='success'>✅ เชื่อมต่อฐานข้อมูล Local สำเร็จ - พบ " . count($tables) . " ตาราง</div>";
    
    if (!empty($tables)) {
        echo "<table><tr><th>ตาราง</th><th>จำนวนข้อมูล</th></tr>";
        $total_records = 0;
        foreach ($tables as $table) {
            $count = $local_pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            $total_records += $count;
            echo "<tr><td>$table</td><td>" . number_format($count) . " แถว</td></tr>";
        }
        echo "</table>";
        echo "<div class='info'>📊 รวมข้อมูลทั้งหมด: " . number_format($total_records) . " แถว</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ ไม่สามารถเชื่อมต่อฐานข้อมูล Local: " . $e->getMessage() . "</div>";
    echo "<div class='warning'>💡 กรุณา Import ฐานข้อมูลลง XAMPP ก่อน</div>";
}

// 2. วิธีการ Export
echo "<h2>2. วิธีการ Export ฐานข้อมูลขึ้น Server</h2>";

// วิธีที่ 1: mysqldump
echo "<div class='method'>";
echo "<h3>🔧 วิธีที่ 1: ใช้ mysqldump Command</h3>";
echo "<p><strong>สำหรับ:</strong> Export ฐานข้อมูลเป็นไฟล์ .sql</p>";
echo "<pre>cd /Applications/XAMPP/xamppfiles/htdocs/loei-rice-ecommerce
/Applications/XAMPP/xamppfiles/bin/mysqldump -u root loei_rice_ecommerce > export_$(date +%Y%m%d_%H%M%S).sql</pre>";

if (isset($local_pdo)) {
    $export_btn = "<a href='?export=sql' class='btn'>📁 Export SQL File</a>";
    echo $export_btn;
}
echo "</div>";

// วิธีที่ 2: phpMyAdmin
echo "<div class='method'>";
echo "<h3>🌐 วิธีที่ 2: ใช้ phpMyAdmin</h3>";
echo "<p><strong>สำหรับ:</strong> Export ผ่าน Web Interface</p>";
echo "<ol>";
echo "<li>เปิด <a href='http://localhost/phpmyadmin' target='_blank'>phpMyAdmin</a></li>";
echo "<li>เลือกฐานข้อมูล <code>loei_rice_ecommerce</code></li>";
echo "<li>คลิกแท็บ <strong>Export</strong></li>";
echo "<li>เลือก <strong>Custom</strong> export method</li>";
echo "<li>เลือกตารางที่ต้องการ</li>";
echo "<li>เลือกรูปแบบ <strong>SQL</strong></li>";
echo "<li>คลิก <strong>Go</strong> เพื่อดาวน์โหลด</li>";
echo "</ol>";
echo "<a href='http://localhost/phpmyadmin' target='_blank' class='btn btn-secondary'>🔗 เปิด phpMyAdmin</a>";
echo "</div>";

// วิธีที่ 3: PHP Export
echo "<div class='method'>";
echo "<h3>💻 วิธีที่ 3: PHP Auto Export</h3>";
echo "<p><strong>สำหรับ:</strong> Export อัตโนมัติผ่านสคริปต์</p>";

if (isset($local_pdo) && isset($_GET['export']) && $_GET['export'] === 'sql') {
    echo "<div class='info'>🔄 กำลัง Export ฐานข้อมูล...</div>";
    
    try {
        $export_file = "loei_rice_ecommerce_" . date('Y-m-d_H-i-s') . ".sql";
        
        $sql_export = generateSQLExport($local_pdo);
        file_put_contents($export_file, $sql_export);
        
        echo "<div class='success'>✅ Export สำเร็จ!</div>";
        echo "<div class='info'>📁 ไฟล์: <code>$export_file</code></div>";
        echo "<div class='info'>📊 ขนาด: " . formatFileSize(filesize($export_file)) . "</div>";
        echo "<a href='$export_file' download class='btn'>📥 ดาวน์โหลดไฟล์</a>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Export ล้มเหลว: " . $e->getMessage() . "</div>";
    }
}
echo "</div>";

// 3. การอัพโหลดไปยัง Server
echo "<h2>3. การอัพโหลดไปยัง Server</h2>";
echo "<div class='method'>";
echo "<h3>📤 ขั้นตอนการอัพโหลด</h3>";
echo "<ol>";
echo "<li><strong>Export ฐานข้อมูล</strong> - ใช้วิธีใดวิธีหนึ่งด้านบน</li>";
echo "<li><strong>เข้าสู่ Control Panel ของ Hosting</strong></li>";
echo "<li><strong>เปิด phpMyAdmin บน Server</strong></li>";
echo "<li><strong>สร้างฐานข้อมูล</strong> ชื่อตามที่กำหนดใน hosting</li>";
echo "<li><strong>Import ไฟล์ .sql</strong> ที่ export มา</li>";
echo "<li><strong>แก้ไข config/database.php</strong> ให้ตรงกับ server</li>";
echo "</ol>";

// แสดงการตั้งค่า Server
echo "<h4>🔧 การตั้งค่าสำหรับ Server</h4>";
echo "<pre>
// Server Configuration (อัพเดทในไฟล์ config/database.php)
private \$host = 'localhost';
private \$db_name = 'loeirice_ecommerce';  // ชื่อที่ได้จาก hosting
private \$username = 'loeirice_ecommerce'; // username จาก hosting
private \$password = '54pbeJZbqxDgmX57mmp6'; // password จาก hosting
</pre>";

echo "</div>";

// 4. การ Sync แบบอัตโนมัติ
echo "<h2>4. การ Sync แบบอัตโนมัติ (Advanced)</h2>";
echo "<div class='method'>";
echo "<h3>🔄 Auto Sync Script</h3>";
echo "<p>สำหรับการซิงค์ข้อมูลระหว่าง Local และ Server แบบอัตโนมัติ</p>";
echo "<a href='sync-database.php' class='btn'>⚙️ ตั้งค่า Auto Sync</a>";
echo "</div>";

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<a href='index.php' class='btn'>🏠 หน้าแรก</a>";
echo "<a href='check-mysql-database.php' class='btn btn-secondary'>🔍 ตรวจสอบ Database</a>";
echo "</div>";

echo "</div></body></html>";

// ฟังก์ชัน Helper
function generateSQLExport($pdo) {
    $sql_export = "-- Export Database: loei_rice_ecommerce\n";
    $sql_export .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    $sql_export .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sql_export .= "START TRANSACTION;\n";
    $sql_export .= "SET time_zone = \"+00:00\";\n\n";
    
    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        // Get table structure
        $create_table = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        $sql_export .= "\n-- Structure for table `$table`\n";
        $sql_export .= "DROP TABLE IF EXISTS `$table`;\n";
        
        // ใช้ key ที่ถูกต้อง
        $create_key = array_keys($create_table)[1]; // มักจะเป็น index ที่ 1
        $sql_export .= $create_table[$create_key] . ";\n\n";
        
        // Get table data
        $data = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($data)) {
            $sql_export .= "-- Data for table `$table`\n";
            $columns = array_keys($data[0]);
            $sql_export .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES\n";
            
            $values = [];
            foreach ($data as $row) {
                $row_values = array_map(function($value) use ($pdo) {
                    return $value === null ? 'NULL' : $pdo->quote($value);
                }, $row);
                $values[] = "(" . implode(', ', $row_values) . ")";
            }
            $sql_export .= implode(",\n", $values) . ";\n\n";
        }
    }
    
    $sql_export .= "COMMIT;\n";
    return $sql_export;
}

function formatFileSize($size) {
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, 2) . ' ' . $units[$i];
}
?>