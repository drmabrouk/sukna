<?php
$filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : 'all';
$tracking_items = AC_IS_Filters::get_all_tracking( array('status' => $filter_status) );
?>

<?php
$filter_search = isset($_GET['filter_search']) ? sanitize_text_field($_GET['filter_search']) : '';
$tracking_items = AC_IS_Filters::get_all_tracking( array('status' => $filter_status, 'search' => $filter_search) );
?>
<div class="ac-is-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap:15px;">
    <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:var(--ac-sidebar-bg);"><?php _e('متابعة صيانة فلاتر المياه', 'ac-inventory-system'); ?></h2>

    <div style="display:flex; gap:10px; align-items:center;">
        <form method="get" style="display:flex; gap:5px;">
            <input type="hidden" name="ac_view" value="filter-tracking">
            <input type="hidden" name="filter_status" value="<?php echo $filter_status; ?>">
            <input type="text" name="filter_search" value="<?php echo esc_attr($filter_search); ?>" placeholder="<?php _e('بحث عميل، هاتف، فلتر...', 'ac-inventory-system'); ?>" style="padding:8px 15px; border-radius:20px; border:1px solid var(--ac-border); font-size:0.85rem; width:250px;">
            <button type="submit" class="ac-is-btn" style="padding:8px 12px;"><span class="dashicons dashicons-search"></span></button>
        </form>

        <button id="ac-is-filter-settings-btn" class="ac-is-btn" style="background:#64748b; padding:8px;"><span class="dashicons dashicons-admin-generic"></span></button>
        <a href="<?php echo add_query_arg('filter_status', 'all'); ?>" class="ac-is-btn" style="<?php echo ($filter_status == 'all' ? '' : 'background:#64748b;'); ?>"><?php _e('الكل', 'ac-inventory-system'); ?></a>
        <a href="<?php echo add_query_arg('filter_status', 'alert'); ?>" class="ac-is-btn" style="<?php echo ($filter_status == 'alert' ? 'background:#ef4444;' : 'background:#64748b;'); ?>"><?php _e('تنبيهات', 'ac-inventory-system'); ?></a>
    </div>
</div>

