const API_BASE = '/api/v1/driver';
let watchId = null;
let currentTab = 'home';
let deferredPrompt;

// 📱 ۱. مدیریت رویداد نصب PWA
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    if (!localStorage.getItem('pwa_installed_confirmed')) {
        document.getElementById('pwa-install-modal').classList.remove('hidden');
    }
});

function triggerPwaInstall() {
    if (!deferredPrompt) {
        alert('در آیفون لطفا از منوی Share گزینه Add to Home Screen را انتخاب کنید.');
        return;
    }
    deferredPrompt.prompt();
    deferredPrompt.userChoice.then((choiceResult) => {
        if (choiceResult.outcome === 'accepted') {
            localStorage.setItem('pwa_installed_confirmed', 'true');
            document.getElementById('pwa-install-modal').classList.add('hidden');
        }
        deferredPrompt = null;
    });
}

window.addEventListener('appinstalled', () => {
    localStorage.setItem('pwa_installed_confirmed', 'true');
    document.getElementById('pwa-install-modal').classList.add('hidden');
});

// 🚀 ۲. راه‌اندازی اولیه اپلیکیشن
document.addEventListener('DOMContentLoaded', () => {
    const token = localStorage.getItem('driver_token');
    if (!token) {
        document.getElementById('bottom-navigation').classList.add('hidden');
        renderLoginMobileScreen();
    } else {
        document.getElementById('bottom-navigation').classList.remove('hidden');
        renderDashboardHub();
        updateTripButtonUI();
    }
});

// 📍 ۳. درخواست مجوز لوکیشن
function requestGeolocationPermission() {
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(
            (pos) => console.log("مجوز لوکیشن با موفقیت دریافت شد."),
            (err) => alert("برای ثبت سفرها، لطفاً دسترسی موقعیت مکانی (GPS) را به مرورگر بدهید."),
            { enableHighAccuracy: true }
        );
    }
}

// 🟨 ۴. پارسر پلاک دقیقاً منطبق بر کدهای Tailwind بک‌اِند شما
function parseAndRenderPlate(plateStr) {
    if (!plateStr || plateStr.includes('ثبت نشده') || plateStr === 'نامشخص') {
        return `<span class="bg-yellow-100 border border-yellow-400 px-3 py-1 rounded text-sm tracking-widest font-mono font-bold">نامشخص</span>`;
    }
    
    let cleanStr = plateStr.replace(/[\(\)\s]/g, '').trim(); 
    let parts = cleanStr.split('-');
    
    let mainPart = parts[0] || '';
    let p3 = parts[1] || ''; // کد شهر
    
    // استخراج دو رقم اول، حرف، و سه رقم بعدی
    let letter = mainPart.replace(/[0-9]/g, '') || 'ع';
    let digits = mainPart.replace(/[^0-9]/g, '');
    let p1 = digits.substring(0, 2); 
    let p2 = digits.substring(2, 5); 

    // دقیقاً ساختار HTML ارسالی خودتان
    return `
        <div class="inline-flex shadow-sm border border-slate-400 rounded-md overflow-hidden bg-[#ffb800] h-10 items-center justify-center text-black font-bold font-mono" dir="ltr" style="background-color: #ffb800;">
            <div class="h-full w-5 bg-[#003399] flex flex-col items-center pt-1 border-r border-slate-400" style="background-color: #003399;">
                <div class="w-3 h-2 flex flex-col">
                    <div class="h-1/3 w-full" style="background-color: #239f40;"></div>
                    <div class="h-1/3 w-full bg-white"></div>
                    <div class="h-1/3 w-full" style="background-color: #da0000;"></div>
                </div>
                <span class="text-white mt-1 font-sans tracking-widest origin-center" style="font-size: 5px; writing-mode: vertical-rl; transform: rotate(180deg);">I.R.IRAN</span>
            </div>
            <div class="px-2 text-lg tracking-widest">${p1}</div>
            <div class="px-1 text-lg font-sans">${letter}</div>
            <div class="px-2 text-lg tracking-widest">${p2}</div>
            <div class="h-full w-[2px] bg-slate-400"></div>
            <div class="px-2 h-full flex flex-col items-center justify-center min-w-[40px]">
                <span class="font-sans leading-none mb-0.5 text-slate-800" style="font-size: 9px;">ایران</span>
                <span class="text-lg leading-none mt-0.5">${p3}</span>
            </div>
        </div>
    `;
}

