{{-- 📜 مودال مستقل بررسی و ویرایش مستندات ارسالی شرکت (نسخه جدید: با قابلیت ویرایش تاریخ‌ها) --}}
<div id="documents_modal" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl max-w-6xl w-full h-[90vh] shadow-2xl overflow-hidden border border-slate-200 flex flex-col">
        <div class="bg-slate-950 p-5 flex justify-between items-center text-white shrink-0">
            <div class="flex items-center gap-2">
                <span class="text-xl">📋</span>
                <div>
                    <h3 class="font-black text-sm">بررسی و ویرایش مستندات پرونده <span id="mdl_d_code" class="font-mono text-amber-400 font-black"></span></h3>
                    <p class="text-[10px] text-slate-400 mt-0.5">اپراتور محترم انجمن، شما می‌توانید فیلدهای متنی و تاریخ‌ها را مستقیماً ویرایش و ذخیره کنید.</p>
                </div>
            </div>
            <button onclick="closeDocumentsModal()" class="text-slate-400 hover:text-white text-xl transition-colors">✕</button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 bg-slate-50/50 text-right" dir="rtl">
            
            <form id="inline_edit_form" class="lg:col-span-5 space-y-4">
                
                <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm border-r-4 border-r-indigo-600">
                    <h4 class="text-xs font-black text-indigo-900 border-b border-slate-100 pb-2 mb-3">👤 راننده و ناوگان متقاضی</h4>
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div><span class="text-slate-400 block mb-0.5">نام راننده:</span> <strong id="mdl_driver_name" class="text-slate-800 font-bold">---</strong></div>
                        <div><span class="text-slate-400 block mb-0.5">کد ملی:</span> <strong id="mdl_driver_national" class="text-slate-800 font-mono font-bold">---</strong></div>
                        <div class="col-span-2 border-t border-slate-100 pt-2 mt-1"></div>
                        <div><span class="text-slate-400 block mb-0.5">کارت هوشمند:</span> <strong id="mdl_fleet_smart" class="text-slate-800 font-mono font-bold">---</strong></div>
                        <div><span class="text-slate-400 block mb-1">پلاک ترانزیت:</span> <div id="mdl_fleet_plate_container">---</div></div>
                    </div>
                </div>
                
                <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
                    <h4 class="text-xs font-black text-indigo-700 border-b border-slate-100 pb-2 mb-3">🗺️ مشخصات سفر و بارگیری (قابل ویرایش)</h4>
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <label class="block text-slate-400 mb-1">نوع عملیات حمل:</label>
                            <input type="text" id="inp_cargo_type" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2 font-bold text-slate-800 focus:bg-white focus:border-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1">کد سامانه جامع (CITS):</label>
                            <input type="text" id="inp_cits_code" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2 font-mono font-bold text-slate-800 focus:bg-white focus:border-indigo-500 outline-none">
                        </div>
                        <div class="col-span-2 border-t border-slate-100 my-1"></div>
                        <div>
                            <label class="block text-slate-400 mb-1">مبدا بارگیری:</label>
                            <input type="text" id="inp_origin" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2 font-bold text-slate-800 focus:bg-white focus:border-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1">مقصد نهایی حمل:</label>
                            <input type="text" id="inp_destination" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2 font-bold text-slate-800 focus:bg-white focus:border-indigo-500 outline-none">
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
                    <h4 class="text-xs font-black text-emerald-700 border-b border-slate-100 pb-2 mb-3">💵 اطلاعات فیش و بارنامه (قابل ویرایش)</h4>
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <label class="block text-slate-400 mb-1">شماره فیش سازمان:</label>
                            <input type="text" id="inp_receipt_code" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2 font-mono font-bold text-slate-800 focus:bg-white focus:border-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1">مبلغ پرداختی:</label>
                            <strong id="mdl_receipt_amount" class="text-emerald-600 font-mono font-black block pt-2">۰ ریال</strong>
                        </div>
                        <div class="col-span-2 border-t border-slate-100 my-1"></div>
                        <div>
                            <label class="block text-slate-400 mb-1">ککد سفر (J-Code):</label>
                            <input type="text" id="inp_trip_code" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2 font-mono font-bold text-slate-800 uppercase focus:bg-white focus:border-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1">تاریخ بارگیری CMR:</label>
                            <input type="date" id="inp_cmr_date" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-1.5 font-mono text-center focus:bg-white focus:border-indigo-500 outline-none">
                        </div>
                        <div class="col-span-2 border-t border-slate-100 my-1"></div>
                        <div>
                            <label class="block text-slate-400 mb-1">شماره کارنه تیر:</label>
                            <input type="text" id="inp_tir_number" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2 font-mono font-bold text-slate-800 focus:bg-white focus:border-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1">تاریخ کارنه تیر:</label>
                            <input type="date" id="inp_tir_date" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-1.5 font-mono text-center focus:bg-white focus:border-indigo-500 outline-none">
                        </div>
                    </div>
                </div>

                <div class="bg-amber-50/70 border border-amber-200 rounded-2xl p-4 shadow-sm">
                    <h4 class="text-xs font-black text-amber-800 pb-1.5 mb-2 border-b border-amber-200">📜 وضعیت تعهدنامه الکترونیک شرکت</h4>
                    <p class="text-[11px] text-amber-900 leading-relaxed text-justify font-medium">
                         تعهدنامه اصالت اطلاعات و مدارک پیوستی فوق توسط مدیرعامل شرکت مندرج در پیش‌نمایش تایید و با موفقیت امضا شده است.
                    </p>
                </div>

                <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
                    <h4 class="text-xs font-black text-amber-700 border-b border-slate-100 pb-2 mb-2">🌍 پروانه‌های درخواستی مسیر</h4>
                    <div id="mdl_countries_list" class="divide-y divide-slate-100 text-xs"></div>
                </div>
            </form>

            <div class="lg:col-span-7 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex flex-col h-full">
                <h4 class="text-xs font-black text-slate-800 border-b border-slate-100 pb-2 mb-4">🖼️ مستندات و فایل‌های اسکن‌شده ارسالی</h4>
                
                <div class="flex flex-wrap gap-2 mb-4 shrink-0" id="image_tabs_container">
                    <button onclick="switchDocumentImage('receipt')" id="tab-receipt" class="doc-tab px-3 py-2 rounded-xl text-xs font-bold bg-indigo-600 text-white shadow transition-all">🧾 تصویر فیش</button>
                    <button onclick="switchDocumentImage('cmr')" id="tab-cmr" class="doc-tab px-3 py-2 rounded-xl text-xs font-bold bg-slate-100 text-slate-600 hover:bg-slate-200 transition-all">📦 تصویر CMR</button>
                    <button onclick="switchDocumentImage('tir')" id="tab-tir" class="doc-tab px-3 py-2 rounded-xl text-xs font-bold bg-slate-100 text-slate-600 hover:bg-slate-200 transition-all">🎫 تصویر کارنه تیر</button>
                    <button onclick="switchDocumentImage('declaration')" id="tab-declaration" class="doc-tab px-3 py-2 rounded-xl text-xs font-bold bg-slate-100 text-slate-600 hover:bg-slate-200 transition-all">📄 تصویر اظهارنامه</button>
                </div>

                <div class="flex-1 bg-slate-900 rounded-2xl border border-slate-800 p-2 flex items-center justify-center overflow-hidden relative min-h-[350px]">
                    <img id="modal_document_viewer" src="" alt="مدرک ارسالی شرکت" class="max-w-full max-h-full object-contain rounded-xl transition-transform duration-300 shadow-xl">
                </div>
            </div>

        </div>

        <div class="bg-slate-50 p-4 border-t border-slate-200 flex justify-between items-center shrink-0">
            <div class="flex items-center gap-2">
                <button type="button" onclick="closeDocumentsModal()" class="bg-slate-200 text-slate-700 px-4 py-2 rounded-xl font-bold text-xs transition hover:bg-slate-300">بستن پنجره</button>
                <button type="button" id="btn_save_inline_edit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl font-black text-xs transition shadow-md">📋 ثبت و ذخیره ویرایش ادمین</button>
            </div>
            <div class="flex items-center gap-2" id="modal_action_buttons"></div>
        </div>
    </div>
</div>