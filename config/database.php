<?php
/**
 * Database Configuration
 * ARM CMS - Content Management System
 */

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'arm_cms');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_CHARSET', 'utf8mb4');

// Global PDO instance
$pdo = null;

/**
 * Get database connection
 * 
 * การทำงาน:
 * - ตรวจสอบว่ามีการเชื่อมต่อฐานข้อมูลอยู่แล้วหรือไม่ (singleton pattern)
 * - หากยังไม่มี จะสร้างการเชื่อมต่อใหม่ด้วย PDO
 * - กำหนดค่า PDO options เพื่อความปลอดภัยและประสิทธิภาพ
 *   - ERRMODE_EXCEPTION: แสดง error แบบ exception
 *   - FETCH_ASSOC: ดึงข้อมูลแบบ associative array
 *   - EMULATE_PREPARES = false: ใช้ prepared statements จริง
 *   - INIT_COMMAND: กำหนด charset เป็น utf8mb4
 * - คืนค่า PDO instance สำหรับใช้งาน
 * - หากเกิดข้อผิดพลาด จะบันทึก error log และแสดงข้อความแจ้งเตือน
 * 
 * @return PDO
 */
function getDatabase() {
    global $pdo;
    
    // Return existing connection if already created
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        return $pdo;
        
    } catch (PDOException $e) {
        // Log error (in production, use proper logging)
        error_log("Database connection failed: " . $e->getMessage());
        
        // Return user-friendly error
        die(json_encode([
            'success' => false,
            'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้'
        ]));
    }
}

/**
 * Test database connection
 * 
 * การทำงาน:
 * - ทดสอบการเชื่อมต่อฐานข้อมูลด้วยการ query SELECT 1
 * - ใช้สำหรับตรวจสอบว่าฐานข้อมูลพร้อมใช้งานหรือไม่
 * - คืนค่า true หากเชื่อมต่อสำเร็จ
 * - คืนค่า false หากเชื่อมต่อไม่สำเร็จ
 * - มักใช้ก่อนแสดงหน้าเว็บเพื่อตรวจสอบสถานะฐานข้อมูล
 * 
 * @return bool
 */
function testConnection() {
    try {
        $db = getDatabase();
        $stmt = $db->query("SELECT 1");
        return $stmt !== false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Close database connection
 * 
 * การทำงาน:
 * - ปิดการเชื่อมต่อฐานข้อมูลโดยกำหนดค่า global $pdo = null
 * - PDO จะทำการปิดการเชื่อมต่ออัตโนมัติเมื่อ object ถูกทำลาย
 * - ใช้เมื่อต้องการปิดการเชื่อมต่อเพื่อประหยัดทรัพยากรเซิร์ฟเวอร์
 * - ในการใช้งานปกติ PHP จะปิดการเชื่อมต่ออัตโนมัติเมื่อสคริปต์จบ
 */
function closeDatabase() {
    global $pdo;
    $pdo = null;
}

// Auto-connect when file is included
// การทำงาน: เชื่อมต่อฐานข้อมูลอัตโนมัติเมื่อไฟล์นี้ถูก include
// หากเกิดข้อผิดพลาด จะจัดการอย่างเงียบๆ เพื่อไม่ให้กระทบต่อการโหลดไฟล์อื่น
try {
    getDatabase();
} catch (Exception $e) {
    // Handle connection error silently for includes
}
?>