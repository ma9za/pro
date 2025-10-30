<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

// معالجة الإجراءات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $message_id = $_POST['message_id'] ?? 0;

    if ($action === 'mark_read' && $message_id) {
        $stmt = $db->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
        $stmt->execute([$message_id]);
        $_SESSION['success'] = 'تم تعليم الرسالة كمقروءة';
    }

    if ($action === 'mark_unread' && $message_id) {
        $stmt = $db->prepare("UPDATE messages SET is_read = 0 WHERE id = ?");
        $stmt->execute([$message_id]);
        $_SESSION['success'] = 'تم تعليم الرسالة كغير مقروءة';
    }

    if ($action === 'mark_replied' && $message_id) {
        $stmt = $db->prepare("UPDATE messages SET is_replied = 1 WHERE id = ?");
        $stmt->execute([$message_id]);
        $_SESSION['success'] = 'تم تعليم الرسالة كمجاب عليها';
    }

    if ($action === 'delete' && $message_id) {
        $stmt = $db->prepare("DELETE FROM messages WHERE id = ?");
        $stmt->execute([$message_id]);
        $_SESSION['success'] = 'تم حذف الرسالة بنجاح';
    }

    if ($action === 'send_reply' && $message_id) {
        $reply_message = trim($_POST['reply_message'] ?? '');

        if (!empty($reply_message)) {
            // جلب معلومات الرسالة
            $stmt = $db->prepare("SELECT * FROM messages WHERE id = ?");
            $stmt->execute([$message_id]);
            $message = $stmt->fetch();

            // جلب إعدادات SMTP
            $stmt = $db->query("SELECT * FROM site_settings LIMIT 1");
            $settings = $stmt->fetch();

            if ($settings && $settings['smtp_enabled'] == 1) {
                require_once __DIR__ . '/../includes/phpmailer/SimpleMailer.php';

                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = $settings['smtp_host'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $settings['smtp_username'];
                    $mail->Password = $settings['smtp_password'];
                    $mail->SMTPSecure = $settings['smtp_encryption'];
                    $mail->Port = $settings['smtp_port'];
                    $mail->CharSet = 'UTF-8';

                    $mail->setFrom($settings['smtp_from_email'], $settings['smtp_from_name']);
                    $mail->addAddress($message['email'], $message['name']);

                    $mail->isHTML(true);
                    $mail->Subject = 'رد على: ' . $message['subject'];
                    $mail->Body = nl2br(htmlspecialchars($reply_message));

                    $mail->send();

                    // تعليم الرسالة كمجاب عليها
                    $stmt = $db->prepare("UPDATE messages SET is_replied = 1, is_read = 1 WHERE id = ?");
                    $stmt->execute([$message_id]);

                    $_SESSION['success'] = 'تم إرسال الرد بنجاح';
                } catch (Exception $e) {
                    $_SESSION['error'] = 'فشل إرسال الرد: ' . $e->getMessage();
                }
            } else {
                $_SESSION['error'] = 'SMTP غير مُفعّل. يرجى تفعيله من الإعدادات';
            }
        }
    }

    header('Location: messages.php');
    exit();
}

// الفلترة
$filter = $_GET['filter'] ?? 'all';
$where_clause = '';
if ($filter === 'unread') {
    $where_clause = 'WHERE is_read = 0';
} elseif ($filter === 'read') {
    $where_clause = 'WHERE is_read = 1';
} elseif ($filter === 'replied') {
    $where_clause = 'WHERE is_replied = 1';
}

// جلب الرسائل
$stmt = $db->query("SELECT * FROM messages $where_clause ORDER BY created_at DESC");
$messages = $stmt->fetchAll();

// إحصائيات
$stmt = $db->query("SELECT COUNT(*) as total FROM messages");
$total_messages = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM messages WHERE is_read = 0");
$unread_messages = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM messages WHERE is_replied = 1");
$replied_messages = $stmt->fetch()['total'];

// جلب إعدادات SMTP للتحقق من التفعيل
$stmt = $db->query("SELECT smtp_enabled FROM site_settings LIMIT 1");
$smtp_settings = $stmt->fetch();
$smtp_enabled = $smtp_settings ? $smtp_settings['smtp_enabled'] : 0;

$page_title = 'الرسائل';
include __DIR__ . '/includes/header.php';
?>

