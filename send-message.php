<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير صحيحة']);
    exit();
}

// جمع البيانات وتنظيفها
$name = cleanInput($_POST['name'] ?? '');
$email = cleanInput($_POST['email'] ?? '');
$subject = cleanInput($_POST['subject'] ?? '');
$message = cleanInput($_POST['message'] ?? '');

// التحقق من البيانات
$errors = [];

if (empty($name)) {
    $errors[] = 'الاسم مطلوب';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'البريد الإلكتروني غير صحيح';
}

if (empty($subject)) {
    $errors[] = 'الموضوع مطلوب';
}

if (empty($message)) {
    $errors[] = 'الرسالة مطلوبة';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit();
}

try {
    $db = Database::getInstance()->getConnection();

    // حفظ الرسالة في قاعدة البيانات
    $stmt = $db->prepare("INSERT INTO messages (name, email, subject, message) VALUES (:name, :email, :subject, :message)");
    $stmt->execute([
        'name' => $name,
        'email' => $email,
        'subject' => $subject,
        'message' => $message
    ]);

    // جلب إعدادات SMTP
    $stmt = $db->query("SELECT * FROM site_settings LIMIT 1");
    $settings = $stmt->fetch();

    // إرسال البريد الإلكتروني إذا كان SMTP مُفعّل
    if ($settings && $settings['smtp_enabled'] == 1 && !empty($settings['smtp_host'])) {
        // استخدام PHPMailer
        require_once __DIR__ . '/includes/phpmailer/PHPMailer.php';
        require_once __DIR__ . '/includes/phpmailer/SMTP.php';
        require_once __DIR__ . '/includes/phpmailer/Exception.php';

        use PHPMailer\PHPMailer\PHPMailer;
        use PHPMailer\PHPMailer\Exception;

        $mail = new PHPMailer(true);

        try {
            // إعدادات الخادم
            $mail->isSMTP();
            $mail->Host       = $settings['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $settings['smtp_username'];
            $mail->Password   = $settings['smtp_password'];
            $mail->SMTPSecure = $settings['smtp_encryption'];
            $mail->Port       = $settings['smtp_port'];
            $mail->CharSet    = 'UTF-8';

            // المرسل والمستقبل
            $mail->setFrom($settings['smtp_from_email'], $settings['smtp_from_name'] ?? 'Portfolio Contact');
            $mail->addAddress($settings['email']); // البريد الذي ستصله الرسائل
            $mail->addReplyTo($email, $name);

            // المحتوى
            $mail->isHTML(true);
            $mail->Subject = 'رسالة جديدة من الموقع: ' . $subject;
            $mail->Body    = "
                <html dir='rtl'>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f4f4f4; }
                        .header { background: #00ff41; color: #0a0e27; padding: 20px; text-align: center; }
                        .content { background: white; padding: 30px; }
                        .field { margin-bottom: 15px; }
                        .label { font-weight: bold; color: #666; }
                        .value { color: #333; margin-top: 5px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>رسالة جديدة من موقعك</h2>
                        </div>
                        <div class='content'>
                            <div class='field'>
                                <div class='label'>الاسم:</div>
                                <div class='value'>{$name}</div>
                            </div>
                            <div class='field'>
                                <div class='label'>البريد الإلكتروني:</div>
                                <div class='value'>{$email}</div>
                            </div>
                            <div class='field'>
                                <div class='label'>الموضوع:</div>
                                <div class='value'>{$subject}</div>
                            </div>
                            <div class='field'>
                                <div class='label'>الرسالة:</div>
                                <div class='value'>" . nl2br($message) . "</div>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            ";
            $mail->AltBody = "الاسم: {$name}\nالبريد: {$email}\nالموضوع: {$subject}\n\nالرسالة:\n{$message}";

            $mail->send();
        } catch (Exception $e) {
            // فشل الإرسال، لكن الرسالة محفوظة في القاعدة
            error_log("Mailer Error: {$mail->ErrorInfo}");
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'تم إرسال رسالتك بنجاح! سنتواصل معك قريباً.'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ أثناء حفظ الرسالة. حاول مرة أخرى لاحقاً.'
    ]);
}
