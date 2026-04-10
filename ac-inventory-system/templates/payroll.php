<?php
$month = isset($_GET['payroll_month']) ? sanitize_text_field($_GET['payroll_month']) : date('Y-m');
$payroll_data = AC_IS_Payroll::get_staff_payroll($month);

global $wpdb;
// Hide admin from staff selection
$staff_list = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ac_is_staff WHERE username != 'admin' ORDER BY name ASC" );
$can_edit_salary = AC_IS_Auth::is_admin() || AC_IS_Auth::is_manager();
?>

<div class="ac-is-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:var(--ac-sidebar-bg);"><?php _e('نظام المرتبات والحضور', 'ac-inventory-system'); ?></h2>
    <button class="ac-is-btn" id="ac-is-general-work-settings" style="background:#64748b; padding:8px;"><span class="dashicons dashicons-admin-generic"></span></button>
</div>

<div class="ac-is-grid" style="grid-template-columns: 1fr 2fr; gap:20px;">
    <!-- Attendance Entry -->
    <div class="ac-is-card">
        <h3><?php _e('تسجيل حضور وانصراف / شيفتات', 'ac-inventory-system'); ?></h3>
        <p style="font-size:0.8rem; color:#64748b; margin-bottom:15px;"><?php _e('الدوام الافتراضي: 09:00 ص - 10:00 م', 'ac-inventory-system'); ?></p>
        <form id="ac-is-attendance-form">
            <div class="ac-is-form-group">
                <label><?php _e('الموظف', 'ac-inventory-system'); ?></label>
                <select name="staff_id" required>
                    <?php foreach($staff_list as $s): ?>
                        <option value="<?php echo $s->id; ?>"><?php echo esc_html($s->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ac-is-form-group">
                <label><?php _e('التاريخ', 'ac-inventory-system'); ?></label>
                <input type="date" name="work_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="ac-is-grid" style="grid-template-columns: 1fr 1fr; gap:10px;">
                <div class="ac-is-form-group">
                    <label><?php _e('الحضور', 'ac-inventory-system'); ?></label>
                    <input type="time" name="check_in" value="09:00">
                </div>
                <div class="ac-is-form-group">
                    <label><?php _e('الانصراف', 'ac-inventory-system'); ?></label>
                    <input type="time" name="check_out" value="22:00">
                </div>
            </div>
            <div class="ac-is-form-group">
                <label><?php _e('نوع الوردية / الحالة', 'ac-inventory-system'); ?></label>
                <select name="status">
                    <option value="present"><?php _e('حاضر (وردية عادية)', 'ac-inventory-system'); ?></option>
                    <option value="absent"><?php _e('غائب', 'ac-inventory-system'); ?></option>
                    <option value="leave"><?php _e('إجازة', 'ac-inventory-system'); ?></option>
                    <option value="shift_request"><?php _e('طلب شيفت إضافي', 'ac-inventory-system'); ?></option>
                </select>
            </div>
            <button type="submit" class="ac-is-btn" style="width:100%; background:#1e293b;"><?php _e('حفظ السجل', 'ac-inventory-system'); ?></button>
        </form>
    </div>

    <!-- Payroll Summary -->
    <div class="ac-is-card">
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom:15px;">
            <h3><?php _e('ملخص مرتبات شهر:', 'ac-inventory-system'); ?> <?php echo $month; ?></h3>
            <form method="get">
                <input type="hidden" name="ac_view" value="payroll">
                <input type="month" name="payroll_month" value="<?php echo $month; ?>" onchange="this.form.submit()" style="padding:5px; border-radius:4px; border:1px solid #ddd;">
            </form>
        </div>

        <table class="ac-is-table">
            <thead>
                <tr>
                    <th><?php _e('الموظف', 'ac-inventory-system'); ?></th>
                    <th><?php _e('الراتب', 'ac-inventory-system'); ?></th>
                    <th><?php _e('الحضور', 'ac-inventory-system'); ?></th>
                    <th><?php _e('الخصومات', 'ac-inventory-system'); ?></th>
                    <th><?php _e('الصافي', 'ac-inventory-system'); ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($payroll_data as $p): ?>
                    <tr>
                        <td><strong><?php echo esc_html($p->name); ?></strong></td>
                        <td><?php echo number_format($p->base_salary, 2); ?></td>
                        <td>
                            <span class="ac-is-capsule capsule-primary"><?php echo $p->days_present; ?> <?php _e('يوم', 'ac-inventory-system'); ?></span>
                            <?php if($p->pending_shifts > 0): ?>
                                <span class="ac-is-capsule capsule-warning" style="cursor:pointer" onclick="approveShifts(<?php echo $p->staff_id; ?>)"><?php echo $p->pending_shifts; ?> <?php _e('شيفت معلق', 'ac-inventory-system'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><span style="color:red;"><?php echo number_format($p->deductions, 2); ?></span></td>
                        <td><span style="font-weight:800; color:#059669;"><?php echo number_format($p->net_salary, 2); ?> EGP</span></td>
                        <td>
                            <?php if($can_edit_salary): ?>
                                <button class="ac-is-edit-staff-payroll" data-id="<?php echo $p->staff_id; ?>" data-name="<?php echo esc_attr($p->name); ?>" data-salary="<?php echo $p->base_salary; ?>" data-hours="<?php echo $p->working_hours; ?>" style="background:none; border:none; cursor:pointer; color:var(--ac-primary);"><span class="dashicons dashicons-edit"></span></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for Staff Editing -->
<div id="ac-is-staff-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10005; align-items:center; justify-content:center;">
    <div class="ac-is-card" style="width:400px; background:#fff;">
        <h3 id="modal-staff-name"><?php _e('تعديل بيانات الموظف', 'ac-inventory-system'); ?></h3>
        <form id="ac-is-staff-edit-form">
            <input type="hidden" name="staff_id" id="modal-staff-id">
            <div class="ac-is-form-group">
                <label><?php _e('الراتب الأساسي', 'ac-inventory-system'); ?></label>
                <input type="number" name="base_salary" id="modal-staff-salary" step="0.01" required>
            </div>
            <div class="ac-is-form-group">
                <label><?php _e('عدد ساعات العمل اليومية', 'ac-inventory-system'); ?></label>
                <input type="number" name="working_hours" id="modal-staff-hours" required>
            </div>
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" class="ac-is-btn" style="flex:1;"><?php _e('حفظ', 'ac-inventory-system'); ?></button>
                <button type="button" class="ac-is-btn" style="flex:1; background:#64748b;" onclick="closeStaffModal()"><?php _e('إلغاء', 'ac-inventory-system'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal for General Work Settings -->
<div id="ac-is-general-work-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10005; align-items:center; justify-content:center;">
    <div class="ac-is-card" style="width:450px; background:#fff;">
        <h3><?php _e('إعدادات العمل العامة', 'ac-inventory-system'); ?></h3>
        <form id="ac-is-general-work-form">
            <div class="ac-is-grid" style="grid-template-columns: 1fr 1fr; gap:10px;">
                <div class="ac-is-form-group">
                    <label><?php _e('بداية الدوام', 'ac-inventory-system'); ?></label>
                    <input type="time" name="default_start" value="<?php echo esc_attr($wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}ac_is_settings WHERE setting_key = 'default_start'") ?: '09:00'); ?>">
                </div>
                <div class="ac-is-form-group">
                    <label><?php _e('نهاية الدوام', 'ac-inventory-system'); ?></label>
                    <input type="time" name="default_end" value="<?php echo esc_attr($wpdb->get_var("SELECT setting_value FROM {$wpdb->prefix}ac_is_settings WHERE setting_key = 'default_end'") ?: '22:00'); ?>">
                </div>
            </div>
            <div class="ac-is-form-group">
                <label><?php _e('العطلة الأسبوعية', 'ac-inventory-system'); ?></label>
                <p style="font-size:0.8rem; color:#64748b;"><?php _e('يوم الجمعة هو العطلة الرسمية، وبقية الأيام أيام عمل (الاثنين - الخميس عادية).', 'ac-inventory-system'); ?></p>
            </div>
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" class="ac-is-btn" style="flex:1;"><?php _e('حفظ الإعدادات', 'ac-inventory-system'); ?></button>
                <button type="button" class="ac-is-btn" style="flex:1; background:#64748b;" onclick="closeGeneralWorkModal()"><?php _e('إغاء', 'ac-inventory-system'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function closeStaffModal() { jQuery('#ac-is-staff-modal').fadeOut(200); }
function closeGeneralWorkModal() { jQuery('#ac-is-general-work-modal').fadeOut(200); }

function approveShifts(staffId) {
    if(!confirm('<?php _e('الموافقة على جميع الشيفتات المعلقة لهذا الموظف؟', 'ac-inventory-system'); ?>')) return;
    jQuery.post(ac_is_ajax.ajax_url, {
        action: 'ac_is_approve_shifts',
        staff_id: staffId,
        nonce: ac_is_ajax.nonce
    }, function() { location.reload(); });
}

jQuery(document).ready(function($) {
    $('#ac-is-attendance-form').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const originalBtnText = $btn.text();

        // Instant visual feedback
        $btn.prop('disabled', true).text('<?php _e('جاري الحفظ...', 'ac-inventory-system'); ?>');

        const data = $form.serialize() + '&action=ac_is_record_attendance&nonce=' + ac_is_ajax.nonce;
        $.post(ac_is_ajax.ajax_url, data, function(response) {
            if (response.success) {
                // Immediate refresh without alert
                location.reload();
            } else {
                $btn.prop('disabled', false).text(originalBtnText);
            }
        });
    });

    $('.ac-is-edit-staff-payroll').on('click', function() {
        const d = $(this).data();
        $('#modal-staff-id').val(d.id);
        $('#modal-staff-name').text('<?php _e('تعديل:', 'ac-inventory-system'); ?> ' + d.name);
        $('#modal-staff-salary').val(d.salary);
        $('#modal-staff-hours').val(d.hours);
        $('#ac-is-staff-modal').css('display', 'flex').hide().fadeIn(200);
    });

    $('#ac-is-staff-edit-form').on('submit', function(e) {
        e.preventDefault();
        $.post(ac_is_ajax.ajax_url, $(this).serialize() + '&action=ac_is_update_staff_payroll&nonce=' + ac_is_ajax.nonce, function() {
            location.reload();
        });
    });

    $('#ac-is-general-work-settings').on('click', function() {
        $('#ac-is-general-work-modal').css('display', 'flex').hide().fadeIn(200);
    });

    $('#ac-is-general-work-form').on('submit', function(e) {
        e.preventDefault();
        $.post(ac_is_ajax.ajax_url, $(this).serialize() + '&action=ac_is_save_work_settings&nonce=' + ac_is_ajax.nonce, function() {
            location.reload();
        });
    });

    $('.ac-is-view-staff-logs').on('click', function() {
        const d = $(this).data();
        $('#logs-modal-title').text('<?php _e('سجل حضور:', 'ac-inventory-system'); ?> ' + d.name + ' (' + d.month + ')');
        $.post(ac_is_ajax.ajax_url, {
            action: 'ac_is_get_staff_logs',
            staff_id: d.id,
            month: d.month,
            nonce: ac_is_ajax.nonce
        }, function(res) {
            if (res.success) {
                $('#ac-is-logs-table tbody').html(res.data.html);
                $('#ac-is-logs-modal').css('display', 'flex').hide().fadeIn(200);
            }
        });
    });

    $('#ac-is-export-payroll-pdf').on('click', function() {
        const element = document.getElementById('ac-is-logs-table');
        const title = $('#logs-modal-title').text();
        const opt = {
            margin: 10,
            filename: title + '.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    });
});
</script>

<!-- Staff Attendance Logs Modal -->
<div id="ac-is-logs-modal" class="ac-is-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10010; align-items:center; justify-content:center;">
    <div class="ac-is-card" style="width:700px; background:#fff; padding:25px; max-height:85vh; overflow-y:auto;">
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom:20px;">
            <h3 id="logs-modal-title" style="margin:0;"></h3>
            <button id="ac-is-export-payroll-pdf" class="ac-is-btn" style="background:#dc2626; font-size:0.8rem;"><span class="dashicons dashicons-pdf" style="margin-left:5px;"></span><?php _e('تصدير PDF', 'ac-inventory-system'); ?></button>
        </div>
        <table class="ac-is-table" id="ac-is-logs-table">
            <thead>
                <tr>
                    <th><?php _e('التاريخ', 'ac-inventory-system'); ?></th>
                    <th><?php _e('الحضور', 'ac-inventory-system'); ?></th>
                    <th><?php _e('الانصراف', 'ac-inventory-system'); ?></th>
                    <th><?php _e('المدة', 'ac-inventory-system'); ?></th>
                    <th><?php _e('الحالة', 'ac-inventory-system'); ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <button class="ac-is-btn" style="width:100%; margin-top:20px; background:#64748b;" onclick="jQuery('#ac-is-logs-modal').fadeOut(200)"><?php _e('إغلاق', 'ac-inventory-system'); ?></button>
    </div>
</div>
