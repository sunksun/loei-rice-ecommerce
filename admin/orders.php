<?php
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// รวมไฟล์การตั้งค่าและเชื่อมต่อฐานข้อมูล
require_once '../config/config.php';
require_once '../config/database.php';
$pdo = getDB();

// ตรวจสอบและสร้าง View order_details หากไม่มี
function ensureOrderDetailsView($pdo) {
    try {
        // ตรวจสอบว่า View มีอยู่หรือไม่
        $stmt = $pdo->query("SHOW TABLES LIKE 'order_details'");
        if ($stmt->rowCount() === 0) {
            // สร้าง View หากไม่มี
            $create_view_sql = "
                CREATE VIEW order_details AS 
                SELECT 
                    o.id, o.user_id, o.order_number, o.status, o.subtotal, 
                    o.shipping_cost, o.tax_amount, o.discount_amount, o.total_amount,
                    o.payment_method, o.payment_status, o.payment_reference, o.paid_at,
                    o.shipping_method_id, o.tracking_number, o.shipping_notes,
                    o.billing_address, o.shipping_address, o.customer_notes, o.admin_notes,
                    o.ordered_at, o.confirmed_at, o.shipped_at, o.delivered_at,
                    o.cancelled_at, o.cancel_reason, o.created_at, o.updated_at,
                    CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS customer_name,
                    u.email AS customer_email, u.phone AS customer_phone,
                    sm.name AS shipping_method_name, sm.estimated_days AS shipping_estimated_days
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN shipping_methods sm ON o.shipping_method_id = sm.id
            ";
            $pdo->exec($create_view_sql);
            return true;
        }
        return true;
    } catch (Exception $e) {
        error_log("Failed to create order_details view: " . $e->getMessage());
        return false;
    }
}

// ฟังก์ชันสำหรับ fallback query เมื่อ View ไม่พร้อมใช้งาน
function getFallbackOrdersQuery($where_conditions = [], $params = []) {
    $sql = "
        SELECT 
            o.id, o.user_id, o.order_number, o.status, o.subtotal, 
            o.shipping_cost, o.tax_amount, o.discount_amount, o.total_amount,
            o.payment_method, o.payment_status, o.payment_reference, o.paid_at,
            o.shipping_method_id, o.tracking_number, o.shipping_notes,
            o.billing_address, o.shipping_address, o.customer_notes, o.admin_notes,
            o.ordered_at, o.confirmed_at, o.shipped_at, o.delivered_at,
            o.cancelled_at, o.cancel_reason, o.created_at, o.updated_at,
            CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS customer_name,
            u.email AS customer_email, u.phone AS customer_phone,
            sm.name AS shipping_method_name, sm.estimated_days AS shipping_estimated_days
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN shipping_methods sm ON o.shipping_method_id = sm.id
    ";
    
    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(" AND ", $where_conditions);
    }
    
    return $sql;
}

// --- ส่วนของการดึงข้อมูล ---

// 1. รับค่าจาก URL เพื่อใช้กรองข้อมูล
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$date_filter = isset($_GET['date']) ? trim($_GET['date']) : '';
$month_filter = isset($_GET['month']) ? trim($_GET['month']) : '';
$year_filter = isset($_GET['year']) ? trim($_GET['year']) : '';

// 2. ตรวจสอบ View และสร้าง SQL query
$use_view = ensureOrderDetailsView($pdo);
$sql = $use_view ? "SELECT * FROM order_details" : getFallbackOrdersQuery();

$params = [];
$where_conditions = [];

// เพิ่มเงื่อนไขการกรองสถานะ
if (!empty($status_filter)) {
    $where_conditions[] = "status = :status";
    $params[':status'] = $status_filter;
}

// เพิ่มเงื่อนไขการกรองตามวันที่
if (!empty($date_filter)) {
    $where_conditions[] = "DATE(ordered_at) = :date";
    $params[':date'] = $date_filter;
}

// เพิ่มเงื่อนไขการกรองตามเดือน/ปี
if (!empty($month_filter) && !empty($year_filter)) {
    $where_conditions[] = "YEAR(ordered_at) = :year AND MONTH(ordered_at) = :month";
    $params[':year'] = $year_filter;
    $params[':month'] = $month_filter;
} elseif (!empty($year_filter)) {
    $where_conditions[] = "YEAR(ordered_at) = :year";
    $params[':year'] = $year_filter;
}

// รวมเงื่อนไข WHERE
if (!empty($where_conditions)) {
    if ($use_view) {
        $sql .= " WHERE " . implode(" AND ", $where_conditions);
    } else {
        $sql = getFallbackOrdersQuery($where_conditions, $params);
    }
}

// 3. เรียงลำดับจากออเดอร์ล่าสุดไปเก่าสุด
$sql .= " ORDER BY ordered_at DESC";

