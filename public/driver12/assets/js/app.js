/* ═══════════════════════════════════════════
   آیکون‌های SVG — بدون CDN
═══════════════════════════════════════════ */
const ICONS = {
    truck: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>`,
    truckHero: `<svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.92)" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>`,
    shield: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.92)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>`,
    send: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>`,
    login: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>`,
    logout: `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>`,
    fileLines: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>`,
    location: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>`,
    locationArrow: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>`,
    stop: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/></svg>`,
    globe: `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>`,
    wifiOff: `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#c0cdd9" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="1" y1="1" x2="23" y2="23"/><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/><path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/><path d="M10.71 5.05A16 16 0 0 1 22.56 9"/><path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>`,
    folderOpen: `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#c0cdd9" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>`,
    check: `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`,
    user: `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`,
    truckSm: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>`,
    building: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><line x1="9" y1="22" x2="9" y2="12"/><line x1="15" y1="22" x2="15" y2="12"/><rect x="9" y="12" width="6" height="10"/></svg>`,
};

/* ═══════════════════════════════════════════
   وضعیت برنامه
═══════════════════════════════════════════ */
const API_BASE = '/api/v1/driver';
let watchId      = null;
let currentTab   = 'home';
let permitsCache = [];

/* ═══════════════════════════════════════════
   راه‌اندازی
═══════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
    const token = localStorage.getItem('driver_token');
    const main  = document.getElementById('main-content');

    if (token) {
        document.getElementById('bottom-navigation').classList.remove('hidden');
        main.classList.remove('is-centered');
        renderDashboard();
    } else {
        document.getElementById('bottom-navigation').classList.add('hidden');
        renderLoginScreen();
    }

    window.addEventListener('online',  () => setConnectionStatus(true));
    window.addEventListener('offline', () => setConnectionStatus(false));
});

/* ═══════════════════════════════════════════
   تب‌ها
═══════════════════════════════════════════ */
function switchTab(tab) {
    currentTab = tab;
    ['home', 'fleet', 'company', 'profile'].forEach(t => {
        const el = document.getElementById(`tab-${t}`);
        if (el) el.classList.toggle('is-active', t === tab);
    });
    renderDashboard();
}

/* ═══════════════════════════════════════════
   توابع کمکی
═══════════════════════════════════════════ */
function getDriver() {
    try { return JSON.parse(localStorage.getItem('driver_info')) || {}; }
    catch { return {}; }
}

function authHeaders() {
    return {
        'Authorization': `Bearer ${localStorage.getItem('driver_token')}`,
        'Content-Type':  'application/json',
        'Accept':        'application/json',
    };
}

function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    const el = document.createElement('div');
    el.className = `toast toast--${type}`;
    el.textContent = message;
    container.appendChild(el);
    setTimeout(() => el.remove(), 3200);
}

function closeModal() {
    document.getElementById('modal-container').innerHTML = '';
}

function setConnectionStatus(online) {
    const el = document.getElementById('connection-status');
    if (!el) return;
    el.textContent = online ? 'متصل' : 'آفلاین';
    el.classList.toggle('is-offline', !online);
}

/* ═══════════════════════════════════════════
   پلاک ایرانی
═══════════════════════════════════════════ */
function renderPlate(plateStr) {
    if (!plateStr || plateStr.includes('ثبت نشده')) {
        return '<span class="badge badge--used">پلاک ثبت نشده</span>';
    }
    const clean    = plateStr.replace(/[()\s]/g, '').trim();
    const parts    = clean.split('-');
    const mainPart = parts[0] || '';
    const cityCode = parts[1] || '32';
    const alpha    = mainPart.replace(/[0-9]/g, '')   || 'ع';
    const digits   = mainPart.replace(/[^0-9]/g, '');
    const num1     = digits.substring(0, 2);
    const num2     = digits.substring(2);
    return `
        <div class="iran-plate">
            <div class="iran-plate__flag">
                <span style="font-size:5px;line-height:1;font-weight:900">I.R.</span>
                <span style="font-size:4px;line-height:1">IRAN</span>
            </div>
            <div class="iran-plate__digits">
                <span>${num1}</span>
                <span class="iran-plate__alpha">${alpha}</span>
                <span>${num2}</span>
            </div>
            <div class="iran-plate__city">
                <span style="font-size:6px;font-weight:900;color:#475569">ایران</span>
                <span style="font-size:12px;font-weight:900">${cityCode}</span>
            </div>
        </div>`;
}

