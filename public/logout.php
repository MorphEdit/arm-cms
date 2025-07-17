<?php
/**
 * Logout Handler
 * ARM CMS - Content Management System
 * 
 * จัดการการออกจากระบบ
 */

// Include authentication system
require_once '../config/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    // Already logged out, redirect to login
    header('Location: index.php');
    exit();
}

// Clear remember me cookie if exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

// Perform logout
logout();

// Redirect to login page with success message
header('Location: index.php?logout=1');
exit();
?>