<div style="background:#fff; border:1px solid var(--ac-border); border-radius: 8px; overflow:hidden;">
    <table class="ac-is-table">
        <thead>
            <tr>
                <th class="col-filter"><?php _e('العملية / الفلتر', 'ac-inventory-system'); ?></th>
                <th class="col-customer"><?php _e('العميل', 'ac-inventory-system'); ?></th>
                <th class="col-status"><?php _e('حالة الشمعات', 'ac-inventory-system'); ?></th>
                <th class="col-actions"><?php _e('إجراءات', 'ac-inventory-system'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( $tracking_items ) : foreach ( $tracking_items as $op ) :
                $alerts = 0;
                foreach($op->stages as $s) {
                    if( (strtotime($s->expiry_date) - time()) <= 7*86400 ) $alerts++;
                }
            ?>
                <tr>
                    <td class="col-filter">
                        <strong><?php echo esc_html($op->product_name); ?></strong><br>
                        <small style="color:#64748b;">#INV-<?php echo str_pad($op->invoice_id, 8, '0', STR_PAD_LEFT); ?></small>
                    </td>
                    <td class="col-customer">
                        <strong><?php echo esc_html($op->customer_name); ?></strong><br>
                        <button class="ac-is-btn view-customer-details"
                                data-name="<?php echo esc_attr($op->customer_name); ?>"
                                data-phone="<?php echo esc_attr($op->customer_phone); ?>"
                                data-address="<?php echo esc_attr($op->customer_address); ?>"
                                data-email="<?php echo esc_attr($op->customer_email); ?>"
                                style="padding:2px 8px; font-size:0.7rem; background:#64748b; margin-top:4px;">
                            <?php _e('تفاصيل العميل', 'ac-inventory-system'); ?>
                        </button>
                    </td>
                    <td class="col-status">
                        <div style="display:flex; gap:5px; flex-wrap: wrap;">
                            <?php foreach($op->stages as $s):
                                $days_left = (strtotime($s->expiry_date) - time()) / 86400;
                                $st_class = ($days_left <= 0) ? 'capsule-danger' : (($days_left <= 7) ? 'capsule-warning' : 'capsule-success');
                            ?>
                                <span class="ac-is-capsule <?php echo $st_class; ?>" title="<?php _e('تاريخ التغيير:', 'ac-inventory-system'); ?> <?php echo $s->expiry_date; ?>">
                                    <?php _e('شمعة', 'ac-inventory-system'); ?> <?php echo $s->stage_number; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </td>
                    <td class="col-actions">
                        <button class="ac-is-btn view-candle-details" data-stages='<?php echo json_encode($op->stages); ?>' style="padding:6px 12px; font-size:0.8rem;">
                            <span class="dashicons dashicons-list-view" style="margin-left:5px;"></span><?php _e('إدارة الشمعات', 'ac-inventory-system'); ?>
                        </button>
                    </td>
                </tr>
            <?php endforeach; else : ?>
                <tr><td colspan="4" style="text-align:center; padding:40px; color:#94a3b8;"><?php _e('لا توجد بيانات متابعة حالياً.', 'ac-inventory-system'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Customer Details Modal -->
<div id="ac-is-customer-modal" class="ac-is-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10010; align-items:center; justify-content:center;">
    <div class="ac-is-card" style="width:400px; background:#fff; padding:25px;">
        <h3><?php _e('تفاصيل العميل', 'ac-inventory-system'); ?></h3>
        <div id="customer-modal-body" style="line-height:2;"></div>
        <button class="ac-is-btn" style="width:100%; margin-top:20px; background:#64748b;" onclick="jQuery('#ac-is-customer-modal').fadeOut(200)"><?php _e('إغلاق', 'ac-inventory-system'); ?></button>
    </div>
</div>

<!-- Candle Management Modal -->
<div id="ac-is-candle-modal" class="ac-is-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10010; align-items:center; justify-content:center;">
    <div class="ac-is-card" style="width:600px; background:#fff; padding:25px; max-height:80vh; overflow-y:auto;">
        <h3><?php _e('إدارة شمعات الفلتر', 'ac-inventory-system'); ?></h3>
        <table class="ac-is-table">
            <thead>
                <tr>
                    <th><?php _e('رقم الشمعة', 'ac-inventory-system'); ?></th>
                    <th><?php _e('تاريخ التغيير القادم', 'ac-inventory-system'); ?></th>
                    <th><?php _e('الحالة', 'ac-inventory-system'); ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="candle-modal-body"></tbody>
        </table>
        <button class="ac-is-btn" style="width:100%; margin-top:20px; background:#64748b;" onclick="jQuery('#ac-is-candle-modal').fadeOut(200)"><?php _e('إغلاق', 'ac-inventory-system'); ?></button>
    </div>
</div>

<!-- Filter Settings Modal -->
<div id="ac-is-filter-settings-modal" class="ac-is-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10010; align-items:center; justify-content:center;">
    <div class="ac-is-card" style="width:450px; background:#fff; padding:25px;">
        <h3><?php _e('إعدادات مدد صلاحية الشمعات (بالشهور)', 'ac-inventory-system'); ?></h3>
        <form id="ac-is-filter-settings-form">
            <?php for($i=1; $i<=7; $i++):
                $val = $wpdb->get_var($wpdb->prepare("SELECT setting_value FROM {$wpdb->prefix}ac_is_settings WHERE setting_key = %s", "filter_stage_{$i}_validity")) ?: (($i==1)?3:(($i<=3)?6:12));
            ?>
                <div class="ac-is-form-group" style="display:flex; align-items:center; gap:10px;">
                    <label style="flex:1;"> <?php _e('الشمعة رقم', 'ac-inventory-system'); ?> <?php echo $i; ?>: </label>
                    <input type="number" name="filter_stage_<?php echo $i; ?>_validity" value="<?php echo $val; ?>" style="width:80px;">
                </div>
            <?php endfor; ?>
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" class="ac-is-btn" style="flex:1;"><?php _e('حفظ الإعدادات', 'ac-inventory-system'); ?></button>
                <button type="button" class="ac-is-btn" style="flex:1; background:#64748b;" onclick="jQuery('#ac-is-filter-settings-modal').fadeOut(200)"><?php _e('إلغاء', 'ac-inventory-system'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.view-customer-details').on('click', function() {
        const d = $(this).data();
        let html = `<strong><?php _e('الاسم:', 'ac-inventory-system'); ?></strong> ${d.name}<br>`;
        html += `<strong><?php _e('الهاتف:', 'ac-inventory-system'); ?></strong> ${d.phone}<br>`;
        html += `<strong><?php _e('العنوان:', 'ac-inventory-system'); ?></strong> ${d.address || '-'}<br>`;
        html += `<strong><?php _e('الإيميل:', 'ac-inventory-system'); ?></strong> ${d.email || '-'}`;
        $('#customer-modal-body').html(html);
        $('#ac-is-customer-modal').css('display', 'flex').hide().fadeIn(200);
    });

    $('.view-candle-details').on('click', function() {
        const stages = $(this).data('stages');
        let html = '';
        stages.forEach(s => {
            const days_left = (new Date(s.expiry_date) - new Date()) / 86400000;
            const st_class = (days_left <= 0) ? 'capsule-danger' : ((days_left <= 7) ? 'capsule-warning' : 'capsule-success');
            const st_text = (days_left <= 0) ? '<?php _e('منتهية', 'ac-inventory-system'); ?>' : ((days_left <= 7) ? '<?php _e('قربت', 'ac-inventory-system'); ?>' : '<?php _e('نشطة', 'ac-inventory-system'); ?>');

            html += `<tr>
                <td><?php _e('شمعة', 'ac-inventory-system'); ?> ${s.stage_number}</td>
                <td><strong>${s.expiry_date}</strong></td>
                <td><span class="ac-is-capsule ${st_class}">${st_text}</span></td>
                <td><button class="ac-is-btn ac-is-replace-candle" data-id="${s.id}" style="padding:4px 8px; font-size:0.7rem; background:#059669;"><?php _e('تأكيد التغيير', 'ac-inventory-system'); ?></button></td>
            </tr>`;
        });
        $('#candle-modal-body').html(html);
        $('#ac-is-candle-modal').css('display', 'flex').hide().fadeIn(200);
    });

    $(document).on('click', '.ac-is-replace-candle', function() {
        const id = $(this).data('id');
        if(!confirm('<?php _e('تأكيد تغيير الشمعة؟', 'ac-inventory-system'); ?>')) return;
        $.post(ac_is_ajax.ajax_url, { action: 'ac_is_replace_candle', tracking_id: id, nonce: ac_is_ajax.nonce }, () => location.reload());
    });

    $('#ac-is-filter-settings-btn').on('click', () => $('#ac-is-filter-settings-modal').css('display', 'flex').hide().fadeIn(200));

    $('#ac-is-filter-settings-form').on('submit', function(e) {
        e.preventDefault();
        $.post(ac_is_ajax.ajax_url, $(this).serialize() + '&action=ac_is_save_settings&nonce=' + ac_is_ajax.nonce, () => location.reload());
    });
});
</script>
