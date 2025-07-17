<?php
/**
 * Public News Detail Page (jQuery Version)
 * ARM CMS - Content Management System
 * 
 * หน้าแสดงรายละเอียดข่าวสำหรับผู้ใช้ทั่วไป - ใช้ jQuery
 */

// Start session
session_start();

if (empty($_SESSION["IsLogin"]) && $_SESSION["IsLogin"] == false){
    header('Location: 401.php');
}

// Include database connection
require_once '../config/database.php';

// Test database connection
if (!testConnection()) {
    die('<div style="text-align:center;padding:50px;color:#dc3545;">
         <h2>ไม่สามารถเชื่อมต่อฐานข้อมูลได้</h2>
         <p>กรุณาลองใหม่อีกครั้งในภายหลัง</p>
         </div>');
}

// Get news ID from URL
$newsId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Redirect if invalid ID
if ($newsId <= 0) {
    header('Location: index.php');
    exit();
}

// Set default metadata (จะถูกอัปเดตโดย jQuery)
$pageTitle = 'ข่าวประชาสัมพันธ์';
$pageDescription = 'ข่าวประชาสัมพันธ์และข้อมูลข่าวสารต่างๆ จากระบบ ARM CMS';

// Set current URL for sharing
$currentUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// Set default Open Graph image
$ogImage = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/assets/images/default-share.jpg';

// Set default breadcrumb (จะถูกอัปเดตโดย jQuery)
$breadcrumb = [
    ['title' => 'หน้าหลัก', 'url' => 'index.php'],
    ['title' => 'ข่าวประชาสัมพันธ์', 'url' => '']
];

// Include header
include 'includes/header.php';
?>

<!-- News Detail Container -->
<div class="container">
    
    <!-- Loading State -->
    <div id="loadingState" class="loading-state">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>กำลังโหลดข่าว...</p>
        </div>
    </div>

    <!-- Error State -->
    <div id="errorState" class="error-state" style="display: none;">
        <div class="error-content">
            <div class="error-icon">⚠️</div>
            <h2>ไม่พบข่าวที่คุณต้องการ</h2>
            <p id="errorMessage">ข่าวนี้อาจถูกลบหรือไม่อยู่ในระบบ</p>
            <div class="error-actions">
                <a href="index.php" class="btn btn-primary">กลับหน้าหลัก</a>
                <button type="button" class="btn btn-secondary" id="retryBtn">ลองใหม่</button>
            </div>
        </div>
    </div>
    
    <!-- News Content (จะถูกสร้างโดย jQuery) -->
    <div id="newsContent" style="display: none;">
        
        <!-- Back Button -->
        <div class="back-navigation">
            <button type="button" class="btn btn-secondary back-btn" id="backBtn">
                ← กลับไปหน้าก่อน
            </button>
            <a href="index.php" class="btn btn-outline">
                <i class="bi bi-house"></i> หน้าหลัก
            </a>
        </div>
        
        <!-- Main Layout with Sidebar -->
        <div class="news-layout">
            
            <!-- Main Content Area -->
            <div class="main-content-area">
                
                <!-- News Article -->
                <article class="news-article" id="newsArticle">
                    <!-- Content will be populated by jQuery -->
                </article>
                
            </div>
            
            <!-- Sidebar -->
            <aside class="news-sidebar">
                
                <!-- Related News Section -->
                <section class="related-news-sidebar">
                    <h2 class="sidebar-title">ข่าวที่เกี่ยวข้อง</h2>
                    <div class="related-list" id="relatedList">
                        <div class="loading-placeholder">
                            <div class="loading-spinner">
                                <div class="spinner"></div>
                                <p>กำลังโหลด...</p>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Category Info -->
                <section class="category-info-sidebar" id="categoryInfo" style="display: none;">
                    <h3 class="sidebar-title">หมวดหมู่นี้</h3>
                    <div class="category-meta" id="categoryMeta">
                        <!-- Will be populated by jQuery -->
                    </div>
                </section>
                
            </aside>
            
        </div>
        
    </div>
    
</div>

<!-- jQuery CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<script>
/**
 * News Detail Page JavaScript (jQuery Version)
 * การจัดการหน้ารายละเอียดข่าว - ใช้ jQuery
 */

class NewsDetailManager {
    constructor() {
        this.newsId = <?php echo $newsId; ?>;
        this.currentUrl = '<?php echo $currentUrl; ?>';
        this.newsData = null;
        this.categoryStats = {};
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadNewsDetail();
    }
    
    bindEvents() {
        // Back button
        $('#backBtn').on('click', () => {
            this.goBack();
        });
        
        // Retry button
        $('#retryBtn').on('click', () => {
            this.loadNewsDetail();
        });
        
        // Related news click
        $(document).on('click', '.related-item', function() {
            const url = $(this).data('url');
            if (url) {
                window.location.href = url;
            }
        });
        
        // Share buttons
        $(document).on('click', '.share-btn', function() {
            const type = $(this).data('type');
            newsDetailManager.handleShare(type);
        });
    }
    
    async loadNewsDetail() {
        try {
            this.showLoading();
            
            // ===== ใช้ jQuery AJAX =====
            const data = await $.ajax({
                url: '../api/get_news_complete.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ id: this.newsId }),
                dataType: 'json'
            });

            if (data.success) {
                this.newsData = data.news;
                this.categoryStats = data.category_stats;
                
                // Update page metadata
                this.updatePageMetadata(data.news);
                
                // Display content
                this.displayNewsContent(data.news);
                this.displayRelatedNews(data.related_news);
                this.displayNavigation(data.navigation);
                this.displayCategoryInfo(data.category_info);
                
                // Show content
                this.showContent();
                
                // Send category stats to footer
                this.updateFooterStats(data.category_stats);
                
                // Setup additional features
                this.setupSharingMetadata(data.news);
                
            } else {
                this.showError(data.message || 'ไม่พบข่าวที่คุณต้องการ');
            }

        } catch (error) {
            console.error('Load news detail error:', error);
            this.showError('เกิดข้อผิดพลาดในการโหลดข้อมูล กรุณาลองใหม่อีกครั้ง');
        }
    }
    
    updatePageMetadata(news) {
        // Update document title
        document.title = `${news.title} - ARM CMS`;
        
        // Update meta description
        const $metaDesc = $('meta[name="description"]');
        if ($metaDesc.length) {
            const excerpt = this.createExcerpt(news.content, 160);
            $metaDesc.attr('content', excerpt);
        }
        
        // Update Open Graph tags
        this.updateOGTags(news);
        
        // Update breadcrumb if possible
        this.updateBreadcrumb(news);
    }
    
    updateOGTags(news) {
        const $ogTitle = $('meta[property="og:title"]');
        const $ogDesc = $('meta[property="og:description"]');
        const $ogImage = $('meta[property="og:image"]');
        
        if ($ogTitle.length) $ogTitle.attr('content', news.title);
        if ($ogDesc.length) {
            const excerpt = this.createExcerpt(news.content, 160);
            $ogDesc.attr('content', excerpt);
        }
        if ($ogImage.length && news.image_url) {
            $ogImage.attr('content', news.image_url);
        }
    }
    
    updateBreadcrumb(news) {
        const $breadcrumbContainer = $('.breadcrumb');
        if ($breadcrumbContainer.length) {
            const $breadcrumbList = $breadcrumbContainer.find('.breadcrumb-list');
            if ($breadcrumbList.length) {
                $breadcrumbList.html(`
                    <li class="breadcrumb-item">
                        <a href="index.php">หน้าหลัก</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="index.php?category=${encodeURIComponent(news.category)}">${this.escapeHtml(news.category)}</a>
                    </li>
                    <li class="breadcrumb-item active">
                        <span aria-current="page">${this.escapeHtml(news.title)}</span>
                    </li>
                `);
            }
        }
    }
    
    displayNewsContent(news) {
        const $articleContainer = $('#newsArticle');
        
        const categoryIcon = this.getCategoryIcon(news.category);
        const imageHtml = news.image_url ? `
            <div class="article-image">
                <img src="${this.escapeHtml(news.image_url)}" 
                     alt="${this.escapeHtml(news.title)}"
                     class="featured-image"
                     onerror="$(this).hide(); $(this).next().show();">
                <div class="image-placeholder" style="display:none;">
                    <span class="placeholder-icon">🖼️</span>
                    <span class="placeholder-text">ไม่สามารถแสดงภาพได้</span>
                </div>
            </div>
        ` : '';
        
        const updatedInfo = news.updated_at !== news.created_at ? `
            
        ` : '';
        
        $articleContainer.html(`
            <!-- Article Header -->
            <header class="article-header">
                <!-- Category Badge -->
                <div class="article-category">
                    ${categoryIcon} ${this.escapeHtml(news.category)}
                </div>
                
                <!-- Article Title -->
                <h1 class="article-title">${this.escapeHtml(news.title)}</h1>
                
                <!-- Article Meta -->
                <div class="article-meta">
                    <div class="meta-item">
                        <span class="meta-icon"><i class="bi bi-calendar4"></i></span>
                        <span class="meta-text">${this.escapeHtml(news.formatted_date)}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-icon"><i class="bi bi-clock"></i></span>
                        <span class="meta-text" id="readingTime">${this.escapeHtml(news.reading_time)}</span>
                    </div>
                    ${updatedInfo}
                </div>
            </header>
            
            <!-- Featured Image -->
            ${imageHtml}
            
            <!-- Article Content -->
            <div class="article-content">
                ${this.formatContent(news.content)}
            </div>
            
            <!-- Article Footer -->
            <footer class="article-footer">
                <!-- Social Share -->
                <div class="article-actions">
                    <h3>แชร์ข่าวนี้:</h3>
                    <div class="share-buttons">
                        <button type="button" class="share-btn facebook" data-type="facebook" title="แชร์ไป Facebook">
                            📘 Facebook
                        </button>
                        <button type="button" class="share-btn twitter" data-type="twitter" title="แชร์ไป Twitter">
                            🐦 Twitter
                        </button>
                        <button type="button" class="share-btn line" data-type="line" title="แชร์ไป LINE">
                            💚 LINE
                        </button>
                        <button type="button" class="share-btn whatsapp" data-type="whatsapp" title="แชร์ไป WhatsApp">
                            💬 WhatsApp
                        </button>
                        <button type="button" class="share-btn telegram" data-type="telegram" title="แชร์ไป Telegram">
                            ✈️ Telegram
                        </button>
                        <button type="button" class="share-btn copy" data-type="copy" title="คัดลอกลิงก์">
                            🔗 คัดลอกลิงก์
                        </button>
                        <button type="button" class="share-btn print" data-type="print" title="พิมพ์">
                            🖨️ พิมพ์
                        </button>
                    </div>
                </div>
                
                <!-- Article Navigation -->
                <div class="article-navigation" id="articleNavigation">
                    <!-- Will be populated by displayNavigation() -->
                </div>
            </footer>
        `);
    }
    
    displayRelatedNews(relatedNews) {
        const $list = $('#relatedList');
        
        if (!relatedNews || relatedNews.length === 0) {
            $list.html('<p class="no-related">ไม่มีข่าวที่เกี่ยวข้อง</p>');
            return;
        }
        
        let html = '';
        relatedNews.forEach(item => {
            html += this.generateRelatedItem(item);
        });
        
        $list.html(html);
    }
    
    generateRelatedItem(item) {
        const imageHtml = item.image_url 
            ? `<img src="${this.escapeHtml(item.image_url)}" alt="${this.escapeHtml(item.title)}" class="related-image" loading="lazy" onerror="$(this).hide(); $(this).next().show();">
               <div class="related-no-image" style="display:none;"><i class="bi bi-layout-text-sidebar-reverse"></i></div>`
            : `<div class="related-no-image"><i class="bi bi-layout-text-sidebar-reverse"></i></div>`;
        
        const categoryIcon = this.getCategoryIcon(item.category);
        
        return `
            <article class="related-item" data-url="${item.url}">
                <div class="related-image-wrapper">
                    ${imageHtml}
                </div>
                <div class="related-content">
                    <div class="related-category">
                        ${categoryIcon} ${this.escapeHtml(item.category)}
                    </div>
                    <h3 class="related-title">${this.escapeHtml(item.title)}</h3>
                    <div class="related-date">
                        <i class="bi bi-calendar4"></i> ${this.escapeHtml(item.formatted_date)}
                    </div>
                </div>
            </article>
        `;
    }
    
    displayNavigation(navigation) {
        const $navContainer = $('#articleNavigation');
        
        if (!navigation.prev && !navigation.next) {
            $navContainer.hide();
            return;
        }
        
        let html = '<div class="nav-buttons">';
        
        if (navigation.prev) {
            html += `
                <a href="${navigation.prev.url}" class="nav-btn nav-prev">
                    <span class="nav-direction">← ข่าวก่อนหน้า</span>
                    <span class="nav-title">${this.escapeHtml(navigation.prev.title)}</span>
                </a>
            `;
        }
        
        if (navigation.next) {
            html += `
                <a href="${navigation.next.url}" class="nav-btn nav-next">
                    <span class="nav-direction">ข่าวถัดไป →</span>
                    <span class="nav-title">${this.escapeHtml(navigation.next.title)}</span>
                </a>
            `;
        }
        
        html += '</div>';
        $navContainer.html(html);
    }
    
    displayCategoryInfo(categoryInfo) {
        const $container = $('#categoryInfo');
        const $metaContainer = $('#categoryMeta');
        
        if (!categoryInfo) {
            $container.hide();
            return;
        }
        
        $metaContainer.html(`
            <div class="category-badge">
                ${categoryInfo.icon} ${this.escapeHtml(categoryInfo.name)}
            </div>
            <p class="category-count">ทั้งหมด ${categoryInfo.total_count} ข่าว</p>
            <a href="index.php?category=${encodeURIComponent(categoryInfo.name)}" class="view-category-btn">
                ดูข่าวในหมวดหมู่นี้ →
            </a>
        `);
        
        $container.show();
    }
    
    updateFooterStats(categoryStats) {
        // ส่ง event ไปยัง footer ด้วย jQuery
        $(document).trigger('categoryStatsLoaded', [{ stats: categoryStats }]);
    }
    
    setupSharingMetadata(news) {
        this.newsTitle = news.title;
        this.currentUrl = window.location.href;
    }
    
    handleShare(type) {
        const url = encodeURIComponent(this.currentUrl);
        const text = encodeURIComponent(this.newsTitle);
        
        switch(type) {
            case 'facebook':
                window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank', 'width=600,height=400');
                break;
                
            case 'twitter':
                window.open(`https://twitter.com/intent/tweet?url=${url}&text=${text}`, '_blank', 'width=600,height=400');
                break;
                
            case 'line':
                window.open(`https://social-plugins.line.me/lineit/share?url=${url}&text=${text}`, '_blank', 'width=600,height=400');
                break;
                
            case 'whatsapp':
                const whatsappText = encodeURIComponent(`${this.newsTitle} - ${this.currentUrl}`);
                const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
                
                if (isMobile) {
                    window.open(`whatsapp://send?text=${whatsappText}`, '_blank');
                } else {
                    window.open(`https://web.whatsapp.com/send?text=${whatsappText}`, '_blank', 'width=600,height=400');
                }
                break;
                
            case 'telegram':
                window.open(`https://t.me/share/url?url=${url}&text=${text}`, '_blank', 'width=600,height=400');
                break;
                
            case 'copy':
                this.copyToClipboard();
                break;
                
            case 'print':
                window.print();
                break;
        }
    }
    
    copyToClipboard() {
        const url = this.currentUrl;
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(() => {
                this.showShareNotification('คัดลอกลิงก์เรียบร้อยแล้ว', 'success');
            }).catch(() => {
                this.fallbackCopyToClipboard(url);
            });
        } else {
            this.fallbackCopyToClipboard(url);
        }
    }
    
    fallbackCopyToClipboard(text) {
        const $textArea = $('<textarea>').val(text).css({
            position: 'fixed',
            top: '0',
            left: '0'
        });
        
        $('body').append($textArea);
        $textArea.focus().select();
        
        try {
            document.execCommand('copy');
            this.showShareNotification('คัดลอกลิงก์เรียบร้อยแล้ว', 'success');
        } catch (err) {
            this.showShareNotification('ไม่สามารถคัดลอกลิงก์ได้', 'error');
        }
        
        $textArea.remove();
    }
    
    showShareNotification(message, type = 'success') {
        // Remove existing notification
        $('.share-notification').remove();
        
        const $notification = $('<div>').addClass(`share-notification ${type}`).text(message);
        
        $notification.css({
            position: 'fixed',
            top: '20px',
            right: '20px',
            background: type === 'success' ? '#28a745' : '#dc3545',
            color: 'white',
            padding: '12px 20px',
            borderRadius: '8px',
            zIndex: 10000,
            fontWeight: '500',
            boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
            transform: 'translateX(100%)',
            transition: 'transform 0.3s ease'
        });
        
        $('body').append($notification);
        
        setTimeout(() => {
            $notification.css('transform', 'translateX(0)');
        }, 100);
        
        setTimeout(() => {
            $notification.css('transform', 'translateX(100%)');
            setTimeout(() => {
                $notification.remove();
            }, 300);
        }, 3000);
    }
    
    goBack() {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = 'index.php';
        }
    }
    
    showLoading() {
        $('#loadingState').show();
        $('#errorState').hide();
        $('#newsContent').hide();
    }
    
    showContent() {
        $('#loadingState').hide();
        $('#errorState').hide();
        $('#newsContent').show();
    }
    
    showError(message) {
        $('#loadingState').hide();
        $('#newsContent').hide();
        $('#errorState').show();
        $('#errorMessage').text(message);
    }
    
    formatContent(content) {
        // Format content with line breaks
        return this.escapeHtml(content).replace(/\n/g, '<br>');
    }
    
    createExcerpt(content, maxLength = 160) {
        if (!content) return '';
        const text = content.replace(/\n/g, ' ').trim();
        return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
    }
    
    getCategoryIcon(category) {
        const icons = {
            'ทั่วไป': '<i class="bi bi-layout-text-sidebar-reverse"></i>',
            'ประกาศ': '<i class="bi bi-megaphone"></i>',
            'กิจกรรม': '<i class="bi bi-magic"></i>'
        };
        return icons[category] || '<i class="bi bi-layout-text-sidebar-reverse"></i>';
    }
    
    escapeHtml(text) {
        if (!text) return '';
        return $('<div>').text(text).html();
    }
}

