<?php
session_start();
require_once 'config/database.php';
$pdo = getDB();

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_number = trim($_POST['order_number']);
    $transfer_amount = trim($_POST['transfer_amount']);
    $transfer_date = trim($_POST['transfer_date']);
    $transfer_time = trim($_POST['transfer_time']);
    $slip_image = $_FILES['slip_image'];

    // --- Validation ---
    if (empty($order_number) || empty($transfer_amount) || empty($transfer_date) || empty($transfer_time) || $slip_image['error'] !== UPLOAD_ERR_OK) {
        $error_message = 'กรุณากรอกข้อมูลให้ครบถ้วนและแนบสลิปการโอน';
    } else {
        try {
            // ตรวจสอบว่ามีออเดอร์นี้จริงหรือไม่
            $stmt_check = $pdo->prepare("SELECT id FROM orders WHERE order_number = ?");
            $stmt_check->execute([$order_number]);
            $order = $stmt_check->fetch();

            if (!$order) {
                $error_message = 'ไม่พบเลขที่คำสั่งซื้อนี้ในระบบ';
            } else {
                // --- Handle File Upload ---
                $target_dir = "uploads/slips/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }
                $file_extension = pathinfo($slip_image["name"], PATHINFO_EXTENSION);
                $new_filename = "slip_" . time() . "." . $file_extension;
                $target_file = $target_dir . $new_filename;

                if (move_uploaded_file($slip_image["tmp_name"], $target_file)) {
                    // --- Insert into database ---
                    $stmt_insert = $pdo->prepare("
                        INSERT INTO payment_notifications (order_id, order_number, transfer_amount, transfer_date, transfer_time, slip_image)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt_insert->execute([$order['id'], $order_number, $transfer_amount, $transfer_date, $transfer_time, $new_filename]);

                    $success_message = "แจ้งชำระเงินสำเร็จ! ทีมงานจะตรวจสอบและยืนยันคำสั่งซื้อของคุณโดยเร็วที่สุด";
                } else {
                    $error_message = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์";
                }
            }
        } catch (Exception $e) {
            $error_message = "เกิดข้อผิดพลาดในระบบ: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แจ้งชำระเงิน - ข้าวพันธุ์พื้นเมืองเลย</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
            margin: 0;
        }

        .container {
            max-width: 600px;
            margin: 2rem auto;
            background: white;
            padding: 2.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #2d5016;
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }

        .form-row {
            display: flex;
            gap: 1rem;
        }

        .form-row .form-group {
            flex: 1;
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

        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 6px;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            text-align: center;
        }

        /* เพิ่มสไตล์สำหรับ suggestion dropdown */
        #orderNumberSuggestions {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-top: 2px;
            display: none;
            position: absolute;
            z-index: 10;
            max-height: 180px;
            overflow-y: auto;
        }

        #orderNumberSuggestions div {
            padding: 8px;
            cursor: pointer;
        }

        #orderNumberSuggestions div:hover {
            background-color: #f1f1f1;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>📝 แจ้งชำระเงิน</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <h3>✅ <?php echo $success_message; ?></h3>
                <p>เลขที่คำสั่งซื้อ: <?php echo htmlspecialchars($order_number); ?></p>
                <a href="index.php" style="display:inline-block; margin-top:1rem; color:#155724;">กลับสู่หน้าแรก</a>
            </div>
        <?php else: ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form method="POST" action="payment-notification.php" enctype="multipart/form-data">
                <div class="form-group" style="position:relative;">
                    <label for="order_number">เลขที่คำสั่งซื้อ</label>
                    <input type="text" id="order_number" name="order_number" value="<?php echo isset($_GET['order_number']) ? htmlspecialchars($_GET['order_number']) : ''; ?>" required autocomplete="off" placeholder="กรอกเลขที่คำสั่งซื้อ เช่น 1751942980 หรือ LOEIRICE-1751942980">
                    <div id="orderNumberSuggestions"></div>
                </div>
                <div class="form-group">
                    <label for="transfer_amount">จำนวนเงินที่โอน (บาท)</label>
                    <input type="number" id="transfer_amount" name="transfer_amount" step="0.01" required>
                </div>
                <div class="form-row" style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                    <div class="form-group" style="flex:1; min-width: 160px;">
                        <label for="transfer_date">วันที่โอน</label>
                        <input type="date" id="transfer_date" name="transfer_date" required>
                    </div>
                    <div class="form-group" style="flex:1; min-width: 160px;">
                        <label for="transfer_time">เวลาที่โอน (โดยประมาณ)</label>
                        <input type="time" id="transfer_time" name="transfer_time" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="slip_image">แนบสลิปการโอน</label>
                    <input type="file" id="slip_image" name="slip_image" accept="image/png, image/jpeg, image/gif" required>
                </div>
                <button type="submit" class="submit-btn">ยืนยันการแจ้งโอน</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        // --- AJAX ค้นหา order_number ---
        const orderInput = document.getElementById('order_number');
        const suggestionDiv = document.getElementById('orderNumberSuggestions');
        if (orderInput && suggestionDiv) {
            orderInput.addEventListener('input', function() {
                const val = this.value.trim();
                if (val.length < 2) {
                    suggestionDiv.style.display = 'none';
                    suggestionDiv.innerHTML = '';
                    return;
                }
                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'api/search_order_number.php?order_number=' + encodeURIComponent(val));
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        try {
                            var data = JSON.parse(xhr.responseText);
                            if (data.success && data.orders.length > 0) {
                                var htmlContent = '';
                                for (var i = 0; i < data.orders.length; i++) {
                                    var order = data.orders[i];
                                    htmlContent += '<div style="padding:8px; cursor:pointer;" data-orderno="' + order.order_number + '">';
                                    htmlContent += '<b>' + order.order_number + '</b> - ' + order.status;
                                    htmlContent += ' <span style="color:#888; font-size:0.9em;">(' + order.created_at + ')</span>';
                                    htmlContent += '</div>';
                                }
                                suggestionDiv.innerHTML = htmlContent;
                                suggestionDiv.style.display = 'block';
                            } else {
                                suggestionDiv.innerHTML = '<div style="padding:8px; color:#888;">ไม่พบเลขที่คำสั่งซื้อ</div>';
                                suggestionDiv.style.display = 'block';
                            }
                        } catch (e) {
                            console.log('Error parsing response:', e);
                        }
                    }
                };
                xhr.send();
            });

            suggestionDiv.addEventListener('mousedown', function(e) {
                if (e.target && e.target.dataset.orderno) {
                    orderInput.value = e.target.dataset.orderno;
                    suggestionDiv.style.display = 'none';
                }
            });

            document.addEventListener('click', function(e) {
                if (!orderInput.contains(e.target) && !suggestionDiv.contains(e.target)) {
                    suggestionDiv.style.display = 'none';
                }
            });
        }
    </script>
</body>

</html>