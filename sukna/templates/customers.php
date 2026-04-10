<?php
$customers = Sukna_Customers::get_all_customers();
$can_manage = Sukna_Auth::is_manager();
?>

<div class="sukna-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:#1e293b;"><?php _e('إدارة قاعدة بيانات العملاء', 'sukna'); ?></h2>
    <div style="display:flex; gap:10px;">
        <button id="sukna-export-customers-pdf" class="sukna-btn" style="background:#dc2626;"><span class="dashicons dashicons-pdf" style="margin-left:8px;"></span><?php _e('تصدير العملاء PDF', 'sukna'); ?></button>
        <?php if($can_manage): ?>
            <button id="sukna-add-customer-btn" class="sukna-btn" style="background:#1e293b;">
                <span class="dashicons dashicons-plus-alt" style="margin-left:8px;"></span><?php _e('إضافة عميل', 'sukna'); ?>
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="sukna-search-filters" style="margin-bottom:25px; padding:20px; background:#fff; border:1px solid #e2e8f0; display:flex; gap:15px; flex-wrap: wrap; align-items: center;">
    <input type="text" id="sukna-customer-search" placeholder="<?php _e('بحث شامل بالاسم، الهاتف، أو البريد...', 'sukna'); ?>" style="flex: 2; height:45px; font-size:1.1rem; border-radius:6px; padding:0 20px;">

    <select id="sukna-customer-sort" style="flex: 1; height:45px; border-radius:6px;">
        <option value="name"><?php _e('ترتيب حسب: الاسم', 'sukna'); ?></option>
    </select>
</div>

<!-- Customer Modal (Styled) -->
<div id="sukna-customer-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.6); z-index:10001; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="sukna-card" style="width:100%; max-width:550px; padding:40px; border-radius:8px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
        <h3 id="modal-title" style="font-size:1.4rem; margin-bottom:30px;"><?php _e('بيانات العميل', 'sukna'); ?></h3>
        <form id="sukna-customer-form">
            <input type="hidden" name="id" id="customer-id">
            <div class="sukna-form-group">
                <input type="text" name="name" id="customer-name" placeholder="<?php _e('اسم العميل بالكامل', 'sukna'); ?>" required>
            </div>
            <div class="sukna-grid" style="grid-template-columns: 1fr 1fr; gap:15px;">
                <div class="sukna-form-group">
                    <input type="text" name="phone" id="customer-phone" placeholder="<?php _e('رقم الهاتف الأساسي', 'sukna'); ?>" required>
                </div>
                <div class="sukna-form-group">
                    <input type="text" name="phone_secondary" id="customer-phone-secondary" placeholder="<?php _e('رقم الهاتف الثاني', 'sukna'); ?>">
                </div>
            </div>
            <div class="sukna-form-group">
                <input type="email" name="email" id="customer-email" placeholder="<?php _e('البريد الإلكتروني (اختياري)', 'sukna'); ?>">
            </div>
            <div class="sukna-form-group">
                <textarea name="address" id="customer-address" rows="2" placeholder="<?php _e('عنوان العميل', 'sukna'); ?>"></textarea>
            </div>
            <div style="display:flex; gap:15px; margin-top:30px;">
                <button type="submit" class="sukna-btn" style="flex:1; height:50px; background:#2563eb;"><?php _e('حفظ البيانات', 'sukna'); ?></button>
                <button type="button" id="close-modal" class="sukna-btn" style="flex:1; height:50px; background:#64748b;"><?php _e('إلغاء', 'sukna'); ?></button>
            </div>
        </form>
    </div>
</div>

<div style="background:#fff; border:1px solid #e2e8f0; overflow:hidden;">
    <table class="sukna-table" id="sukna-customer-table">
        <thead>
            <tr>
                <th><?php _e('العميل', 'sukna'); ?></th>
                <th><?php _e('بيانات التواصل', 'sukna'); ?></th>
                <th><?php _e('العنوان', 'sukna'); ?></th>
                <th><?php _e('إجراءات', 'sukna'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($customers as $c): ?>
                <tr data-customer='<?php echo json_encode($c); ?>'>
                    <td>
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div style="width:40px; height:40px; background:#f1f5f9; color:#2563eb; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800;">
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
                        <small><?php echo esc_html($c->address); ?></small>
                    </td>
                    <td style="text-align:left;">
                        <div style="display:flex; gap:5px; justify-content: flex-end;">
                            <?php if($can_manage): ?>
                                <button class="sukna-btn sukna-edit-customer" style="padding:4px 8px; font-size:0.75rem; background:#3b82f6;"><span class="dashicons dashicons-edit"></span></button>
                                <button class="sukna-btn sukna-delete-customer" style="padding:4px 8px; font-size:0.75rem; background:#ef4444;"><span class="dashicons dashicons-trash"></span></button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(empty($customers)) echo '<tr><td colspan="4" style="text-align:center; padding:40px;">'.__('لا يوجد عملاء مسجلين بعد', 'sukna').'</td></tr>'; ?>
        </tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($) {
    const modal = $('#sukna-customer-modal');

    $('#sukna-add-customer-btn').on('click', function() {
        $('#sukna-customer-form')[0].reset();
        $('#customer-id').val('');
        $('#modal-title').text('<?php _e('إضافة عميل جديد', 'sukna'); ?>');
        modal.css('display', 'flex');
    });

    $(document).on('click', '.sukna-edit-customer', function() {
        const c = $(this).closest('tr').data('customer');
        $('#customer-id').val(c.id);
        $('#customer-name').val(c.name);
        $('#customer-phone').val(c.phone);
        $('#customer-phone-secondary').val(c.phone_secondary);
        $('#customer-email').val(c.email);
        $('#customer-address').val(c.address);
        $('#modal-title').text('<?php _e('تعديل بيانات العميل', 'sukna'); ?>');
        modal.css('display', 'flex');
    });

    $('#close-modal').on('click', function() { modal.hide(); });

    $('#sukna-customer-form').on('submit', function(e) {
        e.preventDefault();
        $.post(sukna_ajax.ajax_url, $(this).serialize() + '&action=sukna_save_customer&nonce=' + sukna_ajax.nonce, () => location.reload());
    });

    $(document).on('click', '.sukna-delete-customer', function() {
        if (!confirm('حذف؟')) return;
        $.post(sukna_ajax.ajax_url, { action: 'sukna_delete_customer', id: $(this).closest('tr').data('customer').id, nonce: sukna_ajax.nonce }, () => location.reload());
    });

    function filterAndSortCustomers() {
        const query = $('#sukna-customer-search').val().toLowerCase();
        const rows = $('#sukna-customer-table tbody tr').get();

        rows.forEach(row => {
            const data = $(row).data('customer');
            if(!data) return;
            const match = data.name.toLowerCase().includes(query) ||
                          data.phone.includes(query) ||
                          (data.phone_secondary && data.phone_secondary.includes(query)) ||
                          (data.email && data.email.toLowerCase().includes(query));
            $(row).toggle(match);
        });
    }

    $('#sukna-customer-search').on('input', filterAndSortCustomers);

    $('#sukna-export-customers-pdf').on('click', function() {
        const element = document.getElementById('sukna-customer-table').cloneNode(true);
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
