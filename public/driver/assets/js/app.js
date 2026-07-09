const API_BASE = '/api/v1/driver';
let watchId = null;
let currentTab = 'home';
let permitsCache = [];
let notificationsCache = [];
let gpsPermissionState = 'prompt';
let deferredInstallPrompt = null;
let notificationPollTimer = null;

document.addEventListener('DOMContentLoaded', initApp);
window.addEventListener('beforeinstallprompt', event => {
    event.preventDefault();
    deferredInstallPrompt = event;
    const installBtn = document.getElementById('install-app-btn');
    if (installBtn) installBtn.disabled = false;
});
window.addEventListener('appinstalled', () => {
    deferredInstallPrompt = null;
    localStorage.setItem('driver_app_installed', 'true');
    renderOpenInstalledAppHint();
});

function initApp() {
    registerServiceWorker();
    const token = localStorage.getItem('driver_token');
    const main = document.getElementById('main-content');
    main.classList.add('is-centered');

    if (shouldShowInstallGate()) {
        document.getElementById('bottom-navigation').classList.add('hidden');
        renderInstallGate();
        return;
    }

    if (!token) {
        document.getElementById('bottom-navigation').classList.add('hidden');
        renderLoginScreen();
        return;
    }

    document.getElementById('bottom-navigation').classList.remove('hidden');
    main.classList.remove('is-centered');
    startNotificationPolling();
    requestBrowserNotificationPermission();
    renderDashboard();
    syncGpsStatus();
    resumeTripTrackingIfNeeded();
}

function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) return;
    navigator.serviceWorker.register('sw.js').catch(() => {});
}

function isStandaloneApp() {
    return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
}

function shouldShowInstallGate() {
    return !isStandaloneApp();
}

function renderInstallGate() {
    const main = document.getElementById('main-content');
    main.classList.add('is-centered');
    const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);

    main.innerHTML = `
        <div class="install-gate card">
            <div class="auth-icon auth-icon--logo">
                ${associationLogoHtml('association-logo--auth')}
            </div>
            <h1 class="auth-title">نصب وب‌اپ راننده</h1>
            <p class="auth-subtitle">برای ورود به سامانه، ابتدا وب‌اپ دوزوله را روی گوشی نصب کنید.</p>
            <div class="install-steps">
                ${isIos
                    ? `
                        <div><b>۱</b><span>دکمه Share مرورگر Safari را بزنید.</span></div>
                        <div><b>۲</b><span>گزینه Add to Home Screen را انتخاب کنید.</span></div>
                        <div><b>۳</b><span>پس از نصب، آیکن دوزوله را از صفحه اصلی گوشی باز کنید.</span></div>
                    `
                    : `
                        <div><b>۱</b><span>دکمه نصب را بزنید. اگر دکمه نصب عمل نکرد، از منوی مرورگر گزینه Install app یا Add to Home screen را انتخاب کنید.</span></div>
                        <div><b>۲</b><span>بعد از نصب، برنامه را از صفحه اصلی گوشی باز کنید.</span></div>
                    `
                }
            </div>
            ${isIos
                ? `<button type="button" onclick="continueAfterInstallGuide()" class="btn btn--primary">متوجه شدم</button>`
                : `<button type="button" onclick="installDriverApp()" id="install-app-btn" class="btn btn--primary">
                    <i class="fa-solid fa-download"></i>
                    <span>نصب وب‌اپ راننده</span>
                </button>`
            }
        </div>`;
}

function installDriverApp() {
    if (!deferredInstallPrompt) {
        showToast('اگر گزینه نصب نمایش داده نشد، از منوی مرورگر گزینه نصب برنامه را انتخاب کنید.', 'info');
        return;
    }

    deferredInstallPrompt.prompt();
    deferredInstallPrompt.userChoice.then(choice => {
        if (choice.outcome === 'accepted') {
            localStorage.setItem('driver_app_installed', 'true');
            renderOpenInstalledAppHint();
        }
        deferredInstallPrompt = null;
    });
}

function continueAfterInstallGuide() {
    renderOpenInstalledAppHint();
}

function renderOpenInstalledAppHint() {
    const main = document.getElementById('main-content');
    main.classList.add('is-centered');
    main.innerHTML = `
        <div class="install-gate card">
            <div class="auth-icon auth-icon--logo">
                ${associationLogoHtml('association-logo--auth')}
            </div>
            <h1 class="auth-title">وب‌اپ آماده ورود است</h1>
            <p class="auth-subtitle">برای ورود، آیکن دوزوله را از صفحه اصلی گوشی باز کنید. ورود از داخل مرورگر فعال نیست.</p>
            <div class="install-steps">
                <div><b>۱</b><span>به صفحه اصلی گوشی برگردید.</span></div>
                <div><b>۲</b><span>آیکن دوزوله را لمس کنید.</span></div>
                <div><b>۳</b><span>بعد از باز شدن برنامه، شماره موبایل راننده را وارد کنید.</span></div>
            </div>
        </div>`;
}

