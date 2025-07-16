<?php
/**
 * Public Footer (jQuery Version)
 * ARM CMS - Content Management System
 * 
 * Footer สำหรับหน้าสาธารณะ - ใช้ jQuery
 */
?>

</main>
<!-- Main Content End -->

<!-- Footer -->
<footer class="public-footer" role="contentinfo">
    <div class="container">

        <!-- Footer Content -->
        <div class="footer-content">

            <!-- About Section -->
            <div class="footer-section">
                <h3 class="footer-title">เกี่ยวกับเรา</h3>
                <p class="footer-text">
                    ระบบจัดการข่าวประชาสัมพันธ์ ARM CMS
                    พัฒนาขึ้นเพื่อให้การเผยแพร่ข่าวสารเป็นไปอย่างมีประสิทธิภาพ
                </p>
            </div>

            <!-- Quick Links -->
            <div class="footer-section">
                <h3 class="footer-title">เมนูด่วน</h3>
                <ul class="footer-nav">
                    <li><a href="index.php" class="footer-link">หน้าหลัก</a></li>
                    <li><a href="index.php?category=ทั่วไป" class="footer-link">ข่าวทั่วไป</a></li>
                    <li><a href="index.php?category=ประกาศ" class="footer-link">ประกาศ</a></li>
                    <li><a href="index.php?category=กิจกรรม" class="footer-link">กิจกรรม</a></li>
                </ul>
            </div>

            <!-- Categories Info -->
            <div class="footer-section">
                <h3 class="footer-title">หมวดหมู่ข่าว</h3>
                <div class="category-stats" id="categoryStats">
                    <!-- Will be populated by jQuery from main page -->
                    <div class="stats-loading">กำลังโหลด...</div>
                </div>
            </div>

        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <p class="copyright">
                    &copy; <?php echo date('Y'); ?> ARM CMS.
                </p>

                <!-- Back to Top Button -->
                <button class="back-to-top" id="backToTopBtn" title="กลับไปด้านบน" aria-label="กลับไปด้านบน">
                    ↑ ด้านบน
                </button>
            </div>
        </div>

    </div>
</footer>

<!-- Loading Overlay (Global) -->
<div id="globalLoadingOverlay" class="loading-overlay" style="display: none;">
    <div class="loading-spinner">
        <div class="spinner"></div>
        <p>กำลังโหลด...</p>
    </div>
</div>

