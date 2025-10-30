<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

$message = '';
$message_type = '';
$errors = [];

// التحقق من وجود معرف المشروع
if (!isset($_GET['id'])) {
    redirect('projects.php');
}

$project_id = intval($_GET['id']);

// جلب معلومات المشروع
$stmt = $db->prepare("SELECT * FROM projects WHERE id = :id");
$stmt->execute(['id' => $project_id]);
$project = $stmt->fetch();

if (!$project) {
    redirect('projects.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // جمع البيانات
    $title = cleanInput($_POST['title'] ?? '');
    $description = cleanInput($_POST['description'] ?? '');
    $project_url = cleanInput($_POST['project_url'] ?? '');
    $github_url = cleanInput($_POST['github_url'] ?? '');
    $category = cleanInput($_POST['category'] ?? '');
    $technologies = cleanInput($_POST['technologies'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    // التحقق من البيانات
    if (empty($title)) {
        $errors[] = 'عنوان المشروع مطلوب';
    }

    // رفع صورة جديدة إذا تم تحديدها
    $image_filename = $project['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadImage($_FILES['image']);
        if ($upload_result['success']) {
            // حذف الصورة القديمة
            if (!empty($project['image'])) {
                deleteImage($project['image']);
            }
            $image_filename = $upload_result['filename'];
        } else {
            $errors[] = $upload_result['message'];
        }
    }

    // إذا لم توجد أخطاء، تحديث المشروع
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("UPDATE projects SET title = :title, description = :description, image = :image, project_url = :project_url, github_url = :github_url, category = :category, technologies = :technologies, display_order = :display_order, is_featured = :is_featured WHERE id = :id");

            $stmt->execute([
                'title' => $title,
                'description' => $description,
                'image' => $image_filename,
                'project_url' => $project_url,
                'github_url' => $github_url,
                'category' => $category,
                'technologies' => $technologies,
                'display_order' => $display_order,
                'is_featured' => $is_featured,
                'id' => $project_id
            ]);

            redirect('projects.php');
        } catch (PDOException $e) {
            $errors[] = 'فشل تحديث المشروع: ' . $e->getMessage();
        }
    }

    // تحديث البيانات المعروضة
    $project = array_merge($project, $_POST);
}

$page_title = 'تعديل المشروع';
include __DIR__ . '/includes/header.php';
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="page-header">
    <h2>تعديل المشروع</h2>
    <a href="projects.php" class="btn btn-secondary">
        <i class="fas fa-arrow-right"></i> العودة
    </a>
</div>

<form method="POST" action="" enctype="multipart/form-data" class="project-form">
    <div class="form-row">
        <div class="form-group">
            <label for="title">عنوان المشروع <span class="required">*</span></label>
            <input type="text" id="title" name="title"
                   value="<?php echo htmlspecialchars($project['title']); ?>"
                   required>
        </div>

        <div class="form-group">
            <label for="category">الفئة</label>
            <input type="text" id="category" name="category"
                   value="<?php echo htmlspecialchars($project['category'] ?? ''); ?>"
                   placeholder="مثال: تطوير ويب، تطبيق موبايل">
        </div>
    </div>

    <div class="form-group">
        <label for="description">الوصف</label>
        <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars($project['description'] ?? ''); ?></textarea>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="project_url">رابط المشروع</label>
            <input type="url" id="project_url" name="project_url"
                   value="<?php echo htmlspecialchars($project['project_url'] ?? ''); ?>"
                   placeholder="https://example.com">
        </div>

        <div class="form-group">
            <label for="github_url">رابط GitHub</label>
            <input type="url" id="github_url" name="github_url"
                   value="<?php echo htmlspecialchars($project['github_url'] ?? ''); ?>"
                   placeholder="https://github.com/username/repo">
        </div>
    </div>

    <div class="form-group">
        <label for="technologies">التقنيات المستخدمة</label>
        <input type="text" id="technologies" name="technologies"
               value="<?php echo htmlspecialchars($project['technologies'] ?? ''); ?>"
               placeholder="PHP, MySQL, JavaScript, CSS (افصل بفواصل)">
        <small>افصل بين التقنيات بفاصلة</small>
    </div>

    <div class="form-group">
        <label for="image">صورة المشروع</label>
        <?php if (!empty($project['image'])): ?>
            <div class="current-image">
                <img src="../uploads/<?php echo htmlspecialchars($project['image']); ?>"
                     alt="Current image" style="max-width: 200px; margin-bottom: 10px;">
                <p>الصورة الحالية</p>
            </div>
        <?php endif; ?>
        <input type="file" id="image" name="image" accept="image/*">
        <small>اترك الحقل فارغاً للاحتفاظ بالصورة الحالية</small>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="display_order">ترتيب العرض</label>
            <input type="number" id="display_order" name="display_order"
                   value="<?php echo htmlspecialchars($project['display_order'] ?? '0'); ?>"
                   min="0">
            <small>الأرقام الأقل تظهر أولاً</small>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_featured"
                       <?php echo $project['is_featured'] ? 'checked' : ''; ?>>
                مشروع مميز
            </label>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> حفظ التغييرات
        </button>
        <a href="projects.php" class="btn btn-secondary">إلغاء</a>
    </div>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>
