<div class="modal fade" id="driverModal" tabindex="-1" aria-labelledby="driverModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="driverModalLabel">ثبت و استعلام راننده جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                
                <div class="card mb-3 bg-light">
                    <div class="card-body">
                        <h6 class="card-title text-primary mb-3">استعلام از سازمان راهداری</h6>
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">کد ملی</label>
                                <input type="text" id="inquiry_national_id" class="form-control" placeholder="مثال: 0924865644">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">شماره موبایل (فقط جهت استعلام)</label>
                                <input type="text" id="inquiry_mobile" class="form-control" placeholder="مثال: 09333593669">
                            </div>
                            <div class="col-md-4">
                                <button type="button" id="btn_inquire" class="btn btn-primary w-100">
                                    <span id="inquire_spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                    استعلام اطلاعات
                                </button>
                            </div>
                        </div>
                        <div id="inquiry_message" class="mt-2 text-danger" style="display: none;"></div>
                    </div>
                </div>

                <hr>

                <form id="driverStoreForm" method="POST" action="{{ route('web.company.driver.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
						<div class="col-md-6">
                            <label class="form-label">کد ملی <span class="text-danger">*</span></label>
                            <input type="text" name="national_id" id="form_national_id" class="form-control" required readonly>
                            </div>
                           <div class="col-md-6">
                             <label class="form-label">شماره موبایل (جهت دریافت پیامک) <span class="text-danger">*</span></label>
                             <input type="text" name="mobile" id="form_mobile" class="form-control" required>
                        </div>
                            <label class="form-label">کد ملی <span class="text-danger">*</span></label>
                            <input type="text" name="national_id" id="form_national_id" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">شماره پاسپورت <span class="text-danger">*</span></label>
                            <input type="text" name="passport_number" id="form_passport_number" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">نام فارسی <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" id="form_first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">نام خانوادگی فارسی <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" id="form_last_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">نام (Finglish)</label>
                            <input type="text" name="first_name_en" id="form_first_name_en" class="form-control" dir="ltr">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">نام خانوادگی (Finglish)</label>
                            <input type="text" name="last_name_en" id="form_last_name_en" class="form-control" dir="ltr">
                        </div>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="submit" class="btn btn-success">ذخیره پرونده راننده</button>
            </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('btn_inquire').addEventListener('click', function() {
    let nationalId = document.getElementById('inquiry_national_id').value;
    let mobile = document.getElementById('inquiry_mobile').value;
    let msgBox = document.getElementById('inquiry_message');
    let spinner = document.getElementById('inquire_spinner');

    if(!nationalId || !mobile) {
        msgBox.innerText = "لطفاً کد ملی و موبایل را وارد کنید.";
        msgBox.style.display = 'block';
        return;
    }

    // نمایش وضعیت در حال لود
    msgBox.style.display = 'none';
    spinner.classList.remove('d-none');
    this.disabled = true;

    // ارسال درخواست به کنترلر داخلی
    fetch("{{ route('web.company.driver.inquire') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: JSON.stringify({
            national_id: nationalId,
            mobile_number: mobile
        })
    })
    .then(response => response.json())
    .then(data => {
        spinner.classList.add('d-none');
        document.getElementById('btn_inquire').disabled = false;

        if(data.success) {
            // موفقیت: پر کردن فرم پایینی
            msgBox.className = "mt-2 text-success";
            msgBox.innerText = "اطلاعات با موفقیت دریافت شد.";
            msgBox.style.display = 'block';

            // انتقال کد ملی به فرم اصلی
            document.getElementById('form_national_id').value = nationalId;
            
            // جایگذاری اطلاعات دریافت شده (کلیدها بر اساس خروجی API شما باید تنظیم شوند)
            let result = data.data; 
            document.getElementById('form_first_name').value = result.name || '';
            document.getElementById('form_last_name').value = result.family || '';
            // اگر پاسپورت و دیتای انگلیسی هم از API می‌آید اینجا ست می‌کنیم
            
        } else {
            // خطا در دریافت (API قطع است یا دیتایی ندارد)
            msgBox.className = "mt-2 text-danger";
            msgBox.innerText = data.message;
            msgBox.style.display = 'block';
        }
    })
    .catch(error => {
        spinner.classList.add('d-none');
        document.getElementById('btn_inquire').disabled = false;
        msgBox.className = "mt-2 text-danger";
        msgBox.innerText = "خطای شبکه. لطفاً فرم را دستی پر کنید.";
        msgBox.style.display = 'block';
    });
});
</script>