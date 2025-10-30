<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

$message = '';
$message_type = '';

// حذف مشروع
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // جلب معلومات المشروع لحذف الصورة
    $stmt = $db->prepare("SELECT image FROM projects WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $project = $stmt->fetch();

    if ($project) {
        // حذف الصورة
        if (!empty($project['image'])) {
            deleteImage($project['image']);
        }

        // حذف المشروع من قاعدة البيانات
        $stmt = $db->prepare("DELETE FROM projects WHERE id = :id");
        if ($stmt->execute(['id' => $id])) {
            $message = 'تم حذف المشروع بنجاح';
            $message_type = 'success';
        } else {
            $message = 'فشل حذف المشروع';
            $message_type = 'error';
        }
    }
}

// جلب جميع المشاريع
$stmt = $db->query("SELECT * FROM projects ORDER BY display_order ASC, created_at DESC");
$projects = $stmt->fetchAll();

$page_title = 'إدارة المشاريع';
include __DIR__ . '/includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="page-header">
    <h2>المشاريع (<?php echo count($projects); ?>)</h2>
    <a href="add_project.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> إضافة مشروع جديد
    </a>
</div>

<?php if (count($projects) > 0): ?>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>الصورة</th>
                    <th>العنوان</th>
                    <th>الفئة</th>
                    <th>التقنيات</th>
                    <th>مميز</th>
                    <th>التاريخ</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $project): ?>
                    <tr>
                        <td>
                            <?php if (!empty($project['image'])): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($project['image']); ?>"
                                     alt="<?php echo htmlspecialchars($project['title']); ?>"
                                     class="table-image">
                            <?php else: ?>
                                <div class="no-image">لا توجد صورة</div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($project['title']); ?></td>
                        <td><?php echo htmlspecialchars($project['category'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($project['technologies'] ?? '-'); ?></td>
                        <td>
                            <?php if ($project['is_featured']): ?>
                                <span class="badge badge-success">نعم</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">لا</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('Y-m-d', strtotime($project['created_at'])); ?></td>
                        <td class="actions">
                            <a href="edit_project.php?id=<?php echo $project['id']; ?>"
                               class="btn btn-sm btn-primary" title="تعديل">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="?action=delete&id=<?php echo $project['id']; ?>"
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('هل أنت متأكد من حذف هذا المشروع؟')"
                               title="حذف">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="empty-state">
        <i class="fas fa-folder-open"></i>
        <p>لا توجد مشاريع حالياً</p>
        <a href="add_project.php" class="btn btn-primary">إضافة أول مشروع</a>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
