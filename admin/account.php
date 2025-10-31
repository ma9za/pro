<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

$errors = [];
$success = '';

// جلب معلومات المستخدم الحالي
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // تحديث المعلومات الشخصية
    if ($action === 'update_profile') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');

        // التحقق من البيانات
        if (empty($username) || strlen($username) < 3) {
            $errors[] = 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'البريد الإلكتروني غير صحيح';
        }

        // التحقق من عدم تكرار اسم المستخدم (إذا تم تغييره)
        if ($username !== $user['username']) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $_SESSION['user_id']]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'اسم المستخدم مستخدم بالفعل';
            }
        }

        // التحقق من عدم تكرار البريد الإلكتروني (إذا تم تغييره)
        if ($email !== $user['email']) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'البريد الإلكتروني مستخدم بالفعل';
            }
        }

        if (empty($errors)) {
            try {
                $stmt = $db->prepare("
                    UPDATE users
                    SET username = ?, email = ?, full_name = ?
                    WHERE id = ?
                ");
                $stmt->execute([$username, $email, $full_name, $_SESSION['user_id']]);

                // تحديث الجلسة
                $_SESSION['username'] = $username;
                $_SESSION['full_name'] = $full_name;

                // إعادة جلب المعلومات
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();

                $success = 'تم تحديث المعلومات بنجاح';
            } catch (PDOException $e) {
                $errors[] = 'فشل تحديث المعلومات: ' . $e->getMessage();
            }
        }
    }

    // تغيير كلمة المرور
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $new_password_confirm = $_POST['new_password_confirm'] ?? '';

        // التحقق من كلمة المرور الحالية
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = 'كلمة المرور الحالية غير صحيحة';
        }

        // التحقق من كلمة المرور الجديدة
        if (empty($new_password) || strlen($new_password) < 6) {
            $errors[] = 'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل';
        }

        if ($new_password !== $new_password_confirm) {
            $errors[] = 'كلمات المرور الجديدة غير متطابقة';
        }

        if (empty($errors)) {
            try {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);

                $success = 'تم تغيير كلمة المرور بنجاح';
            } catch (PDOException $e) {
                $errors[] = 'فشل تغيير كلمة المرور: ' . $e->getMessage();
            }
        }
    }
}

$page_title = 'حسابي';
include __DIR__ . '/includes/header.php';
?>

<style>
.account-sections {
    display: grid;
    gap: 2rem;
}

.account-section {
    background: white;
    border-radius: 10px;
    padding: 2rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.section-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--border-color);
}

.section-header i {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.section-header h3 {
    color: var(--heading-color);
    margin: 0;
}

.user-info-card {
    display: flex;
    gap: 2rem;
    align-items: start;
    padding: 1.5rem;
    background: var(--light-color);
    border-radius: 10px;
    margin-bottom: 2rem;
}

.user-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
    font-weight: bold;
    flex-shrink: 0;
}

.user-details h4 {
    color: var(--heading-color);
    margin-bottom: 0.5rem;
}

.user-meta {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    color: var(--text-color);
    font-size: 0.9rem;
}

