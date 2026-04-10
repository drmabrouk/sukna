jQuery(document).ready(function($) {
    // Shared State
    const SCAN_COOLDOWN = 5000; // 5 seconds

    // --- Infrastructure & Other Logic ---

    const syncLoader = $('#sukna-sync-loader');
    function showSync(text = 'جارٍ تحميل البيانات...') { syncLoader.find('.loader-text').text(text); syncLoader.fadeIn(200); }
    function hideSync() { syncLoader.find('.loader-text').text('تم التحديث بنجاح'); setTimeout(() => syncLoader.fadeOut(400), 1000); }

    $(document).ajaxStart(function() { showSync(); });
    $(document).ajaxStop(function() { hideSync(); });

    $('#sukna-refresh-btn, #sukna-mobile-refresh-btn').on('click', function() {
        showSync('جاري مسح التخزين المؤقت وتحديث البيانات...');
        if (window.sessionStorage) window.sessionStorage.clear();
        setTimeout(() => { window.location.reload(true); }, 500);
    });

    const systemRoot = document.getElementById('sukna-system-root');

    $('#sukna-fullscreen-btn').on('click', function() {
        if (!document.fullscreenElement) {
            if (systemRoot.requestFullscreen) systemRoot.requestFullscreen();
            else if (systemRoot.webkitRequestFullscreen) systemRoot.webkitRequestFullscreen();
            localStorage.setItem('sukna_fullscreen', '1');
        } else {
            $('#sukna-unlock-overlay').css('display', 'flex').hide().fadeIn(300);
            $('#sukna-unlock-pass').focus();
        }
    });

    // Auto-restore fullscreen state
    if (localStorage.getItem('sukna_fullscreen') === '1' && !document.fullscreenElement) {
        $(document).one('click', function() {
             if (systemRoot.requestFullscreen) systemRoot.requestFullscreen();
        });
    }

    // Block Esc key in Fullscreen
    $(document).on('keydown', function(e) {
        if (document.fullscreenElement && e.keyCode === 27) {
            e.preventDefault();
            $('#sukna-unlock-overlay').css('display', 'flex').hide().fadeIn(300);
            $('#sukna-unlock-pass').focus();
        }
    });

    $('#sukna-unlock-submit').on('click', function() {
        $.post(sukna_ajax.ajax_url, {
            action: 'sukna_verify_fullscreen_password',
            password: $('#sukna-unlock-pass').val(),
            nonce: sukna_ajax.nonce
        }, function(res) {
            if (res.success) {
                $('#sukna-unlock-overlay').fadeOut(300, function() {
                    if (document.exitFullscreen) document.exitFullscreen();
                    $('#sukna-unlock-pass').val('');
                    localStorage.removeItem('sukna_fullscreen');
                });
            } else {
                alert('كلمة المرور غير صحيحة');
            }
        });
    });

    // User Save
    $('#sukna-user-form').on('submit', function(e) {
        e.preventDefault();
        const action = $('#user-id').val() ? 'sukna_save_user' : 'sukna_add_user';
        $.post(sukna_ajax.ajax_url, $(this).serialize() + '&action=' + action + '&nonce=' + sukna_ajax.nonce, function(res) {
            if (res.success) location.reload();
            else alert(res.data || 'Error');
        });
    });

    $(document).on('click', '.sukna-delete-user', function(e) {
        if (!confirm('حذف؟')) return;
        $.post(sukna_ajax.ajax_url, { action: 'sukna_delete_user', id: $(this).data('id'), nonce: sukna_ajax.nonce }, () => location.reload());
    });

    // Logout
    $('#sukna-logout-btn, #sukna-mobile-logout-btn').on('click', function() {
        $.post(sukna_ajax.ajax_url, { action: 'sukna_logout', nonce: sukna_ajax.nonce }, () => location.reload());
    });

    // Image Upload Handler
    $(document).on('click', '.sukna-upload-btn', function(e) {
        e.preventDefault();
        const btn = $(this);
        const frame = wp.media({ title: 'اختر صورة', multiple: false }).open();
        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            const target = btn.parent().find('input[type="text"]');
            if (target.length) {
                target.val(attachment.url);
                // Specifically for company logo
                if (target.attr('id') === 'company-logo-url') {
                    $('#logo-preview').attr('src', attachment.url);
                    $('#logo-preview-container').fadeIn();
                }
            }
        });
    });

    // PWA Install Prompt Logic
    let deferredPrompt;
    const installBanner = $('#sukna-install-banner');
    const iosInstallBanner = $('#sukna-ios-install-banner');
    const installBtn = $('#sukna-install-btn');

    // Detect iOS
    const isIos = () => {
        const userAgent = window.navigator.userAgent.toLowerCase();
        return /iphone|ipad|ipod/.test(userAgent);
    };

    // Detect if already installed (standalone mode)
    const isInStandaloneMode = () => ('standalone' in window.navigator) && (window.navigator.standalone);

    if (isIos() && !isInStandaloneMode()) {
        iosInstallBanner.fadeIn(300);
        setTimeout(() => iosInstallBanner.fadeOut(500), 15000);
    }

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        installBanner.fadeIn(300);
    });

    installBtn.on('click', (e) => {
        installBanner.fadeOut(200);
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then((choiceResult) => {
            deferredPrompt = null;
        });
    });

    window.addEventListener('appinstalled', (evt) => {
        installBanner.fadeOut(200);
    });
});
