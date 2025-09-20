<?php
// error_handler.php - Centralized Error Handling Utilities

require_once 'config/config.php';

/**
 * Enhanced Error Handler Class
 */
class ErrorHandler {
    
    /**
     * แปลง technical error messages เป็น user-friendly messages
     */
    public static function getUserFriendlyMessage($error_message, $context = '') {
        $friendly_messages = [
            // Database errors
            'Connection refused' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาลองใหม่อีกครั้ง',
            'Access denied' => 'เกิดข้อผิดพลาดในการเข้าถึงข้อมูล',
            'Table doesn\'t exist' => 'เกิดข้อผิดพลาดในระบบ กรุณาติดต่อผู้ดูแล',
            'Duplicate entry' => 'ข้อมูลนี้มีอยู่ในระบบแล้ว',
            'Data too long' => 'ข้อมูลที่กรอกยาวเกินไป',
            
            // Validation errors
            'SQLSTATE[23000]' => 'ข้อมูลไม่ถูกต้องหรือขัดแย้งกับข้อมูลที่มีอยู่',
            'Invalid JSON' => 'ข้อมูลที่ส่งมาไม่ถูกต้อง',
            'Required field' => 'กรุณากรอกข้อมูลให้ครบถ้วน',
            
            // File upload errors
            'File too large' => 'ไฟล์ที่อัปโหลดมีขนาดใหญ่เกินไป',
            'Invalid file type' => 'ประเภทไฟล์ไม่ถูกต้อง',
            'Upload failed' => 'การอัปโหลดไฟล์ล้มเหลว',
            
            // Payment errors
            'Payment failed' => 'การชำระเงินล้มเหลว กรุณาตรวจสอบข้อมูลและลองใหม่',
            'Insufficient funds' => 'ยอดเงินไม่เพียงพอ',
            'Card declined' => 'การชำระเงินถูกปฏิเสธ',
            
            // Stock errors
            'Out of stock' => 'สินค้าหมด ไม่สามารถดำเนินการได้',
            'Insufficient stock' => 'สินค้ามีจำนวนไม่เพียงพอ',
            
            // Security errors
            'CSRF token' => 'เกิดข้อผิดพลาดด้านความปลอดภัย กรุณาลองใหม่',
            'Rate limit' => 'คุณใช้งานบ่อยเกินไป กรุณารอสักครู่',
            'Access forbidden' => 'คุณไม่มีสิทธิ์เข้าถึงข้อมูลนี้',
        ];
        
        // ค้นหา error pattern ที่ตรงกัน
        foreach ($friendly_messages as $pattern => $message) {
            if (stripos($error_message, $pattern) !== false) {
                return $message;
            }
        }
        
        // Default messages ตาม context
        switch ($context) {
            case 'checkout':
                return 'เกิดข้อผิดพลาดในการสั่งซื้อ กรุณาตรวจสอบข้อมูลและลองใหม่อีกครั้ง';
            case 'login':
                return 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ กรุณาลองใหม่อีกครั้ง';
            case 'registration':
                return 'เกิดข้อผิดพลาดในการสมัครสมาชิก กรุณาตรวจสอบข้อมูลและลองใหม่';
            case 'upload':
                return 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์ กรุณาลองใหม่อีกครั้ง';
            default:
                return 'เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่อีกครั้ง หากปัญหายังคงมีอยู่ กรุณาติดต่อเรา';
        }
    }
    
    /**
     * บันทึก error log แบบครบถ้วน
     */
    public static function logError($error, $context = '', $additional_data = []) {
        $log_data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'context' => $context,
            'error_message' => $error instanceof Exception ? $error->getMessage() : $error,
            'user_ip' => getUserIpAddress(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'session_id' => session_id(),
            'user_id' => $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null,
            'additional_data' => $additional_data
        ];
        
        if ($error instanceof Exception) {
            $log_data['file'] = $error->getFile();
            $log_data['line'] = $error->getLine();
            $log_data['trace'] = $error->getTraceAsString();
        }
        
        $log_message = "ERROR: " . json_encode($log_data, JSON_UNESCAPED_UNICODE);
        error_log($log_message);
        
        // บันทึกลง activity_logs ถ้ามี
        try {
            logActivity(
                isset($_SESSION['admin_id']) ? 'admin' : 'customer',
                $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null,
                'system_error',
                'Error in ' . $context . ': ' . ($error instanceof Exception ? $error->getMessage() : $error)
            );
        } catch (Exception $e) {
            // ไม่ให้ error ในการ log กลายเป็น error ใหญ่
        }
    }
    
