<?php
session_start();

// ถ้าเข้าสู่ระบบแล้วให้ redirect ไปหน้า dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

// รวมไฟล์การตั้งค่า
require_once '../config/database.php';
//require_once '../config/config.php';

$error_message = '';
$success_message = '';

// ตรวจสอบการล็อกอิน
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $remember_me = isset($_POST['remember_me']);

    // Validation
    if (empty($username) || empty($password)) {
        $error_message = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        try {
            $database = Database::getInstance();
            $conn = $database->getConnection();

            // ค้นหาข้อมูลแอดมิน
            $stmt = $conn->prepare("
                SELECT id, username, password, first_name, last_name, role, status, 
                       login_attempts, locked_until 
                FROM admins 
                WHERE (username = :username OR email = :email) AND status = 'active'
            ");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $username);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);

                // ตรวจสอบการล็อกบัญชี
                if ($admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
                    $error_message = 'บัญชีถูกล็อก กรุณาลองใหม่อีกครั้งในภายหลัง';
                } else {
                    // ตรวจสอบรหัสผ่าน
                    if (password_verify($password, $admin['password'])) {
                        // ล็อกอินสำเร็จ

                        // Reset login attempts
                        $stmt = $conn->prepare("
                            UPDATE admins 
                            SET login_attempts = 0, 
                                locked_until = NULL, 
                                last_login = NOW(), 
                                last_login_ip = :ip 
                            WHERE id = :id
                        ");
                        $stmt->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
                        $stmt->bindParam(':id', $admin['id']);
                        $stmt->execute();

                        // ตั้งค่า session
                        $_SESSION['admin_id'] = $admin['id'];
                        $_SESSION['admin_username'] = $admin['username'];
                        $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
                        $_SESSION['admin_role'] = $admin['role'];
                        $_SESSION['admin_login_time'] = time();

                        // บันทึก log การเข้าสู่ระบบ
                        if (function_exists('logActivity')) {
                            logActivity('admin', $admin['id'], 'login', 'Admin login successful');
                        }

                        // Redirect ไปหน้า dashboard
                        header('Location: index.php');
                        exit();
                    } else {
                        // รหัสผ่านผิด - เพิ่ม login attempts
                        $login_attempts = $admin['login_attempts'] + 1;
                        $locked_until = null;

                        // ล็อกบัญชีหากพยายามล็อกอินผิด 5 ครั้ง
                        if ($login_attempts >= 5) {
                            $locked_until = date('Y-m-d H:i:s', time() + (30 * 60)); // ล็อก 30 นาที
                            $error_message = 'บัญชีถูกล็อกเนื่องจากพยายามเข้าสู่ระบบผิด 5 ครั้ง กรุณาลองใหม่ในอีก 30 นาที';
                        } else {
                            $remaining = 5 - $login_attempts;
                            $error_message = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง (เหลือ {$remaining} ครั้ง)";
                        }

                        $stmt = $conn->prepare("
                            UPDATE admins 
                            SET login_attempts = :attempts, locked_until = :locked_until 
                            WHERE id = :id
                        ");
                        $stmt->bindParam(':attempts', $login_attempts);
                        $stmt->bindParam(':locked_until', $locked_until);
                        $stmt->bindParam(':id', $admin['id']);
                        $stmt->execute();
                    }
                }
            } else {
                $error_message = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
            }
        } catch (Exception $e) {
            $error_message = 'เกิดข้อผิดพลาดในระบบ: ' . $e->getMessage();
            error_log("Admin login error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการ - ข้าวพันธุ์พื้นเมืองเลย</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f5f7fa;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }

        .container {
            width: 100%;
            max-width: 380px;
            padding: 2rem;
        }

        .brand-logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .brand-logo img {
            width: 60px;
            height: 60px;
            margin-bottom: 1rem;
        }

        .login-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 2.5rem 2rem;
            border: 1px solid #e8f5e8;
        }

        .card-icon {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .card-icon::before {
            content: '🌾';
            font-size: 3rem;
            display: block;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-5px);
            }
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-header h1 {
            color: #2d5016;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .form-header p {
            color: #666;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.8rem;
            color: #2d5016;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 1rem;
            border: 2px solid #27ae60;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            color: #333;
        }

        .form-control::placeholder {
            color: #999;
        }

        .form-control:focus {
            outline: none;
            border-color: #27ae60;
            box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin: 1.5rem 0;
            font-size: 0.9rem;
        }

        .form-check-input {
            width: 1.1rem;
            height: 1.1rem;
            accent-color: #27ae60;
        }

        .form-check-label {
            color: #555;
            cursor: pointer;
        }

        .btn-login {
            width: 100%;
            background: #27ae60;
            border: none;
            border-radius: 8px;
            padding: 1rem;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: #219a52;
        }

        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 1.5rem;
            padding: 1rem;
            font-size: 0.9rem;
        }

        .alert-danger {
            background: #fee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }

        .forgot-password {
            text-align: center;
            margin-top: 1.5rem;
        }

        .forgot-password a {
            color: #27ae60;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .footer-info {
            text-align: center;
            margin-top: 2rem;
            color: #999;
            font-size: 0.8rem;
        }

        /* Loading animation */
        .loading-spinner {
            display: none;
            width: 1rem;
            height: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 0.5rem;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .container {
                padding: 1rem;
            }

            .login-card {
                padding: 2rem 1.5rem;
            }

            .form-header h1 {
                font-size: 1.5rem;
            }
        }

        /* Animation */
        .login-card {
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="brand-logo"></div>

        <div class="login-card">
            <div class="card-icon"></div>
            <div class="form-header">
                <h1>ระบบจัดการ</h1>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="username" class="form-label">ชื่อผู้ใช้หรืออีเมล</label>
                    <input type="text" class="form-control" id="username" name="username"
                        placeholder="กรอกชื่อผู้ใช้หรืออีเมล" required
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">รหัสผ่าน</label>
                    <input type="password" class="form-control" id="password" name="password"
                        placeholder="กรอกรหัสผ่าน" required>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me">
                    <label class="form-check-label" for="remember_me">
                        จดจำการเข้าสู่ระบบ
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-login" id="loginBtn">
                    <span class="loading-spinner"></span>
                    <span class="btn-text">เข้าสู่ระบบ</span>
                </button>
            </form>

            <div class="forgot-password">
                <a href="#" onclick="showForgotPassword(); return false;">ลืมรหัสผ่าน?</a>
            </div>

            <div class="footer-info">
                &copy; <?php echo date('Y'); ?> ข้าวพันธุ์พื้นเมืองเลย<br>
                เวอร์ชัน 1.0 | เวลา <?php echo date('H:i:s'); ?>
            </div>
        </div>
    </div>

    <script>
        // การจัดการฟอร์มล็อกอิน
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            const btnText = btn.querySelector('.btn-text');
            const loading = btn.querySelector('.loading-spinner');

            // แสดง loading
            btnText.textContent = 'กำลังเข้าสู่ระบบ...';
            loading.style.display = 'inline-block';
            btn.disabled = true;
        });

        // ฟังก์ชันแสดงฟอร์มลืมรหัสผ่าน
        function showForgotPassword() {
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            `;

            modal.innerHTML = `
                <div style="
                    background: white;
                    padding: 2rem;
                    border-radius: 8px;
                    max-width: 350px;
                    width: 90%;
                    text-align: center;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                ">
                    <div style="font-size: 2rem; margin-bottom: 1rem; color: #27ae60;">🔑</div>
                    <h3 style="color: #2d5016; margin-bottom: 1rem;">ลืมรหัสผ่าน?</h3>
                    <p style="color: #666; margin-bottom: 1.5rem; line-height: 1.5;">
                        กรุณาติดต่อผู้ดูแลระบบเพื่อขอรีเซ็ตรหัสผ่าน
                    </p>
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; text-align: left; font-size: 0.9rem;">
                        <div style="margin-bottom: 0.5rem;"><strong>📧 อีเมล:</strong> admin@loeirice.com</div>
                        <div style="margin-bottom: 0.5rem;"><strong>📱 โทร:</strong> 081-234-5678</div>
                        <div><strong>💬 LINE:</strong> @loeirice</div>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" style="
                        background: #27ae60;
                        color: white;
                        border: none;
                        padding: 0.8rem 1.5rem;
                        border-radius: 6px;
                        cursor: pointer;
                        font-weight: 500;
                    ">ปิด</button>
                </div>
            `;

            document.body.appendChild(modal);

            // ปิด modal เมื่อคลิกพื้นหลัง
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        // Auto focus ที่ช่องชื่อผู้ใช้
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>

</html>