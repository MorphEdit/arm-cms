<?php
/**
 * Add News API
 * ARM CMS - Content Management System
 * 
 * API สำหรับเพิ่มข่าวใหม่ด้วย AJAX รองรับการอัปโหลดรูปภาพและ validation ข้อมูล
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
    // require_once '../config/auth.php';

    // Require API authentication
    // requireApiAuth();

    // Include database connection
    require_once '../config/database.php';

    // Get database connection
    $db = getDatabase();

    // Only allow POST requests
    // การทำงาน: ตรวจสอบว่าเป็น POST request เท่านั้น
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    // Get form data (FormData from AJAX)
    // การทำงาน: รับข้อมูลจาก FormData ที่ส่งมาจาก AJAX
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $member_access = isset($_POST['member_access']) ? trim($_POST['member_access']) : 'public';

    // Validate required fields
    // การทำงาน: ตรวจสอบข้อมูลที่จำเป็น
    if (empty($title)) {
        throw new Exception('กรุณากรอกหัวข้อข่าว');
    }

    if (empty($content)) {
        throw new Exception('กรุณากรอกเนื้อหาข่าว');
    }

    if (!in_array($category, ['ทั่วไป', 'ประกาศ', 'กิจกรรม'])) {
        throw new Exception('หมวดหมู่ไม่ถูกต้อง');
    }

    if (!in_array($status, ['active', 'inactive'])) {
        throw new Exception('สถานะไม่ถูกต้อง');
    }

    if (!in_array($member_access, ['public', 'member'])) {
        throw new Exception('การเข้าถึงไม่ถูกต้อง');
    }

    // Start transaction
    // การทำงาน: เริ่ม transaction เพื่อให้การเพิ่มข้อมูลเป็น atomic operation
    $db->beginTransaction();

    try {
        // Handle image upload
        // การทำงาน: จัดการการอัปโหลดรูปภาพ
        $uploadedImage = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadedImage = handleImageUpload($_FILES['image']);
        }

        // Insert news into database
        // การทำงาน: บันทึกข้อมูลข่าวลงฐานข้อมูล
        $stmt = $db->prepare("
            INSERT INTO cms (title, content, category, image, status, member_access, created_at, updated_at) 
            VALUES (:title, :content, :category, :image, :status, :member_access, NOW(), NOW())
        ");

        $result = $stmt->execute([
            ':title' => $title,
            ':content' => $content,
            ':category' => $category,
            ':image' => $uploadedImage,
            ':status' => $status,
            ':member_access' => $member_access
        ]);

        if (!$result) {
            throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
        }

        // Get the ID of the newly inserted news
        // การทำงาน: ดึง ID ของข่าวที่เพิ่งเพิ่มเข้าไป
        $newsId = $db->lastInsertId();

        // Get the complete news data for response
        // การทำงาน: ดึงข้อมูลข่าวที่เพิ่งเพิ่มเข้าไปเพื่อส่งกลับไปยัง client
        $stmt = $db->prepare("SELECT * FROM cms WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $newsId]);
        $newNewsData = $stmt->fetch(PDO::FETCH_ASSOC);

        // Commit transaction
        // การทำงาน: commit transaction หากทุกอย่างสำเร็จ
        $db->commit();

        // Clean output buffer
        ob_clean();

        // Return success response
        // การทำงาน: ส่ง response สำเร็จกลับพร้อมข้อมูลข่าวที่เพิ่งสร้าง
        echo json_encode([
            'success' => true,
            'message' => 'เพิ่มข่าวเรียบร้อยแล้ว',
            'news_data' => [
                'id' => (int) $newNewsData['id'],
                'title' => $newNewsData['title'],
                'content' => $newNewsData['content'],
                'category' => $newNewsData['category'],
                'image' => $newNewsData['image'],
                'status' => $newNewsData['status'],
                'member_access' => $newNewsData['member_access'],
                'created_at' => $newNewsData['created_at'],
                'updated_at' => $newNewsData['updated_at']
            ]
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        // Rollback transaction on error
        // การทำงาน: rollback transaction หากเกิดข้อผิดพลาด
        $db->rollBack();

        // Clean up uploaded file if database insert failed
        // การทำงาน: ลบไฟล์ที่อัปโหลดแล้วหากการบันทึกฐานข้อมูลล้มเหลว
        if (isset($uploadedImage) && $uploadedImage) {
            $imagePath = '../uploads/' . $uploadedImage;
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

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
    error_log('Database error in add_news.php: ' . $e->getMessage());

} catch (Exception $e) {
    // General error
    // การทำงาน: จัดการข้อผิดพลาดทั่วไป
    ob_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'ADD_ERROR'
    ], JSON_UNESCAPED_UNICODE);

    // Log error for debugging
    error_log('Add error in add_news.php: ' . $e->getMessage());

} finally {
    // End output buffering
    ob_end_flush();
}

/**
 * Handle image upload
 * 
 * การทำงาน:
 * - ตรวจสอบ error code จากการอัปโหลด
 * - ตรวจสอบขนาดไฟล์ (สูงสุด 2MB)
 * - ตรวจสอบประเภทไฟล์ด้วย MIME type
 * - สร้างชื่อไฟล์ใหม่ที่ไม่ซ้ำกัน
 * - สร้างโฟลเดอร์ uploads หากยังไม่มี
 * - ย้ายไฟล์จาก temp directory ไปยัง uploads directory
 * - คืนค่าชื่อไฟล์ที่บันทึกหากสำเร็จ
 * - โยน Exception หากเกิดข้อผิดพลาด
 * 
 * @param array $file - ข้อมูลไฟล์จาก $_FILES
 * @return string - ชื่อไฟล์ที่บันทึก
 * @throws Exception - หากเกิดข้อผิดพลาดในการอัปโหลด
 */
