<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// تدمير الجلسة
session_destroy();

// إعادة التوجيه لصفحة تسجيل الدخول
redirect('login.php');
