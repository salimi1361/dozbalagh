<style>
    /* 🔴 تیر خلاص به باگ SweetAlert: جلوگیری از فروپاشی قالب Tailwind */
    body.swal2-height-auto {
        height: 100vh !important;
    }

    /* 1. پاپ‌آپ خطا با اولویت استاندارد (زیر سایدبار مشکی می‌ماند) */
    .swal2-container {
        z-index: 1000 !important; 
    }

    /* 2. منوی بازشو راننده و ناوگان روی فرم و بالاتر از خطا */
    .select2-container--open,
    .select2-dropdown {
        z-index: 1005 !important;
    }

    /* 3. آزادسازی کامل فرم از گیوتین (Overflow) */
    .form-step, form, .main-content, .bg-white, .shadow-sm { 
        overflow: visible !important; 
    }
</style>

<h2 class="text-lg font-black text-slate-800 mb-5 flex items-center gap-2">
    <span class="bg-orange-50 text-orange-600 p-2 rounded-xl text-base">🚛</span> اطلاعات ناوگان و راننده
</h2>

{{-- 🟢 بخش انتخاب نوع درخواست --}}
<div class="mb-5 p-4 bg-slate-50 border border-slate-200/80 rounded-2xl">
    <label class="block text-xs font-black text-slate-700 mb-3">نوع درخواست را مشخص کنید <span class="text-rose-500">*</span></label>
    <div class="flex flex-col sm:flex-row gap-3">
        
        {{-- گزینه ثبت جدید --}}
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

        {{-- گزینه تمدید --}}
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

