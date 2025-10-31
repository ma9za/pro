<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

// معالجة الإجراءات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_field') {
        $field_name = trim($_POST['field_name'] ?? '');
        $field_label = trim($_POST['field_label'] ?? '');
        $field_type = $_POST['field_type'] ?? 'text';
        $field_options = trim($_POST['field_options'] ?? '');
        $is_required = isset($_POST['is_required']) ? 1 : 0;
        $display_order = intval($_POST['display_order'] ?? 0);

        if (!empty($field_name) && !empty($field_label)) {
            $stmt = $db->prepare("
                INSERT INTO project_custom_fields
                (field_name, field_label, field_type, field_options, is_required, display_order)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$field_name, $field_label, $field_type, $field_options, $is_required, $display_order]);
            $_SESSION['success'] = 'تم إضافة الحقل بنجاح';
        } else {
            $_SESSION['error'] = 'اسم الحقل والعنوان مطلوبان';
        }
    }

    if ($action === 'update_field') {
        $field_id = intval($_POST['field_id'] ?? 0);
        $field_name = trim($_POST['field_name'] ?? '');
        $field_label = trim($_POST['field_label'] ?? '');
        $field_type = $_POST['field_type'] ?? 'text';
        $field_options = trim($_POST['field_options'] ?? '');
        $is_required = isset($_POST['is_required']) ? 1 : 0;
        $display_order = intval($_POST['display_order'] ?? 0);

        if ($field_id && !empty($field_name) && !empty($field_label)) {
            $stmt = $db->prepare("
                UPDATE project_custom_fields
                SET field_name = ?, field_label = ?, field_type = ?,
                    field_options = ?, is_required = ?, display_order = ?
                WHERE id = ?
            ");
            $stmt->execute([$field_name, $field_label, $field_type, $field_options, $is_required, $display_order, $field_id]);
            $_SESSION['success'] = 'تم تحديث الحقل بنجاح';
        }
    }

    if ($action === 'delete_field') {
        $field_id = intval($_POST['field_id'] ?? 0);
        if ($field_id) {
            $stmt = $db->prepare("DELETE FROM project_custom_fields WHERE id = ?");
            $stmt->execute([$field_id]);
            $_SESSION['success'] = 'تم حذف الحقل بنجاح';
        }
    }

    header('Location: custom_fields.php');
    exit();
}

// جلب جميع الحقول المخصصة
$stmt = $db->query("SELECT * FROM project_custom_fields ORDER BY display_order ASC, id ASC");
$custom_fields = $stmt->fetchAll();

$page_title = 'الحقول المخصصة للمشاريع';
include __DIR__ . '/includes/header.php';
?>

<style>
.fields-grid {
    display: grid;
    gap: 1rem;
    margin-bottom: 2rem;
}

.field-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 1.5rem;
    align-items: center;
}

.field-handle {
    cursor: move;
    color: var(--text-color);
    font-size: 1.5rem;
}

