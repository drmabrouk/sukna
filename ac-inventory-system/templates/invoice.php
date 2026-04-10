<?php
$invoice_id = isset($_GET['invoice_id']) ? intval($_GET['invoice_id']) : 0;
$invoice = $invoice_id ? AC_IS_Sales::get_invoice($invoice_id) : null;
$items = $invoice_id ? AC_IS_Sales::get_invoice_items($invoice_id) : array();

global $wpdb;
$settings = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ac_is_settings", OBJECT_K );
$company_name = $settings['company_name']->setting_value ?? get_bloginfo('name');
$company_phone = $settings['company_phone']->setting_value ?? '';
$company_email = $settings['company_email']->setting_value ?? '';
$company_address = $settings['company_address']->setting_value ?? '';
$company_logo = $settings['company_logo']->setting_value ?? '';
?>

<div class="ac-is-header-flex no-print" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2 style="font-weight:800;"><?php _e('عرض ومعاينة الفاتورة', 'ac-inventory-system'); ?></h2>
    <div style="display:flex; gap:12px;">
        <button onclick="window.print();" class="ac-is-btn" style="background:#1e293b;"><span class="dashicons dashicons-printer" style="margin-left:8px;"></span><?php _e('طباعة فورية', 'ac-inventory-system'); ?></button>
        <a href="<?php echo add_query_arg('ac_view', 'sales'); ?>" class="ac-is-btn" style="background:#059669;"><?php _e('فاتورة جديدة', 'ac-inventory-system'); ?></a>
    </div>
</div>

