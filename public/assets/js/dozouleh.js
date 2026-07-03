/* public/assets/js/dozouleh.js */

// ==========================================
// 1. پلاگین اختصاصی و آفلاین برای جستجوی کشویی (بدون ارور تداخل)
// ==========================================
// ==========================================
// 1. پلاگین اختصاصی و آفلاین برای جستجوی کشویی (ضد پرش و کاملاً فیکس)
// ==========================================
(function($) {
    if (!$.fn.select2) {
        $.fn.select2 = function(options) {
            return this.each(function() {
                var $select = $(this);
                if ($select.hasClass('select2-hidden-accessible')) return;
                $select.addClass('select2-hidden-accessible');

                var placeholder = options.placeholder || 'انتخاب کنید...';
                var initialText = $select.find('option:selected').text() || placeholder;

                // 🔴 تزریق استایل‌های فیکس‌کننده به صورت مستقیم (Inline) برای جلوگیری از پرش
                var $wrapper = $('<div class="select2-container" style="position: relative !important; width: 100%; display: block; direction: rtl; text-align: right;"></div>');
                
                var $selection = $('<div class="select2-selection--single" style="height: 48px; border: 1px solid #cbd5e1; border-radius: 0.75rem; background: #fff; display: flex; align-items: center; position: relative; cursor: pointer; padding: 0 1rem; transition: all 0.2s;"><span class="select2-selection__rendered" style="width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-align: right; font-size: 0.875rem; font-weight: 700; color: #334155;">' + initialText + '</span><span class="select2-selection__arrow" style="position: absolute; left: 10px; color: #64748b;">▼</span></div>');
                
                var $dropdown = $('<div class="select2-dropdown" style="display: none; position: absolute; top: calc(100% + 4px); left: 0; width: 100%; z-index: 99999; background: #fff; border: 1px solid #cbd5e1; border-radius: 0.75rem; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); overflow: hidden;"></div>');
                
                var $searchWrapper = $('<div style="padding: 8px;"></div>');
                var $searchInput = $('<input type="text" class="select2-search__field" placeholder="جستجو..." style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 0.5rem; outline: none; text-align: right; direction: rtl; font-family: inherit;">');
                var $results = $('<ul class="select2-results__options" style="max-height: 200px; overflow-y: auto; list-style: none; margin: 0; padding: 0;"></ul>');

                $searchWrapper.append($searchInput);
                $dropdown.append($searchWrapper).append($results);
                $wrapper.append($selection).append($dropdown);
                $select.after($wrapper);

                function renderList() {
                    $results.empty();
                    var searchTerm = $searchInput.val().toLowerCase();
                    $select.find('option').each(function() {
                        var $opt = $(this);
                        if ($opt.val()) {
                            if (!searchTerm || $opt.text().toLowerCase().indexOf(searchTerm) > -1) {
                                var $li = $('<li style="padding: 10px 16px; font-size: 0.875rem; font-weight: 600; color: #475569; text-align: right; cursor: pointer; border-bottom: 1px solid #f8fafc; transition: all 0.1s;">' + $opt.text() + '</li>');
                                $li.on('mouseenter', function() { $(this).css({background: '#10b981', color: '#fff'}); });
                                $li.on('mouseleave', function() { $(this).css({background: 'transparent', color: '#475569'}); });
                                $li.on('click', function(evt) {
                                    evt.stopPropagation();
                                    $select.val($opt.val()).trigger('change');
                                    $selection.find('.select2-selection__rendered').text($opt.text());
                                    $dropdown.hide(); // استفاده از تابع ایمن JQuery به جای کلاس
                                });
                                $results.append($li);
                            }
                        }
                    });
                }

                $selection.on('click', function(evt) {
                    evt.stopPropagation();
                    $('.select2-dropdown').hide(); // بستن سایر منوهای باز
                    if ($dropdown.is(':hidden')) {
                        $dropdown.show();
                        $searchInput.val('');
                        renderList();
                        $searchInput.focus();
                    } else {
                        $dropdown.hide();
                    }
                });

                $searchInput.on('keyup', renderList);
                $searchInput.on('click', function(evt) { evt.stopPropagation(); });

                $(document).on('click', function(evt) {
                    if (!$wrapper.is(evt.target) && $wrapper.has(evt.target).length === 0) {
                        $dropdown.hide();
                    }
                });
            });
        };
    }
})(jQuery);

