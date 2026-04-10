<?php
$products = AC_IS_Inventory::get_products();
?>

<div class="ac-is-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:var(--ac-sidebar-bg);"><?php _e('نظام البيع السريع', 'ac-inventory-system'); ?></h2>

    <!-- Mode Toggle Boxes -->
    <div class="ac-is-mode-toggles" style="display:flex; gap:10px; background:#fff; padding:5px; border-radius:8px; border:1px solid var(--ac-border);">
        <div class="ac-is-mode-box active" data-mode="manual">
            <span class="dashicons dashicons-edit"></span>
            <small><?php _e('يدوي', 'ac-inventory-system'); ?></small>
        </div>
        <div class="ac-is-mode-box" data-mode="scan">
            <span class="dashicons dashicons-camera"></span>
            <small><?php _e('ماسح', 'ac-inventory-system'); ?></small>
        </div>
    </div>
</div>

<div class="ac-is-sales-container">
    <div class="ac-is-grid" style="grid-template-columns: 1.2fr 1fr; gap:25px; align-items: start;">

        <!-- Left Column: Cart & Operations -->
        <div class="ac-is-operations-area">
            <div id="ac-is-reader-container" style="display:none; margin-bottom:20px; border: 4px solid var(--ac-primary); border-radius:12px; overflow:hidden;">
                <div id="ac-is-reader"></div>
                <div class="ac-is-scan-overlay"><div class="ac-is-scan-frame"><div class="ac-is-scan-corners"></div></div></div>
                <div class="ac-is-scan-status"><?php _e('جاهز للمسح', 'ac-inventory-system'); ?></div>
            </div>

            <div id="ac-is-manual-entry-area">
                <div class="ac-is-card" style="padding:15px; margin-bottom:20px;">
                    <input type="text" id="ac-is-sale-product-search" placeholder="<?php _e('بحث سريع بالاسم أو الباركود...', 'ac-inventory-system'); ?>" style="width:100%; height:45px; font-size:1.1rem;">
                </div>

                <div class="ac-is-card" style="padding:20px; margin-bottom:20px;">
                    <div class="ac-is-form-group">
                        <label><?php _e('المنتج', 'ac-inventory-system'); ?></label>
                        <select id="ac-is-sale-product" style="height:45px;">
                            <option value=""><?php _e('--- اختر المنتج ---', 'ac-inventory-system'); ?></option>
                            <?php foreach($products as $product): ?>
                                <option value="<?php echo $product->id; ?>"
                                        data-name="<?php echo esc_attr($product->name); ?>"
                                        data-price="<?php echo $product->final_price; ?>"
                                        data-stock="<?php echo $product->stock_quantity; ?>"
                                        data-barcode="<?php echo esc_attr($product->barcode); ?>"
                                        data-serial="<?php echo esc_attr($product->serial_number); ?>">
                                    <?php echo esc_html($product->name); ?> (<?php echo $product->stock_quantity; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ac-is-grid" style="grid-template-columns: 1.5fr 1fr; gap:15px;">
                        <div class="ac-is-form-group">
                            <label><?php _e('السيريال (S/N)', 'ac-inventory-system'); ?></label>
                            <input type="text" id="ac-is-sale-serial" placeholder="<?php _e('رقم الوحدة', 'ac-inventory-system'); ?>">
                        </div>
                        <div class="ac-is-form-group">
                            <label><?php _e('الكمية', 'ac-inventory-system'); ?></label>
                            <input type="number" id="ac-is-sale-qty" min="1" value="1">
                        </div>
                    </div>
                    <button type="button" id="ac-is-add-to-list" class="ac-is-btn" style="width:100%; background:#1e293b; height:45px;">
                        <span class="dashicons dashicons-plus-alt" style="margin-left:8px;"></span><?php _e('إضافة للفاتورة', 'ac-inventory-system'); ?>
                    </button>
                </div>
            </div>

            <div class="ac-is-card" style="padding:0; overflow:hidden; border-top: 4px solid var(--ac-primary);">
                <div style="padding:15px; background:#f8fafc; border-bottom:1px solid #eee;">
                    <h3 style="margin:0; font-size:1rem;"><?php _e('المنتجات المختارة', 'ac-inventory-system'); ?></h3>
                </div>
                <table class="ac-is-table">
                    <tbody id="ac-is-cart-body">
                        <tr class="empty-cart"><td colspan="3" style="text-align:center; padding:40px; color:#94a3b8;"><?php _e('سلة المشتريات فارغة', 'ac-inventory-system'); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right Column: Customer & Payment -->
        <div class="ac-is-customer-payment-area">
            <div class="ac-is-card" style="padding:25px; margin-bottom:20px;">
                <h3 style="margin-bottom:20px; font-size:1.1rem;"><?php _e('بيانات العميل', 'ac-inventory-system'); ?></h3>

                <div class="ac-is-form-group">
                    <label><?php _e('رقم الهاتف للتفتيش والربط', 'ac-inventory-system'); ?></label>
                    <input type="text" id="ac-is-customer-phone" placeholder="01xxxxxxxxx" style="height:45px; font-size:1.1rem; border-color:var(--ac-primary);">
                </div>

                <div id="customer-details-fields" style="display:none; border-top:1px solid #eee; padding-top:20px; margin-top:20px;">
                    <div class="ac-is-form-group">
                        <label><?php _e('اسم العميل', 'ac-inventory-system'); ?></label>
                        <input type="text" id="ac-is-customer-name">
                    </div>
                    <div class="ac-is-form-group">
                        <label><?php _e('العنوان', 'ac-inventory-system'); ?></label>
                        <input type="text" id="ac-is-customer-address">
                    </div>
                    <div class="ac-is-form-group">
                        <label><?php _e('البريد الإلكتروني', 'ac-inventory-system'); ?></label>
                        <input type="email" id="ac-is-customer-email">
                    </div>
                </div>

                <div id="quick-customer-hint" style="margin:10px 0; color:#64748b; font-size:0.85rem;">
                    <span class="dashicons dashicons-info" style="font-size:14px; width:14px; height:14px;"></span>
                    <?php _e('اترك الحقول فارغة للبيع كعميل سريع', 'ac-inventory-system'); ?>
                </div>
            </div>

            <div class="ac-is-card" style="padding:25px; background:var(--ac-sidebar-bg); color:#fff; border:none;">
                <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom:25px;">
                    <span style="font-size:1.1rem; font-weight:600;"><?php _e('الإجمالي النهائي', 'ac-inventory-system'); ?></span>
                    <span style="font-size:2rem; font-weight:900;"><span id="ac-is-cart-total">0.00</span> <small style="font-size:1rem;">EGP</small></span>
                </div>

                <div style="margin-bottom:20px;">
                    <div class="ac-is-form-group">
                        <label style="color:rgba(255,255,255,0.7); font-size:0.8rem; margin-bottom:5px;"><?php _e('مدة الضمان', 'ac-inventory-system'); ?></label>
                        <select id="ac-is-sale-warranty" style="background:#1e293b; color:#fff; border-color:#334155; height:40px;">
                            <option value="0"><?php _e('بدون ضمان', 'ac-inventory-system'); ?></option>
                            <option value="1">1 <?php _e('سنة', 'ac-inventory-system'); ?></option>
                            <option value="2">2 <?php _e('سنة', 'ac-inventory-system'); ?></option>
                            <option value="3">3 <?php _e('سنوات', 'ac-inventory-system'); ?></option>
                            <option value="4">4 <?php _e('سنوات', 'ac-inventory-system'); ?></option>
                            <option value="5">5 <?php _e('سنوات', 'ac-inventory-system'); ?></option>
                        </select>
                    </div>
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-size:0.9rem;">
                        <input type="checkbox" id="ac-is-send-email" checked> <?php _e('إرسال نسخة للبريد الإلكتروني', 'ac-inventory-system'); ?>
                    </label>
                </div>

                <button type="button" id="ac-is-finalize-sale" class="ac-is-btn" style="width:100%; height:60px; background:#059669; font-size:1.3rem; border:none; box-shadow:0 4px 12px rgba(5, 150, 105, 0.4);">
                    <span class="dashicons dashicons-yes-alt" style="margin-left:10px;"></span> <?php _e('تأكيد وإصدار الفاتورة', 'ac-inventory-system'); ?>
                </button>
            </div>
        </div>

    </div>
</div>

<style>
.ac-is-mode-box {
    padding: 10px 20px;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    border-radius: 6px;
    transition: all 0.2s;
    color: #64748b;
}
.ac-is-mode-box:hover { background: #f1f5f9; }
.ac-is-mode-box.active { background: var(--ac-primary); color: #fff; }
.ac-is-mode-box .dashicons { font-size: 20px; width: 20px; height: 20px; }
.ac-is-mode-box small { font-weight: 700; font-size: 0.7rem; }
</style>
