<?php
session_start();

// รวมไฟล์และเรียกใช้ getDB()
require_once 'config/database.php';
$pdo = getDB();


$error_message = '';
$success_message = '';

// ตรวจสอบว่าเข้าสู่ระบบแล้วหรือไม่
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// ประมวลผลการสมัครสมาชิก
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $accept_terms = isset($_POST['accept_terms']);

    // Validation
    $errors = [];

    if (empty($first_name)) $errors[] = 'กรุณากรอกชื่อ';
    if (empty($last_name)) $errors[] = 'กรุณากรอกนามสกุล';
    if (empty($email)) $errors[] = 'กรุณากรอกอีเมล';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
    if (empty($password)) $errors[] = 'กรุณากรอกรหัสผ่าน';
    if (strlen($password) < 6) $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
    if ($password !== $confirm_password) $errors[] = 'รหัสผ่านไม่ตรงกัน';
    if (!$accept_terms) $errors[] = 'กรุณายอมรับข้อตกลงและเงื่อนไข';

    // ตรวจสอบว่าอีเมลซ้ำหรือไม่
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'อีเมลนี้ถูกใช้งานแล้ว';
            }
        } catch (PDOException $e) {
            $errors[] = 'เกิดข้อผิดพลาดในระบบ';
        }
    }

    // บันทึกข้อมูลสมาชิกใหม่
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $verification_token = bin2hex(random_bytes(32));

            $stmt = $pdo->prepare("
                INSERT INTO users (
                    first_name, last_name, email, phone, password, 
                    date_of_birth, gender, verification_token, 
                    status, email_verified, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', 0, NOW())
            ");

            $stmt->execute([
                $first_name,
                $last_name,
                $email,
                $phone ?: null,
                $hashed_password,
                $date_of_birth ?: null,
                $gender ?: null,
                $verification_token
            ]);

            // ส่งอีเมลยืนยัน (จำลอง)
            // ในการใช้งานจริงควรส่งอีเมลจริง

            $success_message = 'สมัครสมาชิกสำเร็จ! ยินดีต้อนรับเข้าสู่ครอบครัวข้าวพื้นเมืองเลย';

            // รีเซ็ตฟอร์ม
            $first_name = $last_name = $email = $phone = $date_of_birth = $gender = '';

            // เปลี่ยนไปหน้า login พร้อมข้อความแจ้ง
            header("Location: login.php?registered=success");
            exit;
        } catch (PDOException $e) {
            $errors[] = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล กรุณาลองใหม่อีกครั้ง';
        }
    }

    if (!empty($errors)) {
        $error_message = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - ข้าวพันธุ์พื้นเมืองเลย</title>
    <meta name="description" content="สมัครสมาชิกเพื่อรับสิทธิประโยชน์พิเศษ ข้าวพันธุ์พื้นเมืองจากจังหวัดเลย">

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
            background: linear-gradient(135deg, #e8f5e8, #f0f8f0);
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #27ae60, #2d5016);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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

        /* Main Content */
        .main-content {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: calc(100vh - 200px);
            padding: 2rem 0;
        }

        .auth-container {
            display: flex;
            justify-content: center;
            max-width: 600px;
            width: 100%;
        }

        /* Register Form */
        .register-form {
            background: white;
            border-radius: 20px;
            padding: 3rem 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(39, 174, 96, 0.1);
        }

        .form-title {
            text-align: center;
            font-size: 1.8rem;
            font-weight: 700;
            color: #2d5016;
            margin-bottom: 0.5rem;
        }

        .form-subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2d5016;
            font-weight: 600;
        }

        .required {
            color: #e74c3c;
        }

        .form-control {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #27ae60;
            box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .password-field {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 1.2rem;
        }

        .password-strength {
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .strength-weak {
            width: 25%;
            background: #e74c3c;
        }

        .strength-medium {
            width: 50%;
            background: #f39c12;
        }

        .strength-good {
            width: 75%;
            background: #3498db;
        }

        .strength-strong {
            width: 100%;
            background: #27ae60;
        }

        .form-options {
            margin: 1.5rem 0;
        }

        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 0.8rem;
            margin-bottom: 1rem;
        }

        .checkbox-group input {
            width: auto;
            margin-top: 0.2rem;
        }

        .checkbox-group label {
            margin-bottom: 0;
            cursor: pointer;
            line-height: 1.4;
        }

        .checkbox-group a {
            color: #27ae60;
            text-decoration: none;
        }

        .checkbox-group a:hover {
            text-decoration: underline;
        }

        .register-btn {
            background: linear-gradient(135deg, #27ae60, #2d5016);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-bottom: 1.5rem;
        }

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }

        .register-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
            color: #666;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e9ecef;
            z-index: 1;
        }

        .divider span {
            background: white;
            padding: 0 1rem;
            position: relative;
            z-index: 2;
        }

        .login-link {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            margin-top: 1rem;
        }

        .login-link a {
            color: #27ae60;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* Alert */
        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 1.5rem;
            padding: 1rem;
            font-size: 0.9rem;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        /* Progress Steps */
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding: 0 1rem;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
            position: relative;
        }

        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 15px;
            left: 60%;
            right: -40%;
            height: 2px;
            background: #e9ecef;
            z-index: 1;
        }

        .step.active:not(:last-child)::after {
            background: #27ae60;
        }

        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Field Validation */
        .field-valid {
            border-color: #27ae60 !important;
        }

        .field-invalid {
            border-color: #e74c3c !important;
        }

        .validation-message {
            font-size: 0.8rem;
            margin-top: 0.3rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .validation-message.valid {
            color: #27ae60;
        }

        .validation-message.invalid {
            color: #e74c3c;
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
                <a href="login.php" class="nav-link">เข้าสู่ระบบ</a>
            </nav>

            <button class="mobile-menu-btn" onclick="toggleMobileMenu()">☰</button>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="auth-container">
                <!-- Register Form -->
                <div class="register-form fade-in">
                    <h2 class="form-title">📝 สมัครสมาชิก</h2>
                    <p class="form-subtitle">กรอกข้อมูลเพื่อสร้างบัญชีสมาชิกใหม่</p>



                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger">
                            ❌ <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success">
                            ✅ <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="registerForm">
                        <!-- ข้อมูลส่วนตัว -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name" class="form-label">ชื่อ <span class="required">*</span></label>
                                <input type="text" id="first_name" name="first_name" class="form-control"
                                    placeholder="กรอกชื่อของคุณ"
                                    value="<?php echo htmlspecialchars($first_name ?? ''); ?>" required>
                                <div class="validation-message" id="firstNameMsg"></div>
                            </div>

                            <div class="form-group">
                                <label for="last_name" class="form-label">นามสกุล <span class="required">*</span></label>
                                <input type="text" id="last_name" name="last_name" class="form-control"
                                    placeholder="กรอกนามสกุลของคุณ"
                                    value="<?php echo htmlspecialchars($last_name ?? ''); ?>" required>
                                <div class="validation-message" id="lastNameMsg"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">อีเมล <span class="required">*</span></label>
                            <input type="email" id="email" name="email" class="form-control"
                                placeholder="กรอกอีเมลของคุณ"
                                value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                            <div class="validation-message" id="emailMsg"></div>
                        </div>

                        <div class="form-group">
                            <label for="phone" class="form-label">เบอร์โทรศัพท์</label>
                            <input type="tel" id="phone" name="phone" class="form-control"
                                placeholder="กรอกเบอร์โทรศัพท์ (ไม่บังคับ)"
                                value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                            <div class="validation-message" id="phoneMsg"></div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_of_birth" class="form-label">วันเกิด</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control"
                                    value="<?php echo htmlspecialchars($date_of_birth ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="gender" class="form-label">เพศ</label>
                                <select id="gender" name="gender" class="form-control">
                                    <option value="">เลือกเพศ</option>
                                    <option value="male" <?php echo ($gender ?? '') == 'male' ? 'selected' : ''; ?>>ชาย</option>
                                    <option value="female" <?php echo ($gender ?? '') == 'female' ? 'selected' : ''; ?>>หญิง</option>
                                    <option value="other" <?php echo ($gender ?? '') == 'other' ? 'selected' : ''; ?>>อื่นๆ</option>
                                </select>
                            </div>
                        </div>

                        <!-- รหัสผ่าน -->
                        <div class="form-group">
                            <label for="password" class="form-label">รหัสผ่าน <span class="required">*</span></label>
                            <div class="password-field">
                                <input type="password" id="password" name="password" class="form-control"
                                    placeholder="กรอกรหัสผ่าน (อย่างน้อย 6 ตัวอักษร)" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                    👁️
                                </button>
                            </div>
                            <div class="password-strength" id="passwordStrength">
                                <div class="strength-bar" id="strengthBar"></div>
                            </div>
                            <div class="validation-message" id="passwordMsg"></div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">ยืนยันรหัสผ่าน <span class="required">*</span></label>
                            <div class="password-field">
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                                    placeholder="กรอกรหัสผ่านอีกครั้ง" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                    👁️
                                </button>
                            </div>
                            <div class="validation-message" id="confirmPasswordMsg"></div>
                        </div>

                        <!-- ข้อตกลงและเงื่อนไข -->
                        <div class="form-options">
                            <div class="checkbox-group">
                                <input type="checkbox" id="accept_terms" name="accept_terms" value="1" required>
                                <label for="accept_terms">
                                    ฉันยอมรับ <a href="terms.php" target="_blank">ข้อตกลงและเงื่อนไข</a>
                                    และ <a href="privacy.php" target="_blank">นโยบายความเป็นส่วนตัว</a> <span class="required">*</span>
                                </label>
                            </div>
                        </div>

                        <button type="submit" name="register" class="register-btn" id="registerBtn">
                            🚀 สมัครสมาชิก
                        </button>
                    </form>

                    <div class="login-link">
                        มีบัญชีอยู่แล้ว? <a href="login.php">เข้าสู่ระบบ</a>
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
        function toggleMobileMenu() {
            const nav = document.getElementById('navMenu');
            nav.classList.toggle('show');
        }

        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const toggleBtn = passwordField.nextElementSibling;

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleBtn.textContent = '🙈';
            } else {
                passwordField.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }

        // Form validation functions
        function validateField(field, validationRules) {
            const value = field.value.trim();
            const messageElement = document.getElementById(field.id + 'Msg');

            for (let rule of validationRules) {
                if (!rule.test(value)) {
                    field.classList.remove('field-valid');
                    field.classList.add('field-invalid');
                    messageElement.innerHTML = `<span class="invalid">❌ ${rule.message}</span>`;
                    messageElement.className = 'validation-message invalid';
                    return false;
                }
            }

            field.classList.remove('field-invalid');
            field.classList.add('field-valid');
            messageElement.innerHTML = '<span class="valid">✅ ถูกต้อง</span>';
            messageElement.className = 'validation-message valid';
            return true;
        }

        // Validation rules
        const validationRules = {
            first_name: [{
                    test: (value) => value.length > 0,
                    message: 'กรุณากรอกชื่อ'
                },
                {
                    test: (value) => value.length >= 2,
                    message: 'ชื่อต้องมีอย่างน้อย 2 ตัวอักษร'
                }
            ],
            last_name: [{
                    test: (value) => value.length > 0,
                    message: 'กรุณากรอกนามสกุล'
                },
                {
                    test: (value) => value.length >= 2,
                    message: 'นามสกุลต้องมีอย่างน้อย 2 ตัวอักษร'
                }
            ],
            email: [{
                    test: (value) => value.length > 0,
                    message: 'กรุณากรอกอีเมล'
                },
                {
                    test: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
                    message: 'รูปแบบอีเมลไม่ถูกต้อง'
                }
            ],
            phone: [{
                test: (value) => value === '' || /^[0-9]{9,10}$/.test(value.replace(/[-\s]/g, '')),
                message: 'เบอร์โทรต้องเป็นตัวเลข 9-10 หลัก'
            }],
            password: [{
                    test: (value) => value.length > 0,
                    message: 'กรุณากรอกรหัสผ่าน'
                },
                {
                    test: (value) => value.length >= 6,
                    message: 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร'
                }
            ]
        };

        // Real-time validation
        Object.keys(validationRules).forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('blur', () => validateField(field, validationRules[fieldId]));
                field.addEventListener('input', () => {
                    if (field.classList.contains('field-invalid')) {
                        validateField(field, validationRules[fieldId]);
                    }
                });
            }
        });

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('passwordMsg');

            let strength = 0;
            let message = '';
            let className = '';

            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;

            switch (strength) {
                case 0:
                case 1:
                    className = 'strength-weak';
                    message = 'รหัสผ่านอ่อนแอ';
                    break;
                case 2:
                    className = 'strength-medium';
                    message = 'รหัสผ่านปานกลาง';
                    break;
                case 3:
                    className = 'strength-good';
                    message = 'รหัสผ่านดี';
                    break;
                case 4:
                case 5:
                    className = 'strength-strong';
                    message = 'รหัสผ่านแข็งแรง';
                    break;
            }

            strengthBar.className = `strength-bar ${className}`;

            if (password.length > 0) {
                strengthText.innerHTML = `<span style="color: #666;">💪 ${message}</span>`;
                strengthText.className = 'validation-message';
            } else {
                strengthText.innerHTML = '';
                strengthBar.className = 'strength-bar';
            }
        });

        // Confirm password validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const messageElement = document.getElementById('confirmPasswordMsg');

            if (confirmPassword.length > 0) {
                if (password === confirmPassword) {
                    this.classList.remove('field-invalid');
                    this.classList.add('field-valid');
                    messageElement.innerHTML = '<span class="valid">✅ รหัสผ่านตรงกัน</span>';
                    messageElement.className = 'validation-message valid';
                } else {
                    this.classList.remove('field-valid');
                    this.classList.add('field-invalid');
                    messageElement.innerHTML = '<span class="invalid">❌ รหัสผ่านไม่ตรงกัน</span>';
                    messageElement.className = 'validation-message invalid';
                }
            } else {
                this.classList.remove('field-valid', 'field-invalid');
                messageElement.innerHTML = '';
            }
        });

        // Phone number formatting
        document.getElementById('phone').addEventListener('input', function() {
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

        // Form submission
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const registerBtn = document.getElementById('registerBtn');
            const originalText = registerBtn.innerHTML;

            // Validate all fields
            let isValid = true;
            Object.keys(validationRules).forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field && !validateField(field, validationRules[fieldId])) {
                    isValid = false;
                }
            });

            // Check password confirmation
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            if (password !== confirmPassword) {
                isValid = false;
                showError('รหัสผ่านไม่ตรงกัน');
            }

            // Check terms acceptance
            if (!document.getElementById('accept_terms').checked) {
                isValid = false;
                showError('กรุณายอมรับข้อตกลงและเงื่อนไข');
            }

            if (!isValid) {
                e.preventDefault();
                return;
            }

            registerBtn.innerHTML = '<span class="loading"></span> กำลังสมัครสมาชิก...';
            registerBtn.disabled = true;

            // Update progress steps
            updateProgressSteps(3);

            // ให้ฟอร์มส่งตามปกติ
            setTimeout(() => {
                if (!e.defaultPrevented) {
                    registerBtn.innerHTML = originalText;
                    registerBtn.disabled = false;
                }
            }, 3000);
        });

        function updateProgressSteps(activeStep) {
            const steps = document.querySelectorAll('.step');
            steps.forEach((step, index) => {
                const stepNumber = index + 1;
                if (stepNumber < activeStep) {
                    step.classList.add('completed');
                    step.classList.remove('active');
                } else if (stepNumber === activeStep) {
                    step.classList.add('active');
                    step.classList.remove('completed');
                } else {
                    step.classList.remove('active', 'completed');
                }
            });
        }

        function showError(message) {
            // ลบ alert เก่า
            const oldAlert = document.querySelector('.alert');
            if (oldAlert) {
                oldAlert.remove();
            }

            // สร้าง alert ใหม่
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger';
            alert.innerHTML = `❌ ${message}`;

            // แทรกหลัง progress steps
            const progressSteps = document.querySelector('.progress-steps');
            progressSteps.parentNode.insertBefore(alert, progressSteps.nextSibling);

            // Scroll to top
            alert.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
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

        // เริ่มต้นเมื่อโหลดหน้า
        document.addEventListener('DOMContentLoaded', function() {
            observeElements();

            // เพิ่ม animation delay
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                element.style.animationDelay = `${index * 0.3}s`;
            });

            // Auto focus ที่ช่องชื่อ
            setTimeout(() => {
                document.getElementById('first_name').focus();
            }, 500);
        });

        // Window resize handler
        window.addEventListener('resize', () => {
            const nav = document.getElementById('navMenu');
            if (window.innerWidth > 768) {
                nav.classList.remove('show');
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Escape เพื่อปิด mobile menu
            if (e.key === 'Escape') {
                const nav = document.getElementById('navMenu');
                nav.classList.remove('show');
            }
        });
    </script>
</body>

</html>