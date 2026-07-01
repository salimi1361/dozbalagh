<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شرکت حمل‌و‌نقل | ثبت و استعلام راننده جدید</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        @import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css');
        body { font-family: 'Vazirmatn', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden">

    <!-- سایدبار پنل -->
    <aside class="w-64 bg-slate-900 text-slate-300 flex flex-col justify-between p-4 shrink-0 shadow-xl">
        <div>
            <div class="text-center pb-6 border-b border-slate-800 mb-6">
                <h2 class="text-white font-black text-base tracking-wide">پنل عملیاتی شرکت</h2>
                <span class="text-[10px] bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded-full font-bold mt-1 inline-block">دسترسی: شرکت حمل‌ونقل</span>
            </div>
            <nav class="space-y-1">
                <p class="text-[10px] font-bold text-slate-500 mr-2 mb-2 uppercase tracking-wider">ناوگان و رانندگان</p>
                <a href="/web/company/driver/create" class="flex items-center gap-3 p-3 rounded-xl bg-blue-600 text-white font-bold shadow-md shadow-blue-900/30 transition">
                    <span class="w-2 h-2 rounded-full bg-white"></span>
                    <span class="text-sm">ثبت راننده جدید</span>
                </a>
            </nav>
        </div>
    </aside>

    <!-- محتوای فرم -->
    <main class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white border-b border-slate-200 p-4 flex items-center justify-between shadow-sm">
            <div class="text-sm font-bold text-slate-800">مدیریت ناوگان / <span class="text-blue-600 font-black">پرونده و استعلام راننده</span></div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-8">
            <div class="max-w-2xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="bg-slate-950 p-6 text-white flex justify-between items-center">
                    <div>
                        <h1 class="text-base font-black">ثبت و استعلام راننده جدید ترانزیت</h1>
                        <p class="text-slate-400 text-[11px] mt-0.5">اطلاعات رسمی راهداری قفل شده؛ فیلدهای لاتین و پاسپورت را با حروف بزرگ انگلیسی وارد نمایید.</p>
                    </div>
                    <span id="status_badge" class="bg-slate-800 text-slate-400 text-[10px] font-bold px-2.5 py-1 rounded-lg">وضعیت: در انتظار استعلام</span>
                </div>

                <div id="msg_box" class="hidden p-4 text-xs font-bold border-b transition-all"></div>

                <div class="p-6 space-y-5">
                    <!-- باکس استعلام اصلی -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 bg-slate-50 p-4 rounded-xl border border-slate-200/60">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">کد ملی راننده</label>
                            <input type="text" id="national_id" maxlength="10" class="w-full p-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none bg-white font-mono" placeholder="0690116616">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">شماره موبایل</label>
                            <input type="text" id="mobile_number" maxlength="11" class="w-full p-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none bg-white text-left font-mono" placeholder="09157277001">
                        </div>
                        <div class="md:col-span-2 flex justify-end">
                            <button type="button" onclick="runInquiry()" id="btn_inquiry" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-5 py-2.5 rounded-xl text-xs shadow-sm transition cursor-pointer">🔍 شلیک و استعلام زنده</button>
                        </div>
                    </div>

                    <!-- فرم نهایی ذخیره در دیتابیس دزدبلاغ -->
                    <form id="driver_final_form" action="#" method="POST" class="space-y-5">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">نام راننده (رسمی راهداری)</label>
                                <input type="text" id="first_name" name="first_name" class="driver-field w-full p-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50" placeholder="نام">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">نام خانوادگی (رسمی راهداری)</label>
                                <input type="text" id="last_name" name="last_name" class="driver-field w-full p-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50" placeholder="نام خانوادگی">
                            </div>

                            <!-- فیلدهای فینگلیش با فیلتر اجباری انگلیسی بزرگ -->
                            <div>
                                <label class="block text-xs font-bold text-blue-700 mb-1.5">نام لاتین (مطابق پاسپورت - فقط انگلیسی بزرگ)</label>
                                <input type="text" id="first_name_en" name="first_name_en" class="english-only w-full p-2.5 border border-blue-200 rounded-xl text-sm bg-white text-left font-mono uppercase text-blue-600 font-bold focus:outline-blue-500" placeholder="MOHSEN">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-blue-700 mb-1.5">نام خانوادگی لاتین (مطابق پاسپورت - فقط انگلیسی بزرگ)</label>
                                <input type="text" id="last_name_en" name="last_name_en" class="english-only w-full p-2.5 border border-blue-200 rounded-xl text-sm bg-white text-left font-mono uppercase text-blue-600 font-bold focus:outline-blue-500" placeholder="SALMANI MOTLAGH">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">شماره گواهینامه رانندگی</label>
                                <input type="text" id="license_number" name="license_number" class="driver-field w-full p-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50 text-left font-mono" placeholder="شماره گواهینامه">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">وضعیت هوشمند راهداری</label>
                                <input type="text" id="is_active" name="is_active" class="driver-field w-full p-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50" placeholder="وضعیت هوشمند">
                            </div>

                            <!-- فیلد شماره پاسپورت دستی با فیلتر اجباری انگلیسی بزرگ -->
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-amber-800 mb-1.5">✍️ شماره پاسپورت راننده (ورود دستی شرکت - فقط انگلیسی و عدد)</label>
                                <input type="text" id="passport_number" name="passport_number" class="english-only w-full p-2.5 border border-amber-300 rounded-xl text-sm bg-white font-mono uppercase text-left focus:outline-amber-500" placeholder="e.g. A12345678">
                            </div>

                            <input type="hidden" id="final_national_id" name="national_id">
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex justify-end">
                            <button type="submit" id="btn_save" class="bg-green-600 hover:bg-green-700 text-white font-bold px-6 py-2.5 rounded-xl text-xs shadow-md transition cursor-pointer">🔒 ذخیره نهایی پرونده راننده</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        // 🛡️ لایه امنیتی جاوااسکریپت: مسدودسازی کاراکترهای غیر انگلیسی و تبدیل آنی به حروف بزرگ
        document.querySelectorAll('.english-only').forEach(input => {
            input.addEventListener('input', function(e) {
                let start = this.selectionStart;
                let end = this.selectionEnd;
                
                // جایگزین کردن هر چیزی غیر از حروف انگلیسی، اعداد و فاصله با خالی
                let validatedValue = this.value.replace(/[^a-zA-Z0-9 ]/g, '');
                
                // تبدیل به حروف بزرگ انگلیسی
                this.value = validatedValue.toUpperCase();
                
                // حفظ موقعیت مکان‌نما در فیلد جهت جلوگیری از پرش متن هنگام تایپ وسط کلمه
                this.setSelectionRange(start, end);
            });
        });

        function runInquiry() {
            let nationalId = document.getElementById('national_id').value;
            let mobileNumber = document.getElementById('mobile_number').value;
            let msgBox = document.getElementById('msg_box');
            let badge = document.getElementById('status_badge');
            let btn = document.getElementById('btn_inquiry');

            if(nationalId.length < 10 || mobileNumber.length < 11) {
                alert('لطفاً کد ملی ۱۰ رقمی و شماره موبایل ۱۱ رقمی را به صورت کامل وارد نمایید.');
                return;
            }

            btn.innerText = '⏳ در حال استعلام از سازمان...';
            btn.disabled = true;

            fetch('/web/company/driver/store', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ national_id: nationalId, mobile_number: mobileNumber })
            })
            .then(res => {
                if (!res.ok) throw new Error('Inquiry Failed');
                return res.json();
            })
            .then(res => {
                btn.innerText = '🔍 شلیک و استعلام زنده';
                btn.disabled = false;
                msgBox.className = "hidden p-4 text-xs font-bold border-b transition-all";

                if(res.success && res.driver) {
                    // مپ داده‌های رسمی و قفل شونده سرور راهداری
                    document.getElementById('first_name').value = res.driver.first_name;
                    document.getElementById('last_name').value = res.driver.last_name;
                    document.getElementById('license_number').value = res.driver.license_number;
                    document.getElementById('is_active').value = res.driver.is_active;
                    document.getElementById('final_national_id').value = res.driver.national_id;

                    // خالی گذاشتن بخش انگلیسی جهت ورود ۱۰۰٪ دقیق و اجباری توسط اپراتور
                    document.getElementById('first_name_en').value = '';
                    document.getElementById('last_name_en').value = '';

                    // قفل فیلدهای فارسی راهداری
                    document.querySelectorAll('.driver-field').forEach(field => {
                        field.readOnly = true;
                        field.classList.remove('bg-white', 'bg-slate-50');
                        field.classList.add('bg-slate-100', 'text-slate-700', 'font-bold', 'cursor-not-allowed');
                    });

                    if (res.driver.is_active === 'فعال') {
                        badge.innerText = 'وضعیت: تایید شده و فعال (قفل سیستمی)';
                        badge.className = "bg-green-600 text-white text-[10px] font-bold px-2.5 py-1 rounded-lg";
                        msgBox.innerText = '✅ استعلام اصالت موفق؛ اطلاعات راننده قفل شد. لطفاً نام لاتین و شماره پاسپورت را با کیبورد انگلیسی وارد کنید.';
                        msgBox.className = "p-4 text-xs font-bold border-b bg-green-50 text-green-600 border-green-100 block";
                    } else {
                        badge.innerText = 'وضعیت: غیرفعال در سازمان راهداری';
                        badge.className = "bg-red-600 text-white text-[10px] font-bold px-2.5 py-1 rounded-lg";
                        msgBox.innerText = '❌ هشدار سیستمی: کارت هوشمند راننده در سازمان غیرفعال است.';
                        msgBox.className = "p-4 text-xs font-bold border-b bg-red-50 text-red-600 border-red-100 block";
                    }
                    
                    // فوکوس مستقیم روی فیلد نام لاتین جهت تسریع کار اپراتور
                    document.getElementById('first_name_en').focus();
                } else {
                    handleManualMode();
                }
            })
            .catch(err => {
                btn.innerText = '🔍 شلیک و استعلام زنده';
                btn.disabled = false;
                handleManualMode();
            });
        }

        function handleManualMode() {
            let msgBox = document.getElementById('msg_box');
            let badge = document.getElementById('status_badge');
            
            msgBox.innerText = '⚠️ اطلاعات راننده دریافت نشد. فیلدها جهت ورود دستی باز شدند.';
            msgBox.className = "p-4 text-xs font-bold border-b bg-yellow-50 text-yellow-700 border-yellow-100 block";
            badge.innerText = 'وضعیت: ورود دستی (آزاد)';
            badge.className = "bg-yellow-500 text-white text-[10px] font-bold px-2.5 py-1 rounded-lg";

            document.querySelectorAll('.driver-field').forEach(field => {
                field.readOnly = false;
                field.classList.remove('bg-slate-50', 'bg-slate-100', 'text-slate-700', 'font-bold', 'cursor-not-allowed');
                field.classList.add('bg-white', 'text-slate-900');
                field.value = '';
            });
            document.getElementById('first_name_en').value = '';
            document.getElementById('last_name_en').value = '';
        }
    </script>
</body>
</html>