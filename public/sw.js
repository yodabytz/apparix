/**
 * Apparix - Service Worker
 * Network-first: always serves fresh content when online.
 * Cache is ONLY used as an offline fallback.
 */

const CACHE_NAME = 'apparix-v4';
const OFFLINE_URL = '/offline.html';

// Minimal precache — only the offline fallback page
const PRECACHE_ASSETS = [
    '/offline.html',
    '/android-chrome-192x192.png',
    '/favicon.ico'
];

// Install — cache offline fallback only
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(PRECACHE_ASSETS))
            .then(() => self.skipWaiting())
    );
});

// Activate — nuke ALL old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames
                    .filter(name => name !== CACHE_NAME)
                    .map(name => caches.delete(name))
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch — network first, cache only when offline
self.addEventListener('fetch', event => {
    const { request } = event;

    // Skip non-GET requests
    if (request.method !== 'GET') return;

    // Navigation requests (HTML pages)
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then(response => {
                    // Cache a copy for offline use
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
                    }
                    return response;
                })
                .catch(() => {
                    return caches.match(request)
                        .then(cached => cached || caches.match(OFFLINE_URL));
                })
        );
        return;
    }

    // All other requests (assets, images, scripts, etc.)
    // Always go to network first. Only fall back to cache when offline.
    event.respondWith(
        fetch(request)
            .then(response => {
                if (response.ok && request.url.startsWith(self.location.origin)) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
                }
                return response;
            })
            .catch(() => caches.match(request))
    );
});

// Background sync for cart operations
self.addEventListener('sync', event => {
    if (event.tag === 'sync-cart') {
        event.waitUntil(syncCart());
    }
});

async function syncCart() {
    const pendingActions = await getStoredCartActions();
    for (const action of pendingActions) {
        try {
            await fetch(action.url, {
                method: 'POST',
                body: JSON.stringify(action.data),
                headers: { 'Content-Type': 'application/json' }
            });
        } catch (e) {
            console.error('Cart sync failed:', e);
        }
    }
}

async function getStoredCartActions() {
    return [];
}

// Push notifications
self.addEventListener('push', event => {
    if (!event.data) return;

    const data = event.data.json();
    const options = {
        body: data.body || 'New update from your store',
        icon: '/android-chrome-192x192.png',
        badge: '/favicon-32x32.png',
        vibrate: [100, 50, 100],
        data: { url: data.url || '/' },
        actions: data.actions || []
    };

    event.waitUntil(
        self.registration.showNotification(data.title || 'Apparix', options)
    );
});

// Handle notification click
self.addEventListener('notificationclick', event => {
    event.notification.close();

    const url = event.notification.data?.url || '/';
    event.waitUntil(
        clients.matchAll({ type: 'window' }).then(clientList => {
            for (const client of clientList) {
                if (client.url === url && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});