// ==========================================
// 2. متغیرهای سراسری سیستم
// ==========================================
let currentStep = 1;
const totalSteps = 4;
let rowCounter = 1;

// ==========================================
// 3. راه‌اندازی اولیه فرم به محض لود صفحه
// ==========================================
$(document).ready(function() {
    if ($.fn.select2) {
        $('#driver_id').select2({ placeholder: "انتخاب و جستجوی راننده...", dir: "rtl" });
        $('#fleet_id').select2({ placeholder: "انتخاب و جستجوی ناوگان...", dir: "rtl" });
    }

    $(document).on('change', '#driver_id', showDriverInfo);
    $(document).on('change', '#fleet_id', showFleetInfo);
});

// ==========================================
// 4. توابع مدیریت راننده و ناوگان
// ==========================================
function showDriverInfo() {
    const select = document.getElementById('driver_id');
    const box = document.getElementById('driver_info_box');
    const opt = select.options[select.selectedIndex];
    
    if(!select.value) { box.classList.add('hidden'); return; }
    
    box.classList.remove('hidden');
    document.getElementById('info_driver_en').innerText = opt.getAttribute('data-name-en') || '---';
    document.getElementById('info_driver_national').innerText = opt.getAttribute('data-national');
    document.getElementById('info_driver_passport').innerText = opt.getAttribute('data-passport');
}

function showFleetInfo() {
    const select = document.getElementById('fleet_id');
    const box = document.getElementById('fleet_info_box');
    const opt = select.options[select.selectedIndex];
    
    if(!select.value) { box.classList.add('hidden'); return; }

    // 🔴 گرفتن آیدی راننده برای استعلام همزمان جفتشان
    const driverId = document.getElementById('driver_id') ? document.getElementById('driver_id').value : null;

    $.post(window.DozoulehConfig.checkFleetUrl, {
        _token: window.DozoulehConfig.csrfToken,
        fleet_id: select.value,
        driver_id: driverId // 👈 ارسال راننده به لاراول
    }).done(function(response) {
        
        // 🟢 هماهنگی کامل با کنترلر جدید لاراول
        if(!response.success) {
            Swal.fire({ icon: 'error', title: 'غیرمجاز', text: response.message, confirmButtonText: 'متوجه شدم', confirmButtonColor: '#ef4444' });
            $('#fleet_id').val(null).trigger('change');
            box.classList.add('hidden');
        } else {
            box.classList.remove('hidden');
            document.getElementById('info_fleet_type').innerText = opt.getAttribute('data-type');
            document.getElementById('info_fleet_smart').innerText = opt.getAttribute('data-smart');
            document.getElementById('info_fleet_horse').innerText = opt.getAttribute('data-horse') || '---';
            document.getElementById('info_fleet_trailer').innerText = opt.getAttribute('data-trailer') || '---';

            const rawPlate = opt.getAttribute('data-plate') || '';
            const parts = rawPlate.split('-');
            if(parts.length === 4) {
                document.getElementById('plate_part_1').innerText = parts[0]; 
                document.getElementById('plate_part_2').innerText = parts[1]; 
                document.getElementById('plate_part_3').innerText = parts[2]; 
                document.getElementById('plate_part_4').innerText = parts[3]; 
            } else {
                document.getElementById('plate_part_1').innerText = rawPlate; 
                document.getElementById('plate_part_2').innerText = '';
                document.getElementById('plate_part_3').innerText = ''; 
                document.getElementById('plate_part_4').innerText = '--';
            }
        }
    }).fail(function() {
        Swal.fire({ icon: 'warning', title: 'خطای سرور', text: 'ارتباط برای استعلام وضعیت ناوگان برقرار نشد.', confirmButtonText: 'تلاش مجدد' });
        $('#fleet_id').val(null).trigger('change');
        box.classList.add('hidden');
    });
}

