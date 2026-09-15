@extends('layouts.admin')

@section('header_title')
    توزیع دوزوله / <span class="text-indigo-600 font-black">مدیریت سهمیه و محدودیت‌ها</span>
@endsection

@section('content')
<div class="mx-auto flex max-w-7xl flex-col gap-5">

    <section class="relative overflow-hidden rounded-2xl px-5 py-4 text-white shadow-lg sm:px-6 sm:py-5" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #0369a1 100%);">
        <div class="pointer-events-none absolute -left-12 -top-16 h-40 w-40 rounded-full bg-white opacity-10"></div>
        <div class="pointer-events-none absolute -bottom-20 right-1/3 h-36 w-36 rounded-full bg-cyan-300 opacity-10"></div>
        <div class="relative flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex items-center gap-3.5">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white bg-opacity-15 ring-1 ring-white ring-opacity-20">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v18m0-18 4.5 4.5M12 3 7.5 7.5M5.25 10.5h13.5A2.25 2.25 0 0 1 21 12.75v4.5a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 17.25v-4.5a2.25 2.25 0 0 1 2.25-2.25Z"/></svg>
                </div>
                <div>
                    <h1 class="text-xl font-black sm:text-2xl">مدیریت سهمیه و محدودیت‌ها</h1>
                    <p class="mt-1 text-xs font-semibold text-blue-100">تنظیم زمان ثبت درخواست، سقف عمومی کشورها و استثناهای اختصاصی شرکت‌ها</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                <div class="rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2 text-center"><span class="block text-[9px] font-bold text-blue-100">کشور فعال</span><strong class="mt-0.5 block text-lg font-black">{{ number_format($countries->count()) }}</strong></div>
                <div class="rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2 text-center"><span class="block text-[9px] font-bold text-blue-100">شرکت‌ها</span><strong class="mt-0.5 block text-lg font-black text-cyan-200">{{ number_format($companies->count()) }}</strong></div>
                <div class="rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2 text-center"><span class="block text-[9px] font-bold text-blue-100">استثنای سهمیه</span><strong class="mt-0.5 block text-lg font-black text-amber-300">{{ number_format($quotas->count()) }}</strong></div>
                <div class="rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 px-3 py-2 text-center"><span class="block text-[9px] font-bold text-blue-100">زمان‌بندی ثبت</span><strong class="mt-0.5 block text-sm font-black {{ $requestWindowSettings['enabled'] ? 'text-emerald-300' : 'text-slate-300' }}">{{ $requestWindowSettings['enabled'] ? 'فعال' : 'غیرفعال' }}</strong></div>
            </div>
        </div>
    </section>

    <div class="relative z-50 order-2 overflow-visible rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-indigo-100 border-r-4 border-r-indigo-500 bg-indigo-50 px-5 py-4">
            <div><h2 class="text-sm font-black text-indigo-900">مدیریت سقف درخواست‌ها و مسدودی</h2><p class="mt-1 text-xs text-indigo-700">قانون عمومی کشور یا سهمیه اختصاصی یک شرکت را ثبت کنید.</p></div>
        </div>
        
        <form action="{{ route('admin.allocations.quota') }}" method="POST" class="p-5 bg-slate-50 border-t border-slate-200">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-4 gap-5 items-start">
                
                <div class="md:col-span-2">
                    <div class="mb-2 flex items-center justify-between gap-2"><label for="allocation_company_search" class="text-xs font-bold text-slate-700">اعمال روی کدام شرکت؟</label><span id="allocation_company_count" class="text-[10px] font-bold text-indigo-600"></span></div>
                    <div id="allocation_company_picker" class="relative z-50">
                        <svg class="pointer-events-none absolute right-3 top-1/2 z-10 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m2.1-5.4a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z"/></svg>
                        <input type="search" id="allocation_company_search" autocomplete="off" role="combobox" aria-controls="allocation_company_options" aria-expanded="false" class="w-full rounded-xl border border-indigo-200 bg-white py-3 pl-10 pr-9 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="همه شرکت‌ها؛ برای جستجو نام یا شناسه ملی را بنویسید...">
                        <input type="hidden" name="company_id" id="allocation_company_id" value="">
                        <button type="button" id="allocation_company_toggle" aria-label="باز کردن فهرست شرکت‌ها" class="absolute left-1 top-1/2 -translate-y-1/2 rounded-lg p-2 text-slate-500 hover:bg-indigo-50 hover:text-indigo-600"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg></button>
                        <div id="allocation_company_options" role="listbox" class="absolute right-0 top-full z-40 mt-2 hidden max-h-72 w-full overflow-y-auto rounded-xl border border-indigo-100 bg-white p-1.5 text-sm shadow-xl"></div>
                    </div>
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-2 text-xs">کشور <span class="text-rose-500">*</span></label>
                    <select name="country_id" required class="w-full p-3 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-slate-500 outline-none bg-white">
                        <option value="">انتخاب کنید...</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}">{{ $country->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-2 text-xs">سقف مجاز (خالی = نامحدود)</label>
                    <input type="number" name="max_limit" min="0" placeholder="مثال: 2" class="w-full p-3 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-slate-500 outline-none">
                </div>

                <div class="md:col-span-3">
                    <label class="block text-slate-700 font-bold mb-2 text-xs">پیام خطا به کاربر (در صورت مسدودی یا رسیدن به سقف)</label>
                    <input type="text" name="reject_message" placeholder="مثال: به دلیل بخشنامه جدید، امکان ثبت دوزبلاغ وجود ندارد." class="w-full p-3 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-slate-500 outline-none">
                </div>

                <div class="flex items-end h-full pb-1">
                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 py-3 text-sm font-bold text-white shadow-md transition hover:bg-indigo-700">
                        💾 ذخیره قوانین
                    </button>
                </div>

            </div>
        </form>
    </div>

    <div class="order-1 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between border-b border-sky-100 border-r-4 border-r-sky-500 bg-sky-50 px-5 py-4">
            <div><h2 class="text-sm font-black text-sky-900">تنظیم زمان مجاز ثبت درخواست شرکت‌ها</h2><p class="mt-1 text-xs text-sky-700">ساعت فعالیت و روزهای غیرمجاز ثبت درخواست را تعیین کنید.</p></div>
        </div>

        <form action="{{ route('admin.allocations.request-window') }}" method="POST" class="p-5 bg-slate-50 border-t border-slate-200">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-4 gap-5 items-start">
                <div>
                    <label class="block text-slate-700 font-bold mb-2 text-xs">وضعیت محدودیت زمانی</label>
                    <label class="flex items-center justify-between gap-3 bg-white border border-slate-300 rounded-xl px-4 py-3 min-h-[48px]">
                        <span class="text-sm font-bold text-slate-700">فعال باشد</span>
                        <input type="checkbox" name="enabled" value="1" class="h-5 w-5 accent-sky-600" @checked($requestWindowSettings['enabled'])>
                    </label>
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-2 text-xs">ساعت شروع ثبت</label>
                    <input type="time" name="start_time" value="{{ $requestWindowSettings['start_time'] }}" required class="w-full p-3 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-slate-500 outline-none bg-white">
                </div>

                <div>
                    <label class="block text-slate-700 font-bold mb-2 text-xs">ساعت پایان ثبت</label>
                    <input type="time" name="end_time" value="{{ $requestWindowSettings['end_time'] }}" required class="w-full p-3 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-slate-500 outline-none bg-white">
                </div>

                <div class="flex items-end h-full pb-1">
                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-sky-600 py-3 text-sm font-bold text-white shadow-md transition hover:bg-sky-700">
                        💾 ذخیره زمان‌بندی
                    </button>
                </div>

                <div class="md:col-span-4">
                    <label class="block text-slate-700 font-bold mb-3 text-xs">روزهای غیرمجاز برای ثبت درخواست</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2">
                        @foreach($weekdays as $weekdayNumber => $weekdayName)
                            <label class="flex items-center justify-center gap-2 bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 min-h-[42px]">
                                <input type="checkbox" name="closed_weekdays[]" value="{{ $weekdayNumber }}" class="h-4 w-4 accent-sky-600" @checked(in_array((int) $weekdayNumber, $requestWindowSettings['closed_weekdays'], true))>
                                <span>{{ $weekdayName }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="md:col-span-4">
                    <label class="block text-slate-700 font-bold mb-2 text-xs">پیام نمایش داده‌شده به شرکت خارج از زمان مجاز</label>
                    <input type="text" name="closed_message" value="{{ $requestWindowSettings['closed_message'] }}" maxlength="255" class="w-full p-3 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-slate-500 outline-none bg-white">
                </div>
            </div>
        </form>
    </div>

    <div class="order-3 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="border-b border-emerald-100 border-r-4 border-r-emerald-500 bg-emerald-50 px-5 py-4">
            <h3 class="text-sm font-black text-emerald-900">قوانین پیش‌فرض کشورها</h3>
            <p class="mt-1 text-xs text-emerald-700">این قوانین به‌صورت عمومی روی همه شرکت‌ها اعمال می‌شوند.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-right text-sm">
                <thead>
                    <tr class="bg-white text-slate-500 border-b border-slate-200 text-xs">
                        <th class="p-4">نام کشور</th>
                        <th class="p-4 text-center">سقف عمومی</th>
                        <th class="p-4">پیام خطا پیش‌فرض</th>
                        <th class="p-4 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @foreach($countries as $country)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-4 text-slate-800 font-bold">{{ $country->name }}</td>
                            <td class="p-4 text-center text-indigo-600 font-black">
                                {{ is_null($country->default_quota) ? 'نامحدود' : ($country->default_quota == 0 ? 'مسدود 🚫' : $country->default_quota) }}
                            </td>
                            <td class="p-4 text-xs text-slate-500 truncate max-w-xs">{{ $country->reject_message ?? '-' }}</td>
                            <td class="p-4 text-center flex items-center justify-center gap-2">
                                <button type="button" 
                                        onclick="editGlobalRule('{{ $country->id }}', '{{ $country->default_quota }}', '{{ $country->reject_message }}')" 
                                        class="text-indigo-600 hover:text-indigo-800 font-bold text-xs bg-indigo-50 px-3 py-1.5 rounded-md transition">
                                    ویرایش
                                </button>
                                
                                @if(!is_null($country->default_quota) || !is_null($country->reject_message))
                                <form action="{{ route('admin.allocations.country.reset', $country->id) }}" method="POST" onsubmit="return confirm('آیا از حذف قانون کلی این کشور مطمئن هستید؟ با این کار سقف مجدداً نامحدود می‌شود.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:text-rose-800 font-bold text-xs bg-rose-50 px-3 py-1.5 rounded-md transition">
                                        حذف قانون
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="order-4 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="border-b border-violet-100 border-r-4 border-r-violet-500 bg-violet-50 px-5 py-4">
            <h3 class="text-sm font-black text-violet-900">استثناهای ثبت‌شده برای شرکت‌های خاص</h3>
            <p class="mt-1 text-xs text-violet-700">سهمیه‌ها و پیام‌های اختصاصی که قانون عمومی را برای یک شرکت تغییر می‌دهند.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-right text-sm">
                <thead>
                    <tr class="bg-white text-slate-500 border-b border-slate-200 text-xs">
                        <th class="p-4 w-1/3">نام شرکت</th>
                        <th class="p-4 text-center">کشور</th>
                        <th class="p-4 text-center">سقف اختصاصی</th>
                        <th class="p-4">پیام خطا اختصاصی</th>
                        <th class="p-4 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($quotas as $quota)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-4 text-slate-800 font-bold leading-relaxed">{{ $quota->company->name ?? 'نامشخص' }}</td>
                            <td class="p-4 text-center text-slate-600">{{ $quota->country->name ?? '-' }}</td>
                            <td class="p-4 text-center text-rose-600 font-black">
                                {{ is_null($quota->custom_limit) ? 'نامحدود' : ($quota->custom_limit == 0 ? 'مسدود 🚫' : $quota->custom_limit) }}
                            </td>
                            <td class="p-4 text-xs text-slate-500 truncate max-w-xs">{{ $quota->reject_message ?? '-' }}</td>
                            <td class="p-4 text-center flex items-center justify-center gap-2">
                                <button type="button" 
                                        onclick="editQuota('{{ $quota->company_id }}', '{{ $quota->country_id }}', '{{ $quota->custom_limit }}', '{{ $quota->reject_message }}')" 
                                        class="text-indigo-600 hover:text-indigo-800 font-bold text-xs bg-indigo-50 px-3 py-1.5 rounded-md transition">
                                    ویرایش
                                </button>
                                <form action="{{ route('admin.allocations.quota.destroy', $quota->id) }}" method="POST" onsubmit="return confirm('آیا از حذف این استثنا مطمئن هستید؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:text-rose-800 font-bold text-xs bg-rose-50 px-3 py-1.5 rounded-md transition">
                                        حذف
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-8 text-center text-slate-400 font-bold text-sm">هنوز استثنایی ثبت نشده است.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>
@php
    $allocationCompanyOptions = $companies->map(function ($company) {
        $name = $company->name_fa ?: $company->name ?: 'شرکت #'.$company->id;

        return [
            'id' => (string) $company->id,
            'label' => $name.($company->national_id ? ' — '.$company->national_id : ''),
            'search' => $name.' '.$company->national_id,
        ];
    })->values();
@endphp
<script>
    const allocationCompanies = @json($allocationCompanyOptions);

    function normalizeCompanySearch(value) {
        return String(value || '').trim().toLowerCase().replace(/ي/g, 'ی').replace(/ك/g, 'ک');
    }

    function selectAllocationCompany(companyId = '') {
        const input = document.getElementById('allocation_company_search');
        const hidden = document.getElementById('allocation_company_id');
        const menu = document.getElementById('allocation_company_options');
        const company = allocationCompanies.find(item => item.id === String(companyId));
        hidden.value = company ? company.id : '';
        input.value = company ? company.label : '';
        menu.classList.add('hidden');
        input.setAttribute('aria-expanded', 'false');
    }

    function renderAllocationCompanies(term = '', openMenu = true) {
        const menu = document.getElementById('allocation_company_options');
        const counter = document.getElementById('allocation_company_count');
        const input = document.getElementById('allocation_company_search');
        if (!menu || !input) return;

        const needle = normalizeCompanySearch(term);
        const matches = allocationCompanies.filter(company => normalizeCompanySearch(company.search).includes(needle));

        menu.innerHTML = '';
        const allOption = document.createElement('button');
        allOption.type = 'button';
        allOption.dataset.companyId = '';
        allOption.className = 'flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-right text-xs font-bold text-indigo-700 hover:bg-indigo-50';
        allOption.textContent = '🌐 همه شرکت‌ها (اعمال گروهی روی کشور)';
        menu.appendChild(allOption);
        matches.forEach(company => {
            const option = document.createElement('button');
            option.type = 'button';
            option.dataset.companyId = company.id;
            option.className = 'block w-full rounded-lg px-3 py-2.5 text-right text-xs font-semibold text-slate-700 hover:bg-indigo-50 hover:text-indigo-700';
            option.textContent = company.label;
            menu.appendChild(option);
        });
        if (!matches.length) {
            const empty = document.createElement('div');
            empty.className = 'px-3 py-4 text-center text-xs font-bold text-slate-400';
            empty.textContent = 'شرکتی با این مشخصات پیدا نشد.';
            menu.appendChild(empty);
        }
        menu.classList.toggle('hidden', !openMenu);
        input.setAttribute('aria-expanded', openMenu ? 'true' : 'false');
        counter.textContent = needle ? `${matches.length.toLocaleString('fa-IR')} شرکت یافت شد` : `${allocationCompanies.length.toLocaleString('fa-IR')} شرکت`;
    }

    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('allocation_company_search');
        const toggle = document.getElementById('allocation_company_toggle');
        const menu = document.getElementById('allocation_company_options');
        selectAllocationCompany('');
        renderAllocationCompanies('', false);
        searchInput?.addEventListener('input', event => renderAllocationCompanies(event.target.value));
        searchInput?.addEventListener('focus', event => renderAllocationCompanies(event.target.value));
        toggle?.addEventListener('click', () => renderAllocationCompanies(searchInput.value));
        menu?.addEventListener('click', event => {
            const option = event.target.closest('[data-company-id]');
            if (option) selectAllocationCompany(option.dataset.companyId);
        });
        document.addEventListener('click', event => {
            if (!document.getElementById('allocation_company_picker')?.contains(event.target)) {
                menu?.classList.add('hidden');
                searchInput?.setAttribute('aria-expanded', 'false');
            }
        });
    });

    @if(session('success'))
        Swal.fire({ 
            icon: 'success', 
            title: 'عملیات موفق', 
            text: @json(session('success')), 
            confirmButtonText: 'عالیه' 
        });
    @endif
    
    @if($errors->any())
        Swal.fire({ 
            icon: 'error', 
            title: 'خطا', 
            text: @json($errors->first()), 
            confirmButtonText: 'متوجه شدم' 
        });
    @endif

    function editQuota(companyId, countryId, maxLimit, rejectMessage) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        selectAllocationCompany(companyId);
        document.querySelector('select[name="country_id"]').value = countryId;
        document.querySelector('input[name="max_limit"]').value = maxLimit !== '' ? maxLimit : '';
        document.querySelector('input[name="reject_message"]').value = rejectMessage !== '-' ? rejectMessage : '';
    }

    function editGlobalRule(countryId, defaultQuota, rejectMessage) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        selectAllocationCompany('');
        document.querySelector('select[name="country_id"]').value = countryId;
        document.querySelector('input[name="max_limit"]').value = defaultQuota !== '' ? defaultQuota : '';
        document.querySelector('input[name="reject_message"]').value = rejectMessage !== '-' ? rejectMessage : '';
    }
</script>
@endsection