function switchTab(tab) {
    currentTab = tab;
    ['home', 'fleet', 'company', 'profile'].forEach(t => {
        const el = document.getElementById(`tab-${t}`);
        if (el) el.classList.toggle('is-active', t === tab);
    });
    renderDashboard();
}

function getDriver() {
    try {
        return JSON.parse(localStorage.getItem('driver_info')) || {};
    } catch {
        return {};
    }
}

function authHeaders() {
    return {
        'Authorization': `Bearer ${localStorage.getItem('driver_token')}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    };
}

function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast toast--${type}`;
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 3200);
}

function requestBrowserNotificationPermission() {
    if (!('Notification' in window)) return;
    if (Notification.permission === 'default') {
        Notification.requestPermission().catch(() => {});
    }
}

function startNotificationPolling() {
    if (notificationPollTimer) return;
    notificationPollTimer = setInterval(() => fetchNotifications(true), 10000);
}

function notifyNewDriverNotifications(items) {
    if (!Array.isArray(items) || items.length === 0) return;
    const unreadItems = items.filter(item => !item.read_at);
    if (unreadItems.length === 0) return;

    const latestId = unreadItems[0].id;
    const lastSeenId = localStorage.getItem('driver_last_notification_id');

    if (!lastSeenId) {
        localStorage.setItem('driver_last_notification_id', latestId);
        return;
    }

    if (latestId === lastSeenId) return;

    localStorage.setItem('driver_last_notification_id', latestId);
    const latest = unreadItems[0];

    if ('Notification' in window && Notification.permission === 'granted') {
        navigator.serviceWorker?.ready
            .then(registration => registration.showNotification(latest.title || 'اعلان دوزوله', {
                body: latest.message || 'اعلان جدید برای شما ثبت شد.',
                icon: '/driver/icon-192.png',
                badge: '/driver/icon-192.png',
                data: { permitId: latest.permit_id || null },
            }))
            .catch(() => new Notification(latest.title || 'اعلان دوزوله', {
                body: latest.message || 'اعلان جدید برای شما ثبت شد.',
                icon: '/driver/icon-192.png',
            }));
    }
}

function closeModal() {
    document.getElementById('modal-container').innerHTML = '';
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, ch => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
    }[ch]));
}

function displayValue(value, fallback = '—') {
    const text = String(value ?? '').trim();
    return text ? escapeHtml(text) : fallback;
}

function detailRow(label, value) {
    return `
        <div class="info-row">
            <span>${escapeHtml(label)}</span>
            <b>${displayValue(value)}</b>
        </div>`;
}

function datePairHtml(label, jalali, gregorian) {
    if (!jalali && !gregorian) return '';
    return `
        <div class="date-duo">
            <span class="date-duo__label">${escapeHtml(label)}</span>
            <span><b>شمسی</b>${displayValue(jalali)}</span>
            <span><b>میلادی</b>${displayValue(gregorian)}</span>
        </div>`;
}

function associationLogoHtml(extraClass = '') {
    return `<img class="association-logo ${extraClass}" src="logo.png" alt="لوگوی انجمن شرکت‌های حمل و نقل بین‌المللی خراسان رضوی">`;
}

function gpsStatusLabel() {
    if (localStorage.getItem('is_on_trip') === 'true' || gpsPermissionState === 'granted') {
        return 'فعال';
    }
    if (gpsPermissionState === 'denied') {
        return 'رد شده';
    }
    return 'در انتظار';
}

function updateGpsBadge() {
    const gpsBadge = document.getElementById('home-gps-badge');
    if (gpsBadge) gpsBadge.textContent = gpsStatusLabel();
}

function syncGpsStatus() {
    if (!navigator.geolocation) {
        gpsPermissionState = 'denied';
        updateGpsBadge();
        return;
    }

    if (navigator.permissions && navigator.permissions.query) {
        navigator.permissions.query({ name: 'geolocation' })
            .then(permission => {
                gpsPermissionState = permission.state;
                updateGpsBadge();
                permission.onchange = () => {
                    gpsPermissionState = permission.state;
                    updateGpsBadge();
                };
            })
            .catch(updateGpsBadge);
    } else {
        updateGpsBadge();
    }
}

