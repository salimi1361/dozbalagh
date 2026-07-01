@extends('layouts.company')
@section('content')
<div style="background: red; color: white; padding: 20px; font-size: 20px; text-align: center;">
    این یک تست است - اگر این را می‌بینی، یعنی فایل جدید لود شده است!
</div>

@section('header_title', 'ثبت درخواست مجوز (دوزبلاغ)')

@section('content')
<div class="max-w-4xl mx-auto bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="bg-slate-900 text-white p-6 text-center">
        <h2 class="text-xl font-bold">فرم صدور مجوز جدید</h2>
        <p class="text-slate-400 text-sm mt-2">لطفاً مراحل زیر را با دقت تکمیل نمایید.</p>
    </div>

    <div class="flex justify-between items-center px-10 py-6 border-b border-slate-100 bg-slate-50">
        <div class="step-indicator text-center w-1/4 font-bold text-emerald-600" id="indicator-1">۱. انتخاب کشور</div>
        <div class="step-indicator text-center w-1/4 font-bold text-slate-400" id="indicator-2">۲. راننده و ناوگان</div>
        <div class="step-indicator text-center w-1/4 font-bold text-slate-400" id="indicator-3">۳. مدارک</div>
        <div class="step-indicator text-center w-1/4 font-bold text-slate-400" id="indicator-4">۴. تایید مالی</div>
    </div>

    <div class="p-8">
        @if($errors->any())
            <div class="bg-rose-50 text-rose-600 p-4 rounded-xl mb-6 border border-rose-200 font-bold">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>- {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('dozbalagh.store') }}" method="POST" enctype="multipart/form-data" id="wizardForm">
            @csrf

            <div class="step-content block" id="step-1">
                <h3 class="font-bold text-lg text-slate-800 mb-4">مرحله اول: مقصد و نوع مجوز</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">کشور مقصد</label>
                        <select name="country_id" id="country_id" class="w-full border-slate-300 rounded-xl p-3 bg-slate-50 focus:ring-emerald-500 focus:border-emerald-500" onchange="updatePrice()">
                            <option value="">انتخاب کنید...</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" data-price="{{ $country->price }}">{{ $country->name }} (قیمت: {{ number_format($country->price) }} ریال)</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">نوع مجوز</label>
                        <select name="permit_type" class="w-full border-slate-300 rounded-xl p-3 bg-slate-50">
                            <option value="یکبار ورود">یکبار ورود</option>
                            <option value="ترانزیت">ترانزیت</option>
                            <option value="دوجانبه">دوجانبه</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="step-content hidden" id="step-2">
                <h3 class="font-bold text-lg text-slate-800 mb-4">مرحله دوم: اطلاعات حامل</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">انتخاب راننده</label>
                        <select name="driver_id" class="w-full border-slate-300 rounded-xl p-3 bg-slate-50">
                            <option value="">انتخاب کنید...</option>
                            @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}">{{ $driver->first_name }} {{ $driver->last_name }} ({{ $driver->national_id }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">انتخاب ناوگان (کامیون)</label>
                        <select name="fleet_id" class="w-full border-slate-300 rounded-xl p-3 bg-slate-50">
                            <option value="">انتخاب کنید...</option>
                            @foreach($fleets as $fleet)
                                <option value="{{ $fleet->id }}">پلاک: {{ $fleet->plate_number }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="step-content hidden" id="step-3">
                <h3 class="font-bold text-lg text-slate-800 mb-4">مرحله سوم: بارگذاری مدارک</h3>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">تصویر بارنامه (اختیاری)</label>
                    <input type="file" name="waybill_file" class="w-full border border-slate-300 rounded-xl p-3 bg-slate-50" accept=".jpg,.jpeg,.png,.pdf">
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-bold text-slate-700 mb-2">توضیحات تکمیلی (اختیاری)</label>
                    <textarea name="description" rows="3" class="w-full border-slate-300 rounded-xl p-3 bg-slate-50"></textarea>
                </div>
            </div>

            <div class="step-content hidden" id="step-4">
                <h3 class="font-bold text-lg text-slate-800 mb-4">مرحله نهایی: تایید و کسر وجه</h3>
                <div class="bg-emerald-50 rounded-2xl p-6 border border-emerald-200 text-center">
                    <p class="text-slate-600 mb-2">مبلغ قابل پرداخت برای این مجوز:</p>
                    <p class="text-3xl font-black text-emerald-700 font-mono mb-4" id="display-price">0 ریال</p>
                    
                    <div class="flex justify-center items-center gap-4 text-sm font-bold">
                        <span class="text-slate-500">موجودی فعلی شما:</span>
                        <span class="text-blue-600 font-mono">{{ number_format($availableBalance) }} ریال</span>
                    </div>
                </div>
            </div>

            <div class="mt-8 flex justify-between border-t border-slate-100 pt-6">
                <button type="button" id="prevBtn" class="hidden px-6 py-3 bg-slate-200 text-slate-700 rounded-xl font-bold hover:bg-slate-300 transition" onclick="changeStep(-1)">مرحله قبل</button>
                <div class="flex-1"></div>
                <button type="button" id="nextBtn" class="px-6 py-3 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-200" onclick="changeStep(1)">مرحله بعدی</button>
                <button type="submit" id="submitBtn" class="hidden px-8 py-3 bg-emerald-600 text-white rounded-xl font-bold hover:bg-emerald-700 transition shadow-lg shadow-emerald-200">ثبت نهایی و مسدودی وجه</button>
            </div>

        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let currentStep = 1;
    const totalSteps = 4;

    function changeStep(n) {
        // پنهان کردن تب فعلی
        document.getElementById(`step-${currentStep}`).classList.add('hidden');
        document.getElementById(`indicator-${currentStep}`).classList.remove('text-emerald-600');
        document.getElementById(`indicator-${currentStep}`).classList.add('text-slate-400');

        // آپدیت شماره تب
        currentStep += n;

        // نمایش تب جدید
        document.getElementById(`step-${currentStep}`).classList.remove('hidden');
        document.getElementById(`indicator-${currentStep}`).classList.remove('text-slate-400');
        document.getElementById(`indicator-${currentStep}`).classList.add('text-emerald-600');

        // کنترل دکمه‌ها
        document.getElementById('prevBtn').classList.toggle('hidden', currentStep === 1);
        document.getElementById('nextBtn').classList.toggle('hidden', currentStep === totalSteps);
        document.getElementById('submitBtn').classList.toggle('hidden', currentStep !== totalSteps);
    }

    function updatePrice() {
        const select = document.getElementById('country_id');
        const price = select.options[select.selectedIndex].getAttribute('data-price') || 0;
        document.getElementById('display-price').innerText = new Intl.NumberFormat('fa-IR').format(price) + ' ریال';
    }
</script>
@endsection