// ==========================================
// 5. توابع مدیریت کشورهای مقصد و مدارک
// ==========================================
function loadRowPermits(selectElement) {
    const row = selectElement.closest('.country-row');
    const permitSelect = row.querySelector('.permit-select');
    const docsBox = row.querySelector('.country-docs-box');
    const fileInput = row.querySelector('.country-file-input');
    const opt = selectElement.options[selectElement.selectedIndex];
    
    permitSelect.innerHTML = '<option value="">انتخاب کنید...</option>';
    permitSelect.disabled = true;

    if(!selectElement.value) {
        docsBox.classList.add('hidden');
        fileInput.required = false; 
        return;
    }

    docsBox.classList.remove('hidden');
    fileInput.required = true;

    const permitsRaw = opt.getAttribute('data-permits');
    if(permitsRaw && permitsRaw !== 'null' && permitsRaw !== '[]') {
        try {
            JSON.parse(permitsRaw).forEach(p => {
                const option = document.createElement('option');
                option.value = p; option.text = p.replace('_', '-');
                permitSelect.appendChild(option);
            });
            permitSelect.disabled = false;
        } catch (e) { console.error("خطا در خواندن مجوزها:", e); }
    } else {
        permitSelect.innerHTML = '<option value="">بدون مجوز فعال</option>';
    }
}

function addCountryRow() {
    const container = document.getElementById('countries_repeater');
    const firstRow = container.querySelector('.country-row');
    const newRow = firstRow.cloneNode(true);
    
    const selectCountry = newRow.querySelector('.country-select');
    const selectPermit = newRow.querySelector('.permit-select');
    const fileInput = newRow.querySelector('.country-file-input');
    const docsBox = newRow.querySelector('.country-docs-box');
    
    selectCountry.name = `countries[${rowCounter}][country_id]`; selectCountry.value = "";
    selectPermit.name = `countries[${rowCounter}][permit_type]`; selectPermit.innerHTML = '<option value="">ابتدا کشور را انتخاب کنید</option>'; selectPermit.disabled = true;
    
    fileInput.name = `countries[${rowCounter}][document]`;
    fileInput.value = "";
    fileInput.required = false;
    docsBox.classList.add('hidden');
    
    newRow.querySelector('.remove-btn').classList.remove('hidden');
    container.appendChild(newRow);
    rowCounter++;
    updateRemoveButtonsVisibility();
}

function removeCountryRow(btn) {
    btn.closest('.country-row').remove();
    updateRemoveButtonsVisibility();
}

function updateRemoveButtonsVisibility() {
    const rows = document.querySelectorAll('.country-row');
    rows.forEach(row => {
        const btn = row.querySelector('.remove-btn');
        if(rows.length > 1) { btn.classList.remove('hidden'); } else { btn.classList.add('hidden'); }
    });
}

function updateFileName(input) {
    if (input.files && input.files[0]) {
        const display = document.getElementById('file-name-display');
        display.innerText = 'فایل انتخاب شد: ' + input.files[0].name; display.classList.remove('hidden');
    }
}

// ==========================================
// 6. موتور ویزارد (جابجایی بین مراحل) و مالی
// ==========================================
function changeStep(n) {
    if (n === 1 && !validateStep(currentStep)) return;
    document.getElementById(`step-${currentStep}`).classList.add('hidden');
    currentStep = currentStep + n;
    document.getElementById(`step-${currentStep}`).classList.remove('hidden');
    if (currentStep === 4) updateSummary();
    updateUI();
}

