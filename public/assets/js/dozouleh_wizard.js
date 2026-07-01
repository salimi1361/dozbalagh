// ==========================================
// موتور ناوبری و مدیریت فرم چندمرحله‌ای دوزوله
// ==========================================

let mohammadWizardStep = 1;
const totalWizardSteps = 3;

window.navigateWizard = function(direction) {
    if (direction === 1) {
        if (mohammadWizardStep === 1) {
            let driverOpt = jQuery('#driver_id option:selected');
            let fleetOpt = jQuery('#fleet_id option:selected');
            
            let driverId = driverOpt.val();
            let fleetId = fleetOpt.val();
            
            if (!driverId || driverId === "") {
                Swal.fire({ icon: 'error', title: 'خطا', text: 'لطفاً راننده متقاضی را انتخاب کنید.' });
                return;
            }
            if (!fleetId || fleetId === "") {
                Swal.fire({ icon: 'error', title: 'خطا', text: 'لطفاً ناوگان ملکی را انتخاب کنید.' });
                return;
            }

            let requestType = jQuery('input[name="request_type"]:checked').val() || 'new';

            if (requestType === 'new') {
                // 🚀 استعلام زنده و آنی از دیتابیس سرور برای اطمینان ۱۰۰٪ (دژ امنیتی فول‌فولاد)
                let nextBtn = jQuery('#nextBtn');
                let originalText = nextBtn.text();
                nextBtn.prop('disabled', true).text('در حال استعلام سیستمی...');

                jQuery.ajax({
                    url: window.DozoulehConfig.checkFleetUrl,
                    type: 'POST',
                    data: {
                        _token: window.DozoulehConfig.csrfToken,
                        driver_id: driverId,
                        fleet_id: fleetId
                    },
                    success: function(response) {
                        if (response.success === false) {
                            // 🛑 سرور مچ راننده یا ناوگان را گرفت! ترمز کشیده شد.
                            Swal.fire({
                                icon: 'error',
                                title: 'عدم تایید سیستمی',
                                text: response.message, // این پیام از کنترلر می‌آید
                                confirmButtonText: 'متوجه شدم',
                                confirmButtonColor: '#1e293b'
                            });
                            nextBtn.prop('disabled', false).text(originalText);
                        } else {
                            // ✅ سرور تایید کرد که هیچ دوزوله فعالی ندارند، عبور به مرحله ۲ مجاز است
                            nextBtn.prop('disabled', false).text(originalText);
                            transitionToStep2();
                        }
                    },
                    error: function() {
                        Swal.fire({ icon: 'error', title: 'خطای سرور', text: 'ارتباط با سرور برای استعلام وضعیت برقرار نشد.' });
                        nextBtn.prop('disabled', false).text(originalText);
                    }
                });
                return; // توقف ناوبری تا زمان دریافت جواب از سرور
                
            } else if (requestType === 'renewal') {
                let prevDozouleh = jQuery('#previous_dozouleh_number').val().trim();
                if (!prevDozouleh || prevDozouleh === "" || prevDozouleh === "در حال خواندن...") {
                    Swal.fire({ icon: 'warning', title: 'نقص اطلاعات', text: 'لطفاً شماره دوزوله مرجع جهت تمدید را وارد کنید.' });
                    return;
                }
                let validFormatRegex = /^[a-zA-Z0-9-]{5,20}$/;
                if (!validFormatRegex.test(prevDozouleh)) {
                    Swal.fire({ icon: 'error', title: 'فرمت نامعتبر', text: 'شماره دوزوله باید حداقل ۵ کاراکتر و فقط شامل حروف انگلیسی و عدد باشد.' });
                    return;
                }
                transitionToStep2();
                return;
            }
        }
        
        if (mohammadWizardStep === 2) {
            let check = validateStep2();
            if (!check.status) {
                Swal.fire({ icon: 'error', title: 'نقص اطلاعات', text: check.message });
                return;
            }
            proceedToStep3(); 
            return;
        }
        
        if (mohammadWizardStep === 3) {
            jQuery('#dozoulehForm').find(':input').prop('disabled', false);
            jQuery('#dozoulehForm').submit();
            return;
        }
    }

    if (direction === -1 && mohammadWizardStep > 1) {
        jQuery(`#step-${mohammadWizardStep}`).addClass('hidden');
        mohammadWizardStep--;
        jQuery(`#step-${mohammadWizardStep}`).removeClass('hidden');
        updateWizardUI();

        if (mohammadWizardStep === 2) {
            validateStep2Directly();
        }
    }
};