function startLocationWatch(permitId, silent = false) {
    if (!navigator.geolocation) {
        showToast('GPS در این دستگاه پشتیبانی نمی‌شود.', 'error');
        gpsPermissionState = 'denied';
        updateGpsBadge();
        return false;
    }

    if (watchId) navigator.geolocation.clearWatch(watchId);

    localStorage.setItem('active_permit_id', permitId);
    localStorage.setItem('is_on_trip', 'true');
    watchId = navigator.geolocation.watchPosition(
        pos => {
            gpsPermissionState = 'granted';
            updateGpsBadge();
            sendLocation(pos.coords.latitude, pos.coords.longitude, permitId);
        },
        () => {
            gpsPermissionState = 'denied';
            localStorage.removeItem('is_on_trip');
            updateTripButtonUI();
            updateGpsBadge();
            if (!silent) showToast('دسترسی به GPS رد شد.', 'error');
        },
        { enableHighAccuracy: true, maximumAge: 10000, timeout: 20000 }
    );
    updateTripButtonUI();
    updateGpsBadge();
    return true;
}

function resumeTripTrackingIfNeeded() {
    if (localStorage.getItem('is_on_trip') !== 'true') return;
    const permitId = localStorage.getItem('active_permit_id');
    if (permitId) startLocationWatch(permitId, true);
}

function toEnglishDigits(value) {
    const fa = '۰۱۲۳۴۵۶۷۸۹';
    const ar = '٠١٢٣٤٥٦٧٨٩';
    return String(value ?? '').replace(/[۰-۹٠-٩]/g, d => {
        const faIndex = fa.indexOf(d);
        if (faIndex >= 0) return String(faIndex);
        const arIndex = ar.indexOf(d);
        return arIndex >= 0 ? String(arIndex) : d;
    });
}

function renderPlate(plateStr) {
    if (!plateStr || String(plateStr).includes('ثبت نشده')) {
        return '<span class="badge badge--used">پلاک ثبت نشده</span>';
    }

    const normalized = toEnglishDigits(plateStr).replace(/[()]/g, ' ').replace(/\s+/g, ' ').trim();
    const cityMatch = normalized.match(/(?:-|ایران|IRAN|\s)(\d{2})\s*$/i);
    const cityCode = cityMatch ? cityMatch[1] : '—';
    const mainPart = cityMatch ? normalized.slice(0, cityMatch.index).trim() : normalized;
    const alphaMatch = mainPart.match(/[آ-یA-Za-z]/);
    const alpha = alphaMatch ? alphaMatch[0] : 'ع';
    const digits = mainPart.replace(/[^0-9]/g, '');
    const num1 = digits.substring(0, 2) || '—';
    const num2 = digits.substring(2, 5) || '—';

    return `
        <div class="iran-plate" aria-label="پلاک ${escapeHtml(plateStr)}">
            <div class="iran-plate__flag">
                <span style="font-size:7px;line-height:1;font-weight:900">I.R.</span>
                <span style="font-size:6px;line-height:1;font-weight:900">IRAN</span>
            </div>
            <div class="iran-plate__digits">
                <span>${escapeHtml(num1)}</span>
                <span class="iran-plate__alpha">${escapeHtml(alpha)}</span>
                <span>${escapeHtml(num2)}</span>
            </div>
            <div class="iran-plate__city">
                <span style="font-weight:900;color:#475569">ایران</span>
                <span style="font-weight:900">${escapeHtml(cityCode)}</span>
            </div>
        </div>`;
}

function statusBadgeClass(statusKey) {
    const map = {
        pending: 'badge--pending',
        approved: 'badge--active',
        issued: 'badge--active',
        started_trip: 'badge--transit',
        in_transit: 'badge--transit',
        at_border_out: 'badge--transit',
        at_destination: 'badge--used',
        used: 'badge--used',
        expired: 'badge--expired',
        lost: 'badge--expired',
        rejected: 'badge--expired',
    };
    return map[statusKey] || 'badge--active';
}

function renderDashboard() {
    const main = document.getElementById('main-content');
    const driver = getDriver();

    if (currentTab === 'home') renderHomeTab(main, driver);
    else if (currentTab === 'fleet') renderFleetTab(main, driver);
    else if (currentTab === 'company') renderCompanyTab(main, driver);
    else if (currentTab === 'profile') renderProfileTab(main, driver);
}

