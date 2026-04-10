<?php
$customers = AC_IS_Customers::get_all_customers();
$can_manage = AC_IS_Auth::is_manager();
?>

<div class="ac-is-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:var(--ac-sidebar-bg);"><?php _e('إدارة قاعدة بيانات العملاء', 'ac-inventory-system'); ?></h2>
    <div style="display:flex; gap:10px;">
        <button id="ac-is-export-customers-pdf" class="ac-is-btn" style="background:#dc2626;"><span class="dashicons dashicons-pdf" style="margin-left:8px;"></span><?php _e('تصدير العملاء PDF', 'ac-inventory-system'); ?></button>
        <?php if($can_manage): ?>
            <button id="ac-is-add-customer-btn" class="ac-is-btn" style="background:#1e293b;">
                <span class="dashicons dashicons-plus-alt" style="margin-left:8px;"></span><?php _e('إضافة عميل', 'ac-inventory-system'); ?>
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="ac-is-search-filters" style="margin-bottom:25px; padding:20px; background:#fff; border:1px solid var(--ac-border); display:flex; gap:15px; flex-wrap: wrap; align-items: center;">
    <input type="text" id="ac-is-customer-search" placeholder="<?php _e('بحث شامل بالاسم، الهاتف، أو البريد...', 'ac-inventory-system'); ?>" style="flex: 2; height:45px; font-size:1.1rem; border-radius:6px; padding:0 20px;">

    <select id="ac-is-customer-sort" style="flex: 1; height:45px; border-radius:6px;">
        <option value="name"><?php _e('ترتيب حسب: الاسم', 'ac-inventory-system'); ?></option>
        <option value="revenue"><?php _e('ترتيب حسب: إجمالي المشتريات', 'ac-inventory-system'); ?></option>
        <option value="profit"><?php _e('ترتيب حسب: صافي الربح', 'ac-inventory-system'); ?></option>
        <option value="frequency"><?php _e('ترتيب حسب: عدد العمليات', 'ac-inventory-system'); ?></option>
    </select>
</div>

<!-- Customer Modal (Styled) -->
<div id="ac-is-customer-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.6); z-index:10001; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="ac-is-card" style="width:100%; max-width:550px; padding:40px; border-radius:8px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
        <h3 id="modal-title" style="font-size:1.4rem; margin-bottom:30px;"><?php _e('بيانات العميل', 'ac-inventory-system'); ?></h3>
        <form id="ac-is-customer-form">
            <input type="hidden" name="id" id="customer-id">
            <div class="ac-is-form-group">
                <input type="text" name="name" id="customer-name" placeholder="<?php _e('اسم العميل بالكامل', 'ac-inventory-system'); ?>" required>
            </div>
            <div class="ac-is-grid" style="grid-template-columns: 1fr 1fr; gap:15px;">
                <div class="ac-is-form-group">
                    <input type="text" name="phone" id="customer-phone" placeholder="<?php _e('رقم الهاتف الأساسي', 'ac-inventory-system'); ?>" required>
                </div>
                <div class="ac-is-form-group">
                    <input type="text" name="phone_secondary" id="customer-phone-secondary" placeholder="<?php _e('رقم الهاتف الثاني', 'ac-inventory-system'); ?>">
                </div>
            </div>
            <div class="ac-is-form-group">
                <input type="email" name="email" id="customer-email" placeholder="<?php _e('البريد الإلكتروني (اختياري)', 'ac-inventory-system'); ?>">
            </div>
            <div class="ac-is-form-group">
                <textarea name="address" id="customer-address" rows="2" placeholder="<?php _e('عنوان العميل المختار التوصيل إليه', 'ac-inventory-system'); ?>"></textarea>
            </div>
            <div style="display:flex; gap:15px; margin-top:30px;">
                <button type="submit" class="ac-is-btn" style="flex:1; height:50px; background:var(--ac-primary);"><?php _e('حفظ البيانات', 'ac-inventory-system'); ?></button>
                <button type="button" id="close-modal" class="ac-is-btn" style="flex:1; height:50px; background:#64748b;"><?php _e('إلغاء', 'ac-inventory-system'); ?></button>
            </div>
        </form>
    </div>
</div>