// تابع انتقال به گام ۲ پس از تاییدیه سرور
function transitionToStep2() {
    jQuery('#step-1').addClass('hidden');
    mohammadWizardStep = 2;
    jQuery('#step-2').removeClass('hidden');
    updateWizardUI();
    validateStep2Directly();
}

// --------------------------------------------------------
// تابع بررسی و فعال‌سازی خودکار دکمه مرحله بعد در استپ ۲
// --------------------------------------------------------
function validateStep2Directly() {
    let isValid = true;
    
    let cargoType = jQuery('#main_cargo_type').val();
    let origin = jQuery('#loading_origin').val();
    let destination = jQuery('#loading_destination').val();
    let cits = jQuery('input[name="cits_code"]').val();

    if (!cargoType || !origin || !destination || !cits) {
        isValid = false;
    }
    
    let countriesCount = jQuery('.country-row').length;
    if (countriesCount === 0) {
        isValid = false;
    }

    jQuery('.country-row').each(function() {
        let cId = jQuery(this).find('.country-select').val();
        let pType = jQuery(this).find('.permit-select').val();
        if (!cId || !pType) {
            isValid = false;
        }
    });

    if (isValid) {
        jQuery('#nextBtn').prop('disabled', false).removeClass('opacity-50 cursor-not-allowed');
    } else {
        if (mohammadWizardStep === 2) {
            jQuery('#nextBtn').prop('disabled', true).addClass('opacity-50 cursor-not-allowed');
        }
    }
}

// --------------------------------------------------------
// اعتبارسنجی استپ ۲ (نسخه کادر ثابت بالا + مقاصد پایین)
// --------------------------------------------------------
function validateStep2() {
    let isValid = true;
    let errorMessage = "";

    let cargoType = jQuery('#main_cargo_type').val();
    let origin = jQuery('#loading_origin').val();
    let destination = jQuery('#loading_destination').val();
    let cits = jQuery('input[name="cits_code"]').val();

    if (!cargoType || !origin || !destination || !cits) {
        return { status: false, message: 'لطفاً اطلاعات ثابت سفر (نوع بار، مبدا، مقصد و کد CITS) را در کادر بالا کامل کنید.' };
    }

    let targetInputs = [
        { name: 'receipt_code', label: 'شماره فیش سازمان' },
        { name: 'receipt_amount', label: 'مبلغ واریزی' },
        { name: 'trip_code', label: 'کد سفر', checkJ: true },
        { name: 'cmr_date', label: 'تاریخ بارگیری CMR' }
    ];

    let fixedBox = jQuery('#fixed_documents_container');

    for (let inputObj of targetInputs) {
        let el = fixedBox.find(`input[name="${inputObj.name}"]`);
        if (el.length > 0) {
            let container = el.closest('.dynamic-doc');
            
            if (container.css('display') !== 'none' && el.prop('required') && (!el.val() || el.val() === "")) {
                return { status: false, message: `تکمیل فیلد "${inputObj.label}" الزامی است.` };
            }
            if (inputObj.checkJ && container.css('display') !== 'none' && el.prop('required') && !el.val().toLowerCase().startsWith('j')) {
                return { status: false, message: `کد سفر حتماً باید با حرف J شروع شود.` };
            }
        }
    }

    let targetFiles = [
        { name: 'receipt_file', label: 'تصویر فیش پرداختی' },
        { name: 'cmr_file', label: 'تصویر CMR' },
        { name: 'tir_file', label: 'تصویر کارنه تیر' },
        { name: 'declaration_file', label: 'تصویر اظهارنامه' }
    ];

    for (let fileObj of targetFiles) {
        let fileInput = fixedBox.find(`input[name="${fileObj.name}"]`);
        if (fileInput.length > 0) {
            let container = fileInput.closest('.dynamic-doc');
            
            if (container.css('display') !== 'none' && fileInput.prop('required') && (!fileInput[0].files || fileInput[0].files.length === 0)) {
                return { status: false, message: `آپلود مدرک "${fileObj.label}" الزامی است.` };
            }
        }
    }

    let countriesCount = jQuery('.country-row').length;
    if (countriesCount === 0) {
        return { status: false, message: 'حداقل یک کشور مقصد/عبوری باید انتخاب شود.' };
    }

    jQuery('.country-row').each(function(index) {
        let row = jQuery(this);
        let cId = row.find('.country-select').val();
        let pType = row.find('.permit-select').val();

        if (!cId || !pType) {
            isValid = false;
            errorMessage = `لطفاً کشور و نوع مجوز را در ردیف ${index + 1} از لیست مقاصد مشخص کنید.`;
            return false; 
        }
    });

    return { status: isValid, message: errorMessage };
}