function renderHomeTab(main, driver) {
    main.innerHTML = `
        <div class="stack">
            <div class="card card--hero">
                <div class="label">خوش آمدید</div>
                <div class="name">${driver.name || 'راننده'}</div>
                <div class="meta">${driver.company_name || 'شرکت حمل و نقل'}</div>
            </div>

            <div>
                <div class="section-title">اعلان‌های راننده <span id="notifications-unread-badge" class="section-counter">۰</span></div>
                <div id="notifications-container" class="stack-sm">
                    <div class="loader-wrap"><div class="loader"></div></div>
                </div>
            </div>

            <div class="metric-grid">
                <div class="metric-card">
                    <div class="metric-card__icon metric-card__icon--blue">
                        <i class="fa-solid fa-file-lines"></i>
                    </div>
                    <div>
                        <span class="metric-card__label">دوزوله‌های متصل</span>
                        <div class="metric-card__value" id="home-active-badge">—</div>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-card__icon metric-card__icon--teal">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <div>
                        <span class="metric-card__label">وضعیت GPS</span>
                        <div class="metric-card__value" id="home-gps-badge">${gpsStatusLabel()}</div>
                    </div>
                </div>
            </div>

            <button type="button" onclick="toggleTripState()" id="btn-trip-state" class="btn btn--success">
                <i class="fa-solid fa-location-arrow"></i>
                <span>شروع ارسال موقعیت مکانی</span>
            </button>

            <div>
                <div class="section-title">گزارش دوزوله‌های متصل</div>
                <div id="permits-container" class="stack-sm">
                    <div class="loader-wrap"><div class="loader"></div></div>
                </div>
            </div>
        </div>`;

    updateTripButtonUI();
    syncGpsStatus();
    fetchNotifications();
    fetchPermits();
}

function renderFleetTab(main, driver) {
    main.innerHTML = `
        <div class="stack">
            <div class="card card--hero">
                <div class="label">ناوگان متصل به راننده</div>
                <div class="name">${driver.truck_type || 'خودرو باری'}</div>
                <div class="meta">کارت هوشمند: ${driver.truck_smart_card || 'ثبت نشده'}</div>
            </div>

            <div class="card info-card">
                <div class="info-card__title">
                    <span><i class="fa-solid fa-truck" style="color:#0369a1"></i> مشخصات ناوگان</span>
                </div>
                <div class="fleet-plate-wrap">
                    ${renderPlate(driver.truck_plate)}
                </div>
                <div class="fleet-summary">
                    <div class="fleet-mini-card">
                        <span>کارت هوشمند خودرو</span>
                        <b>${driver.truck_smart_card || '—'}</b>
                    </div>
                    <div class="fleet-mini-card">
                        <span>نوع خودرو</span>
                        <b>${driver.truck_type || '—'}</b>
                    </div>
                </div>
            </div>
        </div>`;
}

function renderCompanyTab(main, driver) {
    main.innerHTML = `
        <div class="stack">
            <div class="card info-card">
                <div class="info-card__title">
                    <i class="fa-solid fa-building" style="color:#0d9488"></i>
                    شرکت کارفرما
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
                    <b style="max-width:55%;text-align:left;line-height:1.5">${driver.company_address || '—'}</b>
                </div>
            </div>
        </div>`;
}

function renderProfileTab(main, driver) {
    main.innerHTML = `
        <div class="stack">
            <div class="card profile-card">
                <div class="profile-avatar"><i class="fa-solid fa-user"></i></div>
                <div class="profile-name">${driver.name || 'راننده'}</div>
                <div class="profile-meta">کد ملی: ${driver.national_code || '—'}</div>
                <div style="margin-top:24px">
                    <button type="button" onclick="logout()" class="btn btn--ghost">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>خروج از حساب</span>
                    </button>
                </div>
            </div>
        </div>`;
}

