<?php
// แสดง PHP errors สำหรับการ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// เริ่ม session
session_start();

// รวมไฟล์การตั้งค่า
require_once 'config/database.php';

try {
    // เชื่อมต่อฐานข้อมูล
    $conn = getDB();

    // ดึงสินค้าแนะนำ (featured products)
    $featured_stmt = $conn->query("
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.featured = 1 AND p.status = 'active'
        ORDER BY p.created_at DESC
        LIMIT 6
    ");
    $featured_products = $featured_stmt->fetchAll(PDO::FETCH_ASSOC);

    // ดึงสินค้าใหม่ (new products)
    $new_stmt = $conn->query("
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.is_new = 1 AND p.status = 'active'
        ORDER BY p.created_at DESC
        LIMIT 4
    ");
    $new_products = $new_stmt->fetchAll(PDO::FETCH_ASSOC);

    // ตรวจสอบสถานะการล็อกอินและดึงข้อมูลผู้ใช้
    $user_data = null;
    $is_logged_in = false;
    $user_name = '';
    $user_initial = 'G'; // Guest

    if (isset($_SESSION['user_id'])) {
        try {
            $user_stmt = $conn->prepare("
            SELECT first_name, last_name, email, profile_image 
            FROM users 
            WHERE id = ? AND status = 'active'
        ");
            $user_stmt->execute(array($_SESSION['user_id']));
            $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

            if ($user_data) {
                $is_logged_in = true;
                $user_name = trim($user_data['first_name'] . ' ' . $user_data['last_name']);
                $user_initial = strtoupper(mb_substr($user_data['first_name'], 0, 1, 'UTF-8'));

                // อัพเดทเวลาล็อกอินครั้งสุดท้าย
                $update_login = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $update_login->execute(array($_SESSION['user_id']));
            } else {
                // ถ้าไม่พบผู้ใช้หรือถูกระงับ ให้ล็อกเอาท์
                session_destroy();
                $user_initial = 'G';
            }
        } catch (Exception $e) {
            error_log("Error fetching user data: " . $e->getMessage());
            $user_name = '';
            $user_initial = 'G';
        }
    }

    // ดึงหมวดหมู่สินค้า
    $categories_stmt = $conn->query("
        SELECT c.*, COUNT(p.id) as product_count
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
        WHERE c.status = 'active'
        GROUP BY c.id
        ORDER BY c.name
    ");
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // หากการเชื่อมต่อหรือการ query มีปัญหา
    error_log("Error on index.php: " . $e->getMessage());
    $featured_products = array();
    $new_products = array();
    $categories = array();
    
    // ประกาศตัวแปรที่จำเป็นสำหรับการแสดงผล
    $user_data = null;
    $is_logged_in = false;
    $user_name = '';
    $user_initial = 'G';
}

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้าวพันธุ์พื้นเมืองเลย - อนุรักษ์และสืบสานความเป็นไทย</title>
    <meta name="description" content="ข้าวพันธุ์พื้นเมืองแท้จากจังหวัดเลย คุณภาพดี ปลอดสารเคมี สืบทอดภูมิปัญญาท้องถิ่น">
    <meta name="keywords" content="ข้าวพื้นเมือง, ข้าวเลย, ข้าวอินทรีย์, ข้าวเหนียวแดง, ข้าวซิวเกลี้ยง">

    <!-- External CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <a href="index.php" class="logo">
                <span class="logo-icon">🌾</span>
                <span>ข้าวพื้นเมืองเลย</span>
            </a>

            <nav class="nav">
                <a href="index.php" class="nav-link active">หน้าแรก</a>
                <a href="products.php" class="nav-link">สินค้า</a>
                <a href="about.php" class="nav-link">เกี่ยวกับเรา</a>
                <a href="contact.php" class="nav-link">ติดต่อ</a>
                <a href="register.php" class="nav-link">สมัครสมาชิก</a>
                <a href="order-tracking.php" class="nav-link">ติดตามคำสั่งซื้อ</a>
            </nav>

            <div class="user-section">
                <button class="notification-btn" onclick="showNotifications()">
                    🔔
                </button>

                <button class="cart-btn" onclick="toggleCart()">
                    🛒
                    <span class="cart-count" id="cartCount">0</span>
                </button>

                <div class="user-profile" tabindex="0"
                    <?php if ($is_logged_in): ?>
                    onmouseenter="showUserMenu()" onmouseleave="hideUserMenu()" onclick="toggleUserMenu()"
                    <?php else: ?>
                    onclick="window.location.href='login.php'"
                    <?php endif; ?>>
                    <div class="user-avatar">
                        <?php if ($user_data && !empty($user_data['profile_image'])): ?>
                            <img src="uploads/profiles/<?php echo htmlspecialchars($user_data['profile_image']); ?>"
                                alt="Profile" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <?php echo $user_initial; ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($is_logged_in): ?>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                            <div class="user-welcome">ยินดีต้อนรับ!</div>
                        </div>
                        <div id="userMenuPopup" class="user-dropdown-menu" style="display:none; position:absolute; top:110%; right:0; background:white; border-radius:12px; box-shadow:0 8px 25px rgba(0,0,0,0.15); border:1px solid #f0f0f0; min-width:180px; z-index:1001; padding:0.5rem 0;">
                            <a href="profile.php" style="display:block; padding:0.8rem 1rem; text-decoration:none; color:#333; transition:background 0.3s ease;">👤 ข้อมูลส่วนตัว</a>
                            <a href="orders.php" style="display:block; padding:0.8rem 1rem; text-decoration:none; color:#333; transition:background 0.3s ease;">📦 คำสั่งซื้อ</a>
                            <a href="payment-notification.php" style="display:block; padding:0.8rem 1rem; text-decoration:none; color:#333; transition:background 0.3s ease;">💸 แจ้งชำระเงิน</a>
                            <hr style="margin:0.5rem 0; border:none; border-top:1px solid #f0f0f0;">
                            <a href="logout.php" style="display:block; padding:0.8rem 1rem; text-decoration:none; color:#e74c3c; transition:background 0.3s ease;">🚪 ออกจากระบบ</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Search Bar -->
    <div class="search-container">
        <div class="search-bar">
            <span class="search-icon">🔍</span>
            <input type="text" class="search-input" placeholder="ค้นหาข้าวพันธุ์พื้นเมือง..." id="searchInput">
            <button class="search-btn" onclick="performSearch()">ค้นหา</button>
        </div>
    </div>

    <!-- Hero Cover Section -->
    <div class="hero-cover">
        <div class="hero-image">
            <img src="assets/images/rice-field-cover.jpg" alt="ทุ่งนาข้าวเลย">
            <div class="hero-overlay">
                <div class="hero-content">
                    <h1>ข้าวพันธุ์พื้นเมืองเลย</h1>
                    <p>สืบทอดภูมิปัญญาท้องถิ่น ด้วยข้าวคุณภาพจากใจดินแดนเลย</p>
                    <div class="hero-buttons">
                        <button class="hero-btn primary" onclick="window.location.href='products.php'">
                            🛍️ เลือกซื้อสินค้า
                        </button>
                        <button class="hero-btn secondary" onclick="window.location.href='about.php'">
                            📖 เรียนรู้เพิ่มเติม
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Section -->
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">หมวดหมู่สินค้า</h2>
            <a href="products.php" class="see-all-btn">ดูทั้งหมด</a>
        </div>

        <div class="categories-grid">
            <?php if (empty($categories)): ?>
                <div class="category-item">
                    <div class="category-icon">🌾</div>
                    <div class="category-name">ข้าวพื้นเมือง</div>
                    <div class="category-count">15 รายการ</div>
                </div>
                <div class="category-item">
                    <div class="category-icon">🍘</div>
                    <div class="category-name">ผลิตภัณฑ์</div>
                    <div class="category-count">8 รายการ</div>
                </div>
                <div class="category-item">
                    <div class="category-icon">🧴</div>
                    <div class="category-name">เครื่องสำอาง</div>
                    <div class="category-count">23 รายการ</div>
                </div>
                <div class="category-item">
                    <div class="category-icon">🌿</div>
                    <div class="category-name">ธรรมชาติ</div>
                    <div class="category-count">12 รายการ</div>
                </div>
            <?php else: ?>
                <?php foreach (array_slice($categories, 0, 4) as $category): ?>
                    <a href="products.php?category=<?php echo $category['id']; ?>" class="category-item">
                        <div class="category-icon">
                            <?php
                            $icons = array('🌾', '🍘', '🧴', '🍯', '🥥', '🌿');
                            echo $icons[array_rand($icons)];
                            ?>
                        </div>
                        <div class="category-name"><?php echo htmlspecialchars($category['name']); ?></div>
                        <div class="category-count"><?php echo number_format($category['product_count']); ?> รายการ</div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Products Section -->
    <section class="section">
        <div class="hot-deals">
            <div class="deals-header">
                <h2 class="deals-title">สินค้าแนะนำ</h2>
                <a href="products.php?featured=1" class="see-all-btn" style="margin-left: auto;">ดูทั้งหมด</a>
            </div>

            <div class="filter-pills">
                <a href="#" class="filter-pill active">ทั้งหมด</a>
                <a href="#" class="filter-pill">ข้าวเหนียว</a>
                <a href="#" class="filter-pill">ข้าวซิว</a>
                <a href="#" class="filter-pill">ข้าวกล้อง</a>
                <a href="#" class="filter-pill">ผลิตภัณฑ์แปรรูป</a>
            </div>

            <div class="products-grid">
                <?php if (empty($featured_products)): ?>
                    <div class="product-card fade-in">
                        <div class="product-image">
                            🌾
                            <button class="favorite-btn" onclick="toggleFavorite(this)">♡</button>
                        </div>
                        <div class="product-content">
                            <div class="product-category">ข้าวพื้นเมือง</div>
                            <h3 class="product-name">ข้าวเหนียวแดงเมืองเลย</h3>
                            <div class="product-price">
                                <span class="price-current">฿180.00</span>
                            </div>
                            <div class="product-actions">
                                <button class="btn-add-cart" onclick="addToCart(1)">
                                    🛒 เพิ่มลงตะกร้า
                                </button>
                                <button class="btn-view" onclick="viewProduct(1)">
                                    👁️
                                </button>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($featured_products as $product): ?>
                        <div class="product-card fade-in">
                            <div class="product-image">
                                <?php if (!empty($product['image_main'])): ?>
                                    <img src="uploads/products/<?php echo htmlspecialchars($product['image_main']); ?>"
                                        alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    🌾
                                <?php endif; ?>

                                <button class="favorite-btn" onclick="toggleFavorite(this)">♡</button>

                                <div class="product-badges">
                                    <?php if (!empty($product['sale_price'])): ?>
                                        <?php $discount = round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>
                                        <span class="product-badge discount"><?php echo $discount; ?>% off</span>
                                    <?php endif; ?>

                                    <?php if ($product['featured']): ?>
                                        <span class="product-badge featured">แนะนำ</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="product-content">
                                <div class="product-category"><?php echo htmlspecialchars(isset($product['category_name']) && !empty($product['category_name']) ? $product['category_name'] : 'ทั่วไป'); ?></div>
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>

                                <div class="product-price">
                                    <?php if (!empty($product['sale_price'])): ?>
                                        <span class="price-current">฿<?php echo number_format($product['sale_price'], 2); ?></span>
                                        <span class="price-original">฿<?php echo number_format($product['price'], 2); ?></span>
                                    <?php else: ?>
                                        <span class="price-current">฿<?php echo number_format($product['price'], 2); ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="product-actions">
                                    <button class="btn-add-cart" onclick="addToCart(<?php echo $product['id']; ?>)">
                                        🛒 เพิ่มลงตะกร้า
                                    </button>
                                    <button class="btn-view" onclick="viewProduct(<?php echo $product['id']; ?>)">
                                        👁️
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Bottom Navigation (Mobile) -->
    <nav class="bottom-nav">
        <a href="index.php" class="nav-item active">
            <span class="nav-icon">🏠</span>
            <span class="nav-label">หน้าแรก</span>
        </a>
        <a href="register.php" class="nav-item">
            <span class="nav-icon">📝</span>
            <span class="nav-label">สมัครสมาชิก</span>
        </a>
        <a href="order-tracking.php" class="nav-item">
            <span class="nav-icon">📦</span>
            <span class="nav-label">คำสั่งซื้อ</span>
        </a>
        <a href="profile.php" class="nav-item">
            <span class="nav-icon">👤</span>
            <span class="nav-label">โปรไฟล์</span>
        </a>
    </nav>

    <script src="assets/js/main.js"></script>
</body>

</html>