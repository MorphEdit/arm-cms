-- เพิ่ม columns สำหรับ remember me
ALTER TABLE users ADD COLUMN remember_token VARCHAR(255) NULL DEFAULT NULL;
ALTER TABLE users ADD COLUMN remember_expires TIMESTAMP NULL DEFAULT NULL;

-- เพิ่ม index สำหรับประสิทธิภาพ
CREATE INDEX idx_remember_token ON users(remember_token);