function fetchPermits() {
    const container = document.getElementById('permits-container');
    if (!container) return;

    fetch(`${API_BASE}/permits`, { headers: authHeaders() })
        .then(res => {
            if (res.status === 401) { logout(); throw new Error('unauthorized'); }
            return res.json();
        })
        .then(data => {
            const badge = document.getElementById('home-active-badge');
            if (!data || data.status !== 'success') {
                if (badge) badge.textContent = '۰';
                container.innerHTML = emptyPermitsHtml();
                return;
            }

            permitsCache = data.data || [];
            if (badge) badge.textContent = permitsCache.length.toLocaleString('fa-IR');
            if (localStorage.getItem('is_on_trip') === 'true' && !localStorage.getItem('active_permit_id') && permitsCache[0]) {
                startLocationWatch(permitsCache[0].id, true);
            }

            if (permitsCache.length === 0) {
                container.innerHTML = emptyPermitsHtml();
                return;
            }

            container.innerHTML = permitsCache.map(p => `
                <div class="card permit-card" onclick="showPermitDetail(${p.id})">
                    <div class="permit-card__header">
                        <div>
                            <span class="permit-card__serial-label">کد پرونده دوزوله</span>
                            <div class="permit-card__serial">${displayValue(p.d_code || p.serial_number)}</div>
                            <div class="permit-card__subserial">سریال: ${displayValue(p.serial_number)}</div>
                        </div>
                        <span class="badge ${statusBadgeClass(p.status_key)}">${displayValue(p.status_label)}</span>
                    </div>
                    <div class="permit-card__meta-grid">
                        <div>
                            <span>مقصد</span>
                            <b>${displayValue(p.country_name || p.destination)}</b>
                        </div>
                        <div>
                            <span>نوع بار</span>
                            <b>${displayValue(p.cargo_type || p.permit_type)}</b>
                        </div>
                    </div>
                    <div class="permit-card__dates">
                        ${datePairHtml('صدور', p.issue_date_jalali || p.issue_date, p.issue_date_gregorian)}
                    </div>
                    <div class="permit-card__row">
                        <span>شرکت: <b>${displayValue(p.company_name)}</b></span>
                        <span>ناوگان: <b>${displayValue(p.fleet_plate || p.truck_type)}</b></span>
                    </div>
                    <div class="permit-card__hint">
                        <span>برای مشاهده اطلاعات کامل لمس کنید</span>
                        <i class="fa-solid fa-chevron-left"></i>
                    </div>
                </div>
            `).join('');
        })
        .catch(() => {
            const badge = document.getElementById('home-active-badge');
            if (badge) badge.textContent = '—';
            container.innerHTML = `
                <div class="card empty-state">
                    <i class="fa-solid fa-wifi-slash"></i>
                    <p>خطا در دریافت دوزوله‌ها. اتصال را بررسی کنید.</p>
                </div>`;
            setConnectionStatus(false);
        });
}

function fetchNotifications(silent = false) {
    const container = document.getElementById('notifications-container');

    fetch(`${API_BASE}/notifications`, { headers: authHeaders() })
        .then(res => {
            if (res.status === 401) { logout(); throw new Error('unauthorized'); }
            return res.json();
        })
        .then(data => {
            if (!data || data.status !== 'success') {
                if (container) container.innerHTML = emptyNotificationsHtml();
                return;
            }

            notificationsCache = data.data || [];
            notifyNewDriverNotifications(notificationsCache);

            const badge = document.getElementById('notifications-unread-badge');
            if (badge) badge.textContent = Number(data.unread_count || 0).toLocaleString('fa-IR');

            if (!container) return;

            if (notificationsCache.length === 0) {
                container.innerHTML = emptyNotificationsHtml();
                return;
            }

            const latest = notificationsCache[0];
            const unreadCount = Number(data.unread_count || 0);
            container.innerHTML = `
                <button type="button" class="notification-summary-card ${unreadCount > 0 ? 'is-unread' : ''}" onclick="showNotificationsModal()">
                    <span class="notification-summary-card__icon"><i class="fa-solid fa-bell"></i></span>
                    <span class="notification-card__body">
                        <b>${unreadCount > 0 ? `${unreadCount.toLocaleString('fa-IR')} اعلان خوانده‌نشده` : 'اعلان‌های راننده'}</b>
                        <small>${displayValue(latest.message || 'برای مشاهده اعلان‌ها لمس کنید.')}</small>
                    </span>
                    <span class="notification-summary-card__action">مشاهده</span>
                </button>`;
        })
        .catch(() => {
            if (container && !silent) container.innerHTML = emptyNotificationsHtml();
        });
}

function emptyNotificationsHtml() {
    return `
        <div class="card empty-state empty-state--compact">
            <i class="fa-solid fa-bell-slash"></i>
            <p>اعلان جدیدی برای شما ثبت نشده است.</p>
        </div>`;
}

function openNotification(id, permitId) {
    fetch(`${API_BASE}/notifications/${id}/read`, {
        method: 'POST',
        headers: authHeaders(),
        body: JSON.stringify({}),
    }).finally(() => fetchNotifications());

    if (permitId) {
        closeModal();
        showPermitDetail(permitId);
    }
}

function openCompanyMessageNotification(notificationId, messageId) {
    fetch(`${API_BASE}/notifications/${notificationId}/read`, {
        method: 'POST',
        headers: authHeaders(),
        body: JSON.stringify({}),
    }).finally(() => fetchNotifications());

    const item = notificationsCache.find(notification => notification.id === notificationId) || {};
    document.getElementById('modal-container').innerHTML = `
        <div class="modal-overlay" onclick="if(event.target===this)closeModal()">
            <div class="modal-sheet">
                <div class="modal-handle"></div>
                <div class="modal-title">${displayValue(item.title || 'پیام شرکت')}</div>
                <div class="stack-sm">
                    <div class="info-block">
                        <span class="info-block__label">${displayValue(item.company_name || 'شرکت حمل و نقل')}</span>
                        <span class="info-block__value" style="font-size:13px;line-height:1.9">${displayValue(item.message)}</span>
                    </div>
                    <div class="form-group">
                        <label class="input-label" for="company-message-reply">پاسخ شما</label>
                        <textarea id="company-message-reply" class="input-field" style="min-height:110px;resize:none;line-height:1.8;padding-top:12px" placeholder="پاسخ خود را برای شرکت بنویسید..."></textarea>
                    </div>
                    <button type="button" onclick="sendCompanyMessageReply(${Number(messageId) || 0})" class="btn btn--primary">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span>ارسال پاسخ</span>
                    </button>
                </div>
            </div>
        </div>`;
}