function switchTab(tab) {
    currentTab = tab;
    ['home', 'fleet', 'company', 'profile'].forEach(t => {
        const el = document.getElementById(`tab-${t}`);
        if(el) {
            if(t === tab) {
                el.classList.replace('text-slate-400', 'text-blue-600');
                el.querySelector('span').classList.replace('font-bold', 'font-black');
            } else {
                el.classList.replace('text-blue-600', 'text-slate-400');
                el.querySelector('span').classList.replace('font-black', 'font-bold');
            }
        }
    });
    renderDashboardHub();
}

// 👑 ۵. رندر داشبوردها منطبق بر عکس‌های جدید
function renderDashboardHub() {
    const main = document.getElementById('main-content');
    const driver = JSON.parse(localStorage.getItem('driver_info'));
    
    if (currentTab === 'home') {
        main.innerHTML = `
            <div class="space-y-4 animate-fade-in">
                
                <div class="bg-blue-600 text-white rounded-3xl p-5 shadow-lg text-center space-y-1">
                    <p class="text-blue-200 text-xs font-bold">خوش‌آمدید، کاپیتان ترانزیت</p>
                    <h2 class="text-xl font-black">${driver.name}</h2>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white p-4 rounded-3xl border border-slate-100 shadow-sm flex items-center justify-between">
                        <div class="text-right">
                            <span class="text-slate-500 font-bold block" style="font-size: 9px;">کل سفرهای مرزی</span>
                            <span class="text-sm font-black text-slate-800">۱۲ ترانزیت</span>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-600"><i class="fa-solid fa-globe"></i></div>
                    </div>
                    <div class="bg-white p-4 rounded-3xl border border-slate-100 shadow-sm flex items-center justify-between">
                        <div class="text-right">
                            <span class="text-slate-500 font-bold block" style="font-size: 9px;">دوزوله‌های فعال</span>
                            <span id="home-active-badge" class="text-sm font-black text-slate-800">... پروانه</span>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-600"><i class="fa-solid fa-receipt"></i></div>
                    </div>
                </div>
                
                <div class="space-y-3 pt-2">
                    <div class="flex items-center space-x-2 space-x-reverse px-1">
                        <div class="w-1.5 h-4 bg-blue-600 rounded-full"></div>
                        <h4 class="text-sm font-black text-slate-800">کارتابل پروانه‌های دوزوله فعال</h4>
                    </div>
                    <div id="permits-container" class="space-y-3">
                        <div class="flex justify-center py-8"><div class="animate-spin rounded-full h-6 w-6 border-2 border-blue-600 border-t-transparent"></div></div>
                    </div>
                </div>
            </div>
        `;
        fetchDriverPermits();
        
    } else if (currentTab === 'fleet') {
        main.innerHTML = `
            <div class="space-y-4 animate-fade-in">
                <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm space-y-6">
                    <div class="flex justify-between items-center border-b border-slate-100 pb-4">
                        <h4 class="font-black text-sm text-slate-800 flex items-center"><i class="fa-solid fa-truck-front text-blue-600 ml-2"></i>پلاک فعال ناوگان</h4>
                    </div>
                    <div class="flex justify-center">
                        ${parseAndRenderPlate(driver.truck_plate)}
                    </div>
                    <div class="space-y-4 pt-4 border-t border-slate-100">
                        <div class="flex justify-between items-center"><span class="text-xs text-slate-500 font-bold">شماره کارت هوشمند خودرو:</span> <b class="text-slate-900 text-sm font-black">${driver.truck_smart_card}</b></div>
                        <div class="flex justify-between items-center"><span class="text-xs text-slate-500 font-bold">نوع کاربری:</span> <b class="text-slate-800 text-sm font-black">${driver.truck_type}</b></div>
                    </div>
                </div>
            </div>`;
            
    } else if (currentTab === 'company') {
        main.innerHTML = `
            <div class="space-y-4 animate-fade-in">
                <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm space-y-5">
                    <h4 class="font-black text-sm text-slate-800 border-b border-slate-100 pb-4 flex items-center"><i class="fa-solid fa-building-shield text-indigo-600 ml-2"></i>شرکت حمل و نقل تحت پوشش</h4>
                    <div class="space-y-5 text-center pt-2">
                        <span class="text-xs font-bold text-slate-500 block">نام شرکت بین‌المللی کارفرما</span>
                        <b class="text-slate-900 text-lg font-black block">${driver.company_name}</b>
                    </div>
                    <div class="border-t border-slate-100 pt-4 flex justify-between items-center">
                        <span class="text-xs text-slate-500 font-bold">مدیریت شرکت / شعبه:</span> 
                        <b class="text-slate-800 text-sm font-black">${driver.company_manager}</b>
                    </div>
                </div>
            </div>`;
            
    } else if (currentTab === 'profile') {
        main.innerHTML = `
            <div class="space-y-4 animate-fade-in">
                <div class="bg-white p-6 text-center space-y-4 rounded-3xl shadow-sm border border-slate-100">
                    <div class="w-20 h-20 bg-slate-100 text-slate-500 rounded-full flex items-center justify-center text-3xl mx-auto border"><i class="fa-regular fa-user"></i></div>
                    <div>
                        <h4 class="font-black text-slate-900 text-lg">${driver.name}</h4>
                        <p class="text-xs text-slate-500 mt-1 font-bold">کد ملی: <span class="tracking-widest">${driver.national_code}</span></p>
                    </div>
                </div>
            </div>`;
    }
}