// --------------------------------------------------------
// 🧠 موتور هوشمند داینامیک ادمین (فقط برای مدارک کادر ثابت)
// --------------------------------------------------------
function applyDynamicRules(cargoType) {
    let rules = window.DozoulehConfig && window.DozoulehConfig.cargoRules ? window.DozoulehConfig.cargoRules : null;
    if (!rules) return;

    let typeMap = { 'export': 'صادرات', 'import': 'واردات', 'transit': 'ترانزیت', 'صادرات': 'صادرات', 'واردات': 'واردات', 'ترانزیت': 'ترانزیت' };
    let mappedType = typeMap[cargoType] || cargoType;

    if (!rules[mappedType] || !rules[mappedType].fields_config) return;
    let config = rules[mappedType].fields_config;

    let targetFields = [
        'receipt_code', 'receipt_amount', 'receipt_file',
        'trip_code', 'cmr_date', 'cmr_file',
        'tir_carnet_number', 'tir_carnet_date', 'tir_file',
        'declaration_file'
    ];

    let parentBox = jQuery('#fixed_documents_container');

    targetFields.forEach(function(field) {
        let input = parentBox.find(`[name="${field}"]`);
        if (input.length === 0) return;

        let container = input.closest('.dynamic-doc');
        let star = container.find('.text-rose-500'); 
        let state = config[field] || 'hidden'; 

        if (state === 'hidden') {
            container.attr('style', 'display: none !important'); 
            input.prop('required', false).prop('disabled', true).val(''); 
        } 
        else if (state === 'required') {
            container.attr('style', 'display: flex !important');
            star.removeClass('hidden').show();
            input.prop('required', true).prop('disabled', false);
        } 
        else if (state === 'optional') {
            container.attr('style', 'display: flex !important');
            star.addClass('hidden').hide();
            input.prop('required', false).prop('disabled', false);
        }
    });
    
    validateStep2Directly();
}

// --------------------------------------------------------
// تولید فاکتور و تاییدیه برای گام ۳
// --------------------------------------------------------
function proceedToStep3() {
    let totalPrice = 0;
    let countriesHtml = "";

    let cargoType = jQuery('#main_cargo_type').val();
    let origin = jQuery('#loading_origin').val();
    let dest = jQuery('#loading_destination').val();
    let cits = jQuery('input[name="cits_code"]').val();

    let dOption = jQuery('#driver_id option:selected');
    if (dOption.val()) {
        jQuery('#s3_driver_name').text(dOption.attr('data-name-fa') || "مشخص نشده");
        jQuery('#s3_driver_national').text(dOption.attr('data-national') || "---");
        jQuery('#s3_driver_en').text(dOption.attr('data-name-en') || "---");
        jQuery('#s3_lbl_national').text(dOption.attr('data-national') || "---");
        jQuery('#s3_lbl_passport').text(dOption.attr('data-passport') || "---");
    }

    let fOption = jQuery('#fleet_id option:selected');
    if (fOption.val()) {
        jQuery('#s3_truck_type').text(fOption.attr('data-type') || "---");
        jQuery('#s3_smart_card').text(fOption.attr('data-smart') || "---");
        jQuery('#s3_horse').text(fOption.attr('data-horse') || "---");
        jQuery('#s3_trailer').text(fOption.attr('data-trailer') || "---");

        let plateStr = fOption.attr('data-plate') || '';
        if (plateStr.includes('-')) {
            let parts = plateStr.split('-');
            jQuery('#s3_plate_1').text(parts[0] || '--');
            jQuery('#s3_plate_2').text(parts[1] || '--');
            jQuery('#s3_plate_3').text(parts[2] || '---');
            jQuery('#s3_plate_4').text(parts[3] || '--');
        } else {
            jQuery('#s3_plate_3').text(plateStr || '---');
        }
    }

    jQuery('.country-row').each(function(index) {
        let row = jQuery(this);
        let countrySelected = row.find('.country-select option:selected');
        let countryName = countrySelected.text();
        let permitType = row.find('.permit-select').val();
        
        const permitLabels = {
            'bilateral': 'دوجانبه',
            'transit': 'ترانزیت',
            'bilateral_transit': 'دوجانبه ترانزیت',
            'third_country_transit': 'ترانزیت ثالث',
            'third_country': 'ثالث'
        };
        let readablePermit = permitLabels[permitType] || permitType;
        
        let countryPrice = Number(countrySelected.attr('data-price')) || 0; 
        totalPrice += countryPrice;

        countriesHtml += `
        <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm mb-3">
            <div class="p-3 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <span class="w-6 h-6 rounded-full bg-blue-500 text-white font-black flex justify-center items-center text-xs shadow-md">${index + 1}</span>
                    <div>
                        <h4 class="font-black text-slate-800 text-sm">${countryName}</h4>
                        <p class="text-[11px] text-slate-500 font-bold mt-0.5">نوع مجوز: ${readablePermit}</p>
                    </div>
                </div>
                <div class="text-left">
                    <span class="font-mono text-sm font-black text-blue-700">${countryPrice.toLocaleString()} <small class="font-sans text-[10px]">ریال</small></span>
                </div>
            </div>
        </div>`;
    });

    let summaryHeader = `
    <div class="mb-4 p-4 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-700 shadow-sm">
        <div class="flex flex-col md:flex-row gap-4 justify-between">
            <div><strong class="text-rose-600 block mb-1">مسیر سفر:</strong> از ${origin} ⬅️ به ${dest}</div>
            <div><strong class="text-rose-600 block mb-1">عملیات حمل:</strong> ${cargoType}</div>
            <div><strong class="text-rose-600 block mb-1">کد CITS:</strong> <span class="font-mono">${cits}</span></div>
        </div>
    </div>
    `;

    jQuery(`#step-1`).addClass('hidden');
    jQuery(`#step-2`).addClass('hidden');
    mohammadWizardStep = 3;
    jQuery(`#step-3`).removeClass('hidden');
    updateWizardUI();

    setTimeout(function() {
        jQuery('#summary-countries-list').html(summaryHeader + countriesHtml);
        renderWalletSection(totalPrice); 
    }, 50);
}

