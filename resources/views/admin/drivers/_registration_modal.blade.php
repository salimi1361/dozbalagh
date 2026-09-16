<div id="driver_registration_modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/70 p-3 backdrop-blur-sm sm:p-4" dir="rtl">
    <div class="mx-auto my-2 w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="driver_registration_title">
        <div class="flex items-start justify-between gap-3 bg-gradient-to-l from-sky-800 to-blue-950 p-4 text-white">
            <div>
                <p class="text-xs font-bold text-sky-100">مدیریت رانندگان انجمن</p>
                <h3 id="driver_registration_title" class="mt-1 text-base font-black">ثبت و استعلام راننده جدید</h3>
                <p class="mt-1 text-[11px] leading-5 text-blue-100">راننده با اطلاعات کامل، مستقیماً به شرکت انتخاب‌شده متصل می‌شود.</p>
            </div>
            <button type="button" onclick="closeDriverRegistrationModal()" class="rounded-xl p-1 text-2xl leading-none text-blue-100 transition hover:bg-white/10 hover:text-white" aria-label="بستن فرم">×</button>
        </div>

        <form id="driver_registration_form" class="space-y-3 p-4 sm:p-5" onsubmit="saveAssociationDriver(event); return false;">
            <section class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-3 sm:p-4">
                <label for="driver_registration_company_id" class="block text-xs font-black text-slate-700">
                    شرکت مالک راننده <span class="text-rose-600">*</span>
                    <select id="driver_registration_company_id" required class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
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
                        <h4 class="text-sm font-black text-slate-800">استعلام اولیه راننده</h4>
                        <p class="mt-0.5 text-[10px] leading-4 text-slate-500">کد ملی و موبایل برای استعلام الزامی است؛ در صورت عدم پاسخ، ورود دستی مجاز است.</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <label class="block text-xs font-black text-slate-700">
                        کد ملی راننده <span class="text-rose-600">*</span>
                        <input type="text" id="driver_registration_national_id" required maxlength="10" minlength="10" inputmode="numeric" autocomplete="off" placeholder="مثال: ۰۰۱۲۳۴۵۶۷۸" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white p-2.5 text-center font-mono text-sm tracking-widest outline-none transition focus:border-sky-500 focus:ring-4 focus:ring-sky-100">
                    </label>
                    <label class="block text-xs font-black text-slate-700">
                        شماره موبایل راننده <span class="text-rose-600">*</span>
                        <input type="text" id="driver_registration_mobile" required maxlength="11" minlength="11" inputmode="tel" autocomplete="tel" placeholder="مثال: ۰۹۱۲۱۲۳۴۵۶۷" dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white p-2.5 text-center font-mono text-sm tracking-widest outline-none transition focus:border-sky-500 focus:ring-4 focus:ring-sky-100">
                    </label>
                </div>
                <div class="mt-3 flex justify-end">
                    <button type="button" onclick="inquireAssociationDriver()" id="driver_registration_inquiry_button" class="rounded-lg bg-sky-600 px-5 py-2.5 text-xs font-black text-white shadow-sm transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-60">🔍 استعلام از راهداری</button>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-3 sm:p-4">
                <div class="mb-3 flex items-start gap-2.5">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-xs font-black text-white">۲</span>
                    <div>
                        <h4 class="text-sm font-black text-slate-800">مشخصات پرونده راننده</h4>
                        <p class="mt-0.5 text-[10px] leading-4 text-slate-500">همهٔ موارد برای صدور دوزوله استفاده می‌شوند.</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <label class="block text-xs font-black text-slate-700">نام فارسی <span class="text-rose-600">*</span><input type="text" id="driver_registration_first_name_fa" required maxlength="100" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"></label>
                    <label class="block text-xs font-black text-slate-700">نام خانوادگی فارسی <span class="text-rose-600">*</span><input type="text" id="driver_registration_last_name_fa" required maxlength="100" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"></label>
                    <label class="block text-xs font-black text-slate-700">نام لاتین / پاسپورت <span class="text-rose-600">*</span><input type="text" id="driver_registration_first_name_en" required maxlength="100" dir="ltr" class="mt-1.5 w-full rounded-lg border border-indigo-200 bg-indigo-50/60 p-2.5 text-left font-mono text-sm uppercase outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"></label>
                    <label class="block text-xs font-black text-slate-700">نام خانوادگی لاتین / پاسپورت <span class="text-rose-600">*</span><input type="text" id="driver_registration_last_name_en" required maxlength="100" dir="ltr" class="mt-1.5 w-full rounded-lg border border-indigo-200 bg-indigo-50/60 p-2.5 text-left font-mono text-sm uppercase outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"></label>
                    <label class="block text-xs font-black text-slate-700 md:col-span-2">شماره پاسپورت <span class="text-rose-600">*</span><input type="text" id="driver_registration_passport" required maxlength="30" dir="ltr" class="mt-1.5 w-full rounded-lg border border-amber-300 bg-amber-50/50 p-2.5 text-left font-mono text-sm uppercase outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-100"></label>
                </div>
            </section>

            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-3">
                <button type="button" onclick="closeDriverRegistrationModal()" class="rounded-lg bg-slate-100 px-5 py-2.5 text-xs font-black text-slate-700 transition hover:bg-slate-200">انصراف</button>
                <button type="submit" id="driver_registration_save_button" class="rounded-lg bg-sky-700 px-6 py-2.5 text-xs font-black text-white shadow-sm transition hover:bg-sky-800 disabled:cursor-not-allowed disabled:opacity-60">💾 ثبت راننده</button>
            </div>
        </form>
    </div>
