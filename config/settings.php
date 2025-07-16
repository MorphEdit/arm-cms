<?php
/**
 * System Settings Configuration
 * ARM CMS - Content Management System
 * 
 * ไฟล์การตั้งค่าระบบ - รวมการตั้งค่าทั้งหมดไว้ที่เดียว
 */

return [
    // การตั้งค่าข่าว
    'news' => [
        'related_news_limit' => 3,           // จำนวนข่าวที่เกี่ยวข้อง (เพิ่มจาก 3 เป็น 6)
        'news_per_page' => 12,              // จำนวนข่าวต่อหน้าในหน้าหลัก
        'excerpt_length' => 150,            // ความยาว excerpt (ตัวอักษร)
        'reading_speed_wpm' => 150,         // ความเร็วในการอ่าน (คำต่อนาที)
    ],
    
    // การตั้งค่าการอัปโหลดไฟล์
    'upload' => [
        'max_image_size' => 2097152,        // ขนาดไฟล์สูงสุด (2MB)
        'allowed_image_types' => [
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/webp'
        ],
        'upload_path' => '../uploads/',     // path สำหรับเก็บไฟล์
    ],
    
    // การตั้งค่า Pagination
    'pagination' => [
        'items_per_page' => 12,             // รายการต่อหน้า
        'max_items_per_page' => 50,         // สูงสุดต่อหน้า
        'load_more_increment' => 12,        // จำนวนที่โหลดเพิ่มทีละครั้ง
    ],
    
    // การตั้งค่า UI/UX
    'ui' => [
        'animation_duration' => 300,        // ระยะเวลา animation (ms)
        'auto_save_interval' => 30000,      // auto-save ทุก 30 วินาที
        'search_debounce_delay' => 500,     // หน่วงเวลาการค้นหา (ms)
    ],
    
    // การตั้งค่า SEO
    'seo' => [
        'meta_description_length' => 160,   // ความยาว meta description
        'og_image_width' => 1200,          // ความกว้าง Open Graph image
        'og_image_height' => 630,          // ความสูง Open Graph image
    ],
    
    // การตั้งค่าระบบ
    'system' => [
        'session_timeout' => 7200,          // session timeout (2 ชั่วโมง)
        'remember_me_duration' => 2592000,  // remember me (30 วัน)
        'max_login_attempts' => 5,          // จำนวนครั้งการ login ที่ผิดสูงสุด
        'cache_duration' => 3600,           // cache duration (1 ชั่วโมง)
    ],
    
    // การตั้งค่าหมวดหมู่
    'categories' => [
        'ทั่วไป' => [
            'icon' => '<i class="bi bi-layout-text-sidebar-reverse"></i>',
            'color' => '#007bff',
            'description' => 'ข่าวสารทั่วไป'
        ],
        'ประกาศ' => [
            'icon' => '<i class="bi bi-megaphone"></i>',
            'color' => '#dc3545',
            'description' => 'ประกาศสำคัญ'
        ],
        'กิจกรรม' => [
            'icon' => '<i class="bi bi-magic"></i>',
            'color' => '#28a745',
            'description' => 'ข่าวกิจกรรม'
        ]
    ],
    
    // การตั้งค่า Theme
    'theme' => [
        'primary_color' => 'rgb(224, 6, 42)',
        'secondary_color' => 'rgb(174, 5, 33)',
        'success_color' => '#28a745',
        'warning_color' => '#ffc107',
        'danger_color' => '#dc3545',
        'info_color' => '#17a2b8',
    ]
];
?>