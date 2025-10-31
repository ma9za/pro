<?php
require_once __DIR__ . '/config/config.php';

// إذا كان الموقع مثبت بالفعل، أعد التوجيه
if (file_exists(INSTALL_LOCK)) {
    header('Location: index.php');
    exit();
}

$step = $_GET['step'] ?? 1;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 2) {
        // معالجة بيانات التثبيت
        $admin_username = trim($_POST['admin_username'] ?? '');
        $admin_password = $_POST['admin_password'] ?? '';
        $admin_password_confirm = $_POST['admin_password_confirm'] ?? '';
        $admin_email = trim($_POST['admin_email'] ?? '');
        $admin_fullname = trim($_POST['admin_fullname'] ?? '');

        $site_title = trim($_POST['site_title'] ?? 'Portfolio');
        $site_description = trim($_POST['site_description'] ?? '');
        $about_me = trim($_POST['about_me'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        // إعدادات SMTP (اختيارية)
        $setup_smtp_now = isset($_POST['setup_smtp_now']) ? 1 : 0;
        $smtp_host = trim($_POST['smtp_host'] ?? '');
        $smtp_port = intval($_POST['smtp_port'] ?? 587);
        $smtp_username = trim($_POST['smtp_username'] ?? '');
        $smtp_password = $_POST['smtp_password'] ?? '';
        $smtp_encryption = $_POST['smtp_encryption'] ?? 'tls';
        $smtp_from_email = trim($_POST['smtp_from_email'] ?? $admin_email);
        $smtp_from_name = trim($_POST['smtp_from_name'] ?? $site_title);

        // التحقق من البيانات
        if (empty($admin_username) || strlen($admin_username) < 3) {
            $errors[] = 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل';
        }

        if (empty($admin_password) || strlen($admin_password) < 6) {
            $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
        }

        if ($admin_password !== $admin_password_confirm) {
            $errors[] = 'كلمات المرور غير متطابقة';
        }

        if (empty($admin_email) || !filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'البريد الإلكتروني غير صحيح';
        }

        if ($setup_smtp_now && empty($smtp_host)) {
            $errors[] = 'خادم SMTP مطلوب إذا اخترت الإعداد الآن';
        }

        // إذا لم توجد أخطاء، ابدأ التثبيت
        if (empty($errors)) {
            try {
                require_once __DIR__ . '/config/database.php';
                $db = Database::getInstance()->getConnection();

                // إنشاء الجداول من ملف database.sql
                $sql_file = file_get_contents(__DIR__ . '/database.sql');

                // تنظيف SQL من التعليقات
                $sql_commands = array_filter(
                    explode(';', $sql_file),
                    function($cmd) {
                        $cmd = trim($cmd);
                        return !empty($cmd) && !str_starts_with($cmd, '--');
                    }
                );

                // تنفيذ كل أمر SQL
                foreach ($sql_commands as $command) {
                    $command = trim($command);
                    if (!empty($command) && !str_starts_with($command, '--')) {
                        $db->exec($command);
                    }
                }

                // إضافة المستخدم الإداري
                $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, password, email, full_name) VALUES (?, ?, ?, ?)");
                $stmt->execute([$admin_username, $hashed_password, $admin_email, $admin_fullname]);

                // إضافة إعدادات الموقع مع SMTP
                $stmt = $db->prepare("
                    INSERT INTO site_settings (
                        site_title, site_description, about_me, email, phone,
                        smtp_host, smtp_port, smtp_username, smtp_password,
                        smtp_encryption, smtp_from_email, smtp_from_name, smtp_enabled
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $site_title, $site_description, $about_me, $email, $phone,
                    $smtp_host, $smtp_port, $smtp_username, $smtp_password,
                    $smtp_encryption, $smtp_from_email, $smtp_from_name, $setup_smtp_now
                ]);

                // إنشاء مجلد uploads إذا لم يكن موجوداً
                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0755, true);
                }

                // إنشاء ملف القفل
                file_put_contents(INSTALL_LOCK, date('Y-m-d H:i:s'));

                // إعادة التوجيه إلى صفحة النجاح
                header('Location: ?step=3');
                exit();
            } catch (Exception $e) {
                $errors[] = 'فشل التثبيت: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تثبيت Portfolio - الخطوة <?php echo $step; ?></title>
    <link rel="stylesheet" href="assets/css/modern-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary-bg);
            padding: 2rem;
        }

        .install-container {
            width: 100%;
            max-width: 900px;
        }

        .install-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        }

        .install-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .install-header i {
            font-size: 4rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .install-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .install-header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .steps-indicator {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 3rem;
        }

        .step-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--border-color);
            transition: all 0.3s;
        }

        .step-dot.active {
            background: var(--accent-primary);
            box-shadow: 0 0 20px var(--glow-color);
        }

        .step-dot.completed {
            background: var(--accent-secondary);
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section h3 {
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-section h3 i {
            color: var(--accent-primary);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 500;
        }

        .required {
            color: var(--danger-color);
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.875rem;
            background: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-primary);
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 20px var(--glow-color);
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-group small {
            display: block;
            margin-top: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .checkbox-group:hover {
            border-color: var(--accent-primary);
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            cursor: pointer;
        }

        .smtp-section {
            display: none;
            margin-top: 1rem;
            padding: 1.5rem;
            background: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
        }

        .smtp-section.active {
            display: block;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--danger-color);
            color: var(--danger-color);
        }

        .alert ul {
            margin: 0;
            padding-right: 1.5rem;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            flex: 1;
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.7rem;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: var(--primary-bg);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px var(--glow-color);
        }

        .btn-secondary {
            background: transparent;
            color: var(--accent-primary);
            border: 2px solid var(--accent-primary);
        }

        .success-message {
            text-align: center;
            padding: 3rem;
        }

        .success-message i {
            font-size: 5rem;
            color: var(--accent-primary);
            margin-bottom: 1.5rem;
        }

        .success-message h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .success-message p {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .install-card {
                padding: 2rem 1.5rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Particles Background -->
    <div id="particles-js"></div>

    <div class="install-container">
        <div class="install-card">
            <?php if ($step == 1): ?>
                <!-- Welcome Step -->
                <div class="install-header">
                    <i class="fas fa-rocket"></i>
                    <h1>مرحباً بك!</h1>
                    <p>دعنا نبدأ بإعداد موقعك التعريفي الاحترافي</p>
                </div>

                <div class="steps-indicator">
                    <div class="step-dot active"></div>
                    <div class="step-dot"></div>
                    <div class="step-dot"></div>
                </div>

                <div style="text-align: center; padding: 2rem 0;">
                    <h3 style="margin-bottom: 1.5rem;">المتطلبات متوفرة ✓</h3>
                    <ul style="list-style: none; padding: 0; margin-bottom: 2rem;">
                        <li style="padding: 0.5rem 0; color: var(--accent-primary);"><i class="fas fa-check"></i> PHP <?php echo phpversion(); ?></li>
                        <li style="padding: 0.5rem 0; color: var(--accent-primary);"><i class="fas fa-check"></i> SQLite متوفر</li>
                        <li style="padding: 0.5rem 0; color: var(--accent-primary);"><i class="fas fa-check"></i> PDO مُفعّل</li>
                    </ul>
                </div>

                <div class="btn-group">
                    <a href="?step=2" class="btn btn-primary">
                        <span>ابدأ التثبيت</span>
                        <i class="fas fa-arrow-left"></i>
                    </a>
                </div>

            <?php elseif ($step == 2): ?>
                <!-- Installation Step -->
                <div class="install-header">
                    <i class="fas fa-cog"></i>
                    <h1>إعداد الموقع</h1>
                    <p>املأ المعلومات التالية لإكمال التثبيت</p>
                </div>

                <div class="steps-indicator">
                    <div class="step-dot completed"></div>
                    <div class="step-dot active"></div>
                    <div class="step-dot"></div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <strong><i class="fas fa-exclamation-circle"></i> هناك أخطاء:</strong>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <!-- Admin Account -->
                    <div class="form-section">
                        <h3><i class="fas fa-user-shield"></i> حساب المسؤول</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>اسم المستخدم <span class="required">*</span></label>
                                <input type="text" name="admin_username" required minlength="3"
                                       value="<?php echo htmlspecialchars($_POST['admin_username'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>الاسم الكامل</label>
                                <input type="text" name="admin_fullname"
                                       value="<?php echo htmlspecialchars($_POST['admin_fullname'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>البريد الإلكتروني <span class="required">*</span></label>
                            <input type="email" name="admin_email" required
                                   value="<?php echo htmlspecialchars($_POST['admin_email'] ?? ''); ?>">
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>كلمة المرور <span class="required">*</span></label>
                                <input type="password" name="admin_password" required minlength="6">
                                <small>6 أحرف على الأقل</small>
                            </div>
                            <div class="form-group">
                                <label>تأكيد كلمة المرور <span class="required">*</span></label>
                                <input type="password" name="admin_password_confirm" required minlength="6">
                            </div>
                        </div>
                    </div>

                    <!-- Site Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-globe"></i> معلومات الموقع</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>عنوان الموقع <span class="required">*</span></label>
                                <input type="text" name="site_title" required
                                       value="<?php echo htmlspecialchars($_POST['site_title'] ?? 'Portfolio'); ?>">
                            </div>
                            <div class="form-group">
                                <label>وصف الموقع</label>
                                <input type="text" name="site_description"
                                       value="<?php echo htmlspecialchars($_POST['site_description'] ?? ''); ?>"
                                       placeholder="Developer & Designer">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>نبذة عني</label>
                            <textarea name="about_me" rows="4"><?php echo htmlspecialchars($_POST['about_me'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>البريد الإلكتروني للتواصل</label>
                                <input type="email" name="email"
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? $_POST['admin_email'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>رقم الهاتف</label>
                                <input type="tel" name="phone"
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- SMTP Settings (Optional) -->
                    <div class="form-section">
                        <h3><i class="fas fa-envelope"></i> إعدادات البريد الإلكتروني (اختياري)</h3>

                        <div class="checkbox-group" onclick="this.querySelector('input').click()">
                            <input type="checkbox" name="setup_smtp_now" id="setup_smtp_now"
                                   onclick="event.stopPropagation(); document.getElementById('smtp-section').classList.toggle('active');"
                                   <?php echo isset($_POST['setup_smtp_now']) ? 'checked' : ''; ?>>
                            <label for="setup_smtp_now" style="margin: 0; cursor: pointer;">
                                <strong>إعداد SMTP الآن</strong>
                                <small style="display: block; margin-top: 0.25rem;">يمكنك إعداده لاحقاً من لوحة التحكم</small>
                            </label>
                        </div>

                        <div class="smtp-section <?php echo isset($_POST['setup_smtp_now']) ? 'active' : ''; ?>" id="smtp-section">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>خادم SMTP</label>
                                    <input type="text" name="smtp_host"
                                           placeholder="smtp.gmail.com"
                                           value="<?php echo htmlspecialchars($_POST['smtp_host'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>المنفذ</label>
                                    <input type="number" name="smtp_port"
                                           value="<?php echo htmlspecialchars($_POST['smtp_port'] ?? '587'); ?>">
                                </div>
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>اسم المستخدم SMTP</label>
                                    <input type="text" name="smtp_username"
                                           value="<?php echo htmlspecialchars($_POST['smtp_username'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>كلمة مرور SMTP</label>
                                    <input type="password" name="smtp_password">
                                </div>
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>نوع التشفير</label>
                                    <select name="smtp_encryption">
                                        <option value="tls" <?php echo ($_POST['smtp_encryption'] ?? 'tls') == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                        <option value="ssl" <?php echo ($_POST['smtp_encryption'] ?? '') == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>البريد المُرسِل</label>
                                    <input type="email" name="smtp_from_email"
                                           value="<?php echo htmlspecialchars($_POST['smtp_from_email'] ?? $_POST['admin_email'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="btn-group">
                        <a href="?step=1" class="btn btn-secondary">
                            <i class="fas fa-arrow-right"></i>
                            <span>السابق</span>
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <span>إكمال التثبيت</span>
                            <i class="fas fa-check"></i>
                        </button>
                    </div>
                </form>

            <?php elseif ($step == 3): ?>
                <!-- Success Step -->
                <div class="install-header">
                    <i class="fas fa-check-circle" style="color: var(--accent-primary);"></i>
                    <h1>تم التثبيت بنجاح!</h1>
                    <p>موقعك جاهز الآن للاستخدام</p>
                </div>

                <div class="steps-indicator">
                    <div class="step-dot completed"></div>
                    <div class="step-dot completed"></div>
                    <div class="step-dot active"></div>
                </div>

                <div class="success-message">
                    <p>تم إنشاء قاعدة البيانات وإعداد جميع الجداول بنجاح</p>
                    <div class="btn-group" style="max-width: 600px; margin: 2rem auto 0;">
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-home"></i>
                            <span>الصفحة الرئيسية</span>
                        </a>
                        <a href="admin/login.php" class="btn btn-secondary">
                            <i class="fas fa-shield-alt"></i>
                            <span>لوحة التحكم</span>
                        </a>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
        // Particles.js Configuration
        particlesJS('particles-js', {
            particles: {
                number: { value: 50 },
                color: { value: '#00ff41' },
                shape: { type: 'circle' },
                opacity: { value: 0.3 },
                size: { value: 3 },
                line_linked: {
                    enable: true,
                    distance: 150,
                    color: '#00ff41',
                    opacity: 0.2,
                    width: 1
                },
                move: { enable: true, speed: 2 }
            }
        });
    </script>
</body>
</html>
