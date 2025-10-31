<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

// جلب الحقول المخصصة
$stmt = $db->query("SELECT * FROM project_custom_fields ORDER BY display_order ASC, id ASC");
$custom_fields = $stmt->fetchAll();

$message = '';
$message_type = '';
$errors = [];

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

    // التحقق من الحقول المخصصة المطلوبة
    foreach ($custom_fields as $field) {
        if ($field['is_required'] == 1) {
            $field_value = $_POST['custom_field_' . $field['id']] ?? '';
            if (empty(trim($field_value))) {
                $errors[] = 'الحقل "' . $field['field_label'] . '" مطلوب';
            }
        }
    }

    // رفع الصورة
    $image_filename = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadImage($_FILES['image']);
        if ($upload_result['success']) {
            $image_filename = $upload_result['filename'];
        } else {
            $errors[] = $upload_result['message'];
        }
    }

    // إذا لم توجد أخطاء، إضافة المشروع
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("INSERT INTO projects (title, description, image, project_url, github_url, category, technologies, display_order, is_featured) VALUES (:title, :description, :image, :project_url, :github_url, :category, :technologies, :display_order, :is_featured)");

            $stmt->execute([
                'title' => $title,
                'description' => $description,
                'image' => $image_filename,
                'project_url' => $project_url,
                'github_url' => $github_url,
                'category' => $category,
                'technologies' => $technologies,
                'display_order' => $display_order,
                'is_featured' => $is_featured
            ]);

            // الحصول على ID المشروع المُضاف
            $project_id = $db->lastInsertId();

            // حفظ قيم الحقول المخصصة
            foreach ($custom_fields as $field) {
                $field_value = $_POST['custom_field_' . $field['id']] ?? '';
                if (!empty(trim($field_value))) {
                    $stmt = $db->prepare("
                        INSERT INTO project_field_values (project_id, field_id, field_value)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$project_id, $field['id'], trim($field_value)]);
                }
            }

            redirect('projects.php');
        } catch (PDOException $e) {
            $errors[] = 'فشل إضافة المشروع: ' . $e->getMessage();
        }
    }
}

$page_title = 'إضافة مشروع جديد';
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
    <h2>إضافة مشروع جديد</h2>
    <a href="projects.php" class="btn btn-secondary">
        <i class="fas fa-arrow-right"></i> العودة
    </a>
</div>

<form method="POST" action="" enctype="multipart/form-data" class="project-form">
    <div class="form-row">
        <div class="form-group">
            <label for="title">عنوان المشروع <span class="required">*</span></label>
            <input type="text" id="title" name="title"
                   value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                   required>
        </div>

        <div class="form-group">
            <label for="category">الفئة</label>
            <input type="text" id="category" name="category"
                   value="<?php echo htmlspecialchars($_POST['category'] ?? ''); ?>"
                   placeholder="مثال: تطوير ويب، تطبيق موبايل">
        </div>
    </div>

    <div class="form-group">
        <label for="description">الوصف</label>
        <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="project_url">رابط المشروع</label>
            <input type="url" id="project_url" name="project_url"
                   value="<?php echo htmlspecialchars($_POST['project_url'] ?? ''); ?>"
                   placeholder="https://example.com">
        </div>

        <div class="form-group">
            <label for="github_url">رابط GitHub</label>
            <input type="url" id="github_url" name="github_url"
                   value="<?php echo htmlspecialchars($_POST['github_url'] ?? ''); ?>"
                   placeholder="https://github.com/username/repo">
        </div>
    </div>

    <div class="form-group">
        <label for="technologies">التقنيات المستخدمة</label>
        <input type="text" id="technologies" name="technologies"
               value="<?php echo htmlspecialchars($_POST['technologies'] ?? ''); ?>"
               placeholder="PHP, MySQL, JavaScript, CSS (افصل بفواصل)">
        <small>افصل بين التقنيات بفاصلة</small>
    </div>

    <div class="form-group">
        <label for="image">صورة المشروع</label>
        <input type="file" id="image" name="image" accept="image/*">
        <small>الأنواع المسموحة: JPG, PNG, GIF, WebP (الحد الأقصى: 5MB)</small>
    </div>

    <?php if (!empty($custom_fields)): ?>
        <div class="form-section" style="background: #f0f7ff; border: 1px solid #0ea5e9; margin: 2rem 0;">
            <h3 style="color: #075985; margin-bottom: 1.5rem;">
                <i class="fas fa-th-list"></i> الحقول المخصصة
            </h3>
            <div class="form-row">
                <?php foreach ($custom_fields as $field): ?>
                    <div class="form-group">
                        <label for="custom_field_<?php echo $field['id']; ?>">
                            <?php echo htmlspecialchars($field['field_label']); ?>
                            <?php if ($field['is_required']): ?>
                                <span class="required">*</span>
                            <?php endif; ?>
                        </label>

                        <?php if ($field['field_type'] === 'textarea'): ?>
                            <textarea
                                id="custom_field_<?php echo $field['id']; ?>"
                                name="custom_field_<?php echo $field['id']; ?>"
                                rows="4"
                                <?php echo $field['is_required'] ? 'required' : ''; ?>
                            ><?php echo htmlspecialchars($_POST['custom_field_' . $field['id']] ?? ''); ?></textarea>

                        <?php elseif ($field['field_type'] === 'select'): ?>
                            <select
                                id="custom_field_<?php echo $field['id']; ?>"
                                name="custom_field_<?php echo $field['id']; ?>"
                                <?php echo $field['is_required'] ? 'required' : ''; ?>
                            >
                                <option value="">-- اختر --</option>
                                <?php
                                $options = explode("\n", $field['field_options']);
                                foreach ($options as $option) {
                                    $option = trim($option);
                                    if (!empty($option)) {
                                        $selected = ($_POST['custom_field_' . $field['id']] ?? '') === $option ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($option) . '" ' . $selected . '>' . htmlspecialchars($option) . '</option>';
                                    }
                                }
                                ?>
                            </select>

                        <?php else: ?>
                            <input
                                type="<?php echo htmlspecialchars($field['field_type']); ?>"
                                id="custom_field_<?php echo $field['id']; ?>"
                                name="custom_field_<?php echo $field['id']; ?>"
                                value="<?php echo htmlspecialchars($_POST['custom_field_' . $field['id']] ?? ''); ?>"
                                <?php echo $field['is_required'] ? 'required' : ''; ?>
                            >
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="form-row">
        <div class="form-group">
            <label for="display_order">ترتيب العرض</label>
            <input type="number" id="display_order" name="display_order"
                   value="<?php echo htmlspecialchars($_POST['display_order'] ?? '0'); ?>"
                   min="0">
            <small>الأرقام الأقل تظهر أولاً</small>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_featured"
                       <?php echo isset($_POST['is_featured']) ? 'checked' : ''; ?>>
                مشروع مميز
            </label>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> حفظ المشروع
        </button>
        <a href="projects.php" class="btn btn-secondary">إلغاء</a>
    </div>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>
