<?php
/**
 * Delete News API
 * ARM CMS - Content Management System
 * 
 * API สำหรับลบข่าวจากฐานข้อมูล พร้อมลบไฟล์รูปภาพที่เกี่ยวข้อง
 * ใช้ transaction เพื่อความปลอดภัยของข้อมูล
 */

// Set content type to JSON
header('Content-Type: application/json; charset=utf-8');

// Allow CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Start output buffering to prevent any unwanted output
ob_start();

try {
    // Include authentication
    require_once '../config/auth.php';
    
    // Require API authentication
    requireApiAuth();

    // Include database connection
    require_once '../config/database.php';
    
    // Get database connection
    $db = getDatabase();
    
    // Only allow POST requests
    // การทำงาน: ตรวจสอบว่าเป็น POST request เท่านั้น เพื่อความปลอดภัย
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    // Get JSON input
    // การทำงาน: อ่านข้อมูล JSON ที่ส่งมาจาก client
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Validate JSON input
    // การทำงาน: ตรวจสอบว่า JSON ที่ได้รับสามารถ decode ได้หรือไม่
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    // การทำงาน: ตรวจสอบว่ามี ID ของข่าวที่ต้องการลบหรือไม่
    if (!isset($data['id']) || empty($data['id'])) {
        throw new Exception('ID ข่าวไม่ถูกต้อง');
    }
    
    $newsId = (int)$data['id'];
    
    // Validate ID is positive integer
    // การทำงาน: ตรวจสอบว่า ID เป็นจำนวนเต็มบวกหรือไม่
    if ($newsId <= 0) {
        throw new Exception('ID ข่าวไม่ถูกต้อง');
    }
    
    // Start transaction
    // การทำงาน: เริ่ม database transaction เพื่อให้การลบข้อมูลและไฟล์เป็น atomic operation
    $db->beginTransaction();
    
    try {
        // Get news details before deletion (for file cleanup)
        // การทำงาน: ดึงข้อมูลข่าวก่อนลบเพื่อเก็บชื่อไฟล์รูปภาพสำหรับการลบ
        $stmt = $db->prepare("SELECT id, title, image FROM cms WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $newsId]);
        $newsItem = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if news exists
        // การทำงาน: ตรวจสอบว่าข่าวที่ต้องการลบมีอยู่จริงหรือไม่
        if (!$newsItem) {
            throw new Exception('ไม่พบข่าวที่ต้องการลบ');
        }
        
        // Delete from database
        // การทำงาน: ลบข้อมูลข่าวจากฐานข้อมูล
        $deleteStmt = $db->prepare("DELETE FROM cms WHERE id = :id LIMIT 1");
        $deleteResult = $deleteStmt->execute([':id' => $newsId]);
        
        if (!$deleteResult) {
            throw new Exception('ไม่สามารถลบข่าวจากฐานข้อมูลได้');
        }
        
        // Check if any row was actually deleted
        // การทำงาน: ตรวจสอบว่ามีการลบข้อมูลจริงๆ หรือไม่
        if ($deleteStmt->rowCount() === 0) {
            throw new Exception('ไม่พบข่าวที่ต้องการลบ');
        }
        
        // Delete associated image file if exists
        // การทำงาน: ลบไฟล์รูปภาพที่เกี่ยวข้องหากมี
        $imageDeleted = true;
        if (!empty($newsItem['image'])) {
            $imagePath = '../uploads/' . $newsItem['image'];
            if (file_exists($imagePath)) {
                $imageDeleted = unlink($imagePath);
                if (!$imageDeleted) {
                    // Log warning but don't fail the transaction
                    error_log("Warning: Could not delete image file: " . $imagePath);
                }
            }
        }
        
        // Commit transaction
        // การทำงาน: commit transaction หากทุกอย่างสำเร็จ
        $db->commit();
        
        // Clean output buffer
        ob_clean();
        
        // Return success response
        // การทำงาน: ส่ง response กลับแจ้งผลสำเร็จพร้อมข้อมูลที่ถูกลบ
        echo json_encode([
            'success' => true,
            'message' => 'ลบข่าวสำเร็จ',
            'deleted_id' => $newsId,
            'deleted_title' => $newsItem['title'],
            'image_deleted' => $imageDeleted
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        // การทำงาน: rollback transaction หากเกิดข้อผิดพลาด
        $db->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    // Database error
    // การทำงาน: จัดการข้อผิดพลาดจากฐานข้อมูล
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล',
        'error_code' => 'DB_ERROR'
    ], JSON_UNESCAPED_UNICODE);
    
    // Log error for debugging
    error_log('Database error in delete_news.php: ' . $e->getMessage());
    
} catch (Exception $e) {
    // General error
    // การทำงาน: จัดการข้อผิดพลาดทั่วไป
    ob_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'DELETE_ERROR'
    ], JSON_UNESCAPED_UNICODE);
    
    // Log error for debugging
    error_log('Delete error in delete_news.php: ' . $e->getMessage());
    
} finally {
    // End output buffering
    ob_end_flush();
}

/**
 * Helper function to safely delete file
 * 
 * การทำงาน:
 * - ตรวจสอบว่าไฟล์มีอยู่จริงหรือไม่
 * - ตรวจสอบ security โดยให้แน่ใจว่าไฟล์อยู่ใน uploads directory
 * - ลบไฟล์อย่างปลอดภัย
 * - คืนค่า true หากลบสำเร็จ, false หากไม่สำเร็จ
 * 
 * @param string $filePath - path ของไฟล์ที่ต้องการลบ
 * @return bool - ผลลัพธ์การลบไฟล์
 */
function safeDeleteFile($filePath) {
    if (empty($filePath) || !file_exists($filePath)) {
        return true;
    }
    
    // Check if file is within uploads directory (security check)
    $realPath = realpath($filePath);
    $uploadsPath = realpath('../uploads/');
    
    if ($realPath === false || $uploadsPath === false) {
        return false;
    }
    
    // Ensure file is within uploads directory
    if (strpos($realPath, $uploadsPath) !== 0) {
        return false;
    }
    
    return unlink($realPath);
}
?>