function statusBadgeClass(statusKey) {
    return ({ issued:'badge--active', started_trip:'badge--transit', in_transit:'badge--transit',
              at_border_out:'badge--transit', at_destination:'badge--used',
              used:'badge--used', expired:'badge--expired' })[statusKey] || 'badge--active';
}

/* ═══════════════════════════════════════════
   داشبورد
═══════════════════════════════════════════ */
function renderDashboard() {
    const main   = document.getElementById('main-content');
    const driver = getDriver();
    if      (currentTab === 'home')    renderHomeTab(main, driver);
    else if (currentTab === 'fleet')   renderFleetTab(main, driver);
    else if (currentTab === 'company') renderCompanyTab(main, driver);
    else if (currentTab === 'profile') renderProfileTab(main, driver);
}

/* ── تب خانه ── */
function renderHomeTab(main, driver) {
    const onTrip = localStorage.getItem('is_on_trip') === 'true';
    main.innerHTML = `
        <div class="stack">
            <div class="card card--hero">
                <div class="label">خوش آمدید</div>
                <div class="name">${driver.name || 'راننده'}</div>
                <div class="meta">${driver.company_name || 'شرکت حمل و نقل'}</div>
            </div>

            <div class="metric-grid">
                <div class="metric-card">
                    <div class="metric-card__icon metric-card__icon--blue">${ICONS.fileLines}</div>
                    <div>
                        <span class="metric-card__label">پروانه‌های فعال</span>
                        <div class="metric-card__value" id="home-active-badge">—</div>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-card__icon metric-card__icon--${onTrip ? 'green' : 'teal'}">${ICONS.location}</div>
                    <div>
                        <span class="metric-card__label">وضعیت GPS</span>
                        <div class="metric-card__value" id="home-gps-badge">${onTrip ? 'فعال' : 'غیرفعال'}</div>
                    </div>
                </div>
            </div>

            <button type="button" onclick="toggleTripState()" id="btn-trip-state"
                class="btn ${onTrip ? 'btn--danger' : 'btn--success'}">
                ${onTrip ? ICONS.stop : ICONS.locationArrow}
                <span>${onTrip ? 'توقف ارسال موقعیت' : 'شروع ارسال موقعیت مکانی'}</span>
            </button>

            <div>
                <div class="section-title">پروانه‌های دوزبلاغ</div>
                <div id="permits-container" class="stack-sm">
                    <div class="loader-wrap"><div class="loader"></div></div>
                </div>
            </div>
        </div>`;
    fetchPermits();
}

/* ── تب ناوگان ── */
function renderFleetTab(main, driver) {
    main.innerHTML = `
        <div class="stack">
            <div class="card info-card">
                <div class="info-card__title" style="color:#0369a1">
                    ${ICONS.truckSm} مشخصات ناوگان
                </div>
                <div style="display:flex;justify-content:center;margin-bottom:18px">
                    ${renderPlate(driver.truck_plate)}
                </div>
                <div class="info-row">
                    <span>کارت هوشمند خودرو</span>
                    <b>${driver.truck_smart_card || '—'}</b>
                </div>
                <div class="info-row">
                    <span>نوع خودرو</span>
                    <b>${driver.truck_type || '—'}</b>
                </div>
            </div>
        </div>`;
}

