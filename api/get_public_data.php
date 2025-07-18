<?php
/**
 * Get Public Data API (Updated with Config)
 * ARM CMS - Content Management System
 * 
 * เพิ่มการใช้ config file สำหรับตั้งค่าต่างๆ
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
    // Include database connection
    require_once '../config/database.php';
    
    // **เพิ่มการโหลด config file**
    $config = include('../config/settings.php');
    
    // Get database connection
    $db = getDatabase();
    
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Validate JSON input - ถ้า decode ไม่ได้ให้ใช้ค่าเริ่มต้น
    if (json_last_error() !== JSON_ERROR_NONE) {
        $data = [];
    }
    
    // Extract parameters with defaults (ใช้ config)
    $category = isset($data['category']) ? trim($data['category']) : '';
    $search = isset($data['search']) ? trim($data['search']) : '';
    $offset = isset($data['offset']) ? (int)$data['offset'] : 0;
    $limit = isset($data['limit']) ? (int)$data['limit'] : $config['pagination']['items_per_page']; // ใช้จาก config
    
    // Validate and limit parameters (ใช้ config)
    $offset = max(0, $offset);
    $limit = max(1, min($config['pagination']['max_items_per_page'], $limit)); // ใช้ max จาก config
    
    // ===== SINGLE DATABASE QUERY =====
    $stmt = $db->prepare("
        SELECT 
            id, 
            title, 
            content, 
            category, 
            image, 
            created_at, 
            updated_at
        FROM cms 
        WHERE status = 'active'
        ORDER BY created_at DESC, id DESC
    ");
    
    $stmt->execute();
    $allNews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ===== PROCESS DATA IN PHP =====
    
    // 1. Filter news ตามเงื่อนไข
    $filteredNews = filterNews($allNews, $category, $search);
    
    // 2. Calculate pagination
    $totalItems = count($filteredNews);
    $paginatedNews = array_slice($filteredNews, $offset, $limit);
    
    // 3. Calculate category statistics (ใช้ config)
    $categoryStats = calculateCategoryStats($allNews, $config);
    
    // 4. Process news for display (ใช้ config สำหรับ excerpt length)
    $processedNews = [];
    foreach ($paginatedNews as $item) {
        $processedNews[] = [
            'id' => (int)$item['id'],
            'title' => $item['title'],
            'content' => $item['content'],
            'excerpt' => createExcerpt($item['content'], $config['news']['excerpt_length']), // ใช้จาก config
            'category' => $item['category'],
            'image' => $item['image'],
            'image_url' => !empty($item['image']) ? '../uploads/' . $item['image'] : null,
            'created_at' => $item['created_at'],
            'updated_at' => $item['updated_at'],
            'formatted_date' => formatThaiDate($item['created_at']),
            'url' => 'news.php?id=' . $item['id']
        ];
    }
    
    // 5. Calculate pagination info
    $hasMore = ($offset + $limit) < $totalItems;
    $nextOffset = $hasMore ? $offset + $limit : null;
    $currentPage = floor($offset / $limit) + 1;
    $totalPages = ceil($totalItems / $limit);
    
    // Clean output buffer
    ob_clean();
    
    // Return unified response พร้อมข้อมูล config
    echo json_encode([
        'success' => true,
        'news' => $processedNews,
        'category_stats' => $categoryStats,
        'pagination' => [
            'total_items' => (int)$totalItems,
            'items_per_page' => $limit,
            'current_offset' => $offset,
            'next_offset' => $nextOffset,
            'has_more' => $hasMore,
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'showing_from' => $offset + 1,
            'showing_to' => min($offset + $limit, $totalItems)
        ],
        'filters' => [
            'category' => $category,
            'search' => $search
        ],
        'config' => [
            'items_per_page' => $config['pagination']['items_per_page'],
            'max_items_per_page' => $config['pagination']['max_items_per_page'],
            'excerpt_length' => $config['news']['excerpt_length']
        ],
        'meta' => [
            'generated_at' => date('Y-m-d H:i:s'),
            'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            'total_news_in_system' => count($allNews)
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    // Database error
    ob_clean();
    http_response_code(500);
    
    // Log error for debugging
    error_log('Database error in get_public_data.php: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล',
        'error_code' => 'DB_ERROR'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // General error
    ob_clean();
    http_response_code(400);
    
    // Log error for debugging
    error_log('General error in get_public_data.php: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'GENERAL_ERROR'
    ], JSON_UNESCAPED_UNICODE);
    
} finally {
    // End output buffering
    ob_end_flush();
}

/**
 * Filter news based on criteria
 */
