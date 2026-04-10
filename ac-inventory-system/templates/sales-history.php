<?php
global $wpdb;
$table_sales     = $wpdb->prefix . 'ac_is_sales';
$table_invoices  = $wpdb->prefix . 'ac_is_invoices';
$table_products  = $wpdb->prefix . 'ac_is_products';
$table_customers = $wpdb->prefix . 'ac_is_customers';
$table_staff     = $wpdb->prefix . 'ac_is_staff';

// Filters
$where = "1=1";
if ( ! empty( $_GET['sale_search'] ) ) {
    $search = '%' . $wpdb->esc_like( $_GET['sale_search'] ) . '%';
    $where .= $wpdb->prepare( " AND (c.name LIKE %s OR c.phone LIKE %s OR s.serial_number LIKE %s OR p.name LIKE %s OR p.barcode LIKE %s OR i.id = %s)",
        $search, $search, $search, $search, $search, $_GET['sale_search'] );
}

if ( ! empty( $_GET['date_from'] ) ) {
    $where .= $wpdb->prepare( " AND i.invoice_date >= %s", $_GET['date_from'] . ' 00:00:00' );
}
if ( ! empty( $_GET['date_to'] ) ) {
    $where .= $wpdb->prepare( " AND i.invoice_date <= %s", $_GET['date_to'] . ' 23:59:59' );
}
if ( ! empty( $_GET['operator_id'] ) ) {
    $where .= $wpdb->prepare( " AND i.operator_id = %s", $_GET['operator_id'] );
}

