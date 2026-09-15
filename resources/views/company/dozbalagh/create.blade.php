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
            'cits_code' => $firstItemDb->cits_code ?? $permitRequest->tracking_code ?? $permitRequest->d_code ?? '',
            // 🟢 اضافه شدن پارامترهای فرم‌های داینامیک مدارک به آبجکت جاوااسکریپت
            'trip_code' => $firstItemDb->trip_code ?? '',
            'cmr_date' => $firstItemDb->cmr_date ?? '',
            'tir_carnet_number' => $firstItemDb->tir_carnet_number ?? '',
            'tir_carnet_date' => $firstItemDb->tir_carnet_date ?? '',
            'receipt_code' => $firstItemDb->receipt_code ?? '',
            'receipt_amount' => $firstItemDb->receipt_amount ?? '',
            'destinations' => $destinationsArray
        ];
    }
@endphp
@php
    $requestWindowStatus = $requestWindowStatus ?? ['allowed' => true, 'message' => null, 'settings' => []];
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
<div class="mx-auto max-w-7xl space-y-5" dir="rtl">
    <div class="space-y-0">
        <section class="company-page-header rounded-t-2xl px-5 py-4 text-white shadow-lg sm:px-6 sm:py-5">
            <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3.5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white bg-opacity-15 ring-1 ring-white ring-opacity-20">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3.75h7.5L19 8.25v12H7a2 2 0 0 1-2-2v-12.5a2 2 0 0 1 2-2Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3.75v4.5h4.5M12 11v6M9 14h6"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-black sm:text-2xl">{{ isset($permitRequest) ? 'اصلاح درخواست دوزوله' : 'ثبت درخواست دوزوله جدید' }}</h1>
                        <p class="mt-1 text-xs font-semibold text-emerald-100">انتخاب راننده و ناوگان، تکمیل مقصد و مدارک، سپس بازبینی و پرداخت</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-2 rounded-xl border border-white border-opacity-10 bg-white bg-opacity-10 px-4 py-2 text-xs font-black text-emerald-50">
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-white bg-opacity-15">۳</span>
                        فرایند سه‌مرحله‌ای
                    </span>
                    <a href="{{ route('dozbalagh.index') }}" class="company-header-back-link gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6"/></svg>
                        بازگشت به پرونده‌ها
                    </a>
                </div>
            </div>
        </section>

        {{-- نوار پیشرفت مراحل --}}
        <section class="relative overflow-hidden rounded-b-2xl border border-t-0 border-slate-200 bg-white px-4 py-4 shadow-sm sm:px-8">
            <div class="absolute left-8 right-8 top-9 h-1 rounded-full bg-slate-100">
                <div id="progressBar" class="absolute right-0 top-0 h-1 w-0 rounded-full bg-emerald-500 transition-all duration-500"></div>
            </div>
            <div class="relative z-10 flex items-start justify-between">
                @foreach([
                    ['title' => 'راننده و ناوگان', 'subtitle' => 'انتخاب متقاضی'],
                    ['title' => 'کشورها و مدارک', 'subtitle' => 'تکمیل مسیر و مستندات'],
                    ['title' => 'تأیید و پرداخت', 'subtitle' => 'بازبینی و ثبت نهایی'],
                ] as $index => $step)
                    <div class="step-indicator flex flex-1 flex-col items-center gap-1.5 px-1 text-center" data-step="{{ $index + 1 }}">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full border-2 font-black transition-all duration-300 {{ $index === 0 ? 'border-emerald-500 bg-emerald-500 text-white shadow-lg shadow-emerald-200 ring-4 ring-emerald-100' : 'border-slate-200 bg-white text-slate-400' }}" id="indicator-{{ $index + 1 }}">
                            {{ $index + 1 }}
                        </div>
                        <span id="indicator-text-{{ $index + 1 }}" class="text-[10px] font-black transition-colors sm:text-xs {{ $index === 0 ? 'text-emerald-700' : 'text-slate-400' }}">{{ $step['title'] }}</span>
                        <span class="hidden text-[10px] font-medium text-slate-400 sm:block">{{ $step['subtitle'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    @if(!$requestWindowStatus['allowed'])
        <div class="mx-auto max-w-5xl rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold leading-7 text-rose-700">
            {{ $requestWindowStatus['message'] }}
        </div>
    @endif

    {{-- هوشمندسازی اکشن فرم برای سوئیچ بین ذخیره جدید یا آپدیت اصلاحیه --}}
    <form id="dozoulehForm" action="{{ isset($permitRequest) ? route('dozbalagh.update', $permitRequest->id) : route('dozbalagh.store') }}" method="POST" enctype="multipart/form-data" class="mx-auto w-full max-w-5xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm {{ !$requestWindowStatus['allowed'] && !isset($permitRequest) ? 'opacity-60 pointer-events-none' : '' }}">
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

        {{-- فیلدهای قطعی برای ارسال نوع درخواست به store --}}
        <input type="hidden" id="final_request_type" name="request_type" value="new">
        <input type="hidden" id="final_previous_dozouleh_number" name="previous_dozouleh_number" value="">

        {{-- منوی دکمه‌های فوتر ناوبری فرم --}}
        <div class="bg-slate-50 p-4 sm:p-6 border-t border-slate-200 flex justify-between items-center">
            <button type="button" id="prevBtn" class="hidden px-6 py-2.5 rounded-xl font-bold text-slate-600 bg-white border border-slate-300 hover:bg-slate-100 transition" onclick="navigateWizard(-1)">
                مرحله قبل
            </button>
            <div class="flex-1"></div>
            <button type="button" id="nextBtn" class="px-8 py-2.5 rounded-xl font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-lg shadow-emerald-200 transition" onclick="navigateWizard(1)" @disabled(!$requestWindowStatus['allowed'] && !isset($permitRequest))>
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
        isAssociationMember: @json((bool) ($isAssociationMember ?? false)),
        membershipLabel: @json(($isAssociationMember ?? false) ? 'عضو انجمن' : 'غیرعضو انجمن'),
        cargoRules: @json($cargoRules),
        isEditMode: @json(isset($permitRequest)),
        editData: @json($jsEditData)
    };
</script>
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('assets/js/dozouleh.js') }}?v={{ time() }}"></script>
<script src="{{ asset('assets/js/dozouleh_wizard.js') }}?v={{ time() }}"></script>

