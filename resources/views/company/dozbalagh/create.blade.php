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
        'TJ' => ['fa' => 'تاجیکستان', 'en' => 'Transit'],
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

    // 🟢 آماده‌سازی کاملاً دقیق داده‌های اصلاحیه 
    $jsEditData = null;
    if (isset($permitRequest)) {
        
        // ۱. استخراج مستقیم نوع بار از دیتابیس برای جلوگیری از باگ‌های کنترلر
        $firstItemDb = \Illuminate\Support\Facades\DB::table('permit_request_items')
            ->where('permit_request_id', $permitRequest->id)
            ->first();
            
        $cargoTypeVal = $firstItemDb ? $firstItemDb->operation_type : '';
        
        // ۲. تبدیل و واکشی مقاصد قبلی
        $destinationsArray = isset($previousDestinations) ? (is_array($previousDestinations) ? $previousDestinations : (is_object($previousDestinations) ? $previousDestinations->toArray() : [])) : [];
        $firstItem = !empty($destinationsArray) ? (array) $destinationsArray[0] : null;
        
        // ۳. پیدا کردن نام فارسی کشور مقصد 
        $destCountryId = $firstItem ? ($firstItem['country_id'] ?? null) : null;
        $destCountryFa = '';
        if ($destCountryId) {
            $dbCountry = \App\Models\Country::find($destCountryId);
            $destCountryFa = $dbCountry ? $dbCountry->name : ''; 
        }

        $jsEditData = [
            'cargo_type' => $cargoTypeVal, 
            'loading_origin' => 'ایران', 
            'loading_destination' => $destCountryFa, 
            'cits_code' => $permitRequest->tracking_code ?? $permitRequest->d_code ?? '', 
            // 🟢 اضافه شدن پارامترهای فرم‌های داینامیک مدارک به آبجکت جاوااسکریپت
            'trip_code' => $permitRequest->trip_code ?? '',
            'cmr_date' => $permitRequest->cmr_date ?? '',
            'tir_carnet_number' => $permitRequest->tir_carnet_number ?? '',
            'tir_carnet_date' => $permitRequest->tir_carnet_date ?? '',
            'receipt_code' => $permitRequest->receipt_code ?? '',
            'receipt_amount' => $permitRequest->receipt_amount ?? '',
            'destinations' => $destinationsArray
        ];
    }
@endphp
@extends('layouts.app')

@section('header_title')
    @if(isset($permitRequest))
        اصلاح درخواست / <span class="text-orange-600 font-black">ویرایش مدارک دوزوله</span>
    @else
        ثبت درخواست / <span class="text-emerald-600 font-black">دوزوله جدید</span>
    @endif
@endsection

@section('content')
<div class="max-w-4xl mx-auto">
    
    {{-- نوار پیشرفت مراحل (Timeline) --}}
    <div class="mb-8 bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex items-center justify-between relative">
            <div class="absolute left-0 top-0 w-32 h-32 bg-indigo-500/5 rounded-full blur-2xl pointer-events-none"></div>
            <div id="progressBar" class="absolute right-0 top-1/2 transform -translate-y-1/2 w-full h-1 bg-slate-100 -z-10 rounded-full"></div>
            
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

    {{-- هوشمندسازی اکشن فرم برای سوئیچ بین ذخیره جدید یا آپدیت اصلاحیه --}}
    <form id="dozoulehForm" action="{{ isset($permitRequest) ? route('dozbalagh.update', $permitRequest->id) : route('dozbalagh.store') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
        @csrf
        @if(isset($permitRequest))
            @method('PUT')
        @endif
        <input type="hidden" name="tracking_code" value="{{ isset($permitRequest) ? $permitRequest->d_code : ('D' . now()->format('Ymd') . mt_rand(1000, 9999)) }}">
        
        <div id="step-1">
            @include('company.dozbalagh.partials.step1')
        </div>

        <div id="step-2" class="hidden">
            @include('company.dozbalagh.partials.step2')
        </div>

        <div id="step-3" class="hidden">
            @include('company.dozbalagh.partials.step3', ['trackingCode' => isset($permitRequest) ? $permitRequest->d_code : ''])
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
        renewableListUrl: "{{ url('/dozbalagh/renewable-list') }}",
        csrfToken: "{{ csrf_token() }}", 
        countriesList: @json($countries), 
        cargoRules: @json($cargoRules),
        isEditMode: @json(isset($permitRequest)),
        editData: @json($jsEditData)
    };
