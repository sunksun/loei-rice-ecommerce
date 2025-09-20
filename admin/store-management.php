<?php
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';
require_once '../classes/MultiStoreManager.php';

$store_manager = new StoreManager($_SESSION['admin_id']);

// ตรวจสอบสิทธิ์ Super Admin
if (!$store_manager->isSuperAdmin()) {
    header('Location: index.php?error=access_denied');
    exit();
}

$message = '';
$error = '';

// จัดการ CRUD ร้านค้า
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create_store':
                $store_data = [
                    'store_name' => trim($_POST['store_name']),
                    'store_code' => trim($_POST['store_code']),
                    'owner_name' => trim($_POST['owner_name']),
                    'phone' => trim($_POST['phone']),
                    'email' => trim($_POST['email']),
                    'address' => trim($_POST['address']),
                    'description' => trim($_POST['description'])
                ];
                
                if (empty($store_data['store_name']) || empty($store_data['store_code'])) {
                    throw new Exception('กรุณากรอกชื่อร้านและรหัสร้าน');
                }
                
                $store_id = $store_manager->createStore($store_data);
                $message = 'สร้างร้าน "' . $store_data['store_name'] . '" เรียบร้อยแล้ว';
                break;
                
            case 'update_store':
                $store_id = intval($_POST['store_id']);
                $update_data = [
                    'store_name' => trim($_POST['store_name']),
                    'owner_name' => trim($_POST['owner_name']),
                    'phone' => trim($_POST['phone']),
                    'email' => trim($_POST['email']),
                    'address' => trim($_POST['address']),
                    'description' => trim($_POST['description']),
                    'status' => $_POST['status']
                ];
                
                $store_manager->updateStore($store_id, $update_data);
                $message = 'อัพเดตข้อมูลร้านเรียบร้อยแล้ว';
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// ดึงรายการร้านทั้งหมด
$stores = $store_manager->getAllStores();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการร้านค้า - Super Admin</title>
    
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
        }
        
        .header {
            background: linear-gradient(135deg, #27ae60, #2d5016);
            color: white;
            padding: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: auto;
        }
        
        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .nav-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .nav-link {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.1);
            transition: background 0.3s;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #27ae60;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #dc3545;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #27ae60;
        }
        
        .card-title {
            color: #2d5016;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #27ae60;
            color: white;
        }
        
        .btn-primary:hover {
            background: #219a52;
            transform: translateY(-2px);
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
        .stores-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }
        
        .store-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
            border: 1px solid #e8f5e8;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .store-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }
        
        .store-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .store-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2d5016;
        }
        
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-badge.active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-badge.suspended {
            background: #fff3cd;
            color: #856404;
        }
        
        .store-info {
            margin-bottom: 1.5rem;
        }
        
        .store-info p {
            margin-bottom: 0.5rem;
            color: #666;
        }
        
        .store-info strong {
            color: #333;
        }
        
        .store-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #27ae60;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }
        
        .store-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }
        
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 12px;
            padding: 2rem;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .modal-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2d5016;
        }
        
        .close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
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
        
        select.form-control {
            cursor: pointer;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .stores-grid {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-buttons {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .store-actions {
                justify-content: center;
            }
            
            .modal-content {
                width: 95%;
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="header-content">
            <h1>🏪 จัดการร้านค้า</h1>
            <div class="nav-buttons">
                <span>👤 <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Super Admin'); ?></span>
                <a href="index.php" class="nav-link">🏠 หน้าแรก</a>
                <a href="admin-management.php" class="nav-link">👥 จัดการ Admin</a>
                <a href="logout.php" class="nav-link">🚪 ออกจากระบบ</a>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success">
                ✅ <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">รายการร้านค้าทั้งหมด</h2>
                <button onclick="showAddStoreModal()" class="btn btn-primary">
                    ➕ เพิ่มร้านใหม่
                </button>
            </div>

            <div class="stores-grid">
                <?php foreach ($stores as $store): ?>
                <div class="store-card">
                    <div class="store-header">
                        <h3 class="store-name"><?php echo htmlspecialchars($store['store_name']); ?></h3>
                        <span class="status-badge <?php echo $store['status']; ?>">
                            <?php echo ucfirst($store['status']); ?>
                        </span>
                    </div>

                    <div class="store-info">
                        <p><strong>🏷️ รหัสร้าน:</strong> <?php echo htmlspecialchars($store['store_code']); ?></p>
                        <p><strong>👤 เจ้าของ:</strong> <?php echo htmlspecialchars($store['owner_name'] ?: 'ไม่ระบุ'); ?></p>
                        <p><strong>📧 อีเมล:</strong> <?php echo htmlspecialchars($store['email'] ?: 'ไม่ระบุ'); ?></p>
                        <p><strong>📱 โทร:</strong> <?php echo htmlspecialchars($store['phone'] ?: 'ไม่ระบุ'); ?></p>
                    </div>

                    <div class="store-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $store['admin_count']; ?></div>
                            <div class="stat-label">Admin</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $store['product_count']; ?></div>
                            <div class="stat-label">สินค้า</div>
                        </div>
                    </div>

                    <div class="store-actions">
                        <button onclick="editStore(<?php echo $store['id']; ?>)" class="btn btn-warning btn-sm">
                            ✏️ แก้ไข
                        </button>
                        <button onclick="viewStoreDetails(<?php echo $store['id']; ?>)" class="btn btn-info btn-sm">
                            👁️ รายละเอียด
                        </button>
                        <?php if ($store['status'] === 'active'): ?>
                            <button onclick="changeStoreStatus(<?php echo $store['id']; ?>, 'suspended')" class="btn btn-danger btn-sm">
                                ⏸️ ระงับ
                            </button>
                        <?php else: ?>
                            <button onclick="changeStoreStatus(<?php echo $store['id']; ?>, 'active')" class="btn btn-success btn-sm">
                                ▶️ เปิดใช้
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Modal เพิ่มร้านใหม่ -->
    <div id="addStoreModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">เพิ่มร้านใหม่</h3>
                <button class="close" onclick="closeModal('addStoreModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create_store">
                
                <div class="form-group">
                    <label class="form-label">ชื่อร้าน *</label>
                    <input type="text" name="store_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">รหัสร้าน *</label>
                    <input type="text" name="store_code" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">ชื่อเจ้าของร้าน</label>
                    <input type="text" name="owner_name" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="form-label">เบอร์โทรศัพท์</label>
                    <input type="tel" name="phone" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="form-label">อีเมล</label>
                    <input type="email" name="email" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="form-label">ที่อยู่</label>
                    <textarea name="address" class="form-control"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">คำอธิบายร้าน</label>
                    <textarea name="description" class="form-control"></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closeModal('addStoreModal')" class="btn" style="background: #6c757d; color: white;">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">สร้างร้าน</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddStoreModal() {
            document.getElementById('addStoreModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function editStore(storeId) {
            // TODO: Implement edit functionality
            alert('ฟีเจอร์แก้ไขร้าน - จะพัฒนาในขั้นตอนถัดไป');
        }

        function viewStoreDetails(storeId) {
            // TODO: Implement view details functionality
            alert('ฟีเจอร์ดูรายละเอียดร้าน - จะพัฒนาในขั้นตอนถัดไป');
        }

        function changeStoreStatus(storeId, newStatus) {
            if (confirm('คุณต้องการเปลี่ยนสถานะร้านนี้หรือไม่?')) {
                // TODO: Implement status change via AJAX
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="update_store">
                    <input type="hidden" name="store_id" value="${storeId}">
                    <input type="hidden" name="status" value="${newStatus}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // ปิด modal เมื่อคลิกข้างนอก
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>