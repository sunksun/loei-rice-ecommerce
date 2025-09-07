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

// 1. รับ ID ของลูกค้าจาก URL
$customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($customer_id === 0) {
    header('Location: customers.php');
    exit();
}

// 2. จัดการการอัปเดตข้อมูล (เมื่อมีการส่งฟอร์ม)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_customer'])) {
    $status = $_POST['status'];
    try {
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$status, $customer_id]);
        $_SESSION['success_message'] = "อัปเดตข้อมูลลูกค้าสำเร็จ!";
        header("Location: customer_detail.php?id=" . $customer_id);
        exit();
    } catch (Exception $e) {
        $error_message = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล: " . $e->getMessage();
    }
}

// 3. ดึงข้อมูลลูกค้าและข้อมูลที่เกี่ยวข้อง
try {
    // ดึงข้อมูลลูกค้าหลัก
    $stmt_customer = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt_customer->execute([$customer_id]);
    $customer = $stmt_customer->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        die("ไม่พบลูกค้ารายนี้");
    }

    // ดึงประวัติการสั่งซื้อของลูกค้า
    $stmt_orders = $pdo->prepare("SELECT id, order_number, ordered_at, total_amount, status FROM orders WHERE user_id = ? ORDER BY ordered_at DESC LIMIT 10");
    $stmt_orders->execute([$customer_id]);
    $orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage());
}

// ฟังก์ชันสำหรับสร้าง Badge ของสถานะ
function getStatusBadge($status)
{
    $colors = ['active' => '#27ae60', 'inactive' => '#7f8c8d', 'banned' => '#e74c3c'];
    $color = $colors[$status] ?? '#7f8c8d';
    $status_th = ['active' => 'ใช้งาน', 'inactive' => 'ไม่ใช้งาน', 'banned' => 'ถูกระงับ'];
    $text = $status_th[$status] ?? $status;
    return "<span style='background-color: {$color}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8em;'>" . htmlspecialchars($text) . "</span>";
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดลูกค้า - <?php echo htmlspecialchars($customer['first_name']); ?></title>
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

        .customer-layout {
            display: grid;
            grid-template-columns: 1fr 2fr;
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
            margin: 0 0 0.8rem 0;
        }

        .detail-list strong {
            color: #555;
            display: inline-block;
            min-width: 100px;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table th,
        .history-table td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid #eee;
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

        .back-link {
            color: white;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 5px;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="header-content">
            <span>🌾 รายละเอียดลูกค้า</span>
            <a href="customers.php" class="back-link">กลับไปหน้ารายชื่อ</a>
        </div>
    </header>

    <div class="container">
        <?php
        if (isset($_SESSION['success_message'])) {
            echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
            unset($_SESSION['success_message']);
        }
        ?>

        <div class="customer-layout">
            <!-- Sidebar -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <h2>ข้อมูลลูกค้า</h2>
                    </div>
                    <div class="card-body detail-list">
                        <p><strong>ชื่อ-นามสกุล:</strong> <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></p>
                        <p><strong>อีเมล:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
                        <p><strong>เบอร์โทร:</strong> <?php echo htmlspecialchars($customer['phone'] ?? '-'); ?></p>
                        <p><strong>สถานะ:</strong> <?php echo getStatusBadge($customer['status']); ?></p>
                        <p><strong>สมัครเมื่อ:</strong> <?php echo date('d/m/Y H:i', strtotime($customer['created_at'])); ?></p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>จัดการลูกค้า</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="customer_detail.php?id=<?php echo $customer_id; ?>">
                            <div class="form-group">
                                <label for="status">เปลี่ยนสถานะ</label>
                                <select name="status" id="status">
                                    <option value="active" <?php echo ($customer['status'] == 'active') ? 'selected' : ''; ?>>ใช้งาน</option>
                                    <option value="inactive" <?php echo ($customer['status'] == 'inactive') ? 'selected' : ''; ?>>ไม่ใช้งาน</option>
                                    <option value="banned" <?php echo ($customer['status'] == 'banned') ? 'selected' : ''; ?>>ระงับบัญชี</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <button type="submit" name="update_customer">บันทึกการเปลี่ยนแปลง</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <h2>ประวัติการสั่งซื้อ (10 รายการล่าสุด)</h2>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>เลขที่ออเดอร์</th>
                                    <th>วันที่สั่งซื้อ</th>
                                    <th>ยอดรวม</th>
                                    <th>สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; padding: 2rem;">ไม่มีประวัติการสั่งซื้อ</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><a href="order_detail.php?id=<?php echo $order['id']; ?>"><?php echo htmlspecialchars($order['order_number']); ?></a></td>
                                            <td><?php echo date('d/m/Y', strtotime($order['ordered_at'])); ?></td>
                                            <td><?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($order['status']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>