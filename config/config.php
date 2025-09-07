<?php

/**
 * Main Configuration File
 * ไฟล์การตั้งค่าหลักของระบบอีคอมเมิร์ซข้าวพันธุ์พื้นเมืองเลย
 * 
 * @author Loei Rice E-commerce Team
 * @version 1.0
 */

// ป้องกันการเข้าถึงไฟล์โดยตรง
if (!defined('SYSTEM_INIT')) {
    define('SYSTEM_INIT', true);
}

// =================================================================
// การตั้งค่าสภาพแวดล้อม (Environment)
// =================================================================

// ตั้งค่าสภาพแวดล้อม: development, staging, production
define('ENVIRONMENT', 'development');

// แสดง errors ในโหมด development
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

// =================================================================
// การตั้งค่าไซต์หลัก (Site Configuration)
// =================================================================

// ข้อมูลพื้นฐานของเว็บไซต์
define('SITE_NAME', 'ข้าวพันธุ์พื้นเมืองเลย');
define('SITE_TAGLINE', 'อนุรักษ์และสืบสานความเป็นไทย');
define('SITE_DESCRIPTION', 'ร้านขายข้าวพันธุ์พื้นเมืองและผลิตภัณฑ์จากจังหวัดเลย');
define('SITE_KEYWORDS', 'ข้าวพันธุ์พื้นเมือง, ข้าวเหนียวแดง, ข้าวซิวเกลี้ยง, เลย, ข้าวอินทรีย์');
define('SITE_AUTHOR', 'กลุ่มวิสาหกิจชุมชนข้าวพันธุ์พื้นเมืองเลย');

// URL และ Path
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_path = '/loei-rice-ecommerce/';

define('SITE_URL', $protocol . $host . $base_path);
define('ADMIN_URL', SITE_URL . 'admin/');
define('ASSETS_URL', SITE_URL . 'assets/');
define('UPLOADS_URL', SITE_URL . 'uploads/');

// Paths
define('ROOT_PATH', dirname(__DIR__) . '/');
define('CONFIG_PATH', ROOT_PATH . 'config/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('PAGES_PATH', ROOT_PATH . 'pages/');
define('ADMIN_PATH', ROOT_PATH . 'admin/');
define('ASSETS_PATH', ROOT_PATH . 'assets/');
define('UPLOADS_PATH', ROOT_PATH . 'uploads/');

// =================================================================
// การตั้งค่าฐานข้อมูล (Database Configuration)
// =================================================================

// ข้อมูลการเชื่อมต่อฐานข้อมูล (จะถูก override ใน database.php)
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'loei_rice_ecommerce');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// การตั้งค่าตาราง
define('DB_PREFIX', ''); // prefix สำหรับตาราง (ถ้ามี)

// =================================================================
// การตั้งค่าไฟล์ (File Configuration)
// =================================================================