.user-meta span {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.password-strength {
    margin-top: 0.5rem;
    font-size: 0.85rem;
}

.strength-bar {
    height: 5px;
    background: var(--border-color);
    border-radius: 5px;
    margin-top: 0.5rem;
    overflow: hidden;
}

.strength-fill {
    height: 100%;
    width: 0%;
    transition: all 0.3s;
    border-radius: 5px;
}

.strength-weak {
    width: 33%;
    background: var(--danger-color);
}

.strength-medium {
    width: 66%;
    background: var(--warning-color);
}

.strength-strong {
    width: 100%;
    background: var(--success-color);
}

@media (max-width: 768px) {
    .user-info-card {
        flex-direction: column;
        text-align: center;
    }

    .user-avatar {
        margin: 0 auto;
    }
}
</style>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<div class="page-header">
    <h2><i class="fas fa-user-circle"></i> حسابي</h2>
</div>

<!-- معلومات المستخدم الحالية -->
<div class="user-info-card">
    <div class="user-avatar">
        <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
    </div>
    <div class="user-details">
        <h4><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></h4>
        <div class="user-meta">
            <span><i class="fas fa-user"></i> @<?php echo htmlspecialchars($user['username']); ?></span>
            <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></span>
            <span><i class="fas fa-calendar"></i> عضو منذ: <?php echo date('Y-m-d', strtotime($user['created_at'])); ?></span>
        </div>
    </div>
</div>

<div class="account-sections">
    <!-- قسم المعلومات الشخصية -->
    <div class="account-section">
        <div class="section-header">
            <i class="fas fa-id-card"></i>
            <h3>المعلومات الشخصية</h3>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="update_profile">

            <div class="form-group">
                <label for="username">اسم المستخدم <span class="required">*</span></label>
                <input type="text" id="username" name="username"
                       value="<?php echo htmlspecialchars($user['username']); ?>"
                       required minlength="3">
                <small>اسم المستخدم الخاص بك لتسجيل الدخول</small>
            </div>

            <div class="form-group">
                <label for="email">البريد الإلكتروني <span class="required">*</span></label>
                <input type="email" id="email" name="email"
                       value="<?php echo htmlspecialchars($user['email']); ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="full_name">الاسم الكامل</label>
                <input type="text" id="full_name" name="full_name"
                       value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> حفظ التغييرات
                </button>
            </div>
        </form>
    </div>

    <!-- قسم تغيير كلمة المرور -->
    <div class="account-section">
        <div class="section-header">
            <i class="fas fa-lock"></i>
            <h3>تغيير كلمة المرور</h3>
        </div>

        <form method="POST" id="passwordForm">
            <input type="hidden" name="action" value="change_password">

            <div class="form-group">
                <label for="current_password">كلمة المرور الحالية <span class="required">*</span></label>
                <input type="password" id="current_password" name="current_password" required>
                <small>أدخل كلمة المرور الحالية للتأكيد</small>
            </div>

            <div class="form-group">
                <label for="new_password">كلمة المرور الجديدة <span class="required">*</span></label>
                <input type="password" id="new_password" name="new_password" required minlength="6">
                <small>6 أحرف على الأقل</small>
                <div class="password-strength">
                    <div id="strength-text"></div>
                    <div class="strength-bar">
                        <div id="strength-fill" class="strength-fill"></div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="new_password_confirm">تأكيد كلمة المرور الجديدة <span class="required">*</span></label>
                <input type="password" id="new_password_confirm" name="new_password_confirm" required minlength="6">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-key"></i> تغيير كلمة المرور
                </button>
            </div>
        </form>
    </div>

    <!-- معلومات الأمان -->
    <div class="account-section">
        <div class="section-header">
            <i class="fas fa-shield-alt"></i>
            <h3>معلومات الأمان</h3>
        </div>

        <div style="display: grid; gap: 1rem;">
            <div style="padding: 1rem; background: var(--light-color); border-radius: 8px;">
                <h4 style="color: var(--heading-color); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                    نصائح للأمان
                </h4>
                <ul style="margin: 0; padding-right: 1.5rem; color: var(--text-color);">
                    <li>استخدم كلمة مرور قوية تحتوي على أحرف وأرقام ورموز</li>
                    <li>لا تشارك كلمة المرور مع أي شخص</li>
                    <li>قم بتغيير كلمة المرور بشكل دوري</li>
                    <li>استخدم متصفح آمن ومحدث</li>
                    <li>لا تسجل الدخول من أجهزة عامة</li>
                </ul>
            </div>

            <div style="padding: 1rem; background: #e0f2fe; border: 1px solid #0ea5e9; border-radius: 8px; color: #075985;">
                <h4 style="margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-info-circle"></i>
                    معلومة
                </h4>
                <p style="margin: 0;">
                    آخر تسجيل دخول: اليوم
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// Password strength checker
const newPasswordInput = document.getElementById('new_password');
const strengthText = document.getElementById('strength-text');
const strengthFill = document.getElementById('strength-fill');

newPasswordInput.addEventListener('input', function() {
    const password = this.value;
    let strength = 0;
    let text = '';
    let className = '';

    if (password.length >= 6) strength++;
    if (password.length >= 10) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z\d]/.test(password)) strength++;

    if (strength <= 2) {
        text = 'ضعيفة';
        className = 'strength-weak';
    } else if (strength <= 3) {
        text = 'متوسطة';
        className = 'strength-medium';
    } else {
        text = 'قوية';
        className = 'strength-strong';
    }

    strengthText.textContent = 'قوة كلمة المرور: ' + text;
    strengthFill.className = 'strength-fill ' + className;
});

// Password confirmation validation
const confirmPasswordInput = document.getElementById('new_password_confirm');
const passwordForm = document.getElementById('passwordForm');

passwordForm.addEventListener('submit', function(e) {
    if (newPasswordInput.value !== confirmPasswordInput.value) {
        e.preventDefault();
        alert('كلمات المرور غير متطابقة');
        confirmPasswordInput.focus();
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
