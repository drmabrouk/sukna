<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function sukna_get_property_report_html($property_id) {
    $p = Sukna_Properties::get_property($property_id);
    if (!$p) return '';

    $perf = Sukna_Properties::get_property_performance($property_id);
    $rooms = Sukna_Properties::get_rooms($property_id);
    $investors = Sukna_Investments::get_property_investments($property_id);
    $setup_items = Sukna_Properties::get_setup_items($property_id);
    $expenses = $GLOBALS['wpdb']->get_results($GLOBALS['wpdb']->prepare("SELECT * FROM {$GLOBALS['wpdb']->prefix}sukna_expenses WHERE property_id = %d ORDER BY expense_date DESC", $property_id));
    $logs = $GLOBALS['wpdb']->get_results($GLOBALS['wpdb']->prepare("SELECT * FROM {$GLOBALS['wpdb']->prefix}sukna_activity_logs WHERE description LIKE %s ORDER BY action_date DESC LIMIT 10", '%' . $p->name . '%'));

    $current_user = Sukna_Auth::current_user();
    $report_date = date('Y/m/d H:i');
    $logo_url = $GLOBALS['wpdb']->get_var("SELECT setting_value FROM {$GLOBALS['wpdb']->prefix}sukna_settings WHERE setting_key = 'company_logo'");

    ob_start();
    ?>
    <div id="sukna-report-container" style="direction: rtl; font-family: 'Rubik', sans-serif; color: #000; background: #fff; line-height: 1.6;">

        <!-- Page 1: Cover Page -->
        <div class="pdf-page" style="height: 1050px; padding: 80px; display: flex; flex-direction: column; justify-content: space-between; border: 20px solid #000; position: relative; page-break-after: always;">
            <div style="text-align: center;">
                <?php if($logo_url): ?>
                    <img src="<?php echo esc_url($logo_url); ?>" style="max-width: 200px; margin-bottom: 40px;">
                <?php else: ?>
                    <h1 style="font-size: 3rem; margin: 0; letter-spacing: 5px;">SUKNA</h1>
                <?php endif; ?>
                <div style="width: 100px; height: 4px; background: #D4AF37; margin: 20px auto;"></div>
            </div>

            <div style="text-align: center;">
                <h2 style="font-size: 2.5rem; margin-bottom: 10px;"><?php echo esc_html($p->name); ?></h2>
                <p style="font-size: 1.2rem; color: #64748b; margin-top: 0;"><?php _e('تقرير استثماري وعقاري متكامل', 'sukna'); ?></p>
                <div style="display: inline-block; background: #000; color: #fff; padding: 10px 30px; border-radius: 50px; font-weight: 700; margin-top: 20px;">
                    <?php echo $perf['total_invested'] >= $perf['total_project_cost'] ? __('تشغيل / استثمار نشط', 'sukna') : __('تحت التجهيز / التطوير', 'sukna'); ?>
                </div>
            </div>

            <div style="border-top: 1px solid #eee; pt: 40px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 10px 0;"><strong><?php _e('الموقع:', 'sukna'); ?></strong></td>
                        <td style="padding: 10px 0; text-align: left;"><?php echo esc_html($p->city . ', ' . $p->state_emirate . ', ' . $p->country); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0;"><strong><?php _e('تاريخ التقرير:', 'sukna'); ?></strong></td>
                        <td style="padding: 10px 0; text-align: left;"><?php echo $report_date; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0;"><strong><?php _e('نوع العقار:', 'sukna'); ?></strong></td>
                        <td style="padding: 10px 0; text-align: left;"><?php
                            $subtypes = array('building' => 'بناية', 'villa' => 'فيلا', 'apartment' => 'شقة', 'studio' => 'ستوديو', 'commercial' => 'تجاري');
                            echo $subtypes[$p->property_subtype] ?? 'عقار';
                        ?></td>
                    </tr>
                </table>
            </div>

            <div style="position: absolute; bottom: 40px; left: 0; right: 0; text-align: center; opacity: 0.1; font-size: 5rem; font-weight: 800; transform: rotate(-15deg); z-index: -1;">SUKNA</div>
        </div>

        <!-- Page 2: Overview & Investment -->
        <div class="pdf-page" style="padding: 50px; page-break-after: always;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; border-bottom: 2px solid #000; padding-bottom: 15px;">
                <h3 style="margin: 0; font-size: 1.5rem; color: #000;"><?php _e('ملخص البيانات والمشروع', 'sukna'); ?></h3>
                <span style="color: #D4AF37; font-weight: 700;">SUKNA | PROPERTY REPORT</span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 50px;">
                <div>
                    <h4 style="border-right: 4px solid #D4AF37; padding-right: 15px; margin-bottom: 20px;"><?php _e('المواصفات العامة', 'sukna'); ?></h4>
                    <table style="width: 100%; font-size: 0.9rem;">
                        <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 8px 0; color: #64748b;"><?php _e('إجمالي الغرف:', 'sukna'); ?></td><td style="padding: 8px 0; font-weight: 700; text-align: left;"><?php echo count($rooms); ?></td></tr>
                        <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 8px 0; color: #64748b;"><?php _e('الوحدات المؤجرة:', 'sukna'); ?></td><td style="padding: 8px 0; font-weight: 700; text-align: left; color: #ef4444;"><?php echo count(array_filter($rooms, function($r){return $r->status==='rented';})); ?></td></tr>
                        <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 8px 0; color: #64748b;"><?php _e('الوحدات الشاغرة:', 'sukna'); ?></td><td style="padding: 8px 0; font-weight: 700; text-align: left; color: #059669;"><?php echo count(array_filter($rooms, function($r){return $r->status==='available';})); ?></td></tr>
                        <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 8px 0; color: #64748b;"><?php _e('نسبة الإشغال:', 'sukna'); ?></td><td style="padding: 8px 0; font-weight: 700; text-align: left;"><?php echo count($rooms) > 0 ? round((count(array_filter($rooms, function($r){return $r->status==='rented';})) / count($rooms)) * 100) : 0; ?>%</td></tr>
                        <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 8px 0; color: #64748b;"><?php _e('بداية / مدة العقد:', 'sukna'); ?></td><td style="padding: 8px 0; font-weight: 700; text-align: left;"><?php echo $p->contract_start_date . ' / ' . $p->contract_duration . ' ' . __('سنة', 'sukna'); ?></td></tr>
                        <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 8px 0; color: #64748b;"><?php _e('رقم الطابق / الشقة:', 'sukna'); ?></td><td style="padding: 8px 0; font-weight: 700; text-align: left;"><?php echo $p->floor_number . ' / ' . $p->apartment_number; ?></td></tr>
                    </table>
                </div>
                <div>
                    <h4 style="border-right: 4px solid #000; padding-right: 15px; margin-bottom: 20px;"><?php _e('الملخص المالي للاستثمار', 'sukna'); ?></h4>
                    <table style="width: 100%; font-size: 0.9rem;">
                        <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 8px 0; color: #64748b;"><?php _e('قيمة العقار / العقد:', 'sukna'); ?></td><td style="padding: 8px 0; font-weight: 700; text-align: left;"><?php echo number_format($p->base_value); ?></td></tr>
                        <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 8px 0; color: #64748b;"><?php _e('تكاليف التجهيز:', 'sukna'); ?></td><td style="padding: 8px 0; font-weight: 700; text-align: left;"><?php echo number_format($p->total_setup_cost); ?></td></tr>
                        <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 8px 0; color: #64748b;"><?php _e('الرسوم الحكومية:', 'sukna'); ?></td><td style="padding: 8px 0; font-weight: 700; text-align: left;"><?php echo number_format($p->gov_fees); ?></td></tr>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #000;"><td style="padding: 10px 5px; font-weight: 800;"><?php _e('إجمالي رأس المال:', 'sukna'); ?></td><td style="padding: 10px 5px; font-weight: 800; text-align: left;"><?php echo number_format($perf['total_project_cost']); ?></td></tr>
                        <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 8px 0; color: #64748b;"><?php _e('المبلغ الممول:', 'sukna'); ?></td><td style="padding: 8px 0; font-weight: 700; text-align: left; color: #D4AF37;"><?php echo number_format($perf['total_invested']); ?></td></tr>
                        <?php if($perf['total_invested'] < $perf['total_project_cost']): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 8px 0; color: #64748b;"><?php _e('التمويل المتبقي:', 'sukna'); ?></td><td style="padding: 8px 0; font-weight: 700; text-align: left; color: #ef4444;"><?php echo number_format($perf['total_project_cost'] - $perf['total_invested']); ?></td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <h4 style="border-right: 4px solid #D4AF37; padding-right: 15px; margin-bottom: 20px;"><?php _e('المستثمرون والشركاء', 'sukna'); ?></h4>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 40px; font-size: 0.85rem;">
                <thead>
                    <tr style="background: #000; color: #fff;">
                        <th style="padding: 12px; text-align: right;"><?php _e('المستثمر', 'sukna'); ?></th>
                        <th style="padding: 12px; text-align: center;"><?php _e('قيمة المساهمة', 'sukna'); ?></th>
                        <th style="padding: 12px; text-align: center;"><?php _e('نسبة الملكية', 'sukna'); ?></th>
                        <th style="padding: 12px; text-align: left;"><?php _e('الحصة الشهرية التقديرية', 'sukna'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_investor_contribution = 0;
                    foreach($investors as $inv):
                        $share_pct = $perf['total_project_cost'] > 0 ? ($inv->amount / $perf['total_project_cost']) * 100 : 0;
                        $monthly_share = $perf['monthly_net'] * ($share_pct / 100);
                        $total_investor_contribution += $inv->amount;
                    ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 10px;"><strong><?php echo esc_html($inv->investor_name); ?></strong></td>
                            <td style="padding: 10px; text-align: center;"><?php echo number_format($inv->amount); ?></td>
                            <td style="padding: 10px; text-align: center;"><?php echo round($share_pct, 2); ?>%</td>
                            <td style="padding: 10px; text-align: left; color: #059669; font-weight: 700;"><?php echo number_format($monthly_share, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <?php
                    $owner_contribution = $perf['total_project_cost'] - $total_investor_contribution;
                    if ($owner_contribution > 0):
                        $owner_share_pct = ($owner_contribution / $perf['total_project_cost']) * 100;
                        $owner_monthly_share = $perf['monthly_net'] * ($owner_share_pct / 100);
                    ?>
                        <tr style="background: #fffbeb;">
                            <td style="padding: 10px;"><strong><?php _e('المالك / المشغل الرئيسي', 'sukna'); ?></strong></td>
                            <td style="padding: 10px; text-align: center;"><?php echo number_format($owner_contribution); ?></td>
                            <td style="padding: 10px; text-align: center;"><?php echo round($owner_share_pct, 2); ?>%</td>
                            <td style="padding: 10px; text-align: left; color: #000; font-weight: 700;"><?php echo number_format($owner_monthly_share, 2); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Page 3: Financial Performance & Expenses -->
        <div class="pdf-page" style="padding: 50px; page-break-after: always;">
             <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; border-bottom: 2px solid #000; padding-bottom: 15px;">
                <h3 style="margin: 0; font-size: 1.5rem; color: #000;"><?php _e('الأداء المالي والتشغيلي', 'sukna'); ?></h3>
                <span style="color: #D4AF37; font-weight: 700;">SUKNA | FINANCIAL DATA</span>
            </div>

            <div style="display: flex; gap: 20px; margin-bottom: 40px;">
                <div style="flex: 1; background: #000; color: #fff; padding: 25px; border-radius: 15px; text-align: center;">
                    <small style="opacity: 0.7; display: block; margin-bottom: 5px;"><?php _e('الدخل الشهري الحالي', 'sukna'); ?></small>
                    <div style="font-size: 1.8rem; font-weight: 800; color: #D4AF37;"><?php echo number_format($perf['monthly_income']); ?> <small style="font-size: 0.8rem;">EGP</small></div>
                </div>
                <div style="flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; padding: 25px; border-radius: 15px; text-align: center;">
                    <small style="color: #64748b; display: block; margin-bottom: 5px;"><?php _e('صافي الربح الشهري', 'sukna'); ?></small>
                    <div style="font-size: 1.8rem; font-weight: 800; color: #059669;"><?php echo number_format($perf['monthly_net']); ?> <small style="font-size: 0.8rem;">EGP</small></div>
                </div>
                <div style="flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; padding: 25px; border-radius: 15px; text-align: center;">
                    <small style="color: #64748b; display: block; margin-bottom: 5px;"><?php _e('العائد ROI الكلي', 'sukna'); ?></small>
                    <div style="font-size: 1.8rem; font-weight: 800; color: #D4AF37;"><?php echo $perf['roi']; ?>%</div>
                </div>
            </div>

            <h4 style="border-right: 4px solid #D4AF37; padding-right: 15px; margin-bottom: 20px;"><?php _e('تفاصيل المصروفات التشغيلية', 'sukna'); ?></h4>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 40px; font-size: 0.85rem;">
                <thead>
                    <tr style="border-bottom: 2px solid #000;">
                        <th style="padding: 12px; text-align: right;"><?php _e('بند المصروف', 'sukna'); ?></th>
                        <th style="padding: 12px; text-align: center;"><?php _e('التاريخ', 'sukna'); ?></th>
                        <th style="padding: 12px; text-align: left;"><?php _e('القيمة', 'sukna'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $cat_map = array('electricity' => 'كهرباء', 'water' => 'مياه', 'internet' => 'إنترنت', 'cleaning' => 'نظافة', 'maintenance' => 'صيانة');
                    foreach($expenses as $ex): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 10px;"><?php echo $cat_map[$ex->category] ?? $ex->category; ?></td>
                            <td style="padding: 10px; text-align: center; color: #64748b;"><?php echo date('Y/m/d', strtotime($ex->expense_date)); ?></td>
                            <td style="padding: 10px; text-align: left; font-weight: 700;"><?php echo number_format($ex->amount); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="background: #f8fafc; font-weight: 800;">
                        <td colspan="2" style="padding: 12px;"><?php _e('إجمالي المصروفات التشغيلية:', 'sukna'); ?></td>
                        <td style="padding: 12px; text-align: left; color: #ef4444;"><?php echo number_format($perf['expenses']); ?></td>
                    </tr>
                </tbody>
            </table>

            <h4 style="border-right: 4px solid #000; padding-right: 15px; margin-bottom: 20px;"><?php _e('تجهيزات ما قبل التشغيل', 'sukna'); ?></h4>
            <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 30px;">
                <?php foreach($setup_items as $si): ?>
                    <div style="background: #f1f5f9; padding: 10px 20px; border-radius: 8px; font-size: 0.8rem; border-right: 3px solid #D4AF37;">
                        <span style="color: #64748b;"><?php echo esc_html($si->item_name); ?>:</span>
                        <strong style="margin-right: 5px;"><?php echo number_format($si->item_cost); ?></strong>
                    </div>
                <?php endforeach; ?>
                <?php if($p->gov_fees > 0): ?>
                    <div style="background: #f1f5f9; padding: 10px 20px; border-radius: 8px; font-size: 0.8rem; border-right: 3px solid #D4AF37;">
                        <span style="color: #64748b;"><?php _e('الرسوم والضرائب:', 'sukna'); ?>:</span>
                        <strong style="margin-right: 5px;"><?php echo number_format($p->gov_fees); ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Page 4: Room Details & Logs -->
        <div class="pdf-page" style="padding: 50px;">
             <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; border-bottom: 2px solid #000; padding-bottom: 15px;">
                <h3 style="margin: 0; font-size: 1.5rem; color: #000;"><?php _e('تفاصيل الوحدات والسجل', 'sukna'); ?></h3>
                <span style="color: #D4AF37; font-weight: 700;">SUKNA | UNITS & AUDIT</span>
            </div>

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 50px; font-size: 0.8rem;">
                <thead>
                    <tr style="background: #f1f5f9;">
                        <th style="padding: 10px; text-align: right;"><?php _e('رقم الوحدة', 'sukna'); ?></th>
                        <th style="padding: 10px; text-align: center;"><?php _e('الحالة', 'sukna'); ?></th>
                        <th style="padding: 10px; text-align: center;"><?php _e('المستأجر', 'sukna'); ?></th>
                        <th style="padding: 10px; text-align: left;"><?php _e('القيمة الإيجارية', 'sukna'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($rooms as $r): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px; font-weight: 700;">#<?php echo $r->room_number; ?></td>
                            <td style="padding: 10px; text-align: center;">
                                <span style="padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; background: <?php echo $r->status === 'rented' ? '#fee2e2' : '#dcfce7'; ?>; color: <?php echo $r->status === 'rented' ? '#ef4444' : '#059669'; ?>;">
                                    <?php echo $r->status === 'rented' ? __('مؤجر', 'sukna') : __('شاغر', 'sukna'); ?>
                                </span>
                            </td>
                            <td style="padding: 10px; text-align: center; color: #64748b;"><?php echo esc_html($r->tenant_name ?: '-'); ?></td>
                            <td style="padding: 10px; text-align: left; font-weight: 700;"><?php echo number_format($r->rental_price); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h4 style="border-right: 4px solid #D4AF37; padding-right: 15px; margin-bottom: 20px;"><?php _e('آخر النشاطات المسجلة', 'sukna'); ?></h4>
            <div style="background: #fafafa; padding: 20px; border-radius: 12px; border: 1px solid #eee;">
                <?php foreach($logs as $log): ?>
                    <div style="display: flex; gap: 15px; padding: 10px 0; border-bottom: 1px dashed #ddd;">
                        <div style="font-size: 0.75rem; color: #94a3b8; white-space: nowrap;"><?php echo date('H:i - Y/m/d', strtotime($log->action_date)); ?></div>
                        <div style="font-size: 0.8rem; font-weight: 600;"><?php echo esc_html($log->description); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top: 50px; border-top: 2px solid #000; padding-top: 20px; display: flex; justify-content: space-between; font-size: 0.75rem; color: #64748b;">
                <div>
                    <strong><?php _e('مُعد التقرير:', 'sukna'); ?></strong> <?php echo esc_html($current_user->name); ?>
                    (<?php
                        $role_map = array('admin' => 'مدير النظام', 'owner' => 'مالك / مدير عقارات', 'investor' => 'مستثمر', 'tenant' => 'مستأجر');
                        echo $role_map[$current_user->role] ?? strtoupper($current_user->role);
                    ?>)
                </div>
                <div>
                    <?php _e('حقوق الطبع محفوظة © 2026 Sukna Team', 'sukna'); ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
