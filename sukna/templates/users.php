<?php
$users = Sukna_Auth::get_all_users();
$can_manage = Sukna_Auth::is_admin();
?>

<div class="sukna-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:#1e293b;"><?php _e('إدارة مستخدمي النظام', 'sukna'); ?></h2>
    <div style="display:flex; gap:10px;">
        <?php if($can_manage): ?>
            <button id="sukna-add-user-btn" class="sukna-btn" style="background:#000; border:none; border-radius: 8px;">
                <span class="dashicons dashicons-plus-alt" style="margin-left:8px;"></span><?php _e('إضافة مستخدم جديد', 'sukna'); ?>
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- User Modal -->
<div id="sukna-user-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0, 0, 0, 0.6); z-index:10001; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="sukna-card" style="width:100%; max-width:550px; padding:40px; border-radius:12px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
        <h3 id="modal-title" style="font-size:1.4rem; margin-bottom:30px; color: #000;"><?php _e('بيانات المستخدم', 'sukna'); ?></h3>
        <form id="sukna-user-form">
            <input type="hidden" name="id" id="user-id">
            <div class="sukna-form-group">
                <input type="text" name="username" id="user-username" placeholder="<?php _e('اسم المستخدم', 'sukna'); ?>" style="width:100%;">
            </div>
            <div class="sukna-form-group">
                <input type="text" name="phone" id="user-phone" placeholder="<?php _e('رقم الهاتف', 'sukna'); ?>" required style="width:100%;">
            </div>
            <div class="sukna-form-group">
                <input type="text" name="name" id="user-name" placeholder="<?php _e('الاسم بالكامل', 'sukna'); ?>" required style="width:100%;">
            </div>
            <div class="sukna-form-group">
                <input type="email" name="email" id="user-email" placeholder="<?php _e('البريد الإلكتروني', 'sukna'); ?>" style="width:100%;">
            </div>
            <div class="sukna-form-group">
                <input type="password" name="password" id="user-password" placeholder="<?php _e('كلمة المرور', 'sukna'); ?>" style="width:100%;">
            </div>
            <div class="sukna-form-group">
                <select name="role" id="user-role" style="width:100%;">
                    <option value="admin"><?php _e('مدير نظام', 'sukna'); ?></option>
                    <option value="owner"><?php _e('مالك عقار', 'sukna'); ?></option>
                    <option value="investor"><?php _e('مستثمر', 'sukna'); ?></option>
                    <option value="tenant"><?php _e('مستأجر', 'sukna'); ?></option>
                    <option value="employee"><?php _e('موظف', 'sukna'); ?></option>
                </select>
            </div>
            <div style="display:flex; gap:15px; margin-top:30px;">
                <button type="submit" class="sukna-btn" style="flex:1; background:#000; border:none; border-radius: 8px;"><?php _e('حفظ البيانات', 'sukna'); ?></button>
                <button type="button" class="sukna-btn close-user-modal" style="flex:1; background:#64748b; border:none; border-radius: 8px;"><?php _e('إلغاء', 'sukna'); ?></button>
            </div>
        </form>
    </div>
</div>

<div style="background:#fff; border:1px solid #e2e8f0; border-radius: 12px; overflow:hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
    <table class="sukna-table" id="sukna-user-table">
        <thead>
            <tr>
                <th><?php _e('المستخدم', 'sukna'); ?></th>
                <th><?php _e('الهاتف', 'sukna'); ?></th>
                <th><?php _e('الاسم', 'sukna'); ?></th>
                <th><?php _e('الصلاحية', 'sukna'); ?></th>
                <th><?php _e('إجراءات', 'sukna'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($users as $u): ?>
                <tr data-user='<?php echo json_encode($u); ?>'>
                    <td><strong><?php echo esc_html($u->username ?: '-'); ?></strong></td>
                    <td><?php echo esc_html($u->phone); ?></td>
                    <td><?php echo esc_html($u->name); ?></td>
                    <td><span class="sukna-capsule capsule-accent"><?php
                        $roles = array('admin' => 'مدير نظام', 'owner' => 'مالك', 'investor' => 'مستثمر', 'tenant' => 'مستأجر', 'employee' => 'موظف');
                        echo $roles[$u->role] ?? $u->role;
                    ?></span></td>
                    <td style="text-align:left;">
                        <div style="display:flex; gap:5px; justify-content: flex-end;">
                            <?php if($can_manage): ?>
                                <button class="sukna-btn sukna-edit-user" style="padding:4px 8px; font-size:0.75rem; background:#000; border:none; border-radius: 4px;"><span class="dashicons dashicons-edit"></span></button>
                                <?php if($u->username !== 'admin'): ?>
                                    <button class="sukna-btn sukna-delete-user" data-id="<?php echo $u->id; ?>" style="padding:4px 8px; font-size:0.75rem; background:#333; border:none; border-radius: 4px;"><span class="dashicons dashicons-trash"></span></button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($) {
    const modal = $('#sukna-user-modal');

    $('#sukna-add-user-btn').on('click', function() {
        $('#sukna-user-form')[0].reset();
        $('#user-id').val('');
        $('#user-password').prop('required', true);
        $('#modal-title').text('<?php _e('إضافة مستخدم جديد', 'sukna'); ?>');
        modal.css('display', 'flex');
    });

    $(document).on('click', '.sukna-edit-user', function() {
        const u = $(this).closest('tr').data('user');
        $('#user-id').val(u.id);
        $('#user-username').val(u.username);
        $('#user-phone').val(u.phone);
        $('#user-name').val(u.name);
        $('#user-email').val(u.email);
        $('#user-role').val(u.role);
        $('#user-password').val('').prop('required', false);
        $('#modal-title').text('<?php _e('تعديل بيانات المستخدم', 'sukna'); ?>');
        modal.css('display', 'flex');
    });

    $('.close-user-modal').on('click', function() { modal.hide(); });
});
</script>
