@extends('layouts.app')

@section('header_title', 'تکمیل و تایید مشخصات شرکت')

@section('content')
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 max-w-4xl mx-auto text-right" dir="rtl">
    
    @if($company->status !== 'approved')
    <div class="bg-amber-50 border-r-4 border-amber-500 text-amber-800 p-4 rounded-md mb-6">
        <h3 class="font-bold text-lg mb-1">⚠️ نیاز به تکمیل و تایید اطلاعات</h3>
        <p class="text-sm text-justify">مدیر محترم، اطلاعات اولیه شما توسط مدیریت سامانه ثبت شده است. لطفاً فرم زیر را به دقت بررسی، اصلاح و تکمیل نمایید. پس از ذخیره، دسترسی شما به تمامی بخش‌های سامانه (پس از تایید نهایی مدیریت) باز خواهد شد.</p>
    </div>
    @endif

    @if(session('success'))
        <div class="bg-emerald-50 text-emerald-700 p-4 rounded-md mb-6 font-bold border border-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('company.profile.update') }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">شناسه ملی شرکت</label>
                <input type="text" value="{{ $company->national_id }}" disabled class="w-full bg-gray-100 border border-gray-300 rounded-md px-4 py-2 text-gray-500 cursor-not-allowed font-mono text-left" dir="ltr">
                <span class="text-xs text-gray-400 mt-1 block">شناسه ملی قابل تغییر نمی‌باشد.</span>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">موبایل مدیر عامل <span class="text-red-500">*</span></label>
                <input type="text" name="ceo_mobile" value="{{ old('ceo_mobile', $company->ceo_mobile) }}" required class="w-full border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-blue-500 font-mono text-left" dir="ltr">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">نام شرکت (فارسی) <span class="text-red-500">*</span></label>
                <input type="text" name="name_fa" value="{{ old('name_fa', $company->name_fa ?? $company->name) }}" required class="w-full border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">نام شرکت (لاتین / استاندارد دوزبلاغ) <span class="text-red-500">*</span></label>
                <input type="text" name="name_en" value="{{ old('name_en', $company->name_en) }}" required class="w-full border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-blue-500 text-left font-sans" dir="ltr">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">نام مدیر عامل <span class="text-red-500">*</span></label>
                <input type="text" name="ceo_name" value="{{ old('ceo_name', $company->ceo_name) }}" required class="w-full border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">تلفن شرکت</label>
                <input type="text" name="phone" value="{{ old('phone', $company->phone) }}" class="w-full border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-blue-500 font-mono text-left" dir="ltr">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-2">آدرس شرکت (فارسی) <span class="text-red-500">*</span></label>
                <textarea name="address_fa" required rows="2" class="w-full border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-blue-500 resize-none">{{ old('address_fa', $company->address_fa) }}</textarea>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-2">آدرس شرکت (لاتین / استاندارد دوزبلاغ) <span class="text-red-500">*</span></label>
                <textarea name="address_en" required rows="2" dir="ltr" class="w-full border border-gray-300 rounded-md px-4 py-2 text-left font-sans focus:ring-2 focus:ring-blue-500 resize-none">{{ old('address_en', $company->address_en) }}</textarea>
            </div>
        </div>

        <div class="mt-6 bg-yellow-50 border-r-4 border-yellow-400 p-4 rounded-md">
            <h3 class="text-sm font-bold text-yellow-800">هشدار و مسئولیت ثبت اطلاعات</h3>
            <p class="mt-1 text-sm text-yellow-700 text-justify">
                ثبت نام و آدرس به صورت لاتین (استاندارد دوزبلاغ) الزامی است. مسئولیت صحت املایی و استاندارد بودن متون لاتین جهت درج در سامانه دوزبلاغ مستقیماً بر عهده شرکت ثبت‌کننده می‌باشد.
            </p>
        </div>

        <div class="mt-8 flex justify-start gap-3">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-8 rounded shadow transition duration-150">
                ذخیره اطلاعات و ارسال برای تایید
            </button>
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-6 rounded transition duration-150">
                خروج موقت
            </a>
        </div>
    </form>
    
    <div id="password-section" class="mt-10 pt-6 border-t border-gray-200">
        <h3 class="text-lg font-bold text-gray-800 mb-4">🔐 تغییر رمز عبور</h3>
        
        <form action="{{ route('company.password.update') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @csrf
            @method('PUT')
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">رمز عبور فعلی</label>
                <input type="password" name="current_password" required class="w-full border border-gray-300 rounded-md px-4 py-2" dir="ltr">
                @error('current_password') <span class="text-xs text-red-500 font-bold">{{ $message }}</span> @enderror
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">رمز عبور جدید</label>
                <input type="password" name="password" required class="w-full border border-gray-300 rounded-md px-4 py-2" dir="ltr">
                @error('password') <span class="text-xs text-red-500 font-bold">{{ $message }}</span> @enderror
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">تکرار رمز عبور جدید</label>
                <input type="password" name="password_confirmation" required class="w-full border border-gray-300 rounded-md px-4 py-2" dir="ltr">
            </div>
            
            <div class="md:col-span-3 text-left mt-2">
                <button type="submit" class="bg-slate-600 hover:bg-slate-700 text-white font-bold py-2 px-6 rounded shadow transition duration-150">
                    تغییر رمز عبور
                </button>
            </div>
        </form>
    </div>

    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
        @csrf
    </form>

</div>
@endsection