-- قاعدة بيانات الموقع التعريفي الشخصي (SQLite)
-- ملاحظة: هذا الملف للمرجعية فقط. التثبيت يتم تلقائياً من خلال صفحة install.php

-- جدول المستخدمين (للوحة التحكم)
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    full_name TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- جدول المشاريع/الأعمال
CREATE TABLE IF NOT EXISTS projects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    image TEXT,
    project_url TEXT,
    github_url TEXT,
    category TEXT,
    technologies TEXT,
    display_order INTEGER DEFAULT 0,
    is_featured INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- جدول معلومات الموقع
CREATE TABLE IF NOT EXISTS site_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    site_title TEXT DEFAULT 'موقعي التعريفي',
    site_description TEXT,
    about_me TEXT,
    profile_image TEXT,
    email TEXT,
    phone TEXT,
    github_url TEXT,
    linkedin_url TEXT,
    twitter_url TEXT,
    -- SMTP Settings
    smtp_host TEXT,
    smtp_port INTEGER DEFAULT 587,
    smtp_username TEXT,
    smtp_password TEXT,
    smtp_encryption TEXT DEFAULT 'tls',
    smtp_from_email TEXT,
    smtp_from_name TEXT,
    smtp_enabled INTEGER DEFAULT 0,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- جدول الرسائل
CREATE TABLE IF NOT EXISTS messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    subject TEXT NOT NULL,
    message TEXT NOT NULL,
    is_read INTEGER DEFAULT 0,
    is_replied INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- جدول مواقع التواصل الاجتماعي (ديناميكية)
CREATE TABLE IF NOT EXISTS social_links (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    platform_name TEXT NOT NULL,
    icon_class TEXT NOT NULL,
    url TEXT NOT NULL,
    display_order INTEGER DEFAULT 0,
    is_visible INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- جدول الحقول المخصصة للمشاريع
CREATE TABLE IF NOT EXISTS project_custom_fields (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    field_name TEXT NOT NULL,
    field_label TEXT NOT NULL,
    field_type TEXT DEFAULT 'text',
    field_options TEXT,
    is_required INTEGER DEFAULT 0,
    display_order INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- جدول قيم الحقول المخصصة للمشاريع
CREATE TABLE IF NOT EXISTS project_field_values (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    project_id INTEGER NOT NULL,
    field_id INTEGER NOT NULL,
    field_value TEXT,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (field_id) REFERENCES project_custom_fields(id) ON DELETE CASCADE
);

-- ملاحظة: بيانات المستخدم والإعدادات تُضاف عبر صفحة التثبيت (install.php)
