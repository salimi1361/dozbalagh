// 👈 تغییر دادن این شماره نسخه در آینده، کِش تمام راننده‌ها را فوراً آپدیت می‌کند
const CACHE_NAME = 'dozoleh-driver-v7.4';
const ASSETS = [
    '/driver/',
    '/driver/index.html',
    '/driver/manifest.json',
    '/driver/icon-192.png',
    '/driver/icon-512.png',
    '/driver/apple-touch-icon-180.png',
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
                    if (key.startsWith('dozoleh-driver-') && key !== CACHE_NAME) {
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

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if ('focus' in client) return client.focus();
            }
            return clients.openWindow('/driver/index.html');
        })
    );
});