<?php if($invoice): ?>
<div class="invoice-container" style="background:#fff; font-family: 'Cairo', sans-serif;">
    <div class="invoice-header" style="border-bottom: 4px solid var(--ac-primary); padding-bottom: 20px; margin-bottom: 30px;">
        <div style="display:flex; justify-content: space-between; align-items: center;">
            <div style="display:flex; align-items:center; gap:20px;">
                <?php if($company_logo): ?>
                    <img src="<?php echo esc_url($company_logo); ?>" style="max-height:100px; width:auto;">
                <?php endif; ?>
                <div>
                    <h1 style="margin:0; font-size:1.8rem; font-weight:900; color:var(--ac-sidebar-bg);"><?php echo esc_html($company_name); ?></h1>
                    <small style="color:#64748b;"><?php echo esc_html($company_address); ?></small>
                </div>
            </div>
            <div style="text-align: left;">
                <h2 style="margin:0; color:var(--ac-primary); font-weight:800;"><?php _e('فاتورة مبيعات', 'ac-inventory-system'); ?></h2>
                <p style="margin:5px 0; font-weight:600;">#INV-<?php echo str_pad($invoice->id, 8, '0', STR_PAD_LEFT); ?></p>
                <p style="margin:0; font-size:0.9rem; color:#64748b;"><?php echo date('H:i | Y/m/d', strtotime($invoice->invoice_date)); ?></p>
                <?php if($company_phone): ?><p style="margin:5px 0 0 0; font-weight:600;"><?php echo esc_html($company_phone); ?></p><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="customer-info" style="margin-bottom:30px; padding:15px; background:var(--ac-secondary); border:1px solid var(--ac-border); border-radius: 8px;">
        <h3 style="margin:0 0 10px 0; font-size:1rem; color:#334155;"><?php _e('بيانات العميل', 'ac-inventory-system'); ?></h3>
        <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:15px;">
            <div><strong><?php _e('الاسم:', 'ac-inventory-system'); ?></strong> <?php echo esc_html($invoice->customer_name); ?></div>
            <div><strong><?php _e('الهاتف:', 'ac-inventory-system'); ?></strong> <?php echo esc_html($invoice->customer_phone); ?></div>
            <div><strong><?php _e('العنوان:', 'ac-inventory-system'); ?></strong> <?php echo esc_html($invoice->customer_address); ?></div>
        </div>
    </div>

    <div class="invoice-details" style="margin-bottom:40px;">
        <table class="invoice-table" style="width:100%; border-collapse: collapse; border: 1px solid var(--ac-border);">
            <thead>
                <tr style="background:var(--ac-secondary); border-bottom: 2px solid var(--ac-border);">
                    <th style="padding:12px; text-align:right; font-size:0.85rem;"><?php _e('المنتج / البيان', 'ac-inventory-system'); ?></th>
                    <th style="padding:12px; text-align:right; font-size:0.85rem;"><?php _e('السيريال', 'ac-inventory-system'); ?></th>
                    <th style="padding:12px; text-align:center; font-size:0.85rem;"><?php _e('الكمية', 'ac-inventory-system'); ?></th>
                    <th style="padding:12px; text-align:left; font-size:0.85rem;"><?php _e('الإجمالي', 'ac-inventory-system'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $item): ?>
                    <tr style="border-bottom: 1px solid var(--ac-border);">
                        <td style="padding:12px; display:flex; align-items:center; gap:10px;">
                            <?php if($item->brand_logo): ?>
                                <img src="<?php echo esc_url($item->brand_logo); ?>" style="height:20px; width:auto; opacity:0.8;">
                            <?php endif; ?>
                            <strong style="font-size:0.9rem;"><?php echo esc_html($item->product_name); ?></strong>
                        </td>
                        <td style="padding:12px;"><small style="color:#64748b;"><?php echo esc_html($item->serial_number ?: $item->product_barcode); ?></small></td>
                        <td style="padding:12px; text-align:center;"><?php echo $item->quantity; ?></td>
                        <td style="padding:12px; text-align:left; font-weight:700; color:var(--ac-primary);"><?php echo number_format($item->total_price, 2); ?> EGP</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="invoice-summary" style="display:flex; justify-content: space-between; align-items: flex-end; padding:20px; background:var(--ac-secondary); border-radius: 8px;">
        <div class="invoice-qr">
            <svg id="invoice-barcode-svg"></svg>
            <script>
                jQuery(document).ready(function($) {
                    JsBarcode("#invoice-barcode-svg", "INV-<?php echo str_pad($invoice->id, 8, '0', STR_PAD_LEFT); ?>", {
                        format: "CODE128", width: 1.5, height: 40, displayValue: true, fontSize: 10
                    });
                });
            </script>
        </div>
        <div style="text-align: left;">
            <p style="margin:0; color:#64748b; font-weight:600;"><?php _e('المبلغ الإجمالي المستحق', 'ac-inventory-system'); ?></p>
            <h2 style="margin:5px 0 0 0; font-size:2.2rem; font-weight:900; color:#0f172a;"><?php echo number_format($invoice->total_amount, 2); ?> <small style="font-size:1rem; color:#64748b;">EGP</small></h2>
        </div>
    </div>

    <?php if($invoice->warranty_years > 0): ?>
        <div class="warranty-section" style="margin-top:30px; padding:15px; border: 1px solid var(--ac-border); border-right: 5px solid var(--ac-primary); border-radius: 8px;">
            <h4 style="margin:0 0 8px 0; color:var(--ac-primary);"><?php _e('بيان الضمان المحدود', 'ac-inventory-system'); ?></h4>
            <p style="font-size:0.85rem; line-height:1.6; margin:0;">
                <?php printf(__('هذا المنتج مشمول بضمان محدود لمدة (%d) سنوات من تاريخ الشراء، يغطي عيوب الصناعة فقط. لا يشمل الضمان سوء الاستخدام، التلف الناتج عن الكسر، أو الصيانة خارج مراكزنا المعتمدة. يلزم إبراز أصل الفاتورة عند المطالبة بالضمان.', 'ac-inventory-system'), $invoice->warranty_years); ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="invoice-footer" style="margin-top:50px; text-align: center; border-top: 2px dashed var(--ac-border); padding-top: 30px;">
        <p style="margin-top:15px; font-size:0.8rem; color:#94a3b8;">
            <?php echo esc_html($company_name); ?> | <?php echo esc_html($company_email); ?> | <?php echo esc_html($company_phone); ?>
            <br><?php _e('تم إصدار هذه الفاتورة إلكترونياً - شكراً لتعاملكم معنا', 'ac-inventory-system'); ?>
        </p>
    </div>
</div>
<?php else: ?>
    <p><?php _e('عذراً، لم يتم العثور على بيانات هذه الفاتورة.', 'ac-inventory-system'); ?></p>
<?php endif; ?>