function handleImageUpload($file)
{
    // Check for upload errors
    // การทำงาน: ตรวจสอบ error code จากการอัปโหลด
    if ($file['error'] !== UPLOAD_ERR_OK) {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('ไฟล์ภาพมีขนาดใหญ่เกินไป (สูงสุด 2MB)');
            case UPLOAD_ERR_PARTIAL:
                throw new Exception('การอัปโหลดไฟล์ไม่สมบูรณ์');
            case UPLOAD_ERR_NO_TMP_DIR:
                throw new Exception('ไม่พบโฟลเดอร์ชั่วคราวสำหรับอัปโหลดไฟล์');
            case UPLOAD_ERR_CANT_WRITE:
                throw new Exception('ไม่สามารถเขียนไฟล์ได้');
            default:
                throw new Exception('เกิดข้อผิดพลาดในการอัปโหลดไฟล์');
        }
    }

    // Validate file size (2MB max)
    // การทำงาน: ตรวจสอบขนาดไฟล์ไม่เกิน 2MB
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new Exception('ไฟล์ภาพมีขนาดใหญ่เกินไป (สูงสุด 2MB)');
    }

    // Validate file type
    // การทำงาน: ตรวจสอบประเภทไฟล์ด้วย MIME type เพื่อความปลอดภัย
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('รูปแบบไฟล์ไม่ถูกต้อง (รองรับเฉพาะ JPG, JPEG, PNG, WebP)');
    }

    // Generate unique filename
    // การทำงาน: สร้างชื่อไฟล์ใหม่ที่ไม่ซ้ำกัน
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'news_' . date('YmdHis') . '_' . uniqid() . '.' . $extension;

    // Create uploads directory if not exists
    // การทำงาน: สร้างโฟลเดอร์ uploads หากยังไม่มี
    $uploadDir = '../uploads/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('ไม่สามารถสร้างโฟลเดอร์สำหรับเก็บไฟล์ได้');
        }
    }

    // Move uploaded file
    // การทำงาน: ย้ายไฟล์จาก temp directory ไปยัง uploads directory
    $uploadPath = $uploadDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('ไม่สามารถอัปโหลดไฟล์ได้');
    }

    return $filename;
}
?>