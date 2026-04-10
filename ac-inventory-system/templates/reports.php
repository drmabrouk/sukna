<?php
$daily = AC_IS_Reports::get_daily_sales();
$monthly = AC_IS_Reports::get_monthly_sales();
$low_stock = AC_IS_Reports::get_stock_overview();

global $wpdb;
$settings = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ac_is_settings", OBJECT_K );
$company_name = $settings['company_name']->setting_value ?? get_bloginfo('name');
?>
<div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2><?php _e('تقارير المبيعات والمخزون', 'ac-inventory-system'); ?></h2>
    <h3 class="print-only" style="margin:0;"><?php echo esc_html($company_name); ?></h3>
</div>

<div class="ac-is-summary-cards">
    <div class="ac-is-card">
        <h3><?php _e('مبيعات اليوم', 'ac-inventory-system'); ?></h3>
        <div class="value"><?php echo number_format($daily[0]->total ?? 0, 2); ?></div>
        <small><?php echo ($daily[0]->count ?? 0) . ' ' . __('عمليات', 'ac-inventory-system'); ?></small>
    </div>
    <div class="ac-is-card">
        <h3><?php _e('تنبيهات المخزون', 'ac-inventory-system'); ?></h3>
        <div class="value" style="color: #e53e3e;"><?php echo count($low_stock); ?></div>
        <small><?php _e('منتجات قاربت على النفاد', 'ac-inventory-system'); ?></small>
    </div>
</div>

<div class="ac-is-report-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap:30px;">
    <div class="ac-is-report-section">
        <h3><?php _e('ملخص المبيعات اليومية (آخر 7 أيام)', 'ac-inventory-system'); ?></h3>
        <table class="ac-is-table">
            <thead>
                <tr>
                    <th><?php _e('التاريخ', 'ac-inventory-system'); ?></th>
                    <th><?php _e('العدد', 'ac-inventory-system'); ?></th>
                    <th><?php _e('الإجمالي', 'ac-inventory-system'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($daily as $d): ?>
                    <tr>
                        <td><?php echo $d->date; ?></td>
                        <td><?php echo $d->count; ?></td>
                        <td><strong><?php echo number_format($d->total, 2); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="ac-is-report-section">
        <h3><?php _e('ملخص المبيعات الشهرية', 'ac-inventory-system'); ?></h3>
        <table class="ac-is-table">
            <thead>
                <tr>
                    <th><?php _e('الشهر', 'ac-inventory-system'); ?></th>
                    <th><?php _e('العدد', 'ac-inventory-system'); ?></th>
                    <th><?php _e('الإجمالي', 'ac-inventory-system'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($monthly as $m): ?>
                    <tr>
                        <td><?php echo $m->month; ?></td>
                        <td><?php echo $m->count; ?></td>
                        <td><strong><?php echo number_format($m->total, 2); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="ac-is-report-section" style="margin-top:40px;">
    <h3><?php _e('تنبيهات انخفاض المخزون (أقل من 10 قطع)', 'ac-inventory-system'); ?></h3>
    <table class="ac-is-table">
        <thead>
            <tr>
                <th><?php _e('المنتج', 'ac-inventory-system'); ?></th>
                <th><?php _e('التصنيف', 'ac-inventory-system'); ?></th>
                <th><?php _e('الكمية المتبقية', 'ac-inventory-system'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($low_stock as $ls):
                $category_name = ($ls->category == 'ac') ? __('مكيفات', 'ac-inventory-system') : (($ls->category == 'cooling') ? __('تبريد', 'ac-inventory-system') : __('فلاتر', 'ac-inventory-system'));
            ?>
                <tr>
                    <td><strong><?php echo esc_html($ls->name); ?></strong></td>
                    <td><span class="ac-is-capsule capsule-primary"><?php echo $category_name; ?></span></td>
                    <td><span class="ac-is-capsule capsule-danger"><?php echo $ls->stock_quantity; ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if(empty($low_stock)): ?>
                <tr><td colspan="3" style="text-align:center;"><?php _e('لا توجد منتجات منخفضة المخزون حالياً.', 'ac-inventory-system'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="ac-is-export-actions" style="margin-top:40px; display:flex; gap:15px; justify-content:center;">
        <?php if(AC_IS_Auth::is_admin()): ?>
            <a href="<?php echo add_query_arg(array('ac_export' => 'sales', 'ac_nonce' => wp_create_nonce('ac_is_export'))); ?>" class="ac-is-btn" style="background:#28a745;"><?php _e('تصدير التقرير (CSV)', 'ac-inventory-system'); ?></a>
        <?php endif; ?>
    <button onclick="window.print();" class="ac-is-btn" style="background:#6c757d;"><?php _e('طباعة التقرير', 'ac-inventory-system'); ?></button>
</div>
