const CACHE_NAME = 'scoutready-cache-v1';
const OFFLINE_URL = '/offline.html';

self.addEventListener('install', async (event) => {
    console.log('📦 Installation du service worker...');

    event.waitUntil(
        (async () => {
            const cache = await caches.open(CACHE_NAME);
            try {
                const appAssets = self.APP_ASSETS || {};
                const localAssets = appAssets.local ? Object.values(appAssets.local) : [];
                const cdnAssets = appAssets.cdn || [];

                const urlsToCache = [
                    '/',
                    '/accueil/',
                    '/activites/',
                    '/communaute/',
                    'fonctionnalites/',
                    OFFLINE_URL,
                    ...localAssets,
                    ...cdnAssets,
                ];

                await cache.addAll(urlsToCache);
                console.log('✅ Mise en cache réussie :', urlsToCache);
            } catch (err) {
                console.warn('⚠️ Erreur lors de la mise en cache initiale :', err);
            }
        })()
    );

    self.skipWaiting();
});

self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request)
            .then((response) => response || fetch(event.request))
            .catch(() => caches.match(OFFLINE_URL))
    );
});
