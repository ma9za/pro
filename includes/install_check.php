<?php
// التحقق من تثبيت الموقع
if (!file_exists(INSTALL_LOCK)) {
    // إذا لم يكن الموقع مثبتاً، أعد التوجيه لصفحة التثبيت
    // استثناء: لا تعيد التوجيه إذا كنا في صفحة التثبيت نفسها
    $current_script = basename($_SERVER['PHP_SELF']);
    if ($current_script !== 'install.php') {
        header('Location: ' . SITE_URL . '/install.php');
        exit();
    }
}