<!-- jQuery CDN (if not already loaded) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<!-- Footer JavaScript (jQuery Version) -->
<script>
    /**
     * Global JavaScript functions for public pages (jQuery Version)
     */

    /**
     * Scroll to top function
     * การทำงาน: เลื่อนหน้าไปด้านบนอย่างนุ่มนวล
     */
    function scrollToTop() {
        $('html, body').animate({
            scrollTop: 0
        }, 500);
    }

    /**
     * Show/hide back to top button based on scroll position
     * การทำงาน: แสดง/ซ่อนปุ่มกลับด้านบนตามตำแหน่งการเลื่อน
     */
    function handleBackToTopVisibility() {
        const $backToTopBtn = $('#backToTopBtn');
        if ($(window).scrollTop() > 300) {
            $backToTopBtn.addClass('visible');
        } else {
            $backToTopBtn.removeClass('visible');
        }
    }

    /**
     * Display category statistics
     * การทำงาน: แสดงสถิติหมวดหมู่ที่ได้รับจาก event
     * @param {Object} stats - สถิติหมวดหมู่
     */
    function displayCategoryStats(stats) {
        const $container = $('#categoryStats');
        let html = '<div class="stats-grid">';

        const categories = {
            'ทั่วไป': '<i class="bi bi-layout-text-sidebar-reverse"></i>',
            'ประกาศ': '<i class="bi bi-megaphone"></i>',
            'กิจกรรม': '<i class="bi bi-magic"></i>'
        };

        $.each(categories, function(category, icon) {
            const categoryData = stats[category] || { total: 0 };
            const count = categoryData.total || 0;
            html += `
                <div class="stat-item">
                    <span class="stat-icon">${icon}</span>
                    <span class="stat-label">${category}</span>
                    <span class="stat-value">${count}</span>
                </div>
            `;
        });

        html += '</div>';
        $container.html(html);
    }

    /**
     * Handle category stats event from main pages
     * การทำงาน: รับ category stats จาก main page ผ่าน custom event
     */
    function handleCategoryStatsEvent(event, data) {
        const stats = data.stats;
        displayCategoryStats(stats);
    }

    /**
     * Fallback: Load category stats independently
     * การทำงาน: โหลดสถิติแยกเฉพาะเมื่อไม่ได้รับจาก main page
     */
    async function loadCategoryStatsFallback() {
        try {
            // รอ 2 วินาที หากไม่มี event มาจาก main page จึงโหลดเอง
            setTimeout(async () => {
                const $container = $('#categoryStats');
                // ถ้ายังแสดง "กำลังโหลด..." อยู่ แสดงว่าไม่ได้รับจาก main page
                if ($container.html().includes('กำลังโหลด')) {
                    try {
                        // โหลดแบบเดิม (fallback) ด้วย jQuery AJAX
                        const data = await $.ajax({
                            url: '../api/get_public_data.php',
                            method: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify({ limit: 1 }), // โหลดแค่ 1 รายการเพื่อได้ stats
                            dataType: 'json'
                        });

                        if (data.success && data.category_stats) {
                            displayCategoryStats(data.category_stats);
                        } else {
                            $container.html('<div class="stats-error">ไม่สามารถโหลดสถิติได้</div>');
                        }
                    } catch (error) {
                        console.error('Error loading category stats fallback:', error);
                        $container.html('<div class="stats-error">เกิดข้อผิดพลาด</div>');
                    }
                }
            }, 2000);
        } catch (error) {
            console.error('Error in loadCategoryStatsFallback:', error);
        }
    }

    /**
     * Global loading overlay functions
     * การทำงาน: ฟังก์ชันสำหรับจัดการ loading overlay ทั่วระบบ
     */
    window.showGlobalLoading = function (message = 'กำลังโหลด...') {
        const $overlay = $('#globalLoadingOverlay');
        $overlay.find('p').text(message);
        $overlay.show();
    };

    window.hideGlobalLoading = function () {
        $('#globalLoadingOverlay').hide();
    };

    /**
     * Format date for Thai locale
     * การทำงาน: จัดรูปแบบวันที่เป็นภาษาไทย
     * @param {string} dateString - วันที่ในรูปแบบ string
     * @return {string} - วันที่ที่จัดรูปแบบแล้ว
     */
    window.formatThaiDate = function (dateString) {
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('th-TH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (error) {
            return dateString;
        }
    };

    /**
     * Truncate text with ellipsis
     * การทำงาน: ตัดข้อความและเพิ่มจุดไข่ปลา
     * @param {string} text - ข้อความต้นฉบับ
     * @param {number} maxLength - ความยาวสูงสุด
     * @return {string} - ข้อความที่ตัดแล้ว
     */
    window.truncateText = function (text, maxLength) {
        if (!text) return '';
        return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
    };

    /**
     * Initialize on DOM loaded (jQuery Version)
     * การทำงาน: เริ่มต้นการทำงานเมื่อ DOM โหลดเสร็จ
     */
    $(document).ready(function () {

        // Back to top button click
        $('#backToTopBtn').on('click', function() {
            scrollToTop();
        });

        // Initialize scroll events
        $(window).on('scroll', function() {
            handleBackToTopVisibility();
        });
        
        // Initial check
        handleBackToTopVisibility();

        // ===== ฟัง event จาก main page =====
        $(document).on('categoryStatsLoaded', handleCategoryStatsEvent);
        
        // Fallback: โหลดเองหากไม่ได้รับจาก main page
        loadCategoryStatsFallback();

        // Add smooth scrolling to anchor links
        $('a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            const target = $(this.getAttribute('href'));
            if (target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top
                }, 500);
            }
        });

        // Add loading states to external links
        $('a[href^="http"]').on('click', function() {
            if (this.target !== '_blank') {
                showGlobalLoading('กำลังโหลดหน้าใหม่...');
            }
        });

        // Handle online/offline status
        $(window).on('online', function () {
            console.log('กลับมาออนไลน์แล้ว');
            // ไม่ต้อง reload category stats เพราะได้จาก main page แล้ว
        });

        $(window).on('offline', function () {
            console.log('ขาดการเชื่อมต่ออินเทอร์เน็ต');
        });

        // Smooth transitions for footer links
        $('.footer-link').on('mouseenter', function() {
            $(this).css('transition', 'all 0.3s ease');
        });

        // Add click tracking for footer links (optional)
        $('.footer-link').on('click', function() {
            const linkText = $(this).text();
            console.log('Footer link clicked:', linkText);
            // Add analytics tracking here if needed
        });

        // Auto-update copyright year
        const currentYear = new Date().getFullYear();
        $('.copyright').html(`&copy; ${currentYear} ARM CMS.`);

        // Add loading animation to category stats
        $('#categoryStats').on('DOMSubtreeModified', function() {
            // Animate stats when loaded
            $('.stat-item').each(function(index) {
                $(this).delay(index * 100).animate({
                    opacity: 1,
                    transform: 'translateY(0)'
                }, 300);
            });
        });

    });

</script>

<!-- Additional footer scripts -->
<?php if (isset($additionalFooter)): ?>
    <?php echo $additionalFooter; ?>
<?php endif; ?>

</body>

</html>