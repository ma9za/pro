<?php
require_once __DIR__ . '/../config/config.php';

// التحقق من التثبيت
require_once __DIR__ . '/../includes/install_check.php';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// إذا كان المستخدم مسجل دخول بالفعل، أعد توجيهه
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

// رسالة نجاح التثبيت
if (isset($_GET['installed']) && $_GET['installed'] == '1') {
    $success = 'تم تثبيت الموقع بنجاح! يمكنك الآن تسجيل الدخول';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'يرجى إدخال اسم المستخدم وكلمة المرور';
    } else {
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // تسجيل دخول ناجح
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];

            // إعادة توجيه إلى لوحة التحكم
            redirect('dashboard.php');
        } else {
            $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - لوحة التحكم</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <i class="fas fa-lock"></i>
                <h1>لوحة التحكم</h1>
                <p>يرجى تسجيل الدخول للمتابعة</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> اسم المستخدم
                    </label>
                    <input type="text" id="username" name="username"
                           value="<?php echo htmlspecialchars($username ?? ''); ?>"
                           required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-key"></i> كلمة المرور
                    </label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
                </button>
            </form>

            <div class="login-footer">
                <a href="../index.php">
                    <i class="fas fa-home"></i> العودة للصفحة الرئيسية
                </a>
            </div>
        </div>
    </div>
</body>
</html>
