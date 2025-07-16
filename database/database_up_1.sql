-- วิธีที่ปลอดภัย: เพิ่มทั้งสองค่าไว้ใน ENUM พร้อมกัน
ALTER TABLE cms MODIFY COLUMN category ENUM('ทั่วไป', 'ประกาศ', 'กิจกรรม', 'กิจกรรม') NOT NULL DEFAULT 'ทั่วไป';

-- อัปเดตข้อมูล
UPDATE cms SET category = 'กิจกรรม' WHERE category = 'กิจกรรม';

-- ตรวจสอบผลลัพธ์
SELECT category, COUNT(*) as count FROM cms GROUP BY category;

-- หากผลลัพธ์ถูกต้องแล้ว ให้ลบ 'กิจกรรม' ออก
ALTER TABLE cms MODIFY COLUMN category ENUM('ทั่วไป', 'ประกาศ', 'กิจกรรม') NOT NULL DEFAULT 'ทั่วไป';