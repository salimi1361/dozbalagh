<div id="companyModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" onclick="closeCompanyModal()"></div>

    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto transform transition-all z-10 text-right" dir="rtl">
        
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center sticky top-0 bg-white z-20">
            <h3 class="text-lg font-bold text-gray-800">ثبت مشخصات شرکت جدید</h3>
            <button onclick="closeCompanyModal()" class="text-gray-400 hover:text-gray-700 text-2xl font-bold focus:outline-none">&times;</button>
        </div>
        
        <form action="{{ route('admin.companies.store') }}" method="POST" class="p-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">شناسه ملی شرکت</label>
                    <input type="text" name="national_id" required 
                           minlength="11" maxlength="11" 
                           oninput="this.value = this.value.replace(/[^0-9]/g, '');" 
                           class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-left" placeholder="۱۱ رقم">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">موبایل مدیر عامل</label>
                    <input type="text" name="ceo_mobile" required class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-left" placeholder="091xxxxxxxxx">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">نام شرکت (فارسی)</label>
                    <input type="text" name="name" required class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="نام رسمی شرکت">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">نام شرکت (لاتین / استاندارد دوزبلاغ)</label>
                    <input type="text" name="name_en" required dir="ltr" class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-left font-sans" placeholder="English Name">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">تلفن شرکت</label>
                    <input type="text" name="phone" required class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-left font-mono" placeholder="0513xxxxxxx">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">نام مدیر عامل</label>
                    <input type="text" name="ceo_name" required class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="نام و نام خانوادگی">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">آدرس شرکت (فارسی)</label>
                    <textarea name="address" required rows="2" class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" placeholder="آدرس کامل..."></textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">آدرس شرکت (لاتین / استاندارد دوزبلاغ)</label>
                    <textarea name="address_en" required rows="2" dir="ltr" class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none text-left font-sans" placeholder="English Address..."></textarea>
                </div>
            </div>

            <div class="mt-6 bg-amber-50 border-r-4 border-amber-500 p-4 rounded-md">
                <h3 class="text-sm font-bold text-amber-800">هشدار و مسئولیت ثبت اطلاعات</h3>
                <p class="mt-1 text-sm text-amber-700 text-justify">
                    ثبت نام و آدرس به صورت لاتین (استاندارد دوزبلاغ) الزامی است. مسئولیت صحت املایی و استاندارد بودن متون لاتین جهت درج در سامانه دوزبلاغ مستقیماً بر عهده شرکت ثبت‌کننده می‌باشد. در صورت درج اطلاعات نادرست، عواقب آن متوجه شرکت خواهد بود.
                </p>
            </div>

            <div class="mt-6 pt-4 border-t border-gray-200 flex justify-start gap-2 flex-row-reverse">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow transition duration-150">ذخیره اطلاعات</button>
                <button type="button" onclick="closeCompanyModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-6 rounded transition duration-150">انصراف</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCompanyModal() {
        document.getElementById('companyModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden'; 
    }
    function closeCompanyModal() {
        document.getElementById('companyModal').classList.add('hidden');
        document.body.style.overflow = 'auto'; 
    }
</script>