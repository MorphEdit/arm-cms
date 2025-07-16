<?php
/**
 * Main Index Page
 * ARM CMS - Content Management System
 * 
 * หน้าหลักของระบบจัดการข่าว แสดงรายการข่าวพร้อมระบบกรองและจัดการ
 */

// Start session
session_start();

// Include authentication
require_once 'config/auth.php';

// Require login to access this page
requireLogin();

// Get current user for display
$currentUser = getCurrentUser();

// Include database connection
require_once 'config/database.php';

// Set page title
$pageTitle = 'ระบบจัดการข่าวประชาสัมพันธ์';
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ระบบจัดการข่าวประชาสัมพันธ์ ARM CMS">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">

    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/main.css">

    <!-- jQuery CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <!-- Meta tags for SEO -->
    <meta name="robots" content="noindex, nofollow">
    <meta name="author" content="ARM CMS">
</head>

<body>
    <!-- Main Container -->
    <div class="container">

        <!-- Header Section -->
        <header class="header-2">
            <div class="header-content">
                <div class="header-left">
                    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
                    <p>ARM CMS - Content Management System</p>
                </div>
                <div class="header-right">
                    <div class="user-info">
                        <span class="welcome-text">สวัสดี,
                            <strong><?php echo htmlspecialchars(getUserDisplayName()); ?></strong></span>
                        <span class="user-role">(<?php echo htmlspecialchars($_SESSION['role']); ?>)</span>
                    </div>
                    <div class="header-actions">
                        <a href="public/index.php" class="btn btn-outline" target="_blank" title="ดูหน้าข่าวสาธารณะ">
                            <i class="bi bi-book"></i> หน้าข่าว
                        </a>
                        <a href="public/logout.php" class="btn btn-danger" title="ออกจากระบบ">
                            <i class="bi bi-box-arrow-in-right"></i> ออกจากระบบ
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Controls Section -->
        <div class="controls">
            <!-- Filters -->
            <div class="filters">
                <div class="filter-group">
                    <label for="categoryFilter">หมวดหมู่:</label>
                    <select id="categoryFilter" name="category">
                        <option value="">ทั้งหมด</option>
                        <option value="ทั่วไป">ทั่วไป</option>
                        <option value="ประกาศ">ประกาศ</option>
                        <option value="กิจกรรม">กิจกรรม</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="statusFilter">สถานะ:</label>
                    <select id="statusFilter" name="status">
                        <option value="">ทั้งหมด</option>
                        <option value="active">เปิดใช้งาน</option>
                        <option value="inactive">ปิดใช้งาน</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="searchInput">ค้นหา:</label>
                    <div class="search-box">
                        <input type="text" id="searchInput" name="search" placeholder="ค้นหาหัวข้อข่าว..."
                            autocomplete="off">
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="pages/add_news.php" class="btn btn-success" title="เพิ่มข่าวใหม่">
                    + เพิ่มข่าวใหม่
                </a>
                <!-- <button type="button" class="btn" onclick="newsManager.refresh()" title="รีเฟรชข้อมูล">
                    🔄 รีเฟรช
                </button>
                <button type="button" class="btn" onclick="newsManager.clearFilters()" title="ล้างตัวกรอง">
                    ❌ ล้างตัวกรอง
                </button> -->
            </div>
        </div>

        <!-- News Table Section -->
        <main class="table-container">
            <table>
                <thead>
                    <tr>
                        <th width="5%">ลำดับ</th>
                        <th width="10%">ภาพ</th>
                        <th width="35%">หัวข้อข่าว</th>
                        <th width="12%">หมวดหมู่</th>
                        <th width="10%">สถานะ</th>
                        <th width="15%">วันที่สร้าง</th>
                        <th width="13%">จัดการ</th>
                    </tr>
                </thead>
                <tbody id="newsTable">
                    <!-- Data will be loaded here via jQuery -->
                    <tr>
                        <td colspan="7" class="loading">กำลังโหลดข้อมูล...</td>
                    </tr>
                </tbody>
            </table>
        </main>

        <!-- Footer -->
        <footer class="footer">
            <p>&copy; <?php echo date('Y'); ?> CMS.</p>
        </footer>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>กำลังประมวลผล...</p>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="assets/js/news-manager.js"></script>

    <!-- Additional JavaScript for page-specific functionality -->
    <script>
        // Page-specific JavaScript (jQuery Version)
        /**
         * Show tooltip (jQuery Version)
         * การทำงาน:
         * - สร้าง tooltip element ใหม่
         * - ดึงข้อความจาก title attribute
         * - คำนวณตำแหน่งและแสดง tooltip
         * @param {Event} e - mouse enter event
         */
        function showTooltip(e) {
            const $tooltip = $('<div class="tooltip"></div>')
                .text($(e.target).attr('title'))
                .appendTo('body');

            // Position tooltip
            const rect = e.target.getBoundingClientRect();
            $tooltip.css({
                left: rect.left + 'px',
                top: (rect.top - 30) + 'px'
            });
        }

        /**
         * Hide tooltip (jQuery Version)
         * การทำงาน:
         * - ค้นหาและลบ tooltip element
         * - เรียกใช้เมื่อ mouse leave จาก element
         */
        function hideTooltip() {
            $('.tooltip').remove();
        }

        /**
         * Update page statistics
         * การทำงาน:
         * - ฟังก์ชันสำหรับแสดงสถิติของหน้า
         * - ปัจจุบันแค่ log ข้อความ สามารถพัฒนาเพิ่มเติมได้
         * - เช่น แสดงจำนวนข่าวทั้งหมด หรือข้อมูลสถิติอื่นๆ
         */
        function updatePageStats() {
            // This function can be enhanced to show news statistics
            console.log('Page loaded successfully');
        }

        /**
         * Show loading overlay (jQuery Version)
         * การทำงาน:
         * - แสดง loading overlay เมื่อมีการประมวลผล
         * - รับ parameter message เพื่อแสดงข้อความที่กำหนด
         * - ใช้เมื่อต้องการแสดงสถานะกำลังโหลด
         * @param {string} message - ข้อความที่ต้องการแสดง (default: 'กำลังประมวลผล...')
         */
        function showLoadingOverlay(message = 'กำลังประมวลผล...') {
            const $overlay = $('#loadingOverlay');
            $overlay.find('p').text(message);
            $overlay.show();
        }

        /**
         * Hide loading overlay (jQuery Version)
         * การทำงาน:
         * - ซ่อน loading overlay
         * - เรียกใช้เมื่อการประมวลผลเสร็จสิ้น
         */
        function hideLoadingOverlay() {
            $('#loadingOverlay').hide();
        }

        /**
         * Handle online/offline status (jQuery Version)
         * การทำงาน:
         * - ตรวจจับสถานะการเชื่อมต่ออินเทอร์เน็ต
         * - เมื่อกลับมา online จะรีเฟรชข้อมูล
         * - เมื่อ offline จะแจ้งเตือนผู้ใช้
         */
        $(window).on('online', function () {
            console.log('Connection restored');
            newsManager.refresh();
        });

        $(window).on('offline', function () {
            console.log('Connection lost');
            newsManager.showAlert('การเชื่อมต่ออินเทอร์เน็ตขาดหาย', 'warning');
        });
    </script>

    <!-- Page-specific CSS -->
    <style>
        /* Additional styles for this page */
        .action-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            color: #6c757d;
            font-size: 14px;
            border-top: 1px solid #dee2e6;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-spinner {
            background: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .spinner {
            width: 40px;
            height: 40px;
            margin: 0 auto 15px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid rgb(224, 6, 42);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .tooltip {
            position: absolute;
            background: #333;
            color: white;
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 1000;
            pointer-events: none;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
                width: 100%;
            }

            .action-buttons .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</body>

</html>