    /**
     * แสดง error message ที่เป็นมิตรกับผู้ใช้
     */
    public static function displayError($error, $context = '', $return_to = null) {
        $friendly_message = self::getUserFriendlyMessage(
            $error instanceof Exception ? $error->getMessage() : $error,
            $context
        );
        
        // บันทึก error
        self::logError($error, $context);
        
        // กำหนดหน้าที่จะกลับไป
        if (!$return_to) {
            switch ($context) {
                case 'checkout':
                    $return_to = 'checkout.php';
                    break;
                case 'login':
                    $return_to = 'admin/login.php';
                    break;
                case 'cart':
                    $return_to = 'cart.php';
                    break;
                default:
                    $return_to = 'index.php';
            }
        }
        
        // Redirect พร้อม error message
        header("Location: {$return_to}?error=" . urlencode($friendly_message));
        exit();
    }
    
    /**
     * แสดง success message
     */
    public static function displaySuccess($message, $return_to = 'index.php') {
        header("Location: {$return_to}?success=" . urlencode($message));
        exit();
    }
    
    /**
     * Validate และ sanitize input
     */
    public static function validateInput($data, $rules) {
        $errors = [];
        $cleaned_data = [];
        
        foreach ($rules as $field => $rule_set) {
            $value = $data[$field] ?? '';
            $cleaned_value = cleanInput($value);
            
            // Required check
            if (isset($rule_set['required']) && $rule_set['required'] && empty($cleaned_value)) {
                $errors[$field] = $rule_set['label'] . 'จำเป็นต้องกรอก';
                continue;
            }
            
            // Type validation
            if (!empty($cleaned_value) && isset($rule_set['type'])) {
                switch ($rule_set['type']) {
                    case 'email':
                        if (!isValidEmail($cleaned_value)) {
                            $errors[$field] = $rule_set['label'] . 'รูปแบบไม่ถูกต้อง';
                        }
                        break;
                    case 'phone':
                        if (!isValidPhone($cleaned_value)) {
                            $errors[$field] = $rule_set['label'] . 'รูปแบบไม่ถูกต้อง';
                        }
                        break;
                    case 'numeric':
                        if (!is_numeric($cleaned_value)) {
                            $errors[$field] = $rule_set['label'] . 'ต้องเป็นตัวเลข';
                        }
                        break;
                }
            }
            
            // Length validation
            if (!empty($cleaned_value) && isset($rule_set['max_length'])) {
                if (strlen($cleaned_value) > $rule_set['max_length']) {
                    $errors[$field] = $rule_set['label'] . 'ยาวเกินไป (ไม่เกิน ' . $rule_set['max_length'] . ' ตัวอักษร)';
                }
            }
            
            if (!empty($cleaned_value) && isset($rule_set['min_length'])) {
                if (strlen($cleaned_value) < $rule_set['min_length']) {
                    $errors[$field] = $rule_set['label'] . 'สั้นเกินไป (อย่างน้อย ' . $rule_set['min_length'] . ' ตัวอักษร)';
                }
            }
            
            $cleaned_data[$field] = $cleaned_value;
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $cleaned_data
        ];
    }
}

/**
 * Helper functions for easier usage
 */
function handleError($error, $context = '', $return_to = null) {
    ErrorHandler::displayError($error, $context, $return_to);
}

function handleSuccess($message, $return_to = 'index.php') {
    ErrorHandler::displaySuccess($message, $return_to);
}

function validateFormData($data, $rules) {
    return ErrorHandler::validateInput($data, $rules);
}

function logError($error, $context = '', $additional_data = []) {
    ErrorHandler::logError($error, $context, $additional_data);
}
?>