<div class="sukna-auth-wrapper" style="display:flex; min-height:100vh; align-items:center; justify-content:center; background:#f1f5f9; padding: 20px;">
    <div class="sukna-auth-card sukna-card" style="width:100%; max-width:450px; padding:40px; border-radius:16px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); background: #fff;">

        <div style="text-align:center; margin-bottom:35px;">
            <?php
                global $wpdb;
                $logo_url = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}sukna_settings WHERE setting_key = 'company_logo'");
                $system_name = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}sukna_settings WHERE setting_key = 'system_name'") ?: 'Sukna';

                if ( $logo_url ) : ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($system_name); ?>" style="max-height: 80px; margin-bottom: 15px;">
                <?php else : ?>
                    <h2 style="margin:0; font-size:2rem; font-weight:800; color:#1e293b;"><?php echo esc_html($system_name); ?></h2>
                <?php endif;
            ?>
            <p style="color:#64748b; margin-top:10px; font-weight:600;"><?php _e('مرحباً بكم في نظام سكنى', 'sukna'); ?></p>
        </div>

        <!-- Login Form -->
        <div id="sukna-login-container">
            <form id="sukna-login-form">
                <div class="sukna-form-group">
                    <label style="display:block; margin-bottom:8px; font-weight:700;"><?php _e('رقم الهاتف', 'sukna'); ?></label>
                    <div style="display:flex; direction: ltr;">
                        <select id="login-country-code" style="width: 100px; border-radius: 4px 0 0 4px; border-right: none; background: #f8fafc;">
                            <option value="+20" data-flag="🇪🇬">🇪🇬 +20</option>
                            <option value="+971" data-flag="🇦🇪">🇦🇪 +971</option>
                            <option value="+966" data-flag="🇸🇦">🇸🇦 +966</option>
                            <option value="+965" data-flag="🇰🇼">🇰🇼 +965</option>
                            <option value="+974" data-flag="🇶🇦">🇶🇦 +974</option>
                            <option value="+973" data-flag="🇧🇭">🇧🇭 +973</option>
                            <option value="+968" data-flag="🇴🇲">🇴🇲 +968</option>
                        </select>
                        <input type="tel" name="phone_body" id="login-phone-body" placeholder="123456789" required style="flex:1; border-radius: 0 4px 4px 0;">
                        <input type="hidden" name="phone" id="login-phone-full">
                    </div>
                </div>

                <div class="sukna-form-group">
                    <label style="display:block; margin-bottom:8px; font-weight:700;"><?php _e('كلمة المرور', 'sukna'); ?></label>
                    <input type="password" name="password" required placeholder="********" style="width:100%;">
                </div>

                <div id="login-error" style="display:none; padding:12px; background:#fee2e2; color:#991b1b; border-radius:8px; margin-bottom:20px; font-size:0.9rem; text-align:center; font-weight:600;"></div>

                <button type="submit" class="sukna-btn" style="width:100%; height:55px; font-size:1.1rem; background:#2563eb; border-radius:10px; margin-top:10px;">
                    <?php _e('تسجيل الدخول', 'sukna'); ?>
                </button>

                <div style="text-align:center; margin-top:25px; padding-top:20px; border-top: 1px solid #e2e8f0;">
                    <p style="color:#64748b; margin-bottom:10px;"><?php _e('ليس لديك حساب؟', 'sukna'); ?></p>
                    <button type="button" id="switch-to-register" style="background:none; border:none; color:#2563eb; font-weight:800; cursor:pointer; font-size:1rem; font-family: inherit;">
                        <?php _e('إنشاء حساب جديد', 'sukna'); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Registration Form (Multi-step) -->
        <div id="sukna-register-container" style="display:none;">
            <form id="sukna-register-form">
                <div id="reg-step-1" class="reg-step">
                    <h4 style="margin-bottom:20px; color:#1e293b;"><?php _e('الخطوة 1: الاسم بالكامل', 'sukna'); ?></h4>
                    <div style="display:flex; gap:10px; margin-bottom:20px;">
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:8px; font-size:0.8rem; font-weight:700;"><?php _e('الاسم الأول', 'sukna'); ?></label>
                            <input type="text" name="first_name" required style="width:100%;">
                        </div>
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:8px; font-size:0.8rem; font-weight:700;"><?php _e('اسم العائلة', 'sukna'); ?></label>
                            <input type="text" name="last_name" required style="width:100%;">
                        </div>
                    </div>
                </div>

                <div id="reg-step-2" class="reg-step" style="display:none;">
                    <h4 style="margin-bottom:20px; color:#1e293b;"><?php _e('الخطوة 2: رقم الهاتف', 'sukna'); ?></h4>
                    <div class="sukna-form-group">
                        <div style="display:flex; direction: ltr;">
                            <select id="reg-country-code" style="width: 100px; border-radius: 4px 0 0 4px; border-right: none; background: #f8fafc;">
                                <option value="+20">🇪🇬 +20</option>
                                <option value="+971">🇦🇪 +971</option>
                                <option value="+966">🇸🇦 +966</option>
                                <option value="+965">🇰🇼 +965</option>
                                <option value="+974">🇶🇦 +974</option>
                                <option value="+973">🇧🇭 +973</option>
                                <option value="+968">🇴🇲 +968</option>
                            </select>
                            <input type="tel" name="phone_body" id="reg-phone-body" placeholder="123456789" required style="flex:1; border-radius: 0 4px 4px 0;">
                        </div>
                    </div>
                </div>

                <div id="reg-step-3" class="reg-step" style="display:none;">
                    <h4 style="margin-bottom:20px; color:#1e293b;"><?php _e('الخطوة 3: البريد الإلكتروني', 'sukna'); ?></h4>
                    <div class="sukna-form-group">
                        <input type="email" name="email" placeholder="example@sukna.online" style="width:100%;">
                        <small style="color:#64748b;"><?php _e('اختياري', 'sukna'); ?></small>
                    </div>
                </div>

                <div id="reg-step-4" class="reg-step" style="display:none;">
                    <h4 style="margin-bottom:20px; color:#1e293b;"><?php _e('الخطوة الأخيرة: كلمة المرور', 'sukna'); ?></h4>
                    <div class="sukna-form-group">
                        <input type="password" name="password" id="reg-password" placeholder="********" required style="width:100%;">
                        <small style="color:#64748b;"><?php _e('8 أحرف على الأقل', 'sukna'); ?></small>
                    </div>
                </div>

                <div id="reg-error" style="display:none; padding:12px; background:#fee2e2; color:#991b1b; border-radius:8px; margin-bottom:20px; font-size:0.9rem; text-align:center; font-weight:600;"></div>

                <div style="display:flex; gap:10px; margin-top:20px;">
                    <button type="button" id="reg-prev" class="sukna-btn" style="flex:1; background:#64748b; display:none;"><?php _e('السابق', 'sukna'); ?></button>
                    <button type="button" id="reg-next" class="sukna-btn" style="flex:2;"><?php _e('التالي', 'sukna'); ?></button>
                    <button type="submit" id="reg-submit" class="sukna-btn" style="flex:2; display:none; background:#059669;"><?php _e('إتمام التسجيل', 'sukna'); ?></button>
                </div>

                <div style="text-align:center; margin-top:25px; padding-top:20px; border-top: 1px solid #e2e8f0;">
                    <button type="button" id="switch-to-login" style="background:none; border:none; color:#64748b; font-weight:700; cursor:pointer; font-size:0.9rem; font-family: inherit;">
                        <?php _e('العودة لتسجيل الدخول', 'sukna'); ?>
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<style>
.reg-step h4 {
    font-weight: 800;
    font-size: 1.1rem;
    border-right: 4px solid #2563eb;
    padding-right: 12px;
}
</style>
