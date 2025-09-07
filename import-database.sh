#!/bin/bash
# สคริปต์ Import ฐานข้อมูล loei_rice_ecommerce

echo "🔧 Import ฐานข้อมูล loei_rice_ecommerce"
echo "================================="

# เช็คว่าไฟล์ SQL มีอยู่หรือไม่
if [ ! -f "loei_rice_ecommerce.sql" ]; then
    echo "❌ ไม่พบไฟล์ loei_rice_ecommerce.sql"
    exit 1
fi

# Import ฐานข้อมูล
echo "📥 กำลัง Import ฐานข้อมูล..."
/Applications/XAMPP/xamppfiles/bin/mysql -u root -p < loei_rice_ecommerce.sql

if [ $? -eq 0 ]; then
    echo "✅ Import ฐานข้อมูลสำเร็จ!"
else
    echo "❌ Import ฐานข้อมูลล้มเหลว"
    exit 1
fi

echo "🔍 ตรวจสอบฐานข้อมูลที่สร้างแล้ว:"
/Applications/XAMPP/xamppfiles/bin/mysql -u root -p -e "SHOW DATABASES LIKE 'loei_rice_ecommerce';"

echo "📋 ตรวจสอบตารางในฐานข้อมูล:"
/Applications/XAMPP/xamppfiles/bin/mysql -u root -p -e "USE loei_rice_ecommerce; SHOW TABLES;"

echo "✅ เสร็จสิ้น!"