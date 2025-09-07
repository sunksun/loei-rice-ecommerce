<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once 'config/database.php';

// ตั้งค่าตัวแปรพื้นฐาน
$featured_products = [];
$new_products = [];
$categories = [];
$user_data = null;
$is_logged_in = false;
$user_name = '';
$user_initial = 'G';

try {
    $conn = getDB();

    $featured_stmt = $conn->query("
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.featured = 1 AND p.status = 'active'
        ORDER BY p.created_at DESC
        LIMIT 6
    ");
    $featured_products = $featured_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    $featured_products = [];
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้าวพันธุ์พื้นเมืองเลย</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .header {
            background: #f0f0f0;
            padding: 20px;
        }

        .products {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .product {
            border: 1px solid #ccc;
            padding: 15px;
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>ข้าวพันธุ์พื้นเมืองเลย</h1>
        <p>สินค้าคุณภาพจากเลย</p>
    </div>

    <div class="products">
        <?php if (empty($featured_products)): ?>
            <p>ไม่พบสินค้าแนะนำ</p>
        <?php else: ?>
            <?php foreach ($featured_products as $product): ?>
                <div class="product">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p>ราคา: <?php echo number_format($product['price'], 2); ?> บาท</p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>

</html>