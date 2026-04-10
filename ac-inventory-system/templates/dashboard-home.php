<?php
global $wpdb;
$table_products = $wpdb->prefix . 'ac_is_products';
$table_sales    = $wpdb->prefix . 'ac_is_sales';
$table_invoices = $wpdb->prefix . 'ac_is_invoices';

// Statistics
$total_products = $wpdb->get_var("SELECT COUNT(*) FROM $table_products");
$today_sales_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_invoices WHERE DATE(invoice_date) = CURDATE()");
$today_sales_total = $wpdb->get_var("SELECT SUM(total_amount) FROM $table_invoices WHERE DATE(invoice_date) = CURDATE()") ?: 0;

// Daily Profit Margin
$today_cost = $wpdb->get_var("
    SELECT SUM(p.purchase_cost * s.quantity)
    FROM $table_sales s
    JOIN $table_products p ON s.product_id = p.id
    WHERE DATE(s.sale_date) = CURDATE()
") ?: 0;
$today_profit = $today_sales_total - $today_cost;

$low_stock_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_products WHERE stock_quantity < 10");
$total_stock_qty = $wpdb->get_var("SELECT SUM(stock_quantity) FROM $table_products") ?: 0;

// Financial Metrics (Second Row)
$total_inventory_value = $wpdb->get_var("SELECT SUM(purchase_cost * stock_quantity) FROM $table_products") ?: 0;
$total_sales_value = $wpdb->get_var("SELECT SUM(total_amount) FROM $table_invoices") ?: 0;
$total_cost_sold = $wpdb->get_var("
    SELECT SUM(p.purchase_cost * s.quantity)
    FROM $table_sales s
    JOIN $table_products p ON s.product_id = p.id
") ?: 0;
$total_profit_margin = $total_sales_value - $total_cost_sold;

// Monthly Sales data for chart (Last 6 months)
$chart_data = $wpdb->get_results("
    SELECT DATE_FORMAT(invoice_date, '%Y-%m') as month, SUM(total_amount) as total
    FROM $table_invoices
    WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
    ORDER BY month ASC
");

// Recent Activity (Fixed to 5)
$recent_sales = $wpdb->get_results("
    SELECT i.*, c.name as customer_name
    FROM $table_invoices i
    LEFT JOIN {$wpdb->prefix}ac_is_customers c ON i.customer_id = c.id
    ORDER BY i.invoice_date DESC
    LIMIT 5
");

// Daily Sales for Current Month
$current_month_days = $wpdb->get_results("
    SELECT DATE(invoice_date) as sale_date, SUM(total_amount) as daily_total
    FROM $table_invoices
    WHERE MONTH(invoice_date) = MONTH(CURDATE()) AND YEAR(invoice_date) = YEAR(CURDATE())
    GROUP BY DATE(invoice_date)
    ORDER BY sale_date ASC
");
?>

<div class="ac-is-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:var(--ac-sidebar-bg);"><?php _e('لوحة المعلومات', 'ac-inventory-system'); ?></h2>
    <div style="display:flex; gap:10px;">
        <a href="<?php echo add_query_arg('ac_view', 'add-product'); ?>" class="ac-is-btn"><span class="dashicons dashicons-plus" style="margin-left:5px;"></span><?php _e('إضافة منتج', 'ac-inventory-system'); ?></a>
        <a href="<?php echo add_query_arg('ac_view', 'sales'); ?>" class="ac-is-btn" style="background:#059669;"><span class="dashicons dashicons-cart" style="margin-left:5px;"></span><?php _e('بيع جديد', 'ac-inventory-system'); ?></a>
    </div>
</div>

<!-- Horizontal Professional Metrics -->
<div class="ac-is-metrics-row">
    <div class="ac-is-metric-card" style="border-right-color: #059669;">
        <div class="ac-is-metric-icon" style="background: #ecfdf5; color: #059669;">
            <span class="dashicons dashicons-cart"></span>
        </div>
        <div class="ac-is-metric-content">
            <div class="ac-is-metric-title"><?php _e('مبيعات اليوم', 'ac-inventory-system'); ?></div>
            <div class="ac-is-metric-value"><?php echo number_format($today_sales_total, 2); ?></div>
            <div style="font-size: 0.75rem; color: #059669; font-weight: 600;"><?php echo $today_sales_count; ?> <?php _e('عملية', 'ac-inventory-system'); ?></div>
        </div>
    </div>

    <div class="ac-is-metric-card" style="border-right-color: #805ad5;">
        <div class="ac-is-metric-icon" style="background: #f3e8ff; color: #805ad5;">
            <span class="dashicons dashicons-money-alt"></span>
        </div>
        <div class="ac-is-metric-content">
            <div class="ac-is-metric-title"><?php _e('ربح اليوم', 'ac-inventory-system'); ?></div>
            <div class="ac-is-metric-value"><?php echo number_format($today_profit, 2); ?></div>
            <div style="font-size: 0.75rem; color: #805ad5;"><?php _e('هامش الربح', 'ac-inventory-system'); ?></div>
        </div>
    </div>

    <div class="ac-is-metric-card" style="border-right-color: #2563eb;">
        <div class="ac-is-metric-icon" style="background: #eff6ff; color: #2563eb;">
            <span class="dashicons dashicons-database"></span>
        </div>
        <div class="ac-is-metric-content">
            <div class="ac-is-metric-title"><?php _e('إجمالي المخزون', 'ac-inventory-system'); ?></div>
            <div class="ac-is-metric-value"><?php echo number_format($total_stock_qty); ?></div>
            <div style="font-size: 0.75rem; color: #64748b; font-weight: 600;"><?php echo $total_products; ?> <?php _e('صنف مسجل', 'ac-inventory-system'); ?></div>
        </div>
    </div>
</div>

<div class="ac-is-metrics-row">
    <div class="ac-is-metric-card" style="border-right-color: #4b5563;">
        <div class="ac-is-metric-icon" style="background: #f3f4f6; color: #4b5563;">
            <span class="dashicons dashicons-products"></span>
        </div>
        <div class="ac-is-metric-content">
            <div class="ac-is-metric-title"><?php _e('قيمة المخزون (شراء)', 'ac-inventory-system'); ?></div>
            <div class="ac-is-metric-value"><?php echo number_format($total_inventory_value, 2); ?></div>
            <div style="font-size: 0.75rem; color: #64748b;"><?php _e('رأس المال بالمخزن', 'ac-inventory-system'); ?></div>
        </div>
    </div>

    <div class="ac-is-metric-card" style="border-right-color: #1e293b;">
        <div class="ac-is-metric-icon" style="background: #f1f5f9; color: #1e293b;">
            <span class="dashicons dashicons-chart-bar"></span>
        </div>
        <div class="ac-is-metric-content">
            <div class="ac-is-metric-title"><?php _e('إجمالي المبيعات', 'ac-inventory-system'); ?></div>
            <div class="ac-is-metric-value"><?php echo number_format($total_sales_value, 2); ?></div>
            <div style="font-size: 0.75rem; color: #64748b;"><?php _e('قيمة المبيعات الكلية', 'ac-inventory-system'); ?></div>
        </div>
    </div>

    <div class="ac-is-metric-card" style="border-right-color: #059669;">
        <div class="ac-is-metric-icon" style="background: #ecfdf5; color: #059669;">
            <span class="dashicons dashicons-plus-alt"></span>
        </div>
        <div class="ac-is-metric-content">
            <div class="ac-is-metric-title"><?php _e('صافي الربح الكلي', 'ac-inventory-system'); ?></div>
            <div class="ac-is-metric-value"><?php echo number_format($total_profit_margin, 2); ?></div>
            <div style="font-size: 0.75rem; color: #059669; font-weight: 600;"><?php _e('إجمالي الأرباح', 'ac-inventory-system'); ?></div>
        </div>
    </div>
</div>

<div class="ac-is-grid" style="grid-template-columns: 1.8fr 1fr; gap:20px; align-items: stretch;">
    <!-- Monthly Sales Detailed Table/Graph -->
    <div class="ac-is-card" style="display:flex; flex-direction:column;">
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom:15px;">
            <h3 style="margin:0;"><?php _e('تحليل مبيعات الشهر الحالي', 'ac-inventory-system'); ?></h3>
            <span class="ac-is-capsule capsule-success" style="font-size:0.9rem; padding:5px 12px;"><?php _e('الإجمالي:', 'ac-inventory-system'); ?> <?php echo number_format($total_sales_value, 2); ?></span>
        </div>
        <div style="flex-grow:1; overflow-y:auto; max-height:400px;">
            <table class="ac-is-table" style="font-size:0.8rem;">
                <thead>
                    <tr>
                        <th><?php _e('التاريخ', 'ac-inventory-system'); ?></th>
                        <th><?php _e('قيمة المبيعات', 'ac-inventory-system'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($current_month_days as $day): ?>
                        <tr>
                            <td><?php echo date('d / m / Y', strtotime($day->sale_date)); ?></td>
                            <td><strong><?php echo number_format($day->daily_total, 2); ?> EGP</strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="ac-is-card" style="display:flex; flex-direction:column;">
        <h3><?php _e('آخر عمليات البيع', 'ac-inventory-system'); ?></h3>
        <div class="ac-is-recent-list" style="flex-grow:1;">
            <?php foreach($recent_sales as $rs): ?>
                <div style="display:flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f1f5f9;">
                    <div style="font-size:0.8rem;">
                        <strong>#<?php echo $rs->id; ?> - <?php echo esc_html($rs->customer_name ?: __('عميل سريع', 'ac-inventory-system')); ?></strong><br>
                        <small style="color:#94a3b8; font-size:0.7rem;"><?php echo date('H:i - d/m', strtotime($rs->invoice_date)); ?></small>
                    </div>
                    <div style="text-align: left;">
                        <span style="font-weight:700; color:var(--ac-primary); font-size:0.85rem;"><?php echo number_format($rs->total_amount, 2); ?></span><br>
                        <a href="<?php echo add_query_arg(array('ac_view' => 'invoice', 'invoice_id' => $rs->id)); ?>" style="font-size:0.7rem; text-decoration:none; color:#64748b;"><?php _e('عرض', 'ac-inventory-system'); ?></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('ac-is-sales-chart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($d){ return $d->month; }, $chart_data)); ?>,
                datasets: [{
                    label: '<?php _e('المبيعات', 'ac-inventory-system'); ?>',
                    data: <?php echo json_encode(array_map(function($d){ return (float)$d->total; }, $chart_data)); ?>,
                    backgroundColor: '#2563eb',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 10 } } },
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } }
                }
            }
        });
    }
});
</script>
