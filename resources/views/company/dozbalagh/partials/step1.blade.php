<style>
    body.swal2-height-auto { height: 100vh !important; }
    .swal2-container { z-index: 1000 !important; }
    .select2-container--open, .select2-dropdown { z-index: 1005 !important; }
    .form-step, form, .main-content, .bg-white, .shadow-sm { overflow: visible !important; }
</style>

<h2 class="text-lg font-black text-slate-800 mb-5 flex items-center gap-2">
    <span class="bg-orange-50 text-orange-600 p-2 rounded-xl text-base">🚛</span> اطلاعات ناوگان و راننده
</h2>

<div class="mb-5 p-4 bg-slate-50 border border-slate-200/80 rounded-2xl">
    <label class="block text-xs font-black text-slate-700 mb-3">نوع درخواست را مشخص کنید <span class="text-rose-500">*</span></label>
    <div class="flex flex-col sm:flex-row gap-3">
        <label class="flex-1 relative cursor-pointer" onclick="updateRadioUI('new')">
            <input type="radio" id="req_new" name="request_type" value="new" style="display: none;" checked>
            <div id="box_new" class="p-3 rounded-xl border border-slate-800 bg-slate-100/80 transition-all flex items-center gap-3 shadow-sm">
                <div id="outer_new" class="w-4 h-4 rounded-full border border-slate-800 flex items-center justify-center">
                    <div id="inner_new" class="w-2 h-2 rounded-full bg-slate-800 transition-transform" style="transform: scale(1);"></div>
                </div>
                <div class="flex flex-col">
                    <span class="font-bold text-slate-800 text-xs">ثبت دوزوله جدید</span>
                    <span class="text-[10px] text-slate-500 font-medium mt-0.5">صدور پروانه برای سفر جدید</span>
                </div>
            </div>
        </label>

        <label class="flex-1 relative cursor-pointer" onclick="updateRadioUI('renewal')">
            <input type="radio" id="req_renewal" name="request_type" value="renewal" style="display: none;">
            <div id="box_renewal" class="p-3 rounded-xl border border-slate-200 bg-white transition-all flex items-center gap-3">
                <div id="outer_renewal" class="w-4 h-4 rounded-full border border-slate-300 flex items-center justify-center">
                    <div id="inner_renewal" class="w-2 h-2 rounded-full bg-slate-800 transition-transform" style="transform: scale(0);"></div>
                </div>
                <div class="flex flex-col">
                    <span class="font-bold text-slate-800 text-xs">تمدید دوزوله قبلی</span>
                    <span class="text-[10px] text-slate-500 font-medium mt-0.5">عطف به پروانه باز و تسویه‌نشده</span>
                </div>
            </div>
        </label>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div>
        <label class="block text-xs font-bold text-slate-600 mb-1.5">راننده متقاضی <span class="text-rose-500">*</span></label>
        <select name="driver_id" id="driver_id" required class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm outline-none bg-white focus:border-blue-500 transition-colors">
            <option value="">انتخاب راننده...</option>
            @foreach($drivers as $driver)
                @php
                    $driverFa = trim(($driver->first_name_fa ?? '') . ' ' . ($driver->last_name_fa ?? ''));
                    $driverEn = trim(($driver->first_name_en ?? '') . ' ' . ($driver->last_name_en ?? ''));
                    $showName = !empty($driverFa) ? $driverFa : $driverEn;
                    $activeCount = $driver->active_dozbalaghs ?? 0; 
                    $activeDozoulehNumber = $driver->last_active_number ?? ''; 
                @endphp
                <option value="{{ $driver->id }}" 
                        data-active="{{ $activeCount }}"
                        data-dozouleh="{{ $activeDozoulehNumber }}"
                        data-name-fa="{{ $driverFa }}" 
                        data-name-en="{{ $driverEn }}" 
                        data-national="{{ $driver->national_code ?? '---' }}" 
                        data-passport="{{ $driver->passport_number ?? '---' }}">
                    {{ $showName }} ({{ $driver->national_code ?? '---' }})
                </option>
            @endforeach
        </select>
        
        <div id="driver_info_box" class="mt-2.5 p-3 bg-slate-50 border border-slate-200/80 rounded-xl text-[11px] text-slate-600 hidden space-y-1">
            <p>👤 نام لاتین: <span id="info_driver_en" class="font-bold text-slate-800 uppercase"></span></p>
            <p>🪪 کد ملی: <span id="info_driver_national" class="font-mono font-bold"></span> | گذرنامه: <span id="info_driver_passport" class="font-mono font-bold"></span></p>
        </div>
    </div>

    <div>
        <label class="block text-xs font-bold text-slate-600 mb-1.5">ناوگان ملکی <span class="text-rose-500">*</span></label>
        <select name="fleet_id" id="fleet_id" required class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm outline-none bg-white focus:border-blue-500 transition-colors">
            <option value="">انتخاب ناوگان...</option>
            @foreach($fleets as $fleet)
                @php 
                    $fleetActive = $fleet->active_dozbalaghs ?? 0; 
                    $activeFleetDozouleh = $fleet->last_active_number ?? '';
                @endphp
                <option value="{{ $fleet->id }}" 
                        data-active="{{ $fleetActive }}"
                        data-dozouleh="{{ $activeFleetDozouleh }}"
                        data-plate="{{ $fleet->transit_plate ?? '---' }}" 
                        data-smart="{{ $fleet->smart_card_number ?? '---' }}" 
                        data-type="{{ $fleet->truck_type ?? '---' }}" 
                        data-horse="{{ $fleet->transit_horse ?? '---' }}" 
                        data-trailer="{{ $fleet->transit_trailer ?? '---' }}">
                    {{ $fleet->transit_plate ?? 'بدون پلاک' }} ({{ $fleet->truck_type ?? 'ناوگان' }})
                </option>
            @endforeach
        </select>
        
        <div id="fleet_info_box" class="mt-2.5 p-3 bg-slate-50 border border-slate-200/80 rounded-xl text-[11px] text-slate-600 hidden space-y-2">
            <div class="flex items-center justify-between border-b border-slate-200 pb-1.5">
                <p>🚛 نوع کامیون: <span id="info_fleet_type" class="font-bold text-slate-800"></span></p>
                <p>💳 کارت هوشمند: <span id="info_fleet_smart" class="font-mono font-bold text-slate-700"></span></p>
            </div>
            
            <div class="flex justify-center my-2">
                <div dir="ltr" class="inline-flex items-stretch border border-slate-700 rounded-md bg-[#fab800] text-slate-950 font-black h-10 overflow-hidden shadow-sm" style="min-width: 220px;">
                    <div class="bg-[#0033a0] flex flex-col items-center justify-between py-0.5 px-1 text-white border-r border-slate-700" style="width: 22px; min-width: 22px;">
                        <div class="w-full rounded-sm overflow-hidden flex flex-col" style="height: 5px;">
                            <div class="bg-[#228B22]" style="height: 33.33%;"></div>
                            <div class="bg-white" style="height: 33.33%;"></div>
                            <div class="bg-[#0033a0]" style="height: 33.33%;"></div>
                        </div>
                        <div class="flex flex-col items-center text-[5px] font-sans font-bold tracking-tighter" style="line-height: 1;">
                            <span>I.R.</span><span>IRAN</span>
                        </div>
                    </div>
                    <div class="flex-1 flex items-center justify-center gap-2.5 px-2 text-base font-mono font-bold tracking-wide">
                        <span id="plate_part_1">--</span><span id="plate_part_2" class="font-sans font-black text-sm text-slate-900">-</span><span id="plate_part_3">---</span>
                    </div>
                    <div class="border-l border-slate-700 flex flex-col items-center justify-center bg-[#fab800] text-slate-950 font-bold text-center" style="width: 42px; min-width: 42px; line-height: 1;">
                        <span class="text-[8px] text-slate-800">ایران</span>
                        <div class="w-full border-t border-slate-700 my-0.5"></div>
                        <span id="plate_part_4" class="text-xs font-mono tracking-tight">--</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 pt-1 border-t border-slate-200/60 text-[10px]">
                <p>🆔 ترانزیت اسب: <span id="info_fleet_horse" class="font-bold text-slate-700 font-mono"></span></p>
                <p>🆔 ترانزیت یدک: <span id="info_fleet_trailer" class="font-bold text-slate-700 font-mono"></span></p>
            </div>
        </div>
    </div>
