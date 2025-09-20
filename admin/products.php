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

// ประกาศตัวแปรเริ่มต้น
$products = [];
$categories = [];
$stats = ['total' => 0, 'active' => 0, 'inactive' => 0, 'low_stock' => 0];
$error_message = null;

try {
    // เชื่อมต่อฐานข้อมูล - แก้ไขชื่อฐานข้อมูลให้ตรงกับของคุณ
    $host = 'localhost';
    $dbname = 'loei_rice_ecommerce';  // เปลี่ยนเป็นชื่อฐานข้อมูลจริง
    $username_db = 'root';
    $password_db = '';

    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username_db, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // รับค่าจากการค้นหาและกรอง
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $category_filter = isset($_GET['category']) ? $_GET['category'] : '';
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    $view_mode = 'table'; // ใช้แค่ตารางเท่านั้น

    // สร้างคำสั่ง SQL พื้นฐาน
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE 1=1";

    $params = [];

    // เพิ่มเงื่อนไขการค้นหา
    if (!empty($search)) {
        $sql .= " AND (p.name LIKE :search OR p.description LIKE :search)";
        $params[':search'] = "%$search%";
    }

    if (!empty($category_filter)) {
        $sql .= " AND p.category_id = :category";
        $params[':category'] = $category_filter;
    }

    if (!empty($status_filter)) {
        $sql .= " AND p.status = :status";
        $params[':status'] = $status_filter;
    }

    $sql .= " ORDER BY p.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ดึงหมวดหมู่สำหรับ dropdown
    $categories_stmt = $conn->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

    // สถิติสินค้า
    $stats_stmt = $conn->query("SELECT 
        COUNT(*) as total,
        COALESCE(SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END), 0) as active,
        COALESCE(SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END), 0) as inactive,
        COALESCE(SUM(CASE WHEN stock_quantity <= min_stock_level THEN 1 ELSE 0 END), 0) as low_stock
    FROM products");
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // ป้องกัน null values
    $stats = [
        'total' => (int)($stats['total'] ?? 0),
        'active' => (int)($stats['active'] ?? 0), 
        'inactive' => (int)($stats['inactive'] ?? 0),
        'low_stock' => (int)($stats['low_stock'] ?? 0)
    ];
} catch (Exception $e) {
    $products = [];
    $categories = [];
    $stats = ['total' => 0, 'active' => 0, 'inactive' => 0, 'low_stock' => 0];
    $error_message = 'เกิดข้อผิดพลาดในการโหลดข้อมูล: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสินค้า - ระบบจัดการข้าวพันธุ์พื้นเมืองเลย</title>

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

        .stat-item.low-stock {
            border-left-color: #e74c3c;
        }

        .stat-item.inactive {
            border-left-color: #95a5a6;
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

        .view-toggle {
            display: flex;
            gap: 0.5rem;
        }

        .view-btn {
            padding: 0.6rem;
            border: 2px solid #e9ecef;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .view-btn.active {
            background: #27ae60;
            color: white;
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

        /* Table View */
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

        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }

        .product-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.3rem;
        }

        .product-sku {
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

        .stock-warning {
            color: #e74c3c;
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

        .btn-delete {
            background: #e74c3c;
            color: white;
        }

        .btn-delete:hover {
            background: #c0392b;
        }

        /* Grid View */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .card-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .card-content {
            padding: 1.2rem;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .card-sku {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 0.8rem;
        }

        .card-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: #27ae60;
            margin-bottom: 0.8rem;
        }

        .card-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .card-stock {
            color: #666;
        }

        .card-actions {
            display: flex;
            gap: 0.5rem;
        }

        .card-actions .action-btn {
            flex: 1;
            text-align: center;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 0.5rem;
                text-align: center;
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

            .view-toggle {
                justify-content: center;
            }

            .table-container {
                overflow-x: auto;
            }

            .table {
                min-width: 600px;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }

            .stats-bar {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0.8rem;
            }

            .stats-bar {
                grid-template-columns: 1fr;
            }

            .search-filters {
                gap: 0.5rem;
            }

            .actions {
                flex-direction: column;
            }
        }

        /* Loading and Empty States */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #27ae60;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
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
                    <div class="header-title">🌾 จัดการสินค้า</div>
                </div>
            </div>
            <div class="user-info">
                👤 <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Statistics -->
        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-number"><?php echo number_format($stats['total']); ?></div>
                <div class="stat-label">สินค้าทั้งหมด</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo number_format($stats['active']); ?></div>
                <div class="stat-label">เปิดขาย</div>
            </div>
            <div class="stat-item inactive">
                <div class="stat-number"><?php echo number_format($stats['inactive']); ?></div>
                <div class="stat-label">ปิดขาย</div>
            </div>
            <div class="stat-item low-stock">
                <div class="stat-number"><?php echo number_format($stats['low_stock']); ?></div>
                <div class="stat-label">สต็อกต่ำ</div>
            </div>
        </div>

        <!-- Controls -->
        <div class="controls">
            <form method="GET" action="">
                <div class="controls-row">
                    <div class="search-filters">
                        <div class="form-group">
                            <label class="form-label">ค้นหา</label>
                            <input type="text" name="search" class="form-control"
                                placeholder="ชื่อสินค้า, คำอธิบาย"
                                value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">หมวดหมู่</label>
                            <select name="category" class="form-control">
                                <option value="">ทั้งหมด</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"
                                        <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">สถานะ</label>
                            <select name="status" class="form-control">
                                <option value="">ทั้งหมด</option>
                                <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>เปิดขาย</option>
                                <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>ปิดขาย</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="form-control" style="background: #27ae60; color: white; border: none; cursor: pointer;">
                                🔍 ค้นหา
                            </button>
                        </div>
                    </div>

                    <input type="hidden" name="view" value="<?php echo $view_mode; ?>">
                </div>
            </form>

            <div style="margin-top: 1rem; display: flex; justify-content: space-between; align-items: center;">
                <div class="view-toggle">
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'table'])); ?>"
                        class="view-btn <?php echo $view_mode == 'table' ? 'active' : ''; ?>">📋 ตาราง</a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'grid'])); ?>"
                        class="view-btn <?php echo $view_mode == 'grid' ? 'active' : ''; ?>">🔲 กริด</a>
                </div>

                <a href="product-form.php" class="add-btn">
                    ➕ เพิ่มสินค้าใหม่
                </a>
            </div>
        </div>

        <!-- Products Display -->
        <?php if (isset($error_message)): ?>
            <div class="empty-state">
                <div class="empty-icon">❌</div>
                <h3>เกิดข้อผิดพลาด</h3>
                <p><?php echo htmlspecialchars($error_message); ?></p>
            </div>
        <?php elseif (empty($products)): ?>
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <h3>ไม่พบสินค้า</h3>
                <p>ยังไม่มีสินค้าในระบบ หรือไม่พบสินค้าที่ค้นหา</p>
                <a href="product-form.php" class="add-btn" style="margin-top: 1rem;">เพิ่มสินค้าแรก</a>
            </div>
        <?php elseif ($view_mode == 'table'): ?>
            <!-- Table View -->
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">ลำดับ</th>
                            <th>รูปภาพ</th>
                            <th>ข้อมูลสินค้า</th>
                            <th>หมวดหมู่</th>
                            <th>ราคา</th>
                            <th>สต็อก</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $index = 1; foreach ($products as $product): ?>
                            <tr>
                                <td style="text-align: center; font-weight: bold; color: #666;"><?php echo $index++; ?></td>
                                <td>
                                    <img src="<?php echo !empty($product['image_main']) ? '../uploads/products/' . $product['image_main'] : '../assets/images/no-image.jpg'; ?>"
                                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                                        class="product-image">
                                </td>
                                <td>
                                    <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                    <div class="product-sku">ID: <?php echo $product['id']; ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'ไม่ระบุ'); ?></td>
                                <td>฿<?php echo number_format($product['price'], 2); ?></td>
                                <td>
                                    <span class="<?php echo $product['stock_quantity'] <= $product['min_stock_level'] ? 'stock-warning' : ''; ?>">
                                        <?php echo number_format($product['stock_quantity']); ?> <?php echo htmlspecialchars($product['unit'] ?? 'หน่วย'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $product['status']; ?>">
                                        <?php echo $product['status'] == 'active' ? 'เปิดขาย' : 'ปิดขาย'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="product-form.php?id=<?php echo $product['id']; ?>" class="action-btn btn-edit">แก้ไข</a>
                                        <button onclick="deleteProduct(<?php echo $product['id']; ?>)" class="action-btn btn-delete">ลบ</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <!-- Grid View -->
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <img src="<?php echo !empty($product['image_main']) ? '../uploads/products/' . $product['image_main'] : '../assets/images/no-image.jpg'; ?>"
                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                            class="card-image">
                        <div class="card-content">
                            <h3 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <div class="card-sku">ID: <?php echo $product['id']; ?></div>
                            <div class="card-price">฿<?php echo number_format($product['price'], 2); ?></div>
                            <div class="card-meta">
                                <div class="card-stock <?php echo $product['stock_quantity'] <= $product['min_stock_level'] ? 'stock-warning' : ''; ?>">
                                    คงเหลือ: <?php echo number_format($product['stock_quantity']); ?> <?php echo htmlspecialchars($product['unit'] ?? 'หน่วย'); ?>
                                </div>
                                <span class="status-badge status-<?php echo $product['status']; ?>">
                                    <?php echo $product['status'] == 'active' ? 'เปิดขาย' : 'ปิดขาย'; ?>
                                </span>
                            </div>
                            <div class="card-actions">
                                <a href="product-form.php?id=<?php echo $product['id']; ?>" class="action-btn btn-edit">แก้ไข</a>
                                <button onclick="deleteProduct(<?php echo $product['id']; ?>)" class="action-btn btn-delete">ลบ</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // ฟังก์ชันลบสินค้า
        function deleteProduct(productId) {
            if (confirm('คุณแน่ใจหรือไม่ที่จะลบสินค้านี้?\nการดำเนินการนี้ไม่สามารถย้อนกลับได้')) {
                fetch('product-delete.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id: productId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('ลบสินค้าเรียบร้อยแล้ว');
                            location.reload();
                        } else {
                            alert('เกิดข้อผิดพลาด: ' + data.message);
                        }
                    })
                    .catch(error => {
                        alert('เกิดข้อผิดพลาดในการลบสินค้า');
                        console.error('Error:', error);
                    });
            }
        }

        // Auto-submit form เมื่อเปลี่ยน filter
        document.querySelectorAll('select[name="category"], select[name="status"]').forEach(select => {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });

        // Touch feedback สำหรับมือถือ
        document.querySelectorAll('.product-card, .action-btn').forEach(item => {
            item.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.98)';
            });

            item.addEventListener('touchend', function() {
                this.style.transform = '';
            });
        });
    </script>
</body>

</html>