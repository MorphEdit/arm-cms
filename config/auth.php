<?php
/**
 * Authentication System
 * ARM CMS - Content Management System
 * 
 * ระบบจัดการการเข้าสู่ระบบและการตรวจสอบสิทธิ์
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/database.php';

/**
 * Check if user is logged in
 * 
 * การทำงาน:
 * - ตรวจสอบว่ามี session user_id หรือไม่
 * - ตรวจสอบว่าผู้ใช้ยังคงมีสถานะ active ในฐานข้อมูล
 * - คืนค่า true หากเข้าสู่ระบบแล้ว, false หากยังไม่ได้เข้าสู่ระบบ
 * 
 * @return bool
 */
function isLoggedIn() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return false;
    }
    
    try {
        $db = getDatabase();
        $stmt = $db->prepare("SELECT status FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if user exists and is active
        return $user && $user['status'] === 'active';
        
    } catch (Exception $e) {
        error_log('Auth check error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Require user to be logged in
 * 
 * การทำงาน:
 * - เรียกใช้ isLoggedIn() เพื่อตรวจสอบสถานะการเข้าสู่ระบบ
 * - หากยังไม่ได้เข้าสู่ระบบจะ redirect ไปหน้า login
 * - เก็บ URL ปัจจุบันใน session เพื่อ redirect กลับหลัง login สำเร็จ
 * - ส่ง HTTP 401 status code แล้วหยุดการทำงาน
 */
function requireLogin() {
    if (!isLoggedIn()) {
        // Store current URL for redirect after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // Redirect to login page
        header('Location: public/login.php');
        exit();
    }
}

/**
 * Require API authentication
 * 
 * การทำงาน:
 * - ตรวจสอบการเข้าสู่ระบบสำหรับ API endpoints
 * - ส่ง JSON response พร้อม HTTP 401 หากไม่ได้รับอนุญาต
 * - ใช้สำหรับไฟล์ในโฟลเดอร์ api/
 */
function requireApiAuth() {
    if (!isLoggedIn()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'กรุณาเข้าสู่ระบบก่อนใช้งาน',
            'error_code' => 'UNAUTHORIZED'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
}

/**
 * Get current user data
 * 
 * การทำงาน:
 * - ดึงข้อมูลผู้ใช้ปัจจุบันจากฐานข้อมูล
 * - คืนค่าข้อมูลผู้ใช้เป็น array หากสำเร็จ
 * - คืนค่า null หากไม่พบข้อมูลหรือเกิดข้อผิดพลาด
 * 
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $db = getDatabase();
        $stmt = $db->prepare("
            SELECT id, username, full_name, email, role, status, last_login, created_at 
            FROM users 
            WHERE id = ? AND status = 'active' 
            LIMIT 1
        ");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log('Get current user error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Create remember me token
 * 
 * การทำงาน:
 * - สร้าง secure token สำหรับ remember me
 * - บันทึก token ลงฐานข้อมูล
 * - ตั้ง cookie ใน browser
 * 
 * @param int $userId
 * @return string|false
 */
function createRememberToken($userId) {
    try {
        $db = getDatabase();
        
        // สร้าง secure token
        $token = bin2hex(random_bytes(32));
        $hashedToken = password_hash($token, PASSWORD_DEFAULT);
        
        // กำหนดวันหมดอายุ (30 วัน)
        $expires = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60));
        
        // บันทึกลงฐานข้อมูล
        $stmt = $db->prepare("
            UPDATE users 
            SET remember_token = ?, remember_expires = ? 
            WHERE id = ?
        ");
        
        $result = $stmt->execute([$hashedToken, $expires, $userId]);
        
        if ($result) {
            // ตั้ง cookie
            setcookie(
                'remember_token', 
                $userId . ':' . $token, 
                time() + (30 * 24 * 60 * 60), 
                '/', 
                '', 
                isset($_SERVER['HTTPS']), 
                true
            );
            
            return $token;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log('Create remember token error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Check remember me token
 * 
 * การทำงาน:
 * - ตรวจสอบ remember me cookie
 * - verify token กับฐานข้อมูล
 * - auto login หาก token ถูกต้อง
 * 
 * @return bool
 */
function checkRememberToken() {
    if (!isset($_COOKIE['remember_token'])) {
        return false;
    }
    
    try {
        $cookie = $_COOKIE['remember_token'];
        $parts = explode(':', $cookie, 2);
        
        if (count($parts) !== 2) {
            // Invalid cookie format
            clearRememberToken();
            return false;
        }
        
        list($userId, $token) = $parts;
        $userId = (int)$userId;
        
        if ($userId <= 0) {
            clearRememberToken();
            return false;
        }
        
        $db = getDatabase();
        
        // ดึงข้อมูล user และ token
        $stmt = $db->prepare("
            SELECT id, username, full_name, role, status, remember_token, remember_expires
            FROM users 
            WHERE id = ? AND status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            clearRememberToken();
            return false;
        }
        
        // ตรวจสอบ token หมดอายุ
        if ($user['remember_expires'] && strtotime($user['remember_expires']) < time()) {
            clearRememberToken();
            return false;
        }
        
        // ตรวจสอบ token
        if (!$user['remember_token'] || !password_verify($token, $user['remember_token'])) {
            clearRememberToken();
            return false;
        }
        
        // Auto login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['auto_login'] = true; // Mark as auto login
        
        // อัปเดต last_login
        $updateStmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $updateStmt->execute([$user['id']]);
        
        // สร้าง token ใหม่เพื่อความปลอดภัย
        createRememberToken($user['id']);
        
        return true;
        
    } catch (Exception $e) {
        error_log('Check remember token error: ' . $e->getMessage());
        clearRememberToken();
        return false;
    }
}

/**
 * Clear remember me token
 * 
 * การทำงาน:
 * - ลบ token จากฐานข้อมูล
 * - ลบ cookie จาก browser
 */
function clearRememberToken() {
    // ลบ cookie
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        
        // ลบจากฐานข้อมูลถ้าเป็นไปได้
        try {
            $cookie = $_COOKIE['remember_token'];
            $parts = explode(':', $cookie, 2);
            
            if (count($parts) === 2) {
                $userId = (int)$parts[0];
                if ($userId > 0) {
                    $db = getDatabase();
                    $stmt = $db->prepare("
                        UPDATE users 
                        SET remember_token = NULL, remember_expires = NULL 
                        WHERE id = ?
                    ");
                    $stmt->execute([$userId]);
                }
            }
        } catch (Exception $e) {
            error_log('Clear remember token error: ' . $e->getMessage());
        }
    }
}

/**
 * Login user
 * 
 * การทำงาน:
 * - ตรวจสอบ username และ password
 * - ใช้ password_verify() เพื่อตรวจสอบรหัสผ่าน
 * - สร้าง session หากข้อมูลถูกต้อง
 * - อัปเดต last_login ในฐานข้อมูล
 * - คืนค่า array ผลลัพธ์
 * 
 * @param string $username
 * @param string $password
 * @param bool $remember
 * @return array
 */
function login($username, $password, $remember = false) {
    try {
        $db = getDatabase();
        
        // Get user by username
        $stmt = $db->prepare("
            SELECT id, username, password, full_name, email, role, status 
            FROM users 
            WHERE username = ? 
            LIMIT 1
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if user exists
        if (!$user) {
            return [
                'success' => false,
                'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'
            ];
        }
        
        // Check if user is active
        if ($user['status'] !== 'active') {
            return [
                'success' => false,
                'message' => 'บัญชีของคุณถูกปิดการใช้งาน'
            ];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'
            ];
        }
        
        // Create session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();
        
        // Update last login
        $updateStmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $updateStmt->execute([$user['id']]);
        
        // สร้าง remember token ถ้าขอ
        if ($remember) {
            createRememberToken($user['id']);
        }
        
        return [
            'success' => true,
            'message' => 'เข้าสู่ระบบสำเร็จ',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'role' => $user['role']
            ]
        ];
        
    } catch (Exception $e) {
        error_log('Login error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ'
        ];
    }
}

/**
 * Logout user
 * 
 * การทำงาน:
 * - ลบข้อมูลทั้งหมดใน session
 * - ทำลาย session
 * - ล้าง session cookie หากมี
 * - ลบ remember token
 */
function logout() {
    // ลบ remember token
    clearRememberToken();
    
    // Clear all session data
    $_SESSION = [];
    
    // Destroy session cookie if it exists
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy session
    session_destroy();
}

/**
 * Check if user has specific role
 * 
 * การทำงาน:
 * - ตรวจสอบว่าผู้ใช้ปัจจุบันมี role ที่กำหนดหรือไม่
 * - ใช้สำหรับการจัดการสิทธิ์แบบละเอียด
 * 
 * @param string $role
 * @return bool
 */
function hasRole($role) {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Check if user is admin
 * 
 * การทำงาน: ตรวจสอบว่าผู้ใช้เป็น admin หรือไม่
 * 
 * @return bool
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Require admin role
 * 
 * การทำงาน:
 * - ตรวจสอบว่าผู้ใช้เป็น admin หรือไม่
 * - redirect ไปหน้า access denied หากไม่ใช่ admin
 */
function requireAdmin() {
    requireLogin();
    
    if (!isAdmin()) {
        http_response_code(403);
        die('Access Denied: Admin role required');
    }
}

/**
 * Generate CSRF token
 * 
 * การทำงาน:
 * - สร้าง CSRF token สำหรับป้องกัน CSRF attacks
 * - เก็บ token ใน session
 * 
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * การทำงาน:
 * - ตรวจสอบ CSRF token ที่ส่งมากับ token ใน session
 * - ป้องกัน CSRF attacks
 * 
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get user display name
 * 
 * การทำงาน:
 * - คืนค่าชื่อที่ใช้แสดงของผู้ใช้ปัจจุบัน
 * - ลำดับความสำคัญ: full_name > username > 'ผู้ใช้'
 * 
 * @return string
 */
function getUserDisplayName() {
    if (!isLoggedIn()) {
        return 'ผู้ใช้';
    }
    
    if (!empty($_SESSION['full_name'])) {
        return $_SESSION['full_name'];
    }
    
    if (!empty($_SESSION['username'])) {
        return $_SESSION['username'];
    }
    
    return 'ผู้ใช้';
}

/**
 * Check session timeout
 * 
 * การทำงาน:
 * - ตรวจสอบว่า session หมดอายุหรือไม่ (default 2 hours)
 * - logout อัตโนมัติหาก session หมดอายุ
 * 
 * @param int $timeout_seconds
 * @return bool true if session is valid, false if timed out
 */
function checkSessionTimeout($timeout_seconds = 7200) { // 2 hours default
    if (!isLoggedIn()) {
        return false;
    }
    
    if (isset($_SESSION['login_time'])) {
        if (time() - $_SESSION['login_time'] > $timeout_seconds) {
            logout();
            return false;
        }
    }
    
    return true;
}

// Auto-check session timeout on every request
checkSessionTimeout();

// Check remember me if not logged in
if (!isLoggedIn()) {
    checkRememberToken();
}
?>