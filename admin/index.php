<?php
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// รวมไฟล์การตั้งค่าและเชื่อมต่อฐานข้อมูล
require_once '../config/database.php';
$pdo = getDB();

try {
    // --- ดึงสถิติต่างๆ (ส่วนเดิม) ---
    $stats = [];
    $stmt_products = $pdo->query("SELECT COUNT(*) FROM products");
    $stats['products'] = $stmt_products->fetchColumn() ?? 0;
    $stmt_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status != 'cancelled'");
    $stats['orders'] = $stmt_orders->fetchColumn() ?? 0;
    $stmt_customers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
    $stats['customers'] = $stmt_customers->fetchColumn() ?? 0;
    $stmt_sales = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(ordered_at) = CURDATE() AND status NOT IN ('cancelled', 'refunded')");
    $stats['today_sales'] = $stmt_sales->fetchColumn() ?? 0;

    // --- ส่วนที่เพิ่มเติม: ดึงข้อมูลการแจ้งเตือน ---

    // 1. คำสั่งซื้อใหม่ที่รอดำเนินการ (5 รายการล่าสุด)
    $stmt_new_orders = $pdo->query("SELECT * FROM orders WHERE status = 'pending' ORDER BY ordered_at DESC LIMIT 5");
    $new_orders = $stmt_new_orders->fetchAll(PDO::FETCH_ASSOC);

    // 2. การแจ้งชำระเงินที่รอตรวจสอบ (5 รายการล่าสุด)
    $stmt_payments = $pdo->query("SELECT * FROM payment_notifications WHERE status = 'pending' ORDER BY created_at DESC LIMIT 5");
    $pending_payments = $stmt_payments->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูลสถิติ: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าแรก - ระบบจัดการข้าวพันธุ์พื้นเมืองเลย</title>
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
            max-width: 1200px;
            margin: auto;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            padding: 1.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-left: 5px solid;
        }

        .stat-card.products {
            border-left-color: #27ae60;
        }

        .stat-card.orders {
            border-left-color: #3498db;
        }

        .stat-card.customers {
            border-left-color: #e74c3c;
        }

        .stat-card.sales {
            border-left-color: #f39c12;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
        }

        .stat-label {
            color: #666;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1.5rem;
        }

        .menu-item {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            text-decoration: none;
            color: #333;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .menu-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #27ae60;
        }

        .menu-title {
            font-weight: 600;
        }

        /* --- CSS ที่เพิ่มเข้ามาสำหรับส่วนแจ้งเตือน --- */
        .notifications-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .notification-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .notification-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #eee;
            font-weight: 600;
            color: #2d5016;
        }

        .notification-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .notification-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #eee;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }

        .notification-item .time {
            font-size: 0.85em;
            color: #777;
        }

        @media (max-width: 768px) {
            .notifications-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="header-content">
            <h3>🌾 ระบบจัดการข้าวพันธุ์พื้นเมืองเลย</h3>
            <div>
                <span>👤 <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                <a href="logout.php" style="color:white; margin-left:1rem;">ออกจากระบบ</a>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Statistics Cards (ส่วนเดิม) -->
        <div class="stats-grid">
            <div class="stat-card products">
                <div class="stat-number"><?php echo number_format($stats['products']); ?></div>
                <div class="stat-label">สินค้าทั้งหมด</div>
            </div>
            <div class="stat-card orders">
                <div class="stat-number"><?php echo number_format($stats['orders']); ?></div>
                <div class="stat-label">คำสั่งซื้อ</div>
            </div>
            <div class="stat-card customers">
                <div class="stat-number"><?php echo number_format($stats['customers']); ?></div>
                <div class="stat-label">ลูกค้าทั้งหมด</div>
            </div>
            <div class="stat-card sales">
                <div class="stat-number">฿<?php echo number_format($stats['today_sales'], 2); ?></div>
                <div class="stat-label">ยอดขายวันนี้</div>
            </div>
        </div>

        <!-- Main Menu (ส่วนเดิม) -->
        <div class="menu-grid">
            <a href="orders.php" class="menu-item">
                <div class="menu-icon">📋</div>
                <div class="menu-title">จัดการคำสั่งซื้อ</div>
            </a>
            <a href="products.php" class="menu-item">
                <div class="menu-icon">📦</div>
                <div class="menu-title">จัดการสินค้า</div>
            </a>
            <a href="customers.php" class="menu-item">
                <div class="menu-icon">👥</div>
                <div class="menu-title">จัดการลูกค้า</div>
            </a>
            <a href="settings.php" class="menu-item">
                <div class="menu-icon">⚙️</div>
                <div class="menu-title">ตั้งค่าระบบ</div>
            </a>
        </div>

        <!-- --- ส่วนที่เพิ่มเติม: การแจ้งเตือนและกิจกรรมล่าสุด --- -->
        <div class="notifications-layout">
            <div class="notification-card">
                <div class="notification-header">คำสั่งซื้อใหม่ (รอดำเนินการ)</div>
                <ul class="notification-list">
                    <?php if (empty($new_orders)): ?>
                        <li class="notification-item">ไม่มีคำสั่งซื้อใหม่</li>
                    <?php else: ?>
                        <?php foreach ($new_orders as $order): ?>
                            <li class="notification-item">
                                <span><a href="order_detail.php?id=<?php echo $order['id']; ?>"><?php echo htmlspecialchars($order['order_number']); ?></a></span>
                                <span class="time"><?php echo date('d/m/Y H:i', strtotime($order['ordered_at'])); ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="notification-card">
                <div class="notification-header">รายการแจ้งชำระเงิน (รอตรวจสอบ)</div>
                <ul class="notification-list">
                    <?php if (empty($pending_payments)): ?>
                        <li class="notification-item">ไม่มีรายการแจ้งชำระเงินใหม่</li>
                    <?php else: ?>
                        <?php foreach ($pending_payments as $payment): ?>
                            <li class="notification-item">
                                <span><a href="payment_detail.php?id=<?php echo $payment['id']; ?>"><?php echo htmlspecialchars($payment['order_number']); ?></a></span>
                                <span class="time"><?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</body>

</html>