<div class="sukna-dashboard" id="sukna-system-root">
    <aside class="sukna-sidebar">
        <div class="sukna-sidebar-logo">
            <?php
                global $wpdb;
                $logo_url = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}sukna_settings WHERE setting_key = 'company_logo'");
                $system_name = $wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}sukna_settings WHERE setting_key = 'system_name'") ?: 'Sukna';

                if ( $logo_url ) : ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($system_name); ?>" style="max-width: 100%; height: auto; display: block; margin: 0 auto;">
                <?php else : ?>
                    <h2><?php echo esc_html($system_name); ?></h2>
                <?php endif;
            ?>
        </div>
        <nav class="sukna-sidebar-nav">
            <a href="<?php echo add_query_arg('sukna_view', 'dashboard'); ?>" class="<?php echo (!isset($_GET['sukna_view']) || $_GET['sukna_view'] == 'dashboard') ? 'active' : ''; ?>">
                <span class="dashicons dashicons-dashboard"></span> <?php _e('لوحة المعلومات', 'sukna'); ?>
            </a>

            <?php if ( Sukna_Auth::is_admin() || Sukna_Auth::is_owner() ) : ?>
                <a href="<?php echo add_query_arg('sukna_view', 'properties'); ?>" class="<?php echo (isset($_GET['sukna_view']) && $_GET['sukna_view'] == 'properties') ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-admin-home"></span> <?php _e('إدارة العقارات', 'sukna'); ?>
                </a>
            <?php endif; ?>

            <?php if ( Sukna_Auth::is_admin() ) : ?>
                <a href="<?php echo add_query_arg('sukna_view', 'users'); ?>" class="<?php echo (isset($_GET['sukna_view']) && $_GET['sukna_view'] == 'users') ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-admin-users"></span> <?php _e('إدارة المستخدمين', 'sukna'); ?>
                </a>
                <a href="<?php echo add_query_arg('sukna_view', 'settings'); ?>" class="<?php echo (isset($_GET['sukna_view']) && $_GET['sukna_view'] == 'settings') ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-admin-generic"></span> <?php _e('الإعدادات', 'sukna'); ?>
                </a>
            <?php endif; ?>
        </nav>

        <div class="sukna-sidebar-footer" style="padding: 10px; border-top: 1px solid #334155; margin-top: auto;">
            <div class="sukna-sidebar-controls" style="display: flex; justify-content: space-around; align-items: center; gap: 2px;">
                <button id="sukna-refresh-btn" class="sidebar-ctrl-icon" style="background:none; border:none; color:#94a3b8; cursor:pointer; padding:5px; flex:1; display:flex; flex-direction:column; align-items:center;">
                    <span class="dashicons dashicons-update" style="font-size:18px; width:18px; height:18px;"></span>
                    <small style="font-size:0.6rem; margin-top:2px;"><?php _e('تحديث', 'sukna'); ?></small>
                </button>
                <button id="sukna-logout-btn" class="sidebar-ctrl-icon logout" title="<?php _e('خروج', 'sukna'); ?>" style="background:none; border:none; color:#ef4444 !important; cursor:pointer; padding:5px; flex:1; display:flex; flex-direction:column; align-items:center;">
                    <span class="dashicons dashicons-no-alt" style="font-size:18px; width:18px; height:18px;"></span>
                    <small style="font-size:0.6rem; margin-top:2px;"><?php _e('خروج', 'sukna'); ?></small>
                </button>
            </div>
        </div>
    </aside>

    <!-- Mobile Bottom Bar (Fixed) -->
    <div class="sukna-mobile-bottom-bar" style="display:none;">
        <button id="sukna-mobile-refresh-btn" class="sidebar-ctrl-icon" title="<?php _e('تحديث', 'sukna'); ?>">
            <span class="dashicons dashicons-update"></span>
            <small><?php _e('تحديث', 'sukna'); ?></small>
        </button>
        <button id="sukna-mobile-logout-btn" class="sidebar-ctrl-icon logout" title="<?php _e('خروج', 'sukna'); ?>">
            <span class="dashicons dashicons-no-alt"></span>
            <small><?php _e('خروج', 'sukna'); ?></small>
        </button>
    </div>

    <div id="sukna-sync-loader" style="display:none; position:fixed; top:20px; left:50%; transform:translateX(-50%); background:#000000; color:#fff; padding:10px 20px; border-radius:30px; z-index:10000; box-shadow:0 4px 12px rgba(0,0,0,0.2); font-weight:600;">
        <span class="dashicons dashicons-update spin" style="margin-left:8px; vertical-align:middle;"></span>
        <span class="loader-text"><?php _e('جارٍ تحميل البيانات...', 'sukna'); ?></span>
    </div>

    <main class="sukna-main-content">
        <div id="sukna-install-banner" style="display:none; background:#D4AF37; color:#000; padding:15px 25px; border-radius:12px; margin-bottom:25px; align-items:center; justify-content:space-between; font-weight:700; box-shadow:0 4px 12px rgba(212,175,55,0.3);">
            <div style="display:flex; align-items:center; gap:15px;">
                <span class="dashicons dashicons-smartphone" style="font-size:24px; width:24px; height:24px;"></span>
                <span><?php _e('تثبيت تطبيق سكنى على هاتفك للوصول السريع', 'sukna'); ?></span>
            </div>
            <button onclick="window.suknaInstallPrompt.prompt()" class="sukna-btn" style="background:#000; border:none; padding:8px 20px; font-size:0.85rem;"><?php _e('تثبيت الآن', 'sukna'); ?></button>
        </div>
        <div class="sukna-content-inner">
