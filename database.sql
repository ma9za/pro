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
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ملاحظة: بيانات المستخدم والإعدادات تُضاف عبر صفحة التثبيت (install.php)
