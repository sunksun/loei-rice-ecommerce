<?php
// เริ่ม session
session_start();

// รวมไฟล์การตั้งค่า
require_once 'config/database.php';
require_once 'config/config.php';

try {
    // เชื่อมต่อฐานข้อมูล
    $host = 'localhost';
    $dbname = 'loei_rice_ecommerce';
    $username_db = 'root';
    $password_db = '';

    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username_db, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // รับพารามิเตอร์จาก URL
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $per_page = 12;
    $offset = ($page - 1) * $per_page;

    // สร้าง SQL query
    $where_conditions = ["p.status = 'active'"];
    $params = [];

    if (!empty($search)) {
        $where_conditions[] = "(p.name LIKE :search OR p.description LIKE :search OR p.short_description LIKE :search)";
        $params[':search'] = "%$search%";
    }

    if ($category_id > 0) {
        $where_conditions[] = "p.category_id = :category_id";
        $params[':category_id'] = $category_id;
    }

    $where_clause = implode(' AND ', $where_conditions);

    // กำหนด ORDER BY
    switch ($sort) {
        case 'price_low':
            $order_by = 'COALESCE(p.sale_price, p.price) ASC';
            break;
        case 'price_high':
            $order_by = 'COALESCE(p.sale_price, p.price) DESC';
            break;
        case 'name':
            $order_by = 'p.name ASC';
            break;
        case 'featured':
            $order_by = 'p.featured DESC, p.created_at DESC';
            break;
        default:
            $order_by = 'p.created_at DESC';
    }

    // นับจำนวนสินค้าทั้งหมด
    $count_sql = "SELECT COUNT(*) as total FROM products p WHERE $where_clause";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute($params);
    $total_products = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_products / $per_page);

    // ดึงข้อมูลสินค้า
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE $where_clause 
            ORDER BY $order_by 
            LIMIT $per_page OFFSET $offset";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ดึงหมวดหมู่ทั้งหมด
    $categories_stmt = $conn->query("
        SELECT c.*, COUNT(p.id) as product_count 
        FROM categories c 
        LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
        WHERE c.status = 'active' 
        GROUP BY c.id 
        ORDER BY c.name
    ");
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

    // ดึงข้อมูลหมวดหมู่ปัจจุบัน
    $current_category = null;
    if ($category_id > 0) {
        $cat_stmt = $conn->prepare("SELECT * FROM categories WHERE id = :id");
        $cat_stmt->bindParam(':id', $category_id);
        $cat_stmt->execute();
        $current_category = $cat_stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $products = [];
    $categories = [];
    $total_products = 0;
    $total_pages = 0;
    $current_category = null;
    // เพิ่มตัวแปรที่ขาดหายไป
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
}

// ฟังก์ชันสร้าง URL สำหรับ pagination และ sorting
function buildUrl($params = [])
{
    $current_params = $_GET;
    $merged_params = array_merge($current_params, $params);
    $merged_params = array_filter($merged_params); // ลบค่าว่าง
    return 'products.php?' . http_build_query($merged_params);
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_category ? htmlspecialchars($current_category['name']) : 'สินค้าทั้งหมด'; ?> - ข้าวพันธุ์พื้นเมืองเลย</title>
    <meta name="description" content="ช้อปปิ้งข้าวพันธุ์พื้นเมืองคุณภาพจากจังหวัดเลย ปลอดสารเคมี ราคาดี จัดส่งทั่วประเทศ">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #27ae60, #2d5016);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-size: 1.3rem;
            font-weight: 700;
            text-decoration: none;
            color: white;
        }

        .nav {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: #a8e6cf;
        }

        .cart-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .cart-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Breadcrumb */
        .breadcrumb {
            background: white;
            padding: 1rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .breadcrumb-list {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            font-size: 0.9rem;
        }

        .breadcrumb-item {
            color: #666;
        }

        .breadcrumb-item a {
            color: #27ae60;
            text-decoration: none;
        }

        .breadcrumb-item a:hover {
            text-decoration: underline;
        }

        /* Page Header */
        .page-header {
            background: white;
            padding: 2rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d5016;
            margin-bottom: 1rem;
        }

        .page-description {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 1.5rem;
        }

        /* Search and Filters */
        .filters-section {
            background: white;
            padding: 1.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .filters-container {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
            justify-content: space-between;
        }

        .search-box {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .search-input {
            padding: 0.8rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            width: 300px;
            font-size: 0.9rem;
        }

        .search-input:focus {
            outline: none;
            border-color: #27ae60;
        }

        .search-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .filters-right {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .sort-select,
        .category-select {
            padding: 0.8rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        /* Results Info */
        .results-info {
            padding: 1rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #666;
        }

        /* Sidebar and Main Content */
        .content-wrapper {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 2rem;
            padding: 2rem 0;
        }

        .sidebar {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            height: fit-content;
        }

        .sidebar-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #2d5016;
        }

        .category-list {
            list-style: none;
        }

        .category-item {
            margin-bottom: 0.8rem;
        }

        .category-link {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem;
            color: #666;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .category-link:hover,
        .category-link.active {
            background: #e8f5e8;
            color: #27ae60;
        }

        .category-count {
            background: #f8f9fa;
            color: #666;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.8rem;
        }

        /* Products Grid */
        .main-content {
            min-height: 500px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
            cursor: pointer;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .product-image {
            height: 220px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #666;
            position: relative;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #27ae60;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 10;
        }

        .product-badge.new {
            background: #e74c3c;
        }

        .product-badge.sale {
            background: #f39c12;
        }

        .product-content {
            padding: 1.5rem;
        }

        .product-category {
            color: #27ae60;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .product-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.8rem;
            color: #2d5016;
            line-height: 1.4;
        }

        .product-desc {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 1rem;
        }

        .price-current {
            font-size: 1.4rem;
            font-weight: 700;
            color: #27ae60;
        }

        .price-original {
            font-size: 1rem;
            color: #999;
            text-decoration: line-through;
        }

        .discount-percent {
            background: #e74c3c;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .product-actions {
            display: flex;
            gap: 0.8rem;
        }

        .btn-add-cart {
            flex: 1;
            background: #27ae60;
            color: white;
            border: none;
            padding: 0.8rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-add-cart:hover {
            background: #219a52;
        }

        .btn-view {
            background: #f8f9fa;
            color: #666;
            border: 2px solid #e9ecef;
            padding: 0.8rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-view:hover {
            background: #e9ecef;
            color: #333;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin: 3rem 0;
        }

        .page-btn {
            padding: 0.8rem 1rem;
            border: 2px solid #e9ecef;
            background: white;
            color: #666;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .page-btn:hover,
        .page-btn.active {
            background: #27ae60;
            color: white;
            border-color: #27ae60;
        }

        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #2d5016;
        }

        .empty-desc {
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 1rem;
            }

            .nav {
                gap: 1rem;
                flex-wrap: wrap;
                justify-content: center;
            }

            .page-title {
                font-size: 2rem;
            }

            .filters-container {
                flex-direction: column;
                align-items: stretch;
            }

            .search-input {
                width: 100%;
            }

            .content-wrapper {
                grid-template-columns: 1fr;
            }

            .sidebar {
                order: 2;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1.5rem;
            }

            .pagination {
                flex-wrap: wrap;
            }
        }

        @media (max-width: 480px) {
            .products-grid {
                grid-template-columns: 1fr;
            }

            .product-actions {
                flex-direction: column;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <a href="index.php" class="logo">
                <span>🌾</span>
                <span>ข้าวพื้นเมืองเลย</span>
            </a>

            <nav class="nav">
                <a href="index.php" class="nav-link">หน้าแรก</a>
                <a href="products.php" class="nav-link" style="color: #a8e6cf;">สินค้า</a>
                <a href="about.php" class="nav-link">เกี่ยวกับเรา</a>
                <a href="contact.php" class="nav-link">ติดต่อ</a>
                <button class="cart-btn" onclick="toggleCart()">
                    🛒 ตะกร้า <span id="cartCount">(0)</span>
                </button>
            </nav>
        </div>
    </header>

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <div class="container">
            <div class="breadcrumb-list">
                <span class="breadcrumb-item"><a href="index.php">หน้าแรก</a></span>
                <span class="breadcrumb-item">›</span>
                <span class="breadcrumb-item">สินค้า</span>
                <?php if ($current_category): ?>
                    <span class="breadcrumb-item">›</span>
                    <span class="breadcrumb-item"><?php echo htmlspecialchars($current_category['name']); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1 class="page-title">
                <?php if ($current_category): ?>
                    <?php echo htmlspecialchars($current_category['name']); ?>
                <?php elseif (!empty($search)): ?>
                    ผลการค้นหา: "<?php echo htmlspecialchars($search); ?>"
                <?php else: ?>
                    สินค้าทั้งหมด
                <?php endif; ?>
            </h1>

            <p class="page-description">
                <?php if ($current_category && !empty($current_category['description'])): ?>
                    <?php echo htmlspecialchars($current_category['description']); ?>
                <?php elseif (!empty($search)): ?>
                    พบสินค้า <?php echo number_format($total_products); ?> รายการ
                <?php else: ?>
                    ข้าวพันธุ์พื้นเมืองคุณภาพจากจังหวัดเลย ปลอดสารเคมี ราคาดี
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="filters-section">
        <div class="container">
            <div class="filters-container">
                <form method="GET" class="search-box">
                    <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                    <input type="text" name="search" class="search-input"
                        placeholder="ค้นหาสินค้า..."
                        value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="search-btn">🔍 ค้นหา</button>
                </form>

                <div class="filters-right">
                    <select class="category-select" onchange="changeCategory(this.value)">
                        <option value="0">หมวดหมู่ทั้งหมด</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"
                                <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?> (<?php echo $cat['product_count']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select class="sort-select" onchange="changeSort(this.value)">
                        <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>ใหม่ล่าสุด</option>
                        <option value="featured" <?php echo $sort == 'featured' ? 'selected' : ''; ?>>สินค้าแนะนำ</option>
                        <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>ราคาต่ำ → สูง</option>
                        <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>ราคาสูง → ต่ำ</option>
                        <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>ชื่อ A-Z</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Info -->
    <div class="container">
        <div class="results-info">
            <span>แสดง <?php echo number_format(count($products)); ?> จาก <?php echo number_format($total_products); ?> รายการ</span>
            <span>หน้า <?php echo $page; ?> จาก <?php echo $total_pages; ?></span>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="content-wrapper">
            <!-- Sidebar -->
            <aside class="sidebar">
                <h3 class="sidebar-title">หมวดหมู่สินค้า</h3>
                <ul class="category-list">
                    <li class="category-item">
                        <a href="<?php echo buildUrl(['category' => '', 'page' => '']); ?>"
                            class="category-link <?php echo $category_id == 0 ? 'active' : ''; ?>">
                            <span>สินค้าทั้งหมด</span>
                            <span class="category-count"><?php echo number_format($total_products); ?></span>
                        </a>
                    </li>
                    <?php foreach ($categories as $cat): ?>
                        <li class="category-item">
                            <a href="<?php echo buildUrl(['category' => $cat['id'], 'page' => '']); ?>"
                                class="category-link <?php echo $category_id == $cat['id'] ? 'active' : ''; ?>">
                                <span><?php echo htmlspecialchars($cat['name']); ?></span>
                                <span class="category-count"><?php echo number_format($cat['product_count']); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </aside>

            <!-- Products Grid -->
            <main class="main-content">
                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">🔍</div>
                        <h2 class="empty-title">ไม่พบสินค้า</h2>
                        <p class="empty-desc">
                            <?php if (!empty($search)): ?>
                                ไม่พบสินค้าที่ตรงกับคำค้นหา "<?php echo htmlspecialchars($search); ?>"
                            <?php else: ?>
                                ยังไม่มีสินค้าในหมวดหมู่นี้
                            <?php endif; ?>
                        </p>
                        <a href="products.php" class="btn-add-cart" style="text-decoration: none; display: inline-block;">
                            ดูสินค้าทั้งหมด
                        </a>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card" onclick="viewProduct(<?php echo $product['id']; ?>)">
                                <div class="product-image">
                                    <?php if (!empty($product['image_main'])): ?>
                                        <img src="uploads/products/<?php echo htmlspecialchars($product['image_main']); ?>"
                                            alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php else: ?>
                                        🌾
                                    <?php endif; ?>

                                    <?php if ($product['featured']): ?>
                                        <span class="product-badge">แนะนำ</span>
                                    <?php elseif ($product['is_new']): ?>
                                        <span class="product-badge new">ใหม่</span>
                                    <?php elseif (!empty($product['sale_price'])): ?>
                                        <span class="product-badge sale">ลดราคา</span>
                                    <?php endif; ?>
                                </div>

                                <div class="product-content">
                                    <div class="product-category"><?php echo htmlspecialchars($product['category_name'] ?? 'ทั่วไป'); ?></div>
                                    <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p class="product-desc"><?php echo htmlspecialchars($product['short_description'] ?? ''); ?></p>

                                    <div class="product-price">
                                        <?php if (!empty($product['sale_price'])): ?>
                                            <span class="price-current">฿<?php echo number_format($product['sale_price'], 2); ?></span>
                                            <span class="price-original">฿<?php echo number_format($product['price'], 2); ?></span>
                                            <?php
                                            $discount = (($product['price'] - $product['sale_price']) / $product['price']) * 100;
                                            ?>
                                            <span class="discount-percent">-<?php echo round($discount); ?>%</span>
                                        <?php else: ?>
                                            <span class="price-current">฿<?php echo number_format($product['price'], 2); ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="product-actions" onclick="event.stopPropagation()">
                                        <button class="btn-add-cart" onclick="addToCart(<?php echo $product['id']; ?>)">
                                            🛒 เพิ่มลงตะกร้า
                                        </button>
                                        <button class="btn-view" onclick="viewProduct(<?php echo $product['id']; ?>)">
                                            👁️ ดู
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="<?php echo buildUrl(['page' => $page - 1]); ?>" class="page-btn">‹ ก่อนหน้า</a>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);

                            if ($start_page > 1): ?>
                                <a href="<?php echo buildUrl(['page' => 1]); ?>" class="page-btn">1</a>
                                <?php if ($start_page > 2): ?>
                                    <span class="page-btn" style="border: none; background: none;">...</span>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <a href="<?php echo buildUrl(['page' => $i]); ?>"
                                    class="page-btn <?php echo $i == $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                    <span class="page-btn" style="border: none; background: none;">...</span>
                                <?php endif; ?>
                                <a href="<?php echo buildUrl(['page' => $total_pages]); ?>" class="page-btn"><?php echo $total_pages; ?></a>
                            <?php endif; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="<?php echo buildUrl(['page' => $page + 1]); ?>" class="page-btn">ถัดไป ›</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script>
        // การจัดการตะกร้าสินค้า
        let cart = JSON.parse(localStorage.getItem('cart')) || [];

        function updateCartCount() {
            const cartCount = document.getElementById('cartCount');
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            cartCount.textContent = `(${totalItems})`;
        }

        function addToCart(productId) {
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<span class="loading"></span> เพิ่มแล้ว';
            button.disabled = true;

            const existingItem = cart.find(item => item.id === productId);
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({
                    id: productId,
                    quantity: 1
                });
            }

            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartCount();

            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 2000);

            showNotification('เพิ่มสินค้าลงตะกร้าเรียบร้อยแล้ว! 🛒', 'success');
        }

        function viewProduct(productId) {
            window.location.href = `product-detail.php?id=${productId}`;
        }

        function toggleCart() {
            window.location.href = 'cart.php';
        }

        function changeCategory(categoryId) {
            const url = new URL(window.location.href);
            if (categoryId && categoryId !== '0') {
                url.searchParams.set('category', categoryId);
            } else {
                url.searchParams.delete('category');
            }
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }

        function changeSort(sortValue) {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', sortValue);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#27ae60' : '#3498db'};
                color: white;
                padding: 1rem 2rem;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                z-index: 9999;
                font-weight: 600;
                animation: slideInRight 0.3s ease;
            `;
            notification.textContent = message;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // CSS Animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);

        // เริ่มต้นเมื่อโหลดหน้า
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.querySelector('.search-input').focus();
            }
        });
    </script>
</body>

</html>