function sendCompanyMessageReply(messageId) {
    const textarea = document.getElementById('company-message-reply');
    const message = textarea ? textarea.value.trim() : '';

    if (!messageId) {
        showToast('شناسه پیام معتبر نیست.', 'error');
        return;
    }

    if (!message) {
        showToast('متن پاسخ را وارد کنید.', 'error');
        return;
    }

    fetch(`${API_BASE}/company-messages/reply`, {
        method: 'POST',
        headers: authHeaders(),
        body: JSON.stringify({ message_id: messageId, message }),
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                closeModal();
                showToast(data.message || 'پاسخ ارسال شد.', 'success');
                fetchNotifications();
            } else {
                showToast(data.message || 'ارسال پاسخ انجام نشد.', 'error');
            }
        })
        .catch(() => showToast('خطا در ارسال پاسخ.', 'error'));
}

function deleteNotification(id, event) {
    if (event) event.stopPropagation();

    fetch(`${API_BASE}/notifications/${id}`, {
        method: 'DELETE',
        headers: authHeaders(),
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                notificationsCache = notificationsCache.filter(item => item.id !== id);
                showToast('اعلان حذف شد.', 'success');
                fetchNotifications();
                showNotificationsModal();
            } else {
                showToast('حذف اعلان انجام نشد.', 'error');
            }
        })
        .catch(() => showToast('خطا در حذف اعلان.', 'error'));
}

function showNotificationsModal() {
    const items = notificationsCache.length
        ? notificationsCache
        : [];

    const listHtml = items.length
        ? items.map(item => `
            <div class="notification-card ${item.read_at ? '' : 'is-unread'}">
                <button type="button" class="notification-card__open" onclick="${item.type === 'company_message' && item.message_id ? `openCompanyMessageNotification('${escapeHtml(item.id)}', ${Number(item.message_id)})` : `openNotification('${escapeHtml(item.id)}', ${item.permit_id || 'null'})`}">
                    <span class="notification-card__icon"><i class="fa-solid fa-bell"></i></span>
                    <span class="notification-card__body">
                        <b>${displayValue(item.title)}</b>
                        <small>${displayValue(item.message)}</small>
                        ${item.type === 'company_message' && item.message_id ? '<small style="color:#0b63ce;font-weight:950">مشاهده و پاسخ به شرکت</small>' : ''}
                    </span>
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <button type="button" class="notification-card__delete" onclick="deleteNotification('${escapeHtml(item.id)}', event)" aria-label="حذف اعلان">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        `).join('')
        : emptyNotificationsHtml();

    document.getElementById('modal-container').innerHTML = `
        <div class="modal-overlay" onclick="if(event.target===this)closeModal()">
            <div class="modal-sheet">
                <div class="modal-handle"></div>
                <div class="modal-title">اعلان‌های راننده</div>
                <div class="stack-sm">${listHtml}</div>
            </div>
        </div>`;
}

function emptyPermitsHtml() {
    return `
        <div class="card empty-state">
            <i class="fa-solid fa-folder-open"></i>
            <p>دوزوله‌ای برای راننده یا ناوگان شما ثبت نشده است.</p>
        </div>`;
}