</div>

<script>
    function normalizeRegistrationDigits(value) {
        return String(value || '').replace(/[۰-۹٠-٩]/g, function (digit) {
            return '0123456789'['۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩'.indexOf(digit) % 10];
        });
    }

    function openDriverRegistrationModal() {
        document.getElementById('driver_registration_form').reset();
        $('#driver_registration_modal').removeClass('hidden');
        $('#driver_registration_company_id').trigger('focus');
    }

    function closeDriverRegistrationModal() {
        $('#driver_registration_modal').addClass('hidden');
        document.getElementById('driver_registration_form').reset();
    }

    function associationDriverData() {
        const nationalId = normalizeRegistrationDigits($('#driver_registration_national_id').val()).replace(/\D/g, '');
        const mobile = normalizeRegistrationDigits($('#driver_registration_mobile').val()).replace(/\D/g, '');

        $('#driver_registration_national_id').val(nationalId);
        $('#driver_registration_mobile').val(mobile);

        return {
            company_id: $('#driver_registration_company_id').val(),
            national_id: nationalId,
            mobile: mobile,
        };
    }

    function inquireAssociationDriver() {
        const data = associationDriverData();
        if (!data.company_id || !/^\d{10}$/.test(data.national_id) || !/^09\d{9}$/.test(data.mobile)) {
            Swal.fire('اطلاعات ناقص است', 'ابتدا شرکت، کد ملی ۱۰ رقمی و شماره موبایل ۱۱ رقمی را وارد کنید.', 'warning');
            return;
        }

        const button = $('#driver_registration_inquiry_button').prop('disabled', true).text('⏳ در حال استعلام...');
        $.post("{{ route('admin.drivers.inquire') }}", {
            _token: "{{ csrf_token() }}",
            company_id: data.company_id,
            national_id: data.national_id,
            mobile_number: data.mobile,
        }).done(function (response) {
            let payload = response && response.data;
            if (typeof payload === 'string') {
                try { payload = JSON.parse(payload); } catch (_) { payload = null; }
            }
            const driver = payload?.driver || payload?.data?.driver || payload?.data?.data?.driver;
            if (response.success && driver) {
                $('#driver_registration_first_name_fa').val(driver.NAME || '');
                $('#driver_registration_last_name_fa').val(driver.FAMILY || '');
                Swal.fire('استعلام موفق', 'نام و نام خانوادگی راننده جایگذاری شد. سایر فیلدها را بررسی و تکمیل کنید.', 'success');
            } else {
                Swal.fire('ورود دستی اطلاعات', response?.message || 'پاسخ کامل دریافت نشد؛ اطلاعات را دستی وارد کنید.', 'info');
            }
        }).fail(function (xhr) {
            Swal.fire('ورود دستی اطلاعات', xhr.responseJSON?.message || 'استعلام در دسترس نیست؛ اطلاعات را دستی وارد کنید.', 'info');
        }).always(function () {
            button.prop('disabled', false).text('🔍 استعلام از راهداری');
        });
    }

    function saveAssociationDriver(event) {
        event.preventDefault();
        const form = document.getElementById('driver_registration_form');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const data = associationDriverData();
        data.first_name = $.trim($('#driver_registration_first_name_fa').val());
        data.last_name = $.trim($('#driver_registration_last_name_fa').val());
        data.first_name_en = $.trim($('#driver_registration_first_name_en').val()).toUpperCase();
        data.last_name_en = $.trim($('#driver_registration_last_name_en').val()).toUpperCase();
        data.passport_number = $.trim($('#driver_registration_passport').val()).toUpperCase();

        const latinName = /^[A-Z]+(?:[ '-][A-Z]+)*$/;
        const passport = /^[A-Z0-9][A-Z0-9 -]*$/;
        if (!/^\d{10}$/.test(data.national_id) || !/^09\d{9}$/.test(data.mobile) || !latinName.test(data.first_name_en) || !latinName.test(data.last_name_en) || !passport.test(data.passport_number)) {
            Swal.fire('اطلاعات نامعتبر است', 'کد ملی، موبایل، نام لاتین و شماره پاسپورت را مطابق الگوی فرم بررسی کنید.', 'warning');
            return;
        }

        const button = $('#driver_registration_save_button').prop('disabled', true).text('در حال ثبت...');
        $.post("{{ route('admin.drivers.store') }}", { _token: "{{ csrf_token() }}", ...data })
            .done(function (response) {
                if (response.success) {
                    Swal.fire('ثبت شد', response.message || 'راننده با موفقیت ثبت شد.', 'success').then(() => window.location.reload());
                    return;
                }
                Swal.fire('خطا', response.message || 'ثبت راننده انجام نشد.', 'error');
            })
            .fail(function (xhr) {
                const errors = xhr.responseJSON?.errors ? Object.values(xhr.responseJSON.errors).flat().join('\n') : '';
                Swal.fire('عدم ثبت راننده', errors || xhr.responseJSON?.message || 'خطای ارتباط با سرور.', 'error');
            })
            .always(function () { button.prop('disabled', false).text('💾 ثبت راننده'); });
    }
</script>