/* ── تب شرکت ── */
function renderCompanyTab(main, driver) {
    main.innerHTML = `
        <div class="stack">
            <div class="card info-card">
                <div class="info-card__title" style="color:#0d9488">
                    ${ICONS.building} شرکت کارفرما
                </div>
                <div class="info-block" style="margin-bottom:10px">
                    <span class="info-block__label">نام شرکت</span>
                    <span class="info-block__value">${driver.company_name || '—'}</span>
                </div>
                <div class="info-row">
                    <span>مدیر / نماینده</span>
                    <b>${driver.company_manager || '—'}</b>
                </div>
                <div class="info-row">
                    <span>آدرس</span>
                    <b style="max-width:56%;text-align:left;line-height:1.5">${driver.company_address || '—'}</b>
                </div>
            </div>
        </div>`;
}

/* ── تب پروفایل ── */
function renderProfileTab(main, driver) {
    main.innerHTML = `
        <div class="stack">
            <div class="card profile-card">
                <div class="profile-avatar">${ICONS.user}</div>
                <div class="profile-name">${driver.name || 'راننده'}</div>
                <div class="profile-meta">کد ملی: ${driver.national_code || '—'}</div>
                <div class="profile-meta" style="margin-top:2px">موبایل: ${driver.mobile || '—'}</div>
                <div style="margin-top:24px">
                    <button type="button" onclick="logout()" class="btn btn--ghost">
                        ${ICONS.logout} <span>خروج از حساب کاربری</span>
                    </button>
                </div>
            </div>
        </div>`;
}

/* ═══════════════════════════════════════════
   پروانه‌ها
═══════════════════════════════════════════ */
function fetchPermits() {
    const container = document.getElementById('permits-container');
    if (!container) return;

    fetch(`${API_BASE}/permits`, { headers: authHeaders() })
        .then(res => {
            if (res.status === 401) { logout(); throw new Error('unauthorized'); }
            return res.json();
        })
        .then(data => {
            const badge    = document.getElementById('home-active-badge');
            permitsCache   = data?.data || [];

            if (!data || data.status !== 'success' || permitsCache.length === 0) {
                if (badge) badge.textContent = '۰';
                container.innerHTML = `
                    <div class="card empty-state">
                        ${ICONS.folderOpen}
                        <p>پروانه فعالی برای شما ثبت نشده است</p>
                    </div>`;
                return;
            }

            if (badge) badge.textContent = permitsCache.length.toLocaleString('fa-IR');
            container.innerHTML = permitsCache.map(p => `
                <div class="card permit-card" onclick="showPermitDetail(${p.id})">
                    <div class="permit-card__header">
                        <div>
                            <span class="permit-card__serial-label">شماره سریال</span>
                            <div class="permit-card__serial">${p.serial_number}</div>
                        </div>
                        <span class="badge ${statusBadgeClass(p.status_key)}">${p.status_label}</span>
                    </div>
                    <div class="permit-card__row">
                        <span style="display:flex;align-items:center;gap:4px">
                            ${ICONS.globe.replace('stroke="currentColor"','stroke="#0369a1"')}
                            مقصد: <b>${p.country_name}</b>
                        </span>
                    </div>
                    <div class="permit-card__row" style="margin-top:6px">
                        <span>شرکت: <b>${p.company_name}</b></span>
                        <span>صدور: <b>${p.issue_date}</b></span>
                    </div>
                </div>
            `).join('');
        })
        .catch(() => {
            const badge = document.getElementById('home-active-badge');
            if (badge) badge.textContent = '—';
            document.getElementById('permits-container').innerHTML = `
                <div class="card empty-state">
                    ${ICONS.wifiOff}
                    <p>خطا در دریافت پروانه‌ها<br>اتصال اینترنت را بررسی کنید</p>
                </div>`;
            setConnectionStatus(false);
        });
}

