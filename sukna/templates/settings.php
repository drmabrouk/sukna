<?php
global $wpdb;
$settings = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sukna_settings", OBJECT_K );
$fullscreen_pass = $settings['fullscreen_password']->setting_value ?? '123456789';
?>

<div class="sukna-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:#1e293b;"><?php _e('إعدادات النظام والتحكم', 'sukna'); ?></h2>
</div>

<div class="sukna-settings-wrapper">

    <div class="sukna-tabs" style="display:flex; gap:10px; margin-bottom:25px; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">
        <button class="sukna-tab-btn active" data-tab="tab-identity"><?php _e('هوية النظام', 'sukna'); ?></button>
        <button class="sukna-tab-btn" data-tab="tab-pwa"><?php _e('تطبيق الجوال', 'sukna'); ?></button>
        <button class="sukna-tab-btn" data-tab="tab-audit"><?php _e('سجل النشاطات', 'sukna'); ?></button>
    </div>

    <div class="sukna-tab-content-container">
        <!-- Section 1: System Identity -->
        <div id="tab-identity" class="sukna-tab-content active">
        <div class="sukna-card" style="border-top: 4px solid #2563eb;">
            <h3 style="display:flex; align-items:center; gap:10px; margin-bottom:20px;">
                <span class="dashicons dashicons-id"></span> <?php _e('هوية النظام والشركة', 'sukna'); ?>
            </h3>
            <form id="sukna-identity-form" class="sukna-system-settings-form">
                <div class="sukna-form-group">
                    <label><?php _e('اسم النظام', 'sukna'); ?></label>
                    <input type="text" name="system_name" value="<?php echo esc_attr($settings['system_name']->setting_value ?? 'Sukna'); ?>" placeholder="<?php _e('اسم النظام (يظهر في القائمة)', 'sukna'); ?>">
                </div>
                <div class="sukna-form-group">
                    <label><?php _e('اسم الشركة', 'sukna'); ?></label>
                    <input type="text" name="company_name" value="<?php echo esc_attr($settings['company_name']->setting_value ?? 'Sukna'); ?>" placeholder="<?php _e('اسم الشركة / المؤسسة', 'sukna'); ?>">
                </div>
                <div class="sukna-grid" style="grid-template-columns: 1fr 1fr; gap:10px;">
                    <div class="sukna-form-group">
                        <label><?php _e('رقم الهاتف', 'sukna'); ?></label>
                        <input type="text" name="company_phone" value="<?php echo esc_attr($settings['company_phone']->setting_value ?? ''); ?>" placeholder="<?php _e('رقم الهاتف', 'sukna'); ?>">
                    </div>
                    <div class="sukna-form-group">
                        <label><?php _e('البريد الإلكتروني', 'sukna'); ?></label>
                        <input type="email" name="company_email" value="<?php echo esc_attr($settings['company_email']->setting_value ?? ''); ?>" placeholder="<?php _e('البريد الإلكتروني', 'sukna'); ?>">
                    </div>
                </div>
                <div class="sukna-form-group">
                    <label><?php _e('العنوان', 'sukna'); ?></label>
                    <textarea name="company_address" rows="2" placeholder="<?php _e('العنوان بالتفصيل ليظهر في التقارير', 'sukna'); ?>"><?php echo esc_textarea($settings['company_address']->setting_value ?? ''); ?></textarea>
                </div>
                <div class="sukna-form-group">
                    <label><?php _e('شعار الشركة', 'sukna'); ?></label>
                    <div style="display:flex; gap:5px;">
                        <input type="text" name="company_logo" id="company-logo-url" value="<?php echo esc_attr($settings['company_logo']->setting_value ?? ''); ?>" placeholder="<?php _e('رابط الشعار', 'sukna'); ?>">
                        <button type="button" class="sukna-upload-btn sukna-btn" style="background:#64748b; padding:0 10px;"><span class="dashicons dashicons-upload"></span></button>
                    </div>
                    <div id="logo-preview-container" style="margin-top: 10px; <?php echo empty($settings['company_logo']->setting_value) ? 'display:none;' : ''; ?>">
                        <img id="logo-preview" src="<?php echo esc_url($settings['company_logo']->setting_value ?? ''); ?>" style="max-height: 100px; border: 1px solid #e2e8f0; padding: 5px;">
                    </div>
                </div>
                <div class="sukna-form-group">
                    <label><?php _e('أيقونة التطبيق (PWA Icon)', 'sukna'); ?></label>
                    <div style="display:flex; gap:5px;">
                        <input type="text" name="pwa_icon_url" id="pwa-icon-url" value="<?php echo esc_attr($settings['pwa_icon_url']->setting_value ?? ''); ?>" placeholder="URL">
                        <button type="button" class="sukna-upload-btn sukna-btn" style="background:#64748b; padding:0 10px;"><span class="dashicons dashicons-upload"></span></button>
                    </div>
                </div>
                <div class="sukna-form-group" style="background:#fee2e2; padding:15px; border-radius:6px; border:1px solid #fee2e2;">
                    <label style="color:#991b1b; font-weight:700;"><?php _e('أمن ملء الشاشة', 'sukna'); ?></label>
                    <input type="text" name="fullscreen_password" value="<?php echo esc_attr($fullscreen_pass); ?>" placeholder="<?php _e('كلمة مرور الخروج', 'sukna'); ?>">
                </div>
                <button type="submit" class="sukna-btn" style="width:100%; height:45px; background:#2563eb;"><?php _e('حفظ التعديلات', 'sukna'); ?></button>
            </form>
        </div>
        </div>

        <!-- Section 2: PWA & Mobile App Settings -->
        <div id="tab-pwa" class="sukna-tab-content" style="display:none;">
        <div class="sukna-card" style="border-top: 4px solid #805ad5; margin-bottom: 25px;">
            <h3 style="display:flex; align-items:center; gap:10px; margin-bottom:20px;">
                <span class="dashicons dashicons-smartphone"></span> <?php _e('إعدادات تطبيق الجوال (PWA)', 'sukna'); ?>
            </h3>
            <form class="sukna-system-settings-form">
                <div class="sukna-grid" style="grid-template-columns: 1fr 1fr; gap:10px;">
                    <div class="sukna-form-group">
                        <label style="font-size:0.75rem;"><?php _e('اسم التطبيق', 'sukna'); ?></label>
                        <input type="text" name="pwa_app_name" value="<?php echo esc_attr($settings['pwa_app_name']->setting_value ?? 'Sukna'); ?>">
                    </div>
                    <div class="sukna-form-group">
                        <label style="font-size:0.75rem;"><?php _e('الاسم المختصر', 'sukna'); ?></label>
                        <input type="text" name="pwa_short_name" value="<?php echo esc_attr($settings['pwa_short_name']->setting_value ?? 'Sukna'); ?>">
                    </div>
                </div>
                <div class="sukna-grid" style="grid-template-columns: 1fr 1fr; gap:10px;">
                    <div class="sukna-form-group">
                        <label style="font-size:0.75rem;"><?php _e('لون السمة (Theme)', 'sukna'); ?></label>
                        <input type="color" name="pwa_theme_color" value="<?php echo esc_attr($settings['pwa_theme_color']->setting_value ?? '#2563eb'); ?>" style="height:35px; padding:2px;">
                    </div>
                    <div class="sukna-form-group">
                        <label style="font-size:0.75rem;"><?php _e('لون الخلفية', 'sukna'); ?></label>
                        <input type="color" name="pwa_bg_color" value="<?php echo esc_attr($settings['pwa_bg_color']->setting_value ?? '#f1f5f9'); ?>" style="height:35px; padding:2px;">
                    </div>
                </div>
                <button type="submit" class="sukna-btn" style="width:100%; height:40px; background:#805ad5;"><?php _e('تحديث إعدادات التطبيق', 'sukna'); ?></button>
            </form>
        </div>
        </div>

        <!-- Section 3: Activity Audit Log -->
        <div id="tab-audit" class="sukna-tab-content" style="display:none;">
            <div class="sukna-card" style="border-top: 4px solid #f59e0b;">
                <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom:20px;">
                    <h3><?php _e('سجل مراقبة النشاطات (آخر 200 إجراء)', 'sukna'); ?></h3>
                    <button id="sukna-export-audit-pdf" class="sukna-btn" style="background:#dc2626;"><span class="dashicons dashicons-pdf" style="margin-left:5px;"></span><?php _e('تصدير التقرير', 'sukna'); ?></button>
                </div>
                <div style="max-height:600px; overflow-y:auto;">
                    <table class="sukna-table" id="sukna-audit-table">
                        <thead>
                            <tr>
                                <th><?php _e('المستخدم', 'sukna'); ?></th>
                                <th><?php _e('الإجراء', 'sukna'); ?></th>
                                <th><?php _e('الوصف', 'sukna'); ?></th>
                                <th><?php _e('الجهاز / IP', 'sukna'); ?></th>
                                <th><?php _e('التاريخ', 'sukna'); ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="sukna-audit-logs-body">
                            <?php
                            $action_map = array('login' => 'دخول', 'failed_login' => 'فشل دخول', 'add_user' => 'إضافة مستخدم', 'edit_user' => 'تعديل مستخدم');
                            $audit_logs = Sukna_Audit::get_logs();
                            foreach($audit_logs as $log): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($log->user_id); ?></strong></td>
                                    <td><span class="sukna-capsule capsule-info"><?php echo $action_map[$log->action_type] ?? $log->action_type; ?></span></td>
                                    <td><small><?php echo esc_html($log->description); ?></small></td>
                                    <td><small><?php echo esc_html($log->device_type); ?> / <?php echo esc_html($log->ip_address); ?></small></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($log->action_date)); ?></td>
                                    <td>
                                        <?php if(in_array($log->action_type, array('delete_user'))): ?>
                                            <button class="sukna-btn undo-action" data-id="<?php echo $log->id; ?>" style="padding:2px 8px; font-size:0.7rem; background:#f59e0b;"><?php _e('تراجع', 'sukna'); ?></button>
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
.sukna-tab-btn {
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
.sukna-tab-btn.active {
    background: #2563eb;
    color: #fff;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.sukna-tab-btn').on('click', function() {
        const tab = $(this).data('tab');
        $('.sukna-tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.sukna-tab-content').hide();
        $('#' + tab).fadeIn(200);
    });

    $('#sukna-export-audit-pdf').on('click', function() {
        const element = document.getElementById('sukna-audit-table');
        const opt = {
            margin: 10,
            filename: 'audit-log-' + new Date().toISOString().slice(0,10) + '.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
        };
        html2pdf().set(opt).from(element).save();
    });

    $('.sukna-system-settings-form').on('submit', function(e) {
        e.preventDefault();
        $.post(sukna_ajax.ajax_url, $(this).serialize() + '&action=sukna_save_settings&nonce=' + sukna_ajax.nonce, function(res) {
            if(res.success) alert('<?php _e('تم الحفظ بنجاح', 'sukna'); ?>');
        });
    });
});
</script>
