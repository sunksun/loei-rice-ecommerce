<?php
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// รวมไฟล์การตั้งค่า
require_once '../config/database.php';
require_once '../config/config.php';

$success_message = '';
$error_message = '';

try {
    // เชื่อมต่อฐานข้อมูล
    $host = 'localhost';
    $dbname = 'loei_rice_ecommerce';
    $username_db = 'root';
    $password_db = '';

    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username_db, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // การจัดการ CRUD
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action == 'add') {
            // เพิ่มหมวดหมู่ใหม่
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $status = $_POST['status'] ?? 'active';

            if (empty($name)) {
                $error_message = 'กรุณากรอกชื่อหมวดหมู่';
            } else {
                // ตรวจสอบชื่อซ้ำ
                $check_stmt = $conn->prepare("SELECT id FROM categories WHERE name = :name");
                $check_stmt->bindParam(':name', $name);
                $check_stmt->execute();

                if ($check_stmt->rowCount() > 0) {
                    $error_message = 'ชื่อหมวดหมู่นี้มีอยู่แล้ว';
                } else {
                    $stmt = $conn->prepare("INSERT INTO categories (name, description, status, created_at, updated_at) VALUES (:name, :description, :status, NOW(), NOW())");
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':status', $status);

                    if ($stmt->execute()) {
                        $success_message = 'เพิ่มหมวดหมู่เรียบร้อยแล้ว';
                    } else {
                        $error_message = 'เกิดข้อผิดพลาดในการเพิ่มหมวดหมู่';
                    }
                }
            }
        } elseif ($action == 'edit') {
            // แก้ไขหมวดหมู่
            $id = (int)$_POST['id'];
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $status = $_POST['status'] ?? 'active';

            if (empty($name)) {
                $error_message = 'กรุณากรอกชื่อหมวดหมู่';
            } else {
                // ตรวจสอบชื่อซ้ำ (ยกเว้น ID ปัจจุบัน)
                $check_stmt = $conn->prepare("SELECT id FROM categories WHERE name = :name AND id != :id");
                $check_stmt->bindParam(':name', $name);
                $check_stmt->bindParam(':id', $id);
                $check_stmt->execute();

                if ($check_stmt->rowCount() > 0) {
                    $error_message = 'ชื่อหมวดหมู่นี้มีอยู่แล้ว';
                } else {
                    $stmt = $conn->prepare("UPDATE categories SET name = :name, description = :description, status = :status, updated_at = NOW() WHERE id = :id");
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':status', $status);
                    $stmt->bindParam(':id', $id);

                    if ($stmt->execute()) {
                        $success_message = 'แก้ไขหมวดหมู่เรียบร้อยแล้ว';
                    } else {
                        $error_message = 'เกิดข้อผิดพลาดในการแก้ไขหมวดหมู่';
                    }
                }
            }
        } elseif ($action == 'toggle_status') {
            // เปลี่ยนสถานะหมวดหมู่
            $id = (int)$_POST['id'];
            $current_status = $_POST['current_status'];
            $new_status = $current_status == 'active' ? 'inactive' : 'active';

            $stmt = $conn->prepare("UPDATE categories SET status = :status, updated_at = NOW() WHERE id = :id");
            $stmt->bindParam(':status', $new_status);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                $status_text = $new_status == 'active' ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
                $success_message = "เปลี่ยนสถานะเป็น {$status_text} เรียบร้อยแล้ว";
            } else {
                $error_message = 'เกิดข้อผิดพลาดในการเปลี่ยนสถานะ';
            }
        } elseif ($action == 'delete') {
            // ลบหมวดหมู่
            $id = (int)$_POST['id'];

            // ตรวจสอบว่ามีสินค้าในหมวดหมู่นี้หรือไม่
            $check_products = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = :id");
            $check_products->bindParam(':id', $id);
            $check_products->execute();
            $product_count = $check_products->fetch()['count'];

            if ($product_count > 0) {
                $error_message = "ไม่สามารถลบหมวดหมู่ได้ เนื่องจากมีสินค้า {$product_count} รายการในหมวดหมู่นี้";
            } else {
                $stmt = $conn->prepare("DELETE FROM categories WHERE id = :id");
                $stmt->bindParam(':id', $id);

                if ($stmt->execute()) {
                    $success_message = 'ลบหมวดหมู่เรียบร้อยแล้ว';
                } else {
                    $error_message = 'เกิดข้อผิดพลาดในการลบหมวดหมู่';
                }
            }
        }
    }

    // ดึงข้อมูลหมวดหมู่ทั้งหมด
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';

    $sql = "SELECT c.*, 
            (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as product_count
            FROM categories c WHERE 1=1";

    $params = [];

    if (!empty($search)) {
        $sql .= " AND (c.name LIKE :search OR c.description LIKE :search)";
        $params[':search'] = "%$search%";
    }

    if (!empty($status_filter)) {
        $sql .= " AND c.status = :status";
        $params[':status'] = $status_filter;
    }

    $sql .= " ORDER BY c.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // สถิติหมวดหมู่
    $stats_stmt = $conn->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
    FROM categories");
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
    $stats = ['total' => 0, 'active' => 0, 'inactive' => 0];
    $error_message = 'เกิดข้อผิดพลาดในการโหลดข้อมูล: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการหมวดหมู่ - ระบบจัดการข้าวพันธุ์พื้นเมืองเลย</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #27ae60, #2d5016);
            color: white;
            padding: 1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .back-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 0.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.2rem;
            text-decoration: none;
            display: flex;
            align-items: center;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .header-title {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .user-info {
            font-size: 0.9rem;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
        }

        /* Stats */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-item {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-left: 4px solid #27ae60;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.3rem;
        }

        .stat-item.inactive {
            border-left-color: #95a5a6;
        }

        /* Alerts */
        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 1.5rem;
            padding: 1rem;
            font-size: 0.9rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Controls */
        .controls {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
        }

        .controls-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            justify-content: space-between;
        }

        .search-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            flex: 1;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .form-label {
            font-size: 0.8rem;
            color: #666;
            font-weight: 500;
        }

        .form-control {
            padding: 0.6rem;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 0.9rem;
            min-width: 120px;
        }

        .form-control:focus {
            outline: none;
            border-color: #27ae60;
        }

        .add-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: background 0.3s ease;
        }

        .add-btn:hover {
            background: #219a52;
        }

        /* Table */
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
        }

        .table td {
            font-size: 0.9rem;
        }

        .category-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.3rem;
        }

        .category-desc {
            font-size: 0.8rem;
            color: #666;
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .product-count {
            background: #e3f2fd;
            color: #1976d2;
            padding: 0.3rem 0.6rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: #3498db;
            color: white;
        }

        .btn-edit:hover {
            background: #2980b9;
        }

        .btn-warning {
            background: #f39c12;
            color: white;
        }

        .btn-warning:hover {
            background: #e67e22;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #219a52;
        }

        .btn-delete {
            background: #e74c3c;
            color: white;
        }

        .btn-delete:hover {
            background: #c0392b;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            animation: slideUp 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .modal-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        .close-btn:hover {
            color: #333;
        }

        .modal-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .modal-form .form-group {
            margin-bottom: 1rem;
        }

        .modal-form .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2c3e50;
            font-weight: 500;
        }

        .modal-form .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #27ae60;
            color: white;
        }

        .btn-primary:hover {
            background: #219a52;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 0.5rem;
                text-align: center;
            }

            .container {
                padding: 0.8rem;
            }

            .controls-row {
                flex-direction: column;
                align-items: stretch;
            }

            .search-filters {
                flex-direction: column;
            }

            .form-control {
                min-width: auto;
            }

            .table-container {
                overflow-x: auto;
            }

            .table {
                min-width: 600px;
            }

            .stats-bar {
                grid-template-columns: repeat(2, 1fr);
            }

            .modal-content {
                margin: 10% auto;
                width: 95%;
                padding: 1.5rem;
            }

            .modal-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .stats-bar {
                grid-template-columns: 1fr;
            }

            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="header-left">
                <a href="index.php" class="back-btn">←</a>
                <div>
                    <div class="header-title">📂 จัดการหมวดหมู่</div>
                </div>
            </div>
            <div class="user-info">
                👤 <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Statistics -->
        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-number"><?php echo number_format($stats['total']); ?></div>
                <div class="stat-label">หมวดหมู่ทั้งหมด</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo number_format($stats['active']); ?></div>
                <div class="stat-label">เปิดใช้งาน</div>
            </div>
            <div class="stat-item inactive">
                <div class="stat-number"><?php echo number_format($stats['inactive']); ?></div>
                <div class="stat-label">ปิดใช้งาน</div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                ✅ <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                ❌ <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Controls -->
        <div class="controls">
            <form method="GET" action="">
                <div class="controls-row">
                    <div class="search-filters">
                        <div class="form-group">
                            <label class="form-label">ค้นหา</label>
                            <input type="text" name="search" class="form-control"
                                placeholder="ชื่อหมวดหมู่, คำอธิบาย"
                                value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">สถานะ</label>
                            <select name="status" class="form-control">
                                <option value="">ทั้งหมด</option>
                                <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>เปิดใช้งาน</option>
                                <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>ปิดใช้งาน</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="form-control" style="background: #27ae60; color: white; border: none; cursor: pointer;">
                                🔍 ค้นหา
                            </button>
                        </div>
                    </div>

                    <button type="button" class="add-btn" onclick="openModal('add')">
                        ➕ เพิ่มหมวดหมู่ใหม่
                    </button>
                </div>
            </form>
        </div>

        <!-- Categories Table -->
        <?php if (empty($categories)): ?>
            <div class="empty-state">
                <div class="empty-icon">📂</div>
                <h3>ไม่พบหมวดหมู่</h3>
                <p>ยังไม่มีหมวดหมู่ในระบบ หรือไม่พบหมวดหมู่ที่ค้นหา</p>
                <button class="add-btn" style="margin-top: 1rem;" onclick="openModal('add')">เพิ่มหมวดหมู่แรก</button>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ชื่อหมวดหมู่</th>
                            <th>คำอธิบาย</th>
                            <th>จำนวนสินค้า</th>
                            <th>สถานะ</th>
                            <th>วันที่สร้าง</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr id="category-row-<?php echo $category['id']; ?>">
                                <td>
                                    <div class="category-name"><?php echo htmlspecialchars($category['name']); ?></div>
                                </td>
                                <td>
                                    <div class="category-desc"><?php echo htmlspecialchars($category['description'] ?? 'ไม่มีคำอธิบาย'); ?></div>
                                </td>
                                <td>
                                    <span class="product-count"><?php echo number_format($category['product_count']); ?> รายการ</span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $category['status']; ?>">
                                        <?php echo $category['status'] == 'active' ? 'เปิดใช้งาน' : 'ปิดใช้งาน'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($category['created_at'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <button onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)" class="action-btn btn-edit" title="แก้ไขหมวดหมู่">
                                            ✏️ แก้ไข
                                        </button>
                                        <button onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>', <?php echo $category['product_count']; ?>)"
                                            class="action-btn btn-delete"
                                            title="<?php echo $category['product_count'] > 0 ? 'ไม่สามารถลบได้เนื่องจากมีสินค้าในหมวดหมู่' : 'ลบหมวดหมู่'; ?>"
                                            <?php echo $category['product_count'] > 0 ? 'style="opacity: 0.6; cursor: not-allowed;"' : ''; ?>>
                                            🗑️ ลบ
                                        </button>
                                        <button onclick="toggleStatus(<?php echo $category['id']; ?>, '<?php echo $category['status']; ?>', '<?php echo htmlspecialchars($category['name']); ?>')"
                                            class="action-btn <?php echo $category['status'] == 'active' ? 'btn-warning' : 'btn-success'; ?>"
                                            title="<?php echo $category['status'] == 'active' ? 'ปิดใช้งาน' : 'เปิดใช้งาน'; ?>">
                                            <?php echo $category['status'] == 'active' ? '⏸️ ปิด' : '▶️ เปิด'; ?>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal สำหรับเพิ่ม/แก้ไขหมวดหมู่ -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">เพิ่มหมวดหมู่ใหม่</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>

            <form id="categoryForm" method="POST" class="modal-form">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="categoryId" value="">

                <div class="form-group">
                    <label for="categoryName" class="form-label">ชื่อหมวดหมู่ <span style="color: #e74c3c;">*</span></label>
                    <input type="text" id="categoryName" name="name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="categoryDescription" class="form-label">คำอธิบาย</label>
                    <textarea id="categoryDescription" name="description" class="form-control" placeholder="คำอธิบายเกี่ยวกับหมวดหมู่"></textarea>
                </div>

                <div class="form-group">
                    <label for="categoryStatus" class="form-label">สถานะ</label>
                    <select id="categoryStatus" name="status" class="form-control">
                        <option value="active">เปิดใช้งาน</option>
                        <option value="inactive">ปิดใช้งาน</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // เปิด Modal
        function openModal(action, category = null) {
            const modal = document.getElementById('categoryModal');
            const title = document.getElementById('modalTitle');
            const form = document.getElementById('categoryForm');
            const submitBtn = document.getElementById('submitBtn');

            if (action === 'add') {
                title.textContent = 'เพิ่มหมวดหมู่ใหม่';
                submitBtn.textContent = 'เพิ่มหมวดหมู่';
                form.reset();
                document.getElementById('formAction').value = 'add';
                document.getElementById('categoryId').value = '';

                // ตั้งค่าเริ่มต้น
                document.getElementById('categoryStatus').value = 'active';
            }

            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';

            // Auto focus ที่ช่องชื่อหมวดหมู่
            setTimeout(() => {
                document.getElementById('categoryName').focus();
            }, 100);
        }

        // ปิด Modal
        function closeModal() {
            const modal = document.getElementById('categoryModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // แก้ไขหมวดหมู่
        function editCategory(category) {
            const modal = document.getElementById('categoryModal');
            const title = document.getElementById('modalTitle');
            const submitBtn = document.getElementById('submitBtn');

            title.textContent = 'แก้ไขหมวดหมู่';
            submitBtn.textContent = 'บันทึกการเปลี่ยนแปลง';

            document.getElementById('formAction').value = 'edit';
            document.getElementById('categoryId').value = category.id;
            document.getElementById('categoryName').value = category.name;
            document.getElementById('categoryDescription').value = category.description || '';
            document.getElementById('categoryStatus').value = category.status;

            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';

            // Auto focus และ select text
            setTimeout(() => {
                const nameField = document.getElementById('categoryName');
                nameField.focus();
                nameField.select();
            }, 100);
        }

        // เปลี่ยนสถานะหมวดหมู่
        function toggleStatus(id, currentStatus, name) {
            const action = currentStatus === 'active' ? 'ปิดใช้งาน' : 'เปิดใช้งาน';
            const message = `คุณต้องการ${action}หมวดหมู่ "${name}" หรือไม่?`;

            if (confirm(message)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'toggle_status';

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = id;

                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'current_status';
                statusInput.value = currentStatus;

                form.appendChild(actionInput);
                form.appendChild(idInput);
                form.appendChild(statusInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // ลบหมวดหมู่
        function deleteCategory(id, name, productCount) {
            if (productCount > 0) {
                alert(`ไม่สามารถลบหมวดหมู่ "${name}" ได้\n\nเนื่องจากมีสินค้า ${productCount} รายการในหมวดหมู่นี้\nกรุณาย้ายสินค้าไปหมวดหมู่อื่นก่อนลบ`);
                return;
            }

            const confirmMessage = `⚠️ คำเตือน: การลบหมวดหมู่\n\n` +
                `หมวดหมู่: "${name}"\n` +
                `จำนวนสินค้า: ${productCount} รายการ\n\n` +
                `การดำเนินการนี้จะลบหมวดหมู่อย่างถาวร\n` +
                `และไม่สามารถย้อนกลับได้\n\n` +
                `คุณแน่ใจหรือไม่ที่จะลบ?`;

            if (confirm(confirmMessage)) {
                const row = document.getElementById(`category-row-${id}`);
                if (row) {
                    row.style.opacity = '0.5';
                    row.style.pointerEvents = 'none';
                }

                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = id;

                form.appendChild(actionInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // ปิด modal เมื่อคลิกนอกพื้นที่
        window.onclick = function(event) {
            const modal = document.getElementById('categoryModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // ปิด modal เมื่อกด ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });

        // Auto-submit form เมื่อเปลี่ยน filter
        document.querySelectorAll('select[name="status"]').forEach(select => {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });

        // Touch feedback สำหรับมือถือ
        document.querySelectorAll('.action-btn, .add-btn').forEach(item => {
            item.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.98)';
            });

            item.addEventListener('touchend', function() {
                this.style.transform = '';
            });
        });

        // Form validation
        document.getElementById('categoryForm').addEventListener('submit', function(e) {
            const name = document.getElementById('categoryName').value.trim();

            if (!name) {
                alert('กรุณากรอกชื่อหมวดหมู่');
                e.preventDefault();
                return;
            }

            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'กำลังบันทึก...';
            submitBtn.disabled = true;

            setTimeout(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }, 3000);
        });

        // Character counter สำหรับคำอธิบาย
        const descTextarea = document.getElementById('categoryDescription');
        const counter = document.createElement('div');
        counter.style.cssText = 'font-size: 0.8rem; color: #666; text-align: right; margin-top: 0.3rem;';
        descTextarea.parentNode.appendChild(counter);

        function updateCounter() {
            const length = descTextarea.value.length;
            counter.textContent = `${length}/500 ตัวอักษร`;
            counter.style.color = length > 500 ? '#e74c3c' : '#666';
        }

        descTextarea.addEventListener('input', updateCounter);
        descTextarea.setAttribute('maxlength', '500');
        updateCounter();

        // ค้นหาแบบ real-time
        let searchTimeout;
        document.querySelector('input[name="search"]').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 1000);
        });
    </script>