// 4. ดึงข้อมูลจากฐานข้อมูล with enhanced error handling
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Enhanced error handling with fallback
    error_log("Database query error in admin/orders.php: " . $e->getMessage());
    
    // ลองใช้ fallback query หาก view มีปัญหา
    if ($use_view) {
        try {
            $fallback_sql = getFallbackOrdersQuery($where_conditions, $params);
            $fallback_sql .= " ORDER BY ordered_at DESC";
            
            $stmt = $pdo->prepare($fallback_sql);
            $stmt->execute($params);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // บันทึกว่าใช้ fallback query
            error_log("Using fallback query for orders list");
        } catch (Exception $fallback_e) {
            error_log("Fallback query also failed: " . $fallback_e->getMessage());
            $orders = [];
            $database_error = "เกิดข้อผิดพลาดในการดึงข้อมูลคำสั่งซื้อ กรุณาลองใหม่อีกครั้ง";
        }
    } else {
        $orders = [];
        $database_error = "เกิดข้อผิดพลาดในการดึงข้อมูลคำสั่งซื้อ กรุณาติดต่อผู้ดูแลระบบ";
    }
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
            
            <?php if (isset($database_error)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 15px; margin: 15px 0; border: 1px solid #f5c6cb; border-radius: 5px;">
                    <strong>⚠️ เกิดข้อผิดพลาดในฐานข้อมูล:</strong> <?php echo htmlspecialchars($database_error); ?>
                    <?php if (!$use_view): ?>
                        <br><small>ระบบกำลังใช้โหมดสำรอง อาจแสดงข้อมูลไม่ครบถ้วน</small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$use_view): ?>
                <div style="background: #fff3cd; color: #856404; padding: 10px; margin: 10px 0; border: 1px solid #ffeaa7; border-radius: 5px;">
                    <strong>ℹ️ หมายเหตุ:</strong> ระบบใช้โหมดสำรองในการแสดงข้อมูล
                </div>
            <?php endif; ?>

            <!-- ส่วนกรองตามสถานะ -->
            <div class="filter-bar">
                <a href="orders.php" class="filter-btn <?php echo empty($status_filter) && empty($date_filter) && empty($month_filter) && empty($year_filter) ? 'active' : ''; ?>">ทั้งหมด</a>
                <a href="orders.php?status=pending" class="filter-btn <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">รอดำเนินการ</a>
                <a href="orders.php?status=processing" class="filter-btn <?php echo $status_filter == 'processing' ? 'active' : ''; ?>">กำลังเตรียมส่ง</a>
                <a href="orders.php?status=shipped" class="filter-btn <?php echo $status_filter == 'shipped' ? 'active' : ''; ?>">จัดส่งแล้ว</a>
                <a href="orders.php?status=delivered" class="filter-btn <?php echo $status_filter == 'delivered' ? 'active' : ''; ?>">ส่งถึงแล้ว</a>
                <a href="orders.php?status=cancelled" class="filter-btn <?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>">ยกเลิก</a>
            </div>

            <!-- ส่วนกรองตามวันที่ -->
            <div style="background: white; padding: 15px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h4 style="margin: 0 0 10px 0; color: #333;">🗓️ กรองตามวันที่</h4>
                <form method="GET" action="" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: end;">
                    <!-- เก็บ status filter เดิม -->
                    <?php if (!empty($status_filter)): ?>
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                    <?php endif; ?>
                    
                    <!-- กรองตามวันที่เฉพาะ (วัน/เดือน/ปี) -->
                    <div style="display: flex; flex-direction: column; gap: 5px;">
                        <label style="font-size: 0.9em; color: #666;">📅 วันที่เฉพาะ</label>
                        <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>" 
                               style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
                               onchange="clearOtherFilters('date')">
                    </div>

                    <!-- หรือ -->
                    <div style="display: flex; align-items: center; font-weight: bold; color: #666;">หรือ</div>

                    <!-- กรองตามเดือน -->
                    <div style="display: flex; flex-direction: column; gap: 5px;">
                        <label style="font-size: 0.9em; color: #666;">📆 เดือน</label>
                        <select name="month" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
                                onchange="clearOtherFilters('month')">
                            <option value="">ทุกเดือน</option>
                            <?php 
                            $months = [
                                1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
                                5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
                                9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
                            ];
                            foreach ($months as $num => $name):
                            ?>
                                <option value="<?php echo $num; ?>" <?php echo $month_filter == $num ? 'selected' : ''; ?>>
                                    <?php echo $name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- กรองตามปี -->
                    <div style="display: flex; flex-direction: column; gap: 5px;">
                        <label style="font-size: 0.9em; color: #666;">🗓️ ปี</label>
                        <select name="year" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
                                onchange="clearOtherFilters('year')">
                            <option value="">ทุกปี</option>
                            <?php 
                            $current_year = date('Y');
                            for ($year = $current_year; $year >= ($current_year - 3); $year--):
                            ?>
                                <option value="<?php echo $year; ?>" <?php echo $year_filter == $year ? 'selected' : ''; ?>>
                                    <?php echo $year + 543; ?> (<?php echo $year; ?>)
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <!-- ปุ่มดำเนินการ -->
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" style="padding: 8px 16px; background: #27ae60; color: white; border: none; border-radius: 4px; cursor: pointer;">
                            🔍 ค้นหา
                        </button>
                        <a href="orders.php" style="padding: 8px 16px; background: #95a5a6; color: white; text-decoration: none; border-radius: 4px;">
                            🔄 รีเซ็ต
                        </a>
                    </div>
                </form>
                
                <!-- แสดงตัวกรองปัจจุบัน -->
                <?php if (!empty($date_filter) || !empty($month_filter) || !empty($year_filter)): ?>
                    <div style="margin-top: 10px; padding: 8px; background: #e8f5e8; border-radius: 4px; font-size: 0.9em;">
                        <strong>🎯 กรองปัจจุบัน:</strong>
                        <?php if (!empty($date_filter)): ?>
                            วันที่ <?php echo date('d/m/Y', strtotime($date_filter)); ?>
                        <?php elseif (!empty($month_filter) || !empty($year_filter)): ?>
                            <?php if (!empty($month_filter)): ?>
                                เดือน<?php echo $months[$month_filter]; ?>
                            <?php endif; ?>
                            <?php if (!empty($year_filter)): ?>
                                ปี <?php echo $year_filter + 543; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if (!empty($status_filter)): ?>
                            | สถานะ: <?php echo htmlspecialchars($status_filter); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div style="overflow-x:auto;">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">ลำดับ</th>
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
                                <td colspan="8" style="text-align: center; padding: 2rem;">ไม่พบคำสั่งซื้อ</td>
                            </tr>
                        <?php else: ?>
                            <?php $index = 1; foreach ($orders as $order): ?>
                                <tr>
                                    <td style="text-align: center; font-weight: bold; color: #666;"><?php echo $index++; ?></td>
                                    <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                    <td>
                                        <?php
                                        // ดึงชื่อลูกค้าจาก JSON พร้อม error handling
                                        $customer_name = 'ไม่ระบุ';
                                        
                                        // ลองดึงจาก customer_name ใน view ก่อน
                                        if (!empty($order['customer_name'])) {
                                            $customer_name = $order['customer_name'];
                                        } else {
                                            // หากไม่มี ลอง decode จาก shipping_address
                                            if (!empty($order['shipping_address'])) {
                                                $shipping_address = json_decode($order['shipping_address'], true);
                                                if (json_last_error() === JSON_ERROR_NONE && is_array($shipping_address)) {
                                                    $first_name = $shipping_address['first_name'] ?? '';
                                                    $last_name = $shipping_address['last_name'] ?? '';
                                                    if (!empty($first_name) || !empty($last_name)) {
                                                        $customer_name = trim($first_name . ' ' . $last_name);
                                                    }
                                                }
                                            }
                                        }
                                        
                                        echo htmlspecialchars($customer_name);
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

    <script>
        // ฟังก์ชันเคลียร์ตัวกรองอื่นๆ เมื่อเลือกประเภทการกรองใหม่
        function clearOtherFilters(selectedType) {
            const dateInput = document.querySelector('input[name="date"]');
            const monthSelect = document.querySelector('select[name="month"]');
            const yearSelect = document.querySelector('select[name="year"]');
            
            if (selectedType !== 'date') {
                dateInput.value = '';
            }
            if (selectedType !== 'month') {
                monthSelect.value = '';
            }
            if (selectedType !== 'year') {
                yearSelect.value = '';
            }
            
            // หากเลือกเดือน ให้เลือกปีปัจจุบันอัตโนมัติ
            if (selectedType === 'month' && monthSelect.value !== '') {
                const currentYear = new Date().getFullYear();
                yearSelect.value = currentYear;
            }
        }
        
        // เพิ่มคำอธิบายให้ชัดเจน
        document.addEventListener('DOMContentLoaded', function() {
            // เพิ่ม tooltip หรือ hint
            const dateLabel = document.querySelector('label[for="date"]');
            if (dateLabel) {
                dateLabel.title = 'เลือกวันที่เฉพาะเจาะจง เช่น 15 มกราคม 2568';
            }
            
            const monthLabel = document.querySelector('select[name="month"]').previousElementSibling;
            if (monthLabel) {
                monthLabel.title = 'ดูคำสั่งซื้อทั้งเดือน (ร่วมกับปี)';
            }
            
            const yearLabel = document.querySelector('select[name="year"]').previousElementSibling;
            if (yearLabel) {
                yearLabel.title = 'ดูคำสั่งซื้อทั้งปี';
            }
        });
    </script>
</body>

</html>