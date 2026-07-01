// 👈 تغییر دادن این شماره نسخه در آینده، کِش تمام راننده‌ها را فوراً آپدیت می‌کند
const CACHE_NAME = 'dozbalagh-driver-v3.0';
const ASSETS = [
    '/driver/',
    '/driver/index.html',
    '/driver/manifest.json',
    '/driver/assets/css/app.css',
    '/driver/assets/js/app.js'
];

self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS);
        })
    );
    self.skipWaiting(); // 👈 اجبار به فعال‌سازی آنی نسخه جدید
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key); // 👈 پاک کردن کش‌های قدیمی
                    }
                })
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', (e) => {
    // از شبکه بگیر، اگر اینترنت نبود از کش بخون (بهترین استراتژی برای برنامه‌های زنده)
    e.respondWith(
        fetch(e.request).catch(() => caches.match(e.request))
    );
});