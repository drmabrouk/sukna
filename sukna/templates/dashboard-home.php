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
        <div class="sukna-metric-card" style="border-right-color: #000;">
            <div class="sukna-metric-icon" style="background: #f8f9fa; color: #000;">
                <span class="dashicons dashicons-admin-users"></span>
            </div>
            <div class="sukna-metric-content">
                <div class="sukna-metric-title"><?php _e('إجمالي المستخدمين', 'sukna'); ?></div>
                <div class="sukna-metric-value"><?php echo number_format($total_users); ?></div>
            </div>
        </div>
        <div class="sukna-metric-card" style="border-right-color: #D4AF37;">
            <div class="sukna-metric-icon" style="background: #f8f9fa; color: #D4AF37;">
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
    $my_properties = Sukna_Investments::get_investor_properties($current_user->id);

    // Switch between properties
    $active_prop_id = isset($_GET['prop_id']) ? intval($_GET['prop_id']) : (isset($my_properties[0]) ? $my_properties[0]->id : 0);

    $active_property = null;
    $prop_perf = null;
    $rooms = array();
    $contribution = 0;

    if ($active_prop_id) {
        $active_property = Sukna_Properties::get_property($active_prop_id);
        $prop_perf = Sukna_Properties::get_property_performance($active_prop_id);
        $rooms = Sukna_Properties::get_rooms($active_prop_id);

        foreach($my_properties as $p) {
            if ($p->id == $active_prop_id) {
                $contribution = $p->my_contribution;
                break;
            }
        }
    }

    $total_profit = $wpdb->get_var($wpdb->prepare("SELECT SUM(amount) FROM {$wpdb->prefix}sukna_transactions WHERE user_id = %d AND type = 'dividend'", $current_user->id)) ?: 0;
    $overall_roi = ($perf['total_invested'] > 0) ? ($total_profit / $perf['total_invested']) * 100 : 0;
    ?>

    <div class="sukna-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:#1e293b;"><?php _e('لوحة تحكم المستثمر', 'sukna'); ?></h2>

        <?php if (count($my_properties) > 1): ?>
            <div style="background:#fff; padding:5px 15px; border-radius:8px; border:1px solid #e2e8f0; display:flex; align-items:center; gap:10px;">
                <span style="font-size:0.8rem; font-weight:700; color:#64748b;"><?php _e('تغيير العقار:', 'sukna'); ?></span>
                <select onchange="window.location.href='<?php echo add_query_arg('prop_id', '', add_query_arg('sukna_view', 'dashboard')); ?>' + this.value" style="border:none; font-weight:700; cursor:pointer;">
                    <?php foreach($my_properties as $p): ?>
                        <option value="<?php echo $p->id; ?>" <?php selected($active_prop_id, $p->id); ?>><?php echo esc_html($p->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
    </div>

    <!-- Overall Metrics -->
    <div class="sukna-metrics-row" style="margin-bottom:30px;">
        <div class="sukna-metric-card" style="border-right-color: #D4AF37;">
            <div class="sukna-metric-icon" style="background: #f8f9fa; color: #D4AF37;">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="sukna-metric-content">
                <div class="sukna-metric-title"><?php _e('المحفظة المالية', 'sukna'); ?></div>
                <div class="sukna-metric-value"><?php echo number_format($wallet, 2); ?> EGP</div>
            </div>
        </div>
        <div class="sukna-metric-card" style="border-right-color: #000;">
            <div class="sukna-metric-icon" style="background: #f8f9fa; color: #000;">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="sukna-metric-content">
                <div class="sukna-metric-title"><?php _e('إجمالي الاستثمارات', 'sukna'); ?></div>
                <div class="sukna-metric-value"><?php echo number_format($perf['total_invested'], 2); ?> EGP</div>
            </div>
        </div>
        <div class="sukna-metric-card" style="border-right-color: #059669;">
            <div class="sukna-metric-icon" style="background: #f8f9fa; color: #059669;">
                <span class="dashicons dashicons-chart-area"></span>
            </div>
            <div class="sukna-metric-content">
                <div class="sukna-metric-title"><?php _e('إجمالي الأرباح المحققة', 'sukna'); ?></div>
                <div class="sukna-metric-value"><?php echo number_format($total_profit, 2); ?> EGP <small style="font-size:0.75rem; color:#059669;">(<?php echo round($overall_roi, 1); ?>%)</small></div>
            </div>
        </div>
    </div>

    <?php if ($active_property):
        $rented_rooms = array_filter($rooms, function($r){ return $r->status === 'rented'; });
        $vacant_rooms = array_filter($rooms, function($r){ return $r->status === 'available'; });
        $share_percent = ($active_property->valuation > 0) ? ($contribution / $active_property->valuation) * 100 : 0;
        if ($active_property->property_type === 'leased' && $active_property->base_lease_value > 0) {
            $share_percent = ($contribution / $active_property->base_lease_value) * 100;
        }
    ?>
        <div class="sukna-grid" style="grid-template-columns: 2fr 1fr; gap: 25px;">
            <div class="sukna-column">
                <!-- Property Analytics -->
                <div class="sukna-card" style="border-top: 5px solid #D4AF37;">
                    <h3 style="display:flex; justify-content: space-between; align-items: center;">
                        <span><?php echo esc_html($active_property->name); ?> - <?php _e('تحليلات الأداء', 'sukna'); ?></span>
                        <span class="sukna-capsule" style="background:#000; font-size:0.75rem;"><?php echo ($active_property->property_type === 'leased') ? __('إدارة وتشغيل', 'sukna') : __('ملكية عقارية', 'sukna'); ?></span>
                    </h3>

                    <div class="sukna-grid" style="grid-template-columns: repeat(4, 1fr); gap:15px; margin-top:20px;">
                        <div style="background:#f8fafc; padding:15px; border-radius:10px; border:1px solid #e2e8f0; text-align:center;">
                            <small style="display:block; color:#64748b; margin-bottom:5px;"><?php _e('مساهمتي', 'sukna'); ?></small>
                            <span style="font-weight:800; font-size:1.1rem; color:#000;"><?php echo number_format($contribution); ?></span>
                        </div>
                        <div style="background:#f8fafc; padding:15px; border-radius:10px; border:1px solid #e2e8f0; text-align:center;">
                            <small style="display:block; color:#64748b; margin-bottom:5px;"><?php _e('نسبة الملكية', 'sukna'); ?></small>
                            <span style="font-weight:800; font-size:1.1rem; color:#D4AF37;"><?php echo round($share_percent, 2); ?>%</span>
                        </div>
                        <div style="background:#f8fafc; padding:15px; border-radius:10px; border:1px solid #e2e8f0; text-align:center;">
                            <small style="display:block; color:#64748b; margin-bottom:5px;"><?php _e('عائد العقار', 'sukna'); ?></small>
                            <span style="font-weight:800; font-size:1.1rem; color:#059669;"><?php echo $prop_perf['roi']; ?>%</span>
                        </div>
                        <div style="background:#f8fafc; padding:15px; border-radius:10px; border:1px solid #e2e8f0; text-align:center;">
                            <small style="display:block; color:#64748b; margin-bottom:5px;"><?php _e('صافي الربح الكلي', 'sukna'); ?></small>
                            <span style="font-weight:800; font-size:1.1rem; color:#000;"><?php echo number_format($prop_perf['net'], 2); ?></span>
                        </div>
                    </div>

                    <div style="margin-top:30px; padding:20px; background:#000; color:#fff; border-radius:12px; display:flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h4 style="margin:0; font-size:0.9rem; opacity:0.8;"><?php _e('أرباحي المستحقة من هذا العقار', 'sukna'); ?></h4>
                            <div style="font-size:1.8rem; font-weight:800; margin-top:5px; color:#D4AF37;"><?php echo number_format($prop_perf['net'] * ($share_percent / 100), 2); ?> EGP</div>
                        </div>
                        <span class="dashicons dashicons-chart-bar" style="font-size:40px; width:40px; height:40px; opacity:0.3;"></span>
                    </div>
                </div>

                <!-- Financial Transparency -->
                <div class="sukna-card">
                    <h3 style="margin-bottom:20px;"><?php _e('الشفافية المالية والتدفقات', 'sukna'); ?></h3>
                    <div style="display:flex; gap:20px;">
                        <div style="flex:1; border:1px solid #e2e8f0; border-radius:10px; padding:20px;">
                            <h4 style="margin:0 0 15px 0; font-size:0.85rem; color:#059669; display:flex; align-items:center; gap:8px;">
                                <span class="dashicons dashicons-arrow-down-alt" style="font-size:16px;"></span> <?php _e('الإيرادات (إيجارات الوحدات)', 'sukna'); ?>
                            </h4>
                            <div style="font-size:1.4rem; font-weight:800;"><?php echo number_format($prop_perf['income'], 2); ?> <small style="font-size:0.7rem; color:#64748b;">EGP</small></div>
                        </div>
                        <div style="flex:1; border:1px solid #e2e8f0; border-radius:10px; padding:20px;">
                            <h4 style="margin:0 0 15px 0; font-size:0.85rem; color:#ef4444; display:flex; align-items:center; gap:8px;">
                                <span class="dashicons dashicons-arrow-up-alt" style="font-size:16px;"></span> <?php _e('المصروفات التشغيلية', 'sukna'); ?>
                            </h4>
                            <div style="font-size:1.4rem; font-weight:800;"><?php echo number_format($prop_perf['costs'], 2); ?> <small style="font-size:0.7rem; color:#64748b;">EGP</small></div>
                        </div>
                    </div>
                </div>

                <!-- Activity Log -->
                <div class="sukna-card">
                    <h3 style="margin-bottom:20px;"><?php _e('سجل النشاطات العقارية', 'sukna'); ?></h3>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php
                        $logs = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sukna_activity_logs WHERE description LIKE %s ORDER BY action_date DESC LIMIT 50", '%' . $active_property->name . '%'));
                        if (empty($logs)): ?>
                            <p style="text-align:center; color:#64748b; padding:20px;"><?php _e('لا توجد سجلات حالية لهذا العقار', 'sukna'); ?></p>
                        <?php else: ?>
                            <?php foreach($logs as $log): ?>
                                <div style="padding:12px; border-bottom:1px solid #f1f5f9; display:flex; gap:15px; align-items: center;">
                                    <div style="width:8px; height:8px; border-radius:50%; background:#D4AF37;"></div>
                                    <div style="flex:1;">
                                        <div style="font-size:0.85rem; font-weight:600;"><?php echo esc_html($log->description); ?></div>
                                        <small style="color:#94a3b8;"><?php echo $log->action_date; ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="sukna-column">
                <!-- Room Status Visualization -->
                <div class="sukna-card" style="border-right: 4px solid #000;">
                    <h3 style="margin-bottom:20px;"><?php _e('حالة الوحدات الإيجارية', 'sukna'); ?></h3>
                    <div style="margin-bottom:25px; display:flex; align-items: center; justify-content: space-between;">
                        <div style="text-align:center;">
                            <div style="font-size:1.5rem; font-weight:800;"><?php echo count($rooms); ?></div>
                            <small style="color:#64748b;"><?php _e('الإجمالي', 'sukna'); ?></small>
                        </div>
                        <div style="text-align:center;">
                            <div style="font-size:1.5rem; font-weight:800; color:#ef4444;"><?php echo count($rented_rooms); ?></div>
                            <small style="color:#64748b;"><?php _e('مؤجرة', 'sukna'); ?></small>
                        </div>
                        <div style="text-align:center;">
                            <div style="font-size:1.5rem; font-weight:800; color:#059669;"><?php echo count($vacant_rooms); ?></div>
                            <small style="color:#64748b;"><?php _e('شاغرة', 'sukna'); ?></small>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: repeat(5, 1fr); gap:8px;">
                        <?php foreach($rooms as $r): ?>
                            <div title="<?php echo ($r->status === 'rented') ? __('مؤجرة', 'sukna') : __('شاغرة', 'sukna'); ?>"
                                 style="aspect-ratio:1/1; border-radius:4px; background:<?php echo ($r->status === 'rented') ? '#ef4444' : '#059669'; ?>; display:flex; align-items:center; justify-content:center; color:#fff; font-size:0.7rem; font-weight:700;">
                                <?php echo $r->room_number; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="margin-top:25px; padding-top:20px; border-top:1px solid #eee;">
                        <h4 style="font-size:0.8rem; margin:0 0 10px 0; color:#64748b;"><?php _e('قائمة الوحدات الشاغرة:', 'sukna'); ?></h4>
                        <div style="display:flex; flex-wrap:wrap; gap:5px;">
                            <?php foreach($vacant_rooms as $vr): ?>
                                <span style="background:#f1f5f9; padding:2px 8px; border-radius:4px; font-size:0.75rem; font-weight:700;">#<?php echo $vr->room_number; ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Portfolio Distribution -->
                <div class="sukna-card">
                    <h3 style="margin-bottom:20px;"><?php _e('توزيع المحفظة', 'sukna'); ?></h3>
                    <?php foreach($my_properties as $p):
                        $pct = ($perf['total_invested'] > 0) ? ($p->my_contribution / $perf['total_invested']) * 100 : 0;
                    ?>
                        <div style="margin-bottom:15px;">
                            <div style="display:flex; justify-content: space-between; font-size:0.8rem; margin-bottom:5px;">
                                <span><?php echo esc_html($p->name); ?></span>
                                <strong><?php echo round($pct, 1); ?>%</strong>
                            </div>
                            <div style="height:6px; background:#f1f5f9; border-radius:3px; overflow:hidden;">
                                <div style="height:100%; background:<?php echo ($p->id == $active_prop_id) ? '#D4AF37' : '#000'; ?>; width:<?php echo $pct; ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
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
        <div class="sukna-metric-card" style="border-right-color: #000;">
            <div class="sukna-metric-icon" style="background: #f8f9fa; color: #000;">
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
    ?>
    <div class="sukna-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:#1e293b;"><?php _e('لوحة المستأجر', 'sukna'); ?></h2>
    </div>
    <div class="sukna-card" style="border-radius: 12px;">
        <p><?php _e('أهلاً بك. يمكنك متابعة دفعات الإيجار الخاصة بك من هنا قريباً.', 'sukna'); ?></p>
    </div>
    <?php
} else {
    ?>
    <div class="sukna-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:#1e293b;"><?php _e('لوحة المعلومات', 'sukna'); ?></h2>
    </div>
    <div class="sukna-card" style="border-radius: 12px;">
        <p><?php _e('برجاء التواصل مع الإدارة لتحديد دورك في النظام.', 'sukna'); ?></p>
    </div>
    <?php
}
?>
