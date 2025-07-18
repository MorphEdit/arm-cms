/**
 * News Manager JavaScript (jQuery Version)
 * ARM CMS - Content Management System
 * 
 * Class สำหรับจัดการข่าวผ่าน jQuery รองรับการโหลด แสดงผล กรอง และลบข่าว
 */

class NewsManager {
    /**
     * Constructor
     * การทำงาน: เริ่มต้น NewsManager และเรียก init() และ bindEvents()
     */
    constructor() {
        this.init();
        this.bindEvents();
    }

    /**
     * Initialize the news manager
     * การทำงาน: เริ่มต้นการทำงานของ news manager โดยโหลดข้อมูลข่าว
     */
    init() {
        this.loadNews();
    }

    /**
     * Bind event listeners
     * การทำงาน: 
     * - ผูก event listener กับ filter elements
     * - ใช้ debounce สำหรับ search input เพื่อลดการเรียก API
     * - เมื่อมีการเปลี่ยนแปลงจะเรียก loadNews() ใหม่
     */
    bindEvents() {
        // Filter events
        $('#categoryFilter').on('change', () => this.loadNews());
        $('#statusFilter').on('change', () => this.loadNews());
        // $('#searchInput').on('input', this.debounce(() => this.loadNews(), 500));
        $('#searchInput').on('keypress', (e) => {
            if (e.key === 'Enter') {
                this.loadNews();
            }
        });
    }