function showPermitDetail(id) {
    fetch(`${API_BASE}/permits/${id}`, { headers: authHeaders() })
        .then(res => res.json())
        .then(data => {
            if (data.status !== 'success') {
                showToast('جزئیات دوزوله یافت نشد.', 'error');
                return;
            }
            const p = data.data;
            const events = (p.events || []).map(e => `
                <div class="info-row">
                    <span>${displayValue(e.status_label || e.event_type)}</span>
                    <b>${displayValue(e.created_at)}</b>
                </div>
            `).join('') || '<p class="modal-empty-text">رویدادی ثبت نشده</p>';
            const dozolehItems = (p.dozoleh_items || []).map((item, index) => `
                <div class="dozoleh-item">
                    <div class="dozoleh-item__head">
                        <span>ردیف ${Number(index + 1).toLocaleString('fa-IR')}</span>
                        <b>${displayValue(item.serial_number || item.allocation_status)}</b>
                    </div>
                    <div class="dozoleh-item__grid">
                        <div><span>کشور</span><b>${displayValue(item.country_name)}</b></div>
                        <div><span>نوع دوزوله</span><b>${displayValue(item.permit_type)}</b></div>
                        <div><span>نوع بار</span><b>${displayValue(item.operation_type)}</b></div>
                        <div><span>مبدا</span><b>${displayValue(item.loading_origin)}</b></div>
                        <div><span>مقصد</span><b>${displayValue(item.loading_destination)}</b></div>
                        <div><span>کد سفر</span><b>${displayValue(item.trip_code)}</b></div>
                    </div>
                    ${datePairHtml('CMR', item.cmr_date_jalali, item.cmr_date_gregorian)}
                    ${datePairHtml('کارنه تیر', item.tir_carnet_date_jalali, item.tir_carnet_date_gregorian)}
                </div>
            `).join('');

            document.getElementById('modal-container').innerHTML = `
                <div class="modal-overlay" onclick="if(event.target===this)closeModal()">
                    <div class="modal-sheet">
                        <div class="modal-handle"></div>
                        <div class="modal-title">جزئیات دوزوله</div>
                        <div class="stack-sm">
                            <div class="info-block info-block--highlight">
                                <span class="info-block__label">کد پرونده</span>
                                <span class="info-block__value">${displayValue(p.d_code || p.serial_number)}</span>
                                <small>سریال دوزوله: ${displayValue(p.serial_number)}</small>
                            </div>
                            <div class="modal-date-grid">
                                ${datePairHtml('تاریخ صدور', p.issue_date_jalali || p.issue_date, p.issue_date_gregorian)}
                                ${datePairHtml('اعتبار تا', p.valid_until_jalali || p.valid_until, p.valid_until_gregorian)}
                            </div>
                            ${detailRow('وضعیت', p.status_label)}
                            ${detailRow('کشور مقصد', p.country_name)}
                            ${detailRow('مبدا بارگیری', p.origin)}
                            ${detailRow('مقصد بارگیری', p.destination)}
                            ${detailRow('نوع دوزوله', p.permit_type)}
                            ${detailRow('نوع بار', p.cargo_type)}
                            ${detailRow('کد CITS', p.cits_code)}
                            ${detailRow('کد سفر', p.trip_code)}
                            ${detailRow('کد فیش', p.receipt_code)}
                            ${detailRow('اعتبار روزانه', p.validity_days)}
                            ${detailRow('ناوگان', p.fleet_plate || p.truck_type)}
                            ${detailRow('کارت هوشمند ناوگان', p.fleet_smart_card)}
                            ${detailRow('شرکت', p.company_name)}
                            ${detailRow('مبلغ', p.total_amount ? `${p.total_amount} ریال` : null)}
                            ${datePairHtml('تاریخ CMR', p.cmr_date_jalali, p.cmr_date_gregorian)}
                            ${detailRow('شماره کارنه تیر', p.tir_carnet_number)}
                            ${datePairHtml('تاریخ کارنه تیر', p.tir_carnet_date_jalali, p.tir_carnet_date_gregorian)}
                            ${p.company_note ? `<div class="info-block"><span class="info-block__label">یادداشت شرکت</span><span class="info-block__value">${displayValue(p.company_note)}</span></div>` : ''}
                            ${dozolehItems ? `<div class="section-title" style="margin-top:8px">ردیف‌های دوزوله</div>${dozolehItems}` : ''}
                            <div class="section-title" style="margin-top:8px">تاریخچه رویدادها</div>
                            ${events}
                            <button type="button" onclick="selectPermitForTrip(${p.id}); closeModal();" class="btn btn--outline" style="margin-top:8px">
                                <i class="fa-solid fa-check"></i>
                                انتخاب برای ردیابی GPS
                            </button>
                        </div>
                    </div>
                </div>`;
        })
        .catch(() => showToast('خطا در بارگذاری جزئیات.', 'error'));
}

function selectPermitForTrip(id) {
    localStorage.setItem('active_permit_id', id);
    showToast('دوزوله برای ردیابی انتخاب شد.', 'success');
}

function toggleTripState() {
    if (localStorage.getItem('is_on_trip') === 'true') {
        if (watchId) navigator.geolocation.clearWatch(watchId);
        watchId = null;
        localStorage.removeItem('is_on_trip');
        showToast('ارسال موقعیت متوقف شد.', 'info');
    } else {
        const permitId = localStorage.getItem('active_permit_id') || (permitsCache[0] && permitsCache[0].id);
        if (!permitId) {
            showToast('ابتدا یک دوزوله انتخاب کنید.', 'error');
            return;
        }
        if (startLocationWatch(permitId)) showToast('ارسال موقعیت آغاز شد.', 'success');
    }
    updateTripButtonUI();
    updateGpsBadge();
}

