-- เพิ่ม 'member' เข้าไปใน ENUM role

ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'editor', 'member') NOT NULL DEFAULT 'member';

-- เพิ่ม user member ตัวอย่าง

INSERT INTO users (username, password, full_name, email, role, status) VALUES 

('member1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'สมาชิกทดสอบ', 'member@example.com', 'member', 'active');