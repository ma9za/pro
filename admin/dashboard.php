<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

// جلب إحصائيات
$stmt = $db->query("SELECT COUNT(*) as total FROM projects");
$total_projects = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM projects WHERE is_featured = 1");
$featured_projects = $stmt->fetch()['total'];

$page_title = 'لوحة التحكم';
include __DIR__ . '/includes/header.php';
?>

<div class="dashboard-stats">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-briefcase"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $total_projects; ?></h3>
            <p>إجمالي المشاريع</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-star"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $featured_projects; ?></h3>
            <p>المشاريع المميزة</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-user"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo htmlspecialchars($_SESSION['username']); ?></h3>
            <p>المستخدم الحالي</p>
        </div>
    </div>
</div>

<div class="dashboard-actions">
    <h2>الإجراءات السريعة</h2>
    <div class="action-cards">
        <a href="projects.php" class="action-card">
            <i class="fas fa-list"></i>
            <h3>عرض المشاريع</h3>
            <p>إدارة جميع المشاريع</p>
        </a>

        <a href="add_project.php" class="action-card">
            <i class="fas fa-plus"></i>
            <h3>إضافة مشروع جديد</h3>
            <p>أضف مشروع جديد للموقع</p>
        </a>

        <a href="settings.php" class="action-card">
            <i class="fas fa-cog"></i>
            <h3>إعدادات الموقع</h3>
            <p>تعديل معلومات الموقع</p>
        </a>

        <a href="../index.php" class="action-card" target="_blank">
            <i class="fas fa-eye"></i>
            <h3>معاينة الموقع</h3>
            <p>عرض الموقع الرئيسي</p>
        </a>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
