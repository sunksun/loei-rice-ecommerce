<?php
require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html lang='th'>";
echo "<head>";
echo "    <meta charset='UTF-8'>";
echo "    <title>สร้างตาราง activity_logs</title>";
echo "    <style>";
echo "        body { font-family: Arial, sans-serif; margin: 20px; }";
echo "        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; }";
echo "        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; }";
echo "    </style>";
echo "</head>";
echo "<body>";

echo "<h1>🗄️ สร้างตาราง activity_logs</h1>";

try {
    $pdo = getDB();
    
    // สร้างตาราง activity_logs
    $sql = "CREATE TABLE IF NOT EXISTS `activity_logs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) DEFAULT NULL,
        `admin_id` int(11) DEFAULT NULL,
        `action` varchar(100) NOT NULL,
        `table_name` varchar(50) DEFAULT NULL,
        `record_id` int(11) DEFAULT NULL,
        `ip_address` varchar(45) DEFAULT NULL,
        `user_agent` text DEFAULT NULL,
        `details` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `admin_id` (`admin_id`),
        KEY `action` (`action`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($sql);
    
    echo "<div class='success'>";
    echo "<h3>✅ สำเร็จ!</h3>";
    echo "<p>ตาราง <strong>activity_logs</strong> ถูกสร้างเรียบร้อยแล้ว</p>";
    echo "</div>";
    
    // ตรวจสอบโครงสร้างตาราง
    $stmt = $pdo->query("DESCRIBE activity_logs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>📋 โครงสร้างตาราง activity_logs:</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>📝 การใช้งาน:</h3>";
    echo "<ul>";
    echo "<li><strong>user_id</strong> - ID ของลูกค้า (สำหรับ activity ของลูกค้า)</li>";
    echo "<li><strong>admin_id</strong> - ID ของ admin (สำหรับ activity ของ admin)</li>";
    echo "<li><strong>action</strong> - การกระทำ เช่น 'login', 'create_product', 'delete_order'</li>";
    echo "<li><strong>table_name</strong> - ชื่อตารางที่เกี่ยวข้อง</li>";
    echo "<li><strong>record_id</strong> - ID ของ record ที่เกี่ยวข้อง</li>";
    echo "<li><strong>ip_address</strong> - IP address ของผู้ใช้</li>";
    echo "<li><strong>user_agent</strong> - Browser และ OS ของผู้ใช้</li>";
    echo "<li><strong>details</strong> - รายละเอียดเพิ่มเติม (JSON format)</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ เกิดข้อผิดพลาด!</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<p><a href='admin/login.php'>🔐 ไปหน้าล็อกอิน Admin</a></p>";
echo "</body>";
echo "</html>";
?>