window.renderWalletSection = function(totalPrice) {
    let walletBalance = Number(window.DozoulehConfig.walletBalance) || 0;
    let container = jQuery('#wallet-checkout-container');
    
    let html = `
    <div class="bg-slate-800 rounded-3xl p-6 shadow-xl mb-4 flex justify-between items-center text-white">
        <div>
            <h4 class="text-slate-300 text-xs font-bold mb-1">موجودی کیف پول</h4>
            <p class="text-xl font-mono font-black">${walletBalance.toLocaleString()} ریال</p>
        </div>
        <div class="text-right">
            <span class="text-slate-400 text-xs font-bold mb-1">جمع کل قابل پرداخت:</span>
            <span class="text-2xl font-mono font-black text-emerald-400">${totalPrice.toLocaleString()} ریال</span>
        </div>
    </div>`;

    if (walletBalance >= totalPrice) {
        html += `<div class="bg-emerald-50 text-emerald-800 p-4 rounded-xl font-bold">✅ تاییدیه مالی صادر شد. موجودی کافی است.</div>`;
        jQuery('#nextBtn').prop('disabled', false).removeClass('opacity-50 cursor-not-allowed').text('تایید نهایی و صدور درخواست');
    } else {
        let deficit = totalPrice - walletBalance;
        html += `
            <div class="bg-rose-50 text-rose-800 p-5 rounded-xl font-bold flex justify-between items-center">
                <div>❌ موجودی حساب کافی نیست! کسری: <span class="font-mono text-rose-600">${deficit.toLocaleString()} ریال</span></div>
                <a href="${window.DozoulehConfig.chargeUrl}" target="_blank" class="bg-rose-600 text-white px-4 py-2 rounded-lg text-xs">شارژ حساب</a>
            </div>`;
        jQuery('#nextBtn').prop('disabled', true).addClass('opacity-50 cursor-not-allowed').text('عدم کفایت موجودی');
    }
    container.html(html);
}

function updateWizardUI() {
    jQuery('#prevBtn').toggleClass('hidden', mohammadWizardStep === 1);
    jQuery('#nextBtn').text(mohammadWizardStep === 3 ? 'تایید نهایی و ارسال' : 'مرحله بعد');
    jQuery('#progressBar').css('width', ((mohammadWizardStep - 1) / (totalWizardSteps - 1)) * 100 + '%');

    if (mohammadWizardStep === 1) {
        jQuery('#nextBtn').prop('disabled', false).removeClass('opacity-50 cursor-not-allowed');
    }
}

