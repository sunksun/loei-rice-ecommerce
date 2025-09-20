<?php
session_start();
require_once 'config/config.php'; // เพิ่มการโหลด config.php
require_once 'config/database.php'; // เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล
require_once 'error_handler.php'; // เพิ่ม error handler
require_once 'stock_manager.php'; // เพิ่ม stock manager

// ส่วนประมวลผลฟอร์ม (Process Form)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ตรวจสอบ CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf_token)) {
        logActivity('customer', $_SESSION['user_id'] ?? null, 'checkout_csrf_failed', 'CSRF token validation failed from IP: ' . getUserIpAddress());
        handleError('CSRF token validation failed', 'checkout', 'cart.php');
    }

    // ตรวจสอบ rate limiting สำหรับการสั่งซื้อ
    $user_ip = getUserIpAddress();
    $rate_limit_key = "order_rate_limit_" . md5($user_ip);
    
    if (isset($_SESSION[$rate_limit_key])) {
        $requests = $_SESSION[$rate_limit_key];
        if ($requests['count'] >= 5 && (time() - $requests['start_time']) < 3600) {
            logActivity('customer', $_SESSION['user_id'] ?? null, 'checkout_rate_limited', 'Order rate limit exceeded from IP: ' . $user_ip);
            handleError('Rate limit exceeded', 'checkout', 'cart.php');
        }
        
        if ((time() - $requests['start_time']) >= 3600) {
            $_SESSION[$rate_limit_key] = ['count' => 1, 'start_time' => time()];
        } else {
            $_SESSION[$rate_limit_key]['count']++;
        }
    } else {
        $_SESSION[$rate_limit_key] = ['count' => 1, 'start_time' => time()];
    }

    // รับข้อมูลจากฟอร์มและทำ sanitization
    $first_name = cleanInput($_POST['first_name'] ?? '');
    $last_name = cleanInput($_POST['last_name'] ?? '');
    $address_line1 = cleanInput($_POST['address_line1'] ?? '');
    $city = cleanInput($_POST['city'] ?? '');
    $postal_code = cleanInput($_POST['postal_code'] ?? '');
    $phone = cleanInput($_POST['phone'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $customer_notes = cleanInput($_POST['customer_notes'] ?? '');

    // รับข้อมูลตะกร้าสินค้าที่ถูกส่งมาแบบซ่อน
    $order_items_json = $_POST['order_items'] ?? '';
    $order_summary_json = $_POST['order_summary'] ?? '';

    // ตรวจสอบข้อมูล JSON
    if (empty($order_items_json) || empty($order_summary_json)) {
        handleError('Missing order data', 'checkout', 'cart.php');
    }

    $items = json_decode($order_items_json, true);
    $summary = json_decode($order_summary_json, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($items) || !is_array($summary)) {
        handleError('Invalid JSON data', 'checkout', 'cart.php');
    }

    // Enhanced input validation using ErrorHandler
    $validation_rules = [
        'first_name' => [
            'required' => true,
            'max_length' => 50,
            'label' => 'ชื่อ'
        ],
        'last_name' => [
            'required' => true,
            'max_length' => 50,
            'label' => 'นามสกุล'
        ],
        'address_line1' => [
            'required' => true,
            'max_length' => 255,
            'label' => 'ที่อยู่'
        ],
        'city' => [
            'required' => true,
            'max_length' => 100,
            'label' => 'จังหวัด'
        ],
        'postal_code' => [
            'required' => true,
            'label' => 'รหัสไปรษณีย์'
        ],
        'phone' => [
            'required' => true,
            'type' => 'phone',
            'label' => 'เบอร์โทรศัพท์'
        ],
        'email' => [
            'required' => true,
            'type' => 'email',
            'max_length' => 255,
            'label' => 'อีเมล'
        ],
        'customer_notes' => [
            'required' => false,
            'max_length' => 500,
            'label' => 'หมายเหตุ'
        ]
    ];

    $form_data = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'address_line1' => $address_line1,
        'city' => $city,
        'postal_code' => $postal_code,
        'phone' => $phone,
        'email' => $email,
        'customer_notes' => $customer_notes
    ];

    $validation_result = validateFormData($form_data, $validation_rules);

    if (!$validation_result['valid']) {
        $error_message = "ข้อมูลไม่ถูกต้อง: " . implode(", ", $validation_result['errors']);
        logError($error_message, 'checkout_validation', $validation_result['errors']);
        header("Location: checkout.php?error=" . urlencode($error_message));
        exit();
    }

    // ตรวจสอบรหัสไปรษณีย์เพิ่มเติม
    if (!preg_match('/^[0-9]{5}$/', $postal_code)) {
        handleError('Invalid postal code format', 'checkout');
    }

    // ตรวจสอบว่ามีสินค้าในตะกร้าหรือไม่
    if (empty($items)) {
        handleError('Empty cart', 'checkout', 'cart.php');
    }

    // ตรวจสอบ stock availability ก่อนดำเนินการสั่งซื้อ (ใช้ StockManager)
    $stock_check = checkCartStock($items);
    
    if (!$stock_check['success']) {
        $error_message = "ไม่สามารถดำเนินการสั่งซื้อได้: " . implode(", ", $stock_check['errors']);
        logError($error_message, 'checkout_stock_check', ['errors' => $stock_check['errors'], 'items' => $items]);
        header("Location: cart.php?error=" . urlencode($error_message));
        exit();
    }
    
    // อัปเดต items ด้วยข้อมูลที่ validate แล้ว
    $items = $stock_check['available_items'];

    try {
        $pdo = getDB();
        if (!$pdo) {
            throw new Exception("ไม่สามารถเชื่อมต่อฐานข้อมูลได้");
        }
    } catch (Exception $e) {
        die("เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage());
    }

    try {
        // เริ่ม Transaction เพื่อความปลอดภัยของข้อมูล
        $pdo->beginTransaction();

        // 1. สร้างที่อยู่สำหรับจัดส่งและออกบิล (ใช้ข้อมูลเดียวกัน)
        $address_data_json = json_encode([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'address_line1' => $address_line1,
            'city' => $city,
            'postal_code' => $postal_code,
            'phone' => $phone,
            'email' => $email
        ]);

        // 2. สร้างเลขที่คำสั่งซื้อที่ไม่ซ้ำกัน
        $order_number = 'LOEIRICE-' . time();

        // 3. บันทึกข้อมูลลงในตาราง `orders` (เพิ่ม billing_address)
        $stmt_order = $pdo->prepare("
    INSERT INTO orders (user_id, order_number, status, subtotal, shipping_cost, discount_amount, total_amount, payment_method, payment_status, shipping_address, billing_address, customer_notes)
    VALUES (:user_id, :order_number, :status, :subtotal, :shipping_cost, :discount_amount, :total_amount, :payment_method, :payment_status, :shipping_address, :billing_address, :customer_notes)
");

        $stmt_order->execute([
            ':user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null,
            ':order_number' => $order_number,
            ':status' => 'pending',
            ':subtotal' => $summary['subtotal'],
            ':shipping_cost' => $summary['shippingCost'],
            ':discount_amount' => $summary['discountAmount'],
            ':total_amount' => $summary['grandTotal'],
            ':payment_method' => 'bank_transfer',
            ':payment_status' => 'pending',
            ':shipping_address' => $address_data_json, // ใช้ข้อมูลที่อยู่
            ':billing_address' => $address_data_json,  // ใช้ข้อมูลที่อยู่เดียวกัน
            ':customer_notes' => $customer_notes
        ]);

        $order_id = $pdo->lastInsertId(); // ดึง ID ของออเดอร์ล่าสุด

        // 4. บันทึกรายการสินค้าแต่ละชิ้นลงในตาราง `order_items`
        $stmt_items = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, total_price)
            VALUES (:order_id, :product_id, :product_name, :product_price, :quantity, :total_price)
        ");

        // 5. บันทึกรายการสินค้าและตัดสต็อกแบบปลอดภัย (ใช้ StockManager)
        $stock_manager = new StockManager();
        
        foreach ($items as $item) {
            // บันทึกรายการสินค้า
            $stmt_items->execute([
                ':order_id' => $order_id,
                ':product_id' => $item['id'],
                ':product_name' => $item['name'],
                ':product_price' => $item['price'],
                ':quantity' => $item['quantity'],
                ':total_price' => $item['price'] * $item['quantity']
            ]);
            
            // ตัดสต็อกด้วย StockManager
            $deduct_result = $stock_manager->deductStock($item['id'], $item['quantity'], $order_number);
            
            if (!$deduct_result['success']) {
                throw new Exception("ไม่สามารถตัดสต็อกสินค้า '{$item['name']}' ได้: " . $deduct_result['error']);
            }
        }

        // หากทุกอย่างสำเร็จ ให้ยืนยันการทำรายการ
        $pdo->commit();

        // ส่งผู้ใช้ไปยังหน้ายืนยันคำสั่งซื้อ
        header("Location: order-confirmation.php?order_number=" . $order_number);
        exit();
    } catch (Exception $e) {
        // หากเกิดข้อผิดพลาด ให้ยกเลิกการทำรายการทั้งหมด
        $pdo->rollBack();
        
        // ใช้ ErrorHandler สำหรับจัดการ error
        $order_data = [
            'customer_name' => $first_name . ' ' . $last_name,
            'email' => $email,
            'items_count' => count($items),
            'total_amount' => $summary['grandTotal'] ?? 0
        ];
        
        logError($e, 'checkout', $order_data);
        logActivity('customer', $_SESSION['user_id'] ?? null, 'checkout_failed', 'Checkout failed: ' . $e->getMessage());
        
        handleError($e, 'checkout');
    }
}
// สร้าง CSRF token สำหรับฟอร์ม
$csrf_token = generateCSRFToken();

// --- ส่วนที่ 1: เพิ่มฟังก์ชันนี้ไว้ที่ส่วนบนสุดของไฟล์ checkout.php ---
// (วางไว้หลังบรรทัด require_once 'config/database.php';)

/**
 * ฟังก์ชันสำหรับดึงรายชื่อจังหวัดทั้งหมดในประเทศไทย
 * @return array รายชื่อจังหวัด
 */
function getThaiProvinces()
{
    return [
        'กระบี่',
        'กรุงเทพมหานคร',
        'กาญจนบุรี',
        'กาฬสินธุ์',
        'กำแพงเพชร',
        'ขอนแก่น',
        'จันทบุรี',
        'ฉะเชิงเทรา',
        'ชลบุรี',
        'ชัยนาท',
        'ชัยภูมิ',
        'ชุมพร',
        'เชียงราย',
        'เชียงใหม่',
        'ตรัง',
        'ตราด',
        'ตาก',
        'นครนายก',
        'นครปฐม',
        'นครพนม',
        'นครราชสีมา',
        'นครศรีธรรมราช',
        'นครสวรรค์',
        'นนทบุรี',
        'นราธิวาส',
        'น่าน',
        'บึงกาฬ',
        'บุรีรัมย์',
        'ปทุมธานี',
        'ประจวบคีรีขันธ์',
        'ปราจีนบุรี',
        'ปัตตานี',
        'พระนครศรีอยุธยา',
        'พะเยา',
        'พังงา',
        'พัทลุง',
        'พิจิตร',
        'พิษณุโลก',
        'เพชรบุรี',
        'เพชรบูรณ์',
        'แพร่',
        'ภูเก็ต',
        'มหาสารคาม',
        'มุกดาหาร',
        'แม่ฮ่องสอน',
        'ยโสธร',
        'ยะลา',
        'ร้อยเอ็ด',
        'ระนอง',
        'ระยอง',
        'ราชบุรี',
        'ลพบุรี',
        'ลำปาง',
        'ลำพูน',
        'เลย',
        'ศรีสะเกษ',
        'สกลนคร',
        'สงขลา',
        'สตูล',
        'สมุทรปราการ',
        'สมุทรสงคราม',
        'สมุทรสาคร',
        'สระแก้ว',
        'สระบุรี',
        'สิงห์บุรี',
        'สุโขทัย',
        'สุพรรณบุรี',
        'สุราษฎร์ธานี',
        'สุรินทร์',
        'หนองคาย',
        'หนองบัวลำภู',
        'อ่างทอง',
        'อำนาจเจริญ',
        'อุดรธานี',
        'อุตรดิตถ์',
        'อุทัยธานี',
        'อุบลราชธานี'
    ];
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ดำเนินการสั่งซื้อ - ข้าวพันธุ์พื้นเมืองเลย</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
            margin: 0;
        }

        .container {
            max-width: 1100px;
            margin: auto;
            padding: 2rem 1rem;
        }

        .checkout-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2.5rem;
        }

        .form-section,
        .summary-section {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        h1,
        h2,
        h3 {
            color: #2d5016;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }

        .order-summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }

        .order-summary-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 1rem;
        }

        .order-total {
            text-align: right;
            margin-top: 1.5rem;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .submit-btn {
            width: 100%;
            padding: 1rem;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1.5rem;
        }

        @media (max-width: 992px) {
            .checkout-layout {
                grid-template-columns: 1fr;
            }

            .summary-section {
                margin-top: 2rem;
            }
        }

        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            background-color: #fff;
            /* เพิ่มพื้นหลังสีขาว */
            -webkit-appearance: none;
            /* ลบสไตล์เริ่มต้นของเบราว์เซอร์ */
            -moz-appearance: none;
            appearance: none;
            /* เพิ่มลูกศร (arrow) เข้าไปเอง */
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23666%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22/%3E%3C/svg%3E');
            background-repeat: no-repeat;
            background-position: right .7em top 50%;
            background-size: .65em auto;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>ดำเนินการสั่งซื้อ</h1>
        
        <?php if (isset($_GET['error'])): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; margin: 15px 0; border: 1px solid #f5c6cb; border-radius: 5px;">
                <strong>เกิดข้อผิดพลาด:</strong> <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; margin: 15px 0; border: 1px solid #c3e6cb; border-radius: 5px;">
                <strong>สำเร็จ:</strong> <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>
        
        <form id="checkoutForm" method="POST" action="checkout.php">
            <div class="checkout-layout">
                <div class="form-section">
                    <h2>ข้อมูลสำหรับจัดส่ง</h2>
                    <div class="form-group">
                        <label for="first_name">ชื่อ</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">นามสกุล</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label for="address_line1">ที่อยู่</label>
                        <textarea id="address_line1" name="address_line1" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="city">จังหวัด</label>
                        <select id="city" name="city" class="form-control" required>
                            <option value="" disabled selected>--- กรุณาเลือกจังหวัด ---</option>
                            <?php
                            try {
                                $provinces = getThaiProvinces();
                                if (empty($provinces)) {
                                    throw new Exception("ไม่สามารถโหลดรายชื่อจังหวัดได้");
                                }
                                sort($provinces); // เรียงตามตัวอักษร
                                foreach ($provinces as $province) {
                                    echo "<option value=\"{$province}\">" . htmlspecialchars($province) . "</option>";
                                }
                            } catch (Exception $e) {
                                // Fallback provinces
                                $fallback_provinces = ['กรุงเทพมหานคร', 'เชียงใหม่', 'นครราชสีมา', 'ขอนแก่น', 'เลย'];
                                foreach ($fallback_provinces as $province) {
                                    echo "<option value=\"{$province}\">" . htmlspecialchars($province) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="postal_code">รหัสไปรษณีย์</label>
                        <input type="text" id="postal_code" name="postal_code" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">เบอร์โทรศัพท์</label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label for="email">อีเมล</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="customer_notes">หมายเหตุเพิ่มเติม</label>
                        <textarea id="customer_notes" name="customer_notes" rows="3"></textarea>
                    </div>
                </div>

                <div class="summary-section">
                    <h2>สรุปรายการสินค้า</h2>
                    <div id="orderSummaryList">
                    </div>
                    <div id="orderTotal" class="order-total">
                    </div>

                    <input type="hidden" name="order_items" id="order_items_json">
                    <input type="hidden" name="order_summary" id="order_summary_json">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                    <button type="submit" class="submit-btn">ยืนยันการสั่งซื้อ</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const orderDataString = localStorage.getItem('orderForCheckout');
            if (!orderDataString) {
                alert('ไม่พบข้อมูลการสั่งซื้อ กรุณากลับไปที่หน้าตะกร้าสินค้า');
                window.location.href = 'cart.php';
                return;
            }

            const orderData = JSON.parse(orderDataString);
            const summaryListEl = document.getElementById('orderSummaryList');
            const totalEl = document.getElementById('orderTotal');

            // 1. แสดงรายการสินค้า
            let itemsHtml = '';
            orderData.items.forEach(item => {
                const placeholderSvg = 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%2260%22%20height%3D%2260%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Crect%20width%3D%2260%22%20height%3D%2260%22%20fill%3D%22%23eee%22%3E%3C/rect%3E%3C/svg%3E';
                const imageUrl = item.image_main ? `uploads/products/${item.image_main}` : placeholderSvg;
                itemsHtml += `
                    <div class="order-summary-item">
                        <img src="${imageUrl}" alt="${item.name}">
                        <div>
                            <strong>${item.name}</strong><br>
                            <small>จำนวน: ${item.quantity}</small>
                        </div>
                        <span>${(item.price * item.quantity).toLocaleString('th-TH')} บาท</span>
                    </div>
                `;
            });
            summaryListEl.innerHTML = itemsHtml;

            // 2. แสดงยอดรวม
            totalEl.innerHTML = `
                <p>ยอดรวม: ${orderData.subtotal.toLocaleString('th-TH')} บาท</p>
                <p>ค่าจัดส่ง: ${orderData.shippingCost.toLocaleString('th-TH')} บาท</p>
                <h3>ยอดที่ต้องชำระ: ${orderData.grandTotal.toLocaleString('th-TH')} บาท</h3>
            `;

            // 3. ใส่ข้อมูลลงใน input ที่ซ่อนไว้เพื่อส่งไป PHP
            document.getElementById('order_items_json').value = JSON.stringify(orderData.items);
            document.getElementById('order_summary_json').value = JSON.stringify({
                subtotal: orderData.subtotal,
                shippingCost: orderData.shippingCost,
                discountAmount: orderData.discountAmount,
                grandTotal: orderData.grandTotal
            });

            // 4. เพิ่ม Listener ให้ฟอร์ม เมื่อส่งสำเร็จให้เคลียร์ตะกร้า
            document.getElementById('checkoutForm').addEventListener('submit', function() {
                localStorage.removeItem('cart');
                localStorage.removeItem('orderForCheckout');
            });
        });
    </script>
</body>

</html>