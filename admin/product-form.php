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
$is_edit = isset($_GET['id']) && !empty($_GET['id']);
$product_id = $is_edit ? (int)$_GET['id'] : 0;

try {
    // เชื่อมต่อฐานข้อมูล
    $host = 'localhost';
    $dbname = 'loei_rice_ecommerce';
    $username_db = 'root';
    $password_db = '';

    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username_db, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ดึงข้อมูลหมวดหมู่
    $categories_stmt = $conn->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

    // ถ้าเป็นการแก้ไข ดึงข้อมูลสินค้า
    $product = [];
    if ($is_edit) {
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->bindParam(':id', $product_id);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            header('Location: products.php');
            exit();
        }
    }
} catch (Exception $e) {
    $error_message = 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: ' . $e->getMessage();
}

// การบันทึกข้อมูล
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // รับข้อมูลจากฟอร์ม
        $name = trim($_POST['name']);
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $description = trim($_POST['description']);
        $short_description = trim($_POST['short_description']);
        $price = (float)$_POST['price'];
        $sale_price = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
        $stock_quantity = (int)$_POST['stock_quantity'];
        $min_stock_level = (int)$_POST['min_stock_level'];
        $weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : null;
        $unit = trim($_POST['unit']);
        $features = trim($_POST['features']);
        $ingredients = trim($_POST['ingredients']);
        $benefits = trim($_POST['benefits']);
        $usage_instructions = trim($_POST['usage_instructions']);
        $storage_instructions = trim($_POST['storage_instructions']);
        $origin = trim($_POST['origin']);
        $harvest_season = trim($_POST['harvest_season']);
        $certification = trim($_POST['certification']);
        $status = $_POST['status'];
        $featured = isset($_POST['featured']) ? 1 : 0;
        $is_new = isset($_POST['is_new']) ? 1 : 0;

        // Validation
        $errors = [];
        if (empty($name)) $errors[] = 'กรุณากรอกชื่อสินค้า';
        if ($price <= 0) $errors[] = 'ราคาต้องมากกว่า 0';
        if ($stock_quantity < 0) $errors[] = 'จำนวนสต็อกต้องไม่ติดลบ';
        if ($min_stock_level < 0) $errors[] = 'สต็อกขั้นต่ำต้องไม่ติดลบ';
        if ($sale_price !== null && $sale_price >= $price) {
            $errors[] = 'ราคาขายต้องน้อยกว่าราคาปกติ';
        }

        if (!empty($errors)) {
            $error_message = implode('<br>', $errors);
        } else {
            // จัดการรูปภาพ
            $image_main = $is_edit ? $product['image_main'] : null;

            if (isset($_FILES['image_main']) && $_FILES['image_main']['error'] == 0) {
                $upload_dir = '../uploads/products/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_info = pathinfo($_FILES['image_main']['name']);
                $extension = strtolower($file_info['extension']);
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (in_array($extension, $allowed_extensions)) {
                    $new_filename = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
                    $upload_path = $upload_dir . $new_filename;

                    if (move_uploaded_file($_FILES['image_main']['tmp_name'], $upload_path)) {
                        // ลบรูปเก่า (ถ้ามี)
                        if ($is_edit && !empty($product['image_main']) && file_exists($upload_dir . $product['image_main'])) {
                            unlink($upload_dir . $product['image_main']);
                        }
                        $image_main = $new_filename;
                    } else {
                        $errors[] = 'ไม่สามารถอัพโหลดรูปภาพได้';
                    }
                } else {
                    $errors[] = 'รูปภาพต้องเป็นไฟล์ .jpg, .jpeg, .png, .gif หรือ .webp เท่านั้น';
                }
            }

            if (empty($errors)) {
                if ($is_edit) {
                    // อัพเดทสินค้า
                    $sql = "UPDATE products SET 
                            category_id = :category_id,
                            name = :name,
                            description = :description,
                            short_description = :short_description,
                            price = :price,
                            sale_price = :sale_price,
                            stock_quantity = :stock_quantity,
                            min_stock_level = :min_stock_level,
                            weight = :weight,
                            unit = :unit,
                            image_main = :image_main,
                            features = :features,
                            ingredients = :ingredients,
                            benefits = :benefits,
                            usage_instructions = :usage_instructions,
                            storage_instructions = :storage_instructions,
                            origin = :origin,
                            harvest_season = :harvest_season,
                            certification = :certification,
                            status = :status,
                            featured = :featured,
                            is_new = :is_new,
                            updated_at = NOW()
                            WHERE id = :id";

                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':id', $product_id);
                } else {
                    // เพิ่มสินค้าใหม่
                    $sql = "INSERT INTO products (
                            category_id, name, description, short_description, price, sale_price,
                            stock_quantity, min_stock_level, weight, unit, image_main,
                            features, ingredients, benefits, usage_instructions, storage_instructions,
                            origin, harvest_season, certification, status, featured, is_new,
                            created_at, updated_at
                            ) VALUES (
                            :category_id, :name, :description, :short_description, :price, :sale_price,
                            :stock_quantity, :min_stock_level, :weight, :unit, :image_main,
                            :features, :ingredients, :benefits, :usage_instructions, :storage_instructions,
                            :origin, :harvest_season, :certification, :status, :featured, :is_new,
                            NOW(), NOW()
                            )";

                    $stmt = $conn->prepare($sql);
                }

                // Bind parameters
                $stmt->bindParam(':category_id', $category_id);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':short_description', $short_description);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':sale_price', $sale_price);
                $stmt->bindParam(':stock_quantity', $stock_quantity);
                $stmt->bindParam(':min_stock_level', $min_stock_level);
                $stmt->bindParam(':weight', $weight);
                $stmt->bindParam(':unit', $unit);
                $stmt->bindParam(':image_main', $image_main);
                $stmt->bindParam(':features', $features);
                $stmt->bindParam(':ingredients', $ingredients);
                $stmt->bindParam(':benefits', $benefits);
                $stmt->bindParam(':usage_instructions', $usage_instructions);
                $stmt->bindParam(':storage_instructions', $storage_instructions);
                $stmt->bindParam(':origin', $origin);
                $stmt->bindParam(':harvest_season', $harvest_season);
                $stmt->bindParam(':certification', $certification);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':featured', $featured);
                $stmt->bindParam(':is_new', $is_new);

                if ($stmt->execute()) {
                    $success_message = $is_edit ? 'อัพเดทสินค้าเรียบร้อยแล้ว' : 'เพิ่มสินค้าเรียบร้อยแล้ว';

                    // รีเฟรชข้อมูลสินค้าหลังจากอัพเดท
                    if ($is_edit) {
                        $stmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
                        $stmt->bindParam(':id', $product_id);
                        $stmt->execute();
                        $product = $stmt->fetch(PDO::FETCH_ASSOC);
                    }
                } else {
                    $error_message = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';
                }
            } else {
                $error_message = implode('<br>', $errors);
            }
        }
    } catch (Exception $e) {
        $error_message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'แก้ไขสินค้า' : 'เพิ่มสินค้าใหม่'; ?> - ระบบจัดการข้าวพันธุ์พื้นเมืองเลย</title>

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
            max-width: 800px;
            margin: 0 auto;
            padding: 1rem;
        }

        /* Form Card */
        .form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .form-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }

        .form-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .form-subtitle {
            color: #666;
            font-size: 0.9rem;
        }

        .form-body {
            padding: 2rem;
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

        /* Form Groups */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2c3e50;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .required {
            color: #e74c3c;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #27ae60;
            box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .textarea-large {
            min-height: 120px;
        }

        /* Form Row */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
        }

        /* File Upload */
        .file-upload {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-upload input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-upload-label {
            display: block;
            padding: 0.8rem;
            border: 2px dashed #e9ecef;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .file-upload-label:hover {
            border-color: #27ae60;
            background: #e8f5e8;
        }

        .file-upload-icon {
            font-size: 2rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .current-image {
            margin-top: 1rem;
            text-align: center;
        }

        .current-image img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Checkboxes */
        .checkbox-group {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkbox-item input[type="checkbox"] {
            width: 1.2rem;
            height: 1.2rem;
            accent-color: #27ae60;
        }

        /* Buttons */
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
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

        .btn-outline {
            background: white;
            color: #6c757d;
            border: 2px solid #6c757d;
        }

        .btn-outline:hover {
            background: #6c757d;
            color: white;
        }

        /* Sections */
        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Help Text */
        .help-text {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.3rem;
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

            .form-body {
                padding: 1.5rem;
            }

            .form-row,
            .form-row-3 {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .checkbox-group {
                flex-direction: column;
                gap: 1rem;
            }
        }

        /* Loading */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .btn-loading {
            position: relative;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            margin: auto;
            border: 2px solid transparent;
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
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
                <a href="products.php" class="back-btn">←</a>
                <div>
                    <div class="header-title">
                        🌾 <?php echo $is_edit ? 'แก้ไขสินค้า' : 'เพิ่มสินค้าใหม่'; ?>
                    </div>
                </div>
            </div>
            <div class="user-info">
                👤 <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="form-card">
            <div class="form-header">
                <h1 class="form-title">
                    <?php echo $is_edit ? '✏️ แก้ไขข้อมูลสินค้า' : '➕ เพิ่มสินค้าใหม่'; ?>
                </h1>
                <p class="form-subtitle">
                    <?php echo $is_edit ? 'แก้ไขข้อมูลสินค้าข้าวพันธุ์พื้นเมืองเลย' : 'เพิ่มสินค้าข้าวพันธุ์พื้นเมืองเลยใหม่เข้าสู่ระบบ'; ?>
                </p>
            </div>

            <div class="form-body">
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

                <form method="POST" enctype="multipart/form-data" id="productForm">
                    <!-- ข้อมูลพื้นฐาน -->
                    <div class="form-section">
                        <h3 class="section-title">📝 ข้อมูลพื้นฐาน</h3>

                        <div class="form-group">
                            <label for="name" class="form-label">ชื่อสินค้า <span class="required">*</span></label>
                            <input type="text" id="name" name="name" class="form-control"
                                value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="category_id" class="form-label">หมวดหมู่</label>
                                <select id="category_id" name="category_id" class="form-control">
                                    <option value="">เลือกหมวดหมู่</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"
                                            <?php echo ($product['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="status" class="form-label">สถานะ <span class="required">*</span></label>
                                <select id="status" name="status" class="form-control" required>
                                    <option value="active" <?php echo ($product['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>เปิดขาย</option>
                                    <option value="inactive" <?php echo ($product['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>ปิดขาย</option>
                                    <option value="out_of_stock" <?php echo ($product['status'] ?? '') == 'out_of_stock' ? 'selected' : ''; ?>>สินค้าหมด</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="short_description" class="form-label">คำอธิบายสั้น</label>
                            <textarea id="short_description" name="short_description" class="form-control"
                                placeholder="คำอธิบายสั้นๆ เกี่ยวกับสินค้า"><?php echo htmlspecialchars($product['short_description'] ?? ''); ?></textarea>
                            <div class="help-text">แสดงในรายการสินค้า (แนะนำไม่เกิน 200 ตัวอักษร)</div>
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">คำอธิบายรายละเอียด</label>
                            <textarea id="description" name="description" class="form-control textarea-large"
                                placeholder="รายละเอียดครบถ้วนเกี่ยวกับสินค้า"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- ราคาและสต็อก -->
                    <div class="form-section">
                        <h3 class="section-title">💰 ราคาและสต็อก</h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="price" class="form-label">ราคาปกติ (บาท) <span class="required">*</span></label>
                                <input type="number" id="price" name="price" class="form-control"
                                    value="<?php echo $product['price'] ?? ''; ?>" min="0" step="0.01" required>
                            </div>

                            <div class="form-group">
                                <label for="sale_price" class="form-label">ราคาขาย (บาท)</label>
                                <input type="number" id="sale_price" name="sale_price" class="form-control"
                                    value="<?php echo $product['sale_price'] ?? ''; ?>" min="0" step="0.01">
                                <div class="help-text">ใส่ถ้ามีราคาโปรโมชั่น</div>
                            </div>
                        </div>

                        <div class="form-row-3">
                            <div class="form-group">
                                <label for="stock_quantity" class="form-label">จำนวนสต็อก <span class="required">*</span></label>
                                <input type="number" id="stock_quantity" name="stock_quantity" class="form-control"
                                    value="<?php echo $product['stock_quantity'] ?? '0'; ?>" min="0" required>
                            </div>

                            <div class="form-group">
                                <label for="min_stock_level" class="form-label">สต็อกขั้นต่ำ <span class="required">*</span></label>
                                <input type="number" id="min_stock_level" name="min_stock_level" class="form-control"
                                    value="<?php echo $product['min_stock_level'] ?? '5'; ?>" min="0" required>
                                <div class="help-text">แจ้งเตือนเมื่อสต็อกต่ำ</div>
                            </div>

                            <div class="form-group">
                                <label for="unit" class="form-label">หน่วย</label>
                                <input type="text" id="unit" name="unit" class="form-control"
                                    value="<?php echo htmlspecialchars($product['unit'] ?? 'กรัม'); ?>"
                                    placeholder="กรัม, ถุง, หลอด">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="weight" class="form-label">น้ำหนัก (กรัม)</label>
                            <input type="number" id="weight" name="weight" class="form-control"
                                value="<?php echo $product['weight'] ?? ''; ?>" min="0" step="1">
                        </div>
                    </div>

                    <!-- รูปภาพ -->
                    <div class="form-section">
                        <h3 class="section-title">📸 รูปภาพสินค้า</h3>

                        <div class="form-group">
                            <label for="image_main" class="form-label">รูปภาพหลัก</label>
                            <div class="file-upload">
                                <input type="file" id="image_main" name="image_main" accept="image/*">
                                <label for="image_main" class="file-upload-label">
                                    <div class="file-upload-icon">📷</div>
                                    <div>คลิกเพื่อเลือกรูปภาพ</div>
                                    <div style="font-size: 0.8rem; color: #666;">รองรับ JPG, PNG, GIF, WebP</div>
                                </label>
                            </div>

                            <?php if ($is_edit && !empty($product['image_main'])): ?>
                                <div class="current-image">
                                    <div style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem;">รูปภาพปัจจุบัน:</div>
                                    <img src="../uploads/products/<?php echo htmlspecialchars($product['image_main']); ?>"
                                        alt="รูปภาพสินค้าปัจจุบัน">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ข้อมูลเพิ่มเติม -->
                    <div class="form-section">
                        <h3 class="section-title">📋 ข้อมูลเพิ่มเติม</h3>

                        <div class="form-group">
                            <label for="features" class="form-label">คุณสมบัติเด่น</label>
                            <textarea id="features" name="features" class="form-control"
                                placeholder="เช่น หอมเป็นเอกลักษณ์, แตกกอดี, ผลผลิตสูง"><?php echo htmlspecialchars($product['features'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="ingredients" class="form-label">ส่วนผสม/วัตถุดิบ</label>
                            <textarea id="ingredients" name="ingredients" class="form-control"
                                placeholder="รายการส่วนผสมหรือวัตถุดิบ"><?php echo htmlspecialchars($product['ingredients'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="benefits" class="form-label">คุณประโยชน์</label>
                            <textarea id="benefits" name="benefits" class="form-control"
                                placeholder="ประโยชน์ที่ได้รับจากการบริโภค"><?php echo htmlspecialchars($product['benefits'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="usage_instructions" class="form-label">วิธีการใช้งาน</label>
                                <textarea id="usage_instructions" name="usage_instructions" class="form-control"
                                    placeholder="วิธีการเตรียม การปรุง หรือการใช้งาน"><?php echo htmlspecialchars($product['usage_instructions'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="storage_instructions" class="form-label">วิธีการเก็บรักษา</label>
                                <textarea id="storage_instructions" name="storage_instructions" class="form-control"
                                    placeholder="วิธีการเก็บรักษาสินค้า"><?php echo htmlspecialchars($product['storage_instructions'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- ข้อมูลแหล่งที่มา -->
                    <div class="form-section">
                        <h3 class="section-title">🌍 แหล่งที่มาและการรับรอง</h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="origin" class="form-label">แหล่งที่มา/ถิ่นกำเนิด</label>
                                <input type="text" id="origin" name="origin" class="form-control"
                                    value="<?php echo htmlspecialchars($product['origin'] ?? ''); ?>"
                                    placeholder="เช่น บ้านศรีเจริญ อำเภอภูหลวง จังหวัดเลย">
                            </div>

                            <div class="form-group">
                                <label for="harvest_season" class="form-label">ฤดูเก็บเกี่ยว</label>
                                <input type="text" id="harvest_season" name="harvest_season" class="form-control"
                                    value="<?php echo htmlspecialchars($product['harvest_season'] ?? ''); ?>"
                                    placeholder="เช่น กรกฎาคม - สิงหาคม">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="certification" class="form-label">การรับรอง</label>
                            <input type="text" id="certification" name="certification" class="form-control"
                                value="<?php echo htmlspecialchars($product['certification'] ?? ''); ?>"
                                placeholder="เช่น อินทรีย์, GAP, มาตรฐาน Q">
                        </div>
                    </div>

                    <!-- การตั้งค่าพิเศษ -->
                    <div class="form-section">
                        <h3 class="section-title">⭐ การตั้งค่าพิเศษ</h3>

                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="featured" name="featured" value="1"
                                    <?php echo ($product['featured'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="featured">สินค้าแนะนำ</label>
                            </div>

                            <div class="checkbox-item">
                                <input type="checkbox" id="is_new" name="is_new" value="1"
                                    <?php echo ($product['is_new'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="is_new">สินค้าใหม่</label>
                            </div>
                        </div>

                        <div class="help-text">
                            สินค้าแนะนำจะแสดงในหน้าแรกของเว็บไซต์ สินค้าใหม่จะมีป้าย "New" แสดง
                        </div>
                    </div>

                    <!-- ปุ่มบันทึก -->
                    <div class="form-actions">
                        <a href="products.php" class="btn btn-outline">
                            ❌ ยกเลิก
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <?php echo $is_edit ? '💾 บันทึกการเปลี่ยนแปลง' : '➕ เพิ่มสินค้า'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // การจัดการฟอร์ม
        document.getElementById('productForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const formData = new FormData(this);

            // ตรวจสอบข้อมูลพื้นฐาน
            const name = formData.get('name').trim();
            const price = parseFloat(formData.get('price'));
            const salePrice = formData.get('sale_price') ? parseFloat(formData.get('sale_price')) : null;

            if (!name) {
                alert('กรุณากรอกชื่อสินค้า');
                e.preventDefault();
                return;
            }

            if (price <= 0) {
                alert('ราคาต้องมากกว่า 0');
                e.preventDefault();
                return;
            }

            if (salePrice !== null && salePrice >= price) {
                alert('ราคาขายต้องน้อยกว่าราคาปกติ');
                e.preventDefault();
                return;
            }

            // แสดง loading
            submitBtn.classList.add('btn-loading');
            submitBtn.disabled = true;
            submitBtn.textContent = 'กำลังบันทึก...';

            // แทนที่จะยกเลิก ให้ฟอร์มส่งต่อไป
            // setTimeout เพื่อให้ผู้ใช้เห็น loading effect
            setTimeout(() => {
                // ฟอร์มจะส่งต่อไปตามปกติ
            }, 500);
        });

        // ตัวอย่างรูปภาพเมื่อเลือกไฟล์
        document.getElementById('image_main').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const label = document.querySelector('.file-upload-label');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // สร้างการแสดงตัวอย่างรูปภาพ
                    const preview = document.createElement('div');
                    preview.innerHTML = `
                        <div style="margin-top: 1rem; text-align: center;">
                            <div style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem;">ตัวอย่างรูปภาพใหม่:</div>
                            <img src="${e.target.result}" style="max-width: 200px; max-height: 200px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        </div>
                    `;

                    // ลบตัวอย่างเก่า (ถ้ามี)
                    const oldPreview = document.querySelector('.new-image-preview');
                    if (oldPreview) {
                        oldPreview.remove();
                    }

                    preview.className = 'new-image-preview';
                    document.querySelector('.file-upload').appendChild(preview);
                };
                reader.readAsDataURL(file);

                // เปลี่ยนข้อความ label
                label.innerHTML = `
                    <div class="file-upload-icon">✅</div>
                    <div>เลือกไฟล์แล้ว: ${file.name}</div>
                    <div style="font-size: 0.8rem; color: #666;">คลิกเพื่อเปลี่ยนรูปใหม่</div>
                `;
            }
        });

        // Auto-resize textarea
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
        });

        // เพิ่ม character counter สำหรับ short_description
        const shortDesc = document.getElementById('short_description');
        if (shortDesc) {
            const counter = document.createElement('div');
            counter.className = 'help-text';
            counter.style.textAlign = 'right';
            shortDesc.parentNode.appendChild(counter);

            function updateCounter() {
                const length = shortDesc.value.length;
                counter.textContent = `${length}/200 ตัวอักษร`;
                counter.style.color = length > 200 ? '#e74c3c' : '#666';
            }

            shortDesc.addEventListener('input', updateCounter);
            updateCounter();
        }

        // Validation ราคา
        document.getElementById('price').addEventListener('input', function() {
            const salePrice = document.getElementById('sale_price');
            if (this.value && salePrice.value) {
                if (parseFloat(salePrice.value) >= parseFloat(this.value)) {
                    salePrice.setCustomValidity('ราคาขายต้องน้อยกว่าราคาปกติ');
                } else {
                    salePrice.setCustomValidity('');
                }
            }
        });

        document.getElementById('sale_price').addEventListener('input', function() {
            const price = document.getElementById('price');
            if (this.value && price.value) {
                if (parseFloat(this.value) >= parseFloat(price.value)) {
                    this.setCustomValidity('ราคาขายต้องน้อยกว่าราคาปกติ');
                } else {
                    this.setCustomValidity('');
                }
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>

</html>