// Initialize with jQuery
let newsDetailManager;

$(document).ready(function() {
    newsDetailManager = new NewsDetailManager();
    
    // Handle print styles
    $(window).on('beforeprint', function() {
        $('body').addClass('printing');
    });
    
    $(window).on('afterprint', function() {
        $('body').removeClass('printing');
    });
});
</script>

<style>
/* Additional CSS for loading and error states */
.loading-state, .error-state {
    text-align: center;
    padding: 60px 20px;
}

.loading-spinner {
    display: inline-block;
}

.spinner {
    width: 40px;
    height: 40px;
    margin: 0 auto 20px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid rgb(224, 6, 42);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.error-content {
    max-width: 400px;
    margin: 0 auto;
}

.error-icon {
    font-size: 3rem;
    margin-bottom: 20px;
}

.error-actions {
    margin-top: 30px;
}

.error-actions .btn {
    margin: 0 10px;
}

.no-related {
    text-align: center;
    color: #6c757d;
    font-style: italic;
    padding: 20px;
}

.related-item {
    cursor: pointer;
    transition: transform 0.2s;
}

.related-item:hover {
    transform: translateY(-2px);
}

.share-btn {
    cursor: pointer;
    transition: all 0.2s;
}

.share-btn:hover {
    transform: translateY(-1px);
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?>