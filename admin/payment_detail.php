<?php
session_start();

// ตรวจสอบการล็อกอินของผู้ดูแลระบบ
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// รวมไฟล์และเชื่อมต่อฐานข้อมูล
require_once '../config/database.php';
$pdo = getDB();

// --- ส่วนของการจัดการข้อมูล ---

// 1. รับ ID ของการแจ้งโอนจาก URL
$notification_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($notification_id === 0) {
    header('Location: index.php'); // ถ้าไม่มี ID ให้กลับไปหน้าแรกของ admin
    exit();
}

// 2. จัดการการอัปเดตสถานะ (เมื่อมีการส่งฟอร์ม)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment_status'])) {
    $new_status = $_POST['status'];
    $order_id = $_POST['order_id'];
    $admin_notes = trim($_POST['admin_notes']);

    try {
        // เริ่ม Transaction เพื่อความปลอดภัย
        $pdo->beginTransaction();

        // อัปเดตตาราง payment_notifications
        $stmt_notification = $pdo->prepare(
            "UPDATE payment_notifications 
             SET status = ?, admin_notes = ?, verified_by = ?, verified_at = NOW() 
             WHERE id = ?"
        );
        $stmt_notification->execute([$new_status, $admin_notes, $_SESSION['admin_id'], $notification_id]);

        // ถ้า "อนุมัติ" ให้ไปอัปเดตตาราง orders ด้วย
        if ($new_status === 'verified') {
            $stmt_order = $pdo->prepare(
                "UPDATE orders 
                 SET payment_status = 'paid', status = 'processing', confirmed_at = NOW() 
                 WHERE id = ?"
            );
            $stmt_order->execute([$order_id]);
        }

        // ยืนยันการทำรายการทั้งหมด
        $pdo->commit();

        $_SESSION['success_message'] = "อัปเดตสถานะการชำระเงินเรียบร้อยแล้ว!";
        header("Location: payment_detail.php?id=" . $notification_id);
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "เกิดข้อผิดพลาดในการอัปเดต: " . $e->getMessage();
    }
}

// 3. ดึงข้อมูลการแจ้งโอนและข้อมูลออเดอร์ที่เกี่ยวข้อง
try {
    // ดึงข้อมูลการแจ้งโอน
    $stmt_payment = $pdo->prepare("SELECT * FROM payment_notifications WHERE id = ?");
    $stmt_payment->execute([$notification_id]);
    $payment = $stmt_payment->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        die("ไม่พบข้อมูลการแจ้งชำระเงินนี้");
    }

    // ดึงข้อมูลออเดอร์ที่เกี่ยวข้องเพื่อเปรียบเทียบยอด
    $stmt_order = $pdo->prepare("SELECT total_amount FROM orders WHERE id = ?");
    $stmt_order->execute([$payment['order_id']]);
    $order = $stmt_order->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจสอบการชำระเงิน - #<?php echo htmlspecialchars($payment['order_number']); ?></title>
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
            max-width: 900px;
            margin: auto;
        }

        .container {
            max-width: 900px;
            margin: auto;
            padding: 1.5rem;
        }

        .payment-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
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
        }

        .card-body {
            padding: 1.5rem;
        }

        .detail-list p {
            margin: 0 0 1rem 0;
            font-size: 1.1rem;
        }

        .detail-list strong {
            color: #555;
            display: inline-block;
            min-width: 150px;
        }

        .slip-image {
            max-width: 100%;
            border-radius: 6px;
            border: 1px solid #ddd;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .slip-image:hover {
            transform: scale(1.02);
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
        .form-group textarea,
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
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
        }

        .comparison {
            border-top: 1px solid #eee;
            margin-top: 1rem;
            padding-top: 1rem;
        }

        .comparison .amount-due {
            font-size: 1.2rem;
            font-weight: bold;
            color: #e74c3c;
        }

        .comparison .amount-paid {
            font-size: 1.2rem;
            font-weight: bold;
            color: #27ae60;
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="header-content">
            <span>💰 ตรวจสอบการชำระเงิน</span>
            <a href="index.php" style="color:white; text-decoration:none;">กลับหน้าแรก</a>
        </div>
    </header>

    <div class="container">
        <?php
        if (isset($_SESSION['success_message'])) {
            echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
            unset($_SESSION['success_message']);
        }
        ?>
        <div class="payment-layout">
            <!-- Slip and Details -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <h2>หลักฐานการชำระเงิน</h2>
                    </div>
                    <div class="card-body">
                        <a href="../uploads/slips/<?php echo htmlspecialchars($payment['slip_image']); ?>" target="_blank">
                            <img src="../uploads/slips/<?php echo htmlspecialchars($payment['slip_image']); ?>" alt="สลิปการโอนเงิน" class="slip-image">
                        </a>
                    </div>
                </div>
            </div>

            <!-- Information and Actions -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <h2>ข้อมูลการแจ้งโอน</h2>
                    </div>
                    <div class="card-body detail-list">
                        <p><strong>เลขที่ออเดอร์:</strong> <a href="order_detail.php?id=<?php echo $payment['order_id']; ?>"><?php echo htmlspecialchars($payment['order_number']); ?></a></p>
                        <p><strong>วันที่โอน:</strong> <?php echo date('d/m/Y', strtotime($payment['transfer_date'])); ?></p>
                        <p><strong>เวลาที่โอน:</strong> <?php echo htmlspecialchars($payment['transfer_time']); ?></p>
                        <div class="comparison">
                            <p class="amount-due"><strong>ยอดที่ต้องชำระ:</strong> <?php echo number_format($order['total_amount'], 2); ?> บาท</p>
                            <p class="amount-paid"><strong>ยอดเงินที่โอน:</strong> <?php echo number_format($payment['transfer_amount'], 2); ?> บาท</p>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>จัดการการชำระเงิน</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="payment_detail.php?id=<?php echo $notification_id; ?>">
                            <input type="hidden" name="order_id" value="<?php echo $payment['order_id']; ?>">
                            <div class="form-group">
                                <label for="status">สถานะ</label>
                                <select name="status" id="status" <?php echo ($payment['status'] !== 'pending') ? 'disabled' : ''; ?>>
                                    <option value="pending" <?php echo ($payment['status'] == 'pending') ? 'selected' : ''; ?>>รอตรวจสอบ</option>
                                    <option value="verified" <?php echo ($payment['status'] == 'verified') ? 'selected' : ''; ?>>อนุมัติ</option>
                                    <option value="rejected" <?php echo ($payment['status'] == 'rejected') ? 'selected' : ''; ?>>ปฏิเสธ</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="admin_notes">หมายเหตุ (ถ้ามี)</label>
                                <textarea name="admin_notes" id="admin_notes" rows="3" <?php echo ($payment['status'] !== 'pending') ? 'disabled' : ''; ?>><?php echo htmlspecialchars($payment['admin_notes'] ?? ''); ?></textarea>
                            </div>
                            <?php if ($payment['status'] === 'pending'): ?>
                                <div class="form-group">
                                    <button type="submit" name="update_payment_status">บันทึก</button>
                                </div>
                            <?php else: ?>
                                <p>รายการนี้ถูกจัดการแล้วโดย <strong>แอดมิน ID: <?php echo htmlspecialchars($payment['verified_by']); ?></strong><br>เมื่อวันที่: <?php echo date('d/m/Y H:i', strtotime($payment['verified_at'])); ?></p>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>