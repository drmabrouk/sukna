<?php
global $wpdb;
$staff = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ac_is_staff ORDER BY id DESC" );
$settings = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ac_is_settings", OBJECT_K );
$fullscreen_pass = $settings['fullscreen_password']->setting_value ?? '123456789';
?>

<div class="ac-is-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:var(--ac-sidebar-bg);"><?php _e('إعدادات النظام والتحكم', 'ac-inventory-system'); ?></h2>
</div>

<div class="ac-is-settings-wrapper">

    <div class="ac-is-tabs" style="display:flex; gap:10px; margin-bottom:25px; border-bottom:1px solid var(--ac-border); padding-bottom:10px;">
        <button class="ac-is-tab-btn active" data-tab="tab-staff"><?php _e('طاقم العمل', 'ac-inventory-system'); ?></button>
        <button class="ac-is-tab-btn" data-tab="tab-identity"><?php _e('هوية النظام', 'ac-inventory-system'); ?></button>
        <button class="ac-is-tab-btn" data-tab="tab-pwa"><?php _e('تطبيق الجوال', 'ac-inventory-system'); ?></button>
        <button class="ac-is-tab-btn" data-tab="tab-brands"><?php _e('البراندات', 'ac-inventory-system'); ?></button>
        <button class="ac-is-tab-btn" data-tab="tab-audit"><?php _e('سجل النشاطات', 'ac-inventory-system'); ?></button>
    </div>

    <!-- Section 1: Staff Management -->
    <div id="tab-staff" class="ac-is-tab-content active">
    <div class="ac-is-card" style="margin-bottom:30px; border-top: 4px solid #4a5568;">
        <h3 style="display:flex; align-items:center; gap:10px; margin-bottom:25px;">
            <span class="dashicons dashicons-admin-users"></span> <?php _e('إدارة طاقم العمل والمستخدمين', 'ac-inventory-system'); ?>
        </h3>

        <form id="ac-is-staff-form" style="background:#f8fafc; padding:20px; border:1px solid #e2e8f0; margin-bottom:25px;">
            <input type="hidden" name="id" id="staff-id">
            <div class="ac-is-grid" style="grid-template-columns: repeat(2, 1fr); gap:15px;">
                <div class="ac-is-form-group">
                    <input type="text" name="staff_username" placeholder="<?php _e('اسم المستخدم للولوج', 'ac-inventory-system'); ?>" required>
                </div>
                <div class="ac-is-form-group">
                    <input type="password" name="staff_password" placeholder="<?php _e('كلمة المرور الجديدة', 'ac-inventory-system'); ?>" required>
                </div>
                <div class="ac-is-form-group">
                    <input type="text" name="staff_name" placeholder="<?php _e('الاسم بالكامل (للفواتير)', 'ac-inventory-system'); ?>" required>
                </div>
                <div class="ac-is-form-group">
                    <select name="staff_role">
                        <option value="employee"><?php _e('موظف مبيعات', 'ac-inventory-system'); ?></option>
                        <option value="technician"><?php _e('فني صيانة', 'ac-inventory-system'); ?></option>
                        <option value="manager"><?php _e('مدير مبيعات (صلاحية الحذف)', 'ac-inventory-system'); ?></option>
                        <option value="admin"><?php _e('مدير نظام كامل', 'ac-inventory-system'); ?></option>
                    </select>
                </div>
            </div>
            <div class="ac-is-grid" style="grid-template-columns: repeat(3, 1fr); gap:15px; margin-top:10px;">
                <div class="ac-is-form-group"><input type="number" step="0.01" name="base_salary" placeholder="<?php _e('الراتب الأساسي', 'ac-inventory-system'); ?>"></div>
                <div class="ac-is-form-group"><input type="number" name="working_days" placeholder="<?php _e('أيام العمل', 'ac-inventory-system'); ?>" value="26"></div>
                <div class="ac-is-form-group"><input type="number" name="working_hours" placeholder="<?php _e('ساعات العمل', 'ac-inventory-system'); ?>" value="8"></div>
            </div>
            <button type="submit" class="ac-is-btn" style="width:100%; margin-top:10px; background:#4a5568;">
                <?php _e('إضافة مستخدم جديد للنظام', 'ac-inventory-system'); ?>
            </button>
        </form>

        <table class="ac-is-table">
            <thead>
                <tr>
                    <th><?php _e('المستخدم', 'ac-inventory-system'); ?></th>
                    <th><?php _e('الاسم', 'ac-inventory-system'); ?></th>
                    <th><?php _e('الصلاحية', 'ac-inventory-system'); ?></th>
                    <th><?php _e('الراتب', 'ac-inventory-system'); ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($staff as $s): ?>
                    <tr>
                        <td><strong><?php echo esc_html($s->username); ?></strong></td>
                        <td><?php echo esc_html($s->name); ?></td>
                        <td><span class="ac-is-capsule capsule-info"><?php
                            $roles = array('admin' => 'مدير نظام', 'manager' => 'مدير مبيعات', 'technician' => 'فني صيانة', 'employee' => 'موظف');
                            echo $roles[$s->role] ?? $s->role;
                        ?></span></td>
                        <td><?php echo number_format($s->base_salary, 2); ?></td>
                        <td style="text-align:left;">
                            <div style="display:flex; gap:5px; justify-content: flex-end;">
                                <button class="ac-is-btn ac-is-edit-staff" data-staff='<?php echo json_encode($s); ?>' style="padding:4px 8px; font-size:0.7rem; background:#3b82f6;"><span class="dashicons dashicons-edit"></span></button>
                                <?php if($s->username != 'admin'): ?>
                                    <button class="ac-is-btn ac-is-delete-staff" data-id="<?php echo $s->id; ?>" style="padding:4px 8px; font-size:0.7rem; background:#ef4444;"><span class="dashicons dashicons-trash"></span></button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    </div>
    </div>

    <div class="ac-is-tab-content-container">
        <!-- Section 2: System Identity -->
        <div id="tab-identity" class="ac-is-tab-content" style="display:none;">
        <div class="ac-is-card" style="border-top: 4px solid var(--ac-primary);">
            <h3 style="display:flex; align-items:center; gap:10px; margin-bottom:20px;">
                <span class="dashicons dashicons-id"></span> <?php _e('هوية النظام والشركة', 'ac-inventory-system'); ?>
            </h3>
            <form class="ac-is-system-settings-form">
                <div class="ac-is-form-group">
                    <input type="text" name="system_name" value="<?php echo esc_attr($settings['system_name']->setting_value ?? ''); ?>" placeholder="<?php _e('اسم النظام (يظهر في القائمة)', 'ac-inventory-system'); ?>">
                </div>
                <div class="ac-is-form-group">
                    <input type="text" name="company_name" value="<?php echo esc_attr($settings['company_name']->setting_value ?? ''); ?>" placeholder="<?php _e('اسم الشركة / المؤسسة', 'ac-inventory-system'); ?>">
                </div>
                <div class="ac-is-grid" style="grid-template-columns: 1fr 1fr; gap:10px;">
                    <div class="ac-is-form-group"><input type="text" name="company_phone" value="<?php echo esc_attr($settings['company_phone']->setting_value ?? ''); ?>" placeholder="<?php _e('رقم الهاتف', 'ac-inventory-system'); ?>"></div>
                    <div class="ac-is-form-group"><input type="email" name="company_email" value="<?php echo esc_attr($settings['company_email']->setting_value ?? ''); ?>" placeholder="<?php _e('البريد الإلكتروني', 'ac-inventory-system'); ?>"></div>
                </div>
                <div class="ac-is-form-group">
                    <textarea name="company_address" rows="2" placeholder="<?php _e('العنوان بالتفصيل ليظهر في الفواتير', 'ac-inventory-system'); ?>"><?php echo esc_textarea($settings['company_address']->setting_value ?? ''); ?></textarea>
                </div>
                <div class="ac-is-form-group">
                    <label style="font-size:0.75rem;"><?php _e('شعار الشركة', 'ac-inventory-system'); ?></label>
                    <div style="display:flex; gap:5px;">
                        <input type="text" name="company_logo" id="company-logo-url" value="<?php echo esc_attr($settings['company_logo']->setting_value ?? ''); ?>" placeholder="<?php _e('رابط الشعار', 'ac-inventory-system'); ?>">
                        <button type="button" class="ac-is-upload-btn ac-is-btn" style="background:#64748b; padding:0 10px;"><span class="dashicons dashicons-upload"></span></button>
                    </div>
                </div>
                <div class="ac-is-form-group">
                    <label style="font-size:0.75rem;"><?php _e('أيقونة التطبيق (PWA Icon)', 'ac-inventory-system'); ?></label>
                    <div style="display:flex; gap:5px;">
                        <input type="text" name="pwa_icon_url" id="pwa-icon-url" value="<?php echo esc_attr($settings['pwa_icon_url']->setting_value ?? ''); ?>" placeholder="URL">
                        <button type="button" class="ac-is-upload-btn ac-is-btn" style="background:#64748b; padding:0 10px;"><span class="dashicons dashicons-upload"></span></button>
                    </div>
                </div>
                <div class="ac-is-form-group" style="background:#fef2f2; padding:15px; border-radius:6px; border:1px solid #fee2e2;">
                    <label style="color:#991b1b; font-weight:700;"><?php _e('أمن ملء الشاشة', 'ac-inventory-system'); ?></label>
                    <input type="text" name="fullscreen_password" value="<?php echo esc_attr($fullscreen_pass); ?>" placeholder="<?php _e('كلمة مرور الخروج', 'ac-inventory-system'); ?>">
                </div>
                <button type="submit" class="ac-is-btn" style="width:100%; height:45px; background:var(--ac-primary);"><?php _e('حفظ التعديلات', 'ac-inventory-system'); ?></button>
            </form>
        </div>

        </div>
        </div>

        <!-- Section 3: PWA & Mobile App Settings -->
        <div id="tab-pwa" class="ac-is-tab-content" style="display:none;">
        <div class="ac-is-card" style="border-top: 4px solid #805ad5; margin-bottom: 25px;">
            <h3 style="display:flex; align-items:center; gap:10px; margin-bottom:20px;">
                <span class="dashicons dashicons-smartphone"></span> <?php _e('إعدادات تطبيق الجوال (PWA)', 'ac-inventory-system'); ?>
            </h3>
            <form class="ac-is-system-settings-form">
                <div class="ac-is-grid" style="grid-template-columns: 1fr 1fr; gap:10px;">
                    <div class="ac-is-form-group">
                        <label style="font-size:0.75rem;"><?php _e('اسم التطبيق', 'ac-inventory-system'); ?></label>
                        <input type="text" name="pwa_app_name" value="<?php echo esc_attr($settings['pwa_app_name']->setting_value ?? 'نظام المبيعات'); ?>">
                    </div>
                    <div class="ac-is-form-group">
                        <label style="font-size:0.75rem;"><?php _e('الاسم المختصر', 'ac-inventory-system'); ?></label>
                        <input type="text" name="pwa_short_name" value="<?php echo esc_attr($settings['pwa_short_name']->setting_value ?? 'المبيعات'); ?>">
                    </div>
                </div>
                <div class="ac-is-grid" style="grid-template-columns: 1fr 1fr; gap:10px;">
                    <div class="ac-is-form-group">
                        <label style="font-size:0.75rem;"><?php _e('لون السمة (Theme)', 'ac-inventory-system'); ?></label>
                        <input type="color" name="pwa_theme_color" value="<?php echo esc_attr($settings['pwa_theme_color']->setting_value ?? '#2563eb'); ?>" style="height:35px; padding:2px;">
                    </div>
                    <div class="ac-is-form-group">
                        <label style="font-size:0.75rem;"><?php _e('لون الخلفية', 'ac-inventory-system'); ?></label>
                        <input type="color" name="pwa_bg_color" value="<?php echo esc_attr($settings['pwa_bg_color']->setting_value ?? '#f1f5f9'); ?>" style="height:35px; padding:2px;">
                    </div>
                </div>
                <button type="submit" class="ac-is-btn" style="width:100%; height:40px; background:#805ad5;"><?php _e('تحديث إعدادات التطبيق', 'ac-inventory-system'); ?></button>
            </form>
        </div>

        </div>
        </div>

        <!-- Section 4: Brand Management -->
        <div id="tab-brands" class="ac-is-tab-content" style="display:none;">
        <div class="ac-is-card" style="border-top: 4px solid #059669;">
            <h3 style="display:flex; align-items:center; gap:10px; margin-bottom:20px;">
                <span class="dashicons dashicons-tag"></span> <?php _e('إدارة العلامات التجارية', 'ac-inventory-system'); ?>
            </h3>
            <form id="ac-is-brand-form" style="background:#ecfdf5; padding:15px; border:1px solid #d1fae5; margin-bottom:20px;">
                <input type="hidden" name="id" id="brand-id">
                <div class="ac-is-form-group"><input type="text" name="name" id="brand-name" placeholder="<?php _e('اسم البراند', 'ac-inventory-system'); ?>" required></div>
                <div class="ac-is-form-group">
                    <div style="display:flex; gap:5px;">
                        <input type="text" name="logo_url" id="brand-logo-url" placeholder="<?php _e('رابط الشعار', 'ac-inventory-system'); ?>">
                        <button type="button" class="ac-is-upload-btn ac-is-btn" style="background:#64748b; padding:0 10px;"><span class="dashicons dashicons-upload"></span></button>
                    </div>
                </div>
                <button type="submit" class="ac-is-btn" style="width:100%; background:#059669;"><?php _e('إضافة / تحديث براند', 'ac-inventory-system'); ?></button>
            </form>

            <?php $brands = AC_IS_Brands::get_brands(); ?>
            <div style="max-height:250px; overflow-y:auto;">
                <table class="ac-is-table">
                    <tbody>
                        <?php foreach($brands as $b): ?>
                            <tr data-brand='<?php echo json_encode($b); ?>'>
                                <td style="width:50px;"><?php if($b->logo_url): ?><img src="<?php echo esc_url($b->logo_url); ?>" style="height:25px;"><?php endif; ?></td>
                                <td><strong><?php echo esc_html($b->name); ?></strong></td>
                                <td style="text-align:left;">
                                    <button class="ac-is-edit-brand" style="background:none; border:none; color:var(--ac-primary); cursor:pointer;"><span class="dashicons dashicons-edit"></span></button>
                                    <button class="ac-is-delete-brand" data-id="<?php echo $b->id; ?>" style="background:none; border:none; color:#ef4444; cursor:pointer;"><span class="dashicons dashicons-trash"></span></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        </div>

        <!-- Section 5: Activity Audit Log -->
        <div id="tab-audit" class="ac-is-tab-content" style="display:none;">
            <div class="ac-is-card" style="border-top: 4px solid #f59e0b;">
                <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom:20px;">
                    <h3><?php _e('سجل مراقبة النشاطات (آخر 200 إجراء)', 'ac-inventory-system'); ?></h3>
                    <button id="ac-is-export-audit-pdf" class="ac-is-btn" style="background:#dc2626;"><span class="dashicons dashicons-pdf" style="margin-left:5px;"></span><?php _e('تصدير التقرير', 'ac-inventory-system'); ?></button>
                </div>
                <div style="max-height:600px; overflow-y:auto;">
                    <table class="ac-is-table" id="ac-is-audit-table">
                        <thead>
                            <tr>
                                <th><?php _e('المستخدم', 'ac-inventory-system'); ?></th>
                                <th><?php _e('الإجراء', 'ac-inventory-system'); ?></th>
                                <th><?php _e('الوصف', 'ac-inventory-system'); ?></th>
                                <th><?php _e('الجهاز / IP', 'ac-inventory-system'); ?></th>
                                <th><?php _e('التاريخ', 'ac-inventory-system'); ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="ac-is-audit-logs-body">
                            <?php
                            $action_map = array('login' => 'دخول', 'failed_login' => 'فشل دخول', 'add_product' => 'إضافة صنف', 'edit_product' => 'تعديل صنف', 'sale' => 'عملية بيع');
                            $audit_logs = AC_IS_Reports_Audit::get_logs();
                            foreach($audit_logs as $log): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($log->user_id); ?></strong></td>
                                    <td><span class="ac-is-capsule capsule-info"><?php echo $action_map[$log->action_type] ?? $log->action_type; ?></span></td>
                                    <td><small><?php echo esc_html($log->description); ?></small></td>
                                    <td><small><?php echo esc_html($log->device_type); ?> / <?php echo esc_html($log->ip_address); ?></small></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($log->action_date)); ?></td>
                                    <td>
                                        <?php if(in_array($log->action_type, array('delete_product', 'delete_customer'))): ?>
                                            <button class="ac-is-btn undo-action" data-id="<?php echo $log->id; ?>" style="padding:2px 8px; font-size:0.7rem; background:#f59e0b;"><?php _e('تراجع', 'ac-inventory-system'); ?></button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.ac-is-tab-btn {
    padding: 10px 20px;
    border: none;
    background: #e2e8f0;
    color: #475569;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 700;
    font-size: 0.85rem;
    transition: all 0.2s;
}
.ac-is-tab-btn.active {
    background: var(--ac-primary);
    color: #fff;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.ac-is-tab-btn').on('click', function() {
        const tab = $(this).data('tab');
        $('.ac-is-tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.ac-is-tab-content').hide();
        $('#' + tab).fadeIn(200);
    });

    $('#ac-is-export-audit-pdf').on('click', function() {
        const element = document.getElementById('ac-is-audit-table');
        const opt = {
            margin: 10,
            filename: 'audit-log-' + new Date().toISOString().slice(0,10) + '.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
        };
        html2pdf().set(opt).from(element).save();
    });

    // Shared Staff/Settings submit logic
    $('#ac-is-staff-form').on('submit', function(e) {
        e.preventDefault();
        const action = $('#staff-id').val() ? 'ac_is_save_staff' : 'ac_is_add_staff';
        $.post(ac_is_ajax.ajax_url, $(this).serialize() + '&action=' + action + '&nonce=' + ac_is_ajax.nonce, function(res) {
            if(res.success) location.reload(); else alert(res.data);
        });
    });

    $(document).on('click', '.ac-is-edit-staff', function() {
        const s = $(this).data('staff');
        $('#staff-id').val(s.id);
        $('input[name="staff_username"]').val(s.username);
        $('input[name="staff_name"]').val(s.name);
        $('select[name="staff_role"]').val(s.role);
        $('input[name="base_salary"]').val(s.base_salary);
        $('input[name="working_days"]').val(s.working_days);
        $('input[name="working_hours"]').val(s.working_hours);
        $('input[name="staff_password"]').attr('placeholder', '<?php _e('اتركه فارغاً للحفاظ على الحالي', 'ac-inventory-system'); ?>').prop('required', false);
        $('#ac-is-staff-form button').text('<?php _e('تحديث بيانات الموظف', 'ac-inventory-system'); ?>');
        window.scrollTo(0, 0);
    });

    $('.ac-is-system-settings-form').on('submit', function(e) {
        e.preventDefault();
        $.post(ac_is_ajax.ajax_url, $(this).serialize() + '&action=ac_is_save_settings&nonce=' + ac_is_ajax.nonce, function(res) {
            if(res.success) alert('<?php _e('تم الحفظ بنجاح', 'ac-inventory-system'); ?>');
        });
    });

    $(document).on('click', '.ac-is-delete-staff', function() {
        if(!confirm('حذف؟')) return;
        $.post(ac_is_ajax.ajax_url, { action: 'ac_is_delete_staff', id: $(this).data('id'), nonce: ac_is_ajax.nonce }, () => location.reload());
    });

    // Brand logic
    $(document).on('click', '.ac-is-edit-brand', function() {
        const b = $(this).closest('tr').data('brand');
        $('#brand-id').val(b.id); $('#brand-name').val(b.name); $('#brand-logo-url').val(b.logo_url);
    });

    $('#ac-is-brand-form').on('submit', function(e) {
        e.preventDefault();
        $.post(ac_is_ajax.ajax_url, $(this).serialize() + '&action=ac_is_save_brand&nonce=' + ac_is_ajax.nonce, (res) => location.reload());
    });

    $(document).on('click', '.ac-is-delete-brand', function() {
        if(!confirm('حذف؟')) return;
        $.post(ac_is_ajax.ajax_url, { action: 'ac_is_delete_brand', id: $(this).data('id'), nonce: ac_is_ajax.nonce }, () => location.reload());
    });
});
</script>
