const CACHE_NAME = "scoutready-cache-v1";
const OFFLINE_URL = "/offline.html";

// Fonction pour charger la liste d'assets depuis le manifest AssetMapper
async function getAssetsToCache() {
    try {
        const response = await fetch("/assets/manifest.json", { cache: "no-store" });
        if (!response.ok) throw new Error("Manifest introuvable");
        const manifest = await response.json();
        const assets = Object.values(manifest);

        return [
            "/",
            "/intro",
            // "/intro/phone",
            "/accueil",
            "/activites",
            "/communaute",
            "/fonctionnalites",
            OFFLINE_URL,
            ...assets
        ];
    } catch (err) {
        console.warn("⚠️ Impossible de charger le manifest :", err);
        return [OFFLINE_URL];
    }
}

// Installation du Service Worker
self.addEventListener("install", (event) => {
    event.waitUntil(
        (async () => {
            const cache = await caches.open(CACHE_NAME);
            const urlsToCache = await getAssetsToCache();
            await cache.addAll(urlsToCache);
            console.log("✅ Cache initialisé :", urlsToCache);
        })()
    );
    self.skipWaiting();
});

// Activation — nettoyage des anciens caches
self.addEventListener("activate", (event) => {
    event.waitUntil(
        (async () => {
            const keys = await caches.keys();
            await Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        console.log("🧹 Suppression ancien cache :", key);
                        return caches.delete(key);
                    }
                })
            );
            self.clients.claim();
        })()
    );
});

// Interception des requêtes
self.addEventListener("fetch", (event) => {
    if (event.request.method !== "GET") return;

    event.respondWith(
        (async () => {
            try {
                // 🔹 Réponse réseau prioritaire
                const response = await fetch(event.request);
                const cache = await caches.open(CACHE_NAME);
                cache.put(event.request, response.clone());
                return response;
            } catch (error) {
                // 🔹 Fallback sur le cache
                const cached = await caches.match(event.request);
                if (cached) return cached;
                // 🔹 Fallback final : page offline
                return await caches.match(OFFLINE_URL);
            }
        })()
    );
});
