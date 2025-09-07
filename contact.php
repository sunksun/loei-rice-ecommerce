<?php
// เริ่ม session
session_start();

$success_message = '';
$error_message = '';

// ประมวลผลฟอร์มติดต่อ
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validation
    $errors = [];
    if (empty($name)) $errors[] = 'กรุณากรอกชื่อ';
    if (empty($email)) $errors[] = 'กรุณากรอกอีเมล';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
    if (empty($subject)) $errors[] = 'กรุณากรอกหัวข้อ';
    if (empty($message)) $errors[] = 'กรุณากรอกข้อความ';
    if (strlen($message) < 10) $errors[] = 'ข้อความต้องมีอย่างน้อย 10 ตัวอักษร';

    if (empty($errors)) {
        // บันทึกข้อมูลหรือส่งอีเมล (จำลอง)
        // ในการใช้งานจริง ควรบันทึกลงฐานข้อมูลหรือส่งอีเมล
        $success_message = 'ขอบคุณสำหรับข้อความของคุณ เราจะติดต่อกลับภายใน 24 ชั่วโมง';

        // รีเซ็ตฟอร์ม
        $name = $email = $phone = $subject = $message = '';
    } else {
        $error_message = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ติดต่อเรา - ข้าวพันธุ์พื้นเมืองเลย</title>
    <meta name="description" content="ติดต่อเราสำหรับข้อมูลเพิ่มเติมเกี่ยวกับข้าวพันธุ์พื้นเมืองเลย ที่อยู่ เบอร์โทร อีเมล และแผนที่">

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

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, rgba(39, 174, 96, 0.9), rgba(45, 80, 22, 0.9)),
                url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 400"><defs><pattern id="contact-bg" x="0" y="0" width="60" height="60" patternUnits="userSpaceOnUse"><circle cx="30" cy="30" r="2" fill="rgba(255,255,255,0.1)"/><ellipse cx="20" cy="20" rx="8" ry="3" fill="rgba(255,255,255,0.05)" transform="rotate(45 20 20)"/></pattern></defs><rect width="1200" height="400" fill="url(%23contact-bg)"/></svg>');
            color: white;
            padding: 3rem 0;
            text-align: center;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .page-description {
            font-size: 1.2rem;
            opacity: 0.95;
        }

        /* Main Content */
        .main-content {
            padding: 3rem 0;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
        }

        /* Contact Info */
        .contact-info {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            height: fit-content;
        }

        .info-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #2d5016;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .info-item:hover {
            background: #e8f5e8;
            transform: translateY(-2px);
        }

        .info-icon {
            font-size: 1.5rem;
            color: #27ae60;
            min-width: 40px;
            text-align: center;
        }

        .info-content h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2d5016;
        }

        .info-content p {
            color: #666;
            margin-bottom: 0.3rem;
        }

        .info-content a {
            color: #27ae60;
            text-decoration: none;
        }

        .info-content a:hover {
            text-decoration: underline;
        }

        /* Contact Form */
        .contact-form {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .form-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #2d5016;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2d5016;
            font-weight: 500;
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
            min-height: 120px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn-submit {
            background: #27ae60;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-submit:hover {
            background: #219a52;
            transform: translateY(-2px);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
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

        /* Map Section */
        .map-section {
            margin-top: 3rem;
        }

        .map-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .map-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #2d5016;
            text-align: center;
        }

        .map-placeholder {
            width: 100%;
            height: 400px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: #666;
            border: 2px dashed #dee2e6;
        }

        .map-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #27ae60;
        }

        /* FAQ Section */
        .faq-section {
            margin-top: 3rem;
        }

        .faq-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .faq-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #2d5016;
            text-align: center;
        }

        .faq-item {
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 1rem;
        }

        .faq-question {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            cursor: pointer;
            font-weight: 600;
            color: #2d5016;
            transition: color 0.3s ease;
        }

        .faq-question:hover {
            color: #27ae60;
        }

        .faq-toggle {
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .faq-answer {
            padding: 0 0 1rem 0;
            color: #666;
            line-height: 1.6;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .faq-item.active .faq-answer {
            max-height: 200px;
        }

        .faq-item.active .faq-toggle {
            transform: rotate(45deg);
        }

        /* Business Hours */
        .hours-section {
            margin-top: 2rem;
        }

        .hours-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #2d5016;
        }

        .hours-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }

        .hours-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .hours-day {
            font-weight: 500;
            color: #2d5016;
        }

        .hours-time {
            color: #666;
        }

        .hours-today {
            background: #e8f5e8;
            padding: 0.5rem;
            border-radius: 4px;
            margin: -0.5rem;
        }

        /* Social Links */
        .social-section {
            margin-top: 2rem;
            text-align: center;
        }

        .social-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #2d5016;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }

        .social-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background: #27ae60;
            color: white;
            text-decoration: none;
            border-radius: 50%;
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background: #219a52;
            transform: translateY(-3px);
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

            .page-title {
                font-size: 2rem;
            }

            .content-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .hours-grid {
                grid-template-columns: 1fr;
            }

            .social-links {
                flex-wrap: wrap;
            }
        }

        @media (max-width: 480px) {

            .contact-info,
            .contact-form,
            .map-container,
            .faq-container {
                padding: 1.5rem;
            }

            .main-content {
                padding: 2rem 0;
            }

            .container {
                padding: 0 0.8rem;
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

        /* Character Counter */
        .char-counter {
            font-size: 0.8rem;
            color: #666;
            text-align: right;
            margin-top: 0.3rem;
        }

        /* Notification Animations */
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }

            to {
                transform: translateX(100%);
                opacity: 0;
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

            <nav class="nav" id="navMenu">
                <a href="index.php" class="nav-link">หน้าแรก</a>
                <a href="products.php" class="nav-link">สินค้า</a>
                <a href="about.php" class="nav-link">เกี่ยวกับเรา</a>
                <a href="contact.php" class="nav-link" style="color: #a8e6cf;">ติดต่อ</a>
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
                <span class="breadcrumb-item">ติดต่อเรา</span>
            </div>
        </div>
    </div>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1 class="page-title fade-in">ติดต่อเรา</h1>
            <p class="page-description fade-in">เรายินดีที่จะรับฟังและตอบทุกคำถาม</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="content-grid">
                <!-- Contact Information -->
                <div class="contact-info fade-in">
                    <h2 class="info-title">📍 ข้อมูลติดต่อ</h2>

                    <div class="info-item">
                        <div class="info-icon">🏢</div>
                        <div class="info-content">
                            <h3>ที่อยู่</h3>
                            <p>บ้านเลขที่ 123 หมู่ 5 ตำบลศรีเจริญ</p>
                            <p>อำเภอภูหลวง จังหวัดเลย 42160</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">📞</div>
                        <div class="info-content">
                            <h3>เบอร์โทรศัพท์</h3>
                            <p><a href="tel:+66812345678">081-234-5678</a></p>
                            <p><a href="tel:+66421234567">042-123-4567</a></p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">✉️</div>
                        <div class="info-content">
                            <h3>อีเมล</h3>
                            <p><a href="mailto:info@loeirice.com">info@loeirice.com</a></p>
                            <p><a href="mailto:sales@loeirice.com">sales@loeirice.com</a></p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">💬</div>
                        <div class="info-content">
                            <h3>LINE Official</h3>
                            <p><a href="https://line.me/R/ti/p/@loeirice">@loeirice</a></p>
                            <p>สำหรับการสอบถามด่วน</p>
                        </div>
                    </div>

                    <!-- Business Hours -->
                    <div class="hours-section">
                        <h3 class="hours-title">🕒 เวลาทำการ</h3>
                        <div class="hours-grid">
                            <div class="hours-item hours-today">
                                <span class="hours-day">จันทร์ - ศุกร์</span>
                                <span class="hours-time">8:00 - 18:00</span>
                            </div>
                            <div class="hours-item">
                                <span class="hours-day">เสาร์</span>
                                <span class="hours-time">8:00 - 17:00</span>
                            </div>
                            <div class="hours-item">
                                <span class="hours-day">อาทิตย์</span>
                                <span class="hours-time">9:00 - 16:00</span>
                            </div>
                            <div class="hours-item">
                                <span class="hours-day">วันหยุดนักขัตฤกษ์</span>
                                <span class="hours-time">ปิด</span>
                            </div>
                        </div>
                    </div>

                    <!-- Social Links -->
                    <div class="social-section">
                        <h3 class="social-title">ติดตามเรา</h3>
                        <div class="social-links">
                            <a href="#" class="social-link" title="Facebook">📘</a>
                            <a href="#" class="social-link" title="Instagram">📷</a>
                            <a href="#" class="social-link" title="LINE">💬</a>
                            <a href="#" class="social-link" title="YouTube">📺</a>
                        </div>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="contact-form fade-in">
                    <h2 class="form-title">📝 ส่งข้อความถึงเรา</h2>

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

                    <form method="POST" id="contactForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name" class="form-label">ชื่อ - นามสกุล <span class="required">*</span></label>
                                <input type="text" id="name" name="name" class="form-control"
                                    value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="phone" class="form-label">เบอร์โทรศัพท์</label>
                                <input type="tel" id="phone" name="phone" class="form-control"
                                    value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">อีเมล <span class="required">*</span></label>
                            <input type="email" id="email" name="email" class="form-control"
                                value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="subject" class="form-label">หัวข้อ <span class="required">*</span></label>
                            <select id="subject" name="subject" class="form-control" required>
                                <option value="">เลือกหัวข้อ</option>
                                <option value="สอบถามสินค้า" <?php echo ($subject ?? '') == 'สอบถามสินค้า' ? 'selected' : ''; ?>>สอบถามสินค้า</option>
                                <option value="สั่งซื้อสินค้า" <?php echo ($subject ?? '') == 'สั่งซื้อสินค้า' ? 'selected' : ''; ?>>สั่งซื้อสินค้า</option>
                                <option value="ร้องเรียน" <?php echo ($subject ?? '') == 'ร้องเรียน' ? 'selected' : ''; ?>>ร้องเรียน</option>
                                <option value="ขอใบเสนอราคา" <?php echo ($subject ?? '') == 'ขอใบเสนอราคา' ? 'selected' : ''; ?>>ขอใบเสนอราคา</option>
                                <option value="สมัครเป็นตัวแทนจำหน่าย" <?php echo ($subject ?? '') == 'สมัครเป็นตัวแทนจำหน่าย' ? 'selected' : ''; ?>>สมัครเป็นตัวแทนจำหน่าย</option>
                                <option value="อื่นๆ" <?php echo ($subject ?? '') == 'อื่นๆ' ? 'selected' : ''; ?>>อื่นๆ</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="message" class="form-label">ข้อความ <span class="required">*</span></label>
                            <textarea id="message" name="message" class="form-control"
                                placeholder="กรุณาระบุรายละเอียดที่ต้องการสอบถาม..." required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                            <div class="char-counter" id="charCounter">0/1000 ตัวอักษร</div>
                        </div>

                        <button type="submit" class="btn-submit" id="submitBtn">
                            📤 ส่งข้อความ
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Section -->
    <div class="map-section">
        <div class="container">
            <div class="map-container fade-in">
                <h2 class="map-title">🗺️ แผนที่ตำแหน่งที่ตั้ง</h2>
                <div class="map-placeholder">
                    <div class="map-icon">📍</div>
                    <p>แผนที่ Google Maps</p>
                    <p style="font-size: 0.9rem; color: #999;">
                        อำเภอภูหลวง จังหวัดเลย 42160
                    </p>
                    <p style="font-size: 0.8rem; color: #999; margin-top: 1rem;">
                        * ในการใช้งานจริง ควรแทนที่ด้วย Google Maps Embed
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="faq-section">
        <div class="container">
            <div class="faq-container fade-in">
                <h2 class="faq-title">❓ คำถามที่พบบ่อย</h2>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>สินค้าของคุณมีการรับรองคุณภาพหรือไม่?</span>
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        <p>สินค้าของเราผ่านการตรวจสอบคุณภาพอย่างเข้มงวด ปลอดสารเคมี และมีใบรับรองจากหน่วยงานที่เกี่ยวข้อง</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>จัดส่งทั่วประเทศหรือไม่?</span>
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        <p>เราจัดส่งทั่วประเทศไทย ใช้เวลาจัดส่ง 2-5 วันทำการ ขึ้นอยู่กับระยะทางและช่องทางการจัดส่ง</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>มีการรับคืนสินค้าหรือไม่?</span>
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        <p>เรารับคืนสินค้าที่มีปัญหาภายใน 7 วันหลังรับสินค้า โดยสินค้าต้องอยู่ในสภาพเดิมและยังไม่เปิดใช้งาน</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>สามารถสั่งซื้อขั้นต่ำเท่าไหร่?</span>
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        <p>สั่งซื้อขั้นต่ำ 500 บาท สำหรับการจัดส่งฟรี หรือสั่งซื้อเท่าไหร่ก็ได้สำหรับการมารับเองที่ร้าน</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>มีส่วนลดสำหรับการสั่งซื้อจำนวนมากหรือไม่?</span>
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        <p>มีส่วนลดพิเศษสำหรับการสั่งซื้อจำนวนมาก เริ่มต้นที่ 10% สำหรับการสั่งซื้อตั้งแต่ 10,000 บาทขึ้นไป</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>มีบริการส่งด่วนหรือไม่?</span>
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        <p>มีบริการส่งด่วนในพื้นที่กรุงเทพฯ และปริมณฑล สามารถได้รับสินค้าภายใน 24 ชั่วโมง (มีค่าใช้จ่ายเพิ่มเติม)</p>
                    </div>
                </div>
            </div>
        </div>
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

        // FAQ Toggle
        function toggleFaq(element) {
            const faqItem = element.parentElement;
            const isActive = faqItem.classList.contains('active');

            // ปิด FAQ อื่นๆ
            document.querySelectorAll('.faq-item').forEach(item => {
                item.classList.remove('active');
            });

            // เปิด/ปิด FAQ ปัจจุบัน
            if (!isActive) {
                faqItem.classList.add('active');
            }
        }

        // Form submission
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;

            // Validate form
            if (!validateForm()) {
                e.preventDefault();
                return;
            }

            submitBtn.innerHTML = '<span class="loading"></span> กำลังส่ง...';
            submitBtn.disabled = true;

            // ให้ฟอร์มส่งตามปกติ
            setTimeout(() => {
                if (!e.defaultPrevented) {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            }, 3000);
        });

        // Form validation
        function validateForm() {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const subject = document.getElementById('subject').value;
            const message = document.getElementById('message').value.trim();

            if (!name) {
                showNotification('กรุณากรอกชื่อ', 'error');
                document.getElementById('name').focus();
                return false;
            }

            if (!email) {
                showNotification('กรุณากรอกอีเมล', 'error');
                document.getElementById('email').focus();
                return false;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showNotification('รูปแบบอีเมลไม่ถูกต้อง', 'error');
                document.getElementById('email').focus();
                return false;
            }

            if (!subject) {
                showNotification('กรุณาเลือกหัวข้อ', 'error');
                document.getElementById('subject').focus();
                return false;
            }

            if (!message) {
                showNotification('กรุณากรอกข้อความ', 'error');
                document.getElementById('message').focus();
                return false;
            }

            if (message.length < 10) {
                showNotification('กรุณากรอกข้อความอย่างน้อย 10 ตัวอักษร', 'error');
                document.getElementById('message').focus();
                return false;
            }

            if (message.length > 1000) {
                showNotification('ข้อความต้องไม่เกิน 1000 ตัวอักษร', 'error');
                document.getElementById('message').focus();
                return false;
            }

            return true;
        }

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

        // Character counter for message
        const messageTextarea = document.getElementById('message');
        const charCounter = document.getElementById('charCounter');

        function updateMessageCounter() {
            const length = messageTextarea.value.length;
            charCounter.textContent = `${length}/1000 ตัวอักษร`;

            if (length > 1000) {
                charCounter.style.color = '#e74c3c';
            } else if (length < 10) {
                charCounter.style.color = '#f39c12';
            } else {
                charCounter.style.color = '#666';
            }
        }

        messageTextarea.addEventListener('input', updateMessageCounter);
        messageTextarea.setAttribute('maxlength', '1000');

        // Auto-resize textarea
        messageTextarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 200) + 'px';
        });

        // Phone number formatting
        const phoneInput = document.getElementById('phone');
        phoneInput.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (value.length <= 3) {
                    value = value;
                } else if (value.length <= 6) {
                    value = value.slice(0, 3) + '-' + value.slice(3);
                } else {
                    value = value.slice(0, 3) + '-' + value.slice(3, 6) + '-' + value.slice(6, 10);
                }
            }
            this.value = value;
        });

        // Show success notification
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

        // เริ่มต้นเมื่อโหลดหน้า
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
            observeElements();
            updateMessageCounter();

            // เพิ่ม animation delay
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                element.style.animationDelay = `${index * 0.2}s`;
            });

            // Auto focus ที่ช่องชื่อ
            setTimeout(() => {
                document.getElementById('name').focus();
            }, 500);

            // แสดงข้อความต้อนรับ
            setTimeout(() => {
                showNotification('ยินดีต้อนรับ! กรุณากรอกข้อมูลเพื่อติดต่อเรา', 'info');
            }, 1000);
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Escape เพื่อปิด mobile menu
            if (e.key === 'Escape') {
                const nav = document.getElementById('navMenu');
                nav.classList.remove('show');
            }

            // Ctrl+Enter เพื่อส่งฟอร์ม
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('contactForm').submit();
            }
        });

        // ป้องกันการส่งฟอร์มซ้ำ
        let isSubmitting = false;
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                showNotification('กรุณารอสักครู่ กำลังส่งข้อความ...', 'info');
                return false;
            }

            if (validateForm()) {
                isSubmitting = true;
                setTimeout(() => {
                    isSubmitting = false;
                }, 5000);
            }
        });

        // Window resize handler
        window.addEventListener('resize', () => {
            const nav = document.getElementById('navMenu');
            if (window.innerWidth > 768) {
                nav.classList.remove('show');
            }
        });

        // เพิ่ม smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Real-time validation feedback
        document.getElementById('name').addEventListener('blur', function() {
            if (this.value.trim().length < 2) {
                this.style.borderColor = '#e74c3c';
                showNotification('ชื่อต้องมีอย่างน้อย 2 ตัวอักษร', 'error');
            } else {
                this.style.borderColor = '#27ae60';
            }
        });

        document.getElementById('email').addEventListener('blur', function() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (this.value && !emailRegex.test(this.value)) {
                this.style.borderColor = '#e74c3c';
                showNotification('รูปแบบอีเมลไม่ถูกต้อง', 'error');
            } else if (this.value) {
                this.style.borderColor = '#27ae60';
            }
        });

        // เพิ่มฟังก์ชันนับถอยหลังสำหรับปุ่มส่ง
        let cooldownTimer = 0;

        function startCooldown() {
            cooldownTimer = 30;
            const submitBtn = document.getElementById('submitBtn');

            const countdown = setInterval(() => {
                if (cooldownTimer > 0) {
                    submitBtn.innerHTML = `⏱️ รอ ${cooldownTimer} วินาที`;
                    submitBtn.disabled = true;
                    cooldownTimer--;
                } else {
                    submitBtn.innerHTML = '📤 ส่งข้อความ';
                    submitBtn.disabled = false;
                    clearInterval(countdown);
                }
            }, 1000);
        }

        // เพิ่ม Local Storage สำหรับบันทึก draft
        function saveDraft() {
            const formData = {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value,
                subject: document.getElementById('subject').value,
                message: document.getElementById('message').value,
                timestamp: Date.now()
            };
            localStorage.setItem('contactFormDraft', JSON.stringify(formData));
        }

        function loadDraft() {
            const saved = localStorage.getItem('contactFormDraft');
            if (saved) {
                const formData = JSON.parse(saved);
                // โหลด draft ที่บันทึกไว้ไม่เกิน 24 ชั่วโมง
                if (Date.now() - formData.timestamp < 24 * 60 * 60 * 1000) {
                    document.getElementById('name').value = formData.name || '';
                    document.getElementById('email').value = formData.email || '';
                    document.getElementById('phone').value = formData.phone || '';
                    document.getElementById('subject').value = formData.subject || '';
                    document.getElementById('message').value = formData.message || '';
                    updateMessageCounter();

                    if (formData.name || formData.email || formData.message) {
                        showNotification('โหลดข้อมูลที่บันทึกไว้แล้ว', 'info');
                    }
                }
            }
        }

        // บันทึก draft ทุกครั้งที่พิมพ์
        ['name', 'email', 'phone', 'subject', 'message'].forEach(fieldId => {
            document.getElementById(fieldId).addEventListener('input', saveDraft);
        });

        // โหลด draft เมื่อเริ่มต้น
        setTimeout(loadDraft, 100);

        // ลบ draft เมื่อส่งฟอร์มสำเร็จ
        document.getElementById('contactForm').addEventListener('submit', function() {
            if (validateForm()) {
                setTimeout(() => {
                    localStorage.removeItem('contactFormDraft');
                }, 1000);
            }
        });
    </script>
</body>

</html>