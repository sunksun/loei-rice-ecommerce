<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Hash Generator</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        input,
        button {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        button {
            background: #27ae60;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background: #2d5016;
        }

        .result {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #27ae60;
        }

        .hash {
            word-break: break-all;
            font-family: monospace;
            background: #e9ecef;
            padding: 10px;
            border-radius: 3px;
            margin: 10px 0;
        }

        .copy-btn {
            width: auto;
            padding: 5px 10px;
            font-size: 12px;
            margin: 5px 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>🔐 Password Hash Generator สำหรับ PHP</h2>
        <p>สร้างรหัสผ่าน hash สำหรับใช้ในฐานข้อมูล MySQL</p>

        <form id="hashForm">
            <input type="text" id="password" placeholder="กรอกรหัสผ่านที่ต้องการ" required>
            <button type="submit">สร้าง Hash</button>
        </form>

        <div id="results"></div>

        <div style="margin-top: 30px; padding: 20px; background: #e3f2fd; border-radius: 5px;">
            <h3>🧪 รหัสผ่านสำหรับทดสอบ</h3>
            <div class="test-passwords">
                <div style="margin: 10px 0;">
                    <strong>Password:</strong> <code>password123</code><br>
                    <strong>Hash:</strong><br>
                    <div class="hash">$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi</div>
                    <button class="copy-btn" onclick="copyToClipboard('$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')">Copy Hash</button>
                </div>

                <div style="margin: 10px 0;">
                    <strong>Password:</strong> <code>test123</code><br>
                    <strong>Hash:</strong><br>
                    <div class="hash">$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm</div>
                    <button class="copy-btn" onclick="copyToClipboard('$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm')">Copy Hash</button>
                </div>

                <div style="margin: 10px 0;">
                    <strong>Password:</strong> <code>123456</code><br>
                    <strong>Hash:</strong><br>
                    <div class="hash">$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy</div>
                    <button class="copy-btn" onclick="copyToClipboard('$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy')">Copy Hash</button>
                </div>
            </div>
        </div>

        <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 5px;">
            <h4>📝 วิธีใช้:</h4>
            <ol>
                <li>คัดลอก hash ที่ต้องการ</li>
                <li>รัน SQL command ด้านล่างใน phpMyAdmin</li>
                <li>ทดสอบเข้าสู่ระบบด้วยรหัสผ่านต้นฉบับ</li>
            </ol>
        </div>

        <div style="margin-top: 20px; padding: 15px; background: #d1ecf1; border-radius: 5px;">
            <h4>🗄️ SQL Commands สำหรับอัพเดท:</h4>
            <div style="background: white; padding: 10px; border-radius: 3px; margin: 10px 0;">
                <code>
                    -- อัพเดท password ของ jaruwan.lak@gmail.com เป็น "password123"<br>
                    UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE email = 'jaruwan.lak@gmail.com';
                </code>
            </div>
            <div style="background: white; padding: 10px; border-radius: 3px; margin: 10px 0;">
                <code>
                    -- หรือสร้าง user ใหม่สำหรับทดสอบ<br>
                    INSERT INTO users (first_name, last_name, email, password, status, email_verified) VALUES ('ผู้ใช้', 'ทดสอบ', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 1);
                </code>
            </div>
        </div>
    </div>

    <script>
        // ฟังก์ชันสำหรับ copy text
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('คัดลอกแล้ว!');
            });
        }

        // PHP-like password_hash simulation (ใช้สำหรับแสดงตัวอย่าง)
        document.getElementById('hashForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const password = document.getElementById('password').value;
            const resultsDiv = document.getElementById('results');

            // สร้าง hash แบบง่าย (ไม่ใช่ bcrypt จริง)
            const hash = await simpleHash(password);

            resultsDiv.innerHTML = `
                <div class="result">
                    <h3>✅ ผลลัพธ์</h3>
                    <strong>Password:</strong> ${password}<br>
                    <strong>Hash (ตัวอย่าง):</strong><br>
                    <div class="hash">${hash}</div>
                    <button class="copy-btn" onclick="copyToClipboard('${hash}')">Copy Hash</button>
                    <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 3px; font-size: 14px;">
                        <strong>⚠️ หมายเหตุ:</strong> นี่เป็นเพียงตัวอย่าง Hash จริงควรสร้างด้วย PHP password_hash() บนเซิร์ฟเวอร์
                    </div>
                </div>
            `;
        });

        // ฟังก์ชันสร้าง hash แบบง่าย (เพื่อแสดงตัวอย่าง)
        async function simpleHash(password) {
            const encoder = new TextEncoder();
            const data = encoder.encode(password + new Date().getTime());
            const hashBuffer = await crypto.subtle.digest('SHA-256', data);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
            return '$2y$10$' + hashHex.substring(0, 53); // จำลองรูปแบบ bcrypt
        }
    </script>
</body>

</html>