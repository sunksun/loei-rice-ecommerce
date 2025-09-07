<?php
session_start();
require_once 'config/database.php'; // เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล

$pdo = getDB();

// 1. รับเลขที่คำสั่งซื้อจาก URL
$order_number = isset($_GET['order_number']) ? trim($_GET['order_number']) : '';

if (empty($order_number)) {
    // ถ้าไม่มีเลขที่ออเดอร์ ให้กลับไปหน้าแรก
    header("Location: index.php");
    exit;
}

try {
    // 2. ดึงข้อมูลคำสั่งซื้อหลักจากตาราง orders
    $stmt_order = $pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
    $stmt_order->execute([$order_number]);
    $order = $stmt_order->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die("ไม่พบคำสั่งซื้อนี้ในระบบ");
    }

    // 3. ดึงรายการสินค้าทั้งหมดของคำสั่งซื้อนี้จากตาราง order_items
    $stmt_items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt_items->execute([$order['id']]);
    $order_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // 4. ดึงข้อมูลบัญชีธนาคารสำหรับการชำระเงินจากตาราง site_settings
    $payment_keys = ['bank_name', 'bank_account_name', 'bank_account_number', 'promptpay_number'];
    $placeholders = str_repeat('?,', count($payment_keys) - 1) . '?';
    $stmt_settings = $pdo->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ($placeholders)");
    $stmt_settings->execute($payment_keys);
    $payment_info_raw = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);

    // จัดข้อมูลให้ใช้งานง่าย
    $payment_info = [
        'bank_name' => $payment_info_raw['bank_name'] ?? 'N/A',
        'account_name' => $payment_info_raw['bank_account_name'] ?? 'N/A',
        'account_number' => $payment_info_raw['bank_account_number'] ?? 'N/A',
        'promptpay' => $payment_info_raw['promptpay_number'] ?? 'N/A'
    ];
} catch (Exception $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage());
}

