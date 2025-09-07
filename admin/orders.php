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

// --- ส่วนของการดึงข้อมูล ---

// 1. รับค่า status จาก URL เพื่อใช้กรองข้อมูล
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

// 2. สร้าง SQL query โดยใช้ View `order_details` เพื่อความสะดวก
// View นี้จะดึงชื่อลูกค้ามาให้เลย ไม่ต้อง Join ตารางซับซ้อน
$sql = "SELECT * FROM order_details";

$params = [];
if (!empty($status_filter)) {
    // เพิ่มเงื่อนไข WHERE ถ้ามีการกรองสถานะ
    $sql .= " WHERE status = :status";
    $params[':status'] = $status_filter;
}

// 3. เรียงลำดับจากออเดอร์ล่าสุดไปเก่าสุด
$sql .= " ORDER BY ordered_at DESC";

// 4. ดึงข้อมูลจากฐานข้อมูล
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // หากเกิดข้อผิดพลาด ให้แสดงข้อความและหยุดการทำงาน
    die("เกิดข้อผิดพลาดในการดึงข้อมูลคำสั่งซื้อ: " . $e->getMessage());
}

// ฟังก์ชันสำหรับสร้าง Badge ของสถานะให้มีสีสัน
function getStatusBadge($status)
{
    $colors = [
        'pending' => '#f39c12',
        'confirmed' => '#3498db',
        'processing' => '#3498db',
        'shipped' => '#27ae60',
        'delivered' => '#2ecc71',
        'cancelled' => '#e74c3c',
        'returned' => '#d35400',
        'refunded' => '#c0392b'
    ];
    $color = $colors[$status] ?? '#7f8c8d';
    $status_th = [
        'pending' => 'รอดำเนินการ',
        'confirmed' => 'ยืนยันแล้ว',
        'processing' => 'กำลังเตรียมจัดส่ง',
        'shipped' => 'จัดส่งแล้ว',
        'delivered' => 'ส่งถึงแล้ว',
        'cancelled' => 'ยกเลิก',
        'returned' => 'ตีกลับ',
        'refunded' => 'คืนเงินแล้ว'
    ];
    $text = $status_th[$status] ?? $status;

    return "<span style='background-color: {$color}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8em;'>" . htmlspecialchars($text) . "</span>";
}

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคำสั่งซื้อ - ระบบหลังบ้าน</title>
    <style>
        /* ใช้ CSS ที่คล้ายกับหน้า index.php เพื่อความสอดคล้อง */
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

        .header-title {
            font-size: 1.2rem;
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
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
        }

        .filter-bar {
            margin-bottom: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .filter-btn {
            text-decoration: none;
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            background: #e9ecef;
            color: #333;
            font-size: 0.9em;
            border: 1px solid #dee2e6;
        }

        .filter-btn.active {
            background: #27ae60;
            color: white;
            border-color: #27ae60;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th,
        .orders-table td {
            padding: 0.8rem 1rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .orders-table th {
            background: #f8f9fa;
            font-weight: 600;
        }

        .action-btn {
            background: #3498db;
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.85em;
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="header-content">
            <span class="header-title">🌾 จัดการคำสั่งซื้อ</span>
            <div>
                <span>👤 <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                <a href="index.php" style="color:white; text-decoration:none; margin-left:1rem;">กลับหน้าแรก</a>
                <button class="logout-btn" onclick="window.location.href='logout.php'">ออกจากระบบ</button>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <h2>รายการคำสั่งซื้อ</h2>

            <div class="filter-bar">
                <a href="orders.php" class="filter-btn <?php echo empty($status_filter) ? 'active' : ''; ?>">ทั้งหมด</a>
                <a href="orders.php?status=pending" class="filter-btn <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">รอดำเนินการ</a>
                <a href="orders.php?status=processing" class="filter-btn <?php echo $status_filter == 'processing' ? 'active' : ''; ?>">กำลังเตรียมส่ง</a>
                <a href="orders.php?status=shipped" class="filter-btn <?php echo $status_filter == 'shipped' ? 'active' : ''; ?>">จัดส่งแล้ว</a>
                <a href="orders.php?status=delivered" class="filter-btn <?php echo $status_filter == 'delivered' ? 'active' : ''; ?>">ส่งถึงแล้ว</a>
                <a href="orders.php?status=cancelled" class="filter-btn <?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>">ยกเลิก</a>
            </div>

            <div style="overflow-x:auto;">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>เลขที่ออเดอร์</th>
                            <th>ลูกค้า</th>
                            <th>วันที่สั่งซื้อ</th>
                            <th>ยอดรวม</th>
                            <th>สถานะการชำระเงิน</th>
                            <th>สถานะออเดอร์</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem;">ไม่พบคำสั่งซื้อ</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                    <td>
                                        <?php
                                        // ดึงชื่อลูกค้าจาก JSON ที่เก็บไว้
                                        $shipping_address = json_decode($order['shipping_address'], true);
                                        echo htmlspecialchars($shipping_address['first_name'] . ' ' . $shipping_address['last_name']);
                                        ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['ordered_at'])); ?></td>
                                    <td><?php echo number_format($order['total_amount'], 2); ?> บาท</td>
                                    <td><?php echo htmlspecialchars($order['payment_status']); ?></td>
                                    <td><?php echo getStatusBadge($order['status']); ?></td>
                                    <td>
                                        <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="action-btn">ดูรายละเอียด</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>