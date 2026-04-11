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
            if (res.success) {
                alert('تم حفظ بيانات المستخدم بنجاح');
                location.reload();
            }
            else alert(res.data || 'حدث خطأ أثناء الحفظ');
        });
    });

    $(document).on('click', '.sukna-delete-user', function(e) {
        if (!confirm('حذف؟')) return;
        $.post(sukna_ajax.ajax_url, { action: 'sukna_delete_user', id: $(this).data('id'), nonce: sukna_ajax.nonce }, () => location.reload());
    });

    // --- Property Management Wizard ---
    let propCurrentStep = 1;

    function showPropStep(step) {
        $('.prop-wizard-step').hide();
        $(`#prop-step-${step}`).fadeIn(200);

        $('#prop-wizard-dots .dot').removeClass('active');
        $(`#prop-wizard-dots .dot[data-step="${step}"]`).addClass('active');

        $('#prop-prev').toggle(step > 1);
        $('#prop-next').toggle(step < 3);
        $('#prop-submit').toggle(step === 3);

        if (step === 3) updatePropSummary();
    }

    $('#prop-next').on('click', function() {
        if (validatePropStep(propCurrentStep)) {
            propCurrentStep++;
            showPropStep(propCurrentStep);
        }
    });

    $('#prop-prev').on('click', function() {
        propCurrentStep--;
        showPropStep(propCurrentStep);
    });

    function validatePropStep(step) {
        let valid = true;
        $(`#prop-step-${step} input[required], #prop-step-${step} select[required]`).each(function() {
            if (!$(this).val()) {
                alert('يرجى ملء الحقول المطلوبة');
                valid = false;
                return false;
            }
        });
        return valid;
    }

    function updatePropSummary() {
        const base = parseFloat($('#prop-base-value').val()) || 0;
        const gov = parseFloat($('#prop-gov-fees').val()) || 0;
        let setup = 0;
        $('input[name="setup_item_costs[]"]').each(function() {
            setup += parseFloat($(this).val()) || 0;
        });

        $('#sum-base-val').text(base.toLocaleString());
        $('#sum-setup-val').text((setup + gov).toLocaleString());
        $('#sum-total-val').text((base + setup + gov).toLocaleString());
    }

    $('#sukna-add-property-btn').on('click', function() {
        $('#sukna-property-form')[0].reset();
        $('#prop-id').val('');
        $('#setup-items-container').empty();
        $('#prop-modal-title').text('إضافة عقار جديد');
        propCurrentStep = 1;
        showPropStep(1);
        $('#sukna-property-modal').css('display', 'flex');
    });

    $('#add-setup-item-btn').on('click', function() {
        addSetupItemRow();
    });

    function addSetupItemRow(name = '', cost = '') {
        const row = $(`<div class="setup-item-row" style="display:flex; gap:10px; margin-bottom:10px;">
            <input type="text" name="setup_item_names[]" value="${name}" placeholder="اسم البند (مثال: أجهزة كهربائية)" style="flex:2;">
            <input type="number" step="0.01" name="setup_item_costs[]" value="${cost}" placeholder="التكلفة" style="flex:1;">
            <button type="button" class="remove-setup-item" style="background:#ef4444; border:none; border-radius:4px; padding:0 10px; color:#fff;">X</button>
        </div>`);
        $('#setup-items-container').append(row);
    }

    $(document).on('click', '.remove-setup-item', function() {
        $(this).closest('.setup-item-row').remove();
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
        $('#prop-subtype').val(p.property_subtype);
        $('#prop-floor').val(p.floor_number);
        $('#prop-apt-number').val(p.apartment_number);
        $('#prop-type').val(p.property_type || 'owned');
        $('#prop-country').val(p.country).trigger('change');
        setTimeout(() => {
            $('#prop-state').val(p.state_emirate);
        }, 100);
        $('#prop-city').val(p.city);
        $('#prop-total-rooms').val(p.total_rooms);
        $('#prop-expected-rent').val(p.expected_rent_per_room);
        $('#prop-base-value').val(p.base_value);
        $('#prop-gov-fees').val(p.gov_fees);
        $('#prop-contract-start').val(p.contract_start_date);
        $('#prop-invest-start').val(p.investment_start_date);
        $('#prop-duration').val(p.contract_duration);
        $('#prop-address').val(p.address);
        $('#prop-owner-id').val(p.owner_id);

        $('#setup-items-container').empty();
        $.post(sukna_ajax.ajax_url, { action: 'sukna_get_setup_items', id: p.id, nonce: sukna_ajax.nonce }, function(res) {
            if (res.success) {
                res.data.forEach(item => {
                    addSetupItemRow(item.item_name, item.item_cost);
                });
            }
        });

        $('#prop-modal-title').text('تعديل عقار');
        propCurrentStep = 1;
        showPropStep(1);
        $('#sukna-property-modal').css('display', 'flex');
    });

    $('.close-prop-modal').on('click', function() { $('#sukna-property-modal').hide(); });

    $('#sukna-property-form').on('submit', function(e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('جاري الحفظ...');

        $.post(sukna_ajax.ajax_url, $(this).serialize() + '&action=sukna_save_property&nonce=' + sukna_ajax.nonce, function(res) {
            if (res.success) {
                alert('تم حفظ بيانات العقار بنجاح');
                location.reload();
            } else {
                alert('فشل في حفظ البيانات: ' + (res.data || 'خطأ غير معروف'));
                $btn.prop('disabled', false).text('حفظ العقار');
            }
        });
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
                alert('تمت إضافة الوحدة بنجاح');
                $('#sukna-room-quick-add')[0].reset();
                loadRooms(propId);
            } else {
                alert('خطأ: ' + (res.data || 'فشل الحفظ'));
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
            $('#detail-terminate-btn').show().data('id', r.id);
            $('#detail-renew-btn').show().data('id', r.id);
        } else {
            $('#detail-tenant-info').hide();
            $('#detail-contract-btn').show().data('id', r.id);
            $('#detail-terminate-btn').hide();
            $('#detail-renew-btn').hide();
        }

        $('#detail-delete-btn').data('id', r.id);
        $('#sukna-room-details').fadeIn();
        $('#sukna-contract-form').hide();
    });

    $(document).on('click', '#detail-contract-btn, #detail-renew-btn', function() {
        $('#contract-room-id').val($(this).data('id'));
        $('#sukna-contract-form').slideDown();
        $('#sukna-room-details').fadeOut();
    });

    $(document).on('click', '#detail-terminate-btn', function() {
        const id = $(this).data('id');
        if (!confirm('هل أنت متأكد من إنهاء التعاقد وإخلاء الوحدة (Check-out)؟')) return;
        $.post(sukna_ajax.ajax_url, { action: 'sukna_terminate_contract', room_id: id, nonce: sukna_ajax.nonce }, function(res) {
            if (res.success) {
                alert('تم إنهاء التعاقد بنجاح.');
                loadRooms($('#room-property-id').val());
                $('#sukna-room-details').fadeOut();
            }
        });
    });

    $(document).on('click', '#detail-delete-btn', function() {
        if (!confirm('حذف الوحدة؟')) return;
        $.post(sukna_ajax.ajax_url, { action: 'sukna_delete_room', id: $(this).data('id'), nonce: sukna_ajax.nonce }, () => {
            $('#sukna-room-details').fadeOut();
            loadRooms($('#room-property-id').val());
        });
    });

    $('#sukna-reset-rooms-btn').on('click', function() {
        if (!confirm('سيتم مسح كافة المستأجرين وإعادة كافة الوحدات كشاغرة لهذا العقار. هل أنت متأكد؟')) return;
        const propId = $('#room-property-id').val();
        $.post(sukna_ajax.ajax_url, { action: 'sukna_reset_property_rooms', id: propId, nonce: sukna_ajax.nonce }, function(res) {
            if (res.success) {
                alert('تمت عملية إعادة التعيين بنجاح لبداية دورة إيجارية جديدة.');
                loadRooms(propId);
            }
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

    // --- PDF Report Export ---

    $(document).on('click', '.sukna-export-pdf-btn', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const $btn = $(this);
        const originalHtml = $btn.html();

        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> جاري التجهيز...');

        $.post(sukna_ajax.ajax_url, { action: 'sukna_get_report_html', id: id, nonce: sukna_ajax.nonce }, function(res) {
            if (res.success) {
                const element = document.createElement('div');
                element.innerHTML = res.data;
                document.body.appendChild(element);

                const opt = {
                    margin: 0,
                    filename: `Sukna_Report_${name}.pdf`,
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2, useCORS: true },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
                };

                html2pdf().set(opt).from(element).save().then(() => {
                    document.body.removeChild(element);
                    $btn.prop('disabled', false).html(originalHtml);
                });
            } else {
                alert('فشل تجهيز التقرير');
                $btn.prop('disabled', false).html(originalHtml);
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
        window.suknaInstallPrompt = e;
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

    // Mobile Sidebar Toggle
    $('#sukna-toggle-sidebar').on('click', function() {
        $('#sukna-sidebar-main').toggleClass('is-open');
    });

    // Close sidebar when clicking outside on mobile
    $(document).on('click', function(e) {
        if ($(window).width() <= 1024) {
            const sidebar = $('#sukna-sidebar-main');
            const toggle = $('#sukna-toggle-sidebar');
            if (!sidebar.is(e.target) && sidebar.has(e.target).length === 0 && !toggle.is(e.target) && toggle.has(e.target).length === 0) {
                sidebar.removeClass('is-open');
            }
        }
    });
});
