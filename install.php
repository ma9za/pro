<?php
require_once __DIR__ . '/config/config.php';

// إذا كان الموقع مثبت بالفعل، أعد التوجيه
if (file_exists(INSTALL_LOCK)) {
    header('Location: index.php');
    exit();
}

$message = '';
$message_type = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // جمع البيانات
    $admin_username = trim($_POST['admin_username'] ?? '');
    $admin_password = $_POST['admin_password'] ?? '';
    $admin_password_confirm = $_POST['admin_password_confirm'] ?? '';
    $admin_email = trim($_POST['admin_email'] ?? '');
    $admin_fullname = trim($_POST['admin_fullname'] ?? '');

    $site_title = trim($_POST['site_title'] ?? 'موقعي التعريفي');
    $site_description = trim($_POST['site_description'] ?? 'مطور ومصمم مواقع ويب');
    $about_me = trim($_POST['about_me'] ?? '');

    // التحقق من البيانات
    if (empty($admin_username)) {
        $errors[] = 'اسم المستخدم مطلوب';
    } elseif (strlen($admin_username) < 3) {
        $errors[] = 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل';
    }

    if (empty($admin_password)) {
        $errors[] = 'كلمة المرور مطلوبة';
    } elseif (strlen($admin_password) < 6) {
        $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    }

    if ($admin_password !== $admin_password_confirm) {
        $errors[] = 'كلمات المرور غير متطابقة';
    }

    if (empty($admin_email) || !filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صحيح';
    }

    // إذا لم توجد أخطاء، ابدأ التثبيت
    if (empty($errors)) {
        try {
            require_once __DIR__ . '/config/database.php';
            $db = Database::getInstance()->getConnection();

            // إنشاء الجداول
            $sql = "
            -- جدول المستخدمين
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                full_name TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            -- جدول المشاريع
            CREATE TABLE IF NOT EXISTS projects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                description TEXT,
                image TEXT,
                project_url TEXT,
                github_url TEXT,
                category TEXT,
                technologies TEXT,
                display_order INTEGER DEFAULT 0,
                is_featured INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            -- جدول إعدادات الموقع
            CREATE TABLE IF NOT EXISTS site_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                site_title TEXT DEFAULT 'موقعي التعريفي',
                site_description TEXT,
                about_me TEXT,
                profile_image TEXT,
                email TEXT,
                phone TEXT,
                github_url TEXT,
                linkedin_url TEXT,
                twitter_url TEXT,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            ";

            // تنفيذ الجداول
            $db->exec($sql);

            // إضافة المستخدم الإداري
            $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password, email, full_name) VALUES (:username, :password, :email, :full_name)");
            $stmt->execute([
                'username' => $admin_username,
                'password' => $hashed_password,
                'email' => $admin_email,
                'full_name' => $admin_fullname
            ]);

            // إضافة إعدادات الموقع
            $stmt = $db->prepare("INSERT INTO site_settings (site_title, site_description, about_me) VALUES (:site_title, :site_description, :about_me)");
            $stmt->execute([
                'site_title' => $site_title,
                'site_description' => $site_description,
                'about_me' => $about_me
            ]);

            // إنشاء مجلد uploads إذا لم يكن موجوداً
            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0755, true);
            }

            // إنشاء ملف القفل
            file_put_contents(INSTALL_LOCK, date('Y-m-d H:i:s'));

            // إعادة التوجيه إلى صفحة تسجيل الدخول
            header('Location: admin/login.php?installed=1');
            exit();
        } catch (Exception $e) {
            $errors[] = 'فشل التثبيت: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تثبيت الموقع</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .install-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
        }
        .install-container {
            max-width: 700px;
            margin: 0 auto;
        }
        .install-box {
            background: white;
            border-radius: 15px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .install-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .install-header i {
            font-size: 4rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }
        .install-header h1 {
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        .install-header p {
            color: #6b7280;
        }
        .form-section {
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        .form-section h3 {
            color: #1f2937;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding: 0 1rem;
        }
        .step {
            flex: 1;
            text-align: center;
            padding: 0.5rem;
            position: relative;
        }
        .step::after {
            content: '';
            position: absolute;
            top: 20px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #e5e7eb;
            z-index: -1;
        }
        .step:last-child::after {
            display: none;
        }
        .step-number {
            display: inline-block;
            width: 40px;
            height: 40px;
            line-height: 40px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #6b7280;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .step.active .step-number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .step-label {
            font-size: 0.85rem;
            color: #6b7280;
        }
        .requirements {
            background: #e0f2fe;
            border: 1px solid #0ea5e9;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 2rem;
        }
        .requirements h4 {
            color: #0369a1;
            margin-bottom: 0.5rem;
        }
        .requirements ul {
            margin: 0;
            padding-right: 1.5rem;
            color: #075985;
        }
    </style>
</head>
<body class="install-page">
    <div class="install-container">
        <div class="install-box">
            <div class="install-header">
                <i class="fas fa-download"></i>
                <h1>تثبيت الموقع التعريفي</h1>
                <p>أهلاً بك! دعنا نقوم بإعداد موقعك في بضع خطوات بسيطة</p>
            </div>

            <div class="step-indicator">
                <div class="step active">
                    <div class="step-number">1</div>
                    <div class="step-label">المتطلبات</div>
                </div>
                <div class="step active">
                    <div class="step-number">2</div>
                    <div class="step-label">الإعدادات</div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-label">التثبيت</div>
                </div>
            </div>

            <div class="requirements">
                <h4><i class="fas fa-check-circle"></i> المتطلبات متوفرة</h4>
                <ul>
                    <li>✓ PHP <?php echo phpversion(); ?></li>
                    <li>✓ SQLite <?php echo SQLite3::version()['versionString']; ?></li>
                    <li>✓ PDO متوفر</li>
                </ul>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul style="margin: 0; padding-right: 1.5rem;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-section">
                    <h3><i class="fas fa-user-shield"></i> حساب المسؤول</h3>

                    <div class="form-group">
                        <label for="admin_username">اسم المستخدم <span class="required">*</span></label>
                        <input type="text" id="admin_username" name="admin_username"
                               value="<?php echo htmlspecialchars($admin_username ?? ''); ?>"
                               required minlength="3">
                    </div>

                    <div class="form-group">
                        <label for="admin_fullname">الاسم الكامل</label>
                        <input type="text" id="admin_fullname" name="admin_fullname"
                               value="<?php echo htmlspecialchars($admin_fullname ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="admin_email">البريد الإلكتروني <span class="required">*</span></label>
                        <input type="email" id="admin_email" name="admin_email"
                               value="<?php echo htmlspecialchars($admin_email ?? ''); ?>"
                               required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="admin_password">كلمة المرور <span class="required">*</span></label>
                            <input type="password" id="admin_password" name="admin_password"
                                   required minlength="6">
                            <small>6 أحرف على الأقل</small>
                        </div>

                        <div class="form-group">
                            <label for="admin_password_confirm">تأكيد كلمة المرور <span class="required">*</span></label>
                            <input type="password" id="admin_password_confirm" name="admin_password_confirm"
                                   required minlength="6">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-globe"></i> معلومات الموقع</h3>

                    <div class="form-group">
                        <label for="site_title">عنوان الموقع <span class="required">*</span></label>
                        <input type="text" id="site_title" name="site_title"
                               value="<?php echo htmlspecialchars($site_title ?? 'موقعي التعريفي'); ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="site_description">وصف الموقع</label>
                        <input type="text" id="site_description" name="site_description"
                               value="<?php echo htmlspecialchars($site_description ?? 'مطور ومصمم مواقع ويب'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="about_me">نبذة عني</label>
                        <textarea id="about_me" name="about_me" rows="4"><?php echo htmlspecialchars($about_me ?? ''); ?></textarea>
                        <small>يمكنك تعديل هذه المعلومات لاحقاً من لوحة التحكم</small>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="font-size: 1.1rem; padding: 1rem;">
                    <i class="fas fa-rocket"></i> تثبيت الموقع الآن
                </button>
            </form>
        </div>
    </div>
</body>
</html>