{{-- فیلدهای اصلی انتخاب راننده و ناوگان --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    {{-- بخش راننده --}}
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

    {{-- بخش ناوگان --}}
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
                            <div class="bg-[#DA291C]" style="height: 33.33%;"></div>
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

{{-- 💡 کادر ورود اطلاعات تمدید --}}
<div id="renewal_smart_box" class="hidden mt-5 p-4 bg-blue-50/80 border border-blue-200 rounded-2xl">
    <div class="flex items-start gap-3">
        <span class="text-blue-500 text-2xl leading-none mt-0.5">🔄</span>
        <div class="flex-1">
            <h4 class="font-black text-blue-900 text-sm mb-0.5">اطلاعات پرونده مرجع (تمدید)</h4>
            <p class="text-xs text-blue-800/90 mb-3 font-medium">شماره دوزوله قبلی جهت تمدید (سیستم در صورت یافتن پرونده، فیلد را قفل می‌کند).</p>
            
            <div class="p-3 bg-white rounded-xl border border-blue-100 flex flex-col sm:flex-row items-center gap-3">
                <div class="flex-1 w-full flex flex-col">
                    <label class="text-[10px] font-bold text-slate-500 mb-1">شماره دوزوله مرجع <span class="text-rose-500">*</span></label>
                    <input type="text" id="previous_dozouleh_number" name="previous_dozouleh_number" 
                           placeholder="مثال: ۱۲۳۴۵..."
                           class="border border-slate-300 rounded-lg px-3 py-1.5 text-xs font-mono font-black text-slate-800 w-full outline-none focus:border-blue-500 transition-colors">
                </div>
                
                <div id="renewal_status_badge" class="hidden flex items-center gap-1.5 bg-emerald-50 px-3 py-2 rounded-lg border border-emerald-200 w-full sm:w-auto mt-2 sm:mt-0">
                     <span class="text-emerald-500 text-sm">✅</span>
                     <span class="text-[11px] font-bold text-emerald-800">یافت و قفل شد</span>
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

        if(typeof jQuery !== 'undefined') {
            triggerRenewalBoxLogic();
            checkActiveDozoulehProtection();
        }
    }

    function triggerRenewalBoxLogic() {
        let requestType = jQuery('input[name="request_type"]:checked').val();
        let driverOpt = jQuery('#driver_id option:selected');
        let fleetOpt = jQuery('#fleet_id option:selected');
        
        if (requestType === 'renewal') {
            jQuery('#renewal_smart_box').slideDown(250);
            let oldNum = driverOpt.attr('data-dozouleh') || fleetOpt.attr('data-dozouleh');
            
            if (oldNum && oldNum.trim() !== '') {
                jQuery('#previous_dozouleh_number').val(oldNum).prop('readonly', true).removeClass('bg-white cursor-text').addClass('bg-slate-100 cursor-not-allowed text-slate-500 border-slate-200');
                jQuery('#renewal_status_badge').removeClass('hidden').addClass('flex');
            } else {
                jQuery('#previous_dozouleh_number').val('').prop('readonly', false).removeClass('bg-slate-100 cursor-not-allowed text-slate-500 border-slate-200').addClass('bg-white cursor-text text-slate-800 border-slate-300');
                jQuery('#renewal_status_badge').addClass('hidden').removeClass('flex');
            }
        } else {
            jQuery('#renewal_smart_box').slideUp(200);
            jQuery('#previous_dozouleh_number').val('');
        }
    }

    // 🛑 قفل هوشمند فرانت‌بند در گام اول
    function checkActiveDozoulehProtection() {
        let requestType = jQuery('input[name="request_type"]:checked').val();
        if (requestType === 'renewal') {
            return true; 
        }

        let driverOpt = jQuery('#driver_id option:selected');
        let fleetOpt = jQuery('#fleet_id option:selected');

        // ۱. بررسی راننده
        if (driverOpt.val() && parseInt(driverOpt.attr('data-active')) > 0) {
            let dCode = driverOpt.attr('data-dozouleh') || 'نامشخص';
            Swal.fire({
                title: 'خطای راننده متقاضی',
                text: `راننده انتخاب شده (${driverOpt.attr('data-name-fa')}) یک درخواست فعال یا پروانه تسویه نشده به شماره ${dCode} در سیستم دارد.`,
                icon: 'error',
                confirmButtonText: 'متوجه شدم',
                confirmButtonColor: '#1e293b'
            });
            jQuery('#driver_id').val('').trigger('change.select2');
            return false;
        }

        // ۲. بررسی ناوگان
        if (fleetOpt.val() && parseInt(fleetOpt.attr('data-active')) > 0) {
            let dCode = fleetOpt.attr('data-dozouleh') || 'نامشخص';
            Swal.fire({
                title: 'خطای ناوگان ملکی',
                text: `ناوگان انتخاب شده با پلاک ترانزیت (${fleetOpt.attr('data-plate')}) دارای یک درخواست فعال یا پروانه زنده به شماره ${dCode} در سیستم است.`,
                icon: 'error',
                confirmButtonText: 'متوجه شدم',
                confirmButtonColor: '#1e293b'
            });
            jQuery('#fleet_id').val('').trigger('change.select2');
            return false;
        }
        
        return true;
    }

    document.addEventListener("DOMContentLoaded", function() {
        if (typeof jQuery !== 'undefined') {
            jQuery('#driver_id').parents().removeClass('overflow-hidden').css('overflow', 'visible');
        }

        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
            jQuery('#driver_id, #fleet_id').each(function() {
                if (jQuery(this).hasClass('select2-hidden-accessible')) {
                    jQuery(this).select2('destroy');
                }
            });

            jQuery('#driver_id, #fleet_id').select2({
                dir: "rtl",
                width: '100%',
                dropdownParent: jQuery('body')
            });
        }

        setTimeout(function() {
            jQuery('#driver_id, #fleet_id').on('change', function() {
                triggerRenewalBoxLogic();
                checkActiveDozoulehProtection();
            });
        }, 800);
    });

    // 🔐 شنود دکمه مرحله بعد برای قفل کامل فرانت
    jQuery(document).ready(function() {
        jQuery('#nextBtn').on('click', function(e) {
            if (!checkActiveDozoulehProtection()) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });
    });
</script>