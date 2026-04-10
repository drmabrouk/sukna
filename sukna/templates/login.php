<div class="sukna-login-container" style="display:flex; height:100vh; align-items:center; justify-content:center; background:#f1f5f9;">
    <form id="sukna-login-form" class="sukna-card" style="width:100%; max-width:400px; padding:40px; border-radius:12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);">
        <div style="text-align:center; margin-bottom:30px;">
            <h2 style="margin:0; font-size:1.5rem; color:#1e293b;"><?php _e('Sukna System', 'sukna'); ?></h2>
            <p style="color:#64748b; margin-top:10px;"><?php _e('يرجى تسجيل الدخول للمتابعة', 'sukna'); ?></p>
        </div>

        <div class="sukna-form-group">
            <label><?php _e('اسم المستخدم', 'sukna'); ?></label>
            <input type="text" name="username" required placeholder="<?php _e('Username', 'sukna'); ?>">
        </div>

        <div class="sukna-form-group">
            <label><?php _e('كلمة المرور', 'sukna'); ?></label>
            <input type="password" name="password" required placeholder="********">
        </div>

        <div id="sukna-login-error" style="display:none; padding:10px; background:#fee2e2; color:#991b1b; border-radius:6px; margin-bottom:20px; font-size:0.9rem; text-align:center;">
            <?php _e('اسم المستخدم أو كلمة المرور غير صحيحة', 'sukna'); ?>
        </div>

        <button type="submit" class="sukna-btn" style="width:100%; height:50px; font-size:1.1rem;">
            <?php _e('دخول للنظام', 'sukna'); ?>
        </button>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('#sukna-login-form').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const $error = $('#sukna-login-error');
        const originalBtnText = $btn.text();

        // Instant visual feedback
        $btn.prop('disabled', true).text('<?php _e('جاري التحميل...', 'sukna'); ?>');
        $error.hide();

        const data = $form.serialize() + '&action=sukna_login&nonce=' + sukna_ajax.nonce;
        $.post(sukna_ajax.ajax_url, data, function(response) {
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