function updateUI() {
    document.getElementById('prevBtn').classList.toggle('hidden', currentStep === 1);
    document.getElementById('nextBtn').classList.toggle('hidden', currentStep === totalSteps);
    document.getElementById('progressBar').style.width = `${((currentStep - 1) / (totalSteps - 1)) * 100}%`;

    for (let i = 1; i <= totalSteps; i++) {
        const indicator = document.getElementById('indicator-' + i); const text = document.getElementById('indicator-text-' + i);
        if (i <= currentStep) {
            indicator.classList.remove('border-slate-200', 'bg-white', 'text-slate-400');
            indicator.classList.add('border-emerald-500', 'bg-emerald-500', 'text-white', 'shadow-lg', 'shadow-emerald-200');
            text.classList.remove('text-slate-400'); text.classList.add('text-emerald-600');
        } else {
            indicator.classList.remove('border-emerald-500', 'bg-emerald-500', 'text-white', 'shadow-lg', 'shadow-emerald-200');
            indicator.classList.add('border-slate-200', 'bg-white', 'text-slate-400');
            text.classList.remove('text-emerald-600'); text.classList.add('text-slate-400');
        }
    }
}

function validateStep(step) {
    let isValid = true;
    if(step === 1) {
        if(!$('#driver_id').val()) { $('.select2-selection', $('#driver_id').next()).css('border-color', '#ef4444'); isValid = false; } else { $('.select2-selection', $('#driver_id').next()).css('border-color', '#cbd5e1'); }
        if(!$('#fleet_id').val()) { $('.select2-selection', $('#fleet_id').next()).css('border-color', '#ef4444'); isValid = false; } else { $('.select2-selection', $('#fleet_id').next()).css('border-color', '#cbd5e1'); }
    }

    const inputs = document.querySelectorAll('#step-' + step + ' [required]:not(.select2-hidden-accessible)');
    inputs.forEach(input => {
        if (!input.value) {
            input.classList.remove('border-slate-300'); input.classList.add('border-rose-500', 'ring-1', 'ring-rose-500'); isValid = false;
        } else {
            input.classList.remove('border-rose-500', 'ring-1', 'ring-rose-500'); input.classList.add('border-slate-300');
        }
    });
    if(!isValid) alert('لطفاً تمامی فیلدهای ستاره‌دار این مرحله را تکمیل کنید.');
    return isValid;
}