// การอัปโหลดไฟล์
define('UPLOAD_PATH', ROOT_PATH . 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('MAX_IMAGE_WIDTH', 2000);
define('MAX_IMAGE_HEIGHT', 2000);

// ประเภทไฟล์ที่อนุญาต
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx', 'txt']);

// การตั้งค่ารูปภาพ
define('DEFAULT_PRODUCT_IMAGE', 'default-product.jpg');
define('DEFAULT_CATEGORY_IMAGE', 'default-category.jpg');
define('DEFAULT_USER_AVATAR', 'default-avatar.jpg');

// =================================================================
// การตั้งค่าอีเมล (Email Configuration)
// =================================================================

// SMTP Settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', ''); // จะโหลดจาก database
define('SMTP_PASSWORD', ''); // จะโหลดจาก database
define('SMTP_SECURE', 'tls'); // tls หรือ ssl

// Email addresses
define('EMAIL_FROM_NAME', SITE_NAME);
define('EMAIL_FROM_ADDRESS', 'noreply@loeirice.com');
define('EMAIL_ADMIN', 'admin@loeirice.com');
define('EMAIL_SUPPORT', 'support@loeirice.com');

// =================================================================
// การตั้งค่าการชำระเงิน (Payment Configuration)
// =================================================================

// สกุลเงิน
define('CURRENCY_CODE', 'THB');
define('CURRENCY_SYMBOL', '฿');
define('CURRENCY_POSITION', 'before'); // before หรือ after

// การตั้งค่าราคา
define('PRICE_DECIMAL_PLACES', 2);
define('TAX_RATE', 0.00); // 0% (จะโหลดจาก database)

// วิธีการชำระเงิน
define('PAYMENT_METHODS', [
    'bank_transfer' => 'โอนผ่านธนาคาร',
    'promptpay' => 'พร้อมเพย์'
]);

// =================================================================
// การตั้งค่าการจัดส่ง (Shipping Configuration)
// =================================================================

// ค่าจัดส่งฟรี
define('FREE_SHIPPING_MIN_AMOUNT', 1000.00); // จะโหลดจาก database

// น้ำหนักเริ่มต้น
define('DEFAULT_PRODUCT_WEIGHT', 0.5); // กิโลกรัม

// =================================================================
// การตั้งค่า Session และ Security
// =================================================================

// Session
define('SESSION_TIMEOUT', 1800); // 30 นาที
define('SESSION_NAME', 'loei_rice_session');

// Security
define('PASSWORD_MIN_LENGTH', 6);
define('PASSWORD_REQUIRE_SPECIAL_CHARS', false);
define('PASSWORD_REQUIRE_NUMBERS', false);
define('PASSWORD_REQUIRE_UPPERCASE', false);

// CSRF Protection
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_EXPIRE', 3600); // 1 ชั่วโมง

// Login attempts
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 1800); // 30 นาที

// =================================================================
// การตั้งค่าการแสดงผล (Display Configuration)
// =================================================================

// การแบ่งหน้า (Pagination)
define('PRODUCTS_PER_PAGE', 12);
define('ORDERS_PER_PAGE', 10);
define('USERS_PER_PAGE', 20);
define('REVIEWS_PER_PAGE', 10);

// สินค้าในหน้าแรก
define('FEATURED_PRODUCTS_COUNT', 6);
define('NEW_PRODUCTS_COUNT', 8);
define('RELATED_PRODUCTS_COUNT', 4);

// รีวิว
define('REVIEWS_REQUIRE_APPROVAL', true);
define('REVIEWS_ALLOW_ANONYMOUS', false);

// =================================================================
// การตั้งค่าเวลา (Time Configuration)
// =================================================================

// Timezone
date_default_timezone_set('Asia/Bangkok');
define('SITE_TIMEZONE', 'Asia/Bangkok');

// รูปแบบวันที่และเวลา
define('DATE_FORMAT', 'd/m/Y');
define('TIME_FORMAT', 'H:i');
define('DATETIME_FORMAT', 'd/m/Y H:i');

// =================================================================
// การตั้งค่า SEO และ Social Media
// =================================================================

// Meta tags
define('DEFAULT_META_TITLE', SITE_NAME . ' - ' . SITE_TAGLINE);
define('DEFAULT_META_DESCRIPTION', SITE_DESCRIPTION);
define('DEFAULT_META_KEYWORDS', SITE_KEYWORDS);

// Open Graph
define('OG_IMAGE', ASSETS_URL . 'images/og-image.jpg');
define('OG_TYPE', 'website');

// Social Media
define('FACEBOOK_PAGE', 'https://facebook.com/loeirice');
define('LINE_ID', '@loeirice');
define('YOUTUBE_CHANNEL', '');
define('INSTAGRAM_PROFILE', '');

// =================================================================
// การตั้งค่า API และ Third-party Services
// =================================================================

// Google Services
define('GOOGLE_ANALYTICS_ID', ''); // จะโหลดจาก database
define('GOOGLE_TAG_MANAGER_ID', '');
define('GOOGLE_RECAPTCHA_SITE_KEY', '');
define('GOOGLE_RECAPTCHA_SECRET_KEY', '');

// Facebook Pixel
define('FACEBOOK_PIXEL_ID', ''); // จะโหลดจาก database

// Line Notify
define('LINE_NOTIFY_TOKEN', '');

// =================================================================
// การตั้งค่าการพัฒนา (Development Configuration)
// =================================================================

