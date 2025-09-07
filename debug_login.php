<?php
// แทนที่ไฟล์ debug_login.php เดิม
session_start();

echo "<!DOCTYPE html>
<html lang='th'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Debug Login System - Fixed</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 10px; margin: 10px 0; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 10px 0; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 10px 0; }
        .info { background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; margin: 10px 0; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 10px 0; }
        code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; font-family: monospace; }
        button { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin: 5px; }
        button:hover { background: #0056b3; }
        .btn-success { background: #28a745; } .btn-success:hover { background: #1e7e34; }
        .btn-danger { background: #dc3545; } .btn-danger:hover { background: #c82333; }
        input, select { padding: 10px; margin: 5px; border: 1px solid #ccc; border-radius: 4px; min-width: 200px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .form-row { margin: 10px 0; display: flex; align-items: center; gap: 10px; }
        .form-row label { min-width: 100px; font-weight: bold; }
    </style>
</head>
<body>";

echo "<h1>🔧 Debug Login System - Fixed Version</h1>";

// เชื่อมต่อฐานข้อมูล
$pdo = null;
try {
    if (file_exists('config/database.php')) {
        require_once 'config/database.php';
        if (function_exists('getDB')) {
            $pdo = getDB();
            echo "<div class='success'>✅ เชื่อมต่อฐานข้อมูลสำเร็จ</div>";
        }
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
}

// Debug POST data
if (!empty($_POST)) {
    echo "<div class='info'>";
    echo "<h3>📨 POST Data ที่ได้รับ:</h3>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    echo "</div>";
}

// ทดสอบรหัสผ่าน - แก้ไขปัญหา form
if (isset($_POST['test_login'])) {
    $test_email = trim($_POST['test_email'] ?? '');
    $test_password = $_POST['test_password'] ?? '';

    echo "<div class='warning'>";
    echo "<h3>🧪 ผลการทดสอบ Login:</h3>";
    echo "<strong>Email:</strong> '" . htmlspecialchars($test_email) . "'<br>";
    echo "<strong>Password:</strong> '" . htmlspecialchars($test_password) . "'<br>";
    echo "<strong>Password Length:</strong> " . strlen($test_password) . "<br>";
    echo "</div>";

    if (empty($test_email)) {
        echo "<div class='error'>❌ Email ว่างเปล่า</div>";
    } elseif (empty($test_password)) {
        echo "<div class='error'>❌ Password ว่างเปล่า</div>";
    } elseif ($pdo) {
        try {
            // ค้นหา user
            $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, password, status FROM users WHERE email = ?");
            $stmt->execute([$test_email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                echo "<div class='success'>✅ พบ user: {$user['first_name']} {$user['last_name']}</div>";
                echo "<div class='info'>📋 Status: {$user['status']}</div>";
                echo "<div class='info'>🔑 Password Hash: " . substr($user['password'], 0, 30) . "...</div>";

                // ทดสอบรหัสผ่าน
                $verify_result = password_verify($test_password, $user['password']);

                if ($verify_result) {
                    echo "<div class='success'>🎉 <strong>LOGIN สำเร็จ!</strong> รหัสผ่านถูกต้อง</div>";

                    // จำลองการสร้าง session
                    $_SESSION['debug_user_id'] = $user['id'];
                    $_SESSION['debug_user_email'] = $user['email'];
                    $_SESSION['debug_user_name'] = trim($user['first_name'] . ' ' . $user['last_name']);

                    echo "<div class='success'>✅ สร้าง session สำเร็จ (debug)</div>";
                    echo "<div class='info'>Session: " . json_encode([
                        'user_id' => $_SESSION['debug_user_id'],
                        'email' => $_SESSION['debug_user_email'],
                        'name' => $_SESSION['debug_user_name']
                    ]) . "</div>";
                } else {
                    echo "<div class='error'>❌ <strong>LOGIN ล้มเหลว!</strong> รหัสผ่านไม่ถูกต้อง</div>";

                    // แสดงการวิเคราะห์
                    echo "<div class='warning'>";
                    echo "<h4>🔍 การวิเคราะห์รหัสผ่าน:</h4>";
                    echo "Input: '" . $test_password . "' (length: " . strlen($test_password) . ")<br>";
                    echo "Hash: " . $user['password'] . "<br>";
                    echo "Verify result: " . ($verify_result ? 'true' : 'false') . "<br>";

                    // ทดสอบสร้าง hash ใหม่
                    $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
                    echo "New hash test: " . substr($new_hash, 0, 30) . "...<br>";
                    echo "New verify test: " . (password_verify($test_password, $new_hash) ? 'true' : 'false') . "<br>";
                    echo "</div>";
                }
            } else {
                echo "<div class='error'>❌ ไม่พบ user ที่มี email: $test_email</div>";

                // แสดงรายการ email ที่มี
                $all_emails = $pdo->query("SELECT email FROM users")->fetchAll(PDO::FETCH_COLUMN);
                echo "<div class='info'>📧 Email ที่มีในระบบ: " . implode(', ', $all_emails) . "</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
        }
    }
}

// แสดงข้อมูล users ปัจจุบัน
if ($pdo) {
    echo "<div class='container'>";
    echo "<h3>👥 ข้อมูล Users ในระบบ</h3>";

    try {
        $users = $pdo->query("SELECT id, first_name, last_name, email, status, created_at FROM users ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

        if (count($users) > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>ชื่อ-นามสกุล</th><th>Email</th><th>Status</th><th>Created</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>{$user['id']}</td>";
                echo "<td>{$user['first_name']} {$user['last_name']}</td>";
                echo "<td>{$user['email']}</td>";
                echo "<td>{$user['status']}</td>";
                echo "<td>{$user['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='warning'>⚠️ ไม่มีข้อมูล users</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    }
    echo "</div>";
}

// ฟอร์มทดสอบ Login - แก้ไขแล้ว
echo "<div class='container'>";
echo "<h3>🧪 ทดสอบ Login แบบ Step-by-Step</h3>";
echo "<form method='POST'>";

echo "<div class='form-row'>";
echo "<label>Email:</label>";
echo "<input type='email' name='test_email' value='" . ($_POST['test_email'] ?? 'test@example.com') . "' required>";
echo "</div>";

echo "<div class='form-row'>";
echo "<label>Password:</label>";
echo "<input type='text' name='test_password' value='" . ($_POST['test_password'] ?? 'password123') . "' required>";
echo "<small style='color: #666; margin-left: 10px;'>(ใช้ type='text' เพื่อดูค่าที่ส่ง)</small>";
echo "</div>";

echo "<div class='form-row'>";
echo "<button type='submit' name='test_login' class='btn-success'>🧪 ทดสอบ Login</button>";
echo "</div>";

echo "</form>";
echo "</div>";

// เครื่องมือแก้ไขปัญหา
if (isset($_POST['fix_action'])) {
    $action = $_POST['fix_action'];

    if ($action === 'reset_users' && $pdo) {
        echo "<div class='warning'>🔄 กำลังรีเซ็ตข้อมูล users...</div>";

        try {
            // ลบข้อมูลเก่า
            $pdo->exec("DELETE FROM users");

            // เพิ่มข้อมูลใหม่
            $users_data = [
                ['ผู้ใช้', 'ทดสอบ', 'test@example.com', 'password123'],
                ['สมชาย', 'ทดสอบ', 'somchai@example.com', 'test123'],
                ['สมหญิง', 'ทดสอบ', 'somying@example.com', '123456'],
                ['จารุวัลย์', 'รักษ์มณี', 'jaruwan.lak@gmail.com', 'password123'],
                ['Admin', 'System', 'admin@test.com', 'admin123']
            ];

            foreach ($users_data as $user_data) {
                $hash = password_hash($user_data[3], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (first_name, last_name, email, password, status, email_verified, created_at) 
                    VALUES (?, ?, ?, ?, 'active', 1, NOW())
                ");
                $stmt->execute([$user_data[0], $user_data[1], $user_data[2], $hash]);
            }

            echo "<div class='success'>✅ รีเซ็ตข้อมูล users สำเร็จ!</div>";
            echo "<script>setTimeout(() => { window.location.reload(); }, 1000);</script>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
        }
    } elseif ($action === 'fix_password' && $pdo) {
        $email = $_POST['fix_email'];
        $new_password = $_POST['fix_password'];

        try {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $result = $stmt->execute([$new_hash, $email]);

            if ($result) {
                echo "<div class='success'>✅ อัพเดทรหัสผ่านสำเร็จ สำหรับ $email</div>";
                echo "<div class='info'>รหัสผ่านใหม่: $new_password</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
        }
    }
}

// เครื่องมือแก้ไข
echo "<div class='container'>";
echo "<h3>🛠️ เครื่องมือแก้ไขปัญหา</h3>";

echo "<form method='POST' style='margin: 10px 0;'>";
echo "<input type='hidden' name='fix_action' value='reset_users'>";
echo "<button type='submit' class='btn-danger' onclick='return confirm(\"รีเซ็ตข้อมูล users ทั้งหมด?\")'>🔄 รีเซ็ตข้อมูล Users</button>";
echo "</form>";

echo "<form method='POST' style='margin: 10px 0;'>";
echo "<input type='hidden' name='fix_action' value='fix_password'>";
echo "<div class='form-row'>";
echo "<label>Email:</label>";
echo "<input type='email' name='fix_email' value='test@example.com' required>";
echo "</div>";
echo "<div class='form-row'>";
echo "<label>รหัสผ่านใหม่:</label>";
echo "<input type='text' name='fix_password' value='password123' required>";
echo "</div>";
echo "<button type='submit' class='btn-success'>🔑 แก้ไขรหัสผ่าน</button>";
echo "</form>";

echo "</div>";

// ข้อมูลสรุป
echo "<div class='container'>";
echo "<h3>📋 ข้อมูลสำหรับทดสอบ</h3>";
echo "<div class='info'>";
echo "<h4>🔗 ลิงค์ทดสอบ:</h4>";
echo "<p><a href='login.php' target='_blank' style='color: #007bff;'>📝 ทดสอบ Login จริง</a></p>";
echo "<p><a href='register.php' target='_blank' style='color: #28a745;'>👤 ทดสอบ Register</a></p>";

echo "<h4>🧪 ข้อมูล Login ทดสอบ:</h4>";
echo "<table>";
echo "<tr><th>Email</th><th>Password</th><th>ชื่อ</th></tr>";
echo "<tr><td>test@example.com</td><td>password123</td><td>ผู้ใช้ ทดสอบ</td></tr>";
echo "<tr><td>somchai@example.com</td><td>test123</td><td>สมชาย ทดสอบ</td></tr>";
echo "<tr><td>somying@example.com</td><td>123456</td><td>สมหญิง ทดสอบ</td></tr>";
echo "<tr><td>jaruwan.lak@gmail.com</td><td>password123</td><td>จารุวัลย์ รักษ์มณี</td></tr>";
echo "</table>";
echo "</div>";
echo "</div>";

echo "</body></html>";
