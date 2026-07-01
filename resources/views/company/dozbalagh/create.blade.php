@php
    // لیست کشورهای پرکاربرد لجستیک دوزوله
    $allWorldCountries = [
        'IR' => ['fa' => 'ایران', 'en' => 'Iran'],
        'TR' => ['fa' => 'ترکیه', 'en' => 'Turkey'],
        'RU' => ['fa' => 'روسیه', 'en' => 'Russia'],
        'AF' => ['fa' => 'افغانستان', 'en' => 'Afghanistan'],
        'PK' => ['fa' => 'پاکستان', 'en' => 'Pakistan'],
        'IQ' => ['fa' => 'عراق', 'en' => 'Iraq'],
        'TM' => ['fa' => 'ترکمنستان', 'en' => 'Turkmenistan'],
        'UZ' => ['fa' => 'ازبکستان', 'en' => 'Uzbekistan'],
        'TJ' => ['fa' => 'تاجیکستان', 'en' => 'Tajikistan'],
        'KG' => ['fa' => 'قرقیزستان', 'en' => 'Kyrgyzstan'],
        'KZ' => ['fa' => 'قزاقستان', 'en' => 'Kazakhstan'],
        'AZ' => ['fa' => 'آذربایجان', 'en' => 'Azerbaijan'],
        'AM' => ['fa' => 'ارمنستان', 'en' => 'Armenia'],
        'GE' => ['fa' => 'گرجستان', 'en' => 'Georgia'],
        'AE' => ['fa' => 'امارات متحده عربی', 'en' => 'United Arab Emirates'],
        'CN' => ['fa' => 'چین', 'en' => 'China'],
        'DE' => ['fa' => 'آلمان', 'en' => 'Germany'],
        'IT' => ['fa' => 'ایتالیا', 'en' => 'Italy'],
        'FR' => ['fa' => 'فرانسه', 'en' => 'France'],
        'BG' => ['fa' => 'بلغارستان', 'en' => 'Bulgaria'],
    ];
@endphp
@extends('layouts.app')

@section('header_title')
    ثبت درخواست / <span class="text-emerald-600 font-black">دوزوله جدید</span>
@endsection

@section('content')
<link href="{{ asset('assets/css/select2.min.css') }}" rel="stylesheet" />

@php
    $trackingCode = 'D' . now()->format('Ymd') . mt_rand(1000, 9999);
@endphp

<div class="max-w-4xl mx-auto">
    
    {{-- نوار پیشرفت مراحل (Timeline) --}}
    <div class="mb-8 bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex items-center justify-between relative">
            <div class="absolute left-0 top-1/2 transform -translate-y-1/2 w-full h-1 bg-slate-100 -z-10 rounded-full"></div>
            <div id="progressBar" class="absolute right-0 top-1/2 transform -translate-y-1/2 h-1 bg-emerald-500 -z-10 transition-all duration-500" style="width: 0%;"></div>
            
            @foreach(['راننده و ناوگان', 'کشورهای مقصد و مدارک', 'تایید و پرداخت'] as $index => $step)
                <div class="step-indicator flex flex-col items-center gap-2 relative bg-white px-2" data-step="{{ $index + 1 }}">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold border-2 transition-colors duration-300 {{ $index === 0 ? 'border-emerald-500 bg-emerald-500 text-white shadow-lg' : 'border-slate-200 bg-white text-slate-400' }}" id="indicator-{{ $index + 1 }}">
                        {{ $index + 1 }}
                    </div>
                    <span class="text-[10px] sm:text-xs font-bold {{ $index === 0 ? 'text-emerald-600' : 'text-slate-400' }}">{{ $step }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- فرم اصلی ارسال اطلاعات دوزوله --}}
    <form id="dozoulehForm" action="{{ route('dozbalagh.store') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
        @csrf
        <input type="hidden" name="tracking_code" value="{{ $trackingCode }}">
        
        <div id="step-1">
            @include('company.dozbalagh.partials.step1')
        </div>

        <div id="step-2" class="hidden">
            @include('company.dozbalagh.partials.step2')
        </div>

        <div id="step-3" class="hidden">
            @include('company.dozbalagh.partials.step3', ['trackingCode' => $trackingCode])
        </div>

        {{-- منوی دکمه‌های فوتر ناوبری فرم --}}
        <div class="bg-slate-50 p-4 sm:p-6 border-t border-slate-200 flex justify-between items-center">
            <button type="button" id="prevBtn" class="hidden px-6 py-2.5 rounded-xl font-bold text-slate-600 bg-white border border-slate-300 hover:bg-slate-100 transition" onclick="navigateWizard(-1)">
                مرحله قبل
            </button>
            <div class="flex-1"></div>
            <button type="button" id="nextBtn" class="px-8 py-2.5 rounded-xl font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-lg shadow-emerald-200 transition" onclick="navigateWizard(1)">
                مرحله بعد
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    window.DozoulehConfig = {
        walletBalance: Number("{{ $balance ?? 0 }}"), 
        chargeUrl: "{{ route('company.wallet.index') }}",
        checkFleetUrl: "{{ route('dozbalagh.check_fleet') }}",
        csrfToken: "{{ csrf_token() }}", 
        countriesList: @json($countries), 
        cargoRules: @json($cargoRules) 
    };
</script>
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('assets/js/dozouleh.js') }}"></script>
<script src="{{ asset('assets/js/dozouleh_wizard.js') }}?v={{ time() }}"></script>

<script>
    jQuery(document).ready(function() {
        
        // ۱. شکار پیام موفقیت و هدایت به جدول تاریخچه دوزوله
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'عملیات موفق',
                text: '{!! session('success') !!}',
                confirmButtonText: 'مشاهده لیست درخواست‌ها',
                confirmButtonColor: '#059669', 
                backdrop: `rgba(16, 185, 129, 0.1)` 
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "{{ route('dozbalagh.index') }}"; 
                }
            });
        @endif

        // ۲. شکار خطاهای بازگشتی بک‌بند (بررسی دوزوله فعال راننده و ناوگان)
        @if($errors->any())
            let errorMsg = ``;
            @foreach($errors->all() as $error)
                errorMsg += `<li>{{ $error }}</li>`;
            @endforeach

            // 🎯 بخش کلیدی هوشمند: هدایت خودکار فرم به گام سوم تایید برای نمایش درست پاپ‌آپ خطا
            if (typeof changeStep === "function") {
                // اگر قبلا به گام ۱ برگشته، آن را مستقیما به گام ۳ شلیک کن
                document.getElementById('step-1').classList.add('hidden');
                document.getElementById('step-2').classList.add('hidden');
                document.getElementById('step-3').classList.remove('hidden');
                currentStep = 3;
                if (typeof updateUI === "function") updateUI();
                if (typeof updateSummary === "function") updateSummary();
            } else if (typeof navigateWizard === "function") {
                // شبیه‌سازی حرکت برای اسکریپت‌های کاستوم ویزارد
                $('#step-1').addClass('hidden');
                $('#step-2').addClass('hidden');
                $('#step-3').removeClass('hidden');
                currentStep = 3;
                if (typeof updateUI === "function") updateUI();
                if (typeof updateSummary === "function") updateSummary();
            }

            Swal.fire({
                icon: 'error',
                title: 'خطا در تایید وضعیت دوزوله',
                html: `<ul class="text-rose-600 text-sm text-right list-disc pr-5 font-bold space-y-1">${errorMsg}</ul>`,
                confirmButtonText: 'اصلاح اطلاعات درخواست',
                confirmButtonColor: '#e11d48', 
            });
        @endif
        
    });
</script>
@endsection