// Debug mode
define('DEBUG_MODE', ENVIRONMENT === 'development');
define('SQL_DEBUG', ENVIRONMENT === 'development');

// Error logging
define('ERROR_LOG_FILE', ROOT_PATH . 'logs/error.log');
define('ACCESS_LOG_FILE', ROOT_PATH . 'logs/access.log');

// Cache
define('ENABLE_CACHE', ENVIRONMENT !== 'development');
define('CACHE_DURATION', 3600); // 1 ชั่วโมง

// =================================================================
// การตั้งค่าระบบอื่น ๆ (Miscellaneous)
// =================================================================

// เวอร์ชันระบบ
define('SYSTEM_VERSION', '1.0.0');
define('SYSTEM_BUILD', '20241207');

// การบำรุงรักษา
define('MAINTENANCE_MODE', false); // จะโหลดจาก database
define('MAINTENANCE_MESSAGE', 'ระบบอยู่ระหว่างการปรับปรุง กรุณาลองใหม่อีกครั้งในภายหลัง');

// Rate limiting
define('API_RATE_LIMIT', 100); // requests per hour
define('LOGIN_RATE_LIMIT', 10); // attempts per hour

// =================================================================
// ฟังก์ชัน Helper Functions
// =================================================================

/**
 * ดึงค่าการตั้งค่าจากฐานข้อมูล
 * 
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function getSetting($key, $default = null)
{
    static $settings = null;

    // โหลด settings ครั้งแรก
    if ($settings === null) {
        $settings = [];
        try {
            require_once 'database.php';
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
            while ($row = $stmt->fetch()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            // ถ้าไม่สามารถโหลดจาก database ได้
            error_log("Cannot load settings from database: " . $e->getMessage());
        }
    }

    return $settings[$key] ?? $default;
}

/**
 * สร้าง URL แบบ absolute
 * 
 * @param string $path
 * @return string
 */
function siteUrl($path = '')
{
    return SITE_URL . ltrim($path, '/');
}

/**
 * สร้าง URL สำหรับ assets
 * 
 * @param string $path
 * @return string
 */
function assetUrl($path = '')
{
    return ASSETS_URL . ltrim($path, '/');
}

/**
 * สร้าง URL สำหรับ uploads
 * 
 * @param string $path
 * @return string
 */
function uploadUrl($path = '')
{
    return UPLOADS_URL . ltrim($path, '/');
}

/**
 * สร้าง URL สำหรับหน้า admin
 * 
 * @param string $path
 * @return string
 */
function adminUrl($path = '')
{
    return ADMIN_URL . ltrim($path, '/');
}

/**
 * แปลงราคาให้อยู่ในรูปแบบที่ถูกต้อง
 * 
 * @param float $price
 * @return string
 */
function formatPrice($price)
{
    $formatted = number_format($price, PRICE_DECIMAL_PLACES);

    if (CURRENCY_POSITION === 'before') {
        return CURRENCY_SYMBOL . $formatted;
    } else {
        return $formatted . CURRENCY_SYMBOL;
    }
}

/**
 * แปลงวันที่ให้อยู่ในรูปแบบที่กำหนด
 * 
 * @param string|int $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = DATE_FORMAT)
{
    if (is_numeric($date)) {
        return date($format, $date);
    }
    return date($format, strtotime($date));
}

/**
 * ตรวจสอบว่าเป็น HTTPS หรือไม่
 * 
 * @return boolean
 */
function isHttps()
{
    return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
}

/**
 * ดึง IP address ของผู้ใช้
 * 
 * @return string
 */
function getUserIpAddress()
{
    $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];

    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }

    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * สร้าง CSRF Token
 * 
 * @return string
 */
