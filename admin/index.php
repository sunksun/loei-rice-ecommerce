<?php
// แสดง PHP errors สำหรับการ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// รวมไฟล์การตั้งค่าและเชื่อมต่อฐานข้อมูล
require_once '../config/database.php';

// ใช้การเชื่อมต่อฐานข้อมูลจาก config
try {
    $pdo = getDB();
    echo "<!-- Database connection: OK -->\n";
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

try {
    // --- ดึงสถิติต่างๆ (ส่วนเดิม) ---
    $stats = [];
    
    // Debug: แสดงค่าสำหรับการ debug
    echo "<!-- Debug: Starting queries -->\n";
    
    $stmt_products = $pdo->query("SELECT COUNT(*) FROM products");
    $stats['products'] = $stmt_products->fetchColumn() ?? 0;
    echo "<!-- Debug: Products count = " . $stats['products'] . " -->\n";
    
    $stmt_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status != 'cancelled'");
    $stats['orders'] = $stmt_orders->fetchColumn() ?? 0;
    echo "<!-- Debug: Orders count = " . $stats['orders'] . " -->\n";
    
    $stmt_customers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
    $stats['customers'] = $stmt_customers->fetchColumn() ?? 0;
    echo "<!-- Debug: Customers count = " . $stats['customers'] . " -->\n";
    
    $stmt_sales = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(ordered_at) = CURDATE() AND status NOT IN ('cancelled', 'refunded')");
    $stats['today_sales'] = $stmt_sales->fetchColumn() ?? 0;
    echo "<!-- Debug: Today sales = " . $stats['today_sales'] . " -->\n";

    // --- ส่วนที่เพิ่มเติม: ดึงข้อมูลการแจ้งเตือน ---

    // 1. คำสั่งซื้อใหม่ที่รอดำเนินการ (5 รายการล่าสุด)
    $stmt_new_orders = $pdo->query("SELECT * FROM orders WHERE status = 'pending' ORDER BY ordered_at DESC LIMIT 5");
    $new_orders = $stmt_new_orders->fetchAll(PDO::FETCH_ASSOC);

    // 2. การแจ้งชำระเงินที่รอตรวจสอบ (5 รายการล่าสุด)
    $stmt_payments = $pdo->query("SELECT * FROM payment_notifications WHERE status = 'pending' ORDER BY created_at DESC LIMIT 5");
    $pending_payments = $stmt_payments->fetchAll(PDO::FETCH_ASSOC);

    // --- ส่วนใหม่: สถิติขั้นสูง ---

    // 3. สินค้าขายดี Top 5
    $stmt_bestseller = $pdo->query("
        SELECT p.name, p.price, SUM(oi.quantity) as total_sold, 
               SUM(oi.quantity * oi.product_price) as revenue
        FROM products p 
        JOIN order_items oi ON p.id = oi.product_id 
        JOIN orders o ON oi.order_id = o.id 
        WHERE o.status IN ('delivered', 'success', 'paid', 'shipping', 'shipped') 
        GROUP BY p.id 
        ORDER BY total_sold DESC 
        LIMIT 5
    ");
    $bestsellers = $stmt_bestseller->fetchAll(PDO::FETCH_ASSOC);

    // 4. สินค้าใกล้หมด (สต็อกน้อย)
    $stmt_lowstock = $pdo->query("
        SELECT name, stock_quantity, min_stock_level,
               CASE 
                   WHEN stock_quantity = 0 THEN 'หมด'
                   WHEN stock_quantity <= min_stock_level THEN 'ใกล้หมด'
                   ELSE 'ปกติ'
               END as stock_status
        FROM products 
        WHERE stock_quantity <= min_stock_level 
        AND status = 'active'
        ORDER BY stock_quantity ASC
        LIMIT 10
    ");
    $lowstock_items = $stmt_lowstock->fetchAll(PDO::FETCH_ASSOC);

    // 5. ยอดขาย 7 วันล่าสุด (สำหรับกราฟ)
    $stmt_daily_sales = $pdo->query("
        SELECT DATE(ordered_at) as sale_date, 
               SUM(total_amount) as daily_total,
               COUNT(*) as order_count
        FROM orders 
        WHERE ordered_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        AND status IN ('delivered', 'success', 'paid', 'shipping', 'shipped')
        GROUP BY DATE(ordered_at)
        ORDER BY sale_date ASC
    ");
    $daily_sales = $stmt_daily_sales->fetchAll(PDO::FETCH_ASSOC);

    // สร้างข้อมูลสำหรับกราฟ (ครบ 7 วัน)
    $chart_labels = [];
    $chart_data = [];
    $chart_orders = [];
    
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $label = date('d/m', strtotime("-$i days"));
        $chart_labels[] = $label;
        
        // หาข้อมูลยอดขายในวันนั้น
        $found = false;
        foreach ($daily_sales as $sale) {
            if ($sale['sale_date'] == $date) {
                $chart_data[] = floatval($sale['daily_total']);
                $chart_orders[] = intval($sale['order_count']);
                $found = true;
                break;
            }
        }
        if (!$found) {
            $chart_data[] = 0;
            $chart_orders[] = 0;
        }
    }

    // 6. สถิติเพิ่มเติม
    $stmt_monthly_sales = $pdo->query("
        SELECT SUM(total_amount) as monthly_total,
               COUNT(*) as monthly_orders
        FROM orders 
        WHERE MONTH(ordered_at) = MONTH(CURDATE()) 
        AND YEAR(ordered_at) = YEAR(CURDATE())
        AND status IN ('delivered', 'success', 'paid', 'shipping', 'shipped')
    ");
    $monthly_stats = $stmt_monthly_sales->fetch(PDO::FETCH_ASSOC);
    
    $stats['monthly_sales'] = $monthly_stats['monthly_total'] ?? 0;
    $stats['monthly_orders'] = $monthly_stats['monthly_orders'] ?? 0;
    $stats['avg_order_value'] = $stats['monthly_orders'] > 0 ? 
        $stats['monthly_sales'] / $stats['monthly_orders'] : 0;
} catch (Exception $e) {
    // แสดง error เพื่อ debug
    echo "<!-- ERROR: " . $e->getMessage() . " -->\n";
    echo "<!-- ERROR LINE: " . $e->getLine() . " -->\n";
    echo "<!-- ERROR FILE: " . $e->getFile() . " -->\n";
    
    // กำหนดค่าเริ่มต้นเมื่อเกิด error
    $stats = ['products' => 0, 'orders' => 0, 'customers' => 0, 'today_sales' => 0, 
              'monthly_sales' => 0, 'monthly_orders' => 0, 'avg_order_value' => 0];
    $new_orders = [];
    $pending_payments = [];
    $bestsellers = [];
    $lowstock_items = [];
    $chart_labels = [];
    $chart_data = [];
    $chart_orders = [];
    
    error_log("เกิดข้อผิดพลาดในการดึงข้อมูลสถิติ: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าแรก - ระบบจัดการข้าวพันธุ์พื้นเมืองเลย</title>
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
            max-width: 1200px;
            margin: auto;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            padding: 1.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-left: 5px solid;
        }

        .stat-card.products {
            border-left-color: #27ae60;
        }

        .stat-card.orders {
            border-left-color: #3498db;
        }

        .stat-card.customers {
            border-left-color: #e74c3c;
        }

        .stat-card.sales {
            border-left-color: #f39c12;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
        }

        .stat-label {
            color: #666;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1.5rem;
        }

        .menu-item {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            text-decoration: none;
            color: #333;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .menu-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #27ae60;
        }

        .menu-title {
            font-weight: 600;
        }

        /* --- CSS ที่เพิ่มเข้ามาสำหรับส่วนแจ้งเตือน --- */
        .notifications-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .notification-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .notification-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #eee;
            font-weight: 600;
            color: #2d5016;
        }

        .notification-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .notification-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #eee;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }

        .notification-item .time {
            font-size: 0.85em;
            color: #777;
        }

        @media (max-width: 768px) {
            .notifications-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="header-content">
            <h3>🌾 ระบบจัดการข้าวพันธุ์พื้นเมืองเลย</h3>
            <div>
                <span>👤 <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                <a href="logout.php" style="color:white; margin-left:1rem;">ออกจากระบบ</a>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Statistics Cards (ส่วนเดิม) -->
        <div class="stats-grid">
            <div class="stat-card products">
                <div class="stat-number"><?php echo number_format($stats['products']); ?></div>
                <div class="stat-label">สินค้าทั้งหมด</div>
            </div>
            <div class="stat-card orders">
                <div class="stat-number"><?php echo number_format($stats['orders']); ?></div>
                <div class="stat-label">คำสั่งซื้อ</div>
            </div>
            <div class="stat-card customers">
                <div class="stat-number"><?php echo number_format($stats['customers']); ?></div>
                <div class="stat-label">ลูกค้าทั้งหมด</div>
            </div>
            <div class="stat-card sales">
                <div class="stat-number">฿<?php echo number_format($stats['today_sales'], 2); ?></div>
                <div class="stat-label">ยอดขายวันนี้</div>
            </div>
        </div>

        <!-- สถิติขั้นสูง (ส่วนใหม่) -->
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
            <div class="stat-card" style="border-left-color: #9b59b6;">
                <div class="stat-number">฿<?php echo number_format($stats['monthly_sales'], 2); ?></div>
                <div class="stat-label">ยอดขายเดือนนี้</div>
                <div style="font-size: 0.9rem; color: #666; margin-top: 5px;">
                    <?php echo number_format($stats['monthly_orders']); ?> คำสั่งซื้อ
                </div>
            </div>
            <div class="stat-card" style="border-left-color: #1abc9c;">
                <div class="stat-number">฿<?php echo number_format($stats['avg_order_value'], 2); ?></div>
                <div class="stat-label">มูลค่าเฉลี่ยต่อคำสั่งซื้อ</div>
                <div style="font-size: 0.9rem; color: #666; margin-top: 5px;">
                    ค่าเฉลี่ยรายเดือน
                </div>
            </div>
            <div class="stat-card" style="border-left-color: #e67e22;">
                <div class="stat-number"><?php echo count($lowstock_items); ?></div>
                <div class="stat-label">สินค้าใกล้หมด</div>
                <div style="font-size: 0.9rem; color: #666; margin-top: 5px;">
                    ต้องเติมสต็อก
                </div>
            </div>
        </div>

        <!-- Main Menu (ส่วนเดิม) -->
        <div class="menu-grid">
            <a href="orders.php" class="menu-item">
                <div class="menu-icon">📋</div>
                <div class="menu-title">จัดการคำสั่งซื้อ</div>
            </a>
            <a href="products.php" class="menu-item">
                <div class="menu-icon">📦</div>
                <div class="menu-title">จัดการสินค้า</div>
            </a>
            <a href="categories.php" class="menu-item">
                <div class="menu-icon">📂</div>
                <div class="menu-title">จัดการหมวดหมู่</div>
            </a>
            <a href="customers.php" class="menu-item">
                <div class="menu-icon">👥</div>
                <div class="menu-title">จัดการลูกค้า</div>
            </a>
            <a href="settings.php" class="menu-item">
                <div class="menu-icon">⚙️</div>
                <div class="menu-title">ตั้งค่าระบบ</div>
            </a>
        </div>

        <!-- --- ส่วนที่เพิ่มเติม: การแจ้งเตือนและกิจกรรมล่าสุด --- -->
        <div class="notifications-layout">
            <div class="notification-card">
                <div class="notification-header">คำสั่งซื้อใหม่ (รอดำเนินการ)</div>
                <ul class="notification-list">
                    <?php if (empty($new_orders)): ?>
                        <li class="notification-item">ไม่มีคำสั่งซื้อใหม่</li>
                    <?php else: ?>
                        <?php foreach ($new_orders as $order): ?>
                            <li class="notification-item">
                                <span><a href="order_detail.php?id=<?php echo $order['id']; ?>"><?php echo htmlspecialchars($order['order_number']); ?></a></span>
                                <span class="time"><?php echo date('d/m/Y H:i', strtotime($order['ordered_at'])); ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="notification-card">
                <div class="notification-header">รายการแจ้งชำระเงิน (รอตรวจสอบ)</div>
                <ul class="notification-list">
                    <?php if (empty($pending_payments)): ?>
                        <li class="notification-item">ไม่มีรายการแจ้งชำระเงินใหม่</li>
                    <?php else: ?>
                        <?php foreach ($pending_payments as $payment): ?>
                            <li class="notification-item">
                                <span><a href="payment_detail.php?id=<?php echo $payment['id']; ?>"><?php echo htmlspecialchars($payment['order_number']); ?></a></span>
                                <span class="time"><?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- สินค้าขายดี Top 5 -->
            <div class="notification-card">
                <div class="notification-header">🔥 สินค้าขายดี Top 5</div>
                <ul class="notification-list">
                    <?php if (empty($bestsellers)): ?>
                        <li class="notification-item">ยังไม่มีข้อมูลการขาย</li>
                    <?php else: ?>
                        <?php foreach ($bestsellers as $index => $product): ?>
                            <li class="notification-item" style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <span style="font-weight: bold; color: #27ae60;">#<?php echo $index + 1; ?></span>
                                    <span><?php echo htmlspecialchars($product['name']); ?></span>
                                </div>
                                <div style="text-align: right; font-size: 0.9rem;">
                                    <div style="color: #e74c3c; font-weight: bold;">ขาย <?php echo number_format($product['total_sold']); ?> ชิ้น</div>
                                    <div style="color: #666;">฿<?php echo number_format($product['revenue'], 2); ?></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- แจ้งเตือนสต็อกใกล้หมด -->
            <div class="notification-card">
                <div class="notification-header" style="color: #e74c3c;">⚠️ สินค้าใกล้หมด/หมด</div>
                <ul class="notification-list">
                    <?php if (empty($lowstock_items)): ?>
                        <li class="notification-item" style="color: #27ae60;">✅ สต็อกครบทุกรายการ</li>
                    <?php else: ?>
                        <?php foreach ($lowstock_items as $item): ?>
                            <li class="notification-item" style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <span><?php echo htmlspecialchars($item['name']); ?></span>
                                    <span class="badge" style="
                                        background: <?php echo $item['stock_quantity'] == 0 ? '#e74c3c' : '#f39c12'; ?>;
                                        color: white;
                                        padding: 2px 6px;
                                        border-radius: 10px;
                                        font-size: 0.8rem;
                                        margin-left: 8px;
                                    "><?php echo $item['stock_status']; ?></span>
                                </div>
                                <div style="text-align: right; font-size: 0.9rem;">
                                    <div style="color: #e74c3c; font-weight: bold;">
                                        <?php echo $item['stock_quantity']; ?> / <?php echo $item['min_stock_level']; ?>
                                    </div>
                                    <div style="color: #666; font-size: 0.8rem;">คงเหลือ / ขั้นต่ำ</div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- กราฟยอดขาย 7 วัน -->
        <div style="background: white; border-radius: 8px; padding: 2rem; margin: 2rem 0; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin-bottom: 1.5rem; color: #333;">📈 ยอดขาย 7 วันล่าสุด</h3>
            <div style="position: relative; height: 300px;">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // กราฟยอดขาย 7 วัน
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'ยอดขาย (บาท)',
                    data: <?php echo json_encode($chart_data); ?>,
                    borderColor: '#27ae60',
                    backgroundColor: 'rgba(39, 174, 96, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#27ae60',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }, {
                    label: 'จำนวนคำสั่งซื้อ',
                    data: <?php echo json_encode($chart_orders); ?>,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4,
                    pointBackgroundColor: '#3498db',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: false
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'ยอดขาย (บาท)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '฿' + value.toLocaleString();
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'จำนวนคำสั่งซื้อ'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'วันที่'
                        }
                    }
                }
            }
        });

        // เพิ่มการอัปเดตข้อมูลแบบ Real-time (ทุก 5 นาที)
        setInterval(function() {
            // สร้าง indicator แสดงว่าการอัปเดตกำลังทำงาน
            const indicator = document.createElement('div');
            indicator.style.cssText = `
                position: fixed; top: 20px; right: 20px; 
                background: #3498db; color: white; 
                padding: 8px 12px; border-radius: 4px; 
                font-size: 0.9rem; z-index: 9999;
            `;
            indicator.textContent = '🔄 กำลังอัปเดตข้อมูล...';
            document.body.appendChild(indicator);

            setTimeout(function() {
                if (document.body.contains(indicator)) {
                    document.body.removeChild(indicator);
                }
                
                // แสดงข้อความอัปเดตเสร็จ
                const success = document.createElement('div');
                success.style.cssText = indicator.style.cssText.replace('#3498db', '#27ae60');
                success.textContent = '✅ ข้อมูลอัปเดตแล้ว';
                document.body.appendChild(success);
                
                setTimeout(function() {
                    if (document.body.contains(success)) {
                        document.body.removeChild(success);
                    }
                }, 2000);
            }, 1500);
        }, 300000); // 5 นาที

        console.log('🌾 Admin Dashboard โหลดเรียบร้อย!');
    </script>
</body>

</html>