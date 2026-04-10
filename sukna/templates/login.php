<div class="sukna-auth-wrapper">
    <div class="sukna-auth-card">

        <div style="text-align:center; margin-bottom:35px;">
            <?php
                global $wpdb;
                $logo_url = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}sukna_settings WHERE setting_key = 'company_logo'");
                $system_name = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}sukna_settings WHERE setting_key = 'system_name'") ?: 'Sukna';

                if ( $logo_url ) : ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($system_name); ?>" style="max-height: 80px; margin-bottom: 15px; object-fit: contain;">
                <?php else : ?>
                    <h2 style="margin:0; font-size:2rem; font-weight:800; color:#D4AF37;"><?php echo esc_html($system_name); ?></h2>
                <?php endif;
            ?>
        </div>

        <!-- Login Form -->
        <div id="sukna-login-container">
            <form id="sukna-login-form">
                <div class="sukna-form-group">
                    <div class="phone-input-group">
                        <span id="login-flag" class="country-flag-inside">🇪🇬</span>
                        <select id="login-country-code" style="width: 80px; padding-right: 30px; border-radius: 4px 0 0 4px; border-left: none;">
                            <option value="+20" data-flag="🇪🇬">+20</option>
                            <option value="+971" data-flag="🇦🇪">+971</option>
                            <option value="+966" data-flag="🇸🇦">+966</option>
                            <option value="+965" data-flag="🇰🇼">+965</option>
                            <option value="+974" data-flag="🇶🇦">+974</option>
                            <option value="+973" data-flag="🇧🇭">+973</option>
                            <option value="+968" data-flag="🇴🇲">+968</option>
                        </select>
                        <input type="tel" name="phone_body" id="login-phone-body" placeholder="<?php _e('رقم الهاتف', 'sukna'); ?>" required style="flex:1; border-radius: 0 4px 4px 0;">
                        <input type="hidden" name="phone" id="login-phone-full">
                    </div>
                </div>

                <div class="sukna-form-group">
                    <input type="password" name="password" required placeholder="<?php _e('كلمة المرور', 'sukna'); ?>" style="width:100%;">
                </div>

                <div id="login-error" style="display:none; padding:12px; background:#331111; color:#ff9999; border-radius:8px; margin-bottom:20px; font-size:0.9rem; text-align:center; font-weight:600;"></div>

                <button type="submit" class="sukna-btn" style="width:100%; height:55px; font-size:1.1rem; background:#D4AF37; color:#000 !important; border-radius:10px; border:none;">
                    <?php _e('تسجيل الدخول', 'sukna'); ?>
                </button>

                <div style="text-align:center; margin-top:25px; padding-top:20px; border-top: 1px solid #333;">
                    <button type="button" id="switch-to-register" style="background:none; border:none; color:#D4AF37; font-weight:700; cursor:pointer; font-size:1rem; font-family: inherit;">
                        <?php _e('إنشاء حساب جديد', 'sukna'); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Registration Form (Multi-step) -->
        <div id="sukna-register-container" style="display:none;">
            <form id="sukna-register-form">
                <div id="reg-step-1" class="reg-step">
                    <div style="display:flex; gap:10px; margin-bottom:20px;">
                        <input type="text" name="first_name" placeholder="<?php _e('الاسم الأول', 'sukna'); ?>" required style="flex:1;">
                        <input type="text" name="last_name" placeholder="<?php _e('اسم العائلة', 'sukna'); ?>" required style="flex:1;">
                    </div>
                </div>

                <div id="reg-step-2" class="reg-step" style="display:none;">
                    <div class="sukna-form-group">
                        <div class="phone-input-group">
                            <span id="reg-flag" class="country-flag-inside">🇪🇬</span>
                            <select id="reg-country-code" style="width: 80px; padding-right: 30px; border-radius: 4px 0 0 4px; border-left: none;">
                                <option value="+20" data-flag="🇪🇬">+20</option>
                                <option value="+971" data-flag="🇦🇪">+971</option>
                                <option value="+966" data-flag="🇸🇦">+966</option>
                                <option value="+965" data-flag="🇰🇼">+965</option>
                                <option value="+974" data-flag="🇶🇦">+974</option>
                                <option value="+973" data-flag="🇧🇭">+973</option>
                                <option value="+968" data-flag="🇴🇲">+968</option>
                            </select>
                            <input type="tel" name="phone_body" id="reg-phone-body" placeholder="<?php _e('رقم الهاتف', 'sukna'); ?>" required style="flex:1; border-radius: 0 4px 4px 0;">
                        </div>
                    </div>
                </div>

                <div id="reg-step-3" class="reg-step" style="display:none;">
                    <div class="sukna-form-group">
                        <input type="email" name="email" placeholder="<?php _e('البريد الإلكتروني (اختياري)', 'sukna'); ?>" style="width:100%;">
                    </div>
                </div>

                <div id="reg-step-4" class="reg-step" style="display:none;">
                    <div class="sukna-form-group">
                        <input type="password" name="password" id="reg-password" placeholder="<?php _e('كلمة المرور (8 أحرف على الأقل)', 'sukna'); ?>" required style="width:100%;">
                    </div>
                </div>

                <div id="reg-error" style="display:none; padding:12px; background:#331111; color:#ff9999; border-radius:8px; margin-bottom:20px; font-size:0.9rem; text-align:center; font-weight:600;"></div>

                <div style="display:flex; gap:10px; margin-top:20px;">
                    <button type="button" id="reg-prev" class="sukna-btn" style="flex:1; display:none; background:#333;"><?php _e('السابق', 'sukna'); ?></button>
                    <button type="button" id="reg-next" class="sukna-btn" style="flex:2; background:#D4AF37; color:#000 !important; border:none;"><?php _e('التالي', 'sukna'); ?></button>
                    <button type="submit" id="reg-submit" class="sukna-btn" style="flex:2; display:none; background:#D4AF37; color:#000 !important; border:none;"><?php _e('إتمام التسجيل', 'sukna'); ?></button>
                </div>

                <div style="text-align:center; margin-top:25px; padding-top:20px; border-top: 1px solid #333;">
                    <button type="button" id="switch-to-login" style="background:none; border:none; color:#64748b; font-weight:700; cursor:pointer; font-size:0.9rem; font-family: inherit;">
                        <?php _e('العودة لتسجيل الدخول', 'sukna'); ?>
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>
