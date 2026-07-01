@extends('layouts.admin')

@section('content')
<div class="p-6 relative">
    
    @if ($errors->any())
        <div class="bg-red-50 border-r-4 border-red-500 p-4 mb-6 rounded-md shadow-sm">
            <div class="flex items-start">
                <div class="mr-3">
                    <h3 class="text-sm font-bold text-red-800">خطا در ویرایش اطلاعات:</h3>
                    <ul class="list-disc pr-5 mt-2 text-sm text-red-700 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl mx-auto border border-gray-100" dir="rtl">
        
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50 rounded-t-lg">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                ویرایش مشخصات شرکت: <span class="text-indigo-600">{{ $company->name_fa ?? $company->name }}</span>
            </h3>
            <a href="{{ route('admin.companies.index') }}" class="text-sm font-bold text-gray-500 hover:text-gray-800 transition duration-150">
                &larr; بازگشت به لیست
            </a>
        </div>
        
        <form action="{{ route('admin.companies.update', $company->id) }}" method="POST" class="p-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">شناسه ملی شرکت</label>
                    <input type="text" name="national_id" value="{{ old('national_id', $company->national_id) }}" required 
                           minlength="11" maxlength="11" 
                           oninput="this.value = this.value.replace(/[^0-9]/g, '');" 
                           class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono text-left bg-gray-50" readonly title="شناسه ملی قابل تغییر نیست">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">تلفن شرکت</label>
                    <input type="text" name="phone" value="{{ old('phone', $company->phone) }}" required class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-left font-mono">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">نام شرکت (فارسی)</label>
                    <input type="text" id="fa_name" name="name" value="{{ old('name', $company->name_fa ?? $company->name) }}" required class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">نام شرکت (فینگلیش)</label>
                    <input type="text" id="en_name" name="name_en" value="{{ old('name_en', $company->name_en) }}" required dir="ltr" class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-left font-sans">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">مدیر عامل شرکت</label>
                    <input type="text" name="ceo_name" value="{{ old('ceo_name', $company->ceo_mobile) }}" required class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="md:col-span-2">
    <label class="block text-sm font-semibold text-gray-700 mb-2">موبایل مدیر عامل</label>
    <input type="text" name="ceo_mobile" value="{{ old('ceo_mobile', $company->ceo_mobile) }}" required 
           class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono text-left">
</div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">آدرس شرکت (فینگلیش)</label>
                    <textarea id="en_address" name="address_en" required rows="2" dir="ltr" class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none text-left font-sans">{{ old('address_en', $company->address_en) }}</textarea>
                </div>
            </div>

            <div class="mt-8 pt-4 border-t border-gray-200 flex justify-start gap-2 flex-row-reverse">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-8 rounded shadow transition duration-150">
                    بروزرسانی اطلاعات
                </button>
                <a href="{{ route('admin.companies.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-6 rounded transition duration-150 text-center">
                    انصراف
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    function convertToFinglishPro(text) {
        if (!text) return '';
        let finglish = text
            .replace(/شرکت/g, 'Sherkat').replace(/حمل و نقل/g, 'Haml o Naghl')
            .replace(/بین المللی/g, 'Beynolmelali').replace(/تعاونی/g, 'Taavoni')
            .replace(/باربری/g, 'Barbari').replace(/ستاره/g, 'Setareh').replace(/شهر/g, 'Shahr')
            .replace(/استان/g, 'Ostan').replace(/مشهد/g, 'Mashhad').replace(/تهران/g, 'Tehran')
            .replace(/بلوار/g, 'Bolvar').replace(/خیابان/g, 'Khiaban').replace(/میدان/g, 'Meydan')
            .replace(/کوچه/g, 'Koocheh').replace(/پلاک/g, 'Pelak').replace(/وکیل آباد/g, 'Vakil Abad')
            .replace(/انتهای/g, 'Entehaye').replace(/طبقه/g, 'Tabagheh').replace(/واحد/g, 'Vahed');

        finglish = finglish
            .replace(/آ/g, 'a').replace(/ا/g, 'a').replace(/ب/g, 'b').replace(/پ/g, 'p')
            .replace(/ت/g, 't').replace(/ث/g, 's').replace(/ج/g, 'j').replace(/چ/g, 'ch')
            .replace(/ح/g, 'h').replace(/خ/g, 'kh').replace(/د/g, 'd').replace(/ذ/g, 'z')
            .replace(/ر/g, 'r').replace(/ز/g, 'z').replace(/ژ/g, 'zh').replace(/س/g, 's')
            .replace(/ش/g, 'sh').replace(/ص/g, 's').replace(/ض/g, 'z').replace(/ط/g, 't')
            .replace(/ظ/g, 'z').replace(/ع/g, 'a').replace(/غ/g, 'gh').replace(/ف/g, 'f')
            .replace(/ق/g, 'gh').replace(/ک/g, 'k').replace(/گ/g, 'g').replace(/ل/g, 'l')
            .replace(/م/g, 'm').replace(/ن/g, 'n').replace(/و/g, 'v').replace(/ه/g, 'h')
            .replace(/ی/g, 'i').replace(/ئ/g, 'y').replace(/،/g, ',');
            
        return finglish;
    }

    document.getElementById('fa_name').addEventListener('input', function() {
        document.getElementById('en_name').value = convertToFinglishPro(this.value);
    });

    document.getElementById('fa_address').addEventListener('input', function() {
        document.getElementById('en_address').value = convertToFinglishPro(this.value);
    });
</script>
@endsection