<?php
// เครื่องมือทดสอบรหัสผ่าน
// บันทึกเป็นไฟล์ test_password.php

// เชื่อมต่อฐานข้อมูล
require_once 'config/database.php';
$pdo = getDB();

echo "<h1>🔐 เครื่องมือทดสอบรหัสผ่าน</h1>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.container { background: white; padding: 20px; border-radius: 10px; margin: 10px 0; }
.success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 10px 0; }
.error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 10px 0; }
.info { background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; margin: 10px 0; }
code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; }
pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>";

// 1. ตรวจสอบข้อมูลใน database
echo "<div class='container'>";
echo "<h2>📊 ข้อมูลในฐานข้อมูล</h2>";

$stmt = $pdo->prepare("SELECT id, email, password FROM users WHERE email IN (?, ?) ORDER BY id DESC");
$stmt->execute(['test@example.com', 'jaruwan.lak@gmail.com']);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    echo "<div class='info'>";
    echo "<strong>User ID:</strong> {$user['id']}<br>";
    echo "<strong>Email:</strong> {$user['email']}<br>";
    echo "<strong>Password Hash:</strong><br>";
    echo "<code style='word-break: break-all;'>{$user['password']}</code><br>";
    echo "<strong>Hash Length:</strong> " . strlen($user['password']) . " characters<br>";
    echo "</div>";
}
echo "</div>";

// 2. ทดสอบการสร้าง hash ใหม่
echo "<div class='container'>";
echo "<h2>🧪 ทดสอบการสร้าง Hash</h2>";

$test_password = 'password123';
$new_hash = password_hash($test_password, PASSWORD_DEFAULT);

echo "<div class='info'>";
echo "<strong>Test Password:</strong> <code>$test_password</code><br>";
echo "<strong>New Generated Hash:</strong><br>";
echo "<code style='word-break: break-all;'>$new_hash</code><br>";
echo "<strong>New Hash Length:</strong> " . strlen($new_hash) . " characters<br>";
echo "</div>";

// 3. ทดสอบการตรวจสอบ
echo "<h3>🔍 ทดสอบการตรวจสอบรหัสผ่าน</h3>";

foreach ($users as $user) {
    echo "<div class='info'>";
    echo "<strong>Testing User:</strong> {$user['email']}<br>";

    $verify_result = password_verify($test_password, $user['password']);
    echo "<strong>password_verify('$test_password', hash):</strong> " . ($verify_result ? '✅ SUCCESS' : '❌ FAILED') . "<br>";

    if (!$verify_result) {
        echo "<strong>Issue:</strong> Hash ในฐานข้อมูลไม่ตรงกับรหัสผ่าน '$test_password'<br>";
    }
    echo "</div>";
}

// ทดสอบ hash ที่สร้างใหม่
echo "<div class='success'>";
echo "<strong>ทดสอบ Hash ที่สร้างใหม่:</strong><br>";
$verify_new = password_verify($test_password, $new_hash);
echo "<strong>password_verify('$test_password', new_hash):</strong> " . ($verify_new ? '✅ SUCCESS' : '❌ FAILED') . "<br>";
echo "</div>";
echo "</div>";

// 4. แสดงคำสั่ง SQL สำหรับแก้ไข
echo "<div class='container'>";
echo "<h2>🛠️ คำสั่ง SQL สำหรับแก้ไข</h2>";

echo "<div class='info'>";
echo "<h3>ตัวเลือก 1: อัพเดทด้วย Hash ใหม่ที่สร้าง</h3>";
echo "<pre>";
echo "UPDATE users \n";
echo "SET password = '$new_hash' \n";
echo "WHERE email = 'test@example.com';\n\n";
echo "-- หรือ\n\n";
echo "UPDATE users \n";
echo "SET password = '$new_hash' \n";
echo "WHERE email = 'jaruwan.lak@gmail.com';";
echo "</pre>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>ตัวเลือก 2: ใช้ Hash ที่ทราบว่าถูกต้อง</h3>";
echo "<pre>";
$known_good_hash = '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm'; // test123
echo "-- Hash นี้สำหรับรหัสผ่าน 'test123'\n";
echo "UPDATE users \n";
echo "SET password = '$known_good_hash' \n";
echo "WHERE email = 'test@example.com';\n\n";
echo "-- แล้วทดสอบด้วย email: test@example.com และ password: test123";
echo "</pre>";
echo "</div>";

echo "<div class='success'>";
echo "<h3>ตัวเลือก 3: สร้าง User ใหม่เลย</h3>";
echo "<pre>";
echo "-- ลบ user เก่าก่อน (ถ้าต้องการ)\n";
echo "DELETE FROM users WHERE email = 'test@example.com';\n\n";
echo "-- สร้าง user ใหม่\n";
echo "INSERT INTO users (first_name, last_name, email, password, status, email_verified) \n";
echo "VALUES ('ผู้ใช้', 'ทดสอบ', 'test@example.com', '$new_hash', 'active', 1);";
echo "</pre>";
echo "</div>";
echo "</div>";

// 5. ทดสอบแบบง่าย
echo "<div class='container'>";
echo "<h2>🎯 แนะนำขั้นตอนการแก้ไข</h2>";
echo "<div class='success'>";
echo "<ol>";
echo "<li><strong>คัดลอก hash ใหม่:</strong> <code>$new_hash</code></li>";
echo "<li><strong>รันคำสั่ง SQL:</strong><br>";
echo "<code>UPDATE users SET password = '$new_hash' WHERE email = 'test@example.com';</code></li>";
echo "<li><strong>ทดสอบเข้าสู่ระบบด้วย:</strong><br>";
echo "Email: <code>test@example.com</code><br>";
echo "Password: <code>password123</code></li>";
echo "</ol>";
echo "</div>";
echo "</div>";
?>

<script>
    // เพิ่ม function copy
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('คัดลอกแล้ว!');
        });
    }
</script>