function updateTripButtonUI() {
    const btn = document.getElementById('btn-trip-state');
    if (!btn) return;
    const onTrip = localStorage.getItem('is_on_trip') === 'true';
    btn.className = onTrip ? 'btn btn--danger' : 'btn btn--success';
    btn.innerHTML = onTrip
        ? '<i class="fa-solid fa-stop"></i><span>توقف ارسال موقعیت</span>'
        : '<i class="fa-solid fa-location-arrow"></i><span>شروع ارسال موقعیت مکانی</span>';
}

function sendLocation(lat, lng, permitId) {
    fetch(`${API_BASE}/location/sync`, {
        method: 'POST',
        headers: authHeaders(),
        body: JSON.stringify({
            dozbalagh_item_id: parseInt(permitId, 10),
            latitude: lat,
            longitude: lng,
        }),
    }).catch(() => {});
}

function setConnectionStatus(online) {
    const el = document.getElementById('connection-status');
    if (!el) return;
    el.textContent = online ? 'متصل' : 'آفلاین';
    el.classList.toggle('is-offline', !online);
}

function logout() {
    if (watchId) navigator.geolocation.clearWatch(watchId);
    localStorage.clear();
    location.reload();
}

function renderLoginScreen() {
    document.getElementById('main-content').innerHTML = `
        <div class="auth-wrap">
            <div class="auth-icon auth-icon--logo">${associationLogoHtml('association-logo--auth')}</div>
            <h1 class="auth-title">ورود رانندگان</h1>
            <p class="auth-subtitle">کد یکبار مصرف به موبایل ثبت‌شده ارسال می‌شود</p>
            <div class="card auth-card">
                <div class="form-group">
                    <label class="input-label" for="driver-mobile">شماره موبایل</label>
                    <input type="tel" id="driver-mobile" class="input-field" placeholder="۰۹۱۲۳۴۵۶۷۸۹" maxlength="11" inputmode="numeric">
                </div>
                <button type="button" onclick="handleRequestOtp()" id="btn-submit" class="btn btn--primary" style="margin-top:20px">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>دریافت کد تأیید</span>
                </button>
            </div>
        </div>`;
}

function handleRequestOtp() {
    const mobile = document.getElementById('driver-mobile').value.trim();
    if (!/^09[0-9]{9}$/.test(mobile)) {
        showToast('شماره موبایل معتبر وارد کنید.', 'error');
        return;
    }

    const btn = document.getElementById('btn-submit');
    btn.disabled = true;
    btn.innerHTML = '<div class="loader" style="width:18px;height:18px;border-width:2px"></div>';

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
                btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i><span>دریافت کد تأیید</span>';
            }
        })
        .catch(() => {
            showToast('خطا در ارتباط با سرور.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i><span>دریافت کد تأیید</span>';
        });
}

function renderOtpScreen(mobile) {
    document.getElementById('main-content').innerHTML = `
        <div class="auth-wrap">
            <div class="auth-icon auth-icon--logo">${associationLogoHtml('association-logo--auth')}</div>
            <h1 class="auth-title">تأیید کد</h1>
            <p class="auth-subtitle">کد ۵ رقمی ارسال‌شده به ${mobile} را وارد کنید</p>
            <div class="card auth-card">
                <div class="form-group">
                    <label class="input-label" for="otp-code">کد یکبار مصرف</label>
                    <input type="number" id="otp-code" class="input-field" placeholder="· · · · ·" inputmode="numeric">
                </div>
                <button type="button" onclick="handleVerifyOtp('${mobile}')" id="btn-verify" class="btn btn--primary" style="margin-top:20px">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span>ورود به کارتابل</span>
                </button>
                <button type="button" onclick="renderLoginScreen()" class="btn btn--outline" style="margin-top:10px">
                    تغییر شماره موبایل
                </button>
            </div>
        </div>`;
}

function handleVerifyOtp(mobile) {
    const code = document.getElementById('otp-code').value.trim();
    if (code.length !== 5) {
        showToast('کد ۵ رقمی را کامل وارد کنید.', 'error');
        return;
    }

    const btn = document.getElementById('btn-verify');
    btn.disabled = true;

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
                showToast('ورود موفق.', 'success');
                renderDashboard();
                syncGpsStatus();
                resumeTripTrackingIfNeeded();
            } else {
                showToast(data.message || 'کد اشتباه است.', 'error');
                btn.disabled = false;
            }
        })
        .catch(() => {
            showToast('خطا در تأیید کد.', 'error');
            btn.disabled = false;
        });
}
