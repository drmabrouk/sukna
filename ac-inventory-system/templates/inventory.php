<?php
$products = AC_IS_Inventory::get_products();
$brands = AC_IS_Brands::get_brands();
$brand_map = array();
foreach($brands as $b) { $brand_map[$b->id] = $b; }
?>
<div class="ac-is-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap:15px;">
    <h2><?php _e('إدارة المخزون', 'ac-inventory-system'); ?></h2>
    <div style="display:flex; gap:10px; flex-wrap: wrap;">
        <button id="ac-is-inventory-add-scan-btn" class="ac-is-btn" style="background:#059669;"><span class="dashicons dashicons-camera" style="margin-left:5px;"></span><?php _e('إضافة منتج بباركود', 'ac-inventory-system'); ?></button>
        <button id="ac-is-inventory-bulk-scan-btn" class="ac-is-btn" style="background:#1e293b;"><span class="dashicons dashicons-forms" style="margin-left:5px;"></span><?php _e('إدخال سريع', 'ac-inventory-system'); ?></button>
        <button id="ac-is-bulk-barcode-pdf" class="ac-is-btn" style="background:#64748b;"><span class="dashicons dashicons-pdf" style="margin-left:5px;"></span><?php _e('تصدير الباركود (PDF)', 'ac-inventory-system'); ?></button>
        <a href="<?php echo add_query_arg('ac_view', 'add-product'); ?>" class="ac-is-btn"><span class="dashicons dashicons-plus" style="margin-left:5px;"></span><?php _e('إضافة منتج جديد', 'ac-inventory-system'); ?></a>
    </div>
</div>

<div class="ac-is-search-filters" style="margin-bottom:25px; display:flex; gap:15px; flex-wrap: wrap; background:#fff; padding:20px; border:1px solid var(--ac-border);">
    <input type="text" id="ac-is-inventory-search" placeholder="<?php _e('ابحث بالاسم، الباركود أو السيريال...', 'ac-inventory-system'); ?>" style="flex:1; min-width:300px; padding:12px; border:1px solid #ddd; border-radius:8px;">
    <select id="ac-is-inventory-category" style="padding:12px; border:1px solid #ddd; border-radius:8px; width:200px;">
        <option value=""><?php _e('كل التصنيفات', 'ac-inventory-system'); ?></option>
        <option value="ac"><?php _e('مكيفات', 'ac-inventory-system'); ?></option>
        <option value="filter"><?php _e('فلاتر مياه', 'ac-inventory-system'); ?></option>
        <option value="cooling"><?php _e('أنظمة تبريد', 'ac-inventory-system'); ?></option>
    </select>
</div>

