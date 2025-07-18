<?php
/**
 * Get News Complete API (Updated with Config)
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

    // Validate JSON input
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }

    // Validate required fields
    if (!isset($data['id']) || empty($data['id'])) {
        throw new Exception('ID ข่าวไม่ถูกต้อง');
    }

    $newsId = (int) $data['id'];

    // Validate ID is positive integer
    if ($newsId <= 0) {
        throw new Exception('ID ข่าวไม่ถูกต้อง');
    }

    // ===== ใช้การตั้งค่าจาก config =====
    $relatedNewsLimit = $config['news']['related_news_limit'];
    $excerptLength = $config['news']['excerpt_length'];
    $readingSpeed = $config['news']['reading_speed_wpm'];

    // ===== SINGLE DATABASE QUERY =====
    $stmt = $db->prepare("
        SELECT 
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
        WHERE status = 'active'
        ORDER BY created_at DESC, id DESC
    ");

    $stmt->execute();
    $allNews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ===== PROCESS DATA IN PHP =====

    // 1. Find main news
    $mainNews = findMainNews($allNews, $newsId);
    if (!$mainNews) {
        throw new Exception('ไม่พบข่าวที่ต้องการ หรือข่าวถูกปิดการใช้งาน');
    }

    // 1.1 Check member access
    if ($mainNews['member_access'] === 'member') {
        if (!isLoggedIn() || $_SESSION['role'] !== 'member') {
            throw new Exception('ข่าวนี้เฉพาะสมาชิกเท่านั้น');
        }
    }

    // 2. Find related news (ใช้ config limit)
    $relatedNews = findRelatedNews($allNews, $mainNews['category'], $newsId, $relatedNewsLimit);

    // 3. Find navigation (prev/next)
    $navigation = findNavigation($allNews, $newsId);

    // 4. Calculate category statistics
    $categoryStats = calculateCategoryStats($allNews, $config);

    // 5. Process main news for display (ใช้ config สำหรับ reading time)
    $processedNews = [
        'id' => (int) $mainNews['id'],
        'title' => $mainNews['title'],
        'content' => $mainNews['content'],
        'category' => $mainNews['category'],
        'image' => $mainNews['image'],
        'image_url' => !empty($mainNews['image']) ? '../uploads/' . $mainNews['image'] : null,
        'created_at' => $mainNews['created_at'],
        'updated_at' => $mainNews['updated_at'],
        'formatted_date' => formatThaiDate($mainNews['created_at']),
        'formatted_updated_date' => formatThaiDate($mainNews['updated_at']),
        'reading_time' => calculateReadingTime($mainNews['content'], $readingSpeed)
    ];

    // 6. Process related news
    $processedRelatedNews = [];
    foreach ($relatedNews as $item) {
        $processedRelatedNews[] = [
            'id' => (int) $item['id'],
            'title' => $item['title'],
            'category' => $item['category'],
            'image' => $item['image'],
            'image_url' => !empty($item['image']) ? '../uploads/' . $item['image'] : null,
            'created_at' => $item['created_at'],
            'formatted_date' => formatThaiDate($item['created_at']),
            'url' => 'news.php?id=' . $item['id']
        ];
    }

    // 7. Process navigation
    $processedNavigation = [
        'prev' => $navigation['prev'] ? [
            'id' => (int) $navigation['prev']['id'],
            'title' => $navigation['prev']['title'],
            'url' => 'news.php?id=' . $navigation['prev']['id']
        ] : null,
        'next' => $navigation['next'] ? [
            'id' => (int) $navigation['next']['id'],
            'title' => $navigation['next']['title'],
            'url' => 'news.php?id=' . $navigation['next']['id']
        ] : null
    ];

    // 8. Category info (ใช้ config สำหรับ icon)
    $categoryInfo = [
        'name' => $mainNews['category'],
        'total_count' => $categoryStats[$mainNews['category']]['total'] ?? 0,
        'icon' => $config['categories'][$mainNews['category']]['icon'] ?? '📄'
    ];

    // Clean output buffer
    ob_clean();

    // Return unified response พร้อมข้อมูล config
    echo json_encode([
        'success' => true,
        'news' => $processedNews,
        'related_news' => $processedRelatedNews,
        'navigation' => $processedNavigation,
        'category_info' => $categoryInfo,
        'category_stats' => $categoryStats,
        'config' => [
            'related_news_limit' => $relatedNewsLimit,
            'total_related_found' => count($relatedNews)
        ],
        'meta' => [
            'generated_at' => date('Y-m-d H:i:s'),
            'cache_until' => date('Y-m-d H:i:s', strtotime('+' . $config['system']['cache_duration'] . ' seconds')),
            'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            'total_news_in_system' => count($allNews)
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    // Database error
    ob_clean();
    http_response_code(500);

    // Log error for debugging
    error_log('Database error in get_news_complete.php: ' . $e->getMessage());

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
    error_log('General error in get_news_complete.php: ' . $e->getMessage());

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
 * Find main news by ID
 */
