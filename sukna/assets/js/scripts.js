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

    const geoData = sukna_ajax.geo_data;

    function populateStates(countryCode, stateSelector, selectedState = '') {
        const states = geoData[countryCode] ? geoData[countryCode].states : {};
        let html = '<option value="">الولاية / الإمارة</option>';
        for (const [code, data] of Object.entries(states)) {
            html += `<option value="${code}" ${code === selectedState ? 'selected' : ''}>${data.name}</option>`;
        }
        $(stateSelector).html(html);
    }

    $('#filter-country').on('change', function() {
        populateStates($(this).val(), '#filter-state');
    });

    $('#prop-country').on('change', function() {
        populateStates($(this).val(), '#prop-state');
    });

    $(document).on('click', '.sukna-edit-property', function() {
        const p = $(this).data('property');
        $('#prop-id').val(p.id);
        $('#prop-name').val(p.name);
        $('#prop-type').val(p.property_type || 'owned');
        $('#prop-country').val(p.country).trigger('change');
        setTimeout(() => {
            $('#prop-state').val(p.state_emirate);
        }, 100);
        $('#prop-city').val(p.city);
        $('#prop-total-rooms').val(p.total_rooms);
        $('#prop-valuation').val(p.valuation);
        $('#prop-base-lease').val(p.base_lease_value);
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
        $('#sukna-room-quick-add')[0].reset();
        $('#sukna-contract-form').hide();
        loadRooms(propId);
        $('#sukna-room-modal').css('display', 'flex');
    });

    $('#sukna-room-quick-add').on('submit', function(e) {
        e.preventDefault();
        const propId = $('#room-property-id').val();
        $.post(sukna_ajax.ajax_url, $(this).serialize() + '&action=sukna_save_room&nonce=' + sukna_ajax.nonce, function(res) {
            if (res.success) {
                $('#sukna-room-quick-add')[0].reset();
                loadRooms(propId);
            }
        });
    });

    function loadRooms(propId) {
        $.post(sukna_ajax.ajax_url, { action: 'sukna_get_rooms', property_id: propId, nonce: sukna_ajax.nonce }, function(res) {
            if (res.success) {
                let html = '';
                res.data.forEach(r => {
                    const isRented = r.status === 'rented';
                    html += `<div class="sukna-room-block ${isRented ? 'is-rented' : 'is-available'}"
                                  data-room='${JSON.stringify(r)}'
                                  style="width:100%; aspect-ratio:1/1; border:2px solid ${isRented ? '#ef4444' : '#059669'};
                                         background:${isRented ? '#ef4444' : 'transparent'}; cursor:pointer;
                                         display:flex; flex-direction:column; align-items:center; justify-content:center;
                                         border-radius:8px; transition:0.2s;">
                                <span style="font-weight:800; font-size:1.2rem; color:${isRented ? '#fff' : '#059669'};">${r.room_number}</span>
                                <small style="color:${isRented ? '#fff' : '#64748b'}; font-size:0.6rem;">${isRented ? 'مؤجر' : 'متاح'}</small>
                             </div>`;
                });
                $('#sukna-rooms-grid').html(html || '<p>لا توجد وحدات</p>');
            }
        });
    }

    $(document).on('click', '.sukna-room-block', function() {
        const r = $(this).data('room');
        $('.sukna-room-block').css('transform', 'scale(1)');
        $(this).css('transform', 'scale(1.05)');

        $('#detail-room-number').text(r.room_number);
        $('#detail-room-status').text(r.status === 'rented' ? 'مؤجر' : 'متاح');
        $('#detail-room-price').text(r.rental_price);

        if (r.status === 'rented') {
            $('#detail-tenant-info').fadeIn();
            $('#detail-tenant-name').text(r.tenant_name || '-');
            $('#detail-rental-date').text(r.rental_start_date || '-');
            $('#detail-contract-btn').hide();
        } else {
            $('#detail-tenant-info').hide();
            $('#detail-contract-btn').show().data('id', r.id);
        }

        $('#detail-delete-btn').data('id', r.id);
        $('#sukna-room-details').fadeIn();
        $('#sukna-contract-form').hide();
    });

    $(document).on('click', '#detail-contract-btn', function() {
        $('#contract-room-id').val($(this).data('id'));
        $('#sukna-contract-form').slideDown();
        $('#sukna-room-details').fadeOut();
    });

    $(document).on('click', '#detail-delete-btn', function() {
        if (!confirm('حذف الوحدة؟')) return;
        $.post(sukna_ajax.ajax_url, { action: 'sukna_delete_room', id: $(this).data('id'), nonce: sukna_ajax.nonce }, () => {
            $('#sukna-room-details').fadeOut();
            loadRooms($('#room-property-id').val());
        });
    });

    $(document).on('click', '.sukna-create-contract-btn', function() {
        $('#contract-room-id').val($(this).data('id'));
        $('#sukna-contract-form').slideDown();
    });

    $('#sukna-contract-form').on('submit', function(e) {
        e.preventDefault();
        // Basic validation: must have either a registered tenant or a guest name
        if (!$('#contract-tenant-id').val() && !$('#contract-guest-name').val()) {
            alert('يرجى اختيار مستأجر أو إدخال اسم الضيف');
            return;
        }

        $.post(sukna_ajax.ajax_url, $(this).serialize() + '&action=sukna_save_contract&nonce=' + sukna_ajax.nonce, function(res) {
            if (res.success) {
                alert('تم تفعيل العقد بنجاح');
                $('#sukna-contract-form').slideUp();
                loadRooms($('#room-property-id').val());
            }
        });
    });

    $(document).on('click', '.sukna-delete-room', function() {
        if (!confirm('حذف الغرفة؟')) return;
        $.post(sukna_ajax.ajax_url, { action: 'sukna_delete_room', id: $(this).data('id'), nonce: sukna_ajax.nonce }, () => loadRooms($('#room-property-id').val()));
    });

    $('.close-room-modal').on('click', function() { $('#sukna-room-modal').hide(); });

    // --- Expense Management ---

    $(document).on('click', '.sukna-record-expense-btn', function() {
        $('#expense-property-id').val($(this).data('id'));
        $('#sukna-expense-modal').css('display', 'flex');
    });

    $('.close-expense-modal').on('click', function() { $('#sukna-expense-modal').hide(); });

    $('#sukna-expense-form').on('submit', function(e) {
        e.preventDefault();
        $.post(sukna_ajax.ajax_url, $(this).serialize() + '&action=sukna_record_expense&nonce=' + sukna_ajax.nonce, function(res) {
            if (res.success) {
                alert('تم تسجيل المصروفات');
                $('#sukna-expense-form')[0].reset();
                location.reload();
            }
        });
    });

    // --- Revenue Distribution ---

    $(document).on('click', '.sukna-distribute-revenue-btn', function() {
        const id = $(this).data('id');
        const net = $(this).data('net');
        if (!confirm(`هل أنت متأكد من توزيع مبلغ ${net} EGP على كافة الشركاء؟`)) return;
        $.post(sukna_ajax.ajax_url, { action: 'sukna_distribute_revenue', id: id, net_profit: net, nonce: sukna_ajax.nonce }, function(res) {
            if (res.success) alert('تم توزيع الأرباح بنجاح');
        });
    });

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
        setTimeout(() => { window.location.reload(true); }, 500);
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
