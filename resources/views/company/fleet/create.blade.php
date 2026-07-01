<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شرکت حمل‌و‌نقل | ثبت و پرونده‌سازی ناوگان ترانزیت</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        @import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css');
        body { font-family: 'Vazirmatn', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden">

    <aside class="w-64 bg-slate-900 text-slate-300 flex flex-col justify-between p-4 shrink-0 shadow-xl">
        <div>
            <div class="text-center pb-6 border-b border-slate-800 mb-6">
                <h2 class="text-white font-black text-base tracking-wide">پنل عملیاتی شرکت</h2>
                <span class="text-[10px] bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded-full font-bold mt-1 inline-block">دسترسی: شرکت حمل‌ونقل</span>
            </div>
            <nav class="space-y-1">
                <p class="text-[10px] font-bold text-slate-500 mr-2 mb-2 uppercase tracking-wider">ناوگان و رانندگان</p>
                <a href="/web/company/driver/create" class="flex items-center gap-3 p-2.5 rounded-xl text-slate-400 text-xs hover:bg-slate-800 transition">
                    <span>پرونده و استعلام راننده</span>
                </a>
                <a href="/web/company/fleet/create" class="flex items-center gap-3 p-3 rounded-xl bg-blue-600 text-white font-bold shadow-md shadow-blue-900/30 transition">
                    <span class="w-2 h-2 rounded-full bg-white"></span>
                    <span class="text-sm">ثبت و استعلام ناوگان</span>
                </a>
            </nav>
        </div>
    </aside>

    <main class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white border-b border-slate-200 p-4 flex items-center justify-between shadow-sm">
            <div class="text-sm font-bold text-slate-800">مدیریت ناوگان / <span class="text-blue-600 font-black">تشکیل پرونده کامیون</span></div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-8">
            <div class="max-w-2xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="bg-slate-950 p-6 text-white flex justify-between items-center">
                    <div>
                        <h1 class="text-base font-black">ثبت و استعلام ناوگان جدید</h1>
                        <p class="text-slate-400 text-[11px] mt-0.5">در صورت برقراری ارتباط، دیتای راهداری قفل می‌شود؛ در غیر این صورت ورود دستی آزاد می‌گردد.</p>
                    </div>
                    <span id="status_badge" class="bg-slate-800 text-slate-400 text-[10px] font-bold px-2.5 py-1 rounded-lg">وضعیت: در انتظار استعلام</span>
                </div>

                <div id="msg_box" class="hidden p-4 text-xs font-bold border-b transition-all"></div>

                <div id="live_plate_container" class="hidden py-4 bg-slate-100 border-b flex justify-center items-center">
                    <div class="w-[320px] h-[75px] bg-[#fab800] border-[3px] border-black rounded-lg flex overflow-hidden shadow-md select-none text-black font-sans shrink-0">
                        <div class="w-[40px] h-full bg-[#1c3c94] flex flex-col justify-between items-center py-1 text-white text-[8px] shrink-0 border-e-[2px] border-black">
                            <div class="w-6 h-3.5 bg-white rounded-sm flex flex-col justify-between shadow-sm overflow-hidden">
                                <div class="h-1 bg-[#228b22]"></div>
                                <div class="h-1 bg-[#ffffff]"></div>
                                <div class="h-1 bg-[#da251d]"></div>
                            </div>
                            <div class="text-center font-serif font-black leading-none tracking-tighter">I.R.<br><span class="text-[6px] font-bold">IRAN</span></div>
                        </div>
                        <div class="flex-1 h-full flex items-center justify-between px-4 font-black">
                            <div id="plate_part3" class="text-3xl tracking-tighter w-10 text-center shrink-0">۷۲</div>
                            <div id="plate_part2" class="text-3xl pb-0.5 text-center flex-1 shrink-0">ع</div>
                            <div id="plate_part1" class="text-3xl tracking-tighter w-14 text-center shrink-0">۵۷۹</div>
                        </div>
                        <div class="w-[65px] h-full border-s-[3px] border-black flex flex-col justify-center items-center shrink-0 bg-[#fab800] text-black">
                            <div class="text-[9px] font-black tracking-wider border-b border-black/30 px-1 pb-0.5 mb-0.5">ایران</div>
                            <div id="plate_serial" class="text-2xl font-black tracking-tighter">۱۲</div>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 bg-slate-50 p-4 rounded-xl border border-slate-200/60">
                        <div>
                            <div class="block text-xs font-bold text-slate-700 mb-1.5">شماره کارت هوشمند ناوگان</div>
                            <input type="text" id="smart_number" class="w-full p-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none bg-white font-mono" placeholder="4214474">
                        </div>
                        <div>
                            <div class="block text-xs font-bold text-slate-700 mb-1.5">شماره پلاک (اختیاری)</div>
                            <input type="text" id="plate_number" class="w-full p-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none bg-white text-left font-mono" placeholder="579ع72 ایران 12">
                        </div>
                        <div class="md:col-span-2 flex justify-end">
                            <button type="button" onclick="runFleetInquiry()" id="btn_fleet_inquiry" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-5 py-2.5 rounded-xl text-xs shadow-sm transition cursor-pointer">🔍 شلیک و استعلام زنده ناوگان</button>
                        </div>
                    </div>

                    <form id="fleet_final_form" onsubmit="saveFleetToDB(event)" method="POST" class="space-y-5">
                        @csrf
                        <input type="hidden" id="hidden_plq1" name="plq1">
                        <input type="hidden" id="hidden_plq2" name="plq2" value="ع">
                        <input type="hidden" id="hidden_plq3" name="plq3">
                        <input type="hidden" id="hidden_serial" name="serial">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">شماره کارت هوشمند (CardNumber)</label>
                                <input type="text" id="fleet_smart_id" name="smart_id" class="fleet-field w-full p-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50 font-mono" placeholder="شماره هوشمند">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">وضعیت هوشمند ناوگان (IsActive)</label>
                                <input type="text" id="fleet_is_active" name="is_active" class="fleet-field w-full p-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50" placeholder="وضعیت فعال بودن">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">نوع بارگیر / کشنده (LoadingTypeTitle)</label>
                                <input type="text" id="fleet_bargir_name" name="bargir_name" class="fleet-field w-full p-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50" placeholder="مثال: باري فلزي بالاي 20 تن">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-blue-700 mb-1.5">شماره شاسی خودرو (VINCode)</label>
                                <input type="text" id="fleet_vin_code" name="vin_code" class="fleet-field w-full p-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50 uppercase font-mono tracking-wider text-left" placeholder="IRGC549FCY880721">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">تاریخ انقضای معاینه فنی (tarikh_moayene)</label>
                                <input type="text" id="fleet_moayene" name="tarikh_moayene" class="fleet-field w-full p-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50 font-mono" placeholder="14050502">
                            </div>

                            <div class="pt-2 mt-2 border-t border-dashed border-slate-200 md:col-span-2"></div>

                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1.5">شماره ترانزیت اسب (کشنده)</label>
                                <input type="text" id="transit_horse_num" name="transit_horse_num" class="w-full p-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none uppercase font-mono tracking-wider text-left bg-white text-slate-900" placeholder="مثال: TR-12345">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1.5">شماره ترانزیت یدک (بارگیر)</label>
                                <input type="text" id="transit_trailer_num" name="transit_trailer_num" class="w-full p-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none uppercase font-mono tracking-wider text-left bg-white text-slate-900" placeholder="مثال: TR-98765">
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex justify-end">
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold px-6 py-2.5 rounded-xl text-xs shadow-md transition cursor-pointer">🔒 ذخیره نهایی اطلاعات ناوگان</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('fleet_vin_code').addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });

        ['transit_horse_num', 'transit_trailer_num'].forEach(id => {
            document.getElementById(id).addEventListener('input', function() {
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9\-]/g, '');
            });
        });

        function toPersianDigits(num) {
            if(!num) return '';
            const id = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            return num.toString().replace(/[0-9]/g, function (w) { return id[+w]; });
        }

        let currentPlq = { plq1: '', plq2: 'ع', plq3: '', serial: '' };

        function runFleetInquiry() {
            let smartNumber = document.getElementById('smart_number').value;
            let plateNumber = document.getElementById('plate_number').value;
            let msgBox = document.getElementById('msg_box');
            let badge = document.getElementById('status_badge');
            let btn = document.getElementById('btn_fleet_inquiry');
            let plateContainer = document.getElementById('live_plate_container');

            if(!smartNumber) {
                alert('لطفاً شماره کارت هوشمند ناوگان را وارد نمایید.');
                return;
            }

            btn.innerText = '⏳ در حال فرستادن سیگنال استعلام ناوگان...';
            btn.disabled = true;

            fetch('/web/company/fleet/store', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ smart_number: smartNumber, plate_number: plateNumber })
            })
            .then(res => res.json())
            .then(res => {
                btn.innerText = '🔍 شلیک و استعلام زنده ناوگان';
                btn.disabled = false;
                msgBox.className = "hidden p-4 text-xs font-bold border-b transition-all";

                if(res.success && res.fleet) {
                    document.getElementById('fleet_smart_id').value   = res.fleet.smart_id;
                    document.getElementById('fleet_is_active').value  = res.fleet.is_active;
                    document.getElementById('fleet_bargir_name').value = res.fleet.bargir_name;
                    document.getElementById('fleet_vin_code').value   = res.fleet.shenaseh_khodro;
                    document.getElementById('fleet_moayene').value    = res.fleet.tarikh_moayene;

                    currentPlq.plq1 = res.fleet.plq1;
                    currentPlq.plq2 = res.fleet.plq2 ? res.fleet.plq2 : 'ع';
                    currentPlq.plq3 = res.fleet.plq3;
                    currentPlq.serial = res.fleet.serial;

                    document.getElementById('plate_part1').innerText = toPersianDigits(currentPlq.plq1);
                    document.getElementById('plate_part2').innerText = currentPlq.plq2;
                    document.getElementById('plate_part3').innerText = toPersianDigits(currentPlq.plq3);
                    document.getElementById('plate_serial').innerText = toPersianDigits(currentPlq.serial);

                    plateContainer.classList.remove('hidden');

                    document.querySelectorAll('.fleet-field').forEach(field => {
                        field.readOnly = true;
                        field.classList.remove('bg-white');
                        field.classList.add('bg-slate-100', 'text-slate-700', 'font-bold', 'cursor-not-allowed');
                    });

                    badge.innerText = 'وضعیت: تایید شده و قفل (راهداری)';
                    badge.className = "bg-green-600 text-white text-[10px] font-bold px-2.5 py-1 rounded-lg";
                    msgBox.innerText = '✅ استعلام اصالت ناوگان موفق؛ دیتای کامیون با موفقیت قفل شد.';
                    msgBox.className = "p-4 text-xs font-bold border-b bg-green-50 text-green-600 border-green-100 block";
                } else {
                    handleManualFleetMode();
                }
            })
            .catch(err => {
                btn.innerText = '🔍 شلیک و استعلام زنده ناوگان';
                btn.disabled = false;
                handleManualFleetMode();
            });
        }

        function handleManualFleetMode() {
            let msgBox = document.getElementById('msg_box');
            let badge = document.getElementById('status_badge');
            let plateContainer = document.getElementById('live_plate_container');
            
            plateContainer.classList.add('hidden');
            msgBox.innerText = '⚠️ وب‌سرویس قطع شد یا اطلاعات یافت نشد؛ فیلدها جهت ورود دستی فعال شدند.';
            msgBox.className = "p-4 text-xs font-bold border-b bg-yellow-50 text-yellow-700 border-yellow-100 block";
            badge.innerText = 'وضعیت: ورود دستی (آزاد)';
            badge.className = "bg-yellow-500 text-white text-[10px] font-bold px-2.5 py-1 rounded-lg";

            currentPlq = { plq1: '', plq2: 'ع', plq3: '', serial: '' };

            document.querySelectorAll('.fleet-field').forEach(field => {
                field.readOnly = false;
                field.classList.remove('bg-slate-50', 'bg-slate-100', 'text-slate-700', 'font-bold', 'cursor-not-allowed');
                field.classList.add('bg-white', 'text-slate-900');
            });
        }

        function saveFleetToDB(event) {
            event.preventDefault();
            let msgBox = document.getElementById('msg_box');

            let smartIdVal = document.getElementById('fleet_smart_id').value;
            let vinCodeVal = document.getElementById('fleet_vin_code').value;

            if(!smartIdVal || !vinCodeVal) {
                alert('اطلاعات کلیدی فرم ناقص است. ابتدا استعلام بگیرید یا فیلدها را پر کنید.');
                return;
            }

            document.getElementById('hidden_plq1').value = currentPlq.plq1;
            document.getElementById('hidden_plq2').value = currentPlq.plq2;
            document.getElementById('hidden_plq3').value = currentPlq.plq3;
            document.getElementById('hidden_serial').value = currentPlq.serial;

            let payload = {
                final_save: true,
                smart_id: smartIdVal,
                is_active: document.getElementById('fleet_is_active').value,
                bargir_name: document.getElementById('fleet_bargir_name').value,
                vin_code: vinCodeVal,
                tarikh_moayene: document.getElementById('fleet_moayene').value,
                plq1: currentPlq.plq1,
                plq2: currentPlq.plq2,
                plq3: currentPlq.plq3,
                serial: currentPlq.serial,
                transit_horse_num: document.getElementById('transit_horse_num').value,
                transit_trailer_num: document.getElementById('transit_trailer_num').value
            };

            fetch('/web/company/fleet/store', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(res => {
                if(res.success) {
                    msgBox.innerText = res.message;
                    msgBox.className = "p-4 text-xs font-bold border-b bg-blue-50 text-blue-700 border-blue-100 block";
                    alert(res.message);
                } else {
                    alert('خطا: ' + res.message);
                }
            })
            .catch(err => alert('خطای ارتباط با سرور دوزبلاغ.'));
        }
    </script>
</body>
</html>