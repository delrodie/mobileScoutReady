// service-worker.js

// ✅ Nouvelle version du cache pour forcer la mise à jour et la nouvelle stratégie
const CACHE_NAME = "scoutready-cache-v1.0";
const OFFLINE_URL = "/offline.html";

// Fonction pour charger la liste d'assets depuis le manifest AssetMapper
async function getAssetsToCache() {
    // ... (Reste inchangé)
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
    // ... (Reste inchangé)
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
    // ... (Reste inchangé)
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

    const requestUrl = new URL(event.request.url);

    // Ne pas intercepter les requêtes non-HTTP (comme chrome-extension://)
    if (!requestUrl.protocol.startsWith('http')) {
        return;
    }

    // 1. Détection des images
    // Utilise event.request.destination 'image' OU vérifie l'extension du fichier
    const isImage = event.request.destination === 'image' ||
        requestUrl.pathname.match(/\.(jpe?g|png|gif|webp|svg)$/i);

    if (isImage) {
        // 🔹 STRATÉGIE CACHE-FIRST pour les IMAGES (Fiabilité Offline)
        event.respondWith(
            caches.match(event.request)
                .then(cachedResponse => {
                    // Si l'image est en cache, on la sert immédiatement.
                    if (cachedResponse) {
                        return cachedResponse;
                    }

                    // Sinon, on va sur le réseau pour la chercher
                    return fetch(event.request)
                        .then(response => {
                            // Vérifier la réponse avant de la mettre en cache
                            if (!response || response.status !== 200 || response.type === 'opaque') {
                                return response;
                            }

                            // Mettre en cache la nouvelle image
                            const responseToCache = response.clone();
                            caches.open(CACHE_NAME)
                                .then(cache => {
                                    cache.put(event.request, responseToCache);
                                });

                            return response;
                        })
                        .catch(error => {
                            // En cas d'échec du réseau et d'absence de cache (premier chargement offline)
                            console.error(`[SW] Échec du chargement image ${event.request.url}`, error);
                            // Le navigateur affichera l'icône "image brisée"
                            throw error;
                        });
                })
        );
        return; // Ne pas continuer vers la logique par défaut
    }

    // 2. STRATÉGIE PAR DÉFAUT (Network-First pour les HTML/API)
    event.respondWith(
        (async () => {
            try {
                // 🔹 Réponse réseau prioritaire
                const response = await fetch(event.request);
                const cache = await caches.open(CACHE_NAME);

                // Mettre en cache seulement les requêtes valides (status 200, pas opaque)
                if (response && response.status === 200 && response.type !== 'opaque') {
                    cache.put(event.request, response.clone());
                }

                return response;
            } catch (error) {
                // 🔹 Fallback sur le cache
                const cached = await caches.match(event.request);
                if (cached) return cached;

                // 🔹 Fallback final : page offline si la requête est pour du HTML (navigation)
                if (event.request.mode === 'navigate') {
                    return await caches.match(OFFLINE_URL);
                }
                // Si ce n'est pas un document HTML, on laisse l'erreur se propager
                throw error;
            }
        })()
    );
});
