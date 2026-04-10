<?php
$current_user = Sukna_Auth::current_user();
$is_admin = Sukna_Auth::is_admin();
$is_owner = Sukna_Auth::is_owner();

$args = array();
if ( $is_owner && !$is_admin ) {
    $args['owner_id'] = $current_user->id;
}

$properties = Sukna_Properties::get_all_properties($args);
$users = Sukna_Auth::get_all_users();
$owners = array_filter($users, function($u){ return $u->role === 'owner' || $u->role === 'admin'; });
$tenants = array_filter($users, function($u){ return $u->role === 'tenant'; });
$investors = array_filter($users, function($u){ return $u->role === 'investor'; });
?>

<div class="sukna-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:#1e293b;"><?php _e('إدارة العقارات والوحدات السكنية', 'sukna'); ?></h2>
    <div style="display:flex; gap:10px;">
        <?php if($is_admin || $is_owner): ?>
            <button id="sukna-add-property-btn" class="sukna-btn" style="background:#000; border-radius: 8px;">
                <span class="dashicons dashicons-plus-alt" style="margin-left:8px;"></span><?php _e('إضافة عقار جديد', 'sukna'); ?>
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="sukna-grid" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
    <?php foreach($properties as $p):
        $rooms = Sukna_Properties::get_rooms($p->id);
        $rented_count = count(array_filter($rooms, function($r){ return $r->status === 'rented'; }));
    ?>
        <div class="sukna-card" style="border-top: 4px solid #D4AF37; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <div style="display:flex; justify-content: space-between; align-items: flex-start; margin-bottom:15px;">
                <div>
                    <h3 style="margin:0; font-size:1.2rem; color:#000;"><?php echo esc_html($p->name); ?></h3>
                    <small style="color:#64748b;"><span class="dashicons dashicons-location" style="font-size:14px; width:14px; height:14px;"></span> <?php echo esc_html($p->address); ?></small>
                    <br><small style="color:#D4AF37; font-weight: 600;"><?php _e('المالك:', 'sukna'); ?> <?php echo esc_html($p->owner_name); ?></small>
                </div>
                <div style="display:flex; gap:5px;">
                    <button class="sukna-btn sukna-edit-property" data-property='<?php echo json_encode($p); ?>' style="padding:4px 8px; font-size:0.7rem; background:#000; border:none;"><span class="dashicons dashicons-edit"></span></button>
                    <button class="sukna-btn sukna-delete-property" data-id="<?php echo $p->id; ?>" style="padding:4px 8px; font-size:0.7rem; background:#333; border:none;"><span class="dashicons dashicons-trash"></span></button>
                </div>
            </div>

            <div style="background:#f8fafc; padding:15px; border-radius:8px; margin-bottom:15px;">
                <div style="display:flex; justify-content: space-between; margin-bottom:10px;">
                    <span style="font-size:0.85rem; color:#64748b;"><?php _e('إجمالي الوحدات:', 'sukna'); ?></span>
                    <span style="font-weight:700;"><?php echo count($rooms); ?></span>
                </div>
                <div style="display:flex; justify-content: space-between;">
                    <span style="font-size:0.85rem; color:#64748b;"><?php _e('الحالة:', 'sukna'); ?></span>
                    <span class="sukna-capsule <?php echo ($rented_count == count($rooms) && count($rooms) > 0) ? 'capsule-danger' : 'capsule-accent'; ?>">
                        <?php echo $rented_count; ?> / <?php echo count($rooms); ?> <?php _e('مؤجر', 'sukna'); ?>
                    </span>
                </div>
            </div>

            <div style="display:flex; gap:10px;">
                <button class="sukna-btn sukna-manage-rooms" data-id="<?php echo $p->id; ?>" style="flex:1; font-size:0.8rem; background:#000; border:none; border-radius: 6px;"><?php _e('إدارة الوحدات', 'sukna'); ?></button>
                <?php if($is_admin): ?>
                    <button class="sukna-btn sukna-manage-investors" data-id="<?php echo $p->id; ?>" style="flex:1; font-size:0.8rem; background:#D4AF37; color:#000 !important; border:none; border-radius: 6px;"><?php _e('المستثمرون', 'sukna'); ?></button>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Property Modal -->
