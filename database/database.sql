-- สร้าง Database
-- การทำงาน: สร้างฐานข้อมูลชื่อ arm_cms หากยังไม่มี
-- ใช้ charset utf8mb4 เพื่อรองรับภาษาไทยและ emoji
-- ใช้ collation utf8mb4_general_ci สำหรับการเปรียบเทียบข้อความ
CREATE DATABASE IF NOT EXISTS arm_cms 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_general_ci;

-- ใช้ Database
-- การทำงาน: เลือกใช้งานฐานข้อมูล arm_cms สำหรับคำสั่งต่อไป
USE arm_cms;

-- สร้าง Table สำหรับจัดการข่าว
-- การทำงาน: สร้างตาราง cms สำหรับเก็บข้อมูลข่าว
CREATE TABLE cms (
    -- id: รหัสข่าว (Primary Key, Auto Increment)
    -- การทำงาน: รหัสอ้างอิงเฉพาะของแต่ละข่าว เพิ่มขึ้นอัตโนมัติ
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- title: หัวข้อข่าว (ไม่เกิน 255 ตัวอักษร, บังคับกรอก)
    -- การทำงาน: เก็บหัวข้อข่าวที่ต้องกรอกทุกครั้ง
    title VARCHAR(255) NOT NULL,
    
    -- content: เนื้อหาข่าว (TEXT, บังคับกรอก)
    -- การทำงาน: เก็บเนื้อหาข่าวแบบ TEXT เพื่อรองรับข้อความยาว
    content TEXT NOT NULL,
    
    -- category: หมวดหมู่ข่าว (ENUM, มี 3 ตัวเลือก, ค่าเริ่มต้น 'ทั่วไป')
    -- การทำงาน: จำกัดหมวดหมู่ให้เลือกได้เฉพาะ 3 ประเภท
    -- - 'ทั่วไป': ข่าวทั่วไป
    -- - 'ประกาศ': ข่าวประกาศ
    -- - 'กิจกรรม': ข่าวเกี่ยวกับกิจกรรม
    category ENUM('ทั่วไป', 'ประกาศ', 'กิจกรรม') NOT NULL DEFAULT 'ทั่วไป',
    
    -- image: ชื่อไฟล์รูปภาพ (VARCHAR 255, อนุญาตให้เป็น NULL)
    -- การทำงาน: เก็บชื่อไฟล์รูปภาพประกอบข่าว ไม่บังคับ
    image VARCHAR(255) DEFAULT NULL,
    
    -- status: สถานะข่าว (ENUM, มี 2 ตัวเลือก, ค่าเริ่มต้น 'active')
    -- การทำงาน: จำกัดสถานะให้เลือกได้เฉพาะ 2 ประเภท
    -- - 'active': เปิดใช้งาน (แสดงข่าว)
    -- - 'inactive': ปิดใช้งาน (ไม่แสดงข่าว)
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    
    -- created_at: วันที่สร้างข่าว (TIMESTAMP, ค่าเริ่มต้นเป็นเวลาปัจจุบัน)
    -- การทำงาน: บันทึกวันที่และเวลาที่สร้างข่าวอัตโนมัติ
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- updated_at: วันที่แก้ไขข่าวล่าสุด (TIMESTAMP, อัปเดตอัตโนมัติเมื่อมีการแก้ไข)
    -- การทำงาน: บันทึกวันที่และเวลาที่แก้ไขข่าวล่าสุดอัตโนมัติ
    -- ON UPDATE CURRENT_TIMESTAMP จะอัปเดตเวลาทุกครั้งที่มีการแก้ไขข้อมูล
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    
-- Engine และ Charset Configuration:
-- - ENGINE=InnoDB: ใช้ storage engine InnoDB เพื่อรองรับ transactions และ foreign keys
-- - CHARSET=utf8mb4: รองรับภาษาไทยและตัวอักษรพิเศษ
-- - COLLATE=utf8mb4_general_ci: การเปรียบเทียบข้อความแบบไม่คำนึงถึงตัวพิมพ์ใหญ่เล็ก
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*
คำอธิบายการใช้งานตาราง:

1. การเพิ่มข่าวใหม่:
   INSERT INTO cms (title, content, category, image, status) 
   VALUES ('หัวข้อข่าว', 'เนื้อหาข่าว', 'ทั่วไป', 'image.jpg', 'active');

2. การดึงข่าวทั้งหมด:
   SELECT * FROM cms ORDER BY created_at DESC;

3. การดึงข่าวตามหมวดหมู่:
   SELECT * FROM cms WHERE category = 'ประกาศ' AND status = 'active';

4. การค้นหาข่าว:
   SELECT * FROM cms WHERE title LIKE '%คำค้นหา%' OR content LIKE '%คำค้นหา%';

5. การอัปเดตข่าว:
   UPDATE cms SET title = 'หัวข้อใหม่', content = 'เนื้อหาใหม่' WHERE id = 1;

6. การลบข่าว:
   DELETE FROM cms WHERE id = 1;

ดัชนีที่แนะนำ (Index Recommendations):
- CREATE INDEX idx_category ON cms(category);
- CREATE INDEX idx_status ON cms(status);
- CREATE INDEX idx_created_at ON cms(created_at);
- CREATE INDEX idx_category_status ON cms(category, status);

ดัชนีเหล่านี้จะช่วยเพิ่มประสิทธิภาพในการค้นหาและกรองข้อมูล
*/