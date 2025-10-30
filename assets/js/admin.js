// JavaScript للوحة التحكم

// إخفاء الرسائل التلقائي بعد 5 ثوان
const alerts = document.querySelectorAll('.alert');
alerts.forEach(alert => {
    setTimeout(() => {
        alert.style.opacity = '0';
        setTimeout(() => {
            alert.remove();
        }, 300);
    }, 5000);
});

// معاينة الصورة قبل الرفع
const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
imageInputs.forEach(input => {
    input.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                // البحث عن عنصر المعاينة أو إنشاؤه
                let preview = input.parentElement.querySelector('.image-preview');
                if (!preview) {
                    preview = document.createElement('div');
                    preview.className = 'image-preview';
                    preview.style.marginTop = '10px';
                    input.parentElement.insertBefore(preview, input.nextSibling);
                }
                preview.innerHTML = `<img src="${event.target.result}" style="max-width: 200px; border-radius: 5px;">
                                     <p style="margin-top: 5px; font-size: 0.9rem;">معاينة الصورة الجديدة</p>`;
            };
            reader.readAsDataURL(file);
        }
    });
});

// تأكيد الحذف
const deleteButtons = document.querySelectorAll('a[href*="delete"]');
deleteButtons.forEach(button => {
    button.addEventListener('click', function(e) {
        if (!confirm('هل أنت متأكد من الحذف؟ هذا الإجراء لا يمكن التراجع عنه.')) {
            e.preventDefault();
        }
    });
});

// التحقق من النماذج
const forms = document.querySelectorAll('form');
forms.forEach(form => {
    form.addEventListener('submit', function(e) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.style.borderColor = 'var(--danger-color)';
            } else {
                field.style.borderColor = '';
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert('يرجى ملء جميع الحقول المطلوبة');
        }
    });
});