</script>
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script>
    if (!window.jQuery) {
        document.write('<script src="https://code.jquery.com/jquery-3.7.1.min.js"><\/script>');
    }
</script>
<script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('assets/js/dozouleh.js') }}?v={{ time() }}"></script>
<script src="{{ asset('assets/js/dozouleh_wizard.js') }}?v={{ time() }}"></script>

<script>
    (function waitForJQuery(){
        if (!window.jQuery) { setTimeout(waitForJQuery, 50); return; }
        jQuery(document).ready(function() {
            let $jq = jQuery;
        
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

        @if($errors->any())
            let errorMsg = ``;
            @foreach($errors->all() as $error)
                errorMsg += `<li>{{ $error }}</li>`;
            @endforeach

            if (typeof changeStep === "function") {
                document.getElementById('step-1').classList.add('hidden');
                document.getElementById('step-2').classList.add('hidden');
                document.getElementById('step-3').classList.remove('hidden');
                currentStep = 3;
                if (typeof updateUI === "function") updateUI();
                if (typeof updateSummary === "function") updateSummary();
            } else if (typeof navigateWizard === "function") {
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

        @if(isset($permitRequest) && $permitRequest->status === 'returned')
            function jumpToStepTwo() {
                $jq('#driver_id').val("{{ $permitRequest->driver_id }}").trigger('change').trigger('change.select2');
                $jq('#fleet_id').val("{{ $permitRequest->fleet_id }}").trigger('change').trigger('change.select2');
                
                setTimeout(function() {
                    if (typeof navigateWizard === "function") {
                        navigateWizard(1);
                    } else {
                        $('#step-1').addClass('hidden');
                        $('#step-2').removeClass('hidden');
                        $('#prevBtn').removeClass('hidden');
                        currentStep = 2;
                    }

                    let data = window.DozoulehConfig.editData;
                    if (data) {
                        setTimeout(function() {
                            // ست کردن منوها
                            if (data.cargo_type) {
                                $jq('#main_cargo_type').val(data.cargo_type).trigger('change');
                                let nativeSelect = document.getElementById('main_cargo_type');
                                if (nativeSelect) {
                                    nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                                }
                            }
                            if (data.loading_origin) {
                                $jq('#loading_origin').val(data.loading_origin).trigger('change').trigger('change.select2');
                            }
                            if (data.loading_destination) {
                                $jq('#loading_destination').val(data.loading_destination).trigger('change').trigger('change.select2');
                            }
                            if (data.cits_code) {
                                $jq('input[name="cits_code"]').val(data.cits_code).trigger('input').trigger('change');
                            }

                            // 🟢 پر کردن اتوماتیک فیلدهای متنی باکس‌های CMR، کارنه تیر و فیش‌ها
                            if (data.trip_code) $jq('input[name="trip_code"]').val(data.trip_code);
                            if (data.cmr_date) $jq('input[name="cmr_date"]').val(data.cmr_date);
                            if (data.tir_carnet_number) $jq('input[name="tir_carnet_number"]').val(data.tir_carnet_number);
                            if (data.tir_carnet_date) $jq('input[name="tir_carnet_date"]').val(data.tir_carnet_date);
                            if (data.receipt_code) $jq('input[name="receipt_code"]').val(data.receipt_code);
                            if (data.receipt_amount) $jq('input[name="receipt_amount"]').val(data.receipt_amount).trigger('input'); // تریگر برای اعمال جداکننده هزارگان

                        }, 400);

                        // بازسازی خودکار ردیف‌های کشورهای مقصد و عبوری
                        if (data.destinations && data.destinations.length > 0) {
                            data.destinations.forEach(function(dest, index) {
                                let addBtn = $jq('#add_destination_btn');
                                if (addBtn.length && index > 0) {
                                    addBtn.click();
                                }
                                
                                $jq(document).ready(function() {
                                    setTimeout(function() {
                                        let row = $jq('.country-row[data-index="' + index + '"]');
                                        if (row.length || index === 0) {
                                            $jq('select[name="destinations['+index+'][country_id]"]').val(dest.country_id).trigger('change').trigger('change.select2');
                                            
                                            setTimeout(function() {
                                                $jq('select[name="destinations['+index+'][permit_type]"]').val(dest.permit_type).trigger('change').trigger('change.select2');
                                            }, 200);
                                        }
                                    }, 400 * index);
                                });
                            });
                        }
                    }
                }, 500);
            }
            jumpToStepTwo();
        @endif
        
        });
    })();
</script>
@endsection