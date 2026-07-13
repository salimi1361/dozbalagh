(() => {
    let deferredPrompt = null;
    const standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

    function deviceUuid() {
        let id = localStorage.getItem('dozoleh_pwa_device_uuid');
        if (!id) {
            id = window.crypto?.randomUUID?.() || `device-${Date.now()}-${Math.random().toString(16).slice(2)}`;
            localStorage.setItem('dozoleh_pwa_device_uuid', id);
        }
        return id;
    }

    function deviceInfo() {
        const ua = navigator.userAgent;
        const deviceType = /ipad|tablet/i.test(ua) ? 'tablet' : /mobile|android|iphone|ipod/i.test(ua) ? 'mobile' : 'desktop';
        const browser = /edg/i.test(ua) ? 'Edge' : /opr|opera/i.test(ua) ? 'Opera' : /chrome|crios/i.test(ua) ? 'Chrome' : /safari/i.test(ua) ? 'Safari' : /firefox|fxios/i.test(ua) ? 'Firefox' : 'Other';
        return { device_type: deviceType, browser, platform: navigator.userAgentData?.platform || navigator.platform || 'unknown' };
    }

    async function record(event) {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!token) return;
        try {
            await fetch('/pwa/installations', {
                method: 'POST',
                credentials: 'same-origin',
                keepalive: true,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ device_uuid: deviceUuid(), event, ...deviceInfo() })
            });
        } catch (_) {}
    }

    function updateInstallButtons() {
        document.querySelectorAll('[data-pwa-install]').forEach(button => {
            button.classList.toggle('hidden', !deferredPrompt || standalone);
            button.style.display = deferredPrompt && !standalone ? 'flex' : 'none';
            button.onclick = async () => {
                if (!deferredPrompt) return;
                deferredPrompt.prompt();
                await deferredPrompt.userChoice;
                deferredPrompt = null;
                updateInstallButtons();
            };
        });
    }

    window.addEventListener('beforeinstallprompt', event => {
        event.preventDefault();
        deferredPrompt = event;
        updateInstallButtons();
    });
    window.addEventListener('appinstalled', () => record('installed'));
    document.addEventListener('DOMContentLoaded', () => {
        if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js').catch(() => {});
        updateInstallButtons();
        const lastSeen = Number(localStorage.getItem('dozoleh_pwa_last_seen') || 0);
        if (standalone || Date.now() - lastSeen > 6 * 60 * 60 * 1000) {
            record(standalone ? 'standalone' : 'seen');
            localStorage.setItem('dozoleh_pwa_last_seen', String(Date.now()));
        }
    });
})();
