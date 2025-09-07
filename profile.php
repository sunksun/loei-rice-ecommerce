<?php
// profile.php - หน้าโปรไฟล์ผู้ใช้ (ดูและแก้ไขในหน้าเดียว)
session_start();
require_once 'config/config.php';
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$success = false;
$error = '';

try {
    $conn = getDB();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_profile'])) {
        // รับค่าจากฟอร์ม
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $profile_image = null;
        $user_id = $_SESSION['user_id'];

        // ตรวจสอบอีเมลซ้ำ (ยกเว้นของตัวเอง)
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->execute([$email, $user_id]);
        if ($check->fetch()) {
            $error = 'อีเมลนี้ถูกใช้ไปแล้ว';
        } else {
            // อัปโหลดรูปโปรไฟล์ถ้ามี
            if (!empty($_FILES['profile_image']['name'])) {
                $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($ext, $allowed)) {
                    $newname = 'profile_' . $user_id . '_' . time() . '.' . $ext;
                    $target = 'uploads/profiles/' . $newname;
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target)) {
                        $profile_image = $newname;
                    } else {
                        $error = 'อัปโหลดรูปภาพไม่สำเร็จ';
                    }
                } else {
                    $error = 'ไฟล์รูปต้องเป็น jpg, jpeg, png หรือ gif';
                }
            }
            if (!$error) {
                $sql = "UPDATE users SET first_name=?, last_name=?, email=?";
                $params = [$first_name, $last_name, $email];
                if ($profile_image) {
                    $sql .= ", profile_image=?";
                    $params[] = $profile_image;
                }
                $sql .= " WHERE id=?";
                $params[] = $user_id;
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                $success = true;
            }
        }
    }
    // ดึงข้อมูลล่าสุด
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND status = 'active'");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
} catch (Exception $e) {
    // กำหนดค่าเริ่มต้นสำหรับ user หากเกิดข้อผิดพลาด
    $user = array(
        'id' => '',
        'email' => '',
        'first_name' => '',
        'last_name' => '',
        'profile_image' => '',
        'created_at' => ''
    );
    $error = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
}

function h($str)
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ของฉัน | ข้าวพื้นเมืองเลย</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <style>
        body {
            background: #f8f9fa;
        }

        .profile-container {
            max-width: 600px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.07);
            padding: 2rem;
        }

        .profile-avatar {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #27ae60;
            margin: 0 auto 1rem;
            overflow: hidden;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .profile-name {
            font-size: 1.3rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 0.3rem;
        }

        .profile-email {
            text-align: center;
            color: #888;
            margin-bottom: 1.2rem;
        }

        .profile-info {
            margin-bottom: 1.5rem;
        }

        .profile-label {
            color: #888;
            font-size: 0.95rem;
        }

        .profile-value {
            font-size: 1.05rem;
            font-weight: 500;
        }

        .btn-edit {
            display: block;
            width: 100%;
            margin-top: 1.5rem;
        }

        .form-section {
            margin-top: 2rem;
        }

        .alert {
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <div class="profile-container">
        <?php if ($success): ?>
            <div class="alert alert-success">บันทึกข้อมูลเรียบร้อยแล้ว</div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>
        <div class="profile-avatar">
            <?php if (!empty($user['profile_image'])): ?>
                <img src="uploads/profiles/<?php echo h($user['profile_image']); ?>" alt="Avatar">
            <?php else: ?>
                <?php echo strtoupper(mb_substr($user['first_name'], 0, 1, 'UTF-8')); ?>
            <?php endif; ?>
        </div>
        <div class="profile-name"><?php echo h($user['first_name'] . ' ' . $user['last_name']); ?></div>
        <div class="profile-email"><?php echo h($user['email']); ?></div>
        <div class="profile-info">
            <div class="profile-label">รหัสผู้ใช้</div>
            <div class="profile-value"><?php echo h($user['id']); ?></div>
            <div class="profile-label">อีเมล</div>
            <div class="profile-value"><?php echo h($user['email']); ?></div>
            <div class="profile-label">ชื่อ</div>
            <div class="profile-value"><?php echo h($user['first_name']); ?></div>
            <div class="profile-label">นามสกุล</div>
            <div class="profile-value"><?php echo h($user['last_name']); ?></div>
            <div class="profile-label">วันที่สมัครสมาชิก</div>
            <div class="profile-value"><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></div>
            <div class="profile-label">สถานะ</div>
            <div class="profile-value"><?php echo h($user['status']); ?></div>
            <div class="profile-label">สิทธิ์การใช้งาน</div>
            <div class="profile-value"><?php echo h($user['permission'] ?? '-'); ?></div>
            <div class="profile-label">เข้าสู่ระบบล่าสุด</div>
            <div class="profile-value"><?php echo !empty($user['last_login']) ? date('d/m/Y H:i', strtotime($user['last_login'])) : '-'; ?></div>
        </div>
        <form class="form-section" method="post" enctype="multipart/form-data" autocomplete="off">
            <h5 class="mb-3">แก้ไขโปรไฟล์</h5>
            <div class="mb-3">
                <label class="form-label">ชื่อ</label>
                <input type="text" name="first_name" class="form-control" value="<?php echo h($user['first_name']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">นามสกุล</label>
                <input type="text" name="last_name" class="form-control" value="<?php echo h($user['last_name']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">อีเมล</label>
                <input type="email" name="email" class="form-control" value="<?php echo h($user['email']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">รูปโปรไฟล์ (jpg, png, gif)</label>
                <input type="file" name="profile_image" class="form-control">
            </div>
            <button type="submit" name="edit_profile" class="btn btn-success">บันทึกการเปลี่ยนแปลง</button>
        </form>
        <a href="logout.php" class="btn btn-outline-danger btn-edit">ออกจากระบบ</a>
    </div>
</body>

</html>