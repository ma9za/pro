<?php
require_once __DIR__ . '/config/config.php';

// التحقق من التثبيت
require_once __DIR__ . '/includes/install_check.php';

// Redirect to loader page (with cool animation)
header('Location: loader.php');
exit();
