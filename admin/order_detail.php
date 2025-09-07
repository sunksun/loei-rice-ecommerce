<?php
session_start();

// ตรวจสอบการล็อกอินของผู้ดูแลระบบ
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// รวมไฟล์การตั้งค่าและเชื่อมต่อฐานข้อมูล
require_once '../config/database.php';
$pdo = getDB();

// --- ส่วนของการจัดการข้อมูล ---

// 1. รับ ID ของออเดอร์จาก URL และตรวจสอบความถูกต้อง
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id === 0) {
    header('Location: orders.php');
    exit();
}

// 2. จัดการการอัปเดตสถานะ (เมื่อมีการส่งฟอร์ม)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);

        // เก็บข้อความแจ้งเตือนใน Session เพื่อแสดงผลหลัง redirect
        $_SESSION['success_message'] = "อัปเดตสถานะออเดอร์สำเร็จ!";
        header("Location: order_detail.php?id=" . $order_id);
        exit();
    } catch (Exception $e) {
        $error_message = "เกิดข้อผิดพลาดในการอัปเดตสถานะ: " . $e->getMessage();
    }
}

// 3. ดึงข้อมูลออเดอร์และรายการสินค้า
try {
    // ดึงข้อมูลออเดอร์หลัก
    $stmt_order = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt_order->execute([$order_id]);
    $order = $stmt_order->fetch(PDO::FETCH_ASSOC);

    // หากไม่พบออเดอร์ ให้กลับไปหน้ารายการ
    if (!$order) {
        header('Location: orders.php');
        exit();
    }

    // ดึงรายการสินค้าในออเดอร์
    $stmt_items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt_items->execute([$order_id]);
    $order_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // แปลงข้อมูลที่อยู่จาก JSON เพื่อนำมาแสดงผล
    $shipping_address = json_decode($order['shipping_address'], true);
} catch (Exception $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage());
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
        'processing' => 'กำลังเตรียมส่ง',
        'shipped' => 'จัดส่งแล้ว',
        'delivered' => 'ส่งถึงแล้ว',
        'cancelled' => 'ยกเลิก',
        'returned' => 'ตีกลับ',
        'refunded' => 'คืนเงินแล้ว'
    ];
    $text = $status_th[$status] ?? $status;

    return "<span style='background-color: {$color}; color: white; padding: 5px 10px; border-radius: 5px; font-size: 0.9em;'>" . htmlspecialchars($text) . "</span>";
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดคำสั่งซื้อ #<?php echo htmlspecialchars($order['order_number']); ?></title>
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

        .order-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            align-items: flex-start;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
        }

        .card-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #eee;
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.2rem;
            color: #2d5016;
        }

        .card-body {
            padding: 1.5rem;
        }

        .detail-list p {
            margin: 0 0 0.8rem 0;
        }

        .detail-list strong {
            color: #555;
            display: inline-block;
            min-width: 120px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table th,
        .items-table td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .items-table th {
            background-color: #f8f9fa;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .form-group select,
        .form-group button {
            font-size: 1rem;
            padding: 0.6rem;
            width: 100%;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .form-group button {
            background: #3498db;
            color: white;
            cursor: pointer;
            border: none;
            transition: background 0.3s;
        }

        .form-group button:hover {
            background: #2980b9;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            border: 1px solid transparent;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .back-link {
            color: white;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 5px;
            font-size: 0.9rem;
        }

        @media (max-width: 992px) {
            .order-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="header-content">
            <span>รายละเอียดคำสั่งซื้อ #<?php echo htmlspecialchars($order['order_number']); ?></span>
            <a href="orders.php" class="back-link">กลับไปหน้ารายการ</a>
        </div>
    </header>

    <div class="container">
        <?php
        // แสดงข้อความแจ้งเตือน (ถ้ามี)
        if (isset($_SESSION['success_message'])) {
            echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
            unset($_SESSION['success_message']); // ลบข้อความออกหลังจากแสดงผล
        }
        if (isset($error_message)) {
            echo '<div class="alert" style="background-color: #f8d7da;">' . htmlspecialchars($error_message) . '</div>';
        }
        ?>

        <div class="order-layout">
            <!-- Main Content -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <h2>รายการสินค้าในออเดอร์</h2>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>สินค้า</th>
                                    <th>ราคาต่อหน่วย</th>
                                    <th>จำนวน</th>
                                    <th style="text-align:right;">ราคารวม</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td><?php echo number_format($item['product_price'], 2); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td style="text-align:right;"><?php echo number_format($item['total_price'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>ข้อมูลลูกค้าและการจัดส่ง</h2>
                    </div>
                    <div class="card-body detail-list">
                        <p><strong>ชื่อ-นามสกุล:</strong> <?php echo htmlspecialchars($shipping_address['first_name'] . ' ' . $shipping_address['last_name']); ?></p>
                        <p><strong>อีเมล:</strong> <?php echo htmlspecialchars($shipping_address['email']); ?></p>
                        <p><strong>เบอร์โทรศัพท์:</strong> <?php echo htmlspecialchars($shipping_address['phone']); ?></p>
                        <p><strong>ที่อยู่:</strong><br><?php echo nl2br(htmlspecialchars($shipping_address['address_line1'])); ?>, <?php echo htmlspecialchars($shipping_address['city']); ?> <?php echo htmlspecialchars($shipping_address['postal_code']); ?></p>
                        <?php if (!empty($order['customer_notes'])): ?>
                            <p><strong>หมายเหตุจากลูกค้า:</strong> <?php echo nl2br(htmlspecialchars($order['customer_notes'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <h2>ข้อมูลคำสั่งซื้อ</h2>
                    </div>
                    <div class="card-body detail-list">
                        <p><strong>สถานะ:</strong> <?php echo getStatusBadge($order['status']); ?></p>
                        <p><strong>ยอดรวม:</strong> <?php echo number_format($order['total_amount'], 2); ?> บาท</p>
                        <p><strong>วิธีการชำระเงิน:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                        <p><strong>สถานะการชำระเงิน:</strong> <?php echo htmlspecialchars($order['payment_status']); ?></p>
                        <p><strong>วันที่สั่งซื้อ:</strong> <?php echo date('d/m/Y H:i', strtotime($order['ordered_at'])); ?></p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>จัดการออเดอร์</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="order_detail.php?id=<?php echo $order_id; ?>">
                            <div class="form-group">
                                <label for="status">อัปเดตสถานะคำสั่งซื้อ</label>
                                <select name="status" id="status">
                                    <option value="pending" <?php echo ($order['status'] == 'pending') ? 'selected' : ''; ?>>รอดำเนินการ</option>
                                    <option value="processing" <?php echo ($order['status'] == 'processing') ? 'selected' : ''; ?>>กำลังเตรียมส่ง</option>
                                    <option value="shipped" <?php echo ($order['status'] == 'shipped') ? 'selected' : ''; ?>>จัดส่งแล้ว</option>
                                    <option value="delivered" <?php echo ($order['status'] == 'delivered') ? 'selected' : ''; ?>>ส่งถึงแล้ว</option>
                                    <option value="cancelled" <?php echo ($order['status'] == 'cancelled') ? 'selected' : ''; ?>>ยกเลิก</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <button type="submit" name="update_status">บันทึกการเปลี่ยนแปลง</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>