// 📍 ۶. تغییر وضعیت سفر و ارسال لوکیشن
function toggleTripState() {
    const isOnTrip = localStorage.getItem('is_on_trip') === 'true';
    
    if (isOnTrip) {
        if(watchId) navigator.geolocation.clearWatch(watchId);
        localStorage.removeItem('is_on_trip');
        alert('ثبت موقعیت پایان یافت.');
    } else {
        if (!navigator.geolocation) {
            alert('مرورگر شما از GPS پشتیبانی نمی‌کند.');
            return;
        }
        localStorage.setItem('is_on_trip', 'true');
        alert('موقعیت‌یابی زنده فعال شد.');
        watchId = navigator.geolocation.watchPosition((pos) => {
            sendLocationToServer(pos.coords.latitude, pos.coords.longitude);
        }, null, { enableHighAccuracy: true });
    }
    updateTripButtonUI();
}

// تغییر ظاهر دکمه مرکزی
function updateTripButtonUI() {
    const fabBtn = document.getElementById('fab-trip-btn');
    if(!fabBtn) return;
    
    if (localStorage.getItem('is_on_trip') === 'true') {
        fabBtn.className = "w-16 h-16 bg-red-500 rounded-full flex items-center justify-center text-white shadow-xl shadow-red-500/40 text-2xl transform active:scale-95 transition-all border-4 border-white animate-pulse";
        fabBtn.innerHTML = `<i class="fa-solid fa-stop"></i>`;
    } else {
        fabBtn.className = "w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center text-white shadow-xl shadow-blue-600/40 text-2xl transform active:scale-95 transition-all border-4 border-white";
        fabBtn.innerHTML = `<i class="fa-solid fa-paper-plane"></i>`;
    }
}

function sendLocationToServer(lat, lng) {
    const token = localStorage.getItem('driver_token');
    fetch(`${API_BASE}/location/store`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
        body: JSON.stringify({ latitude: lat, longitude: lng })
    });
}