// =======================================================
// تابع محاسبات مالی و آپدیت باکس خلاصه (گام آخر)
// =======================================================
function updateSummary() {
    const driverSelect = document.getElementById('driver_id'); 
    const fleetSelect = document.getElementById('fleet_id');
    
    // آپدیت نام راننده و ناوگان (اگر هنوز در HTML آیدی آن‌ها وجود داشته باشد)
    const summaryDriver = document.getElementById('summary-driver');
    if (summaryDriver) summaryDriver.innerText = driverSelect.options[driverSelect.selectedIndex].getAttribute('data-name-fa') || '---';
    
    const summaryFleet = document.getElementById('summary-fleet');
    if (summaryFleet) summaryFleet.innerText = fleetSelect.options[fleetSelect.selectedIndex].getAttribute('data-plate') || '---';

    let totalPrice = 0; 
    const summaryListDiv = document.getElementById('summary-countries-list'); 
    if(summaryListDiv) summaryListDiv.innerHTML = '';
    
    // محاسبه جمع مبالغ کشورها
    document.querySelectorAll('.country-row').forEach(row => {
        const countrySel = row.querySelector('.country-select'); 
        const permitSel = row.querySelector('.permit-select');
        if(countrySel && countrySel.value && permitSel && permitSel.value) {
            const opt = countrySel.options[countrySel.selectedIndex];
            const cPrice = parseInt(opt.getAttribute('data-price') || 0);
            totalPrice += cPrice;
            if(summaryListDiv) {
                summaryListDiv.innerHTML += '<div class="flex justify-between text-xs bg-white p-2 rounded-lg border border-slate-100 shadow-sm"><span class="font-bold text-slate-700">📍 ' + opt.getAttribute('data-name') + ' [' + permitSel.options[permitSel.selectedIndex].text + ']</span><span class="font-mono text-slate-500">' + cPrice.toLocaleString() + ' ریال</span></div>';
            }
        }
    });

    // آپدیت باکس شیک کیف پول و دکمه پرداخت
    const actionsDiv = document.getElementById('wallet-checkout-container');
    if (actionsDiv) {
        // گرفتن موجودی کیف پول از بک‌اند لاراول (اگر ست نشده باشد، صفر در نظر می‌گیرد)
        const balance = (window.DozoulehConfig && window.DozoulehConfig.walletBalance) ? parseInt(window.DozoulehConfig.walletBalance) : 0;
        const chargeUrl = (window.DozoulehConfig && window.DozoulehConfig.chargeUrl) ? window.DozoulehConfig.chargeUrl : '#';

        // ساختار HTML تاریک برای باکس کیف پول (طبق عکس هدف)
        let htmlBox = `
        <div class="bg-slate-900 rounded-2xl p-5 mb-4 shadow-lg text-white">
            <div class="flex justify-between items-center mb-4 border-b border-slate-700 pb-4">
                <span class="text-slate-400 text-sm font-bold">موجودی کیف پول</span>
                <span class="text-xl font-mono font-black">${balance.toLocaleString()} <span class="text-xs text-slate-500">ریال</span></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-slate-300 text-sm font-bold">جمع کل قابل پرداخت:</span>
                <span class="text-2xl text-emerald-400 font-mono font-black">${totalPrice.toLocaleString()} <span class="text-xs text-emerald-600">ریال</span></span>
            </div>
        </div>`;

        // تصمیم‌گیری برای نمایش دکمه ثبت یا دکمه شارژ
        if (balance >= totalPrice) {
            htmlBox += `
            <div class="bg-emerald-50 text-emerald-700 p-4 rounded-xl text-sm mb-4 font-bold flex items-center gap-2 border border-emerald-200">
                <span class="text-xl">✅</span> موجودی حساب شما برای این درخواست کافی است.
            </div>
            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black py-4 rounded-xl shadow-xl shadow-emerald-200 transition text-lg">
                تایید نهایی و ثبت پرونده
            </button>`;
        } else {
            const deficit = totalPrice - balance;
            htmlBox += `
            <div class="bg-rose-50 text-rose-700 p-4 rounded-xl text-sm mb-4 font-black flex items-center justify-between border border-rose-200">
                <div class="flex items-center gap-2">
                    <span class="text-xl">❌</span> موجودی حساب کافی نیست!
                </div>
                <span>کسری: ${deficit.toLocaleString()} ریال</span>
            </div>
            <a href="${chargeUrl}" target="_blank" class="block w-full text-center bg-rose-600 hover:bg-rose-700 text-white font-black py-4 rounded-xl shadow-xl shadow-rose-200 transition text-lg">
                شارژ حساب
            </a>`;
        }

        actionsDiv.innerHTML = htmlBox;
    }
}

// =======================================================
// بررسی وضعیت ۳ فیلد اصلی (باید بیرون از توابع دیگر باشد)
// =======================================================
$(document).ready(function() {
    $(document).on('change', '.primary-select', function() {
        let parentBox = $(this).closest('.destination-item');
        
        let country = parentBox.find('select[name$="[country_id]"]').val();
        let permit = parentBox.find('select[name$="[permit_type]"]').val();
        let cargo = parentBox.find('select[name$="[cargo_type]"]').val();
        
        if(country && permit && cargo) {
            parentBox.find('.destination-details').slideDown().removeClass('hidden');
        } else {
            parentBox.find('.destination-details').slideUp().addClass('hidden');
        }
    });
});

