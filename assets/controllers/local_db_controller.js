// assets/controllers/local_db_controller.js
import { Controller } from "@hotwired/stimulus";

const DB_NAME = 'db_scoutready'
const DB_VERSION = 1.2

/**
 * Contrôleur responsable de la gestion de la base locale IndexedDB.
 * - Vérifie la présence d’un profil enregistré
 * - Sauvegarde les données après la connexion
 * - Redirige automatiquement si un profil est déjà présent
 */
export default class extends Controller {
    connect() {
        console.log("🧩 LocalDbController connecté.");
        this.boundOnTurboLoad = this.onTurboLoad.bind(this);
        document.addEventListener('turbo:load', this.boundOnTurboLoad);
    }

    onTurboLoad(){
        console.log("turbo:load détecté. Vérification du profil local...")
        document.removeEventListener('turbo:load', this.boundOnTurboLoad);
        this.checkLocalProfile();
    }

    /**
     * Vérifie si un profil existe déjà dans la base locale.
     * Si oui → redirige vers /accueil
     */
    async checkLocalProfile() {
        try {
            const hasProfile = await this.hasLocalProfile();

            // URL de destination selon la présence du profil
            const destination = hasProfile ? "/accueil" : "/intro/phone";

            console.log(
                hasProfile
                    ? "Profil déjà présent localement. Redirection vers /accueil"
                    : "Aucun profil local trouvé. Redirection vers /intro/phone"
            );

            // 🧠 Vérifie si Turbo Native bridge est disponible
            const isBridgeReady =
                window.TurboNativeBridge &&
                typeof window.TurboNativeBridge.visit === "function";

            if (isBridgeReady) {
                // ✅ Utilise la navigation Turbo Native
                await window.TurboNativeBridge.visit(destination);
            } else {
                // ⚙️ Fallback classique navigateur
                console.warn("Turbo bridge non disponible, fallback vers window.location.href");
                window.location.href = destination;
            }
        } catch (error) {
            console.error("Erreur lors de la vérification du profil local :", error);

            // En cas d’erreur imprévue, on redirige vers la page de démarrage
            window.location.href = "/intro/phone";
        }
    }



