<?php
/**
 * Public News List Page (jQuery Version)
 * ARM CMS - Content Management System
 * 
 * หน้าแสดงรายการข่าวสำหรับผู้ใช้ทั่วไป - ใช้ jQuery
 */

// Start session
session_start();

// if (empty($_SESSION["IsLogin"]) && $_SESSION["IsLogin"] == false){
//     header('Location: 401.php');
// }

// Include database connection
require_once '../config/database.php';

// Test database connection
if (!testConnection()) {
    die('<div style="text-align:center;padding:50px;color:#dc3545;">
         <h2>ไม่สามารถเชื่อมต่อฐานข้อมูลได้</h2>
         <p>กรุณาลองใหม่อีกครั้งในภายหลัง</p>
         </div>');
}

// Set default page metadata (จะถูกอัปเดตโดย jQuery)
$pageTitle = 'ข่าวประชาสัมพันธ์';
$pageDescription = 'ข่าวประชาสัมพันธ์และข้อมูลข่าวสารต่างๆ จากระบบ ARM CMS';

// Set default breadcrumb
$breadcrumb = [
    ['title' => 'หน้าหลัก', 'url' => 'index.php']
];

// Include header
include 'includes/header.php';
?>

<!-- News List Container -->
<div class="container">

    <!-- Page Header -->
    <div class="public-page-header">
        <h1 class="page-title">
            <i class="bi bi-layout-text-sidebar-reverse"></i> ข่าวประชาสัมพันธ์
        </h1>
        <p class="page-subtitle">ข้อมูลข่าวสารและประกาศต่างๆ</p>
    </div>

    <!-- Filters Section -->
    <div class="news-filters">
        <div class="filters-wrapper">

            <!-- Category Filter -->
            <div class="filter-group">
                <label for="categorySelect" class="filter-label">หมวดหมู่:</label>
                <select id="categorySelect" class="filter-select">
                    <option value="">ทั้งหมด</option>
                    <option value="ทั่วไป"><i class="bi bi-layout-text-sidebar-reverse"></i> ข่าวทั่วไป</option>
                    <option value="ประกาศ"><i class="bi bi-megaphone"></i> ประกาศ</option>
                    <option value="กิจกรรม"><i class="bi bi-magic"></i> กิจกรรม</option>
                </select>
            </div>

            <!-- Search -->
            <div class="filter-group">
                <label for="searchInput" class="filter-label">ค้นหา:</label>
                <div class="search-wrapper">
                    <input type="text" id="searchInput" class="search-input" placeholder="ค้นหาข่าว..."
                        autocomplete="off">
                    <button type="button" class="search-btn" id="searchBtn">
                        ค้นหา
                    </button>
                </div>
            </div>

            <!-- Clear Filters -->
            <div class="filter-actions">
                <button type="button" class="btn btn-secondary" id="clearFiltersBtn">
                    ล้างตัวกรอง
                </button>
            </div>

        </div>
    </div>

    <!-- Results Info -->
    <div id="resultsInfo" class="results-info" style="display: none;">
        <span id="resultsText"></span>
    </div>

    <!-- News Grid -->
    <div class="news-grid" id="newsGrid">
        <!-- News cards will be loaded here -->
        <div class="loading-placeholder">
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>กำลังโหลดข่าว...</p>
            </div>
        </div>
    </div>

    <!-- Load More Section -->
    <div class="load-more-section" id="loadMoreSection" style="display: none;">
        <button type="button" class="btn btn-primary load-more-btn" id="loadMoreBtn">
            <i class="bi bi-layout-text-sidebar-reverse"></i> โหลดข่าวเพิ่มเติม
        </button>
        <div class="load-more-info">
            <span id="loadMoreText"></span>
        </div>
    </div>

    <!-- No Results -->
    <div id="noResults" class="no-results" style="display: none;">
        <div class="no-results-content">
            <div class="no-results-icon">📭</div>
            <h3>ไม่พบข่าวที่ตรงกับเงื่อนไข</h3>
            <p>ลองเปลี่ยนเงื่อนไขการค้นหาหรือเลือกหมวดหมู่อื่น</p>
            <button type="button" class="btn btn-primary" id="showAllNewsBtn">
                ดูข่าวทั้งหมด
            </button>
        </div>
    </div>

</div>

<!-- jQuery CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<script>
    /**
     * Public News List JavaScript (jQuery Version)
     * การจัดการหน้ารายการข่าวสำหรับผู้ใช้ทั่วไป - ใช้ jQuery
     */

    class PublicNewsManager {
        constructor() {
            this.currentOffset = 0;
            this.itemsPerPage = 12;
            this.hasMore = false;
            this.isLoading = false;
            this.categoryStats = {};
            this.init();
        }

        init() {
            this.loadFiltersFromURL();
            this.bindEvents();
            this.loadNews(true); // Initial load
        }

        loadFiltersFromURL() {
            const urlParams = new URLSearchParams(window.location.search);
            const category = urlParams.get('category');
            const search = urlParams.get('search');
            
            if (category) {
                $('#categorySelect').val(category);
            }
            if (search) {
                $('#searchInput').val(search);
            }
            
            // Update page header based on URL parameters
            this.updatePageHeader(category, search);
        }

        bindEvents() {
            // Category filter change
            $('#categorySelect').on('change', () => {
                this.resetAndLoad();
            });

            // Search on Enter key
            $('#searchInput').on('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.resetAndLoad();
                }
            });

            // Search button click
            $('#searchBtn').on('click', () => {
                this.resetAndLoad();
            });

            // Clear filters button
            $('#clearFiltersBtn').on('click', () => {
                this.clearFilters();
            });

            // Load more button
            $('#loadMoreBtn').on('click', () => {
                this.loadNews(false);
            });

            // Show all news button
            $('#showAllNewsBtn').on('click', () => {
                this.clearFilters();
            });

            // Click on news card
            $(document).on('click', '.news-card', function() {
                const url = $(this).data('url');
                if (url) {
                    window.location.href = url;
                }
            });
        }

        getFilters() {
            return {
                category: $('#categorySelect').val() || '',
                search: $('#searchInput').val().trim() || '',
                offset: this.currentOffset,
                limit: this.itemsPerPage
            };
        }

        resetAndLoad() {
            this.currentOffset = 0;
            this.loadNews(true);
            
            // Update URL and header
            const filters = this.getFilters();
            this.updateURL(filters.category, filters.search);
            this.updatePageHeader(filters.category, filters.search);
        }

        updateURL(category, search) {
            const url = new URL(window.location);
            
            if (category) {
                url.searchParams.set('category', category);
            } else {
                url.searchParams.delete('category');
            }
            
            if (search) {
                url.searchParams.set('search', search);
            } else {
                url.searchParams.delete('search');
            }
            
            window.history.pushState({}, '', url);
        }

        updatePageHeader(category, search) {
            const $headerTitle = $('.page-title');
            
            let newTitle = '<i class="bi bi-layout-text-sidebar-reverse"></i> ข่าวประชาสัมพันธ์';
            
            if (category) {
                const categoryIcons = {
                    'ทั่วไป': '<i class="bi bi-layout-text-sidebar-reverse"></i>',
                    'ประกาศ': '<i class="bi bi-megaphone"></i>',
                    'กิจกรรม': '<i class="bi bi-magic"></i>'
                };
                newTitle = `${categoryIcons[category] || '<i class="bi bi-layout-text-sidebar-reverse"></i>'} ข่าว${this.escapeHtml(category)}`;
            } else if (search) {
                newTitle = `🔍 ผลการค้นหา: "${this.escapeHtml(search)}"`;
            }
            
            $headerTitle.html(newTitle);
            
            // Update document title
            document.title = `${category ? 'ข่าว' + category : search ? 'ค้นหา: ' + search : 'ข่าวประชาสัมพันธ์'} - ARM CMS`;
        }

        async loadNews(reset = false) {
            if (this.isLoading) return;

            try {
                this.isLoading = true;

                if (reset) {
                    this.showLoading();
                } else {
                    this.showLoadMoreLoading();
                }

                const filters = this.getFilters();

                // ===== ใช้ jQuery AJAX =====
                const data = await $.ajax({
                    url: '../api/get_public_data.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(filters),
                    dataType: 'json'
                });

                if (data.success) {
                    // เก็บ category stats
                    this.categoryStats = data.category_stats;
                    
                    if (reset) {
                        this.displayNews(data.news);
                    } else {
                        this.appendNews(data.news);
                    }

                    this.updatePagination(data.pagination);
                    this.updateResultsInfo(data.pagination, filters);
                    
                    // ส่ง category stats ไปยัง footer
                    this.updateFooterStats(data.category_stats);

                } else {
                    this.showError(data.message || 'เกิดข้อผิดพลาดในการโหลดข่าว');
                }

            } catch (error) {
                console.error('Load news error:', error);
                this.showError('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
            } finally {
                this.isLoading = false;
            }
        }

        updateFooterStats(categoryStats) {
            // ส่ง event ไปยัง footer ด้วย jQuery
            $(document).trigger('categoryStatsLoaded', [{ stats: categoryStats }]);
        }

        showLoading() {
            $('#newsGrid').html(`
                <div class="loading-placeholder">
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                        <p>กำลังโหลดข่าว...</p>
                    </div>
                </div>
            `);

            $('#loadMoreSection').hide();
            $('#noResults').hide();
            $('#resultsInfo').hide();
        }

        showLoadMoreLoading() {
            $('#loadMoreBtn').prop('disabled', true).html('⏳ กำลังโหลด...');
        }

        displayNews(news) {
            const $grid = $('#newsGrid');

            if (!news || news.length === 0) {
                this.showNoResults();
                return;
            }

            let html = '';
            news.forEach(item => {
                html += this.generateNewsCard(item);
            });

            $grid.html(html);
            $('#noResults').hide();
        }

        appendNews(news) {
            const $grid = $('#newsGrid');

            if (!news || news.length === 0) {
                return;
            }

            let html = '';
            news.forEach(item => {
                html += this.generateNewsCard(item);
            });

            $grid.append(html);
        }

        generateNewsCard(item) {
            const imageHtml = item.image_url
                ? `<img src="${this.escapeHtml(item.image_url)}" alt="${this.escapeHtml(item.title)}" class="card-image" loading="lazy" onerror="$(this).hide(); $(this).next().show();">
                   <div class="card-no-image" style="display:none;"><i class="bi bi-layout-text-sidebar-reverse"></i></div>`
                : `<div class="card-no-image"><i class="bi bi-layout-text-sidebar-reverse"></i></div>`;

            const categoryIcon = this.getCategoryIcon(item.category);

            return `
                <article class="news-card" data-url="${item.url}">
                    <div class="card-image-wrapper">
                        ${imageHtml}
                        <div class="card-category">
                            ${categoryIcon} ${this.escapeHtml(item.category)}
                        </div>
                    </div>
                    <div class="card-content">
                        <h2 class="card-title">${this.escapeHtml(item.title)}</h2>
                        <p class="card-excerpt">${this.escapeHtml(item.excerpt)}</p>
                        <div class="card-meta">
                            <span class="card-date"><i class="bi bi-calendar4"></i> ${this.escapeHtml(item.formatted_date)}</span>
                            <span class="card-read-more">อ่านต่อ →</span>
                        </div>
                    </div>
                </article>
            `;
        }

        updatePagination(pagination) {
            this.currentOffset = pagination.next_offset || 0;
            this.hasMore = pagination.has_more;

            const $loadMoreSection = $('#loadMoreSection');
            const $loadMoreBtn = $('#loadMoreBtn');
            const $loadMoreText = $('#loadMoreText');

            if (this.hasMore) {
                $loadMoreSection.show();
                $loadMoreBtn.prop('disabled', false).html('<i class="bi bi-layout-text-sidebar-reverse"></i> โหลดข่าวเพิ่มเติม');
                $loadMoreText.text(`แสดง ${pagination.showing_to} จาก ${pagination.total_items} ข่าว`);
            } else {
                $loadMoreSection.hide();
            }
        }

        updateResultsInfo(pagination, filters) {
            const $resultsInfo = $('#resultsInfo');
            const $resultsText = $('#resultsText');

            let text = `พบ ${pagination.total_items} ข่าว`;

            if (filters.category) {
                text += ` ในหมวดหมู่ "${filters.category}"`;
            }

            if (filters.search) {
                text += ` ที่มีคำว่า "${filters.search}"`;
            }

            $resultsText.text(text);
            $resultsInfo.show();
        }

        showNoResults() {
            $('#newsGrid').empty();
            $('#loadMoreSection').hide();
            $('#noResults').show();
            $('#resultsInfo').hide();
        }

        showError(message) {
            $('#newsGrid').html(`
                <div class="error-placeholder">
                    <div class="error-content">
                        <div class="error-icon">⚠️</div>
                        <h3>เกิดข้อผิดพลาด</h3>
                        <p>${this.escapeHtml(message)}</p>
                        <button type="button" class="btn btn-primary" id="retryBtn">
                            ลองใหม่อีกครั้ง
                        </button>
                    </div>
                </div>
            `);

            // Bind retry button
            $(document).on('click', '#retryBtn', () => {
                this.resetAndLoad();
            });

            $('#loadMoreSection').hide();
            $('#noResults').hide();
            $('#resultsInfo').hide();
        }

        clearFilters() {
            $('#categorySelect').val('');
            $('#searchInput').val('');

            // Update URL
            const url = new URL(window.location);
            url.searchParams.delete('category');
            url.searchParams.delete('search');
            window.history.pushState({}, '', url);

            this.resetAndLoad();
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
    let newsManager;

    $(document).ready(function() {
        newsManager = new PublicNewsManager();
    });

    // Handle browser back/forward
    $(window).on('popstate', function() {
        if (newsManager) {
            newsManager.loadFiltersFromURL();
            newsManager.resetAndLoad();
        }
    });
</script>

<style>
/* Additional CSS for loading and error states */
.loading-placeholder, .error-placeholder {
    text-align: center;
    padding: 60px 20px;
    grid-column: 1 / -1;
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

.no-results {
    text-align: center;
    padding: 60px 20px;
}

.no-results-content {
    max-width: 400px;
    margin: 0 auto;
}

.no-results-icon {
    font-size: 4rem;
    margin-bottom: 20px;
}

.news-card {
    cursor: pointer;
    transition: transform 0.2s;
}

.news-card:hover {
    transform: translateY(-2px);
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?>