function fetchDriverPermits() {
    const token = localStorage.getItem('driver_token');
    const container = document.getElementById('permits-container');

    fetch(`${API_BASE}/permits`, {
        method: 'GET',
        headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        const badge = document.getElementById('home-active-badge');
        if (data && data.status === 'success' && data.data.length > 0) {
            if(badge) badge.innerText = `${data.data.length} پروانه`;
            container.innerHTML = '';
            data.data.forEach(permit => {
                container.innerHTML += `
                    <div class="bg-white border border-slate-100 p-5 rounded-3xl flex flex-col space-y-4 shadow-sm">
                        <div class="flex justify-between items-center border-b border-slate-50 pb-3">
                            <span class="text-sm font-black text-slate-900 tracking-wide">${permit.serial_number}</span>
                            <span class="text-xs font-black px-3 py-1 rounded-lg bg-blue-50 text-blue-600">${permit.status_label}</span>
                        </div>
                        <div class="flex justify-between text-xs font-bold text-slate-500">
                            <span><i class="fa-solid fa-globe text-blue-500 ml-1.5"></i>مقصد: <b class="text-slate-800">${permit.country_name}</b></span>
                            <span>تاریخ: <b class="text-slate-800">${permit.issue_date}</b></span>
                        </div>
                    </div>`;
            });
        } else {
            if(badge) badge.innerText = '۰ پروانه';
            container.innerHTML = `
                <div class="text-center p-8 bg-white rounded-3xl border border-slate-100 shadow-sm">
                    <i class="fa-solid fa-folder-open text-slate-800 text-3xl mb-3 block"></i>
                    <p class="text-sm font-bold text-slate-800">هیچ پروانه دوزوله‌ای برای شما صادر نشده است.</p>
                </div>`;
        }
    });
}

function renderLoginMobileScreen() {
    const main = document.getElementById('main-content');
    main.innerHTML = `
        <div class="w-full max-w-sm mx-auto space-y-6 my-auto animate-fade-in">
            <div class="text-center space-y-2">
                <div class="inline-flex p-4 bg-blue-50 text-blue-600 rounded-3xl mb-2"><i class="fa-solid fa-truck-container text-2xl"></i></div>
                <h2 class="text-xl font-black text-slate-900">ورود به کارتابل ناوگان</h2>
            </div>
            <div class="bg-white p-6 rounded-3xl border border-slate-100 space-y-5 shadow-sm">
                <input type="tel" id="driver-mobile" placeholder="۰۹XXXXXXXXX" maxlength="11" class="w-full px-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-center font-black text-xl tracking-widest outline-none text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                <button onclick="handleRequestOtp()" id="btn-submit" class="w-full bg-blue-600 text-white font-black py-4 px-4 rounded-2xl text-sm shadow-md transition-all active:scale-95">دریافت کد تایید</button>
            </div>
        </div>
    `;
}

function handleRequestOtp() {
    const mobileInput = document.getElementById('driver-mobile').value.trim();
    if (!/^09[0-9]{9}$/.test(mobileInput)) return;
    document.getElementById('btn-submit').innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    fetch(`${API_BASE}/auth/request-otp`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ mobile: mobileInput }) })
    .then(res => res.json()).then(data => { if (data.status === 'success') renderVerifyOtpScreen(mobileInput); });
}

function renderVerifyOtpScreen(mobile) {
    const main = document.getElementById('main-content');
    main.innerHTML = `
        <div class="w-full max-w-sm mx-auto space-y-6 my-auto">
            <input type="number" id="otp-code" placeholder="· · · · ·" class="w-full px-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-center font-black text-3xl tracking-widest outline-none text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
            <button onclick="handleVerifyOtp('${mobile}')" id="btn-verify" class="w-full bg-blue-600 text-white font-black py-4 px-4 rounded-2xl text-sm shadow-md transition-all active:scale-95">ورود به سیستم</button>
        </div>
    `;
}

function handleVerifyOtp(mobile) {
    const code = document.getElementById('otp-code').value.trim();
    document.getElementById('btn-verify').innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    fetch(`${API_BASE}/auth/verify-otp`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ mobile: mobile, code: code }) })
    .then(res => res.json()).then(data => {
        if (data.status === 'success') {
            localStorage.setItem('driver_token', data.token);
            localStorage.setItem('driver_info', JSON.stringify(data.driver));
            document.getElementById('bottom-navigation').classList.remove('hidden');
            
            // درخواست مجوز لوکیشن و رندر داشبورد
            requestGeolocationPermission();
            renderDashboardHub();
            updateTripButtonUI();
        } else {
            alert('کد نامعتبر است.');
            document.getElementById('btn-verify').innerText = 'ورود به سیستم';
        }
    });
}