</div> 

{{-- 💡 کادر انتخاب دوزبلاغ مرجع برای تمدید --}}
<div id="renewal_smart_box" class="hidden mt-5 p-4 bg-blue-50/80 border border-blue-200 rounded-2xl">
    <div class="flex items-start gap-3">
        <span class="text-blue-500 text-2xl leading-none mt-0.5">🔄</span>
        <div class="flex-1">
            <h4 class="font-black text-blue-900 text-sm mb-0.5">اطلاعات پرونده مرجع (تمدید)</h4>
            <p class="text-xs text-blue-800/90 mb-3 font-medium">
                دوزبلاغ قبلی را از لیست انتخاب کنید. لیست بر اساس راننده یا ناوگان انتخاب‌شده فیلتر می‌شود.
            </p>

            <input type="hidden" id="previous_dozouleh_number" name="previous_dozouleh_number">

            <div class="p-3 bg-white rounded-xl border border-blue-100">
                <div class="mb-3">
                    <label class="block text-[10px] font-bold text-slate-500 mb-1">انتخاب دوزبلاغ قبلی <span class="text-rose-500">*</span></label>
                    <select id="previous_dozouleh_select" name="previous_dozouleh_select_validation" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs font-bold text-slate-800 outline-none focus:border-blue-500 transition-colors bg-white">
                        <option value="">ابتدا راننده یا ناوگان را انتخاب کنید...</option>
                    </select>
                </div>

                <div id="renewal_loading" class="hidden text-[11px] font-bold text-blue-700 bg-blue-50 border border-blue-100 rounded-lg px-3 py-2">
                    در حال دریافت دوزبلاغ‌های قبلی...
                </div>

                <div id="renewal_empty" class="hidden text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
                    با این مشخصات دوزبلاغ قابل تمدیدی پیدا نشد.
                </div>

                <div id="renewal_preview" class="hidden mt-3 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-[11px] text-emerald-900 space-y-1">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-black">✅ دوزبلاغ مرجع انتخاب شد</span>
                        <span id="renewal_selected_code" class="font-mono font-black text-emerald-800"></span>
                    </div>
                    <div>راننده: <span id="renewal_selected_driver" class="font-bold"></span></div>
                    <div>ناوگان: <span id="renewal_selected_fleet" class="font-bold"></span></div>
                    <div>مقاصد: <span id="renewal_selected_countries" class="font-bold"></span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function updateRadioUI(type) {
        if (type === 'new') {
            document.getElementById('req_new').checked = true;
            document.getElementById('box_new').className = "p-3 rounded-xl border border-slate-800 bg-slate-100/80 transition-all flex items-center gap-3 shadow-sm";
            document.getElementById('outer_new').className = "w-4 h-4 rounded-full border border-slate-800 flex items-center justify-center";
            document.getElementById('inner_new').style.transform = "scale(1)";

            document.getElementById('box_renewal').className = "p-3 rounded-xl border border-slate-200 bg-white transition-all flex items-center gap-3";
            document.getElementById('outer_renewal').className = "w-4 h-4 rounded-full border border-slate-300 flex items-center justify-center";
            document.getElementById('inner_renewal').style.transform = "scale(0)";
        } else {
            document.getElementById('req_renewal').checked = true;
            document.getElementById('box_renewal').className = "p-3 rounded-xl border border-slate-800 bg-slate-100/80 transition-all flex items-center gap-3 shadow-sm";
            document.getElementById('outer_renewal').className = "w-4 h-4 rounded-full border border-slate-800 flex items-center justify-center";
            document.getElementById('inner_renewal').style.transform = "scale(1)";

            document.getElementById('box_new').className = "p-3 rounded-xl border border-slate-200 bg-white transition-all flex items-center gap-3";
            document.getElementById('outer_new').className = "w-4 h-4 rounded-full border border-slate-300 flex items-center justify-center";
            document.getElementById('inner_new').style.transform = "scale(0)";
        }

        if (typeof jQuery !== 'undefined' || typeof $ !== 'undefined') {
            let $jq = typeof jQuery !== 'undefined' ? jQuery : $;
            triggerRenewalBoxLogic($jq);
            
            let fId = $jq('#fleet_id').val();
            let dId = $jq('#driver_id').val();
            if (fId || dId) {
                $jq('#fleet_id').trigger('change');
            }
        }
    }

    function loadRenewableDozbalaghs($jq) {
        let requestType = $jq('input[name="request_type"]:checked').val();
        if (requestType !== 'renewal') return;

        let fleetId = $jq('#fleet_id').val();
        let driverId = $jq('#driver_id').val();
        let select = $jq('#previous_dozouleh_select');

        // 🟢 شرط اصلاح شد: واکشی انجام می‌شود حتی اگر فقط یکی (راننده یا ناوگان) انتخاب شده باشد
        if (!fleetId && !driverId) {
            select.html('<option value="">ابتدا راننده یا ناوگان ملکی را انتخاب کنید...</option>');
            $jq('#renewal_empty').addClass('hidden');
            $jq('#renewal_preview').addClass('hidden');
            return;
        }

        $jq('#renewal_loading').removeClass('hidden');
        $jq('#renewal_empty').addClass('hidden');
        $jq('#renewal_preview').addClass('hidden');
        $jq('#previous_dozouleh_number').val('');
        select.html('<option value="">در حال دریافت لیست دوزبلاغ‌ها...</option>');

        $jq.ajax({
            url: "{{ route('dozbalagh.renewable_list') }}",
            type: 'GET',
            data: {
                fleet_id: fleetId,
                driver_id: driverId
            },
            success: function(res) {
                $jq('#renewal_loading').addClass('hidden');
                select.html('<option value="">انتخاب کنید...</option>');

                if (!res.success || !res.items || res.items.length === 0) {
                    $jq('#renewal_empty').removeClass('hidden');
                    return;
                }

                res.items.forEach(function(item) {
                    let title = item.d_code + ' | مقاصد: ' + item.countries_text + ' | تاریخ: ' + item.created_at;
                    select.append(
                        $jq('<option>', {
                            value: item.d_code,
                            text: title,
                            'data-driver': item.driver,
                            'data-fleet': item.fleet,
                            'data-countries': item.countries_text,
                            'data-code': item.d_code,
                            'data-destinations': JSON.stringify(item.destinations || [])
                        })
                    );
                });
            },
            error: function() {
                $jq('#renewal_loading').addClass('hidden');
                select.html('<option value="">خطا در دریافت لیست دوزبلاغ‌ها</option>');
            }
        });
    }

    function triggerRenewalBoxLogic($jq) {
        let requestType = $jq('input[name="request_type"]:checked').val();

        if (requestType === 'renewal') {
            $jq('#renewal_smart_box').slideDown(250);
            $jq('#previous_dozouleh_select').prop('required', true);
            loadRenewableDozbalaghs($jq);
        } else {
            $jq('#renewal_smart_box').slideUp(200);
            $jq('#previous_dozouleh_number').val('');
            $jq('#previous_dozouleh_select').val('').prop('required', false);
            $jq('#renewal_preview').addClass('hidden');
        }
    }

    function checkActiveDozoulehProtection($jq) {
        let requestType = $jq('input[name="request_type"]:checked').val();
        
        if (requestType === 'renewal') {
            if (typeof Swal !== 'undefined' && Swal.isVisible()) {
                Swal.close();
            }
            return true; 
        }

        let driverOpt = $jq('#driver_id option:selected');
        let fleetOpt = $jq('#fleet_id option:selected');

        if (driverOpt.val() && parseInt(driverOpt.attr('data-active')) > 0) {
            let dCode = driverOpt.attr('data-dozouleh') || 'نامشخص';
            Swal.fire({
                title: 'خطای راننده متقاضی',
                text: `راننده انتخاب شده (${driverOpt.attr('data-name-fa')}) یک درخواست فعال یا پروانه تسویه نشده به شماره ${dCode} در سیستم دارد.`,
                icon: 'error',
                confirmButtonText: 'متوجه شدم',
                confirmButtonColor: '#1e293b'
            });
            $jq('#driver_id').val('').trigger('change.select2');
            return false;
        }

        if (fleetOpt.val() && parseInt(fleetOpt.attr('data-active')) > 0) {
            let dCode = fleetOpt.attr('data-dozouleh') || 'نامشخص';
            Swal.fire({
                title: 'خطای ناوگان ملکی',
                text: `ناوگان انتخاب شده با پلاک ترانزیت (${fleetOpt.attr('data-plate')}) دارای یک درخواست فعال یا پروانه زنده به شماره ${dCode} در سیستم است.`,
                icon: 'error',
                confirmButtonText: 'متوجه شدم',
                confirmButtonColor: '#1e293b'
            });
            $jq('#fleet_id').val('').trigger('change.select2');
            return false;
        }
        
        return true;
    }

    function preg_match_plate($jq, plateStr) {
        let matches = plateStr.match(/(\d+)|([^\d\s]+)/g);
        if (matches && matches.length >= 3) {
            $jq('#plate_part_1').text(matches[0] || '--');
            $jq('#plate_part_2').text(matches[1] || '-');
            $jq('#plate_part_3').text(matches[2] || '---');
            $jq('#plate_part_4').text(matches[3] || '--');
        } else {
            $jq('#plate_part_1').text('--');
            $jq('#plate_part_2').text('-');
            $jq('#plate_part_3').text('---');
            $jq('#plate_part_4').text('--');
        }
    }

    function initStepOneLogic() {
        let $jq = typeof jQuery !== 'undefined' ? jQuery : $;

        $jq('#driver_id').parents().removeClass('overflow-hidden').css('overflow', 'visible');

        $jq('#driver_id, #fleet_id').each(function() {
            if ($jq(this).hasClass('select2-hidden-accessible')) {
                $jq(this).select2('destroy');
            }
        });

        $jq('#driver_id, #fleet_id').select2({
            dir: "rtl",
            width: '100%',
            dropdownParent: $jq('body')
        });

        $jq('#driver_id, #fleet_id').on('change', function() {
            let requestType = $jq('input[name="request_type"]:checked').val();
            let id = $jq(this).attr('id');

            if (id === 'driver_id') {
                let opt = $jq('#driver_id option:selected');
                if (opt.val()) {
                    $jq('#info_driver_en').text(opt.attr('data-name-en'));
                    $jq('#info_driver_national').text(opt.attr('data-national'));
                    $jq('#info_driver_passport').text(opt.attr('data-passport'));
                    $jq('#driver_info_box').removeClass('hidden');
                } else {
                    $jq('#driver_info_box').addClass('hidden');
                }
            } else if (id === 'fleet_id') {
                let opt = $jq('#fleet_id option:selected');
                if (opt.val()) {
                    $jq('#info_fleet_type').text(opt.attr('data-type'));
                    $jq('#info_fleet_smart').text(opt.attr('data-smart'));
                    $jq('#info_fleet_horse').text(opt.attr('data-horse'));
                    $jq('#info_fleet_trailer').text(opt.attr('data-trailer'));
                    
                    let plate = opt.attr('data-plate') || '---';
                    preg_match_plate($jq, plate);
                    $jq('#fleet_info_box').removeClass('hidden');
                } else {
                    $jq('#fleet_info_box').addClass('hidden');
                }
            }

            if (typeof window.fleetRequestData === 'object') {
                window.fleetRequestData.request_type = requestType;
            }

            if (requestType === 'renewal') {
                loadRenewableDozbalaghs($jq);
            } else {
                checkActiveDozoulehProtection($jq);
            }
        });

        $jq('#previous_dozouleh_select').on('change', function() {
            let opt = $jq(this).find('option:selected');
            let val = opt.val();
            $jq('#previous_dozouleh_number').val(val || '');

            if (val) {
                $jq('#renewal_selected_code').text(opt.attr('data-code'));
                $jq('#renewal_selected_driver').text(opt.attr('data-driver'));
                $jq('#renewal_selected_fleet').text(opt.attr('data-fleet'));
                $jq('#renewal_selected_countries').text(opt.attr('data-countries'));
                $jq('#renewal_preview').removeClass('hidden');

                window.renewalPreviousDestinations = JSON.parse(opt.attr('data-destinations') || '[]');
            } else {
                $jq('#renewal_preview').addClass('hidden');
                window.renewalPreviousDestinations = null;
            }
        });
    }

    (function checkJQueryReady() {
        if (typeof jQuery !== 'undefined' || typeof $ !== 'undefined') {
            let $jq = typeof jQuery !== 'undefined' ? jQuery : $;
            
            if (typeof Swal !== 'undefined') {
                const originalFire = Swal.fire;
                Swal.fire = function(...args) {
                    let requestType = $jq('input[name="request_type"]:checked').val();
                    if (requestType === 'renewal' && args[0] && (args[0].icon === 'error' || args[0].title === 'غیرمجاز')) {
                        return Promise.resolve({ isConfirmed: true });
                    }
                    return originalFire.apply(this, args);
                };
            }
            
            initStepOneLogic();
        } else {
            setTimeout(checkJQueryReady, 50);
        }
    })();
</script>