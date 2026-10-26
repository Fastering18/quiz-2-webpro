// Service Worker for myITS Classroom (PWA)
const CACHE_NAME = 'myits-classroom-v1';

const STATIC_PRECACHE = [
    '/offline.html',
    '/global.css',
    '/dashboard.css',
    '/style.css',
    '/components.js',
    '/js/api.js',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/icon-maskable-192.png',
    '/icons/apple-touch-icon.png',
    '/favicon.png',
    '/manifest.webmanifest',
    '/image/logo_its_aseli.png',
    '/image/Classroom.png',
    '/svg/Admin.svg',
    '/svg/Books.svg',
    '/svg/Literature.svg',
    '/svg/Page.svg',
    '/svg/Portal.svg',
    '/svg/Speech Bubble.svg',
    '/svg/User.svg'
];

// Install: precache essential offline and static shell assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_PRECACHE).catch((err) => {
                console.warn('[SW] Pre-caching partial failure:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// Activate: clean up older caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch handler
self.addEventListener('fetch', (event) => {
    const { request } = event;

    // 1. Only intercept GET requests
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // 2. Ignore non-http/https or foreign domains
    if (url.origin !== self.location.origin) {
        return;
    }

    // 3. Never cache dynamic API calls or authentication routes
    if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/sanctum/') || url.pathname === '/logout') {
        return;
    }

    // 4. HTML Navigation: Network-first with offline fallback
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .catch(async () => {
                    const cache = await caches.open(CACHE_NAME);
                    const cachedResponse = await cache.match(request);
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    return cache.match('/offline.html');
                })
        );
        return;
    }

    // 5. Static Assets (CSS, JS, Fonts, Images): Stale-While-Revalidate
    if (
        request.destination === 'style' ||
        request.destination === 'script' ||
        request.destination === 'image' ||
        request.destination === 'font' ||
        url.pathname.endsWith('.css') ||
        url.pathname.endsWith('.js') ||
        url.pathname.endsWith('.svg') ||
        url.pathname.endsWith('.png') ||
        url.pathname.endsWith('.jpg') ||
        url.pathname.endsWith('.webmanifest')
    ) {
        event.respondWith(
            caches.open(CACHE_NAME).then(async (cache) => {
                const cached = await cache.match(request);
                const fetchPromise = fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        cache.put(request, networkResponse.clone());
                    }
                    return networkResponse;
                }).catch(() => null);

                return cached || fetchPromise;
            })
        );
        return;
    }
});