$sales = $wpdb->get_results("
    SELECT s.*, i.invoice_date, i.total_amount as invoice_total, c.name as customer_name, c.phone as customer_phone, p.name as product_name, i.operator_id
    FROM $table_sales s
    JOIN $table_invoices i ON s.invoice_id = i.id
    JOIN $table_products p ON s.product_id = p.id
    LEFT JOIN $table_customers c ON i.customer_id = c.id
    WHERE $where
    ORDER BY i.invoice_date DESC
");

$staff_list = $wpdb->get_results( "SELECT id, name FROM $table_staff" );
?>

<div class="ac-is-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap:15px;">
    <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:var(--ac-sidebar-bg);"><?php _e('سجل المبيعات والتفاصيل', 'ac-inventory-system'); ?></h2>

    <div class="ac-is-compact-search" style="flex-grow: 1; max-width: 500px; display:flex; gap:10px;">
        <div style="position: relative; flex:1;">
            <span class="dashicons dashicons-search" style="position: absolute; right: 10px; top: 10px; color: #94a3b8;"></span>
            <input type="text" id="ac-is-live-sales-search" placeholder="<?php _e('بحث سريع: فاتورة، عميل، منتج...', 'ac-inventory-system'); ?>" style="width: 100%; padding: 8px 35px 8px 12px; border-radius: 20px; border: 1px solid var(--ac-border); font-size: 0.85rem;">
        </div>
        <div class="ac-is-dropdown" style="position:relative;">
            <button class="ac-is-btn" style="background:#64748b; height:38px;" onclick="jQuery('#export-dropdown').toggle()"><?php _e('تصدير التقارير', 'ac-inventory-system'); ?> <span class="dashicons dashicons-arrow-down-alt2"></span></button>
            <div id="export-dropdown" style="display:none; position:absolute; left:0; top:45px; background:#fff; border:1px solid #ddd; border-radius:8px; width:200px; z-index:1000; box-shadow:0 10px 15px rgba(0,0,0,0.1);">
                <a href="#" class="export-link" data-type="sales-pdf" style="display:block; padding:10px 15px; text-decoration:none; color:#333; border-bottom:1px solid #eee;"><?php _e('تصدير المبيعات (PDF)', 'ac-inventory-system'); ?></a>
                <a href="#" class="export-link" data-type="details-pdf" style="display:block; padding:10px 15px; text-decoration:none; color:#333; border-bottom:1px solid #eee;"><?php _e('تصدير التفاصيل (PDF)', 'ac-inventory-system'); ?></a>
                <a href="<?php echo add_query_arg(array('ac_export' => 'sales', 'ac_nonce' => wp_create_nonce('ac_is_export'))); ?>" style="display:block; padding:10px 15px; text-decoration:none; color:#333;"><?php _e('تصدير CSV', 'ac-inventory-system'); ?></a>
            </div>
        </div>
    </div>
</div>

<div class="ac-is-search-filters" style="background:#fff; padding:15px; border:1px solid var(--ac-border); margin-bottom:25px; border-radius: 8px;">
    <form method="get" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
        <input type="hidden" name="ac_view" value="sales-history">

        <div class="ac-is-form-group" style="margin:0; flex:1; min-width: 150px;">
            <label style="font-size:0.75rem; margin-bottom:4px;"><?php _e('من تاريخ', 'ac-inventory-system'); ?></label>
            <input type="date" name="date_from" value="<?php echo esc_attr($_GET['date_from'] ?? ''); ?>" style="padding:6px 10px; font-size:0.8rem;">
        </div>

        <div class="ac-is-form-group" style="margin:0; flex:1; min-width: 150px;">
            <label style="font-size:0.75rem; margin-bottom:4px;"><?php _e('إلى تاريخ', 'ac-inventory-system'); ?></label>
            <input type="date" name="date_to" value="<?php echo esc_attr($_GET['date_to'] ?? ''); ?>" style="padding:6px 10px; font-size:0.8rem;">
        </div>

        <div class="ac-is-form-group" style="margin:0; flex:1; min-width: 150px;">
            <label style="font-size:0.75rem; margin-bottom:4px;"><?php _e('الموظف', 'ac-inventory-system'); ?></label>
            <select name="operator_id" style="padding:6px 10px; font-size:0.8rem;">
                <option value=""><?php _e('كل الموظفين', 'ac-inventory-system'); ?></option>
                <?php foreach($staff_list as $s): ?>
                    <option value="<?php echo $s->id; ?>" <?php selected($_GET['operator_id'] ?? '', $s->id); ?>><?php echo esc_html($s->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display:flex; gap:8px;">
            <button type="submit" class="ac-is-btn" style="height:34px; padding:0 15px; font-size:0.8rem;"><?php _e('فلترة', 'ac-inventory-system'); ?></button>
            <a href="?ac_view=sales-history" class="ac-is-btn" style="background:#64748b; height:34px; padding:0 15px; font-size:0.8rem;"><?php _e('إعادة', 'ac-inventory-system'); ?></a>
        </div>
    </form>
</div>

<div style="background:#fff; border:1px solid var(--ac-border); overflow:hidden; border-radius: 8px;">
    <table class="ac-is-table" id="ac-is-sales-table">
        <thead>
            <tr>
                <th><?php _e('رقم / تاريخ', 'ac-inventory-system'); ?></th>
                <th><?php _e('العميل', 'ac-inventory-system'); ?></th>
                <th><?php _e('المنتج والسيريال', 'ac-inventory-system'); ?></th>
                <th><?php _e('المبلغ', 'ac-inventory-system'); ?></th>
                <th><?php _e('الموظف المسؤول', 'ac-inventory-system'); ?></th>
                <th><?php _e('إجراءات', 'ac-inventory-system'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( $sales ) : foreach($sales as $s):
                $operator_name = __('غير معروف', 'ac-inventory-system');
                if ( strpos( (string)$s->operator_id, 'wp_' ) === 0 ) {
                    $wp_id = str_replace('wp_', '', (string)$s->operator_id);
                    $user = get_userdata($wp_id);
                    if ($user) $operator_name = $user->display_name;
                } else {
                    $staff = $wpdb->get_row($wpdb->prepare("SELECT name FROM $table_staff WHERE id = %s", $s->operator_id));
                    if ($staff) $operator_name = $staff->name;
                }
            ?>
                <tr>
                    <td>
                        <strong>#<?php echo $s->invoice_id; ?></strong><br>
                        <small style="color:#64748b;"><?php echo date('Y-m-d H:i', strtotime($s->invoice_date)); ?></small>
                    </td>
                    <td>
                        <strong><?php echo esc_html($s->customer_name ?: __('عميل سريع', 'ac-inventory-system')); ?></strong><br>
                        <small><?php echo esc_html($s->customer_phone ?: '-'); ?></small>
                    </td>
                    <td>
                        <strong><?php echo esc_html($s->product_name); ?></strong><br>
                        <small>SN: <?php echo esc_html($s->serial_number ?: '-'); ?></small>
                    </td>
                    <td><span style="font-weight:700; color:var(--ac-primary);"><?php echo number_format($s->total_price, 2); ?> EGP</span></td>
                    <td><span class="ac-is-capsule capsule-info"><?php echo esc_html($operator_name); ?></span></td>
                    <td>
                        <div style="display:flex; gap:5px;">
                            <a href="<?php echo add_query_arg(array('ac_view' => 'invoice', 'invoice_id' => $s->invoice_id)); ?>" class="ac-is-btn" style="padding:4px 8px; font-size:0.75rem; background:#64748b;" title="<?php _e('عرض', 'ac-inventory-system'); ?>"><span class="dashicons dashicons-visibility"></span></a>
                            <button class="ac-is-btn ac-is-download-invoice-pdf" data-id="<?php echo $s->invoice_id; ?>" style="padding:4px 8px; font-size:0.75rem; background:#059669;" title="<?php _e('تحميل PDF', 'ac-inventory-system'); ?>"><span class="dashicons dashicons-pdf"></span></button>
                            <?php if ( AC_IS_Auth::can_delete_records() ) : ?>
                                <button class="ac-is-btn ac-is-delete-invoice" data-id="<?php echo $s->invoice_id; ?>" style="padding:4px 8px; font-size:0.75rem; background:#ef4444;" title="<?php _e('حذف', 'ac-inventory-system'); ?>"><span class="dashicons dashicons-trash"></span></button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="6" style="text-align:center; padding:40px;"><?php _e('لم يتم العثور على نتائج', 'ac-inventory-system'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($) {
    $('.ac-is-download-invoice-pdf').on('click', function() {
        const id = $(this).data('id');
        const url = '?ac_view=invoice&invoice_id=' + id + '&print_mode=1';

        // Use an iframe to load and then export
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = url;
        document.body.appendChild(iframe);

        iframe.onload = function() {
            const element = iframe.contentWindow.document.querySelector('.invoice-container');
            if (element) {
                html2pdf().from(element).save('invoice-' + id + '.pdf').then(() => {
                    document.body.removeChild(iframe);
                });
            }
        };
    });

    $('.export-link').on('click', function(e) {
        e.preventDefault();
        const type = $(this).data('type');
        const element = document.getElementById('ac-is-sales-table').cloneNode(true);
        $(element).find('th:last-child, td:last-child').remove();

        const opt = {
            margin: 10,
            filename: 'sales-report-' + new Date().toISOString().slice(0,10) + '.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
        };
        html2pdf().set(opt).from(element).save().then(() => $('#export-dropdown').hide());
    });

    $('.ac-is-delete-invoice').on('click', function() {
        if (!confirm('<?php _e('هل أنت متأكد من حذف هذه الفاتورة؟ سيتم استرجاع المنتجات للمخزون وحذف السجل نهائياً.', 'ac-inventory-system'); ?>')) return;
        const id = $(this).data('id');
        $.post(ac_is_ajax.ajax_url, {
            action: 'ac_is_delete_invoice',
            invoice_id: id,
            nonce: ac_is_ajax.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            }
        });
    });
});
</script>
