<?php
/**
 * Add News Page
 * ARM CMS - Content Management System
 * 
 * หน้าสำหรับเพิ่มข่าวใหม่ ใช้ jQuery AJAX สำหรับการส่งข้อมูล
 */

// Start session
session_start();

// Include authentication
require_once '../config/auth.php';

// Require login to access this page
requireLogin();

// Include database connection
require_once '../config/database.php';

// Test database connection
// การทำงาน: ตรวจสอบการเชื่อมต่อฐานข้อมูลก่อนแสดงฟอร์ม
if (!testConnection()) {
    die('<div style="text-align:center;padding:50px;color:#dc3545;">
         <h2>ไม่สามารถเชื่อมต่อฐานข้อมูลได้</h2>
         <p>กรุณาตรวจสอบการตั้งค่าฐานข้อมูลในไฟล์ config/database.php</p>
         </div>');
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มข่าวใหม่ - ARM CMS</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    
    <!-- jQuery CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border: 1px solid #dee2e6;
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #495057;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: none;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .required {
            color: #dc3545;
        }
        
        .file-info {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .preview-container {
            margin-top: 10px;
        }
        
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }

        .breadcrumb {
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .breadcrumb a {
            color: rgb(224, 6, 42);
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
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
            border-top: 4px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .success-actions {
            margin-top: 15px;
        }

        .success-actions a {
            color: #155724;
            text-decoration: none;
            margin-right: 15px;
        }

        .success-actions a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Loading Overlay -->
        <div id="loadingOverlay" class="loading-overlay">
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>กำลังบันทึกข้อมูล...</p>
            </div>
        </div>

        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="../index.php">หน้าหลัก</a> > เพิ่มข่าวใหม่
        </div>

        <!-- Header -->
        <header class="header">
            <h1>เพิ่มข่าวใหม่</h1>
            <p>ARM CMS - Content Management System</p>
        </header>

        <!-- Alert Messages -->
        <div id="alertSuccess" class="alert alert-success">
            <strong>สำเร็จ!</strong> <span id="successMessage"></span>
            <div class="success-actions">
                <a href="../index.php">กลับไปหน้าหลัก</a>
                <a href="#" onclick="resetForm(); return false;">เพิ่มข่าวอีกข่าว</a>
            </div>
        </div>

        <div id="alertError" class="alert alert-danger">
            <strong>เกิดข้อผิดพลาด!</strong> <span id="errorMessage"></span>
        </div>

        <!-- Form -->
        <div class="form-container">
            <form id="addNewsForm" enctype="multipart/form-data">
                
                <!-- Title -->
                <div class="form-group">
                    <label for="title">หัวข้อข่าว <span class="required">*</span></label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           placeholder="กรอกหัวข้อข่าว"
                           maxlength="255"
                           required>
                </div>

                <!-- Content -->
                <div class="form-group">
                    <label for="content">เนื้อหาข่าว <span class="required">*</span></label>
                    <textarea id="content" 
                              name="content" 
                              placeholder="กรอกเนื้อหาข่าว"
                              required></textarea>
                </div>

                <!-- Category -->
                <div class="form-group">
                    <label for="category">หมวดหมู่ <span class="required">*</span></label>
                    <select id="category" name="category" required>
                        <option value="ทั่วไป">ทั่วไป</option>
                        <option value="ประกาศ">ประกาศ</option>
                        <option value="กิจกรรม">กิจกรรม</option>
                    </select>
                </div>

                <!-- Image Upload -->
                <div class="form-group">
                    <label for="image">ภาพประกอบ</label>
                    <input type="file" 
                           id="image" 
                           name="image" 
                           accept="image/jpeg,image/jpg,image/png,image/webp">
                    <div class="file-info">
                        รองรับไฟล์: JPG, JPEG, PNG, WebP | ขนาดไม่เกิน 2MB
                    </div>
                    <div class="preview-container" id="imagePreview"></div>
                </div>

                <!-- Status -->
                <div class="form-group">
                    <label for="status">สถานะ <span class="required">*</span></label>
                    <select id="status" name="status" required>
                        <option value="active">เปิดใช้งาน</option>
                        <option value="inactive">ปิดใช้งาน</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="member">การเข้าถึง <span class="required">*</span></label>
                    <select id="member" name="member" required>
                        <option value="public" <?php echo $newsData['member'] === 'public' ? 'selected' : ''; ?>>ทั่วไป</option>
                        <option value="member" <?php echo $newsData['member'] === 'member' ? 'selected' : ''; ?>>สมาชิก</option>
                    </select>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-success" id="submitBtn">บันทึกข่าว</button>
                    <a href="../index.php" class="btn">ยกเลิก</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        /**
         * jQuery Document Ready
         * การทำงาน: เริ่มต้นการทำงานเมื่อ DOM พร้อม
         */
        $(document).ready(function() {
            // Hide alerts initially
            hideAlerts();
            
            // Focus on title field
            $('#title').focus();
            
            // Form submission handler
            $('#addNewsForm').on('submit', handleFormSubmit);
            
            // Image change handler
            $('#image').on('change', function() {
                previewImage(this);
            });
            
            // Auto-resize textarea
            $('#content').on('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
            
            // Character counter for title
            $('#title').on('input', function() {
                updateCharacterCounter(this);
            });
            
            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                // Ctrl + S: Save form
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    $('#addNewsForm').submit();
                }
                
                // Escape: Clear form
                if (e.key === 'Escape') {
                    if (confirm('คุณต้องการล้างข้อมูลในฟอร์มใช่หรือไม่?')) {
                        resetForm();
                        hideAlerts();
                    }
                }
            });
            
            // Auto-save functionality
            initAutoSave();
        });

        /**
         * Form submission handler with jQuery AJAX
         * การทำงาน:
         * - ตรวจสอบข้อมูลก่อนส่ง (client-side validation)
         * - สร้าง FormData object เพื่อรองรับการอัปโหลดไฟล์
         * - ส่งข้อมูลด้วย jQuery AJAX ไปยัง add_news.php API
         * - แสดงผลลัพธ์และจัดการ UI accordingly
         */
        function handleFormSubmit(e) {
            e.preventDefault();
            
            // Client-side validation
            const title = $('#title').val().trim();
            const content = $('#content').val().trim();
            
            if (!title) {
                showAlert('กรุณากรอกหัวข้อข่าว', 'error');
                return;
            }
            
            if (!content) {
                showAlert('กรุณากรอกเนื้อหาข่าว', 'error');
                return;
            }

            // Validate image file if selected
            const imageFile = $('#image')[0].files[0];
            if (imageFile) {
                if (!validateImageFile(imageFile)) {
                    return;
                }
            }
            
            // Show loading
            showLoading(true);
            hideAlerts();
            
            // Create FormData object
            const formData = new FormData();
            formData.append('title', title);
            formData.append('content', content);
            formData.append('category', $('#category').val());
            formData.append('status', $('#status').val());
            formData.append('member', $('#member').val());
            
            // Add image file if selected
            if (imageFile) {
                formData.append('image', imageFile);
            }

            // Send jQuery AJAX request
            $.ajax({
                url: '../api/add_news.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAlert('เพิ่มข่าวเรียบร้อยแล้ว', 'success');
                        resetForm();
                        clearSavedData();
                    } else {
                        showAlert(response.message || 'เกิดข้อผิดพลาดในการเพิ่มข่าว', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Add news error:', error);
                    showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
                },
                complete: function() {
                    showLoading(false);
                }
            });
        }

        /**
         * Validate image file
         * การทำงาน:
         * - ตรวจสอบขนาดไฟล์ (ไม่เกิน 2MB)
         * - ตรวจสอบประเภทไฟล์ (รองรับเฉพาะรูปภาพ)
         * @param {File} file - ไฟล์ที่ต้องการตรวจสอบ
         * @return {boolean} - true หากผ่านการตรวจสอบ
         */
        function validateImageFile(file) {
            // Check file size (2MB max)
            if (file.size > 2 * 1024 * 1024) {
                showAlert('ไฟล์ภาพมีขนาดใหญ่เกินไป (สูงสุด 2MB)', 'error');
                return false;
            }
            
            // Check file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                showAlert('รูปแบบไฟล์ไม่ถูกต้อง (รองรับเฉพาะ JPG, JPEG, PNG, WebP)', 'error');
                return false;
            }
            
            return true;
        }

        /**
         * Reset form to initial state
         * การทำงาน: รีเซ็ตฟอร์มให้กลับสู่สถานะเริ่มต้น
         */
        function resetForm() {
            $('#addNewsForm')[0].reset();
            $('#imagePreview').empty();
            
            // Reset textarea height
            $('#content').css('height', 'auto');
            
            // Remove character counter
            $('.char-counter').remove();
            
            // Focus on title field
            $('#title').focus();
        }

        /**
         * Show/hide loading overlay
         * การทำงาน: แสดงหรือซ่อน loading overlay ระหว่างการประมวลผล
         * @param {boolean} show - true เพื่อแสดง, false เพื่อซ่อน
         */
        function showLoading(show) {
            const $submitBtn = $('#submitBtn');
            
            if (show) {
                $('#loadingOverlay').show();
                $submitBtn.prop('disabled', true).text('กำลังบันทึก...');
            } else {
                $('#loadingOverlay').hide();
                $submitBtn.prop('disabled', false).text('บันทึกข่าว');
            }
        }

        /**
         * Show alert message
         * การทำงาน: แสดงข้อความแจ้งเตือนแบบ inline (ไม่ใช่ popup)
         * @param {string} message - ข้อความที่ต้องการแสดง
         * @param {string} type - ประเภท ('success' หรือ 'error')
         */
        function showAlert(message, type) {
            hideAlerts();
            
            if (type === 'success') {
                $('#successMessage').text(message);
                $('#alertSuccess').show();
                
                // Scroll to alert
                $('html, body').animate({
                    scrollTop: $('#alertSuccess').offset().top - 50
                }, 500);
            } else {
                $('#errorMessage').text(message);
                $('#alertError').show();
                
                // Scroll to alert
                $('html, body').animate({
                    scrollTop: $('#alertError').offset().top - 50
                }, 500);
            }
            
            // Auto hide error alerts after 5 seconds
            if (type === 'error') {
                setTimeout(hideAlerts, 5000);
            }
        }

        /**
         * Hide all alert messages
         * การทำงาน: ซ่อนข้อความแจ้งเตือนทั้งหมด
         */
        function hideAlerts() {
            $('#alertSuccess, #alertError').hide();
        }

        /**
         * Image preview function
         * การทำงาน: แสดงตัวอย่างรูปภาพที่เลือกก่อนอัปโหลด
         * @param {HTMLInputElement} input - input element ของการเลือกไฟล์
         */
        function previewImage(input) {
            const $previewContainer = $('#imagePreview');
            $previewContainer.empty();
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Validate file before preview
                if (!validateImageFile(file)) {
                    $(input).val('');
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const $img = $('<img>')
                        .attr('src', e.target.result)
                        .addClass('preview-image')
                        .attr('alt', 'ตัวอย่างภาพ');
                    
                    const $label = $('<div>')
                        .text('ตัวอย่างภาพ')
                        .css({
                            'font-size': '12px',
                            'color': '#6c757d',
                            'margin-top': '5px'
                        });
                    
                    $previewContainer.append($img).append($label);
                };
                reader.readAsDataURL(file);
            }
        }

        /**
         * Character counter for title
         * การทำงาน: แสดงจำนวนตัวอักษรที่พิมพ์ในหัวข้อข่าว
         * @param {HTMLElement} input - input element
         */
        function updateCharacterCounter(input) {
            const maxLength = 255;
            const currentLength = $(input).val().length;
            const $parent = $(input).parent();
            
            // Create or update character counter
            let $counter = $parent.find('.char-counter');
            if ($counter.length === 0) {
                $counter = $('<div class="char-counter"></div>').css({
                    'font-size': '12px',
                    'color': '#6c757d',
                    'margin-top': '5px'
                });
                $parent.append($counter);
            }
            
            $counter.text(`${currentLength}/${maxLength} ตัวอักษร`);
            
            // Change color when approaching limit
            if (currentLength > maxLength * 0.9) {
                $counter.css('color', '#dc3545');
            } else {
                $counter.css('color', '#6c757d');
            }
        }

        /**
         * Auto-save functionality
         * การทำงาน: บันทึกข้อมูลใน localStorage เพื่อกู้คืนหากหน้าถูกรีเฟรช
         */
        function initAutoSave() {
            const $inputs = $('#addNewsForm').find('input[type="text"], textarea, select');
            
            // Load saved data
            $inputs.each(function() {
                const $input = $(this);
                const name = $input.attr('name');
                if (name && $input.attr('type') !== 'file') {
                    const savedValue = localStorage.getItem('addNews_' + name);
                    if (savedValue) {
                        $input.val(savedValue);
                    }
                }
            });
            
            // Save data on input
            $inputs.on('input change', function() {
                const $input = $(this);
                const name = $input.attr('name');
                if (name && $input.attr('type') !== 'file') {
                    localStorage.setItem('addNews_' + name, $input.val());
                }
            });
        }

        /**
         * Clear saved form data
         * การทำงาน: ล้างข้อมูลที่บันทึกไว้ใน localStorage
         */
        function clearSavedData() {
            $('#addNewsForm').find('input[type="text"], textarea, select').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    localStorage.removeItem('addNews_' + name);
                }
            });
        }
    </script>
</body>
</html>