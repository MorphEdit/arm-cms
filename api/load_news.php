<?php
/**
 * Load News API
 * ARM CMS - Content Management System
 * 
 * API สำหรับโหลดข้อมูลข่าวจากฐานข้อมูล พร้อมระบบกรองข้อมูล
 * รองรับการกรองตาม category, status และการค้นหาใน title/content
 */

// Set content type to JSON
// การทำงาน: กำหนด Content-Type เป็น JSON และ charset เป็น utf-8 เพื่อให้ client รู้ว่าจะได้รับข้อมูล JSON
header('Content-Type: application/json; charset=utf-8');

// Allow CORS if needed
// การทำงาน: กำหนด CORS headers เพื่อรองรับการเรียกจาก domain อื่น (หากจำเป็น)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Start output buffering to prevent any unwanted output
// การทำงาน: เริ่ม output buffering เพื่อป้องกันการแสดงผลที่ไม่ต้องการก่อนส่ง JSON response
ob_start();

try {
    // Include authentication
    // require_once '../config/auth.php';
    
    // Require API authentication
    // requireApiAuth();

    // Include database connection
    require_once '../config/database.php';
    
    // Get database connection
    // การทำงาน: ดึงการเชื่อมต่อฐานข้อมูลจาก singleton pattern ใน database.php
    $db = getDatabase();
    
    // Only allow POST requests
    // การทำงาน: ตรวจสอบว่าเป็น POST request เท่านั้น เพื่อความปลอดภัย (ป้องกัน GET request ที่อาจมี parameters ใน URL)
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    // Get JSON input
    // การทำงาน: อ่านข้อมูล JSON ที่ส่งมาจาก client (AJAX request จาก news-manager.js)
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Validate JSON input - ถ้า decode ไม่ได้ให้ใช้ค่าเริ่มต้น
    // การทำงาน: ตรวจสอบว่า JSON ที่ได้รับสามารถ decode ได้หรือไม่ หากไม่ได้จะใช้ array เปล่าแทน
    if (json_last_error() !== JSON_ERROR_NONE) {
        $data = [];
    }
    
    // Extract filters with defaults
    // การทำงาน: ดึงข้อมูล filter จาก request และกำหนดค่าเริ่มต้นเป็นสตริงเปล่าหากไม่มีข้อมูล
    $category = isset($data['category']) ? trim($data['category']) : '';
    $status = isset($data['status']) ? trim($data['status']) : '';
    $search = isset($data['search']) ? trim($data['search']) : '';
    
    // Build base query
    // การทำงาน: สร้าง SQL query พื้นฐานสำหรับดึงข้อมูลข่าว เลือกเฉพาะ columns ที่จำเป็น
    $sql = "SELECT 
                id, 
                title, 
                content, 
                category, 
                image, 
                status, 
                member_access, 
                created_at, 
                updated_at
            FROM cms 
            WHERE 1=1";
    
    $params = [];
    
    // Add category filter
    // การทำงาน: เพิ่มเงื่อนไขกรองตามหมวดหมู่หากมีการระบุค่า category
    if (!empty($category)) {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    
    // Add status filter
    // การทำงาน: เพิ่มเงื่อนไขกรองตามสถานะหากมีการระบุค่า status
    if (!empty($status)) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    // Add search filter
    // การทำงาน: เพิ่มเงื่อนไขค้นหาในหัวข้อและเนื้อหาหากมีการระบุคำค้นหา ใช้ LIKE pattern เพื่อค้นหาข้อความที่มีคำค้นหาอยู่
    if (!empty($search)) {
        $sql .= " AND (title LIKE ? OR content LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    // Add ordering
    // การทำงาน: เรียงลำดับข้อมูลตามวันที่สร้างล่าสุดก่อน แล้วตาม ID ล่าสุด เพื่อให้ข่าวใหม่แสดงก่อนเสมอ
    $sql .= " ORDER BY created_at DESC, id DESC";
    
    // Add limit to prevent memory issues
    // การทำงาน: จำกัดจำนวนข้อมูลที่ดึงมาไม่เกิน 1000 records เพื่อป้องกันปัญหา memory และประสิทธิภาพ
    $sql .= " LIMIT 1000";
    
    // Prepare and execute query
    // การทำงาน: เตรียมและ execute SQL query ด้วย prepared statement เพื่อป้องกัน SQL injection
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    // Fetch all results
    // การทำงาน: ดึงข้อมูลทั้งหมดที่ตรงตามเงื่อนไขเป็น associative array
    $news = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process results
    // การทำงาน: ประมวลผลข้อมูลก่อนส่งกลับ แปลง data types และ sanitize ข้อมูลเพื่อความปลอดภัย
    $processedNews = [];
    foreach ($news as $item) {
        $processedNews[] = [
            'id' => (int)$item['id'],
            'title' => $item['title'],
            'content' => $item['content'],
            'category' => $item['category'],
            'image' => $item['image'],
            'status' => $item['status'],
            'member_access' => $item['member_access'],
            'created_at' => $item['created_at'],
            'updated_at' => $item['updated_at']
        ];
    }
    
    // Clean output buffer
    // การทำงาน: ล้าง output buffer เพื่อให้แน่ใจว่าจะส่งเฉพาะ JSON response โดยไม่มีข้อความอื่นปนเปื้อน
    ob_clean();
    
    // Return success response
    // การทำงาน: ส่ง JSON response กลับพร้อมข้อมูลข่าว จำนวนข่าว และ filter ที่ใช้ ใช้ JSON_UNESCAPED_UNICODE เพื่อแสดงภาษาไทยถูกต้อง
    echo json_encode([
        'success' => true,
        'news' => $processedNews,
        'total' => count($processedNews),
        'filters' => [
            'category' => $category,
            'status' => $status,
            'search' => $search
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    // Database error
    // การทำงาน: จัดการข้อผิดพลาดจากฐานข้อมูล ล้าง output buffer แล้วส่ง HTTP status 500
    ob_clean();
    http_response_code(500);
    
    // Log error for debugging
    // การทำงาน: บันทึก error ลง server log เพื่อใช้ในการ debug โดยไม่เปิดเผยข้อมูลให้ผู้ใช้
    error_log('Database error in load_news.php: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล',
        'error_code' => 'DB_ERROR'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // General error
    // การทำงาน: จัดการข้อผิดพลาดทั่วไป ล้าง output buffer แล้วส่ง HTTP status 400
    ob_clean();
    http_response_code(400);
    
    // Log error for debugging
    // การทำงาน: บันทึก error ลง server log เพื่อใช้ในการ debug
    error_log('General error in load_news.php: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'GENERAL_ERROR'
    ], JSON_UNESCAPED_UNICODE);
    
} finally {
    // End output buffering
    // การทำงาน: จบการใช้งาน output buffering ในทุกกรณี (สำเร็จหรือเกิดข้อผิดพลาด)
    ob_end_flush();
}
?>