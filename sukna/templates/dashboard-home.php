<?php
global $wpdb;
$current_user = Sukna_Auth::current_user();
if ( Sukna_Auth::is_admin() ) {
    $total_users = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sukna_staff");
    $total_properties = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sukna_properties");
    $stats = Sukna_Investments::get_system_wide_stats('live');
    ?>
    <div class="sukna-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:#1e293b;"><?php _e('لوحة تحكم النظام', 'sukna'); ?></h2>
    </div>

    <!-- Administrative Overview Metrics -->
    <div class="sukna-metrics-grid" style="margin-bottom:25px;">
        <div class="sukna-metric-card">
            <div class="sukna-metric-icon" style="background: #f8fafc; color: #000;">
                <span class="dashicons dashicons-admin-home"></span>
            </div>
            <div class="sukna-metric-content">
                <div class="sukna-metric-title"><?php _e('إجمالي العقارات', 'sukna'); ?></div>
                <div class="sukna-metric-value"><?php echo number_format($total_properties); ?></div>
                <small style="color:#64748b; font-size:0.6rem;"><?php _e('تحت الإدارة', 'sukna'); ?></small>
            </div>
        </div>
        <div class="sukna-metric-card">
            <div class="sukna-metric-icon" style="background: #f8fafc; color: #D4AF37;">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="sukna-metric-content">
                <div class="sukna-metric-title"><?php _e('رأس المال المستثمر', 'sukna'); ?></div>
                <div class="sukna-metric-value"><?php echo number_format($stats['total_invested']); ?></div>
                <small style="color:#059669; font-size:0.6rem;"><?php _e('تمويل فعلي', 'sukna'); ?></small>
            </div>
        </div>
        <div class="sukna-metric-card">
            <div class="sukna-metric-icon" style="background: #f8fafc; color: #059669;">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="sukna-metric-content">
                <div class="sukna-metric-title"><?php _e('الإشغال الكلي', 'sukna'); ?></div>
                <div class="sukna-metric-value"><?php echo $stats['occupancy_rate']; ?>%</div>
            </div>
        </div>
        <div class="sukna-metric-card">
            <div class="sukna-metric-icon" style="background: #f8fafc; color: #000;">
                <span class="dashicons dashicons-chart-area"></span>
            </div>
            <div class="sukna-metric-content">
                <div class="sukna-metric-title"><?php _e('النمو الشهري', 'sukna'); ?></div>
                <div class="sukna-metric-value" style="color:<?php echo $stats['growth_rate'] >= 0 ? '#059669' : '#ef4444'; ?>;">
                    <?php echo ($stats['growth_rate'] >= 0 ? '+' : '') . $stats['growth_rate']; ?>%
                </div>
            </div>
        </div>
    </div>


    <div class="sukna-grid" style="grid-template-columns: 2fr 1fr; gap: 25px;">
        <div class="sukna-card" style="border-top: 5px solid #000;">
            <h3 style="margin-bottom:20px;"><?php _e('الأداء التشغيلي والمالي', 'sukna'); ?></h3>
            <div class="sukna-grid" style="grid-template-columns: repeat(4, 1fr); gap: 15px;">
                <div style="background:#f8fafc; padding:12px; border-radius:12px; border:1px solid #e2e8f0;">
                    <small style="display:block; color:#64748b; margin-bottom:5px;"><?php _e('المستثمرون', 'sukna'); ?></small>
                    <div style="font-size:1.1rem; font-weight:800;"><?php echo number_format($stats['investor_count']); ?></div>
                </div>
                <div style="background:#f8fafc; padding:12px; border-radius:12px; border:1px solid #e2e8f0;">
                    <small style="display:block; color:#64748b; margin-bottom:5px;"><?php _e('المستخدمون', 'sukna'); ?></small>
                    <div style="font-size:1.1rem; font-weight:800;"><?php echo number_format($total_users); ?></div>
                </div>
                <div style="background:#f8fafc; padding:12px; border-radius:12px; border:1px solid #e2e8f0;">
                    <small style="display:block; color:#64748b; margin-bottom:5px;"><?php _e('العقود النشطة', 'sukna'); ?></small>
                    <div style="font-size:1.1rem; font-weight:800; color:#059669;"><?php echo number_format($stats['active_contracts']); ?></div>
                </div>
                <div style="background:#f8fafc; padding:12px; border-radius:12px; border:1px solid #e2e8f0;">
                    <small style="display:block; color:#64748b; margin-bottom:5px;"><?php _e('العقود المنتهية', 'sukna'); ?></small>
                    <div style="font-size:1.1rem; font-weight:800; color:#ef4444;"><?php echo number_format($stats['expired_contracts']); ?></div>
                </div>
            </div>
            <div class="sukna-grid" style="grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top:15px;">
                <div style="background:#f8fafc; padding:20px; border-radius:12px; border:1px solid #e2e8f0;">
                    <small style="display:block; color:#64748b; margin-bottom:5px;"><?php _e('إجمالي الإيرادات', 'sukna'); ?></small>
                    <div style="font-size:1.2rem; font-weight:800; color:#059669;"><?php echo number_format($stats['total_revenue']); ?></div>
                    <small style="font-size:0.65rem; color:#94a3b8;"><?php _e('التدفقات النقدية الداخلة', 'sukna'); ?></small>
                </div>
                <div style="background:#f8fafc; padding:20px; border-radius:12px; border:1px solid #e2e8f0;">
                    <small style="display:block; color:#64748b; margin-bottom:5px;"><?php _e('المصروفات التشغيلية', 'sukna'); ?></small>
                    <div style="font-size:1.2rem; font-weight:800; color:#ef4444;"><?php echo number_format($stats['total_expenses']); ?></div>
                    <small style="font-size:0.65rem; color:#94a3b8;"><?php _e('التدفقات النقدية الخارجة', 'sukna'); ?></small>
                </div>
                <div style="background:#000; padding:20px; border-radius:12px; border:1px solid #000;">
                    <small style="display:block; color:#94a3b8; margin-bottom:5px;"><?php _e('صافي الربح', 'sukna'); ?></small>
                    <div style="font-size:1.2rem; font-weight:800; color:#D4AF37;"><?php echo number_format($stats['net_profit']); ?></div>
                    <small style="font-size:0.65rem; color:#94a3b8;"><?php _e('صافي حركة النقد', 'sukna'); ?></small>
                </div>
            </div>

        </div>

        <div class="sukna-card">
            <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin:0;"><?php _e('آخر 5 عمليات', 'sukna'); ?></h3>
                <a href="<?php echo add_query_arg('sukna_view', 'settings'); ?>#tab-audit" style="font-size:0.7rem; color:#D4AF37; font-weight:700; text-decoration:none;"><?php _e('عرض الكل', 'sukna'); ?></a>
            </div>
            <div style="max-height: 400px; overflow-y: auto;">
                <?php
                $recent_logs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sukna_activity_logs ORDER BY action_date DESC LIMIT 5");
                foreach($recent_logs as $log): ?>
                    <div style="padding: 10px 0; border-bottom: 1px solid #f1f5f9;">
                        <small style="display:block; color:#64748b;"><?php echo date('H:i - Y/m/d', strtotime($log->action_date)); ?></small>
                        <div style="font-size:0.8rem; font-weight:600;"><?php echo esc_html($log->description); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
} elseif ( Sukna_Auth::is_investor() ) {
    $perf = Sukna_Investments::get_investor_performance($current_user->id);
    $wallet_obj = Sukna_Investments::get_wallet($current_user->id);
    $wallet = $wallet_obj->balance;
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

        <div style="display:flex; gap:10px;">
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
    </div>

    <!-- Wallet Section -->
    <div class="sukna-card" style="border-top: 5px solid #D4AF37; margin-bottom: 25px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="margin:0;"><?php _e('المحفظة الاستثمارية والسيولة', 'sukna'); ?></h3>
            <span class="sukna-capsule" style="background:#000; color:#D4AF37;"><?php _e('حساب حقيقي (AED)', 'sukna'); ?></span>
        </div>

        <div class="sukna-grid" style="grid-template-columns: 1.5fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div style="background:#000; color:#fff; padding:25px; border-radius:15px; position:relative; overflow:hidden;">
                <div style="position:relative; z-index:2;">
                    <small style="opacity:0.7; display:block; margin-bottom:5px;"><?php _e('صافي الرصيد الحالي', 'sukna'); ?></small>
                    <div style="font-size:2.2rem; font-weight:800; color:#D4AF37; line-height:1;"><?php echo number_format($wallet_obj->balance, 2); ?></div>
                    <div style="margin-top:10px; font-size:0.75rem; opacity:0.6;"><?php _e('بعد خصم كافة الالتزامات والمصاريف', 'sukna'); ?></div>
                </div>
                <span class="dashicons dashicons-shield" style="position:absolute; right:-10px; bottom:-10px; font-size:100px; width:100px; height:100px; opacity:0.05;"></span>
            </div>
            <div style="background:#f0fdf4; border:1px solid #dcfce7; padding:20px; border-radius:12px; display:flex; flex-direction:column; justify-content:center;">
                <small style="color:#166534; font-weight:700; margin-bottom:8px; display:flex; align-items:center; gap:5px;">
                    <span class="dashicons dashicons-yes-alt" style="font-size:16px; width:16px; height:16px;"></span> <?php _e('متاح للسحب الآن', 'sukna'); ?>
                </small>
                <div style="font-size:1.6rem; font-weight:800; color:#15803d;"><?php echo number_format($wallet_obj->available_balance, 2); ?></div>
                <small style="color:#166534; opacity:0.6; font-size:0.65rem; margin-top:5px;"><?php _e('* محول من دورات سابقة', 'sukna'); ?></small>
            </div>
            <div style="background:#fff7ed; border:1px solid #ffedd5; padding:20px; border-radius:12px; display:flex; flex-direction:column; justify-content:center;">
                <small style="color:#9a3412; font-weight:700; margin-bottom:8px; display:flex; align-items:center; gap:5px;">
                    <span class="dashicons dashicons-clock" style="font-size:16px; width:16px; height:16px;"></span> <?php _e('أرباح الشهر (معلقة)', 'sukna'); ?>
                </small>
                <div style="font-size:1.6rem; font-weight:800; color:#c2410c;"><?php echo number_format($wallet_obj->pending_balance, 2); ?></div>
                <small style="color:#9a3412; opacity:0.6; font-size:0.65rem; margin-top:5px;"><?php _e('* تصدر في نهاية الشهر', 'sukna'); ?></small>
            </div>
        </div>

        <div class="sukna-grid" style="grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom:20px;">
            <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:15px; border-radius:10px;">
                <small style="color:#64748b; display:block; margin-bottom:5px;"><?php _e('إجمالي التحصيلات (عوائد)', 'sukna'); ?></small>
                <?php $total_in = $wpdb->get_var($wpdb->prepare("SELECT SUM(amount) FROM {$wpdb->prefix}sukna_transactions WHERE user_id = %d AND amount > 0", $current_user->id)) ?: 0; ?>
                <div style="font-weight:800; color:#059669;"><?php echo number_format($total_in, 2); ?></div>
            </div>
            <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:15px; border-radius:10px;">
                <small style="color:#64748b; display:block; margin-bottom:5px;"><?php _e('إجمالي الاستقطاعات', 'sukna'); ?></small>
                <?php $total_out = $wpdb->get_var($wpdb->prepare("SELECT SUM(amount) FROM {$wpdb->prefix}sukna_transactions WHERE user_id = %d AND amount < 0", $current_user->id)) ?: 0; ?>
                <div style="font-weight:800; color:#ef4444;"><?php echo number_format(abs($total_out), 2); ?></div>
            </div>
            <div style="background:#fefce8; border:1px solid #fef08a; padding:15px; border-radius:10px;">
                <small style="color:#854d0e; display:block; margin-bottom:5px;"><?php _e('المبالغ المحجوزة', 'sukna'); ?></small>
                <div style="font-weight:800; color:#854d0e;"><?php echo number_format($wallet_obj->reserved_balance, 2); ?></div>
            </div>
        </div>
        <div class="sukna-metrics-grid">
            <div class="sukna-metric-card" style="background:#f8fafc;">
                <div class="sukna-metric-content">
                    <div class="sukna-metric-title"><?php _e('رأس المال المستثمر', 'sukna'); ?></div>
                    <div class="sukna-metric-value"><?php echo number_format($perf['total_invested']); ?></div>
                </div>
            </div>
            <div class="sukna-metric-card" style="background:#f8fafc;">
                <div class="sukna-metric-content">
                    <div class="sukna-metric-title"><?php _e('إجمالي الأرباح المستلمة', 'sukna'); ?></div>
                    <div class="sukna-metric-value"><?php echo number_format($total_profit); ?></div>
                </div>
            </div>
            <div class="sukna-metric-card" style="background:#f8fafc;">
                <div class="sukna-metric-content">
                    <div class="sukna-metric-title"><?php _e('عائد ROI تراكمي', 'sukna'); ?></div>
                    <div class="sukna-metric-value"><?php echo round($overall_roi, 2); ?>%</div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($active_property):
        $rented_rooms = array_filter($rooms, function($r){ return $r->status === 'rented'; });
        $vacant_rooms = array_filter($rooms, function($r){ return $r->status === 'available'; });
        $total_project_cost = floatval($active_property->base_value) + floatval($active_property->total_setup_cost);
        $share_percent = ($total_project_cost > 0) ? ($contribution / $total_project_cost) * 100 : 0;
    ?>
        <div class="sukna-grid" style="grid-template-columns: 2fr 1fr; gap: 25px;">
            <div class="sukna-column">
                <!-- Property Analytics -->
                <div class="sukna-card" style="border-top: 5px solid #D4AF37;">
                    <h3 style="display:flex; justify-content: space-between; align-items: center;">
                        <span><?php echo esc_html($active_property->name); ?> - <?php _e('تحليلات الأداء', 'sukna'); ?></span>
                        <span class="sukna-capsule" style="background:#000; font-size:0.75rem;"><?php echo ($active_property->property_type === 'leased') ? __('إدارة وتشغيل', 'sukna') : __('ملكية عقارية', 'sukna'); ?></span>
                    </h3>

                    <div class="sukna-grid sukna-analytics-grid" style="grid-template-columns: repeat(4, 1fr); gap:15px; margin-top:20px;">
                        <div style="background:#f8fafc; padding:12px; border-radius:10px; border:1px solid #e2e8f0; text-align:center;">
                            <small style="display:block; color:#64748b; margin-bottom:5px;"><?php _e('مساهمتي', 'sukna'); ?></small>
                            <span style="font-weight:800; font-size:0.95rem; color:#000;"><?php echo number_format($contribution); ?></span>
                        </div>
                        <div style="background:#f8fafc; padding:12px; border-radius:10px; border:1px solid #e2e8f0; text-align:center;">
                            <small style="display:block; color:#64748b; margin-bottom:5px;"><?php _e('نسبة الملكية', 'sukna'); ?></small>
                            <span style="font-weight:800; font-size:0.95rem; color:#D4AF37;"><?php echo round($share_percent, 2); ?>%</span>
                        </div>
                        <div style="background:#f8fafc; padding:12px; border-radius:10px; border:1px solid #e2e8f0; text-align:center;">
                            <small style="display:block; color:#64748b; margin-bottom:5px;"><?php _e(' ROI المشروع', 'sukna'); ?></small>
                            <span style="font-weight:800; font-size:0.95rem; color:#059669;"><?php echo $prop_perf['roi']; ?>%</span>
                        </div>
                        <div style="background:#f8fafc; padding:12px; border-radius:10px; border:1px solid #e2e8f0; text-align:center;">
                            <small style="display:block; color:#64748b; margin-bottom:5px;"><?php _e('إشغال العقار', 'sukna'); ?></small>
                            <span style="font-weight:800; font-size:0.95rem; color:#000;"><?php echo round((count($rented_rooms) / (count($rooms) ?: 1)) * 100); ?>%</span>
                        </div>
                    </div>

                    <div style="margin-top:30px; padding:20px; background:#000; color:#fff; border-radius:12px; display:flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h4 style="margin:0; font-size:0.9rem; opacity:0.8;"><?php _e('أرباحي الشهرية (الواقع الفعلي)', 'sukna'); ?></h4>
                            <div style="font-size:1.8rem; font-weight:800; margin-top:5px; color:#D4AF37;"><?php echo number_format($prop_perf['monthly_net'] * ($share_percent / 100), 2); ?> AED</div>
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
                            <div style="font-size:1.4rem; font-weight:800;"><?php echo number_format($prop_perf['income'], 2); ?> <small style="font-size:0.7rem; color:#64748b;">AED</small></div>
                        </div>
                        <div style="flex:1; border:1px solid #e2e8f0; border-radius:10px; padding:20px;">
                            <h4 style="margin:0 0 15px 0; font-size:0.85rem; color:#ef4444; display:flex; align-items:center; gap:8px;">
                                <span class="dashicons dashicons-arrow-up-alt" style="font-size:16px;"></span> <?php _e('تكاليف التجهيز والتشغيل', 'sukna'); ?>
                            </h4>
                            <div style="font-size:1.4rem; font-weight:800;"><?php echo number_format($prop_perf['expenses'], 2); ?> <small style="font-size:0.7rem; color:#64748b;">AED</small></div>
                        </div>
                    </div>
                </div>

                <!-- Transaction History -->
                <div class="sukna-card">
                    <h3 style="margin-bottom:20px;"><?php _e('سجل الحركات المالية (العوائد والخصومات)', 'sukna'); ?></h3>
                    <div class="sukna-table-container">
                    <table class="sukna-table" style="font-size:0.85rem;">
                        <thead>
                            <tr>
                                <th><?php _e('التاريخ', 'sukna'); ?></th>
                                <th><?php _e('الوصف', 'sukna'); ?></th>
                                <th><?php _e('النوع', 'sukna'); ?></th>
                                <th><?php _e('المبلغ', 'sukna'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $txs = Sukna_Investments::get_transactions($current_user->id);
                            if (empty($txs)): ?>
                                <tr><td colspan="4" style="text-align:center; padding:20px;"><?php _e('لا توجد حركات مالية مسجلة', 'sukna'); ?></td></tr>
                            <?php else: ?>
                                <?php foreach($txs as $tx): ?>
                                    <tr>
                                        <td><?php echo date('Y/m/d', strtotime($tx->transaction_date)); ?></td>
                                        <td><?php echo esc_html($tx->description); ?></td>
                                        <td>
                                            <?php
                                            $types = array('dividend' => 'ربح/عائد', 'investment' => 'مساهمة', 'payout' => 'سحب رصيد');
                                            echo $types[$tx->type] ?? $tx->type;
                                            ?>
                                        </td>
                                        <td style="font-weight:700; color:<?php echo ($tx->type === 'dividend') ? '#059669' : (($tx->type === 'investment' || $tx->type === 'payout') ? '#ef4444' : '#000'); ?>;">
                                            <?php echo ($tx->type === 'dividend') ? '+' : '-'; ?> <?php echo number_format($tx->amount); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
                                 style="aspect-ratio:1/1; border-radius:6px; background:<?php echo ($r->status === 'rented') ? '#ef4444' : '#059669'; ?>; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#fff; padding:5px; border: 1px solid rgba(0,0,0,0.1);">
                                <span style="font-weight:800; font-size:0.8rem; line-height:1;"><?php echo $r->room_number; ?></span>
                                <span style="font-size:0.5rem; opacity:0.9; margin-top:2px; font-weight:600;"><?php echo number_format($r->rental_price); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="margin-top:25px; padding-top:20px; border-top:1px solid #eee;">
                        <div style="margin-bottom:15px;">
                            <h4 style="font-size:0.8rem; margin:0 0 8px 0; color:#ef4444;"><?php _e('قائمة الوحدات المؤجرة:', 'sukna'); ?></h4>
                            <div style="display:flex; flex-wrap:wrap; gap:5px;">
                                <?php foreach($rented_rooms as $rr): ?>
                                    <span style="background:#fef2f2; border:1px solid #fee2e2; color:#ef4444; padding:2px 8px; border-radius:4px; font-size:0.75rem; font-weight:700;">#<?php echo $rr->room_number; ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div>
                            <h4 style="font-size:0.8rem; margin:0 0 8px 0; color:#059669;"><?php _e('قائمة الوحدات الشاغرة:', 'sukna'); ?></h4>
                            <div style="display:flex; flex-wrap:wrap; gap:5px;">
                                <?php foreach($vacant_rooms as $vr): ?>
                                    <span style="background:#f0fdf4; border:1px solid #dcfce7; color:#059669; padding:2px 8px; border-radius:4px; font-size:0.75rem; font-weight:700;">#<?php echo $vr->room_number; ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    <?php endif; ?>
    <?php
} elseif ( Sukna_Auth::is_owner() ) {
    $properties = Sukna_Properties::get_all_properties(array('owner_id' => $current_user->id));
    $total_rooms = 0;
    $total_occupied = 0;
    $total_income = 0;
    $total_expenses = 0;

    foreach($properties as $p) {
        $rooms = Sukna_Properties::get_rooms($p->id);
        $perf = Sukna_Properties::get_property_performance($p->id);
        $total_rooms += count($rooms);
        $total_occupied += count(array_filter($rooms, function($r){ return $r->status === 'rented'; }));
        $total_income += $perf['monthly_income'];
        $total_expenses += $perf['expenses'];
    }
    ?>
    <div class="sukna-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:#1e293b;"><?php _e('لوحة المالك / مدير العقارات', 'sukna'); ?></h2>
    </div>

    <!-- Owner Overview Metrics -->
    <div class="sukna-metrics-grid" style="margin-bottom:25px;">
        <div class="sukna-metric-card">
            <div class="sukna-metric-icon" style="background: #f8fafc; color: #000;">
                <span class="dashicons dashicons-admin-home"></span>
            </div>
            <div class="sukna-metric-content">
                <div class="sukna-metric-title"><?php _e('عقارات مدارة', 'sukna'); ?></div>
                <div class="sukna-metric-value"><?php echo count($properties); ?></div>
            </div>
        </div>
        <div class="sukna-metric-card">
            <div class="sukna-metric-icon" style="background: #f8fafc; color: #D4AF37;">
                <span class="dashicons dashicons-layout"></span>
            </div>
            <div class="sukna-metric-content">
                <div class="sukna-metric-title"><?php _e('الإشغال العام', 'sukna'); ?></div>
                <div class="sukna-metric-value"><?php echo round(($total_occupied / ($total_rooms ?: 1)) * 100); ?>%</div>
            </div>
        </div>
        <div class="sukna-metric-card">
            <div class="sukna-metric-icon" style="background: #f8fafc; color: #059669;">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="sukna-metric-content">
                <div class="sukna-metric-title"><?php _e('دخل شهري', 'sukna'); ?></div>
                <div class="sukna-metric-value"><?php echo number_format($total_income); ?></div>
            </div>
        </div>
        <div class="sukna-metric-card">
            <div class="sukna-metric-icon" style="background: #f8fafc; color: #000;">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="sukna-metric-content">
                <div class="sukna-metric-title"><?php _e('هامش الربح', 'sukna'); ?></div>
                <div class="sukna-metric-value"><?php echo $total_income > 0 ? round((($total_income - $total_expenses) / $total_income) * 100) : 0; ?>%</div>
            </div>
        </div>
    </div>

    <div class="sukna-grid" style="grid-template-columns: 2fr 1fr; gap: 25px;">
        <div class="sukna-card" style="border-top: 5px solid #D4AF37;">
            <h3 style="margin-bottom:20px;"><?php _e('أداء العقارات المدارة', 'sukna'); ?></h3>
            <div class="sukna-table-container">
            <table class="sukna-table">
                <thead>
                    <tr>
                        <th><?php _e('العقار', 'sukna'); ?></th>
                        <th><?php _e('الإشغال', 'sukna'); ?></th>
                        <th><?php _e('عقود (نشط/منته)', 'sukna'); ?></th>
                        <th><?php _e('دخل/ربح (شهري)', 'sukna'); ?></th>
                        <th><?php _e('ROI', 'sukna'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($properties as $p):
                        $rooms = Sukna_Properties::get_rooms($p->id);
                        $occ_count = count(array_filter($rooms, function($r){ return $r->status === 'rented'; }));
                        $perf = Sukna_Properties::get_property_performance($p->id);
                    ?>
                        <tr>
                            <td>
                                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                    <div>
                                        <strong><?php echo esc_html($p->name); ?></strong><br>
                                        <small style="color:#64748b;"><?php _e('متوسط السعر:', 'sukna'); ?> <?php echo number_format($perf['avg_rent']); ?></small>
                                    </div>
                                    <button class="sukna-btn sukna-export-pdf-btn" data-id="<?php echo $p->id; ?>" data-name="<?php echo esc_attr($p->name); ?>" title="<?php _e('تحميل التقرير', 'sukna'); ?>" style="padding:4px; background:none; border:none; color:#000;"><span class="dashicons dashicons-pdf"></span></button>
                                </div>
                            </td>
                            <td>
                                <span class="sukna-status-indicator <?php echo ($occ_count == count($rooms)) ? 'indicator-success' : 'indicator-warning'; ?>">
                                    <?php echo $occ_count; ?> / <?php echo count($rooms); ?>
                                </span>
                            </td>
                            <td>
                                <span style="color:#059669;"><?php echo $perf['active_contracts']; ?></span> /
                                <span style="color:#ef4444;"><?php echo $perf['expired_contracts']; ?></span>
                            </td>
                            <td>
                                <div style="font-weight:700;"><?php echo number_format($perf['monthly_income']); ?></div>
                                <small style="color:#059669;"><?php echo number_format($perf['monthly_net']); ?></small>
                            </td>
                            <td><span class="sukna-capsule capsule-accent" style="font-weight:700;"><?php echo $perf['roi']; ?>%</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>

        <div class="sukna-card">
            <h3><?php _e('مركز الإشعارات والعمليات', 'sukna'); ?></h3>
            <div style="padding: 15px; background: #fffcf0; border: 1px solid #fdf2d0; border-radius: 8px; margin-bottom:15px;">
                <small style="color: #D4AF37; font-weight: 800; display: block; margin-bottom: 5px;">
                    <span class="dashicons dashicons-calendar-alt" style="font-size:16px;"></span> <?php _e('تذكير بداية الدورة الإيجارية', 'sukna'); ?>
                </small>
                <p style="margin: 0; font-size: 0.8rem; color: #854d0e;"><?php _e('يرجى إعادة تعيين الوحدات الشاغرة لهذا الشهر لبدء تسجيل العقود الجديدة وتحصيل الإيرادات.', 'sukna'); ?></p>
            </div>

            <div style="display:flex; justify-content: space-between; align-items: center; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:15px;">
                <h4 style="font-size:0.85rem; margin:0; color:#000;"><?php _e('آخر 5 عمليات', 'sukna'); ?></h4>
            </div>
            <div style="max-height: 250px; overflow-y: auto;">
                <?php
                $owner_logs = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sukna_activity_logs WHERE description LIKE %s OR description LIKE %s ORDER BY action_date DESC LIMIT 5", '%عقار%', '%وحدة%'));
                foreach($owner_logs as $log): ?>
                    <div style="padding: 8px 0; border-bottom: 1px solid #f1f5f9;">
                        <small style="display:block; color:#94a3b8; font-size:0.7rem;"><?php echo date('Y/m/d H:i', strtotime($log->action_date)); ?></small>
                        <div style="font-size:0.75rem; font-weight:600;"><?php echo esc_html($log->description); ?></div>
                    </div>
                <?php endforeach; ?>
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