    /**
     * Load news data from server
     * การทำงาน:
     * - รับค่า filter จาก UI
     * - แสดง loading state
     * - ส่ง POST request ไปยัง load_news.php API
     * - ประมวลผล response และแสดงข้อมูลหรือ error
     */
    async loadNews() {
        try {
            const filters = this.getFilters();
            this.showLoading();

            const response = await $.ajax({
                url: 'api/load_news.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(filters),
                dataType: 'json'
            });

            if (response.success) {
                this.displayNews(response.news);
            } else {
                this.showError(response.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูล');
            }

        } catch (error) {
            console.error('Load news error:', error);

            // Check for 401 Unauthorized
            if (error.status === 401 || (error.responseJSON && error.responseJSON.error_code === 'UNAUTHORIZED')) {
                alert('เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่');
                window.location.href = 'public/login.php?session_expired=1';
                return;
            }

            this.showError('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
        }
    }

    /**
     * Get current filter values
     * การทำงาน:
     * - ดึงค่าจาก filter elements (category, status, search)
     * - คืนค่าเป็น object สำหรับส่งไปยัง API
     */
    getFilters() {
        return {
            category: $('#categoryFilter').val() || '',
            status: $('#statusFilter').val() || '',
            search: $('#searchInput').val() || ''
        };
    }

    /**
     * Show loading state
     * การทำงาน: แสดงข้อความ "กำลังโหลดข้อมูล..." ในตาราง
     */
    showLoading() {
        $('#newsTable').html('<tr><td colspan="8" class="loading">กำลังโหลดข้อมูล...</td></tr>');
    }

    /**
     * Show error message
     * การทำงาน: แสดงข้อความ error ในตาราง
     * @param {string} message - ข้อความ error ที่ต้องการแสดง
     */
    showError(message) {
        $('#newsTable').html(`<tr><td colspan="8" class="no-data">เกิดข้อผิดพลาด: ${this.escapeHtml(message)}</td></tr>`);
    }

    /**
     * Display news data in table
     * การทำงาน:
     * - รับข้อมูลข่าวและแสดงในตาราง
     * - หากไม่มีข้อมูลจะแสดงข้อความ "ไม่พบข้อมูลข่าว"
     * - เรียก generateNewsRow() สำหรับแต่ละข่าว
     * @param {Array} news - array ของข้อมูลข่าว
     */
    displayNews(news) {
        const $tbody = $('#newsTable');

        if ($tbody.length === 0) {
            console.error('News table not found');
            return;
        }

        if (!news || news.length === 0) {
            $tbody.html('<tr><td colspan="8" class="no-data">ไม่พบข้อมูลข่าว</td></tr>');
            return;
        }

        let html = '';
        news.forEach((item, index) => {
            html += this.generateNewsRow(item, index + 1);
        });

        $tbody.html(html);
    }

    /**
     * Generate HTML for news table row
     * การทำงาน:
     * - สร้าง HTML สำหรับแถวของตารางข่าว
     * - เรียกใช้ helper functions เพื่อสร้างส่วนต่างๆ
     * - escape HTML เพื่อป้องกัน XSS
     * @param {Object} item - ข้อมูลข่าว
     * @param {number} index - ลำดับของข่าว
     * @return {string} - HTML string ของแถว
     */
    generateNewsRow(item, index) {
        const imageHtml = this.generateImageHtml(item.image);
        const statusHtml = this.generateStatusHtml(item.status);
        const memberHtml = this.generateMemberHtml(item.member_access);
        const categoryHtml = this.generateCategoryHtml(item.category);
        const actionsHtml = this.generateActionsHtml(item.id);

        return `
            <tr>
                <td>${index}</td>
                <td>${imageHtml}</td>
                <td>
                    <div class="news-title">${this.escapeHtml(item.title)}</div>
                    <div class="news-preview">${this.truncateText(this.escapeHtml(item.content), 100)}</div>
                </td>
                <td>${categoryHtml}</td>
                <td>${statusHtml}</td>
                <td>${memberHtml}</td>
                <td>${this.formatDate(item.created_at)}</td>
                <td>${actionsHtml}</td>
            </tr>
        `;
    }

    /**
     * Generate image HTML
     * การทำงาน:
     * - สร้าง HTML สำหรับแสดงรูปภาพ
     * - หากมีรูปภาพจะแสดง img tag พร้อม fallback
     * - หากไม่มีรูปภาพจะแสดงข้อความ "ไม่มีภาพ"
     * @param {string} image - ชื่อไฟล์รูปภาพ
     * @return {string} - HTML string ของรูปภาพ
     */
    generateImageHtml(image) {
        if (image) {
            return `<img src="uploads/${this.escapeHtml(image)}" alt="ภาพประกอบ" class="thumbnail" onerror="$(this).hide(); $(this).next().show();">
                    <div class="no-image" style="display:none;">ไม่มีภาพ</div>`;
        }
        return '<div class="no-image">ไม่มีภาพ</div>';
    }

    /**
     * Generate status HTML
     * การทำงาน:
     * - สร้าง HTML สำหรับแสดงสถานะ
     * - เพิ่ม CSS class ตามสถานะ (active/inactive)
     * - แปลงสถานะเป็นภาษาไทย
     * @param {string} status - สถานะ (active/inactive)
     * @return {string} - HTML string ของสถานะ
     */
    generateStatusHtml(status) {
        const statusClass = status === 'active' ? 'active' : 'inactive';
        const statusText = status === 'active' ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
        return `<span class="status ${statusClass}">${statusText}</span>`;
    }

    generateMemberHtml(member) {
        const memberClass = member === 'public' ? 'public' : 'member';
        const memberText = member === 'public' ? 'ทั่วไป' : 'สมาชิก';
        return `<span class="member ${memberClass}">${memberText}</span>`;
    }

    /**
     * Generate category HTML
     * การทำงาน:
     * - สร้าง HTML สำหรับแสดงหมวดหมู่
     * - เพิ่ม CSS class ตามหมวดหมู่
     * @param {string} category - หมวดหมู่
     * @return {string} - HTML string ของหมวดหมู่
     */
    generateCategoryHtml(category) {
        return `<span class="category ${this.escapeHtml(category)}">${this.escapeHtml(category)}</span>`;
    }

    /**
     * Generate actions HTML
     * การทำงาน:
     * - สร้าง HTML สำหรับปุ่มจัดการ (แก้ไข, ลบ)
     * - เรียกใช้ functions ของ newsManager เมื่อคลิก
     * @param {number} id - ID ของข่าว
     * @return {string} - HTML string ของปุ่มจัดการ
     */
    generateActionsHtml(id) {
        return `
            <div class="actions">
                <button class="btn btn-warning" onclick="newsManager.editNews(${id})">แก้ไข</button>
                <button class="btn btn-danger" onclick="newsManager.confirmDelete(${id})">ลบ</button>
            </div>
        `;
    }

    /**
     * Edit news
     * การทำงาน:
     * - ตรวจสอบ ID ว่าถูกต้องหรือไม่
     * - redirect ไปยังหน้าแก้ไขข่าว (edit_news.php) พร้อม ID
     * @param {number} id - ID ของข่าวที่ต้องการแก้ไข
     */
    editNews(id) {
        if (!id || isNaN(id)) {
            alert('ID ข่าวไม่ถูกต้อง');
            return;
        }
        window.location.href = `pages/edit_news.php?id=${id}`;
    }

    /**
     * Confirm delete news
     * การทำงาน:
     * - ดึงข้อมูลข่าวเพื่อแสดงชื่อใน confirmation dialog
     * - แสดง confirmation dialog
     * - หากผู้ใช้ยืนยันจะเรียก deleteNews()
     * @param {number} id - ID ของข่าวที่ต้องการลบ
     */
    async confirmDelete(id) {
        try {
            // Get news title for confirmation
            const newsData = await this.getNewsById(id);
            const title = newsData ? newsData.title : 'รายการนี้';

            if (!confirm(`คุณต้องการลบข่าว "${title}" ใช่หรือไม่?\n\nการลบจะไม่สามารถกู้คืนได้`)) {
                return;
            }

            await this.deleteNews(id);

        } catch (error) {
            console.error('Confirm delete error:', error);
            if (!confirm(`คุณต้องการลบข่าวนี้ใช่หรือไม่?\n\nการลบจะไม่สามารถกู้คืนได้`)) {
                return;
            }
            await this.deleteNews(id);
        }
    }

    /**
     * Delete news
     * การทำงาน:
     * - ส่ง POST request ไปยัง delete_news.php API
     * - ประมวลผล response
     * - แสดงข้อความแจ้งผลลัพธ์
     * - โหลดข้อมูลใหม่หากลบสำเร็จ
     * @param {number} id - ID ของข่าวที่ต้องการลบ
     */
    async deleteNews(id) {
        try {
            const response = await $.ajax({
                url: 'api/delete_news.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ id: parseInt(id) }),
                dataType: 'json'
            });

            if (response.success) {
                this.showAlert('ลบข่าวสำเร็จ', 'success');
                this.loadNews(); // Reload data
            } else {
                this.showAlert(response.message || 'เกิดข้อผิดพลาดในการลบข่าว', 'error');
            }

        } catch (error) {
            console.error('Delete news error:', error);
            this.showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
        }
    }

    /**
     * Get news by ID (for confirmation dialog)
     * การทำงาน:
     * - ส่ง request ไปยัง get_news.php API เพื่อดึงข้อมูลข่าวตาม ID
     * - ใช้สำหรับแสดงชื่อข่าวใน confirmation dialog
     * - คืนค่าข้อมูลข่าวหากสำเร็จ หรือ null หากไม่สำเร็จ
     * @param {number} id - ID ของข่าว
     * @return {Object|null} - ข้อมูลข่าวหรือ null
     */
    async getNewsById(id) {
        try {
            const response = await $.ajax({
                url: 'api/get_news.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ id: parseInt(id) }),
                dataType: 'json'
            });

            return response.success ? response.news : null;

        } catch (error) {
            console.error('Get news by ID error:', error);
            return null;
        }
    }

    /**
     * Show alert message
     * การทำงาน:
     * - แสดงข้อความแจ้งเตือนให้ผู้ใช้
     * - ปัจจุบันใช้ alert() แบบง่าย สามารถพัฒนาเป็น modal ได้
     * @param {string} message - ข้อความที่ต้องการแสดง
     * @param {string} type - ประเภทของข้อความ (info, success, error, warning)
     */
    showAlert(message, type = 'info') {
        // Simple alert for now, can be enhanced with custom modal
        alert(message);
    }

    /**
     * Format date for display
     * การทำงาน:
     * - แปลง date string เป็นรูปแบบที่อ่านง่าย
     * - ใช้ toLocaleDateString() ด้วย locale 'th-TH'
     * - แสดงวันที่ เวลา ในรูปแบบไทย
     * @param {string} dateString - date string จากฐานข้อมูล
     * @return {string} - วันที่ที่จัดรูปแบบแล้ว
     */
    formatDate(dateString) {
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
    }

    /**
     * Truncate text with ellipsis
     * การทำงาน:
     * - ตัดข้อความให้สั้นลงตามจำนวนตัวอักษรที่กำหนด
     * - เพิ่ม "..." ท้ายข้อความหากข้อความยาวเกินที่กำหนด
     * - ใช้สำหรับแสดง preview ของเนื้อหาข่าว
     * @param {string} text - ข้อความต้นฉบับ
     * @param {number} maxLength - จำนวนตัวอักษรสูงสุด
     * @return {string} - ข้อความที่ตัดแล้ว
     */
    truncateText(text, maxLength) {
        if (!text) return '';
        return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
    }

    /**
     * Escape HTML to prevent XSS
     * การทำงาน:
     * - แปลงตัวอักษรพิเศษใน HTML ให้เป็น HTML entities
     * - ป้องกัน XSS attacks โดยใช้ text() แทน html()
     * - ใช้กับข้อมูลที่มาจากผู้ใช้ก่อนแสดงใน HTML
     * @param {string} text - ข้อความที่ต้องการ escape
     * @return {string} - ข้อความที่ escape แล้ว
     */
    escapeHtml(text) {
        if (!text) return '';
        return $('<div>').text(text).html();
    }

    /**
     * Debounce function for search input
     * การทำงาน:
     * - สร้าง debounced version ของ function
     * - หน่วงเวลาการเรียก function จนกว่าจะหยุดเรียกนานพอ
     * - ใช้กับ search input เพื่อลดการเรียก API บ่อยเกินไป
     * @param {Function} func - function ที่ต้องการ debounce
     * @param {number} wait - เวลาหน่วง (milliseconds)
     * @return {Function} - debounced function
     */
    // debounce(func, wait) {
    //     let timeout;
    //     return function executedFunction(...args) {
    //         const later = () => {
    //             clearTimeout(timeout);
    //             func(...args);
    //         };
    //         clearTimeout(timeout);
    //         timeout = setTimeout(later, wait);
    //     };
    // }

    /**
     * Refresh news data
     * การทำงาน:
     * - โหลดข้อมูลข่าวใหม่
     * - เรียกจากปุ่ม refresh หรือเมื่อต้องการอัปเดตข้อมูล
     */
    refresh() {
        this.loadNews();
    }

    /**
     * Clear all filters
     * การทำงาน:
     * - ล้างค่าในทุก filter (category, status, search)
     * - โหลดข้อมูลข่าวใหม่แบบไม่มีการกรอง
     * - เรียกจากปุ่ม "ล้างตัวกรอง"
     */
    clearFilters() {
        $('#categoryFilter').val('');
        $('#statusFilter').val('');
        $('#searchInput').val('');
        this.loadNews();
    }
}

// Initialize when DOM is loaded
let newsManager;

$(document).ready(function () {
    newsManager = new NewsManager();
});

// Export for global access
window.newsManager = newsManager;