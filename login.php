<?php
session_start();
require_once 'config/database.php';

$error_message = '';
$success_message = '';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error_message = 'กรุณากรอกอีเมลและรหัสผ่าน';
    } else {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare('SELECT id, email, password, status FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && $user['status'] === 'active' && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_status'] = $user['status'];
                header('Location: index.php');
                exit;
            } else {
                $error_message = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
            }
        } catch (Exception $e) {
            $error_message = 'เกิดข้อผิดพลาดในระบบ';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ข้าวพันธุ์พื้นเมืองเลย</title>
    <meta name="description" content="เข้าสู่ระบบสมาชิก ข้าวพันธุ์พื้นเมืองจากจังหวัดเลย">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
            padding: 1rem;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
            position: relative;
        }

        .login-header {
            background: linear-gradient(135deg, #27ae60, #2d5016);
            color: white;
            text-align: center;
            padding: 2rem 1.5rem 1.5rem;
            position: relative;
        }

        .logo-icon {
            font-size: 3rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .login-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.3rem;
        }

        .login-subtitle {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .login-form {
            padding: 2rem 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2d5016;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-control:focus {
            outline: none;
            border-color: #27ae60;
            background: white;
            box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
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
            padding: 0.2rem;
        }

        .password-toggle:hover {
            color: #27ae60;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #666;
        }

        .remember-me input[type="checkbox"] {
            width: auto;
            margin: 0;
            accent-color: #27ae60;
        }

        .forgot-password {
            color: #27ae60;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .login-btn {
            background: linear-gradient(135deg, #27ae60, #2d5016);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-bottom: 1rem;
            position: relative;
            overflow: hidden;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(39, 174, 96, 0.3);
        }

        .login-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .divider {
            text-align: center;
            margin: 1rem 0;
            position: relative;
            color: #999;
            font-size: 0.85rem;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e9ecef;
        }

        .divider span {
            background: white;
            padding: 0 1rem;
            position: relative;
        }

        .register-link {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 12px;
            margin-top: 0.5rem;
        }

        .register-link a {
            color: #27ae60;
            text-decoration: none;
            font-weight: 600;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .back-home {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 1.2rem;
        }

        .back-home:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 1.5rem;
            padding: 1rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-danger {
            background: #fee;
            color: #c53030;
            border-left: 4px solid #e53e3e;
        }

        .alert-success {
            background: #f0fff4;
            color: #2f855a;
            border-left: 4px solid #38a169;
        }

        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Mobile Responsive */
        @media (max-width: 480px) {
            body {
                padding: 0.5rem;
            }

            .login-container {
                max-width: 100%;
            }

            .login-header {
                padding: 1.5rem 1rem 1rem;
            }

            .login-form {
                padding: 1.5rem 1rem;
            }

            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .logo-icon {
                font-size: 2.5rem;
            }

            .login-title {
                font-size: 1.3rem;
            }
        }

        /* เพิ่ม animation สำหรับการโหลด */
        .login-container {
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Test credentials info */
        .test-info {
            background: #e3f2fd;
            color: #1565c0;
            border-left: 4px solid #2196f3;
            font-family: monospace;
            font-size: 0.8rem;
            margin-bottom: 1.5rem;
        }

        .test-info strong {
            display: block;
            margin-bottom: 0.5rem;
            font-family: inherit;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <a href="index.php" class="back-home" title="กลับหน้าแรก">←</a>
            <span class="logo-icon">🌾</span>
            <h1 class="login-title">ข้าวพื้นเมืองเลย</h1>
            <p class="login-subtitle">เข้าสู่ระบบสมาชิก</p>
        </div>

        <div class="login-form">
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <span>⚠️</span>
                    <span><?php echo $error_message; ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <span>✅</span>
                    <span><?php echo $success_message; ?></span>
                </div>
            <?php endif; ?>

            <!-- แสดงข้อมูลทดสอบเฉพาะใน development mode -->
            <?php if (defined('ENVIRONMENT') && ENVIRONMENT === 'development'): ?>
                <div class="alert test-info">
                    <strong>🧪 ข้อมูลทดสอบ:</strong>
                    Email: test@example.com<br>
                    Password: password123<br><br>
                    หรือ somchai@example.com / test123<br>
                    หรือ somying@example.com / 123456
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label for="email" class="form-label">อีเมล</label>
                    <input type="email" id="email" name="email" class="form-control"
                        placeholder="กรอกอีเมลของคุณ"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">รหัสผ่าน</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" class="form-control"
                            placeholder="กรอกรหัสผ่านของคุณ" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">👁️</button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember_me" value="1">
                        <span>จดจำการเข้าสู่ระบบ</span>
                    </label>
                    <a href="forgot-password.php" class="forgot-password">ลืมรหัสผ่าน?</a>
                </div>

                <button type="submit" name="login" class="login-btn" id="loginBtn">
                    เข้าสู่ระบบ
                </button>
            </form>

            <div class="divider">
                <span>หรือ</span>
            </div>

            <div class="register-link">
                ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิกใหม่</a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleBtn = document.querySelector('.password-toggle');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleBtn.textContent = '🙈';
            } else {
                passwordField.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }

        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('loginBtn');
            const originalText = loginBtn.innerHTML;

            if (!validateForm()) {
                e.preventDefault();
                return;
            }

            loginBtn.innerHTML = '<span class="loading"></span> กำลังเข้าสู่ระบบ...';
            loginBtn.disabled = true;

            setTimeout(() => {
                if (!e.defaultPrevented) {
                    loginBtn.innerHTML = originalText;
                    loginBtn.disabled = false;
                }
            }, 3000);
        });

        function validateForm() {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            if (!email) {
                showError('กรุณากรอกอีเมล');
                document.getElementById('email').focus();
                return false;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showError('รูปแบบอีเมลไม่ถูกต้อง');
                document.getElementById('email').focus();
                return false;
            }

            if (!password) {
                showError('กรุณากรอกรหัสผ่าน');
                document.getElementById('password').focus();
                return false;
            }

            return true;
        }

        function showError(message) {
            const oldAlert = document.querySelector('.alert-danger');
            if (oldAlert) {
                oldAlert.remove();
            }

            const alert = document.createElement('div');
            alert.className = 'alert alert-danger';
            alert.innerHTML = `<span>⚠️</span><span>${message}</span>`;

            const form = document.getElementById('loginForm');
            form.insertBefore(alert, form.firstChild);
        }

        // Auto focus และ clear URL parameters
        document.addEventListener('DOMContentLoaded', function() {
            // Clear URL parameters (IE compatible)
            var search = window.location.search;
            if (search.indexOf('registered') !== -1 || search.indexOf('logout') !== -1) {
                var url = window.location.protocol + "//" + window.location.host + window.location.pathname;
                if (window.history && window.history.replaceState) {
                    window.history.replaceState({}, document.title, url);
                }
            }

            // Auto focus
            setTimeout(function() {
                document.getElementById('email').focus();
            }, 100);
        });

        // Real-time validation
        document.getElementById('email').addEventListener('blur', function() {
            const email = this.value.trim();
            if (email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                this.style.borderColor = emailRegex.test(email) ? '#27ae60' : '#e53e3e';
            }
        });

        document.getElementById('password').addEventListener('blur', function() {
            const password = this.value;
            if (password) {
                this.style.borderColor = password.length >= 6 ? '#27ae60' : '#e53e3e';
            }
        });
    </script>
</body>

</html>