<div id="fleet_registration_modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/70 p-3 backdrop-blur-sm sm:p-4" dir="rtl">
    <div class="mx-auto my-2 w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="fleet_registration_title">
        <div class="flex items-start justify-between gap-3 bg-gradient-to-l from-sky-800 to-blue-950 p-4 text-white">
            <div>
                <p class="text-xs font-bold text-sky-100">مدیریت ناوگان انجمن</p>
                <h3 id="fleet_registration_title" class="mt-1 text-base font-black">ثبت و استعلام ناوگان جدید</h3>
                <p class="mt-1 text-[11px] leading-5 text-blue-100">ناوگان پس از ثبت به شرکت انتخاب‌شده متصل می‌شود.</p>
            </div>
            <button type="button" onclick="closeFleetRegistrationModal()" class="rounded-xl p-1 text-2xl leading-none text-blue-100 transition hover:bg-white/10 hover:text-white" aria-label="بستن فرم">×</button>
        </div>

        <form id="fleet_registration_form" class="space-y-3 p-4 sm:p-5" onsubmit="saveAssociationFleet(event); return false;">
            <section class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-3 sm:p-4">
                <label for="fleet_registration_company_id" class="block text-xs font-black text-slate-700">
                    شرکت مالک ناوگان <span class="text-rose-600">*</span>
                    <select id="fleet_registration_company_id" required class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        <option value="">یک شرکت تأییدشده را انتخاب کنید</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name_fa }}</option>
                        @endforeach
                    </select>
                </label>
            </section>

            <section class="rounded-xl border border-sky-100 bg-sky-50/60 p-3 sm:p-4">
                <div class="mb-3 flex items-start gap-2.5">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-600 text-xs font-black text-white">۱</span>
                    <div>
                        <h4 class="text-sm font-black text-slate-800">استعلام کارت هوشمند</h4>
                        <p class="mt-0.5 text-[10px] leading-4 text-slate-500">در صورت عدم پاسخ سرویس، ورود دستی اطلاعات همچنان امکان‌پذیر است.</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <label class="block text-xs font-black text-slate-700">شماره کارت هوشمند <span class="text-rose-600">*</span><input type="text" id="fleet_registration_smart_id" required minlength="7" maxlength="7" pattern="[0-9]{7}" inputmode="numeric" autocomplete="off" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white p-2.5 text-center font-mono text-sm outline-none transition focus:border-sky-500 focus:ring-4 focus:ring-sky-100" placeholder="مثال: 1768168"></label>
                    <label class="block text-xs font-black text-slate-700">عنوان بارگیر در استعلام<input type="text" id="fleet_registration_organization_loading_type" readonly class="mt-1.5 w-full rounded-lg border border-slate-300 bg-slate-100 p-2.5 text-center text-sm outline-none" placeholder="پس از استعلام نمایش داده می‌شود"></label>
                </div>
                <div class="mt-3 flex justify-end"><button type="button" onclick="inquireAssociationFleet()" id="fleet_registration_inquiry_button" class="rounded-lg bg-sky-600 px-5 py-2.5 text-xs font-black text-white shadow-sm transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-60">🔍 استعلام از راهداری</button></div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-3 sm:p-4">
                <div class="mb-3 flex items-start gap-2.5"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-xs font-black text-white">۲</span><div><h4 class="text-sm font-black text-slate-800">مشخصات ناوگان</h4><p class="mt-0.5 text-[10px] leading-4 text-slate-500">تمامی فیلدهای مشترک ثبت ناوگان شرکت در این فرم موجود است.</p></div></div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <label class="block text-xs font-black text-slate-700">نوع بارگیر (یدک) <span class="text-rose-600">*</span><select id="fleet_registration_loading_type_id" required class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"><option value="">نوع بارگیر را انتخاب کنید</option>@foreach($loadingTypes as $type)<option value="{{ $type->id }}" data-code="{{ $type->rmto_code }}">{{ $type->name_fa }}</option>@endforeach</select></label>
                    <label class="block text-xs font-black text-slate-700">شماره ترانزیت اسب (کشنده) <span class="text-rose-600">*</span><input type="text" id="fleet_registration_transit_horse" required maxlength="100" pattern="[A-Z0-9]+(?:-[A-Z0-9]+)*" dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white p-2.5 text-center font-mono text-sm uppercase outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100" placeholder="TR-12345"></label>
                    <label class="block text-xs font-black text-slate-700 sm:col-span-2">شماره ترانزیت یدک (بارگیر) <span class="text-rose-600">*</span><input type="text" id="fleet_registration_transit_trailer" required maxlength="100" pattern="[A-Z0-9]+(?:-[A-Z0-9]+)*" dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white p-2.5 text-center font-mono text-sm uppercase outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100" placeholder="TR-98765"></label>
                </div>
                <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <div class="mb-2 flex items-center justify-between"><span class="text-xs font-black text-slate-700">شماره پلاک ایران خودرو <span class="text-rose-600">*</span></span><span class="text-[10px] text-slate-400">چهار بخش پلاک را تکمیل کنید</span></div>
                    <div class="grid grid-cols-[1fr_auto_1fr_1fr] gap-2" dir="ltr">
                        <input type="text" id="fleet_registration_plate_1" required maxlength="3" inputmode="numeric" class="min-w-0 rounded-lg border border-slate-300 p-2 text-center font-mono text-sm" placeholder="123">
                        <input type="text" id="fleet_registration_plate_letter" readonly value="ع" class="w-10 rounded-lg border border-slate-300 bg-slate-100 p-2 text-center font-bold text-sm" dir="rtl">
                        <input type="text" id="fleet_registration_plate_2" required maxlength="3" inputmode="numeric" class="min-w-0 rounded-lg border border-slate-300 p-2 text-center font-mono text-sm" placeholder="456">
                        <input type="text" id="fleet_registration_plate_3" required maxlength="2" inputmode="numeric" class="min-w-0 rounded-lg border border-slate-300 p-2 text-center font-mono text-sm" placeholder="11">
                    </div>
                </div>
            </section>

            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-3"><button type="button" onclick="closeFleetRegistrationModal()" class="rounded-lg bg-slate-100 px-5 py-2.5 text-xs font-black text-slate-700 transition hover:bg-slate-200">انصراف</button><button type="submit" id="fleet_registration_save_button" class="rounded-lg bg-sky-700 px-6 py-2.5 text-xs font-black text-white shadow-sm transition hover:bg-sky-800 disabled:cursor-not-allowed disabled:opacity-60">💾 ثبت ناوگان</button></div>
        </form>
    </div>
