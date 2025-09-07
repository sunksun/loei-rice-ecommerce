<?php
// order-tracking.php - ติดตามสถานะคำสั่งซื้อสำหรับ guest/ลูกค้าทั่วไป
require_once 'config/config.php';
require_once 'config/database.php';

$order = null;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_number = trim($_POST['order_number'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    if ($order_number === '' || $contact === '') {
        $error = 'กรุณากรอกเลขที่คำสั่งซื้อและเบอร์โทรศัพท์หรืออีเมล';
    } else {
        $conn = getDB();
        $stmt = $conn->prepare("SELECT * FROM orders WHERE order_number = ?");
        $stmt->execute([$order_number]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            $error = 'ไม่พบคำสั่งซื้อนี้ในระบบ';
        } else {
            // ตรวจสอบเบอร์โทรหรืออีเมลใน shipping_address หรือ billing_address (JSON)
            $found = false;
            foreach (['shipping_address', 'billing_address'] as $addr_field) {
                if (!empty($order[$addr_field])) {
                    $addr = json_decode($order[$addr_field], true);
                    // Normalize phone: remove non-digits
                    $input_phone = preg_replace('/\D+/', '', $contact);
                    $db_phone = isset($addr['phone']) ? preg_replace('/\D+/', '', $addr['phone']) : '';
                    // Normalize email: lowercase
                    $input_email = strtolower($contact);
                    $db_email = isset($addr['email']) ? strtolower($addr['email']) : '';
                    if ((strlen($input_phone) > 6 && $input_phone !== '' && $db_phone !== '' && strpos($db_phone, $input_phone) !== false)
                        || ($input_email !== '' && $db_email !== '' && $db_email === $input_email)
                    ) {
                        $found = true;
                        break;
                    }
                }
            }
            if (!$found) {
                $error = 'ข้อมูลติดต่อไม่ถูกต้อง';
                $order = null;
            }
        }
    }
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
    <title>ติดตามคำสั่งซื้อ | ข้าวพื้นเมืองเลย</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <style>
        body {
            background: #f8f9fa;
        }

        .track-container {
            max-width: 500px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.07);
            padding: 2rem;
        }
    </style>
</head>

<body>
    <div class="track-container">
        <h3 class="mb-4">ติดตามคำสั่งซื้อ</h3>
        <form method="post" class="mb-4">
            <div class="mb-3">
                <label for="order_number" class="form-label">เลขที่คำสั่งซื้อ</label>
                <input type="text" class="form-control" id="order_number" name="order_number" required placeholder="กรอกเลขที่คำสั่งซื้อ">
            </div>
            <div class="mb-3">
                <label for="contact" class="form-label">เบอร์โทรศัพท์หรืออีเมล</label>
                <input type="text" class="form-control" id="contact" name="contact" required placeholder="กรอกเบอร์โทรศัพท์หรืออีเมลที่ใช้สั่งซื้อ">
            </div>
            <button type="submit" class="btn btn-primary">ค้นหา</button>
        </form>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>
        <?php if ($order): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">เลขที่คำสั่งซื้อ: <?php echo h($order['order_number']); ?></h5>
                    <p class="card-text mb-1">วันที่สั่งซื้อ: <?php echo date('d/m/Y H:i', strtotime($order['ordered_at'])); ?></p>
                    <p class="card-text mb-1">ยอดรวม: <?php echo number_format($order['total_amount'], 2); ?> บาท</p>
                    <p class="card-text mb-1">สถานะ: <b><?php
                                                        $status_map = [
                                                            'waiting' => 'รอชำระเงิน',
                                                            'pending' => 'รอชำระเงิน',
                                                            'paid' => 'ชำระเงินแล้ว',
                                                            'processing' => 'กำลังเตรียมสินค้า',
                                                            'shipping' => 'กำลังจัดส่ง',
                                                            'shipped' => 'จัดส่งแล้ว',
                                                            'delivered' => 'สำเร็จ',
                                                            'success' => 'สำเร็จ',
                                                            'cancel' => 'ยกเลิก',
                                                            'cancelled' => 'ยกเลิก'
                                                        ];
                                                        echo $status_map[$order['status']] ?? h($order['status']);
                                                        ?></b></p>
                    <a href="order-confirmation.php?order_number=<?php echo h($order['order_number']); ?>" class="btn btn-outline-primary btn-sm mt-2">ดูรายละเอียดคำสั่งซื้อ</a>
                </div>
            </div>
        <?php endif; ?>
        <a href="index.php" class="btn btn-secondary mt-2">ย้อนกลับหน้าแรก</a>
    </div>
</body>

</html>