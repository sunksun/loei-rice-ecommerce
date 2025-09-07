<?php
// ตรวจสอบความเข้ากันได้ของระบบ
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='th'>
<head>
    <meta charset='UTF-8'>
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>ตรวจสอบความเข้ากันได้ - ข้าวพันธุ์พื้นเมืองเลย</title>
    <style>
        body { font-family: 'Kanit', sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .status { padding: 15px; margin: 10px 0; border-radius: 8px; }
        .success { background: #d4edda; color: #155724; }
        .warning { background: #fff3cd; color: #856404; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        pre { background: #f1f3f4; padding: 15px; border-radius: 8px; overflow: auto; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #27ae60; color: white; }
        .btn { display: inline-block; padding: 12px 24px; background: #27ae60; color: white; text-decoration: none; border-radius: 8px; margin: 5px; }
        .btn:hover { background: #219a52; }
    </style>
    <link href=\"https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap\" rel=\"stylesheet\">
</head>
<body>
<div class=\"container\">";

echo "<h1>🔍 ตรวจสอบความเข้ากันได้ของระบบ</h1>";

// 1. ข้อมูล PHP Server
echo "<h2>1. ข้อมูล PHP Server</h2>";
echo "<table>";
echo "<tr><th>รายการ</th><th>ค่า</th><th>สถานะ</th></tr>";

$php_version = PHP_VERSION;
$php_compatible = version_compare($php_version, '7.0.0', '>=');
echo "<tr><td>PHP Version</td><td>$php_version</td><td>" . ($php_compatible ? "✅ รองรับ" : "❌ ต้อง PHP 7.0+") . "</td></tr>";

$extensions_check = array(
    'PDO' => extension_loaded('pdo'),
    'PDO MySQL' => extension_loaded('pdo_mysql'),
    'mbstring' => extension_loaded('mbstring'),
    'JSON' => extension_loaded('json'),
    'Session' => extension_loaded('session')
);

foreach ($extensions_check as $ext => $loaded) {
    echo "<tr><td>$ext</td><td>" . ($loaded ? "โหลดแล้ว" : "ไม่พบ") . "</td><td>" . ($loaded ? "✅" : "❌") . "</td></tr>";
}

echo "</table>";

// 2. ตรวจสอบ Syntax ที่แก้ไข
echo "<h2>2. การแก้ไข Syntax ที่ทำไป</h2>";
echo "<div class='success'>✅ <strong>แก้ไขเสร็จสิ้น:</strong></div>";
echo "<ul>";
echo "<li>✅ แก้ไข JavaScript optional chaining (?.) ใน assets/js/main.js</li>";
echo "<li>✅ เปลี่ยน fetch() เป็น XMLHttpRequest ใน payment-notification.php</li>";
echo "<li>✅ แก้ไข URLSearchParams ใน login.php ให้รองรับ IE</li>";
echo "<li>✅ แยก CSS และ JS ออกจาก index.php</li>";
echo "</ul>";

// 3. ทดสอบ PHP Operators
echo "<h2>3. ทดสอบ PHP Operators</h2>";

// Null Coalescing Operator (??) - PHP 7.0+
$test_data = array('name' => 'ข้าวเหนียวแดง');
$category = $test_data['category'] ?? 'ทั่วไป';
echo "<div class='info'><strong>Null Coalescing (??):</strong> $category</div>";

// Ternary Operator (?:)
$featured = true;
$badge = $featured ? 'แนะนำ' : 'ทั่วไป';
echo "<div class='info'><strong>Ternary Operator:</strong> $badge</div>";

// Short Ternary Operator (?:) - PHP 5.3+
$title = '' ?: 'ไม่มีชื่อ';
echo "<div class='info'><strong>Short Ternary:</strong> $title</div>";

echo "<div class='success'>✅ PHP Operators ทำงานได้ปกติ</div>";

// 4. ทดสอบฐานข้อมูล
echo "<h2>4. ทดสอบการเชื่อมต่อฐานข้อมูล</h2>";
try {
    require_once 'config/database.php';
    $conn = getDB();
    echo "<div class='success'>✅ เชื่อมต่อฐานข้อมูลสำเร็จ</div>";
    
    // ทดสอบ Query พื้นฐาน
    $test_query = $conn->query("SHOW TABLES");
    $tables = $test_query->fetchAll(PDO::FETCH_COLUMN);
    echo "<div class='info'><strong>จำนวนตาราง:</strong> " . count($tables) . " ตาราง</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ ไม่สามารถเชื่อมต่อฐานข้อมูล: " . $e->getMessage() . "</div>";
}

// 5. ทดสอบไฟล์สำคัญ
echo "<h2>5. ตรวจสอบไฟล์สำคัญ</h2>";
$important_files = array(
    'index.php' => 'หน้าแรก',
    'assets/css/style.css' => 'CSS หลัก',
    'assets/js/main.js' => 'JavaScript หลัก',
    'config/database.php' => 'การตั้งค่าฐานข้อมูล',
    'config/config.php' => 'การตั้งค่าทั่วไป'
);

echo "<table>";
echo "<tr><th>ไฟล์</th><th>คำอธิบาย</th><th>สถานะ</th></tr>";
foreach ($important_files as $file => $desc) {
    $exists = file_exists($file);
    $size = $exists ? formatBytes(filesize($file)) : '-';
    echo "<tr><td>$file</td><td>$desc</td><td>" . ($exists ? "✅ ($size)" : "❌ ไม่พบ") . "</td></tr>";
}
echo "</table>";

// 6. การแนะนำสำหรับ Production
echo "<h2>6. 🚀 การเตรียมพร้อมสำหรับ Production Server</h2>";
echo "<div class='warning'>";
echo "<h3>สิ่งที่ต้องตรวจสอบบน Production Server:</h3>";
echo "<ol>";
echo "<li><strong>PHP Version:</strong> ตรวจสอบว่าเป็น PHP 7.0+ (แนะนำ PHP 8.0+)</li>";
echo "<li><strong>PHP Extensions:</strong> ตรวจสอบว่ามี PDO, pdo_mysql, mbstring</li>";
echo "<li><strong>Database:</strong> สร้างฐานข้อมูลและ import ข้อมูล</li>";
echo "<li><strong>File Permissions:</strong> ตั้งค่า chmod 755 สำหรับโฟลเดอร์ uploads/</li>";
echo "<li><strong>Error Reporting:</strong> ปิด error reporting ใน production</li>";
echo "<li><strong>HTTPS:</strong> ตรวจสอบ SSL Certificate</li>";
echo "</ol>";
echo "</div>";

echo "<h2>7. 🛠 คำสั่งสำหรับตรวจสอบ Server</h2>";
echo "<pre>";
echo "# ตรวจสอบ PHP Version
php -v

# ตรวจสอบ PHP Extensions
php -m | grep -E '(pdo|mysql|mbstring|json)'

# ตรวจสอบ Syntax Error
find . -name '*.php' -exec php -l {} \\;

# ตรวจสอบ File Permissions
ls -la uploads/
chmod 755 uploads/
chmod 644 uploads/*
";
echo "</pre>";

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<a href='index.php' class='btn'>🏠 กลับหน้าแรก</a>";
echo "<a href='test-syntax.php' class='btn'>🧪 ทดสอบ Syntax</a>";
echo "<a href='check-mysql-database.php' class='btn'>🔍 ตรวจสอบฐานข้อมูล</a>";
echo "</div>";

echo "</div>";

// JavaScript สำหรับทดสอบ compatibility
echo "<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ JavaScript DOMContentLoaded ทำงาน');
    
    // ทดสอบ basic JavaScript features
    var testResult = {
        json: typeof JSON !== 'undefined',
        addEventListener: typeof document.addEventListener !== 'undefined',
        querySelector: typeof document.querySelector !== 'undefined',
        localStorage: typeof localStorage !== 'undefined'
    };
    
    var compatDiv = document.createElement('div');
    compatDiv.className = 'info';
    compatDiv.innerHTML = '<h3>🌐 ทดสอบ JavaScript Compatibility</h3>';
    
    for (var feature in testResult) {
        var status = testResult[feature] ? '✅' : '❌';
        compatDiv.innerHTML += '<div>' + status + ' ' + feature + '</div>';
    }
    
    document.querySelector('.container').appendChild(compatDiv);
});
</script>";

echo "</body></html>";

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB');
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, $precision) . ' ' . $units[$i];
}
?>