function findMainNews($allNews, $newsId)
{
    foreach ($allNews as $news) {
        if ((int) $news['id'] === $newsId) {
            return $news;
        }
    }
    return null;
}

/**
 * Find related news (ใช้ parameter limit จาก config)
 */
function findRelatedNews($allNews, $category, $currentId, $limit)
{
    $related = [];

    foreach ($allNews as $news) {
        if ($news['category'] === $category && (int) $news['id'] !== $currentId) {
            $related[] = $news;

            if (count($related) >= $limit) {
                break;
            }
        }
    }

    return $related;
}

/**
 * Find navigation (previous and next news)
 */
function findNavigation($allNews, $currentId)
{
    $prev = null;
    $next = null;
    $currentIndex = null;

    // Find current news index
    foreach ($allNews as $index => $news) {
        if ((int) $news['id'] === $currentId) {
            $currentIndex = $index;
            break;
        }
    }

    if ($currentIndex !== null) {
        // Previous news
        if (isset($allNews[$currentIndex + 1])) {
            $prev = [
                'id' => $allNews[$currentIndex + 1]['id'],
                'title' => $allNews[$currentIndex + 1]['title']
            ];
        }

        // Next news
        if (isset($allNews[$currentIndex - 1])) {
            $next = [
                'id' => $allNews[$currentIndex - 1]['id'],
                'title' => $allNews[$currentIndex - 1]['title']
            ];
        }
    }

    return [
        'prev' => $prev,
        'next' => $next
    ];
}

/**
 * Calculate category statistics (ใช้ config สำหรับ icons)
 */
function calculateCategoryStats($allNews, $config)
{
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
            if (
                is_null($stats[$category]['latest_date']) ||
                $news['created_at'] > $stats[$category]['latest_date']
            ) {

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
 * Format date to Thai format
 */
function formatThaiDate($dateString)
{
    try {
        $date = new DateTime($dateString);

        // Thai month names
        $thaiMonths = [
            1 => 'มกราคม',
            2 => 'กุมภาพันธ์',
            3 => 'มีนาคม',
            4 => 'เมษายน',
            5 => 'พฤษภาคม',
            6 => 'มิถุนายน',
            7 => 'กรกฎาคม',
            8 => 'สิงหาคม',
            9 => 'กันยายน',
            10 => 'ตุลาคม',
            11 => 'พฤศจิกายน',
            12 => 'ธันวาคม'
        ];

        $day = $date->format('j');
        $month = $thaiMonths[(int) $date->format('n')];
        $year = $date->format('Y') + 543; // Convert to Buddhist Era
        $time = $date->format('H:i');

        return "{$day} {$month} {$year} เวลา {$time} น.";

    } catch (Exception $e) {
        return $dateString;
    }
}

/**
 * Calculate estimated reading time (ใช้ config สำหรับ reading speed)
 */
function calculateReadingTime($content, $wordsPerMinute = 150)
{
    if (empty($content)) {
        return '1 นาที';
    }

    // Remove HTML tags and count words
    $text = strip_tags($content);
    $wordCount = str_word_count($text);

    $minutes = max(1, ceil($wordCount / $wordsPerMinute));

    if ($minutes === 1) {
        return '1 นาที';
    } else {
        return $minutes . ' นาที';
    }
}
?>