function generateCSRFToken()
{
    if (
        !isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) ||
        (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRE
    ) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * ตรวจสอบ CSRF Token
 * 
 * @param string $token
 * @return boolean
 */
function verifyCSRFToken($token)
{
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }

    if ((time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRE) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * ทำความสะอาด input
 * 
 * @param string $input
 * @return string
 */
function cleanInput($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * ตรวจสอบว่าเป็น AJAX request หรือไม่
 * 
 * @return boolean
 */
function isAjaxRequest()
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * ส่ง JSON response
 * 
 * @param array $data
 * @param int $status_code
 */
function sendJsonResponse($data, $status_code = 200)
{
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * บันทึก activity log
 * 
 * @param string $user_type
 * @param int $user_id
 * @param string $action
 * @param string $description
 */
function logActivity($user_type, $user_id, $action, $description = '')
{
    try {
        require_once 'database.php';
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_type, user_id, action, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $user_type,
            $user_id,
            $action,
            $description,
            getUserIpAddress(),
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * ตรวจสอบสิทธิ์ admin
 * 
 * @param string $required_role
 * @return boolean
 */
function checkAdminPermission($required_role = 'admin')
{
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_role'])) {
        return false;
    }

    $role_hierarchy = [
        'editor' => 1,
        'admin' => 2,
        'super_admin' => 3
    ];

    $user_level = $role_hierarchy[$_SESSION['admin_role']] ?? 0;
    $required_level = $role_hierarchy[$required_role] ?? 0;

    return $user_level >= $required_level;
}

/**
 * Redirect ไปหน้าที่กำหนด
 * 
 * @param string $url
 * @param int $status_code
 */
function redirect($url, $status_code = 302)
{
    // ถ้าเป็น relative URL ให้เพิ่ม base URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $url = SITE_URL . ltrim($url, '/');
    }

    http_response_code($status_code);
    header("Location: $url");
    exit;
}

/**
 * สร้าง slug จากข้อความ
 * 
 * @param string $text
 * @return string
 */
function createSlug($text)
{
    // แปลงเป็นตัวพิมพ์เล็ก
    $text = strtolower($text);

    // แทนที่ช่องว่างด้วย -
    $text = preg_replace('/\s+/', '-', $text);

    // ลบอักขระพิเศษ
    $text = preg_replace('/[^a-z0-9\-]/', '', $text);

    // ลบ - ซ้ำ
    $text = preg_replace('/-+/', '-', $text);

    // ลบ - ที่ต้นและท้าย
    $text = trim($text, '-');

    return $text;
}

/**
 * สร้างรหัสสั่งซื้อ
 * 
 * @return string
 */
function generateOrderNumber()
{
    $prefix = getSetting('order_number_prefix', 'LR');
    $date = date('Ymd');
    $random = sprintf('%04d', mt_rand(1, 9999));

    return $prefix . $date . $random;
}

/**
 * ตรวจสอบรูปแบบอีเมล
 * 
 * @param string $email
 * @return boolean
 */
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * ตรวจสอบรูปแบบเบอร์โทร
 * 
 * @param string $phone
 * @return boolean
 */
function isValidPhone($phone)
{
    // รูปแบบเบอร์โทรไทย
    $pattern = '/^(\+66|66|0)[0-9]{8,9}$/';
    return preg_match($pattern, $phone);
}

/**
 * สร้างรหัสผ่านแบบสุ่ม
 * 
 * @param int $length
 * @return string
 */
function generateRandomPassword($length = 8)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($chars), 0, $length);
}

/**
 * คำนวณระยะเวลาที่ผ่านมา
 * 
 * @param string|int $datetime
 * @return string
 */
function timeAgo($datetime)
{
    $time = is_numeric($datetime) ? $datetime : strtotime($datetime);
    $time_difference = time() - $time;

    if ($time_difference < 1) return 'เมื่อสักครู่';

    $condition = [
        12 * 30 * 24 * 60 * 60 => 'ปี',
        30 * 24 * 60 * 60      => 'เดือน',
        24 * 60 * 60           => 'วัน',
        60 * 60                => 'ชั่วโมง',
        60                     => 'นาที'
    ];

    foreach ($condition as $secs => $str) {
        $d = $time_difference / $secs;
        if ($d >= 1) {
            $t = round($d);
            return $t . ' ' . $str . 'ที่แล้ว';
        }
    }

    return 'เมื่อสักครู่';
}

/**
 * แปลงขนาดไฟล์เป็นรูปแบบที่อ่านง่าย
 * 
 * @param int $bytes
 * @param int $precision
 * @return string
 */
function formatFileSize($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }

    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * ตรวจสอบโหมดการบำรุงรักษา
 * 
 * @return boolean
 */
function isMaintenanceMode()
{
    return getSetting('maintenance_mode', false) === 'true';
}

/**
 * ป้องกันการโจมตี SQL Injection เพิ่มเติม
 * 
 * @param string $string
 * @return string
 */
function sanitizeSQL($string)
{
    $string = trim($string);
    $string = addslashes($string);
    return $string;
}

/**
 * ตรวจสอบความแข็งแรงของรหัสผ่าน
 * 
 * @param string $password
 * @return array
 */
function checkPasswordStrength($password)
{
    $result = [
        'score' => 0,
        'strength' => 'weak',
        'feedback' => []
    ];

    // ความยาว
    if (strlen($password) >= 8) $result['score'] += 25;
    else $result['feedback'][] = 'ควรมีความยาวอย่างน้อย 8 ตัวอักษร';

    // ตัวพิมพ์เล็ก
    if (preg_match('/[a-z]/', $password)) $result['score'] += 20;
    else $result['feedback'][] = 'ควรมีตัวพิมพ์เล็ก';

    // ตัวพิมพ์ใหญ่
    if (preg_match('/[A-Z]/', $password)) $result['score'] += 20;
    else $result['feedback'][] = 'ควรมีตัวพิมพ์ใหญ่';

    // ตัวเลข
    if (preg_match('/[0-9]/', $password)) $result['score'] += 15;
    else $result['feedback'][] = 'ควรมีตัวเลข';

    // อักขระพิเศษ
    if (preg_match('/[^a-zA-Z0-9]/', $password)) $result['score'] += 20;
    else $result['feedback'][] = 'ควรมีอักขระพิเศษ';

    // กำหนดระดับความแข็งแรง
    if ($result['score'] >= 80) $result['strength'] = 'very_strong';
    elseif ($result['score'] >= 60) $result['strength'] = 'strong';
    elseif ($result['score'] >= 40) $result['strength'] = 'medium';
    elseif ($result['score'] >= 20) $result['strength'] = 'weak';
    else $result['strength'] = 'very_weak';

    return $result;
}

// =================================================================
// Auto-load settings จากฐานข้อมูล
// =================================================================

// โหลดการตั้งค่าจากฐานข้อมูลเมื่อมีการเรียกใช้ครั้งแรก
if (!defined('SETTINGS_LOADED')) {
    // ตรวจสอบว่ามีไฟล์ database.php หรือไม่
    if (file_exists(__DIR__ . '/database.php')) {
        try {
            // โหลดการตั้งค่าที่สำคัญจากฐานข้อมูล
            $dynamic_settings = [
                'site_title' => SITE_NAME,
                'site_tagline' => SITE_TAGLINE,
                'free_shipping_amount' => FREE_SHIPPING_MIN_AMOUNT,
                'tax_rate' => TAX_RATE,
                'maintenance_mode' => MAINTENANCE_MODE,
                'products_per_page' => PRODUCTS_PER_PAGE,
                'reviews_require_approval' => REVIEWS_REQUIRE_APPROVAL
            ];

            // Override ค่าคงที่ด้วยค่าจากฐานข้อมูล (ถ้ามี)
            foreach ($dynamic_settings as $key => $default) {
                $value = getSetting($key, $default);

                // สร้างค่าคงที่ใหม่สำหรับการตั้งค่าที่โหลดจาก database
                $constant_name = 'DB_' . strtoupper($key);
                if (!defined($constant_name)) {
                    define($constant_name, $value);
                }
            }

            define('SETTINGS_LOADED', true);
        } catch (Exception $e) {
            // ถ้าไม่สามารถโหลดการตั้งค่าจากฐานข้อมูลได้
            error_log("Cannot load dynamic settings: " . $e->getMessage());
            define('SETTINGS_LOADED', false);
        }
    } else {
        define('SETTINGS_LOADED', false);
    }
}

// =================================================================
// Session Configuration
// =================================================================

// ตั้งค่า session
if (!session_id()) {
    ini_set('session.name', SESSION_NAME);
    ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isHttps() ? 1 : 0);
    ini_set('session.use_strict_mode', 1);

    session_start();
}

// ตรวจสอบ session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();

// =================================================================
// Error Handling
// =================================================================

// ตั้งค่า error handler แบบกำหนดเอง
function customErrorHandler($errno, $errstr, $errfile, $errline)
{
    $error_message = "Error: [$errno] $errstr in $errfile on line $errline";

    // เขียน log
    error_log($error_message);

    // แสดง error ในโหมด development เท่านั้น
    if (ENVIRONMENT === 'development') {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
        echo "<strong>Error:</strong> $errstr<br>";
        echo "<strong>File:</strong> $errfile<br>";
        echo "<strong>Line:</strong> $errline";
        echo "</div>";
    }

    return true;
}

// ตั้งค่า exception handler
function customExceptionHandler($exception)
{
    $error_message = "Uncaught exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();

    error_log($error_message);

    if (ENVIRONMENT === 'development') {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
        echo "<strong>Exception:</strong> " . $exception->getMessage() . "<br>";
        echo "<strong>File:</strong> " . $exception->getFile() . "<br>";
        echo "<strong>Line:</strong> " . $exception->getLine();
        echo "</div>";
    } else {
        echo "<h1>เกิดข้อผิดพลาด</h1><p>ขออภัย เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่อีกครั้ง</p>";
    }
}

// ลงทะเบียน error handlers
set_error_handler('customErrorHandler');
set_exception_handler('customExceptionHandler');

// =================================================================
// Final Checks
// =================================================================

// ตรวจสอบโฟลเดอร์ที่จำเป็น
$required_dirs = [
    UPLOADS_PATH,
    UPLOADS_PATH . 'products/',
    UPLOADS_PATH . 'banners/',
    ROOT_PATH . 'logs/'
];

foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        // ตรวจสอบสิทธิ์ก่อนสร้างโฟลเดอร์
        $parent_dir = dirname($dir);
        if (!is_writable($parent_dir)) {
            error_log("Cannot create directory '$dir': parent directory '$parent_dir' is not writable");
            continue;
        }

        // พยายามสร้างโฟลเดอร์
        if (!@mkdir($dir, 0755, true)) {
            error_log("Cannot create required directory: $dir");

            // แสดงคำแนะนำการแก้ไขใน development mode
            if (ENVIRONMENT === 'development') {
                echo "<div style='background: #fff3cd; color: #856404; padding: 10px; margin: 10px; border: 1px solid #ffeaa7; border-radius: 4px;'>";
                echo "<strong>Warning:</strong> Cannot create directory: $dir<br>";
                echo "<strong>Solution:</strong> Please create this directory manually or fix permissions:<br>";
                echo "<code>mkdir -p " . $dir . "</code><br>";
                echo "<code>chmod 755 " . $dir . "</code>";
                echo "</div>";
            }
        }
    }
}

// ตรวจสอบไฟล์ .htaccess สำหรับการป้องกัน
$htaccess_content = "# Protect configuration files\n<Files ~ \"\.(php|json|log)$\">\nOrder allow,deny\nDeny from all\n</Files>\n";
$htaccess_file = UPLOADS_PATH . '.htaccess';

// ตรวจสอบว่าสามารถเขียนไฟล์ได้หรือไม่
if (is_dir(UPLOADS_PATH) && is_writable(UPLOADS_PATH)) {
    if (!file_exists($htaccess_file)) {
        if (!@file_put_contents($htaccess_file, $htaccess_content)) {
            error_log("Cannot create .htaccess file in uploads directory");
        }
    }
} else {
    if (ENVIRONMENT === 'development') {
        echo "<div style='background: #fff3cd; color: #856404; padding: 10px; margin: 10px; border: 1px solid #ffeaa7; border-radius: 4px;'>";
        echo "<strong>Warning:</strong> Uploads directory is not writable: " . UPLOADS_PATH . "<br>";
        echo "<strong>Solution:</strong> Please fix permissions:<br>";
        echo "<code>chmod 755 " . UPLOADS_PATH . "</code>";
        echo "</div>";
    }
}

// สิ้นสุดไฟล์การตั้งค่า