.field-info h4 {
    color: var(--heading-color);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.field-meta {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    font-size: 0.9rem;
    color: var(--text-color);
}

.field-meta span {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.field-actions {
    display: flex;
    gap: 0.5rem;
}

.add-field-form {
    background: white;
    border-radius: 10px;
    padding: 2rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    margin-bottom: 2rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.field-types {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 0.5rem;
}

.field-type-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: var(--light-color);
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s;
}

.field-type-option:has(input:checked) {
    background: var(--primary-color);
    color: white;
}

.field-type-option input[type="radio"] {
    width: auto;
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
    max-width: 600px;
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

@media (max-width: 768px) {
    .field-card {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .field-actions {
        flex-direction: column;
    }

    .field-actions .btn {
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

<div class="page-header">
    <h2><i class="fas fa-th-list"></i> الحقول المخصصة للمشاريع</h2>
    <button onclick="openModal('addFieldModal')" class="btn btn-primary">
        <i class="fas fa-plus"></i> إضافة حقل جديد
    </button>
</div>

<div class="alert" style="background: #e0f2fe; border: 1px solid #0ea5e9; color: #075985;">
    <p><strong><i class="fas fa-info-circle"></i> معلومة:</strong> الحقول المخصصة تظهر تلقائياً في نموذج إضافة وتعديل المشاريع.</p>
</div>

<?php if (empty($custom_fields)): ?>
    <div class="empty-state">
        <i class="fas fa-th-list"></i>
        <p>لا توجد حقول مخصصة</p>
        <button onclick="openModal('addFieldModal')" class="btn btn-primary">
            <i class="fas fa-plus"></i> إضافة أول حقل
        </button>
    </div>
<?php else: ?>
    <div class="fields-grid">
        <?php foreach ($custom_fields as $field): ?>
            <div class="field-card">
                <div class="field-handle">
                    <i class="fas fa-grip-vertical"></i>
                </div>
                <div class="field-info">
                    <h4>
                        <?php echo htmlspecialchars($field['field_label']); ?>
                        <?php if ($field['is_required']): ?>
                            <span class="badge badge-danger">مطلوب</span>
                        <?php endif; ?>
                    </h4>
                    <div class="field-meta">
                        <span><i class="fas fa-code"></i> <code><?php echo htmlspecialchars($field['field_name']); ?></code></span>
                        <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($field['field_type']); ?></span>
                        <span><i class="fas fa-sort-numeric-up"></i> ترتيب: <?php echo $field['display_order']; ?></span>
                        <?php if (!empty($field['field_options'])): ?>
                            <span><i class="fas fa-list"></i> خيارات</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="field-actions">
                    <button onclick='editField(<?php echo json_encode($field); ?>)' class="btn btn-primary btn-sm">
                        <i class="fas fa-edit"></i> تعديل
                    </button>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا الحقل؟ سيتم حذف جميع القيم المرتبطة به.')">
                        <input type="hidden" name="action" value="delete_field">
                        <input type="hidden" name="field_id" value="<?php echo $field['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash"></i> حذف
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Modal إضافة حقل جديد -->
<div id="addFieldModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> إضافة حقل مخصص جديد</h3>
            <button class="modal-close" onclick="closeModal('addFieldModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="add_field">

                <div class="form-group">
                    <label>اسم الحقل (بالإنجليزية) <span class="required">*</span></label>
                    <input type="text" name="field_name" required pattern="[a-zA-Z_][a-zA-Z0-9_]*"
                           placeholder="مثال: github_stars">
                    <small>استخدم أحرف إنجليزية وأرقام و underscore فقط، يبدأ بحرف</small>
                </div>

                <div class="form-group">
                    <label>عنوان الحقل <span class="required">*</span></label>
                    <input type="text" name="field_label" required placeholder="مثال: عدد النجوم على GitHub">
                </div>

                <div class="form-group">
                    <label>نوع الحقل <span class="required">*</span></label>
                    <div class="field-types">
                        <label class="field-type-option">
                            <input type="radio" name="field_type" value="text" checked>
                            <span>نص قصير</span>
                        </label>
                        <label class="field-type-option">
                            <input type="radio" name="field_type" value="textarea">
                            <span>نص طويل</span>
                        </label>
                        <label class="field-type-option">
                            <input type="radio" name="field_type" value="url">
                            <span>رابط</span>
                        </label>
                        <label class="field-type-option">
                            <input type="radio" name="field_type" value="email">
                            <span>بريد</span>
                        </label>
                        <label class="field-type-option">
                            <input type="radio" name="field_type" value="number">
                            <span>رقم</span>
                        </label>
                        <label class="field-type-option">
                            <input type="radio" name="field_type" value="date">
                            <span>تاريخ</span>
                        </label>
                        <label class="field-type-option">
                            <input type="radio" name="field_type" value="select">
                            <span>قائمة منسدلة</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>خيارات الحقل</label>
                    <textarea name="field_options" rows="3" placeholder="للقوائم المنسدلة: اكتب كل خيار في سطر"></textarea>
                    <small>فقط للقوائم المنسدلة - كل خيار في سطر منفصل</small>
                </div>

                <div class="form-group">
                    <label>ترتيب العرض</label>
                    <input type="number" name="display_order" value="0" min="0">
                    <small>الحقول ذات الترتيب الأقل تظهر أولاً</small>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="is_required" style="width: auto;">
                        <span>حقل مطلوب</span>
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> حفظ الحقل
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addFieldModal')">
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal تعديل حقل -->
<div id="editFieldModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> تعديل الحقل المخصص</h3>
            <button class="modal-close" onclick="closeModal('editFieldModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" id="editFieldForm">
                <input type="hidden" name="action" value="update_field">
                <input type="hidden" name="field_id" id="edit_field_id">

                <div class="form-group">
                    <label>اسم الحقل (بالإنجليزية) <span class="required">*</span></label>
                    <input type="text" name="field_name" id="edit_field_name" required pattern="[a-zA-Z_][a-zA-Z0-9_]*">
                    <small>استخدم أحرف إنجليزية وأرقام و underscore فقط، يبدأ بحرف</small>
                </div>

                <div class="form-group">
                    <label>عنوان الحقل <span class="required">*</span></label>
                    <input type="text" name="field_label" id="edit_field_label" required>
                </div>

                <div class="form-group">
                    <label>نوع الحقل <span class="required">*</span></label>
                    <select name="field_type" id="edit_field_type" class="form-control">
                        <option value="text">نص قصير</option>
                        <option value="textarea">نص طويل</option>
                        <option value="url">رابط</option>
                        <option value="email">بريد</option>
                        <option value="number">رقم</option>
                        <option value="date">تاريخ</option>
                        <option value="select">قائمة منسدلة</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>خيارات الحقل</label>
                    <textarea name="field_options" id="edit_field_options" rows="3"></textarea>
                    <small>فقط للقوائم المنسدلة - كل خيار في سطر منفصل</small>
                </div>

                <div class="form-group">
                    <label>ترتيب العرض</label>
                    <input type="number" name="display_order" id="edit_display_order" min="0">
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="is_required" id="edit_is_required" style="width: auto;">
                        <span>حقل مطلوب</span>
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> حفظ التغييرات
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editFieldModal')">
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

function editField(field) {
    document.getElementById('edit_field_id').value = field.id;
    document.getElementById('edit_field_name').value = field.field_name;
    document.getElementById('edit_field_label').value = field.field_label;
    document.getElementById('edit_field_type').value = field.field_type;
    document.getElementById('edit_field_options').value = field.field_options || '';
    document.getElementById('edit_display_order').value = field.display_order;
    document.getElementById('edit_is_required').checked = field.is_required == 1;
    openModal('editFieldModal');
}

// إغلاق عند الضغط خارج Modal
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
