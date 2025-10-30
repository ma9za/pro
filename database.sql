-- قاعدة بيانات الموقع التعريفي الشخصي
CREATE DATABASE IF NOT EXISTS portfolio_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE portfolio_db;

-- جدول المستخدمين (للوحة التحكم)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول المشاريع/الأعمال
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    project_url VARCHAR(255),
    github_url VARCHAR(255),
    category VARCHAR(50),
    technologies VARCHAR(255),
    display_order INT DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول معلومات الموقع
CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_title VARCHAR(100) DEFAULT 'موقعي التعريفي',
    site_description TEXT,
    about_me TEXT,
    profile_image VARCHAR(255),
    email VARCHAR(100),
    phone VARCHAR(20),
    github_url VARCHAR(255),
    linkedin_url VARCHAR(255),
    twitter_url VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدراج مستخدم افتراضي (اسم المستخدم: admin، كلمة المرور: admin123)
-- يُنصح بتغيير كلمة المرور بعد أول تسجيل دخول
INSERT INTO users (username, password, email, full_name) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'المسؤول');

-- إدراج إعدادات افتراضية للموقع
INSERT INTO site_settings (site_title, site_description, about_me) VALUES
('موقعي التعريفي', 'مطور ومصمم مواقع ويب', 'مرحباً بك في موقعي التعريفي. أنا مطور ومصمم مواقع ويب متخصص في تطوير تطبيقات الويب الحديثة.');
