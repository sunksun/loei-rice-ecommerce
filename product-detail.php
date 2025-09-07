<?php
session_start();

// เชื่อมต่อฐานข้อมูล (ปรับปรุงการเชื่อมต่อตามการตั้งค่าของคุณ)
try {
    $pdo = new PDO("mysql:host=localhost;dbname=loei_rice_ecommerce", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("set names utf8");
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// รับ ID สินค้าจาก URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// ดึงข้อมูลสินค้า
try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ? AND p.status = 'active'
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header("Location: products.php");
        exit;
    }

    // อัพเดทจำนวนการดู
    $stmt = $pdo->prepare("UPDATE products SET view_count = view_count + 1 WHERE id = ?");
    $stmt->execute([$product_id]);
} catch (PDOException $e) {
    header("Location: products.php");
    exit;
}

// ดึงสินค้าที่เกี่ยวข้อง
try {
    $stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE category_id = ? AND id != ? AND status = 'active' 
        ORDER BY featured DESC, rating_average DESC 
        LIMIT 4
    ");
    $stmt->execute([$product['category_id'], $product_id]);
    $related_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $related_products = [];
}

// ฟังก์ชันสำหรับแสดงราคา
function formatPrice($price)
{
    return number_format($price, 0) . ' บาท';
}

// ฟังก์ชันสำหรับแสดงเรตติ้ง
function displayRating($rating)
{
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '⭐';
        } else {
            $stars .= '☆';
        }
    }
    return $stars;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['meta_title'] ?: $product['name']); ?> - ข้าวพันธุ์พื้นเมืองเลย</title>
    <meta name="description" content="<?php echo htmlspecialchars($product['meta_description'] ?: $product['short_description']); ?>">

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

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
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

        /* Product Detail */
        .product-detail {
            background: white;
            margin: 2rem 0;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .product-main {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            padding: 3rem;
        }

        /* Product Images */
        .product-images {
            position: relative;
        }

        .main-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            cursor: zoom-in;
        }

        .image-gallery {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .thumb-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .thumb-image:hover,
        .thumb-image.active {
            border-color: #27ae60;
            transform: scale(1.05);
        }

        .product-badges {
            position: absolute;
            top: 1rem;
            left: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
        }

        .badge-new {
            background: #e74c3c;
        }

        .badge-featured {
            background: #f39c12;
        }

        .badge-sale {
            background: #e74c3c;
        }

        /* Product Info */
        .product-info {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .product-title {
            font-size: 2rem;
            font-weight: 700;
            color: #2d5016;
            margin-bottom: 0.5rem;
        }

        .product-category {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .product-rating {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .rating-stars {
            font-size: 1.2rem;
        }

        .rating-text {
            color: #666;
            font-size: 0.9rem;
        }

        .product-price {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .current-price {
            font-size: 2rem;
            font-weight: 700;
            color: #27ae60;
        }

        .original-price {
            font-size: 1.2rem;
            color: #999;
            text-decoration: line-through;
        }

        .discount-percent {
            background: #e74c3c;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .product-summary {
            color: #666;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .product-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .meta-label {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
        }

        .meta-value {
            font-weight: 500;
            color: #2d5016;
        }

        /* Add to Cart */
        .add-to-cart-section {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            background: #f8f9fa;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .quantity-label {
            font-weight: 600;
            color: #2d5016;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            overflow: hidden;
        }

        .qty-btn {
            background: #f8f9fa;
            border: none;
            padding: 0.5rem 1rem;
            cursor: pointer;
            font-size: 1.2rem;
            font-weight: 600;
            transition: background 0.3s ease;
        }

        .qty-btn:hover {
            background: #e9ecef;
        }

        .qty-input {
            border: none;
            padding: 0.5rem 1rem;
            text-align: center;
            font-weight: 600;
            background: white;
            min-width: 60px;
        }

        .stock-info {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .stock-available {
            color: #27ae60;
            font-weight: 600;
        }

        .stock-low {
            color: #f39c12;
            font-weight: 600;
        }

        .add-to-cart-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-bottom: 1rem;
        }

        .add-to-cart-btn:hover {
            background: #219a52;
            transform: translateY(-2px);
        }

        .add-to-cart-btn:disabled {
            background: #ddd;
            cursor: not-allowed;
            transform: none;
        }

        .buy-now-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .buy-now-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        /* Product Details Tabs */
        .product-details {
            margin-top: 3rem;
        }

        .tabs-nav {
            display: flex;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 2rem;
        }

        .tab-btn {
            background: none;
            border: none;
            padding: 1rem 2rem;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .tab-btn.active {
            color: #27ae60;
            border-bottom-color: #27ae60;
        }

        .tab-content {
            display: none;
            padding: 2rem 0;
            line-height: 1.8;
        }

        .tab-content.active {
            display: block;
        }

        .features-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #27ae60;
        }

        .feature-icon {
            color: #27ae60;
            font-size: 1.2rem;
        }

        .nutrition-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
        }

        .nutrition-table th,
        .nutrition-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .nutrition-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2d5016;
        }

        /* Related Products */
        .related-products {
            margin: 3rem 0;
            padding: 3rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2d5016;
            margin-bottom: 2rem;
            text-align: center;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .product-card-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .product-card-content {
            padding: 1.5rem;
        }

        .product-card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d5016;
            margin-bottom: 0.5rem;
        }

        .product-card-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: #27ae60;
            margin-bottom: 1rem;
        }

        .product-card-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .product-card-btn:hover {
            background: #219a52;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 1rem;
            }

            .nav {
                display: none;
                width: 100%;
                flex-direction: column;
                gap: 1rem;
                margin-top: 1rem;
            }

            .nav.show {
                display: flex;
            }

            .mobile-menu-btn {
                display: block;
                position: absolute;
                right: 1rem;
                top: 50%;
                transform: translateY(-50%);
            }

            .product-main {
                grid-template-columns: 1fr;
                gap: 2rem;
                padding: 2rem;
            }

            .product-title {
                font-size: 1.5rem;
            }

            .current-price {
                font-size: 1.5rem;
            }

            .tabs-nav {
                flex-wrap: wrap;
            }

            .tab-btn {
                padding: 0.8rem 1rem;
                font-size: 0.9rem;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1rem;
            }
        }

        /* Animations */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Loading */
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

        /* Image Zoom */
        .image-zoom {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            cursor: zoom-out;
        }

        .zoom-image {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
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

            <nav class="nav" id="navMenu">
                <a href="index.php" class="nav-link">หน้าแรก</a>
                <a href="products.php" class="nav-link">สินค้า</a>
                <a href="about.php" class="nav-link">เกี่ยวกับเรา</a>
                <a href="contact.php" class="nav-link">ติดต่อ</a>
                <button class="cart-btn" onclick="toggleCart()">
                    🛒 ตะกร้า <span id="cartCount">(0)</span>
                </button>
            </nav>

            <button class="mobile-menu-btn" onclick="toggleMobileMenu()">☰</button>
        </div>
    </header>

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <div class="container">
            <div class="breadcrumb-list">
                <span class="breadcrumb-item"><a href="index.php">หน้าแรก</a></span>
                <span class="breadcrumb-item">›</span>
                <span class="breadcrumb-item"><a href="products.php">สินค้า</a></span>
                <span class="breadcrumb-item">›</span>
                <span class="breadcrumb-item"><?php echo htmlspecialchars($product['category_name'] ?: 'สินค้า'); ?></span>
                <span class="breadcrumb-item">›</span>
                <span class="breadcrumb-item"><?php echo htmlspecialchars($product['name']); ?></span>
            </div>
        </div>
    </div>

    <!-- Product Detail -->
    <div class="container">
        <div class="product-detail fade-in">
            <div class="product-main">
                <!-- Product Images -->
                <div class="product-images">
                    <div class="product-badges">
                        <?php if ($product['is_new']): ?>
                            <span class="badge badge-new">ใหม่</span>
                        <?php endif; ?>
                        <?php if ($product['featured']): ?>
                            <span class="badge badge-featured">แนะนำ</span>
                        <?php endif; ?>
                        <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                            <?php $discount = round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>
                            <span class="badge badge-sale">-<?php echo $discount; ?>%</span>
                        <?php endif; ?>
                    </div>

                    <img src="<?php echo $product['image_main'] ? 'uploads/products/' . $product['image_main'] : 'https://via.placeholder.com/600x400/27ae60/ffffff?text=' . urlencode($product['name']); ?>"
                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                        class="main-image"
                        id="mainImage"
                        onclick="zoomImage(this.src)">

                    <?php if ($product['image_gallery']): ?>
                        <div class="image-gallery">
                            <?php
                            $gallery = json_decode($product['image_gallery'], true);
                            if (is_array($gallery)) {
                                foreach ($gallery as $index => $image) {
                                    echo '<img src="uploads/products/' . $image . '" 
                                              alt="' . htmlspecialchars($product['name']) . '" 
                                              class="thumb-image" 
                                              onclick="changeMainImage(this.src)">';
                                }
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Product Info -->
                <div class="product-info">
                    <div class="product-category">
                        📦 หมวดหมู่: <?php echo htmlspecialchars($product['category_name'] ?: 'ทั่วไป'); ?>
                    </div>

                    <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>

                    <?php if ($product['rating_average'] > 0): ?>
                        <div class="product-rating">
                            <span class="rating-stars"><?php echo displayRating($product['rating_average']); ?></span>
                            <span class="rating-text">
                                <?php echo number_format($product['rating_average'], 1); ?>
                                (<?php echo $product['rating_count']; ?> รีวิว)
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="product-price">
                        <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                            <span class="current-price"><?php echo formatPrice($product['sale_price']); ?></span>
                            <span class="original-price"><?php echo formatPrice($product['price']); ?></span>
                            <?php $discount = round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>
                            <span class="discount-percent">ประหยัด <?php echo $discount; ?>%</span>
                        <?php else: ?>
                            <span class="current-price"><?php echo formatPrice($product['price']); ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($product['short_description']): ?>
                        <div class="product-summary">
                            <?php echo nl2br(htmlspecialchars($product['short_description'])); ?>
                        </div>
                    <?php endif; ?>

                    <div class="product-meta">
                        <?php if ($product['weight']): ?>
                            <div class="meta-item">
                                <span class="meta-label">น้ำหนัก</span>
                                <span class="meta-value"><?php echo $product['weight'] . ' ' . $product['unit']; ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($product['origin']): ?>
                            <div class="meta-item">
                                <span class="meta-label">แหล่งที่มา</span>
                                <span class="meta-value"><?php echo htmlspecialchars($product['origin']); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($product['harvest_season']): ?>
                            <div class="meta-item">
                                <span class="meta-label">ฤดูเก็บเกี่ยว</span>
                                <span class="meta-value"><?php echo htmlspecialchars($product['harvest_season']); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($product['certification']): ?>
                            <div class="meta-item">
                                <span class="meta-label">การรับรอง</span>
                                <span class="meta-value"><?php echo htmlspecialchars($product['certification']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Add to Cart Section -->
                    <div class="add-to-cart-section">
                        <div class="quantity-selector">
                            <span class="quantity-label">จำนวน:</span>
                            <div class="quantity-controls">
                                <button type="button" class="qty-btn" onclick="changeQuantity(-1)">-</button>
                                <input type="number" class="qty-input" id="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                                <button type="button" class="qty-btn" onclick="changeQuantity(1)">+</button>
                            </div>
                        </div>

                        <div class="stock-info">
                            <?php if ($product['stock_quantity'] > 10): ?>
                                <span class="stock-available">✅ มีสินค้าในสต็อก (<?php echo $product['stock_quantity']; ?> ชิ้น)</span>
                            <?php elseif ($product['stock_quantity'] > 0): ?>
                                <span class="stock-low">⚠️ เหลือสินค้าอีก <?php echo $product['stock_quantity']; ?> ชิ้น</span>
                            <?php else: ?>
                                <span style="color: #e74c3c;">❌ สินค้าหมด</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($product['stock_quantity'] > 0): ?>
                            <button type="button" class="add-to-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>)">
                                🛒 เพิ่มลงตะกร้า
                            </button>
                            <button type="button" class="buy-now-btn" onclick="buyNow(<?php echo $product['id']; ?>)">
                                ⚡ สั่งซื้อทันที
                            </button>
                        <?php else: ?>
                            <button type="button" class="add-to-cart-btn" disabled>
                                สินค้าหมด
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Product Details Tabs -->
            <div class="product-details">
                <div class="tabs-nav">
                    <button class="tab-btn active" onclick="showTab('description')">📝 รายละเอียด</button>
                    <?php if ($product['features']): ?>
                        <button class="tab-btn" onclick="showTab('features')">⭐ คุณสมบัติเด่น</button>
                    <?php endif; ?>
                    <?php if ($product['benefits']): ?>
                        <button class="tab-btn" onclick="showTab('benefits')">💚 คุณประโยชน์</button>
                    <?php endif; ?>
                    <?php if ($product['usage_instructions']): ?>
                        <button class="tab-btn" onclick="showTab('usage')">📋 วิธีใช้</button>
                    <?php endif; ?>
                    <?php if ($product['storage_instructions']): ?>
                        <button class="tab-btn" onclick="showTab('storage')">🏠 การเก็บรักษา</button>
                    <?php endif; ?>
                </div>

                <div id="description" class="tab-content active">
                    <h3>📝 รายละเอียดสินค้า</h3>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>

                    <?php if ($product['ingredients']): ?>
                        <h4 style="margin-top: 2rem; margin-bottom: 1rem;">🌿 ส่วนผสม/วัตถุดิบ</h4>
                        <p><?php echo nl2br(htmlspecialchars($product['ingredients'])); ?></p>
                    <?php endif; ?>
                </div>

                <?php if ($product['features']): ?>
                    <div id="features" class="tab-content">
                        <h3>⭐ คุณสมบัติเด่น</h3>
                        <div class="features-list">
                            <?php
                            $features = explode(',', $product['features']);
                            foreach ($features as $feature) {
                                $feature = trim($feature);
                                if (!empty($feature)) {
                                    echo '<div class="feature-item">
                                            <span class="feature-icon">✅</span>
                                            <span>' . htmlspecialchars($feature) . '</span>
                                          </div>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($product['benefits']): ?>
                    <div id="benefits" class="tab-content">
                        <h3>💚 คุณประโยชน์</h3>
                        <p><?php echo nl2br(htmlspecialchars($product['benefits'])); ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($product['usage_instructions']): ?>
                    <div id="usage" class="tab-content">
                        <h3>📋 วิธีการใช้งาน</h3>
                        <p><?php echo nl2br(htmlspecialchars($product['usage_instructions'])); ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($product['storage_instructions']): ?>
                    <div id="storage" class="tab-content">
                        <h3>🏠 วิธีการเก็บรักษา</h3>
                        <p><?php echo nl2br(htmlspecialchars($product['storage_instructions'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Related Products -->
        <?php if (!empty($related_products)): ?>
            <div class="related-products fade-in">
                <h2 class="section-title">🔗 สินค้าที่เกี่ยวข้อง</h2>
                <div class="products-grid">
                    <?php foreach ($related_products as $related): ?>
                        <div class="product-card">
                            <img src="<?php echo $related['image_main'] ? 'uploads/products/' . $related['image_main'] : 'https://via.placeholder.com/250x200/27ae60/ffffff?text=' . urlencode($related['name']); ?>"
                                alt="<?php echo htmlspecialchars($related['name']); ?>"
                                class="product-card-image">
                            <div class="product-card-content">
                                <h3 class="product-card-title"><?php echo htmlspecialchars($related['name']); ?></h3>
                                <div class="product-card-price">
                                    <?php if ($related['sale_price'] && $related['sale_price'] < $related['price']): ?>
                                        <?php echo formatPrice($related['sale_price']); ?>
                                        <span style="font-size: 0.8rem; color: #999; text-decoration: line-through;">
                                            <?php echo formatPrice($related['price']); ?>
                                        </span>
                                    <?php else: ?>
                                        <?php echo formatPrice($related['price']); ?>
                                    <?php endif; ?>
                                </div>
                                <button class="product-card-btn" onclick="viewProduct(<?php echo $related['id']; ?>)">
                                    ดูรายละเอียด
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Image Zoom Modal -->
    <div class="image-zoom" id="imageZoom" onclick="closeZoom()">
        <img src="" alt="" class="zoom-image" id="zoomImage">
    </div>

    <!-- Footer -->
    <footer style="background: #2d5016; color: white; padding: 2rem 0; margin-top: 3rem;">
        <div class="container">
            <div style="text-align: center;">
                <h3 style="margin-bottom: 1rem;">🌾 ข้าวพื้นเมืองเลย</h3>
                <p style="margin-bottom: 1rem;">อนุรักษ์และสืบสานภูมิปัญญาท้องถิ่น เพื่อสุขภาพและสิ่งแวดล้อม</p>
                <p>&copy; <?php echo date('Y'); ?> ข้าวพันธุ์พื้นเมืองเลย สงวนลิขสิทธิ์ทุกประการ</p>
            </div>
        </div>
    </footer>

    <script>
        // การจัดการตะกร้าสินค้า
        let cart = JSON.parse(localStorage.getItem('cart')) || [];

        function updateCartCount() {
            const cartCount = document.getElementById('cartCount');
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            cartCount.textContent = `(${totalItems})`;
        }

        function toggleCart() {
            window.location.href = 'cart.php';
        }

        function toggleMobileMenu() {
            const nav = document.getElementById('navMenu');
            nav.classList.toggle('show');
        }

        // การจัดการจำนวนสินค้า
        function changeQuantity(change) {
            const quantityInput = document.getElementById('quantity');
            let newValue = parseInt(quantityInput.value) + change;
            const maxStock = parseInt(quantityInput.getAttribute('max'));

            if (newValue < 1) newValue = 1;
            if (newValue > maxStock) newValue = maxStock;

            quantityInput.value = newValue;
        }

        // การเปลี่ยนรูปภาพหลัก
        function changeMainImage(src) {
            document.getElementById('mainImage').src = src;

            // อัพเดท active thumbnail
            document.querySelectorAll('.thumb-image').forEach(thumb => {
                thumb.classList.remove('active');
                if (thumb.src === src) {
                    thumb.classList.add('active');
                }
            });
        }

        // การซูมรูปภาพ
        function zoomImage(src) {
            const zoomModal = document.getElementById('imageZoom');
            const zoomImg = document.getElementById('zoomImage');
            zoomImg.src = src;
            zoomModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeZoom() {
            const zoomModal = document.getElementById('imageZoom');
            zoomModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // การจัดการแท็บ
        function showTab(tabName) {
            // ซ่อนแท็บทั้งหมด
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // ลบ active จากปุ่มทั้งหมด
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // แสดงแท็บที่เลือก
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        // เพิ่มสินค้าลงตะกร้า
        function addToCart(productId) {
            const quantity = parseInt(document.getElementById('quantity').value);
            const productName = '<?php echo addslashes($product['name']); ?>';
            const productPrice = <?php echo $product['sale_price'] ?: $product['price']; ?>;
            const productImage = '<?php echo $product['image_main'] ? 'uploads/products/' . $product['image_main'] : ''; ?>';

            // ตรวจสอบว่ามีสินค้าในตะกร้าแล้วหรือไม่
            const existingItem = cart.find(item => item.id === productId);

            if (existingItem) {
                existingItem.quantity += quantity;
            } else {
                cart.push({
                    id: productId,
                    name: productName,
                    price: productPrice,
                    image: productImage,
                    quantity: quantity
                });
            }

            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartCount();

            // แสดงข้อความแจ้งเตือน
            showNotification(`เพิ่ม "${productName}" ลงตะกร้าแล้ว (${quantity} ชิ้น)`, 'success');

            // เอฟเฟกต์ปุ่ม
            const addButton = event.target;
            const originalText = addButton.innerHTML;
            addButton.innerHTML = '<span class="loading"></span> เพิ่มแล้ว';
            addButton.disabled = true;

            setTimeout(() => {
                addButton.innerHTML = originalText;
                addButton.disabled = false;
            }, 1500);
        }

        // สั่งซื้อทันที
        function buyNow(productId) {
            addToCart(productId);
            setTimeout(() => {
                window.location.href = 'cart.php';
            }, 500);
        }

        // ดูสินค้าที่เกี่ยวข้อง
        function viewProduct(productId) {
            window.location.href = `product-detail.php?id=${productId}`;
        }

        // แสดงการแจ้งเตือน
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            const bgColor = type === 'success' ? '#27ae60' : type === 'error' ? '#e74c3c' : '#3498db';

            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${bgColor};
                color: white;
                padding: 1rem 2rem;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                z-index: 9999;
                font-weight: 600;
                animation: slideInRight 0.3s ease;
                max-width: 300px;
                word-wrap: break-word;
            `;
            notification.textContent = message;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        // CSS สำหรับ animations
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

        // Scroll Animation
        function observeElements() {
            const elements = document.querySelectorAll('.fade-in');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            }, {
                threshold: 0.1
            });

            elements.forEach(element => {
                observer.observe(element);
            });
        }

        // ป้องกันการกรอกจำนวนที่ไม่ถูกต้อง
        document.getElementById('quantity').addEventListener('input', function() {
            const value = parseInt(this.value);
            const max = parseInt(this.getAttribute('max'));

            if (isNaN(value) || value < 1) {
                this.value = 1;
            } else if (value > max) {
                this.value = max;
                showNotification(`สินค้าคงเหลือเพียง ${max} ชิ้น`, 'error');
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Escape เพื่อปิด zoom
            if (e.key === 'Escape') {
                closeZoom();
                const nav = document.getElementById('navMenu');
                nav.classList.remove('show');
            }

            // Enter เพื่อเพิ่มลงตะกร้า
            if (e.key === 'Enter' && !e.ctrlKey && !e.altKey) {
                const focusedElement = document.activeElement;
                if (focusedElement.id !== 'quantity') {
                    e.preventDefault();
                    addToCart(<?php echo $product['id']; ?>);
                }
            }

            // Ctrl+Enter เพื่อสั่งซื้อทันที
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                buyNow(<?php echo $product['id']; ?>);
            }
        });

        // เริ่มต้นเมื่อโหลดหน้า
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
            observeElements();

            // Set first thumbnail as active
            const firstThumb = document.querySelector('.thumb-image');
            if (firstThumb) {
                firstThumb.classList.add('active');
            }

            // เพิ่ม animation delay
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                element.style.animationDelay = `${index * 0.2}s`;
            });
        });

        // Window resize handler
        window.addEventListener('resize', () => {
            const nav = document.getElementById('navMenu');
            if (window.innerWidth > 768) {
                nav.classList.remove('show');
            }
        });

        // เพิ่ม lazy loading สำหรับรูปภาพ
        document.querySelectorAll('img').forEach(img => {
            img.loading = 'lazy';
        });

        // ตรวจสอบสต็อกสินค้าแบบ real-time (จำลอง)
        function checkStock() {
            // ในการใช้งานจริง ควรส่ง AJAX request ไปตรวจสอบสต็อกจากเซิร์ฟเวอร์
            console.log('Checking stock...');
        }

        // เช็คสต็อกทุก 30 วินาที
        setInterval(checkStock, 30000);

        // เพิ่มฟังก์ชันแชร์สินค้า
        function shareProduct() {
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo addslashes($product['name']); ?>',
                    text: '<?php echo addslashes($product['short_description']); ?>',
                    url: window.location.href
                });
            } else {
                // Fallback: copy URL to clipboard
                navigator.clipboard.writeText(window.location.href).then(() => {
                    showNotification('คัดลอกลิงก์แล้ว!', 'success');
                });
            }
        }

        // เพิ่มปุ่มแชร์ถ้าต้องการ
        const shareButton = document.createElement('button');
        shareButton.innerHTML = '📤 แชร์สินค้า';
        shareButton.className = 'product-card-btn';
        shareButton.style.marginTop = '1rem';
        shareButton.onclick = shareProduct;

        // เพิ่มปุ่มแชร์ในส่วน add-to-cart-section ถ้าต้องการ
        // document.querySelector('.add-to-cart-section').appendChild(shareButton);
    </script>
</body>

</html>