function showPermitDetail(id) {
    fetch(`${API_BASE}/permits/${id}`, { headers: authHeaders() })
        .then(res => res.json())
        .then(data => {
            if (data.status !== 'success') { showToast('جزئیات یافت نشد.', 'error'); return; }
            const p      = data.data;
            const events = (p.events || []).map(e => `
                <div class="info-row">
                    <span>${e.status_label || e.event_type}</span>
                    <b>${e.created_at}</b>
                </div>
            `).join('') || '<p style="font-size:11px;color:#9aaab8;text-align:center;padding:8px 0">رویدادی ثبت نشده</p>';

            document.getElementById('modal-container').innerHTML = `
                <div class="modal-overlay" onclick="if(event.target===this)closeModal()">
                    <div class="modal-sheet">
                        <div class="modal-handle"></div>
                        <div class="modal-title">جزئیات پروانه</div>
                        <div class="stack-sm">
                            <div class="info-block">
                                <span class="info-block__label">شماره سریال</span>
                                <span class="info-block__value">${p.serial_number}</span>
                            </div>
                            <div class="info-row"><span>وضعیت</span><b>${p.status_label}</b></div>
                            <div class="info-row"><span>کشور مقصد</span><b>${p.country_name}</b></div>
                            <div class="info-row"><span>شرکت</span><b>${p.company_name}</b></div>
                            <div class="info-row"><span>تاریخ صدور</span><b>${p.issue_date}</b></div>
                            <div class="section-title" style="margin-top:10px">تاریخچه رویدادها</div>
                            ${events}
                            <button type="button"
                                onclick="selectPermitForTrip(${p.id}); closeModal();"
                                class="btn btn--outline" style="margin-top:10px">
                                ${ICONS.check} انتخاب برای ردیابی GPS
                            </button>
                        </div>
                    </div>
                </div>`;
        })
        .catch(() => showToast('خطا در بارگذاری جزئیات.', 'error'));
}

function selectPermitForTrip(id) {
    localStorage.setItem('active_permit_id', id);
    showToast('پروانه برای ردیابی انتخاب شد.', 'success');
}

/* ═══════════════════════════════════════════
   GPS ردیابی
═══════════════════════════════════════════ */
function toggleTripState() {
    if (localStorage.getItem('is_on_trip') === 'true') {
        if (watchId) navigator.geolocation.clearWatch(watchId);
        watchId = null;
        localStorage.removeItem('is_on_trip');
        showToast('ارسال موقعیت متوقف شد.', 'info');
    } else {
        const permitId = localStorage.getItem('active_permit_id') || (permitsCache[0] && permitsCache[0].id);
        if (!permitId) { showToast('ابتدا یک پروانه انتخاب کنید.', 'error'); return; }
        if (!navigator.geolocation) { showToast('GPS در این دستگاه پشتیبانی نمی‌شود.', 'error'); return; }
        localStorage.setItem('active_permit_id', permitId);
        localStorage.setItem('is_on_trip', 'true');
        showToast('ارسال موقعیت آغاز شد.', 'success');
        watchId = navigator.geolocation.watchPosition(
            pos => sendLocation(pos.coords.latitude, pos.coords.longitude, permitId),
            () => showToast('دسترسی به GPS رد شد.', 'error'),
            { enableHighAccuracy: true, maximumAge: 10000 }
        );
    }
    const btn = document.getElementById('btn-trip-state');
    if (btn) {
        const onTrip = localStorage.getItem('is_on_trip') === 'true';
        btn.className = `btn ${onTrip ? 'btn--danger' : 'btn--success'}`;
        btn.innerHTML = `${onTrip ? ICONS.stop : ICONS.locationArrow}
                         <span>${onTrip ? 'توقف ارسال موقعیت' : 'شروع ارسال موقعیت مکانی'}</span>`;
    }
    const gpsBadge = document.getElementById('home-gps-badge');
    if (gpsBadge) gpsBadge.textContent = localStorage.getItem('is_on_trip') === 'true' ? 'فعال' : 'غیرفعال';
}

function sendLocation(lat, lng, permitId) {
    fetch(`${API_BASE}/location/sync`, {
        method: 'POST',
        headers: authHeaders(),
        body: JSON.stringify({ dozbalagh_item_id: parseInt(permitId, 10), latitude: lat, longitude: lng }),
    }).catch(() => {});
}

function logout() {
    if (watchId) navigator.geolocation.clearWatch(watchId);
    localStorage.clear();
    location.reload();
}