<script>
    // همگام‌سازی قطعی اطلاعات تمدید قبل از submit
    (function syncRenewalFieldsBeforeSubmit() {
        function syncFields() {
            var finalType = document.getElementById('final_request_type');
            var finalPrevious = document.getElementById('final_previous_dozouleh_number');
            var visiblePrevious = document.getElementById('previous_dozouleh_number');
            var checkedType = document.querySelector('input[name="request_type"]:checked');

            if (!finalType || !finalPrevious) return;

            var previousValue = visiblePrevious ? (visiblePrevious.value || '').trim() : '';

            if (!previousValue && window.renewalReference) {
                previousValue = (window.renewalReference.d_code || window.renewalReference.serial_number || '').toString().trim();
            }

            if (previousValue !== '') {
                finalType.value = 'renewal';
                finalPrevious.value = previousValue;
            } else {
                finalType.value = checkedType && checkedType.value === 'renewal' ? 'renewal' : 'new';
                finalPrevious.value = '';
            }
        }

        window.syncRenewalFinalFields = syncFields;

        document.addEventListener('input', function(e) {
            if (e.target && e.target.id === 'previous_dozouleh_number') syncFields();
        });

        document.addEventListener('change', function(e) {
            if (e.target && (e.target.id === 'previous_dozouleh_number' || e.target.name === 'request_type')) syncFields();
        });

        document.addEventListener('DOMContentLoaded', function() {
            syncFields();
            var form = document.getElementById('dozoulehForm');
            if (form) {
                form.addEventListener('submit', function() {
                    syncFields();
                }, true);
            }
        });

        setTimeout(syncFields, 300);
        setTimeout(syncFields, 1000);
    })();
</script>

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
