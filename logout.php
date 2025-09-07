<?php
session_start();

// เชื่อมต่อฐานข้อมูล
try {
    $pdo = new PDO("mysql:host=localhost;dbname=loei_rice_ecommerce", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("set names utf8");
} catch (PDOException $e) {
    // ไม่จำเป็นต้อง die เพราะการออกจากระบบไม่ต้องใช้ฐานข้อมูล
}

// ล้าง remember token ถ้ามี (ใช้ reset_token แทน remember_token)
if (isset($_SESSION['user_id']) && isset($pdo)) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET reset_token = NULL WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (PDOException $e) {
        // ไม่ทำอะไร
    }
}

// ลบ remember me cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

// ทำลาย session
session_destroy();

// ลบ session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// เปลี่ยนไปหน้า login พร้อมข้อความแจ้ง
header("Location: login.php?logout=success");
exit;
