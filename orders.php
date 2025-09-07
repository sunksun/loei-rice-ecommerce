<?php
// orders.php - แสดงรายการสั่งซื้อของผู้ใช้
session_start();
require_once 'config/config.php';
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

try {
    $conn = getDB();
    $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY ordered_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $orders = [];
}

function h($str)
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คำสั่งซื้อของฉัน | ข้าวพื้นเมืองเลย</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <style>
        body {
            background: #f8f9fa;
        }

        .orders-container {
            max-width: 900px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.07);
            padding: 2rem;
        }

        .order-table th,
        .order-table td {
            vertical-align: middle;
        }

        .order-status {
            font-weight: 600;
        }

        .order-status.waiting {
            color: #f39c12;
        }

        .order-status.paid {
            color: #27ae60;
        }

        .order-status.cancel {
            color: #e74c3c;
        }

        .order-status.shipping {
            color: #3498db;
        }

        .order-status.success {
            color: #2d5016;
        }
    </style>
</head>

<body>
    <div class="orders-container">
        <h3 class="mb-4">รายการสั่งซื้อของฉัน</h3>
        <?php if (empty($orders)): ?>
            <div class="alert alert-info">ยังไม่มีรายการสั่งซื้อ</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table order-table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>เลขที่คำสั่งซื้อ</th>
                            <th>วันที่สั่งซื้อ</th>
                            <th>ยอดรวม (บาท)</th>
                            <th>สถานะ</th>
                            <th>ดูรายละเอียด</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo h($order['order_number']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['ordered_at'])); ?></td>
                                <td><?php echo number_format($order['total_amount'], 2); ?></td>
                                <td class="order-status <?php echo h($order['status']); ?>">
                                    <?php
                                    $status_map = [
                                        'waiting' => 'รอชำระเงิน',
                                        'paid' => 'ชำระเงินแล้ว',
                                        'shipping' => 'กำลังจัดส่ง',
                                        'success' => 'สำเร็จ',
                                        'cancel' => 'ยกเลิก'
                                    ];
                                    echo $status_map[$order['status']] ?? h($order['status']);
                                    ?>
                                </td>
                                <td><a href="order-confirmation.php?order_number=<?php echo h($order['order_number']); ?>" class="btn btn-sm btn-outline-primary">ดูรายละเอียด</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <a href="index.php" class="btn btn-secondary mt-4">ย้อนกลับหน้าแรก</a>
    </div>
</body>

</html>