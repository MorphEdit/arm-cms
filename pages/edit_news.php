<?php
/**
 * Edit News Page
 * ARM CMS - Content Management System
 * 
 * หน้าสำหรับแก้ไขข่าว รองรับการแก้ไขข้อมูล การจัดการรูปภาพ และการลบข่าว
 * ใช้ jQuery AJAX สำหรับการบันทึกข้อมูล
 */

// Start session
session_start();

// Include authentication
require_once '../config/auth.php';

// Require login to access this page
requireLogin();

// Include database connection
require_once '../config/database.php';

// Get news ID from URL
// การทำงาน: ดึง ID ของข่าวจาก URL parameter
$newsId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// การทำงาน: ตรวจสอบ ID ว่าถูกต้องหรือไม่ หากไม่ถูกต้องให้กลับไปหน้าหลัก
if ($newsId <= 0) {
    header('Location: ../index.php');
    exit();
}

// Initialize variables
$newsData = null;

// Get database connection
$db = getDatabase();

// Load existing news data
// การทำงาน: โหลดข้อมูลข่าวที่ต้องการแก้ไขจากฐานข้อมูล
try {
    $stmt = $db->prepare("SELECT * FROM cms WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $newsId]);
    $newsData = $stmt->fetch(PDO::FETCH_ASSOC);

    // การทำงาน: หากไม่พบข่าวให้กลับไปหน้าหลัก
    if (!$newsData) {
        header('Location: ../index.php');
        exit();
    }
} catch (Exception $e) {
    header('Location: ../index.php');
    exit();
}

?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข่าว - ARM CMS</title>
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

        .current-image {
            margin-bottom: 15px;
        }

        .current-image img {
            max-width: 200px;
            max-height: 200px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
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
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
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
            <a href="../index.php">หน้าหลัก</a> > แก้ไขข่าว
        </div>

        <!-- Header -->
        <header class="header">
            <h1>แก้ไขข่าว</h1>
            <p>ARM CMS - Content Management System</p>
        </header>

        <!-- Alert Messages -->
        <div id="alertSuccess" class="alert alert-success">
            <strong>สำเร็จ!</strong> <span id="successMessage"></span>
        </div>

        <div id="alertError" class="alert alert-danger">
            <strong>เกิดข้อผิดพลาด!</strong> <span id="errorMessage"></span>
        </div>

        <!-- Form -->
        <div class="form-container">
            <form id="editNewsForm" enctype="multipart/form-data">
                <!-- Hidden field สำหรับ news ID -->
                <input type="hidden" id="newsId" value="<?php echo $newsId; ?>">

                <!-- Title -->
                <div class="form-group">
                    <label for="title">หัวข้อข่าว <span class="required">*</span></label>
                    <input type="text" id="title" name="title"
                        value="<?php echo htmlspecialchars($newsData['title']); ?>" placeholder="กรอกหัวข้อข่าว"
                        maxlength="255" required>
                </div>

                <!-- Content -->
                <div class="form-group">
                    <label for="content">เนื้อหาข่าว <span class="required">*</span></label>
                    <textarea id="content" name="content" placeholder="กรอกเนื้อหาข่าว"
                        required><?php echo htmlspecialchars($newsData['content']); ?></textarea>
                </div>

                <!-- Category -->
                <div class="form-group">
                    <label for="category">หมวดหมู่ <span class="required">*</span></label>
                    <select id="category" name="category" required>
                        <option value="ทั่วไป" <?php echo $newsData['category'] === 'ทั่วไป' ? 'selected' : ''; ?>>ทั่วไป
                        </option>
                        <option value="ประกาศ" <?php echo $newsData['category'] === 'ประกาศ' ? 'selected' : ''; ?>>ประกาศ
                        </option>
                        <option value="กิจกรรม" <?php echo $newsData['category'] === 'กิจกรรม' ? 'selected' : ''; ?>>
                            กิจกรรม</option>
                    </select>
                </div>

                <!-- Current Image -->
                <div class="form-group">
                    <label>ภาพประกอบปัจจุบัน</label>
                    <div id="currentImageContainer">
                        <?php if (!empty($newsData['image'])): ?>
                            <div class="current-image">
                                <img src="../uploads/<?php echo htmlspecialchars($newsData['image']); ?>"
                                    alt="ภาพประกอบปัจจุบัน">
                                <div class="checkbox-group">
                                    <input type="checkbox" id="remove_image" name="remove_image" value="1">
                                    <label for="remove_image">ลบภาพนี้</label>
                                </div>
                            </div>
                        <?php else: ?>
                            <div
                                style="padding: 20px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; text-align: center; color: #6c757d;">
                                ไม่มีภาพประกอบ
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- New Image Upload -->
                <div class="form-group">
                    <label for="image">อัปโหลดภาพใหม่</label>
                    <input type="file" id="image" name="image" accept="image/jpeg,image/jpg,image/png,image/webp">
                    <div class="file-info">
                        รองรับไฟล์: JPG, JPEG, PNG, WebP | ขนาดไม่เกิน 2MB<br>
                        <small>หากเลือกไฟล์ใหม่ ภาพเก่าจะถูกแทนที่</small>
                    </div>
                    <div class="preview-container" id="imagePreview"></div>
                </div>

                <!-- Status -->
                <div class="form-group">
                    <label for="status">สถานะ <span class="required">*</span></label>
                    <select id="status" name="status" required>
                        <option value="active" <?php echo $newsData['status'] === 'active' ? 'selected' : ''; ?>>
                            เปิดใช้งาน</option>
                        <option value="inactive" <?php echo $newsData['status'] === 'inactive' ? 'selected' : ''; ?>>
                            ปิดใช้งาน</option>
                    </select>
                </div>

                <!-- Member Access -->
                <div class="form-group">
                    <label for="member_access">การเข้าถึง <span class="required">*</span></label>
                    <select id="member_access" name="member_access" required>
                        <option value="public" <?php echo $newsData['member_access'] === 'public' ? 'selected' : ''; ?>>
                            สาธารณะ</option>
                        <option value="member" <?php echo $newsData['member_access'] === 'member' ? 'selected' : ''; ?>>
                            สมาชิกเท่านั้น</option>
                    </select>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-success" id="updateBtn">อัปเดตข่าว</button>
                    <a href="../index.php" class="btn">ยกเลิก</a>
                    <button type="button" class="btn btn-danger" id="deleteBtn">ลบข่าวนี้</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        /**
         * jQuery Document Ready
         * การทำงาน: เริ่มต้นการทำงานเมื่อ DOM พร้อม
         */
        $(document).ready(function () {
            // Hide alerts initially
            hideAlerts();

            // Initialize textarea height
            const $textarea = $('#content');
            $textarea.css('height', 'auto');
            $textarea.css('height', $textarea[0].scrollHeight + 'px');

            // Form submission handler
            $('#editNewsForm').on('submit', handleFormSubmit);

            // Delete button handler
            $('#deleteBtn').on('click', confirmDelete);

            // Image change handler
            $('#image').on('change', function () {
                previewImage(this);
            });

            // Auto-resize textarea
            $('#content').on('input', function () {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });

            // Character counter for title
            $('#title').on('input', function () {
                updateCharacterCounter(this);
            });

            // Handle remove image checkbox
            $('#remove_image').on('change', function () {
                if (this.checked && $('#image').val()) {
                    if (confirm('คุณได้เลือกทั้งลบภาพเก่าและอัปโหลดภาพใหม่\nภาพใหม่จะถูกใช้แทน')) {
                        this.checked = false;
                    }
                }
            });
        });

        /**
         * Form submission handler with jQuery AJAX
         * การทำงาน:
         * - ตรวจสอบข้อมูลก่อนส่ง (client-side validation)
         * - สร้าง FormData object เพื่อรองรับการอัปโหลดไฟล์
         * - ส่งข้อมูลด้วย jQuery AJAX ไปยัง update_news.php API
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
            formData.append('id', $('#newsId').val());
            formData.append('title', title);
            formData.append('content', content);
            formData.append('category', $('#category').val());
            formData.append('status', $('#status').val());
            formData.append('member_access', $('#member_access').val());

            // Add remove_image flag if checked
            if ($('#remove_image').is(':checked')) {
                formData.append('remove_image', '1');
            }

            // Add image file if selected
            if (imageFile) {
                formData.append('image', imageFile);
            }

            // Send jQuery AJAX request
            $.ajax({
                url: '../api/update_news.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        showAlert('อัปเดตข่าวเรียบร้อยแล้ว', 'success');

                        // Update current image if changed
                        if (response.updated_data) {
                            updateCurrentImage(response.updated_data.image);
                        }

                        // Clear new image selection
                        $('#image').val('');
                        $('#imagePreview').empty();

                        // Uncheck remove image
                        $('#remove_image').prop('checked', false);

                    } else {
                        showAlert(response.message || 'เกิดข้อผิดพลาดในการอัปเดตข่าว', 'error');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Update error:', error);
                    showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
                },
                complete: function () {
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
         * Update current image display
         * การทำงาน:
         * - อัปเดตรูปภาพที่แสดงในส่วน "ภาพประกอบปัจจุบัน"
         * - จัดการกรณีมีรูปภาพหรือไม่มีรูปภาพ
         * @param {string|null} imageName - ชื่อไฟล์รูปภาพใหม่
         */
        function updateCurrentImage(imageName) {
            const $container = $('#currentImageContainer');

            if (imageName) {
                // Has image - show image with remove option
                $container.html(`
                    <div class="current-image">
                        <img src="../uploads/${imageName}" alt="ภาพประกอบปัจจุบัน">
                        <div class="checkbox-group">
                            <input type="checkbox" id="remove_image" name="remove_image" value="1">
                            <label for="remove_image">ลบภาพนี้</label>
                        </div>
                    </div>
                `);
            } else {
                // No image - show placeholder
                $container.html(`
                    <div style="padding: 20px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; text-align: center; color: #6c757d;">
                        ไม่มีภาพประกอบ
                    </div>
                `);
            }
        }

        /**
         * Show/hide loading overlay
         * การทำงาน: แสดงหรือซ่อน loading overlay ระหว่างการประมวลผล
         * @param {boolean} show - true เพื่อแสดง, false เพื่อซ่อน
         */
        function showLoading(show) {
            const $updateBtn = $('#updateBtn');

            if (show) {
                $('#loadingOverlay').show();
                $updateBtn.prop('disabled', true).text('กำลังอัปเดต...');
            } else {
                $('#loadingOverlay').hide();
                $updateBtn.prop('disabled', false).text('อัปเดตข่าว');
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
            } else {
                $('#errorMessage').text(message);
                $('#alertError').show();
            }

            // Auto hide after 5 seconds
            setTimeout(hideAlerts, 5000);
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
                reader.onload = function (e) {
                    const $img = $('<img>')
                        .attr('src', e.target.result)
                        .addClass('preview-image')
                        .attr('alt', 'ตัวอย่างภาพใหม่');

                    const $label = $('<div>')
                        .text('ตัวอย่างภาพใหม่')
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
         * Confirm delete function
         * การทำงาน: แสดง confirmation dialog และดำเนินการลบหากผู้ใช้ยืนยัน
         */
        function confirmDelete() {
            if (confirm('คุณต้องการลบข่าวนี้ใช่หรือไม่?\n\nการลบจะไม่สามารถกู้คืนได้')) {
                // ใช้ jQuery AJAX เรียก delete API
                $.ajax({
                    url: '../api/delete_news.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ id: <?php echo $newsId; ?> }),
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            alert('ลบข่าวสำเร็จ');
                            window.location.href = '../index.php';
                        } else {
                            alert('เกิดข้อผิดพลาด: ' + response.message);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error:', error);
                        alert('เกิดข้อผิดพลาดในการลบข่าว');
                    }
                });
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
    </script>
</body>

</html>