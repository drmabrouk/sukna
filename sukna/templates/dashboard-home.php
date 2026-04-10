<?php
global $wpdb;
$table_users = $wpdb->prefix . 'sukna_staff';

// Statistics
$total_users = $wpdb->get_var("SELECT COUNT(*) FROM $table_users");

?>

<div class="sukna-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:#1e293b;"><?php _e('لوحة المعلومات', 'sukna'); ?></h2>
    <div style="display:flex; gap:10px;">
        <a href="<?php echo add_query_arg('sukna_view', 'users'); ?>" class="sukna-btn"><span class="dashicons dashicons-admin-users" style="margin-left:5px;"></span><?php _e('إدارة المستخدمين', 'sukna'); ?></a>
    </div>
</div>

<!-- Horizontal Professional Metrics -->
<div class="sukna-metrics-row">
    <div class="sukna-metric-card" style="border-right-color: #2563eb;">
        <div class="sukna-metric-icon" style="background: #eff6ff; color: #2563eb;">
            <span class="dashicons dashicons-admin-users"></span>
        </div>
        <div class="sukna-metric-content">
            <div class="sukna-metric-title"><?php _e('إجمالي المستخدمين', 'sukna'); ?></div>
            <div class="sukna-metric-value"><?php echo number_format($total_users); ?></div>
            <div style="font-size: 0.75rem; color: #64748b; font-weight: 600;"><?php _e('مستخدم مسجل', 'sukna'); ?></div>
        </div>
    </div>
</div>
