<?php
// Export ฐานข้อมูลแบบง่าย
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ตั้งค่า headers สำหรับดาวน์โหลดไฟล์
if (isset($_GET['download']) && $_GET['download'] === 'sql') {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="loei_rice_ecommerce_' . date('Y-m-d_H-i-s') . '.sql"');
    header('Content-Transfer-Encoding: binary');
    
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=loei_rice_ecommerce;charset=utf8mb4', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        echo generateSimpleExport($pdo);
        exit;
        
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}

// แสดงหน้าเว็บ
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Database Export</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Kanit', sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        h1 { color: #27ae60; text-align: center; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .btn { display: inline-block; padding: 15px 30px; background: #27ae60; color: white; text-decoration: none; border-radius: 8px; margin: 10px; font-weight: 500; }
        .btn:hover { background: #219a52; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #27ae60; color: white; }
        .center { text-align: center; }
    </style>
</head>
<body>
<div class="container">
    <h1>📁 Simple Database Export</h1>
    
    <?php
    // ตรวจสอบฐานข้อมูล
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=loei_rice_ecommerce;charset=utf8mb4', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "<div class='success'>✅ เชื่อมต่อฐานข้อมูลสำเร็จ - พบ " . count($tables) . " ตาราง</div>";
        
        if (!empty($tables)) {
            echo "<table>";
            echo "<tr><th>ตาราง</th><th>จำนวนข้อมูล</th></tr>";
            $total_records = 0;
            
            foreach ($tables as $table) {
                $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                $total_records += $count;
                echo "<tr><td>$table</td><td>" . number_format($count) . " แถว</td></tr>";
            }
            
            echo "</table>";
            echo "<div class='info'>📊 รวมข้อมูลทั้งหมด: " . number_format($total_records) . " แถว</div>";
            
            echo "<div class='center'>";
            echo "<a href='?download=sql' class='btn'>📥 ดาวน์โหลดไฟล์ SQL</a>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ ไม่สามารถเชื่อมต่อฐานข้อมูล: " . $e->getMessage() . "</div>";
        echo "<div class='info'>💡 กรุณาตรวจสอบ:<br>";
        echo "1. XAMPP MySQL service ทำงานอยู่หรือไม่<br>";
        echo "2. ฐานข้อมูล loei_rice_ecommerce มีอยู่หรือไม่<br>";
        echo "3. Import ไฟล์ loei_rice_ecommerce.sql แล้วหรือยัง</div>";
    }
    ?>
    
    <div class="center">
        <a href="index.php" class="btn btn-secondary">🏠 หน้าแรก</a>
        <a href="check-mysql-database.php" class="btn btn-secondary">🔍 ตรวจสอบ Database</a>
        <a href="http://localhost/phpmyadmin" target="_blank" class="btn btn-secondary">💾 phpMyAdmin</a>
    </div>
</div>
</body>
</html>

<?php
function generateSimpleExport($pdo) {
    $output = "";
    $output .= "-- Export Database: loei_rice_ecommerce\n";
    $output .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
    $output .= "-- Generated by: Simple Export Script\n\n";
    
    $output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $output .= "START TRANSACTION;\n";
    $output .= "SET time_zone = \"+00:00\";\n\n";
    
    $output .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
    $output .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
    $output .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
    $output .= "/*!40101 SET NAMES utf8mb4 */;\n\n";
    
    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        $output .= "-- --------------------------------------------------------\n\n";
        $output .= "-- Table structure for table `$table`\n\n";
        
        // Get CREATE TABLE statement
        $result = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
        $output .= $result[1] . ";\n\n";
        
        // Get table data
        $data = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($data)) {
            $output .= "-- Dumping data for table `$table`\n\n";
            
            $columns = array_keys($data[0]);
            $output .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES\n";
            
            $rows = [];
            foreach ($data as $row) {
                $values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = "'" . addslashes($value) . "'";
                    }
                }
                $rows[] = '(' . implode(', ', $values) . ')';
            }
            
            $output .= implode(",\n", $rows) . ";\n\n";
        }
    }
    
    $output .= "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
    $output .= "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
    $output .= "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";
    $output .= "COMMIT;\n";
    
    return $output;
}
?>