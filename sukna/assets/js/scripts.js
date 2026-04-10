jQuery(document).ready(function($) {
    // --- Auth Toggling & Multi-step Registration ---

    $('#switch-to-register').on('click', function() {
        $('#sukna-login-container').fadeOut(200, function() {
            $('#sukna-register-container').fadeIn(200);
        });
    });

    $('#switch-to-login').on('click', function() {
        $('#sukna-register-container').fadeOut(200, function() {
            $('#sukna-login-container').fadeIn(200);
        });
    });

    // Country Flag Toggling
    $('#login-country-code, #reg-country-code').on('change', function() {
        const flag = $(this).find(':selected').data('flag');
        const target = $(this).attr('id') === 'login-country-code' ? '#login-flag' : '#reg-flag';
        $(target).text(flag);
    });

    let currentStep = 1;
    const totalSteps = 4;

    $('#reg-next').on('click', function() {
        if (validateStep(currentStep)) {
            $(`#reg-step-${currentStep}`).hide();
            currentStep++;
            $(`#reg-step-${currentStep}`).fadeIn(300);
            updateRegButtons();
        }
    });

    $('#reg-prev').on('click', function() {
        $(`#reg-step-${currentStep}`).hide();
        currentStep--;
        $(`#reg-step-${currentStep}`).fadeIn(300);
        updateRegButtons();
    });

    function updateRegButtons() {
        $('#reg-prev').toggle(currentStep > 1);
        $('#reg-next').toggle(currentStep < totalSteps);
        $('#reg-submit').toggle(currentStep === totalSteps);
    }

    function validateStep(step) {
        let valid = true;
        $(`#reg-step-${step} input[required]`).each(function() {
            if (!$(this).val()) {
                alert('يرجى ملء الحقول المطلوبة');
                valid = false;
                return false;
            }
        });
        return valid;
    }

    // --- Authentication Actions ---

    $('#sukna-login-form').on('submit', function(e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        const phoneFull = $('#login-country-code').val() + $('#login-phone-body').val();
        $('#login-phone-full').val(phoneFull);

        $btn.prop('disabled', true).text('جاري الدخول...');
        $('#login-error').hide();

        $.post(sukna_ajax.ajax_url, $(this).serialize() + '&action=sukna_login&nonce=' + sukna_ajax.nonce, function(res) {
            if (res.success) {
                window.location.reload();
            } else {
                $btn.prop('disabled', false).text('تسجيل الدخول');
                $('#login-error').text(res.data.message || 'حدث خطأ').show();
            }
        });
    });

    $('#sukna-register-form').on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#reg-submit');
        const phoneFull = $('#reg-country-code').val() + $('#reg-phone-body').val();

        if ($('#reg-password').val().length < 8) {
            $('#reg-error').text('كلمة المرور يجب أن لا تقل عن 8 أحرف').show();
            return;
        }

        $btn.prop('disabled', true).text('جاري التسجيل...');
        $('#reg-error').hide();

        const formData = $(this).serializeArray();
        formData.push({ name: 'phone', value: phoneFull });
        formData.push({ name: 'action', value: 'sukna_register' });
        formData.push({ name: 'nonce', value: sukna_ajax.nonce });

        $.post(sukna_ajax.ajax_url, formData, function(res) {
            if (res.success) {
                window.location.reload();
            } else {
                $btn.prop('disabled', false).text('إتمام التسجيل');
                $('#reg-error').text(res.data.message || 'حدث خطأ').show();
            }
        });
    });

    // --- User Management ---

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

    // --- Property Management ---

    $('#sukna-add-property-btn').on('click', function() {
        $('#sukna-property-form')[0].reset();
        $('#prop-id').val('');
        $('#prop-modal-title').text('إضافة عقار جديد');
        $('#sukna-property-modal').css('display', 'flex');
    });

    $(document).on('click', '.sukna-edit-property', function() {
        const p = $(this).data('property');
        $('#prop-id').val(p.id);
        $('#prop-name').val(p.name);
        $('#prop-address').val(p.address);
        $('#prop-owner-id').val(p.owner_id);
        $('#prop-modal-title').text('تعديل عقار');
        $('#sukna-property-modal').css('display', 'flex');
    });

    $('.close-prop-modal').on('click', function() { $('#sukna-property-modal').hide(); });

    $('#sukna-property-form').on('submit', function(e) {
        e.preventDefault();
        $.post(sukna_ajax.ajax_url, $(this).serialize() + '&action=sukna_save_property&nonce=' + sukna_ajax.nonce, () => location.reload());
    });

    $(document).on('click', '.sukna-delete-property', function() {
        if (!confirm('حذف العقار وجميع غرفه؟')) return;
        $.post(sukna_ajax.ajax_url, { action: 'sukna_delete_property', id: $(this).data('id'), nonce: sukna_ajax.nonce }, () => location.reload());
    });

    // --- Room Management ---

    $(document).on('click', '.sukna-manage-rooms', function() {
        const propId = $(this).data('id');
        $('#room-property-id').val(propId);
        loadRooms(propId);
        $('#sukna-room-modal').css('display', 'flex');
    });

    function loadRooms(propId) {
        $.post(sukna_ajax.ajax_url, { action: 'sukna_get_rooms', property_id: propId, nonce: sukna_ajax.nonce }, function(res) {
            if (res.success) {
                let html = '';
                res.data.forEach(r => {
                    html += `<tr>
                        <td>${r.room_number}</td>
                        <td>${r.rental_price}</td>
                        <td><span class="sukna-capsule ${r.status === 'rented' ? 'capsule-danger' : 'capsule-accent'}">${r.status === 'rented' ? 'مؤجر' : 'متاح'}</span></td>
                        <td>${r.tenant_name || '-'}</td>
                        <td style="text-align:left;">
                            <button class="sukna-btn sukna-delete-room" data-id="${r.id}" style="padding:4px 8px; background:#333; border:none; color:#fff;"><span class="dashicons dashicons-trash"></span></button>
                        </td>
                    </tr>`;
                });
                $('#sukna-rooms-table-body').html(html || '<tr><td colspan="5" style="text-align:center;">لا توجد غرف مضافة</td></tr>');
            }
        });
    }

    $('#sukna-room-form').on('submit', function(e) {
        e.preventDefault();
        $.post(sukna_ajax.ajax_url, $(this).serialize() + '&action=sukna_save_room&nonce=' + sukna_ajax.nonce, function(res) {
            if (res.success) {
                $('#sukna-room-form')[0].reset();
                loadRooms($('#room-property-id').val());
            }
        });
    });

    $(document).on('click', '.sukna-delete-room', function() {
        if (!confirm('حذف الغرفة؟')) return;
        $.post(sukna_ajax.ajax_url, { action: 'sukna_delete_room', id: $(this).data('id'), nonce: sukna_ajax.nonce }, () => loadRooms($('#room-property-id').val()));
    });

    $('.close-room-modal').on('click', function() { $('#sukna-room-modal').hide(); });

    // --- Investor Management ---

    $(document).on('click', '.sukna-manage-investors', function() {
        const propId = $(this).data('id');
        $('#invest-property-id').val(propId);
        loadInvestments(propId);
        $('#sukna-investor-modal').css('display', 'flex');
    });

    function loadInvestments(propId) {
        $.post(sukna_ajax.ajax_url, { action: 'sukna_get_investments', property_id: propId, nonce: sukna_ajax.nonce }, function(res) {
            if (res.success) {
                let html = '<table class="sukna-table"><thead><tr><th>المستثمر</th><th>المبلغ</th><th>التاريخ</th></tr></thead><tbody>';
                res.data.forEach(i => {
                    html += `<tr>
                        <td><strong>${i.investor_name}</strong></td>
                        <td>${i.amount} EGP</td>
                        <td>${i.investment_date}</td>
                    </tr>`;
                });
                html += '</tbody></table>';
                $('#sukna-investments-list').html(res.data.length ? html : '<p style="text-align:center;">لا يوجد مستثمرون حالياً</p>');
            }
        });
    }

    $('.close-investor-modal').on('click', function() { $('#sukna-investor-modal').hide(); });

    $('#sukna-investment-form').on('submit', function(e) {
        e.preventDefault();
        const propId = $('#invest-property-id').val();
        $.post(sukna_ajax.ajax_url, $(this).serialize() + '&action=sukna_save_investment&nonce=' + sukna_ajax.nonce, function(res) {
            if (res.success) {
                alert('تمت إضافة الاستثمار بنجاح');
                $('#sukna-investment-form')[0].reset();
                loadInvestments(propId);
            }
        });
    });

    // --- Other Shared Utilities ---

    const syncLoader = $('#sukna-sync-loader');
    function showSync(text = 'جارٍ تحميل البيانات...') { syncLoader.find('.loader-text').text(text); syncLoader.fadeIn(200); }
    function hideSync() { syncLoader.find('.loader-text').text('تم التحديث بنجاح'); setTimeout(() => syncLoader.fadeOut(400), 1000); }

    $(document).ajaxStart(function() { showSync(); });
    $(document).ajaxStop(function() { hideSync(); });

    $('#sukna-refresh-btn, #sukna-mobile-refresh-btn').on('click', function() {
        showSync('جاري مسح التخزين المؤقت وتحديث البيانات...');
        localStorage.clear();
        sessionStorage.clear();
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

    $('#sukna-logout-btn, #sukna-mobile-logout-btn').on('click', function() {
        $.post(sukna_ajax.ajax_url, { action: 'sukna_logout', nonce: sukna_ajax.nonce }, () => location.reload());
    });

    $(document).on('click', '.sukna-upload-btn', function(e) {
        e.preventDefault();
        const btn = $(this);
        const frame = wp.media({ title: 'اختر صورة', multiple: false }).open();
        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            const target = btn.parent().find('input[type="text"]');
            if (target.length) {
                target.val(attachment.url);
                if (target.attr('id') === 'company-logo-url') {
                    $('#logo-preview').attr('src', attachment.url);
                    $('#logo-preview-container').fadeIn();
                }
            }
        });
    });

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        $('#sukna-install-banner').fadeIn(300);
    });

    // Tab switching for settings
    $('.sukna-tab-btn').on('click', function() {
        const tab = $(this).data('tab');
        $('.sukna-tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.sukna-tab-content').hide();
        $('#' + tab).fadeIn(200);
    });
});
