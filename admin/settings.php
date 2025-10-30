<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

$message = '';
$message_type = '';
$errors = [];

// جلب الإعدادات الحالية
$stmt = $db->query("SELECT * FROM site_settings LIMIT 1");
$settings = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // جمع البيانات
    $site_title = cleanInput($_POST['site_title'] ?? '');
    $site_description = cleanInput($_POST['site_description'] ?? '');
    $about_me = cleanInput($_POST['about_me'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $phone = cleanInput($_POST['phone'] ?? '');
    $github_url = cleanInput($_POST['github_url'] ?? '');
    $linkedin_url = cleanInput($_POST['linkedin_url'] ?? '');
    $twitter_url = cleanInput($_POST['twitter_url'] ?? '');

    // التحقق من البيانات
    if (empty($site_title)) {
        $errors[] = 'عنوان الموقع مطلوب';
    }

    // رفع صورة الملف الشخصي إذا تم تحديدها
    $profile_image = $settings['profile_image'] ?? '';
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadImage($_FILES['profile_image']);
        if ($upload_result['success']) {
            // حذف الصورة القديمة
            if (!empty($settings['profile_image'])) {
                deleteImage($settings['profile_image']);
            }
            $profile_image = $upload_result['filename'];
        } else {
            $errors[] = $upload_result['message'];
        }
    }

    // إذا لم توجد أخطاء، تحديث الإعدادات
    if (empty($errors)) {
        try {
            if ($settings) {
                // تحديث الإعدادات
                $stmt = $db->prepare("UPDATE site_settings SET site_title = :site_title, site_description = :site_description, about_me = :about_me, profile_image = :profile_image, email = :email, phone = :phone, github_url = :github_url, linkedin_url = :linkedin_url, twitter_url = :twitter_url WHERE id = :id");
                $stmt->execute([
                    'site_title' => $site_title,
                    'site_description' => $site_description,
                    'about_me' => $about_me,
                    'profile_image' => $profile_image,
                    'email' => $email,
                    'phone' => $phone,
                    'github_url' => $github_url,
                    'linkedin_url' => $linkedin_url,
                    'twitter_url' => $twitter_url,
                    'id' => $settings['id']
                ]);
            } else {
                // إنشاء إعدادات جديدة
                $stmt = $db->prepare("INSERT INTO site_settings (site_title, site_description, about_me, profile_image, email, phone, github_url, linkedin_url, twitter_url) VALUES (:site_title, :site_description, :about_me, :profile_image, :email, :phone, :github_url, :linkedin_url, :twitter_url)");
                $stmt->execute([
                    'site_title' => $site_title,
                    'site_description' => $site_description,
                    'about_me' => $about_me,
                    'profile_image' => $profile_image,
                    'email' => $email,
                    'phone' => $phone,
                    'github_url' => $github_url,
                    'linkedin_url' => $linkedin_url,
                    'twitter_url' => $twitter_url
                ]);
            }

            $message = 'تم حفظ الإعدادات بنجاح';
            $message_type = 'success';

            // إعادة جلب الإعدادات
            $stmt = $db->query("SELECT * FROM site_settings LIMIT 1");
            $settings = $stmt->fetch();
        } catch (PDOException $e) {
            $errors[] = 'فشل حفظ الإعدادات: ' . $e->getMessage();
        }
    }
}

$page_title = 'إعدادات الموقع';
include __DIR__ . '/includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

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
    <h2>إعدادات الموقع</h2>
</div>

<form method="POST" action="" enctype="multipart/form-data" class="settings-form">
    <div class="form-section">
        <h3>المعلومات الأساسية</h3>

        <div class="form-group">
            <label for="site_title">عنوان الموقع <span class="required">*</span></label>
            <input type="text" id="site_title" name="site_title"
                   value="<?php echo htmlspecialchars($settings['site_title'] ?? ''); ?>"
                   required>
        </div>

        <div class="form-group">
            <label for="site_description">وصف الموقع</label>
            <input type="text" id="site_description" name="site_description"
                   value="<?php echo htmlspecialchars($settings['site_description'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="about_me">عني</label>
            <textarea id="about_me" name="about_me" rows="6"><?php echo htmlspecialchars($settings['about_me'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label for="profile_image">صورة الملف الشخصي</label>
            <?php if (!empty($settings['profile_image'])): ?>
                <div class="current-image">
                    <img src="../uploads/<?php echo htmlspecialchars($settings['profile_image']); ?>"
                         alt="Profile" style="max-width: 200px; margin-bottom: 10px;">
                    <p>الصورة الحالية</p>
                </div>
            <?php endif; ?>
            <input type="file" id="profile_image" name="profile_image" accept="image/*">
        </div>
    </div>

    <div class="form-section">
        <h3>معلومات الاتصال</h3>

        <div class="form-row">
            <div class="form-group">
                <label for="email">البريد الإلكتروني</label>
                <input type="email" id="email" name="email"
                       value="<?php echo htmlspecialchars($settings['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="phone">رقم الهاتف</label>
                <input type="tel" id="phone" name="phone"
                       value="<?php echo htmlspecialchars($settings['phone'] ?? ''); ?>">
            </div>
        </div>
    </div>

    <div class="form-section">
        <h3>روابط التواصل الاجتماعي</h3>

        <div class="form-group">
            <label for="github_url">رابط GitHub</label>
            <input type="url" id="github_url" name="github_url"
                   value="<?php echo htmlspecialchars($settings['github_url'] ?? ''); ?>"
                   placeholder="https://github.com/username">
        </div>

        <div class="form-group">
            <label for="linkedin_url">رابط LinkedIn</label>
            <input type="url" id="linkedin_url" name="linkedin_url"
                   value="<?php echo htmlspecialchars($settings['linkedin_url'] ?? ''); ?>"
                   placeholder="https://linkedin.com/in/username">
        </div>

        <div class="form-group">
            <label for="twitter_url">رابط Twitter</label>
            <input type="url" id="twitter_url" name="twitter_url"
                   value="<?php echo htmlspecialchars($settings['twitter_url'] ?? ''); ?>"
                   placeholder="https://twitter.com/username">
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> حفظ الإعدادات
        </button>
    </div>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>
