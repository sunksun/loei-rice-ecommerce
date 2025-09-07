<?php
session_start();

// ตรวจสอบว่ามี session admin หรือไม่
if (isset($_SESSION['admin_id'])) {
    // บันทึก log การออกจากระบบ (ถ้ามีฟังก์ชัน)
    if (function_exists('logActivity')) {
        logActivity('admin', $_SESSION['admin_id'], 'logout', 'Admin logout successful');
    }

    // เก็บข้อมูลบางส่วนก่อนลบ session
    $admin_name = $_SESSION['admin_name'] ?? 'ผู้ใช้';
    $login_time = $_SESSION['admin_login_time'] ?? time();
    $session_duration = time() - $login_time;

    // ลบข้อมูล session ทั้งหมด
    session_unset();
    session_destroy();

    // ลบ cookie (ถ้ามี)
    if (isset($_COOKIE['admin_remember'])) {
        setcookie('admin_remember', '', time() - 3600, '/');
    }

    $logout_success = true;
    $session_time = gmdate("H:i:s", $session_duration);
} else {
    $logout_success = false;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ออกจากระบบสำเร็จ - ข้าวพันธุ์พื้นเมืองเลย</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }

        .container {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }

        .logout-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 3rem 2rem;
            text-align: center;
            border: 1px solid #e8f5e8;
            animation: slideUp 0.6s ease-out;
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

        .success-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            animation: bounce 1.5s ease-in-out;
        }

        @keyframes bounce {

            0%,
            20%,
            50%,
            80%,
            100% {
                transform: translateY(0);
            }

            40% {
                transform: translateY(-10px);
            }

            60% {
                transform: translateY(-5px);
            }
        }

        .logout-title {
            color: #27ae60;
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .logout-message {
            color: #666;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .session-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
            border-left: 4px solid #27ae60;
        }

        .session-info h4 {
            color: #2d5016;
            font-size: 1rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.8rem;
            font-size: 0.9rem;
        }

        .info-item:last-child {
            margin-bottom: 0;
        }

        .info-label {
            color: #666;
            font-weight: 500;
        }

        .info-value {
            color: #2c3e50;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .btn {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: #27ae60;
            color: white;
        }

        .btn-primary:hover {
            background: #219a52;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #666;
            border: 2px solid #e9ecef;
        }

        .btn-secondary:hover {
            background: #e9ecef;
            border-color: #dee2e6;
        }

        .footer-text {
            margin-top: 2rem;
            color: #999;
            font-size: 0.8rem;
        }

        .error-card {
            background: #fff5f5;
            border-left-color: #e53e3e;
        }

        .error-icon {
            color: #e53e3e;
        }

        .error-title {
            color: #e53e3e;
        }

        /* Mobile Responsive */
        @media (max-width: 480px) {
            .container {
                padding: 1rem;
            }

            .logout-card {
                padding: 2rem 1.5rem;
            }

            .success-icon {
                font-size: 3rem;
            }

            .logout-title {
                font-size: 1.5rem;
            }

            .session-info {
                padding: 1rem;
            }

            .info-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.3rem;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: 0.5rem;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <?php if ($logout_success): ?>
            <div class="logout-card">
                <div class="success-icon">✅</div>
                <h1 class="logout-title">ออกจากระบบสำเร็จ</h1>
                <p class="logout-message">
                    คุณได้ออกจากระบบเรียบร้อยแล้ว<br>
                    ขอบคุณที่ใช้บริการระบบจัดการข้าวพันธุ์พื้นเมืองเลย
                </p>

                <div class="session-info">
                    <h4>📊 ข้อมูลการใช้งาน</h4>
                    <div class="info-item">
                        <span class="info-label">👤 ผู้ใช้:</span>
                        <span class="info-value"><?php echo htmlspecialchars($admin_name); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">⏰ เวลาการใช้งาน:</span>
                        <span class="info-value"><?php echo $session_time; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">📅 วันที่ออกจากระบบ:</span>
                        <span class="info-value"><?php echo date('d/m/Y H:i:s'); ?></span>
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="login.php" class="btn btn-primary" id="loginBtn">
                        เข้าสู่ระบบอีกครั้ง
                    </a>
                    <a href="../index.php" class="btn btn-secondary">
                        กลับไปหน้าเว็บไซต์หลัก
                    </a>
                </div>

                <div class="footer-text">
                    ระบบจะปิดหน้าต่างนี้อัตโนมัติในอีก <span id="countdown">10</span> วินาที
                </div>
            </div>
        <?php else: ?>
            <div class="logout-card error-card">
                <div class="success-icon error-icon">❌</div>
                <h1 class="logout-title error-title">เกิดข้อผิดพลาด</h1>
                <p class="logout-message">
                    ไม่พบข้อมูลการเข้าสู่ระบบ<br>
                    หรือคุณไม่ได้เข้าสู่ระบบอยู่
                </p>

                <div class="action-buttons">
                    <a href="login.php" class="btn btn-primary">
                        เข้าสู่ระบบ
                    </a>
                    <a href="../index.php" class="btn btn-secondary">
                        กลับไปหน้าเว็บไซต์หลัก
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        <?php if ($logout_success): ?>
            // Countdown และ redirect อัตโนมัติ
            let countdown = 10;
            const countdownElement = document.getElementById('countdown');

            const timer = setInterval(() => {
                countdown--;
                countdownElement.textContent = countdown;

                if (countdown <= 0) {
                    clearInterval(timer);
                    window.location.href = 'login.php';
                }
            }, 1000);

            // หยุด countdown เมื่อผู้ใช้ hover หรือ interact
            document.addEventListener('mousemove', () => {
                clearInterval(timer);
                countdownElement.parentElement.style.display = 'none';
            });

            document.addEventListener('touchstart', () => {
                clearInterval(timer);
                countdownElement.parentElement.style.display = 'none';
            });
        <?php endif; ?>

        // เพิ่ม loading effect เมื่อคลิกปุ่ม
        document.getElementById('loginBtn')?.addEventListener('click', function(e) {
            const btn = this;
            const originalText = btn.innerHTML;

            btn.innerHTML = '<span class="loading"></span>กำลังโหลด...';
            btn.style.pointerEvents = 'none';

            // ให้ browser redirect ตามปกติ
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.style.pointerEvents = 'auto';
            }, 1000);
        });

        // ป้องกันการกดปุ่ม back
        window.history.pushState(null, null, window.location.href);
        window.onpopstate = function() {
            window.history.pushState(null, null, window.location.href);
        };
    </script>
</body>

</html>