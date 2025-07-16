<?php
/**
 * Debug Configuration
 * ARM CMS - Content Management System
 * 
 * ไฟล์ configuration สำหรับจัดการ debug settings และ environment variables
 * ใช้สำหรับควบคุมการแสดงข้อมูล debug, error reporting และ logging
 * รองรับหลาย environment (development, production, testing)
 */

// Debug settings
// การทำงาน: เปิด/ปิดโหมด debug สำหรับระบบทั้งหมด
// - true: เปิดใช้งาน debug mode (แสดง error details, query logs, performance info)
// - false: ปิดใช้งาน debug mode (สำหรับ production environment)
define('DEBUG', false);  // เปิดใช้งาน debug mode

// การทำงาน: กำหนด environment ที่ระบบทำงานอยู่ เพื่อปรับการ configuration ให้เหมาะสม
// - 'development': สภาพแวดล้อมพัฒนา (เครื่อง local developer)
// - 'production': สภาพแวดล้อมจริง (เซิร์ฟเวอร์ที่ผู้ใช้เข้าถึง)
// - 'testing': สภาพแวดล้อมทดสอบ
// - 'staging': สภาพแวดล้อม staging ก่อนขึ้น production
define('ENV', 'development'); // development, production

// Debug levels (optional)
// การทำงาน: กำหนดระดับความละเอียดของการ debug
// - 0: ปิดใช้งาน debug ทั้งหมด
// - 1: basic debug (แสดง errors และ warnings พื้นฐาน)
// - 2: verbose debug (แสดง debug info รายละเอียด เช่น queries, performance metrics)
// - 3: extreme debug (แสดงทุกอย่าง รวมถึง variable dumps และ stack traces)
define('DEBUG_LEVEL', 1); // 0=off, 1=basic, 2=verbose
?>