/* ═══════════════════════════════════════════
   صفحه ورود — شماره موبایل
═══════════════════════════════════════════ */
function renderLoginScreen() {
    const main = document.getElementById('main-content');
    main.innerHTML = `
        <div class="auth-hero">
            <div class="auth-hero__brand">
                <div class="auth-hero__icon">${ICONS.truck}</div>
                <div class="auth-hero__brand-name">
                    <div class="title">سامانه دوزبلاغ</div>
                    <div class="sub">کارتابل رانندگان ترانزیت</div>
                </div>
            </div>
            <div class="auth-hero__truck">${ICONS.truckHero}</div>
            <div class="auth-hero__tagline">مدیریت پروانه‌های بین‌المللی</div>
        </div>

        <div class="auth-sheet">
            <h1 class="auth-sheet-title">ورود به کارتابل</h1>
            <p class="auth-sheet-sub">شماره موبایل خود را وارد کنید<br>کد تأیید پیامک می‌شود</p>

            <div class="form-group">
                <label class="input-label" for="driver-mobile">شماره موبایل</label>
                <input
                    type="tel"
                    id="driver-mobile"
                    class="input-field"
                    placeholder="09xxxxxxxxx"
                    maxlength="11"
                    inputmode="numeric"
                    autocomplete="tel"
                >
            </div>

            <button type="button" onclick="handleRequestOtp()" id="btn-submit" class="btn btn--primary" style="margin-top:20px">
                ${ICONS.send} <span>دریافت کد تأیید</span>
            </button>
        </div>`;

    const input = document.getElementById('driver-mobile');
    input.addEventListener('keydown', e => { if (e.key === 'Enter') handleRequestOtp(); });
    requestAnimationFrame(() => input.focus());
}

function handleRequestOtp() {
    const mobile = document.getElementById('driver-mobile').value.trim();
    if (!/^09[0-9]{9}$/.test(mobile)) {
        showToast('شماره موبایل معتبر وارد کنید (۰۹xxxxxxxx).', 'error');
        return;
    }

    const btn = document.getElementById('btn-submit');
    btn.disabled = true;
    btn.innerHTML = `<div class="loader" style="width:18px;height:18px;border-width:2px"></div> <span>در حال ارسال...</span>`;

    fetch(`${API_BASE}/auth/request-otp`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ mobile }),
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                renderOtpScreen(mobile);
                showToast('کد تأیید ارسال شد.', 'success');
            } else {
                showToast(data.message || 'خطا در ارسال کد.', 'error');
                btn.disabled = false;
                btn.innerHTML = `${ICONS.send} <span>دریافت کد تأیید</span>`;
            }
        })
        .catch(() => {
            showToast('خطا در ارتباط با سرور.', 'error');
            btn.disabled = false;
            btn.innerHTML = `${ICONS.send} <span>دریافت کد تأیید</span>`;
        });
}

/* ═══════════════════════════════════════════
   صفحه ورود — کد OTP
═══════════════════════════════════════════ */
function renderOtpScreen(mobile) {
    const main = document.getElementById('main-content');
    main.innerHTML = `
        <div class="auth-hero">
            <div class="auth-hero__brand">
                <div class="auth-hero__icon">${ICONS.truck}</div>
                <div class="auth-hero__brand-name">
                    <div class="title">سامانه دوزبلاغ</div>
                    <div class="sub">کارتابل رانندگان ترانزیت</div>
                </div>
            </div>
            <div class="auth-hero__truck">${ICONS.shield}</div>
            <div class="auth-hero__tagline">تأیید هویت راننده</div>
        </div>

        <div class="auth-sheet">
            <h1 class="auth-sheet-title">کد تأیید را وارد کنید</h1>
            <p class="auth-sheet-sub">کد ۵ رقمی ارسال‌شده به این شماره:</p>

            <div style="display:flex;justify-content:center;margin-bottom:20px">
                <div class="phone-hint">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13.4a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.69h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.16 6.16l1.28-.93a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    ${mobile}
                </div>
            </div>

            <div class="otp-grid" id="otp-grid">
                <input class="otp-box" type="number" inputmode="numeric" maxlength="1" min="0" max="9" data-idx="0">
                <input class="otp-box" type="number" inputmode="numeric" maxlength="1" min="0" max="9" data-idx="1">
                <input class="otp-box" type="number" inputmode="numeric" maxlength="1" min="0" max="9" data-idx="2">
                <input class="otp-box" type="number" inputmode="numeric" maxlength="1" min="0" max="9" data-idx="3">
                <input class="otp-box" type="number" inputmode="numeric" maxlength="1" min="0" max="9" data-idx="4">
            </div>

            <button type="button" onclick="handleVerifyOtp('${mobile}')" id="btn-verify" class="btn btn--primary" style="margin-top:22px">
                ${ICONS.login} <span>ورود به کارتابل</span>
            </button>

            <button type="button" onclick="renderLoginScreen()" class="btn btn--outline" style="margin-top:10px">
                تغییر شماره موبایل
            </button>
        </div>`;

    initOtpBoxes(mobile);
}

