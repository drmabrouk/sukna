<?php
global $wpdb;
$settings = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sukna_settings", OBJECT_K );
$fullscreen_pass = $settings['fullscreen_password']->setting_value ?? '123456789';
?>

<div class="sukna-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:#1e293b;"><?php _e('إعدادات النظام', 'sukna'); ?></h2>
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
        <div class="sukna-card" style="border-top: 5px solid #D4AF37;">
            <h3 style="display:flex; align-items:center; gap:10px; margin-bottom:20px; color: #000;">
                <span class="dashicons dashicons-id"></span> <?php _e('هوية النظام والشركة', 'sukna'); ?>
            </h3>
            <form id="sukna-identity-form" class="sukna-system-settings-form">
                <div class="sukna-form-group">
                    <input type="text" name="system_name" value="<?php echo esc_attr($settings['system_name']->setting_value ?? 'Sukna'); ?>" placeholder="<?php _e('اسم النظام (يظهر في القائمة)', 'sukna'); ?>">
                </div>
                <div class="sukna-form-group">
                    <input type="text" name="company_name" value="<?php echo esc_attr($settings['company_name']->setting_value ?? 'Sukna'); ?>" placeholder="<?php _e('اسم الشركة / المؤسسة', 'sukna'); ?>">
                </div>
                <div class="sukna-grid" style="grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="sukna-form-group">
                        <input type="text" name="company_phone" value="<?php echo esc_attr($settings['company_phone']->setting_value ?? ''); ?>" placeholder="<?php _e('رقم الهاتف', 'sukna'); ?>">
                    </div>
                    <div class="sukna-form-group">
                        <input type="email" name="company_email" value="<?php echo esc_attr($settings['company_email']->setting_value ?? ''); ?>" placeholder="<?php _e('البريد الإلكتروني', 'sukna'); ?>">
                    </div>
                </div>
                <div class="sukna-form-group">
                    <textarea name="company_address" rows="2" placeholder="<?php _e('العنوان بالتفصيل ليظهر في التقارير', 'sukna'); ?>"><?php echo esc_textarea($settings['company_address']->setting_value ?? ''); ?></textarea>
                </div>
                <div class="sukna-form-group">
                    <div style="display:flex; gap:10px; align-items: center;">
                        <input type="text" name="company_logo" id="company-logo-url" value="<?php echo esc_attr($settings['company_logo']->setting_value ?? ''); ?>" placeholder="<?php _e('رابط شعار الشركة', 'sukna'); ?>" style="flex:1;">
                        <button type="button" class="sukna-upload-btn sukna-btn" style="background:#000; border:none;"><span class="dashicons dashicons-upload"></span></button>
                    </div>
                    <div id="logo-preview-container" style="margin-top: 15px; text-align: center; <?php echo empty($settings['company_logo']->setting_value) ? 'display:none;' : ''; ?>">
                        <img id="logo-preview" src="<?php echo esc_url($settings['company_logo']->setting_value ?? ''); ?>" style="max-height: 80px; border: 1px solid #e2e8f0; padding: 10px; border-radius: 8px; object-fit: contain;">
                    </div>
                </div>
                <div class="sukna-form-group">
                    <div style="display:flex; gap:10px; align-items: center;">
                        <input type="text" name="pwa_icon_url" id="pwa-icon-url" value="<?php echo esc_attr($settings['pwa_icon_url']->setting_value ?? ''); ?>" placeholder="<?php _e('رابط أيقونة التطبيق (PWA Icon)', 'sukna'); ?>" style="flex:1;">
                        <button type="button" class="sukna-upload-btn sukna-btn" style="background:#000; border:none;"><span class="dashicons dashicons-upload"></span></button>
                    </div>
                </div>
                <div class="sukna-form-group" style="background:#000; padding:20px; border-radius:10px;">
                    <input type="text" name="fullscreen_password" value="<?php echo esc_attr($fullscreen_pass); ?>" placeholder="<?php _e('كلمة مرور الخروج من ملء الشاشة', 'sukna'); ?>" style="width:100%; background:#1a1a1a; color:#fff; border: 1px solid #333;">
                </div>
                <button type="submit" class="sukna-btn sukna-btn-accent" style="width:100%; height:50px; border-radius: 8px; font-weight: 800;"><?php _e('حفظ كافة التعديلات', 'sukna'); ?></button>
            </form>
        </div>
        </div>

        <!-- Section 2: PWA & Mobile App Settings -->
        <div id="tab-pwa" class="sukna-tab-content" style="display:none;">
        <div class="sukna-card" style="border-top: 5px solid #000;">
            <h3 style="display:flex; align-items:center; gap:10px; margin-bottom:25px; color:#000;">
                <span class="dashicons dashicons-smartphone"></span> <?php _e('إعدادات تطبيق الجوال', 'sukna'); ?>
            </h3>
            <form class="sukna-system-settings-form">
                <div class="sukna-grid" style="grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="sukna-form-group">
                        <input type="text" name="pwa_app_name" value="<?php echo esc_attr($settings['pwa_app_name']->setting_value ?? 'Sukna'); ?>" placeholder="<?php _e('اسم التطبيق', 'sukna'); ?>">
                    </div>
                    <div class="sukna-form-group">
                        <input type="text" name="pwa_short_name" value="<?php echo esc_attr($settings['pwa_short_name']->setting_value ?? 'Sukna'); ?>" placeholder="<?php _e('الاسم المختصر', 'sukna'); ?>">
                    </div>
                </div>
                <div class="sukna-grid" style="grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="sukna-form-group">
                        <input type="color" name="pwa_theme_color" value="<?php echo esc_attr($settings['pwa_theme_color']->setting_value ?? '#000000'); ?>" style="height:45px; cursor: pointer;">
                    </div>
                    <div class="sukna-form-group">
                        <input type="color" name="pwa_bg_color" value="<?php echo esc_attr($settings['pwa_bg_color']->setting_value ?? '#ffffff'); ?>" style="height:45px; cursor: pointer;">
                    </div>
                </div>
                <button type="submit" class="sukna-btn sukna-btn-accent" style="width:100%; height:45px; border-radius: 8px;"><?php _e('تحديث إعدادات التطبيق', 'sukna'); ?></button>
            </form>
        </div>
        </div>

        <!-- Section 3: Activity Audit Log -->
        <div id="tab-audit" class="sukna-tab-content" style="display:none;">
            <div class="sukna-card" style="border-top: 5px solid #000;">
                <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom:25px;">
                    <h3 style="margin:0; color:#000;"><?php _e('سجل النشاطات', 'sukna'); ?></h3>
                    <button id="sukna-export-audit-pdf" class="sukna-btn" style="background:#000; border:none; border-radius: 6px;"><span class="dashicons dashicons-pdf" style="margin-left:5px;"></span><?php _e('تصدير التقرير', 'sukna'); ?></button>
                </div>
                <div style="max-height:600px; overflow-y:auto;">
                    <table class="sukna-table">
                        <thead>
                            <tr>
                                <th><?php _e('المستخدم', 'sukna'); ?></th>
                                <th><?php _e('الإجراء', 'sukna'); ?></th>
                                <th><?php _e('الوصف', 'sukna'); ?></th>
                                <th><?php _e('التاريخ', 'sukna'); ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="sukna-audit-logs-body">
                            <?php
                            $action_map = array('login' => 'دخول', 'failed_login' => 'فشل دخول', 'add_user' => 'إضافة مستخدم', 'edit_user' => 'تعديل مستخدم', 'add_property' => 'إضافة عقار', 'save_room' => 'إضافة وحدة');
                            $audit_logs = Sukna_Audit::get_logs();
                            foreach($audit_logs as $log): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($log->user_id); ?></strong></td>
                                    <td><span class="sukna-capsule capsule-accent"><?php echo $action_map[$log->action_type] ?? $log->action_type; ?></span></td>
                                    <td><small><?php echo esc_html($log->description); ?></small></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($log->action_date)); ?></td>
                                    <td>
                                        <?php if(in_array($log->action_type, array('delete_user'))): ?>
                                            <button class="sukna-btn undo-action" data-id="<?php echo $log->id; ?>" style="padding:2px 8px; font-size:0.7rem; background:#000; border:none;"><?php _e('تراجع', 'sukna'); ?></button>
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
