<?php
// إعدادات قاعدة البيانات SQLite
define('DB_PATH', __DIR__ . '/../database/portfolio.db');
define('INSTALL_LOCK', __DIR__ . '/../installed.lock');

// إعدادات الموقع
define('SITE_URL', 'http://localhost/pro');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// إعدادات الجلسة
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // اجعلها 1 إذا كنت تستخدم HTTPS

// بدء الجلسة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// المنطقة الزمنية
date_default_timezone_set('Asia/Riyadh');