// แปลงข้อมูลที่อยู่ (JSON) ให้เป็น array เพื่อใช้งาน
$shipping_address = json_decode($order['shipping_address'], true);

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยืนยันคำสั่งซื้อ #<?php echo htmlspecialchars($order['order_number']); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
            margin: 0;
            padding: 2rem 1rem;
        }

        .container {
            max-width: 800px;
            margin: auto;
            background: white;
            padding: 2.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .confirmation-header {
            text-align: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .confirmation-header .icon {
            font-size: 4rem;
            color: #27ae60;
        }

        .confirmation-header h1 {
            color: #2d5016;
            margin: 0.5rem 0;
        }

        .order-details,
        .payment-instructions {
            margin-bottom: 2rem;
        }

        .order-details h2,
        .payment-instructions h2 {
            color: #2d5016;
            border-bottom: 2px solid #27ae60;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .items-table th,
        .items-table td {
            text-align: left;
            padding: 0.8rem;
            border-bottom: 1px solid #eee;
        }

        .items-table th {
            background-color: #f8f9fa;
        }

        .totals {
            text-align: right;
            margin-top: 1.5rem;
        }

        .totals p {
            margin: 0.5rem 0;
        }

        .totals .grand-total {
            font-size: 1.2rem;
            font-weight: bold;
        }

        .payment-box {
            background: #e8f5e8;
            border-left: 4px solid #27ae60;
            padding: 1.5rem;
            border-radius: 6px;
        }

        .home-button {
            display: block;
            width: fit-content;
            margin: 2rem auto 0;
            background: #27ae60;
            color: white;
            text-decoration: none;
            padding: 0.8rem 2rem;
            border-radius: 6px;
        }

        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            font-size: 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
        }

        .btn-outline-secondary {
            background: none;
            color: #333;
            border: 1px solid #ccc;
        }

        .btn-outline-secondary:hover {
            background: #f0f0f0;
        }

        @media print {

            body,
            html {
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 210mm;
                height: 297mm;
                box-sizing: border-box;
                font-size: 12px !important;
            }

            .container {
                max-width: 100% !important;
                width: 190mm !important;
                margin: 0 auto !important;
                box-shadow: none !important;
                padding: 0.5in 0.5in 0.5in 0.5in !important;
                background: white !important;
                border-radius: 0 !important;
            }

            .confirmation-header .icon {
                font-size: 2rem !important;
            }

            .btn,
            .home-button,
            .payment-instructions a,
            .btn-outline-secondary {
                display: none !important;
            }

            .order-details h2,
            .payment-instructions h2 {
                font-size: 1rem !important;
                border-bottom: 1px solid #aaa !important;
                padding-bottom: 0.2rem !important;
            }

            .items-table th,
            .items-table td {
                font-size: 0.85rem !important;
                padding: 0.3rem !important;
            }

            .totals {
                font-size: 0.95rem !important;
            }

            .payment-box {
                background: #f7f7f7 !important;
                border-left: 3px solid #27ae60 !important;
                padding: 0.7rem !important;
            }

            @page {
                size: A4;
                margin: 0;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="confirmation-header">
            <div class="icon">✅</div>
            <h1>ขอบคุณสำหรับคำสั่งซื้อ</h1>
            <p>เราได้รับคำสั่งซื้อของคุณแล้ว และจะดำเนินการจัดส่งโดยเร็วที่สุด</p>
        </div>

        <div class="order-details">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <h2 style="margin-bottom: 0;">รายละเอียดคำสั่งซื้อ</h2>
                <button onclick="window.print()" class="btn btn-outline-secondary" style="margin-left: auto;">🖨️
                    พิมพ์</button>
            </div>
            <div class="detail-grid">
                <div>
                    <strong>เลขที่คำสั่งซื้อ:</strong><br>
                    <?php echo htmlspecialchars($order['order_number']); ?>
                </div>
                <div>
                    <strong>วันที่สั่งซื้อ:</strong><br>
                    <?php echo date("d/m/Y H:i", strtotime($order['ordered_at'])); ?>
                </div>
                <div>
                    <strong>ที่อยู่สำหรับจัดส่ง:</strong><br>
                    <?php echo htmlspecialchars($shipping_address['first_name'] . ' ' . $shipping_address['last_name']); ?><br>
                    <?php echo htmlspecialchars($shipping_address['address_line1']); ?><br>
                    <?php echo htmlspecialchars($shipping_address['city'] . ' ' . $shipping_address['postal_code']); ?><br>
                    โทร: <?php echo htmlspecialchars($shipping_address['phone']); ?>
                </div>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>สินค้า</th>
                        <th>จำนวน</th>
                        <th style="text-align: right;">ราคา</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td style="text-align: right;"><?php echo number_format($item['total_price'], 2); ?> บาท</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="totals">
                <p>ยอดรวม: <?php echo number_format($order['subtotal'], 2); ?> บาท</p>
                <p>ค่าจัดส่ง: <?php echo number_format($order['shipping_cost'], 2); ?> บาท</p>
                <p class="grand-total">ยอดที่ต้องชำระ: <?php echo number_format($order['total_amount'], 2); ?> บาท</p>
            </div>
        </div>

        <div class="payment-instructions">
            <h2>ขั้นตอนการชำระเงิน</h2>
            <div class="payment-box">
                <p>กรุณาโอนเงินมาที่บัญชี:</p>
                <p><strong>ธนาคาร:</strong> <?php echo htmlspecialchars($payment_info['bank_name']); ?></p>
                <p><strong>ชื่อบัญชี:</strong> <?php echo htmlspecialchars($payment_info['account_name']); ?></p>
                <p><strong>เลขที่บัญชี:</strong> <?php echo htmlspecialchars($payment_info['account_number']); ?></p>
                <hr style="margin: 1rem 0; border: none; border-top: 1px solid #ddd;">
                <p>หรือชำระผ่าน <strong>PromptPay</strong> ที่เบอร์: <?php echo htmlspecialchars($payment_info['promptpay']); ?></p>
                <br>
                <p>หลังจากชำระเงินแล้ว กรุณาแจ้งการชำระเงินที่หน้า "แจ้งชำระเงิน" พร้อมแนบหลักฐานการโอน</p>
                <a href="payment-notification.php?order_number=<?php echo htmlspecialchars($order['order_number']); ?>"
                    style="display: block; text-align: center; background: #27ae60; color: white; padding: 0.8rem; border-radius: 6px; text-decoration: none; margin-top: 1.5rem;">
                    คลิกที่นี่เพื่อแจ้งชำระเงิน
                </a>
            </div>
        </div>

        <a href="index.php" class="home-button">กลับสู่หน้าแรก</a>
    </div>

    <script>
        // เคลียร์ตะกร้าสินค้าใน LocalStorage หลังจากที่การสั่งซื้อสำเร็จ
        document.addEventListener('DOMContentLoaded', function() {
            localStorage.removeItem('cart');
            localStorage.removeItem('orderForCheckout');

            // อัปเดตไอคอนตะกร้าบน Header ให้เป็น (0) (ถ้ามี)
            const cartCountEl = window.opener ? window.opener.document.getElementById('cartCount') : null;
            if (cartCountEl) {
                cartCountEl.textContent = '(0)';
            }
        });
    </script>
</body>

</html>