// ========================================================
// رویدادها (Event Listeners)
// ========================================================
jQuery(document).ready(function() {
    
    jQuery(document).on('change', '#main_cargo_type', function() {
        let cargoType = jQuery(this).val();
        if (cargoType) {
            applyDynamicRules(cargoType);
        } else {
            jQuery('.dynamic-doc').attr('style', 'display: none !important');
        }
    });

    jQuery('.dynamic-doc').attr('style', 'display: none !important');

    jQuery(document).on('change', '.country-select', function() {
        let row = jQuery(this).closest('.country-row');
        let permitSelect = row.find('.permit-select');
        let selectedCountryId = jQuery(this).val();

        permitSelect.empty().append('<option value="">انتخاب کنید...</option>');
        
        if (selectedCountryId && window.DozoulehConfig && window.DozoulehConfig.countriesList) {
            let countryData = window.DozoulehConfig.countriesList.find(c => c.id == selectedCountryId);
            
            if (countryData && countryData.allowed_permit_types) { 
                let permits = typeof countryData.allowed_permit_types === 'string' 
                    ? JSON.parse(countryData.allowed_permit_types) 
                    : countryData.allowed_permit_types;
                
                if(Array.isArray(permits)) {
                    const permitLabels = {
                        'bilateral': 'دوجانبه',
                        'transit': 'ترانزیت',
                        'bilateral_transit': 'دوجانبه ترانزیت',
                        'third_country_transit': 'ترانزیت ثالث',
                        'third_country': 'ثالث'
                    };

                    permits.forEach(permit => {
                        let labelText = permitLabels[permit] || permit.replace('_', ' ');
                        permitSelect.append(`<option value="${permit}">${labelText}</option>`);
                    });
                }
            }
        }
        validateStep2Directly();
    });

    jQuery(document).on('change font-bold keyup', '.permit-select, #loading_origin, #loading_destination, input[name="cits_code"]', function() {
        validateStep2Directly();
    });

    jQuery(document).on('keyup', '.number-format', function(event) {
        if(event.which >= 37 && event.which <= 40) return;
        jQuery(this).val(function(index, value) { return value.replace(/\D/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ","); });
    });

    jQuery(document).on('click', '.remove-country-btn', function() {
        jQuery(this).closest('.country-row').remove();
        validateStep2Directly();
    });

    let destinationIndex = 1;
    jQuery('#add_destination_btn').on('click', function() {
        let countryOptions = '<option value="">انتخاب کشور...</option>';
        if (window.DozoulehConfig && window.DozoulehConfig.countriesList) {
            window.DozoulehConfig.countriesList.forEach(function(country) {
                let price = country.price ?? 0;
                countryOptions += `<option value="${country.id}" data-price="${price}">${country.name}</option>`;
            });
        }

        let newRow = `
        <div class="country-row flex flex-col md:flex-row items-center gap-4 bg-white border border-slate-200 p-4 rounded-xl shadow-sm relative mt-3" data-index="${destinationIndex}">
            <div class="w-full md:w-1/2">
                <label class="block text-xs font-bold text-slate-600 mb-1">کشور مقصد/عبوری <span class="text-rose-500">*</span></label>
                <select name="destinations[${destinationIndex}][country_id]" class="w-full border border-slate-300 rounded-lg p-2.5 country-select outline-none focus:border-blue-500">
                    ${countryOptions}
                </select>
            </div>
            <div class="w-full md:w-1/2">
                <label class="block text-xs font-bold text-slate-600 mb-1">نوع مجوز دوزوله <span class="text-rose-500">*</span></label>
                <select name="destinations[${destinationIndex}][permit_type]" class="w-full border border-slate-300 rounded-lg p-2.5 permit-select outline-none focus:border-blue-500">
                    <option value="">ابتدا کشور را انتخاب کنید...</option>
                </select>
            </div>
            <button type="button" class="remove-country-btn absolute -top-3 -right-2 bg-rose-100 text-rose-600 border border-rose-200 w-7 h-7 rounded-full flex items-center justify-center hover:bg-rose-500 hover:text-white transition shadow-sm" title="حذف این کشور">✖</button>
        </div>`;

        jQuery('#destinations_wrapper').append(newRow);
        destinationIndex++;
        validateStep2Directly();
    });
});