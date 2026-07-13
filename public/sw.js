const CACHE_NAME = 'dozoleh-web-shell-v1';
const STATIC_ASSETS = [
    '/manifest.webmanifest',
    '/driver/icon-192.png',
    '/driver/icon-512.png',
    '/assets/fonts/Vazirmatn-Regular.woff2',
    '/assets/fonts/Vazirmatn-Bold.woff2'
];

self.addEventListener('install', event => {
    event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS)));
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(key => key.startsWith('dozoleh-web-shell-') && key !== CACHE_NAME).map(key => caches.delete(key)))));
    self.clients.claim();
});

self.addEventListener('fetch', event => {
    if (event.request.method !== 'GET') return;
    const url = new URL(event.request.url);
    if (url.origin !== self.location.origin || !STATIC_ASSETS.includes(url.pathname)) return;
    event.respondWith(caches.match(event.request).then(cached => cached || fetch(event.request)));
});
