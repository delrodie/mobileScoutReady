import { Controller } from "@hotwired/stimulus";

export const DB_NAME = 'db_scoutready'
export const DB_VERSION = 1.3

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
                if (!db.objectStoreNames.contains("profil_infocomplementaire"))
                    db.createObjectStore("profil_infocomplementaire", { keyPath: "id" });
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
                if (!db.objectStoreNames.contains("profil_infocomplementaire"))
                    db.createObjectStore("profil_infocomplementaire", { keyPath: "id" });
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


    /**
     * Sauvegarde les données et gère l'affichage du loader
     */
    static async saveToIndexedDB(data) {
        // 1. AFFICHER LE LOADER
        this.showLoader();

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
                if (!db.objectStoreNames.contains('profil_infocomplementaire')) {
                    db.createObjectStore('profil_infocomplementaire', {keyPath: 'id'});
                }
            };

            request.onsuccess = async (event) => {
                const db = event.target.result;
                const tx = db.transaction(['profil', 'profil_fonction', 'profil_instance', 'profil_infocomplementaire', 'champs_activite'], 'readwrite');

                const profilStore = tx.objectStore('profil');
                const fonctionStore = tx.objectStore('profil_fonction');
                const instanceStore = tx.objectStore('profil_instance');
                const infocomplementaireStore = tx.objectStore('profil_infocomplementaire');
                const champsStore = tx.objectStore('champs_activite');

                // --- PROFIL & FONCTIONS ---
                if (data.profil) {
                    profilStore.clear();
                    // ✅ Nettoyage des champs Blob inutiles
                    delete data.profil.qrCodeBlob;
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
                if (data.profil_infocomplementaire) {
                    infocomplementaireStore.clear();
                    infocomplementaireStore.put(data.profil_infocomplementaire);
                }

                // --- CHAMPS D'ACTIVITÉ ---
                if (data.champs_activite && Array.isArray(data.champs_activite.champs)) {
                    champsStore.clear();

                    data.champs_activite.champs.forEach(champ => {
                        if (champ && typeof champ === 'object' && champ.id) {
                            // ✅ Nettoyage des champs Blob inutiles
                            delete champ.champActiviteBlob;
                            champsStore.put(champ);
                        }
                    });
                    console.log(`💾 ${data.champs_activite.champs.length} champs traités.`);
                }

                tx.oncomplete = async () => {
                    // ✅ Logique de téléchargement de média retirée
                    console.log("✅ Données texte sauvegardées. Le Service Worker gère le cache des médias.");

                    // 2a. CACHER LE LOADER (Succès)
                    this.hideLoader();
                    resolve();
                };

                tx.onerror = (e) => {
                    // 2b. CACHER LE LOADER (Erreur Transaction)
                    this.hideLoader();
                    reject(e.target.error);
                };
            };

            request.onerror = (e) => {
                // 2c. CACHER LE LOADER (Erreur Ouverture)
                this.hideLoader();
                reject(e.target.error);
            };
        });
    }

    // --- Les méthodes processQrCode, processChampImage, fetchBlobUrl, batchSaveImages sont supprimées ---

    // --- UI HELPERS POUR LE LOADER ---

    static showLoader() {
        // Crée le loader s'il n'existe pas déjà dans le DOM
        let loader = document.getElementById('db-save-loader');
        if (!loader) {
            loader = document.createElement('div');
            loader.id = 'db-save-loader';
            // Styles inline pour assurer que ça marche sans framework CSS externe
            loader.innerHTML = `
                <div style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; display:flex; flex-direction:column; justify-content:center; align-items:center; color:white; font-family:system-ui, sans-serif;">
                    <style>
                        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
                        .loader-spinner { border: 4px solid rgba(255,255,255,0.3); border-top: 4px solid white; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin-bottom: 20px; }
                    </style>
                    <div class="loader-spinner"></div>
                    <p style="font-size: 1.1rem; font-weight: 500;">Synchronisation des données...</p>
                    <p style="font-size: 0.9rem; opacity: 0.8; margin-top: 5px;">Veuillez ne pas quitter.</p>
                </div>
            `;
            document.body.appendChild(loader);
        }
        loader.style.display = 'flex';
    }

    static hideLoader() {
        const loader = document.getElementById('db-save-loader');
        if (loader) {
            // Petit délai pour éviter le flash si c'est trop rapide
            setTimeout(() => {
                loader.style.display = 'none';
            }, 300);
        }
    }
}