<div id="sukna-property-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0, 0, 0, 0.6); z-index:10001; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="sukna-card" style="width:100%; max-width:550px; padding:40px; border-radius:12px;">
        <h3 id="prop-modal-title" style="font-size:1.4rem; margin-bottom:30px;"><?php _e('بيانات العقار', 'sukna'); ?></h3>
        <form id="sukna-property-form">
            <input type="hidden" name="id" id="prop-id">
            <div class="sukna-form-group">
                <input type="text" name="name" id="prop-name" placeholder="<?php _e('اسم العقار', 'sukna'); ?>" required style="width:100%;">
            </div>
            <div class="sukna-form-group">
                <textarea name="address" id="prop-address" placeholder="<?php _e('العنوان بالتفصيل', 'sukna'); ?>" rows="2" style="width:100%;"></textarea>
            </div>
            <div class="sukna-form-group">
                <select name="owner_id" id="prop-owner-id" style="width:100%;">
                    <option value=""><?php _e('اختر المالك', 'sukna'); ?></option>
                    <?php foreach($owners as $o): ?>
                        <option value="<?php echo $o->id; ?>"><?php echo esc_html($o->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex; gap:15px; margin-top:30px;">
                <button type="submit" class="sukna-btn" style="flex:1; background:#000; border:none; border-radius: 8px;"><?php _e('حفظ العقار', 'sukna'); ?></button>
                <button type="button" class="sukna-btn close-prop-modal" style="flex:1; background:#64748b; border:none; border-radius: 8px;"><?php _e('إلغاء', 'sukna'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Room Management Modal -->
<div id="sukna-room-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0, 0, 0, 0.6); z-index:10001; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="sukna-card" style="width:100%; max-width:900px; max-height: 90vh; overflow-y: auto; padding:40px; border-radius:12px;">
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h3 style="margin:0; font-size:1.4rem;"><?php _e('إدارة الوحدات والغرف', 'sukna'); ?></h3>
            <button type="button" class="sukna-btn close-room-modal" style="background:#333; border:none; padding: 5px 15px; border-radius: 4px;">X</button>
        </div>

        <form id="sukna-room-form" style="background:#f8fafc; padding:20px; border-radius:8px; margin-bottom:30px; border: 1px solid #e2e8f0;">
            <input type="hidden" name="property_id" id="room-property-id">
            <div class="sukna-grid" style="grid-template-columns: repeat(3, 1fr); gap:15px;">
                <div class="sukna-form-group">
                    <input type="text" name="room_number" placeholder="<?php _e('رقم الوحدة/الغرفة', 'sukna'); ?>" required style="width:100%;">
                </div>
                <div class="sukna-form-group">
                    <input type="number" step="0.01" name="rental_price" placeholder="<?php _e('سعر الإيجار', 'sukna'); ?>" required style="width:100%;">
                </div>
                <div class="sukna-form-group">
                    <select name="status" style="width:100%;">
                        <option value="available"><?php _e('متاحة', 'sukna'); ?></option>
                        <option value="rented"><?php _e('مؤجرة', 'sukna'); ?></option>
                        <option value="maintenance"><?php _e('صيانة', 'sukna'); ?></option>
                    </select>
                </div>
            </div>
            <div class="sukna-grid" style="grid-template-columns: repeat(3, 1fr); gap:15px; margin-top:10px;">
                <div class="sukna-form-group">
                    <select name="tenant_id" style="width:100%;">
                        <option value=""><?php _e('المستأجر (اختياري)', 'sukna'); ?></option>
                        <?php foreach($tenants as $t): ?>
                            <option value="<?php echo $t->id; ?>"><?php echo esc_html($t->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sukna-form-group">
                    <input type="text" onfocus="(this.type='date')" name="rental_start_date" placeholder="<?php _e('بداية الإيجار', 'sukna'); ?>" style="width:100%;">
                </div>
                <div class="sukna-form-group">
                    <select name="payment_frequency" style="width:100%;">
                        <option value="monthly"><?php _e('شهري', 'sukna'); ?></option>
                        <option value="quarterly"><?php _e('ربع سنوي', 'sukna'); ?></option>
                        <option value="yearly"><?php _e('سنوي', 'sukna'); ?></option>
                    </select>
                </div>
            </div>
            <button type="submit" class="sukna-btn" style="width:100%; margin-top:10px; background:#D4AF37; color:#000 !important; border:none; border-radius: 8px;"><?php _e('إضافة الوحدة', 'sukna'); ?></button>
        </form>

        <table class="sukna-table">
            <thead>
                <tr>
                    <th><?php _e('رقم الوحدة', 'sukna'); ?></th>
                    <th><?php _e('السعر', 'sukna'); ?></th>
                    <th><?php _e('الحالة', 'sukna'); ?></th>
                    <th><?php _e('المستأجر', 'sukna'); ?></th>
                    <th><?php _e('إجراءات', 'sukna'); ?></th>
                </tr>
            </thead>
            <tbody id="sukna-rooms-table-body">
                <!-- Loaded via AJAX -->
            </tbody>
        </table>
    </div>
</div>

<!-- Investor Management Modal -->
<div id="sukna-investor-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0, 0, 0, 0.6); z-index:10001; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="sukna-card" style="width:100%; max-width:600px; padding:40px; border-radius:12px;">
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h3 style="margin:0; font-size:1.4rem;"><?php _e('إدارة المستثمرين في العقار', 'sukna'); ?></h3>
            <button type="button" class="sukna-btn close-investor-modal" style="background:#333; border:none; padding: 5px 15px; border-radius: 4px;">X</button>
        </div>

        <form id="sukna-investment-form" style="background:#f8fafc; padding:20px; border-radius:8px; margin-bottom:30px; border: 1px solid #e2e8f0;">
            <input type="hidden" name="property_id" id="invest-property-id">
            <div class="sukna-form-group">
                <select name="investor_id" required style="width:100%;">
                    <option value=""><?php _e('اختر المستثمر', 'sukna'); ?></option>
                    <?php foreach($investors as $i): ?>
                        <option value="<?php echo $i->id; ?>"><?php echo esc_html($i->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sukna-form-group">
                <input type="number" step="0.01" name="amount" placeholder="<?php _e('مبلغ المساهمة', 'sukna'); ?>" required style="width:100%;">
            </div>
            <button type="submit" class="sukna-btn" style="width:100%; background:#000; border:none; border-radius: 8px;"><?php _e('إضافة مساهمة', 'sukna'); ?></button>
        </form>

        <div id="sukna-investments-list">
            <!-- Loaded via AJAX -->
        </div>
    </div>
</div>
