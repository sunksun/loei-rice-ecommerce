<?php
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../stock_manager.php';

$stock_manager = new StockManager();

// ดึงข้อมูล low stock products
$low_stock_products = $stock_manager->getLowStockProducts();

// ดึงข้อมูล stock movements (ล่าสุด 50 รายการ)
$stock_movements = $stock_manager->getStockMovements(null, 50);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานสต็อกสินค้า - ระบบหลังบ้าน</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f7fa;
            color: #333;
            margin: 0;
        }

        .header {
            background: linear-gradient(135deg, #27ae60, #2d5016);
            color: white;
            padding: 1rem;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: auto;
        }

        .container {
            max-width: 1400px;
            margin: auto;
            padding: 1.5rem;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .card h2 {
            color: #2d5016;
            border-bottom: 2px solid #27ae60;
            padding-bottom: 0.5rem;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-warning {
            color: #856404;
            background-color: #fff3cd;
            border-color: #ffeaa7;
        }

        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .table th, .table td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
        }

        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            margin-left: 1rem;
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="header-content">
            <span>🌾 รายงานสต็อกสินค้า</span>
            <div>
                <span>👤 <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                <a href="index.php" class="nav-link">กลับหน้าแรก</a>
                <button class="logout-btn" onclick="window.location.href='logout.php'">ออกจากระบบ</button>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Low Stock Alert -->
        <div class="card">
            <h2>🚨 แจ้งเตือนสต็อกต่ำ</h2>
            
            <?php if (empty($low_stock_products)): ?>
                <div class="alert alert-info">
                    <strong>ดีเยี่ยม!</strong> ไม่มีสินค้าที่มีสต็อกต่ำในขณะนี้
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <strong>คำเตือน!</strong> มีสินค้า <?php echo count($low_stock_products); ?> รายการที่มีสต็อกต่ำ
                </div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ชื่อสินค้า</th>
                            <th>สต็อกปัจจุบัน</th>
                            <th>จุดแจ้งเตือน</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($low_stock_products as $product): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo $product['stock_quantity']; ?></td>
                                <td><?php echo $product['min_stock_alert'] ?? 5; ?></td>
                                <td>
                                    <?php if ($product['stock_quantity'] <= 0): ?>
                                        <span class="badge badge-danger">หมดสต็อก</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">สต็อกต่ำ</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Stock Movements -->
        <div class="card">
            <h2>📊 การเคลื่อนไหวสต็อกล่าสุด</h2>
            
            <?php if (empty($stock_movements)): ?>
                <div class="alert alert-info">
                    ยังไม่มีการเคลื่อนไหวสต็อกในระบบ
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>วันที่/เวลา</th>
                            <th>สินค้า</th>
                            <th>การเปลี่ยนแปลง</th>
                            <th>ประเภท</th>
                            <th>อ้างอิง</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stock_movements as $movement): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($movement['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($movement['product_name'] ?? 'ไม่ระบุ'); ?></td>
                                <td>
                                    <?php 
                                    $change = $movement['quantity_change'];
                                    $color = $change > 0 ? 'success' : 'danger';
                                    $symbol = $change > 0 ? '+' : '';
                                    ?>
                                    <span class="badge badge-<?php echo $color; ?>">
                                        <?php echo $symbol . $change; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($movement['movement_type']); ?></td>
                                <td><?php echo htmlspecialchars($movement['reference'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>