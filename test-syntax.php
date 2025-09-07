<?php
// ทดสอบไฟล์หลังแก้ไข syntax
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='th'>
<head>
    <meta charset='UTF-8'>
    <title>ทดสอบ Syntax</title>
</head>
<body>
    <h1>🧪 ทดสอบ Syntax หลังแก้ไข</h1>
    
    <h2>1. ทดสอบ PHP Ternary Operator</h2>";

// ทดสอบ ternary operator ที่แก้ไขแล้ว
$product = array(
    'category_name' => 'ข้าวพื้นเมือง',
    'name' => 'ข้าวเหนียวแดง'
);

echo "<p>หมวดหมู่: " . htmlspecialchars(isset($product['category_name']) && !empty($product['category_name']) ? $product['category_name'] : 'ทั่วไป') . "</p>";

// ทดสอบกรณีที่ไม่มีข้อมูล
$empty_product = array();
echo "<p>หมวดหมู่เปล่า: " . htmlspecialchars(isset($empty_product['category_name']) && !empty($empty_product['category_name']) ? $empty_product['category_name'] : 'ทั่วไป') . "</p>";

echo "
    <h2>2. ทดสอบ JavaScript</h2>
    <p>ตรวจสอบ Console ใน Browser สำหรับ JavaScript errors</p>
    
    <button id='testBtn' onclick='testFunction()'>ทดสอบ JavaScript</button>
    <div id='result'></div>
    
    <script src='assets/js/main.js'></script>
    <script>
        function testFunction() {
            console.log('Testing JavaScript after syntax fix...');
            
            // ทดสอบ code ที่แก้ไขแล้วใน main.js
            const result = document.getElementById('result');
            result.innerHTML = '✅ JavaScript ทำงานได้ปกติ';
            
            // ทดสอบฟังก์ชันจาก main.js
            if (typeof window.LoeiRice !== 'undefined') {
                result.innerHTML += '<br>✅ LoeiRice object พร้อมใช้งาน';
                
                // ทดสอบ notification
                if (typeof window.LoeiRice.ui.showNotification === 'function') {
                    window.LoeiRice.ui.showNotification('ทดสอบ notification สำเร็จ!', 'success');
                    result.innerHTML += '<br>✅ Notification ทำงานได้';
                }
            } else {
                result.innerHTML += '<br>❌ LoeiRice object ไม่พบ';
            }
        }
    </script>
    
    <h2>3. สถานะการแก้ไข</h2>
    <ul>
        <li>✅ แก้ไข PHP ternary operator ใน index.php</li>
        <li>✅ แก้ไข JavaScript optional chaining (?.)</li>
        <li>✅ เปลี่ยน dots[currentSlide]?.classList เป็น if (dots[currentSlide]) dots[currentSlide].classList</li>
        <li>✅ เปลี่ยน this?.classList เป็น if (this) this.classList</li>
    </ul>
    
    <hr>
    <p><a href='index.php'>← กลับหน้าแรก</a></p>
</body>
</html>";
?>