<style>
.messages-filters {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 0.75rem 1.5rem;
    border: 2px solid var(--border-color);
    background: white;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    font-family: 'Cairo', sans-serif;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-btn:hover {
    border-color: var(--primary-color);
    color: var(--primary-color);
}

.filter-btn.active {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.message-item {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    transition: all 0.3s;
    border-right: 4px solid transparent;
}

.message-item:hover {
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
}

.message-item.unread {
    border-right-color: var(--primary-color);
    background: #f0f7ff;
}

.message-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 1rem;
    gap: 1rem;
}

.message-info h4 {
    color: var(--heading-color);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.message-meta {
    display: flex;
    gap: 1.5rem;
    color: var(--text-color);
    font-size: 0.9rem;
    flex-wrap: wrap;
}

.message-meta span {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.message-badges {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.message-content {
    padding: 1rem;
    background: var(--light-color);
    border-radius: 8px;
    margin-bottom: 1rem;
}

.message-content h5 {
    color: var(--heading-color);
    margin-bottom: 0.5rem;
}

.message-content p {
    color: var(--text-color);
    line-height: 1.8;
    white-space: pre-wrap;
}

.message-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 1000;
    overflow-y: auto;
}

.modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

.modal-content {
    background: white;
    border-radius: 15px;
    width: 100%;
    max-width: 700px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    color: var(--heading-color);
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-color);
}

.modal-body {
    padding: 1.5rem;
}

.reply-form textarea {
    width: 100%;
    min-height: 200px;
    padding: 1rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-family: 'Cairo', sans-serif;
    margin-bottom: 1rem;
}

.mailto-option {
    background: var(--light-color);
    padding: 1rem;
    border-radius: 8px;
    margin-top: 1rem;
}

.mailto-option p {
    margin-bottom: 0.5rem;
    color: var(--text-color);
}

@media (max-width: 768px) {
    .message-header {
        flex-direction: column;
    }

    .message-meta {
        flex-direction: column;
        gap: 0.5rem;
    }

    .message-actions {
        flex-direction: column;
    }

    .message-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-error">
        <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<!-- إحصائيات -->
<div class="dashboard-stats">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-envelope"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $total_messages; ?></h3>
            <p>إجمالي الرسائل</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-envelope-open"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $unread_messages; ?></h3>
            <p>غير مقروءة</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-reply"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $replied_messages; ?></h3>
            <p>تم الرد عليها</p>
        </div>
    </div>
</div>

<!-- الفلاتر -->
<div class="messages-filters">
    <a href="?filter=all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
        <i class="fas fa-list"></i> الكل
    </a>
    <a href="?filter=unread" class="filter-btn <?php echo $filter === 'unread' ? 'active' : ''; ?>">
        <i class="fas fa-envelope"></i> غير مقروءة
    </a>
    <a href="?filter=read" class="filter-btn <?php echo $filter === 'read' ? 'active' : ''; ?>">
        <i class="fas fa-envelope-open"></i> مقروءة
    </a>
    <a href="?filter=replied" class="filter-btn <?php echo $filter === 'replied' ? 'active' : ''; ?>">
        <i class="fas fa-reply"></i> تم الرد
    </a>
</div>

<!-- قائمة الرسائل -->
<?php if (empty($messages)): ?>
    <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <p>لا توجد رسائل</p>
    </div>
<?php else: ?>
    <?php foreach ($messages as $message): ?>
        <div class="message-item <?php echo $message['is_read'] == 0 ? 'unread' : ''; ?>">
            <div class="message-header">
                <div class="message-info">
                    <h4>
                        <?php if ($message['is_read'] == 0): ?>
                            <i class="fas fa-circle" style="font-size: 0.5rem; color: var(--primary-color);"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($message['name']); ?>
                    </h4>
                    <div class="message-meta">
                        <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($message['email']); ?></span>
                        <span><i class="fas fa-clock"></i> <?php echo date('Y-m-d H:i', strtotime($message['created_at'])); ?></span>
                    </div>
                </div>
                <div class="message-badges">
                    <?php if ($message['is_read'] == 0): ?>
                        <span class="badge badge-primary">جديدة</span>
                    <?php endif; ?>
                    <?php if ($message['is_replied'] == 1): ?>
                        <span class="badge badge-success">تم الرد</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="message-content">
                <h5><?php echo htmlspecialchars($message['subject']); ?></h5>
                <p><?php echo htmlspecialchars(mb_substr($message['message'], 0, 200)); ?><?php echo mb_strlen($message['message']) > 200 ? '...' : ''; ?></p>
            </div>

            <div class="message-actions">
                <button onclick="viewMessage(<?php echo $message['id']; ?>)" class="btn btn-primary btn-sm">
                    <i class="fas fa-eye"></i> عرض كامل
                </button>

                <button onclick="openReplyModal(<?php echo $message['id']; ?>, '<?php echo htmlspecialchars($message['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($message['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($message['subject'], ENT_QUOTES); ?>')" class="btn btn-primary btn-sm">
                    <i class="fas fa-reply"></i> رد
                </button>

                <?php if ($message['is_read'] == 0): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="mark_read">
                        <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                        <button type="submit" class="btn btn-secondary btn-sm">
                            <i class="fas fa-check"></i> تعليم كمقروءة
                        </button>
                    </form>
                <?php else: ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="mark_unread">
                        <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                        <button type="submit" class="btn btn-secondary btn-sm">
                            <i class="fas fa-envelope"></i> تعليم كغير مقروءة
                        </button>
                    </form>
                <?php endif; ?>

                <form method="POST" style="display: inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذه الرسالة؟')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="fas fa-trash"></i> حذف
                    </button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Modal عرض الرسالة كاملة -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-envelope-open"></i> تفاصيل الرسالة</h3>
            <button class="modal-close" onclick="closeModal('viewModal')">&times;</button>
        </div>
        <div class="modal-body" id="viewModalBody">
            <!-- سيتم ملؤه بـ JavaScript -->
        </div>
    </div>
</div>

<!-- Modal الرد على الرسالة -->
<div id="replyModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-reply"></i> الرد على الرسالة</h3>
            <button class="modal-close" onclick="closeModal('replyModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div id="replyInfo" style="background: var(--light-color); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                <!-- سيتم ملؤه بـ JavaScript -->
            </div>

            <?php if ($smtp_enabled): ?>
                <form method="POST" class="reply-form">
                    <input type="hidden" name="action" value="send_reply">
                    <input type="hidden" name="message_id" id="replyMessageId">

                    <div class="form-group">
                        <label>رسالتك:</label>
                        <textarea name="reply_message" required placeholder="اكتب ردك هنا..."></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> إرسال الرد
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('replyModal')">
                            إلغاء
                        </button>
                    </div>
                </form>

                <div class="mailto-option">
                    <p><strong>أو افتح في تطبيق البريد:</strong></p>
                    <a id="mailtoLink" href="#" class="btn btn-secondary btn-sm">
                        <i class="fas fa-external-link-alt"></i> فتح في تطبيق البريد
                    </a>
                </div>
            <?php else: ?>
                <div class="alert alert-error">
                    <p><strong>تنبيه:</strong> لم يتم تفعيل SMTP. يمكنك الرد عن طريق فتح تطبيق البريد مباشرة.</p>
                </div>
                <a id="mailtoLink" href="#" class="btn btn-primary">
                    <i class="fas fa-envelope"></i> فتح في تطبيق البريد
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// عرض الرسالة كاملة
function viewMessage(messageId) {
    fetch(`get_message.php?id=${messageId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const message = data.message;
                document.getElementById('viewModalBody').innerHTML = `
                    <div style="margin-bottom: 1.5rem;">
                        <h4 style="color: var(--heading-color); margin-bottom: 1rem;">${message.subject}</h4>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1rem; color: var(--text-color);">
                            <p><strong>من:</strong> ${message.name}</p>
                            <p><strong>البريد:</strong> ${message.email}</p>
                            <p><strong>التاريخ:</strong> ${message.created_at}</p>
                        </div>
                        <div style="background: var(--light-color); padding: 1.5rem; border-radius: 8px;">
                            <p style="white-space: pre-wrap; line-height: 1.8;">${message.message}</p>
                        </div>
                    </div>
                `;
                openModal('viewModal');
            }
        });
}

// فتح modal الرد
function openReplyModal(messageId, email, name, subject) {
    document.getElementById('replyMessageId').value = messageId;
    document.getElementById('replyInfo').innerHTML = `
        <p><strong>إلى:</strong> ${name} (${email})</p>
        <p><strong>رداً على:</strong> ${subject}</p>
    `;

    // تحديث رابط mailto
    const mailtoLink = `mailto:${email}?subject=رد على: ${encodeURIComponent(subject)}`;
    document.getElementById('mailtoLink').href = mailtoLink;

    openModal('replyModal');
}

// فتح وإغلاق Modals
function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

// إغلاق عند الضغط خارج Modal
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
