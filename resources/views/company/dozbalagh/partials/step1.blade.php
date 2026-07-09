<style>
    body.swal2-height-auto { height: 100vh !important; }
    .swal2-container { z-index: 1000 !important; }
    .select2-container--open, .select2-dropdown { z-index: 1005 !important; }
    .form-step, form, .main-content, .bg-white, .shadow-sm { overflow: visible !important; }
    select.select2-hidden-accessible { display: none !important; }
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

<div id="driver_fleet_selection_box" class="grid grid-cols-1 md:grid-cols-2 gap-5">
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

        <div id="driver_active_warning" class="hidden mt-2 p-2.5 bg-amber-50 border border-amber-200 text-amber-800 text-[10.5px] rounded-xl font-bold shadow-sm">
            ⚠️ <strong>توجه:</strong> این راننده یک پروانه باز یا تسویه‌نشده (کد: <span id="driver_active_code" class="font-mono text-amber-900 font-black"></span>) در سیستم دارد.
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

<div id="renewal_smart_box" class="hidden mt-5 p-4 bg-blue-50/80 border border-blue-200 rounded-2xl">
    <div class="flex items-start gap-3">
        <span class="text-blue-500 text-2xl leading-none mt-0.5">🔄</span>
        <div class="flex-1">
            <h4 class="font-black text-blue-900 text-sm mb-0.5">تمدید دوزوله با کد رهگیری</h4>
            <p class="text-xs text-blue-800/90 mb-3 font-medium">
                فقط کد رهگیری پرونده قبلی یا شماره دوزوله را وارد کنید. بعد از تأیید، اطلاعات پرونده قبلی در مرحله بعد برای ویرایش بارگذاری می‌شود.
            </p>

            <div class="p-3 bg-white rounded-xl border border-blue-100">
                <label class="block text-[10px] font-bold text-slate-500 mb-1">
                    کد رهگیری / شماره دوزوله قبلی <span class="text-rose-500">*</span>
                </label>

                <div class="flex flex-col sm:flex-row gap-2">
                    <input
                        type="text"
                        id="previous_dozouleh_number"
                        name="previous_dozouleh_number"
                        class="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-xs font-bold text-slate-800 outline-none focus:border-blue-500 transition-colors bg-white font-mono"
                        placeholder="مثلاً KHD1405... یا شماره دوزوله قبلی"
                        autocomplete="off"
                    >
                    <button type="button" id="check_renewal_code_btn" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-black transition">
                        بررسی پرونده
                    </button>
                </div>

                <div id="renewal_loading" class="hidden mt-3 text-[11px] font-bold text-blue-700 bg-blue-50 border border-blue-100 rounded-lg px-3 py-2">
                    در حال بررسی پرونده تمدید...
                </div>

                <div id="renewal_empty" class="hidden mt-3 text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
                    در حال حاضر امکان ثبت درخواست تمدید برای این دوزبلاغ وجود ندارد.
                </div>

                <div id="renewal_preview" class="hidden mt-3 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-[11px] text-emerald-900 space-y-1">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-black">✅ پرونده مرجع تمدید پیدا شد</span>
                        <span id="renewal_selected_code" class="font-mono font-black text-emerald-800"></span>
                    </div>
                    <div>راننده پرونده: <span id="renewal_selected_driver" class="font-bold"></span></div>
                    <div>ناوگان پرونده: <span id="renewal_selected_fleet" class="font-bold"></span></div>
                    <div>مقاصد پرونده: <span id="renewal_selected_countries" class="font-bold"></span></div>
                </div>

                <div id="renewal_detail_box" class="hidden mt-3 rounded-2xl border border-slate-200 bg-slate-50 overflow-hidden">
                    <div class="px-3 py-2 bg-slate-100 border-b border-slate-200 text-xs font-black text-slate-700">
                        اطلاعات خوانده‌شده از پرونده قبلی
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 p-3 text-[11px] text-slate-700">
                        <div class="bg-white border border-slate-100 rounded-xl p-2">
                            <span class="text-slate-400 font-bold">کد پرونده:</span>
                            <span id="renewal_detail_code" class="font-mono font-black text-slate-900"></span>
                        </div>
                        <div class="bg-white border border-slate-100 rounded-xl p-2">
                            <span class="text-slate-400 font-bold">وضعیت:</span>
                            <span id="renewal_detail_status" class="font-black text-slate-900"></span>
                        </div>
                        <div class="bg-white border border-slate-100 rounded-xl p-2">
                            <span class="text-slate-400 font-bold">راننده:</span>
                            <span id="renewal_detail_driver" class="font-black text-slate-900"></span>
                        </div>
                        <div class="bg-white border border-slate-100 rounded-xl p-2">
                            <span class="text-slate-400 font-bold">ناوگان:</span>
                            <span id="renewal_detail_fleet" class="font-black text-slate-900"></span>
                        </div>
                        <div class="bg-white border border-slate-100 rounded-xl p-2">
                            <span class="text-slate-400 font-bold">نوع بار:</span>
                            <span id="renewal_detail_cargo" class="font-black text-slate-900"></span>
                        </div>
                        <div class="bg-white border border-slate-100 rounded-xl p-2">
                            <span class="text-slate-400 font-bold">کد CITS:</span>
                            <span id="renewal_detail_cits" class="font-mono font-black text-slate-900"></span>
                        </div>
                        <div class="bg-white border border-slate-100 rounded-xl p-2">
                            <span class="text-slate-400 font-bold">مبدا:</span>
                            <span id="renewal_detail_origin" class="font-black text-slate-900"></span>
                        </div>
                        <div class="bg-white border border-slate-100 rounded-xl p-2">
                            <span class="text-slate-400 font-bold">مقصد:</span>
                            <span id="renewal_detail_destination" class="font-black text-slate-900"></span>
                        </div>
                        <div class="md:col-span-2 bg-white border border-slate-100 rounded-xl p-2">
                            <span class="text-slate-400 font-bold">مقاصد دوزوله:</span>
                            <span id="renewal_detail_countries" class="font-black text-slate-900"></span>
                        </div>
                    </div>

                    <div class="px-3 pb-3 text-[11px] font-bold text-emerald-700">
                        ✅ اطلاعات پرونده آماده است. با زدن «مرحله بعد»، همین اطلاعات در مرحله دوم برای ویرایش نمایش داده می‌شود.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function updateRadioUI(type) {
        let $jq = typeof jQuery !== 'undefined' ? jQuery : $;
        let requestType = type === 'new' ? 'new' : 'renewal';

        window.fleetRequestData = window.fleetRequestData || {};
        window.fleetRequestData.request_type = requestType;

        if (type === 'new') {
            $jq('#req_new').prop('checked', true);
            $jq('#box_new').attr('class', "p-3 rounded-xl border border-slate-800 bg-slate-100/80 transition-all flex items-center gap-3 shadow-sm");
            $jq('#outer_new').attr('class', "w-4 h-4 rounded-full border border-slate-800 flex items-center justify-center");
            $jq('#inner_new').css('transform', "scale(1)");

            $jq('#box_renewal').attr('class', "p-3 rounded-xl border border-slate-200 bg-white transition-all flex items-center gap-3");
            $jq('#outer_renewal').attr('class', "w-4 h-4 rounded-full border border-slate-300 flex items-center justify-center");
            $jq('#inner_renewal').css('transform', "scale(0)");
        } else {
            $jq('#req_renewal').prop('checked', true);
            $jq('#box_renewal').attr('class', "p-3 rounded-xl border border-slate-800 bg-slate-100/80 transition-all flex items-center gap-3 shadow-sm");
            $jq('#outer_renewal').attr('class', "w-4 h-4 rounded-full border border-slate-800 flex items-center justify-center");
            $jq('#inner_renewal').css('transform', "scale(1)");

            $jq('#box_new').attr('class', "p-3 rounded-xl border border-slate-200 bg-white transition-all flex items-center gap-3");
            $jq('#outer_new').attr('class', "w-4 h-4 rounded-full border border-slate-300 flex items-center justify-center");
            $jq('#inner_new').css('transform', "scale(0)");
        }

        triggerRenewalBoxLogic($jq);
    }

    function triggerRenewalBoxLogic($jq) {
        let requestType = $jq('input[name="request_type"]:checked').val();

        if (requestType === 'renewal') {
            $jq('#driver_fleet_selection_box').slideUp(200);
            $jq('#driver_id, #fleet_id').prop('required', false);
            $jq('#renewal_smart_box').slideDown(250);
        } else {
            $jq('#driver_fleet_selection_box').slideDown(200);
            $jq('#driver_id, #fleet_id').prop('required', true);
            $jq('#renewal_smart_box').slideUp(200);
            $jq('#previous_dozouleh_number').val('').removeClass('border-rose-500 ring-1 ring-rose-500');
            $jq('#renewal_preview').addClass('hidden');
            $jq('#renewal_empty').addClass('hidden');
            $jq('#renewal_detail_box').addClass('hidden');
            window.renewalReference = null;
            window.renewalPreviousDestinations = null;
        }
    }

    function checkActiveDozoulehProtection($jq) {
        if (typeof window.DozoulehConfig !== 'undefined' && window.DozoulehConfig.isEditMode) return true;
        let requestType = $jq('input[name="request_type"]:checked').val();
        if (requestType === 'renewal' || window.isApplyingRenewalReference) return true;

        let fleetOpt = $jq('#fleet_id option:selected');
        if (fleetOpt.val() && parseInt(fleetOpt.attr('data-active')) > 0) {
            let dCode = fleetOpt.attr('data-dozouleh') || 'نامشخص';
            Swal.fire({
                title: 'خطای ناوگان ملکی',
                text: `ناوگان با پلاک (${fleetOpt.attr('data-plate')}) دارای یک پروانه باز به شماره ${dCode} است. ثبت دوزوله جدید برای این ناوگان مقدور نیست.`,
                icon: 'error',
                confirmButtonText: 'متوجه شدم',
                confirmButtonColor: '#1e293b'
            });
            $jq('#fleet_id').val('').trigger('change.select2').trigger('change');
            return false;
        }
        return true;
    }

    function preg_match_plate($jq, plateStr) {
        let matches = (plateStr || '').match(/(\d+)|([^\d\s]+)/g);
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

    function fillRenewalReference(item, $jq) {
        if (!item) return false;

        $jq('#previous_dozouleh_number').val(item.d_code || item.serial_number || '').removeClass('border-rose-500 ring-1 ring-rose-500');

        window.isApplyingRenewalReference = true;
        // در تمدید فقط مقدار پشت‌صحنه ست می‌شود؛ change نمی‌زنیم تا هشدار ناوگان فعال اجرا نشود
        if (item.driver_id) $jq('#driver_id').val(item.driver_id);
        if (item.fleet_id) $jq('#fleet_id').val(item.fleet_id);
        setTimeout(function() { window.isApplyingRenewalReference = false; }, 500);

        $jq('#renewal_selected_code').text(item.d_code || item.serial_number || '');
        $jq('#renewal_selected_driver').text(item.driver_name || item.driver || '---');
        $jq('#renewal_selected_fleet').text(item.fleet_plate || item.fleet || '---');
        $jq('#renewal_selected_countries').text(item.countries_text || '---');
        $jq('#renewal_preview').removeClass('hidden');
        $jq('#renewal_empty').addClass('hidden');

        $jq('#renewal_detail_code').text(item.d_code || item.serial_number || '---');
        $jq('#renewal_detail_status').text(item.status || '---');
        $jq('#renewal_detail_driver').text(item.driver_name || item.driver || '---');
        $jq('#renewal_detail_fleet').text(item.fleet_plate || item.fleet || '---');
        $jq('#renewal_detail_cargo').text(item.cargo_type || '---');
        $jq('#renewal_detail_cits').text(item.cits_code || '---');
        $jq('#renewal_detail_origin').text(item.loading_origin || '---');
        $jq('#renewal_detail_destination').text(item.loading_destination || '---');
        $jq('#renewal_detail_countries').text(item.countries_text || '---');
        $jq('#renewal_detail_box').removeClass('hidden');

        window.renewalReference = item;
        window.renewalPreviousDestinations = item.destinations || [];

        // مقدار تمدید را برای submit نهایی فرم حفظ می‌کنیم
        let finalType = document.getElementById('final_request_type');
        let finalPrevious = document.getElementById('final_previous_dozouleh_number');
        if (finalType) finalType.value = 'renewal';
        if (finalPrevious) finalPrevious.value = item.d_code || item.serial_number || '';

        if (typeof window.syncRenewalFinalFields === 'function') {
            window.syncRenewalFinalFields();
        }

        return true;
    }

    function lookupRenewalByCode($jq, goNextAfterSuccess) {
        let code = ($jq('#previous_dozouleh_number').val() || '').trim();

        if (!code) {
            $jq('#previous_dozouleh_number').addClass('border-rose-500 ring-1 ring-rose-500');
            Swal.fire({
                icon: 'warning',
                title: 'نقص اطلاعات تمدید',
                text: 'لطفاً کد رهگیری یا شماره دوزوله قبلی را وارد کنید.',
                confirmButtonText: 'متوجه شدم',
                confirmButtonColor: '#1e293b'
            });
            return false;
        }

        $jq('#renewal_loading').removeClass('hidden');
        $jq('#renewal_empty').addClass('hidden');

        $jq.ajax({
            url: (window.DozoulehConfig && window.DozoulehConfig.renewableListUrl) ? window.DozoulehConfig.renewableListUrl : "{{ route('dozbalagh.renewable_list') }}",
            type: 'GET',
            data: { d_code: code },
            success: function(res) {
                $jq('#renewal_loading').addClass('hidden');

                if (!res.success || !res.items || res.items.length === 0) {
                    let msg = res.message || 'در حال حاضر امکان ثبت درخواست تمدید برای این دوزبلاغ وجود ندارد.';
                    $jq('#renewal_empty').text(msg).removeClass('hidden');
                    $jq('#renewal_preview').addClass('hidden');
                    window.renewalReference = null;
                    window.renewalPreviousDestinations = null;
                    $jq('#renewal_detail_box').addClass('hidden');

                    let finalType = document.getElementById('final_request_type');
                    let finalPrevious = document.getElementById('final_previous_dozouleh_number');
                    if (finalType) finalType.value = 'new';
                    if (finalPrevious) finalPrevious.value = '';

                    return;
                }

                let item = res.items[0];
                fillRenewalReference(item, $jq);

                if (goNextAfterSuccess && typeof window.transitionToStep2 === 'function') {
                    window.transitionToStep2();
                }
            },
            error: function() {
                $jq('#renewal_loading').addClass('hidden');
                Swal.fire({
                    icon: 'error',
                    title: 'خطای استعلام',
                    text: 'ارتباط با سرور برای بررسی پرونده تمدید برقرار نشد.',
                    confirmButtonText: 'متوجه شدم',
                    confirmButtonColor: '#1e293b'
                });
            }
        });

        return true;
    }

    function initStepOneLogic() {
        let $jq = typeof jQuery !== 'undefined' ? jQuery : $;
        let initialReqType = $jq('input[name="request_type"]:checked').val() || 'new';

        window.fleetRequestData = window.fleetRequestData || {};
        window.fleetRequestData.request_type = initialReqType;

        // Select2 ممکن است در بعضی بارگذاری‌ها هنوز آماده نباشد.
        // اگر آماده نبود، نباید کل منطق مرحله اول بخوابد؛ با select معمولی ادامه می‌دهیم.
        if ($jq.fn && typeof $jq.fn.select2 === 'function') {
            $jq('#driver_id, #fleet_id').each(function() {
                if ($jq(this).hasClass('select2-hidden-accessible')) $jq(this).select2('destroy');
            });

            $jq('#driver_id, #fleet_id').select2({ dir: "rtl", width: '100%', dropdownParent: $jq('body') });
        }

        $jq('#driver_id, #fleet_id').on('change', function() {
            let requestType = $jq('input[name="request_type"]:checked').val();
            let id = $jq(this).attr('id');

            window.fleetRequestData = window.fleetRequestData || {};
            window.fleetRequestData.request_type = requestType;

            if (id === 'driver_id') {
                let opt = $jq('#driver_id option:selected');
                if (opt.val()) {
                    $jq('#info_driver_en').text(opt.attr('data-name-en'));
                    $jq('#info_driver_national').text(opt.attr('data-national'));
                    $jq('#info_driver_passport').text(opt.attr('data-passport'));
                    $jq('#driver_info_box').removeClass('hidden');

                    if (parseInt(opt.attr('data-active')) > 0) {
                        $jq('#driver_active_code').text(opt.attr('data-dozouleh') || 'نامشخص');
                        $jq('#driver_active_warning').removeClass('hidden');
                    } else {
                        $jq('#driver_active_warning').addClass('hidden');
                    }
                } else {
                    $jq('#driver_info_box').addClass('hidden');
                    $jq('#driver_active_warning').addClass('hidden');
                }
            }

            if (id === 'fleet_id') {
                let opt = $jq('#fleet_id option:selected');
                if (opt.val()) {
                    $jq('#info_fleet_type').text(opt.attr('data-type'));
                    $jq('#info_fleet_smart').text(opt.attr('data-smart'));
                    $jq('#info_fleet_horse').text(opt.attr('data-horse'));
                    $jq('#info_fleet_trailer').text(opt.attr('data-trailer'));
                    preg_match_plate($jq, opt.attr('data-plate') || '---');
                    $jq('#fleet_info_box').removeClass('hidden');
                } else {
                    $jq('#fleet_info_box').addClass('hidden');
                }

                if (requestType === 'new') checkActiveDozoulehProtection($jq);
            }
        });

        $jq('#check_renewal_code_btn').on('click', function() {
            lookupRenewalByCode($jq, false);
        });

        $jq('#previous_dozouleh_number').on('keyup', function(e) {
            if ($jq(this).val().trim()) $jq(this).removeClass('border-rose-500 ring-1 ring-rose-500');
            if (e.key === 'Enter') {
                e.preventDefault();
                lookupRenewalByCode($jq, false);
            }
        });

        triggerRenewalBoxLogic($jq);
    }

    (function checkJQueryReady() {
        if (typeof jQuery !== 'undefined' || typeof $ !== 'undefined') {
            let $jq = typeof jQuery !== 'undefined' ? jQuery : $;

            $jq.ajaxPrefilter(function(options) {
                if (options.url && options.url.includes('check_fleet')) {
                    let currentReqType = $jq('input[name="request_type"]:checked').val() || 'new';
                    let separator = options.url.includes('?') ? '&' : '?';
                    options.url += separator + 'request_type=' + currentReqType;
                }
            });

            initStepOneLogic();
        } else {
            setTimeout(checkJQueryReady, 50);
        }
    })();
</script>
