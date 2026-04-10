jQuery(document).ready(function($) {
    // Shared State
    let cart = [];
    let salesMode = 'manual';
    let customerData = { is_quick: true };
    let html5QrCode;
    let formQrCode;
    let lastScannedCode = '';
    let lastScanTime = 0;
    const SCAN_COOLDOWN = 5000; // 5 seconds

    // --- Core Operations ---

    function addProductToCart(product_id, serial = '', quantity = 1) {
        const option = $(`#ac-is-sale-product option[value="${product_id}"]`);
        if (!option.length) return false;

        const product_name = option.data('name');
        const price = parseFloat(option.data('price'));
        const stock = parseInt(option.data('stock'));

        if (quantity > stock) {
            alert('الكمية المطلوبة أكبر من المتوفر');
            return false;
        }

        cart.push({
            product_id: product_id,
            product_name: product_name,
            quantity: quantity,
            serial_number: serial,
            unit_price: price,
            total_price: (price * quantity).toFixed(2)
        });

        renderCart();
        return true;
    }

    function renderCart() {
        const body = $('#ac-is-cart-body');
        body.empty();
        let total = 0;

        if (cart.length === 0) {
            body.append('<tr class="empty-cart"><td colspan="3" style="text-align:center; padding:40px; color:#94a3b8;">سلة المشتريات فارغة</td></tr>');
        } else {
            cart.forEach((item, index) => {
                total += parseFloat(item.total_price);
                body.append(`
                    <tr>
                        <td style="padding:12px;"><strong>${item.product_name}</strong><br><small>SN: ${item.serial_number || '-'}</small></td>
                        <td style="padding:12px;">x${item.quantity}</td>
                        <td style="padding:12px; text-align:left;">${item.total_price} EGP <button type="button" class="ac-is-remove-item" data-index="${index}" style="margin-right:10px; background:none; border:none; color:red; cursor:pointer;"><span class="dashicons dashicons-no-alt"></span></button></td>
                    </tr>
                `);
            });
        }
        $('#ac-is-cart-total').text(total.toFixed(2));
    }

    $(document).on('click', '.ac-is-remove-item', function() {
        cart.splice($(this).data('index'), 1);
        renderCart();
    });

    $('#ac-is-add-to-list').on('click', function() {
        const pid = $('#ac-is-sale-product').val();
        if (!pid) return;
        if (addProductToCart(pid, $('#ac-is-sale-serial').val(), parseInt($('#ac-is-sale-qty').val()))) {
            $('#ac-is-sale-product').val('').trigger('change');
            $('#ac-is-sale-serial').val('');
            $('#ac-is-sale-qty').val(1);
            $('#ac-is-sale-product-search').val('');
        }
    });

    // --- Mode Selection & Scanning ---

    $('.ac-is-mode-box').on('click', function() {
        salesMode = $(this).data('mode');
        $('.ac-is-mode-box').removeClass('active');
        $(this).addClass('active');

        if (salesMode === 'scan') {
            $('#ac-is-reader-container').show();
            $('#ac-is-manual-entry-area').hide();
            startScanner();
        } else {
            $('#ac-is-reader-container').hide();
            $('#ac-is-manual-entry-area').show();
            if (html5QrCode) html5QrCode.stop().catch(err => console.error(err));
        }
    });

    function startScanner() {
        if (!html5QrCode) html5QrCode = new Html5Qrcode("ac-is-reader");
        html5QrCode.start({ facingMode: "environment" }, { fps: 30, qrbox: { width: 250, height: 150 } }, (text) => {
            if (processScannedBarcode(text)) {
                showScanConfirmation();
                if (window.navigator.vibrate) window.navigator.vibrate(100);
            }
        });
    }

    function processScannedBarcode(query) {
        const now = Date.now();
        if (query === lastScannedCode && (now - lastScanTime) < SCAN_COOLDOWN) {
            console.log('Barcode cooldown active for:', query);
            return false;
        }

        // Check if it's an invoice barcode (INV-XXXXXXXX)
        if (query.startsWith('INV-')) {
            const invoiceId = parseInt(query.replace('INV-', ''));
            if (invoiceId) {
                window.location.href = window.location.href.split('&')[0] + '&ac_view=invoice&invoice_id=' + invoiceId;
                return true;
            }
        }

        let found = false;
        $('#ac-is-sale-product option').each(function() {
            if ($(this).data('barcode') == query || $(this).data('serial') == query) {
                if (addProductToCart($(this).val(), $(this).data('serial'), 1)) {
                    lastScannedCode = query;
                    lastScanTime = now;
                    found = true;
                }
                return false;
            }
        });
        return found;
    }

    function showScanConfirmation() {
        const overlay = $('#ac-is-scan-conf-overlay');
        overlay.css('display', 'flex').hide().fadeIn(200);
        setTimeout(() => overlay.fadeOut(300), 1200);
    }

    // --- Real-time Customer Recognition ---

    let customerTimeout;
    $('#ac-is-customer-phone').on('input', function() {
        clearTimeout(customerTimeout);
        const phone = $(this).val().trim();
        if (phone.length < 5) {
            $('#customer-details-fields').slideUp();
            return;
        }

        customerTimeout = setTimeout(() => {
            $.post(ac_is_ajax.ajax_url, {
                action: 'ac_is_get_customer',
                phone: phone,
                nonce: ac_is_ajax.nonce
            }, function(res) {
                if (res.success) {
                    const c = res.data;
                    $('#ac-is-customer-name').val(c.name);
                    $('#ac-is-customer-address').val(c.address);
                    $('#ac-is-customer-email').val(c.email);
                    customerData = c;
                    customerData.is_quick = false;
                    $('#ac-is-customer-phone').css('border-color', '#059669');
                } else {
                    $('#ac-is-customer-name').val('');
                    $('#ac-is-customer-address').val('');
                    $('#ac-is-customer-email').val('');
                    customerData = { is_quick: false, phone: phone };
                    $('#ac-is-customer-phone').css('border-color', 'var(--ac-primary)');
                }
                $('#customer-details-fields').slideDown();
            });
        }, 400);
    });

    // --- Finalize Sale ---

    $('#ac-is-finalize-sale').on('click', function() {
        if (cart.length === 0) { alert('يرجى إضافة منتجات أولاً'); return; }

        const data = {
            action: 'ac_is_multi_sale',
            nonce: ac_is_ajax.nonce,
            items: cart,
            total_amount: $('#ac-is-cart-total').text(),
            customer_name: $('#ac-is-customer-name').val() || 'عميل سريع',
            customer_phone: $('#ac-is-customer-phone').val() || '-',
            customer_address: $('#ac-is-customer-address').val() || '',
            customer_email: $('#ac-is-customer-email').val() || '',
            send_email: $('#ac-is-send-email').is(':checked') ? 1 : 0,
            warranty_years: $('#ac-is-sale-warranty').val() || 0,
            offline_id: Date.now()
        };

        if (navigator.onLine) {
            $.post(ac_is_ajax.ajax_url, data, function(res) {
                if (res.success) {
                    window.location.href = window.location.href.split('&')[0] + '&ac_view=invoice&invoice_id=' + res.data.invoice_id + '&autoprint=1';
                }
            });
        } else {
            const pending = JSON.parse(localStorage.getItem('ac_is_pending_sales') || '[]');
            pending.push(data);
            localStorage.setItem('ac_is_pending_sales', JSON.stringify(pending));
            alert('تم حفظ العملية محلياً (أوفلاين). سيتم المزامنة تلقائياً عند عودة الإنترنت.');
            cart = []; renderCart();
        }
    });

    // Auto-Sync Logic
    setInterval(() => {
        if (navigator.onLine) {
            const pending = JSON.parse(localStorage.getItem('ac_is_pending_sales') || '[]');
            if (pending.length > 0) {
                const item = pending.shift();
                $.post(ac_is_ajax.ajax_url, item, function(res) {
                    if (res.success) {
                        localStorage.setItem('ac_is_pending_sales', JSON.stringify(pending));
                        console.log('Synced offline sale:', item.offline_id);
                    }
                });
            }
        }
    }, 1000 * 60 * 5); // 5 minutes

    // --- Infrastructure & Other Logic ---

    const syncLoader = $('#ac-is-sync-loader');
    function showSync(text = 'جارٍ تحميل البيانات...') { syncLoader.find('.loader-text').text(text); syncLoader.fadeIn(200); }
    function hideSync() { syncLoader.find('.loader-text').text('تم التحديث بنجاح'); setTimeout(() => syncLoader.fadeOut(400), 1000); }

    $(document).ajaxStart(function() { showSync(); });
    $(document).ajaxStop(function() { hideSync(); });

    $('#ac-is-refresh-btn, #ac-is-mobile-refresh-btn').on('click', function() {
        showSync('جاري مسح التخزين المؤقت وتحديث البيانات...');
        if (window.sessionStorage) window.sessionStorage.clear();
        setTimeout(() => { window.location.reload(true); }, 500);
    });

    const systemRoot = document.getElementById('ac-is-system-root');

    $('#ac-is-fullscreen-btn').on('click', function() {
        if (!document.fullscreenElement) {
            if (systemRoot.requestFullscreen) systemRoot.requestFullscreen();
            else if (systemRoot.webkitRequestFullscreen) systemRoot.webkitRequestFullscreen();
            localStorage.setItem('ac_is_fullscreen', '1');
        } else {
            $('#ac-is-unlock-overlay').css('display', 'flex').hide().fadeIn(300);
            $('#ac-is-unlock-pass').focus();
        }
    });

    // Auto-restore fullscreen state
    if (localStorage.getItem('ac_is_fullscreen') === '1' && !document.fullscreenElement) {
        // Must be triggered by user interaction, so we might need a workaround or user to click once
        $(document).one('click', function() {
             if (systemRoot.requestFullscreen) systemRoot.requestFullscreen();
        });
    }

    // Block Esc key in Fullscreen
    $(document).on('keydown', function(e) {
        if (document.fullscreenElement && e.keyCode === 27) {
            e.preventDefault();
            $('#ac-is-unlock-overlay').css('display', 'flex').hide().fadeIn(300);
            $('#ac-is-unlock-pass').focus();
        }
    });

    $('#ac-is-mobile-quick-scan-btn').on('click', function() {
        window.location.href = window.location.href.split('&')[0] + '&ac_view=sales&mode=scan';
    });

    $('#ac-is-unlock-submit').on('click', function() {
        $.post(ac_is_ajax.ajax_url, {
            action: 'ac_is_verify_fullscreen_password',
            password: $('#ac-is-unlock-pass').val(),
            nonce: ac_is_ajax.nonce
        }, function(res) {
            if (res.success) {
                $('#ac-is-unlock-overlay').fadeOut(300, function() {
                    if (document.exitFullscreen) document.exitFullscreen();
                    $('#ac-is-unlock-pass').val('');
                    localStorage.removeItem('ac_is_fullscreen');
                });
            } else {
                alert('كلمة المرور غير صحيحة');
            }
        });
    });

    // Product calculations (Enhanced)
    $('#original-price, #discount').on('input', function() {
        const original = parseFloat($('#original-price').val()) || 0;
        const discount = parseFloat($('#discount').val()) || 0;
        $('#final-price').val((original - (original * (discount/100))).toFixed(2));
    });

    // Barcode Sticker Printing (Professional)
    $(document).on('click', '.ac-is-print-barcode', function(e) {
        e.preventDefault();
        const barcode = $(this).data('barcode');
        const name = $(this).data('name');
        const serial = $(this).data('serial');
        if (!barcode) { alert('لا يوجد باركود لهذا المنتج'); return; }

        $('#print-product-name').text(name + (serial ? ' (' + serial + ')' : ''));
        JsBarcode("#print-barcode-svg", barcode, {
            format: "CODE128",
            width: 2,
            height: 60,
            displayValue: true,
            fontSize: 14
        });

        $('body').addClass('ac-is-printing-sticker');
        window.print();
        setTimeout(() => $('body').removeClass('ac-is-printing-sticker'), 1000);
    });

    // Barcode Download (High Resolution PNG)
    $(document).on('click', '.ac-is-download-barcode', function(e) {
        e.preventDefault();
        const barcode = $(this).data('barcode');
        const name = $(this).data('name');
        if (!barcode) return;

        const canvas = document.createElement('canvas');
        JsBarcode(canvas, barcode, {
            format: "CODE128",
            width: 4,
            height: 120,
            displayValue: true,
            fontSize: 20
        });

        const link = document.createElement('a');
        link.download = `barcode-${name}-${barcode}.png`;
        link.href = canvas.toDataURL("image/png");
        link.click();
    });

    // Bulk Barcode PDF Export
    $('#ac-is-bulk-barcode-pdf').on('click', function() {
        const container = $('<div style="display:grid; grid-template-columns: repeat(3, 1fr); gap: 10mm; padding: 10mm;"></div>');

        $('#ac-is-inventory-table-body tr').each(function() {
            const barcode = $(this).find('.ac-is-print-barcode').data('barcode');
            const name = $(this).find('.ac-is-print-barcode').data('name');
            if (!barcode) return;

            const sticker = $(`
                <div style="border:1px dashed #ccc; padding:5mm; text-align:center; break-inside:avoid; width:50mm; height:30mm; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                    <div style="font-size:8pt; font-weight:bold; margin-bottom:1mm; overflow:hidden; white-space:nowrap; width:100%;">${name}</div>
                    <svg class="bulk-barcode-svg" data-code="${barcode}"></svg>
                </div>
            `);
            container.append(sticker);
        });

        // We need to append to body temporarily to render SVGs via JsBarcode
        $('body').append(container);
        container.find('.bulk-barcode-svg').each(function() {
            JsBarcode(this, $(this).data('code'), { format: "CODE128", width: 1.2, height: 35, displayValue: true, fontSize: 10 });
        });

        const opt = {
            margin: 0,
            filename: 'all-barcodes.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        html2pdf().set(opt).from(container[0]).save().then(() => {
            container.remove();
        });
    });

    // Product Barcode Generation (Enhanced)
    function generateBarcodeImage(value) {
        if (!value) return;

        $('#barcode-canvas-container').empty().append('<canvas id="barcode-canvas"></canvas>');
        JsBarcode("#barcode-canvas", value, {
            format: "CODE128",
            width: 3,
            height: 100,
            displayValue: true,
            fontSize: 20
        });
        $('#barcode-image-preview').fadeIn();
    }

    $('#ac-is-barcode-input').on('input', function() {
        generateBarcodeImage($(this).val());
    });

    if ($('#ac-is-barcode-input').val()) {
        generateBarcodeImage($('#ac-is-barcode-input').val());
    }

    $('#generate-barcode').on('click', function() {
        const randomBarcode = 'AC-' + Math.floor(Math.random() * 100000000);
        $('#ac-is-barcode-input').val(randomBarcode).trigger('input');
        if (!$('#ac-is-serial-input').val()) {
            $('#ac-is-serial-input').val(randomBarcode);
        }
    });

    // Product Save
    $('#ac-is-product-form').on('submit', function(e) {
        e.preventDefault();
        $.post(ac_is_ajax.ajax_url, $(this).serialize() + '&action=ac_is_save_product&nonce=' + ac_is_ajax.nonce, function(res) {
            if (res.success) window.location.href = '?ac_view=inventory';
        });
    });

    $('#scan-factory-barcode, #ac-is-inventory-add-scan-btn').on('click', function() {
        $('#ac-is-form-scanner-overlay').fadeIn(300);
        if (!formQrCode) formQrCode = new Html5Qrcode("ac-is-form-reader");
        formQrCode.start({ facingMode: "environment" }, { fps: 20, qrbox: { width: 250, height: 150 } }, (text) => {
            const isAddMode = $(this).attr('id') === 'ac-is-inventory-add-scan-btn';

            if (isAddMode) {
                $.post(ac_is_ajax.ajax_url, { action: 'ac_is_recognize_product', barcode: text, nonce: ac_is_ajax.nonce }, function(res) {
                    if (res.success) {
                         window.location.href = '?ac_view=edit-product&id=' + res.data.id;
                    } else {
                         window.location.href = '?ac_view=add-product&factory_barcode=' + text;
                    }
                });
            } else {
                $('#ac-is-factory-barcode-input').val(text);
                recognizeProductByBarcode(text);
            }
            stopFormScanner();
        }).catch(err => console.error(err));
    });

    // Bulk Scan Handler
    $('#ac-is-inventory-bulk-scan-btn').on('click', function() {
        $('#ac-is-form-scanner-overlay').fadeIn(300);
        if (!formQrCode) formQrCode = new Html5Qrcode("ac-is-form-reader");
        formQrCode.start({ facingMode: "environment" }, { fps: 15, qrbox: { width: 250, height: 150 } }, (text) => {
            if (text !== lastScannedCode) {
                 lastScannedCode = text;
                 // Simple bulk add logic - open in new tab or store in queue?
                 // For now, let's just show a notification and save to a local queue
                 console.log('Bulk Scanned:', text);
                 if(window.navigator.vibrate) window.navigator.vibrate(100);
                 alert('تم مسح: ' + text);
            }
        }).catch(err => console.error(err));
    });

    function stopFormScanner() {
        if (formQrCode) formQrCode.stop().then(() => {
            $('#ac-is-form-scanner-overlay').fadeOut(200);
        });
    }

    $('#close-form-scanner').on('click', stopFormScanner);

    function recognizeProductByBarcode(code) {
        $.post(ac_is_ajax.ajax_url, {
            action: 'ac_is_recognize_product',
            barcode: code,
            nonce: ac_is_ajax.nonce
        }, function(res) {
            if (res.success) {
                const p = res.data;
                // Fill form
                $('input[name="name"]').val(p.name);
                $('#ac-is-category-select').val(p.category).trigger('change');
                $('#ac-is-brand-select').val(p.brand_id);
                $('input[name="model_number"]').val(p.model_number);
                $('input[name="purchase_cost"]').val(p.purchase_cost);
                $('input[name="original_price"]').val(p.original_price);
                $('input[name="discount"]').val(p.discount);
                $('input[name="final_price"]').val(p.final_price);
                $('#ac-is-barcode-input').val(p.barcode);
                $('#ac-is-factory-barcode-input').val(p.factory_barcode);
                $('input[name="id"]').val(p.id);

                showScanConfirmation();
            }
        });
    }

    $(document).on('click', '.ac-is-delete-product', function(e) {
        if (!confirm('حذف؟')) return;
        $.post(ac_is_ajax.ajax_url, { action: 'ac_is_delete_product', id: $(this).data('id'), nonce: ac_is_ajax.nonce }, () => location.reload());
    });

    // Image Upload Handler
    $(document).on('click', '.ac-is-upload-btn', function(e) {
        e.preventDefault();
        const btn = $(this);
        const frame = wp.media({ title: 'اختر صورة', multiple: false }).open();
        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            const target = btn.parent().find('input[type="text"]');
            if (target.length) {
                target.val(attachment.url);
            }
        });
    });

    // Logout
    $('#ac-is-logout-btn, #ac-is-mobile-logout-btn').on('click', function() {
        $.post(ac_is_ajax.ajax_url, { action: 'ac_is_logout', nonce: ac_is_ajax.nonce }, () => location.reload());
    });

    // Product Search
    $('#ac-is-sale-product-search').on('input', function() {
        const query = $(this).val().toLowerCase();
        if (query.length < 2) return;
        $('#ac-is-sale-product option').each(function() {
            const barcode = String($(this).data('barcode')).toLowerCase();
            const name = $(this).text().toLowerCase();
            if (barcode == query || name.includes(query)) {
                $('#ac-is-sale-product').val($(this).val()).trigger('change');
                return false;
            }
        });
    });

    // Handle mode from URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('ac_view') === 'sales' && urlParams.get('mode') === 'scan') {
        $('.ac-is-mode-box[data-mode="scan"]').trigger('click');
    }

    if (window.location.search.indexOf('autoprint=1') > -1) {
        // Prevent double printing via a session flag
        if (!sessionStorage.getItem('is_printed_' + window.location.search)) {
            sessionStorage.setItem('is_printed_' + window.location.search, '1');
            setTimeout(function() { window.print(); }, 1000);
        }
    }

    // PWA Install Prompt Logic
    let deferredPrompt;
    const installBanner = $('#ac-is-install-banner');
    const iosInstallBanner = $('#ac-is-ios-install-banner');
    const installBtn = $('#ac-is-install-btn');

    // Detect iOS
    const isIos = () => {
        const userAgent = window.navigator.userAgent.toLowerCase();
        return /iphone|ipad|ipod/.test(userAgent);
    };

    // Detect if already installed (standalone mode)
    const isInStandaloneMode = () => ('standalone' in window.navigator) && (window.navigator.standalone);

    if (isIos() && !isInStandaloneMode()) {
        iosInstallBanner.fadeIn(300);
        // Auto hide after 15 seconds to avoid permanent clutter
        setTimeout(() => iosInstallBanner.fadeOut(500), 15000);
    }

    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent Chrome 67 and earlier from automatically showing the prompt
        e.preventDefault();
        // Stash the event so it can be triggered later.
        deferredPrompt = e;
        // Update UI notify the user they can add to home screen
        installBanner.fadeIn(300);
    });

    installBtn.on('click', (e) => {
        // hide our install banner
        installBanner.fadeOut(200);
        // Show the prompt
        deferredPrompt.prompt();
        // Wait for the user to respond to the prompt
        deferredPrompt.userChoice.then((choiceResult) => {
            if (choiceResult.outcome === 'accepted') {
                console.log('User accepted the A2HS prompt');
            } else {
                console.log('User dismissed the A2HS prompt');
            }
            deferredPrompt = null;
        });
    });

    window.addEventListener('appinstalled', (evt) => {
        console.log('AC IS was installed.');
        installBanner.fadeOut(200);
    });

    // Live Sales Search
    let salesSearchTimeout;
    $('#ac-is-live-sales-search').on('input', function() {
        clearTimeout(salesSearchTimeout);
        const query = $(this).val().trim();
        if (query.length < 2) return;

        salesSearchTimeout = setTimeout(() => {
            $.post(ac_is_ajax.ajax_url, {
                action: 'ac_is_search_sales',
                query: query,
                nonce: ac_is_ajax.nonce
            }, function(res) {
                if (res.success) {
                    $('#ac-is-sales-table tbody').html(res.data.html);
                }
            });
        }, 300);
    });
});
