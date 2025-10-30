// assets/controllers/local_db_controller.js
import { Controller } from "@hotwired/stimulus";

const DB_NAME = 'db_scoutready'
const DB_VERSION = 1

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
     * Methode pour sauvegarder les données dans la base locale
     * @param data
     * @returns {Promise<unknown>}
     */
    static async saveProfilData(data) {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION)

            request.onupgradeneeded = (event) => {
                const db = event.target.result
                db.createObjectStore('profil', { keyPath: 'slug' })
                db.createObjectStore('profil_fonction', { keyPath: 'id' })
                db.createObjectStore('profil_instance', { keyPath: 'id' })
            }

            request.onsuccess = (event) => {
                const db = event.target.result
                const tx = db.transaction(['profil', 'profil_fonction', 'profil_instance'], 'readwrite')

                // Nettoyage avant nouvelle insertion
                tx.objectStore('profil').clear()
                tx.objectStore('profil_fonction').clear()
                tx.objectStore('profil_instance').clear()

                // Insertion
                tx.objectStore('profil').put(data.profil)
                tx.objectStore('profil_fonction').put(data.profil_fonction)
                tx.objectStore('profil_instance').put(data.profil_instance)

                tx.oncomplete = () => {
                    console.log("💾 Données sauvegardées avec succès dans IndexedDB")
                    resolve()
                }

                tx.onerror = (e) => reject(e.target.error)
            }

            request.onerror = (e) => reject(e.target.error)
        })
    }


}
