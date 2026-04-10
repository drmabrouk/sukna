<div class="ac-is-login-container" style="display:flex; height:100vh; align-items:center; justify-content:center; background:#f1f5f9;">
    <form id="ac-is-login-form" class="ac-is-card" style="width:100%; max-width:400px; padding:40px; border-radius:12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);">
        <div style="text-align:center; margin-bottom:30px;">
            <h2 style="margin:0; font-size:1.5rem; color:var(--ac-sidebar-bg);"><?php _e('نظام البيع والمخزون', 'ac-inventory-system'); ?></h2>
            <p style="color:#64748b; margin-top:10px;"><?php _e('يرجى تسجيل الدخول للمتابعة', 'ac-inventory-system'); ?></p>
        </div>

        <div class="ac-is-form-group">
            <label><?php _e('اسم المستخدم', 'ac-inventory-system'); ?></label>
            <input type="text" name="username" required placeholder="<?php _e('Username', 'ac-inventory-system'); ?>">
        </div>

        <div class="ac-is-form-group">
            <label><?php _e('كلمة المرور', 'ac-inventory-system'); ?></label>
            <input type="password" name="password" required placeholder="********">
        </div>

        <div id="ac-is-login-error" style="display:none; padding:10px; background:var(--ac-danger); color:var(--ac-danger-text); border-radius:6px; margin-bottom:20px; font-size:0.9rem; text-align:center;">
            <?php _e('اسم المستخدم أو كلمة المرور غير صحيحة', 'ac-inventory-system'); ?>
        </div>

        <button type="submit" class="ac-is-btn" style="width:100%; height:50px; font-size:1.1rem;">
            <?php _e('دخول للنظام', 'ac-inventory-system'); ?>
        </button>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('#ac-is-login-form').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const $error = $('#ac-is-login-error');
        const originalBtnText = $btn.text();

        // Instant visual feedback
        $btn.prop('disabled', true).text('<?php _e('جاري التحميل...', 'ac-inventory-system'); ?>');
        $error.hide();

        const data = $form.serialize() + '&action=ac_is_login&nonce=' + ac_is_ajax.nonce;
        $.post(ac_is_ajax.ajax_url, data, function(response) {
            if (response.success) {
                // Instant redirection to dashboard
                window.location.href = window.location.href.split('#')[0].split('?')[0];
            } else {
                // Instant error display and reset
                $btn.prop('disabled', false).text(originalBtnText);
                $error.show();
            }
        });
    });
});
</script>
