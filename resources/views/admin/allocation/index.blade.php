@extends('layouts.admin')

@section('header_title')
    توزیع دوزوله / <span class="text-indigo-600 font-black">مدیریت سهمیه و محدودیت‌ها</span>
@endsection

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
        <div class="bg-slate-800 p-5 text-white flex justify-between items-center">
            <h2 class="text-sm font-black flex items-center gap-2">⚙️ مدیریت سقف درخواست‌ها و مسدودی</h2>
        </div>
        
        <form action="{{ route('admin.allocations.quota') }}" method="POST" class="p-5 bg-slate-50 border-t border-slate-200">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-4 gap-5 items-start">
                
                <div class="md:col-span-2">
                    <label class="block text-slate-700 font-bold mb-2 text-xs">اعمال روی کدام شرکت؟</label>
                    <select name="company_id" class="w-full p-3 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-slate-500 outline-none bg-white">
                        <option value="" class="font-bold text-indigo-600">🌐 همه شرکت‌ها (اعمال گروهی روی کشور)</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name ?? 'شرکت #'.$company->id }}</option>
                        @endforeach
                    </select>
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
                    <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-3 rounded-xl transition shadow-lg text-sm flex items-center justify-center gap-2">
                        💾 ذخیره قوانین
                    </button>
                </div>

            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
        <div class="p-5 border-b border-slate-100 bg-slate-50">
            <h3 class="font-bold text-slate-700 text-sm">🌍 قوانین پیش‌فرض کشورها (روی همه شرکت‌ها اعمال می‌شود)</h3>
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

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 bg-slate-50">
            <h3 class="font-bold text-slate-700 text-sm">🏢 استثناهای ثبت شده برای شرکت‌های خاص</h3>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
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
        document.querySelector('select[name="company_id"]').value = companyId;
        document.querySelector('select[name="country_id"]').value = countryId;
        document.querySelector('input[name="max_limit"]').value = maxLimit !== '' ? maxLimit : '';
        document.querySelector('input[name="reject_message"]').value = rejectMessage !== '-' ? rejectMessage : '';
    }

    function editGlobalRule(countryId, defaultQuota, rejectMessage) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        document.querySelector('select[name="company_id"]').value = ''; 
        document.querySelector('select[name="country_id"]').value = countryId;
        document.querySelector('input[name="max_limit"]').value = defaultQuota !== '' ? defaultQuota : '';
        document.querySelector('input[name="reject_message"]').value = rejectMessage !== '-' ? rejectMessage : '';
    }
</script>
@endsection