    /**
     * Vérifie la présence de données dans IndexedDB
     */
    async hasLocalProfile() {
        return new Promise((resolve) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                if (!db.objectStoreNames.contains("profil"))
                    db.createObjectStore("profil", { keyPath: "slug" });
                if (!db.objectStoreNames.contains("profil_fonction"))
                    db.createObjectStore("profil_fonction", { keyPath: "id" });
                if (!db.objectStoreNames.contains("profil_instance"))
                    db.createObjectStore("profil_instance", { keyPath: "id" });
                if (!db.objectStoreNames.contains("champs_activite"))
                    db.createObjectStore("champs_activite", { keyPath: "id" });
            };

            request.onsuccess = (event) => {
                const db = event.target.result;
                const tx = db.transaction("profil", "readonly");
                const store = tx.objectStore("profil");
                const countRequest = store.count();

                countRequest.onsuccess = () => resolve(countRequest.result > 0);
                countRequest.onerror = () => resolve(false);
            };

            request.onerror = () => resolve(false);
        });
    }

    static async openDatabase(){
        return new Promise((resolve) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                if (!db.objectStoreNames.contains("profil"))
                    db.createObjectStore("profil", { keyPath: "slug" });
                if (!db.objectStoreNames.contains("profil_fonction"))
                    db.createObjectStore("profil_fonction", { keyPath: "id" });
                if (!db.objectStoreNames.contains("profil_instance"))
                    db.createObjectStore("profil_instance", { keyPath: "id" });
                if (!db.objectStoreNames.contains("champs_activite"))
                    db.createObjectStore("champs_activite", { keyPath: "id" });
            };

            request.onsuccess = (event) => resolve(event.target.result);
            request.onerror = () => resolve(false);
        });
    }

    static async getAllFromStore(storeName){
        const db = await this.openDatabase();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, "readonly");
            const store = tx.objectStore(storeName);
            const request = store.getAll();

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject("Erreur lecture store " + storeName);
        });
    }



    static async saveToIndexedDB(data) {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // 🔹 Création / mise à jour des stores si nécessaires
                if (!db.objectStoreNames.contains('profil')) {
                    db.createObjectStore('profil', { keyPath: 'slug' });
                }
                if (!db.objectStoreNames.contains('profil_fonction')) {
                    db.createObjectStore('profil_fonction', { keyPath: 'id' });
                }
                if (!db.objectStoreNames.contains('profil_instance')) {
                    db.createObjectStore('profil_instance', { keyPath: 'id' });
                }
                if (!db.objectStoreNames.contains('champs_activite')) {
                    db.createObjectStore('champs_activite', {keyPath: 'id'});
                }
            };

            request.onsuccess = async (event) => {
                const db = event.target.result;
                const tx = db.transaction(['profil', 'profil_fonction', 'profil_instance', 'champs_activite'], 'readwrite');

                const profilStore = tx.objectStore('profil');
                const fonctionStore = tx.objectStore('profil_fonction');
                const instanceStore = tx.objectStore('profil_instance');
                const champsStore = tx.objectStore('champs_activite');

                // --- PROFIL & FONCTIONS ---
                if (data.profil) {
                    profilStore.clear();
                    profilStore.put(data.profil);
                }
                if (data.profil_fonction) {
                    fonctionStore.clear();
                    fonctionStore.put(data.profil_fonction);
                }
                if (data.profil_instance) {
                    instanceStore.clear();
                    instanceStore.put(data.profil_instance);
                }

                // --- CHAMPS D'ACTIVITÉ ---
                // On vérifie si on a bien reçu les champs (objet DTO avec propriété 'champs')
                if (data.champs_activite && Array.isArray(data.champs_activite.champs)) {
                    champsStore.clear(); // On vide avant de remettre à jour

                    data.champs_activite.champs.forEach(champ => {
                        // IMPORTANT: Le DTO renvoie parfois un '0' en premier élément pour les select,
                        // ou null. On ne stocke que les vrais objets avec un ID.
                        if (champ && typeof champ === 'object' && champ.id) {
                            champsStore.put(champ);
                        }
                    });
                    console.log(`💾 ${data.champs_activite.champs.length} champs traités.`);
                }

                tx.oncomplete = async () => {
                    console.log("💾 Données texte sauvegardées. Lancement du téléchargement des médias...");

                    // On prépare toutes les promesses de téléchargement
                    const tasks = [];

                    // A. Tâche QR Code
                    if (data.profil && data.profil.qrCodeFile) {
                        tasks.push(this.processQrCode(data.profil.qrCodeFile, data.profil.slug));
                    }

                    // B. Tâche Images Activités (Parallélisation)
                    if (data.champs_activite && Array.isArray(data.champs_activite.champs)) {
                        data.champs_activite.champs.forEach(champ => {
                            if (champ && champ.id && champ.media) {
                                tasks.push(this.processChampImage(champ.media, champ.id));
                            }
                        });
                    }

                    // On attend que TOUT soit téléchargé avant de rouvrir la base UNE SEULE FOIS
                    try {
                        const results = await Promise.all(tasks);

                        // Si on a des résultats (images téléchargées), on sauvegarde tout en un bloc
                        if (results.length > 0) {
                            await this.batchSaveImages(results);
                        }

                        console.log("✅ Tous les médias ont été téléchargés et sauvegardés.");
                        resolve();

                    } catch (err) {
                        console.warn("⚠️ Erreur lors du téléchargement des médias (mode offline partiel) :", err);
                        resolve(); // On resolve quand même pour ne pas bloquer l'app
                    }
                };

                tx.onerror = (e) => reject(e.target.error);
            };

            request.onerror = (e) => reject(e.target.error);
        });
    }

    static async processQrCode(url, slug) {
        try {
            const blobUrl = await this.fetchBlobUrl(url);
            return { type: 'profil', key: slug, field: 'qrCodeBlob', value: blobUrl };
        } catch (e) {
            console.warn("Skip QR Code:", e);
            return null;
        }
    }

    static async processChampImage(url, id) {
        try {
            const blobUrl = await this.fetchBlobUrl(url);
            return { type: 'champs_activite', key: id, field: 'champActiviteBlob', value: blobUrl };
        } catch (e) {
            console.warn(`Skip image ${id}:`, e);
            return null;
        }
    }

    static async fetchBlobUrl(url) {
        if (!url) throw new Error("URL vide");
        const absoluteUrl = url.startsWith('http') ? url : `${window.location.origin}/${url.replace(/^\/+/, '')}`;
        const response = await fetch(absoluteUrl);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const blob = await response.blob();
        return URL.createObjectURL(blob);
    }

    // --- Sauvegarde groupée (Batch) ---

    static async batchSaveImages(items) {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onsuccess = (event) => {
                const db = event.target.result;
                const tx = db.transaction(['profil', 'champs_activite'], 'readwrite');

                items.forEach(item => {
                    if (!item) return; // Ignore les échecs

                    const store = tx.objectStore(item.type);
                    const getReq = store.get(item.key);

                    getReq.onsuccess = () => {
                        const record = getReq.result;
                        if (record) {
                            record[item.field] = item.value;
                            store.put(record);
                        }
                    };
                });

                tx.oncomplete = () => resolve();
                tx.onerror = (e) => reject(e);
            };
        });
    }

}
