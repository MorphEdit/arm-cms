-- เพิ่ม table users ใน database.sql
-- การทำงาน: สร้างตาราง users สำหรับระบบ authentication

-- สร้าง Table สำหรับผู้ใช้งาน
CREATE TABLE users (
    -- id: รหัสผู้ใช้ (Primary Key, Auto Increment)
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- username: ชื่อผู้ใช้สำหรับ login (ไม่เกิน 50 ตัวอักษร, ไม่ซ้ำ, บังคับกรอก)
    username VARCHAR(50) NOT NULL UNIQUE,
    
    -- password: รหัสผ่านที่เข้ารหัสแล้ว (VARCHAR 255 เพื่อรองรับ password_hash)
    password VARCHAR(255) NOT NULL,
    
    -- full_name: ชื่อเต็มของผู้ใช้ (ไม่เกิน 100 ตัวอักษร, บังคับกรอก)
    full_name VARCHAR(100) NOT NULL,
    
    -- email: อีเมลผู้ใช้ (ไม่เกิน 100 ตัวอักษร, ไม่ซ้ำ, อนุญาตให้เป็น NULL)
    email VARCHAR(100) UNIQUE DEFAULT NULL,
    
    -- role: บทบาทของผู้ใช้ (ENUM, ค่าเริ่มต้น 'editor')
    -- - 'admin': ผู้ดูแลระบบ (สิทธิเต็ม)
    -- - 'editor': บรรณาธิการ (จัดการข่าว)
    role ENUM('admin', 'editor') NOT NULL DEFAULT 'editor',
    
    -- status: สถานะผู้ใช้ (ENUM, ค่าเริ่มต้น 'active')
    -- - 'active': ใช้งานได้
    -- - 'inactive': ปิดการใช้งาน
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    
    -- last_login: วันที่เข้าสู่ระบบครั้งล่าสุด (TIMESTAMP, อนุญาตให้เป็น NULL)
    last_login TIMESTAMP NULL DEFAULT NULL,
    
    -- created_at: วันที่สร้างผู้ใช้ (TIMESTAMP, ค่าเริ่มต้นเป็นเวลาปัจจุบัน)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- updated_at: วันที่แก้ไขล่าสุด (TIMESTAMP, อัปเดตอัตโนมัติเมื่อมีการแก้ไข)
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- เพิ่มดัชนีเพื่อเพิ่มประสิทธิภาพการค้นหา
CREATE INDEX idx_username ON users(username);
CREATE INDEX idx_status ON users(status);
CREATE INDEX idx_role ON users(role);

-- เพิ่มผู้ใช้ admin เริ่มต้น
-- Username: admin
-- Password: password (จะถูกเข้ารหัสด้วย password_hash ใน PHP)
INSERT INTO users (username, password, full_name, email, role, status) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูแลระบบ', 'admin@example.com', 'admin', 'active');

-- หมายเหตุ: รหัสผ่านข้างบนคือ 'password' ที่เข้ารหัสแล้ว
-- ในการใช้งานจริง ควรเปลี่ยนรหัสผ่านใหม่ทันที

/*
คำอธิบายการใช้งาน:

1. การเข้าสู่ระบบ:
   SELECT * FROM users WHERE username = ? AND status = 'active';

2. การตรวจสอบรหัสผ่าน (ใน PHP):
   password_verify($input_password, $user['password']);

3. การสร้างผู้ใช้ใหม่:
   INSERT INTO users (username, password, full_name) 
   VALUES (?, password_hash($password, PASSWORD_DEFAULT), ?);

4. การอัปเดต last_login:
   UPDATE users SET last_login = NOW() WHERE id = ?;

5. การค้นหาผู้ใช้:
   SELECT * FROM users WHERE username LIKE '%?%' OR full_name LIKE '%?%';

Security Notes:
- ไม่เก็บรหัสผ่านแบบ plain text
- ใช้ password_hash() และ password_verify() ของ PHP
- มี unique constraint สำหรับ username และ email
- มีระบบ role และ status เพื่อจัดการสิทธิ์
*/