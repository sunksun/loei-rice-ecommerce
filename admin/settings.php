<?php
session_start();

// ตรวจสอบการล็อกอินของผู้ดูแลระบบ
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// รวมไฟล์และเชื่อมต่อฐานข้อมูล
require_once '../config/database.php';
$pdo = getDB();

// --- จัดการการอัปเดตข้อมูล ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");

        // วนลูปอัปเดตทุกค่าที่ส่งมาจากฟอร์ม
        foreach ($_POST['settings'] as $key => $value) {
            $stmt->execute([trim($value), $key]);
        }

        $pdo->commit();
        $_SESSION['success_message'] = "บันทึกการตั้งค่าเรียบร้อยแล้ว!";
        header("Location: settings.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "เกิดข้อผิดพลาดในการบันทึก: " . $e->getMessage();
    }
}

// --- ดึงข้อมูลการตั้งค่าทั้งหมด ---
try {
    $stmt = $pdo->query("SELECT * FROM site_settings ORDER BY category, id");
    $settings_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // จัดกลุ่มข้อมูลตาม category
    $settings = [];
    foreach ($settings_raw as $setting) {
        $settings[$setting['category']][] = $setting;
    }
} catch (Exception $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูลการตั้งค่า: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าระบบ - ระบบหลังบ้าน</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f7fa;
            color: #333;
            margin: 0;
        }

        .header {
            background: linear-gradient(135deg, #27ae60, #2d5016);
            color: white;
            padding: 1rem;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 900px;
            margin: auto;
        }

        .container {
            max-width: 900px;
            margin: auto;
            padding: 1.5rem;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
        }

        .card-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #eee;
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.2rem;
            color: #2d5016;
        }

        .card-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1rem;
            background-color: #fff;
        }

        .form-group small {
            color: #666;
            font-size: 0.85em;
        }

        .form-actions {
            text-align: right;
            margin-top: 1.5rem;
        }

        .save-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="header-content">
            <span>⚙️ ตั้งค่าระบบ</span>
            <a href="index.php" style="color:white; text-decoration:none;">กลับหน้าแรก</a>
        </div>
    </header>

    <div class="container">
        <?php
        if (isset($_SESSION['success_message'])) {
            echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
            unset($_SESSION['success_message']);
        }
        if (isset($error_message)) {
            echo '<div class="alert" style="background-color: #f8d7da;">' . htmlspecialchars($error_message) . '</div>';
        }
        ?>

        <form method="POST" action="settings.php">
            <?php foreach ($settings as $category => $items): ?>
                <div class="card">
                    <div class="card-header">
                        <h2><?php echo htmlspecialchars(ucfirst($category)); ?></h2>
                    </div>
                    <div class="card-body">
                        <?php foreach ($items as $item): ?>
                            <div class="form-group">
                                <label for="<?php echo $item['setting_key']; ?>"><?php echo htmlspecialchars($item['description']); ?></label>

                                <?php // --- ส่วนที่แก้ไข: เปลี่ยน Input ตามประเภท --- 
                                ?>

                                <?php if ($item['setting_type'] === 'textarea'): ?>
                                    <textarea id="<?php echo $item['setting_key']; ?>" name="settings[<?php echo $item['setting_key']; ?>]" rows="3"><?php echo htmlspecialchars($item['setting_value']); ?></textarea>

                                <?php elseif ($item['setting_type'] === 'boolean'): ?>
                                    <select id="<?php echo $item['setting_key']; ?>" name="settings[<?php echo $item['setting_key']; ?>]">
                                        <option value="true" <?php echo ($item['setting_value'] == 'true') ? 'selected' : ''; ?>>เปิดใช้งาน</option>
                                        <option value="false" <?php echo ($item['setting_value'] == 'false') ? 'selected' : ''; ?>>ปิดใช้งาน</option>
                                    </select>

                                <?php elseif ($item['setting_key'] === 'timezone'): ?>
                                    <select id="<?php echo $item['setting_key']; ?>" name="settings[<?php echo $item['setting_key']; ?>]">
                                        <option value="Asia/Bangkok" <?php echo ($item['setting_value'] == 'Asia/Bangkok') ? 'selected' : ''; ?>>Asia/Bangkok (UTC+7)</option>
                                        <option value="UTC" <?php echo ($item['setting_value'] == 'UTC') ? 'selected' : ''; ?>>UTC</option>
                                    </select>

                                <?php else: // Default to text input 
                                ?>
                                    <input type="text" id="<?php echo $item['setting_key']; ?>" name="settings[<?php echo $item['setting_key']; ?>]" value="<?php echo htmlspecialchars($item['setting_value']); ?>">
                                <?php endif; ?>

                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="form-actions">
                <button type="submit" name="save_settings" class="save-btn">บันทึกการตั้งค่า</button>
            </div>
        </form>
    </div>
</body>

</html>