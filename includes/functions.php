<?php

// التحقق من تسجيل الدخول
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// إعادة التوجيه
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// تنظيف البيانات
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// رفع الصور
function uploadImage($file, $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp']) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'خطأ في رفع الملف'];
    }

    // التحقق من حجم الملف
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'حجم الملف كبير جداً (الحد الأقصى 5MB)'];
    }

    // التحقق من نوع الملف
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_extensions)) {
        return ['success' => false, 'message' => 'نوع الملف غير مسموح به'];
    }

    // التحقق من أن الملف هو صورة حقيقية
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return ['success' => false, 'message' => 'الملف ليس صورة'];
    }

    // إنشاء اسم فريد للملف
    $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
    $target_file = UPLOAD_DIR . $new_filename;

    // التأكد من وجود مجلد uploads
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    // نقل الملف
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['success' => true, 'filename' => $new_filename];
    } else {
        return ['success' => false, 'message' => 'فشل رفع الملف'];
    }
}

// حذف الصورة
function deleteImage($filename) {
    if (empty($filename)) return false;

    $file_path = UPLOAD_DIR . $filename;
    if (file_exists($file_path)) {
        return unlink($file_path);
    }
    return false;
}

// عرض الرسائل
function displayMessage($message, $type = 'info') {
    $class = 'message-' . $type;
    return "<div class='message {$class}'>{$message}</div>";
}

// تنسيق التاريخ
function formatDate($date) {
    return date('Y-m-d', strtotime($date));
}

// حماية CSRF
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// التحقق من صحة البريد الإلكتروني
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// التحقق من صحة URL
function validateURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL);
}

// اختصار النص
function truncateText($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text) > $length) {
        return mb_substr($text, 0, $length) . $suffix;
    }
    return $text;
}
