<?php
/**
 * Login Page
 * ARM CMS - Content Management System
 * 
 * หน้าเข้าสู่ระบบสำหรับผู้ดูแล
 */

 session_start();

// Include authentication system
require_once '../config/auth.php';

// Redirect if already logged in
// if (isLoggedIn()) {
//     $redirectUrl = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : '../index.php';
//     unset($_SESSION['redirect_after_login']);
//     header('Location: ' . $redirectUrl);
//     exit();
// }

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) ? true : false;
    
    // Basic validation
    if (empty($username)) {
        $error = 'กรุณากรอกชื่อผู้ใช้';
    } elseif (empty($password)) {
        $error = 'กรุณากรอกรหัสผ่าน';
    } else {
        // Attempt login (ส่ง $remember parameter)
        $result = login($username, $password, $remember);
        
        if ($result['success']) {
            $success = $result['message'];

            // $_SESSION['IsLogin'] = true;
            
            // Redirect after successful login
            $redirectUrl = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'index.php';
            unset($_SESSION['redirect_after_login']);
            
            // JavaScript redirect for better UX
            echo "<script>setTimeout(function(){ window.location.href = '" . htmlspecialchars($redirectUrl) . "'; }, 1500);</script>";
            
        } else {
            $error = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ARM CMS</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/main.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    
    <style>
        /* Login Page Specific Styles */
        body {
            background: linear-gradient(135deg, rgb(224, 6, 42) 0%, rgb(174, 5, 33) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            padding: 50px;
            width: 100%;
            max-width: 420px;
            margin: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .login-title {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, rgb(224, 6, 42) 0%, rgb(174, 5, 33) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0 0 8px 0;
        }
        
        .login-subtitle {
            color: #666;
            font-size: 16px;
            margin: 0;
            font-weight: 500;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            box-sizing: border-box;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .form-input:focus {
            outline: none;
            border-color: rgb(224, 6, 42);
            box-shadow: 0 0 0 4px rgba(224, 6, 42, 0.1);
            background: rgba(255, 255, 255, 1);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin: 0;
            transform: scale(1.1);
            accent-color: rgb(224, 6, 42);
        }
        
        .checkbox-group label {
            margin: 0;
            font-size: 14px;
            color: #555;
            cursor: pointer;
            font-weight: 500;
        }
        
        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, rgb(224, 6, 42) 0%, rgb(174, 5, 33) 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }
        
        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(224, 6, 42, 0.4);
        }
        
        .login-btn:hover::before {
            left: 100%;
        }
        
        .login-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 500;
            border: 1px solid;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
            color: #c62828;
            border-color: #ef5350;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
            color: #2e7d32;
            border-color: #66bb6a;
        }
        
        .back-link {
            text-align: center;
            margin-top: 25px;
        }
        
        .back-link a {
            color: rgb(224, 6, 42);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .back-link a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 50%;
            background: linear-gradient(135deg, rgb(224, 6, 42) 0%, rgb(174, 5, 33) 100%);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }
        
        .back-link a:hover::after {
            width: 100%;
        }
        
        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .input-icon {
            position: relative;
        }
        
        .input-icon input {
            padding-left: 50px;
        }
        
        .input-icon .icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            z-index: 2;
            color: #999;
        }
        
        .demo-credentials {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border: 2px solid #cbd5e0;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 25px;
            font-size: 13px;
            color: #4a5568;
        }
        
        .demo-credentials strong {
            color: #2d3748;
            font-weight: 600;
        }
        
        .demo-credentials code {
            background: rgba(224, 6, 42, 0.1);
            color: rgb(224, 6, 42);
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
        }
        
        /* Auto login notification */
        .auto-login-info {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border: 2px solid #2196f3;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 25px;
            font-size: 13px;
            color: #1565c0;
            text-align: center;
            display: none;
        }
        
        .auto-login-info strong {
            color: #0d47a1;
            font-weight: 600;
        }
        
        /* Floating Animation */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .login-container {
            animation: float 6s ease-in-out infinite;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 35px 25px;
                margin: 15px;
                animation: none;
            }
            
            .login-title {
                font-size: 24px;
            }
            
            .login-subtitle {
                font-size: 14px;
            }
            
            .form-input {
                padding: 12px 16px;
            }
            
            .input-icon input {
                padding-left: 45px;
            }
            
            .input-icon .icon {
                left: 16px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        
        <!-- Header -->
        <div class="login-header">
            <h1 class="login-title">เข้าสู่ระบบ</h1>
            <p class="login-subtitle">ARM CMS - ระบบจัดการข่าว</p>
        </div>
        
        <!-- Auto Login Notification -->
        <?php if (isset($_SESSION['auto_login']) && $_SESSION['auto_login']): ?>
            <div class="auto-login-info">
                ✨ <strong>ยินดีต้อนรับกลับ!</strong><br>
                เข้าสู่ระบบอัตโนมัติจากการจดจำการเข้าสู่ระบบ
            </div>
            <?php unset($_SESSION['auto_login']); ?>
        <?php endif; ?>
        
        <!-- Alert Messages -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                ✅ <?php echo htmlspecialchars($success); ?>
                <br><small>กำลังนำท่านเข้าสู่ระบบ...</small>
            </div>
        <?php endif; ?>
        
        <!-- Demo Credentials (remove in production) -->
        <!-- <div class="demo-credentials">
            <strong>ข้อมูลทดสอบ:</strong><br>
            Username: <code>admin</code><br>
            Password: <code>password</code>
        </div> -->
        
        <!-- Login Form -->
        <form method="POST" action="" id="loginForm">
            
            <!-- Username -->
            <div class="form-group">
                <label for="username">ชื่อผู้ใช้</label>
                <div class="input-icon">
                    <span class="icon">👤</span>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-input"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                           placeholder="กรอกชื่อผู้ใช้"
                           required
                           autocomplete="username">
                </div>
            </div>
            
            <!-- Password -->
            <div class="form-group">
                <label for="password">รหัสผ่าน</label>
                <div class="input-icon">
                    <span class="icon">🔒</span>
                    <input type="password" 
                           id="password" 
                           name="password"
                           class="form-input"
                           placeholder="กรอกรหัสผ่าน"
                           required
                           autocomplete="current-password">
                </div>
            </div>
            
            <!-- Remember Me -->
            <div class="checkbox-group">
                <input type="checkbox" 
                       id="remember" 
                       name="remember" 
                       value="1"
                       <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                <label for="remember">จดจำการเข้าสู่ระบบ (30 วัน)</label>
            </div>
            
            <!-- Submit Button -->
            <button type="submit" class="login-btn" id="loginBtn">
                <span class="loading-spinner" id="loadingSpinner"></span>
                <span id="btnText">เข้าสู่ระบบ</span>
            </button>
            
        </form>
        
        <!-- Back Link -->
        <div class="back-link">
            <a href="index.php">← กลับไปหน้าข่าว</a>
        </div>
        
    </div>

    <script>
        /**
         * Login Page JavaScript
         * การจัดการหน้า login และ UX enhancements
         */
        
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const loginBtn = document.getElementById('loginBtn');
            const btnText = document.getElementById('btnText');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            
            // Focus on username field
            usernameInput.focus();
            
            // Form submission handler
            form.addEventListener('submit', function(e) {
                const username = usernameInput.value.trim();
                const password = passwordInput.value;
                
                // Basic client-side validation
                if (!username) {
                    e.preventDefault();
                    showError('กรุณากรอกชื่อผู้ใช้');
                    usernameInput.focus();
                    return;
                }
                
                if (!password) {
                    e.preventDefault();
                    showError('กรุณากรอกรหัสผ่าน');
                    passwordInput.focus();
                    return;
                }
                
                // Show loading state
                showLoading();
            });
            
            // Enter key handling
            usernameInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    passwordInput.focus();
                }
            });
            
            passwordInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    form.submit();
                }
            });
            
            // Demo credentials auto-fill (remove in production)
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'd') {
                    e.preventDefault();
                    usernameInput.value = 'admin';
                    passwordInput.value = 'password';
                    usernameInput.focus();
                }
            });
        });
        
        /**
         * Show loading state
         * การทำงาน: แสดงสถานะกำลังโหลดขณะส่งฟอร์ม
         */
        function showLoading() {
            const loginBtn = document.getElementById('loginBtn');
            const btnText = document.getElementById('btnText');
            const loadingSpinner = document.getElementById('loadingSpinner');
            
            loginBtn.disabled = true;
            btnText.textContent = 'กำลังเข้าสู่ระบบ...';
            loadingSpinner.style.display = 'inline-block';
        }
        
        /**
         * Show error message
         * การทำงาน: แสดงข้อความ error แบบ dynamic
         * @param {string} message - ข้อความ error
         */
        function showError(message) {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());
            
            // Create new alert
            const alert = document.createElement('div');
            alert.className = 'alert alert-error';
            alert.innerHTML = '⚠️ ' + message;
            
            // Insert before form
            const form = document.getElementById('loginForm');
            form.parentNode.insertBefore(alert, form);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }
        
        /**
         * Show success message
         * การทำงาน: แสดงข้อความสำเร็จ
         * @param {string} message - ข้อความสำเร็จ
         */
        function showSuccess(message) {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());
            
            // Create new alert
            const alert = document.createElement('div');
            alert.className = 'alert alert-success';
            alert.innerHTML = '✅ ' + message;
            
            // Insert before form
            const form = document.getElementById('loginForm');
            form.parentNode.insertBefore(alert, form);
        }
        
        // Check for URL parameters (logout message, etc.)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('logout') === '1') {
            showSuccess('ออกจากระบบเรียบร้อยแล้ว');
            
            // Clean URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }
        
        if (urlParams.get('session_expired') === '1') {
            showError('เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่');
            
            // Clean URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }
        
        // Prevent back button after logout
        if (urlParams.get('logout') === '1' || urlParams.get('session_expired') === '1') {
            window.history.pushState(null, null, window.location.href);
            window.onpopstate = function() {
                window.history.pushState(null, null, window.location.href);
            };
        }
    </script>
</body>
</html>