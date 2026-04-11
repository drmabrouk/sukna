<?php
$current_user = Sukna_Auth::current_user();
$is_admin = Sukna_Auth::is_admin();
$is_owner = Sukna_Auth::is_owner();

$args = array();
if ( $is_owner && !$is_admin ) {
    $args['owner_id'] = $current_user->id;
}

if ( ! empty( $_GET['filter_country'] ) ) $args['country'] = sanitize_text_field( $_GET['filter_country'] );
if ( ! empty( $_GET['filter_state'] ) ) $args['state_emirate'] = sanitize_text_field( $_GET['filter_state'] );
if ( ! empty( $_GET['filter_type'] ) ) $args['property_type'] = sanitize_text_field( $_GET['filter_type'] );

$properties = Sukna_Properties::get_all_properties($args);
$geo_data = Sukna_Geo::get_data();
$users = Sukna_Auth::get_all_users();
$owners = array_filter($users, function($u){ return $u->role === 'owner' || $u->role === 'admin'; });
$tenants = array_filter($users, function($u){ return $u->role === 'tenant'; });
$investors = array_filter($users, function($u){ return $u->role === 'investor'; });
?>

<div class="sukna-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:#1e293b;"><?php _e('إدارة العقارات', 'sukna'); ?></h2>
    <div style="display:flex; gap:10px;">
        <?php if($is_admin || $is_owner): ?>
            <button class="sukna-btn sukna-export-btn" data-type="properties" style="background:#fff; color:#000 !important; border:1px solid #ddd; padding:8px 15px;">
                <span class="dashicons dashicons-download" style="margin-left:5px;"></span><?php _e('تصدير', 'sukna'); ?>
            </button>
            <button class="sukna-btn sukna-import-trigger" data-type="properties" style="background:#fff; color:#000 !important; border:1px solid #ddd; padding:8px 15px;">
                <span class="dashicons dashicons-upload" style="margin-left:5px;"></span><?php _e('استيراد', 'sukna'); ?>
            </button>
            <button id="sukna-add-property-btn" class="sukna-btn" style="background:#000; border-radius: 8px;">
                <span class="dashicons dashicons-plus-alt" style="margin-left:8px;"></span><?php _e('إضافة عقار جديد', 'sukna'); ?>
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Property Search Engine -->
<div class="sukna-card" style="margin-bottom:30px; border-top: 3px solid #000;">
    <form method="GET" action="" style="display:flex; gap:15px; align-items: flex-end; flex-wrap: wrap;">
        <input type="hidden" name="sukna_view" value="properties">
        <div class="sukna-form-group" style="margin-bottom:0; flex:1; min-width:150px;">
            <label style="display:block; font-size:0.75rem; margin-bottom:5px; color:#64748b;"><?php _e('الدولة', 'sukna'); ?></label>
            <select name="filter_country" id="filter-country">
                <option value=""><?php _e('الكل', 'sukna'); ?></option>
                <?php foreach($geo_data as $code => $data): ?>
                    <option value="<?php echo $code; ?>" <?php selected($_GET['filter_country'] ?? '', $code); ?>><?php echo $data['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sukna-form-group" style="margin-bottom:0; flex:1; min-width:150px;">
            <label style="display:block; font-size:0.75rem; margin-bottom:5px; color:#64748b;"><?php _e('الولاية / الإمارة', 'sukna'); ?></label>
            <select name="filter_state" id="filter-state">
                <option value=""><?php _e('الكل', 'sukna'); ?></option>
                <?php
                if ( ! empty($_GET['filter_country']) && isset($geo_data[$_GET['filter_country']]) ) {
                    foreach($geo_data[$_GET['filter_country']]['states'] as $s_code => $s_data) {
                        echo '<option value="'.$s_code.'" '.selected($_GET['filter_state'] ?? '', $s_code, false).'>'.$s_data['name'].'</option>';
                    }
                }
                ?>
            </select>
        </div>
        <div class="sukna-form-group" style="margin-bottom:0; flex:1; min-width:150px;">
            <label style="display:block; font-size:0.75rem; margin-bottom:5px; color:#64748b;"><?php _e('نوع العقار', 'sukna'); ?></label>
            <select name="filter_type">
                <option value=""><?php _e('الكل', 'sukna'); ?></option>
                <option value="owned" <?php selected($_GET['filter_type'] ?? '', 'owned'); ?>><?php _e('ملك', 'sukna'); ?></option>
                <option value="leased" <?php selected($_GET['filter_type'] ?? '', 'leased'); ?>><?php _e('مستأجر (إدارة)', 'sukna'); ?></option>
            </select>
        </div>
        <button type="submit" class="sukna-btn" style="background:#000; padding:10px 25px;"><span class="dashicons dashicons-search" style="margin-left:5px;"></span><?php _e('بحث', 'sukna'); ?></button>
        <a href="<?php echo add_query_arg('sukna_view', 'properties', remove_query_arg(array('filter_country', 'filter_state', 'filter_type'))); ?>" class="sukna-btn" style="background:#64748b; padding:10px 25px;"><?php _e('إعادة تعيين', 'sukna'); ?></a>
    </form>
</div>

<div class="sukna-grid" style="grid-template-columns: repeat(auto-fill, minmax(45%, 1fr)); gap: 30px;">
    <?php foreach($properties as $p):
        $rooms = Sukna_Properties::get_rooms($p->id);
        $rented_count = count(array_filter($rooms, function($r){ return $r->status === 'rented'; }));
        $perf = Sukna_Properties::get_property_performance($p->id);
        $is_leased = ($p->property_type === 'leased');
        $investors_linked = Sukna_Investments::get_property_investments($p->id);
        $is_fully_funded = ($perf['total_invested'] >= $perf['total_project_cost']);
        $setup_items = Sukna_Properties::get_setup_items($p->id);
    ?>
        <div class="sukna-card property-dashboard-unit" data-funded="<?php echo $is_fully_funded ? '1' : '0'; ?>" style="border-top: 6px solid <?php echo $is_leased ? '#000' : '#D4AF37'; ?>; padding:0; overflow:hidden;">
            <!-- Property Header -->
            <div style="padding:20px; border-bottom:1px solid #eee; display:flex; justify-content: space-between; align-items: flex-start; background: #fafafa;">
                <div>
                    <h3 style="margin:0; font-size:1.3rem; color:#000;"><?php echo esc_html($p->name); ?></h3>
                    <div style="margin-top:5px;">
                        <span class="sukna-capsule" style="background:#eee; color:#666; font-size:0.7rem;"><?php
                            $subtypes = array('building' => 'بناية', 'villa' => 'فيلا', 'apartment' => 'شقة', 'studio' => 'ستوديو', 'commercial' => 'تجاري');
                            echo $subtypes[$p->property_subtype] ?? 'عقار';
                        ?> | <?php echo $is_leased ? __('إدارة', 'sukna') : __('ملك', 'sukna'); ?></span>
                        <small style="color:#64748b; margin-right:10px;"><span class="dashicons dashicons-location" style="font-size:14px;"></span> <?php echo esc_html($p->city . ', ' . ($geo_data[$p->country]['states'][$p->state_emirate]['name'] ?? '')); ?></small>
                    </div>
                </div>
                <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
                    <div style="display:flex; gap:8px; align-items:center;">
                        <button class="sukna-btn sukna-export-pdf-btn" data-id="<?php echo $p->id; ?>" data-name="<?php echo esc_attr($p->name); ?>" title="<?php _e('تحميل التقرير', 'sukna'); ?>" style="padding:5px; background:none; border:none; color:#000;"><span class="dashicons dashicons-pdf"></span></button>
                        <button class="sukna-btn sukna-edit-property" data-property='<?php echo json_encode($p); ?>' style="padding:5px; background:none; border:none; color:#D4AF37;"><span class="dashicons dashicons-edit"></span></button>
                        <button class="sukna-btn sukna-delete-property" data-id="<?php echo $p->id; ?>" style="padding:5px; background:none; border:none; color:#ef4444;"><span class="dashicons dashicons-trash"></span></button>
                    </div>
                    <?php if(!$is_fully_funded): ?>
                        <div style="text-align:left;">
                            <span class="sukna-status-indicator indicator-warning" style="font-size:0.6rem; display:block; margin-bottom:3px;"><?php _e('تمويل قيد التنفيذ', 'sukna'); ?></span>
                            <small style="font-weight:700; color:#ef4444; font-size:0.65rem;"><?php _e('المتبقي:', 'sukna'); ?> <?php echo number_format($perf['total_project_cost'] - $perf['total_invested']); ?> AED</small>
                        </div>
                    <?php else: ?>
                        <span class="sukna-status-indicator indicator-success" style="font-size:0.6rem;"><?php _e('مكتمل التمويل / نشط', 'sukna'); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Property Body -->
            <div style="padding:20px;">
                <!-- Financial Breakdown -->
                <div style="background:#f8fafc; padding:15px; border-radius:10px; margin-bottom:15px; border:1px solid #e2e8f0;">
                    <div style="display:flex; justify-content: space-between; margin-bottom:5px;">
                        <span style="font-size:0.75rem; color:#64748b;"><?php _e('تكلفة المشروع الكلية:', 'sukna'); ?></span>
                        <span style="font-weight:700; color:#000;"><?php echo number_format($perf['total_project_cost']); ?> <small>AED</small></span>
                    </div>
                    <div style="max-height: 50px; overflow-y: auto; margin: 5px 0 10px 0; border-right: 2px solid #f1f5f9; padding-right: 10px;">
                        <?php foreach($setup_items as $si): ?>
                            <div style="display:flex; justify-content: space-between; font-size:0.65rem; color:#94a3b8; margin-bottom:2px;">
                                <span>- <?php echo esc_html($si->item_name); ?></span>
                                <span><?php echo number_format($si->item_cost); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if($p->gov_fees > 0): ?>
                            <div style="display:flex; justify-content: space-between; font-size:0.65rem; color:#94a3b8; margin-bottom:2px;">
                                <span>- <?php _e('الرسوم والضرائب', 'sukna'); ?></span>
                                <span><?php echo number_format($p->gov_fees); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex; justify-content: space-between; border-top:1px dashed #cbd5e1; padding-top:8px; margin-top:5px;">
                        <span style="font-size:0.8rem; font-weight:800; color:#D4AF37;"><?php _e('إجمالي التمويل المحصل:', 'sukna'); ?></span>
                        <span style="font-weight:800; color:#D4AF37;"><?php echo number_format($perf['total_invested']); ?></span>
                    </div>
                </div>

                <!-- Financial Mode Indicators -->
                <div style="margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
                    <small style="font-weight:700; color:#64748b;"><?php _e('مؤشرات الأداء:', 'sukna'); ?></small>
                    <span class="sukna-status-indicator indicator-success" style="font-size:0.6rem;"><?php _e('وضع التشغيل (Live)', 'sukna'); ?></span>
                </div>

                <div class="sukna-grid sukna-property-kpi-row" style="grid-template-columns: repeat(4, 1fr); gap:10px; margin-bottom:20px;">
                    <div class="kpi-indicator" style="background:#f8fafc; padding:12px 5px; border-radius:10px; text-align:center; border:1px solid #eee; cursor:help;" title="<?php _e('نسبة إشغال الوحدات حالياً', 'sukna'); ?>">
                        <small style="display:block; color:#64748b; font-size:0.6rem; margin-bottom:3px;"><?php _e('الإشغال', 'sukna'); ?></small>
                        <span style="font-weight:800; font-size:0.9rem; color: #000;"><?php echo count($rooms) > 0 ? round(($rented_count / count($rooms)) * 100) : 0; ?>%</span>
                    </div>
                    <div class="kpi-indicator" style="background:#f8fafc; padding:12px 5px; border-radius:10px; text-align:center; border:1px solid #eee; cursor:help;" title="<?php _e('إجمالي التحصيل الشهري الفعلي', 'sukna'); ?>">
                        <small style="display:block; color:#64748b; font-size:0.6rem; margin-bottom:3px;"><?php _e('الدخل', 'sukna'); ?></small>
                        <span style="font-weight:800; font-size:0.9rem; color: #059669;"><?php echo number_format($perf['monthly_income']); ?></span>
                    </div>
                    <div class="kpi-indicator" style="background:#f8fafc; padding:12px 5px; border-radius:10px; text-align:center; border:1px solid #eee; cursor:help;" title="<?php _e('صافي الربح بعد خصم المصاريف التشغيلية', 'sukna'); ?>">
                        <small style="display:block; color:#64748b; font-size:0.6rem; margin-bottom:3px;"><?php _e('الربح', 'sukna'); ?></small>
                        <span style="font-weight:800; font-size:0.9rem; color: #059669;"><?php echo number_format($perf['monthly_net']); ?></span>
                    </div>
                    <div class="kpi-indicator" style="background:#f8fafc; padding:12px 5px; border-radius:10px; text-align:center; border:1px solid #eee; cursor:help;" title="<?php _e('معدل العائد على الاستثمار الكلي للمشروع', 'sukna'); ?>">
                        <small style="display:block; color:#64748b; font-size:0.6rem; margin-bottom:3px;"><?php _e('العائد', 'sukna'); ?></small>
                        <span style="font-weight:800; font-size:0.9rem; color: #D4AF37;"><?php echo $perf['roi']; ?>%</span>
                    </div>
                </div>

                <!-- Unit Stats -->
                <div style="display:flex; justify-content: space-between; margin-bottom:20px; background:#fff; border:1px solid #eee; padding:10px; border-radius:8px;">
                     <div style="text-align:center; flex:1;">
                        <div style="font-weight:800;"><?php echo count($rooms); ?></div>
                        <small style="color:#64748b; font-size:0.7rem;"><?php _e('وحدات', 'sukna'); ?></small>
                     </div>
                     <div style="text-align:center; flex:1; border-right:1px solid #eee; border-left:1px solid #eee;">
                        <div style="font-weight:800; color:#ef4444;"><?php echo $rented_count; ?></div>
                        <small style="color:#64748b; font-size:0.7rem;"><?php _e('مؤجرة', 'sukna'); ?></small>
                     </div>
                     <div style="text-align:center; flex:1;">
                        <div style="font-weight:800; color:#059669;"><?php echo (count($rooms) - $rented_count); ?></div>
                        <small style="color:#64748b; font-size:0.7rem;"><?php _e('شاغرة', 'sukna'); ?></small>
                     </div>
                </div>

                <!-- Investment Health & Status -->
                <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding: 10px; background: #fff; border-radius: 8px; border: 1px solid #eee;">
                    <div>
                        <small style="display:block; color:#64748b; font-size:0.6rem;"><?php _e('نسبة التمويل', 'sukna'); ?></small>
                        <div style="font-weight:800; font-size:0.9rem;"><?php echo $perf['funding_completion']; ?>%</div>
                    </div>
                    <div style="text-align: center;">
                        <small style="display:block; color:#64748b; font-size:0.6rem;"><?php _e('صحة الاستثمار', 'sukna'); ?></small>
                        <?php
                            $health = ($perf['roi'] > 5 && $perf['funding_completion'] >= 100) ? 'Strong' : (($perf['funding_completion'] >= 50) ? 'Stable' : 'Risk');
                            $health_map = array('Strong' => array('قوية', 'indicator-success'), 'Stable' => array('مستقرة', 'indicator-accent'), 'Risk' => array('مخاطرة', 'indicator-danger'));
                        ?>
                        <span class="sukna-status-indicator <?php echo $health_map[$health][1]; ?>" style="font-size:0.6rem;"><?php echo $health_map[$health][0]; ?></span>
                    </div>
                    <div style="text-align: left;">
                        <small style="display:block; color:#64748b; font-size:0.6rem;"><?php _e('حالة الالتزام', 'sukna'); ?></small>
                        <span class="sukna-status-indicator indicator-success" style="font-size:0.6rem;"><?php _e('ملتزم', 'sukna'); ?></span>
                    </div>
                </div>

                <!-- Investors Section -->
                <div style="margin-bottom:20px;">
                    <button class="sukna-collapse-trigger" style="width:100%; background:#f8fafc; border:1px solid #eee; border-radius:8px; padding:8px 12px; display:flex; justify-content:space-between; align-items:center; cursor:pointer;">
                        <span style="font-size:0.8rem; font-weight:700;"><span class="dashicons dashicons-groups" style="font-size:16px; margin-left:5px;"></span> <?php _e('قائمة الشركاء والمستثمرين', 'sukna'); ?></span>
                        <span style="background:#000; color:#fff; padding:2px 8px; border-radius:10px; font-size:0.7rem;"><?php echo count($investors_linked); ?></span>
                    </button>
                    <div class="sukna-collapse-content" style="display:none; margin-top:10px; max-height:200px; overflow-y:auto; border:1px solid #eee; border-radius:8px; padding:5px;">
                        <?php if(empty($investors_linked)): ?>
                            <small style="color:#94a3b8; text-align:center; display:block; padding:10px;"><?php _e('لا يوجد مستثمرون', 'sukna'); ?></small>
                        <?php else: ?>
                            <?php foreach($investors_linked as $inv):
                                $total_cost = $perf['total_project_cost'];
                                $share_pct = $total_cost > 0 ? ($inv->amount / $total_cost) * 100 : 0;
                                $monthly_profit_share = ($perf['monthly_net'] * ($share_pct / 100));
                            ?>
                                <div style="display:flex; justify-content: space-between; font-size:0.75rem; padding:8px; border-bottom:1px solid #f9f9f9; background:#fff; margin-bottom:5px; border-radius:5px;">
                                    <div>
                                        <strong style="display:block;"><?php echo esc_html($inv->investor_name); ?></strong>
                                        <small style="color:#94a3b8;"><?php echo round($share_pct, 1); ?>% ملكية</small>
                                    </div>
                                    <div style="text-align:left;">
                                        <div style="font-weight:700;"><?php echo number_format($inv->amount); ?></div>
                                        <small style="color:#059669; display:block; font-size:0.6rem;"><?php echo number_format($monthly_profit_share); ?> / ربح</small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="display:flex; gap:8px;">
                    <button class="sukna-btn sukna-manage-rooms" data-id="<?php echo $p->id; ?>" style="flex:2; font-size:0.8rem; background:#000; border:none;"><?php _e('إدارة الوحدات', 'sukna'); ?></button>
                    <button class="sukna-btn sukna-record-expense-btn" data-id="<?php echo $p->id; ?>" style="flex:1; font-size:0.8rem; background:#333; border:none;"><?php _e('المصاريف', 'sukna'); ?></button>
                </div>
                <div style="display:flex; gap:8px; margin-top:8px;">
                    <?php if($is_admin): ?>
                        <button class="sukna-btn sukna-manage-investors" data-id="<?php echo $p->id; ?>" style="flex:1; font-size:0.8rem; background:#D4AF37; color:#000 !important; border:none;"><?php _e('الشركاء', 'sukna'); ?></button>
                        <button class="sukna-btn sukna-distribute-revenue-btn" data-id="<?php echo $p->id; ?>" data-net="<?php echo $perf['net']; ?>" style="flex:1; font-size:0.8rem; background:#059669; border:none;"><?php _e('توزيع', 'sukna'); ?></button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Property Modal (3-Step Wizard) -->
<div id="sukna-property-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0, 0, 0, 0.6); z-index:10001; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="sukna-card" style="width:100%; max-width:600px; padding:40px; border-radius:12px;">
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h3 id="prop-modal-title" style="margin:0; font-size:1.4rem;"><?php _e('بيانات العقار', 'sukna'); ?></h3>
            <div style="display:flex; gap:5px;" id="prop-wizard-dots">
                <span class="dot active" data-step="1"></span>
                <span class="dot" data-step="2"></span>
                <span class="dot" data-step="3"></span>
            </div>
        </div>

        <form id="sukna-property-form">
            <input type="hidden" name="id" id="prop-id">

            <!-- Step 1: Basic Information -->
            <div id="prop-step-1" class="prop-wizard-step">
                <div class="sukna-grid" style="grid-template-columns: 1.5fr 1fr; gap:15px;">
                    <div class="sukna-form-group">
                        <input type="text" name="name" id="prop-name" placeholder="<?php _e('اسم العقار / المشروع', 'sukna'); ?>" required>
                    </div>
                    <div class="sukna-form-group">
                        <select name="property_subtype" id="prop-subtype">
                            <option value="building"><?php _e('بناية كاملة', 'sukna'); ?></option>
                            <option value="villa"><?php _e('فيلا', 'sukna'); ?></option>
                            <option value="apartment"><?php _e('شقة', 'sukna'); ?></option>
                            <option value="studio"><?php _e('ستوديو', 'sukna'); ?></option>
                            <option value="commercial"><?php _e('محل / وحدة تجارية', 'sukna'); ?></option>
                            <option value="other"><?php _e('أخرى', 'sukna'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="sukna-grid" style="grid-template-columns: 1fr 1fr 1fr; gap:15px;">
                    <div class="sukna-form-group">
                        <input type="text" name="floor_number" id="prop-floor" placeholder="<?php _e('رقم الطابق', 'sukna'); ?>">
                    </div>
                    <div class="sukna-form-group">
                        <input type="text" name="apartment_number" id="prop-apt-number" placeholder="<?php _e('رقم الشقة (إن وجد)', 'sukna'); ?>">
                    </div>
                    <div class="sukna-form-group">
                        <select name="property_type" id="prop-type">
                            <option value="owned"><?php _e('نوع التعاقد: ملك', 'sukna'); ?></option>
                            <option value="leased"><?php _e('نوع التعاقد: إدارة وتشغيل', 'sukna'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="sukna-grid" style="grid-template-columns: 1fr 1fr 1fr; gap:15px;">
                    <div class="sukna-form-group">
                        <select name="country" id="prop-country" required>
                            <option value=""><?php _e('الدولة', 'sukna'); ?></option>
                            <?php foreach($geo_data as $code => $data): ?>
                                <option value="<?php echo $code; ?>"><?php echo $data['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="sukna-form-group">
                        <select name="state_emirate" id="prop-state" required>
                            <option value=""><?php _e('الولاية / الإمارة', 'sukna'); ?></option>
                        </select>
                    </div>
                    <div class="sukna-form-group">
                        <input type="text" name="city" id="prop-city" placeholder="<?php _e('المدينة', 'sukna'); ?>">
                    </div>
                </div>

                <div class="sukna-form-group">
                    <textarea name="address" id="prop-address" placeholder="<?php _e('العنوان بالتفصيل', 'sukna'); ?>" rows="2"></textarea>
                </div>
            </div>

            <!-- Step 2: Structure -->
            <div id="prop-step-2" class="prop-wizard-step" style="display:none;">
                <div class="sukna-grid" style="grid-template-columns: 1fr; gap:15px;">
                    <div class="sukna-form-group">
                        <input type="number" name="total_rooms" id="prop-total-rooms" placeholder="<?php _e('إجمالي عدد الوحدات (الغرف)', 'sukna'); ?>" required>
                    </div>
                </div>

                <div style="background:#f8fafc; padding:20px; border-radius:10px; border:1px solid #e2e8f0; margin-bottom:15px;">
                    <h4 style="margin:0 0 15px 0; font-size:0.9rem;"><?php _e('تكاليف التجهيز والإعداد', 'sukna'); ?></h4>
                    <div id="setup-items-container"></div>
                    <button type="button" id="add-setup-item-btn" class="sukna-btn" style="background:#333; padding:5px 15px; font-size:0.75rem;"><span class="dashicons dashicons-plus" style="font-size:14px; width:14px; height:14px; margin-left:5px;"></span><?php _e('إضافة بند تجهيز', 'sukna'); ?></button>
                </div>
            </div>

            <!-- Step 3: Financial & Timeline -->
            <div id="prop-step-3" class="prop-wizard-step" style="display:none;">
                <div class="sukna-grid" style="grid-template-columns: 1.5fr 1fr; gap:15px;">
                    <div class="sukna-form-group">
                        <input type="number" step="0.01" name="annual_rent" id="prop-annual-rent" placeholder="<?php _e('الإيجار السنوي للعقار', 'sukna'); ?>" required>
                        <input type="hidden" name="base_value" id="prop-base-value">
                    </div>
                    <div class="sukna-form-group">
                        <input type="number" step="0.01" name="gov_fees" id="prop-gov-fees" placeholder="<?php _e('الرسوم الحكومية والتنظيمية', 'sukna'); ?>">
                    </div>
                </div>

                <div class="sukna-grid" style="grid-template-columns: 1fr 1fr 1fr; gap:15px;">
                    <div class="sukna-form-group">
                        <input type="number" step="0.01" name="monthly_fixed_opex" id="prop-fixed-opex" placeholder="<?php _e('تكاليف التشغيل الشهرية', 'sukna'); ?>">
                    </div>
                    <div class="sukna-form-group">
                        <input type="number" step="0.01" name="additional_setup_cost" id="prop-add-setup" placeholder="<?php _e('تجهيزات إضافية (Fit-out)', 'sukna'); ?>">
                    </div>
                    <div class="sukna-form-group">
                        <input type="number" step="0.01" name="projected_rent_per_room" id="prop-proj-rent" placeholder="<?php _e('الإيجار المتوقع للوحدة', 'sukna'); ?>">
                    </div>
                </div>

                <div class="sukna-grid" style="grid-template-columns: 1fr 1fr 1fr; gap:15px;">
                    <div class="sukna-form-group">
                        <label style="display:block; font-size:0.7rem; color:#64748b; margin-bottom:4px;"><?php _e('تاريخ بداية العقد', 'sukna'); ?></label>
                        <input type="date" name="contract_start_date" id="prop-contract-start">
                    </div>
                    <div class="sukna-form-group">
                        <label style="display:block; font-size:0.7rem; color:#64748b; margin-bottom:4px;"><?php _e('تاريخ تفعيل الاستثمار', 'sukna'); ?></label>
                        <input type="date" name="investment_start_date" id="prop-invest-start">
                    </div>
                    <div class="sukna-form-group">
                        <label style="display:block; font-size:0.7rem; color:#64748b; margin-bottom:4px;"><?php _e('مدة العقد (سنوات)', 'sukna'); ?></label>
                        <select name="contract_duration" id="prop-duration">
                            <option value="1">1 <?php _e('سنة', 'sukna'); ?></option>
                            <option value="2">2 <?php _e('سنة', 'sukna'); ?></option>
                            <option value="3">3 <?php _e('سنوات', 'sukna'); ?></option>
                        </select>
                    </div>
                    <div class="sukna-form-group">
                        <label style="display:block; font-size:0.7rem; color:#64748b; margin-bottom:4px;"><?php _e('دفعات رأس المال / سنة', 'sukna'); ?></label>
                        <select name="installments_per_year" id="prop-installments">
                            <option value="1">1 (<?php _e('سنوي', 'sukna'); ?>)</option>
                            <option value="2">2 (<?php _e('نصف سنوي', 'sukna'); ?>)</option>
                            <option value="3">3 (<?php _e('كل 4 أشهر', 'sukna'); ?>)</option>
                            <option value="4" selected>4 (<?php _e('ربع سنوي', 'sukna'); ?>)</option>
                            <option value="6">6 (<?php _e('كل شهرين', 'sukna'); ?>)</option>
                        </select>
                    </div>
                </div>

                <div class="sukna-form-group">
                    <select name="owner_id" id="prop-owner-id">
                        <option value=""><?php _e('اختر المالك / المدير المسؤول', 'sukna'); ?></option>
                        <?php foreach($owners as $o): ?>
                            <option value="<?php echo $o->id; ?>"><?php echo esc_html($o->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="prop-summary-final" style="background:#000; color:#fff; padding:20px; border-radius:12px; margin-top:20px;">
                    <div style="display:flex; justify-content: space-between; margin-bottom:10px; opacity:0.8;">
                        <span><?php _e('قيمة العقار:', 'sukna'); ?></span>
                        <span id="sum-base-val">0</span>
                    </div>
                    <div style="display:flex; justify-content: space-between; margin-bottom:10px; opacity:0.8;">
                        <span><?php _e('إجمالي التجهيزات:', 'sukna'); ?></span>
                        <span id="sum-setup-val">0</span>
                    </div>
                    <div style="display:flex; justify-content: space-between; border-top:1px solid #333; padding-top:10px; font-weight:800;">
                        <span style="color:#D4AF37;"><?php _e('إجمالي رأس المال المطلوب:', 'sukna'); ?></span>
                        <span id="sum-total-val" style="color:#D4AF37; font-size:1.2rem;">0</span>
                    </div>
                </div>
            </div>

            <div style="display:flex; gap:15px; margin-top:30px; border-top:1px solid #eee; padding-top:20px;">
                <button type="button" id="prop-prev" class="sukna-btn" style="flex:1; background:#64748b; border:none; display:none;"><?php _e('السابق', 'sukna'); ?></button>
                <button type="button" id="prop-next" class="sukna-btn" style="flex:1; background:#000; border:none;"><?php _e('التالي', 'sukna'); ?></button>
                <button type="submit" id="prop-submit" class="sukna-btn sukna-btn-accent" style="flex:2; display:none;"><?php _e('حفظ وتأكيد البيانات', 'sukna'); ?></button>
                <button type="button" class="sukna-btn close-prop-modal" style="background:#333; border:none;"><?php _e('إلغاء', 'sukna'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Room Management Modal -->
<div id="sukna-room-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0, 0, 0, 0.6); z-index:10001; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="sukna-card" style="width:100%; max-width:900px; max-height: 90vh; overflow-y: auto; padding:40px; border-radius:12px;">
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h3 style="margin:0; font-size:1.4rem;"><?php _e('إدارة الوحدات والعقود', 'sukna'); ?></h3>
                <small style="color:#64748b;"><?php _e('يمكنك إعادة تعيين كافة الوحدات كشاغرة في بداية كل شهر.', 'sukna'); ?></small>
            </div>
            <div style="display:flex; gap:10px; align-items:center;">
                <button type="button" id="sukna-reset-rooms-btn" class="sukna-btn" style="background:#ef4444; border:none; padding: 5px 15px; border-radius: 4px; font-size:0.75rem;"><span class="dashicons dashicons-calendar-alt" style="margin-left:5px;"></span><?php _e('تصفير كافة الوحدات (بداية الشهر)', 'sukna'); ?></button>
                <button type="button" class="sukna-btn close-room-modal" style="background:#333; border:none; padding: 5px 15px; border-radius: 4px;">X</button>
            </div>
        </div>

        <div style="display:flex; gap:20px; margin-bottom:30px;">
            <!-- Add Room Form -->
            <form id="sukna-room-quick-add" style="flex:1; background:#f8fafc; padding:20px; border-radius:8px; border: 1px solid #e2e8f0;">
                <h4 style="margin:0 0 15px 0;"><?php _e('إضافة وحدة سريعة', 'sukna'); ?></h4>
                <input type="hidden" name="property_id" id="room-property-id">
                <input type="hidden" name="status" value="available">
                <div class="sukna-grid" style="grid-template-columns: 1fr 1fr; gap:10px;">
                    <div class="sukna-form-group" style="margin-bottom:10px;">
                        <input type="text" name="room_number" placeholder="<?php _e('رقم الوحدة', 'sukna'); ?>" required>
                    </div>
                    <div class="sukna-form-group" style="margin-bottom:10px;">
                        <input type="number" step="0.01" name="rental_price" placeholder="<?php _e('سعر الإيجار', 'sukna'); ?>" required>
                    </div>
                </div>
                <div class="sukna-form-group" style="margin-bottom:10px;">
                    <select name="payment_frequency">
                        <option value="monthly"><?php _e('شهري', 'sukna'); ?></option>
                        <option value="quarterly"><?php _e('ربع سنوي', 'sukna'); ?></option>
                        <option value="annually"><?php _e('سنوي', 'sukna'); ?></option>
                    </select>
                </div>
                <button type="submit" class="sukna-btn" style="width:100%; background:#000; border:none; border-radius: 6px; padding:8px;"><?php _e('إضافة الوحدة', 'sukna'); ?></button>
            </form>

            <!-- Contract Form (initially hidden) -->
            <form id="sukna-contract-form" style="display:none; flex:2; background:#fff; padding:20px; border-radius:8px; border: 2px solid #D4AF37;">
                <h4 style="margin:0 0 15px 0; color:#D4AF37;"><?php _e('تفعيل عقد إيجار جديد', 'sukna'); ?></h4>
                <input type="hidden" name="room_id" id="contract-room-id">
                <div class="sukna-grid" style="grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="sukna-form-group">
                        <select name="tenant_id" id="contract-tenant-id">
                            <option value=""><?php _e('مستأجر غير مسجل (ضيف)', 'sukna'); ?></option>
                            <?php foreach($tenants as $t): ?>
                                <option value="<?php echo $t->id; ?>"><?php echo esc_html($t->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="sukna-form-group">
                        <input type="text" name="guest_tenant_name" id="contract-guest-name" placeholder="<?php _e('اسم الضيف / المستأجر', 'sukna'); ?>">
                    </div>
                </div>
                <div class="sukna-grid" style="grid-template-columns: 1fr 1fr 1fr; gap:15px; margin-top:10px;">
                    <div class="sukna-form-group">
                        <input type="text" onfocus="(this.type='date')" name="start_date" placeholder="<?php _e('تاريخ البداية', 'sukna'); ?>" required>
                    </div>
                    <div class="sukna-form-group">
                        <input type="number" name="duration_years" placeholder="<?php _e('المدة (سنوات)', 'sukna'); ?>" required value="1">
                    </div>
                    <div class="sukna-form-group">
                        <input type="number" step="0.01" name="total_value" placeholder="<?php _e('إجمالي العقد', 'sukna'); ?>" required>
                    </div>
                </div>
                <div style="display:flex; gap:10px; margin-top:15px;">
                    <button type="submit" class="sukna-btn" style="flex:2; background:#D4AF37; color:#000 !important; border:none; border-radius: 6px; font-weight:700;"><?php _e('تأكيد وتفعيل العقد', 'sukna'); ?></button>
                    <button type="button" onclick="jQuery('#sukna-contract-form').slideUp()" class="sukna-btn" style="flex:1; background:#64748b; border:none; border-radius: 6px;"><?php _e('إلغاء', 'sukna'); ?></button>
                </div>
            </form>
        </div>

        <div id="sukna-rooms-grid" class="sukna-grid" style="grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));">
            <!-- Loaded via AJAX -->
        </div>

        <!-- Room Details Panel (Hidden by default) -->
        <div id="sukna-room-details" style="display:none; margin-top:30px; background:#fafafa; border:1px solid #ddd; padding:20px; border-radius:8px;">
            <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom:15px;">
                <h4 style="margin:0;"><?php _e('تفاصيل الوحدة', 'sukna'); ?> #<span id="detail-room-number"></span></h4>
                <button type="button" onclick="jQuery('#sukna-room-details').fadeOut()" class="sukna-btn" style="background:none; border:none; color:#666; padding:0;"><span class="dashicons dashicons-no-alt"></span></button>
            </div>
            <div class="sukna-grid" style="grid-template-columns: 1fr 1fr; gap:20px;">
                <div>
                    <p><strong><?php _e('الحالة:', 'sukna'); ?></strong> <span id="detail-room-status"></span></p>
                    <p><strong><?php _e('السعر:', 'sukna'); ?></strong> <span id="detail-room-price"></span> AED</p>
                </div>
                <div id="detail-tenant-info" style="display:none;">
                    <p><strong><?php _e('المستأجر:', 'sukna'); ?></strong> <span id="detail-tenant-name"></span></p>
                    <p><strong><?php _e('تاريخ الإيجار:', 'sukna'); ?></strong> <span id="detail-rental-date"></span></p>
                </div>
            </div>

            <div id="detail-installments-section" style="display:none; margin-top:15px; padding-top:15px; border-top:1px dashed #eee;">
                <!-- Loaded via JS -->
            </div>
            <div style="margin-top:15px; display:flex; flex-wrap:wrap; gap:10px;">
                <button id="detail-contract-btn" class="sukna-btn" style="background:#D4AF37; color:#000 !important; border:none; font-size:0.8rem; display:none;"><?php _e('تفعيل عقد جديد (Check-in)', 'sukna'); ?></button>
                <button id="detail-terminate-btn" class="sukna-btn" style="background:#ef4444; border:none; font-size:0.8rem; display:none;"><?php _e('إنهاء التعاقد (Check-out)', 'sukna'); ?></button>
                <button id="detail-renew-btn" class="sukna-btn" style="background:#000; border:none; font-size:0.8rem; display:none;"><?php _e('تجديد العقد', 'sukna'); ?></button>
                <button id="detail-delete-btn" class="sukna-btn" style="background:#333; border:none; font-size:0.8rem;"><?php _e('حذف الوحدة نهائياً', 'sukna'); ?></button>
            </div>
            <div style="margin-top:10px; font-size:0.7rem; color:#64748b;">
                * <?php _e('وقت تسجيل المغادرة القياسي (Check-out) هو 12:00 ظهراً.', 'sukna'); ?>
            </div>
        </div>
    </div>
</div>

<!-- Expense Modal -->
<div id="sukna-expense-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0, 0, 0, 0.6); z-index:10001; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="sukna-card" style="width:100%; max-width:500px; padding:40px; border-radius:12px;">
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h3 style="margin:0; font-size:1.4rem;"><?php _e('تسجيل مصاريف تشغيلية', 'sukna'); ?></h3>
            <button type="button" class="sukna-btn close-expense-modal" style="background:#333; border:none; padding: 5px 15px; border-radius: 4px;">X</button>
        </div>
        <form id="sukna-expense-form">
            <input type="hidden" name="property_id" id="expense-property-id">
            <div class="sukna-form-group">
                <select name="category" required>
                    <option value="electricity"><?php _e('كهرباء', 'sukna'); ?></option>
                    <option value="water"><?php _e('مياه', 'sukna'); ?></option>
                    <option value="internet"><?php _e('إنترنت', 'sukna'); ?></option>
                    <option value="cleaning"><?php _e('نظافة', 'sukna'); ?></option>
                    <option value="maintenance"><?php _e('صيانة', 'sukna'); ?></option>
                </select>
            </div>
            <div class="sukna-form-group">
                <input type="number" step="0.01" name="amount" placeholder="<?php _e('المبلغ', 'sukna'); ?>" required>
            </div>
            <button type="submit" class="sukna-btn" style="width:100%; background:#000; border:none; border-radius: 8px;"><?php _e('حفظ المصروفات', 'sukna'); ?></button>
        </form>
    </div>
</div>

<!-- Investor Management Modal -->
<div id="sukna-investor-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0, 0, 0, 0.6); z-index:10001; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="sukna-card" style="width:100%; max-width:600px; padding:40px; border-radius:12px;">
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h3 style="margin:0; font-size:1.4rem;"><?php _e('إدارة المستثمرين في العقار', 'sukna'); ?></h3>
            <button type="button" class="sukna-btn close-investor-modal" style="background:#333; border:none; padding: 5px 15px; border-radius: 4px;">X</button>
        </div>

        <div id="investment-lock-msg" style="display:none; background:#fef2f2; color:#991b1b; padding:15px; border-radius:8px; margin-bottom:20px; text-align:center; font-weight:700; border:1px solid #fee2e2;">
            <span class="dashicons dashicons-lock" style="vertical-align:middle; margin-left:5px;"></span> <?php _e('تم اكتمال التمويل لهذا المشروع. الاستثمار مغلق حالياً.', 'sukna'); ?>
        </div>

        <form id="sukna-investment-form" style="background:#f8fafc; padding:20px; border-radius:8px; margin-bottom:30px; border: 1px solid #e2e8f0;">
            <input type="hidden" name="property_id" id="invest-property-id">
            <div class="sukna-form-group">
                <select name="investor_id" required>
                    <option value=""><?php _e('اختر المستثمر', 'sukna'); ?></option>
                    <?php foreach($investors as $i): ?>
                        <option value="<?php echo $i->id; ?>"><?php echo esc_html($i->name); ?></option>
                    <?php endforeach; ?>
                    <?php foreach($owners as $o): ?>
                        <option value="<?php echo $o->id; ?>"><?php echo esc_html($o->name); ?> (<?php _e('مالك', 'sukna'); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sukna-grid" style="grid-template-columns: 2fr 1fr; gap: 10px;">
                <div class="sukna-form-group">
                    <input type="number" step="0.01" name="amount" placeholder="<?php _e('مبلغ المساهمة النقدية', 'sukna'); ?>" required>
                </div>
                <div class="sukna-form-group">
                    <input type="number" name="installments_paid" placeholder="<?php _e('عدد الدفعات', 'sukna'); ?>" value="1">
                </div>
            </div>
            <button type="submit" class="sukna-btn" style="width:100%; background:#000; border:none; border-radius: 8px;"><?php _e('تسجيل المساهمة الاستثمارية', 'sukna'); ?></button>
        </form>

        <div id="sukna-investments-list">
            <!-- Loaded via AJAX -->
        </div>
    </div>
</div>