/* ── منطق باکس‌های OTP ── */
function initOtpBoxes(mobile) {
    const boxes = document.querySelectorAll('.otp-box');

    boxes.forEach((box, idx) => {
        box.addEventListener('input', () => {
            let val = box.value.replace(/[^0-9]/g, '');
            if (val.length > 1) val = val[val.length - 1];
            box.value = val;

            if (val) {
                box.classList.add('is-filled');
                box.style.animation = 'otp-pop 0.2s ease';
                setTimeout(() => box.style.animation = '', 200);
                if (idx < boxes.length - 1) boxes[idx + 1].focus();
                else if (idx === boxes.length - 1) handleVerifyOtp(mobile);
            } else {
                box.classList.remove('is-filled');
            }
        });

        box.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !box.value && idx > 0) {
                boxes[idx - 1].value = '';
                boxes[idx - 1].classList.remove('is-filled');
                boxes[idx - 1].focus();
            }
            if (e.key === 'Enter') handleVerifyOtp(mobile);
        });

        box.addEventListener('paste', e => {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
            [...pasted].slice(0, 5).forEach((ch, i) => {
                if (boxes[i]) {
                    boxes[i].value = ch;
                    boxes[i].classList.add('is-filled');
                }
            });
            const next = Math.min(pasted.length, 4);
            boxes[next]?.focus();
        });
    });

    requestAnimationFrame(() => boxes[0].focus());
}

function getOtpValue() {
    return [...document.querySelectorAll('.otp-box')].map(b => b.value).join('');
}

function handleVerifyOtp(mobile) {
    const code = getOtpValue();
    if (code.length !== 5) {
        showToast('کد ۵ رقمی را کامل وارد کنید.', 'error');
        return;
    }

    const btn = document.getElementById('btn-verify');
    btn.disabled = true;
    btn.innerHTML = `<div class="loader" style="width:18px;height:18px;border-width:2px"></div> <span>در حال بررسی...</span>`;

    fetch(`${API_BASE}/auth/verify-otp`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ mobile, code }),
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                localStorage.setItem('driver_token', data.token);
                localStorage.setItem('driver_info', JSON.stringify(data.driver));
                document.getElementById('bottom-navigation').classList.remove('hidden');
                document.getElementById('main-content').classList.remove('is-centered');
                showToast(`خوش آمدید، ${data.driver?.name || 'راننده'}`, 'success');
                renderDashboard();
            } else {
                showToast(data.message || 'کد اشتباه است.', 'error');
                btn.disabled = false;
                btn.innerHTML = `${ICONS.login} <span>ورود به کارتابل</span>`;
                document.querySelectorAll('.otp-box').forEach(b => {
                    b.value = '';
                    b.classList.remove('is-filled');
                });
                document.querySelector('.otp-box')?.focus();
            }
        })
        .catch(() => {
            showToast('خطا در تأیید کد.', 'error');
            btn.disabled = false;
            btn.innerHTML = `${ICONS.login} <span>ورود به کارتابل</span>`;
        });
}
