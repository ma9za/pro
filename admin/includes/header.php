<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'لوحة التحكم'; ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-wrapper">
        <!-- الشريط الجانبي -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-user-shield"></i> لوحة التحكم</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> الرئيسية
                </a>
                <a href="projects.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'projects.php' ? 'active' : ''; ?>">
                    <i class="fas fa-briefcase"></i> المشاريع
                </a>
                <a href="add_project.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'add_project.php' ? 'active' : ''; ?>">
                    <i class="fas fa-plus"></i> إضافة مشروع
                </a>
                <a href="messages.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'messages.php' || basename($_SERVER['PHP_SELF']) === 'get_message.php' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i> الرسائل
                    <?php
                    // عرض عدد الرسائل غير المقروءة
                    if (isset($db)) {
                        $stmt = $db->query("SELECT COUNT(*) as unread FROM messages WHERE is_read = 0");
                        $unread = $stmt->fetch()['unread'];
                        if ($unread > 0) {
                            echo "<span style='background: var(--danger-color); color: white; padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.75rem; margin-right: auto;'>$unread</span>";
                        }
                    }
                    ?>
                </a>
                <a href="custom_fields.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'custom_fields.php' ? 'active' : ''; ?>">
                    <i class="fas fa-th-list"></i> الحقول المخصصة
                </a>
                <a href="social_links.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'social_links.php' ? 'active' : ''; ?>">
                    <i class="fas fa-share-alt"></i> روابط التواصل
                </a>
                <a href="settings.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i> الإعدادات
                </a>
                <a href="account.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'account.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle"></i> حسابي
                </a>
                <a href="../index.php" class="nav-item" target="_blank">
                    <i class="fas fa-eye"></i> معاينة الموقع
                </a>
                <a href="logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                </a>
            </nav>
        </aside>

        <!-- المحتوى الرئيسي -->
        <div class="main-content">
            <header class="top-bar">
                <div class="top-bar-content">
                    <h1><?php echo $page_title ?? 'لوحة التحكم'; ?></h1>
                    <div class="user-info">
                        <span>مرحباً، <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></span>
                    </div>
                </div>
            </header>

            <main class="content-area">
