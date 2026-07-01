@extends('layouts.admin')

@section('header_title', 'مدیریت تردد و پیوست لاشه دوزبِلاغ')

@section('content')
<div class="space-y-6">
    <div class="bg-gradient-to-r from-slate-900 to-indigo-950 text-white rounded-2xl p-6 shadow-xl border border-slate-800">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-amber-500/10 text-amber-500 rounded-xl border border-amber-500/20 text-2xl">
                🚚
            </div>
            <div>
                <h2 class="text-lg font-black tracking-wide">کارتابل ناوگان در حال تردد (انتظار لاشه)</h2>
                <p class="text-slate-400 text-xs mt-1">لیست ناوگانی که در مسیر ترانزیت هستند. در این مرحله راننده قفل است و پس از بازگشت باید تعیین تکلیف شود.</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-bold text-sm text-slate-700 flex items-center gap-2">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                پروانه‌های در حال تردد فعال (<span id="request-count">{{ $requests->total() }}</span>)
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-100 text-slate-600 text-xs font-bold whitespace-nowrap">
                        <th class="p-4">سریال پروانه</th>
                        <th class="p-4">کد پرونده</th>
                        <th class="p-4">راننده متقاضی</th>
                        <th class="p-4 text-center">ناوگان / پلاک</th>
                        <th class="p-4">کشور مقصد</th>
                        <th class="p-4 text-center">اقدامات و تعیین تکلیف</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60 text-sm text-slate-700">
                    @forelse($requests as $index => $req)
                        <tr id="req-row-{{ $req->id }}" class="transition-all duration-200 {{ $index % 2 === 0 ? 'bg-white' : 'bg-slate-50/60' }} hover:bg-indigo-50/30">
                            
                            <td class="p-4 font-mono font-black text-indigo-600 text-sm">🎫 {{ $req->serial_number }}</td>
                            <td class="p-4 font-mono text-xs font-bold text-slate-500">{{ $req->d_code }}</td>
                            
                            <td class="p-4">
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-900">
                                        {{ $req->driver ? trim(($req->driver->first_name_fa ?? '') . ' ' . ($req->driver->last_name_fa ?? '')) : 'نامشخص' }}
                                    </span>
                                    <span class="text-slate-400 text-[11px]">کد ملی: {{ $req->driver->national_code ?? '---' }}</span>
                                </div>
                            </td>

                            <td class="p-4 text-center font-mono font-black text-xs text-slate-800">
                                {{ optional($req->fleet)->transit_plate ?? '---' }}
                            </td>

                            <td class="p-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-700 border border-emerald-100">
                                    {{ $req->country_name }}
                                </span>
                            </td>

                            <td class="p-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button onclick="window.open('/web/association/request/print/{{ $req->id }}', '_blank')" class="px-2 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition-all" title="باز کردن مجدد صفحه چاپ رسمی">
                                        🖨️ چاپ
                                    </button>

                                    <button onclick="processTransit({{ $req->id }}, 'collected', '{{ $req->serial_number }}')" class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-black transition-all shadow-sm">
                                        📥 تحویل لاشه
                                    </button>

                                    <button onclick="processTransit({{ $req->id }}, 'lost', '{{ $req->serial_number }}')" class="px-2.5 py-1.5 bg-rose-100 hover:bg-rose-200 text-rose-700 rounded-lg text-xs font-black transition-all">
                                        ⚠️ مفقودی
                                    </button>

                                    <button onclick="openExtensionModal({{ $req->id }}, '{{ $req->d_code }}')" class="px-2.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-slate-950 rounded-lg text-xs font-black transition-all shadow-sm">
                                        🔄 تمدید مسیر
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-12 text-center text-slate-400 bg-white">
                                <div class="text-4xl mb-3">🚚</div>
                                <p class="font-bold text-sm">در حال حاضر هیچ ناوگانی در حال تردد با پروانه‌های فعال نیست.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('assets/js/compressor.min.js') }}"></script>