function filterNews($allNews, $category, $search) {
    $filtered = $allNews;
    
    // Filter by category
    if (!empty($category)) {
        $filtered = array_filter($filtered, function($news) use ($category) {
            return $news['category'] === $category;
        });
    }
    
    // Filter by search
    if (!empty($search)) {
        $searchLower = mb_strtolower($search, 'UTF-8');
        $filtered = array_filter($filtered, function($news) use ($searchLower) {
            $titleLower = mb_strtolower($news['title'], 'UTF-8');
            $contentLower = mb_strtolower($news['content'], 'UTF-8');
            
            return (strpos($titleLower, $searchLower) !== false) || 
                   (strpos($contentLower, $searchLower) !== false);
        });
    }
    
    // Re-index array
    return array_values($filtered);
}

/**
 * Calculate category statistics (ใช้ config สำหรับ icons)
 */
function calculateCategoryStats($allNews, $config) {
    $stats = [];
    
    // Initialize categories จาก config
    foreach ($config['categories'] as $category => $categoryConfig) {
        $stats[$category] = [
            'total' => 0,
            'active' => 0,
            'latest_date' => null,
            'latest_news' => null,
            'icon' => $categoryConfig['icon']
        ];
    }
    
    // Count and find latest for each category
    foreach ($allNews as $news) {
        $category = $news['category'];
        
        if (isset($stats[$category])) {
            $stats[$category]['total']++;
            $stats[$category]['active']++; // เนื่องจาก query เฉพาะ active แล้ว
            
            // Update latest news if this is newer
            if (is_null($stats[$category]['latest_date']) || 
                $news['created_at'] > $stats[$category]['latest_date']) {
                
                $stats[$category]['latest_date'] = $news['created_at'];
                $stats[$category]['latest_news'] = [
                    'title' => $news['title'],
                    'date' => $news['created_at']
                ];
            }
        }
    }
    
    return $stats;
}

/**
 * Create excerpt from content (ใช้ config สำหรับ max length)
 */
function createExcerpt($content, $maxLength = 150) {
    if (empty($content)) {
        return '';
    }
    
    // Remove HTML tags and decode entities
    $text = html_entity_decode(strip_tags($content), ENT_QUOTES, 'UTF-8');
    
    // Remove extra whitespace
    $text = preg_replace('/\s+/', ' ', trim($text));
    
    if (mb_strlen($text, 'UTF-8') <= $maxLength) {
        return $text;
    }
    
    // Truncate and find last complete word
    $truncated = mb_substr($text, 0, $maxLength, 'UTF-8');
    $lastSpace = mb_strrpos($truncated, ' ', 0, 'UTF-8');
    
    if ($lastSpace !== false && $lastSpace > $maxLength * 0.7) {
        $truncated = mb_substr($truncated, 0, $lastSpace, 'UTF-8');
    }
    
    return $truncated . '...';
}

/**
 * Format date to Thai format
 */
function formatThaiDate($dateString) {
    try {
        $date = new DateTime($dateString);
        
        // Thai month names
        $thaiMonths = [
            1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม',
            4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
            7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน',
            10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
        ];
        
        $day = $date->format('j');
        $month = $thaiMonths[(int)$date->format('n')];
        $year = $date->format('Y') + 543; // Convert to Buddhist Era
        $time = $date->format('H:i');
        
        return "{$day} {$month} {$year} เวลา {$time} น.";
        
    } catch (Exception $e) {
        return $dateString;
    }
}
?>