</div>

<script>
    let associationFleetInquiryPayload = null;
    let associationFleetLoadingTypeSource = 'manual';

    function openFleetRegistrationModal() {
        document.getElementById('fleet_registration_form').reset();
        $('#fleet_registration_plate_letter').val('ع');
        associationFleetInquiryPayload = null;
        associationFleetLoadingTypeSource = 'manual';
        $('#fleet_registration_modal').removeClass('hidden');
        $('#fleet_registration_company_id').trigger('focus');
    }

    function closeFleetRegistrationModal() {
        $('#fleet_registration_modal').addClass('hidden');
        document.getElementById('fleet_registration_form').reset();
        associationFleetInquiryPayload = null;
        associationFleetLoadingTypeSource = 'manual';
    }

    function associationFleetInquiryData() {
        const smartId = normalizeRegistrationDigits($('#fleet_registration_smart_id').val()).replace(/\D/g, '');
        $('#fleet_registration_smart_id').val(smartId);
        return { company_id: $('#fleet_registration_company_id').val(), smart_id: smartId };
    }

    function inquireAssociationFleet() {
        const data = associationFleetInquiryData();
        if (!data.company_id || !/^\d{7}$/.test(data.smart_id)) {
            Swal.fire('اطلاعات ناقص است', 'ابتدا شرکت و شماره کارت هوشمند ۷ رقمی را وارد کنید.', 'warning');
            return;
        }
        const button = $('#fleet_registration_inquiry_button').prop('disabled', true).text('⏳ در حال استعلام...');
        $.post("{{ route('admin.fleets.inquire') }}", { _token: "{{ csrf_token() }}", ...data })
            .done(function (response) {
                if (!response?.success) {
                    Swal.fire('ورود دستی اطلاعات', response?.message || 'پاسخ کاملی دریافت نشد؛ اطلاعات را دستی وارد کنید.', 'info');
                    return;
                }
                const root = response.data || {};
                const fleet = root?.data?.car || root?.car || root;
                associationFleetInquiryPayload = fleet;
                const active = fleet.IsActive === true || fleet.is_active === true || fleet.IsActive === 1 || fleet.is_active === 1 || fleet.IsActive === 'true' || fleet.is_active === 'true';
                if (!active && !fleet.id) {
                    associationFleetInquiryPayload = null;
                    Swal.fire('ورود دستی اطلاعات', 'استعلام معتبر نبود؛ اطلاعات را دستی وارد کنید.', 'info');
                    return;
                }

                const loadingType = fleet.fleet_loading_type_name || fleet.LoadingTypeTitle || fleet.bargir_name || fleet.truck_type || '';
                $('#fleet_registration_organization_loading_type').val(loadingType || 'در پاسخ سازمان عنوانی اعلام نشد');
                const loadingTypeId = String(fleet.fleet_loading_type_id || '');
                if (loadingTypeId && $('#fleet_registration_loading_type_id option[value="' + loadingTypeId + '"]').length) {
                    $('#fleet_registration_loading_type_id').val(loadingTypeId);
                    associationFleetLoadingTypeSource = fleet.fleet_loading_type_allowed ? 'rmto' : 'manual';
                }

                const plate = String(fleet.transit_plate || '').split('-');
                $('#fleet_registration_plate_1').val(plate[0] || fleet.plq3 || '');
                $('#fleet_registration_plate_2').val(plate[2] || fleet.plq1 || '');
                $('#fleet_registration_plate_3').val(plate[3] || fleet.shomare_serial_plaque || '');
                $('#fleet_registration_transit_horse').val(fleet.transit_horse || '');
                $('#fleet_registration_transit_trailer').val(fleet.transit_trailer || '');
                Swal.fire('استعلام موفق', 'اطلاعات قابل‌دریافت جایگذاری شد؛ پیش از ثبت، همهٔ فیلدها را بررسی کنید.', 'success');
            })
            .fail(function (xhr) { Swal.fire('ورود دستی اطلاعات', xhr.responseJSON?.message || 'استعلام در دسترس نیست؛ اطلاعات را دستی وارد کنید.', 'info'); })
            .always(function () { button.prop('disabled', false).text('🔍 استعلام از راهداری'); });
    }

    function saveAssociationFleet(event) {
        event.preventDefault();
        const form = document.getElementById('fleet_registration_form');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        const inquiry = associationFleetInquiryData();
        const loadingType = $('#fleet_registration_loading_type_id option:selected');
        const transitHorse = $.trim($('#fleet_registration_transit_horse').val()).toUpperCase();
        const transitTrailer = $.trim($('#fleet_registration_transit_trailer').val()).toUpperCase();
        const transitPlate = [$('#fleet_registration_plate_1').val(), $('#fleet_registration_plate_letter').val(), $('#fleet_registration_plate_2').val(), $('#fleet_registration_plate_3').val()].join('-');
        if (!/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/.test(transitHorse) || !/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/.test(transitTrailer)) {
            Swal.fire('شماره ترانزیت نامعتبر است', 'شماره ترانزیت اسب و یدک باید فقط از حروف انگلیسی بزرگ، عدد و خط تیره تشکیل شود.', 'warning');
            return;
        }
        const button = $('#fleet_registration_save_button').prop('disabled', true).text('در حال ثبت...');
        $.post("{{ route('admin.fleets.store') }}", {
            _token: "{{ csrf_token() }}",
            company_id: inquiry.company_id,
            smart_id: inquiry.smart_id,
            loading_type: $('#fleet_registration_organization_loading_type').val(),
            loading_type_id: loadingType.val(),
            loading_type_code: loadingType.data('code') || '',
            loading_type_source: associationFleetLoadingTypeSource,
            organization_payload: associationFleetInquiryPayload ? JSON.stringify(associationFleetInquiryPayload) : '',
            transit_horse: transitHorse,
            transit_trailer: transitTrailer,
            transit_plate: transitPlate,
        }).done(function (response) {
            if (response.success) {
                Swal.fire('ثبت شد', response.message || 'ناوگان با موفقیت ثبت شد.', 'success').then(() => window.location.reload());
                return;
            }
            Swal.fire('خطا', response.message || 'ثبت ناوگان انجام نشد.', 'error');
        }).fail(function (xhr) {
            const errors = xhr.responseJSON?.errors ? Object.values(xhr.responseJSON.errors).flat().join('\n') : '';
            Swal.fire('عدم ثبت ناوگان', errors || xhr.responseJSON?.message || 'خطای ارتباط با سرور.', 'error');
        }).always(function () { button.prop('disabled', false).text('💾 ثبت ناوگان'); });
    }
</script>