<script>
function processTransit(id, status, serial) {
    if (status === 'lost') {
        // روال مفقودی ساده بدون نیاز به عکس
        Swal.fire({
            title: 'ثبت اعلام مفقودی پروانه',
            text: `در صورت ثبت مفقودی سریال ${serial}، راننده آزاد شده اما سوابق جریمه مفقودی در پرونده شرکت درج خواهد شد. تایید می‌کنید؟`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'تایید و ثبت مفقودی',
            cancelButtonText: 'انصراف'
        }).then((result) => {
            if (result.isConfirmed) {
                executeSettleRequest(id, status, null);
            }
        });
        return;
    }

    // 📥 روال تحویل لاشه همراه با آپلودر و فشرده‌ساز خودکار عکس
    Swal.fire({
        title: 'تحویل فیزیکی لاشه پروانه',
        html: `
            <div class="text-right space-y-3">
                <p class="text-xs text-slate-600 font-bold leading-relaxed">آیا از تحویل فیزیکی لاشه برگه به شماره سریال <b class="text-indigo-600 font-mono text-sm">${serial}</b> مطمئن هستید؟ لطفاً جهت بایگانی تصوير لاشه باطل شده را بارگذاری کنید:</p>
                <div class="mt-4 p-4 border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50 hover:bg-slate-100/70 transition-all relative flex flex-col items-center justify-center group">
                    <span class="text-3xl mb-1 group-hover:scale-110 transition-transform">📸</span>
                    <span class="text-[11px] font-black text-slate-500" id="file-label">انتخاب یا تصویربرداری از لاشه دوزبِلاغ</span>
                    <input type="file" id="swal-file" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full" onchange="handleFileChange(this)">
                </div>
                <div id="compress-loading" class="hidden text-center text-[11px] font-bold text-amber-600 animate-pulse">⚡ در حال فشرده‌سازی هوشمند تصاویر...</div>
            </div>
        `,
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        confirmButtonText: '📥 ثبت و آزادسازی ناوگان',
        cancelButtonText: 'انصراف',
        preConfirm: () => {
            const fileInput = document.getElementById('swal-file');
            if (!fileInput.files || fileInput.files.length === 0) {
                Swal.showValidationMessage('بارگذاری تصویر برگه لاشه الزامی است!');
                return false;
            }
            // بازگرداندن فایل پردازش شده نهایی
            return window.compressedFileBlob || fileInput.files[0];
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            executeSettleRequest(id, status, result.value);
        }
    });
}

// 🎯 تابع فشرده‌سازی عکس‌های چند مگابایتی به زیر ۲۰۰ کیلوبایت قبل از ارسال
function handleFileChange(input) {
    const file = input.files[0];
    if (!file) return;

    const label = document.getElementById('file-label');
    const loading = document.getElementById('compress-loading');
    
    label.innerText = `فایل انتخاب شد: ${file.name} (${(file.size/1024/1024).toFixed(2)} MB)`;
    loading.classList.remove('hidden');

    window.compressedFileBlob = null;

    // اجرای عملیات کمپرسور جاوااسکریپت به صورت لوکال
    new Compressor(file, {
        quality: 0.6,          // حفظ کیفیت تا ۶۰ درصد که کاملاً خواناست
        maxWidth: 1200,        // حداکثر عرض مجاز تصویر
        maxHeight: 1200,       // حداکثر ارتفاع مجاز تصویر
        success(result) {
            loading.classList.add('hidden');
            label.innerHTML = `✅ فشرده‌سازی موفق: <strong class="text-emerald-600 font-mono">${(result.size/1024).toFixed(0)} KB</strong>`;
            // ذخیره فایل فشرده شده در آبجکت ویندوز جهت ارسال نهایی
            window.compressedFileBlob = result;
        },
        error(err) {
            loading.classList.add('hidden');
            label.innerText = "❌ خطا در بهینه‌سازی عکس، فایل اصلی ارسال خواهد شد.";
            console.error(err.message);
        },
    });
}

// ارسال نهایی درخواست کسر و بایگانی لاشه با فرم‌دیتای واقعی (FormData)
function executeSettleRequest(id, status, fileBlob) {
    const formData = new FormData();
    formData.append('status', status);
    
    if (fileBlob) {
        // الحاق فایل فشرده شده به درخواست ارسالی
        formData.append('image', fileBlob, "collected_laashe.jpg");
    }

    // باز کردن لودینگ در حین آپلود
    Swal.fire({ title: 'در حال ثبت در انبار کل...', didOpen: () => { Swal.showLoading(); } });

    fetch(`/web/association/request/process/${id}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({ title: 'موفقیت‌آمیز', text: data.message, icon: 'success', timer: 2000, showConfirmButton: false });
            document.getElementById(`req-row-${id}`).remove();
        } else {
            Swal.fire({ title: 'خطا', text: data.message, icon: 'error', confirmButtonText: 'تایید' });
        }
    })
    .catch(err => {
        Swal.fire({ title: 'خطا', text: 'خطا در ارتباط با سرور مرکزی.', icon: 'error', confirmButtonText: 'تایید' });
    });
}
</script>
@endsection