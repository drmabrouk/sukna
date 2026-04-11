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
    <style>
        @page { size: A4; margin: 0; }
        .pdf-report-body {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #fff;
            box-sizing: border-box;
            direction: rtl;
            font-family: 'Rubik', sans-serif;
        }
        .pdf-page {
            width: 210mm;
            height: 297mm;
            padding: 20mm;
            box-sizing: border-box;
            position: relative;
            page-break-after: always;
            overflow: hidden;
        }
        .cover-page {
            border: 15mm solid #000;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            text-align: center;
        }
        .section-title {
            border-right: 4pt solid #D4AF37;
            padding-right: 15pt;
            margin: 20pt 0 15pt 0;
            font-size: 14pt;
            color: #000;
        }
        .analysis-note {
            background: #f8fafc;
            padding: 12pt;
            border-right: 3pt solid #000;
            font-size: 9.5pt;
            color: #475569;
            margin-bottom: 15pt;
            line-height: 1.5;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20pt;
        }
        .data-table th {
            background: #000;
            color: #fff;
            padding: 10pt;
            text-align: right;
            font-size: 10pt;
        }
        .data-table td {
            padding: 8pt 10pt;
            border-bottom: 1pt solid #eee;
            font-size: 9.5pt;
        }
        .kpi-box {
            background: #f1f5f9;
            padding: 15pt;
            border-radius: 10pt;
            text-align: center;
            flex: 1;
        }
        .kpi-value {
            font-size: 18pt;
            font-weight: 800;
            color: #000;
            margin-top: 5pt;
        }
        .gold { color: #D4AF37; }
    </style>

    <div class="pdf-report-body">

        <!-- Page 1: Cover Page -->
        <div class="pdf-page cover-page">
            <div>
                <?php if($logo_url): ?>
                    <img src="<?php echo esc_url($logo_url); ?>" style="max-width: 60mm; margin-top: 20mm;">
                <?php else: ?>
                    <h1 style="font-size: 40pt; margin-top: 20mm; letter-spacing: 5px;">SUKNA</h1>
                <?php endif; ?>
                <div style="width: 20mm; height: 4pt; background: #D4AF37; margin: 10mm auto;"></div>
            </div>

            <div>
                <h2 style="font-size: 32pt; margin-bottom: 5mm;"><?php echo esc_html($p->name); ?></h2>
                <p style="font-size: 16pt; color: #64748b; font-weight: 400;"><?php _e('تقرير تحليل الأداء الاستثماري والتشغيلي', 'sukna'); ?></p>
                <div style="display: inline-block; background: #000; color: #fff; padding: 12pt 35pt; border-radius: 50pt; font-weight: 700; font-size: 12pt; margin-top: 10mm;">
                    <?php echo $perf['total_invested'] >= $perf['total_project_cost'] ? __('تشغيل / استثمار نشط', 'sukna') : __('قيد التجهيز والتطوير', 'sukna'); ?>
                </div>
            </div>

            <div style="border-top: 1pt solid #e2e8f0; padding-top: 10mm; text-align: right;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8pt 0;"><strong><?php _e('الموقع الجغرافي:', 'sukna'); ?></strong></td>
                        <td style="padding: 8pt 0; text-align: left;"><?php echo esc_html($p->city . ', ' . $p->state_emirate . ', ' . $p->country); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8pt 0;"><strong><?php _e('تاريخ إصدار التقرير:', 'sukna'); ?></strong></td>
                        <td style="padding: 8pt 0; text-align: left;"><?php echo $report_date; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8pt 0;"><strong><?php _e('التصنيف العقاري:', 'sukna'); ?></strong></td>
                        <td style="padding: 8pt 0; text-align: left;"><?php
                            $subtypes = array('building' => 'بناية', 'villa' => 'فيلا', 'apartment' => 'شقة', 'studio' => 'ستوديو', 'commercial' => 'تجاري');
                            echo $subtypes[$p->property_subtype] ?? 'عقار';
                        ?></td>
                    </tr>
                </table>
            </div>

            <div style="position: absolute; bottom: 30mm; left: 0; right: 0; opacity: 0.05; font-size: 80pt; font-weight: 900; transform: rotate(-15deg); z-index: -1;">SUKNA</div>
        </div>

        <!-- Page 2: Summary & Capital Structure -->
        <div class="pdf-page">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2pt solid #000; padding-bottom: 5mm; margin-bottom: 10mm;">
                <h3 style="margin: 0; font-size: 16pt;"><?php _e('أولاً: ملخص بيانات العقار والمركز المالي', 'sukna'); ?></h3>
                <span style="color: #D4AF37; font-weight: 700; font-size: 9pt;">SUKNA | PROPERTY PROFILE</span>
            </div>

            <div class="analysis-note">
                <strong><?php _e('المنهجية التحليلية:', 'sukna'); ?></strong> <?php _e('يوضح هذا القسم الهوية التشغيلية والهيكل الرأسمالي للمشروع. يقوم النظام بربط القيمة الرأسمالية (CAPEX) بكافة تكاليف التجهيز والرسوم الحكومية لضمان حساب العائد على الاستثمار (ROI) بدقة متناهية، حيث يتم اعتبار أي مبلغ مدفوع قبل التشغيل جزءاً لا يتجزأ من وعاء الاستثمار الكلي.', 'sukna'); ?>
            </div>

            <div style="display: flex; gap: 15mm; margin-bottom: 15mm;">
                <div style="flex: 1;">
                    <h4 class="section-title" style="border-right-color: #000;"><?php _e('1. المواصفات الهيكلية', 'sukna'); ?></h4>
                    <table style="width: 100%; font-size: 10pt;">
                        <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 8pt 0; color: #64748b;"><?php _e('إجمالي عدد الوحدات:', 'sukna'); ?></td><td style="padding: 8pt 0; font-weight: 700; text-align: left;"><?php echo count($rooms); ?></td></tr>
                        <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 8pt 0; color: #64748b;"><?php _e('الوحدات المؤجرة حالياً:', 'sukna'); ?></td><td style="padding: 8pt 0; font-weight: 700; text-align: left; color: #ef4444;"><?php echo count(array_filter($rooms, function($r){return $r->status==='rented';})); ?></td></tr>
                        <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 8pt 0; color: #64748b;"><?php _e('نسبة الإشغال:', 'sukna'); ?></td><td style="padding: 8pt 0; font-weight: 700; text-align: left;"><?php echo count($rooms) > 0 ? round((count(array_filter($rooms, function($r){return $r->status==='rented';})) / count($rooms)) * 100) : 0; ?>%</td></tr>
                        <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 8pt 0; color: #64748b;"><?php _e('بداية / مدة العقد:', 'sukna'); ?></td><td style="padding: 8pt 0; font-weight: 700; text-align: left;"><?php echo $p->contract_start_date . ' / ' . $p->contract_duration . ' ' . __('سنة', 'sukna'); ?></td></tr>
                    </table>
                </div>
                <div style="flex: 1;">
                    <h4 class="section-title" style="border-right-color: #000;"><?php _e('2. الهيكل الرأسمالي', 'sukna'); ?></h4>
                    <table style="width: 100%; font-size: 10pt;">
                        <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 8pt 0; color: #64748b;"><?php _e('قيمة العقار / العقد:', 'sukna'); ?></td><td style="padding: 8pt 0; font-weight: 700; text-align: left;"><?php echo number_format($p->base_value); ?></td></tr>
                        <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 8pt 0; color: #64748b;"><?php _e('تكاليف التجهيز والرسوم:', 'sukna'); ?></td><td style="padding: 8pt 0; font-weight: 700; text-align: left;"><?php echo number_format($p->total_setup_cost + $p->gov_fees); ?></td></tr>
                        <tr style="background: #f8fafc; border-bottom: 2pt solid #000;"><td style="padding: 8pt 5pt; font-weight: 800;"><?php _e('إجمالي رأس المال:', 'sukna'); ?></td><td style="padding: 8pt 5pt; font-weight: 800; text-align: left;"><?php echo number_format($perf['total_project_cost']); ?></td></tr>
                        <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 8pt 0; color: #64748b;"><?php _e('المبلغ الممول:', 'sukna'); ?></td><td style="padding: 8pt 0; font-weight: 700; text-align: left; color: #D4AF37;"><?php echo number_format($perf['total_invested']); ?></td></tr>
                    </table>
                </div>
            </div>

            <h3 class="section-title"><?php _e('ثانياً: سجل الشركاء وتوزيع حصص الملكية', 'sukna'); ?></h3>
            <div class="analysis-note">
                <strong><?php _e('توزيع الحصص والحقوق:', 'sukna'); ?></strong> <?php _e('يعتمد النظام نموذج "التملك النسبي" (Proportional Equity)، حيث يتم تحديد ملكية كل شريك بناءً على مساهمته النقدية الفعلية مقابل إجمالي تكلفة المشروع. الأرباح الشهرية الظاهرة هي استحقاقات نقدية صافية (بعد خصم المصاريف) يتم تحويلها تلقائياً لمحفظة الشريك عند إتمام دورة التحصيل.', 'sukna'); ?>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?php _e('اسم الشريك / المستثمر', 'sukna'); ?></th>
                        <th style="text-align: center;"><?php _e('قيمة المساهمة', 'sukna'); ?></th>
                        <th style="text-align: center;"><?php _e('نسبة الملكية', 'sukna'); ?></th>
                        <th style="text-align: left;"><?php _e('الربح الشهري المستحق', 'sukna'); ?></th>
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
                        <tr>
                            <td><strong><?php echo esc_html($inv->investor_name); ?></strong></td>
                            <td style="text-align: center;"><?php echo number_format($inv->amount); ?></td>
                            <td style="text-align: center;"><?php echo round($share_pct, 2); ?>%</td>
                            <td style="text-align: left; color: #059669; font-weight: 700;"><?php echo number_format($monthly_share, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <?php
                    $owner_contribution = $perf['total_project_cost'] - $total_investor_contribution;
                    if ($owner_contribution > 0):
                        $owner_share_pct = ($owner_contribution / $perf['total_project_cost']) * 100;
                        $owner_monthly_share = $perf['monthly_net'] * ($owner_share_pct / 100);
                    ?>
                        <tr style="background: #fffbeb;">
                            <td><strong><?php _e('المالك / المشغل الرئيسي', 'sukna'); ?></strong></td>
                            <td style="text-align: center;"><?php echo number_format($owner_contribution); ?></td>
                            <td style="text-align: center;"><?php echo round($owner_share_pct, 2); ?>%</td>
                            <td style="text-align: left; color: #000; font-weight: 700;"><?php echo number_format($owner_monthly_share, 2); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Page 3: Performance Analysis & OPEX -->
        <div class="pdf-page">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2pt solid #000; padding-bottom: 5mm; margin-bottom: 10mm;">
                <h3 style="margin: 0; font-size: 16pt;"><?php _e('ثالثاً: تحليل التدفقات النقدية والأداء التشغيلي', 'sukna'); ?></h3>
                <span style="color: #D4AF37; font-weight: 700; font-size: 9pt;">SUKNA | ANALYTICAL PERFORMANCE</span>
            </div>

            <div class="analysis-note" style="background: #000; color: #fff;">
                <strong><?php _e('تفسير المؤشرات المالية (Financial Insights):', 'sukna'); ?></strong> <?php _e('يعكس معدل ROI المعروض الكفاءة التشغيلية السنوية المتوقعة بناءً على التدفقات الفعلية. يتم حساب صافي الربح وفق معادلة (الإيرادات المحصلة - المصاريف التشغيلية)، مما يعطي قراءة حقيقية للسيولة النقدية المتاحة للتوزيع دون الاعتماد على أرقام تقديرية أو افتراضية.', 'sukna'); ?>
            </div>

            <div style="display: flex; gap: 10mm; margin-bottom: 15mm;">
                <div class="kpi-box">
                    <small style="color: #64748b; font-weight: 700;"><?php _e('الدخل الشهري الفعلي', 'sukna'); ?></small>
                    <div class="kpi-value" style="color: #059669;"><?php echo number_format($perf['monthly_income']); ?></div>
                    <small>EGP</small>
                </div>
                <div class="kpi-box" style="background: #000;">
                    <small style="color: #94a3b8; font-weight: 700;"><?php _e('صافي الربح الشهري', 'sukna'); ?></small>
                    <div class="kpi-value gold"><?php echo number_format($perf['monthly_net']); ?></div>
                    <small style="color: #fff;">EGP</small>
                </div>
                <div class="kpi-box">
                    <small style="color: #64748b; font-weight: 700;"><?php _e('العائد على الاستثمار ROI', 'sukna'); ?></small>
                    <div class="kpi-value gold"><?php echo $perf['roi']; ?>%</div>
                    <small><?php _e('سنوي تراكمي', 'sukna'); ?></small>
                </div>
            </div>

            <h4 class="section-title"><?php _e('1. سجل المصروفات التشغيلية (OPEX)', 'sukna'); ?></h4>
            <p style="font-size: 9pt; color: #475569; margin-bottom: 10pt;">
                <?php _e('يتم تتبع المصاريف التشغيلية بدقة لضمان حساب صافي التدفق النقدي. تشمل هذه المصاريف الفواتير الدورية (كهرباء، مياه، إنترنت) وتكاليف الصيانة والنظافة. يقوم النظام بخصم هذه القيم من إجمالي الإيرادات قبل عملية توزيع الأرباح على الشركاء.', 'sukna'); ?>
            </p>
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?php _e('بند المصروف', 'sukna'); ?></th>
                        <th style="text-align: center;"><?php _e('التاريخ', 'sukna'); ?></th>
                        <th style="text-align: left;"><?php _e('القيمة', 'sukna'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $cat_map = array('electricity' => 'كهرباء', 'water' => 'مياه', 'internet' => 'إنترنت', 'cleaning' => 'نظافة', 'maintenance' => 'صيانة');
                    foreach($expenses as $ex): ?>
                        <tr>
                            <td><?php echo $cat_map[$ex->category] ?? $ex->category; ?></td>
                            <td style="text-align: center; color: #64748b;"><?php echo date('Y/m/d', strtotime($ex->expense_date)); ?></td>
                            <td style="text-align: left; font-weight: 700;"><?php echo number_format($ex->amount); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="background: #f8fafc; font-weight: 800;">
                        <td colspan="2"><?php _e('إجمالي المصروفات التشغيلية:', 'sukna'); ?></td>
                        <td style="text-align: left; color: #ef4444;"><?php echo number_format($perf['expenses']); ?></td>
                    </tr>
                </tbody>
            </table>

            <h4 class="section-title" style="border-right-color: #000;"><?php _e('2. تكاليف التجهيز الرأسمالية (CAPEX)', 'sukna'); ?></h4>
            <p style="font-size: 9pt; color: #64748b; margin-bottom: 10pt;">
                <?php _e('تمثل هذه البنود الاستثمارات الأولية غير المتكررة التي تمت لتهيئة العقار للتشغيل الفندقي / السكني، وهي جزء أصيل من قيمة رأس المال المستثمر.', 'sukna'); ?>
            </p>
            <div style="display: flex; flex-wrap: wrap; gap: 8pt;">
                <?php foreach($setup_items as $si): ?>
                    <div style="background: #f1f5f9; padding: 8pt 15pt; border-radius: 6pt; font-size: 9pt; border-right: 3pt solid #D4AF37;">
                        <span style="color: #64748b;"><?php echo esc_html($si->item_name); ?>:</span>
                        <strong style="margin-right: 5pt;"><?php echo number_format($si->item_cost); ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Page 4: Unit Inventory & Audit -->
        <div class="pdf-page">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2pt solid #000; padding-bottom: 5mm; margin-bottom: 10mm;">
                <h3 style="margin: 0; font-size: 16pt;"><?php _e('رابعاً: الحالة التشغيلية للوحدات والتدقيق', 'sukna'); ?></h3>
                <span style="color: #D4AF37; font-weight: 700; font-size: 9pt;">SUKNA | OPERATIONS & LOGS</span>
            </div>

            <div class="analysis-note">
                <strong><?php _e('الرقابة التشغيلية:', 'sukna'); ?></strong> <?php _e('يقدم هذا الجدول جرداً حياً للوحدات والنشاط الإيجاري. يراقب النظام فترات الشغور ومواعيد استحقاق الإيجارات لضمان استدامة التدفق النقدي. تهدف هذه البيانات إلى مساعدة الإدارة في اتخاذ قرارات تسعيرية مرنة بناءً على العرض والطلب وحالة الوحدات.', 'sukna'); ?>
            </div>

            <table class="data-table" style="font-size: 9pt;">
                <thead>
                    <tr style="background: #f8fafc;">
                        <th style="background:#f1f5f9; color:#000;"><?php _e('رقم الوحدة', 'sukna'); ?></th>
                        <th style="background:#f1f5f9; color:#000; text-align: center;"><?php _e('الحالة', 'sukna'); ?></th>
                        <th style="background:#f1f5f9; color:#000; text-align: center;"><?php _e('اسم المستأجر / الضيف', 'sukna'); ?></th>
                        <th style="background:#f1f5f9; color:#000; text-align: left;"><?php _e('القيمة الإيجارية', 'sukna'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($rooms as $r): ?>
                        <tr>
                            <td style="font-weight: 700;">#<?php echo $r->room_number; ?></td>
                            <td style="text-align: center;">
                                <span style="padding: 2pt 8pt; border-radius: 4pt; font-size: 8pt; font-weight:700; background: <?php echo $r->status === 'rented' ? '#fee2e2' : '#dcfce7'; ?>; color: <?php echo $r->status === 'rented' ? '#ef4444' : '#059669'; ?>;">
                                    <?php echo $r->status === 'rented' ? __('مؤجر', 'sukna') : __('شاغر', 'sukna'); ?>
                                </span>
                            </td>
                            <td style="text-align: center; color: #64748b;"><?php echo esc_html($r->tenant_name ?: '-'); ?></td>
                            <td style="text-align: left; font-weight: 700;"><?php echo number_format($r->rental_price); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h4 class="section-title"><?php _e('خامساً: سجل تتبع العمليات (Audit Trail)', 'sukna'); ?></h4>
            <p style="font-size: 8.5pt; color: #475569; margin-bottom: 10pt;">
                <?php _e('يوفر هذا السجل شفافية كاملة حول العمليات الإدارية التي تمت على العقار، مما يضمن أمان البيانات وتتبع التغييرات من قبل المسؤولين المخولين.', 'sukna'); ?>
            </p>
            <div style="background: #fafafa; padding: 15pt; border-radius: 10pt; border: 1px solid #eee;">
                <?php foreach($logs as $log): ?>
                    <div style="display: flex; gap: 15pt; padding: 8pt 0; border-bottom: 1px dashed #ddd;">
                        <div style="font-size: 8.5pt; color: #94a3b8; white-space: nowrap;"><?php echo date('H:i - Y/m/d', strtotime($log->action_date)); ?></div>
                        <div style="font-size: 9pt; font-weight: 600; color: #334155;"><?php echo esc_html($log->description); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="position: absolute; bottom: 20mm; left: 20mm; right: 20mm; border-top: 2pt solid #000; padding-top: 5mm; display: flex; justify-content: space-between; font-size: 8.5pt; color: #64748b;">
                <div>
                    <strong><?php _e('مُعد التقرير:', 'sukna'); ?></strong> <?php echo esc_html($current_user->name); ?>
                    (<?php
                        $role_map = array('admin' => 'مدير النظام', 'owner' => 'مالك / مدير عقارات', 'investor' => 'مستثمر', 'tenant' => 'مستأجر');
                        echo $role_map[$current_user->role] ?? strtoupper($current_user->role);
                    ?>)
                </div>
                <div>
                    <?php _e('حقوق الطبع محفوظة © 2026 Sukna Team', 'sukna'); ?> | <?php echo $report_date; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