<div style="background:#fff; border:1px solid var(--ac-border); overflow:hidden;">
    <table class="ac-is-table">
        <thead>
            <tr>
                <th><?php _e('البراند', 'ac-inventory-system'); ?></th>
                <th><?php _e('الاسم والتصنيف', 'ac-inventory-system'); ?></th>
                <th><?php _e('بيانات الموديل', 'ac-inventory-system'); ?></th>
                <th><?php _e('التسعير (EGP)', 'ac-inventory-system'); ?></th>
                <th><?php _e('المخزون', 'ac-inventory-system'); ?></th>
                <th><?php _e('إجراءات', 'ac-inventory-system'); ?></th>
            </tr>
        </thead>
        <tbody id="ac-is-inventory-table-body">
            <?php if ( $products ) : foreach ( $products as $product ) :
                $stock_class = ($product->stock_quantity < 5) ? 'capsule-danger' : (($product->stock_quantity < 15) ? 'capsule-warning' : 'capsule-success');
                $category_name = ($product->category == 'ac') ? __('مكيفات', 'ac-inventory-system') : (($product->category == 'filter') ? __('فلاتر', 'ac-inventory-system') : __('تبريد', 'ac-inventory-system'));
                $brand = $brand_map[$product->brand_id] ?? null;
            ?>
                <tr data-id="<?php echo $product->id; ?>">
                    <td style="text-align:center;">
                        <?php if($brand && $brand->logo_url): ?>
                            <img src="<?php echo esc_url($brand->logo_url); ?>" style="height:35px; max-width:100px; object-fit:contain;" title="<?php echo esc_attr($brand->name); ?>">
                        <?php else: ?>
                            <small style="color:#94a3b8;"><?php echo $brand ? esc_html($brand->name) : __('بدون براند', 'ac-inventory-system'); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo esc_html( $product->name ); ?></strong><br>
                        <span class="ac-is-capsule capsule-primary"><?php echo $category_name; ?></span>
                        <?php if($product->subcategory): ?><small style="color:#64748b;"> (<?php echo esc_html($product->subcategory); ?>)</small><?php endif; ?>
                    </td>
                    <td>
                        <?php if($product->model_number): ?><small>MOD: <?php echo esc_html($product->model_number); ?></small><br><?php endif; ?>
                        <?php if($product->filter_stages): ?><small><?php echo $product->filter_stages; ?> <?php _e('مراحل', 'ac-inventory-system'); ?></small><br><?php endif; ?>
                        <small>B: <?php echo esc_html($product->barcode ?: 'N/A'); ?></small>
                    </td>
                    <td>
                        <div style="font-size:0.85rem;">
                            <span title="<?php _e('سعر البيع النهائي', 'ac-inventory-system'); ?>" style="font-weight:bold; color:var(--ac-primary);"><?php echo number_format($product->final_price, 2); ?></span><br>
                            <?php if($product->discount > 0): ?>
                                <del title="<?php _e('السعر المعروض', 'ac-inventory-system'); ?>" style="font-size:0.75rem; color:#94a3b8;"><?php echo number_format($product->original_price, 2); ?></del>
                                <span class="ac-is-capsule capsule-danger" style="font-size:0.7rem; padding: 1px 4px;"><?php echo $product->discount; ?>%</span><br>
                            <?php endif; ?>
                            <small title="<?php _e('سعر الشراء', 'ac-inventory-system'); ?>" style="color:#64748b; font-size:0.7rem;">(Cost: <?php echo number_format($product->purchase_cost, 2); ?>)</small>
                        </div>
                    </td>
                    <td>
                        <span class="ac-is-capsule <?php echo $stock_class; ?>"><?php echo esc_html( $product->stock_quantity ); ?></span>
                    </td>
                    <td>
                        <div style="display:flex; gap:5px;">
                            <?php if ( AC_IS_Auth::can_edit_products() ) : ?>
                                <a href="<?php echo add_query_arg( array('ac_view' => 'edit-product', 'id' => $product->id) ); ?>" class="ac-is-btn" style="padding: 6px 10px; font-size:0.8rem; background:#3b82f6;" title="<?php _e('تعديل', 'ac-inventory-system'); ?>"><span class="dashicons dashicons-edit"></span></a>
                            <?php endif; ?>
                            <button class="ac-is-btn ac-is-print-barcode" data-barcode="<?php echo esc_attr($product->barcode); ?>" data-name="<?php echo esc_attr($product->name); ?>" data-serial="<?php echo esc_attr($product->serial_number); ?>" style="padding: 6px 10px; font-size:0.8rem; background:#64748b;" title="<?php _e('طباعة ملصق', 'ac-inventory-system'); ?>"><span class="dashicons dashicons-printer"></span></button>
                            <button class="ac-is-btn ac-is-download-barcode" data-barcode="<?php echo esc_attr($product->barcode); ?>" data-name="<?php echo esc_attr($product->name); ?>" style="padding: 6px 10px; font-size:0.8rem; background:#059669;" title="<?php _e('تحميل PNG', 'ac-inventory-system'); ?>"><span class="dashicons dashicons-download"></span></button>
                            <?php if ( AC_IS_Auth::can_delete_products() ) : ?>
                                <button class="ac-is-delete-product ac-is-btn" data-id="<?php echo $product->id; ?>" style="padding: 6px 10px; font-size:0.8rem; background:#ef4444;" title="<?php _e('حذف', 'ac-inventory-system'); ?>"><span class="dashicons dashicons-trash"></span></button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; else : ?>
                <tr><td colspan="6" style="text-align:center; padding: 40px; color: #94a3b8;"><?php _e('لا توجد منتجات مسجلة حالياً.', 'ac-inventory-system'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="barcode-print-section" style="display:none;">
    <div class="barcode-sticker">
        <div class="product-name" id="print-product-name"></div>
        <svg id="print-barcode-svg"></svg>
    </div>
</div>
