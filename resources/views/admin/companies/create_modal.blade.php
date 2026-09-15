<div id="companyModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-3 md:p-5">
    <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" onclick="closeCompanyModal()"></div>
    <div class="relative z-10 flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white text-right shadow-2xl" dir="rtl">
        <div class="flex shrink-0 items-center justify-between border-b border-slate-200 px-5 py-4">
            <div><h3 class="text-lg font-black text-slate-800">ثبت دستی شرکت جدید</h3><p class="mt-1 text-xs text-slate-500">نام کاربری شرکت به‌صورت خودکار همان شناسه ملی خواهد بود.</p></div>
            <button type="button" onclick="closeCompanyModal()" class="rounded-lg p-2 text-2xl font-bold text-slate-400 hover:bg-slate-100 hover:text-slate-700">&times;</button>
        </div>

        <form action="{{ route('admin.companies.store') }}" method="POST" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                @if($errors->any())
                    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700"><ul class="list-disc space-y-1 pr-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif

                <div class="mb-5 rounded-xl border border-blue-100 bg-blue-50 p-4">
                    <h4 class="font-black text-blue-900">هویت و عضویت شرکت</h4>
                    <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <label class="text-sm font-bold text-slate-700">شناسه ملی / نام کاربری <span class="text-rose-500">*</span>
                            <input name="national_id" value="{{ old('national_id') }}" required minlength="11" maxlength="11" inputmode="numeric" dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-left font-mono" placeholder="۱۱ رقم">
                        </label>
                        <label class="text-sm font-bold text-slate-700">وضعیت عضویت <span class="text-rose-500">*</span>
                            <select name="is_association_member" required class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5">
                                <option value="1" @selected(old('is_association_member', '1') === '1')>عضو انجمن</option>
                                <option value="0" @selected(old('is_association_member') === '0')>غیرعضو</option>
                            </select>
                        </label>
                        <label class="text-sm font-bold text-slate-700">نام رسمی فارسی <span class="text-rose-500">*</span>
                            <input name="name" value="{{ old('name') }}" required class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5" placeholder="نام کامل شرکت">
                        </label>
                        <label class="text-sm font-bold text-slate-700">نام لاتین <span class="text-rose-500">*</span>
                            <input name="name_en" value="{{ old('name_en') }}" required dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-left" placeholder="English Company Name">
                        </label>
                        <label class="text-sm font-bold text-slate-700">شماره ثبت <span class="text-rose-500">*</span>
                            <input name="registration_number" value="{{ old('registration_number') }}" required dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-left">
                        </label>
                        <label class="text-sm font-bold text-slate-700">نوع فعالیت <span class="text-rose-500">*</span>
                            <input name="activity_type" value="{{ old('activity_type') }}" required class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5" placeholder="حمل‌ونقل بین‌المللی کالا">
                        </label>
                    </div>
                </div>

                <div class="mb-5 rounded-xl border border-slate-200 p-4">
                    <h4 class="font-black text-slate-800">مدیرعامل و اطلاعات تماس</h4>
                    <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <label class="text-sm font-bold text-slate-700">نام مدیرعامل <span class="text-rose-500">*</span><input name="ceo_name" value="{{ old('ceo_name') }}" required class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
                        <label class="text-sm font-bold text-slate-700">کد ملی مدیرعامل <span class="text-rose-500">*</span><input name="ceo_national_code" value="{{ old('ceo_national_code') }}" required maxlength="10" inputmode="numeric" dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-left font-mono"></label>
                        <label class="text-sm font-bold text-slate-700">موبایل مدیرعامل <span class="text-rose-500">*</span><input name="ceo_mobile" value="{{ old('ceo_mobile') }}" required maxlength="11" inputmode="numeric" dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-left font-mono" placeholder="091xxxxxxxx"></label>
                        <label class="text-sm font-bold text-slate-700">تلفن شرکت <span class="text-rose-500">*</span><input name="phone" value="{{ old('phone') }}" required dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-left font-mono"></label>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                    <h4 class="font-black text-slate-800">نشانی شرکت</h4>
                    <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <label class="text-sm font-bold text-slate-700">استان <span class="text-rose-500">*</span><input name="province" value="{{ old('province') }}" required class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
                        <label class="text-sm font-bold text-slate-700">شهر <span class="text-rose-500">*</span><input name="city" value="{{ old('city') }}" required class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
                        <label class="text-sm font-bold text-slate-700">کدپستی <span class="text-rose-500">*</span><input name="postal_code" value="{{ old('postal_code') }}" required maxlength="10" inputmode="numeric" dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-left font-mono"></label>
                        <label class="text-sm font-bold text-slate-700 md:col-span-3">آدرس فارسی <span class="text-rose-500">*</span><textarea name="address_fa" required rows="2" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5">{{ old('address_fa') }}</textarea></label>
                        <label class="text-sm font-bold text-slate-700 md:col-span-3">آدرس لاتین <span class="text-rose-500">*</span><textarea name="address_en" required rows="2" dir="ltr" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-left">{{ old('address_en') }}</textarea></label>
                    </div>
                </div>
            </div>

            <div class="flex shrink-0 flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4">
                <p class="text-xs text-slate-500">رمز اولیه: <b dir="ltr">12345678</b> — شرکت در اولین ورود باید اطلاعات را کنترل و برای انجمن ارسال کند.</p>
                <div class="flex gap-2"><button type="button" onclick="closeCompanyModal()" class="rounded-lg bg-slate-200 px-5 py-2.5 font-bold text-slate-700">انصراف</button><button class="rounded-lg bg-blue-600 px-6 py-2.5 font-black text-white">ثبت شرکت و ساخت حساب</button></div>
            </div>
        </form>
    </div>
</div>

<script>
function openCompanyModal(){document.getElementById('companyModal').classList.remove('hidden');document.getElementById('companyModal').classList.add('flex');document.body.style.overflow='hidden';}
function closeCompanyModal(){document.getElementById('companyModal').classList.add('hidden');document.getElementById('companyModal').classList.remove('flex');document.body.style.overflow='auto';}
@if($errors->any()) document.addEventListener('DOMContentLoaded', openCompanyModal); @endif
</script>
