<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

// معالجة الإجراءات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_link') {
        $platform_name = trim($_POST['platform_name'] ?? '');
        $icon_class = trim($_POST['icon_class'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $display_order = intval($_POST['display_order'] ?? 0);
        $is_visible = isset($_POST['is_visible']) ? 1 : 0;

        if (!empty($platform_name) && !empty($icon_class) && !empty($url)) {
            $stmt = $db->prepare("
                INSERT INTO social_links
                (platform_name, icon_class, url, display_order, is_visible)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$platform_name, $icon_class, $url, $display_order, $is_visible]);
            $_SESSION['success'] = 'تم إضافة الرابط بنجاح';
        } else {
            $_SESSION['error'] = 'جميع الحقول مطلوبة';
        }
    }

    if ($action === 'update_link') {
        $link_id = intval($_POST['link_id'] ?? 0);
        $platform_name = trim($_POST['platform_name'] ?? '');
        $icon_class = trim($_POST['icon_class'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $display_order = intval($_POST['display_order'] ?? 0);
        $is_visible = isset($_POST['is_visible']) ? 1 : 0;

        if ($link_id && !empty($platform_name) && !empty($icon_class) && !empty($url)) {
            $stmt = $db->prepare("
                UPDATE social_links
                SET platform_name = ?, icon_class = ?, url = ?, display_order = ?, is_visible = ?
                WHERE id = ?
            ");
            $stmt->execute([$platform_name, $icon_class, $url, $display_order, $is_visible, $link_id]);
            $_SESSION['success'] = 'تم تحديث الرابط بنجاح';
        }
    }

    if ($action === 'delete_link') {
        $link_id = intval($_POST['link_id'] ?? 0);
        if ($link_id) {
            $stmt = $db->prepare("DELETE FROM social_links WHERE id = ?");
            $stmt->execute([$link_id]);
            $_SESSION['success'] = 'تم حذف الرابط بنجاح';
        }
    }

    if ($action === 'toggle_visibility') {
        $link_id = intval($_POST['link_id'] ?? 0);
        if ($link_id) {
            $stmt = $db->prepare("UPDATE social_links SET is_visible = 1 - is_visible WHERE id = ?");
            $stmt->execute([$link_id]);
            $_SESSION['success'] = 'تم تحديث حالة الظهور';
        }
    }

    header('Location: social_links.php');
    exit();
}

// جلب جميع الروابط
$stmt = $db->query("SELECT * FROM social_links ORDER BY display_order ASC, id ASC");
$social_links = $stmt->fetchAll();

$page_title = 'روابط التواصل الاجتماعي';
include __DIR__ . '/includes/header.php';
?>

<style>
.social-links-grid {
    display: grid;
    gap: 1rem;
    margin-bottom: 2rem;
}

.social-link-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    display: grid;
    grid-template-columns: auto 1fr auto auto;
    gap: 1.5rem;
    align-items: center;
    transition: all 0.3s;
}

.social-link-card:hover {
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
}

.social-link-card.hidden {
    opacity: 0.5;
}

.link-icon {
    width: 60px;
    height: 60px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.8rem;
}

.link-info h4 {
    color: var(--heading-color);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.link-meta {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    font-size: 0.9rem;
    color: var(--text-color);
}

.link-meta span {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.link-meta a {
    color: var(--primary-color);
    text-decoration: none;
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.link-visibility {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.toggle-switch {
    position: relative;
    width: 50px;
    height: 26px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.4s;
    border-radius: 26px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: 0.4s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: var(--success-color);
}

input:checked + .toggle-slider:before {
    transform: translateX(24px);
}

.link-actions {
    display: flex;
    gap: 0.5rem;
}

.icon-picker {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
    gap: 0.5rem;
    max-height: 300px;
    overflow-y: auto;
    margin-top: 1rem;
    padding: 1rem;
    background: var(--light-color);
    border-radius: 8px;
}

.icon-option {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 1.5rem;
}

.icon-option:hover {
    border-color: var(--primary-color);
    background: var(--primary-color);
    color: white;
}

.icon-option.selected {
    border-color: var(--primary-color);
    background: var(--primary-color);
    color: white;
}

@media (max-width: 768px) {
    .social-link-card {
        grid-template-columns: auto 1fr;
        gap: 1rem;
    }

    .link-visibility,
    .link-actions {
        grid-column: 1 / -1;
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

<div class="page-header">
    <h2><i class="fas fa-share-alt"></i> روابط التواصل الاجتماعي</h2>
    <button onclick="openModal('addLinkModal')" class="btn btn-primary">
        <i class="fas fa-plus"></i> إضافة رابط جديد
    </button>
</div>

<div class="alert" style="background: #e0f2fe; border: 1px solid #0ea5e9; color: #075985;">
    <p><strong><i class="fas fa-info-circle"></i> معلومة:</strong> روابط التواصل الاجتماعي تظهر في صفحة التواصل على الموقع الرئيسي.</p>
</div>

<?php if (empty($social_links)): ?>
    <div class="empty-state">
        <i class="fas fa-share-alt"></i>
        <p>لا توجد روابط تواصل اجتماعي</p>
        <button onclick="openModal('addLinkModal')" class="btn btn-primary">
            <i class="fas fa-plus"></i> إضافة أول رابط
        </button>
    </div>
<?php else: ?>
    <div class="social-links-grid">
        <?php foreach ($social_links as $link): ?>
            <div class="social-link-card <?php echo $link['is_visible'] ? '' : 'hidden'; ?>">
                <div class="link-icon">
                    <i class="<?php echo htmlspecialchars($link['icon_class']); ?>"></i>
                </div>

                <div class="link-info">
                    <h4>
                        <?php echo htmlspecialchars($link['platform_name']); ?>
                        <?php if (!$link['is_visible']): ?>
                            <span class="badge badge-secondary">مخفي</span>
                        <?php endif; ?>
                    </h4>
                    <div class="link-meta">
                        <span><i class="fas fa-link"></i> <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank"><?php echo htmlspecialchars($link['url']); ?></a></span>
                        <span><i class="fas fa-sort-numeric-up"></i> ترتيب: <?php echo $link['display_order']; ?></span>
                    </div>
                </div>

                <div class="link-visibility">
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="action" value="toggle_visibility">
                        <input type="hidden" name="link_id" value="<?php echo $link['id']; ?>">
                        <label class="toggle-switch">
                            <input type="checkbox" <?php echo $link['is_visible'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span class="toggle-slider"></span>
                        </label>
                    </form>
                </div>

                <div class="link-actions">
                    <button onclick='editLink(<?php echo json_encode($link); ?>)' class="btn btn-primary btn-sm">
                        <i class="fas fa-edit"></i> تعديل
                    </button>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا الرابط؟')">
                        <input type="hidden" name="action" value="delete_link">
                        <input type="hidden" name="link_id" value="<?php echo $link['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash"></i> حذف
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Modal إضافة رابط جديد -->
<div id="addLinkModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> إضافة رابط تواصل اجتماعي</h3>
            <button class="modal-close" onclick="closeModal('addLinkModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="add_link">

                <div class="form-group">
                    <label>اسم المنصة <span class="required">*</span></label>
                    <input type="text" name="platform_name" required placeholder="مثال: GitHub">
                </div>

                <div class="form-group">
                    <label>أيقونة Font Awesome <span class="required">*</span></label>
                    <input type="text" name="icon_class" id="add_icon_class" required placeholder="fab fa-github" readonly>
                    <small>اختر أيقونة من القائمة أدناه</small>

                    <div class="icon-picker">
                        <div class="icon-option" onclick="selectIcon('fab fa-github', 'add')"><i class="fab fa-github"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-linkedin', 'add')"><i class="fab fa-linkedin"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-twitter', 'add')"><i class="fab fa-twitter"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-facebook', 'add')"><i class="fab fa-facebook"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-instagram', 'add')"><i class="fab fa-instagram"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-youtube', 'add')"><i class="fab fa-youtube"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-tiktok', 'add')"><i class="fab fa-tiktok"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-telegram', 'add')"><i class="fab fa-telegram"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-whatsapp', 'add')"><i class="fab fa-whatsapp"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-discord', 'add')"><i class="fab fa-discord"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-slack', 'add')"><i class="fab fa-slack"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-reddit', 'add')"><i class="fab fa-reddit"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-pinterest', 'add')"><i class="fab fa-pinterest"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-snapchat', 'add')"><i class="fab fa-snapchat"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-medium', 'add')"><i class="fab fa-medium"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-dribbble', 'add')"><i class="fab fa-dribbble"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-behance', 'add')"><i class="fab fa-behance"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-codepen', 'add')"><i class="fab fa-codepen"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-stack-overflow', 'add')"><i class="fab fa-stack-overflow"></i></div>
                        <div class="icon-option" onclick="selectIcon('fas fa-envelope', 'add')"><i class="fas fa-envelope"></i></div>
                        <div class="icon-option" onclick="selectIcon('fas fa-globe', 'add')"><i class="fas fa-globe"></i></div>
                        <div class="icon-option" onclick="selectIcon('fas fa-link', 'add')"><i class="fas fa-link"></i></div>
                    </div>
                </div>

                <div class="form-group">
                    <label>الرابط <span class="required">*</span></label>
                    <input type="url" name="url" required placeholder="https://github.com/username">
                </div>

                <div class="form-group">
                    <label>ترتيب العرض</label>
                    <input type="number" name="display_order" value="0" min="0">
                    <small>الروابط ذات الترتيب الأقل تظهر أولاً</small>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="is_visible" checked style="width: auto;">
                        <span>ظاهر</span>
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> حفظ الرابط
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addLinkModal')">
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal تعديل رابط -->
<div id="editLinkModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> تعديل رابط التواصل</h3>
            <button class="modal-close" onclick="closeModal('editLinkModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" id="editLinkForm">
                <input type="hidden" name="action" value="update_link">
                <input type="hidden" name="link_id" id="edit_link_id">

                <div class="form-group">
                    <label>اسم المنصة <span class="required">*</span></label>
                    <input type="text" name="platform_name" id="edit_platform_name" required>
                </div>

                <div class="form-group">
                    <label>أيقونة Font Awesome <span class="required">*</span></label>
                    <input type="text" name="icon_class" id="edit_icon_class" required readonly>
                    <small>اختر أيقونة من القائمة أدناه</small>

                    <div class="icon-picker">
                        <div class="icon-option" onclick="selectIcon('fab fa-github', 'edit')"><i class="fab fa-github"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-linkedin', 'edit')"><i class="fab fa-linkedin"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-twitter', 'edit')"><i class="fab fa-twitter"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-facebook', 'edit')"><i class="fab fa-facebook"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-instagram', 'edit')"><i class="fab fa-instagram"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-youtube', 'edit')"><i class="fab fa-youtube"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-tiktok', 'edit')"><i class="fab fa-tiktok"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-telegram', 'edit')"><i class="fab fa-telegram"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-whatsapp', 'edit')"><i class="fab fa-whatsapp"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-discord', 'edit')"><i class="fab fa-discord"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-slack', 'edit')"><i class="fab fa-slack"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-reddit', 'edit')"><i class="fab fa-reddit"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-pinterest', 'edit')"><i class="fab fa-pinterest"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-snapchat', 'edit')"><i class="fab fa-snapchat"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-medium', 'edit')"><i class="fab fa-medium"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-dribbble', 'edit')"><i class="fab fa-dribbble"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-behance', 'edit')"><i class="fab fa-behance"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-codepen', 'edit')"><i class="fab fa-codepen"></i></div>
                        <div class="icon-option" onclick="selectIcon('fab fa-stack-overflow', 'edit')"><i class="fab fa-stack-overflow"></i></div>
                        <div class="icon-option" onclick="selectIcon('fas fa-envelope', 'edit')"><i class="fas fa-envelope"></i></div>
                        <div class="icon-option" onclick="selectIcon('fas fa-globe', 'edit')"><i class="fas fa-globe"></i></div>
                        <div class="icon-option" onclick="selectIcon('fas fa-link', 'edit')"><i class="fas fa-link"></i></div>
                    </div>
                </div>

                <div class="form-group">
                    <label>الرابط <span class="required">*</span></label>
                    <input type="url" name="url" id="edit_url" required>
                </div>

                <div class="form-group">
                    <label>ترتيب العرض</label>
                    <input type="number" name="display_order" id="edit_display_order" min="0">
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="is_visible" id="edit_is_visible" style="width: auto;">
                        <span>ظاهر</span>
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> حفظ التغييرات
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editLinkModal')">
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

function selectIcon(iconClass, mode) {
    // إزالة التحديد من جميع الأيقونات
    document.querySelectorAll('.icon-option').forEach(el => el.classList.remove('selected'));

    // تحديد الأيقونة المختارة
    event.currentTarget.classList.add('selected');

    // تحديث الحقل
    if (mode === 'add') {
        document.getElementById('add_icon_class').value = iconClass;
    } else {
        document.getElementById('edit_icon_class').value = iconClass;
    }
}

function editLink(link) {
    document.getElementById('edit_link_id').value = link.id;
    document.getElementById('edit_platform_name').value = link.platform_name;
    document.getElementById('edit_icon_class').value = link.icon_class;
    document.getElementById('edit_url').value = link.url;
    document.getElementById('edit_display_order').value = link.display_order;
    document.getElementById('edit_is_visible').checked = link.is_visible == 1;

    // تحديد الأيقونة المختارة في المحرر
    document.querySelectorAll('#editLinkModal .icon-option').forEach(el => {
        const iconEl = el.querySelector('i');
        if (iconEl && iconEl.className === link.icon_class) {
            el.classList.add('selected');
        }
    });

    openModal('editLinkModal');
}

// إغلاق عند الضغط خارج Modal
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
