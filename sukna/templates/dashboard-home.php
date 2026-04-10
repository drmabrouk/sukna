<?php
global $wpdb;
$current_user = Sukna_Auth::current_user();

if ( Sukna_Auth::is_admin() ) {
    $total_users = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sukna_staff");
    $total_properties = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sukna_properties");
    ?>
    <div class="sukna-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:#1e293b;"><?php _e('لوحة تحكم المدير', 'sukna'); ?></h2>
    </div>
    <div class="sukna-metrics-row">
        <div class="sukna-metric-card" style="border-right-color: #000000;">
            <div class="sukna-metric-icon" style="background: #f8f9fa; color: #000000;">
                <span class="dashicons dashicons-admin-users"></span>
            </div>
            <div class="sukna-metric-content">
                <div class="sukna-metric-title"><?php _e('إجمالي المستخدمين', 'sukna'); ?></div>
                <div class="sukna-metric-value"><?php echo number_format($total_users); ?></div>
            </div>
        </div>
        <div class="sukna-metric-card" style="border-right-color: #059669;">
            <div class="sukna-metric-icon" style="background: #ecfdf5; color: #059669;">
                <span class="dashicons dashicons-admin-home"></span>
            </div>
            <div class="sukna-metric-content">
                <div class="sukna-metric-title"><?php _e('إجمالي العقارات', 'sukna'); ?></div>
                <div class="sukna-metric-value"><?php echo number_format($total_properties); ?></div>
            </div>
        </div>
    </div>
    <?php
} elseif ( Sukna_Auth::is_investor() ) {
    $perf = Sukna_Investments::get_investor_performance($current_user->id);
    $wallet = Sukna_Investments::get_wallet_balance($current_user->id);
    $transactions = Sukna_Investments::get_transactions($current_user->id);
    $my_properties = Sukna_Investments::get_investor_properties($current_user->id);
    ?>
    <div class="sukna-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:#1e293b;"><?php _e('لوحة المستثمر', 'sukna'); ?></h2>
    </div>
    <div class="sukna-metrics-row">
        <div class="sukna-metric-card" style="border-right-color: #8b5cf6;">
            <div class="sukna-metric-icon" style="background: #f5f3ff; color: #8b5cf6;">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="sukna-metric-content">
                <div class="sukna-metric-title"><?php _e('المحفظة المالية', 'sukna'); ?></div>
                <div class="sukna-metric-value"><?php echo number_format($wallet, 2); ?> EGP</div>
            </div>
        </div>
        <div class="sukna-metric-card" style="border-right-color: #10b981;">
            <div class="sukna-metric-icon" style="background: #ecfdf5; color: #10b981;">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="sukna-metric-content">
                <div class="sukna-metric-title"><?php _e('إجمالي الاستثمارات', 'sukna'); ?></div>
                <div class="sukna-metric-value"><?php echo number_format($perf['total_invested'], 2); ?> EGP</div>
            </div>
        </div>
        <div class="sukna-metric-card" style="border-right-color: #f59e0b;">
            <div class="sukna-metric-icon" style="background: #fffbeb; color: #f59e0b;">
                <span class="dashicons dashicons-admin-home"></span>
            </div>
            <div class="sukna-metric-content">
                <div class="sukna-metric-title"><?php _e('عقارات مشارك بها', 'sukna'); ?></div>
                <div class="sukna-metric-value"><?php echo $perf['property_count']; ?></div>
            </div>
        </div>
    </div>

    <div class="sukna-grid" style="grid-template-columns: 1.5fr 1fr; gap: 20px;">
        <div class="sukna-card">
            <h3><?php _e('عقاراتي المستثمر بها', 'sukna'); ?></h3>
            <table class="sukna-table">
                <thead>
                    <tr>
                        <th><?php _e('العقار', 'sukna'); ?></th>
                        <th><?php _e('مساهمتي', 'sukna'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($my_properties as $prop): ?>
                        <tr>
                            <td><strong><?php echo esc_html($prop->name); ?></strong></td>
                            <td><?php echo number_format($prop->my_contribution, 2); ?> EGP</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="sukna-card">
            <h3><?php _e('آخر الحركات المالية', 'sukna'); ?></h3>
            <div style="max-height: 300px; overflow-y: auto;">
                <?php foreach($transactions as $t): ?>
                    <div style="padding: 10px 0; border-bottom: 1px solid #e2e8f0; display:flex; justify-content: space-between;">
                        <div>
                            <small style="display:block; color:#64748b;"><?php echo $t->transaction_date; ?></small>
                            <span style="font-size:0.85rem;"><?php echo esc_html($t->description); ?></span>
                        </div>
                        <span style="font-weight:700; color: <?php echo ($t->type == 'investment' || $t->type == 'payout') ? '#ef4444' : '#059669'; ?>">
                            <?php echo ($t->type == 'investment' || $t->type == 'payout') ? '-' : '+'; ?>
                            <?php echo number_format($t->amount, 2); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
} elseif ( Sukna_Auth::is_owner() ) {
    $properties = Sukna_Properties::get_all_properties(array('owner_id' => $current_user->id));
    $total_rooms = 0;
    foreach($properties as $p) $total_rooms += count(Sukna_Properties::get_rooms($p->id));
    ?>
    <div class="sukna-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:#1e293b;"><?php _e('لوحة المالك', 'sukna'); ?></h2>
    </div>
    <div class="sukna-metrics-row">
        <div class="sukna-metric-card" style="border-right-color: #D4AF37;">
            <div class="sukna-metric-icon" style="background: #f8f9fa; color: #D4AF37;">
                <span class="dashicons dashicons-admin-home"></span>
            </div>
            <div class="sukna-metric-content">
                <div class="sukna-metric-title"><?php _e('عقاراتي', 'sukna'); ?></div>
                <div class="sukna-metric-value"><?php echo count($properties); ?></div>
            </div>
        </div>
        <div class="sukna-metric-card" style="border-right-color: #f43f5e;">
            <div class="sukna-metric-icon" style="background: #fff1f2; color: #f43f5e;">
                <span class="dashicons dashicons-layout"></span>
            </div>
            <div class="sukna-metric-content">
                <div class="sukna-metric-title"><?php _e('إجمالي الوحدات/الغرف', 'sukna'); ?></div>
                <div class="sukna-metric-value"><?php echo $total_rooms; ?></div>
            </div>
        </div>
    </div>
    <?php
} elseif ( Sukna_Auth::is_tenant() ) {
    // Basic tenant view
    ?>
    <div class="sukna-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:#1e293b;"><?php _e('لوحة المستأجر', 'sukna'); ?></h2>
    </div>
    <div class="sukna-card">
        <p><?php _e('أهلاً بك. يمكنك متابعة دفعات الإيجار الخاصة بك من هنا قريباً.', 'sukna'); ?></p>
    </div>
    <?php
} else {
    ?>
    <div class="sukna-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:#1e293b;"><?php _e('لوحة المعلومات', 'sukna'); ?></h2>
    </div>
    <div class="sukna-card">
        <p><?php _e('برجاء التواصل مع الإدارة لتحديد دورك في النظام.', 'sukna'); ?></p>
    </div>
    <?php
}
?>
