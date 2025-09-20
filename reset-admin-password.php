<?php
require_once 'config/database.php';

$new_password = 'admin123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE username = 'admin'");
    $stmt->execute([$hashed_password]);
    
    echo "✅ Password updated successfully!<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
    echo "<a href='admin/login.php'>Login Admin</a>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>