<div style="background:#fff; border:1px solid var(--ac-border); overflow:hidden;">
    <table class="ac-is-table" id="ac-is-customer-table">
        <thead>
            <tr>
                <th><?php _e('العميل', 'ac-inventory-system'); ?></th>
                <th><?php _e('بيانات التواصل', 'ac-inventory-system'); ?></th>
                <th><?php _e('رؤى العميل', 'ac-inventory-system'); ?></th>
                <th><?php _e('صافي الربح', 'ac-inventory-system'); ?></th>
                <th><?php _e('إجراءات', 'ac-inventory-system'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($customers as $c):
                $profit_class = ($c->net_profit > 0) ? 'capsule-success' : 'capsule-warning';
            ?>
                <tr data-customer='<?php echo json_encode($c); ?>'>
                    <td>
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div style="width:40px; height:40px; background:#f1f5f9; color:var(--ac-primary); border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800;">
                                <?php echo mb_substr($c->name, 0, 1); ?>
                            </div>
                            <strong><?php echo esc_html($c->name); ?></strong>
                        </div>
                    </td>
                    <td>
                        <small><?php echo esc_html($c->phone); ?></small>
                        <?php if($c->phone_secondary): ?><br><small style="color:#64748b;"><?php echo esc_html($c->phone_secondary); ?></small><?php endif; ?>
                    </td>
                    <td>
                        <span class="ac-is-capsule capsule-primary" style="margin-bottom:4px;">
                            <?php _e('إجمالي:', 'ac-inventory-system'); ?> <?php echo number_format($c->total_revenue ?: 0, 2); ?>
                        </span><br>
                        <span class="ac-is-capsule capsule-info">
                            <?php echo $c->total_invoices; ?> <?php _e('عملية', 'ac-inventory-system'); ?>
                        </span>
                    </td>
                    <td><span class="ac-is-capsule <?php echo $profit_class; ?>"><?php echo number_format($c->net_profit ?: 0, 2); ?> EGP</span></td>
                    <td style="text-align:left;">
                        <div style="display:flex; gap:5px; justify-content: flex-end;">
                            <a href="<?php echo add_query_arg(array('ac_view' => 'sales-history', 'sale_search' => $c->phone)); ?>" class="ac-is-btn" style="padding:4px 8px; font-size:0.75rem; background:#64748b;"><span class="dashicons dashicons-list-view"></span></a>
                            <?php if($can_manage): ?>
                                <button class="ac-is-btn ac-is-edit-customer" style="padding:4px 8px; font-size:0.75rem; background:#3b82f6;"><span class="dashicons dashicons-edit"></span></button>
                                <button class="ac-is-btn ac-is-delete-customer" style="padding:4px 8px; font-size:0.75rem; background:#ef4444;"><span class="dashicons dashicons-trash"></span></button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(empty($customers)) echo '<tr><td colspan="5" style="text-align:center; padding:40px;">'.__('لا يوجد عملاء مسجلين بعد', 'ac-inventory-system').'</td></tr>'; ?>
        </tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($) {
    const modal = $('#ac-is-customer-modal');

    $('#ac-is-add-customer-btn').on('click', function() {
        $('#ac-is-customer-form')[0].reset();
        $('#customer-id').val('');
        $('#modal-title').text('<?php _e('إضافة عميل جديد لقاعدة البيانات', 'ac-inventory-system'); ?>');
        modal.css('display', 'flex');
    });

    $(document).on('click', '.ac-is-edit-customer', function() {
        const c = $(this).closest('tr').data('customer');
        $('#customer-id').val(c.id);
        $('#customer-name').val(c.name);
        $('#customer-phone').val(c.phone);
        $('#customer-phone-secondary').val(c.phone_secondary);
        $('#customer-email').val(c.email);
        $('#customer-address').val(c.address);
        $('#modal-title').text('<?php _e('تعديل بيانات العميل الحالي', 'ac-inventory-system'); ?>');
        modal.css('display', 'flex');
    });

    $('#close-modal').on('click', function() { modal.hide(); });

    $('#ac-is-customer-form').on('submit', function(e) {
        e.preventDefault();
        $.post(ac_is_ajax.ajax_url, $(this).serialize() + '&action=ac_is_save_customer&nonce=' + ac_is_ajax.nonce, () => location.reload());
    });

    $(document).on('click', '.ac-is-delete-customer', function() {
        if (!confirm('حذف؟')) return;
        $.post(ac_is_ajax.ajax_url, { action: 'ac_is_delete_customer', id: $(this).closest('tr').data('customer').id, nonce: ac_is_ajax.nonce }, () => location.reload());
    });

    function filterAndSortCustomers() {
        const query = $('#ac-is-customer-search').val().toLowerCase();
        const sortBy = $('#ac-is-customer-sort').val();
        const rows = $('#ac-is-customer-table tbody tr').get();

        rows.forEach(row => {
            const data = $(row).data('customer');
            if(!data) return;
            const match = data.name.toLowerCase().includes(query) ||
                          data.phone.includes(query) ||
                          (data.phone_secondary && data.phone_secondary.includes(query)) ||
                          (data.email && data.email.toLowerCase().includes(query));
            $(row).toggle(match);
        });

        rows.sort((a, b) => {
            const valA = $(a).data('customer');
            const valB = $(b).data('customer');
            if (!valA || !valB) return 0;

            switch(sortBy) {
                case 'revenue': return parseFloat(valB.total_revenue || 0) - parseFloat(valA.total_revenue || 0);
                case 'profit': return parseFloat(valB.net_profit || 0) - parseFloat(valA.net_profit || 0);
                case 'frequency': return parseInt(valB.total_invoices || 0) - parseInt(valA.total_invoices || 0);
                default: return valA.name.localeCompare(valB.name, 'ar');
            }
        });

        $.each(rows, (index, row) => $('#ac-is-customer-table tbody').append(row));
    }

    $('#ac-is-customer-search').on('input', filterAndSortCustomers);
    $('#ac-is-customer-sort').on('change', filterAndSortCustomers);

    $('#ac-is-export-customers-pdf').on('click', function() {
        const element = document.getElementById('ac-is-customer-table').cloneNode(true);
        $(element).find('th:last-child, td:last-child').remove();

        const opt = {
            margin: 10,
            filename: 'customers-report-' + new Date().toISOString().slice(0,10) + '.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
        };
        html2pdf().set(opt).from(element).save();
    });
});
</script>
