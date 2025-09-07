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

// --- ส่วนของการดึงข้อมูล ---

// 1. รับค่าค้นหาจาก URL
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// 2. สร้าง SQL query
$sql = "SELECT * FROM users";
$params = [];

if (!empty($search_term)) {
    // เพิ่มเงื่อนไข WHERE ถ้ามีการค้นหา
    $sql .= " WHERE first_name LIKE :search OR last_name LIKE :search OR email LIKE :search";
    $params[':search'] = '%' . $search_term . '%';
}

// 3. เรียงลำดับจากสมาชิกใหม่ไปเก่าสุด
$sql .= " ORDER BY created_at DESC";

// 4. ดึงข้อมูลจากฐานข้อมูล
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูลลูกค้า: " . $e->getMessage());
}

// ฟังก์ชันสำหรับสร้าง Badge ของสถานะ
function getStatusBadge($status)
{
    $colors = [
        'active' => '#27ae60',
        'inactive' => '#7f8c8d',
        'banned' => '#e74c3c'
    ];
    $color = $colors[$status] ?? '#7f8c8d';
    $status_th = [
        'active' => 'ใช้งาน',
        'inactive' => 'ไม่ใช้งาน',
        'banned' => 'ถูกระงับ'
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
    <title>จัดการลูกค้า - ระบบหลังบ้าน</title>
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
            max-width: 1400px;
            margin: auto;
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

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .search-form {
            display: flex;
            gap: 0.5rem;
        }

        .search-form input {
            padding: 0.6rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .search-form button {
            padding: 0.6rem 1rem;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .customers-table {
            width: 100%;
            border-collapse: collapse;
        }

        .customers-table th,
        .customers-table td {
            padding: 0.8rem 1rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .customers-table th {
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
            <span>🌾 จัดการลูกค้า</span>
            <a href="index.php" style="color:white; text-decoration:none;">กลับหน้าแรก</a>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <div class="toolbar">
                <h2>รายชื่อลูกค้า</h2>
                <form method="GET" action="customers.php" class="search-form">
                    <input type="text" name="search" placeholder="ค้นหาชื่อ, นามสกุล, อีเมล..." value="<?php echo htmlspecialchars($search_term); ?>">
                    <button type="submit">ค้นหา</button>
                </form>
            </div>

            <div style="overflow-x:auto;">
                <table class="customers-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>อีเมล</th>
                            <th>เบอร์โทรศัพท์</th>
                            <th>วันที่สมัคร</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customers)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem;">
                                    <?php if (!empty($search_term)): ?>
                                        ไม่พบลูกค้าที่ตรงกับ "<?php echo htmlspecialchars($search_term); ?>"
                                    <?php else: ?>
                                        ยังไม่มีลูกค้าในระบบ
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><?php echo $customer['id']; ?></td>
                                    <td><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone'] ?? '-'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($customer['created_at'])); ?></td>
                                    <td><?php echo getStatusBadge($customer['status']); ?></td>
                                    <td>
                                        <a href="customer_detail.php?id=<?php echo $customer['id']; ?>" class="action-btn">ดู/แก้ไข</a>
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